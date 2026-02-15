<?php
/**
 * Novel Reports System
 * 
 * گزارش‌دهی یکپارچه برای قسمت، دیدگاه، کاربر و رمان
 *
 * @package suspended-flavor
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

class Novel_Reports {

    private static $instance = null;
    
    /** انواع مجاز گزارش */
    private $valid_types = ['chapter', 'comment', 'user', 'novel'];
    
    /** دلایل مجاز بر اساس نوع */
    private $valid_reasons = [
        'chapter' => ['typo', 'bad_translation', 'inappropriate', 'duplicate', 'broken_link', 'other'],
        'comment' => ['spam', 'insult', 'spoiler', 'inappropriate', 'other'],
        'user'    => ['inappropriate', 'spam', 'fake_identity', 'other'],
        'novel'   => ['duplicate', 'inappropriate', 'copyright', 'other'],
    ];

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('wp_ajax_novel_submit_report', [$this, 'submit_report']);
        add_action('wp_ajax_novel_check_reported', [$this, 'check_reported']);
        add_action('wp_footer', [$this, 'render_report_modal']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'novel-reports',
            get_template_directory_uri() . '/assets/css/reports.css',
            ['novel-main-style'],
            FLAVOR_VERSION
        );
        
        wp_enqueue_script(
            'novel-reports',
            get_template_directory_uri() . '/assets/js/reports.js',
            ['jquery'],
            FLAVOR_VERSION,
            true
        );
        
        wp_localize_script('novel-reports', 'novelReports', [
            'ajaxUrl'  => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('novel_report_nonce'),
            'strings'  => [
                'selectReason'   => 'لطفاً یک دلیل انتخاب کنید',
                'descRequired'   => 'لطفاً توضیحات را وارد کنید',
                'submitting'     => 'در حال ارسال...',
                'success'        => 'گزارش شما ثبت شد و بررسی خواهد شد ✓',
                'alreadyReported'=> 'شما قبلاً این مورد را گزارش کرده‌اید ✓',
                'error'          => 'خطا در ارسال گزارش',
                'loginRequired'  => 'برای گزارش‌دهی باید وارد حساب شوید',
                'verifyRequired' => 'لطفاً ابتدا ایمیل خود را تأیید کنید',
            ],
            'reasons' => [
                'chapter' => [
                    ['value' => 'typo',            'label' => 'خطای تایپی',        'icon' => '📝'],
                    ['value' => 'bad_translation', 'label' => 'ترجمه نادرست',      'icon' => '🔄'],
                    ['value' => 'inappropriate',   'label' => 'محتوای نامناسب',    'icon' => '🚫'],
                    ['value' => 'duplicate',       'label' => 'تکراری',            'icon' => '📋'],
                    ['value' => 'broken_link',     'label' => 'لینک/تصویر خراب',  'icon' => '🔗'],
                    ['value' => 'other',           'label' => 'سایر',             'icon' => '📌'],
                ],
                'comment' => [
                    ['value' => 'spam',          'label' => 'اسپم',                'icon' => '🤖'],
                    ['value' => 'insult',        'label' => 'توهین و بی‌احترامی', 'icon' => '🤬'],
                    ['value' => 'spoiler',       'label' => 'اسپویلر بدون برچسب', 'icon' => '⚠️'],
                    ['value' => 'inappropriate', 'label' => 'محتوای نامناسب',     'icon' => '🚫'],
                    ['value' => 'other',         'label' => 'سایر',              'icon' => '📌'],
                ],
                'user' => [
                    ['value' => 'inappropriate', 'label' => 'رفتار نامناسب',      'icon' => '🚫'],
                    ['value' => 'spam',          'label' => 'اسپم',               'icon' => '🤖'],
                    ['value' => 'fake_identity', 'label' => 'هویت جعلی',          'icon' => '🎭'],
                    ['value' => 'other',         'label' => 'سایر',              'icon' => '📌'],
                ],
                'novel' => [
                    ['value' => 'duplicate',     'label' => 'رمان تکراری',        'icon' => '📋'],
                    ['value' => 'inappropriate', 'label' => 'محتوای نامناسب',     'icon' => '🚫'],
                    ['value' => 'copyright',     'label' => 'نقض کپی‌رایت',       'icon' => '©️'],
                    ['value' => 'other',         'label' => 'سایر',              'icon' => '📌'],
                ],
            ],
        ]);
    }

    /**
     * ارسال گزارش (AJAX)
     */
    public function submit_report() {
        check_ajax_referer('novel_report_nonce', 'nonce');
        
        // بررسی لاگین
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'برای گزارش‌دهی باید وارد حساب شوید']);
        }
        
        $user_id = get_current_user_id();
        
        // بررسی تأیید ایمیل
        if (!get_user_meta($user_id, 'novel_email_verified', true)) {
            wp_send_json_error(['message' => 'لطفاً ابتدا ایمیل خود را تأیید کنید']);
        }
        
        // Rate limiting: حداکثر ۱۰ گزارش در ساعت
        $rate_key = 'novel_report_rate_' . $user_id;
        $rate_count = (int) get_transient($rate_key);
        if ($rate_count >= 10) {
            wp_send_json_error(['message' => 'تعداد گزارش‌های شما بیش از حد مجاز است. لطفاً بعداً تلاش کنید']);
        }
        
        // Sanitize inputs
        $reported_type = sanitize_text_field($_POST['reported_type'] ?? '');
        $reported_id   = absint($_POST['reported_id'] ?? 0);
        $reason        = sanitize_text_field($_POST['reason'] ?? '');
        $description   = sanitize_textarea_field($_POST['description'] ?? '');
        
        // Validate type
        if (!in_array($reported_type, $this->valid_types, true)) {
            wp_send_json_error(['message' => 'نوع گزارش نامعتبر است']);
        }
        
        // Validate ID
        if ($reported_id <= 0) {
            wp_send_json_error(['message' => 'شناسه مورد گزارش نامعتبر است']);
        }
        
        // Validate reason
        if (!in_array($reason, $this->valid_reasons[$reported_type], true)) {
            wp_send_json_error(['message' => 'دلیل گزارش نامعتبر است']);
        }
        
        // Validate description for "other"
        if ($reason === 'other' && empty($description)) {
            wp_send_json_error(['message' => 'لطفاً توضیحات را وارد کنید']);
        }
        
        // Trim description
        $description = mb_substr($description, 0, 500);
        
        // Validate reported item exists
        if (!$this->validate_reported_item($reported_type, $reported_id)) {
            wp_send_json_error(['message' => 'مورد گزارش‌شده یافت نشد']);
        }
        
        // Prevent self-report
        if ($this->is_self_report($reported_type, $reported_id, $user_id)) {
            wp_send_json_error(['message' => 'نمی‌توانید محتوای خودتان را گزارش کنید']);
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'novel_reports';
        
        // بررسی تکراری
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} 
             WHERE reporter_id = %d AND reported_type = %s AND reported_id = %d",
            $user_id, $reported_type, $reported_id
        ));
        
        if ($existing) {
            wp_send_json_error(['message' => 'شما قبلاً این مورد را گزارش کرده‌اید', 'already_reported' => true]);
        }
        
        // ثبت گزارش
        $inserted = $wpdb->insert($table, [
            'reporter_id'   => $user_id,
            'reported_type' => $reported_type,
            'reported_id'   => $reported_id,
            'reason'        => $reason,
            'description'   => $description,
            'status'        => 'pending',
            'created_at'    => current_time('mysql'),
        ], ['%d', '%s', '%d', '%s', '%s', '%s', '%s']);
        
        if (!$inserted) {
            wp_send_json_error(['message' => 'خطا در ثبت گزارش. لطفاً دوباره تلاش کنید']);
        }
        
        // Rate limit update
        set_transient($rate_key, $rate_count + 1, HOUR_IN_SECONDS);
        
        // اعلان به ادمین‌ها
        $this->notify_admins($reported_type, $reported_id, $reason);
        
        // آپدیت شمارنده pending
        delete_transient('novel_reports_pending_count');
        
        wp_send_json_success(['message' => 'گزارش شما ثبت شد و بررسی خواهد شد ✓']);
    }

    /**
     * بررسی آیا قبلاً گزارش داده (AJAX)
     */
    public function check_reported() {
        check_ajax_referer('novel_report_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_success(['reported' => false]);
        }
        
        $user_id       = get_current_user_id();
        $reported_type = sanitize_text_field($_POST['reported_type'] ?? '');
        $reported_id   = absint($_POST['reported_id'] ?? 0);
        
        if (!in_array($reported_type, $this->valid_types, true) || $reported_id <= 0) {
            wp_send_json_success(['reported' => false]);
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'novel_reports';
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table}
             WHERE reporter_id = %d AND reported_type = %s AND reported_id = %d",
            $user_id, $reported_type, $reported_id
        ));
        
        wp_send_json_success(['reported' => (bool) $exists]);
    }

    /**
     * بررسی وجود آیتم گزارش‌شده
     */
    private function validate_reported_item($type, $id) {
        global $wpdb;
        
        switch ($type) {
            case 'chapter':
                return (bool) get_post($id) && get_post_type($id) === 'chapter';
            case 'comment':
                return (bool) get_comment($id);
            case 'user':
                return (bool) get_user_by('id', $id);
            case 'novel':
                return (bool) get_post($id) && get_post_type($id) === 'novel';
            default:
                return false;
        }
    }

    /**
     * بررسی گزارش خود
     */
    private function is_self_report($type, $id, $user_id) {
        switch ($type) {
            case 'chapter':
            case 'novel':
                $post = get_post($id);
                return $post && (int) $post->post_author === $user_id;
            case 'comment':
                $comment = get_comment($id);
                return $comment && (int) $comment->user_id === $user_id;
            case 'user':
                return (int) $id === $user_id;
            default:
                return false;
        }
    }

    /**
     * اعلان به ادمین‌ها
     */
    private function notify_admins($type, $id, $reason) {
        $admins = get_users(['role' => 'administrator', 'fields' => 'ID']);
        
        $type_labels = [
            'chapter' => 'قسمت',
            'comment' => 'دیدگاه',
            'user'    => 'کاربر',
            'novel'   => 'رمان',
        ];
        
        $type_label = $type_labels[$type] ?? $type;
        $title = sprintf('گزارش جدید: %s #%d', $type_label, $id);
        $message = sprintf('یک %s جدید گزارش شده است. دلیل: %s', $type_label, $reason);
        
        if (class_exists('Novel_Notifications')) {
            $notif = Novel_Notifications::get_instance();
            foreach ($admins as $admin_id) {
                $notif->send_notification(
                    (int) $admin_id,
                    'system',
                    $title,
                    $message,
                    admin_url('admin.php?page=novel-reports')
                );
            }
        }
    }

    /**
     * تعداد گزارش‌های در انتظار
     */
    public static function get_pending_count() {
        $count = get_transient('novel_reports_pending_count');
        
        if (false === $count) {
            global $wpdb;
            $table = $wpdb->prefix . 'novel_reports';
            $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'pending'");
            set_transient('novel_reports_pending_count', $count, 5 * MINUTE_IN_SECONDS);
        }
        
        return (int) $count;
    }

    /**
     * گرفتن جزئیات آیتم گزارش‌شده
     */
    public static function get_reported_item_info($type, $id) {
        $info = ['title' => '', 'url' => '', 'excerpt' => ''];
        
        switch ($type) {
            case 'chapter':
                $post = get_post($id);
                if ($post) {
                    $info['title']   = $post->post_title;
                    $info['url']     = get_permalink($id);
                    $info['excerpt'] = mb_substr(wp_strip_all_tags($post->post_content), 0, 150);
                }
                break;
                
            case 'comment':
                $comment = get_comment($id);
                if ($comment) {
                    $info['title']   = sprintf('دیدگاه #%d', $id);
                    $info['url']     = get_comment_link($id);
                    $info['excerpt'] = mb_substr(wp_strip_all_tags($comment->comment_content), 0, 150);
                }
                break;
                
            case 'user':
                $user = get_user_by('id', $id);
                if ($user) {
                    $info['title'] = $user->display_name;
                    $info['url']   = get_author_posts_url($id);
                }
                break;
                
            case 'novel':
                $post = get_post($id);
                if ($post) {
                    $info['title']   = $post->post_title;
                    $info['url']     = get_permalink($id);
                    $info['excerpt'] = mb_substr(wp_strip_all_tags($post->post_content), 0, 150);
                }
                break;
        }
        
        return $info;
    }

    /**
     * رندر مودال گزارش در فوتر
     */
    public function render_report_modal() {
        if (!is_user_logged_in()) return;
        get_template_part('templates/reports/report-modal');
    }

    /**
     * ایجاد جدول دیتابیس
     */
    public static function create_table() {
        global $wpdb;
        $table   = $wpdb->prefix . 'novel_reports';
        $charset = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            reporter_id BIGINT UNSIGNED NOT NULL,
            reported_type VARCHAR(20) NOT NULL,
            reported_id BIGINT UNSIGNED NOT NULL,
            reason VARCHAR(50) NOT NULL,
            description TEXT,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            admin_note TEXT,
            reviewed_by BIGINT UNSIGNED DEFAULT NULL,
            reviewed_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_report (reporter_id, reported_type, reported_id),
            KEY idx_status (status),
            KEY idx_type (reported_type),
            KEY idx_created (created_at)
        ) {$charset};";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}