# Zmiany / poprawki Figma (zmiany.pdf) — Plan & Progress

**Źródło:** `figma/figma-to-html/zmiany.pdf` (PDF był uszkodzony/ucięty — czytane ze
stron `figma/figma-to-html/zmiany.pdf~/1.jpg … 6.jpg`). Każda strona = opis PL +
screen elementu, którego dotyczy.

**Zakres:** runda poprawek **desktopowych + strukturalnych** do szablonu
`themes/hummingbird` (PrestaShop, fork Hummingbird Elegant) oraz modułu
`modules/hummingbird_editor`. Wcześniejsza runda (`MOBILE_HOMEPAGE_PROGRESS.md`)
zrobiła mobile + część desktop polish — tu kontynuujemy.

## Środowisko / workflow (z poprzedniego progress doc)
- **Moduł CSS:** `modules/hummingbird_editor/views/css/*.css` — czysty CSS, BEM `.hbe-*`,
  zmienne design-token. Edycja → wystarczy wyczyścić cache CCC PrestaShop (bez builda).
  Pliki: `home.css` (homepage), `front.css` (wspólne), `listing.css`, `product.css`,
  `cart-preview.css`, `wishlist-preview.css`.
- **Theme CSS:** SCSS w `themes/hummingbird/src/scss/...`. Edycja → `cd themes/hummingbird
  && npm run build` (~7s, node_modules są). `assets/css/theme.css` jest **gitignored**
  (artefakt builda) — w gicie tylko SCSS.
- **Design tokeny:** `themes/hummingbird/src/scss/prestashop/base/_design-tokens.scss`.
  Fonts: nagłówki = Lora (serif), body = Geist (sans). Kolory: `--c-black #242424`,
  `--c-grey-5 #5a5a5a`, `--c-grey-2 #e5e5e5`, `--c-grey-1 #f5f5f5`, `--c-beige #f5f1ea`.
- **Brak live preview:** staging serwuje inny sklep multistore, brak headless browsera.
  Buduję wg specyfikacji; **weryfikacja wizualna po stronie użytkownika** (lub statyczny
  harness `modules/hummingbird_editor/_preview/`).
- Reguły kodu (memory): no jQuery, no `any` TS, JS hooki tylko `data-ps-*`, brak logiki
  biznesowej w theme, BEM + SCSS `@layer`, A11y. Moduł NIE ma parent theme (`parent:`
  Smarty resource pusty — w tpl używać `file:`/`module:`).

## Mapa zmian (strona PDF → element → pliki)

| # | Str | Opis (PL skrót) | Komponent | Pliki (kandydaci) | Ryzyko |
|---|-----|------------------|-----------|-------------------|--------|
| 1 | 1 | MENU desktop: hover podświetla PRODUKTY ale nie KOLEKCJE — ma oba. Foto w mega-menu **bez zaokrągleń** | ps_mainmenu | theme `src/scss/prestashop/modules/_mainmenu.scss` (+ `ps_mainmenu.tpl`) | L |
| 2 | 1 | Wejście w PRODUKTY / KOLEKCJE: na górze kafle-zdjęcia podkategorii z nazwami, niżej opisy jak w Figmie | kategoria | theme `catalog/_partials/subcategories.tpl`, `catalog/listing/category.tpl`, `_partials/miniatures/category.tpl`, `src/scss/prestashop/pages/_category.scss` | **H** |
| 3 | 2 | Polubienia = **kółko z serduszkiem** jak w makiecie | blockwishlist / miniatura | theme `catalog/_partials/miniatures/product.tpl`, `src/scss/.../product/_product-miniature.scss` | L |
| 4 | 2 | Aktywna pozycja menu (INSPIRACJE): **ramka zbędna** — tylko hover, jaśniejszy kolor | ps_mainmenu | theme `_mainmenu.scss` | L |
| 5 | 2 | Hover **równy** na banerze głównym i wyróżnionych kolekcjach; te same przyciski; lekka przeźroczystość; na białym i czarnym | imghero/imghero2/slider | module `home.css` (`.hbe-imghero`, `.hbe-slider`, CTA) | M |
| 6 | 2 | Odnośniki LLADRO/MAISON BERGER/KARENSKI: **zdjęcia nad linkami** (kwadrat/pion), desktop 3 w rzędzie, mobile pod sobą. Sekcja NAZWA CECHY (icons4) **OUT** na teraz | cols3desc + icons4 | module `cols3desc.tpl`, `home.css`, admin (obraz na kolumnę?); `HBE_HOME_ORDER` (usuń `icons4`) | M |
| 7 | 3 | Kafle kategorii (Filiżanki/Anioły): **równe odstępy** zdjęcie↔podpis, także po hover/ruchu | katcols | module `home.css` (`.hbe-katcols`) | L |
| 8 | 3 | Niespójne zaokrąglenia — jedne kafle zaokrąglone, inne nie. **Wszystko ostre, bez radius** | katcols + splitblock | module `home.css` | L |
| 9 | 3 | Stopka: okno NEWSLETTER **bez zaokrągleń** | footer | theme `src/scss/prestashop/layout/_footer.scss` (input newsletter) | L |
| 10 | 4 | Podgląd koszyka (hover dropdown): **delikatniejszy**, mniej boldów, cieńsza linia oddzielająca | cart-preview | module `views/css/cart-preview.css` (+ ew. `views/js/cart-preview.js`) | M |
| 11 | 4 | Strona koszyka ładniejsza: podsumowanie, ikony (polityka/dostawa/zwroty), kod promo, pakowanie na prezent, Rosenthal Care, odstępy | cart page | theme `checkout/cart.tpl` + `_partials/cart-*.tpl`, `src/scss/.../pages/_cart.scss`, blockreassurance | **H** |
| 12 | 5 | Karta produktu: inne, **schludniejsze odstępy** między opisami. Zakładki rozwijane (pakowanie/transport/płatność) — **DECYZJA: czy potrzebne** | product page | theme `catalog/product.tpl` + `_partials/product-*.tpl`, `src/scss/.../pages/_product.scss`; module `displayProductSections`/`displayFooterProduct` | M |
| 13 | 6 | Checkout **jednostronicowy**: wszystko na jednej stronie bez przeklikiwania, sensowne checkboxy, metody dostawy w podgrupach | checkout | theme `checkout/checkout-process.tpl` + `_partials/steps/*.tpl`, `src/scss/.../pages/_checkout.scss` | **H** |

