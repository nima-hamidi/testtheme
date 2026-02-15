<?php
/**
 * فایل: inc/meta-boxes.php
 * توضیح: متاباکس‌های رمان و قسمت در ادمین وردپرس
 * نسخه: 2.0.0
 * وابستگی: custom-post-types.php
 */

if (!defined('ABSPATH')) exit;

add_action('add_meta_boxes', 'novel_add_meta_boxes');
add_action('save_post', 'novel_save_meta_boxes', 10, 2);

function novel_add_meta_boxes() {
    // متاباکس رمان
    add_meta_box(
        'novel_details',
        'جزئیات رمان',
        'novel_details_meta_box',
        'novel',
        'normal',
        'high'
    );

    // متاباکس قسمت
    add_meta_box(
        'chapter_details',
        'جزئیات قسمت',
        'novel_chapter_meta_box',
        'chapter',
        'normal',
        'high'
    );
}

/**
 * متاباکس جزئیات رمان
 */
function novel_details_meta_box($post) {
    wp_nonce_field('novel_meta_nonce', 'novel_meta_nonce_field');

    $fields = [
        'novel_original_title'  => get_post_meta($post->ID, '_novel_original_title', true),
        'novel_author_name'     => get_post_meta($post->ID, '_novel_author_name', true),
        'novel_translator'      => get_post_meta($post->ID, '_novel_translator', true),
        'novel_origin_country'  => get_post_meta($post->ID, '_novel_origin_country', true),
        'novel_year'            => get_post_meta($post->ID, '_novel_year', true),
        'novel_total_chapters'  => get_post_meta($post->ID, '_novel_total_chapters', true),
        'novel_age_rating'      => get_post_meta($post->ID, '_novel_age_rating', true),
        'novel_is_premium'      => get_post_meta($post->ID, '_novel_is_premium', true),
        'novel_coin_price'      => get_post_meta($post->ID, '_novel_coin_price', true),
        'novel_free_chapters'   => get_post_meta($post->ID, '_novel_free_chapters', true),
        'novel_volume_id'       => get_post_meta($post->ID, '_novel_volume_id', true),
    ];
    ?>
    <style>
        .novel-meta-table { width: 100%; border-collapse: collapse; }
        .novel-meta-table td { padding: 10px; vertical-align: top; }
        .novel-meta-table td:first-child { width: 180px; font-weight: bold; }
        .novel-meta-table input[type="text"],
        .novel-meta-table input[type="number"],
        .novel-meta-table select { width: 100%; padding: 6px; }
    </style>
    <table class="novel-meta-table">
        <tr>
            <td><label for="novel_original_title">عنوان اصلی</label></td>
            <td><input type="text" id="novel_original_title" name="novel_original_title" 
                       value="<?php echo esc_attr($fields['novel_original_title']); ?>"></td>
        </tr>
        <tr>
            <td><label for="novel_author_name">نام نویسنده اصلی</label></td>
            <td><input type="text" id="novel_author_name" name="novel_author_name" 
                       value="<?php echo esc_attr($fields['novel_author_name']); ?>"></td>
        </tr>
        <tr>
            <td><label for="novel_translator">مترجم</label></td>
            <td><input type="text" id="novel_translator" name="novel_translator" 
                       value="<?php echo esc_attr($fields['novel_translator']); ?>"></td>
        </tr>
        <tr>
            <td><label for="novel_origin_country">کشور مبدأ</label></td>
            <td>
                <select id="novel_origin_country" name="novel_origin_country">
                    <option value="">انتخاب کنید</option>
                    <?php
                    $countries = ['کره جنوبی', 'چین', 'ژاپن', 'ایران', 'انگلیسی', 'سایر'];
                    foreach ($countries as $c) :
                        $selected = selected($fields['novel_origin_country'], $c, false);
                    ?>
                    <option value="<?php echo esc_attr($c); ?>" <?php echo $selected; ?>><?php echo esc_html($c); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td><label for="novel_year">سال انتشار</label></td>
            <td><input type="number" id="novel_year" name="novel_year" 
                       value="<?php echo esc_attr($fields['novel_year']); ?>" min="1300" max="1500"></td>
        </tr>
        <tr>
            <td><label for="novel_total_chapters">تعداد کل قسمت‌ها</label></td>
            <td><input type="number" id="novel_total_chapters" name="novel_total_chapters" 
                       value="<?php echo esc_attr($fields['novel_total_chapters']); ?>" min="0">
                <p class="description">اگر رمان تمام شده، تعداد کل را وارد کنید. ۰ = نامشخص</p>
            </td>
        </tr>
        <tr>
            <td><label for="novel_age_rating">رده سنی</label></td>
            <td>
                <select id="novel_age_rating" name="novel_age_rating">
                    <option value="">همه سنین</option>
                    <?php
                    $ratings = ['+13' => '۱۳+', '+15' => '۱۵+', '+17' => '۱۷+', '+18' => '۱۸+'];
                    foreach ($ratings as $val => $label) :
                        $selected = selected($fields['novel_age_rating'], $val, false);
                    ?>
                    <option value="<?php echo esc_attr($val); ?>" <?php echo $selected; ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td><label for="novel_is_premium">رمان پولی</label></td>
            <td>
                <label>
                    <input type="checkbox" id="novel_is_premium" name="novel_is_premium" 
                           value="1" <?php checked($fields['novel_is_premium'], '1'); ?>>
                    قسمت‌های این رمان نیاز به سکه دارند
                </label>
            </td>
        </tr>
        <tr>
            <td><label for="novel_coin_price">قیمت هر قسمت (سکه)</label></td>
            <td><input type="number" id="novel_coin_price" name="novel_coin_price" 
                       value="<?php echo esc_attr($fields['novel_coin_price']); ?>" min="0">
                <p class="description">قیمت پیش‌فرض هر قسمت. قابل تغییر برای هر قسمت جداگانه.</p>
            </td>
        </tr>
        <tr>
            <td><label for="novel_free_chapters">تعداد قسمت رایگان</label></td>
            <td><input type="number" id="novel_free_chapters" name="novel_free_chapters" 
                       value="<?php echo esc_attr($fields['novel_free_chapters']); ?>" min="0">
                <p class="description">تعداد اولین قسمت‌هایی که رایگان هستند</p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * متاباکس جزئیات قسمت
 */
function novel_chapter_meta_box($post) {
    wp_nonce_field('novel_chapter_nonce', 'novel_chapter_nonce_field');

    $novel_id      = get_post_meta($post->ID, '_chapter_novel_id', true);
    $chapter_number = get_post_meta($post->ID, '_chapter_number', true);
    $volume_id     = get_post_meta($post->ID, '_chapter_volume_id', true);
    $is_premium    = get_post_meta($post->ID, '_chapter_is_premium', true);
    $coin_price    = get_post_meta($post->ID, '_chapter_coin_price', true);
    $word_count    = get_post_meta($post->ID, '_chapter_word_count', true);

    // لیست رمان‌ها
    $novels = get_posts([
        'post_type'      => 'novel',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post_status'    => ['publish', 'draft', 'pending'],
    ]);
    ?>
    <table class="novel-meta-table">
        <tr>
            <td><label for="chapter_novel_id">رمان مادر</label></td>
            <td>
                <select id="chapter_novel_id" name="chapter_novel_id" required>
                    <option value="">انتخاب رمان...</option>
                    <?php foreach ($novels as $novel) : ?>
                    <option value="<?php echo esc_attr($novel->ID); ?>" 
                            <?php selected($novel_id, $novel->ID); ?>>
                        <?php echo esc_html($novel->post_title); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td><label for="chapter_number">شماره قسمت</label></td>
            <td><input type="number" id="chapter_number" name="chapter_number" 
                       value="<?php echo esc_attr($chapter_number); ?>" min="0" step="0.5"></td>
        </tr>
        <tr>
            <td><label for="chapter_volume_id">جلد/فصل</label></td>
            <td><input type="number" id="chapter_volume_id" name="chapter_volume_id" 
                       value="<?php echo esc_attr($volume_id); ?>" min="0">
                <p class="description">شماره جلد/فصل (۰ = بدون جلدبندی)</p>
            </td>
        </tr>
        <tr>
            <td><label for="chapter_is_premium">قسمت پولی</label></td>
            <td>
                <label>
                    <input type="checkbox" id="chapter_is_premium" name="chapter_is_premium" 
                           value="1" <?php checked($is_premium, '1'); ?>>
                    این قسمت نیاز به سکه دارد
                </label>
            </td>
        </tr>
        <tr>
            <td><label for="chapter_coin_price">قیمت سکه</label></td>
            <td><input type="number" id="chapter_coin_price" name="chapter_coin_price" 
                       value="<?php echo esc_attr($coin_price); ?>" min="0">
                <p class="description">اگر خالی باشد از قیمت پیش‌فرض رمان استفاده می‌شود</p>
            </td>
        </tr>
        <tr>
            <td>تعداد کلمات</td>
            <td>
                <strong><?php echo $word_count ? novel_format_number($word_count) : '—'; ?></strong>
                <p class="description">به صورت خودکار محاسبه می‌شود</p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * ذخیره متاباکس‌ها
 */
function novel_save_meta_boxes($post_id, $post) {
    // بررسی autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    // ═══ ذخیره متای رمان ═══
    if ($post->post_type === 'novel' && isset($_POST['novel_meta_nonce_field'])) {
        if (!wp_verify_nonce($_POST['novel_meta_nonce_field'], 'novel_meta_nonce')) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $text_fields = [
            'novel_original_title', 'novel_author_name', 'novel_translator',
            'novel_origin_country', 'novel_age_rating',
        ];
        foreach ($text_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
            }
        }

        $number_fields = ['novel_year', 'novel_total_chapters', 'novel_coin_price', 'novel_free_chapters'];
        foreach ($number_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, absint($_POST[$field]));
            }
        }

        $checkbox_fields = ['novel_is_premium'];
        foreach ($checkbox_fields as $field) {
            update_post_meta($post_id, '_' . $field, isset($_POST[$field]) ? '1' : '0');
        }

        // Cache invalidation
        delete_transient('novel_rankings_all');
        delete_transient('novel_latest_updates');
    }

    // ═══ ذخیره متای قسمت ═══
    if ($post->post_type === 'chapter' && isset($_POST['novel_chapter_nonce_field'])) {
        if (!wp_verify_nonce($_POST['novel_chapter_nonce_field'], 'novel_chapter_nonce')) return;
        if (!current_user_can('edit_post', $post_id)) return;

        if (isset($_POST['chapter_novel_id'])) {
            update_post_meta($post_id, '_chapter_novel_id', absint($_POST['chapter_novel_id']));
        }
        if (isset($_POST['chapter_number'])) {
            update_post_meta($post_id, '_chapter_number', floatval($_POST['chapter_number']));
        }
        if (isset($_POST['chapter_volume_id'])) {
            update_post_meta($post_id, '_chapter_volume_id', absint($_POST['chapter_volume_id']));
        }
        if (isset($_POST['chapter_coin_price'])) {
            update_post_meta($post_id, '_chapter_coin_price', absint($_POST['chapter_coin_price']));
        }
        update_post_meta($post_id, '_chapter_is_premium', isset($_POST['chapter_is_premium']) ? '1' : '0');

        // محاسبه تعداد کلمات
        $content = $post->post_content;
        $word_count = mb_str_word_count(wp_strip_all_tags($content));
        update_post_meta($post_id, '_chapter_word_count', $word_count);

        // Cache invalidation
        $novel_id = absint($_POST['chapter_novel_id'] ?? 0);
        if ($novel_id) {
            delete_transient('novel_chapter_count_' . $novel_id);
            delete_transient('novel_latest_updates');
        }
    }
}

/**
 * تعداد کلمات برای رشته فارسی
 */
if (!function_exists('mb_str_word_count')) {
    function mb_str_word_count($string) {
        $string = trim($string);
        if (empty($string)) return 0;
        return count(preg_split('/[\s\n\r]+/u', $string, -1, PREG_SPLIT_NO_EMPTY));
    }
}