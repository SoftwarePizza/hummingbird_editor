{*
 * Multi-language image upload partial — native PS translatable-field pattern.
 * Checkbox ml_images toggles single-image (all langs) vs per-language images.
 *
 * Params:
 *   name           – form field base (e.g. "image")
 *   dom_prefix     – DOM id base (e.g. "hbe-imghero-img")
 *   base_url       – current single/base image URL (may be empty)
 *   per_lang       – array id_lang => filename
 *   per_lang_urls  – array id_lang => url
 *   delete_action  – controller action name (e.g. "DeleteImgHeroImage")
 *   delete_extra   – optional extra query params string (e.g. "side=l")
 *   help           – optional help text
 *   ml             – 1/0, current ml_images flag
 *   mobile         – 1/0, render additional collapsible mobile version block
 *   mobile_base_url        – current mobile base image URL (optional)
 *   mobile_per_lang_urls   – array id_lang => url for mobile (optional)
 *}
{if !isset($ml)}{assign var=ml value=0}{/if}
{if !isset($help)}{assign var=help value=''}{/if}
{if !isset($delete_action)}{assign var=delete_action value=''}{/if}
{if !isset($delete_extra)}{assign var=delete_extra value=''}{/if}
{if !isset($mobile)}{assign var=mobile value=0}{/if}
{if !isset($mobile_base_url)}{assign var=mobile_base_url value=''}{/if}
{if !isset($mobile_per_lang_urls)}{assign var=mobile_per_lang_urls value=[]}{/if}
{assign var=hbe_has_mobile value=0}
{if $mobile_base_url !== ''}{assign var=hbe_has_mobile value=1}{/if}
{if !$hbe_has_mobile}
  {foreach from=$mobile_per_lang_urls item=mu}
    {if $mu !== ''}{assign var=hbe_has_mobile value=1}{/if}
  {/foreach}
{/if}

