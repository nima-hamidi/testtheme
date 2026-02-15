<?php
/**
 * Dashboard: Notifications Tab
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;
if (!is_user_logged_in()) return;

$user_id = get_current_user_id();
$type    = sanitize_text_field($_GET['notif_type'] ?? 'all');
$page    = max(1, absint($_GET['notif_page'] ?? 1));
$result  = Novel_Notifications::get_all($user_id, $page, 20, $type);

$type_labels = [
    'all'            => 'همه',
    'new_chapter'    => '📖 قسمت جدید',
    'comment_reply'  => '💬 پاسخ دیدگاه',
    'comment_like'   => '👍 لایک',
    'new_follower'   => '❤ فالو',
    'system'         => '🔔 سیستم',
];
?>

<div class="dashboard-notifications">
    <div class="notif-page-header">
        <h2 class="dashboard-form-title">🔔 اعلان‌ها</h2>
        <div class="notif-page-actions">
            <button class="btn-notif-action" id="btnMarkAllReadPage">✓ همه خوانده شد</button>
            <button class="btn-notif-action btn-danger" id="btnDeleteReadPage">🗑 حذف خوانده‌شده‌ها</button>
        </div>
    </div>

    <!-- Type Filter -->
    <div class="notif-type-filter">
        <?php foreach ($type_labels as $key => $label) : ?>
            <a href="<?php echo add_query_arg(['tab' => 'notifications', 'notif_type' => $key], home_url('/dashboard/')); ?>"
               class="notif-type-chip <?php echo $type === $key ? 'active' : ''; ?>">
                <?php echo $label; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- List -->
    <div class="notif-list" id="notifPageList">
        <?php if (!empty($result['notifications'])) : ?>
            <?php foreach ($result['notifications'] as $n) : ?>
                <?php echo Novel_Notifications::render_notification_item($n, true); ?>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="notif-empty-page">
                <span class="empty-icon">🔔</span>
                <p>هنوز اعلانی ندارید!</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($result['max_pages'] > 1) : ?>
    <div class="notif-pagination">
        <?php echo paginate_links([
            'total'    => $result['max_pages'],
            'current'  => $page,
            'format'   => '?notif_page=%#%',
            'add_args' => ['tab' => 'notifications', 'notif_type' => $type],
        ]); ?>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var markAllBtn = document.getElementById('btnMarkAllReadPage');
    var deleteBtn  = document.getElementById('btnDeleteReadPage');

    if (markAllBtn) {
        markAllBtn.addEventListener('click', function() {
            fetch(novelNotif.ajaxUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=novel_mark_all_read&nonce=' + novelNotif.nonce
            }).then(function(r) { return r.json(); }).then(function(d) {
                if (d.success) {
                    document.querySelectorAll('.notif-unread').forEach(function(el) {
                        el.classList.remove('notif-unread');
                        var dot = el.querySelector('.notif-dot');
                        if (dot) dot.remove();
                    });
                    if (typeof novelToast === 'function') novelToast('همه خوانده شد ✓', 'success');
                }
            });
        });
    }

    if (deleteBtn) {
        deleteBtn.addEventListener('click', function() {
            if (!confirm('اعلان‌های خوانده‌شده حذف شوند؟')) return;
            fetch(novelNotif.ajaxUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=novel_delete_read_notifications&nonce=' + novelNotif.nonce
            }).then(function(r) { return r.json(); }).then(function(d) {
                if (d.success) {
                    document.querySelectorAll('.notif-item:not(.notif-unread)').forEach(function(el) {
                        el.remove();
                    });
                    if (typeof novelToast === 'function') novelToast('حذف شد', 'success');
                }
            });
        });
    }

    // Click to mark read + navigate
    document.querySelectorAll('.notif-item').forEach(function(item) {
        item.addEventListener('click', function(e) {
            var link = this.querySelector('.notif-title-link');
            var id = this.dataset.id;

            if (this.classList.contains('notif-unread') && id) {
                fetch(novelNotif.ajaxUrl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=novel_mark_notification_read&notification_id=' + id + '&nonce=' + novelNotif.nonce
                });
            }

            if (link && !e.target.closest('button')) {
                window.location.href = link.href;
            }
        });
    });
});
</script>