Ryzyko: L=niskie (CSS), M=średnie (CSS+drobny tpl), H=duże (przebudowa szablonu/layoutu).

## Decyzje do potwierdzenia z klientem
- **#13 checkout:** natywny checkout PS 1.7 jest już jednostronicowy z krokami
  rozwijanymi (accordion). Plan = wymusić rozwinięcie wszystkich kroków + restyle +
  pogrupować metody dostawy, **bez** instalowania osobnego modułu OPC (mniejsze ryzyko).
- **#12 zakładki produktu:** klient pisze „nie wiem czy są konieczne". Domyślnie
  **zostawiamy** zakładki, poprawiamy tylko odstępy/styl; usunięcie dopiero po decyzji.
- **#6 zdjęcia nad linkami salonów:** czy obrazy mają być konfigurowalne w adminie
  (pole per-kolumna w cols3desc), czy wystarczą wgrane na sztywno? Konfigurowalne =
  więcej pracy (admin + DB), ładniej docelowo.

## Fazy (kolejność wykonania)
- **Faza A — szybkie poprawki CSS (L):** #4, #1(radius+hover), #8, #7, #9, #3. Theme:
  jeden `npm run build`. Moduł: tylko CSS.
- **Faza B — spójność banerów/hover (M):** #5.
- **Faza C — sekcja salonów + usunięcie icons4 (M):** #6.
- **Faza D — landing kategorii PRODUKTY/KOLEKCJE (H):** #2.
- **Faza E — koszyk (M+H):** #10, #11.
- **Faza F — karta produktu (M):** #12 (bez usuwania zakładek do decyzji).
- **Faza G — checkout jednostronicowy (H):** #13.

## Progress checklist
- [x] Recon: PDF odczytany ze stron jpg, mapa zmian→pliki zbudowana, plan zapisany
- [ ] Faza A — szybkie poprawki CSS
- [ ] Faza B — hover banerów
- [ ] Faza C — salony + icons4 out
- [ ] Faza D — landing kategorii
- [ ] Faza E — koszyk (preview + strona)
- [ ] Faza F — karta produktu
- [ ] Faza G — checkout

## Uwagi
- PDF `zmiany.pdf` jest **ucięty** (zadeklarowane ~10.4 MB, na dysku 1.28 MB, brak
  `%%EOF`). Treść czytana wyłącznie z `zmiany.pdf~/*.jpg`.
- Po edycjach modułu: wyczyść cache PrestaShop (CCC) by zobaczyć zmiany CSS.
- Po edycjach theme: `cd themes/hummingbird && npm run build`.
