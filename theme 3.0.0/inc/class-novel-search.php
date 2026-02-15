<?php
/**
 * Novel Advanced Search System
 * 
 * جستجوی زنده هدر + جستجوی پیشرفته با فیلتر
 *
 * @package suspended-flavor
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

class Novel_Search {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // AJAX endpoints
        add_action('wp_ajax_novel_live_search', [$this, 'live_search']);
        add_action('wp_ajax_nopriv_novel_live_search', [$this, 'live_search']);
        add_action('wp_ajax_novel_advanced_search', [$this, 'advanced_search']);
        add_action('wp_ajax_nopriv_novel_advanced_search', [$this, 'advanced_search']);
        add_action('wp_ajax_novel_trending_searches', [$this, 'get_trending']);
        add_action('wp_ajax_nopriv_novel_trending_searches', [$this, 'get_trending']);

        // Shortcode
        add_shortcode('novel_search', [$this, 'render_advanced_search_page']);

        // Enqueue
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // Track searches
        add_action('wp_ajax_novel_track_search', [$this, 'track_search']);
        add_action('wp_ajax_nopriv_novel_track_search', [$this, 'track_search']);
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'novel-search',
            get_template_directory_uri() . '/assets/css/search.css',
            ['novel-main-style'],
            FLAVOR_VERSION
        );

        wp_enqueue_script(
            'novel-search',
            get_template_directory_uri() . '/assets/js/search.js',
            ['jquery'],
            FLAVOR_VERSION,
            true
        );

        wp_localize_script('novel-search', 'novelSearch', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('novel_search_nonce'),
            'strings' => [
                'placeholder'   => 'جستجوی رمان، نویسنده، تگ...',
                'noResults'     => 'نتیجه‌ای یافت نشد 😕',
                'moreResults'   => 'نتایج بیشتر برای',
                'searching'     => 'در حال جستجو...',
                'minChars'      => 'حداقل ۲ کاراکتر وارد کنید',
                'novels'        => '📚 رمان‌ها',
                'authors'       => '✍️ نویسندگان',
                'tags'          => '🏷 تگ‌ها',
                'genres'        => '📂 ژانرها',
                'trending'      => '🔥 جستجوهای پرطرفدار',
                'popularNovels' => '📖 رمان‌های محبوب',
                'cancel'        => 'لغو',
                'filter'        => 'فیلتر',
                'clearFilters'  => 'پاک کردن فیلترها',
                'applyFilters'  => 'اعمال فیلتر',
                'found'         => 'رمان یافت شد',
                'noFilter'      => 'رمانی با این مشخصات یافت نشد 😕',
                'chapters'      => 'قسمت',
                'novels_count'  => 'رمان',
                'loading'       => 'در حال بارگذاری...',
            ],
        ]);
    }

    /**
     * ═══════════════════════════════════════
     * Live Search (Header)
     * ═══════════════════════════════════════
     */
    public function live_search() {
        check_ajax_referer('novel_search_nonce', 'nonce');

        $search_term = sanitize_text_field($_POST['query'] ?? '');

        if (mb_strlen($search_term) < 2) {
            wp_send_json_success([
                'novels'  => [],
                'authors' => [],
                'tags'    => [],
                'genres'  => [],
                'total'   => 0,
            ]);
        }

        $results = [
            'novels'  => $this->search_novels($search_term, 5),
            'authors' => $this->search_authors($search_term, 3),
            'tags'    => $this->search_tags($search_term, 5),
            'genres'  => $this->search_genres($search_term, 5),
        ];

        $results['total'] = count($results['novels']) 
                          + count($results['authors']) 
                          + count($results['tags'])
                          + count($results['genres']);

        $results['query'] = $search_term;

        wp_send_json_success($results);
    }

    /**
     * جستجوی رمان‌ها
     */
    private function search_novels($term, $limit = 5) {
        global $wpdb;

        $like = '%' . $wpdb->esc_like($term) . '%';

        // جستجو در عنوان + متاهای مهم
        $post_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT p.ID FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = 'novel_english_name'
             LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'novel_original_author'
             LEFT JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = 'novel_translator'
             LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
             LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
             LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
             WHERE p.post_type = 'novel'
               AND p.post_status = 'publish'
               AND (
                 p.post_title LIKE %s
                 OR pm1.meta_value LIKE %s
                 OR pm2.meta_value LIKE %s
                 OR pm3.meta_value LIKE %s
                 OR (tt.taxonomy IN ('genre','novel_tag') AND t.name LIKE %s)
               )
             ORDER BY 
               CASE WHEN p.post_title LIKE %s THEN 0 ELSE 1 END,
               p.post_title ASC
             LIMIT %d",
            $like, $like, $like, $like, $like, $like, $limit
        ));

        if (empty($post_ids)) return [];

        $novels = [];
        foreach ($post_ids as $pid) {
            $post = get_post($pid);
            if (!$post) continue;

            $rating  = (float) get_post_meta($pid, 'novel_rating_average', true);
            $type    = get_post_meta($pid, 'novel_type', true) ?: 'WN';
            $author  = get_post_meta($pid, 'novel_original_author', true);
            $thumb   = get_the_post_thumbnail_url($pid, 'thumbnail');

            // تعداد قسمت (cached)
            $chapter_count = (int) get_post_meta($pid, 'novel_chapter_count', true);
            if (!$chapter_count) {
                $chapter_count = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->posts} 
                     WHERE post_parent = %d AND post_type = 'chapter' AND post_status = 'publish'",
                    $pid
                ));
                update_post_meta($pid, 'novel_chapter_count', $chapter_count);
            }

            $novels[] = [
                'id'       => $pid,
                'title'    => $post->post_title,
                'url'      => get_permalink($pid),
                'thumb'    => $thumb ?: get_template_directory_uri() . '/assets/images/no-cover.png',
                'rating'   => round($rating, 1),
                'type'     => strtoupper($type),
                'author'   => $author,
                'chapters' => $chapter_count,
            ];
        }

        return $novels;
    }

    /**
     * جستجوی نویسندگان
     */
    private function search_authors($term, $limit = 3) {
        global $wpdb;

        $like = '%' . $wpdb->esc_like($term) . '%';

        $users = $wpdb->get_results($wpdb->prepare(
            "SELECT u.ID, u.display_name
             FROM {$wpdb->users} u
             INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = '{$wpdb->prefix}capabilities'
             WHERE u.display_name LIKE %s
               AND (um.meta_value LIKE %s OR um.meta_value LIKE %s OR um.meta_value LIKE %s)
             ORDER BY u.display_name ASC
             LIMIT %d",
            $like,
            '%author%',
            '%editor%',
            '%administrator%',
            $limit
        ));

        if (empty($users)) return [];

        $results = [];
        foreach ($users as $user) {
            // تعداد رمان‌ها
            $novel_count = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} 
                 WHERE post_author = %d AND post_type = 'novel' AND post_status = 'publish'",
                $user->ID
            ));

            // آواتار
            $avatar_id = (int) get_user_meta($user->ID, 'novel_avatar', true);
            $avatar_url = '';
            if ($avatar_id > 0 && $avatar_id <= 114) {
                $avatar_url = get_template_directory_uri() . '/assets/avatars/avatar-' . $avatar_id . '.png';
            }

            $results[] = [
                'id'           => $user->ID,
                'name'         => $user->display_name,
                'url'          => get_author_posts_url($user->ID),
                'avatar'       => $avatar_url ?: get_avatar_url($user->ID, ['size' => 64]),
                'novel_count'  => $novel_count,
            ];
        }

        return $results;
    }

    /**
     * جستجوی تگ‌ها
     */
    private function search_tags($term, $limit = 5) {
        $tags = get_terms([
            'taxonomy'   => 'novel_tag',
            'search'     => $term,
            'number'     => $limit,
            'hide_empty' => true,
            'orderby'    => 'count',
            'order'      => 'DESC',
        ]);

        if (is_wp_error($tags) || empty($tags)) return [];

        $results = [];
        foreach ($tags as $tag) {
            $results[] = [
                'id'    => $tag->term_id,
                'name'  => $tag->name,
                'slug'  => $tag->slug,
                'url'   => get_term_link($tag),
                'count' => $tag->count,
            ];
        }

        return $results;
    }

    /**
     * جستجوی ژانرها
     */
    private function search_genres($term, $limit = 5) {
        $genres = get_terms([
            'taxonomy'   => 'genre',
            'search'     => $term,
            'number'     => $limit,
            'hide_empty' => true,
            'orderby'    => 'count',
            'order'      => 'DESC',
        ]);

        if (is_wp_error($genres) || empty($genres)) return [];

        $results = [];
        foreach ($genres as $genre) {
            $results[] = [
                'id'    => $genre->term_id,
                'name'  => $genre->name,
                'slug'  => $genre->slug,
                'url'   => get_term_link($genre),
                'count' => $genre->count,
            ];
        }

        return $results;
    }

    /**
     * ═══════════════════════════════════════
     * Advanced Search (Page)
     * ═══════════════════════════════════════
     */
    public function advanced_search() {
        check_ajax_referer('novel_search_nonce', 'nonce');

        $page     = max(1, absint($_POST['page'] ?? 1));
        $per_page = 24;

        // Build query args
        $args = [
            'post_type'      => 'novel',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
        ];

        $meta_query = ['relation' => 'AND'];
        $tax_query  = ['relation' => 'AND'];
        $has_meta   = false;
        $has_tax    = false;

        // === متنی ===
        $search = sanitize_text_field($_POST['search'] ?? '');
        if ($search) {
            $args['s'] = $search;
        }

        // === ژانر (چندانتخابی) ===
        $genres = $this->sanitize_array($_POST['genres'] ?? []);
        if (!empty($genres)) {
            $tax_query[] = [
                'taxonomy' => 'genre',
                'field'    => 'slug',
                'terms'    => $genres,
                'operator' => 'IN',
            ];
            $has_tax = true;
        }

        // === تگ‌ها (چندانتخابی) ===
        $tags = $this->sanitize_array($_POST['tags'] ?? []);
        if (!empty($tags)) {
            $tax_query[] = [
                'taxonomy' => 'novel_tag',
                'field'    => 'slug',
                'terms'    => $tags,
                'operator' => 'IN',
            ];
            $has_tax = true;
        }

        // === وضعیت ===
        $status = sanitize_text_field($_POST['status'] ?? '');
        if ($status) {
            $tax_query[] = [
                'taxonomy' => 'novel_status',
                'field'    => 'slug',
                'terms'    => $status,
            ];
            $has_tax = true;
        }

        // === نوع رمان ===
        $type = sanitize_text_field($_POST['type'] ?? '');
        if ($type && in_array($type, ['LN', 'WN', 'ln', 'wn'], true)) {
            $meta_query[] = [
                'key'   => 'novel_type',
                'value' => strtoupper($type),
            ];
            $has_meta = true;
        }

        // === کشور ===
        $country = sanitize_text_field($_POST['country'] ?? '');
        $valid_countries = ['japan', 'china', 'korea', 'iran', 'other'];
        if ($country && in_array($country, $valid_countries, true)) {
            $meta_query[] = [
                'key'   => 'novel_country',
                'value' => $country,
            ];
            $has_meta = true;
        }

        // === حداقل امتیاز ===
        $min_rating = (float) ($_POST['min_rating'] ?? 0);
        if ($min_rating > 0 && $min_rating <= 5) {
            $meta_query[] = [
                'key'     => 'novel_rating_average',
                'value'   => $min_rating,
                'compare' => '>=',
                'type'    => 'DECIMAL(3,2)',
            ];
            $has_meta = true;
        }

        // === تعداد قسمت ===
        $min_chapters = absint($_POST['min_chapters'] ?? 0);
        $max_chapters = absint($_POST['max_chapters'] ?? 0);
        if ($min_chapters > 0) {
            $meta_query[] = [
                'key'     => 'novel_chapter_count',
                'value'   => $min_chapters,
                'compare' => '>=',
                'type'    => 'NUMERIC',
            ];
            $has_meta = true;
        }
        if ($max_chapters > 0) {
            $meta_query[] = [
                'key'     => 'novel_chapter_count',
                'value'   => $max_chapters,
                'compare' => '<=',
                'type'    => 'NUMERIC',
            ];
            $has_meta = true;
        }

        // === دسترسی ===
        $access = sanitize_text_field($_POST['access'] ?? '');
        if ($access === 'free') {
            $meta_query[] = [
                'relation' => 'OR',
                [
                    'key'     => 'novel_has_vip',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key'   => 'novel_has_vip',
                    'value' => '0',
                ],
            ];
            $has_meta = true;
        } elseif ($access === 'vip') {
            $meta_query[] = [
                'key'   => 'novel_has_vip',
                'value' => '1',
            ];
            $has_meta = true;
        }

        // Apply meta/tax queries
        if ($has_meta) {
            $args['meta_query'] = $meta_query;
        }
        if ($has_tax) {
            $args['tax_query'] = $tax_query;
        }

        // === مرتب‌سازی ===
        $sort = sanitize_text_field($_POST['sort'] ?? 'popular');
        switch ($sort) {
            case 'newest':
                $args['orderby'] = 'date';
                $args['order']   = 'DESC';
                break;

            case 'chapters':
                $args['meta_key'] = 'novel_chapter_count';
                $args['orderby']  = 'meta_value_num';
                $args['order']    = 'DESC';
                break;

            case 'rating':
                $args['meta_key'] = 'novel_rating_average';
                $args['orderby']  = 'meta_value_num';
                $args['order']    = 'DESC';
                break;

            case 'followers':
                $args['meta_key'] = 'novel_followers_count';
                $args['orderby']  = 'meta_value_num';
                $args['order']    = 'DESC';
                break;

            case 'bookmarks':
                $args['meta_key'] = 'novel_bookmarks_count';
                $args['orderby']  = 'meta_value_num';
                $args['order']    = 'DESC';
                break;

            case 'views':
                $args['meta_key'] = 'novel_total_views';
                $args['orderby']  = 'meta_value_num';
                $args['order']    = 'DESC';
                break;

            case 'updated':
                $args['orderby'] = 'modified';
                $args['order']   = 'DESC';
                break;

            case 'popular':
            default:
                $args['meta_key'] = 'novel_total_views';
                $args['orderby']  = 'meta_value_num';
                $args['order']    = 'DESC';
                break;
        }

        $query = new WP_Query($args);
        $novels = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $pid = get_the_ID();

                $rating        = (float) get_post_meta($pid, 'novel_rating_average', true);
                $type_val      = get_post_meta($pid, 'novel_type', true) ?: 'WN';
                $original_auth = get_post_meta($pid, 'novel_original_author', true);
                $chapter_count = (int) get_post_meta($pid, 'novel_chapter_count', true);
                $views         = (int) get_post_meta($pid, 'novel_total_views', true);
                $followers     = (int) get_post_meta($pid, 'novel_followers_count', true);
                $thumb         = get_the_post_thumbnail_url($pid, 'medium');

                // ژانرها
                $genre_terms = wp_get_post_terms($pid, 'genre', ['fields' => 'names']);
                $genre_list  = is_wp_error($genre_terms) ? [] : $genre_terms;

                // وضعیت
                $status_terms = wp_get_post_terms($pid, 'novel_status', ['fields' => 'names']);
                $status_val   = !is_wp_error($status_terms) && !empty($status_terms) ? $status_terms[0] : '';

                $novels[] = [
                    'id'            => $pid,
                    'title'         => get_the_title(),
                    'url'           => get_permalink(),
                    'thumb'         => $thumb ?: get_template_directory_uri() . '/assets/images/no-cover.png',
                    'rating'        => round($rating, 1),
                    'type'          => strtoupper($type_val),
                    'author'        => $original_auth,
                    'chapters'      => $chapter_count,
                    'views'         => $views,
                    'followers'     => $followers,
                    'genres'        => array_slice($genre_list, 0, 3),
                    'status'        => $status_val,
                    'excerpt'       => mb_substr(wp_strip_all_tags(get_the_excerpt()), 0, 120),
                ];
            }
            wp_reset_postdata();
        }

        wp_send_json_success([
            'novels'     => $novels,
            'total'      => $query->found_posts,
            'pages'      => $query->max_num_pages,
            'page'       => $page,
            'has_more'   => $page < $query->max_num_pages,
        ]);
    }

    /**
     * ═══════════════════════════════════════
     * Trending Searches
     * ═══════════════════════════════════════
     */
    public function get_trending() {
        $trending = get_transient('novel_trending_searches');

        if (false === $trending) {
            $trending = $this->calculate_trending();
            set_transient('novel_trending_searches', $trending, HOUR_IN_SECONDS);
        }

        wp_send_json_success(['trending' => $trending]);
    }

    /**
     * Calculate trending from recent searches
     */
    private function calculate_trending() {
        global $wpdb;
        $table = $wpdb->prefix . 'novel_search_log';

        // Check table exists
        $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
        if (!$exists) return [];

        $results = $wpdb->get_results(
            "SELECT search_term, COUNT(*) as cnt
             FROM {$table}
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY search_term
             ORDER BY cnt DESC
             LIMIT 10"
        );

        $trending = [];
        foreach ($results as $row) {
            $trending[] = $row->search_term;
        }

        // Fallback: popular novels
        if (empty($trending)) {
            $popular = get_posts([
                'post_type'      => 'novel',
                'posts_per_page' => 8,
                'meta_key'       => 'novel_total_views',
                'orderby'        => 'meta_value_num',
                'order'          => 'DESC',
                'fields'         => 'ids',
            ]);
            foreach ($popular as $pid) {
                $trending[] = get_the_title($pid);
            }
        }

        return $trending;
    }

    /**
     * Track search queries
     */
    public function track_search() {
        check_ajax_referer('novel_search_nonce', 'nonce');

        $term = sanitize_text_field($_POST['query'] ?? '');
        if (mb_strlen($term) < 2) {
            wp_send_json_success();
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'novel_search_log';

        $wpdb->insert($table, [
            'search_term' => mb_substr($term, 0, 100),
            'user_id'     => get_current_user_id() ?: null,
            'ip_hash'     => md5($_SERVER['REMOTE_ADDR'] ?? ''),
            'created_at'  => current_time('mysql'),
        ], ['%s', '%d', '%s', '%s']);

        wp_send_json_success();
    }

    /**
     * Get filter options for advanced search page
     */
    public function get_filter_options() {
        $options = [];

        // Genres
        $genres = get_terms([
            'taxonomy'   => 'genre',
            'hide_empty' => true,
            'orderby'    => 'count',
            'order'      => 'DESC',
        ]);
        $options['genres'] = [];
        if (!is_wp_error($genres)) {
            foreach ($genres as $g) {
                $options['genres'][] = [
                    'slug'  => $g->slug,
                    'name'  => $g->name,
                    'count' => $g->count,
                ];
            }
        }

        // Tags (top 50)
        $tags = get_terms([
            'taxonomy'   => 'novel_tag',
            'hide_empty' => true,
            'number'     => 50,
            'orderby'    => 'count',
            'order'      => 'DESC',
        ]);
        $options['tags'] = [];
        if (!is_wp_error($tags)) {
            foreach ($tags as $t) {
                $options['tags'][] = [
                    'slug'  => $t->slug,
                    'name'  => $t->name,
                    'count' => $t->count,
                ];
            }
        }

        // Statuses
        $statuses = get_terms([
            'taxonomy'   => 'novel_status',
            'hide_empty' => true,
        ]);
        $options['statuses'] = [];
        if (!is_wp_error($statuses)) {
            foreach ($statuses as $s) {
                $options['statuses'][] = [
                    'slug' => $s->slug,
                    'name' => $s->name,
                ];
            }
        }

        // Countries
        $options['countries'] = [
            ['slug' => 'japan', 'name' => 'ژاپن 🇯🇵'],
            ['slug' => 'china', 'name' => 'چین 🇨🇳'],
            ['slug' => 'korea', 'name' => 'کره 🇰🇷'],
            ['slug' => 'iran',  'name' => 'ایران 🇮🇷'],
            ['slug' => 'other', 'name' => 'سایر 🌍'],
        ];

        // Types
        $options['types'] = [
            ['slug' => 'LN', 'name' => 'لایت ناول (LN)'],
            ['slug' => 'WN', 'name' => 'وب ناول (WN)'],
        ];

        // Sort options
        $options['sorts'] = [
            ['slug' => 'popular',   'name' => 'محبوب‌ترین (بازدید)'],
            ['slug' => 'newest',    'name' => 'جدیدترین'],
            ['slug' => 'chapters',  'name' => 'بیشترین قسمت'],
            ['slug' => 'rating',    'name' => 'بالاترین امتیاز'],
            ['slug' => 'followers', 'name' => 'بیشترین دنبال‌کننده'],
            ['slug' => 'bookmarks', 'name' => 'بیشترین بوکمارک'],
            ['slug' => 'views',     'name' => 'بیشترین بازدید'],
            ['slug' => 'updated',   'name' => 'آخرین به‌روزرسانی'],
        ];

        return $options;
    }

    /**
     * Render advanced search page (shortcode)
     */
    public function render_advanced_search_page($atts) {
        ob_start();
        
        // Pass filter options to template
        $filter_options = $this->get_filter_options();
        set_query_var('novel_filter_options', $filter_options);
        
        get_template_part('templates/search/advanced-search');
        
        return ob_get_clean();
    }

    /**
     * Sanitize array input
     */
    private function sanitize_array($input) {
        if (!is_array($input)) {
            $input = explode(',', (string) $input);
        }
        return array_filter(array_map('sanitize_text_field', $input));
    }

    /**
     * Create search log table
     */
    public static function create_table() {
        global $wpdb;
        $table   = $wpdb->prefix . 'novel_search_log';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            search_term VARCHAR(100) NOT NULL,
            user_id BIGINT UNSIGNED DEFAULT NULL,
            ip_hash VARCHAR(32) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_term (search_term),
            KEY idx_created (created_at)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}