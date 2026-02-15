<?php
/**
 * All Comments Page
 *
 * Shortcode: [novel_all_comments]
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

$filter_type  = sanitize_text_field($_GET['type'] ?? 'all');
$filter_sort  = sanitize_text_field($_GET['sort'] ?? 'newest');
$filter_novel = intval($_GET['novel'] ?? 0);
$page         = max(1, intval($_GET['cpage'] ?? 1));
$per_page     = 20;

// Build query
global $wpdb;
$where_parts = ["c.comment_approved = '1'", "c.comment_parent = 0"];

if ($filter_type !== 'all' && in_array($filter_type, ['comment', 'review', 'theory'])) {
    $where_parts[] = $wpdb->prepare(
        "EXISTS (SELECT 1 FROM {$wpdb->commentmeta} cm WHERE cm.comment_id = c.comment_ID AND cm.meta_key = 'novel_comment_type' AND cm.meta_value = %s)",
        $filter_type
    );
}

if ($filter_novel > 0) {
    // Include chapters of this novel too
    $chapter_ids = get_posts([
        'post_type'   => 'chapter',
        'meta_key'    => '_novel_id',
        'meta_value'  => $filter_novel,
        'numberposts' => -1,
        'fields'      => 'ids',
    ]);
    $post_ids = array_merge([$filter_novel], $chapter_ids);
    $placeholders = implode(',', array_fill(0, count($post_ids), '%d'));
    $where_parts[] = $wpdb->prepare("c.comment_post_ID IN ($placeholders)", ...$post_ids);
}

$where = implode(' AND ', $where_parts);

switch ($filter_sort) {
    case 'popular':
        $order = "(SELECT COALESCE(SUM(v.vote),0) FROM {$wpdb->prefix}comment_votes v WHERE v.comment_id = c.comment_ID) DESC";
        break;
    case 'oldest':
        $order = "c.comment_date_gmt ASC";
        break;
    default:
        $order = "c.comment_date_gmt DESC";
}

$total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->comments} c WHERE {$where}");
$total_pages = ceil($total / $per_page);
$offset = ($page - 1) * $per_page;

$results = $wpdb->get_results(
    $wpdb->prepare("SELECT c.* FROM {$wpdb->comments} c WHERE {$where} ORDER BY {$order} LIMIT %d OFFSET %d", $per_page, $offset)
);

// Get all novels for dropdown
$all_novels = get_posts([
    'post_type'   => 'novel',
    'numberposts' => -1,
    'post_status' => 'publish',
    'orderby'     => 'title',
    'order'       => 'ASC',
]);

$comments_obj = Novel_Comments::get_instance();
?>

<div class="all-comments-page">
    <div class="all-comments-page__header">
        <h1 class="all-comments-page__title">💬 همه دیدگاه‌ها</h1>
        <p class="all-comments-page__subtitle"><?php echo number_format_i18n($total); ?> دیدگاه</p>
    </div>

    <!-- Filters -->
    <div class="all-comments-page__filters">
        <div class="filter-group">
            <label class="filter-label">رمان:</label>
            <select id="filterNovel" class="filter-select" onchange="location.href=this.value">
                <option value="<?php echo esc_url(remove_query_arg('novel')); ?>">همه رمان‌ها</option>
                <?php foreach ($all_novels as $n) : ?>
                    <option
                        value="<?php echo esc_url(add_query_arg('novel', $n->ID)); ?>"
                        <?php selected($filter_novel, $n->ID); ?>
                    ><?php echo esc_html($n->post_title); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label class="filter-label">نوع:</label>
            <div class="filter-pills">
                <a href="<?php echo esc_url(add_query_arg('type', 'all')); ?>" class="filter-pill <?php echo $filter_type === 'all' ? 'is-active' : ''; ?>">همه</a>
                <a href="<?php echo esc_url(add_query_arg('type', 'comment')); ?>" class="filter-pill <?php echo $filter_type === 'comment' ? 'is-active' : ''; ?>">💬 دیدگاه</a>
                <a href="<?php echo esc_url(add_query_arg('type', 'review')); ?>" class="filter-pill <?php echo $filter_type === 'review' ? 'is-active' : ''; ?>">📝 نقد</a>
                <a href="<?php echo esc_url(add_query_arg('type', 'theory')); ?>" class="filter-pill <?php echo $filter_type === 'theory' ? 'is-active' : ''; ?>">🧠 تئوری</a>
            </div>
        </div>

        <div class="filter-group">
            <label class="filter-label">ترتیب:</label>
            <div class="filter-pills">
                <a href="<?php echo esc_url(add_query_arg('sort', 'newest')); ?>" class="filter-pill <?php echo $filter_sort === 'newest' ? 'is-active' : ''; ?>">جدیدترین</a>
                <a href="<?php echo esc_url(add_query_arg('sort', 'popular')); ?>" class="filter-pill <?php echo $filter_sort === 'popular' ? 'is-active' : ''; ?>">محبوب‌ترین</a>
            </div>
        </div>
    </div>

    <!-- Comments List -->
    <div class="all-comments-page__list">
        <?php if (empty($results)) : ?>
            <div class="comments-empty">
                <div class="comments-empty__icon">🖋️</div>
                <p>دیدگاهی یافت نشد.</p>
            </div>
        <?php else : ?>
            <?php foreach ($results as $row) :
                $comment = new WP_Comment($row);
                $depth = 0;
                include get_template_directory() . '/templates/comments/comment-single.php';
            endforeach;
            unset($comment);
            ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1) : ?>
        <div class="all-comments-page__pagination">
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
</div>