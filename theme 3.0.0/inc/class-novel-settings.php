<?php
/**
 * فایل: inc/class-novel-settings.php
 * توضیح: کلاس تنظیمات قالب - بررسی فعال/غیرفعال بودن ماژول‌ها + دسترسی به تنظیمات
 * نسخه: 2.0.0
 * وابستگی: هیچ (باید قبل از همه ماژول‌ها لود شود)
 */

if (!defined('ABSPATH')) exit;

class Novel_Settings {

    /**
     * کش تنظیمات در حافظه (جلوگیری از query تکراری)
     */
    private static $cache = [];

    /**
     * بررسی فعال بودن یک ماژول
     *
     * @param string $module_slug نام ماژول
     * @return bool
     */
    public static function is_module_active($module_slug) {
        $key = 'novel_module_' . sanitize_key($module_slug);

        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $value = get_option($key, '1');
        $result = ($value === '1' || $value === true || $value === 1);

        self::$cache[$key] = $result;

        return $result;
    }

    /**
     * فعال/غیرفعال کردن ماژول
     *
     * @param string $module_slug نام ماژول
     * @param bool $active فعال یا خیر
     */
    public static function set_module_active($module_slug, $active) {
        $key = 'novel_module_' . sanitize_key($module_slug);
        update_option($key, $active ? '1' : '0');
        self::$cache[$key] = (bool) $active;

        // پاکسازی cache تنظیمات
        delete_transient('novel_settings_cache');
    }

    /**
     * دریافت تنظیم عمومی
     *
     * @param string $key کلید تنظیم
     * @param mixed $default مقدار پیش‌فرض
     * @return mixed
     */
    public static function get($key, $default = '') {
        $option_key = 'novel_' . sanitize_key($key);

        if (isset(self::$cache[$option_key])) {
            return self::$cache[$option_key];
        }

        $value = get_option($option_key, $default);
        self::$cache[$option_key] = $value;

        return $value;
    }

    /**
     * ذخیره تنظیم عمومی
     *
     * @param string $key کلید تنظیم
     * @param mixed $value مقدار
     */
    public static function set($key, $value) {
        $option_key = 'novel_' . sanitize_key($key);
        update_option($option_key, $value);
        self::$cache[$option_key] = $value;
        delete_transient('novel_settings_cache');
    }

    /**
     * پاکسازی کش
     */
    public static function flush_cache() {
        self::$cache = [];
        delete_transient('novel_settings_cache');
    }

