<div class="hbe-topbar" role="region" aria-label="Promocja">
  {assign var=hbe_lt value=($hbe_topbar_link_text|default:'')|trim}
  {if $hbe_topbar_url && $hbe_lt !== ''}
    <span>{$hbe_topbar_text|escape:'html':'UTF-8'}</span>
    <a href="{$hbe_topbar_url|escape:'html':'UTF-8'}">{$hbe_lt|escape:'html':'UTF-8'}</a>
  {elseif $hbe_topbar_url}
    <a href="{$hbe_topbar_url|escape:'html':'UTF-8'}">{$hbe_topbar_text|escape:'html':'UTF-8'}</a>
  {else}
    <span>{$hbe_topbar_text|escape:'html':'UTF-8'}</span>
  {/if}
</div>
