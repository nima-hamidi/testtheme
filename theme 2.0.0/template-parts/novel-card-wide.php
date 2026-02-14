<?php
/**
 * کارت رمان - نمایش عریض (افقی)
 * استفاده: get_template_part( 'template-parts/novel-card', 'wide' )
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$novel_id      = get_the_ID();
$cover_url     = get_the_post_thumbnail_url( $novel_id, 'novel-cover-sm' );
$synopsis      = get_post_meta( $novel_id, '_fn_synopsis', true );
$genres        = wp_get_post_terms( $novel_id, 'genre', array( 'fields' => 'names' ) );
$status_terms  = wp_get_post_terms( $novel_id, 'novel_status' );
$avg_rating    = floatval( get_post_meta( $novel_id, '_fn_avg_rating', true ) );
$chapter_count = fn_get_chapter_count( $novel_id );
$total_views   = absint( get_post_meta( $novel_id, '_fn_total_views', true ) );
$author_name   = get_post_meta( $novel_id, '_fn_original_author', true );
?>

<article class="fn-novel-card-wide">
    <a href="<?php the_permalink(); ?>" class="fn-novel-card-wide__inner">

        <!-- کاور -->
        <div class="fn-novel-card-wide__cover">
            <?php if ( $cover_url ) : ?>
                <img src="<?php echo esc_url( $cover_url ); ?>"
                     alt="<?php echo esc_attr( get_the_title() ); ?>"
                     loading="lazy">
            <?php endif; ?>

            <?php if ( ! empty( $status_terms ) && ! is_wp_error( $status_terms ) ) : ?>
                <span class="fn-novel-card__badge fn-novel-card__badge--<?php echo esc_attr( $status_terms[0]->slug ); ?>">
                    <?php echo esc_html( $status_terms[0]->name ); ?>
                </span>
            <?php endif; ?>
        </div>

        <!-- محتوا -->
        <div class="fn-novel-card-wide__content">
            <h3 class="fn-novel-card-wide__title"><?php the_title(); ?></h3>

            <?php if ( $author_name ) : ?>
                <div class="fn-novel-card-wide__author">
                    ✍️ <?php echo esc_html( $author_name ); ?>
                </div>
            <?php endif; ?>

            <?php if ( $synopsis ) : ?>
                <p class="fn-novel-card-wide__synopsis">
                    <?php echo esc_html( wp_trim_words( $synopsis, 20 ) ); ?>
                </p>
            <?php endif; ?>

            <!-- ژانرها -->
            <?php if ( ! empty( $genres ) ) : ?>
                <div class="fn-novel-card-wide__genres">
                    <?php foreach ( array_slice( $genres, 0, 3 ) as $genre_name ) : ?>
                        <span class="fn-novel-card__genre"><?php echo esc_html( $genre_name ); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- آمار -->
            <div class="fn-novel-card-wide__stats">
                <?php if ( $avg_rating > 0 ) : ?>
                    <span>⭐ <?php echo number_format( $avg_rating, 1 ); ?></span>
                <?php endif; ?>
                <span>📖 <?php echo fn_format_number( $chapter_count ); ?> فصل</span>
                <span>👁 <?php echo fn_format_number( $total_views ); ?></span>
            </div>
        </div>
    </a>
</article>