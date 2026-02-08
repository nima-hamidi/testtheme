<?php
/**
 * صفحه خواندن فصل - Chapter Reader ⭐
 * مهم‌ترین صفحه قالب: شامل ریدر، تنظیمات خواندن، ناوبری، پیشرفت
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// اطلاعات فصل
$chapter_id     = get_the_ID();
$chapter_number = get_post_meta( $chapter_id, '_fn_chapter_number', true );
$novel_id       = get_post_meta( $chapter_id, '_fn_parent_novel', true );
$novel_title    = $novel_id ? get_the_title( $novel_id ) : '';
$novel_url      = $novel_id ? get_permalink( $novel_id ) : '#';
$cover_url      = $novel_id ? get_the_post_thumbnail_url( $novel_id, 'novel-cover-sm' ) : '';

// فصل‌های مجاور
$prev_chapter = fn_get_adjacent_chapter( $chapter_id, 'prev' );
$next_chapter = fn_get_adjacent_chapter( $chapter_id, 'next' );

// تعداد کل فصل‌ها
$total_chapters = $novel_id ? fn_get_chapter_count( $novel_id ) : 0;

// محتوای فصل
$chapter_content = get_the_content();

// تعداد کلمات و زمان تقریبی خواندن
$word_count    = str_word_count( strip_tags( $chapter_content ) );
$reading_time  = max( 1, ceil( $word_count / 250 ) ); // ۲۵۰ کلمه در دقیقه

// ثبت بازدید
do_action( 'fn_count_view', $chapter_id );

// ذخیره پیشرفت خواندن (سرور)
if ( is_user_logged_in() && $novel_id ) {
    global $wpdb;
    $table   = $wpdb->prefix . 'fn_reading_progress';
    $user_id = get_current_user_id();

    $existing = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM $table WHERE user_id = %d AND novel_id = %d",
        $user_id, $novel_id
    ) );

    if ( $existing ) {
        $wpdb->update(
            $table,
            array( 'chapter_id' => $chapter_id ),
            array( 'user_id' => $user_id, 'novel_id' => $novel_id ),
            array( '%d' ),
            array( '%d', '%d' )
        );
    } else {
        $wpdb->insert( $table, array(
            'user_id'    => $user_id,
            'novel_id'   => $novel_id,
            'chapter_id' => $chapter_id,
        ), array( '%d', '%d', '%d' ) );
    }
}

get_header();
?>

<!-- نوار پیشرفت (قبلاً در header اضافه شده) -->

<div class="fn-reader-wrapper" id="fnReaderWrapper">

    <!-- ===== Breadcrumb ===== -->
    <div class="fn-container">
        <?php fn_breadcrumb(); ?>
    </div>

    <!-- ===== ناوبری بالای فصل ===== -->
    <div class="fn-container">
        <nav class="fn-chapter-nav fn-chapter-nav--top" aria-label="ناوبری فصل">
            <?php if ( $prev_chapter ) : ?>
                <a href="<?php echo esc_url( get_permalink( $prev_chapter->ID ) ); ?>"
                   class="fn-chapter-nav__btn fn-chapter-nav__btn--prev"
                   title="فصل <?php echo esc_attr( get_post_meta( $prev_chapter->ID, '_fn_chapter_number', true ) ); ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                    فصل قبلی
                </a>
            <?php else : ?>
                <span class="fn-chapter-nav__btn fn-chapter-nav__btn--disabled">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                    اولین فصل
                </span>
            <?php endif; ?>

            <a href="<?php echo esc_url( $novel_url . '#chapters' ); ?>"
               class="fn-chapter-nav__btn fn-chapter-nav__btn--list"
               title="فهرست فصل‌ها">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                فهرست
            </a>

            <?php if ( $next_chapter ) : ?>
                <a href="<?php echo esc_url( get_permalink( $next_chapter->ID ) ); ?>"
                   class="fn-chapter-nav__btn fn-chapter-nav__btn--next"
                   title="فصل <?php echo esc_attr( get_post_meta( $next_chapter->ID, '_fn_chapter_number', true ) ); ?>">
                    فصل بعدی
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                </a>
            <?php else : ?>
                <span class="fn-chapter-nav__btn fn-chapter-nav__btn--disabled">
                    آخرین فصل
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                </span>
            <?php endif; ?>
        </nav>
    </div>


    <!-- ===== محتوای ریدر ===== -->
    <article class="fn-reader" id="fnReader">

        <!-- هدر فصل -->
        <header class="fn-reader__header">
            <a href="<?php echo esc_url( $novel_url ); ?>" class="fn-reader__novel-title">
                <?php if ( $cover_url ) : ?>
                    <img src="<?php echo esc_url( $cover_url ); ?>" alt="" class="fn-reader__novel-cover-sm" loading="lazy">
                <?php endif; ?>
                <?php echo esc_html( $novel_title ); ?>
            </a>

            <h1 class="fn-reader__chapter-title">
                فصل <?php echo esc_html( $chapter_number ); ?>
                <?php if ( get_the_title() !== $novel_title ) : ?>
                    <span class="fn-reader__chapter-subtitle">
                        — <?php the_title(); ?>
                    </span>
                <?php endif; ?>
            </h1>

            <!-- اطلاعات فصل -->
            <div class="fn-reader__meta">
                <span class="fn-reader__meta-item">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <?php echo fn_persian_number( $reading_time ); ?> دقیقه مطالعه
                </span>
                <span class="fn-reader__meta-item">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
                    <?php echo fn_persian_number( fn_format_number( $word_count ) ); ?> کلمه
                </span>
                <span class="fn-reader__meta-item">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <?php echo fn_time_ago( get_the_time( 'U' ) ); ?>
                </span>
                <span class="fn-reader__meta-item">
                    فصل <?php echo esc_html( $chapter_number ); ?> از <?php echo esc_html( $total_chapters ); ?>
                </span>
            </div>
        </header>

        <!-- محتوای اصلی فصل -->
        <div class="fn-reader__content" id="fnReaderContent">
            <?php
            // اعمال فیلتر محتوا
            echo apply_filters( 'the_content', $chapter_content );
            ?>
        </div>

        <!-- پایان فصل -->
        <footer class="fn-reader__footer">
            <div class="fn-reader__end-mark">
                <span>— پایان فصل <?php echo esc_html( $chapter_number ); ?> —</span>
            </div>

            <?php if ( $next_chapter ) : ?>
                <div class="fn-reader__next-preview">
                    <p class="fn-reader__next-label">فصل بعدی:</p>
                    <a href="<?php echo esc_url( get_permalink( $next_chapter->ID ) ); ?>" class="fn-reader__next-link">
                        <span class="fn-reader__next-number">
                            فصل <?php echo esc_html( get_post_meta( $next_chapter->ID, '_fn_chapter_number', true ) ); ?>
                        </span>
                        <span class="fn-reader__next-title"><?php echo esc_html( $next_chapter->post_title ); ?></span>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                    </a>
                </div>
            <?php else : ?>
                <div class="fn-reader__completed">
                    <div class="fn-reader__completed-icon">🎉</div>
                    <h3>تبریک!</h3>
                    <p>شما تمام فصل‌های منتشر شده را خوانده‌اید.</p>
                    <div class="fn-reader__completed-actions">
                        <a href="<?php echo esc_url( $novel_url ); ?>" class="fn-btn fn-btn--primary">
                            بازگشت به صفحه رمان
                        </a>
                        <?php if ( ! fn_is_bookmarked( $novel_id ) && is_user_logged_in() ) : ?>
                            <button class="fn-bookmark-btn" data-novel-id="<?php echo esc_attr( $novel_id ); ?>">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                                <span class="fn-bookmark-btn__text">افزودن به کتابخانه</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </footer>
    </article>


    <!-- ===== ناوبری پایین فصل ===== -->
    <div class="fn-container">
        <nav class="fn-chapter-nav fn-chapter-nav--bottom" aria-label="ناوبری فصل">
            <?php if ( $prev_chapter ) : ?>
                <a href="<?php echo esc_url( get_permalink( $prev_chapter->ID ) ); ?>"
                   class="fn-chapter-nav__btn fn-chapter-nav__btn--prev">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                    فصل قبلی
                </a>
            <?php else : ?>
                <span class="fn-chapter-nav__btn fn-chapter-nav__btn--disabled">اولین فصل</span>
            <?php endif; ?>

            <a href="<?php echo esc_url( $novel_url . '#chapters' ); ?>"
               class="fn-chapter-nav__btn fn-chapter-nav__btn--list">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                فهرست
            </a>

            <?php if ( $next_chapter ) : ?>
                <a href="<?php echo esc_url( get_permalink( $next_chapter->ID ) ); ?>"
                   class="fn-chapter-nav__btn fn-chapter-nav__btn--next">
                    فصل بعدی
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                </a>
            <?php else : ?>
                <span class="fn-chapter-nav__btn fn-chapter-nav__btn--disabled">آخرین فصل</span>
            <?php endif; ?>
        </nav>
    </div>


    <!-- ===== نظرات فصل ===== -->
    <div class="fn-container">
        <div class="fn-reader-comments fn-fade-in">
            <h2 class="fn-novel-section__title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                نظرات فصل
                <span class="fn-chapter-list__count">(<?php echo get_comments_number(); ?>)</span>
            </h2>
            <?php
            if ( comments_open() || get_comments_number() ) {
                comments_template();
            }
            ?>
        </div>
    </div>

</div><!-- /fn-reader-wrapper -->


<!-- ===== دکمه‌های شناور (FAB) ===== -->
<div class="fn-reader-fab" id="fnReaderFab">
    <!-- فصل قبلی -->
    <?php if ( $prev_chapter ) : ?>
        <a href="<?php echo esc_url( get_permalink( $prev_chapter->ID ) ); ?>"
           class="fn-btn--icon fn-fab-btn" title="فصل قبلی">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
    <?php endif; ?>

    <!-- تنظیمات -->
    <button class="fn-btn--icon fn-fab-btn fn-fab-btn--settings" id="fnSettingsToggle" title="تنظیمات خواندن">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="3"/>
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68 1.65 1.65 0 0 0 10 3.17V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
        </svg>
    </button>

    <!-- تمام‌صفحه -->
    <button class="fn-btn--icon fn-fab-btn" id="fnFullscreenToggle" title="حالت تمام‌صفحه">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" id="fnFullscreenIcon">
            <polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/>
        </svg>
    </button>

    <!-- فصل بعدی -->
    <?php if ( $next_chapter ) : ?>
        <a href="<?php echo esc_url( get_permalink( $next_chapter->ID ) ); ?>"
           class="fn-btn--icon fn-fab-btn" title="فصل بعدی">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
    <?php endif; ?>
</div>


<!-- ===== پنل تنظیمات خواندن ===== -->
<?php get_template_part( 'template-parts/reading-settings-panel' ); ?>


<!-- ===== پنل فهرست فصل‌ها (Drawer) ===== -->
<div class="fn-chapter-drawer" id="fnChapterDrawer">
    <div class="fn-chapter-drawer__overlay" id="fnDrawerOverlay"></div>
    <div class="fn-chapter-drawer__panel">
        <div class="fn-chapter-drawer__header">
            <h3>فهرست فصل‌ها</h3>
            <button class="fn-chapter-drawer__close" id="fnDrawerClose">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="fn-chapter-drawer__body" id="fnDrawerBody">
            <!-- لود با AJAX -->
            <div class="fn-chapter-drawer__loading">در حال بارگذاری...</div>
        </div>
    </div>
</div>


<!-- داده‌های مورد نیاز جاوااسکریپت -->
<script>
    // داده‌های فصل فعلی
    window.FN_CHAPTER = {
        chapterId: <?php echo $chapter_id; ?>,
        novelId: <?php echo $novel_id ?: 0; ?>,
        chapterNumber: <?php echo floatval( $chapter_number ); ?>,
        totalChapters: <?php echo $total_chapters; ?>,
        prevChapterUrl: <?php echo $prev_chapter ? '"' . esc_url( get_permalink( $prev_chapter->ID ) ) . '"' : 'null'; ?>,
        nextChapterUrl: <?php echo $next_chapter ? '"' . esc_url( get_permalink( $next_chapter->ID ) ) . '"' : 'null'; ?>,
        novelUrl: '<?php echo esc_url( $novel_url ); ?>',
    };
</script>

<?php get_footer(); ?>