<?php
/**
 * فایل: functions.php
 * توضیح: فایل اصلی توابع قالب ناول - لود ماژول‌ها، enqueue، تنظیمات پایه
 * نسخه: 2.0.0
 * وابستگی: class-novel-core.php, class-novel-settings.php
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// ═══════════════════════════════════════
// ثابت‌های قالب
// ═══════════════════════════════════════
define('NOVEL_VERSION', '2.0.0');
define('NOVEL_DB_VERSION', '2.0.0');
define('NOVEL_DIR', get_template_directory());
define('NOVEL_URI', get_template_directory_uri());
define('NOVEL_INC', NOVEL_DIR . '/inc/');
define('NOVEL_ASSETS', NOVEL_URI . '/assets/');
define('NOVEL_MIN_PHP', '7.4');
define('NOVEL_MIN_WP', '6.0');

// ═══════════════════════════════════════
// بررسی حداقل نیازمندی‌ها
// ═══════════════════════════════════════
if (version_compare(PHP_VERSION, NOVEL_MIN_PHP, '<')) {
    add_action('admin_notices', function () {
        printf(
            '<div class="notice notice-error"><p>قالب ناول نیاز به PHP نسخه %s یا بالاتر دارد. نسخه فعلی: %s</p></div>',
            esc_html(NOVEL_MIN_PHP),
            esc_html(PHP_VERSION)
        );
    });
    return;
}

// ═══════════════════════════════════════
// تابع لاگ اختصاصی
// ═══════════════════════════════════════
function novel_log($message, $level = 'info', $context = []) {
    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        $log = sprintf(
            '[Novel %s] [%s] %s%s',
            NOVEL_VERSION,
            strtoupper($level),
            $message,
            !empty($context) ? ' | Context: ' . wp_json_encode($context, JSON_UNESCAPED_UNICODE) : ''
        );
        error_log($log);
    }
}

// ═══════════════════════════════════════
// لود کلاس‌های هسته (همیشه لود می‌شوند)
// ═══════════════════════════════════════
require_once NOVEL_INC . 'class-novel-core.php';
require_once NOVEL_INC . 'class-novel-settings.php';
require_once NOVEL_INC . 'custom-post-types.php';
require_once NOVEL_INC . 'taxonomies.php';
require_once NOVEL_INC . 'meta-boxes.php';
require_once NOVEL_INC . 'breadcrumbs.php';
/*فاز 3*/
require_once NOVEL_INC . 'class-novel-volumes.php';
/*فاز4 ریتینگ*/
require_once get_template_directory() . '/inc/class-novel-ratings.php';
/*فاز 5*/
require_once get_template_directory() . '/inc/class-novel-authors.php';
require_once get_template_directory() . '/inc/class-novel-follow.php';
/*فاز 6 */
require_once get_template_directory() . '/inc/class-novel-notifications.php';
/*فاز 7*/
require_once get_template_directory() . '/inc/class-novel-reports.php';
Novel_Reports::get_instance();
/*فاز 8*/
// === Search System ===
require_once get_template_directory() . '/inc/class-novel-search.php';
Novel_Search::get_instance();
/*فاز 9*/
// === Bookmarks & Library System ===
require_once get_template_directory() . '/inc/class-novel-bookmarks.php';
Novel_Bookmarks::get_instance();
/*فاز 10*/
// === Rankings & View Counter ===
require_once get_template_directory() . '/inc/class-novel-rankings.php';
Novel_Rankings::get_instance();
/*فاز 11*/
// === Subscriptions ===
require_once get_template_directory() . '/inc/class-novel-subscriptions.php';
Novel_Subscriptions::get_instance();

// === Coins System ===
require_once get_template_directory() . '/inc/class-novel-coins.php';
Novel_Coins::get_instance();

// Admin
if (is_admin()) {
    require_once get_template_directory() . '/inc/admin/class-novel-admin-coins.php';
    Novel_Admin_Coins::get_instance();
}
// ═══════════════════════════════════════
// لود شرطی ماژول‌ها بر اساس تنظیمات
// ═══════════════════════════════════════
$novel_modules = [
    'auth'          => 'class-novel-auth.php',
    'avatars'       => 'class-novel-avatars.php',
    'comments'      => 'class-novel-comments.php',
    'ratings'       => 'class-novel-ratings.php',
    'notifications' => 'class-novel-notifications.php',
    'reports'       => 'class-novel-reports.php',
    'search'        => 'class-novel-search.php',
    'bookmarks'     => 'class-novel-bookmarks.php',
    'rankings'      => 'class-novel-rankings.php',
    'authors'       => 'class-novel-authors.php',
    'subscriptions' => 'class-novel-subscriptions.php',
    'coins'         => 'class-novel-coins.php',
    'polls'         => 'class-novel-polls.php',
    'achievements'  => 'class-novel-achievements.php',
    'seo'           => 'class-novel-seo.php',
    'follow'        => 'class-novel-follow.php',
    'stickers'      => 'class-novel-stickers.php',
    'volumes'       => 'class-novel-volumes.php',
    'quiz'          => 'class-novel-quiz.php',
    'author_banners' => 'class-novel-author-banners.php',
];

foreach ($novel_modules as $slug => $file) {
    $file_path = NOVEL_INC . $file;
    if (Novel_Settings::is_module_active($slug) && file_exists($file_path)) {
        require_once $file_path;
    }
}

/*فاز3*/
function novel_init_volumes() {
    new Novel_Volumes();
}
add_action('init', 'novel_init_volumes', 15);

/*فاز 4*/
// === Initialize ===
function novel_init_ratings() {
    new Novel_Ratings();
}
add_action('init', 'novel_init_ratings', 15);

/*فاز 5*/
function novel_init_authors() {
    new Novel_Authors();
}
add_action('init', 'novel_init_authors', 15);

function novel_init_follow() {
    new Novel_Follow();
}
add_action('init', 'novel_init_follow', 15);

