<?php
/**
 * Email Template: New Follower
 *
 * @package suspended-starter
 */

if (!defined('ABSPATH')) exit;
include get_template_directory() . '/inc/email-templates/base-template.php';
?>
<?php ob_start(); ?>

<h2 style="color:#1e293b;font-size:20px;margin:0 0 12px;">❤ دنبال‌کننده جدید!</h2>

<p style="color:#475569;font-size:15px;line-height:1.8;margin:0 0 16px;">
    سلام <?php echo esc_html($user_name); ?>!<br>
    <?php echo esc_html($title); ?>
</p>

<?php if (!empty($link)) : ?>
<p style="text-align:center;margin:24px 0;">
    <a href="<?php echo esc_url($link); ?>"
       style="display:inline-block;padding:12px 32px;background:#ef4444;color:#fff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:700;">
        مشاهده پروفایل
    </a>
</p>
<?php endif; ?>

<?php
$email_content = ob_get_clean();
echo $email_content;