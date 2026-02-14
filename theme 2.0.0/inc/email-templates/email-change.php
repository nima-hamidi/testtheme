<?php if (!defined('ABSPATH')) exit; ?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:Tahoma,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:40px 0;">
<tr><td align="center">
<table width="580" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

<tr><td style="background:linear-gradient(135deg,#4facfe 0%,#00f2fe 100%);padding:32px;text-align:center;">
    <h1 style="color:#fff;margin:0;font-size:22px;">📧 تأیید تغییر ایمیل</h1>
</td></tr>

<tr><td style="padding:40px 36px;">
    <p style="font-size:16px;color:#333;line-height:2;">سلام <?php echo esc_html($user_name); ?> 🌿</p>
    <p style="font-size:15px;color:#555;line-height:2;">
        درخواست تغییر ایمیل حساب شما به <strong><?php echo esc_html($new_email); ?></strong> ثبت شده.<br>
        برای تأیید، روی دکمه زیر کلیک کنید:
    </p>

    <div style="text-align:center;margin:32px 0;">
        <a href="<?php echo esc_url($verify_url); ?>"
           style="display:inline-block;background:linear-gradient(135deg,#4facfe,#00f2fe);color:#fff;text-decoration:none;padding:14px 40px;border-radius:8px;font-size:16px;font-weight:bold;">
            ✅ تأیید ایمیل جدید
        </a>
    </div>

    <p style="font-size:13px;color:#999;line-height:2;">
        اگر این درخواست از طرف شما نبوده، این ایمیل را نادیده بگیرید.
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