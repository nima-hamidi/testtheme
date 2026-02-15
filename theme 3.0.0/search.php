<?php
/**
 * فایل: search.php
 * توضیح: صفحه نتایج جستجو
 * نسخه: 2.0.0
 */

get_header();
?>

<div class="novel-container">
    <?php novel_breadcrumb(); ?>

    <div class="novel-page-header" style="margin-bottom: var(--spacing-lg);">
        <h1>نتایج جستجو برای: «<?php echo esc_html(get_search_query()); ?>»</h1>
        <p class="text-muted text-sm mt-sm">
            <?php echo esc_html(novel_format_number($wp_query->found_posts)); ?> نتیجه یافت شد
        </p>
    </div>

    <?php if (have_posts()) : ?>
        <div class="novel-search-results">
            <?php while (have_posts()) : the_post(); ?>
                <article class="novel-card novel-card-interactive" style="padding: var(--spacing-md); margin-bottom: var(--spacing-md);">
                    <div class="flex gap-md">
                        <?php if (has_post_thumbnail()) : ?>
                        <div style="flex-shrink:0; width:80px;">
                            <?php the_post_thumbnail('novel-thumb', [
                                'style'   => 'border-radius: var(--border-radius-sm); width:80px; height:112px; object-fit:cover;',
                                'loading' => 'lazy',
                            ]); ?>
                        </div>
                        <?php endif; ?>
                        <div style="flex:1; min-width:0;">
                            <h2 style="font-size: var(--font-size-md); margin-bottom: var(--spacing-xs);">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>
                            <div class="text-muted text-xs" style="margin-bottom: var(--spacing-xs);">
                                <span class="novel-badge" style="font-size:10px;"><?php echo esc_html(get_post_type_object(get_post_type())->labels->singular_name); ?></span>
                                · <?php echo esc_html(novel_time_ago(get_the_time('U'))); ?>
                            </div>
                            <p class="text-sm" style="color: var(--text-secondary); margin:0; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                                <?php echo esc_html(wp_trim_words(get_the_excerpt(), 25)); ?>
                            </p>
                        </div>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>

        <?php novel_pagination(); ?>

    <?php else : ?>
        <div class="text-center" style="padding: var(--spacing-3xl) 0;">
            <div style="font-size: 3rem; margin-bottom: var(--spacing-md);">🔍</div>
            <h2 style="margin-bottom: var(--spacing-sm);">نتیجه‌ای یافت نشد</h2>
            <p class="text-muted">عبارت دیگری را جستجو کنید یا از فیلترها استفاده نمایید.</p>
            <a href="<?php echo esc_url(get_post_type_archive_link('novel')); ?>" class="novel-btn novel-btn-primary mt-lg">
                مرور رمان‌ها
            </a>
        </div>
    <?php endif; ?>
</div>

<?php get_footer(); ?>