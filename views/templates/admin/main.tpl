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
    <a id="hbe-export-btn" class="btn btn-default" href="{$hbe_ajax_url nofilter}&action=ExportSettings&token={$hbe_token}">
      <i class="icon-download"></i> {l s='Eksport ustawień (XML)' mod='hummingbird_editor'}
    </a>
    <button id="hbe-import-btn" class="btn btn-default" type="button">
      <i class="icon-upload"></i> {l s='Import ustawień (XML)' mod='hummingbird_editor'}
    </button>
    <input type="file" id="hbe-import-file" accept=".xml,application/xml,text/xml" style="display:none">
    <p class="hbe-hint text-muted">
      {l s='Drag rows to reorder within a hook group.' mod='hummingbird_editor'}
    </p>
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

  {* ── Main section tabs ───────────────────────────────────────────────── *}
  <ul class="nav nav-tabs hbe-main-tabs" role="tablist">
    <li role="presentation" class="active">
      <a href="#hbe-tab-bars" data-toggle="tab" role="tab"><i class="icon-bullhorn"></i> {l s='Paski info' mod='hummingbird_editor'}</a>
    </li>
    <li role="presentation">
      <a href="#hbe-tab-banners" data-toggle="tab" role="tab"><i class="icon-picture"></i> {l s='Banery' mod='hummingbird_editor'}</a>
    </li>
    <li role="presentation">
      <a href="#hbe-tab-cols" data-toggle="tab" role="tab"><i class="icon-align-left"></i> {l s='Kolumny tekstowe' mod='hummingbird_editor'}</a>
    </li>
    <li role="presentation">
      <a href="#hbe-tab-sections" data-toggle="tab" role="tab"><i class="icon-th-large"></i> {l s='Sekcje z obrazkami' mod='hummingbird_editor'}</a>
    </li>
    <li role="presentation">
      <a href="#hbe-tab-carousels" data-toggle="tab" role="tab"><i class="icon-repeat"></i> {l s='Karuzele' mod='hummingbird_editor'}</a>
    </li>
    <li role="presentation">
      <a href="#hbe-tab-slider" data-toggle="tab" role="tab"><i class="icon-film"></i> {l s='Slider' mod='hummingbird_editor'}</a>
    </li>
    <li role="presentation">
      <a href="#hbe-tab-cart" data-toggle="tab" role="tab"><i class="icon-shopping-cart"></i> {l s='Koszyk' mod='hummingbird_editor'}</a>
    </li>
    <li role="presentation">
      <a href="#hbe-tab-faq" data-toggle="tab" role="tab"><i class="icon-question-sign"></i> {l s='FAQ' mod='hummingbird_editor'}</a>
    </li>
    <li role="presentation">
      <a href="#hbe-tab-imgtext" data-toggle="tab" role="tab"><i class="icon-picture"></i> {l s='Obraz + tekst' mod='hummingbird_editor'}</a>
    </li>
    <li role="presentation">
      <a href="#hbe-tab-listing" data-toggle="tab" role="tab"><i class="icon-th-list"></i> {l s='Listing' mod='hummingbird_editor'}</a>
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
       Tab 2 — Banery
    ═══════════════════════════════════════════════════════════════════════ *}
    <div id="hbe-tab-banners" class="tab-pane" role="tabpanel">

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

    {* ═══════════════════════════════════════════════════════════════════════
       Tab 3 — Kolumny tekstowe
    ═══════════════════════════════════════════════════════════════════════ *}
    <div id="hbe-tab-cols" class="tab-pane" role="tabpanel">

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

    {* ═══════════════════════════════════════════════════════════════════════
       Tab 4 — Sekcje z obrazkami
    ═══════════════════════════════════════════════════════════════════════ *}
    <div id="hbe-tab-sections" class="tab-pane" role="tabpanel">

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

    </div>{* /tab-sections *}

    {* ═══════════════════════════════════════════════════════════════════════
       Tab 5 — Karuzele
    ═══════════════════════════════════════════════════════════════════════ *}
    <div id="hbe-tab-carousels" class="tab-pane" role="tabpanel">

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

              <h4 style="margin:0 0 .75rem;border-bottom:1px solid #eee;padding-bottom:.5rem">{l s='Karuzela nowości (ps_newproducts)' mod='hummingbird_editor'}</h4>
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
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='np_link_text' values=$hbe_np_link_text_lang placeholder='np. Zobacz wszystkie'}
                </div>
                <div class="col-md-3 form-group">
                  <label class="control-label">{l s='URL linku' mod='hummingbird_editor'}</label>
                  {include file="{$hbe_tpl_dir}_ml_input.tpl" name='np_link_url' values=$hbe_np_link_url_lang placeholder='https://...'}
                </div>
              </div>

              <h4 style="margin:.75rem 0;border-bottom:1px solid #eee;padding-bottom:.5rem">{l s='Karuzela bestsellerów (ps_bestsellers)' mod='hummingbird_editor'}</h4>
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

              <h4 style="margin:.75rem 0;border-bottom:1px solid #eee;padding-bottom:.5rem">{l s='Karuzela wybranej kategorii (ps_categoryproducts)' mod='hummingbird_editor'}</h4>
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

              <button type="submit" class="btn btn-success"><i class="icon-save"></i> {l s='Zapisz nagłówki karuzeli' mod='hummingbird_editor'}</button>
              <div class="hbe-alerts"></div>
            </form>
          </div>
        </div>
      </div>

    </div>{* /tab-carousels *}

    {* ═══════════════════════════════════════════════════════════════════════
       Tab — Slider (ported from bemo_slider)
    ═══════════════════════════════════════════════════════════════════════ *}
    <div id="hbe-tab-slider" class="tab-pane" role="tabpanel">
      {if $hbe_slider_mode == 'form'}
        {include file="{$hbe_tpl_dir}slider/add_form.tpl"}
      {else}
        {include file="{$hbe_tpl_dir}slider/list.tpl"}
      {/if}
    </div>{* /tab-slider *}

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
              {l s='Nowy modal koszyka z paskiem „do darmowej dostawy". Próg darmowej dostawy ustawiasz w: Sprzedaż → Dostawa → Preferencje (PS_SHIPPING_FREE_PRICE).' mod='hummingbird_editor'}
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
                      <strong>{l s='Próg darmowej dostawy (ręczny)' mod='hummingbird_editor'}</strong>
                      <div class="help-block">{l s='Wartość w walucie domyślnej sklepu. Jeśli większa od 0, nadpisuje próg ze sklepu (Dostawa → Preferencje). Wpisz 0, aby użyć ustawienia sklepu.' mod='hummingbird_editor'}</div>
                    </td>
                    <td style="width:140px;text-align:right;vertical-align:middle">
                      <input type="number" step="0.01" min="0" name="cart_free_shipping_threshold" value="{$hbe_cart_free_ship_manual}" class="form-control text-right">
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
       Tab 6 — Ustawienia
    ═══════════════════════════════════════════════════════════════════════ *}
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

    </div>{* /tab-settings *}

    {* ══ Image + text (below the description on product page) ════════════ *}
    <div id="hbe-tab-imgtext" class="tab-pane" role="tabpanel">

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

    </div>{* /hbe-tab-imgtext *}

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

    {* ══ FAQ (below add-to-cart on product page) ══════════════════════════ *}
    <div id="hbe-tab-faq" class="tab-pane" role="tabpanel">

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

    </div>{* /hbe-tab-faq *}

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
  {* displayHome: combined sortable (static elements + custom blocks)      *}
  {* ══════════════════════════════════════════════════════════════════════ *}

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

  {* ══════════════════════════════════════════════════════════════════════ *}
  {* BLOCK LIST for all other hooks                                        *}
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
