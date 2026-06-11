{* "Inni kupili również" — one-card carousel below the FAQ on the product page.
   Items load lazily via AJAX (controllers/front/related.php). *}
<section class="hbe-related" data-hbe-related data-hbe-related-url="{$hbe_related_ajax_url|escape:'html':'UTF-8'}">
  <div class="hbe-related__header">
    <h2 class="hbe-related__title">{$hbe_related_title|escape:'html':'UTF-8'}</h2>
    <div class="hbe-related__nav">
      <button type="button" class="hbe-related__arrow" data-hbe-related-prev disabled
              aria-label="{l s='Poprzedni produkt' mod='hummingbird_editor'}">
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true"><path d="M9 2 4 7l5 5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
      <button type="button" class="hbe-related__arrow" data-hbe-related-next disabled
              aria-label="{l s='Następny produkt' mod='hummingbird_editor'}">
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true"><path d="m5 2 5 5-5 5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
    </div>
  </div>
  <div class="hbe-related__viewport">
    <div class="hbe-related__track" data-hbe-related-track>
      <div class="hbe-related__card hbe-related__card--skeleton" aria-hidden="true"></div>
    </div>
  </div>
</section>
