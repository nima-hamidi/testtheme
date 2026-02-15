<?php
/**
 * فایل: templates/errors/duplicate-comment.php
 * توضیح: خطای دیدگاه تکراری
 * نسخه: 2.0.0
 */

get_header();
?>

<div class="novel-error-page">
    <div class="novel-error-content">
        <div style="font-size: 4rem; margin-bottom: var(--spacing-lg);">💬</div>
        <h1 class="novel-error-title">دیدگاه تکراری!</h1>
        <p class="novel-error-description">
            شما قبلاً این دیدگاه را ارسال کرده‌اید.
            <br>
            لطفاً متن متفاوتی بنویسید.
        </p>
        <div class="novel-error-actions">
            <button onclick="history.back()" class="novel-btn novel-btn-primary novel-btn-lg">بازگشت</button>
        </div>
    </div>
</div>

<?php get_footer(); ?>