/**
 * Novel Reader Mode
 * 
 * پنل تنظیمات مطالعه + Progress Bar + Keyboard Shortcuts + TTS + Fullscreen
 *
 * @package suspended-flavor
 * @since 3.0.0
 */

(function($) {
    'use strict';

    const Reader = {
        settings: {},
        defaults: {
            fontSize: 16,
            fontFamily: 'IRANSans',
            theme: 'light',
            lineHeight: 1.8,
            width: 800,
            paragraphSpacing: 16,
        },
        isFullscreen: false,
        ttsActive: false,
        ttsUtterance: null,
        panelOpen: false,

        /**
         * Init
         */
        init() {
            if (!document.querySelector('.chapter-content')) return;

            this.loadSettings();
            this.applySettings();
            this.createProgressBar();
            this.createSettingsPanel();
            this.createStickyNav();
            this.bindEvents();
            this.showReadingTime();
        },

        /* ═══════════════════════════════════════
           Settings Management
           ═══════════════════════════════════════ */

        loadSettings() {
            try {
                const saved = localStorage.getItem('novel_reader_settings');
                this.settings = saved ? { ...this.defaults, ...JSON.parse(saved) } : { ...this.defaults };
            } catch (e) {
                this.settings = { ...this.defaults };
            }
        },

        saveSettings() {
            try {
                localStorage.setItem('novel_reader_settings', JSON.stringify(this.settings));
            } catch (e) { /* silent */ }
        },

        applySettings() {
            const s = this.settings;
            const root = document.documentElement;
            const $content = $('.chapter-content');

            root.style.setProperty('--reader-font-size', s.fontSize + 'px');
            root.style.setProperty('--reader-font-family', this.getFontStack(s.fontFamily));
            root.style.setProperty('--reader-line-height', s.lineHeight);
            root.style.setProperty('--reader-width', s.width + 'px');
            root.style.setProperty('--reader-paragraph-spacing', s.paragraphSpacing + 'px');

            // Theme
            this.applyTheme(s.theme);

            // Update panel UI if open
            this.updatePanelUI();
        },

        applyTheme(theme) {
            const themes = {
                light: { bg: '#FFFFFF', text: '#2D3436', name: 'روشن' },
                sepia: { bg: '#F4ECD8', text: '#5B4636', name: 'سپیا' },
                dark:  { bg: '#2D2D2D', text: '#D4D4D4', name: 'تاریک' },
                black: { bg: '#000000', text: '#C0C0C0', name: 'مشکی' },
            };

            const t = themes[theme] || themes.light;
            document.documentElement.style.setProperty('--reader-bg', t.bg);
            document.documentElement.style.setProperty('--reader-text', t.text);

            // Set body class for reader theme
            document.body.classList.remove('reader-theme-light', 'reader-theme-sepia', 'reader-theme-dark', 'reader-theme-black');
            document.body.classList.add('reader-theme-' + theme);

            this.settings.theme = theme;
        },

        getFontStack(font) {
            const fonts = {
                'IRANSans':  "'IRANSansX', 'IRANSans', sans-serif",
                'Vazirmatn': "'Vazirmatn', sans-serif",
                'Sahifeh':   "'B Nazanin', 'Sahifeh', serif",
                'Titr':      "'B Titr', 'IRANSansX', sans-serif",
            };
            return fonts[font] || fonts['IRANSans'];
        },

        resetSettings() {
            this.settings = { ...this.defaults };
            this.saveSettings();
            this.applySettings();
        },

        /* ═══════════════════════════════════════
           Progress Bar
           ═══════════════════════════════════════ */

        createProgressBar() {
            if ($('#readerProgressBar').length) return;

            $('body').prepend(`
                <div class="reader-progress-bar" id="readerProgressBar">
                    <div class="reader-progress-bar__fill" id="readerProgressFill"></div>
                </div>
            `);

            this.updateProgress();
        },

        updateProgress() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const docHeight = document.documentElement.scrollHeight;
            const winHeight = window.innerHeight;
            const maxScroll = docHeight - winHeight;
            const percent = maxScroll > 0 ? (scrollTop / maxScroll) * 100 : 0;

            const $fill = document.getElementById('readerProgressFill');
            if ($fill) {
                $fill.style.width = Math.min(100, percent) + '%';
            }
        },

        /* ═══════════════════════════════════════
           Settings Panel
           ═══════════════════════════════════════ */

        createSettingsPanel() {
            if ($('#readerSettingsPanel').length) return;

            const s = this.settings;

            const panelHtml = `
                <div class="reader-settings-panel" id="readerSettingsPanel" style="display:none">
                    <div class="reader-panel__overlay" data-close-panel></div>
                    <div class="reader-panel__content">
                        <div class="reader-panel__header">
                            <h3>⚙️ تنظیمات مطالعه</h3>
                            <button class="reader-panel__close" data-close-panel aria-label="بستن">✕</button>
                        </div>

                        <!-- Font Size -->
                        <div class="reader-option">
                            <label class="reader-option__label">📏 اندازه فونت</label>
                            <div class="reader-slider-row">
                                <span class="slider-label-sm">ᴬ</span>
                                <input type="range" id="readerFontSize" min="12" max="28" step="1" 
                                       value="${s.fontSize}" class="reader-range">
                                <span class="slider-label-lg">A</span>
                                <span class="reader-value" id="fontSizeValue">${s.fontSize}px</span>
                            </div>
                        </div>

                        <!-- Font Family -->
                        <div class="reader-option">
                            <label class="reader-option__label">🔤 فونت</label>
                            <div class="reader-font-options">
                                <button class="font-option ${s.fontFamily === 'IRANSans' ? 'active' : ''}" data-font="IRANSans">ایران‌سنس</button>
                                <button class="font-option ${s.fontFamily === 'Vazirmatn' ? 'active' : ''}" data-font="Vazirmatn">وزیرمتن</button>
                                <button class="font-option ${s.fontFamily === 'Sahifeh' ? 'active' : ''}" data-font="Sahifeh">صحیفه</button>
                                <button class="font-option ${s.fontFamily === 'Titr' ? 'active' : ''}" data-font="Titr">تیتر</button>
                            </div>
                        </div>

                        <!-- Theme -->
                        <div class="reader-option">
                            <label class="reader-option__label">🎨 تم رنگی</label>
                            <div class="reader-theme-options">
                                <button class="theme-option ${s.theme === 'light' ? 'active' : ''}" data-theme="light" 
                                        style="background:#fff;color:#2D3436;border:2px solid #ddd;">☀️ روشن</button>
                                <button class="theme-option ${s.theme === 'sepia' ? 'active' : ''}" data-theme="sepia" 
                                        style="background:#F4ECD8;color:#5B4636;">📜 سپیا</button>
                                <button class="theme-option ${s.theme === 'dark' ? 'active' : ''}" data-theme="dark" 
                                        style="background:#2D2D2D;color:#D4D4D4;">🌘 تاریک</button>
                                <button class="theme-option ${s.theme === 'black' ? 'active' : ''}" data-theme="black" 
                                        style="background:#000;color:#C0C0C0;">⬛ مشکی</button>
                            </div>
                        </div>

                        <!-- Line Height -->
                        <div class="reader-option">
                            <label class="reader-option__label">↕️ فاصله خطوط</label>
                            <div class="reader-slider-row">
                                <span class="slider-label-sm">≡</span>
                                <input type="range" id="readerLineHeight" min="1.4" max="2.8" step="0.1" 
                                       value="${s.lineHeight}" class="reader-range">
                                <span class="slider-label-lg">≡</span>
                                <span class="reader-value" id="lineHeightValue">${s.lineHeight}</span>
                            </div>
                        </div>

                        <!-- Width -->
                        <div class="reader-option">
                            <label class="reader-option__label">↔️ عرض متن</label>
                            <div class="reader-width-options">
                                <button class="width-option ${s.width === 600 ? 'active' : ''}" data-width="600">باریک</button>
                                <button class="width-option ${s.width === 800 ? 'active' : ''}" data-width="800">متوسط</button>
                                <button class="width-option ${s.width === 1000 ? 'active' : ''}" data-width="1000">عریض</button>
                                <button class="width-option ${s.width === 9999 ? 'active' : ''}" data-width="9999">تمام</button>
                            </div>
                        </div>

                        <!-- Paragraph Spacing -->
                        <div class="reader-option">
                            <label class="reader-option__label">¶ فاصله پاراگراف</label>
                            <div class="reader-slider-row">
                                <span class="slider-label-sm">کم</span>
                                <input type="range" id="readerParagraphSpacing" min="8" max="32" step="2" 
                                       value="${s.paragraphSpacing}" class="reader-range">
                                <span class="slider-label-lg">زیاد</span>
                                <span class="reader-value" id="paragraphValue">${s.paragraphSpacing}px</span>
                            </div>
                        </div>

                        <!-- Reset -->
                        <button class="reader-reset-btn" id="readerResetBtn">↺ بازنشانی به پیش‌فرض</button>
                    </div>
                </div>
            `;

            $('body').append(panelHtml);

            // Settings FAB button
            if (!$('#readerSettingsFab').length) {
                $('body').append(`
                    <button class="reader-fab" id="readerSettingsFab" title="تنظیمات مطالعه (S)">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06A1.65 1.65 0 0019.32 9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"></path>
                        </svg>
                    </button>
                `);
            }

            // Fullscreen button
            if (!$('#readerFullscreenBtn').length) {
                $('.chapter-nav-top, .chapter-header').first().append(`
                    <button class="reader-fullscreen-btn" id="readerFullscreenBtn" title="تمام‌صفحه (F)">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15 3 21 3 21 9"></polyline>
                            <polyline points="9 21 3 21 3 15"></polyline>
                            <line x1="21" y1="3" x2="14" y2="10"></line>
                            <line x1="3" y1="21" x2="10" y2="14"></line>
                        </svg>
                    </button>
                `);
            }
        },

        updatePanelUI() {
            const s = this.settings;
            $('#readerFontSize').val(s.fontSize);
            $('#fontSizeValue').text(s.fontSize + 'px');
            $('#readerLineHeight').val(s.lineHeight);
            $('#lineHeightValue').text(s.lineHeight);
            $('#readerParagraphSpacing').val(s.paragraphSpacing);
            $('#paragraphValue').text(s.paragraphSpacing + 'px');

            $('.font-option').removeClass('active');
            $(`.font-option[data-font="${s.fontFamily}"]`).addClass('active');
            $('.theme-option').removeClass('active');
            $(`.theme-option[data-theme="${s.theme}"]`).addClass('active');
            $('.width-option').removeClass('active');
            $(`.width-option[data-width="${s.width}"]`).addClass('active');
        },

        togglePanel() {
            const $panel = $('#readerSettingsPanel');
            if (this.panelOpen) {
                $panel.fadeOut(200);
                this.panelOpen = false;
            } else {
                $panel.fadeIn(200);
                this.panelOpen = true;
            }
        },

        /* ═══════════════════════════════════════
           Sticky Bottom Nav (Mobile)
           ═══════════════════════════════════════ */

        createStickyNav() {
            if ($('#readerStickyNav').length || window.innerWidth > 768) return;

            const prevUrl = $('[data-prev-chapter]').attr('href') || '';
            const nextUrl = $('[data-next-chapter]').attr('href') || '';
            const listUrl = $('[data-chapter-list]').attr('href') || '';

            $('body').append(`
                <div class="reader-sticky-nav" id="readerStickyNav">
                    ${prevUrl ? `<a href="${prevUrl}" class="sticky-nav-btn" title="قبلی">←</a>` : '<span class="sticky-nav-btn disabled">←</span>'}
                    ${listUrl ? `<a href="${listUrl}" class="sticky-nav-btn" title="لیست">📋</a>` : ''}
                    <button class="sticky-nav-btn" id="stickySettingsBtn" title="تنظیمات">⚙️</button>
                    ${nextUrl ? `<a href="${nextUrl}" class="sticky-nav-btn" title="بعدی">→</a>` : '<span class="sticky-nav-btn disabled">→</span>'}
                </div>
            `);
        },

        /* ═══════════════════════════════════════
           Reading Time
           ═══════════════════════════════════════ */

        showReadingTime() {
            const $content = $('.chapter-content');
            if (!$content.length) return;

            const text = $content.text();
            const words = text.trim().split(/\s+/).length;
            const minutes = Math.ceil(words / 200);

            const $info = $(`
                <div class="reader-reading-time">
                    ⏱ ${minutes} دقیقه مطالعه | ${words.toLocaleString('fa-IR')} کلمه
                </div>
            `);

            $content.before($info);
        },

        /* ═══════════════════════════════════════
           Fullscreen
           ═══════════════════════════════════════ */

        toggleFullscreen() {
            if (this.isFullscreen) {
                this.exitFullscreen();
            } else {
                this.enterFullscreen();
            }
        },

        enterFullscreen() {
            document.body.classList.add('reader-fullscreen');
            this.isFullscreen = true;

            // Add exit button
            if (!$('#readerExitFullscreen').length) {
                $('body').append(`
                    <button class="reader-exit-fullscreen" id="readerExitFullscreen" title="خروج (Esc)">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                `);
            }
        },

        exitFullscreen() {
            document.body.classList.remove('reader-fullscreen');
            this.isFullscreen = false;
            $('#readerExitFullscreen').remove();
        },

        /* ═══════════════════════════════════════
           Text-to-Speech
           ═══════════════════════════════════════ */

        initTTS() {
            if (!('speechSynthesis' in window)) return;

            if (!$('#readerTTSBtn').length) {
                $('.chapter-nav-top, .chapter-header').first().append(`
                    <button class="reader-tts-btn" id="readerTTSBtn" title="خواندن با صدا">
                        🔊
                    </button>
                `);
            }
        },

        toggleTTS() {
            if (!('speechSynthesis' in window)) {
                if (typeof NovelToast !== 'undefined') {
                    NovelToast.show('مرورگر شما از خواندن با صدا پشتیبانی نمی‌کند', 'warning');
                }
                return;
            }

            if (this.ttsActive) {
                speechSynthesis.cancel();
                this.ttsActive = false;
                $('#readerTTSBtn').text('🔊').removeClass('tts-active');
                $('.chapter-content p').removeClass('tts-highlight');
                return;
            }

            const paragraphs = document.querySelectorAll('.chapter-content p');
            if (!paragraphs.length) return;

            this.ttsActive = true;
            $('#readerTTSBtn').text('⏹').addClass('tts-active');

            this.speakParagraphs(paragraphs, 0);
        },

        speakParagraphs(paragraphs, index) {
            if (index >= paragraphs.length || !this.ttsActive) {
                this.ttsActive = false;
                $('#readerTTSBtn').text('🔊').removeClass('tts-active');
                $('.chapter-content p').removeClass('tts-highlight');
                return;
            }

            const p = paragraphs[index];
            const text = p.textContent.trim();
            if (!text) {
                this.speakParagraphs(paragraphs, index + 1);
                return;
            }

            // Highlight current paragraph
            $('.chapter-content p').removeClass('tts-highlight');
            $(p).addClass('tts-highlight');
            p.scrollIntoView({ behavior: 'smooth', block: 'center' });

            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'fa-IR';
            utterance.rate = parseFloat(localStorage.getItem('novel_tts_rate') || '1');

            utterance.onend = () => {
                this.speakParagraphs(paragraphs, index + 1);
            };

            utterance.onerror = () => {
                this.speakParagraphs(paragraphs, index + 1);
            };

            speechSynthesis.speak(utterance);
            this.ttsUtterance = utterance;
        },

        /* ═══════════════════════════════════════
           Paragraph Bookmark
           ═══════════════════════════════════════ */

        initParagraphActions() {
            let longPressTimer = null;

            $(document).on('mousedown touchstart', '.chapter-content p', function(e) {
                const $p = $(this);
                longPressTimer = setTimeout(() => {
                    Reader.showParagraphMenu($p, e);
                }, 600);
            });

            $(document).on('mouseup touchend mouseleave', '.chapter-content p', function() {
                clearTimeout(longPressTimer);
            });

            // Close paragraph menu on outside click
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.paragraph-action-menu').length) {
                    $('.paragraph-action-menu').remove();
                }
            });
        },

        showParagraphMenu($p, event) {
            $('.paragraph-action-menu').remove();

            const pIndex = $p.index();
            const text = $p.text().substring(0, 80);

            const menu = $(`
                <div class="paragraph-action-menu" style="top:${event.pageY - 50}px; left:${Math.min(event.pageX, window.innerWidth - 200)}px;">
                    <button class="para-action" data-action="bookmark" data-index="${pIndex}">📌 نشانک</button>
                    <button class="para-action" data-action="copy" data-text="${$p.text().replace(/"/g, '&quot;')}">📋 کپی</button>
                </div>
            `);

            $('body').append(menu);

            menu.on('click', '.para-action', function() {
                const action = $(this).data('action');
                if (action === 'bookmark') {
                    Reader.bookmarkParagraph(pIndex, text);
                } else if (action === 'copy') {
                    Reader.copyParagraph($(this).data('text'));
                }
                menu.remove();
            });
        },

        bookmarkParagraph(index, text) {
            const chapterId = $('body').data('chapter-id') || $('[data-chapter-id]').data('chapter-id');
            if (!chapterId) return;

            let bookmarks = JSON.parse(localStorage.getItem('novel_paragraph_bookmarks') || '[]');
            
            // Check duplicate
            const exists = bookmarks.find(b => b.chapterId === chapterId && b.index === index);
            if (exists) {
                if (typeof NovelToast !== 'undefined') NovelToast.show('این پاراگراف قبلاً نشانک شده', 'info');
                return;
            }

            bookmarks.unshift({
                chapterId: chapterId,
                chapterTitle: document.title,
                index: index,
                text: text,
                url: window.location.href,
                date: new Date().toISOString(),
            });

            // Keep max 50
            bookmarks = bookmarks.slice(0, 50);
            localStorage.setItem('novel_paragraph_bookmarks', JSON.stringify(bookmarks));

            // Visual indicator
            $(`.chapter-content p:eq(${index})`).addClass('para-bookmarked');

            if (typeof NovelToast !== 'undefined') NovelToast.show('📌 نشانک ذخیره شد', 'success');
        },

        copyParagraph(text) {
            const siteUrl = window.location.origin;
            const ref = `\n— از رمان ${document.title} | ${siteUrl}`;
            const fullText = text + ref;

            if (navigator.clipboard) {
                navigator.clipboard.writeText(fullText).then(() => {
                    if (typeof NovelToast !== 'undefined') NovelToast.show('📋 کپی شد', 'success');
                });
            }
        },

        /* ═══════════════════════════════════════
           Events
           ═══════════════════════════════════════ */

        bindEvents() {
            const self = this;

            // Scroll → progress bar
            let scrollTick = false;
            $(window).on('scroll', function() {
                if (!scrollTick) {
                    requestAnimationFrame(() => {
                        self.updateProgress();
                        scrollTick = false;
                    });
                    scrollTick = true;
                }
            });

            // FAB → open settings
            $(document).on('click', '#readerSettingsFab', () => self.togglePanel());
            $(document).on('click', '#stickySettingsBtn', () => self.togglePanel());

            // Close panel
            $(document).on('click', '[data-close-panel]', () => {
                $('#readerSettingsPanel').fadeOut(200);
                self.panelOpen = false;
            });

            // Font size slider
            $(document).on('input', '#readerFontSize', function() {
                self.settings.fontSize = parseInt(this.value, 10);
                $('#fontSizeValue').text(self.settings.fontSize + 'px');
                self.applySettings();
                self.saveSettings();
            });

            // Line height slider
            $(document).on('input', '#readerLineHeight', function() {
                self.settings.lineHeight = parseFloat(this.value);
                $('#lineHeightValue').text(self.settings.lineHeight);
                self.applySettings();
                self.saveSettings();
            });

            // Paragraph spacing slider
            $(document).on('input', '#readerParagraphSpacing', function() {
                self.settings.paragraphSpacing = parseInt(this.value, 10);
                $('#paragraphValue').text(self.settings.paragraphSpacing + 'px');
                self.applySettings();
                self.saveSettings();
            });

            // Font family
            $(document).on('click', '.font-option', function() {
                self.settings.fontFamily = $(this).data('font');
                self.applySettings();
                self.saveSettings();
            });

            // Theme
            $(document).on('click', '.theme-option', function() {
                self.settings.theme = $(this).data('theme');
                self.applySettings();
                self.saveSettings();
            });

            // Width
            $(document).on('click', '.width-option', function() {
                self.settings.width = parseInt($(this).data('width'), 10);
                self.applySettings();
                self.saveSettings();
            });

            // Reset
            $(document).on('click', '#readerResetBtn', () => self.resetSettings());

            // Fullscreen
            $(document).on('click', '#readerFullscreenBtn', () => self.toggleFullscreen());
            $(document).on('click', '#readerExitFullscreen', () => self.exitFullscreen());

            // TTS
            self.initTTS();
            $(document).on('click', '#readerTTSBtn', () => self.toggleTTS());

            // Paragraph actions
            self.initParagraphActions();

            // Mark existing bookmarks
            self.markBookmarkedParagraphs();

            // Keyboard shortcuts
            $(document).on('keydown', function(e) {
                // Don't trigger in inputs
                if ($(e.target).is('input, textarea, select, [contenteditable]')) return;

                switch (e.key) {
                    case 'ArrowLeft': // RTL: next chapter
                        const nextUrl = $('[data-next-chapter]').attr('href');
                        if (nextUrl) window.location.href = nextUrl;
                        break;
                    case 'ArrowRight': // RTL: prev chapter
                        const prevUrl = $('[data-prev-chapter]').attr('href');
                        if (prevUrl) window.location.href = prevUrl;
                        break;
                    case 'Escape':
                        if (self.isFullscreen) self.exitFullscreen();
                        if (self.panelOpen) {
                            $('#readerSettingsPanel').fadeOut(200);
                            self.panelOpen = false;
                        }
                        break;
                    case 'f':
                    case 'F':
                        e.preventDefault();
                        self.toggleFullscreen();
                        break;
                    case 's':
                    case 'S':
                        if (!e.ctrlKey && !e.metaKey) {
                            e.preventDefault();
                            self.togglePanel();
                        }
                        break;
                }
            });

            // Sticky nav hide/show on scroll
            let lastScrollY = 0;
            $(window).on('scroll', function() {
                const $nav = $('#readerStickyNav');
                if (!$nav.length) return;
                const currentY = window.pageYOffset;
                if (currentY > lastScrollY && currentY > 200) {
                    $nav.addClass('hidden');
                } else {
                    $nav.removeClass('hidden');
                }
                lastScrollY = currentY;
            });
        },

        markBookmarkedParagraphs() {
            const chapterId = $('body').data('chapter-id') || $('[data-chapter-id]').data('chapter-id');
            if (!chapterId) return;

            try {
                const bookmarks = JSON.parse(localStorage.getItem('novel_paragraph_bookmarks') || '[]');
                bookmarks.forEach(b => {
                    if (b.chapterId == chapterId) {
                        $(`.chapter-content p:eq(${b.index})`).addClass('para-bookmarked');
                    }
                });
            } catch (e) { /* silent */ }
        }
    };

    $(document).ready(() => Reader.init());
    window.NovelReader = Reader;

})(jQuery);