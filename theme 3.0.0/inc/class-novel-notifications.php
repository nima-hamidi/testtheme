<?php
/**
 * Unified Notification System
 *
 * Handles in-site notifications, email queue, cron cleanup.
 *
 * Types: new_chapter, new_novel, comment_reply, comment_like,
 *        mention, new_follower, novel_approved, novel_rejected,
 *        author_review, report_result, coin_expiry, achievement, system
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Novel_Notifications {

    private static $table;
    private static $queue_table;

    public function __construct() {
        global $wpdb;
        self::$table       = $wpdb->prefix . 'notifications';
        self::$queue_table = $wpdb->prefix . 'email_queue';

        add_action('after_switch_theme', [$this, 'create_tables']);
        add_action('init', [$this, 'maybe_create_tables'], 99);

        // AJAX
        add_action('wp_ajax_novel_get_notifications', [$this, 'ajax_get_notifications']);
        add_action('wp_ajax_novel_get_unread_count', [$this, 'ajax_get_unread_count']);
        add_action('wp_ajax_novel_mark_notification_read', [$this, 'ajax_mark_read']);
        add_action('wp_ajax_novel_mark_all_read', [$this, 'ajax_mark_all_read']);
        add_action('wp_ajax_novel_delete_read_notifications', [$this, 'ajax_delete_read']);
        add_action('wp_ajax_novel_load_more_notifications', [$this, 'ajax_load_more']);

        // Cron
        add_action('novel_process_email_queue', [$this, 'process_email_queue']);
        add_action('novel_cleanup_old_notifications', [$this, 'cleanup_old']);
        add_action('novel_check_coin_expiry', [$this, 'check_coin_expiry']);

        if (!wp_next_scheduled('novel_process_email_queue')) {
            wp_schedule_event(time(), 'every_minute', 'novel_process_email_queue');
        }
        if (!wp_next_scheduled('novel_cleanup_old_notifications')) {
            wp_schedule_event(time(), 'weekly', 'novel_cleanup_old_notifications');
        }
        if (!wp_next_scheduled('novel_check_coin_expiry')) {
            wp_schedule_event(time(), 'daily', 'novel_check_coin_expiry');
        }

        // Custom cron interval
        add_filter('cron_schedules', [$this, 'add_cron_interval']);

        // Enqueue
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function add_cron_interval($schedules) {
        $schedules['every_minute'] = [
            'interval' => 60,
            'display'  => 'هر دقیقه',
        ];
        return $schedules;
    }

    // ═══════════════════════════════════════════
    // TABLES
    // ═══════════════════════════════════════════

    public function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta("CREATE TABLE IF NOT EXISTS " . self::$table . " (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            type VARCHAR(30) NOT NULL,
            title VARCHAR(300) NOT NULL,
            message TEXT DEFAULT '',
            link VARCHAR(500) DEFAULT '',
            image VARCHAR(500) DEFAULT '',
            is_read TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_read (user_id, is_read),
            INDEX idx_user_date (user_id, created_at),
            INDEX idx_type (type)
        ) {$charset}");

        dbDelta("CREATE TABLE IF NOT EXISTS " . self::$queue_table . " (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            to_email VARCHAR(200) NOT NULL,
            to_user_id BIGINT UNSIGNED NULL,
            subject VARCHAR(300) NOT NULL,
            body LONGTEXT NOT NULL,
            status ENUM('pending','sent','failed') DEFAULT 'pending',
            attempts TINYINT DEFAULT 0,
            scheduled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            sent_at DATETIME NULL,
            error_message TEXT NULL,
            INDEX idx_status (status),
            INDEX idx_scheduled (scheduled_at)
        ) {$charset}");
    }

    public function maybe_create_tables() {
        global $wpdb;
        if ($wpdb->get_var("SHOW TABLES LIKE '" . self::$table . "'") !== self::$table) {
            $this->create_tables();
        }
    }

    // ═══════════════════════════════════════════
    // ENQUEUE
    // ═══════════════════════════════════════════

    public function enqueue_assets() {
        if (!is_user_logged_in()) return;

        wp_enqueue_style(
            'novel-notifications',
            get_template_directory_uri() . '/assets/css/notifications.css',
            ['novel-main-style'],
            NOVEL_VERSION
        );

        wp_enqueue_script(
            'novel-notifications',
            get_template_directory_uri() . '/assets/js/notifications.js',
            ['jquery'],
            NOVEL_VERSION,
            true
        );

        wp_localize_script('novel-notifications', 'novelNotif', [
            'ajaxUrl'      => admin_url('admin-ajax.php'),
            'nonce'        => wp_create_nonce('novel_notifications_action'),
            'pollInterval' => (int) get_option('novel_notif_poll_interval', 60) * 1000,
            'userId'       => get_current_user_id(),
        ]);
    }

    // ═══════════════════════════════════════════
    // CORE: SEND NOTIFICATION (Static)
    // ═══════════════════════════════════════════

    /**
     * Send a notification to a user
     *
     * @param int    $user_id  Recipient user ID
     * @param string $type     Notification type
     * @param string $title    Notification title
     * @param string $message  Optional message body
     * @param string $link     Optional link URL
     * @param string $image    Optional image URL
     * @return int|false       Notification ID or false
     */
    public static function send($user_id, $type, $title, $message = '', $link = '', $image = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'notifications';

        if (!$user_id || !$type || !$title) return false;

        // Throttle: comment_like → aggregate if 5+ in 1 hour
        if ($type === 'comment_like') {
            $recent = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table}
                 WHERE user_id = %d AND type = 'comment_like' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
                $user_id
            ));

            if ($recent >= 4) {
                // Update last one to aggregate
                $last_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$table}
                     WHERE user_id = %d AND type = 'comment_like'
                     ORDER BY created_at DESC LIMIT 1",
                    $user_id
                ));
                if ($last_id) {
                    $count = $recent + 1;
                    $wpdb->update($table, [
                        'title'      => '👍 ' . $count . ' نفر دیدگاه شما را پسندیدند',
                        'is_read'    => 0,
                        'created_at' => current_time('mysql'),
                    ], ['id' => $last_id]);

                    self::invalidate_cache($user_id);
                    return $last_id;
                }
            }
        }

        // Insert notification
        $wpdb->insert($table, [
            'user_id'    => $user_id,
            'type'       => $type,
            'title'      => $title,
            'message'    => $message,
            'link'       => $link,
            'image'      => $image,
            'is_read'    => 0,
            'created_at' => current_time('mysql'),
        ]);

        $notification_id = $wpdb->insert_id;

        // Invalidate cache
        self::invalidate_cache($user_id);

        // Check email preference & queue email
        self::maybe_queue_email($user_id, $type, $title, $message, $link);

        return $notification_id;
    }

    /**
     * Queue email if user has email notification enabled for this type
     */
    private static function maybe_queue_email($user_id, $type, $title, $message, $link) {
        // Get user notification settings
        $settings = get_user_meta($user_id, 'novel_notification_settings', true);
        if (!is_array($settings)) {
            $settings = self::get_default_settings();
        }

        $email_key = $type . '_email';
        if (isset($settings[$email_key]) && !$settings[$email_key]) {
            return; // User disabled email for this type
        }

        // Immediate emails (bypass queue)
        $immediate_types = ['verify_email', 'reset_password'];
        if (in_array($type, $immediate_types)) {
            return; // These are handled by auth system directly
        }

        $user = get_user_by('id', $user_id);
        if (!$user || !$user->user_email) return;

        // Build email body
        $body = self::build_email_body($type, $title, $message, $link, $user);

        // Queue it
        self::queue_email($user->user_email, $user_id, $title, $body);
    }

    /**
     * Build email HTML from template
     */
    private static function build_email_body($type, $title, $message, $link, $user) {
        $template_file = get_template_directory() . '/inc/email-templates/' . str_replace('_', '-', $type) . '.php';

        if (!file_exists($template_file)) {
            // Fallback: generic template
            $template_file = get_template_directory() . '/inc/email-templates/base-template.php';
        }

        $site_name = get_bloginfo('name');
        $site_url  = home_url();
        $user_name = $user->display_name;

        ob_start();
        include $template_file;
        return ob_get_clean();
    }

    /**
     * Add email to queue
     */
    public static function queue_email($to_email, $user_id, $subject, $body, $scheduled_at = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'email_queue';

        $wpdb->insert($table, [
            'to_email'     => $to_email,
            'to_user_id'   => $user_id,
            'subject'      => $subject,
            'body'         => $body,
            'status'       => 'pending',
            'attempts'     => 0,
            'scheduled_at' => $scheduled_at ?: current_time('mysql'),
        ]);
    }

    // ═══════════════════════════════════════════
    // READ / QUERY
    // ═══════════════════════════════════════════

    public static function get_unread_count($user_id) {
        $cached = get_transient('novel_notif_count_' . $user_id);
        if (false !== $cached) return (int) $cached;

        global $wpdb;
        $table = $wpdb->prefix . 'notifications';
        $count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND is_read = 0",
            $user_id
        ));

        set_transient('novel_notif_count_' . $user_id, $count, 60);
        return $count;
    }

    public static function get_recent($user_id, $limit = 10) {
        global $wpdb;
        $table = $wpdb->prefix . 'notifications';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
            $user_id, $limit
        ));
    }

    public static function get_all($user_id, $page = 1, $per_page = 20, $type = '') {
        global $wpdb;
        $table  = $wpdb->prefix . 'notifications';
        $offset = ($page - 1) * $per_page;

        $where = $wpdb->prepare("WHERE user_id = %d", $user_id);
        if ($type && $type !== 'all') {
            $where .= $wpdb->prepare(" AND type = %s", $type);
        }

        $results = $wpdb->get_results(
            "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT {$per_page} OFFSET {$offset}"
        );

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where}");

        return [
            'notifications' => $results,
            'total'         => $total,
            'max_pages'     => ceil($total / $per_page),
        ];
    }

    // ═══════════════════════════════════════════
    // OPERATIONS
    // ═══════════════════════════════════════════

    public static function mark_read($notification_id, $user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'notifications';
        $wpdb->update($table, ['is_read' => 1], ['id' => $notification_id, 'user_id' => $user_id]);
        self::invalidate_cache($user_id);
    }

    public static function mark_all_read($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'notifications';
        $wpdb->update($table, ['is_read' => 1], ['user_id' => $user_id, 'is_read' => 0]);
        self::invalidate_cache($user_id);
    }

    private static function invalidate_cache($user_id) {
        delete_transient('novel_notif_count_' . $user_id);
    }

    // ═══════════════════════════════════════════
    // CRON: EMAIL QUEUE PROCESSOR
    // ═══════════════════════════════════════════

    public function process_email_queue() {
        global $wpdb;
        $table     = self::$queue_table;
        $batch     = (int) get_option('novel_email_batch_size', 20);
        $max_tries = (int) get_option('novel_email_max_attempts', 3);

        $emails = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE status = 'pending' AND scheduled_at <= %s
             ORDER BY id ASC LIMIT %d",
            current_time('mysql'), $batch
        ));

        if (empty($emails)) return;

        foreach ($emails as $email) {
            $headers = ['Content-Type: text/html; charset=UTF-8'];
            $sent = wp_mail($email->to_email, $email->subject, $email->body, $headers);

            if ($sent) {
                $wpdb->update($table, [
                    'status'  => 'sent',
                    'sent_at' => current_time('mysql'),
                ], ['id' => $email->id]);
            } else {
                $attempts = $email->attempts + 1;
                $new_status = $attempts >= $max_tries ? 'failed' : 'pending';

                $wpdb->update($table, [
                    'attempts'      => $attempts,
                    'status'        => $new_status,
                    'error_message' => 'wp_mail returned false',
                ], ['id' => $email->id]);
            }
        }
    }

    // ═══════════════════════════════════════════
    // CRON: CLEANUP
    // ═══════════════════════════════════════════

    public function cleanup_old() {
        global $wpdb;
        $days = (int) get_option('novel_notif_retention_days', 90);

        // Old notifications
        $wpdb->query($wpdb->prepare(
            "DELETE FROM " . self::$table . " WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));

        // Old sent emails
        $wpdb->query(
            "DELETE FROM " . self::$queue_table . " WHERE status = 'sent' AND sent_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );

        // Old failed emails
        $wpdb->query(
            "DELETE FROM " . self::$queue_table . " WHERE status = 'failed' AND scheduled_at < DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
    }

    // ═══════════════════════════════════════════
    // CRON: COIN EXPIRY WARNING
    // ═══════════════════════════════════════════

    public function check_coin_expiry() {
        global $wpdb;
        $tx_table = $wpdb->prefix . 'coin_transactions';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tx_table}'") !== $tx_table) return;

        // Find users with coins expiring in 3 days
        // This is a placeholder — real implementation depends on coin expiry system in future phase
        // For now, skip
    }

    // ═══════════════════════════════════════════
    // DEFAULT NOTIFICATION SETTINGS
    // ═══════════════════════════════════════════

    public static function get_default_settings() {
        return [
            'new_chapter_site'      => true,
            'new_chapter_email'     => true,
            'new_novel_site'        => true,
            'new_novel_email'       => false,
            'comment_reply_site'    => true,
            'comment_reply_email'   => true,
            'comment_like_site'     => true,
            'comment_like_email'    => false,
            'mention_site'          => true,
            'mention_email'         => true,
            'new_follower_site'     => true,
            'new_follower_email'    => false,
            'novel_approved_site'   => true,
            'novel_approved_email'  => true,
            'novel_rejected_site'   => true,
            'novel_rejected_email'  => true,
            'author_review_site'    => true,
            'author_review_email'   => false,
            'report_result_site'    => true,
            'report_result_email'   => true,
            'coin_expiry_site'      => true,
            'coin_expiry_email'     => true,
            'achievement_site'      => true,
            'achievement_email'     => false,
            'system_site'           => true,
            'system_email'          => true,
        ];
    }

    // ═══════════════════════════════════════════
    // AJAX HANDLERS
    // ═══════════════════════════════════════════

    public function ajax_get_unread_count() {
        check_ajax_referer('novel_notifications_action', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error();

        $count = self::get_unread_count(get_current_user_id());
        wp_send_json_success(['count' => $count]);
    }

    public function ajax_get_notifications() {
        check_ajax_referer('novel_notifications_action', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error();

        $user_id = get_current_user_id();
        $recent  = self::get_recent($user_id, 10);
        $count   = self::get_unread_count($user_id);

        $html = '';
        if (empty($recent)) {
            $html = '<div class="notif-empty">هنوز اعلانی ندارید 🔔</div>';
        } else {
            foreach ($recent as $n) {
                $html .= self::render_notification_item($n);
            }
        }

        wp_send_json_success([
            'html'  => $html,
            'count' => $count,
        ]);
    }

    public function ajax_mark_read() {
        check_ajax_referer('novel_notifications_action', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error();

        $id = absint($_POST['notification_id'] ?? 0);
        if (!$id) wp_send_json_error();

        self::mark_read($id, get_current_user_id());
        wp_send_json_success();
    }

    public function ajax_mark_all_read() {
        check_ajax_referer('novel_notifications_action', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error();

        self::mark_all_read(get_current_user_id());
        wp_send_json_success(['message' => 'همه خوانده شد']);
    }

    public function ajax_delete_read() {
        check_ajax_referer('novel_notifications_action', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error();

        global $wpdb;
        $user_id = get_current_user_id();
        $wpdb->delete(self::$table, ['user_id' => $user_id, 'is_read' => 1]);

        wp_send_json_success(['message' => 'اعلان‌های خوانده‌شده حذف شدند']);
    }

    public function ajax_load_more() {
        check_ajax_referer('novel_notifications_action', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error();

        $page = absint($_POST['page'] ?? 1);
        $type = sanitize_text_field($_POST['type'] ?? 'all');
        $user_id = get_current_user_id();

        $result = self::get_all($user_id, $page, 20, $type);

        $html = '';
        foreach ($result['notifications'] as $n) {
            $html .= self::render_notification_item($n, true);
        }

        wp_send_json_success([
            'html'      => $html,
            'total'     => $result['total'],
            'max_pages' => $result['max_pages'],
        ]);
    }

    // ═══════════════════════════════════════════
    // RENDER HELPERS
    // ═══════════════════════════════════════════

    /**
     * Render a notification item HTML
     */
    public static function render_notification_item($n, $full = false) {
        $type_icons = [
            'new_chapter'    => '📖',
            'new_novel'      => '📚',
            'comment_reply'  => '💬',
            'comment_like'   => '👍',
            'mention'        => '📢',
            'new_follower'   => '❤',
            'novel_approved' => '✅',
            'novel_rejected' => '❌',
            'author_review'  => '⭐',
            'report_result'  => '📋',
            'coin_expiry'    => '⚠️',
            'achievement'    => '🏆',
            'system'         => '🔔',
        ];

        $icon     = $type_icons[$n->type] ?? '🔔';
        $is_read  = (int) $n->is_read;
        $time_ago = self::time_ago($n->created_at);
        $classes  = 'notif-item' . ($is_read ? '' : ' notif-unread');

        $html = '<div class="' . $classes . '" data-id="' . esc_attr($n->id) . '">';

        if ($n->image) {
            $html .= '<div class="notif-image"><img src="' . esc_url($n->image) . '" alt="" loading="lazy" /></div>';
        } else {
            $html .= '<div class="notif-icon">' . $icon . '</div>';
        }

        $html .= '<div class="notif-body">';

        if ($n->link) {
            $html .= '<a href="' . esc_url($n->link) . '" class="notif-title-link">';
        }
        $html .= '<span class="notif-title">' . esc_html($n->title) . '</span>';
        if ($n->link) {
            $html .= '</a>';
        }

        if ($full && $n->message) {
            $html .= '<p class="notif-message">' . esc_html(mb_substr($n->message, 0, 120)) . '</p>';
        }

        $html .= '<span class="notif-time">' . esc_html($time_ago) . '</span>';
        $html .= '</div>'; // .notif-body

        if (!$is_read) {
            $html .= '<span class="notif-dot" title="خوانده‌نشده">●</span>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Human-readable time ago
     */
    public static function time_ago($datetime) {
        $now  = current_time('timestamp');
        $time = strtotime($datetime);
        $diff = $now - $time;

        if ($diff < 60) return 'لحظاتی پیش';
        if ($diff < 3600) return (int)($diff / 60) . ' دقیقه پیش';
        if ($diff < 86400) return (int)($diff / 3600) . ' ساعت پیش';
        if ($diff < 604800) return (int)($diff / 86400) . ' روز پیش';
        if ($diff < 2592000) return (int)($diff / 604800) . ' هفته پیش';
        return date_i18n('j F Y', $time);
    }

    /**
     * Render notification bell for header
     */
    public static function render_bell() {
        if (!is_user_logged_in()) return '';

        $count = self::get_unread_count(get_current_user_id());

        ob_start();
        ?>
        <div class="notif-bell-wrapper" id="notifBell">
            <button class="notif-bell-btn" id="notifBellBtn" aria-label="اعلان‌ها">
                <svg class="bell-icon" viewBox="0 0 24 24" width="22" height="22">
                    <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z" fill="currentColor"/>
                </svg>
                <?php if ($count > 0) : ?>
                    <span class="notif-badge" id="notifBadge">
                        <?php echo $count > 99 ? '+۹۹' : number_format_i18n($count); ?>
                    </span>
                <?php else : ?>
                    <span class="notif-badge" id="notifBadge" style="display:none;"></span>
                <?php endif; ?>
            </button>

            <div class="notif-dropdown" id="notifDropdown" style="display:none;">
                <div class="notif-dropdown__header">
                    <h4>اعلان‌ها</h4>
                    <button class="notif-mark-all-btn" id="notifMarkAllBtn" title="همه خوانده شد">✓ همه خوانده شد</button>
                </div>
                <div class="notif-dropdown__body" id="notifDropdownBody">
                    <div class="notif-loading">
                        <div class="notif-skeleton"></div>
                        <div class="notif-skeleton"></div>
                        <div class="notif-skeleton"></div>
                    </div>
                </div>
                <div class="notif-dropdown__footer">
                    <a href="<?php echo home_url('/dashboard/?tab=notifications'); ?>">مشاهده همه اعلان‌ها →</a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}