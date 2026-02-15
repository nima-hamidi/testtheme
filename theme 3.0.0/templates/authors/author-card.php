<?php
/**
 * Author Card Template
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

$user = $GLOBALS['author_card_user'] ?? null;
if (!$user) return;

$uid   = $user->ID;
$stats = Novel_Authors::get_author_stats($uid);
$is_following = is_user_logged_in() ? Novel_Follow::is_following_user(get_current_user_id(), $uid) : false;

$badges = [];
if (user_can($uid, 'manage_options')) {
    $badges[] = '👑 مدیر';
} elseif ($stats['novels'] > 0) {
    $badges[] = '✍️ نویسنده';
}
?>

<div class="author-card" data-user-id="<?php echo $uid; ?>">
    <div class="author-card__avatar">
        <a href="<?php echo get_author_posts_url($uid); ?>">
            <?php echo get_avatar($uid, 80, '', '', ['class' => 'author-card__img']); ?>
        </a>
        <?php echo Novel_Authors::render_online_dot($uid); ?>
    </div>

    <div class="author-card__body">
        <h3 class="author-card__name">
            <a href="<?php echo get_author_posts_url($uid); ?>">
                <?php echo esc_html($user->display_name); ?>
            </a>
        </h3>

        <?php if (!empty($badges)) : ?>
            <div class="author-card__badges">
                <?php foreach ($badges as $b) : ?>
                    <span class="author-mini-badge"><?php echo $b; ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="author-card__stats">
            <span>📖 <?php echo number_format_i18n($stats['novels']); ?> رمان</span>
            <span>📄 <?php echo number_format_i18n($stats['chapters']); ?> قسمت</span>
            <span>★ <?php echo number_format($stats['avg_rating'], 1); ?> میانگین</span>
            <span>❤ <?php echo number_format_i18n($stats['followers']); ?> دنبال‌کننده</span>
            <span>👁 <?php echo number_format_i18n($stats['views']); ?> بازدید</span>
            <span>📅 <?php echo date_i18n('F Y', strtotime($user->user_registered)); ?></span>
        </div>

        <div class="author-card__actions">
            <?php if (is_user_logged_in() && get_current_user_id() !== $uid) : ?>
                <button class="btn-follow-user <?php echo $is_following ? 'following' : ''; ?>"
                        data-user-id="<?php echo $uid; ?>">
                    <?php echo $is_following ? '❤ دنبال شده' : '🤍 دنبال کردن'; ?>
                </button>
            <?php endif; ?>
            <a href="<?php echo get_author_posts_url($uid); ?>" class="btn-view-profile">مشاهده پروفایل</a>
        </div>
    </div>
</div>