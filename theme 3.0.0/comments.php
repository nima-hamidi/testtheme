<?php
/**
 * فایل: comments.php
 * توضیح: قالب پایه کامنت‌های وردپرس (فال‌بک - سیستم دیدگاه پیشرفته در فازهای بعد)
 * نسخه: 2.0.0
 */

if (!defined('ABSPATH')) exit;
if (post_password_required()) return;
?>

<div class="novel-comments-section novel-card" id="comments" style="padding: var(--spacing-lg); margin-top: var(--spacing-lg);">
    <h3 style="margin-bottom: var(--spacing-md);">
        💬 دیدگاه‌ها
        <?php if (get_comments_number()) : ?>
            <span class="text-muted text-sm">(<?php echo esc_html(novel_fa_num(get_comments_number())); ?>)</span>
        <?php endif; ?>
    </h3>

    <?php if (have_comments()) : ?>
        <ol class="novel-comment-list" style="margin-bottom: var(--spacing-lg);">
            <?php
            wp_list_comments([
                'style'      => 'ol',
                'short_ping' => true,
                'callback'   => function($comment, $args, $depth) {
                    ?>
                    <li id="comment-<?php comment_ID(); ?>" <?php comment_class('', $comment); ?> 
                        style="padding: var(--spacing-md); border-bottom: 1px solid var(--border-color); list-style:none;">
                        <div class="flex gap-sm">
                            <img src="<?php echo esc_url(novel_get_avatar_url($comment->user_id ?: 0)); ?>" 
                                 alt="" width="36" height="36" loading="lazy"
                                 style="border-radius: var(--border-radius-full); flex-shrink:0;">
                            <div style="flex:1; min-width:0;">
                                <div class="flex gap-xs" style="align-items:center; margin-bottom: 4px;">
                                    <strong class="text-sm"><?php echo esc_html(get_comment_author()); ?></strong>
                                    <span class="text-xs text-muted"><?php echo esc_html(novel_time_ago(get_comment_time('U'))); ?></span>
                                </div>
                                <div class="text-sm" style="line-height: 1.6;">
                                    <?php comment_text(); ?>
                                </div>
                            </div>
                        </div>
                    <?php
                }
            ]);
            ?>
        </ol>
    <?php endif; ?>

    <?php if (comments_open()) : ?>
        <?php comment_form([
            'title_reply'          => 'ارسال دیدگاه',
            'title_reply_before'   => '<h4 style="margin-bottom: var(--spacing-md);">',
            'title_reply_after'    => '</h4>',
            'comment_notes_before' => '',
            'submit_button'        => '<button type="submit" class="novel-btn novel-btn-primary">ارسال</button>',
            'comment_field'        => '<div class="novel-input-group"><textarea class="novel-input" name="comment" rows="4" placeholder="نظر خود را بنویسید..." required></textarea></div>',
        ]); ?>
    <?php endif; ?>
</div>