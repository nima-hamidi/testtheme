<?php
/**
 * Volume/Arc Management System
 * 
 * Volumes are stored as serialized array in novel post_meta.
 * Each chapter references a volume by its ID.
 * 
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Novel_Volumes {

    /**
     * Initialize
     */
    public function __construct() {
        // AJAX handlers
        add_action('wp_ajax_novel_add_volume', [$this, 'ajax_add_volume']);
        add_action('wp_ajax_novel_edit_volume', [$this, 'ajax_edit_volume']);
        add_action('wp_ajax_novel_delete_volume', [$this, 'ajax_delete_volume']);
        add_action('wp_ajax_novel_reorder_volumes', [$this, 'ajax_reorder_volumes']);
        add_action('wp_ajax_novel_get_volume_chapters', [$this, 'ajax_get_volume_chapters']);
        
        // Add volumes meta box to novel edit screen
        add_action('add_meta_boxes', [$this, 'add_volumes_meta_box']);
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);
    }

    /**
     * Add volumes meta box to novel editor
     */
    public function add_volumes_meta_box() {
        add_meta_box(
            'novel_volumes',
            '📂 مدیریت جلدها / فصل‌ها',
            [$this, 'render_volumes_meta_box'],
            'novel',
            'normal',
            'default'
        );
    }

    /**
     * Render volumes meta box
     */
    public function render_volumes_meta_box($post) {
        $volumes = $this->get_volumes($post->ID);
        $nonce   = wp_create_nonce('novel_volumes_action');
        ?>
        <div class="novel-volumes-manager" data-novel-id="<?php echo $post->ID; ?>" data-nonce="<?php echo $nonce; ?>">
            <style>
                .novel-volumes-manager { padding: 10px 0; }
                .volumes-list { margin-bottom: 16px; }
                .volume-item {
                    display: flex; align-items: center; gap: 10px;
                    padding: 10px 14px; margin-bottom: 6px;
                    background: #f8fafc; border: 1px solid #e2e8f0;
                    border-radius: 8px; transition: all 0.2s;
                }
                .volume-item:hover { background: #eef2ff; border-color: #c7d2fe; }
                .volume-item .drag-handle {
                    cursor: grab; color: #94a3b8; font-size: 18px;
                    user-select: none; padding: 0 4px;
                }
                .volume-item .drag-handle:active { cursor: grabbing; }
                .volume-item.dragging { opacity: 0.5; background: #e0e7ff; }
                .volume-item .vol-order {
                    background: #6366f1; color: #fff; 
                    width: 28px; height: 28px; border-radius: 50%;
                    display: flex; align-items: center; justify-content: center;
                    font-size: 12px; font-weight: 700; flex-shrink: 0;
                }
                .volume-item .vol-title {
                    flex: 1; font-weight: 600; font-size: 14px; color: #1e293b;
                }
                .volume-item .vol-title input {
                    width: 100%; padding: 4px 8px; border: 1px solid #d1d5db;
                    border-radius: 4px; font-size: 14px; display: none;
                }
                .volume-item .vol-title input.editing { display: block; }
                .volume-item .vol-title span.editing { display: none; }
                .volume-item .vol-chapters {
                    color: #6b7280; font-size: 12px; flex-shrink: 0;
                }
                .volume-item .vol-actions { display: flex; gap: 4px; flex-shrink: 0; }
                .volume-item .vol-actions button {
                    background: none; border: none; cursor: pointer;
                    padding: 4px 6px; border-radius: 4px; font-size: 14px;
                    transition: background 0.2s;
                }
                .volume-item .vol-actions .btn-edit:hover { background: #e0e7ff; }
                .volume-item .vol-actions .btn-delete:hover { background: #fee2e2; }
                .volume-item .vol-actions .btn-save { color: #059669; display: none; }
                .volume-item .vol-actions .btn-save:hover { background: #d1fae5; }
                .volume-item .vol-actions .btn-cancel { color: #6b7280; display: none; }
                .volume-item .vol-actions .btn-cancel:hover { background: #f1f5f9; }
                .add-volume-form {
                    display: flex; gap: 8px; margin-top: 12px;
                    padding-top: 12px; border-top: 1px dashed #e2e8f0;
                }
                .add-volume-form input {
                    flex: 1; padding: 8px 12px; border: 1px solid #d1d5db;
                    border-radius: 6px; font-size: 14px;
                }
                .add-volume-form input:focus { border-color: #6366f1; outline: none; }
                .add-volume-form button {
                    background: #6366f1; color: #fff; border: none;
                    padding: 8px 16px; border-radius: 6px; cursor: pointer;
                    font-size: 13px; white-space: nowrap; transition: background 0.2s;
                }
                .add-volume-form button:hover { background: #4f46e5; }
                .add-volume-form button:disabled { opacity: 0.5; cursor: not-allowed; }
                .volumes-empty {
                    text-align: center; padding: 20px; color: #9ca3af;
                    font-size: 14px; background: #f9fafb; border-radius: 8px;
                }
            </style>

            <div class="volumes-list" id="volumes-list">
                <?php if (empty($volumes)) : ?>
                    <div class="volumes-empty" id="volumes-empty">
                        📂 هنوز جلدی تعریف نشده. قسمت‌ها بدون جلدبندی نمایش داده می‌شوند.
                    </div>
                <?php else : ?>
                    <?php foreach ($volumes as $vol) : 
                        $ch_count = $this->count_volume_chapters($post->ID, $vol['id']);
                    ?>
                    <div class="volume-item" data-volume-id="<?php echo esc_attr($vol['id']); ?>">
                        <span class="drag-handle" title="جابجایی">⠿</span>
                        <span class="vol-order"><?php echo esc_html($vol['order']); ?></span>
                        <div class="vol-title">
                            <span><?php echo esc_html($vol['title']); ?></span>
                            <input type="text" value="<?php echo esc_attr($vol['title']); ?>" class="editing" />
                        </div>
                        <span class="vol-chapters"><?php echo $ch_count; ?> قسمت</span>
                        <div class="vol-actions">
                            <button type="button" class="btn-edit" title="ویرایش">✏️</button>
                            <button type="button" class="btn-save" title="ذخیره">✅</button>
                            <button type="button" class="btn-cancel" title="انصراف">↩️</button>
                            <button type="button" class="btn-delete" title="حذف">🗑️</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="add-volume-form">
                <input type="text" id="new-volume-title" placeholder="عنوان جلد جدید (مثلاً: جلد ۱: شروع ماجرا)" maxlength="200" />
                <button type="button" id="btn-add-volume">+ افزودن جلد</button>
            </div>
        </div>

        <script>
        jQuery(function($) {
            var $manager = $('.novel-volumes-manager');
            var novelId = $manager.data('novel-id');
            var nonce = $manager.data('nonce');
            var $list = $('#volumes-list');

            // ═══ Add Volume ═══
            $('#btn-add-volume').on('click', function() {
                var $btn = $(this);
                var $input = $('#new-volume-title');
                var title = $.trim($input.val());
                
                if (!title) {
                    $input.focus().css('border-color', '#ef4444');
                    return;
                }
                
                $btn.prop('disabled', true).text('در حال افزودن...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'novel_add_volume',
                        novel_id: novelId,
                        title: title,
                        nonce: nonce
                    },
                    success: function(res) {
                        if (res.success) {
                            $('#volumes-empty').remove();
                            $list.append(res.data.html);
                            $input.val('').css('border-color', '');
                            initSortable();
                        } else {
                            alert(res.data.message || 'خطا');
                        }
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('+ افزودن جلد');
                    }
                });
            });

            // Enter key in input
            $('#new-volume-title').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $('#btn-add-volume').click();
                }
            }).on('input', function() {
                $(this).css('border-color', '');
            });

            // ═══ Edit Volume ═══
            $list.on('click', '.btn-edit', function() {
                var $item = $(this).closest('.volume-item');
                $item.find('.vol-title span').addClass('editing');
                $item.find('.vol-title input').addClass('editing').focus().select();
                $item.find('.btn-edit, .btn-delete').hide();
                $item.find('.btn-save, .btn-cancel').show();
            });

            $list.on('click', '.btn-cancel', function() {
                var $item = $(this).closest('.volume-item');
                var original = $item.find('.vol-title span').text();
                $item.find('.vol-title input').val(original).removeClass('editing');
                $item.find('.vol-title span').removeClass('editing');
                $item.find('.btn-edit, .btn-delete').show();
                $item.find('.btn-save, .btn-cancel').hide();
            });

            $list.on('click', '.btn-save', function() {
                var $item = $(this).closest('.volume-item');
                var volId = $item.data('volume-id');
                var newTitle = $.trim($item.find('.vol-title input').val());
                
                if (!newTitle) return;
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'novel_edit_volume',
                        novel_id: novelId,
                        volume_id: volId,
                        title: newTitle,
                        nonce: nonce
                    },
                    success: function(res) {
                        if (res.success) {
                            $item.find('.vol-title span').text(newTitle).removeClass('editing');
                            $item.find('.vol-title input').removeClass('editing');
                            $item.find('.btn-edit, .btn-delete').show();
                            $item.find('.btn-save, .btn-cancel').hide();
                        }
                    }
                });
            });

            // Save on Enter
            $list.on('keypress', '.vol-title input', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $(this).closest('.volume-item').find('.btn-save').click();
                }
            });

            // ═══ Delete Volume ═══
            $list.on('click', '.btn-delete', function() {
                var $item = $(this).closest('.volume-item');
                var volId = $item.data('volume-id');
                var chCount = parseInt($item.find('.vol-chapters').text()) || 0;
                
                var msg = 'آیا از حذف این جلد مطمئنید؟';
                if (chCount > 0) {
                    msg += '\n\n⚠️ ' + chCount + ' قسمت از این جلد به «بدون جلد» منتقل می‌شوند.';
                }
                
                if (!confirm(msg)) return;
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'novel_delete_volume',
                        novel_id: novelId,
                        volume_id: volId,
                        nonce: nonce
                    },
                    success: function(res) {
                        if (res.success) {
                            $item.fadeOut(300, function() {
                                $(this).remove();
                                updateOrders();
                                if ($list.find('.volume-item').length === 0) {
                                    $list.html('<div class="volumes-empty" id="volumes-empty">📂 هنوز جلدی تعریف نشده.</div>');
                                }
                            });
                        }
                    }
                });
            });

            // ═══ Drag & Drop Reorder ═══
            function initSortable() {
                if (typeof $.fn.sortable === 'undefined') return;
                
                $list.sortable({
                    handle: '.drag-handle',
                    placeholder: 'volume-item ui-sortable-placeholder',
                    tolerance: 'pointer',
                    start: function(e, ui) {
                        ui.item.addClass('dragging');
                        ui.placeholder.height(ui.item.outerHeight());
                    },
                    stop: function(e, ui) {
                        ui.item.removeClass('dragging');
                        updateOrders();
                        saveOrder();
                    }
                });
            }

            function updateOrders() {
                $list.find('.volume-item').each(function(i) {
                    $(this).find('.vol-order').text(i + 1);
                });
            }

            function saveOrder() {
                var order = [];
                $list.find('.volume-item').each(function() {
                    order.push($(this).data('volume-id'));
                });
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'novel_reorder_volumes',
                        novel_id: novelId,
                        order: order,
                        nonce: nonce
                    }
                });
            }

            initSortable();
        });
        </script>
        <?php
    }

    /**
     * Enqueue admin scripts (jQuery UI Sortable)
     */
    public function admin_scripts($hook) {
        global $post_type;
        if (($hook === 'post.php' || $hook === 'post-new.php') && $post_type === 'novel') {
            wp_enqueue_script('jquery-ui-sortable');
        }
    }

    // ═══════════════════════════════════════════
    // DATA METHODS
    // ═══════════════════════════════════════════

    /**
     * Get volumes for a novel
     */
    public function get_volumes($novel_id) {
        $volumes = get_post_meta($novel_id, 'novel_volumes', true);
        if (!is_array($volumes)) {
            return [];
        }
        // Sort by order
        usort($volumes, function($a, $b) {
            return ($a['order'] ?? 0) - ($b['order'] ?? 0);
        });
        return $volumes;
    }

    /**
     * Get next volume ID
     */
    private function get_next_volume_id($novel_id) {
        $volumes = $this->get_volumes($novel_id);
        $max_id = 0;
        foreach ($volumes as $vol) {
            if ($vol['id'] > $max_id) {
                $max_id = $vol['id'];
            }
        }
        return $max_id + 1;
    }

    /**
     * Count chapters in a volume
     */
    public function count_volume_chapters($novel_id, $volume_id) {
        global $wpdb;
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = 'chapter_novel_id'
             INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'chapter_volume'
             WHERE p.post_type = 'chapter'
             AND p.post_status IN ('publish','draft','future')
             AND pm1.meta_value = %d
             AND pm2.meta_value = %s",
            $novel_id,
            (string) $volume_id
        ));
    }

    /**
     * Check if novel has volumes
     */
    public static function novel_has_volumes($novel_id) {
        $volumes = get_post_meta($novel_id, 'novel_volumes', true);
        return is_array($volumes) && !empty($volumes);
    }

    /**
     * Get chapters grouped by volume
     */
    public function get_chapters_by_volume($novel_id) {
        $volumes  = $this->get_volumes($novel_id);
        $chapters = novel_get_chapters($novel_id);
        
        $grouped = [];
        
        // Initialize volume groups
        foreach ($volumes as $vol) {
            $grouped[$vol['id']] = [
                'id'       => $vol['id'],
                'title'    => $vol['title'],
                'order'    => $vol['order'],
                'chapters' => [],
            ];
        }
        
        // "No volume" group
        $grouped['none'] = [
            'id'       => 'none',
            'title'    => 'بدون جلد',
            'order'    => 999,
            'chapters' => [],
        ];
        
        // Distribute chapters
        if ($chapters->have_posts()) {
            while ($chapters->have_posts()) {
                $chapters->the_post();
                $vol_id = get_post_meta(get_the_ID(), 'chapter_volume', true);
                
                if (empty($vol_id) || !isset($grouped[$vol_id])) {
                    $grouped['none']['chapters'][] = get_the_ID();
                } else {
                    $grouped[$vol_id]['chapters'][] = get_the_ID();
                }
            }
            wp_reset_postdata();
        }
        
        // Remove empty "no volume" if no chapters there
        if (empty($grouped['none']['chapters'])) {
            unset($grouped['none']);
        }
        
        // Sort by order
        uasort($grouped, function($a, $b) {
            return ($a['order'] ?? 0) - ($b['order'] ?? 0);
        });
        
        return $grouped;
    }

    // ═══════════════════════════════════════════
    // AJAX HANDLERS
    // ═══════════════════════════════════════════

    /**
     * AJAX: Add volume
     */
    public function ajax_add_volume() {
        check_ajax_referer('novel_volumes_action', 'nonce');
        
        $novel_id = absint($_POST['novel_id'] ?? 0);
        $title    = sanitize_text_field($_POST['title'] ?? '');
        
        if (!$novel_id || !$title) {
            wp_send_json_error(['message' => 'داده نامعتبر']);
        }
        
        if (!current_user_can('edit_post', $novel_id)) {
            wp_send_json_error(['message' => 'دسترسی ندارید']);
        }
        
        $volumes = $this->get_volumes($novel_id);
        
        // Max 50 volumes
        if (count($volumes) >= 50) {
            wp_send_json_error(['message' => 'حداکثر ۵۰ جلد مجاز است']);
        }
        
        $new_id    = $this->get_next_volume_id($novel_id);
        $new_order = count($volumes) + 1;
        
        $volumes[] = [
            'id'    => $new_id,
            'title' => $title,
            'order' => $new_order,
        ];
        
        update_post_meta($novel_id, 'novel_volumes', $volumes);
        
        // Generate HTML for new item
        $html = '<div class="volume-item" data-volume-id="' . esc_attr($new_id) . '">'
              . '<span class="drag-handle" title="جابجایی">⠿</span>'
              . '<span class="vol-order">' . esc_html($new_order) . '</span>'
              . '<div class="vol-title">'
              . '<span>' . esc_html($title) . '</span>'
              . '<input type="text" value="' . esc_attr($title) . '" class="editing" />'
              . '</div>'
              . '<span class="vol-chapters">0 قسمت</span>'
              . '<div class="vol-actions">'
              . '<button type="button" class="btn-edit" title="ویرایش">✏️</button>'
              . '<button type="button" class="btn-save" title="ذخیره">✅</button>'
              . '<button type="button" class="btn-cancel" title="انصراف">↩️</button>'
              . '<button type="button" class="btn-delete" title="حذف">🗑️</button>'
              . '</div></div>';
        
        wp_send_json_success(['html' => $html, 'id' => $new_id]);
    }

    /**
     * AJAX: Edit volume title
     */
    public function ajax_edit_volume() {
        check_ajax_referer('novel_volumes_action', 'nonce');
        
        $novel_id  = absint($_POST['novel_id'] ?? 0);
        $volume_id = absint($_POST['volume_id'] ?? 0);
        $title     = sanitize_text_field($_POST['title'] ?? '');
        
        if (!$novel_id || !$volume_id || !$title) {
            wp_send_json_error(['message' => 'داده نامعتبر']);
        }
        
        if (!current_user_can('edit_post', $novel_id)) {
            wp_send_json_error(['message' => 'دسترسی ندارید']);
        }
        
        $volumes = $this->get_volumes($novel_id);
        $found = false;
        
        foreach ($volumes as &$vol) {
            if ($vol['id'] == $volume_id) {
                $vol['title'] = $title;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            wp_send_json_error(['message' => 'جلد یافت نشد']);
        }
        
        update_post_meta($novel_id, 'novel_volumes', $volumes);
        
        wp_send_json_success(['message' => 'ذخیره شد']);
    }

    /**
     * AJAX: Delete volume
     */
    public function ajax_delete_volume() {
        check_ajax_referer('novel_volumes_action', 'nonce');
        
        $novel_id  = absint($_POST['novel_id'] ?? 0);
        $volume_id = absint($_POST['volume_id'] ?? 0);
        
        if (!$novel_id || !$volume_id) {
            wp_send_json_error(['message' => 'داده نامعتبر']);
        }
        
        if (!current_user_can('edit_post', $novel_id)) {
            wp_send_json_error(['message' => 'دسترسی ندارید']);
        }
        
        // Move chapters of this volume to "none"
        global $wpdb;
        $chapter_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT p.ID 
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = 'chapter_novel_id'
             INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'chapter_volume'
             WHERE p.post_type = 'chapter'
             AND pm1.meta_value = %d
             AND pm2.meta_value = %s",
            $novel_id,
            (string) $volume_id
        ));
        
        foreach ($chapter_ids as $ch_id) {
            update_post_meta($ch_id, 'chapter_volume', '');
        }
        
        // Remove volume
        $volumes = $this->get_volumes($novel_id);
        $volumes = array_filter($volumes, function($vol) use ($volume_id) {
            return $vol['id'] != $volume_id;
        });
        
        // Re-order
        $order = 1;
        foreach ($volumes as &$vol) {
            $vol['order'] = $order++;
        }
        
        update_post_meta($novel_id, 'novel_volumes', array_values($volumes));
        
        wp_send_json_success(['message' => 'جلد حذف شد', 'moved_chapters' => count($chapter_ids)]);
    }

    /**
     * AJAX: Reorder volumes
     */
    public function ajax_reorder_volumes() {
        check_ajax_referer('novel_volumes_action', 'nonce');
        
        $novel_id = absint($_POST['novel_id'] ?? 0);
        $order    = isset($_POST['order']) ? array_map('absint', $_POST['order']) : [];
        
        if (!$novel_id || empty($order)) {
            wp_send_json_error(['message' => 'داده نامعتبر']);
        }
        
        if (!current_user_can('edit_post', $novel_id)) {
            wp_send_json_error(['message' => 'دسترسی ندارید']);
        }
        
        $volumes = $this->get_volumes($novel_id);
        $reordered = [];
        
        $i = 1;
        foreach ($order as $vol_id) {
            foreach ($volumes as $vol) {
                if ($vol['id'] == $vol_id) {
                    $vol['order'] = $i++;
                    $reordered[] = $vol;
                    break;
                }
            }
        }
        
        update_post_meta($novel_id, 'novel_volumes', $reordered);
        
        wp_send_json_success(['message' => 'ترتیب ذخیره شد']);
    }

    /**
     * AJAX: Get chapters for a volume (used in frontend accordion)
     */
    public function ajax_get_volume_chapters() {
        $novel_id  = absint($_POST['novel_id'] ?? 0);
        $volume_id = sanitize_text_field($_POST['volume_id'] ?? '');
        $page      = absint($_POST['page'] ?? 1);
        $per_page  = 50;
        
        if (!$novel_id) {
            wp_send_json_error();
        }
        
        $meta_query = [
            [
                'key'   => 'chapter_novel_id',
                'value' => $novel_id,
                'type'  => 'NUMERIC',
            ],
        ];
        
        if ($volume_id === 'none' || empty($volume_id)) {
            $meta_query[] = [
                'relation' => 'OR',
                [
                    'key'     => 'chapter_volume',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key'   => 'chapter_volume',
                    'value' => '',
                ],
            ];
        } else {
            $meta_query[] = [
                'key'   => 'chapter_volume',
                'value' => $volume_id,
            ];
        }
        
        $query = new WP_Query([
            'post_type'      => 'chapter',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'post_status'    => 'publish',
            'meta_query'     => $meta_query,
            'meta_key'       => 'chapter_number',
            'orderby'        => 'meta_value_num',
            'order'          => 'ASC',
        ]);
        
        $chapters = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $id = get_the_ID();
                $chapters[] = [
                    'id'            => $id,
                    'number'        => get_post_meta($id, 'chapter_number', true),
                    'title'         => get_post_meta($id, 'chapter_title', true),
                    'is_vip'        => (bool) get_post_meta($id, 'chapter_is_vip', true),
                    'coin_price'    => get_post_meta($id, 'chapter_coin_price', true),
                    'word_count'    => get_post_meta($id, 'chapter_word_count', true),
                    'reading_time'  => get_post_meta($id, 'chapter_reading_time', true),
                    'date'          => get_the_date('j F Y'),
                    'url'           => novel_get_chapter_permalink($id),
                    'likes'         => get_post_meta($id, 'chapter_likes', true) ?: 0,
                    'comments'      => get_comments_number($id),
                    'views'         => get_post_meta($id, 'chapter_views', true) ?: 0,
                ];
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success([
            'chapters'  => $chapters,
            'total'     => $query->found_posts,
            'max_pages' => $query->max_num_pages,
        ]);
    }
}