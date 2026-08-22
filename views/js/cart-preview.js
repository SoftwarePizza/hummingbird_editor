/**
 * Cart preview interactions (quantity +/- and remove) for the hover panel and
 * the add-to-cart modal replacement.
 *
 * Managed by hummingbird_editor so the ps_shoppingcart core module stays
 * untouched. Vanilla JS (no jQuery).
 *
 * The quantity is settled in the browser first; only the result travels to the
 * server:
 *  1. what the line IS comes from the template, before anything can be clicked:
 *     fabric sold by the metre (pproperties qty_policy = 2, quantities carry a
 *     comma), goods sold in pieces (whole numbers), a sample (no buttons at all
 *     — it costs nothing at any quantity), or a line the browser cannot count,
 *     because several coupons of one fabric read as "2 x 0,8 m" (no data-ps-qty:
 *     such a click goes straight to the server and waits for its render);
 *  2. clicking, holding the button down or typing the number changes what is on
 *     screen at once, with no request in between — going from 35 m to 12 m
 *     costs no waiting and no 23 round trips;
 *  3. SETTLE_DELAY after the last change, one request per line carries the whole
 *     difference, and 'updateCart' on the PrestaShop event bus makes
 *     ps_shoppingcart.js re-render the header .blockcart (badge + hover panel);
 *  4. the modal is not covered by that refresh, so once the queue drains its
 *     preview is re-rendered from the module's cartpreview controller. That
 *     server render is what corrects line totals, the free-shipping bar, the
 *     discount tiers and the cart total — until it lands, every amount on
 *     screen is dimmed, because it belongs to the previous quantity.
 *
 * Clicking steps by one whole unit (1 piece, 1 metre); the fine step from
 * pproperties (0,1 m) is reachable by typing, and on the cart page, which has
 * room for two pairs of buttons.
 */
