<?php
/**
 * Comment Thread Page
 *
 * Standalone page showing a parent comment and all its replies.
 * URL: /comment-thread/{comment_id}/
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

$thread_id = (int) get_query_var('comment_thread');
$comment   = get_comment($thread_id);

if (!$comment) {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    include get_template_directory() . '/404.php';
    exit;
}

$post = get_post($comment->comment_post_ID);
$comment_type = get_comment_meta($thread_id, 'novel_comment_type', true) ?: 'comment';

// Breadcrumb data
$novel_id = $post->post_type === 'chapter' ? get_post_meta($post->ID, '_novel_id', true) : $post->ID;
$novel = $novel_id ? get_post($novel_id) : null;

// All replies (paginated)
$page = max(1, intval($_GET['rpage'] ?? 1));
$per_page = 20;

$replies = get_comments([
    'parent'  => $thread_id,
    'status'  => 'approve',
    'orderby' => 'comment_date_gmt',
    'order'   => 'ASC',
    'number'  => $per_page,
    'offset'  => ($page - 1) * $per_page,
]);

$total_replies = (int) get_comments([
    'parent' => $thread_id,
    'status' => 'approve',
    'count'  => true,
]);

$total_pages = ceil($total_replies / $per_page);
$comments_obj = Novel_Comments::get_instance();

get_header();
?>

<main class="comment-thread-page">
    <div class="comment-thread-page__container">

        <!-- Breadcrumb -->
        <nav class="comment-thread-page__breadcrumb" aria-label="مسیر">
            <a href="<?php echo home_url('/'); ?>">خانه</a>
            <span class="breadcrumb-sep">›</span>
            <?php if ($novel) : ?>
                <a href="<?php echo get_permalink($novel->ID); ?>"><?php echo esc_html($novel->post_title); ?></a>
                <span class="breadcrumb-sep">›</span>
            <?php endif; ?>
            <?php if ($post->post_type === 'chapter') : ?>
                <a href="<?php echo get_permalink($post->ID); ?>"><?php echo esc_html($post->post_title); ?></a>
                <span class="breadcrumb-sep">›</span>
            <?php endif; ?>
            <span class="breadcrumb-current">پاسخ‌های دیدگاه</span>
        </nav>

        <!-- Header -->
        <h1 class="comment-thread-page__title">
            💬 پاسخ‌های دیدگاه <?php echo esc_html($comment->comment_author); ?>
            <span class="comment-thread-page__count">(<?php echo number_format_i18n($total_replies); ?> پاسخ)</span>
        </h1>

        <!-- Original Comment (large) -->
        <div class="comment-thread-page__original">
            <?php
            $depth = 0;
            $c = $comment;
            include get_template_directory() . '/templates/comments/comment-single.php';
            // Reset variable for replies loop
            unset($c);
            ?>
        </div>

        <!-- Replies -->
        <div class="comment-thread-page__replies">
            <h2 class="comment-thread-page__replies-title">
                پاسخ‌ها
                <span class="comment-thread-page__replies-count"><?php echo number_format_i18n($total_replies); ?></span>
            </h2>

            <?php if (empty($replies)) : ?>
                <div class="comments-empty">
                    <p>هنوز پاسخی ارسال نشده.</p>
                </div>
            <?php else : ?>
                <div class="comments-list">
                    <?php foreach ($replies as $reply) :
                        $depth = min($comments_obj->get_comment_depth($reply), 3);
                        $comment = $reply;
                        include get_template_directory() . '/templates/comments/comment-single.php';
                    endforeach;
                    unset($comment);
                    ?>
                </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($total_pages > 1) : ?>
                <div class="comment-thread-page__pagination">
                    <?php
                    echo paginate_links([
                        'base'    => add_query_arg('rpage', '%#%'),
                        'format'  => '',
                        'current' => $page,
                        'total'   => $total_pages,
                        'prev_text' => '← قبلی',
                        'next_text' => 'بعدی →',
                    ]);
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Reply Form -->
        <div class="comment-thread-page__form" data-parent-id="<?php echo $thread_id; ?>">
            <?php
            // Override post_id for the form
            global $post;
            $post = get_post($comment->comment_post_ID);
            setup_postdata($post);
            include get_template_directory() . '/templates/comments/comment-form.php';
            wp_reset_postdata();
            ?>
        </div>

        <!-- Back Link -->
        <div class="comment-thread-page__back">
            <a href="<?php echo get_permalink($comment->comment_post_ID); ?>#comment-<?php echo $thread_id; ?>">
                ← بازگشت به صفحه اصلی
            </a>
        </div>
    </div>
</main>

<?php get_footer(); ?>