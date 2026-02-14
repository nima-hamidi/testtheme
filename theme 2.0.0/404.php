<?php
/**
 * صفحه ۴۰۴ - یافت نشد
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();
?>

<div class="fn-container">
    <div class="fn-404">
        <div class="fn-404__number">۴۰۴</div>
        <h1 style="font-size: 1.6rem; margin: 20px 0 10px;">صفحه مورد نظر یافت نشد!</h1>
        <p style="color: var(--fn-text-muted); max-width: 400px; margin: 0 auto 30px; line-height: 1.8;">
            به نظر می‌رسد این صفحه وجود ندارد یا حذف شده.
            شاید یکی از لینک‌های زیر به شما کمک کند.
        </p>

        <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; margin-bottom: 50px;">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="fn-btn fn-btn--primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                صفحه اصلی
            </a>
            <a href="<?php echo esc_url( get_post_type_archive_link( 'novel' ) ); ?>" class="fn-btn fn-btn--ghost">
                📚 کتابخانه رمان‌ها
            </a>
        </div>

        <!-- رمان‌های تصادفی پیشنهادی -->
        <div style="max-width: 900px; margin: 0 auto;">
            <h3 class="fn-section__title" style="justify-content: center; margin-bottom: 20px;">
                شاید این رمان‌ها را دوست داشته باشید
            </h3>
            <div class="fn-novels-grid">
                <?php
                $random_novels = new WP_Query( array(
                    'post_type'      => 'novel',
                    'posts_per_page' => 6,
                    'orderby'        => 'rand',
                    'post_status'    => 'publish',
                ) );

                if ( $random_novels->have_posts() ) :
                    while ( $random_novels->have_posts() ) :
                        $random_novels->the_post();
                        get_template_part( 'template-parts/novel-card' );
                    endwhile;
                    wp_reset_postdata();
                endif;
                ?>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>