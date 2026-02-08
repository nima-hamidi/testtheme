<?php
/**
 * تکسونومی‌های سفارشی
 * ژانر، تگ رمان، وضعیت انتشار
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * ثبت تکسونومی‌ها
 */
function flavor_novel_register_taxonomies() {

    // --- ژانر (Genre) ---
    register_taxonomy( 'genre', array( 'novel' ), array(
        'labels' => array(
            'name'                       => 'ژانرها',
            'singular_name'              => 'ژانر',
            'search_items'               => 'جستجوی ژانر',
            'popular_items'              => 'ژانرهای محبوب',
            'all_items'                  => 'همه ژانرها',
            'parent_item'                => 'ژانر والد',
            'parent_item_colon'          => 'ژانر والد:',
            'edit_item'                  => 'ویرایش ژانر',
            'update_item'                => 'بروزرسانی ژانر',
            'add_new_item'               => 'افزودن ژانر جدید',
            'new_item_name'              => 'نام ژانر جدید',
            'separate_items_with_commas' => 'ژانرها را با ویرگول جدا کنید',
            'add_or_remove_items'        => 'افزودن یا حذف ژانرها',
            'choose_from_most_used'      => 'انتخاب از پرکاربردترین‌ها',
            'not_found'                  => 'ژانری یافت نشد.',
            'menu_name'                  => 'ژانرها',
        ),
        'hierarchical'      => true,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'query_var'         => true,
        'rewrite'           => array(
            'slug'         => 'genre',
            'with_front'   => false,
            'hierarchical' => true,
        ),
    ) );

    // --- تگ رمان (Novel Tag) ---
    register_taxonomy( 'novel_tag', array( 'novel' ), array(
        'labels' => array(
            'name'                       => 'تگ‌های رمان',
            'singular_name'              => 'تگ رمان',
            'search_items'               => 'جستجوی تگ',
            'popular_items'              => 'تگ‌های محبوب',
            'all_items'                  => 'همه تگ‌ها',
            'edit_item'                  => 'ویرایش تگ',
            'update_item'                => 'بروزرسانی تگ',
            'add_new_item'               => 'افزودن تگ جدید',
            'new_item_name'              => 'نام تگ جدید',
            'separate_items_with_commas' => 'تگ‌ها را با ویرگول جدا کنید',
            'add_or_remove_items'        => 'افزودن یا حذف تگ‌ها',
            'choose_from_most_used'      => 'انتخاب از پرکاربردترین‌ها',
            'not_found'                  => 'تگی یافت نشد.',
            'menu_name'                  => 'تگ‌ها',
        ),
        'hierarchical'      => false,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'query_var'         => true,
        'rewrite'           => array(
            'slug'       => 'novel-tag',
            'with_front' => false,
        ),
    ) );

    // --- وضعیت انتشار رمان (Novel Status) ---
    register_taxonomy( 'novel_status', array( 'novel' ), array(
        'labels' => array(
            'name'          => 'وضعیت رمان',
            'singular_name' => 'وضعیت',
            'search_items'  => 'جستجوی وضعیت',
            'all_items'     => 'همه وضعیت‌ها',
            'edit_item'     => 'ویرایش وضعیت',
            'update_item'   => 'بروزرسانی وضعیت',
            'add_new_item'  => 'افزودن وضعیت جدید',
            'new_item_name' => 'نام وضعیت جدید',
            'menu_name'     => 'وضعیت انتشار',
        ),
        'hierarchical'      => true,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'query_var'         => true,
        'rewrite'           => array(
            'slug'       => 'status',
            'with_front' => false,
        ),
    ) );

    // --- نوع رمان (Novel Type: Web Novel vs Light Novel) ---
    register_taxonomy( 'novel_type', array( 'novel' ), array(
        'labels' => array(
            'name'              => 'نوع رمان',
            'singular_name'     => 'نوع',
            'search_items'      => 'جستجوی نوع رمان',
            'all_items'         => 'انواع رمان',
            'edit_item'         => 'ویرایش نوع',
            'update_item'       => 'بروزرسانی نوع',
            'add_new_item'      => 'افزودن نوع جدید',
            'new_item_name'     => 'نام نوع جدید',
            'menu_name'         => 'نوع رمان',
        ),
        'hierarchical'      => true,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'query_var'         => true,
        'rewrite'           => array(
            'slug'       => 'novel-type',
            'with_front' => false,
        ),
    ) );
}
add_action( 'init', 'flavor_novel_register_taxonomies', 0 );


