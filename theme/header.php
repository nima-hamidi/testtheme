<?php
/**
 * هدر قالب فلیور نوول
 * شامل: منوی ناوبری، جستجو، دکمه‌های کاربری، حالت تاریک
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> dir="rtl" data-theme="light">
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="theme-color" content="#6C5CE7">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<!-- نوار پیشرفت خواندن (فقط در صفحه فصل) -->
<?php if ( is_singular( 'chapter' ) ) : ?>
    <div class="fn-progress-bar" id="fnProgressBar"></div>
<?php endif; ?>

<!-- ===== هدر اصلی ===== -->
<header class="fn-header" id="fnHeader">
    <div class="fn-container">
        <div class="fn-header__inner">

            <!-- لوگو -->
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="fn-header__logo">
                <?php if ( has_custom_logo() ) : ?>
                    <?php
                    $logo_id  = get_theme_mod( 'custom_logo' );
                    $logo_url = wp_get_attachment_image_url( $logo_id, 'full' );
                    ?>
                    <img src="<?php echo esc_url( $logo_url ); ?>"
                         alt="<?php bloginfo( 'name' ); ?>"
                         style="height: 36px; width: auto;">
                <?php else : ?>
                    <span class="fn-header__logo-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                            <line x1="8" y1="7" x2="16" y2="7"/>
                            <line x1="8" y1="11" x2="13" y2="11"/>
                        </svg>
                    </span>
                    <span><?php bloginfo( 'name' ); ?></span>
                <?php endif; ?>
            </a>

            <!-- منوی ناوبری اصلی -->
            <nav class="fn-nav" id="fnMainNav" role="navigation" aria-label="<?php esc_attr_e( 'منوی اصلی', 'flavor-novel' ); ?>">
                <?php
                if ( has_nav_menu( 'primary' ) ) {
                    wp_nav_menu( array(
                        'theme_location' => 'primary',
                        'container'      => false,
                        'items_wrap'     => '%3$s',
                        'walker'         => new FN_Nav_Walker(),
                        'fallback_cb'    => false,
                    ) );
                } else {
                    // منوی پیش‌فرض اگر منویی ثبت نشده بود
                    ?>
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="fn-nav__link <?php echo is_front_page() ? 'active' : ''; ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px;">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                        خانه
                    </a>
                    <a href="<?php echo esc_url( get_post_type_archive_link( 'novel' ) ); ?>" class="fn-nav__link <?php echo is_post_type_archive( 'novel' ) ? 'active' : ''; ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px;">
                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                        </svg>
                        کتابخانه
                    </a>
                    <a href="<?php echo esc_url( home_url( '/genre/' ) ); ?>" class="fn-nav__link">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px;">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                        ژانرها
                    </a>
                    <a href="<?php echo esc_url( home_url( '/ranking/' ) ); ?>" class="fn-nav__link">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px;">
                            <line x1="18" y1="20" x2="18" y2="10"/>
                            <line x1="12" y1="20" x2="12" y2="4"/>
                            <line x1="6" y1="20" x2="6" y2="14"/>
                        </svg>
                        رتبه‌بندی
                    </a>
                <?php } ?>
            </nav>

            <!-- جستجو -->
            <div class="fn-search" id="fnSearch">
                <form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                    <span class="fn-search__icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                    </span>
                    <input type="search"
                           class="fn-search__input"
                           name="s"
                           placeholder="<?php esc_attr_e( 'جستجوی رمان...', 'flavor-novel' ); ?>"
                           value="<?php echo esc_attr( get_search_query() ); ?>"
                           autocomplete="off">
                    <input type="hidden" name="post_type" value="novel">
                </form>

                <!-- نتایج زنده جستجو -->
                <div class="fn-search-results" id="fnSearchResults"></div>
            </div>

            <!-- دکمه‌های سمت چپ -->
            <div class="fn-header__actions">

                <!-- دکمه جستجوی موبایل -->
                <button class="fn-btn--icon fn-mobile-search-btn" id="fnMobileSearchBtn" aria-label="جستجو">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                </button>

                <!-- تاگل حالت تاریک/روشن -->
                <button class="fn-btn--icon" id="fnThemeToggle" aria-label="تغییر حالت نمایش">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="fn-icon-sun" id="fnIconSun">
                        <circle cx="12" cy="12" r="5"/>
                        <line x1="12" y1="1" x2="12" y2="3"/>
                        <line x1="12" y1="21" x2="12" y2="23"/>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                        <line x1="1" y1="12" x2="3" y2="12"/>
                        <line x1="21" y1="12" x2="23" y2="12"/>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                    </svg>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="fn-icon-moon fn-hidden" id="fnIconMoon">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                    </svg>
                </button>

                <?php if ( is_user_logged_in() ) : ?>
                    <!-- دکمه کتابخانه من -->
                    <a href="<?php echo esc_url( home_url( '/dashboard/' ) ); ?>" class="fn-btn--icon" aria-label="داشبورد">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                        </svg>
                    </a>

                    <!-- آواتار کاربر -->
                    <div class="fn-user-menu" id="fnUserMenu">
                        <button class="fn-user-menu__trigger" id="fnUserMenuBtn">
                            <?php echo get_avatar( get_current_user_id(), 34, '', '', array( 'class' => 'fn-user-menu__avatar' ) ); ?>
                        </button>
                        <div class="fn-user-menu__dropdown" id="fnUserDropdown">
                            <div class="fn-user-menu__header">
                                <strong><?php echo esc_html( wp_get_current_user()->display_name ); ?></strong>
                                <small><?php echo esc_html( wp_get_current_user()->user_email ); ?></small>
                            </div>
                            <div class="fn-user-menu__divider"></div>
                            <a href="<?php echo esc_url( home_url( '/dashboard/' ) ); ?>" class="fn-user-menu__item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                                داشبورد
                            </a>
                            <a href="<?php echo esc_url( home_url( '/dashboard/?tab=bookmarks' ) ); ?>" class="fn-user-menu__item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                                کتابخانه من
                            </a>
                            <a href="<?php echo esc_url( home_url( '/dashboard/?tab=history' ) ); ?>" class="fn-user-menu__item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                تاریخچه خواندن
                            </a>
                            <a href="<?php echo esc_url( home_url( '/dashboard/?tab=settings' ) ); ?>" class="fn-user-menu__item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                                تنظیمات
                            </a>
                            <div class="fn-user-menu__divider"></div>
                            <a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="fn-user-menu__item fn-user-menu__item--danger">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                خروج
                            </a>
                        </div>
                    </div>
                <?php else : ?>
                    <!-- دکمه ورود/ثبت‌نام -->
                    <a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="fn-btn fn-btn--ghost fn-btn--sm fn-login-btn">
                        ورود
                    </a>
                    <a href="<?php echo esc_url( wp_registration_url() ); ?>" class="fn-btn fn-btn--primary fn-btn--sm fn-register-btn">
                        ثبت‌نام
                    </a>
                <?php endif; ?>

                <!-- دکمه همبرگری (موبایل) -->
                <button class="fn-hamburger" id="fnHamburger" aria-label="باز کردن منو" aria-expanded="false">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </div>
</header>

<!-- پوشش تاریک برای منوی موبایل -->
<div class="fn-overlay" id="fnOverlay"></div>

<!-- جستجوی موبایل (مودال) -->
<div class="fn-mobile-search" id="fnMobileSearch">
    <div class="fn-mobile-search__inner">
        <form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
            <div class="fn-mobile-search__input-wrap">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="search" name="s" placeholder="نام رمان را جستجو کنید..." autocomplete="off" autofocus>
                <input type="hidden" name="post_type" value="novel">
                <button type="button" class="fn-mobile-search__close" id="fnMobileSearchClose">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- شروع محتوای اصلی -->
<main class="fn-main" id="fnMain">