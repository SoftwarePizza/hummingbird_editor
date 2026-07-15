{**
 * Karta podarunkowa — footer promo block.
 * Mirrors the ps_linklist .footer-block markup so it lines up with the other
 * footer columns, with a small CTA button instead of a link list.
 *}
<div class="ps-linklist footer-block hbe-giftcard-footer">
  <p class="footer-block__title">{$hbe_giftcard_footer_label|escape:'html':'UTF-8'}</p>
  <div class="footer-block__content">
    {if $hbe_giftcard_footer_desc}
      <p class="hbe-giftcard-footer__desc">{$hbe_giftcard_footer_desc|escape:'html':'UTF-8'}</p>
    {/if}
    <a class="hbe-giftcard-footer__cta" href="{$hbe_giftcard_url|escape:'html':'UTF-8'}">
      {l s='Kup kartę' d='Shop.Theme.Global'}
    </a>
  </div>
</div>
