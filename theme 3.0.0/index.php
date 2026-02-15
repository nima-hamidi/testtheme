<?php
/**
 * فایل: index.php
 * توضیح: قالب پایه فال‌بک - ایندکس اصلی
 * نسخه: 2.0.0
 */

get_header();
?>

<div class="novel-container">
    <?php novel_breadcrumb(); ?>

    <div class="novel-page-header">
        <h1>آخرین مطالب</h1>
    </div>

    <?php if (have_posts()) : ?>
        <div class="novel-posts-list">
            <?php while (have_posts()) : the_post(); ?>
                <article class="novel-card novel-card-interactive" style="padding: var(--spacing-md); margin-bottom: var(--spacing-md);">
                    <h2 style="font-size: var(--font-size-lg); margin-bottom: var(--spacing-sm);">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h2>
                    <div class="text-muted text-sm" style="margin-bottom: var(--spacing-sm);">
                        <?php echo esc_html(novel_time_ago(get_the_time('U'))); ?>
                    </div>
                    <?php the_excerpt(); ?>
                </article>
            <?php endwhile; ?>
        </div>

        <?php novel_pagination(); ?>

    <?php else : ?>
        <div class="text-center" style="padding: var(--spacing-3xl) 0;">
            <p class="text-muted">محتوایی یافت نشد.</p>
        </div>
    <?php endif; ?>
</div>

<?php get_footer(); ?>