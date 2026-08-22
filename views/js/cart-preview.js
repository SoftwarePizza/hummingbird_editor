/**
 * Cart preview interactions (quantity +/- and remove) for the hover panel and
 * the add-to-cart modal replacement.
 *
 * Managed by hummingbird_editor so the ps_shoppingcart core module stays
 * untouched. Vanilla JS (no jQuery).
 *
 * Flow of a +/- click:
 *  1. the displayed quantity is bumped right away, so the click feels applied;
 *  2. the cart change is POSTed to the core cart controller URL carried by the
 *     link's href (requests are queued, so rapid clicks cannot race);
 *  3. 'updateCart' is emitted on the PrestaShop event bus, which makes
 *     ps_shoppingcart.js re-render the header .blockcart (badge + hover panel);
 *  4. the modal is not covered by that refresh, so once the queue drains its
 *     preview is re-rendered from the module's cartpreview controller. That
 *     server render is what corrects quantities, line totals, the free-shipping
 *     bar and the cart total — the optimistic bump in step 1 is only a
 *     placeholder until it lands.
 *
 * One click = one whole unit: 1 piece, or 1 metre of fabric sold by the metre.
 * The preview has room for a single pair of buttons, so the fine 0,1 m step
 * stays where there is room for two pairs — the cart page. The quantity the
 * click lands on is worked out here (clamped to the line minimum and to the
 * stock the template rendered into data-ps-max), and the request carries the
 * resulting difference, so queued clicks add up exactly.
 */
