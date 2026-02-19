<?php
/**
 * فایل: header.php
 * توضیح: هدر قالب ناول - بنر اطلاعیه + هدر sticky با glassmorphism + منو همبرگری + جستجو + دارک‌مود
 * نسخه: 2.0.0
 * وابستگی: main.css, main.js
 */

if (!defined('ABSPATH')) exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> dir="rtl" data-theme="light">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#6C5CE7">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
    <!-- تشخیص دارک‌مود قبل رندر (جلوگیری از flash) -->
    <script>
        (function(){
            var t = localStorage.getItem('novel-theme');
            var p = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (t === 'dark' || (!t && p)) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>
</head>
<body <?php body_class(); ?>>

<?php wp_body_open(); ?>

<!-- Skip to content (a11y) -->
<a href="#main-content" class="skip-to-content">رفتن به محتوا</a>

<?php
// ═══ بنر اطلاعیه ═══
$announcement = get_option('novel_announcement_text', '');
$announcement_active = get_option('novel_module_announcement_banner', '1') === '1';
if ($announcement_active && !empty($announcement)) :
?>
<div class="novel-announcement-bar" role="alert">
    <div class="novel-container">
        <?php echo wp_kses_post($announcement); ?>
    </div>
    <button class="novel-announcement-close" aria-label="بستن">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
    </button>
</div>
<?php endif; ?>


<!--فاز 13 -->

<?php
/**
 * Announcement Banner - اصلاح شده
 * تمام آپشن‌ها از novel_announcement_ استفاده می‌کنند
 * 
 * اضافه شود به header.php بعد از <body <?php body_class(); ?>>
 * و قبل از <header>
 * 
 * @package suspended developer
 * @since 3.0.0
 */

// === اضافه شود بعد از <body> ===
?>

<?php
// ═══ بنر اطلاعیه سایت (Announcement Banner) ═══
// ⚠️ novel_announcement_ prefix (نه novel_banner_ که مربوط به بنر نویسنده است)
$announcement_enabled = get_option('novel_announcement_enabled', false);

if ($announcement_enabled):
    $announcement_text       = get_option('novel_announcement_text', '');
    $announcement_type       = get_option('novel_announcement_type', 'info');
    $announcement_link_url   = get_option('novel_announcement_link_url', '');
    $announcement_link_text  = get_option('novel_announcement_link_text', '');
    $announcement_start      = get_option('novel_announcement_start_date', '');
    $announcement_end        = get_option('novel_announcement_end_date', '');
    $announcement_dismissible = get_option('novel_announcement_dismissible', true);
    $announcement_audience   = get_option('novel_announcement_audience', 'all');

    // ═══ بررسی شرایط نمایش ═══

    $show_announcement = true;

    // بررسی مخاطب
    if ($announcement_audience === 'logged_in' && !is_user_logged_in()) {
        $show_announcement = false;
    }
    if ($announcement_audience === 'logged_out' && is_user_logged_in()) {
        $show_announcement = false;
    }

    // بررسی تاریخ
    $now = current_time('mysql');
    if (!empty($announcement_start) && $now < $announcement_start) {
        $show_announcement = false;
    }
    if (!empty($announcement_end) && $now > $announcement_end) {
        $show_announcement = false;
    }

    // بررسی محتوا
    if (empty(trim($announcement_text))) {
        $show_announcement = false;
    }

    // شناسه یکتا (برای localStorage - تغییر با هر بار ویرایش متن)
    $announcement_hash = md5($announcement_text . $announcement_type);

    if ($show_announcement):
        // آیکون‌های SVG هر نوع
        $announcement_icons = [
            'info'    => '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>',
            'warning' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>',
            'danger'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>',
            'success' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>',
            'promo'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
        ];
        ?>
        <div class="novel-announcement novel-announcement--<?php echo esc_attr($announcement_type); ?>"
             id="novelAnnouncement"
             data-announcement-id="<?php echo esc_attr($announcement_hash); ?>"
             data-dismissible="<?php echo $announcement_dismissible ? '1' : '0'; ?>"
             style="display: none;">
            <div class="novel-announcement__inner novel-container">
                <span class="novel-announcement__icon">
                    <?php echo $announcement_icons[$announcement_type] ?? $announcement_icons['info']; ?>
                </span>
                <div class="novel-announcement__content">
                    <span class="novel-announcement__text">
                        <?php echo wp_kses($announcement_text, [
                            'a'      => ['href' => [], 'target' => [], 'rel' => []],
                            'b'      => [],
                            'em'     => [],
                            'strong' => [],
                        ]); ?>
                    </span>
                    <?php if (!empty($announcement_link_url) && !empty($announcement_link_text)): ?>
                        <a href="<?php echo esc_url($announcement_link_url); ?>"
                           class="novel-announcement__link">
                            <?php echo esc_html($announcement_link_text); ?>
                        </a>
                    <?php endif; ?>
                </div>
                <?php if ($announcement_dismissible): ?>
                    <button class="novel-announcement__close" id="novelAnnouncementClose"
                            aria-label="بستن بنر" title="بستن">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" 
                             stroke="currentColor" stroke-width="2.5">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <script>
        (function() {
            var el = document.getElementById('novelAnnouncement');
            if (!el) return;

            var announcementId = el.dataset.announcementId;
            var dismissible = el.dataset.dismissible === '1';
            var storageKey = 'novel_announcement_closed_' + announcementId;

            // بررسی آیا قبلاً بسته شده (۲۴ ساعت)
            if (dismissible) {
                var closedTime = localStorage.getItem(storageKey);
                if (closedTime) {
                    var elapsed = Date.now() - parseInt(closedTime, 10);
                    if (elapsed < 86400000) { // 24 ساعت = 86400000ms
                        return; // نمایش نده
                    }
                    localStorage.removeItem(storageKey);
                }
            }

            // نمایش بنر با انیمیشن
            el.style.display = '';
            el.style.animation = 'novelAnnouncementSlideDown 0.4s ease';

            // دکمه بسته شدن
            if (dismissible) {
                var closeBtn = document.getElementById('novelAnnouncementClose');
                if (closeBtn) {
                    closeBtn.addEventListener('click', function() {
                        el.style.animation = 'novelAnnouncementSlideUp 0.3s ease forwards';
                        localStorage.setItem(storageKey, Date.now().toString());
                        setTimeout(function() { el.remove(); }, 300);
                    });
                }
            }
        })();
        </script>
    <?php endif; endif; ?>

<!-- ═══ Header ═══ -->
<header class="novel-header" role="banner">
    <div class="novel-header-inner">

        <!-- دکمه همبرگری (موبایل/تبلت) -->
        <button class="novel-mobile-menu-btn novel-header-btn" aria-label="منو" aria-expanded="false">
            <svg class="novel-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>

        <!-- لوگو -->
        <a href="<?php echo esc_url(home_url('/')); ?>" class="novel-logo" aria-label="<?php bloginfo('name'); ?>">
            <?php
            $logo_url = get_option('novel_site_logo', '');
            if ($logo_url) :
            ?>
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php bloginfo('name'); ?>" width="120" height="36" loading="eager">
            <?php else : ?>
                <span class="novel-logo-text"><?php bloginfo('name'); ?></span>
            <?php endif; ?>
        </a>

        <!-- منوی اصلی (دسکتاپ) -->
        <nav class="novel-nav-menu" role="navigation" aria-label="منوی اصلی">
            <?php
            wp_nav_menu([
                'theme_location' => 'primary',
                'container'      => false,
                'items_wrap'     => '%3$s',
                'depth'          => 1,
                'fallback_cb'    => function() {
                    $links = [
                        ['url' => home_url('/'), 'label' => 'خانه'],
                        ['url' => get_post_type_archive_link('novel'), 'label' => 'رمان‌ها'],
                        ['url' => home_url('/ranking/'), 'label' => 'رتبه‌بندی'],
                        ['url' => home_url('/authors/'), 'label' => 'نویسندگان'],
                        ['url' => home_url('/genres/'), 'label' => 'ژانرها'],
                    ];
                    foreach ($links as $link) {
                        $active = (untrailingslashit($_SERVER['REQUEST_URI']) === untrailingslashit(wp_make_link_relative($link['url']))) ? ' active' : '';
                        echo '<a href="' . esc_url($link['url']) . '" class="' . $active . '">' . esc_html($link['label']) . '</a>';
                    }
                },
            ]);
            ?>
        </nav>

        <!-- دکمه‌های هدر -->
        <div class="novel-header-actions">

            <!-- جستجو -->
                /*فاز 8*/
            /**
             * Header - Search Section Addition
             * 
             * این بخش را داخل header.php در جای مناسب (معمولاً داخل nav) اضافه کنید
             * بعد از لوگو و قبل از دکمه‌های سمت چپ
             *
             * @package suspended-flavor
             * @since 3.0.0
             */
            

            <!-- ═══ Header Search ═══ -->
            <div class="header-search-wrap">
                
                <!-- Desktop: Full search field -->
                <div class="header-search-field">
                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" 
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" id="headerSearchInput" 
                        placeholder="<?php esc_attr_e('جستجوی رمان، نویسنده، تگ...', 'flavor'); ?>"
                        autocomplete="off"
                        role="combobox"
                        aria-expanded="false"
                        aria-controls="headerSearchDropdown"
                        aria-autocomplete="list">
                </div>
                
                <!-- Mobile: Search toggle button -->
                <button class="header-search-toggle" aria-label="<?php esc_attr_e('جستجو', 'flavor'); ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" 
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
                
                <!-- Mobile cancel button (shown in fullscreen mode) -->
                <button class="search-mobile-cancel"><?php esc_html_e('لغو', 'flavor'); ?></button>
                
                <!-- Search dropdown results -->
                <div id="headerSearchDropdown" role="listbox" aria-label="<?php esc_attr_e('نتایج جستجو', 'flavor'); ?>">
                    <!-- Filled by JS -->
                </div>
                
            </div>

            <!-- Search overlay (mobile) -->
            <div id="headerSearchOverlay"></div>




            <!-- دارک‌مود -->
            <button class="novel-theme-toggle novel-header-btn" aria-label="تغییر حالت تاریک/روشن">
                <!-- ماه (نمایش در حالت روشن) -->
                <svg class="novel-icon icon-moon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                </svg>
                <!-- خورشید (نمایش در حالت تاریک) -->
                <svg class="novel-icon icon-sun" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="12" cy="12" r="5"></circle>
                    <line x1="12" y1="1" x2="12" y2="3"></line>
                    <line x1="12" y1="21" x2="12" y2="23"></line>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                    <line x1="1" y1="12" x2="3" y2="12"></line>
                    <line x1="21" y1="12" x2="23" y2="12"></line>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                </svg>
            </button>

            <?php if (is_user_logged_in()) :
                $current_user = wp_get_current_user();
                $avatar_url = novel_get_avatar_url($current_user->ID);
                $unread = 0;
                if (class_exists('Novel_Notifications')) {
                    $unread = Novel_Notifications::get_unread_count($current_user->ID);
                }
            ?>
                <!-- اعلان‌ها -->
                <button class="novel-header-btn" id="headerNotifBtn" aria-label="اعلان‌ها" data-requires-login>
                    <svg class="novel-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span class="novel-notif-badge" data-count="<?php echo (int) $unread; ?>">
                        <?php if ($unread > 0) echo esc_html(novel_fa_num($unread > 99 ? '99+' : $unread)); ?>
                    </span>
                </button>

                <!-- پروفایل -->
                <div class="novel-header-profile">
                    <img src="<?php echo esc_url($avatar_url); ?>" 
                         alt="<?php echo esc_attr($current_user->display_name); ?>" 
                         class="novel-header-avatar"
                         width="36" height="36"
                         loading="lazy"
                         data-fallback="<?php echo esc_url(NOVEL_ASSETS . 'avatars/avatar-1.png'); ?>"
                         aria-haspopup="true"
                         aria-expanded="false">

                    <!-- Dropdown -->
                    <div class="novel-profile-dropdown" role="menu">
                        <div class="novel-dropdown-header">
                            <img src="<?php echo esc_url($avatar_url); ?>" alt="" width="44" height="44" loading="lazy">
                            <div class="novel-dropdown-header-info">
                                <div class="name"><?php echo esc_html($current_user->display_name); ?></div>
                                <div class="email"><?php echo esc_html($current_user->user_email); ?></div>
                            </div>
                        </div>
                        <div class="novel-dropdown-menu">
                            <a href="<?php echo esc_url(home_url('/dashboard/')); ?>" role="menuitem">
                                <span>📊</span> داشبورد
                            </a>
                            <a href="<?php echo esc_url(home_url('/library/')); ?>" role="menuitem">
                                <span>📚</span> کتابخانه
                            </a>
                            <?php if (Novel_Settings::is_module_active('coins')) : ?>
                            <a href="<?php echo esc_url(home_url('/dashboard/?tab=coins')); ?>" role="menuitem">
                                <span>🪙</span> <span class="coin-display">سکه: <?php echo esc_html(novel_fa_num(get_user_meta($current_user->ID, 'novel_coin_balance', true) ?: 0)); ?></span>
                            </a>
                            <?php endif; ?>
                            <a href="<?php echo esc_url(home_url('/dashboard/?tab=settings')); ?>" role="menuitem">
                                <span>⚙️</span> تنظیمات
                            </a>
                            <div class="separator"></div>
                            <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>" role="menuitem">
                                <span>🚪</span> خروج
                            </a>
                        </div>
                    </div>
                </div>

            <?php else : ?>
                <!-- کاربر مهمان -->
                <a href="<?php echo esc_url(home_url('/login/')); ?>" class="novel-btn novel-btn-primary novel-btn-sm">
                    ورود / ثبت‌نام
                </a>
            <?php endif; ?>

        </div>
    </div>
</header>

<!-- ═══ منوی همبرگری موبایل (سایدبار) ═══ -->
<div class="novel-mobile-overlay" aria-hidden="true"></div>
<aside class="novel-mobile-menu" aria-hidden="true" role="navigation" aria-label="منوی موبایل">
    <div class="novel-mobile-menu-header">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="novel-logo">
            <?php if ($logo_url ?? '') : ?>
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php bloginfo('name'); ?>" width="100" height="30" loading="lazy">
            <?php else : ?>
                <span class="novel-logo-text" style="font-size: 1.1rem;"><?php bloginfo('name'); ?></span>
            <?php endif; ?>
        </a>
        <button class="novel-mobile-menu-close novel-header-btn" aria-label="بستن منو">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>

    <?php if (is_user_logged_in()) :
        $cu = wp_get_current_user();
    ?>
    <div class="novel-mobile-menu-profile">
        <img src="<?php echo esc_url(novel_get_avatar_url($cu->ID)); ?>" alt="" width="48" height="48" loading="lazy">
        <div class="info">
            <div class="name"><?php echo esc_html($cu->display_name); ?></div>
            <div class="role"><?php echo esc_html(novel_get_user_badge($cu->ID)); ?></div>
        </div>
    </div>
    <?php endif; ?>

    <div class="novel-mobile-menu-nav">
        <a href="<?php echo esc_url(home_url('/')); ?>">
            <svg class="novel-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            خانه
        </a>
        <a href="<?php echo esc_url(get_post_type_archive_link('novel')); ?>">
            <svg class="novel-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
            رمان‌ها
        </a>
        <a href="<?php echo esc_url(home_url('/ranking/')); ?>">
            <svg class="novel-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
            رتبه‌بندی
        </a>
        <a href="<?php echo esc_url(home_url('/authors/')); ?>">
            <svg class="novel-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 00-3-3.87"></path><path d="M16 3.13a4 4 0 010 7.75"></path></svg>
            نویسندگان
        </a>
        <a href="<?php echo esc_url(home_url('/genres/')); ?>">
            <svg class="novel-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            ژانرها
        </a>

        <?php if (is_user_logged_in()) : ?>
        <a href="<?php echo esc_url(home_url('/dashboard/')); ?>">
            <svg class="novel-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
            داشبورد
        </a>
        <a href="<?php echo esc_url(home_url('/library/')); ?>">
            <svg class="novel-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"></path></svg>
            کتابخانه
        </a>
        <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">
            <svg class="novel-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            خروج
        </a>
        <?php else : ?>
        <a href="<?php echo esc_url(home_url('/login/')); ?>">
            <svg class="novel-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
            ورود / ثبت‌نام
        </a>
        <?php endif; ?>
    </div>
</aside>

<!-- Pull to Refresh indicator -->
<div class="novel-ptr-indicator" aria-hidden="true">
    <div class="spinner"></div>
</div>

<!-- شروع محتوای اصلی -->
<main id="main-content" class="novel-main" role="main">