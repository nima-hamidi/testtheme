<?php
/**
 * فایل: templates/errors/rate-limited.php
 * توضیح: صفحه محدودیت نرخ درخواست
 * نسخه: 2.0.0
 */

get_header();
?>

<div class="novel-error-page">
    <div class="novel-error-content">
        <div style="font-size: 4rem; margin-bottom: var(--spacing-lg);">⏳</div>
        <h1 class="novel-error-title">کمی صبر کنید!</h1>
        <p class="novel-error-description">
            تعداد درخواست‌های شما بیش از حد مجاز است.
            <br>
            لطفاً چند دقیقه صبر کنید و دوباره تلاش کنید.
        </p>
        <div class="novel-error-actions">
            <button onclick="history.back()" class="novel-btn novel-btn-primary novel-btn-lg">بازگشت</button>
        </div>
    </div>
</div>

<?php get_footer(); ?>