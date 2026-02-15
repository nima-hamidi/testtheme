<?php
/**
 * Reset Password Email Template
 *
 * Variables: $display_name, $reset_url, $site_name, $site_url, $expiry_hours
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

ob_start();
?>
<h2>بازنشانی رمز عبور 🔐</h2>

<p>سلام <?php echo esc_html($display_name); ?>،</p>

<p>درخواست بازنشانی رمز عبور حساب شما در <strong><?php echo esc_html($site_name); ?></strong> ثبت شد.</p>

<p>برای تنظیم رمز عبور جدید، روی دکمه زیر کلیک کنید:</p>

<div style="text-align: center; margin: 25px 0;">
    <a href="<?php echo esc_url($reset_url); ?>" class="email-btn">بازنشانی رمز عبور</a>
</div>

<p>یا لینک زیر را کپی کنید:</p>
<div class="email-link"><?php echo esc_url($reset_url); ?></div>

<div class="email-note">
    ⏰ این لینک <strong><?php echo intval($expiry_hours); ?> ساعت</strong> اعتبار دارد و فقط یک‌بار قابل استفاده است.
</div>

<div class="email-divider"></div>

<p style="font-size: 13px; color: #666;">
    ⚠️ اگر شما درخواست بازنشانی رمز عبور نداده‌اید، این ایمیل را نادیده بگیرید. 
    رمز عبور فعلی شما تغییر نخواهد کرد.
</p>
<?php
$content = ob_get_clean();

include get_template_directory() . '/inc/email-templates/base-template.php';