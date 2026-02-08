<?php
/**
 * کارت رمان - نمایش استاندارد (گرید)
 * استفاده: get_template_part( 'template-parts/novel-card' )
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$novel_id     = get_the_ID();
$cover_url    = get_the_post_thumbnail_url( $novel_id, 'novel-card' );
$genres       = wp_get_post_terms( $novel_id, 'genre', array( 'fields' => 'names' ) );
$status_terms = wp_get_post_terms( $novel_id, 'novel_status' );
$avg_rating   = floatval( get_post_meta( $novel_id, '_fn_avg_rating', true ) );
$chapter_count = fn_get_chapter_count( $novel_id );
$total_views  = absint( get_post_meta( $novel_id, '_fn_total_views', true ) );
?>

<article class="fn-novel-card" data-novel-id="<?php echo esc_attr( $novel_id ); ?>">
    <a href="<?php the_permalink(); ?>" class="fn-novel-card__link" aria-label="<?php echo esc_attr( get_the_title() ); ?>">

        <!-- کاور -->
        <div class="fn-novel-card__cover">
            <?php if ( $cover_url ) : ?>
                <img src="<?php echo esc_url( $cover_url ); ?>"
                     alt="<?php echo esc_attr( get_the_title() ); ?>"
                     loading="lazy"
                     width="240"
                     height="320">
            <?php else : ?>
                <div class="fn-novel-card__cover-placeholder">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" opacity="0.3">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                    </svg>
                    <span style="font-size: 0.7rem; margin-top: 8px; opacity: 0.4;"><?php echo esc_html( wp_trim_words( get_the_title(), 3 ) ); ?></span>
                </div>
            <?php endif; ?>

            <!-- بج وضعیت -->
            <?php if ( ! empty( $status_terms ) && ! is_wp_error( $status_terms ) ) : ?>
                <span class="fn-novel-card__badge fn-novel-card__badge--<?php echo esc_attr( $status_terms[0]->slug ); ?>">
                    <?php echo esc_html( $status_terms[0]->name ); ?>
                </span>
            <?php endif; ?>

            <!-- تعداد فصل روی کاور -->
            <?php if ( $chapter_count > 0 ) : ?>
                <span class="fn-novel-card__chapters-badge">
                    <?php echo fn_format_number( $chapter_count ); ?> فصل
                </span>
            <?php endif; ?>
        </div>

        <!-- اطلاعات -->
        <div class="fn-novel-card__info">
            <h3 class="fn-novel-card__title"><?php the_title(); ?></h3>

            <div class="fn-novel-card__meta">
                <?php if ( ! empty( $genres ) ) : ?>
                    <span class="fn-novel-card__genre"><?php echo esc_html( $genres[0] ); ?></span>
                <?php endif; ?>

                <?php if ( $avg_rating > 0 ) : ?>
                    <span class="fn-novel-card__rating">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <?php echo number_format( $avg_rating, 1 ); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </a>
</article>