/**
 * Search overlay — icon opens full-width search bar inside header,
 * everything below header dims.
 *
 * Includes client-side synonym suggestions (zero server load).
 */
(function () {
  'use strict';

  /* ── Synonym dictionary (Polish, tableware/porcelain domain) ── */
  var SYNONYMS = {
    'filiżanka':   ['kubek', 'czarka', 'czarek', 'espresso', 'cappuccino'],
    'filizanka':   ['kubek', 'czarka', 'espresso', 'cappuccino'],
    'kubek':       ['filiżanka', 'czarka', 'mug', 'porcelana'],
    'czarka':      ['filiżanka', 'kubek', 'miseczka'],
    'talerz':      ['półmisek', 'misa', 'salaterka', 'talerzyk'],
    'talerzyk':    ['talerz', 'deserowy', 'śniadaniowy'],
    'półmisek':    ['talerz', 'misa', 'patera', 'taca'],
    'misa':        ['miska', 'salaterka', 'waza', 'półmisek'],
    'miska':       ['misa', 'salaterka', 'miseczka'],
    'salaterka':   ['misa', 'miska', 'talerz', 'waza'],
    'miseczka':    ['czarka', 'salaterka', 'misa'],
    'waza':        ['misa', 'garnek', 'salaterka', 'zupa'],
    'dzbanek':     ['czajnik', 'imbryczek', 'karafka', 'mlecznik'],
    'czajnik':     ['dzbanek', 'imbryczek', 'herbata'],
    'imbryczek':   ['dzbanek', 'czajnik', 'herbata'],
    'karafka':     ['dzbanek', 'karafa', 'woda'],
    'mlecznik':    ['dzbanek', 'śmietannik', 'karafka'],
    'cukiernica':  ['cukierniczka', 'cukier', 'pojemnik'],
    'sosjerka':    ['sosjera', 'sos', 'dzbanek'],
    'patera':      ['talerz', 'półmisek', 'taca', 'dekoracja'],
    'taca':        ['patera', 'półmisek', 'serwowanie'],
    'porcelana':   ['ceramika', 'zastawa', 'serwis', 'naczynia'],
    'ceramika':    ['porcelana', 'zastawa', 'naczynia'],
    'zastawa':     ['serwis', 'komplet', 'porcelana', 'naczynia'],
    'serwis':      ['zastawa', 'komplet', 'zestaw', 'porcelana'],
    'komplet':     ['serwis', 'zastawa', 'zestaw'],
    'zestaw':      ['komplet', 'serwis', 'zastawa'],
    'sztućce':     ['widelec', 'nóż', 'łyżka', 'łyżeczka'],
    'łyżka':       ['łyżeczka', 'sztućce', 'chochla'],
    'łyżeczka':    ['łyżka', 'sztućce', 'herbata', 'dessert'],
    'widelec':     ['widelczyk', 'sztućce'],
    'nóż':         ['nożyk', 'sztućce'],
    'kieliszek':   ['szklanka', 'puchar', 'kryształ', 'wino'],
    'szklanka':    ['kubek', 'kieliszek', 'szklany'],
    'puchar':      ['kieliszek', 'trofeum', 'nagroda'],
    'herbata':     ['czajnik', 'dzbanek', 'filiżanka', 'imbryczek'],
    'kawa':        ['filiżanka', 'espresso', 'cappuccino', 'kubek'],
    'espresso':    ['filiżanka', 'kawa', 'czarka'],
    'cappuccino':  ['filiżanka', 'kawa', 'kubek'],
    'zupa':        ['waza', 'misa', 'talerz', 'czarka'],
    'dekoracja':   ['patera', 'waza', 'figurka', 'ozdoba'],
    'figurka':     ['dekoracja', 'ozdoba', 'porcelana'],
    'maria':       ['serwis', 'zastawa', 'klasyczny'],
    'sanssouci':   ['serwis', 'zastawa', 'złoty', 'klasyczny'],
    'biały':       ['porcelana', 'klasyczny', 'czysty'],
    'biala':       ['porcelana', 'klasyczny'],
    'złoty':       ['sanssouci', 'gold', 'dekoracja'],
    'gold':        ['złoty', 'sanssouci', 'dekoracja'],
  };

  /**
   * Returns synonyms for the last word (or full phrase) of a query string.
   */
  function getSuggestions(query) {
    if (!query) return [];
    var q = query.trim().toLowerCase();
    // try full phrase first, then last word
    var hit = SYNONYMS[q];
    if (!hit) {
      var words = q.split(/\s+/);
      var last  = words[words.length - 1];
      hit = SYNONYMS[last];
    }
    return hit || [];
  }

  /* ── Synonym UI ── */
  function initSynonymSuggestions(input, onHeightChange) {
    var suggestBar = document.createElement('div');
    suggestBar.className = 'ps-search-suggestions';
    suggestBar.setAttribute('aria-live', 'polite');
    // inject right after the overlay form
    var form = input.closest('form');
    if (form && form.parentNode) {
      form.parentNode.insertBefore(suggestBar, form.nextSibling);
    }

    function renderSuggestions(suggestions) {
      if (!suggestions.length) {
        suggestBar.innerHTML = '';
        suggestBar.style.display = 'none';
        onHeightChange && onHeightChange();
        return;
      }
      suggestBar.innerHTML =
        '<span class="ps-search-suggestions__label">Spróbuj też:</span>' +
        suggestions.map(function (s) {
          return '<button type="button" class="ps-search-suggestions__chip" data-term="' +
            s.replace(/"/g, '&quot;') + '">' + s + '</button>';
        }).join('');
      suggestBar.style.display = 'flex';
      onHeightChange && onHeightChange();

      suggestBar.querySelectorAll('.ps-search-suggestions__chip').forEach(function (btn) {
        btn.addEventListener('mousedown', function (e) {
          // prevent input blur before click fires
          e.preventDefault();
        });
        btn.addEventListener('click', function () {
          var parts = input.value.trim().split(/\s+/);
          parts[parts.length - 1] = btn.dataset.term;
          input.value = parts.join(' ');
          // trigger PS autocomplete
          input.dispatchEvent(new Event('input', { bubbles: true }));
          input.dispatchEvent(new KeyboardEvent('keydown', { bubbles: true }));
          input.focus();
          suggestBar.style.display = 'none';
        });
      });
    }

    input.addEventListener('input', function () {
      renderSuggestions(getSuggestions(input.value));
    });

    input.addEventListener('blur', function () {
      // small delay so chip click can still fire
      setTimeout(function () { suggestBar.style.display = 'none'; }, 200);
    });

    input.addEventListener('focus', function () {
      if (input.value.trim()) {
        renderSuggestions(getSuggestions(input.value));
      }
    });
  }

  /* ── Main overlay init ── */
  function initSearchOverlay() {
    var openBtn   = document.querySelector('.btn-search-open');
    var closeBtn  = document.querySelector('.btn-search-close');
    var overlay   = document.getElementById('ps-search-overlay');
    var dimmer    = document.getElementById('search-page-dimmer');
    var header    = document.getElementById('header');
    var input     = overlay ? overlay.querySelector('.js-search-input') : null;

    if (!openBtn || !overlay || !dimmer) return;

    // Move overlay to be a direct child of #header so position:absolute
    // covers the full header width regardless of inner flex containers
    if (header && overlay.parentElement !== header) {
      header.appendChild(overlay);
    }

    // Set CSS var so dropdown appears right below the overlay (which may
    // expand when suggestions row is visible)
    function updateDropdownTop() {
      var rect = overlay.getBoundingClientRect();
      var top  = overlay.classList.contains('is-open') ? rect.bottom : header.getBoundingClientRect().bottom;
      document.documentElement.style.setProperty('--search-dropdown-top', top + 'px');
    }

    updateDropdownTop();
    window.addEventListener('resize', updateDropdownTop);
    window.addEventListener('scroll', updateDropdownTop, { passive: true });

    // Init synonym suggestions (after updateDropdownTop is defined)
    if (input) {
      initSynonymSuggestions(input, updateDropdownTop);
    }

    function openSearch() {
      overlay.style.display = 'flex';
      overlay.classList.add('is-open');
      overlay.setAttribute('aria-hidden', 'false');
      dimmer.classList.add('is-visible');
      // trigger reflow for transition
      dimmer.offsetHeight; // eslint-disable-line no-unused-expressions
      dimmer.classList.add('is-active');
      openBtn.setAttribute('aria-expanded', 'true');
      if (input) {
        input.focus();
      }
    }

    function closeSearch() {
      dimmer.classList.remove('is-active');
      openBtn.setAttribute('aria-expanded', 'false');
      // hide after transition
      setTimeout(function () {
        overlay.style.display = 'none';
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        dimmer.classList.remove('is-visible');
      }, 260);
    }

    openBtn.addEventListener('click', openSearch);

    if (closeBtn) {
      closeBtn.addEventListener('click', closeSearch);
    }

    // Click on dimmer closes search
    dimmer.addEventListener('click', closeSearch);

    // Escape key closes
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && overlay.classList.contains('is-open')) {
        closeSearch();
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSearchOverlay);
  } else {
    initSearchOverlay();
  }
})();
