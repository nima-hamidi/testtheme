<?php
/**
 * Dashboard: Edit Novel Form
 * 
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    return;
}

$user_id  = get_current_user_id();
$novel_id = absint($_GET['novel_id'] ?? 0);

if (!$novel_id) {
    echo '<div class="form-alert form-alert--error">رمانی انتخاب نشده.</div>';
    return;
}

$novel = get_post($novel_id);
if (!$novel || $novel->post_type !== 'novel' || (int) $novel->post_author !== $user_id) {
    echo '<div class="form-alert form-alert--error">دسترسی ندارید یا رمان یافت نشد.</div>';
    return;
}

$form_errors  = [];
$form_success = '';

// Handle update
if (isset($_POST['novel_update']) && wp_verify_nonce($_POST['novel_edit_nonce'], 'novel_edit_novel_' . $novel_id)) {
    
    $title_fa     = sanitize_text_field($_POST['novel_title_fa'] ?? '');
    $title_en     = sanitize_text_field($_POST['novel_title_en'] ?? '');
    $novel_type   = sanitize_text_field($_POST['novel_type'] ?? 'web_novel');
    $author_orig  = sanitize_text_field($_POST['novel_original_author'] ?? '');
    $translator   = sanitize_text_field($_POST['novel_translator'] ?? '');
    $country      = sanitize_text_field($_POST['novel_country'] ?? 'japan');
    $status_term  = sanitize_text_field($_POST['novel_pub_status'] ?? 'ongoing');
    $description  = wp_kses_post($_POST['novel_description'] ?? '');
    $genres       = isset($_POST['novel_genres']) ? array_map('absint', $_POST['novel_genres']) : [];
    $tags         = isset($_POST['novel_tags']) ? array_map('absint', $_POST['novel_tags']) : [];
    
    if (empty($title_fa)) $form_errors[] = 'نام فارسی الزامی است.';
    if (empty($title_en)) $form_errors[] = 'نام انگلیسی الزامی است.';
    if (empty($author_orig)) $form_errors[] = 'نام نویسنده الزامی است.';
    if (empty($genres)) $form_errors[] = 'حداقل یک ژانر انتخاب کنید.';
    if (count($genres) > 5) $form_errors[] = 'حداکثر ۵ ژانر.';
    
    if (empty($form_errors)) {
        wp_update_post([
            'ID'           => $novel_id,
            'post_title'   => $title_fa,
            'post_content' => $description,
        ]);
        
        update_post_meta($novel_id, 'novel_english_name', $title_en);
        update_post_meta($novel_id, 'novel_type', $novel_type);
        update_post_meta($novel_id, 'novel_original_author', $author_orig);
        update_post_meta($novel_id, 'novel_translator', $translator);
        update_post_meta($novel_id, 'novel_country', $country);
        
        if (!empty($genres)) wp_set_post_terms($novel_id, $genres, 'genre');
        if (!empty($tags)) wp_set_post_terms($novel_id, $tags, 'novel_tag');
        else wp_set_post_terms($novel_id, [], 'novel_tag');
        
        if ($status_term) {
            $term = get_term_by('slug', $status_term, 'novel_status');
            if ($term) wp_set_post_terms($novel_id, [$term->term_id], 'novel_status');
        }
        
        // Handle cover update
        if (!empty($_FILES['novel_cover']['name'])) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            
            $attachment_id = media_handle_upload('novel_cover', $novel_id);
            if (!is_wp_error($attachment_id)) {
                set_post_thumbnail($novel_id, $attachment_id);
            }
        }
        
        $form_success = 'رمان با موفقیت ویرایش شد.';
        $novel = get_post($novel_id); // Refresh
    }
}

// Load current data
$english_name    = get_post_meta($novel_id, 'novel_english_name', true);
$novel_type      = get_post_meta($novel_id, 'novel_type', true) ?: 'web_novel';
$original_author = get_post_meta($novel_id, 'novel_original_author', true);
$translator      = get_post_meta($novel_id, 'novel_translator', true);
$country         = get_post_meta($novel_id, 'novel_country', true) ?: 'japan';
$current_genres  = wp_list_pluck(wp_get_post_terms($novel_id, 'genre'), 'term_id');
$current_tags    = wp_list_pluck(wp_get_post_terms($novel_id, 'novel_tag'), 'term_id');
$current_status  = wp_get_post_terms($novel_id, 'novel_status');
$current_status_slug = !empty($current_status) ? $current_status[0]->slug : 'ongoing';

$all_genres  = get_terms(['taxonomy' => 'genre', 'hide_empty' => false]);
$all_tags    = get_terms(['taxonomy' => 'novel_tag', 'hide_empty' => false]);
$countries   = novel_get_country_labels_plain();
?>

<div class="dashboard-form-page">
    <h2 class="dashboard-form-title">✏️ ویرایش رمان: <?php echo esc_html($novel->post_title); ?></h2>
    
    <?php if (!empty($form_errors)) : ?>
        <div class="form-alert form-alert--error">
            <ul><?php foreach ($form_errors as $err) : ?><li><?php echo esc_html($err); ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    
    <?php if ($form_success) : ?>
        <div class="form-alert form-alert--success">✅ <?php echo esc_html($form_success); ?></div>
    <?php endif; ?>
    
    <form method="post" enctype="multipart/form-data" class="novel-frontend-form" novalidate>
        <?php wp_nonce_field('novel_edit_novel_' . $novel_id, 'novel_edit_nonce'); ?>
        
        <div class="form-group">
            <label class="form-label">نام فارسی <span class="required">*</span></label>
            <input type="text" name="novel_title_fa" class="form-input" required 
                   value="<?php echo esc_attr($novel->post_title); ?>" />
        </div>
        
        <div class="form-group">
            <label class="form-label">نام انگلیسی <span class="required">*</span></label>
            <input type="text" name="novel_title_en" class="form-input form-input--ltr" required 
                   value="<?php echo esc_attr($english_name); ?>" />
        </div>
        
        <div class="form-group">
            <label class="form-label">نوع رمان</label>
            <div class="type-card-selector">
                <label class="type-card <?php echo $novel_type === 'web_novel' ? 'selected' : ''; ?>">
                    <input type="radio" name="novel_type" value="web_novel" <?php checked($novel_type, 'web_novel'); ?> />
                    <span class="type-card__icon">💻</span><span class="type-card__title">WN</span>
                </label>
                <label class="type-card <?php echo $novel_type === 'light_novel' ? 'selected' : ''; ?>">
                    <input type="radio" name="novel_type" value="light_novel" <?php checked($novel_type, 'light_novel'); ?> />
                    <span class="type-card__icon">📕</span><span class="type-card__title">LN</span>
                </label>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">نویسنده اصلی <span class="required">*</span></label>
            <input type="text" name="novel_original_author" class="form-input" required 
                   value="<?php echo esc_attr($original_author); ?>" />
        </div>
        
        <div class="form-group">
            <label class="form-label">مترجم</label>
            <input type="text" name="novel_translator" class="form-input" 
                   value="<?php echo esc_attr($translator); ?>" />
        </div>
        
        <div class="form-group">
            <label class="form-label">کشور</label>
            <select name="novel_country" class="form-select">
                <?php foreach ($countries as $val => $lbl) : ?>
                    <option value="<?php echo esc_attr($val); ?>" <?php selected($country, $val); ?>><?php echo esc_html($lbl); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">ژانرها <span class="required">*</span></label>
            <div class="genre-toggle-grid" id="genreGrid">
                <?php foreach ($all_genres as $genre) : 
                    $color = get_term_meta($genre->term_id, 'genre_color', true) ?: '#6366f1';
                    $checked = in_array($genre->term_id, $current_genres);
                ?>
                    <label class="genre-toggle <?php echo $checked ? 'checked' : ''; ?>" style="--gc: <?php echo esc_attr($color); ?>;">
                        <input type="checkbox" name="novel_genres[]" value="<?php echo $genre->term_id; ?>" <?php checked($checked); ?> />
                        <span class="genre-toggle__text"><?php echo esc_html($genre->name); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">تگ‌ها</label>
            <div class="tags-toggle-grid">
                <?php foreach ($all_tags as $tag) : 
                    $checked = in_array($tag->term_id, $current_tags);
                ?>
                    <label class="tag-toggle <?php echo $checked ? 'checked' : ''; ?>">
                        <input type="checkbox" name="novel_tags[]" value="<?php echo $tag->term_id; ?>" <?php checked($checked); ?> />
                        <span class="tag-toggle__text"><?php echo esc_html($tag->name); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">وضعیت</label>
            <select name="novel_pub_status" class="form-select">
                <option value="ongoing" <?php selected($current_status_slug, 'ongoing'); ?>>در حال انتشار</option>
                <option value="completed" <?php selected($current_status_slug, 'completed'); ?>>تکمیل شده</option>
                <option value="hiatus" <?php selected($current_status_slug, 'hiatus'); ?>>متوقف شده</option>
                <option value="dropped" <?php selected($current_status_slug, 'dropped'); ?>>رها شده</option>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">خلاصه</label>
            <textarea name="novel_description" class="form-textarea" rows="6" maxlength="2000"><?php echo esc_textarea($novel->post_content); ?></textarea>
        </div>
        
        <div class="form-group">
            <label class="form-label">تصویر جلد</label>
            <div class="cover-upload">
                <div class="cover-preview" id="coverPreview">
                    <?php if (has_post_thumbnail($novel_id)) : ?>
                        <?php echo get_the_post_thumbnail($novel_id, 'medium', ['style' => 'max-height:200px;']); ?>
                    <?php else : ?>
                        <span class="cover-placeholder">📷 انتخاب تصویر</span>
                    <?php endif; ?>
                </div>
                <input type="file" name="novel_cover" accept="image/jpeg,image/png,image/webp" class="cover-input" />
                <p class="form-hint">تصویر جدید جایگزین فعلی می‌شود.</p>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" name="novel_update" value="1" class="btn-submit-novel">💾 ذخیره تغییرات</button>
            <a href="<?php echo home_url('/dashboard/?tab=my-novels'); ?>" class="btn-draft-novel">انصراف</a>
        </div>
    </form>
</div>