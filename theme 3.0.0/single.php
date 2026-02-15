<?php
/**
 * فایل: single.php
 * توضیح: نمایش تک‌نوشته (وبلاگ)
 * نسخه: 2.0.0
 */

get_header();
?>

<div class="novel-container novel-container-md">
    <?php novel_breadcrumb(); ?>

    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <article class="novel-card" style="padding: var(--spacing-xl);">
            <header style="margin-bottom: var(--spacing-lg);">
                <h1 style="font-size: var(--font-size-2xl); margin-bottom: var(--spacing-sm);">
                    <?php the_title(); ?>
                </h1>
                <div class="flex gap-md text-sm text-muted">
                    <span><?php echo esc_html(novel_time_ago(get_the_time('U'))); ?></span>
                    <span>·</span>
                    <span><?php echo esc_html(get_the_author()); ?></span>
                </div>
            </header>

            <?php if (has_post_thumbnail()) : ?>
            <div style="margin-bottom: var(--spacing-lg); border-radius: var(--border-radius-lg); overflow:hidden;">
                <?php the_post_thumbnail('large', ['style' => 'width:100%; height:auto;', 'loading' => 'lazy']); ?>
            </div>
            <?php endif; ?>

            <div class="novel-post-content" style="line-height: var(--line-height-relaxed);">
                <?php the_content(); ?>
            </div>
        </article>

        <?php if (comments_open() || get_comments_number()) :
            comments_template();
        endif; ?>

    <?php endwhile; endif; ?>
</div>

<?php get_footer(); ?>