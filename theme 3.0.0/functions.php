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