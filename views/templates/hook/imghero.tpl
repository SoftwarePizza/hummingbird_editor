<div class="hbe-imghero" role="region" aria-label="{if $hbe_imghero_title}{$hbe_imghero_title|escape:'html':'UTF-8'}{else}Baner{/if}">
  <div class="hbe-imghero__inner">
    {include file="./_picture.tpl"
      p_url=$hbe_imghero_image_url
      p_webp=$hbe_imghero_image_webp_url
      p_mobile=$hbe_imghero_image_mobile_url
      p_mobile_webp=$hbe_imghero_image_mobile_webp_url
      p_alt=($hbe_imghero_title|default:'')
      p_class='hbe-imghero__img'}
    <div class="hbe-imghero__overlay">
      {if $hbe_imghero_title}
        <h2 class="hbe-imghero__title">{$hbe_imghero_title|escape:'html':'UTF-8'}</h2>
      {/if}
      {if $hbe_imghero_desc}
        <p class="hbe-imghero__desc">{$hbe_imghero_desc|escape:'html':'UTF-8'}</p>
      {/if}
      {if $hbe_imghero_cta_text}
        <a href="{if $hbe_imghero_cta_url}{$hbe_imghero_cta_url|escape:'html':'UTF-8'}{else}#{/if}"
           class="hbe-imghero__cta btn">
          {$hbe_imghero_cta_text|escape:'html':'UTF-8'}
        </a>
      {/if}
    </div>
  </div>
</div>
