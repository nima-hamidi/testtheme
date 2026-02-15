<?php
/**
 * Email Template: New Chapter
 *
 * Available vars: $title, $message, $link, $user_name, $site_name, $site_url
 *
 * @package suspended-starter
 */

if (!defined('ABSPATH')) exit;

$unsubscribe_url = add_query_arg([
    'action' => 'novel_unsubscribe',
    'type'   => 'new_chapter',
    'user'   => $user_id ?? 0,
    'token'  => wp_hash('unsubscribe_' . ($user_id ?? 0)),
], home_url('/'));

include get_template_directory() . '/inc/email-templates/base-template.php';
?>
<?php ob_start(); ?>

<h2 style="color:#1e293b;font-size:20px;margin:0 0 12px;">📖 قسمت جدید منتشر شد!</h2>

<p style="color:#475569;font-size:15px;line-height:1.8;margin:0 0 16px;">
    سلام <?php echo esc_html($user_name); ?>!<br>
    <?php echo esc_html($title); ?>
</p>

<?php if (!empty($message)) : ?>
<p style="color:#6b7280;font-size:14px;line-height:1.7;margin:0 0 16px;"><?php echo esc_html($message); ?></p>
<?php endif; ?>

<?php if (!empty($link)) : ?>
<p style="text-align:center;margin:24px 0;">
    <a href="<?php echo esc_url($link); ?>"
       style="display:inline-block;padding:12px 32px;background:#6366f1;color:#fff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:700;">
        مطالعه قسمت جدید
    </a>
</p>
<?php endif; ?>

<hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;" />

<p style="color:#9ca3af;font-size:12px;text-align:center;">
    اگر نمی‌خواهید اعلان‌های این رمان را دریافت کنید:
    <a href="<?php echo esc_url($unsubscribe_url); ?>" style="color:#6366f1;">لغو اشتراک</a>
</p>

<?php
$email_content = ob_get_clean();
echo $email_content;