/*فاز 6*/
function novel_init_notifications() {
    new Novel_Notifications();
}
add_action('init', 'novel_init_notifications', 12);

// ═══════════════════════════════════════
// لود فایل‌های ادمین
// ═══════════════════════════════════════
if (is_admin()) {
    $admin_files = [
        'admin/class-novel-admin-settings.php',
        'admin/class-novel-admin-reports.php',
        'admin/class-novel-admin-coins.php',
        'admin/class-novel-admin-polls.php',
        'admin/class-novel-admin-achievements.php',
        'admin/class-novel-admin-stickers.php',
        'admin/class-novel-admin-authors.php',
        'admin/class-novel-admin-dashboard.php',
        'admin/class-novel-admin-quiz.php',
        'admin/class-novel-admin-banners.php',
    ];
    foreach ($admin_files as $admin_file) {
        $admin_path = NOVEL_INC . $admin_file;
        if (file_exists($admin_path)) {
            require_once $admin_path;
        }
    }
    /*فاز 7*/
    if (class_exists('Novel_Admin_Reports')) {
    Novel_Admin_Reports::get_instance();
    }
}

// ═══════════════════════════════════════
// Enqueue Assets
// ═══════════════════════════════════════
add_action('wp_enqueue_scripts', 'novel_enqueue_assets');
function novel_enqueue_assets() {
    // ── فونت‌ها ──
    wp_enqueue_style(
        'novel-fonts',
        NOVEL_ASSETS . 'css/fonts.css',
        [],
        NOVEL_VERSION
    );

    // ── CSS اصلی ──
    wp_enqueue_style(
        'novel-main',
        NOVEL_ASSETS . 'css/main.css',
        ['novel-fonts'],
        NOVEL_VERSION
    );

    // ── CSS شرطی بر اساس صفحه ──
    $conditional_styles = [
        'is_singular-chapter'  => 'reader',
        'is_page_login'        => 'auth',
        'is_page_register'     => 'auth',
        'is_page_dashboard'    => 'dashboard',
        'is_search'            => 'search',
        'is_page_ranking'      => 'rankings',
        'is_page_authors'      => 'authors',
        'is_singular-post'     => 'blog',
        'is_404'               => 'errors',
    ];

    if (is_singular('chapter')) {
        wp_enqueue_style('novel-reader', NOVEL_ASSETS . 'css/reader.css', ['novel-main'], NOVEL_VERSION);
    }
    if (is_page_template() || is_page()) {
        $page_slug = get_post_field('post_name', get_the_ID());
        if (in_array($page_slug, ['login', 'register', 'forgot-password', 'reset-password', 'verify-email'])) {
            wp_enqueue_style('novel-auth', NOVEL_ASSETS . 'css/auth.css', ['novel-main'], NOVEL_VERSION);
        }
        if ($page_slug === 'dashboard' || strpos($page_slug, 'dashboard') !== false) {
            wp_enqueue_style('novel-dashboard', NOVEL_ASSETS . 'css/dashboard.css', ['novel-main'], NOVEL_VERSION);
        }
        if ($page_slug === 'ranking') {
            wp_enqueue_style('novel-rankings', NOVEL_ASSETS . 'css/rankings.css', ['novel-main'], NOVEL_VERSION);
        }
        if ($page_slug === 'authors') {
            wp_enqueue_style('novel-authors', NOVEL_ASSETS . 'css/authors.css', ['novel-main'], NOVEL_VERSION);
        }
    }
    if (is_search()) {
        wp_enqueue_style('novel-search', NOVEL_ASSETS . 'css/search.css', ['novel-main'], NOVEL_VERSION);
    }
    if (is_singular('post')) {
        wp_enqueue_style('novel-blog', NOVEL_ASSETS . 'css/blog.css', ['novel-main'], NOVEL_VERSION);
    }
    if (is_404()) {
        wp_enqueue_style('novel-errors', NOVEL_ASSETS . 'css/errors.css', ['novel-main'], NOVEL_VERSION);
    }

    // CSS دیدگاه‌ها - در صفحات رمان و قسمت
    if (is_singular('novel') || is_singular('chapter')) {
        wp_enqueue_style('novel-comments', NOVEL_ASSETS . 'css/comments.css', ['novel-main'], NOVEL_VERSION);
    }

    /*فاز 9*/
    // اضافه به بخش enqueue styles
        wp_enqueue_style(
            'novel-bookmark',
            get_template_directory_uri() . '/assets/css/bookmark.css',
            ['novel-main-style'],
            FLAVOR_VERSION
        );

    // ── JS اصلی ──
    wp_enqueue_script(
        'novel-main',
        NOVEL_ASSETS . 'js/main.js',
        [],
        NOVEL_VERSION,
        true
    );

    // ── Localize ──
    $current_user = wp_get_current_user();
    wp_localize_script('novel-main', 'NovelAjax', [
        'url'          => admin_url('admin-ajax.php'),
        'nonce'        => wp_create_nonce('novel_nonce'),
        'is_logged_in' => is_user_logged_in(),
        'user_id'      => get_current_user_id(),
        'login_url'    => home_url('/login/'),
        'home_url'     => home_url('/'),
        'theme_url'    => NOVEL_URI,
        'assets_url'   => NOVEL_ASSETS,
        'user_avatar'  => is_user_logged_in() ? Novel_Avatars::get_avatar_url($current_user->ID) : '',
        'user_name'    => is_user_logged_in() ? $current_user->display_name : '',
        'notif_interval' => absint(get_option('novel_notif_interval', 60)) * 1000,
        'i18n'         => [
            'loading'       => 'در حال بارگذاری...',
            'error'         => 'خطایی رخ داد',
            'success'       => 'با موفقیت انجام شد',
            'confirm'       => 'آیا مطمئن هستید؟',
            'login_required' => 'برای این کار باید وارد شوید',
            'network_error' => 'خطا در ارتباط با سرور',
            'load_more'     => 'بارگذاری بیشتر',
            'no_more'       => 'موردی دیگری وجود ندارد',
        ],
    ]);

    // ── JS شرطی ──
    if (is_singular('chapter')) {
        wp_enqueue_script('novel-reader', NOVEL_ASSETS . 'js/reader.js', ['novel-main'], NOVEL_VERSION, true);
    }
    if (is_singular('novel') || is_singular('chapter')) {
        wp_enqueue_script('novel-comments', NOVEL_ASSETS . 'js/comments.js', ['novel-main'], NOVEL_VERSION, true);
    }
}


