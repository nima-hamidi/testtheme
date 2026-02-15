<?php
/**
 * Novel Stickers & GIF System
 *
 * Manages local SVG stickers and GIF files.
 * No external services — all files served from theme directory.
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

class Novel_Stickers {

    private static $instance = null;
    private $sticker_dir;
    private $gif_dir;
    private $sticker_uri;
    private $gif_uri;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->sticker_dir = get_template_directory() . '/assets/stickers/';
        $this->gif_dir     = get_template_directory() . '/assets/gifs/';
        $this->sticker_uri = get_template_directory_uri() . '/assets/stickers/';
        $this->gif_uri     = get_template_directory_uri() . '/assets/gifs/';

        // AJAX
        add_action('wp_ajax_novel_get_stickers', [$this, 'ajax_get_stickers']);
        add_action('wp_ajax_novel_get_gifs', [$this, 'ajax_get_gifs']);

        // Admin AJAX
        add_action('wp_ajax_novel_upload_sticker', [$this, 'ajax_upload']);
        add_action('wp_ajax_novel_delete_sticker', [$this, 'ajax_delete']);

        // Ensure directories exist
        $this->ensure_directories();
    }

    /**
     * Ensure asset directories exist
     */
    private function ensure_directories() {
        $dirs = [
            $this->sticker_dir,
            $this->sticker_dir . 'emotions/',
            $this->sticker_dir . 'reactions/',
            $this->sticker_dir . 'characters/',
            $this->sticker_dir . 'custom/',
            $this->gif_dir,
            $this->gif_dir . 'funny/',
            $this->gif_dir . 'reactions/',
            $this->gif_dir . 'custom/',
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                wp_mkdir_p($dir);
            }
        }
    }

    /**
     * Get all stickers organized by category
     */
    public function get_stickers() {
        $cache_key = 'novel_stickers_list';
        $cached = get_transient($cache_key);
        if ($cached !== false) return $cached;

        $categories = [];

        if (!is_dir($this->sticker_dir)) return $categories;

        $dirs = glob($this->sticker_dir . '*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $cat_name = basename($dir);
            $files = glob($dir . '/*.svg');

            if (empty($files)) continue;

            $items = [];
            foreach ($files as $file) {
                $filename = pathinfo($file, PATHINFO_FILENAME);
                $items[] = [
                    'name' => $filename,
                    'file' => basename($file),
                    'url'  => $this->sticker_uri . $cat_name . '/' . basename($file),
                    'code' => '[sticker:' . $cat_name . '/' . $filename . ']',
                    'size' => filesize($file),
                ];
            }

            $categories[] = [
                'slug'  => $cat_name,
                'label' => $this->get_category_label($cat_name),
                'items' => $items,
                'count' => count($items),
            ];
        }

        set_transient($cache_key, $categories, 3600);
        return $categories;
    }

    /**
     * Get all GIFs organized by category
     */
    public function get_gifs() {
        $cache_key = 'novel_gifs_list';
        $cached = get_transient($cache_key);
        if ($cached !== false) return $cached;

        $categories = [];

        if (!is_dir($this->gif_dir)) return $categories;

        $dirs = glob($this->gif_dir . '*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $cat_name = basename($dir);
            $files = glob($dir . '/*.gif');

            if (empty($files)) continue;

            $items = [];
            foreach ($files as $file) {
                $filename = pathinfo($file, PATHINFO_FILENAME);
                $items[] = [
                    'name' => $filename,
                    'file' => basename($file),
                    'url'  => $this->gif_uri . $cat_name . '/' . basename($file),
                    'code' => '[gif:' . $cat_name . '/' . $filename . ']',
                    'size' => filesize($file),
                ];
            }

            $categories[] = [
                'slug'  => $cat_name,
                'label' => $this->get_category_label($cat_name),
                'items' => $items,
                'count' => count($items),
            ];
        }

        set_transient($cache_key, $categories, 3600);
        return $categories;
    }

    /**
     * AJAX: Get stickers JSON
     */
    public function ajax_get_stickers() {
        wp_send_json_success(['categories' => $this->get_stickers()]);
    }

    /**
     * AJAX: Get GIFs JSON
     */
    public function ajax_get_gifs() {
        wp_send_json_success(['categories' => $this->get_gifs()]);
    }

    /**
     * AJAX: Upload sticker/gif (admin only)
     */
    public function ajax_upload() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی ندارید.']);
        }

        check_ajax_referer('novel_admin_nonce', 'nonce');

        $type = sanitize_text_field($_POST['type'] ?? 'sticker');
        $category = sanitize_file_name($_POST['category'] ?? 'custom');

        $base_dir = ($type === 'gif') ? $this->gif_dir : $this->sticker_dir;
        $target_dir = $base_dir . $category . '/';

        if (!is_dir($target_dir)) {
            wp_mkdir_p($target_dir);
        }

        $allowed_ext = ($type === 'gif') ? ['gif'] : ['svg'];
        $max_size = ($type === 'gif') ? 2 * 1024 * 1024 : 512 * 1024; // 2MB gif, 512KB svg
        $uploaded = [];

        if (empty($_FILES['files'])) {
            wp_send_json_error(['message' => 'فایلی انتخاب نشده.']);
        }

        $files = $_FILES['files'];
        $count = is_array($files['name']) ? count($files['name']) : 1;

        for ($i = 0; $i < $count; $i++) {
            $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
            $tmp  = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
            $size = is_array($files['size']) ? $files['size'][$i] : $files['size'];
            $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];

            if ($error !== UPLOAD_ERR_OK) continue;

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_ext)) continue;
            if ($size > $max_size) continue;

            // SVG security check
            if ($ext === 'svg') {
                $content = file_get_contents($tmp);
                if (preg_match('/<script|javascript:|on\w+=/i', $content)) continue;
            }

            $safe_name = sanitize_file_name(pathinfo($name, PATHINFO_FILENAME)) . '.' . $ext;
            $destination = $target_dir . $safe_name;

            // Avoid overwrite
            $counter = 1;
            while (file_exists($destination)) {
                $safe_name = sanitize_file_name(pathinfo($name, PATHINFO_FILENAME)) . '-' . $counter . '.' . $ext;
                $destination = $target_dir . $safe_name;
                $counter++;
            }

            if (move_uploaded_file($tmp, $destination)) {
                $uploaded[] = $safe_name;
            }
        }

        // Invalidate cache
        delete_transient('novel_stickers_list');
        delete_transient('novel_gifs_list');

        wp_send_json_success([
            'message'  => sprintf('%d فایل آپلود شد.', count($uploaded)),
            'uploaded' => $uploaded,
        ]);
    }

    /**
     * AJAX: Delete sticker/gif (admin only)
     */
    public function ajax_delete() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی ندارید.']);
        }

        check_ajax_referer('novel_admin_nonce', 'nonce');

        $type = sanitize_text_field($_POST['type'] ?? 'sticker');
        $file = sanitize_text_field($_POST['file'] ?? '');

        if (empty($file)) {
            wp_send_json_error(['message' => 'فایل مشخص نشده.']);
        }

        // Security: prevent path traversal
        $file = str_replace(['..', '/', '\\'], '', $file);

        // File path should include category: "emotions/happy.svg"
        $relative = sanitize_text_field($_POST['file']);
        if (strpos($relative, '..') !== false) {
            wp_send_json_error(['message' => 'مسیر نامعتبر.']);
        }

        $base_dir = ($type === 'gif') ? $this->gif_dir : $this->sticker_dir;
        $full_path = $base_dir . $relative;

        if (!file_exists($full_path)) {
            wp_send_json_error(['message' => 'فایل یافت نشد.']);
        }

        if (unlink($full_path)) {
            delete_transient('novel_stickers_list');
            delete_transient('novel_gifs_list');
            wp_send_json_success(['message' => 'فایل حذف شد.']);
        }

        wp_send_json_error(['message' => 'خطا در حذف فایل.']);
    }

    /**
     * Get human-readable category label
     */
    private function get_category_label($slug) {
        $labels = [
            'emotions'   => 'هیجان‌ها',
            'reactions'  => 'واکنش‌ها',
            'characters' => 'شخصیت‌ها',
            'custom'     => 'سفارشی',
            'funny'      => 'خنده‌دار',
        ];

        return $labels[$slug] ?? ucfirst($slug);
    }

    /**
     * Render sticker by shortcode
     */
    public static function render_shortcode($code) {
        // [sticker:category/name] → <img>
        if (preg_match('/\[sticker:([a-zA-Z0-9_\-]+)\/([a-zA-Z0-9_\-]+)\]/', $code, $m)) {
            $cat = sanitize_file_name($m[1]);
            $name = sanitize_file_name($m[2]);
            $path = get_template_directory() . "/assets/stickers/{$cat}/{$name}.svg";
            if (file_exists($path)) {
                $url = get_template_directory_uri() . "/assets/stickers/{$cat}/{$name}.svg";
                return '<img src="' . esc_url($url) . '" class="comment-sticker" alt="' . esc_attr($name) . '" loading="lazy">';
            }
        }

        // [gif:category/name] → <img>
        if (preg_match('/\[gif:([a-zA-Z0-9_\-]+)\/([a-zA-Z0-9_\-]+)\]/', $code, $m)) {
            $cat = sanitize_file_name($m[1]);
            $name = sanitize_file_name($m[2]);
            $path = get_template_directory() . "/assets/gifs/{$cat}/{$name}.gif";
            if (file_exists($path)) {
                $url = get_template_directory_uri() . "/assets/gifs/{$cat}/{$name}.gif";
                return '<img src="' . esc_url($url) . '" class="comment-gif" alt="' . esc_attr($name) . '" loading="lazy">';
            }
        }

        return '';
    }
}