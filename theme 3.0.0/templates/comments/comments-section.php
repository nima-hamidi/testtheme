<?php
/**
 * Comments Section - Main Template Part
 *
 * Shared across: single-novel.php, single-chapter.php, single.php
 * Includes: tabs, sorting, pinned, list, form
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

$post_id   = get_the_ID();
$post_type = get_post_type();
$post_obj  = get_post($post_id);

// Tab visibility
$show_review_tab = ($post_type === 'novel');
$show_theory_tab = in_array($post_type, ['novel', 'chapter']);

// Counts
$comment_count = novel_get_comment_count($post_id, 'comment');
$review_count  = $show_review_tab ? novel_get_comment_count($post_id, 'review') : 0;
$theory_count  = $show_theory_tab ? novel_get_comment_count($post_id, 'theory') : 0;
$total_count   = $comment_count + $review_count + $theory_count;

// Current user state
$is_logged_in = is_user_logged_in();
$user_id      = get_current_user_id();
$is_verified  = $is_logged_in && class_exists('Novel_Auth') ? Novel_Auth::is_email_verified($user_id) : false;

// Sort & tab from URL
$current_sort = sanitize_text_field($_GET['sort'] ?? 'newest');
$current_tab  = sanitize_text_field($_GET['ctab'] ?? 'comment');

// Pinned comments
$pinned = get_comments([
    'post_id'    => $post_id,
    'meta_key'   => 'novel_is_pinned',
    'meta_value' => 1,
    'status'     => 'approve',
    'orderby'    => 'comment_date_gmt',
    'order'      => 'DESC',
    'number'     => Novel_Comments::MAX_PINS,
]);

// Initial comments load
$comments_instance = Novel_Comments::get_instance();
$initial_comments  = $comments_instance->query_comments($post_id, $current_tab, $current_sort, 1, 20);
$has_more          = $comments_instance->count_comments($post_id, $current_tab) > 20;

// Encouragement & warning texts
$encourage_text = get_option('novel_comment_encourage_text',
    '🖋️ قلمت را رها کن؛ تا علاوه بر قلبت، دیدگاه‌هایت نیز به جمع ما بپیوندد؛ اما با احترام و مهربانی. دیدگاه‌هایت بخشی از دنیای داستان‌ها هستند؛ لطفاً مرتبط با روایت باشند و فضای خیال دیگران را آشفته نکنند.'
);
$warning_text = get_option('novel_comment_warning_text',
    '⚠️ این‌جا دنیای داستان‌هاست... دیدگاه شما بلافاصله منتشر می‌شود! در صورت نقض قوانین، به دنیای ممنوعه رانده خواهید شد.'
);
$comment_rules_url = get_option('novel_comment_rules_page', '#');

// Spoiler safe mode from localStorage (handled in JS)
?>

<section class="novel-comments" id="comments" data-post-id="<?php echo esc_attr($post_id); ?>" data-post-type="<?php echo esc_attr($post_type); ?>">

    <!-- Header -->
    <div class="novel-comments__header">
        <h3 class="novel-comments__title">
            <span class="novel-comments__title-icon">💬</span>
            <span class="novel-comments__title-text"><?php echo number_format_i18n($total_count); ?> دیدگاه</span>
        </h3>
        <div class="novel-comments__header-actions">
            <button type="button" class="spoiler-mode-toggle" id="spoilerModeToggle" aria-pressed="false">
                <span class="spoiler-mode-toggle__icon">🛡️</span>
                <span class="spoiler-mode-toggle__text">حالت امن</span>
            </button>
        </div>
    </div>

    <!-- Tabs -->
    <div class="comment-tabs" role="tablist">
        <button
            type="button"
            class="comment-tab <?php echo $current_tab === 'comment' ? 'is-active' : ''; ?>"
            data-tab="comment"
            role="tab"
            aria-selected="<?php echo $current_tab === 'comment' ? 'true' : 'false'; ?>"
        >
            <span class="comment-tab__icon">💬</span>
            <span class="comment-tab__label">دیدگاه‌ها</span>
            <span class="comment-tab__count"><?php echo number_format_i18n($comment_count); ?></span>
        </button>

        <?php if ($show_review_tab) : ?>
            <button
                type="button"
                class="comment-tab <?php echo $current_tab === 'review' ? 'is-active' : ''; ?>"
                data-tab="review"
                role="tab"
                aria-selected="<?php echo $current_tab === 'review' ? 'true' : 'false'; ?>"
            >
                <span class="comment-tab__icon">📝</span>
                <span class="comment-tab__label">نقد و بررسی</span>
                <span class="comment-tab__count"><?php echo number_format_i18n($review_count); ?></span>
            </button>
        <?php endif; ?>

        <?php if ($show_theory_tab) : ?>
            <button
                type="button"
                class="comment-tab <?php echo $current_tab === 'theory' ? 'is-active' : ''; ?>"
                data-tab="theory"
                role="tab"
                aria-selected="<?php echo $current_tab === 'theory' ? 'true' : 'false'; ?>"
            >
                <span class="comment-tab__icon">🧠</span>
                <span class="comment-tab__label">تئوری‌ها</span>
                <span class="comment-tab__count"><?php echo number_format_i18n($theory_count); ?></span>
            </button>
        <?php endif; ?>

        <!-- Tab underline indicator -->
        <div class="comment-tabs__indicator" id="tabIndicator"></div>
    </div>

    <!-- Sorting -->
    <div class="comment-sort" id="commentSort">
        <span class="comment-sort__label">مرتب‌سازی:</span>
        <div class="comment-sort__options">
            <button type="button" class="comment-sort__btn <?php echo $current_sort === 'newest' ? 'is-active' : ''; ?>" data-sort="newest">جدیدترین</button>
            <button type="button" class="comment-sort__btn <?php echo $current_sort === 'oldest' ? 'is-active' : ''; ?>" data-sort="oldest">قدیمی‌ترین</button>
            <button type="button" class="comment-sort__btn <?php echo $current_sort === 'popular' ? 'is-active' : ''; ?>" data-sort="popular">محبوب‌ترین</button>
            <button type="button" class="comment-sort__btn <?php echo $current_sort === 'most_replied' ? 'is-active' : ''; ?>" data-sort="most_replied">بیشترین پاسخ</button>
            <?php if ($current_tab === 'review') : ?>
                <button type="button" class="comment-sort__btn <?php echo $current_sort === 'helpful' ? 'is-active' : ''; ?>" data-sort="helpful">مفیدترین</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pinned Comments -->
    <?php if (!empty($pinned)) : ?>
        <div class="pinned-comments" id="pinnedComments">
            <?php foreach ($pinned as $pc) :
                $depth = 0;
                $comment = $pc;
                include get_template_directory() . '/templates/comments/comment-single.php';
            endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Comments List -->
    <div class="comments-list" id="commentsList" data-tab="<?php echo esc_attr($current_tab); ?>" data-sort="<?php echo esc_attr($current_sort); ?>" data-page="1">
        <?php if (empty($initial_comments)) : ?>
            <div class="comments-empty">
                <div class="comments-empty__icon">🖋️</div>
                <p class="comments-empty__text">هنوز دیدگاهی نوشته نشده. اولین نفر باشید!</p>
            </div>
        <?php else : ?>
            <?php foreach ($initial_comments as $c) :
                $depth = 0;
                $comment = $c;
                include get_template_directory() . '/templates/comments/comment-single.php';
            endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Load More -->
    <?php if ($has_more) : ?>
        <div class="comments-load-more" id="commentsLoadMore">
            <button type="button" class="comments-load-more__btn" id="loadMoreBtn">
                <span class="btn-text">بارگذاری بیشتر</span>
                <span class="btn-loading" style="display:none;">
                    <svg class="spinner" width="18" height="18" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="31.4" stroke-dashoffset="10"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur=".8s" repeatCount="indefinite"/></circle></svg>
                    <span>در حال بارگذاری...</span>
                </span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Encouragement Box -->
    <?php if (!empty($encourage_text)) : ?>
        <div class="comment-box comment-box--encourage">
            <div class="comment-box__content"><?php echo wp_kses_post(nl2br($encourage_text)); ?></div>
        </div>
    <?php endif; ?>

    <!-- Warning Box -->
    <?php if (!empty($warning_text)) : ?>
        <div class="comment-box comment-box--warning">
            <div class="comment-box__content">
                <?php echo wp_kses_post(nl2br($warning_text)); ?>
                <?php if ($comment_rules_url && $comment_rules_url !== '#') : ?>
                    <a href="<?php echo esc_url($comment_rules_url); ?>" target="_blank" rel="noopener" class="comment-box__link">قوانین دیدگاه‌گذاری</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Comment Form -->
    <?php
    include get_template_directory() . '/templates/comments/comment-form.php';
    ?>

</section>

<!-- Spoiler Confirmation Modal -->
<div class="modal-overlay spoiler-modal" id="spoilerModal">
    <div class="modal">
        <div class="modal__header">
            <div class="modal__icon">⚠️</div>
            <h3 class="modal__title">توجه!</h3>
        </div>
        <div class="modal__body">
            <p>این بخش ممکن است به افشای اسرار دنیای داستان بپردازد.</p>
        </div>
        <div class="modal__actions">
            <button type="button" class="btn btn--primary spoiler-modal__show">می‌خوام ببینم 👀</button>
            <button type="button" class="btn btn--outline spoiler-modal__hide">نه، بیخیال ✋</button>
        </div>
    </div>
</div>

<!-- Report Modal -->
<div class="modal-overlay report-modal" id="reportModal">
    <div class="modal">
        <div class="modal__header">
            <h3 class="modal__title">🚩 گزارش دیدگاه</h3>
        </div>
        <div class="modal__body">
            <input type="hidden" id="reportCommentId" value="">
            <div class="report-reasons">
                <label class="report-reason">
                    <input type="radio" name="report_reason" value="spam">
                    <span class="report-reason__mark"></span>
                    <span>اسپم</span>
                </label>
                <label class="report-reason">
                    <input type="radio" name="report_reason" value="offensive">
                    <span class="report-reason__mark"></span>
                    <span>توهین و بی‌احترامی</span>
                </label>
                <label class="report-reason">
                    <input type="radio" name="report_reason" value="spoiler_unmarked">
                    <span class="report-reason__mark"></span>
                    <span>اسپویلر بدون برچسب</span>
                </label>
                <label class="report-reason">
                    <input type="radio" name="report_reason" value="inappropriate">
                    <span class="report-reason__mark"></span>
                    <span>محتوای نامناسب</span>
                </label>
                <label class="report-reason">
                    <input type="radio" name="report_reason" value="other">
                    <span class="report-reason__mark"></span>
                    <span>سایر</span>
                </label>
            </div>
            <textarea id="reportDetails" class="report-details" placeholder="توضیحات (اختیاری)" maxlength="300"></textarea>
            <div class="report-char-count"><span id="reportCharCount">0</span>/300</div>
        </div>
        <div class="modal__actions">
            <button type="button" class="btn btn--primary" id="submitReportBtn">
                <span class="btn-text">ارسال گزارش</span>
                <span class="btn-loading" style="display:none;">ارسال...</span>
            </button>
            <button type="button" class="btn btn--outline modal__cancel">انصراف</button>
        </div>
        <div class="modal__error" id="reportError"></div>
    </div>
</div>