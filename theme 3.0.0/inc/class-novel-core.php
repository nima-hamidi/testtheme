<?php
/**
 * فایل: inc/class-novel-core.php
 * توضیح: کلاس مرکزی قالب - ساخت جداول DB، activation/deactivation hooks، migration
 * نسخه: 2.0.0
 * وابستگی: هیچ (اولین فایل لود شده)
 */

if (!defined('ABSPATH')) exit;

class Novel_Core {

    /**
     * نمونه Singleton
     */
    private static $instance = null;

    /**
     * نسخه دیتابیس
     */
    const DB_VERSION = '2.0.0';

    /**
     * لیست صفحات پیش‌فرض
     */
    private static $default_pages = [
        'login'           => ['title' => 'ورود', 'slug' => 'login'],
        'register'        => ['title' => 'ثبت‌نام', 'slug' => 'register'],
        'forgot_password' => ['title' => 'فراموشی رمز عبور', 'slug' => 'forgot-password'],
        'reset_password'  => ['title' => 'بازنشانی رمز عبور', 'slug' => 'reset-password'],
        'verify_email'    => ['title' => 'تأیید ایمیل', 'slug' => 'verify-email'],
        'dashboard'       => ['title' => 'داشبورد', 'slug' => 'dashboard'],
        'library'         => ['title' => 'کتابخانه', 'slug' => 'library'],
        'ranking'         => ['title' => 'رتبه‌بندی', 'slug' => 'ranking'],
        'authors'         => ['title' => 'نویسندگان', 'slug' => 'authors'],
        'search'          => ['title' => 'جستجو', 'slug' => 'search'],
        'genres'          => ['title' => 'ژانرها', 'slug' => 'genres'],
    ];

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // بررسی migration هنگام لود
        add_action('init', [$this, 'check_db_version'], 0);

        // ثبت hooks فعال‌سازی
        $theme = wp_get_theme();
        add_action('after_switch_theme', [$this, 'activate']);

