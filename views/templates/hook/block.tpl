{**
 * Hummingbird Editor — Frontend block renderer
 * Handles types: text, image, html
 * Responsive: CSS-based desktop/mobile switch + <picture> for images
 **}

{assign var=type value=$hbe_block.type}
{assign var=mobileDiff value=$hbe_block.mobile_different}
{assign var=contentD value=$hbe_block.content_desktop|default:''}
{assign var=contentM value=$hbe_block.content_mobile|default:''}
{assign var=linkD value=$hbe_block.link_desktop|default:''}
{assign var=linkM value=$hbe_block.link_mobile|default:''}
{assign var=imgD value=$hbe_block.image_desktop_url|default:''}
{assign var=imgM value=$hbe_block.image_mobile_url|default:''}
{assign var=imgDWebp value=$hbe_block.image_desktop_webp_url|default:''}
{assign var=imgMWebp value=$hbe_block.image_mobile_webp_url|default:''}

{if $type == 'image' && !$imgD && !$imgM}{* nothing to render *}
{elseif $type != 'image' && !$contentD && !$contentM}{* nothing to render *}
{else}
<div class="hbe-block hbe-type-{$type}"
     data-hook="{$hbe_block.hook_name|escape:'html':'UTF-8'}"
     data-id="{$hbe_block.id_block}">

  {if $type == 'image'}
    {* ── Image block ─────────────────────────────────────────────────── *}
    {if $mobileDiff && ($imgD || $imgM)}
      {* Picture element: browser chooses mobile src on small screens *}
      {assign var=wrapLink value=$linkD}
      {if $linkD || $linkM}<a href="{if $linkD}{$linkD|escape:'html':'UTF-8'}{else}#{/if}"
                              class="hbe-img-link hbe-desktop-link">{/if}
      <picture class="hbe-picture">
        {if $imgMWebp}<source media="(max-width: 767px)" type="image/webp" srcset="{$imgMWebp|escape:'html':'UTF-8'}">{/if}
        {if $imgM}<source media="(max-width: 767px)" srcset="{$imgM|escape:'html':'UTF-8'}">{/if}
        {if $imgDWebp}<source type="image/webp" srcset="{$imgDWebp|escape:'html':'UTF-8'}">{/if}
        {if $imgD}<img src="{$imgD|escape:'html':'UTF-8'}"
                       alt="{$contentD|escape:'html':'UTF-8'}"
                       class="hbe-img img-responsive" loading="lazy">{/if}
      </picture>
      {if $linkD || $linkM}</a>{/if}
    {else}
      {* Single image — desktop only *}
      {if $linkD}<a href="{$linkD|escape:'html':'UTF-8'}" class="hbe-img-link">{/if}
      <picture class="hbe-picture">
        {if $imgDWebp}<source type="image/webp" srcset="{$imgDWebp|escape:'html':'UTF-8'}">{/if}
        <img src="{$imgD|escape:'html':'UTF-8'}"
             alt="{$contentD|escape:'html':'UTF-8'}"
             class="hbe-img img-responsive" loading="lazy">
      </picture>
      {if $linkD}</a>{/if}
    {/if}

  {elseif $type == 'html'}
    {* ── Raw HTML block ──────────────────────────────────────────────── *}
    {if $mobileDiff}
    <div class="hbe-content hbe-content--desktop">{$contentD nofilter}</div>
    <div class="hbe-content hbe-content--mobile">{$contentM nofilter}</div>
    {else}
    <div class="hbe-content">{$contentD nofilter}</div>
    {/if}

  {else}
    {* ── Text / WYSIWYG block ────────────────────────────────────────── *}
    {if $mobileDiff}
    <div class="hbe-content hbe-content--desktop">
      {if $linkD}<a href="{$linkD|escape:'html':'UTF-8'}" class="hbe-link">{/if}
      {$contentD nofilter}
      {if $linkD}</a>{/if}
    </div>
    <div class="hbe-content hbe-content--mobile">
      {if $linkM}<a href="{$linkM|escape:'html':'UTF-8'}" class="hbe-link">{/if}
      {$contentM nofilter}
      {if $linkM}</a>{/if}
    </div>
    {else}
    <div class="hbe-content">
      {if $linkD}<a href="{$linkD|escape:'html':'UTF-8'}" class="hbe-link">{/if}
      {$contentD nofilter}
      {if $linkD}</a>{/if}
    </div>
    {/if}

  {/if}

</div>{* /hbe-block *}
{/if}
