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