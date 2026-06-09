{**
 * Brands logo strip section
 * Variables: $hbe_brands_title, $hbe_brands (array of {img_url, img_webp_url, link, alt})
 *}
{if $hbe_brands|count}
<section class="hbe-brands">
  <div class="container">
    {if $hbe_brands_title}
      <h2 class="hbe-brands__title">{$hbe_brands_title|escape:'html':'UTF-8'}</h2>
    {/if}
    <div class="hbe-brands__track">
      {foreach from=$hbe_brands item=brand}
        {if $brand.img_url}
          {if $brand.link}
            <a class="hbe-brands__item" href="{$brand.link|escape:'html':'UTF-8'}" rel="noopener noreferrer">
          {else}
            <div class="hbe-brands__item">
          {/if}
            <picture>
              {if $brand.img_webp_url}
                <source srcset="{$brand.img_webp_url|escape:'html':'UTF-8'}" type="image/webp">
              {/if}
              <img src="{$brand.img_url|escape:'html':'UTF-8'}"
                   alt="{$brand.alt|default:''|escape:'html':'UTF-8'}"
                   loading="lazy"
                   width="160"
                   height="80">
            </picture>
          {if $brand.link}</a>{else}</div>{/if}
        {/if}
      {/foreach}
    </div>
  </div>
</section>
{/if}
