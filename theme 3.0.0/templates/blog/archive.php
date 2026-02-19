<?php
/**
 * Blog Archive Template
 * آرشیو نوشته‌های وبلاگ
 * 
 * @package suspended developer
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

get_header();

$paged = get_query_var('paged') ? get_query_var('paged') : 1;
$current_cat = get_query_var('cat') ? absint(get_query_var('cat')) : 0;

// Query اصلی
$args = [
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'paged'          => $paged,
    'posts_per_page' => 10,
];

if ($current_cat) {
    $args['cat'] = $current_cat;
}

$blog_query = new WP_Query($args);
$is_first = ($paged === 1 && !$current_cat);

// دسته‌بندی‌ها
$categories = get_categories([
    'orderby'    => 'count',
    'order'      => 'DESC',
    'hide_empty' => true,
]);

// آخرین نوشته‌ها (سایدبار)
$recent_posts = get_posts([
    'post_type'      => 'post',
    'posts_per_page' => 5,
    'post_status'    => 'publish',
]);

// تگ‌ها
$tags = get_tags([
    'orderby' => 'count',
    'order'   => 'DESC',
    'number'  => 30,
]);
?>

<main class="novel-main">
    <div class="novel-container">

        <?php if (function_exists('novel_breadcrumbs')): ?>
            <div class="novel-breadcrumbs-wrap">
                <?php novel_breadcrumbs(); ?>
            </div>
        <?php endif; ?>

        <div class="novel-page-header">
            <h1 class="novel-page-title">
                <span class="novel-page-icon">📰</span>
                <?php
                if ($current_cat) {
                    echo 'دسته: ' . esc_html(get_cat_name($current_cat));
                } elseif (is_tag()) {
                    echo 'برچسب: ' . esc_html(single_tag_title('', false));
                } else {
                    echo 'وبلاگ';
                }
                ?>
            </h1>
            <p class="novel-page-subtitle">آخرین اخبار، مقالات و معرفی رمان‌ها</p>
        </div>

        <div class="novel-blog-layout">

            <!-- محتوای اصلی -->
            <div class="novel-blog-content">

                <!-- فیلتر دسته‌بندی -->
                <div class="novel-blog-filters" id="blogFilters">
                    <a href="<?php echo esc_url(get_permalink(get_option('page_for_posts'))); ?>"
                       class="novel-blog-filter <?php echo !$current_cat ? 'is-active' : ''; ?>"
                       data-cat="0">
                        همه
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="<?php echo esc_url(get_category_link($cat->term_id)); ?>"
                           class="novel-blog-filter <?php echo $current_cat === $cat->term_id ? 'is-active' : ''; ?>"
                           data-cat="<?php echo $cat->term_id; ?>">
                            <?php echo esc_html($cat->name); ?>
                            <span class="novel-blog-filter__count"><?php echo $cat->count; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <?php if ($blog_query->have_posts()): ?>

                    <div class="novel-blog-grid" id="blogGrid">
                        <?php
                        $post_index = 0;
                        while ($blog_query->have_posts()): $blog_query->the_post();
                            $post_index++;
                            $is_hero = ($is_first && $post_index === 1);
                            $reading_time = novel_blog_reading_time(get_the_content());
                            $post_cats = get_the_category();
                            $first_cat = !empty($post_cats) ? $post_cats[0] : null;
                        ?>

                        <article class="novel-blog-card <?php echo $is_hero ? 'novel-blog-card--hero' : ''; ?>">
                            <?php if (has_post_thumbnail()): ?>
                                <a href="<?php the_permalink(); ?>" class="novel-blog-card__image-link">
                                    <div class="novel-blog-card__image">
                                        <?php the_post_thumbnail($is_hero ? 'large' : 'medium_large', [
                                            'class'   => 'novel-blog-card__img',
                                            'loading' => $post_index <= 2 ? 'eager' : 'lazy',
                                            'alt'     => get_the_title(),
                                        ]); ?>
                                    </div>
                                </a>
                            <?php endif; ?>

                            <div class="novel-blog-card__body">
                                <div class="novel-blog-card__meta-top">
                                    <?php if ($first_cat): ?>
                                        <a href="<?php echo esc_url(get_category_link($first_cat->term_id)); ?>"
                                           class="novel-blog-card__category">
                                            <?php echo esc_html($first_cat->name); ?>
                                        </a>
                                    <?php endif; ?>
                                    <time class="novel-blog-card__date" datetime="<?php echo get_the_date('c'); ?>">
                                        <?php echo get_the_date('j F Y'); ?>
                                    </time>
                                </div>

                                <h2 class="novel-blog-card__title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h2>

                                <p class="novel-blog-card__excerpt">
                                    <?php echo wp_trim_words(get_the_excerpt(), $is_hero ? 30 : 15, '...'); ?>
                                </p>

                                <div class="novel-blog-card__meta-bottom">
                                    <div class="novel-blog-card__author">
                                        <?php
                                        $author_id = get_the_author_meta('ID');
                                        $avatar_id = get_user_meta($author_id, 'novel_avatar', true);
                                        $avatar_url = '';
                                        if ($avatar_id && class_exists('Novel_Avatars')) {
                                            $avatar_url = Novel_Avatars::get_avatar_url($avatar_id);
                                        }
                                        if (!$avatar_url) {
                                            $avatar_url = get_avatar_url($author_id, ['size' => 32]);
                                        }
                                        ?>
                                        <img src="<?php echo esc_url($avatar_url); ?>"
                                             alt="<?php the_author(); ?>"
                                             class="novel-blog-card__author-avatar"
                                             width="28" height="28">
                                        <span class="novel-blog-card__author-name"><?php the_author(); ?></span>
                                    </div>
                                    <div class="novel-blog-card__stats">
                                        <span class="novel-blog-card__stat">⏱ <?php echo $reading_time; ?> دقیقه</span>
                                        <span class="novel-blog-card__stat">💬 <?php echo get_comments_number(); ?></span>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <?php endwhile; ?>
                    </div>

                    <!-- صفحه‌بندی -->
                    <?php if ($blog_query->max_num_pages > 1): ?>
                        <div class="novel-pagination">
                            <?php
                            echo paginate_links([
                                'total'     => $blog_query->max_num_pages,
                                'current'   => $paged,
                                'prev_text' => '← قبلی',
                                'next_text' => 'بعدی →',
                                'mid_size'  => 2,
                            ]);
                            ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="novel-empty-state">
                        <div class="novel-empty-icon">📝</div>
                        <h3>نوشته‌ای یافت نشد</h3>
                        <p>هنوز مطلبی در وبلاگ منتشر نشده است.</p>
                    </div>
                <?php endif; wp_reset_postdata(); ?>

            </div>

            <!-- سایدبار -->
            <aside class="novel-blog-sidebar">

                <!-- آخرین نوشته‌ها -->
                <div class="novel-blog-widget">
                    <h3 class="novel-blog-widget__title">📝 آخرین نوشته‌ها</h3>
                    <ul class="novel-blog-widget__list">
                        <?php foreach ($recent_posts as $rp): ?>
                            <li class="novel-blog-widget__item">
                                <?php if (has_post_thumbnail($rp->ID)): ?>
                                    <a href="<?php echo get_permalink($rp->ID); ?>" class="novel-blog-widget__thumb">
                                        <?php echo get_the_post_thumbnail($rp->ID, 'thumbnail', ['class' => 'novel-blog-widget__thumb-img']); ?>
                                    </a>
                                <?php endif; ?>
                                <div class="novel-blog-widget__item-body">
                                    <a href="<?php echo get_permalink($rp->ID); ?>" class="novel-blog-widget__item-title">
                                        <?php echo esc_html(wp_trim_words($rp->post_title, 8)); ?>
                                    </a>
                                    <time class="novel-blog-widget__item-date"><?php echo get_the_date('j F', $rp->ID); ?></time>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- دسته‌بندی‌ها -->
                <div class="novel-blog-widget">
                    <h3 class="novel-blog-widget__title">📂 دسته‌بندی‌ها</h3>
                    <ul class="novel-blog-widget__cats">
                        <?php foreach ($categories as $cat): ?>
                            <li>
                                <a href="<?php echo esc_url(get_category_link($cat->term_id)); ?>">
                                    <?php echo esc_html($cat->name); ?>
                                    <span>(<?php echo $cat->count; ?>)</span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- ابر تگ -->
                <?php if (!empty($tags)): ?>
                    <div class="novel-blog-widget">
                        <h3 class="novel-blog-widget__title">🏷 برچسب‌ها</h3>
                        <div class="novel-blog-widget__tags">
                            <?php
                            $max_count = max(array_column($tags, 'count'));
                            $min_count = min(array_column($tags, 'count'));
                            foreach ($tags as $tag):
                                $size = $max_count > $min_count
                                    ? 12 + (($tag->count - $min_count) / ($max_count - $min_count)) * 8
                                    : 14;
                                ?>
                                <a href="<?php echo esc_url(get_tag_link($tag->term_id)); ?>"
                                   class="novel-blog-widget__tag"
                                   style="font-size: <?php echo round($size); ?>px;">
                                    <?php echo esc_html($tag->name); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ویجت نظرسنجی -->
                <?php if (class_exists('Novel_Polls')):
                    $polls = Novel_Polls::get_instance();
                    $active_poll = $polls->get_latest_active_poll();
                    if ($active_poll):
                        ?>
                        <div class="novel-blog-widget">
                            <h3 class="novel-blog-widget__title">📊 نظرسنجی</h3>
                            <?php $polls->render_poll_template($active_poll, true); ?>
                        </div>
                    <?php endif; endif; ?>

            </aside>

        </div>
    </div>
</main>

<?php get_footer(); ?>