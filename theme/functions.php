<?php
/**
 * فایل اصلی توابع قالب فلیور نوول
 * Flavor Novel Theme Functions
 *
 * @package Flavor_Novel
 * @version 1.0.0
 */

// جلوگیری از دسترسی مستقیم
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ===== ثابت‌های قالب ===== */
define( 'FN_VERSION', '1.0.0' );
define( 'FN_DIR', get_template_directory() );
define( 'FN_URI', get_template_directory_uri() );
define( 'FN_INC', FN_DIR . '/inc' );

/* ===== بارگذاری فایل‌های ماژولار ===== */
require_once FN_INC . '/custom-post-types.php';   // پست تایپ رمان و فصل
require_once FN_INC . '/taxonomies.php';           // تکسونومی‌ها (ژانر، تگ، وضعیت)
require_once FN_INC . '/meta-boxes.php';           // متاباکس‌ها و فیلدهای سفارشی
require_once FN_INC . '/ajax-handlers.php';        // هندلرهای AJAX
require_once FN_INC . '/bookmark-system.php';      // سیستم بوکمارک
require_once FN_INC . '/reading-progress.php';     // پیشرفت خواندن
require_once FN_INC . '/rating-system.php';        // امتیازدهی ستاره‌ای
require_once FN_INC . '/view-counter.php';         // شمارنده بازدید
require_once FN_INC . '/breadcrumbs.php';          // مسیر نمایش (Breadcrumb)
require_once FN_INC . '/theme-settings.php';       // تنظیمات قالب (Customizer)
require_once FN_INC . '/rcp-integration.php';      // سازگاری با RCP
require_once FN_INC . '/volume-system.php';        // سیستم جلدبندی
require_once FN_INC . '/comment-votes.php';        // سیستم لایک/دیسلایک نظرات

/* ===== تنظیمات پایه قالب ===== */
function flavor_novel_setup() {
    // پشتیبانی از ترجمه
    load_theme_textdomain( 'flavor-novel', FN_DIR . '/languages' );

    // پشتیبانی از قابلیت‌های وردپرس
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ) );
    add_theme_support( 'custom-logo', array(
        'width'       => 200,
        'height'      => 60,
        'flex-width'  => true,
        'flex-height' => true,
    ) );
    add_theme_support( 'automatic-feed-links' );

    // سایزهای سفارشی تصویر
    add_image_size( 'novel-cover', 300, 400, true );         // کاور رمان
    add_image_size( 'novel-cover-sm', 150, 200, true );      // کاور کوچک
    add_image_size( 'novel-hero', 800, 400, true );          // اسلایدر
    add_image_size( 'novel-card', 240, 320, true );          // کارت رمان

    // ثبت فهرست‌های ناوبری
    register_nav_menus( array(
        'primary'   => __( 'منوی اصلی', 'flavor-novel' ),
        'footer'    => __( 'منوی فوتر', 'flavor-novel' ),
        'mobile'    => __( 'منوی موبایل', 'flavor-novel' ),
    ) );
}
add_action( 'after_setup_theme', 'flavor_novel_setup' );

