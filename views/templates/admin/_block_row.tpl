{**
 * Reusable block row partial.
 * Requires: $block, $hbe_token, $hbe_languages, $hbe_all_shops
 *}
      <li class="hbe-block-row" data-id="{$block.id_block}">

        {* ── Row header ─────────────────────────────────────────────────── *}
        <div class="hbe-row-header clearfix">
          <span class="hbe-handle" title="{l s='Drag to reorder' mod='hummingbird_editor'}">
            <i class="icon-reorder"></i>
          </span>

          {if $block.section_type}
          <span class="hbe-type-badge" style="background:#fff3e0;color:#7a4200;border-color:#ffcc80">
            <i class="icon-copy"></i> {l s='Sekcja:' mod='hummingbird_editor'} <strong>{$block.section_type|escape:'html':'UTF-8'}</strong>
          </span>
          {else}
          <span class="hbe-type-badge hbe-type-{$block.type}">
            {if $block.type == 'text'}<i class="icon-align-left"></i> {l s='Text' mod='hummingbird_editor'}
            {elseif $block.type == 'image'}<i class="icon-picture"></i> {l s='Image' mod='hummingbird_editor'}
            {else}<i class="icon-code"></i> {l s='HTML' mod='hummingbird_editor'}
            {/if}
          </span>
          {/if}

          <span class="hbe-block-id text-muted">#{$block.id_block}</span>

          {if $block.mobile_different}
          <span class="label label-info hbe-mobile-label">
            <i class="icon-mobile"></i> {l s='Mobile diff.' mod='hummingbird_editor'}
          </span>
          {/if}

          <div class="hbe-row-actions pull-right">
            <button class="btn btn-xs btn-default hbe-edit-btn" data-id="{$block.id_block}"
                    title="{l s='Edit' mod='hummingbird_editor'}">
              <i class="icon-pencil"></i> {l s='Edit' mod='hummingbird_editor'}
            </button>

            <button class="btn btn-xs hbe-toggle-active {if $block.active}btn-success hbe-active{else}btn-warning hbe-inactive{/if}"
                    data-id="{$block.id_block}"
                    title="{if $block.active}{l s='Disable' mod='hummingbird_editor'}{else}{l s='Enable' mod='hummingbird_editor'}{/if}">
              <i class="icon-{if $block.active}check{else}remove{/if}"></i>
              {if $block.active}{l s='ON' mod='hummingbird_editor'}{else}{l s='OFF' mod='hummingbird_editor'}{/if}
            </button>

            <button class="btn btn-xs btn-default hbe-duplicate" data-id="{$block.id_block}"
                    title="{l s='Duplicate block' mod='hummingbird_editor'}">
              <i class="icon-copy"></i>
            </button>

            <button class="btn btn-xs btn-danger hbe-delete" data-id="{$block.id_block}"
                    title="{l s='Delete' mod='hummingbird_editor'}">
              <i class="icon-trash"></i>
            </button>
          </div>
        </div>{* /row-header *}

        {* ── Section data edit panel (for cloned sections) ──────────────── *}
        {if $block.section_type}
        <div id="hbe-edit-panel-{$block.id_block}" class="hbe-edit-panel hbe-section-panel" style="display:none">
          <form class="hbe-section-edit-form" data-id="{$block.id_block}" autocomplete="off">
            <input type="hidden" name="token" value="{$hbe_token}">
            <input type="hidden" name="id_block" value="{$block.id_block}">
            <div class="row hbe-edit-general" style="margin-bottom:12px">
              <div class="col-md-3 form-group">
                <label>{l s='Active' mod='hummingbird_editor'}</label>
                <div class="checkbox"><label>
                  <input type="checkbox" name="active" value="1" {if $block.active}checked{/if}>
                  {l s='Yes' mod='hummingbird_editor'}
                </label></div>
              </div>
              <div class="col-md-9">
                <div class="alert alert-info" style="margin:0;padding:8px 12px;font-size:0.85em">
                  <i class="icon-info-sign"></i>
                  {l s='Zduplikowana sekcja. Edytuj dane JSON poniżej.' mod='hummingbird_editor'}
                  Typ: <code>{$block.section_type|escape:'html':'UTF-8'}</code>
                </div>
              </div>
            </div>
            <div class="form-group">
              <label>{l s='Dane sekcji (JSON)' mod='hummingbird_editor'}</label>
              <textarea name="section_data" class="form-control hbe-code-editor" rows="12"
                        style="font-family:monospace;font-size:12px"
              >{$block.section_data|default:'{}'|escape:'html':'UTF-8'}</textarea>
            </div>
            {if $hbe_all_shops|count > 1}
            <div class="hbe-shops-section form-group">
              <label>{l s='Visible in shops' mod='hummingbird_editor'}</label>
              <div class="hbe-shops-wrap">
                {foreach from=$hbe_all_shops item=shop}
                <label class="hbe-shop-label">
                  <input type="checkbox" name="shop_ids[]" value="{$shop.id_shop}"
                         {if in_array($shop.id_shop, $block.shop_ids)}checked{/if}>
                  {$shop.name|escape:'html':'UTF-8'}
                </label>
                {/foreach}
              </div>
            </div>
            {/if}
            <div class="form-group hbe-save-row">
              <button type="submit" class="btn btn-success">
                <i class="icon-save"></i> {l s='Save' mod='hummingbird_editor'}
              </button>
              <button type="button" class="btn btn-default hbe-close-edit" data-id="{$block.id_block}">
                {l s='Close' mod='hummingbird_editor'}
              </button>
              <span class="hbe-save-status text-success" style="display:none">
                <i class="icon-check"></i> {l s='Saved' mod='hummingbird_editor'}
              </span>
            </div>
            <div class="hbe-alerts"></div>
          </form>
        </div>{* /section-panel *}
        {else}
        {* ── Edit panel (collapsed) ──────────────────────────────────────── *}
        <div id="hbe-edit-panel-{$block.id_block}" class="hbe-edit-panel" style="display:none">
          <form class="hbe-edit-form" data-id="{$block.id_block}" autocomplete="off">
            <input type="hidden" name="token" value="{$hbe_token}">
            <input type="hidden" name="id_block" value="{$block.id_block}">

            {* General settings row *}
            <div class="row hbe-edit-general">
              <div class="col-md-4 form-group">
                <label>{l s='Hook name' mod='hummingbird_editor'}</label>
                <input type="text" name="hook_name" class="form-control"
                       value="{$block.hook_name|escape:'html':'UTF-8'}"
                       list="hbe-hooks-datalist" required>
              </div>
              <div class="col-md-2 form-group">
                <label>{l s='Type' mod='hummingbird_editor'}</label>
                <select name="type" class="form-control hbe-type-select">
                  <option value="text" {if $block.type == 'text'}selected{/if}>
                    {l s='Text / WYSIWYG' mod='hummingbird_editor'}
                  </option>
                  <option value="image" {if $block.type == 'image'}selected{/if}>
                    {l s='Image' mod='hummingbird_editor'}
                  </option>
                  <option value="html" {if $block.type == 'html'}selected{/if}>
                    {l s='Raw HTML' mod='hummingbird_editor'}
                  </option>
                </select>
              </div>
              <div class="col-md-2 form-group">
                <label>{l s='Active' mod='hummingbird_editor'}</label>
                <div class="checkbox">
                  <label>
                    <input type="checkbox" name="active" value="1" {if $block.active}checked{/if}>
                    {l s='Yes' mod='hummingbird_editor'}
                  </label>
                </div>
              </div>
              <div class="col-md-4 form-group">
                <label>{l s='Separate mobile content' mod='hummingbird_editor'}</label>
                <div class="checkbox">
                  <label>
                    <input type="checkbox" name="mobile_different" value="1"
                           class="hbe-mobile-diff-cb" {if $block.mobile_different}checked{/if}>
                    {l s='Enable desktop / mobile tabs' mod='hummingbird_editor'}
                  </label>
                </div>
              </div>
            </div>

            {* ── Image upload section (visible when type = image) ─────────── *}
            <div class="hbe-image-section {if $block.type != 'image'}hbe-hidden{/if}">
              <div class="row">
                <div class="col-md-6">
                  <div class="hbe-img-slot">
                    <label><i class="icon-desktop"></i> {l s='Image — Desktop' mod='hummingbird_editor'}</label>
                    {if $block.image_desktop_url}
                    <div class="hbe-img-preview-wrap">
                      <img id="hbe-img-prev-{$block.id_block}-desktop"
                           src="{$block.image_desktop_url|escape:'html':'UTF-8'}" class="hbe-img-preview">
                      <button type="button" class="btn btn-xs btn-danger hbe-img-delete"
                              data-id="{$block.id_block}" data-side="desktop">
                        <i class="icon-trash"></i>
                      </button>
                    </div>
                    {else}
                    <img id="hbe-img-prev-{$block.id_block}-desktop" class="hbe-img-preview hbe-hidden" src="">
                    {/if}
                    <input type="file" class="hbe-image-upload" accept="image/*"
                           data-id="{$block.id_block}" data-side="desktop">
                  </div>
                </div>
                <div class="col-md-6 hbe-mobile-fields {if !$block.mobile_different}hbe-hidden{/if}">
                  <div class="hbe-img-slot">
                    <label><i class="icon-mobile-phone"></i> {l s='Image — Mobile' mod='hummingbird_editor'}</label>
                    {if $block.image_mobile_url}
                    <div class="hbe-img-preview-wrap">
                      <img id="hbe-img-prev-{$block.id_block}-mobile"
                           src="{$block.image_mobile_url|escape:'html':'UTF-8'}" class="hbe-img-preview">
                      <button type="button" class="btn btn-xs btn-danger hbe-img-delete"
                              data-id="{$block.id_block}" data-side="mobile">
                        <i class="icon-trash"></i>
                      </button>
                    </div>
                    {else}
                    <img id="hbe-img-prev-{$block.id_block}-mobile" class="hbe-img-preview hbe-hidden" src="">
                    {/if}
                    <input type="file" class="hbe-image-upload" accept="image/*"
                           data-id="{$block.id_block}" data-side="mobile">
                  </div>
                </div>
              </div>
            </div>{* /image-section *}

            {* ── Language tabs ────────────────────────────────────────────── *}
            <div class="hbe-lang-section {if $block.type == 'image' && !$block.mobile_different}hbe-hidden{/if}">
              <ul class="nav nav-tabs hbe-lang-tabs" role="tablist">
                {foreach from=$hbe_languages item=lang name=langloop}
                <li role="presentation" {if $smarty.foreach.langloop.first}class="active"{/if}>
                  <a href="#hbe-tab-{$block.id_block}-{$lang.id_lang}" role="tab" data-toggle="tab">
                    {if $lang.iso_code}{$lang.iso_code|upper}{else}{$lang.name}{/if}
                  </a>
                </li>
                {/foreach}
              </ul>

              <div class="tab-content hbe-tab-content">
                {foreach from=$hbe_languages item=lang name=langloop}
                {assign var=lid value=$lang.id_lang}
                {assign var=ldata value=$block.lang_data[$lid]|default:[]}
                <div class="tab-pane {if $smarty.foreach.langloop.first}active{/if}"
                     id="hbe-tab-{$block.id_block}-{$lid}">

                  {* Desktop content *}
                  <div class="hbe-content-group">
                    <label class="hbe-device-label">
                      <i class="icon-desktop"></i>
                      {l s='Desktop content' mod='hummingbird_editor'}
                      {if $block.type == 'image'} — {l s='alt text' mod='hummingbird_editor'}{/if}
                    </label>
                    {if $block.type == 'html'}
                    <textarea name="content_desktop_{$lid}"
                              class="form-control hbe-code-editor"
                              rows="8"
                              placeholder="{l s='HTML code' mod='hummingbird_editor'}"
                    >{$ldata.content_desktop|default:''|escape:'html':'UTF-8'}</textarea>
                    {elseif $block.type == 'image'}
                    <input type="text" name="content_desktop_{$lid}" class="form-control"
                           placeholder="{l s='Alt text for desktop image' mod='hummingbird_editor'}"
                           value="{$ldata.content_desktop|default:''|escape:'html':'UTF-8'}">
                    {else}
                    <div class="hbe-wysiwyg-toolbar" data-target="hbe-ta-d-{$block.id_block}-{$lid}">
                      <button type="button" data-cmd="bold" title="Bold"><b>B</b></button>
                      <button type="button" data-cmd="italic" title="Italic"><i>I</i></button>
                      <button type="button" data-cmd="underline" title="Underline"><u>U</u></button>
                      <button type="button" data-cmd="insertUnorderedList" title="List">&#9679;</button>
                      <button type="button" data-cmd="createLink" title="Link">&#128279;</button>
                      <button type="button" data-cmd="removeFormat" title="Clear">&#10006;</button>
                    </div>
                    <div class="hbe-wysiwyg form-control"
                         id="hbe-ta-d-{$block.id_block}-{$lid}"
                         contenteditable="true"
                         data-field="content_desktop_{$lid}"
                    >{$ldata.content_desktop|default:''}</div>
                    <input type="hidden" name="content_desktop_{$lid}"
                           value="{$ldata.content_desktop|default:''|escape:'html':'UTF-8'}">
                    {/if}
                  </div>

                  {* Mobile content — shown only when mobile_different is on *}
                  <div class="hbe-mobile-fields {if !$block.mobile_different}hbe-hidden{/if}">
                    <div class="hbe-content-group hbe-mobile-group">
                      <label class="hbe-device-label">
                        <i class="icon-mobile-phone"></i>
                        {l s='Mobile content' mod='hummingbird_editor'}
                        {if $block.type == 'image'} — {l s='alt text' mod='hummingbird_editor'}{/if}
                      </label>
                      {if $block.type == 'html'}
                      <textarea name="content_mobile_{$lid}"
                                class="form-control hbe-code-editor"
                                rows="6"
                                placeholder="{l s='HTML code (mobile)' mod='hummingbird_editor'}"
                      >{$ldata.content_mobile|default:''|escape:'html':'UTF-8'}</textarea>
                      {elseif $block.type == 'image'}
                      <input type="text" name="content_mobile_{$lid}" class="form-control"
                             placeholder="{l s='Alt text for mobile image' mod='hummingbird_editor'}"
                             value="{$ldata.content_mobile|default:''|escape:'html':'UTF-8'}">
                      {else}
                      <div class="hbe-wysiwyg-toolbar" data-target="hbe-ta-m-{$block.id_block}-{$lid}">
                        <button type="button" data-cmd="bold" title="Bold"><b>B</b></button>
                        <button type="button" data-cmd="italic" title="Italic"><i>I</i></button>
                        <button type="button" data-cmd="underline" title="Underline"><u>U</u></button>
                        <button type="button" data-cmd="insertUnorderedList" title="List">&#9679;</button>
                        <button type="button" data-cmd="createLink" title="Link">&#128279;</button>
                        <button type="button" data-cmd="removeFormat" title="Clear">&#10006;</button>
                      </div>
                      <div class="hbe-wysiwyg form-control"
                           id="hbe-ta-m-{$block.id_block}-{$lid}"
                           contenteditable="true"
                           data-field="content_mobile_{$lid}"
                      >{$ldata.content_mobile|default:''}</div>
                      <input type="hidden" name="content_mobile_{$lid}"
                             value="{$ldata.content_mobile|default:''|escape:'html':'UTF-8'}">
                      {/if}
                    </div>
                  </div>

                  {* Link URL (for image or optional text wrapping) *}
                  {if $block.type == 'image' || $block.type == 'text'}
                  <div class="row hbe-link-row">
                    <div class="col-md-6 form-group">
                      <label><i class="icon-link"></i> {l s='Link — Desktop' mod='hummingbird_editor'}</label>
                      <input type="url" name="link_desktop_{$lid}" class="form-control"
                             placeholder="https://"
                             value="{$ldata.link_desktop|default:''|escape:'html':'UTF-8'}">
                    </div>
                    <div class="col-md-6 hbe-mobile-fields {if !$block.mobile_different}hbe-hidden{/if}">
                      <div class="form-group">
                        <label><i class="icon-link"></i> {l s='Link — Mobile' mod='hummingbird_editor'}</label>
                        <input type="url" name="link_mobile_{$lid}" class="form-control"
                               placeholder="https://"
                               value="{$ldata.link_mobile|default:''|escape:'html':'UTF-8'}">
                      </div>
                    </div>
                  </div>
                  {/if}

                </div>{* /tab-pane *}
                {/foreach}
              </div>{* /tab-content *}
            </div>{* /lang-section *}

            {* ── Shop assignment ──────────────────────────────────────────── *}
            {if $hbe_all_shops|count > 1}
            <div class="hbe-shops-section form-group">
              <label>{l s='Visible in shops' mod='hummingbird_editor'}</label>
              <div class="hbe-shops-wrap">
                {foreach from=$hbe_all_shops item=shop}
                <label class="hbe-shop-label">
                  <input type="checkbox" name="shop_ids[]" value="{$shop.id_shop}"
                         {if in_array($shop.id_shop, $block.shop_ids)}checked{/if}>
                  {$shop.name|escape:'html':'UTF-8'}
                </label>
                {/foreach}
              </div>
            </div>
            {/if}

            {* ── Save button ──────────────────────────────────────────────── *}
            <div class="form-group hbe-save-row">
              <button type="submit" class="btn btn-success">
                <i class="icon-save"></i> {l s='Save' mod='hummingbird_editor'}
              </button>
              <button type="button" class="btn btn-default hbe-close-edit" data-id="{$block.id_block}">
                {l s='Close' mod='hummingbird_editor'}
              </button>
              <span class="hbe-save-status text-success" style="display:none">
                <i class="icon-check"></i> {l s='Saved' mod='hummingbird_editor'}
              </span>
            </div>
            <div class="hbe-alerts"></div>

          </form>
        </div>{* /edit-panel *}
        {/if}{* end section_type conditional *}

      </li>
