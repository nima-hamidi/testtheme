<?php
/**
 * سیستم بوکمارک (کتابخانه شخصی)
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * هندلر AJAX تاگل بوکمارک
 */
function fn_ajax_toggle_bookmark() {
    check_ajax_referer( 'flavor_novel_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'لطفاً ابتدا وارد شوید.' ) );
    }

    $user_id  = get_current_user_id();
    $novel_id = absint( $_POST['novel_id'] ?? 0 );

    if ( ! $novel_id || get_post_type( $novel_id ) !== 'novel' ) {
        wp_send_json_error( array( 'message' => 'رمان نامعتبر.' ) );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'fn_bookmarks';

    // بررسی وجود بوکمارک
    $existing = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM $table WHERE user_id = %d AND novel_id = %d",
        $user_id, $novel_id
    ) );

    if ( $existing ) {
        // حذف
        $wpdb->delete( $table, array(
            'user_id'  => $user_id,
            'novel_id' => $novel_id,
        ), array( '%d', '%d' ) );

        $action = 'removed';
    } else {
        // افزودن
        $wpdb->insert( $table, array(
            'user_id'  => $user_id,
            'novel_id' => $novel_id,
        ), array( '%d', '%d' ) );

        $action = 'added';
    }

    // بروزرسانی تعداد بوکمارک‌ها
    $count = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE novel_id = %d", $novel_id
    ) );
    update_post_meta( $novel_id, '_fn_bookmark_count', absint( $count ) );

    wp_send_json_success( array(
        'action'         => $action,
        'bookmark_count' => absint( $count ),
    ) );
}
add_action( 'wp_ajax_fn_toggle_bookmark', 'fn_ajax_toggle_bookmark' );

/**
 * دریافت لیست بوکمارک‌های کاربر
 */
function fn_get_user_bookmarks( $user_id, $per_page = 20, $page = 1 ) {
    global $wpdb;
    $table  = $wpdb->prefix . 'fn_bookmarks';
    $offset = ( $page - 1 ) * $per_page;

    $novel_ids = $wpdb->get_col( $wpdb->prepare(
        "SELECT novel_id FROM $table WHERE user_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $user_id, $per_page, $offset
    ) );

    $total = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE user_id = %d", $user_id
    ) );

    return array(
        'novel_ids' => $novel_ids,
        'total'     => absint( $total ),
        'pages'     => ceil( $total / $per_page ),
    );
}

/**
 * بررسی بوکمارک بودن
 */
function fn_is_bookmarked( $novel_id, $user_id = null ) {
    if ( ! $user_id ) $user_id = get_current_user_id();
    if ( ! $user_id ) return false;

    global $wpdb;
    return (bool) $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}fn_bookmarks WHERE user_id = %d AND novel_id = %d",
        $user_id, $novel_id
    ) );
}