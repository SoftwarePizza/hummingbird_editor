{* Cards injected into the hbe-related carousel track (AJAX response). *}
{foreach from=$hbe_related_products item=p}
<article class="hbe-related__card">
  <a class="hbe-related__media" href="{$p.url|escape:'html':'UTF-8'}" tabindex="-1" aria-hidden="true">
    {if isset($p.cover.bySize.default_xs.url)}
      <img src="{$p.cover.bySize.default_xs.url|escape:'html':'UTF-8'}" alt="" width="160" height="160" loading="lazy">
    {elseif isset($p.cover.small.url)}
      <img src="{$p.cover.small.url|escape:'html':'UTF-8'}" alt="" width="98" height="98" loading="lazy">
    {/if}
  </a>
  <div class="hbe-related__info">
    <a class="hbe-related__name" href="{$p.url|escape:'html':'UTF-8'}">{$p.name|escape:'html':'UTF-8'}</a>
    <div class="hbe-related__price">
      {$p.price}
      <span class="hbe-related__tax">{l s='(brutto)' mod='hummingbird_editor'}</span>
    </div>
  </div>
  <form class="hbe-related__form" action="{$hbe_cart_url|escape:'html':'UTF-8'}" method="post">
    <input type="hidden" name="id_product" value="{$p.id_product|intval}">
    <input type="hidden" name="token" value="{$hbe_static_token}">
    <input type="hidden" name="qty" value="1">
    <input type="hidden" name="add" value="1">
    <button type="submit" class="hbe-related__cart" data-button-action="add-to-cart"
            aria-label="{l s='Dodaj do koszyka' mod='hummingbird_editor'}: {$p.name|escape:'html':'UTF-8'}">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
        <path d="M4.5 6.5h11l-1 10h-9l-1-10Z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/>
        <path d="M7 8.5V5a3 3 0 0 1 6 0v3.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
      </svg>
    </button>
  </form>
</article>
{/foreach}
