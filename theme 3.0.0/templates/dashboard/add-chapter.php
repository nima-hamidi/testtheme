<?php
/**
 * Dashboard: Add Chapter Form
 * 
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!get_option('novel_user_writing', true)) {
    echo '<div class="dashboard-notice notice-warning">ارسال قسمت غیرفعال است.</div>';
    return;
}

if (!is_user_logged_in()) {
    return;
}

$user_id = get_current_user_id();

// Get user's novels
$my_novels = get_posts([
    'post_type'      => 'novel',
    'author'         => $user_id,
    'posts_per_page' => -1,
    'post_status'    => ['publish', 'pending', 'draft'],
    'orderby'        => 'title',
    'order'          => 'ASC',
]);

// Pre-selected novel from URL
$preselect_novel = absint($_GET['novel_id'] ?? 0);

$form_errors  = [];
$form_success = '';

if (isset($_POST['chapter_submit']) && wp_verify_nonce($_POST['chapter_form_nonce'], 'novel_add_chapter')) {
    
    $novel_id      = absint($_POST['chapter_novel_id'] ?? 0);
    $chapter_num   = absint($_POST['chapter_number'] ?? 0);
    $chapter_title = sanitize_text_field($_POST['chapter_title'] ?? '');
    $chapter_vol   = sanitize_text_field($_POST['chapter_volume'] ?? '');
    $content       = wp_kses_post($_POST['chapter_content'] ?? '');
    $is_vip        = isset($_POST['chapter_is_vip']) ? '1' : '';
    $coin_price    = absint($_POST['chapter_coin_price'] ?? get_option('novel_default_coin_price', 5));
    $recap         = sanitize_textarea_field($_POST['chapter_recap'] ?? '');
    $recap         = mb_substr($recap, 0, 200);
    $is_scheduled  = isset($_POST['chapter_scheduled']);
    $schedule_date = sanitize_text_field($_POST['chapter_schedule_date'] ?? '');
    $submit_type   = sanitize_text_field($_POST['submit_type'] ?? 'publish');
    
    // Validation
    if (!$novel_id) {
        $form_errors[] = 'رمان را انتخاب کنید.';
    } else {
        // Check ownership
        $novel = get_post($novel_id);
        if (!$novel || (int) $novel->post_author !== $user_id) {
            $form_errors[] = 'این رمان متعلق به شما نیست.';
        }
    }
    
    if (!$chapter_num) {
        $form_errors[] = 'شماره قسمت الزامی است.';
    } elseif ($novel_id && novel_is_chapter_duplicate($novel_id, $chapter_num)) {
        $form_errors[] = 'قسمت ' . $chapter_num . ' قبلاً وجود دارد.';
    }
    
    if (mb_strlen(wp_strip_all_tags($content)) < 500) {
        $form_errors[] = 'محتوای قسمت باید حداقل ۵۰۰ کاراکتر باشد.';
    }
    
    if (empty($form_errors)) {
        // Determine post status
        if ($submit_type === 'draft') {
            $post_status = 'draft';
        } elseif ($is_scheduled && $schedule_date) {
            $post_status = 'future';
        } else {
            $post_status = 'publish';
        }
        
        $novel_name = get_the_title($novel_id);
        $auto_title = $novel_name . ' - قسمت ' . $chapter_num;
        if ($chapter_title) {
            $auto_title .= ': ' . $chapter_title;
        }
        
        $post_data = [
            'post_title'   => $auto_title,
            'post_name'    => 'chapter-' . $chapter_num,
            'post_content' => $content,
            'post_type'    => 'chapter',
            'post_status'  => $post_status,
            'post_author'  => $user_id,
        ];
        
        if ($post_status === 'future' && $schedule_date) {
            $post_data['post_date']     = $schedule_date;
            $post_data['post_date_gmt'] = get_gmt_from_date($schedule_date);
        }
        
        $chapter_id = wp_insert_post($post_data);
        
        if (is_wp_error($chapter_id)) {
            $form_errors[] = 'خطا در ثبت قسمت: ' . $chapter_id->get_error_message();
        } else {
            update_post_meta($chapter_id, 'chapter_novel_id', $novel_id);
            update_post_meta($chapter_id, 'chapter_number', $chapter_num);
            update_post_meta($chapter_id, 'chapter_title', $chapter_title);
            update_post_meta($chapter_id, 'chapter_volume', $chapter_vol);
            update_post_meta($chapter_id, 'chapter_is_vip', $is_vip);
            update_post_meta($chapter_id, 'chapter_coin_price', max(1, $coin_price));
            update_post_meta($chapter_id, 'chapter_recap', $recap);
            update_post_meta($chapter_id, 'chapter_views', 0);
            update_post_meta($chapter_id, 'chapter_likes', 0);
            update_post_meta($chapter_id, 'chapter_dislikes', 0);
            
            // Word count & reading time (auto-calculated in save hook)
            
            $status_labels = [
                'publish' => 'منتشر شد',
                'draft'   => 'به عنوان پیش‌نویس ذخیره شد',
                'future'  => 'برای انتشار زمان‌بندی شد',
            ];
            
            $form_success = 'قسمت ' . $chapter_num . ' با موفقیت ' . ($status_labels[$post_status] ?? 'ثبت شد') . '.';
        }
    }
}
?>

<div class="dashboard-form-page">
    <h2 class="dashboard-form-title">📄 افزودن قسمت جدید</h2>
    
    <?php if (empty($my_novels)) : ?>
        <div class="form-alert form-alert--warning">
            ⚠️ ابتدا باید یک رمان ایجاد کنید.
            <br><a href="<?php echo home_url('/dashboard/?tab=add-novel'); ?>">افزودن رمان جدید →</a>
        </div>
        <?php return; ?>
    <?php endif; ?>
    
    <?php if (!empty($form_errors)) : ?>
        <div class="form-alert form-alert--error">
            <strong>⚠️ خطا:</strong>
            <ul><?php foreach ($form_errors as $err) : ?><li><?php echo esc_html($err); ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    
    <?php if ($form_success) : ?>
        <div class="form-alert form-alert--success">
            <strong>✅</strong> <?php echo esc_html($form_success); ?>
            <br>
            <a href="<?php echo home_url('/dashboard/?tab=add-chapter&novel_id=' . ($novel_id ?? '')); ?>">افزودن قسمت بعدی</a>
            |
            <a href="<?php echo home_url('/dashboard/?tab=my-chapters'); ?>">لیست قسمت‌ها</a>
        </div>
    <?php else : ?>
    
    <form method="post" class="novel-frontend-form" id="addChapterForm" novalidate>
        <?php wp_nonce_field('novel_add_chapter', 'chapter_form_nonce'); ?>
        
        <!-- Novel Info Banner -->
        <div class="chapter-novel-banner" id="chapterNovelBanner" style="display:none;">
            <span class="banner-icon">📖</span>
            <span class="banner-text" id="bannerText"></span>
            <span class="banner-latest" id="bannerLatest"></span>
        </div>
        
        <!-- ① انتخاب رمان -->
        <div class="form-group">
            <label for="chapter_novel_id" class="form-label">رمان مربوطه <span class="required">*</span></label>
            <select id="chapter_novel_id" name="chapter_novel_id" class="form-select" required>
                <option value="">-- انتخاب رمان --</option>
                <?php foreach ($my_novels as $novel) : ?>
                    <option value="<?php echo $novel->ID; ?>" 
                            <?php selected($preselect_novel ?: ($_POST['chapter_novel_id'] ?? ''), $novel->ID); ?>>
                        <?php echo esc_html($novel->post_title); ?>
                        <?php if ($novel->post_status !== 'publish') echo ' [' . $novel->post_status . ']'; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-row-2col">
            <!-- ② شماره قسمت -->
            <div class="form-group">
                <label for="chapter_number" class="form-label">شماره قسمت <span class="required">*</span></label>
                <input type="number" id="chapter_number" name="chapter_number" 
                       class="form-input" required min="1"
                       value="<?php echo esc_attr($_POST['chapter_number'] ?? ''); ?>" />
                <span class="form-hint" id="chapterNumHint">شماره نباید تکراری باشد</span>
            </div>
            
            <!-- ③ عنوان قسمت -->
            <div class="form-group">
                <label for="chapter_title" class="form-label">عنوان قسمت</label>
                <input type="text" id="chapter_title" name="chapter_title" 
                       class="form-input"
                       value="<?php echo esc_attr($_POST['chapter_title'] ?? ''); ?>"
                       placeholder="عنوان (اختیاری)" />
            </div>
        </div>
        
        <!-- ④ جلد -->
        <div class="form-group">
            <label for="chapter_volume" class="form-label">جلد / فصل</label>
            <select id="chapter_volume" name="chapter_volume" class="form-select">
                <option value="">بدون جلد</option>
            </select>
        </div>
        
        <!-- ⑤ محتوا -->
        <div class="form-group">
            <label for="chapter_content" class="form-label">محتوای قسمت <span class="required">*</span></label>
            <?php 
            wp_editor($_POST['chapter_content'] ?? '', 'chapter_content', [
                'textarea_rows' => 20,
                'media_buttons' => false,
                'teeny'         => true,
                'quicktags'     => true,
                'tinymce'       => [
                    'toolbar1' => 'bold,italic,underline,separator,bullist,numlist,separator,blockquote,hr,separator,undo,redo',
                    'toolbar2' => '',
                ],
            ]);
            ?>
            <span class="form-hint">حداقل ۵۰۰ کاراکتر</span>
        </div>
        
        <!-- ⑥ VIP -->
        <div class="form-group">
            <label class="form-checkbox-label">
                <input type="checkbox" name="chapter_is_vip" id="chapterVip" value="1"
                       <?php checked(!empty($_POST['chapter_is_vip'])); ?> />
                <span>👑 قسمت VIP (اشتراکی)</span>
            </label>
            
            <div class="vip-price-field" id="vipPriceField" style="display:none;">
                <label for="chapter_coin_price" class="form-label">قیمت (سکه) 🪙</label>
                <input type="number" id="chapter_coin_price" name="chapter_coin_price" 
                       class="form-input" min="1" style="width:120px;"
                       value="<?php echo esc_attr($_POST['chapter_coin_price'] ?? get_option('novel_default_coin_price', 5)); ?>" />
            </div>
        </div>
        
        <!-- Recap -->
        <div class="form-group">
            <label for="chapter_recap" class="form-label">📋 خلاصه قسمت قبل</label>
            <textarea id="chapter_recap" name="chapter_recap" class="form-textarea" 
                      rows="2" maxlength="200"
                      placeholder="خلاصه‌ای کوتاه از قسمت قبلی (اختیاری)"
                      ><?php echo esc_textarea($_POST['chapter_recap'] ?? ''); ?></textarea>
            <span class="form-hint"><span id="recapCharCount"><?php echo mb_strlen($_POST['chapter_recap'] ?? ''); ?></span>/200</span>
        </div>
        
        <!-- ⑦ زمان‌بندی -->
        <div class="form-group">
            <label class="form-checkbox-label">
                <input type="checkbox" name="chapter_scheduled" id="chapterScheduled" value="1"
                       <?php checked(!empty($_POST['chapter_scheduled'])); ?> />
                <span>⏰ انتشار زمان‌بندی‌شده</span>
            </label>
            
            <div class="schedule-field" id="scheduleField" style="display:none;">
                <label for="chapter_schedule_date" class="form-label">تاریخ و ساعت انتشار</label>
                <input type="datetime-local" id="chapter_schedule_date" name="chapter_schedule_date" 
                       class="form-input" style="width:260px;"
                       value="<?php echo esc_attr($_POST['chapter_schedule_date'] ?? ''); ?>"
                       min="<?php echo date('Y-m-d\TH:i'); ?>" />
            </div>
        </div>
        
        <!-- Submit -->
        <div class="form-actions">
            <button type="submit" name="chapter_submit" value="1" class="btn-submit-novel" 
                    onclick="document.getElementById('submitType').value='publish';">
                📤 انتشار
            </button>
            <button type="submit" name="chapter_submit" value="1" class="btn-draft-novel"
                    onclick="document.getElementById('submitType').value='draft';">
                📝 پیش‌نویس
            </button>
            <input type="hidden" name="submit_type" id="submitType" value="publish" />
        </div>
    </form>
    
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var novelSelect = document.getElementById('chapter_novel_id');
    var chapterNum  = document.getElementById('chapter_number');
    var volumeSelect = document.getElementById('chapter_volume');
    var banner = document.getElementById('chapterNovelBanner');
    
    // VIP toggle
    document.getElementById('chapterVip').addEventListener('change', function() {
        document.getElementById('vipPriceField').style.display = this.checked ? 'block' : 'none';
    });
    if (document.getElementById('chapterVip').checked) {
        document.getElementById('vipPriceField').style.display = 'block';
    }
    
    // Schedule toggle
    document.getElementById('chapterScheduled').addEventListener('change', function() {
        document.getElementById('scheduleField').style.display = this.checked ? 'block' : 'none';
    });
    if (document.getElementById('chapterScheduled').checked) {
        document.getElementById('scheduleField').style.display = 'block';
    }
    
    // Recap counter
    var recapField = document.getElementById('chapter_recap');
    if (recapField) {
        recapField.addEventListener('input', function() {
            document.getElementById('recapCharCount').textContent = this.value.length;
        });
    }
    
    // Load novel info when selected
    if (novelSelect) {
        novelSelect.addEventListener('change', function() {
            var novelId = this.value;
            if (!novelId) {
                banner.style.display = 'none';
                return;
            }
            
            fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=novel_get_volumes&novel_id=' + novelId + '&nonce=<?php echo wp_create_nonce("novel_get_volumes"); ?>'
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    // Update volumes dropdown
                    volumeSelect.innerHTML = '<option value="">بدون جلد</option>';
                    data.data.volumes.forEach(function(vol) {
                        volumeSelect.innerHTML += '<option value="' + vol.id + '">' + vol.title + '</option>';
                    });
                    
                    // Update banner
                    var latest = data.data.latest_chapter || 0;
                    document.getElementById('bannerText').textContent = novelSelect.options[novelSelect.selectedIndex].text;
                    document.getElementById('bannerLatest').textContent = 'آخرین قسمت: ' + latest + ' | پیشنهاد: ' + (parseInt(latest) + 1);
                    banner.style.display = 'flex';
                    
                    // Auto-suggest number
                    if (!chapterNum.value) {
                        chapterNum.value = parseInt(latest) + 1;
                    }
                }
            });
        });
        
        // Trigger on page load if preselected
        if (novelSelect.value) {
            novelSelect.dispatchEvent(new Event('change'));
        }
    }
    
    // Check duplicate chapter number
    var checkTimer;
    if (chapterNum) {
        chapterNum.addEventListener('input', function() {
            clearTimeout(checkTimer);
            var num = this.value;
            var novelId = novelSelect.value;
            var hint = document.getElementById('chapterNumHint');
            
            if (!num || !novelId) { hint.textContent = ''; return; }
            
            hint.textContent = 'بررسی...';
            hint.className = 'form-hint';
            
            checkTimer = setTimeout(function() {
                fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=novel_check_chapter_duplicate&novel_id=' + novelId + '&chapter_number=' + num + '&exclude_id=0&nonce=<?php echo wp_create_nonce("novel_check_chapter"); ?>'
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        if (data.data.duplicate) {
                            hint.textContent = '❌ قسمت ' + num + ' قبلاً وجود دارد!';
                            hint.className = 'form-hint form-hint--error';
                            chapterNum.classList.add('input-error');
                        } else {
                            hint.textContent = '✅ شماره آزاد است';
                            hint.className = 'form-hint form-hint--success';
                            chapterNum.classList.remove('input-error');
                        }
                    }
                });
            }, 500);
        });
    }
});
</script>