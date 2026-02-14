<?php
/**
 * صفحه نتایج جستجو
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();
?>

<div class="fn-container" style="padding: 40px 20px;">

    <?php fn_breadcrumb(); ?>

    <!-- هدر جستجو -->
    <div class="fn-search-page-header">
        <h1 class="fn-section__title">
            نتایج جستجو برای: «<?php echo esc_html( get_search_query() ); ?>»
        </h1>
        <p class="fn-search-page-count">
            <?php
            global $wp_query;
            printf( '%s نتیجه یافت شد', fn_persian_number( $wp_query->found_posts ) );
            ?>
        </p>
    </div>

    <!-- فرم جستجوی مجدد -->
    <div class="fn-search-page-form">
        <form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
            <div class="fn-search-page-input-wrap">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="search" name="s" class="fn-search-page-input"
                       value="<?php echo esc_attr( get_search_query() ); ?>"
                       placeholder="جستجوی رمان...">
                <input type="hidden" name="post_type" value="novel">
                <button type="submit" class="fn-btn fn-btn--primary">جستجو</button>
            </div>
        </form>
    </div>

    <?php if ( have_posts() ) : ?>
        <div class="fn-novels-grid" style="margin-top: 30px;">
            <?php
            while ( have_posts() ) :
                the_post();
                if ( get_post_type() === 'novel' ) {
                    get_template_part( 'template-parts/novel-card' );
                } else {
                    get_template_part( 'template-parts/novel-card' );
                }
            endwhile;
            ?>
        </div>

        <nav class="fn-pagination">
            <?php
            echo paginate_links( array(
                'prev_text' => '← قبلی',
                'next_text' => 'بعدی →',
                'type'      => 'list',
            ) );
            ?>
        </nav>
    <?php else : ?>
        <div class="fn-empty-state" style="text-align: center; padding: 80px 20px;">
            <div style="font-size: 4rem; margin-bottom: 20px;">🔍</div>
            <h3 style="margin-bottom: 10px;">نتیجه‌ای یافت نشد!</h3>
            <p style="color: var(--fn-text-muted); margin-bottom: 20px;">
                رمانی با عبارت «<?php echo esc_html( get_search_query() ); ?>» پیدا نشد.
                لطفاً عبارت دیگری جستجو کنید.
            </p>
            <a href="<?php echo esc_url( get_post_type_archive_link( 'novel' ) ); ?>" class="fn-btn fn-btn--primary">
                مرور کتابخانه
            </a>
        </div>
    <?php endif; ?>
</div>

<?php get_footer(); ?>