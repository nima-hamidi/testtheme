/**
 * Filter JS - Archive page filtering
 * 
 * Handles URL-based filtering without AJAX (progressive enhancement)
 * 
 * @package suspended-starter
 * @since 3.0.0
 */

(function() {
    'use strict';

    var filtersContainer = document.getElementById('archiveFilters');
    if (!filtersContainer) return;

    var baseUrl = filtersContainer.dataset.baseUrl || window.location.pathname;

    // Get current params
    function getParams() {
        var params = new URLSearchParams(window.location.search);
        return params;
    }

    // Build URL from params
    function buildUrl(params) {
        var url = baseUrl;
        var str = params.toString();
        if (str) url += '?' + str;
        return url;
    }

    // Navigate with filters
    function applyFilter(key, value) {
        var params = getParams();
        if (value) {
            params.set(key, value);
        } else {
            params.delete(key);
        }
        // Reset to page 1 when filtering
        params.delete('paged');
        window.location.href = buildUrl(params);
    }

    // ═══ Filter Chips ═══
    filtersContainer.querySelectorAll('.filter-chip').forEach(function(chip) {
        chip.addEventListener('click', function() {
            var filterKey = this.dataset.filter;
            var filterValue = this.dataset.value;
            applyFilter(filterKey, filterValue);
        });
    });

    // ═══ Sort Select ═══
    var sortSelect = document.getElementById('sortSelect');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            applyFilter('sort', this.value);
        });
    }

    // ═══ View Toggle ═══
    filtersContainer.querySelectorAll('.view-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var view = this.dataset.view;
            applyFilter('view', view);
            
            // Also save to localStorage
            try {
                localStorage.setItem('novel_archive_view', view);
            } catch (e) {}
        });
    });

    // ═══ Restore view from localStorage ═══
    try {
        var savedView = localStorage.getItem('novel_archive_view');
        var params = getParams();
        if (savedView && !params.has('view')) {
            var results = document.getElementById('archiveResults');
            if (results) {
                results.classList.remove('view-grid', 'view-list');
                results.classList.add('view-' + savedView);
            }
            filtersContainer.querySelectorAll('.view-btn').forEach(function(b) {
                b.classList.toggle('active', b.dataset.view === savedView);
            });
        }
    } catch (e) {}

})();