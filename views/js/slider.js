/**
 * Hummingbird Editor — Slider (front)
 *
 * Vanilla JS, no jQuery. Ported from bemo_slider; DOM bindings use data-ps-*
 * attributes (V2 rule), BEM classes are used only for styling/state.
 */
(function () {
    'use strict';

    var SEL_ROOT = '[data-ps-component="hbe-slider"]';
    var SEL_SLIDE = '[data-ps-ref="slide"]';
    var SEL_TRACK = '[data-ps-ref="track"]';
    var SEL_DOTS = '[data-ps-ref="dots"]';

    class HbEditorSlider {
        constructor(element) {
            this.slider = element;
            this.track = this.slider.querySelector(SEL_TRACK);
            this.allSlides = Array.from(this.slider.querySelectorAll(SEL_SLIDE));
            this.updateSlidesList();

            this.currentSlide = 0;
            this.isTransitioning = false;

            // Settings from data-ps-* attributes
            this.speed = parseInt(this.slider.getAttribute('data-ps-speed'), 10) || 5000;
            this.autoplay = parseInt(this.slider.getAttribute('data-ps-autoplay'), 10) === 1;
            this.pauseOnHover = this.slider.getAttribute('data-ps-pause') === 'hover';
            this.showArrows = parseInt(this.slider.getAttribute('data-ps-arrows'), 10) === 1;
            this.showDots = parseInt(this.slider.getAttribute('data-ps-dots'), 10) === 1;

            this.autoplayInterval = null;
            this.resizeTimeout = null;

            this.init();
        }

        updateSlidesList() {
            // Filter slides based on viewport width (mobile-hidden slides)
            this.slides = this.allSlides.filter((slide) => {
                if (window.innerWidth <= 768 && slide.hasAttribute('data-ps-no-mobile')) {
                    return false;
                }
                return true;
            });

            this.allSlides.forEach((slide) => {
                if (this.slides.includes(slide)) {
                    slide.style.display = '';
                } else {
                    slide.classList.remove('is-active');
                    slide.style.display = 'none';
                }
            });

            if (this.currentSlide >= this.slides.length) {
                this.currentSlide = 0;
            }

            if (this.slides.length > 0) {
                const activeSlide = this.slides.find((s) => s.classList.contains('is-active'));
                if (!activeSlide) {
                    this.slides[0].classList.add('is-active');
                    this.currentSlide = 0;
                } else {
                    this.currentSlide = this.slides.indexOf(activeSlide);
                }
            }
        }

        init() {
            if (this.slides.length <= 1) {
                this.adjustSliderHeight();
                if (this.slides.length === 0) return;
            }

            this.setupArrows();
            this.setupDots();
            this.setupTouchEvents();
            this.setupResizeObserver();

            this.adjustSliderHeight();
            this.updateTransform();

            if (this.autoplay && this.slides.length > 1) {
                this.stopAutoplay();
                this.startAutoplay();

                if (this.pauseOnHover) {
                    this.slider.addEventListener('mouseenter', () => this.stopAutoplay());
                    this.slider.addEventListener('mouseleave', () => {
                        if (this.slides.length > 1) this.startAutoplay();
                    });
                }
            }
        }

        setupResizeObserver() {
            window.addEventListener('resize', () => {
                clearTimeout(this.resizeTimeout);
                this.resizeTimeout = setTimeout(() => {
                    const previousSlidesLength = this.slides.length;

                    this.updateSlidesList();
                    this.updateTransform();

                    if (this.slides.length !== previousSlidesLength) {
                        this.reInitUI();
                    }

                    this.adjustSliderHeight();
                }, 150);
            });

            this.allSlides.forEach((slide) => {
                const img = slide.querySelector('img');
                if (img) {
                    img.addEventListener('load', () => {
                        if (slide.classList.contains('is-active')) {
                            this.adjustSliderHeight();
                        }
                    });
                }
            });
        }

        reInitUI() {
            this.setupDots();
            this.setupArrows();

            if (this.autoplay) {
                this.stopAutoplay();
                if (this.slides.length > 1) {
                    this.startAutoplay();
                }
            }
        }

        adjustSliderHeight() {
            const activeSlide = this.slides[this.currentSlide];
            if (!activeSlide) {
                if (this.track) this.track.style.height = '0px';
                return;
            }

            const img = activeSlide.querySelector('img');
            if (!img) return;

            if (!img.complete) {
                img.addEventListener('load', () => {
                    this.setContainerHeight(img);
                }, { once: true });
            } else {
                this.setContainerHeight(img);
            }
        }

        setContainerHeight(img) {
            const height = img.offsetHeight;
            if (height > 0 && this.track) {
                this.track.style.height = height + 'px';
            }
        }

        setupArrows() {
            // Behaviour preserved from source: arrows are wired via delegation
            // when present. (Source removed them; here we keep them functional.)
            if (this._arrowsBound) return;
            this._arrowsBound = true;
            this.slider.addEventListener('click', (e) => {
                const trigger = e.target.closest('[data-ps-action]');
                if (!trigger || !this.slider.contains(trigger)) return;
                const action = trigger.getAttribute('data-ps-action');
                if (action === 'prev') {
                    this.prevSlide();
                } else if (action === 'next') {
                    this.nextSlide();
                } else if (action === 'dot') {
                    const idx = parseInt(trigger.getAttribute('data-ps-slide'), 10);
                    if (!Number.isNaN(idx)) this.goToSlide(idx);
                }
            });
        }

        setupDots() {
            if (!this.showDots) return;

            let dotsContainer = this.slider.querySelector(SEL_DOTS);

            if (!dotsContainer && this.slides.length > 1) {
                dotsContainer = document.createElement('div');
                dotsContainer.className = 'hbe-slider__dots';
                dotsContainer.setAttribute('data-ps-ref', 'dots');
                this.slider.appendChild(dotsContainer);
            }

            if (dotsContainer) {
                dotsContainer.innerHTML = '';

                if (this.slides.length > 1) {
                    dotsContainer.style.display = '';
                    this.slides.forEach((_, index) => {
                        const dot = document.createElement('button');
                        dot.type = 'button';
                        dot.className = 'hbe-slider__dot' + (index === this.currentSlide ? ' is-active' : '');
                        dot.setAttribute('data-ps-action', 'dot');
                        dot.setAttribute('data-ps-slide', index);
                        dot.setAttribute('aria-label', 'Przejdź do slajdu ' + (index + 1));
                        dotsContainer.appendChild(dot);
                    });
                } else {
                    dotsContainer.style.display = 'none';
                }
            }
        }

        setupTouchEvents() {
            this.handleDrag = this.handleDrag.bind(this);
            const container = this.track;
            if (!container) return;

            this.dragState = {
                isDragging: false,
                startPos: 0,
                currentTranslate: 0,
                prevTranslate: 0,
                animationID: 0,
                currentIndex: 0,
            };

            container.addEventListener('touchstart', (e) => this.touchStart(e), { passive: true });
            container.addEventListener('touchend', () => this.touchEnd());
            container.addEventListener('touchmove', (e) => this.touchMove(e), { passive: false });

            container.addEventListener('mousedown', (e) => this.touchStart(e));
            container.addEventListener('mouseup', () => this.touchEnd());
            container.addEventListener('mouseleave', () => {
                if (this.dragState.isDragging) this.touchEnd();
            });
            container.addEventListener('mousemove', (e) => this.touchMove(e));

            container.addEventListener('click', (e) => {
                if (this.dragState.preventClick) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }, true);

            const images = container.querySelectorAll('img');
            images.forEach((img) => {
                img.addEventListener('dragstart', (e) => e.preventDefault());
            });
        }

        touchStart(index) {
            this.dragState.isDragging = true;
            this.dragState.currentIndex = this.currentSlide;

            const posX = (index.type.includes('mouse')) ? index.pageX : index.touches[0].clientX;
            this.dragState.startPos = posX;

            this.dragState.currentTranslate = -(this.currentSlide * 100);

            this.dragState.animationID = requestAnimationFrame(this.handleDrag);
            if (this.track) this.track.style.transition = 'none';
        }

        touchMove(event) {
            if (this.dragState.isDragging) {
                const currentPosition = (event.type.includes('mouse')) ? event.pageX : event.touches[0].clientX;

                const diff = currentPosition - this.dragState.startPos;
                const containerWidth = this.slider.offsetWidth;
                const percentMove = (diff / containerWidth) * 100;

                this.dragState.currentTranslate = -(this.currentSlide * 100) + percentMove;
                this.dragState.movedPixels = diff;
            }
        }

        touchEnd() {
            this.dragState.isDragging = false;
            cancelAnimationFrame(this.dragState.animationID);

            const movedPixels = this.dragState.movedPixels || 0;
            const threshold = 50;

            if (Math.abs(movedPixels) > 5) {
                this.dragState.preventClick = true;
                setTimeout(() => { this.dragState.preventClick = false; }, 100);
            }

            const canLoop = this.slides.length > 1;

            if (this.track) {
                this.track.style.transition = 'transform 0.6s cubic-bezier(0.25, 1, 0.5, 1), height 0.5s ease-in-out';
            }

            this.dragState.movedPixels = 0;

            if (movedPixels < -threshold) {
                if (this.currentSlide < this.slides.length - 1) {
                    this.nextSlide();
                } else if (canLoop) {
                    this.nextSlide();
                } else {
                    this.goToSlide(this.currentSlide);
                }
            } else if (movedPixels > threshold) {
                if (this.currentSlide > 0) {
                    this.prevSlide();
                } else if (canLoop) {
                    this.prevSlide();
                } else {
                    this.goToSlide(this.currentSlide);
                }
            } else {
                this.goToSlide(this.currentSlide);
            }
        }

        handleDrag() {
            if (this.dragState.isDragging && this.track) {
                this.track.style.transform = `translateX(${this.dragState.currentTranslate}%)`;
                requestAnimationFrame(this.handleDrag);
            }
        }

        updateTransform() {
            if (this.track) {
                this.track.style.transform = 'translateX(-' + (this.currentSlide * 100) + '%)';
            }
        }

        nextSlide() {
            if (this.isTransitioning) return;
            const next = (this.currentSlide + 1) % this.slides.length;
            this.goToSlide(next);
        }

        prevSlide() {
            if (this.isTransitioning) return;
            const prev = (this.currentSlide - 1 + this.slides.length) % this.slides.length;
            this.goToSlide(prev);
        }

        goToSlide(index) {
            if (this.isTransitioning || index === this.currentSlide) {
                if (!this.isTransitioning && this.dragState && this.dragState.isDragging === false) {
                    this.updateTransform();
                }
                return;
            }

            this.isTransitioning = true;

            this.slides[this.currentSlide].classList.remove('is-active');
            this.slides[index].classList.add('is-active');

            if (this.showDots) {
                const dots = this.slider.querySelectorAll('.hbe-slider__dot');
                if (dots[this.currentSlide]) dots[this.currentSlide].classList.remove('is-active');
                if (dots[index]) dots[index].classList.add('is-active');
            }

            this.currentSlide = index;
            this.updateTransform();
            this.adjustSliderHeight();

            setTimeout(() => {
                this.isTransitioning = false;
            }, 600);

            if (this.autoplay) {
                this.stopAutoplay();
                this.startAutoplay();
            }
        }

        startAutoplay() {
            if (this.autoplayInterval) {
                clearInterval(this.autoplayInterval);
            }
            this.autoplayInterval = setInterval(() => {
                this.nextSlide();
            }, this.speed);
        }

        stopAutoplay() {
            if (this.autoplayInterval) {
                clearInterval(this.autoplayInterval);
                this.autoplayInterval = null;
            }
        }
    }

    function initSliders() {
        document.querySelectorAll(SEL_ROOT).forEach((slider) => {
            new HbEditorSlider(slider);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSliders);
    } else {
        initSliders();
    }
})();
