<?php
/**
 * Authors System
 *
 * Handles author listing, profiles, stats, reviews, online status.
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Novel_Authors {

    /** @var string */
    private $reviews_table;

    public function __construct() {
        global $wpdb;
        $this->reviews_table = $wpdb->prefix . 'author_reviews';

        // Shortcode
        add_shortcode('novel_authors', [$this, 'render_authors_page']);

        // Tables
        add_action('after_switch_theme', [$this, 'create_tables']);
        add_action('init', [$this, 'maybe_create_tables'], 99);

        // AJAX
        add_action('wp_ajax_novel_search_authors', [$this, 'ajax_search_authors']);
        add_action('wp_ajax_nopriv_novel_search_authors', [$this, 'ajax_search_authors']);
        add_action('wp_ajax_novel_load_more_authors', [$this, 'ajax_load_more_authors']);
        add_action('wp_ajax_nopriv_novel_load_more_authors', [$this, 'ajax_load_more_authors']);
        add_action('wp_ajax_novel_submit_author_review', [$this, 'ajax_submit_review']);
        add_action('wp_ajax_novel_report_user', [$this, 'ajax_report_user']);

        // Online status heartbeat
        add_action('wp_ajax_novel_heartbeat', [$this, 'ajax_heartbeat']);
        add_action('template_redirect', [$this, 'update_last_active']);

        // Enqueue
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    // ═══════════════════════════════════════════
    // TABLES
    // ═══════════════════════════════════════════

    public function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE IF NOT EXISTS {$this->reviews_table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            author_id BIGINT UNSIGNED NOT NULL,
            rating TINYINT UNSIGNED NOT NULL DEFAULT 0,
            content TEXT NOT NULL,
            parent_id BIGINT UNSIGNED DEFAULT 0,
            likes INT UNSIGNED DEFAULT 0,
            dislikes INT UNSIGNED DEFAULT 0,
            status VARCHAR(20) DEFAULT 'approved',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY user_author (user_id, author_id),
            INDEX idx_author (author_id),
            INDEX idx_status (status)
        ) {$charset}";
        dbDelta($sql);
    }

    public function maybe_create_tables() {
        global $wpdb;
        if ($wpdb->get_var("SHOW TABLES LIKE '{$this->reviews_table}'") !== $this->reviews_table) {
            $this->create_tables();
        }
    }

    // ═══════════════════════════════════════════
    // ENQUEUE
    // ═══════════════════════════════════════════

    public function enqueue_assets() {
        if (is_author() || is_page('authors') || has_shortcode(get_post()->post_content ?? '', 'novel_authors')) {
            wp_enqueue_style('novel-authors', get_template_directory_uri() . '/assets/css/authors.css', ['novel-main-style'], NOVEL_VERSION);
            wp_enqueue_script('novel-authors', get_template_directory_uri() . '/assets/js/authors.js', ['jquery'], NOVEL_VERSION, true);
            wp_localize_script('novel-authors', 'novelAuthors', [
                'ajaxUrl'    => admin_url('admin-ajax.php'),
                'nonce'      => wp_create_nonce('novel_authors_action'),
                'isLoggedIn' => is_user_logged_in(),
            ]);
        }
    }

    // ═══════════════════════════════════════════
    // ONLINE STATUS
    // ═══════════════════════════════════════════

    public function update_last_active() {
        if (!is_user_logged_in()) return;
        $user_id = get_current_user_id();
        $last = get_user_meta($user_id, 'novel_last_active', true);
        // Update every 2 minutes max
        if (!$last || (time() - (int)$last) > 120) {
            update_user_meta($user_id, 'novel_last_active', time());
        }
    }

    public function ajax_heartbeat() {
        if (is_user_logged_in()) {
            update_user_meta(get_current_user_id(), 'novel_last_active', time());
        }
        wp_send_json_success();
    }

    public static function get_online_status($user_id) {
        $last = (int) get_user_meta($user_id, 'novel_last_active', true);
        if (!$last) return 'offline';
        $diff = time() - $last;
        if ($diff < 300) return 'online';     // 5 min
        if ($diff < 1800) return 'recent';    // 30 min
        return 'offline';
    }

    public static function render_online_dot($user_id) {
        $status = self::get_online_status($user_id);
        $classes = [
            'online' => 'status-dot status-online',
            'recent' => 'status-dot status-recent',
            'offline' => 'status-dot status-offline',
        ];
        $titles = [
            'online' => 'آنلاین',
            'recent' => 'اخیراً فعال',
            'offline' => 'آفلاین',
        ];
        return '<span class="' . ($classes[$status] ?? '') . '" title="' . ($titles[$status] ?? '') . '"></span>';
    }

    // ═══════════════════════════════════════════
    // AUTHOR STATS
    // ═══════════════════════════════════════════

    public static function get_author_stats($user_id) {
        global $wpdb;

        $cache_key = 'novel_author_stats_' . $user_id;
        $cached = wp_cache_get($cache_key, 'novel');
        if (false !== $cached) return $cached;

        // Novel count
        $novels_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='novel' AND post_status='publish' AND post_author=%d",
            $user_id
        ));

        // Chapter count
        $chapters_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='chapter' AND post_status='publish' AND post_author=%d",
            $user_id
        ));

        // Total views
        $total_views = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(CAST(pm.meta_value AS UNSIGNED)),0)
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE p.post_type='novel' AND p.post_status='publish' AND p.post_author=%d AND pm.meta_key='novel_views'",
            $user_id
        ));

        // Average rating across novels
        $avg_rating = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(AVG(CAST(pm.meta_value AS DECIMAL(3,1))),0)
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE p.post_type='novel' AND p.post_status='publish' AND p.post_author=%d AND pm.meta_key='novel_avg_rating'
             AND pm.meta_value > 0",
            $user_id
        ));

        // Followers count
        $followers_table = $wpdb->prefix . 'user_follows';
        $followers = 0;
        if ($wpdb->get_var("SHOW TABLES LIKE '{$followers_table}'") === $followers_table) {
            $followers = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$followers_table} WHERE followed_id=%d", $user_id
            ));
        }

        // Following count
        $following = 0;
        if ($wpdb->get_var("SHOW TABLES LIKE '{$followers_table}'") === $followers_table) {
            $following = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$followers_table} WHERE follower_id=%d", $user_id
            ));
        }

        $stats = [
            'novels'     => $novels_count,
            'chapters'   => $chapters_count,
            'views'      => $total_views,
            'avg_rating' => round($avg_rating, 1),
            'followers'  => $followers,
            'following'  => $following,
        ];

        wp_cache_set($cache_key, $stats, 'novel', 1800);
        return $stats;
    }

    /**
     * Calculate combined score for ranking
     */
    public static function get_author_score($user_id) {
        $stats = self::get_author_stats($user_id);
        return ($stats['avg_rating'] * 0.3) +
               ($stats['followers'] * 0.3) +
               (min($stats['views'], 100000) / 100000 * 100 * 0.2) +
               ($stats['novels'] * 10 * 0.2);
    }

    // ═══════════════════════════════════════════
    // QUERIES
    // ═══════════════════════════════════════════

    /**
     * Get authors who have at least 1 published novel
     */
    public static function get_authors($args = []) {
        global $wpdb;

        $defaults = [
            'per_page' => 12,
            'page'     => 1,
            'sort'     => 'novels',
            'search'   => '',
        ];
        $args = wp_parse_args($args, $defaults);

        // Get author IDs with published novels
        $author_ids = $wpdb->get_col(
            "SELECT DISTINCT post_author FROM {$wpdb->posts}
             WHERE post_type='novel' AND post_status='publish'"
        );

        if (empty($author_ids)) {
            return ['users' => [], 'total' => 0, 'max_pages' => 0];
        }

        $user_query_args = [
            'include'  => $author_ids,
            'number'   => $args['per_page'],
            'paged'    => $args['page'],
            'orderby'  => 'registered',
            'order'    => 'DESC',
        ];

        if (!empty($args['search'])) {
            $user_query_args['search'] = '*' . esc_attr($args['search']) . '*';
            $user_query_args['search_columns'] = ['user_login', 'display_name', 'user_nicename'];
        }

        $query = new WP_User_Query($user_query_args);
        $users = $query->get_results();
        $total = $query->get_total();

        // Sort by custom metric
        if (!empty($users)) {
            usort($users, function($a, $b) use ($args) {
                $sa = self::get_author_stats($a->ID);
                $sb = self::get_author_stats($b->ID);

                switch ($args['sort']) {
                    case 'rating':
                        return $sb['avg_rating'] <=> $sa['avg_rating'];
                    case 'followers':
                        return $sb['followers'] <=> $sa['followers'];
                    case 'active':
                        $la = (int) get_user_meta($a->ID, 'novel_last_active', true);
                        $lb = (int) get_user_meta($b->ID, 'novel_last_active', true);
                        return $lb <=> $la;
                    case 'newest':
                        return strtotime($b->user_registered) <=> strtotime($a->user_registered);
                    case 'novels':
                    default:
                        return $sb['novels'] <=> $sa['novels'];
                }
            });
        }

        return [
            'users'     => $users,
            'total'     => $total,
            'max_pages' => ceil($total / $args['per_page']),
        ];
    }

    /**
     * Get top authors for homepage
     */
    public static function get_top_authors($limit = 8) {
        $cached = get_transient('novel_top_authors');
        if (false !== $cached) return $cached;

        global $wpdb;
        $author_ids = $wpdb->get_col(
            "SELECT DISTINCT post_author FROM {$wpdb->posts}
             WHERE post_type='novel' AND post_status='publish'"
        );

        if (empty($author_ids)) return [];

        $scored = [];
        foreach ($author_ids as $uid) {
            $scored[] = [
                'user_id' => (int) $uid,
                'score'   => self::get_author_score($uid),
            ];
        }

        usort($scored, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        $top = array_slice($scored, 0, $limit);
        $result = [];
        foreach ($top as $item) {
            $user = get_user_by('id', $item['user_id']);
            if ($user) {
                $result[] = $user;
            }
        }

        set_transient('novel_top_authors', $result, 6 * HOUR_IN_SECONDS);
        return $result;
    }

    // ═══════════════════════════════════════════
    // AUTHOR REVIEWS
    // ═══════════════════════════════════════════

    public function get_author_reviews($author_id, $page = 1, $per_page = 10) {
        global $wpdb;
        $offset = ($page - 1) * $per_page;

        $reviews = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->reviews_table}
             WHERE author_id = %d AND parent_id = 0 AND status = 'approved'
             ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $author_id, $per_page, $offset
        ));

        $total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->reviews_table}
             WHERE author_id = %d AND parent_id = 0 AND status = 'approved'",
            $author_id
        ));

        return [
            'reviews'   => $reviews,
            'total'     => $total,
            'max_pages' => ceil($total / $per_page),
        ];
    }

    public function get_author_review_stats($author_id) {
        global $wpdb;
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as cnt, COALESCE(AVG(rating),0) as avg_r
             FROM {$this->reviews_table}
             WHERE author_id = %d AND parent_id = 0 AND status = 'approved' AND rating > 0",
            $author_id
        ));

        $dist = [];
        for ($i = 5; $i >= 1; $i--) {
            $dist[$i] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->reviews_table}
                 WHERE author_id = %d AND parent_id = 0 AND status = 'approved' AND rating = %d",
                $author_id, $i
            ));
        }

        return [
            'count'        => (int) $stats->cnt,
            'average'      => round((float) $stats->avg_r, 1),
            'distribution' => $dist,
        ];
    }

    public function ajax_submit_review() {
        check_ajax_referer('novel_authors_action', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'ابتدا وارد شوید']);
        }

        $author_id = absint($_POST['author_id'] ?? 0);
        $rating    = absint($_POST['rating'] ?? 0);
        $content   = sanitize_textarea_field($_POST['content'] ?? '');
        $user_id   = get_current_user_id();

        if (!$author_id || $rating < 1 || $rating > 5) {
            wp_send_json_error(['message' => 'داده نامعتبر']);
        }
        if ($user_id === $author_id) {
            wp_send_json_error(['message' => 'نمی‌توانید برای خودتان نظر بدهید']);
        }
        if (mb_strlen($content) < 10) {
            wp_send_json_error(['message' => 'حداقل ۱۰ کاراکتر بنویسید']);
        }

        global $wpdb;

        // Check existing
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->reviews_table} WHERE user_id = %d AND author_id = %d AND parent_id = 0",
            $user_id, $author_id
        ));

        if ($existing) {
            $wpdb->update($this->reviews_table, [
                'rating'     => $rating,
                'content'    => $content,
                'updated_at' => current_time('mysql'),
            ], ['id' => $existing]);
            $message = 'نظر شما به‌روز شد';
        } else {
            $wpdb->insert($this->reviews_table, [
                'user_id'    => $user_id,
                'author_id'  => $author_id,
                'rating'     => $rating,
                'content'    => $content,
                'parent_id'  => 0,
                'status'     => 'approved',
                'created_at' => current_time('mysql'),
            ]);
            $message = 'نظر شما ثبت شد';

            // Send notification
            if (class_exists('Novel_Notifications')) {
                Novel_Notifications::send(
                    $author_id,
                    'author_review',
                    '⭐ ' . wp_get_current_user()->display_name . ' درباره شما نظر داد',
                    mb_substr($content, 0, 80),
                    get_author_posts_url($author_id) . '#reviews'
                );
            }
        }

        wp_send_json_success(['message' => $message]);
    }

    // ═══════════════════════════════════════════
    // AJAX: SEARCH & LOAD MORE
    // ═══════════════════════════════════════════

    public function ajax_search_authors() {
        $search = sanitize_text_field($_POST['search'] ?? '');
        $sort   = sanitize_text_field($_POST['sort'] ?? 'novels');
        $page   = absint($_POST['page'] ?? 1);

        $result = self::get_authors([
            'search'   => $search,
            'sort'     => $sort,
            'page'     => $page,
            'per_page' => 12,
        ]);

        ob_start();
        foreach ($result['users'] as $user) {
            $GLOBALS['author_card_user'] = $user;
            get_template_part('templates/authors/author-card');
        }
        $html = ob_get_clean();

        if (empty($result['users'])) {
            $html = '<div class="authors-empty"><p>نویسنده‌ای یافت نشد.</p></div>';
        }

        wp_send_json_success([
            'html'      => $html,
            'total'     => $result['total'],
            'max_pages' => $result['max_pages'],
        ]);
    }

    public function ajax_load_more_authors() {
        $this->ajax_search_authors();
    }

    // ═══════════════════════════════════════════
    // AJAX: REPORT USER
    // ═══════════════════════════════════════════

    public function ajax_report_user() {
        check_ajax_referer('novel_authors_action', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'ابتدا وارد شوید']);
        }

        $reported_id = absint($_POST['user_id'] ?? 0);
        $reason      = sanitize_text_field($_POST['reason'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $user_id     = get_current_user_id();

        $valid = ['behavior', 'spam', 'fake_identity', 'inappropriate', 'other'];
        if (!$reported_id || !in_array($reason, $valid)) {
            wp_send_json_error(['message' => 'داده نامعتبر']);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'reports';

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE user_id=%d AND reported_type='user' AND reported_id=%d AND status='pending'",
            $user_id, $reported_id
        ));
        if ($exists) {
            wp_send_json_error(['message' => 'قبلاً گزارش داده‌اید']);
        }

        $wpdb->insert($table, [
            'user_id'       => $user_id,
            'reported_type' => 'user',
            'reported_id'   => $reported_id,
            'reason'        => $reason,
            'description'   => mb_substr($description, 0, 500),
            'status'        => 'pending',
            'created_at'    => current_time('mysql'),
        ]);

        wp_send_json_success(['message' => 'گزارش ارسال شد']);
    }

    // ═══════════════════════════════════════════
    // SHORTCODE RENDER
    // ═══════════════════════════════════════════

    public function render_authors_page($atts) {
        ob_start();
        get_template_part('templates/authors/authors-list');
        return ob_get_clean();
    }
}