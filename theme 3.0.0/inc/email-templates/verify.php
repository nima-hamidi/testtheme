<?php
/**
 * Email Verification Template
 *
 * Variables: $display_name, $verify_url, $site_name, $site_url, $expiry_hours
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

ob_start();
?>
<h2>سلام <?php echo esc_html($display_name); ?>! 👋</h2>

<p>از عضویت شما در <strong><?php echo esc_html($site_name); ?></strong> خوشحالیم.</p>

<p>برای فعال‌سازی حساب کاربری و دسترسی به تمام امکانات سایت، لطفاً ایمیل خود را تأیید کنید:</p>

<div style="text-align: center; margin: 25px 0;">
    <a href="<?php echo esc_url($verify_url); ?>" class="email-btn">تأیید ایمیل ✓</a>
</div>

<p>یا لینک زیر را در مرورگر خود کپی کنید:</p>
<div class="email-link"><?php echo esc_url($verify_url); ?></div>

<div class="email-note">
    ⏰ این لینک <strong><?php echo intval($expiry_hours); ?> ساعت</strong> اعتبار دارد.
</div>

<div class="email-divider"></div>

<p style="font-size: 13px; color: #666;">اگر شما در <?php echo esc_html($site_name); ?> ثبت‌نام نکرده‌اید، این ایمیل را نادیده بگیرید.</p>
<?php
$content = ob_get_clean();

include get_template_directory() . '/inc/email-templates/base-template.php';