<?php
/**
 * Blog Single Post Template
 * نمایش تک نوشته وبلاگ
 * 
 * @package suspended developer
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

get_header();

if (have_posts()): the_post();

$author_id    = get_the_author_meta('ID');
$reading_time = novel_blog_reading_time(get_the_content());
$post_cats    = get_the_category();
$post_tags    = get_the_tags();

// آواتار نویسنده
$avatar_id  = get_user_meta($author_id, 'novel_avatar', true);
$avatar_url = '';
if ($avatar_id && class_exists('Novel_Avatars')) {
    $avatar_url = Novel_Avatars::get_avatar_url($avatar_id);
}
if (!$avatar_url) {
    $avatar_url = get_avatar_url($author_id, ['size' => 64]);
}

// نوشته‌های مرتبط
$related_args = [
    'post_type'      => 'post',
    'posts_per_page' => 3,
    'post__not_in'   => [get_the_ID()],
    'post_status'    => 'publish',
    'orderby'        => 'rand',
];
if (!empty($post_cats)) {
    $related_args['cat'] = $post_cats[0]->term_id;
}
$related_posts = get_posts($related_args);
?>

<main class="novel-main">

    <!-- هدر بنری -->
    <?php if (has_post_thumbnail()): ?>
        <div class="novel-blog-hero">
            <div class="novel-blog-hero__image">
                <?php the_post_thumbnail('full', [
                    'class' => 'novel-blog-hero__img',
                    'alt'   => get_the_title(),
                ]); ?>
            </div>
            <div class="novel-blog-hero__overlay">
                <div class="novel-container">
                    <div class="novel-blog-hero__content">
                        <?php if (!empty($post_cats)): ?>
                            <div class="novel-blog-hero__cats">
                                <?php foreach ($post_cats as $cat): ?>
                                    <a href="<?php echo esc_url(get_category_link($cat->term_id)); ?>"
                                       class="novel-blog-hero__cat">
                                        <?php echo esc_html($cat->name); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <h1 class="novel-blog-hero__title"><?php the_title(); ?></h1>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="novel-container">

        <?php if (function_exists('novel_breadcrumbs')): ?>
            <div class="novel-breadcrumbs-wrap">
                <?php novel_breadcrumbs(); ?>
            </div>
        <?php endif; ?>

        <article class="novel-blog-single">

            <!-- اطلاعات نویسنده + تاریخ -->
            <div class="novel-blog-single__meta">
                <div class="novel-blog-single__author">
                    <img src="<?php echo esc_url($avatar_url); ?>"
                         alt="<?php the_author(); ?>"
                         class="novel-blog-single__author-avatar"
                         width="48" height="48">
                    <div class="novel-blog-single__author-info">
                        <a href="<?php echo esc_url(get_author_posts_url($author_id)); ?>"
                           class="novel-blog-single__author-name">
                            <?php the_author(); ?>
                        </a>
                        <div class="novel-blog-single__meta-row">
                            <time datetime="<?php echo get_the_date('c'); ?>">
                                📅 <?php echo get_the_date('j F Y'); ?>
                            </time>
                            <span>⏱ <?php echo $reading_time; ?> دقیقه مطالعه</span>
                            <span>💬 <?php echo get_comments_number(); ?> دیدگاه</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- عنوان (اگر بدون تصویر شاخص) -->
            <?php if (!has_post_thumbnail()): ?>
                <h1 class="novel-blog-single__title"><?php the_title(); ?></h1>
            <?php endif; ?>

            <!-- محتوای نوشته -->
            <div class="novel-blog-single__content novel-reading-content">
                <?php the_content(); ?>
            </div>

            <!-- تگ‌ها -->
            <?php if (!empty($post_tags)): ?>
                <div class="novel-blog-single__tags">
                    <span class="novel-blog-single__tags-label">🏷 برچسب‌ها:</span>
                    <?php foreach ($post_tags as $tag): ?>
                        <a href="<?php echo esc_url(get_tag_link($tag->term_id)); ?>"
                           class="novel-blog-single__tag">
                            <?php echo esc_html($tag->name); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- اشتراک‌گذاری -->
            <div class="novel-blog-single__share">
                <span class="novel-blog-single__share-label">📤 اشتراک‌گذاری:</span>
                <div class="novel-blog-single__share-buttons">
                    <a href="https://t.me/share/url?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode(get_the_title()); ?>"
                       target="_blank" rel="noopener noreferrer"
                       class="novel-share-btn novel-share-btn--telegram" title="تلگرام">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                        </svg>
                    </a>
                    <a href="https://api.whatsapp.com/send?text=<?php echo urlencode(get_the_title() . ' ' . get_permalink()); ?>"
                       target="_blank" rel="noopener noreferrer"
                       class="novel-share-btn novel-share-btn--whatsapp" title="واتساپ">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode(get_the_title()); ?>"
                       target="_blank" rel="noopener noreferrer"
                       class="novel-share-btn novel-share-btn--twitter" title="توییتر">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                    </a>
                    <button class="novel-share-btn novel-share-btn--copy" title="کپی لینک"
                            onclick="navigator.clipboard.writeText('<?php echo esc_url(get_permalink()); ?>').then(()=>{if(typeof NovelApp!=='undefined')NovelApp.showToast('لینک کپی شد! ✅','success');else alert('لینک کپی شد!')})">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="9" y="9" width="13" height="13" rx="2"/>
                            <path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- نوشته‌های مرتبط -->
            <?php if (!empty($related_posts)): ?>
                <div class="novel-blog-single__related">
                    <h3 class="novel-blog-single__related-title">📚 نوشته‌های مرتبط</h3>
                    <div class="novel-blog-single__related-grid">
                        <?php foreach ($related_posts as $rp):
                            $rp_cats = get_the_category($rp->ID);
                            ?>
                            <a href="<?php echo get_permalink($rp->ID); ?>" class="novel-blog-related-card">
                                <?php if (has_post_thumbnail($rp->ID)): ?>
                                    <div class="novel-blog-related-card__image">
                                        <?php echo get_the_post_thumbnail($rp->ID, 'medium', [
                                            'class'   => 'novel-blog-related-card__img',
                                            'loading' => 'lazy',
                                        ]); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="novel-blog-related-card__body">
                                    <?php if (!empty($rp_cats)): ?>
                                        <span class="novel-blog-related-card__cat"><?php echo esc_html($rp_cats[0]->name); ?></span>
                                    <?php endif; ?>
                                    <h4 class="novel-blog-related-card__title"><?php echo esc_html($rp->post_title); ?></h4>
                                    <time class="novel-blog-related-card__date"><?php echo get_the_date('j F', $rp->ID); ?></time>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </article>

        <!-- بخش دیدگاه -->
        <div class="novel-blog-single__comments">
            <?php
            if (comments_open() || get_comments_number()) {
                comments_template();
            }
            ?>
        </div>

    </div>
</main>

<?php endif; get_footer(); ?>