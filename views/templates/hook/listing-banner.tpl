{* Listing banner (Figma: Image Banner) — full-width photo, title + CTA bottom-left. *}
<section class="hbe-listban" role="region"
         aria-label="{if $hbe_listban_title}{$hbe_listban_title|escape:'html':'UTF-8'}{else}Baner{/if}">
  {include file="./_picture.tpl"
    p_url=$hbe_listban_image_url
    p_webp=$hbe_listban_image_webp_url
    p_mobile=$hbe_listban_image_mobile_url
    p_mobile_webp=$hbe_listban_image_mobile_webp_url
    p_alt=($hbe_listban_title|default:'')
    p_class='hbe-listban__img'}
  <div class="hbe-listban__overlay">
    {if $hbe_listban_title}
      <h2 class="hbe-listban__title">{$hbe_listban_title|escape:'html':'UTF-8'}</h2>
    {/if}
    {if $hbe_listban_cta_text}
      <a href="{if $hbe_listban_url}{$hbe_listban_url|escape:'html':'UTF-8'}{else}#{/if}"
         class="hbe-listban__cta">
        {$hbe_listban_cta_text|escape:'html':'UTF-8'}
      </a>
    {/if}
  </div>
  {if $hbe_listban_url && !$hbe_listban_cta_text}
    <a class="hbe-listban__link" href="{$hbe_listban_url|escape:'html':'UTF-8'}"
       aria-label="{$hbe_listban_title|default:'Baner'|escape:'html':'UTF-8'}"></a>
  {/if}
</section>
