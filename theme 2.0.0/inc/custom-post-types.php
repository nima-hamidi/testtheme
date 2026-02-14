<?php
/**
 * پست تایپ‌های سفارشی
 * فقط ثبت CPT رمان و فصل + ستون‌های مدیریت + فیلتر ادمین
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* =============================================
 *  ثبت پست تایپ‌ها
 * ============================================= */
function flavor_novel_register_post_types() {

    // --- رمان (Novel) ---
    register_post_type( 'novel', array(
        'labels' => array(
            'name'                  => 'رمان‌ها',
            'singular_name'         => 'رمان',
            'menu_name'             => 'رمان‌ها',
            'name_admin_bar'        => 'رمان',
            'add_new'               => 'افزودن رمان',
            'add_new_item'          => 'افزودن رمان جدید',
            'new_item'              => 'رمان جدید',
            'edit_item'             => 'ویرایش رمان',
            'view_item'             => 'مشاهده رمان',
            'all_items'             => 'همه رمان‌ها',
            'search_items'          => 'جستجوی رمان',
            'not_found'             => 'رمانی یافت نشد.',
            'not_found_in_trash'    => 'رمانی در زباله‌دان یافت نشد.',
            'featured_image'        => 'تصویر جلد رمان',
            'set_featured_image'    => 'انتخاب تصویر جلد',
            'remove_featured_image' => 'حذف تصویر جلد',
            'use_featured_image'    => 'استفاده به عنوان تصویر جلد',
            'archives'              => 'آرشیو رمان‌ها',
        ),
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'novel', 'with_front' => false ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-book-alt',
        'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments', 'revisions' ),
        'taxonomies'         => array( 'genre', 'novel_tag', 'novel_status' ),
    ) );

    // --- فصل (Chapter) ---
    register_post_type( 'chapter', array(
        'labels' => array(
            'name'               => 'فصل‌ها',
            'singular_name'      => 'فصل',
            'menu_name'          => 'فصل‌ها',
            'name_admin_bar'     => 'فصل',
            'add_new'            => 'افزودن فصل',
            'add_new_item'       => 'افزودن فصل جدید',
            'new_item'           => 'فصل جدید',
            'edit_item'          => 'ویرایش فصل',
            'view_item'          => 'مشاهده فصل',
            'all_items'          => 'همه فصل‌ها',
            'search_items'       => 'جستجوی فصل',
            'not_found'          => 'فصلی یافت نشد.',
            'not_found_in_trash' => 'فصلی در زباله‌دان یافت نشد.',
            'archives'           => 'آرشیو فصل‌ها',
        ),
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'chapter', 'with_front' => false ),
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => 6,
        'menu_icon'          => 'dashicons-media-text',
        'supports'           => array( 'title', 'editor', 'author', 'comments', 'revisions' ),
    ) );
}
add_action( 'init', 'flavor_novel_register_post_types', 0 );


/* =============================================
 *  ستون‌های مدیریت رمان
 * ============================================= */
function fn_novel_admin_columns( $columns ) {
    $new = array();
    foreach ( $columns as $key => $val ) {
        if ( $key === 'title' ) $new['fn_cover'] = 'جلد';
        $new[ $key ] = $val;
        if ( $key === 'title' ) {
            $new['fn_chapters'] = 'فصل‌ها';
            $new['fn_views']    = 'بازدید';
            $new['fn_rating']   = 'امتیاز';
        }
    }
    return $new;
}
add_filter( 'manage_novel_posts_columns', 'fn_novel_admin_columns' );

function fn_novel_admin_column_data( $column, $post_id ) {
    switch ( $column ) {
        case 'fn_cover':
            if ( has_post_thumbnail( $post_id ) ) {
                echo '<img src="' . esc_url( get_the_post_thumbnail_url( $post_id, 'thumbnail' ) ) . '" style="width:40px;height:55px;object-fit:cover;border-radius:4px;">';
            } else {
                echo '—';
            }
            break;
        case 'fn_chapters':
            echo '<strong>' . absint( get_post_meta( $post_id, '_fn_chapter_count', true ) ) . '</strong>';
            break;
        case 'fn_views':
            echo fn_format_number( absint( get_post_meta( $post_id, '_fn_total_views', true ) ) );
            break;
        case 'fn_rating':
            $r = floatval( get_post_meta( $post_id, '_fn_avg_rating', true ) );
            echo $r > 0 ? '⭐ ' . number_format( $r, 1 ) : '—';
            break;
    }
}
add_action( 'manage_novel_posts_custom_column', 'fn_novel_admin_column_data', 10, 2 );


