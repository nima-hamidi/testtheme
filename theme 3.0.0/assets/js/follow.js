/**
 * Follow System JavaScript (Global)
 *
 * Handles follow/unfollow for users and novels across all pages.
 *
 * @package suspended-starter
 * @since 3.0.0
 */

(function() {
    'use strict';

    if (typeof novelFollow === 'undefined') return;

    // ═══ Follow User ═══
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-follow-user');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();

        if (!novelFollow.isLoggedIn) {
            window.location.href = novelFollow.loginUrl;
            return;
        }

        var userId = btn.dataset.userId;
        if (!userId) return;
        btn.disabled = true;

        fetch(novelFollow.ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=novel_follow_user&user_id=' + userId + '&nonce=' + novelFollow.nonce
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                // Update ALL buttons for this user
                document.querySelectorAll('.btn-follow-user[data-user-id="' + userId + '"]').forEach(function(b) {
                    b.classList.toggle('following', data.data.following);
                    var icon = b.querySelector('.follow-icon');
                    var text = b.querySelector('.follow-text');
                    var count = b.querySelector('.follow-count');

                    if (icon) icon.textContent = data.data.following ? '❤' : '🤍';
                    if (text) text.textContent = data.data.following ? 'دنبال شده ✓' : 'دنبال کردن';
                    if (count) count.textContent = '(' + data.data.count + ')';

                    // Simple buttons
                    if (!icon && !text) {
                        b.textContent = data.data.following ? '❤ دنبال شده' : '🤍 دنبال کردن';
                    }

                    // Small buttons
                    if (b.classList.contains('btn-follow-sm')) {
                        b.textContent = data.data.following ? '✓' : '+ دنبال';
                    }
                });

                if (typeof novelToast === 'function') {
                    novelToast(data.data.message, 'success');
                }
            }
        })
        .finally(function() { btn.disabled = false; });
    });

    // ═══ Follow Novel ═══
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-novel-follow, .card-btn-follow');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();

        if (!novelFollow.isLoggedIn) {
            window.location.href = novelFollow.loginUrl;
            return;
        }

        var novelId = btn.dataset.novelId;
        if (!novelId) return;
        btn.disabled = true;

        fetch(novelFollow.ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=novel_follow_novel&novel_id=' + novelId + '&nonce=' + novelFollow.nonce
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                document.querySelectorAll('[data-novel-id="' + novelId + '"].btn-novel-follow, [data-novel-id="' + novelId + '"].card-btn-follow').forEach(function(b) {
                    b.classList.toggle('following', data.data.following);
                    var icon = b.querySelector('.follow-icon');
                    var text = b.querySelector('.follow-text');
                    var count = b.querySelector('.follow-count');

                    if (icon) icon.textContent = data.data.following ? '❤' : '🤍';
                    if (text) text.textContent = data.data.following ? 'دنبال می‌کنید ✓' : 'دنبال کردن';
                    if (count) count.textContent = data.data.count;

                    // Card mini button
                    if (b.classList.contains('card-btn-follow')) {
                        b.textContent = data.data.following ? '❤' : '🤍';
                    }
                });

                if (typeof novelToast === 'function') {
                    novelToast(data.data.following ? 'دنبال شد ✓' : 'لغو شد', 'success');
                }
            }
        })
        .finally(function() { btn.disabled = false; });
    });
})();