<?php
/**
 * Novel Polls System
 * 
 * سیستم نظرسنجی کامل با رأی‌گیری AJAX، تک/چندانتخابی،
 * شمارش معکوس، نتایج درصدی انیمیشنی، ویجت سایدبار
 * 
 * @package suspended developer
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

class Novel_Polls {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Shortcodes
        add_shortcode('novel_poll', [$this, 'render_single']);
        add_shortcode('novel_polls', [$this, 'render_list']);

        // AJAX
        add_action('wp_ajax_novel_vote_poll', [$this, 'ajax_vote']);
        add_action('wp_ajax_novel_load_poll_results', [$this, 'ajax_load_results']);
        add_action('wp_ajax_nopriv_novel_load_poll_results', [$this, 'ajax_load_results']);

        // Cron: auto-close expired polls
        add_action('novel_cron_close_polls', [$this, 'auto_close_expired']);
        if (!wp_next_scheduled('novel_cron_close_polls')) {
            wp_schedule_event(time(), 'hourly', 'novel_cron_close_polls');
        }

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // Widget
        add_action('widgets_init', [$this, 'register_widget']);
    }

    /**
     * ایجاد جداول دیتابیس
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = [];

        // جدول نظرسنجی‌ها
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}polls (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(500) NOT NULL,
            description TEXT NULL,
            poll_type ENUM('single','multiple') DEFAULT 'single',
            status ENUM('active','draft','closed') DEFAULT 'draft',
            novel_id BIGINT UNSIGNED NULL DEFAULT NULL,
            start_date DATETIME NULL DEFAULT NULL,
            end_date DATETIME NULL DEFAULT NULL,
            total_votes INT UNSIGNED DEFAULT 0,
            created_by BIGINT UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_novel (novel_id),
            INDEX idx_created (created_at)
        ) {$charset_collate};";

        // جدول گزینه‌ها
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}poll_options (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            poll_id BIGINT UNSIGNED NOT NULL,
            option_text VARCHAR(500) NOT NULL,
            vote_count INT UNSIGNED DEFAULT 0,
            sort_order INT UNSIGNED DEFAULT 0,
            INDEX idx_poll (poll_id)
        ) {$charset_collate};";

        // جدول رأی‌ها
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}poll_votes (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            poll_id BIGINT UNSIGNED NOT NULL,
            option_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            voted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_vote_single (poll_id, user_id, option_id),
            INDEX idx_poll_user (poll_id, user_id),
            INDEX idx_option (option_id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach ($sql as $query) {
            dbDelta($query);
        }
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        if ($this->should_load_assets()) {
            wp_enqueue_style(
                'novel-polls',
                get_template_directory_uri() . '/assets/css/polls.css',
                [],
                JEsuspended_DEVELOPER_VERSION
            );
            wp_enqueue_script(
                'novel-polls',
                get_template_directory_uri() . '/assets/js/polls.js',
                ['jquery'],
                JEsuspended_DEVELOPER_VERSION,
                true
            );
            wp_localize_script('novel-polls', 'NovelPolls', [
                'ajaxurl'    => admin_url('admin-ajax.php'),
                'nonce'      => wp_create_nonce('novel_poll_nonce'),
                'is_logged'  => is_user_logged_in(),
                'strings'    => [
                    'vote_success'    => 'رأی شما ثبت شد! ✅',
                    'already_voted'   => 'شما قبلاً رأی داده‌اید.',
                    'login_required'  => 'برای رأی دادن ابتدا وارد شوید.',
                    'select_option'   => 'لطفاً یک گزینه انتخاب کنید.',
                    'error'           => 'خطا در ثبت رأی. دوباره تلاش کنید.',
                    'poll_closed'     => 'این نظرسنجی بسته شده است.',
                    'day'             => 'روز',
                    'hour'            => 'ساعت',
                    'minute'          => 'دقیقه',
                    'remaining'       => 'مانده',
                    'votes'           => 'رأی',
                    'voted'           => 'نفر رأی داده‌اند',
                ],
            ]);
        }
    }

    /**
     * آیا باید assets لود شود
     */
    private function should_load_assets() {
        global $post;
        if (is_front_page()) return true;
        if (is_active_widget(false, false, 'novel_poll_widget')) return true;
        if ($post && has_shortcode($post->post_content, 'novel_poll')) return true;
        if ($post && has_shortcode($post->post_content, 'novel_polls')) return true;
        if (is_singular('novel') && $this->get_novel_poll(get_the_ID())) return true;
        return false;
    }

    /**
     * ثبت ویجت
     */
    public function register_widget() {
        register_widget('Novel_Poll_Widget');
    }

    // ═══════════════════════════════════════
    // CRUD Operations
    // ═══════════════════════════════════════

    /**
     * ساخت نظرسنجی جدید
     */
    public function create_poll($data) {
        global $wpdb;

        $poll_data = [
            'title'       => sanitize_text_field($data['title']),
            'description' => isset($data['description']) ? wp_kses_post($data['description']) : '',
            'poll_type'   => in_array($data['poll_type'], ['single', 'multiple']) ? $data['poll_type'] : 'single',
            'status'      => in_array($data['status'], ['active', 'draft', 'closed']) ? $data['status'] : 'draft',
            'novel_id'    => !empty($data['novel_id']) ? absint($data['novel_id']) : null,
            'start_date'  => !empty($data['start_date']) ? sanitize_text_field($data['start_date']) : null,
            'end_date'    => !empty($data['end_date']) ? sanitize_text_field($data['end_date']) : null,
            'created_by'  => get_current_user_id(),
        ];

        $wpdb->insert("{$wpdb->prefix}polls", $poll_data);
        $poll_id = $wpdb->insert_id;

        if ($poll_id && !empty($data['options'])) {
            foreach ($data['options'] as $index => $option_text) {
                $option_text = sanitize_text_field(trim($option_text));
                if (empty($option_text)) continue;

                $wpdb->insert("{$wpdb->prefix}poll_options", [
                    'poll_id'     => $poll_id,
                    'option_text' => $option_text,
                    'sort_order'  => $index,
                ]);
            }
        }

        return $poll_id;
    }

    /**
     * ویرایش نظرسنجی
     */
    public function update_poll($poll_id, $data) {
        global $wpdb;

        $update_data = [];
        $format = [];

        if (isset($data['title'])) {
            $update_data['title'] = sanitize_text_field($data['title']);
            $format[] = '%s';
        }
        if (isset($data['description'])) {
            $update_data['description'] = wp_kses_post($data['description']);
            $format[] = '%s';
        }
        if (isset($data['poll_type']) && in_array($data['poll_type'], ['single', 'multiple'])) {
            $update_data['poll_type'] = $data['poll_type'];
            $format[] = '%s';
        }
        if (isset($data['status']) && in_array($data['status'], ['active', 'draft', 'closed'])) {
            $update_data['status'] = $data['status'];
            $format[] = '%s';
        }
        if (array_key_exists('novel_id', $data)) {
            $update_data['novel_id'] = !empty($data['novel_id']) ? absint($data['novel_id']) : null;
            $format[] = $update_data['novel_id'] ? '%d' : null;
        }
        if (array_key_exists('start_date', $data)) {
            $update_data['start_date'] = !empty($data['start_date']) ? sanitize_text_field($data['start_date']) : null;
            $format[] = '%s';
        }
        if (array_key_exists('end_date', $data)) {
            $update_data['end_date'] = !empty($data['end_date']) ? sanitize_text_field($data['end_date']) : null;
            $format[] = '%s';
        }

        if (!empty($update_data)) {
            $wpdb->update("{$wpdb->prefix}polls", $update_data, ['id' => $poll_id], $format, ['%d']);
        }

        // بروزرسانی گزینه‌ها
        if (isset($data['options'])) {
            // حذف گزینه‌های قبلی فقط اگر هنوز رأیی ثبت نشده
            $has_votes = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}poll_votes WHERE poll_id = %d",
                $poll_id
            ));

            if (!$has_votes) {
                $wpdb->delete("{$wpdb->prefix}poll_options", ['poll_id' => $poll_id]);
                foreach ($data['options'] as $index => $option_text) {
                    $option_text = sanitize_text_field(trim($option_text));
                    if (empty($option_text)) continue;
                    $wpdb->insert("{$wpdb->prefix}poll_options", [
                        'poll_id'     => $poll_id,
                        'option_text' => $option_text,
                        'sort_order'  => $index,
                    ]);
                }
            }
        }

        return true;
    }

    /**
     * حذف نظرسنجی
     */
    public function delete_poll($poll_id) {
        global $wpdb;
        $poll_id = absint($poll_id);

        $wpdb->delete("{$wpdb->prefix}poll_votes", ['poll_id' => $poll_id]);
        $wpdb->delete("{$wpdb->prefix}poll_options", ['poll_id' => $poll_id]);
        $wpdb->delete("{$wpdb->prefix}polls", ['id' => $poll_id]);

        return true;
    }

    /**
     * دریافت نظرسنجی
     */
    public function get_poll($poll_id) {
        global $wpdb;

        $poll = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}polls WHERE id = %d",
            $poll_id
        ));

        if (!$poll) return null;

        $poll->options = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}poll_options WHERE poll_id = %d ORDER BY sort_order ASC",
            $poll_id
        ));

        // بررسی وضعیت واقعی (auto-close)
        $poll->effective_status = $this->get_effective_status($poll);

        return $poll;
    }

    /**
     * وضعیت واقعی نظرسنجی (بررسی تاریخ)
     */
    private function get_effective_status($poll) {
        if ($poll->status === 'closed' || $poll->status === 'draft') {
            return $poll->status;
        }

        $now = current_time('mysql');

        if ($poll->start_date && $now < $poll->start_date) {
            return 'upcoming';
        }
        if ($poll->end_date && $now > $poll->end_date) {
            return 'closed';
        }

        return 'active';
    }

    /**
     * نظرسنجی مرتبط با رمان
     */
    public function get_novel_poll($novel_id) {
        global $wpdb;

        $poll_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}polls 
             WHERE novel_id = %d AND status = 'active' 
             AND (end_date IS NULL OR end_date > %s)
             ORDER BY created_at DESC LIMIT 1",
            $novel_id,
            current_time('mysql')
        ));

        return $poll_id ? $this->get_poll($poll_id) : null;
    }

    /**
     * آخرین نظرسنجی فعال
     */
    public function get_latest_active_poll() {
        global $wpdb;

        $now = current_time('mysql');

        $poll_id = $wpdb->get_var(
            "SELECT id FROM {$wpdb->prefix}polls 
             WHERE status = 'active'
             AND (start_date IS NULL OR start_date <= '{$now}')
             AND (end_date IS NULL OR end_date > '{$now}')
             ORDER BY created_at DESC LIMIT 1"
        );

        return $poll_id ? $this->get_poll($poll_id) : null;
    }

    /**
     * لیست نظرسنجی‌ها
     */
    public function get_polls($args = []) {
        global $wpdb;

        $defaults = [
            'status'  => 'all',
            'page'    => 1,
            'per_page' => 10,
            'orderby' => 'created_at',
            'order'   => 'DESC',
        ];
        $args = wp_parse_args($args, $defaults);

        $where = "WHERE 1=1";
        $now = current_time('mysql');

        if ($args['status'] === 'active') {
            $where .= " AND p.status = 'active' AND (p.start_date IS NULL OR p.start_date <= '{$now}') AND (p.end_date IS NULL OR p.end_date > '{$now}')";
        } elseif ($args['status'] === 'closed') {
            $where .= " AND (p.status = 'closed' OR (p.end_date IS NOT NULL AND p.end_date <= '{$now}'))";
        } elseif ($args['status'] === 'draft') {
            $where .= " AND p.status = 'draft'";
        }

        $offset = ($args['page'] - 1) * $args['per_page'];
        $allowed_orderby = ['created_at', 'total_votes', 'title'];
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'created_at';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}polls p {$where}");

        $polls = $wpdb->get_results(
            "SELECT p.* FROM {$wpdb->prefix}polls p 
             {$where} 
             ORDER BY p.{$orderby} {$order} 
             LIMIT {$args['per_page']} OFFSET {$offset}"
        );

        foreach ($polls as &$poll) {
            $poll->options = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}poll_options WHERE poll_id = %d ORDER BY sort_order ASC",
                $poll->id
            ));
            $poll->effective_status = $this->get_effective_status($poll);
        }

        return [
            'polls'      => $polls,
            'total'      => (int)$total,
            'pages'      => ceil($total / $args['per_page']),
            'current'    => (int)$args['page'],
        ];
    }

    /**
     * آیا کاربر رأی داده
     */
    public function has_user_voted($poll_id, $user_id = null) {
        global $wpdb;

        if (!$user_id) $user_id = get_current_user_id();
        if (!$user_id) return false;

        return (bool)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}poll_votes WHERE poll_id = %d AND user_id = %d",
            $poll_id,
            $user_id
        ));
    }

    /**
     * گزینه‌های انتخاب‌شده کاربر
     */
    public function get_user_votes($poll_id, $user_id = null) {
        global $wpdb;

        if (!$user_id) $user_id = get_current_user_id();
        if (!$user_id) return [];

        return $wpdb->get_col($wpdb->prepare(
            "SELECT option_id FROM {$wpdb->prefix}poll_votes WHERE poll_id = %d AND user_id = %d",
            $poll_id,
            $user_id
        ));
    }

    /**
     * محاسبه زمان باقیمانده
     */
    public function get_remaining_time($poll) {
        if (empty($poll->end_date)) return null;

        $end = strtotime($poll->end_date);
        $now = current_time('timestamp');
        $diff = $end - $now;

        if ($diff <= 0) return ['expired' => true];

        return [
            'expired'  => false,
            'total_seconds' => $diff,
            'days'     => floor($diff / 86400),
            'hours'    => floor(($diff % 86400) / 3600),
            'minutes'  => floor(($diff % 3600) / 60),
            'seconds'  => $diff % 60,
            'timestamp' => $end,
        ];
    }

    // ═══════════════════════════════════════
    // AJAX Handlers
    // ═══════════════════════════════════════

    /**
     * AJAX: ثبت رأی
     */
    public function ajax_vote() {
        check_ajax_referer('novel_poll_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'برای رأی دادن ابتدا وارد شوید.']);
        }

        $user_id = get_current_user_id();

        // بررسی تأیید ایمیل
        if (class_exists('Novel_Auth') && !get_user_meta($user_id, 'novel_email_verified', true)) {
            wp_send_json_error(['message' => 'لطفاً ابتدا ایمیل خود را تأیید کنید.']);
        }

        $poll_id = absint($_POST['poll_id'] ?? 0);
        $option_ids = isset($_POST['option_ids']) ? array_map('absint', (array)$_POST['option_ids']) : [];

        if (!$poll_id || empty($option_ids)) {
            wp_send_json_error(['message' => 'لطفاً یک گزینه انتخاب کنید.']);
        }

        global $wpdb;

        // دریافت نظرسنجی
        $poll = $this->get_poll($poll_id);
        if (!$poll) {
            wp_send_json_error(['message' => 'نظرسنجی یافت نشد.']);
        }

        // بررسی وضعیت
        if ($poll->effective_status !== 'active') {
            wp_send_json_error(['message' => 'این نظرسنجی بسته شده است.']);
        }

        // بررسی رأی تکراری
        if ($this->has_user_voted($poll_id, $user_id)) {
            wp_send_json_error(['message' => 'شما قبلاً رأی داده‌اید.']);
        }

        // بررسی تک/چندانتخابی
        if ($poll->poll_type === 'single' && count($option_ids) > 1) {
            wp_send_json_error(['message' => 'فقط یک گزینه انتخاب کنید.']);
        }

        // بررسی معتبر بودن گزینه‌ها
        $valid_option_ids = wp_list_pluck($poll->options, 'id');
        foreach ($option_ids as $oid) {
            if (!in_array($oid, $valid_option_ids)) {
                wp_send_json_error(['message' => 'گزینه نامعتبر.']);
            }
        }

        // ثبت رأی
        foreach ($option_ids as $option_id) {
            $wpdb->insert("{$wpdb->prefix}poll_votes", [
                'poll_id'   => $poll_id,
                'option_id' => $option_id,
                'user_id'   => $user_id,
            ]);

            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}poll_options SET vote_count = vote_count + 1 WHERE id = %d",
                $option_id
            ));
        }

        // بروزرسانی total_votes (تعداد کاربرهای رأی‌داده)
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}poll_votes WHERE poll_id = %d",
            $poll_id
        ));
        $wpdb->update("{$wpdb->prefix}polls", ['total_votes' => $total], ['id' => $poll_id]);

        // آماده‌سازی نتایج
        $results = $this->get_results_data($poll_id);

        wp_send_json_success([
            'message' => 'رأی شما ثبت شد! ✅',
            'results' => $results,
        ]);
    }

    /**
     * AJAX: لود نتایج (برای کاربران رأی‌داده یا نظرسنجی بسته)
     */
    public function ajax_load_results() {
        $poll_id = absint($_POST['poll_id'] ?? $_GET['poll_id'] ?? 0);
        if (!$poll_id) {
            wp_send_json_error(['message' => 'نظرسنجی نامعتبر.']);
        }

        $results = $this->get_results_data($poll_id);
        wp_send_json_success(['results' => $results]);
    }

    /**
     * داده‌های نتایج نظرسنجی
     */
    private function get_results_data($poll_id) {
        global $wpdb;

        $poll = $this->get_poll($poll_id);
        if (!$poll) return null;

        $total_option_votes = 0;
        foreach ($poll->options as $opt) {
            $total_option_votes += (int)$opt->vote_count;
        }

        $user_votes = $this->get_user_votes($poll_id);

        $options = [];
        foreach ($poll->options as $opt) {
            $count = (int)$opt->vote_count;
            $percentage = $total_option_votes > 0 ? round(($count / $total_option_votes) * 100, 1) : 0;

            $options[] = [
                'id'         => (int)$opt->id,
                'text'       => $opt->option_text,
                'count'      => $count,
                'percentage' => $percentage,
                'is_user'    => in_array($opt->id, $user_votes),
            ];
        }

        // مرتب‌سازی بر اساس تعداد (بیشترین بالا)
        usort($options, function($a, $b) {
            return $b['count'] - $a['count'];
        });

        return [
            'poll_id'      => (int)$poll_id,
            'total_voters' => (int)$poll->total_votes,
            'options'      => $options,
        ];
    }

    /**
     * بستن خودکار نظرسنجی‌های منقضی
     */
    public function auto_close_expired() {
        global $wpdb;

        $now = current_time('mysql');

        $wpdb->query(
            "UPDATE {$wpdb->prefix}polls 
             SET status = 'closed' 
             WHERE status = 'active' 
             AND end_date IS NOT NULL 
             AND end_date <= '{$now}'"
        );
    }

    // ═══════════════════════════════════════
    // Render Methods
    // ═══════════════════════════════════════

    /**
     * شورتکد نظرسنجی تکی: [novel_poll id="5"]
     */
    public function render_single($atts) {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);

        $poll_id = absint($atts['id']);
        if (!$poll_id) return '';

        $poll = $this->get_poll($poll_id);
        if (!$poll) return '<p class="novel-poll-error">نظرسنجی یافت نشد.</p>';

        ob_start();
        $this->render_poll_template($poll);
        return ob_get_clean();
    }

    /**
     * شورتکد لیست نظرسنجی‌ها: [novel_polls]
     */
    public function render_list($atts) {
        $atts = shortcode_atts([
            'status'   => 'all',
            'per_page' => 10,
        ], $atts);

        $page = max(1, absint($_GET['poll_page'] ?? 1));

        $result = $this->get_polls([
            'status'   => $atts['status'],
            'page'     => $page,
            'per_page' => absint($atts['per_page']),
        ]);

        ob_start();
        include get_template_directory() . '/templates/polls/poll-list.php';
        return ob_get_clean();
    }

    /**
     * رندر قالب نظرسنجی
     */
    public function render_poll_template($poll, $compact = false) {
        $has_voted = is_user_logged_in() ? $this->has_user_voted($poll->id) : false;
        $user_votes = $has_voted ? $this->get_user_votes($poll->id) : [];
        $remaining = $this->get_remaining_time($poll);
        $show_results = $has_voted || $poll->effective_status === 'closed' || ($remaining && $remaining['expired']);

        if ($compact) {
            include get_template_directory() . '/templates/polls/poll-widget.php';
        } else {
            include get_template_directory() . '/templates/polls/poll-single.php';
        }
    }

    /**
     * رندر برای صفحه اصلی
     */
    public function render_homepage_section() {
        $poll = $this->get_latest_active_poll();
        if (!$poll) return;

        echo '<section class="novel-section novel-polls-section">';
        echo '<div class="novel-container">';
        echo '<div class="novel-section-header">';
        echo '<h2 class="novel-section-title"><span class="novel-section-icon">📊</span> نظرسنجی</h2>';
        echo '<a href="' . esc_url(home_url('/polls/')) . '" class="novel-section-link">همه نظرسنجی‌ها ←</a>';
        echo '</div>';

        $this->render_poll_template($poll, true);

        echo '</div>';
        echo '</section>';
    }

    /**
     * لیست نظرسنجی‌ها برای ادمین (WP_List_Table data)
     */
    public function get_admin_polls($args = []) {
        global $wpdb;

        $defaults = [
            'status'  => 'all',
            'search'  => '',
            'page'    => 1,
            'per_page' => 20,
            'orderby' => 'created_at',
            'order'   => 'DESC',
        ];
        $args = wp_parse_args($args, $defaults);

        $where = "WHERE 1=1";

        if ($args['status'] !== 'all') {
            $where .= $wpdb->prepare(" AND p.status = %s", $args['status']);
        }

        if (!empty($args['search'])) {
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $where .= $wpdb->prepare(" AND p.title LIKE %s", $search);
        }

        $offset = ($args['page'] - 1) * $args['per_page'];
        $allowed_orderby = ['created_at', 'total_votes', 'title', 'status'];
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'created_at';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}polls p {$where}");

        $polls = $wpdb->get_results(
            "SELECT p.*, u.display_name as creator_name 
             FROM {$wpdb->prefix}polls p 
             LEFT JOIN {$wpdb->users} u ON p.created_by = u.ID
             {$where} 
             ORDER BY p.{$orderby} {$order} 
             LIMIT {$args['per_page']} OFFSET {$offset}"
        );

        foreach ($polls as &$poll) {
            $poll->option_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}poll_options WHERE poll_id = %d",
                $poll->id
            ));
            $poll->effective_status = $this->get_effective_status($poll);
        }

        return [
            'polls'   => $polls,
            'total'   => (int)$total,
            'pages'   => ceil($total / $args['per_page']),
            'current' => (int)$args['page'],
        ];
    }

    /**
     * آمار کلی نظرسنجی‌ها
     */
    public function get_stats() {
        global $wpdb;

        return [
            'total'       => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}polls"),
            'active'      => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}polls WHERE status = 'active'"),
            'closed'      => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}polls WHERE status = 'closed'"),
            'total_votes' => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}poll_votes"),
        ];
    }
}

// ═══════════════════════════════════════
// Widget Class
// ═══════════════════════════════════════

class Novel_Poll_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'novel_poll_widget',
            '📊 نظرسنجی ناول',
            ['description' => 'نمایش آخرین نظرسنجی فعال']
        );
    }

    public function widget($args, $instance) {
        $polls = Novel_Polls::get_instance();
        $poll = $polls->get_latest_active_poll();

        if (!$poll) return;

        echo $args['before_widget'];

        if (!empty($instance['title'])) {
            echo $args['before_title'] . esc_html($instance['title']) . $args['after_title'];
        }

        $polls->render_poll_template($poll, true);

        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '📊 نظرسنجی';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">عنوان:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = sanitize_text_field($new_instance['title'] ?? '');
        return $instance;
    }
}