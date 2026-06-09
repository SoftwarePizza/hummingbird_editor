{**
 * Hummingbird Editor — Slider (front)
 * Ported from bemo_slider; BEM classes (.hbe-slider__*) + data-ps-* JS bindings.
 *}
{if $hbe_slider.slides}
<div class="hbe-slider{if isset($hbe_slider.arrow_style) && $hbe_slider.arrow_style == 'corner'} hbe-slider--arrows-corner{/if}">
    <div class="hbe-slider__viewport"
         data-ps-component="hbe-slider"
         data-ps-speed="{$hbe_slider.speed|intval}"
         data-ps-autoplay="{$hbe_slider.autoplay|intval}"
         data-ps-pause="{$hbe_slider.pause|escape:'html':'UTF-8'}"
         data-ps-arrows="{$hbe_slider.show_arrows|intval}"
         data-ps-dots="{$hbe_slider.show_dots|intval}">

        <div class="hbe-slider__track" data-ps-ref="track">
            {foreach from=$hbe_slider.slides item=slide name=slides}
                <div class="hbe-slider__slide{if isset($slide.active_mobile) && !$slide.active_mobile} hbe-slider__slide--no-mobile{/if}{if $smarty.foreach.slides.first} is-active{/if}"
                     data-ps-ref="slide"{if isset($slide.active_mobile) && !$slide.active_mobile} data-ps-no-mobile="1"{/if}>
                    {if !empty($slide.url)}
                        <a href="{$slide.url|escape:'html':'UTF-8'}" class="hbe-slider__link">
                    {/if}

                    <div class="hbe-slider__media">
                        <picture>
                            {if !empty($slide.image_mobile_url)}
                                {if !empty($slide.image_mobile_webp_url)}
                                <source media="(max-width: 768px)" type="image/webp" srcset="{$slide.image_mobile_webp_url|escape:'html':'UTF-8'}">
                                {/if}
                                <source media="(max-width: 768px)" srcset="{$slide.image_mobile_url|escape:'html':'UTF-8'}">
                            {/if}
                            {if !empty($slide.image_webp_url)}
                                <source type="image/webp" srcset="{$slide.image_webp_url|escape:'html':'UTF-8'}">
                            {/if}
                            <img src="{$slide.image_url|escape:'html':'UTF-8'}"
                                 alt="{$slide.title|escape:'html':'UTF-8'}"
                                 loading="lazy" />
                        </picture>
                    </div>

                    {if ($slide.title || $slide.description) && (!isset($slide.show_text) || $slide.show_text == 1)}
                        <div class="hbe-slider__caption {if isset($slide.text_position) && $slide.text_position == 1}hbe-slider__caption--left{elseif isset($slide.text_position) && $slide.text_position == 2}hbe-slider__caption--bottom-left{else}hbe-slider__caption--center{/if}"
                             style="{if isset($slide.overlay_is_transparent) && $slide.overlay_is_transparent == 1}background: transparent;{else}background: {$slide.overlay_rgba};{/if}">
                            <div class="hbe-slider__caption-inner">
                                {if $slide.title}
                                    <h2 class="hbe-slider__title">{$slide.title|escape:'html':'UTF-8'}</h2>
                                {/if}
                                {if $slide.description}
                                    <div class="hbe-slider__desc">{$slide.description nofilter}</div>
                                {/if}
                                {if $slide.cta_enabled && $slide.cta_text}
                                    <a href="{if !empty($slide.url)}{$slide.url|escape:'html':'UTF-8'}{else}#{/if}"
                                       class="hbe-slider__cta hbe-slider__cta--{$slide.cta_size|default:'md'|escape:'html':'UTF-8'}"
                                       style="color:{$slide.cta_color|default:'#ffffff'|escape:'html':'UTF-8'};background:{$slide.cta_bg|default:'#000000'|escape:'html':'UTF-8'};border-radius:{$slide.cta_radius|default:4|intval}px">
                                        {$slide.cta_text|escape:'html':'UTF-8'}
                                    </a>
                                {/if}
                            </div>
                        </div>
                    {/if}

                    {if !empty($slide.url)}
                        </a>
                    {/if}
                </div>
            {/foreach}
        </div>

        {if $hbe_slider.show_arrows}
            <button type="button" class="hbe-slider__arrow hbe-slider__arrow--prev" data-ps-action="prev" aria-label="{l s='Poprzedni slajd' mod='hummingbird_editor'}">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            <button type="button" class="hbe-slider__arrow hbe-slider__arrow--next" data-ps-action="next" aria-label="{l s='Następny slajd' mod='hummingbird_editor'}">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        {/if}

        {if $hbe_slider.show_dots}
            <div class="hbe-slider__dots" data-ps-ref="dots">
                {foreach from=$hbe_slider.slides item=slide name=dots}
                    <button type="button" class="hbe-slider__dot{if $smarty.foreach.dots.first} is-active{/if}"
                            data-ps-action="dot" data-ps-slide="{$smarty.foreach.dots.index|intval}"
                            aria-label="{l s='Przejdź do slajdu' mod='hummingbird_editor'} {$smarty.foreach.dots.iteration|intval}">
                    </button>
                {/foreach}
            </div>
        {/if}
    </div>
</div>
{/if}
