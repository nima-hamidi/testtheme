<?php
/**
 * فایل: templates/errors/maintenance.php
 * توضیح: صفحه حالت تعمیرات (503)
 * نسخه: 2.0.0
 * وابستگی: مستقل (بدون header/footer - چون سایت down است)
 */

if (!defined('ABSPATH')) exit;

$message = get_option('novel_maintenance_message', 'در حال به‌روزرسانی هستیم! به‌زودی برمی‌گردیم.');
$eta = get_option('novel_maintenance_eta', '');
$logo = get_option('novel_site_logo', '');
$site_name = get_bloginfo('name');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>در حال تعمیرات | <?php echo esc_html($site_name); ?></title>
    <style>
        @font-face {
            font-family: 'IRANSans';
            src: url('<?php echo esc_url(NOVEL_ASSETS); ?>fonts/IRANSansWeb.woff2') format('woff2');
            font-weight: 400;
            font-display: swap;
        }
        @font-face {
            font-family: 'IRANSans';
            src: url('<?php echo esc_url(NOVEL_ASSETS); ?>fonts/IRANSansWeb_Bold.woff2') format('woff2');
            font-weight: 700;
            font-display: swap;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'IRANSans', 'Vazirmatn', Tahoma, sans-serif;
            background: linear-gradient(135deg, #F8F9FA 0%, #E9ECEF 100%);
            color: #2D3436;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px;
            text-align: center;
            direction: rtl;
        }
        .container { max-width: 480px; }
        .logo { margin-bottom: 32px; }
        .logo img { height: 40px; }
        .logo-text { font-size: 1.5rem; font-weight: 900; color: #6C5CE7; }
        .icon { font-size: 4rem; margin-bottom: 24px; animation: float 3s ease-in-out infinite; }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
        }
        /* چرخ‌دنده */
        .gears { margin: 24px auto; position: relative; width: 120px; height: 80px; }
        .gear { position: absolute; }
        .gear svg { animation: spin 8s linear infinite; }
        .gear.reverse svg { animation: spinR 6s linear infinite; }
        .gear:nth-child(1) { top: 0; left: 10px; }
        .gear:nth-child(2) { top: 20px; left: 60px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes spinR { to { transform: rotate(-360deg); } }
        h1 { font-size: 1.5rem; margin-bottom: 12px; }
        .message { font-size: 1rem; color: #636E72; line-height: 1.8; margin-bottom: 20px; }
        .eta {
            display: inline-flex; align-items: center; gap: 6px;
            background: #fff; padding: 8px 16px; border-radius: 12px;
            font-size: 0.9rem; color: #636E72; box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        @media (prefers-color-scheme: dark) {
            body { background: linear-gradient(135deg, #1A1A2E 0%, #16213E 100%); color: #E8E8E8; }
            .message { color: #A0A0B0; }
            .eta { background: #1E1E38; color: #A0A0B0; }
        }
    </style>
    <script>
        var t = localStorage.getItem('novel-theme');
        if (t === 'dark') document.documentElement.setAttribute('data-theme','dark');
    </script>
</head>
<body>
    <div class="container">
        <div class="logo">
            <?php if ($logo) : ?>
                <img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr($site_name); ?>">
            <?php else : ?>
                <span class="logo-text"><?php echo esc_html($site_name); ?></span>
            <?php endif; ?>
        </div>

        <div class="gears">
            <div class="gear">
                <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="#6C5CE7" stroke-width="1.5">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"></path>
                </svg>
            </div>
            <div class="gear reverse">
                <svg width="35" height="35" viewBox="0 0 24 24" fill="none" stroke="#A29BFE" stroke-width="1.5">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"></path>
                </svg>
            </div>
        </div>

        <div class="icon">🔧</div>
        <h1>در حال به‌روزرسانی هستیم!</h1>
        <p class="message"><?php echo esc_html($message); ?></p>

        <?php if ($eta) : ?>
        <div class="eta">
            <span>⏰</span>
            <span>زمان تخمینی: <?php echo esc_html($eta); ?></span>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>