<?php
/**
 * Template Name: آرشیو ژانرها
 *
 * ساخت: برگه جدید → عنوان "ژانرها" → نامک "genre" → قالب "آرشیو ژانرها"
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$genres = get_terms( array(
    'taxonomy'   => 'genre',
    'hide_empty' => false,
    'orderby'    => 'count',
    'order'      => 'DESC',
) );
?>

<div class="fn-container">
    <?php fn_breadcrumb(); ?>

    <div class="fn-genres-page">
        <div class="fn-section__header">
            <h1 class="fn-section__title">📂 مرور بر اساس ژانر</h1>
        </div>

        <div class="fn-genres-page__grid">
            <?php if ( ! empty( $genres ) && ! is_wp_error( $genres ) ) :
                foreach ( $genres as $genre ) :
                    $icon  = get_term_meta( $genre->term_id, '_fn_genre_icon', true ) ?: '📖';
                    $count = $genre->count;

                    // دریافت ۳ رمان برتر ژانر
                    $top_novels = new WP_Query( array(
                        'post_type'      => 'novel',
                        'posts_per_page' => 3,
                        'post_status'    => 'publish',
                        'tax_query'      => array(
                            array( 'taxonomy' => 'genre', 'field' => 'term_id', 'terms' => $genre->term_id ),
                        ),
                        'meta_key' => '_fn_total_views',
                        'orderby'  => 'meta_value_num',
                        'order'    => 'DESC',
                    ) );
            ?>
                <a href="<?php echo esc_url( get_term_link( $genre ) ); ?>" class="fn-genre-card">
                    <div class="fn-genre-card__header">
                        <span class="fn-genre-card__icon"><?php echo esc_html( $icon ); ?></span>
                        <h3 class="fn-genre-card__name"><?php echo esc_html( $genre->name ); ?></h3>
                        <span class="fn-genre-card__count"><?php echo $count; ?> رمان</span>
                    </div>

                    <?php if ( $genre->description ) : ?>
                        <p class="fn-genre-card__desc"><?php echo esc_html( wp_trim_words( $genre->description, 12 ) ); ?></p>
                    <?php endif; ?>

                    <?php if ( $top_novels->have_posts() ) : ?>
                        <div class="fn-genre-card__covers">
                            <?php while ( $top_novels->have_posts() ) : $top_novels->the_post();
                                $c = get_the_post_thumbnail_url( get_the_ID(), 'novel-cover-sm' );
                                if ( $c ) :
                            ?>
                                <img src="<?php echo esc_url( $c ); ?>" alt="" class="fn-genre-card__cover-img" loading="lazy">
                            <?php endif; endwhile; wp_reset_postdata(); ?>
                        </div>
                    <?php endif; ?>
                </a>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>
