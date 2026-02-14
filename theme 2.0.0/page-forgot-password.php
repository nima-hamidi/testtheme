<?php
/**
 * Template Name: فراموشی رمز عبور
 */

if (is_user_logged_in()) {
    wp_safe_redirect(home_url('/'));
    exit;
}

get_header();
$login_url = novel_get_page_url('login');
?>

<div class="novel-auth-page">
    <div class="novel-auth-overlay"></div>
    <div class="novel-auth-container">
        <div class="novel-auth-card novel-auth-card--forgot">

            <div class="novel-auth-logo">
                <?php if (has_custom_logo()) : ?>
                    <?php the_custom_logo(); ?>
                <?php else : ?>
                    <a href="<?php echo esc_url(home_url('/')); ?>"><h1><?php bloginfo('name'); ?></h1></a>
                <?php endif; ?>
            </div>

            <h2 class="novel-auth-title">
                <svg class="novel-auth-icon" viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1s3.1 1.39 3.1 3.1v2z"/></svg>
                <?php esc_html_e('بازیابی رمز عبور', 'flavor'); ?>
            </h2>

            <p class="novel-auth-subtitle">
                <?php esc_html_e('ایمیل حساب خود را وارد کنید تا لینک بازیابی برایتان ارسال شود.', 'flavor'); ?>
            </p>

            <div class="novel-auth-messages" style="display:none;"></div>

            <!-- فرم درخواست -->
            <form id="novel-forgot-form" class="novel-auth-form" novalidate>
                <?php wp_nonce_field('novel_auth_nonce', 'nonce'); ?>
                <input type="hidden" name="action" value="novel_reset_password_request">

                <div class="novel-auth-field">
                    <label for="forgot-email">
                        <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                        <?php esc_html_e('ایمیل', 'flavor'); ?>
                    </label>
                    <input type="email" id="forgot-email" name="email" required
                           placeholder="<?php esc_attr_e('ایمیل ثبت‌شده خود را وارد کنید', 'flavor'); ?>"
                           autocomplete="email">
                </div>

                <button type="submit" class="novel-auth-btn novel-auth-btn--primary" id="novel-forgot-btn">
                    <span class="novel-auth-btn-text"><?php esc_html_e('ارسال لینک بازیابی', 'flavor'); ?></span>
                    <span class="novel-auth-btn-loading" style="display:none;">
                        <span class="novel-spinner"></span>
                        <?php esc_html_e('در حال ارسال...', 'flavor'); ?>
                    </span>
                </button>
            </form>

            <div class="novel-auth-footer">
                <p>
                    <a href="<?php echo esc_url($login_url ?: '#'); ?>"><?php esc_html_e('بازگشت به صفحه ورود', 'flavor'); ?></a>
                </p>
            </div>

        </div>
    </div>
</div>

<?php get_footer(); ?>