/* ===== بارگذاری استایل‌ها و اسکریپت‌ها ===== */
function flavor_novel_enqueue_assets() {
    // --- استایل‌ها ---

    // فونت وزیرمتن از CDN
    wp_enqueue_style(
        'vazirmatn-font',
        'https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css',
        array(),
        '33.003'
    );

    // استایل اصلی قالب (style.css)
    wp_enqueue_style(
        'flavor-novel-style',
        get_stylesheet_uri(),
        array( 'vazirmatn-font' ),
        FN_VERSION
    );

    // استایل ریدر (فقط در صفحه فصل)
    if ( is_singular( 'chapter' ) ) {
        wp_enqueue_style(
            'flavor-novel-reader',
            FN_URI . '/assets/css/reader.css',
            array( 'flavor-novel-style' ),
            FN_VERSION
        );
    }

    // --- اسکریپت‌ها ---

    // اسکریپت اصلی
    wp_enqueue_script(
        'flavor-novel-main',
        FN_URI . '/assets/js/main.js',
        array(),
        FN_VERSION,
        true // بارگذاری در فوتر
    );

    // ارسال داده‌های PHP به جاوااسکریپت
    wp_localize_script( 'flavor-novel-main', 'flavor_novel', array(
        'ajax_url'   => admin_url( 'admin-ajax.php' ),
        'rest_url'   => esc_url_raw( rest_url( 'flavor-novel/v1/' ) ),
        'nonce'      => wp_create_nonce( 'flavor_novel_nonce' ),
        'rest_nonce' => wp_create_nonce( 'wp_rest' ),
        'is_user_logged_in' => is_user_logged_in(),
        'user_id'    => get_current_user_id(),
        'home_url'   => home_url( '/' ),
        'i18n'       => array(
            'bookmark_added'   => __( 'به کتابخانه اضافه شد', 'flavor-novel' ),
            'bookmark_removed' => __( 'از کتابخانه حذف شد', 'flavor-novel' ),
            'rate_success'     => __( 'امتیاز شما ثبت شد', 'flavor-novel' ),
            'rate_error'       => __( 'خطا در ثبت امتیاز', 'flavor-novel' ),
            'loading'          => __( 'در حال بارگذاری...', 'flavor-novel' ),
            'error'            => __( 'خطایی رخ داد', 'flavor-novel' ),
            'confirm_remove'   => __( 'آیا مطمئنید؟', 'flavor-novel' ),
            'login_required'   => __( 'لطفاً ابتدا وارد شوید', 'flavor-novel' ),
        ),
    ) );

    // اسکریپت ریدر (فقط در صفحه فصل)
    if ( is_singular( 'chapter' ) ) {
        wp_enqueue_script(
            'flavor-novel-reader',
            FN_URI . '/assets/js/reader.js',
            array( 'flavor-novel-main' ),
            FN_VERSION,
            true
        );
    }

    // اسکریپت بوکمارک
    if ( is_user_logged_in() ) {
        wp_enqueue_script(
            'flavor-novel-bookmark',
            FN_URI . '/assets/js/bookmark.js',
            array( 'flavor-novel-main' ),
            FN_VERSION,
            true
        );
    }

    // اسکریپت فیلتر (در صفحه آرشیو)
    if ( is_post_type_archive( 'novel' ) || is_tax( 'genre' ) ) {
        wp_enqueue_script(
            'flavor-novel-filter',
            FN_URI . '/assets/js/filter.js',
            array( 'flavor-novel-main' ),
            FN_VERSION,
            true
        );
    }
}
add_action( 'wp_enqueue_scripts', 'flavor_novel_enqueue_assets' );

/* ===== ساخت جداول سفارشی دیتابیس ===== */
function flavor_novel_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // جدول بوکمارک‌ها
    $table_bookmarks = $wpdb->prefix . 'fn_bookmarks';
    $sql_bookmarks = "CREATE TABLE IF NOT EXISTS $table_bookmarks (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        novel_id BIGINT(20) UNSIGNED NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY user_novel (user_id, novel_id),
        KEY novel_id (novel_id)
    ) $charset_collate;";

    // جدول امتیازها
    $table_ratings = $wpdb->prefix . 'fn_ratings';
    $sql_ratings = "CREATE TABLE IF NOT EXISTS $table_ratings (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        novel_id BIGINT(20) UNSIGNED NOT NULL,
        rating TINYINT(1) UNSIGNED NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY user_novel (user_id, novel_id),
        KEY novel_id (novel_id)
    ) $charset_collate;";

    // جدول پیشرفت خواندن
    $table_progress = $wpdb->prefix . 'fn_reading_progress';
    $sql_progress = "CREATE TABLE IF NOT EXISTS $table_progress (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        novel_id BIGINT(20) UNSIGNED NOT NULL,
        chapter_id BIGINT(20) UNSIGNED NOT NULL,
        scroll_position FLOAT DEFAULT 0,
        last_read DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY user_novel (user_id, novel_id),
        KEY chapter_id (chapter_id)
    ) $charset_collate;";

    // جدول بازدید
    $table_views = $wpdb->prefix . 'fn_views';
    $sql_views = "CREATE TABLE IF NOT EXISTS $table_views (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id BIGINT(20) UNSIGNED NOT NULL,
        view_date DATE NOT NULL,
        view_count INT UNSIGNED DEFAULT 1,
        PRIMARY KEY (id),
        UNIQUE KEY post_date (post_id, view_date),
        KEY post_id (post_id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql_bookmarks );
    dbDelta( $sql_ratings );
    dbDelta( $sql_progress );
    dbDelta( $sql_views );

    // ثبت CPT ها و بازنویسی Rewrite Rules
    flavor_novel_register_post_types();
    flavor_novel_register_taxonomies();
    flush_rewrite_rules();

    // اعطای قابلیت‌های نویسندگی به نقش‌های نویسنده و مشارک
    fn_grant_author_capabilities();
}
add_action( 'after_switch_theme', 'flavor_novel_activate' );

