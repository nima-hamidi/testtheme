<?php
/**
 * Chapter List Item Template
 * Used in single-novel.php chapter listing
 * 
 * Expects: $GLOBALS['chapter_item_id'] = chapter post ID
 * 
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$ch_id        = isset($GLOBALS['chapter_item_id']) ? $GLOBALS['chapter_item_id'] : get_the_ID();
$ch_number    = get_post_meta($ch_id, 'chapter_number', true);
$ch_title     = get_post_meta($ch_id, 'chapter_title', true);
$ch_is_vip    = get_post_meta($ch_id, 'chapter_is_vip', true);
$ch_coin      = get_post_meta($ch_id, 'chapter_coin_price', true);
$ch_reading   = get_post_meta($ch_id, 'chapter_reading_time', true);
$ch_likes     = get_post_meta($ch_id, 'chapter_likes', true) ?: 0;
$ch_views     = get_post_meta($ch_id, 'chapter_views', true) ?: 0;
$ch_comments  = get_comments_number($ch_id);
$ch_date      = get_the_date('j F Y', $ch_id);
$ch_url       = novel_get_chapter_permalink($ch_id);

// Check if user has read this chapter
$is_read = false;
$has_access = true;

if (is_user_logged_in()) {
    global $wpdb;
    $user_id = get_current_user_id();
    
    // Check reading history
    $history_table = $wpdb->prefix . 'reading_history';
    if ($wpdb->get_var("SHOW TABLES LIKE '{$history_table}'") === $history_table) {
        $is_read = (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$history_table} WHERE user_id = %d AND chapter_id = %d",
            $user_id, $ch_id
        ));
    }
    
    // Check VIP access
    if ($ch_is_vip) {
        // Check subscription (RCP or custom)
        $has_subscription = apply_filters('novel_user_has_subscription', false, $user_id);
        
        // Check coin purchase
        $purchase_table = $wpdb->prefix . 'chapter_purchases';
        $has_purchased = false;
        if ($wpdb->get_var("SHOW TABLES LIKE '{$purchase_table}'") === $purchase_table) {
            $has_purchased = (bool) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$purchase_table} WHERE user_id = %d AND chapter_id = %d",
                $user_id, $ch_id
            ));
        }
        
        $has_access = $has_subscription || $has_purchased;
    }
}

$item_classes = ['chapter-list-item'];
if ($ch_is_vip) $item_classes[] = 'chapter-vip';
if ($is_read) $item_classes[] = 'chapter-read';
?>

<a href="<?php echo esc_url($ch_url); ?>" 
   class="<?php echo implode(' ', $item_classes); ?>"
   data-number="<?php echo esc_attr($ch_number); ?>"
   data-vip="<?php echo $ch_is_vip ? '1' : '0'; ?>">
    
    <div class="chapter-item__main">
        <span class="chapter-item__number"><?php echo esc_html($ch_number); ?>.</span>
        <span class="chapter-item__title">
            <?php 
            echo $ch_title ? esc_html($ch_title) : 'قسمت ' . esc_html($ch_number);
            ?>
        </span>
        
        <?php if ($is_read) : ?>
            <span class="chapter-item__read-badge" title="خوانده‌شده">✓</span>
        <?php endif; ?>
        
        <span class="chapter-item__type">
            <?php if ($ch_is_vip) : ?>
                <?php if ($has_access) : ?>
                    <span class="vip-badge unlocked" title="دسترسی دارید">🔓 VIP</span>
                <?php else : ?>
                    <span class="vip-badge locked" title="<?php echo esc_attr($ch_coin); ?> سکه">🔒 VIP</span>
                <?php endif; ?>
            <?php else : ?>
                <span class="free-badge">✅</span>
            <?php endif; ?>
        </span>
        
        <span class="chapter-item__likes" title="لایک">👍 <?php echo number_format_i18n($ch_likes); ?></span>
    </div>
    
    <div class="chapter-item__meta">
        <?php if ($ch_reading) : ?>
            <span class="meta-reading-time">⏱ <?php echo esc_html($ch_reading); ?> دقیقه</span>
        <?php endif; ?>
        <span class="meta-date"><?php echo esc_html($ch_date); ?></span>
        <?php if ($ch_comments > 0) : ?>
            <span class="meta-comments">💬 <?php echo number_format_i18n($ch_comments); ?></span>
        <?php endif; ?>
        <span class="meta-views">👁 <?php echo number_format_i18n($ch_views); ?></span>
    </div>
</a>