<?php
/**
 * صفحه تک رمان - Single Novel
 * شامل: کاور، اطلاعات، خلاصه، امتیاز، بوکمارک، لیست فصل‌ها، مشابه‌ها
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

// دریافت اطلاعات رمان
$novel_id        = get_the_ID();
$synopsis        = get_post_meta( $novel_id, '_fn_synopsis', true );
$original_author = get_post_meta( $novel_id, '_fn_original_author', true );
$translator      = get_post_meta( $novel_id, '_fn_translator', true );
$original_lang   = get_post_meta( $novel_id, '_fn_original_language', true );
$publish_year    = get_post_meta( $novel_id, '_fn_publish_year', true );
$alt_names       = get_post_meta( $novel_id, '_fn_alternative_names', true );
$cover_url       = get_the_post_thumbnail_url( $novel_id, 'novel-cover' );
$cover_full      = get_the_post_thumbnail_url( $novel_id, 'full' );

// تکسونومی‌ها
$genres       = wp_get_post_terms( $novel_id, 'genre' );
$tags         = wp_get_post_terms( $novel_id, 'novel_tag' );
$status_terms = wp_get_post_terms( $novel_id, 'novel_status' );
$status_slug  = ! empty( $status_terms ) ? $status_terms[0]->slug : 'ongoing';
$status_name  = ! empty( $status_terms ) ? $status_terms[0]->name : 'نامشخص';

// آمار
$chapter_count  = fn_get_chapter_count( $novel_id );
$total_views    = absint( get_post_meta( $novel_id, '_fn_total_views', true ) );
$avg_rating     = floatval( get_post_meta( $novel_id, '_fn_avg_rating', true ) );
$rating_count   = absint( get_post_meta( $novel_id, '_fn_rating_count', true ) );
$bookmark_count = absint( get_post_meta( $novel_id, '_fn_bookmark_count', true ) );

// فصل اول و آخر
$first_chapter = fn_get_first_chapter( $novel_id );
$last_chapter  = fn_get_latest_chapter( $novel_id );

// بوکمارک شده یا نه
$is_bookmarked = false;
if ( is_user_logged_in() ) {
    global $wpdb;
    $is_bookmarked = (bool) $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}fn_bookmarks WHERE user_id = %d AND novel_id = %d",
        get_current_user_id(), $novel_id
    ) );
}

// امتیاز کاربر فعلی
$user_rating = 0;
if ( is_user_logged_in() ) {
    global $wpdb;
    $user_rating = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT rating FROM {$wpdb->prefix}fn_ratings WHERE user_id = %d AND novel_id = %d",
        get_current_user_id(), $novel_id
    ) );
}

// آخرین فصل خوانده‌شده کاربر
$reading_progress = null;
if ( is_user_logged_in() ) {
    global $wpdb;
    $reading_progress = $wpdb->get_row( $wpdb->prepare(
        "SELECT chapter_id, scroll_position FROM {$wpdb->prefix}fn_reading_progress WHERE user_id = %d AND novel_id = %d",
        get_current_user_id(), $novel_id
    ) );
}

// زبان‌ها
$languages = array(
    'chinese'  => '🇨🇳 چینی',
    'korean'   => '🇰🇷 کره‌ای',
    'japanese' => '🇯🇵 ژاپنی',
    'english'  => '🇬🇧 انگلیسی',
    'persian'  => '🇮🇷 فارسی',
    'other'    => '🌍 سایر',
);

// ثبت بازدید
do_action( 'fn_count_view', $novel_id );
?>

<div class="fn-container">
    <?php fn_breadcrumb(); ?>
</div>

<!-- ===== بخش هیرو رمان ===== -->
<section class="fn-novel-hero">
    <!-- پس‌زمینه بلور -->
    <?php if ( $cover_full ) : ?>
        <div class="fn-novel-hero__bg" style="background-image: url('<?php echo esc_url( $cover_full ); ?>')"></div>
    <?php endif; ?>

    <div class="fn-container">
        <div class="fn-novel-hero__inner">

            <!-- کاور -->
            <div class="fn-novel-hero__cover">
                <?php if ( $cover_url ) : ?>
                    <img src="<?php echo esc_url( $cover_url ); ?>"
                         alt="<?php echo esc_attr( get_the_title() ); ?>"
                         width="300" height="400">
                <?php else : ?>
                    <div class="fn-novel-hero__cover-placeholder">
                        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" opacity="0.3">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                        </svg>
                    </div>
                <?php endif; ?>
            </div>

            <!-- اطلاعات -->
            <div class="fn-novel-hero__details">

                <!-- وضعیت -->
                <span class="fn-novel-card__badge fn-novel-card__badge--<?php echo esc_attr( $status_slug ); ?>" style="margin-bottom: 10px; display: inline-block;">
                    <?php echo esc_html( $status_name ); ?>
                </span>

                <!-- عنوان -->
                <h1 class="fn-novel-hero__title"><?php the_title(); ?></h1>

                <!-- نویسنده و مترجم -->
                <div class="fn-novel-hero__author">
                    <?php if ( $original_author ) : ?>
                        <span>✍️ نویسنده: <strong><?php echo esc_html( $original_author ); ?></strong></span>
                    <?php endif; ?>
                    <?php if ( $translator ) : ?>
                        <span class="fn-novel-hero__sep">|</span>
                        <span>🌐 مترجم: <strong><?php echo esc_html( $translator ); ?></strong></span>
                    <?php endif; ?>
                </div>

                <!-- نام‌های جایگزین -->
                <?php if ( $alt_names ) : ?>
                    <div class="fn-novel-hero__alt-names">
                        📝 <?php echo esc_html( $alt_names ); ?>
                    </div>
                <?php endif; ?>

                <!-- آمار -->
                <div class="fn-novel-hero__stats">
                    <div class="fn-stat">
                        <div class="fn-stat__value"><?php echo fn_format_number( $chapter_count ); ?></div>
                        <div class="fn-stat__label">فصل</div>
                    </div>
                    <div class="fn-stat">
                        <div class="fn-stat__value"><?php echo fn_format_number( $total_views ); ?></div>
                        <div class="fn-stat__label">بازدید</div>
                    </div>
                    <div class="fn-stat">
                        <div class="fn-stat__value"><?php echo fn_format_number( $bookmark_count ); ?></div>
                        <div class="fn-stat__label">بوکمارک</div>
                    </div>
                    <div class="fn-stat">
                        <div class="fn-stat__value">
                            <?php echo $avg_rating > 0 ? number_format( $avg_rating, 1 ) : '—'; ?>
                        </div>
                        <div class="fn-stat__label">
                            امتیاز
                            <?php if ( $rating_count > 0 ) : ?>
                                <small>(<?php echo fn_format_number( $rating_count ); ?>)</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- امتیازدهی ستاره‌ای -->
                <div class="fn-rating-wrap">
                    <span class="fn-rating-wrap__label">امتیاز شما:</span>
                    <div class="fn-stars" data-novel-id="<?php echo esc_attr( $novel_id ); ?>">
                        <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                            <span class="fn-star-icon <?php echo $i <= $user_rating ? 'filled' : ''; ?>"
                                  data-rating="<?php echo $i; ?>"
                                  role="button"
                                  aria-label="<?php echo $i; ?> ستاره">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="<?php echo $i <= $user_rating ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="1.5">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                </svg>
                            </span>
                        <?php endfor; ?>
                    </div>
                    <span class="fn-rating-avg"><?php echo $avg_rating > 0 ? number_format( $avg_rating, 1 ) : ''; ?></span>
                </div>

                <!-- دکمه‌های عملیاتی -->
                <div class="fn-novel-hero__actions">
                    <?php
                    // دکمه ادامه/شروع خواندن
                    if ( $reading_progress && $reading_progress->chapter_id ) :
                        $continue_chapter = get_post( $reading_progress->chapter_id );
                        if ( $continue_chapter ) :
                            $continue_num = get_post_meta( $continue_chapter->ID, '_fn_chapter_number', true );
                    ?>
                        <a href="<?php echo esc_url( get_permalink( $continue_chapter->ID ) ); ?>" class="fn-btn fn-btn--primary fn-btn--lg">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                            ادامه خواندن (فصل <?php echo esc_html( $continue_num ); ?>)
                        </a>
                    <?php
                        endif;
                    elseif ( $first_chapter ) :
                    ?>
                        <a href="<?php echo esc_url( get_permalink( $first_chapter->ID ) ); ?>" class="fn-btn fn-btn--primary fn-btn--lg">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                            شروع خواندن
                        </a>
                    <?php endif; ?>

                    <?php if ( $last_chapter && ( ! $reading_progress || $reading_progress->chapter_id != $last_chapter->ID ) ) :
                        $last_num = get_post_meta( $last_chapter->ID, '_fn_chapter_number', true );
                    ?>
                        <a href="<?php echo esc_url( get_permalink( $last_chapter->ID ) ); ?>" class="fn-btn fn-btn--ghost">
                            آخرین فصل (<?php echo esc_html( $last_num ); ?>)
                        </a>
                    <?php endif; ?>

                    <!-- دکمه بوکمارک -->
                    <button class="fn-bookmark-btn <?php echo $is_bookmarked ? 'bookmarked' : ''; ?>"
                            data-novel-id="<?php echo esc_attr( $novel_id ); ?>"
                            id="fnBookmarkBtn">
                        <svg width="18" height="18" viewBox="0 0 24 24"
                             fill="<?php echo $is_bookmarked ? 'currentColor' : 'none'; ?>"
                             stroke="currentColor" stroke-width="2">
                            <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                        </svg>
                        <span class="fn-bookmark-btn__text">
                            <?php echo $is_bookmarked ? 'در کتابخانه' : 'افزودن به کتابخانه'; ?>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- ===== محتوای اصلی رمان ===== -->
<div class="fn-container">
    <div class="fn-novel-content">

        <!-- ستون اصلی -->
        <div class="fn-novel-content__main">

            <!-- خلاصه داستان -->
            <div class="fn-novel-section fn-fade-in" id="synopsis">
                <h2 class="fn-novel-section__title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    خلاصه داستان
                </h2>
                <div class="fn-novel-synopsis" id="fnSynopsis">
                    <div class="fn-novel-synopsis__text <?php echo mb_strlen( $synopsis ) > 500 ? 'fn-novel-synopsis__text--collapsed' : ''; ?>">
                        <?php
                        if ( $synopsis ) {
                            echo wp_kses_post( nl2br( $synopsis ) );
                        } else {
                            echo '<p style="color: var(--fn-text-muted);">خلاصه‌ای برای این رمان ثبت نشده است.</p>';
                        }
                        ?>
                    </div>
                    <?php if ( mb_strlen( $synopsis ) > 500 ) : ?>
                        <button class="fn-novel-synopsis__toggle" id="fnSynopsisToggle">
                            <span class="fn-synopsis-more">نمایش بیشتر ▼</span>
                            <span class="fn-synopsis-less fn-hidden">نمایش کمتر ▲</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- اطلاعات جزئی -->
            <div class="fn-novel-section fn-fade-in" id="info">
                <h2 class="fn-novel-section__title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    اطلاعات رمان
                </h2>
                <div class="fn-novel-info-grid">
                    <?php if ( $original_author ) : ?>
                        <div class="fn-info-item">
                            <span class="fn-info-item__label">نویسنده</span>
                            <span class="fn-info-item__value"><?php echo esc_html( $original_author ); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ( $translator ) : ?>
                        <div class="fn-info-item">
                            <span class="fn-info-item__label">مترجم</span>
                            <span class="fn-info-item__value"><?php echo esc_html( $translator ); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ( $original_lang && isset( $languages[ $original_lang ] ) ) : ?>
                        <div class="fn-info-item">
                            <span class="fn-info-item__label">زبان اصلی</span>
                            <span class="fn-info-item__value"><?php echo esc_html( $languages[ $original_lang ] ); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ( $publish_year ) : ?>
                        <div class="fn-info-item">
                            <span class="fn-info-item__label">سال انتشار</span>
                            <span class="fn-info-item__value"><?php echo esc_html( $publish_year ); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="fn-info-item">
                        <span class="fn-info-item__label">وضعیت</span>
                        <span class="fn-info-item__value">
                            <span class="fn-novel-card__badge fn-novel-card__badge--<?php echo esc_attr( $status_slug ); ?>">
                                <?php echo esc_html( $status_name ); ?>
                            </span>
                        </span>
                    </div>

                    <div class="fn-info-item">
                        <span class="fn-info-item__label">تعداد فصل</span>
                        <span class="fn-info-item__value"><?php echo fn_format_number( $chapter_count ); ?></span>
                    </div>

                    <div class="fn-info-item">
                        <span class="fn-info-item__label">تاریخ انتشار</span>
                        <span class="fn-info-item__value"><?php echo get_the_date( 'Y/m/d' ); ?></span>
                    </div>

                    <div class="fn-info-item">
                        <span class="fn-info-item__label">آخرین بروزرسانی</span>
                        <span class="fn-info-item__value"><?php echo get_the_modified_date( 'Y/m/d' ); ?></span>
                    </div>
                </div>

                <!-- ژانرها -->
                <?php if ( ! empty( $genres ) && ! is_wp_error( $genres ) ) : ?>
                    <div class="fn-novel-tags-section">
                        <span class="fn-info-item__label">ژانرها:</span>
                        <div class="fn-novel-tags-list">
                            <?php foreach ( $genres as $genre ) : ?>
                                <a href="<?php echo esc_url( get_term_link( $genre ) ); ?>" class="fn-tag fn-tag--genre">
                                    <?php echo esc_html( $genre->name ); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- تگ‌ها -->
                <?php if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) : ?>
                    <div class="fn-novel-tags-section">
                        <span class="fn-info-item__label">تگ‌ها:</span>
                        <div class="fn-novel-tags-list">
                            <?php foreach ( $tags as $tag ) : ?>
                                <a href="<?php echo esc_url( get_term_link( $tag ) ); ?>" class="fn-tag fn-tag--default">
                                    <?php echo esc_html( $tag->name ); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- لیست فصل‌ها -->
            <div class="fn-novel-section fn-fade-in" id="chapters">
                <?php
                // بررسی نوع رمان - چاپی یا وب‌ای
                $is_light_novel = fn_is_light_novel( $novel_id );
                $novel_type = fn_get_novel_type( $novel_id );
                
                if ( $is_light_novel && ! empty( fn_get_volumes( $novel_id ) ) ) {
                    // نمایش فصل‌ها به صورت جلدها
                    get_template_part( 'template-parts/chapter-list-volumes' );
                } else {
                    // نمایش فصل‌ها به صورت فهرست عادی
                ?>
                <div class="fn-chapter-list">
                    <div class="fn-chapter-list__header">
                        <h2 class="fn-novel-section__title" style="margin: 0;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                            فهرست فصل‌ها
                            <span class="fn-chapter-list__count">(<?php echo fn_format_number( $chapter_count ); ?>)</span>
                        </h2>

                        <div class="fn-chapter-list__controls">
                            <!-- جستجوی فصل -->
                            <div class="fn-chapter-search">
                                <input type="text" id="fnChapterSearch" placeholder="جستجوی فصل..." class="fn-chapter-search__input">
                            </div>

                            <!-- مرتب‌سازی -->
                            <button class="fn-chapter-sort-btn" id="fnChapterSortBtn" title="تغییر ترتیب">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                                <span id="fnSortLabel">نزولی</span>
                            </button>
                        </div>
                    </div>

                    <!-- لیست فصل‌ها -->
                    <div class="fn-chapter-list__body" id="fnChapterListBody">
                        <?php
                        $chapters = new WP_Query( array(
                            'post_type'      => 'chapter',
                            'posts_per_page' => 100,
                            'orderby'        => 'meta_value_num',
                            'meta_key'       => '_fn_chapter_number',
                            'order'          => 'DESC',
                            'post_status'    => 'publish',
                            'meta_query'     => array(
                                array(
                                    'key'   => '_fn_parent_novel',
                                    'value' => $novel_id,
                                    'type'  => 'NUMERIC',
                                ),
                            ),
                        ) );

                        if ( $chapters->have_posts() ) :
                            while ( $chapters->have_posts() ) :
                                $chapters->the_post();
                                $ch_id     = get_the_ID();
                                $ch_number = get_post_meta( $ch_id, '_fn_chapter_number', true );
                                $ch_title  = get_the_title();
                                $ch_date   = get_the_time( 'U' );

                                // آیا این فصل خوانده شده؟
                                $is_read = false;
                                if ( $reading_progress && $reading_progress->chapter_id ) {
                                    $progress_num = get_post_meta( $reading_progress->chapter_id, '_fn_chapter_number', true );
                                    $is_read = ( floatval( $ch_number ) <= floatval( $progress_num ) );
                                }
                        ?>
                            <a href="<?php the_permalink(); ?>"
                               class="fn-chapter-item <?php echo $is_read ? 'fn-chapter-item--read' : ''; ?>"
                               data-chapter-number="<?php echo esc_attr( $ch_number ); ?>">
                                <div class="fn-chapter-item__right">
                                    <?php if ( $is_read ) : ?>
                                        <span class="fn-chapter-item__check">✓</span>
                                    <?php endif; ?>
                                    <span class="fn-chapter-item__number">فصل <?php echo esc_html( $ch_number ); ?></span>
                                    <span class="fn-chapter-item__title"><?php echo esc_html( $ch_title ); ?></span>
                                </div>
                                <span class="fn-chapter-item__date"><?php echo fn_time_ago( $ch_date ); ?></span>
                            </a>
                        <?php
                            endwhile;
                            wp_reset_postdata();
                        else :
                        ?>
                            <div class="fn-chapter-list__empty">
                                <p>هنوز فصلی منتشر نشده است.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- بارگذاری بیشتر -->
                    <?php if ( $chapter_count > 100 ) : ?>
                        <div class="fn-chapter-list__footer">
                            <button class="fn-btn fn-btn--ghost fn-btn--full" id="fnLoadMoreChapters"
                                    data-novel-id="<?php echo esc_attr( $novel_id ); ?>"
                                    data-page="2"
                                    data-total="<?php echo ceil( $chapter_count / 100 ); ?>">
                                بارگذاری فصل‌های بیشتر
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
                <?php } // end if light novel ?>
            </div>

            <!-- نظرات -->
            <div class="fn-novel-section fn-fade-in" id="comments-section">
                <h2 class="fn-novel-section__title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    نظرات
                    <span class="fn-chapter-list__count">(<?php echo get_comments_number(); ?>)</span>
                </h2>
                <?php
                if ( comments_open() || get_comments_number() ) {
                    comments_template();
                }
                ?>
            </div>
        </div>

        <!-- ===== سایدبار ===== -->
        <aside class="fn-novel-content__sidebar">
            <?php get_template_part( 'template-parts/novel-sidebar' ); ?>
        </aside>

    </div>
</div>

<!-- ===== رمان‌های مشابه ===== -->
<?php
$similar_genre_ids = array();
if ( ! empty( $genres ) && ! is_wp_error( $genres ) ) {
    foreach ( $genres as $g ) {
        $similar_genre_ids[] = $g->term_id;
    }
}

if ( ! empty( $similar_genre_ids ) ) :
    $similar_novels = new WP_Query( array(
        'post_type'      => 'novel',
        'posts_per_page' => 6,
        'post__not_in'   => array( $novel_id ),
        'post_status'    => 'publish',
        'tax_query'      => array(
            array(
                'taxonomy' => 'genre',
                'field'    => 'term_id',
                'terms'    => $similar_genre_ids,
            ),
        ),
        'orderby' => 'rand',
    ) );

    if ( $similar_novels->have_posts() ) :
?>
<section class="fn-section fn-fade-in">
    <div class="fn-container">
        <div class="fn-section__header">
            <h2 class="fn-section__title">رمان‌های مشابه</h2>
        </div>
        <div class="fn-novels-grid">
            <?php
            while ( $similar_novels->have_posts() ) :
                $similar_novels->the_post();
                get_template_part( 'template-parts/novel-card' );
            endwhile;
            wp_reset_postdata();
            ?>
        </div>
    </div>
</section>
<?php
    endif;
endif;
?>

<!-- اسکریپت صفحه تک رمان -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // تاگل خلاصه داستان
    const synopsisToggle = document.getElementById('fnSynopsisToggle');
    if (synopsisToggle) {
        synopsisToggle.addEventListener('click', function() {
            const text = document.querySelector('.fn-novel-synopsis__text');
            const moreLabel = this.querySelector('.fn-synopsis-more');
            const lessLabel = this.querySelector('.fn-synopsis-less');

            text.classList.toggle('fn-novel-synopsis__text--collapsed');
            moreLabel.classList.toggle('fn-hidden');
            lessLabel.classList.toggle('fn-hidden');
        });
    }

    // مرتب‌سازی فصل‌ها
    const sortBtn = document.getElementById('fnChapterSortBtn');
    if (sortBtn) {
        let isDesc = true;
        sortBtn.addEventListener('click', function() {
            isDesc = !isDesc;
            const body = document.getElementById('fnChapterListBody');
            const items = [...body.querySelectorAll('.fn-chapter-item')];

            items.sort((a, b) => {
                const numA = parseFloat(a.dataset.chapterNumber);
                const numB = parseFloat(b.dataset.chapterNumber);
                return isDesc ? numB - numA : numA - numB;
            });

            body.innerHTML = '';
            items.forEach(item => body.appendChild(item));

            document.getElementById('fnSortLabel').textContent = isDesc ? 'نزولی' : 'صعودی';
            this.querySelector('svg').style.transform = isDesc ? '' : 'rotate(180deg)';
        });
    }

    // جستجوی فصل
    const chapterSearch = document.getElementById('fnChapterSearch');
    if (chapterSearch) {
        chapterSearch.addEventListener('input', FN.debounce(function() {
            const query = this.value.trim().toLowerCase();
            const items = document.querySelectorAll('.fn-chapter-item');

            items.forEach(item => {
                const title = item.querySelector('.fn-chapter-item__title').textContent.toLowerCase();
                const number = item.dataset.chapterNumber;
                const match = title.includes(query) || number.includes(query);
                item.style.display = match ? '' : 'none';
            });
        }, 200));
    }

    // بارگذاری بیشتر فصل‌ها
    const loadMoreBtn = document.getElementById('fnLoadMoreChapters');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', async function() {
            const novelId = this.dataset.novelId;
            const page = parseInt(this.dataset.page);
            const total = parseInt(this.dataset.total);

            this.textContent = 'در حال بارگذاری...';
            this.disabled = true;

            const data = await FN.rest('chapters/' + novelId, {
                page: page,
                per_page: 100,
                order: 'DESC'
            });

            if (data && data.chapters) {
                const body = document.getElementById('fnChapterListBody');
                data.chapters.forEach(ch => {
                    const link = document.createElement('a');
                    link.href = ch.url;
                    link.className = 'fn-chapter-item';
                    link.dataset.chapterNumber = ch.chapter_number;
                    link.innerHTML = `
                        <div class="fn-chapter-item__right">
                            <span class="fn-chapter-item__number">فصل ${ch.chapter_number}</span>
                            <span class="fn-chapter-item__title">${ch.title}</span>
                        </div>
                        <span class="fn-chapter-item__date">${ch.date_human}</span>
                    `;
                    body.appendChild(link);
                });

                this.dataset.page = page + 1;
                if (page >= total) {
                    this.remove();
                } else {
                    this.textContent = 'بارگذاری فصل‌های بیشتر';
                    this.disabled = false;
                }
            }
        });
    }
});
</script>

<?php get_footer(); ?>