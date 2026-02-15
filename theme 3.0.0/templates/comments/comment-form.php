<?php
/**
 * Comment Form Template
 *
 * Adapts based on active tab (comment/review/theory)
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

$post_id     = get_the_ID();
$post_type   = get_post_type();
$is_logged   = is_user_logged_in();
$current_tab = sanitize_text_field($_GET['ctab'] ?? 'comment');

// Check if user already has a review for this post
$has_review = false;
if ($is_logged && $post_type === 'novel') {
    $existing_review = get_comments([
        'post_id'    => $post_id,
        'user_id'    => get_current_user_id(),
        'meta_key'   => 'novel_comment_type',
        'meta_value' => 'review',
        'number'     => 1,
        'status'     => 'any',
    ]);
    $has_review = !empty($existing_review);
}
?>

<div class="comment-form-section" id="commentFormSection">
    <?php if (!$is_logged) : ?>
        <!-- Not logged in -->
        <div class="comment-form-login">
            <div class="comment-form-login__icon">🔒</div>
            <p class="comment-form-login__text">برای ارسال دیدگاه، لطفاً وارد شوید.</p>
            <a href="<?php echo esc_url(home_url('/login/?redirect_to=' . urlencode(get_permalink()))); ?>" class="btn btn--primary">ورود / ثبت‌نام</a>
        </div>
    <?php else : ?>
        <!-- Logged in: Show Form -->
        <form class="comment-form" id="commentForm" novalidate>
            <input type="hidden" name="action" value="novel_submit_comment">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('novel_comments_nonce'); ?>">
            <input type="hidden" name="post_id" value="<?php echo esc_attr($post_id); ?>">
            <input type="hidden" name="parent_id" value="0" id="commentParentId">
            <input type="hidden" name="comment_type" value="<?php echo esc_attr($current_tab); ?>" id="commentTypeInput">
            <input type="hidden" name="form_timestamp" value="<?php echo time(); ?>">
            <!-- Honeypot -->
            <div style="display:none;" aria-hidden="true">
                <input type="text" name="website_url_hp" tabindex="-1" autocomplete="off">
            </div>

            <!-- User Avatar Row -->
            <div class="comment-form__header">
                <img
                    src="<?php echo esc_url(Novel_Avatars::get_avatar_url_static(get_current_user_id())); ?>"
                    alt="آواتار شما"
                    class="comment-form__avatar"
                    width="40"
                    height="40"
                >
                <span class="comment-form__username"><?php echo esc_html(wp_get_current_user()->display_name); ?></span>
            </div>

            <!-- Rating Stars (Review only) -->
            <div class="comment-form__rating" id="commentFormRating" style="display:<?php echo $current_tab === 'review' ? 'flex' : 'none'; ?>;">
                <span class="comment-form__rating-label">امتیاز شما:</span>
                <div class="star-rating-input" id="starRatingInput">
                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                        <button type="button" class="star-rating-input__star" data-value="<?php echo $i; ?>" aria-label="<?php echo $i; ?> ستاره">★</button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="ratingInput" value="0">
                <span class="comment-form__rating-text" id="ratingText"></span>
            </div>

            <!-- Textarea -->
            <div class="comment-form__body">
                <textarea
                    id="commentTextarea"
                    name="content"
                    class="comment-form__textarea"
                    placeholder="دیدگاه خود را بنویسید..."
                    rows="4"
                    required
                ></textarea>

                <!-- Character/Word Counter -->
                <div class="comment-form__counter" id="commentCounter">
                    <span id="counterText">0 / 500 کاراکتر</span>
                </div>

                <!-- Sticker/GIF Button + Mention hint -->
                <div class="comment-form__toolbar">
                    <button type="button" class="comment-form__tool sticker-toggle" id="stickerToggle" title="استیکر و GIF">
                        😊
                    </button>
                    <span class="comment-form__mention-hint">از @ برای منشن کردن استفاده کنید</span>
                </div>

                <!-- Mention Autocomplete Dropdown -->
                <div class="mention-dropdown" id="mentionDropdown" style="display:none;"></div>

                <!-- Sticker Picker -->
                <div class="sticker-picker-wrap" id="stickerPickerWrap" style="display:none;">
                    <?php include get_template_directory() . '/templates/stickers/sticker-picker.php'; ?>
                </div>
            </div>

            <!-- Spoiler Checkbox -->
            <div class="comment-form__options">
                <label class="comment-form__spoiler-check custom-checkbox">
                    <input type="checkbox" name="is_spoiler" id="commentSpoiler" value="1">
                    <span class="checkbox-mark">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    </span>
                    <span class="checkbox-text">⚠️ این دیدگاه حاوی اسپویلر است</span>
                </label>
            </div>

            <!-- Existing Review Notice -->
            <?php if ($has_review && $current_tab === 'review') : ?>
                <div class="comment-form__notice comment-form__notice--info">
                    📝 شما قبلاً برای این رمان نقد نوشته‌اید. می‌توانید نقد قبلی را ویرایش کنید.
                </div>
            <?php endif; ?>

            <!-- Submit -->
            <div class="comment-form__footer">
                <button type="submit" class="btn btn--primary comment-form__submit" id="commentSubmitBtn">
                    <span class="btn-text">ارسال دیدگاه</span>
                    <span class="btn-loading" style="display:none;">
                        <svg class="spinner" width="18" height="18" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="31.4" stroke-dashoffset="10"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur=".8s" repeatCount="indefinite"/></circle></svg>
                        <span>در حال ارسال...</span>
                    </span>
                </button>
            </div>

            <!-- Form Messages -->
            <div class="comment-form__messages" id="commentFormMessages" aria-live="polite"></div>
        </form>
    <?php endif; ?>
</div>