(function () {
  'use strict';

  var ACTION_SELECTOR = '[data-ps-action="cart-preview-update"]';
  var PREVIEW_SELECTOR = '[data-ps-ref="cart-preview"]';
  var MODAL_SELECTOR = '[data-ps-ref="blockcart-modal"]';
  var QTY_GROUP_SELECTOR = '[data-ps-ref="cart-preview-qty"]';
  var QTY_VALUE_SELECTOR = '[data-ps-target="cart-preview-qty-value"]';
  var ROW_SELECTOR = '.cart-preview-product';
  var INPUT_CLASS = 'cart-preview-product__qty-input';

  // Quiet time after the last change before the cart is told about it. Long
  // enough to swallow a burst of clicks, short enough not to feel deferred.
  var SETTLE_DELAY = 650;
  // …but a number typed and confirmed is a finished decision, not a burst.
  var TYPED_DELAY = 150;

  // Press and hold: first repeat, then every REPEAT_DELAY, speeding up.
  var HOLD_DELAY = 400;
  var REPEAT_DELAY = 140;
  var REPEAT_FAST = 60;
  var REPEATS_BEFORE_FAST = 6;

  var EPSILON = 0.000001;

  var states = new WeakMap();
  var pending = 0;
  var queue = Promise.resolve();
  var refreshSeq = 0;
  var decimalSign = null;
  var settleTimer = null;
  var hold = null;
  var suppressClick = false;

  function getPrestashop() {
    return window.prestashop || null;
  }

  function toArray(nodeList) {
    return Array.prototype.slice.call(nodeList);
  }

  function getPreviews() {
    return toArray(document.querySelectorAll(PREVIEW_SELECTOR));
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

  /**
   * Amounts belong to the quantity the server knows about, so they are dimmed
   * from the first click — through the whole local editing — until the server
   * render brings them back in sync.
   */
  function setLoading(isLoading) {
    getPreviews().forEach(function (preview) {
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

  /** How many decimals the quantity step allows: 0,1 → 1, 0,125 → 3, 1 → 0. */
  function decimalsOfStep(step) {
    var text = String(step);
    var dot = text.indexOf('.');

    return dot === -1 ? 0 : Math.min(6, text.length - dot - 1);
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
   * What kind of line this is, and the room it leaves for a new quantity — read
   * once from the attributes the template rendered, then kept in step with what
   * the customer does. `server` is what the cart holds, `display` what is on
   * screen; the difference between them is what still has to be sent.
   *
   * Returns null for lines the browser cannot count on its own.
   */
  function readState(group) {
    var state = states.get(group);

    if (state) {
      return state;
    }

    var current = toNumber(group.getAttribute('data-ps-qty'));

    if (current === null) {
      return null;
    }

    var step = toNumber(group.getAttribute('data-ps-step'));
    var click = toNumber(group.getAttribute('data-ps-click-step'));

    if (step === null || step <= 0) {
      step = 1;
    }

    if (click === null || click <= 0) {
      click = 1;
    }

    state = {
      kind: group.getAttribute('data-ps-kind') === 'decimal' ? 'decimal' : 'units',
      server: current,
      display: current,
      min: toNumber(group.getAttribute('data-ps-min')),
      max: toNumber(group.getAttribute('data-ps-max')),
      step: step,
      click: click,
      // Fixed decimals for display (0 = trim trailing zeroes, like PP::formatQty),
      // and the precision the step allows, which is what values are rounded to.
      decimals: parseInt(group.getAttribute('data-ps-decimals'), 10) || 0,
      precision: decimalsOfStep(step),
    };

    states.set(group, state);

    return state;
  }

  /**
   * Rounds to the precision the step allows and keeps the value in range.
   * Pieces cannot be cut in half, and a typed "2,5 szt" means 2 rather than 3 —
   * the same reading the quantity field on the product page settled on.
   */
  function fit(value, state) {
    var fitted = state.kind === 'units'
      ? Math.floor(value + EPSILON)
      : parseFloat(value.toFixed(state.precision));

    if (state.min !== null && fitted < state.min) {
      fitted = state.min;
    }

    if (state.max !== null && fitted > state.max) {
      fitted = state.max;
    }

    return round6(fitted);
  }

  /** Dims the button that has nothing left to do on this line. */
  function markLimits(group, state) {
    if (state.max !== null && state.display >= state.max - EPSILON) {
      group.setAttribute('data-ps-at-max', '');
    } else {
      group.removeAttribute('data-ps-at-max');
    }

    if (state.min !== null && state.display <= state.min + EPSILON) {
      group.setAttribute('data-ps-at-min', '');
    } else {
      group.removeAttribute('data-ps-at-min');
    }
  }

  /**
   * Replaces just the number inside the displayed quantity, leaving the rest of
   * the text (the unit) alone, and records it in data-ps-qty so a preview
   * re-render started elsewhere reads the same value back.
   */
  function writeQuantity(group, state) {
    var target = group.querySelector(QTY_VALUE_SELECTOR);

    group.setAttribute('data-ps-qty', String(round6(state.display)));

    if (!target) {
      return;
    }

    var text = formatQuantity(state.display, state.decimals);
    var node = firstNumberNode(target);

    if (node) {
      node.nodeValue = node.nodeValue.replace(/\d+(?:[.,]\d+)?/, text);
    } else {
      target.textContent = text;
    }
  }

  function setDisplay(group, state, value, delay) {
    var target = fit(value, state);

    if (Math.abs(target - state.display) <= EPSILON) {
      markLimits(group, state);

      return false;
    }

    state.display = target;
    writeQuantity(group, state);
    markLimits(group, state);
    setLoading(true);
    scheduleSettle(delay);

    return true;
  }

  function scheduleSettle(delay) {
    if (settleTimer) {
      clearTimeout(settleTimer);
    }

    settleTimer = setTimeout(settle, delay || SETTLE_DELAY);
  }

  /** Lines whose displayed quantity has not reached the cart yet. */
  function unsettledGroups() {
    return toArray(document.querySelectorAll(QTY_GROUP_SELECTOR)).filter(function (group) {
      var state = states.get(group);

      return state && Math.abs(state.display - state.server) > EPSILON;
    });
  }

  /** One click, or one repeat of a held button. */
  function stepBy(group, direction) {
    var state = readState(group);

    if (!state) {
      // Nothing to count with ("2 x 0,8 m"): the server answers this one.
      return false;
    }

    return setDisplay(group, state, state.display + direction * state.click);
  }

  /** Puts a quantity into the link's `qty` parameter. */
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
      setLoading(false);

      return Promise.resolve();
    }

    var url = getRefreshUrl(previews[0]);

    if (!url) {
      setLoading(false);

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

        // The customer started changing quantities again while this was in
        // flight: their number is newer than this render, so it stays.
        if (unsettledGroups().length) {
          return;
        }

        getStalePreviews().forEach(function (preview) {
          swapPreview(preview, data.html);
        });

        setLoading(false);
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

  function enqueue(url, reason, onFailure) {
    var prestashop = getPrestashop();

    if (!url || !prestashop || typeof prestashop.emit !== 'function') {
      return;
    }

    pending += 1;
    setLoading(true);

    queue = queue
      .then(function () {
        return postUpdate(url, reason);
      })
      .then(function () {
        pending -= 1;

        // Re-render once, when the last queued change has been applied — and
        // only if nothing new is waiting to be sent.
        if (pending === 0 && !unsettledGroups().length) {
          return refreshPreviews();
        }
      })
      .catch(function () {
        // A rejected queue would swallow every later change, so it always ends
        // settled, even if this update failed.
        pending = 0;

        if (typeof onFailure === 'function') {
          onFailure();
        }

        setLoading(false);
      });
  }

  /** Sends the difference each line has accumulated: one request per line. */
  function settle() {
    settleTimer = null;

    unsettledGroups().forEach(function (group) {
      var state = states.get(group);
      var difference = round6(state.display - state.server);
      var op = difference > 0 ? 'up' : 'down';
      var link = group.querySelector('[data-ps-qty-op="' + op + '"]');

      if (!link) {
        return;
      }

      var url = withQuantity(link.getAttribute('href'), Math.abs(difference));
      // The link is replaced by the server render, so keep what the bus needs.
      var reason = Object.assign({}, link.dataset);

      state.server = state.display;

      enqueue(url, reason, function () {
        state.server = round6(state.server - difference);
      });
    });
  }

  /**
   * Typing the quantity in. The number turns into an input holding the same
   * text, so a customer who wants 12 m out of 35 m does not have to click 23
   * times; the fine 0,1 m step of a fabric is reachable this way too.
   */
  function openEditor(group) {
    var state = readState(group);
    var value = group.querySelector(QTY_VALUE_SELECTOR);

    if (!state || !value || group.querySelector('.' + INPUT_CLASS)) {
      return;
    }

    var input = document.createElement('input');

    input.type = 'text';
    input.className = INPUT_CLASS;
    input.value = formatQuantity(state.display, state.decimals);
    input.autocomplete = 'off';
    input.setAttribute('inputmode', state.kind === 'decimal' ? 'decimal' : 'numeric');
    input.setAttribute('aria-label', group.getAttribute('aria-label') || '');

    var closed = false;

    function close(commit) {
      if (closed) {
        return;
      }

      closed = true;

      if (commit) {
        var typed = parseFloat(input.value.replace(',', '.'));

        if (!isNaN(typed)) {
          setDisplay(group, state, typed, TYPED_DELAY);
        }
      }

      input.remove();
      value.hidden = false;
    }

    input.addEventListener('keydown', function (event) {
      if (event.key === 'Enter') {
        event.preventDefault();
        close(true);
      } else if (event.key === 'Escape') {
        event.preventDefault();
        close(false);
      }
    });

    input.addEventListener('blur', function () {
      close(true);
    });

    value.hidden = true;
    value.insertAdjacentElement('afterend', input);
    input.focus();
    input.select();
  }

  function stopHold() {
    if (!hold) {
      return;
    }

    if (hold.timer) {
      clearTimeout(hold.timer);
    }

    // A held button has already changed the number; the click that ends the
    // press must not add one more step on top of it.
    suppressClick = hold.repeats > 0;
    hold = null;
  }

  function startHold(link, direction) {
    var group = link.closest(QTY_GROUP_SELECTOR);

    if (!group || !readState(group)) {
      return;
    }

    hold = {repeats: 0, timer: null};

    var current = hold;

    function repeat() {
      if (hold !== current) {
        return;
      }

      if (!stepBy(group, direction)) {
        // Limit reached: nothing more to repeat.
        stopHold();
        suppressClick = true;

        return;
      }

      current.repeats += 1;
      current.timer = setTimeout(repeat, current.repeats < REPEATS_BEFORE_FAST ? REPEAT_DELAY : REPEAT_FAST);
    }

    current.timer = setTimeout(repeat, HOLD_DELAY);
  }

  /**
   * A freshly rendered line says nothing about the room it has left, so the
   * buttons are marked as soon as it appears — not on the first click. Both
   * previews are re-rendered from the server (the modal by this script, the
   * header panel by ps_shoppingcart.js), which is why this watches the document
   * rather than running once.
   */
  function initGroups(root) {
    toArray((root || document).querySelectorAll(QTY_GROUP_SELECTOR)).forEach(function (group) {
      var state = readState(group);

      if (state) {
        markLimits(group, state);
      }
    });
  }

  if (typeof MutationObserver === 'function') {
    var observer = new MutationObserver(function (records) {
      for (var i = 0; i < records.length; i += 1) {
        var added = records[i].addedNodes;

        for (var j = 0; j < added.length; j += 1) {
          var node = added[j];

          if (node.nodeType === 1 && (node.matches(QTY_GROUP_SELECTOR) || node.querySelector(QTY_GROUP_SELECTOR))) {
            initGroups(node);
          }
        }
      }
    });

    observer.observe(document.documentElement, {childList: true, subtree: true});
  }

  initGroups();

  document.addEventListener('pointerdown', function (event) {
    // Set when a held button ends its press, consumed by the click that
    // follows it. Any new press starts from a clean slate, so a press that
    // ended outside the button cannot swallow an unrelated click later on.
    suppressClick = false;

    if (!event.target || typeof event.target.closest !== 'function') {
      return;
    }

    var link = event.target.closest(ACTION_SELECTOR);

    if (!link) {
      return;
    }

    var op = link.getAttribute('data-ps-qty-op');

    if (op !== 'up' && op !== 'down') {
      return;
    }

    // Holding a button must not start a text selection or a link drag.
    event.preventDefault();
    startHold(link, op === 'up' ? 1 : -1);
  });

  ['pointerup', 'pointercancel', 'pointerleave'].forEach(function (name) {
    document.addEventListener(name, stopHold);
  });

  window.addEventListener('blur', stopHold);

  document.addEventListener('click', function (event) {
    var target = event.target;

    if (!target || typeof target.closest !== 'function') {
      return;
    }

    var value = target.closest(QTY_VALUE_SELECTOR);

    if (value) {
      var editable = value.closest(QTY_GROUP_SELECTOR);

      if (editable) {
        openEditor(editable);
      }

      return;
    }

    var link = target.closest(ACTION_SELECTOR);

    if (!link) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();

    if (suppressClick) {
      suppressClick = false;

      return;
    }

    var op = link.getAttribute('data-ps-qty-op');
    var group = link.closest(QTY_GROUP_SELECTOR);

    if ((op === 'up' || op === 'down') && group) {
      if (readState(group)) {
        stepBy(group, op === 'up' ? 1 : -1);

        return;
      }

      // A line the browser cannot count: the server settles it, and the dimmed
      // number says the answer is on its way.
      group.setAttribute('data-ps-state', 'pending');
      enqueue(link.getAttribute('href'), Object.assign({}, link.dataset));

      return;
    }

    // Removing a line: whatever else is waiting goes first, so the quantities
    // of the other lines are not lost, then the line leaves at once.
    var row = link.closest(ROW_SELECTOR);

    if (row) {
      row.setAttribute('data-ps-removing', '');
    }

    if (settleTimer) {
      clearTimeout(settleTimer);
      settleTimer = null;
    }

    settle();
    enqueue(link.getAttribute('href'), Object.assign({}, link.dataset), function () {
      if (row) {
        row.removeAttribute('data-ps-removing');
      }
    });
  });
})();
