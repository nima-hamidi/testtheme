<?php if (!defined('ABSPATH')) exit; ?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:Tahoma,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:40px 0;">
<tr><td align="center">
<table width="580" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

<tr><td style="background:linear-gradient(135deg,#43e97b 0%,#38f9d7 100%);padding:32px;text-align:center;">
    <h1 style="color:#fff;margin:0;font-size:22px;">🌙 <?php echo esc_html($site_name); ?></h1>
</td></tr>

<tr><td style="padding:40px 36px;">
    <p style="font-size:16px;color:#333;line-height:2;">سلام <?php echo esc_html($user_name); ?> 🌿</p>
    <p style="font-size:15px;color:#555;line-height:2;">
        خوشحالیم که ایمیلت رو تأیید کردی و حالا رسماً عضوی از جمع ما هستی.
    </p>
    <p style="font-size:15px;color:#555;line-height:2;">
        اینجا جاییه که قصه‌ها شنیده می‌شن، ناول‌ها قدم‌به‌قدم رشد می‌کنن و خیال، فرصت نوشتن پیدا می‌کنه.
    </p>
    <p style="font-size:14px;color:#666;line-height:2;">
        فرقی نداره تازه‌کاری یا سال‌هاست می‌نویسی؛ اینجا جای توئه.
    </p>

    <div style="text-align:center;margin:28px 0;">
        <a href="<?php echo esc_url($dashboard_url); ?>"
           style="display:inline-block;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;text-decoration:none;padding:12px 32px;border-radius:8px;font-size:15px;margin:0 8px;">
            ✍️ شروع نوشتن اولین داستان
        </a>
        <a href="<?php echo esc_url($archive_url); ?>"
           style="display:inline-block;background:linear-gradient(135deg,#f093fb,#f5576c);color:#fff;text-decoration:none;padding:12px 32px;border-radius:8px;font-size:15px;margin:8px;">
            📚 کشف داستان‌ها و ناول‌ها
        </a>
    </div>

    <p style="font-size:14px;color:#555;line-height:2;">
        ما کنار قلمت هستیم — و مشتاق خوندن دنیایی که قراره بسازی.
    </p>
</td></tr>

<tr><td style="background:#f8f9fb;padding:20px 36px;text-align:center;">
    <p style="font-size:13px;color:#aaa;margin:0;">
        با احترام، <strong>تیم <?php echo esc_html($site_name); ?></strong>
    </p>
</td></tr>

</table>
</td></tr>
</table>
</body>
</html>