{* "Inne sklepy online" — editorial closing section: up to 3 sister shops,
   each with a 3-image mosaic gallery, serif name, description and CTA link. *}
<section class="hbe-shops" aria-labelledby="hbe-shops-title">
  <div class="container">

    {if $hbe_shops_eyebrow || $hbe_shops_title || $hbe_shops_text}
    <header class="hbe-shops__header">
      {if $hbe_shops_eyebrow}
        <p class="hbe-shops__eyebrow">{$hbe_shops_eyebrow|escape:'html':'UTF-8'}</p>
      {/if}
      {if $hbe_shops_title}
        <h2 id="hbe-shops-title" class="hbe-shops__title">{$hbe_shops_title|escape:'html':'UTF-8'}</h2>
      {/if}
      {if $hbe_shops_text}
        <p class="hbe-shops__lead">{$hbe_shops_text|escape:'html':'UTF-8'}</p>
      {/if}
    </header>
    {/if}

    <ul class="hbe-shops__grid">
      {foreach from=$hbe_shops item=shop}
      <li class="hbe-shops__card">

        {if $shop.images}
          {* The gallery duplicates the name link, so keep it out of the tab
             order and hide it from screen readers — images are decorative. *}
          {if $shop.url}
          <a href="{$shop.url|escape:'html':'UTF-8'}" class="hbe-shops__gallery hbe-shops__gallery--n{$shop.images|count}"
             tabindex="-1" aria-hidden="true"{if $shop.external} target="_blank" rel="noopener"{/if}>
          {else}
          <span class="hbe-shops__gallery hbe-shops__gallery--n{$shop.images|count}" aria-hidden="true">
          {/if}
            {foreach from=$shop.images item=img name=gal}
              <span class="hbe-shops__tile hbe-shops__tile--{$smarty.foreach.gal.index}">
                {include file="./_picture.tpl"
                  p_url=$img.url
                  p_webp=$img.webp_url
                  p_alt=''
                  p_class='hbe-shops__img'}
              </span>
            {/foreach}
          {if $shop.url}</a>{else}</span>{/if}
        {/if}

        <div class="hbe-shops__body">
          {if $shop.name}
            <h3 class="hbe-shops__name">
              {if $shop.url}
                <a href="{$shop.url|escape:'html':'UTF-8'}"{if $shop.external} target="_blank" rel="noopener"{/if}>{$shop.name|escape:'html':'UTF-8'}</a>
              {else}
                {$shop.name|escape:'html':'UTF-8'}
              {/if}
            </h3>
          {/if}
          {if $shop.desc}
            <p class="hbe-shops__desc">{$shop.desc|escape:'html':'UTF-8'}</p>
          {/if}
          {if $shop.url && $hbe_shops_cta}
            <a href="{$shop.url|escape:'html':'UTF-8'}" class="hbe-shops__link"
               {if $shop.external}target="_blank" rel="noopener"{/if}
               aria-label="{$hbe_shops_cta|escape:'html':'UTF-8'} — {$shop.name|escape:'html':'UTF-8'}">
              {$hbe_shops_cta|escape:'html':'UTF-8'}
              <svg class="hbe-shops__link-arrow" width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
                <path d="M3 11L11 3M11 3H4.5M11 3V9.5" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </a>
          {/if}
        </div>

      </li>
      {/foreach}
    </ul>

  </div>{* /container *}
</section>
