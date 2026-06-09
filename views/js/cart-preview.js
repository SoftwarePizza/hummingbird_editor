/**
 * Cart preview interactions (quantity +/- and remove) for the hover panel and
 * the add-to-cart modal replacement.
 *
 * Managed by hummingbird_editor so the ps_shoppingcart core module stays
 * untouched. Vanilla JS (no jQuery). The actual cart change is performed by
 * POSTing to the core cart controller URL carried by the link's href; the
 * existing ps_shoppingcart.js refresh handler then re-renders the .blockcart
 * preview (including the free-shipping bar and totals) when the 'updateCart'
 * event is emitted on the PrestaShop event bus.
 */
(function () {
  'use strict';

  var ACTION_SELECTOR = '[data-ps-action="cart-preview-update"]';

  function getPrestashop() {
    return window.prestashop || null;
  }

  function sendUpdate(link) {
    var prestashop = getPrestashop();
    var url = link.getAttribute('href');

    if (!url || !prestashop || typeof prestashop.emit !== 'function') {
      return;
    }

    if (link.dataset.psBusy === '1') {
      return;
    }
    link.dataset.psBusy = '1';
    link.setAttribute('aria-disabled', 'true');

    var formData = new FormData();
    formData.append('ajax', '1');
    formData.append('action', 'update');

    fetch(url, {method: 'POST', body: formData})
      .then(function (resp) {
        prestashop.emit('updateCart', {
          reason: link.dataset,
          resp: resp,
        });
      })
      .catch(function (err) {
        prestashop.emit('handleError', {
          eventType: 'updateProductInCart',
          resp: err,
        });
      })
      .finally(function () {
        delete link.dataset.psBusy;
        link.removeAttribute('aria-disabled');
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
    sendUpdate(link);
  });
})();
