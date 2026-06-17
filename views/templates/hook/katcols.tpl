<section class="hbe-katcols" role="region">
  <div class="container">

    {* ── Header row ──────────────────────────────────────────────────────── *}
    {if $hbe_katcols_title || $hbe_katcols_hdr_text || $hbe_katcols_hdr_link_text}
    <div class="hbe-katcols__header">
      <div class="hbe-katcols__header-left">
        {if $hbe_katcols_title}
          <h2 class="hbe-katcols__title">{$hbe_katcols_title|escape:'html':'UTF-8'}</h2>
        {/if}
      </div>
      <div class="hbe-katcols__header-right">
        {if $hbe_katcols_hdr_text}
          <span class="hbe-katcols__hdr-text">{$hbe_katcols_hdr_text|escape:'html':'UTF-8'}</span>
        {/if}
        {if $hbe_katcols_hdr_link_text}
          <a href="{if $hbe_katcols_hdr_url}{$hbe_katcols_hdr_url|escape:'html':'UTF-8'}{else}#{/if}"
             class="hbe-katcols__hdr-link">
            {$hbe_katcols_hdr_link_text|escape:'html':'UTF-8'}
          </a>
        {/if}
      </div>
    </div>
    {/if}

    {* ── Columns row ─────────────────────────────────────────────────────── *}
    <div class="hbe-katcols__cols">

      {* Left column — large image *}
      {if $hbe_katcols_l_img_url || $hbe_katcols_l_caption}
      <div class="hbe-katcols__col hbe-katcols__col--left">
        {if $hbe_katcols_l_url}<a href="{$hbe_katcols_l_url|escape:'html':'UTF-8'}" class="hbe-katcols__img-link">{/if}
          {if $hbe_katcols_l_img_url}
            <span class="hbe-katcols__media">
            {include file="./_picture.tpl"
              p_url=$hbe_katcols_l_img_url
              p_webp=$hbe_katcols_l_img_webp_url
              p_mobile=$hbe_katcols_l_img_mobile_url
              p_mobile_webp=$hbe_katcols_l_img_mobile_webp_url
              p_alt=$hbe_katcols_l_caption
              p_class='hbe-katcols__img'}
            </span>
          {/if}
          {if $hbe_katcols_l_caption}
            <span class="hbe-katcols__caption">{$hbe_katcols_l_caption|escape:'html':'UTF-8'}</span>
          {/if}
        {if $hbe_katcols_l_url}</a>{/if}
      </div>
      {/if}

      {* Right column — smaller image *}
      {if $hbe_katcols_r_img_url || $hbe_katcols_r_caption}
      <div class="hbe-katcols__col hbe-katcols__col--right">
        {if $hbe_katcols_r_url}<a href="{$hbe_katcols_r_url|escape:'html':'UTF-8'}" class="hbe-katcols__img-link">{/if}
          {if $hbe_katcols_r_img_url}
            <span class="hbe-katcols__media">
            {include file="./_picture.tpl"
              p_url=$hbe_katcols_r_img_url
              p_webp=$hbe_katcols_r_img_webp_url
              p_mobile=$hbe_katcols_r_img_mobile_url
              p_mobile_webp=$hbe_katcols_r_img_mobile_webp_url
              p_alt=$hbe_katcols_r_caption
              p_class='hbe-katcols__img'}
            </span>
          {/if}
          {if $hbe_katcols_r_caption}
            <span class="hbe-katcols__caption">{$hbe_katcols_r_caption|escape:'html':'UTF-8'}</span>
          {/if}
        {if $hbe_katcols_r_url}</a>{/if}
      </div>
      {/if}

    </div>{* /cols *}

  </div>{* /container *}
</section>
