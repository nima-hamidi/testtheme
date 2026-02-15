<?php
/**
 * Meta Boxes for Novel and Chapter
 * 
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register all meta boxes
 */
function novel_register_meta_boxes() {
    // Novel meta boxes
    add_meta_box(
        'novel_info',
        '📖 اطلاعات رمان',
        'novel_info_meta_box_callback',
        'novel',
        'normal',
        'high'
    );
    
    add_meta_box(
        'novel_links',
        '🔗 لینک‌های مرتبط',
        'novel_links_meta_box_callback',
        'novel',
        'normal',
        'default'
    );
    
    add_meta_box(
        'novel_stats_display',
        '📊 آمار رمان',
        'novel_stats_meta_box_callback',
        'novel',
        'side',
        'default'
    );
    
    // Chapter meta boxes
    add_meta_box(
        'chapter_info',
        '📄 اطلاعات قسمت',
        'chapter_info_meta_box_callback',
        'chapter',
        'normal',
        'high'
    );
    
    add_meta_box(
        'chapter_stats_display',
        '📊 آمار قسمت',
        'chapter_stats_meta_box_callback',
        'chapter',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'novel_register_meta_boxes');

// ═══════════════════════════════════════════
// NOVEL META BOX: اطلاعات رمان
// ═══════════════════════════════════════════

function novel_info_meta_box_callback($post) {
    wp_nonce_field('novel_meta_box', 'novel_meta_box_nonce');
    
    $english_name    = get_post_meta($post->ID, 'novel_english_name', true);
    $type            = get_post_meta($post->ID, 'novel_type', true) ?: 'web_novel';
    $original_author = get_post_meta($post->ID, 'novel_original_author', true);
    $translator      = get_post_meta($post->ID, 'novel_translator', true);
    $country         = get_post_meta($post->ID, 'novel_country', true) ?: 'japan';
    $year            = get_post_meta($post->ID, 'novel_year', true);
    $total_chapters  = get_post_meta($post->ID, 'novel_total_chapters', true);
    
    $countries  = novel_get_country_labels_plain();
    $type_labels = novel_get_type_labels();
    ?>
    <div class="novel-meta-box">
        <style>
            .novel-meta-box { padding: 10px 0; }
            .novel-meta-row { margin-bottom: 16px; display: flex; align-items: flex-start; gap: 12px; }
            .novel-meta-row label { min-width: 160px; font-weight: 600; padding-top: 6px; color: #1e293b; }
            .novel-meta-row input[type="text"],
            .novel-meta-row input[type="number"],
            .novel-meta-row input[type="url"],
            .novel-meta-row select { flex: 1; max-width: 400px; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; }
            .novel-meta-row input:focus,
            .novel-meta-row select:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); outline: none; }
            .novel-meta-row .description { color: #6b7280; font-size: 12px; margin-top: 4px; }
            .novel-meta-row .required { color: #ef4444; }
            .novel-type-cards { display: flex; gap: 12px; }
            .novel-type-card { padding: 12px 20px; border: 2px solid #e5e7eb; border-radius: 8px; cursor: pointer; transition: all 0.2s; text-align: center; min-width: 140px; }
            .novel-type-card:hover { border-color: #a5b4fc; }
            .novel-type-card.selected { border-color: #6366f1; background: #eef2ff; }
            .novel-type-card input { display: none; }
            .novel-type-card .type-icon { font-size: 24px; display: block; margin-bottom: 4px; }
            .novel-type-card .type-label { font-weight: 600; font-size: 14px; }
        </style>

        <!-- ① نام انگلیسی -->
        <div class="novel-meta-row">
            <label for="novel_english_name">نام انگلیسی / جایگزین <span class="required">*</span></label>
            <div style="flex:1;">
                <input type="text" 
                       id="novel_english_name" 
                       name="novel_english_name" 
                       value="<?php echo esc_attr($english_name); ?>" 
                       pattern="[a-zA-Z0-9\s\-':.,!?]+" 
                       placeholder="English Novel Name"
                       required
                       style="direction:ltr;text-align:left;" />
                <p class="description">فقط حروف انگلیسی، اعداد و علائم مجاز</p>
            </div>
        </div>

        <!-- ② نوع رمان -->
        <div class="novel-meta-row">
            <label>نوع رمان <span class="required">*</span></label>
            <div class="novel-type-cards">
                <?php foreach ($type_labels as $value => $label) : 
                    $icon = $value === 'light_novel' ? '📕' : '💻';
                    $desc = $value === 'light_novel' ? 'چاپ شده با تصویرسازی' : 'منتشر شده آنلاین';
                ?>
                <label class="novel-type-card <?php echo $type === $value ? 'selected' : ''; ?>">
                    <input type="radio" name="novel_type" value="<?php echo esc_attr($value); ?>" 
                           <?php checked($type, $value); ?> />
                    <span class="type-icon"><?php echo $icon; ?></span>
                    <span class="type-label"><?php echo esc_html($label); ?></span>
                    <small style="color:#6b7280;display:block;margin-top:2px;"><?php echo esc_html($desc); ?></small>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ③ نویسنده اصلی -->
        <div class="novel-meta-row">
            <label for="novel_original_author">نویسنده اصلی <span class="required">*</span></label>
            <input type="text" 
                   id="novel_original_author" 
                   name="novel_original_author" 
                   value="<?php echo esc_attr($original_author); ?>" 
                   placeholder="نام نویسنده اصلی اثر"
                   required />
        </div>

        <!-- ④ مترجم -->
        <div class="novel-meta-row">
            <label for="novel_translator">مترجم</label>
            <input type="text" 
                   id="novel_translator" 
                   name="novel_translator" 
                   value="<?php echo esc_attr($translator); ?>" 
                   placeholder="نام مترجم (اختیاری)" />
        </div>

        <!-- ⑤ کشور مبدأ -->
        <div class="novel-meta-row">
            <label for="novel_country">کشور مبدأ</label>
            <select id="novel_country" name="novel_country">
                <?php foreach ($countries as $val => $lbl) : ?>
                    <option value="<?php echo esc_attr($val); ?>" <?php selected($country, $val); ?>>
                        <?php echo esc_html($lbl); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- ⑥ سال انتشار -->
        <div class="novel-meta-row">
            <label for="novel_year">سال انتشار اصلی</label>
            <input type="number" 
                   id="novel_year" 
                   name="novel_year" 
                   value="<?php echo esc_attr($year); ?>" 
                   min="1990" 
                   max="<?php echo date('Y'); ?>" 
                   placeholder="<?php echo date('Y'); ?>" />
        </div>

        <!-- ⑦ تعداد کل قسمت‌ها -->
        <div class="novel-meta-row">
            <label for="novel_total_chapters">تعداد کل قسمت‌ها (اصلی)</label>
            <div style="flex:1;">
                <input type="number" 
                       id="novel_total_chapters" 
                       name="novel_total_chapters" 
                       value="<?php echo esc_attr($total_chapters); ?>" 
                       min="0" 
                       placeholder="0 = نامشخص" />
                <p class="description">تعداد کل قسمت‌های اثر اصلی. 0 = نامشخص. در فرانت نمایش: «X از Y قسمت ترجمه شده»</p>
            </div>
        </div>
    </div>

    <script>
    jQuery(function($) {
        // Type card selection
        $('.novel-type-card').on('click', function() {
            $('.novel-type-card').removeClass('selected');
            $(this).addClass('selected');
        });
    });
    </script>
    <?php
}

// ═══════════════════════════════════════════
// NOVEL META BOX: لینک‌های مرتبط
// ═══════════════════════════════════════════

function novel_links_meta_box_callback($post) {
    $has_anime    = get_post_meta($post->ID, 'novel_has_anime', true);
    $anime_url    = get_post_meta($post->ID, 'novel_anime_url', true);
    $has_manga    = get_post_meta($post->ID, 'novel_has_manga', true);
    $manga_url    = get_post_meta($post->ID, 'novel_manga_url', true);
    $custom_links = get_post_meta($post->ID, 'novel_custom_links', true);
    
    if (!is_array($custom_links)) {
        $custom_links = [];
    }
    ?>
    <div class="novel-meta-box">
        <style>
            .novel-link-toggle { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
            .novel-link-url { margin-left: 172px; margin-bottom: 16px; display: none; }
            .novel-link-url.visible { display: block; }
            .novel-custom-links { margin-top: 16px; border-top: 1px solid #e5e7eb; padding-top: 16px; }
            .novel-custom-link-item { display: flex; gap: 8px; margin-bottom: 8px; align-items: center; }
            .novel-custom-link-item input { padding: 6px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; }
            .novel-custom-link-item .link-name { width: 150px; }
            .novel-custom-link-item .link-url { flex: 1; direction: ltr; text-align: left; }
            .btn-remove-link { background: #fee2e2; color: #dc2626; border: none; border-radius: 6px; padding: 6px 10px; cursor: pointer; font-size: 16px; }
            .btn-remove-link:hover { background: #fecaca; }
            .btn-add-link { background: #eef2ff; color: #6366f1; border: 1px dashed #a5b4fc; border-radius: 6px; padding: 8px 16px; cursor: pointer; font-size: 13px; margin-top: 8px; }
            .btn-add-link:hover { background: #e0e7ff; }
        </style>

        <!-- Anime -->
        <div class="novel-link-toggle">
            <label style="min-width:160px;font-weight:600;">
                <input type="checkbox" name="novel_has_anime" value="1" 
                       <?php checked($has_anime, '1'); ?> 
                       id="toggle_anime" />
                🎬 نسخه انیمه دارد
            </label>
        </div>
        <div class="novel-link-url <?php echo $has_anime ? 'visible' : ''; ?>" id="anime_url_wrap">
            <input type="url" name="novel_anime_url" value="<?php echo esc_url($anime_url); ?>" 
                   placeholder="https://example.com/anime" style="width:100%;max-width:500px;padding:8px 12px;border:1px solid #d1d5db;border-radius:6px;direction:ltr;text-align:left;" />
        </div>

        <!-- Manga -->
        <div class="novel-link-toggle">
            <label style="min-width:160px;font-weight:600;">
                <input type="checkbox" name="novel_has_manga" value="1" 
                       <?php checked($has_manga, '1'); ?> 
                       id="toggle_manga" />
                📚 نسخه مانگا/مانهوا دارد
            </label>
        </div>
        <div class="novel-link-url <?php echo $has_manga ? 'visible' : ''; ?>" id="manga_url_wrap">
            <input type="url" name="novel_manga_url" value="<?php echo esc_url($manga_url); ?>" 
                   placeholder="https://example.com/manga" style="width:100%;max-width:500px;padding:8px 12px;border:1px solid #d1d5db;border-radius:6px;direction:ltr;text-align:left;" />
        </div>

        <!-- Custom Links (Repeater) -->
        <div class="novel-custom-links">
            <h4 style="margin:0 0 12px;font-size:14px;">🔗 لینک‌های سفارشی</h4>
            <div id="custom-links-container">
                <?php foreach ($custom_links as $index => $link) : ?>
                    <div class="novel-custom-link-item">
                        <input type="text" class="link-name" 
                               name="novel_custom_links[<?php echo $index; ?>][name]" 
                               value="<?php echo esc_attr($link['name'] ?? ''); ?>" 
                               placeholder="نام لینک" />
                        <input type="url" class="link-url" 
                               name="novel_custom_links[<?php echo $index; ?>][url]" 
                               value="<?php echo esc_url($link['url'] ?? ''); ?>" 
                               placeholder="https://..." />
                        <button type="button" class="btn-remove-link" title="حذف">×</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn-add-link" id="add-custom-link">+ افزودن لینک</button>
            <p class="description" style="margin-top:8px;">حداکثر ۱۰ لینک سفارشی</p>
        </div>
    </div>

    <script>
    jQuery(function($) {
        // Toggle anime/manga URL
        $('#toggle_anime').on('change', function() {
            $('#anime_url_wrap').toggleClass('visible', this.checked);
        });
        $('#toggle_manga').on('change', function() {
            $('#manga_url_wrap').toggleClass('visible', this.checked);
        });

        // Custom links repeater
        var linkIndex = <?php echo max(count($custom_links), 0); ?>;
        var maxLinks = 10;

        $('#add-custom-link').on('click', function() {
            var container = $('#custom-links-container');
            if (container.children().length >= maxLinks) {
                alert('حداکثر ' + maxLinks + ' لینک مجاز است.');
                return;
            }
            var html = '<div class="novel-custom-link-item">' +
                '<input type="text" class="link-name" name="novel_custom_links[' + linkIndex + '][name]" placeholder="نام لینک" />' +
                '<input type="url" class="link-url" name="novel_custom_links[' + linkIndex + '][url]" placeholder="https://..." />' +
                '<button type="button" class="btn-remove-link" title="حذف">×</button>' +
                '</div>';
            container.append(html);
            linkIndex++;
        });

        $(document).on('click', '.btn-remove-link', function() {
            $(this).closest('.novel-custom-link-item').fadeOut(200, function() { $(this).remove(); });
        });
    });
    </script>
    <?php
}

// ═══════════════════════════════════════════
// NOVEL META BOX: آمار (سایدبار)
// ═══════════════════════════════════════════

function novel_stats_meta_box_callback($post) {
    $chapter_counts = novel_get_chapter_counts_by_type($post->ID);
    $total_chapters = get_post_meta($post->ID, 'novel_total_chapters', true);
    $views          = get_post_meta($post->ID, 'novel_views', true) ?: 0;
    ?>
    <div style="font-size:13px;line-height:1.8;">
        <p><strong>📖 قسمت‌ها:</strong> <?php echo number_format_i18n($chapter_counts['total']); ?>
            <?php if ($total_chapters) : ?>
                از <?php echo number_format_i18n($total_chapters); ?>
            <?php endif; ?>
        </p>
        <p><strong>✅ رایگان:</strong> <?php echo number_format_i18n($chapter_counts['free']); ?></p>
        <p><strong>🔒 VIP:</strong> <?php echo number_format_i18n($chapter_counts['vip']); ?></p>
        <p><strong>👁 بازدید:</strong> <?php echo number_format_i18n($views); ?></p>
        <p style="color:#6b7280;font-size:12px;margin-top:8px;">آمار به‌صورت خودکار محاسبه می‌شود.</p>
    </div>
    <?php
}

// ═══════════════════════════════════════════
// CHAPTER META BOX: اطلاعات قسمت
// ═══════════════════════════════════════════

function chapter_info_meta_box_callback($post) {
    wp_nonce_field('chapter_meta_box', 'chapter_meta_box_nonce');
    
    $novel_id      = get_post_meta($post->ID, 'chapter_novel_id', true);
    $chapter_num   = get_post_meta($post->ID, 'chapter_number', true);
    $chapter_title = get_post_meta($post->ID, 'chapter_title', true);
    $chapter_vol   = get_post_meta($post->ID, 'chapter_volume', true);
    $is_vip        = get_post_meta($post->ID, 'chapter_is_vip', true);
    $coin_price    = get_post_meta($post->ID, 'chapter_coin_price', true);
    $word_count    = get_post_meta($post->ID, 'chapter_word_count', true);
    $reading_time  = get_post_meta($post->ID, 'chapter_reading_time', true);
    $recap         = get_post_meta($post->ID, 'chapter_recap', true);
    
    $default_price = get_option('novel_default_coin_price', 5);
    if (!$coin_price) {
        $coin_price = $default_price;
    }
    
    // Get all novels for dropdown
    $novels = get_posts([
        'post_type'      => 'novel',
        'posts_per_page' => -1,
        'post_status'    => ['publish', 'draft', 'pending'],
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);
    
    // Get volumes for selected novel
    $volumes = [];
    if ($novel_id) {
        $volumes = get_post_meta($novel_id, 'novel_volumes', true);
        if (!is_array($volumes)) {
            $volumes = [];
        }
    }
    ?>
    <div class="novel-meta-box">
        <style>
            .chapter-meta-row { margin-bottom: 16px; }
            .chapter-meta-row label { display: block; font-weight: 600; margin-bottom: 4px; color: #1e293b; font-size: 13px; }
            .chapter-meta-row input[type="text"],
            .chapter-meta-row input[type="number"],
            .chapter-meta-row select,
            .chapter-meta-row textarea { width: 100%; max-width: 500px; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
            .chapter-meta-row input:focus,
            .chapter-meta-row select:focus,
            .chapter-meta-row textarea:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); outline: none; }
            .chapter-meta-row .description { color: #6b7280; font-size: 12px; margin-top: 4px; }
            .chapter-meta-row .required { color: #ef4444; }
            .chapter-meta-row .field-status { font-size: 12px; margin-top: 4px; }
            .chapter-meta-row .field-status.success { color: #059669; }
            .chapter-meta-row .field-status.error { color: #dc2626; }
            .chapter-meta-inline { display: flex; gap: 16px; flex-wrap: wrap; }
            .chapter-meta-inline .chapter-meta-row { flex: 1; min-width: 200px; }
            .vip-settings { padding: 12px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; margin-top: 8px; display: none; }
            .vip-settings.visible { display: block; }
            .chapter-stats-auto { display: flex; gap: 16px; padding: 10px; background: #f0fdf4; border-radius: 8px; margin-top: 8px; }
            .chapter-stats-auto .stat-item { font-size: 13px; color: #166534; }
            .recap-counter { float: left; font-size: 12px; color: #6b7280; }
        </style>

        <!-- ① انتخاب رمان -->
        <div class="chapter-meta-row">
            <label for="chapter_novel_id">رمان مربوطه <span class="required">*</span></label>
            <select id="chapter_novel_id" name="chapter_novel_id" required>
                <option value="">-- انتخاب رمان --</option>
                <?php foreach ($novels as $novel) : ?>
                    <option value="<?php echo $novel->ID; ?>" <?php selected($novel_id, $novel->ID); ?>>
                        <?php echo esc_html($novel->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="chapter-meta-inline">
            <!-- ② شماره قسمت -->
            <div class="chapter-meta-row">
                <label for="chapter_number">شماره قسمت <span class="required">*</span></label>
                <input type="number" 
                       id="chapter_number" 
                       name="chapter_number" 
                       value="<?php echo esc_attr($chapter_num); ?>" 
                       min="1" 
                       required />
                <div id="chapter_number_status" class="field-status"></div>
                <p class="description">شماره نباید تکراری باشد</p>
            </div>

            <!-- ③ عنوان قسمت -->
            <div class="chapter-meta-row">
                <label for="chapter_title">عنوان قسمت</label>
                <input type="text" 
                       id="chapter_title" 
                       name="chapter_title" 
                       value="<?php echo esc_attr($chapter_title); ?>" 
                       placeholder="عنوان (اختیاری)" />
            </div>
        </div>

        <!-- ④ جلد/فصل -->
        <div class="chapter-meta-row">
            <label for="chapter_volume">جلد / فصل</label>
            <select id="chapter_volume" name="chapter_volume">
                <option value="">بدون جلد</option>
                <?php foreach ($volumes as $vol) : ?>
                    <option value="<?php echo esc_attr($vol['id']); ?>" <?php selected($chapter_vol, $vol['id']); ?>>
                        <?php echo esc_html($vol['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="description">جلدها از صفحه ویرایش رمان مدیریت می‌شوند</p>
        </div>

        <!-- ⑤ VIP -->
        <div class="chapter-meta-row">
            <label>
                <input type="checkbox" 
                       name="chapter_is_vip" 
                       id="chapter_is_vip" 
                       value="1" 
                       <?php checked($is_vip, '1'); ?> />
                <strong>👑 قسمت VIP (اشتراکی)</strong>
            </label>
            
            <!-- ⑥ قیمت سکه -->
            <div class="vip-settings <?php echo $is_vip ? 'visible' : ''; ?>" id="vip_settings">
                <label for="chapter_coin_price">قیمت (سکه) 🪙</label>
                <input type="number" 
                       id="chapter_coin_price" 
                       name="chapter_coin_price" 
                       value="<?php echo esc_attr($coin_price); ?>" 
                       min="1" 
                       style="width:120px;" />
                <p class="description">پیش‌فرض: <?php echo esc_html($default_price); ?> سکه (قابل تغییر از تنظیمات)</p>
            </div>
        </div>

        <!-- ⑦⑧ آمار خودکار -->
        <?php if ($word_count || $reading_time) : ?>
        <div class="chapter-stats-auto">
            <div class="stat-item">📝 <strong><?php echo number_format_i18n($word_count); ?></strong> کلمه</div>
            <div class="stat-item">⏱ <strong><?php echo esc_html($reading_time); ?></strong> دقیقه</div>
        </div>
        <?php else : ?>
        <p class="description">تعداد کلمات و زمان مطالعه پس از ذخیره محاسبه می‌شود.</p>
        <?php endif; ?>

        <!-- خلاصه قسمت قبل (Recap) -->
        <div class="chapter-meta-row" style="margin-top:16px;border-top:1px solid #e5e7eb;padding-top:16px;">
            <label for="chapter_recap">📋 خلاصه قسمت قبل (برای خواننده)</label>
            <textarea id="chapter_recap" 
                      name="chapter_recap" 
                      rows="3" 
                      maxlength="200" 
                      placeholder="خلاصه‌ای کوتاه از قسمت قبلی برای یادآوری خواننده (اختیاری)"
                      ><?php echo esc_textarea($recap); ?></textarea>
            <span class="recap-counter"><span id="recap-count"><?php echo mb_strlen($recap); ?></span> / 200</span>
            <p class="description" style="clear:both;">در بالای قسمت نمایش داده می‌شود تا خواننده‌ای که مدتی نخوانده، یادآوری شود.</p>
        </div>
    </div>

    <script>
    jQuery(function($) {
        // VIP toggle
        $('#chapter_is_vip').on('change', function() {
            $('#vip_settings').toggleClass('visible', this.checked);
        });

        // Recap character counter
        $('#chapter_recap').on('input', function() {
            $('#recap-count').text($(this).val().length);
        });

        // Check duplicate chapter number (AJAX)
        var checkTimer;
        $('#chapter_number').on('input', function() {
            clearTimeout(checkTimer);
            var num = $(this).val();
            var novelId = $('#chapter_novel_id').val();
            var $status = $('#chapter_number_status');
            
            if (!num || !novelId) {
                $status.html('');
                return;
            }

            $status.html('<span style="color:#6b7280;">بررسی...</span>');
            
            checkTimer = setTimeout(function() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'novel_check_chapter_duplicate',
                        novel_id: novelId,
                        chapter_number: num,
                        exclude_id: <?php echo $post->ID; ?>,
                        nonce: '<?php echo wp_create_nonce('novel_check_chapter'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            if (response.data.duplicate) {
                                $status.html('<span class="error">❌ قسمت ' + num + ' قبلاً وجود دارد!</span>');
                            } else {
                                $status.html('<span class="success">✅ شماره آزاد است</span>');
                            }
                        }
                    }
                });
            }, 500);
        });

        // Load volumes when novel changes
        $('#chapter_novel_id').on('change', function() {
            var novelId = $(this).val();
            if (!novelId) return;
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'novel_get_volumes',
                    novel_id: novelId,
                    nonce: '<?php echo wp_create_nonce('novel_get_volumes'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        var $vol = $('#chapter_volume');
                        $vol.find('option:not(:first)').remove();
                        $.each(response.data.volumes, function(i, vol) {
                            $vol.append('<option value="' + vol.id + '">' + vol.title + '</option>');
                        });
                        
                        // Update latest chapter info
                        if (response.data.latest_chapter) {
                            $('#chapter_number_status').html(
                                '<span style="color:#6b7280;">آخرین قسمت: ' + response.data.latest_chapter + 
                                ' | پیشنهاد: ' + (parseInt(response.data.latest_chapter) + 1) + '</span>'
                            );
                        }
                    }
                }
            });
        });
    });
    </script>
    <?php
}

// ═══════════════════════════════════════════
// CHAPTER META BOX: آمار (سایدبار)
// ═══════════════════════════════════════════

function chapter_stats_meta_box_callback($post) {
    $views     = get_post_meta($post->ID, 'chapter_views', true) ?: 0;
    $likes     = get_post_meta($post->ID, 'chapter_likes', true) ?: 0;
    $dislikes  = get_post_meta($post->ID, 'chapter_dislikes', true) ?: 0;
    $words     = get_post_meta($post->ID, 'chapter_word_count', true) ?: 0;
    $time      = get_post_meta($post->ID, 'chapter_reading_time', true) ?: 0;
    $comments  = get_comments(['post_id' => $post->ID, 'count' => true]);
    
    $satisfaction = ($likes + $dislikes) > 0 
                    ? round(($likes / ($likes + $dislikes)) * 100) 
                    : 0;
    ?>
    <div style="font-size:13px;line-height:2;">
        <p>👁 <strong>بازدید:</strong> <?php echo number_format_i18n($views); ?></p>
        <p>👍 <strong>لایک:</strong> <?php echo number_format_i18n($likes); ?></p>
        <p>👎 <strong>دیسلایک:</strong> <?php echo number_format_i18n($dislikes); ?></p>
        <?php if ($likes + $dislikes > 0) : ?>
        <p>📊 <strong>رضایت:</strong> <?php echo $satisfaction; ?>%</p>
        <?php endif; ?>
        <p>💬 <strong>دیدگاه‌ها:</strong> <?php echo number_format_i18n($comments); ?></p>
        <hr style="border:none;border-top:1px solid #e5e7eb;">
        <p>📝 <strong>کلمات:</strong> <?php echo number_format_i18n($words); ?></p>
        <p>⏱ <strong>زمان مطالعه:</strong> <?php echo esc_html($time); ?> دقیقه</p>
    </div>
    <?php
}

// ═══════════════════════════════════════════
// SAVE META DATA
// ═══════════════════════════════════════════

/**
 * Save novel meta data
 */
function novel_save_meta_data($post_id) {
    // Verify nonce
    if (!isset($_POST['novel_meta_box_nonce']) || 
        !wp_verify_nonce($_POST['novel_meta_box_nonce'], 'novel_meta_box')) {
        return;
    }
    
    // Check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Novel info fields
    $text_fields = [
        'novel_english_name',
        'novel_original_author',
        'novel_translator',
    ];
    
    foreach ($text_fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
    
    // Novel type (radio)
    if (isset($_POST['novel_type'])) {
        $allowed_types = ['web_novel', 'light_novel'];
        $type = sanitize_text_field($_POST['novel_type']);
        if (in_array($type, $allowed_types)) {
            update_post_meta($post_id, 'novel_type', $type);
        }
    }
    
    // Country
    if (isset($_POST['novel_country'])) {
        $allowed_countries = array_keys(novel_get_country_labels_plain());
        $country = sanitize_text_field($_POST['novel_country']);
        if (in_array($country, $allowed_countries)) {
            update_post_meta($post_id, 'novel_country', $country);
        }
    }
    
    // Year
    if (isset($_POST['novel_year'])) {
        $year = absint($_POST['novel_year']);
        if ($year >= 1990 && $year <= (int)date('Y')) {
            update_post_meta($post_id, 'novel_year', $year);
        } else {
            delete_post_meta($post_id, 'novel_year');
        }
    }
    
    // Total chapters
    if (isset($_POST['novel_total_chapters'])) {
        update_post_meta($post_id, 'novel_total_chapters', absint($_POST['novel_total_chapters']));
    }
    
    // Links - Anime
    $has_anime = isset($_POST['novel_has_anime']) ? '1' : '';
    update_post_meta($post_id, 'novel_has_anime', $has_anime);
    if ($has_anime && isset($_POST['novel_anime_url'])) {
        update_post_meta($post_id, 'novel_anime_url', esc_url_raw($_POST['novel_anime_url']));
    }
    
    // Links - Manga
    $has_manga = isset($_POST['novel_has_manga']) ? '1' : '';
    update_post_meta($post_id, 'novel_has_manga', $has_manga);
    if ($has_manga && isset($_POST['novel_manga_url'])) {
        update_post_meta($post_id, 'novel_manga_url', esc_url_raw($_POST['novel_manga_url']));
    }
    
    // Custom links (repeater)
    if (isset($_POST['novel_custom_links']) && is_array($_POST['novel_custom_links'])) {
        $clean_links = [];
        $count = 0;
        foreach ($_POST['novel_custom_links'] as $link) {
            if ($count >= 10) break;
            $name = isset($link['name']) ? sanitize_text_field($link['name']) : '';
            $url  = isset($link['url']) ? esc_url_raw($link['url']) : '';
            if (!empty($name) && !empty($url)) {
                $clean_links[] = ['name' => $name, 'url' => $url];
                $count++;
            }
        }
        update_post_meta($post_id, 'novel_custom_links', $clean_links);
    } else {
        update_post_meta($post_id, 'novel_custom_links', []);
    }
}
add_action('save_post_novel', 'novel_save_meta_data');

/**
 * Save chapter meta data
 */
function chapter_save_meta_data($post_id) {
    // Verify nonce
    if (!isset($_POST['chapter_meta_box_nonce']) || 
        !wp_verify_nonce($_POST['chapter_meta_box_nonce'], 'chapter_meta_box')) {
        return;
    }
    
    // Check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Novel ID
    if (isset($_POST['chapter_novel_id'])) {
        $novel_id = absint($_POST['chapter_novel_id']);
        update_post_meta($post_id, 'chapter_novel_id', $novel_id);
    }
    
    // Chapter number
    if (isset($_POST['chapter_number'])) {
        $chapter_num = absint($_POST['chapter_number']);
        
        // Server-side duplicate check
        $novel_id = isset($_POST['chapter_novel_id']) ? absint($_POST['chapter_novel_id']) : 0;
        if ($novel_id && $chapter_num) {
            if (novel_is_chapter_duplicate($novel_id, $chapter_num, $post_id)) {
                // Add admin notice for duplicate
                set_transient('novel_chapter_error_' . $post_id, 'شماره قسمت تکراری است!', 30);
            }
        }
        
        update_post_meta($post_id, 'chapter_number', $chapter_num);
    }
    
    // Chapter title
    if (isset($_POST['chapter_title'])) {
        update_post_meta($post_id, 'chapter_title', sanitize_text_field($_POST['chapter_title']));
    }
    
    // Volume
    if (isset($_POST['chapter_volume'])) {
        update_post_meta($post_id, 'chapter_volume', sanitize_text_field($_POST['chapter_volume']));
    }
    
    // VIP
    $is_vip = isset($_POST['chapter_is_vip']) ? '1' : '';
    update_post_meta($post_id, 'chapter_is_vip', $is_vip);
    
    // Coin price
    if (isset($_POST['chapter_coin_price'])) {
        $price = max(1, absint($_POST['chapter_coin_price']));
        update_post_meta($post_id, 'chapter_coin_price', $price);
    }
    
    // Recap
    if (isset($_POST['chapter_recap'])) {
        $recap = sanitize_textarea_field($_POST['chapter_recap']);
        $recap = mb_substr($recap, 0, 200);
        update_post_meta($post_id, 'chapter_recap', $recap);
    }
}
add_action('save_post_chapter', 'chapter_save_meta_data');

/**
 * Show admin notices for chapter errors
 */
function novel_chapter_admin_notices() {
    global $post;
    if (!$post || $post->post_type !== 'chapter') {
        return;
    }
    
    $error = get_transient('novel_chapter_error_' . $post->ID);
    if ($error) {
        echo '<div class="notice notice-error"><p>⚠️ ' . esc_html($error) . '</p></div>';
        delete_transient('novel_chapter_error_' . $post->ID);
    }
}
add_action('admin_notices', 'novel_chapter_admin_notices');

// ═══════════════════════════════════════════
// AJAX HANDLERS
// ═══════════════════════════════════════════

/**
 * AJAX: Check duplicate chapter number
 */
function novel_ajax_check_chapter_duplicate() {
    check_ajax_referer('novel_check_chapter', 'nonce');
    
    $novel_id       = absint($_POST['novel_id'] ?? 0);
    $chapter_number = absint($_POST['chapter_number'] ?? 0);
    $exclude_id     = absint($_POST['exclude_id'] ?? 0);
    
    if (!$novel_id || !$chapter_number) {
        wp_send_json_error(['message' => 'داده نامعتبر']);
    }
    
    $duplicate = novel_is_chapter_duplicate($novel_id, $chapter_number, $exclude_id);
    
    wp_send_json_success(['duplicate' => $duplicate]);
}
add_action('wp_ajax_novel_check_chapter_duplicate', 'novel_ajax_check_chapter_duplicate');

/**
 * AJAX: Get volumes for a novel
 */
function novel_ajax_get_volumes() {
    check_ajax_referer('novel_get_volumes', 'nonce');
    
    $novel_id = absint($_POST['novel_id'] ?? 0);
    
    if (!$novel_id) {
        wp_send_json_error(['message' => 'رمان نامعتبر']);
    }
    
    $volumes = get_post_meta($novel_id, 'novel_volumes', true);
    if (!is_array($volumes)) {
        $volumes = [];
    }
    
    $latest = novel_get_latest_chapter_number($novel_id);
    
    wp_send_json_success([
        'volumes'        => $volumes,
        'latest_chapter' => $latest,
    ]);
}
add_action('wp_ajax_novel_get_volumes', 'novel_ajax_get_volumes');