/* ===== ثبت Sidebar/Widget Area ===== */
function flavor_novel_widgets_init() {
    register_sidebar( array(
        'name'          => __( 'سایدبار رمان', 'flavor-novel' ),
        'id'            => 'novel-sidebar',
        'description'   => __( 'ویجت‌های سایدبار صفحه رمان', 'flavor-novel' ),
        'before_widget' => '<div id="%1$s" class="fn-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="fn-widget__title">',
        'after_title'   => '</h3>',
    ) );

    register_sidebar( array(
        'name'          => __( 'سایدبار صفحه اصلی', 'flavor-novel' ),
        'id'            => 'home-sidebar',
        'description'   => __( 'ویجت‌های صفحه اصلی', 'flavor-novel' ),
        'before_widget' => '<div id="%1$s" class="fn-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="fn-widget__title">',
        'after_title'   => '</h3>',
    ) );
}
add_action( 'widgets_init', 'flavor_novel_widgets_init' );

/* ===== ثبت REST API Endpoints ===== */
function flavor_novel_register_rest_routes() {

    // دریافت فصل‌های یک رمان
    register_rest_route( 'flavor-novel/v1', '/chapters/(?P<novel_id>\d+)', array(
        'methods'  => 'GET',
        'callback' => 'flavor_novel_get_chapters',
        'permission_callback' => '__return_true',
        'args'     => array(
            'novel_id' => array(
                'required'          => true,
                'validate_callback' => function( $param ) {
                    return is_numeric( $param );
                },
            ),
            'page' => array(
                'default'           => 1,
                'validate_callback' => function( $param ) {
                    return is_numeric( $param );
                },
            ),
            'per_page' => array(
                'default'           => 50,
                'validate_callback' => function( $param ) {
                    return is_numeric( $param ) && $param <= 100;
                },
            ),
            'order' => array(
                'default' => 'ASC',
                'enum'    => array( 'ASC', 'DESC' ),
            ),
        ),
    ) );

    // دریافت رمان‌ها با فیلتر
    register_rest_route( 'flavor-novel/v1', '/novels', array(
        'methods'  => 'GET',
        'callback' => 'flavor_novel_get_novels',
        'permission_callback' => '__return_true',
    ) );
}
add_action( 'rest_api_init', 'flavor_novel_register_rest_routes' );

/**
 * کالبک REST: دریافت فصل‌های رمان
 */
function flavor_novel_get_chapters( WP_REST_Request $request ) {
    $novel_id = absint( $request['novel_id'] );
    $page     = absint( $request->get_param( 'page' ) );
    $per_page = absint( $request->get_param( 'per_page' ) );
    $order    = sanitize_text_field( $request->get_param( 'order' ) );

    $args = array(
        'post_type'      => 'chapter',
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'orderby'        => 'meta_value_num',
        'meta_key'       => '_fn_chapter_number',
        'order'          => $order,
        'meta_query'     => array(
            array(
                'key'   => '_fn_parent_novel',
                'value' => $novel_id,
                'type'  => 'NUMERIC',
            ),
        ),
    );

    $query    = new WP_Query( $args );
    $chapters = array();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $chapters[] = array(
                'id'             => get_the_ID(),
                'title'          => get_the_title(),
                'chapter_number' => get_post_meta( get_the_ID(), '_fn_chapter_number', true ),
                'url'            => get_permalink(),
                'date'           => get_the_date( 'Y/m/d' ),
                'date_human'     => human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ) . ' پیش',
            );
        }
        wp_reset_postdata();
    }

    return new WP_REST_Response( array(
        'chapters'    => $chapters,
        'total'       => $query->found_posts,
        'total_pages' => $query->max_num_pages,
        'current_page' => $page,
    ), 200 );
}

