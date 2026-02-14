<?php
/**
 * سیستم Breadcrumb (مسیر نمایش)
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * نمایش Breadcrumb
 */
function fn_breadcrumb() {
    if ( is_front_page() ) return;

    echo '<nav class="fn-breadcrumb" aria-label="مسیر">';

    // لینک خانه
    echo '<a href="' . esc_url( home_url( '/' ) ) . '">خانه</a>';
    echo '<span class="fn-breadcrumb__sep">‹</span>';

    if ( is_singular( 'novel' ) ) {
        // صفحه تک رمان
        echo '<a href="' . esc_url( get_post_type_archive_link( 'novel' ) ) . '">کتابخانه</a>';
        echo '<span class="fn-breadcrumb__sep">‹</span>';

        // ژانر اول
        $genres = wp_get_post_terms( get_the_ID(), 'genre' );
        if ( ! empty( $genres ) && ! is_wp_error( $genres ) ) {
            echo '<a href="' . esc_url( get_term_link( $genres[0] ) ) . '">' . esc_html( $genres[0]->name ) . '</a>';
            echo '<span class="fn-breadcrumb__sep">‹</span>';
        }

        echo '<span class="fn-breadcrumb__current">' . esc_html( get_the_title() ) . '</span>';

    } elseif ( is_singular( 'chapter' ) ) {
        // صفحه فصل
        $novel_id = get_post_meta( get_the_ID(), '_fn_parent_novel', true );

        echo '<a href="' . esc_url( get_post_type_archive_link( 'novel' ) ) . '">کتابخانه</a>';
        echo '<span class="fn-breadcrumb__sep">‹</span>';

        if ( $novel_id ) {
            echo '<a href="' . esc_url( get_permalink( $novel_id ) ) . '">' . esc_html( get_the_title( $novel_id ) ) . '</a>';
            echo '<span class="fn-breadcrumb__sep">‹</span>';
        }

        $chapter_num = get_post_meta( get_the_ID(), '_fn_chapter_number', true );
        echo '<span class="fn-breadcrumb__current">فصل ' . esc_html( $chapter_num ) . '</span>';

    } elseif ( is_post_type_archive( 'novel' ) ) {
        echo '<span class="fn-breadcrumb__current">کتابخانه رمان‌ها</span>';

    } elseif ( is_tax( 'genre' ) ) {
        echo '<a href="' . esc_url( get_post_type_archive_link( 'novel' ) ) . '">کتابخانه</a>';
        echo '<span class="fn-breadcrumb__sep">‹</span>';
        echo '<span class="fn-breadcrumb__current">ژانر: ' . esc_html( single_term_title( '', false ) ) . '</span>';

    } elseif ( is_tax( 'novel_status' ) ) {
        echo '<a href="' . esc_url( get_post_type_archive_link( 'novel' ) ) . '">کتابخانه</a>';
        echo '<span class="fn-breadcrumb__sep">‹</span>';
        echo '<span class="fn-breadcrumb__current">' . esc_html( single_term_title( '', false ) ) . '</span>';

    } elseif ( is_search() ) {
        echo '<span class="fn-breadcrumb__current">جستجو: ' . esc_html( get_search_query() ) . '</span>';

    } elseif ( is_author() ) {
        echo '<span class="fn-breadcrumb__current">نویسنده: ' . esc_html( get_the_author() ) . '</span>';

    } elseif ( is_page() ) {
        echo '<span class="fn-breadcrumb__current">' . esc_html( get_the_title() ) . '</span>';

    } elseif ( is_404() ) {
        echo '<span class="fn-breadcrumb__current">صفحه یافت نشد</span>';
    }

    echo '</nav>';
}