/**
 * فیلتر و مرتب‌سازی آرشیو - Flavor Novel
 *
 * @package Flavor_Novel
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        // جستجو با Enter
        const searchInput = document.querySelector('.fn-filter-search');
        if (searchInput) {
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    this.closest('form').submit();
                }
            });

            // جستجوی خودکار بعد از توقف تایپ
            let searchTimer;
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimer);
                const form = this.closest('form');
                searchTimer = setTimeout(() => {
                    if (this.value.length >= 2 || this.value.length === 0) {
                        form.submit();
                    }
                }, 800);
            });
        }

        // ذخیره/بازیابی حالت نمایش
        const viewBtns = document.querySelectorAll('.fn-view-toggle__btn');
        viewBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                FN.storage.set('archive_view', this.dataset.view);
            });
        });

        // اسکرول نرم به بالای نتایج بعد از فیلتر
        const hasFilters = new URLSearchParams(window.location.search);
        if (hasFilters.has('genre') || hasFilters.has('status') || hasFilters.has('orderby')) {
            const filters = document.getElementById('fnFilters');
            if (filters) {
                filters.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    });

})();