/**
 * کالبک REST: دریافت رمان‌ها با فیلتر
 */
function flavor_novel_get_novels( WP_REST_Request $request ) {
    $page     = absint( $request->get_param( 'page' ) ?: 1 );
    $per_page = absint( $request->get_param( 'per_page' ) ?: 12 );
    $orderby  = sanitize_text_field( $request->get_param( 'orderby' ) ?: 'date' );
    $order    = sanitize_text_field( $request->get_param( 'order' ) ?: 'DESC' );
    $genre    = sanitize_text_field( $request->get_param( 'genre' ) );
    $status   = sanitize_text_field( $request->get_param( 'status' ) );
    $search   = sanitize_text_field( $request->get_param( 'search' ) );

    $args = array(
        'post_type'      => 'novel',
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'orderby'        => $orderby,
        'order'          => $order,
        'post_status'    => 'publish',
    );

    // فیلتر جستجو
    if ( ! empty( $search ) ) {
        $args['s'] = $search;
    }

    // فیلتر تکسونومی
    $tax_query = array();

    if ( ! empty( $genre ) ) {
        $tax_query[] = array(
            'taxonomy' => 'genre',
            'field'    => 'slug',
            'terms'    => $genre,
        );
    }

    if ( ! empty( $status ) ) {
        $tax_query[] = array(
            'taxonomy' => 'novel_status',
            'field'    => 'slug',
            'terms'    => $status,
        );
    }

    if ( ! empty( $tax_query ) ) {
        $args['tax_query'] = $tax_query;
    }

    // مرتب‌سازی بر اساس محبوبیت
    if ( $orderby === 'views' ) {
        $args['orderby']  = 'meta_value_num';
        $args['meta_key'] = '_fn_total_views';
    } elseif ( $orderby === 'rating' ) {
        $args['orderby']  = 'meta_value_num';
        $args['meta_key'] = '_fn_avg_rating';
    } elseif ( $orderby === 'chapters' ) {
        $args['orderby']  = 'meta_value_num';
        $args['meta_key'] = '_fn_chapter_count';
    }

    $query  = new WP_Query( $args );
    $novels = array();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $novel_id = get_the_ID();

            $novels[] = array(
                'id'            => $novel_id,
                'title'         => get_the_title(),
                'url'           => get_permalink(),
                'cover'         => get_the_post_thumbnail_url( $novel_id, 'novel-card' ),
                'synopsis'      => wp_trim_words( get_post_meta( $novel_id, '_fn_synopsis', true ), 20 ),
                'genres'        => wp_get_post_terms( $novel_id, 'genre', array( 'fields' => 'names' ) ),
                'status'        => wp_get_post_terms( $novel_id, 'novel_status', array( 'fields' => 'names' ) ),
                'chapter_count' => absint( get_post_meta( $novel_id, '_fn_chapter_count', true ) ),
                'avg_rating'    => floatval( get_post_meta( $novel_id, '_fn_avg_rating', true ) ),
                'total_views'   => absint( get_post_meta( $novel_id, '_fn_total_views', true ) ),
            );
        }
        wp_reset_postdata();
    }

    return new WP_REST_Response( array(
        'novels'       => $novels,
        'total'        => $query->found_posts,
        'total_pages'  => $query->max_num_pages,
        'current_page' => $page,
    ), 200 );
}

/* ===== توابع کمکی (Helper Functions) ===== */

/**
 * دریافت تعداد فصل‌های یک رمان
 */
function fn_get_chapter_count( $novel_id ) {
    $count = get_post_meta( $novel_id, '_fn_chapter_count', true );
    if ( ! $count ) {
        $count = fn_update_chapter_count( $novel_id );
    }
    return absint( $count );
}

/**
 * بروزرسانی تعداد فصل‌ها (هنگام انتشار فصل جدید)
 */
function fn_update_chapter_count( $novel_id ) {
    $count = new WP_Query( array(
        'post_type'      => 'chapter',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => array(
            array(
                'key'   => '_fn_parent_novel',
                'value' => $novel_id,
                'type'  => 'NUMERIC',
            ),
        ),
    ) );

    $total = $count->found_posts;
    update_post_meta( $novel_id, '_fn_chapter_count', $total );
    return $total;
}

