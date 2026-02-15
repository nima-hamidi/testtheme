<?php
/**
 * Novel Card Small Template
 * 
 * Used in: rankings, similar novels, sidebar
 * 
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$card_id         = get_the_ID();
$novel_type      = get_post_meta($card_id, 'novel_type', true) ?: 'web_novel';
$original_author = get_post_meta($card_id, 'novel_original_author', true);
$avg_rating      = get_post_meta($card_id, 'novel_avg_rating', true) ?: 0;
$chapter_counts  = novel_get_chapter_counts_by_type($card_id);
?>

<a href="<?php the_permalink(); ?>" class="novel-card-small">
    <div class="novel-card-small__cover">
        <?php if (has_post_thumbnail()) : ?>
            <?php the_post_thumbnail('thumbnail', [
                'class'   => 'novel-card-small__img',
                'loading' => 'lazy',
            ]); ?>
        <?php else : ?>
            <div class="novel-card-small__placeholder">📖</div>
        <?php endif; ?>
    </div>
    
    <div class="novel-card-small__info">
        <h4 class="novel-card-small__title"><?php the_title(); ?></h4>
        <p class="novel-card-small__meta">
            <?php echo esc_html($original_author ?: get_the_author()); ?>
            <span class="sep">|</span>
            <?php echo $novel_type === 'light_novel' ? 'LN' : 'WN'; ?>
            <span class="sep">|</span>
            📖 <?php echo number_format_i18n($chapter_counts['total']); ?>
        </p>
    </div>
    
    <?php if ($avg_rating > 0) : ?>
    <div class="novel-card-small__rating">
        ★ <?php echo number_format($avg_rating, 1); ?>
    </div>
    <?php endif; ?>
</a>