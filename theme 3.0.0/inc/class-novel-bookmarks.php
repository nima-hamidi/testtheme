<?php
/**
 * Novel Bookmarks, Library & Reading History
 * 
 * کتابخانه شخصی + بوکمارک + تاریخچه مطالعه + ادامه مطالعه
 *
 * @package suspended-flavor
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

class Novel_Bookmarks {

    private static $instance = null;

    /** وضعیت‌های مجاز کتابخانه */
    private $valid_statuses = ['reading', 'plan_to_read', 'completed', 'dropped', 'on_hold'];

    /** برچسب وضعیت‌ها */
    private $status_labels = [
        'reading'      => ['label' => 'در حال خواندن',  'icon' => '📖', 'color' => '#3b82f6'],
        'plan_to_read' => ['label' => 'می‌خوام بخوانم', 'icon' => '📋', 'color' => '#f59e0b'],
        'completed'    => ['label' => 'تکمیل شده',     'icon' => '✅', 'color' => '#10b981'],
        'dropped'      => ['label' => 'رها شده',       'icon' => '❌', 'color' => '#ef4444'],
        'on_hold'      => ['label' => 'نگه‌داشته',     'icon' => '⏸', 'color' => '#8b5cf6'],
    ];

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Library AJAX
        add_action('wp_ajax_novel_add_to_library', [$this, 'add_to_library']);
        add_action('wp_ajax_novel_remove_from_library', [$this, 'remove_from_library']);
        add_action('wp_ajax_novel_change_library_status', [$this, 'change_status']);
        add_action('wp_ajax_novel_get_library', [$this, 'get_library']);
        add_action('wp_ajax_novel_get_library_status', [$this, 'get_library_status']);

        // History AJAX
        add_action('wp_ajax_novel_save_scroll_position', [$this, 'save_scroll_position']);
        add_action('wp_ajax_novel_get_scroll_position', [$this, 'get_scroll_position']);
        add_action('wp_ajax_novel_delete_history_item', [$this, 'delete_history_item']);
        add_action('wp_ajax_novel_clear_history', [$this, 'clear_history']);
        add_action('wp_ajax_novel_get_history', [$this, 'get_history']);

        // Continue reading
        add_action('wp_ajax_novel_get_continue_reading', [$this, 'get_continue_reading']);

        // Auto-track reading
        add_action('template_redirect', [$this, 'auto_track_reading']);

        // Enqueue
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        wp_enqueue_script(
            'novel-bookmark',
            get_template_directory_uri() . '/assets/js/bookmark.js',
            ['jquery'],
            FLAVOR_VERSION,
            true
        );

        wp_localize_script('novel-bookmark', 'novelBookmark', [
            'ajaxUrl'  => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('novel_bookmark_nonce'),
            'loggedIn' => is_user_logged_in(),
            'statuses' => $this->status_labels,
            'strings'  => [
                'added'         => 'به کتابخانه اضافه شد ✓',
                'removed'       => 'از کتابخانه حذف شد',
                'statusChanged' => 'وضعیت تغییر کرد ✓',
                'loginRequired' => 'برای افزودن به کتابخانه وارد شوید',
                'error'         => 'خطایی رخ داد',
                'confirmRemove' => 'از حذف این رمان از کتابخانه مطمئنید؟',
                'confirmClear'  => 'تمام تاریخچه مطالعه پاک شود؟',
                'historyCleared'=> 'تاریخچه پاک شد ✓',
                'historyDeleted'=> 'آیتم حذف شد',
                'continueFrom'  => 'از جایی که بودید ادامه می‌دهید ☺',
                'addToLibrary'  => 'افزودن به کتابخانه',
                'inLibrary'     => 'در کتابخانه',
            ],
        ]);
    }

    /* ═══════════════════════════════════════
       Library Management
       ═══════════════════════════════════════ */

    /**
     * افزودن به کتابخانه (AJAX)
     */
    public function add_to_library() {
        check_ajax_referer('novel_bookmark_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'ابتدا وارد حساب شوید']);
        }

        $user_id  = get_current_user_id();
        $novel_id = absint($_POST['novel_id'] ?? 0);
        $status   = sanitize_text_field($_POST['status'] ?? 'reading');

        if (!$novel_id || get_post_type($novel_id) !== 'novel') {
            wp_send_json_error(['message' => 'رمان نامعتبر']);
        }

        if (!in_array($status, $this->valid_statuses, true)) {
            $status = 'reading';
        }

        global $wpdb;
        $table = $wpdb->prefix . 'novel_user_library';

        // UPSERT
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, list_type FROM {$table} WHERE user_id = %d AND novel_id = %d",
            $user_id, $novel_id
        ));

        if ($existing) {
            $wpdb->update($table, [
                'list_type'  => $status,
                'updated_at' => current_time('mysql'),
            ], ['id' => $existing->id], ['%s', '%s'], ['%d']);
        } else {
            // Calculate initial progress
            $progress = $this->calculate_progress($novel_id, 0);

            $wpdb->insert($table, [
                'user_id'          => $user_id,
                'novel_id'         => $novel_id,
                'list_type'        => $status,
                'last_chapter_id'  => 0,
                'progress_percent' => 0,
                'added_at'         => current_time('mysql'),
                'updated_at'       => current_time('mysql'),
            ], ['%d', '%d', '%s', '%d', '%f', '%s', '%s']);
        }

        // Update novel bookmark count cache
        $this->update_bookmark_count($novel_id);

        $status_info = $this->status_labels[$status] ?? $this->status_labels['reading'];

        wp_send_json_success([
            'message' => $status_info['icon'] . ' ' . $status_info['label'],
            'status'  => $status,
            'label'   => $status_info['label'],
            'icon'    => $status_info['icon'],
        ]);
    }

    /**
     * حذف از کتابخانه (AJAX)
     */
    public function remove_from_library() {
        check_ajax_referer('novel_bookmark_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'ابتدا وارد حساب شوید']);
        }

        $user_id  = get_current_user_id();
        $novel_id = absint($_POST['novel_id'] ?? 0);

        if (!$novel_id) {
            wp_send_json_error(['message' => 'شناسه نامعتبر']);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'novel_user_library';

        $wpdb->delete($table, [
            'user_id'  => $user_id,
            'novel_id' => $novel_id,
        ], ['%d', '%d']);

        $this->update_bookmark_count($novel_id);

        wp_send_json_success(['message' => 'از کتابخانه حذف شد']);
    }

    /**
     * تغییر وضعیت (AJAX)
     */
    public function change_status() {
        check_ajax_referer('novel_bookmark_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'ابتدا وارد حساب شوید']);
        }

        $user_id  = get_current_user_id();
        $novel_id = absint($_POST['novel_id'] ?? 0);
        $status   = sanitize_text_field($_POST['status'] ?? '');

        if (!$novel_id || !in_array($status, $this->valid_statuses, true)) {
            wp_send_json_error(['message' => 'داده نامعتبر']);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'novel_user_library';

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE user_id = %d AND novel_id = %d",
            $user_id, $novel_id
        ));

        if ($existing) {
            $wpdb->update($table, [
                'list_type'  => $status,
                'updated_at' => current_time('mysql'),
            ], ['id' => $existing], ['%s', '%s'], ['%d']);
        } else {
            // Auto-add
            return $this->add_to_library();
        }

        $info = $this->status_labels[$status];

        wp_send_json_success([
            'message' => $info['icon'] . ' ' . $info['label'],
            'status'  => $status,
            'label'   => $info['label'],
            'icon'    => $info['icon'],
        ]);
    }

    /**
     * گرفتن وضعیت کتابخانه رمان (AJAX)
     */
    public function get_library_status() {
        check_ajax_referer('novel_bookmark_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_success(['in_library' => false]);
        }

        $user_id  = get_current_user_id();
        $novel_id = absint($_POST['novel_id'] ?? 0);

        if (!$novel_id) {
            wp_send_json_success(['in_library' => false]);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'novel_user_library';

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT list_type, last_chapter_id, progress_percent 
             FROM {$table} WHERE user_id = %d AND novel_id = %d",
            $user_id, $novel_id
        ));

        if (!$row) {
            wp_send_json_success(['in_library' => false]);
        }

        $info = $this->status_labels[$row->list_type] ?? $this->status_labels['reading'];

        // Next chapter URL
        $next_chapter_url = '';
        if ($row->last_chapter_id) {
            $next_id = $this->get_next_chapter($novel_id, $row->last_chapter_id);
            if ($next_id) {
                $next_chapter_url = get_permalink($next_id);
            } else {
                $next_chapter_url = get_permalink($row->last_chapter_id);
            }
        }

        wp_send_json_success([
            'in_library'   => true,
            'status'       => $row->list_type,
            'label'        => $info['label'],
            'icon'         => $info['icon'],
            'color'        => $info['color'],
            'progress'     => round($row->progress_percent, 1),
            'last_chapter' => (int) $row->last_chapter_id,
            'continue_url' => $next_chapter_url,
        ]);
    }

    /**
     * گرفتن کتابخانه کاربر (AJAX - داشبورد)
     */
    public function get_library() {
        check_ajax_referer('novel_bookmark_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'ابتدا وارد حساب شوید']);
        }

        $user_id = get_current_user_id();
        $status  = sanitize_text_field($_POST['status'] ?? '');
        $sort    = sanitize_text_field($_POST['sort'] ?? 'updated');
        $page    = max(1, absint($_POST['page'] ?? 1));
        $per_page = 12;
        $offset   = ($page - 1) * $per_page;

        global $wpdb;
        $table = $wpdb->prefix . 'novel_user_library';

        $where = "WHERE l.user_id = %d";
        $params = [$user_id];

        if ($status && in_array($status, $this->valid_statuses, true)) {
            $where .= " AND l.list_type = %s";
            $params[] = $status;
        }

        // Sort
        $orderby = 'l.updated_at DESC';
        switch ($sort) {
            case 'name':
                $orderby = 'p.post_title ASC';
                break;
            case 'rating':
                $orderby = 'CAST(pm_rating.meta_value AS DECIMAL(3,2)) DESC';
                break;
            case 'progress':
                $orderby = 'l.progress_percent DESC';
                break;
            case 'added':
                $orderby = 'l.added_at DESC';
                break;
            case 'updated':
            default:
                $orderby = 'l.updated_at DESC';
                break;
        }

        // Count total
        $count_query = "SELECT COUNT(*) FROM {$table} l {$where}";
        $total = (int) $wpdb->get_var($wpdb->prepare($count_query, $params));

        // Get items
        $query = "SELECT l.*, p.post_title, p.post_author
                  FROM {$table} l
                  INNER JOIN {$wpdb->posts} p ON l.novel_id = p.ID AND p.post_status = 'publish'
                  LEFT JOIN {$wpdb->postmeta} pm_rating ON p.ID = pm_rating.post_id AND pm_rating.meta_key = 'novel_rating_average'
                  {$where}
                  ORDER BY {$orderby}
                  LIMIT %d OFFSET %d";
        
        $all_params = array_merge($params, [$per_page, $offset]);
        $items = $wpdb->get_results($wpdb->prepare($query, $all_params));

        $novels = [];
        foreach ($items as $item) {
            $pid = $item->novel_id;
            $rating = (float) get_post_meta($pid, 'novel_rating_average', true);
            $type = get_post_meta($pid, 'novel_type', true) ?: 'WN';
            $author = get_post_meta($pid, 'novel_original_author', true);
            $thumb = get_the_post_thumbnail_url($pid, 'thumbnail');
            $chapter_count = (int) get_post_meta($pid, 'novel_chapter_count', true);

            // Last chapter info
            $last_ch_title = '';
            $last_ch_num = 0;
            if ($item->last_chapter_id) {
                $last_ch = get_post($item->last_chapter_id);
                if ($last_ch) {
                    $last_ch_title = $last_ch->post_title;
                    $last_ch_num = (int) get_post_meta($item->last_chapter_id, 'chapter_number', true);
                }
            }

            // Continue URL
            $continue_url = '';
            if ($item->last_chapter_id) {
                $next_id = $this->get_next_chapter($pid, $item->last_chapter_id);
                $continue_url = get_permalink($next_id ?: $item->last_chapter_id);
            } else {
                $first = $this->get_first_chapter($pid);
                if ($first) $continue_url = get_permalink($first);
            }

            $status_info = $this->status_labels[$item->list_type] ?? $this->status_labels['reading'];

            $novels[] = [
                'id'             => $pid,
                'title'          => $item->post_title,
                'url'            => get_permalink($pid),
                'thumb'          => $thumb ?: get_template_directory_uri() . '/assets/images/no-cover.png',
                'rating'         => round($rating, 1),
                'type'           => strtoupper($type),
                'author'         => $author,
                'chapter_count'  => $chapter_count,
                'status'         => $item->list_type,
                'status_label'   => $status_info['label'],
                'status_icon'    => $status_info['icon'],
                'status_color'   => $status_info['color'],
                'progress'       => round($item->progress_percent, 1),
                'last_chapter'   => $last_ch_num,
                'last_ch_title'  => $last_ch_title,
                'continue_url'   => $continue_url,
                'updated_at'     => $item->updated_at,
                'updated_human'  => human_time_diff(strtotime($item->updated_at), current_time('timestamp')) . ' پیش',
            ];
        }

        // Status counts
        $counts_raw = $wpdb->get_results($wpdb->prepare(
            "SELECT list_type, COUNT(*) as cnt FROM {$table} WHERE user_id = %d GROUP BY list_type",
            $user_id
        ));
        $counts = ['all' => 0];
        foreach ($this->valid_statuses as $s) $counts[$s] = 0;
        foreach ($counts_raw as $c) {
            $counts[$c->list_type] = (int) $c->cnt;
            $counts['all'] += (int) $c->cnt;
        }

        wp_send_json_success([
            'novels'   => $novels,
            'total'    => $total,
            'pages'    => ceil($total / $per_page),
            'page'     => $page,
            'counts'   => $counts,
            'has_more' => $page < ceil($total / $per_page),
        ]);
    }

    /* ═══════════════════════════════════════
       Auto-Track Reading
       ═══════════════════════════════════════ */

    /**
     * ثبت خودکار خواندن قسمت
     */
    public function auto_track_reading() {
        if (!is_singular('chapter') || !is_user_logged_in()) return;

        $user_id    = get_current_user_id();
        $chapter_id = get_the_ID();
        $novel_id   = (int) get_post_meta($chapter_id, 'chapter_novel_id', true);

        if (!$novel_id) {
            // Try post_parent
            $novel_id = wp_get_post_parent_id($chapter_id);
        }

        if (!$novel_id) return;

        global $wpdb;

        // 1. Record reading history
        $history_table = $wpdb->prefix . 'novel_reading_history';
        
        $existing_history = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$history_table} WHERE user_id = %d AND chapter_id = %d",
            $user_id, $chapter_id
        ));

        if ($existing_history) {
            $wpdb->update($history_table, [
                'read_at' => current_time('mysql'),
            ], ['id' => $existing_history], ['%s'], ['%d']);
        } else {
            $wpdb->insert($history_table, [
                'user_id'    => $user_id,
                'novel_id'   => $novel_id,
                'chapter_id' => $chapter_id,
                'read_at'    => current_time('mysql'),
            ], ['%d', '%d', '%d', '%s']);
        }

        // 2. Update library
        $library_table = $wpdb->prefix . 'novel_user_library';
        
        $library_entry = $wpdb->get_row($wpdb->prepare(
            "SELECT id, list_type FROM {$library_table} WHERE user_id = %d AND novel_id = %d",
            $user_id, $novel_id
        ));

        $progress = $this->calculate_progress($novel_id, $chapter_id);

        if ($library_entry) {
            $wpdb->update($library_table, [
                'last_chapter_id'  => $chapter_id,
                'progress_percent' => $progress,
                'updated_at'       => current_time('mysql'),
            ], ['id' => $library_entry->id], ['%d', '%f', '%s'], ['%d']);
        } else {
            // Auto-add to library as "reading"
            $wpdb->insert($library_table, [
                'user_id'          => $user_id,
                'novel_id'         => $novel_id,
                'list_type'        => 'reading',
                'last_chapter_id'  => $chapter_id,
                'progress_percent' => $progress,
                'added_at'         => current_time('mysql'),
                'updated_at'       => current_time('mysql'),
            ], ['%d', '%d', '%s', '%d', '%f', '%s', '%s']);
        }

        // 3. Invalidate caches
        delete_transient("novel_read_chapters_{$user_id}_{$novel_id}");
    }

    /* ═══════════════════════════════════════
       Reading History
       ═══════════════════════════════════════ */

    /**
     * ذخیره scroll position (AJAX)
     */
    public function save_scroll_position() {
        check_ajax_referer('novel_bookmark_nonce', 'nonce');

        if (!is_user_logged_in()) wp_send_json_error();

        $user_id    = get_current_user_id();
        $chapter_id = absint($_POST['chapter_id'] ?? 0);
        $position   = floatval($_POST['position'] ?? 0);

        if (!$chapter_id || $position < 0 || $position > 100) {
            wp_send_json_error();
        }

        global $wpdb;
        $table = $wpdb->prefix . 'novel_reading_history';

        $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET scroll_position = %f WHERE user_id = %d AND chapter_id = %d",
            $position, $user_id, $chapter_id
        ));

        wp_send_json_success();
    }

    /**
     * گرفتن scroll position (AJAX)
     */
    public function get_scroll_position() {
        check_ajax_referer('novel_bookmark_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_success(['position' => 0]);
        }

        $user_id    = get_current_user_id();
        $chapter_id = absint($_POST['chapter_id'] ?? 0);

        if (!$chapter_id) {
            wp_send_json_success(['position' => 0]);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'novel_reading_history';

        $position = $wpdb->get_var($wpdb->prepare(
            "SELECT scroll_position FROM {$table} WHERE user_id = %d AND chapter_id = %d",
            $user_id, $chapter_id
        ));

        wp_send_json_success(['position' => floatval($position)]);
    }

    /**
     * گرفتن تاریخچه (AJAX)
     */
    public function get_history() {
        check_ajax_referer('novel_bookmark_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'ابتدا وارد حساب شوید']);
        }

        $user_id  = get_current_user_id();
        $page     = max(1, absint($_POST['page'] ?? 1));
        $per_page = 30;
        $offset   = ($page - 1) * $per_page;

        global $wpdb;
        $table = $wpdb->prefix . 'novel_reading_history';

        $total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE user_id = %d",
            $user_id
        ));

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT h.*, p.post_title as chapter_title, n.post_title as novel_title
             FROM {$table} h
             INNER JOIN {$wpdb->posts} p ON h.chapter_id = p.ID
             INNER JOIN {$wpdb->posts} n ON h.novel_id = n.ID
             WHERE h.user_id = %d
             ORDER BY h.read_at DESC
             LIMIT %d OFFSET %d",
            $user_id, $per_page, $offset
        ));

        // Group by date
        $grouped = [];
        foreach ($items as $item) {
            $date_key = date('Y-m-d', strtotime($item->read_at));
            $today = current_time('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day', current_time('timestamp')));

            if ($date_key === $today) {
                $group_label = 'امروز';
            } elseif ($date_key === $yesterday) {
                $group_label = 'دیروز';
            } else {
                // Format Persian date (fallback to Gregorian)
                $group_label = date_i18n('j F Y', strtotime($item->read_at));
            }

            $thumb = get_the_post_thumbnail_url($item->novel_id, 'thumbnail');
            $ch_number = (int) get_post_meta($item->chapter_id, 'chapter_number', true);

            $grouped[$group_label][] = [
                'chapter_id'    => $item->chapter_id,
                'novel_id'      => $item->novel_id,
                'chapter_title' => $item->chapter_title,
                'chapter_num'   => $ch_number,
                'novel_title'   => $item->novel_title,
                'novel_url'     => get_permalink($item->novel_id),
                'chapter_url'   => get_permalink($item->chapter_id),
                'thumb'         => $thumb ?: get_template_directory_uri() . '/assets/images/no-cover.png',
                'time'          => date_i18n('H:i', strtotime($item->read_at)),
                'read_at'       => $item->read_at,
                'scroll'        => floatval($item->scroll_position),
            ];
        }

        wp_send_json_success([
            'groups'   => $grouped,
            'total'    => $total,
            'pages'    => ceil($total / $per_page),
            'page'     => $page,
            'has_more' => $page < ceil($total / $per_page),
        ]);
    }

    /**
     * حذف آیتم تاریخچه (AJAX)
     */
    public function delete_history_item() {
        check_ajax_referer('novel_bookmark_nonce', 'nonce');

        if (!is_user_logged_in()) wp_send_json_error();

        $user_id    = get_current_user_id();
        $chapter_id = absint($_POST['chapter_id'] ?? 0);

        if (!$chapter_id) wp_send_json_error();

        global $wpdb;
        $table = $wpdb->prefix . 'novel_reading_history';

        $wpdb->delete($table, [
            'user_id'    => $user_id,
            'chapter_id' => $chapter_id,
        ], ['%d', '%d']);

        wp_send_json_success(['message' => 'آیتم حذف شد']);
    }

    /**
     * پاک کردن کل تاریخچه (AJAX)
     */
    public function clear_history() {
        check_ajax_referer('novel_bookmark_nonce', 'nonce');

        if (!is_user_logged_in()) wp_send_json_error();

        $user_id = get_current_user_id();

        global $wpdb;
        $table = $wpdb->prefix . 'novel_reading_history';

        $wpdb->delete($table, ['user_id' => $user_id], ['%d']);

        wp_send_json_success(['message' => 'تاریخچه پاک شد ✓']);
    }

    /* ═══════════════════════════════════════
       Continue Reading
       ═══════════════════════════════════════ */

    /**
     * گرفتن رمان‌های «ادامه مطالعه» (AJAX)
     */
    public function get_continue_reading() {
        check_ajax_referer('novel_bookmark_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_success(['novels' => []]);
        }

        $user_id = get_current_user_id();
        $limit   = absint($_POST['limit'] ?? 5);
        $limit   = min($limit, 10);

        global $wpdb;
        $table = $wpdb->prefix . 'novel_user_library';

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, p.post_title
             FROM {$table} l
             INNER JOIN {$wpdb->posts} p ON l.novel_id = p.ID AND p.post_status = 'publish'
             WHERE l.user_id = %d AND l.list_type = 'reading' AND l.last_chapter_id > 0
             ORDER BY l.updated_at DESC
             LIMIT %d",
            $user_id, $limit
        ));

        $novels = [];
        foreach ($items as $item) {
            $pid = $item->novel_id;
            $thumb = get_the_post_thumbnail_url($pid, 'thumbnail');
            $chapter_count = (int) get_post_meta($pid, 'novel_chapter_count', true);
            $last_ch_num = (int) get_post_meta($item->last_chapter_id, 'chapter_number', true);

            // Next chapter
            $next_id = $this->get_next_chapter($pid, $item->last_chapter_id);
            $continue_url = get_permalink($next_id ?: $item->last_chapter_id);

            $novels[] = [
                'id'           => $pid,
                'title'        => $item->post_title,
                'url'          => get_permalink($pid),
                'thumb'        => $thumb ?: get_template_directory_uri() . '/assets/images/no-cover.png',
                'progress'     => round($item->progress_percent, 1),
                'last_chapter' => $last_ch_num,
                'total'        => $chapter_count,
                'continue_url' => $continue_url,
                'updated'      => human_time_diff(strtotime($item->updated_at), current_time('timestamp')) . ' پیش',
            ];
        }

        wp_send_json_success(['novels' => $novels]);
    }

    /* ═══════════════════════════════════════
       Static Helpers
       ═══════════════════════════════════════ */

    /**
     * بررسی آیا رمان در کتابخانه کاربر هست
     */
    public static function is_in_library($user_id, $novel_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'novel_user_library';

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE user_id = %d AND novel_id = %d",
            $user_id, $novel_id
        ));
    }

    /**
     * گرفتن وضعیت رمان در کتابخانه
     */
    public static function get_status($user_id, $novel_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'novel_user_library';

        return $wpdb->get_var($wpdb->prepare(
            "SELECT list_type FROM {$table} WHERE user_id = %d AND novel_id = %d",
            $user_id, $novel_id
        ));
    }

    /**
     * گرفتن لیست قسمت‌های خوانده‌شده
     */
    public static function get_read_chapters($user_id, $novel_id) {
        $cache_key = "novel_read_chapters_{$user_id}_{$novel_id}";
        $cached = get_transient($cache_key);

        if (false !== $cached) return $cached;

        global $wpdb;
        $table = $wpdb->prefix . 'novel_reading_history';

        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT chapter_id FROM {$table} WHERE user_id = %d AND novel_id = %d",
            $user_id, $novel_id
        ));

        $ids = array_map('intval', $ids);
        set_transient($cache_key, $ids, 30 * MINUTE_IN_SECONDS);

        return $ids;
    }

    /**
     * گرفتن آخرین قسمت خوانده‌شده + URL ادامه
     */
    public static function get_continue_info($user_id, $novel_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'novel_user_library';

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT last_chapter_id, progress_percent FROM {$table} 
             WHERE user_id = %d AND novel_id = %d",
            $user_id, $novel_id
        ));

        if (!$row || !$row->last_chapter_id) {
            // First chapter
            $instance = self::get_instance();
            $first = $instance->get_first_chapter($novel_id);
            return [
                'has_progress'  => false,
                'label'         => 'شروع مطالعه: قسمت ۱',
                'url'           => $first ? get_permalink($first) : get_permalink($novel_id),
                'progress'      => 0,
                'last_chapter'  => 0,
            ];
        }

        $instance = self::get_instance();
        $last_ch_num = (int) get_post_meta($row->last_chapter_id, 'chapter_number', true);
        $next_id = $instance->get_next_chapter($novel_id, $row->last_chapter_id);

        if ($next_id) {
            $next_num = (int) get_post_meta($next_id, 'chapter_number', true);
            return [
                'has_progress' => true,
                'label'        => sprintf('ادامه مطالعه: قسمت %d', $next_num),
                'url'          => get_permalink($next_id),
                'progress'     => round($row->progress_percent, 1),
                'last_chapter' => $last_ch_num,
            ];
        }

        return [
            'has_progress' => true,
            'label'        => sprintf('آخرین خوانده: قسمت %d', $last_ch_num),
            'url'          => get_permalink($row->last_chapter_id),
            'progress'     => round($row->progress_percent, 1),
            'last_chapter' => $last_ch_num,
        ];
    }

    /* ═══════════════════════════════════════
       Internal Helpers
       ═══════════════════════════════════════ */

    /**
     * محاسبه درصد پیشرفت
     */
    private function calculate_progress($novel_id, $chapter_id) {
        if (!$chapter_id) return 0;

        $chapter_number = (int) get_post_meta($chapter_id, 'chapter_number', true);
        $total = (int) get_post_meta($novel_id, 'novel_chapter_count', true);

        if (!$total) {
            global $wpdb;
            $total = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts}
                 WHERE post_parent = %d AND post_type = 'chapter' AND post_status = 'publish'",
                $novel_id
            ));
            if ($total) {
                update_post_meta($novel_id, 'novel_chapter_count', $total);
            }
        }

        if ($total <= 0) return 0;

        return min(100, ($chapter_number / $total) * 100);
    }

    /**
     * گرفتن قسمت بعدی
     */
    private function get_next_chapter($novel_id, $current_chapter_id) {
        $current_num = (int) get_post_meta($current_chapter_id, 'chapter_number', true);

        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'chapter_number'
             WHERE (p.post_parent = %d OR EXISTS(
                SELECT 1 FROM {$wpdb->postmeta} WHERE post_id = p.ID AND meta_key = 'chapter_novel_id' AND meta_value = %d
             ))
             AND p.post_type = 'chapter'
             AND p.post_status = 'publish'
             AND CAST(pm.meta_value AS UNSIGNED) > %d
             ORDER BY CAST(pm.meta_value AS UNSIGNED) ASC
             LIMIT 1",
            $novel_id, $novel_id, $current_num
        ));
    }

    /**
     * گرفتن اولین قسمت
     */
    private function get_first_chapter($novel_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'chapter_number'
             WHERE (p.post_parent = %d OR EXISTS(
                SELECT 1 FROM {$wpdb->postmeta} WHERE post_id = p.ID AND meta_key = 'chapter_novel_id' AND meta_value = %d
             ))
             AND p.post_type = 'chapter'
             AND p.post_status = 'publish'
             ORDER BY CAST(pm.meta_value AS UNSIGNED) ASC
             LIMIT 1",
            $novel_id, $novel_id
        ));
    }

    /**
     * آپدیت شمارنده بوکمارک رمان
     */
    private function update_bookmark_count($novel_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'novel_user_library';

        $count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE novel_id = %d",
            $novel_id
        ));

        update_post_meta($novel_id, 'novel_bookmarks_count', $count);
    }

    /**
     * Get status labels
     */
    public function get_status_labels() {
        return $this->status_labels;
    }

    /**
     * ایجاد جداول دیتابیس
     */
    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        // User Library
        $lib_table = $wpdb->prefix . 'novel_user_library';
        $sql1 = "CREATE TABLE IF NOT EXISTS {$lib_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            novel_id BIGINT UNSIGNED NOT NULL,
            list_type VARCHAR(20) NOT NULL DEFAULT 'reading',
            last_chapter_id BIGINT UNSIGNED DEFAULT 0,
            progress_percent DECIMAL(5,2) DEFAULT 0.00,
            added_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_user_novel (user_id, novel_id),
            KEY idx_user_status (user_id, list_type),
            KEY idx_novel (novel_id),
            KEY idx_updated (updated_at)
        ) {$charset};";

        // Reading History
        $hist_table = $wpdb->prefix . 'novel_reading_history';
        $sql2 = "CREATE TABLE IF NOT EXISTS {$hist_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            novel_id BIGINT UNSIGNED NOT NULL,
            chapter_id BIGINT UNSIGNED NOT NULL,
            scroll_position DECIMAL(5,2) DEFAULT 0.00,
            read_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_user_chapter (user_id, chapter_id),
            KEY idx_user_novel (user_id, novel_id),
            KEY idx_read_at (read_at)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql1);
        dbDelta($sql2);
    }
}