// بروزرسانی خودکار تعداد فصل‌ها هنگام ذخیره فصل
function fn_on_chapter_save( $post_id, $post ) {
    if ( $post->post_type !== 'chapter' ) return;
    if ( wp_is_post_revision( $post_id ) ) return;

    $novel_id = get_post_meta( $post_id, '_fn_parent_novel', true );
    if ( $novel_id ) {
        fn_update_chapter_count( $novel_id );
    }
}
add_action( 'save_post', 'fn_on_chapter_save', 20, 2 );

/**
 * دریافت آخرین فصل منتشرشده رمان
 */
function fn_get_latest_chapter( $novel_id ) {
    $chapters = get_posts( array(
        'post_type'      => 'chapter',
        'posts_per_page' => 1,
        'orderby'        => 'meta_value_num',
        'meta_key'       => '_fn_chapter_number',
        'order'          => 'DESC',
        'meta_query'     => array(
            array(
                'key'   => '_fn_parent_novel',
                'value' => $novel_id,
                'type'  => 'NUMERIC',
            ),
        ),
    ) );

    return ! empty( $chapters ) ? $chapters[0] : null;
}

/**
 * دریافت اولین فصل رمان
 */
function fn_get_first_chapter( $novel_id ) {
    $chapters = get_posts( array(
        'post_type'      => 'chapter',
        'posts_per_page' => 1,
        'orderby'        => 'meta_value_num',
        'meta_key'       => '_fn_chapter_number',
        'order'          => 'ASC',
        'meta_query'     => array(
            array(
                'key'   => '_fn_parent_novel',
                'value' => $novel_id,
                'type'  => 'NUMERIC',
            ),
        ),
    ) );

    return ! empty( $chapters ) ? $chapters[0] : null;
}

/**
 * دریافت فصل بعدی/قبلی — بازنویسی کامل
 *
 * @param int    $chapter_id  آیدی فصل فعلی
 * @param string $direction   'next' یا 'prev'
 * @return WP_Post|null
 */
function fn_get_adjacent_chapter( $chapter_id, $direction = 'next' ) {
    $novel_id       = get_post_meta( $chapter_id, '_fn_parent_novel', true );
    $chapter_number = floatval( get_post_meta( $chapter_id, '_fn_chapter_number', true ) );

    if ( ! $novel_id ) return null;

    global $wpdb;

    if ( $direction === 'next' ) {
        // فصل بعدی: شماره بزرگ‌تر از فعلی، کوچک‌ترین
        $result_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT p.ID
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm_novel ON p.ID = pm_novel.post_id
                AND pm_novel.meta_key = '_fn_parent_novel'
             INNER JOIN {$wpdb->postmeta} pm_num ON p.ID = pm_num.post_id
                AND pm_num.meta_key = '_fn_chapter_number'
             WHERE p.post_type = 'chapter'
               AND p.post_status = 'publish'
               AND pm_novel.meta_value = %s
               AND CAST(pm_num.meta_value AS DECIMAL(10,2)) > %f
               AND p.ID != %d
             ORDER BY CAST(pm_num.meta_value AS DECIMAL(10,2)) ASC
             LIMIT 1",
            strval( $novel_id ),
            $chapter_number,
            $chapter_id
        ) );
    } else {
        // فصل قبلی: شماره کوچک‌تر از فعلی، بزرگ‌ترین
        $result_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT p.ID
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm_novel ON p.ID = pm_novel.post_id
                AND pm_novel.meta_key = '_fn_parent_novel'
             INNER JOIN {$wpdb->postmeta} pm_num ON p.ID = pm_num.post_id
                AND pm_num.meta_key = '_fn_chapter_number'
             WHERE p.post_type = 'chapter'
               AND p.post_status = 'publish'
               AND pm_novel.meta_value = %s
               AND CAST(pm_num.meta_value AS DECIMAL(10,2)) < %f
               AND p.ID != %d
             ORDER BY CAST(pm_num.meta_value AS DECIMAL(10,2)) DESC
             LIMIT 1",
            strval( $novel_id ),
            $chapter_number,
            $chapter_id
        ) );
    }

    return $result_id ? get_post( intval( $result_id ) ) : null;
}

