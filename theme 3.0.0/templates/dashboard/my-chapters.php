<?php
/**
 * Dashboard: My Chapters List
 * 
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) return;

$user_id  = get_current_user_id();
$novel_id = absint($_GET['novel_id'] ?? 0);

// Get user's novels
$my_novels = get_posts([
    'post_type'      => 'novel',
    'author'         => $user_id,
    'posts_per_page' => -1,
    'post_status'    => ['publish', 'pending', 'draft'],
    'orderby'        => 'title',
    'order'          => 'ASC',
]);

// Handle delete
if (isset($_POST['delete_chapter_id']) && wp_verify_nonce($_POST['delete_chapter_nonce'], 'novel_delete_chapter')) {
    $del_id = absint($_POST['delete_chapter_id']);
    $del_post = get_post($del_id);
    if ($del_post && $del_post->post_type === 'chapter' && (int) $del_post->post_author === $user_id) {
        wp_delete_post($del_id, true);
        echo '<div class="form-alert form-alert--success">✅ قسمت حذف شد.</div>';
    }
}
?>

<div class="dashboard-chapters-page">
    <div class="chapters-page-header">
        <h2 class="dashboard-form-title">📋 مدیریت قسمت‌ها</h2>
        <a href="<?php echo home_url('/dashboard/?tab=add-chapter' . ($novel_id ? '&novel_id=' . $novel_id : '')); ?>" class="btn-add-chapter">
            + افزودن قسمت
        </a>
    </div>
    
    <?php if (empty($my_novels)) : ?>
        <div class="form-alert form-alert--warning">
            هنوز رمانی ندارید. <a href="<?php echo home_url('/dashboard/?tab=add-novel'); ?>">اولین رمان را بسازید!</a>
        </div>
        <?php return; ?>
    <?php endif; ?>
    
    <!-- Novel selector -->
    <div class="form-group" style="margin-bottom:20px;">
        <select id="chapterNovelFilter" class="form-select" onchange="window.location.href='<?php echo home_url('/dashboard/?tab=my-chapters&novel_id='); ?>'+this.value;">
            <option value="">-- انتخاب رمان --</option>
            <?php foreach ($my_novels as $nov) : ?>
                <option value="<?php echo $nov->ID; ?>" <?php selected($novel_id, $nov->ID); ?>>
                    <?php echo esc_html($nov->post_title); ?>
                    (<?php echo novel_get_chapter_count($nov->ID, 'any'); ?> قسمت)
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <?php if ($novel_id) :
        $paged = max(1, absint($_GET['ch_page'] ?? 1));
        $chapters = new WP_Query([
            'post_type'      => 'chapter',
            'posts_per_page' => 30,
            'paged'          => $paged,
            'post_status'    => ['publish', 'draft', 'pending', 'future'],
            'author'         => $user_id,
            'meta_query'     => [
                ['key' => 'chapter_novel_id', 'value' => $novel_id, 'type' => 'NUMERIC'],
            ],
            'meta_key'       => 'chapter_number',
            'orderby'        => 'meta_value_num',
            'order'          => 'DESC',
        ]);
    ?>
    
        <?php if ($chapters->have_posts()) : ?>
        <div class="chapters-table-wrap">
            <table class="chapters-table">
                <thead>
                    <tr>
                        <th>＃</th>
                        <th>عنوان</th>
                        <th>تاریخ</th>
                        <th>وضعیت</th>
                        <th>نوع</th>
                        <th>👁</th>
                        <th>👍</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($chapters->have_posts()) : $chapters->the_post();
                        $ch_id     = get_the_ID();
                        $ch_num    = get_post_meta($ch_id, 'chapter_number', true);
                        $ch_title  = get_post_meta($ch_id, 'chapter_title', true);
                        $ch_vip    = get_post_meta($ch_id, 'chapter_is_vip', true);
                        $ch_views  = get_post_meta($ch_id, 'chapter_views', true) ?: 0;
                        $ch_likes  = get_post_meta($ch_id, 'chapter_likes', true) ?: 0;
                        $ch_status = get_post_status();
                        
                        $status_labels = [
                            'publish' => ['✅ منتشر', 'published'],
                            'draft'   => ['📝 پیش‌نویس', 'draft'],
                            'pending' => ['⏳ در انتظار', 'pending'],
                            'future'  => ['⏰ زمان‌بندی', 'scheduled'],
                        ];
                        $st = $status_labels[$ch_status] ?? ['—', 'unknown'];
                    ?>
                    <tr>
                        <td class="ch-num"><strong><?php echo esc_html($ch_num); ?></strong></td>
                        <td class="ch-title"><?php echo $ch_title ? esc_html($ch_title) : '<span class="text-muted">—</span>'; ?></td>
                        <td class="ch-date"><?php echo get_the_date('j M Y'); ?></td>
                        <td><span class="ch-status ch-status--<?php echo $st[1]; ?>"><?php echo $st[0]; ?></span></td>
                        <td>
                            <?php if ($ch_vip) : ?>
                                <span class="vip-badge locked">VIP 🔒</span>
                            <?php else : ?>
                                <span class="free-badge">✅ رایگان</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo number_format_i18n($ch_views); ?></td>
                        <td><?php echo number_format_i18n($ch_likes); ?></td>
                        <td class="ch-actions">
                            <a href="<?php echo home_url('/dashboard/?tab=edit-chapter&chapter_id=' . $ch_id); ?>" class="ch-btn ch-btn-edit" title="ویرایش">✏️</a>
                            <?php if ($ch_status === 'publish') : ?>
                                <a href="<?php echo novel_get_chapter_permalink($ch_id); ?>" class="ch-btn ch-btn-view" title="مشاهده" target="_blank">👁</a>
                            <?php endif; ?>
                            <form method="post" class="ch-delete-form" onsubmit="return confirm('آیا از حذف قسمت <?php echo esc_attr($ch_num); ?> مطمئنید؟\nاین عمل غیرقابل بازگشت است.');">
                                <?php wp_nonce_field('novel_delete_chapter', 'delete_chapter_nonce'); ?>
                                <input type="hidden" name="delete_chapter_id" value="<?php echo $ch_id; ?>" />
                                <button type="submit" class="ch-btn ch-btn-delete" title="حذف">🗑️</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; wp_reset_postdata(); ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($chapters->max_num_pages > 1) : ?>
            <div class="chapters-pagination">
                <?php echo paginate_links([
                    'total'   => $chapters->max_num_pages,
                    'current' => $paged,
                    'format'  => '?ch_page=%#%',
                    'add_args' => ['tab' => 'my-chapters', 'novel_id' => $novel_id],
                ]); ?>
            </div>
        <?php endif; ?>
        
        <?php else : ?>
            <div class="chapters-empty">
                <p>📖 هنوز قسمتی ننوشته‌اید.</p>
                <a href="<?php echo home_url('/dashboard/?tab=add-chapter&novel_id=' . $novel_id); ?>" class="btn-add-chapter">
                    اولین قسمت را بنویسید! 📝
                </a>
            </div>
        <?php endif; ?>
    
    <?php else : ?>
        <div class="chapters-empty">
            <p>یک رمان از لیست بالا انتخاب کنید تا قسمت‌های آن نمایش داده شود.</p>
        </div>
    <?php endif; ?>
</div>