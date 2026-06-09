<div class="hbe-tagline" role="region">
  <div class="container">
    <p class="hbe-tagline__text">{$hbe_tagline_text|escape:'html':'UTF-8'}</p>
    {if $hbe_tagline_link_text}
      <a href="{if $hbe_tagline_link_url}{$hbe_tagline_link_url|escape:'html':'UTF-8'}{else}#{/if}"
         class="hbe-tagline__link">
        {$hbe_tagline_link_text|escape:'html':'UTF-8'}
      </a>
    {/if}
  </div>
</div>
