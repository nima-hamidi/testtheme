<?php
/**
 * Custom Post Types Registration
 * 
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register all custom post types
 */
function novel_register_post_types() {
    
    // ═══ Post Type: novel ═══
    $novel_labels = [
        'name'                  => 'رمان‌ها',
        'singular_name'         => 'رمان',
        'menu_name'             => 'رمان‌ها',
        'name_admin_bar'        => 'رمان',
        'add_new'               => 'افزودن رمان',
        'add_new_item'          => 'افزودن رمان جدید',
        'new_item'              => 'رمان جدید',
        'edit_item'             => 'ویرایش رمان',
        'view_item'             => 'مشاهده رمان',
        'all_items'             => 'همه رمان‌ها',
        'search_items'          => 'جستجوی رمان',
        'parent_item_colon'     => 'رمان مادر:',
        'not_found'             => 'رمانی یافت نشد.',
        'not_found_in_trash'    => 'رمانی در زباله‌دان یافت نشد.',
        'featured_image'        => 'تصویر جلد',
        'set_featured_image'    => 'انتخاب تصویر جلد',
        'remove_featured_image' => 'حذف تصویر جلد',
        'use_featured_image'    => 'استفاده به عنوان تصویر جلد',
        'archives'              => 'آرشیو رمان‌ها',
        'insert_into_item'      => 'درج در رمان',
        'uploaded_to_this_item' => 'آپلود شده برای این رمان',
        'filter_items_list'     => 'فیلتر لیست رمان‌ها',
        'items_list_navigation' => 'ناوبری لیست رمان‌ها',
        'items_list'            => 'لیست رمان‌ها',
    ];

    $novel_args = [
        'labels'              => $novel_labels,
        'public'              => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_nav_menus'   => true,
        'show_in_admin_bar'   => true,
        'query_var'           => true,
        'rewrite'             => [
            'slug'       => 'novel',
            'with_front' => false,
        ],
        'capability_type'     => 'post',
        'has_archive'         => true,
        'hierarchical'        => false,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-book',
        'show_in_rest'        => true,
        'rest_base'           => 'novels',
        'supports'            => [
            'title',
            'editor',
            'thumbnail',
            'comments',
            'author',
            'excerpt',
        ],
        'taxonomies'          => ['genre', 'novel_tag', 'novel_status'],
    ];

    register_post_type('novel', $novel_args);

    // ═══ Post Type: chapter ═══
    $chapter_labels = [
        'name'                  => 'قسمت‌ها',
        'singular_name'         => 'قسمت',
        'menu_name'             => 'قسمت‌ها',
        'name_admin_bar'        => 'قسمت',
        'add_new'               => 'افزودن قسمت',
        'add_new_item'          => 'افزودن قسمت جدید',
        'new_item'              => 'قسمت جدید',
        'edit_item'             => 'ویرایش قسمت',
        'view_item'             => 'مشاهده قسمت',
        'all_items'             => 'همه قسمت‌ها',
        'search_items'          => 'جستجوی قسمت',
        'not_found'             => 'قسمتی یافت نشد.',
        'not_found_in_trash'    => 'قسمتی در زباله‌دان یافت نشد.',
        'archives'              => 'آرشیو قسمت‌ها',
        'insert_into_item'      => 'درج در قسمت',
        'uploaded_to_this_item' => 'آپلود شده برای این قسمت',
        'filter_items_list'     => 'فیلتر لیست قسمت‌ها',
        'items_list_navigation' => 'ناوبری لیست قسمت‌ها',
        'items_list'            => 'لیست قسمت‌ها',
    ];

    $chapter_args = [
        'labels'              => $chapter_labels,
        'public'              => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_nav_menus'   => false,
        'show_in_admin_bar'   => true,
        'query_var'           => true,
        'rewrite'             => false, // Custom rewrite rules below
        'capability_type'     => 'post',
        'has_archive'         => false,
        'hierarchical'        => false,
        'menu_position'       => 6,
        'menu_icon'           => 'dashicons-media-text',
        'show_in_rest'        => true,
        'rest_base'           => 'chapters',
        'supports'            => [
            'title',
            'editor',
            'author',
            'comments',
        ],
    ];

    register_post_type('chapter', $chapter_args);
}
add_action('init', 'novel_register_post_types', 5);

/**
 * Custom rewrite rules for chapter URLs
 * Pattern: /novel/{novel-slug}/chapter-{number}/
 */
function novel_chapter_rewrite_rules() {
    // Main chapter URL: /novel/novel-name/chapter-45/
    add_rewrite_rule(
        'novel/([^/]+)/chapter-([0-9]+)/?$',
        'index.php?post_type=chapter&novel_slug=$matches[1]&chapter_number=$matches[2]',
        'top'
    );
}
add_action('init', 'novel_chapter_rewrite_rules', 10);

