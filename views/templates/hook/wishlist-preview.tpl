{*
 * Wishlist preview drawer (Figma: Ulubione) — static shell injected on every
 * page; rows are rendered client-side by wishlist-preview.js from the
 * blockwishlist AJAX API. Managed by hummingbird_editor so the core
 * blockwishlist module stays untouched.
 *}
<div class="hbe-wishlist-preview" data-ps-component="wishlist-preview" hidden>
  <div class="hbe-wishlist-preview__overlay" data-ps-action="wishlist-preview-close"></div>
  <aside class="hbe-wishlist-preview__panel" role="dialog" aria-modal="true"
         aria-labelledby="hbe-wishlist-preview-title" tabindex="-1" data-ps-ref="wishlist-preview-panel">
    <header class="hbe-wishlist-preview__header">
      <p class="hbe-wishlist-preview__title" id="hbe-wishlist-preview-title">{l s='Ulubione' mod='hummingbird_editor'}</p>
      <button class="hbe-wishlist-preview__close" type="button"
              data-ps-action="wishlist-preview-close" aria-label="{l s='Zamknij' mod='hummingbird_editor'}">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path d="M1 1l14 14M15 1L1 15" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
        </svg>
      </button>
    </header>
    <div class="hbe-wishlist-preview__body" data-ps-target="wishlist-preview-list" aria-live="polite"></div>
    <footer class="hbe-wishlist-preview__footer">
      <a class="hbe-wishlist-preview__cta" href="{$hbe_wishlist_lists_url|escape:'html':'UTF-8'}"
         data-ps-ref="wishlist-preview-cta" hidden>{l s='Zobacz wszystkie' mod='hummingbird_editor'}</a>
    </footer>
  </aside>
</div>
