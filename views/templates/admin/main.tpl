{**
 * Hummingbird Editor — Admin interface
 **}
<div id="hbe-app" class="hbe-wrap">

  {* ── Page header ──────────────────────────────────────────────────────── *}
  <div class="page-head">
    <div class="page-head-content">
      <h1 class="page-title">
        <span class="title-content">{l s='Hummingbird Editor' mod='hummingbird_editor'}</span>
      </h1>
    </div>
  </div>

  {* ── Alerts placeholder ──────────────────────────────────────────────── *}
  <div id="hbe-global-alerts"></div>

  {* ── Toolbar ─────────────────────────────────────────────────────────── *}
  <div class="hbe-toolbar clearfix">
    <button id="hbe-add-btn" class="btn btn-primary" type="button">
      <i class="icon-plus"></i> {l s='Add block' mod='hummingbird_editor'}
    </button>
    <a id="hbe-export-btn" class="btn btn-default"
       data-export-url="{$hbe_ajax_url nofilter}&action=ExportBackup&token={$hbe_token}"
       href="{$hbe_ajax_url nofilter}&action=ExportBackup&token={$hbe_token}"
       title="{l s='Pełny backup: ustawienia + bloki + slider + wszystkie obrazki' mod='hummingbird_editor'}">
      <i class="icon-download"></i> {l s='Backup (ZIP)' mod='hummingbird_editor'}
    </a>
    <label class="hbe-export-opt" style="margin:0 8px 0 0;font-weight:normal" title="{l s='Dołącz przypisania modułów do hooków wyglądu (displayHome itd.) + listę powiązanych modułów i ich stan aktywności' mod='hummingbird_editor'}">
      <input type="checkbox" id="hbe-opt-hooks"> {l s='+ hooki i powiązane moduły' mod='hummingbird_editor'}
    </label>
    <button id="hbe-import-btn" class="btn btn-default" type="button" title="{l s='Wczytaj backup ZIP (lub stary plik XML)' mod='hummingbird_editor'}">
      <i class="icon-upload"></i> {l s='Wczytaj backup' mod='hummingbird_editor'}
    </button>
    <input type="file" id="hbe-import-file" accept=".zip,application/zip,.xml,application/xml,text/xml" style="display:none">
    <p class="hbe-hint text-muted">
      {l s='Drag rows to reorder within a hook group.' mod='hummingbird_editor'}
    </p>
  </div>

  {* ── Import z serwera (dla dużych backupów przekraczających limit uploadu) ── *}
  <div class="hbe-server-backups panel" style="margin-top:8px">
    <div class="panel-heading">
      <i class="icon-hdd"></i> {l s='Backupy na serwerze' mod='hummingbird_editor'}
    </div>
    <div class="panel-body">
      <p class="text-muted" style="margin-top:0">
        {l s='Duży backup ZIP (ze zdjęciami) może przekroczyć limit uploadu przeglądarki. Wgraj plik przez FTP/SFTP do katalogu' mod='hummingbird_editor'}
        <code>{$hbe_backup_dir}</code>{l s=', odśwież stronę i zaimportuj poniżej.' mod='hummingbird_editor'}
      </p>
      {if $hbe_server_backups && $hbe_server_backups|@count > 0}
        <div class="form-inline">
          <select id="hbe-server-backup-select" class="form-control">
            {foreach from=$hbe_server_backups item=bk}
              <option value="{$bk.filename|escape:'html':'UTF-8'}">{$bk.filename|escape:'html':'UTF-8'} — {$bk.size_h} — {$bk.date}</option>
            {/foreach}
          </select>
          <button id="hbe-server-import-btn" class="btn btn-default" type="button">
            <i class="icon-upload"></i> {l s='Importuj wybrany' mod='hummingbird_editor'}
          </button>
        </div>
      {else}
        <p class="text-muted" style="margin-bottom:0"><em>{l s='Brak plików backupu na serwerze.' mod='hummingbird_editor'}</em></p>
      {/if}
    </div>
  </div>

  {* ── Filled-state detection for smart collapse ───────────────────────── *}
  {assign var=hbe_s_topbar    value=0}
  {if isset($hbe_topbar_text_lang[$hbe_lang_id])    && $hbe_topbar_text_lang[$hbe_lang_id]    neq ''}{assign var=hbe_s_topbar    value=1}{/if}
  {assign var=hbe_s_infobar   value=0}
  {if isset($hbe_infobar_text_lang[$hbe_lang_id])   && $hbe_infobar_text_lang[$hbe_lang_id]   neq ''}{assign var=hbe_s_infobar   value=1}{/if}
  {assign var=hbe_s_infobar2  value=0}
  {if isset($hbe_infobar2_text_lang[$hbe_lang_id])  && $hbe_infobar2_text_lang[$hbe_lang_id]  neq ''}{assign var=hbe_s_infobar2  value=1}{/if}
  {assign var=hbe_s_imghero   value=0}
  {if isset($hbe_imghero_img_url)  && $hbe_imghero_img_url  neq ''}{assign var=hbe_s_imghero   value=1}{/if}
  {assign var=hbe_s_imghero2  value=0}
  {if isset($hbe_imghero2_img_url) && $hbe_imghero2_img_url neq ''}{assign var=hbe_s_imghero2  value=1}{/if}
  {assign var=hbe_s_cols3     value=0}
  {if isset($hbe_cols3_text_1_lang[$hbe_lang_id])    && $hbe_cols3_text_1_lang[$hbe_lang_id]    neq ''}{assign var=hbe_s_cols3     value=1}{/if}
  {assign var=hbe_s_cols3d    value=0}
  {if isset($hbe_cols3d_title_1_lang[$hbe_lang_id])  && $hbe_cols3d_title_1_lang[$hbe_lang_id]  neq ''}{assign var=hbe_s_cols3d    value=1}{/if}
  {assign var=hbe_s_tagline   value=0}
  {if isset($hbe_tagline_text_lang[$hbe_lang_id])    && $hbe_tagline_text_lang[$hbe_lang_id]    neq ''}{assign var=hbe_s_tagline   value=1}{/if}
  {assign var=hbe_s_katcols   value=0}
  {if (isset($hbe_katcols_l_img_url) && $hbe_katcols_l_img_url neq '') || (isset($hbe_katcols_title_lang[$hbe_lang_id]) && $hbe_katcols_title_lang[$hbe_lang_id] neq '')}{assign var=hbe_s_katcols value=1}{/if}
  {assign var=hbe_s_splitblock value=0}
  {if (isset($hbe_splitblock_m_img_url) && $hbe_splitblock_m_img_url neq '') || (isset($hbe_splitblock_r_img_url) && $hbe_splitblock_r_img_url neq '')}{assign var=hbe_s_splitblock value=1}{/if}
  {assign var=hbe_s_icons4    value=0}
  {if isset($hbe_icons4_img_url_1) && $hbe_icons4_img_url_1 neq ''}{assign var=hbe_s_icons4    value=1}{/if}
  {assign var=hbe_s_brands    value=0}
  {if isset($hbe_brands_items) && $hbe_brands_items|count && ($hbe_brands_items[0].img_url neq '' || $hbe_brands_items[0].id_manufacturer > 0)}{assign var=hbe_s_brands value=1}{/if}
  {assign var=hbe_s_carousel  value=0}
  {if isset($hbe_np_title_lang[$hbe_lang_id]) && $hbe_np_title_lang[$hbe_lang_id] neq ''}{assign var=hbe_s_carousel  value=1}{/if}
  {assign var=hbe_s_shops     value=0}
  {if (isset($hbe_shops_title_lang[$hbe_lang_id]) && $hbe_shops_title_lang[$hbe_lang_id] neq '') || (isset($hbe_shops_stores[1].img_urls[1]) && $hbe_shops_stores[1].img_urls[1] neq '')}{assign var=hbe_s_shops value=1}{/if}

  {* ── Main section tabs ───────────────────────────────────────────────── *}
  <ul class="nav nav-tabs hbe-main-tabs" role="tablist">
    <li role="presentation" class="active">
      <a href="#hbe-tab-bars" data-toggle="tab" role="tab"><i class="icon-bullhorn"></i> {l s='Paski info' mod='hummingbird_editor'}</a>
    </li>
    <li role="presentation">
      <a href="#hbe-tab-home" data-toggle="tab" role="tab"><i class="icon-home"></i> {l s='Strona główna' mod='hummingbird_editor'}</a>
    </li>
    <li role="presentation">
      <a href="#hbe-tab-cart" data-toggle="tab" role="tab"><i class="icon-shopping-cart"></i> {l s='Koszyk' mod='hummingbird_editor'}</a>
    </li>
    <li role="presentation">
      <a href="#hbe-tab-checkout" data-toggle="tab" role="tab"><i class="icon-credit-card"></i> {l s='Kasa' mod='hummingbird_editor'}</a>
    </li>
    <li role="presentation">
      <a href="#hbe-tab-productcard" data-toggle="tab" role="tab"><i class="icon-tag"></i> {l s='Karta produktu' mod='hummingbird_editor'}</a>
    </li>
    <li role="presentation">
      <a href="#hbe-tab-listing" data-toggle="tab" role="tab"><i class="icon-th-list"></i> {l s='Listing' mod='hummingbird_editor'}</a>
    </li>
    <li role="presentation">
      <a href="#hbe-tab-miniatures" data-toggle="tab" role="tab"><i class="icon-th-large"></i> {l s='Miniatury' mod='hummingbird_editor'}</a>
    </li>
    <li role="presentation">
      <a href="#hbe-tab-menu" data-toggle="tab" role="tab"><i class="icon-sitemap"></i> {l s='Menu' mod='hummingbird_editor'}</a>
    </li>
    <li role="presentation">
      <a href="#hbe-tab-giftcard" data-toggle="tab" role="tab"><i class="icon-gift"></i> {l s='Karta podarunkowa' mod='hummingbird_editor'}</a>
    </li>
    <li role="presentation">
      <a href="#hbe-tab-settings" data-toggle="tab" role="tab"><i class="icon-cogs"></i> {l s='Ustawienia' mod='hummingbird_editor'}</a>
    </li>
  </ul>

  <div class="tab-content hbe-tab-content">

    {* ═══════════════════════════════════════════════════════════════════════
       Tab 1 — Paski info
    ═══════════════════════════════════════════════════════════════════════ *}
    <div id="hbe-tab-bars" class="tab-pane active" role="tabpanel">

      {* Top promo bar *}
      <div class="panel panel-default hbe-collapse-panel">
        <div class="panel-heading hbe-cp-head" data-toggle="collapse" data-target="#hbe-c-topbar">
          <h4 class="panel-title clearfix">
            {l s='Top promo bar' mod='hummingbird_editor'}
            <span class="pull-right">
              {if $hbe_topbar_enabled}<span class="label label-success hbe-status-badge">{l s='Włączone' mod='hummingbird_editor'}</span>{/if}
              <i class="icon-chevron-down hbe-chevron{if !$hbe_s_topbar} hbe-chevron-open{/if}"></i>
            </span>
          </h4>
        </div>
        <div id="hbe-c-topbar" class="panel-collapse collapse{if !$hbe_s_topbar} in{/if}">
          <div class="panel-body">
            <form id="hbe-topbar-form" method="post" action="{$hbe_ajax_url nofilter}" autocomplete="off">
              <input type="hidden" name="token" value="{$hbe_token}">
              <div class="row">
                <div class="col-md-2 form-group">
                  <label class="control-label">{l s='Enabled' mod='hummingbird_editor'}</label>
                  <div class="checkbox"><label>
                    <input type="checkbox" name="enabled" value="1" {if $hbe_topbar_enabled}checked{/if}>
                    {l s='Yes' mod='hummingbird_editor'}
                  </label></div>
                </div>
                <div class="col-md-4 form-group">
                  <label class="control-label">{l s='Text' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='text' values=$hbe_topbar_text_lang placeholder='Promocja na wszystkie produkty -20%'}
                </div>
                <div class="col-md-3 form-group">
                  <label class="control-label">{l s='Tekst linku (opcjonalny)' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='link_text' values=$hbe_topbar_link_text_lang placeholder='KUP TERAZ'}
                </div>
                <div class="col-md-3 form-group">
                  <label class="control-label">{l s='Link (optional)' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='url' values=$hbe_topbar_url_lang placeholder='https://example.com/promocja'}
                </div>
              </div>
              <button type="submit" class="btn btn-success"><i class="icon-save"></i> {l s='Save top bar' mod='hummingbird_editor'}</button>
              <div class="hbe-alerts"></div>
            </form>
          </div>
        </div>
      </div>

      {* Info bar *}
      <div class="panel panel-default hbe-collapse-panel">
        <div class="panel-heading hbe-cp-head" data-toggle="collapse" data-target="#hbe-c-infobar">
          <h4 class="panel-title clearfix">
            {l s='Info bar (poniżej slidera)' mod='hummingbird_editor'}
            <span class="pull-right">
              {if $hbe_infobar_enabled}<span class="label label-success hbe-status-badge">{l s='Włączone' mod='hummingbird_editor'}</span>{/if}
              <i class="icon-chevron-down hbe-chevron{if !$hbe_s_infobar} hbe-chevron-open{/if}"></i>
            </span>
          </h4>
        </div>
        <div id="hbe-c-infobar" class="panel-collapse collapse{if !$hbe_s_infobar} in{/if}">
          <div class="panel-body">
            <form id="hbe-infobar-form" method="post" action="{$hbe_ajax_url nofilter}" autocomplete="off">
              <input type="hidden" name="token" value="{$hbe_token}">
              <div class="row">
                <div class="col-md-2 form-group">
                  <label class="control-label">{l s='Włącz' mod='hummingbird_editor'}</label>
                  <div class="checkbox"><label>
                    <input type="checkbox" name="enabled" value="1" {if $hbe_infobar_enabled}checked{/if}>
                    {l s='Yes' mod='hummingbird_editor'}
                  </label></div>
                </div>
                <div class="col-md-3 form-group">
                  <label class="control-label">{l s='Tekst' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='text' values=$hbe_infobar_text_lang placeholder='Sprawdź naszą ofertę!'}
                </div>
                <div class="col-md-2 form-group">
                  <label class="control-label">{l s='Tekst linku' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='link_text' values=$hbe_infobar_link_text_lang placeholder='Zobacz więcej'}
                </div>
                <div class="col-md-2 form-group">
                  <label class="control-label">{l s='Link (opcjonalny)' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='url' values=$hbe_infobar_url_lang placeholder='https://example.com/oferta'}
                </div>
                <div class="col-md-1 form-group">
                  <label class="control-label">{l s='Kolor tła' mod='hummingbird_editor'}</label>
                  <input type="color" name="bg" class="form-control" value="{$hbe_infobar_bg|escape:'html':'UTF-8'}" style="height:38px;padding:2px 4px;cursor:pointer">
                </div>
                <div class="col-md-1 form-group">
                  <label class="control-label">{l s='Kolor tekstu' mod='hummingbird_editor'}</label>
                  <input type="color" name="color" class="form-control" value="{$hbe_infobar_color|escape:'html':'UTF-8'}" style="height:38px;padding:2px 4px;cursor:pointer">
                </div>
                <div class="col-md-1 form-group" style="display:flex;align-items:flex-end">
                  <div class="hbe-infobar-preview" style="padding:0.35rem 1rem;border-radius:3px;font-size:0.85rem;font-weight:500;white-space:nowrap;background:{$hbe_infobar_bg|escape:'html':'UTF-8'};color:{$hbe_infobar_color|escape:'html':'UTF-8'}">
                    {$hbe_infobar_text|escape:'html':'UTF-8'|truncate:15:'…'}
                  </div>
                </div>
              </div>
              <button type="submit" class="btn btn-success"><i class="icon-save"></i> {l s='Zapisz info bar' mod='hummingbird_editor'}</button>
              <div class="hbe-alerts"></div>
            </form>
          </div>
        </div>
      </div>

      {* Info bar 2 *}
      <div class="panel panel-default hbe-collapse-panel">
        <div class="panel-heading hbe-cp-head" data-toggle="collapse" data-target="#hbe-c-infobar2">
          <h4 class="panel-title clearfix">
            {l s='Info bar 2 (druga kopia, poniżej slidera)' mod='hummingbird_editor'}
            <span class="pull-right">
              {if $hbe_infobar2_enabled}<span class="label label-success hbe-status-badge">{l s='Włączone' mod='hummingbird_editor'}</span>{/if}
              <i class="icon-chevron-down hbe-chevron{if !$hbe_s_infobar2} hbe-chevron-open{/if}"></i>
            </span>
          </h4>
        </div>
        <div id="hbe-c-infobar2" class="panel-collapse collapse{if !$hbe_s_infobar2} in{/if}">
          <div class="panel-body">
            <form id="hbe-infobar2-form" method="post" action="{$hbe_ajax_url nofilter}" autocomplete="off">
              <input type="hidden" name="token" value="{$hbe_token}">
              <div class="row">
                <div class="col-md-2 form-group">
                  <label class="control-label">{l s='Włącz' mod='hummingbird_editor'}</label>
                  <div class="checkbox"><label>
                    <input type="checkbox" name="enabled" value="1" {if $hbe_infobar2_enabled}checked{/if}>
                    {l s='Yes' mod='hummingbird_editor'}
                  </label></div>
                </div>
                <div class="col-md-3 form-group">
                  <label class="control-label">{l s='Tekst' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='text' values=$hbe_infobar2_text_lang placeholder='Druga informacja'}
                </div>
                <div class="col-md-2 form-group">
                  <label class="control-label">{l s='Tekst linku' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='link_text' values=$hbe_infobar2_link_text_lang placeholder='Zobacz więcej'}
                </div>
                <div class="col-md-2 form-group">
                  <label class="control-label">{l s='Link (opcjonalny)' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='url' values=$hbe_infobar2_url_lang placeholder='https://example.com'}
                </div>
                <div class="col-md-1 form-group">
                  <label class="control-label">{l s='Kolor tła' mod='hummingbird_editor'}</label>
                  <input type="color" name="bg" class="form-control" value="{$hbe_infobar2_bg|escape:'html':'UTF-8'}" style="height:38px;padding:2px 4px;cursor:pointer">
                </div>
                <div class="col-md-1 form-group">
                  <label class="control-label">{l s='Kolor tekstu' mod='hummingbird_editor'}</label>
                  <input type="color" name="color" class="form-control" value="{$hbe_infobar2_color|escape:'html':'UTF-8'}" style="height:38px;padding:2px 4px;cursor:pointer">
                </div>
                <div class="col-md-1 form-group" style="display:flex;align-items:flex-end">
                  <div class="hbe-infobar-preview" style="padding:0.35rem 1rem;border-radius:3px;font-size:0.85rem;font-weight:500;white-space:nowrap;background:{$hbe_infobar2_bg|escape:'html':'UTF-8'};color:{$hbe_infobar2_color|escape:'html':'UTF-8'}">
                    {$hbe_infobar2_text|escape:'html':'UTF-8'|truncate:15:'…'}
                  </div>
                </div>
              </div>
              <button type="submit" class="btn btn-success"><i class="icon-save"></i> {l s='Zapisz info bar 2' mod='hummingbird_editor'}</button>
              <div class="hbe-alerts"></div>
            </form>
          </div>
        </div>
      </div>

    </div>{* /tab-bars *}

    {* ═══════════════════════════════════════════════════════════════════════
       Tab — Strona główna (body: banery, kolumny, sekcje, karuzele, slider,
       kolejność sekcji displayHome)
    ═══════════════════════════════════════════════════════════════════════ *}
    <div id="hbe-tab-home" class="tab-pane" role="tabpanel">

    {* ── Banery ── *}
    <div id="hbe-tab-banners">

      {* Baner 1 *}
      <div class="panel panel-default hbe-collapse-panel">
        <div class="panel-heading hbe-cp-head" data-toggle="collapse" data-target="#hbe-c-imghero">
          <h4 class="panel-title clearfix">
            {l s='Baner z obrazkiem (displayHome)' mod='hummingbird_editor'}
            <span class="pull-right">
              {if $hbe_imghero_enabled}<span class="label label-success hbe-status-badge">{l s='Włączone' mod='hummingbird_editor'}</span>{/if}
              <i class="icon-chevron-down hbe-chevron{if !$hbe_s_imghero} hbe-chevron-open{/if}"></i>
            </span>
          </h4>
        </div>
        <div id="hbe-c-imghero" class="panel-collapse collapse{if !$hbe_s_imghero} in{/if}">
          <div class="panel-body">
            <p class="text-muted" style="margin-bottom:1rem">{l s='Pełnoszerokościowy baner ze zdjęciem, tytułem, opisem i przyciskiem CTA w lewym dolnym rogu.' mod='hummingbird_editor'}</p>
            <form id="hbe-imghero-form" method="post" action="{$hbe_ajax_url nofilter}" enctype="multipart/form-data" autocomplete="off">
              <input type="hidden" name="token" value="{$hbe_token}">
              <div class="row">
                <div class="col-md-2 form-group">
                  <label class="control-label">{l s='Włącz' mod='hummingbird_editor'}</label>
                  <div class="checkbox"><label>
                    <input type="checkbox" name="enabled" id="hbe-imghero-enabled" value="1" {if $hbe_imghero_enabled}checked{/if}>
                    {l s='Yes' mod='hummingbird_editor'}
                  </label></div>
                </div>
                <div class="col-md-4 form-group">
                  <label class="control-label">{l s='Tytuł' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='title' values=$hbe_imghero_title_lang placeholder='np. Nowa kolekcja'}
                </div>
                <div class="col-md-6 form-group">
                  <label class="control-label">{l s='Opis' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='desc' values=$hbe_imghero_desc_lang placeholder='Krótki opis oferty'}
                </div>
              </div>
              <div class="row">
                <div class="col-md-3 form-group">
                  <label class="control-label">{l s='Tekst przycisku CTA' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='cta_text' values=$hbe_imghero_cta_text_lang placeholder='np. Sprawdź ofertę'}
                </div>
                <div class="col-md-4 form-group">
                  <label class="control-label">{l s='Link przycisku CTA' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='cta_url' values=$hbe_imghero_cta_url_lang placeholder='https://example.com/oferta'}
                </div>
              </div>
              <div class="row">
                <div class="col-md-3 form-group">
                  <label class="control-label">{l s='Zdjęcia per język' mod='hummingbird_editor'}</label>
                  <div class="checkbox"><label>
                    <input type="checkbox" name="ml_images" value="1" {if $hbe_imghero_ml_images}checked{/if}>
                    {l s='Tak (osobne zdjęcia dla języków)' mod='hummingbird_editor'}
                  </label></div>
                </div>
                <div class="col-md-6 form-group">
                  <label class="control-label">{l s='Zdjęcie banera' mod='hummingbird_editor'}</label>
                  {capture name=hbe_imghero_help}{l s='Zalecany format: JPG/WebP, min. 1920×600 px.' mod='hummingbird_editor'}{/capture}
                  {include file="{$hbe_tpl_dir}_ml_image.tpl"
                    name="image" dom_prefix="hbe-imghero-img"
                    base_url=$hbe_imghero_img_url
                    per_lang=$hbe_imghero_image_lang per_lang_urls=$hbe_imghero_image_lang_urls
                    delete_action="DeleteImgHeroImage" help=$smarty.capture.hbe_imghero_help
                    ml=$hbe_imghero_ml_images mobile=1
                    mobile_base_url=$hbe_imghero_img_mobile_url
                    mobile_per_lang_urls=$hbe_imghero_image_mobile_lang_urls}
                </div>
              </div>
              <button type="submit" class="btn btn-success"><i class="icon-save"></i> {l s='Zapisz baner' mod='hummingbird_editor'}</button>
              <div class="hbe-alerts"></div>
            </form>
          </div>
        </div>
      </div>

      {* Baner 2 *}
      <div class="panel panel-default hbe-collapse-panel">
        <div class="panel-heading hbe-cp-head" data-toggle="collapse" data-target="#hbe-c-imghero2">
          <h4 class="panel-title clearfix">
            {l s='Baner z obrazkiem 2 (displayHome)' mod='hummingbird_editor'}
            <span class="pull-right">
              {if $hbe_imghero2_enabled}<span class="label label-success hbe-status-badge">{l s='Włączone' mod='hummingbird_editor'}</span>{/if}
              <i class="icon-chevron-down hbe-chevron{if !$hbe_s_imghero2} hbe-chevron-open{/if}"></i>
            </span>
          </h4>
        </div>
        <div id="hbe-c-imghero2" class="panel-collapse collapse{if !$hbe_s_imghero2} in{/if}">
          <div class="panel-body">
            <p class="text-muted" style="margin-bottom:1rem">{l s='Drugi pełnoszerokościowy baner — identyczny układ jak Baner 1, osobne zdjęcie i treść.' mod='hummingbird_editor'}</p>
            <form id="hbe-imghero2-form" method="post" action="{$hbe_ajax_url nofilter}" enctype="multipart/form-data" autocomplete="off">
              <input type="hidden" name="token" value="{$hbe_token}">
              <div class="row">
                <div class="col-md-2 form-group">
                  <label class="control-label">{l s='Włącz' mod='hummingbird_editor'}</label>
                  <div class="checkbox"><label>
                    <input type="checkbox" name="enabled" id="hbe-imghero2-enabled" value="1" {if $hbe_imghero2_enabled}checked{/if}>
                    {l s='Yes' mod='hummingbird_editor'}
                  </label></div>
                </div>
                <div class="col-md-4 form-group">
                  <label class="control-label">{l s='Tytuł' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='title' values=$hbe_imghero2_title_lang placeholder='np. Nowa kolekcja'}
                </div>
                <div class="col-md-6 form-group">
                  <label class="control-label">{l s='Opis' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='desc' values=$hbe_imghero2_desc_lang placeholder='Krótki opis oferty'}
                </div>
              </div>
              <div class="row">
                <div class="col-md-3 form-group">
                  <label class="control-label">{l s='Tekst przycisku CTA' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='cta_text' values=$hbe_imghero2_cta_text_lang placeholder='np. Sprawdź ofertę'}
                </div>
                <div class="col-md-4 form-group">
                  <label class="control-label">{l s='Link przycisku CTA' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='cta_url' values=$hbe_imghero2_cta_url_lang placeholder='https://example.com/oferta'}
                </div>
              </div>
              <div class="row">
                <div class="col-md-3 form-group">
                  <label class="control-label">{l s='Zdjęcia per język' mod='hummingbird_editor'}</label>
                  <div class="checkbox"><label>
                    <input type="checkbox" name="ml_images" value="1" {if $hbe_imghero2_ml_images}checked{/if}>
                    {l s='Tak (osobne zdjęcia dla języków)' mod='hummingbird_editor'}
                  </label></div>
                </div>
                <div class="col-md-6 form-group">
                  <label class="control-label">{l s='Zdjęcie banera' mod='hummingbird_editor'}</label>
                  {capture name=hbe_imghero2_help}{l s='Zalecany format: JPG/WebP, min. 1920×600 px.' mod='hummingbird_editor'}{/capture}
                  {include file="{$hbe_tpl_dir}_ml_image.tpl"
                    name="image" dom_prefix="hbe-imghero2-img"
                    base_url=$hbe_imghero2_img_url
                    per_lang=$hbe_imghero2_image_lang per_lang_urls=$hbe_imghero2_image_lang_urls
                    delete_action="DeleteImgHero2Image" help=$smarty.capture.hbe_imghero2_help
                    ml=$hbe_imghero2_ml_images mobile=1
                    mobile_base_url=$hbe_imghero2_img_mobile_url
                    mobile_per_lang_urls=$hbe_imghero2_image_mobile_lang_urls}
                </div>
              </div>
              <button type="submit" class="btn btn-success"><i class="icon-save"></i> {l s='Zapisz baner 2' mod='hummingbird_editor'}</button>
              <div class="hbe-alerts"></div>
            </form>
          </div>
        </div>
      </div>

    </div>{* /tab-banners *}

    {* ── Kolumny tekstowe ── *}
    <div id="hbe-tab-cols">

      {* 3 kolumny – teksty z linkami *}
      <div class="panel panel-default hbe-collapse-panel">
        <div class="panel-heading hbe-cp-head" data-toggle="collapse" data-target="#hbe-c-cols3">
          <h4 class="panel-title clearfix">
            {l s='Blok 3 kolumn — teksty z linkami (displayHome)' mod='hummingbird_editor'}
            <span class="pull-right">
              {if $hbe_cols3_enabled}<span class="label label-success hbe-status-badge">{l s='Włączone' mod='hummingbird_editor'}</span>{/if}
              <i class="icon-chevron-down hbe-chevron{if !$hbe_s_cols3} hbe-chevron-open{/if}"></i>
            </span>
          </h4>
        </div>
        <div id="hbe-c-cols3" class="panel-collapse collapse{if !$hbe_s_cols3} in{/if}">
          <div class="panel-body">
            <p class="text-muted" style="margin-bottom:1rem">{l s='Trzy równe kolumny, każda z tekstem i linkiem. Po prawej stronie każdego tekstu widoczna jest strzałka w kółku.' mod='hummingbird_editor'}</p>
            <form id="hbe-cols3-form" method="post" action="{$hbe_ajax_url nofilter}" autocomplete="off">
              <input type="hidden" name="token" value="{$hbe_token}">
              <div class="row" style="margin-bottom:0.5rem">
                <div class="col-md-2 form-group">
                  <label class="control-label">{l s='Włącz' mod='hummingbird_editor'}</label>
                  <div class="checkbox"><label>
                    <input type="checkbox" name="enabled" value="1" {if $hbe_cols3_enabled}checked{/if}>
                    {l s='Yes' mod='hummingbird_editor'}
                  </label></div>
                </div>
              </div>
              {foreach from=[1,2,3] item=i}
              {if $i == 1}{assign var=text_lang value=$hbe_cols3_text_1_lang}{assign var=url_lang value=$hbe_cols3_url_1_lang}{elseif $i == 2}{assign var=text_lang value=$hbe_cols3_text_2_lang}{assign var=url_lang value=$hbe_cols3_url_2_lang}{else}{assign var=text_lang value=$hbe_cols3_text_3_lang}{assign var=url_lang value=$hbe_cols3_url_3_lang}{/if}
              <div class="row" style="margin-bottom:0.75rem;padding-bottom:0.75rem;border-bottom:1px solid #eee">
                <div class="col-md-1 form-group" style="padding-top:2rem;font-weight:600;font-size:1.1rem;color:#555">{$i}</div>
                <div class="col-md-5 form-group">
                  <label class="control-label">{l s='Tekst kolumny' mod='hummingbird_editor'} {$i}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name="text_{$i}" values=$text_lang placeholder='np. Szybka dostawa'}
                </div>
                <div class="col-md-5 form-group">
                  <label class="control-label">{l s='Link (URL)' mod='hummingbird_editor'} {$i}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name="url_{$i}" values=$url_lang placeholder='https://example.com/strona'}
                </div>
              </div>
              {/foreach}
              <button type="submit" class="btn btn-success" style="margin-top:0.5rem"><i class="icon-save"></i> {l s='Zapisz blok 3 kolumn' mod='hummingbird_editor'}</button>
              <div class="hbe-alerts"></div>
            </form>
          </div>
        </div>
      </div>

      {* 3 kolumny z opisami *}
      <div class="panel panel-default hbe-collapse-panel">
        <div class="panel-heading hbe-cp-head" data-toggle="collapse" data-target="#hbe-c-cols3d">
          <h4 class="panel-title clearfix">
            {l s='Blok 3 kolumn z opisami (displayHome)' mod='hummingbird_editor'}
            <span class="pull-right">
              {if $hbe_cols3d_enabled}<span class="label label-success hbe-status-badge">{l s='Włączone' mod='hummingbird_editor'}</span>{/if}
              <i class="icon-chevron-down hbe-chevron{if !$hbe_s_cols3d} hbe-chevron-open{/if}"></i>
            </span>
          </h4>
        </div>
        <div id="hbe-c-cols3d" class="panel-collapse collapse{if !$hbe_s_cols3d} in{/if}">
          <div class="panel-body">
            <p class="text-muted" style="margin-bottom:1rem">{l s='Trzy równe kolumny, każda z tytułem, opisem i linkiem.' mod='hummingbird_editor'}</p>
            <form id="hbe-cols3desc-form" method="post" action="{$hbe_ajax_url nofilter}" enctype="multipart/form-data" autocomplete="off">
              <input type="hidden" name="token" value="{$hbe_token}">
              <div class="row" style="margin-bottom:0.5rem">
                <div class="col-md-2 form-group">
                  <label class="control-label">{l s='Włącz' mod='hummingbird_editor'}</label>
                  <div class="checkbox"><label>
                    <input type="checkbox" name="enabled" value="1" {if $hbe_cols3d_enabled}checked{/if}>
                    {l s='Yes' mod='hummingbird_editor'}
                  </label></div>
                </div>
              </div>
              {foreach from=[1,2,3] item=i}
              {if $i == 1}{assign var=title_lang value=$hbe_cols3d_title_1_lang}{assign var=desc_lang value=$hbe_cols3d_desc_1_lang}{assign var=url_lang value=$hbe_cols3d_url_1_lang}{assign var=c3d_img_url value=$hbe_cols3d_img_url_1}{elseif $i == 2}{assign var=title_lang value=$hbe_cols3d_title_2_lang}{assign var=desc_lang value=$hbe_cols3d_desc_2_lang}{assign var=url_lang value=$hbe_cols3d_url_2_lang}{assign var=c3d_img_url value=$hbe_cols3d_img_url_2}{else}{assign var=title_lang value=$hbe_cols3d_title_3_lang}{assign var=desc_lang value=$hbe_cols3d_desc_3_lang}{assign var=url_lang value=$hbe_cols3d_url_3_lang}{assign var=c3d_img_url value=$hbe_cols3d_img_url_3}{/if}
              <div class="row" style="margin-bottom:0.75rem;padding-bottom:0.75rem;border-bottom:1px solid #eee">
                <div class="col-md-1 form-group" style="padding-top:2rem;font-weight:600;font-size:1.1rem;color:#555">{$i}</div>
                <div class="col-md-3 form-group">
                  <label class="control-label">{l s='Zdjęcie nad linkiem (opcjonalnie)' mod='hummingbird_editor'} {$i}</label>
                  {include file="{$hbe_tpl_dir}_ml_image.tpl"
                    name="img_`$i`" dom_prefix="hbe-cols3d-`$i`-img"
                    base_url=$c3d_img_url per_lang=[] per_lang_urls=[]
                    delete_action="DeleteCols3descImage" delete_extra="col=`$i`"
                    ml=0 mobile=0}
                </div>
                <div class="col-md-2 form-group">
                  <label class="control-label">{l s='Tytuł kolumny' mod='hummingbird_editor'} {$i}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name="title_{$i}" values=$title_lang placeholder='np. LLadro'}
                </div>
                <div class="col-md-3 form-group">
                  <label class="control-label">{l s='Opis kolumny' mod='hummingbird_editor'} {$i}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name="desc_{$i}" values=$desc_lang type='textarea' rows=2 placeholder='Krótki opis...'}
                </div>
                <div class="col-md-3 form-group">
                  <label class="control-label">{l s='Link (URL)' mod='hummingbird_editor'} {$i}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name="url_{$i}" values=$url_lang placeholder='https://example.com/strona'}
                </div>
              </div>
              {/foreach}
              <button type="submit" class="btn btn-success" style="margin-top:0.5rem"><i class="icon-save"></i> {l s='Zapisz blok 3 kolumn z opisami' mod='hummingbird_editor'}</button>
              <div class="hbe-alerts"></div>
            </form>
          </div>
        </div>
      </div>

      {* Tagline *}
      <div class="panel panel-default hbe-collapse-panel">
        <div class="panel-heading hbe-cp-head" data-toggle="collapse" data-target="#hbe-c-tagline">
          <h4 class="panel-title clearfix">
            {l s='Blok tagline — tekst z linkiem (displayHome)' mod='hummingbird_editor'}
            <span class="pull-right">
              {if $hbe_tagline_enabled}<span class="label label-success hbe-status-badge">{l s='Włączone' mod='hummingbird_editor'}</span>{/if}
              <i class="icon-chevron-down hbe-chevron{if !$hbe_s_tagline} hbe-chevron-open{/if}"></i>
            </span>
          </h4>
        </div>
        <div id="hbe-c-tagline" class="panel-collapse collapse{if !$hbe_s_tagline} in{/if}">
          <div class="panel-body">
            <p class="text-muted" style="margin-bottom:1rem">{l s='Duży tekst (font Lora 32px) z podlinkowanym napisem poniżej. Wyświetlany na stronie głównej.' mod='hummingbird_editor'}</p>
            <form id="hbe-tagline-form" method="post" action="{$hbe_ajax_url nofilter}" autocomplete="off">
              <input type="hidden" name="token" value="{$hbe_token}">
              <div class="row">
                <div class="col-md-2 form-group">
                  <label class="control-label">{l s='Włącz' mod='hummingbird_editor'}</label>
                  <div class="checkbox"><label>
                    <input type="checkbox" name="enabled" value="1" {if $hbe_tagline_enabled}checked{/if}>
                    {l s='Yes' mod='hummingbird_editor'}
                  </label></div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-10 form-group">
                  <label class="control-label">{l s='Tekst (wyświetlany fontem Lora 32px)' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='text' values=$hbe_tagline_text_lang type='textarea' rows=3 placeholder="{l s='np. Porcelana Rosenthal to połączenie niemieckiej precyzji...' mod='hummingbird_editor'}"}
                </div>
              </div>
              <div class="row">
                <div class="col-md-3 form-group">
                  <label class="control-label">{l s='Tekst linku' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='link_text' values=$hbe_tagline_link_text_lang placeholder="{l s='np. Czytaj o nas' mod='hummingbird_editor'}"}
                </div>
                <div class="col-md-5 form-group">
                  <label class="control-label">{l s='Link (URL)' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='link_url' values=$hbe_tagline_link_url_lang placeholder='https://example.com/o-nas'}
                </div>
              </div>
              <button type="submit" class="btn btn-success"><i class="icon-save"></i> {l s='Zapisz tagline' mod='hummingbird_editor'}</button>
              <div class="hbe-alerts"></div>
            </form>
          </div>
        </div>
      </div>

    </div>{* /tab-cols *}

    {* ── Sekcje z obrazkami ── *}
    <div id="hbe-tab-sections">

      {* Sekcja Kategorie *}
      <div class="panel panel-default hbe-collapse-panel">
        <div class="panel-heading hbe-cp-head" data-toggle="collapse" data-target="#hbe-c-katcols">
          <h4 class="panel-title clearfix">
            {l s='Sekcja Kategorie — dwie kolumny z obrazkami (displayHome)' mod='hummingbird_editor'}
            <span class="pull-right">
              {if $hbe_katcols_enabled}<span class="label label-success hbe-status-badge">{l s='Włączone' mod='hummingbird_editor'}</span>{/if}
              <i class="icon-chevron-down hbe-chevron{if !$hbe_s_katcols} hbe-chevron-open{/if}"></i>
            </span>
          </h4>
        </div>
        <div id="hbe-c-katcols" class="panel-collapse collapse{if !$hbe_s_katcols} in{/if}">
          <div class="panel-body">
            <p class="text-muted" style="margin-bottom:1rem">{l s='Nagłówek z tytułem i linkiem + lewa kolumna (duży obrazek) i prawa kolumna (mniejszy obrazek), każda z podpisem i linkiem.' mod='hummingbird_editor'}</p>
            <form id="hbe-katcols-form" method="post" action="{$hbe_ajax_url nofilter}" enctype="multipart/form-data" autocomplete="off">
              <input type="hidden" name="token" value="{$hbe_token}">
              <div class="row" style="margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid #eee">
                <div class="col-md-2 form-group">
                  <label class="control-label">{l s='Włącz' mod='hummingbird_editor'}</label>
                  <div class="checkbox"><label>
                    <input type="checkbox" name="enabled" value="1" {if $hbe_katcols_enabled}checked{/if}>
                    {l s='Yes' mod='hummingbird_editor'}
                  </label></div>
                </div>
                <div class="col-md-4 form-group">
                  <label class="control-label">{l s='Zdjęcia per język' mod='hummingbird_editor'}</label>
                  <div class="checkbox"><label>
                    <input type="checkbox" name="ml_images" value="1" {if $hbe_katcols_ml_images}checked{/if}>
                    {l s='Tak (osobne zdjęcia dla języków)' mod='hummingbird_editor'}
                  </label></div>
                </div>
              </div>
              <div class="row" style="margin-bottom:0.5rem">
                <div class="col-md-4 form-group">
                  <label class="control-label">{l s='Tytuł sekcji (nagłówek lewy)' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='title' values=$hbe_katcols_title_lang placeholder="{l s='np. Nasze kategorie' mod='hummingbird_editor'}"}
                </div>
                <div class="col-md-4 form-group">
                  <label class="control-label">{l s='Nagłówek prawy — tekst' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='hdr_text' values=$hbe_katcols_hdr_text_lang placeholder="{l s='np. Przeglądaj pełną ofertę' mod='hummingbird_editor'}"}
                </div>
                <div class="col-md-2 form-group">
                  <label class="control-label">{l s='Tekst linku' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='hdr_link_text' values=$hbe_katcols_hdr_link_text_lang placeholder="{l s='np. Zobacz wszystkie' mod='hummingbird_editor'}"}
                </div>
                <div class="col-md-2 form-group">
                  <label class="control-label">{l s='URL linku' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='hdr_url' values=$hbe_katcols_hdr_url_lang placeholder='https://example.com/kategorie'}
                </div>
              </div>
              <div class="row" style="margin-bottom:0.5rem">
                <div class="col-md-3 form-group">
                  <label class="control-label">{l s='Lewa kolumna — podpis' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='l_caption' values=$hbe_katcols_l_caption_lang placeholder="{l s='np. Zastawy stołowe' mod='hummingbird_editor'}"}
                </div>
                <div class="col-md-3 form-group">
                  <label class="control-label">{l s='Lewa kolumna — link' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='l_url' values=$hbe_katcols_l_url_lang placeholder='https://example.com/zastawy'}
                </div>
                <div class="col-md-3 form-group">
                  <label class="control-label">{l s='Prawa kolumna — podpis' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='r_caption' values=$hbe_katcols_r_caption_lang placeholder="{l s='np. Szkła kryształowe' mod='hummingbird_editor'}"}
                </div>
                <div class="col-md-3 form-group">
                  <label class="control-label">{l s='Prawa kolumna — link' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='r_url' values=$hbe_katcols_r_url_lang placeholder='https://example.com/szkla'}
                </div>
              </div>
              <div class="row" style="margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid #eee">
                <div class="col-md-12"><strong style="display:block;margin-bottom:0.5rem">{l s='Lewa kolumna — duży obrazek (~65% szerokości)' mod='hummingbird_editor'}</strong></div>
                <div class="col-md-12 form-group">
                  <label class="control-label">{l s='Zdjęcie' mod='hummingbird_editor'}</label>
                  {capture name=hbe_katcols_l_help}{l s='Zalecany: JPG/WebP, proporcje ok. 3:2, min. 800px.' mod='hummingbird_editor'}{/capture}
                  {include file="{$hbe_tpl_dir}_ml_image.tpl"
                    name="l_image" dom_prefix="hbe-katcols-l-img"
                    base_url=$hbe_katcols_l_img_url
                    per_lang=$hbe_katcols_l_image_lang per_lang_urls=$hbe_katcols_l_image_lang_urls
                    delete_action="DeleteKatcolsImage" delete_extra="side=l"
                    help=$smarty.capture.hbe_katcols_l_help ml=$hbe_katcols_ml_images
                    mobile=1 mobile_base_url=$hbe_katcols_l_img_mobile_url
                    mobile_per_lang_urls=$hbe_katcols_l_image_mobile_lang_urls}
                </div>
              </div>
              <div class="row" style="margin-bottom:1rem">
                <div class="col-md-12"><strong style="display:block;margin-bottom:0.5rem">{l s='Prawa kolumna — mniejszy obrazek (~35% szerokości, ~60% rozmiaru lewego)' mod='hummingbird_editor'}</strong></div>
                <div class="col-md-12 form-group">
                  <label class="control-label">{l s='Zdjęcie' mod='hummingbird_editor'}</label>
                  {capture name=hbe_katcols_r_help}{l s='Zalecany: JPG/WebP, proporcje ok. 3:2, min. 500px.' mod='hummingbird_editor'}{/capture}
                  {include file="{$hbe_tpl_dir}_ml_image.tpl"
                    name="r_image" dom_prefix="hbe-katcols-r-img"
                    base_url=$hbe_katcols_r_img_url
                    per_lang=$hbe_katcols_r_image_lang per_lang_urls=$hbe_katcols_r_image_lang_urls
                    delete_action="DeleteKatcolsImage" delete_extra="side=r"
                    help=$smarty.capture.hbe_katcols_r_help ml=$hbe_katcols_ml_images
                    mobile=1 mobile_base_url=$hbe_katcols_r_img_mobile_url
                    mobile_per_lang_urls=$hbe_katcols_r_image_mobile_lang_urls}
                </div>
              </div>
              <button type="submit" class="btn btn-success"><i class="icon-save"></i> {l s='Zapisz sekcję Kategorie' mod='hummingbird_editor'}</button>
              <div class="hbe-alerts"></div>
            </form>
          </div>
        </div>
      </div>

      {* Split block *}
      <div class="panel panel-default hbe-collapse-panel">
        <div class="panel-heading hbe-cp-head" data-toggle="collapse" data-target="#hbe-c-splitblock">
          <h4 class="panel-title clearfix">
            {l s='Sekcja 3 kolumn — tekst, obraz środkowy, duży obraz prawy (displayHome)' mod='hummingbird_editor'}
            <span class="pull-right">
              {if $hbe_splitblock_enabled}<span class="label label-success hbe-status-badge">{l s='Włączone' mod='hummingbird_editor'}</span>{/if}
              <i class="icon-chevron-down hbe-chevron{if !$hbe_s_splitblock} hbe-chevron-open{/if}"></i>
            </span>
          </h4>
        </div>
        <div id="hbe-c-splitblock" class="panel-collapse collapse{if !$hbe_s_splitblock} in{/if}">
          <div class="panel-body">
            <p class="text-muted" style="margin-bottom:1rem">{l s='Lewa+środkowa = 50%, prawa = 50%. Lewa: tytuł, opis, CTA. Środkowa: mniejszy obraz (50% kolumny). Prawa: duży obraz.' mod='hummingbird_editor'}</p>
            <form id="hbe-splitblock-form" method="post" action="{$hbe_ajax_url nofilter}" enctype="multipart/form-data" autocomplete="off">
              <input type="hidden" name="token" value="{$hbe_token}">
              <div class="row" style="margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid #eee">
                <div class="col-md-2 form-group">
                  <label class="control-label">{l s='Włącz' mod='hummingbird_editor'}</label>
                  <div class="checkbox"><label>
                    <input type="checkbox" name="enabled" value="1" {if $hbe_splitblock_enabled}checked{/if}>
                    {l s='Yes' mod='hummingbird_editor'}
                  </label></div>
                </div>
                <div class="col-md-4 form-group">
                  <label class="control-label">{l s='Zdjęcia per język' mod='hummingbird_editor'}</label>
                  <div class="checkbox"><label>
                    <input type="checkbox" name="ml_images" value="1" {if $hbe_splitblock_ml_images}checked{/if}>
                    {l s='Tak (osobne zdjęcia dla języków)' mod='hummingbird_editor'}
                  </label></div>
                </div>
              </div>
              <div class="row" style="margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid #eee">
                <div class="col-md-12"><strong style="display:block;margin-bottom:0.5rem">{l s='Lewa kolumna — tekst + CTA' mod='hummingbird_editor'}</strong></div>
                <div class="col-md-4 form-group">
                  <label class="control-label">{l s='Tytuł' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='title' values=$hbe_splitblock_title_lang placeholder='np. Nowa kolekcja'}
                </div>
                <div class="col-md-8 form-group">
                  <label class="control-label">{l s='Opis' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='desc' values=$hbe_splitblock_desc_lang type='textarea' rows=3 placeholder='np. Odkryj nasze najnowsze produkty...'}
                </div>
                <div class="col-md-3 form-group">
                  <label class="control-label">{l s='Tekst przycisku CTA' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='cta_text' values=$hbe_splitblock_cta_text_lang placeholder='np. Sprawdź ofertę'}
                </div>
                <div class="col-md-5 form-group">
                  <label class="control-label">{l s='Link CTA' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='cta_url' values=$hbe_splitblock_cta_url_lang placeholder='https://example.com/oferta'}
                </div>
              </div>
              <div class="row" style="margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid #eee">
                <div class="col-md-12"><strong style="display:block;margin-bottom:0.5rem">{l s='Środkowa kolumna — obraz (wyświetlany na 50% kolumny)' mod='hummingbird_editor'}</strong></div>
                <div class="col-md-5 form-group">
                  <label class="control-label">{l s='Zdjęcie środkowe' mod='hummingbird_editor'}</label>
                  {capture name=hbe_splitblock_m_help}{l s='Dowolny format. Wyświetlany na 50% szerokości kolumny.' mod='hummingbird_editor'}{/capture}
                  {include file="{$hbe_tpl_dir}_ml_image.tpl"
                    name="m_image" dom_prefix="hbe-splitblock-m-img"
                    base_url=$hbe_splitblock_m_img_url
                    per_lang=$hbe_splitblock_m_image_lang per_lang_urls=$hbe_splitblock_m_image_lang_urls
                    delete_action="DeleteSplitBlockImage" delete_extra="side=m"
                    help=$smarty.capture.hbe_splitblock_m_help ml=$hbe_splitblock_ml_images
                    mobile=1 mobile_base_url=$hbe_splitblock_m_img_mobile_url
                    mobile_per_lang_urls=$hbe_splitblock_m_image_mobile_lang_urls}
                </div>
              </div>
              <div class="row" style="margin-bottom:1rem">
                <div class="col-md-12"><strong style="display:block;margin-bottom:0.5rem">{l s='Prawa kolumna — duży obraz (50% całej sekcji)' mod='hummingbird_editor'}</strong></div>
                <div class="col-md-5 form-group">
                  <label class="control-label">{l s='Duże zdjęcie' mod='hummingbird_editor'}</label>
                  {capture name=hbe_splitblock_r_help}{l s='Zalecany: JPG/WebP, min. 900px szer., proporcje ok. 3:4 lub 1:1.' mod='hummingbird_editor'}{/capture}
                  {include file="{$hbe_tpl_dir}_ml_image.tpl"
                    name="r_image" dom_prefix="hbe-splitblock-r-img"
                    base_url=$hbe_splitblock_r_img_url
                    per_lang=$hbe_splitblock_r_image_lang per_lang_urls=$hbe_splitblock_r_image_lang_urls
                    delete_action="DeleteSplitBlockImage" delete_extra="side=r"
                    help=$smarty.capture.hbe_splitblock_r_help ml=$hbe_splitblock_ml_images
                    mobile=1 mobile_base_url=$hbe_splitblock_r_img_mobile_url
                    mobile_per_lang_urls=$hbe_splitblock_r_image_mobile_lang_urls}
                </div>
              </div>
              <button type="submit" class="btn btn-success"><i class="icon-save"></i> {l s='Zapisz sekcję 3 kolumn' mod='hummingbird_editor'}</button>
              <div class="hbe-alerts"></div>
            </form>
          </div>
        </div>
      </div>

      {* Ikony 4 kolumny *}
      <div class="panel panel-default hbe-collapse-panel">
        <div class="panel-heading hbe-cp-head" data-toggle="collapse" data-target="#hbe-c-icons4">
          <h4 class="panel-title clearfix">
            {l s='Blok 4 kolumn z ikonami (displayHome)' mod='hummingbird_editor'}
            <span class="pull-right">
              {if $hbe_icons4_enabled}<span class="label label-success hbe-status-badge">{l s='Włączone' mod='hummingbird_editor'}</span>{/if}
              <i class="icon-chevron-down hbe-chevron{if !$hbe_s_icons4} hbe-chevron-open{/if}"></i>
            </span>
          </h4>
        </div>
        <div id="hbe-c-icons4" class="panel-collapse collapse{if !$hbe_s_icons4} in{/if}">
          <div class="panel-body">
            <p class="text-muted" style="margin-bottom:1rem">{l s='Cztery równe kolumny, każda z ikoną (obrazek), tytułem i opisem. Np. blok zaufania / bezpieczeństwo sklepu.' mod='hummingbird_editor'}</p>
            <form id="hbe-icons4-form" method="post" action="{$hbe_ajax_url nofilter}" enctype="multipart/form-data" autocomplete="off">
              <input type="hidden" name="token" value="{$hbe_token}">
              <div class="row" style="margin-bottom:0.5rem">
                <div class="col-md-2 form-group">
                  <label class="control-label">{l s='Włącz' mod='hummingbird_editor'}</label>
                  <div class="checkbox"><label>
                    <input type="checkbox" name="enabled" value="1" {if $hbe_icons4_enabled}checked{/if}>
                    {l s='Yes' mod='hummingbird_editor'}
                  </label></div>
                </div>
                <div class="col-md-4 form-group">
                  <label class="control-label">{l s='Zdjęcia per język' mod='hummingbird_editor'}</label>
                  <div class="checkbox"><label>
                    <input type="checkbox" name="ml_images" value="1" {if $hbe_icons4_ml_images}checked{/if}>
                    {l s='Tak (osobne zdjęcia dla języków)' mod='hummingbird_editor'}
                  </label></div>
                </div>
              </div>
              {foreach from=[1,2,3,4] item=i}
              <div class="row" style="margin-bottom:0.75rem;padding-bottom:0.75rem;border-bottom:1px solid #eee">
                <div class="col-md-1 form-group" style="padding-top:2rem;font-weight:600;font-size:1.1rem;color:#555">{$i}</div>
                <div class="col-md-3 form-group">
                  <label class="control-label">{l s='Ikona (obrazek)' mod='hummingbird_editor'} {$i}</label>
                  {if $i == 1}
                    {assign var=ic4_base_url value=$hbe_icons4_img_url_1}
                    {assign var=ic4_per_lang value=$hbe_icons4_img_1_lang}
                    {assign var=ic4_per_lang_urls value=$hbe_icons4_img_1_lang_urls}
                    {assign var=ic4_mobile_base_url value=$hbe_icons4_img_mobile_url_1}
                    {assign var=ic4_mobile_per_lang_urls value=$hbe_icons4_img_1_mobile_lang_urls}
                  {elseif $i == 2}
                    {assign var=ic4_base_url value=$hbe_icons4_img_url_2}
                    {assign var=ic4_per_lang value=$hbe_icons4_img_2_lang}
                    {assign var=ic4_per_lang_urls value=$hbe_icons4_img_2_lang_urls}
                    {assign var=ic4_mobile_base_url value=$hbe_icons4_img_mobile_url_2}
                    {assign var=ic4_mobile_per_lang_urls value=$hbe_icons4_img_2_mobile_lang_urls}
                  {elseif $i == 3}
                    {assign var=ic4_base_url value=$hbe_icons4_img_url_3}
                    {assign var=ic4_per_lang value=$hbe_icons4_img_3_lang}
                    {assign var=ic4_per_lang_urls value=$hbe_icons4_img_3_lang_urls}
                    {assign var=ic4_mobile_base_url value=$hbe_icons4_img_mobile_url_3}
                    {assign var=ic4_mobile_per_lang_urls value=$hbe_icons4_img_3_mobile_lang_urls}
                  {else}
                    {assign var=ic4_base_url value=$hbe_icons4_img_url_4}
                    {assign var=ic4_per_lang value=$hbe_icons4_img_4_lang}
                    {assign var=ic4_per_lang_urls value=$hbe_icons4_img_4_lang_urls}
                    {assign var=ic4_mobile_base_url value=$hbe_icons4_img_mobile_url_4}
                    {assign var=ic4_mobile_per_lang_urls value=$hbe_icons4_img_4_mobile_lang_urls}
                  {/if}
                  {include file="{$hbe_tpl_dir}_ml_image.tpl"
                    name="img_`$i`" dom_prefix="hbe-icons4-`$i`-img"
                    base_url=$ic4_base_url per_lang=$ic4_per_lang per_lang_urls=$ic4_per_lang_urls
                    delete_action="DeleteIcons4Image" delete_extra="col=`$i`"
                    ml=$hbe_icons4_ml_images mobile=1
                    mobile_base_url=$ic4_mobile_base_url mobile_per_lang_urls=$ic4_mobile_per_lang_urls}
                </div>
                <div class="col-md-4 form-group">
                  <label class="control-label">{l s='Tytuł' mod='hummingbird_editor'} {$i}</label>
                  {if $i == 1}{assign var=ic4_title_lang value=$hbe_icons4_title_1_lang}{elseif $i == 2}{assign var=ic4_title_lang value=$hbe_icons4_title_2_lang}{elseif $i == 3}{assign var=ic4_title_lang value=$hbe_icons4_title_3_lang}{else}{assign var=ic4_title_lang value=$hbe_icons4_title_4_lang}{/if}
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name="title_{$i}" values=$ic4_title_lang placeholder='np. Bezpieczne płatności'}
                </div>
                <div class="col-md-4 form-group">
                  <label class="control-label">{l s='Opis' mod='hummingbird_editor'} {$i}</label>
                  {if $i == 1}{assign var=ic4_desc_lang value=$hbe_icons4_desc_1_lang}{elseif $i == 2}{assign var=ic4_desc_lang value=$hbe_icons4_desc_2_lang}{elseif $i == 3}{assign var=ic4_desc_lang value=$hbe_icons4_desc_3_lang}{else}{assign var=ic4_desc_lang value=$hbe_icons4_desc_4_lang}{/if}
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name="desc_{$i}" values=$ic4_desc_lang type='textarea' rows=2 placeholder='Krótki opis...'}
                </div>
              </div>
              {/foreach}
              <button type="submit" class="btn btn-success" style="margin-top:0.5rem"><i class="icon-save"></i> {l s='Zapisz blok ikon' mod='hummingbird_editor'}</button>
              <div class="hbe-alerts"></div>
            </form>
          </div>
        </div>
      </div>

      {* ── Brands panel ──────────────────────────────────────────────────── *}
      <div class="panel panel-default hbe-collapse-panel">
        <div class="panel-heading hbe-cp-head" data-toggle="collapse" data-target="#hbe-c-brands">
          <h4 class="panel-title clearfix">
            {l s='Pasek marek / logotypów' mod='hummingbird_editor'}
            <span class="pull-right">
              {if $hbe_brands_enabled}<span class="label label-success hbe-status-badge">{l s='Włączone' mod='hummingbird_editor'}</span>{/if}
              <i class="icon-chevron-down hbe-chevron{if !$hbe_s_brands} hbe-chevron-open{/if}"></i>
            </span>
          </h4>
        </div>
        <div id="hbe-c-brands" class="panel-collapse collapse{if !$hbe_s_brands} in{/if}">
          <div class="panel-body">
            <form id="hbe-brands-form" method="post" action="{$hbe_ajax_url nofilter}" enctype="multipart/form-data" autocomplete="off">
              <input type="hidden" name="token" value="{$hbe_token}">
              <input type="hidden" name="ajax" value="1">
              <input type="hidden" name="action" value="SaveBrands">

              <div class="row">
                <div class="col-md-4 form-group">
                  <label class="control-label">{l s='Włączone' mod='hummingbird_editor'}</label>
                  <div class="checkbox">
                    <label><input type="checkbox" name="enabled" value="1" {if $hbe_brands_enabled}checked{/if}> {l s='Pokaż sekcję na stronie' mod='hummingbird_editor'}</label>
                  </div>
                </div>
                <div class="col-md-8 form-group">
                  <label class="control-label">{l s='Tytuł sekcji (opcjonalny)' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='HBE_BRANDS_TITLE' values=$hbe_brands_title_lang placeholder='np. Nasze marki'}
                </div>
              </div>

              <hr style="margin:0.75rem 0">
              <p class="text-muted" style="margin-bottom:1rem">{l s='Wybierz markę z bazy sklepu — logo, nazwa i link uzupełnią się automatycznie. Możesz nadpisać logo (własny plik) i nazwę (tekst alt). Pozostaw markę pustą, aby dodać własny logotyp ręcznie. Puste sloty są pomijane.' mod='hummingbird_editor'}</p>

              {foreach from=$hbe_brands_items item=bitem}
                {assign var=brand_preview value=''}
                {if $bitem.img_url}{assign var=brand_preview value=$bitem.img_url}{elseif $bitem.manu_logo_url}{assign var=brand_preview value=$bitem.manu_logo_url}{/if}
                <div class="row hbe-brand-row" style="border:1px solid #eee;border-radius:4px;padding:0.75rem 0.5rem;margin-bottom:0.75rem">
                  <div class="col-md-1 text-center" style="padding-top:0.25rem">
                    <strong style="color:#666">#{$bitem.n}</strong>
                    <br>
                    <img id="hbe-brand-preview-{$bitem.n}" src="{$brand_preview|escape:'html':'UTF-8'}" style="max-width:60px;max-height:40px;object-fit:contain;margin-top:4px{if !$brand_preview};display:none{/if}" alt="">
                  </div>
                  <div class="col-md-3 form-group" style="margin-bottom:0">
                    <label class="control-label" style="font-size:0.8rem">{l s='Marka (producent)' mod='hummingbird_editor'}</label>
                    <select name="HBE_BRANDS_MANU_{$bitem.n}" class="form-control hbe-brand-manu" data-slot="{$bitem.n}">
                      <option value="0">{l s='— ręcznie / brak —' mod='hummingbird_editor'}</option>
                      {foreach from=$hbe_manufacturers item=manu}
                        <option value="{$manu.id}" data-logo="{$manu.logo_url|escape:'html':'UTF-8'}"{if $manu.id == $bitem.id_manufacturer} selected{/if}>{$manu.name|escape:'html':'UTF-8'}</option>
                      {/foreach}
                    </select>
                  </div>
                  <div class="col-md-3 form-group" style="margin-bottom:0">
                    <label class="control-label" style="font-size:0.8rem">{l s='Własny logotyp (nadpisuje)' mod='hummingbird_editor'}</label>
                    <input type="file" name="HBE_BRANDS_IMG_{$bitem.n}" accept="image/*" class="form-control" style="padding:2px 6px">
                  </div>
                  <div class="col-md-2 form-group" style="margin-bottom:0">
                    <label class="control-label" style="font-size:0.8rem">{l s='Link' mod='hummingbird_editor'}</label>
                    <input type="text" name="HBE_BRANDS_LINK_{$bitem.n}" value="{$bitem.link|escape:'html':'UTF-8'}" class="form-control" placeholder="{l s='auto' mod='hummingbird_editor'}">
                  </div>
                  <div class="col-md-3 form-group" style="margin-bottom:0">
                    <label class="control-label" style="font-size:0.8rem">{l s='Nazwa / tekst alt (nadpisuje)' mod='hummingbird_editor'}</label>
                    {include file="{$hbe_tpl_dir}_ml_input.tpl" name="HBE_BRANDS_ALT_{$bitem.n}" values=$bitem.alt_lang placeholder='auto z marki'}
                  </div>
                </div>
              {/foreach}

              <button type="submit" class="btn btn-success" style="margin-top:0.5rem"><i class="icon-save"></i> {l s='Zapisz marki' mod='hummingbird_editor'}</button>
              <div class="hbe-alerts"></div>
            </form>
          </div>
        </div>
      </div>

      {* ── Inne sklepy online ──────────────────────────────────────────── *}
      <div class="panel panel-default hbe-collapse-panel">
        <div class="panel-heading hbe-cp-head" data-toggle="collapse" data-target="#hbe-c-shops">
          <h4 class="panel-title clearfix">
            {l s='Inne sklepy online — 3 sklepy z galeriami (displayHome)' mod='hummingbird_editor'}
            <span class="pull-right">
              {if $hbe_shops_enabled}<span class="label label-success hbe-status-badge">{l s='Włączone' mod='hummingbird_editor'}</span>{/if}
              <i class="icon-chevron-down hbe-chevron{if !$hbe_s_shops} hbe-chevron-open{/if}"></i>
            </span>
          </h4>
        </div>
        <div id="hbe-c-shops" class="panel-collapse collapse{if !$hbe_s_shops} in{/if}">
          <div class="panel-body">
            <p class="text-muted" style="margin-bottom:1rem">{l s='Elegancka sekcja zamykająca stronę główną (beżowe tło): nagłówek + trzy karty sklepów. Każda karta ma mozaikę 3 zdjęć (duże + dwa mniejsze), nazwę, opis i link „Odwiedź sklep". Linki zewnętrzne (http/https) otwierają się w nowej karcie. Na mobile karty przesuwa się palcem.' mod='hummingbird_editor'}</p>
            <form id="hbe-shops-form" method="post" action="{$hbe_ajax_url nofilter}" enctype="multipart/form-data" autocomplete="off">
              <input type="hidden" name="token" value="{$hbe_token}">

              <div class="row" style="margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid #eee">
                <div class="col-md-2 form-group">
                  <label class="control-label">{l s='Włącz' mod='hummingbird_editor'}</label>
                  <div class="checkbox"><label>
                    <input type="checkbox" name="enabled" value="1" {if $hbe_shops_enabled}checked{/if}>
                    {l s='Yes' mod='hummingbird_editor'}
                  </label></div>
                </div>
                <div class="col-md-2 form-group">
                  <label class="control-label">{l s='Nadtytuł (eyebrow)' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='eyebrow' values=$hbe_shops_eyebrow_lang placeholder="{l s='np. Rosenthal poleca' mod='hummingbird_editor'}"}
                </div>
                <div class="col-md-3 form-group">
                  <label class="control-label">{l s='Tytuł sekcji' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='title' values=$hbe_shops_title_lang placeholder="{l s='np. Odwiedź nasze pozostałe sklepy online' mod='hummingbird_editor'}"}
                </div>
                <div class="col-md-3 form-group">
                  <label class="control-label">{l s='Opis pod tytułem (opcjonalny)' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='text' values=$hbe_shops_text_lang type='textarea' rows=2 placeholder="{l s='Krótki lead sekcji...' mod='hummingbird_editor'}"}
                </div>
                <div class="col-md-2 form-group">
                  <label class="control-label">{l s='Tekst linku (CTA)' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='cta' values=$hbe_shops_cta_lang placeholder="{l s='np. Odwiedź sklep' mod='hummingbird_editor'}"}
                </div>
              </div>

              {foreach from=$hbe_shops_stores item=store}
              <div class="row" style="margin-bottom:1rem;padding-bottom:1rem;{if $store.n < 3}border-bottom:1px solid #eee{/if}">
                <div class="col-md-12"><strong style="display:block;margin-bottom:0.5rem">{l s='Sklep' mod='hummingbird_editor'} #{$store.n}</strong></div>
                <div class="col-md-3 form-group">
                  <label class="control-label">{l s='Nazwa sklepu' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name="name_{$store.n}" values=$store.name_lang placeholder="{l s='np. Karenski' mod='hummingbird_editor'}"}
                </div>
                <div class="col-md-5 form-group">
                  <label class="control-label">{l s='Krótki opis' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name="desc_{$store.n}" values=$store.desc_lang type='textarea' rows=2 placeholder="{l s='1–2 zdania o sklepie...' mod='hummingbird_editor'}"}
                </div>
                <div class="col-md-4 form-group">
                  <label class="control-label">{l s='Adres sklepu (URL)' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name="url_{$store.n}" values=$store.url_lang placeholder='https://example.pl'}
                </div>
                <div class="col-md-12">
                  <label class="control-label">{l s='Galeria — 3 zdjęcia (pierwsze jest największe)' mod='hummingbird_editor'}</label>
                  <p class="help-block" style="margin-top:0">{l s='Zalecany: JPG/WebP, pion (proporcje ok. 4:5–5:7), min. 800px szerokości.' mod='hummingbird_editor'}</p>
                </div>
                {foreach from=$store.img_urls key=imgIdx item=imgUrl}
                <div class="col-md-4 form-group">
                  <div id="hbe-shops-img-{$store.n}-{$imgIdx}-wrap" style="margin-bottom:0.5rem{if !$imgUrl};display:none{/if}">
                    <img id="hbe-shops-img-{$store.n}-{$imgIdx}-preview" src="{$imgUrl|escape:'html':'UTF-8'}"
                         style="max-width:100%;max-height:160px;border:1px solid #ddd;border-radius:3px;display:block;margin-bottom:0.5rem" alt="">
                    <button type="button" class="btn btn-xs btn-danger hbe-shops-del-img" data-store="{$store.n}" data-idx="{$imgIdx}">
                      <i class="icon-trash"></i> {l s='Usuń zdjęcie' mod='hummingbird_editor'}
                    </button>
                  </div>
                  <input type="file" name="img_{$store.n}_{$imgIdx}" accept="image/*" class="form-control">
                </div>
                {/foreach}
              </div>
              {/foreach}

              <button type="submit" class="btn btn-success"><i class="icon-save"></i> {l s='Zapisz Inne sklepy online' mod='hummingbird_editor'}</button>
              <div class="hbe-alerts"></div>
            </form>
          </div>
        </div>
      </div>

    </div>{* /tab-sections *}

    {* ── Karuzele ── *}
    <div id="hbe-tab-carousels">

      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title"><i class="icon-dashboard"></i> {l s='Wydajność karuzel produktowych' mod='hummingbird_editor'}</h3>
        </div>
        <div class="panel-body">
          <p class="text-muted" style="margin-bottom:1rem">
            {l s='Każda karuzela to wyszukiwanie produktów w kategorii i wyliczenie kart — przy kilkunastu sekcjach to główny koszt strony głównej. Gotowy HTML leży na dysku i odświeża się co zadany czas, a sekcje poniżej pierwszego ekranu doładowują się dopiero przy przewijaniu.' mod='hummingbird_editor'}
          </p>

          <div class="alert alert-info" style="margin-bottom:1rem">
            {l s='Stan cache:' mod='hummingbird_editor'}
            <strong id="hbe-cc-files">{$hbe_cc_stats.files|intval}</strong> {l s='wpisów' mod='hummingbird_editor'},
            <strong id="hbe-cc-size">{$hbe_cc_stats.size|escape:'html':'UTF-8'}</strong>,
            {l s='najstarszy sprzed' mod='hummingbird_editor'} <strong id="hbe-cc-age">{$hbe_cc_stats.age|escape:'html':'UTF-8'}</strong>
          </div>

          <form id="hbe-carousel-cache-form" method="post" action="{$hbe_ajax_url nofilter}" autocomplete="off">
            <input type="hidden" name="token" value="{$hbe_token}">

            <div class="row">
              <div class="col-md-6 form-group">
                <label class="control-label">
                  <input type="checkbox" name="enabled" value="1"{if $hbe_cc_enabled} checked{/if}>
                  {l s='Włącz cache karuzel' mod='hummingbird_editor'}
                </label>
                <p class="help-block">{l s='Wyłączenie oznacza przeliczanie każdej karuzeli przy każdym wejściu na stronę.' mod='hummingbird_editor'}</p>
              </div>
              <div class="col-md-6 form-group">
                <label class="control-label">
                  <input type="checkbox" name="lazy" value="1"{if $hbe_cc_lazy} checked{/if}>
                  {l s='Doładowuj karuzele przy przewijaniu' mod='hummingbird_editor'}
                </label>
                <p class="help-block">{l s='Sekcje poniżej progu poniżej trafiają do strony jako zajawka i dociągają treść, gdy zbliżą się do ekranu.' mod='hummingbird_editor'}</p>
              </div>
            </div>

            <div class="row">
              <div class="col-md-4 form-group">
                <label class="control-label">{l s='Odświeżaj co (godzin)' mod='hummingbird_editor'}</label>
                <input type="text" class="form-control" name="ttl_hours" value="{$hbe_cc_ttl_hours|escape:'html':'UTF-8'}">
                <p class="help-block">{l s='24 = raz dziennie. Po tym czasie pierwszy gość odbudowuje wpis — chyba że wyprzedzi go cron (poniżej).' mod='hummingbird_editor'}</p>
              </div>
              <div class="col-md-4 form-group">
                <label class="control-label">{l s='Ile karuzel w HTML strony' mod='hummingbird_editor'}</label>
                <input type="number" class="form-control" name="eager" min="0" max="20" value="{$hbe_cc_eager|intval}">
                <p class="help-block">{l s='Tyle pierwszych karuzel renderuje się od razu, żeby góra strony była kompletna. Reszta doładowuje się przy przewijaniu.' mod='hummingbird_editor'}</p>
              </div>
              <div class="col-md-4 form-group">
                <label class="control-label">{l s='Warianty karuzel losowych' mod='hummingbird_editor'}</label>
                <input type="number" class="form-control" name="variants" min="1" max="10" value="{$hbe_cc_variants|intval}">
                <p class="help-block">{l s='Karuzela z losową kolejnością trzyma tyle wersji i losuje między nimi — inaczej zamrożony HTML pokazywałby wszystkim to samo.' mod='hummingbird_editor'}</p>
              </div>
            </div>

            <button type="submit" class="btn btn-success"><i class="icon-save"></i> {l s='Zapisz ustawienia' mod='hummingbird_editor'}</button>
            <button type="button" id="hbe-cc-purge" class="btn btn-default"><i class="icon-trash"></i> {l s='Wyczyść cache teraz' mod='hummingbird_editor'}</button>
            <div class="hbe-alerts"></div>
          </form>

          <hr>

          <p class="text-muted" style="margin-bottom:.5rem">
            <strong>{l s='Odświeżanie z crona' mod='hummingbird_editor'}</strong> —
            {l s='żeby to nie pierwszy gość po wygaśnięciu cache czekał na odbudowę, wywołaj ten adres raz dziennie (dla każdego języka osobno, zmieniając prefiks w adresie):' mod='hummingbird_editor'}
          </p>
          <input type="text" class="form-control" readonly onclick="this.select()" value="{$hbe_cc_warm_url|escape:'html':'UTF-8'}">
          <p class="help-block">{l s='Adres zawiera klucz — nie publikuj go. Przykład wpisu crona: 15 4 * * * curl -s "…" > /dev/null' mod='hummingbird_editor'}</p>
        </div>
      </div>

      <div class="panel panel-default hbe-collapse-panel">
        <div class="panel-heading hbe-cp-head" data-toggle="collapse" data-target="#hbe-c-carousel">
          <h4 class="panel-title clearfix">
            {l s='Nagłówki sekcji karuzeli produktów' mod='hummingbird_editor'}
            <span class="pull-right">
              <i class="icon-chevron-down hbe-chevron{if !$hbe_s_carousel} hbe-chevron-open{/if}"></i>
            </span>
          </h4>
        </div>
        <div id="hbe-c-carousel" class="panel-collapse collapse{if !$hbe_s_carousel} in{/if}">
          <div class="panel-body">
            <p class="text-muted" style="margin-bottom:1.5rem">{l s='Tytuł, tekst i link wyświetlane nad każdą karuzelą. Pola opcjonalne — karuzela działa niezależnie od ich wypełnienia.' mod='hummingbird_editor'}</p>
            <form id="hbe-carousel-headers-form" method="post" action="{$hbe_ajax_url nofilter}" autocomplete="off">
              <input type="hidden" name="token" value="{$hbe_token}">

              <h4 class="clearfix" style="margin:0 0 .75rem;border-bottom:1px solid #eee;padding-bottom:.5rem">{l s='Karuzela nowości (ps_newproducts)' mod='hummingbird_editor'}{if $hbe_cfg_url_newproducts}<a class="hbe-cfg-link pull-right" href="{$hbe_cfg_url_newproducts|escape:'html':'UTF-8'}" target="_blank" rel="noopener"><i class="icon-cog"></i> {l s='Konfiguruj moduł' mod='hummingbird_editor'}</a>{/if}</h4>
              <div class="row">
                <div class="col-md-3 form-group">
                  <label class="control-label">{l s='Tytuł' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='np_title' values=$hbe_np_title_lang placeholder='np. Nowości'}
                </div>
                <div class="col-md-4 form-group">
                  <label class="control-label">{l s='Tekst' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='np_text' values=$hbe_np_text_lang placeholder='np. Odkryj najnowsze produkty'}
                </div>
                <div class="col-md-2 form-group">
                  <label class="control-label">{l s='Tekst linku' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='np_link_text' values=$hbe_np_link_text_lang placeholder='np. Więcej tkanin'}
                </div>
                <div class="col-md-3 form-group">
                  <label class="control-label">{l s='URL linku' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='np_link_url' values=$hbe_np_link_url_lang placeholder='https://...'}
                </div>
              </div>
              <div class="row">
                <div class="col-md-12 form-group hbe-source-group">
                  <label class="control-label">{l s='Źródło produktów' mod='hummingbird_editor'}</label>
                  <div class="hbe-catpick" data-hbe-catpick>
                    <input type="hidden" name="np_category_id" value="{$hbe_np_category_id|intval}" data-hbe-catpick-id>
                    <div class="input-group hbe-catpick-input">
                      <input type="text" class="form-control" data-hbe-catpick-search autocomplete="off"
                             placeholder="{l s='Domyślnie: produkty modułu — wpisz nazwę kategorii, aby je podmienić' mod='hummingbird_editor'}"
                             value="{$hbe_np_category_label|escape:'html':'UTF-8'}">
                      <span class="input-group-btn">
                        <button type="button" class="btn btn-default" data-hbe-catpick-clear title="{l s='Wróć do domyślnych produktów modułu' mod='hummingbird_editor'}"><i class="icon-remove"></i></button>
                      </span>
                    </div>
                    <ul class="hbe-catpick-menu" data-hbe-catpick-menu></ul>
                  </div>
                  <p class="help-block">{l s='Puste = domyślne produkty modułu. Wybór kategorii podmienia produkty tej karuzeli na produkty z niej (kolejność losowa).' mod='hummingbird_editor'}</p>
                </div>
              </div>

              <h4 class="clearfix" style="margin:.75rem 0;border-bottom:1px solid #eee;padding-bottom:.5rem">{l s='Karuzela bestsellerów (ps_bestsellers)' mod='hummingbird_editor'}{if $hbe_cfg_url_bestsellers}<a class="hbe-cfg-link pull-right" href="{$hbe_cfg_url_bestsellers|escape:'html':'UTF-8'}" target="_blank" rel="noopener"><i class="icon-cog"></i> {l s='Konfiguruj moduł' mod='hummingbird_editor'}</a>{/if}</h4>
              <div class="row">
                <div class="col-md-3 form-group">
                  <label class="control-label">{l s='Tytuł' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='bs_title' values=$hbe_bs_title_lang placeholder='np. Bestsellery'}
                </div>
                <div class="col-md-4 form-group">
                  <label class="control-label">{l s='Tekst' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='bs_text' values=$hbe_bs_text_lang placeholder='np. Najchętniej kupowane produkty'}
                </div>
                <div class="col-md-2 form-group">
                  <label class="control-label">{l s='Tekst linku' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='bs_link_text' values=$hbe_bs_link_text_lang placeholder='np. Pokaż bestsellery'}
                </div>
                <div class="col-md-3 form-group">
                  <label class="control-label">{l s='URL linku' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='bs_link_url' values=$hbe_bs_link_url_lang placeholder='https://...'}
                </div>
              </div>
              <div class="row">
                <div class="col-md-12 form-group hbe-source-group">
                  <label class="control-label">{l s='Źródło produktów' mod='hummingbird_editor'}</label>
                  <div class="hbe-catpick" data-hbe-catpick>
                    <input type="hidden" name="bs_category_id" value="{$hbe_bs_category_id|intval}" data-hbe-catpick-id>
                    <div class="input-group hbe-catpick-input">
                      <input type="text" class="form-control" data-hbe-catpick-search autocomplete="off"
                             placeholder="{l s='Domyślnie: produkty modułu — wpisz nazwę kategorii, aby je podmienić' mod='hummingbird_editor'}"
                             value="{$hbe_bs_category_label|escape:'html':'UTF-8'}">
                      <span class="input-group-btn">
                        <button type="button" class="btn btn-default" data-hbe-catpick-clear title="{l s='Wróć do domyślnych produktów modułu' mod='hummingbird_editor'}"><i class="icon-remove"></i></button>
                      </span>
                    </div>
                    <ul class="hbe-catpick-menu" data-hbe-catpick-menu></ul>
                  </div>
                  <p class="help-block">{l s='Puste = domyślne produkty modułu. Wybór kategorii podmienia produkty tej karuzeli na produkty z niej (kolejność losowa).' mod='hummingbird_editor'}</p>
                </div>
              </div>

              <h4 class="clearfix" style="margin:.75rem 0;border-bottom:1px solid #eee;padding-bottom:.5rem">{l s='Karuzela wybranej kategorii (ps_categoryproducts)' mod='hummingbird_editor'}{if $hbe_cfg_url_categoryproducts}<a class="hbe-cfg-link pull-right" href="{$hbe_cfg_url_categoryproducts|escape:'html':'UTF-8'}" target="_blank" rel="noopener"><i class="icon-cog"></i> {l s='Konfiguruj moduł' mod='hummingbird_editor'}</a>{/if}</h4>
              <div class="row">
                <div class="col-md-3 form-group">
                  <label class="control-label">{l s='Tytuł' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='cp_title' values=$hbe_cp_title_lang placeholder='np. Polecane z kategorii'}
                </div>
                <div class="col-md-4 form-group">
                  <label class="control-label">{l s='Tekst' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='cp_text' values=$hbe_cp_text_lang placeholder='np. Produkty z tej samej kategorii'}
                </div>
                <div class="col-md-2 form-group">
                  <label class="control-label">{l s='Tekst linku' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='cp_link_text' values=$hbe_cp_link_text_lang placeholder='np. Przeglądaj kategorię'}
                </div>
                <div class="col-md-3 form-group">
                  <label class="control-label">{l s='URL linku' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='cp_link_url' values=$hbe_cp_link_url_lang placeholder='https://...'}
                </div>
              </div>
              <div class="row">
                <div class="col-md-12 form-group hbe-source-group">
                  <label class="control-label">{l s='Źródło produktów' mod='hummingbird_editor'}</label>
                  <div class="hbe-catpick" data-hbe-catpick>
                    <input type="hidden" name="cp_category_id" value="{$hbe_cp_category_id|intval}" data-hbe-catpick-id>
                    <div class="input-group hbe-catpick-input">
                      <input type="text" class="form-control" data-hbe-catpick-search autocomplete="off"
                             placeholder="{l s='Domyślnie: kategoria oglądanego produktu — wpisz nazwę kategorii, aby ustawić stałą' mod='hummingbird_editor'}"
                             value="{$hbe_cp_category_label|escape:'html':'UTF-8'}">
                      <span class="input-group-btn">
                        <button type="button" class="btn btn-default" data-hbe-catpick-clear title="{l s='Wróć do kategorii oglądanego produktu' mod='hummingbird_editor'}"><i class="icon-remove"></i></button>
                      </span>
                    </div>
                    <ul class="hbe-catpick-menu" data-hbe-catpick-menu></ul>
                  </div>
                  <p class="help-block">{l s='Puste = produkty z kategorii oglądanego produktu (domyślne działanie modułu). Wybór kategorii ustawia stałą kategorię na każdej karcie produktu.' mod='hummingbird_editor'}</p>
                </div>
              </div>

              <button type="submit" class="btn btn-success"><i class="icon-save"></i> {l s='Zapisz nagłówki karuzeli' mod='hummingbird_editor'}</button>
              <div class="hbe-alerts"></div>
            </form>
          </div>
        </div>
      </div>

    </div>{* /tab-carousels *}

    {* ── Slider (ported from bemo_slider) ── *}
    <div id="hbe-tab-slider">
      {if $hbe_slider_mode == 'form'}
        {include file="{$hbe_tpl_dir}slider/add_form.tpl"}
      {else}
        {include file="{$hbe_tpl_dir}slider/list.tpl"}
      {/if}
    </div>{* /tab-slider *}

    {* ── displayHome: combined sortable (static elements + custom blocks) ── *}
    <div class="hbe-hook-group panel" data-hook="displayHome">
      <div class="hbe-hook-header panel-heading clearfix">
        <span class="hbe-hook-name">
          <i class="icon-plug"></i>
          {l s='Hook:' mod='hummingbird_editor'} <code>displayHome</code>
          <small class="text-muted" style="margin-left:8px;font-weight:normal">
            — {l s='przeciągnij wiersz, aby zmienić kolejność wyświetlania na stronie głównej' mod='hummingbird_editor'}
          </small>
        </span>
        <span class="label label-default pull-right">
          {$hbe_home_ordered|count} {l s='item(s)' mod='hummingbird_editor'}
        </span>
      </div>

      <ul class="hbe-sortable list-unstyled" data-hook="displayHome">
      {foreach from=$hbe_home_ordered item=hbItem}
        {if $hbItem.kind === 'static'}
        <li class="hbe-block-row hbe-static-row" data-id="{$hbItem.id}">
          <div class="hbe-row-header clearfix">
            <span class="hbe-handle" title="{l s='Drag to reorder' mod='hummingbird_editor'}">
              <i class="icon-reorder"></i>
            </span>
            <span class="hbe-type-badge" style="background:#e8f4ff;color:#0a5d8a;border-color:#b3d9f5">
              <i class="icon-home"></i> {l s='Wbudowany:' mod='hummingbird_editor'} <strong>{$hbItem.label}</strong>
            </span>
            <span class="text-muted" style="font-size:0.8em;margin-left:8px">
              {l s='(skonfiguruj w panelu powyżej)' mod='hummingbird_editor'}
            </span>
            <div class="hbe-row-actions pull-right">
              <button class="btn btn-xs btn-default hbe-clone-static"
                      data-slug="{$hbItem.id|escape:'html':'UTF-8'}"
                      title="{l s='Duplikuj tę sekcję jako nowy blok' mod='hummingbird_editor'}">
                <i class="icon-copy"></i> {l s='Duplikuj' mod='hummingbird_editor'}
              </button>
            </div>
          </div>
        </li>
        {elseif $hbItem.kind === 'module'}
        <li class="hbe-block-row hbe-module-row" data-id="{$hbItem.id}">
          <div class="hbe-row-header clearfix">
            <span class="hbe-handle" title="{l s='Drag to reorder' mod='hummingbird_editor'}">
              <i class="icon-reorder"></i>
            </span>
            <span class="hbe-type-badge hbe-type-module">
              <i class="icon-puzzle-piece"></i> {l s='Moduł:' mod='hummingbird_editor'} <strong>{$hbItem.module.display_name|escape:'html':'UTF-8'}</strong>
            </span>
            <code class="text-muted" style="font-size:0.75em;margin-left:6px">{$hbItem.module.name|escape:'html':'UTF-8'}</code>
            {if !$hbItem.module.active}
              <span class="label label-warning" style="margin-left:6px;font-size:10px">{l s='nieaktywny' mod='hummingbird_editor'}</span>
            {/if}
            <div class="hbe-row-actions pull-right">
              <button class="btn btn-xs btn-default hbe-release-module-btn"
                      data-module="{$hbItem.module.name|escape:'html':'UTF-8'}"
                      title="{l s='Zwróć moduł do systemu PS (odepnij od HBE)' mod='hummingbird_editor'}">
                <i class="icon-sign-out"></i> {l s='Zwolnij' mod='hummingbird_editor'}
              </button>
            </div>
          </div>
        </li>
        {else}
        {assign var=block value=$hbItem.block}
        {include file=$hbe_tpl_dir|cat:'_block_row.tpl'}
        {/if}
      {/foreach}
      </ul>

      {* ── Available modules on displayHome (not yet managed by HBE) ────── *}
      {if $hbe_available_modules|count > 0}
      <div class="hbe-available-modules">
        <strong><i class="icon-puzzle-piece"></i> {l s='Moduły na hooku displayHome (dostępne do zarządzania):' mod='hummingbird_editor'}</strong>
        <div class="hbe-available-modules-list">
          {foreach from=$hbe_available_modules item=avMod}
          <div class="hbe-available-module-item{if !$avMod.active} inactive{/if}">
            <i class="icon-puzzle-piece text-muted"></i>
            <span><strong>{$avMod.display_name|escape:'html':'UTF-8'}</strong> <code style="font-size:10px">{$avMod.name|escape:'html':'UTF-8'}</code></span>
            {if !$avMod.active}<span class="text-muted">{l s='(nieaktywny)' mod='hummingbird_editor'}</span>{/if}
            <button class="btn btn-xs btn-primary hbe-add-module-btn"
                    data-module="{$avMod.name|escape:'html':'UTF-8'}"
                    title="{l s='Przenieś pod kontrolę HBE i dodaj do listy kolejności' mod='hummingbird_editor'}">
              <i class="icon-plus"></i> {l s='Zarządzaj' mod='hummingbird_editor'}
            </button>
          </div>
          {/foreach}
        </div>
        <p class="text-muted" style="font-size:11px;margin-top:6px">
          {l s='Kliknij „Zarządzaj" aby odpiąć moduł od hooka PS i włączyć go do listy kolejności powyżej. Można go w każdej chwili zwolnić przyciskiem „Zwolnij".' mod='hummingbird_editor'}
        </p>
      </div>
      {/if}
    </div>{* /displayHome hook group *}

    </div>{* /hbe-tab-home *}

    {* ═══════════════════════════════════════════════════════════════════════
       Tab — Koszyk (podgląd koszyka / darmowa dostawa)
    ═══════════════════════════════════════════════════════════════════════ *}
    <div id="hbe-tab-cart" class="tab-pane" role="tabpanel">

      <div class="panel panel-default hbe-collapse-panel">
        <div class="panel-heading hbe-cp-head" data-toggle="collapse" data-target="#hbe-c-cart">
          <h4 class="panel-title clearfix">
            {l s='Podgląd koszyka' mod='hummingbird_editor'}
            <span class="pull-right"><i class="icon-chevron-down hbe-chevron hbe-chevron-open"></i></span>
          </h4>
        </div>
        <div id="hbe-c-cart" class="panel-collapse collapse in">
          <div class="panel-body">
            <p class="help-block">
              {l s='Nowy modal koszyka z paskiem „do darmowej dostawy”. Kwotę na pasku ustawiasz niżej — domyślnie jest czytana z progów przewoźników (Sprzedaż → Przewoźnicy → Koszty wysyłki), więc nie rozjedzie się z tym, co naliczy kasa.' mod='hummingbird_editor'}
            </p>
            <form id="hbe-cart-form" method="post" action="{$hbe_ajax_url nofilter}" autocomplete="off">
              <input type="hidden" name="token" value="{$hbe_token}">
              <table class="table">
                <tbody>
                  <tr>
                    <td>
                      <strong>{l s='Podgląd po najechaniu na ikonę koszyka' mod='hummingbird_editor'}</strong>
                      <div class="help-block">{l s='Pokazuje panel z zawartością koszyka i paskiem darmowej dostawy po najechaniu na ikonę koszyka w nagłówku.' mod='hummingbird_editor'}</div>
                    </td>
                    <td style="width:90px;text-align:right;vertical-align:middle">
                      <input type="checkbox" name="cart_hover" value="1" {if $hbe_cart_hover}checked{/if}>
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <strong>{l s='Użyj jako modal po dodaniu do koszyka' mod='hummingbird_editor'}</strong>
                      <div class="help-block">{l s='Po dodaniu produktu pokazuje ten podgląd koszyka zamiast standardowego okna „Dodano do koszyka".' mod='hummingbird_editor'}</div>
                    </td>
                    <td style="width:90px;text-align:right;vertical-align:middle">
                      <input type="checkbox" name="cart_preview_modal" value="1" {if $hbe_cart_preview_modal}checked{/if}>
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <strong>{l s='Próg darmowej dostawy — skąd brać kwotę' mod='hummingbird_editor'}</strong>
                      <div class="help-block">
                        {l s='Kwota pokazywana na pasku „do darmowej dostawy”. Zalecane: z przewoźników — pasek trzyma się wtedy kwoty, którą naprawdę nalicza kasa.' mod='hummingbird_editor'}
                      </div>
                    </td>
                    <td style="width:260px;text-align:right;vertical-align:middle">
                      <select name="cart_free_shipping_mode" id="hbe-free-ship-mode" class="form-control">
                        <option value="auto"{if $hbe_cart_free_ship_mode == 'auto'} selected{/if}>
                          {l s='Automatycznie z przewoźników' mod='hummingbird_editor'}
                          {if $hbe_cart_free_ship_detected > 0}({$hbe_cart_free_ship_detected|string_format:"%.2f"}){/if}
                        </option>
                        <option value="manual"{if $hbe_cart_free_ship_mode == 'manual'} selected{/if}>
                          {l s='Ręcznie — kwota poniżej' mod='hummingbird_editor'}
                        </option>
                        <option value="shop"{if $hbe_cart_free_ship_mode == 'shop'} selected{/if}>
                          {l s='Z ustawień sklepu' mod='hummingbird_editor'}
                          ({$hbe_cart_free_ship_shop|string_format:"%.2f"})
                        </option>
                        <option value="off"{if $hbe_cart_free_ship_mode == 'off'} selected{/if}>
                          {l s='Nie pokazuj paska' mod='hummingbird_editor'}
                        </option>
                      </select>
                    </td>
                  </tr>
                  <tr id="hbe-free-ship-manual-row"{if $hbe_cart_free_ship_mode != 'manual'} style="display:none"{/if}>
                    <td>
                      <strong>{l s='Próg darmowej dostawy (ręczny)' mod='hummingbird_editor'}</strong>
                      <div class="help-block">{l s='Wartość w walucie domyślnej sklepu. Używana tylko w trybie „Ręcznie”.' mod='hummingbird_editor'}</div>
                    </td>
                    <td style="width:260px;text-align:right;vertical-align:middle">
                      <input type="number" step="0.01" min="0" name="cart_free_shipping_threshold" value="{$hbe_cart_free_ship_manual}" class="form-control text-right">
                    </td>
                  </tr>
                  <tr>
                    <td colspan="2">
                      {if $hbe_cart_free_ship_effective > 0}
                        <div class="alert alert-info" style="margin-bottom:0">
                          {l s='Pasek pokazuje teraz: darmowa dostawa od' mod='hummingbird_editor'}
                          <strong>{$hbe_cart_free_ship_effective|string_format:"%.2f"}</strong>
                          {if $hbe_cart_free_ship_detected > 0 && $hbe_cart_free_ship_effective != $hbe_cart_free_ship_detected}
                            <br>
                            <i class="icon-warning-sign"></i>
                            {l s='Uwaga: przewoźnicy wożą za darmo dopiero od' mod='hummingbird_editor'}
                            <strong>{$hbe_cart_free_ship_detected|string_format:"%.2f"}</strong>
                            — {l s='pasek obiecuje klientowi inną kwotę niż naliczy kasa.' mod='hummingbird_editor'}
                          {/if}
                        </div>
                      {else}
                        <div class="alert alert-warning" style="margin-bottom:0">
                          {l s='Pasek darmowej dostawy jest ukryty (brak progu do pokazania).' mod='hummingbird_editor'}
                        </div>
                      {/if}
                    </td>
                  </tr>
                </tbody>
              </table>
              <button type="submit" class="btn btn-success"><i class="icon-save"></i> {l s='Zapisz' mod='hummingbird_editor'}</button>
              <div class="hbe-alerts"></div>
            </form>
          </div>
        </div>
      </div>

      <div class="panel panel-default hbe-collapse-panel">
        <div class="panel-heading hbe-cp-head" data-toggle="collapse" data-target="#hbe-c-care">
          <h4 class="panel-title clearfix">
            {l s='Rosenthal Care (blok w koszyku)' mod='hummingbird_editor'}
            <span class="pull-right"><i class="icon-chevron-down hbe-chevron hbe-chevron-open"></i></span>
          </h4>
        </div>
        <div id="hbe-c-care" class="panel-collapse collapse in">
          <div class="panel-body">
            <p class="help-block">
              {l s='Blok promujący usługę Rosenthal Care w koszyku (pod listą produktów). Przycisk dodaje wskazany produkt do koszyka. Blok ukrywa się, gdy produkt jest już w koszyku.' mod='hummingbird_editor'}
            </p>
            <form id="hbe-care-form" method="post" action="{$hbe_ajax_url nofilter}" autocomplete="off">
              <input type="hidden" name="token" value="{$hbe_token}">
              <table class="table">
                <tbody>
                  <tr>
                    <td><strong>{l s='Włącz blok Rosenthal Care' mod='hummingbird_editor'}</strong></td>
                    <td style="width:90px;text-align:right;vertical-align:middle">
                      <input type="checkbox" name="care_enabled" value="1" {if $hbe_care_enabled}checked{/if}>
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <strong>{l s='ID produktu Rosenthal Care' mod='hummingbird_editor'}</strong>
                      <div class="help-block">{l s='Produkt dodawany do koszyka po kliknięciu przycisku.' mod='hummingbird_editor'}</div>
                    </td>
                    <td style="width:140px;text-align:right;vertical-align:middle">
                      <input type="number" step="1" min="0" name="care_product_id" value="{$hbe_care_product_id}" class="form-control text-right">
                    </td>
                  </tr>
                  <tr>
                    <td colspan="2">
                      <strong>{l s='Nagłówek' mod='hummingbird_editor'}</strong>
                      <input type="text" name="care_heading" value="{$hbe_care_heading|escape:'html':'UTF-8'}" class="form-control">
                    </td>
                  </tr>
                  <tr>
                    <td colspan="2">
                      <strong>{l s='Opis (każda linia = osobny akapit)' mod='hummingbird_editor'}</strong>
                      <textarea name="care_text" rows="4" class="form-control">{$hbe_care_text|escape:'html':'UTF-8'}</textarea>
                    </td>
                  </tr>
                  <tr>
                    <td colspan="2">
                      <strong>{l s='Tekst przycisku' mod='hummingbird_editor'}</strong>
                      <input type="text" name="care_button" value="{$hbe_care_button|escape:'html':'UTF-8'}" class="form-control">
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <strong>{l s='Wymagaj zalogowania' mod='hummingbird_editor'}</strong>
                      <div class="help-block">{l s='Gdy włączone, niezalogowani widzą przycisk „Zaloguj się" zamiast dodania do koszyka.' mod='hummingbird_editor'}</div>
                    </td>
                    <td style="width:90px;text-align:right;vertical-align:middle">
                      <input type="checkbox" name="care_login_required" value="1" {if $hbe_care_login_required}checked{/if}>
                    </td>
                  </tr>
                </tbody>
              </table>
              <button type="submit" class="btn btn-success"><i class="icon-save"></i> {l s='Zapisz' mod='hummingbird_editor'}</button>
              <div class="hbe-alerts"></div>
            </form>
          </div>
        </div>
      </div>

    </div>{* /tab-cart *}

    {* ═══════════════════════════════════════════════════════════════════════
       Tab — Kasa (kroki zamówienia)
    ═══════════════════════════════════════════════════════════════════════ *}
    <div id="hbe-tab-checkout" class="tab-pane" role="tabpanel">

      <div class="panel panel-default hbe-collapse-panel">
        <div class="panel-heading hbe-cp-head" data-toggle="collapse" data-target="#hbe-c-checkout">
          <h4 class="panel-title clearfix">
            {l s='Kroki zamówienia' mod='hummingbird_editor'}
            <span class="pull-right"><i class="icon-chevron-down hbe-chevron hbe-chevron-open"></i></span>
          </h4>
        </div>
        <div id="hbe-c-checkout" class="panel-collapse collapse in">
          <div class="panel-body">
            <p class="help-block">
              {l s='Ustawienia dotyczą wbudowanej kasy PrestaShopa (kroki „Przesyłka”, „Płatność”, podsumowanie). Wszystkie są domyślnie wyłączone — sklep bez nich wygląda dokładnie tak, jak daje motyw. Jeśli zamówienie obsługuje osobny moduł kasy (np. jednostronicowa), te przełączniki go nie dotyczą.' mod='hummingbird_editor'}
            </p>
            <form id="hbe-checkout-form" method="post" action="{$hbe_ajax_url nofilter}" autocomplete="off">
              <input type="hidden" name="token" value="{$hbe_token}">
              <table class="table">
                <tbody>
                  <tr>
                    <td>
                      <strong>{l s='Nowy wygląd kroków kasy' mod='hummingbird_editor'}</strong>
                      <div class="help-block">
                        {l s='Lista przewoźników i metod płatności jako wiersze w jednej ramce, z zaznaczeniem wybranej opcji, uporządkowanymi logotypami i czytelnym podsumowaniem przed płatnością.' mod='hummingbird_editor'}
                      </div>
                    </td>
                    <td style="width:90px;text-align:right;vertical-align:middle">
                      <input type="checkbox" name="checkout_skin" value="1" {if $hbe_checkout_skin}checked{/if}>
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <strong>{l s='Ukończone kroki widoczne pod bieżącym' mod='hummingbird_editor'}</strong>
                      <div class="help-block">
                        {l s='Kasa przestaje chować kroki, które klient ma już za sobą — „Dane osobowe”, „Adresy” i „Przesyłka” zostają otwarte jeden pod drugim. Ma sens tylko przy kasie pomyślanej jako jedna strona; przy zwykłej kasie krokowej robi się z tego długa lista sekcji.' mod='hummingbird_editor'}
                      </div>
                    </td>
                    <td style="width:90px;text-align:right;vertical-align:middle">
                      <input type="checkbox" name="checkout_onepage" value="1" {if $hbe_checkout_onepage}checked{/if}>
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <strong>{l s='Zgody tuż nad przyciskiem „Złóż zamówienie”' mod='hummingbird_editor'}</strong>
                      <div class="help-block">
                        {l s='Akceptacja regulaminu przenosi się spod listy metod płatności na sam dół — pod podsumowanie zamówienia, bezpośrednio nad przycisk. Klient zaznacza zgodę tam, gdzie ją zatwierdza.' mod='hummingbird_editor'}
                      </div>
                    </td>
                    <td style="width:90px;text-align:right;vertical-align:middle">
                      <input type="checkbox" name="checkout_terms_bottom" value="1" {if $hbe_checkout_terms_bottom}checked{/if}>
                    </td>
                  </tr>
                </tbody>
              </table>
                  <tr>
                    <td>
                      <strong>{l s='Odbiór osobisty zamiast „Za darmo!”' mod='hummingbird_editor'}</strong>
                      <div class="help-block">
                        {l s='Zaznacz przewoźników, którzy są odbiorem osobistym. Gdy taka dostawa nic nie kosztuje, klient zamiast samego „Za darmo!” zobaczy „Darmowy odbiór osobisty” — przy wyborze przewoźnika, w podsumowaniu zamówienia i w wierszu „Wysyłka” w koszyku. Zwykłych przewoźników to nie dotyczy: darmowa wysyłka z progu kwotowego dalej pokazuje się jako „Za darmo!”.' mod='hummingbird_editor'}
                      </div>
                      <div class="hbe-pickup-carriers" style="margin-top:8px">
                        {if $hbe_carriers|@count}
                          {foreach from=$hbe_carriers item=carrier}
                            <label class="checkbox-inline" style="display:block;margin:0 0 4px">
                              <input type="checkbox" name="pickup_carriers[]" value="{$carrier.reference|intval}"
                                {if $carrier.selected}checked{/if}>
                              {$carrier.name|escape:'html':'UTF-8'}
                              {if !$carrier.active}<span class="text-muted"> — {l s='wyłączony' mod='hummingbird_editor'}</span>{/if}
                            </label>
                          {/foreach}
                        {else}
                          <span class="text-muted">{l s='Sklep nie ma jeszcze żadnego przewoźnika.' mod='hummingbird_editor'}</span>
                        {/if}
                      </div>
                    </td>
                    <td style="width:90px"></td>
                  </tr>
              <button type="submit" class="btn btn-success"><i class="icon-save"></i> {l s='Zapisz' mod='hummingbird_editor'}</button>
              <div class="hbe-alerts"></div>
            </form>
          </div>
        </div>
      </div>

    </div>{* /tab-checkout *}

    {* ═══════════════════════════════════════════════════════════════════════
       Tab — Karta produktu
    ═══════════════════════════════════════════════════════════════════════ *}
    <div id="hbe-tab-productcard" class="tab-pane" role="tabpanel">

      <div class="panel panel-default hbe-collapse-panel">
        <div class="panel-heading hbe-cp-head" data-toggle="collapse" data-target="#hbe-c-productcard">
          <h4 class="panel-title clearfix">
            {l s='Opis pod ceną (summary)' mod='hummingbird_editor'}
            <span class="pull-right"><i class="icon-chevron-down hbe-chevron hbe-chevron-open"></i></span>
          </h4>
        </div>
        <div id="hbe-c-productcard" class="panel-collapse collapse in">
          <div class="panel-body">
            <p class="help-block">
              {l s='Na karcie produktu, pod ceną, wyświetlany jest opis przycięty do 3 linii z przyciskiem „zobacz pełny opis", który rozwija resztę. Tutaj wybierasz, który opis tam trafia. Pełny opis w sekcji „Opis produktu" na dole strony pozostaje bez zmian.' mod='hummingbird_editor'}
            </p>
            <form id="hbe-productcard-form" method="post" action="{$hbe_ajax_url nofilter}" autocomplete="off">
              <input type="hidden" name="token" value="{$hbe_token}">
              <table class="table">
                <tbody>
                  <tr>
                    <td>
                      <strong>{l s='Źródło opisu pod ceną' mod='hummingbird_editor'}</strong>
                      <div class="help-block">{l s='„Standardowo" = zachowanie szablonu (krótki opis). „Pełny opis" przydaje się, gdy produkty nie mają uzupełnionego krótkiego opisu.' mod='hummingbird_editor'}</div>
                    </td>
                    <td style="width:260px;text-align:right;vertical-align:middle">
                      <select name="product_summary_source" class="form-control">
                        <option value="" {if $hbe_product_summary_source === ''}selected{/if}>{l s='Standardowo (z szablonu)' mod='hummingbird_editor'}</option>
                        <option value="short" {if $hbe_product_summary_source === 'short'}selected{/if}>{l s='Krótki opis produktu' mod='hummingbird_editor'}</option>
                        <option value="full" {if $hbe_product_summary_source === 'full'}selected{/if}>{l s='Pełny opis produktu' mod='hummingbird_editor'}</option>
                      </select>
                    </td>
                  </tr>
                </tbody>
              </table>
              <button type="submit" class="btn btn-success"><i class="icon-save"></i> {l s='Zapisz' mod='hummingbird_editor'}</button>
              <div class="hbe-alerts"></div>
            </form>
          </div>
        </div>
      </div>

      {* ══ Zoom na okladce ══════════════════════════════════════════════════ *}
      <div class="panel panel-default hbe-collapse-panel">
        <div class="panel-heading hbe-cp-head" data-toggle="collapse" data-target="#hbe-c-zoom">
          <h4 class="panel-title clearfix">
            {l s='Zoom zdjęcia (po najechaniu)' mod='hummingbird_editor'}
            <span class="pull-right"><i class="icon-chevron-down hbe-chevron hbe-chevron-open"></i></span>
          </h4>
        </div>
        <div id="hbe-c-zoom" class="panel-collapse collapse in">
          <div class="panel-body">
            <p class="help-block">
              {l s='Po najechaniu myszą na główne zdjęcie produktu powiększenie renderuje się w tej samej ramce — kursor przesuwa kadr. Nic nie wyskakuje poza obrys zdjęcia. Na telefonach i tabletach zoom się nie włącza: tam działa dotknięcie zdjęcia otwierające galerię.' mod='hummingbird_editor'}
            </p>
            <p class="help-block">
              {l s='Powiększenie bierze się z oryginału zdjęcia (do 2 MB) — miniatury są za wąskie, bo PrestaShop skaluje je do kwadratu i przy zdjęciach pionowych zostaje z nich ledwie tyle szerokości, ile ma ramka.' mod='hummingbird_editor'}
              {if $hbe_zoom_source}
                {l s='Gdy oryginał jest cięższy niż limit, zoom schodzi do miniatury' mod='hummingbird_editor'}
                <strong>{$hbe_zoom_source|escape:'html':'UTF-8'}</strong>
                ({$hbe_zoom_source_width|intval} px) — {l s='działa, tylko słabiej powiększa.' mod='hummingbird_editor'}
              {/if}
            </p>
            <form id="hbe-zoom-form" method="post" action="{$hbe_ajax_url nofilter}" autocomplete="off">
              <input type="hidden" name="token" value="{$hbe_token}">
              <table class="table">
                <tbody>
                  <tr>
                    <td>
                      <strong>{l s='Włącz zoom na karcie produktu' mod='hummingbird_editor'}</strong>
                    </td>
                    <td style="width:260px;text-align:right;vertical-align:middle">
                      <div class="checkbox" style="margin:0"><label>
                        <input type="checkbox" name="zoom_enabled" value="1" {if $hbe_zoom_enabled}checked{/if}>
                        {l s='Tak' mod='hummingbird_editor'}
                      </label></div>
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <strong>{l s='Siła powiększenia' mod='hummingbird_editor'}</strong>
                      <div class="help-block">{l s='„Naturalna" pokazuje zdjęcie piksel w piksel — najostrzej, jak pozwala plik. Stałe wartości powiększają mocniej, ale przy małych zdjęciach obraz zacznie się rozmywać.' mod='hummingbird_editor'}</div>
                    </td>
                    <td style="width:260px;text-align:right;vertical-align:middle">
                      <select name="zoom_level" class="form-control">
                        <option value="0" {if $hbe_zoom_level === '0'}selected{/if}>{l s='Naturalna rozdzielczość zdjęcia' mod='hummingbird_editor'}</option>
                        <option value="2" {if $hbe_zoom_level === '2'}selected{/if}>{l s='2×' mod='hummingbird_editor'}</option>
                        <option value="2.5" {if $hbe_zoom_level === '2.5'}selected{/if}>{l s='2,5×' mod='hummingbird_editor'}</option>
                        <option value="3" {if $hbe_zoom_level === '3'}selected{/if}>{l s='3×' mod='hummingbird_editor'}</option>
                      </select>
                    </td>
                  </tr>
                </tbody>
              </table>
              <button type="submit" class="btn btn-success"><i class="icon-save"></i> {l s='Zapisz' mod='hummingbird_editor'}</button>
              <div class="hbe-alerts"></div>
            </form>
          </div>
        </div>
      </div>

      {* ══ FAQ (below add-to-cart on product page) ══════════════════════════ *}
      <div class="panel panel-default">
        <div class="panel-heading"><h3 class="panel-title"><i class="icon-question-sign"></i> {l s='Sekcja FAQ (karta produktu)' mod='hummingbird_editor'}</h3></div>
        <div class="panel-body">

          <form id="hbe-faq-form" autocomplete="off">

            <div class="form-group">
              <div class="checkbox">
                <label>
                  <input type="checkbox" id="hbe_faq_enabled" name="enabled" value="1"{if $hbe_faq_enabled} checked{/if}>
                  {l s='Włącz sekcję FAQ na karcie produktu' mod='hummingbird_editor'}
                </label>
              </div>
            </div>

            <hr>

            <div class="row">
              <div class="col-md-3">
                <div class="form-group">
                  <label>{l s='Tło sekcji' mod='hummingbird_editor'}</label>
                  <input type="color" class="form-control" name="HBE_FAQ_BG" value="{$hbe_faq_bg|escape:'html':'UTF-8'}">
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label>{l s='Kolor pytania' mod='hummingbird_editor'}</label>
                  <input type="color" class="form-control" name="HBE_FAQ_QUESTION_COLOR" value="{$hbe_faq_question_color|escape:'html':'UTF-8'}">
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label>{l s='Kolor odpowiedzi' mod='hummingbird_editor'}</label>
                  <input type="color" class="form-control" name="HBE_FAQ_ANSWER_COLOR" value="{$hbe_faq_answer_color|escape:'html':'UTF-8'}">
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label>{l s='Kolor separatora' mod='hummingbird_editor'}</label>
                  <input type="color" class="form-control" name="HBE_FAQ_BORDER_COLOR" value="{$hbe_faq_border_color|escape:'html':'UTF-8'}">
                </div>
              </div>
            </div>

            <hr>

            {* Language tabs for FAQ items *}
            <ul class="nav nav-tabs" role="tablist">
              {foreach from=$hbe_languages item=lang name=faqLang}
              <li role="presentation"{if $smarty.foreach.faqLang.first} class="active"{/if}>
                <a href="#hbe-faq-lang-{$lang.id_lang|intval}" data-toggle="tab" role="tab">{$lang.name|escape:'html':'UTF-8'}</a>
              </li>
              {/foreach}
            </ul>
            <div class="tab-content" style="padding-top:1rem">
              {foreach from=$hbe_languages item=lang name=faqLangContent}
              {assign var=faqLangId value=$lang.id_lang|intval}
              <div id="hbe-faq-lang-{$faqLangId}" class="tab-pane{if $smarty.foreach.faqLangContent.first} active{/if}" role="tabpanel">
                <div class="hbe-faq-builder" data-lang="{$faqLangId}">
                  {foreach from=$hbe_faq_items_lang[$faqLangId] item=faqRow name=faqRows}
                  <div class="hbe-faq-row">
                    <div class="form-group">
                      <label>{l s='Pytanie' mod='hummingbird_editor'}</label>
                      <input type="text" class="form-control hbe-faq-q" value="{$faqRow.q|escape:'html':'UTF-8'}" placeholder="{l s='Pytanie...' mod='hummingbird_editor'}">
                    </div>
                    <div class="form-group">
                      <label>{l s='Odpowiedź (HTML dozwolony)' mod='hummingbird_editor'}</label>
                      <textarea class="form-control hbe-faq-a" rows="4" placeholder="{l s='Odpowiedź...' mod='hummingbird_editor'}">{$faqRow.a|escape:'html':'UTF-8'}</textarea>
                    </div>
                    <button type="button" class="btn btn-xs btn-danger hbe-faq-remove-btn">{l s='Usuń' mod='hummingbird_editor'}</button>
                    <hr>
                  </div>
                  {/foreach}
                </div>
                <button type="button" class="btn btn-default hbe-faq-add-btn" data-lang="{$faqLangId}">
                  <i class="icon-plus"></i> {l s='Dodaj pytanie' mod='hummingbird_editor'}
                </button>
                <input type="hidden" class="hbe-faq-items-input" name="faq_items_{$faqLangId}" value="{$hbe_faq_items_lang_json[$faqLangId]|escape:'html':'UTF-8'}">
              </div>
              {/foreach}
            </div>

            <div class="form-group" style="margin-top:1.5rem">
              <button type="submit" class="btn btn-primary"><i class="icon-save"></i> {l s='Zapisz FAQ' mod='hummingbird_editor'}</button>
            </div>

          </form>
        </div>
      </div>

      {* ══ Image + text (below the description on product page) ════════════ *}
      <div class="panel panel-default">
        <div class="panel-heading"><h3 class="panel-title"><i class="icon-picture"></i> {l s='Sekcja Obraz + tekst (karta produktu)' mod='hummingbird_editor'}</h3></div>
        <div class="panel-body">
          <p class="text-muted" style="margin-bottom:1rem">{l s='Sekcja pod opisem produktu: panel z tytułem, opisem i przyciskiem po lewej, zdjęcie po prawej.' mod='hummingbird_editor'}</p>
          <form id="hbe-imgtext-form" method="post" action="{$hbe_ajax_url nofilter}" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" name="token" value="{$hbe_token}">
            <div class="row">
              <div class="col-md-2 form-group">
                <label class="control-label">{l s='Włącz' mod='hummingbird_editor'}</label>
                <div class="checkbox"><label>
                  <input type="checkbox" name="enabled" id="hbe-imgtext-enabled" value="1" {if $hbe_imgtext_enabled}checked{/if}>
                  {l s='Yes' mod='hummingbird_editor'}
                </label></div>
              </div>
              <div class="col-md-4 form-group">
                <label class="control-label">{l s='Tytuł' mod='hummingbird_editor'}</label>
                {include file="{$hbe_tpl_dir}_ml_input.tpl" name='title' values=$hbe_imgtext_title_lang placeholder='np. Sanssouci Elfenbein Gold'}
              </div>
              <div class="col-md-6 form-group">
                <label class="control-label">{l s='Opis' mod='hummingbird_editor'}</label>
                {include file="{$hbe_tpl_dir}_ml_input.tpl" name='desc' values=$hbe_imgtext_desc_lang placeholder='Krótki opis kolekcji'}
              </div>
            </div>
            <div class="row">
              <div class="col-md-3 form-group">
                <label class="control-label">{l s='Tekst przycisku' mod='hummingbird_editor'}</label>
                {include file="{$hbe_tpl_dir}_ml_input.tpl" name='cta_text' values=$hbe_imgtext_cta_text_lang placeholder='np. Zobacz całą kolekcję'}
              </div>
              <div class="col-md-4 form-group">
                <label class="control-label">{l s='Link przycisku' mod='hummingbird_editor'}</label>
                {include file="{$hbe_tpl_dir}_ml_input.tpl" name='cta_url' values=$hbe_imgtext_cta_url_lang placeholder='https://example.com/kolekcja'}
              </div>
              <div class="col-md-2 form-group">
                <label class="control-label">{l s='Tło panelu' mod='hummingbird_editor'}</label>
                <input type="color" class="form-control" name="HBE_IMGTEXT_BG" value="{$hbe_imgtext_bg|escape:'html':'UTF-8'}">
              </div>
            </div>
            <div class="row">
              <div class="col-md-3 form-group">
                <label class="control-label">{l s='Zdjęcia per język' mod='hummingbird_editor'}</label>
                <div class="checkbox"><label>
                  <input type="checkbox" name="ml_images" value="1" {if $hbe_imgtext_ml_images}checked{/if}>
                  {l s='Tak (osobne zdjęcia dla języków)' mod='hummingbird_editor'}
                </label></div>
              </div>
              <div class="col-md-6 form-group">
                <label class="control-label">{l s='Zdjęcie sekcji' mod='hummingbird_editor'}</label>
                {capture name=hbe_imgtext_help}{l s='Zalecany format: JPG/WebP, min. 1200×600 px.' mod='hummingbird_editor'}{/capture}
                {include file="{$hbe_tpl_dir}_ml_image.tpl"
                  name="image" dom_prefix="hbe-imgtext-img"
                  base_url=$hbe_imgtext_img_url
                  per_lang=$hbe_imgtext_image_lang per_lang_urls=$hbe_imgtext_image_lang_urls
                  delete_action="DeleteImgTextImage" help=$smarty.capture.hbe_imgtext_help
                  ml=$hbe_imgtext_ml_images mobile=1
                  mobile_base_url=$hbe_imgtext_img_mobile_url
                  mobile_per_lang_urls=$hbe_imgtext_image_mobile_lang_urls}
              </div>
            </div>
            <button type="submit" class="btn btn-success"><i class="icon-save"></i> {l s='Zapisz sekcję' mod='hummingbird_editor'}</button>
            <div class="hbe-alerts"></div>
          </form>
        </div>
      </div>

    </div>{* /tab-productcard *}

    {* ═══════════════════════════════════════════════════════════════════════
       Tab — Menu (układ płaski submenu)
    ═══════════════════════════════════════════════════════════════════════ *}
    <div id="hbe-tab-menu" class="tab-pane" role="tabpanel">

      <div class="panel panel-default hbe-collapse-panel">
        <div class="panel-heading hbe-cp-head" data-toggle="collapse" data-target="#hbe-c-menuflat">
          <h4 class="panel-title clearfix">
            {l s='Menu — układ rozwijanego panelu' mod='hummingbird_editor'}
            <span class="pull-right"><i class="icon-chevron-down hbe-chevron hbe-chevron-open"></i></span>
          </h4>
        </div>
        <div id="hbe-c-menuflat" class="panel-collapse collapse in">
          <div class="panel-body">
            <p class="text-muted" style="margin-bottom:1rem">
              {l s='Każda pozycja menu może mieć inny układ panelu na desktopie:' mod='hummingbird_editor'}
            </p>
            <ul class="text-muted" style="margin-bottom:1rem">
              <li><strong>{l s='Zakładki' mod='hummingbird_editor'}</strong> — {l s='lewa kolumna z podkategoriami, po prawej kafelki ze zdjęciami. Domyślny układ motywu; zostawia pusty panel dla podkategorii, które nie mają własnych dzieci.' mod='hummingbird_editor'}</li>
              <li><strong>{l s='Kafelki' mod='hummingbird_editor'}</strong> — {l s='bez lewej kolumny, same kafelki ze zdjęciami.' mod='hummingbird_editor'}</li>
              <li><strong>{l s='Kolumny' mod='hummingbird_editor'}</strong> — {l s='wszystkie podkategorie naraz jako lista w kolumnach. Dobre dla gałęzi szerokich i płytkich.' mod='hummingbird_editor'}</li>
              <li><strong>{l s='Kaskada' mod='hummingbird_editor'}</strong> — {l s='lista podkategorii, a obok panel z ich zawartością; schodzi tyle poziomów w głąb, ile ich naprawdę jest. Panel zostaje niski nawet przy dużym drzewie.' mod='hummingbird_editor'}</li>
            </ul>
            <p class="text-muted" style="margin-bottom:1rem">
              {l s='Kolumna „Mobile" jest niezależna — na telefonie działa tylko układ kafelkowy.' mod='hummingbird_editor'}
            </p>
            <form id="hbe-menuflat-form" method="post" action="{$hbe_ajax_url nofilter}" autocomplete="off">
              <input type="hidden" name="token" value="{$hbe_token}">
              {if $hbe_menu_top_items}
                <table class="table">
                  <thead>
                    <tr>
                      <th>{l s='Pozycja menu' mod='hummingbird_editor'}</th>
                      <th style="width:22rem">{l s='Układ na desktopie' mod='hummingbird_editor'}</th>
                      <th class="text-center" style="width:9rem">{l s='Mobile: kafelki' mod='hummingbird_editor'}</th>
                    </tr>
                  </thead>
                  <tbody>
                    {foreach from=$hbe_menu_top_items item=mi}
                      {assign var=miLayout value=$hbe_menu_layouts[$mi.id]|default:'tabs'}
                      <tr>
                        <td><strong>{$mi.label|escape:'html':'UTF-8'}</strong></td>
                        <td>
                          <select class="form-control hbe-menu-layout" data-item-id="{$mi.id|escape:'html':'UTF-8'}">
                            {foreach from=$hbe_menu_layout_choices key=lk item=ll}
                              <option value="{$lk|escape:'html':'UTF-8'}"{if $miLayout === $lk} selected{/if}>{$ll|escape:'html':'UTF-8'}</option>
                            {/foreach}
                          </select>
                        </td>
                        <td class="text-center"><input type="checkbox" name="flat_items_mobile[]" value="{$mi.id|escape:'html':'UTF-8'}"{if in_array($mi.id, $hbe_menu_flat_items_mobile)} checked{/if}></td>
                      </tr>
                    {/foreach}
                  </tbody>
                </table>
                <hr>
                <h4 style="margin-top:1.5rem">{l s='Pozycje menu — co ukryć, co wyróżnić' mod='hummingbird_editor'}</h4>
                <ul class="text-muted" style="margin-bottom:1rem">
                  <li><strong>{l s='Ukryj' mod='hummingbird_editor'}</strong> — {l s='pozycja znika z menu. Kategoria zostaje w sklepie, działa jej strona i linki. Ukrycie gałęzi ukrywa też wszystko, co pod nią wisi. Przydaje się, gdy ta sama kategoria jest już osobną pozycją górnego paska.' mod='hummingbird_editor'}</li>
                  <li><strong>{l s='Wyróżnij' mod='hummingbird_editor'}</strong> — {l s='oznacza pozycję jako najczęściej szukaną. Wygląd wybierasz niżej, jeden dla całego menu.' mod='hummingbird_editor'}</li>
                  <li><strong>{l s='Na początek' mod='hummingbird_editor'}</strong> — {l s='wyciąga pozycję na czoło listy, przed alfabet. Dla tych, które mają być pierwsze, bo są najważniejsze.' mod='hummingbird_editor'}</li>
                  <li><strong>{l s='Na koniec' mod='hummingbird_editor'}</strong> — {l s='spycha pozycję pod spód listy, poza alfabet. Dla „zbieraczy" w rodzaju „Inne tkaniny", które alfabetycznie lądują w środku.' mod='hummingbird_editor'}</li>
                </ul>

                <div class="form-group" style="max-width:34rem">
                  <label for="hbe-feat-style">{l s='Jak wyglądają wyróżnione pozycje' mod='hummingbird_editor'}</label>
                  <select class="form-control" id="hbe-feat-style" name="featured_style">
                    {foreach from=$hbe_menu_feat_styles key=fk item=fl}
                      <option value="{$fk|escape:'html':'UTF-8'}"{if $hbe_menu_feat_style === $fk} selected{/if}>{$fl|escape:'html':'UTF-8'}</option>
                    {/foreach}
                  </select>
                </div>

                {function name="hbeMenuTree" nodes=[] level=0}
                  {foreach from=$nodes item=node}
                    <tr>
                      <td style="padding-left:{$level*1.5+0.5}rem">
                        <span style="font-weight:{if $level}400{else}700{/if}">{$node.label|escape:'html':'UTF-8'}</span>
                      </td>
                      <td class="text-center"><input type="checkbox" name="hidden_items[]" value="{$node.path|escape:'html':'UTF-8'}"{if in_array($node.path, $hbe_menu_hidden)} checked{/if}></td>
                      <td class="text-center"><input type="checkbox" name="featured_items[]" value="{$node.path|escape:'html':'UTF-8'}"{if in_array($node.path, $hbe_menu_featured)} checked{/if}></td>
                      <td class="text-center"><input type="checkbox" name="top_items[]" value="{$node.path|escape:'html':'UTF-8'}"{if in_array($node.path, $hbe_menu_top)} checked{/if}></td>
                      <td class="text-center"><input type="checkbox" name="bottom_items[]" value="{$node.path|escape:'html':'UTF-8'}"{if in_array($node.path, $hbe_menu_bottom)} checked{/if}></td>
                    </tr>
                    {if $node.children|count}
                      {hbeMenuTree nodes=$node.children level=$level+1}
                    {/if}
                  {/foreach}
                {/function}

                {if $hbe_menu_tree}
                  <table class="table table-condensed">
                    <thead>
                      <tr>
                        <th>{l s='Pozycja' mod='hummingbird_editor'}</th>
                        <th class="text-center" style="width:7rem">{l s='Ukryj' mod='hummingbird_editor'}</th>
                        <th class="text-center" style="width:7rem">{l s='Wyróżnij' mod='hummingbird_editor'}</th>
                        <th class="text-center" style="width:7rem">{l s='Na początek' mod='hummingbird_editor'}</th>
                        <th class="text-center" style="width:7rem">{l s='Na koniec' mod='hummingbird_editor'}</th>
                      </tr>
                    </thead>
                    <tbody>
                      {hbeMenuTree nodes=$hbe_menu_tree level=0}
                    </tbody>
                  </table>
                {else}
                  <p class="text-warning">{l s='Nie udało się pobrać drzewa menu.' mod='hummingbird_editor'}</p>
                {/if}

                <button type="submit" class="btn btn-success" style="margin-top:1rem"><i class="icon-save"></i> {l s='Zapisz' mod='hummingbird_editor'}</button>
                <div class="hbe-alerts"></div>
              {else}
                <p class="text-warning">{l s='Nie udało się pobrać pozycji menu (moduł ps_mainmenu).' mod='hummingbird_editor'}</p>
              {/if}
            </form>
          </div>
        </div>
      </div>

    </div>{* /hbe-tab-menu *}

    {* ═══════════════════════════════════════════════════════════════════════
       Tab 6 — Ustawienia
    ═══════════════════════════════════════════════════════════════════════ *}
    {* ═══════════════════════════════════════════════════════════════════════
       Tab — Karta podarunkowa (placements: menu / footer / floating pill)
    ═══════════════════════════════════════════════════════════════════════ *}
    <div id="hbe-tab-giftcard" class="tab-pane" role="tabpanel">

      <div class="panel panel-default">
        <div class="panel-heading"><h3 class="panel-title"><i class="icon-gift"></i> {l s='Karta podarunkowa — gdzie pokazać link' mod='hummingbird_editor'}</h3></div>
        <div class="panel-body">
          <p class="text-muted" style="margin-bottom:1rem">
            {l s='Włącz wybrane miejsca, w których pojawi się link do karty podarunkowej. Każde miejsce działa niezależnie. Wspólny adres docelowy poniżej — puste pole = strona zakupu karty (giftcard/choicegiftcard).' mod='hummingbird_editor'}
          </p>

          <form id="hbe-giftcard-form" method="post" action="{$hbe_ajax_url nofilter}" autocomplete="off">
            <input type="hidden" name="token" value="{$hbe_token}">

            {* Wspólny adres docelowy *}
            <div class="form-group">
              <label class="control-label">{l s='Adres docelowy (wspólny)' mod='hummingbird_editor'}</label>
              {include file="{$hbe_tpl_dir}_ml_input.tpl" name='url' type='url' values=$hbe_giftcard_url_lang placeholder=$hbe_giftcard_url_default}
              <p class="help-block" style="margin-top:4px">
                {l s='Domyślnie (pole puste): strona zakupu karty' mod='hummingbird_editor'} — <code>{$hbe_giftcard_url_default|escape:'html':'UTF-8'}</code>.
                {l s='Alternatywnie strona opisowa CMS „Karta upominkowa”:' mod='hummingbird_editor'} <code>{$hbe_giftcard_cms_url|escape:'html':'UTF-8'}</code>
              </p>
            </div>

            <hr>

            {* 1) Menu główne *}
            <div class="panel panel-default hbe-collapse-panel">
              <div class="panel-heading hbe-cp-head" data-toggle="collapse" data-target="#hbe-c-gc-menu">
                <h4 class="panel-title clearfix">
                  {l s='Link w menu głównym' mod='hummingbird_editor'}
                  <span class="pull-right">
                    {if $hbe_giftcard_menu_enabled}<span class="label label-success hbe-status-badge">{l s='Włączone' mod='hummingbird_editor'}</span>{/if}
                    <i class="icon-chevron-down hbe-chevron{if $hbe_giftcard_menu_enabled} hbe-chevron-open{/if}"></i>
                  </span>
                </h4>
              </div>
              <div id="hbe-c-gc-menu" class="panel-collapse collapse{if $hbe_giftcard_menu_enabled} in{/if}">
                <div class="panel-body">
                  <div class="row">
                    <div class="col-md-3 form-group">
                      <label class="control-label">{l s='Włącz' mod='hummingbird_editor'}</label>
                      <div class="checkbox"><label>
                        <input type="checkbox" name="menu_enabled" value="1" {if $hbe_giftcard_menu_enabled}checked{/if}>
                        {l s='Tak — jako ostatnia pozycja w menu' mod='hummingbird_editor'}
                      </label></div>
                    </div>
                    <div class="col-md-6 form-group">
                      <label class="control-label">{l s='Etykieta' mod='hummingbird_editor'}</label>
                      {include file="{$hbe_tpl_dir}_ml_input.tpl" name='menu_label' values=$hbe_giftcard_menu_label_lang placeholder='Karta podarunkowa'}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {* 2) Stopka *}
            <div class="panel panel-default hbe-collapse-panel">
              <div class="panel-heading hbe-cp-head" data-toggle="collapse" data-target="#hbe-c-gc-footer">
                <h4 class="panel-title clearfix">
                  {l s='Blok w stopce' mod='hummingbird_editor'}
                  <span class="pull-right">
                    {if $hbe_giftcard_footer_enabled}<span class="label label-success hbe-status-badge">{l s='Włączone' mod='hummingbird_editor'}</span>{/if}
                    <i class="icon-chevron-down hbe-chevron{if $hbe_giftcard_footer_enabled} hbe-chevron-open{/if}"></i>
                  </span>
                </h4>
              </div>
              <div id="hbe-c-gc-footer" class="panel-collapse collapse{if $hbe_giftcard_footer_enabled} in{/if}">
                <div class="panel-body">
                  <div class="row">
                    <div class="col-md-3 form-group">
                      <label class="control-label">{l s='Włącz' mod='hummingbird_editor'}</label>
                      <div class="checkbox"><label>
                        <input type="checkbox" name="footer_enabled" value="1" {if $hbe_giftcard_footer_enabled}checked{/if}>
                        {l s='Tak — kolumna promocyjna w stopce' mod='hummingbird_editor'}
                      </label></div>
                    </div>
                    <div class="col-md-4 form-group">
                      <label class="control-label">{l s='Tytuł' mod='hummingbird_editor'}</label>
                      {include file="{$hbe_tpl_dir}_ml_input.tpl" name='footer_label' values=$hbe_giftcard_footer_label_lang placeholder='Karta podarunkowa'}
                    </div>
                    <div class="col-md-5 form-group">
                      <label class="control-label">{l s='Opis (jedno zdanie, opcjonalny)' mod='hummingbird_editor'}</label>
                      {include file="{$hbe_tpl_dir}_ml_input.tpl" name='footer_desc' type='textarea' rows=2 values=$hbe_giftcard_footer_desc_lang placeholder='Zawsze trafiony prezent — obdarowany sam wybierze, co pokocha.'}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {* 3) Pływający przycisk *}
            <div class="panel panel-default hbe-collapse-panel">
              <div class="panel-heading hbe-cp-head" data-toggle="collapse" data-target="#hbe-c-gc-float">
                <h4 class="panel-title clearfix">
                  {l s='Pływający przycisk (róg ekranu)' mod='hummingbird_editor'}
                  <span class="pull-right">
                    {if $hbe_giftcard_float_enabled}<span class="label label-success hbe-status-badge">{l s='Włączone' mod='hummingbird_editor'}</span>{/if}
                    <i class="icon-chevron-down hbe-chevron{if $hbe_giftcard_float_enabled} hbe-chevron-open{/if}"></i>
                  </span>
                </h4>
              </div>
              <div id="hbe-c-gc-float" class="panel-collapse collapse{if $hbe_giftcard_float_enabled} in{/if}">
                <div class="panel-body">
                  <div class="row">
                    <div class="col-md-3 form-group">
                      <label class="control-label">{l s='Włącz' mod='hummingbird_editor'}</label>
                      <div class="checkbox"><label>
                        <input type="checkbox" name="float_enabled" value="1" {if $hbe_giftcard_float_enabled}checked{/if}>
                        {l s='Tak — przypięty przycisk na każdej stronie' mod='hummingbird_editor'}
                      </label></div>
                    </div>
                    <div class="col-md-5 form-group">
                      <label class="control-label">{l s='Etykieta' mod='hummingbird_editor'}</label>
                      {include file="{$hbe_tpl_dir}_ml_input.tpl" name='float_label' values=$hbe_giftcard_float_label_lang placeholder='Karta podarunkowa'}
                    </div>
                    <div class="col-md-4 form-group">
                      <label class="control-label">{l s='Pozycja' mod='hummingbird_editor'}</label>
                      <select name="float_position" class="form-control">
                        <option value="right" {if $hbe_giftcard_float_position != 'left'}selected{/if}>{l s='Prawy dolny róg' mod='hummingbird_editor'}</option>
                        <option value="left" {if $hbe_giftcard_float_position == 'left'}selected{/if}>{l s='Lewy dolny róg' mod='hummingbird_editor'}</option>
                      </select>
                    </div>
                  </div>
                  <p class="help-block">{l s='Klient może zamknąć przycisk — wróci przy kolejnej wizycie.' mod='hummingbird_editor'}</p>
                </div>
              </div>
            </div>

            <button type="submit" class="btn btn-success"><i class="icon-save"></i> {l s='Zapisz' mod='hummingbird_editor'}</button>
            <div class="hbe-alerts"></div>
          </form>
        </div>
      </div>

    </div>{* /tab-giftcard *}

    <div id="hbe-tab-settings" class="tab-pane" role="tabpanel">

      <div class="panel panel-default hbe-collapse-panel">
        <div class="panel-heading hbe-cp-head" data-toggle="collapse" data-target="#hbe-c-toggles">
          <h4 class="panel-title clearfix">
            {l s='Widoczność elementów nagłówka' mod='hummingbird_editor'}
            <span class="pull-right"><i class="icon-chevron-down hbe-chevron hbe-chevron-open"></i></span>
          </h4>
        </div>
        <div id="hbe-c-toggles" class="panel-collapse collapse in">
          <div class="panel-body">
            <form id="hbe-toggles-form" method="post" action="{$hbe_ajax_url nofilter}" autocomplete="off">
              <input type="hidden" name="token" value="{$hbe_token}">
              <table class="table">
                <thead>
                  <tr>
                    <th></th>
                    <th>{l s='Desktop' mod='hummingbird_editor'}</th>
                    <th>{l s='Mobile' mod='hummingbird_editor'}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><strong>{l s='Ukryj selektor waluty' mod='hummingbird_editor'}</strong></td>
                    <td><input type="checkbox" name="hide_currency_desktop" value="1" {if $hbe_hide_currency_desktop}checked{/if}></td>
                    <td><input type="checkbox" name="hide_currency_mobile" value="1" {if $hbe_hide_currency_mobile}checked{/if}></td>
                  </tr>
                  <tr>
                    <td><strong>{l s='Ukryj selektor języka' mod='hummingbird_editor'}</strong></td>
                    <td><input type="checkbox" name="hide_language_desktop" value="1" {if $hbe_hide_language_desktop}checked{/if}></td>
                    <td><input type="checkbox" name="hide_language_mobile" value="1" {if $hbe_hide_language_mobile}checked{/if}></td>
                  </tr>
                  <tr>
                    <td><strong>{l s='Ukryj „Szybki podgląd" na miniaturce produktu' mod='hummingbird_editor'}</strong></td>
                    <td colspan="2">
                      <label style="margin:0">
                        <input type="checkbox" name="hide_quickview" value="1" {if $hbe_hide_quickview}checked{/if}>
                        {l s='Tak (ukryj wszędzie)' mod='hummingbird_editor'}
                      </label>
                    </td>
                  </tr>
                  <tr>
                    <td><strong>{l s='Podgląd ulubionych (wysuwana szuflada)' mod='hummingbird_editor'}</strong></td>
                    <td colspan="2">
                      <label style="margin:0">
                        <input type="checkbox" name="wishlist_preview" value="1" {if $hbe_wishlist_preview}checked{/if}>
                        {l s='Włącz (po kliknięciu serca w nagłówku i po dodaniu produktu do ulubionych)' mod='hummingbird_editor'}
                      </label>
                    </td>
                  </tr>
                </tbody>
              </table>
              <button type="submit" class="btn btn-success"><i class="icon-save"></i> {l s='Zapisz' mod='hummingbird_editor'}</button>
              <div class="hbe-alerts"></div>
            </form>
          </div>
        </div>
      </div>

      {* ── Ikony social media w stopce (kolumna „Kontakt") ─────────────────── *}
      <div class="panel panel-default hbe-collapse-panel">
        <div class="panel-heading hbe-cp-head" data-toggle="collapse" data-target="#hbe-c-social">
          <h4 class="panel-title clearfix">
            {l s='Social media (stopka)' mod='hummingbird_editor'}
            <span class="pull-right"><i class="icon-chevron-down hbe-chevron hbe-chevron-open"></i></span>
          </h4>
        </div>
        <div id="hbe-c-social" class="panel-collapse collapse in">
          <div class="panel-body">
            <p class="text-muted" style="margin-bottom:1rem">
              {l s='Ikony pokazują się w stopce, pod danymi kontaktowymi. Pusty adres = ikona ukryta. Adres musi być pełny, np. https://www.instagram.com/rosenthal_polska' mod='hummingbird_editor'}
            </p>
            <form id="hbe-social-form" method="post" action="{$hbe_ajax_url nofilter}" autocomplete="off">
              <input type="hidden" name="token" value="{$hbe_token}">
              {foreach from=$hbe_social key=hbeSocialKey item=hbeSocial}
                <div class="form-group">
                  <label class="control-label col-lg-2" for="hbe-social-{$hbeSocialKey}">{$hbeSocial.label}</label>
                  <div class="col-lg-8">
                    <input
                      type="url"
                      class="form-control"
                      id="hbe-social-{$hbeSocialKey}"
                      name="social_{$hbeSocialKey}"
                      value="{$hbeSocial.url|escape:'html':'UTF-8'}"
                      placeholder="https://">
                  </div>
                </div>
              {/foreach}
              <div class="clearfix"></div>
              <button type="submit" class="btn btn-success"><i class="icon-save"></i> {l s='Zapisz' mod='hummingbird_editor'}</button>
              <div class="hbe-alerts"></div>
            </form>
          </div>
        </div>
      </div>

    </div>{* /tab-settings *}

    {* ══ Listing — banery między rzędami produktów na kategoriach ═════════ *}
    <div id="hbe-tab-listing" class="tab-pane" role="tabpanel">

      <div class="panel panel-default">
        <div class="panel-heading"><h3 class="panel-title"><i class="icon-th-list"></i> {l s='Banery na listingu kategorii' mod='hummingbird_editor'}</h3></div>
        <div class="panel-body">
          <p class="text-muted" style="margin-bottom:1rem">
            {l s='Baner pojawia się po drugiej linii produktów na stronie kategorii. Każdy baner można przypisać do jednej lub wielu kategorii — pierwszy włączony baner pasujący do kategorii wygrywa. Pamiętaj o wersji mobilnej zdjęcia.' mod='hummingbird_editor'}
          </p>
          <form id="hbe-listban-form" method="post" action="{$hbe_ajax_url nofilter}" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" name="token" value="{$hbe_token}">

            {foreach from=$hbe_listban_slots item=slot}
            <div class="panel panel-default hbe-collapse-panel">
              <div class="panel-heading hbe-cp-head" data-toggle="collapse" data-target="#hbe-c-listban-{$slot.n}">
                <h4 class="panel-title clearfix">
                  {l s='Baner' mod='hummingbird_editor'} {$slot.n}
                  <span class="pull-right">
                    {if $slot.enabled}<span class="label label-success hbe-status-badge">{l s='Włączone' mod='hummingbird_editor'}</span>{/if}
                    <i class="icon-chevron-down hbe-chevron{if $slot.enabled} hbe-chevron-open{/if}"></i>
                  </span>
                </h4>
              </div>
              <div id="hbe-c-listban-{$slot.n}" class="panel-collapse collapse{if $slot.enabled} in{/if}">
                <div class="panel-body">
                  <div class="row">
                    <div class="col-md-2 form-group">
                      <label class="control-label">{l s='Włącz' mod='hummingbird_editor'}</label>
                      <div class="checkbox"><label>
                        <input type="checkbox" name="enabled_{$slot.n}" value="1" {if $slot.enabled}checked{/if}>
                        {l s='Yes' mod='hummingbird_editor'}
                      </label></div>
                    </div>
                    <div class="col-md-4 form-group">
                      <label class="control-label">{l s='Tytuł' mod='hummingbird_editor'}</label>
                      {include file="{$hbe_tpl_dir}_ml_input.tpl" name="title_{$slot.n}" values=$slot.title_lang placeholder='np. Wyróżniona kolekcja'}
                    </div>
                    <div class="col-md-3 form-group">
                      <label class="control-label">{l s='Tekst przycisku' mod='hummingbird_editor'}</label>
                      {include file="{$hbe_tpl_dir}_ml_input.tpl" name="cta_text_{$slot.n}" values=$slot.cta_text_lang placeholder='np. Zobacz produkty'}
                    </div>
                    <div class="col-md-3 form-group">
                      <label class="control-label">{l s='Link' mod='hummingbird_editor'}</label>
                      {include file="{$hbe_tpl_dir}_ml_input.tpl" name="url_{$slot.n}" values=$slot.url_lang placeholder='https://example.com/kolekcja'}
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-md-4 form-group">
                      <label class="control-label">{l s='Kategorie (ctrl+klik = wiele)' mod='hummingbird_editor'}</label>
                      <select name="cats_{$slot.n}[]" multiple size="8" class="form-control">
                        {foreach from=$hbe_all_categories item=cat}
                          <option value="{$cat.id_category|intval}"
                            {if in_array((int)$cat.id_category, $slot.cats)}selected{/if}>
                            {$cat.name|escape:'html':'UTF-8'} (#{$cat.id_category|intval})
                          </option>
                        {/foreach}
                      </select>
                    </div>
                    <div class="col-md-4 form-group">
                      <label class="control-label">{l s='Zdjęcie (desktop)' mod='hummingbird_editor'}</label>
                      <div id="hbe-listban-img-{$slot.n}-wrap" {if !$slot.img_url}style="display:none"{/if}>
                        <img id="hbe-listban-img-{$slot.n}-preview" src="{$slot.img_url|escape:'html':'UTF-8'}" alt="" style="max-width:100%;max-height:120px;margin-bottom:6px">
                        <button type="button" class="btn btn-xs btn-danger hbe-listban-del" data-slot="{$slot.n}" data-variant="desktop">{l s='Usuń' mod='hummingbird_editor'}</button>
                      </div>
                      <input type="file" name="HBE_LISTBAN_{$slot.n}_IMAGE" accept="image/*" class="form-control">
                      <p class="help-block">{l s='Zalecany format: JPG/WebP, min. 1600×500 px.' mod='hummingbird_editor'}</p>
                    </div>
                    <div class="col-md-4 form-group">
                      <label class="control-label">{l s='Zdjęcie (mobile)' mod='hummingbird_editor'}</label>
                      <div id="hbe-listban-img-m-{$slot.n}-wrap" {if !$slot.img_mobile_url}style="display:none"{/if}>
                        <img id="hbe-listban-img-m-{$slot.n}-preview" src="{$slot.img_mobile_url|escape:'html':'UTF-8'}" alt="" style="max-width:100%;max-height:120px;margin-bottom:6px">
                        <button type="button" class="btn btn-xs btn-danger hbe-listban-del" data-slot="{$slot.n}" data-variant="mobile">{l s='Usuń' mod='hummingbird_editor'}</button>
                      </div>
                      <input type="file" name="HBE_LISTBAN_{$slot.n}_IMAGE_MOBILE" accept="image/*" class="form-control">
                      <p class="help-block">{l s='Pionowy kadr na telefony, np. 800×1000 px.' mod='hummingbird_editor'}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            {/foreach}

            <button type="submit" class="btn btn-success"><i class="icon-save"></i> {l s='Zapisz banery' mod='hummingbird_editor'}</button>
            <div class="hbe-alerts"></div>
          </form>
        </div>
      </div>

    </div>{* /hbe-tab-listing *}

    {* ══ Miniatury — wyglad kafla produktu w calym sklepie ════════════════ *}
    <div id="hbe-tab-miniatures" class="tab-pane" role="tabpanel">

      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title"><i class="icon-th-large"></i> {l s='Wygląd miniatury produktu' mod='hummingbird_editor'}</h3>
        </div>
        <div class="panel-body">
          <p class="text-muted" style="margin-bottom:1rem">
            {l s='Ustawienia obejmują kafel produktu wszędzie, gdzie się pojawia: listing kategorii i wyników wyszukiwania, karuzele na stronie głównej, „Już obejrzane produkty" i produkty powiązane. Zacznij od gotowego zestawu, a potem dopraw szczegóły — każdą wartość widać od razu w podglądzie po prawej.' mod='hummingbird_editor'}
          </p>

          <form id="hbe-mini-form" method="post" action="{$hbe_ajax_url nofilter}" autocomplete="off"
                data-presets='{$hbe_mini_presets|escape:'html':'UTF-8'}'>
            <input type="hidden" name="token" value="{$hbe_token}">
            <input type="hidden" name="mini_enabled" id="hbe-mini-enabled" value="{$hbe_mini.enabled|intval}">

            {* ── Gotowe zestawy ─────────────────────────────────────────── *}
            <div class="hbe-mini-presets">
              {foreach from=$hbe_mini_cards item=card}
                {assign var=artCount value=$card.art.car_desktop}
                {if $artCount > 4}{assign var=artCount value=4}{/if}
                <button type="button" class="hbe-mini-preset{if $hbe_mini_preset === $card.key} is-active{/if}"
                        data-preset="{$card.key|escape:'html':'UTF-8'}">
                  {* Miniaturka rysuje sie z tych samych liczb, ktore zestaw
                     zapisuje — w skali 1:4, bo kafel na karcie jest tyle razy
                     wezszy od prawdziwego. *}
                  <span class="hbe-mini-preset__art" style="gap:{($card.art.gap/4)|round}px">
                    {for $i=1 to $artCount}
                      <span class="hbe-mini-preset__tile" style="{if $card.art.car_border}border:1px solid #dcdcdc;{/if}">
                        <span class="hbe-mini-preset__photo" style="
                          padding:{($card.art.pad/4)|round}px;
                          border-radius:{($card.art.radius/4)|round}px;
                          background-size:{if $card.art.ratio}{$card.art.fit|escape:'html':'UTF-8'}{else}cover{/if};
                          aspect-ratio:{if $card.art.ratio}{$card.art.ratio|escape:'html':'UTF-8'}{else}3/4{/if}"></span>
                      </span>
                    {/for}
                  </span>
                  <span class="hbe-mini-preset__name">{$card.name|escape:'html':'UTF-8'}</span>
                  <span class="hbe-mini-preset__desc">{$card.desc|escape:'html':'UTF-8'}</span>
                </button>
              {/foreach}
            </div>

            <p class="hbe-mini-state">
              {l s='Teraz ustawione:' mod='hummingbird_editor'}
              <strong id="hbe-mini-state-name" data-custom-label="{l s='ustawienia własne' mod='hummingbird_editor'}">
                {if $hbe_mini_preset === 'custom'}{l s='ustawienia własne' mod='hummingbird_editor'}
                {else}{foreach from=$hbe_mini_cards item=card}{if $card.key === $hbe_mini_preset}{$card.name|escape:'html':'UTF-8'}{/if}{/foreach}{/if}
              </strong>
              <span class="hbe-mini-state__hint">{l s='Zmiana dowolnego pola poniżej przełącza opis na „ustawienia własne" — zestawy to tylko punkt wyjścia.' mod='hummingbird_editor'}</span>
            </p>

            {* ── Szczegoly + podglad ────────────────────────────────────── *}
            <div class="row hbe-mini-body{if !$hbe_mini.enabled} is-theme{/if}" id="hbe-mini-body">
              <div class="col-md-7">

                <table class="table hbe-mini-table">
                  <tbody>
                    <tr>
                      <td>
                        <strong>{l s='Margines wokół zdjęcia' mod='hummingbird_editor'}</strong>
                        <div class="help-block">{l s='Motyw trzyma 40 px. Przy zerze zdjęcie idzie na całą szerokość kafla — to najprostszy sposób na większe zdjęcia bez ruszania siatki.' mod='hummingbird_editor'}</div>
                      </td>
                      <td class="hbe-mini-cell">
                        <select name="mini_pad" class="form-control">
                          {foreach from=[0,4,8,12,16,24,32,40] item=v}
                            <option value="{$v}" {if $hbe_mini.pad == $v}selected{/if}>{$v} px</option>
                          {/foreach}
                        </select>
                      </td>
                    </tr>
                    <tr>
                      <td>
                        <strong>{l s='Kadr zdjęcia' mod='hummingbird_editor'}</strong>
                        <div class="help-block">{l s='Wymuszona proporcja wyrównuje rzędy i blokuje przeskok siatki w chwili doładowania zdjęć. „Jak plik" zostawia proporcje takie, jakie mają zdjęcia w katalogu.' mod='hummingbird_editor'}</div>
                      </td>
                      <td class="hbe-mini-cell">
                        <select name="mini_ratio" class="form-control">
                          {foreach from=$hbe_mini_ratios key=rval item=rlabel}
                            <option value="{$rval|escape:'html':'UTF-8'}" {if $hbe_mini.ratio === $rval}selected{/if}>{$rlabel|escape:'html':'UTF-8'}</option>
                          {/foreach}
                        </select>
                      </td>
                    </tr>
                    <tr class="hbe-mini-row-fit">
                      <td>
                        <strong>{l s='Zdjęcie w kadrze' mod='hummingbird_editor'}</strong>
                        <div class="help-block">{l s='„Wypełnij" przycina to, co nie mieści się w kadrze — dobre do tkanin i zdjęć aranżacyjnych. „Zmieść w całości" nic nie obcina, ale zostawia puste pasy przy zdjęciach o innej proporcji.' mod='hummingbird_editor'}</div>
                      </td>
                      <td class="hbe-mini-cell">
                        <select name="mini_fit" class="form-control">
                          <option value="cover" {if $hbe_mini.fit === 'cover'}selected{/if}>{l s='Wypełnij kadr (przytnij)' mod='hummingbird_editor'}</option>
                          <option value="contain" {if $hbe_mini.fit === 'contain'}selected{/if}>{l s='Zmieść w całości' mod='hummingbird_editor'}</option>
                        </select>
                      </td>
                    </tr>
                    <tr>
                      <td><strong>{l s='Zaokrąglenie rogów zdjęcia' mod='hummingbird_editor'}</strong></td>
                      <td class="hbe-mini-cell">
                        <select name="mini_radius" class="form-control">
                          {foreach from=[0,4,8,12,16,24] item=v}
                            <option value="{$v}" {if $hbe_mini.radius == $v}selected{/if}>{$v} px</option>
                          {/foreach}
                        </select>
                      </td>
                    </tr>
                    <tr>
                      <td>
                        <strong>{l s='Przerwa między kaflami' mod='hummingbird_editor'}</strong>
                        <div class="help-block">{l s='Ta sama wartość rozdziela siatkę listingu i pas karuzeli, więc kafel wygląda wszędzie identycznie. Im mniejsza przerwa, tym większe zdjęcia przy tej samej liczbie kolumn.' mod='hummingbird_editor'}</div>
                      </td>
                      <td class="hbe-mini-cell">
                        <select name="mini_gap" class="form-control">
                          {foreach from=[0,4,8,12,16,24,32,40] item=v}
                            <option value="{$v}" {if $hbe_mini.gap == $v}selected{/if}>{$v} px</option>
                          {/foreach}
                        </select>
                      </td>
                    </tr>
                    <tr>
                      <td>
                        <strong>{l s='Kafle widoczne w karuzeli' mod='hummingbird_editor'}</strong>
                        <div class="help-block">{l s='Na stronie głównej karuzela dzieli wiersz z kolumną tytułu, więc 3 kafle wypadają mniej więcej tak szeroko jak kafel 4-kolumnowego listingu.' mod='hummingbird_editor'}</div>
                      </td>
                      <td class="hbe-mini-cell">
                        <div class="hbe-mini-pair">
                          <label>{l s='Komputer' mod='hummingbird_editor'}
                            <select name="mini_car_desktop" class="form-control">
                              {foreach from=[2,3,4,5,6] item=v}
                                <option value="{$v}" {if $hbe_mini.car_desktop == $v}selected{/if}>{$v}</option>
                              {/foreach}
                            </select>
                          </label>
                          <label>{l s='Telefon' mod='hummingbird_editor'}
                            <select name="mini_car_mobile" class="form-control">
                              {foreach from=[1,2,3] item=v}
                                <option value="{$v}" {if $hbe_mini.car_mobile == $v}selected{/if}>{$v}</option>
                              {/foreach}
                            </select>
                          </label>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td>
                        <strong>{l s='Kolumny na listingu' mod='hummingbird_editor'}</strong>
                        <div class="help-block">{l s='„Z motywu” zostawia siatkę taką, jaką daje układ strony. Wybrana liczba dotyczy ekranów od 1400 px; w zakresie 768–1400 px kolumn jest najwyżej 3, a na telefonie zawsze 2. Uwaga: przy widocznej kolumnie filtrów po lewej większa liczba kolumn zwęża kafle.' mod='hummingbird_editor'}</div>
                      </td>
                      <td class="hbe-mini-cell">
                        <select name="mini_list_cols" class="form-control">
                          <option value="0" {if $hbe_mini.list_cols == 0}selected{/if}>{l s='Z motywu (bez zmian)' mod='hummingbird_editor'}</option>
                          {foreach from=[2,3,4,5,6] item=v}
                            <option value="{$v}" {if $hbe_mini.list_cols == $v}selected{/if}>{$v}</option>
                          {/foreach}
                        </select>
                      </td>
                    </tr>
                    <tr>
                      <td>
                        <strong>{l s='Kreska wokół kafla karuzeli' mod='hummingbird_editor'}</strong>
                        <div class="help-block">{l s='Ramka 1 px z motywu. Ma sens przy zerowej przerwie, gdzie rozdziela sklejone kafle; przy wyraźnej przerwie zwykle przeszkadza.' mod='hummingbird_editor'}</div>
                      </td>
                      <td class="hbe-mini-cell">
                        <label class="hbe-mini-check">
                          <input type="checkbox" name="mini_car_border" value="1" {if $hbe_mini.car_border}checked{/if}>
                          {l s='Pokaż' mod='hummingbird_editor'}
                        </label>
                      </td>
                    </tr>
                    <tr>
                      <td>
                        <strong>{l s='Powiększenie zdjęcia po najechaniu' mod='hummingbird_editor'}</strong>
                        <div class="help-block">{l s='Zdjęcie powiększa się o 10 % w obrębie kadru. Kadr zawsze przycina to, co wychodzi poza kafel.' mod='hummingbird_editor'}</div>
                      </td>
                      <td class="hbe-mini-cell">
                        <label class="hbe-mini-check">
                          <input type="checkbox" name="mini_zoom" value="1" {if $hbe_mini.zoom}checked{/if}>
                          {l s='Włącz' mod='hummingbird_editor'}
                        </label>
                      </td>
                    </tr>
                  </tbody>
                </table>

              </div>

              <div class="col-md-5">
                <div class="hbe-mini-preview-wrap">
                  <div class="hbe-mini-preview-head">
                    {l s='Podgląd' mod='hummingbird_editor'}
                    <span id="hbe-mini-preview-note">{l s='pas karuzeli' mod='hummingbird_editor'}</span>
                  </div>
                  <div class="hbe-mini-preview" id="hbe-mini-preview">
                    {for $i=1 to 6}
                      <div class="hbe-mini-tile">
                        <div class="hbe-mini-tile__photo"></div>
                        <div class="hbe-mini-tile__name"></div>
                        <div class="hbe-mini-tile__price"></div>
                      </div>
                    {/for}
                  </div>
                  <p class="hbe-mini-preview-hint">
                    {l s='Podgląd rysuje kafel w proporcjach, jakie zobaczy klient — zdjęcie jest przykładowe.' mod='hummingbird_editor'}
                  </p>
                </div>
              </div>
            </div>

            <div class="hbe-mini-theme-note" id="hbe-mini-theme-note"{if $hbe_mini.enabled} style="display:none"{/if}>
              {l s='Wybrany jest wygląd z motywu — moduł nie dokłada żadnych stylów, a pola powyżej czekają bezczynnie. Kliknij inny zestaw albo zmień dowolne pole, żeby przejąć kontrolę nad kaflem.' mod='hummingbird_editor'}
            </div>

            <button type="submit" class="btn btn-success"><i class="icon-save"></i> {l s='Zapisz wygląd miniatur' mod='hummingbird_editor'}</button>
            <div class="hbe-alerts"></div>
          </form>
        </div>
      </div>

    </div>{* /hbe-tab-miniatures *}

  </div>{* /tab-content *}

  <div id="hbe-add-panel" class="hbe-panel panel" style="display:none">
    <div class="panel-heading">{l s='New block' mod='hummingbird_editor'}</div>
    <div class="panel-body">
      <form id="hbe-add-form" method="post" action="{$hbe_ajax_url nofilter}" autocomplete="off">
        <input type="hidden" name="token" value="{$hbe_token}">

        <div class="row">
          <div class="col-md-5 form-group">
            <label class="control-label required">{l s='Hook name' mod='hummingbird_editor'}</label>
            <input type="text" name="hook_name" id="hbe-add-hook" class="form-control"
                   list="hbe-hooks-datalist" placeholder="e.g. displayHome" required>
            <datalist id="hbe-hooks-datalist">
              {foreach from=$hbe_standard_hooks item=h}
                <option value="{$h}">
              {/foreach}
              {foreach from=$hbe_used_hooks item=h}
                <option value="{$h}">
              {/foreach}
            </datalist>
            <p class="help-block">{l s='Standard or custom hook name. Use {hook h=\'name\'} in templates.' mod='hummingbird_editor'}</p>
          </div>
          <div class="col-md-3 form-group">
            <label class="control-label required">{l s='Block type' mod='hummingbird_editor'}</label>
            <select name="type" class="form-control">
              <option value="text">{l s='Text / HTML (WYSIWYG)' mod='hummingbird_editor'}</option>
              <option value="image">{l s='Image' mod='hummingbird_editor'}</option>
              <option value="html">{l s='Raw HTML / code' mod='hummingbird_editor'}</option>
            </select>
          </div>
          <div class="col-md-2 form-group">
            <label class="control-label">{l s='Active' mod='hummingbird_editor'}</label>
            <div class="checkbox">
              <label>
                <input type="checkbox" name="active" value="1" checked>
                {l s='Yes' mod='hummingbird_editor'}
              </label>
            </div>
          </div>
          <div class="col-md-2 form-group">
            <label class="control-label">{l s='Mobile diff.' mod='hummingbird_editor'}</label>
            <div class="checkbox">
              <label>
                <input type="checkbox" name="mobile_different" value="1" class="hbe-mobile-diff-init">
                {l s='Separate mobile content' mod='hummingbird_editor'}
              </label>
            </div>
          </div>
        </div>

        {if $hbe_all_shops|count > 1}
        <div class="form-group">
          <label class="control-label">{l s='Shops' mod='hummingbird_editor'}</label>
          <div class="hbe-shops-wrap">
            {foreach from=$hbe_all_shops item=shop}
            <label class="hbe-shop-label">
              <input type="checkbox" name="shop_ids[]" value="{$shop.id_shop}" checked>
              {$shop.name|escape:'html':'UTF-8'}
            </label>
            {/foreach}
          </div>
        </div>
        {/if}

        <div class="form-group">
          <button type="submit" class="btn btn-success">
            <i class="icon-save"></i> {l s='Create block' mod='hummingbird_editor'}
          </button>
          <button type="button" class="btn btn-default" id="hbe-add-cancel">
            {l s='Cancel' mod='hummingbird_editor'}
          </button>
        </div>
        <div class="hbe-alerts"></div>
      </form>
    </div>
  </div>{* /add panel *}

  {* ══════════════════════════════════════════════════════════════════════ *}
  {* BLOCK LIST for all other hooks                                        *}
  {* (displayHome group moved into the "Strona główna" tab above)          *}
  {* ══════════════════════════════════════════════════════════════════════ *}

  {foreach from=$hbe_grouped key=hookName item=hookBlocks}
  <div class="hbe-hook-group panel" data-hook="{$hookName|escape:'html':'UTF-8'}">
    <div class="hbe-hook-header panel-heading clearfix">
      <span class="hbe-hook-name">
        <i class="icon-plug"></i>
        {l s='Hook:' mod='hummingbird_editor'}
        <code>{$hookName|escape:'html':'UTF-8'}</code>
      </span>
      <span class="label label-default pull-right">
        {$hookBlocks|count} {l s='block(s)' mod='hummingbird_editor'}
      </span>
    </div>

    <ul class="hbe-sortable list-unstyled" data-hook="{$hookName|escape:'html':'UTF-8'}">
    {foreach from=$hookBlocks item=block}
      {include file=$hbe_tpl_dir|cat:'_block_row.tpl'}
    {/foreach}
    </ul>
  </div>{* /hook-group *}
  {/foreach}

</div>{* /hbe-app *}

<script>
var hbeAjaxUrl  = '{$hbe_ajax_url nofilter}'+'&';
var hbeToken    = '{$hbe_token}';
var hbeImgUrl   = '{$hbe_img_url}';
var hbeLangId   = '{$hbe_lang_id|intval}';
var hbeTrans = {
  confirmDelete : '{l s='Delete this block?' mod='hummingbird_editor' js=1}',
  confirmImg    : '{l s='Delete this image?' mod='hummingbird_editor' js=1}',
  saved         : '{l s='Saved successfully.' mod='hummingbird_editor' js=1}',
  duplicated    : '{l s='Block duplicated.' mod='hummingbird_editor' js=1}',
  error         : '{l s='An error occurred.' mod='hummingbird_editor' js=1}'
};

// FAQ builder
{literal}
document.addEventListener('DOMContentLoaded', function () {
  // Add FAQ row
  document.querySelectorAll('.hbe-faq-add-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var lang = btn.dataset.lang;
      var builder = document.querySelector('.hbe-faq-builder[data-lang="' + lang + '"]');
      var row = document.createElement('div');
      row.className = 'hbe-faq-row';
      row.innerHTML = '<div class="form-group"><label>Pytanie</label><input type="text" class="form-control hbe-faq-q" placeholder="Pytanie..."></div>'
        + '<div class="form-group"><label>Odpowiedź (HTML dozwolony)</label><textarea class="form-control hbe-faq-a" rows="4" placeholder="Odpowiedź..."></textarea></div>'
        + '<button type="button" class="btn btn-xs btn-danger hbe-faq-remove-btn">Usuń</button><hr>';
      builder.appendChild(row);
      attachRemoveBtn(row.querySelector('.hbe-faq-remove-btn'));
    });
  });

  // Remove row
  function attachRemoveBtn(btn) {
    btn.addEventListener('click', function () {
      btn.closest('.hbe-faq-row').remove();
    });
  }
  document.querySelectorAll('.hbe-faq-remove-btn').forEach(attachRemoveBtn);

  // Serialize before submit
  var faqForm = document.getElementById('hbe-faq-form');
  if (faqForm) {
    faqForm.addEventListener('submit', function (e) {
      e.preventDefault();
      document.querySelectorAll('.hbe-faq-builder').forEach(function (builder) {
        var lang = builder.dataset.lang;
        var items = [];
        builder.querySelectorAll('.hbe-faq-row').forEach(function (row) {
          var q = row.querySelector('.hbe-faq-q').value.trim();
          var a = row.querySelector('.hbe-faq-a').value.trim();
          if (q) items.push({q: q, a: a});
        });
        var input = document.querySelector('.hbe-faq-items-input[name="faq_items_' + lang + '"]');
        if (input) input.value = JSON.stringify(items);
      });

      var data = new FormData(faqForm);
      data.append('action', 'SaveFaq');
      data.append('ajax', '1');
      data.append('token', hbeToken);

      fetch(hbeAjaxUrl + 'action=SaveFaq&ajax=1&token=' + hbeToken, {method: 'POST', body: data})
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res.success) {
            alert(hbeTrans.saved);
          } else {
            alert(hbeTrans.error);
          }
        });
    });
  }
});
{/literal}
</script>
