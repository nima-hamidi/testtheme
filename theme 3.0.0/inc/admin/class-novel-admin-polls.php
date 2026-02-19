<?php
/**
 * Novel Admin Polls Management
 * 
 * مدیریت نظرسنجی‌ها در پنل ادمین
 * 
 * @package suspended developer
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

class Novel_Admin_Polls {

    private static $instance = null;
    private $polls;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->polls = Novel_Polls::get_instance();

        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'handle_form']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * منوی ادمین
     */
    public function add_menu() {
        add_submenu_page(
            'novel-settings',
            'نظرسنجی‌ها',
            '📊 نظرسنجی‌ها',
            'manage_options',
            'novel-polls',
            [$this, 'render_page']
        );
    }

    /**
     * Assets ادمین
     */
    public function enqueue_assets($hook) {
        if (strpos($hook, 'novel-polls') === false) return;

        wp_enqueue_style('novel-admin-css');

        wp_enqueue_style(
            'novel-admin-polls',
            get_template_directory_uri() . '/assets/css/polls.css',
            [],
            JEsuspended_DEVELOPER_VERSION
        );

        // Inline admin-specific styles
        wp_add_inline_style('novel-admin-polls', '
            .novel-admin-poll-form { max-width: 800px; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
            .novel-admin-poll-form .form-group { margin-bottom: 20px; }
            .novel-admin-poll-form label { display: block; font-weight: 600; margin-bottom: 6px; color: #1e293b; }
            .novel-admin-poll-form input[type="text"],
            .novel-admin-poll-form textarea,
            .novel-admin-poll-form select { width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; }
            .novel-admin-poll-form textarea { min-height: 80px; resize: vertical; }
            .novel-admin-poll-options { list-style: none; padding: 0; margin: 0; }
            .novel-admin-poll-options li { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
            .novel-admin-poll-options li input { flex: 1; }
            .novel-admin-poll-options li .remove-option { background: #ef4444; color: #fff; border: none; border-radius: 6px; width: 36px; height: 36px; cursor: pointer; font-size: 16px; }
            .novel-admin-poll-options li .remove-option:hover { background: #dc2626; }
            .novel-admin-add-option { background: #10b981; color: #fff; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 14px; }
            .novel-admin-add-option:hover { background: #059669; }
            .novel-admin-radio-group { display: flex; gap: 20px; flex-wrap: wrap; }
            .novel-admin-radio-group label { display: flex; align-items: center; gap: 6px; font-weight: 400; cursor: pointer; }
            .novel-poll-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin-bottom: 24px; }
            .novel-poll-stat-card { background: #fff; padding: 20px; border-radius: 12px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
            .novel-poll-stat-card .stat-number { font-size: 28px; font-weight: 700; color: var(--primary, #6366f1); }
            .novel-poll-stat-card .stat-label { font-size: 13px; color: #64748b; margin-top: 4px; }
            .novel-poll-results-bar { display: flex; flex-direction: column; gap: 12px; margin-top: 16px; }
            .novel-poll-result-item { display: flex; align-items: center; gap: 12px; }
            .novel-poll-result-item .result-label { min-width: 150px; font-size: 14px; }
            .novel-poll-result-item .result-bar-wrap { flex: 1; background: #e2e8f0; border-radius: 8px; height: 24px; overflow: hidden; }
            .novel-poll-result-item .result-bar { height: 100%; background: linear-gradient(90deg, #6366f1, #8b5cf6); border-radius: 8px; transition: width 0.5s ease; display: flex; align-items: center; justify-content: flex-end; padding-right: 8px; color: #fff; font-size: 12px; font-weight: 600; }
            .novel-poll-result-item .result-count { min-width: 60px; text-align: left; font-size: 13px; color: #64748b; }
        ');

        wp_enqueue_script(
            'novel-admin-polls',
            get_template_directory_uri() . '/assets/js/polls.js',
            ['jquery'],
            JEsuspended_DEVELOPER_VERSION,
            true
        );
    }

    /**
     * پردازش فرم‌ها
     */
    public function handle_form() {
        if (!isset($_POST['novel_poll_action'])) return;
        if (!current_user_can('manage_options')) return;
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'novel_poll_form')) return;

        $action = sanitize_text_field($_POST['novel_poll_action']);

        switch ($action) {
            case 'create':
                $this->handle_create();
                break;
            case 'update':
                $this->handle_update();
                break;
            case 'delete':
                $this->handle_delete();
                break;
            case 'toggle_status':
                $this->handle_toggle_status();
                break;
        }
    }

    /**
     * ساخت نظرسنجی
     */
    private function handle_create() {
        $options = array_filter(array_map('sanitize_text_field', $_POST['options'] ?? []));
        if (count($options) < 2) {
            add_settings_error('novel_polls', 'min_options', 'حداقل ۲ گزینه نیاز است.', 'error');
            return;
        }

        $poll_id = $this->polls->create_poll([
            'title'       => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'poll_type'   => $_POST['poll_type'] ?? 'single',
            'status'      => $_POST['status'] ?? 'draft',
            'novel_id'    => $_POST['novel_id'] ?? '',
            'start_date'  => $_POST['start_date'] ?? '',
            'end_date'    => $_POST['end_date'] ?? '',
            'options'     => $options,
        ]);

        if ($poll_id) {
            wp_redirect(admin_url('admin.php?page=novel-polls&message=created'));
            exit;
        }
    }

    /**
     * ویرایش نظرسنجی
     */
    private function handle_update() {
        $poll_id = absint($_POST['poll_id'] ?? 0);
        if (!$poll_id) return;

        $options = array_filter(array_map('sanitize_text_field', $_POST['options'] ?? []));

        $this->polls->update_poll($poll_id, [
            'title'       => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'poll_type'   => $_POST['poll_type'] ?? 'single',
            'status'      => $_POST['status'] ?? 'draft',
            'novel_id'    => $_POST['novel_id'] ?? '',
            'start_date'  => $_POST['start_date'] ?? '',
            'end_date'    => $_POST['end_date'] ?? '',
            'options'     => $options,
        ]);

        wp_redirect(admin_url('admin.php?page=novel-polls&message=updated'));
        exit;
    }

    /**
     * حذف نظرسنجی
     */
    private function handle_delete() {
        $poll_id = absint($_POST['poll_id'] ?? 0);
        if (!$poll_id) return;

        $this->polls->delete_poll($poll_id);

        wp_redirect(admin_url('admin.php?page=novel-polls&message=deleted'));
        exit;
    }

    /**
     * تغییر وضعیت
     */
    private function handle_toggle_status() {
        $poll_id = absint($_POST['poll_id'] ?? 0);
        $new_status = sanitize_text_field($_POST['new_status'] ?? '');

        if (!$poll_id || !in_array($new_status, ['active', 'draft', 'closed'])) return;

        $this->polls->update_poll($poll_id, ['status' => $new_status]);

        wp_redirect(admin_url('admin.php?page=novel-polls&message=status_changed'));
        exit;
    }

    /**
     * رندر صفحه اصلی
     */
    public function render_page() {
        $view = sanitize_text_field($_GET['view'] ?? 'list');

        echo '<div class="wrap novel-admin-wrap">';

        // پیام‌ها
        $this->show_messages();

        switch ($view) {
            case 'create':
                $this->render_create_form();
                break;
            case 'edit':
                $this->render_edit_form();
                break;
            case 'results':
                $this->render_results();
                break;
            default:
                $this->render_list();
                break;
        }

        echo '</div>';
    }

    /**
     * نمایش پیام‌ها
     */
    private function show_messages() {
        $messages = [
            'created'        => ['نظرسنجی با موفقیت ساخته شد. ✅', 'success'],
            'updated'        => ['نظرسنجی بروزرسانی شد. ✅', 'success'],
            'deleted'        => ['نظرسنجی حذف شد. 🗑', 'warning'],
            'status_changed' => ['وضعیت نظرسنجی تغییر کرد. ✅', 'success'],
        ];

        $msg_key = sanitize_text_field($_GET['message'] ?? '');
        if (isset($messages[$msg_key])) {
            printf(
                '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                esc_attr($messages[$msg_key][1]),
                esc_html($messages[$msg_key][0])
            );
        }

        settings_errors('novel_polls');
    }

    /**
     * لیست نظرسنجی‌ها
     */
    private function render_list() {
        $status_filter = sanitize_text_field($_GET['status'] ?? 'all');
        $page = max(1, absint($_GET['paged'] ?? 1));
        $search = sanitize_text_field($_GET['s'] ?? '');

        $result = $this->polls->get_admin_polls([
            'status'   => $status_filter,
            'page'     => $page,
            'per_page' => 20,
            'search'   => $search,
        ]);

        $stats = $this->polls->get_stats();
        ?>

        <h1 class="wp-heading-inline">📊 نظرسنجی‌ها</h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=novel-polls&view=create')); ?>" class="page-title-action">+ نظرسنجی جدید</a>
        <hr class="wp-header-end">

        <!-- آمار -->
        <div class="novel-poll-stats-grid">
            <div class="novel-poll-stat-card">
                <div class="stat-number"><?php echo number_format_i18n($stats['total']); ?></div>
                <div class="stat-label">کل نظرسنجی‌ها</div>
            </div>
            <div class="novel-poll-stat-card">
                <div class="stat-number" style="color: #10b981;"><?php echo number_format_i18n($stats['active']); ?></div>
                <div class="stat-label">فعال</div>
            </div>
            <div class="novel-poll-stat-card">
                <div class="stat-number" style="color: #64748b;"><?php echo number_format_i18n($stats['closed']); ?></div>
                <div class="stat-label">بسته</div>
            </div>
            <div class="novel-poll-stat-card">
                <div class="stat-number" style="color: #f59e0b;"><?php echo number_format_i18n($stats['total_votes']); ?></div>
                <div class="stat-label">کل آرا</div>
            </div>
        </div>

        <!-- فیلتر + جستجو -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
            <ul class="subsubsub" style="margin: 0;">
                <?php
                $statuses = [
                    'all'    => ['همه', $stats['total']],
                    'active' => ['فعال', $stats['active']],
                    'closed' => ['بسته', $stats['closed']],
                    'draft'  => ['پیش‌نویس', $stats['total'] - $stats['active'] - $stats['closed']],
                ];
                $links = [];
                foreach ($statuses as $key => $info) {
                    $class = $status_filter === $key ? 'current' : '';
                    $url = admin_url('admin.php?page=novel-polls&status=' . $key);
                    $links[] = sprintf(
                        '<li><a href="%s" class="%s">%s <span class="count">(%s)</span></a></li>',
                        esc_url($url), $class, esc_html($info[0]), number_format_i18n($info[1])
                    );
                }
                echo implode(' | ', $links);
                ?>
            </ul>

            <form method="get" style="display: flex; gap: 8px;">
                <input type="hidden" name="page" value="novel-polls">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="جستجوی عنوان..." style="padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
                <button type="submit" class="button">جستجو</button>
            </form>
        </div>

        <!-- جدول -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 40px;">#</th>
                    <th>عنوان</th>
                    <th style="width: 90px;">نوع</th>
                    <th style="width: 90px;">وضعیت</th>
                    <th style="width: 80px;">تعداد رأی</th>
                    <th style="width: 80px;">گزینه‌ها</th>
                    <th style="width: 130px;">تاریخ</th>
                    <th style="width: 180px;">عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($result['polls'])): ?>
                    <tr><td colspan="8" style="text-align: center; padding: 30px;">نظرسنجی‌ای یافت نشد.</td></tr>
                <?php else: foreach ($result['polls'] as $poll): ?>
                    <tr>
                        <td><?php echo esc_html($poll->id); ?></td>
                        <td>
                            <strong>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=novel-polls&view=edit&id=' . $poll->id)); ?>">
                                    <?php echo esc_html($poll->title); ?>
                                </a>
                            </strong>
                            <?php if ($poll->novel_id): ?>
                                <br><small style="color: #64748b;">📖 رمان: <?php echo esc_html(get_the_title($poll->novel_id)); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $poll->poll_type === 'single' ? 'تک‌انتخابی' : 'چندانتخابی'; ?>
                        </td>
                        <td>
                            <?php
                            $status_labels = [
                                'active'   => ['🟢 فعال', '#10b981'],
                                'closed'   => ['🔴 بسته', '#ef4444'],
                                'draft'    => ['📝 پیش‌نویس', '#f59e0b'],
                                'upcoming' => ['⏳ آینده', '#3b82f6'],
                            ];
                            $eff = $poll->effective_status;
                            $label = $status_labels[$eff] ?? ['❓ نامشخص', '#64748b'];
                            printf('<span style="color: %s; font-weight: 600;">%s</span>', $label[1], $label[0]);
                            ?>
                        </td>
                        <td style="text-align: center; font-weight: 600;">
                            <?php echo number_format_i18n($poll->total_votes); ?>
                        </td>
                        <td style="text-align: center;">
                            <?php echo number_format_i18n($poll->option_count); ?>
                        </td>
                        <td>
                            <small><?php echo esc_html(mysql2date('Y/m/d H:i', $poll->created_at)); ?></small>
                            <?php if ($poll->end_date): ?>
                                <br><small style="color: #64748b;">پایان: <?php echo esc_html(mysql2date('Y/m/d', $poll->end_date)); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=novel-polls&view=edit&id=' . $poll->id)); ?>" class="button button-small">✏️ ویرایش</a>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=novel-polls&view=results&id=' . $poll->id)); ?>" class="button button-small">📊 نتایج</a>

                            <?php if ($poll->status === 'active'): ?>
                                <form method="post" style="display: inline;">
                                    <?php wp_nonce_field('novel_poll_form'); ?>
                                    <input type="hidden" name="novel_poll_action" value="toggle_status">
                                    <input type="hidden" name="poll_id" value="<?php echo $poll->id; ?>">
                                    <input type="hidden" name="new_status" value="closed">
                                    <button type="submit" class="button button-small" onclick="return confirm('بستن نظرسنجی؟')">🔒 بسته</button>
                                </form>
                            <?php elseif ($poll->status === 'closed' || $poll->status === 'draft'): ?>
                                <form method="post" style="display: inline;">
                                    <?php wp_nonce_field('novel_poll_form'); ?>
                                    <input type="hidden" name="novel_poll_action" value="toggle_status">
                                    <input type="hidden" name="poll_id" value="<?php echo $poll->id; ?>">
                                    <input type="hidden" name="new_status" value="active">
                                    <button type="submit" class="button button-small">🟢 فعال</button>
                                </form>
                            <?php endif; ?>

                            <form method="post" style="display: inline;">
                                <?php wp_nonce_field('novel_poll_form'); ?>
                                <input type="hidden" name="novel_poll_action" value="delete">
                                <input type="hidden" name="poll_id" value="<?php echo $poll->id; ?>">
                                <button type="submit" class="button button-small" style="color: #ef4444;" onclick="return confirm('حذف نظرسنجی و تمام آرا؟')">🗑</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>

        <!-- صفحه‌بندی -->
        <?php if ($result['pages'] > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links([
                        'base'    => add_query_arg('paged', '%#%'),
                        'format'  => '',
                        'current' => $result['current'],
                        'total'   => $result['pages'],
                    ]);
                    ?>
                </div>
            </div>
        <?php endif; ?>

        <?php
    }

    /**
     * فرم ساخت نظرسنجی
     */
    private function render_create_form() {
        $this->render_poll_form(null);
    }

    /**
     * فرم ویرایش نظرسنجی
     */
    private function render_edit_form() {
        $poll_id = absint($_GET['id'] ?? 0);
        $poll = $this->polls->get_poll($poll_id);

        if (!$poll) {
            echo '<div class="notice notice-error"><p>نظرسنجی یافت نشد.</p></div>';
            return;
        }

        $this->render_poll_form($poll);
    }

    /**
     * فرم مشترک ساخت/ویرایش
     */
    private function render_poll_form($poll = null) {
        $is_edit = !is_null($poll);
        $has_votes = false;

        if ($is_edit) {
            global $wpdb;
            $has_votes = (bool)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}poll_votes WHERE poll_id = %d",
                $poll->id
            ));
        }

        // رمان‌ها برای dropdown
        $novels = get_posts([
            'post_type'      => 'novel',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        ]);
        ?>

        <h1><?php echo $is_edit ? '✏️ ویرایش نظرسنجی' : '+ ساخت نظرسنجی جدید'; ?></h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=novel-polls')); ?>" class="page-title-action">← بازگشت به لیست</a>
        <hr class="wp-header-end">

        <form method="post" class="novel-admin-poll-form">
            <?php wp_nonce_field('novel_poll_form'); ?>
            <input type="hidden" name="novel_poll_action" value="<?php echo $is_edit ? 'update' : 'create'; ?>">
            <?php if ($is_edit): ?>
                <input type="hidden" name="poll_id" value="<?php echo $poll->id; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="poll-title">عنوان نظرسنجی *</label>
                <input type="text" id="poll-title" name="title"
                       value="<?php echo esc_attr($is_edit ? $poll->title : ''); ?>"
                       required placeholder="مثال: بهترین رمان ماه آذر کدام است؟">
            </div>

            <div class="form-group">
                <label for="poll-desc">توضیحات (اختیاری)</label>
                <textarea id="poll-desc" name="description"
                          placeholder="توضیحات تکمیلی..."><?php echo esc_textarea($is_edit ? $poll->description : ''); ?></textarea>
            </div>

            <div class="form-group">
                <label>نوع نظرسنجی</label>
                <div class="novel-admin-radio-group">
                    <label>
                        <input type="radio" name="poll_type" value="single"
                               <?php checked(!$is_edit || $poll->poll_type === 'single'); ?>>
                        تک‌انتخابی
                    </label>
                    <label>
                        <input type="radio" name="poll_type" value="multiple"
                               <?php checked($is_edit && $poll->poll_type === 'multiple'); ?>>
                        چندانتخابی
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label>گزینه‌ها * <?php if ($has_votes): ?><small style="color: #f59e0b;">(⚠️ رأی ثبت شده - گزینه‌ها قابل تغییر نیست)</small><?php endif; ?></label>
                <ul class="novel-admin-poll-options" id="pollOptions">
                    <?php
                    $options = $is_edit ? $poll->options : [];
                    $option_count = max(2, count($options));

                    for ($i = 0; $i < $option_count; $i++):
                        $val = isset($options[$i]) ? $options[$i]->option_text : '';
                        ?>
                        <li>
                            <span style="min-width: 24px; color: #64748b; font-weight: 600;">①</span>
                            <input type="text" name="options[]"
                                   value="<?php echo esc_attr($val); ?>"
                                   placeholder="گزینه <?php echo $i + 1; ?>"
                                   <?php echo $has_votes ? 'readonly' : ''; ?>>
                            <?php if (!$has_votes): ?>
                                <button type="button" class="remove-option" onclick="this.closest('li').remove(); reindexOptions();">🗑</button>
                            <?php endif; ?>
                        </li>
                    <?php endfor; ?>
                </ul>
                <?php if (!$has_votes): ?>
                    <button type="button" class="novel-admin-add-option" onclick="addPollOption()">+ افزودن گزینه</button>
                    <small style="display: block; margin-top: 6px; color: #64748b;">حداکثر ۱۰ گزینه</small>
                <?php endif; ?>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label for="poll-start">تاریخ شروع (اختیاری)</label>
                    <input type="datetime-local" id="poll-start" name="start_date"
                           value="<?php echo $is_edit && $poll->start_date ? esc_attr(date('Y-m-d\TH:i', strtotime($poll->start_date))) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="poll-end">تاریخ پایان (اختیاری)</label>
                    <input type="datetime-local" id="poll-end" name="end_date"
                           value="<?php echo $is_edit && $poll->end_date ? esc_attr(date('Y-m-d\TH:i', strtotime($poll->end_date))) : ''; ?>">
                    <small style="color: #64748b;">بعد از این تاریخ نظرسنجی خودکار بسته می‌شود.</small>
                </div>
            </div>

            <div class="form-group">
                <label>وضعیت</label>
                <div class="novel-admin-radio-group">
                    <label>
                        <input type="radio" name="status" value="active"
                               <?php checked($is_edit && $poll->status === 'active'); ?>>
                        🟢 فعال
                    </label>
                    <label>
                        <input type="radio" name="status" value="draft"
                               <?php checked(!$is_edit || $poll->status === 'draft'); ?>>
                        📝 پیش‌نویس
                    </label>
                    <label>
                        <input type="radio" name="status" value="closed"
                               <?php checked($is_edit && $poll->status === 'closed'); ?>>
                        🔴 بسته
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="poll-novel">رمان مرتبط (اختیاری)</label>
                <select id="poll-novel" name="novel_id">
                    <option value="">— بدون رمان (عمومی) —</option>
                    <?php foreach ($novels as $novel): ?>
                        <option value="<?php echo $novel->ID; ?>"
                                <?php selected($is_edit && $poll->novel_id == $novel->ID); ?>>
                            <?php echo esc_html($novel->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small style="color: #64748b;">اگر انتخاب شود، نظرسنجی در صفحه آن رمان نمایش داده می‌شود.</small>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="button button-primary button-large">
                    <?php echo $is_edit ? '💾 بروزرسانی' : '✅ ذخیره نظرسنجی'; ?>
                </button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=novel-polls')); ?>" class="button button-large">انصراف</a>
            </div>
        </form>

        <script>
        function addPollOption() {
            const list = document.getElementById('pollOptions');
            if (list.children.length >= 10) {
                alert('حداکثر ۱۰ گزینه مجاز است.');
                return;
            }
            const li = document.createElement('li');
            const num = list.children.length + 1;
            li.innerHTML = `
                <span style="min-width: 24px; color: #64748b; font-weight: 600;">①</span>
                <input type="text" name="options[]" placeholder="گزینه ${num}">
                <button type="button" class="remove-option" onclick="this.closest('li').remove(); reindexOptions();">🗑</button>
            `;
            list.appendChild(li);
            reindexOptions();
        }

        function reindexOptions() {
            const items = document.querySelectorAll('#pollOptions li');
            const numbers = ['①','②','③','④','⑤','⑥','⑦','⑧','⑨','⑩'];
            items.forEach((item, i) => {
                const span = item.querySelector('span');
                if (span) span.textContent = numbers[i] || (i + 1);
            });
        }
        reindexOptions();
        </script>

        <?php
    }

    /**
     * صفحه نتایج نظرسنجی
     */
    private function render_results() {
        $poll_id = absint($_GET['id'] ?? 0);
        $poll = $this->polls->get_poll($poll_id);

        if (!$poll) {
            echo '<div class="notice notice-error"><p>نظرسنجی یافت نشد.</p></div>';
            return;
        }

        // محاسبه مجموع آرای گزینه‌ها
        $total_option_votes = 0;
        $max_votes = 0;
        foreach ($poll->options as $opt) {
            $total_option_votes += (int)$opt->vote_count;
            $max_votes = max($max_votes, (int)$opt->vote_count);
        }
        ?>

        <h1>📊 نتایج: <?php echo esc_html($poll->title); ?></h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=novel-polls')); ?>" class="page-title-action">← بازگشت</a>
        <hr class="wp-header-end">

        <div class="novel-admin-poll-form">
            <div class="novel-poll-stats-grid">
                <div class="novel-poll-stat-card">
                    <div class="stat-number"><?php echo number_format_i18n($poll->total_votes); ?></div>
                    <div class="stat-label">نفر رأی داده‌اند</div>
                </div>
                <div class="novel-poll-stat-card">
                    <div class="stat-number"><?php echo number_format_i18n(count($poll->options)); ?></div>
                    <div class="stat-label">گزینه</div>
                </div>
                <div class="novel-poll-stat-card">
                    <div class="stat-number"><?php echo esc_html($poll->poll_type === 'single' ? 'تک‌انتخابی' : 'چندانتخابی'); ?></div>
                    <div class="stat-label">نوع</div>
                </div>
            </div>

            <h3>نتایج</h3>
            <div class="novel-poll-results-bar">
                <?php foreach ($poll->options as $opt):
                    $count = (int)$opt->vote_count;
                    $pct = $total_option_votes > 0 ? round(($count / $total_option_votes) * 100, 1) : 0;
                    $is_winner = $count === $max_votes && $max_votes > 0;
                    ?>
                    <div class="novel-poll-result-item">
                        <div class="result-label">
                            <?php if ($is_winner): ?>🏆<?php endif; ?>
                            <?php echo esc_html($opt->option_text); ?>
                        </div>
                        <div class="result-bar-wrap">
                            <div class="result-bar" style="width: <?php echo $pct; ?>%;">
                                <?php echo $pct; ?>٪
                            </div>
                        </div>
                        <div class="result-count"><?php echo number_format_i18n($count); ?> رأی</div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($poll->end_date): ?>
                <p style="margin-top: 20px; color: #64748b;">
                    📅 پایان: <?php echo esc_html(mysql2date('Y/m/d H:i', $poll->end_date)); ?>
                    <?php
                    $remaining = $this->polls->get_remaining_time($poll);
                    if ($remaining && !$remaining['expired']) {
                        printf(' (⏳ %s روز %s ساعت مانده)', $remaining['days'], $remaining['hours']);
                    } elseif ($remaining && $remaining['expired']) {
                        echo ' (پایان یافته)';
                    }
                    ?>
                </p>
            <?php endif; ?>
        </div>

        <?php
    }
}