/* =============================================
 *  ستون‌های مدیریت فصل
 * ============================================= */
function fn_chapter_admin_columns( $columns ) {
    $new = array();
    foreach ( $columns as $key => $val ) {
        $new[ $key ] = $val;
        if ( $key === 'title' ) {
            $new['fn_parent_novel']   = 'رمان';
            $new['fn_chapter_number'] = 'شماره فصل';
        }
    }
    return $new;
}
add_filter( 'manage_chapter_posts_columns', 'fn_chapter_admin_columns' );

function fn_chapter_admin_column_data( $column, $post_id ) {
    switch ( $column ) {
        case 'fn_parent_novel':
            $nid = get_post_meta( $post_id, '_fn_parent_novel', true );
            echo $nid ? '<a href="' . esc_url( get_edit_post_link( $nid ) ) . '">' . esc_html( get_the_title( $nid ) ) . '</a>' : '<span style="color:#999;">تنظیم نشده</span>';
            break;
        case 'fn_chapter_number':
            $num = get_post_meta( $post_id, '_fn_chapter_number', true );
            echo $num ? '<strong>' . esc_html( $num ) . '</strong>' : '—';
            break;
    }
}
add_action( 'manage_chapter_posts_custom_column', 'fn_chapter_admin_column_data', 10, 2 );

// مرتب‌سازی ستون‌ها
function fn_chapter_sortable_columns( $columns ) {
    $columns['fn_chapter_number'] = 'fn_chapter_number';
    $columns['fn_parent_novel']   = 'fn_parent_novel';
    return $columns;
}
add_filter( 'manage_edit-chapter_sortable_columns', 'fn_chapter_sortable_columns' );

function fn_chapter_orderby( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() ) return;
    $ob = $query->get( 'orderby' );
    if ( $ob === 'fn_chapter_number' ) {
        $query->set( 'meta_key', '_fn_chapter_number' );
        $query->set( 'orderby', 'meta_value_num' );
    }
    if ( $ob === 'fn_parent_novel' ) {
        $query->set( 'meta_key', '_fn_parent_novel' );
        $query->set( 'orderby', 'meta_value_num' );
    }
}
add_action( 'pre_get_posts', 'fn_chapter_orderby' );


/* =============================================
 *  فیلتر فصل‌ها بر اساس رمان در ادمین
 * ============================================= */
function fn_chapter_filter_dropdown() {
    global $typenow;
    if ( $typenow !== 'chapter' ) return;

    $novels = get_posts( array(
        'post_type'      => 'novel',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post_status'    => 'publish',
    ) );
    if ( empty( $novels ) ) return;

    $selected = absint( $_GET['filter_novel'] ?? 0 );
    echo '<select name="filter_novel">';
    echo '<option value="">همه رمان‌ها</option>';
    foreach ( $novels as $n ) {
        printf( '<option value="%d" %s>%s</option>', $n->ID, selected( $selected, $n->ID, false ), esc_html( $n->post_title ) );
    }
    echo '</select>';
}
add_action( 'restrict_manage_posts', 'fn_chapter_filter_dropdown' );

function fn_chapter_filter_query( $query ) {
    global $pagenow, $typenow;
    if ( $pagenow !== 'edit.php' || $typenow !== 'chapter' || ! is_admin() ) return;
    if ( ! empty( $_GET['filter_novel'] ) ) {
        $query->set( 'meta_query', array(
            array( 'key' => '_fn_parent_novel', 'value' => absint( $_GET['filter_novel'] ), 'type' => 'NUMERIC' ),
        ) );
    }
}
add_action( 'pre_get_posts', 'fn_chapter_filter_query' );


/* =============================================
 *  عنوان خودکار فصل
 * ============================================= */
function fn_auto_chapter_title( $data, $postarr ) {
    if ( $data['post_type'] !== 'chapter' ) return $data;
    if ( empty( $data['post_title'] ) || $data['post_title'] === 'پیش‌نویس خودکار' ) {
        $nid = absint( $postarr['fn_parent_novel'] ?? 0 );
        $num = floatval( $postarr['fn_chapter_number'] ?? 0 );
        if ( $nid && $num ) {
            $data['post_title'] = sprintf( '%s - فصل %s', get_the_title( $nid ), $num );
            $data['post_name']  = sanitize_title( $data['post_title'] );
        }
    }
    return $data;
}
add_filter( 'wp_insert_post_data', 'fn_auto_chapter_title', 10, 2 );