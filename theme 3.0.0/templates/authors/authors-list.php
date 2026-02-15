<?php
/**
 * Authors List Page (Shortcode)
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

$sort   = sanitize_text_field($_GET['sort'] ?? 'novels');
$search = sanitize_text_field($_GET['search'] ?? '');
$page   = max(1, absint($_GET['pg'] ?? 1));

$result = Novel_Authors::get_authors([
    'sort'     => $sort,
    'search'   => $search,
    'page'     => $page,
    'per_page' => 12,
]);
?>

<div class="authors-page">
    <header class="authors-header">
        <h1 class="authors-title">✍️ نویسندگان</h1>
    </header>

    <div class="authors-filters" id="authorsFilters">
        <div class="authors-search-wrap">
            <input type="text" class="authors-search-input" id="authorsSearch"
                   value="<?php echo esc_attr($search); ?>"
                   placeholder="🔍 جستجوی نام نویسنده..." />
        </div>

        <div class="authors-sort-wrap">
            <span class="sort-label">مرتب‌سازی:</span>
            <select class="authors-sort-select" id="authorsSort">
                <option value="novels" <?php selected($sort, 'novels'); ?>>بیشترین رمان</option>
                <option value="rating" <?php selected($sort, 'rating'); ?>>بالاترین امتیاز</option>
                <option value="newest" <?php selected($sort, 'newest'); ?>>جدیدترین</option>
                <option value="active" <?php selected($sort, 'active'); ?>>فعال‌ترین</option>
                <option value="followers" <?php selected($sort, 'followers'); ?>>بیشترین فالوور</option>
            </select>
        </div>
    </div>

    <div class="authors-grid" id="authorsGrid">
        <?php if (!empty($result['users'])) : ?>
            <?php foreach ($result['users'] as $user) :
                $GLOBALS['author_card_user'] = $user;
                get_template_part('templates/authors/author-card');
            endforeach; ?>
        <?php else : ?>
            <div class="authors-empty"><p>نویسنده‌ای یافت نشد.</p></div>
        <?php endif; ?>
    </div>

    <?php if ($result['max_pages'] > 1) : ?>
        <div class="authors-load-more-wrap">
            <button class="btn-load-more-authors"
                    data-page="2" data-max="<?php echo $result['max_pages']; ?>"
                    data-sort="<?php echo esc_attr($sort); ?>"
                    data-search="<?php echo esc_attr($search); ?>">
                بارگذاری بیشتر...
            </button>
        </div>
    <?php endif; ?>
</div>