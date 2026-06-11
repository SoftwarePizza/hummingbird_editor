/* hbe-related: one-card-at-a-time carousel, items fetched lazily when the
   section scrolls near the viewport. No jQuery. */
(function () {
  'use strict';

  function init(section) {
    var track = section.querySelector('[data-hbe-related-track]');
    var prev = section.querySelector('[data-hbe-related-prev]');
    var next = section.querySelector('[data-hbe-related-next]');
    var url = section.getAttribute('data-hbe-related-url');
    if (!track || !prev || !next || !url) {
      return;
    }

    var index = 0;
    var count = 0;

    function update() {
      track.style.transform = 'translateX(' + (-index * 100) + '%)';
      prev.disabled = index <= 0;
      next.disabled = index >= count - 1;
    }

    function load() {
      fetch(url, { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (!data || !data.count) {
            section.remove();
            return;
          }
          track.innerHTML = data.html;
          count = data.count;
          update();
        })
        .catch(function () {
          section.remove();
        });
    }

    prev.addEventListener('click', function () {
      if (index > 0) {
        index -= 1;
        update();
      }
    });
    next.addEventListener('click', function () {
      if (index < count - 1) {
        index += 1;
        update();
      }
    });

    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            io.disconnect();
            load();
          }
        });
      }, { rootMargin: '300px' });
      io.observe(section);
    } else {
      load();
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-hbe-related]').forEach(init);
  });
})();