/*فاز 3*/

// ← کل این تابع رو بعدش اضافه کن:
function novel_phase3_enqueue_scripts() {
    if (is_singular('chapter')) {
        wp_enqueue_style(
            'novel-reader',
            get_template_directory_uri() . '/assets/css/reader.css',
            ['novel-main'],
            NOVEL_VERSION
        );
        wp_enqueue_script(
            'novel-reader',
            get_template_directory_uri() . '/assets/js/reader.js',
            ['jquery'],
            NOVEL_VERSION,
            true
        );
        wp_localize_script('novel-reader', 'novelReader', [
            'ajaxUrl'    => admin_url('admin-ajax.php'),
            'nonce'      => wp_create_nonce('novel_reader_action'),
            'chapterId'  => get_the_ID(),
            'novelId'    => get_post_meta(get_the_ID(), 'chapter_novel_id', true),
            'isLoggedIn' => is_user_logged_in(),
        ]);
    }

    if (is_singular('novel') || is_post_type_archive('novel') || is_tax('genre') || is_tax('novel_tag') || is_tax('novel_status')) {
        wp_enqueue_script(
            'novel-chapters',
            get_template_directory_uri() . '/assets/js/chapters.js',
            ['jquery'],
            NOVEL_VERSION,
            true
        );
        wp_localize_script('novel-chapters', 'novelChapters', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('novel_chapters_action'),
        ]);

        wp_enqueue_script(
            'novel-filter',
            get_template_directory_uri() . '/assets/js/filter.js',
            ['jquery'],
            NOVEL_VERSION,
            true
        );
        wp_localize_script('novel-filter', 'novelFilter', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('novel_filter_action'),
        ]);
    }
}
add_action('wp_enqueue_scripts', 'novel_phase3_enqueue_scripts');



// ═══════════════════════════════════════
// Theme Setup
// ═══════════════════════════════════════
add_action('after_setup_theme', 'novel_setup');
function novel_setup() {
    // پشتیبانی RTL
    load_theme_textdomain('flavor-flavor', NOVEL_DIR . '/languages');

    // قابلیت‌های قالب
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', [
        'comment-form',
        'comment-list',
        'search-form',
        'gallery',
        'caption',
        'style',
        'script',
    ]);
    add_theme_support('custom-logo', [
        'height'      => 60,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
    ]);

    // منوها
    register_nav_menus([
        'primary' => 'منوی اصلی',
        'footer'  => 'منوی فوتر',
        'mobile'  => 'منوی موبایل',
    ]);

    // اندازه‌های تصویر
    add_image_size('novel-card', 300, 420, true);
    add_image_size('novel-card-small', 150, 210, true);
    add_image_size('novel-thumb', 80, 112, true);
    add_image_size('novel-banner', 1200, 400, true);
    add_image_size('novel-avatar-lg', 120, 120, true);

    // غیرفعال emoji وردپرس (بهینه‌سازی)
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
}

// ═══════════════════════════════════════
// ریدایرکت wp-login.php به صفحه لاگین سفارشی
// ═══════════════════════════════════════
add_action('login_init', function () {
    // اجازه دسترسی مستقیم ادمین‌ها
    if (defined('DOING_AJAX') && DOING_AJAX) {
        return;
    }
    $action = isset($_GET['action']) ? sanitize_key($_GET['action']) : '';
    // اجازه logout
    if ($action === 'logout') {
        return;
    }
    // ریدایرکت به صفحه لاگین سفارشی
    if (!is_admin()) {
        wp_safe_redirect(home_url('/login/'));
        exit;
    }
});

// ═══════════════════════════════════════
// حالت تعمیرات
// ═══════════════════════════════════════
add_action('template_redirect', 'novel_maintenance_mode');
function novel_maintenance_mode() {
    if (!get_option('novel_maintenance_mode', false)) {
        return;
    }
    // ادمین‌ها عبور کنند
    if (current_user_can('manage_options')) {
        return;
    }
    // AJAX عبور کند
    if (defined('DOING_AJAX') && DOING_AJAX) {
        return;
    }
    // صفحه لاگین عبور کند
    if (is_page('login') || is_page('register')) {
        return;
    }

    status_header(503);
    header('Retry-After: 3600');
    include NOVEL_DIR . '/templates/errors/maintenance.php';
    exit;
}

// ═══════════════════════════════════════
// توابع کمکی عمومی
// ═══════════════════════════════════════

/**
 * تبدیل اعداد انگلیسی به فارسی
 */
function novel_fa_num($string) {
    $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $fa = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    return str_replace($en, $fa, (string) $string);
}

/**
 * فرمت عدد فارسی با جداکننده
 */
function novel_format_number($number) {
    return novel_fa_num(number_format((int) $number));
}

/**
 * زمان نسبی فارسی
 */
function novel_time_ago($datetime) {
    $now = current_time('timestamp');
    $time = is_numeric($datetime) ? $datetime : strtotime($datetime);
    $diff = $now - $time;

    if ($diff < 1) {
        return 'همین الان';
    }

    $intervals = [
        ['label' => 'سال', 'seconds' => 31536000],
        ['label' => 'ماه', 'seconds' => 2592000],
        ['label' => 'هفته', 'seconds' => 604800],
        ['label' => 'روز', 'seconds' => 86400],
        ['label' => 'ساعت', 'seconds' => 3600],
        ['label' => 'دقیقه', 'seconds' => 60],
        ['label' => 'ثانیه', 'seconds' => 1],
    ];

    foreach ($intervals as $interval) {
        $count = floor($diff / $interval['seconds']);
        if ($count > 0) {
            return novel_fa_num($count) . ' ' . $interval['label'] . ' پیش';
        }
    }

    return 'همین الان';
}

