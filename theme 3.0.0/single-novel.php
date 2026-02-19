<?php
/**
 * Single Novel Template
 * 
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) :
    the_post();
    
    $novel_id        = get_the_ID();
    $english_name    = get_post_meta($novel_id, 'novel_english_name', true);
    $novel_type      = get_post_meta($novel_id, 'novel_type', true) ?: 'web_novel';
    $original_author = get_post_meta($novel_id, 'novel_original_author', true);
    $translator      = get_post_meta($novel_id, 'novel_translator', true);
    $country         = get_post_meta($novel_id, 'novel_country', true);
    $year            = get_post_meta($novel_id, 'novel_year', true);
    $total_chapters  = get_post_meta($novel_id, 'novel_total_chapters', true);
    $has_anime       = get_post_meta($novel_id, 'novel_has_anime', true);
    $anime_url       = get_post_meta($novel_id, 'novel_anime_url', true);
    $has_manga       = get_post_meta($novel_id, 'novel_has_manga', true);
    $manga_url       = get_post_meta($novel_id, 'novel_manga_url', true);
    $custom_links    = get_post_meta($novel_id, 'novel_custom_links', true);
    $views           = get_post_meta($novel_id, 'novel_views', true) ?: 0;
    
    $countries       = novel_get_country_labels();
    $type_labels     = novel_get_type_labels();
    $chapter_counts  = novel_get_chapter_counts_by_type($novel_id);
    
    // Genres & Tags
    $genres     = wp_get_post_terms($novel_id, 'genre');
    $tags       = wp_get_post_terms($novel_id, 'novel_tag');
    
    // User-specific data
    $is_logged_in   = is_user_logged_in();
    $current_user   = $is_logged_in ? wp_get_current_user() : null;
    $is_following   = false;
    $library_status = '';
    $last_read      = 0;
    
    if ($is_logged_in) {
        global $wpdb;
        $user_id = $current_user->ID;
        
        // Check follow
        $follow_table = $wpdb->prefix . 'novel_follows';
        if ($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$follow_table} WHERE user_id = %d AND novel_id = %d",
            $user_id, $novel_id
        ))) {
            $is_following = true;
        }
        
        // Check library
        $library_table = $wpdb->prefix . 'user_library';
        $library_row = $wpdb->get_row($wpdb->prepare(
            "SELECT status, last_chapter_id FROM {$library_table} WHERE user_id = %d AND novel_id = %d",
            $user_id, $novel_id
        ));
        if ($library_row) {
            $library_status = $library_row->status;
            $last_read = $library_row->last_chapter_id;
        }
        
        // Last read chapter number
        if ($last_read) {
            $last_read_num = get_post_meta($last_read, 'chapter_number', true);
        }
    }
    
    // Rating (average)
    $avg_rating   = get_post_meta($novel_id, 'novel_avg_rating', true) ?: 0;
    $rating_count = get_post_meta($novel_id, 'novel_rating_count', true) ?: 0;
    
    // Follow count
    $follow_count   = get_post_meta($novel_id, 'novel_follow_count', true) ?: 0;
    $bookmark_count = get_post_meta($novel_id, 'novel_bookmark_count', true) ?: 0;
    $comment_count  = get_comments_number($novel_id);
    
    // Increment views
    $new_views = (int) $views + 1;
    update_post_meta($novel_id, 'novel_views', $new_views);
    
    // Has volumes?
    $has_volumes = Novel_Volumes::novel_has_volumes($novel_id);
?>

<main class="single-novel-page">
    
    <!-- Breadcrumb -->
    <nav class="novel-breadcrumb" aria-label="مسیر">
        <div class="container">
            <a href="<?php echo home_url(); ?>">خانه</a>
            <span class="sep">›</span>
            <a href="<?php echo get_post_type_archive_link('novel'); ?>">رمان‌ها</a>
            <span class="sep">›</span>
            <span class="current"><?php the_title(); ?></span>
        </div>
    </nav>

    <div class="container">
        
        <!-- ═══ بخش ۱: هدر رمان ═══ -->
        <section class="novel-header" id="novelHeader">
            <div class="novel-header__cover">
                <div class="novel-cover-wrapper">
                    <?php if (has_post_thumbnail()) : ?>
                        <?php the_post_thumbnail('large', [
                            'class' => 'novel-cover-img',
                            'loading' => 'eager',
                        ]); ?>
                    <?php else : ?>
                        <div class="novel-cover-placeholder">
                            <span>📖</span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Type Badge -->
                    <span class="novel-type-badge novel-type-<?php echo $novel_type === 'light_novel' ? 'ln' : 'wn'; ?>">
                        <?php echo $novel_type === 'light_novel' ? 'LN' : 'WN'; ?>
                    </span>
                    
                    <!-- Rating Badge -->
                    <?php if ($avg_rating > 0) : ?>
                    <span class="novel-rating-badge">
                        ★ <?php echo number_format($avg_rating, 1); ?>
                    </span>
                    <?php endif; ?>
                    
                    <!-- Media Badges -->
                    <?php if ($has_anime) : ?>
                        <span class="novel-media-badge anime-badge">🎬 انیمه</span>
                    <?php endif; ?>
                    <?php if ($has_manga) : ?>
                        <span class="novel-media-badge manga-badge">📚 مانگا</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="novel-header__info">
                <h1 class="novel-title"><?php the_title(); ?></h1>
                
                <?php if ($english_name) : ?>
                    <p class="novel-title-en"><?php echo esc_html($english_name); ?></p>
                <?php endif; ?>
                
                <div class="novel-meta-info">
                    <!-- Author -->
                    <div class="meta-item">
                        <span class="meta-label">نویسنده:</span>
                        <span class="meta-value">
                            <a href="<?php echo get_author_posts_url(get_the_author_meta('ID')); ?>" class="author-link">
                                <?php echo esc_html($original_author ?: get_the_author()); ?>
                            </a>
                        </span>
                    </div>
                    
                    <!-- Translator -->
                    <?php if ($translator) : ?>
                    <div class="meta-item">
                        <span class="meta-label">مترجم:</span>
                        <span class="meta-value"><?php echo esc_html($translator); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="novel-meta-divider"></div>
                    
                    <!-- Type -->
                    <div class="meta-item">
                        <span class="meta-label">نوع:</span>
                        <span class="meta-value">
                            <span class="type-inline-badge type-<?php echo $novel_type === 'light_novel' ? 'ln' : 'wn'; ?>">
                                <?php echo esc_html($type_labels[$novel_type] ?? $novel_type); ?>
                            </span>
                        </span>
                    </div>
                    
                    <!-- Country -->
                    <?php if ($country && isset($countries[$country])) : ?>
                    <div class="meta-item">
                        <span class="meta-label">کشور:</span>
                        <span class="meta-value"><?php echo esc_html($countries[$country]); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Status -->
                    <div class="meta-item">
                        <span class="meta-label">وضعیت:</span>
                        <span class="meta-value"><?php echo novel_get_status_badge($novel_id); ?></span>
                    </div>
                    
                    <!-- Year -->
                    <?php if ($year) : ?>
                    <div class="meta-item">
                        <span class="meta-label">سال:</span>
                        <span class="meta-value"><?php echo esc_html($year); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="novel-meta-divider"></div>
                    
                    <!-- Rating -->
                    <div class="meta-item meta-item--rating">
                        <div class="novel-stars" data-rating="<?php echo esc_attr($avg_rating); ?>">
                            <?php for ($i = 1; $i <= 5; $i++) : ?>
                                <?php if ($i <= floor($avg_rating)) : ?>
                                    <span class="star star-full">★</span>
                                <?php elseif ($i - 0.5 <= $avg_rating) : ?>
                                    <span class="star star-half">★</span>
                                <?php else : ?>
                                    <span class="star star-empty">☆</span>
                                <?php endif; ?>
                            <?php endfor; ?>
                            <span class="rating-number"><?php echo number_format($avg_rating, 1); ?></span>
                            <span class="rating-count">(<?php echo number_format_i18n($rating_count); ?> رأی)</span>
                        </div>
                    </div>
                    
                    <div class="novel-meta-divider"></div>
                    
                    <!-- Stats -->
                    <div class="novel-stats-row">
                        <span class="stat-item" title="تعداد قسمت‌ها">
                            📖 <?php echo number_format_i18n($chapter_counts['total']); ?> قسمت
                            <?php if ($total_chapters) : ?>
                                <small>(از <?php echo number_format_i18n($total_chapters); ?>)</small>
                            <?php endif; ?>
                        </span>
                        
                        <?php if ($chapter_counts['free'] > 0 || $chapter_counts['vip'] > 0) : ?>
                        <span class="stat-item stat-free" title="رایگان">
                            ✅ <?php echo number_format_i18n($chapter_counts['free']); ?> رایگان
                        </span>
                        <?php if ($chapter_counts['vip'] > 0) : ?>
                        <span class="stat-item stat-vip" title="VIP">
                            🔒 <?php echo number_format_i18n($chapter_counts['vip']); ?> VIP
                        </span>
                        <?php endif; ?>
                        <?php endif; ?>
                        
                        <span class="stat-item" title="دیدگاه‌ها">
                            💬 <?php echo number_format_i18n($comment_count); ?> دیدگاه
                        </span>
                        <span class="stat-item" title="بازدید">
                            👁 <?php echo number_format_i18n($new_views); ?> بازدید
                        </span>
                        <span class="stat-item" title="دنبال‌کنندگان">
                            ❤ <?php echo number_format_i18n($follow_count); ?> دنبال‌کننده
                        </span>
                        <span class="stat-item" title="بوکمارک">
                            📚 <?php echo number_format_i18n($bookmark_count); ?> بوکمارک
                        </span>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="novel-actions">
                    <!-- Follow Button -->
                    <button class="btn-novel-follow <?php echo $is_following ? 'following' : ''; ?>" 
                            data-novel-id="<?php echo $novel_id; ?>"
                            <?php echo !$is_logged_in ? 'data-requires-login="true"' : ''; ?>>
                        <span class="follow-icon"><?php echo $is_following ? '❤' : '🤍'; ?></span>
                        <span class="follow-text"><?php echo $is_following ? 'دنبال می‌کنید ✓' : 'دنبال کردن'; ?></span>
                        <span class="follow-count"><?php echo number_format_i18n($follow_count); ?></span>
                    </button>
                    
                    <!-- Library Dropdown -->
                    <div class="novel-library-dropdown">
                        <button class="btn-novel-library <?php echo $library_status ? 'in-library' : ''; ?>"
                                <?php echo !$is_logged_in ? 'data-requires-login="true"' : ''; ?>>
                            <span class="library-icon">📚</span>
                            <span class="library-text">
                                <?php 
                                $statuses_labels = [
                                    'reading'    => '📖 در حال خواندن',
                                    'plan'       => '📋 می‌خوام بخوانم',
                                    'completed'  => '✅ تکمیل شده',
                                    'on_hold'    => '⏸ نگه‌داشته',
                                    'dropped'    => '❌ رها شده',
                                ];
                                echo $library_status && isset($statuses_labels[$library_status]) 
                                     ? $statuses_labels[$library_status] 
                                     : 'افزودن به کتابخانه';
                                ?>
                            </span>
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="library-dropdown-menu">
                            <?php foreach ($statuses_labels as $key => $label) : ?>
                                <button class="library-option <?php echo $library_status === $key ? 'active' : ''; ?>"
                                        data-status="<?php echo esc_attr($key); ?>"
                                        data-novel-id="<?php echo $novel_id; ?>">
                                    <?php echo $label; ?>
                                    <?php if ($library_status === $key) : ?>
                                        <span class="check-mark">✓</span>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                            <?php if ($library_status) : ?>
                                <button class="library-option library-remove" 
                                        data-status="remove" 
                                        data-novel-id="<?php echo $novel_id; ?>">
                                    🗑 حذف از کتابخانه
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Share Button -->
                    <button class="btn-novel-share" data-url="<?php echo esc_url(get_permalink()); ?>" data-title="<?php echo esc_attr(get_the_title()); ?>">
                        🔗 اشتراک‌گذاری
                    </button>
                </div>
                
                <!-- Continue / Start Reading -->
                <div class="novel-reading-cta">
                    <?php if ($last_read && isset($last_read_num)) : ?>
                        <a href="<?php echo novel_get_chapter_permalink($last_read); ?>" class="btn-continue-reading">
                            ▶ ادامه مطالعه: قسمت <?php echo esc_html($last_read_num); ?>
                        </a>
                    <?php else : 
                        // Get first chapter
                        $first_ch = novel_get_chapters($novel_id, ['posts_per_page' => 1, 'order' => 'ASC']);
                        if ($first_ch->have_posts()) :
                            $first_ch->the_post();
                    ?>
                        <a href="<?php echo novel_get_chapter_permalink(get_the_ID()); ?>" class="btn-start-reading">
                            📖 شروع مطالعه
                        </a>
                    <?php 
                            wp_reset_postdata();
                        endif; 
                    endif; ?>
                </div>
            </div>
        </section>
        
        <!-- ═══ بخش ۲: خلاصه رمان ═══ -->
        <section class="novel-synopsis" id="synopsis">
            <h2 class="section-title">📝 خلاصه رمان</h2>
            <div class="synopsis-content" id="synopsisContent">
                <div class="synopsis-text">
                    <?php 
                    $content = get_the_content();
                    $excerpt = get_the_excerpt();
                    $text = $content ?: $excerpt;
                    echo wp_kses_post($text);
                    ?>
                </div>
                <?php if (mb_strlen(wp_strip_all_tags($text)) > 300) : ?>
                    <div class="synopsis-fade"></div>
                    <button class="btn-synopsis-toggle" id="btnSynopsisToggle">
                        <span class="toggle-more">ادامه... ▼</span>
                        <span class="toggle-less" style="display:none;">بستن ▲</span>
                    </button>
                <?php endif; ?>
            </div>
            
            <!-- Genres -->
            <?php if (!empty($genres) && !is_wp_error($genres)) : ?>
            <div class="novel-genres">
                <span class="genres-label">ژانرها:</span>
                <?php foreach ($genres as $genre) : ?>
                    <?php echo novel_get_genre_badge($genre); ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Tags -->
            <?php if (!empty($tags) && !is_wp_error($tags)) : ?>
            <div class="novel-tags">
                <span class="tags-label">تگ‌ها:</span>
                <?php foreach ($tags as $tag) : ?>
                    <a href="<?php echo esc_url(get_term_link($tag)); ?>" class="novel-tag-badge">
                        <?php echo esc_html($tag->name); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
        
        <!-- ═══ بخش ۳: لینک‌های مرتبط ═══ -->
        <?php if ($has_anime || $has_manga || (!empty($custom_links) && is_array($custom_links))) : ?>
        <section class="novel-related-links">
            <h2 class="section-title">🔗 لینک‌های مرتبط</h2>
            <div class="related-links-grid">
                <?php if ($has_anime && $anime_url) : ?>
                    <a href="<?php echo esc_url($anime_url); ?>" class="related-link-btn" target="_blank" rel="noopener">
                        🎬 مشاهده انیمه
                    </a>
                <?php endif; ?>
                <?php if ($has_manga && $manga_url) : ?>
                    <a href="<?php echo esc_url($manga_url); ?>" class="related-link-btn" target="_blank" rel="noopener">
                        📚 مشاهده مانگا
                    </a>
                <?php endif; ?>
                <?php if (is_array($custom_links)) : ?>
                    <?php foreach ($custom_links as $link) : ?>
                        <a href="<?php echo esc_url($link['url']); ?>" class="related-link-btn" target="_blank" rel="noopener">
                            🔗 <?php echo esc_html($link['name']); ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>
        <?php
            /*فاز 15*/
            /**
             * نمونه کد اضافه شونده به single-novel.php
             * برای نمایش بنر catch-up
             */

            // === قبل از لیست قسمت‌ها اضافه شود ===

            // بنر Catch-up (اگر کاربر مدتی نخوانده)
            if (class_exists('Novel_Smart_Features') && is_user_logged_in()) {
                Novel_Smart_Features::get_instance()->render_catchup_banner(get_the_ID());
            }
        ?>
        <!-- ═══ بخش ۴: لیست قسمت‌ها ═══ -->
        <section class="novel-chapters" id="chapters">
            <div class="chapters-header">
                <h2 class="section-title">📋 لیست قسمت‌ها (<?php echo number_format_i18n($chapter_counts['total']); ?>)</h2>
                
                <div class="chapters-controls">
                    <!-- Filter -->
                    <div class="chapters-filter">
                        <button class="filter-btn active" data-filter="all">همه</button>
                        <button class="filter-btn" data-filter="free">رایگان</button>
                        <?php if ($chapter_counts['vip'] > 0) : ?>
                            <button class="filter-btn" data-filter="vip">VIP</button>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Sort -->
                    <div class="chapters-sort">
                        <button class="sort-btn active" data-sort="asc" title="صعودی">↑ صعودی</button>
                        <button class="sort-btn" data-sort="desc" title="نزولی">↓ نزولی</button>
                    </div>
                    
                    <!-- Quick Search -->
                    <div class="chapters-search">
                        <input type="number" 
                               class="chapter-search-input" 
                               id="chapterSearchInput"
                               placeholder="شماره قسمت..." 
                               min="1" />
                        <button class="chapter-search-btn" id="chapterSearchBtn" title="رفتن به قسمت">🔍</button>
                    </div>
                </div>
            </div>
            
            <div class="chapters-list" id="chaptersList" 
                 data-novel-id="<?php echo $novel_id; ?>"
                 data-has-volumes="<?php echo $has_volumes ? '1' : '0'; ?>">
                
                <?php if ($has_volumes) : 
                    // Accordion view
                    $volumes_obj = new Novel_Volumes();
                    $grouped = $volumes_obj->get_chapters_by_volume($novel_id);
                    $first = true;
                ?>
                    <?php foreach ($grouped as $vol_id => $vol_data) : ?>
                    <div class="volume-accordion <?php echo $first ? 'expanded' : ''; ?>" 
                         data-volume-id="<?php echo esc_attr($vol_id); ?>">
                        <button class="volume-accordion__header" type="button">
                            <span class="volume-icon"><?php echo $first ? '▾' : '▸'; ?></span>
                            <span class="volume-title"><?php echo esc_html($vol_data['title']); ?></span>
                            <span class="volume-count">(<?php echo count($vol_data['chapters']); ?> قسمت)</span>
                        </button>
                        <div class="volume-accordion__body" <?php echo $first ? '' : 'style="display:none;"'; ?>>
                            <?php foreach ($vol_data['chapters'] as $ch_id) : ?>
                                <?php 
                                $GLOBALS['chapter_item_id'] = $ch_id;
                                get_template_part('templates/novel/chapter-list-item');
                                ?>
                            <?php endforeach; ?>
                            <?php if (empty($vol_data['chapters'])) : ?>
                                <p class="volume-empty">هنوز قسمتی در این جلد نیست.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php $first = false; endforeach; ?>
                    
                <?php else : 
                    // Simple list
                    $chapters = novel_get_chapters($novel_id, ['posts_per_page' => 50]);
                    if ($chapters->have_posts()) :
                        while ($chapters->have_posts()) :
                            $chapters->the_post();
                            $GLOBALS['chapter_item_id'] = get_the_ID();
                            get_template_part('templates/novel/chapter-list-item');
                        endwhile;
                        wp_reset_postdata();
                        
                        if ($chapters->max_num_pages > 1) :
                ?>
                        <div class="chapters-load-more-wrap">
                            <button class="btn-load-more-chapters" 
                                    data-page="2" 
                                    data-max="<?php echo $chapters->max_num_pages; ?>"
                                    data-novel-id="<?php echo $novel_id; ?>">
                                بارگذاری بیشتر...
                            </button>
                        </div>
                <?php 
                        endif;
                    else :
                ?>
                        <div class="chapters-empty">
                            <p>📖 هنوز قسمتی منتشر نشده است.</p>
                        </div>
                <?php 
                    endif;
                endif; ?>
            </div>
        </section>
        
        <!-- ═══ بخش ۵: رمان‌های مشابه ═══ -->
        <?php
        $genre_ids = wp_list_pluck($genres, 'term_id');
        $tag_ids   = wp_list_pluck($tags, 'term_id');
        
        $similar_args = [
            'post_type'      => 'novel',
            'posts_per_page' => 6,
            'post__not_in'   => [$novel_id],
            'post_status'    => 'publish',
            'meta_key'       => 'novel_views',
            'orderby'        => 'meta_value_num',
            'order'          => 'DESC',
        ];
        
        if (!empty($genre_ids) || !empty($tag_ids)) {
            $similar_args['tax_query'] = ['relation' => 'OR'];
            if (!empty($genre_ids)) {
                $similar_args['tax_query'][] = [
                    'taxonomy' => 'genre',
                    'field'    => 'term_id',
                    'terms'    => $genre_ids,
                ];
            }
            if (!empty($tag_ids)) {
                $similar_args['tax_query'][] = [
                    'taxonomy' => 'novel_tag',
                    'field'    => 'term_id',
                    'terms'    => $tag_ids,
                ];
            }
        }
        
        $similar = new WP_Query($similar_args);
        
        if ($similar->have_posts()) :
        ?>
        <section class="novel-similar">
            <h2 class="section-title">📚 رمان‌های مشابه</h2>
            <div class="similar-novels-grid">
                <?php while ($similar->have_posts()) : $similar->the_post(); ?>
                    <?php get_template_part('templates/novel/novel-card-small'); ?>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- ═══ بخش ۶: دیدگاه‌ها ═══ -->
        <section class="novel-comments-section" id="comments">
            <?php get_template_part('templates/comments/comments-section'); ?>
        </section>
        
        <!-- ═══ بخش ۷: اشتراک‌گذاری ═══ -->
        <section class="novel-share-section">
            <h2 class="section-title">📤 اشتراک‌گذاری</h2>
            <div class="share-buttons">
                <a href="https://t.me/share/url?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode(get_the_title()); ?>" 
                   class="share-btn share-telegram" target="_blank" rel="noopener" title="تلگرام">
                    <svg viewBox="0 0 24 24" width="20" height="20"><path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.46-.52-.19L7.74 13.3 3.64 12c-.88-.25-.89-.86.2-1.3l15.97-6.16c.73-.33 1.43.18 1.15 1.3l-2.72 12.81c-.19.91-.74 1.13-1.5.71L12.6 16.3l-1.99 1.93c-.23.23-.42.42-.83.42z" fill="currentColor"/></svg>
                    تلگرام
                </a>
                <a href="https://api.whatsapp.com/send?text=<?php echo urlencode(get_the_title() . ' ' . get_permalink()); ?>" 
                   class="share-btn share-whatsapp" target="_blank" rel="noopener" title="واتس‌اپ">
                    <svg viewBox="0 0 24 24" width="20" height="20"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z" fill="currentColor"/></svg>
                    واتس‌اپ
                </a>
                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode(get_the_title()); ?>" 
                   class="share-btn share-twitter" target="_blank" rel="noopener" title="توییتر">
                    <svg viewBox="0 0 24 24" width="20" height="20"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" fill="currentColor"/></svg>
                    توییتر
                </a>
                <button class="share-btn share-copy" 
                        data-url="<?php echo esc_url(get_permalink()); ?>" 
                        title="کپی لینک">
                    📋 کپی لینک
                </button>
            </div>
        </section>
        
    </div><!-- .container -->
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Synopsis toggle
    var btnToggle = document.getElementById('btnSynopsisToggle');
    if (btnToggle) {
        var synopsisContent = document.getElementById('synopsisContent');
        btnToggle.addEventListener('click', function() {
            synopsisContent.classList.toggle('expanded');
            var more = btnToggle.querySelector('.toggle-more');
            var less = btnToggle.querySelector('.toggle-less');
            if (synopsisContent.classList.contains('expanded')) {
                more.style.display = 'none';
                less.style.display = 'inline';
            } else {
                more.style.display = 'inline';
                less.style.display = 'none';
            }
        });
    }
    
    // Copy link
    document.querySelectorAll('.share-copy').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var url = this.dataset.url;
            navigator.clipboard.writeText(url).then(function() {
                if (typeof novelToast === 'function') {
                    novelToast('لینک کپی شد ✓', 'success');
                } else {
                    alert('لینک کپی شد ✓');
                }
            });
        });
    });
    
    // Follow button
    document.querySelectorAll('.btn-novel-follow').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if (this.dataset.requiresLogin === 'true') {
                window.location.href = '<?php echo wp_login_url(get_permalink()); ?>';
                return;
            }
            var novelId = this.dataset.novelId;
            var isFollowing = this.classList.contains('following');
            var self = this;
            
            fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=novel_toggle_follow&novel_id=' + novelId + '&nonce=<?php echo wp_create_nonce("novel_follow"); ?>'
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    self.classList.toggle('following');
                    var icon = self.querySelector('.follow-icon');
                    var text = self.querySelector('.follow-text');
                    var count = self.querySelector('.follow-count');
                    if (data.data.following) {
                        icon.textContent = '❤';
                        text.textContent = 'دنبال می‌کنید ✓';
                    } else {
                        icon.textContent = '🤍';
                        text.textContent = 'دنبال کردن';
                    }
                    count.textContent = data.data.count;
                }
            });
        });
    });
    
    // Library dropdown
    var libraryDropdown = document.querySelector('.novel-library-dropdown');
    if (libraryDropdown) {
        var libraryBtn = libraryDropdown.querySelector('.btn-novel-library');
        var libraryMenu = libraryDropdown.querySelector('.library-dropdown-menu');
        
        libraryBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (this.dataset.requiresLogin === 'true') {
                window.location.href = '<?php echo wp_login_url(get_permalink()); ?>';
                return;
            }
            libraryMenu.classList.toggle('show');
        });
        
        // Close on outside click
        document.addEventListener('click', function(e) {
            if (!libraryDropdown.contains(e.target)) {
                libraryMenu.classList.remove('show');
            }
        });
        
        // Library option click
        libraryMenu.querySelectorAll('.library-option').forEach(function(opt) {
            opt.addEventListener('click', function() {
                var status = this.dataset.status;
                var novelId = this.dataset.novelId;
                
                fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=novel_update_library&novel_id=' + novelId + '&status=' + status + '&nonce=<?php echo wp_create_nonce("novel_library"); ?>'
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        libraryMenu.classList.remove('show');
                        var textEl = libraryBtn.querySelector('.library-text');
                        if (data.data.removed) {
                            textEl.textContent = 'افزودن به کتابخانه';
                            libraryBtn.classList.remove('in-library');
                        } else {
                            textEl.textContent = data.data.label;
                            libraryBtn.classList.add('in-library');
                        }
                        // Update active state
                        libraryMenu.querySelectorAll('.library-option').forEach(function(o) {
                            o.classList.remove('active');
                            var cm = o.querySelector('.check-mark');
                            if (cm) cm.remove();
                        });
                        if (!data.data.removed) {
                            var activeOpt = libraryMenu.querySelector('[data-status="' + status + '"]');
                            if (activeOpt) {
                                activeOpt.classList.add('active');
                            }
                        }
                        if (typeof novelToast === 'function') {
                            novelToast(data.data.message, 'success');
                        }
                    }
                });
            });
        });
    }
    
    // Volume accordion
    document.querySelectorAll('.volume-accordion__header').forEach(function(header) {
        header.addEventListener('click', function() {
            var accordion = this.closest('.volume-accordion');
            var body = accordion.querySelector('.volume-accordion__body');
            var icon = this.querySelector('.volume-icon');
            var isExpanded = accordion.classList.contains('expanded');
            
            if (isExpanded) {
                body.style.maxHeight = body.scrollHeight + 'px';
                requestAnimationFrame(function() {
                    body.style.maxHeight = '0px';
                });
                accordion.classList.remove('expanded');
                icon.textContent = '▸';
            } else {
                body.style.display = 'block';
                body.style.maxHeight = '0px';
                requestAnimationFrame(function() {
                    body.style.maxHeight = body.scrollHeight + 'px';
                });
                accordion.classList.add('expanded');
                icon.textContent = '▾';
                
                // Save state
                try {
                    var volId = accordion.dataset.volumeId;
                    var key = 'novel_vol_' + <?php echo $novel_id; ?>;
                    var states = JSON.parse(localStorage.getItem(key) || '{}');
                    states[volId] = true;
                    localStorage.setItem(key, JSON.stringify(states));
                } catch(e) {}
            }
            
            setTimeout(function() {
                if (accordion.classList.contains('expanded')) {
                    body.style.maxHeight = 'none';
                } else {
                    body.style.display = 'none';
                }
            }, 350);
        });
    });
    
    // Restore accordion states from localStorage
    try {
        var key = 'novel_vol_' + <?php echo $novel_id; ?>;
        var states = JSON.parse(localStorage.getItem(key) || '{}');
        document.querySelectorAll('.volume-accordion').forEach(function(acc) {
            var volId = acc.dataset.volumeId;
            if (states[volId] === false) {
                acc.classList.remove('expanded');
                var body = acc.querySelector('.volume-accordion__body');
                body.style.display = 'none';
                acc.querySelector('.volume-icon').textContent = '▸';
            }
        });
    } catch(e) {}
    
    // Chapter filter
    document.querySelectorAll('.chapters-filter .filter-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.chapters-filter .filter-btn').forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');
            var filter = this.dataset.filter;
            document.querySelectorAll('.chapter-list-item').forEach(function(item) {
                if (filter === 'all') {
                    item.style.display = '';
                } else if (filter === 'free') {
                    item.style.display = item.dataset.vip === '0' ? '' : 'none';
                } else if (filter === 'vip') {
                    item.style.display = item.dataset.vip === '1' ? '' : 'none';
                }
            });
        });
    });
    
    // Chapter sort
    document.querySelectorAll('.chapters-sort .sort-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.chapters-sort .sort-btn').forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');
            var sort = this.dataset.sort;
            var list = document.getElementById('chaptersList');
            
            // Sort all volume bodies or the main list
            var containers = list.querySelectorAll('.volume-accordion__body');
            if (containers.length === 0) {
                containers = [list];
            }
            
            containers.forEach(function(container) {
                var items = Array.from(container.querySelectorAll('.chapter-list-item'));
                items.sort(function(a, b) {
                    var numA = parseInt(a.dataset.number);
                    var numB = parseInt(b.dataset.number);
                    return sort === 'asc' ? numA - numB : numB - numA;
                });
                items.forEach(function(item) {
                    container.appendChild(item);
                });
            });
        });
    });
    
    // Quick chapter search
    document.getElementById('chapterSearchBtn').addEventListener('click', function() {
        var num = document.getElementById('chapterSearchInput').value;
        if (!num) return;
        var item = document.querySelector('.chapter-list-item[data-number="' + num + '"]');
        if (item) {
            // Expand parent volume if collapsed
            var accordion = item.closest('.volume-accordion');
            if (accordion && !accordion.classList.contains('expanded')) {
                accordion.querySelector('.volume-accordion__header').click();
            }
            setTimeout(function() {
                item.scrollIntoView({ behavior: 'smooth', block: 'center' });
                item.classList.add('highlight');
                setTimeout(function() { item.classList.remove('highlight'); }, 2000);
            }, 400);
        } else {
            if (typeof novelToast === 'function') {
                novelToast('قسمت ' + num + ' یافت نشد', 'error');
            }
        }
    });
    
    document.getElementById('chapterSearchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            document.getElementById('chapterSearchBtn').click();
        }
    });
});
</script>

<?php endwhile; ?>

<?php get_footer(); ?>