    /**
     * لیست تمام ماژول‌ها با عنوان فارسی
     */
    public static function get_all_modules() {
        return [
            // ═══ ماژول‌های اصلی (فایل مستقل) ═══
            'auth'              => ['title' => 'سیستم ورود و ثبت‌نام', 'group' => 'core'],
            'avatars'           => ['title' => 'آواتارهای سفارشی', 'group' => 'core'],
            'comments'          => ['title' => 'سیستم دیدگاه پیشرفته', 'group' => 'comments'],
            'ratings'           => ['title' => 'امتیاز ستاره‌ای رمان', 'group' => 'ratings'],
            'notifications'     => ['title' => 'سیستم اعلان', 'group' => 'core'],
            'reports'           => ['title' => 'گزارش‌دهی', 'group' => 'core'],
            'search'            => ['title' => 'جستجوی پیشرفته', 'group' => 'core'],
            'bookmarks'         => ['title' => 'کتابخانه شخصی', 'group' => 'core'],
            'rankings'          => ['title' => 'رتبه‌بندی', 'group' => 'core'],
            'authors'           => ['title' => 'صفحه نویسندگان', 'group' => 'authors'],
            'subscriptions'     => ['title' => 'اشتراک (RCP)', 'group' => 'coins'],
            'coins'             => ['title' => 'سیستم سکه', 'group' => 'coins'],
            'polls'             => ['title' => 'نظرسنجی', 'group' => 'extras'],
            'achievements'      => ['title' => 'دستاوردها و مدال', 'group' => 'extras'],
            'seo'               => ['title' => 'SEO اختصاصی', 'group' => 'core'],
            'follow'            => ['title' => 'فالو نویسنده و رمان', 'group' => 'social'],
            'stickers'          => ['title' => 'استیکر و GIF', 'group' => 'comments'],
            'volumes'           => ['title' => 'فصل‌بندی/جلدبندی', 'group' => 'core'],
            'quiz'              => ['title' => 'مسابقه کتابخوانی', 'group' => 'extras'],
            'author_banners'    => ['title' => 'بنرهای نویسنده', 'group' => 'authors'],

            // ═══ ماژول‌های فرعی (فعال/غیرفعال UI) ═══
            'comment_likes'     => ['title' => 'لایک/دیسلایک دیدگاه', 'group' => 'comments'],
            'comment_reactions' => ['title' => 'ری‌اکشن اموجی', 'group' => 'comments'],
            'spoiler'           => ['title' => 'سیستم اسپویلر', 'group' => 'comments'],
            'theory'            => ['title' => 'سیستم تئوری', 'group' => 'comments'],
            'review'            => ['title' => 'نقد و بررسی', 'group' => 'comments'],
            'review_helpfulness' => ['title' => 'رأی مفید/غیرمفید', 'group' => 'comments'],
            'pin_comment'       => ['title' => 'پین دیدگاه', 'group' => 'comments'],
            'mention_user'      => ['title' => 'منشن کاربر', 'group' => 'comments'],
            'edit_comment'      => ['title' => 'ویرایش دیدگاه', 'group' => 'comments'],
            'bad_word_filter'   => ['title' => 'فیلتر کلمات رکیک', 'group' => 'comments'],
            'comment_level'     => ['title' => 'سطح‌بندی دیدگاه‌گذار', 'group' => 'comments'],
            'sticker_gif'       => ['title' => 'استیکر و GIF در دیدگاه', 'group' => 'comments'],
            'star_rating'       => ['title' => 'امتیاز ستاره‌ای', 'group' => 'ratings'],
            'chapter_votes'     => ['title' => 'لایک/دیسلایک قسمت', 'group' => 'ratings'],
            'follow_author'     => ['title' => 'فالو نویسنده', 'group' => 'social'],
            'follow_novel'      => ['title' => 'دنبال کردن رمان', 'group' => 'social'],
            'library'           => ['title' => 'کتابخانه شخصی', 'group' => 'core'],
            'reading_history'   => ['title' => 'تاریخچه مطالعه', 'group' => 'core'],
            'coin_system'       => ['title' => 'سکه و خرید قسمت', 'group' => 'coins'],
            'user_authoring'    => ['title' => 'نویسندگی سمت کاربر', 'group' => 'authors'],
            'blog_posts'        => ['title' => 'وبلاگ/نوشته‌ها', 'group' => 'extras'],
            'announcement_banner' => ['title' => 'بنر اطلاعیه', 'group' => 'extras'],
            'advanced_reader'   => ['title' => 'حالت مطالعه پیشرفته', 'group' => 'core'],
            'social_share'      => ['title' => 'اشتراک‌گذاری اجتماعی', 'group' => 'social'],
            'similar_novels'    => ['title' => 'رمان‌های مشابه', 'group' => 'core'],
            'content_tags'      => ['title' => 'تگ‌های محتوایی', 'group' => 'core'],
            'dark_mode'         => ['title' => 'دارک‌مود', 'group' => 'core'],
        ];
    }

    /**
     * گروه‌بندی ماژول‌ها
     */
    public static function get_module_groups() {
        return [
            'core'     => 'هسته',
            'comments' => 'دیدگاه‌ها',
            'ratings'  => 'امتیازدهی',
            'social'   => 'اجتماعی',
            'authors'  => 'نویسندگان',
            'coins'    => 'سکه و اشتراک',
            'extras'   => 'امکانات اضافه',
        ];
    }
}