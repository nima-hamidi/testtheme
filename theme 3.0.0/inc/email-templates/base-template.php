<?php
/**
 * Base Email Template
 * 
 * All email templates wrap their content in this base layout.
 * Variables available: $site_name, $site_url, $content
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

$site_name = $site_name ?? get_bloginfo('name');
$site_url  = $site_url ?? home_url('/');
$logo_url  = get_option('novel_logo_url', '');
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($site_name); ?></title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f4f7;
            font-family: Tahoma, 'Segoe UI', Arial, sans-serif;
            direction: rtl;
            text-align: right;
            -webkit-text-size-adjust: 100%;
        }
        .email-wrapper {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        .email-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            padding: 30px 40px;
            text-align: center;
        }
        .email-header img {
            max-height: 50px;
            margin-bottom: 10px;
        }
        .email-header h1 {
            color: #ffffff;
            font-size: 20px;
            margin: 0;
            font-weight: 700;
        }
        .email-body {
            padding: 40px;
            color: #333333;
            line-height: 1.8;
            font-size: 14px;
        }
        .email-body h2 {
            color: #1a1a2e;
            font-size: 18px;
            margin-top: 0;
            margin-bottom: 15px;
        }
        .email-body p {
            margin: 0 0 15px;
        }
        .email-btn {
            display: inline-block;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: #ffffff !important;
            padding: 14px 32px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 15px;
            font-weight: 700;
            margin: 15px 0;
            text-align: center;
        }
        .email-btn:hover {
            opacity: 0.9;
        }
        .email-link {
            word-break: break-all;
            color: #6366f1;
            font-size: 12px;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 6px;
            display: block;
            margin: 10px 0;
            direction: ltr;
            text-align: left;
        }
        .email-footer {
            background-color: #f8f9fa;
            padding: 20px 40px;
            text-align: center;
            font-size: 12px;
            color: #999999;
            border-top: 1px solid #eee;
        }
        .email-footer a {
            color: #6366f1;
            text-decoration: none;
        }
        .email-divider {
            height: 1px;
            background: #eee;
            margin: 20px 0;
        }
        .email-note {
            background: #fef3c7;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 13px;
            color: #92400e;
            margin: 15px 0;
        }
        @media only screen and (max-width: 600px) {
            .email-body { padding: 24px 20px; }
            .email-header { padding: 20px; }
            .email-footer { padding: 16px 20px; }
        }
    </style>
</head>
<body>
    <table width="100%" cellpadding="0" cellspacing="0" style="padding: 30px 15px; background-color: #f4f4f7;">
        <tr>
            <td align="center">
                <div class="email-wrapper">
                    <!-- Header -->
                    <div class="email-header">
                        <?php if (!empty($logo_url)) : ?>
                            <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($site_name); ?>">
                        <?php endif; ?>
                        <h1><?php echo esc_html($site_name); ?></h1>
                    </div>

                    <!-- Body -->
                    <div class="email-body">
                        <?php echo $content; ?>
                    </div>

                    <!-- Footer -->
                    <div class="email-footer">
                        <p>این ایمیل از طرف <a href="<?php echo esc_url($site_url); ?>"><?php echo esc_html($site_name); ?></a> ارسال شده است.</p>
                        <p>اگر انتظار دریافت این ایمیل را نداشتید، آن را نادیده بگیرید.</p>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>