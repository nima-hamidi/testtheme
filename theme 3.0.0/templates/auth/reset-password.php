<?php
/**
 * Reset Password Page Template
 *
 * @package suspended-starter
 * @since 3.0.0
 */

get_header();

$site_name = get_bloginfo('name');
$login_url = home_url('/login/');
$token = isset($_GET['novel_reset']) ? sanitize_text_field($_GET['novel_reset']) : '';
$email = isset($_GET['email']) ? sanitize_email(urldecode($_GET['email'])) : '';

// Validate token exists
$valid_token = false;
$expired = false;

if (!empty($token) && !empty($email)) {
    $user = get_user_by('email', $email);
    if ($user) {
        $stored_token = get_user_meta($user->ID, 'novel_reset_token', true);
        $expiry = (int) get_user_meta($user->ID, 'novel_reset_expiry', true);
        
        if ($token === $stored_token) {
            if (time() <= $expiry) {
                $valid_token = true;
            } else {
                $expired = true;
            }
        }
    }
}
?>

<main class="auth-page auth-page--reset">
    <div class="auth-background">
        <div class="auth-bg-pattern"></div>
        <div class="auth-bg-gradient"></div>
    </div>

    <div class="auth-container">
        <div class="auth-card" data-animate="fadeInUp">
            <!-- Logo -->
            <div class="auth-logo">
                <?php
                $logo_id = get_option('novel_logo_id');
                if ($logo_id) {
                    echo wp_get_attachment_image($logo_id, 'medium', false, ['class' => 'auth-logo__img', 'alt' => esc_attr($site_name)]);
                } else {
                    echo '<div class="auth-logo__text">' . esc_html($site_name) . '</div>';
                }
                ?>
            </div>

            <?php if (!$valid_token && !$expired) : ?>
                <!-- Invalid Token -->
                <div class="auth-header">
                    <h1 class="auth-title">لینک نامعتبر</h1>
                </div>
                <div class="auth-error-state">
                    <div class="error-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--color-danger)" stroke-width="1.5">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="15" y1="9" x2="9" y2="15"/>
                            <line x1="9" y1="9" x2="15" y2="15"/>
                        </svg>
                    </div>
                    <p>لینک بازنشانی رمز عبور نامعتبر است یا قبلاً استفاده شده.</p>
                    <a href="<?php echo esc_url(home_url('/forgot-password/')); ?>" class="auth-btn" style="margin-top:15px;">
                        <span class="btn-text">درخواست لینک جدید</span>
                    </a>
                </div>

            <?php elseif ($expired) : ?>
                <!-- Expired Token -->
                <div class="auth-header">
                    <h1 class="auth-title">لینک منقضی شده</h1>
                </div>
                <div class="auth-error-state">
                    <div class="error-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--color-warning)" stroke-width="1.5">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <p>لینک بازنشانی منقضی شده. لطفاً دوباره درخواست دهید.</p>
                    <a href="<?php echo esc_url(home_url('/forgot-password/')); ?>" class="auth-btn" style="margin-top:15px;">
                        <span class="btn-text">درخواست لینک جدید</span>
                    </a>
                </div>

            <?php else : ?>
                <!-- Valid Token - Show Reset Form -->
                <div class="auth-header">
                    <h1 class="auth-title">بازنشانی رمز عبور</h1>
                    <p class="auth-subtitle">رمز عبور جدید خود را وارد کنید 🔐</p>
                </div>

                <div class="auth-messages" id="authMessages" aria-live="polite"></div>

                <form class="auth-form" id="resetForm" novalidate>
                    <?php wp_nonce_field('novel_auth_nonce', 'nonce'); ?>
                    <input type="hidden" name="action" value="novel_reset_password">
                    <input type="hidden" name="token" value="<?php echo esc_attr($token); ?>">
                    <input type="hidden" name="email" value="<?php echo esc_attr($email); ?>">

                    <!-- New Password -->
                    <div class="form-group" data-field="password">
                        <label for="password" class="form-label">رمز عبور جدید</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                            </span>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-input"
                                placeholder="رمز عبور جدید"
                                minlength="8"
                                required
                                autofocus
                                autocomplete="new-password"
                            >
                            <button type="button" class="toggle-password" data-target="password" aria-label="نمایش رمز عبور">
                                <svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg class="eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            </button>
                        </div>
                        <div class="password-strength" id="passwordStrength">
                            <div class="strength-bar">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                            <span class="strength-text" id="strengthText"></span>
                        </div>
                        <div class="password-requirements" id="passwordRequirements">
                            <div class="requirement" data-req="length">
                                <span class="req-icon">✗</span>
                                <span>حداقل ۸ کاراکتر</span>
                            </div>
                            <div class="requirement" data-req="number">
                                <span class="req-icon">✗</span>
                                <span>حداقل ۱ عدد</span>
                            </div>
                            <div class="requirement" data-req="uppercase">
                                <span class="req-icon">✗</span>
                                <span>حداقل ۱ حرف بزرگ انگلیسی</span>
                            </div>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group" data-field="password_confirm">
                        <label for="password_confirm" class="form-label">تکرار رمز عبور جدید</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                </svg>
                            </span>
                            <input
                                type="password"
                                id="password_confirm"
                                name="password_confirm"
                                class="form-input"
                                placeholder="تکرار رمز عبور جدید"
                                required
                                autocomplete="new-password"
                            >
                            <button type="button" class="toggle-password" data-target="password_confirm" aria-label="نمایش رمز عبور">
                                <svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg class="eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            </button>
                        </div>
                        <div class="field-feedback" id="confirmFeedback"></div>
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="auth-btn" id="resetBtn">
                        <span class="btn-text">تغییر رمز عبور</span>
                        <span class="btn-loading" style="display:none;">
                            <svg class="spinner" width="20" height="20" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="31.4" stroke-dashoffset="10">
                                    <animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="0.8s" repeatCount="indefinite"/>
                                </circle>
                            </svg>
                            <span>در حال بازنشانی...</span>
                        </span>
                    </button>
                </form>
            <?php endif; ?>

            <!-- Footer -->
            <div class="auth-footer">
                <p class="auth-switch">
                    <a href="<?php echo esc_url($login_url); ?>">← بازگشت به صفحه ورود</a>
                </p>
            </div>
        </div>
    </div>
</main>

<?php get_footer(); ?>