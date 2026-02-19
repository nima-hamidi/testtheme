<?php
/**
 * Template Name: مقایسه رمان
 * Novel Comparison Page
 * شورتکد: [novel_compare] یا page template
 * 
 * @package suspended developer
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

get_header();

// دریافت رمان‌ها از URL
$novel_ids_raw = sanitize_text_field($_GET['novels'] ?? '');
$novel_ids = array_filter(array_map('absint', explode(',', $novel_ids_raw)));
$novel_ids = array_slice($novel_ids, 0, 3); // حداکثر ۳

$novels_data = [];
foreach ($novel_ids as $nid) {
    $post = get_post($nid);
    if (!$post || $post->post_type !== 'novel' || $post->post_status !== 'publish') continue;

    $genres = wp_get_post_terms($nid, 'genre', ['fields' => 'names']);
    $novel_type = get_post_meta($nid, '_novel_type', true) ?: '-';

    $novels_data[] = [
        'id'         => $nid,
        'title'      => $post->post_title,
        'url'        => get_permalink($nid),
        'image'      => get_the_post_thumbnail_url($nid, 'medium') ?: '',
        'type'       => $novel_type,
        'genres'     => implode('، ', $genres),
        'rating'     => (float)get_post_meta($nid, '_novel_rating', true),
        'rating_count' => (int)get_post_meta($nid, '_novel_rating_count', true),
        'chapters'   => (int)get_post_meta($nid, '_novel_chapter_count', true),
        'views'      => (int)get_post_meta($nid, '_novel_views', true),
        'followers'  => (int)get_post_meta($nid, '_novel_followers', true),
        'status'     => get_post_meta($nid, '_novel_status', true) ?: 'ongoing',
        'author'     => get_the_author_meta('display_name', $post->post_author),
        'author_url' => get_author_posts_url($post->post_author),
        'date'       => get_the_date('Y/m/d', $nid),
    ];
}

$status_labels = [
    'ongoing'   => ['🟢 در حال انتشار', '#10b981'],
    'completed' => ['✅ تکمیل شده', '#3b82f6'],
    'hiatus'    => ['⏸ متوقف', '#f59e0b'],
    'dropped'   => ['❌ رها شده', '#ef4444'],
];

$type_labels = [
    'web_novel'   => 'وب نول (WN)',
    'light_novel' => 'لایت نول (LN)',
    'original'    => 'اورجینال',
];
?>

<main class="novel-main">
    <div class="novel-container">

        <?php if (function_exists('novel_breadcrumbs')): ?>
            <div class="novel-breadcrumbs-wrap">
                <?php novel_breadcrumbs(); ?>
            </div>
        <?php endif; ?>

        <div class="novel-page-header" style="text-align: center;">
            <h1 class="novel-page-title">
                <span class="novel-page-icon">⚖️</span>
                مقایسه رمان‌ها
            </h1>
            <p class="novel-page-subtitle">رمان‌های مورد نظرت رو انتخاب کن و مقایسه کن!</p>
        </div>

        <!-- فرم انتخاب رمان‌ها -->
        <div class="novel-compare-selector" id="compareSelector">
            <form method="get" class="novel-compare-form">
                <div class="novel-compare-inputs">
                    <?php for ($i = 0; $i < 3; $i++): ?>
                        <div class="novel-compare-input-wrap">
                            <label>رمان <?php echo $i + 1; ?> <?php echo $i < 2 ? '*' : '(اختیاری)'; ?></label>
                            <div class="novel-compare-search-wrap">
                                <input type="text"
                                       class="novel-compare-search"
                                       placeholder="جستجوی نام رمان..."
                                       data-index="<?php echo $i; ?>"
                                       value="<?php echo isset($novels_data[$i]) ? esc_attr($novels_data[$i]['title']) : ''; ?>"
                                       autocomplete="off">
                                <input type="hidden"
                                       class="novel-compare-id"
                                       name="novel_<?php echo $i; ?>"
                                       value="<?php echo isset($novel_ids[$i]) ? $novel_ids[$i] : ''; ?>">
                                <div class="novel-compare-dropdown" style="display: none;"></div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
                <button type="submit" class="novel-btn novel-btn--primary" id="compareSubmit">
                    ⚖️ مقایسه کن!
                </button>
            </form>
        </div>

        <!-- جدول مقایسه -->
        <?php if (count($novels_data) >= 2): ?>
            <div class="novel-compare-table-wrap">
                <table class="novel-compare-table">
                    <thead>
                        <tr>
                            <th class="novel-compare-th-label"></th>
                            <?php foreach ($novels_data as $novel): ?>
                                <th class="novel-compare-th-novel">
                                    <a href="<?php echo esc_url($novel['url']); ?>" class="novel-compare-novel-link">
                                        <?php if ($novel['image']): ?>
                                            <img src="<?php echo esc_url($novel['image']); ?>"
                                                 alt="<?php echo esc_attr($novel['title']); ?>"
                                                 class="novel-compare-img">
                                        <?php endif; ?>
                                        <span class="novel-compare-novel-title"><?php echo esc_html($novel['title']); ?></span>
                                    </a>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rows = [
                            ['label' => 'نوع', 'key' => 'type', 'format' => 'type'],
                            ['label' => 'ژانر', 'key' => 'genres', 'format' => 'text'],
                            ['label' => 'امتیاز', 'key' => 'rating', 'format' => 'rating'],
                            ['label' => 'تعداد امتیاز', 'key' => 'rating_count', 'format' => 'number'],
                            ['label' => 'قسمت‌ها', 'key' => 'chapters', 'format' => 'number'],
                            ['label' => 'بازدید', 'key' => 'views', 'format' => 'number_k'],
                            ['label' => 'دنبال‌کننده', 'key' => 'followers', 'format' => 'number'],
                            ['label' => 'وضعیت', 'key' => 'status', 'format' => 'status'],
                            ['label' => 'نویسنده', 'key' => 'author', 'format' => 'author'],
                            ['label' => 'تاریخ انتشار', 'key' => 'date', 'format' => 'text'],
                        ];

                        foreach ($rows as $row):
                            // پیدا کردن بالاترین مقدار عددی
                            $max_val = 0;
                            $is_numeric = in_array($row['format'], ['number', 'number_k', 'rating']);
                            if ($is_numeric) {
                                foreach ($novels_data as $n) {
                                    $val = is_numeric($n[$row['key']]) ? (float)$n[$row['key']] : 0;
                                    $max_val = max($max_val, $val);
                                }
                            }
                            ?>
                            <tr>
                                <td class="novel-compare-label">
                                    <?php echo esc_html($row['label']); ?>
                                </td>
                                <?php foreach ($novels_data as $novel):
                                    $val = $novel[$row['key']];
                                    $is_best = $is_numeric && $max_val > 0 && (float)$val === $max_val;
                                    $best_class = $is_best ? 'is-best' : '';
                                    ?>
                                    <td class="novel-compare-value <?php echo $best_class; ?>">
                                        <?php
                                        switch ($row['format']) {
                                            case 'rating':
                                                echo '<span class="novel-compare-stars">★ ' . number_format($val, 1) . '</span>';
                                                break;
                                            case 'number':
                                                echo number_format_i18n($val);
                                                break;
                                            case 'number_k':
                                                echo $val >= 1000 ? number_format_i18n(round($val / 1000, 1)) . 'K' : number_format_i18n($val);
                                                break;
                                            case 'status':
                                                $s = $status_labels[$val] ?? ['❓ نامشخص', '#64748b'];
                                                echo '<span style="color: ' . $s[1] . '; font-weight: 600;">' . $s[0] . '</span>';
                                                break;
                                            case 'type':
                                                echo esc_html($type_labels[$val] ?? $val);
                                                break;
                                            case 'author':
                                                echo '<a href="' . esc_url($novel['author_url']) . '">' . esc_html($val) . '</a>';
                                                break;
                                            default:
                                                echo esc_html($val ?: '-');
                                        }
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- لینک مقایسه قابل اشتراک -->
            <div class="novel-compare-share">
                <span>🔗 لینک مقایسه:</span>
                <input type="text" readonly
                       value="<?php echo esc_url(add_query_arg('novels', implode(',', $novel_ids), home_url('/compare/'))); ?>"
                       class="novel-compare-share-input"
                       onclick="this.select();">
                <button class="novel-btn novel-btn--outline novel-btn--sm novel-copy-btn"
                        onclick="navigator.clipboard.writeText(this.previousElementSibling.value).then(()=>{if(typeof NovelApp!=='undefined')NovelApp.showToast('کپی شد! ✅','success')})">
                    📋 کپی
                </button>
            </div>

        <?php elseif (!empty($novel_ids)): ?>
            <div class="novel-empty-state">
                <div class="novel-empty-icon">⚖️</div>
                <h3>حداقل ۲ رمان برای مقایسه انتخاب کنید</h3>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php get_footer(); ?>