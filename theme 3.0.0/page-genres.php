<?php
/**
 * Template Name: صفحه ژانرها
 * Genres Page
 * 
 * @package suspended developer
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

get_header();

// دریافت تمام ژانرها
$genres = get_terms([
    'taxonomy'   => 'genre',
    'hide_empty' => false,
    'orderby'    => 'count',
    'order'      => 'DESC',
]);

// آیکون‌های پیش‌فرض ژانرها
$default_icons = [
    'action'        => '🗡',
    'romance'       => '❤',
    'fantasy'       => '🧙',
    'horror'        => '👻',
    'comedy'        => '😂',
    'sci-fi'        => '🔬',
    'historical'    => '📜',
    'psychology'    => '🧠',
    'adventure'     => '⚔',
    'drama'         => '🎭',
    'mystery'       => '🔍',
    'thriller'      => '😱',
    'slice-of-life' => '🌸',
    'sports'        => '⚽',
    'school'        => '🏫',
    'martial-arts'  => '🥋',
    'harem'         => '💕',
    'isekai'        => '🌍',
    'mecha'         => '🤖',
    'supernatural'  => '👁',
    'military'      => '🎖',
    'tragedy'       => '😢',
    'mature'        => '🔞',
    'shounen'       => '💪',
    'shoujo'        => '🌹',
    'josei'         => '👩',
    'seinen'        => '🧔',
    'wuxia'         => '⚔️',
    'xianxia'       => '☯',
    'xuanhuan'      => '🌀',
];

// رنگ‌های پیش‌فرض
$default_colors = [
    '#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16',
    '#22c55e', '#10b981', '#14b8a6', '#06b6d4', '#0ea5e9',
    '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef',
    '#ec4899', '#f43f5e', '#64748b', '#78716c', '#059669',
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
                <span class="novel-page-icon">🎭</span>
                ژانرها
            </h1>
            <p class="novel-page-subtitle">ژانر مورد علاقه‌ات رو پیدا کن و کلی رمان باحال بخون!</p>
        </div>

        <?php if (!empty($genres) && !is_wp_error($genres)): ?>
            <div class="novel-genres-grid">
                <?php
                $color_index = 0;
                foreach ($genres as $genre):
                    $icon = get_term_meta($genre->term_id, 'genre_icon', true);
                    $color = get_term_meta($genre->term_id, 'genre_color', true);

                    if (empty($icon)) {
                        $icon = $default_icons[$genre->slug] ?? '📚';
                    }
                    if (empty($color)) {
                        $color = $default_colors[$color_index % count($default_colors)];
                    }
                    $color_index++;

                    $link = get_term_link($genre);
                    if (is_wp_error($link)) continue;
                    ?>
                    <a href="<?php echo esc_url($link); ?>"
                       class="novel-genre-card"
                       style="--genre-color: <?php echo esc_attr($color); ?>;">
                        <div class="novel-genre-card__icon"><?php echo $icon; ?></div>
                        <div class="novel-genre-card__info">
                            <h3 class="novel-genre-card__name"><?php echo esc_html($genre->name); ?></h3>
                            <span class="novel-genre-card__count">
                                <?php echo number_format_i18n($genre->count); ?> رمان
                            </span>
                        </div>
                        <div class="novel-genre-card__arrow">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"/>
                            </svg>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="novel-empty-state">
                <div class="novel-empty-icon">🎭</div>
                <h3>ژانری یافت نشد</h3>
                <p>هنوز ژانری ایجاد نشده است.</p>
            </div>
        <?php endif; ?>

        <!-- ابر تگ -->
        <?php
        $novel_tags = get_terms([
            'taxonomy'   => 'novel_tag',
            'hide_empty' => true,
            'orderby'    => 'count',
            'order'      => 'DESC',
            'number'     => 50,
        ]);
        if (!empty($novel_tags) && !is_wp_error($novel_tags)):
            $max_count = max(array_column((array)$novel_tags, 'count'));
            $min_count = min(array_column((array)$novel_tags, 'count'));
            ?>
            <div class="novel-tag-cloud-section">
                <h2 class="novel-section-title">
                    <span class="novel-section-icon">🏷</span>
                    برچسب‌های محبوب
                </h2>
                <div class="novel-tag-cloud">
                    <?php foreach ($novel_tags as $tag):
                        $size = ($max_count > $min_count)
                            ? 12 + (($tag->count - $min_count) / ($max_count - $min_count)) * 10
                            : 14;
                        $link = get_term_link($tag);
                        if (is_wp_error($link)) continue;

                        // رنگ‌های تصادفی ولی ثابت
                        $tag_colors = ['#6366f1','#ec4899','#f59e0b','#10b981','#3b82f6','#8b5cf6','#ef4444','#06b6d4'];
                        $tag_color = $tag_colors[crc32($tag->slug) % count($tag_colors)];
                        ?>
                        <a href="<?php echo esc_url($link); ?>"
                           class="novel-tag-cloud__item"
                           style="font-size: <?php echo round($size); ?>px; --tag-color: <?php echo $tag_color; ?>;">
                            <?php echo esc_html($tag->name); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php get_footer(); ?>