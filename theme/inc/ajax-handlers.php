<?php
/**
 * هندلرهای عمومی AJAX
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * جستجوی زنده رمان‌ها (برای غیرلاگین‌ها هم)
 */
function fn_ajax_live_search() {
    $query = sanitize_text_field( $_GET['query'] ?? '' );

    if ( mb_strlen( $query ) < 2 ) {
        wp_send_json_success( array( 'novels' => array() ) );
    }

    $novels = new WP_Query( array(
        'post_type'      => 'novel',
        'posts_per_page' => 5,
        's'              => $query,
        'post_status'    => 'publish',
    ) );

    $results = array();
    if ( $novels->have_posts() ) {
        while ( $novels->have_posts() ) {
            $novels->the_post();
            $id = get_the_ID();
            $results[] = array(
                'id'      => $id,
                'title'   => get_the_title(),
                'url'     => get_permalink(),
                'cover'   => get_the_post_thumbnail_url( $id, 'novel-cover-sm' ),
                'genres'  => wp_get_post_terms( $id, 'genre', array( 'fields' => 'names' ) ),
                'chapters'=> fn_get_chapter_count( $id ),
            );
        }
        wp_reset_postdata();
    }

    wp_send_json_success( array( 'novels' => $results ) );
}
add_action( 'wp_ajax_fn_live_search', 'fn_ajax_live_search' );
add_action( 'wp_ajax_nopriv_fn_live_search', 'fn_ajax_live_search' );