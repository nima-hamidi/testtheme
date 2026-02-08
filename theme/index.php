<?php
/**
 * قالب پیش‌فرض (Fallback)
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();
?>

<div class="fn-container" style="padding: 40px 20px;">
    <div class="fn-section__header">
        <h1 class="fn-section__title">
            <?php
            if ( is_home() ) {
                echo 'آخرین مطالب';
            } elseif ( is_archive() ) {
                the_archive_title();
            } elseif ( is_search() ) {
                printf( 'نتایج جستجو: %s', esc_html( get_search_query() ) );
            } else {
                echo 'مطالب';
            }
            ?>
        </h1>
    </div>

    <?php fn_breadcrumb(); ?>

    <?php if ( have_posts() ) : ?>
        <div class="fn-novels-grid">
            <?php
            while ( have_posts() ) :
                the_post();
                if ( get_post_type() === 'novel' ) {
                    get_template_part( 'template-parts/novel-card' );
                } else {
                    ?>
                    <article class="fn-novel-card">
                        <a href="<?php the_permalink(); ?>" class="fn-novel-card__link">
                            <div class="fn-novel-card__cover">
                                <?php if ( has_post_thumbnail() ) : ?>
                                    <?php the_post_thumbnail( 'novel-card' ); ?>
                                <?php endif; ?>
                            </div>
                            <div class="fn-novel-card__info">
                                <h3 class="fn-novel-card__title"><?php the_title(); ?></h3>
                                <div class="fn-novel-card__meta">
                                    <span><?php echo fn_time_ago( get_the_time( 'U' ) ); ?></span>
                                </div>
                            </div>
                        </a>
                    </article>
                    <?php
                }
            endwhile;
            ?>
        </div>

        <!-- صفحه‌بندی -->
        <nav class="fn-pagination">
            <?php
            echo paginate_links( array(
                'prev_text' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg> قبلی',
                'next_text' => 'بعدی <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>',
                'type'      => 'list',
            ) );
            ?>
        </nav>
    <?php else : ?>
        <div class="fn-empty-state" style="text-align: center; padding: 60px 20px;">
            <div style="font-size: 3rem; margin-bottom: 16px;">📭</div>
            <h3>محتوایی یافت نشد</h3>
            <p style="color: var(--fn-text-muted);">متأسفانه نتیجه‌ای مطابق با درخواست شما پیدا نشد.</p>
        </div>
    <?php endif; ?>
</div>

<?php get_footer(); ?>