<?php
/**
 * فایل: templates/errors/general-error.php
 * توضیح: صفحه خطای عمومی
 * نسخه: 2.0.0
 */

get_header();

$error_title = $error_title ?? 'خطایی رخ داد';
$error_message = $error_message ?? 'مشکلی پیش آمده است. لطفاً دوباره تلاش کنید.';
?>

<div class="novel-error-page">
    <div class="novel-error-content">
        <div style="font-size: 4rem; margin-bottom: var(--spacing-lg);">⚠️</div>
        <h1 class="novel-error-title"><?php echo esc_html($error_title); ?></h1>
        <p class="novel-error-description"><?php echo esc_html($error_message); ?></p>
        <div class="novel-error-actions">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="novel-btn novel-btn-primary">بازگشت به خانه</a>
            <button onclick="history.back()" class="novel-btn novel-btn-secondary">بازگشت</button>
        </div>
    </div>
</div>

<?php get_footer(); ?>