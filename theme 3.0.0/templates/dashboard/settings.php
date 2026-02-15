<?php
/**
 * Dashboard: Notification & Privacy Settings
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;
if (!is_user_logged_in()) return;

$user_id  = get_current_user_id();
$settings = get_user_meta($user_id, 'novel_notification_settings', true);
if (!is_array($settings)) {
    $settings = Novel_Notifications::get_default_settings();
}

// Save
if (isset($_POST['save_notif_settings']) && wp_verify_nonce($_POST['notif_settings_nonce'], 'novel_save_notif_settings')) {
    $new = [];
    $defaults = Novel_Notifications::get_default_settings();
    foreach ($defaults as $key => $default) {
        $new[$key] = isset($_POST['notif_' . $key]) ? true : false;
    }
    update_user_meta($user_id, 'novel_notification_settings', $new);
    $settings = $new;
    echo '<div class="form-alert form-alert--success">✅ تنظیمات ذخیره شد.</div>';
}

// Privacy settings
$show_online   = get_user_meta($user_id, 'novel_show_online', true) !== '0';
$show_activity = get_user_meta($user_id, 'novel_show_activity', true) !== '0';

if (isset($_POST['save_privacy_settings']) && wp_verify_nonce($_POST['privacy_settings_nonce'], 'novel_save_privacy')) {
    update_user_meta($user_id, 'novel_show_online', isset($_POST['show_online']) ? '1' : '0');
    update_user_meta($user_id, 'novel_show_activity', isset($_POST['show_activity']) ? '1' : '0');
    $show_online = isset($_POST['show_online']);
    $show_activity = isset($_POST['show_activity']);
    echo '<div class="form-alert form-alert--success">✅ تنظیمات حریم خصوصی ذخیره شد.</div>';
}

$notif_types = [
    'new_chapter'    => '📖 قسمت جدید رمان‌هایی که دنبال می‌کنم',
    'new_novel'      => '📚 رمان جدید نویسندگانی که دنبال می‌کنم',
    'comment_reply'  => '💬 پاسخ به دیدگاه‌هایم',
    'comment_like'   => '👍 لایک دیدگاه‌هایم',
    'mention'        => '📢 منشن شدن در دیدگاه',
    'new_follower'   => '❤ دنبال‌کننده جدید',
    'novel_approved' => '✅ تأیید رمان',
    'novel_rejected' => '❌ رد رمان',
    'author_review'  => '⭐ نظر درباره من',
    'report_result'  => '📋 نتیجه گزارش',
    'coin_expiry'    => '⚠️ انقضای سکه',
    'achievement'    => '🏆 دستاورد جدید',
    'system'         => '🔔 پیام‌های سیستمی',
];
?>

<div class="dashboard-settings-page">

    <!-- Notification Settings -->
    <section class="settings-section">
        <h2 class="dashboard-form-title">🔔 تنظیمات اعلان‌ها</h2>
        <p class="settings-desc">مشخص کنید چه اعلان‌هایی دریافت کنید.</p>

        <form method="post">
            <?php wp_nonce_field('novel_save_notif_settings', 'notif_settings_nonce'); ?>

            <div class="notif-settings-table">
                <div class="notif-settings-header">
                    <span class="ns-col-type">نوع اعلان</span>
                    <span class="ns-col-site">درون‌سایتی</span>
                    <span class="ns-col-email">ایمیل</span>
                </div>

                <?php foreach ($notif_types as $key => $label) : ?>
                <div class="notif-settings-row">
                    <span class="ns-col-type"><?php echo $label; ?></span>
                    <span class="ns-col-site">
                        <label class="toggle-switch">
                            <input type="checkbox" name="notif_<?php echo $key; ?>_site" value="1"
                                   <?php checked($settings[$key . '_site'] ?? true); ?> />
                            <span class="toggle-slider"></span>
                        </label>
                    </span>
                    <span class="ns-col-email">
                        <label class="toggle-switch">
                            <input type="checkbox" name="notif_<?php echo $key; ?>_email" value="1"
                                   <?php checked($settings[$key . '_email'] ?? false); ?> />
                            <span class="toggle-slider"></span>
                        </label>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>

            <button type="submit" name="save_notif_settings" value="1" class="btn-submit-novel" style="margin-top:16px;">
                💾 ذخیره تنظیمات اعلان
            </button>
        </form>
    </section>

    <!-- Privacy Settings -->
    <section class="settings-section" style="margin-top:32px;">
        <h2 class="dashboard-form-title">🔒 حریم خصوصی</h2>

        <form method="post">
            <?php wp_nonce_field('novel_save_privacy', 'privacy_settings_nonce'); ?>

            <div class="privacy-options">
                <label class="privacy-option">
                    <input type="checkbox" name="show_online" value="1" <?php checked($show_online); ?> />
                    <span>نمایش وضعیت آنلاین من به دیگران</span>
                </label>
                <label class="privacy-option">
                    <input type="checkbox" name="show_activity" value="1" <?php checked($show_activity); ?> />
                    <span>نمایش فعالیت من (دیدگاه‌ها، امتیازها) در پروفایل عمومی</span>
                </label>
            </div>

            <button type="submit" name="save_privacy_settings" value="1" class="btn-submit-novel" style="margin-top:16px;">
                💾 ذخیره حریم خصوصی
            </button>
        </form>
    </section>
</div>