<?php
/**
 * Dashboard: My Comments Tab
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

$user_id     = get_current_user_id();
$filter_type = sanitize_text_field($_GET['ctype'] ?? 'all');
$filter_sort = sanitize_text_field($_GET['csort'] ?? 'newest');
$page        = max(1, intval($_GET['cpage'] ?? 1));
$per_page    = 15;

// Build query
$args = [
    'user_id' => $user_id,
    'status'  => 'any',
    'number'  => $per_page,
    'offset'  => ($page - 1) * $per_page,
    'orderby' => 'comment_date_gmt',
    'order'   => $filter_sort === 'most_liked' ? 'DESC' : ($filter_sort === 'oldest' ? 'ASC' : 'DESC'),
];

if ($filter_type !== 'all' && in_array($filter_type, ['comment', 'review', 'theory', 'reply'])) {
    if ($filter_type === 'reply') {
        $args['parent__not_in'] = [0]; // Only replies
    } else {
        $args['meta_key'] = 'novel_comment_type';
        $args['meta_value'] = $filter_type;
    }
}

$comments = get_comments($args);
$total = get_comments(array_merge($args, ['count' => true, 'number' => 0, 'offset' => 0]));
$total_pages = ceil($total / $per_page);

$comments_obj = Novel_Comments::get_instance();

// Status labels
$status_labels = [
    '1'    => ['label' => 'تأیید شده', 'icon' => '✅', 'class' => 'status--approved'],
    '0'    => ['label' => 'در انتظار', 'icon' => '⏳', 'class' => 'status--pending'],
    'spam' => ['label' => 'اسپم', 'icon' => '🚫', 'class' => 'status--spam'],
    'trash'=> ['label' => 'حذف شده', 'icon' => '❌', 'class' => 'status--trash'],
];
?>

<div class="dashboard__content-header">
    <h2 class="dashboard__content-title">
        <span>💬</span> دیدگاه‌های من
        <span class="dashboard__content-count">(<?php echo number_format_i18n($total); ?>)</span>
    </h2>
</div>

<!-- Filters -->
<div class="my-comments__filters">
    <div class="filter-pills">
        <a href="?tab=comments&ctype=all" class="filter-pill <?php echo $filter_type === 'all' ? 'is-active' : ''; ?>">همه</a>
        <a href="?tab=comments&ctype=comment" class="filter-pill <?php echo $filter_type === 'comment' ? 'is-active' : ''; ?>">💬 دیدگاه</a>
        <a href="?tab=comments&ctype=review" class="filter-pill <?php echo $filter_type === 'review' ? 'is-active' : ''; ?>">📝 نقد</a>
        <a href="?tab=comments&ctype=theory" class="filter-pill <?php echo $filter_type === 'theory' ? 'is-active' : ''; ?>">🧠 تئوری</a>
        <a href="?tab=comments&ctype=reply" class="filter-pill <?php echo $filter_type === 'reply' ? 'is-active' : ''; ?>">↩ پاسخ‌ها</a>
    </div>

    <div class="filter-pills">
        <a href="?tab=comments&ctype=<?php echo $filter_type; ?>&csort=newest" class="filter-pill <?php echo $filter_sort === 'newest' ? 'is-active' : ''; ?>">جدیدترین</a>
        <a href="?tab=comments&ctype=<?php echo $filter_type; ?>&csort=most_liked" class="filter-pill <?php echo $filter_sort === 'most_liked' ? 'is-active' : ''; ?>">بیشترین لایک</a>
    </div>
</div>

<!-- Comments List -->
<div class="my-comments__list">
    <?php if (empty($comments)) : ?>
        <div class="comments-empty">
            <div class="comments-empty__icon">🖋️</div>
            <p>هنوز دیدگاهی ننوشته‌اید. بیایید شروع کنیم!</p>
        </div>
    <?php else : ?>
        <?php foreach ($comments as $mc) :
            $mc_post = get_post($mc->comment_post_ID);
            $mc_type = get_comment_meta($mc->comment_ID, 'novel_comment_type', true) ?: 'comment';
            $mc_votes = $comments_obj->get_vote_counts($mc->comment_ID);
            $mc_replies = $comments_obj->get_reply_count($mc->comment_ID);
            $mc_status = $status_labels[$mc->comment_approved] ?? $status_labels['1'];
            $mc_can_edit = Novel_Comments::can_edit($mc);

            // Post info
            $mc_post_title = $mc_post ? $mc_post->post_title : 'حذف شده';
            $mc_post_url = $mc_post ? get_permalink($mc_post->ID) : '#';
            if ($mc_post && $mc_post->post_type === 'chapter') {
                $novel_id = get_post_meta($mc_post->ID, '_novel_id', true);
                $novel_p = $novel_id ? get_post($novel_id) : null;
                if ($novel_p) {
                    $mc_post_title = $novel_p->post_title . ' - ' . $mc_post->post_title;
                }
            }

            $type_badge_map = [
                'comment' => ['label' => '💬 دیدگاه', 'class' => 'badge--comment'],
                'review'  => ['label' => '📝 نقد', 'class' => 'badge--review'],
                'theory'  => ['label' => '🧠 تئوری', 'class' => 'badge--theory'],
            ];
            $type_badge = $type_badge_map[$mc_type] ?? $type_badge_map['comment'];
        ?>
            <div class="my-comment-item">
                <div class="my-comment-item__header">
                    <span class="my-comment-item__type-badge <?php echo $type_badge['class']; ?>"><?php echo $type_badge['label']; ?></span>
                    <a href="<?php echo esc_url($mc_post_url); ?>" class="my-comment-item__post"><?php echo esc_html($mc_post_title); ?></a>
                    <span class="my-comment-item__status <?php echo $mc_status['class']; ?>"><?php echo $mc_status['icon']; ?> <?php echo $mc_status['label']; ?></span>
                </div>

                <div class="my-comment-item__content">
                    <?php echo esc_html(mb_substr(wp_strip_all_tags($mc->comment_content), 0, 80)); ?><?php echo mb_strlen(wp_strip_all_tags($mc->comment_content)) > 80 ? '...' : ''; ?>
                </div>

                <div class="my-comment-item__footer">
                    <time class="my-comment-item__date" datetime="<?php echo esc_attr($mc->comment_date); ?>">
                        <?php echo esc_html(date_i18n('j F Y - H:i', strtotime($mc->comment_date))); ?>
                    </time>
                    <span class="my-comment-item__stat">👍 <?php echo $mc_votes['likes']; ?></span>
                    <span class="my-comment-item__stat">💬 <?php echo $mc_replies; ?></span>
                    <a href="<?php echo esc_url(get_comment_link($mc->comment_ID)); ?>" class="my-comment-item__link">مشاهده</a>
                    <?php if ($mc_can_edit) : ?>
                        <a href="<?php echo esc_url(get_comment_link($mc->comment_ID)); ?>#comment-<?php echo $mc->comment_ID; ?>" class="my-comment-item__link my-comment-item__link--edit">ویرایش</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1) : ?>
    <div class="my-comments__pagination">
        <?php
        echo paginate_links([
            'base'    => add_query_arg('cpage', '%#%'),
            'format'  => '',
            'current' => $page,
            'total'   => $total_pages,
            'prev_text' => '← قبلی',
            'next_text' => 'بعدی →',
        ]);
        ?>
    </div>
<?php endif; ?>