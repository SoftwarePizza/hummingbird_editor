<section class="hbe-faq" style="background:{$hbe_faq_bg|escape:'html':'UTF-8'}">
  {foreach from=$hbe_faq_items item=item name=faqloop}
  <div class="hbe-faq__item{if $smarty.foreach.faqloop.first} hbe-faq__item--open{/if}"
       style="border-top-color:{$hbe_faq_border_color|escape:'html':'UTF-8'}">
    <button class="hbe-faq__question" aria-expanded="{if $smarty.foreach.faqloop.first}true{else}false{/if}"
            style="color:{$hbe_faq_question_color|escape:'html':'UTF-8'}">
      {$item.q|escape:'html':'UTF-8'}
      <span class="hbe-faq__icon" aria-hidden="true"></span>
    </button>
    <div class="hbe-faq__answer" style="color:{$hbe_faq_answer_color|escape:'html':'UTF-8'}">
      <div class="hbe-faq__answer-inner">{$item.a nofilter}</div>
    </div>
  </div>
  {/foreach}
</section>
