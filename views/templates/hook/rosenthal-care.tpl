{**
 * Rosenthal Care promo block — rendered in the cart via displayShoppingCartFooter.
 * Managed by hummingbird_editor (BO → Hummingbird → Koszyk → Rosenthal Care).
 *}
{if isset($hbe_care)}
  <section class="hbe-care">
    <div class="hbe-care__body">
      {if $hbe_care.heading}
        <h3 class="hbe-care__heading">{$hbe_care.heading|escape:'html':'UTF-8'}</h3>
      {/if}

      {if $hbe_care.lines}
        <div class="hbe-care__text">
          {foreach $hbe_care.lines as $line}
            <p class="hbe-care__line">{$line|escape:'html':'UTF-8'}</p>
          {/foreach}
        </div>
      {/if}
    </div>

    <div class="hbe-care__actions">
      {if $hbe_care.login_required && !$hbe_care.is_logged}
        <a class="hbe-care__btn btn btn-outline-primary" href="{$hbe_care.login_url}" rel="nofollow">
          {l s='Zaloguj się' mod='hummingbird_editor'}
        </a>
      {else}
        <form class="hbe-care__form" action="{$hbe_care.cart_url}" method="post">
          <input type="hidden" name="id_product" value="{$hbe_care.id_product}">
          <input type="hidden" name="token" value="{$hbe_care.static_token}">
          <input type="hidden" name="qty" value="1">
          <button type="submit" class="hbe-care__btn btn btn-primary" data-button-action="add-to-cart" data-ps-ref="add-to-cart">
            {$hbe_care.button|escape:'html':'UTF-8'}
          </button>
        </form>
      {/if}
    </div>
  </section>
{/if}
