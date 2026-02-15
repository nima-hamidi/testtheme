<?php
/**
 * Email Template: Coin Expiry Warning
 *
 * @package suspended-starter
 */

if (!defined('ABSPATH')) exit;
include get_template_directory() . '/inc/email-templates/base-template.php';
?>
<?php ob_start(); ?>

<h2 style="color:#1e293b;font-size:20px;margin:0 0 12px;">⚠️ سکه‌های شما در حال انقضاست!</h2>

<p style="color:#475569;font-size:15px;line-height:1.8;margin:0 0 16px;">
    سلام <?php echo esc_html($user_name); ?>!<br>
    <?php echo esc_html($title); ?>
</p>

<?php if (!empty($message)) : ?>
<p style="color:#6b7280;font-size:14px;line-height:1.7;margin:0 0 16px;"><?php echo esc_html($message); ?></p>
<?php endif; ?>

<p style="text-align:center;margin:24px 0;">
    <a href="<?php echo esc_url(home_url('/dashboard/?tab=my-coins')); ?>"
       style="display:inline-block;padding:12px 32px;background:#f59e0b;color:#fff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:700;">
        🪙 مشاهده سکه‌ها
    </a>
</p>

<?php
$email_content = ob_get_clean();
echo $email_content;