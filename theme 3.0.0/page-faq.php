<?php
/**
 * Template Name: سوالات متداول
 * FAQ Page
 * 
 * @package suspended developer
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

get_header();

// سوالات از محتوای صفحه یا آپشن
$faqs = get_option('novel_faqs', []);
if (empty($faqs)) {
    // Fallback: parse content
    $faqs = [
        ['q' => 'چگونه ثبت‌نام کنم؟', 'a' => 'با کلیک روی دکمه «ثبت‌نام» در بالای صفحه و وارد کردن اطلاعات خود می‌توانید ثبت‌نام کنید.'],
        ['q' => 'آیا خواندن رمان‌ها رایگان است؟', 'a' => 'بیشتر رمان‌ها رایگان هستند. برخی قسمت‌ها ممکن است نیاز به سکه یا اشتراک ویژه داشته باشند.'],
        ['q' => 'چگونه رمان خود را منتشر کنم؟', 'a' => 'بعد از ثبت‌نام و تأیید ایمیل، از پنل نویسندگی در داشبورد خود می‌توانید رمان جدید ایجاد کنید.'],
        ['q' => 'چطور مشکل فنی گزارش دهم؟', 'a' => 'از صفحه تماس با ما استفاده کنید و موضوع «گزارش مشکل فنی» را انتخاب نمایید.'],
        ['q' => 'آیا می‌توانم آفلاین بخوانم؟', 'a' => 'در حال حاضر قابلیت آفلاین وجود ندارد، اما در نسخه‌های آینده اضافه خواهد شد.'],
    ];
}
?>

<main class="novel-main">
    <div class="novel-container">

        <div class="novel-page-header" style="text-align: center;">
            <h1 class="novel-page-title"><span class="novel-page-icon">❓</span> سوالات متداول</h1>
            <p class="novel-page-subtitle">پاسخ سوالات رایج را اینجا بیابید</p>
        </div>

        <div class="novel-faq-list" style="max-width: 750px; margin: 0 auto;">
            <?php foreach ($faqs as $index => $faq): ?>
                <div class="novel-faq-item">
                    <button class="novel-faq-question" onclick="this.parentElement.classList.toggle('is-open')">
                        <span class="novel-faq-question__text"><?php echo esc_html($faq['q']); ?></span>
                        <span class="novel-faq-question__icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </span>
                    </button>
                    <div class="novel-faq-answer">
                        <p><?php echo wp_kses_post($faq['a']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="novel-faq-cta" style="text-align: center; margin-top: 40px;">
            <p style="font-size: 16px; color: var(--color-text-secondary);">پاسخ سوالتان را پیدا نکردید؟</p>
            <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="novel-btn novel-btn--primary novel-btn--lg">
                ✉️ تماس با ما
            </a>
        </div>

    </div>
</main>

<?php get_footer(); ?>