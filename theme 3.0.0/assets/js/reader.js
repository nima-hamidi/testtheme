/**
 * Reader JS - Chapter Reading Experience
 * 
 * Handles:
 * - Progress bar
 * - Reader settings (font, theme, size, etc.)
 * - Fullscreen mode
 * - Keyboard navigation
 * - Vote like/dislike
 * - Report modal
 * - Recap toggle
 * - Scroll position save
 * - VIP coin purchase
 * 
 * @package suspended-starter
 * @since 3.0.0
 */

(function() {
    'use strict';

    // ═══════════════════════════════════════════
    // PROGRESS BAR
    // ═══════════════════════════════════════════
    
    const progressBar = document.getElementById('chapterProgressBar');
    
    function updateProgress() {
        if (!progressBar) return;
        const scrollTop = window.scrollY || document.documentElement.scrollTop;
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
        const progress = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
        progressBar.style.width = Math.min(100, progress) + '%';
    }
    
    window.addEventListener('scroll', updateProgress, { passive: true });
    updateProgress();

    // ═══════════════════════════════════════════
    // READER SETTINGS
    // ═══════════════════════════════════════════
    
    const SETTINGS_KEY = 'novel_reader_settings';
    const chapterContent = document.getElementById('chapterContent');
    
    const defaults = {
        fontSize: 16,
        fontFamily: 'IRANSans',
        theme: 'light',
        lineHeight: 18, // stored as 10x (18 = 1.8)
        contentWidth: 800,
        paraSpacing: 16,
    };

    function loadSettings() {
        try {
            const saved = localStorage.getItem(SETTINGS_KEY);
            return saved ? Object.assign({}, defaults, JSON.parse(saved)) : Object.assign({}, defaults);
        } catch (e) {
            return Object.assign({}, defaults);
        }
    }

    function saveSettings(settings) {
        try {
            localStorage.setItem(SETTINGS_KEY, JSON.stringify(settings));
        } catch (e) {}
    }

    function applySettings(settings) {
        if (!chapterContent) return;
        
        // Font size
        chapterContent.style.setProperty('--reader-font-size', settings.fontSize + 'px');
        const fontSizeSlider = document.getElementById('fontSizeSlider');
        const fontSizeValue = document.getElementById('fontSizeValue');
        if (fontSizeSlider) fontSizeSlider.value = settings.fontSize;
        if (fontSizeValue) fontSizeValue.textContent = settings.fontSize + 'px';
        
        // Font family
        chapterContent.style.setProperty('--reader-font', settings.fontFamily + ', sans-serif');
        document.querySelectorAll('.font-btn').forEach(function(btn) {
            btn.classList.toggle('active', btn.dataset.font === settings.fontFamily);
        });
        
        // Line height
        var lh = settings.lineHeight / 10;
        chapterContent.style.setProperty('--reader-line-height', lh);
        var lineHeightSlider = document.getElementById('lineHeightSlider');
        var lineHeightValue = document.getElementById('lineHeightValue');
        if (lineHeightSlider) lineHeightSlider.value = settings.lineHeight;
        if (lineHeightValue) lineHeightValue.textContent = lh.toFixed(1);
        
        // Content width
        if (settings.contentWidth === 'full') {
            chapterContent.style.setProperty('--reader-width', '100%');
        } else {
            chapterContent.style.setProperty('--reader-width', settings.contentWidth + 'px');
        }
        document.querySelectorAll('.width-btn').forEach(function(btn) {
            btn.classList.toggle('active', btn.dataset.width == settings.contentWidth);
        });
        
        // Paragraph spacing
        chapterContent.style.setProperty('--reader-para-spacing', settings.paraSpacing + 'px');
        var paraSlider = document.getElementById('paraSpacingSlider');
        var paraValue = document.getElementById('paraSpacingValue');
        if (paraSlider) paraSlider.value = settings.paraSpacing;
        if (paraValue) paraValue.textContent = settings.paraSpacing + 'px';
        
        // Theme
        var page = document.getElementById('chapterPage') || document.body;
        page.classList.remove('reader-theme-light', 'reader-theme-sepia', 'reader-theme-dark', 'reader-theme-black');
        page.classList.add('reader-theme-' + settings.theme);
        document.querySelectorAll('.theme-btn').forEach(function(btn) {
            btn.classList.toggle('active', btn.dataset.theme === settings.theme);
        });
    }

    var currentSettings = loadSettings();
    applySettings(currentSettings);

    // Settings Panel Toggle
    var btnOpenSettings = document.getElementById('btnReaderSettings');
    var settingsPanel = document.getElementById('readerSettingsPanel');
    var btnCloseSettings = document.getElementById('btnCloseSettings');
    
    if (btnOpenSettings && settingsPanel) {
        btnOpenSettings.addEventListener('click', function() {
            settingsPanel.style.display = 'block';
            requestAnimationFrame(function() {
                settingsPanel.classList.add('open');
            });
        });
        
        if (btnCloseSettings) {
            btnCloseSettings.addEventListener('click', function() {
                settingsPanel.classList.remove('open');
                setTimeout(function() {
                    settingsPanel.style.display = 'none';
                }, 300);
            });
        }
    }

    // Font Size Slider
    var fontSizeSlider = document.getElementById('fontSizeSlider');
    if (fontSizeSlider) {
        fontSizeSlider.addEventListener('input', function() {
            currentSettings.fontSize = parseInt(this.value);
            applySettings(currentSettings);
            saveSettings(currentSettings);
        });
    }
    
    // Font Size Buttons
    document.querySelectorAll('.font-size-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var action = this.dataset.action;
            if (action === 'increase' && currentSettings.fontSize < 28) {
                currentSettings.fontSize += 1;
            } else if (action === 'decrease' && currentSettings.fontSize > 12) {
                currentSettings.fontSize -= 1;
            }
            applySettings(currentSettings);
            saveSettings(currentSettings);
        });
    });
    
    // Font Family
    document.querySelectorAll('.font-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            currentSettings.fontFamily = this.dataset.font;
            applySettings(currentSettings);
            saveSettings(currentSettings);
        });
    });
    
    // Theme
    document.querySelectorAll('.theme-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            currentSettings.theme = this.dataset.theme;
            applySettings(currentSettings);
            saveSettings(currentSettings);
        });
    });
    
    // Line Height
    var lineHeightSlider = document.getElementById('lineHeightSlider');
    if (lineHeightSlider) {
        lineHeightSlider.addEventListener('input', function() {
            currentSettings.lineHeight = parseInt(this.value);
            applySettings(currentSettings);
            saveSettings(currentSettings);
        });
    }
    
    // Content Width
    document.querySelectorAll('.width-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var w = this.dataset.width;
            currentSettings.contentWidth = w === 'full' ? 'full' : parseInt(w);
            applySettings(currentSettings);
            saveSettings(currentSettings);
        });
    });
    
    // Paragraph Spacing
    var paraSpacingSlider = document.getElementById('paraSpacingSlider');
    if (paraSpacingSlider) {
        paraSpacingSlider.addEventListener('input', function() {
            currentSettings.paraSpacing = parseInt(this.value);
            applySettings(currentSettings);
            saveSettings(currentSettings);
        });
    }
    
    // Reset
    var btnReset = document.getElementById('btnResetSettings');
    if (btnReset) {
        btnReset.addEventListener('click', function() {
            currentSettings = Object.assign({}, defaults);
            applySettings(currentSettings);
            saveSettings(currentSettings);
        });
    }

    // ═══════════════════════════════════════════
    // FULLSCREEN MODE
    // ═══════════════════════════════════════════
    
    var btnFullscreen = document.getElementById('btnFullscreen');
    if (btnFullscreen) {
        btnFullscreen.addEventListener('click', function() {
            document.body.classList.toggle('reader-fullscreen');
            this.textContent = document.body.classList.contains('reader-fullscreen') 
                ? '✕ خروج' : '⛶ تمام‌صفحه';
        });
    }

    // ═══════════════════════════════════════════
    // KEYBOARD SHORTCUTS
    // ═══════════════════════════════════════════
    
    document.addEventListener('keydown', function(e) {
        // Don't trigger when typing in inputs
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable) {
            return;
        }
        
        switch (e.key) {
            case 'ArrowLeft':
                // Next chapter (RTL: left = next)
                var nextBtn = document.querySelector('.nav-btn.nav-next:not(.disabled)');
                if (nextBtn && nextBtn.href) {
                    window.location.href = nextBtn.href;
                }
                break;
                
            case 'ArrowRight':
                // Prev chapter (RTL: right = prev)
                var prevBtn = document.querySelector('.nav-btn.nav-prev:not(.disabled)');
                if (prevBtn && prevBtn.href) {
                    window.location.href = prevBtn.href;
                }
                break;
                
            case 'Escape':
                if (document.body.classList.contains('reader-fullscreen')) {
                    document.body.classList.remove('reader-fullscreen');
                    if (btnFullscreen) btnFullscreen.textContent = '⛶ تمام‌صفحه';
                }
                // Close settings panel
                if (settingsPanel && settingsPanel.classList.contains('open')) {
                    settingsPanel.classList.remove('open');
                    setTimeout(function() { settingsPanel.style.display = 'none'; }, 300);
                }
                // Close report modal
                var reportModal = document.getElementById('reportModal');
                if (reportModal && reportModal.style.display !== 'none') {
                    reportModal.style.display = 'none';
                }
                break;
        }
    });

    // ═══════════════════════════════════════════
    // VOTE (LIKE/DISLIKE)
    // ═══════════════════════════════════════════
    
    document.querySelectorAll('.vote-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (typeof novelReader === 'undefined' || !novelReader.isLoggedIn) {
                if (typeof novelToast === 'function') {
                    novelToast('برای رأی دادن وارد شوید', 'warning');
                }
                return;
            }
            
            var type = this.dataset.type;
            var chapterId = this.dataset.chapter;
            var self = this;
            
            fetch(novelReader.ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=novel_chapter_vote&chapter_id=' + chapterId + '&vote_type=' + type + '&nonce=' + novelReader.nonce
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    // Update all vote buttons (top + bottom)
                    document.querySelectorAll('.vote-btn[data-chapter="' + chapterId + '"]').forEach(function(b) {
                        b.classList.remove('active');
                        if (b.dataset.type === 'like') {
                            b.querySelector('.vote-count').textContent = data.data.likes;
                        } else {
                            b.querySelector('.vote-count').textContent = data.data.dislikes;
                        }
                    });
                    
                    // Set active on clicked type
                    if (data.data.user_vote) {
                        document.querySelectorAll('.vote-btn[data-type="' + data.data.user_vote + '"][data-chapter="' + chapterId + '"]').forEach(function(b) {
                            b.classList.add('active');
                        });
                    }
                    
                    // Update satisfaction
                    document.querySelectorAll('.vote-satisfaction').forEach(function(el) {
                        el.textContent = data.data.satisfaction + '٪ پسندیدند';
                        el.className = 'vote-satisfaction satisfaction-' + data.data.sat_class;
                    });
                }
            });
        });
    });

    // ═══════════════════════════════════════════
    // REPORT MODAL
    // ═══════════════════════════════════════════
    
    var reportModal = document.getElementById('reportModal');
    var btnReportOpen = document.getElementById('btnReportChapter');
    var btnReportClose = document.getElementById('reportModalClose');
    var btnReportCancel = document.getElementById('btnCancelReport');
    var btnReportSubmit = document.getElementById('btnSubmitReport');
    
    if (reportModal && btnReportOpen) {
        btnReportOpen.addEventListener('click', function() {
            reportModal.style.display = 'flex';
        });
        
        if (btnReportClose) {
            btnReportClose.addEventListener('click', function() {
                reportModal.style.display = 'none';
            });
        }
        
        if (btnReportCancel) {
            btnReportCancel.addEventListener('click', function() {
                reportModal.style.display = 'none';
            });
        }
        
        // Close on overlay click
        var overlay = reportModal.querySelector('.report-modal__overlay');
        if (overlay) {
            overlay.addEventListener('click', function() {
                reportModal.style.display = 'none';
            });
        }
        
        if (btnReportSubmit) {
            btnReportSubmit.addEventListener('click', function() {
                var reason = reportModal.querySelector('input[name="report_reason"]:checked');
                var desc = document.getElementById('reportDescription');
                
                if (!reason) return;
                
                this.disabled = true;
                this.textContent = 'در حال ارسال...';
                
                var self = this;
                
                fetch(novelReader.ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=novel_report_chapter&chapter_id=' + novelReader.chapterId + 
                          '&reason=' + reason.value + 
                          '&description=' + encodeURIComponent(desc ? desc.value : '') +
                          '&nonce=' + novelReader.nonce
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        reportModal.style.display = 'none';
                        if (typeof novelToast === 'function') {
                            novelToast('گزارش شما ارسال شد ✓', 'success');
                        }
                    } else {
                        if (typeof novelToast === 'function') {
                            novelToast(data.data.message || 'خطا در ارسال', 'error');
                        }
                    }
                })
                .finally(function() {
                    self.disabled = false;
                    self.textContent = 'ارسال گزارش';
                });
            });
        }
    }

    // ═══════════════════════════════════════════
    // RECAP TOGGLE
    // ═══════════════════════════════════════════
    
    var recapToggle = document.getElementById('recapToggle');
    var recapBody = document.getElementById('recapBody');
    
    if (recapToggle && recapBody) {
        // Restore state
        try {
            var recapState = localStorage.getItem('novel_recap_collapsed');
            if (recapState === 'true') {
                recapBody.classList.add('collapsed');
                recapToggle.textContent = 'نمایش ▼';
            }
        } catch (e) {}
        
        recapToggle.addEventListener('click', function() {
            var collapsed = recapBody.classList.toggle('collapsed');
            this.textContent = collapsed ? 'نمایش ▼' : 'بستن ▲';
            try {
                localStorage.setItem('novel_recap_collapsed', collapsed);
            } catch (e) {}
        });
    }

    // ═══════════════════════════════════════════
    // SCROLL POSITION SAVE
    // ═══════════════════════════════════════════
    
    if (typeof novelReader !== 'undefined' && novelReader.chapterId) {
        var scrollKey = 'novel_scroll_' + novelReader.chapterId;
        
        // Restore scroll position
        try {
            var savedScroll = localStorage.getItem(scrollKey);
            if (savedScroll) {
                var pos = parseInt(savedScroll);
                if (pos > 100) {
                    setTimeout(function() {
                        window.scrollTo(0, pos);
                    }, 100);
                }
            }
        } catch (e) {}
        
        // Save scroll position periodically
        var saveScrollTimer;
        window.addEventListener('scroll', function() {
            clearTimeout(saveScrollTimer);
            saveScrollTimer = setTimeout(function() {
                try {
                    localStorage.setItem(scrollKey, window.scrollY);
                } catch (e) {}
            }, 500);
        }, { passive: true });
        
        // Clear on page bottom (chapter completed)
        window.addEventListener('scroll', function() {
            var scrollTop = window.scrollY;
            var docHeight = document.documentElement.scrollHeight - window.innerHeight;
            if (docHeight > 0 && (scrollTop / docHeight) > 0.95) {
                try {
                    localStorage.removeItem(scrollKey);
                } catch (e) {}
            }
        }, { passive: true });
    }

    // ═══════════════════════════════════════════
    // VIP COIN PURCHASE
    // ═══════════════════════════════════════════
    
    var btnBuyCoins = document.getElementById('btnBuyWithCoins');
    if (btnBuyCoins) {
        btnBuyCoins.addEventListener('click', function() {
            if (this.disabled) return;
            
            if (!confirm('آیا از خرید این قسمت با ' + this.dataset.price + ' سکه مطمئنید؟')) {
                return;
            }
            
            this.disabled = true;
            var originalText = this.querySelector('.option-title').textContent;
            this.querySelector('.option-title').textContent = 'در حال خرید...';
            
            var self = this;
            
            fetch(novelReader.ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=novel_purchase_chapter&chapter_id=' + this.dataset.chapter + '&nonce=' + novelReader.nonce
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    // Reload to show content
                    if (typeof novelToast === 'function') {
                        novelToast('خرید موفق! در حال بارگذاری...', 'success');
                    }
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    if (typeof novelToast === 'function') {
                        novelToast(data.data.message || 'خطا در خرید', 'error');
                    }
                    self.querySelector('.option-title').textContent = originalText;
                    self.disabled = false;
                }
            })
            .catch(function() {
                self.querySelector('.option-title').textContent = originalText;
                self.disabled = false;
            });
        });
    }

    // ═══════════════════════════════════════════
    // COPY SHARE LINK
    // ═══════════════════════════════════════════
    
    document.querySelectorAll('.share-copy').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var url = this.dataset.url;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function() {
                    if (typeof novelToast === 'function') {
                        novelToast('لینک کپی شد ✓', 'success');
                    }
                });
            }
        });
    });

})();