/**
 * Register custom query vars
 */
function novel_register_query_vars($vars) {
    $vars[] = 'novel_slug';
    $vars[] = 'chapter_number';
    return $vars;
}
add_filter('query_vars', 'novel_register_query_vars');

/**
 * Handle chapter query - resolve novel_slug + chapter_number to actual post
 */
function novel_resolve_chapter_query($query) {
    if (is_admin() || !$query->is_main_query()) {
        return;
    }

    $novel_slug    = $query->get('novel_slug');
    $chapter_num   = $query->get('chapter_number');

    if (empty($novel_slug) || $chapter_num === '') {
        return;
    }

    // Find the novel by slug
    $novel = get_page_by_path($novel_slug, OBJECT, 'novel');
    if (!$novel) {
        $query->set_404();
        return;
    }

    // Find the chapter by novel_id + chapter_number
    $chapter_query = new WP_Query([
        'post_type'      => 'chapter',
        'posts_per_page' => 1,
        'post_status'    => 'publish',
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'   => 'chapter_novel_id',
                'value' => $novel->ID,
                'type'  => 'NUMERIC',
            ],
            [
                'key'   => 'chapter_number',
                'value' => absint($chapter_num),
                'type'  => 'NUMERIC',
            ],
        ],
        'fields' => 'ids',
    ]);

    if ($chapter_query->have_posts()) {
        $chapter_id = $chapter_query->posts[0];
        $query->set('post_type', 'chapter');
        $query->set('p', $chapter_id);
        $query->set('novel_slug', '');
        $query->set('chapter_number', '');
        $query->is_single   = true;
        $query->is_singular = true;
        $query->is_archive  = false;
        $query->is_home     = false;
    } else {
        $query->set_404();
    }

    wp_reset_postdata();
}
add_action('pre_get_posts', 'novel_resolve_chapter_query');

/**
 * Generate proper chapter permalink
 * Returns: /novel/{novel-slug}/chapter-{number}/
 */
function novel_get_chapter_permalink($chapter_id) {
    $novel_id = get_post_meta($chapter_id, 'chapter_novel_id', true);
    $chapter_num = get_post_meta($chapter_id, 'chapter_number', true);
    
    if (!$novel_id || !$chapter_num) {
        return get_permalink($chapter_id);
    }
    
    $novel = get_post($novel_id);
    if (!$novel) {
        return get_permalink($chapter_id);
    }
    
    return home_url('/novel/' . $novel->post_name . '/chapter-' . $chapter_num . '/');
}

/**
 * Filter chapter post_type_link to use custom URL structure
 */
function novel_chapter_post_type_link($post_link, $post) {
    if ($post->post_type !== 'chapter') {
        return $post_link;
    }
    
    $custom_link = novel_get_chapter_permalink($post->ID);
    return $custom_link ?: $post_link;
}
add_filter('post_type_link', 'novel_chapter_post_type_link', 10, 2);

/**
 * Update chapter post title automatically
 * Format: "نام رمان - قسمت X: عنوان"
 */
function novel_auto_chapter_title($data, $postarr) {
    if ($data['post_type'] !== 'chapter' || empty($postarr['ID'])) {
        return $data;
    }

    $novel_id    = isset($postarr['chapter_novel_id']) 
                   ? $postarr['chapter_novel_id'] 
                   : get_post_meta($postarr['ID'], 'chapter_novel_id', true);
    $chapter_num = isset($postarr['chapter_number']) 
                   ? $postarr['chapter_number'] 
                   : get_post_meta($postarr['ID'], 'chapter_number', true);
    $chapter_title = isset($postarr['chapter_title']) 
                     ? $postarr['chapter_title'] 
                     : get_post_meta($postarr['ID'], 'chapter_title', true);

    if ($novel_id && $chapter_num) {
        $novel_name = get_the_title($novel_id);
        $title = $novel_name . ' - قسمت ' . $chapter_num;
        if (!empty($chapter_title)) {
            $title .= ': ' . $chapter_title;
        }
        $data['post_title'] = $title;
        $data['post_name']  = sanitize_title('chapter-' . $chapter_num);
    }

    return $data;
}
add_filter('wp_insert_post_data', 'novel_auto_chapter_title', 10, 2);

/**
 * Flush rewrite rules on theme activation
 */
function novel_flush_rewrite_rules() {
    novel_register_post_types();
    novel_chapter_rewrite_rules();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'novel_flush_rewrite_rules');

