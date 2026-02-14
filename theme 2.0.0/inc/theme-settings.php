<?php
/**
 * تنظیمات قالب (Customizer)
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function flavor_novel_customizer( $wp_customize ) {

    // ===== پنل اصلی =====
    $wp_customize->add_panel( 'fn_panel', array(
        'title'    => '🔮 تنظیمات فلیور نوول',
        'priority' => 30,
    ) );

    // ===== بخش عمومی =====
    $wp_customize->add_section( 'fn_general', array(
        'title' => 'تنظیمات عمومی',
        'panel' => 'fn_panel',
    ) );

    // تعداد رمان صفحه اصلی
    $wp_customize->add_setting( 'fn_home_novel_count', array(
        'default'           => 12,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'fn_home_novel_count', array(
        'label'   => 'تعداد رمان در صفحه اصلی',
        'section' => 'fn_general',
        'type'    => 'number',
        'input_attrs' => array( 'min' => 4, 'max' => 30 ),
    ) );

    // ===== شبکه‌های اجتماعی =====
    $wp_customize->add_section( 'fn_social', array(
        'title' => 'شبکه‌های اجتماعی',
        'panel' => 'fn_panel',
    ) );

    $socials = array(
        'fn_telegram'  => 'لینک تلگرام',
        'fn_instagram' => 'لینک اینستاگرام',
        'fn_twitter'   => 'لینک توییتر (X)',
        'fn_discord'   => 'لینک دیسکورد',
    );

    foreach ( $socials as $id => $label ) {
        $wp_customize->add_setting( $id, array(
            'default'           => '',
            'sanitize_callback' => 'esc_url_raw',
        ) );
        $wp_customize->add_control( $id, array(
            'label'   => $label,
            'section' => 'fn_social',
            'type'    => 'url',
        ) );
    }

    // ===== بخش ریدر =====
    $wp_customize->add_section( 'fn_reader', array(
        'title' => 'تنظیمات ریدر',
        'panel' => 'fn_panel',
    ) );

    $wp_customize->add_setting( 'fn_reader_default_font', array(
        'default'           => 'Vazirmatn',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'fn_reader_default_font', array(
        'label'   => 'فونت پیش‌فرض ریدر',
        'section' => 'fn_reader',
        'type'    => 'select',
        'choices' => array(
            'Vazirmatn' => 'وزیرمتن',
            'IRANSansX' => 'ایران‌سنس X',
            'Sahel'     => 'ساحل',
            'Shabnam'   => 'شبنم',
        ),
    ) );

    $wp_customize->add_setting( 'fn_reader_default_size', array(
        'default'           => 18,
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'fn_reader_default_size', array(
        'label'   => 'اندازه فونت پیش‌فرض (px)',
        'section' => 'fn_reader',
        'type'    => 'number',
        'input_attrs' => array( 'min' => 14, 'max' => 28 ),
    ) );
}
add_action( 'customize_register', 'flavor_novel_customizer' );