/**
 * فرمت عدد فارسی
 */
function fn_format_number( $number ) {
    if ( $number >= 1000000 ) {
        return round( $number / 1000000, 1 ) . 'M';
    } elseif ( $number >= 1000 ) {
        return round( $number / 1000, 1 ) . 'K';
    }
    return number_format( $number );
}

/**
 * تبدیل اعداد به فارسی
 */
function fn_persian_number( $string ) {
    $persian = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
    $latin   = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
    return str_replace( $latin, $persian, $string );
}

/**
 * دریافت زمان نسبی فارسی
 */
function fn_time_ago( $timestamp ) {
    $diff = current_time( 'timestamp' ) - $timestamp;

    if ( $diff < 60 ) {
        return 'لحظاتی پیش';
    } elseif ( $diff < 3600 ) {
        return fn_persian_number( floor( $diff / 60 ) ) . ' دقیقه پیش';
    } elseif ( $diff < 86400 ) {
        return fn_persian_number( floor( $diff / 3600 ) ) . ' ساعت پیش';
    } elseif ( $diff < 604800 ) {
        return fn_persian_number( floor( $diff / 86400 ) ) . ' روز پیش';
    } elseif ( $diff < 2592000 ) {
        return fn_persian_number( floor( $diff / 604800 ) ) . ' هفته پیش';
    } else {
        return fn_persian_number( date_i18n( 'Y/m/d', $timestamp ) );
    }
}

/* ===== اضافه کردن Schema Markup ===== */
function flavor_novel_schema_markup() {
    if ( is_singular( 'novel' ) ) {
        $novel_id    = get_the_ID();
        $title       = get_the_title();
        $synopsis    = get_post_meta( $novel_id, '_fn_synopsis', true );
        $cover       = get_the_post_thumbnail_url( $novel_id, 'full' );
        $author_name = get_post_meta( $novel_id, '_fn_original_author', true );
        $avg_rating  = floatval( get_post_meta( $novel_id, '_fn_avg_rating', true ) );
        $rating_count= absint( get_post_meta( $novel_id, '_fn_rating_count', true ) );

        $schema = array(
            '@context'    => 'https://schema.org',
            '@type'       => 'Book',
            'name'        => $title,
            'description' => wp_strip_all_tags( $synopsis ),
            'image'       => $cover,
            'author'      => array(
                '@type' => 'Person',
                'name'  => $author_name ?: get_the_author(),
            ),
            'url'         => get_permalink(),
            'inLanguage'  => 'fa',
        );

        if ( $avg_rating > 0 && $rating_count > 0 ) {
            $schema['aggregateRating'] = array(
                '@type'       => 'AggregateRating',
                'ratingValue' => $avg_rating,
                'bestRating'  => 5,
                'ratingCount' => $rating_count,
            );
        }

        echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
    }

    if ( is_singular( 'chapter' ) ) {
        $chapter_id  = get_the_ID();
        $novel_id    = get_post_meta( $chapter_id, '_fn_parent_novel', true );
        $novel_title = get_the_title( $novel_id );

        $schema = array(
            '@context' => 'https://schema.org',
            '@type'    => 'Chapter',
            'name'     => get_the_title(),
            'isPartOf' => array(
                '@type' => 'Book',
                'name'  => $novel_title,
                'url'   => get_permalink( $novel_id ),
            ),
            'position' => absint( get_post_meta( $chapter_id, '_fn_chapter_number', true ) ),
            'url'      => get_permalink(),
        );

        echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
    }
}
add_action( 'wp_head', 'flavor_novel_schema_markup' );

/* ===== Open Graph Tags ===== */
function flavor_novel_open_graph() {
    if ( is_singular( 'novel' ) ) {
        $novel_id = get_the_ID();
        $title    = get_the_title();
        $synopsis = wp_strip_all_tags( get_post_meta( $novel_id, '_fn_synopsis', true ) );
        $cover    = get_the_post_thumbnail_url( $novel_id, 'novel-hero' );

        echo '<meta property="og:type" content="book" />' . "\n";
        echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
        echo '<meta property="og:description" content="' . esc_attr( wp_trim_words( $synopsis, 30 ) ) . '" />' . "\n";
        echo '<meta property="og:image" content="' . esc_url( $cover ) . '" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url( get_permalink() ) . '" />' . "\n";
    }
}
add_action( 'wp_head', 'flavor_novel_open_graph' );

