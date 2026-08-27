{*
 * Pola sekcji motywu / „Treści motywu” generowane ze schematu (HbEditorThemeSection::formTree).
 * Wymaga: $nodes (drzewo), $hbe_languages, $hbe_lang_id, $hbe_tpl_dir. Wstawiać wewnątrz <div class="row">.
 *}
{foreach from=$nodes item=node}
  {if $node.kind == 'group'}
    <div class="col-md-12">
      <div class="hbe-tf-group">
        <div class="hbe-tf-group__label">{$node.label|escape:'html':'UTF-8'}</div>
        {if $node.help}<p class="help-block" style="margin-top:0">{$node.help|escape:'html':'UTF-8'}</p>{/if}
        {foreach from=$node.rows item=row name=tfrows}
          <fieldset class="hbe-tf-row">
            <legend>{if $node.row_label}{$node.row_label|escape:'html':'UTF-8'}{else}{$node.label|escape:'html':'UTF-8'}{/if} {$smarty.foreach.tfrows.iteration}</legend>
            <div class="row">
              {include file="{$hbe_tpl_dir}_theme_fields.tpl" nodes=$row}
            </div>
          </fieldset>
        {/foreach}
      </div>
    </div>
  {elseif $node.type == 'list'}
    <div class="col-md-12 form-group">
      <label class="control-label">{$node.label|escape:'html':'UTF-8'}</label>
      {if $node.help}<p class="help-block" style="margin-top:0">{$node.help|escape:'html':'UTF-8'}</p>{/if}
      <div class="hbe-list" data-hbe-list data-schema="{$node.schema_json|escape:'html':'UTF-8'}" data-row-label="{$node.row_label|escape:'html':'UTF-8'}" data-add-label="{$node.add_label|escape:'html':'UTF-8'}">
        <textarea class="hbe-list-json" name="tflist[{$node.path|escape:'html':'UTF-8'}]" hidden>{$node.value_json|escape:'html':'UTF-8'}</textarea>
        <div class="hbe-list__mount"></div>
      </div>
    </div>
  {elseif $node.type == 'image'}
    <div class="col-md-{$node.width|intval} form-group hbe-tf-image">
      <label class="control-label">{$node.label|escape:'html':'UTF-8'}</label>
      <div class="hbe-tf-preview"{if !$node.url} style="display:none"{/if}>
        <img src="{$node.url|escape:'html':'UTF-8'}" data-hbe-tf-img="{$node.path|escape:'html':'UTF-8'}" alt=""
             style="max-width:100%;max-height:140px;border:1px solid #ddd;border-radius:3px;background:#f6f6f6">
      </div>
      <input type="file" name="{$node.file_input|escape:'html':'UTF-8'}" accept="image/*,.svg" class="form-control">
      <label class="checkbox-inline hbe-tf-del" style="font-weight:normal{if !$node.file};display:none{/if}">
        <input type="checkbox" name="tfdel[{$node.path|escape:'html':'UTF-8'}]" value="1"> {l s='Usuń zdjęcie' mod='hummingbird_editor'}
      </label>
      {if $node.help}<p class="help-block">{$node.help|escape:'html':'UTF-8'}</p>{/if}
    </div>
  {else}
    <div class="col-md-{$node.width|intval} form-group">
      <label class="control-label">{$node.label|escape:'html':'UTF-8'}</label>
      {if $node.type == 'textarea' || $node.type == 'html'}
        {include file="{$hbe_tpl_dir}_ml_input.tpl" name=$node.input values=$node.values type='textarea' rows=$node.rows placeholder=$node.placeholder}
      {else}
        {include file="{$hbe_tpl_dir}_ml_input.tpl" name=$node.input values=$node.values type='text' placeholder=$node.placeholder}
      {/if}
      {if $node.help}<p class="help-block">{$node.help|escape:'html':'UTF-8'}</p>{/if}
    </div>
  {/if}
{/foreach}
