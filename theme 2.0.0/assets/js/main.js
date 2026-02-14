/**
 * اسکریپت اصلی قالب فلیور نوول
 * Flavor Novel - Main JavaScript
 *
 * @package Flavor_Novel
 * @version 1.0.0
 */

(function () {
    'use strict';

    /* ========================================
     *  ابزارهای کمکی (Utilities)
     * ======================================== */
    const FN = {
        /**
         * انتخاب المان
         */
        $(selector, parent = document) {
            return parent.querySelector(selector);
        },

        /**
         * انتخاب چند المان
         */
        $$(selector, parent = document) {
            return [...parent.querySelectorAll(selector)];
        },

        /**
         * افزودن رویداد
         */
        on(el, event, handler, options = {}) {
            if (typeof el === 'string') el = this.$(el);
            if (el) el.addEventListener(event, handler, options);
        },

        /**
         * افزودن رویداد تفویضی (Event Delegation)
         */
        delegate(parent, event, selector, handler) {
            this.on(parent, event, (e) => {
                const target = e.target.closest(selector);
                if (target && (typeof parent === 'string' ? document.querySelector(parent) : parent).contains(target)) {
                    handler.call(target, e, target);
                }
            });
        },

        /**
         * ارسال درخواست AJAX
         */
        async ajax(action, data = {}, method = 'POST') {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('nonce', flavor_novel.nonce);

            for (const [key, value] of Object.entries(data)) {
                formData.append(key, value);
            }

            try {
                const response = await fetch(flavor_novel.ajax_url, {
                    method: method,
                    credentials: 'same-origin',
                    body: formData,
                });

                if (!response.ok) throw new Error(`HTTP Error: ${response.status}`);
                return await response.json();
            } catch (error) {
                console.error('AJAX Error:', error);
                FN.toast(flavor_novel.i18n.error, 'error');
                return null;
            }
        },

        /**
         * ارسال درخواست REST API
         */
        async rest(endpoint, params = {}, method = 'GET') {
            let url = flavor_novel.rest_url + endpoint;

            if (method === 'GET' && Object.keys(params).length) {
                url += '?' + new URLSearchParams(params).toString();
            }

            try {
                const options = {
                    method: method,
                    headers: {
                        'X-WP-Nonce': flavor_novel.rest_nonce,
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin',
                };

                if (method !== 'GET') {
                    options.body = JSON.stringify(params);
                }

                const response = await fetch(url, options);
                if (!response.ok) throw new Error(`REST Error: ${response.status}`);
                return await response.json();
            } catch (error) {
                console.error('REST Error:', error);
                return null;
            }
        },

        /**
         * نمایش پیام Toast
         */
        toast(message, type = 'success', duration = 3000) {
            const container = document.getElementById('fnToastContainer');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = `fn-toast fn-toast--${type}`;
            toast.innerHTML = `
                <span class="fn-toast__icon">${type === 'success' ? '✓' : '✕'}</span>
                <span class="fn-toast__text">${message}</span>
            `;

            container.appendChild(toast);

            // حذف خودکار
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(20px)';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        },

        /**
         * Debounce
         */
        debounce(func, wait = 300) {
            let timeout;
            return function (...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        },

        /**
         * ذخیره در localStorage
         */
        storage: {
            get(key, fallback = null) {
                try {
                    const value = localStorage.getItem(`fn_${key}`);
                    return value ? JSON.parse(value) : fallback;
                } catch {
                    return fallback;
                }
            },
            set(key, value) {
                try {
                    localStorage.setItem(`fn_${key}`, JSON.stringify(value));
                } catch (e) {
                    console.warn('localStorage Error:', e);
                }
            },
            remove(key) {
                localStorage.removeItem(`fn_${key}`);
            }
        },

        /**
         * تبدیل عدد به فارسی
         */
        persianNumber(str) {
            const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            return String(str).replace(/[0-9]/g, (d) => persian[parseInt(d)]);
        },
    };

    // ساختن شیء سراسری
    window.FN = FN;

    /* ========================================
     *  حالت تاریک / روشن (Dark Mode)
     * ======================================== */
    const ThemeManager = {
        init() {
            // خواندن تنظیمات ذخیره‌شده
            const savedTheme = FN.storage.get('theme', 'light');
            this.setTheme(savedTheme);

            // رویداد دکمه تاگل
            const toggleBtn = document.getElementById('fnThemeToggle');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', () => {
                    const current = document.documentElement.getAttribute('data-theme');
                    const next = current === 'dark' ? 'light' : 'dark';
                    this.setTheme(next);
                });
            }
        },

        setTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            FN.storage.set('theme', theme);

            // تغییر آیکون
            const sunIcon = document.getElementById('fnIconSun');
            const moonIcon = document.getElementById('fnIconMoon');

            if (sunIcon && moonIcon) {
                if (theme === 'dark') {
                    sunIcon.classList.add('fn-hidden');
                    moonIcon.classList.remove('fn-hidden');
                } else {
                    sunIcon.classList.remove('fn-hidden');
                    moonIcon.classList.add('fn-hidden');
                }
            }

            // بروزرسانی meta theme-color
            const metaTheme = document.querySelector('meta[name="theme-color"]');
            if (metaTheme) {
                metaTheme.content = theme === 'dark' ? '#1A1A2E' : '#6C5CE7';
            }
        }
    };

    /* ========================================
     *  منوی موبایل (Mobile Menu)
     * ======================================== */
    const MobileMenu = {
        init() {
            const hamburger = document.getElementById('fnHamburger');
            const nav = document.getElementById('fnMainNav');
            const overlay = document.getElementById('fnOverlay');

            if (!hamburger || !nav) return;

            hamburger.addEventListener('click', () => {
                const isOpen = nav.classList.contains('open');
                this.toggle(!isOpen, nav, hamburger, overlay);
            });

            if (overlay) {
                overlay.addEventListener('click', () => {
                    this.toggle(false, nav, hamburger, overlay);
                });
            }

            // بستن با Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && nav.classList.contains('open')) {
                    this.toggle(false, nav, hamburger, overlay);
                }
            });
        },

        toggle(open, nav, hamburger, overlay) {
            nav.classList.toggle('open', open);
            hamburger.setAttribute('aria-expanded', open);
            if (overlay) overlay.classList.toggle('active', open);
            document.body.style.overflow = open ? 'hidden' : '';

            // انیمیشن همبرگر
            const spans = hamburger.querySelectorAll('span');
            if (open) {
                spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                spans[1].style.opacity = '0';
                spans[2].style.transform = 'rotate(-45deg) translate(5px, -5px)';
            } else {
                spans[0].style.transform = '';
                spans[1].style.opacity = '';
                spans[2].style.transform = '';
            }
        }
    };

    /* ========================================
     *  جستجوی موبایل
     * ======================================== */
    const MobileSearch = {
        init() {
            const openBtn = document.getElementById('fnMobileSearchBtn');
            const modal = document.getElementById('fnMobileSearch');
            const closeBtn = document.getElementById('fnMobileSearchClose');

            if (!openBtn || !modal) return;

            openBtn.addEventListener('click', () => {
                modal.classList.add('active');
                const input = modal.querySelector('input[type="search"]');
                if (input) setTimeout(() => input.focus(), 100);
            });

            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    modal.classList.remove('active');
                });
            }

            // بستن با Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && modal.classList.contains('active')) {
                    modal.classList.remove('active');
                }
            });
        }
    };

    /* ========================================
     *  منوی کاربری (User Dropdown)
     * ======================================== */
    const UserMenu = {
        init() {
            const btn = document.getElementById('fnUserMenuBtn');
            const dropdown = document.getElementById('fnUserDropdown');

            if (!btn || !dropdown) return;

            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdown.classList.toggle('open');
            });

            // بستن با کلیک بیرون
            document.addEventListener('click', (e) => {
                if (!e.target.closest('#fnUserMenu')) {
                    dropdown.classList.remove('open');
                }
            });
        }
    };

    /* ========================================
     *  هدر چسبان (Sticky Header)
     * ======================================== */
    const StickyHeader = {
        init() {
            const header = document.getElementById('fnHeader');
            if (!header) return;

            let lastScroll = 0;

            window.addEventListener('scroll', FN.debounce(() => {
                const currentScroll = window.pageYOffset;

                if (currentScroll <= 0) {
                    header.classList.remove('fn-header--hidden');
                    return;
                }

                // مخفی کردن هنگام اسکرول پایین
                if (currentScroll > lastScroll && currentScroll > 80) {
                    header.classList.add('fn-header--hidden');
                } else {
                    header.classList.remove('fn-header--hidden');
                }

                lastScroll = currentScroll;
            }, 50));
        }
    };

    /* ========================================
     *  دکمه بازگشت به بالا
     * ======================================== */
    const BackToTop = {
        init() {
            const btn = document.getElementById('fnBackToTop');
            if (!btn) return;

            // نمایش/مخفی‌سازی
            window.addEventListener('scroll', FN.debounce(() => {
                if (window.pageYOffset > 400) {
                    btn.classList.add('visible');
                } else {
                    btn.classList.remove('visible');
                }
            }, 100));

            // اسکرول به بالا
            btn.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
    };

    /* ========================================
     *  اسلایدر صفحه اصلی
     * ======================================== */
    const HeroSlider = {
        currentSlide: 0,
        totalSlides: 0,
        autoplayTimer: null,

        init() {
            const slider = document.getElementById('fnHeroSlider');
            if (!slider) return;

            const slides = FN.$$('.fn-hero-slide', slider);
            const dots = FN.$$('.fn-slider-dot', slider);

            this.totalSlides = slides.length;
            if (this.totalSlides <= 1) return;

            // رویداد کلیک روی دات‌ها
            dots.forEach((dot, index) => {
                dot.addEventListener('click', () => {
                    this.goTo(index, slides, dots);
                });
            });

            // دکمه‌های قبل و بعد
            const prevBtn = FN.$('.fn-slider-prev', slider);
            const nextBtn = FN.$('.fn-slider-next', slider);

            if (prevBtn) prevBtn.addEventListener('click', () => this.prev(slides, dots));
            if (nextBtn) nextBtn.addEventListener('click', () => this.next(slides, dots));

            // اجرای خودکار
            this.startAutoplay(slides, dots);

            // توقف خودکار هنگام هاور
            slider.addEventListener('mouseenter', () => this.stopAutoplay());
            slider.addEventListener('mouseleave', () => this.startAutoplay(slides, dots));

            // پشتیبانی از سوایپ (لمسی)
            this.initSwipe(slider, slides, dots);
        },

        goTo(index, slides, dots) {
            slides[this.currentSlide].classList.remove('active');
            dots[this.currentSlide].classList.remove('active');

            this.currentSlide = index;

            slides[this.currentSlide].classList.add('active');
            dots[this.currentSlide].classList.add('active');
        },

        next(slides, dots) {
            const nextIndex = (this.currentSlide + 1) % this.totalSlides;
            this.goTo(nextIndex, slides, dots);
        },

        prev(slides, dots) {
            const prevIndex = (this.currentSlide - 1 + this.totalSlides) % this.totalSlides;
            this.goTo(prevIndex, slides, dots);
        },

        startAutoplay(slides, dots) {
            this.stopAutoplay();
            this.autoplayTimer = setInterval(() => this.next(slides, dots), 5000);
        },

        stopAutoplay() {
            if (this.autoplayTimer) clearInterval(this.autoplayTimer);
        },

        initSwipe(slider, slides, dots) {
            let startX = 0;
            let endX = 0;

            slider.addEventListener('touchstart', (e) => {
                startX = e.changedTouches[0].screenX;
            }, { passive: true });

            slider.addEventListener('touchend', (e) => {
                endX = e.changedTouches[0].screenX;
                const diff = startX - endX;

                if (Math.abs(diff) > 50) {
                    // در RTL جهت برعکس
                    if (diff < 0) {
                        this.next(slides, dots);
                    } else {
                        this.prev(slides, dots);
                    }
                }
            }, { passive: true });
        }
    };

    /* ========================================
     *  انیمیشن ورودی المان‌ها (Fade In)
     * ======================================== */
    const FadeInObserver = {
        init() {
            const elements = FN.$$('.fn-fade-in');
            if (!elements.length) return;

            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('visible');
                            observer.unobserve(entry.target);
                        }
                    });
                },
                { threshold: 0.1, rootMargin: '0px 0px -50px 0px' }
            );

            elements.forEach((el) => observer.observe(el));
        }
    };

    /* ========================================
     *  جستجوی زنده (Live Search)
     * ======================================== */
    const LiveSearch = {
        init() {
            const searchInputs = FN.$$('.fn-search__input');

            searchInputs.forEach((input) => {
                input.addEventListener('input', FN.debounce(async (e) => {
                    const query = e.target.value.trim();
                    const resultsContainer = input.closest('.fn-search')?.querySelector('.fn-search-results')
                        || document.getElementById('fnSearchResults');

                    if (!resultsContainer) return;

                    if (query.length < 2) {
                        resultsContainer.innerHTML = '';
                        resultsContainer.classList.remove('active');
                        return;
                    }

                    resultsContainer.innerHTML = `<div class="fn-search-loading">${flavor_novel.i18n.loading}</div>`;
                    resultsContainer.classList.add('active');

                    const data = await FN.rest('novels', { search: query, per_page: 5 });

                    if (data && data.novels && data.novels.length > 0) {
                        let html = '';
                        data.novels.forEach((novel) => {
                            html += `
                                <a href="${novel.url}" class="fn-search-result__item">
                                    <img src="${novel.cover || ''}" alt="" class="fn-search-result__cover" loading="lazy">
                                    <div class="fn-search-result__info">
                                        <div class="fn-search-result__title">${novel.title}</div>
                                        <div class="fn-search-result__meta">
                                            ${novel.genres ? novel.genres.join('، ') : ''}
                                            · ${novel.chapter_count || 0} فصل
                                        </div>
                                    </div>
                                </a>
                            `;
                        });
                        resultsContainer.innerHTML = html;
                    } else {
                        resultsContainer.innerHTML = '<div class="fn-search-empty">نتیجه‌ای یافت نشد</div>';
                    }
                }, 400));

                // بستن نتایج هنگام کلیک بیرون
                document.addEventListener('click', (e) => {
                    if (!e.target.closest('.fn-search')) {
                        const results = document.getElementById('fnSearchResults');
                        if (results) {
                            results.classList.remove('active');
                        }
                    }
                });
            });
        }
    };

    /* ========================================
     *  تب‌ها (Tabs)
     * ======================================== */
    const Tabs = {
        init() {
            FN.delegate(document, 'click', '.fn-tab', (e, tab) => {
                const tabGroup = tab.closest('.fn-tabs');
                const targetId = tab.dataset.tab;

                if (!tabGroup || !targetId) return;

                // فعال کردن تب
                FN.$$('.fn-tab', tabGroup).forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                // نمایش محتوای تب
                const parent = tabGroup.parentElement;
                FN.$$('.fn-tab-content', parent).forEach(content => {
                    content.classList.toggle('active', content.id === targetId);
                });
            });
        }
    };

    /* ========================================
     *  Lazy Loading تصاویر
     * ======================================== */
    const LazyLoad = {
        init() {
            if ('loading' in HTMLImageElement.prototype) {
                // مرورگر از loading=lazy پشتیبانی می‌کند
                FN.$$('img[data-src]').forEach((img) => {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                });
            } else {
                // فالبک با Intersection Observer
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                            observer.unobserve(img);
                        }
                    });
                });

                FN.$$('img[data-src]').forEach((img) => observer.observe(img));
            }
        }
    };

    /* ========================================
     *  امتیازدهی ستاره‌ای
     * ======================================== */
    const StarRating = {
        init() {
            FN.delegate(document, 'click', '.fn-star-icon[data-rating]', async (e, star) => {
                if (!flavor_novel.is_user_logged_in) {
                    FN.toast(flavor_novel.i18n.login_required, 'error');
                    return;
                }

                const container = star.closest('.fn-stars');
                const novelId = container?.dataset.novelId;
                const rating = parseInt(star.dataset.rating);

                if (!novelId || !rating) return;

                // بروزرسانی ویژوالی فوری
                FN.$$('.fn-star-icon', container).forEach((s, i) => {
                    s.classList.toggle('filled', i < rating);
                });

                // ارسال به سرور
                const result = await FN.ajax('fn_rate_novel', {
                    novel_id: novelId,
                    rating: rating,
                });

                if (result?.success) {
                    FN.toast(flavor_novel.i18n.rate_success);
                    // بروزرسانی نمایش میانگین
                    const avgEl = container?.closest('.fn-rating-wrap')?.querySelector('.fn-rating-avg');
                    if (avgEl && result.data?.avg_rating) {
                        avgEl.textContent = parseFloat(result.data.avg_rating).toFixed(1);
                    }
                } else {
                    FN.toast(result?.data?.message || flavor_novel.i18n.rate_error, 'error');
                }
            });

            // هاور روی ستاره‌ها
            FN.delegate(document, 'mouseenter', '.fn-star-icon[data-rating]', (e, star) => {
                const container = star.closest('.fn-stars');
                const rating = parseInt(star.dataset.rating);

                FN.$$('.fn-star-icon', container).forEach((s, i) => {
                    s.classList.toggle('hover', i < rating);
                });
            });

            FN.delegate(document, 'mouseleave', '.fn-stars', (e, container) => {
                FN.$$('.fn-star-icon', container).forEach((s) => {
                    s.classList.remove('hover');
                });
            });
        }
    };

    /* ========================================
     *  تب‌های رنکینگ (جداگانه از تب عمومی)
     * ======================================== */
    const RankingTabs = {
        init() {
            const tabsContainer = document.getElementById('fnRankingTabs');
            if (!tabsContainer) return;

            tabsContainer.addEventListener('click', (e) => {
                const tab = e.target.closest('.fn-ranking__tab');
                if (!tab) return;

                const targetId = tab.dataset.rankTab;
                if (!targetId) return;

                // غیرفعال کردن همه تب‌ها
                tabsContainer.querySelectorAll('.fn-ranking__tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                // مخفی کردن همه محتواها
                const ranking = tabsContainer.closest('.fn-ranking');
                ranking.querySelectorAll('.fn-rank-content').forEach(c => c.classList.remove('active'));

                // نمایش محتوای انتخابی
                const target = document.getElementById(targetId);
                if (target) target.classList.add('active');
            });
        }
    };

    /* ========================================
     *  سیستم لایک/دیسلایک نظرات
     * ======================================== */
    const CommentVotes = {
        init() {
            FN.delegate(document, 'click', '.fn-vote-btn', async (e, btn) => {
                const wrap = btn.closest('.fn-comment-votes');
                const commentId = wrap.dataset.commentId;
                const type = btn.dataset.type;

                const result = await FN.ajax('fn_vote_comment', { comment_id: commentId, type: type });
                if (result?.success) {
                    wrap.querySelector('.fn-like-count').textContent = result.data.likes;
                    wrap.querySelector('.fn-dislike-count').textContent = result.data.dislikes;
                    wrap.querySelectorAll('.fn-vote-btn').forEach(b => b.classList.remove('voted'));
                    if (result.data.action === 'voted') btn.classList.add('voted');
                }
            });
        }
    };

    /* ========================================
     *  مقداردهی اولیه (Initialize)
     * ======================================== */
    document.addEventListener('DOMContentLoaded', () => {
        ThemeManager.init();
        MobileMenu.init();
        MobileSearch.init();
        UserMenu.init();
        StickyHeader.init();
        BackToTop.init();
        HeroSlider.init();
        FadeInObserver.init();
        LiveSearch.init();
        Tabs.init();
        RankingTabs.init();
        LazyLoad.init();
        StarRating.init();
        CommentVotes.init();

        console.log('🔮 Flavor Novel Theme Loaded Successfully');
    });

})();