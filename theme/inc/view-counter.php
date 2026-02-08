<?php
/**
 * سیستم شمارش بازدید
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * ثبت بازدید
 */
function fn_count_view_handler( $post_id ) {
    if ( ! $post_id ) return;

    // بررسی ربات و ادمین
    if ( is_admin() ) return;
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) return;
    if ( defined( 'DOING_CRON' ) && DOING_CRON ) return;

    // بررسی ربات‌ها
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if ( empty( $user_agent ) || preg_match( '/bot|crawl|spider|slurp|mediapartners/i', $user_agent ) ) {
        return;
    }

    // جلوگیری از شمارش تکراری با سشن (ساده)
    $cookie_key = 'fn_viewed_' . $post_id;
    if ( isset( $_COOKIE[ $cookie_key ] ) ) return;

    // ثبت کوکی (۱ ساعت)
    setcookie( $cookie_key, '1', time() + 3600, '/' );

    global $wpdb;
    $table = $wpdb->prefix . 'fn_views';
    $today = current_time( 'Y-m-d' );

    // Upsert: اگر امروز رکورد داره +1 وگرنه درج جدید
    $wpdb->query( $wpdb->prepare(
        "INSERT INTO $table (post_id, view_date, view_count) VALUES (%d, %s, 1)
         ON DUPLICATE KEY UPDATE view_count = view_count + 1",
        $post_id, $today
    ) );

    // بروزرسانی کل بازدیدها
    $total = $wpdb->get_var( $wpdb->prepare(
        "SELECT SUM(view_count) FROM $table WHERE post_id = %d", $post_id
    ) );
    update_post_meta( $post_id, '_fn_total_views', absint( $total ) );

    // بروزرسانی بازدید هفتگی
    $week_ago = date( 'Y-m-d', strtotime( '-7 days' ) );
    $weekly = $wpdb->get_var( $wpdb->prepare(
        "SELECT SUM(view_count) FROM $table WHERE post_id = %d AND view_date >= %s",
        $post_id, $week_ago
    ) );
    update_post_meta( $post_id, '_fn_weekly_views', absint( $weekly ) );

    // بروزرسانی بازدید ماهانه
    $month_ago = date( 'Y-m-d', strtotime( '-30 days' ) );
    $monthly = $wpdb->get_var( $wpdb->prepare(
        "SELECT SUM(view_count) FROM $table WHERE post_id = %d AND view_date >= %s",
        $post_id, $month_ago
    ) );
    update_post_meta( $post_id, '_fn_monthly_views', absint( $monthly ) );

    // اگر فصل بود، بازدید رمان والد را هم بروزرسانی کن
    if ( get_post_type( $post_id ) === 'chapter' ) {
        $novel_id = get_post_meta( $post_id, '_fn_parent_novel', true );
        if ( $novel_id ) {
            $novel_total = absint( get_post_meta( $novel_id, '_fn_total_views', true ) ) + 1;
            update_post_meta( $novel_id, '_fn_total_views', $novel_total );
        }
    }
}
add_action( 'fn_count_view', 'fn_count_view_handler' );

/**
 * پاکسازی بازدیدهای قدیمی (Cron Job)
 */
function fn_cleanup_old_views() {
    global $wpdb;
    $table = $wpdb->prefix . 'fn_views';
    $old_date = date( 'Y-m-d', strtotime( '-90 days' ) );

    $wpdb->query( $wpdb->prepare(
        "DELETE FROM $table WHERE view_date < %s", $old_date
    ) );
}

// ثبت Cron Job
if ( ! wp_next_scheduled( 'fn_daily_cleanup' ) ) {
    wp_schedule_event( time(), 'daily', 'fn_daily_cleanup' );
}
add_action( 'fn_daily_cleanup', 'fn_cleanup_old_views' );