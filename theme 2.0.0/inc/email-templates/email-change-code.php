<?php if (!defined('ABSPATH')) exit; ?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:Tahoma,Arial,sans-serif;direction:rtl;text-align:right;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:40px 0;" dir="rtl">
<tr><td align="center">
<table width="580" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);direction:rtl;text-align:right;">

<tr><td style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:32px;text-align:center;">
<h1 style="color:#fff;margin:0;font-size:22px;">🔐 کد تأیید تغییر ایمیل</h1>
</td></tr>

<tr><td style="padding:40px 36px;direction:rtl;text-align:right;">
<p style="font-size:16px;color:#333;line-height:2;text-align:right;">
سلام <?php echo esc_html($user_name); ?> 🌿
</p>

<p style="font-size:15px;color:#555;line-height:2;text-align:right;">
درخواست تغییر ایمیل حساب شما به آدرس زیر ثبت شده:<br>
<strong style="color:#667eea;direction:ltr;display:inline-block;"><?php echo esc_html($new_email); ?></strong>
</p>

<p style="font-size:15px;color:#555;line-height:2;text-align:right;">
برای تأیید، کد زیر را در صفحه داشبورد وارد کنید:
</p>

<div style="text-align:center;margin:32px 0;">
<div style="display:inline-block;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;padding:20px 48px;border-radius:12px;font-size:32px;font-weight:bold;letter-spacing:12px;font-family:monospace;">
<?php echo esc_html($code); ?>
</div>
</div>

<p style="font-size:14px;color:#e67e22;line-height:2;text-align:right;background:#fff8f0;padding:12px 16px;border-radius:8px;border-right:4px solid #e67e22;">
⏰ این کد فقط <strong>۱۰ دقیقه</strong> اعتبار دارد.<br>
⚠️ اگر از صفحه داشبورد خارج شوید، باید دوباره کد تأیید درخواست کنید.
</p>

<hr style="border:none;border-top:1px solid #eee;margin:28px 0;">

<p style="font-size:13px;color:#999;line-height:2;text-align:right;">
اگر این درخواست از طرف شما نبوده، این ایمیل را نادیده بگیرید. ایمیل فعلی شما بدون تغییر باقی می‌ماند.
</p>

<p style="font-size:13px;color:#bbb;line-height:2;text-align:right;">
📌 محدودیت تغییر ایمیل: هر ۶ ماه یکبار
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