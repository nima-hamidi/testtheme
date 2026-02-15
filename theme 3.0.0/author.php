<?php
/**
 * Author Profile Template
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$author    = get_queried_object();
$author_id = $author->ID;
$stats     = Novel_Authors::get_author_stats($author_id);
$is_logged = is_user_logged_in();
$current   = $is_logged ? get_current_user_id() : 0;
$is_following = $is_logged ? Novel_Follow::is_following_user($current, $author_id) : false;

// Profile data
$bio          = get_user_meta($author_id, 'description', true);
$announcement = get_user_meta($author_id, 'novel_announcement', true);
$telegram     = get_user_meta($author_id, 'novel_telegram', true);
$instagram    = get_user_meta($author_id, 'novel_instagram', true);
$profile_color = get_user_meta($author_id, 'novel_profile_color', true) ?: '#6366f1';
$registered   = date_i18n('F Y', strtotime($author->user_registered));

// Badges
$badges = [];
if (user_can($author_id, 'manage_options')) {
    $badges[] = ['icon' => '👑', 'label' => 'مدیر', 'class' => 'badge-admin'];
} elseif ($stats['novels'] > 0) {
    $badges[] = ['icon' => '✍️', 'label' => 'نویسنده', 'class' => 'badge-author'];
}

// Active tab
$tab = sanitize_text_field($_GET['tab'] ?? 'novels');
?>

<main class="author-profile-page">

    <!-- ═══ هدر پروفایل ═══ -->
    <section class="author-hero" style="--profile-color: <?php echo esc_attr($profile_color); ?>;">
        <div class="author-hero__banner"></div>
        <div class="container">
            <div class="author-hero__content">
                <div class="author-hero__avatar">
                    <?php echo get_avatar($author_id, 120, '', '', ['class' => 'author-avatar-img']); ?>
                    <?php echo Novel_Authors::render_online_dot($author_id); ?>
                </div>

                <div class="author-hero__info">
                    <h1 class="author-name">
                        <?php echo esc_html($author->display_name); ?>
                        <?php foreach ($badges as $b) : ?>
                            <span class="author-badge <?php echo esc_attr($b['class']); ?>">
                                <?php echo $b['icon'] . ' ' . esc_html($b['label']); ?>
                            </span>
                        <?php endforeach; ?>
                    </h1>

                    <?php if ($bio) : ?>
                        <p class="author-bio"><?php echo esc_html($bio); ?></p>
                    <?php endif; ?>

                    <div class="author-meta-line">
                        <span>📅 عضو از <?php echo esc_html($registered); ?></span>
                        <?php if ($telegram) : ?>
                            <a href="https://t.me/<?php echo esc_attr(ltrim($telegram, '@')); ?>" target="_blank" rel="noopener">
                                📱 <?php echo esc_html($telegram); ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($instagram) : ?>
                            <a href="https://instagram.com/<?php echo esc_attr(ltrim($instagram, '@')); ?>" target="_blank" rel="noopener">
                                📸 <?php echo esc_html($instagram); ?>
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Stats boxes -->
                    <div class="author-stats-grid">
                        <div class="stat-box"><span class="stat-num"><?php echo number_format_i18n($stats['novels']); ?></span><span class="stat-lbl">📖 رمان</span></div>
                        <div class="stat-box"><span class="stat-num"><?php echo number_format_i18n($stats['chapters']); ?></span><span class="stat-lbl">📄 قسمت</span></div>
                        <div class="stat-box stat-clickable" data-modal="followers"><span class="stat-num"><?php echo number_format_i18n($stats['followers']); ?></span><span class="stat-lbl">❤ فالوور</span></div>
                        <div class="stat-box"><span class="stat-num"><?php echo number_format_i18n($stats['views']); ?></span><span class="stat-lbl">👁 بازدید</span></div>
                    </div>

                    <div class="author-actions">
                        <?php if ($is_logged && $current !== $author_id) : ?>
                            <button class="btn-follow-user btn-follow-lg <?php echo $is_following ? 'following' : ''; ?>"
                                    data-user-id="<?php echo $author_id; ?>">
                                <span class="follow-icon"><?php echo $is_following ? '❤' : '🤍'; ?></span>
                                <span class="follow-text"><?php echo $is_following ? 'دنبال شده ✓' : 'دنبال کردن'; ?></span>
                                <span class="follow-count">(<?php echo number_format_i18n($stats['followers']); ?>)</span>
                            </button>

                            <button class="btn-report-user" data-user-id="<?php echo $author_id; ?>">🚩 گزارش</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($announcement) : ?>
                <div class="author-announcement">
                    <span class="announcement-icon">📢</span>
                    <p><?php echo esc_html($announcement); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ═══ تب‌ها ═══ -->
    <div class="container">
        <nav class="author-tabs">
            <a href="?tab=novels" class="author-tab <?php echo $tab === 'novels' ? 'active' : ''; ?>">📖 رمان‌ها</a>
            <a href="?tab=reviews" class="author-tab <?php echo $tab === 'reviews' ? 'active' : ''; ?>">💬 نظرات</a>
            <a href="?tab=activity" class="author-tab <?php echo $tab === 'activity' ? 'active' : ''; ?>">📝 فعالیت</a>
            <a href="?tab=achievements" class="author-tab <?php echo $tab === 'achievements' ? 'active' : ''; ?>">🏆 دستاوردها</a>
        </nav>

        <div class="author-tab-content">
            <?php
            switch ($tab) {
                case 'reviews':
                    get_template_part('templates/authors/author-profile', 'reviews');
                    break;
                case 'activity':
                    get_template_part('templates/authors/author-profile', 'activity');
                    break;
                case 'achievements':
                    get_template_part('templates/authors/author-profile', 'achievements');
                    break;
                case 'novels':
                default:
                    // Author's novels
                    $novels = new WP_Query([
                        'post_type'      => 'novel',
                        'author'         => $author_id,
                        'posts_per_page' => 12,
                        'post_status'    => 'publish',
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                    ]);
                    if ($novels->have_posts()) :
                        echo '<div class="author-novels-grid">';
                        while ($novels->have_posts()) : $novels->the_post();
                            get_template_part('templates/novel/novel-card');
                        endwhile;
                        wp_reset_postdata();
                        echo '</div>';
                    else :
                        echo '<div class="tab-empty"><p>هنوز رمانی منتشر نشده.</p></div>';
                    endif;
                    break;
            }
            ?>
        </div>
    </div>

    <!-- ═══ Report Modal ═══ -->
    <div class="report-user-modal" id="reportUserModal" style="display:none;">
        <div class="report-modal__overlay"></div>
        <div class="report-modal__content">
            <div class="report-modal__header">
                <h3>🚩 گزارش کاربر</h3>
                <button class="report-modal__close">×</button>
            </div>
            <div class="report-modal__body">
                <div class="report-options">
                    <label class="report-option"><input type="radio" name="user_report_reason" value="behavior" checked /> رفتار نامناسب</label>
                    <label class="report-option"><input type="radio" name="user_report_reason" value="spam" /> اسپم</label>
                    <label class="report-option"><input type="radio" name="user_report_reason" value="fake_identity" /> هویت جعلی</label>
                    <label class="report-option"><input type="radio" name="user_report_reason" value="inappropriate" /> محتوای نامناسب</label>
                    <label class="report-option"><input type="radio" name="user_report_reason" value="other" /> سایر</label>
                </div>
                <textarea id="userReportDesc" placeholder="توضیحات..." rows="3" maxlength="500"></textarea>
            </div>
            <div class="report-modal__footer">
                <button class="btn-submit-user-report">ارسال گزارش</button>
                <button class="btn-cancel-user-report">انصراف</button>
            </div>
        </div>
    </div>

    <!-- ═══ Followers Modal ═══ -->
    <div class="followers-modal" id="followersModal" style="display:none;">
        <div class="report-modal__overlay"></div>
        <div class="report-modal__content followers-modal-content">
            <div class="report-modal__header">
                <h3>دنبال‌کنندگان</h3>
                <button class="report-modal__close">×</button>
            </div>
            <div class="followers-modal-body" id="followersModalBody">
                <div class="loading-spinner">بارگذاری...</div>
            </div>
        </div>
    </div>

</main>

<?php get_footer(); ?>