{**
 * Hummingbird Editor — Slider add/edit slide form (admin)
 * Ported from bemo_slider add_form.tpl.
 *}
{assign var=slide value=$hbe_slider_edit}
{assign var=languages value=$hbe_slider_languages}
{assign var=defaultFormLanguage value=$hbe_slider_default_lang}

<div class="panel">
    <div class="panel-heading">
        <i class="icon-picture"></i>
        {if isset($slide) && $slide->id}{l s='Edytuj slajd' mod='hummingbird_editor'}{else}{l s='Dodaj nowy slajd' mod='hummingbird_editor'}{/if}
    </div>

    <form action="{$hbe_slider_form_action|escape:'html':'UTF-8'}" method="post" enctype="multipart/form-data" class="form-horizontal">
        <input type="hidden" name="submitSlide" value="1" />
        {if isset($slide) && $slide->id}
            <input type="hidden" name="id_slide" value="{$slide->id|intval}" />
            <input type="hidden" name="has_picture" value="1" />
        {/if}

        {* Obraz Desktop *}
        <div class="form-group">
            <label class="control-label col-lg-3 required">
                <span class="label-tooltip" data-toggle="tooltip" title="{l s='Maksymalny rozmiar pliku' mod='hummingbird_editor'}: {$hbe_slider_max_file_size|escape:'html':'UTF-8'}">
                    {l s='Obraz (Desktop)' mod='hummingbird_editor'}
                </span>
            </label>
            <div class="col-lg-9">
                {foreach from=$languages item=language}
                    <div class="translatable-field lang-{$language.id_lang|intval}" {if $language.id_lang != $defaultFormLanguage}style="display:none"{/if}>
                        <div class="form-group"><div class="col-lg-12">
                            {if $languages|count > 1}<span class="badge">{$language.iso_code|upper}</span>{/if}
                            {if isset($slide) && isset($slide->image[$language.id_lang]) && $slide->image[$language.id_lang] != ''}
                                <div class="image-preview" style="margin: 10px 0;">
                                    <img src="{$hbe_slider_image_baseurl|escape:'html':'UTF-8'}{$slide->image[$language.id_lang]|escape:'html':'UTF-8'}" alt="" class="img-thumbnail" style="max-width: 300px; max-height: 200px;" />
                                </div>
                                <input type="hidden" name="image_old_{$language.id_lang|intval}" value="{$slide->image[$language.id_lang]|escape:'html':'UTF-8'}" />
                            {/if}
                            <input type="file" name="image_{$language.id_lang|intval}" id="image_{$language.id_lang|intval}" class="form-control" accept="image/*" />
                            <p class="help-block">{l s='Zalecane wymiary: 1920x600 px. Formaty: JPG, PNG, GIF, WEBP' mod='hummingbird_editor'}</p>
                            <div class="checkbox"><label>
                                <input type="checkbox" name="apply_image_all_langs_{$language.id_lang|intval}" value="1" />
                                {l s='Użyj tego obrazu dla wszystkich języków' mod='hummingbird_editor'}
                            </label></div>
                        </div></div>
                    </div>
                {/foreach}
                {if $languages|count > 1}
                    <div class="form-group"><div class="col-lg-12">
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">{$hbe_slider_default_lang_name|escape:'html':'UTF-8'} <span class="caret"></span></button>
                        <ul class="dropdown-menu">
                            {foreach from=$languages item=language}<li><a href="javascript:hbeSliderLang({$language.id_lang|intval}, '{$language.name|escape:'html':'UTF-8'}');">{$language.name|escape:'html':'UTF-8'}</a></li>{/foreach}
                        </ul>
                    </div></div>
                {/if}
            </div>
        </div>

        {* Obraz Mobile *}
        <div class="form-group">
            <label class="control-label col-lg-3">
                <span class="label-tooltip" data-toggle="tooltip" title="{l s='Opcjonalny osobny obraz dla urządzeń mobilnych' mod='hummingbird_editor'}">
                    {l s='Obraz (Mobile)' mod='hummingbird_editor'}
                </span>
            </label>
            <div class="col-lg-9">
                {foreach from=$languages item=language}
                    <div class="translatable-field lang-{$language.id_lang|intval}" {if $language.id_lang != $defaultFormLanguage}style="display:none"{/if}>
                        <div class="form-group"><div class="col-lg-12">
                            {if $languages|count > 1}<span class="badge">{$language.iso_code|upper}</span>{/if}
                            {if isset($slide) && isset($slide->image_mobile[$language.id_lang]) && $slide->image_mobile[$language.id_lang] != ''}
                                <div class="image-preview" style="margin: 10px 0;">
                                    <img src="{$hbe_slider_image_baseurl|escape:'html':'UTF-8'}{$slide->image_mobile[$language.id_lang]|escape:'html':'UTF-8'}" alt="" class="img-thumbnail" style="max-width: 200px; max-height: 300px;" />
                                </div>
                                <input type="hidden" name="image_mobile_old_{$language.id_lang|intval}" value="{$slide->image_mobile[$language.id_lang]|escape:'html':'UTF-8'}" />
                            {/if}
                            <input type="file" name="image_mobile_{$language.id_lang|intval}" id="image_mobile_{$language.id_lang|intval}" class="form-control" accept="image/*" />
                            <p class="help-block">{l s='Opcjonalnie: osobny obraz dla mobile. Zalecane: 768x1024 px. Jeśli nie podano, użyty będzie obraz desktop.' mod='hummingbird_editor'}</p>
                            <div class="checkbox"><label>
                                <input type="checkbox" name="apply_image_mobile_all_langs_{$language.id_lang|intval}" value="1" />
                                {l s='Użyj tego obrazu mobilnego dla wszystkich języków' mod='hummingbird_editor'}
                            </label></div>
                        </div></div>
                    </div>
                {/foreach}
            </div>
        </div>

        {* Ustawienia tekstu *}
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Ustawienia tekstu' mod='hummingbird_editor'}</label>
            <div class="col-lg-9">
                <div class="row"><div class="col-lg-12"><div class="checkbox"><label>
                    <input type="checkbox" name="show_text" value="1" {if isset($slide) && $slide->show_text}checked{elseif !isset($slide)}checked{/if} />
                    {l s='Pokaż tekst' mod='hummingbird_editor'}
                </label></div></div></div>
                <div class="row" style="margin-top: 15px;">
                    <div class="col-lg-4">
                        <label>{l s='Pozycja tekstu' mod='hummingbird_editor'}</label>
                        <select name="text_position" class="form-control">
                            <option value="0" {if isset($slide) && $slide->text_position == 0}selected{/if}>{l s='Na środku' mod='hummingbird_editor'}</option>
                            <option value="1" {if isset($slide) && $slide->text_position == 1}selected{/if}>{l s='Na środku po lewej' mod='hummingbird_editor'}</option>
                            <option value="2" {if isset($slide) && $slide->text_position == 2}selected{/if}>{l s='W lewym dolnym rogu' mod='hummingbird_editor'}</option>
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <label>{l s='Kolor tła tekstu' mod='hummingbird_editor'}</label>
                        <div class="input-group">
                            <input type="color" name="overlay_color" value="{if isset($slide) && $slide->overlay_color}{$slide->overlay_color}{else}#000000{/if}" class="form-control" style="height: 31px; padding: 0;" />
                        </div>
                        <div class="checkbox"><label>
                            <input type="checkbox" name="overlay_is_transparent" value="1" {if isset($slide) && $slide->overlay_is_transparent}checked{/if} />
                            {l s='Tło całkowicie przezroczyste' mod='hummingbird_editor'}
                        </label></div>
                    </div>
                    <div class="col-lg-4">
                        <label>{l s='Krycie tła (%)' mod='hummingbird_editor'}</label>
                        <div class="input-group">
                            <input type="number" name="overlay_opacity" value="{if isset($slide) && isset($slide->overlay_opacity)}{$slide->overlay_opacity}{else}50{/if}" class="form-control" min="0" max="100" />
                            <span class="input-group-addon">%</span>
                        </div>
                        <p class="help-block">{l s='0 = niewidoczne, 100 = pełny kolor' mod='hummingbird_editor'}</p>
                    </div>
                </div>
            </div>
        </div>

        {* Przycisk CTA *}
        <div class="panel" style="margin-bottom:1rem">
            <div class="panel-heading">{l s='Przycisk Call to Action (CTA)' mod='hummingbird_editor'}</div>
            <div class="panel-body"><div class="row">
                <div class="col-lg-2 form-group">
                    <label>{l s='Włącz CTA' mod='hummingbird_editor'}</label>
                    <div class="checkbox"><label>
                        <input type="checkbox" name="cta_enabled" value="1" {if isset($slide) && $slide->cta_enabled}checked{/if} /> {l s='Tak' mod='hummingbird_editor'}
                    </label></div>
                </div>
                <div class="col-lg-3 form-group">
                    <label>{l s='Tekst przycisku' mod='hummingbird_editor'}</label>
                    <input type="text" name="cta_text" class="form-control" value="{if isset($slide) && $slide->cta_text}{$slide->cta_text|escape:'html':'UTF-8'}{/if}" maxlength="100" placeholder="{l s='np. Sprawdź ofertę' mod='hummingbird_editor'}" />
                </div>
                <div class="col-lg-2 form-group">
                    <label>{l s='Rozmiar' mod='hummingbird_editor'}</label>
                    <select name="cta_size" class="form-control">
                        <option value="sm" {if isset($slide) && $slide->cta_size == 'sm'}selected{/if}>{l s='Mały' mod='hummingbird_editor'}</option>
                        <option value="md" {if !isset($slide) || $slide->cta_size == 'md' || !$slide->cta_size}selected{/if}>{l s='Średni' mod='hummingbird_editor'}</option>
                        <option value="lg" {if isset($slide) && $slide->cta_size == 'lg'}selected{/if}>{l s='Duży' mod='hummingbird_editor'}</option>
                    </select>
                </div>
                <div class="col-lg-2 form-group">
                    <label>{l s='Kolor tekstu' mod='hummingbird_editor'}</label>
                    <input type="color" name="cta_color" value="{if isset($slide) && $slide->cta_color}{$slide->cta_color|escape:'html':'UTF-8'}{else}#ffffff{/if}" class="form-control" style="height:31px;padding:0;cursor:pointer" />
                </div>
                <div class="col-lg-2 form-group">
                    <label>{l s='Kolor tła' mod='hummingbird_editor'}</label>
                    <input type="color" name="cta_bg" value="{if isset($slide) && $slide->cta_bg}{$slide->cta_bg|escape:'html':'UTF-8'}{else}#000000{/if}" class="form-control" style="height:31px;padding:0;cursor:pointer" />
                </div>
                <div class="col-lg-2 form-group">
                    <label>{l s='Zaokrąglenie (px)' mod='hummingbird_editor'}</label>
                    <input type="number" name="cta_radius" min="0" max="100" value="{if isset($slide) && $slide->cta_radius !== null && $slide->cta_radius !== ''}{$slide->cta_radius|intval}{else}4{/if}" class="form-control" />
                </div>
            </div></div>
        </div>

        {* Tytuł *}
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Tytuł' mod='hummingbird_editor'}</label>
            <div class="col-lg-9">
                {foreach from=$languages item=language}
                    <div class="translatable-field lang-{$language.id_lang|intval}" {if $language.id_lang != $defaultFormLanguage}style="display:none"{/if}>
                        <div class="input-group">
                            {if $languages|count > 1}<span class="input-group-addon"><span class="badge">{$language.iso_code|upper}</span></span>{/if}
                            <input type="text" name="title_{$language.id_lang|intval}" id="title_{$language.id_lang|intval}" value="{if isset($slide->title[$language.id_lang])}{$slide->title[$language.id_lang]|escape:'html':'UTF-8'}{/if}" class="form-control" maxlength="255" />
                        </div>
                    </div>
                {/foreach}
                <p class="help-block">{l s='Tytuł wyświetlany na slajdzie' mod='hummingbird_editor'}</p>
            </div>
        </div>

        {* Opis *}
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Opis' mod='hummingbird_editor'}</label>
            <div class="col-lg-9">
                {foreach from=$languages item=language}
                    <div class="translatable-field lang-{$language.id_lang|intval}" {if $language.id_lang != $defaultFormLanguage}style="display:none"{/if}>
                        <div class="input-group">
                            {if $languages|count > 1}<span class="input-group-addon"><span class="badge">{$language.iso_code|upper}</span></span>{/if}
                            <textarea name="description_{$language.id_lang|intval}" id="description_{$language.id_lang|intval}" class="form-control" rows="5" maxlength="4000">{if isset($slide->description[$language.id_lang])}{$slide->description[$language.id_lang]|escape:'html':'UTF-8'}{/if}</textarea>
                        </div>
                    </div>
                {/foreach}
                <p class="help-block">{l s='Tekst wyświetlany na slajdzie' mod='hummingbird_editor'}</p>
            </div>
        </div>

        {* URL *}
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Docelowy URL' mod='hummingbird_editor'}</label>
            <div class="col-lg-9">
                {foreach from=$languages item=language}
                    <div class="translatable-field lang-{$language.id_lang|intval}" {if $language.id_lang != $defaultFormLanguage}style="display:none"{/if}>
                        <div class="input-group">
                            {if $languages|count > 1}<span class="input-group-addon"><span class="badge">{$language.iso_code|upper}</span></span>{/if}
                            <input type="text" name="url_{$language.id_lang|intval}" id="url_{$language.id_lang|intval}" value="{if isset($slide->url[$language.id_lang])}{$slide->url[$language.id_lang]|escape:'html':'UTF-8'}{/if}" class="form-control" maxlength="255" />
                        </div>
                    </div>
                {/foreach}
                <p class="help-block">{l s='Link, do którego przekieruje kliknięcie w slajd (opcjonalnie)' mod='hummingbird_editor'}</p>
            </div>
        </div>

        {* Aktywny *}
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Aktywny' mod='hummingbird_editor'}</label>
            <div class="col-lg-9">
                <span class="switch prestashop-switch fixed-width-lg">
                    <input type="radio" name="active_slide" id="active_slide_on" value="1" {if !isset($slide) || $slide->active}checked{/if}><label for="active_slide_on">{l s='Tak' mod='hummingbird_editor'}</label>
                    <input type="radio" name="active_slide" id="active_slide_off" value="0" {if isset($slide) && !$slide->active}checked{/if}><label for="active_slide_off">{l s='Nie' mod='hummingbird_editor'}</label>
                    <a class="slide-button btn"></a>
                </span>
            </div>
        </div>

        {* Aktywny Mobile *}
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Aktywny na Mobile' mod='hummingbird_editor'}</label>
            <div class="col-lg-9">
                <span class="switch prestashop-switch fixed-width-lg">
                    <input type="radio" name="active_mobile" id="active_mobile_on" value="1" {if !isset($slide) || !isset($slide->active_mobile) || $slide->active_mobile}checked{/if}><label for="active_mobile_on">{l s='Tak' mod='hummingbird_editor'}</label>
                    <input type="radio" name="active_mobile" id="active_mobile_off" value="0" {if isset($slide) && isset($slide->active_mobile) && !$slide->active_mobile}checked{/if}><label for="active_mobile_off">{l s='Nie' mod='hummingbird_editor'}</label>
                    <a class="slide-button btn"></a>
                </span>
                <p class="help-block">{l s='Wyłącz, jeśli slajd ma być ukryty na urządzeniach mobilnych (poniżej 768px).' mod='hummingbird_editor'}</p>
            </div>
        </div>

        {* Sklepy *}
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Przypisz do sklepów' mod='hummingbird_editor'}</label>
            <div class="col-lg-9">
                {foreach from=$hbe_slider_shops item=shop}
                    <div class="checkbox"><label>
                        <input type="checkbox" name="checkBoxShopAsso_{$shop.id_shop}" value="{$shop.id_shop}" {if $hbe_slider_associated && in_array($shop.id_shop, $hbe_slider_associated)}checked{/if} />
                        {$shop.name|escape:'html':'UTF-8'}
                    </label></div>
                {/foreach}
                <p class="help-block">{l s='Wybierz sklepy, w których slajd ma być dostępny' mod='hummingbird_editor'}</p>
            </div>
        </div>

        <div class="panel-footer">
            <button type="submit" name="submitSlide" class="btn btn-default pull-right">
                <i class="process-icon-save"></i> {l s='Zapisz' mod='hummingbird_editor'}
            </button>
            <a href="{$hbe_slider_form_action|escape:'html':'UTF-8'}" class="btn btn-default">
                <i class="process-icon-cancel"></i> {l s='Anuluj' mod='hummingbird_editor'}
            </a>
        </div>
    </form>
</div>

<script type="text/javascript">
    function hbeSliderLang(id, name) {
        $('#hbe-tab-slider .translatable-field').hide();
        $('#hbe-tab-slider .translatable-field.lang-' + id).show();
        if (name) {
            $('#hbe-tab-slider .dropdown-toggle').each(function () {
                if ($(this).next('.dropdown-menu').find('a[href*="hbeSliderLang"]').length > 0) {
                    $(this).html(name + ' <span class="caret"></span>');
                }
            });
        }
    }
</script>
