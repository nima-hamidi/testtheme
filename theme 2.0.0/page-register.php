<?php
/**
 * Template Name: صفحه ثبت‌نام
 */

if (is_user_logged_in()) {
    wp_safe_redirect(novel_get_page_url('user-dashboard') ?: home_url('/'));
    exit;
}

get_header();

$login_url = novel_get_page_url('login');
$rules_url = get_privacy_policy_url() ?: '#';
// صفحه قوانین دیدگاه - اگر slug دارید عوض کنید
$comment_rules_url = novel_get_page_url('comment-rules') ?: $rules_url;
?>

<div class="novel-auth-page">
    <div class="novel-auth-overlay"></div>
    <div class="novel-auth-container">
        <div class="novel-auth-card novel-auth-card--register">

            <div class="novel-auth-logo">
                <?php if (has_custom_logo()) : ?>
                    <?php the_custom_logo(); ?>
                <?php else : ?>
                    <a href="<?php echo esc_url(home_url('/')); ?>"><h1><?php bloginfo('name'); ?></h1></a>
                <?php endif; ?>
            </div>

            <h2 class="novel-auth-title">
                <svg class="novel-auth-icon" viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                <?php esc_html_e('ایجاد حساب کاربری', 'flavor'); ?>
            </h2>

            <div class="novel-auth-messages" style="display:none;"></div>

            <form id="novel-register-form" class="novel-auth-form" novalidate>
                <?php wp_nonce_field('novel_auth_nonce', 'nonce'); ?>
                <input type="hidden" name="action" value="novel_register">

                <!-- Honeypot -->
                <div style="position:absolute;left:-9999px;" aria-hidden="true">
                    <input type="text" name="website_url" tabindex="-1" autocomplete="off">
                </div>

                <!-- نام نمایشی -->
                <div class="novel-auth-field">
                    <label for="reg-display-name">
                        <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                        <?php esc_html_e('نام نمایشی', 'flavor'); ?>
                    </label>
                    <input type="text" id="reg-display-name" name="display_name" required
                           minlength="3" maxlength="20"
                           placeholder="<?php esc_attr_e('۳ تا ۲۰ کاراکتر', 'flavor'); ?>"
                           autocomplete="nickname">
                    <span class="novel-auth-field-status" id="display-name-status"></span>
                </div>

                <!-- ایمیل -->
                <div class="novel-auth-field">
                    <label for="reg-email">
                        <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                        <?php esc_html_e('ایمیل', 'flavor'); ?>
                    </label>
                    <input type="email" id="reg-email" name="email" required
                           placeholder="<?php esc_attr_e('ایمیل خود را وارد کنید', 'flavor'); ?>"
                           autocomplete="email">
                </div>

                <!-- رمز عبور -->
                <div class="novel-auth-field">
                    <label for="reg-password">
                        <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1s3.1 1.39 3.1 3.1v2z"/></svg>
                        <?php esc_html_e('رمز عبور', 'flavor'); ?>
                    </label>
                    <div class="novel-auth-password-wrap">
                        <input type="password" id="reg-password" name="password" required
                               minlength="8"
                               placeholder="<?php esc_attr_e('حداقل ۸ کاراکتر', 'flavor'); ?>"
                               autocomplete="new-password">
                        <button type="button" class="novel-auth-toggle-pass" data-target="reg-password" aria-label="<?php esc_attr_e('نمایش رمز', 'flavor'); ?>">
                            <svg class="eye-open" viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                            <svg class="eye-closed" viewBox="0 0 24 24" width="20" height="20" style="display:none;"><path fill="currentColor" d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>
                        </button>
                    </div>
                    <!-- نوار قدرت رمز -->
                    <div class="novel-auth-password-strength">
                        <div class="novel-auth-strength-bar">
                            <div class="novel-auth-strength-fill" id="password-strength-fill"></div>
                        </div>
                        <span class="novel-auth-strength-text" id="password-strength-text"></span>
                    </div>
                </div>

                <!-- تکرار رمز عبور -->
                <div class="novel-auth-field">
                    <label for="reg-password-confirm">
                        <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1s3.1 1.39 3.1 3.1v2z"/></svg>
                        <?php esc_html_e('تکرار رمز عبور', 'flavor'); ?>
                    </label>
                    <div class="novel-auth-password-wrap">
                        <input type="password" id="reg-password-confirm" name="password_confirm" required
                               placeholder="<?php esc_attr_e('رمز عبور را تکرار کنید', 'flavor'); ?>"
                               autocomplete="new-password">
                        <button type="button" class="novel-auth-toggle-pass" data-target="reg-password-confirm" aria-label="<?php esc_attr_e('نمایش رمز', 'flavor'); ?>">
                            <svg class="eye-open" viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                            <svg class="eye-closed" viewBox="0 0 24 24" width="20" height="20" style="display:none;"><path fill="currentColor" d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>
                        </button>
                    </div>
                    <span class="novel-auth-field-status" id="password-match-status"></span>
                </div>

                <!-- تیک قوانین -->
                <div class="novel-auth-field novel-auth-field--rules">
                    <label class="novel-auth-checkbox novel-auth-checkbox--rules">
                        <input type="checkbox" name="accept_rules" value="1" required id="reg-accept-rules">
                        <span class="novel-auth-checkmark novel-auth-checkmark--fancy"></span>
                        <span class="novel-auth-rules-text">
                            <?php
                            printf(
                                /* translators: 1: site rules link, 2: comment rules link */
                                esc_html__('%1$s و %2$s را خوانده‌ام و می‌پذیرم.', 'flavor'),
                                '<a href="' . esc_url($rules_url) . '" target="_blank">' . esc_html__('قوانین سایت', 'flavor') . '</a>',
                                '<a href="' . esc_url($comment_rules_url) . '" target="_blank">' . esc_html__('قوانین دیدگاه‌گذاری', 'flavor') . '</a>'
                            );
                            ?>
                        </span>
                    </label>
                </div>

                <!-- دکمه ثبت‌نام -->
                <button type="submit" class="novel-auth-btn novel-auth-btn--primary" id="novel-register-btn">
                    <span class="novel-auth-btn-text"><?php esc_html_e('ثبت‌نام', 'flavor'); ?></span>
                    <span class="novel-auth-btn-loading" style="display:none;">
                        <span class="novel-spinner"></span>
                        <?php esc_html_e('در حال ثبت‌نام...', 'flavor'); ?>
                    </span>
                </button>
            </form>

            <div class="novel-auth-footer">
                <p>
                    <?php esc_html_e('قبلاً حساب دارید؟', 'flavor'); ?>
                    <a href="<?php echo esc_url($login_url ?: '#'); ?>"><?php esc_html_e('وارد شوید', 'flavor'); ?></a>
                </p>
            </div>

        </div>
    </div>
</div>

<?php get_footer(); ?>