<?php
/**
 * Welcome Email Template
 *
 * Variables: $display_name, $site_name, $site_url, $login_url, $dashboard_url
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

ob_start();
?>
<h2>خوش آمدید <?php echo esc_html($display_name); ?>! 🎉</h2>

<p>ثبت‌نام شما در <strong><?php echo esc_html($site_name); ?></strong> با موفقیت انجام شد.</p>

<p>حالا می‌توانید:</p>

<ul style="list-style: none; padding: 0;">
    <li style="padding: 8px 0;">📚 هزاران رمان و داستان جذاب بخوانید</li>
    <li style="padding: 8px 0;">💬 نظرات و تئوری‌های خود را با دیگران به اشتراک بگذارید</li>
    <li style="padding: 8px 0;">📖 لیست خواندنی و بوکمارک‌های شخصی بسازید</li>
    <li style="padding: 8px 0;">⭐ به رمان‌های مورد علاقه امتیاز دهید</li>
    <li style="padding: 8px 0;">✍️ نویسندگان مورد علاقه را دنبال کنید</li>
</ul>

<div style="text-align: center; margin: 25px 0;">
    <a href="<?php echo esc_url($dashboard_url); ?>" class="email-btn">ورود به داشبورد</a>
</div>

<div class="email-divider"></div>

<p style="font-size: 13px; color: #666;">
    💡 <strong>نکته:</strong> فراموش نکنید ایمیل خود را تأیید کنید تا بتوانید از تمام امکانات استفاده کنید.
</p>
<?php
$content = ob_get_clean();

include get_template_directory() . '/inc/email-templates/base-template.php';