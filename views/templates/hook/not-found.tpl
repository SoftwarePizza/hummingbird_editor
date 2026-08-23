{*
 * Kafle kategorii i skroty na stronie 404 (hook displayNotFound, wolany
 * z themes/hummingbird/templates/errors/404.tpl).
 *
 * Nazwy kategorii i stron CMS przychodza z bazy w biezacym jezyku, wiec
 * ten szablon nie potrzebuje wlasnych tlumaczen poza dwoma naglowkami.
 *}
{if $hbe_404_categories}
  <nav class="hbe-404-cats" aria-labelledby="hbe-404-cats-title">
    <h2 class="hbe-404-cats__title" id="hbe-404-cats-title">
      {l s='Popular categories' d='Modules.Hummingbirdeditor.Shop'}
    </h2>

    <ul class="hbe-404-cats__list">
      {foreach from=$hbe_404_categories item=cat}
        <li class="hbe-404-cats__item">
          <a class="hbe-404-cats__link" href="{$cat.url|escape:'html':'UTF-8'}">{$cat.name|escape:'html':'UTF-8'}</a>
        </li>
      {/foreach}
    </ul>
  </nav>
{/if}

{if $hbe_404_links}
  <nav class="hbe-404-links" aria-labelledby="hbe-404-links-title">
    <h2 class="hbe-404-links__title" id="hbe-404-links-title">
      {l s='Where to go next' d='Modules.Hummingbirdeditor.Shop'}
    </h2>

    <ul class="hbe-404-links__list">
      {foreach from=$hbe_404_links item=item}
        <li class="hbe-404-links__item">
          <a class="hbe-404-links__link" href="{$item.url|escape:'html':'UTF-8'}">{$item.name|escape:'html':'UTF-8'}</a>
        </li>
      {/foreach}
    </ul>
  </nav>
{/if}
