<?php
/**
 * Novel Achievements System
 * 
 * سیستم دستاوردها، مدال‌ها، سطح‌بندی کاربر
 * بررسی خودکار شرایط + اعطای مدال + popup تبریک
 * 
 * @package suspended developer
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

class Novel_Achievements {

    private static $instance = null;
    private $definitions = [];

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->register_definitions();

        // Hooks برای بررسی خودکار
        add_action('comment_post', [$this, 'on_comment_post'], 20, 1);
        add_action('transition_post_status', [$this, 'on_post_publish'], 20, 3);
        add_action('novel_chapter_read', [$this, 'on_chapter_read'], 20, 2);
        add_action('novel_follow_added', [$this, 'on_follow_added'], 20, 2);
        add_action('novel_bookmark_added', [$this, 'on_bookmark_added'], 20, 2);
        add_action('novel_coin_purchase', [$this, 'on_coin_purchase'], 20, 2);
        add_action('novel_subscription_activated', [$this, 'on_subscription'], 20, 1);
        add_action('novel_vote_chapter', [$this, 'on_vote'], 20, 2);
        add_action('novel_comment_liked', [$this, 'on_comment_liked'], 20, 2);
        add_action('novel_quiz_completed', [$this, 'on_quiz_completed'], 20, 2);

        // Cron: بررسی مدال‌های زمانی
        add_action('novel_cron_check_achievements', [$this, 'check_time_based']);
        if (!wp_next_scheduled('novel_cron_check_achievements')) {
            wp_schedule_event(time(), 'daily', 'novel_cron_check_achievements');
        }

        // AJAX
        add_action('wp_ajax_novel_get_pending_achievements', [$this, 'ajax_get_pending']);
        add_action('wp_ajax_novel_dismiss_achievement', [$this, 'ajax_dismiss']);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * ایجاد جداول
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = [];

        // جدول تعاریف دستاوردها
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}achievements (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(100) NOT NULL UNIQUE,
            title VARCHAR(300) NOT NULL,
            description TEXT NULL,
            icon VARCHAR(50) NOT NULL DEFAULT '🏆',
            category ENUM('comment','reading','writing','social','special','quiz') DEFAULT 'special',
            condition_type VARCHAR(50) NOT NULL,
            condition_value INT UNSIGNED DEFAULT 1,
            points INT UNSIGNED DEFAULT 10,
            is_active TINYINT DEFAULT 1,
            sort_order INT UNSIGNED DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_category (category),
            INDEX idx_active (is_active),
            INDEX idx_sort (sort_order)
        ) {$charset_collate};";

        // جدول دستاوردهای کاربران
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}user_achievements (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            achievement_id BIGINT UNSIGNED NOT NULL,
            earned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            notified TINYINT DEFAULT 0,
            UNIQUE KEY unique_user_achievement (user_id, achievement_id),
            INDEX idx_user (user_id),
            INDEX idx_notified (user_id, notified)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach ($sql as $query) {
            dbDelta($query);
        }

        // Seed default achievements
        self::seed_defaults();
    }

    /**
     * ثبت تعاریف پیش‌فرض
     */
    private static function seed_defaults() {
        global $wpdb;

        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}achievements");
        if ($count > 0) return;

        $defaults = [
            // دیدگاه
            ['first_comment', 'اولین سخن', 'اولین دیدگاه خود را ثبت کنید', '💬', 'comment', 'comment_count', 1, 5, 1],
            ['commentator_10', 'گوینده فعال', '۱۰ دیدگاه ثبت کنید', '🗣', 'comment', 'comment_count', 10, 10, 2],
            ['commentator_50', 'صدای داستان', '۵۰ دیدگاه ثبت کنید', '📢', 'comment', 'comment_count', 50, 25, 3],
            ['commentator_100', 'منتقد حرفه‌ای', '۱۰۰ دیدگاه ثبت کنید', '🎙', 'comment', 'comment_count', 100, 50, 4],
            ['commentator_500', 'افسانه دیدگاه', '۵۰۰ دیدگاه ثبت کنید', '👑', 'comment', 'comment_count', 500, 100, 5],
            ['first_review', 'اولین نقد', 'اولین نقد خود را بنویسید', '📝', 'comment', 'review_count', 1, 10, 6],
            ['reviewer_10', 'منتقد ادبی', '۱۰ نقد بنویسید', '🔍', 'comment', 'review_count', 10, 30, 7],
            ['first_theory', 'نظریه‌پرداز', 'اولین تئوری خود را بنویسید', '🧠', 'comment', 'theory_count', 1, 10, 8],

            // لایک دریافتی
            ['liked_10', 'محبوب', '۱۰ لایک بر دیدگاه‌ها دریافت کنید', '👍', 'social', 'received_likes', 10, 15, 20],
            ['liked_100', 'ستاره دیدگاه', '۱۰۰ لایک دریافت کنید', '⭐', 'social', 'received_likes', 100, 40, 21],
            ['liked_1000', 'افسانه‌ای', '۱٬۰۰۰ لایک دریافت کنید', '🌟', 'social', 'received_likes', 1000, 100, 22],

            // نویسندگی
            ['first_novel', 'نویسنده تازه‌کار', 'اولین رمان خود را منتشر کنید', '✍️', 'writing', 'novel_count', 1, 20, 30],
            ['author_5', 'نویسنده حرفه‌ای', '۵ رمان منتشر کنید', '📚', 'writing', 'novel_count', 5, 50, 31],
            ['chapters_50', 'قلم‌زن', '۵۰ قسمت منتشر کنید', '✒️', 'writing', 'chapter_count', 50, 30, 32],
            ['chapters_200', 'استاد قلم', '۲۰۰ قسمت منتشر کنید', '🖋', 'writing', 'chapter_count', 200, 80, 33],

            // مطالعه
            ['read_10', 'خواننده', '۱۰ قسمت بخوانید', '📖', 'reading', 'read_count', 10, 5, 40],
            ['read_100', 'کتاب‌خوان', '۱۰۰ قسمت بخوانید', '📕', 'reading', 'read_count', 100, 20, 41],
            ['read_500', 'کرم کتاب', '۵۰۰ قسمت بخوانید', '🐛', 'reading', 'read_count', 500, 50, 42],
            ['read_1000', 'اسطوره مطالعه', '۱٬۰۰۰ قسمت بخوانید', '📚', 'reading', 'read_count', 1000, 100, 43],
            ['night_reader', 'خواننده شبانه', 'بین ۱ تا ۵ صبح مطالعه کنید', '🦉', 'reading', 'night_read', 1, 15, 44],
            ['speed_reader', 'خواننده سریع', '۱۰ قسمت در یک روز بخوانید', '⚡', 'reading', 'daily_reads', 10, 20, 45],
            ['diverse_reader', 'خواننده همه‌چیز', 'از ۵ ژانر مختلف بخوانید', '🌈', 'reading', 'genre_diversity', 5, 25, 46],
            ['marathon_reader', 'ماراتن‌خوان', '۳۰ روز متوالی مطالعه کنید', '🏃', 'reading', 'consecutive_days', 30, 80, 47],

            // اجتماعی
            ['member_30', 'یک ماهه', '۳۰ روز عضو باشید', '📅', 'social', 'member_days', 30, 5, 50],
            ['member_365', 'یک ساله', '۳۶۵ روز عضو باشید', '🎂', 'social', 'member_days', 365, 30, 51],
            ['member_730', 'وفادار', '۲ سال عضو باشید', '💎', 'social', 'member_days', 730, 60, 52],
            ['first_follow', 'اولین دنبال‌کننده', '۱ فالوور داشته باشید', '❤', 'social', 'follower_count', 1, 10, 53],
            ['followers_50', 'محبوب خوانندگان', '۵۰ فالوور داشته باشید', '🌹', 'social', 'follower_count', 50, 40, 54],
            ['followers_200', 'ستاره ناول', '۲۰۰ فالوور داشته باشید', '⭐', 'social', 'follower_count', 200, 80, 55],
            ['bookworm', 'جمع‌آوری‌کننده', '۲۰ رمان در کتابخانه داشته باشید', '📋', 'social', 'library_count', 20, 15, 56],

            // ویژه
            ['vip_member', 'عضو ویژه', 'اشتراک ویژه تهیه کنید', '💎', 'special', 'subscription', 1, 20, 60],
            ['first_purchase', 'اولین خرید', 'اولین خرید با سکه', '🪙', 'special', 'purchase_count', 1, 10, 61],

            // مسابقه
            ['quiz_first', 'اولین مسابقه', 'در ۱ مسابقه شرکت کنید', '🎮', 'quiz', 'quiz_count', 1, 5, 70],
            ['quiz_champion', 'چمپیون', 'رتبه ۱ مسابقه شوید', '🏆', 'quiz', 'quiz_wins', 1, 50, 71],
            ['quiz_streak_7', '۷ روز متوالی', '۷ مسابقه روزانه پشت سر هم', '🔥', 'quiz', 'quiz_streak', 7, 30, 72],
            ['quiz_streak_30', 'ماراتن مسابقه', '۳۰ مسابقه روزانه متوالی', '💪', 'quiz', 'quiz_streak', 30, 80, 73],
            ['quiz_perfect', 'بی‌نقص', '۱۰۰٪ در مسابقه', '💯', 'quiz', 'quiz_perfect', 1, 40, 74],
            ['quiz_master', 'استاد مسابقه', '۵۰ مسابقه شرکت', '🧙', 'quiz', 'quiz_count', 50, 60, 75],
        ];

        foreach ($defaults as $d) {
            $wpdb->insert("{$wpdb->prefix}achievements", [
                'slug'            => $d[0],
                'title'           => $d[1],
                'description'     => $d[2],
                'icon'            => $d[3],
                'category'        => $d[4],
                'condition_type'  => $d[5],
                'condition_value' => $d[6],
                'points'          => $d[7],
                'sort_order'      => $d[8],
                'is_active'       => 1,
            ]);
        }
    }

    /**
     * لود تعاریف
     */
    private function register_definitions() {
        global $wpdb;

        $cached = wp_cache_get('novel_achievement_defs', 'novel');
        if ($cached !== false) {
            $this->definitions = $cached;
            return;
        }

        $results = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}achievements WHERE is_active = 1 ORDER BY sort_order ASC"
        );

        $this->definitions = [];
        foreach ($results as $row) {
            $this->definitions[$row->slug] = $row;
        }

        wp_cache_set('novel_achievement_defs', $this->definitions, 'novel', 3600);
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        if (!is_user_logged_in()) return;

        wp_enqueue_style(
            'novel-achievements',
            get_template_directory_uri() . '/assets/css/achievements.css',
            [],
            JEsuspended_DEVELOPER_VERSION
        );

        wp_enqueue_script(
            'novel-achievements',
            get_template_directory_uri() . '/assets/js/achievements.js',
            ['jquery'],
            JEsuspended_DEVELOPER_VERSION,
            true
        );

        wp_localize_script('novel-achievements', 'NovelAchievements', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('novel_achievement_nonce'),
        ]);
    }

    // ═══════════════════════════════════════
    // آمار کاربر
    // ═══════════════════════════════════════

    /**
     * دریافت آمار کاربر برای بررسی دستاوردها
     */
    public function get_user_stats($user_id) {
        global $wpdb;

        $cache_key = 'novel_user_stats_' . $user_id;
        $cached = wp_cache_get($cache_key, 'novel');
        if ($cached !== false) return $cached;

        $stats = [];

        // دیدگاه
        $stats['comment_count'] = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->comments} WHERE user_id = %d AND comment_approved = '1'",
            $user_id
        ));

        // نقد و تئوری
        $stats['review_count'] = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->comments} 
             WHERE user_id = %d AND comment_approved = '1' 
             AND comment_type = 'review'",
            $user_id
        ));

        $stats['theory_count'] = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->comments} 
             WHERE user_id = %d AND comment_approved = '1' 
             AND comment_type = 'theory'",
            $user_id
        ));

        // لایک دریافتی
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}comment_votes'")) {
            $stats['received_likes'] = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}comment_votes cv
                 INNER JOIN {$wpdb->comments} c ON cv.comment_id = c.comment_ID
                 WHERE c.user_id = %d AND cv.vote_type = 'like'",
                $user_id
            ));
        } else {
            $stats['received_likes'] = 0;
        }

        // رمان
        $stats['novel_count'] = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
             WHERE post_author = %d AND post_type = 'novel' AND post_status = 'publish'",
            $user_id
        ));

        // قسمت
        $stats['chapter_count'] = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
             WHERE post_author = %d AND post_type = 'chapter' AND post_status = 'publish'",
            $user_id
        ));

        // مطالعه
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}reading_history'")) {
            $stats['read_count'] = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT chapter_id) FROM {$wpdb->prefix}reading_history WHERE user_id = %d",
                $user_id
            ));
        } else {
            $stats['read_count'] = 0;
        }

        // عضویت
        $registered = get_userdata($user_id)->user_registered;
        $stats['member_days'] = (int)floor((time() - strtotime($registered)) / 86400);

        // فالوور
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}follows'")) {
            $stats['follower_count'] = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}follows 
                 WHERE followed_id = %d AND follow_type = 'user'",
                $user_id
            ));
        } else {
            $stats['follower_count'] = 0;
        }

        // کتابخانه
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}bookmarks'")) {
            $stats['library_count'] = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}bookmarks WHERE user_id = %d",
                $user_id
            ));
        } else {
            $stats['library_count'] = 0;
        }

        // خرید
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}coin_transactions'")) {
            $stats['purchase_count'] = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}coin_transactions 
                 WHERE user_id = %d AND type = 'spend'",
                $user_id
            ));
        } else {
            $stats['purchase_count'] = 0;
        }

        // اشتراک
        $stats['subscription'] = function_exists('rcp_is_active') && rcp_is_active($user_id) ? 1 : 0;

        // مطالعه شبانه
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}reading_history'")) {
            $stats['night_read'] = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}reading_history 
                 WHERE user_id = %d AND HOUR(read_at) BETWEEN 1 AND 4",
                $user_id
            ));
        } else {
            $stats['night_read'] = 0;
        }

        // مطالعه روزانه
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}reading_history'")) {
            $stats['daily_reads'] = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT MAX(cnt) FROM (
                    SELECT COUNT(*) as cnt FROM {$wpdb->prefix}reading_history 
                    WHERE user_id = %d GROUP BY DATE(read_at)
                ) sub",
                $user_id
            ));
        } else {
            $stats['daily_reads'] = 0;
        }

        // تنوع ژانر
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}reading_history'")) {
            $stats['genre_diversity'] = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT tt.term_id) 
                 FROM {$wpdb->prefix}reading_history rh
                 INNER JOIN {$wpdb->posts} ch ON rh.chapter_id = ch.ID
                 INNER JOIN {$wpdb->postmeta} pm ON ch.ID = pm.post_id AND pm.meta_key = '_novel_id'
                 INNER JOIN {$wpdb->term_relationships} tr ON pm.meta_value = tr.object_id
                 INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'genre'
                 WHERE rh.user_id = %d",
                $user_id
            ));
        } else {
            $stats['genre_diversity'] = 0;
        }

        // روزهای متوالی
        $stats['consecutive_days'] = $this->get_consecutive_reading_days($user_id);

        // مسابقه
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}quiz_attempts'")) {
            $stats['quiz_count'] = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}quiz_attempts 
                 WHERE user_id = %d AND status = 'completed'",
                $user_id
            ));
            $stats['quiz_wins'] = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM (
                    SELECT qa.quiz_id, qa.score,
                    RANK() OVER (PARTITION BY qa.quiz_id ORDER BY qa.score DESC, qa.time_spent ASC) as rnk
                    FROM {$wpdb->prefix}quiz_attempts qa WHERE qa.status = 'completed'
                ) ranked WHERE ranked.rnk = 1 
                AND ranked.quiz_id IN (SELECT quiz_id FROM {$wpdb->prefix}quiz_attempts WHERE user_id = %d AND status = 'completed')
                AND ranked.score = (SELECT score FROM {$wpdb->prefix}quiz_attempts WHERE user_id = %d AND status = 'completed' AND quiz_id = ranked.quiz_id)",
                $user_id, $user_id
            ));
            $stats['quiz_perfect'] = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}quiz_attempts 
                 WHERE user_id = %d AND status = 'completed' AND score = total_points AND total_points > 0",
                $user_id
            ));
            $stats['quiz_streak'] = 0; // محاسبه جداگانه
        } else {
            $stats['quiz_count'] = 0;
            $stats['quiz_wins'] = 0;
            $stats['quiz_perfect'] = 0;
            $stats['quiz_streak'] = 0;
        }

        wp_cache_set($cache_key, $stats, 'novel', 300);
        return $stats;
    }

    /**
     * محاسبه روزهای متوالی مطالعه
     */
    private function get_consecutive_reading_days($user_id) {
        global $wpdb;

        if (!$wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}reading_history'")) return 0;

        $dates = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT DATE(read_at) as d FROM {$wpdb->prefix}reading_history 
             WHERE user_id = %d ORDER BY d DESC LIMIT 365",
            $user_id
        ));

        if (empty($dates)) return 0;

        $streak = 1;
        $today = date('Y-m-d');

        // اگر امروز نخوانده، از دیروز شروع کن
        if ($dates[0] !== $today && $dates[0] !== date('Y-m-d', strtotime('-1 day'))) {
            return 0;
        }

        for ($i = 1; $i < count($dates); $i++) {
            $expected = date('Y-m-d', strtotime($dates[$i - 1] . ' -1 day'));
            if ($dates[$i] === $expected) {
                $streak++;
            } else {
                break;
            }
        }

        return $streak;
    }

    // ═══════════════════════════════════════
    // بررسی و اعطای دستاوردها
    // ═══════════════════════════════════════

    /**
     * بررسی و اعطای دستاوردها
     */
    public function check_and_award($user_id, $category = null) {
        if (!$user_id) return [];

        $stats = $this->get_user_stats($user_id);
        $earned = $this->get_user_earned_slugs($user_id);
        $new_achievements = [];

        foreach ($this->definitions as $slug => $achievement) {
            if ($category && $achievement->category !== $category) continue;
            if (in_array($slug, $earned)) continue;

            $type = $achievement->condition_type;
            $value = (int)$achievement->condition_value;

            if (isset($stats[$type]) && $stats[$type] >= $value) {
                $this->award($user_id, $achievement->id);
                $new_achievements[] = $achievement;
            }
        }

        // پاک کردن کش
        if (!empty($new_achievements)) {
            wp_cache_delete('novel_user_stats_' . $user_id, 'novel');
            wp_cache_delete('novel_user_achievements_' . $user_id, 'novel');
        }

        return $new_achievements;
    }

    /**
     * اعطای مدال
     */
    public function award($user_id, $achievement_id) {
        global $wpdb;

        $result = $wpdb->insert("{$wpdb->prefix}user_achievements", [
            'user_id'        => $user_id,
            'achievement_id' => $achievement_id,
            'notified'       => 0,
        ]);

        if ($result) {
            $achievement = $this->get_achievement_by_id($achievement_id);
            if ($achievement && class_exists('Novel_Notifications')) {
                Novel_Notifications::get_instance()->send(
                    $user_id,
                    'achievement',
                    sprintf('🏆 دستاورد جدید: %s %s!', $achievement->icon, $achievement->title),
                    '',
                    ''
                );
            }
        }

        return $result;
    }

    /**
     * اعطای دستی مدال (ادمین)
     */
    public function manual_award($user_id, $achievement_id) {
        return $this->award($user_id, $achievement_id);
    }

    /**
     * دستاورد بر اساس ID
     */
    private function get_achievement_by_id($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}achievements WHERE id = %d",
            $id
        ));
    }

    /**
     * لیست slug های کسب‌شده
     */
    private function get_user_earned_slugs($user_id) {
        global $wpdb;

        return $wpdb->get_col($wpdb->prepare(
            "SELECT a.slug FROM {$wpdb->prefix}user_achievements ua
             INNER JOIN {$wpdb->prefix}achievements a ON ua.achievement_id = a.id
             WHERE ua.user_id = %d",
            $user_id
        ));
    }

    // ═══════════════════════════════════════
    // Hook Handlers
    // ═══════════════════════════════════════

    public function on_comment_post($comment_id) {
        $comment = get_comment($comment_id);
        if ($comment && $comment->user_id) {
            wp_cache_delete('novel_user_stats_' . $comment->user_id, 'novel');
            $this->check_and_award($comment->user_id, 'comment');
        }
    }

    public function on_post_publish($new_status, $old_status, $post) {
        if ($new_status !== 'publish' || $old_status === 'publish') return;
        if (!in_array($post->post_type, ['novel', 'chapter'])) return;

        wp_cache_delete('novel_user_stats_' . $post->post_author, 'novel');
        $this->check_and_award($post->post_author, 'writing');
    }

    public function on_chapter_read($user_id, $chapter_id) {
        wp_cache_delete('novel_user_stats_' . $user_id, 'novel');
        $this->check_and_award($user_id, 'reading');
    }

    public function on_follow_added($follower_id, $followed_id) {
        wp_cache_delete('novel_user_stats_' . $followed_id, 'novel');
        $this->check_and_award($followed_id, 'social');
    }

    public function on_bookmark_added($user_id, $novel_id) {
        wp_cache_delete('novel_user_stats_' . $user_id, 'novel');
        $this->check_and_award($user_id, 'social');
    }

    public function on_coin_purchase($user_id, $amount) {
        wp_cache_delete('novel_user_stats_' . $user_id, 'novel');
        $this->check_and_award($user_id, 'special');
    }

    public function on_subscription($user_id) {
        wp_cache_delete('novel_user_stats_' . $user_id, 'novel');
        $this->check_and_award($user_id, 'special');
    }

    public function on_vote($user_id, $chapter_id) {
        // No specific achievement for voting yet
    }

    public function on_comment_liked($commenter_user_id, $liker_user_id) {
        wp_cache_delete('novel_user_stats_' . $commenter_user_id, 'novel');
        $this->check_and_award($commenter_user_id, 'social');
    }

    public function on_quiz_completed($user_id, $attempt_data) {
        wp_cache_delete('novel_user_stats_' . $user_id, 'novel');
        $this->check_and_award($user_id, 'quiz');
    }

    /**
     * بررسی مدال‌های زمانی (cron)
     */
    public function check_time_based() {
        global $wpdb;

        $users = $wpdb->get_col("SELECT ID FROM {$wpdb->users} LIMIT 500");
        foreach ($users as $user_id) {
            wp_cache_delete('novel_user_stats_' . $user_id, 'novel');
            $this->check_and_award($user_id, 'social'); // member_days
        }
    }

    // ═══════════════════════════════════════
    // Query Methods
    // ═══════════════════════════════════════

    /**
     * دستاوردهای کاربر
     */
    public function get_user_achievements($user_id) {
        global $wpdb;

        $cache_key = 'novel_user_achievements_' . $user_id;
        $cached = wp_cache_get($cache_key, 'novel');
        if ($cached !== false) return $cached;

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, ua.earned_at FROM {$wpdb->prefix}achievements a
             INNER JOIN {$wpdb->prefix}user_achievements ua ON a.id = ua.achievement_id
             WHERE ua.user_id = %d AND a.is_active = 1
             ORDER BY ua.earned_at DESC",
            $user_id
        ));

        wp_cache_set($cache_key, $results, 'novel', 600);
        return $results;
    }

    /**
     * تمام دستاوردها با وضعیت کاربر
     */
    public function get_all_with_user_status($user_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, ua.earned_at,
                    IF(ua.id IS NOT NULL, 1, 0) as is_earned
             FROM {$wpdb->prefix}achievements a
             LEFT JOIN {$wpdb->prefix}user_achievements ua 
                ON a.id = ua.achievement_id AND ua.user_id = %d
             WHERE a.is_active = 1
             ORDER BY a.category, a.sort_order ASC",
            $user_id
        ));
    }

    /**
     * آخرین مدال کاربر (برای نمایش کنار نام)
     */
    public function get_user_badge($user_id) {
        global $wpdb;

        $badge = $wpdb->get_row($wpdb->prepare(
            "SELECT a.icon, a.title FROM {$wpdb->prefix}achievements a
             INNER JOIN {$wpdb->prefix}user_achievements ua ON a.id = ua.achievement_id
             WHERE ua.user_id = %d AND a.is_active = 1
             ORDER BY a.points DESC, ua.earned_at DESC LIMIT 1",
            $user_id
        ));

        return $badge;
    }

    /**
     * تعداد دستاوردهای کاربر
     */
    public function get_user_achievement_count($user_id) {
        global $wpdb;

        return (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}user_achievements WHERE user_id = %d",
            $user_id
        ));
    }

    /**
     * مجموع امتیاز دستاوردها
     */
    public function get_user_total_points($user_id) {
        global $wpdb;

        return (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(a.points), 0) FROM {$wpdb->prefix}achievements a
             INNER JOIN {$wpdb->prefix}user_achievements ua ON a.id = ua.achievement_id
             WHERE ua.user_id = %d",
            $user_id
        ));
    }

    /**
     * دستاوردهای اخیراً کسب‌نشده (popup)
     */
    public function get_pending_notifications($user_id) {
        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT a.* FROM {$wpdb->prefix}achievements a
             INNER JOIN {$wpdb->prefix}user_achievements ua ON a.id = ua.achievement_id
             WHERE ua.user_id = %d AND ua.notified = 0
             ORDER BY ua.earned_at DESC LIMIT 5",
            $user_id
        ));

        return $results;
    }

    // ═══════════════════════════════════════
    // AJAX
    // ═══════════════════════════════════════

    /**
     * AJAX: دریافت مدال‌های pending (popup)
     */
    public function ajax_get_pending() {
        check_ajax_referer('novel_achievement_nonce', 'nonce');

        if (!is_user_logged_in()) wp_send_json_error();

        $user_id = get_current_user_id();
        $pending = $this->get_pending_notifications($user_id);

        if (empty($pending)) {
            wp_send_json_success(['achievements' => []]);
            return;
        }

        $data = [];
        foreach ($pending as $a) {
            $data[] = [
                'id'          => (int)$a->id,
                'title'       => $a->title,
                'description' => $a->description,
                'icon'        => $a->icon,
                'points'      => (int)$a->points,
            ];
        }

        wp_send_json_success(['achievements' => $data]);
    }

    /**
     * AJAX: dismiss (mark as notified)
     */
    public function ajax_dismiss() {
        check_ajax_referer('novel_achievement_nonce', 'nonce');

        if (!is_user_logged_in()) wp_send_json_error();

        $user_id = get_current_user_id();
        $achievement_id = absint($_POST['achievement_id'] ?? 0);

        global $wpdb;

        if ($achievement_id) {
            $wpdb->update(
                "{$wpdb->prefix}user_achievements",
                ['notified' => 1],
                ['user_id' => $user_id, 'achievement_id' => $achievement_id]
            );
        } else {
            // dismiss all
            $wpdb->update(
                "{$wpdb->prefix}user_achievements",
                ['notified' => 1],
                ['user_id' => $user_id, 'notified' => 0]
            );
        }

        wp_send_json_success();
    }

    /**
     * Progress محاسبه
     */
    public function get_progress($user_id, $achievement) {
        $stats = $this->get_user_stats($user_id);
        $type = $achievement->condition_type;
        $target = (int)$achievement->condition_value;
        $current = isset($stats[$type]) ? (int)$stats[$type] : 0;
        $percentage = $target > 0 ? min(100, round(($current / $target) * 100)) : 0;

        return [
            'current'    => $current,
            'target'     => $target,
            'percentage' => $percentage,
        ];
    }

    /**
     * دسته‌بندی‌ها
     */
    public function get_categories() {
        return [
            'comment' => ['label' => '💬 دیدگاه', 'color' => '#3b82f6'],
            'reading' => ['label' => '📖 مطالعه', 'color' => '#10b981'],
            'writing' => ['label' => '✍️ نویسندگی', 'color' => '#f59e0b'],
            'social'  => ['label' => '❤ اجتماعی', 'color' => '#ec4899'],
            'special' => ['label' => '💎 ویژه', 'color' => '#8b5cf6'],
            'quiz'    => ['label' => '🎮 مسابقه', 'color' => '#ef4444'],
        ];
    }
}