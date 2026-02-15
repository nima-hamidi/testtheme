<?php
/**
 * Comment Reply Notification Email
 *
 * Variables: $display_name, $replier_name, $reply_content, 
 *            $comment_url, $site_name, $site_url
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

ob_start();
?>
<h2>پاسخ جدید به دیدگاه شما 💬</h2>

<p>سلام <?php echo esc_html($display_name); ?>،</p>

<p><strong><?php echo esc_html($replier_name); ?></strong> به دیدگاه شما پاسخ داد:</p>

<blockquote style="
    background: #f8f9fa;
    padding: 14px 18px;
    border-radius: 10px;
    border-right: 4px solid #6366f1;
    margin: 16px 0;
    font-size: 14px;
    color: #374151;
    line-height: 1.7;
"><?php echo wp_kses_post(wp_trim_words($reply_content, 50)); ?></blockquote>

<div style="text-align: center; margin: 25px 0;">
    <a href="<?php echo esc_url($comment_url); ?>" class="email-btn">مشاهده پاسخ</a>
</div>

<div class="email-divider"></div>

<p style="font-size: 12px; color: #999;">
    برای تغییر تنظیمات اعلان‌ها، به <a href="<?php echo esc_url($site_url); ?>/dashboard/?tab=settings">تنظیمات داشبورد</a> مراجعه کنید.
</p>
<?php
$content = ob_get_clean();

include get_template_directory() . '/inc/email-templates/base-template.php';