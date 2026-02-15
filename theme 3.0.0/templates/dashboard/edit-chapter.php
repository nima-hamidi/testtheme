<?php
/**
 * Dashboard: Edit Chapter Form
 * 
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) return;

$user_id    = get_current_user_id();
$chapter_id = absint($_GET['chapter_id'] ?? 0);

if (!$chapter_id) {
    echo '<div class="form-alert form-alert--error">قسمتی انتخاب نشده.</div>';
    return;
}

$chapter = get_post($chapter_id);
if (!$chapter || $chapter->post_type !== 'chapter' || (int) $chapter->post_author !== $user_id) {
    echo '<div class="form-alert form-alert--error">دسترسی ندارید.</div>';
    return;
}

$novel_id      = get_post_meta($chapter_id, 'chapter_novel_id', true);
$chapter_num   = get_post_meta($chapter_id, 'chapter_number', true);
$chapter_title = get_post_meta($chapter_id, 'chapter_title', true);
$chapter_vol   = get_post_meta($chapter_id, 'chapter_volume', true);
$is_vip        = get_post_meta($chapter_id, 'chapter_is_vip', true);
$coin_price    = get_post_meta($chapter_id, 'chapter_coin_price', true) ?: 5;
$recap         = get_post_meta($chapter_id, 'chapter_recap', true);
$novel_title   = get_the_title($novel_id);

// Volumes
$volumes = get_post_meta($novel_id, 'novel_volumes', true);
if (!is_array($volumes)) $volumes = [];

$form_errors  = [];
$form_success = '';

if (isset($_POST['chapter_update']) && wp_verify_nonce($_POST['chapter_edit_nonce'], 'novel_edit_chapter_' . $chapter_id)) {
    
    $new_title   = sanitize_text_field($_POST['chapter_title'] ?? '');
    $new_vol     = sanitize_text_field($_POST['chapter_volume'] ?? '');
    $new_content = wp_kses_post($_POST['chapter_content'] ?? '');
    $new_vip     = isset($_POST['chapter_is_vip']) ? '1' : '';
    $new_price   = max(1, absint($_POST['chapter_coin_price'] ?? 5));
    $new_recap   = mb_substr(sanitize_textarea_field($_POST['chapter_recap'] ?? ''), 0, 200);
    
    if (mb_strlen(wp_strip_all_tags($new_content)) < 500) {
        $form_errors[] = 'محتوا باید حداقل ۵۰۰ کاراکتر باشد.';
    }
    
    if (empty($form_errors)) {
        $auto_title = $novel_title . ' - قسمت ' . $chapter_num;
        if ($new_title) $auto_title .= ': ' . $new_title;
        
        wp_update_post([
            'ID'           => $chapter_id,
            'post_title'   => $auto_title,
            'post_content' => $new_content,
        ]);
        
        update_post_meta($chapter_id, 'chapter_title', $new_title);
        update_post_meta($chapter_id, 'chapter_volume', $new_vol);
        update_post_meta($chapter_id, 'chapter_is_vip', $new_vip);
        update_post_meta($chapter_id, 'chapter_coin_price', $new_price);
        update_post_meta($chapter_id, 'chapter_recap', $new_recap);
        
        $form_success = 'قسمت با موفقیت ویرایش شد.';
    }
}
?>

<div class="dashboard-form-page">
    <h2 class="dashboard-form-title">
        ✏️ ویرایش قسمت <?php echo esc_html($chapter_num); ?>
        <small>— <?php echo esc_html($novel_title); ?></small>
    </h2>
    
    <?php if (!empty($form_errors)) : ?>
        <div class="form-alert form-alert--error">
            <ul><?php foreach ($form_errors as $e) : ?><li><?php echo esc_html($e); ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    
    <?php if ($form_success) : ?>
        <div class="form-alert form-alert--success">✅ <?php echo esc_html($form_success); ?></div>
    <?php endif; ?>
    
    <form method="post" class="novel-frontend-form" novalidate>
        <?php wp_nonce_field('novel_edit_chapter_' . $chapter_id, 'chapter_edit_nonce'); ?>
        
        <div class="form-row-2col">
            <div class="form-group">
                <label class="form-label">شماره قسمت</label>
                <input type="number" class="form-input" value="<?php echo esc_attr($chapter_num); ?>" disabled />
                <span class="form-hint">شماره قسمت قابل تغییر نیست</span>
            </div>
            <div class="form-group">
                <label class="form-label">عنوان قسمت</label>
                <input type="text" name="chapter_title" class="form-input" 
                       value="<?php echo esc_attr($chapter_title); ?>" placeholder="اختیاری" />
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">جلد</label>
            <select name="chapter_volume" class="form-select">
                <option value="">بدون جلد</option>
                <?php foreach ($volumes as $vol) : ?>
                    <option value="<?php echo esc_attr($vol['id']); ?>" <?php selected($chapter_vol, $vol['id']); ?>>
                        <?php echo esc_html($vol['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">محتوا</label>
            <?php wp_editor($chapter->post_content, 'chapter_content', [
                'textarea_rows' => 20, 'media_buttons' => false, 'teeny' => true,
            ]); ?>
        </div>
        
        <div class="form-group">
            <label class="form-checkbox-label">
                <input type="checkbox" name="chapter_is_vip" value="1" <?php checked($is_vip, '1'); ?> id="editVip" />
                <span>👑 VIP</span>
            </label>
            <div id="editVipPrice" style="<?php echo $is_vip ? '' : 'display:none;'; ?>">
                <input type="number" name="chapter_coin_price" class="form-input" min="1" style="width:120px;"
                       value="<?php echo esc_attr($coin_price); ?>" />
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">خلاصه قسمت قبل</label>
            <textarea name="chapter_recap" class="form-textarea" rows="2" maxlength="200"><?php echo esc_textarea($recap); ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" name="chapter_update" value="1" class="btn-submit-novel">💾 ذخیره</button>
            <a href="<?php echo home_url('/dashboard/?tab=my-chapters'); ?>" class="btn-draft-novel">انصراف</a>
        </div>
    </form>
</div>

<script>
document.getElementById('editVip').addEventListener('change', function() {
    document.getElementById('editVipPrice').style.display = this.checked ? 'block' : 'none';
});
</script>