/**
 * Add chapter count column to novels admin list
 */
function novel_admin_columns($columns) {
    $new_columns = [];
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['chapter_count'] = 'تعداد قسمت‌ها';
            $new_columns['novel_type']    = 'نوع';
            $new_columns['novel_country'] = 'کشور';
        }
    }
    return $new_columns;
}
add_filter('manage_novel_posts_columns', 'novel_admin_columns');

/**
 * Populate custom admin columns for novels
 */
function novel_admin_column_content($column, $post_id) {
    switch ($column) {
        case 'chapter_count':
            $count = novel_get_chapter_count($post_id);
            $total = get_post_meta($post_id, 'novel_total_chapters', true);
            echo '<strong>' . esc_html($count) . '</strong>';
            if ($total) {
                echo ' / ' . esc_html($total);
            }
            break;
            
        case 'novel_type':
            $type = get_post_meta($post_id, 'novel_type', true);
            $types = novel_get_type_labels();
            if (isset($types[$type])) {
                $badge_class = $type === 'light_novel' ? 'ln' : 'wn';
                echo '<span class="novel-type-badge novel-type-' . esc_attr($badge_class) . '">' 
                     . esc_html($types[$type]) . '</span>';
            }
            break;
            
        case 'novel_country':
            $country = get_post_meta($post_id, 'novel_country', true);
            $countries = novel_get_country_labels();
            echo isset($countries[$country]) ? esc_html($countries[$country]) : '—';
            break;
    }
}
add_action('manage_novel_posts_custom_column', 'novel_admin_column_content', 10, 2);

/**
 * Add novel info column to chapters admin list
 */
function novel_chapter_admin_columns($columns) {
    $new_columns = [];
    foreach ($columns as $key => $value) {
        if ($key === 'title') {
            $new_columns['chapter_num']  = 'شماره';
            $new_columns[$key]           = $value;
            $new_columns['chapter_novel'] = 'رمان';
            $new_columns['chapter_vip']   = 'نوع';
            $new_columns['word_count']    = 'کلمات';
        } else {
            $new_columns[$key] = $value;
        }
    }
    return $new_columns;
}
add_filter('manage_chapter_posts_columns', 'novel_chapter_admin_columns');

/**
 * Populate custom admin columns for chapters
 */
function novel_chapter_admin_column_content($column, $post_id) {
    switch ($column) {
        case 'chapter_num':
            $num = get_post_meta($post_id, 'chapter_number', true);
            echo '<strong>' . esc_html($num ?: '—') . '</strong>';
            break;
            
        case 'chapter_novel':
            $novel_id = get_post_meta($post_id, 'chapter_novel_id', true);
            if ($novel_id) {
                $novel = get_post($novel_id);
                if ($novel) {
                    echo '<a href="' . get_edit_post_link($novel_id) . '">' 
                         . esc_html($novel->post_title) . '</a>';
                } else {
                    echo '<span style="color:#dc3545;">رمان حذف شده</span>';
                }
            } else {
                echo '—';
            }
            break;
            
        case 'chapter_vip':
            $is_vip = get_post_meta($post_id, 'chapter_is_vip', true);
            if ($is_vip) {
                $price = get_post_meta($post_id, 'chapter_coin_price', true);
                echo '<span class="novel-vip-badge">VIP 👑</span>';
                if ($price) {
                    echo ' <small>' . esc_html($price) . ' سکه</small>';
                }
            } else {
                echo '<span class="novel-free-badge">رایگان ✅</span>';
            }
            break;
            
        case 'word_count':
            $wc = get_post_meta($post_id, 'chapter_word_count', true);
            echo $wc ? number_format_i18n($wc) : '—';
            break;
    }
}
add_action('manage_chapter_posts_custom_column', 'novel_chapter_admin_column_content', 10, 2);

/**
 * Make custom columns sortable
 */
function novel_sortable_columns($columns) {
    $columns['chapter_count'] = 'chapter_count';
    return $columns;
}
add_filter('manage_edit-novel_sortable_columns', 'novel_sortable_columns');

function novel_chapter_sortable_columns($columns) {
    $columns['chapter_num'] = 'chapter_num';
    $columns['word_count']  = 'word_count';
    return $columns;
}
add_filter('manage_edit-chapter_sortable_columns', 'novel_chapter_sortable_columns');

/**
 * Handle sorting by custom columns
 */
function novel_admin_sort_columns($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    
    $orderby = $query->get('orderby');
    
    if ($orderby === 'chapter_num') {
        $query->set('meta_key', 'chapter_number');
        $query->set('orderby', 'meta_value_num');
    }
    
    if ($orderby === 'word_count') {
        $query->set('meta_key', 'chapter_word_count');
        $query->set('orderby', 'meta_value_num');
    }
}
add_action('pre_get_posts', 'novel_admin_sort_columns');

