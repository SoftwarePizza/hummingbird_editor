<div class="hbe-cols3desc" role="region">
  <div class="container">
    <div class="hbe-cols3desc__row">
      {foreach from=$hbe_cols3desc item=col name=cols}
        <div class="hbe-cols3desc__col">
          {if $col.url}
            <a href="{$col.url|escape:'html':'UTF-8'}" class="hbe-cols3desc__link">
          {else}
            <div class="hbe-cols3desc__link">
          {/if}
          {if isset($col.img_url) && $col.img_url}
            <span class="hbe-cols3desc__media">
              {if isset($col.img_webp_url) && $col.img_webp_url}
              <picture>
                <source srcset="{$col.img_webp_url|escape:'html':'UTF-8'}" type="image/webp">
                <img class="hbe-cols3desc__img" src="{$col.img_url|escape:'html':'UTF-8'}" alt="{$col.title|escape:'html':'UTF-8'}" loading="lazy">
              </picture>
              {else}
              <img class="hbe-cols3desc__img" src="{$col.img_url|escape:'html':'UTF-8'}" alt="{$col.title|escape:'html':'UTF-8'}" loading="lazy">
              {/if}
            </span>
          {/if}
          <span class="hbe-cols3desc__body">
            {if $col.title}
              <span class="hbe-cols3desc__title">{$col.title|escape:'html':'UTF-8'}</span>
            {/if}
            {if $col.desc}
              <p class="hbe-cols3desc__desc">{$col.desc|escape:'html':'UTF-8'}</p>
            {/if}
            {if $col.url}
              <span class="hbe-cols3desc__arrow" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                  <circle cx="12" cy="12" r="10"></circle>
                  <polyline points="12 8 16 12 12 16"></polyline>
                  <line x1="8" y1="12" x2="16" y2="12"></line>
                </svg>
              </span>
            {/if}
          </span>
          {if $col.url}
            </a>
          {else}
            </div>
          {/if}
          {if !$smarty.foreach.cols.last}
            <div class="hbe-cols3desc__divider" aria-hidden="true"></div>
          {/if}
        </div>
      {/foreach}
    </div>
  </div>
</div>