<div class="hbe-img-block" data-hbe-img-block>

  {* ── Single (base) image — visible when ml_images unchecked ─── *}
  <div class="hbe-base-img" {if $ml}style="display:none"{/if}>
    {if $base_url}
      <div id="{$dom_prefix}-wrap" style="margin-bottom:0.5rem">
        <img id="{$dom_prefix}-preview" src="{$base_url|escape:'html':'UTF-8'}"
             style="max-width:100%;max-height:200px;border:1px solid #ddd;border-radius:3px;display:block;margin-bottom:0.5rem">
        {if $delete_action}
          <button type="button" class="btn btn-xs btn-danger hbe-img-del"
                  data-action="{$delete_action}" data-lang="0" data-variant="desktop" data-extra="{$delete_extra|escape:'html':'UTF-8'}">
            <i class="icon-trash"></i> {l s='Usuń zdjęcie' mod='hummingbird_editor'}
          </button>
        {/if}
      </div>
    {else}
      <div id="{$dom_prefix}-wrap" style="margin-bottom:0.5rem;display:none">
        <img id="{$dom_prefix}-preview" src=""
             style="max-width:100%;max-height:200px;border:1px solid #ddd;border-radius:3px;display:block;margin-bottom:0.5rem">
        {if $delete_action}
          <button type="button" class="btn btn-xs btn-danger hbe-img-del"
                  data-action="{$delete_action}" data-lang="0" data-variant="desktop" data-extra="{$delete_extra|escape:'html':'UTF-8'}">
            <i class="icon-trash"></i> {l s='Usuń zdjęcie' mod='hummingbird_editor'}
          </button>
        {/if}
      </div>
    {/if}
    <input type="file" name="{$name}" accept="image/*" class="form-control">
    {if $help}<p class="help-block">{$help}</p>{/if}
  </div>

  {* ── Per-language images — visible when ml_images checked ─── *}
  <div class="hbe-ml-imgs" {if !$ml}style="display:none"{/if}>
    {foreach from=$hbe_languages item=lang}
      {assign var=lid value=(int)$lang.id_lang}
      {if isset($per_lang_urls[$lid])}{assign var=lurl value=$per_lang_urls[$lid]}{else}{assign var=lurl value=''}{/if}
      <div class="translatable-field lang-{$lid}"
           style="{if $lid != $hbe_lang_id}display:none;{/if}padding:0.5rem;border:1px solid #eee;border-radius:3px;margin-bottom:0.5rem">
        {if $hbe_languages|count > 1}
        <div style="margin-bottom:0.5rem">
          <div class="btn-group">
            <button type="button" class="btn btn-default btn-xs dropdown-toggle" tabindex="-1"
                    data-toggle="dropdown">{$lang.iso_code|upper}&nbsp;<span class="caret"></span></button>
            <ul class="dropdown-menu">
              {foreach from=$hbe_languages item=l2}
              <li><a href="javascript:hideOtherLanguage({$l2.id_lang});" tabindex="-1">{$l2.name|escape:'html':'UTF-8'}</a></li>
              {/foreach}
            </ul>
          </div>
        </div>
        {/if}
        {if $lurl}
          <div style="margin-bottom:0.4rem">
            <img src="{$lurl|escape:'html':'UTF-8'}"
                 style="max-width:100%;max-height:160px;border:1px solid #ddd;border-radius:3px;display:block;margin-bottom:0.4rem">
            {if $delete_action}
              <button type="button" class="btn btn-xs btn-danger hbe-img-del"
                      data-action="{$delete_action}" data-lang="{$lid}" data-variant="desktop" data-extra="{$delete_extra|escape:'html':'UTF-8'}">
                <i class="icon-trash"></i> {l s='Usuń zdjęcie' mod='hummingbird_editor'}
              </button>
            {/if}
          </div>
        {/if}
        <input type="file" name="{$name}_lang_{$lid}" accept="image/*" class="form-control">
      </div>
    {/foreach}
    {if $help}<p class="help-block">{$help}</p>{/if}
  </div>

  {if $mobile}
    {* ── Mobile version — collapsible ─── *}
    <div class="hbe-mobile-img-toggle" style="margin-top:0.75rem">
      <label style="font-weight:500;cursor:pointer">
        <input type="checkbox" class="hbe-mobile-toggle" {if $hbe_has_mobile}checked{/if}>
        {l s='Dodaj osobną wersję mobile (opcjonalnie)' mod='hummingbird_editor'}
      </label>
    </div>
    <div class="hbe-mobile-img-block" {if !$hbe_has_mobile}style="display:none"{/if}
         style="margin-top:0.5rem;padding:0.75rem;border:1px dashed #c8c8c8;border-radius:4px;background:#fafafa">
      <p class="text-muted" style="margin-bottom:0.5rem;font-size:0.85rem">
        {l s='Jeśli zostanie dodane, wyświetli się na ekranach < 768px zamiast wersji desktop.' mod='hummingbird_editor'}
      </p>

      {* Mobile single (base) — visible when ml=0 *}
      <div class="hbe-base-img" {if $ml}style="display:none"{/if}>
        {if $mobile_base_url}
          <div id="{$dom_prefix}-mobile-wrap" style="margin-bottom:0.5rem">
            <img id="{$dom_prefix}-mobile-preview" src="{$mobile_base_url|escape:'html':'UTF-8'}"
                 style="max-width:100%;max-height:200px;border:1px solid #ddd;border-radius:3px;display:block;margin-bottom:0.5rem">
            {if $delete_action}
              <button type="button" class="btn btn-xs btn-danger hbe-img-del"
                      data-action="{$delete_action}" data-lang="0" data-variant="mobile" data-extra="{$delete_extra|escape:'html':'UTF-8'}">
                <i class="icon-trash"></i> {l s='Usuń zdjęcie mobile' mod='hummingbird_editor'}
              </button>
            {/if}
          </div>
        {else}
          <div id="{$dom_prefix}-mobile-wrap" style="margin-bottom:0.5rem;display:none">
            <img id="{$dom_prefix}-mobile-preview" src=""
                 style="max-width:100%;max-height:200px;border:1px solid #ddd;border-radius:3px;display:block;margin-bottom:0.5rem">
            {if $delete_action}
              <button type="button" class="btn btn-xs btn-danger hbe-img-del"
                      data-action="{$delete_action}" data-lang="0" data-variant="mobile" data-extra="{$delete_extra|escape:'html':'UTF-8'}">
                <i class="icon-trash"></i> {l s='Usuń zdjęcie mobile' mod='hummingbird_editor'}
              </button>
            {/if}
          </div>
        {/if}
        <input type="file" name="{$name}_mobile" accept="image/*" class="form-control">
      </div>

      {* Mobile per-language — visible when ml=1 *}
      <div class="hbe-ml-imgs" {if !$ml}style="display:none"{/if}>
        {foreach from=$hbe_languages item=lang}
          {assign var=lid value=(int)$lang.id_lang}
          {if isset($mobile_per_lang_urls[$lid])}{assign var=lmurl value=$mobile_per_lang_urls[$lid]}{else}{assign var=lmurl value=''}{/if}
          <div class="translatable-field lang-{$lid}"
               style="{if $lid != $hbe_lang_id}display:none;{/if}padding:0.5rem;border:1px solid #eee;border-radius:3px;margin-bottom:0.5rem;background:#fff">
            {if $hbe_languages|count > 1}
            <div style="margin-bottom:0.5rem">
              <div class="btn-group">
                <button type="button" class="btn btn-default btn-xs dropdown-toggle" tabindex="-1"
                        data-toggle="dropdown">{$lang.iso_code|upper}&nbsp;(mobile)&nbsp;<span class="caret"></span></button>
                <ul class="dropdown-menu">
                  {foreach from=$hbe_languages item=l2}
                  <li><a href="javascript:hideOtherLanguage({$l2.id_lang});" tabindex="-1">{$l2.name|escape:'html':'UTF-8'}</a></li>
                  {/foreach}
                </ul>
              </div>
            </div>
            {/if}
            {if $lmurl}
              <div style="margin-bottom:0.4rem">
                <img src="{$lmurl|escape:'html':'UTF-8'}"
                     style="max-width:100%;max-height:160px;border:1px solid #ddd;border-radius:3px;display:block;margin-bottom:0.4rem">
                {if $delete_action}
                  <button type="button" class="btn btn-xs btn-danger hbe-img-del"
                          data-action="{$delete_action}" data-lang="{$lid}" data-variant="mobile" data-extra="{$delete_extra|escape:'html':'UTF-8'}">
                    <i class="icon-trash"></i> {l s='Usuń zdjęcie mobile' mod='hummingbird_editor'}
                  </button>
                {/if}
              </div>
            {/if}
            <input type="file" name="{$name}_mobile_lang_{$lid}" accept="image/*" class="form-control">
          </div>
        {/foreach}
      </div>
    </div>
  {/if}

</div>
