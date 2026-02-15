<?php
/**
 * Single Chapter Template
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
    
    $chapter_id    = get_the_ID();
    $novel_id      = get_post_meta($chapter_id, 'chapter_novel_id', true);
    $chapter_num   = get_post_meta($chapter_id, 'chapter_number', true);
    $chapter_title = get_post_meta($chapter_id, 'chapter_title', true);
    $chapter_vol   = get_post_meta($chapter_id, 'chapter_volume', true);
    $is_vip        = get_post_meta($chapter_id, 'chapter_is_vip', true);
    $coin_price    = get_post_meta($chapter_id, 'chapter_coin_price', true) ?: 5;
    $word_count    = get_post_meta($chapter_id, 'chapter_word_count', true) ?: 0;
    $reading_time  = get_post_meta($chapter_id, 'chapter_reading_time', true) ?: 0;
    $recap         = get_post_meta($chapter_id, 'chapter_recap', true);
    $views         = get_post_meta($chapter_id, 'chapter_views', true) ?: 0;
    $likes         = get_post_meta($chapter_id, 'chapter_likes', true) ?: 0;
    $dislikes      = get_post_meta($chapter_id, 'chapter_dislikes', true) ?: 0;
    
    // Novel info
    $novel = get_post($novel_id);
    $novel_title = $novel ? $novel->post_title : '';
    $novel_url   = $novel ? get_permalink($novel_id) : '#';
    $original_author = get_post_meta($novel_id, 'novel_original_author', true);
    $translator      = get_post_meta($novel_id, 'novel_translator', true);
    
    // Navigation
    $prev_chapter = novel_get_adjacent_chapter($chapter_id, 'prev');
    $next_chapter = novel_get_adjacent_chapter($chapter_id, 'next');
    
    // Views increment
    $new_views = $views + 1;
    update_post_meta($chapter_id, 'chapter_views', $new_views);
    
    // Satisfaction percentage
    $total_votes   = $likes + $dislikes;
    $satisfaction   = $total_votes > 0 ? round(($likes / $total_votes) * 100) : 0;
    $sat_class      = $satisfaction >= 80 ? 'good' : ($satisfaction >= 50 ? 'mid' : 'bad');
    
    // User vote check
    $user_vote = '';
    $has_access = true;
    $is_logged_in = is_user_logged_in();
    $user_coins = 0;
    
    if ($is_logged_in) {
        $user_id = get_current_user_id();
        global $wpdb;
        
        // Check user vote
        $votes_table = $wpdb->prefix . 'chapter_votes';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$votes_table}'") === $votes_table) {
            $user_vote = $wpdb->get_var($wpdb->prepare(
                "SELECT vote_type FROM {$votes_table} WHERE user_id = %d AND chapter_id = %d",
                $user_id, $chapter_id
            ));
        }
        
        // Check VIP access
        if ($is_vip) {
            $has_subscription = apply_filters('novel_user_has_subscription', false, $user_id);
            
            $purchase_table = $wpdb->prefix . 'chapter_purchases';
            $has_purchased = false;
            if ($wpdb->get_var("SHOW TABLES LIKE '{$purchase_table}'") === $purchase_table) {
                $has_purchased = (bool) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$purchase_table} WHERE user_id = %d AND chapter_id = %d",
                    $user_id, $chapter_id
                ));
            }
            
            $has_access = $has_subscription || $has_purchased;
            
            // Get user coins
            $user_coins = (int) get_user_meta($user_id, 'novel_coins', true);
        }
        
        // Record reading history
        $history_table = $wpdb->prefix . 'reading_history';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$history_table}'") === $history_table) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$history_table} WHERE user_id = %d AND chapter_id = %d",
                $user_id, $chapter_id
            ));
            
            if ($existing) {
                $wpdb->update($history_table, 
                    ['read_at' => current_time('mysql')],
                    ['id' => $existing]
                );
            } else {
                $wpdb->insert($history_table, [
                    'user_id'    => $user_id,
                    'novel_id'   => $novel_id,
                    'chapter_id' => $chapter_id,
                    'read_at'    => current_time('mysql'),
                ]);
            }
        }
        
        // Update library last chapter
        $library_table = $wpdb->prefix . 'user_library';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$library_table}'") === $library_table) {
            $lib_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$library_table} WHERE user_id = %d AND novel_id = %d",
                $user_id, $novel_id
            ));
            
            if ($lib_exists) {
                $wpdb->update($library_table,
                    ['last_chapter_id' => $chapter_id, 'updated_at' => current_time('mysql')],
                    ['id' => $lib_exists]
                );
            } else {
                $wpdb->insert($library_table, [
                    'user_id'         => $user_id,
                    'novel_id'        => $novel_id,
                    'status'          => 'reading',
                    'last_chapter_id' => $chapter_id,
                    'created_at'      => current_time('mysql'),
                    'updated_at'      => current_time('mysql'),
                ]);
            }
        }
    }
?>

<!-- Progress Bar -->
<div class="chapter-progress-bar" id="chapterProgressBar"></div>

<main class="single-chapter-page" id="chapterPage">
    
    <!-- Breadcrumb -->
    <nav class="chapter-breadcrumb" aria-label="مسیر">
        <div class="container">
            <a href="<?php echo home_url(); ?>">خانه</a>
            <span class="sep">›</span>
            <a href="<?php echo esc_url($novel_url); ?>"><?php echo esc_html($novel_title); ?></a>
            <span class="sep">›</span>
            <span class="current">قسمت <?php echo esc_html($chapter_num); ?></span>
        </div>
    </nav>
    
    <div class="container">
        
        <!-- ═══ هدر قسمت ═══ -->
        <header class="chapter-header">
            <h1 class="chapter-main-title">
                قسمت <?php echo esc_html($chapter_num); ?>
                <?php if ($chapter_title) : ?>
                    <span class="chapter-sub-title">: <?php echo esc_html($chapter_title); ?></span>
                <?php endif; ?>
            </h1>
            
            <div class="chapter-novel-link">
                <a href="<?php echo esc_url($novel_url); ?>">📖 <?php echo esc_html($novel_title); ?></a>
            </div>
            
            <div class="chapter-meta-row">
                <span class="chapter-author">
                    نویسنده: <a href="<?php echo get_author_posts_url(get_the_author_meta('ID')); ?>"><?php echo esc_html($original_author ?: get_the_author()); ?></a>
                </span>
                <?php if ($translator) : ?>
                    <span class="chapter-translator">مترجم: <?php echo esc_html($translator); ?></span>
                <?php endif; ?>
            </div>
            
            <div class="chapter-info-bar">
                <span>⏱ <?php echo esc_html($reading_time); ?> دقیقه</span>
                <span>📝 <?php echo number_format_i18n($word_count); ?> کلمه</span>
                <span>📅 <?php echo get_the_date('j F Y'); ?></span>
                <span>👁 <?php echo number_format_i18n($new_views); ?></span>
            </div>
            
            <!-- Like/Dislike (top) -->
            <div class="chapter-votes" id="chapterVotes">
                <button class="vote-btn vote-like <?php echo $user_vote === 'like' ? 'active' : ''; ?>" 
                        data-type="like" data-chapter="<?php echo $chapter_id; ?>">
                    👍 <span class="vote-count"><?php echo number_format_i18n($likes); ?></span>
                </button>
                <button class="vote-btn vote-dislike <?php echo $user_vote === 'dislike' ? 'active' : ''; ?>" 
                        data-type="dislike" data-chapter="<?php echo $chapter_id; ?>">
                    👎 <span class="vote-count"><?php echo number_format_i18n($dislikes); ?></span>
                </button>
                <?php if ($total_votes > 0) : ?>
                    <span class="vote-satisfaction satisfaction-<?php echo $sat_class; ?>">
                        <?php echo $satisfaction; ?>٪ پسندیدند
                    </span>
                <?php endif; ?>
            </div>
        </header>
        
        <!-- ═══ ناوبری بالا ═══ -->
        <nav class="chapter-navigation chapter-nav-top">
            <?php if ($prev_chapter) : ?>
                <a href="<?php echo novel_get_chapter_permalink($prev_chapter->ID); ?>" class="nav-btn nav-prev">
                    ← قسمت قبلی
                </a>
            <?php else : ?>
                <span class="nav-btn nav-prev disabled">← اولین قسمت</span>
            <?php endif; ?>
            
            <a href="<?php echo esc_url($novel_url); ?>#chapters" class="nav-btn nav-list">
                📋 لیست قسمت‌ها
            </a>
            
            <?php if ($next_chapter) : ?>
                <a href="<?php echo novel_get_chapter_permalink($next_chapter->ID); ?>" class="nav-btn nav-next">
                    قسمت بعدی →
                </a>
            <?php else : ?>
                <span class="nav-btn nav-next disabled">آخرین قسمت</span>
            <?php endif; ?>
        </nav>
        
        <?php if ($is_vip && !$has_access) : ?>
        <!-- ═══ پیام قفل VIP ═══ -->
        <section class="chapter-locked" id="chapterLocked">
            <div class="locked-content">
                <div class="locked-icon">
                    <svg class="lock-svg" viewBox="0 0 80 80" width="80" height="80">
                        <rect x="15" y="35" width="50" height="35" rx="5" fill="var(--primary)" opacity="0.2" stroke="var(--primary)" stroke-width="2"/>
                        <path d="M25 35V25a15 15 0 0130 0v10" fill="none" stroke="var(--primary)" stroke-width="3" stroke-linecap="round"/>
                        <circle cx="40" cy="50" r="4" fill="var(--primary)"/>
                        <line x1="40" y1="54" x2="40" y2="60" stroke="var(--primary)" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
                
                <h2 class="locked-title">🔒 این قسمت مخصوص اعضای ویژه است</h2>
                <p class="locked-desc">برای خواندن این قسمت یکی از گزینه‌های زیر را انتخاب کنید:</p>
                
                <div class="locked-options">
                    <a href="<?php echo home_url('/subscription/'); ?>" class="locked-option locked-subscription">
                        <span class="option-icon">💎</span>
                        <span class="option-title">خرید اشتراک ویژه</span>
                        <span class="option-desc">دسترسی نامحدود به همه قسمت‌ها</span>
                    </a>
                    
                    <div class="locked-divider">
                        <span>یا</span>
                    </div>
                    
                    <button class="locked-option locked-coin-buy" 
                            id="btnBuyWithCoins"
                            data-chapter="<?php echo $chapter_id; ?>"
                            data-price="<?php echo esc_attr($coin_price); ?>"
                            <?php echo $user_coins < $coin_price ? 'disabled' : ''; ?>>
                        <span class="option-icon">🪙</span>
                        <span class="option-title">خرید با <?php echo number_format_i18n($coin_price); ?> سکه</span>
                        <span class="option-desc">
                            موجودی شما: <?php echo number_format_i18n($user_coins); ?> 🪙
                            <?php if ($user_coins < $coin_price) : ?>
                                <br><small class="insufficient">موجودی کافی نیست. <a href="<?php echo home_url('/purchase-coins/'); ?>">شارژ سکه</a></small>
                            <?php endif; ?>
                        </span>
                    </button>
                </div>
                
                <?php if (!$is_logged_in) : ?>
                    <p class="locked-login-notice">
                        ابتدا <a href="<?php echo wp_login_url(get_permalink()); ?>">وارد شوید</a> یا 
                        <a href="<?php echo home_url('/register/'); ?>">ثبت‌نام کنید</a>.
                    </p>
                <?php endif; ?>
            </div>
        </section>
        
        <?php else : ?>
        
        <!-- Reader Settings Button -->
        <div class="reader-toolbar">
            <button class="btn-reader-settings" id="btnReaderSettings" title="تنظیمات خواندن">
                ⚙️ تنظیمات
            </button>
            <button class="btn-fullscreen" id="btnFullscreen" title="تمام‌صفحه">
                ⛶ تمام‌صفحه
            </button>
            <button class="btn-report-chapter" id="btnReportChapter" title="گزارش خطا">
                🚩 گزارش
            </button>
        </div>
        
        <!-- Reader Settings Panel -->
        <div class="reader-settings-panel" id="readerSettingsPanel" style="display:none;">
            <div class="settings-panel-header">
                <h3>⚙️ تنظیمات خواندن</h3>
                <button class="btn-close-settings" id="btnCloseSettings">×</button>
            </div>
            <div class="settings-panel-body">
                <!-- Font Size -->
                <div class="setting-item">
                    <label>اندازه فونت</label>
                    <div class="setting-control">
                        <button class="font-size-btn" data-action="decrease">A-</button>
                        <input type="range" id="fontSizeSlider" min="12" max="28" value="16" />
                        <button class="font-size-btn" data-action="increase">A+</button>
                        <span class="setting-value" id="fontSizeValue">16px</span>
                    </div>
                </div>
                
                <!-- Font Family -->
                <div class="setting-item">
                    <label>فونت</label>
                    <div class="setting-control font-options">
                        <button class="font-btn active" data-font="IRANSans" style="font-family:IRANSans,sans-serif;">ایران‌سنس</button>
                        <button class="font-btn" data-font="Vazirmatn" style="font-family:Vazirmatn,sans-serif;">وزیرمتن</button>
                        <button class="font-btn" data-font="Sahifeh" style="font-family:Sahifeh,serif;">صحیفه</button>
                        <button class="font-btn" data-font="Titr" style="font-family:Titr,sans-serif;">تیتر</button>
                    </div>
                </div>
                
                <!-- Theme -->
                <div class="setting-item">
                    <label>تم صفحه</label>
                    <div class="setting-control theme-options">
                        <button class="theme-btn active" data-theme="light" title="روشن">☀️</button>
                        <button class="theme-btn" data-theme="sepia" title="سپیا">📜</button>
                        <button class="theme-btn" data-theme="dark" title="تاریک">🌑</button>
                        <button class="theme-btn" data-theme="black" title="مشکی">⬛</button>
                    </div>
                </div>
                
                <!-- Line Height -->
                <div class="setting-item">
                    <label>فاصله خطوط</label>
                    <div class="setting-control">
                        <input type="range" id="lineHeightSlider" min="14" max="28" value="18" step="1" />
                        <span class="setting-value" id="lineHeightValue">1.8</span>
                    </div>
                </div>
                
                <!-- Content Width -->
                <div class="setting-item">
                    <label>عرض متن</label>
                    <div class="setting-control width-options">
                        <button class="width-btn" data-width="600">باریک</button>
                        <button class="width-btn active" data-width="800">متوسط</button>
                        <button class="width-btn" data-width="1000">عریض</button>
                        <button class="width-btn" data-width="full">تمام</button>
                    </div>
                </div>
                
                <!-- Paragraph Spacing -->
                <div class="setting-item">
                    <label>فاصله پاراگراف</label>
                    <div class="setting-control">
                        <input type="range" id="paraSpacingSlider" min="8" max="32" value="16" step="2" />
                        <span class="setting-value" id="paraSpacingValue">16px</span>
                    </div>
                </div>
                
                <!-- Reset -->
                <div class="setting-item">
                    <button class="btn-reset-settings" id="btnResetSettings">↺ بازنشانی پیش‌فرض</button>
                </div>
            </div>
        </div>
        
        <!-- ═══ Previous Chapter Recap ═══ -->
        <?php if ($recap) : ?>
        <div class="chapter-recap" id="chapterRecap">
            <div class="recap-header">
                <span class="recap-title">📋 خلاصه قسمت قبل (قسمت <?php echo esc_html($chapter_num - 1); ?>):</span>
                <button class="recap-toggle" id="recapToggle">بستن ▲</button>
            </div>
            <div class="recap-body" id="recapBody">
                <p><?php echo esc_html($recap); ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- ═══ محتوای قسمت ═══ -->
        <article class="chapter-content" id="chapterContent">
            <?php the_content(); ?>
        </article>
        
        <!-- ═══ لایک/دیسلایک (پایین) ═══ -->
        <div class="chapter-votes chapter-votes-bottom" id="chapterVotesBottom">
            <button class="vote-btn vote-like <?php echo $user_vote === 'like' ? 'active' : ''; ?>" 
                    data-type="like" data-chapter="<?php echo $chapter_id; ?>">
                👍 <span class="vote-count"><?php echo number_format_i18n($likes); ?></span>
            </button>
            <button class="vote-btn vote-dislike <?php echo $user_vote === 'dislike' ? 'active' : ''; ?>" 
                    data-type="dislike" data-chapter="<?php echo $chapter_id; ?>">
                👎 <span class="vote-count"><?php echo number_format_i18n($dislikes); ?></span>
            </button>
            <?php if ($total_votes > 0) : ?>
                <span class="vote-satisfaction satisfaction-<?php echo $sat_class; ?>">
                    <?php echo $satisfaction; ?>٪ پسندیدند
                </span>
            <?php endif; ?>
        </div>
        
        <?php endif; // end VIP check ?>
        
        <!-- ═══ ناوبری پایین ═══ -->
        <nav class="chapter-navigation chapter-nav-bottom">
            <?php if ($prev_chapter) : ?>
                <a href="<?php echo novel_get_chapter_permalink($prev_chapter->ID); ?>" class="nav-btn nav-prev">
                    ← قسمت قبلی
                </a>
            <?php else : ?>
                <span class="nav-btn nav-prev disabled">← اولین قسمت</span>
            <?php endif; ?>
            
            <a href="<?php echo esc_url($novel_url); ?>#chapters" class="nav-btn nav-list">
                📋 لیست
            </a>
            
            <?php if ($next_chapter) : ?>
                <a href="<?php echo novel_get_chapter_permalink($next_chapter->ID); ?>" class="nav-btn nav-next">
                    قسمت بعدی →
                </a>
            <?php else : ?>
                <span class="nav-btn nav-next disabled">آخرین قسمت</span>
            <?php endif; ?>
        </nav>
        
        <!-- ═══ گزارش خطا Modal ═══ -->
        <div class="report-modal" id="reportModal" style="display:none;">
            <div class="report-modal__overlay"></div>
            <div class="report-modal__content">
                <div class="report-modal__header">
                    <h3>🚩 گزارش خطا</h3>
                    <button class="report-modal__close" id="reportModalClose">×</button>
                </div>
                <div class="report-modal__body">
                    <div class="report-options">
                        <label class="report-option">
                            <input type="radio" name="report_reason" value="typo" checked /> خطای تایپی
                        </label>
                        <label class="report-option">
                            <input type="radio" name="report_reason" value="translation" /> ترجمه نادرست
                        </label>
                        <label class="report-option">
                            <input type="radio" name="report_reason" value="inappropriate" /> محتوای نامناسب
                        </label>
                        <label class="report-option">
                            <input type="radio" name="report_reason" value="duplicate" /> تکراری
                        </label>
                        <label class="report-option">
                            <input type="radio" name="report_reason" value="broken" /> لینک/تصویر خراب
                        </label>
                        <label class="report-option">
                            <input type="radio" name="report_reason" value="other" /> سایر
                        </label>
                    </div>
                    <textarea class="report-description" id="reportDescription" 
                              placeholder="توضیحات (اختیاری)..." rows="3" maxlength="500"></textarea>
                </div>
                <div class="report-modal__footer">
                    <button class="btn-submit-report" id="btnSubmitReport">ارسال گزارش</button>
                    <button class="btn-cancel-report" id="btnCancelReport">انصراف</button>
                </div>
            </div>
        </div>
        
        <!-- ═══ اشتراک‌گذاری ═══ -->
        <section class="chapter-share">
            <div class="share-buttons">
                <a href="https://t.me/share/url?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode(get_the_title()); ?>" 
                   class="share-btn share-telegram" target="_blank" rel="noopener">تلگرام</a>
                <a href="https://api.whatsapp.com/send?text=<?php echo urlencode(get_the_title() . ' ' . get_permalink()); ?>" 
                   class="share-btn share-whatsapp" target="_blank" rel="noopener">واتس‌اپ</a>
                <button class="share-btn share-copy" data-url="<?php echo esc_url(get_permalink()); ?>">📋 کپی</button>
            </div>
        </section>
        
        <!-- ═══ دیدگاه‌ها ═══ -->
        <section class="chapter-comments-section" id="comments">
            <?php get_template_part('templates/comments/comments-section'); ?>
        </section>
        
    </div><!-- .container -->
</main>

<?php endwhile; ?>

<?php get_footer(); ?>