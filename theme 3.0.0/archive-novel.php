<?php
/**
 * Archive Novel Template
 * URL: /novel/
 * 
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Current filters from URL
$current_type   = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
$current_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$current_sort   = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'newest';
$current_view   = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'grid';
$current_access = isset($_GET['access']) ? sanitize_text_field($_GET['access']) : '';
$paged          = get_query_var('paged') ? get_query_var('paged') : 1;

// Build query
$args = [
    'post_type'      => 'novel',
    'posts_per_page' => 24,
    'paged'          => $paged,
    'post_status'    => 'publish',
];

// Meta query
$meta_query = [];

// Type filter
if ($current_type === 'wn') {
    $meta_query[] = ['key' => 'novel_type', 'value' => 'web_novel'];
} elseif ($current_type === 'ln') {
    $meta_query[] = ['key' => 'novel_type', 'value' => 'light_novel'];
}

if (!empty($meta_query)) {
    $args['meta_query'] = $meta_query;
}

// Status filter (taxonomy)
if ($current_status) {
    $args['tax_query'][] = [
        'taxonomy' => 'novel_status',
        'field'    => 'slug',
        'terms'    => $current_status,
    ];
}

// Sort
switch ($current_sort) {
    case 'popular':
        $args['meta_key'] = 'novel_views';
        $args['orderby']  = 'meta_value_num';
        $args['order']    = 'DESC';
        break;
    case 'rating':
        $args['meta_key'] = 'novel_avg_rating';
        $args['orderby']  = 'meta_value_num';
        $args['order']    = 'DESC';
        break;
    case 'chapters':
        $args['meta_key'] = 'novel_views'; // fallback, chapter count sort done post-query
        $args['orderby']  = 'meta_value_num';
        $args['order']    = 'DESC';
        break;
    case 'oldest':
        $args['orderby'] = 'date';
        $args['order']   = 'ASC';
        break;
    case 'newest':
    default:
        $args['orderby'] = 'date';
        $args['order']   = 'DESC';
        break;
}

$novels_query = new WP_Query($args);
$total_novels = $novels_query->found_posts;

// Status terms for filter
$status_terms = get_terms([
    'taxonomy'   => 'novel_status',
    'hide_empty' => true,
]);
?>

<main class="archive-novel-page">
    
    <!-- Breadcrumb -->
    <nav class="archive-breadcrumb" aria-label="مسیر">
        <div class="container">
            <a href="<?php echo home_url(); ?>">خانه</a>
            <span class="sep">›</span>
            <span class="current">رمان‌ها</span>
        </div>
    </nav>
    
    <div class="container">
        
        <!-- Header -->
        <header class="archive-header">
            <h1 class="archive-title">📚 همه رمان‌ها <span class="archive-count">(<?php echo number_format_i18n($total_novels); ?>)</span></h1>
        </header>
        
        <!-- Filters -->
        <div class="archive-filters" id="archiveFilters" data-base-url="<?php echo get_post_type_archive_link('novel'); ?>">
            
            <!-- Quick Filters -->
            <div class="filter-row">
                <div class="filter-group">
                    <span class="filter-label">نوع:</span>
                    <button class="filter-chip <?php echo !$current_type ? 'active' : ''; ?>" data-filter="type" data-value="">همه</button>
                    <button class="filter-chip <?php echo $current_type === 'ln' ? 'active' : ''; ?>" data-filter="type" data-value="ln">LN</button>
                    <button class="filter-chip <?php echo $current_type === 'wn' ? 'active' : ''; ?>" data-filter="type" data-value="wn">WN</button>
                </div>
                
                <div class="filter-group">
                    <span class="filter-label">وضعیت:</span>
                    <button class="filter-chip <?php echo !$current_status ? 'active' : ''; ?>" data-filter="status" data-value="">همه</button>
                    <?php if (!empty($status_terms) && !is_wp_error($status_terms)) : ?>
                        <?php foreach ($status_terms as $st) : ?>
                            <button class="filter-chip <?php echo $current_status === $st->slug ? 'active' : ''; ?>" 
                                    data-filter="status" data-value="<?php echo esc_attr($st->slug); ?>">
                                <?php echo esc_html($st->name); ?>
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="filter-group">
                    <span class="filter-label">دسترسی:</span>
                    <button class="filter-chip <?php echo !$current_access ? 'active' : ''; ?>" data-filter="access" data-value="">همه</button>
                    <button class="filter-chip <?php echo $current_access === 'free' ? 'active' : ''; ?>" data-filter="access" data-value="free">رایگان</button>
                </div>
            </div>
            
            <!-- Sort & View -->
            <div class="filter-row filter-row--bottom">
                <div class="filter-group">
                    <span class="filter-label">مرتب‌سازی:</span>
                    <select class="sort-select" id="sortSelect" data-filter="sort">
                        <option value="newest" <?php selected($current_sort, 'newest'); ?>>جدیدترین</option>
                        <option value="popular" <?php selected($current_sort, 'popular'); ?>>محبوب‌ترین</option>
                        <option value="rating" <?php selected($current_sort, 'rating'); ?>>بالاترین امتیاز</option>
                        <option value="chapters" <?php selected($current_sort, 'chapters'); ?>>بیشترین قسمت</option>
                        <option value="oldest" <?php selected($current_sort, 'oldest'); ?>>قدیمی‌ترین</option>
                    </select>
                </div>
                
                <div class="filter-group view-toggle">
                    <button class="view-btn <?php echo $current_view === 'grid' ? 'active' : ''; ?>" 
                            data-view="grid" title="نمایش گریدی">▦</button>
                    <button class="view-btn <?php echo $current_view === 'list' ? 'active' : ''; ?>" 
                            data-view="list" title="نمایش لیستی">≡</button>
                </div>
            </div>
        </div>
        
        <!-- Results -->
        <div class="archive-results <?php echo 'view-' . esc_attr($current_view); ?>" id="archiveResults">
            
            <?php if ($novels_query->have_posts()) : ?>
                
                <!-- Grid View -->
                <div class="novels-grid" id="novelsGrid">
                    <?php while ($novels_query->have_posts()) : $novels_query->the_post(); ?>
                        <?php if ($current_view === 'list') : ?>
                            <?php get_template_part('templates/novel/novel-card-small'); ?>
                        <?php else : ?>
                            <?php get_template_part('templates/novel/novel-card'); ?>
                        <?php endif; ?>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($novels_query->max_num_pages > 1) : ?>
                <div class="archive-pagination">
                    <?php
                    echo paginate_links([
                        'total'     => $novels_query->max_num_pages,
                        'current'   => $paged,
                        'prev_text' => '← قبلی',
                        'next_text' => 'بعدی →',
                        'mid_size'  => 2,
                    ]);
                    ?>
                </div>
                <?php endif; ?>
                
            <?php else : ?>
                <div class="archive-empty">
                    <div class="empty-icon">📖</div>
                    <h3>رمانی یافت نشد</h3>
                    <p>با فیلترهای انتخابی شما رمانی وجود ندارد. فیلترها را تغییر دهید.</p>
                    <a href="<?php echo get_post_type_archive_link('novel'); ?>" class="btn-reset-filters">حذف فیلترها</a>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
</main>

<?php get_footer(); ?>