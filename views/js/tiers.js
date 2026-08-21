/**
 * Progi rabatowe — odświeżenie paska na karcie produktu po dodaniu do koszyka.
 *
 * Koszyk i jego podgląd renderuje serwer przy każdej zmianie (fragmenty ajax
 * rdzenia, cartpreview.php), więc tam nic nie trzeba robić. Karta produktu się
 * nie przeładowuje — po zdarzeniu koszyka pobieramy świeży HTML paska z
 * kontrolera tiers.php i podmieniamy blok w miejscu. Bez odpowiedzi blok
 * zostaje stary, nigdy pusty.
 */
(function () {
  'use strict';

  function init() {
    var ps = window.prestashop;
    var cfg = window.hbeTiers || {};
    if (!ps || typeof ps.on !== 'function' || !cfg.url) {
      return;
    }
    if (!document.querySelector('[data-hbe-tiers="product"]')) {
      return;
    }

    var timer = null;
    var url = cfg.url + (cfg.url.indexOf('?') > -1 ? '&' : '?') + 'ctx=product';

    function swap(html) {
      var blocks = document.querySelectorAll('[data-hbe-tiers="product"]');
      if (!blocks.length) {
        return;
      }
      var wrap = document.createElement('div');
      wrap.innerHTML = String(html || '').trim();
      var fresh = wrap.firstElementChild;
      if (!fresh) {
        return;
      }
      blocks.forEach(function (el, i) {
        el.replaceWith(i === 0 ? fresh : fresh.cloneNode(true));
      });
    }

    function refresh() {
      clearTimeout(timer);
      timer = setTimeout(function () {
        fetch(url, {
          credentials: 'same-origin',
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
          .then(function (r) { return r.ok ? r.json() : null; })
          .then(function (data) {
            if (data && typeof data.html === 'string') {
              swap(data.html);
            }
          })
          .catch(function () {});
      }, 200);
    }

    ps.on('updateCart', refresh);
    ps.on('updatedCart', refresh);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
