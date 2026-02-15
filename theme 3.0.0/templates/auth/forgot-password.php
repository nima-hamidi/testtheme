<?php
/**
 * Forgot Password Page Template
 *
 * @package suspended-starter
 * @since 3.0.0
 */

get_header();

$site_name = get_bloginfo('name');
$login_url = home_url('/login/');
?>

<main class="auth-page auth-page--forgot">
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
                <h1 class="auth-title">فراموشی رمز عبور</h1>
                <p class="auth-subtitle">ایمیل حساب خود را وارد کنید تا لینک بازنشانی ارسال شود 🔑</p>
            </div>

            <!-- Messages -->
            <div class="auth-messages" id="authMessages" aria-live="polite"></div>

            <!-- Forgot Form -->
            <form class="auth-form" id="forgotForm" novalidate>
                <?php wp_nonce_field('novel_auth_nonce', 'nonce'); ?>
                <input type="hidden" name="action" value="novel_forgot_password">

                <!-- Email -->
                <div class="form-group" data-field="email">
                    <label for="email" class="form-label">ایمیل</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="4" width="20" height="16" rx="2"/>
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                            </svg>
                        </span>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-input"
                            placeholder="ایمیل ثبت‌شده شما"
                            required
                            autofocus
                            autocomplete="email"
                        >
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit" class="auth-btn" id="forgotBtn">
                    <span class="btn-text">ارسال لینک بازنشانی</span>
                    <span class="btn-loading" style="display:none;">
                        <svg class="spinner" width="20" height="20" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="31.4" stroke-dashoffset="10">
                                <animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="0.8s" repeatCount="indefinite"/>
                            </circle>
                        </svg>
                        <span>در حال ارسال...</span>
                    </span>
                </button>
            </form>

            <!-- Success State (hidden by default) -->
            <div class="auth-success-state" id="forgotSuccess" style="display:none;">
                <div class="success-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--color-success)" stroke-width="1.5">
                        <rect x="2" y="4" width="20" height="16" rx="2"/>
                        <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                    </svg>
                </div>
                <h2>ایمیل بررسی کنید 📧</h2>
                <p>اگر حسابی با این ایمیل وجود دارد، لینک بازنشانی ارسال شد.</p>
                <p class="text-muted">پوشه اسپم را هم بررسی کنید.</p>
            </div>

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