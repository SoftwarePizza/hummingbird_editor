{**
 * Hummingbird Editor — Slider list + global settings (admin)
 * Ported from bemo_slider list.tpl + settings form.
 *}

{* ── Global slider settings ─────────────────────────────────────────────── *}
<div class="panel">
    <div class="panel-heading"><i class="icon-cogs"></i> {l s='Ustawienia slidera' mod='hummingbird_editor'}</div>
    <form action="{$hbe_slider_form_action|escape:'html':'UTF-8'}" method="post" class="form-horizontal">
        <input type="hidden" name="submitSlider" value="1" />
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Prędkość (ms)' mod='hummingbird_editor'}</label>
            <div class="col-lg-3">
                <input type="number" name="HBE_SLIDER_SPEED" class="form-control" min="500" step="100" value="{$hbe_slider_speed|intval}" />
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Autoodtwarzanie' mod='hummingbird_editor'}</label>
            <div class="col-lg-9">
                <span class="switch prestashop-switch fixed-width-lg">
                    <input type="radio" name="HBE_SLIDER_AUTOPLAY" id="hbe_sl_ap_on" value="1" {if $hbe_slider_autoplay}checked{/if}><label for="hbe_sl_ap_on">{l s='Tak' mod='hummingbird_editor'}</label>
                    <input type="radio" name="HBE_SLIDER_AUTOPLAY" id="hbe_sl_ap_off" value="0" {if !$hbe_slider_autoplay}checked{/if}><label for="hbe_sl_ap_off">{l s='Nie' mod='hummingbird_editor'}</label>
                    <a class="slide-button btn"></a>
                </span>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Pauza po najechaniu' mod='hummingbird_editor'}</label>
            <div class="col-lg-9">
                <span class="switch prestashop-switch fixed-width-lg">
                    <input type="radio" name="HBE_SLIDER_PAUSE_ON_HOVER" id="hbe_sl_ph_on" value="1" {if $hbe_slider_pause}checked{/if}><label for="hbe_sl_ph_on">{l s='Tak' mod='hummingbird_editor'}</label>
                    <input type="radio" name="HBE_SLIDER_PAUSE_ON_HOVER" id="hbe_sl_ph_off" value="0" {if !$hbe_slider_pause}checked{/if}><label for="hbe_sl_ph_off">{l s='Nie' mod='hummingbird_editor'}</label>
                    <a class="slide-button btn"></a>
                </span>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Strzałki' mod='hummingbird_editor'}</label>
            <div class="col-lg-9">
                <span class="switch prestashop-switch fixed-width-lg">
                    <input type="radio" name="HBE_SLIDER_SHOW_ARROWS" id="hbe_sl_ar_on" value="1" {if $hbe_slider_arrows}checked{/if}><label for="hbe_sl_ar_on">{l s='Tak' mod='hummingbird_editor'}</label>
                    <input type="radio" name="HBE_SLIDER_SHOW_ARROWS" id="hbe_sl_ar_off" value="0" {if !$hbe_slider_arrows}checked{/if}><label for="hbe_sl_ar_off">{l s='Nie' mod='hummingbird_editor'}</label>
                    <a class="slide-button btn"></a>
                </span>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Styl strzałek' mod='hummingbird_editor'}</label>
            <div class="col-lg-9">
                <select name="HBE_SLIDER_ARROW_STYLE" class="form-control fixed-width-xxl">
                    <option value="classic" {if $hbe_slider_arrow_style != 'corner'}selected{/if}>{l s='Klasyczne (po bokach)' mod='hummingbird_editor'}</option>
                    <option value="corner" {if $hbe_slider_arrow_style == 'corner'}selected{/if}>{l s='Narożne (prawy dół)' mod='hummingbird_editor'}</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Kropki' mod='hummingbird_editor'}</label>
            <div class="col-lg-9">
                <span class="switch prestashop-switch fixed-width-lg">
                    <input type="radio" name="HBE_SLIDER_SHOW_DOTS" id="hbe_sl_dt_on" value="1" {if $hbe_slider_dots}checked{/if}><label for="hbe_sl_dt_on">{l s='Tak' mod='hummingbird_editor'}</label>
                    <input type="radio" name="HBE_SLIDER_SHOW_DOTS" id="hbe_sl_dt_off" value="0" {if !$hbe_slider_dots}checked{/if}><label for="hbe_sl_dt_off">{l s='Nie' mod='hummingbird_editor'}</label>
                    <a class="slide-button btn"></a>
                </span>
            </div>
        </div>
        <div class="panel-footer">
            <button type="submit" name="submitSlider" class="btn btn-default pull-right">
                <i class="process-icon-save"></i> {l s='Zapisz ustawienia' mod='hummingbird_editor'}
            </button>
        </div>
    </form>
</div>

