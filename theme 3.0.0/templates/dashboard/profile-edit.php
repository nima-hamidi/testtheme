<?php
/**
 * Profile Edit Tab Content
 * Loaded via AJAX inside dashboard
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

$user_id = get_current_user_id();
$user = get_userdata($user_id);
$bio = get_user_meta($user_id, 'novel_bio', true);
$telegram = get_user_meta($user_id, 'novel_telegram', true);
$instagram = get_user_meta($user_id, 'novel_instagram', true);
$profile_color = get_user_meta($user_id, 'novel_profile_color', true) ?: 'purple';
$last_name_change = (int) get_user_meta($user_id, 'novel_last_name_change', true);
$days_since_name_change = $last_name_change ? floor((time() - $last_name_change) / 86400) : 999;
$can_change_name = $days_since_name_change >= Novel_Profile::NAME_CHANGE_COOLDOWN;
$name_days_remaining = max(0, Novel_Profile::NAME_CHANGE_COOLDOWN - $days_since_name_change);

// Mask email
$email_parts = explode('@', $user->user_email);
$masked_email = substr($email_parts[0], 0, 2) . str_repeat('•', max(3, strlen($email_parts[0]) - 2)) . '@' . ($email_parts[1] ?? '');

$notification_settings = Novel_Profile::get_user_notification_settings($user_id);
$privacy_settings = Novel_Profile::get_user_privacy_settings($user_id);

$notification_types = [
    'comment_reply'    => 'پاسخ به دیدگاه',
    'comment_like'     => 'لایک دیدگاه',
    'new_follower'     => 'فالوور جدید',
    'new_chapter'      => 'قسمت جدید رمان دنبال‌شده',
    'new_novel_author' => 'اثر جدید نویسنده دنبال‌شده',
    'mention'          => 'منشن (@)',
    'report_result'    => 'نتیجه گزارش',
    'system_message'   => 'پیام سیستمی',
    'coin_expiry'      => 'هشدار انقضای سکه',
];

$colors = ['purple', 'blue', 'green', 'red', 'orange', 'pink', 'teal', 'gray'];
?>

<div class="dashboard__content-header">
    <h2 class="dashboard__content-title">
        <span>👤</span> ویرایش پروفایل
    </h2>
</div>

<!-- Profile Form -->
<form class="profile-form" id="profileEditForm">
    
    <!-- Avatar Section -->
    <div class="form-section" style="margin-top:0; padding-top:0; border-top:none;">
        <div class="form-section__title">🖼 آواتار</div>
        <?php include get_template_directory() . '/templates/dashboard/avatar-picker.php'; ?>
    </div>

    <!-- Display Name -->
    <div class="form-group">
        <label class="form-label" for="profileDisplayName">
            <span>✏️</span> نام نمایشی
        </label>
        <input
            type="text"
            id="profileDisplayName"
            name="display_name"
            class="form-input <?php echo !$can_change_name ? 'form-input--readonly' : ''; ?>"
            value="<?php echo esc_attr($user->display_name); ?>"
            minlength="3"
            maxlength="20"
            <?php echo !$can_change_name ? 'readonly' : ''; ?>
        >
        <div class="field-feedback"></div>
        <?php if (!$can_change_name) : ?>
            <div class="name-change-notice">
                <span>⏰</span>
                شما <?php echo $name_days_remaining; ?> روز دیگر می‌توانید نام را تغییر دهید.
            </div>
        <?php endif; ?>
        <div class="form-hint">۳ تا ۲۰ کاراکتر • فارسی، انگلیسی، عدد، فاصله و آندرلاین • ماهی یک‌بار قابل تغییر</div>
    </div>

    <!-- Email -->
    <div class="form-group">
        <label class="form-label">
            <span>📧</span> ایمیل
        </label>
        <div style="display:flex; gap:10px; align-items:center;">
            <input
                type="text"
                class="form-input form-input--readonly"
                value="<?php echo esc_attr($masked_email); ?>"
                readonly
                style="flex:1;"
            >
            <button type="button" class="btn btn--outline" id="changeEmailTrigger" style="white-space:nowrap;">
                تغییر ایمیل
            </button>
        </div>
    </div>

    <!-- Email Change Steps (hidden by default) -->
    <div id="emailChangeSection" style="display:none;">
        <!-- Step 1: Enter new email -->
        <div class="email-change-step is-active" id="emailNewStep">
            <div class="form-group">
                <label class="form-label" for="newEmail">ایمیل جدید</label>
                <div style="display:flex; gap:10px;">
                    <input type="email" id="newEmail" class="form-input" placeholder="ایمیل جدید" style="flex:1;">
                    <button type="button" class="btn btn--primary" id="changeEmailBtn" style="white-space:nowrap;">
                        <span class="btn-text">ارسال کد</span>
                        <span class="btn-loading" style="display:none;">
                            <svg class="spinner" width="16" height="16" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="31.4" stroke-dashoffset="10"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur=".8s" repeatCount="indefinite"/></circle></svg>
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 2: Enter verification code -->
        <div class="email-change-step" id="emailVerifyStep">
            <p style="text-align:center; font-size:14px; color:var(--color-text-secondary); margin-bottom:12px;">
                کد ۶ رقمی ارسال‌شده به ایمیل جدید را وارد کنید:
            </p>
            <div class="verification-code-inputs">
                <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric">
                <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric">
                <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric">
                <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric">
                <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric">
                <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric">
            </div>
            <div style="text-align:center;">
                <button type="button" class="btn btn--primary" id="confirmEmailCodeBtn">تأیید کد</button>
            </div>
        </div>
    </div>

    <!-- Bio -->
    <div class="form-group" style="position:relative;">
        <label class="form-label" for="profileBio">
            <span>📝</span> بیوگرافی
        </label>
        <textarea
            id="profileBio"
            name="bio"
            class="form-textarea"
            placeholder="درباره خودت بنویس..."
            maxlength="200"
        ><?php echo esc_textarea($bio); ?></textarea>
        <span class="form-char-counter" id="bioCharCounter"><?php echo mb_strlen($bio); ?>/200</span>
    </div>

    <!-- Telegram -->
    <div class="form-group">
        <label class="form-label" for="profileTelegram">
            <span>📱</span> تلگرام
        </label>
        <input
            type="text"
            id="profileTelegram"
            name="telegram"
            class="form-input"
            placeholder="@username"
            value="<?php echo esc_attr($telegram); ?>"
            dir="ltr"
            style="text-align:left;"
        >
    </div>

    <!-- Instagram -->
    <div class="form-group">
        <label class="form-label" for="profileInstagram">
            <span>📷</span> اینستاگرام
        </label>
        <input
            type="text"
            id="profileInstagram"
            name="instagram"
            class="form-input"
            placeholder="@username"
            value="<?php echo esc_attr($instagram); ?>"
            dir="ltr"
            style="text-align:left;"
        >
    </div>

    <!-- Profile Color -->
    <div class="form-group">
        <label class="form-label">
            <span>🎨</span> رنگ تم پروفایل
        </label>
        <div class="profile-colors">
            <?php foreach ($colors as $color) : ?>
                <button
                    type="button"
                    class="profile-color-option <?php echo ($color === $profile_color) ? 'is-active' : ''; ?>"
                    data-color="<?php echo esc_attr($color); ?>"
                    aria-label="رنگ <?php echo esc_attr($color); ?>"
                ></button>
            <?php endforeach; ?>
            <input type="hidden" name="profile_color" id="profileColorInput" value="<?php echo esc_attr($profile_color); ?>">
        </div>
    </div>

    <!-- Save Profile Button -->
    <div style="display:flex; justify-content:flex-end; margin-top:8px;">
        <button type="submit" class="btn btn--primary btn--save-profile">
            <span class="btn-text">ذخیره تغییرات</span>
            <span class="btn-loading" style="display:none;">
                <svg class="spinner" width="16" height="16" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="31.4" stroke-dashoffset="10"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur=".8s" repeatCount="indefinite"/></circle></svg>
                <span>ذخیره...</span>
            </span>
        </button>
    </div>
</form>

<!-- Change Password Section -->
<div class="form-section">
    <div class="form-section__title">🔐 تغییر رمز عبور</div>
    <form class="profile-form" id="changePasswordForm">
        <div class="form-group">
            <label class="form-label" for="currentPassword">رمز عبور فعلی</label>
            <input type="password" id="currentPassword" name="current_password" class="form-input" autocomplete="current-password">
        </div>
        <div class="form-group">
            <label class="form-label" for="newPassword">رمز عبور جدید</label>
            <input type="password" id="newPassword" name="new_password" class="form-input" autocomplete="new-password" minlength="8">
            <div class="form-hint">حداقل ۸ کاراکتر + ۱ عدد + ۱ حرف بزرگ</div>
        </div>
        <div class="form-group">
            <label class="form-label" for="confirmNewPassword">تکرار رمز عبور جدید</label>
            <input type="password" id="confirmNewPassword" name="confirm_password" class="form-input" autocomplete="new-password">
        </div>
        <div style="display:flex; justify-content:flex-end;">
            <button type="submit" class="btn btn--primary btn--change-password">
                <span class="btn-text">تغییر رمز عبور</span>
                <span class="btn-loading" style="display:none;">
                    <svg class="spinner" width="16" height="16" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="31.4" stroke-dashoffset="10"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur=".8s" repeatCount="indefinite"/></circle></svg>
                    <span>تغییر...</span>
                </span>
            </button>
        </div>
    </form>
</div>

<!-- Notification Settings -->
<div class="form-section">
    <div class="form-section__title">🔔 تنظیمات اعلان</div>
    <form id="notificationSettingsForm">
        <table class="notification-settings">
            <thead>
                <tr>
                    <th>نوع اعلان</th>
                    <th>درون‌سایتی</th>
                    <th>ایمیل</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notification_types as $key => $label) : 
                    $site_on = !empty($notification_settings[$key]['site']);
                    $email_on = !empty($notification_settings[$key]['email']);
                ?>
                    <tr>
                        <td><?php echo esc_html($label); ?></td>
                        <td>
                            <label class="toggle-switch">
                                <input type="checkbox" name="notif_site_<?php echo esc_attr($key); ?>" <?php checked($site_on); ?>>
                                <span class="toggle-switch__slider"></span>
                            </label>
                        </td>
                        <td>
                            <label class="toggle-switch">
                                <input type="checkbox" name="notif_email_<?php echo esc_attr($key); ?>" <?php checked($email_on); ?>>
                                <span class="toggle-switch__slider"></span>
                            </label>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div style="display:flex; justify-content:flex-end; margin-top:16px;">
            <button type="submit" class="btn btn--primary btn--save-notifications">
                <span class="btn-text">ذخیره تنظیمات اعلان</span>
                <span class="btn-loading" style="display:none;">ذخیره...</span>
            </button>
        </div>
    </form>
</div>

<!-- Privacy Settings -->
<div class="form-section">
    <div class="form-section__title">🔒 حریم خصوصی</div>
    <form id="privacySettingsForm">
        <div class="privacy-settings">
            <div class="privacy-option">
                <span class="privacy-option__text">نمایش لیست خوانده‌ها در پروفایل عمومی</span>
                <label class="toggle-switch">
                    <input type="checkbox" name="show_reading_list" <?php checked(!empty($privacy_settings['show_reading_list'])); ?>>
                    <span class="toggle-switch__slider"></span>
                </label>
            </div>
            <div class="privacy-option">
                <span class="privacy-option__text">نمایش دستاوردها در پروفایل عمومی</span>
                <label class="toggle-switch">
                    <input type="checkbox" name="show_achievements" <?php checked(!empty($privacy_settings['show_achievements'])); ?>>
                    <span class="toggle-switch__slider"></span>
                </label>
            </div>
            <div class="privacy-option">
                <span class="privacy-option__text">نمایش آمار دیدگاه‌ها در پروفایل عمومی</span>
                <label class="toggle-switch">
                    <input type="checkbox" name="show_comment_stats" <?php checked(!empty($privacy_settings['show_comment_stats'])); ?>>
                    <span class="toggle-switch__slider"></span>
                </label>
            </div>
        </div>
        <div style="display:flex; justify-content:flex-end; margin-top:16px;">
            <button type="submit" class="btn btn--primary btn--save-privacy">
                <span class="btn-text">ذخیره تنظیمات حریم خصوصی</span>
                <span class="btn-loading" style="display:none;">ذخیره...</span>
            </button>
        </div>
    </form>
</div>

<!-- Danger Zone -->
<div class="danger-zone">
    <div class="danger-zone__header">
        <span>🔴</span>
        <h3 class="danger-zone__title">منطقه خطرناک</h3>
    </div>
    <p class="danger-zone__description">
        این عمل غیرقابل بازگشت است. تمام اطلاعات، دیدگاه‌ها، کتابخانه و سکه‌های شما حذف خواهد شد.
    </p>
    <button type="button" class="btn btn--danger" id="deleteAccountBtn">
        <span>🗑</span> حذف حساب کاربری
    </button>
</div>

<!-- Delete Account Modal -->
<div class="modal-overlay" id="deleteAccountModal">
    <div class="modal">
        <div class="modal__header">
            <div class="modal__icon">⚠️</div>
            <h3 class="modal__title">آیا مطمئنید؟</h3>
        </div>
        <div class="modal__body">
            <p style="font-size:14px; color:var(--color-text-secondary); margin:0 0 12px;">با حذف حساب:</p>
            <ul class="modal__list">
                <li>تمام اطلاعات شخصی حذف می‌شود</li>
                <li>دیدگاه‌ها ناشناس می‌شوند (نام ← «کاربر حذف‌شده»)</li>
                <li>رمان‌ها و قسمت‌ها حذف یا منتقل می‌شوند</li>
                <li>سکه‌ها و اشتراک از بین می‌رود</li>
                <li>این عمل غیرقابل بازگشت است</li>
            </ul>
            <div class="modal__password-field">
                <label for="deleteAccountPassword">برای تأیید، رمز عبور خود را وارد کنید:</label>
                <input type="password" id="deleteAccountPassword" placeholder="رمز عبور" autocomplete="current-password">
            </div>
            <div class="modal__error" id="deleteAccountError"></div>
        </div>
        <div class="modal__actions">
            <button type="button" class="btn btn--danger" id="confirmDeleteBtn">
                <span class="btn-text">❌ حذف حساب</span>
                <span class="btn-loading" style="display:none;">در حال حذف...</span>
            </button>
            <button type="button" class="btn btn--outline modal__cancel">
                ← انصراف
            </button>
        </div>
    </div>
</div>

<script>
// Inline: Email change toggle & color picker sync
jQuery(function($) {
    // Toggle email change section
    $('#changeEmailTrigger').on('click', function() {
        $('#emailChangeSection').slideToggle(300);
    });

    // Color picker → hidden input
    $(document).on('click', '.profile-color-option', function() {
        $('#profileColorInput').val($(this).data('color'));
    });

    // Notification settings save
    $('#notificationSettingsForm').on('submit', function(e) {
        e.preventDefault();
        var $btn = $(this).find('.btn--save-notifications');
        $btn.addClass('is-loading').prop('disabled', true);
        $.post(novelDash.ajaxUrl, $(this).serialize() + '&action=novel_save_notification_settings&nonce=' + novelDash.nonce, function(res) {
            $btn.removeClass('is-loading').prop('disabled', false);
            // toast handled by dashboard.js
        });
    });

    // Privacy settings save
    $('#privacySettingsForm').on('submit', function(e) {
        e.preventDefault();
        var $btn = $(this).find('.btn--save-privacy');
        $btn.addClass('is-loading').prop('disabled', true);
        $.post(novelDash.ajaxUrl, $(this).serialize() + '&action=novel_save_privacy_settings&nonce=' + novelDash.nonce, function(res) {
            $btn.removeClass('is-loading').prop('disabled', false);
        });
    });

    // Confirm email code
    $('#confirmEmailCodeBtn').on('click', function() {
        var code = '';
        $('.verification-code-inputs input').each(function() { code += $(this).val(); });
        if (code.length !== 6) return;
        var $btn = $(this);
        $btn.addClass('is-loading').prop('disabled', true);
        $.post(novelDash.ajaxUrl, {
            action: 'novel_confirm_email_change',
            nonce: novelDash.nonce,
            code: code
        }, function(res) {
            $btn.removeClass('is-loading').prop('disabled', false);
            if (res.success) {
                $('#emailChangeSection').slideUp();
                location.reload();
            }
        });
    });
});
</script>