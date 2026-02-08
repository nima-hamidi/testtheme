<?php
/**
 * آرشیو رمان‌ها - کتابخانه
 * شامل: فیلتر، مرتب‌سازی، نمایش گرید/لیست، صفحه‌بندی
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

// پارامترهای فیلتر از URL
$current_genre   = sanitize_text_field( $_GET['genre'] ?? '' );
$current_status  = sanitize_text_field( $_GET['status'] ?? '' );
$current_lang    = sanitize_text_field( $_GET['lang'] ?? '' );
$current_orderby = sanitize_text_field( $_GET['orderby'] ?? 'date' );
$current_order   = sanitize_text_field( $_GET['order'] ?? 'DESC' );
$current_view    = sanitize_text_field( $_GET['view'] ?? 'grid' );
$current_search  = sanitize_text_field( $_GET['sq'] ?? '' );
$paged           = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;

// ساخت کوئری
$args = array(
    'post_type'      => 'novel',
    'posts_per_page' => 24,
    'paged'          => $paged,
    'post_status'    => 'publish',
);

// مرتب‌سازی
switch ( $current_orderby ) {
    case 'popular':
        $args['meta_key'] = '_fn_total_views';
        $args['orderby']  = 'meta_value_num';
        $args['order']    = 'DESC';
        break;
    case 'rating':
        $args['meta_key'] = '_fn_avg_rating';
        $args['orderby']  = 'meta_value_num';
        $args['order']    = 'DESC';
        break;
    case 'chapters':
        $args['meta_key'] = '_fn_chapter_count';
        $args['orderby']  = 'meta_value_num';
        $args['order']    = 'DESC';
        break;
    case 'title':
        $args['orderby'] = 'title';
        $args['order']   = 'ASC';
        break;
    case 'updated':
        $args['orderby'] = 'modified';
        $args['order']   = 'DESC';
        break;
    default:
        $args['orderby'] = 'date';
        $args['order']   = $current_order;
        break;
}

// فیلتر تکسونومی
$tax_query = array();

if ( $current_genre ) {
    $tax_query[] = array(
        'taxonomy' => 'genre',
        'field'    => 'slug',
        'terms'    => $current_genre,
    );
}

if ( $current_status ) {
    $tax_query[] = array(
        'taxonomy' => 'novel_status',
        'field'    => 'slug',
        'terms'    => $current_status,
    );
}

if ( ! empty( $tax_query ) ) {
    $tax_query['relation'] = 'AND';
    $args['tax_query'] = $tax_query;
}

// فیلتر زبان
if ( $current_lang ) {
    $args['meta_query'][] = array(
        'key'   => '_fn_original_language',
        'value' => $current_lang,
    );
}

// جستجو در آرشیو
if ( $current_search ) {
    $args['s'] = $current_search;
}

$novels_query = new WP_Query( $args );

// دریافت ژانرها و وضعیت‌ها برای فیلتر
$all_genres   = get_terms( array( 'taxonomy' => 'genre', 'hide_empty' => true ) );
$all_statuses = get_terms( array( 'taxonomy' => 'novel_status', 'hide_empty' => true ) );

$languages = array(
    'chinese'  => 'چینی',
    'korean'   => 'کره‌ای',
    'japanese' => 'ژاپنی',
    'english'  => 'انگلیسی',
    'persian'  => 'فارسی',
);
?>

<div class="fn-container">
    <?php fn_breadcrumb(); ?>

    <!-- هدر صفحه -->
    <div class="fn-archive-header">
        <div class="fn-archive-header__right">
            <h1 class="fn-section__title">
                <?php
                if ( is_tax( 'genre' ) ) {
                    echo 'ژانر: ' . esc_html( single_term_title( '', false ) );
                } elseif ( is_tax( 'novel_status' ) ) {
                    echo esc_html( single_term_title( '', false ) );
                } else {
                    echo '📚 کتابخانه رمان‌ها';
                }
                ?>
            </h1>
            <p class="fn-archive-header__count">
                <?php echo fn_persian_number( $novels_query->found_posts ); ?> رمان یافت شد
            </p>
        </div>

        <!-- تاگل نمایش گرید/لیست -->
        <div class="fn-archive-header__left">
            <div class="fn-view-toggle">
                <button class="fn-view-toggle__btn <?php echo $current_view === 'grid' ? 'active' : ''; ?>"
                        data-view="grid" title="نمایش شبکه‌ای">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                </button>
                <button class="fn-view-toggle__btn <?php echo $current_view === 'list' ? 'active' : ''; ?>"
                        data-view="list" title="نمایش لیستی">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- ===== فیلترها ===== -->
    <form class="fn-filters" id="fnFilters" method="get"
          action="<?php echo esc_url( get_post_type_archive_link( 'novel' ) ); ?>">

        <!-- جستجو -->
        <div class="fn-filter-group fn-filter-group--search">
            <input type="text" name="sq" class="fn-filter-search"
                   value="<?php echo esc_attr( $current_search ); ?>"
                   placeholder="🔍 جستجو در رمان‌ها...">
        </div>

        <!-- ژانر -->
        <div class="fn-filter-group">
            <select name="genre" class="fn-filter-select" onchange="this.form.submit()">
                <option value="">همه ژانرها</option>
                <?php if ( ! empty( $all_genres ) && ! is_wp_error( $all_genres ) ) :
                    foreach ( $all_genres as $genre ) :
                        $icon = get_term_meta( $genre->term_id, '_fn_genre_icon', true );
                ?>
                    <option value="<?php echo esc_attr( $genre->slug ); ?>"
                            <?php selected( $current_genre, $genre->slug ); ?>>
                        <?php echo esc_html( ( $icon ? $icon . ' ' : '' ) . $genre->name ); ?>
                        (<?php echo $genre->count; ?>)
                    </option>
                <?php endforeach; endif; ?>
            </select>
        </div>

        <!-- وضعیت -->
        <div class="fn-filter-group">
            <select name="status" class="fn-filter-select" onchange="this.form.submit()">
                <option value="">همه وضعیت‌ها</option>
                <?php if ( ! empty( $all_statuses ) && ! is_wp_error( $all_statuses ) ) :
                    foreach ( $all_statuses as $status ) :
                ?>
                    <option value="<?php echo esc_attr( $status->slug ); ?>"
                            <?php selected( $current_status, $status->slug ); ?>>
                        <?php echo esc_html( $status->name ); ?>
                        (<?php echo $status->count; ?>)
                    </option>
                <?php endforeach; endif; ?>
            </select>
        </div>

        <!-- زبان -->
        <div class="fn-filter-group">
            <select name="lang" class="fn-filter-select" onchange="this.form.submit()">
                <option value="">همه زبان‌ها</option>
                <?php foreach ( $languages as $lang_key => $lang_name ) : ?>
                    <option value="<?php echo esc_attr( $lang_key ); ?>"
                            <?php selected( $current_lang, $lang_key ); ?>>
                        <?php echo esc_html( $lang_name ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- مرتب‌سازی -->
        <div class="fn-filter-group">
            <select name="orderby" class="fn-filter-select" onchange="this.form.submit()">
                <option value="date" <?php selected( $current_orderby, 'date' ); ?>>جدیدترین</option>
                <option value="updated" <?php selected( $current_orderby, 'updated' ); ?>>آخرین بروزرسانی</option>
                <option value="popular" <?php selected( $current_orderby, 'popular' ); ?>>محبوب‌ترین</option>
                <option value="rating" <?php selected( $current_orderby, 'rating' ); ?>>بالاترین امتیاز</option>
                <option value="chapters" <?php selected( $current_orderby, 'chapters' ); ?>>بیشترین فصل</option>
                <option value="title" <?php selected( $current_orderby, 'title' ); ?>>الفبایی</option>
            </select>
        </div>

        <!-- حفظ نمایش -->
        <input type="hidden" name="view" value="<?php echo esc_attr( $current_view ); ?>" id="fnViewInput">

        <!-- دکمه پاک‌سازی -->
        <?php if ( $current_genre || $current_status || $current_lang || $current_search || $current_orderby !== 'date' ) : ?>
            <a href="<?php echo esc_url( get_post_type_archive_link( 'novel' ) ); ?>" class="fn-filter-clear">
                ✕ پاک‌سازی فیلترها
            </a>
        <?php endif; ?>
    </form>

    <!-- فیلترهای فعال -->
    <?php if ( $current_genre || $current_status || $current_lang ) : ?>
        <div class="fn-active-filters">
            <?php if ( $current_genre ) :
                $genre_obj = get_term_by( 'slug', $current_genre, 'genre' );
            ?>
                <span class="fn-active-filter">
                    ژانر: <?php echo esc_html( $genre_obj ? $genre_obj->name : $current_genre ); ?>
                    <a href="<?php echo esc_url( remove_query_arg( 'genre' ) ); ?>" class="fn-active-filter__remove">✕</a>
                </span>
            <?php endif; ?>

            <?php if ( $current_status ) :
                $status_obj = get_term_by( 'slug', $current_status, 'novel_status' );
            ?>
                <span class="fn-active-filter">
                    وضعیت: <?php echo esc_html( $status_obj ? $status_obj->name : $current_status ); ?>
                    <a href="<?php echo esc_url( remove_query_arg( 'status' ) ); ?>" class="fn-active-filter__remove">✕</a>
                </span>
            <?php endif; ?>

            <?php if ( $current_lang && isset( $languages[ $current_lang ] ) ) : ?>
                <span class="fn-active-filter">
                    زبان: <?php echo esc_html( $languages[ $current_lang ] ); ?>
                    <a href="<?php echo esc_url( remove_query_arg( 'lang' ) ); ?>" class="fn-active-filter__remove">✕</a>
                </span>
            <?php endif; ?>
        </div>
    <?php endif; ?>


    <!-- ===== نتایج ===== -->
    <?php if ( $novels_query->have_posts() ) : ?>

        <!-- نمایش گرید -->
        <div class="fn-novels-grid <?php echo $current_view === 'list' ? 'fn-hidden' : ''; ?>" id="fnGridView">
            <?php
            while ( $novels_query->have_posts() ) :
                $novels_query->the_post();
                get_template_part( 'template-parts/novel-card' );
            endwhile;
            ?>
        </div>

        <!-- نمایش لیست -->
        <div class="fn-novels-list <?php echo $current_view !== 'list' ? 'fn-hidden' : ''; ?>" id="fnListView">
            <?php
            $novels_query->rewind_posts();
            while ( $novels_query->have_posts() ) :
                $novels_query->the_post();
                get_template_part( 'template-parts/novel-card', 'wide' );
            endwhile;
            ?>
        </div>

        <!-- صفحه‌بندی -->
        <nav class="fn-pagination">
            <?php
            echo paginate_links( array(
                'total'     => $novels_query->max_num_pages,
                'current'   => $paged,
                'prev_text' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg> قبلی',
                'next_text' => 'بعدی <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>',
                'type'      => 'list',
                'add_args'  => array_filter( array(
                    'genre'   => $current_genre,
                    'status'  => $current_status,
                    'lang'    => $current_lang,
                    'orderby' => $current_orderby !== 'date' ? $current_orderby : '',
                    'view'    => $current_view !== 'grid' ? $current_view : '',
                    'sq'      => $current_search,
                ) ),
            ) );
            ?>
        </nav>

    <?php else : ?>
        <div class="fn-empty-state">
            <div class="fn-empty-state__icon">📭</div>
            <h3>رمانی یافت نشد!</h3>
            <p>با فیلترهای انتخابی شما رمانی پیدا نشد. فیلترها را تغییر دهید.</p>
            <a href="<?php echo esc_url( get_post_type_archive_link( 'novel' ) ); ?>" class="fn-btn fn-btn--primary">
                مشاهده همه رمان‌ها
            </a>
        </div>
    <?php endif; ?>

    <?php wp_reset_postdata(); ?>
</div>

<!-- اسکریپت تاگل نمایش -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const viewBtns = document.querySelectorAll('.fn-view-toggle__btn');
    const gridView = document.getElementById('fnGridView');
    const listView = document.getElementById('fnListView');
    const viewInput = document.getElementById('fnViewInput');

    viewBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const view = this.dataset.view;

            viewBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            if (view === 'grid') {
                gridView?.classList.remove('fn-hidden');
                listView?.classList.add('fn-hidden');
            } else {
                gridView?.classList.add('fn-hidden');
                listView?.classList.remove('fn-hidden');
            }

            if (viewInput) viewInput.value = view;
            FN.storage.set('archive_view', view);
        });
    });

    // بازیابی نمایش ذخیره‌شده
    const savedView = FN.storage.get('archive_view', 'grid');
    const savedBtn = document.querySelector(`.fn-view-toggle__btn[data-view="${savedView}"]`);
    if (savedBtn && !new URLSearchParams(window.location.search).has('view')) {
        savedBtn.click();
    }
});
</script>

<?php get_footer(); ?>