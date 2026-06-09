<div class="hbe-cols3" role="region">
  <div class="container">
    <div class="hbe-cols3__row">
      {foreach from=$hbe_cols3 item=col name=cols}
        <div class="hbe-cols3__col">
          {if $col.url}
            <a href="{$col.url|escape:'html':'UTF-8'}" class="hbe-cols3__link">
          {/if}
          <span class="hbe-cols3__text">{$col.text|escape:'html':'UTF-8'}</span>
          <span class="hbe-cols3__arrow" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
              <circle cx="12" cy="12" r="10"></circle>
              <polyline points="12 8 16 12 12 16"></polyline>
              <line x1="8" y1="12" x2="16" y2="12"></line>
            </svg>
          </span>
          {if $col.url}
            </a>
          {/if}
          {if !$smarty.foreach.cols.last}
            <div class="hbe-cols3__divider" aria-hidden="true"></div>
          {/if}
        </div>
      {/foreach}
    </div>
  </div>
</div>
