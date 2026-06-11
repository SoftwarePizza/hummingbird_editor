{* Image + text split section (Figma: Image with Text) — product page, below description. *}
<section class="hbe-imgtext" role="region"
         aria-label="{if $hbe_imgtext_title}{$hbe_imgtext_title|escape:'html':'UTF-8'}{else}Sekcja{/if}">
  <div class="hbe-imgtext__panel" style="background:{$hbe_imgtext_bg|escape:'html':'UTF-8'}">
    {if $hbe_imgtext_title}
      <h2 class="hbe-imgtext__title">{$hbe_imgtext_title|escape:'html':'UTF-8'}</h2>
    {/if}
    {if $hbe_imgtext_desc}
      <p class="hbe-imgtext__desc">{$hbe_imgtext_desc|escape:'html':'UTF-8'}</p>
    {/if}
    {if $hbe_imgtext_cta_text}
      <a href="{if $hbe_imgtext_cta_url}{$hbe_imgtext_cta_url|escape:'html':'UTF-8'}{else}#{/if}"
         class="hbe-imgtext__cta">
        {$hbe_imgtext_cta_text|escape:'html':'UTF-8'}
      </a>
    {/if}
  </div>
  <div class="hbe-imgtext__media">
    {include file="./_picture.tpl"
      p_url=$hbe_imgtext_image_url
      p_webp=$hbe_imgtext_image_webp_url
      p_mobile=$hbe_imgtext_image_mobile_url
      p_mobile_webp=$hbe_imgtext_image_mobile_webp_url
      p_alt=($hbe_imgtext_title|default:'')
      p_class='hbe-imgtext__img'}
  </div>
</section>
