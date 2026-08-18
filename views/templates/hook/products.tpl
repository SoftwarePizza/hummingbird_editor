{**
 * Karuzela produktow z kategorii.
 *
 * Sekcja przeniesiona z modulu multislider, ktory rozszerzal components/featured-products.tpl —
 * komponent istniejacy tylko w motywach opartych na Classicu. Hummingbird dostarcza rownowazny
 * components/module-products.tpl i to na nim opiera sie ten szablon, tak samo jak ps_featuredproducts.
 *}
{extends file="components/module-products.tpl"}

{block name='module_products_name'}hbe-products{/block}

{block name='module_products'}
  <section class="hbe-products">
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

          <div class="hbe-carousel-nav" data-hbe-carousel-nav>
            <button type="button" class="hbe-carousel-nav__btn" data-hbe-carousel-prev aria-label="{l s='Previous' d='Shop.Theme.Global'}">
              <i class="material-icons" aria-hidden="true">&#xE314;</i>
            </button>
            <button type="button" class="hbe-carousel-nav__btn" data-hbe-carousel-next aria-label="{l s='Next' d='Shop.Theme.Global'}">
              <i class="material-icons" aria-hidden="true">&#xE315;</i>
            </button>
          </div>
        </div>

        <div class="module-products__carousel">
          {if $hbe_products}
            <div class="module-products__list">
              {include file='catalog/_partials/productlist.tpl' products=$hbe_products}
            </div>
          {/if}
        </div>

      </div>
    </div>
  </section>
{/block}
