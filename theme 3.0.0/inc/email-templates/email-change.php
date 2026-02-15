<?php
/**
 * Email Change Verification Code Template
 *
 * Variables: $display_name, $code, $site_name, $site_url
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

ob_start();
?>
<h2>تغییر ایمیل 📧</h2>

<p>سلام <?php echo esc_html($display_name); ?>،</p>

<p>درخواست تغییر ایمیل حساب شما در <strong><?php echo esc_html($site_name); ?></strong> ثبت شد.</p>

<p>کد تأیید شما:</p>

<div style="text-align: center; margin: 25px 0;">
    <div style="
        display: inline-block;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #ffffff;
        font-size: 32px;
        font-weight: 800;
        letter-spacing: 8px;
        padding: 16px 32px;
        border-radius: 12px;
        font-family: monospace;
        direction: ltr;
    "><?php echo esc_html($code); ?></div>
</div>

<div class="email-note">
    ⏰ این کد <strong>۱۵ دقیقه</strong> اعتبار دارد.
</div>

<div class="email-divider"></div>

<p style="font-size: 13px; color: #666;">
    ⚠️ اگر شما درخواست تغییر ایمیل نداده‌اید، فوراً رمز عبور خود را تغییر دهید.
</p>
<?php
$content = ob_get_clean();

include get_template_directory() . '/inc/email-templates/base-template.php';