/**
 * Add novel filter dropdown in chapters admin
 */
function novel_chapter_admin_filter() {
    global $typenow;
    
    if ($typenow !== 'chapter') {
        return;
    }
    
    $novels = get_posts([
        'post_type'      => 'novel',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);
    
    $selected = isset($_GET['filter_novel']) ? absint($_GET['filter_novel']) : 0;
    
    echo '<select name="filter_novel">';
    echo '<option value="">همه رمان‌ها</option>';
    foreach ($novels as $novel) {
        printf(
            '<option value="%d" %s>%s</option>',
            $novel->ID,
            selected($selected, $novel->ID, false),
            esc_html($novel->post_title)
        );
    }
    echo '</select>';
}
add_action('restrict_manage_posts', 'novel_chapter_admin_filter');

/**
 * Apply novel filter in chapters admin
 */
function novel_chapter_admin_filter_query($query) {
    global $pagenow, $typenow;
    
    if ($pagenow !== 'edit.php' || $typenow !== 'chapter' || !is_admin()) {
        return;
    }
    
    if (!empty($_GET['filter_novel'])) {
        $query->set('meta_key', 'chapter_novel_id');
        $query->set('meta_value', absint($_GET['filter_novel']));
        $query->set('meta_type', 'NUMERIC');
    }
}
add_action('pre_get_posts', 'novel_chapter_admin_filter_query');

/**
 * Count chapters for a novel
 */
function novel_get_chapter_count($novel_id, $status = 'publish') {
    global $wpdb;
    
    $count = wp_cache_get('novel_chapter_count_' . $novel_id . '_' . $status, 'novel');
    
    if (false === $count) {
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'chapter'
             AND p.post_status = %s
             AND pm.meta_key = 'chapter_novel_id'
             AND pm.meta_value = %d",
            $status,
            $novel_id
        ));
        
        wp_cache_set('novel_chapter_count_' . $novel_id . '_' . $status, $count, 'novel', 3600);
    }
    
    return (int) $count;
}

/**
 * Count free and VIP chapters for a novel
 */
function novel_get_chapter_counts_by_type($novel_id) {
    global $wpdb;
    
    $cache_key = 'novel_chapter_type_counts_' . $novel_id;
    $counts = wp_cache_get($cache_key, 'novel');
    
    if (false === $counts) {
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                COALESCE(pm2.meta_value, '0') as is_vip,
                COUNT(*) as cnt
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'chapter_novel_id'
             LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'chapter_is_vip'
             WHERE p.post_type = 'chapter'
             AND p.post_status = 'publish'
             AND pm.meta_value = %d
             GROUP BY is_vip",
            $novel_id
        ));
        
        $counts = ['free' => 0, 'vip' => 0, 'total' => 0];
        foreach ($results as $row) {
            if ($row->is_vip === '1') {
                $counts['vip'] = (int) $row->cnt;
            } else {
                $counts['free'] = (int) $row->cnt;
            }
        }
        $counts['total'] = $counts['free'] + $counts['vip'];
        
        wp_cache_set($cache_key, $counts, 'novel', 3600);
    }
    
    return $counts;
}

/**
 * Invalidate chapter count cache when chapter is saved/deleted
 */
function novel_invalidate_chapter_cache($post_id) {
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'chapter') {
        return;
    }
    
    $novel_id = get_post_meta($post_id, 'chapter_novel_id', true);
    if ($novel_id) {
        wp_cache_delete('novel_chapter_count_' . $novel_id . '_publish', 'novel');
        wp_cache_delete('novel_chapter_count_' . $novel_id . '_any', 'novel');
        wp_cache_delete('novel_chapter_type_counts_' . $novel_id, 'novel');
    }
}
add_action('save_post_chapter', 'novel_invalidate_chapter_cache');
add_action('delete_post', 'novel_invalidate_chapter_cache');
add_action('trash_post', 'novel_invalidate_chapter_cache');

/**
 * Get type labels
 */
function novel_get_type_labels() {
    return [
        'web_novel'   => 'وب ناول (WN)',
        'light_novel' => 'لایت ناول (LN)',
    ];
}

/**
 * Get country labels with flags
 */
function novel_get_country_labels() {
    return [
        'japan'  => '🇯🇵 ژاپن',
        'china'  => '🇨🇳 چین',
        'korea'  => '🇰🇷 کره',
        'iran'   => '🇮🇷 ایران',
        'other'  => '🌍 سایر',
    ];
}