(function () {
  'use strict';

  var ACTION_SELECTOR = '[data-ps-action="cart-preview-update"]';
  var PREVIEW_SELECTOR = '[data-ps-ref="cart-preview"]';
  var MODAL_SELECTOR = '[data-ps-ref="blockcart-modal"]';
  var QTY_GROUP_SELECTOR = '[data-ps-ref="cart-preview-qty"]';
  var QTY_VALUE_SELECTOR = '[data-ps-target="cart-preview-qty-value"]';

  // One click = one whole unit (1 piece, or 1 metre of fabric).
  var STEP = 1;
  var EPSILON = 0.000001;

  var pending = 0;
  var queue = Promise.resolve();
  var refreshSeq = 0;
  var decimalSign = null;

  function getPrestashop() {
    return window.prestashop || null;
  }

  function toArray(nodeList) {
    return Array.prototype.slice.call(nodeList);
  }

  /**
   * Previews the core refresh does not reach. ps_shoppingcart.js replaces the
   * whole header .blockcart (hover panel included), but leaves the modal as it
   * was rendered when the product was added.
   */
  function getStalePreviews() {
    return toArray(document.querySelectorAll(MODAL_SELECTOR + ' ' + PREVIEW_SELECTOR));
  }

  function getRefreshUrl(preview) {
    var raw = preview.getAttribute('data-ps-data');

    if (!raw) {
      return '';
    }

    try {
      var data = JSON.parse(raw);

      return data && data.refreshUrl ? data.refreshUrl : '';
    } catch (err) {
      return '';
    }
  }

  function setLoading(isLoading) {
    getStalePreviews().forEach(function (preview) {
      if (isLoading) {
        preview.setAttribute('data-ps-state', 'loading');
      } else {
        preview.removeAttribute('data-ps-state');
      }
    });
  }

  function toNumber(raw) {
    if (raw === null || raw === '') {
      return null;
    }

    var value = parseFloat(raw);

    return isNaN(value) ? null : value;
  }

  function round6(value) {
    return Math.round(value * 1000000) / 1000000;
  }

  /**
   * The shop's decimal separator — the one pproperties writes quantities with
   * ("1,6 m" in Polish, "1.6 m" in English). Taken from the page language,
   * because the preview runs on 15 domains speaking different languages.
   */
  function getDecimalSign() {
    if (decimalSign === null) {
      try {
        decimalSign = (1.1).toLocaleString(document.documentElement.lang || undefined).charAt(1);
      } catch (err) {
        decimalSign = '.';
      }

      if (decimalSign !== ',' && decimalSign !== '.') {
        decimalSign = '.';
      }
    }

    return decimalSign;
  }

  /** Same as PP::formatQty: fixed decimals, or no trailing zeroes at all. */
  function formatQuantity(value, decimals) {
    var text = decimals > 0 ? value.toFixed(decimals) : String(round6(value));

    return text.replace('.', getDecimalSign());
  }

  /** First text node holding a digit — pproperties wraps quantities in a span. */
  function firstNumberNode(element) {
    var walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT, null, false);
    var node;

    while ((node = walker.nextNode())) {
      if (/\d/.test(node.nodeValue)) {
        return node;
      }
    }

    return null;
  }

  /**
   * Replaces just the number inside the displayed quantity, leaving the rest
   * of the text (the unit) alone, and records the new value in data-ps-qty so
   * further clicks count from it rather than from what is still on screen.
   */
  function writeQuantity(group, value, decimals) {
    var target = group.querySelector(QTY_VALUE_SELECTOR);

    group.setAttribute('data-ps-qty', String(round6(value)));

    if (!target) {
      return;
    }

    var text = formatQuantity(value, decimals);
    var node = firstNumberNode(target);

    if (node) {
      node.nodeValue = node.nodeValue.replace(/\d+(?:[.,]\d+)?/, text);
    } else {
      target.textContent = text;
    }
  }

  /**
   * The quantity a click lands on, and the difference to send to the server.
   *
   * Returns `null` when the link is not a quantity button (the remove link) or
   * when the quantity cannot be counted in the browser — several coupons of the
   * same fabric read as "2 x 0,8 m". Those lines get `data-ps-state="pending"`
   * (CSS dims the number) and wait for the server render. `{skip: true}` means
   * there is nothing to send: the line already holds the whole stock carried by
   * data-ps-max.
   */
  function planQuantity(link) {
    var op = link.getAttribute('data-ps-qty-op');
    var group = link.closest(QTY_GROUP_SELECTOR);

    if ((op !== 'up' && op !== 'down') || !group) {
      return null;
    }

    var current = toNumber(group.getAttribute('data-ps-qty'));

    if (current === null) {
      group.setAttribute('data-ps-state', 'pending');

      return null;
    }

    var min = toNumber(group.getAttribute('data-ps-min'));
    var max = toNumber(group.getAttribute('data-ps-max'));
    var decimals = parseInt(group.getAttribute('data-ps-decimals'), 10) || 0;
    var target;

    if (op === 'up') {
      target = round6(current + STEP);

      if (max !== null && target > max) {
        target = max;
      }

      if (target <= current + EPSILON) {
        // Whole stock already in the cart: dim the "+" instead of asking again.
        group.setAttribute('data-ps-state', 'max');

        return {skip: true};
      }
    } else {
      target = round6(current - STEP);

      if (min !== null && target < min - EPSILON) {
        // The last step down reaches the minimum; the next one drops the line.
        target = current > min + EPSILON ? min : 0;
      }

      if (target < 0) {
        target = 0;
      }
    }

    var difference = round6(Math.abs(target - current));

    if (difference <= 0) {
      return {skip: true};
    }

    if (max !== null && target >= max - EPSILON) {
      group.setAttribute('data-ps-state', 'max');
    } else {
      group.removeAttribute('data-ps-state');
    }

    if (target > 0) {
      writeQuantity(group, target, decimals);
    }

    // Target 0 means the line is on its way out, so the number is left as it
    // is — the server render takes it away together with the whole row.
    return {quantity: difference};
  }

  /** Puts the computed difference into the link's `qty` parameter. */
  function withQuantity(url, quantity) {
    var value = encodeURIComponent(String(quantity));

    if (/[?&]qty=/.test(url)) {
      return url.replace(/([?&])qty=[^&]*/, '$1qty=' + value);
    }

    return url + (url.indexOf('?') === -1 ? '?' : '&') + 'qty=' + value;
  }

  function swapPreview(preview, html) {
    var template = document.createElement('template');
    template.innerHTML = html.trim();

    var fresh = template.content.firstElementChild;

    if (fresh) {
      preview.replaceWith(fresh);
    }
  }

  function refreshPreviews() {
    var previews = getStalePreviews();

    if (!previews.length) {
      return Promise.resolve();
    }

    var url = getRefreshUrl(previews[0]);

    if (!url) {
      return Promise.resolve();
    }

    refreshSeq += 1;
    var seq = refreshSeq;

    return fetch(url, {
      method: 'GET',
      credentials: 'same-origin',
      headers: {'X-Requested-With': 'XMLHttpRequest'},
    })
      .then(function (resp) {
        return resp.json();
      })
      .then(function (data) {
        // A later refresh already started: its response is the current one.
        if (seq !== refreshSeq || !data || typeof data.html !== 'string') {
          return;
        }

        getStalePreviews().forEach(function (preview) {
          swapPreview(preview, data.html);
        });
      })
      .catch(function () {
        // Keep what is on screen: the server render is a correction, not the
        // cart change itself, which already went through.
        setLoading(false);
      });
  }

  function postUpdate(url, reason) {
    var prestashop = getPrestashop();
    var formData = new FormData();

    formData.append('ajax', '1');
    formData.append('action', 'update');

    return fetch(url, {method: 'POST', body: formData, credentials: 'same-origin'})
      .then(function (resp) {
        return resp.json().catch(function () {
          return {};
        });
      })
      .then(function (data) {
        // Core contract: ps_shoppingcart.js refreshes the header block from this.
        prestashop.emit('updateCart', {reason: reason, resp: data});
      })
      .catch(function (err) {
        prestashop.emit('handleError', {eventType: 'updateProductInCart', resp: err});
      });
  }

  function enqueueUpdate(link, plan) {
    var prestashop = getPrestashop();
    var url = link.getAttribute('href');

    if (!url || !prestashop || typeof prestashop.emit !== 'function') {
      return;
    }

    if (plan && plan.quantity) {
      url = withQuantity(url, plan.quantity);
    }

    // The link is replaced by the server render, so keep what the event bus needs.
    var reason = Object.assign({}, link.dataset);

    pending += 1;
    setLoading(true);

    queue = queue
      .then(function () {
        return postUpdate(url, reason);
      })
      .then(function () {
        pending -= 1;

        // Re-render once, when the last queued click has been applied.
        if (pending === 0) {
          return refreshPreviews();
        }
      })
      .catch(function () {
        // A rejected queue would swallow every later click, so it always ends
        // settled, even if this update failed.
        pending = 0;
        setLoading(false);
      });
  }

  document.addEventListener('click', function (event) {
    var target = event.target;

    if (!target || typeof target.closest !== 'function') {
      return;
    }

    var link = target.closest(ACTION_SELECTOR);

    if (!link) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();

    var plan = planQuantity(link);

    if (plan && plan.skip) {
      return;
    }

    enqueueUpdate(link, plan);
  });
})();
