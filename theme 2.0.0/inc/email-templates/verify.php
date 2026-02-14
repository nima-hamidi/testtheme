<?php if (!defined('ABSPATH')) exit; ?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:Tahoma,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:40px 0;">
<tr><td align="center">
<table width="580" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

<!-- Header -->
<tr><td style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:32px;text-align:center;">
    <h1 style="color:#fff;margin:0;font-size:22px;">🌿 <?php echo esc_html($site_name); ?></h1>
</td></tr>

<!-- Body -->
<tr><td style="padding:40px 36px;">
    <p style="font-size:16px;color:#333;line-height:2;">سلام 🌿</p>
    <p style="font-size:15px;color:#555;line-height:2;">
        خوشحالیم که به جمع ما اومدی.<br>
        برای اینکه مطمئن بشیم این ایمیل واقعاً متعلق به خودته، فقط کافیه روی دکمه‌ی زیر کلیک کنی:
    </p>

    <div style="text-align:center;margin:32px 0;">
        <a href="<?php echo esc_url($verify_url); ?>"
           style="display:inline-block;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;text-decoration:none;padding:14px 40px;border-radius:8px;font-size:16px;font-weight:bold;">
            ✅ تأیید ایمیل و شروع
        </a>
    </div>

    <p style="font-size:13px;color:#888;line-height:1.8;word-break:break-all;">
        اگر دکمه کار نمی‌کند، لینک زیر را کپی کنید:<br>
        <a href="<?php echo esc_url($verify_url); ?>" style="color:#667eea;"><?php echo esc_url($verify_url); ?></a>
    </p>

    <hr style="border:none;border-top:1px solid #eee;margin:28px 0;">

    <p style="font-size:14px;color:#555;line-height:2;">
        بعد از تأیید، درِ دنیای داستان‌ها به روت باز می‌شه؛<br>
        جایی برای خواندن، نوشتن و جان گرفتن خیال‌ها.
    </p>
    <p style="font-size:13px;color:#999;line-height:1.8;">
        اگر این ثبت‌نام کار تو نبوده، می‌تونی این ایمیل رو نادیده بگیری — هیچ اتفاقی نمی‌افته.
    </p>
</td></tr>

<!-- Footer -->
<tr><td style="background:#f8f9fb;padding:20px 36px;text-align:center;">
    <p style="font-size:13px;color:#aaa;margin:0;">
        با آرزوی داستان‌های ماندگار، <strong>تیم <?php echo esc_html($site_name); ?></strong>
    </p>
</td></tr>

</table>
</td></tr>
</table>
</body>
</html>