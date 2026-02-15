<?php
/**
 * Avatar Picker Component
 *
 * Used inside dashboard profile edit tab.
 * Can be included inline or loaded via AJAX.
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

$user_id = get_current_user_id();
$current_slug = Novel_Avatars::get_user_avatar_slug($user_id);
$current_url = Novel_Avatars::get_avatar_url_static($user_id);
$all_avatars = Novel_Avatars::get_all_avatars();

// Extract current avatar number
preg_match('/avatar-(\d+)/', $current_slug, $m);
$current_id = isset($m[1]) ? (int) $m[1] : 1;
?>

<div class="avatar-picker" id="avatarPicker">
    <!-- Current Avatar Preview -->
    <div class="avatar-picker__current">
        <div class="avatar-picker__preview">
            <img
                src="<?php echo esc_url($current_url); ?>"
                alt="آواتار فعلی"
                class="avatar-picker__preview-img"
                id="avatarPreviewImg"
                width="120"
                height="120"
            >
            <span class="avatar-picker__badge">فعلی</span>
        </div>
        <h3 class="avatar-picker__title">انتخاب آواتار</h3>
        <p class="avatar-picker__subtitle">یکی از آواتارهای زیر را انتخاب کنید</p>
    </div>

    <!-- Search / Filter (optional for 114 items) -->
    <div class="avatar-picker__filter">
        <div class="avatar-picker__count">
            <span id="avatarCount"><?php echo Novel_Avatars::TOTAL_AVATARS; ?></span> آواتار موجود
        </div>
    </div>

    <!-- Avatar Grid -->
    <div class="avatar-picker__grid" id="avatarGrid">
        <?php foreach ($all_avatars as $avatar) : ?>
            <button
                type="button"
                class="avatar-picker__item <?php echo ($avatar['id'] === $current_id) ? 'is-active is-current' : ''; ?>"
                data-avatar-id="<?php echo esc_attr($avatar['id']); ?>"
                data-avatar-slug="<?php echo esc_attr($avatar['slug']); ?>"
                data-avatar-url="<?php echo esc_url($avatar['url']); ?>"
                aria-label="آواتار <?php echo esc_attr($avatar['id']); ?>"
                title="آواتار <?php echo esc_attr($avatar['id']); ?>"
            >
                <img
                    src="<?php echo esc_url($avatar['url']); ?>"
                    alt="آواتار <?php echo esc_attr($avatar['id']); ?>"
                    width="64"
                    height="64"
                    loading="lazy"
                >
                <span class="avatar-picker__check">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </span>
                <?php if ($avatar['id'] === $current_id) : ?>
                    <span class="avatar-picker__current-label">فعلی</span>
                <?php endif; ?>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- Save Button -->
    <div class="avatar-picker__actions">
        <button type="button" class="btn btn--primary avatar-picker__save" id="saveAvatarBtn" disabled>
            <span class="btn-text">ذخیره آواتار</span>
            <span class="btn-loading" style="display:none;">
                <svg class="spinner" width="18" height="18" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="31.4" stroke-dashoffset="10">
                        <animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="0.8s" repeatCount="indefinite"/>
                    </circle>
                </svg>
                <span>در حال ذخیره...</span>
            </span>
        </button>
    </div>
</div>