/**
 * بررسی AJAX request معتبر
 */
function novel_verify_ajax($action = 'novel_nonce') {
    if (!check_ajax_referer($action, 'nonce', false)) {
        wp_send_json_error(['message' => 'درخواست نامعتبر'], 403);
        exit;
    }
}

/**
 * بررسی لاگین بودن در AJAX
 */
function novel_require_login() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'برای این کار باید وارد شوید', 'login_required' => true], 401);
        exit;
    }
}

/**
 * بررسی rate limit
 */
function novel_check_rate_limit($action, $user_id, $limit = 10, $window = 60) {
    $key = 'novel_rl_' . $action . '_' . $user_id;
    $attempts = get_transient($key);

    if ($attempts === false) {
        set_transient($key, 1, $window);
        return true;
    }

    if ($attempts >= $limit) {
        return false;
    }

    set_transient($key, $attempts + 1, $window);
    return true;
}

/**
 * دریافت آواتار امن
 */
function novel_get_avatar_url($user_id) {
    if (class_exists('Novel_Avatars')) {
        return Novel_Avatars::get_avatar_url($user_id);
    }
    return NOVEL_ASSETS . 'avatars/avatar-1.png';
}

/**
 * دریافت بج کاربر
 */
function novel_get_user_badge($user_id) {
    $user = get_userdata($user_id);
    if (!$user) return '';

    $badges = [];

    if (in_array('administrator', $user->roles)) {
        $badges[] = '<span class="novel-badge badge-admin">👑 مدیر</span>';
    } elseif (in_array('editor', $user->roles)) {
        $badges[] = '<span class="novel-badge badge-editor">📝 ویراستار</span>';
    } elseif (in_array('author', $user->roles)) {
        $badges[] = '<span class="novel-badge badge-author">✍️ نویسنده رسمی</span>';
    }

    // بج VIP (اشتراک)
    if (function_exists('rcp_is_active') && rcp_is_active($user_id)) {
        $badges[] = '<span class="novel-badge badge-vip">⭐ VIP</span>';
    }

    return implode(' ', $badges);
}

/**
 * خروجی SVG آیکون
 */
function novel_icon($name, $size = 24, $class = '') {
    $file = NOVEL_DIR . '/assets/icons/' . sanitize_file_name($name) . '.svg';
    if (!file_exists($file)) {
        return '';
    }
    $svg = file_get_contents($file);
    $class_attr = $class ? ' class="novel-icon ' . esc_attr($class) . '"' : ' class="novel-icon"';
    $svg = preg_replace('/<svg /', '<svg' . $class_attr . ' width="' . intval($size) . '" height="' . intval($size) . '" ', $svg, 1);
    return $svg;
}

/**
 * Pagination عمومی
 */
function novel_pagination($query = null, $type = 'auto') {
    if ($type === 'auto') {
        $type = wp_is_mobile() ? 'load_more' : 'numbered';
    }
    include NOVEL_DIR . '/templates/components/pagination.php';
}

/**
 * پاکسازی cache ها
 */
function novel_clear_cache($group, $id = '') {
    $map = [
        'comment'       => ['novel_comment_count_', 'novel_homepage_comments'],
        'comment_vote'  => ['novel_comment_votes_'],
        'follow'        => ['novel_followers_count_', 'novel_novel_followers_'],
        'chapter'       => ['novel_chapter_count_', 'novel_latest_updates', 'novel_rankings_'],
        'views'         => ['novel_views_'],
        'settings'      => ['novel_settings_cache'],
        'coins'         => ['novel_user_balance_'],
    ];

    if (isset($map[$group])) {
        foreach ($map[$group] as $prefix) {
            if ($id) {
                delete_transient($prefix . $id);
            } else {
                delete_transient($prefix);
            }
        }
    }
}














/**
 * functions.php - Updated for Phase 1
 * 
 * Add 'auth' module to the modules array in the existing functions.php
 * 
 * @package suspended-starter
 * @since 3.0.0
 */

// ... (existing code from Phase 0 remains unchanged) ...

/**
 * Load theme modules
 * UPDATE: Add 'auth' to the modules list
 */
function suspended_starter_load_modules() {
    $modules = [
        'core'     => '/inc/class-novel-core.php',
        'settings' => '/inc/class-novel-settings.php',
        'auth'     => '/inc/class-novel-auth.php',      // ← NEW: Phase 1
    ];

    $module_classes = [
        'core'     => 'Novel_Core',
        'settings' => 'Novel_Settings',
        'auth'     => 'Novel_Auth',                     // ← NEW: Phase 1
    ];

    foreach ($modules as $slug => $file) {
        $filepath = get_template_directory() . $file;
        if (file_exists($filepath)) {
            require_once $filepath;
            
            // Initialize class if exists
            if (isset($module_classes[$slug]) && class_exists($module_classes[$slug])) {
                $class = $module_classes[$slug];
                if (method_exists($class, 'get_instance')) {
                    $class::get_instance();
                } else {
                    new $class();
                }
            }
        }
    }
}
add_action('after_setup_theme', 'suspended_starter_load_modules', 5);

/**
 * Flush rewrite rules on theme activation (for auth URLs)
 */
function suspended_starter_activation() {
    // Ensure auth rewrite rules are registered
    if (class_exists('Novel_Auth')) {
        $auth = Novel_Auth::get_instance();
        $auth->add_rewrite_rules();
    }
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'suspended_starter_activation');

/**
 * Auth-specific: Add email verification banner in dashboard
 * (Hooked to wp_footer to show verification notice)
 */
