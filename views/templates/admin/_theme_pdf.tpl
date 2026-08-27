{*
 * Wiersz PDF-a sekcji motywu (schemat: "pdf"). Wymaga: $pdf (HbEditorThemeSection::pdfInfo) — null = nic.
 * Formularz nadrzędny ma id_block w polu ukrytym; przycisk odświeżenia bierze id z data-hbe-pdf-render.
 *}
{if isset($pdf) && $pdf}
<p class="hbe-pdf-row">
  <i class="icon-file-text"></i> <strong>{$pdf.label|escape:'html':'UTF-8'}:</strong>
  <a data-hbe-pdf-link href="{$pdf.url|escape:'html':'UTF-8'}" target="_blank" rel="noopener"{if !$pdf.url} style="display:none"{/if}>{l s='otwórz' mod='hummingbird_editor'}</a>
  <span data-hbe-pdf-missing class="text-muted"{if $pdf.url} style="display:none"{/if}>{l s='jeszcze nie wygenerowany' mod='hummingbird_editor'}</span>
  <span class="text-muted"> — {l s='odświeża się automatycznie przy każdym zapisie' mod='hummingbird_editor'}</span>
  {if isset($block.id_block)}
    <button type="button" class="btn btn-xs btn-default" data-hbe-pdf-render="{$block.id_block|intval}" style="margin-left:6px"><i class="icon-refresh"></i> {l s='Odśwież PDF' mod='hummingbird_editor'}</button>
  {/if}
</p>
{/if}
