<?php
/**
 * نظرات — عمق نامحدود با طراحی هوشمند
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( post_password_required() ) return;
?>

<div class="fn-comments" id="comments">

    <?php if ( have_comments() ) : ?>
        <h3 class="fn-comments__title">
            💬 <?php printf( '%s نظر', fn_persian_number( get_comments_number() ) ); ?>
        </h3>

        <ol class="fn-comments__list">
            <?php
            wp_list_comments( array(
                'style'       => 'ol',
                'short_ping'  => true,
                'avatar_size' => 40,
                'max_depth'   => 10,
                'callback'    => 'fn_render_comment',
                'end-callback' => 'fn_render_comment_end',
            ) );
            ?>
        </ol>

        <?php if ( get_comment_pages_count() > 1 ) : ?>
            <nav class="fn-comments__pagination fn-pagination">
                <?php paginate_comments_links(); ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ( comments_open() ) : ?>
        <div class="fn-comment-form-wrap">
            <?php
            comment_form( array(
                'title_reply'          => '💬 ارسال نظر',
                'title_reply_to'       => '↩️ پاسخ به %s',
                'cancel_reply_link'    => '✕ انصراف',
                'label_submit'         => 'ارسال نظر',
                'submit_button'        => '<button type="submit" name="%1$s" id="%2$s" class="fn-btn fn-btn--primary">%4$s</button>',
                'submit_field'         => '<div class="fn-comment-form__submit">%1$s %2$s</div>',
                'comment_notes_before' => '',
                'class_form'           => 'fn-comment-form',
                'comment_field'        => '<div class="fn-form-group"><label for="comment" class="fn-form-label">متن نظر</label><textarea id="comment" name="comment" class="fn-form-input fn-form-textarea" rows="4" required placeholder="نظر خود را بنویسید..."></textarea></div>',
                'fields' => array(
                    'author' => '<div class="fn-comment-form__row"><div class="fn-form-group"><label for="author" class="fn-form-label">نام *</label><input id="author" name="author" type="text" class="fn-form-input" required></div>',
                    'email'  => '<div class="fn-form-group"><label for="email" class="fn-form-label">ایمیل *</label><input id="email" name="email" type="email" class="fn-form-input" required></div></div>',
                ),
            ) );
            ?>
        </div>
    <?php endif; ?>
</div>

<?php
function fn_render_comment( $comment, $args, $depth ) {
    // محاسبه سطح عمق
    $level = min( $depth, 5 ); // حداکثر تورفتگی بصری تا سطح ۵
    $is_reply = $depth > 1;

    // نویسنده والد
    $parent_author = '';
    if ( $comment->comment_parent ) {
        $parent = get_comment( $comment->comment_parent );
        if ( $parent ) $parent_author = $parent->comment_author;
    }

    // رنگ‌بندی سطوح
    $level_colors = array(
        1 => '#6C5CE7',
        2 => '#74B9FF',
        3 => '#00B894',
        4 => '#FDCB6E',
        5 => '#E17055',
    );
    $border_color = $level_colors[ min( $level, 5 ) ] ?? '#6C5CE7';
    ?>
    <li id="comment-<?php comment_ID(); ?>" <?php comment_class( 'fn-comment fn-comment--level-' . $level ); ?>>
        <div class="fn-comment__inner" style="border-right-color: <?php echo $border_color; ?>;">

            <div class="fn-comment__avatar">
                <?php echo get_avatar( $comment, $depth > 2 ? 32 : 40, '', '', array( 'class' => 'fn-comment__avatar-img' ) ); ?>
            </div>

            <div class="fn-comment__body">
                <div class="fn-comment__header">
                    <strong class="fn-comment__author"><?php comment_author(); ?></strong>

                    <?php if ( $parent_author ) : ?>
                        <span class="fn-comment__reply-to">
                            ↩ پاسخ به <strong><?php echo esc_html( $parent_author ); ?></strong>
                        </span>
                    <?php endif; ?>

                    <time class="fn-comment__date"><?php echo fn_time_ago( get_comment_date( 'U' ) ); ?></time>
                </div>

                <?php if ( $comment->comment_approved === '0' ) : ?>
                    <p class="fn-comment__moderation">⏳ در انتظار تأیید</p>
                <?php endif; ?>

                <div class="fn-comment__content"><?php comment_text(); ?></div>

                <!-- لایک/دیسلایک -->
                <div class="fn-comment__footer">
                    <div class="fn-comment-votes" data-comment-id="<?php comment_ID(); ?>">
                        <button class="fn-vote-btn fn-vote-btn--like" data-type="like" title="پسندیدم">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>
                            <span class="fn-vote-count fn-like-count"><?php echo absint( get_comment_meta( get_comment_ID(), '_fn_likes', true ) ); ?></span>
                        </button>
                        <button class="fn-vote-btn fn-vote-btn--dislike" data-type="dislike" title="نپسندیدم">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zm7-13h2.67A2.31 2.31 0 0 1 22 4v7a2.31 2.31 0 0 1-2.33 2H17"/></svg>
                            <span class="fn-vote-count fn-dislike-count"><?php echo absint( get_comment_meta( get_comment_ID(), '_fn_dislikes', true ) ); ?></span>
                        </button>
                    </div>

                    <?php
                    comment_reply_link( array_merge( $args, array(
                        'depth'      => $depth,
                        'max_depth'  => $args['max_depth'],
                        'before'     => '<span class="fn-comment__reply-btn">',
                        'after'      => '</span>',
                        'reply_text' => '↩️ پاسخ',
                    ) ) );
                    ?>
                </div>
            </div>
        </div>
    <?php
}

function fn_render_comment_end() { echo '</li>'; }
