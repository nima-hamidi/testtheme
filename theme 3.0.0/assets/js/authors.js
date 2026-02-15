/**
 * Authors Page JavaScript
 *
 * @package suspended-starter
 * @since 3.0.0
 */

(function() {
    'use strict';

    if (typeof novelAuthors === 'undefined') return;

    var searchInput = document.getElementById('authorsSearch');
    var sortSelect  = document.getElementById('authorsSort');
    var grid        = document.getElementById('authorsGrid');
    var searchTimer;

    // ═══ Search (debounce 400ms) ═══
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimer);
            var val = this.value;
            searchTimer = setTimeout(function() { loadAuthors(val, sortSelect ? sortSelect.value : 'novels', 1); }, 400);
        });
    }

    // ═══ Sort ═══
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            loadAuthors(searchInput ? searchInput.value : '', this.value, 1);
        });
    }

    function loadAuthors(search, sort, page) {
        if (!grid) return;
        grid.innerHTML = '<div class="authors-empty"><p>بارگذاری...</p></div>';

        fetch(novelAuthors.ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=novel_search_authors&search=' + encodeURIComponent(search) +
                  '&sort=' + sort + '&page=' + page + '&nonce=' + novelAuthors.nonce
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                grid.innerHTML = data.data.html;
                // Update URL
                var url = new URL(window.location);
                if (search) url.searchParams.set('search', search); else url.searchParams.delete('search');
                url.searchParams.set('sort', sort);
                url.searchParams.delete('pg');
                history.replaceState(null, '', url);
            }
        });
    }

    // ═══ Load More ═══
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-load-more-authors');
        if (!btn) return;

        var page = parseInt(btn.dataset.page);
        var max  = parseInt(btn.dataset.max);
        if (page > max) return;

        btn.disabled = true;
        btn.textContent = 'بارگذاری...';

        fetch(novelAuthors.ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=novel_load_more_authors&search=' + encodeURIComponent(btn.dataset.search || '') +
                  '&sort=' + (btn.dataset.sort || 'novels') + '&page=' + page + '&nonce=' + novelAuthors.nonce
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success && data.data.html) {
                grid.insertAdjacentHTML('beforeend', data.data.html);
                btn.dataset.page = page + 1;
                if (page + 1 > max) { btn.parentElement.remove(); }
                else { btn.textContent = 'بارگذاری بیشتر...'; btn.disabled = false; }
            }
        });
    });

    // ═══ Report User Modal ═══
    var reportModal = document.getElementById('reportUserModal');
    if (reportModal) {
        var reportUserId = 0;

        document.querySelectorAll('.btn-report-user').forEach(function(btn) {
            btn.addEventListener('click', function() {
                reportUserId = this.dataset.userId;
                reportModal.style.display = 'flex';
            });
        });

        reportModal.querySelectorAll('.report-modal__close, .btn-cancel-user-report, .report-modal__overlay').forEach(function(el) {
            el.addEventListener('click', function() { reportModal.style.display = 'none'; });
        });

        var submitReportBtn = reportModal.querySelector('.btn-submit-user-report');
        if (submitReportBtn) {
            submitReportBtn.addEventListener('click', function() {
                var reason = reportModal.querySelector('input[name="user_report_reason"]:checked');
                var desc = document.getElementById('userReportDesc');

                this.disabled = true;
                this.textContent = 'ارسال...';
                var self = this;

                fetch(novelAuthors.ajaxUrl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=novel_report_user&user_id=' + reportUserId +
                          '&reason=' + (reason ? reason.value : '') +
                          '&description=' + encodeURIComponent(desc ? desc.value : '') +
                          '&nonce=' + novelAuthors.nonce
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    reportModal.style.display = 'none';
                    if (typeof novelToast === 'function') {
                        novelToast(data.data.message || (data.success ? 'ارسال شد' : 'خطا'), data.success ? 'success' : 'error');
                    }
                })
                .finally(function() { self.disabled = false; self.textContent = 'ارسال گزارش'; });
            });
        }
    }

    // ═══ Followers Modal ═══
    var followersModal = document.getElementById('followersModal');
    if (followersModal) {
        document.querySelectorAll('.stat-clickable[data-modal="followers"]').forEach(function(box) {
            box.addEventListener('click', function() {
                followersModal.style.display = 'flex';
                var body = document.getElementById('followersModalBody');
                body.innerHTML = '<div class="loading-spinner">بارگذاری...</div>';

                // Get author ID from page
                var authorId = document.querySelector('.btn-follow-user[data-user-id]');
                var uid = authorId ? authorId.dataset.userId : 0;

                fetch(novelAuthors.ajaxUrl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=novel_get_followers&type=user&id=' + uid + '&list=followers&page=1&nonce=' + novelAuthors.nonce
                })
                .then(function(r) { return r.json(); })
                .then(function(data) { if (data.success) body.innerHTML = data.data.html; });
            });
        });

        followersModal.querySelectorAll('.report-modal__close, .report-modal__overlay').forEach(function(el) {
            el.addEventListener('click', function() { followersModal.style.display = 'none'; });
        });
    }
})();