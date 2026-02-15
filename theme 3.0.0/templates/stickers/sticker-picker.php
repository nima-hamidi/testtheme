<?php
/**
 * Sticker & GIF Picker
 *
 * Inline component inside comment form.
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

// Scan sticker directories
$sticker_dir = get_template_directory() . '/assets/stickers/';
$gif_dir     = get_template_directory() . '/assets/gifs/';
$sticker_uri = get_template_directory_uri() . '/assets/stickers/';
$gif_uri     = get_template_directory_uri() . '/assets/gifs/';

$sticker_cats = [];
if (is_dir($sticker_dir)) {
    $dirs = glob($sticker_dir . '*', GLOB_ONLYDIR);
    foreach ($dirs as $dir) {
        $cat_name = basename($dir);
        $files = glob($dir . '/*.svg');
        if (!empty($files)) {
            $items = [];
            foreach ($files as $f) {
                $items[] = [
                    'name' => pathinfo($f, PATHINFO_FILENAME),
                    'url'  => $sticker_uri . $cat_name . '/' . basename($f),
                    'code' => '[sticker:' . $cat_name . '/' . pathinfo($f, PATHINFO_FILENAME) . ']',
                ];
            }
            $sticker_cats[$cat_name] = $items;
        }
    }
}

$gif_cats = [];
if (is_dir($gif_dir)) {
    $dirs = glob($gif_dir . '*', GLOB_ONLYDIR);
    foreach ($dirs as $dir) {
        $cat_name = basename($dir);
        $files = glob($dir . '/*.gif');
        if (!empty($files)) {
            $items = [];
            foreach ($files as $f) {
                $items[] = [
                    'name' => pathinfo($f, PATHINFO_FILENAME),
                    'url'  => $gif_uri . $cat_name . '/' . basename($f),
                    'code' => '[gif:' . $cat_name . '/' . pathinfo($f, PATHINFO_FILENAME) . ']',
                ];
            }
            $gif_cats[$cat_name] = $items;
        }
    }
}

$cat_labels = [
    'emotions'   => 'هیجان‌ها',
    'reactions'  => 'واکنش‌ها',
    'characters' => 'شخصیت‌ها',
    'custom'     => 'سفارشی',
    'funny'      => 'خنده‌دار',
];
?>

<div class="sticker-picker" id="stickerPicker">
    <!-- Tabs: Stickers | GIF -->
    <div class="sticker-picker__tabs">
        <button type="button" class="sticker-picker__tab is-active" data-picker-tab="stickers">استیکرها</button>
        <button type="button" class="sticker-picker__tab" data-picker-tab="gifs">GIF</button>
    </div>

    <!-- Search -->
    <div class="sticker-picker__search">
        <input type="text" class="sticker-picker__search-input" placeholder="🔍 جستجو..." id="stickerSearch">
    </div>

    <!-- Stickers Panel -->
    <div class="sticker-picker__panel" id="stickerPanel" data-picker-panel="stickers">
        <?php if (empty($sticker_cats)) : ?>
            <p class="sticker-picker__empty">استیکری موجود نیست.</p>
        <?php else : ?>
            <?php foreach ($sticker_cats as $cat => $items) : ?>
                <div class="sticker-picker__category">
                    <h4 class="sticker-picker__cat-title"><?php echo esc_html($cat_labels[$cat] ?? $cat); ?></h4>
                    <div class="sticker-picker__grid">
                        <?php foreach ($items as $item) : ?>
                            <button
                                type="button"
                                class="sticker-picker__item"
                                data-code="<?php echo esc_attr($item['code']); ?>"
                                data-name="<?php echo esc_attr($item['name']); ?>"
                                title="<?php echo esc_attr($item['name']); ?>"
                            >
                                <img src="<?php echo esc_url($item['url']); ?>" alt="<?php echo esc_attr($item['name']); ?>" loading="lazy">
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- GIF Panel -->
    <div class="sticker-picker__panel" id="gifPanel" data-picker-panel="gifs" style="display:none;">
        <?php if (empty($gif_cats)) : ?>
            <p class="sticker-picker__empty">GIF موجود نیست.</p>
        <?php else : ?>
            <?php foreach ($gif_cats as $cat => $items) : ?>
                <div class="sticker-picker__category">
                    <h4 class="sticker-picker__cat-title"><?php echo esc_html($cat_labels[$cat] ?? $cat); ?></h4>
                    <div class="sticker-picker__grid sticker-picker__grid--gifs">
                        <?php foreach ($items as $item) : ?>
                            <button
                                type="button"
                                class="sticker-picker__item sticker-picker__item--gif"
                                data-code="<?php echo esc_attr($item['code']); ?>"
                                data-name="<?php echo esc_attr($item['name']); ?>"
                                title="<?php echo esc_attr($item['name']); ?>"
                            >
                                <img src="<?php echo esc_url($item['url']); ?>" alt="<?php echo esc_attr($item['name']); ?>" loading="lazy">
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Limit notice -->
    <div class="sticker-picker__notice">
        حداکثر ۱ استیکر/GIF در هر دیدگاه
    </div>
</div>