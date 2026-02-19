<?php
/**
 * Novel Reading Challenges
 * 
 * سیستم چالش مطالعه با تعریف ادمین، شرکت کاربر،
 * ردیابی پیشرفت، جایزه خودکار
 * 
 * @package suspended developer
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

class Novel_Challenges {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_shortcode('novel_challenges', [$this, 'render_list']);

        add_action('wp_ajax_novel_join_challenge', [$this, 'ajax_join']);
        add_action('wp_ajax_novel_challenge_progress', [$this, 'ajax_progress']);

        // هوک‌ها برای بروزرسانی پیشرفت
        add_action('novel_chapter_read', [$this, 'on_chapter_read'], 25, 2);

        // Cron: بررسی تکمیل و انقضا
        add_action('novel_cron_check_challenges', [$this, 'cron_check']);
        if (!wp_next_scheduled('novel_cron_check_challenges')) {
            wp_schedule_event(time(), 'twicedaily', 'novel_cron_check_challenges');
        }

        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * جداول
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = [];

        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}reading_challenges (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(500) NOT NULL,
            description TEXT NULL,
            challenge_type ENUM('chapter_count','novel_count','genre_specific','diverse') DEFAULT 'chapter_count',
            target_value INT UNSIGNED NOT NULL DEFAULT 10,
            genre_id BIGINT UNSIGNED NULL,
            start_date DATETIME NOT NULL,
            end_date DATETIME NOT NULL,
            reward_coins INT UNSIGNED DEFAULT 0,
            reward_badge VARCHAR(100) NULL,
            status ENUM('active','upcoming','ended','draft') DEFAULT 'draft',
            icon VARCHAR(50) DEFAULT '📚',
            max_participants INT UNSIGNED DEFAULT 0,
            created_by BIGINT UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_dates (start_date, end_date)
        ) {$charset_collate};";

        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}challenge_participants (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            challenge_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            progress INT UNSIGNED DEFAULT 0,
            completed_at DATETIME NULL,
            joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            rewarded TINYINT DEFAULT 0,
            UNIQUE KEY unique_participation (challenge_id, user_id),
            INDEX idx_challenge (challenge_id),
            INDEX idx_user (user_id),
            INDEX idx_completed (challenge_id, completed_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach ($sql as $query) {
            dbDelta($query);
        }
    }

    /**
     * Assets
     */
    public function enqueue_assets() {
        // فقط در صفحاتی که لازم است
        global $post;
        if (!$post || !has_shortcode($post->post_content, 'novel_challenges')) return;

        wp_enqueue_style('novel-achievements'); // استفاده مشترک
        wp_enqueue_script('novel-achievements');
    }

    // ═══════════════════════════════════════
    // CRUD
    // ═══════════════════════════════════════

    /**
     * ساخت چالش
     */
    public function create_challenge($data) {
        global $wpdb;

        return $wpdb->insert("{$wpdb->prefix}reading_challenges", [
            'title'          => sanitize_text_field($data['title']),
            'description'    => wp_kses_post($data['description'] ?? ''),
            'challenge_type' => in_array($data['challenge_type'], ['chapter_count', 'novel_count', 'genre_specific', 'diverse'])
                                ? $data['challenge_type'] : 'chapter_count',
            'target_value'   => absint($data['target_value']),
            'genre_id'       => !empty($data['genre_id']) ? absint($data['genre_id']) : null,
            'start_date'     => sanitize_text_field($data['start_date']),
            'end_date'       => sanitize_text_field($data['end_date']),
            'reward_coins'   => absint($data['reward_coins'] ?? 0),
            'reward_badge'   => sanitize_text_field($data['reward_badge'] ?? ''),
            'status'         => in_array($data['status'], ['active', 'upcoming', 'ended', 'draft'])
                                ? $data['status'] : 'draft',
            'icon'           => sanitize_text_field($data['icon'] ?? '📚'),
            'max_participants' => absint($data['max_participants'] ?? 0),
            'created_by'     => get_current_user_id(),
        ]);
    }

    /**
     * دریافت چالش
     */
    public function get_challenge($id) {
        global $wpdb;

        $challenge = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}reading_challenges WHERE id = %d",
            $id
        ));

        if ($challenge) {
            $challenge->participant_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}challenge_participants WHERE challenge_id = %d",
                $id
            ));
            $challenge->completed_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}challenge_participants 
                 WHERE challenge_id = %d AND completed_at IS NOT NULL",
                $id
            ));
            $challenge->effective_status = $this->get_effective_status($challenge);
        }

        return $challenge;
    }

    /**
     * وضعیت واقعی
     */
    private function get_effective_status($challenge) {
        if ($challenge->status === 'draft') return 'draft';

        $now = current_time('mysql');
        if ($now < $challenge->start_date) return 'upcoming';
        if ($now > $challenge->end_date) return 'ended';
        return 'active';
    }

    /**
     * لیست چالش‌ها
     */
    public function get_challenges($status = 'active', $limit = 10) {
        global $wpdb;

        $now = current_time('mysql');
        $where = "WHERE c.status != 'draft'";

        switch ($status) {
            case 'active':
                $where .= " AND c.start_date <= '{$now}' AND c.end_date > '{$now}'";
                break;
            case 'upcoming':
                $where .= " AND c.start_date > '{$now}'";
                break;
            case 'ended':
                $where .= " AND c.end_date <= '{$now}'";
                break;
        }

        $challenges = $wpdb->get_results(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM {$wpdb->prefix}challenge_participants WHERE challenge_id = c.id) as participant_count,
                    (SELECT COUNT(*) FROM {$wpdb->prefix}challenge_participants WHERE challenge_id = c.id AND completed_at IS NOT NULL) as completed_count
             FROM {$wpdb->prefix}reading_challenges c
             {$where}
             ORDER BY c.start_date DESC
             LIMIT {$limit}"
        );

        foreach ($challenges as &$ch) {
            $ch->effective_status = $this->get_effective_status($ch);
        }

        return $challenges;
    }

    // ═══════════════════════════════════════
    // شرکت و پیشرفت
    // ═══════════════════════════════════════

    /**
     * آیا شرکت کرده
     */
    public function has_joined($challenge_id, $user_id = null) {
        global $wpdb;
        if (!$user_id) $user_id = get_current_user_id();
        if (!$user_id) return false;

        return (bool)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}challenge_participants 
             WHERE challenge_id = %d AND user_id = %d",
            $challenge_id, $user_id
        ));
    }

    /**
     * پیشرفت کاربر
     */
    public function get_user_progress($challenge_id, $user_id = null) {
        global $wpdb;
        if (!$user_id) $user_id = get_current_user_id();
        if (!$user_id) return null;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}challenge_participants
             WHERE challenge_id = %d AND user_id = %d",
            $challenge_id, $user_id
        ));
    }

    /**
     * محاسبه پیشرفت واقعی
     */
    public function calculate_progress($challenge, $user_id) {
        global $wpdb;

        if (!$wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}reading_history'")) return 0;

        $participant = $this->get_user_progress($challenge->id, $user_id);
        if (!$participant) return 0;

        $start = $participant->joined_at;
        $end = $challenge->end_date;

        switch ($challenge->challenge_type) {
            case 'chapter_count':
                return (int)$wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(DISTINCT chapter_id) FROM {$wpdb->prefix}reading_history 
                     WHERE user_id = %d AND read_at BETWEEN %s AND %s",
                    $user_id, $start, $end
                ));

            case 'novel_count':
                // تعداد رمان‌هایی که حداقل آخرین قسمت خوانده شده
                return (int)$wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(DISTINCT pm.meta_value) FROM {$wpdb->prefix}reading_history rh
                     INNER JOIN {$wpdb->postmeta} pm ON rh.chapter_id = pm.post_id AND pm.meta_key = '_novel_id'
                     WHERE rh.user_id = %d AND rh.read_at BETWEEN %s AND %s",
                    $user_id, $start, $end
                ));

            case 'genre_specific':
                if (!$challenge->genre_id) return 0;
                return (int)$wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(DISTINCT rh.chapter_id) FROM {$wpdb->prefix}reading_history rh
                     INNER JOIN {$wpdb->postmeta} pm ON rh.chapter_id = pm.post_id AND pm.meta_key = '_novel_id'
                     INNER JOIN {$wpdb->term_relationships} tr ON pm.meta_value = tr.object_id
                     INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                     WHERE rh.user_id = %d AND tt.term_id = %d AND rh.read_at BETWEEN %s AND %s",
                    $user_id, $challenge->genre_id, $start, $end
                ));

            case 'diverse':
                return (int)$wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(DISTINCT tt.term_id) FROM {$wpdb->prefix}reading_history rh
                     INNER JOIN {$wpdb->postmeta} pm ON rh.chapter_id = pm.post_id AND pm.meta_key = '_novel_id'
                     INNER JOIN {$wpdb->term_relationships} tr ON pm.meta_value = tr.object_id
                     INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'genre'
                     WHERE rh.user_id = %d AND rh.read_at BETWEEN %s AND %s",
                    $user_id, $start, $end
                ));
        }

        return 0;
    }

    /**
     * لیدربورد
     */
    public function get_leaderboard($challenge_id, $limit = 20) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT cp.*, u.display_name,
                    (SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = cp.user_id AND meta_key = 'novel_avatar' LIMIT 1) as avatar_id
             FROM {$wpdb->prefix}challenge_participants cp
             INNER JOIN {$wpdb->users} u ON cp.user_id = u.ID
             WHERE cp.challenge_id = %d
             ORDER BY cp.completed_at IS NOT NULL DESC, cp.completed_at ASC, cp.progress DESC
             LIMIT %d",
            $challenge_id, $limit
        ));
    }

    // ═══════════════════════════════════════
    // Hooks
    // ═══════════════════════════════════════

    /**
     * هنگام خواندن قسمت
     */
    public function on_chapter_read($user_id, $chapter_id) {
        global $wpdb;

        $now = current_time('mysql');

        // چالش‌های فعالی که کاربر شرکت کرده
        $participations = $wpdb->get_results($wpdb->prepare(
            "SELECT cp.*, rc.* FROM {$wpdb->prefix}challenge_participants cp
             INNER JOIN {$wpdb->prefix}reading_challenges rc ON cp.challenge_id = rc.id
             WHERE cp.user_id = %d AND cp.completed_at IS NULL
             AND rc.start_date <= %s AND rc.end_date > %s",
            $user_id, $now, $now
        ));

        foreach ($participations as $p) {
            $progress = $this->calculate_progress($p, $user_id);

            $wpdb->update(
                "{$wpdb->prefix}challenge_participants",
                ['progress' => $progress],
                ['id' => $p->id]
            );

            // بررسی تکمیل
            if ($progress >= (int)$p->target_value && !$p->completed_at) {
                $this->complete_challenge($p->challenge_id, $user_id);
            }
        }
    }

    /**
     * تکمیل چالش
     */
    private function complete_challenge($challenge_id, $user_id) {
        global $wpdb;

        $challenge = $this->get_challenge($challenge_id);
        if (!$challenge) return;

        // ثبت تکمیل
        $wpdb->update(
            "{$wpdb->prefix}challenge_participants",
            ['completed_at' => current_time('mysql')],
            ['challenge_id' => $challenge_id, 'user_id' => $user_id]
        );

        // اعطای سکه
        if ($challenge->reward_coins > 0 && class_exists('Novel_Coins')) {
            Novel_Coins::get_instance()->add_coins(
                $user_id,
                $challenge->reward_coins,
                'challenge_reward',
                sprintf('جایزه چالش: %s', $challenge->title)
            );
        }

        // اعلان
        if (class_exists('Novel_Notifications')) {
            Novel_Notifications::get_instance()->send(
                $user_id,
                'challenge',
                sprintf('🎉 تبریک! چالش «%s» را تکمیل کردید! %s',
                    $challenge->title,
                    $challenge->reward_coins ? '+' . $challenge->reward_coins . ' سکه 🪙' : ''
                ),
                '',
                ''
            );
        }
    }

    /**
     * AJAX: شرکت در چالش
     */
    public function ajax_join() {
        check_ajax_referer('novel_achievement_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'ابتدا وارد شوید.']);
        }

        $user_id = get_current_user_id();
        $challenge_id = absint($_POST['challenge_id'] ?? 0);

        if (!$challenge_id) {
            wp_send_json_error(['message' => 'چالش نامعتبر.']);
        }

        $challenge = $this->get_challenge($challenge_id);
        if (!$challenge || $challenge->effective_status !== 'active') {
            wp_send_json_error(['message' => 'این چالش فعال نیست.']);
        }

        if ($this->has_joined($challenge_id, $user_id)) {
            wp_send_json_error(['message' => 'شما قبلاً شرکت کرده‌اید.']);
        }

        if ($challenge->max_participants > 0 && $challenge->participant_count >= $challenge->max_participants) {
            wp_send_json_error(['message' => 'ظرفیت چالش پر شده.']);
        }

        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}challenge_participants", [
            'challenge_id' => $challenge_id,
            'user_id'      => $user_id,
        ]);

        wp_send_json_success(['message' => '✅ شما در چالش شرکت کردید! موفق باشید!']);
    }

    /**
     * Cron: بررسی‌ها
     */
    public function cron_check() {
        global $wpdb;
        $now = current_time('mysql');

        // بروزرسانی وضعیت چالش‌های منقضی
        $wpdb->query(
            "UPDATE {$wpdb->prefix}reading_challenges 
             SET status = 'ended' 
             WHERE status = 'active' AND end_date <= '{$now}'"
        );

        // فعال‌سازی چالش‌های آینده
        $wpdb->query(
            "UPDATE {$wpdb->prefix}reading_challenges 
             SET status = 'active' 
             WHERE status = 'upcoming' AND start_date <= '{$now}' AND end_date > '{$now}'"
        );
    }

    // ═══════════════════════════════════════
    // Render
    // ═══════════════════════════════════════

    /**
     * شورتکد لیست چالش‌ها
     */
    public function render_list($atts) {
        $atts = shortcode_atts(['status' => 'active'], $atts);

        $challenges = $this->get_challenges($atts['status'], 20);

        ob_start();
        include get_template_directory() . '/templates/challenges/challenge-list.php';
        return ob_get_clean();
    }
}