function novel_email_verification_notice() {
    if (!is_user_logged_in()) return;
    if (!class_exists('Novel_Auth')) return;
    
    $user_id = get_current_user_id();
    if (Novel_Auth::is_email_verified($user_id)) return;
    
    // Don't show on auth pages
    $auth_page = get_query_var('novel_auth_page');
    if (!empty($auth_page)) return;
    
    ?>
    <div class="novel-verify-banner" id="novelVerifyBanner">
        <div class="novel-verify-banner__inner">
            <span class="novel-verify-banner__icon">⚠️</span>
            <span class="novel-verify-banner__text">
                ایمیل شما تأیید نشده! برخی امکانات محدود است.
                <a href="<?php echo esc_url(home_url('/verify-email/')); ?>" class="novel-verify-banner__link">ارسال مجدد لینک تأیید</a>
            </span>
            <button type="button" class="novel-verify-banner__close" onclick="this.closest('.novel-verify-banner').style.display='none'" aria-label="بستن">×</button>
        </div>
    </div>
    <style>
        .novel-verify-banner {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 10000;
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border-bottom: 2px solid #f59e0b;
            padding: 10px 20px;
            font-size: 14px;
            color: #92400e;
            text-align: center;
        }
        .novel-verify-banner__inner {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            max-width: 900px;
            margin: 0 auto;
        }
        .novel-verify-banner__link {
            color: #d97706;
            font-weight: 700;
            text-decoration: underline;
            margin-right: 4px;
        }
        .novel-verify-banner__close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #92400e;
            padding: 0 4px;
            line-height: 1;
            margin-right: auto;
        }
        [data-theme="dark"] .novel-verify-banner {
            background: linear-gradient(135deg, #451a03, #78350f);
            border-color: #b45309;
            color: #fde68a;
        }
        [data-theme="dark"] .novel-verify-banner__link { color: #fbbf24; }
        [data-theme="dark"] .novel-verify-banner__close { color: #fde68a; }
        body.has-verify-banner { padding-top: 48px; }
    </style>
    <script>document.body.classList.add('has-verify-banner');</script>
    <?php
}
add_action('wp_footer', 'novel_email_verification_notice', 5);













/**
 * functions.php - Updated for Phase 1 Part 2
 * 
 * ADD these modules to the existing modules array:
 */

function suspended_starter_load_modules() {
    $modules = [
        'core'     => '/inc/class-novel-core.php',
        'settings' => '/inc/class-novel-settings.php',
        'auth'     => '/inc/class-novel-auth.php',
        'avatars'  => '/inc/class-novel-avatars.php',     // ← NEW
        'profile'  => '/inc/class-novel-profile.php',     // ← NEW
    ];

    $module_classes = [
        'core'     => 'Novel_Core',
        'settings' => 'Novel_Settings',
        'auth'     => 'Novel_Auth',
        'avatars'  => 'Novel_Avatars',                   // ← NEW
        'profile'  => 'Novel_Profile',                   // ← NEW
    ];

    foreach ($modules as $slug => $file) {
        $filepath = get_template_directory() . $file;
        if (file_exists($filepath)) {
            require_once $filepath;
            if (isset($module_classes[$slug]) && class_exists($module_classes[$slug])) {
                $class = $module_classes[$slug];
                if (method_exists($class, 'get_instance')) {
                    $class::get_instance();
                } else {
                    new $class();
                }
            }
        }
    }
}
add_action('after_setup_theme', 'suspended_starter_load_modules', 5);

/**
 * Global helper: Get avatar URL
 */
function novel_get_avatar($user_id, $size = 64) {
    if (class_exists('Novel_Avatars')) {
        return Novel_Avatars::get_avatar_url_static($user_id, $size);
    }
    return get_avatar_url($user_id, ['size' => $size]);
}













/**
 * functions.php - Add comments module
 * Add to the existing modules array:
 */

function suspended_starter_load_modules() {
    $modules = [
        'core'     => '/inc/class-novel-core.php',
        'settings' => '/inc/class-novel-settings.php',
        'auth'     => '/inc/class-novel-auth.php',
        'avatars'  => '/inc/class-novel-avatars.php',
        'profile'  => '/inc/class-novel-profile.php',
        'comments' => '/inc/class-novel-comments.php',   // ← NEW
    ];

    $module_classes = [
        'core'     => 'Novel_Core',
        'settings' => 'Novel_Settings',
        'auth'     => 'Novel_Auth',
        'avatars'  => 'Novel_Avatars',
        'profile'  => 'Novel_Profile',
        'comments' => 'Novel_Comments',                  // ← NEW
    ];

    foreach ($modules as $slug => $file) {
        $filepath = get_template_directory() . $file;
        if (file_exists($filepath)) {
            require_once $filepath;
            if (isset($module_classes[$slug]) && class_exists($module_classes[$slug])) {
                $class = $module_classes[$slug];
                if (method_exists($class, 'get_instance')) {
                    $class::get_instance();
                } else {
                    new $class();
                }
            }
        }
    }
}
add_action('after_setup_theme', 'suspended_starter_load_modules', 5);

/**
 * Global helper: Get comment count by type for a post
 */
function novel_get_comment_count($post_id, $type = 'comment') {
    if (class_exists('Novel_Comments')) {
        return Novel_Comments::get_instance()->count_comments($post_id, $type);
    }
    return wp_count_comments($post_id)->approved;
}













/**
 * functions.php - Complete module list after Phase 2
 */

function suspended_starter_load_modules() {
    $modules = [
        'core'     => '/inc/class-novel-core.php',
        'settings' => '/inc/class-novel-settings.php',
        'auth'     => '/inc/class-novel-auth.php',
        'avatars'  => '/inc/class-novel-avatars.php',
        'profile'  => '/inc/class-novel-profile.php',
        'comments' => '/inc/class-novel-comments.php',
        'stickers' => '/inc/class-novel-stickers.php',    // ← NEW
    ];

    $module_classes = [
        'core'     => 'Novel_Core',
        'settings' => 'Novel_Settings',
        'auth'     => 'Novel_Auth',
        'avatars'  => 'Novel_Avatars',
        'profile'  => 'Novel_Profile',
        'comments' => 'Novel_Comments',
        'stickers' => 'Novel_Stickers',                   // ← NEW
    ];

    foreach ($modules as $slug => $file) {
        $filepath = get_template_directory() . $file;
        if (file_exists($filepath)) {
            require_once $filepath;
            if (isset($module_classes[$slug]) && class_exists($module_classes[$slug])) {
                $class = $module_classes[$slug];
                if (method_exists($class, 'get_instance')) {
                    $class::get_instance();
                } else {
                    new $class();
                }
            }
        }
    }
}
add_action('after_setup_theme', 'suspended_starter_load_modules', 5);

/**
 * Global helpers
 */

// Avatar
function novel_get_avatar($user_id, $size = 64) {
    if (class_exists('Novel_Avatars')) {
        return Novel_Avatars::get_avatar_url_static($user_id, $size);
    }
    return get_avatar_url($user_id, ['size' => $size]);
}

// Comment count by type
function novel_get_comment_count($post_id, $type = 'comment') {
    if (class_exists('Novel_Comments')) {
        return Novel_Comments::get_instance()->count_comments($post_id, $type);
    }
    return wp_count_comments($post_id)->approved;
}

// User comment level
function novel_get_user_level($user_id) {
    if (class_exists('Novel_Comments')) {
        return Novel_Comments::get_user_level($user_id);
    }
    return ['title' => 'ناشناس', 'icon' => '👤', 'color' => '#6b7280', 'count' => 0];
}

// User badges
function novel_get_user_badges($user_id, $post_id = 0) {
    if (class_exists('Novel_Comments')) {
        return Novel_Comments::get_user_badges($user_id, $post_id);
    }
    return [];
}

/**
 * Flush rewrite rules on activation
 */
function suspended_starter_activation() {
    if (class_exists('Novel_Auth')) {
        Novel_Auth::get_instance()->add_rewrite_rules();
    }
    if (class_exists('Novel_Comments')) {
        Novel_Comments::get_instance()->add_rewrite_rules();
    }
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'suspended_starter_activation');
















/**
 * ═══ فاز ۳ - AJAX Handlers + Enqueue ═══
 * 
 * اضافه به functions.php
 */

// ═══════════════════════════════════════════
// AJAX: Chapter Vote (Like/Dislike)
// ═══════════════════════════════════════════

/*این  قسمت حذف شد*/
/**
 * ═══ Replace old vote handlers ═══
 * 
 * اگر در functions.php فاز ۳ از novel_ajax_chapter_vote استفاده شده،
 * آن را حذف کنید. حالا Novel_Ratings آن را مدیریت می‌کند.
 * 
 * حذف شوند:
 *   - function novel_ajax_chapter_vote() و add_action مربوطه
 *   (جایگزین شده با Novel_Ratings::ajax_vote_chapter)
 */

// ═══════════════════════════════════════════
// AJAX: Report Chapter
// ═══════════════════════════════════════════

function novel_ajax_report_chapter() {
    check_ajax_referer('novel_reader_action', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'ابتدا وارد شوید']);
    }
    
    $chapter_id  = absint($_POST['chapter_id'] ?? 0);
    $reason      = sanitize_text_field($_POST['reason'] ?? '');
    $description = sanitize_textarea_field($_POST['description'] ?? '');
    $user_id     = get_current_user_id();
    
    $valid_reasons = ['typo', 'translation', 'inappropriate', 'duplicate', 'broken', 'other'];
    
    if (!$chapter_id || !in_array($reason, $valid_reasons)) {
        wp_send_json_error(['message' => 'داده نامعتبر']);
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'reports';
    
    // Create table if not exists
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$table} (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        reported_type VARCHAR(20) NOT NULL,
        reported_id BIGINT UNSIGNED NOT NULL,
        reason VARCHAR(50) NOT NULL,
        description TEXT,
        status VARCHAR(20) DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_reported (reported_type, reported_id)
    ) {$wpdb->get_charset_collate()}");
    
    // Check duplicate report from same user
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND reported_type = 'chapter' AND reported_id = %d AND status = 'pending'",
        $user_id, $chapter_id
    ));
    
    if ($exists) {
        wp_send_json_error(['message' => 'شما قبلاً این قسمت را گزارش داده‌اید']);
    }
    
    $wpdb->insert($table, [
        'user_id'       => $user_id,
        'reported_type' => 'chapter',
        'reported_id'   => $chapter_id,
        'reason'        => $reason,
        'description'   => mb_substr($description, 0, 500),
        'status'        => 'pending',
        'created_at'    => current_time('mysql'),
    ]);
    
    wp_send_json_success(['message' => 'گزارش ارسال شد']);
}
add_action('wp_ajax_novel_report_chapter', 'novel_ajax_report_chapter');

