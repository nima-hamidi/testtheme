<?php
/**
 * Dashboard Overview Tab
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

$user_id = get_current_user_id();
$user = get_userdata($user_id);
$avatar_url = Novel_Avatars::get_avatar_url_static($user_id);

// Quick stats
global $wpdb;

$novels_read = 0;
$lib_table = $wpdb->prefix . 'user_library';
if ($wpdb->get_var("SHOW TABLES LIKE '$lib_table'") === $lib_table) {
    $novels_read = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $lib_table WHERE user_id = %d", $user_id
    ));
}

$total_comments = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->comments} WHERE user_id = %d AND comment_approved = '1'", $user_id
));

$coins = 0;
$coins_table = $wpdb->prefix . 'user_coins';
if ($wpdb->get_var("SHOW TABLES LIKE '$coins_table'") === $coins_table) {
    $coins = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(amount), 0) FROM $coins_table WHERE user_id = %d AND (expires_at IS NULL OR expires_at > NOW())",
        $user_id
    ));
}

$achievements = 0;
$ach_table = $wpdb->prefix . 'user_achievements';
if ($wpdb->get_var("SHOW TABLES LIKE '$ach_table'") === $ach_table) {
    $achievements = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $ach_table WHERE user_id = %d", $user_id
    ));
}

$followers = 0;
$follow_table = $wpdb->prefix . 'user_follows';
if ($wpdb->get_var("SHOW TABLES LIKE '$follow_table'") === $follow_table) {
    $followers = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $follow_table WHERE following_id = %d", $user_id
    ));
}

// Get hour for greeting
$hour = (int) date('G');
if ($hour < 12) {
    $greeting = 'صبح بخیر';
} elseif ($hour < 17) {
    $greeting = 'ظهر بخیر';
} else {
    $greeting = 'عصر بخیر';
}
?>

<div class="overview">
    <!-- Greeting -->
    <div class="overview__greeting">
        <h2><?php echo esc_html($greeting); ?>، <?php echo esc_html($user->display_name); ?>! 👋</h2>
    </div>

    <!-- Continue Reading -->
    <div class="overview__section">
        <h3 class="overview__section-title">
            <span>📖</span> ادامه مطالعه
        </h3>
        <div class="overview__continue-grid" id="continueReading">
            <?php
            // Try to get reading history
            $history_table = $wpdb->prefix . 'reading_history';
            $has_history = $wpdb->get_var("SHOW TABLES LIKE '$history_table'") === $history_table;

            if ($has_history) {
                $recent = $wpdb->get_results($wpdb->prepare(
                    "SELECT DISTINCT novel_id, chapter_id, read_at 
                     FROM $history_table 
                     WHERE user_id = %d 
                     ORDER BY read_at DESC 
                     LIMIT 3",
                    $user_id
                ));

                if (!empty($recent)) {
                    foreach ($recent as $item) {
                        $novel = get_post($item->novel_id);
                        if (!$novel) continue;
                        $cover = get_the_post_thumbnail_url($novel->ID, 'medium') ?: get_template_directory_uri() . '/assets/images/no-cover.png';
                        $chapter = get_post($item->chapter_id);
                        $chapter_title = $chapter ? $chapter->post_title : '';
                        ?>
                        <a href="<?php echo get_permalink($item->chapter_id ?: $novel->ID); ?>" class="continue-card">
                            <img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr($novel->post_title); ?>" class="continue-card__cover">
                            <div class="continue-card__info">
                                <h4 class="continue-card__title"><?php echo esc_html($novel->post_title); ?></h4>
                                <?php if ($chapter_title) : ?>
                                    <p class="continue-card__chapter"><?php echo esc_html($chapter_title); ?></p>
                                <?php endif; ?>
                                <span class="continue-card__btn">ادامه خواندن ←</span>
                            </div>
                        </a>
                        <?php
                    }
                } else {
                    echo '<p style="color:var(--color-text-muted);font-size:14px;">هنوز رمانی نخوانده‌اید. <a href="' . home_url('/') . '" style="color:var(--color-primary);">کاوش کنید!</a></p>';
                }
            } else {
                echo '<p style="color:var(--color-text-muted);font-size:14px;">هنوز رمانی نخوانده‌اید. <a href="' . home_url('/') . '" style="color:var(--color-primary);">کاوش کنید!</a></p>';
            }
            ?>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="overview__section">
        <h3 class="overview__section-title">
            <span>📊</span> آمار سریع
        </h3>
        <div class="overview__stats">
            <div class="overview__stat-card">
                <div class="overview__stat-icon">📖</div>
                <div class="overview__stat-value"><?php echo number_format_i18n($novels_read); ?></div>
                <div class="overview__stat-label">خوانده</div>
            </div>
            <div class="overview__stat-card">
                <div class="overview__stat-icon">💬</div>
                <div class="overview__stat-value"><?php echo number_format_i18n($total_comments); ?></div>
                <div class="overview__stat-label">دیدگاه</div>
            </div>
            <div class="overview__stat-card">
                <div class="overview__stat-icon">🪙</div>
                <div class="overview__stat-value"><?php echo number_format_i18n($coins); ?></div>
                <div class="overview__stat-label">سکه</div>
            </div>
            <div class="overview__stat-card">
                <div class="overview__stat-icon">🏆</div>
                <div class="overview__stat-value"><?php echo number_format_i18n($achievements); ?></div>
                <div class="overview__stat-label">مدال</div>
            </div>
            <div class="overview__stat-card">
                <div class="overview__stat-icon">❤</div>
                <div class="overview__stat-value"><?php echo number_format_i18n($followers); ?></div>
                <div class="overview__stat-label">فالوور</div>
            </div>
            <div class="overview__stat-card">
                <div class="overview__stat-icon">📊</div>
                <div class="overview__stat-value">-</div>
                <div class="overview__stat-label">بازدید</div>
            </div>
        </div>
    </div>

    <!-- Recent Notifications -->
    <div class="overview__section">
        <h3 class="overview__section-title">
            <span>🔔</span> اعلان‌های اخیر
        </h3>
        <div id="recentNotifications">
            <?php
            $notif_table = $wpdb->prefix . 'notifications';
            $has_notifs = $wpdb->get_var("SHOW TABLES LIKE '$notif_table'") === $notif_table;

            if ($has_notifs) {
                $notifs = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $notif_table WHERE user_id = %d ORDER BY created_at DESC LIMIT 3",
                    $user_id
                ));

                if (!empty($notifs)) {
                    echo '<div class="overview__notif-list">';
                    foreach ($notifs as $notif) {
                        $is_unread = !$notif->is_read;
                        echo '<div class="overview__notif-item ' . ($is_unread ? 'is-unread' : '') . '">';
                        echo '<span class="overview__notif-text">' . esc_html($notif->message) . '</span>';
                        echo '<span class="overview__notif-time">' . esc_html(human_time_diff(strtotime($notif->created_at))) . ' پیش</span>';
                        echo '</div>';
                    }
                    echo '</div>';
                    echo '<a href="?tab=notifications" class="overview__see-all" data-tab="notifications">مشاهده همه اعلان‌ها ←</a>';
                } else {
                    echo '<p style="color:var(--color-text-muted);font-size:14px;">اعلان جدیدی ندارید.</p>';
                }
            } else {
                echo '<p style="color:var(--color-text-muted);font-size:14px;">اعلان جدیدی ندارید.</p>';
            }
            ?>
        </div>
    </div>
</div>

<style>
/* Continue Card */
.continue-card {
    display: flex;
    gap: 14px;
    padding: 14px;
    background: var(--color-bg-secondary, #f8f9fa);
    border-radius: 14px;
    text-decoration: none;
    transition: all 0.2s;
    border: 1px solid var(--color-border-light, #f1f5f9);
}
[data-theme="dark"] .continue-card {
    background: rgba(255,255,255,0.04);
    border-color: rgba(255,255,255,0.06);
}
.continue-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.continue-card__cover {
    width: 60px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    flex-shrink: 0;
}
.continue-card__info {
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-width: 0;
}
.continue-card__title {
    font-size: 14px;
    font-weight: 700;
    color: var(--color-text-primary, #1a1a2e);
    margin: 0 0 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
[data-theme="dark"] .continue-card__title { color: #f1f1f1; }
.continue-card__chapter {
    font-size: 12px;
    color: var(--color-text-muted, #9ca3af);
    margin: 0 0 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.continue-card__btn {
    font-size: 12px;
    color: var(--color-primary, #6366f1);
    font-weight: 600;
}

/* Notification items */
.overview__notif-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.overview__notif-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background: var(--color-bg-secondary, #f8f9fa);
    border-radius: 10px;
    font-size: 13px;
    border-right: 3px solid transparent;
}
[data-theme="dark"] .overview__notif-item {
    background: rgba(255,255,255,0.04);
}
.overview__notif-item.is-unread {
    border-right-color: var(--color-primary, #6366f1);
    background: rgba(99,102,241,0.04);
}
.overview__notif-text {
    color: var(--color-text-primary, #374151);
    flex: 1;
    margin-left: 12px;
}
[data-theme="dark"] .overview__notif-text { color: #d1d5db; }
.overview__notif-time {
    color: var(--color-text-muted, #9ca3af);
    font-size: 11px;
    white-space: nowrap;
}
.overview__see-all {
    display: inline-block;
    margin-top: 12px;
    font-size: 13px;
    color: var(--color-primary, #6366f1);
    text-decoration: none;
    font-weight: 600;
}
.overview__see-all:hover {
    text-decoration: underline;
}
</style>