<?php
/**
 * صفحه نویسنده / مترجم
 * نمایش بیوگرافی + لیست آثار + آمار
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$author_id   = get_queried_object_id();
$author_obj  = get_queried_object();
$author_name = $author_obj->display_name;
$author_bio  = get_the_author_meta( 'description', $author_id );
$author_url  = get_the_author_meta( 'url', $author_id );
$member_since = get_the_author_meta( 'user_registered', $author_id );

// آثار نویسنده
$paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
$author_novels = new WP_Query( array(
    'post_type'      => 'novel',
    'posts_per_page' => 12,
    'paged'          => $paged,
    'author'         => $author_id,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'post_status'    => 'publish',
) );

// آمار
$total_novels = $author_novels->found_posts;

// مجموع بازدید آثار
global $wpdb;
$total_views = $wpdb->get_var( $wpdb->prepare(
    "SELECT SUM(CAST(pm.meta_value AS UNSIGNED))
     FROM {$wpdb->postmeta} pm
     INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
     WHERE pm.meta_key = '_fn_total_views'
     AND p.post_type = 'novel'
     AND p.post_author = %d
     AND p.post_status = 'publish'",
    $author_id
) );

// مجموع فصل‌ها
$total_chapters = $wpdb->get_var( $wpdb->prepare(
    "SELECT SUM(CAST(pm2.meta_value AS UNSIGNED))
     FROM {$wpdb->postmeta} pm2
     INNER JOIN {$wpdb->posts} p2 ON p2.ID = pm2.post_id
     WHERE pm2.meta_key = '_fn_chapter_count'
     AND p2.post_type = 'novel'
     AND p2.post_author = %d
     AND p2.post_status = 'publish'",
    $author_id
) );
?>

<div class="fn-container">
    <?php fn_breadcrumb(); ?>

    <!-- هدر نویسنده -->
    <div class="fn-author-hero">
        <div class="fn-author-hero__avatar">
            <?php echo get_avatar( $author_id, 120, '', $author_name, array( 'class' => 'fn-author-hero__img' ) ); ?>
        </div>
        <div class="fn-author-hero__info">
            <h1 class="fn-author-hero__name"><?php echo esc_html( $author_name ); ?></h1>

            <?php if ( $author_bio ) : ?>
                <p class="fn-author-hero__bio"><?php echo wp_kses_post( $author_bio ); ?></p>
            <?php endif; ?>

            <?php if ( $author_url ) : ?>
                <a href="<?php echo esc_url( $author_url ); ?>" class="fn-author-hero__link" target="_blank" rel="noopener">
                    🔗 <?php echo esc_html( $author_url ); ?>
                </a>
            <?php endif; ?>

            <div class="fn-author-hero__stats">
                <div class="fn-stat">
                    <div class="fn-stat__value"><?php echo fn_format_number( $total_novels ); ?></div>
                    <div class="fn-stat__label">اثر</div>
                </div>
                <div class="fn-stat">
                    <div class="fn-stat__value"><?php echo fn_format_number( absint( $total_chapters ) ); ?></div>
                    <div class="fn-stat__label">فصل</div>
                </div>
                <div class="fn-stat">
                    <div class="fn-stat__value"><?php echo fn_format_number( absint( $total_views ) ); ?></div>
                    <div class="fn-stat__label">بازدید</div>
                </div>
                <div class="fn-stat">
                    <div class="fn-stat__value"><?php echo fn_persian_number( date_i18n( 'Y', strtotime( $member_since ) ) ); ?></div>
                    <div class="fn-stat__label">عضویت</div>
                </div>
            </div>
        </div>
    </div>

    <!-- آثار نویسنده -->
    <div class="fn-section">
        <div class="fn-section__header">
            <h2 class="fn-section__title">آثار <?php echo esc_html( $author_name ); ?></h2>
        </div>

        <?php if ( $author_novels->have_posts() ) : ?>
            <div class="fn-novels-grid">
                <?php
                while ( $author_novels->have_posts() ) :
                    $author_novels->the_post();
                    get_template_part( 'template-parts/novel-card' );
                endwhile;
                ?>
            </div>

            <!-- صفحه‌بندی -->
            <?php if ( $author_novels->max_num_pages > 1 ) : ?>
                <nav class="fn-pagination">
                    <?php
                    echo paginate_links( array(
                        'total'     => $author_novels->max_num_pages,
                        'current'   => $paged,
                        'prev_text' => '← قبلی',
                        'next_text' => 'بعدی →',
                        'type'      => 'list',
                    ) );
                    ?>
                </nav>
            <?php endif; ?>

            <?php wp_reset_postdata(); ?>

        <?php else : ?>
            <div class="fn-empty-state">
                <div class="fn-empty-state__icon">📝</div>
                <h3>هنوز اثری منتشر نشده</h3>
                <p>این نویسنده هنوز رمانی منتشر نکرده است.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>