// ═══════════════════════════════════════════
// AJAX: Toggle Follow Novel
// ═══════════════════════════════════════════

/* این کد کامل جایگزین شد*/

// ═══════════════════════════════════════════
// AJAX: Update Library Status
// ═══════════════════════════════════════════

function novel_ajax_update_library() {
    check_ajax_referer('novel_library', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'ابتدا وارد شوید']);
    }
    
    $novel_id = absint($_POST['novel_id'] ?? 0);
    $status   = sanitize_text_field($_POST['status'] ?? '');
    $user_id  = get_current_user_id();
    
    $valid_statuses = ['reading', 'plan', 'completed', 'on_hold', 'dropped', 'remove'];
    
    if (!$novel_id || !in_array($status, $valid_statuses)) {
        wp_send_json_error(['message' => 'داده نامعتبر']);
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'user_library';
    
    // Create table if not exists
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$table} (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        novel_id BIGINT UNSIGNED NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'reading',
        last_chapter_id BIGINT UNSIGNED DEFAULT 0,
        progress_percent TINYINT UNSIGNED DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY user_novel (user_id, novel_id)
    ) {$wpdb->get_charset_collate()}");
    
    $labels = [
        'reading'   => '📖 در حال خواندن',
        'plan'      => '📋 می‌خوام بخوانم',
        'completed' => '✅ تکمیل شده',
        'on_hold'   => '⏸ نگه‌داشته',
        'dropped'   => '❌ رها شده',
    ];
    
    if ($status === 'remove') {
        $wpdb->delete($table, ['user_id' => $user_id, 'novel_id' => $novel_id]);
        
        // Update bookmark count
        $count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE novel_id = %d", $novel_id));
        update_post_meta($novel_id, 'novel_bookmark_count', $count);
        
        wp_send_json_success([
            'removed' => true,
            'message' => 'از کتابخانه حذف شد',
        ]);
    }
    
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$table} WHERE user_id = %d AND novel_id = %d",
        $user_id, $novel_id
    ));
    
    if ($existing) {
        $wpdb->update($table, 
            ['status' => $status, 'updated_at' => current_time('mysql')],
            ['id' => $existing]
        );
    } else {
        $wpdb->insert($table, [
            'user_id'    => $user_id,
            'novel_id'   => $novel_id,
            'status'     => $status,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ]);
    }
    
    // Update bookmark count
    $count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE novel_id = %d", $novel_id));
    update_post_meta($novel_id, 'novel_bookmark_count', $count);
    
    wp_send_json_success([
        'removed' => false,
        'label'   => $labels[$status] ?? $status,
        'message' => 'کتابخانه به‌روز شد',
    ]);
}
add_action('wp_ajax_novel_update_library', 'novel_ajax_update_library');

