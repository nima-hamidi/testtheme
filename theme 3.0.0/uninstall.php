<?php
/**
 * فایل: uninstall.php
 * توضیح: حذف کامل داده‌های قالب هنگام uninstall
 * نسخه: 2.0.0
 * ⚠️ فقط وقتی اجرا می‌شود که قالب از طریق وردپرس حذف شود
 */

// بررسی امنیتی
if (!defined('WP_UNINSTALL_PLUGIN') && !defined('ABSPATH')) {
    exit;
}

// فقط ادمین
if (!current_user_can('manage_options')) {
    exit;
}

global $wpdb;

// ═══ ۱. حذف تمام جداول ═══
$tables = [
    'comment_votes', 'comment_reactions', 'review_helpfulness',
    'chapter_votes', 'reports', 'notifications', 'user_follows',
    'novel_follows', 'user_library', 'reading_history', 'user_coins',
    'chapter_purchases', 'author_earnings', 'author_payouts',
    'polls', 'poll_options', 'poll_votes', 'user_achievements',
    'author_reviews', 'novel_views', 'quizzes', 'quiz_questions',
    'quiz_attempts', 'quiz_answers', 'author_banners',
];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
}

// ═══ ۲. حذف options ═══
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'novel_%'");

// ═══ ۳. حذف user_meta ═══
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'novel_%'");

// ═══ ۴. حذف transients ═══
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_novel_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_novel_%'");

// ═══ ۵. حذف cron events ═══
wp_clear_scheduled_hook('novel_daily_cron');
wp_clear_scheduled_hook('novel_hourly_cron');

// ═══ ۶. پاکسازی rewrite rules ═══
flush_rewrite_rules();