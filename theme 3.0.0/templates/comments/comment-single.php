<?php
/**
 * Single Comment Template
 *
 * Renders one comment with all interactions.
 * Variables: $comment (WP_Comment), $depth (int)
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH') || !isset($comment)) return;

$c              = $comment;
$c_id           = $c->comment_ID;
$c_user_id      = (int) $c->user_id;
$c_content      = apply_filters('comment_text', $c->comment_content, $c);
$c_date         = $c->comment_date;
$c_type         = get_comment_meta($c_id, 'novel_comment_type', true) ?: 'comment';
$c_spoiler      = (int) get_comment_meta($c_id, 'novel_is_spoiler', true);
$c_pinned       = (int) get_comment_meta($c_id, 'novel_is_pinned', true);
$c_edited       = get_comment_meta($c_id, 'novel_last_edited', true);
$c_rating       = (int) get_comment_meta($c_id, 'novel_review_rating', true);
$depth           = isset($depth) ? (int) $depth : 0;

// User info
$avatar_url     = Novel_Avatars::get_avatar_url_static($c_user_id);
$display_name   = $c->comment_author ?: 'ناشناس';
$badges         = Novel_Comments::get_user_badges($c_user_id, $c->comment_post_ID);

// Current user state
$current_user_id = get_current_user_id();
$is_owner        = ($current_user_id > 0 && $current_user_id === $c_user_id);
$can_edit_c      = Novel_Comments::can_edit($c);
$edit_remaining  = Novel_Comments::edit_remaining($c);
$can_pin         = current_user_can('manage_options') || (int) get_post_field('post_author', $c->comment_post_ID) === $current_user_id;

// Votes & reactions
$comments_obj   = Novel_Comments::get_instance();
$votes          = $comments_obj->get_vote_counts($c_id);
$user_vote      = $current_user_id ? $comments_obj->get_user_vote($c_id, $current_user_id) : 0;
$reactions      = $comments_obj->get_reaction_counts($c_id);
$user_reaction  = $current_user_id ? $comments_obj->get_user_reaction($c_id, $current_user_id) : '';

// Reply count
$reply_count    = $comments_obj->get_reply_count($c_id);

// Helpful (reviews only)
$helpful_counts = null;
$user_helpful   = 0;
if ($c_type === 'review') {
    $helpful_counts = $comments_obj->get_helpful_counts($c_id);
    if ($current_user_id) {
        global $wpdb;
        $user_helpful = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT helpful FROM {$wpdb->prefix}review_helpfulness WHERE comment_id = %d AND user_id = %d",
            $c_id, $current_user_id
        ));
    }
}

// Parent reference (for replies)
$parent_ref = null;
if ($c->comment_parent > 0) {
    $parent_c = get_comment($c->comment_parent);
    if ($parent_c) {
        $parent_ref = [
            'name'    => $parent_c->comment_author ?: 'ناشناس',
            'excerpt' => mb_substr(wp_strip_all_tags($parent_c->comment_content), 0, 30),
            'id'      => $parent_c->comment_ID,
        ];
    }
}

// Type badge info
$type_badges = [
    'review' => ['label' => '📝 نقد', 'class' => 'comment-badge--review'],
    'theory' => ['label' => '🧠 تئوری', 'class' => 'comment-badge--theory'],
];

// Depth classes
$depth_class = 'comment-depth-' . min($depth, 3);
$indent_style = $depth > 0 ? 'padding-right:' . ($depth * 24) . 'px;' : '';
$border_colors = ['', 'var(--color-primary)', 'var(--color-secondary, #8b5cf6)', 'var(--color-accent, #ec4899)'];
$border_color = $border_colors[min($depth, 3)] ?? '';
?>

<article
    class="comment-item <?php echo $depth_class; ?> <?php echo $c_pinned ? 'is-pinned' : ''; ?> <?php echo $c_spoiler ? 'is-spoiler' : ''; ?>"
    id="comment-<?php echo $c_id; ?>"
    data-comment-id="<?php echo $c_id; ?>"
    data-user-id="<?php echo $c_user_id; ?>"
    data-type="<?php echo esc_attr($c_type); ?>"
    data-depth="<?php echo $depth; ?>"
    <?php if ($depth > 0 && $border_color) : ?>
        style="<?php echo $indent_style; ?> border-right: 2px solid <?php echo $border_color; ?>;"
    <?php endif; ?>
>
    <!-- Pinned Badge -->
    <?php if ($c_pinned) : ?>
        <div class="comment-pinned-badge">📌 پین شده</div>
    <?php endif; ?>

    <!-- Header Row -->
    <div class="comment-item__header">
        <div class="comment-item__author">
            <img
                src="<?php echo esc_url($avatar_url); ?>"
                alt="<?php echo esc_attr($display_name); ?>"
                class="comment-item__avatar"
                width="40"
                height="40"
                loading="lazy"
            >
            <div class="comment-item__author-info">
                <div class="comment-item__name-row">
                    <span class="comment-item__name"><?php echo esc_html($display_name); ?></span>
                    <?php foreach ($badges as $badge) : ?>
                        <span
                            class="comment-badge"
                            style="background:<?php echo esc_attr($badge['color']); ?>20; color:<?php echo esc_attr($badge['color']); ?>; border:1px solid <?php echo esc_attr($badge['color']); ?>40;"
                            <?php if (!empty($badge['tooltip'])) : ?>
                                title="<?php echo esc_attr($badge['tooltip']); ?>"
                            <?php endif; ?>
                        >
                            <?php echo $badge['icon']; ?> <?php echo esc_html($badge['label']); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
                <div class="comment-item__meta">
                    <time class="comment-item__time" datetime="<?php echo esc_attr($c_date); ?>" title="<?php echo esc_attr(date_i18n('Y/m/d H:i', strtotime($c_date))); ?>">
                        <?php echo esc_html(Novel_Comments::time_ago($c_date)); ?>
                    </time>
                    <?php if ($c_edited) : ?>
                        <span class="comment-item__edited" title="ویرایش شده در <?php echo esc_attr(date_i18n('Y/m/d H:i', (int) $c_edited)); ?>">(ویرایش‌شده)</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Parent Reference (reply-to box) -->
    <?php if ($parent_ref) : ?>
        <div class="comment-item__ref" data-scroll-to="comment-<?php echo $parent_ref['id']; ?>">
            <span class="comment-item__ref-icon">↩</span>
            <span class="comment-item__ref-name"><?php echo esc_html($parent_ref['name']); ?>:</span>
            <span class="comment-item__ref-text"><?php echo esc_html($parent_ref['excerpt']); ?>...</span>
        </div>
    <?php endif; ?>

    <!-- Type Badge -->
    <?php if (isset($type_badges[$c_type])) : ?>
        <div class="comment-item__type-badge <?php echo $type_badges[$c_type]['class']; ?>">
            <?php echo $type_badges[$c_type]['label']; ?>
        </div>
    <?php endif; ?>

    <!-- Spoiler Badge -->
    <?php if ($c_spoiler) : ?>
        <div class="comment-item__spoiler-badge">⚠️ اسپویلر</div>
    <?php endif; ?>

    <!-- Review Rating Stars -->
    <?php if ($c_type === 'review' && $c_rating > 0) : ?>
        <div class="comment-item__rating">
            <?php for ($i = 1; $i <= 5; $i++) : ?>
                <span class="star <?php echo $i <= $c_rating ? 'star--filled' : 'star--empty'; ?>">★</span>
            <?php endfor; ?>
            <span class="comment-item__rating-text"><?php echo $c_rating; ?>/۵</span>
        </div>
    <?php endif; ?>

    <!-- Content -->
    <div class="comment-item__content <?php echo $c_spoiler ? 'is-blurred' : ''; ?>" id="commentContent-<?php echo $c_id; ?>">
        <?php if ($c_spoiler) : ?>
            <div class="comment-spoiler-overlay" data-comment-id="<?php echo $c_id; ?>">
                <span class="comment-spoiler-overlay__icon">⚠️</span>
                <span class="comment-spoiler-overlay__text">هشدار اسپویلر! کلیک کنید</span>
            </div>
        <?php endif; ?>
        <div class="comment-item__text"><?php echo $c_content; ?></div>
    </div>

    <!-- Reactions Display -->
    <?php if (!empty($reactions)) : ?>
        <div class="comment-item__reactions-display">
            <?php foreach ($reactions as $key => $r) : ?>
                <span class="reaction-chip <?php echo ($user_reaction === $key) ? 'is-active' : ''; ?>" data-reaction="<?php echo esc_attr($key); ?>">
                    <?php echo $r['emoji']; ?> <span class="reaction-chip__count"><?php echo $r['count']; ?></span>
                </span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Action Bar -->
    <div class="comment-item__actions">
        <!-- Vote -->
        <div class="comment-action-group comment-votes-group">
            <button
                type="button"
                class="comment-action vote-btn vote-btn--like <?php echo ($user_vote === 1) ? 'is-active' : ''; ?>"
                data-comment-id="<?php echo $c_id; ?>"
                data-vote="1"
                aria-label="لایک"
            >
                <span class="vote-icon">👍</span>
                <span class="vote-count" id="likeCount-<?php echo $c_id; ?>"><?php echo $votes['likes'] ?: ''; ?></span>
            </button>
            <button
                type="button"
                class="comment-action vote-btn vote-btn--dislike <?php echo ($user_vote === -1) ? 'is-active' : ''; ?>"
                data-comment-id="<?php echo $c_id; ?>"
                data-vote="-1"
                aria-label="دیسلایک"
            >
                <span class="vote-icon">👎</span>
                <span class="vote-count" id="dislikeCount-<?php echo $c_id; ?>"><?php echo $votes['dislikes'] ?: ''; ?></span>
            </button>
        </div>

        <div class="comment-action-separator"></div>

        <!-- Reaction Picker Toggle -->
        <div class="comment-action-group">
            <button type="button" class="comment-action reaction-toggle" data-comment-id="<?php echo $c_id; ?>" aria-label="ری‌اکشن">
                <span>😊</span>
            </button>
            <!-- Reaction Picker Popup -->
            <div class="reaction-picker" id="reactionPicker-<?php echo $c_id; ?>" style="display:none;">
                <?php foreach (Novel_Comments::REACTION_EMOJI as $key => $emoji) : ?>
                    <button type="button" class="reaction-picker__item <?php echo ($user_reaction === $key) ? 'is-active' : ''; ?>" data-reaction="<?php echo esc_attr($key); ?>" data-comment-id="<?php echo $c_id; ?>" title="<?php echo esc_attr($key); ?>">
                        <?php echo $emoji; ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="comment-action-separator"></div>

        <!-- Reply -->
        <button type="button" class="comment-action reply-btn" data-comment-id="<?php echo $c_id; ?>" data-author="<?php echo esc_attr($display_name); ?>">
            <span>💬</span>
            <span>پاسخ</span>
        </button>

        <!-- Edit (owner only, within window) -->
        <?php if ($can_edit_c) : ?>
            <button type="button" class="comment-action edit-btn" data-comment-id="<?php echo $c_id; ?>" data-remaining="<?php echo $edit_remaining; ?>">
                <span>✏️</span>
                <?php if ($edit_remaining > 0 && $edit_remaining < (Novel_Comments::EDIT_WINDOW * 60)) : ?>
                    <span class="edit-countdown" data-seconds="<?php echo $edit_remaining; ?>"></span>
                <?php endif; ?>
            </button>
        <?php elseif ($is_owner) : ?>
            <span class="comment-action comment-action--disabled" title="زمان ویرایش تمام شده">✏️</span>
        <?php endif; ?>

        <!-- Delete (owner) -->
        <?php if ($is_owner || current_user_can('manage_options')) : ?>
            <button type="button" class="comment-action delete-btn" data-comment-id="<?php echo $c_id; ?>" aria-label="حذف">
                <span>🗑</span>
            </button>
        <?php endif; ?>

        <!-- Pin (admin / post author) -->
        <?php if ($can_pin && $depth === 0) : ?>
            <button type="button" class="comment-action pin-btn <?php echo $c_pinned ? 'is-active' : ''; ?>" data-comment-id="<?php echo $c_id; ?>" aria-label="پین">
                <span>📌</span>
            </button>
        <?php endif; ?>

        <!-- Report -->
        <?php if ($current_user_id && !$is_owner) : ?>
            <button type="button" class="comment-action report-btn" data-comment-id="<?php echo $c_id; ?>" aria-label="گزارش">
                <span>🚩</span>
            </button>
        <?php endif; ?>
    </div>

    <!-- Helpful (Review only) -->
    <?php if ($c_type === 'review' && $helpful_counts !== null) : ?>
        <div class="comment-item__helpful">
            <span class="comment-helpful__label">این نقد مفید بود؟</span>
            <button type="button" class="comment-helpful__btn comment-helpful__btn--yes <?php echo ($user_helpful === 1) ? 'is-active' : ''; ?>" data-comment-id="<?php echo $c_id; ?>" data-helpful="1">
                👍 بله (<span class="helpful-count-yes"><?php echo $helpful_counts['yes']; ?></span>)
            </button>
            <button type="button" class="comment-helpful__btn comment-helpful__btn--no <?php echo ($user_helpful === -1) ? 'is-active' : ''; ?>" data-comment-id="<?php echo $c_id; ?>" data-helpful="-1">
                👎 خیر (<span class="helpful-count-no"><?php echo $helpful_counts['no']; ?></span>)
            </button>
        </div>
    <?php endif; ?>

    <!-- Inline Reply Form (hidden, shown on click) -->
    <div class="comment-reply-form-wrap" id="replyForm-<?php echo $c_id; ?>" style="display:none;"></div>

    <!-- Replies -->
    <?php if ($reply_count > 0 && $depth === 0) : ?>
        <div class="comment-replies-toggle">
            <button type="button" class="comment-replies-btn" data-parent-id="<?php echo $c_id; ?>" data-loaded="0">
                <span class="comment-replies-btn__text">💬 <?php echo sprintf('%d پاسخ ▼', $reply_count); ?></span>
            </button>
        </div>
        <div class="comment-replies" id="replies-<?php echo $c_id; ?>" style="display:none;">
            <!-- Loaded via AJAX -->
        </div>
        <?php if ($reply_count > 3) : ?>
            <div class="comment-replies-all" id="repliesAll-<?php echo $c_id; ?>" style="display:none;">
                <a href="<?php echo esc_url(home_url('/comment-thread/' . $c_id . '/')); ?>" class="comment-replies-all__link">
                    مشاهده همه <?php echo number_format_i18n($reply_count); ?> پاسخ →
                </a>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</article>