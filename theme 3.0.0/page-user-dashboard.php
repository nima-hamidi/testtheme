<?php
/**
 * Template Name: داشبورد کاربر
 *
 * Main user dashboard page with tab navigation
 *
 * @package suspended-starter
 * @since 3.0.0
 */

// Require login
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/?redirect_to=' . urlencode($_SERVER['REQUEST_URI'])));
    exit;
}

get_header();

$user_id = get_current_user_id();
$user = get_userdata($user_id);
$avatar_url = Novel_Avatars::get_avatar_url_static($user_id);
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';

// Check if user is an author (has novels)
$is_author = (bool) get_posts([
    'post_type'   => 'novel',
    'author'      => $user_id,
    'numberposts' => 1,
    'post_status' => 'any',
    'fields'      => 'ids',
]);

// Unread notifications count
$unread_notifs = 0;
global $wpdb;
$notif_table = $wpdb->prefix . 'notifications';
if ($wpdb->get_var("SHOW TABLES LIKE '$notif_table'") === $notif_table) {
    $unread_notifs = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $notif_table WHERE user_id = %d AND is_read = 0",
        $user_id
    ));
}

// Pending comments count
$pending_comments = 0;

// Email verification check
$is_verified = Novel_Auth::is_email_verified($user_id);

// User role display
$role_labels = [
    'administrator' => 'مدیر',
    'editor'        => 'ویرایشگر',
    'author'        => 'نویسنده',
    'contributor'   => 'مشارکت‌کننده',
    'subscriber'    => 'کاربر',
];
$user_role = !empty($user->roles) ? $user->roles[0] : 'subscriber';
$role_label = $role_labels[$user_role] ?? 'کاربر';

// Nav items structure
$nav_groups = [
    'general' => [
        'label' => 'عمومی',
        'items' => [
            'overview' => ['icon' => '📊', 'label' => 'خلاصه'],
            'profile'  => ['icon' => '👤', 'label' => 'ویرایش پروفایل'],
            'settings' => ['icon' => '⚙️', 'label' => 'تنظیمات'],
        ],
    ],
    'reading' => [
        'label' => 'مطالعه',
        'items' => [
            'library'   => ['icon' => '📚', 'label' => 'کتابخانه'],
            'history'   => ['icon' => '📜', 'label' => 'تاریخچه'],
            'following' => ['icon' => '❤', 'label' => 'دنبال‌شده‌ها'],
        ],
    ],
    'social' => [
        'label' => 'اجتماعی',
        'items' => [
            'comments'      => ['icon' => '💬', 'label' => 'دیدگاه‌های من', 'badge' => $pending_comments],
            'notifications' => ['icon' => '🔔', 'label' => 'اعلان‌ها', 'badge' => $unread_notifs],
            'followers'     => ['icon' => '👥', 'label' => 'فالوورها'],
            'achievements'  => ['icon' => '🏆', 'label' => 'دستاوردها'],
        ],
    ],
    'financial' => [
        'label' => 'مالی',
        'items' => [
            'coins'        => ['icon' => '🪙', 'label' => 'سکه‌ها'],
            'subscription' => ['icon' => '💎', 'label' => 'اشتراک'],
            'contests'     => ['icon' => '🎮', 'label' => 'مسابقات من'],
        ],
    ],
];

// Author nav group
if ($is_author) {
    $nav_groups['author'] = [
        'label' => 'نویسنده',
        'items' => [
            'my-novels'      => ['icon' => '📖', 'label' => 'رمان‌های من'],
            'my-chapters'    => ['icon' => '📄', 'label' => 'قسمت‌های من'],
            'author-stats'   => ['icon' => '📈', 'label' => 'آمار'],
            'author-income'  => ['icon' => '💰', 'label' => 'درآمد'],
            'author-banners' => ['icon' => '🖼', 'label' => 'بنرها'],
        ],
    ];
}
?>

<main class="dashboard">
    
    <!-- Mobile Navigation -->
    <nav class="dashboard__mobile-nav" aria-label="منوی داشبورد موبایل">
        <div class="dashboard__mobile-nav-inner">
            <?php foreach ($nav_groups as $group) : ?>
                <?php foreach ($group['items'] as $tab_key => $tab_data) : ?>
                    <button
                        type="button"
                        class="dashboard__mobile-tab <?php echo ($tab_key === $current_tab) ? 'is-active' : ''; ?>"
                        data-tab="<?php echo esc_attr($tab_key); ?>"
                    >
                        <span><?php echo $tab_data['icon']; ?></span>
                        <span><?php echo esc_html($tab_data['label']); ?></span>
                        <?php if (!empty($tab_data['badge'])) : ?>
                            <span class="dashboard__nav-badge"><?php echo intval($tab_data['badge']); ?></span>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </nav>

    <div class="dashboard__container">
        
        <!-- Sidebar -->
        <aside class="dashboard__sidebar">
            <!-- User Info -->
            <div class="dashboard__user-info">
                <img
                    src="<?php echo esc_url($avatar_url); ?>"
                    alt="<?php echo esc_attr($user->display_name); ?>"
                    class="dashboard__user-avatar novel-avatar-dynamic header-user-avatar"
                    width="72"
                    height="72"
                >
                <h3 class="dashboard__user-name"><?php echo esc_html($user->display_name); ?></h3>
                <div class="dashboard__user-role">
                    <span><?php echo esc_html($role_label); ?></span>
                    <?php if (!$is_verified) : ?>
                        <span style="color:var(--color-warning);" title="ایمیل تأیید نشده">⚠️</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="dashboard__nav" aria-label="منوی داشبورد">
                <?php foreach ($nav_groups as $group_key => $group) : ?>
                    <div class="dashboard__nav-group">
                        <div class="dashboard__nav-label"><?php echo esc_html($group['label']); ?></div>
                        <?php foreach ($group['items'] as $tab_key => $tab_data) : ?>
                            <button
                                type="button"
                                class="dashboard__nav-item <?php echo ($tab_key === $current_tab) ? 'is-active' : ''; ?>"
                                data-tab="<?php echo esc_attr($tab_key); ?>"
                            >
                                <span class="dashboard__nav-icon"><?php echo $tab_data['icon']; ?></span>
                                <span><?php echo esc_html($tab_data['label']); ?></span>
                                <?php if (!empty($tab_data['badge'])) : ?>
                                    <span class="dashboard__nav-badge"><?php echo intval($tab_data['badge']); ?></span>
                                <?php endif; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

                <!-- Logout -->
                <div class="dashboard__nav-group" style="padding-top:8px;">
                    <a href="<?php echo wp_logout_url(home_url('/')); ?>" class="dashboard__nav-item" style="color:var(--color-danger);">
                        <span class="dashboard__nav-icon">🚪</span>
                        <span>خروج</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="dashboard__main">
            <div class="dashboard__content">
                <?php
                // Load initial tab content (server-side for first load)
                $tab_templates = [
                    'overview' => 'templates/dashboard/overview.php',
                    'profile'  => 'templates/dashboard/profile-edit.php',
                ];

                $template_file = isset($tab_templates[$current_tab]) ? $tab_templates[$current_tab] : 'templates/dashboard/overview.php';
                $template_path = get_template_directory() . '/' . $template_file;

                if (file_exists($template_path)) {
                    include $template_path;
                } else {
                    echo '<div class="dashboard__error"><p>این بخش به‌زودی فعال می‌شود.</p></div>';
                }
                ?>
            </div>
        </div>
    </div>
</main>

<?php get_footer(); ?>