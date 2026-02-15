/**
 * Notifications JavaScript
 *
 * Handles:
 * - Bell dropdown toggle
 * - AJAX polling for new notifications
 * - Mark read on click
 * - Mark all read
 * - Skeleton loading
 *
 * @package suspended-starter
 * @since 3.0.0
 */

(function() {
    'use strict';

    if (typeof novelNotif === 'undefined') return;

    var bell     = document.getElementById('notifBellBtn');
    var dropdown = document.getElementById('notifDropdown');
    var badge    = document.getElementById('notifBadge');
    var body     = document.getElementById('notifDropdownBody');
    var markAll  = document.getElementById('notifMarkAllBtn');
    var isOpen   = false;
    var loaded   = false;
    var lastCount = parseInt(badge ? badge.textContent : '0') || 0;

    if (!bell || !dropdown) return;

    // ═══ Toggle Dropdown ═══
    bell.addEventListener('click', function(e) {
        e.stopPropagation();
        isOpen = !isOpen;
        dropdown.style.display = isOpen ? 'flex' : 'none';

        if (isOpen && !loaded) {
            loadNotifications();
        }
    });

    // Close on outside click
    document.addEventListener('click', function(e) {
        if (isOpen && !dropdown.contains(e.target) && e.target !== bell) {
            isOpen = false;
            dropdown.style.display = 'none';
        }
    });

    // Close on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isOpen) {
            isOpen = false;
            dropdown.style.display = 'none';
        }
    });

    // ═══ Load Notifications ═══
    function loadNotifications() {
        fetch(novelNotif.ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=novel_get_notifications&nonce=' + novelNotif.nonce
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                body.innerHTML = data.data.html;
                updateBadge(data.data.count);
                loaded = true;
                attachClickHandlers();
            }
        })
        .catch(function() {
            body.innerHTML = '<div class="notif-empty">خطا در بارگذاری</div>';
        });
    }

    // ═══ Click to Mark Read ═══
    function attachClickHandlers() {
        body.querySelectorAll('.notif-item').forEach(function(item) {
            item.addEventListener('click', function() {
                var id = this.dataset.id;
                var link = this.querySelector('.notif-title-link');

                if (this.classList.contains('notif-unread') && id) {
                    this.classList.remove('notif-unread');
                    var dot = this.querySelector('.notif-dot');
                    if (dot) dot.remove();

                    fetch(novelNotif.ajaxUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'action=novel_mark_notification_read&notification_id=' + id + '&nonce=' + novelNotif.nonce
                    });

                    lastCount = Math.max(0, lastCount - 1);
                    updateBadge(lastCount);
                }

                if (link) {
                    setTimeout(function() {
                        window.location.href = link.href;
                    }, 100);
                }
            });
        });
    }

    // ═══ Mark All Read ═══
    if (markAll) {
        markAll.addEventListener('click', function(e) {
            e.stopPropagation();

            fetch(novelNotif.ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=novel_mark_all_read&nonce=' + novelNotif.nonce
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    body.querySelectorAll('.notif-unread').forEach(function(el) {
                        el.classList.remove('notif-unread');
                        var dot = el.querySelector('.notif-dot');
                        if (dot) dot.remove();
                    });
                    updateBadge(0);

                    if (typeof novelToast === 'function') {
                        novelToast('همه خوانده شد ✓', 'success');
                    }
                }
            });
        });
    }

    // ═══ Update Badge ═══
    function updateBadge(count) {
        lastCount = count;
        if (!badge) return;

        if (count > 0) {
            badge.textContent = count > 99 ? '+۹۹' : count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }

    // ═══ Polling for New Notifications ═══
    var pollInterval = novelNotif.pollInterval || 60000;

    function poll() {
        fetch(novelNotif.ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=novel_get_unread_count&nonce=' + novelNotif.nonce
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                var newCount = data.data.count;
                if (newCount !== lastCount) {
                    updateBadge(newCount);

                    // If dropdown is open, refresh
                    if (isOpen) {
                        loadNotifications();
                    }

                    // If new notifications appeared
                    if (newCount > lastCount) {
                        // Optional: subtle bell shake
                        if (bell) {
                            bell.classList.add('bell-shake');
                            setTimeout(function() {
                                bell.classList.remove('bell-shake');
                            }, 600);
                        }
                    }

                    lastCount = newCount;
                }
            }
        })
        .catch(function() {});
    }

    // Start polling
    setInterval(poll, pollInterval);

    // Also poll on page visibility change
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            poll();
        }
    });

})();