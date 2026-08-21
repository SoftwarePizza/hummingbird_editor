{**
 * Progi rabatowe — sekcja strony głównej („Kupuj więcej, płać mniej”).
 * Zmienne: $hbe_tiers_home (title, text, cta_text, cta_url, tiers[], exclude_special), $hbe_tiers_l.
 *}
{assign var=h value=$hbe_tiers_home}
<section class="hbe-tiers-home" role="region" aria-labelledby="hbe-tiers-home-title">
  <div class="container">
    <div class="hbe-tiers-home__inner">
      <div class="hbe-tiers-home__intro">
        <h2 class="hbe-tiers-home__title" id="hbe-tiers-home-title">{$h.title|escape:'html':'UTF-8'}</h2>
        {if $h.text}
          <p class="hbe-tiers-home__text">{$h.text|escape:'html':'UTF-8'}</p>
        {/if}
        {if $h.exclude_special}
          <p class="hbe-tiers-home__note">{$hbe_tiers_l.exclude|escape:'html':'UTF-8'}</p>
        {/if}
        {if $h.cta_text && $h.cta_url}
          <a class="hbe-tiers-home__cta btn btn-primary" href="{$h.cta_url|escape:'html':'UTF-8'}">{$h.cta_text|escape:'html':'UTF-8'}</a>
        {/if}
      </div>

      <ol class="hbe-tiers-home__cards">
        {foreach from=$h.tiers item=tier name=tiers}
          <li class="hbe-tiers-home__card{if $smarty.foreach.tiers.last} hbe-tiers-home__card--top{/if}">
            <span class="hbe-tiers-home__pct">−{$tier.percent_label}</span>
            <span class="hbe-tiers-home__from">{$hbe_tiers_l.tier_short|replace:'%percent% ':''|replace:'%amount%':$tier.threshold_short|escape:'html':'UTF-8'}</span>
            <span class="hbe-tiers-home__code">{$hbe_tiers_l.code|replace:'%code%':$tier.code|escape:'html':'UTF-8'}</span>
          </li>
        {/foreach}
      </ol>
    </div>
  </div>
</section>
