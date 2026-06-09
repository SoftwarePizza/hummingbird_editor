{*
 * Picture element with optional mobile + webp variants.
 * Params:
 *   - p_url       (desktop URL — required)
 *   - p_webp      (desktop WebP URL, optional)
 *   - p_mobile    (mobile URL, optional)
 *   - p_mobile_webp (mobile WebP URL, optional)
 *   - p_alt       (alt text, optional)
 *   - p_class     (CSS class, optional)
 *   - p_loading   (loading attr value, default lazy)
 *   - p_breakpoint (media query max-width, default 768)
 *}
{if !isset($p_alt)}{assign var=p_alt value=''}{/if}
{if !isset($p_class)}{assign var=p_class value=''}{/if}
{if !isset($p_loading)}{assign var=p_loading value='lazy'}{/if}
{if !isset($p_webp)}{assign var=p_webp value=''}{/if}
{if !isset($p_mobile)}{assign var=p_mobile value=''}{/if}
{if !isset($p_mobile_webp)}{assign var=p_mobile_webp value=''}{/if}
{if !isset($p_breakpoint)}{assign var=p_breakpoint value=768}{/if}
<picture>
  {if $p_mobile_webp}
    <source media="(max-width: {$p_breakpoint}px)" type="image/webp" srcset="{$p_mobile_webp|escape:'html':'UTF-8'}">
  {/if}
  {if $p_mobile}
    <source media="(max-width: {$p_breakpoint}px)" srcset="{$p_mobile|escape:'html':'UTF-8'}">
  {/if}
  {if $p_webp}
    <source type="image/webp" srcset="{$p_webp|escape:'html':'UTF-8'}">
  {/if}
  <img src="{$p_url|escape:'html':'UTF-8'}"
       alt="{$p_alt|escape:'html':'UTF-8'}"
       {if $p_class}class="{$p_class|escape:'html':'UTF-8'}"{/if}
       loading="{$p_loading|escape:'html':'UTF-8'}">
</picture>
