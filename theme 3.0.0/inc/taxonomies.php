<?php
/**
 * فایل: inc/taxonomies.php
 * توضیح: ثبت Taxonomy ها - ژانر، وضعیت، تگ رمان، نوع رمان
 * نسخه: 2.0.0
 * وابستگی: custom-post-types.php
 */

if (!defined('ABSPATH')) exit;

add_action('init', 'novel_register_taxonomies', 6);

function novel_register_taxonomies() {

    // ═══ ژانر ═══
    register_taxonomy('genre', ['novel'], [
        'labels' => [
            'name'          => 'ژانرها',
            'singular_name' => 'ژانر',
            'search_items'  => 'جستجوی ژانر',
            'all_items'     => 'همه ژانرها',
            'edit_item'     => 'ویرایش ژانر',
            'update_item'   => 'به‌روزرسانی ژانر',
            'add_new_item'  => 'افزودن ژانر جدید',
            'new_item_name' => 'نام ژانر جدید',
            'menu_name'     => 'ژانرها',
        ],
        'hierarchical' => true,
        'public'       => true,
        'rewrite'      => ['slug' => 'genre', 'with_front' => false],
        'show_in_rest' => true,
        'show_admin_column' => true,
    ]);

    // ═══ وضعیت رمان ═══
    register_taxonomy('novel_status', ['novel'], [
        'labels' => [
            'name'          => 'وضعیت',
            'singular_name' => 'وضعیت',
            'all_items'     => 'همه وضعیت‌ها',
            'edit_item'     => 'ویرایش وضعیت',
            'add_new_item'  => 'افزودن وضعیت',
            'menu_name'     => 'وضعیت انتشار',
        ],
        'hierarchical' => true,
        'public'       => true,
        'rewrite'      => ['slug' => 'status', 'with_front' => false],
        'show_in_rest' => true,
        'show_admin_column' => true,
    ]);

    // ═══ تگ رمان ═══
    register_taxonomy('novel_tag', ['novel'], [
        'labels' => [
            'name'          => 'تگ‌ها',
            'singular_name' => 'تگ',
            'search_items'  => 'جستجوی تگ',
            'all_items'     => 'همه تگ‌ها',
            'edit_item'     => 'ویرایش تگ',
            'add_new_item'  => 'افزودن تگ',
            'menu_name'     => 'تگ‌های رمان',
        ],
        'hierarchical' => false,
        'public'       => true,
        'rewrite'      => ['slug' => 'tag', 'with_front' => false],
        'show_in_rest' => true,
        'show_admin_column' => true,
    ]);

    // ═══ نوع رمان ═══
    register_taxonomy('novel_type', ['novel'], [
        'labels' => [
            'name'          => 'نوع رمان',
            'singular_name' => 'نوع',
            'all_items'     => 'همه انواع',
            'edit_item'     => 'ویرایش نوع',
            'add_new_item'  => 'افزودن نوع',
            'menu_name'     => 'نوع رمان',
        ],
        'hierarchical' => true,
        'public'       => true,
        'rewrite'      => ['slug' => 'type', 'with_front' => false],
        'show_in_rest' => true,
        'show_admin_column' => true,
    ]);
}