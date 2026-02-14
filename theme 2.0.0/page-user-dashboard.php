<?php
/**
 * Template Name: داشبورد کاربر
 * صفحه داشبورد: کتابخانه، تاریخچه، پروفایل
 *
 * @package Flavor_Novel
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// اگر لاگین نیست، به صفحه ورود برو
if ( ! is_user_logged_in() ) {
    $login_url = function_exists('novel_get_page_url') ? novel_get_page_url('login') : wp_login_url( get_permalink() );
    wp_redirect( $login_url );
    exit;
}

get_header();

$user        = wp_get_current_user();
$user_id     = $user->ID;
$current_tab = sanitize_text_field( $_GET['tab'] ?? 'bookmarks' );

// دریافت داده‌ها بر اساس تب
$bookmarks_data = fn_get_user_bookmarks( $user_id, 20, max( 1, absint( $_GET['bp'] ?? 1 ) ) );
$history_data   = fn_get_reading_history( $user_id, 20, max( 1, absint( $_GET['hp'] ?? 1 ) ) );
?>

<div class="fn-container">
    <?php fn_breadcrumb(); ?>

    <div class="fn-dashboard">

        <!-- دکمه منوی موبایل -->
        <button class="novel-dashboard-menu-toggle" id="dashboard-menu-toggle" aria-label="<?php esc_attr_e('منوی داشبورد', 'flavor'); ?>">
            ☰
        </button>
        <div class="novel-dashboard-overlay" id="dashboard-overlay"></div>

        <!-- ===== سایدبار ===== -->
        <aside class="fn-dashboard__sidebar">
            <!-- پروفایل -->
            <div class="fn-dashboard__profile">
                <div class="fn-dashboard__avatar">
                    <?php
                    $sidebar_avatar_url = function_exists('novel_get_avatar_url') ? novel_get_avatar_url($user_id, 80) : get_avatar_url($user_id, ['size' => 80]);
                    ?>
                    <img src="<?php echo esc_url($sidebar_avatar_url); ?>"
                         alt="<?php echo esc_attr($user->display_name); ?>"
                         width="80" height="80"
                         class="novel-avatar">
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
                <a href="?tab=profile" class="fn-dashboard__nav-item <?php echo $current_tab === 'profile' ? 'active' : ''; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    پروفایل
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
                            $novel_id     = get_the_ID();
                            $progress     = fn_get_reading_progress( $user_id, $novel_id );
                            $total_ch     = fn_get_chapter_count( $novel_id );
                            $current_ch   = 0;
                            $progress_pct = 0;

                            if ( $progress && $progress->chapter_id ) {
                                $current_ch   = floatval( get_post_meta( $progress->chapter_id, '_fn_chapter_number', true ) );
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
                        'total'     => $bookmarks_data['pages'],
                        'current'   => max( 1, absint( $_GET['bp'] ?? 1 ) ),
                        'format'    => '?tab=bookmarks&bp=%#%',
                        'prev_text' => '← قبلی',
                        'next_text' => 'بعدی →',
                        'type'      => 'list',
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
                        $h_novel_id   = $item->novel_id;
                        $h_chapter_id = $item->chapter_id;
                        $h_novel      = get_post( $h_novel_id );
                        $h_chapter    = get_post( $h_chapter_id );

                        if ( ! $h_novel || ! $h_chapter ) continue;

                        $cover        = get_the_post_thumbnail_url( $h_novel_id, 'novel-cover-sm' );
                        $ch_number    = get_post_meta( $h_chapter_id, '_fn_chapter_number', true );
                        $total_ch     = fn_get_chapter_count( $h_novel_id );
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
                        'total'     => $history_data['pages'],
                        'current'   => max( 1, absint( $_GET['hp'] ?? 1 ) ),
                        'format'    => '?tab=history&hp=%#%',
                        'prev_text' => '← قبلی',
                        'next_text' => 'بعدی →',
                        'type'      => 'list',
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

            <!-- تب: رمان‌های من -->
            <?php if ( $current_tab === 'my-novels' ) : ?>
            <div class="fn-dashboard__section">
                <div class="fn-dashboard__section-header" style="display:flex;align-items:center;justify-content:space-between;">
                    <h2 class="fn-section__title">✍ رمان‌های من</h2>
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
                        $nid      = get_the_ID();
                        $ch_count = fn_get_chapter_count( $nid );
                        $cover    = get_the_post_thumbnail_url( $nid, 'novel-cover-sm' );
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
                            <a href="?tab=edit-novel&novel_id=<?php echo $nid; ?>" class="fn-btn fn-btn--ghost fn-btn--sm">✏</a>
                        </div>
                    </div>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
                <?php else : ?>
                <div class="fn-empty-state">
                    <div class="fn-empty-state__icon">✍</div>
                    <h3>هنوز رمانی ننوشته‌اید</h3>
                    <a href="?tab=add-novel" class="fn-btn fn-btn--primary">+ ساخت رمان</a>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- افزودن/ویرایش رمان (فرانت‌اند) -->
            <?php if ( $current_tab === 'add-novel' || $current_tab === 'edit-novel' ) :
                $edit_novel_id = absint( $_GET['novel_id'] ?? 0 );
                $is_edit       = ( $current_tab === 'edit-novel' && $edit_novel_id );

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
                    $title     = sanitize_text_field( $_POST['novel_title'] ?? '' );
                    $synopsis  = wp_kses_post( $_POST['novel_synopsis'] ?? '' );
                    $author_n  = sanitize_text_field( $_POST['novel_author'] ?? '' );
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
                            $post_data['ID']          = $edit_novel_id;
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
                <h2 class="fn-section__title"><?php echo $is_edit ? '✏ ویرایش رمان' : '📝 افزودن رمان جدید'; ?></h2>

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
                $ch_saved     = false;

                if ( isset( $_POST['fn_save_chapter_front'] ) && wp_verify_nonce( $_POST['fn_ch_nonce'], 'fn_save_chapter_fe' ) ) {
                    $ch_title   = sanitize_text_field( $_POST['chapter_title'] ?? '' );
                    $ch_number  = floatval( $_POST['chapter_number'] ?? 0 );
                    $ch_content = wp_kses_post( $_POST['chapter_content'] ?? '' );

                    if ( $novel_for_ch && $ch_number > 0 && ! empty( $ch_content ) ) {
                        $ch_id = wp_insert_post( array(
                            'post_title'  => $ch_title ?: sprintf( 'فصل %s', $ch_number ),
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

                $novel_obj   = get_post( $novel_for_ch );
                $next_ch_num = 1;
                $last        = fn_get_latest_chapter( $novel_for_ch );
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


            <!-- ═══════════════════════════════════════ -->
            <!-- تب پروفایل — اصلاح‌شده نسخه ۴ -->
            <!-- ═══════════════════════════════════════ -->
            <?php if ( $current_tab === 'profile' ) : ?>
            <div class="dashboard-tab-content" id="tab-profile">
            <?php
            $current_user      = wp_get_current_user();
            $current_avatar_id = (int) get_user_meta($current_user->ID, 'custom_avatar', true);
            $current_avatar_url = function_exists('novel_get_avatar_url') ? novel_get_avatar_url($current_user->ID, 96) : get_avatar_url($current_user->ID);

            // پیام‌های تغییر ایمیل
            $email_change_status = isset($_GET['email_change']) ? sanitize_text_field($_GET['email_change']) : '';

            // محدودیت ۶ ماهه
            $last_email_change = (int) get_user_meta($current_user->ID, 'last_email_change_time', true);
            $email_change_allowed = true;
            $email_change_remaining_days = 0;
            if ($last_email_change > 0) {
                $six_months_days = defined('NOVEL_EMAIL_CHANGE_DAYS') ? NOVEL_EMAIL_CHANGE_DAYS : 180;
                $next_allowed = $last_email_change + ($six_months_days * DAY_IN_SECONDS);
                if (time() < $next_allowed) {
                    $email_change_allowed = false;
                    $email_change_remaining_days = ceil(($next_allowed - time()) / DAY_IN_SECONDS);
                }
            }
            ?>

            <?php if ($email_change_status === 'success') : ?>
                <div class="novel-dashboard-alert novel-dashboard-alert--success">
                    ✅ <?php esc_html_e('ایمیل شما با موفقیت تغییر کرد.', 'flavor'); ?>
                </div>
            <?php elseif ($email_change_status === 'invalid') : ?>
                <div class="novel-dashboard-alert novel-dashboard-alert--error">
                    ❌ <?php esc_html_e('لینک تأیید تغییر ایمیل نامعتبر یا منقضی شده.', 'flavor'); ?>
                </div>
            <?php elseif ($email_change_status === 'taken') : ?>
                <div class="novel-dashboard-alert novel-dashboard-alert--error">
                    ❌ <?php esc_html_e('این ایمیل قبلاً توسط کاربر دیگری ثبت شده.', 'flavor'); ?>
                </div>
            <?php endif; ?>

            <div class="novel-dashboard-profile-grid">

                <!-- ═══ ستون چپ: آواتار ═══ -->
                <div class="novel-dashboard-profile-col novel-avatar-section">
                    <div class="novel-dashboard-card">
                        <h3 class="novel-dashboard-card-title">
                            🖼 <?php esc_html_e('تصویر پروفایل', 'flavor'); ?>
                        </h3>

                        <div class="novel-avatar-current">
                            <img src="<?php echo esc_url($current_avatar_url); ?>"
                                 alt="<?php esc_attr_e('آواتار فعلی', 'flavor'); ?>"
                                 width="96" height="96"
                                 id="current-avatar-preview">
                            <p class="novel-avatar-current-label"><?php esc_html_e('آواتار فعلی شما', 'flavor'); ?></p>
                        </div>

                        <div class="novel-avatar-grid-toggle">
                            <button type="button" class="novel-dashboard-btn novel-dashboard-btn--outline">
                                🖼 <?php esc_html_e('انتخاب آواتار', 'flavor'); ?>
                            </button>
                        </div>

                        <div class="novel-avatar-grid" id="avatar-grid">
                            <?php
                            $avatar_count = defined('NOVEL_AVATAR_COUNT') ? NOVEL_AVATAR_COUNT : 20;
                            $avatar_url   = defined('NOVEL_AVATAR_URL') ? NOVEL_AVATAR_URL : get_template_directory_uri() . '/assets/avatars/';
                            for ($i = 1; $i <= $avatar_count; $i++) : ?>
                                <div class="novel-avatar-grid-item <?php echo ($i === $current_avatar_id) ? 'is-selected' : ''; ?>"
                                     data-avatar-id="<?php echo $i; ?>"
                                     title="<?php printf(esc_attr__('آواتار %d', 'flavor'), $i); ?>">
                                    <img src="<?php echo esc_url($avatar_url . 'avatar-' . $i . '.png'); ?>"
                                         alt="<?php printf(esc_attr__('آواتار %d', 'flavor'), $i); ?>"
                                         loading="lazy" width="56" height="56">
                                </div>
                            <?php endfor; ?>
                        </div>

                        <div class="novel-avatar-save-wrap" id="avatar-save-wrap">
                            <input type="hidden" id="selected-avatar-id" value="">
                            <button type="button" id="novel-save-avatar-btn" class="novel-dashboard-btn novel-dashboard-btn--primary">
                                <?php esc_html_e('ذخیره آواتار', 'flavor'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ═══ ستون راست: اطلاعات ═══ -->
                <div class="novel-dashboard-profile-col">

                    <!-- ──── کارت نام نمایشی ──── -->
                    <div class="novel-dashboard-card">
                        <h3 class="novel-dashboard-card-title">
                            👤 <?php esc_html_e('نام نمایشی', 'flavor'); ?>
                        </h3>
                        <form id="novel-profile-form" class="novel-dashboard-form" novalidate>
                            <div class="novel-auth-field">
                                <label for="dashboard-display-name">
                                    <?php esc_html_e('نام نمایشی', 'flavor'); ?>
                                </label>
                                <input type="text"
                                       id="dashboard-display-name"
                                       name="display_name"
                                       value="<?php echo esc_attr($current_user->display_name); ?>"
                                       minlength="5" maxlength="20"
                                       placeholder="<?php esc_attr_e('۵ تا ۲۰ کاراکتر فارسی', 'flavor'); ?>"
                                       autocomplete="off" required>
                                <span class="novel-auth-field-status" id="dashboard-name-status"></span>
                                <div class="novel-dashboard-field-help">
                                    <small>📝 <?php esc_html_e('فقط حروف فارسی و خط تیره (-) بین کلمات مجاز است. حداقل ۵ و حداکثر ۲۰ کاراکتر.', 'flavor'); ?></small>
                                </div>
                            </div>
                            <div class="novel-profile-messages novel-auth-messages" style="display:none;"></div>
                            <button type="submit" class="novel-dashboard-btn novel-dashboard-btn--primary">
                                <span class="novel-auth-btn-text"><?php esc_html_e('ذخیره تغییرات', 'flavor'); ?></span>
                                <span class="novel-auth-btn-loading" style="display:none;">
                                    <span class="novel-spinner"></span>
                                    <?php esc_html_e('در حال ذخیره...', 'flavor'); ?>
                                </span>
                            </button>
                        </form>
                    </div>

                    <!-- ──── کارت تغییر رمز عبور ──── -->
                    <div class="novel-dashboard-card">
                        <h3 class="novel-dashboard-card-title">
                            🔒 <?php esc_html_e('تغییر رمز عبور', 'flavor'); ?>
                        </h3>

                        <div class="novel-dashboard-alert novel-dashboard-alert--info" style="margin-bottom:16px;">
                            📌 <?php esc_html_e('قوانین رمز عبور: حداقل ۸ کاراکتر، شامل حداقل یک حرف بزرگ انگلیسی و یک عدد. رمز جدید نباید با رمز فعلی یکسان باشد.', 'flavor'); ?>
                        </div>

                        <form id="novel-change-password-form" class="novel-dashboard-form" novalidate>

                            <!-- رمز فعلی -->
                            <div class="novel-auth-field">
                                <label for="current-password"><?php esc_html_e('رمز عبور فعلی', 'flavor'); ?></label>
                                <div class="novel-auth-password-wrap">
                                    <input type="password" id="current-password" name="current_password" required
                                           placeholder="<?php esc_attr_e('رمز فعلی', 'flavor'); ?>"
                                           autocomplete="current-password">
                                    <button type="button" class="novel-auth-toggle-pass" data-target="current-password">
                                        <svg class="eye-open" viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                        <svg class="eye-closed" viewBox="0 0 24 24" width="20" height="20" style="display:none;"><path fill="currentColor" d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- رمز جدید -->
                            <div class="novel-auth-field">
                                <label for="new-password"><?php esc_html_e('رمز عبور جدید', 'flavor'); ?></label>
                                <div class="novel-auth-password-wrap">
                                    <input type="password" id="new-password" name="new_password" required
                                           minlength="8"
                                           placeholder="<?php esc_attr_e('حداقل ۸ کاراکتر', 'flavor'); ?>"
                                           autocomplete="new-password">
                                    <button type="button" class="novel-auth-toggle-pass" data-target="new-password">
                                        <svg class="eye-open" viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                        <svg class="eye-closed" viewBox="0 0 24 24" width="20" height="20" style="display:none;"><path fill="currentColor" d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>
                                    </button>
                                </div>
                                <!-- نوار قدرت رمز -->
                                <div class="novel-auth-password-strength">
                                    <div class="novel-auth-strength-bar">
                                        <div class="novel-auth-strength-fill"></div>
                                    </div>
                                    <span class="novel-auth-strength-text"></span>
                                </div>
                            </div>

                            <!-- تکرار رمز جدید -->
                            <div class="novel-auth-field">
                                <label for="new-password-confirm"><?php esc_html_e('تکرار رمز عبور جدید', 'flavor'); ?></label>
                                <div class="novel-auth-password-wrap">
                                    <input type="password" id="new-password-confirm" name="new_password_confirm" required
                                           placeholder="<?php esc_attr_e('تکرار رمز جدید', 'flavor'); ?>"
                                           autocomplete="new-password">
                                    <button type="button" class="novel-auth-toggle-pass" data-target="new-password-confirm">
                                        <svg class="eye-open" viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                        <svg class="eye-closed" viewBox="0 0 24 24" width="20" height="20" style="display:none;"><path fill="currentColor" d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>
                                    </button>
                                </div>
                                <span class="novel-auth-field-status"></span>
                            </div>

                            <div class="novel-auth-messages" style="display:none;"></div>

                            <button type="submit" class="novel-dashboard-btn novel-dashboard-btn--primary">
                                <span class="novel-auth-btn-text"><?php esc_html_e('تغییر رمز عبور', 'flavor'); ?></span>
                                <span class="novel-auth-btn-loading" style="display:none;">
                                    <span class="novel-spinner"></span>
                                    <?php esc_html_e('در حال تغییر...', 'flavor'); ?>
                                </span>
                            </button>
                        </form>
                    </div>

                    <!-- ──── کارت تغییر ایمیل ──── -->
                    <div class="novel-dashboard-card">
                        <h3 class="novel-dashboard-card-title">
                            📧 <?php esc_html_e('تغییر ایمیل', 'flavor'); ?>
                        </h3>

                        <!-- ایمیل فعلی -->
                        <div class="novel-dashboard-current-email">
                            <span class="novel-dashboard-label"><?php esc_html_e('ایمیل فعلی:', 'flavor'); ?></span>
                            <strong dir="ltr" class="novel-dashboard-email-value"><?php echo esc_html($current_user->user_email); ?></strong>
                        </div>

                        <!-- هشدار محدودیت ۶ ماهه -->
                        <div class="novel-dashboard-alert novel-dashboard-alert--info" style="margin-top:12px;">
                            📌 <?php esc_html_e('تغییر ایمیل هر ۶ ماه یکبار مجاز است. ابتدا کد تأیید به ایمیل فعلی ارسال می‌شود، سپس لینک تأیید به ایمیل جدید.', 'flavor'); ?>
                        </div>

                        <?php if (!$email_change_allowed) : ?>
                            <!-- محدودیت فعال -->
                            <div class="novel-dashboard-alert novel-dashboard-alert--warning" style="margin-top:12px;">
                                ⏳ <?php printf(
                                    esc_html__('شما %d روز دیگر تا امکان تغییر ایمیل باید صبر کنید.', 'flavor'),
                                    $email_change_remaining_days
                                ); ?>
                            </div>
                        <?php else : ?>
                            <!-- فیلد ایمیل جدید -->
                            <div class="novel-auth-field" style="margin-top:16px;">
                                <label for="new-email-input"><?php esc_html_e('ایمیل جدید', 'flavor'); ?></label>
                                <input type="email" id="new-email-input"
                                       placeholder="<?php esc_attr_e('ایمیل جدید خود را وارد کنید', 'flavor'); ?>"
                                       autocomplete="email" dir="ltr">
                            </div>

                            <!-- دکمه ارسال کد -->
                            <button type="button" id="novel-send-email-code-btn" class="novel-dashboard-btn novel-dashboard-btn--primary">
                                <span class="novel-auth-btn-text"><?php esc_html_e('ارسال کد تأیید', 'flavor'); ?></span>
                                <span class="novel-auth-btn-loading" style="display:none;">
                                    <span class="novel-spinner"></span>
                                    <?php esc_html_e('در حال ارسال...', 'flavor'); ?>
                                </span>
                            </button>

                            <!-- باکس ورود کد تأیید — مخفی تا زمان ارسال -->
                            <div id="email-change-code-section" style="display:none; margin-top:20px;">
                                <div class="novel-dashboard-alert novel-dashboard-alert--info">
                                    🔐 <?php esc_html_e('کد ۶ رقمی ارسال‌شده به ایمیل فعلی را وارد کنید. کد ۱۰ دقیقه اعتبار دارد.', 'flavor'); ?>
                                </div>

                                <div class="novel-auth-field" style="margin-top:12px;">
                                    <label for="email-change-code-input"><?php esc_html_e('کد تأیید', 'flavor'); ?></label>
                                    <input type="text" id="email-change-code-input"
                                           maxlength="6" pattern="[0-9]{6}"
                                           placeholder="<?php esc_attr_e('کد ۶ رقمی', 'flavor'); ?>"
                                           autocomplete="one-time-code"
                                           dir="ltr"
                                           style="text-align:center; font-size:20px; letter-spacing:8px; font-family:monospace;">
                                </div>

                                <button type="button" id="novel-verify-email-code-btn" class="novel-dashboard-btn novel-dashboard-btn--primary">
                                    <span class="novel-auth-btn-text"><?php esc_html_e('تأیید کد و ارسال لینک', 'flavor'); ?></span>
                                    <span class="novel-auth-btn-loading" style="display:none;">
                                        <span class="novel-spinner"></span>
                                        <?php esc_html_e('در حال بررسی...', 'flavor'); ?>
                                    </span>
                                </button>
                            </div>
                        <?php endif; ?>

                        <div class="novel-auth-messages" id="email-change-messages" style="display:none; margin-top:12px;"></div>
                    </div>

                    <!-- ──── کارت اطلاع‌رسانی ──── -->
                    <div class="novel-dashboard-card">
                        <h3 class="novel-dashboard-card-title">
                            🔔 <?php esc_html_e('تنظیمات اطلاع‌رسانی', 'flavor'); ?>
                        </h3>
                        <label class="novel-auth-checkbox" id="notifications">
                            <input type="checkbox" id="notify-comment-reply"
                                <?php checked(get_user_meta($current_user->ID, 'notify_comment_reply', true), '1'); ?>
                                data-meta-key="notify_comment_reply">
                            <span class="novel-auth-checkmark"></span>
                            <?php esc_html_e('اطلاع‌رسانی ایمیلی هنگام پاسخ به دیدگاه‌هایم', 'flavor'); ?>
                        </label>
                    </div>

                </div>
            </div>
            </div>
            <?php endif; ?>
            <!-- پایان تب پروفایل -->

        </div>
    </div>
</div>

<?php get_footer(); ?>