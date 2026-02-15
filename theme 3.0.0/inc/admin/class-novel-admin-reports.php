<?php
/**
 * Novel Admin Reports Management
 * 
 * پنل مدیریت گزارش‌ها برای ادمین
 *
 * @package suspended-flavor
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

// WP_List_Table
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Novel_Admin_Reports {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('wp_ajax_novel_admin_update_report', [$this, 'update_report']);
        add_action('wp_ajax_novel_admin_bulk_reports', [$this, 'bulk_action']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Add submenu
     */
    public function add_menu() {
        $pending_count = Novel_Reports::get_pending_count();
        $badge = '';
        if ($pending_count > 0) {
            $badge = sprintf(
                ' <span class="novel-report-badge">%d</span>',
                $pending_count
            );
        }

        add_submenu_page(
            'novel-settings',
            'مدیریت گزارش‌ها',
            'گزارش‌ها 🚩' . $badge,
            'manage_options',
            'novel-reports',
            [$this, 'render_page']
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'novel-reports') === false) return;
        
        wp_enqueue_style(
            'novel-admin-reports',
            get_template_directory_uri() . '/assets/css/admin-reports.css',
            [],
            FLAVOR_VERSION
        );
    }

    /**
     * Render page
     */
    public function render_page() {
        // Handle single report view
        if (isset($_GET['report_id'])) {
            $this->render_single_report(absint($_GET['report_id']));
            return;
        }

        $table = new Novel_Reports_List_Table();
        $table->prepare_items();
        ?>
        <div class="wrap novel-admin-reports">
            <h1 class="wp-heading-inline">🚩 مدیریت گزارش‌ها</h1>
            
            <?php $this->render_stats(); ?>
            
            <form method="get">
                <input type="hidden" name="page" value="novel-reports">
                <?php
                $table->search_box('جستجو', 'report-search');
                $table->display();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Statistics cards
     */
    private function render_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'novel_reports';
        
        $stats = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM {$table} GROUP BY status"
        );
        
        $counts = ['pending' => 0, 'reviewed' => 0, 'resolved' => 0, 'rejected' => 0];
        foreach ($stats as $stat) {
            if (isset($counts[$stat->status])) {
                $counts[$stat->status] = (int) $stat->count;
            }
        }
        $total = array_sum($counts);
        
        ?>
        <div class="novel-reports-stats">
            <div class="stat-card stat-total">
                <span class="stat-number"><?php echo number_format_i18n($total); ?></span>
                <span class="stat-label">کل گزارش‌ها</span>
            </div>
            <div class="stat-card stat-pending">
                <span class="stat-number"><?php echo number_format_i18n($counts['pending']); ?></span>
                <span class="stat-label">در انتظار</span>
            </div>
            <div class="stat-card stat-reviewed">
                <span class="stat-number"><?php echo number_format_i18n($counts['reviewed']); ?></span>
                <span class="stat-label">بررسی‌شده</span>
            </div>
            <div class="stat-card stat-resolved">
                <span class="stat-number"><?php echo number_format_i18n($counts['resolved']); ?></span>
                <span class="stat-label">حل‌شده</span>
            </div>
            <div class="stat-card stat-rejected">
                <span class="stat-number"><?php echo number_format_i18n($counts['rejected']); ?></span>
                <span class="stat-label">رد شده</span>
            </div>
        </div>
        <?php
    }

    /**
     * Render single report detail
     */
    private function render_single_report($report_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'novel_reports';
        
        $report = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d", $report_id
        ));
        
        if (!$report) {
            echo '<div class="wrap"><div class="notice notice-error"><p>گزارش یافت نشد.</p></div></div>';
            return;
        }
        
        $reporter = get_user_by('id', $report->reporter_id);
        $item_info = Novel_Reports::get_reported_item_info($report->reported_type, $report->reported_id);
        
        $type_labels = [
            'chapter' => ['label' => 'قسمت', 'color' => '#3b82f6'],
            'comment' => ['label' => 'دیدگاه', 'color' => '#10b981'],
            'user'    => ['label' => 'کاربر', 'color' => '#ef4444'],
            'novel'   => ['label' => 'رمان', 'color' => '#8b5cf6'],
        ];
        
        $reason_labels = [
            'typo' => 'خطای تایپی',
            'bad_translation' => 'ترجمه نادرست',
            'inappropriate' => 'محتوای نامناسب',
            'duplicate' => 'تکراری',
            'broken_link' => 'لینک/تصویر خراب',
            'spam' => 'اسپم',
            'insult' => 'توهین و بی‌احترامی',
            'spoiler' => 'اسپویلر بدون برچسب',
            'fake_identity' => 'هویت جعلی',
            'copyright' => 'نقض کپی‌رایت',
            'other' => 'سایر',
        ];
        
        $status_labels = [
            'pending'  => ['label' => 'در انتظار', 'color' => '#f59e0b'],
            'reviewed' => ['label' => 'بررسی‌شده', 'color' => '#3b82f6'],
            'resolved' => ['label' => 'حل‌شده', 'color' => '#10b981'],
            'rejected' => ['label' => 'رد شده', 'color' => '#ef4444'],
        ];
        
        $type_info = $type_labels[$report->reported_type] ?? ['label' => $report->reported_type, 'color' => '#666'];
        $status_info = $status_labels[$report->status] ?? ['label' => $report->status, 'color' => '#666'];
        ?>
        <div class="wrap novel-admin-reports">
            <h1>
                <a href="<?php echo admin_url('admin.php?page=novel-reports'); ?>" class="page-title-action">← بازگشت</a>
                🚩 جزئیات گزارش #<?php echo $report->id; ?>
            </h1>
            
            <div class="report-detail-grid">
                <!-- Info Card -->
                <div class="report-detail-card">
                    <h3>اطلاعات گزارش</h3>
                    <table class="form-table">
                        <tr>
                            <th>نوع:</th>
                            <td>
                                <span class="report-type-badge" style="background:<?php echo $type_info['color']; ?>">
                                    <?php echo esc_html($type_info['label']); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>دلیل:</th>
                            <td><?php echo esc_html($reason_labels[$report->reason] ?? $report->reason); ?></td>
                        </tr>
                        <tr>
                            <th>توضیحات:</th>
                            <td><?php echo $report->description ? esc_html($report->description) : '<em>بدون توضیحات</em>'; ?></td>
                        </tr>
                        <tr>
                            <th>وضعیت:</th>
                            <td>
                                <span class="report-status-badge" style="background:<?php echo $status_info['color']; ?>">
                                    <?php echo esc_html($status_info['label']); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>تاریخ:</th>
                            <td><?php echo esc_html($report->created_at); ?></td>
                        </tr>
                        <tr>
                            <th>گزارش‌دهنده:</th>
                            <td>
                                <?php if ($reporter): ?>
                                    <a href="<?php echo get_edit_user_link($reporter->ID); ?>">
                                        <?php echo esc_html($reporter->display_name); ?>
                                    </a>
                                <?php else: ?>
                                    <em>کاربر حذف شده</em>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Reported Item -->
                <div class="report-detail-card">
                    <h3>محتوای گزارش‌شده</h3>
                    <div class="reported-item-preview">
                        <p><strong>عنوان:</strong> <?php echo esc_html($item_info['title'] ?: 'نامشخص'); ?></p>
                        <?php if ($item_info['excerpt']): ?>
                            <p><strong>پیش‌نمایش:</strong> <?php echo esc_html($item_info['excerpt']); ?>...</p>
                        <?php endif; ?>
                        <?php if ($item_info['url']): ?>
                            <a href="<?php echo esc_url($item_info['url']); ?>" target="_blank" class="button">
                                مشاهده محتوا ↗
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="report-detail-card report-actions-card">
                    <h3>عملیات</h3>
                    <form method="post" class="report-action-form">
                        <?php wp_nonce_field('novel_admin_report_action', 'report_action_nonce'); ?>
                        <input type="hidden" name="report_id" value="<?php echo $report->id; ?>">
                        
                        <div class="form-field">
                            <label>تغییر وضعیت:</label>
                            <select name="new_status" class="regular-text">
                                <option value="pending" <?php selected($report->status, 'pending'); ?>>در انتظار</option>
                                <option value="reviewed" <?php selected($report->status, 'reviewed'); ?>>بررسی‌شده</option>
                                <option value="resolved" <?php selected($report->status, 'resolved'); ?>>حل‌شده</option>
                                <option value="rejected" <?php selected($report->status, 'rejected'); ?>>رد شده</option>
                            </select>
                        </div>
                        
                        <div class="form-field">
                            <label>یادداشت ادمین:</label>
                            <textarea name="admin_note" rows="3" class="large-text"><?php echo esc_textarea($report->admin_note ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-field">
                            <label>
                                <input type="checkbox" name="notify_reporter" value="1">
                                اطلاع‌رسانی به گزارش‌دهنده
                            </label>
                        </div>
                        
                        <div class="report-action-buttons">
                            <button type="submit" name="action" value="update_status" class="button button-primary">
                                ذخیره تغییرات
                            </button>
                            
                            <?php if ($report->reported_type === 'comment'): ?>
                                <button type="submit" name="action" value="delete_content" class="button button-link-delete"
                                        onclick="return confirm('آیا از حذف محتوا مطمئنید؟')">
                                    🗑 حذف دیدگاه
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($report->reported_type === 'user'): ?>
                                <button type="submit" name="action" value="block_user" class="button button-link-delete"
                                        onclick="return confirm('آیا از مسدود کردن کاربر مطمئنید؟')">
                                    🚫 مسدود کردن کاربر
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                    
                    <?php if ($report->reviewed_by): ?>
                        <div class="report-review-info">
                            <small>
                                بررسی شده توسط: 
                                <?php 
                                $reviewer = get_user_by('id', $report->reviewed_by);
                                echo $reviewer ? esc_html($reviewer->display_name) : 'نامشخص';
                                ?>
                                در <?php echo esc_html($report->reviewed_at); ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        
        // Handle form submission
        $this->handle_single_report_action();
    }

    /**
     * Handle single report action
     */
    private function handle_single_report_action() {
        if (!isset($_POST['report_action_nonce'])) return;
        if (!wp_verify_nonce($_POST['report_action_nonce'], 'novel_admin_report_action')) return;
        if (!current_user_can('manage_options')) return;
        
        $report_id = absint($_POST['report_id'] ?? 0);
        $action = sanitize_text_field($_POST['action'] ?? '');
        
        if (!$report_id) return;
        
        global $wpdb;
        $table = $wpdb->prefix . 'novel_reports';
        $report = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $report_id));
        
        if (!$report) return;
        
        switch ($action) {
            case 'update_status':
                $new_status = sanitize_text_field($_POST['new_status'] ?? '');
                $admin_note = sanitize_textarea_field($_POST['admin_note'] ?? '');
                $notify = !empty($_POST['notify_reporter']);
                
                if (in_array($new_status, ['pending', 'reviewed', 'resolved', 'rejected'], true)) {
                    $wpdb->update($table, [
                        'status'      => $new_status,
                        'admin_note'  => $admin_note,
                        'reviewed_by' => get_current_user_id(),
                        'reviewed_at' => current_time('mysql'),
                    ], ['id' => $report_id], ['%s', '%s', '%d', '%s'], ['%d']);
                    
                    delete_transient('novel_reports_pending_count');
                    
                    // Notify reporter
                    if ($notify && class_exists('Novel_Notifications') && $report->reporter_id) {
                        $status_labels = [
                            'reviewed' => 'بررسی شد',
                            'resolved' => 'حل شد',
                            'rejected' => 'رد شد',
                        ];
                        $label = $status_labels[$new_status] ?? $new_status;
                        
                        $notif = Novel_Notifications::get_instance();
                        $notif->send_notification(
                            (int) $report->reporter_id,
                            'report_result',
                            'نتیجه گزارش شما',
                            sprintf('گزارش شما بررسی و %s.', $label),
                            ''
                        );
                    }
                }
                break;
                
            case 'delete_content':
                if ($report->reported_type === 'comment') {
                    wp_delete_comment($report->reported_id, true);
                    $wpdb->update($table, [
                        'status'      => 'resolved',
                        'admin_note'  => 'محتوا حذف شد.',
                        'reviewed_by' => get_current_user_id(),
                        'reviewed_at' => current_time('mysql'),
                    ], ['id' => $report_id]);
                    delete_transient('novel_reports_pending_count');
                }
                break;
                
            case 'block_user':
                if ($report->reported_type === 'user') {
                    update_user_meta($report->reported_id, 'novel_blocked', true);
                    update_user_meta($report->reported_id, 'novel_blocked_at', current_time('mysql'));
                    update_user_meta($report->reported_id, 'novel_blocked_by', get_current_user_id());
                    
                    $wpdb->update($table, [
                        'status'      => 'resolved',
                        'admin_note'  => 'کاربر مسدود شد.',
                        'reviewed_by' => get_current_user_id(),
                        'reviewed_at' => current_time('mysql'),
                    ], ['id' => $report_id]);
                    delete_transient('novel_reports_pending_count');
                }
                break;
        }
        
        // Redirect to avoid resubmission
        wp_safe_redirect(admin_url('admin.php?page=novel-reports&report_id=' . $report_id . '&updated=1'));
        exit;
    }

    /**
     * AJAX: Update report status
     */
    public function update_report() {
        check_ajax_referer('novel_admin_report_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی ندارید']);
        }
        
        $report_id = absint($_POST['report_id'] ?? 0);
        $new_status = sanitize_text_field($_POST['status'] ?? '');
        
        if (!$report_id || !in_array($new_status, ['pending', 'reviewed', 'resolved', 'rejected'], true)) {
            wp_send_json_error(['message' => 'داده نامعتبر']);
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'novel_reports';
        
        $wpdb->update($table, [
            'status'      => $new_status,
            'reviewed_by' => get_current_user_id(),
            'reviewed_at' => current_time('mysql'),
        ], ['id' => $report_id], ['%s', '%d', '%s'], ['%d']);
        
        delete_transient('novel_reports_pending_count');
        
        wp_send_json_success(['message' => 'وضعیت به‌روزرسانی شد']);
    }

    /**
     * AJAX: Bulk action
     */
    public function bulk_action() {
        check_ajax_referer('novel_admin_report_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی ندارید']);
        }
        
        $ids = array_map('absint', $_POST['ids'] ?? []);
        $action = sanitize_text_field($_POST['bulk_action'] ?? '');
        
        if (empty($ids)) {
            wp_send_json_error(['message' => 'موردی انتخاب نشده']);
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'novel_reports';
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        
        switch ($action) {
            case 'reviewed':
            case 'resolved':
            case 'rejected':
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$table} SET status = %s, reviewed_by = %d, reviewed_at = %s 
                     WHERE id IN ({$placeholders})",
                    array_merge([$action, get_current_user_id(), current_time('mysql')], $ids)
                ));
                break;
                
            case 'delete':
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$table} WHERE id IN ({$placeholders})",
                    $ids
                ));
                break;
                
            default:
                wp_send_json_error(['message' => 'عملیات نامعتبر']);
        }
        
        delete_transient('novel_reports_pending_count');
        
        wp_send_json_success(['message' => 'عملیات انجام شد', 'count' => count($ids)]);
    }
}

