<?php
/**
 * Novel Rankings & View Counter System
 * 
 * بازدید پیشرفته + رتبه‌بندی + آمار نویسنده + ترند
 *
 * @package suspended-flavor
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

class Novel_Rankings {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Record views
        add_action('template_redirect', [$this, 'record_view']);

        // AJAX endpoints
        add_action('wp_ajax_novel_get_rankings', [$this, 'ajax_rankings']);
        add_action('wp_ajax_nopriv_novel_get_rankings', [$this, 'ajax_rankings']);
        add_action('wp_ajax_novel_get_updates', [$this, 'ajax_updates']);
        add_action('wp_ajax_nopriv_novel_get_updates', [$this, 'ajax_updates']);
        add_action('wp_ajax_novel_author_stats', [$this, 'ajax_author_stats']);

        // Shortcodes
        add_shortcode('novel_rankings', [$this, 'render_rankings_page']);
        add_shortcode('novel_updates', [$this, 'render_updates_page']);

        // Cron: recalculate rankings hourly
        add_action('novel_cron_recalculate_rankings', [$this, 'recalculate_rankings']);
        if (!wp_next_scheduled('novel_cron_recalculate_rankings')) {
            wp_schedule_event(time(), 'hourly', 'novel_cron_recalculate_rankings');
        }

        // Cron: calculate trend scores daily
        add_action('novel_cron_calculate_trends', [$this, 'calculate_trend_scores']);
        if (!wp_next_scheduled('novel_cron_calculate_trends')) {
            wp_schedule_event(time(), 'daily', 'novel_cron_calculate_trends');
        }

        // Enqueue
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'novel-rankings',
            get_template_directory_uri() . '/assets/css/rankings.css',
            ['novel-main-style'],
            FLAVOR_VERSION
        );

        wp_localize_script('novel-main', 'novelRankings', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('novel_rankings_nonce'),
            'strings' => [
                'loading'  => 'در حال بارگذاری...',
                'noResult' => 'رمانی یافت نشد',
                'rank'     => 'رتبه',
                'up'       => 'صعود',
                'down'     => 'نزول',
                'new'      => 'تازه‌وارد',
                'same'     => 'ثابت',
            ],
        ]);
    }

    /* ═══════════════════════════════════════
       View Counter
       ═══════════════════════════════════════ */

    /**
     * ثبت بازدید (یک IP/user در روز = یک بازدید)
     */
    public function record_view() {
        if (!is_singular(['novel', 'chapter'])) return;
        if (is_admin() || wp_doing_ajax()) return;

        // Skip bots
        if ($this->is_bot()) return;

        $post_id = get_the_ID();
        $user_id = get_current_user_id();
        $ip = $this->get_hashed_ip();
        $today = current_time('Y-m-d');

        global $wpdb;
        $table = $wpdb->prefix . 'novel_views';

        // Check duplicate
        if ($user_id) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table} WHERE post_id = %d AND user_id = %d AND view_date = %s",
                $post_id, $user_id, $today
            ));
        } else {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table} WHERE post_id = %d AND ip_address = %s AND view_date = %s AND user_id = 0",
                $post_id, $ip, $today
            ));
        }

        if ($exists) return;

        // Insert view
        $wpdb->insert($table, [
            'post_id'    => $post_id,
            'user_id'    => $user_id,
            'ip_address' => $ip,
            'view_date'  => $today,
            'created_at' => current_time('mysql'),
        ], ['%d', '%d', '%s', '%s', '%s']);

        // If chapter → also count for parent novel
        if (get_post_type($post_id) === 'chapter') {
            $novel_id = (int) get_post_meta($post_id, 'chapter_novel_id', true);
            if (!$novel_id) $novel_id = wp_get_post_parent_id($post_id);
            
            if ($novel_id) {
                // Count for novel too (separate entry)
                if ($user_id) {
                    $novel_exists = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM {$table} WHERE post_id = %d AND user_id = %d AND view_date = %s",
                        $novel_id, $user_id, $today
                    ));
                } else {
                    $novel_exists = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM {$table} WHERE post_id = %d AND ip_address = %s AND view_date = %s AND user_id = 0",
                        $novel_id, $ip, $today
                    ));
                }

                if (!$novel_exists) {
                    $wpdb->insert($table, [
                        'post_id'    => $novel_id,
                        'user_id'    => $user_id,
                        'ip_address' => $ip,
                        'view_date'  => $today,
                        'created_at' => current_time('mysql'),
                    ], ['%d', '%d', '%s', '%s', '%s']);
                }

                // Quick update cached meta
                $this->quick_update_view_meta($novel_id);
            }
        }

        // Quick update for this post
        $this->quick_update_view_meta($post_id);
    }

    /**
     * Quick update view count meta (incremental)
     */
    private function quick_update_view_meta($post_id) {
        $current_total = (int) get_post_meta($post_id, 'novel_views_total', true);
        update_post_meta($post_id, 'novel_views_total', $current_total + 1);

        $current_today = (int) get_post_meta($post_id, 'novel_views_today', true);
        update_post_meta($post_id, 'novel_views_today', $current_today + 1);
    }

    /**
     * Get views for a post
     */
    public static function get_views($post_id, $period = 'total') {
        $meta_key = 'novel_views_' . $period;
        $cached = get_post_meta($post_id, $meta_key, true);
        
        if ($cached !== '') return (int) $cached;

        // Calculate from DB
        global $wpdb;
        $table = $wpdb->prefix . 'novel_views';

        $where = $wpdb->prepare("WHERE post_id = %d", $post_id);

        switch ($period) {
            case 'today':
                $where .= $wpdb->prepare(" AND view_date = %s", current_time('Y-m-d'));
                break;
            case 'week':
                $where .= $wpdb->prepare(" AND view_date >= %s", date('Y-m-d', strtotime('-7 days')));
                break;
            case 'month':
                $where .= $wpdb->prepare(" AND view_date >= %s", date('Y-m-d', strtotime('-30 days')));
                break;
        }

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where}");
        update_post_meta($post_id, $meta_key, $count);

        return $count;
    }

    /* ═══════════════════════════════════════
       Rankings Recalculation (Cron)
       ═══════════════════════════════════════ */

    /**
     * بازمحاسبه کامل رتبه‌بندی‌ها (هر ساعت)
     */
    public function recalculate_rankings() {
        global $wpdb;
        $views_table = $wpdb->prefix . 'novel_views';

        $novels = get_posts([
            'post_type'      => 'novel',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);

        $today = current_time('Y-m-d');
        $week_ago = date('Y-m-d', strtotime('-7 days'));
        $month_ago = date('Y-m-d', strtotime('-30 days'));

        foreach ($novels as $novel_id) {
            // Views
            $views_today = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$views_table} WHERE post_id = %d AND view_date = %s",
                $novel_id, $today
            ));
            $views_week = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$views_table} WHERE post_id = %d AND view_date >= %s",
                $novel_id, $week_ago
            ));
            $views_month = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$views_table} WHERE post_id = %d AND view_date >= %s",
                $novel_id, $month_ago
            ));
            $views_total = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$views_table} WHERE post_id = %d",
                $novel_id
            ));

            update_post_meta($novel_id, 'novel_views_today', $views_today);
            update_post_meta($novel_id, 'novel_views_week', $views_week);
            update_post_meta($novel_id, 'novel_views_month', $views_month);
            update_post_meta($novel_id, 'novel_views_total', $views_total);

            // Chapter count cache
            $ch_count = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} 
                 WHERE post_type = 'chapter' AND post_status = 'publish'
                 AND (post_parent = %d OR ID IN (
                     SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'chapter_novel_id' AND meta_value = %d
                 ))",
                $novel_id, $novel_id
            ));
            update_post_meta($novel_id, 'novel_chapter_count', $ch_count);
        }

        // Store previous rankings for comparison
        $this->store_previous_rankings();

        // Clear transient caches
        $this->clear_ranking_caches();
    }

    /**
     * ذخیره رتبه فعلی برای مقایسه بعدی
     */
    private function store_previous_rankings() {
        $periods = ['today', 'week', 'month', 'total'];
        $types = ['popular', 'rating', 'followers', 'comments', 'bookmarks'];

        foreach ($periods as $period) {
            foreach ($types as $type) {
                $current = get_transient("novel_ranking_{$type}_{$period}");
                if ($current) {
                    set_transient("novel_ranking_prev_{$type}_{$period}", $current, 2 * HOUR_IN_SECONDS);
                }
            }
        }
    }

    /**
     * Clear ranking caches
     */
    private function clear_ranking_caches() {
        $periods = ['today', 'week', 'month', 'total'];
        $types = ['popular', 'rating', 'followers', 'comments', 'updated', 'newest', 'bookmarks', 'views'];

        foreach ($periods as $p) {
            foreach ($types as $t) {
                delete_transient("novel_ranking_{$t}_{$p}");
            }
        }
        delete_transient('novel_trending_novels');
    }

    /* ═══════════════════════════════════════
       Trend Score Calculation (Daily Cron)
       ═══════════════════════════════════════ */

    /**
     * محاسبه امتیاز ترند
     * score = views_week * 0.5 + comments_week * 0.3 + follows_week * 0.2
     */
    public function calculate_trend_scores() {
        global $wpdb;

        $week_ago = date('Y-m-d', strtotime('-7 days'));
        $views_table = $wpdb->prefix . 'novel_views';
        $follows_table = $wpdb->prefix . 'novel_follows';

        $novels = get_posts([
            'post_type'      => 'novel',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);

        $scores = [];

        foreach ($novels as $novel_id) {
            // Weekly views
            $views_week = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$views_table} WHERE post_id = %d AND view_date >= %s",
                $novel_id, $week_ago
            ));

            // Weekly comments
            $comments_week = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->comments}
                 WHERE comment_post_ID IN (
                     SELECT ID FROM {$wpdb->posts} 
                     WHERE (post_parent = %d OR ID = %d) AND post_type IN ('novel','chapter')
                 )
                 AND comment_date >= %s AND comment_approved = '1'",
                $novel_id, $novel_id, $week_ago . ' 00:00:00'
            ));

            // Weekly follows
            $follows_week = 0;
            $follows_exists = $wpdb->get_var("SHOW TABLES LIKE '{$follows_table}'");
            if ($follows_exists) {
                $follows_week = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$follows_table} 
                     WHERE followed_type = 'novel' AND followed_id = %d AND created_at >= %s",
                    $novel_id, $week_ago . ' 00:00:00'
                ));
            }

            $score = ($views_week * 0.5) + ($comments_week * 0.3) + ($follows_week * 0.2);
            update_post_meta($novel_id, 'novel_trend_score', round($score, 2));

            $scores[$novel_id] = $score;
        }

        // Cache top 20 trending
        arsort($scores);
        $trending_ids = array_slice(array_keys($scores), 0, 20, true);
        set_transient('novel_trending_ids', $trending_ids, DAY_IN_SECONDS);
    }

    /* ═══════════════════════════════════════
       Rankings AJAX
       ═══════════════════════════════════════ */

    /**
     * AJAX: Get rankings
     */
    public function ajax_rankings() {
        check_ajax_referer('novel_rankings_nonce', 'nonce');

        $type   = sanitize_text_field($_POST['type'] ?? 'popular');
        $period = sanitize_text_field($_POST['period'] ?? 'total');
        $page   = max(1, absint($_POST['page'] ?? 1));
        $per_page = 20;

        $valid_types = ['popular', 'rating', 'followers', 'comments', 'updated', 'newest', 'bookmarks', 'views'];
        $valid_periods = ['today', 'week', 'month', 'total'];

        if (!in_array($type, $valid_types, true)) $type = 'popular';
        if (!in_array($period, $valid_periods, true)) $period = 'total';

        // Check cache
        $cache_key = "novel_ranking_{$type}_{$period}_p{$page}";
        $cached = get_transient($cache_key);
        if ($cached !== false && $page <= 2) {
            wp_send_json_success($cached);
        }

        // Build query
        $args = [
            'post_type'      => 'novel',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
        ];

        switch ($type) {
            case 'popular':
            case 'views':
                $meta_key = 'novel_views_' . ($period === 'total' ? 'total' : $period);
                $args['meta_key'] = $meta_key;
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;

            case 'rating':
                $args['meta_key'] = 'novel_rating_average';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                // Minimum 10 ratings
                $args['meta_query'] = [
                    [
                        'key'     => 'novel_rating_count',
                        'value'   => 10,
                        'compare' => '>=',
                        'type'    => 'NUMERIC',
                    ],
                ];
                break;

            case 'followers':
                $args['meta_key'] = 'novel_followers_count';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;

            case 'comments':
                $args['orderby'] = 'comment_count';
                $args['order'] = 'DESC';
                break;

            case 'updated':
                $args['orderby'] = 'modified';
                $args['order'] = 'DESC';
                break;

            case 'newest':
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;

            case 'bookmarks':
                $args['meta_key'] = 'novel_bookmarks_count';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
        }

        $query = new WP_Query($args);
        $novels = [];
        $rank = ($page - 1) * $per_page;

        // Previous rankings for comparison
        $prev_key = "novel_ranking_prev_{$type}_{$period}";
        $prev_rankings = get_transient($prev_key) ?: [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $rank++;
                $pid = get_the_ID();

                $rating = (float) get_post_meta($pid, 'novel_rating_average', true);
                $novel_type = get_post_meta($pid, 'novel_type', true) ?: 'WN';
                $author = get_post_meta($pid, 'novel_original_author', true);
                $thumb = get_the_post_thumbnail_url($pid, 'medium');
                $ch_count = (int) get_post_meta($pid, 'novel_chapter_count', true);
                $views = (int) get_post_meta($pid, 'novel_views_' . ($period === 'total' ? 'total' : $period), true);
                $followers = (int) get_post_meta($pid, 'novel_followers_count', true);

                // Rank change
                $prev_rank = isset($prev_rankings[$pid]) ? $prev_rankings[$pid] : 0;
                $rank_change = 0;
                $rank_direction = 'same';

                if ($prev_rank > 0 && $prev_rank !== $rank) {
                    $rank_change = $prev_rank - $rank;
                    $rank_direction = $rank_change > 0 ? 'up' : 'down';
                    $rank_change = abs($rank_change);
                } elseif ($prev_rank === 0) {
                    $rank_direction = 'new';
                }

                // Genres
                $genres = wp_get_post_terms($pid, 'genre', ['fields' => 'names']);
                $genre_list = is_wp_error($genres) ? [] : array_slice($genres, 0, 3);

                $novels[] = [
                    'id'             => $pid,
                    'rank'           => $rank,
                    'title'          => get_the_title(),
                    'url'            => get_permalink(),
                    'thumb'          => $thumb ?: get_template_directory_uri() . '/assets/images/no-cover.png',
                    'rating'         => round($rating, 1),
                    'type'           => strtoupper($novel_type),
                    'author'         => $author,
                    'chapters'       => $ch_count,
                    'views'          => $views,
                    'followers'      => $followers,
                    'genres'         => $genre_list,
                    'rank_change'    => $rank_change,
                    'rank_direction' => $rank_direction,
                ];
            }
            wp_reset_postdata();
        }

        // Store current rankings
        $current_rankings = [];
        foreach ($novels as $n) {
            $current_rankings[$n['id']] = $n['rank'];
        }

        $result = [
            'novels'   => $novels,
            'total'    => $query->found_posts,
            'pages'    => $query->max_num_pages,
            'page'     => $page,
            'has_more' => $page < $query->max_num_pages,
        ];

        // Cache for 1 hour
        if ($page <= 2) {
            set_transient($cache_key, $result, HOUR_IN_SECONDS);
        }

        // Store rankings for next comparison
        set_transient("novel_ranking_{$type}_{$period}", $current_rankings, 2 * HOUR_IN_SECONDS);

        wp_send_json_success($result);
    }

    /* ═══════════════════════════════════════
       Updates AJAX
       ═══════════════════════════════════════ */

    /**
     * AJAX: Latest chapter updates
     */
    public function ajax_updates() {
        check_ajax_referer('novel_rankings_nonce', 'nonce');

        $page     = max(1, absint($_POST['page'] ?? 1));
        $per_page = 50;
        $genre    = sanitize_text_field($_POST['genre'] ?? '');
        $type     = sanitize_text_field($_POST['type'] ?? '');

        $args = [
            'post_type'      => 'chapter',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        // Filter by novel genre/type through parent
        // We'll filter after query for simplicity

        $query = new WP_Query($args);
        $updates = [];
        $grouped = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $ch_id = get_the_ID();
                $novel_id = (int) get_post_meta($ch_id, 'chapter_novel_id', true);
                if (!$novel_id) $novel_id = wp_get_post_parent_id($ch_id);
                if (!$novel_id) continue;

                $novel = get_post($novel_id);
                if (!$novel || $novel->post_status !== 'publish') continue;

                // Filter by genre
                if ($genre) {
                    $novel_genres = wp_get_post_terms($novel_id, 'genre', ['fields' => 'slugs']);
                    if (!in_array($genre, $novel_genres, true)) continue;
                }

                // Filter by type
                if ($type) {
                    $novel_type = get_post_meta($novel_id, 'novel_type', true);
                    if (strtolower($novel_type) !== strtolower($type)) continue;
                }

                $ch_number = (int) get_post_meta($ch_id, 'chapter_number', true);
                $is_vip = (bool) get_post_meta($ch_id, 'chapter_is_vip', true);
                $thumb = get_the_post_thumbnail_url($novel_id, 'thumbnail');
                $novel_type_val = get_post_meta($novel_id, 'novel_type', true) ?: 'WN';
                $novel_author = get_post_meta($novel_id, 'novel_original_author', true);
                $published = get_the_date('', $ch_id);

                // Group by date
                $date_key = get_the_date('Y-m-d', $ch_id);
                $today = current_time('Y-m-d');
                $yesterday = date('Y-m-d', strtotime('-1 day', current_time('timestamp')));

                if ($date_key === $today) {
                    $group_label = 'امروز';
                } elseif ($date_key === $yesterday) {
                    $group_label = 'دیروز';
                } else {
                    $group_label = date_i18n('j F Y', strtotime($date_key));
                }

                $grouped[$group_label][] = [
                    'chapter_id'   => $ch_id,
                    'chapter_title'=> get_the_title(),
                    'chapter_num'  => $ch_number,
                    'chapter_url'  => get_permalink($ch_id),
                    'is_vip'       => $is_vip,
                    'novel_id'     => $novel_id,
                    'novel_title'  => $novel->post_title,
                    'novel_url'    => get_permalink($novel_id),
                    'novel_type'   => strtoupper($novel_type_val),
                    'novel_author' => $novel_author,
                    'thumb'        => $thumb ?: get_template_directory_uri() . '/assets/images/no-cover.png',
                    'time_ago'     => human_time_diff(get_the_time('U', $ch_id), current_time('timestamp')),
                ];
            }
            wp_reset_postdata();
        }

        wp_send_json_success([
            'groups'   => $grouped,
            'total'    => $query->found_posts,
            'pages'    => $query->max_num_pages,
            'page'     => $page,
            'has_more' => $page < $query->max_num_pages,
        ]);
    }

    /* ═══════════════════════════════════════
       Author Stats AJAX
       ═══════════════════════════════════════ */

    /**
     * AJAX: Author statistics for dashboard
     */
    public function ajax_author_stats() {
        check_ajax_referer('novel_rankings_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'ابتدا وارد شوید']);
        }

        $user_id = get_current_user_id();
        $period = sanitize_text_field($_POST['period'] ?? 'month');

        global $wpdb;
        $views_table = $wpdb->prefix . 'novel_views';

        // Get author's novels
        $novels = get_posts([
            'post_type'      => 'novel',
            'post_status'    => 'publish',
            'author'         => $user_id,
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);

        if (empty($novels)) {
            wp_send_json_success(['has_novels' => false]);
        }

        $novel_ids = implode(',', array_map('intval', $novels));

        // Summary stats
        $total_views = 0;
        $total_followers = 0;
        $total_comments = 0;
        $total_likes = 0;
        $total_rating = 0;
        $rating_count = 0;

        $novel_stats = [];

        foreach ($novels as $nid) {
            $views = (int) get_post_meta($nid, 'novel_views_total', true);
            $followers = (int) get_post_meta($nid, 'novel_followers_count', true);
            $comments = (int) get_comments_number($nid);
            $rating = (float) get_post_meta($nid, 'novel_rating_average', true);
            $r_count = (int) get_post_meta($nid, 'novel_rating_count', true);
            $ch_count = (int) get_post_meta($nid, 'novel_chapter_count', true);

            $total_views += $views;
            $total_followers += $followers;
            $total_comments += $comments;
            if ($r_count > 0) {
                $total_rating += $rating * $r_count;
                $rating_count += $r_count;
            }

            $novel_stats[] = [
                'id'        => $nid,
                'title'     => get_the_title($nid),
                'url'       => get_permalink($nid),
                'views'     => $views,
                'followers' => $followers,
                'comments'  => $comments,
                'rating'    => round($rating, 1),
                'chapters'  => $ch_count,
            ];
        }

        $avg_rating = $rating_count > 0 ? round($total_rating / $rating_count, 1) : 0;

        // Daily views chart (last 30 days)
        $chart_data = [];
        $days = $period === 'week' ? 7 : ($period === 'year' ? 365 : 30);

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $day_views = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$views_table} 
                 WHERE post_id IN ({$novel_ids}) AND view_date = '{$date}'"
            );
            $chart_data[] = [
                'date'  => date_i18n('j M', strtotime($date)),
                'views' => $day_views,
            ];
        }

        // Top 5 chapters by views
        $top_chapters = $wpdb->get_results(
            "SELECT p.ID, p.post_title, pm.meta_value as novel_id, COUNT(v.id) as view_count
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'chapter_novel_id'
             LEFT JOIN {$views_table} v ON p.ID = v.post_id
             WHERE pm.meta_value IN ({$novel_ids}) AND p.post_type = 'chapter' AND p.post_status = 'publish'
             GROUP BY p.ID
             ORDER BY view_count DESC
             LIMIT 5"
        );

        $top_chapters_data = [];
        foreach ($top_chapters as $ch) {
            $top_chapters_data[] = [
                'id'         => $ch->ID,
                'title'      => $ch->post_title,
                'novel'      => get_the_title($ch->novel_id),
                'views'      => (int) $ch->view_count,
                'url'        => get_permalink($ch->ID),
            ];
        }

        wp_send_json_success([
            'has_novels'   => true,
            'summary'      => [
                'views'     => $total_views,
                'followers' => $total_followers,
                'comments'  => $total_comments,
                'likes'     => $total_likes,
                'rating'    => $avg_rating,
            ],
            'novels'       => $novel_stats,
            'chart'        => $chart_data,
            'top_chapters' => $top_chapters_data,
        ]);
    }

    /* ═══════════════════════════════════════
       Homepage Sections Data
       ═══════════════════════════════════════ */

    /**
     * Get trending novels for homepage
     */
    public static function get_trending($limit = 10) {
        $ids = get_transient('novel_trending_ids');

        if (!$ids) {
            // Fallback: weekly views
            $args = [
                'post_type'      => 'novel',
                'post_status'    => 'publish',
                'posts_per_page' => $limit,
                'meta_key'       => 'novel_views_week',
                'orderby'        => 'meta_value_num',
                'order'          => 'DESC',
                'fields'         => 'ids',
            ];
            $ids = get_posts($args);
        } else {
            $ids = array_slice($ids, 0, $limit);
        }

        return $ids;
    }

    /**
     * Get latest chapter updates for homepage
     */
    public static function get_latest_updates($limit = 15) {
        $cache = get_transient('novel_latest_updates');
        if ($cache !== false) return $cache;

        $chapters = get_posts([
            'post_type'      => 'chapter',
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        $updates = [];
        foreach ($chapters as $ch) {
            $novel_id = (int) get_post_meta($ch->ID, 'chapter_novel_id', true);
            if (!$novel_id) $novel_id = $ch->post_parent;
            if (!$novel_id) continue;

            $novel = get_post($novel_id);
            if (!$novel) continue;

            $ch_num = (int) get_post_meta($ch->ID, 'chapter_number', true);
            $is_vip = (bool) get_post_meta($ch->ID, 'chapter_is_vip', true);

            $updates[] = [
                'chapter_id'   => $ch->ID,
                'chapter_title'=> $ch->post_title,
                'chapter_num'  => $ch_num,
                'chapter_url'  => get_permalink($ch->ID),
                'is_vip'       => $is_vip,
                'novel_id'     => $novel_id,
                'novel_title'  => $novel->post_title,
                'novel_url'    => get_permalink($novel_id),
                'thumb'        => get_the_post_thumbnail_url($novel_id, 'thumbnail'),
                'type'         => strtoupper(get_post_meta($novel_id, 'novel_type', true) ?: 'WN'),
                'time_ago'     => human_time_diff(strtotime($ch->post_date), current_time('timestamp')),
            ];
        }

        set_transient('novel_latest_updates', $updates, 15 * MINUTE_IN_SECONDS);
        return $updates;
    }

    /**
     * Get popular novels
     */
    public static function get_popular($limit = 12, $period = 'month') {
        return get_posts([
            'post_type'      => 'novel',
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'meta_key'       => 'novel_views_' . $period,
            'orderby'        => 'meta_value_num',
            'order'          => 'DESC',
        ]);
    }

    /* ═══════════════════════════════════════
       Render Shortcodes
       ═══════════════════════════════════════ */

    /**
     * Render rankings page
     */
    public function render_rankings_page($atts) {
        ob_start();
        get_template_part('templates/rankings/rankings-page');
        return ob_get_clean();
    }

    /**
     * Render updates page
     */
    public function render_updates_page($atts) {
        ob_start();
        get_template_part('templates/rankings/updates-page');
        return ob_get_clean();
    }

    /* ═══════════════════════════════════════
       Helpers
       ═══════════════════════════════════════ */

    private function is_bot() {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $bots = ['bot', 'crawl', 'spider', 'slurp', 'googlebot', 'bingbot', 'yandex', 'baidu', 'duckduck'];
        $ua_lower = strtolower($ua);
        foreach ($bots as $bot) {
            if (strpos($ua_lower, $bot) !== false) return true;
        }
        return false;
    }

    private function get_hashed_ip() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        return md5(trim($ip) . 'novel_salt_' . date('Y-m'));
    }

    /**
     * Format number for display
     */
    public static function format_number($num) {
        $num = (int) $num;
        if ($num >= 1000000) return round($num / 1000000, 1) . 'M';
        if ($num >= 1000) return round($num / 1000, 1) . 'K';
        return number_format_i18n($num);
    }

    /**
     * Create tables
     */
    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $views_table = $wpdb->prefix . 'novel_views';
        $sql = "CREATE TABLE IF NOT EXISTS {$views_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED DEFAULT 0,
            ip_address VARCHAR(64) DEFAULT NULL,
            view_date DATE NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_view (post_id, user_id, ip_address, view_date),
            KEY idx_post_date (post_id, view_date),
            KEY idx_date (view_date)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}