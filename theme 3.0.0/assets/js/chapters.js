/**
 * Chapters JS - Chapter list interactions
 * 
 * Handles:
 * - Load more chapters (AJAX pagination)
 * - Chapter filter (free/VIP)
 * - Chapter sort (asc/desc)
 * 
 * @package suspended-starter
 * @since 3.0.0
 */

(function() {
    'use strict';

    // ═══════════════════════════════════════════
    // LOAD MORE CHAPTERS
    // ═══════════════════════════════════════════
    
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-load-more-chapters');
        if (!btn) return;
        
        e.preventDefault();
        
        var page = parseInt(btn.dataset.page);
        var maxPages = parseInt(btn.dataset.max);
        var novelId = btn.dataset.novelId;
        
        if (page > maxPages) return;
        
        btn.disabled = true;
        btn.textContent = 'در حال بارگذاری...';
        
        var url = (typeof novelChapters !== 'undefined' ? novelChapters.ajaxUrl : '') || '';
        if (!url) return;
        
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=novel_load_more_chapters&novel_id=' + novelId + 
                  '&page=' + page + 
                  '&nonce=' + (novelChapters.nonce || '')
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success && data.data.html) {
                // Insert before the load more button wrapper
                var wrapper = btn.closest('.chapters-load-more-wrap');
                if (wrapper) {
                    wrapper.insertAdjacentHTML('beforebegin', data.data.html);
                }
                
                var nextPage = page + 1;
                if (nextPage > maxPages) {
                    btn.remove();
                    if (wrapper) wrapper.remove();
                } else {
                    btn.dataset.page = nextPage;
                    btn.textContent = 'بارگذاری بیشتر...';
                    btn.disabled = false;
                }
            }
        })
        .catch(function() {
            btn.textContent = 'خطا! دوباره تلاش کنید';
            btn.disabled = false;
        });
    });

    // ═══════════════════════════════════════════
    // AJAX VOTE HANDLER (Server-side registration)
    // ═══════════════════════════════════════════
    
    // Registered in reader.js, but if on novel page we need AJAX handlers too
    // These are handled by the inline scripts in single-novel.php

})();