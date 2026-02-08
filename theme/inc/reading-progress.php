<?php
/**
 * سیستم ذخیره پیشرفت خواندن
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * هندلر AJAX ذخیره پیشرفت خواندن
 */
function fn_ajax_save_reading_progress() {
    check_ajax_referer( 'flavor_novel_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'نیاز به ورود.' ) );
    }

    $user_id         = get_current_user_id();
    $chapter_id      = absint( $_POST['chapter_id'] ?? 0 );
    $scroll_position = floatval( $_POST['scroll_position'] ?? 0 );

    if ( ! $chapter_id || get_post_type( $chapter_id ) !== 'chapter' ) {
        wp_send_json_error( array( 'message' => 'فصل نامعتبر.' ) );
    }

    $novel_id = get_post_meta( $chapter_id, '_fn_parent_novel', true );
    if ( ! $novel_id ) {
        wp_send_json_error( array( 'message' => 'رمان یافت نشد.' ) );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'fn_reading_progress';

    // بررسی رکورد موجود
    $existing = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM $table WHERE user_id = %d AND novel_id = %d",
        $user_id, $novel_id
    ) );

    if ( $existing ) {
        $wpdb->update(
            $table,
            array(
                'chapter_id'      => $chapter_id,
                'scroll_position' => $scroll_position,
            ),
            array( 'user_id' => $user_id, 'novel_id' => $novel_id ),
            array( '%d', '%f' ),
            array( '%d', '%d' )
        );
    } else {
        $wpdb->insert(
            $table,
            array(
                'user_id'         => $user_id,
                'novel_id'        => $novel_id,
                'chapter_id'      => $chapter_id,
                'scroll_position' => $scroll_position,
            ),
            array( '%d', '%d', '%d', '%f' )
        );
    }

    wp_send_json_success( array( 'saved' => true ) );
}
add_action( 'wp_ajax_fn_save_reading_progress', 'fn_ajax_save_reading_progress' );

/**
 * دریافت پیشرفت خواندن کاربر
 */
function fn_get_reading_progress( $user_id, $novel_id ) {
    global $wpdb;
    return $wpdb->get_row( $wpdb->prepare(
        "SELECT chapter_id, scroll_position, last_read
         FROM {$wpdb->prefix}fn_reading_progress
         WHERE user_id = %d AND novel_id = %d",
        $user_id, $novel_id
    ) );
}

/**
 * دریافت تاریخچه خواندن کاربر
 */
function fn_get_reading_history( $user_id, $per_page = 20, $page = 1 ) {
    global $wpdb;
    $table  = $wpdb->prefix . 'fn_reading_progress';
    $offset = ( $page - 1 ) * $per_page;

    $results = $wpdb->get_results( $wpdb->prepare(
        "SELECT novel_id, chapter_id, scroll_position, last_read
         FROM $table
         WHERE user_id = %d
         ORDER BY last_read DESC
         LIMIT %d OFFSET %d",
        $user_id, $per_page, $offset
    ) );

    $total = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE user_id = %d", $user_id
    ) );

    return array(
        'items' => $results,
        'total' => absint( $total ),
        'pages' => ceil( $total / $per_page ),
    );
}