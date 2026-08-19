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
 */
(function () {
  'use strict';

  var ACTION_SELECTOR = '[data-ps-action="cart-preview-update"]';
  var PREVIEW_SELECTOR = '[data-ps-ref="cart-preview"]';
  var MODAL_SELECTOR = '[data-ps-ref="blockcart-modal"]';
  var QTY_GROUP_SELECTOR = '[data-ps-ref="cart-preview-qty"]';
  var QTY_VALUE_SELECTOR = '[data-ps-target="cart-preview-qty-value"]';

  var pending = 0;
  var queue = Promise.resolve();
  var refreshSeq = 0;

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

  /**
   * Show the new quantity immediately. Anything derived from it (prices, cart
   * total, free-shipping bar) is left to the server render, and so is a
   * decrease down to zero, which removes the line altogether.
   *
   * Fabric sold by the metre (pproperties) is skipped on purpose: its quantity
   * link carries a step (&qty=0.2) and the rendered value is not a plain number
   * but the module's own wording — "1,6 m" or even "2 x 0,8 m". Counting that in
   * the browser flashed a piece count where metres belong, so those lines wait
   * for the server render instead.
   */
  function bumpQuantity(link) {
    var op = link.getAttribute('data-ps-qty-op');
    var group = link.closest(QTY_GROUP_SELECTOR);

    if ((op !== 'up' && op !== 'down') || !group) {
      return;
    }

    if (/[?&]qty=/.test(link.getAttribute('href') || '')) {
      group.setAttribute('data-ps-state', 'pending');

      return;
    }

    var value = group.querySelector(QTY_VALUE_SELECTOR);

    if (!value) {
      return;
    }

    var shown = value.textContent.trim();

    if (!/^\d+$/.test(shown)) {
      group.setAttribute('data-ps-state', 'pending');

      return;
    }

    var current = parseInt(shown, 10);

    if (isNaN(current) || (op === 'down' && current <= 1)) {
      return;
    }

    value.textContent = String(op === 'up' ? current + 1 : current - 1);
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

  function enqueueUpdate(link) {
    var prestashop = getPrestashop();
    var url = link.getAttribute('href');

    if (!url || !prestashop || typeof prestashop.emit !== 'function') {
      return;
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

    bumpQuantity(link);
    enqueueUpdate(link);
  });
})();
