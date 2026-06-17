# Mobile Homepage Styling — Plan & Progress

**Goal:** Style the PrestaShop homepage for **mobile (360px)** to match the Figma
design at `figma/figma-to-html/Homepage.png` (360 × 8549 px).
The markup/content already exists; this task is **CSS only** (with tiny template
tweaks only if a class/hook is missing). 90% of the content lives in the
`hummingbird_editor` module.

> **How to resume:** read this file top-to-bottom. The "Section map" table is the
> source of truth for which Figma block maps to which template + CSS. The
> "Progress checklist" tracks what is done. Edits live in
> `modules/hummingbird_editor/views/css/home.css` (plus `front.css` for shared
> tokens). Each finished section is committed separately in the module git repo.

## Environment notes
- CSS lives in `modules/hummingbird_editor/views/css/` (plain CSS, BEM `.hbe-*`,
  design-token vars). NOT SCSS — that is the theme. Follow module conventions.
- Design tokens (`--fs-*`, `--font-*`, `--c-*`) are defined in the theme:
  `themes/hummingbird/src/scss/prestashop/base/_design-tokens.scss`.
  Mobile scale: H1 32 / H2 26 / H3 24 / H4 20 / H5 16. Body L18 M16 S14 XS12 XXS11.
  Fonts: headings = Lora (serif), body = Geist (sans). Colors: --c-black #242424,
  --c-grey-5 #5a5a5a, --c-grey-2 #e5e5e5, --c-grey-1 #f5f5f5, --c-beige #f5f1ea.
- Mobile breakpoints already used in home.css: `max-width: 767px`, `575px`, `479px`.
- No live preview: the staging domain serves a different multistore shop, and no
  headless browser is installed. Verify visually with the static harness at
  `modules/hummingbird_editor/_preview/mobile.html` (open at 360px width) and by
  comparing against the Figma crops in `/tmp/hp/` (regenerate with the crop
  command in "Regenerating crops" below).
- Page side padding on mobile = **16px** (theme `.container`). Full-bleed sections
  (slider, imghero, infobar, topbar) span edge-to-edge.

## Section map (Figma top → bottom)
Order comes from `HBE_HOME_ORDER` config:
`slider, infobar(off), module_ps_bestsellers, imghero, cols3, tagline, 1,
katcols, module_ps_newproducts, imghero2, splitblock, infobar2, cols3desc,
module_ps_customtext, icons4, 3`

| # | Figma section | Component | Template | CSS prefix | Scope |
|---|---|---|---|---|---|
| 1 | Promo bar "Promocja…-20%" + header | topbar + theme header | `topbar.tpl` | `.hbe-topbar` | module (bar) / theme (header) |
| 2 | Hero "Sanssouci Midas Rosenthal" + ">" arrow + "Zobacz produkt" | **slider** | `slider.tpl` | `.hbe-slider` | module |
| 3 | Light infobar "Promocja…KUP TERAZ" | infobar (cfg off) / bestsellers hdr | `infobar.tpl` | `.hbe-infobar` | module |
| 4 | "Nasze bestsellery" product carousel | ps_bestsellers | — | — | **theme/other module** |
| 5 | Big image "Wyróżniona kolekcja" + "Zobacz produkty" | imghero | `imghero.tpl` | `.hbe-imghero` | module |
| 6 | Arrow links: Swarovski / Versace / Arthur Krupp | cols3 | `cols3.tpl` | `.hbe-cols3` | module |
| 7 | Tagline "Porcelana Rosenthal to…" + "Czytaj o nas" | tagline | `tagline.tpl` | `.hbe-tagline` | module |
| 8 | (custom block 1) | block id 1 | `block.tpl` | `.hbe-block` | module |
| 9 | "Kategorie" 2 image cards (Filiżanki / Anioły) | katcols | `katcols.tpl` | `.hbe-katcols` | module |
| 10 | "Nowości" product carousel | ps_newproducts | — | — | **theme/other module** |
| 11 | Big image "Wyróżniona kolekcja" #2 | imghero2 | `imghero2.tpl` | `.hbe-imghero--second` | module |
| 12 | "Zainspiruj się…" title+desc+2 imgs+"Zobacz inspiracje" | splitblock | `splitblock.tpl` | `.hbe-splitblock` | module |
| 13 | Dark bar "Zobacz, gdzie… NASZE SALONY >>" | infobar2 | `infobar.tpl` | `.hbe-infobar` | module |
| 14 | Brand links LLadro / Maison Berger / Karenski (desc+arrow) | cols3desc | `cols3desc.tpl` | `.hbe-cols3desc` | module |
| 15 | SEO expandable text "Niemiecka porcelana…" + "Rozwiń" | ps_customtext | — | — | **theme/other module** |
| 16 | "Nazwa cechy" ×4 feature icons | icons4 | `icons4.tpl` | `.hbe-icons4` | module |
| 17 | (custom block 3) | block id 3 | `block.tpl` | `.hbe-block` | module |
| 18 | Footer | theme | — | — | **theme** |

