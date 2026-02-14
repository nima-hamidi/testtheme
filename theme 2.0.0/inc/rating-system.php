<?php
/**
 * سیستم امتیازدهی ستاره‌ای
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * هندلر AJAX ثبت امتیاز
 */
function fn_ajax_rate_novel() {
    // بررسی نانس
    check_ajax_referer( 'flavor_novel_nonce', 'nonce' );

    // بررسی لاگین
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'لطفاً ابتدا وارد شوید.' ) );
    }

    $user_id  = get_current_user_id();
    $novel_id = absint( $_POST['novel_id'] ?? 0 );
    $rating   = absint( $_POST['rating'] ?? 0 );

    // اعتبارسنجی
    if ( ! $novel_id || $rating < 1 || $rating > 5 ) {
        wp_send_json_error( array( 'message' => 'داده نامعتبر.' ) );
    }

    if ( get_post_type( $novel_id ) !== 'novel' ) {
        wp_send_json_error( array( 'message' => 'رمان یافت نشد.' ) );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'fn_ratings';

    // بررسی امتیاز قبلی
    $existing = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM $table WHERE user_id = %d AND novel_id = %d",
        $user_id, $novel_id
    ) );

    if ( $existing ) {
        // بروزرسانی
        $wpdb->update(
            $table,
            array( 'rating' => $rating ),
            array( 'user_id' => $user_id, 'novel_id' => $novel_id ),
            array( '%d' ),
            array( '%d', '%d' )
        );
    } else {
        // درج جدید
        $wpdb->insert(
            $table,
            array(
                'user_id'  => $user_id,
                'novel_id' => $novel_id,
                'rating'   => $rating,
            ),
            array( '%d', '%d', '%d' )
        );
    }

    // محاسبه میانگین جدید
    $avg = $wpdb->get_var( $wpdb->prepare(
        "SELECT AVG(rating) FROM $table WHERE novel_id = %d", $novel_id
    ) );
    $count = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE novel_id = %d", $novel_id
    ) );

    $avg = round( floatval( $avg ), 2 );

    // ذخیره در meta رمان
    update_post_meta( $novel_id, '_fn_avg_rating', $avg );
    update_post_meta( $novel_id, '_fn_rating_count', absint( $count ) );

    wp_send_json_success( array(
        'avg_rating'   => $avg,
        'rating_count' => absint( $count ),
        'user_rating'  => $rating,
    ) );
}
add_action( 'wp_ajax_fn_rate_novel', 'fn_ajax_rate_novel' );