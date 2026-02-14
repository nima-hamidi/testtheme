/**
 * سیستم بوکمارک - Flavor Novel
 *
 * @package Flavor_Novel
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        // رویداد کلیک روی دکمه بوکمارک
        FN.delegate(document, 'click', '.fn-bookmark-btn', async function (e, btn) {
            e.preventDefault();

            if (!flavor_novel.is_user_logged_in) {
                FN.toast(flavor_novel.i18n.login_required, 'error');
                return;
            }

            const novelId = btn.dataset.novelId;
            if (!novelId) return;

            // غیرفعال کردن دکمه
            btn.disabled = true;
            btn.style.opacity = '0.6';

            const result = await FN.ajax('fn_toggle_bookmark', {
                novel_id: novelId,
            });

            btn.disabled = false;
            btn.style.opacity = '';

            if (result && result.success) {
                const isAdded = result.data.action === 'added';

                // بروزرسانی ظاهر دکمه
                btn.classList.toggle('bookmarked', isAdded);

                // بروزرسانی آیکون
                const svg = btn.querySelector('svg');
                if (svg) {
                    svg.setAttribute('fill', isAdded ? 'currentColor' : 'none');
                }

                // بروزرسانی متن
                const textEl = btn.querySelector('.fn-bookmark-btn__text');
                if (textEl) {
                    textEl.textContent = isAdded ? 'در کتابخانه' : 'افزودن به کتابخانه';
                }

                // نمایش پیام
                FN.toast(
                    isAdded ? flavor_novel.i18n.bookmark_added : flavor_novel.i18n.bookmark_removed,
                    'success'
                );
            } else {
                FN.toast(flavor_novel.i18n.error, 'error');
            }
        });
    });

})();