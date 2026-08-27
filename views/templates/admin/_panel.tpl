{*
 * Zakładka sklepu — bloki z themes/<motyw>/hbe/panel.json (AdminHbEditorController::panelCfg).
 * Wymaga: $hbe_panel, $hbe_languages, $hbe_lang_id, $hbe_tpl_dir, $hbe_token, $hbe_ajax_url.
 * Formularze podpinają się pod istniejące akcje: SaveHeroQuick, SaveTopBar, SaveSectionBlock/
 * SaveThemeSection (.hbe-section-edit-form), SaveThemeFields (.hbe-theme-fields-form), SaveSocial.
 *}
<div class="hbe-panel">
  {if $hbe_panel.intro}<p class="text-muted" style="margin-bottom:1rem">{$hbe_panel.intro|escape:'html':'UTF-8'}</p>{/if}

  {foreach from=$hbe_panel.blocks item=pb name=pbl}
  <div class="panel panel-default hbe-collapse-panel hbe-panel-block hbe-panel-block--{$pb.type|escape:'html':'UTF-8'}">
    <div class="panel-heading hbe-cp-head" data-toggle="collapse" data-target="#hbe-pb-{$smarty.foreach.pbl.index}">
      <h4 class="panel-title clearfix">
        {$pb.label|escape:'html':'UTF-8'}
        <span class="pull-right"><i class="icon-chevron-down hbe-chevron hbe-chevron-open"></i></span>
      </h4>
    </div>
    <div id="hbe-pb-{$smarty.foreach.pbl.index}" class="panel-collapse collapse in">
      <div class="panel-body">
        {if $pb.help}<p class="text-muted" style="margin-bottom:1rem">{$pb.help|escape:'html':'UTF-8'}</p>{/if}

        {* ── Hero: pierwszy slajd ─────────────────────────────────────── *}
        {if $pb.type == 'hero'}
          {if $pb.slide}
          <form class="hbe-quick-form" data-action="SaveHeroQuick" method="post" action="{$hbe_ajax_url nofilter}" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" name="token" value="{$hbe_token}">
            <input type="hidden" name="id_slide" value="{$pb.slide.id|intval}">
            <div class="row">
              <div class="col-md-2 form-group">
                <label class="control-label">{l s='Włączony' mod='hummingbird_editor'}</label>
                <div class="checkbox"><label><input type="checkbox" name="active" value="1" {if $pb.slide.active}checked{/if}> {l s='Yes' mod='hummingbird_editor'}</label></div>
              </div>
              <div class="col-md-5 form-group">
                <label class="control-label">{l s='Tytuł' mod='hummingbird_editor'}</label>
                {include file="{$hbe_tpl_dir}_ml_input.tpl" name='title' values=$pb.slide.title type='text' placeholder=''}
              </div>
              <div class="col-md-5 form-group">
                <label class="control-label">{l s='Adres po kliknięciu w cały slajd (zwykle puste)' mod='hummingbird_editor'}</label>
                {include file="{$hbe_tpl_dir}_ml_input.tpl" name='url' values=$pb.slide.url type='text' placeholder=''}
                <p class="help-block">{l s='Zostaw puste, jeśli w tekście pod tytułem są przyciski — z adresem cały slajd staje się linkiem i przyciski przestają działać.' mod='hummingbird_editor'}</p>
              </div>
            </div>
            <div class="row">
              <div class="col-md-8 form-group">
                <label class="control-label">{l s='Tekst pod tytułem (HTML — akapity, przyciski)' mod='hummingbird_editor'}</label>
                {include file="{$hbe_tpl_dir}_ml_input.tpl" name='description' values=$pb.slide.description type='textarea' rows=7 placeholder=''}
              </div>
              <div class="col-md-4 form-group">
                <label class="control-label">{l s='Tekst przycisku slajdu' mod='hummingbird_editor'}</label>
                <input type="text" name="cta_text" class="form-control" value="{$pb.slide.cta_text|escape:'html':'UTF-8'}" maxlength="100">
                <p class="help-block">{l s='Używany tylko, gdy przycisk slajdu jest włączony w zakładce Strona główna → Slider.' mod='hummingbird_editor'}</p>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 form-group hbe-tf-image">
                <label class="control-label">{l s='Zdjęcie — komputer' mod='hummingbird_editor'}</label>
                <div class="hbe-tf-preview"{if !$pb.slide.image_url} style="display:none"{/if}>
                  <img src="{$pb.slide.image_url|escape:'html':'UTF-8'}" data-hbe-quick-img="image" alt="" style="max-width:100%;max-height:180px;border:1px solid #ddd;border-radius:3px">
                </div>
                <input type="file" name="image_{$hbe_lang_id|intval}" accept="image/*" class="form-control">
                <input type="hidden" name="image_old_{$hbe_lang_id|intval}" value="{$pb.slide.image|escape:'html':'UTF-8'}">
                <p class="help-block">{l s='JPG/WebP, ok. 1920×900 px. Jedno zdjęcie dla wszystkich języków.' mod='hummingbird_editor'}</p>
              </div>
              <div class="col-md-6 form-group hbe-tf-image">
                <label class="control-label">{l s='Zdjęcie — telefon (opcjonalne, kadr pionowy)' mod='hummingbird_editor'}</label>
                <div class="hbe-tf-preview"{if !$pb.slide.image_mobile_url} style="display:none"{/if}>
                  <img src="{$pb.slide.image_mobile_url|escape:'html':'UTF-8'}" data-hbe-quick-img="image_mobile" alt="" style="max-width:100%;max-height:180px;border:1px solid #ddd;border-radius:3px">
                </div>
                <input type="file" name="image_mobile_{$hbe_lang_id|intval}" accept="image/*" class="form-control">
                <input type="hidden" name="image_mobile_old_{$hbe_lang_id|intval}" value="{$pb.slide.image_mobile|escape:'html':'UTF-8'}">
              </div>
            </div>
            <div class="form-group hbe-save-row">
              <button type="submit" class="btn btn-success"><i class="icon-save"></i> {l s='Zapisz' mod='hummingbird_editor'}</button>
              <a href="#hbe-tab-home" data-hbe-goto-tab="#hbe-tab-home" class="btn btn-link">{l s='Więcej slajdów i ustawienia slidera →' mod='hummingbird_editor'}</a>
            </div>
            <div class="hbe-alerts"></div>
          </form>
          {else}
          <p class="text-muted">{l s='Brak slajdów — dodaj pierwszy w zakładce Strona główna → Slider.' mod='hummingbird_editor'}</p>
          {/if}

        {* ── Pasek nad nagłówkiem ─────────────────────────────────────── *}
        {elseif $pb.type == 'topbar'}
          <form class="hbe-quick-form" data-action="SaveTopBar" method="post" action="{$hbe_ajax_url nofilter}" autocomplete="off">
            <input type="hidden" name="token" value="{$hbe_token}">
            <div class="row">
              <div class="col-md-2 form-group">
                <label class="control-label">{l s='Włącz' mod='hummingbird_editor'}</label>
                <div class="checkbox"><label><input type="checkbox" name="enabled" value="1" {if $hbe_topbar_enabled}checked{/if}> {l s='Yes' mod='hummingbird_editor'}</label></div>
              </div>
              <div class="col-md-5 form-group">
                <label class="control-label">{l s='Tekst' mod='hummingbird_editor'}</label>
                {include file="{$hbe_tpl_dir}_ml_input.tpl" name='text' values=$hbe_topbar_text_lang type='text' placeholder=''}
              </div>
              <div class="col-md-2 form-group">
                <label class="control-label">{l s='Tekst linku' mod='hummingbird_editor'}</label>
                {include file="{$hbe_tpl_dir}_ml_input.tpl" name='link_text' values=$hbe_topbar_link_text_lang type='text' placeholder=''}
              </div>
              <div class="col-md-3 form-group">
                <label class="control-label">{l s='Adres linku' mod='hummingbird_editor'}</label>
                {include file="{$hbe_tpl_dir}_ml_input.tpl" name='url' values=$hbe_topbar_url_lang type='text' placeholder='/content/1-dostawa-i-platnosc'}
              </div>
            </div>
            <div class="form-group hbe-save-row">
              <button type="submit" class="btn btn-success"><i class="icon-save"></i> {l s='Zapisz' mod='hummingbird_editor'}</button>
            </div>
            <div class="hbe-alerts"></div>
          </form>

        {* ── Sekcje motywu w hooku ────────────────────────────────────── *}
        {elseif $pb.type == 'sections'}
          {if $pb.blocks}
            {foreach from=$pb.blocks item=block}
            <div class="hbe-edit-panel hbe-quick-section" id="hbe-quick-panel-{$block.id_block|intval}">
              <div class="hbe-quick-section__title">
                <i class="icon-magic"></i> {if isset($block.theme_cfg) && $block.theme_cfg}{$block.theme_cfg.label|escape:'html':'UTF-8'}{else}#{$block.id_block|intval}{/if}
                {if !$block.active}<span class="label label-warning" style="margin-left:6px">{l s='wyłączona' mod='hummingbird_editor'}</span>{/if}
              </div>
              {if isset($block.theme_cfg) && $block.theme_cfg}
              <form class="hbe-section-edit-form" data-id="{$block.id_block|intval}" data-section-type="theme" method="post" action="{$hbe_ajax_url nofilter}" enctype="multipart/form-data" autocomplete="off">
                <input type="hidden" name="token" value="{$hbe_token}">
                <input type="hidden" name="id_block" value="{$block.id_block|intval}">
                <input type="hidden" name="active" value="{if $block.active}1{else}0{/if}">
                {if $block.theme_cfg.help}<p class="text-muted" style="margin-bottom:.75rem">{$block.theme_cfg.help|escape:'html':'UTF-8'}</p>{/if}
                <div class="row">
                  {include file="{$hbe_tpl_dir}_theme_fields.tpl" nodes=$block.theme_cfg.tree}
                </div>
                <div class="form-group hbe-save-row">
                  <button type="submit" class="btn btn-success"><i class="icon-save"></i> {l s='Zapisz' mod='hummingbird_editor'}</button>
                  <span class="hbe-save-status text-success" style="display:none;margin-left:8px"><i class="icon-check"></i> {l s='Saved' mod='hummingbird_editor'}</span>
                </div>
                <div class="hbe-alerts"></div>
              </form>
              {else}
              <p class="text-muted">{l s='Brak schematu tej sekcji w motywie — edytuj ją w zakładce Strona główna.' mod='hummingbird_editor'}</p>
              {/if}
            </div>
            {/foreach}
          {else}
          <p class="text-muted">{l s='Brak sekcji motywu w tym miejscu.' mod='hummingbird_editor'}</p>
          {/if}

        {* ── Karuzela produktów (blok products) ───────────────────────── *}
        {elseif $pb.type == 'products'}
          {if $pb.block}
          <div class="hbe-edit-panel hbe-quick-section" id="hbe-quick-panel-{$pb.block.id_block|intval}">
            <form class="hbe-section-edit-form" data-id="{$pb.block.id_block|intval}" data-section-type="products" method="post" action="{$hbe_ajax_url nofilter}" autocomplete="off">
              <input type="hidden" name="token" value="{$hbe_token}">
              <input type="hidden" name="id_block" value="{$pb.block.id_block|intval}">
              <input type="hidden" name="active" value="{if $pb.block.active}1{else}0{/if}">
              <input type="hidden" name="pc_original" value="{$pb.block.section_data|default:'{}'|escape:'html':'UTF-8'}">
              <input type="hidden" name="pc_id_category" value="{$pb.block.products_cfg.id_category|intval}">
              <input type="hidden" name="pc_number" value="{$pb.block.products_cfg.number|intval}">
              {* admin.js czyta te pola przez :checked — ukryte checkboxy zachowują ustawienia z zakładki Strona główna *}
              <input type="checkbox" name="pc_randomized" value="1" {if $pb.block.products_cfg.randomized}checked{/if} style="display:none" tabindex="-1" aria-hidden="true">
              <input type="checkbox" name="pc_exclude_previous" value="1" {if $pb.block.products_cfg.exclude_previous}checked{/if} style="display:none" tabindex="-1" aria-hidden="true">
              <div class="row">
                <div class="col-md-5 form-group">
                  <label class="control-label">{l s='Tytuł karuzeli' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='pc_title' values=$pb.block.products_cfg.title type='text' placeholder=''}
                </div>
                <div class="col-md-7 form-group">
                  <label class="control-label">{l s='Nadtytuł / podpis' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='pc_text' values=$pb.block.products_cfg.text type='textarea' rows=2 placeholder=''}
                </div>
              </div>
              <div class="form-group hbe-save-row">
                <button type="submit" class="btn btn-success"><i class="icon-save"></i> {l s='Zapisz' mod='hummingbird_editor'}</button>
                <span class="hbe-save-status text-success" style="display:none;margin-left:8px"><i class="icon-check"></i> {l s='Saved' mod='hummingbird_editor'}</span>
                <a href="#hbe-tab-home" data-hbe-goto-tab="#hbe-tab-home" class="btn btn-link">{l s='Kategoria źródłowa i liczba produktów →' mod='hummingbird_editor'}</a>
              </div>
              <div class="hbe-alerts"></div>
            </form>
          </div>
          {else}
          <p class="text-muted">{l s='Nie znaleziono bloku karuzeli.' mod='hummingbird_editor'}</p>
          {/if}

        {* ── Pola globalne motywu (wybrane grupy) ─────────────────────── *}
        {elseif $pb.type == 'fields'}
          {if $pb.groups}
          <form class="hbe-theme-fields-form" method="post" action="{$hbe_ajax_url nofilter}" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" name="token" value="{$hbe_token}">
            {foreach from=$pb.groups item=tfg}
            <div class="hbe-quick-section">
              <div class="hbe-quick-section__title">{$tfg.label|escape:'html':'UTF-8'}</div>
              {if $tfg.help}<p class="text-muted" style="margin-bottom:.75rem">{$tfg.help|escape:'html':'UTF-8'}</p>{/if}
              <div class="row">
                {include file="{$hbe_tpl_dir}_theme_fields.tpl" nodes=$tfg.tree}
              </div>
            </div>
            {/foreach}
            <div class="form-group hbe-save-row">
              <button type="submit" class="btn btn-success"><i class="icon-save"></i> {l s='Zapisz' mod='hummingbird_editor'}</button>
              <span class="hbe-save-status text-success" style="display:none;margin-left:8px"><i class="icon-check"></i> {l s='Saved' mod='hummingbird_editor'}</span>
            </div>
            <div class="hbe-alerts"></div>
          </form>
          {else}
          <p class="text-muted">{l s='Motyw nie definiuje tych pól (hbe/fields.json).' mod='hummingbird_editor'}</p>
          {/if}

        {* ── Social ───────────────────────────────────────────────────── *}
        {elseif $pb.type == 'social'}
          <form class="hbe-quick-form" data-action="SaveSocial" method="post" action="{$hbe_ajax_url nofilter}" autocomplete="off">
            <input type="hidden" name="token" value="{$hbe_token}">
            <div class="row">
              {foreach from=$pb.social key=sKey item=sNet}
              <div class="col-md-4 form-group">
                <label class="control-label">{$sNet.label|escape:'html':'UTF-8'}</label>
                <input type="url" class="form-control" name="social_{$sKey|escape:'html':'UTF-8'}" value="{$sNet.url|escape:'html':'UTF-8'}" placeholder="https://">
              </div>
              {/foreach}
            </div>
            <p class="help-block">{l s='Puste pole = ikona nie pokazuje się w stopce.' mod='hummingbird_editor'}</p>
            <div class="form-group hbe-save-row">
              <button type="submit" class="btn btn-success"><i class="icon-save"></i> {l s='Zapisz' mod='hummingbird_editor'}</button>
            </div>
            <div class="hbe-alerts"></div>
          </form>

        {* ── Okładki stron CMS ────────────────────────────────────────── *}
        {elseif $pb.type == 'covers'}
          <form class="hbe-theme-fields-form" method="post" action="{$hbe_ajax_url nofilter}" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" name="token" value="{$hbe_token}">
            <div class="row">
              {foreach from=$pb.covers item=cover}
              <div class="col-md-3 form-group">
                <label class="control-label">
                  <span class="label label-{if $cover.kind == 'category'}primary{else}default{/if}">{if $cover.kind == 'category'}{l s='dział' mod='hummingbird_editor'}{else}{l s='strona' mod='hummingbird_editor'}{/if}</span>
                  {$cover.name|escape:'html':'UTF-8'}
                </label>
                <div class="hbe-tf-preview"{if !$cover.url} style="display:none"{/if}>
                  <img src="{$cover.url|escape:'html':'UTF-8'}" data-hbe-cover-img="{$cover.input}" alt="" style="max-width:100%;max-height:110px;border:1px solid #ddd;border-radius:3px">
                </div>
                {if $cover.rewrite}<input type="file" name="{$cover.input}" accept="image/jpeg,image/png,image/webp" class="form-control">{/if}
              </div>
              {/foreach}
            </div>
            <div class="form-group hbe-save-row">
              <button type="submit" class="btn btn-success"><i class="icon-save"></i> {l s='Zapisz okładki' mod='hummingbird_editor'}</button>
              <span class="hbe-save-status text-success" style="display:none;margin-left:8px"><i class="icon-check"></i> {l s='Saved' mod='hummingbird_editor'}</span>
            </div>
            <div class="hbe-alerts"></div>
          </form>

        {* ── Skróty do PrestaShopa ────────────────────────────────────── *}
        {elseif $pb.type == 'links'}
          <div class="hbe-panel-links">
            {foreach from=$pb.items item=it}
            <a class="btn btn-default" href="{$it.url|escape:'html':'UTF-8'}"><i class="icon-external-link"></i> {$it.label|escape:'html':'UTF-8'}</a>
            {/foreach}
          </div>
        {/if}
      </div>
    </div>
  </div>
  {/foreach}
</div>