/**
 * Get country labels without flags (for forms)
 */
function novel_get_country_labels_plain() {
    return [
        'japan'  => 'ژاپن',
        'china'  => 'چین',
        'korea'  => 'کره',
        'iran'   => 'ایران',
        'other'  => 'سایر',
    ];
}

/**
 * Count words in content (supports Persian/Arabic)
 */
function novel_count_words($content) {
    $content = wp_strip_all_tags($content);
    $content = strip_shortcodes($content);
    $content = trim($content);
    
    if (empty($content)) {
        return 0;
    }
    
    // Split by whitespace (works for Persian/Arabic/English)
    $words = preg_split('/[\s\n\r\t]+/u', $content, -1, PREG_SPLIT_NO_EMPTY);
    
    return count($words);
}

/**
 * Calculate reading time in minutes
 */
function novel_reading_time($word_count, $wpm = 200) {
    if ($word_count <= 0) {
        return 1;
    }
    return max(1, (int) ceil($word_count / $wpm));
}

/**
 * Auto-calculate word count and reading time on chapter save
 */
function novel_calculate_chapter_stats($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'chapter') {
        return;
    }
    
    $word_count = novel_count_words($post->post_content);
    $reading_time = novel_reading_time($word_count);
    
    update_post_meta($post_id, 'chapter_word_count', $word_count);
    update_post_meta($post_id, 'chapter_reading_time', $reading_time);
}
add_action('save_post_chapter', 'novel_calculate_chapter_stats', 20);

/**
 * Get navigation chapters (prev/next)
 */
function novel_get_adjacent_chapter($chapter_id, $direction = 'next') {
    $novel_id    = get_post_meta($chapter_id, 'chapter_novel_id', true);
    $chapter_num = get_post_meta($chapter_id, 'chapter_number', true);
    
    if (!$novel_id || !$chapter_num) {
        return null;
    }
    
    $compare = $direction === 'next' ? '>' : '<';
    $order   = $direction === 'next' ? 'ASC' : 'DESC';
    
    $adjacent = new WP_Query([
        'post_type'      => 'chapter',
        'posts_per_page' => 1,
        'post_status'    => 'publish',
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'   => 'chapter_novel_id',
                'value' => $novel_id,
                'type'  => 'NUMERIC',
            ],
            [
                'key'     => 'chapter_number',
                'value'   => $chapter_num,
                'compare' => $compare,
                'type'    => 'NUMERIC',
            ],
        ],
        'meta_key' => 'chapter_number',
        'orderby'  => 'meta_value_num',
        'order'    => $order,
    ]);
    
    if ($adjacent->have_posts()) {
        return $adjacent->posts[0];
    }
    
    return null;
}

/**
 * Get all chapters for a novel (ordered)
 */
function novel_get_chapters($novel_id, $args = []) {
    $defaults = [
        'post_type'      => 'chapter',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => [
            [
                'key'   => 'chapter_novel_id',
                'value' => $novel_id,
                'type'  => 'NUMERIC',
            ],
        ],
        'meta_key'       => 'chapter_number',
        'orderby'        => 'meta_value_num',
        'order'          => 'ASC',
    ];
    
    $args = wp_parse_args($args, $defaults);
    
    return new WP_Query($args);
}

/**
 * Get latest chapter number for a novel
 */
function novel_get_latest_chapter_number($novel_id) {
    global $wpdb;
    
    $latest = $wpdb->get_var($wpdb->prepare(
        "SELECT MAX(CAST(pm.meta_value AS UNSIGNED))
         FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
         INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
         WHERE pm.meta_key = 'chapter_number'
         AND pm2.meta_key = 'chapter_novel_id'
         AND pm2.meta_value = %d
         AND p.post_type = 'chapter'
         AND p.post_status IN ('publish', 'future', 'draft')",
        $novel_id
    ));
    
    return $latest ? (int) $latest : 0;
}

/**
 * Check if chapter number is duplicate
 */
function novel_is_chapter_duplicate($novel_id, $chapter_number, $exclude_id = 0) {
    global $wpdb;
    
    $sql = $wpdb->prepare(
        "SELECT COUNT(*)
         FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = 'chapter_novel_id'
         INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'chapter_number'
         WHERE p.post_type = 'chapter'
         AND p.post_status != 'trash'
         AND pm1.meta_value = %d
         AND pm2.meta_value = %d",
        $novel_id,
        $chapter_number
    );
    
    if ($exclude_id) {
        $sql .= $wpdb->prepare(" AND p.ID != %d", $exclude_id);
    }
    
    return (int) $wpdb->get_var($sql) > 0;
}