/**
 * درج ترم‌های پیش‌فرض هنگام فعال‌سازی قالب
 */
function flavor_novel_insert_default_terms() {

    // وضعیت‌های پیش‌فرض
    $default_statuses = array(
        array( 'name' => 'در حال انتشار', 'slug' => 'ongoing',   'desc' => 'رمان در حال انتشار فصل‌های جدید است' ),
        array( 'name' => 'تمام‌شده',      'slug' => 'completed', 'desc' => 'رمان به پایان رسیده است' ),
        array( 'name' => 'متوقف',         'slug' => 'hiatus',    'desc' => 'انتشار به‌صورت موقت متوقف شده' ),
        array( 'name' => 'رهاشده',        'slug' => 'dropped',   'desc' => 'ترجمه یا انتشار رها شده است' ),
    );

    foreach ( $default_statuses as $s ) {
        if ( ! term_exists( $s['slug'], 'novel_status' ) ) {
            wp_insert_term( $s['name'], 'novel_status', array(
                'slug'        => $s['slug'],
                'description' => $s['desc'],
            ) );
        }
    }

    // ژانرهای پیش‌فرض
    $default_genres = array(
        array( 'name' => 'اکشن',         'slug' => 'action',        'icon' => '⚔️' ),
        array( 'name' => 'ماجراجویی',     'slug' => 'adventure',     'icon' => '🗺️' ),
        array( 'name' => 'کمدی',          'slug' => 'comedy',        'icon' => '😂' ),
        array( 'name' => 'درام',          'slug' => 'drama',         'icon' => '🎭' ),
        array( 'name' => 'فانتزی',        'slug' => 'fantasy',       'icon' => '🧙' ),
        array( 'name' => 'تاریخی',        'slug' => 'historical',    'icon' => '🏛️' ),
        array( 'name' => 'وحشت',          'slug' => 'horror',        'icon' => '👻' ),
        array( 'name' => 'رمز و راز',     'slug' => 'mystery',       'icon' => '🔍' ),
        array( 'name' => 'عاشقانه',       'slug' => 'romance',       'icon' => '❤️' ),
        array( 'name' => 'علمی‌تخیلی',    'slug' => 'sci-fi',        'icon' => '🚀' ),
        array( 'name' => 'ترسناک',        'slug' => 'thriller',      'icon' => '😱' ),
        array( 'name' => 'ورزشی',         'slug' => 'sports',        'icon' => '⚽' ),
        array( 'name' => 'روان‌شناسی',    'slug' => 'psychological', 'icon' => '🧠' ),
        array( 'name' => 'هارم',          'slug' => 'harem',         'icon' => '👑' ),
        array( 'name' => 'بازی',          'slug' => 'game',          'icon' => '🎮' ),
        array( 'name' => 'هنرهای رزمی',   'slug' => 'martial-arts',  'icon' => '🥋' ),
        array( 'name' => 'مکانیک',        'slug' => 'mecha',         'icon' => '🤖' ),
        array( 'name' => 'زندگی روزمره',  'slug' => 'slice-of-life', 'icon' => '🌸' ),
        array( 'name' => 'فوق‌طبیعی',     'slug' => 'supernatural',  'icon' => '✨' ),
        array( 'name' => 'تراژدی',        'slug' => 'tragedy',       'icon' => '💔' ),
    );

    foreach ( $default_genres as $g ) {
        if ( ! term_exists( $g['slug'], 'genre' ) ) {
            $result = wp_insert_term( $g['name'], 'genre', array( 'slug' => $g['slug'] ) );
            if ( ! is_wp_error( $result ) ) {
                update_term_meta( $result['term_id'], '_fn_genre_icon', $g['icon'] );
            }
        }
    }

    // انواع رمان پیش‌فرض
    $default_types = array(
        array( 'name' => 'وب‌نوول',      'slug' => 'web-novel',   'desc' => 'رمان‌های وب‌ای منتشر شده به صورت فصل به فصل' ),
        array( 'name' => 'لایت‌نوول',    'slug' => 'light-novel', 'desc' => 'رمان‌های خفیف منتشر شده به صورت جلد (جلدهای)' ),
    );

    foreach ( $default_types as $t ) {
        if ( ! term_exists( $t['slug'], 'novel_type' ) ) {
            wp_insert_term( $t['name'], 'novel_type', array(
                'slug'        => $t['slug'],
                'description' => $t['desc'],
            ) );
        }
    }
}
add_action( 'after_switch_theme', 'flavor_novel_insert_default_terms' );