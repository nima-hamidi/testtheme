<?php
/**
 * فایل: inc/class-novel-avatars.php
 * توضیح: سیستم آواتار سفارشی - stub برای فاز ۰ (پیاده‌سازی کامل در فاز بعد)
 * نسخه: 2.0.0
 */

if (!defined('ABSPATH')) exit;

class Novel_Avatars {

    public static function get_avatar_url($user_id) {
        $avatar_id = get_user_meta($user_id, 'novel_avatar_id', true);
        if (!$avatar_id) {
            $avatar_id = 1;
        }
        $avatar_id = absint($avatar_id);
        if ($avatar_id < 1 || $avatar_id > 114) {
            $avatar_id = 1;
        }
        return NOVEL_ASSETS . 'avatars/avatar-' . $avatar_id . '.png';
    }
}










/**
 * Novel Avatars System
 *
 * Custom avatar picker with 114 pre-built avatars.
 * Replaces Gravatar entirely.
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Novel_Avatars {

    private static $instance = null;

    const TOTAL_AVATARS = 114;
    const DEFAULT_AVATAR = 'avatar-1';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Override WordPress avatar
        add_filter('get_avatar', [$this, 'custom_avatar'], 10, 6);
        add_filter('get_avatar_url', [$this, 'custom_avatar_url'], 10, 3);
        add_filter('pre_get_avatar_data', [$this, 'custom_avatar_data'], 10, 2);

        // Disable Gravatar
        add_filter('option_show_avatars', '__return_true');
        add_filter('user_profile_picture_description', [$this, 'avatar_description']);

        // AJAX
        add_action('wp_ajax_novel_save_avatar', [$this, 'save_avatar']);

        // Enqueue
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Override get_avatar()
     */
    public function custom_avatar($avatar, $id_or_email, $size, $default, $alt, $args = []) {
        $user_id = $this->resolve_user_id($id_or_email);
        if (!$user_id) {
            return $avatar;
        }

        $url = self::get_avatar_url_static($user_id);
        $size = (int) $size ?: 64;
        $alt = esc_attr($alt);
        $class = isset($args['class']) ? $args['class'] : '';
        if (is_array($class)) {
            $class = implode(' ', $class);
        }
        $class = 'avatar novel-avatar ' . $class;

        return sprintf(
            '<img src="%s" class="%s" width="%d" height="%d" alt="%s" loading="lazy">',
            esc_url($url),
            esc_attr(trim($class)),
            $size,
            $size,
            $alt
        );
    }

    /**
     * Override get_avatar_url()
     */
    public function custom_avatar_url($url, $id_or_email, $args) {
        $user_id = $this->resolve_user_id($id_or_email);
        if (!$user_id) {
            return $url;
        }

        return self::get_avatar_url_static($user_id);
    }

    /**
     * Override avatar data (prevents Gravatar requests)
     */
    public function custom_avatar_data($args, $id_or_email) {
        $user_id = $this->resolve_user_id($id_or_email);
        if ($user_id) {
            $args['url'] = self::get_avatar_url_static($user_id);
            $args['found_avatar'] = true;
        }
        return $args;
    }

    /**
     * Static: Get avatar URL for a user
     */
    public static function get_avatar_url_static($user_id, $size = 64) {
        $custom = get_user_meta($user_id, 'novel_custom_avatar', true);

        if (empty($custom)) {
            $custom = self::DEFAULT_AVATAR;
        }

        // Sanitize: must be avatar-X format
        if (!preg_match('/^avatar-(\d+)$/', $custom, $m)) {
            $custom = self::DEFAULT_AVATAR;
        } else {
            $num = (int) $m[1];
            if ($num < 1 || $num > self::TOTAL_AVATARS) {
                $custom = self::DEFAULT_AVATAR;
            }
        }

        return get_template_directory_uri() . '/assets/avatars/' . $custom . '.png';
    }

    /**
     * Get all available avatars info
     */
    public static function get_all_avatars() {
        $avatars = [];
        for ($i = 1; $i <= self::TOTAL_AVATARS; $i++) {
            $slug = 'avatar-' . $i;
            $avatars[] = [
                'id'   => $i,
                'slug' => $slug,
                'url'  => get_template_directory_uri() . '/assets/avatars/' . $slug . '.png',
            ];
        }
        return $avatars;
    }

    /**
     * Get current user's avatar slug
     */
    public static function get_user_avatar_slug($user_id) {
        $custom = get_user_meta($user_id, 'novel_custom_avatar', true);
        if (empty($custom) || !preg_match('/^avatar-\d+$/', $custom)) {
            return self::DEFAULT_AVATAR;
        }
        return $custom;
    }

    /**
     * AJAX: Save selected avatar
     */
    public function save_avatar() {
        if (!check_ajax_referer('novel_auth_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'توکن امنیتی نامعتبر است.']);
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'لطفاً ابتدا وارد شوید.']);
        }

        $avatar_id = isset($_POST['avatar_id']) ? intval($_POST['avatar_id']) : 0;

        if ($avatar_id < 1 || $avatar_id > self::TOTAL_AVATARS) {
            wp_send_json_error(['message' => 'آواتار انتخابی نامعتبر است.']);
        }

        $slug = 'avatar-' . $avatar_id;
        $user_id = get_current_user_id();

        update_user_meta($user_id, 'novel_custom_avatar', $slug);

        wp_send_json_success([
            'message'    => 'آواتار با موفقیت تغییر کرد! ✓',
            'avatar_url' => self::get_avatar_url_static($user_id),
            'slug'       => $slug,
        ]);
    }

    /**
     * Resolve user ID from various input types
     */
    private function resolve_user_id($id_or_email) {
        if (is_numeric($id_or_email)) {
            return (int) $id_or_email;
        }

        if (is_object($id_or_email)) {
            if (isset($id_or_email->user_id) && $id_or_email->user_id > 0) {
                return (int) $id_or_email->user_id;
            }
            if (isset($id_or_email->ID)) {
                return (int) $id_or_email->ID;
            }
            if (isset($id_or_email->comment_author_email)) {
                $user = get_user_by('email', $id_or_email->comment_author_email);
                return $user ? $user->ID : 0;
            }
            return 0;
        }

        if (is_string($id_or_email) && is_email($id_or_email)) {
            $user = get_user_by('email', $id_or_email);
            return $user ? $user->ID : 0;
        }

        return 0;
    }

    /**
     * Replace profile picture description in admin
     */
    public function avatar_description($desc) {
        return 'آواتار از طریق تنظیمات قالب مدیریت می‌شود.';
    }

    /**
     * Enqueue avatar-related assets
     */
    public function enqueue_assets() {
        // Only on dashboard pages
        if (!$this->is_dashboard_page()) {
            return;
        }

        wp_enqueue_style(
            'novel-avatars',
            get_template_directory_uri() . '/assets/css/avatars.css',
            ['novel-main-style'],
            SUSPENDED_STARTER_VERSION
        );

        wp_enqueue_script(
            'novel-avatars',
            get_template_directory_uri() . '/assets/js/avatars.js',
            ['jquery'],
            SUSPENDED_STARTER_VERSION,
            true
        );
    }

    /**
     * Check if current page is dashboard
     */
    private function is_dashboard_page() {
        if (is_page_template('page-user-dashboard.php')) return true;
        $dash_id = get_option('novel_dashboard_page_id');
        if ($dash_id && is_page($dash_id)) return true;
        // Check URL
        $uri = trim($_SERVER['REQUEST_URI'], '/');
        return strpos($uri, 'dashboard') !== false;
    }
}