/* ===== غیرفعال‌سازی ایموجی وردپرس (بهبود عملکرد) ===== */
function flavor_novel_disable_emojis() {
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'flavor_novel_disable_emojis' );

/* ===== حذف نسخه وردپرس از هدر (امنیت) ===== */
remove_action( 'wp_head', 'wp_generator' );

/* ===== افزودن کلاس‌های سفارشی به body ===== */
function flavor_novel_body_classes( $classes ) {
    if ( is_singular( 'chapter' ) ) {
        $classes[] = 'fn-reading-mode';
    }
    if ( is_singular( 'novel' ) ) {
        $classes[] = 'fn-novel-page';
    }
    if ( is_post_type_archive( 'novel' ) ) {
        $classes[] = 'fn-library-page';
    }
    return $classes;
}
add_filter( 'body_class', 'flavor_novel_body_classes' );

/* ===== تنظیم تعداد پست در آرشیو رمان ===== */
function flavor_novel_archive_posts_per_page( $query ) {
    if ( ! is_admin() && $query->is_main_query() ) {
        if ( is_post_type_archive( 'novel' ) || is_tax( 'genre' ) || is_tax( 'novel_status' ) ) {
            $query->set( 'posts_per_page', 24 );
        }
    }
}
add_action( 'pre_get_posts', 'flavor_novel_archive_posts_per_page' );

















/**
 * Walker سفارشی منوها
 * اضافه کنید به functions.php یا فایل inc/nav-walkers.php
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Walker منوی اصلی هدر
 */
class FN_Nav_Walker extends Walker_Nav_Menu {

    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        $classes = empty( $item->classes ) ? array() : (array) $item->classes;
        $active_class = in_array( 'current-menu-item', $classes ) ? ' active' : '';

        $output .= '<a href="' . esc_url( $item->url ) . '" class="fn-nav__link' . $active_class . '">';
        $output .= esc_html( $item->title );
    }

    public function end_el( &$output, $item, $depth = 0, $args = null ) {
        $output .= '</a>';
    }
}

/**
 * Walker منوی فوتر
 */
class FN_Footer_Nav_Walker extends Walker_Nav_Menu {

    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        $output .= '<a href="' . esc_url( $item->url ) . '" class="fn-footer__link">';
        $output .= esc_html( $item->title );
    }

    public function end_el( &$output, $item, $depth = 0, $args = null ) {
        $output .= '</a>';
    }
}

/**
 * آیکون‌های شبکه‌های اجتماعی
 */
function fn_get_social_icon( $platform ) {
    $icons = array(
        'telegram'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>',
        'instagram' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>',
        'twitter'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
        'discord'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.3698a19.7913 19.7913 0 00-4.8851-1.5152.0741.0741 0 00-.0785.0371c-.211.3753-.4447.8648-.6083 1.2495-1.8447-.2762-3.68-.2762-5.4868 0-.1636-.3933-.4058-.8742-.6177-1.2495a.077.077 0 00-.0785-.037 19.7363 19.7363 0 00-4.8852 1.515.0699.0699 0 00-.0321.0277C.5334 9.0458-.319 13.5799.0992 18.0578a.0824.0824 0 00.0312.0561c2.0528 1.5076 4.0413 2.4228 5.9929 3.0294a.0777.0777 0 00.0842-.0276c.4616-.6304.8731-1.2952 1.226-1.9942a.076.076 0 00-.0416-.1057c-.6528-.2476-1.2743-.5495-1.8722-.8923a.077.077 0 01-.0076-.1277c.1258-.0943.2517-.1923.3718-.2914a.0743.0743 0 01.0776-.0105c3.9278 1.7933 8.18 1.7933 12.0614 0a.0739.0739 0 01.0785.0095c.1202.099.246.1981.3728.2924a.077.077 0 01-.0066.1276 12.2986 12.2986 0 01-1.873.8914.0766.0766 0 00-.0407.1067c.3604.698.7719 1.3628 1.225 1.9932a.076.076 0 00.0842.0286c1.961-.6067 3.9495-1.5219 6.0023-3.0294a.077.077 0 00.0313-.0552c.5004-5.177-.8382-9.6739-3.5485-13.6604a.061.061 0 00-.0312-.0286zM8.02 15.3312c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9555-2.4189 2.157-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.9555 2.4189-2.1569 2.4189zm7.9748 0c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9554-2.4189 2.1569-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.946 2.4189-2.1568 2.4189z"/></svg>',
    );

    return isset( $icons[ $platform ] ) ? $icons[ $platform ] : '';
}

