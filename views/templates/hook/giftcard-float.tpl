{**
 * Karta podarunkowa — floating pill button (position:fixed corner).
 * Dismissible for the current browser session (sessionStorage), so it comes
 * back on the visitor's next visit. Vanilla JS only (no jQuery on the front).
 *}
<div class="hbe-giftcard-float hbe-giftcard-float--{$hbe_giftcard_float_position|escape:'html':'UTF-8'}"
     id="hbe-giftcard-float" hidden>
  <a class="hbe-giftcard-float__link" href="{$hbe_giftcard_url|escape:'html':'UTF-8'}">
    <svg class="hbe-giftcard-float__icon" width="22" height="22" viewBox="0 0 24 24" fill="none"
         stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <rect x="3" y="8" width="18" height="4" rx="1"/>
      <path d="M12 8v13M4 12v7a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-7"/>
      <path d="M12 8S10.5 3.5 8 4.2C6.3 4.7 6 7 8 8h4zM12 8s1.5-4.5 4-3.8C17.7 4.7 18 7 16 8h-4z"/>
    </svg>
    <span class="hbe-giftcard-float__label">{$hbe_giftcard_float_label|escape:'html':'UTF-8'}</span>
  </a>
  <button type="button" class="hbe-giftcard-float__close"
          aria-label="{l s='Zamknij' d='Shop.Theme.Global'}">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
  </button>
</div>
<script>
(function () {
  var KEY = 'hbeGiftcardFloatClosed';
  var el = document.getElementById('hbe-giftcard-float');
  if (!el) { return; }
  try { if (sessionStorage.getItem(KEY) === '1') { return; } } catch (e) {}
  el.hidden = false;
  var close = el.querySelector('.hbe-giftcard-float__close');
  if (close) {
    close.addEventListener('click', function () {
      el.hidden = true;
      try { sessionStorage.setItem(KEY, '1'); } catch (e) {}
    });
  }
})();
</script>
