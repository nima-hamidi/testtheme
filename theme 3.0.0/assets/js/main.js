/**
 * فایل: assets/js/main.js
 * توضیح: جاوااسکریپت اصلی قالب ناول
 *         - Toggle دارک‌مود
 *         - Navigation شناور (مخفی/نمایش با اسکرول)
 *         - Header sticky (مخفی/نمایش)
 *         - Toast Notification System
 *         - Profile Dropdown
 *         - Mobile Menu
 *         - Bottom Sheet
 *         - Pull to Refresh
 *         - AJAX Helpers
 *         - اعداد فارسی
 * نسخه: 2.0.0
 * وابستگی: NovelAjax (wp_localize_script)
 */

(function () {
    'use strict';

    /* ═══════════════════════════════════════
       ۱. ابزارهای عمومی (Utility)
       ═══════════════════════════════════════ */

    const Novel = {
        /**
         * انتخابگر DOM
         */
        $(selector, parent = document) {
            return parent.querySelector(selector);
        },
        $$(selector, parent = document) {
            return [...parent.querySelectorAll(selector)];
        },

        /**
         * تبدیل اعداد به فارسی
         */
        faNum(str) {
            const en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            const fa = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            str = String(str);
            for (let i = 0; i < 10; i++) {
                str = str.replace(new RegExp(en[i], 'g'), fa[i]);
            }
            return str;
        },

        /**
         * فرمت عدد با جداکننده
         */
        formatNumber(num) {
            return this.faNum(Number(num).toLocaleString('en-US'));
        },

        /**
         * Debounce
         */
        debounce(fn, delay = 250) {
            let timer;
            return function (...args) {
                clearTimeout(timer);
                timer = setTimeout(() => fn.apply(this, args), delay);
            };
        },

        /**
         * Throttle
         */
        throttle(fn, limit = 100) {
            let waiting = false;
            return function (...args) {
                if (!waiting) {
                    fn.apply(this, args);
                    waiting = true;
                    setTimeout(() => { waiting = false; }, limit);
                }
            };
        },

        /**
         * AJAX Request ساده
         */
        async ajax(action, data = {}, method = 'POST') {
            const config = window.NovelAjax || {};
            const formData = new FormData();
            formData.append('action', action);
            formData.append('nonce', config.nonce || '');

            Object.keys(data).forEach(key => {
                formData.append(key, data[key]);
            });

            try {
                const response = await fetch(config.url || '/wp-admin/admin-ajax.php', {
                    method: method,
                    credentials: 'same-origin',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const json = await response.json();
                return json;

            } catch (error) {
                const i18n = config.i18n || {};
                Novel.toast(i18n.network_error || 'خطا در ارتباط با سرور', 'error');
                Novel.log('AJAX Error', error);
                throw error;
            }
        },

        /**
         * بررسی لاگین
         */
        isLoggedIn() {
            return window.NovelAjax && window.NovelAjax.is_logged_in;
        },

        /**
         * ریدایرکت به لاگین
         */
        requireLogin() {
            if (!this.isLoggedIn()) {
                const config = window.NovelAjax || {};
                this.toast(config.i18n?.login_required || 'برای این کار باید وارد شوید', 'warning');
                setTimeout(() => {
                    window.location.href = config.login_url || '/login/';
                }, 1200);
                return false;
            }
            return true;
        },

        /**
         * لاگ توسعه
         */
        log(...args) {
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                console.log('[Novel]', ...args);
            }
        }
    };

    /* ═══════════════════════════════════════
       ۲. دارک‌مود
       ═══════════════════════════════════════ */

    const ThemeManager = {
        STORAGE_KEY: 'novel-theme',

        init() {
            const saved = localStorage.getItem(this.STORAGE_KEY);
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

            if (saved === 'dark' || (!saved && prefersDark)) {
                this.setTheme('dark', false);
            } else {
                this.setTheme('light', false);
            }

            // Listen to system changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (!localStorage.getItem(this.STORAGE_KEY)) {
                    this.setTheme(e.matches ? 'dark' : 'light', false);
                }
            });

            // دکمه‌های toggle
            Novel.$$('.novel-theme-toggle').forEach(btn => {
                btn.addEventListener('click', () => this.toggle());
            });
        },

        setTheme(theme, save = true) {
            document.documentElement.setAttribute('data-theme', theme);
            if (save) {
                localStorage.setItem(this.STORAGE_KEY, theme);
            }

            // آپدیت آیکون‌ها
            Novel.$$('.novel-theme-toggle').forEach(btn => {
                btn.setAttribute('aria-label', theme === 'dark' ? 'حالت روشن' : 'حالت تاریک');
            });
        },

        toggle() {
            const current = document.documentElement.getAttribute('data-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            this.setTheme(next);

            // انیمیشن rotate
            Novel.$$('.novel-theme-toggle .novel-icon').forEach(icon => {
                icon.style.transition = 'transform 400ms ease';
                icon.style.transform = 'rotate(360deg)';
                setTimeout(() => {
                    icon.style.transform = '';
                }, 400);
            });
        },

        isDark() {
            return document.documentElement.getAttribute('data-theme') === 'dark';
        }
    };

    /* ═══════════════════════════════════════
       ۳. Header Sticky (مخفی/نمایش)
       ═══════════════════════════════════════ */

    const HeaderManager = {
        init() {
            this.header = Novel.$('.novel-header');
            if (!this.header) return;

            this.lastScrollY = 0;
            this.ticking = false;

            window.addEventListener('scroll', () => {
                if (!this.ticking) {
                    requestAnimationFrame(() => {
                        this.onScroll();
                        this.ticking = false;
                    });
                    this.ticking = true;
                }
            }, { passive: true });
        },

        onScroll() {
            const scrollY = window.scrollY;
            const direction = scrollY > this.lastScrollY ? 'down' : 'up';

            if (direction === 'down' && scrollY > 100) {
                this.header.classList.add('header-hidden');
            } else {
                this.header.classList.remove('header-hidden');
            }

            this.lastScrollY = scrollY;
        }
    };

    /* ═══════════════════════════════════════
       ۴. Navigation Bar شناور (مخفی/نمایش)
       ═══════════════════════════════════════ */

    const NavbarManager = {
        init() {
            this.navbar = Novel.$('.novel-bottom-nav');
            if (!this.navbar) return;

            this.lastScrollY = 0;
            this.ticking = false;

            // مخفی/نمایش با اسکرول
            window.addEventListener('scroll', () => {
                if (!this.ticking) {
                    requestAnimationFrame(() => {
                        this.onScroll();
                        this.ticking = false;
                    });
                    this.ticking = true;
                }
            }, { passive: true });

            // علامت‌گذاری آیتم فعال
            this.setActiveItem();

            // بج اعلان
            this.pollNotifications();
        },

        onScroll() {
            const scrollY = window.scrollY;
            const direction = scrollY > this.lastScrollY ? 'down' : 'up';

            if (direction === 'down' && scrollY > 200) {
                this.navbar.classList.add('nav-hidden');
            } else {
                this.navbar.classList.remove('nav-hidden');
            }

            this.lastScrollY = scrollY;
        },

        setActiveItem() {
            const path = window.location.pathname;
            Novel.$$('.novel-nav-item', this.navbar).forEach(item => {
                item.classList.remove('active');
                const href = item.getAttribute('href');
                if (href === '/' && path === '/') {
                    item.classList.add('active');
                } else if (href !== '/' && path.startsWith(href)) {
                    item.classList.add('active');
                }
            });
        },

        pollNotifications() {
            if (!Novel.isLoggedIn()) return;

            const interval = (window.NovelAjax && window.NovelAjax.notif_interval) || 60000;

            const updateBadge = async () => {
                try {
                    const res = await Novel.ajax('novel_get_unread_count');
                    if (res.success) {
                        const count = res.data.count || 0;
                        Novel.$$('.nav-badge, .novel-notif-badge').forEach(badge => {
                            badge.textContent = count > 0 ? Novel.faNum(count > 99 ? '99+' : count) : '';
                            badge.setAttribute('data-count', count);
                        });
                    }
                } catch (e) {
                    // سکوت - خطای شبکه
                }
            };

            // اولین بار
            updateBadge();
            // هر interval ثانیه
            setInterval(updateBadge, interval);
        }
    };

    /* ═══════════════════════════════════════
       ۵. Toast Notification System
       ═══════════════════════════════════════ */

    const ToastManager = {
        container: null,

        init() {
            // ساخت container اگر وجود ندارد
            if (!Novel.$('.novel-toast-container')) {
                this.container = document.createElement('div');
                this.container.className = 'novel-toast-container';
                this.container.setAttribute('aria-live', 'polite');
                this.container.setAttribute('aria-atomic', 'false');
                document.body.appendChild(this.container);
            } else {
                this.container = Novel.$('.novel-toast-container');
            }
        },

        /**
         * نمایش toast
         * @param {string} message پیام
         * @param {string} type نوع: success, error, warning, info
         * @param {number} duration مدت نمایش (ms)
         */
        show(message, type = 'info', duration = 4000) {
            if (!this.container) this.init();

            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };

            const toast = document.createElement('div');
            toast.className = `novel-toast toast-${type}`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <span class="novel-toast-icon">${icons[type] || icons.info}</span>
                <span class="novel-toast-message">${this.escapeHtml(message)}</span>
                <button class="novel-toast-close" aria-label="بستن">×</button>
            `;

            // دکمه بستن
            toast.querySelector('.novel-toast-close').addEventListener('click', () => {
                this.dismiss(toast);
            });

            this.container.appendChild(toast);

            // حذف خودکار
            if (duration > 0) {
                setTimeout(() => this.dismiss(toast), duration);
            }

            // حداکثر ۵ toast
            const toasts = this.container.querySelectorAll('.novel-toast');
            if (toasts.length > 5) {
                this.dismiss(toasts[0]);
            }

            return toast;
        },

        dismiss(toast) {
            if (!toast || toast.classList.contains('toast-out')) return;
            toast.classList.add('toast-out');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 250);
        },

        escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
    };

    // Shortcut عمومی
    Novel.toast = (message, type, duration) => ToastManager.show(message, type, duration);

    /* ═══════════════════════════════════════
       ۶. Profile Dropdown
       ═══════════════════════════════════════ */

    const ProfileDropdown = {
        init() {
            this.trigger = Novel.$('.novel-header-avatar');
            this.dropdown = Novel.$('.novel-profile-dropdown');
            if (!this.trigger || !this.dropdown) return;

            this.trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggle();
            });

            // بستن با کلیک بیرون
            document.addEventListener('click', (e) => {
                if (!this.dropdown.contains(e.target)) {
                    this.close();
                }
            });

            // بستن با Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') this.close();
            });
        },

        toggle() {
            this.dropdown.classList.toggle('show');
            const isOpen = this.dropdown.classList.contains('show');
            this.trigger.setAttribute('aria-expanded', isOpen);
        },

        close() {
            this.dropdown.classList.remove('show');
            if (this.trigger) {
                this.trigger.setAttribute('aria-expanded', 'false');
            }
        }
    };

    /* ═══════════════════════════════════════
       ۷. Mobile Menu
       ═══════════════════════════════════════ */

    const MobileMenu = {
        init() {
            this.menu = Novel.$('.novel-mobile-menu');
            this.overlay = Novel.$('.novel-mobile-overlay');
            this.openBtn = Novel.$('.novel-mobile-menu-btn');
            this.closeBtn = Novel.$('.novel-mobile-menu-close');

            if (!this.menu || !this.openBtn) return;

            this.openBtn.addEventListener('click', () => this.open());

            if (this.closeBtn) {
                this.closeBtn.addEventListener('click', () => this.close());
            }
            if (this.overlay) {
                this.overlay.addEventListener('click', () => this.close());
            }

            // بستن با Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') this.close();
            });

            // Swipe to close (راست به چپ → بسته نمی‌شود، چپ به راست هم نه، ولی swipe right)
            this.setupSwipe();
        },

        open() {
            this.menu.classList.add('show');
            if (this.overlay) this.overlay.classList.add('show');
            document.body.style.overflow = 'hidden';
            this.menu.setAttribute('aria-hidden', 'false');

            // Focus trap
            const firstFocusable = this.menu.querySelector('a, button');
            if (firstFocusable) firstFocusable.focus();
        },

        close() {
            this.menu.classList.remove('show');
            if (this.overlay) this.overlay.classList.remove('show');
            document.body.style.overflow = '';
            this.menu.setAttribute('aria-hidden', 'true');
        },

        setupSwipe() {
            let startX = 0;
            let currentX = 0;

            this.menu.addEventListener('touchstart', (e) => {
                startX = e.touches[0].clientX;
            }, { passive: true });

            this.menu.addEventListener('touchmove', (e) => {
                currentX = e.touches[0].clientX;
                const diff = currentX - startX;
                // فقط swipe به راست (RTL → بستن)
                if (diff > 0) {
                    this.menu.style.transform = `translateX(${diff}px)`;
                }
            }, { passive: true });

            this.menu.addEventListener('touchend', () => {
                const diff = currentX - startX;
                if (diff > 100) {
                    this.close();
                }
                this.menu.style.transform = '';
                startX = 0;
                currentX = 0;
            }, { passive: true });
        }
    };

    /* ═══════════════════════════════════════
       ۸. Bottom Sheet
       ═══════════════════════════════════════ */

    const BottomSheet = {
        open(contentHtml, title = '') {
            // حذف قبلی
            this.close();

            const overlay = document.createElement('div');
            overlay.className = 'novel-bottom-sheet-overlay show';

            const sheet = document.createElement('div');
            sheet.className = 'novel-bottom-sheet';
            sheet.setAttribute('role', 'dialog');
            sheet.setAttribute('aria-modal', 'true');
            sheet.innerHTML = `
                <div class="novel-bottom-sheet-handle"></div>
                ${title ? `<div class="novel-bottom-sheet-header">
                    <h3>${title}</h3>
                    <button class="novel-bottom-sheet-close novel-header-btn" aria-label="بستن">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>` : ''}
                <div class="novel-bottom-sheet-body">${contentHtml}</div>
            `;

            document.body.appendChild(overlay);
            document.body.appendChild(sheet);
            document.body.style.overflow = 'hidden';

            // انیمیشن
            requestAnimationFrame(() => {
                sheet.classList.add('show');
            });

            // بستن
            overlay.addEventListener('click', () => this.close());
            const closeBtn = sheet.querySelector('.novel-bottom-sheet-close');
            if (closeBtn) closeBtn.addEventListener('click', () => this.close());

            // Swipe down to close
            let startY = 0;
            sheet.addEventListener('touchstart', (e) => {
                startY = e.touches[0].clientY;
            }, { passive: true });
            sheet.addEventListener('touchend', (e) => {
                const diff = e.changedTouches[0].clientY - startY;
                if (diff > 80) this.close();
            }, { passive: true });

            return sheet;
        },

        close() {
            const overlay = Novel.$('.novel-bottom-sheet-overlay');
            const sheet = Novel.$('.novel-bottom-sheet');
            if (overlay) {
                overlay.classList.remove('show');
                setTimeout(() => overlay.remove(), 300);
            }
            if (sheet) {
                sheet.classList.remove('show');
                setTimeout(() => sheet.remove(), 350);
            }
            document.body.style.overflow = '';
        }
    };

    /* ═══════════════════════════════════════
       ۹. جستجو هدر
       ═══════════════════════════════════════ */

    const HeaderSearch = {
        init() {
            this.wrapper = Novel.$('.novel-header-search');
            this.input = Novel.$('.novel-search-input');
            this.btn = Novel.$('.novel-search-btn');

            if (!this.wrapper || !this.btn) return;

            this.btn.addEventListener('click', (e) => {
                e.preventDefault();
                if (this.wrapper.classList.contains('active')) {
                    // submit
                    if (this.input.value.trim()) {
                        window.location.href = `${window.NovelAjax?.home_url || '/'}?s=${encodeURIComponent(this.input.value.trim())}&post_type=novel`;
                    }
                } else {
                    this.wrapper.classList.add('active');
                    this.input.focus();
                }
            });

            // بستن با Escape
            this.input.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.wrapper.classList.remove('active');
                    this.input.value = '';
                }
                if (e.key === 'Enter' && this.input.value.trim()) {
                    window.location.href = `${window.NovelAjax?.home_url || '/'}?s=${encodeURIComponent(this.input.value.trim())}&post_type=novel`;
                }
            });

            // بستن با کلیک بیرون
            document.addEventListener('click', (e) => {
                if (!this.wrapper.contains(e.target)) {
                    this.wrapper.classList.remove('active');
                }
            });
        }
    };

    /* ═══════════════════════════════════════
       ۱۰. بنر اطلاعیه
       ═══════════════════════════════════════ */

    const AnnouncementBar = {
        init() {
            const bar = Novel.$('.novel-announcement-bar');
            if (!bar) return;

            const closeBtn = Novel.$('.novel-announcement-close', bar);
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    bar.style.transition = 'all 300ms ease';
                    bar.style.maxHeight = bar.scrollHeight + 'px';
                    requestAnimationFrame(() => {
                        bar.style.maxHeight = '0';
                        bar.style.padding = '0';
                        bar.style.overflow = 'hidden';
                    });
                    // ذخیره — امروز نمایش نده
                    localStorage.setItem('novel-announcement-dismissed', new Date().toDateString());
                });
            }

            // بررسی dismiss
            const dismissed = localStorage.getItem('novel-announcement-dismissed');
            if (dismissed === new Date().toDateString()) {
                bar.style.display = 'none';
            }
        }
    };

    /* ═══════════════════════════════════════
       ۱۱. لاگین مورد نیاز (کلیک‌ها)
       ═══════════════════════════════════════ */

    const AuthGuard = {
        init() {
            Novel.$$('[data-requires-login]').forEach(el => {
                el.addEventListener('click', (e) => {
                    if (!Novel.isLoggedIn()) {
                        e.preventDefault();
                        e.stopPropagation();
                        Novel.requireLogin();
                    }
                });
            });
        }
    };

    /* ═══════════════════════════════════════
       ۱۲. Lazy Load تصاویر (فال‌بک)
       ═══════════════════════════════════════ */

    const ImageHandler = {
        init() {
            // Error handling برای تصاویر
            Novel.$$('img[data-fallback]').forEach(img => {
                img.addEventListener('error', function () {
                    if (!this.dataset.retried) {
                        this.dataset.retried = '1';
                        this.src = this.dataset.fallback;
                    }
                });
            });
        }
    };

    /* ═══════════════════════════════════════
       ۱۳. Pull to Refresh
       ═══════════════════════════════════════ */

    const PullToRefresh = {
        init() {
            // فقط موبایل
            if (window.innerWidth > 768) return;

            let startY = 0;
            let pulling = false;
            const indicator = Novel.$('.novel-ptr-indicator');
            if (!indicator) return;

            document.addEventListener('touchstart', (e) => {
                if (window.scrollY === 0) {
                    startY = e.touches[0].clientY;
                    pulling = true;
                }
            }, { passive: true });

            document.addEventListener('touchmove', (e) => {
                if (!pulling) return;
                const diff = e.touches[0].clientY - startY;
                if (diff > 60 && window.scrollY === 0) {
                    indicator.classList.add('pulling');
                }
            }, { passive: true });

            document.addEventListener('touchend', () => {
                if (indicator.classList.contains('pulling')) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                }
                pulling = false;
            }, { passive: true });
        }
    };

    /* ═══════════════════════════════════════
       ۱۴. مقداردهی اولیه (DOMContentLoaded)
       ═══════════════════════════════════════ */

    document.addEventListener('DOMContentLoaded', () => {
        // ── Core ──
        ThemeManager.init();
        HeaderManager.init();
        NavbarManager.init();
        ToastManager.init();

        // ── UI ──
        ProfileDropdown.init();
        MobileMenu.init();
        HeaderSearch.init();
        AnnouncementBar.init();
        AuthGuard.init();
        ImageHandler.init();
        PullToRefresh.init();

        Novel.log('Theme initialized ✅');
    });

    /* ═══════════════════════════════════════
       ۱۵. اکسپورت عمومی
       ═══════════════════════════════════════ */

    window.Novel = Novel;
    window.NovelToast = ToastManager;
    window.NovelBottomSheet = BottomSheet;
    window.NovelTheme = ThemeManager;

})();









/*فاز 8*/
/* ═══════════════════════════════════════
   User Dropdown Menu
   (اضافه به main.js)
   ═══════════════════════════════════════ */

(function($) {
    'use strict';

    const UserMenu = {
        init() {
            this.toggle = document.getElementById('userMenuToggle');
            this.dropdown = document.getElementById('userDropdown');
            
            if (!this.toggle || !this.dropdown) return;
            
            this.bindEvents();
        },
        
        bindEvents() {
            const self = this;
            
            // Toggle dropdown
            $(this.toggle).on('click', function(e) {
                e.stopPropagation();
                self.toggleDropdown();
            });
            
            // Close on outside click
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.header-user-menu').length) {
                    self.closeDropdown();
                }
            });
            
            // Close on Escape
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    self.closeDropdown();
                }
            });
        },
        
        toggleDropdown() {
            const isVisible = this.dropdown.style.display !== 'none';
            this.dropdown.style.display = isVisible ? 'none' : 'block';
        },
        
        closeDropdown() {
            if (this.dropdown) {
                this.dropdown.style.display = 'none';
            }
        }
    };
    
    /* ═══════════════════════════════════════
       Mobile Navigation
       ═══════════════════════════════════════ */
    
    const MobileNav = {
        init() {
            this.toggle = document.getElementById('mobileMenuToggle');
            this.overlay = document.getElementById('mobileNavOverlay');
            this.close = document.getElementById('mobileNavClose');
            
            if (!this.toggle || !this.overlay) return;
            
            this.bindEvents();
        },
        
        bindEvents() {
            const self = this;
            
            $(this.toggle).on('click', function() {
                self.open();
            });
            
            if (this.close) {
                $(this.close).on('click', function() {
                    self.closeNav();
                });
            }
            
            // Close on overlay click
            $(this.overlay).on('click', function(e) {
                if (e.target === self.overlay) {
                    self.closeNav();
                }
            });
            
            // Escape
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    self.closeNav();
                }
            });
        },
        
        open() {
            $(this.overlay).fadeIn(250);
            document.body.style.overflow = 'hidden';
            $(this.overlay).find('.mobile-nav-content').addClass('is-open');
        },
        
        closeNav() {
            $(this.overlay).find('.mobile-nav-content').removeClass('is-open');
            $(this.overlay).fadeOut(250);
            document.body.style.overflow = '';
        }
    };
    
    $(document).ready(function() {
        UserMenu.init();
        MobileNav.init();
    });
    
})(jQuery);