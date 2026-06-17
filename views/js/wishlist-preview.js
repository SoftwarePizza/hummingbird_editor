/**
 * Wishlist preview drawer (Figma: Ulubione).
 *
 * Managed by hummingbird_editor so the core blockwishlist module stays
 * untouched. Vanilla JS (no jQuery). Data comes from blockwishlist's own
 * AJAX API: action=getAllWishlist for the lists, then the default list's
 * listUrl + '&from-xhr' for its products (same calls the module's Vue app
 * makes). The drawer opens from the header heart icon and automatically
 * after a product is added to a wishlist (blockwishlist's global
 * WishlistEventBus 'addedToWishlist' event).
 *
 * Config: window.hbeWishlistPreview = {getAllWishlistUrl, loginUrl, i18n:{...}}
 */
(function () {
  'use strict';

  var SELECTORS = {
    root: '[data-ps-component="wishlist-preview"]',
    panel: '[data-ps-ref="wishlist-preview-panel"]',
    list: '[data-ps-target="wishlist-preview-list"]',
    cta: '[data-ps-ref="wishlist-preview-cta"]',
    open: '[data-ps-action="wishlist-preview-open"]',
    close: '[data-ps-action="wishlist-preview-close"]',
  };

  var HEART_SELECTOR = '.wishlist-button-add';
  var LAST_LIST_KEY = 'hbeWishlistLastList';

  var config = window.hbeWishlistPreview || null;
  var root = null;
  var lastFocused = null;
  var lists = null; // cached customer wishlists (for the one-click add decision)

  function init() {
    root = document.querySelector(SELECTORS.root);
    if (!root || !config) {
      return;
    }

    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onKeydown);
    // Capture phase so we run before blockwishlist's own button handler and can
    // suppress its "choose a list" modal when we add directly.
    document.addEventListener('click', onHeartCapture, true);
    bindWishlistBus();

    if (isLogged()) {
      ensureListsLoaded();
    }
  }

  function onDocumentClick(event) {
    var target = event.target;
    if (!target || typeof target.closest !== 'function') {
      return;
    }

    var opener = target.closest(SELECTORS.open);
    if (opener) {
      // keep new-tab / middle-click behaviour of the original link
      if (event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) {
        return;
      }
      event.preventDefault();
      open();
      return;
    }

    if (target.closest(SELECTORS.close)) {
      event.preventDefault();
      close();
    }
  }

  function onKeydown(event) {
    if ((event.key === 'Escape' || event.keyCode === 27) && !root.hidden) {
      close();
    }
  }

  /** blockwishlist exposes its Vue event bus globally; it fires
   * 'addedToWishlist' after a product lands on a list. */
  function bindWishlistBus() {
    if (window.WishlistEventBus && typeof window.WishlistEventBus.$on === 'function') {
      window.WishlistEventBus.$on('addedToWishlist', function (event) {
        // Remember the chosen list so the next add (even with several lists) is one-click.
        if (event && event.detail && event.detail.listId) {
          rememberList(event.detail.listId);
        }
        open();
      });
      return;
    }
    if (window.prestashop && typeof window.prestashop.on === 'function') {
      window.prestashop.on('wishlistEventBusInit', bindWishlistBus);
    }
  }

  /* ── One-click add: skip blockwishlist's "choose a list" modal ──────────── */

  function isLogged() {
    return !!(window.prestashop && window.prestashop.customer && window.prestashop.customer.is_logged);
  }

  function getBus() {
    return (window.WishlistEventBus && typeof window.WishlistEventBus.$emit === 'function')
      ? window.WishlistEventBus
      : null;
  }

  function rememberList(listId) {
    try { window.localStorage.setItem(LAST_LIST_KEY, String(listId)); } catch (e) { /* ignore */ }
  }

  function rememberedList() {
    try { return parseInt(window.localStorage.getItem(LAST_LIST_KEY), 10) || 0; } catch (e) { return 0; }
  }

  function ensureListsLoaded() {
    return fetch(config.getAllWishlistUrl, {headers: jsonHeaders()})
      .then(function (resp) { return resp.json(); })
      .then(function (data) { lists = (data && data.wishlists) || []; return lists; })
      .catch(function () { lists = lists || []; return lists; });
  }

  /** Which list a one-click add should target, or 0 to fall back to the modal. */
  function pickTargetList() {
    if (!lists || !lists.length) {
      return 0;
    }
    if (lists.length === 1) {
      return parseInt(lists[0].id_wishlist, 10);
    }
    var remembered = rememberedList();
    var exists = lists.some(function (l) { return parseInt(l.id_wishlist, 10) === remembered; });
    return exists ? remembered : 0; // several lists, nothing remembered → let the modal ask
  }

  function onHeartCapture(event) {
    var target = event.target;
    if (!target || typeof target.closest !== 'function') {
      return;
    }
    var btn = target.closest(HEART_SELECTOR);
    if (!btn) {
      return;
    }

    // Only handle ADD (empty heart). A filled heart removes — blockwishlist does
    // that directly (no modal), so we leave it alone.
    var icon = btn.querySelector('i.material-icons');
    if (icon && icon.textContent.trim() === 'favorite') {
      return;
    }
    if (!isLogged()) {
      return; // blockwishlist shows its login prompt
    }

    // Product identity is only reliably available on listing miniatures; on the
    // product page we leave blockwishlist's default flow untouched.
    var mini = btn.closest('.js-product-miniature');
    if (!mini) {
      return;
    }
    var productId = parseInt(mini.dataset.idProduct, 10);
    if (!productId) {
      return;
    }
    var attrId = parseInt(mini.dataset.idProductAttribute, 10) || 0;

    var listId = pickTargetList();
    if (!listId) {
      return; // unknown target (still loading, or several lists) → native modal
    }

    // Block blockwishlist's own click handler (and its modal) and add directly.
    event.preventDefault();
    event.stopImmediatePropagation();
    directAdd(productId, attrId, listId);
  }

  function directAdd(productId, attrId, listId) {
    var url = config.addUrl
      + '&params[id_product]=' + productId
      + '&params[idWishList]=' + listId
      + '&params[quantity]=0'
      + '&params[id_product_attribute]=' + attrId;

    fetch(url, {method: 'POST', headers: jsonHeaders()})
      .then(function (resp) { return resp.json(); })
      .then(function (data) {
        var bus = getBus();
        if (data && data.success) {
          rememberList(listId);
          if (bus) {
            // fills the heart (Button listens) + opens our drawer (we listen)
            bus.$emit('addedToWishlist', {
              detail: {productId: productId, productAttributeId: attrId, listId: listId},
            });
            bus.$emit('showToast', {detail: {type: 'success', message: config.i18n.added}});
          } else {
            open();
          }
        } else if (bus) {
          bus.$emit('showToast', {
            detail: {type: 'error', message: (data && data.message) || config.i18n.error},
          });
        }
      })
      .catch(function () {
        var bus = getBus();
        if (bus) {
          bus.$emit('showToast', {detail: {type: 'error', message: config.i18n.error}});
        }
      });
  }

  function open() {
    lastFocused = document.activeElement;
    root.hidden = false;
    document.body.style.overflow = 'hidden';
    var panel = root.querySelector(SELECTORS.panel);
    if (panel) {
      panel.focus();
    }
    load();
  }

  function close() {
    root.hidden = true;
    document.body.style.overflow = '';
    if (lastFocused && typeof lastFocused.focus === 'function') {
      lastFocused.focus();
    }
  }

  function setBody(html) {
    root.querySelector(SELECTORS.list).innerHTML = html;
  }

  function message(html) {
    setBody('<p class="hbe-wishlist-preview__message">' + html + '</p>');
  }

  function load() {
    message(escapeHtml(config.i18n.loading));

    fetch(config.getAllWishlistUrl, {headers: jsonHeaders()})
      .then(function (resp) { return resp.json(); })
      .then(function (data) {
        if (data && data.wishlists) {
          lists = data.wishlists; // keep the one-click-add cache fresh
        }
        if (!data || !data.wishlists || !data.wishlists.length) {
          if (data && data.success === false) {
            message(
              escapeHtml(config.i18n.login)
              + ' <a href="' + escapeHtml(config.loginUrl) + '">' + escapeHtml(config.i18n.loginLink) + '</a>'
            );
            return;
          }
          message(escapeHtml(config.i18n.empty));
          return;
        }

        // first list is the default one (API orders by `default` DESC)
        var list = data.wishlists[0];
        var cta = root.querySelector(SELECTORS.cta);
        if (cta && list.listUrl) {
          cta.href = list.listUrl;
          cta.hidden = false;
        }
        return loadProducts(list);
      })
      .catch(function () {
        message(escapeHtml(config.i18n.error));
      });
  }

  function loadProducts(list) {
    // same valueless '&from-xhr' suffix blockwishlist's own resolvers use
    return fetch(list.listUrl + '&from-xhr', {headers: jsonHeaders()})
      .then(function (resp) { return resp.json(); })
      .then(function (data) {
        var products = (data && data.products) || [];
        if (!products.length) {
          message(escapeHtml(config.i18n.empty));
          return;
        }
        setBody(products.map(renderRow).join(''));
      });
  }

  function renderRow(product) {
    var image = coverUrl(product);
    return '<a class="hbe-wishlist-preview__item" href="' + escapeHtml(productUrl(product)) + '">'
      + (image
        ? '<img class="hbe-wishlist-preview__thumb" src="' + escapeHtml(image) + '" alt="" loading="lazy" width="96" height="96">'
        : '')
      + '<span class="hbe-wishlist-preview__info">'
      + '<span class="hbe-wishlist-preview__name">' + escapeHtml(product.name || '') + '</span>'
      + '<span class="hbe-wishlist-preview__price">' + escapeHtml(product.price || '')
      + ' <span class="hbe-wishlist-preview__tax">' + escapeHtml(config.i18n.tax) + '</span></span>'
      + '</span>'
      + '</a>';
  }

  function coverUrl(product) {
    var bySize = product.cover && product.cover.bySize;
    if (bySize) {
      var size = bySize.cart_default || bySize.small_default || bySize.home_default;
      if (size && size.url) {
        return size.url;
      }
    }
    return (product.cover && product.cover.small && product.cover.small.url) || '';
  }

  function productUrl(product) {
    return product.url || product.canonical_url || '#';
  }

  function jsonHeaders() {
    return {
      Accept: 'application/json, text/javascript, */*; q=0.01',
      'X-Requested-With': 'XMLHttpRequest',
    };
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
