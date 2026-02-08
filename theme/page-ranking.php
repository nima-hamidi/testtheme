<?php
/**
 * Template Name: رتبه‌بندی
 * صفحه رتبه‌بندی رمان‌ها
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$current_tab    = sanitize_text_field( $_GET['type'] ?? 'views' );
$current_period = sanitize_text_field( $_GET['period'] ?? 'all' );

// ساخت کوئری بر اساس تب
$meta_key = '_fn_total_views';
switch ( $current_tab ) {
    case 'rating':
        $meta_key = '_fn_avg_rating';
        break;
    case 'bookmarks':
        $meta_key = '_fn_bookmark_count';
        break;
    case 'chapters':
        $meta_key = '_fn_chapter_count';
        break;
    case 'views':
    default:
        if ( $current_period === 'weekly' ) {
            $meta_key = '_fn_weekly_views';
        } elseif ( $current_period === 'monthly' ) {
            $meta_key = '_fn_monthly_views';
        } else {
            $meta_key = '_fn_total_views';
        }
        break;
}

$paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;

$ranked_query = new WP_Query( array(
    'post_type'      => 'novel',
    'posts_per_page' => 50,
    'paged'          => $paged,
    'meta_key'       => $meta_key,
    'orderby'        => 'meta_value_num',
    'order'          => 'DESC',
    'post_status'    => 'publish',
) );

// فالبک اگر متا نداشت
if ( ! $ranked_query->have_posts() ) {
    $ranked_query = new WP_Query( array(
        'post_type'      => 'novel',
        'posts_per_page' => 50,
        'paged'          => $paged,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'post_status'    => 'publish',
    ) );
}
?>

<div class="fn-container">
    <?php fn_breadcrumb(); ?>

    <div class="fn-ranking-page">
        <!-- هدر -->
        <div class="fn-ranking-page__header">
            <h1 class="fn-section__title">🏆 رتبه‌بندی رمان‌ها</h1>
            <p class="fn-ranking-page__desc">بهترین رمان‌ها بر اساس معیارهای مختلف</p>
        </div>

        <!-- تب‌های اصلی -->
        <div class="fn-ranking-page__tabs">
            <a href="?type=views" class="fn-ranking-page__tab <?php echo $current_tab === 'views' ? 'active' : ''; ?>">
                👁 بازدید
            </a>
            <a href="?type=rating" class="fn-ranking-page__tab <?php echo $current_tab === 'rating' ? 'active' : ''; ?>">
                ⭐ امتیاز
            </a>
            <a href="?type=bookmarks" class="fn-ranking-page__tab <?php echo $current_tab === 'bookmarks' ? 'active' : ''; ?>">
                🔖 بوکمارک
            </a>
            <a href="?type=chapters" class="fn-ranking-page__tab <?php echo $current_tab === 'chapters' ? 'active' : ''; ?>">
                📖 تعداد فصل
            </a>
        </div>

        <!-- فیلتر دوره زمانی (فقط برای بازدید) -->
        <?php if ( $current_tab === 'views' ) : ?>
            <div class="fn-ranking-page__periods">
                <a href="?type=views&period=weekly" class="fn-ranking-period <?php echo $current_period === 'weekly' ? 'active' : ''; ?>">هفتگی</a>
                <a href="?type=views&period=monthly" class="fn-ranking-period <?php echo $current_period === 'monthly' ? 'active' : ''; ?>">ماهانه</a>
                <a href="?type=views&period=all" class="fn-ranking-period <?php echo $current_period === 'all' ? 'active' : ''; ?>">کل</a>
            </div>
        <?php endif; ?>

        <!-- لیست رتبه‌بندی -->
        <?php if ( $ranked_query->have_posts() ) : ?>
            <div class="fn-ranking-page__list">
                <?php
                $rank = ( $paged - 1 ) * 50 + 1;
                while ( $ranked_query->have_posts() ) :
                    $ranked_query->the_post();
                    $novel_id      = get_the_ID();
                    $cover         = get_the_post_thumbnail_url( $novel_id, 'novel-cover-sm' );
                    $genres        = wp_get_post_terms( $novel_id, 'genre', array( 'fields' => 'names' ) );
                    $status_terms  = wp_get_post_terms( $novel_id, 'novel_status' );
                    $avg_rating    = floatval( get_post_meta( $novel_id, '_fn_avg_rating', true ) );
                    $total_views   = absint( get_post_meta( $novel_id, '_fn_total_views', true ) );
                    $chapter_count = fn_get_chapter_count( $novel_id );
                    $bookmark_cnt  = absint( get_post_meta( $novel_id, '_fn_bookmark_count', true ) );

                    // مقدار فعلی بر اساس تب
                    $current_value = '';
                    switch ( $current_tab ) {
                        case 'rating':
                            $current_value = $avg_rating > 0 ? '⭐ ' . number_format( $avg_rating, 1 ) : '—';
                            break;
                        case 'bookmarks':
                            $current_value = '🔖 ' . fn_format_number( $bookmark_cnt );
                            break;
                        case 'chapters':
                            $current_value = '📖 ' . fn_format_number( $chapter_count );
                            break;
                        default:
                            $current_value = '👁 ' . fn_format_number( $total_views );
                            break;
                    }
                ?>
                    <a href="<?php the_permalink(); ?>" class="fn-ranking-page__item">
                        <!-- رتبه -->
                        <div class="fn-ranking-page__rank <?php echo $rank <= 3 ? 'fn-ranking-page__rank--top' . $rank : ''; ?>">
                            <?php if ( $rank === 1 ) : ?>
                                <span class="fn-rank-medal">🥇</span>
                            <?php elseif ( $rank === 2 ) : ?>
                                <span class="fn-rank-medal">🥈</span>
                            <?php elseif ( $rank === 3 ) : ?>
                                <span class="fn-rank-medal">🥉</span>
                            <?php else : ?>
                                <span class="fn-rank-number"><?php echo $rank; ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- کاور -->
                        <div class="fn-ranking-page__cover">
                            <?php if ( $cover ) : ?>
                                <img src="<?php echo esc_url( $cover ); ?>" alt="" loading="lazy">
                            <?php endif; ?>
                        </div>

                        <!-- اطلاعات -->
                        <div class="fn-ranking-page__info">
                            <h3 class="fn-ranking-page__title"><?php the_title(); ?></h3>
                            <div class="fn-ranking-page__meta">
                                <?php if ( ! empty( $genres ) ) : ?>
                                    <span class="fn-novel-card__genre"><?php echo esc_html( $genres[0] ); ?></span>
                                <?php endif; ?>
                                <?php if ( ! empty( $status_terms ) ) : ?>
                                    <span class="fn-novel-card__badge fn-novel-card__badge--<?php echo esc_attr( $status_terms[0]->slug ); ?>" style="position:static;">
                                        <?php echo esc_html( $status_terms[0]->name ); ?>
                                    </span>
                                <?php endif; ?>
                                <span>📖 <?php echo fn_format_number( $chapter_count ); ?> فصل</span>
                            </div>
                        </div>

                        <!-- مقدار اصلی -->
                        <div class="fn-ranking-page__value">
                            <?php echo $current_value; ?>
                        </div>
                    </a>
                <?php
                    $rank++;
                endwhile;
                wp_reset_postdata();
                ?>
            </div>

            <!-- صفحه‌بندی -->
            <?php if ( $ranked_query->max_num_pages > 1 ) : ?>
                <nav class="fn-pagination">
                    <?php
                    echo paginate_links( array(
                        'total'   => $ranked_query->max_num_pages,
                        'current' => $paged,
                        'prev_text' => '← قبلی',
                        'next_text' => 'بعدی →',
                        'type'    => 'list',
                        'add_args' => array_filter( array(
                            'type'   => $current_tab,
                            'period' => $current_period !== 'all' ? $current_period : '',
                        ) ),
                    ) );
                    ?>
                </nav>
            <?php endif; ?>

        <?php else : ?>
            <div class="fn-empty-state">
                <div class="fn-empty-state__icon">📊</div>
                <h3>هنوز داده‌ای برای رتبه‌بندی وجود ندارد</h3>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
