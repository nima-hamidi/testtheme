<?php
/**
 * Registration Page Template
 *
 * @package suspended-starter
 * @since 3.0.0
 */

// Redirect if logged in
if (is_user_logged_in()) {
    wp_redirect(home_url('/dashboard/'));
    exit;
}

get_header();

$site_name = get_bloginfo('name');
$rules_page = get_option('novel_rules_page', '#');
$comment_rules_page = get_option('novel_comment_rules_page', '#');
$login_url = home_url('/login/');
?>

<main class="auth-page auth-page--register">
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
                <h1 class="auth-title">ثبت‌نام در <?php echo esc_html($site_name); ?></h1>
                <p class="auth-subtitle">به دنیای داستان‌ها بپیوند ✨</p>
            </div>

            <!-- Messages container -->
            <div class="auth-messages" id="authMessages" aria-live="polite"></div>

            <!-- Registration Form -->
            <form class="auth-form" id="registerForm" novalidate>
                <?php wp_nonce_field('novel_auth_nonce', 'nonce'); ?>
                <input type="hidden" name="action" value="novel_register">

                <!-- Honeypot -->
                <div style="display:none;" aria-hidden="true">
                    <input type="text" name="website_url" tabindex="-1" autocomplete="off">
                </div>

                <!-- Display Name -->
                <div class="form-group" data-field="display_name">
                    <label for="display_name" class="form-label">نام نمایشی</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </span>
                        <input
                            type="text"
                            id="display_name"
                            name="display_name"
                            class="form-input"
                            placeholder="نام نمایشی شما"
                            minlength="3"
                            maxlength="20"
                            required
                            autocomplete="nickname"
                        >
                        <span class="input-status" id="displayNameStatus"></span>
                    </div>
                    <div class="field-feedback" id="displayNameFeedback"></div>
                    <div class="field-suggestions" id="displayNameSuggestions"></div>
                </div>

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
                            placeholder="ایمیل شما"
                            required
                            autocomplete="email"
                        >
                        <span class="input-status" id="emailStatus"></span>
                    </div>
                    <div class="field-feedback" id="emailFeedback"></div>
                </div>

                <!-- Password -->
                <div class="form-group" data-field="password">
                    <label for="password" class="form-label">رمز عبور</label>
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
                            minlength="8"
                            required
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
                    <!-- Password Strength -->
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
                    <label for="password_confirm" class="form-label">تکرار رمز عبور</label>
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
                            placeholder="تکرار رمز عبور"
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

                <!-- Terms -->
                <div class="form-group form-group--checkbox" data-field="accept_terms">
                    <label class="custom-checkbox">
                        <input type="checkbox" id="accept_terms" name="accept_terms" value="1" required>
                        <span class="checkbox-mark">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </span>
                        <span class="checkbox-text">
                            <a href="<?php echo esc_url($rules_page); ?>" target="_blank" rel="noopener">قوانین سایت</a>
                            و
                            <a href="<?php echo esc_url($comment_rules_page); ?>" target="_blank" rel="noopener">قوانین دیدگاه‌گذاری</a>
                            را خوانده‌ام و می‌پذیرم.
                        </span>
                    </label>
                </div>

                <!-- Submit -->
                <button type="submit" class="auth-btn" id="registerBtn" disabled>
                    <span class="btn-text">ثبت‌نام</span>
                    <span class="btn-loading" style="display:none;">
                        <svg class="spinner" width="20" height="20" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="31.4" stroke-dashoffset="10">
                                <animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="0.8s" repeatCount="indefinite"/>
                            </circle>
                        </svg>
                        <span>در حال ثبت‌نام...</span>
                    </span>
                </button>
            </form>

            <!-- Footer Link -->
            <div class="auth-footer">
                <div class="auth-divider">
                    <span>یا</span>
                </div>
                <p class="auth-switch">
                    قبلاً عضو هستید؟
                    <a href="<?php echo esc_url($login_url); ?>">وارد شوید</a>
                </p>
            </div>
        </div>
    </div>
</main>

<?php get_footer(); ?>