{* ── Slides list ────────────────────────────────────────────────────────── *}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-list-ul"></i> {l s='Lista slajdów' mod='hummingbird_editor'}
        <span class="panel-heading-action">
            <a class="list-toolbar-btn" href="{$hbe_slider_form_action|escape:'html':'UTF-8'}&addSlide=1#hbe-tab-slider">
                <span data-placement="top" data-toggle="tooltip" class="label-tooltip" data-original-title="{l s='Dodaj nowy slajd' mod='hummingbird_editor'}" title="">
                    <i class="process-icon-new"></i>
                </span>
            </a>
        </span>
    </div>

    <div class="table-responsive clearfix">
        <table class="table slide">
            <thead>
                <tr>
                    <th class="center"><span class="title_box">{l s='ID' mod='hummingbird_editor'}</span></th>
                    <th class="center"><span class="title_box">{l s='Obraz' mod='hummingbird_editor'}</span></th>
                    <th class="center"><span class="title_box">{l s='Tytuł' mod='hummingbird_editor'}</span></th>
                    <th class="center"><span class="title_box">{l s='Status' mod='hummingbird_editor'}</span></th>
                    <th class="center"><span class="title_box">{l s='Akcje' mod='hummingbird_editor'}</span></th>
                </tr>
            </thead>
            <tbody id="hbe-slides">
                {if $hbe_slider_slides}
                    {foreach from=$hbe_slider_slides item=slide}
                        <tr data-id="{$slide.id_slide|intval}">
                            <td class="center">{$slide.id_slide|intval}</td>
                            <td class="center">
                                {if $slide.image}
                                    <img src="{$hbe_slider_image_baseurl|escape:'html':'UTF-8'}{$slide.image|escape:'html':'UTF-8'}" alt="{$slide.title|escape:'html':'UTF-8'}" class="img-thumbnail" style="max-width: 300px; max-height: 150px;" />
                                {else}
                                    <span class="label label-warning">{l s='Brak obrazu' mod='hummingbird_editor'}</span>
                                {/if}
                            </td>
                            <td class="center">
                                <strong>{$slide.title|escape:'html':'UTF-8'}</strong>
                                {if $slide.is_shared}<br/><span class="label label-info">{l s='Współdzielony' mod='hummingbird_editor'}</span>{/if}
                            </td>
                            <td class="center">
                                <a class="btn {if $slide.active}btn-success{else}btn-danger{/if}" href="{$hbe_slider_form_action|escape:'html':'UTF-8'}&changeStatus&id_slide={$slide.id_slide|intval}">
                                    <i class="icon-{if $slide.active}check{else}remove{/if}"></i>
                                    {if $slide.active}{l s='Włączony' mod='hummingbird_editor'}{else}{l s='Wyłączony' mod='hummingbird_editor'}{/if}
                                </a>
                            </td>
                            <td class="center">
                                <div class="btn-group-action">
                                    <div class="btn-group pull-right">
                                        <a href="{$hbe_slider_form_action|escape:'html':'UTF-8'}&id_slide={$slide.id_slide|intval}#hbe-tab-slider" class="btn btn-default" title="{l s='Edytuj' mod='hummingbird_editor'}">
                                            <i class="icon-edit"></i> {l s='Edytuj' mod='hummingbird_editor'}
                                        </a>
                                        <button class="btn btn-default dropdown-toggle" data-toggle="dropdown"><i class="icon-caret-down"></i>&nbsp;</button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a href="{$hbe_slider_form_action|escape:'html':'UTF-8'}&copy_id_slide={$slide.id_slide|intval}" onclick="return confirm('{l s='Czy na pewno chcesz skopiować ten slajd?' mod='hummingbird_editor'}');">
                                                    <i class="icon-copy"></i> {l s='Kopiuj' mod='hummingbird_editor'}
                                                </a>
                                            </li>
                                            <li>
                                                <a href="{$hbe_slider_form_action|escape:'html':'UTF-8'}&delete_id_slide={$slide.id_slide|intval}" onclick="return confirm('{l s='Czy na pewno chcesz usunąć ten slajd?' mod='hummingbird_editor'}');">
                                                    <i class="icon-trash"></i> {l s='Usuń' mod='hummingbird_editor'}
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    {/foreach}
                {else}
                    <tr><td colspan="5" class="text-center"><p class="alert alert-warning">{l s='Nie znaleziono żadnych slajdów' mod='hummingbird_editor'}</p></td></tr>
                {/if}
            </tbody>
        </table>
    </div>

    <div class="panel-footer">
        <a href="{$hbe_slider_form_action|escape:'html':'UTF-8'}&addSlide=1#hbe-tab-slider" class="btn btn-default">
            <i class="process-icon-new"></i> {l s='Dodaj nowy slajd' mod='hummingbird_editor'}
        </a>
    </div>
</div>

<style>
    #hbe-slides tr { cursor: move; }
    #hbe-slides tr:hover { background-color: #f5f5f5; }
</style>
