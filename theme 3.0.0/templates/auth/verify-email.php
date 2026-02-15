<?php
/**
 * Email Verification Page Template
 *
 * Handles three states:
 * 1. Success - email verified
 * 2. Expired - token expired
 * 3. Waiting - pending verification (show resend button)
 *
 * @package suspended-starter
 * @since 3.0.0
 */

get_header();

$site_name = get_bloginfo('name');
$verify_status = isset($_GET['verify_status']) ? sanitize_text_field($_GET['verify_status']) : '';
$user_email = '';

if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    $user_email = $current_user->user_email;
    $is_verified = Novel_Auth::is_email_verified($current_user->ID);
} else {
    $is_verified = false;
}

// Mask email for display
$masked_email = '';
if (!empty($user_email)) {
    $parts = explode('@', $user_email);
    $name = $parts[0];
    $domain = $parts[1] ?? '';
    $masked = substr($name, 0, 2) . str_repeat('•', max(3, strlen($name) - 2));
    $masked_email = $masked . '@' . $domain;
}
?>

<main class="auth-page auth-page--verify">
    <div class="auth-background">
        <div class="auth-bg-pattern"></div>
        <div class="auth-bg-gradient"></div>
    </div>

    <div class="auth-container">
        <div class="auth-card auth-card--verify" data-animate="fadeInUp">
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

            <?php if ($verify_status === 'success' || ($is_verified && empty($verify_status))) : ?>
                <!-- SUCCESS STATE -->
                <div class="verify-state verify-state--success">
                    <div class="verify-icon verify-icon--success">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="var(--color-success, #10b981)" stroke-width="1.5">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="9 12 11.5 14.5 16 9"/>
                        </svg>
                    </div>
                    <h1 class="auth-title">ایمیل شما تأیید شد! ✅</h1>
                    <p class="auth-subtitle">اکنون می‌توانید از تمام امکانات سایت استفاده کنید.</p>
                    <a href="<?php echo esc_url(home_url('/dashboard/')); ?>" class="auth-btn" style="margin-top: 20px;">
                        <span class="btn-text">رفتن به داشبورد</span>
                    </a>
                </div>

            <?php elseif ($verify_status === 'expired') : ?>
                <!-- EXPIRED STATE -->
                <div class="verify-state verify-state--expired">
                    <div class="verify-icon verify-icon--warning">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="var(--color-warning, #f59e0b)" stroke-width="1.5">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <h1 class="auth-title">لینک تأیید منقضی شده ⏰</h1>
                    <p class="auth-subtitle">لینک تأیید ایمیل منقضی شده. لطفاً لینک جدید درخواست دهید.</p>
                    
                    <?php if (is_user_logged_in()) : ?>
                        <button type="button" class="auth-btn resend-verify-btn" id="resendVerifyBtn" style="margin-top: 20px;">
                            <span class="btn-text">ارسال مجدد لینک تأیید</span>
                            <span class="btn-loading" style="display:none;">
                                <svg class="spinner" width="20" height="20" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="31.4" stroke-dashoffset="10">
                                        <animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="0.8s" repeatCount="indefinite"/>
                                    </circle>
                                </svg>
                                <span>در حال ارسال...</span>
                            </span>
                        </button>
                        <div class="resend-cooldown" id="resendCooldown" style="display:none;"></div>
                    <?php else : ?>
                        <a href="<?php echo esc_url(home_url('/login/')); ?>" class="auth-btn" style="margin-top: 20px;">
                            <span class="btn-text">ورود و ارسال مجدد</span>
                        </a>
                    <?php endif; ?>
                </div>

            <?php elseif ($verify_status === 'invalid') : ?>
                <!-- INVALID STATE -->
                <div class="verify-state verify-state--invalid">
                    <div class="verify-icon verify-icon--error">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="var(--color-danger, #ef4444)" stroke-width="1.5">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="15" y1="9" x2="9" y2="15"/>
                            <line x1="9" y1="9" x2="15" y2="15"/>
                        </svg>
                    </div>
                    <h1 class="auth-title">لینک نامعتبر ❌</h1>
                    <p class="auth-subtitle">لینک تأیید ایمیل نامعتبر است یا قبلاً استفاده شده.</p>
                    <a href="<?php echo esc_url(home_url('/login/')); ?>" class="auth-btn" style="margin-top: 20px;">
                        <span class="btn-text">ورود به حساب</span>
                    </a>
                </div>

            <?php else : ?>
                <!-- WAITING STATE (default) -->
                <div class="verify-state verify-state--waiting">
                    <div class="verify-icon verify-icon--email">
                        <div class="email-animation">
                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary, #6366f1)" stroke-width="1.5">
                                <rect x="2" y="4" width="20" height="16" rx="2"/>
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                            </svg>
                        </div>
                    </div>
                    
                    <h1 class="auth-title">ایمیل خود را بررسی کنید 📧</h1>
                    
                    <?php if (!empty($masked_email)) : ?>
                        <p class="auth-subtitle">
                            لینک تأیید به <strong dir="ltr"><?php echo esc_html($masked_email); ?></strong> ارسال شد.
                        </p>
                    <?php else : ?>
                        <p class="auth-subtitle">لینک تأیید به ایمیل شما ارسال شد.</p>
                    <?php endif; ?>
                    
                    <p class="verify-hint">لطفاً ایمیل خود را بررسی کنید. پوشه اسپم (Spam/Junk) را هم چک کنید.</p>

                    <?php if (is_user_logged_in()) : ?>
                        <div class="verify-actions">
                            <button type="button" class="auth-btn auth-btn--outline resend-verify-btn" id="resendVerifyBtn">
                                <span class="btn-text">ارسال مجدد لینک تأیید</span>
                                <span class="btn-loading" style="display:none;">
                                    <svg class="spinner" width="20" height="20" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="31.4" stroke-dashoffset="10">
                                            <animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="0.8s" repeatCount="indefinite"/>
                                        </circle>
                                    </svg>
                                    <span>در حال ارسال...</span>
                                </span>
                            </button>
                            <div class="resend-cooldown" id="resendCooldown" style="display:none;">
                                ارسال مجدد تا <span id="cooldownTimer">120</span> ثانیه دیگر
                            </div>
                        </div>

                        <div class="auth-messages" id="authMessages" aria-live="polite" style="margin-top: 15px;"></div>
                    <?php else : ?>
                        <a href="<?php echo esc_url(home_url('/login/')); ?>" class="auth-btn auth-btn--outline" style="margin-top: 20px;">
                            <span class="btn-text">ورود به حساب</span>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Footer -->
            <div class="auth-footer">
                <p class="auth-switch">
                    <a href="<?php echo esc_url(home_url('/')); ?>">← بازگشت به صفحه اصلی</a>
                </p>
            </div>
        </div>
    </div>
</main>

<?php get_footer(); ?>