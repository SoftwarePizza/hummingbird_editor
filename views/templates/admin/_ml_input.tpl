{*
 * Multi-language input — native PrestaShop translatable-field pattern.
 * Shows one language at a time; PS global hideOtherLanguage(id) switches active lang.
 *
 * Params:
 *   name        – form field base (name="field[id_lang]")
 *   values      – array id_lang => value
 *   type        – "text" (default) | "textarea" | "url" | "email"
 *   placeholder – optional
 *   rows        – textarea rows (default 3)
 *}
{if !isset($type)}{assign var=type value='text'}{/if}
{if !isset($rows)}{assign var=rows value=3}{/if}
{if !isset($placeholder)}{assign var=placeholder value=''}{/if}
{if !isset($values) || !is_array($values)}{assign var=values value=[]}{/if}

{foreach from=$hbe_languages item=lang}
  {assign var=lid value=(int)$lang.id_lang}
  {if isset($values[$lid])}{assign var=val value=$values[$lid]}{else}{assign var=val value=''}{/if}
  <div class="translatable-field lang-{$lid}"{if $lid != $hbe_lang_id} style="display:none"{/if}>
    {if $type == 'textarea'}
      <textarea name="{$name}[{$lid}]" class="form-control" rows="{$rows}"
                placeholder="{$placeholder|escape:'html':'UTF-8'}">{$val|escape:'html':'UTF-8'}</textarea>
      {if $hbe_languages|count > 1}
      <div style="text-align:right;margin-top:2px">
        <div class="btn-group">
          <button type="button" class="btn btn-default btn-xs dropdown-toggle" tabindex="-1"
                  data-toggle="dropdown">{$lang.iso_code|upper}&nbsp;<span class="caret"></span></button>
          <ul class="dropdown-menu dropdown-menu-right">
            {foreach from=$hbe_languages item=l2}
            <li><a href="javascript:hideOtherLanguage({$l2.id_lang});" tabindex="-1">{$l2.name|escape:'html':'UTF-8'}</a></li>
            {/foreach}
          </ul>
        </div>
      </div>
      {/if}
    {else}
      {if $hbe_languages|count > 1}
      <div class="input-group">
        <input type="{$type}" name="{$name}[{$lid}]" class="form-control"
               value="{$val|escape:'html':'UTF-8'}"
               placeholder="{$placeholder|escape:'html':'UTF-8'}">
        <div class="input-group-btn">
          <button type="button" class="btn btn-default dropdown-toggle" tabindex="-1"
                  data-toggle="dropdown">{$lang.iso_code|upper}&nbsp;<span class="caret"></span></button>
          <ul class="dropdown-menu dropdown-menu-right">
            {foreach from=$hbe_languages item=l2}
            <li><a href="javascript:hideOtherLanguage({$l2.id_lang});" tabindex="-1">{$l2.name|escape:'html':'UTF-8'}</a></li>
            {/foreach}
          </ul>
        </div>
      </div>
      {else}
      <input type="{$type}" name="{$name}[{$lid}]" class="form-control"
             value="{$val|escape:'html':'UTF-8'}"
             placeholder="{$placeholder|escape:'html':'UTF-8'}">
      {/if}
    {/if}
  </div>
{/foreach}
