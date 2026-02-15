<?php
/**
 * Novel SEO - Schema, OG, Meta Tags
 * 
 * سئوی خودکار برای رمان‌ها، قسمت‌ها و نویسندگان
 *
 * @package suspended-flavor
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

class Novel_SEO {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('wp_head', [$this, 'output_meta'], 1);
        add_action('wp_head', [$this, 'output_og_tags'], 2);
        add_action('wp_head', [$this, 'output_schema'], 3);
        add_filter('document_title_parts', [$this, 'custom_title']);

        // Remove default WP meta if we handle it
        remove_action('wp_head', 'rel_canonical');
        add_action('wp_head', [$this, 'output_canonical'], 1);
    }

    /**
     * Custom page title
     */
    public function custom_title($title) {
        if (is_singular('novel')) {
            $title['title'] = get_the_title();
        } elseif (is_singular('chapter')) {
            $novel_id = (int) get_post_meta(get_the_ID(), 'chapter_novel_id', true);
            if (!$novel_id) $novel_id = wp_get_post_parent_id(get_the_ID());
            $ch_num = (int) get_post_meta(get_the_ID(), 'chapter_number', true);
            $novel_title = $novel_id ? get_the_title($novel_id) : '';

            $title['title'] = sprintf('قسمت %d: %s - %s', $ch_num, get_the_title(), $novel_title);
        } elseif (is_author()) {
            $author = get_queried_object();
            $title['title'] = sprintf('پروفایل %s', $author->display_name);
        }

        return $title;
    }

    /**
     * Output meta description + canonical
     */
    public function output_meta() {
        $description = $this->get_description();
        if ($description) {
            printf('<meta name="description" content="%s">' . "\n", esc_attr($description));
        }
    }

    /**
     * Output canonical
     */
    public function output_canonical() {
        $url = $this->get_canonical_url();
        if ($url) {
            printf('<link rel="canonical" href="%s">' . "\n", esc_url($url));
        }
    }

    /**
     * Output Open Graph + Twitter Card
     */
    public function output_og_tags() {
        $data = $this->get_og_data();
        if (empty($data)) return;

        echo "\n<!-- Novel SEO: Open Graph -->\n";
        printf('<meta property="og:title" content="%s">' . "\n", esc_attr($data['title']));
        printf('<meta property="og:description" content="%s">' . "\n", esc_attr($data['description']));
        printf('<meta property="og:url" content="%s">' . "\n", esc_url($data['url']));
        printf('<meta property="og:type" content="%s">' . "\n", esc_attr($data['type']));
        printf('<meta property="og:site_name" content="%s">' . "\n", esc_attr(get_bloginfo('name')));
        echo '<meta property="og:locale" content="fa_IR">' . "\n";

        if (!empty($data['image'])) {
            printf('<meta property="og:image" content="%s">' . "\n", esc_url($data['image']));
        }

        // Twitter Card
        echo "\n<!-- Novel SEO: Twitter Card -->\n";
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        printf('<meta name="twitter:title" content="%s">' . "\n", esc_attr($data['title']));
        printf('<meta name="twitter:description" content="%s">' . "\n", esc_attr($data['description']));
        if (!empty($data['image'])) {
            printf('<meta name="twitter:image" content="%s">' . "\n", esc_url($data['image']));
        }
    }

    /**
     * Output Schema.org JSON-LD
     */
    public function output_schema() {
        $schemas = $this->get_schemas();
        if (empty($schemas)) return;

        echo "\n<!-- Novel SEO: Schema.org -->\n";
        foreach ($schemas as $schema) {
            echo '<script type="application/ld+json">' . "\n";
            echo wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            echo "\n</script>\n";
        }
    }

    /* ═══════════════════════════════════════
       Data Generators
       ═══════════════════════════════════════ */

    /**
     * Get meta description
     */
    private function get_description() {
        if (is_singular('novel')) {
            $pid = get_the_ID();
            $author = get_post_meta($pid, 'novel_original_author', true);
            $rating = (float) get_post_meta($pid, 'novel_rating_average', true);
            $ch_count = (int) get_post_meta($pid, 'novel_chapter_count', true);
            $genres = wp_get_post_terms($pid, 'genre', ['fields' => 'names']);
            $genre_str = !is_wp_error($genres) ? implode('، ', array_slice($genres, 0, 3)) : '';

            $parts = [];
            $parts[] = sprintf('خلاصه رمان %s', get_the_title());
            if ($author) $parts[] = sprintf('نوشته %s', $author);
            if ($genre_str) $parts[] = $genre_str;
            if ($ch_count) $parts[] = sprintf('%d قسمت', $ch_count);
            if ($rating > 0) $parts[] = sprintf('امتیاز %.1f', $rating);

            return mb_substr(implode(' - ', $parts), 0, 160);
        }

        if (is_singular('chapter')) {
            $pid = get_the_ID();
            $ch_num = (int) get_post_meta($pid, 'chapter_number', true);
            $novel_id = (int) get_post_meta($pid, 'chapter_novel_id', true);
            if (!$novel_id) $novel_id = wp_get_post_parent_id($pid);
            $novel_title = $novel_id ? get_the_title($novel_id) : '';
            $excerpt = mb_substr(wp_strip_all_tags(get_the_content()), 0, 120);

            return sprintf('قسمت %d رمان %s - %s', $ch_num, $novel_title, $excerpt);
        }

        if (is_author()) {
            $author = get_queried_object();
            $novel_count = count_user_posts($author->ID, 'novel');
            $bio = get_user_meta($author->ID, 'description', true);

            $desc = sprintf('پروفایل %s - %d رمان', $author->display_name, $novel_count);
            if ($bio) $desc .= ' - ' . mb_substr($bio, 0, 100);

            return mb_substr($desc, 0, 160);
        }

        if (is_tax('genre')) {
            $term = get_queried_object();
            return sprintf('رمان‌های ژانر %s - مجموعه کامل رمان‌های %s', $term->name, $term->name);
        }

        return '';
    }

    /**
     * Get canonical URL
     */
    private function get_canonical_url() {
        if (is_singular()) return get_permalink();
        if (is_author()) return get_author_posts_url(get_queried_object_id());
        if (is_tax() || is_category() || is_tag()) return get_term_link(get_queried_object());
        if (is_home() || is_front_page()) return home_url('/');
        return '';
    }

    /**
     * Get OG data
     */
    private function get_og_data() {
        $data = ['title' => '', 'description' => '', 'url' => '', 'image' => '', 'type' => 'website'];

        if (is_singular('novel')) {
            $data['title'] = get_the_title();
            $data['description'] = $this->get_description();
            $data['url'] = get_permalink();
            $data['image'] = get_the_post_thumbnail_url(get_the_ID(), 'large');
            $data['type'] = 'book';
        } elseif (is_singular('chapter')) {
            $novel_id = (int) get_post_meta(get_the_ID(), 'chapter_novel_id', true);
            if (!$novel_id) $novel_id = wp_get_post_parent_id(get_the_ID());
            $ch_num = (int) get_post_meta(get_the_ID(), 'chapter_number', true);
            $novel_title = $novel_id ? get_the_title($novel_id) : '';

            $data['title'] = sprintf('قسمت %d: %s - %s', $ch_num, get_the_title(), $novel_title);
            $data['description'] = $this->get_description();
            $data['url'] = get_permalink();
            $data['image'] = $novel_id ? get_the_post_thumbnail_url($novel_id, 'large') : '';
            $data['type'] = 'article';
        } elseif (is_author()) {
            $author = get_queried_object();
            $data['title'] = sprintf('پروفایل %s', $author->display_name);
            $data['description'] = $this->get_description();
            $data['url'] = get_author_posts_url($author->ID);
            $data['image'] = get_avatar_url($author->ID, ['size' => 512]);
            $data['type'] = 'profile';
        } elseif (is_singular()) {
            $data['title'] = get_the_title();
            $data['description'] = mb_substr(wp_strip_all_tags(get_the_excerpt()), 0, 160);
            $data['url'] = get_permalink();
            $data['image'] = get_the_post_thumbnail_url(get_the_ID(), 'large');
            $data['type'] = 'article';
        }

        return $data;
    }

    /**
     * Get Schema.org structured data
     */
    private function get_schemas() {
        $schemas = [];

        // Breadcrumb schema (all pages)
        $breadcrumb = $this->get_breadcrumb_schema();
        if ($breadcrumb) $schemas[] = $breadcrumb;

        if (is_singular('novel')) {
            $schemas[] = $this->get_novel_schema();
        } elseif (is_singular('chapter')) {
            $schemas[] = $this->get_chapter_schema();
        } elseif (is_author()) {
            $schemas[] = $this->get_author_schema();
        }

        return $schemas;
    }

    /**
     * Novel Schema (Book)
     */
    private function get_novel_schema() {
        $pid = get_the_ID();
        $title = get_the_title();
        $english = get_post_meta($pid, 'novel_english_name', true);
        $author = get_post_meta($pid, 'novel_original_author', true);
        $translator = get_post_meta($pid, 'novel_translator', true);
        $rating = (float) get_post_meta($pid, 'novel_rating_average', true);
        $rating_count = (int) get_post_meta($pid, 'novel_rating_count', true);
        $ch_count = (int) get_post_meta($pid, 'novel_chapter_count', true);
        $image = get_the_post_thumbnail_url($pid, 'large');
        $genres = wp_get_post_terms($pid, 'genre', ['fields' => 'names']);

        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => 'Book',
            'name'     => $title,
            'url'      => get_permalink(),
            'inLanguage' => 'fa',
            'numberOfPages' => $ch_count,
        ];

        if ($english) $schema['alternateName'] = $english;
        if ($image) $schema['image'] = $image;
        if ($author) $schema['author'] = ['@type' => 'Person', 'name' => $author];
        if ($translator) $schema['translator'] = ['@type' => 'Person', 'name' => $translator];
        if (!is_wp_error($genres) && !empty($genres)) $schema['genre'] = $genres;

        if ($rating > 0 && $rating_count > 0) {
            $schema['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => round($rating, 1),
                'reviewCount' => $rating_count,
                'bestRating'  => '5',
                'worstRating' => '1',
            ];
        }

        return $schema;
    }

    /**
     * Chapter Schema
     */
    private function get_chapter_schema() {
        $pid = get_the_ID();
        $ch_num = (int) get_post_meta($pid, 'chapter_number', true);
        $novel_id = (int) get_post_meta($pid, 'chapter_novel_id', true);
        if (!$novel_id) $novel_id = wp_get_post_parent_id($pid);

        $content = get_the_content();
        $word_count = str_word_count(wp_strip_all_tags($content));

        $schema = [
            '@context'      => 'https://schema.org',
            '@type'         => 'Chapter',
            'name'          => sprintf('قسمت %d: %s', $ch_num, get_the_title()),
            'position'      => $ch_num,
            'wordCount'     => $word_count,
            'datePublished' => get_the_date('c'),
            'url'           => get_permalink(),
        ];

        if ($novel_id) {
            $schema['isPartOf'] = [
                '@type' => 'Book',
                'name'  => get_the_title($novel_id),
                'url'   => get_permalink($novel_id),
            ];
        }

        return $schema;
    }

    /**
     * Author Schema (Person)
     */
    private function get_author_schema() {
        $author = get_queried_object();
        if (!$author) return null;

        $avatar_id = (int) get_user_meta($author->ID, 'novel_avatar', true);
        $avatar_url = '';
        if ($avatar_id > 0 && $avatar_id <= 114) {
            $avatar_url = get_template_directory_uri() . '/assets/avatars/avatar-' . $avatar_id . '.png';
        } else {
            $avatar_url = get_avatar_url($author->ID, ['size' => 256]);
        }

        $bio = get_user_meta($author->ID, 'description', true);

        return [
            '@context'    => 'https://schema.org',
            '@type'       => 'Person',
            'name'        => $author->display_name,
            'url'         => get_author_posts_url($author->ID),
            'image'       => $avatar_url,
            'description' => $bio ? mb_substr($bio, 0, 200) : '',
        ];
    }

    /**
     * Breadcrumb Schema
     */
    private function get_breadcrumb_schema() {
        $items = [];
        $pos = 1;

        // Home
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $pos++,
            'name'     => 'خانه',
            'item'     => home_url('/'),
        ];

        if (is_singular('novel')) {
            // Genre
            $genres = wp_get_post_terms(get_the_ID(), 'genre');
            if (!is_wp_error($genres) && !empty($genres)) {
                $items[] = [
                    '@type'    => 'ListItem',
                    'position' => $pos++,
                    'name'     => $genres[0]->name,
                    'item'     => get_term_link($genres[0]),
                ];
            }
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $pos++,
                'name'     => get_the_title(),
            ];
        } elseif (is_singular('chapter')) {
            $novel_id = (int) get_post_meta(get_the_ID(), 'chapter_novel_id', true);
            if (!$novel_id) $novel_id = wp_get_post_parent_id(get_the_ID());

            if ($novel_id) {
                $items[] = [
                    '@type'    => 'ListItem',
                    'position' => $pos++,
                    'name'     => get_the_title($novel_id),
                    'item'     => get_permalink($novel_id),
                ];
            }

            $ch_num = (int) get_post_meta(get_the_ID(), 'chapter_number', true);
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $pos++,
                'name'     => sprintf('قسمت %d', $ch_num),
            ];
        } elseif (is_author()) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $pos++,
                'name'     => get_queried_object()->display_name,
            ];
        } elseif (is_tax('genre')) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $pos++,
                'name'     => get_queried_object()->name,
            ];
        }

        if (count($items) < 2) return null;

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }
}