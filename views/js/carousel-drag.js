/**
 * Hummingbird Editor — drag-to-scroll + arrow nav for product carousels.
 * Targets: .ps-bestsellers .module-products__carousel .products
 */
(function () {
    'use strict';

    function initCarousel(section) {
        var track = section.querySelector('.module-products__carousel .products');
        if (!track) {
            return;
        }

        var nav = section.querySelector('[data-hbe-carousel-nav]');
        var btnPrev = nav ? nav.querySelector('[data-hbe-carousel-prev]') : null;
        var btnNext = nav ? nav.querySelector('[data-hbe-carousel-next]') : null;

        // Drag-to-scroll
        var isDown = false;
        var startX = 0;
        var startScroll = 0;
        var moved = false;

        track.addEventListener('mousedown', function (e) {
            isDown = true;
            moved = false;
            startX = e.pageX - track.offsetLeft;
            startScroll = track.scrollLeft;
            track.classList.add('is-dragging');
        });

        window.addEventListener('mouseup', function () {
            if (!isDown) { return; }
            isDown = false;
            track.classList.remove('is-dragging');
        });

        track.addEventListener('mouseleave', function () {
            if (!isDown) { return; }
            isDown = false;
            track.classList.remove('is-dragging');
        });

        track.addEventListener('mousemove', function (e) {
            if (!isDown) { return; }
            e.preventDefault();
            var x = e.pageX - track.offsetLeft;
            var walk = x - startX;
            if (Math.abs(walk) > 4) { moved = true; }
            track.scrollLeft = startScroll - walk;
        });

        // Suppress click on miniature anchors when dragging
        track.addEventListener('click', function (e) {
            if (moved) {
                e.preventDefault();
                e.stopPropagation();
                moved = false;
            }
        }, true);

        // Step = first miniature width + gap
        function step() {
            var item = track.querySelector('.product-miniature');
            if (!item) { return Math.round(track.clientWidth * 0.8); }
            var style = window.getComputedStyle(track);
            var gap = parseFloat(style.columnGap || style.gap || '0') || 0;
            return item.getBoundingClientRect().width + gap;
        }

        function updateNavState() {
            if (!btnPrev || !btnNext) { return; }
            var maxScroll = track.scrollWidth - track.clientWidth - 1;
            btnPrev.disabled = track.scrollLeft <= 0;
            btnNext.disabled = track.scrollLeft >= maxScroll;
        }

        if (btnPrev) {
            btnPrev.addEventListener('click', function () {
                track.scrollBy({ left: -step(), behavior: 'smooth' });
            });
        }
        if (btnNext) {
            btnNext.addEventListener('click', function () {
                track.scrollBy({ left: step(), behavior: 'smooth' });
            });
        }

        track.addEventListener('scroll', updateNavState, { passive: true });
        window.addEventListener('resize', updateNavState);
        updateNavState();
    }

    function initAll() {
        document.querySelectorAll('.ps-bestsellers, .ps-newproducts, .ps-featuredproducts').forEach(initCarousel);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