        // Cron events
        add_action('novel_daily_cron', [$this, 'daily_cron']);
        add_action('novel_hourly_cron', [$this, 'hourly_cron']);
    }

    /**
     * بررسی نسخه DB و اجرای migration
     */
    public function check_db_version() {
        $current = get_option('novel_db_version', '0');
        if (version_compare($current, self::DB_VERSION, '<')) {
            $this->run_migrations($current, self::DB_VERSION);
            update_option('novel_db_version', self::DB_VERSION);
        }
    }

    /**
     * هنگام فعال‌سازی قالب
     */
    public function activate() {
        novel_log('فعال‌سازی قالب آغاز شد', 'info');

        // ۱. ساخت جداول
        $this->create_tables();

        /*فاز 7*/
        // Reports table
        if (class_exists('Novel_Reports')) {
            Novel_Reports::create_table();
        }

        /*فاز 8*/
        // Search log table
        if (class_exists('Novel_Search')) {
            Novel_Search::create_table();
        }

        /*فاز 9*/
        // Library & History tables
        if (class_exists('Novel_Bookmarks')) {
            Novel_Bookmarks::create_tables();
        }

        /*فاز 10*/
        // Views table
        if (class_exists('Novel_Rankings')) {
            Novel_Rankings::create_tables();
        }

        /*فاز 11*/
        // Coins tables
        if (class_exists('Novel_Coins')) {
            Novel_Coins::create_tables();
        }
        
        // ۲. ساخت صفحات پیش‌فرض
        $this->create_default_pages();

        // ۳. تنظیمات پیش‌فرض
        $this->set_default_options();

        // ۴. Seed ژانرها
        $this->seed_genres();

        // ۵. Seed وضعیت‌ها
        $this->seed_statuses();

        // ۶. Seed تگ‌ها
        $this->seed_tags();

        // ۷. زمان‌بند cron
        $this->schedule_cron();

        // ۸. flush rewrite
        flush_rewrite_rules();

        novel_log('فعال‌سازی قالب با موفقیت انجام شد', 'info');
    }

    /**
     * ساخت تمام جداول دیتابیس (۲۳ جدول)
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $tables = [];

        // ═══ جدول ۱: رأی دیدگاه ═══
        $tables[] = "CREATE TABLE {$wpdb->prefix}comment_votes (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            comment_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            vote TINYINT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_vote (comment_id, user_id),
            INDEX idx_comment (comment_id)
        ) {$charset_collate};";

        // ═══ جدول ۲: ری‌اکشن اموجی دیدگاه ═══
        $tables[] = "CREATE TABLE {$wpdb->prefix}comment_reactions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            comment_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            reaction VARCHAR(20) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_reaction (comment_id, user_id),
            INDEX idx_comment_reaction (comment_id, reaction)
        ) {$charset_collate};";

        // ═══ جدول ۳: رأی مفید/غیرمفید نقد ═══
        $tables[] = "CREATE TABLE {$wpdb->prefix}review_helpfulness (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            comment_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            helpful TINYINT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_helpful (comment_id, user_id),
            INDEX idx_comment (comment_id)
        ) {$charset_collate};";

        // ═══ جدول ۴: رأی لایک/دیسلایک قسمت ═══
        $tables[] = "CREATE TABLE {$wpdb->prefix}chapter_votes (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            chapter_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            vote TINYINT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_vote (chapter_id, user_id),
            INDEX idx_chapter (chapter_id)
        ) {$charset_collate};";

        // ═══ جدول ۵: گزارش‌ها ═══
        $tables[] = "CREATE TABLE {$wpdb->prefix}reports (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            reporter_id BIGINT UNSIGNED NOT NULL,
            reported_type ENUM('chapter','comment','user','novel') NOT NULL,
            reported_id BIGINT UNSIGNED NOT NULL,
            reason VARCHAR(50) NOT NULL,
            description TEXT NULL,
            status ENUM('pending','reviewed','resolved','rejected') DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            reviewed_at DATETIME NULL,
            reviewed_by BIGINT UNSIGNED NULL,
            admin_note TEXT NULL,
            INDEX idx_status (status),
            INDEX idx_type_id (reported_type, reported_id),
            UNIQUE KEY unique_report (reporter_id, reported_type, reported_id)
        ) {$charset_collate};";

        // ═══ جدول ۶: اعلان‌ها ═══
        $tables[] = "CREATE TABLE {$wpdb->prefix}notifications (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(300) NOT NULL,
            message TEXT NULL,
            link VARCHAR(500) NULL,
            image_url VARCHAR(500) NULL,
            is_read TINYINT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_read (user_id, is_read),
            INDEX idx_created (created_at)
        ) {$charset_collate};";

        // ═══ جدول ۷: فالو کاربر ═══
        $tables[] = "CREATE TABLE {$wpdb->prefix}user_follows (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            follower_id BIGINT UNSIGNED NOT NULL,
            following_id BIGINT UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_follow (follower_id, following_id),
            INDEX idx_following (following_id),
            INDEX idx_follower (follower_id)
        ) {$charset_collate};";

        // ═══ جدول ۸: دنبال کردن رمان ═══
        $tables[] = "CREATE TABLE {$wpdb->prefix}novel_follows (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            novel_id BIGINT UNSIGNED NOT NULL,
            notify_new_chapter TINYINT DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_follow (user_id, novel_id),
            INDEX idx_novel (novel_id)
        ) {$charset_collate};";

        // ═══ جدول ۹: کتابخانه شخصی ═══
        $tables[] = "CREATE TABLE {$wpdb->prefix}user_library (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            novel_id BIGINT UNSIGNED NOT NULL,
            list_type ENUM('reading','plan_to_read','completed','dropped','on_hold') NOT NULL,
            last_chapter_id BIGINT UNSIGNED DEFAULT 0,
            progress_percent DECIMAL(5,2) DEFAULT 0,
            added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_entry (user_id, novel_id),
            INDEX idx_user_list (user_id, list_type)
        ) {$charset_collate};";

        // ═══ جدول ۱۰: تاریخچه مطالعه ═══
        $tables[] = "CREATE TABLE {$wpdb->prefix}reading_history (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            novel_id BIGINT UNSIGNED NOT NULL,
            chapter_id BIGINT UNSIGNED NOT NULL,
            scroll_position FLOAT DEFAULT 0,
            read_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_read (user_id, chapter_id),
            INDEX idx_user_novel (user_id, novel_id),
            INDEX idx_read_at (read_at)
        ) {$charset_collate};";

        // ═══ جدول ۱۱: سکه‌ها ═══
        $tables[] = "CREATE TABLE {$wpdb->prefix}user_coins (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            amount INT NOT NULL,
            balance_after INT NOT NULL,
            type ENUM('purchase','subscription_bonus','spend','refund','admin_grant','expired') NOT NULL,
            description VARCHAR(300) NULL,
            related_id BIGINT UNSIGNED NULL,
            expires_at DATETIME NULL,
            is_expired TINYINT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_expires (expires_at, is_expired),
            INDEX idx_type (type)
        ) {$charset_collate};";

        // ═══ جدول ۱۲: خرید قسمت ═══
        $tables[] = "CREATE TABLE {$wpdb->prefix}chapter_purchases (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            chapter_id BIGINT UNSIGNED NOT NULL,
            novel_id BIGINT UNSIGNED NOT NULL,
            coins_spent INT UNSIGNED NOT NULL,
            purchased_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_purchase (user_id, chapter_id),
            INDEX idx_novel (novel_id),
            INDEX idx_user (user_id)
        ) {$charset_collate};";

        // ═══ جدول ۱۳: درآمد نویسنده ═══
        $tables[] = "CREATE TABLE {$wpdb->prefix}author_earnings (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            author_id BIGINT UNSIGNED NOT NULL,
            novel_id BIGINT UNSIGNED NOT NULL,
            chapter_id BIGINT UNSIGNED NOT NULL,
            buyer_id BIGINT UNSIGNED NOT NULL,
            coins_earned INT UNSIGNED NOT NULL,
            real_value DECIMAL(12,0) DEFAULT 0,
            commission_rate DECIMAL(3,2) DEFAULT 0.70,
            status ENUM('pending','paid','cancelled') DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_author (author_id),
            INDEX idx_status (status),
            INDEX idx_novel (novel_id)
        ) {$charset_collate};";

        // ═══ جدول ۱۴: واریزی‌های نویسنده ═══
        $tables[] = "CREATE TABLE {$wpdb->prefix}author_payouts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            author_id BIGINT UNSIGNED NOT NULL,
            amount DECIMAL(12,0) NOT NULL,
            payment_method VARCHAR(50) NOT NULL,
            payment_details TEXT NULL,
            transaction_id VARCHAR(100) NULL,
            status ENUM('pending','processing','completed','failed') DEFAULT 'pending',
            requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            processed_at DATETIME NULL,
            processed_by BIGINT UNSIGNED NULL,
            note TEXT NULL,
            INDEX idx_author (author_id),
            INDEX idx_status (status)
        ) {$charset_collate};";

        // ═══ جدول ۱۵: نظرسنجی ═══
        $tables[] = "CREATE TABLE {$wpdb->prefix}polls (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(300) NOT NULL,
            description TEXT NULL,
            type ENUM('single','multiple') DEFAULT 'single',
            status ENUM('active','closed','draft') DEFAULT 'active',
            start_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            end_date DATETIME NULL,
            created_by BIGINT UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) {$charset_collate};";

        $tables[] = "CREATE TABLE {$wpdb->prefix}poll_options (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            poll_id BIGINT UNSIGNED NOT NULL,
            option_text VARCHAR(300) NOT NULL,
            votes_count INT UNSIGNED DEFAULT 0,
            INDEX idx_poll (poll_id)
        ) {$charset_collate};";

        $tables[] = "CREATE TABLE {$wpdb->prefix}poll_votes (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            poll_id BIGINT UNSIGNED NOT NULL,
            option_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_vote (poll_id, user_id)
        ) {$charset_collate};";

        // ═══ جدول ۱۶: دستاوردها ═══
        $tables[] = "CREATE TABLE {$wpdb->prefix}user_achievements (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            achievement_slug VARCHAR(50) NOT NULL,
            earned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_achievement (user_id, achievement_slug),
            INDEX idx_user (user_id)
        ) {$charset_collate};";

        // ═══ جدول ۱۷: نظرات درباره نویسنده ═══
        $tables[] = "CREATE TABLE {$wpdb->prefix}author_reviews (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            author_id BIGINT UNSIGNED NOT NULL,
            reviewer_id BIGINT UNSIGNED NOT NULL,
            content TEXT NOT NULL,
            rating TINYINT UNSIGNED DEFAULT 0,
            parent_id BIGINT UNSIGNED DEFAULT 0,
            likes_count INT UNSIGNED DEFAULT 0,
            dislikes_count INT UNSIGNED DEFAULT 0,
            status VARCHAR(20) DEFAULT 'approved',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_author (author_id),
            INDEX idx_parent (parent_id)
        ) {$charset_collate};";

        // ═══ جدول ۱۸: بازدید ═══
        $tables[] = "CREATE TABLE {$wpdb->prefix}novel_views (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED DEFAULT 0,
            ip_address VARCHAR(45) NULL,
            view_date DATE NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_view (post_id, user_id, ip_address, view_date),
            INDEX idx_post_date (post_id, view_date),
            INDEX idx_date (view_date)
        ) {$charset_collate};";

        // ═══ جدول ۱۹: مسابقات ═══
        $tables[] = "CREATE TABLE {$wpdb->prefix}quizzes (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(300) NOT NULL,
            description TEXT NULL,
            type ENUM('daily','weekly','custom') DEFAULT 'custom',
            novel_id BIGINT UNSIGNED NULL,
            status ENUM('draft','active','closed') DEFAULT 'draft',
            time_limit INT UNSIGNED DEFAULT 300,
            max_attempts INT UNSIGNED DEFAULT 1,
            prize_1st INT UNSIGNED DEFAULT 0,
            prize_2nd INT UNSIGNED DEFAULT 0,
            prize_3rd INT UNSIGNED DEFAULT 0,
            prize_participation INT UNSIGNED DEFAULT 0,
            start_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            end_date DATETIME NULL,
            created_by BIGINT UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_type (type),
            INDEX idx_dates (start_date, end_date)
        ) {$charset_collate};";

        // ═══ جدول ۲۰: سوالات مسابقه ═══
        $tables[] = "CREATE TABLE {$wpdb->prefix}quiz_questions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            quiz_id BIGINT UNSIGNED NOT NULL,
            question_text TEXT NOT NULL,
            option_a VARCHAR(500) NOT NULL,
            option_b VARCHAR(500) NOT NULL,
            option_c VARCHAR(500) NULL,
            option_d VARCHAR(500) NULL,
            correct_option CHAR(1) NOT NULL,
            points INT UNSIGNED DEFAULT 10,
            sort_order INT UNSIGNED DEFAULT 0,
            INDEX idx_quiz (quiz_id)
        ) {$charset_collate};";

        // ═══ جدول ۲۱: تلاش‌های مسابقه ═══
        $tables[] = "CREATE TABLE {$wpdb->prefix}quiz_attempts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            quiz_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            score INT UNSIGNED DEFAULT 0,
            total_points INT UNSIGNED DEFAULT 0,
            time_spent INT UNSIGNED DEFAULT 0,
            completed TINYINT DEFAULT 0,
            started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME NULL,
            INDEX idx_quiz_user (quiz_id, user_id),
            INDEX idx_score (quiz_id, score DESC)
        ) {$charset_collate};";

        // ═══ جدول ۲۲: پاسخ‌های مسابقه ═══
        $tables[] = "CREATE TABLE {$wpdb->prefix}quiz_answers (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            attempt_id BIGINT UNSIGNED NOT NULL,
            question_id BIGINT UNSIGNED NOT NULL,
            selected_option CHAR(1) NULL,
            is_correct TINYINT DEFAULT 0,
            answered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_attempt (attempt_id)
        ) {$charset_collate};";

        // ═══ جدول ۲۳: بنر نویسنده ═══
        $tables[] = "CREATE TABLE {$wpdb->prefix}author_banners (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            author_id BIGINT UNSIGNED NOT NULL,
            novel_id BIGINT UNSIGNED NOT NULL,
            chapter_id BIGINT UNSIGNED NULL,
            banner_image VARCHAR(500) NULL,
            title VARCHAR(300) NOT NULL,
            description TEXT NULL,
            cta_text VARCHAR(100) DEFAULT 'بخوانید',
            position ENUM('before_chapter','after_chapter','both') DEFAULT 'before_chapter',
            status ENUM('pending','active','expired','rejected') DEFAULT 'pending',
            impressions INT UNSIGNED DEFAULT 0,
            clicks INT UNSIGNED DEFAULT 0,
            coins_spent INT UNSIGNED DEFAULT 0,
            start_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            end_date DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_author (author_id),
            INDEX idx_status (status),
            INDEX idx_dates (start_date, end_date),
            INDEX idx_position (position, status)
        ) {$charset_collate};";

        /*فاز8*/



        // اجرای تمام جداول
        foreach ($tables as $sql) {
            dbDelta($sql);
        }

        novel_log('تمام جداول دیتابیس ساخته شدند', 'info');
    }

    /**
     * ساخت صفحات پیش‌فرض
     */
    private function create_default_pages() {
        foreach (self::$default_pages as $key => $page) {
            $option_key = 'novel_page_' . $key;
            $existing_id = get_option($option_key);

            // بررسی وجود صفحه
            if ($existing_id && get_post_status($existing_id) !== false) {
                continue;
            }

            $page_id = wp_insert_post([
                'post_title'   => $page['title'],
                'post_name'    => $page['slug'],
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_content' => '',
                'meta_input'   => [
                    '_novel_system_page' => $key,
                ],
            ]);

            if (!is_wp_error($page_id)) {
                update_option($option_key, $page_id);
                novel_log("صفحه «{$page['title']}» ساخته شد", 'info', ['page_id' => $page_id]);
            }
        }
    }

    /**
     * تنظیمات پیش‌فرض
     */
    private function set_default_options() {
        $defaults = [
            // عمومی
            'novel_site_description'      => 'بهترین سایت خواندن رمان آنلاین فارسی',
            'novel_rules_page'            => '',
            'novel_comment_rules_page'    => '',
            'novel_comment_encourage'     => 'نظر شما ارزشمند است! 💬',
            'novel_comment_warning'       => 'لطفاً از بیان اسپویلر بدون تگ اسپویلر خودداری کنید.',
            'novel_min_comment_chars'     => 10,
            'novel_max_comment_chars'     => 1000,
            'novel_min_review_words'      => 200,
            'novel_min_theory_words'      => 250,
            'novel_comment_edit_time'     => 15,
            'novel_login_attempts'        => 5,
            'novel_login_lockout'         => 15,
            'novel_bad_words'             => '',
            'novel_allow_user_author'     => true,
            'novel_author_commission'     => 70,
            'novel_min_payout'            => 500000,
            'novel_coin_expiry_days'      => 0,
            'novel_notif_interval'        => 60,
            'novel_maintenance_mode'      => false,
            'novel_maintenance_message'   => 'در حال به‌روزرسانی هستیم! به‌زودی برمی‌گردیم.',
            'novel_maintenance_eta'       => '',

            // مسابقه
            'novel_quiz_daily_auto'       => false,
            'novel_quiz_daily_time'       => '00:00',
            'novel_quiz_prize_1st'        => 50,
            'novel_quiz_prize_2nd'        => 30,
            'novel_quiz_prize_3rd'        => 20,
            'novel_quiz_prize_participate' => 5,

            // بنر نویسنده
            'novel_banner_enabled'        => true,
            'novel_banner_duration'       => 30,
            'novel_banner_max_active'     => 3,
            'novel_banner_position'       => 'both',

            // شبکه‌های اجتماعی
            'novel_social_telegram'       => '',
            'novel_social_instagram'      => '',
            'novel_social_twitter'        => '',
        ];

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }

        // تنظیمات ماژول‌ها - همه فعال به صورت پیش‌فرض
        $modules = [
            'auth', 'avatars', 'comments', 'ratings', 'notifications', 'reports',
            'search', 'bookmarks', 'rankings', 'authors', 'subscriptions', 'coins',
            'polls', 'achievements', 'seo', 'follow', 'stickers', 'volumes',
            'quiz', 'author_banners',
            // ماژول‌های فرعی
            'comment_likes', 'comment_reactions', 'spoiler', 'theory', 'review',
            'review_helpfulness', 'report_system', 'pin_comment', 'mention_user',
            'edit_comment', 'bad_word_filter', 'comment_level', 'sticker_gif',
            'star_rating', 'chapter_votes', 'notification_system', 'follow_author',
            'follow_novel', 'library', 'reading_history', 'coin_system',
            'user_authoring', 'poll_system', 'achievement_system', 'blog_posts',
            'announcement_banner', 'advanced_search', 'advanced_reader',
            'social_share', 'author_page', 'ranking_system', 'similar_novels',
            'content_tags', 'custom_seo', 'dark_mode',
        ];

        foreach ($modules as $module) {
            $key = 'novel_module_' . $module;
            if (get_option($key) === false) {
                add_option($key, '1');
            }
        }

        novel_log('تنظیمات پیش‌فرض ست شدند', 'info');
    }

    /**
     * Seed ژانرها
     */
    private function seed_genres() {
        if (term_exists('اکشن', 'genre')) {
            return; // قبلاً seed شده
        }

        $genres = [
            'اکشن', 'ماجراجویی', 'کمدی', 'درام', 'فانتزی', 'ترسناک',
            'رازآلود', 'عاشقانه', 'علمی-تخیلی', 'زندگی‌روزمره', 'ورزشی',
            'تراژدی', 'روانشناختی', 'تاریخی', 'هارم', 'اسمات', 'ایسکای',
            'مکا', 'جویی‌سی', 'شونن', 'شوجو', 'سینن', 'جوزی',
            'بزرگسال', 'بدون ژانر',
        ];

        foreach ($genres as $genre) {
            if (!term_exists($genre, 'genre')) {
                wp_insert_term($genre, 'genre');
            }
        }

        novel_log('ژانرها seed شدند', 'info');
    }

    /**
     * Seed وضعیت‌ها
     */
    private function seed_statuses() {
        if (term_exists('در حال انتشار', 'novel_status')) {
            return;
        }

        $statuses = [
            'در حال انتشار' => 'ongoing',
            'تمام‌شده'      => 'completed',
            'متوقف'         => 'hiatus',
            'رها‌شده'       => 'dropped',
        ];

        foreach ($statuses as $name => $slug) {
            if (!term_exists($slug, 'novel_status')) {
                wp_insert_term($name, 'novel_status', ['slug' => $slug]);
            }
        }

        novel_log('وضعیت‌ها seed شدند', 'info');
    }

    /**
     * Seed تگ‌ها
     */
    private function seed_tags() {
        if (term_exists('قهرمان قوی', 'novel_tag')) {
            return;
        }

        $tags = [
            'قهرمان قوی', 'انتقام', 'سیستمی', 'رستگاری', 'ضدقهرمان',
            'بازگشت به گذشته', 'تناسخ', 'تنها', 'هوش بالا', 'دنیای بازی',
            'غارنوردی', 'قلدر مدرسه', 'جادو', 'شمشیرزنی', 'کیمیاگری',
        ];

        foreach ($tags as $tag) {
            if (!term_exists($tag, 'novel_tag')) {
                wp_insert_term($tag, 'novel_tag');
            }
        }

        novel_log('تگ‌ها seed شدند', 'info');
    }

    /**
     * زمان‌بندی cron
     */
    private function schedule_cron() {
        if (!wp_next_scheduled('novel_daily_cron')) {
            wp_schedule_event(time(), 'daily', 'novel_daily_cron');
        }
        if (!wp_next_scheduled('novel_hourly_cron')) {
            wp_schedule_event(time(), 'hourly', 'novel_hourly_cron');
        }
    }

    /**
     * cron روزانه
     */
    public function daily_cron() {
        // انقضای سکه
        $this->expire_coins();
        // پاکسازی اعلان‌های قدیمی (۹۰ روز)
        $this->cleanup_old_notifications();

        novel_log('Cron روزانه اجرا شد', 'info');
    }

    /**
     * cron ساعتی
     */
    public function hourly_cron() {
        // پاکسازی transient های بازدید
        // (وردپرس خودش garbage collection دارد ولی ما هم کمک می‌کنیم)
        novel_log('Cron ساعتی اجرا شد', 'info');
    }

    /**
     * انقضای سکه‌ها
     */
    private function expire_coins() {
        global $wpdb;

        $expiry_days = absint(get_option('novel_coin_expiry_days', 0));
        if ($expiry_days === 0) {
            return;
        }

        $expired = $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}user_coins 
             SET is_expired = 1 
             WHERE expires_at IS NOT NULL 
             AND expires_at <= %s 
             AND is_expired = 0 
             AND amount > 0",
            current_time('mysql')
        ));

        if ($expired > 0) {
            novel_log("تعداد {$expired} رکورد سکه منقضی شد", 'info');
        }
    }

    /**
     * پاکسازی اعلان‌های قدیمی
     */
    private function cleanup_old_notifications() {
        global $wpdb;

        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}notifications 
             WHERE is_read = 1 
             AND created_at < %s",
            date('Y-m-d H:i:s', strtotime('-90 days'))
        ));
    }

    /**
     * اجرای migrations
     */
    private function run_migrations($from, $to) {
        novel_log("شروع migration از {$from} به {$to}", 'info');

        // از نسخه ۰ یا قبلی به ۲.۰.۰
        if (version_compare($from, '2.0.0', '<')) {
            $this->create_tables(); // ساخت/آپدیت تمام جداول
            $this->set_default_options();
        }

        // آینده: ۲.۰.۰ → ۲.۱.۰
        // if (version_compare($from, '2.1.0', '<')) {
        //     // تغییرات جدید
        // }

        novel_log("Migration به {$to} انجام شد", 'info');
    }

    /**
     * غیرفعال‌سازی قالب
     */
    public static function deactivate() {
        // حذف cron ها
        wp_clear_scheduled_hook('novel_daily_cron');
        wp_clear_scheduled_hook('novel_hourly_cron');

        // flush rewrite
        flush_rewrite_rules();

        // ⚠️ جداول و داده‌ها حذف نمی‌شوند!
        novel_log('قالب غیرفعال شد', 'info');
    }

    /**
     * آمار سایت (cache شده)
     */
    public static function get_site_stats() {
        $cached = get_transient('novel_site_stats');
        if ($cached !== false) {
            return $cached;
        }

        global $wpdb;

        $stats = [
            'novels'   => wp_count_posts('novel')->publish ?? 0,
            'chapters' => wp_count_posts('chapter')->publish ?? 0,
            'users'    => (int) $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->users}"),
            'comments' => (int) wp_count_comments()->approved,
        ];

        set_transient('novel_site_stats', $stats, HOUR_IN_SECONDS);

        return $stats;
    }
}

// مقداردهی اولیه
Novel_Core::get_instance();

// Hook غیرفعال‌سازی
add_action('switch_theme', ['Novel_Core', 'deactivate']);