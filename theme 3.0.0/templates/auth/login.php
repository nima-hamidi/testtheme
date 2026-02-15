<?php
/**
 * Login Page Template
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (is_user_logged_in()) {
    wp_redirect(home_url('/dashboard/'));
    exit;
}

get_header();

$site_name = get_bloginfo('name');
$register_url = home_url('/register/');
$forgot_url = home_url('/forgot-password/');
$redirect_to = isset($_GET['redirect_to']) ? esc_url($_GET['redirect_to']) : '';
?>

<main class="auth-page auth-page--login">
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

            <!-- Title -->
            <div class="auth-header">
                <h1 class="auth-title">ورود به حساب</h1>
                <p class="auth-subtitle">خوش‌آمدید 👋</p>
            </div>

            <!-- Messages -->
            <div class="auth-messages" id="authMessages" aria-live="polite"></div>

            <!-- Login Form -->
            <form class="auth-form" id="loginForm" novalidate>
                <?php wp_nonce_field('novel_auth_nonce', 'nonce'); ?>
                <input type="hidden" name="action" value="novel_login">
                <?php if ($redirect_to) : ?>
                    <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">
                <?php endif; ?>

                <!-- Email/Username -->
                <div class="form-group" data-field="login">
                    <label for="login" class="form-label">ایمیل یا نام کاربری</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </span>
                        <input
                            type="text"
                            id="login"
                            name="login"
                            class="form-input"
                            placeholder="ایمیل یا نام کاربری"
                            required
                            autofocus
                            autocomplete="username"
                        >
                    </div>
                </div>

                <!-- Password -->
                <div class="form-group" data-field="password">
                    <div class="form-label-row">
                        <label for="password" class="form-label">رمز عبور</label>
                        <a href="<?php echo esc_url($forgot_url); ?>" class="forgot-link">فراموشی رمز عبور</a>
                    </div>
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
                            placeholder="رمز عبور"
                            required
                            autocomplete="current-password"
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
                </div>

                <!-- Remember Me -->
                <div class="form-group form-group--checkbox form-group--remember">
                    <label class="custom-checkbox">
                        <input type="checkbox" id="remember" name="remember" value="1" checked>
                        <span class="checkbox-mark">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </span>
                        <span class="checkbox-text">مرا به خاطر بسپار</span>
                    </label>
                </div>

                <!-- Submit -->
                <button type="submit" class="auth-btn" id="loginBtn">
                    <span class="btn-text">ورود</span>
                    <span class="btn-loading" style="display:none;">
                        <svg class="spinner" width="20" height="20" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="31.4" stroke-dashoffset="10">
                                <animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="0.8s" repeatCount="indefinite"/>
                            </circle>
                        </svg>
                        <span>در حال ورود...</span>
                    </span>
                </button>
            </form>

            <!-- Footer -->
            <div class="auth-footer">
                <div class="auth-divider">
                    <span>یا</span>
                </div>
                <p class="auth-switch">
                    حساب ندارید؟
                    <a href="<?php echo esc_url($register_url); ?>">ثبت‌نام کنید</a>
                </p>
            </div>
        </div>
    </div>
</main>

<?php get_footer(); ?>