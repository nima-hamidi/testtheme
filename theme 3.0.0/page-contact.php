<?php
/**
 * Template Name: تماس با ما
 * Contact Page
 * 
 * @package suspended developer
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

get_header();
?>

<main class="novel-main">
    <div class="novel-container">

        <div class="novel-page-header" style="text-align: center;">
            <h1 class="novel-page-title"><span class="novel-page-icon">✉️</span> تماس با ما</h1>
            <p class="novel-page-subtitle">سوال یا پیشنهادی دارید؟ ما گوش می‌دهیم!</p>
        </div>

        <div class="novel-contact-layout">
            <div class="novel-contact-form-wrap">
                <form class="novel-contact-form" id="novelContactForm">
                    <input type="hidden" name="action" value="novel_contact_form">
                    <?php wp_nonce_field('novel_contact_nonce', 'contact_nonce'); ?>

                    <!-- Honeypot -->
                    <div style="position: absolute; left: -9999px;">
                        <input type="text" name="novel_hp_field" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="novel-form-group">
                        <label for="contact-name">نام شما *</label>
                        <input type="text" id="contact-name" name="contact_name" required
                               placeholder="نام و نام خانوادگی"
                               value="<?php echo is_user_logged_in() ? esc_attr(wp_get_current_user()->display_name) : ''; ?>">
                    </div>

                    <div class="novel-form-group">
                        <label for="contact-email">ایمیل *</label>
                        <input type="email" id="contact-email" name="contact_email" required
                               placeholder="example@email.com"
                               value="<?php echo is_user_logged_in() ? esc_attr(wp_get_current_user()->user_email) : ''; ?>">
                    </div>

                    <div class="novel-form-group">
                        <label for="contact-subject">موضوع *</label>
                        <select id="contact-subject" name="contact_subject" required>
                            <option value="">— انتخاب کنید —</option>
                            <option value="general">سوال عمومی</option>
                            <option value="bug">گزارش مشکل فنی</option>
                            <option value="suggestion">پیشنهاد</option>
                            <option value="copyright">حق نشر / DMCA</option>
                            <option value="partnership">همکاری</option>
                            <option value="other">سایر</option>
                        </select>
                    </div>

                    <div class="novel-form-group">
                        <label for="contact-message">پیام *</label>
                        <textarea id="contact-message" name="contact_message" required
                                  rows="6" placeholder="پیام خود را بنویسید..." minlength="20"></textarea>
                    </div>

                    <button type="submit" class="novel-btn novel-btn--primary novel-btn--lg" id="contactSubmitBtn">
                        📤 ارسال پیام
                    </button>
                </form>
            </div>

            <div class="novel-contact-info">
                <div class="novel-contact-info__card">
                    <span class="novel-contact-info__icon">📧</span>
                    <h3>ایمیل</h3>
                    <p><?php echo esc_html(get_option('admin_email')); ?></p>
                </div>
                <div class="novel-contact-info__card">
                    <span class="novel-contact-info__icon">⏰</span>
                    <h3>زمان پاسخ‌دهی</h3>
                    <p>معمولاً ۲۴ تا ۴۸ ساعت</p>
                </div>
                <div class="novel-contact-info__card">
                    <span class="novel-contact-info__icon">📋</span>
                    <h3>سوالات متداول</h3>
                    <p>شاید پاسخ سوالتان <a href="<?php echo esc_url(home_url('/faq/')); ?>">اینجا</a> باشد</p>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
jQuery(document).ready(function($) {
    $('#novelContactForm').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#contactSubmitBtn');
        $btn.prop('disabled', true).text('در حال ارسال...');

        $.ajax({
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            method: 'POST',
            data: $(this).serialize(),
            success: function(r) {
                if (r.success) {
                    if (typeof NovelApp !== 'undefined') NovelApp.showToast(r.data.message, 'success');
                    else alert(r.data.message);
                    $('#novelContactForm')[0].reset();
                } else {
                    if (typeof NovelApp !== 'undefined') NovelApp.showToast(r.data.message || 'خطا', 'error');
                    else alert(r.data.message || 'خطا');
                }
                $btn.prop('disabled', false).text('📤 ارسال پیام');
            },
            error: function() {
                if (typeof NovelApp !== 'undefined') NovelApp.showToast('خطا در ارتباط', 'error');
                $btn.prop('disabled', false).text('📤 ارسال پیام');
            }
        });
    });
});
</script>

<?php get_footer(); ?>