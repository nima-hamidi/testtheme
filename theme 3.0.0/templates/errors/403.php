<?php
/**
 * فایل: templates/errors/403.php
 * توضیح: صفحه خطای ۴۰۳ - دسترسی ممنوع
 * نسخه: 2.0.0
 */

get_header();
?>

<div class="novel-error-page">
    <div class="novel-error-content">
        <div class="novel-error-code"><?php echo esc_html(novel_fa_num('403')); ?></div>
        <h1 class="novel-error-title">دسترسی غیرمجاز! 🚫</h1>
        <p class="novel-error-description">
            شما اجازه دسترسی به این بخش را ندارید.
            <br>
            اگر فکر می‌کنید این خطاست، لطفاً با پشتیبانی تماس بگیرید.
        </p>
        <div class="novel-error-actions">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="novel-btn novel-btn-primary novel-btn-lg">بازگشت به خانه</a>
        </div>
    </div>
</div>

<?php get_footer(); ?>