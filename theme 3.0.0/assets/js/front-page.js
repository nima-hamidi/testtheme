/**
 * Front Page JavaScript
 * صفحه اصلی سایت ناول
 *
 * @package suspended-starter
 */

(function () {
    'use strict';

    /* ══════════════════════════════════════
       Utilities
       ══════════════════════════════════════ */
    const $ = (sel, ctx = document) => ctx.querySelector(sel);
    const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

    /* ══════════════════════════════════════
       1. Intersection Observer — fadeInUp
       ══════════════════════════════════════ */
    function initSectionAnimations() {
        const sections = $$('.fp-section');
        if (!sections.length) return;

        // Hero بلافاصله نمایش داده شود
        const hero = $('.fp-hero');
        if (hero) hero.classList.add('fp-visible');

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fp-visible');
                        observer.unobserve(entry.target);
                    }
                });
            },
            {
                threshold: 0.1,
                rootMargin: '0px 0px -40px 0px',
            }
        );

        sections.forEach((section) => {
            if (!section.classList.contains('fp-hero')) {
                observer.observe(section);
            }
        });
    }

    /* ══════════════════════════════════════
       2. Announcement Banner
       ══════════════════════════════════════ */
    function initAnnouncement() {
        const banner = $('#fp-announcement');
        if (!banner) return;

        const bannerId = banner.dataset.bannerId || 'default';
        const storageKey = 'novel_banner_closed_' + bannerId;
        const stored = localStorage.getItem(storageKey);

        // بررسی بسته شدن قبلی (۲۴ ساعت)
        if (stored) {
            const closedAt = parseInt(stored, 10);
            const now = Date.now();
            const twentyFourHours = 24 * 60 * 60 * 1000;
            if (now - closedAt < twentyFourHours) {
                banner.remove();
                return;
            }
            localStorage.removeItem(storageKey);
        }

        // نمایش با انیمیشن slideDown
        banner.style.display = '';
        banner.style.overflow = 'hidden';
        banner.style.maxHeight = '0';
        banner.style.opacity = '0';
        banner.style.transition = 'max-height 0.5s ease, opacity 0.5s ease, padding 0.5s ease';

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                banner.style.maxHeight = '200px';
                banner.style.opacity = '1';
            });
        });

        // دکمه بستن
        const closeBtn = banner.querySelector('.fp-announcement__close');
        if (closeBtn) {
            closeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                localStorage.setItem(storageKey, Date.now().toString());

                banner.style.maxHeight = '0';
                banner.style.opacity = '0';
                banner.style.paddingTop = '0';
                banner.style.paddingBottom = '0';

                setTimeout(() => banner.remove(), 500);
            });
        }
    }

    /* ══════════════════════════════════════
       3. Hero Slider
       ══════════════════════════════════════ */
    function initHeroSlider() {
        const slider = $('.fp-hero__slider');
        if (!slider) return;

        const slides = $$('.fp-hero__slide', slider);
        const dots = $$('.fp-hero__dot', slider);
        const prevBtn = $('.fp-hero__nav-btn--prev', slider);
        const nextBtn = $('.fp-hero__nav-btn--next', slider);
        const autoPlayDelay = parseInt(slider.dataset.autoPlay, 10) || 5000;
        const totalSlides = slides.length;

        if (totalSlides <= 1) return;

        let currentSlide = 0;
        let autoPlayTimer = null;
        let isTransitioning = false;

        function goToSlide(index) {
            if (isTransitioning || index === currentSlide) return;
            isTransitioning = true;

            // حذف active قبلی
            slides[currentSlide].classList.remove('fp-hero__slide--active');
            if (dots[currentSlide]) {
                dots[currentSlide].classList.remove('fp-hero__dot--active');
            }

            // فعال کردن جدید
            currentSlide = ((index % totalSlides) + totalSlides) % totalSlides;
            slides[currentSlide].classList.add('fp-hero__slide--active');
            if (dots[currentSlide]) {
                dots[currentSlide].classList.add('fp-hero__dot--active');
            }

            setTimeout(() => {
                isTransitioning = false;
            }, 800);
        }

        function nextSlide() {
            goToSlide(currentSlide + 1);
        }

        function prevSlide() {
            goToSlide(currentSlide - 1);
        }

        function startAutoPlay() {
            stopAutoPlay();
            autoPlayTimer = setInterval(nextSlide, autoPlayDelay);
        }

        function stopAutoPlay() {
            if (autoPlayTimer) {
                clearInterval(autoPlayTimer);
                autoPlayTimer = null;
            }
        }

        // Event Listeners
        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                nextSlide();
                startAutoPlay();
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                prevSlide();
                startAutoPlay();
            });
        }

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                goToSlide(index);
                startAutoPlay();
            });
        });

        // Swipe support (touch)
        let touchStartX = 0;
        let touchEndX = 0;
        const swipeThreshold = 50;

        slider.addEventListener(
            'touchstart',
            (e) => {
                touchStartX = e.changedTouches[0].screenX;
                stopAutoPlay();
            },
            { passive: true }
        );

        slider.addEventListener(
            'touchend',
            (e) => {
                touchEndX = e.changedTouches[0].screenX;
                const diff = touchStartX - touchEndX;

                // RTL: swipe right = next, swipe left = prev
                const isRTL = document.documentElement.dir === 'rtl';

                if (Math.abs(diff) > swipeThreshold) {
                    if (isRTL) {
                        diff > 0 ? prevSlide() : nextSlide();
                    } else {
                        diff > 0 ? nextSlide() : prevSlide();
                    }
                }
                startAutoPlay();
            },
            { passive: true }
        );

        // Pause on hover
        slider.addEventListener('mouseenter', stopAutoPlay);
        slider.addEventListener('mouseleave', startAutoPlay);

        // Pause when tab hidden
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                stopAutoPlay();
            } else {
                startAutoPlay();
            }
        });

        // Keyboard navigation
        slider.setAttribute('tabindex', '0');
        slider.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') {
                // RTL: ArrowLeft = next
                document.documentElement.dir === 'rtl' ? nextSlide() : prevSlide();
                startAutoPlay();
            } else if (e.key === 'ArrowRight') {
                document.documentElement.dir === 'rtl' ? prevSlide() : nextSlide();
                startAutoPlay();
            }
        });

        // Start
        startAutoPlay();
    }

    /* ══════════════════════════════════════
       4. Horizontal Scroll Sliders
       (Continue Reading + Trending)
       ══════════════════════════════════════ */
    function initHorizontalSliders() {
        const sliders = $$('.fp-continue__slider, .fp-trending__slider');

        sliders.forEach((slider) => {
            const track = slider.querySelector(
                '.fp-continue__track, .fp-trending__track'
            );
            if (!track) return;

            let isDown = false;
            let startX;
            let scrollLeft;

            // Mouse drag scroll
            slider.addEventListener('mousedown', (e) => {
                isDown = true;
                slider.classList.add('fp-slider--grabbing');
                startX = e.pageX - slider.offsetLeft;
                scrollLeft = slider.scrollLeft;
                e.preventDefault();
            });

            slider.addEventListener('mouseleave', () => {
                isDown = false;
                slider.classList.remove('fp-slider--grabbing');
            });

            slider.addEventListener('mouseup', () => {
                isDown = false;
                slider.classList.remove('fp-slider--grabbing');
            });

            slider.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - slider.offsetLeft;
                const walk = (x - startX) * 1.5;
                slider.scrollLeft = scrollLeft - walk;
            });

            // Cursor style
            slider.style.cursor = 'grab';
            slider.addEventListener('mousedown', () => {
                slider.style.cursor = 'grabbing';
            });
            slider.addEventListener('mouseup', () => {
                slider.style.cursor = 'grab';
            });
            slider.addEventListener('mouseleave', () => {
                slider.style.cursor = 'grab';
            });
        });
    }

    /* ══════════════════════════════════════
       5. Mood Filter — Inline Results
       ══════════════════════════════════════ */
    function initMoodFilter() {
        const moodCards = $$('.fp-mood__card');
        const resultsContainer = $('#fp-mood-results');

        if (!moodCards.length || !resultsContainer) return;

        const resultsTitle = resultsContainer.querySelector('.fp-mood__results-title');
        const resultsGrid = resultsContainer.querySelector('.fp-mood__results-grid');
        const closeBtn = resultsContainer.querySelector('.fp-mood__results-close');

        moodCards.forEach((card) => {
            card.addEventListener('click', (e) => {
                // اگر کاربر می‌خواهد به صفحه جستجو برود، اجازه بده
                // اما اگر JS فعال است، نتایج inline نمایش بده
                e.preventDefault();

                const mood = card.dataset.mood;
                const label = card.querySelector('.fp-mood__label')?.textContent || '';
                const emoji = card.querySelector('.fp-mood__emoji')?.textContent || '';

                // Active state
                moodCards.forEach((c) => c.classList.remove('fp-mood__card--active'));
                card.classList.add('fp-mood__card--active');

                // Show loading
                resultsContainer.style.display = 'block';
                resultsTitle.textContent = emoji + ' ' + label;
                resultsGrid.innerHTML = generateSkeletons(6);

                // AJAX fetch
                fetchMoodResults(mood, card.href)
                    .then((html) => {
                        resultsGrid.innerHTML = html;
                    })
                    .catch(() => {
                        // fallback: redirect
                        window.location.href = card.href;
                    });

                // Scroll to results
                resultsContainer.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                });
            });
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                resultsContainer.style.display = 'none';
                moodCards.forEach((c) => c.classList.remove('fp-mood__card--active'));
            });
        }
    }

    function generateSkeletons(count) {
        let html = '';
        for (let i = 0; i < count; i++) {
            html += `<div class="fp-skeleton" style="height:200px;border-radius:12px;"></div>`;
        }
        return html;
    }

    function fetchMoodResults(mood, fallbackUrl) {
        // eslint-disable-next-line no-undef
        const ajaxUrl =
            typeof novelFrontPage !== 'undefined' && novelFrontPage.ajaxUrl
                ? novelFrontPage.ajaxUrl
                : (typeof novelData !== 'undefined' && novelData.ajaxUrl
                    ? novelData.ajaxUrl
                    : '/wp-admin/admin-ajax.php');

        const nonce =
            typeof novelFrontPage !== 'undefined' && novelFrontPage.nonce
                ? novelFrontPage.nonce
                : (typeof novelData !== 'undefined' && novelData.nonce
                    ? novelData.nonce
                    : '');

        const data = new FormData();
        data.append('action', 'novel_mood_results');
        data.append('mood', mood);
        data.append('nonce', nonce);

        return fetch(ajaxUrl, {
            method: 'POST',
            body: data,
            credentials: 'same-origin',
        })
            .then((res) => {
                if (!res.ok) throw new Error('Network error');
                return res.json();
            })
            .then((json) => {
                if (json.success && json.data && json.data.html) {
                    return json.data.html;
                }
                throw new Error('No data');
            });
    }

    /* ══════════════════════════════════════
       6. Lazy Load Images
       ══════════════════════════════════════ */
    function initLazyLoad() {
        // Native lazy loading is used via loading="lazy" attribute
        // This adds intersection observer fallback for older browsers
        if ('loading' in HTMLImageElement.prototype) return;

        const images = $$('img[loading="lazy"]');
        if (!images.length) return;

        const imageObserver = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                        }
                        imageObserver.unobserve(img);
                    }
                });
            },
            {
                rootMargin: '100px',
            }
        );

        images.forEach((img) => imageObserver.observe(img));
    }

    /* ══════════════════════════════════════
       Init
       ══════════════════════════════════════ */
    function init() {
        initAnnouncement();
        initHeroSlider();
        initSectionAnimations();
        initHorizontalSliders();
        initMoodFilter();
        initLazyLoad();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();