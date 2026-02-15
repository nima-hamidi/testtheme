<?php
/**
 * فایل: templates/components/pagination.php
 * توضیح: کامپوننت pagination مشترک - Load More + Numbered
 * نسخه: 2.0.0
 * وابستگی: main.js
 *
 * استفاده: novel_pagination($query, 'auto')
 */

if (!defined('ABSPATH')) exit;

global $wp_query;
$query = $query ?? $wp_query;
$total_pages = (int) $query->max_num_pages;
$current_page = max(1, get_query_var('paged', 1));
$type = $type ?? 'auto';

if ($total_pages <= 1) return;

if ($type === 'auto') {
    $type = wp_is_mobile() ? 'load_more' : 'numbered';
}

$total_results = (int) $query->found_posts;
$per_page = (int) $query->query_vars['posts_per_page'];
$shown = min($current_page * $per_page, $total_results);
?>

<?php if ($type === 'load_more') : ?>
<!-- ═══ Load More ═══ -->
<div class="novel-pagination novel-pagination-loadmore" 
     data-page="<?php echo esc_attr($current_page); ?>" 
     data-max="<?php echo esc_attr($total_pages); ?>">
    
    <?php if ($current_page < $total_pages) : ?>
    <button class="novel-btn novel-btn-secondary novel-btn-full novel-load-more-btn"
            data-next-page="<?php echo esc_attr($current_page + 1); ?>"
            aria-label="بارگذاری بیشتر">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6 9 12 15 18 9"></polyline>
        </svg>
        بارگذاری بیشتر
    </button>
    <?php endif; ?>

    <div class="novel-pagination-info text-center text-muted text-sm mt-sm">
        نمایش <?php echo esc_html(novel_fa_num($shown)); ?> از <?php echo esc_html(novel_fa_num($total_results)); ?>
    </div>
</div>

<?php else : ?>
<!-- ═══ Numbered ═══ -->
<nav class="novel-pagination novel-pagination-numbered" aria-label="صفحه‌بندی">
    <div class="novel-pagination-list">
        <?php
        // دکمه قبلی
        if ($current_page > 1) :
        ?>
        <a href="<?php echo esc_url(get_pagenum_link($current_page - 1)); ?>" 
           class="novel-page-btn novel-page-prev" aria-label="صفحه قبل">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
        </a>
        <?php endif; ?>

        <?php
        // شماره صفحات
        $range = 2;
        $showitems = ($range * 2) + 1;

        if ($total_pages > 1) :
            // صفحه اول
            if ($current_page > $range + 1) :
            ?>
                <a href="<?php echo esc_url(get_pagenum_link(1)); ?>" class="novel-page-btn"><?php echo esc_html(novel_fa_num(1)); ?></a>
                <?php if ($current_page > $range + 2) : ?>
                    <span class="novel-page-dots">...</span>
                <?php endif; ?>
            <?php endif;

            for ($i = max(1, $current_page - $range); $i <= min($total_pages, $current_page + $range); $i++) :
                if ($i === $current_page) :
                ?>
                    <span class="novel-page-btn novel-page-current" aria-current="page"><?php echo esc_html(novel_fa_num($i)); ?></span>
                <?php else : ?>
                    <a href="<?php echo esc_url(get_pagenum_link($i)); ?>" class="novel-page-btn"><?php echo esc_html(novel_fa_num($i)); ?></a>
                <?php endif;
            endfor;

            // صفحه آخر
            if ($current_page < $total_pages - $range) :
                if ($current_page < $total_pages - $range - 1) : ?>
                    <span class="novel-page-dots">...</span>
                <?php endif; ?>
                <a href="<?php echo esc_url(get_pagenum_link($total_pages)); ?>" class="novel-page-btn"><?php echo esc_html(novel_fa_num($total_pages)); ?></a>
            <?php endif;
        endif;

        // دکمه بعدی
        if ($current_page < $total_pages) :
        ?>
        <a href="<?php echo esc_url(get_pagenum_link($current_page + 1)); ?>" 
           class="novel-page-btn novel-page-next" aria-label="صفحه بعد">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <?php endif; ?>
    </div>
</nav>

<style>
.novel-pagination-numbered {
    display: flex;
    justify-content: center;
    padding: var(--spacing-lg) 0;
}
.novel-pagination-list {
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
}
.novel-page-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: 0 var(--spacing-sm);
    border-radius: var(--border-radius-sm);
    font-size: var(--font-size-sm);
    font-weight: 600;
    color: var(--text-secondary);
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    transition: all var(--transition-fast);
    text-decoration: none;
}
.novel-page-btn:hover {
    background: var(--bg-hover);
    color: var(--primary);
    border-color: var(--primary);
}
.novel-page-current {
    background: var(--primary) !important;
    color: var(--text-inverse) !important;
    border-color: var(--primary) !important;
}
.novel-page-dots {
    display: flex;
    align-items: center;
    padding: 0 var(--spacing-xs);
    color: var(--text-tertiary);
}
</style>
<?php endif; ?>