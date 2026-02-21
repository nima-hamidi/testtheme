<?php
/**
 * Front Page Template
 * صفحه اصلی سایت ناول
 *
 * @package suspended-starter
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$is_logged_in    = is_user_logged_in();
$current_user_id = get_current_user_id();
global $wpdb;

// ═══ تنظیمات ادمین صفحه اصلی ═══
$default_sections_logged = array(
    'announcement',
    'hero_slider',
    'continue_reading',
    'daily_recommendation',
    'trending',
    'active_contest',
    'latest_updates',
    'popular_novels',
    'newest_novels',
    'top_authors',
    'latest_comments',
    'active_poll',
    'mood_filter',
);

$default_sections_guest = array(
    'announcement',
    'hero_slider',
    'trending',
    'active_contest',
    'latest_updates',
    'popular_novels',
    'newest_novels',
    'cta_register',
    'top_authors',
    'latest_comments',
    'mood_filter',
);

$saved_sections = get_option( 'novel_homepage_sections', array() );

if ( $is_logged_in ) {
    $sections = ! empty( $saved_sections['logged_in'] ) ? $saved_sections['logged_in'] : $default_sections_logged;
} else {
    $sections = ! empty( $saved_sections['guest'] ) ? $saved_sections['guest'] : $default_sections_guest;
}

$disabled_sections = get_option( 'novel_homepage_disabled_sections', array() );
?>

<main id="front-page" class="front-page" role="main">
    <?php
    foreach ( $sections as $section_key ) :

        if ( in_array( $section_key, $disabled_sections, true ) ) {
            continue;
        }

        switch ( $section_key ) :

            // ═══════════════════════════════════════
            // سکشن ۱: بنر اطلاعیه ادمین
            // ═══════════════════════════════════════
            case 'announcement':
                $banner_active = get_option( 'novel_announcement_active', false );
                if ( $banner_active ) :
                    $banner_text = get_option( 'novel_announcement_text', '' );
                    $banner_type = get_option( 'novel_announcement_type', 'info' );
                    $banner_link = get_option( 'novel_announcement_link', '' );
                    $banner_btn  = get_option( 'novel_announcement_btn_text', '' );
                    $banner_id   = get_option( 'novel_announcement_id', 'default' );
                    $banner_end  = get_option( 'novel_announcement_end_date', '' );

                    $show_banner = true;
                    if ( ! empty( $banner_end ) ) {
                        $end_ts = strtotime( $banner_end );
                        if ( $end_ts && time() > $end_ts ) {
                            $show_banner = false;
                        }
                    }

                    if ( $show_banner && ! empty( $banner_text ) ) :
                        $type_icons = array(
                            'info'    => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>',
                            'warning' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
                            'danger'  => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
                            'success' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
                            'promo'   => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
                        );
                        $icon = isset( $type_icons[ $banner_type ] ) ? $type_icons[ $banner_type ] : $type_icons['info'];
                        ?>
                        <div class="fp-announcement fp-announcement--<?php echo esc_attr( $banner_type ); ?>"
                             id="fp-announcement"
                             data-banner-id="<?php echo esc_attr( $banner_id ); ?>"
                             role="alert"
                             style="display:none;">
                            <div class="container">
                                <div class="fp-announcement__inner">
                                    <span class="fp-announcement__icon"><?php echo $icon; ?></span>
                                    <p class="fp-announcement__text"><?php echo esc_html( $banner_text ); ?></p>
                                    <?php if ( ! empty( $banner_link ) && ! empty( $banner_btn ) ) : ?>
                                        <a href="<?php echo esc_url( $banner_link ); ?>" class="fp-announcement__btn">
                                            <?php echo esc_html( $banner_btn ); ?>
                                        </a>
                                    <?php endif; ?>
                                    <button class="fp-announcement__close" aria-label="<?php esc_attr_e( 'بستن', 'flavor-starter' ); ?>"
                                            data-banner-id="<?php echo esc_attr( $banner_id ); ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php
                    endif;
                endif;
                break;

            // ═══════════════════════════════════════
            // سکشن ۲: اسلایدر ناول‌های ویژه (Hero)
            // ═══════════════════════════════════════
            case 'hero_slider':
                $featured_args = array(
                    'post_type'      => 'novel',
                    'posts_per_page' => 5,
                    'post_status'    => 'publish',
                    'meta_query'     => array(
                        array(
                            'key'     => 'is_featured',
                            'value'   => '1',
                            'compare' => '=',
                        ),
                    ),
                    'orderby' => 'rand',
                );

                $featured_novels = new WP_Query( $featured_args );

                // fallback: sticky
                if ( ! $featured_novels->have_posts() ) {
                    $sticky = get_option( 'sticky_posts', array() );
                    if ( ! empty( $sticky ) ) {
                        $featured_novels = new WP_Query( array(
                            'post_type'      => 'novel',
                            'posts_per_page' => 5,
                            'post__in'       => $sticky,
                            'post_status'    => 'publish',
                            'orderby'        => 'rand',
                        ) );
                    }
                }

                // fallback: most viewed
                if ( ! $featured_novels->have_posts() ) {
                    $featured_novels = new WP_Query( array(
                        'post_type'      => 'novel',
                        'posts_per_page' => 5,
                        'post_status'    => 'publish',
                        'meta_key'       => 'novel_views',
                        'orderby'        => 'meta_value_num',
                        'order'          => 'DESC',
                    ) );
                }

                if ( $featured_novels->have_posts() ) :
                    $slide_count = $featured_novels->post_count;
                    ?>
                    <section class="fp-section fp-hero" data-section="hero_slider" aria-label="<?php esc_attr_e( 'رمان‌های ویژه', 'flavor-starter' ); ?>">
                        <div class="fp-hero__slider" data-auto-play="5000" data-slide-count="<?php echo esc_attr( $slide_count ); ?>">
                            <div class="fp-hero__track">
                                <?php
                                $slide_index = 0;
                                while ( $featured_novels->have_posts() ) :
                                    $featured_novels->the_post();
                                    $novel_id  = get_the_ID();
                                    $cover     = get_the_post_thumbnail_url( $novel_id, 'large' );
                                    $title     = get_the_title();
                                    $permalink = get_permalink();
                                    $excerpt   = mb_substr( wp_strip_all_tags( get_the_excerpt() ), 0, 120, 'UTF-8' );

                                    // نویسنده
                                    $author_name     = '';
                                    $novel_author_id = get_post_meta( $novel_id, 'novel_author', true );
                                    if ( $novel_author_id ) {
                                        $author_user = get_userdata( intval( $novel_author_id ) );
                                        if ( $author_user ) {
                                            $author_name = $author_user->display_name;
                                        }
                                    }
                                    if ( empty( $author_name ) ) {
                                        $author_name = get_the_author();
                                    }

                                    // ژانر
                                    $genres     = wp_get_post_terms( $novel_id, 'genre', array( 'fields' => 'names' ) );
                                    $genres_str = is_array( $genres ) ? implode( '، ', array_slice( $genres, 0, 3 ) ) : '';

                                    // امتیاز
                                    $rating         = floatval( get_post_meta( $novel_id, 'novel_rating', true ) );
                                    $rating_display = $rating > 0
                                        ? ( function_exists( 'novel_format_number' ) ? novel_format_number( $rating, 1 ) : number_format( $rating, 1 ) )
                                        : '—';

                                    // وضعیت
                                    $status_terms = wp_get_post_terms( $novel_id, 'novel_status', array( 'fields' => 'names' ) );
                                    $status       = ! empty( $status_terms ) && ! is_wp_error( $status_terms ) ? $status_terms[0] : '';

                                    // لینک شروع
                                    $first_chapter = get_posts( array(
                                        'post_type'      => 'chapter',
                                        'posts_per_page' => 1,
                                        'post_status'    => 'publish',
                                        'meta_key'       => 'chapter_number',
                                        'orderby'        => 'meta_value_num',
                                        'order'          => 'ASC',
                                        'meta_query'     => array(
                                            array(
                                                'key'   => 'parent_novel',
                                                'value' => $novel_id,
                                            ),
                                        ),
                                        'fields' => 'ids',
                                    ) );
                                    $start_link = ! empty( $first_chapter ) ? get_permalink( $first_chapter[0] ) : $permalink;

                                    if ( empty( $cover ) ) {
                                        $cover = get_template_directory_uri() . '/assets/images/placeholder-cover.jpg';
                                    }
                                    ?>
                                    <div class="fp-hero__slide <?php echo $slide_index === 0 ? 'fp-hero__slide--active' : ''; ?>"
                                         data-slide="<?php echo esc_attr( $slide_index ); ?>">
                                        <div class="fp-hero__bg" style="background-image: url('<?php echo esc_url( $cover ); ?>');" aria-hidden="true"></div>
                                        <div class="fp-hero__overlay"></div>
                                        <div class="container">
                                            <div class="fp-hero__content">
                                                <div class="fp-hero__cover">
                                                    <img src="<?php echo esc_url( $cover ); ?>"
                                                         alt="<?php echo esc_attr( $title ); ?>"
                                                         class="fp-hero__cover-img"
                                                         loading="<?php echo $slide_index === 0 ? 'eager' : 'lazy'; ?>"
                                                         width="220" height="320" />
                                                    <?php if ( $rating > 0 ) : ?>
                                                        <div class="fp-hero__rating-badge">
                                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                                            <span><?php echo esc_html( $rating_display ); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="fp-hero__info">
                                                    <?php if ( ! empty( $genres_str ) ) : ?>
                                                        <span class="fp-hero__genres"><?php echo esc_html( $genres_str ); ?></span>
                                                    <?php endif; ?>
                                                    <h2 class="fp-hero__title">
                                                        <a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
                                                    </h2>
                                                    <?php if ( ! empty( $author_name ) ) : ?>
                                                        <p class="fp-hero__author">
                                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                                            <?php echo esc_html( $author_name ); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                    <?php if ( ! empty( $status ) ) : ?>
                                                        <span class="fp-hero__status"><?php echo esc_html( $status ); ?></span>
                                                    <?php endif; ?>
                                                    <?php if ( ! empty( $excerpt ) ) : ?>
                                                        <p class="fp-hero__excerpt"><?php echo esc_html( $excerpt ); ?>…</p>
                                                    <?php endif; ?>
                                                    <div class="fp-hero__actions">
                                                        <a href="<?php echo esc_url( $start_link ); ?>" class="btn btn--primary btn--lg fp-hero__start-btn">
                                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                                            <?php esc_html_e( 'شروع مطالعه', 'flavor-starter' ); ?>
                                                        </a>
                                                        <a href="<?php echo esc_url( $permalink ); ?>" class="btn btn--outline btn--lg fp-hero__info-btn">
                                                            <?php esc_html_e( 'اطلاعات بیشتر', 'flavor-starter' ); ?>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                    $slide_index++;
                                endwhile;
                                wp_reset_postdata();
                                ?>
                            </div>
                            <?php if ( $slide_count > 1 ) : ?>
                                <div class="fp-hero__nav">
                                    <button class="fp-hero__nav-btn fp-hero__nav-btn--prev" aria-label="<?php esc_attr_e( 'اسلاید قبلی', 'flavor-starter' ); ?>">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                                    </button>
                                    <button class="fp-hero__nav-btn fp-hero__nav-btn--next" aria-label="<?php esc_attr_e( 'اسلاید بعدی', 'flavor-starter' ); ?>">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                                    </button>
                                </div>
                                <div class="fp-hero__dots">
                                    <?php for ( $i = 0; $i < $slide_count; $i++ ) : ?>
                                        <button class="fp-hero__dot <?php echo $i === 0 ? 'fp-hero__dot--active' : ''; ?>"
                                                data-slide="<?php echo esc_attr( $i ); ?>"
                                                aria-label="<?php echo esc_attr( sprintf( __( 'اسلاید %s', 'flavor-starter' ), $i + 1 ) ); ?>"></button>
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                    <?php
                endif;
                break;

            // ═══════════════════════════════════════
            // سکشن ۳: ادامه مطالعه (فقط لاگین)
            // ═══════════════════════════════════════
            case 'continue_reading':
                if ( ! $is_logged_in ) break;

                $library_table = $wpdb->prefix . 'novel_user_library';
                $history_table = $wpdb->prefix . 'novel_reading_history';

                $reading_novels = $wpdb->get_results( $wpdb->prepare(
                    "SELECT l.novel_id, l.current_chapter, l.total_chapters,
                            COALESCE(h.last_read, l.added_date) as last_activity
                     FROM {$library_table} l
                     LEFT JOIN (
                         SELECT novel_id, MAX(read_date) as last_read
                         FROM {$history_table}
                         WHERE user_id = %d
                         GROUP BY novel_id
                     ) h ON l.novel_id = h.novel_id
                     WHERE l.user_id = %d AND l.list_type = 'reading'
                     ORDER BY last_activity DESC
                     LIMIT 5",
                    $current_user_id, $current_user_id
                ) );
                ?>
                <section class="fp-section fp-continue" data-section="continue_reading"
                         aria-label="<?php esc_attr_e( 'ادامه مطالعه', 'flavor-starter' ); ?>">
                    <div class="container">
                        <div class="fp-section__header">
                            <h2 class="fp-section__title">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/><path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/></svg>
                                <?php esc_html_e( 'ادامه مطالعه', 'flavor-starter' ); ?>
                            </h2>
                            <a href="<?php echo esc_url( home_url( '/dashboard/?tab=bookmarks' ) ); ?>" class="fp-section__more">
                                <?php esc_html_e( 'کتابخانه من', 'flavor-starter' ); ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                            </a>
                        </div>

                        <?php if ( ! empty( $reading_novels ) ) : ?>
                            <div class="fp-continue__slider">
                                <div class="fp-continue__track">
                                    <?php foreach ( $reading_novels as $item ) :
                                        $novel_id   = intval( $item->novel_id );
                                        $novel_post = get_post( $novel_id );
                                        if ( ! $novel_post || $novel_post->post_status !== 'publish' ) continue;

                                        $cover      = get_the_post_thumbnail_url( $novel_id, 'medium' );
                                        $title      = get_the_title( $novel_id );
                                        $current_ch = intval( $item->current_chapter );
                                        $total_ch   = intval( $item->total_chapters );

                                        if ( $total_ch <= 0 ) {
                                            $total_ch = intval( get_post_meta( $novel_id, 'novel_chapters_count', true ) );
                                        }
                                        if ( $total_ch <= 0 ) {
                                            $total_ch = intval( $wpdb->get_var( $wpdb->prepare(
                                                "SELECT COUNT(*) FROM {$wpdb->posts} p
                                                 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                                                 WHERE p.post_type = 'chapter' AND p.post_status = 'publish'
                                                 AND pm.meta_key = 'parent_novel' AND pm.meta_value = %s",
                                                $novel_id
                                            ) ) );
                                        }

                                        $progress = ( $total_ch > 0 ) ? min( round( ( $current_ch / $total_ch ) * 100 ), 100 ) : 0;

                                        // لینک ادامه
                                        $next_ch_num  = $current_ch + 1;
                                        $next_chapter = $wpdb->get_var( $wpdb->prepare(
                                            "SELECT p.ID FROM {$wpdb->posts} p
                                             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                                             INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
                                             WHERE p.post_type = 'chapter' AND p.post_status = 'publish'
                                             AND pm.meta_key = 'parent_novel' AND pm.meta_value = %s
                                             AND pm2.meta_key = 'chapter_number' AND pm2.meta_value = %s
                                             LIMIT 1",
                                            $novel_id, $next_ch_num
                                        ) );

                                        if ( $next_chapter ) {
                                            $continue_link = get_permalink( $next_chapter );
                                        } else {
                                            $last_read = $wpdb->get_var( $wpdb->prepare(
                                                "SELECT p.ID FROM {$wpdb->posts} p
                                                 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                                                 INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
                                                 WHERE p.post_type = 'chapter' AND p.post_status = 'publish'
                                                 AND pm.meta_key = 'parent_novel' AND pm.meta_value = %s
                                                 AND pm2.meta_key = 'chapter_number' AND pm2.meta_value = %s
                                                 LIMIT 1",
                                                $novel_id, $current_ch
                                            ) );
                                            $continue_link = $last_read ? get_permalink( $last_read ) : get_permalink( $novel_id );
                                        }

                                        $current_ch_fa = function_exists( 'novel_format_number' ) ? novel_format_number( $current_ch ) : $current_ch;
                                        $total_ch_fa   = function_exists( 'novel_format_number' ) ? novel_format_number( $total_ch ) : $total_ch;

                                        if ( empty( $cover ) ) {
                                            $cover = get_template_directory_uri() . '/assets/images/placeholder-cover.jpg';
                                        }
                                        ?>
                                        <div class="fp-continue__card">
                                            <a href="<?php echo esc_url( $continue_link ); ?>" class="fp-continue__cover-link">
                                                <img src="<?php echo esc_url( $cover ); ?>"
                                                     alt="<?php echo esc_attr( $title ); ?>"
                                                     class="fp-continue__cover"
                                                     loading="lazy" width="130" height="190" />
                                            </a>
                                            <div class="fp-continue__info">
                                                <h3 class="fp-continue__title">
                                                    <a href="<?php echo esc_url( get_permalink( $novel_id ) ); ?>"><?php echo esc_html( $title ); ?></a>
                                                </h3>
                                                <div class="fp-continue__progress-wrap">
                                                    <div class="fp-continue__progress-bar">
                                                        <div class="fp-continue__progress-fill" style="width: <?php echo esc_attr( $progress ); ?>%;"></div>
                                                    </div>
                                                    <span class="fp-continue__progress-text">
                                                        <?php printf( esc_html__( 'قسمت %1$s از %2$s', 'flavor-starter' ), $current_ch_fa, $total_ch_fa ); ?>
                                                    </span>
                                                </div>
                                                <a href="<?php echo esc_url( $continue_link ); ?>" class="btn btn--primary btn--sm fp-continue__btn">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                                    <?php esc_html_e( 'ادامه', 'flavor-starter' ); ?>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else : ?>
                            <div class="fp-continue__empty">
                                <div class="fp-continue__empty-icon">📚</div>
                                <p><?php esc_html_e( 'هنوز رمانی نخوانده‌اید!', 'flavor-starter' ); ?></p>
                                <a href="<?php echo esc_url( home_url( '/ranking/' ) ); ?>" class="btn btn--outline btn--sm">
                                    <?php esc_html_e( 'رمان‌های محبوب را ببینید', 'flavor-starter' ); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
                <?php
                break;

            // ═══════════════════════════════════════
            // سکشن ۴: پیشنهاد امروز (فقط لاگین)
            // ═══════════════════════════════════════
            case 'daily_recommendation':
                if ( ! $is_logged_in ) break;

                $cache_key       = 'novel_daily_rec_' . $current_user_id;
                $recommendations = get_transient( $cache_key );

                if ( false === $recommendations ) {
                    $recommendations = array();

                    // ① ژانرهای محبوب کاربر
                    $user_genres = $wpdb->get_col( $wpdb->prepare(
                        "SELECT tt.term_taxonomy_id
                         FROM {$wpdb->prefix}novel_reading_history h
                         INNER JOIN {$wpdb->term_relationships} tr ON h.novel_id = tr.object_id
                         INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                         WHERE h.user_id = %d AND tt.taxonomy = 'genre'
                         GROUP BY tt.term_taxonomy_id
                         ORDER BY COUNT(*) DESC
                         LIMIT 5",
                        $current_user_id
                    ) );

                    $read_novels = $wpdb->get_col( $wpdb->prepare(
                        "SELECT DISTINCT novel_id FROM {$wpdb->prefix}novel_user_library WHERE user_id = %d",
                        $current_user_id
                    ) );
                    if ( empty( $read_novels ) ) {
                        $read_novels = array( 0 );
                    }

                    if ( ! empty( $user_genres ) ) {
                        $genre_recs = new WP_Query( array(
                            'post_type'      => 'novel',
                            'posts_per_page' => 10,
                            'post_status'    => 'publish',
                            'post__not_in'   => $read_novels,
                            'tax_query'      => array(
                                array(
                                    'taxonomy' => 'genre',
                                    'terms'    => $user_genres,
                                    'field'    => 'term_taxonomy_id',
                                ),
                            ),
                            'meta_key' => 'novel_rating',
                            'orderby'  => 'meta_value_num',
                            'order'    => 'DESC',
                        ) );

                        if ( $genre_recs->have_posts() ) {
                            while ( $genre_recs->have_posts() ) {
                                $genre_recs->the_post();
                                $rec_id     = get_the_ID();
                                $rec_genres = wp_get_post_terms( $rec_id, 'genre', array( 'fields' => 'names' ) );
                                $recommendations[] = array(
                                    'novel_id' => $rec_id,
                                    'reason'   => sprintf( __( 'چون %s دوست دارید', 'flavor-starter' ), implode( ' و ', array_slice( $rec_genres, 0, 2 ) ) ),
                                );
                            }
                            wp_reset_postdata();
                        }
                    }

                    // ② Collaborative filtering
                    if ( count( $recommendations ) < 3 ) {
                        $similar_users_novels = $wpdb->get_col( $wpdb->prepare(
                            "SELECT DISTINCT h3.novel_id
                             FROM {$wpdb->prefix}novel_reading_history h1
                             INNER JOIN {$wpdb->prefix}novel_reading_history h2
                                ON h1.novel_id = h2.novel_id AND h1.user_id != h2.user_id
                             INNER JOIN {$wpdb->prefix}novel_reading_history h3
                                ON h2.user_id = h3.user_id
                             WHERE h1.user_id = %d
                             AND h3.novel_id NOT IN (" . implode( ',', array_map( 'intval', $read_novels ) ) . ")
                             LIMIT 10",
                            $current_user_id
                        ) );

                        if ( ! empty( $similar_users_novels ) ) {
                            $existing_ids = wp_list_pluck( $recommendations, 'novel_id' );
                            foreach ( $similar_users_novels as $sn_id ) {
                                if ( ! in_array( intval( $sn_id ), $existing_ids, true ) ) {
                                    $recommendations[] = array(
                                        'novel_id' => intval( $sn_id ),
                                        'reason'   => __( 'خوانندگان مشابه شما پسندیدند', 'flavor-starter' ),
                                    );
                                }
                                if ( count( $recommendations ) >= 6 ) break;
                            }
                        }
                    }

                    // ③ fallback
                    if ( count( $recommendations ) < 3 ) {
                        $fallback = new WP_Query( array(
                            'post_type'      => 'novel',
                            'posts_per_page' => 6,
                            'post_status'    => 'publish',
                            'post__not_in'   => array_merge( $read_novels, wp_list_pluck( $recommendations, 'novel_id' ) ),
                            'meta_key'       => 'novel_views',
                            'orderby'        => 'meta_value_num',
                            'order'          => 'DESC',
                        ) );
                        if ( $fallback->have_posts() ) {
                            while ( $fallback->have_posts() ) {
                                $fallback->the_post();
                                $recommendations[] = array(
                                    'novel_id' => get_the_ID(),
                                    'reason'   => __( 'رمان محبوب سایت', 'flavor-starter' ),
                                );
                            }
                            wp_reset_postdata();
                        }
                    }

                    shuffle( $recommendations );
                    $recommendations = array_slice( $recommendations, 0, 3 );
                    set_transient( $cache_key, $recommendations, DAY_IN_SECONDS );
                }

                if ( ! empty( $recommendations ) ) :
                    ?>
                    <section class="fp-section fp-recommendation" data-section="daily_recommendation"
                             aria-label="<?php esc_attr_e( 'پیشنهاد امروز', 'flavor-starter' ); ?>">
                        <div class="container">
                            <div class="fp-section__header">
                                <h2 class="fp-section__title">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                                    <?php esc_html_e( 'پیشنهاد امروز برای شما', 'flavor-starter' ); ?>
                                </h2>
                            </div>
                            <div class="fp-recommendation__grid">
                                <?php foreach ( $recommendations as $rec ) :
                                    $rec_id   = intval( $rec['novel_id'] );
                                    $rec_post = get_post( $rec_id );
                                    if ( ! $rec_post || $rec_post->post_status !== 'publish' ) continue;

                                    $rec_cover  = get_the_post_thumbnail_url( $rec_id, 'medium' );
                                    $rec_title  = get_the_title( $rec_id );
                                    $rec_link   = get_permalink( $rec_id );
                                    $rec_rating = floatval( get_post_meta( $rec_id, 'novel_rating', true ) );
                                    $rec_genres = wp_get_post_terms( $rec_id, 'genre', array( 'fields' => 'names' ) );
                                    $reason     = $rec['reason'];

                                    if ( empty( $rec_cover ) ) {
                                        $rec_cover = get_template_directory_uri() . '/assets/images/placeholder-cover.jpg';
                                    }
                                    $rec_rating_fa = $rec_rating > 0
                                        ? ( function_exists( 'novel_format_number' ) ? novel_format_number( $rec_rating, 1 ) : number_format( $rec_rating, 1 ) )
                                        : '—';
                                    ?>
                                    <div class="fp-recommendation__card">
                                        <a href="<?php echo esc_url( $rec_link ); ?>" class="fp-recommendation__cover-link">
                                            <img src="<?php echo esc_url( $rec_cover ); ?>"
                                                 alt="<?php echo esc_attr( $rec_title ); ?>"
                                                 class="fp-recommendation__cover"
                                                 loading="lazy" width="160" height="230" />
                                        </a>
                                        <div class="fp-recommendation__info">
                                            <h3 class="fp-recommendation__title">
                                                <a href="<?php echo esc_url( $rec_link ); ?>"><?php echo esc_html( $rec_title ); ?></a>
                                            </h3>
                                            <?php if ( ! empty( $rec_genres ) && ! is_wp_error( $rec_genres ) ) : ?>
                                                <span class="fp-recommendation__genres">
                                                    <?php echo esc_html( implode( '، ', array_slice( $rec_genres, 0, 2 ) ) ); ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ( $rec_rating > 0 ) : ?>
                                                <div class="fp-recommendation__rating">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                                    <span><?php echo esc_html( $rec_rating_fa ); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <p class="fp-recommendation__reason">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                                                <?php echo esc_html( $reason ); ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>
                    <?php
                endif;
                break;

            // ═══════════════════════════════════════
            // سکشن ۵: در حال ترند 🔥
            // ═══════════════════════════════════════
            case 'trending':
                $trending_cache = get_transient( 'novel_trending_home' );

                if ( false === $trending_cache ) {
                    $one_week_ago = date( 'Y-m-d H:i:s', strtotime( '-7 days' ) );

                    $trending_cache = $wpdb->get_results( $wpdb->prepare(
                        "SELECT p.ID as novel_id,
                                COALESCE(pm_views.meta_value, 0) * 0.5 +
                                COALESCE(comments_count.cnt, 0) * 0.3 +
                                COALESCE(follows_count.cnt, 0) * 0.2 AS trend_score
                         FROM {$wpdb->posts} p
                         LEFT JOIN {$wpdb->postmeta} pm_views
                            ON p.ID = pm_views.post_id AND pm_views.meta_key = 'novel_views_week'
                         LEFT JOIN (
                             SELECT pm_novel.meta_value AS novel_id, COUNT(*) AS cnt
                             FROM {$wpdb->comments} c
                             INNER JOIN {$wpdb->postmeta} pm_novel ON c.comment_post_ID = pm_novel.post_id
                             WHERE c.comment_approved = '1'
                             AND c.comment_date >= %s
                             AND pm_novel.meta_key = 'parent_novel'
                             GROUP BY pm_novel.meta_value
                         ) comments_count ON p.ID = comments_count.novel_id
                         LEFT JOIN (
                             SELECT followed_id AS novel_id, COUNT(*) AS cnt
                             FROM {$wpdb->prefix}novel_follows
                             WHERE follow_type = 'novel' AND created_at >= %s
                             GROUP BY followed_id
                         ) follows_count ON p.ID = follows_count.novel_id
                         WHERE p.post_type = 'novel' AND p.post_status = 'publish'
                         ORDER BY trend_score DESC
                         LIMIT 10",
                        $one_week_ago, $one_week_ago
                    ) );

                    // fallback
                    if ( empty( $trending_cache ) ) {
                        $fallback_q = new WP_Query( array(
                            'post_type'      => 'novel',
                            'posts_per_page' => 10,
                            'post_status'    => 'publish',
                            'meta_key'       => 'novel_views',
                            'orderby'        => 'meta_value_num',
                            'order'          => 'DESC',
                        ) );
                        $trending_cache = array();
                        if ( $fallback_q->have_posts() ) {
                            while ( $fallback_q->have_posts() ) {
                                $fallback_q->the_post();
                                $trending_cache[] = (object) array(
                                    'novel_id'    => get_the_ID(),
                                    'trend_score' => intval( get_post_meta( get_the_ID(), 'novel_views', true ) ),
                                );
                            }
                            wp_reset_postdata();
                        }
                    }

                    set_transient( 'novel_trending_home', $trending_cache, HOUR_IN_SECONDS );
                }

                if ( ! empty( $trending_cache ) ) :
                    ?>
                    <section class="fp-section fp-trending" data-section="trending"
                             aria-label="<?php esc_attr_e( 'در حال ترند', 'flavor-starter' ); ?>">
                        <div class="container">
                            <div class="fp-section__header">
                                <h2 class="fp-section__title">
                                    <span class="fp-section__icon-fire">🔥</span>
                                    <?php esc_html_e( 'در حال ترند', 'flavor-starter' ); ?>
                                </h2>
                                <a href="<?php echo esc_url( home_url( '/ranking/' ) ); ?>" class="fp-section__more">
                                    <?php esc_html_e( 'مشاهده همه', 'flavor-starter' ); ?>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                                </a>
                            </div>
                            <div class="fp-trending__slider">
                                <div class="fp-trending__track">
                                    <?php
                                    foreach ( $trending_cache as $trend_item ) :
                                        $t_novel_id = intval( $trend_item->novel_id );
                                        $t_post     = get_post( $t_novel_id );
                                        if ( ! $t_post || $t_post->post_status !== 'publish' ) continue;

                                        $GLOBALS['post'] = $t_post;
                                        setup_postdata( $t_post );
                                        get_template_part( 'templates/novel/novel-card' );
                                    endforeach;
                                    wp_reset_postdata();
                                    ?>
                                </div>
                            </div>
                        </div>
                    </section>
                    <?php
                endif;
                break;

            // ═══════════════════════════════════════
            // سکشن ۶: مسابقه فعال 🏆
            // ═══════════════════════════════════════
            case 'active_contest':
                if ( class_exists( 'Novel_Quiz' ) ) :
                    $active_quiz = $wpdb->get_row(
                        "SELECT * FROM {$wpdb->prefix}novel_quizzes
                         WHERE status = 'active' AND end_time > NOW()
                         ORDER BY created_at DESC LIMIT 1"
                    );

                    if ( $active_quiz ) :
                        $quiz_title     = esc_html( $active_quiz->title );
                        $quiz_id        = intval( $active_quiz->id );
                        $time_remaining = strtotime( $active_quiz->end_time ) - time();
                        $hours_left     = max( 0, floor( $time_remaining / 3600 ) );
                        $hours_left_fa  = function_exists( 'novel_format_number' ) ? novel_format_number( $hours_left ) : $hours_left;

                        $participants    = intval( $wpdb->get_var( $wpdb->prepare(
                            "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}novel_quiz_answers WHERE quiz_id = %d",
                            $quiz_id
                        ) ) );
                        $participants_fa = function_exists( 'novel_format_number' ) ? novel_format_number( $participants ) : $participants;
                        $quiz_link       = home_url( '/quiz/?id=' . $quiz_id );
                        ?>
                        <section class="fp-section fp-contest" data-section="active_contest"
                                 aria-label="<?php esc_attr_e( 'مسابقه فعال', 'flavor-starter' ); ?>">
                            <div class="container">
                                <div class="fp-contest__card">
                                    <div class="fp-contest__icon">🏆</div>
                                    <div class="fp-contest__info">
                                        <h3 class="fp-contest__title"><?php echo $quiz_title; ?></h3>
                                        <div class="fp-contest__meta">
                                            <span class="fp-contest__time">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                                <?php printf( esc_html__( '%s ساعت مانده', 'flavor-starter' ), $hours_left_fa ); ?>
                                            </span>
                                            <span class="fp-contest__participants">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                                                <?php printf( esc_html__( '%s نفر', 'flavor-starter' ), $participants_fa ); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <a href="<?php echo esc_url( $quiz_link ); ?>" class="btn btn--primary fp-contest__btn">
                                        🎯 <?php esc_html_e( 'شرکت کن!', 'flavor-starter' ); ?>
                                    </a>
                                </div>
                            </div>
                        </section>
                        <?php
                    endif;
                endif;
                break;

            // ═══════════════════════════════════════
            // سکشن ۷: آخرین به‌روزرسانی‌ها
            // ═══════════════════════════════════════
            case 'latest_updates':
                $updates_cache = get_transient( 'novel_latest_updates' );

                if ( false === $updates_cache ) {
                    $latest_chapters = new WP_Query( array(
                        'post_type'      => 'chapter',
                        'posts_per_page' => 15,
                        'post_status'    => 'publish',
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                    ) );

                    $updates_cache = array();
                    if ( $latest_chapters->have_posts() ) {
                        while ( $latest_chapters->have_posts() ) {
                            $latest_chapters->the_post();
                            $ch_id        = get_the_ID();
                            $parent_novel = intval( get_post_meta( $ch_id, 'parent_novel', true ) );
                            $ch_number    = intval( get_post_meta( $ch_id, 'chapter_number', true ) );
                            $ch_title     = get_the_title();
                            $ch_date      = get_the_date( 'Y-m-d H:i:s' );
                            $is_vip       = get_post_meta( $ch_id, 'is_vip', true );
                            $novel_type   = '';

                            if ( $parent_novel ) {
                                $type_terms = wp_get_post_terms( $parent_novel, 'novel_type', array( 'fields' => 'names' ) );
                                $novel_type = ! empty( $type_terms ) && ! is_wp_error( $type_terms ) ? $type_terms[0] : '';
                            }

                            $updates_cache[] = array(
                                'chapter_id'    => $ch_id,
                                'novel_id'      => $parent_novel,
                                'novel_title'   => $parent_novel ? get_the_title( $parent_novel ) : '',
                                'novel_link'    => $parent_novel ? get_permalink( $parent_novel ) : '#',
                                'chapter_num'   => $ch_number,
                                'chapter_title' => $ch_title,
                                'chapter_link'  => get_permalink( $ch_id ),
                                'date'          => $ch_date,
                                'timestamp'     => strtotime( $ch_date ),
                                'is_vip'        => ! empty( $is_vip ),
                                'novel_type'    => $novel_type,
                            );
                        }
                        wp_reset_postdata();
                    }

                    set_transient( 'novel_latest_updates', $updates_cache, 15 * MINUTE_IN_SECONDS );
                }

                if ( ! empty( $updates_cache ) ) :
                    $today_start     = strtotime( 'today midnight' );
                    $yesterday_start = strtotime( 'yesterday midnight' );

                    $groups = array(
                        'today'     => array( 'label' => __( 'امروز', 'flavor-starter' ), 'items' => array() ),
                        'yesterday' => array( 'label' => __( 'دیروز', 'flavor-starter' ), 'items' => array() ),
                        'older'     => array( 'label' => __( 'قبل‌تر', 'flavor-starter' ), 'items' => array() ),
                    );

                    foreach ( $updates_cache as $update ) {
                        if ( $update['timestamp'] >= $today_start ) {
                            $groups['today']['items'][] = $update;
                        } elseif ( $update['timestamp'] >= $yesterday_start ) {
                            $groups['yesterday']['items'][] = $update;
                        } else {
                            $groups['older']['items'][] = $update;
                        }
                    }
                    ?>
                    <section class="fp-section fp-updates" data-section="latest_updates"
                             aria-label="<?php esc_attr_e( 'آخرین به‌روزرسانی‌ها', 'flavor-starter' ); ?>">
                        <div class="container">
                            <div class="fp-section__header">
                                <h2 class="fp-section__title">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                                    <?php esc_html_e( 'آخرین به‌روزرسانی‌ها', 'flavor-starter' ); ?>
                                </h2>
                                <a href="<?php echo esc_url( get_post_type_archive_link( 'chapter' ) ); ?>" class="fp-section__more">
                                    <?php esc_html_e( 'همه به‌روزرسانی‌ها', 'flavor-starter' ); ?>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                                </a>
                            </div>
                            <div class="fp-updates__list">
                                <?php foreach ( $groups as $group_key => $group ) :
                                    if ( empty( $group['items'] ) ) continue;
                                    ?>
                                    <div class="fp-updates__group">
                                        <h3 class="fp-updates__group-title"><?php echo esc_html( $group['label'] ); ?></h3>
                                        <?php foreach ( $group['items'] as $upd ) :
                                            $ch_num_fa = function_exists( 'novel_format_number' ) ? novel_format_number( $upd['chapter_num'] ) : $upd['chapter_num'];
                                            $time_ago  = function_exists( 'novel_time_ago' ) ? novel_time_ago( $upd['timestamp'] ) : human_time_diff( $upd['timestamp'] ) . ' ' . __( 'پیش', 'flavor-starter' );
                                            ?>
                                            <div class="fp-updates__item">
                                                <div class="fp-updates__item-main">
                                                    <a href="<?php echo esc_url( $upd['novel_link'] ); ?>" class="fp-updates__novel-name">
                                                        <?php echo esc_html( $upd['novel_title'] ); ?>
                                                    </a>
                                                    <a href="<?php echo esc_url( $upd['chapter_link'] ); ?>" class="fp-updates__chapter">
                                                        <?php printf( esc_html__( 'قسمت %s', 'flavor-starter' ), $ch_num_fa ); ?>
                                                        <?php if ( ! empty( $upd['chapter_title'] ) && $upd['chapter_title'] !== $upd['novel_title'] ) : ?>
                                                            <span class="fp-updates__ch-title">: <?php echo esc_html( mb_substr( $upd['chapter_title'], 0, 40, 'UTF-8' ) ); ?></span>
                                                        <?php endif; ?>
                                                    </a>
                                                </div>
                                                <div class="fp-updates__item-meta">
                                                    <?php if ( ! empty( $upd['novel_type'] ) ) : ?>
                                                        <span class="fp-updates__type-badge"><?php echo esc_html( $upd['novel_type'] ); ?></span>
                                                    <?php endif; ?>
                                                    <?php if ( $upd['is_vip'] ) : ?>
                                                        <span class="fp-updates__vip-badge">
                                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                                                            VIP
                                                        </span>
                                                    <?php else : ?>
                                                        <span class="fp-updates__free-badge"><?php esc_html_e( 'رایگان', 'flavor-starter' ); ?></span>
                                                    <?php endif; ?>
                                                    <span class="fp-updates__time"><?php echo esc_html( $time_ago ); ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>
                    <?php
                endif;
                break;

            // ═══════════════════════════════════════
            // سکشن ۸: رمان‌های محبوب
            // ═══════════════════════════════════════
            case 'popular_novels':
                $popular_ids = get_transient( 'novel_popular_home' );

                if ( false === $popular_ids ) {
                    $popular_q = new WP_Query( array(
                        'post_type'      => 'novel',
                        'posts_per_page' => 12,
                        'post_status'    => 'publish',
                        'meta_key'       => 'novel_views_month',
                        'orderby'        => 'meta_value_num',
                        'order'          => 'DESC',
                    ) );

                    if ( ! $popular_q->have_posts() ) {
                        $popular_q = new WP_Query( array(
                            'post_type'      => 'novel',
                            'posts_per_page' => 12,
                            'post_status'    => 'publish',
                            'meta_key'       => 'novel_views',
                            'orderby'        => 'meta_value_num',
                            'order'          => 'DESC',
                        ) );
                    }

                    $popular_ids = array();
                    if ( $popular_q->have_posts() ) {
                        while ( $popular_q->have_posts() ) {
                            $popular_q->the_post();
                            $popular_ids[] = get_the_ID();
                        }
                        wp_reset_postdata();
                    }

                    set_transient( 'novel_popular_home', $popular_ids, HOUR_IN_SECONDS );
                }

                if ( ! empty( $popular_ids ) ) :
                    ?>
                    <section class="fp-section fp-popular" data-section="popular_novels"
                             aria-label="<?php esc_attr_e( 'رمان‌های محبوب', 'flavor-starter' ); ?>">
                        <div class="container">
                            <div class="fp-section__header">
                                <h2 class="fp-section__title">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
                                    <?php esc_html_e( 'رمان‌های محبوب', 'flavor-starter' ); ?>
                                </h2>
                                <a href="<?php echo esc_url( home_url( '/ranking/' ) ); ?>" class="fp-section__more">
                                    <?php esc_html_e( 'مشاهده همه', 'flavor-starter' ); ?>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                                </a>
                            </div>
                            <div class="fp-novels__grid fp-novels__grid--4col">
                                <?php
                                foreach ( $popular_ids as $pop_id ) :
                                    $pop_post = get_post( $pop_id );
                                    if ( ! $pop_post || $pop_post->post_status !== 'publish' ) continue;
                                    $GLOBALS['post'] = $pop_post;
                                    setup_postdata( $pop_post );
                                    get_template_part( 'templates/novel/novel-card' );
                                endforeach;
                                wp_reset_postdata();
                                ?>
                            </div>
                        </div>
                    </section>
                    <?php
                endif;
                break;

            // ═══════════════════════════════════════
            // سکشن ۹: تازه‌ترین رمان‌ها
            // ═══════════════════════════════════════
            case 'newest_novels':
                $newest_q = new WP_Query( array(
                    'post_type'      => 'novel',
                    'posts_per_page' => 8,
                    'post_status'    => 'publish',
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                ) );

                if ( $newest_q->have_posts() ) :
                    ?>
                    <section class="fp-section fp-newest" data-section="newest_novels"
                             aria-label="<?php esc_attr_e( 'تازه‌ترین رمان‌ها', 'flavor-starter' ); ?>">
                        <div class="container">
                            <div class="fp-section__header">
                                <h2 class="fp-section__title">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    <?php esc_html_e( 'تازه‌ترین رمان‌ها', 'flavor-starter' ); ?>
                                </h2>
                                <a href="<?php echo esc_url( get_post_type_archive_link( 'novel' ) ); ?>" class="fp-section__more">
                                    <?php esc_html_e( 'مشاهده همه', 'flavor-starter' ); ?>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                                </a>
                            </div>
                            <div class="fp-novels__grid fp-novels__grid--4col">
                                <?php
                                while ( $newest_q->have_posts() ) :
                                    $newest_q->the_post();
                                    get_template_part( 'templates/novel/novel-card' );
                                endwhile;
                                wp_reset_postdata();
                                ?>
                            </div>
                        </div>
                    </section>
                    <?php
                endif;
                break;

            // ═══════════════════════════════════════
            // بنر CTA ثبت‌نام (فقط مهمان)
            // ═══════════════════════════════════════
            case 'cta_register':
                if ( $is_logged_in ) break;
                ?>
                <section class="fp-section fp-cta" data-section="cta_register"
                         aria-label="<?php esc_attr_e( 'ثبت‌نام', 'flavor-starter' ); ?>">
                    <div class="container">
                        <div class="fp-cta__card">
                            <div class="fp-cta__content">
                                <div class="fp-cta__icon">🌟</div>
                                <h2 class="fp-cta__title"><?php esc_html_e( 'به دنیای داستان‌ها بپیوندید!', 'flavor-starter' ); ?></h2>
                                <p class="fp-cta__text">
                                    <?php esc_html_e( 'با عضویت رایگان، کتابخانه شخصی بسازید، در مسابقات شرکت کنید و سکه جمع کنید!', 'flavor-starter' ); ?>
                                </p>
                                <div class="fp-cta__actions">
                                    <a href="<?php echo esc_url( home_url( '/register/' ) ); ?>" class="btn btn--primary btn--lg fp-cta__register-btn">
                                        🚀 <?php esc_html_e( 'ثبت‌نام رایگان', 'flavor-starter' ); ?>
                                    </a>
                                    <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="btn btn--outline btn--lg fp-cta__login-btn">
                                        <?php esc_html_e( 'ورود', 'flavor-starter' ); ?>
                                    </a>
                                </div>
                            </div>
                            <div class="fp-cta__decoration" aria-hidden="true">
                                <div class="fp-cta__circle fp-cta__circle--1"></div>
                                <div class="fp-cta__circle fp-cta__circle--2"></div>
                                <div class="fp-cta__circle fp-cta__circle--3"></div>
                            </div>
                        </div>
                    </div>
                </section>
                <?php
                break;

            // ═══════════════════════════════════════
            // سکشن ۱۰: نویسندگان برتر
            // ═══════════════════════════════════════
            case 'top_authors':
                get_template_part( 'templates/authors/top-authors' );
                break;

            // ═══════════════════════════════════════
            // سکشن ۱۱: آخرین دیدگاه‌ها
            // ═══════════════════════════════════════
            case 'latest_comments':
                get_template_part( 'templates/comments/homepage-box' );
                break;

            // ═══════════════════════════════════════
            // سکشن ۱۲: نظرسنجی فعال
            // ═══════════════════════════════════════
            case 'active_poll':
                if ( class_exists( 'Novel_Polls' ) ) :
                    $active_poll = $wpdb->get_row(
                        "SELECT * FROM {$wpdb->prefix}novel_polls
                         WHERE status = 'active'
                         AND (end_date IS NULL OR end_date > NOW())
                         ORDER BY created_at DESC LIMIT 1"
                    );
                    if ( $active_poll ) :
                        set_query_var( 'poll_data', $active_poll );
                        get_template_part( 'templates/polls/poll-widget' );
                    endif;
                endif;
                break;

            // ═══════════════════════════════════════
            // سکشن ۱۳: حال‌وهوای من 🎭
            // ═══════════════════════════════════════
            case 'mood_filter':
                $moods = array(
                    array( 'key' => 'laugh',     'emoji' => '😂',  'label' => __( 'می‌خوام بخندم', 'flavor-starter' ),    'genres' => array( 'comedy', 'slice-of-life' ),     'tags' => array(),                         'color' => '#FFD93D' ),
                    array( 'key' => 'sad',       'emoji' => '😢',  'label' => __( 'غمگینم', 'flavor-starter' ),            'genres' => array( 'drama', 'romance' ),             'tags' => array( 'tragedy' ),              'color' => '#6C9BCF' ),
                    array( 'key' => 'romance',   'emoji' => '❤️', 'label' => __( 'عاشقانه می‌خوام', 'flavor-starter' ),   'genres' => array( 'romance', 'drama' ),             'tags' => array(),                         'color' => '#FF6B6B' ),
                    array( 'key' => 'adventure', 'emoji' => '🗺️', 'label' => __( 'حس ماجراجویی', 'flavor-starter' ),     'genres' => array( 'adventure', 'action' ),           'tags' => array( 'isekai' ),               'color' => '#4ECDC4' ),
                    array( 'key' => 'thrill',    'emoji' => '😱',  'label' => __( 'هیجان و ترس', 'flavor-starter' ),       'genres' => array( 'horror', 'thriller' ),            'tags' => array(),                         'color' => '#7B2D8E' ),
                    array( 'key' => 'brainy',    'emoji' => '🧠',  'label' => __( 'چالش ذهنی', 'flavor-starter' ),         'genres' => array( 'mystery', 'psychological' ),      'tags' => array( 'mind-game' ),            'color' => '#45B7D1' ),
                    array( 'key' => 'action',    'emoji' => '⚔️', 'label' => __( 'اکشن محض', 'flavor-starter' ),          'genres' => array( 'action', 'martial-arts' ),        'tags' => array( 'op-mc', 'battles' ),     'color' => '#F7931E' ),
                    array( 'key' => 'calm',      'emoji' => '☮️', 'label' => __( 'آرامش', 'flavor-starter' ),             'genres' => array( 'slice-of-life' ),                 'tags' => array( 'healing' ),              'color' => '#A8E6CF' ),
                );
                ?>
                <section class="fp-section fp-mood" data-section="mood_filter"
                         aria-label="<?php esc_attr_e( 'حال‌وهوای من', 'flavor-starter' ); ?>">
                    <div class="container">
                        <div class="fp-section__header">
                            <h2 class="fp-section__title">
                                🎭 <?php esc_html_e( 'الان چه حالی داری؟', 'flavor-starter' ); ?>
                            </h2>
                        </div>
                        <div class="fp-mood__grid">
                            <?php foreach ( $moods as $mood ) :
                                $search_params = array();
                                if ( ! empty( $mood['genres'] ) ) {
                                    $search_params['genre'] = implode( ',', $mood['genres'] );
                                }
                                if ( ! empty( $mood['tags'] ) ) {
                                    $search_params['tag'] = implode( ',', $mood['tags'] );
                                }
                                $mood_url = add_query_arg( $search_params, home_url( '/advanced-search/' ) );
                                ?>
                                <a href="<?php echo esc_url( $mood_url ); ?>"
                                   class="fp-mood__card"
                                   data-mood="<?php echo esc_attr( $mood['key'] ); ?>"
                                   style="--mood-color: <?php echo esc_attr( $mood['color'] ); ?>;">
                                    <span class="fp-mood__emoji"><?php echo $mood['emoji']; ?></span>
                                    <span class="fp-mood__label"><?php echo esc_html( $mood['label'] ); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <div class="fp-mood__results" id="fp-mood-results" style="display:none;">
                            <div class="fp-mood__results-header">
                                <h3 class="fp-mood__results-title"></h3>
                                <button class="fp-mood__results-close" aria-label="<?php esc_attr_e( 'بستن', 'flavor-starter' ); ?>">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                </button>
                            </div>
                            <div class="fp-mood__results-grid"></div>
                        </div>
                    </div>
                </section>
                <?php
                break;

        endswitch;
    endforeach;
    ?>
</main>

<?php get_footer(); ?>