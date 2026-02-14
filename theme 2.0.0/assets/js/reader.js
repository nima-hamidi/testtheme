/**
 * اسکریپت ریدر - Flavor Novel Reader ⭐
 * شامل: تنظیمات خواندن، نوار پیشرفت، ذخیره موقعیت، میانبرها، تمام‌صفحه
 *
 * @package Flavor_Novel
 * @version 1.0.0
 */

(function () {
    'use strict';

    /* ========================================
     *  تنظیمات پیش‌فرض
     * ======================================== */
    const DEFAULTS = {
        fontSize: 18,
        lineHeight: 2,
        contentWidth: 800,
        fontFamily: 'Vazirmatn',
        readerTheme: 'light',
        textAlign: 'justify',
    };

    /* ========================================
     *  مدیریت تنظیمات خواندن
     * ======================================== */
    const ReaderSettings = {
        settings: {},

        init() {
            // خواندن تنظیمات ذخیره‌شده
            this.settings = FN.storage.get('reader_settings', { ...DEFAULTS });
            this.apply();
            this.bindEvents();
            this.updateUI();
        },

        /**
         * اعمال تنظیمات به محتوا
         */
        apply() {
            const content = document.getElementById('fnReaderContent');
            const reader = document.getElementById('fnReader');
            if (!content || !reader) return;

            // فونت
            content.style.fontFamily = `'${this.settings.fontFamily}', serif`;

            // سایز
            content.style.fontSize = this.settings.fontSize + 'px';

            // فاصله خط
            content.style.lineHeight = this.settings.lineHeight;

            // عرض
            reader.style.maxWidth = this.settings.contentWidth + 'px';

            // تراز متن — اعمال به همه پاراگراف‌ها
            content.style.textAlign = this.settings.textAlign;
            const paragraphs = content.querySelectorAll('p');
            paragraphs.forEach(p => {
                p.style.textAlign = this.settings.textAlign;
            });

            // تم ریدر
            document.documentElement.setAttribute('data-theme', this.settings.readerTheme);
        },

        /**
         * ذخیره تنظیمات
         */
        save() {
            FN.storage.set('reader_settings', this.settings);
            this.apply();
        },

        /**
         * بروزرسانی UI کنترل‌ها
         */
        updateUI() {
            // سایز فونت
            const fontSizeRange = document.getElementById('fnFontSizeRange');
            const fontSizeValue = document.getElementById('fnFontSizeValue');
            if (fontSizeRange) fontSizeRange.value = this.settings.fontSize;
            if (fontSizeValue) fontSizeValue.textContent = this.settings.fontSize + 'px';

            // فاصله خط
            const lineHeightRange = document.getElementById('fnLineHeightRange');
            const lineHeightValue = document.getElementById('fnLineHeightValue');
            if (lineHeightRange) lineHeightRange.value = this.settings.lineHeight;
            if (lineHeightValue) lineHeightValue.textContent = this.settings.lineHeight;

            // عرض
            const widthRange = document.getElementById('fnContentWidthRange');
            const widthValue = document.getElementById('fnContentWidthValue');
            if (widthRange) widthRange.value = this.settings.contentWidth;
            if (widthValue) widthValue.textContent = this.settings.contentWidth + 'px';

            // فونت
            const fontSelect = document.getElementById('fnFontSelect');
            if (fontSelect) fontSelect.value = this.settings.fontFamily;

            // تم
            document.querySelectorAll('.fn-theme-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.readerTheme === this.settings.readerTheme);
            });

            // تراز
            document.querySelectorAll('.fn-settings-align-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.align === this.settings.textAlign);
            });
        },

        /**
         * اتصال رویدادها
         */
        bindEvents() {
            const self = this;

            // --- سایز فونت ---
            const fontRange = document.getElementById('fnFontSizeRange');
            if (fontRange) {
                fontRange.addEventListener('input', function () {
                    self.settings.fontSize = parseInt(this.value);
                    self.save();
                    self.updateUI();
                });
            }

            const fontDown = document.getElementById('fnFontSizeDown');
            if (fontDown) {
                fontDown.addEventListener('click', () => {
                    if (self.settings.fontSize > 14) {
                        self.settings.fontSize -= 1;
                        self.save();
                        self.updateUI();
                    }
                });
            }

            const fontUp = document.getElementById('fnFontSizeUp');
            if (fontUp) {
                fontUp.addEventListener('click', () => {
                    if (self.settings.fontSize < 28) {
                        self.settings.fontSize += 1;
                        self.save();
                        self.updateUI();
                    }
                });
            }

            // --- فاصله خط ---
            const lineRange = document.getElementById('fnLineHeightRange');
            if (lineRange) {
                lineRange.addEventListener('input', function () {
                    self.settings.lineHeight = parseFloat(this.value);
                    self.save();
                    self.updateUI();
                });
            }

            const lineDown = document.getElementById('fnLineHeightDown');
            if (lineDown) {
                lineDown.addEventListener('click', () => {
                    if (self.settings.lineHeight > 1.4) {
                        self.settings.lineHeight = Math.round((self.settings.lineHeight - 0.1) * 10) / 10;
                        self.save();
                        self.updateUI();
                    }
                });
            }

            const lineUp = document.getElementById('fnLineHeightUp');
            if (lineUp) {
                lineUp.addEventListener('click', () => {
                    if (self.settings.lineHeight < 3) {
                        self.settings.lineHeight = Math.round((self.settings.lineHeight + 0.1) * 10) / 10;
                        self.save();
                        self.updateUI();
                    }
                });
            }

            // --- عرض محتوا ---
            const widthRange = document.getElementById('fnContentWidthRange');
            if (widthRange) {
                widthRange.addEventListener('input', function () {
                    self.settings.contentWidth = parseInt(this.value);
                    self.save();
                    self.updateUI();
                });
            }

            const widthDown = document.getElementById('fnWidthDown');
            if (widthDown) {
                widthDown.addEventListener('click', () => {
                    if (self.settings.contentWidth > 500) {
                        self.settings.contentWidth -= 50;
                        self.save();
                        self.updateUI();
                    }
                });
            }

            const widthUp = document.getElementById('fnWidthUp');
            if (widthUp) {
                widthUp.addEventListener('click', () => {
                    if (self.settings.contentWidth < 1100) {
                        self.settings.contentWidth += 50;
                        self.save();
                        self.updateUI();
                    }
                });
            }

            // --- فونت ---
            const fontSelect = document.getElementById('fnFontSelect');
            if (fontSelect) {
                fontSelect.addEventListener('change', function () {
                    self.settings.fontFamily = this.value;
                    self.save();
                });
            }

            // --- تم ---
            document.querySelectorAll('.fn-theme-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    self.settings.readerTheme = this.dataset.readerTheme;
                    self.save();
                    self.updateUI();

                    // بروزرسانی آیکون هدر
                    const sunIcon = document.getElementById('fnIconSun');
                    const moonIcon = document.getElementById('fnIconMoon');
                    if (sunIcon && moonIcon) {
                        const isDark = self.settings.readerTheme === 'dark';
                        sunIcon.classList.toggle('fn-hidden', isDark);
                        moonIcon.classList.toggle('fn-hidden', !isDark);
                    }
                });
            });

            // --- تراز --- (جایگزین بخش قبلی)
            document.querySelectorAll('.fn-settings-align-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    // حذف active از همه
                    document.querySelectorAll('.fn-settings-align-btn').forEach(b => b.classList.remove('active'));
                    // اضافه به فعلی
                    this.classList.add('active');
                    
                    self.settings.textAlign = this.dataset.align;
                    self.save();
                    self.updateUI();
                });
            });

            // --- بازنشانی ---
            const resetBtn = document.getElementById('fnResetSettings');
            if (resetBtn) {
                resetBtn.addEventListener('click', () => {
                    self.settings = { ...DEFAULTS };
                    self.save();
                    self.updateUI();
                    FN.toast('تنظیمات بازنشانی شد', 'success');
                });
            }
        },
    };

    /* ========================================
     *  پنل تنظیمات (باز/بسته)
     * ======================================== */
    const SettingsPanel = {
        init() {
            const toggleBtn = document.getElementById('fnSettingsToggle');
            const panel = document.getElementById('fnReaderSettings');
            const closeBtn = document.getElementById('fnSettingsClose');

            if (!toggleBtn || !panel) return;

            toggleBtn.addEventListener('click', () => {
                panel.classList.toggle('open');
            });

            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    panel.classList.remove('open');
                });
            }

            // بستن با کلیک بیرون
            document.addEventListener('click', (e) => {
                if (!e.target.closest('#fnReaderSettings') &&
                    !e.target.closest('#fnSettingsToggle')) {
                    panel.classList.remove('open');
                }
            });

            // بستن با Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    panel.classList.remove('open');
                }
            });
        },
    };

    /* ========================================
     *  نوار پیشرفت خواندن
     * ======================================== */
    const ProgressBar = {
        init() {
            const bar = document.getElementById('fnProgressBar');
            const content = document.getElementById('fnReaderContent');
            if (!bar || !content) return;

            const updateProgress = () => {
                const contentRect = content.getBoundingClientRect();
                const contentTop = contentRect.top + window.pageYOffset;
                const contentHeight = contentRect.height;
                const windowHeight = window.innerHeight;
                const scrolled = window.pageYOffset - contentTop + windowHeight;
                const progress = Math.min(100, Math.max(0, (scrolled / contentHeight) * 100));

                bar.style.width = progress + '%';
            };

            window.addEventListener('scroll', updateProgress, { passive: true });
            updateProgress();
        },
    };

    /* ========================================
     *  ذخیره موقعیت خواندن
     * ======================================== */
    const ReadingPosition = {
        saveTimer: null,

        init() {
            if (!window.FN_CHAPTER) return;

            // بازیابی موقعیت ذخیره‌شده (localStorage)
            this.restorePosition();

            // ذخیره هنگام اسکرول
            window.addEventListener('scroll', FN.debounce(() => {
                this.savePosition();
            }, 1000), { passive: true });

            // ذخیره هنگام خروج
            window.addEventListener('beforeunload', () => {
                this.savePosition();
            });
        },

        savePosition() {
            const content = document.getElementById('fnReaderContent');
            if (!content) return;

            const contentRect = content.getBoundingClientRect();
            const contentTop = contentRect.top + window.pageYOffset;
            const contentHeight = contentRect.height;
            const scrollPosition = Math.max(0, window.pageYOffset - contentTop);
            const percentage = contentHeight > 0 ? (scrollPosition / contentHeight) : 0;

            // ذخیره در localStorage
            const key = `chapter_${FN_CHAPTER.chapterId}`;
            FN.storage.set(key, {
                scroll: percentage,
                timestamp: Date.now(),
            });

            // ذخیره در سرور (اگر لاگین باشد)
            if (flavor_novel.is_user_logged_in && FN_CHAPTER.novelId) {
                clearTimeout(this.saveTimer);
                this.saveTimer = setTimeout(() => {
                    FN.ajax('fn_save_reading_progress', {
                        chapter_id: FN_CHAPTER.chapterId,
                        scroll_position: percentage,
                    });
                }, 3000);
            }
        },

        restorePosition() {
            const key = `chapter_${FN_CHAPTER.chapterId}`;
            const saved = FN.storage.get(key);

            if (saved && saved.scroll > 0.05) {
                // اگر کمتر از ۲۴ ساعت پیش ذخیره شده
                if (Date.now() - saved.timestamp < 86400000) {
                    setTimeout(() => {
                        const content = document.getElementById('fnReaderContent');
                        if (!content) return;

                        const contentTop = content.getBoundingClientRect().top + window.pageYOffset;
                        const targetScroll = contentTop + (content.offsetHeight * saved.scroll);

                        // نمایش پیام تایید
                        this.showRestorePrompt(targetScroll);
                    }, 500);
                }
            }
        },

        showRestorePrompt(targetScroll) {
            const prompt = document.createElement('div');
            prompt.className = 'fn-restore-prompt';
            prompt.innerHTML = `
                <span>📖 از آخرین نقطه ادامه دهید؟</span>
                <div class="fn-restore-prompt__actions">
                    <button class="fn-restore-yes">بله</button>
                    <button class="fn-restore-no">نه</button>
                </div>
            `;

            document.body.appendChild(prompt);

            // انیمیشن ورود
            requestAnimationFrame(() => prompt.classList.add('visible'));

            prompt.querySelector('.fn-restore-yes').addEventListener('click', () => {
                window.scrollTo({ top: targetScroll, behavior: 'smooth' });
                prompt.remove();
            });

            prompt.querySelector('.fn-restore-no').addEventListener('click', () => {
                prompt.remove();
            });

            // حذف خودکار بعد از ۸ ثانیه
            setTimeout(() => {
                if (prompt.parentElement) {
                    prompt.classList.remove('visible');
                    setTimeout(() => prompt.remove(), 300);
                }
            }, 8000);
        },
    };

    /* ========================================
     *  حالت تمام‌صفحه
     * ======================================== */
    const FullscreenMode = {
        isFullscreen: false,

        init() {
            const toggleBtn = document.getElementById('fnFullscreenToggle');
            if (!toggleBtn) return;

            toggleBtn.addEventListener('click', () => {
                this.toggle();
            });
        },

        toggle() {
            this.isFullscreen = !this.isFullscreen;
            document.body.classList.toggle('fn-fullscreen', this.isFullscreen);

            const icon = document.getElementById('fnFullscreenIcon');
            if (icon) {
                if (this.isFullscreen) {
                    icon.innerHTML = '<polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="14" y1="10" x2="21" y2="3"/><line x1="3" y1="21" x2="10" y2="14"/>';
                } else {
                    icon.innerHTML = '<polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/>';
                }
            }
        },
    };

    /* ========================================
     *  کشوی فهرست فصل‌ها
     * ======================================== */
    const ChapterDrawer = {
        loaded: false,

        init() {
            const drawer = document.getElementById('fnChapterDrawer');
            if (!drawer) return;

            // باز کردن از دکمه فهرست
            document.querySelectorAll('.fn-chapter-nav__btn--list').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    // فقط اگر در صفحه ریدر باشد
                    if (document.body.classList.contains('fn-reading-mode')) {
                        e.preventDefault();
                        this.open();
                    }
                });
            });

            // بستن
            const closeBtn = document.getElementById('fnDrawerClose');
            const overlay = document.getElementById('fnDrawerOverlay');

            if (closeBtn) closeBtn.addEventListener('click', () => this.close());
            if (overlay) overlay.addEventListener('click', () => this.close());

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') this.close();
            });
        },

        async open() {
            const drawer = document.getElementById('fnChapterDrawer');
            if (!drawer) return;

            drawer.classList.add('open');
            document.body.style.overflow = 'hidden';

            if (!this.loaded && window.FN_CHAPTER) {
                await this.loadChapters();
            }
        },

        close() {
            const drawer = document.getElementById('fnChapterDrawer');
            if (drawer) {
                drawer.classList.remove('open');
                document.body.style.overflow = '';
            }
        },

        async loadChapters() {
            const body = document.getElementById('fnDrawerBody');
            if (!body || !window.FN_CHAPTER) return;

            const data = await FN.rest('chapters/' + FN_CHAPTER.novelId, {
                per_page: 100,
                order: 'ASC',
            });

            if (data && data.chapters) {
                let html = '';
                data.chapters.forEach(ch => {
                    const isCurrent = parseInt(ch.id) === FN_CHAPTER.chapterId;
                    html += `
                        <a href="${ch.url}" class="fn-drawer-chapter ${isCurrent ? 'fn-drawer-chapter--current' : ''}">
                            <span class="fn-drawer-chapter__number">فصل ${ch.chapter_number}</span>
                            <span class="fn-drawer-chapter__title">${ch.title}</span>
                            <span class="fn-drawer-chapter__date">${ch.date_human}</span>
                            ${isCurrent ? '<span class="fn-drawer-chapter__badge">فعلی</span>' : ''}
                        </a>
                    `;
                });
                body.innerHTML = html;
                this.loaded = true;

                // اسکرول به فصل فعلی
                const currentEl = body.querySelector('.fn-drawer-chapter--current');
                if (currentEl) {
                    currentEl.scrollIntoView({ block: 'center' });
                }
            }
        },
    };

    /* ========================================
     *  میانبرهای صفحه‌کلید
     * ======================================== */
    const KeyboardShortcuts = {
        init() {
            document.addEventListener('keydown', (e) => {
                // اگر در فرم هستیم، کاری نکن
                if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) return;

                switch (e.key) {
                    case 'ArrowRight':
                        // فصل قبلی (RTL)
                        if (FN_CHAPTER.prevChapterUrl) {
                            window.location.href = FN_CHAPTER.prevChapterUrl;
                        }
                        break;

                    case 'ArrowLeft':
                        // فصل بعدی (RTL)
                        if (FN_CHAPTER.nextChapterUrl) {
                            window.location.href = FN_CHAPTER.nextChapterUrl;
                        }
                        break;

                    case 'f':
                    case 'F':
                        // تمام‌صفحه
                        if (!e.ctrlKey && !e.metaKey) {
                            e.preventDefault();
                            FullscreenMode.toggle();
                        }
                        break;

                    case 's':
                    case 'S':
                        // تنظیمات
                        if (!e.ctrlKey && !e.metaKey) {
                            e.preventDefault();
                            const panel = document.getElementById('fnReaderSettings');
                            if (panel) panel.classList.toggle('open');
                        }
                        break;

                    case 'Escape':
                        // خروج از تمام‌صفحه
                        if (FullscreenMode.isFullscreen) {
                            FullscreenMode.toggle();
                        }
                        break;
                }
            });
        },
    };

    /* ========================================
     *  FAB نمایش/مخفی هنگام اسکرول
     * ======================================== */
    const FabVisibility = {
        init() {
            const fab = document.getElementById('fnReaderFab');
            if (!fab) return;

            let lastScroll = 0;
            let hideTimer;

            const showFab = () => {
                fab.classList.remove('fn-fab--hidden');
                clearTimeout(hideTimer);
                hideTimer = setTimeout(() => {
                    fab.classList.add('fn-fab--hidden');
                }, 3000);
            };

            window.addEventListener('scroll', () => {
                showFab();
                lastScroll = window.pageYOffset;
            }, { passive: true });

            // نمایش هنگام حرکت ماوس
            document.addEventListener('mousemove', FN.debounce(showFab, 100));

            // نمایش اولیه
            showFab();
        },
    };

    /* ========================================
     *  مقداردهی اولیه ریدر
     * ======================================== */
    document.addEventListener('DOMContentLoaded', () => {
        ReaderSettings.init();
        SettingsPanel.init();
        ProgressBar.init();
        ReadingPosition.init();
        FullscreenMode.init();
        ChapterDrawer.init();
        KeyboardShortcuts.init();
        FabVisibility.init();

        console.log('📖 Flavor Novel Reader Loaded');
    });

})();