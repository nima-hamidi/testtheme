/**
 * Novel Smart Features JavaScript
 * 
 * - Auto Night Mode
 * - Daily Recommendations
 * - Mood Discovery
 * - Theme Settings
 * - Catch-up Tracker
 * 
 * @package suspended developer
 * @since 3.0.0
 */

(function($) {
    'use strict';

    const SmartApp = {

        init() {
            this.initMoodDiscovery();
            this.initDailyRecommendations();
            this.initThemeSettings();
            this.initNightFilter();
        },

        // ═══════════════════════════════════════
        // Mood Discovery
        // ═══════════════════════════════════════

        initMoodDiscovery() {
            const $grid = $('#novelMoodGrid');
            if (!$grid.length) return;

            $grid.on('click', '.novel-mood-btn', (e) => {
                const mood = $(e.currentTarget).data('mood');
                this.loadMoodNovels(mood);
            });

            $('#moodResultsBack').on('click', () => {
                $('#novelMoodResults').slideUp(300);
                $('#novelMoodGrid').slideDown(300);
            });
        },

        loadMoodNovels(mood) {
            const $results = $('#novelMoodResults');
            const $grid = $('#moodResultsGrid');
            const $title = $('#moodResultsTitle');

            $grid.html('<div class="novel-daily-loading"><div class="novel-spinner"></div></div>');
            $('#novelMoodGrid').slideUp(300);
            $results.slideDown(300);

            $.ajax({
                url: NovelSmart.ajaxurl,
                method: 'POST',
                data: {
                    action: 'novel_get_mood_novels',
                    nonce: NovelSmart.nonce,
                    mood: mood,
                },
                success: (response) => {
                    if (response.success) {
                        const moodInfo = response.data.mood;
                        $title.html(`${moodInfo.icon} پیشنهاد برای حال‌وهوای «${moodInfo.label.replace(moodInfo.icon + ' ', '')}»`);

                        if (response.data.novels.length === 0) {
                            $grid.html(`<div class="novel-empty-state"><p>${NovelSmart.strings.no_results}</p></div>`);
                            return;
                        }

                        let html = '';
                        response.data.novels.forEach(novel => {
                            html += this.renderNovelCard(novel);
                        });
                        $grid.html(html);
                    } else {
                        $grid.html(`<p>${NovelSmart.strings.error}</p>`);
                    }
                },
                error: () => {
                    $grid.html(`<p>${NovelSmart.strings.error}</p>`);
                }
            });
        },

        // ═══════════════════════════════════════
        // Daily Recommendations
        // ═══════════════════════════════════════

        initDailyRecommendations() {
            const $section = $('#novelDailySection');
            if (!$section.length || !NovelSmart.is_logged) return;

            this.loadDailyRecommendations();
        },

        loadDailyRecommendations() {
            const $grid = $('#novelDailyGrid');

            $.ajax({
                url: NovelSmart.ajaxurl,
                method: 'POST',
                data: {
                    action: 'novel_get_daily_recommendations',
                    nonce: NovelSmart.nonce,
                },
                success: (response) => {
                    if (response.success && response.data.novels.length > 0) {
                        let html = '';
                        response.data.novels.forEach(novel => {
                            html += this.renderRecommendationCard(novel);
                        });
                        $grid.html(html);
                    } else {
                        // اگر پیشنهادی نبود بخش رو مخفی کن
                        $('#novelDailySection').hide();
                    }
                },
                error: () => {
                    $('#novelDailySection').hide();
                }
            });
        },

        // ═══════════════════════════════════════
        // Theme Settings
        // ═══════════════════════════════════════

        initThemeSettings() {
            $(document).on('change', '.novel-theme-radio', (e) => {
                const mode = $(e.target).val();
                this.setThemeMode(mode);

                // UI update
                $('.novel-theme-option').removeClass('is-active');
                $(e.target).closest('.novel-theme-option').addClass('is-active');

                // Save to server
                if (NovelSmart.is_logged) {
                    $.post(NovelSmart.ajaxurl, {
                        action: 'novel_save_theme_preference',
                        nonce: NovelSmart.nonce,
                        mode: mode,
                    });
                }

                localStorage.setItem('novel_theme_mode', mode);
                this.showToast(NovelSmart.strings.saved, 'success');
            });
        },

        setThemeMode(mode) {
            const apply = (theme) => {
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('novel_current_theme', theme);
            };

            switch (mode) {
                case 'dark': apply('dark'); break;
                case 'light': apply('light'); break;
                case 'system':
                    apply(window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                    break;
                case 'auto':
                default:
                    const h = new Date().getHours();
                    apply((h >= 20 || h < 7) ? 'dark' : 'light');
                    break;
            }
        },

        // ═══════════════════════════════════════
        // Night Filter
        // ═══════════════════════════════════════

        initNightFilter() {
            const $checkbox = $('#novelNightFilter');
            if (!$checkbox.length) return;

            const stored = localStorage.getItem('novel_night_filter');
            if (stored === '0') {
                $checkbox.prop('checked', false);
            }

            $checkbox.on('change', function() {
                const enabled = $(this).is(':checked');
                localStorage.setItem('novel_night_filter', enabled ? '1' : '0');

                if (!enabled) {
                    document.documentElement.style.filter = '';
                    document.documentElement.dataset.nightFilter = 'off';
                } else {
                    const h = new Date().getHours();
                    if (h >= 22 || h < 6) {
                        document.documentElement.style.filter = 'sepia(12%) brightness(96%)';
                        document.documentElement.dataset.nightFilter = 'on';
                    }
                }
            });
        },

        // ═══════════════════════════════════════
        // Card Renderers
        // ═══════════════════════════════════════

        renderNovelCard(novel) {
            const stars = novel.rating ? `★ ${novel.rating.toFixed(1)}` : '';
            const img = novel.image
                ? `<img src="${novel.image}" alt="${this.esc(novel.title)}" class="novel-smart-card__img" loading="lazy">`
                : '<div class="novel-smart-card__img-placeholder">📚</div>';

            return `
                <a href="${novel.url}" class="novel-smart-card">
                    <div class="novel-smart-card__image">${img}</div>
                    <div class="novel-smart-card__body">
                        <h4 class="novel-smart-card__title">${this.esc(novel.title)}</h4>
                        ${stars ? `<span class="novel-smart-card__rating">${stars}</span>` : ''}
                    </div>
                </a>
            `;
        },

        renderRecommendationCard(novel) {
            const stars = novel.rating ? `★ ${novel.rating.toFixed(1)}` : '';
            const img = novel.image
                ? `<img src="${novel.image}" alt="${this.esc(novel.title)}" class="novel-rec-card__img" loading="lazy">`
                : '<div class="novel-rec-card__img-placeholder">📚</div>';

            return `
                <a href="${novel.url}" class="novel-rec-card">
                    <div class="novel-rec-card__image">${img}</div>
                    <div class="novel-rec-card__body">
                        <h4 class="novel-rec-card__title">${this.esc(novel.title)}</h4>
                        ${stars ? `<span class="novel-rec-card__rating">${stars}</span>` : ''}
                        <p class="novel-rec-card__reason">${this.esc(novel.reason)}</p>
                    </div>
                </a>
            `;
        },

        // ═══════════════════════════════════════
        // Utils
        // ═══════════════════════════════════════

        showToast(msg, type) {
            if (typeof NovelApp !== 'undefined' && NovelApp.showToast) {
                NovelApp.showToast(msg, type);
                return;
            }
            const t = document.createElement('div');
            t.textContent = msg;
            t.style.cssText = `position:fixed;bottom:30px;left:50%;transform:translateX(-50%);padding:12px 24px;border-radius:10px;color:#fff;font-size:14px;z-index:99999;background:${type==='success'?'#10b981':'#ef4444'};box-shadow:0 4px 20px rgba(0,0,0,.15);font-family:inherit`;
            document.body.appendChild(t);
            setTimeout(() => { t.style.opacity='0'; t.style.transition='opacity .3s'; setTimeout(() => t.remove(), 300); }, 3000);
        },

        esc(text) {
            const d = document.createElement('div');
            d.textContent = text || '';
            return d.innerHTML;
        }
    };

    $(document).ready(() => SmartApp.init());

})(jQuery);