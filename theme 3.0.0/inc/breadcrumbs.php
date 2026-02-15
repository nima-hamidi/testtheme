<?php
/**
 * فایل: inc/breadcrumbs.php
 * توضیح: بردکرامب (مسیرنما) فارسی سفارشی
 * نسخه: 2.0.0
 * وابستگی: functions.php
 */

if (!defined('ABSPATH')) exit;

function novel_breadcrumb() {
    if (is_front_page()) return;

    $items = [];
    $items[] = '<a href="' . esc_url(home_url('/')) . '">خانه</a>';

    if (is_singular('novel')) {
        $items[] = '<a href="' . esc_url(get_post_type_archive_link('novel')) . '">رمان‌ها</a>';
        $genres = get_the_terms(get_the_ID(), 'genre');
        if ($genres && !is_wp_error($genres)) {
            $genre = $genres[0];
            $items[] = '<a href="' . esc_url(get_term_link($genre)) . '">' . esc_html($genre->name) . '</a>';
        }
        $items[] = '<span class="current">' . esc_html(get_the_title()) . '</span>';

    } elseif (is_singular('chapter')) {
        $novel_id = get_post_meta(get_the_ID(), '_chapter_novel_id', true);
        $items[] = '<a href="' . esc_url(get_post_type_archive_link('novel')) . '">رمان‌ها</a>';
        if ($novel_id) {
            $items[] = '<a href="' . esc_url(get_permalink($novel_id)) . '">' . esc_html(get_the_title($novel_id)) . '</a>';
        }
        $items[] = '<span class="current">' . esc_html(get_the_title()) . '</span>';

    } elseif (is_tax('genre')) {
        $items[] = '<a href="' . esc_url(get_post_type_archive_link('novel')) . '">رمان‌ها</a>';
        $items[] = '<span class="current">' . esc_html(single_term_title('', false)) . '</span>';

    } elseif (is_post_type_archive('novel')) {
        $items[] = '<span class="current">رمان‌ها</span>';

    } elseif (is_search()) {
        $items[] = '<span class="current">جستجو: ' . esc_html(get_search_query()) . '</span>';

    } elseif (is_page()) {
        $items[] = '<span class="current">' . esc_html(get_the_title()) . '</span>';

    } elseif (is_singular('post')) {
        $items[] = '<a href="' . esc_url(get_permalink(get_option('page_for_posts'))) . '">وبلاگ</a>';
        $items[] = '<span class="current">' . esc_html(get_the_title()) . '</span>';

    } elseif (is_404()) {
        $items[] = '<span class="current">خطای ۴۰۴</span>';
    }

    if (!empty($items)) {
        echo '<nav class="novel-breadcrumb" aria-label="مسیرنما">';
        echo '<ol itemscope itemtype="https://schema.org/BreadcrumbList">';
        foreach ($items as $i => $item) {
            $position = $i + 1;
            echo '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
            echo $item;
            echo '<meta itemprop="position" content="' . $position . '">';
            if ($i < count($items) - 1) {
                echo '<span class="separator">/</span>';
            }
            echo '</li>';
        }
        echo '</ol>';
        echo '</nav>';
    }
}