/**
 * اعطای قابلیت‌های نویسندگی به نقش‌های نویسنده و مشارک
 * اجازه می‌دهد نویسندگان رمان‌ها و فصل‌ها را ایجاد کنند
 */
function fn_grant_author_capabilities() {
    $author_role = get_role( 'author' );
    $contributor_role = get_role( 'contributor' );

    if ( ! $author_role && ! $contributor_role ) {
        return;
    }

    // قابلیت‌های برای رمان (Novel)
    $novel_caps = array(
        'edit_novels',
        'edit_others_novels',
        'publish_novels',
        'read_private_novels',
        'delete_novels',
        'delete_others_novels',
        'edit_published_novels',
        'delete_published_novels',
    );

    // قابلیت‌های برای فصل (Chapter)
    $chapter_caps = array(
        'edit_chapters',
        'edit_others_chapters',
        'publish_chapters',
        'read_private_chapters',
        'delete_chapters',
        'delete_others_chapters',
        'edit_published_chapters',
        'delete_published_chapters',
    );

    // اعطای قابلیت‌ها به نویسندگان
    if ( $author_role ) {
        foreach ( array_merge( $novel_caps, $chapter_caps ) as $cap ) {
            $author_role->add_cap( $cap );
        }
        
        // اجازه آپلود فایل
        $author_role->add_cap( 'upload_files' );
    }

    // اعطای بخش‌ای از قابلیت‌ها به مشارکین
    if ( $contributor_role ) {
        $contrib_caps = array(
            'edit_chapters',
            'edit_others_chapters',
            'edit_published_chapters',
            'delete_chapters',
            'delete_published_chapters',
        );

        foreach ( $contrib_caps as $cap ) {
            $contributor_role->add_cap( $cap );
        }

        $contributor_role->add_cap( 'upload_files' );
    }
}

/* ===== تغییر خودکار وضعیت به «رها شده» ===== */
function fn_auto_mark_dropped() {
    $six_months_ago = date( 'Y-m-d H:i:s', strtotime( '-6 months' ) );

    $stale = new WP_Query( array(
        'post_type'      => 'novel',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'date_query'     => array(
            array( 'column' => 'post_modified', 'before' => $six_months_ago ),
        ),
        'author__not_in' => array( 1 ), // حذف ادمین اصلی
        'tax_query'      => array(
            array( 'taxonomy' => 'novel_status', 'field' => 'slug', 'terms' => array( 'completed', 'ongoing', 'hiatus', 'dropped' ), 'operator' => 'NOT IN' ),
        ),
        'fields' => 'ids',
    ) );

    // همچنین رمان‌هایی که وضعیت مشخصی ندارند
    $stale2 = new WP_Query( array(
        'post_type'      => 'novel',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'date_query'     => array(
            array( 'column' => 'post_modified', 'before' => $six_months_ago ),
        ),
        'author__not_in' => array( 1 ),
        'fields'         => 'ids',
    ) );

    $all_ids = array_unique( array_merge( $stale->posts, $stale2->posts ) );

    foreach ( $all_ids as $pid ) {
        $current_status = wp_get_post_terms( $pid, 'novel_status', array( 'fields' => 'slugs' ) );
        if ( in_array( 'completed', $current_status ) || in_array( 'dropped', $current_status ) ) continue;
        wp_set_post_terms( $pid, array( 'dropped' ), 'novel_status' );
    }
}

if ( ! wp_next_scheduled( 'fn_check_stale_novels' ) ) {
    wp_schedule_event( time(), 'daily', 'fn_check_stale_novels' );
}
add_action( 'fn_check_stale_novels', 'fn_auto_mark_dropped' );
