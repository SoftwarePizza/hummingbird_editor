/**
 * Hummingbird Editor — zoom zdjecia produktu.
 *
 * Dwa tryby, kazdy dla swojego rodzaju urzadzenia:
 *
 *  1. Kursor — powiekszenie w ramce okladki. Warstwa z tlem ustawionym na
 *     oryginal zdjecia przesuwa sie za kursorem; nic nie wychodzi poza obrys,
 *     wiec uklad karty stoi.
 *  2. Dotyk — pinch dwoma palcami w podgladzie pelnoekranowym (ikona lupy).
 *     Przy pierwszym powiekszeniu zdjecie podmienia sie na oryginal, zeby
 *     bylo na co patrzec po rozsunieciu palcow.
 *
 * Zalozenia wspolne:
 *  - zero zaleznosci (motyw nie laduje jQuery),
 *  - duzy plik ciagnie sie dopiero, gdy klient siegnie po zoom,
 *  - `originals` z PHP zawiera tylko zdjecia mieszczace sie w limicie wagi;
 *    gdy zdjecia tam nie ma, schodzimy do najwiekszej miniatury.
 */
(function () {
    'use strict';

    var cfg = window.hbeZoom || {};
    var imageType = cfg.type || '';          // np. 'product_main_2x'
    var level = parseFloat(cfg.level || 0);  // 0 = naturalna rozdzielczosc zrodla
    var originals = cfg.originals || {};     // id_image -> adres oryginalu (o ile lekki)

    /**
     * Numer zdjecia z adresu miniatury — ostatnia liczba przed nazwa typu,
     * bo PrestaShop generuje albo `{id_image}-{typ}`, albo `{id_produktu}-{id_image}-{typ}`.
     */
    function imageId(src) {
        var m = src.match(/\/(?:\d+-)?(\d+)-[A-Za-z0-9_]+(?:\/[^/?#]+|\.[A-Za-z0-9]+)(?:\?[^#]*)?$/);
        return m ? m[1] : null;
    }

    /**
     * Zrodlo powiekszenia. Najpierw oryginal zdjecia — przy zdjeciach pionowych
     * to jedyne zrodlo naprawde szersze od ramki, bo miniatury PrestaShopa
     * skaluja sie do zmieszczenia w kwadracie. Potem podmiana typu na `imageType`:
     *   friendly:     /55036-product_main/nazwa.jpg
     *   bez friendly: /img/p/5/5/0/3/6/55036-product_main.jpg
     * Zwraca null, gdy adres nie wyglada na miniature produktu.
     */
    function bigImageUrl(src) {
        if (!src) { return null; }

        var id = imageId(src);
        if (id && originals[id]) { return originals[id]; }

        if (!imageType) { return null; }

        var friendly = src.replace(
            /(\/\d+(?:-\d+)?)-[A-Za-z0-9_]+(\/[^/?#]+)(\?[^#]*)?$/,
            '$1-' + imageType + '$2$3'
        );
        if (friendly !== src) { return friendly; }

        var plain = src.replace(
            /(\/\d+(?:-\d+)?)-[A-Za-z0-9_]+(\.[A-Za-z0-9]+)(\?[^#]*)?$/,
            '$1-' + imageType + '$2$3'
        );
        return plain !== src ? plain : null;
    }

    /* ══════════════════════════════════════════════════════════════════════
       Tryb kursora — powiekszenie w ramce okladki
    ══════════════════════════════════════════════════════════════════════ */
    function initHoverZoom() {
        var LAYER_CLASS = 'hbe-zoom-layer';
        var current = null;

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
         * Podpina duze zdjecie pod warstwe. Gdy powiekszonego wariantu nie ma,
         * schodzimy do zrodla z `src` — zoom jest slabszy, ale dziala.
         */
        function loadInto(layer, img) {
            var base = img.currentSrc || img.src;
            var target = bigImageUrl(base) || base;
            if (layer.dataset.hbeSrc === target) { return; }

            layer.dataset.hbeSrc = target;
            layer.classList.remove('is-ready');

            function show(url, node) {
                if (layer.dataset.hbeSrc !== url) { return; } // w miedzyczasie zmieniono slajd
                layer.style.backgroundImage = 'url("' + url.replace(/"/g, '\\"') + '")';
                layer.dataset.hbeW = node.naturalWidth;
                layer.dataset.hbeH = node.naturalHeight;
                layer.classList.add('is-ready');
                if (current && current.layer === layer) { applySize(layer, current.car); }
            }

            var pre = new Image();
            pre.onload = function () { show(target, pre); };
            pre.onerror = function () {
                if (layer.dataset.hbeSrc !== target || target === base) { return; }
                layer.dataset.hbeSrc = base;
                var fb = new Image();
                fb.onload = function () { show(base, fb); };
                fb.src = base;
            };
            pre.src = target;
        }

        /**
         * Rozmiar tla. Przy `level = 0` bierzemy naturalna rozdzielczosc pliku —
         * piksel w piksel, czyli najostrzejszy mozliwy obraz bez interpolacji.
         * Przy 2/2,5/3 skalujemy wzgledem szerokosci ramki.
         */
        function applySize(layer, box) {
            var w = parseInt(layer.dataset.hbeW, 10);
            var h = parseInt(layer.dataset.hbeH, 10);
            if (!w || !h) { return; }

            if (level > 0) {
                var boxW = box.offsetWidth * level;
                layer.style.backgroundSize = boxW + 'px ' + (boxW * h / w) + 'px';
            } else {
                layer.style.backgroundSize = w + 'px ' + h + 'px';
            }
        }

        /**
         * Zoom trzyma sie calej karuzeli, nie pojedynczego slajdu: strzalki
         * i przycisk galerii leza w tym samym kontenerze, wiec przejazd kursora
         * nad nimi nie gasi powiekszenia.
         */
        function carouselOf(el) {
            if (!el || !el.closest) { return null; }
            var car = el.closest('.js-product-carousel');
            return car && car.closest('.js-images-container') ? car : null;
        }

        function move(e) {
            if (!current) { return; }
            var rect = current.car.getBoundingClientRect();
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
            current.car.removeEventListener('mousemove', move);
            current.car.removeEventListener('mouseleave', leave);
            current = null;
        }

        /** Buduje (lub przebudowuje) powiekszenie dla aktywnego slajdu karuzeli. */
        function attach(car) {
            var item = car.querySelector('.carousel-item.active');
            var img = item && item.querySelector('img');
            if (!img) { return; }

            var layer = getLayer(item);
            loadInto(layer, img);
            applySize(layer, car);

            current = { car: car, item: item, layer: layer };
            car.addEventListener('mousemove', move);
            car.addEventListener('mouseleave', leave);
            item.classList.add('hbe-zoom-on');
        }

        document.addEventListener('mouseover', function (e) {
            var car = carouselOf(e.target);
            if (!car) {
                if (current && !current.car.contains(e.target)) { leave(); }
                return;
            }
            if (current && current.car === car) { return; }
            leave();
            attach(car);
        });

        /* Slajd zmieniony spod myszy (strzalki, miniatury): przelacz powiekszenie
           na nowe zdjecie od razu, bo bez ruchu kursora nie przyjdzie zadne mouseover. */
        document.addEventListener('slid.bs.carousel', function (e) {
            if (!current || current.car !== e.target) { return; }
            var car = current.car;
            leave();
            attach(car);
        }, true);

        window.addEventListener('resize', function () {
            if (current) { applySize(current.layer, current.car); }
        });
    }

    /* ══════════════════════════════════════════════════════════════════════
       Tryb dotyku — pinch w podgladzie pelnoekranowym
    ══════════════════════════════════════════════════════════════════════ */
    function initPinchZoom() {
        var MAX = 4;
        var modal = document.querySelector('.js-product-images-modal');
        if (!modal) { return; }

        var carousel = modal.querySelector('.js-product-images-modal-carousel') || modal;
        var st = null;       // {img, scale, tx, ty}
        var gesture = null;  // stan trwajacego gestu
        var lastTap = 0;

        function apply() {
            st.img.style.transform = st.scale === 1
                ? ''
                : 'translate(' + st.tx + 'px,' + st.ty + 'px) scale(' + st.scale + ')';
            st.img.classList.toggle('is-zoomed', st.scale > 1);
        }

        function reset() {
            if (st) {
                st.scale = 1;
                st.tx = 0;
                st.ty = 0;
                apply();
            }
            st = null;
            gesture = null;
        }

        function stateFor(img) {
            if (st && st.img === img) { return st; }
            reset();
            st = { img: img, scale: 1, tx: 0, ty: 0 };
            return st;
        }

        /** Trzyma zdjecie w kadrze — przy powiekszeniu s krawedzie moga odjechac o polowe nadmiaru. */
        function clampPan() {
            var maxX = (st.img.offsetWidth * (st.scale - 1)) / 2;
            var maxY = (st.img.offsetHeight * (st.scale - 1)) / 2;
            st.tx = Math.max(-maxX, Math.min(maxX, st.tx));
            st.ty = Math.max(-maxY, Math.min(maxY, st.ty));
        }

        /**
         * Zmienia skale tak, zeby punkt (cx, cy) — liczony od srodka zdjecia —
         * zostal pod palcem. Bez tej korekty zoom uciekalby zawsze do srodka.
         */
        function scaleAround(next, cx, cy) {
            next = Math.max(1, Math.min(MAX, next));
            st.tx = cx - (next / st.scale) * (cx - st.tx);
            st.ty = cy - (next / st.scale) * (cy - st.ty);
            st.scale = next;
            if (st.scale === 1) {
                st.tx = 0;
                st.ty = 0;
            } else {
                clampPan();
            }
            apply();
        }

        /**
         * Po pierwszym powiekszeniu podmienia zdjecie na oryginal — w modalu
         * srcset konczy sie na 1440 px, co przy czterokrotnym zoomie widac.
         */
        function upgradeSource(img) {
            if (img.dataset.hbeFull) { return; }
            var id = imageId(img.currentSrc || img.src);
            var url = id && originals[id];
            if (!url) { return; }

            img.dataset.hbeFull = '1';
            var pre = new Image();
            pre.onload = function () {
                img.removeAttribute('srcset');
                img.removeAttribute('sizes');
                img.src = url;
            };
            pre.src = url;
        }

        function activeImg(target) {
            var item = target && target.closest ? target.closest('.carousel-item') : null;
            if (!item || !modal.contains(item)) { return null; }
            return item.querySelector('img');
        }

        function centerOf(touches, img) {
            var rect = img.getBoundingClientRect();
            var mx = (touches[0].clientX + touches[1].clientX) / 2;
            var my = (touches[0].clientY + touches[1].clientY) / 2;
            return {
                x: mx - (rect.left + rect.width / 2),
                y: my - (rect.top + rect.height / 2)
            };
        }

        function distance(touches) {
            var dx = touches[0].clientX - touches[1].clientX;
            var dy = touches[0].clientY - touches[1].clientY;
            return Math.sqrt(dx * dx + dy * dy);
        }

        /* Wszystko slucha na karuzeli w fazie przechwytywania, bo karuzela
           Bootstrapa ma tu wlasne listenery od przesuwania slajdow palcem.
           Dopoki zdjecie jest powiekszone, palec ma ruszac kadrem, nie slajdem —
           przerywamy jej wtedy zdarzenie, zanim do niej dojdzie. */
        var CAPTURE = { capture: true, passive: false };

        function blockCarousel(e) {
            if (st && st.scale > 1) { e.stopPropagation(); }
        }

        carousel.addEventListener('pointerdown', blockCarousel, true);
        carousel.addEventListener('pointermove', blockCarousel, true);
        carousel.addEventListener('pointerup', blockCarousel, true);

        carousel.addEventListener('touchstart', function (e) {
            var img = activeImg(e.target);
            if (!img) { return; }

            if (e.touches.length === 2 || (st && st.img === img && st.scale > 1)) {
                e.stopPropagation();
            }

            if (e.touches.length === 2) {
                stateFor(img);
                upgradeSource(img);
                gesture = {
                    kind: 'pinch',
                    dist: distance(e.touches),
                    scale: st.scale,
                    center: centerOf(e.touches, img)
                };
                e.preventDefault();
                return;
            }

            if (e.touches.length === 1) {
                /* Dwuklik: szybkie przejscie miedzy 1x a 2,5x w dotknietym punkcie. */
                var now = Date.now();
                if (now - lastTap < 300) {
                    stateFor(img);
                    var rect = img.getBoundingClientRect();
                    var cx = e.touches[0].clientX - (rect.left + rect.width / 2);
                    var cy = e.touches[0].clientY - (rect.top + rect.height / 2);
                    if (st.scale > 1) {
                        scaleAround(1, cx, cy);
                    } else {
                        upgradeSource(img);
                        scaleAround(2.5, cx, cy);
                    }
                    e.preventDefault();
                    lastTap = 0;
                    return;
                }
                lastTap = now;

                if (st && st.img === img && st.scale > 1) {
                    gesture = {
                        kind: 'pan',
                        x: e.touches[0].clientX,
                        y: e.touches[0].clientY,
                        tx: st.tx,
                        ty: st.ty
                    };
                }
            }
        }, CAPTURE);

        carousel.addEventListener('touchmove', function (e) {
            if (!gesture || !st) { return; }
            e.stopPropagation();

            if (gesture.kind === 'pinch' && e.touches.length === 2) {
                var ratio = distance(e.touches) / (gesture.dist || 1);
                scaleAround(gesture.scale * ratio, gesture.center.x, gesture.center.y);
                e.preventDefault();
                return;
            }

            if (gesture.kind === 'pan' && e.touches.length === 1) {
                st.tx = gesture.tx + (e.touches[0].clientX - gesture.x);
                st.ty = gesture.ty + (e.touches[0].clientY - gesture.y);
                clampPan();
                apply();
                e.preventDefault();
            }
        }, CAPTURE);

        carousel.addEventListener('touchend', function (e) {
            if (gesture || (st && st.scale > 1)) { e.stopPropagation(); }
            gesture = null;
        }, true);

        modal.addEventListener('slid.bs.carousel', reset);
        modal.addEventListener('hidden.bs.modal', reset);
    }

    if (window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
        initHoverZoom();
    }

    if ('ontouchstart' in window || (navigator.maxTouchPoints || 0) > 0) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initPinchZoom);
        } else {
            initPinchZoom();
        }
    }
}());
