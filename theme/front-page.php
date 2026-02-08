<?php
/**
 * صفحه اصلی قالب فلیور نوول
 * شامل: اسلایدر، آخرین بروزرسانی‌ها، محبوب‌ترین‌ها، ژانرها، رنکینگ
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();
?>

<!-- ===== اسلایدر رمان‌های ویژه ===== -->
<?php
$featured_novels = new WP_Query( array(
    'post_type'      => 'novel',
    'posts_per_page' => 5,
    'meta_query'     => array(
        array(
            'key'   => '_fn_is_featured',
            'value' => '1',
        ),
    ),
    'orderby' => 'modified',
    'order'   => 'DESC',
) );

if ( $featured_novels->have_posts() ) :
?>
<section class="fn-section fn-hero-section">
    <div class="fn-container">
        <div class="fn-hero-slider" id="fnHeroSlider">
            <?php
            $slide_index = 0;
            while ( $featured_novels->have_posts() ) :
                $featured_novels->the_post();
                $novel_id    = get_the_ID();
                $synopsis    = get_post_meta( $novel_id, '_fn_synopsis', true );
                $cover_url   = get_the_post_thumbnail_url( $novel_id, 'novel-hero' );
                $genres      = wp_get_post_terms( $novel_id, 'genre', array( 'fields' => 'names' ) );
                $status_terms = wp_get_post_terms( $novel_id, 'novel_status' );
                $chapter_count = fn_get_chapter_count( $novel_id );
                $avg_rating  = floatval( get_post_meta( $novel_id, '_fn_avg_rating', true ) );
            ?>
                <div class="fn-hero-slide <?php echo $slide_index === 0 ? 'active' : ''; ?>">
                    <!-- پس‌زمینه بلور -->
                    <div class="fn-hero-slide__bg" style="background-image: url('<?php echo esc_url( $cover_url ); ?>')"></div>

                    <div class="fn-hero-slide__content">
                        <!-- کاور -->
                        <a href="<?php the_permalink(); ?>" class="fn-hero-slide__cover">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <img src="<?php echo esc_url( $cover_url ); ?>"
                                     alt="<?php echo esc_attr( get_the_title() ); ?>"
                                     loading="<?php echo $slide_index === 0 ? 'eager' : 'lazy'; ?>">
                            <?php endif; ?>
                        </a>

                        <!-- اطلاعات -->
                        <div class="fn-hero-slide__info">
                            <?php if ( ! empty( $status_terms ) && ! is_wp_error( $status_terms ) ) : ?>
                                <span class="fn-hero-slide__status fn-novel-card__badge fn-novel-card__badge--<?php echo esc_attr( $status_terms[0]->slug ); ?>">
                                    <?php echo esc_html( $status_terms[0]->name ); ?>
                                </span>
                            <?php endif; ?>

                            <h2 class="fn-hero-slide__title">
                                <a href="<?php the_permalink(); ?>" style="color: #fff;">
                                    <?php the_title(); ?>
                                </a>
                            </h2>

                            <?php if ( $synopsis ) : ?>
                                <p class="fn-hero-slide__synopsis">
                                    <?php echo esc_html( wp_trim_words( $synopsis, 30 ) ); ?>
                                </p>
                            <?php endif; ?>

                            <!-- تگ‌های ژانر -->
                            <?php if ( ! empty( $genres ) ) : ?>
                                <div class="fn-hero-slide__tags">
                                    <?php foreach ( array_slice( $genres, 0, 4 ) as $genre_name ) : ?>
                                        <span class="fn-hero-slide__tag"><?php echo esc_html( $genre_name ); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- آمار و دکمه -->
                            <div class="fn-hero-slide__bottom">
                                <div class="fn-hero-slide__stats">
                                    <?php if ( $avg_rating > 0 ) : ?>
                                        <span class="fn-hero-slide__stat">⭐ <?php echo number_format( $avg_rating, 1 ); ?></span>
                                    <?php endif; ?>
                                    <span class="fn-hero-slide__stat">📖 <?php echo fn_format_number( $chapter_count ); ?> فصل</span>
                                </div>
                                <a href="<?php the_permalink(); ?>" class="fn-btn fn-btn--primary">
                                    شروع خواندن
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
                $slide_index++;
            endwhile;
            wp_reset_postdata();
            ?>

            <!-- دکمه‌های ناوبری اسلایدر -->
            <button class="fn-slider-arrow fn-slider-prev" aria-label="اسلاید قبلی">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
            <button class="fn-slider-arrow fn-slider-next" aria-label="اسلاید بعدی">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
            </button>

            <!-- نقطه‌های اسلایدر -->
            <div class="fn-slider-dots">
                <?php for ( $i = 0; $i < $slide_index; $i++ ) : ?>
                    <button class="fn-slider-dot <?php echo $i === 0 ? 'active' : ''; ?>"
                            aria-label="اسلاید <?php echo $i + 1; ?>"></button>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>


<!-- ===== آخرین بروزرسانی‌ها ===== -->
<section class="fn-section fn-fade-in">
    <div class="fn-container">
        <div class="fn-section__header">
            <h2 class="fn-section__title">آخرین بروزرسانی‌ها</h2>
            <a href="<?php echo esc_url( get_post_type_archive_link( 'novel' ) ); ?>?orderby=modified" class="fn-section__more">
                مشاهده همه
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            </a>
        </div>

        <div class="fn-updates-list">
            <?php
            // دریافت رمان‌هایی که اخیراً فصل جدید گرفته‌اند
            $recent_chapters = new WP_Query( array(
                'post_type'      => 'chapter',
                'posts_per_page' => 15,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'post_status'    => 'publish',
            ) );

            $shown_novels = array(); // جلوگیری از تکرار رمان

            if ( $recent_chapters->have_posts() ) :
                while ( $recent_chapters->have_posts() ) :
                    $recent_chapters->the_post();
                    $chapter_id     = get_the_ID();
                    $novel_id       = get_post_meta( $chapter_id, '_fn_parent_novel', true );

                    // اگر رمان قبلاً نشان داده شده، رد شو
                    if ( ! $novel_id || in_array( $novel_id, $shown_novels ) ) continue;
                    if ( count( $shown_novels ) >= 10 ) break;

                    $shown_novels[] = $novel_id;
                    $novel_title    = get_the_title( $novel_id );
                    $cover_url      = get_the_post_thumbnail_url( $novel_id, 'novel-cover-sm' );
                    $chapter_number = get_post_meta( $chapter_id, '_fn_chapter_number', true );
                    $chapter_title  = get_the_title();
                    $genres         = wp_get_post_terms( $novel_id, 'genre', array( 'fields' => 'names' ) );
            ?>
                    <a href="<?php echo esc_url( get_permalink( $chapter_id ) ); ?>" class="fn-update-item">
                        <div class="fn-update-item__cover">
                            <?php if ( $cover_url ) : ?>
                                <img src="<?php echo esc_url( $cover_url ); ?>"
                                     alt="<?php echo esc_attr( $novel_title ); ?>"
                                     loading="lazy">
                            <?php else : ?>
                                <div style="width:100%;height:100%;background:var(--fn-accent-light);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">📖</div>
                            <?php endif; ?>
                        </div>

                        <div class="fn-update-item__info">
                            <div class="fn-update-item__title"><?php echo esc_html( $novel_title ); ?></div>
                            <div class="fn-update-item__chapter">
                                فصل <?php echo esc_html( $chapter_number ); ?>
                                <?php if ( $chapter_title !== $novel_title ) : ?>
                                    - <?php echo esc_html( wp_trim_words( $chapter_title, 6 ) ); ?>
                                <?php endif; ?>
                            </div>
                            <?php if ( ! empty( $genres ) ) : ?>
                                <div class="fn-update-item__genre">
                                    <?php echo esc_html( $genres[0] ); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="fn-update-item__time">
                            <?php echo fn_time_ago( get_the_time( 'U' ) ); ?>
                        </div>
                    </a>
            <?php
                endwhile;
                wp_reset_postdata();
            else :
            ?>
                <div class="fn-empty-state" style="padding: 40px; text-align: center; color: var(--fn-text-muted);">
                    <p>هنوز فصلی منتشر نشده است.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>


<!-- ===== رمان‌های محبوب (تب‌دار) ===== -->
<section class="fn-section fn-fade-in">
    <div class="fn-container">
        <div class="fn-section__header">
            <h2 class="fn-section__title">پرطرفدارترین‌ها</h2>
        </div>

        <!-- تب‌ها -->
        <div class="fn-tabs">
            <button class="fn-tab active" data-tab="popular-weekly">هفتگی</button>
            <button class="fn-tab" data-tab="popular-monthly">ماهانه</button>
            <button class="fn-tab" data-tab="popular-all">کل</button>
        </div>

        <!-- محتوای تب هفتگی -->
        <div class="fn-tab-content active" id="popular-weekly">
            <div class="fn-novels-grid">
                <?php
                $popular_weekly = new WP_Query( array(
                    'post_type'      => 'novel',
                    'posts_per_page' => 12,
                    'meta_key'       => '_fn_weekly_views',
                    'orderby'        => 'meta_value_num',
                    'order'          => 'DESC',
                    'post_status'    => 'publish',
                ) );

                // اگر هنوز بازدید هفتگی نداریم، از بازدید کل استفاده کن
                if ( ! $popular_weekly->have_posts() ) {
                    $popular_weekly = new WP_Query( array(
                        'post_type'      => 'novel',
                        'posts_per_page' => 12,
                        'meta_key'       => '_fn_total_views',
                        'orderby'        => 'meta_value_num',
                        'order'          => 'DESC',
                        'post_status'    => 'publish',
                    ) );
                }

                // فالبک نهایی
                if ( ! $popular_weekly->have_posts() ) {
                    $popular_weekly = new WP_Query( array(
                        'post_type'      => 'novel',
                        'posts_per_page' => 12,
                        'orderby'        => 'comment_count',
                        'order'          => 'DESC',
                        'post_status'    => 'publish',
                    ) );
                }

                if ( $popular_weekly->have_posts() ) :
                    while ( $popular_weekly->have_posts() ) :
                        $popular_weekly->the_post();
                        get_template_part( 'template-parts/novel-card' );
                    endwhile;
                    wp_reset_postdata();
                endif;
                ?>
            </div>
        </div>

        <!-- محتوای تب ماهانه -->
        <div class="fn-tab-content" id="popular-monthly">
            <div class="fn-novels-grid">
                <?php
                $popular_monthly = new WP_Query( array(
                    'post_type'      => 'novel',
                    'posts_per_page' => 12,
                    'meta_key'       => '_fn_monthly_views',
                    'orderby'        => 'meta_value_num',
                    'order'          => 'DESC',
                    'post_status'    => 'publish',
                ) );

                if ( ! $popular_monthly->have_posts() ) {
                    $popular_monthly = new WP_Query( array(
                        'post_type'      => 'novel',
                        'posts_per_page' => 12,
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                        'post_status'    => 'publish',
                    ) );
                }

                if ( $popular_monthly->have_posts() ) :
                    while ( $popular_monthly->have_posts() ) :
                        $popular_monthly->the_post();
                        get_template_part( 'template-parts/novel-card' );
                    endwhile;
                    wp_reset_postdata();
                endif;
                ?>
            </div>
        </div>

        <!-- محتوای تب کل -->
        <div class="fn-tab-content" id="popular-all">
            <div class="fn-novels-grid">
                <?php
                $popular_all = new WP_Query( array(
                    'post_type'      => 'novel',
                    'posts_per_page' => 12,
                    'meta_key'       => '_fn_total_views',
                    'orderby'        => 'meta_value_num',
                    'order'          => 'DESC',
                    'post_status'    => 'publish',
                ) );

                if ( ! $popular_all->have_posts() ) {
                    $popular_all = new WP_Query( array(
                        'post_type'      => 'novel',
                        'posts_per_page' => 12,
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                        'post_status'    => 'publish',
                    ) );
                }

                if ( $popular_all->have_posts() ) :
                    while ( $popular_all->have_posts() ) :
                        $popular_all->the_post();
                        get_template_part( 'template-parts/novel-card' );
                    endwhile;
                    wp_reset_postdata();
                endif;
                ?>
            </div>
        </div>
    </div>
</section>


<!-- ===== لایه دوم: تازه‌ترین‌ها + رنکینگ ===== -->
<section class="fn-section fn-fade-in">
    <div class="fn-container">
        <div class="fn-home-two-col">

            <!-- ستون چپ: تازه‌ترین رمان‌ها -->
            <div class="fn-home-two-col__main">
                <div class="fn-section__header">
                    <h2 class="fn-section__title">تازه‌ترین رمان‌ها</h2>
                    <a href="<?php echo esc_url( get_post_type_archive_link( 'novel' ) ); ?>" class="fn-section__more">
                        مشاهده همه
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                    </a>
                </div>

                <div class="fn-novels-grid">
                    <?php
                    $newest_novels = new WP_Query( array(
                        'post_type'      => 'novel',
                        'posts_per_page' => 12,
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                        'post_status'    => 'publish',
                    ) );

                    if ( $newest_novels->have_posts() ) :
                        while ( $newest_novels->have_posts() ) :
                            $newest_novels->the_post();
                            get_template_part( 'template-parts/novel-card' );
                        endwhile;
                        wp_reset_postdata();
                    endif;
                    ?>
                </div>
            </div>

            <!-- ستون راست: رنکینگ -->
            <div class="fn-home-two-col__sidebar">
                <div class="fn-section__header">
                    <h2 class="fn-section__title">🏆 رنکینگ قدرت</h2>
                </div>

                <div class="fn-ranking">
                    <!-- تب‌های رنکینگ -->
                    <div class="fn-ranking__tabs" id="fnRankingTabs">
                        <button class="fn-ranking__tab active" data-rank-tab="rank-views">بازدید</button>
                        <button class="fn-ranking__tab" data-rank-tab="rank-rating">امتیاز</button>
                        <button class="fn-ranking__tab" data-rank-tab="rank-bookmarks">بوکمارک</button>
                    </div>

                    <!-- رنکینگ بازدید -->
                    <div class="fn-rank-content active" id="rank-views">
                        <?php
                        $ranked_by_views = new WP_Query( array(
                            'post_type'      => 'novel',
                            'posts_per_page' => 10,
                            'meta_key'       => '_fn_total_views',
                            'orderby'        => 'meta_value_num',
                            'order'          => 'DESC',
                            'post_status'    => 'publish',
                        ) );

                        // فالبک
                        if ( ! $ranked_by_views->have_posts() ) {
                            $ranked_by_views = new WP_Query( array(
                                'post_type'      => 'novel',
                                'posts_per_page' => 10,
                                'orderby'        => 'date',
                                'order'          => 'DESC',
                                'post_status'    => 'publish',
                            ) );
                        }

                        $rank_num = 1;
                        if ( $ranked_by_views->have_posts() ) :
                            while ( $ranked_by_views->have_posts() ) :
                                $ranked_by_views->the_post();
                                $novel_id = get_the_ID();
                                $views    = absint( get_post_meta( $novel_id, '_fn_total_views', true ) );
                                $cover    = get_the_post_thumbnail_url( $novel_id, 'novel-cover-sm' );
                                $genres   = wp_get_post_terms( $novel_id, 'genre', array( 'fields' => 'names' ) );
                        ?>
                            <a href="<?php the_permalink(); ?>" class="fn-ranking__item">
                                <span class="fn-ranking__number"><?php echo $rank_num; ?></span>
                                <div class="fn-ranking__cover">
                                    <?php if ( $cover ) : ?>
                                        <img src="<?php echo esc_url( $cover ); ?>"
                                             alt="<?php echo esc_attr( get_the_title() ); ?>"
                                             loading="lazy">
                                    <?php endif; ?>
                                </div>
                                <div class="fn-ranking__info">
                                    <div class="fn-ranking__title"><?php echo esc_html( wp_trim_words( get_the_title(), 5 ) ); ?></div>
                                    <div class="fn-ranking__meta">
                                        <?php if ( ! empty( $genres ) ) : ?>
                                            <span><?php echo esc_html( $genres[0] ); ?></span>
                                        <?php endif; ?>
                                        <span>👁 <?php echo fn_format_number( $views ); ?></span>
                                    </div>
                                </div>
                            </a>
                        <?php
                                $rank_num++;
                            endwhile;
                            wp_reset_postdata();
                        endif;
                        ?>
                    </div>

                    <!-- رنکینگ امتیاز -->
                    <div class="fn-rank-content" id="rank-rating">
                        <?php
                        $ranked_by_rating = new WP_Query( array(
                            'post_type'      => 'novel',
                            'posts_per_page' => 10,
                            'meta_key'       => '_fn_avg_rating',
                            'orderby'        => 'meta_value_num',
                            'order'          => 'DESC',
                            'post_status'    => 'publish',
                        ) );

                        if ( ! $ranked_by_rating->have_posts() ) {
                            $ranked_by_rating = new WP_Query( array(
                                'post_type'      => 'novel',
                                'posts_per_page' => 10,
                                'orderby'        => 'date',
                                'order'          => 'DESC',
                            ) );
                        }

                        $rank_num = 1;
                        if ( $ranked_by_rating->have_posts() ) :
                            while ( $ranked_by_rating->have_posts() ) :
                                $ranked_by_rating->the_post();
                                $novel_id   = get_the_ID();
                                $avg_rating = floatval( get_post_meta( $novel_id, '_fn_avg_rating', true ) );
                                $cover      = get_the_post_thumbnail_url( $novel_id, 'novel-cover-sm' );
                        ?>
                            <a href="<?php the_permalink(); ?>" class="fn-ranking__item">
                                <span class="fn-ranking__number"><?php echo $rank_num; ?></span>
                                <div class="fn-ranking__cover">
                                    <?php if ( $cover ) : ?>
                                        <img src="<?php echo esc_url( $cover ); ?>" alt="" loading="lazy">
                                    <?php endif; ?>
                                </div>
                                <div class="fn-ranking__info">
                                    <div class="fn-ranking__title"><?php echo esc_html( wp_trim_words( get_the_title(), 5 ) ); ?></div>
                                    <div class="fn-ranking__meta">
                                        <span>⭐ <?php echo $avg_rating > 0 ? number_format( $avg_rating, 1 ) : '—'; ?></span>
                                    </div>
                                </div>
                            </a>
                        <?php
                                $rank_num++;
                            endwhile;
                            wp_reset_postdata();
                        endif;
                        ?>
                    </div>

                    <!-- رنکینگ بوکمارک -->
                    <div class="fn-rank-content" id="rank-bookmarks">
                        <?php
                        $ranked_by_bookmarks = new WP_Query( array(
                            'post_type'      => 'novel',
                            'posts_per_page' => 10,
                            'meta_key'       => '_fn_bookmark_count',
                            'orderby'        => 'meta_value_num',
                            'order'          => 'DESC',
                            'post_status'    => 'publish',
                        ) );

                        if ( ! $ranked_by_bookmarks->have_posts() ) {
                            $ranked_by_bookmarks = new WP_Query( array(
                                'post_type'      => 'novel',
                                'posts_per_page' => 10,
                                'orderby'        => 'date',
                                'order'          => 'DESC',
                            ) );
                        }

                        $rank_num = 1;
                        if ( $ranked_by_bookmarks->have_posts() ) :
                            while ( $ranked_by_bookmarks->have_posts() ) :
                                $ranked_by_bookmarks->the_post();
                                $novel_id       = get_the_ID();
                                $bookmark_count = absint( get_post_meta( $novel_id, '_fn_bookmark_count', true ) );
                                $cover          = get_the_post_thumbnail_url( $novel_id, 'novel-cover-sm' );
                        ?>
                            <a href="<?php the_permalink(); ?>" class="fn-ranking__item">
                                <span class="fn-ranking__number"><?php echo $rank_num; ?></span>
                                <div class="fn-ranking__cover">
                                    <?php if ( $cover ) : ?>
                                        <img src="<?php echo esc_url( $cover ); ?>" alt="" loading="lazy">
                                    <?php endif; ?>
                                </div>
                                <div class="fn-ranking__info">
                                    <div class="fn-ranking__title"><?php echo esc_html( wp_trim_words( get_the_title(), 5 ) ); ?></div>
                                    <div class="fn-ranking__meta">
                                        <span>🔖 <?php echo fn_format_number( $bookmark_count ); ?></span>
                                    </div>
                                </div>
                            </a>
                        <?php
                                $rank_num++;
                            endwhile;
                            wp_reset_postdata();
                        endif;
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- ===== رمان‌های تمام‌شده — اصلاح شده ===== -->
<?php
$completed_novels = new WP_Query( array(
    'post_type'      => 'novel',
    'posts_per_page' => 6,
    'post_status'    => 'publish',
    'tax_query'      => array(
        array(
            'taxonomy' => 'novel_status',
            'field'    => 'slug',
            'terms'    => 'completed',
        ),
    ),
    'orderby' => 'modified',
    'order'   => 'DESC',
) );

// فقط اگر واقعاً رمان تمام‌شده وجود دارد نمایش بده
if ( $completed_novels->have_posts() ) :
?>
<section class="fn-section fn-fade-in">
    <div class="fn-container">
        <div class="fn-section__header">
            <h2 class="fn-section__title">✅ رمان‌های تمام‌شده</h2>
            <a href="<?php echo esc_url( home_url( '/status/completed/' ) ); ?>" class="fn-section__more">
                مشاهده همه
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            </a>
        </div>

        <div class="fn-novels-grid fn-novels-grid--wide">
            <?php
            while ( $completed_novels->have_posts() ) :
                $completed_novels->the_post();
                get_template_part( 'template-parts/novel-card', 'wide' );
            endwhile;
            wp_reset_postdata();
            ?>
        </div>
    </div>
</section>
<?php endif; ?>


<!-- ===== مرور بر اساس ژانر ===== -->
<section class="fn-section fn-fade-in">
    <div class="fn-container">
        <div class="fn-section__header">
            <h2 class="fn-section__title">مرور بر اساس ژانر</h2>
        </div>

        <div class="fn-genres-grid">
            <?php
            $genres = get_terms( array(
                'taxonomy'   => 'genre',
                'hide_empty' => false,
                'orderby'    => 'count',
                'order'      => 'DESC',
            ) );

            if ( ! empty( $genres ) && ! is_wp_error( $genres ) ) :
                foreach ( $genres as $genre ) :
                    $icon = get_term_meta( $genre->term_id, '_fn_genre_icon', true );
                    if ( ! $icon ) $icon = '📖';
            ?>
                <a href="<?php echo esc_url( get_term_link( $genre ) ); ?>" class="fn-genre-chip">
                    <span class="fn-genre-chip__icon"><?php echo esc_html( $icon ); ?></span>
                    <span class="fn-genre-chip__name"><?php echo esc_html( $genre->name ); ?></span>
                    <span class="fn-genre-chip__count"><?php echo absint( $genre->count ); ?></span>
                </a>
            <?php
                endforeach;
            endif;
            ?>
        </div>
    </div>
</section>

<?php get_footer(); ?>