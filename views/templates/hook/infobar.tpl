<div class="hbe-infobar" role="region" aria-label="Informacja"
     style="background:{$hbe_infobar_bg|escape:'html':'UTF-8'};color:{$hbe_infobar_color|escape:'html':'UTF-8'}">
  {assign var=hbe_lt value=($hbe_infobar_link_text|default:'')|trim}
  {if $hbe_infobar_url && $hbe_lt !== ''}
    <span>{$hbe_infobar_text|escape:'html':'UTF-8'}</span>
    <a href="{$hbe_infobar_url|escape:'html':'UTF-8'}" style="color:inherit">{$hbe_lt|escape:'html':'UTF-8'}</a>
  {elseif $hbe_infobar_url}
    <a href="{$hbe_infobar_url|escape:'html':'UTF-8'}" style="color:inherit">{$hbe_infobar_text|escape:'html':'UTF-8'}</a>
  {else}
    <span>{$hbe_infobar_text|escape:'html':'UTF-8'}</span>
  {/if}
</div>
