<section class="hbe-splitblock" role="region">
  <div class="container">
    <div class="hbe-splitblock__inner">

      {* ── Left half: text col + middle image col ───────────────────────── *}
      <div class="hbe-splitblock__left-half">

        {* Left column: title + desc + CTA *}
        <div class="hbe-splitblock__col hbe-splitblock__col--text">
          {if $hbe_splitblock_title}
            <h2 class="hbe-splitblock__title">{$hbe_splitblock_title|escape:'html':'UTF-8'}</h2>
          {/if}
          {if $hbe_splitblock_desc}
            <p class="hbe-splitblock__desc">{$hbe_splitblock_desc|escape:'html':'UTF-8'}</p>
          {/if}
          {if $hbe_splitblock_cta_text}
            <a href="{if $hbe_splitblock_cta_url}{$hbe_splitblock_cta_url|escape:'html':'UTF-8'}{else}#{/if}"
               class="hbe-splitblock__cta">
              {$hbe_splitblock_cta_text|escape:'html':'UTF-8'}
            </a>
          {/if}
        </div>

        {* Middle column: image centred at 50% width *}
        {if $hbe_splitblock_m_img_url}
        <div class="hbe-splitblock__col hbe-splitblock__col--mid">
          {include file="./_picture.tpl"
            p_url=$hbe_splitblock_m_img_url
            p_webp=$hbe_splitblock_m_img_webp_url
            p_mobile=$hbe_splitblock_m_img_mobile_url
            p_mobile_webp=$hbe_splitblock_m_img_mobile_webp_url
            p_class='hbe-splitblock__mid-img'}
        </div>
        {/if}

      </div>{* /left-half *}

      {* ── Right half: large image ──────────────────────────────────────── *}
      {if $hbe_splitblock_r_img_url}
      <div class="hbe-splitblock__right-half">
        {include file="./_picture.tpl"
          p_url=$hbe_splitblock_r_img_url
          p_webp=$hbe_splitblock_r_img_webp_url
          p_mobile=$hbe_splitblock_r_img_mobile_url
          p_mobile_webp=$hbe_splitblock_r_img_mobile_webp_url
          p_class='hbe-splitblock__right-img'}
      </div>
      {/if}

    </div>{* /inner *}
  </div>{* /container *}
</section>
