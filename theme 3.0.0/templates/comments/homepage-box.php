<?php
/**
 * Homepage Comments Box
 *
 * Displays latest 10 comments on the front page.
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

$recent_comments = get_comments([
    'status'  => 'approve',
    'number'  => 10,
    'parent'  => 0,
    'orderby' => 'comment_date_gmt',
    'order'   => 'DESC',
    'type'    => 'comment',
]);

if (empty($recent_comments)) return;
?>

<section class="homepage-comments">
    <div class="homepage-comments__header">
        <h2 class="homepage-comments__title">
            <span>💬</span> آخرین دیدگاه‌ها
        </h2>
        <a href="<?php echo esc_url(get_permalink(get_option('novel_all_comments_page', ''))); ?>" class="homepage-comments__all-link">
            همه →
        </a>
    </div>

    <div class="homepage-comments__grid">
        <?php foreach ($recent_comments as $hc) :
            $hc_post = get_post($hc->comment_post_ID);
            if (!$hc_post) continue;

            $hc_user_id = (int) $hc->user_id;
            $hc_avatar = Novel_Avatars::get_avatar_url_static($hc_user_id);
            $hc_name = $hc->comment_author ?: 'ناشناس';
            $hc_time = Novel_Comments::time_ago($hc->comment_date);
            $hc_excerpt = mb_substr(wp_strip_all_tags($hc->comment_content), 0, 100);
            $hc_type = get_comment_meta($hc->comment_ID, 'novel_comment_type', true) ?: 'comment';

            // Post info
            $hc_post_title = $hc_post->post_title;
            $hc_post_url = get_permalink($hc_post->ID);
            $hc_chapter = '';
            if ($hc_post->post_type === 'chapter') {
                $novel_id = get_post_meta($hc_post->ID, '_novel_id', true);
                $novel_post = $novel_id ? get_post($novel_id) : null;
                if ($novel_post) {
                    $hc_post_title = $novel_post->post_title;
                    $hc_chapter = $hc_post->post_title;
                    $hc_post_url = get_permalink($novel_post->ID);
                }
            }

            // Vote counts
            $hc_votes = Novel_Comments::get_instance()->get_vote_counts($hc->comment_ID);
            $hc_replies = Novel_Comments::get_instance()->get_reply_count($hc->comment_ID);
        ?>
            <div class="homepage-comment-card">
                <div class="homepage-comment-card__header">
                    <img src="<?php echo esc_url($hc_avatar); ?>" alt="<?php echo esc_attr($hc_name); ?>" class="homepage-comment-card__avatar" width="32" height="32" loading="lazy">
                    <span class="homepage-comment-card__name"><?php echo esc_html($hc_name); ?></span>
                    <span class="homepage-comment-card__time"><?php echo esc_html($hc_time); ?></span>
                </div>

                <a href="<?php echo esc_url($hc_post_url); ?>" class="homepage-comment-card__novel">
                    📖 <?php echo esc_html($hc_post_title); ?>
                    <?php if ($hc_chapter) : ?>
                        <span>- <?php echo esc_html($hc_chapter); ?></span>
                    <?php endif; ?>
                </a>

                <p class="homepage-comment-card__text">
                    <?php echo esc_html($hc_excerpt); ?><?php echo mb_strlen(wp_strip_all_tags($hc->comment_content)) > 100 ? '...' : ''; ?>
                </p>

                <div class="homepage-comment-card__footer">
                    <span class="homepage-comment-card__stat">👍 <?php echo $hc_votes['likes']; ?></span>
                    <span class="homepage-comment-card__stat">💬 <?php echo $hc_replies; ?> پاسخ</span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="homepage-comments__footer">
        <a href="<?php echo esc_url(get_permalink(get_option('novel_all_comments_page', ''))); ?>" class="btn btn--outline">
            مشاهده همه دیدگاه‌ها
        </a>
    </div>
</section>