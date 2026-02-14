<?php if (!defined('ABSPATH')) exit; ?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:Tahoma,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:40px 0;">
<tr><td align="center">
<table width="580" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

<tr><td style="background:linear-gradient(135deg,#a18cd1 0%,#fbc2eb 100%);padding:32px;text-align:center;">
    <h1 style="color:#fff;margin:0;font-size:22px;">🔒 <?php echo esc_html($site_name); ?></h1>
</td></tr>

<tr><td style="padding:40px 36px;">
    <p style="font-size:16px;color:#333;line-height:2;">سلام 🌿</p>
    <p style="font-size:15px;color:#555;line-height:2;">
        این ایمیل برای اطلاع‌رسانی به شماست که رمز عبور حساب کاربری‌تان با موفقیت تغییر داده شد.
    </p>
    <p style="font-size:15px;color:#555;line-height:2;">
        اگر این تغییر توسط خودتان انجام شده، نیاز به هیچ اقدامی نیست.
    </p>
    <p style="font-size:15px;color:#555;line-height:2;">
        اما اگر شما این تغییر رو انجام ندادید، هرچه سریع‌تر:
    </p>

    <div style="text-align:center;margin:28px 0;">
        <a href="<?php echo esc_url($forgot_url); ?>"
           style="display:inline-block;background:linear-gradient(135deg,#f5576c,#ff6a88);color:#fff;text-decoration:none;padding:12px 32px;border-radius:8px;font-size:15px;">
            🔑 تغییر فوری رمز عبور
        </a>
    </div>

    <p style="font-size:14px;color:#666;line-height:2;">
        یا با پشتیبانی تماس بگیرید: <a href="mailto:<?php echo esc_attr($site_email); ?>" style="color:#667eea;"><?php echo esc_html($site_email); ?></a>
    </p>

    <p style="font-size:14px;color:#555;line-height:2;">🔒 امنیت حساب شما برای ما اهمیت زیادی داره.</p>
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