/**
 * WP_List_Table for Reports
 */
class Novel_Reports_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => 'report',
            'plural'   => 'reports',
            'ajax'     => false,
        ]);
    }

    public function get_columns() {
        return [
            'cb'           => '<input type="checkbox" />',
            'id'           => 'شناسه',
            'reported_type'=> 'نوع',
            'reported_item'=> 'محتوای گزارش‌شده',
            'reporter'     => 'گزارش‌دهنده',
            'reason'       => 'دلیل',
            'status'       => 'وضعیت',
            'created_at'   => 'تاریخ',
            'actions'      => 'عملیات',
        ];
    }

    public function get_sortable_columns() {
        return [
            'id'         => ['id', true],
            'created_at' => ['created_at', true],
            'status'     => ['status', false],
        ];
    }

    protected function get_bulk_actions() {
        return [
            'mark_reviewed' => 'بررسی‌شده',
            'mark_resolved' => 'حل‌شده',
            'mark_rejected' => 'رد شده',
            'delete'        => 'حذف',
        ];
    }

    public function prepare_items() {
        global $wpdb;
        $table = $wpdb->prefix . 'novel_reports';
        
        $per_page = 20;
        $current_page = $this->get_pagenum();
        
        // Filters
        $where = "WHERE 1=1";
        $params = [];
        
        // Status filter
        $status_filter = sanitize_text_field($_GET['report_status'] ?? '');
        if ($status_filter && in_array($status_filter, ['pending', 'reviewed', 'resolved', 'rejected'], true)) {
            $where .= " AND status = %s";
            $params[] = $status_filter;
        }
        
        // Type filter
        $type_filter = sanitize_text_field($_GET['report_type'] ?? '');
        if ($type_filter && in_array($type_filter, ['chapter', 'comment', 'user', 'novel'], true)) {
            $where .= " AND reported_type = %s";
            $params[] = $type_filter;
        }
        
        // Search
        $search = sanitize_text_field($_GET['s'] ?? '');
        if ($search) {
            $where .= " AND (description LIKE %s OR reason LIKE %s)";
            $like = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $like;
            $params[] = $like;
        }
        
        // Orderby
        $orderby = sanitize_sql_orderby($_GET['orderby'] ?? 'created_at');
        if (!in_array($orderby, ['id', 'created_at', 'status'], true)) {
            $orderby = 'created_at';
        }
        $order = strtoupper(sanitize_text_field($_GET['order'] ?? 'DESC'));
        if (!in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'DESC';
        }
        
        // Count
        $count_query = "SELECT COUNT(*) FROM {$table} {$where}";
        $total_items = $params 
            ? (int) $wpdb->get_var($wpdb->prepare($count_query, $params))
            : (int) $wpdb->get_var($count_query);
        
        // Get items
        $offset = ($current_page - 1) * $per_page;
        $query = "SELECT * FROM {$table} {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $all_params = array_merge($params, [$per_page, $offset]);
        
        $this->items = $wpdb->get_results($wpdb->prepare($query, $all_params));
        
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ]);
        
        $this->_column_headers = [
            $this->get_columns(),
            [],
            $this->get_sortable_columns(),
        ];
        
        // Process bulk actions
        $this->process_bulk_action();
    }

    protected function extra_tablenav($which) {
        if ($which !== 'top') return;
        
        $status_filter = sanitize_text_field($_GET['report_status'] ?? '');
        $type_filter = sanitize_text_field($_GET['report_type'] ?? '');
        ?>
        <div class="alignleft actions">
            <select name="report_status">
                <option value="">همه وضعیت‌ها</option>
                <option value="pending" <?php selected($status_filter, 'pending'); ?>>در انتظار</option>
                <option value="reviewed" <?php selected($status_filter, 'reviewed'); ?>>بررسی‌شده</option>
                <option value="resolved" <?php selected($status_filter, 'resolved'); ?>>حل‌شده</option>
                <option value="rejected" <?php selected($status_filter, 'rejected'); ?>>رد شده</option>
            </select>
            <select name="report_type">
                <option value="">همه انواع</option>
                <option value="chapter" <?php selected($type_filter, 'chapter'); ?>>قسمت</option>
                <option value="comment" <?php selected($type_filter, 'comment'); ?>>دیدگاه</option>
                <option value="user" <?php selected($type_filter, 'user'); ?>>کاربر</option>
                <option value="novel" <?php selected($type_filter, 'novel'); ?>>رمان</option>
            </select>
            <?php submit_button('فیلتر', '', 'filter_action', false); ?>
        </div>
        <?php
    }

    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="report_ids[]" value="%d" />', $item->id);
    }

    public function column_id($item) {
        return sprintf(
            '<a href="%s"><strong>#%d</strong></a>',
            admin_url('admin.php?page=novel-reports&report_id=' . $item->id),
            $item->id
        );
    }

    public function column_reported_type($item) {
        $badges = [
            'chapter' => '<span class="report-badge report-badge--chapter">قسمت</span>',
            'comment' => '<span class="report-badge report-badge--comment">دیدگاه</span>',
            'user'    => '<span class="report-badge report-badge--user">کاربر</span>',
            'novel'   => '<span class="report-badge report-badge--novel">رمان</span>',
        ];
        return $badges[$item->reported_type] ?? esc_html($item->reported_type);
    }

    public function column_reported_item($item) {
        $info = Novel_Reports::get_reported_item_info($item->reported_type, $item->reported_id);
        $title = esc_html($info['title'] ?: 'نامشخص #' . $item->reported_id);
        
        if ($info['url']) {
            return sprintf('<a href="%s" target="_blank">%s ↗</a>', esc_url($info['url']), $title);
        }
        return $title;
    }

    public function column_reporter($item) {
        $user = get_user_by('id', $item->reporter_id);
        if (!$user) return '<em>حذف شده</em>';
        
        return sprintf(
            '<a href="%s">%s</a>',
            get_edit_user_link($user->ID),
            esc_html($user->display_name)
        );
    }

    public function column_reason($item) {
        $labels = [
            'typo' => '📝 خطای تایپی',
            'bad_translation' => '🔄 ترجمه نادرست',
            'inappropriate' => '🚫 نامناسب',
            'duplicate' => '📋 تکراری',
            'broken_link' => '🔗 لینک خراب',
            'spam' => '🤖 اسپم',
            'insult' => '🤬 توهین',
            'spoiler' => '⚠️ اسپویلر',
            'fake_identity' => '🎭 هویت جعلی',
            'copyright' => '©️ کپی‌رایت',
            'other' => '📌 سایر',
        ];
        
        $label = $labels[$item->reason] ?? esc_html($item->reason);
        $output = '<span class="report-reason-label">' . $label . '</span>';
        
        if ($item->description) {
            $output .= '<br><small class="description" title="' . esc_attr($item->description) . '">' 
                     . esc_html(mb_substr($item->description, 0, 60)) 
                     . (mb_strlen($item->description) > 60 ? '...' : '') 
                     . '</small>';
        }
        
        return $output;
    }

    public function column_status($item) {
        $statuses = [
            'pending'  => '<span class="report-status report-status--pending">⏳ در انتظار</span>',
            'reviewed' => '<span class="report-status report-status--reviewed">👁 بررسی‌شده</span>',
            'resolved' => '<span class="report-status report-status--resolved">✅ حل‌شده</span>',
            'rejected' => '<span class="report-status report-status--rejected">❌ رد شده</span>',
        ];
        return $statuses[$item->status] ?? esc_html($item->status);
    }

    public function column_created_at($item) {
        return '<span title="' . esc_attr($item->created_at) . '">' 
             . human_time_diff(strtotime($item->created_at), current_time('timestamp')) 
             . ' پیش</span>';
    }

    public function column_actions($item) {
        $detail_url = admin_url('admin.php?page=novel-reports&report_id=' . $item->id);
        return sprintf(
            '<a href="%s" class="button button-small">جزئیات</a>',
            $detail_url
        );
    }

    public function column_default($item, $column_name) {
        return isset($item->$column_name) ? esc_html($item->$column_name) : '';
    }

    private function process_bulk_action() {
        $action = $this->current_action();
        if (!$action) return;
        
        $ids = array_map('absint', $_REQUEST['report_ids'] ?? []);
        if (empty($ids)) return;
        
        check_admin_referer('bulk-reports');
        
        global $wpdb;
        $table = $wpdb->prefix . 'novel_reports';
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        
        switch ($action) {
            case 'mark_reviewed':
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$table} SET status = 'reviewed', reviewed_by = %d, reviewed_at = %s 
                     WHERE id IN ({$placeholders})",
                    array_merge([get_current_user_id(), current_time('mysql')], $ids)
                ));
                break;
                
            case 'mark_resolved':
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$table} SET status = 'resolved', reviewed_by = %d, reviewed_at = %s 
                     WHERE id IN ({$placeholders})",
                    array_merge([get_current_user_id(), current_time('mysql')], $ids)
                ));
                break;
                
            case 'mark_rejected':
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$table} SET status = 'rejected', reviewed_by = %d, reviewed_at = %s 
                     WHERE id IN ({$placeholders})",
                    array_merge([get_current_user_id(), current_time('mysql')], $ids)
                ));
                break;
                
            case 'delete':
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$table} WHERE id IN ({$placeholders})",
                    $ids
                ));
                break;
        }
        
        delete_transient('novel_reports_pending_count');
    }
}