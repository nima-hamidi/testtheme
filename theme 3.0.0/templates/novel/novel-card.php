<?php
/**
 * Novel Card Template (Main/Large)
 * 
 * Used in: archive, homepage, search results
 * 
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$card_id        = get_the_ID();
$english_name   = get_post_meta($card_id, 'novel_english_name', true);
$novel_type     = get_post_meta($card_id, 'novel_type', true) ?: 'web_novel';
$original_author = get_post_meta($card_id, 'novel_original_author', true);
$avg_rating     = get_post_meta($card_id, 'novel_avg_rating', true) ?: 0;
$views          = get_post_meta($card_id, 'novel_views', true) ?: 0;
$follow_count   = get_post_meta($card_id, 'novel_follow_count', true) ?: 0;
$comment_count  = get_comments_number($card_id);
$chapter_counts = novel_get_chapter_counts_by_type($card_id);

$genres = wp_get_post_terms($card_id, 'genre', ['number' => 3]);
$status_terms = wp_get_post_terms($card_id, 'novel_status');
$status_slug = !empty($status_terms) ? $status_terms[0]->slug : 'unknown';
$status_name = !empty($status_terms) ? $status_terms[0]->name : 'نامشخص';

$status_icons = [
    'ongoing'   => '🟢',
    'completed' => '✅',
    'hiatus'    => '⏸️',
    'dropped'   => '❌',
];
$status_icon = isset($status_icons[$status_slug]) ? $status_icons[$status_slug] : '⚪';

// VIP status
$has_vip = $chapter_counts['vip'] > 0;
$all_vip = $chapter_counts['total'] > 0 && $chapter_counts['free'] === 0;

$card_classes = ['novel-card'];
if ($novel_type === 'light_novel') $card_classes[] = 'novel-card--ln';
?>

<article class="<?php echo implode(' ', $card_classes); ?>">
    <a href="<?php the_permalink(); ?>" class="novel-card__link">
        
        <!-- Cover -->
        <div class="novel-card__cover">
            <?php if (has_post_thumbnail()) : ?>
                <?php the_post_thumbnail('medium_large', [
                    'class'   => 'novel-card__img',
                    'loading' => 'lazy',
                ]); ?>
            <?php else : ?>
                <div class="novel-card__placeholder">📖</div>
            <?php endif; ?>
            
            <!-- Type Badge -->
            <span class="novel-card__type-badge type-<?php echo $novel_type === 'light_novel' ? 'ln' : 'wn'; ?>">
                <?php echo $novel_type === 'light_novel' ? 'LN' : 'WN'; ?>
            </span>
            
            <!-- Rating Badge -->
            <?php if ($avg_rating > 0) : ?>
            <span class="novel-card__rating-badge">★ <?php echo number_format($avg_rating, 1); ?></span>
            <?php endif; ?>
            
            <!-- VIP Badge -->
            <?php if ($all_vip) : ?>
                <span class="novel-card__vip-badge vip-full">🔒 اشتراکی</span>
            <?php elseif ($has_vip) : ?>
                <span class="novel-card__vip-badge vip-partial">👑 VIP</span>
            <?php else : ?>
                <span class="novel-card__vip-badge vip-free">✅ رایگان</span>
            <?php endif; ?>
        </div>
        
        <!-- Info -->
        <div class="novel-card__info">
            <h3 class="novel-card__title"><?php the_title(); ?></h3>
            
            <p class="novel-card__author">
                نویسنده: <?php echo esc_html($original_author ?: get_the_author()); ?>
            </p>
            
            <!-- Genres -->
            <?php if (!empty($genres) && !is_wp_error($genres)) : ?>
            <div class="novel-card__genres">
                <?php foreach ($genres as $genre) : ?>
                    <span class="genre-mini-badge" style="--genre-color: <?php echo esc_attr(get_term_meta($genre->term_id, 'genre_color', true) ?: '#6366f1'); ?>">
                        <?php echo esc_html($genre->name); ?>
                    </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Stats -->
            <div class="novel-card__stats">
                <span>📖 <?php echo number_format_i18n($chapter_counts['total']); ?></span>
                <span>👁 <?php echo number_format_i18n($views); ?></span>
                <span>❤ <?php echo number_format_i18n($follow_count); ?></span>
                <span>💬 <?php echo number_format_i18n($comment_count); ?></span>
            </div>
            
            <!-- Status -->
            <div class="novel-card__status status-<?php echo esc_attr($status_slug); ?>">
                <?php echo $status_icon . ' ' . esc_html($status_name); ?>
            </div>
        </div>
    </a>
    
    <!-- Action Buttons (stop propagation) -->
    <div class="novel-card__actions">
        <button class="card-btn-library" 
                data-novel-id="<?php echo $card_id; ?>" 
                title="افزودن به کتابخانه"
                onclick="event.stopPropagation(); event.preventDefault();">
            📚
        </button>
        <button class="card-btn-follow" 
                data-novel-id="<?php echo $card_id; ?>" 
                title="دنبال کردن"
                onclick="event.stopPropagation(); event.preventDefault();">
            🤍
        </button>
    </div>
</article>