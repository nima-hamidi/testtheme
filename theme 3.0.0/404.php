<?php
/**
 * فایل: 404.php
 * توضیح: صفحه خطای ۴۰۴ - طراحی زیبا با انیمیشن
 * نسخه: 2.0.0
 */

get_header();
?>

<div class="novel-error-page">
    <div class="novel-error-content">
        <!-- انیمیشن SVG -->
        <div class="novel-error-illustration">
            <svg width="200" height="200" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- کتاب باز -->
                <rect x="30" y="60" width="60" height="80" rx="4" fill="var(--primary-light)" opacity="0.3"/>
                <rect x="110" y="60" width="60" height="80" rx="4" fill="var(--primary-light)" opacity="0.3"/>
                <rect x="88" y="55" width="24" height="90" rx="2" fill="var(--primary)" opacity="0.6"/>
                <!-- علامت سوال -->
                <text x="100" y="120" text-anchor="middle" font-size="48" font-weight="900" fill="var(--primary)" opacity="0.8">?</text>
                <!-- ذره‌ها -->
                <circle cx="40" cy="40" r="3" fill="var(--accent)" opacity="0.6">
                    <animate attributeName="cy" values="40;30;40" dur="3s" repeatCount="indefinite"/>
                </circle>
                <circle cx="160" cy="50" r="2" fill="var(--secondary)" opacity="0.5">
                    <animate attributeName="cy" values="50;35;50" dur="2.5s" repeatCount="indefinite"/>
                </circle>
                <circle cx="80" cy="170" r="2.5" fill="var(--warning)" opacity="0.6">
                    <animate attributeName="cy" values="170;160;170" dur="2s" repeatCount="indefinite"/>
                </circle>
            </svg>
        </div>

        <div class="novel-error-code"><?php echo esc_html(novel_fa_num('404')); ?></div>
        <h1 class="novel-error-title">صفحه مورد نظر یافت نشد!</h1>
        <p class="novel-error-description">
            شاید این صفحه حذف شده یا آدرس آن تغییر کرده باشد.
            <br>
            نگران نباشید، هزاران رمان جذاب منتظر شماست! 📚
        </p>

        <div class="novel-error-actions">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="novel-btn novel-btn-primary novel-btn-lg">
                بازگشت به خانه
            </a>
            <a href="<?php echo esc_url(get_post_type_archive_link('novel')); ?>" class="novel-btn novel-btn-secondary novel-btn-lg">
                مرور رمان‌ها
            </a>
        </div>
    </div>
</div>

<?php get_footer(); ?>