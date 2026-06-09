<section class="hbe-icons4" role="region">
  <div class="container">
    <div class="hbe-icons4__row">
      {foreach from=$hbe_icons4 item=col name=icols}
        <div class="hbe-icons4__col">
          {if $col.img_url}
            <div class="hbe-icons4__icon">
              {include file="./_picture.tpl"
                p_url=$col.img_url
                p_webp=$col.img_webp_url
                p_mobile=$col.img_mobile_url
                p_mobile_webp=$col.img_mobile_webp_url
                p_alt=($col.title|default:'')}
            </div>
          {/if}
          {if $col.title}
            <h3 class="hbe-icons4__title">{$col.title|escape:'html':'UTF-8'}</h3>
          {/if}
          {if $col.desc}
            <p class="hbe-icons4__desc">{$col.desc|escape:'html':'UTF-8'}</p>
          {/if}
        </div>
        {if !$smarty.foreach.icols.last}
          <div class="hbe-icons4__divider" aria-hidden="true"></div>
        {/if}
      {/foreach}
    </div>
  </div>
</section>
