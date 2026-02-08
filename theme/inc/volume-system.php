<?php
/**
 * سیستم جلدبندی (Volume) برای لایت‌ناول
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * آیا رمان لایت‌ناول است؟
 */
function fn_is_light_novel( $novel_id ) {
    $types = wp_get_post_terms( $novel_id, 'novel_type', array( 'fields' => 'slugs' ) );
    return ( ! is_wp_error( $types ) && in_array( 'light-novel', $types ) );
}

/**
 * دریافت نوع اثر
 */
function fn_get_novel_type( $novel_id ) {
    $types = wp_get_post_terms( $novel_id, 'novel_type' );
    if ( ! empty( $types ) && ! is_wp_error( $types ) ) {
        return $types[0];
    }
    return null;
}

/**
 * دریافت بج نوع اثر
 */
function fn_novel_type_badge( $novel_id ) {
    $type = fn_get_novel_type( $novel_id );
    if ( ! $type ) return '';

    $class = $type->slug === 'light-novel' ? 'fn-type-badge--ln' : 'fn-type-badge--wn';
    return '<span class="fn-type-badge ' . $class . '">' . esc_html( $type->name ) . '</span>';
}

/**
 * AJAX چک نوع رمان
 */
function fn_ajax_check_novel_type() {
    $novel_id = absint( $_POST['novel_id'] ?? 0 );
    wp_send_json_success( array(
        'is_light_novel' => fn_is_light_novel( $novel_id ),
    ) );
}
add_action( 'wp_ajax_fn_check_novel_type', 'fn_ajax_check_novel_type' );

/**
 * دریافت جلدهای یک رمان لایت‌ناول
 */
function fn_get_volumes( $novel_id ) {
    global $wpdb;

    $volumes = $wpdb->get_results( $wpdb->prepare(
        "SELECT DISTINCT
            pm_vol.meta_value AS volume_number,
            pm_title.meta_value AS volume_title,
            COUNT(p.ID) AS chapter_count
         FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm_novel ON p.ID = pm_novel.post_id
            AND pm_novel.meta_key = '_fn_parent_novel'
         INNER JOIN {$wpdb->postmeta} pm_vol ON p.ID = pm_vol.post_id
            AND pm_vol.meta_key = '_fn_volume_number'
         LEFT JOIN {$wpdb->postmeta} pm_title ON p.ID = pm_title.post_id
            AND pm_title.meta_key = '_fn_volume_title'
         WHERE p.post_type = 'chapter'
           AND p.post_status = 'publish'
           AND pm_novel.meta_value = %s
           AND pm_vol.meta_value != ''
           AND pm_vol.meta_value IS NOT NULL
         GROUP BY pm_vol.meta_value, pm_title.meta_value
         ORDER BY CAST(pm_vol.meta_value AS UNSIGNED) ASC",
        strval( $novel_id )
    ) );

    return $volumes;
}

/**
 * دریافت فصل‌های یک جلد خاص
 */
function fn_get_volume_chapters( $novel_id, $volume_number ) {
    return new WP_Query( array(
        'post_type'      => 'chapter',
        'posts_per_page' => -1,
        'orderby'        => 'meta_value_num',
        'meta_key'       => '_fn_chapter_number',
        'order'          => 'ASC',
        'post_status'    => 'publish',
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'   => '_fn_parent_novel',
                'value' => $novel_id,
            ),
            array(
                'key'   => '_fn_volume_number',
                'value' => $volume_number,
            ),
        ),
    ) );
}
