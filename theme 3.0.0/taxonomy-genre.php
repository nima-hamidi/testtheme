<?php
/**
 * Genre Taxonomy Archive
 * لیست رمان‌های یک ژانر خاص
 * 
 * @package suspended developer
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

get_header();

$term = get_queried_object();
$term_icon = get_term_meta($term->term_id, 'genre_icon', true) ?: '📚';
$term_color = get_term_meta($term->term_id, 'genre_color', true) ?: '#6366f1';

$paged = get_query_var('paged') ? get_query_var('paged') : 1;
$sort = sanitize_text_field($_GET['sort'] ?? 'newest');

$orderby = 'date';
$order = 'DESC';
$meta_key = '';

switch ($sort) {
    case 'popular':
        $meta_key = '_novel_views';
        $orderby = 'meta_value_num';
        $order = 'DESC';
        break;
    case 'rating':
        $meta_key = '_novel_rating';
        $orderby = 'meta_value_num';
        $order = 'DESC';
        break;
    case 'chapters':
        $meta_key = '_novel_chapter_count';
        $orderby = 'meta_value_num';
        $order = 'DESC';
        break;
    case 'oldest':
        $orderby = 'date';
        $order = 'ASC';
        break;
    case 'title':
        $orderby = 'title';
        $order = 'ASC';
        break;
}

$args = [
    'post_type'      => 'novel',
    'post_status'    => 'publish',
    'paged'          => $paged,
    'posts_per_page' => 24,
    'orderby'        => $orderby,
    'order'          => $order,
    'tax_query'      => [[
        'taxonomy' => 'genre',
        'field'    => 'term_id',
        'terms'    => $term->term_id,
    ]],
];

if ($meta_key) {
    $args['meta_key'] = $meta_key;
}

$novels_query = new WP_Query($args);
?>

<main class="novel-main">
    <div class="novel-container">

        <?php if (function_exists('novel_breadcrumbs')): ?>
            <div class="novel-breadcrumbs-wrap">
                <?php novel_breadcrumbs(); ?>
            </div>
        <?php endif; ?>

        <!-- هدر ژانر -->
        <div class="novel-genre-header" style="--genre-color: <?php echo esc_attr($term_color); ?>;">
            <div class="novel-genre-header__icon"><?php echo $term_icon; ?></div>
            <div class="novel-genre-header__info">
                <h1 class="novel-genre-header__title"><?php echo esc_html($term->name); ?></h1>
                <?php if ($term->description): ?>
                    <p class="novel-genre-header__desc"><?php echo esc_html($term->description); ?></p>
                <?php endif; ?>
                <span class="novel-genre-header__count">
                    <?php echo number_format_i18n($novels_query->found_posts); ?> رمان
                </span>
            </div>
        </div>

        <!-- مرتب‌سازی -->
        <div class="novel-genre-sort">
            <?php
            $sort_options = [
                'newest'   => '🕐 جدیدترین',
                'popular'  => '🔥 محبوب‌ترین',
                'rating'   => '⭐ بالاترین امتیاز',
                'chapters' => '📖 بیشترین قسمت',
                'title'    => '🔤 الفبایی',
            ];
            foreach ($sort_options as $key => $label):
                $url = add_query_arg('sort', $key);
                $active = $sort === $key ? 'is-active' : '';
                ?>
                <a href="<?php echo esc_url($url); ?>" class="novel-genre-sort__item <?php echo $active; ?>">
                    <?php echo $label; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- لیست رمان‌ها -->
        <?php if ($novels_query->have_posts()): ?>
            <div class="novel-grid novel-grid--4">
                <?php while ($novels_query->have_posts()): $novels_query->the_post(); ?>
                    <?php get_template_part('templates/novel/novel-card'); ?>
                <?php endwhile; ?>
            </div>

            <!-- صفحه‌بندی -->
            <?php if ($novels_query->max_num_pages > 1): ?>
                <div class="novel-pagination">
                    <?php
                    echo paginate_links([
                        'total'     => $novels_query->max_num_pages,
                        'current'   => $paged,
                        'prev_text' => '← قبلی',
                        'next_text' => 'بعدی →',
                    ]);
                    ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="novel-empty-state">
                <div class="novel-empty-icon"><?php echo $term_icon; ?></div>
                <h3>رمانی در این ژانر یافت نشد</h3>
                <p>هنوز رمانی با ژانر «<?php echo esc_html($term->name); ?>» منتشر نشده.</p>
                <a href="<?php echo esc_url(home_url('/genres/')); ?>" class="novel-btn novel-btn--primary">
                    مشاهده ژانرهای دیگر
                </a>
            </div>
        <?php endif; wp_reset_postdata(); ?>

    </div>
</main>

<?php get_footer(); ?>