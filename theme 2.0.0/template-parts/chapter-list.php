<?php
/**
 * لیست فصل‌ها (قابل استفاده مجدد)
 * 
 * استفاده: get_template_part('template-parts/chapter-list');
 * نیاز: $novel_id باید در global set شده باشد یا از get_the_ID() بیاید
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$novel_id      = $novel_id ?? get_the_ID();
$chapter_count = fn_get_chapter_count( $novel_id );

// پیشرفت خواندن کاربر
$reading_progress = null;
$progress_ch_num  = 0;
if ( is_user_logged_in() ) {
    $reading_progress = fn_get_reading_progress( get_current_user_id(), $novel_id );
    if ( $reading_progress && $reading_progress->chapter_id ) {
        $progress_ch_num = floatval( get_post_meta( $reading_progress->chapter_id, '_fn_chapter_number', true ) );
    }
}
?>

<div class="fn-chapter-list" id="fnChapterList">
    <div class="fn-chapter-list__header">
        <h2 class="fn-novel-section__title" style="margin: 0;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/>
                <line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/>
                <line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
            </svg>
            فهرست فصل‌ها
            <span class="fn-chapter-list__count">(<?php echo fn_format_number( $chapter_count ); ?>)</span>
        </h2>

        <div class="fn-chapter-list__controls">
            <div class="fn-chapter-search">
                <input type="text" id="fnChapterSearch" placeholder="جستجوی فصل..." class="fn-chapter-search__input">
            </div>
            <button class="fn-chapter-sort-btn" id="fnChapterSortBtn" title="تغییر ترتیب">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
                <span id="fnSortLabel">نزولی</span>
            </button>
        </div>
    </div>

    <div class="fn-chapter-list__body" id="fnChapterListBody">
        <?php
        $chapters = new WP_Query( array(
            'post_type'      => 'chapter',
            'posts_per_page' => 100,
            'orderby'        => 'meta_value_num',
            'meta_key'       => '_fn_chapter_number',
            'order'          => 'DESC',
            'post_status'    => 'publish',
            'meta_query'     => array(
                array(
                    'key'   => '_fn_parent_novel',
                    'value' => $novel_id,
                    'type'  => 'NUMERIC',
                ),
            ),
        ) );

        if ( $chapters->have_posts() ) :
            while ( $chapters->have_posts() ) :
                $chapters->the_post();
                $ch_id     = get_the_ID();
                $ch_number = get_post_meta( $ch_id, '_fn_chapter_number', true );
                $ch_title  = get_the_title();
                $ch_date   = get_the_time( 'U' );

                $is_read = ( $progress_ch_num > 0 && floatval( $ch_number ) <= $progress_ch_num );
        ?>
            <a href="<?php the_permalink(); ?>"
               class="fn-chapter-item <?php echo $is_read ? 'fn-chapter-item--read' : ''; ?>"
               data-chapter-number="<?php echo esc_attr( $ch_number ); ?>">
                <div class="fn-chapter-item__right">
                    <?php if ( $is_read ) : ?>
                        <span class="fn-chapter-item__check">✓</span>
                    <?php endif; ?>
                    <span class="fn-chapter-item__number">فصل <?php echo esc_html( $ch_number ); ?></span>
                    <span class="fn-chapter-item__title"><?php echo esc_html( $ch_title ); ?></span>
                </div>
                <span class="fn-chapter-item__date"><?php echo fn_time_ago( $ch_date ); ?></span>
            </a>
        <?php
            endwhile;
            wp_reset_postdata();
        else :
        ?>
            <div class="fn-chapter-list__empty">
                <p>هنوز فصلی منتشر نشده است.</p>
            </div>
        <?php endif; ?>
    </div>

    <?php if ( $chapter_count > 100 ) : ?>
        <div class="fn-chapter-list__footer">
            <button class="fn-btn fn-btn--ghost fn-btn--full" id="fnLoadMoreChapters"
                    data-novel-id="<?php echo esc_attr( $novel_id ); ?>"
                    data-page="2"
                    data-total="<?php echo ceil( $chapter_count / 100 ); ?>">
                بارگذاری فصل‌های بیشتر
            </button>
        </div>
    <?php endif; ?>
</div>