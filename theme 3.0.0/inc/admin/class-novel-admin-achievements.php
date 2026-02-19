<?php
/**
 * Novel Admin Achievements
 * مدیریت دستاوردها و چالش‌ها در ادمین
 * 
 * @package suspended developer
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

class Novel_Admin_Achievements {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'handle_forms']);
    }

    public function add_menu() {
        add_submenu_page(
            'novel-settings',
            'دستاوردها و چالش‌ها',
            '🏆 دستاوردها',
            'manage_options',
            'novel-achievements',
            [$this, 'render_page']
        );
    }

    public function handle_forms() {
        if (!isset($_POST['novel_ach_action'])) return;
        if (!current_user_can('manage_options')) return;
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'novel_ach_form')) return;

        $action = sanitize_text_field($_POST['novel_ach_action']);

        switch ($action) {
            case 'toggle_achievement':
                $this->toggle_achievement();
                break;
            case 'manual_award':
                $this->manual_award();
                break;
            case 'create_challenge':
                $this->create_challenge();
                break;
        }
    }

    private function toggle_achievement() {
        global $wpdb;
        $id = absint($_POST['achievement_id'] ?? 0);
        $active = absint($_POST['is_active'] ?? 0);

        $wpdb->update("{$wpdb->prefix}achievements", ['is_active' => $active], ['id' => $id]);
        wp_cache_delete('novel_achievement_defs', 'novel');

        wp_redirect(admin_url('admin.php?page=novel-achievements&message=updated'));
        exit;
    }

    private function manual_award() {
        $user_id = absint($_POST['user_id'] ?? 0);
        $achievement_id = absint($_POST['achievement_id'] ?? 0);

        if ($user_id && $achievement_id) {
            Novel_Achievements::get_instance()->manual_award($user_id, $achievement_id);
        }

        wp_redirect(admin_url('admin.php?page=novel-achievements&tab=manual&message=awarded'));
        exit;
    }

    private function create_challenge() {
        if (!class_exists('Novel_Challenges')) return;

        Novel_Challenges::get_instance()->create_challenge($_POST);

        wp_redirect(admin_url('admin.php?page=novel-achievements&tab=challenges&message=created'));
        exit;
    }

    public function render_page() {
        $tab = sanitize_text_field($_GET['tab'] ?? 'list');
        $msg = sanitize_text_field($_GET['message'] ?? '');

        echo '<div class="wrap novel-admin-wrap">';
        echo '<h1>🏆 دستاوردها و چالش‌ها</h1>';

        if ($msg) {
            $msgs = [
                'updated' => 'بروزرسانی شد. ✅',
                'awarded' => 'مدال اعطا شد. ✅',
                'created' => 'چالش ساخته شد. ✅',
            ];
            if (isset($msgs[$msg])) {
                printf('<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html($msgs[$msg]));
            }
        }

        // تب‌ها
        $tabs = [
            'list'       => '📋 لیست دستاوردها',
            'manual'     => '🎁 اعطای دستی',
            'challenges' => '📚 چالش‌ها',
            'stats'      => '📊 آمار',
        ];
        echo '<nav class="nav-tab-wrapper">';
        foreach ($tabs as $key => $label) {
            $active = $tab === $key ? 'nav-tab-active' : '';
            printf(
                '<a href="%s" class="nav-tab %s">%s</a>',
                esc_url(admin_url('admin.php?page=novel-achievements&tab=' . $key)),
                $active,
                $label
            );
        }
        echo '</nav>';

        echo '<div style="margin-top: 20px;">';

        switch ($tab) {
            case 'manual':
                $this->render_manual_award();
                break;
            case 'challenges':
                $this->render_challenges();
                break;
            case 'stats':
                $this->render_stats();
                break;
            default:
                $this->render_list();
        }

        echo '</div></div>';
    }

    private function render_list() {
        global $wpdb;

        $achievements = $wpdb->get_results(
            "SELECT a.*, 
                    (SELECT COUNT(*) FROM {$wpdb->prefix}user_achievements WHERE achievement_id = a.id) as earned_count
             FROM {$wpdb->prefix}achievements a
             ORDER BY a.category, a.sort_order"
        );

        $categories = Novel_Achievements::get_instance()->get_categories();
        $current_cat = '';
        ?>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 40px;">آیکون</th>
                    <th>عنوان</th>
                    <th style="width: 200px;">شرط</th>
                    <th style="width: 80px;">امتیاز</th>
                    <th style="width: 80px;">کسب‌شده</th>
                    <th style="width: 80px;">وضعیت</th>
                    <th style="width: 100px;">عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($achievements as $a):
                    if ($a->category !== $current_cat):
                        $current_cat = $a->category;
                        $cat_info = $categories[$current_cat] ?? ['label' => $current_cat, 'color' => '#64748b'];
                        ?>
                        <tr style="background: <?php echo $cat_info['color']; ?>15;">
                            <td colspan="7" style="font-weight: 700; font-size: 15px; color: <?php echo $cat_info['color']; ?>;">
                                <?php echo $cat_info['label']; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <tr <?php echo !$a->is_active ? 'style="opacity: 0.5;"' : ''; ?>>
                        <td style="font-size: 24px; text-align: center;"><?php echo $a->icon; ?></td>
                        <td>
                            <strong><?php echo esc_html($a->title); ?></strong>
                            <br><small style="color: #64748b;"><?php echo esc_html($a->description); ?></small>
                        </td>
                        <td>
                            <code><?php echo esc_html($a->condition_type); ?></code> ≥ <?php echo number_format_i18n($a->condition_value); ?>
                        </td>
                        <td style="text-align: center; font-weight: 600;"><?php echo $a->points; ?></td>
                        <td style="text-align: center;">
                            <strong><?php echo number_format_i18n($a->earned_count); ?></strong> نفر
                        </td>
                        <td style="text-align: center;">
                            <?php echo $a->is_active ? '🟢' : '🔴'; ?>
                        </td>
                        <td>
                            <form method="post" style="display: inline;">
                                <?php wp_nonce_field('novel_ach_form'); ?>
                                <input type="hidden" name="novel_ach_action" value="toggle_achievement">
                                <input type="hidden" name="achievement_id" value="<?php echo $a->id; ?>">
                                <input type="hidden" name="is_active" value="<?php echo $a->is_active ? 0 : 1; ?>">
                                <button type="submit" class="button button-small">
                                    <?php echo $a->is_active ? 'غیرفعال' : 'فعال'; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    private function render_manual_award() {
        global $wpdb;

        $achievements = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}achievements WHERE is_active = 1 ORDER BY category, sort_order"
        );
        ?>

        <div style="max-width: 600px; background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3>🎁 اعطای دستی مدال</h3>
            <form method="post">
                <?php wp_nonce_field('novel_ach_form'); ?>
                <input type="hidden" name="novel_ach_action" value="manual_award">

                <p>
                    <label><strong>شناسه کاربر:</strong></label><br>
                    <input type="number" name="user_id" required class="regular-text" placeholder="User ID">
                </p>

                <p>
                    <label><strong>دستاورد:</strong></label><br>
                    <select name="achievement_id" required class="regular-text">
                        <option value="">— انتخاب کنید —</option>
                        <?php foreach ($achievements as $a): ?>
                            <option value="<?php echo $a->id; ?>">
                                <?php echo $a->icon . ' ' . esc_html($a->title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>

                <button type="submit" class="button button-primary">🎁 اعطای مدال</button>
            </form>
        </div>
        <?php
    }

    private function render_challenges() {
        if (!class_exists('Novel_Challenges')) {
            echo '<p>ماژول چالش فعال نیست.</p>';
            return;
        }

        $challenges_instance = Novel_Challenges::get_instance();

        // فرم ساخت چالش
        $genres = get_terms(['taxonomy' => 'genre', 'hide_empty' => false]);
        ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
            <!-- فرم -->
            <div style="background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h3>📚 ساخت چالش جدید</h3>
                <form method="post">
                    <?php wp_nonce_field('novel_ach_form'); ?>
                    <input type="hidden" name="novel_ach_action" value="create_challenge">

                    <p>
                        <label><strong>عنوان:</strong></label><br>
                        <input type="text" name="title" required class="regular-text" placeholder="مثال: چالش پاییزی">
                    </p>
                    <p>
                        <label><strong>توضیحات:</strong></label><br>
                        <textarea name="description" class="large-text" rows="3"></textarea>
                    </p>
                    <p>
                        <label><strong>نوع:</strong></label><br>
                        <select name="challenge_type" class="regular-text">
                            <option value="chapter_count">تعداد قسمت خوانده</option>
                            <option value="novel_count">تعداد رمان</option>
                            <option value="genre_specific">ژانر خاص</option>
                            <option value="diverse">تنوع ژانر</option>
                        </select>
                    </p>
                    <p>
                        <label><strong>هدف (عدد):</strong></label><br>
                        <input type="number" name="target_value" required value="10" min="1" class="small-text">
                    </p>
                    <p>
                        <label><strong>ژانر (اگر نوع=ژانر خاص):</strong></label><br>
                        <select name="genre_id" class="regular-text">
                            <option value="">— بدون ژانر —</option>
                            <?php foreach ($genres as $g): ?>
                                <option value="<?php echo $g->term_id; ?>"><?php echo esc_html($g->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <p>
                        <label><strong>شروع:</strong></label><br>
                        <input type="datetime-local" name="start_date" required>
                    </p>
                    <p>
                        <label><strong>پایان:</strong></label><br>
                        <input type="datetime-local" name="end_date" required>
                    </p>
                    <p>
                        <label><strong>جایزه سکه:</strong></label><br>
                        <input type="number" name="reward_coins" value="0" min="0" class="small-text">
                    </p>
                    <p>
                        <label><strong>آیکون:</strong></label><br>
                        <input type="text" name="icon" value="📚" class="small-text">
                    </p>
                    <p>
                        <label><strong>وضعیت:</strong></label><br>
                        <select name="status">
                            <option value="active">فعال</option>
                            <option value="upcoming">آینده</option>
                            <option value="draft">پیش‌نویس</option>
                        </select>
                    </p>

                    <button type="submit" class="button button-primary">✅ ساخت چالش</button>
                </form>
            </div>

            <!-- لیست -->
            <div style="background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h3>📋 چالش‌های موجود</h3>
                <?php
                global $wpdb;
                $all_challenges = $wpdb->get_results(
                    "SELECT c.*,
                            (SELECT COUNT(*) FROM {$wpdb->prefix}challenge_participants WHERE challenge_id = c.id) as participants
                     FROM {$wpdb->prefix}reading_challenges c
                     ORDER BY c.created_at DESC LIMIT 20"
                );
                if (empty($all_challenges)): ?>
                    <p style="color: #64748b;">هنوز چالشی ساخته نشده.</p>
                <?php else: ?>
                    <table class="widefat fixed striped" style="margin-top: 10px;">
                        <thead><tr><th>عنوان</th><th>شرکت‌کنندگان</th><th>وضعیت</th><th>پایان</th></tr></thead>
                        <tbody>
                            <?php foreach ($all_challenges as $ch): ?>
                                <tr>
                                    <td><?php echo $ch->icon . ' ' . esc_html($ch->title); ?></td>
                                    <td><?php echo number_format_i18n($ch->participants); ?> نفر</td>
                                    <td><?php echo esc_html($ch->status); ?></td>
                                    <td><?php echo esc_html(mysql2date('Y/m/d', $ch->end_date)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    private function render_stats() {
        global $wpdb;

        $top_achievements = $wpdb->get_results(
            "SELECT a.icon, a.title, COUNT(ua.id) as earned_count
             FROM {$wpdb->prefix}achievements a
             INNER JOIN {$wpdb->prefix}user_achievements ua ON a.id = ua.achievement_id
             GROUP BY a.id ORDER BY earned_count DESC LIMIT 10"
        );

        $total_earned = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}user_achievements");
        $total_users = $wpdb->get_var(
            "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}user_achievements"
        );
        ?>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px;">
            <div style="background: #fff; padding: 20px; border-radius: 12px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <div style="font-size: 28px; font-weight: 700; color: #6366f1;"><?php echo number_format_i18n($total_earned); ?></div>
                <div style="font-size: 13px; color: #64748b;">مدال اعطا شده</div>
            </div>
            <div style="background: #fff; padding: 20px; border-radius: 12px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <div style="font-size: 28px; font-weight: 700; color: #10b981;"><?php echo number_format_i18n($total_users); ?></div>
                <div style="font-size: 13px; color: #64748b;">کاربر دارای مدال</div>
            </div>
            <div style="background: #fff; padding: 20px; border-radius: 12px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <div style="font-size: 28px; font-weight: 700; color: #f59e0b;">
                    <?php echo $total_users > 0 ? round($total_earned / $total_users, 1) : 0; ?>
                </div>
                <div style="font-size: 13px; color: #64748b;">میانگین مدال/کاربر</div>
            </div>
        </div>

        <div style="background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3>🏅 پرکسب‌ترین دستاوردها</h3>
            <table class="widefat fixed striped" style="margin-top: 10px;">
                <thead><tr><th style="width: 40px;">#</th><th style="width: 40px;"></th><th>دستاورد</th><th style="width: 100px;">تعداد</th></tr></thead>
                <tbody>
                    <?php $rank = 0; foreach ($top_achievements as $ta): $rank++; ?>
                        <tr>
                            <td style="font-weight: 700;"><?php echo $rank; ?></td>
                            <td style="font-size: 20px;"><?php echo $ta->icon; ?></td>
                            <td><?php echo esc_html($ta->title); ?></td>
                            <td style="font-weight: 600;"><?php echo number_format_i18n($ta->earned_count); ?> نفر</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}