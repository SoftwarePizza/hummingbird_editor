/* hbe-carousel-lazy: doladowywanie karuzel produktowych strony glownej.

   Strona przychodzi z atrapami (products-lazy.tpl); tutaj podmieniamy je na
   prawdziwa tresc, gdy zbliza sie do ekranu. Id sekcji, ktore weszly w pole
   widzenia mniej wiecej naraz, ida jednym zapytaniem — kazde zadanie to pelny
   start PrestaShopa, wiec liczba zapytan kosztuje wiecej niz ich rozmiar. */
(function () {
  'use strict';

  var cfg = window.hbeCarouselLazy || {};
  if (!cfg.url) {
    return;
  }

  /* Ile karuzel maksymalnie w jednym zapytaniu (endpoint tnie na 20). */
  var BATCH = 6;
  /* Zapas nad krawedzia ekranu — tresc ma byc gotowa, zanim dojedzie sie wzrokiem. */
  var MARGIN = '800px 0px';
  /* Okno na zebranie sekcji, ktore weszly w pole widzenia razem. */
  var DEBOUNCE = 60;

  var pending = [];
  var timer = null;

  function schedule(section) {
    if (section.getAttribute('data-hbe-queued')) {
      return;
    }
    section.setAttribute('data-hbe-queued', '1');
    pending.push(section);

    if (timer) {
      clearTimeout(timer);
    }
    timer = setTimeout(flush, DEBOUNCE);
  }

  function flush() {
    timer = null;
    if (!pending.length) {
      return;
    }

    var batch = pending.splice(0, BATCH);
    if (pending.length) {
      timer = setTimeout(flush, DEBOUNCE);
    }
    request(batch);
  }

  function request(batch) {
    var ids = batch.map(function (section) {
      return section.getAttribute('data-hbe-carousel');
    });
    var url = cfg.url + (cfg.url.indexOf('?') === -1 ? '?' : '&') +
      'ids=' + encodeURIComponent(ids.join(','));

    fetch(url, {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('HTTP ' + response.status);
        }
        return response.json();
      })
      .then(function (data) {
        var blocks = (data && data.blocks) || {};
        batch.forEach(function (section) {
          swap(section, blocks[section.getAttribute('data-hbe-carousel')]);
        });
      })
      .catch(function () {
        /* Atrapa niesie naglowek i link do kategorii, wiec przy nieudanym
           doladowaniu zostawiamy ja na miejscu — sekcja jest uboga, ale zywa. */
        batch.forEach(function (section) {
          section.removeAttribute('aria-busy');
          section.removeAttribute('data-hbe-carousel');
          section.classList.add('hbe-products--failed');
        });
      });
  }

  function swap(section, html) {
    /* Pusty wynik = kategoria bez produktow. Atrapa obiecywala karuzele,
       ktorej nie ma, wiec znika razem z nia. */
    if (typeof html !== 'string' || html.trim() === '') {
      section.parentNode.removeChild(section);
      return;
    }

    var holder = document.createElement('div');
    holder.innerHTML = html;
    var fresh = holder.firstElementChild;
    if (!fresh) {
      section.parentNode.removeChild(section);
      return;
    }

    section.parentNode.replaceChild(fresh, section);

    /* Przeciaganie i strzalki wiaza sie na konkretnej sekcji, wiec swiezy
       kawalek DOM trzeba zainicjowac recznie (reszta motywu slucha na
       document, wiec dziala bez pomocy). */
    if (typeof window.hbeInitCarousel === 'function') {
      window.hbeInitCarousel(fresh);
    }
  }

  function start() {
    var sections = Array.prototype.slice.call(
      document.querySelectorAll('[data-hbe-carousel]')
    );
    if (!sections.length) {
      return;
    }

    if (!('IntersectionObserver' in window)) {
      sections.forEach(schedule);
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          observer.unobserve(entry.target);
          schedule(entry.target);
        }
      });
    }, { rootMargin: MARGIN });

    sections.forEach(function (section) {
      observer.observe(section);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
