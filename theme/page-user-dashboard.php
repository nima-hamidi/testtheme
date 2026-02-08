<?php
/**
 * Template Name: داشبورد کاربر
 * صفحه داشبورد: کتابخانه، تاریخچه، تنظیمات
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// اگر لاگین نیست، به صفحه ورود برو
if ( ! is_user_logged_in() ) {
    wp_redirect( wp_login_url( get_permalink() ) );
    exit;
}

get_header();

$user         = wp_get_current_user();
$user_id      = $user->ID;
$current_tab  = sanitize_text_field( $_GET['tab'] ?? 'bookmarks' );

// دریافت داده‌ها بر اساس تب
$bookmarks_data = fn_get_user_bookmarks( $user_id, 20, max( 1, absint( $_GET['bp'] ?? 1 ) ) );
$history_data   = fn_get_reading_history( $user_id, 20, max( 1, absint( $_GET['hp'] ?? 1 ) ) );
?>

<div class="fn-container">
    <?php fn_breadcrumb(); ?>

    <div class="fn-dashboard">

        <!-- ===== سایدبار ===== -->
        <aside class="fn-dashboard__sidebar">
            <!-- پروفایل -->
            <div class="fn-dashboard__profile">
                <div class="fn-dashboard__avatar">
                    <?php echo get_avatar( $user_id, 80 ); ?>
                </div>
                <h3 class="fn-dashboard__name"><?php echo esc_html( $user->display_name ); ?></h3>
                <p class="fn-dashboard__email"><?php echo esc_html( $user->user_email ); ?></p>
                <div class="fn-dashboard__stats-row">
                    <div class="fn-dashboard__stat-mini">
                        <strong><?php echo fn_format_number( $bookmarks_data['total'] ); ?></strong>
                        <small>بوکمارک</small>
                    </div>
                    <div class="fn-dashboard__stat-mini">
                        <strong><?php echo fn_format_number( $history_data['total'] ); ?></strong>
                        <small>خوانده</small>
                    </div>
                </div>
            </div>

            <!-- منوی ناوبری -->
            <nav class="fn-dashboard__nav">
                <a href="?tab=bookmarks" class="fn-dashboard__nav-item <?php echo $current_tab === 'bookmarks' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                    کتابخانه من
                    <span class="fn-dashboard__nav-count"><?php echo $bookmarks_data['total']; ?></span>
                </a>
                <a href="?tab=history" class="fn-dashboard__nav-item <?php echo $current_tab === 'history' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    تاریخچه خواندن
                    <span class="fn-dashboard__nav-count"><?php echo $history_data['total']; ?></span>
                </a>
                <?php if ( current_user_can( 'edit_posts' ) ) : ?>
                    <a href="?tab=my-novels" class="fn-dashboard__nav-item <?php echo $current_tab === 'my-novels' ? 'active' : ''; ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                        رمان‌های من
                        <span class="fn-dashboard__nav-count" id="fnMyNovelsCount">0</span>
                    </a>
                <?php endif; ?>
                <a href="?tab=settings" class="fn-dashboard__nav-item <?php echo $current_tab === 'settings' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33z"/></svg>
                    تنظیمات حساب
                </a>
                <div class="fn-dashboard__nav-divider"></div>
                <a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="fn-dashboard__nav-item fn-dashboard__nav-item--danger">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    خروج از حساب
                </a>
            </nav>
        </aside>

        <!-- ===== محتوای اصلی ===== -->
        <div class="fn-dashboard__content">

            <!-- تب: کتابخانه من -->
            <?php if ( $current_tab === 'bookmarks' ) : ?>
                <div class="fn-dashboard__section">
                    <div class="fn-dashboard__section-header">
                        <h2 class="fn-section__title">📚 کتابخانه من</h2>
                    </div>

                    <?php if ( ! empty( $bookmarks_data['novel_ids'] ) ) : ?>
                        <div class="fn-novels-grid fn-novels-grid--dashboard">
                            <?php
                            $bookmark_query = new WP_Query( array(
                                'post_type'      => 'novel',
                                'post__in'       => $bookmarks_data['novel_ids'],
                                'posts_per_page' => count( $bookmarks_data['novel_ids'] ),
                                'orderby'        => 'post__in',
                                'post_status'    => 'publish',
                            ) );

                            if ( $bookmark_query->have_posts() ) :
                                while ( $bookmark_query->have_posts() ) :
                                    $bookmark_query->the_post();
                                    $novel_id    = get_the_ID();
                                    $progress    = fn_get_reading_progress( $user_id, $novel_id );
                                    $total_ch    = fn_get_chapter_count( $novel_id );
                                    $current_ch  = 0;
                                    $progress_pct = 0;

                                    if ( $progress && $progress->chapter_id ) {
                                        $current_ch  = floatval( get_post_meta( $progress->chapter_id, '_fn_chapter_number', true ) );
                                        $progress_pct = $total_ch > 0 ? round( ( $current_ch / $total_ch ) * 100 ) : 0;
                                    }
                            ?>
                                <div class="fn-bookmark-card">
                                    <a href="<?php the_permalink(); ?>" class="fn-bookmark-card__inner">
                                        <div class="fn-bookmark-card__cover">
                                            <?php if ( has_post_thumbnail() ) : ?>
                                                <img src="<?php echo esc_url( get_the_post_thumbnail_url( $novel_id, 'novel-cover-sm' ) ); ?>"
                                                     alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy">
                                            <?php endif; ?>
                                        </div>
                                        <div class="fn-bookmark-card__info">
                                            <h3 class="fn-bookmark-card__title"><?php the_title(); ?></h3>

                                            <?php if ( $progress && $progress->chapter_id ) : ?>
                                                <p class="fn-bookmark-card__progress-text">
                                                    فصل <?php echo esc_html( $current_ch ); ?> از <?php echo esc_html( $total_ch ); ?>
                                                </p>
                                                <div class="fn-bookmark-card__progress-bar">
                                                    <div class="fn-bookmark-card__progress-fill" style="width: <?php echo $progress_pct; ?>%"></div>
                                                </div>
                                                <span class="fn-bookmark-card__progress-pct"><?php echo $progress_pct; ?>%</span>
                                            <?php else : ?>
                                                <p class="fn-bookmark-card__progress-text">هنوز شروع نشده</p>
                                            <?php endif; ?>

                                            <div class="fn-bookmark-card__meta">
                                                <?php
                                                $latest = fn_get_latest_chapter( $novel_id );
                                                if ( $latest ) :
                                                    $latest_num = get_post_meta( $latest->ID, '_fn_chapter_number', true );
                                                ?>
                                                    <span class="fn-bookmark-card__update">
                                                        آخرین: فصل <?php echo esc_html( $latest_num ); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </a>

                                    <!-- دکمه‌های عملیات -->
                                    <div class="fn-bookmark-card__actions">
                                        <?php if ( $progress && $progress->chapter_id ) : ?>
                                            <a href="<?php echo esc_url( get_permalink( $progress->chapter_id ) ); ?>"
                                               class="fn-btn fn-btn--primary fn-btn--sm">ادامه</a>
                                        <?php elseif ( $first_ch = fn_get_first_chapter( $novel_id ) ) : ?>
                                            <a href="<?php echo esc_url( get_permalink( $first_ch->ID ) ); ?>"
                                               class="fn-btn fn-btn--primary fn-btn--sm">شروع</a>
                                        <?php endif; ?>

                                        <button class="fn-btn fn-btn--ghost fn-btn--sm fn-bookmark-btn bookmarked"
                                                data-novel-id="<?php echo esc_attr( $novel_id ); ?>"
                                                title="حذف از کتابخانه">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                                        </button>
                                    </div>
                                </div>
                            <?php
                                endwhile;
                                wp_reset_postdata();
                            endif;
                            ?>
                        </div>

                        <!-- صفحه‌بندی -->
                        <?php if ( $bookmarks_data['pages'] > 1 ) : ?>
                            <nav class="fn-pagination">
                                <?php
                                echo paginate_links( array(
                                    'total'   => $bookmarks_data['pages'],
                                    'current' => max( 1, absint( $_GET['bp'] ?? 1 ) ),
                                    'format'  => '?tab=bookmarks&bp=%#%',
                                    'prev_text' => '← قبلی',
                                    'next_text' => 'بعدی →',
                                    'type'    => 'list',
                                ) );
                                ?>
                            </nav>
                        <?php endif; ?>

                    <?php else : ?>
                        <div class="fn-empty-state">
                            <div class="fn-empty-state__icon">📚</div>
                            <h3>کتابخانه‌تان خالی است!</h3>
                            <p>رمان‌هایی که بوکمارک می‌کنید اینجا نمایش داده می‌شوند.</p>
                            <a href="<?php echo esc_url( get_post_type_archive_link( 'novel' ) ); ?>" class="fn-btn fn-btn--primary">
                                مرور کتابخانه
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>


            <!-- تب: تاریخچه خواندن -->
            <?php if ( $current_tab === 'history' ) : ?>
                <div class="fn-dashboard__section">
                    <div class="fn-dashboard__section-header">
                        <h2 class="fn-section__title">🕐 تاریخچه خواندن</h2>
                    </div>

                    <?php if ( ! empty( $history_data['items'] ) ) : ?>
                        <div class="fn-history-list">
                            <?php foreach ( $history_data['items'] as $item ) :
                                $h_novel_id  = $item->novel_id;
                                $h_chapter_id = $item->chapter_id;
                                $h_novel     = get_post( $h_novel_id );
                                $h_chapter   = get_post( $h_chapter_id );

                                if ( ! $h_novel || ! $h_chapter ) continue;

                                $cover       = get_the_post_thumbnail_url( $h_novel_id, 'novel-cover-sm' );
                                $ch_number   = get_post_meta( $h_chapter_id, '_fn_chapter_number', true );
                                $total_ch    = fn_get_chapter_count( $h_novel_id );
                                $progress_pct = $total_ch > 0 ? round( ( floatval( $ch_number ) / $total_ch ) * 100 ) : 0;
                            ?>
                                <div class="fn-history-item">
                                    <a href="<?php echo esc_url( get_permalink( $h_novel_id ) ); ?>" class="fn-history-item__cover">
                                        <?php if ( $cover ) : ?>
                                            <img src="<?php echo esc_url( $cover ); ?>" alt="" loading="lazy">
                                        <?php endif; ?>
                                    </a>
                                    <div class="fn-history-item__info">
                                        <a href="<?php echo esc_url( get_permalink( $h_novel_id ) ); ?>" class="fn-history-item__title">
                                            <?php echo esc_html( $h_novel->post_title ); ?>
                                        </a>
                                        <p class="fn-history-item__chapter">
                                            آخرین: فصل <?php echo esc_html( $ch_number ); ?> از <?php echo esc_html( $total_ch ); ?>
                                        </p>
                                        <div class="fn-history-item__progress-bar">
                                            <div class="fn-history-item__progress-fill" style="width: <?php echo $progress_pct; ?>%"></div>
                                        </div>
                                        <span class="fn-history-item__time">
                                            <?php echo fn_time_ago( strtotime( $item->last_read ) ); ?>
                                        </span>
                                    </div>
                                    <div class="fn-history-item__actions">
                                        <a href="<?php echo esc_url( get_permalink( $h_chapter_id ) ); ?>"
                                           class="fn-btn fn-btn--primary fn-btn--sm">ادامه</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ( $history_data['pages'] > 1 ) : ?>
                            <nav class="fn-pagination">
                                <?php
                                echo paginate_links( array(
                                    'total'   => $history_data['pages'],
                                    'current' => max( 1, absint( $_GET['hp'] ?? 1 ) ),
                                    'format'  => '?tab=history&hp=%#%',
                                    'prev_text' => '← قبلی',
                                    'next_text' => 'بعدی →',
                                    'type'    => 'list',
                                ) );
                                ?>
                            </nav>
                        <?php endif; ?>

                    <?php else : ?>
                        <div class="fn-empty-state">
                            <div class="fn-empty-state__icon">📖</div>
                            <h3>هنوز چیزی نخوانده‌اید!</h3>
                            <p>وقتی شروع به خواندن رمان کنید، تاریخچه اینجا ذخیره می‌شود.</p>
                            <a href="<?php echo esc_url( get_post_type_archive_link( 'novel' ) ); ?>" class="fn-btn fn-btn--primary">
                                مرور کتابخانه
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ( $current_tab === 'my-novels' ) : ?>
                <div class="fn-dashboard__section">
                    <div class="fn-dashboard__section-header" style="display:flex;align-items:center;justify-content:space-between;">
                        <h2 class="fn-section__title">✍️ رمان‌های من</h2>
                        <a href="?tab=add-novel" class="fn-btn fn-btn--primary fn-btn--sm">+ رمان جدید</a>
                    </div>

                    <?php
                    $my_novels = new WP_Query( array(
                        'post_type'      => 'novel',
                        'posts_per_page' => 20,
                        'author'         => $user_id,
                        'post_status'    => array( 'publish', 'draft', 'pending' ),
                        'orderby'        => 'modified',
                        'order'          => 'DESC',
                    ) );

                    if ( $my_novels->have_posts() ) :
                    ?>
                        <div class="fn-my-novels-list">
                            <?php while ( $my_novels->have_posts() ) : $my_novels->the_post();
                                $nid = get_the_ID();
                                $ch_count = fn_get_chapter_count( $nid );
                                $cover = get_the_post_thumbnail_url( $nid, 'novel-cover-sm' );
                            ?>
                                <div class="fn-my-novel-item">
                                    <div class="fn-my-novel-item__cover">
                                        <?php if ( $cover ) : ?>
                                            <img src="<?php echo esc_url( $cover ); ?>" alt="" loading="lazy">
                                        <?php endif; ?>
                                    </div>
                                    <div class="fn-my-novel-item__info">
                                        <h3 class="fn-my-novel-item__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                        <div class="fn-my-novel-item__meta">
                                            <span>📖 <?php echo $ch_count; ?> فصل</span>
                                            <span>📅 <?php echo get_the_modified_date( 'Y/m/d' ); ?></span>
                                        </div>
                                    </div>
                                    <div class="fn-my-novel-item__actions">
                                        <a href="?tab=add-chapter&novel_id=<?php echo $nid; ?>" class="fn-btn fn-btn--primary fn-btn--sm">+ فصل</a>
                                        <a href="?tab=edit-novel&novel_id=<?php echo $nid; ?>" class="fn-btn fn-btn--ghost fn-btn--sm">✏️</a>
                                    </div>
                                </div>
                            <?php endwhile; wp_reset_postdata(); ?>
                        </div>
                    <?php else : ?>
                        <div class="fn-empty-state">
                            <div class="fn-empty-state__icon">✍️</div>
                            <h3>هنوز رمانی ننوشته‌اید</h3>
                            <a href="?tab=add-novel" class="fn-btn fn-btn--primary">+ ساخت رمان</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>


            <!-- افزودن/ویرایش رمان (فرانت‌اند) -->
            <?php if ( $current_tab === 'add-novel' || $current_tab === 'edit-novel' ) :
                $edit_novel_id = absint( $_GET['novel_id'] ?? 0 );
                $is_edit = ( $current_tab === 'edit-novel' && $edit_novel_id );

                // بررسی مالکیت
                if ( $is_edit ) {
                    $edit_post = get_post( $edit_novel_id );
                    if ( ! $edit_post || intval( $edit_post->post_author ) !== $user_id ) {
                        echo '<div class="fn-alert fn-alert--error">دسترسی غیرمجاز.</div>';
                        $is_edit = false;
                    }
                }

                // پردازش فرم
                $saved = false;
                $error = '';
                if ( isset( $_POST['fn_save_novel_front'] ) && wp_verify_nonce( $_POST['fn_novel_front_nonce'], 'fn_save_novel_frontend' ) ) {
                    $title    = sanitize_text_field( $_POST['novel_title'] ?? '' );
                    $synopsis = wp_kses_post( $_POST['novel_synopsis'] ?? '' );
                    $author_n = sanitize_text_field( $_POST['novel_author'] ?? '' );
                    $genre_ids = array_map( 'absint', $_POST['novel_genres'] ?? array() );
                    $tag_names = sanitize_text_field( $_POST['novel_tags'] ?? '' );
                    $status_id = absint( $_POST['novel_status_tax'] ?? 0 );
                    $type_id   = absint( $_POST['novel_type_tax'] ?? 0 );
                    $language  = sanitize_text_field( $_POST['novel_language'] ?? '' );

                    if ( empty( $title ) ) {
                        $error = 'عنوان رمان الزامی است.';
                    } elseif ( count( $genre_ids ) > 6 ) {
                        $error = 'حداکثر ۶ ژانر مجاز است.';
                    } else {
                        $post_data = array(
                            'post_title'  => $title,
                            'post_type'   => 'novel',
                            'post_status' => 'pending',
                            'post_author' => $user_id,
                        );

                        if ( $is_edit ) {
                            $post_data['ID'] = $edit_novel_id;
                            $post_data['post_status'] = $edit_post->post_status;
                            wp_update_post( $post_data );
                            $novel_post_id = $edit_novel_id;
                        } else {
                            $novel_post_id = wp_insert_post( $post_data );
                        }

                        if ( $novel_post_id && ! is_wp_error( $novel_post_id ) ) {
                            update_post_meta( $novel_post_id, '_fn_synopsis', $synopsis );
                            update_post_meta( $novel_post_id, '_fn_original_author', $author_n );
                            update_post_meta( $novel_post_id, '_fn_original_language', $language );

                            if ( ! empty( $genre_ids ) ) wp_set_post_terms( $novel_post_id, $genre_ids, 'genre' );
                            if ( $status_id ) wp_set_post_terms( $novel_post_id, array( $status_id ), 'novel_status' );
                            if ( $type_id ) wp_set_post_terms( $novel_post_id, array( $type_id ), 'novel_type' );

                            // تگ‌ها (حداکثر ۶)
                            if ( ! empty( $tag_names ) ) {
                                $tags = array_slice( array_map( 'trim', explode( ',', $tag_names ) ), 0, 6 );
                                wp_set_post_terms( $novel_post_id, $tags, 'novel_tag' );
                            }

                            // آپلود تصویر جلد
                            if ( ! empty( $_FILES['novel_cover']['name'] ) ) {
                                require_once ABSPATH . 'wp-admin/includes/image.php';
                                require_once ABSPATH . 'wp-admin/includes/file.php';
                                require_once ABSPATH . 'wp-admin/includes/media.php';
                                $attach_id = media_handle_upload( 'novel_cover', $novel_post_id );
                                if ( ! is_wp_error( $attach_id ) ) {
                                    set_post_thumbnail( $novel_post_id, $attach_id );
                                }
                            }

                            $saved = true;
                        }
                    }
                }

                // مقادیر فعلی
                $f = array(
                    'title'    => $is_edit ? $edit_post->post_title : '',
                    'synopsis' => $is_edit ? get_post_meta( $edit_novel_id, '_fn_synopsis', true ) : '',
                    'author'   => $is_edit ? get_post_meta( $edit_novel_id, '_fn_original_author', true ) : '',
                    'language' => $is_edit ? get_post_meta( $edit_novel_id, '_fn_original_language', true ) : '',
                    'genres'   => $is_edit ? wp_get_post_terms( $edit_novel_id, 'genre', array( 'fields' => 'ids' ) ) : array(),
                    'tags'     => $is_edit ? implode( ', ', wp_get_post_terms( $edit_novel_id, 'novel_tag', array( 'fields' => 'names' ) ) ) : '',
                    'status'   => $is_edit ? wp_get_post_terms( $edit_novel_id, 'novel_status', array( 'fields' => 'ids' ) ) : array(),
                    'type'     => $is_edit ? wp_get_post_terms( $edit_novel_id, 'novel_type', array( 'fields' => 'ids' ) ) : array(),
                );
            ?>
                <div class="fn-dashboard__section">
                    <h2 class="fn-section__title"><?php echo $is_edit ? '✏️ ویرایش رمان' : '📝 افزودن رمان جدید'; ?></h2>

                    <?php if ( $saved ) : ?>
                        <div class="fn-alert fn-alert--success">✅ رمان ذخیره شد! <?php if ( ! $is_edit ) echo 'پس از تأیید مدیر منتشر می‌شود.'; ?></div>
                    <?php endif; ?>
                    <?php if ( $error ) : ?>
                        <div class="fn-alert fn-alert--error">❌ <?php echo esc_html( $error ); ?></div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data" class="fn-settings-form" style="max-width:100%;">
                        <?php wp_nonce_field( 'fn_save_novel_frontend', 'fn_novel_front_nonce' ); ?>

                        <div class="fn-form-row">
                            <div class="fn-form-group">
                                <label class="fn-form-label">عنوان رمان *</label>
                                <input type="text" name="novel_title" class="fn-form-input" value="<?php echo esc_attr( $f['title'] ); ?>" required>
                            </div>
                            <div class="fn-form-group">
                                <label class="fn-form-label">نویسنده اصلی</label>
                                <input type="text" name="novel_author" class="fn-form-input" value="<?php echo esc_attr( $f['author'] ); ?>">
                            </div>
                        </div>

                        <div class="fn-form-group">
                            <label class="fn-form-label">خلاصه داستان</label>
                            <textarea name="novel_synopsis" class="fn-form-input fn-form-textarea" rows="5"><?php echo esc_textarea( $f['synopsis'] ); ?></textarea>
                        </div>

                        <div class="fn-form-row">
                            <div class="fn-form-group">
                                <label class="fn-form-label">ژانرها (حداکثر ۶)</label>
                                <div class="fn-checkbox-grid">
                                    <?php foreach ( get_terms( array( 'taxonomy' => 'genre', 'hide_empty' => false ) ) as $g ) : ?>
                                        <label class="fn-checkbox-item">
                                            <input type="checkbox" name="novel_genres[]" value="<?php echo $g->term_id; ?>"
                                                <?php checked( in_array( $g->term_id, $f['genres'] ) ); ?>>
                                            <?php echo esc_html( $g->name ); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="fn-form-group">
                                <label class="fn-form-label">برچسب‌ها (حداکثر ۶، با ویرگول)</label>
                                <input type="text" name="novel_tags" class="fn-form-input" value="<?php echo esc_attr( $f['tags'] ); ?>" placeholder="اکشن، قهرمان، ...">
                                <small style="color:var(--fn-text-muted);font-size:0.75rem;">حداکثر ۶ برچسب</small>
                            </div>
                        </div>

                        <div class="fn-form-row" style="grid-template-columns:1fr 1fr 1fr;">
                            <div class="fn-form-group">
                                <label class="fn-form-label">نوع اثر</label>
                                <select name="novel_type_tax" class="fn-form-input">
                                    <?php foreach ( get_terms( array( 'taxonomy' => 'novel_type', 'hide_empty' => false ) ) as $t ) : ?>
                                        <option value="<?php echo $t->term_id; ?>" <?php selected( in_array( $t->term_id, $f['type'] ) ); ?>><?php echo esc_html( $t->name ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="fn-form-group">
                                <label class="fn-form-label">وضعیت انتشار</label>
                                <select name="novel_status_tax" class="fn-form-input">
                                    <?php foreach ( get_terms( array( 'taxonomy' => 'novel_status', 'hide_empty' => false ) ) as $s ) : ?>
                                        <option value="<?php echo $s->term_id; ?>" <?php selected( in_array( $s->term_id, $f['status'] ) ); ?>><?php echo esc_html( $s->name ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="fn-form-group">
                                <label class="fn-form-label">زبان اصلی</label>
                                <select name="novel_language" class="fn-form-input">
                                    <option value="">انتخاب</option>
                                    <option value="chinese" <?php selected( $f['language'], 'chinese' ); ?>>چینی</option>
                                    <option value="korean" <?php selected( $f['language'], 'korean' ); ?>>کره‌ای</option>
                                    <option value="japanese" <?php selected( $f['language'], 'japanese' ); ?>>ژاپنی</option>
                                    <option value="english" <?php selected( $f['language'], 'english' ); ?>>انگلیسی</option>
                                    <option value="persian" <?php selected( $f['language'], 'persian' ); ?>>فارسی</option>
                                </select>
                            </div>
                        </div>

                        <div class="fn-form-group">
                            <label class="fn-form-label">تصویر جلد</label>
                            <input type="file" name="novel_cover" accept="image/*" class="fn-form-input" style="padding:10px;">
                            <?php if ( $is_edit && has_post_thumbnail( $edit_novel_id ) ) : ?>
                                <img src="<?php echo esc_url( get_the_post_thumbnail_url( $edit_novel_id, 'novel-cover-sm' ) ); ?>" style="width:60px;height:80px;object-fit:cover;border-radius:6px;margin-top:8px;">
                            <?php endif; ?>
                        </div>

                        <div class="fn-form-actions">
                            <button type="submit" name="fn_save_novel_front" class="fn-btn fn-btn--primary">💾 ذخیره رمان</button>
                            <a href="?tab=my-novels" class="fn-btn fn-btn--ghost">بازگشت</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>


            <!-- افزودن فصل (فرانت‌اند) -->
            <?php if ( $current_tab === 'add-chapter' ) :
                $novel_for_ch = absint( $_GET['novel_id'] ?? 0 );
                $ch_saved = false;

                if ( isset( $_POST['fn_save_chapter_front'] ) && wp_verify_nonce( $_POST['fn_ch_nonce'], 'fn_save_chapter_fe' ) ) {
                    $ch_title   = sanitize_text_field( $_POST['chapter_title'] ?? '' );
                    $ch_number  = floatval( $_POST['chapter_number'] ?? 0 );
                    $ch_content = wp_kses_post( $_POST['chapter_content'] ?? '' );

                    if ( $novel_for_ch && $ch_number > 0 && ! empty( $ch_content ) ) {
                        $ch_id = wp_insert_post( array(
                            'post_title'   => $ch_title ?: sprintf( 'فصل %s', $ch_number ),
                            'post_content' => $ch_content,
                            'post_type'    => 'chapter',
                            'post_status'  => 'publish',
                            'post_author'  => $user_id,
                        ) );

                        if ( $ch_id && ! is_wp_error( $ch_id ) ) {
                            update_post_meta( $ch_id, '_fn_parent_novel', $novel_for_ch );
                            update_post_meta( $ch_id, '_fn_chapter_number', $ch_number );
                            fn_update_chapter_count( $novel_for_ch );
                            $ch_saved = true;
                        }
                    }
                }

                $novel_obj = get_post( $novel_for_ch );
                $next_ch_num = 1;
                $last = fn_get_latest_chapter( $novel_for_ch );
                if ( $last ) $next_ch_num = floatval( get_post_meta( $last->ID, '_fn_chapter_number', true ) ) + 1;
            ?>
                <div class="fn-dashboard__section">
                    <h2 class="fn-section__title">📝 افزودن فصل به «<?php echo esc_html( $novel_obj->post_title ?? '' ); ?>»</h2>

                    <?php if ( $ch_saved ) : ?>
                        <div class="fn-alert fn-alert--success">✅ فصل با موفقیت منتشر شد!</div>
                    <?php endif; ?>

                    <form method="post" class="fn-settings-form" style="max-width:100%;">
                        <?php wp_nonce_field( 'fn_save_chapter_fe', 'fn_ch_nonce' ); ?>

                        <div class="fn-form-row">
                            <div class="fn-form-group">
                                <label class="fn-form-label">شماره فصل *</label>
                                <input type="number" name="chapter_number" class="fn-form-input" value="<?php echo $next_ch_num; ?>" min="0" step="0.5" required>
                            </div>
                            <div class="fn-form-group">
                                <label class="fn-form-label">عنوان فصل (اختیاری)</label>
                                <input type="text" name="chapter_title" class="fn-form-input" placeholder="مثلاً: آغاز ماجراجویی">
                            </div>
                        </div>

                        <div class="fn-form-group">
                            <label class="fn-form-label">محتوای فصل *</label>
                            <?php
                            wp_editor( '', 'chapter_content', array(
                                'textarea_name' => 'chapter_content',
                                'textarea_rows' => 15,
                                'media_buttons' => false,
                                'teeny'         => true,
                                'quicktags'     => true,
                            ) );
                            ?>
                        </div>

                        <div class="fn-form-actions">
                            <button type="submit" name="fn_save_chapter_front" class="fn-btn fn-btn--primary">📤 انتشار فصل</button>
                            <a href="?tab=my-novels" class="fn-btn fn-btn--ghost">بازگشت</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>


            <!-- تب: تنظیمات حساب -->
            <?php if ( $current_tab === 'settings' ) : ?>
                <div class="fn-dashboard__section">
                    <div class="fn-dashboard__section-header">
                        <h2 class="fn-section__title">⚙️ تنظیمات حساب</h2>
                    </div>

                    <?php
                    // پردازش فرم تنظیمات
                    $settings_saved = false;
                    $settings_error = '';

                    if ( isset( $_POST['fn_save_settings'] ) && wp_verify_nonce( $_POST['fn_settings_nonce'], 'fn_save_user_settings' ) ) {
                        $display_name = sanitize_text_field( $_POST['display_name'] ?? '' );
                        $email        = sanitize_email( $_POST['email'] ?? '' );
                        $new_pass     = $_POST['new_password'] ?? '';
                        $confirm_pass = $_POST['confirm_password'] ?? '';

                        if ( $display_name ) {
                            wp_update_user( array( 'ID' => $user_id, 'display_name' => $display_name ) );
                        }

                        if ( $email && is_email( $email ) && $email !== $user->user_email ) {
                            if ( ! email_exists( $email ) ) {
                                wp_update_user( array( 'ID' => $user_id, 'user_email' => $email ) );
                            } else {
                                $settings_error = 'این ایمیل قبلاً استفاده شده است.';
                            }
                        }

                        if ( ! empty( $new_pass ) ) {
                            if ( $new_pass === $confirm_pass ) {
                                if ( mb_strlen( $new_pass ) >= 6 ) {
                                    wp_set_password( $new_pass, $user_id );
                                    wp_set_auth_cookie( $user_id );
                                } else {
                                    $settings_error = 'رمز عبور باید حداقل ۶ کاراکتر باشد.';
                                }
                            } else {
                                $settings_error = 'رمز عبور و تکرار آن یکسان نیست.';
                            }
                        }

                        if ( ! $settings_error ) {
                            $settings_saved = true;
                            $user = wp_get_current_user(); // بروزرسانی اطلاعات
                        }
                    }
                    ?>

                    <?php if ( $settings_saved ) : ?>
                        <div class="fn-alert fn-alert--success">✅ تنظیمات با موفقیت ذخیره شد.</div>
                    <?php endif; ?>

                    <?php if ( $settings_error ) : ?>
                        <div class="fn-alert fn-alert--error">❌ <?php echo esc_html( $settings_error ); ?></div>
                    <?php endif; ?>

                    <form method="post" class="fn-settings-form">
                        <?php wp_nonce_field( 'fn_save_user_settings', 'fn_settings_nonce' ); ?>

                        <div class="fn-form-group">
                            <label for="display_name" class="fn-form-label">نام نمایشی</label>
                            <input type="text" id="display_name" name="display_name"
                                   class="fn-form-input"
                                   value="<?php echo esc_attr( $user->display_name ); ?>" required>
                        </div>

                        <div class="fn-form-group">
                            <label for="email" class="fn-form-label">ایمیل</label>
                            <input type="email" id="email" name="email"
                                   class="fn-form-input"
                                   value="<?php echo esc_attr( $user->user_email ); ?>" required>
                        </div>

                        <div class="fn-form-divider">
                            <span>تغییر رمز عبور (اختیاری)</span>
                        </div>

                        <div class="fn-form-row">
                            <div class="fn-form-group">
                                <label for="new_password" class="fn-form-label">رمز عبور جدید</label>
                                <input type="password" id="new_password" name="new_password"
                                       class="fn-form-input" placeholder="حداقل ۶ کاراکتر" autocomplete="new-password">
                            </div>
                            <div class="fn-form-group">
                                <label for="confirm_password" class="fn-form-label">تکرار رمز عبور</label>
                                <input type="password" id="confirm_password" name="confirm_password"
                                       class="fn-form-input" placeholder="تکرار رمز عبور" autocomplete="new-password">
                            </div>
                        </div>

                        <div class="fn-form-actions">
                            <button type="submit" name="fn_save_settings" class="fn-btn fn-btn--primary">
                                💾 ذخیره تنظیمات
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php get_footer(); ?>