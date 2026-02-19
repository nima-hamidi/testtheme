<?php
/**
 * Novel Author Banners System
 * بنر تبلیغاتی نویسنده در قسمت‌های رمان
 * 
 * @package suspended developer
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

class Novel_Author_Banners {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('wp_ajax_novel_submit_banner', [$this, 'ajax_submit']);
        add_action('wp_ajax_novel_delete_banner', [$this, 'ajax_delete']);
        add_action('wp_ajax_novel_banner_click', [$this, 'ajax_click']);
        add_action('wp_ajax_nopriv_novel_banner_click', [$this, 'ajax_click']);
        add_action('wp_ajax_novel_banner_impression', [$this, 'ajax_impression']);
        add_action('wp_ajax_nopriv_novel_banner_impression', [$this, 'ajax_impression']);

        add_action('novel_cron_expire_banners', [$this, 'expire_banners']);
        if (!wp_next_scheduled('novel_cron_expire_banners')) {
            wp_schedule_event(time(), 'daily', 'novel_cron_expire_banners');
        }
    }

    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        dbDelta("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}author_banners (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            author_id BIGINT UNSIGNED NOT NULL,
            novel_id BIGINT UNSIGNED NOT NULL,
            banner_image VARCHAR(500) NOT NULL,
            banner_link VARCHAR(500) NOT NULL,
            position ENUM('above_chapter','between_chapter','both') DEFAULT 'above_chapter',
            status ENUM('pending','approved','rejected','expired','deleted') DEFAULT 'pending',
            admin_note TEXT NULL,
            impressions INT UNSIGNED DEFAULT 0,
            clicks INT UNSIGNED DEFAULT 0,
            revenue DECIMAL(12,0) DEFAULT 0,
            submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            approved_at DATETIME NULL,
            approved_by BIGINT UNSIGNED NULL,
            expires_at DATETIME NULL,
            INDEX idx_author (author_id),
            INDEX idx_novel (novel_id),
            INDEX idx_status (status),
            INDEX idx_expires (expires_at)
        ) {$charset_collate};");
    }

    /**
     * دریافت بنر فعال برای رمان
     */
    public function get_active_banner($novel_id, $position = 'above_chapter') {
        global $wpdb;
        $now = current_time('mysql');

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}author_banners
             WHERE novel_id = %d AND status = 'approved'
             AND (expires_at IS NULL OR expires_at > %s)
             AND (position = %s OR position = 'both')
             ORDER BY RAND() LIMIT 1",
            $novel_id, $now, $position
        ));
    }

    /**
     * بنرهای نویسنده
     */
    public function get_author_banners($author_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT b.*, p.post_title as novel_title
             FROM {$wpdb->prefix}author_banners b
             LEFT JOIN {$wpdb->posts} p ON b.novel_id = p.ID
             WHERE b.author_id = %d AND b.status != 'deleted'
             ORDER BY b.submitted_at DESC",
            $author_id
        ));
    }

    /**
     * تعداد بنرهای فعال نویسنده
     */
    public function get_active_count($author_id) {
        global $wpdb;
        return (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}author_banners
             WHERE author_id = %d AND status IN ('approved','pending')",
            $author_id
        ));
    }

    /**
     * AJAX: ارسال بنر جدید
     */
    public function ajax_submit() {
        check_ajax_referer('novel_banner_nonce', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error(['message' => 'وارد شوید.']);

        $user_id = get_current_user_id();
        $novel_id = absint($_POST['novel_id'] ?? 0);
        $link = esc_url_raw($_POST['banner_link'] ?? '');
        $position = sanitize_text_field($_POST['position'] ?? 'above_chapter');

        // بررسی نویسنده بودن
        $novel = get_post($novel_id);
        if (!$novel || $novel->post_type !== 'novel' || (int)$novel->post_author !== $user_id) {
            wp_send_json_error(['message' => 'این رمان متعلق به شما نیست.']);
        }

        // بررسی تعداد
        $max = (int)get_option('novel_banner_max_active', 3);
        if ($this->get_active_count($user_id) >= $max) {
            wp_send_json_error(['message' => "حداکثر {$max} بنر فعال مجاز است."]);
        }

        // بررسی لینک
        if (empty($link) || !filter_var($link, FILTER_VALIDATE_URL)) {
            wp_send_json_error(['message' => 'لینک نامعتبر.']);
        }
        if (strpos($link, 'https://') !== 0) {
            wp_send_json_error(['message' => 'لینک باید https باشد.']);
        }

        // آپلود تصویر
        if (empty($_FILES['banner_image'])) {
            wp_send_json_error(['message' => 'تصویر بنر الزامی است.']);
        }

        $file = $_FILES['banner_image'];
        $max_size = (int)get_option('novel_banner_max_size', 500) * 1024;
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            wp_send_json_error(['message' => 'فرمت تصویر مجاز نیست. فرمت‌های مجاز: ' . implode(', ', $allowed)]);
        }

        if ($file['size'] > $max_size) {
            wp_send_json_error(['message' => 'حجم تصویر بیش از حد مجاز.']);
        }

        // بررسی ابعاد
        $image_info = getimagesize($file['tmp_name']);
        if (!$image_info) wp_send_json_error(['message' => 'تصویر نامعتبر.']);

        $width = $image_info[0];
        $height = $image_info[1];
        if ($width < 320 || $width > 1200 || $height < 50 || $height > 200) {
            wp_send_json_error(['message' => 'ابعاد تصویر: عرض ۳۲۰-۱۲۰۰، ارتفاع ۵۰-۲۰۰ پیکسل.']);
        }

        // ذخیره فایل
        $upload_dir = wp_upload_dir();
        $banner_dir = $upload_dir['basedir'] . '/novel-banners/';
        if (!file_exists($banner_dir)) wp_mkdir_p($banner_dir);

        $filename = 'banner_' . $user_id . '_' . time() . '.' . $ext;
        $filepath = $banner_dir . $filename;
        $fileurl = $upload_dir['baseurl'] . '/novel-banners/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            wp_send_json_error(['message' => 'خطا در آپلود تصویر.']);
        }

        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}author_banners", [
            'author_id'    => $user_id,
            'novel_id'     => $novel_id,
            'banner_image' => $fileurl,
            'banner_link'  => $link,
            'position'     => in_array($position, ['above_chapter', 'between_chapter', 'both']) ? $position : 'above_chapter',
        ]);

        // اعلان ادمین
        if (class_exists('Novel_Notifications')) {
            // ادمین‌ها را پیدا کن
            $admins = get_users(['role' => 'administrator', 'fields' => 'ID']);
            foreach ($admins as $admin_id) {
                Novel_Notifications::get_instance()->send($admin_id, 'admin', '🖼 بنر جدید برای بررسی ارسال شده.', '', '');
            }
        }

        wp_send_json_success(['message' => '✅ بنر ارسال شد و پس از بررسی فعال خواهد شد.']);
    }

    /**
     * AJAX: حذف بنر
     */
    public function ajax_delete() {
        check_ajax_referer('novel_banner_nonce', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error();

        $user_id = get_current_user_id();
        $banner_id = absint($_POST['banner_id'] ?? 0);

        global $wpdb;
        $banner = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}author_banners WHERE id = %d AND author_id = %d",
            $banner_id, $user_id
        ));
        if (!$banner) wp_send_json_error(['message' => 'بنر یافت نشد.']);

        // حذف فایل
        $upload_dir = wp_upload_dir();
        $filepath = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $banner->banner_image);
        if (file_exists($filepath)) @unlink($filepath);

        $wpdb->update("{$wpdb->prefix}author_banners", ['status' => 'deleted'], ['id' => $banner_id]);

        wp_send_json_success(['message' => '🗑 بنر حذف شد.']);
    }

    /**
     * AJAX: ثبت کلیک
     */
    public function ajax_click() {
        $banner_id = absint($_POST['banner_id'] ?? 0);
        if (!$banner_id) wp_send_json_error();

        $ip = $_SERVER['REMOTE_ADDR'];
        $user_id = get_current_user_id();
        $throttle_key = 'banner_click_' . $banner_id . '_' . md5($ip . $user_id);

        if (get_transient($throttle_key)) {
            wp_send_json_success(); // سکوت
            return;
        }

        global $wpdb;
        $banner = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}author_banners WHERE id = %d AND status = 'approved'", $banner_id
        ));
        if (!$banner) wp_send_json_error();

        // نویسنده خودش کلیک نکند
        if ($user_id && $user_id == $banner->author_id) {
            wp_send_json_success();
            return;
        }

        $revenue_per_click = (int)get_option('novel_banner_click_revenue', 1000);

        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}author_banners SET clicks = clicks + 1, revenue = revenue + %d WHERE id = %d",
            $revenue_per_click, $banner_id
        ));

        set_transient($throttle_key, 1, DAY_IN_SECONDS);

        wp_send_json_success(['redirect' => $banner->banner_link]);
    }

    /**
     * AJAX: ثبت نمایش
     */
    public function ajax_impression() {
        $banner_id = absint($_POST['banner_id'] ?? 0);
        if (!$banner_id) wp_send_json_error();

        $ip = $_SERVER['REMOTE_ADDR'];
        $throttle_key = 'banner_imp_' . $banner_id . '_' . md5($ip);

        if (get_transient($throttle_key)) return;

        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}author_banners SET impressions = impressions + 1 WHERE id = %d", $banner_id
        ));

        set_transient($throttle_key, 1, HOUR_IN_SECONDS);
        wp_send_json_success();
    }

    /**
     * انقضای خودکار
     */
    public function expire_banners() {
        global $wpdb;
        $now = current_time('mysql');

        // هشدار ۳ روز قبل
        $warn_date = date('Y-m-d', strtotime('+3 days', current_time('timestamp')));
        $expiring = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}author_banners WHERE status = 'approved' AND DATE(expires_at) = %s",
            $warn_date
        ));
        foreach ($expiring as $b) {
            if (class_exists('Novel_Notifications')) {
                Novel_Notifications::get_instance()->send($b->author_id, 'banner',
                    '⚠️ بنر شما تا ۳ روز دیگر منقضی می‌شود.', '', '');
            }
        }

        // منقضی
        $expired = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}author_banners WHERE status = 'approved' AND expires_at <= '{$now}'"
        );
        foreach ($expired as $b) {
            $wpdb->update("{$wpdb->prefix}author_banners", ['status' => 'expired'], ['id' => $b->id]);
            if (class_exists('Novel_Notifications')) {
                Novel_Notifications::get_instance()->send($b->author_id, 'banner',
                    sprintf('🖼 بنر شما منقضی شد. آمار: %s نمایش، %s کلیک.',
                        number_format_i18n($b->impressions), number_format_i18n($b->clicks)), '', '');
            }
        }
    }

    /**
     * رندر بنر در قسمت
     */
    public function render_chapter_banner($novel_id, $position = 'above_chapter') {
        if (!get_option('novel_module_banners', true)) return;

        $banner = $this->get_active_banner($novel_id, $position);
        if (!$banner) return;
        ?>
        <div class="novel-author-banner" data-banner-id="<?php echo $banner->id; ?>"
             data-banner-position="<?php echo esc_attr($position); ?>">
            <a href="#" class="novel-author-banner__link" data-banner-id="<?php echo $banner->id; ?>"
               data-redirect="<?php echo esc_url($banner->banner_link); ?>"
               target="_blank" rel="noopener nofollow sponsored">
                <img src="<?php echo esc_url($banner->banner_image); ?>"
                     alt="تبلیغات نویسنده"
                     class="novel-author-banner__img"
                     loading="lazy">
            </a>
            <span class="novel-author-banner__label">📢 تبلیغات نویسنده</span>
            <button class="novel-author-banner__close" onclick="this.closest('.novel-author-banner').remove();localStorage.setItem('banner_closed_<?php echo $banner->id; ?>',Date.now())">×</button>
        </div>
        <?php
    }
}