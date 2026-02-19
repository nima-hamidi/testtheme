/**
 * Novel Compare JavaScript
 * جستجوی AJAX autocomplete + ساخت URL مقایسه
 * 
 * @package suspended developer
 * @since 3.0.0
 */

(function($) {
    'use strict';

    const CompareApp = {

        searchTimer: null,

        init() {
            this.bindSearch();
            this.bindFormSubmit();
        },

        bindSearch() {
            $(document).on('input', '.novel-compare-search', (e) => {
                const $input = $(e.target);
                const query = $input.val().trim();
                const $wrap = $input.closest('.novel-compare-search-wrap');
                const $dropdown = $wrap.find('.novel-compare-dropdown');

                clearTimeout(this.searchTimer);

                if (query.length < 2) {
                    $dropdown.hide().empty();
                    return;
                }

                this.searchTimer = setTimeout(() => {
                    this.searchNovels(query, $dropdown, $input);
                }, 300);
            });

            // بستن dropdown
            $(document).on('click', (e) => {
                if (!$(e.target).closest('.novel-compare-search-wrap').length) {
                    $('.novel-compare-dropdown').hide();
                }
            });

            // انتخاب از dropdown
            $(document).on('click', '.novel-compare-option', (e) => {
                const $opt = $(e.currentTarget);
                const $wrap = $opt.closest('.novel-compare-search-wrap');
                const $input = $wrap.find('.novel-compare-search');
                const $hidden = $wrap.find('.novel-compare-id');

                $input.val($opt.data('title'));
                $hidden.val($opt.data('id'));
                $wrap.find('.novel-compare-dropdown').hide();
            });
        },

        searchNovels(query, $dropdown, $input) {
            $.ajax({
                url: NovelSmart.ajaxurl || (typeof novelData !== 'undefined' ? novelData.ajaxurl : '/wp-admin/admin-ajax.php'),
                method: 'POST',
                data: {
                    action: 'novel_search_autocomplete',
                    nonce: NovelSmart.nonce || '',
                    query: query,
                    post_type: 'novel',
                },
                success: (response) => {
                    if (response.success && response.data.results) {
                        let html = '';
                        response.data.results.forEach(item => {
                            const img = item.image
                                ? `<img src="${item.image}" class="novel-compare-option__img">`
                                : '<span class="novel-compare-option__img-ph">📚</span>';
                            html += `
                                <div class="novel-compare-option" data-id="${item.id}" data-title="${this.esc(item.title)}">
                                    ${img}
                                    <div class="novel-compare-option__info">
                                        <span class="novel-compare-option__title">${this.esc(item.title)}</span>
                                        <span class="novel-compare-option__meta">★ ${item.rating || '-'}</span>
                                    </div>
                                </div>
                            `;
                        });

                        if (!html) {
                            html = '<div class="novel-compare-option novel-compare-option--empty">نتیجه‌ای یافت نشد</div>';
                        }

                        $dropdown.html(html).show();
                    }
                }
            });
        },

        bindFormSubmit() {
            $('.novel-compare-form').on('submit', (e) => {
                e.preventDefault();

                const ids = [];
                $('.novel-compare-id').each(function() {
                    const val = $(this).val();
                    if (val) ids.push(val);
                });

                if (ids.length < 2) {
                    if (typeof NovelApp !== 'undefined' && NovelApp.showToast) {
                        NovelApp.showToast('حداقل ۲ رمان انتخاب کنید.', 'warning');
                    } else {
                        alert('حداقل ۲ رمان انتخاب کنید.');
                    }
                    return;
                }

                const url = window.location.pathname + '?novels=' + ids.join(',');
                window.location.href = url;
            });
        },

        esc(text) {
            const d = document.createElement('div');
            d.textContent = text || '';
            return d.innerHTML;
        }
    };

    $(document).ready(() => CompareApp.init());

})(jQuery);