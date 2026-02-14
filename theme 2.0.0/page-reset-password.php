<?php
/**
 * Template Name: بازنشانی رمز عبور
 */

if (is_user_logged_in()) {
    wp_safe_redirect(home_url('/'));
    exit;
}

get_header();

$login_url = novel_get_page_url('login');
$rp_key    = isset($_GET['key']) ? sanitize_text_field(wp_unslash($_GET['key'])) : '';
$rp_login  = isset($_GET['login']) ? sanitize_text_field(wp_unslash($_GET['login'])) : '';

// بررسی اعتبار لینک
$valid_link = false;
$link_error = '';
if ($rp_key && $rp_login) {
    $user = check_password_reset_key($rp_key, $rp_login);
    if (is_wp_error($user)) {
        $link_error = __('لینک بازیابی نامعتبر یا منقضی شده. لطفاً مجدداً درخواست دهید.', 'flavor');
    } else {
        $valid_link = true;
    }
} else {
    $link_error = __('اطلاعات بازیابی ناقص. لطفاً از لینک ایمیل استفاده کنید.', 'flavor');
}
?>

<div class="novel-auth-page">
    <div class="novel-auth-overlay"></div>
    <div class="novel-auth-container">
        <div class="novel-auth-card novel-auth-card--reset">

            <div class="novel-auth-logo">
                <?php if (has_custom_logo()) : ?>
                    <?php the_custom_logo(); ?>
                <?php else : ?>
                    <a href="<?php echo esc_url(home_url('/')); ?>"><h1><?php bloginfo('name'); ?></h1></a>
                <?php endif; ?>
            </div>

            <?php if ($valid_link) : ?>

                <h2 class="novel-auth-title">
                    <svg class="novel-auth-icon" viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1s3.1 1.39 3.1 3.1v2z"/></svg>
                    <?php esc_html_e('تعیین رمز عبور جدید', 'flavor'); ?>
                </h2>

                <div class="novel-auth-messages" style="display:none;"></div>

                <form id="novel-reset-form" class="novel-auth-form" novalidate>
                    <?php wp_nonce_field('novel_auth_nonce', 'nonce'); ?>
                    <input type="hidden" name="action" value="novel_reset_password_do">
                    <input type="hidden" name="key" value="<?php echo esc_attr($rp_key); ?>">
                    <input type="hidden" name="login" value="<?php echo esc_attr($rp_login); ?>">

                    <!-- رمز جدید -->
                    <div class="novel-auth-field">
                        <label for="reset-password">
                            <?php esc_html_e('رمز عبور جدید', 'flavor'); ?>
                        </label>
                        <div class="novel-auth-password-wrap">
                            <input type="password" id="reset-password" name="password" required
                                   minlength="8"
                                   placeholder="<?php esc_attr_e('حداقل ۸ کاراکتر', 'flavor'); ?>"
                                   autocomplete="new-password">
                            <button type="button" class="novel-auth-toggle-pass" data-target="reset-password">
                                <svg class="eye-open" viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                <svg class="eye-closed" viewBox="0 0 24 24" width="20" height="20" style="display:none;"><path fill="currentColor" d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>
                            </button>
                        </div>
                        <div class="novel-auth-password-strength">
                            <div class="novel-auth-strength-bar">
                                <div class="novel-auth-strength-fill" id="password-strength-fill"></div>
                            </div>
                            <span class="novel-auth-strength-text" id="password-strength-text"></span>
                        </div>
                    </div>

                    <!-- تکرار -->
                    <div class="novel-auth-field">
                        <label for="reset-password-confirm">
                            <?php esc_html_e('تکرار رمز عبور جدید', 'flavor'); ?>
                        </label>
                        <div class="novel-auth-password-wrap">
                            <input type="password" id="reset-password-confirm" name="password_confirm" required
                                   placeholder="<?php esc_attr_e('تکرار رمز عبور', 'flavor'); ?>"
                                   autocomplete="new-password">
                            <button type="button" class="novel-auth-toggle-pass" data-target="reset-password-confirm">
                                <svg class="eye-open" viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                <svg class="eye-closed" viewBox="0 0 24 24" width="20" height="20" style="display:none;"><path fill="currentColor" d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>
                            </button>
                        </div>
                        <span class="novel-auth-field-status" id="password-match-status"></span>
                    </div>

                    <button type="submit" class="novel-auth-btn novel-auth-btn--primary" id="novel-reset-btn">
                        <span class="novel-auth-btn-text"><?php esc_html_e('تغییر رمز عبور', 'flavor'); ?></span>
                        <span class="novel-auth-btn-loading" style="display:none;">
                            <span class="novel-spinner"></span>
                            <?php esc_html_e('در حال تغییر...', 'flavor'); ?>
                        </span>
                    </button>
                </form>

            <?php else : ?>

                <div class="novel-auth-result novel-auth-result--error">
                    <div class="novel-auth-result-icon">❌</div>
                    <h2><?php esc_html_e('خطا', 'flavor'); ?></h2>
                    <p><?php echo esc_html($link_error); ?></p>
                    <a href="<?php echo esc_url(novel_get_page_url('forgot-password') ?: '#'); ?>" class="novel-auth-btn novel-auth-btn--primary">
                        <?php esc_html_e('درخواست مجدد بازیابی', 'flavor'); ?>
                    </a>
                </div>

            <?php endif; ?>

            <div class="novel-auth-footer">
                <p>
                    <a href="<?php echo esc_url($login_url ?: '#'); ?>"><?php esc_html_e('بازگشت به ورود', 'flavor'); ?></a>
                </p>
            </div>

        </div>
    </div>
</div>

<?php get_footer(); ?>