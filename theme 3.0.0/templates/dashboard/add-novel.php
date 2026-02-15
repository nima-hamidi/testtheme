<?php
/**
 * Dashboard: Add Novel Form
 * 
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check if user writing is enabled
if (!get_option('novel_user_writing', true)) {
    echo '<div class="dashboard-notice notice-warning">ارسال رمان توسط کاربران غیرفعال است.</div>';
    return;
}

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(home_url('/dashboard/?tab=add-novel')));
    exit;
}

$user_id = get_current_user_id();

// Handle form submission
$form_errors  = [];
$form_success = '';

if (isset($_POST['novel_submit']) && wp_verify_nonce($_POST['novel_form_nonce'], 'novel_add_novel')) {
    
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
    
    // Validation
    if (empty($title_fa)) {
        $form_errors[] = 'نام فارسی رمان الزامی است.';
    } elseif (!preg_match('/^[\x{0600}-\x{06FF}\x{0660}-\x{0669}\s\x{200C}\x{0640}\!\?\.\,\:\;\(\)\-\d]+$/u', $title_fa)) {
        $form_errors[] = 'نام فارسی فقط باید شامل حروف فارسی باشد.';
    }
    
    if (empty($title_en)) {
        $form_errors[] = 'نام انگلیسی رمان الزامی است.';
    } elseif (!preg_match('/^[a-zA-Z0-9\s\-\':.,!?]+$/', $title_en)) {
        $form_errors[] = 'نام انگلیسی فقط باید شامل حروف انگلیسی باشد.';
    }
    
    if (empty($author_orig)) {
        $form_errors[] = 'نام نویسنده اصلی الزامی است.';
    }
    
    if (empty($genres)) {
        $form_errors[] = 'حداقل یک ژانر انتخاب کنید.';
    } elseif (count($genres) > 5) {
        $form_errors[] = 'حداکثر ۵ ژانر مجاز است.';
    }
    
    if (mb_strlen($description) > 2000) {
        $form_errors[] = 'خلاصه نباید بیش از ۲۰۰۰ کاراکتر باشد.';
    }
    
    if (!in_array($novel_type, ['web_novel', 'light_novel'])) {
        $novel_type = 'web_novel';
    }
    
    // Handle cover image
    $thumbnail_id = 0;
    if (!empty($_FILES['novel_cover']['name'])) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($_FILES['novel_cover']['type'], $allowed)) {
            $form_errors[] = 'فرمت تصویر باید JPG، PNG یا WebP باشد.';
        } elseif ($_FILES['novel_cover']['size'] > 2 * 1024 * 1024) {
            $form_errors[] = 'حجم تصویر نباید بیش از ۲ مگابایت باشد.';
        } else {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            
            $attachment_id = media_handle_upload('novel_cover', 0);
            if (is_wp_error($attachment_id)) {
                $form_errors[] = 'خطا در آپلود تصویر: ' . $attachment_id->get_error_message();
            } else {
                $thumbnail_id = $attachment_id;
            }
        }
    }
    
    // Create novel
    if (empty($form_errors)) {
        $post_data = [
            'post_title'   => $title_fa,
            'post_content' => $description,
            'post_type'    => 'novel',
            'post_status'  => 'pending', // Needs admin approval
            'post_author'  => $user_id,
        ];
        
        $novel_id = wp_insert_post($post_data);
        
        if (is_wp_error($novel_id)) {
            $form_errors[] = 'خطا در ثبت رمان: ' . $novel_id->get_error_message();
        } else {
            // Meta
            update_post_meta($novel_id, 'novel_english_name', $title_en);
            update_post_meta($novel_id, 'novel_type', $novel_type);
            update_post_meta($novel_id, 'novel_original_author', $author_orig);
            update_post_meta($novel_id, 'novel_translator', $translator);
            update_post_meta($novel_id, 'novel_country', $country);
            update_post_meta($novel_id, 'novel_views', 0);
            update_post_meta($novel_id, 'novel_avg_rating', 0);
            update_post_meta($novel_id, 'novel_rating_count', 0);
            update_post_meta($novel_id, 'novel_follow_count', 0);
            update_post_meta($novel_id, 'novel_bookmark_count', 0);
            
            // Thumbnail
            if ($thumbnail_id) {
                set_post_thumbnail($novel_id, $thumbnail_id);
            }
            
            // Taxonomies
            if (!empty($genres)) {
                wp_set_post_terms($novel_id, $genres, 'genre');
            }
            if (!empty($tags)) {
                wp_set_post_terms($novel_id, $tags, 'novel_tag');
            }
            if ($status_term) {
                $term = get_term_by('slug', $status_term, 'novel_status');
                if ($term) {
                    wp_set_post_terms($novel_id, [$term->term_id], 'novel_status');
                }
            }
            
            $form_success = 'رمان شما با موفقیت ارسال شد و پس از بررسی ادمین منتشر خواهد شد.';
            
            // Notify admin
            $admin_email = get_option('admin_email');
            wp_mail(
                $admin_email,
                'رمان جدید: ' . $title_fa,
                sprintf('رمان جدید "%s" توسط %s ارسال شد و منتظر بررسی است.', $title_fa, wp_get_current_user()->display_name)
            );
        }
    }
}

// Get genres and tags for form
$all_genres = get_terms(['taxonomy' => 'genre', 'hide_empty' => false, 'orderby' => 'name']);
$all_tags   = get_terms(['taxonomy' => 'novel_tag', 'hide_empty' => false, 'orderby' => 'name']);
$countries  = novel_get_country_labels_plain();
?>

<div class="dashboard-form-page">
    <h2 class="dashboard-form-title">📝 افزودن رمان جدید</h2>
    
    <?php if (!empty($form_errors)) : ?>
        <div class="form-alert form-alert--error">
            <strong>⚠️ خطا:</strong>
            <ul>
                <?php foreach ($form_errors as $err) : ?>
                    <li><?php echo esc_html($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if ($form_success) : ?>
        <div class="form-alert form-alert--success">
            <strong>✅</strong> <?php echo esc_html($form_success); ?>
            <br><a href="<?php echo home_url('/dashboard/?tab=my-novels'); ?>">بازگشت به رمان‌های من</a>
        </div>
    <?php else : ?>
    
    <form method="post" enctype="multipart/form-data" class="novel-frontend-form" id="addNovelForm" novalidate>
        <?php wp_nonce_field('novel_add_novel', 'novel_form_nonce'); ?>
        
        <!-- ① نام فارسی -->
        <div class="form-group">
            <label for="novel_title_fa" class="form-label">
                نام فارسی رمان <span class="required">*</span>
            </label>
            <input type="text" id="novel_title_fa" name="novel_title_fa" 
                   class="form-input" required
                   value="<?php echo esc_attr($_POST['novel_title_fa'] ?? ''); ?>"
                   placeholder="نام فارسی رمان" 
                   data-validate="persian" />
            <span class="form-hint" id="titleFaHint"></span>
        </div>
        
        <!-- ② نام انگلیسی -->
        <div class="form-group">
            <label for="novel_title_en" class="form-label">
                نام انگلیسی / جایگزین <span class="required">*</span>
            </label>
            <input type="text" id="novel_title_en" name="novel_title_en" 
                   class="form-input form-input--ltr" required
                   value="<?php echo esc_attr($_POST['novel_title_en'] ?? ''); ?>"
                   placeholder="English Novel Name"
                   data-validate="english" />
            <span class="form-hint" id="titleEnHint"></span>
        </div>
        
        <!-- ③ نوع رمان -->
        <div class="form-group">
            <label class="form-label">نوع رمان <span class="required">*</span></label>
            <div class="type-card-selector">
                <label class="type-card <?php echo ($_POST['novel_type'] ?? 'web_novel') === 'web_novel' ? 'selected' : ''; ?>">
                    <input type="radio" name="novel_type" value="web_novel" 
                           <?php checked(($_POST['novel_type'] ?? 'web_novel'), 'web_novel'); ?> />
                    <span class="type-card__icon">💻</span>
                    <span class="type-card__title">وب ناول (WN)</span>
                    <span class="type-card__desc">منتشر شده آنلاین</span>
                </label>
                <label class="type-card <?php echo ($_POST['novel_type'] ?? '') === 'light_novel' ? 'selected' : ''; ?>">
                    <input type="radio" name="novel_type" value="light_novel" 
                           <?php checked(($_POST['novel_type'] ?? ''), 'light_novel'); ?> />
                    <span class="type-card__icon">📕</span>
                    <span class="type-card__title">لایت ناول (LN)</span>
                    <span class="type-card__desc">چاپ شده با تصویرسازی</span>
                </label>
            </div>
        </div>
        
        <!-- ④ نویسنده اصلی -->
        <div class="form-group">
            <label for="novel_original_author" class="form-label">
                نویسنده اصلی <span class="required">*</span>
            </label>
            <input type="text" id="novel_original_author" name="novel_original_author" 
                   class="form-input" required
                   value="<?php echo esc_attr($_POST['novel_original_author'] ?? ''); ?>"
                   placeholder="نام نویسنده اصلی اثر" />
        </div>
        
        <!-- ⑤ مترجم -->
        <div class="form-group">
            <label for="novel_translator" class="form-label">مترجم</label>
            <input type="text" id="novel_translator" name="novel_translator" 
                   class="form-input"
                   value="<?php echo esc_attr($_POST['novel_translator'] ?? ''); ?>"
                   placeholder="نام مترجم (اختیاری)" />
        </div>
        
        <!-- ⑥ کشور مبدأ -->
        <div class="form-group">
            <label for="novel_country" class="form-label">کشور مبدأ</label>
            <select id="novel_country" name="novel_country" class="form-select">
                <?php foreach ($countries as $val => $lbl) : ?>
                    <option value="<?php echo esc_attr($val); ?>" 
                            <?php selected(($_POST['novel_country'] ?? 'japan'), $val); ?>>
                        <?php echo esc_html($lbl); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- ⑦ ژانرها -->
        <div class="form-group">
            <label class="form-label">ژانرها <span class="required">*</span> <small>(حداقل ۱ - حداکثر ۵)</small></label>
            <div class="genre-toggle-grid" id="genreGrid">
                <?php if (!empty($all_genres) && !is_wp_error($all_genres)) : ?>
                    <?php foreach ($all_genres as $genre) : 
                        $color = get_term_meta($genre->term_id, 'genre_color', true) ?: '#6366f1';
                        $checked = in_array($genre->term_id, $_POST['novel_genres'] ?? []);
                    ?>
                        <label class="genre-toggle <?php echo $checked ? 'checked' : ''; ?>" 
                               style="--gc: <?php echo esc_attr($color); ?>;">
                            <input type="checkbox" name="novel_genres[]" 
                                   value="<?php echo $genre->term_id; ?>"
                                   <?php checked($checked); ?> />
                            <span class="genre-toggle__text"><?php echo esc_html($genre->name); ?></span>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <span class="form-hint genre-counter">انتخاب‌شده: <span id="genreCount">0</span>/5</span>
        </div>
        
        <!-- ⑧ تگ‌ها -->
        <div class="form-group">
            <label class="form-label">تگ‌ها <small>(اختیاری)</small></label>
            <div class="tags-toggle-grid" id="tagsGrid">
                <?php if (!empty($all_tags) && !is_wp_error($all_tags)) : ?>
                    <?php foreach ($all_tags as $tag) : 
                        $checked = in_array($tag->term_id, $_POST['novel_tags'] ?? []);
                    ?>
                        <label class="tag-toggle <?php echo $checked ? 'checked' : ''; ?>">
                            <input type="checkbox" name="novel_tags[]" 
                                   value="<?php echo $tag->term_id; ?>"
                                   <?php checked($checked); ?> />
                            <span class="tag-toggle__text"><?php echo esc_html($tag->name); ?></span>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- ⑨ وضعیت انتشار -->
        <div class="form-group">
            <label for="novel_pub_status" class="form-label">وضعیت انتشار</label>
            <select id="novel_pub_status" name="novel_pub_status" class="form-select">
                <option value="ongoing" <?php selected(($_POST['novel_pub_status'] ?? 'ongoing'), 'ongoing'); ?>>در حال انتشار</option>
                <option value="completed" <?php selected(($_POST['novel_pub_status'] ?? ''), 'completed'); ?>>تکمیل شده</option>
                <option value="hiatus" <?php selected(($_POST['novel_pub_status'] ?? ''), 'hiatus'); ?>>متوقف شده</option>
            </select>
        </div>
        
        <!-- ⑩ خلاصه -->
        <div class="form-group">
            <label for="novel_description" class="form-label">خلاصه / توضیحات</label>
            <textarea id="novel_description" name="novel_description" 
                      class="form-textarea" rows="6" maxlength="2000"
                      placeholder="خلاصه رمان را بنویسید..."
                      ><?php echo esc_textarea($_POST['novel_description'] ?? ''); ?></textarea>
            <span class="form-hint char-counter">
                <span id="descCount"><?php echo mb_strlen($_POST['novel_description'] ?? ''); ?></span> / 2000
            </span>
        </div>
        
        <!-- ⑪ تصویر جلد -->
        <div class="form-group">
            <label class="form-label">تصویر جلد</label>
            <div class="cover-upload" id="coverUpload">
                <div class="cover-preview" id="coverPreview">
                    <span class="cover-placeholder">📷<br>انتخاب تصویر</span>
                </div>
                <input type="file" name="novel_cover" id="novelCover" 
                       accept="image/jpeg,image/png,image/webp" class="cover-input" />
                <p class="form-hint">فرمت: JPG, PNG, WebP | حداکثر: 2MB | نسبت پیشنهادی: 3:4</p>
            </div>
        </div>
        
        <!-- Submit -->
        <div class="form-actions">
            <button type="submit" name="novel_submit" value="1" class="btn-submit-novel">
                📤 ارسال برای بررسی
            </button>
        </div>
    </form>
    
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Type card selector
    document.querySelectorAll('.type-card').forEach(function(card) {
        card.addEventListener('click', function() {
            document.querySelectorAll('.type-card').forEach(function(c) { c.classList.remove('selected'); });
            this.classList.add('selected');
        });
    });
    
    // Genre toggle with max 5
    var genreGrid = document.getElementById('genreGrid');
    if (genreGrid) {
        genreGrid.addEventListener('change', function(e) {
            var checked = genreGrid.querySelectorAll('input:checked');
            document.getElementById('genreCount').textContent = checked.length;
            
            if (checked.length >= 5) {
                genreGrid.querySelectorAll('input:not(:checked)').forEach(function(cb) {
                    cb.disabled = true;
                    cb.closest('.genre-toggle').classList.add('disabled');
                });
            } else {
                genreGrid.querySelectorAll('input').forEach(function(cb) {
                    cb.disabled = false;
                    cb.closest('.genre-toggle').classList.remove('disabled');
                });
            }
            
            // Toggle checked class
            if (e.target.type === 'checkbox') {
                e.target.closest('.genre-toggle').classList.toggle('checked', e.target.checked);
            }
        });
        // Init count
        document.getElementById('genreCount').textContent = genreGrid.querySelectorAll('input:checked').length;
    }
    
    // Tag toggle visual
    document.querySelectorAll('.tag-toggle input').forEach(function(cb) {
        cb.addEventListener('change', function() {
            this.closest('.tag-toggle').classList.toggle('checked', this.checked);
        });
    });
    
    // Description char counter
    var descField = document.getElementById('novel_description');
    if (descField) {
        descField.addEventListener('input', function() {
            document.getElementById('descCount').textContent = this.value.length;
        });
    }
    
    // Cover preview
    var coverInput = document.getElementById('novelCover');
    var coverPreview = document.getElementById('coverPreview');
    if (coverInput && coverPreview) {
        coverPreview.addEventListener('click', function() {
            coverInput.click();
        });
        
        coverInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    coverPreview.innerHTML = '<img src="' + e.target.result + '" alt="پیش‌نمایش" />';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
    
    // Persian/English validation hints
    var titleFa = document.getElementById('novel_title_fa');
    var titleEn = document.getElementById('novel_title_en');
    
    if (titleFa) {
        titleFa.addEventListener('input', function() {
            var hint = document.getElementById('titleFaHint');
            if (/[a-zA-Z]/.test(this.value)) {
                hint.textContent = '⚠️ لطفاً نام فارسی وارد کنید';
                hint.className = 'form-hint form-hint--error';
                this.classList.add('input-error');
            } else {
                hint.textContent = '';
                this.classList.remove('input-error');
            }
        });
    }
    
    if (titleEn) {
        titleEn.addEventListener('input', function() {
            var hint = document.getElementById('titleEnHint');
            if (/[\u0600-\u06FF]/.test(this.value)) {
                hint.textContent = '⚠️ لطفاً نام انگلیسی وارد کنید';
                hint.className = 'form-hint form-hint--error';
                this.classList.add('input-error');
            } else {
                hint.textContent = '';
                this.classList.remove('input-error');
            }
        });
    }
});
</script>