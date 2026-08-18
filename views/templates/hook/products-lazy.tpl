{**
 * Atrapa karuzeli produktowej — to trafia do HTML strony glownej zamiast
 * kilkudziesieciu kart produktu. carousel-lazy.js podmienia cala sekcje na
 * prawdziwa tresc, gdy zbliza sie ona do ekranu.
 *
 * Naglowek i link do kategorii sa prawdziwe, wiec sekcja niesie tresc takze
 * wtedy, gdy JS nie wystartuje. Szkielet kart rezerwuje wysokosc, zeby
 * podmiana nie przesuwala tego, co uzytkownik wlasnie czyta.
 *}
<section class="hbe-products hbe-products--lazy"
         data-hbe-carousel="{$hbe_products_lazy_id|intval}"
         aria-busy="true">
  <div class="module-products container">
    <div class="module-products__split">

      <div class="module-products__intro">
        {if !empty($hbe_products_title)}
          {include file='components/section-title.tpl' title=$hbe_products_title}
        {/if}

        {if !empty($hbe_products_text)}
          <div class="hbe-products__text">{$hbe_products_text|escape:'html':'UTF-8'|nl2br nofilter}</div>
        {/if}

        <div class="module-products__buttons module-products__buttons--intro">
          {if !empty($hbe_products_all_link)}
            <a class="btn btn-primary hbe-section-link" href="{$hbe_products_all_link|escape:'html':'UTF-8'}">
              {l s='All products' d='Shop.Theme.Catalog'}
            </a>
          {/if}
        </div>
      </div>

      <div class="module-products__carousel">
        <div class="hbe-products__skeleton" aria-hidden="true">
          {section name=card loop=$hbe_products_lazy_count}
            <div class="hbe-products__skeleton-card"></div>
          {/section}
        </div>
      </div>

    </div>
  </div>
</section>
