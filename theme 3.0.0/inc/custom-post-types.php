<?php
/**
 * فایل: inc/custom-post-types.php
 * توضیح: ثبت Custom Post Types - رمان (novel) و قسمت (chapter)
 * نسخه: 2.0.0
 * وابستگی: functions.php
 */

if (!defined('ABSPATH')) exit;

add_action('init', 'novel_register_post_types', 5);

function novel_register_post_types() {

    // ═══ CPT: رمان ═══
    register_post_type('novel', [
        'labels' => [
            'name'               => 'رمان‌ها',
            'singular_name'      => 'رمان',
            'add_new'            => 'افزودن رمان',
            'add_new_item'       => 'افزودن رمان جدید',
            'edit_item'          => 'ویرایش رمان',
            'new_item'           => 'رمان جدید',
            'view_item'          => 'مشاهده رمان',
            'search_items'       => 'جستجوی رمان',
            'not_found'          => 'رمانی یافت نشد',
            'not_found_in_trash' => 'رمانی در سطل زباله نیست',
            'all_items'          => 'همه رمان‌ها',
            'menu_name'          => 'رمان‌ها',
        ],
        'public'             => true,
        'has_archive'        => true,
        'rewrite'            => ['slug' => 'novel', 'with_front' => false],
        'supports'           => ['title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments'],
        'menu_icon'          => 'dashicons-book',
        'menu_position'      => 5,
        'show_in_rest'       => true,
        'taxonomies'         => ['genre', 'novel_status', 'novel_tag', 'novel_type'],
        'capability_type'    => 'post',
        'map_meta_cap'       => true,
    ]);

    // ═══ CPT: قسمت ═══
    register_post_type('chapter', [
        'labels' => [
            'name'               => 'قسمت‌ها',
            'singular_name'      => 'قسمت',
            'add_new'            => 'افزودن قسمت',
            'add_new_item'       => 'افزودن قسمت جدید',
            'edit_item'          => 'ویرایش قسمت',
            'new_item'           => 'قسمت جدید',
            'view_item'          => 'مشاهده قسمت',
            'search_items'       => 'جستجوی قسمت',
            'not_found'          => 'قسمتی یافت نشد',
            'not_found_in_trash' => 'قسمتی در سطل زباله نیست',
            'all_items'          => 'همه قسمت‌ها',
            'menu_name'          => 'قسمت‌ها',
        ],
        'public'             => true,
        'has_archive'        => false,
        'rewrite'            => ['slug' => 'chapter', 'with_front' => false],
        'supports'           => ['title', 'editor', 'author', 'comments'],
        'menu_icon'          => 'dashicons-media-text',
        'menu_position'      => 6,
        'show_in_rest'       => true,
        'capability_type'    => 'post',
        'map_meta_cap'       => true,
    ]);
}