// ═══════════════════════════════════════════
// AJAX: Purchase Chapter with Coins
// ═══════════════════════════════════════════

function novel_ajax_purchase_chapter() {
    check_ajax_referer('novel_reader_action', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'ابتدا وارد شوید']);
    }
    
    $chapter_id = absint($_POST['chapter_id'] ?? 0);
    $user_id    = get_current_user_id();
    
    if (!$chapter_id) {
        wp_send_json_error(['message' => 'قسمت نامعتبر']);
    }
    
    $is_vip    = get_post_meta($chapter_id, 'chapter_is_vip', true);
    $price     = (int) get_post_meta($chapter_id, 'chapter_coin_price', true) ?: 5;
    $user_coins = (int) get_user_meta($user_id, 'novel_coins', true);
    
    if (!$is_vip) {
        wp_send_json_error(['message' => 'این قسمت رایگان است']);
    }
    
    if ($user_coins < $price) {
        wp_send_json_error(['message' => 'موجودی سکه کافی نیست']);
    }
    
    global $wpdb;
    $purchase_table = $wpdb->prefix . 'chapter_purchases';
    
    // Create table if not exists
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$purchase_table} (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        chapter_id BIGINT UNSIGNED NOT NULL,
        novel_id BIGINT UNSIGNED NOT NULL,
        coins_spent INT UNSIGNED NOT NULL,
        purchased_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_chapter (user_id, chapter_id)
    ) {$wpdb->get_charset_collate()}");
    
    // Check if already purchased
    $already = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$purchase_table} WHERE user_id = %d AND chapter_id = %d",
        $user_id, $chapter_id
    ));
    
    if ($already) {
        wp_send_json_error(['message' => 'قبلاً خریداری شده']);
    }
    
    $novel_id = get_post_meta($chapter_id, 'chapter_novel_id', true);
    
    // Deduct coins
    $new_balance = $user_coins - $price;
    update_user_meta($user_id, 'novel_coins', $new_balance);
    
    // Record purchase
    $wpdb->insert($purchase_table, [
        'user_id'      => $user_id,
        'chapter_id'   => $chapter_id,
        'novel_id'     => $novel_id,
        'coins_spent'  => $price,
        'purchased_at' => current_time('mysql'),
    ]);
    
    // Record transaction
    $tx_table = $wpdb->prefix . 'coin_transactions';
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$tx_table} (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        amount INT NOT NULL,
        type VARCHAR(20) NOT NULL,
        description VARCHAR(255),
        balance_after INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id)
    ) {$wpdb->get_charset_collate()}");
    
    $wpdb->insert($tx_table, [
        'user_id'       => $user_id,
        'amount'        => -$price,
        'type'          => 'purchase',
        'description'   => 'خرید قسمت ' . get_post_meta($chapter_id, 'chapter_number', true) . ' - ' . get_the_title($novel_id),
        'balance_after' => $new_balance,
        'created_at'    => current_time('mysql'),
    ]);
    
    wp_send_json_success([
        'message'     => 'خرید موفق',
        'new_balance' => $new_balance,
    ]);
}
add_action('wp_ajax_novel_purchase_chapter', 'novel_ajax_purchase_chapter');

// ═══════════════════════════════════════════
// AJAX: Load More Chapters
// ═══════════════════════════════════════════

