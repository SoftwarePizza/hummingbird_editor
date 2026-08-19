/**
 * Hummingbird Editor — inner zoom on the product cover.
 *
 * Po najechaniu na aktywne zdjecie karuzeli w tej samej ramce renderuje sie
 * powiekszenie: warstwa z tlem ustawionym na najwiekszy dostepny typ obrazka
 * (domyslnie product_main_2x, 1440 px), przesuwana kursorem. Nic nie wychodzi
 * poza obrys zdjecia, wiec zoom nie koliduje z ukladem karty produktu.
 *
 * Zalozenia:
 *  - zero zaleznosci (motyw nie laduje jQuery),
 *  - duzy plik ciagnie sie dopiero przy pierwszym najechaniu, nie przy wejsciu
 *    na strone — karta produktu nie placi za funkcje, ktorej klient nie uzyl,
 *  - obsluga przez delegacje na document: podmiana DOM po zmianie wariantu
 *    (updatedProduct) nie wymaga zadnego re-init,
 *  - tylko urzadzenia z prawdziwym kursorem; na dotyku zostaje modal galerii.
 */
(function () {
    'use strict';

    var cfg = window.hbeZoom || {};
    var imageType = cfg.type || '';          // np. 'product_main_2x'
    var level = parseFloat(cfg.level || 0);  // 0 = naturalna rozdzielczosc zrodla

    /* Dotyk i rysiki pomijamy: tam nie ma najechania, a jest juz modal. */
    if (!window.matchMedia || !window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
        return;
    }

    var LAYER_CLASS = 'hbe-zoom-layer';

    /**
     * Zamienia typ obrazka w URL-u miniatury na `imageType`.
     * Obsluguje oba warianty PrestaShop:
     *   friendly:     /55036-product_main/nazwa.jpg
     *   bez friendly: /img/p/5/5/0/3/6/55036-product_main.jpg
     * Zwraca null, gdy URL nie wyglada na miniature produktu.
     */
    function bigImageUrl(src) {
        if (!src || !imageType) { return null; }

        // /<id>[-<id_produktu>]-<typ>/nazwa.ext
        var friendly = src.replace(
            /(\/\d+(?:-\d+)?)-[A-Za-z0-9_]+(\/[^/?#]+)(\?[^#]*)?$/,
            '$1-' + imageType + '$2$3'
        );
        if (friendly !== src) { return friendly; }

        // /<id>[-<id_produktu>]-<typ>.ext
        var plain = src.replace(
            /(\/\d+(?:-\d+)?)-[A-Za-z0-9_]+(\.[A-Za-z0-9]+)(\?[^#]*)?$/,
            '$1-' + imageType + '$2$3'
        );
        return plain !== src ? plain : null;
    }

    /** Warstwa powiekszenia dla danego slajdu — tworzona raz, potem odswiezana. */
    function getLayer(item) {
        var layer = item.querySelector('.' + LAYER_CLASS);
        if (!layer) {
            layer = document.createElement('div');
            layer.className = LAYER_CLASS;
            layer.setAttribute('aria-hidden', 'true');
            item.appendChild(layer);
        }
        return layer;
    }

    /**
     * Podpina duzy obrazek pod warstwe. Jesli powiekszony wariant nie istnieje
     * (nie wygenerowano miniatur tego typu), schodzimy do zrodla z `src` —
     * zoom jest wtedy slabszy, ale dziala.
     */
    function loadInto(layer, img) {
        var base = img.currentSrc || img.src;
        var target = bigImageUrl(base) || base;
        if (layer.dataset.hbeSrc === target) { return; }

        layer.dataset.hbeSrc = target;
        layer.classList.remove('is-ready');

        var pre = new Image();
        pre.onload = function () {
            if (layer.dataset.hbeSrc !== target) { return; } // w miedzyczasie zmieniono slajd
            layer.style.backgroundImage = 'url("' + target.replace(/"/g, '\\"') + '")';
            layer.dataset.hbeW = pre.naturalWidth;
            layer.dataset.hbeH = pre.naturalHeight;
            layer.classList.add('is-ready');
        };
        pre.onerror = function () {
            if (layer.dataset.hbeSrc !== target || target === base) { return; }
            layer.dataset.hbeSrc = base;
            var fb = new Image();
            fb.onload = function () {
                if (layer.dataset.hbeSrc !== base) { return; }
                layer.style.backgroundImage = 'url("' + base.replace(/"/g, '\\"') + '")';
                layer.dataset.hbeW = fb.naturalWidth;
                layer.dataset.hbeH = fb.naturalHeight;
                layer.classList.add('is-ready');
            };
            fb.src = base;
        };
        pre.src = target;
    }

    /**
     * Rozmiar tla. Przy `level = 0` bierzemy naturalna rozdzielczosc pliku —
     * piksel w piksel, czyli najostrzejszy mozliwy obraz bez interpolacji.
     * Przy 2/2.5/3 skalujemy wzgledem szerokosci ramki.
     */
    function applySize(layer, item) {
        var w = parseInt(layer.dataset.hbeW, 10);
        var h = parseInt(layer.dataset.hbeH, 10);
        if (!w || !h) { return; }

        if (level > 0) {
            var boxW = item.offsetWidth * level;
            layer.style.backgroundSize = boxW + 'px ' + (boxW * h / w) + 'px';
        } else {
            layer.style.backgroundSize = w + 'px ' + h + 'px';
        }
    }

    function activeItem(el) {
        var item = el.closest ? el.closest('.carousel-item') : null;
        if (!item || !item.classList.contains('active')) { return null; }
        return item.closest('.js-images-container') ? item : null;
    }

    var current = null;

    function move(e) {
        if (!current) { return; }
        var rect = current.item.getBoundingClientRect();
        if (!rect.width || !rect.height) { return; }

        /* Procentowe background-position samo mapuje 0% na lewa krawedz obrazu,
           a 100% na prawa — dokladnie to, czego chcemy przy tle wiekszym od ramki. */
        var x = ((e.clientX - rect.left) / rect.width) * 100;
        var y = ((e.clientY - rect.top) / rect.height) * 100;
        x = x < 0 ? 0 : (x > 100 ? 100 : x);
        y = y < 0 ? 0 : (y > 100 ? 100 : y);

        current.layer.style.backgroundPosition = x + '% ' + y + '%';
    }

    function leave() {
        if (!current) { return; }
        current.item.classList.remove('hbe-zoom-on');
        current.item.removeEventListener('mousemove', move);
        current.item.removeEventListener('mouseleave', leave);
        current = null;
    }

    document.addEventListener('mouseover', function (e) {
        var target = e.target;
        if (!target || !target.closest) { return; }

        var item = activeItem(target);
        if (!item) {
            /* Kursor zjechal ze zdjecia na cokolwiek innego. */
            if (current && !current.item.contains(target)) { leave(); }
            return;
        }
        if (current && current.item === item) { return; }
        leave();

        var img = item.querySelector('img');
        if (!img) { return; }

        var layer = getLayer(item);
        loadInto(layer, img);
        applySize(layer, item);

        current = { item: item, layer: layer };
        item.addEventListener('mousemove', move);
        item.addEventListener('mouseleave', leave);
        item.classList.add('hbe-zoom-on');

        /* Rozmiar tla zalezy od naturalnych wymiarow pliku, ktore przy pierwszym
           najechaniu moga jeszcze sie sciagac — przeliczamy po zaladowaniu. */
        if (!layer.classList.contains('is-ready')) {
            var poll = setInterval(function () {
                if (!current || current.layer !== layer) { clearInterval(poll); return; }
                if (layer.classList.contains('is-ready')) {
                    applySize(layer, current.item);
                    clearInterval(poll);
                }
            }, 60);
            setTimeout(function () { clearInterval(poll); }, 8000);
        }
    });

    /* Zmiana slajdu spod myszy (strzalki, miniatury) — zwolnij stary slajd,
       zeby kolejne najechanie zbudowalo warstwe dla nowego zdjecia. */
    document.addEventListener('slide.bs.carousel', leave, true);

    window.addEventListener('resize', function () {
        if (current) { applySize(current.layer, current.item); }
    });
}());
