{**
 * Progi rabatowe — pasek postępu do następnego rabatu z kodem.
 *
 * Jeden szablon dla trzech miejsc (hbe_tiers_ctx):
 *   cart    — strona koszyka (wewnątrz .js-cart-detailed-totals, odświeżane ajaxem)
 *   preview — podgląd koszyka: modal po dodaniu i panel pod ikoną w nagłówku
 *   product — karta produktu (z tytułem; przy pustym koszyku sama drabinka)
 *
 * Zmienne: $hbe_tiers (HbEditorDiscountTiers::getState()), $hbe_tiers_ctx,
 * $hbe_tiers_apply (POST aktywujący kod), $hbe_tiers_token, $hbe_tiers_l (teksty).
 *}
{assign var=t value=$hbe_tiers}
<div class="hbe-tiers hbe-tiers--{$hbe_tiers_ctx}{if $t.action} is-reached{/if}{if $t.applied} is-applied{/if}{if $t.amount <= 0} is-empty{/if}"
     data-hbe-tiers="{$hbe_tiers_ctx}" role="region" aria-label="{$hbe_tiers_l.title|escape:'html':'UTF-8'}">

  <div class="hbe-tiers__head">
    <i class="material-icons hbe-tiers__icon" aria-hidden="true">{if $t.action || $t.applied}&#xE86C;{else}&#xE54E;{/if}</i>
    <div class="hbe-tiers__copy">
      <p class="hbe-tiers__message">{$t.message|escape:'html':'UTF-8'}</p>
      {if $t.sub}
        <p class="hbe-tiers__sub">{$t.sub|escape:'html':'UTF-8'}</p>
      {elseif $hbe_tiers_ctx == 'product' && $t.amount <= 0}
        <p class="hbe-tiers__sub">{$hbe_tiers_l.one_click|escape:'html':'UTF-8'}</p>
      {/if}
    </div>
  </div>

  {* Wspólny pasek: 0 zł → najwyższy próg, znaczniki na progach. *}
  <div class="hbe-tiers__track" role="progressbar" aria-valuenow="{$t.progress_total}" aria-valuemin="0" aria-valuemax="100"
       aria-valuetext="{$t.amount_formatted|escape:'html':'UTF-8'}">
    <span class="hbe-tiers__fill" style="width: {$t.progress_total}%;"></span>
    {foreach from=$t.tiers item=tier}
      {assign var=tierState value=''}
      {if $t.applied && $t.applied.id == $tier.id}{assign var=tierState value='is-active'}
      {elseif $t.amount >= $tier.threshold}{assign var=tierState value='is-done'}
      {elseif $t.next && $t.next.id == $tier.id}{assign var=tierState value='is-next'}{/if}
      <span class="hbe-tiers__mark {$tierState}" style="left: {$tier.pos}%;" aria-hidden="true"></span>
    {/foreach}
  </div>
  <ol class="hbe-tiers__ladder">
    {foreach from=$t.tiers item=tier}
      {assign var=tierState value=''}
      {if $t.applied && $t.applied.id == $tier.id}{assign var=tierState value='is-active'}
      {elseif $t.amount >= $tier.threshold}{assign var=tierState value='is-done'}
      {elseif $t.next && $t.next.id == $tier.id}{assign var=tierState value='is-next'}{/if}
      <li class="hbe-tiers__step {$tierState}" style="left: {$tier.pos}%;">
        <span class="hbe-tiers__pct">−{$tier.percent_label}</span>
        <span class="hbe-tiers__from">{$hbe_tiers_l.tier_short|replace:'%percent% ':''|replace:'%amount%':$tier.threshold_short|escape:'html':'UTF-8'}</span>
      </li>
    {/foreach}
  </ol>

  {if $t.action && $t.action_tier}
    <form class="hbe-tiers__form" method="post" action="{$hbe_tiers_apply|escape:'html':'UTF-8'}">
      <input type="hidden" name="token" value="{$hbe_tiers_token|escape:'html':'UTF-8'}">
      <input type="hidden" name="code" value="{$t.action_tier.code|escape:'html':'UTF-8'}">
      <button type="submit" class="btn btn-primary hbe-tiers__cta">
        {if $t.action == 'upgrade'}
          {$hbe_tiers_l.cta_upgrade|replace:'%percent%':$t.action_tier.percent_label|escape:'html':'UTF-8'}
        {else}
          {$hbe_tiers_l.cta_apply|replace:'%percent%':$t.action_tier.percent_label|escape:'html':'UTF-8'}
        {/if}
      </button>
      <span class="hbe-tiers__code">{$hbe_tiers_l.code|replace:'%code%':$t.action_tier.code|escape:'html':'UTF-8'}</span>
    </form>
  {/if}

  {if $t.note}
    <p class="hbe-tiers__note">{$t.note|escape:'html':'UTF-8'}</p>
  {/if}
</div>