function novel_ajax_load_more_chapters() {
    $novel_id = absint($_POST['novel_id'] ?? 0);
    $page     = absint($_POST['page'] ?? 2);
    
    if (!$novel_id) {
        wp_send_json_error();
    }
    
    $chapters = novel_get_chapters($novel_id, [
        'posts_per_page' => 50,
        'paged'          => $page,
    ]);
    
    ob_start();
    if ($chapters->have_posts()) {
        while ($chapters->have_posts()) {
            $chapters->the_post();
            $GLOBALS['chapter_item_id'] = get_the_ID();
            get_template_part('templates/novel/chapter-list-item');
        }
        wp_reset_postdata();
    }
    $html = ob_get_clean();
    
    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_novel_load_more_chapters', 'novel_ajax_load_more_chapters');
add_action('wp_ajax_nopriv_novel_load_more_chapters', 'novel_ajax_load_more_chapters');

// ═══════════════════════════════════════════
// ENQUEUE: Archive Novel CSS
// ═══════════════════════════════════════════

function novel_phase3_enqueue_archive_styles() {
    if (is_post_type_archive('novel') || is_tax('genre') || is_tax('novel_tag') || is_tax('novel_status')) {
        wp_enqueue_style(
            'novel-archive',
            get_template_directory_uri() . '/assets/css/archive-novel.css',
            ['novel-main-style'],
            NOVEL_VERSION
        );
    }
    
    // Dashboard forms
    if (is_page_template('page-user-dashboard.php') || is_page('dashboard')) {
        wp_enqueue_style(
            'novel-archive',
            get_template_directory_uri() . '/assets/css/archive-novel.css',
            ['novel-main-style'],
            NOVEL_VERSION
        );
    }
}
add_action('wp_enqueue_scripts', 'novel_phase3_enqueue_archive_styles');
/*فاز 12 */
// === Reader Mode ===
// (JS/CSS enqueued conditionally on chapter pages)
function novel_enqueue_reader_assets() {
    if (is_singular('chapter')) {
        wp_enqueue_style('novel-reader', get_template_directory_uri() . '/assets/css/reader.css', ['novel-main-style'], FLAVOR_VERSION);
        wp_enqueue_script('novel-reader', get_template_directory_uri() . '/assets/js/reader.js', ['jquery'], FLAVOR_VERSION, true);
    }
}
add_action('wp_enqueue_scripts', 'novel_enqueue_reader_assets');

// === SEO ===
require_once get_template_directory() . '/inc/class-novel-seo.php';
Novel_SEO::get_instance();

// ═══════════════════════════════════════════
// READING HISTORY TABLE (Create on theme setup)
// ═══════════════════════════════════════════

function novel_create_phase3_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    
    // Reading History
    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}reading_history (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        novel_id BIGINT UNSIGNED NOT NULL,
        chapter_id BIGINT UNSIGNED NOT NULL,
        scroll_position INT UNSIGNED DEFAULT 0,
        read_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_chapter (user_id, chapter_id),
        INDEX idx_user_novel (user_id, novel_id)
    ) {$charset}";
    dbDelta($sql);
}
add_action('after_switch_theme', 'novel_create_phase3_tables');

// Also run on init if tables don't exist yet
function novel_maybe_create_phase3_tables() {
    global $wpdb;
    $table = $wpdb->prefix . 'reading_history';
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
        novel_create_phase3_tables();
    }
}
add_action('init', 'novel_maybe_create_phase3_tables', 99);










// === Helper: رندر ستاره‌ها در هرجای قالب ===
// استفاده: echo novel_render_stars($novel_id, 'lg');
function novel_render_stars($novel_id, $size = 'md') {
    $avg   = get_post_meta($novel_id, 'novel_avg_rating', true) ?: 0;
    $count = get_post_meta($novel_id, 'novel_rating_count', true) ?: 0;
    return Novel_Ratings::render_stars($avg, $count, $size, $novel_id);
}

// === Helper: رندر لایک/دیسلایک قسمت ===
// استفاده: echo novel_render_chapter_votes($chapter_id, 'bottom');
function novel_render_chapter_votes($chapter_id, $position = 'top') {
    return Novel_Ratings::render_chapter_votes($chapter_id, $position);
}

// === Helper: مینی لایک برای لیست قسمت‌ها ===
function novel_render_mini_likes($chapter_id) {
    return Novel_Ratings::render_mini_likes($chapter_id);
}







/* فاز 5*/
function novel_is_user_writing_enabled() {
    return (bool) get_option('novel_user_writing', true);
}

 
/*فاز 6*/
// Novel approved/rejected notification
function novel_notify_novel_status_change($new_status, $old_status, $post) {
    if ($post->post_type !== 'novel') return;

    if ($old_status === 'pending' && $new_status === 'publish') {
        Novel_Notifications::send(
            $post->post_author,
            'novel_approved',
            '✅ رمان «' . $post->post_title . '» تأیید و منتشر شد!',
            '',
            get_permalink($post->ID)
        );
    }

    if ($old_status === 'pending' && $new_status === 'draft') {
        $reason = get_post_meta($post->ID, '_rejection_reason', true) ?: '';
        Novel_Notifications::send(
            $post->post_author,
            'novel_rejected',
            '❌ رمان «' . $post->post_title . '» رد شد',
            $reason,
            ''
        );
    }
}
add_action('transition_post_status', 'novel_notify_novel_status_change', 10, 3);

// Comment reply notification
function novel_notify_comment_reply($comment_id, $comment_approved) {
    if ($comment_approved !== 1) return;

    $comment = get_comment($comment_id);
    if (!$comment || !$comment->comment_parent) return;

    $parent = get_comment($comment->comment_parent);
    if (!$parent || !$parent->user_id) return;

    if ((int)$parent->user_id === (int)$comment->user_id) return;

    $commenter = $comment->comment_author ?: 'کاربر';
    $post_url  = get_permalink($comment->comment_post_ID) . '#comment-' . $comment_id;

    Novel_Notifications::send(
        $parent->user_id,
        'comment_reply',
        '💬 ' . $commenter . ' به دیدگاه شما پاسخ داد',
        mb_substr(wp_strip_all_tags($comment->comment_content), 0, 80),
        $post_url
    );
}
add_action('comment_post', 'novel_notify_comment_reply', 20, 2);




function novel_deactivation_cleanup() {
    wp_clear_scheduled_hook('novel_process_email_queue');
    wp_clear_scheduled_hook('novel_cleanup_old_notifications');
    wp_clear_scheduled_hook('novel_check_coin_expiry');
}
add_action('switch_theme', 'novel_deactivation_cleanup');