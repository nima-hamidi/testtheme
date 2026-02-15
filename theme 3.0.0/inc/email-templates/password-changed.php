<?php
/**
 * Password Changed Confirmation Email
 *
 * Variables: $display_name, $site_name, $site_url, $change_time, $ip_address
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

ob_start();
?>
<h2>رمز عبور تغییر کرد ✅</h2>

<p>سلام <?php echo esc_html($display_name); ?>،</p>

<p>رمز عبور حساب شما در <strong><?php echo esc_html($site_name); ?></strong> با موفقیت تغییر کرد.</p>

<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
    <tr>
        <td style="padding: 10px 15px; background: #f8f9fa; border-radius: 8px 8px 0 0; font-weight: bold; border-bottom: 1px solid #eee;">📅 زمان تغییر</td>
        <td style="padding: 10px 15px; background: #f8f9fa; border-radius: 8px 8px 0 0; border-bottom: 1px solid #eee; direction: ltr; text-align: left;"><?php echo esc_html($change_time); ?></td>
    </tr>
    <tr>
        <td style="padding: 10px 15px; background: #f8f9fa; border-radius: 0 0 8px 8px; font-weight: bold;">🌐 آدرس IP</td>
        <td style="padding: 10px 15px; background: #f8f9fa; border-radius: 0 0 8px 8px; direction: ltr; text-align: left;"><?php echo esc_html($ip_address); ?></td>
    </tr>
</table>

<div class="email-note">
    ⚠️ اگر شما این تغییر را انجام نداده‌اید، لطفاً فوراً با پشتیبانی سایت تماس بگیرید.
</div>
<?php
$content = ob_get_clean();

include get_template_directory() . '/inc/email-templates/base-template.php';