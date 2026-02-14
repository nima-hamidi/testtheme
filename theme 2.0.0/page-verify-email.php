<?php
/**
 * Template Name: تأیید ایمیل
 */

get_header();

$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

// اگر لاگین و تأیید شده → ریدایرکت
if (is_user_logged_in() && novel_is_email_verified() && empty($status)) {
    wp_safe_redirect(novel_get_page_url('user-dashboard') ?: home_url('/'));
    exit;
}

$current_user = wp_get_current_user();
$user_email   = '';

// اگر لاگین نیست ولی ایمیل در GET هست (از ثبت‌نام اومده)
if (!is_user_logged_in() && !empty($_GET['email'])) {
    $user_email = sanitize_email(wp_unslash($_GET['email']));
} elseif (is_user_logged_in()) {
    $user_email = $current_user->user_email;
}
?>

<div class="novel-auth-page">
    <div class="novel-auth-overlay"></div>
    <div class="novel-auth-container">
        <div class="novel-auth-card novel-auth-card--verify">

            <?php if ($status === 'success') : ?>
                <!-- تأیید موفق -->
                <div class="novel-auth-result novel-auth-result--success">
                    <div class="novel-auth-result-icon">✅</div>
                    <h2><?php esc_html_e('ایمیل شما با موفقیت تأیید شد!', 'flavor'); ?></h2>
                    <p><?php esc_html_e('به دنیای داستان‌ها خوش آمدید. حالا می‌توانید از تمام امکانات استفاده کنید.', 'flavor'); ?></p>
                    <div class="novel-auth-result-actions">
                        <a href="<?php echo esc_url(novel_get_page_url('user-dashboard') ?: home_url('/')); ?>" class="novel-auth-btn novel-auth-btn--primary">
                            <?php esc_html_e('ورود به داشبورد', 'flavor'); ?>
                        </a>
                        <a href="<?php echo esc_url(get_post_type_archive_link('novel') ?: home_url('/')); ?>" class="novel-auth-btn novel-auth-btn--secondary">
                            <?php esc_html_e('مشاهده رمان‌ها', 'flavor'); ?>
                        </a>
                    </div>
                </div>

            <?php elseif ($status === 'expired') : ?>
                <!-- لینک منقضی -->
                <div class="novel-auth-result novel-auth-result--warning">
                    <div class="novel-auth-result-icon">⏰</div>
                    <h2><?php esc_html_e('لینک تأیید منقضی شده', 'flavor'); ?></h2>
                    <p><?php esc_html_e('لینک تأیید ایمیل شما منقضی شده. لطفاً درخواست ارسال مجدد بدهید.', 'flavor'); ?></p>
                    <?php if ($user_email) : ?>
                        <button class="novel-auth-btn novel-auth-btn--primary novel-resend-verify-btn"
                                data-email="<?php echo esc_attr($user_email); ?>">
                            <?php esc_html_e('ارسال مجدد لینک تأیید', 'flavor'); ?>
                        </button>
                        <div class="novel-resend-cooldown" style="display:none;"></div>
                    <?php endif; ?>
                </div>

            <?php elseif ($status === 'invalid') : ?>
                <!-- لینک نامعتبر -->
                <div class="novel-auth-result novel-auth-result--error">
                    <div class="novel-auth-result-icon">❌</div>
                    <h2><?php esc_html_e('لینک تأیید نامعتبر', 'flavor'); ?></h2>
                    <p><?php esc_html_e('لینک تأیید ایمیل نامعتبر است. ممکن است قبلاً استفاده شده باشد.', 'flavor'); ?></p>
                    <a href="<?php echo esc_url(novel_get_page_url('login') ?: home_url('/')); ?>" class="novel-auth-btn novel-auth-btn--primary">
                        <?php esc_html_e('ورود به حساب', 'flavor'); ?>
                    </a>
                </div>

            <?php else : ?>
                <!-- صفحه انتظار تأیید -->
                <div class="novel-auth-result novel-auth-result--pending">
                    <div class="novel-auth-result-icon">📧</div>
                    <h2><?php esc_html_e('ایمیل خود را تأیید کنید', 'flavor'); ?></h2>
                    <p><?php esc_html_e('یک ایمیل تأیید برای شما ارسال شده است. لطفاً صندوق ورودی (و پوشه اسپم) را بررسی کنید.', 'flavor'); ?></p>

                    <?php if ($user_email) : ?>
                        <div class="novel-verify-email-display">
                            <span class="novel-verify-email-label"><?php esc_html_e('ارسال شده به:', 'flavor'); ?></span>
                            <strong class="novel-verify-email-address"><?php echo esc_html($user_email); ?></strong>
                        </div>
                    <?php endif; ?>

                    <div class="novel-verify-restrictions">
                        <h4><?php esc_html_e('⚠️ بدون تأیید ایمیل نمی‌توانید:', 'flavor'); ?></h4>
                        <ul>
                            <li><?php esc_html_e('دیدگاه بگذارید', 'flavor'); ?></li>
                            <li><?php esc_html_e('رمان یا قسمت اضافه کنید', 'flavor'); ?></li>
                            <li><?php esc_html_e('امتیاز بدهید', 'flavor'); ?></li>
                            <li><?php esc_html_e('لایک یا دیسلایک بزنید', 'flavor'); ?></li>
                        </ul>
                    </div>

                    <?php if ($user_email) : ?>
                        <button class="novel-auth-btn novel-auth-btn--primary novel-resend-verify-btn"
                                data-email="<?php echo esc_attr($user_email); ?>"
                                id="resend-verify-btn">
                            <?php esc_html_e('ارسال مجدد لینک تأیید', 'flavor'); ?>
                        </button>
                        <div class="novel-resend-info">
                            <span class="novel-resend-cooldown" id="resend-cooldown" style="display:none;"></span>
                            <span class="novel-resend-remaining" id="resend-remaining"></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="novel-auth-messages" id="verify-messages" style="display:none;"></div>

        </div>
    </div>
</div>

<?php get_footer(); ?>