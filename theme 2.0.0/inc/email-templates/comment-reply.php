<?php if (!defined('ABSPATH')) exit; ?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:Tahoma,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:40px 0;">
<tr><td align="center">
<table width="580" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

<tr><td style="background:linear-gradient(135deg,#a18cd1 0%,#fbc2eb 100%);padding:32px;text-align:center;">
    <h1 style="color:#fff;margin:0;font-size:22px;">💬 پاسخ به دیدگاه شما</h1>
</td></tr>

<tr><td style="padding:40px 36px;">
    <p style="font-size:16px;color:#333;line-height:2;">
        سلام <?php echo esc_html($parent_author_name); ?>
    </p>
    <p style="font-size:15px;color:#555;line-height:2;">
        دیدگاه شما، همچون یک گنجینه پنهان در دل یکی از صفحات داستان‌ها، به دست نویسنده رسید و به آن پاسخ داده شد.
    </p>

    <?php if (!empty($content_title)) : ?>
    <div style="background:#f0f0ff;border-radius:8px;padding:12px 16px;margin:16px 0;">
        <small style="color:#888;">در:</small>
        <strong style="color:#333;"><?php echo esc_html($content_title); ?></strong>
    </div>
    <?php endif; ?>

    <div style="background:#f8f8f8;border-right:4px solid #667eea;border-radius:8px;padding:16px;margin:16px 0;">
        <small style="color:#888;"><?php echo esc_html($replier_name); ?> نوشت:</small><br>
        <p style="color:#444;margin:8px 0 0;line-height:1.8;"><?php echo wp_kses_post($reply_excerpt); ?></p>
    </div>

    <div style="text-align:center;margin:28px 0;">
        <a href="<?php echo esc_url($comment_url); ?>"
           style="display:inline-block;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;text-decoration:none;padding:14px 40px;border-radius:8px;font-size:16px;font-weight:bold;">
            💬 مشاهده پاسخ
        </a>
    </div>

    <p style="font-size:14px;color:#555;line-height:2;">
        گفت‌وگو و بازخورد شما، همانند نیرویی جادویی، روح تازه‌ای به داستان‌ها می‌بخشد.
    </p>
</td></tr>

<tr><td style="background:#f8f9fb;padding:20px 36px;text-align:center;">
    <p style="font-size:13px;color:#aaa;margin:0;">
        با آرزوی داستان‌های بی‌پایان، <strong>تیم <?php echo esc_html($site_name); ?></strong>
    </p>
    <p style="font-size:12px;color:#ccc;margin:4px 0 0;">
        <a href="<?php echo esc_url($unsubscribe_url); ?>" style="color:#ccc;">لغو اطلاع‌رسانی</a>
    </p>
</td></tr>

</table>
</td></tr>
</table>
</body>
</html>