## Per-section Figma spec (mobile, 360px)
- **Slider hero:** full-bleed image; circular light ">" next-arrow vertically
  centred on the right edge of the image; caption rendered **below** the image on
  white — Lora ~26px black title (2 lines), then black square CTA "Zobacz produkt"
  (white text, ~14px, no radius). Dots hidden on this layout? (single slide).
- **Light infobar:** full-bleed, bg ≈ --c-grey-1 (#f5f5f5), text #242424 ~13px,
  centred, link underlined. ~12px vertical padding.
- **imghero "Wyróżniona kolekcja":** full-bleed image with overlay bottom-left:
  white Lora title ~26px + white square CTA "Zobacz produkty" (black text).
- **cols3:** stacked rows, each: Lora ~24px text left + circular arrow right,
  divider line between rows; full-width; ~16px side padding.
- **tagline:** Lora ~26px black, max-width 100% on mobile; "Czytaj o nas"
  underlined link below; generous top/bottom padding (~48px).
- **katcols:** header "Kategorie" (Lora ~24px) + right link "Zobacz wszystkie
  kolekcje" (underlined, may wrap below on mobile); two image cards stacked
  full-width, caption Lora ~20px below each image.
- **imghero2:** same as imghero.
- **splitblock "Zainspiruj się":** title Lora ~26px + desc ~16px grey + two images
  stacked + white square CTA "Zobacz inspiracje" (black border). Stacks vertically.
- **infobar2 (dark):** full-bleed dark #242424 bg, white text centred ~13px,
  "NASZE SALONY >>" underlined.
- **cols3desc:** stacked brand rows, each: bold title ~16-18px + grey desc ~14px,
  circular arrow on the right, divider between rows.
- **icons4:** stacked single-column on mobile (Figma shows 1-per-row), each: round
  grey icon chip ~64px, bold title "Nazwa cechy" ~16px, grey desc ~14px; left
  aligned in Figma (not centred!). Verify alignment.

## Progress checklist
- [x] Recon: Figma analysed, section→template map built, tokens located
- [x] Plan/progress file created (this file)
- [x] Static preview harness (`_preview/mobile.html`) — layout/typography preview
- [x] §2 slider hero — mobile caption moved BELOW image (static, black non-uppercase
      Lora), gradient overlay removed, black square CTA, light circular nav arrows.
      FIXED after real screenshot: (a) caption was being **clipped** because slider.js
      sets `track.style.height = img.offsetHeight` + slide `overflow:hidden` → added
      `.hbe-slider__track{height:auto!important}` + `.hbe-slider__slide{overflow:visible}`
      on mobile; (b) the slide CTA is configured **white-bg/dark-text** (`cta_bg=#fff`)
      which is invisible on the white caption — forced `background:var(--c-black)!important;
      color:#fff!important`. Slide id=4, one slide, text_position=2, show_text=1.
      ⚠ arrow vertical centre still `top:38%` approximation.
- [x] §1 topbar promo bar — already correct (`--bs-primary` = #242424, 12px, centred)
- [x] §3 + §13 infobar / infobar2 — mobile font reduced to 14px, padding tightened
      (bg/colour come from config inline style: light #f5f5f5 / dark #242424)
- [x] §5 + §11 imghero / imghero2 overlay — title bottom-margin reduced, overlay
      raised + widened on mobile
- [x] §6 cols3 arrow links — mobile dividers added (grey top/bottom borders),
      text → fs-h4
- [x] §7 tagline — mobile size restored to responsive fs-h2 (was shrunk to fs-h4)
- [x] §9 katcols Kategorie — title weight 700→400 (Lora regular)
- [x] §12 splitblock "Zainspiruj się" — mobile reorder via `display:contents` so the
      CTA renders AFTER both images (title, desc, img1, img2, CTA)
- [x] §14 cols3desc brand links — title → Lora serif fs-h4 regular; mobile dividers
      → light grey; link side-padding removed on mobile
- [x] §16 icons4 features — grey circle icon chip added; mobile = left-aligned single
      column (was centred 2-col)
- [ ] §8/§17 custom blocks (verify they don't break layout — content unknown; visually
      inspect on a real render)
- [ ] Final verification pass on a real device/render: section-to-section spacing
      rhythm, exact font sizes (cols3 / cols3desc / list items), arrow vertical centre

## Theme work (themes/hummingbird — SCSS, rebuild with `npm run build`)
Workflow: edit `src/scss/...`, then `cd themes/hummingbird && npm run build` (≈7s,
node_modules present). `assets/css/theme.css` is **gitignored** (build artifact) —
only the SCSS is tracked. Build is deterministic.

- [x] §18 footer — built "per PNG design" (dark grid, newsletter, legal). Fixes
      [`layout/_footer.scss`]: (a) bottom bar bg `--c-grey-5` → `transparent` (uniform
      dark); (b) newsletter input → **solid white box, dark text** + dark arrow;
      (c) **columns expanded on mobile** — KONTAKT/ZAKUPY ONLINE/ROSENTHAL were
      collapsible accordions (chevrons, collapsed); Figma shows them open → force
      `.footer-block__content.collapse{display:block!important}` + hide
      `.footer-block__title--toggle .stretched-link` under md; (d) **social icons**
      (ps_socialfollow in displayFooterBefore, rendered white above footer) → dark
      band with circular bordered icons; (e) **newsletter GDPR checkbox**
      (`.ps-emailsubscription__gdpr`) was cramped under the input → flex + top margin.
      ⚠ Social ideal placement is UNDER KONTAKT (Figma) — needs an admin hook move
      (ps_socialfollow → displayFooter), can't be done in pure CSS. The GDPR consent
      text is lorem-ipsum placeholder (configure or disable the GDPR module to match
      Figma, which shows no checkbox).
- [x] §15 SEO text (ps_customtext "Niemiecka porcelana…") — paragraph `line-height`
      was `100%` (cramped) → `1.6`; heading → fs-h4. Scoped to `#custom-text` so the
      footer KONTAKT (also `.ps-customtext`) is untouched. [`modules/_customtext.scss`]
- [x] §4/§10 product cards (ps_bestsellers / ps_newproducts) — already built per
      design in [`components/product/_product-miniature.scss`]: circular black
      wishlist button top-right, "Nowość" flag, 40px image padding, grey price,
      full-width square "Dodaj do koszyka". No change needed; verify on real render.
- [x] icons4 grey **circle** chip — independently confirmed by Figma crop (the chip
      is a light-grey circle), matches the module-side change.
- [x] §1 header — mobile-only fix [`layout/_header-bottom.scss`, `@media max-width:md`]:
      **centred logo** via `position:absolute;left:50%;translateX(-50%)` on
      `.header-bottom__logo` (overrides the `me-auto` left-align), and **hid the
      standalone account icon** (`#_mobile_ps_customersignin{display:none}`) — Figma
      shows only wishlist+cart on the right. ⚠ Still TODO/verify: Figma puts the
      **search icon on the LEFT** next to the hamburger; it currently sits in the
      right icon group (search is module-injected via displayTop / `wrapHeaderIcons`,
      so moving it needs the rendered DOM — check on a real render).
- [x] Mobile menu (offcanvas drawer, ps_mainmenu) — Figma `Mobile-Menu.png`. Restyled
      to match the design [`modules/_mainmenu.scss` + theme `ps_mainmenu.tpl`]:
      added a **"Menu" header label** (left of the close X, hidden once drilled into a
      submenu via `:has(.js-back-button:not(.d-none))`); top-level items → **serif Lora
      ~20px**, dark; **dividers** between every row (+ a top divider); the submenu
      toggle → **circular bordered chevron button** pointing right (rotated material
      caret). Kept the existing JS **drill-down** behaviour (each submenu is a sliding
      panel + back button). ⚠ Figma shows an **inline accordion** (expand in place) and
      level-2 carets are plain (not circular) — converting drill-down→accordion is a JS
      rewrite, deferred; current = Figma *look* on the existing drill-down mechanics.
- [ ] (theme, needs real render) footer & header & mobile-menu final visual check —
      depend on PrestaShop module-generated markup, so not in the static harness.

## Desktop polish (Figma `Rosenthal - UI/` component specs)
Compared the live desktop render against the Figma component PNGs (Links, Icons with
Text, Content, Footer, Images with Text). Fixes (module `home.css` — plain CSS, no
theme rebuild needed, just clear the PrestaShop CCC cache):
- [x] **icons4** (Icons with Text) — was centred with vertical dividers; Figma is
      **left-aligned, no dividers** → `.hbe-icons4__col` align-items/text-align →
      start/left, `__row` gap 32px, `__divider{display:none}`.
- [x] **cols3desc** (Links) — removed the heavy **black bottom border** that crowded
      the SEO text below (Figma desktop has no divider); mobile keeps light grey
      dividers between stacked rows.
- [x] **SEO text** (Content) — crowding resolved by the cols3desc border removal;
      heading fs-h4 + paragraph line-height 1.6 already in place (theme `_customtext`).
- [x] **Footer social** (Footer) — DONE, 1:1. The IG/FB icons were rendering **twice**:
      once correctly inside the KONTAKT column (`.footer__social`, hardcoded in
      `ps_contactinfo.tpl`) and once as a duplicate centred band from `ps_socialfollow`
      in `displayFooterBefore`. **DB change:** deleted the `hook_module` row
      (`id_module=32` ps_socialfollow, `id_hook=553` displayFooterBefore, `id_shop=1`)
      → band removed; only the KONTAKT-column icons remain (Figma layout). Removed the
      now-dead `.footer__before .ps-socialfollow` SCSS. (Reversible: re-add the module
      in admin → Positions → displayFooterBefore.)
- Splitblock (Images with Text), infobar2, footer columns/newsletter: already match.

## Anti-flicker / layout shift (CLS)
The module section images (`_picture.tpl`) rendered `<img>` with **no width/height or
aspect-ratio** → they collapsed to 0 height and the page jumped as each one loaded.
Fixed in `home.css` (no rebuild — plain CSS): each section now **reserves its box via
`aspect-ratio`** (from the real uploaded-file ratios) + a **light-grey placeholder
background** (`--c-grey-1`) shown until the image paints, with `object-fit: cover`.
Ratios used — slider 3869:1558 desktop / 1:1 mobile (art-directed), imghero 3:2,
imghero2 4:3, katcols-L 3:4, katcols-R 2:3, splitblock-mid 1:1, splitblock-right 3:4.
Product-card images (theme miniatures) already carry `width`/`height` attrs, so they
weren't shifting — left as-is.

## Resource loading / flicker (investigated + fixed)
Findings: CCC (combine CSS) is **OFF**, but every stylesheet (theme.css, the module's
front.css + home.css) is a **render-blocking `<link>` in `<head>`** (see
`_partials/stylesheets.tpl`). So there is **no order-based FOUC** — the browser paints
already-styled, and "module before theme" wouldn't change anything (and the module must
load *after* the theme for the cascade anyway). The real flicker sources were:
- [x] **Images** — fixed earlier (aspect-ratio reservation + grey placeholder).
- [x] **Fonts (FOUT)** — Lora (headings) + Geist (body) used `font-display: swap` and
      were **not preloaded**, so headings flashed from the serif fallback to Lora.
      → Preloaded Lora 400 + Geist 400/500 in tracked `_partials/head.tpl`
      (`<link rel=preload as=font crossorigin>`). Material Icons was already preloaded.
- [x] **Slimmer render-blocking CSS** — the theme `@import`ed **Inter in 5 weights**
      (35 dead `@font-face` blocks) although nothing uses it (body=Geist, headings=Lora).
      Removed from `base/_fonts.scss` → theme.css 380 KB → **368 KB** (−12.5 KB).
- [ ] **(optional, test-then-keep)** Enable CCC "Smart cache for CSS" in Admin →
      Advanced → Performance to merge the ~5 CSS requests into one ("wszystko na raz").
      NOT enabled here because combining can rewrite font `url()` paths — verify fonts
      still load after toggling; revert if they 404. Critical-CSS/async split for
      home.css was considered and skipped: theme.css (368 KB) dominates the blocking
      budget, so async-loading the small home.css wouldn't speed first paint.

## Remaining / needs a real render to finish
- Verify exact type sizes against Figma at 360px (I used design tokens; a couple of
  list-item sizes were judgement calls): cols3 list items, cols3desc brand names.
- Slider arrow vertical centring (see §2 note) — only perfectible with a template
  tweak or a known image aspect-ratio.
- Custom blocks 1 & 3 content is unknown (DB) — confirm they look right.
- Inter-section vertical spacing rhythm (each section sets its own padding; check the
  cumulative rhythm matches Figma once rendered).

## Regenerating crops
```
cd figma/figma-to-html
mkdir -p /tmp/hp
for i in $(seq 0 7); do y=$((i*1100)); \
  convert Homepage.png -crop 360x1150+0+${y} +repage /tmp/hp/hp_$i.png; done
```

## Decisions / open questions
- Slider caption moves **below** the image on mobile per Figma — this changes the
  shared slider component's mobile behaviour. Applied via `@media (max-width:767px)`
  only, so desktop overlay is unaffected.
- icons4 is **left-aligned** single column on mobile in Figma (current CSS centres
  + 2-col). Needs override.
</content>
