<?php
/**
 * فایل: footer.php
 * توضیح: فوتر قالب ناول - لینک‌ها + آمار + شبکه‌های اجتماعی + Navigation Bar شناور
 * نسخه: 2.0.0
 * وابستگی: main.css, main.js
 */

if (!defined('ABSPATH')) exit;

// آمار سایت (cache شده)
$stats = Novel_Core::get_site_stats();
?>

</main><!-- .novel-main -->

<!-- ═══ Footer ═══ -->
<footer class="novel-footer" role="contentinfo">
    <div class="novel-container">
        <div class="novel-footer-grid">

            <!-- ستون ۱: درباره سایت -->
            <div class="novel-footer-col">
                <div class="novel-footer-about">
                    <?php
                    $logo_url = get_option('novel_site_logo', '');
                    if ($logo_url) :
                    ?>
                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php bloginfo('name'); ?>" width="120" height="36" loading="lazy">
                    <?php else : ?>
                        <strong style="font-size: 1.2rem; color: var(--primary);"><?php bloginfo('name'); ?></strong>
                    <?php endif; ?>
                    <p><?php echo esc_html(get_option('novel_site_description', 'بهترین سایت خواندن رمان آنلاین فارسی')); ?></p>
                </div>

                <!-- شبکه‌های اجتماعی -->
                <div class="novel-footer-social">
                    <?php
                    $socials = [
                        'telegram'  => ['url' => get_option('novel_social_telegram', ''), 'icon' => '<path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>', 'label' => 'تلگرام'],
                        'instagram' => ['url' => get_option('novel_social_instagram', ''), 'icon' => '<rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>', 'label' => 'اینستاگرام'],
                        'twitter'   => ['url' => get_option('novel_social_twitter', ''), 'icon' => '<path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/>', 'label' => 'توییتر'],
                    ];
                    foreach ($socials as $key => $social) :
                        if (empty($social['url'])) continue;
                    ?>
                    <a href="<?php echo esc_url($social['url']); ?>" target="_blank" rel="noopener noreferrer" 
                       aria-label="<?php echo esc_attr($social['label']); ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <?php echo $social['icon']; ?>
                        </svg>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- آمار -->
                <div class="novel-footer-stats">
                    <div class="novel-footer-stat-item">
                        <span>📖</span>
                        <span><?php echo esc_html(novel_format_number($stats['novels'])); ?> رمان</span>
                    </div>
                    <div class="novel-footer-stat-item">
                        <span>📄</span>
                        <span><?php echo esc_html(novel_format_number($stats['chapters'])); ?> قسمت</span>
                    </div>
                    <div class="novel-footer-stat-item">
                        <span>👥</span>
                        <span><?php echo esc_html(novel_format_number($stats['users'])); ?> کاربر</span>
                    </div>
                    <div class="novel-footer-stat-item">
                        <span>💬</span>
                        <span><?php echo esc_html(novel_format_number($stats['comments'])); ?> دیدگاه</span>
                    </div>
                </div>
            </div>

            <!-- ستون ۲: لینک‌های سریع -->
            <div class="novel-footer-col">
                <h4>لینک‌های سریع</h4>
                <ul>
                    <li><a href="<?php echo esc_url(home_url('/')); ?>">صفحه اصلی</a></li>
                    <li><a href="<?php echo esc_url(get_post_type_archive_link('novel')); ?>">رمان‌ها</a></li>
                    <li><a href="<?php echo esc_url(home_url('/ranking/')); ?>">رتبه‌بندی</a></li>
                    <li><a href="<?php echo esc_url(home_url('/authors/')); ?>">نویسندگان</a></li>
                    <li><a href="<?php echo esc_url(home_url('/genres/')); ?>">ژانرها</a></li>
                </ul>
            </div>

            <!-- ستون ۳: راهنما -->
            <div class="novel-footer-col">
                <h4>راهنما</h4>
                <ul>
                    <?php
                    $rules_page = get_option('novel_rules_page', '');
                    $comment_rules = get_option('novel_comment_rules_page', '');
                    if ($rules_page) :
                    ?>
                    <li><a href="<?php echo esc_url(get_permalink($rules_page)); ?>">قوانین سایت</a></li>
                    <?php endif; ?>
                    <?php if ($comment_rules) : ?>
                    <li><a href="<?php echo esc_url(get_permalink($comment_rules)); ?>">قوانین دیدگاه</a></li>
                    <?php endif; ?>
                    <?php
                    // منوی فوتر (اگر ست شده)
                    wp_nav_menu([
                        'theme_location' => 'footer',
                        'container'      => false,
                        'items_wrap'     => '%3$s',
                        'depth'          => 1,
                        'fallback_cb'    => false,
                    ]);
                    ?>
                </ul>
            </div>

            <!-- ستون ۴: منوی فوتر سفارشی -->
            <div class="novel-footer-col">
                <h4>دسترسی سریع</h4>
                <ul>
                    <?php if (is_user_logged_in()) : ?>
                    <li><a href="<?php echo esc_url(home_url('/dashboard/')); ?>">داشبورد من</a></li>
                    <li><a href="<?php echo esc_url(home_url('/library/')); ?>">کتابخانه من</a></li>
                    <?php else : ?>
                    <li><a href="<?php echo esc_url(home_url('/login/')); ?>">ورود</a></li>
                    <li><a href="<?php echo esc_url(home_url('/register/')); ?>">ثبت‌نام</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo esc_url(home_url('/search/')); ?>">جستجوی پیشرفته</a></li>
                </ul>
            </div>

        </div>

        <!-- کپی‌رایت -->
        <div class="novel-footer-bottom">
            <p>
                © <?php echo esc_html(novel_fa_num(date_i18n('Y'))); ?> 
                <?php bloginfo('name'); ?>. 
                تمامی حقوق محفوظ است.
            </p>
            <p style="margin-top: 4px;">
                طراحی با ❤️ برای خوانندگان فارسی‌زبان
            </p>
        </div>
    </div>
</footer>

<!-- ═══ Navigation Bar شناور پایین ═══ -->
<nav class="novel-bottom-nav" id="bottomNav" role="navigation" aria-label="منوی ناوبری">
    <!-- خانه -->
    <a href="<?php echo esc_url(home_url('/')); ?>" class="novel-nav-item" data-page="home">
        <svg class="novel-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
            <polyline points="9 22 9 12 15 12 15 22"></polyline>
        </svg>
        <span>خانه</span>
    </a>

    <!-- جستجو -->
    <a href="<?php echo esc_url(home_url('/search/')); ?>" class="novel-nav-item" data-page="search">
        <svg class="novel-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <span>جستجو</span>
    </a>

    <!-- کتابخانه -->
    <a href="<?php echo esc_url(home_url('/library/')); ?>" class="novel-nav-item" data-page="library" 
       <?php if (!is_user_logged_in()) echo 'data-requires-login'; ?>>
        <svg class="novel-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"></path>
        </svg>
        <span>کتابخانه</span>
    </a>

    <!-- اعلان‌ها -->
    <a href="<?php echo is_user_logged_in() ? esc_url(home_url('/dashboard/?tab=notifications')) : esc_url(home_url('/login/')); ?>" 
       class="novel-nav-item" data-page="notifications"
       <?php if (!is_user_logged_in()) echo 'data-requires-login'; ?>>
        <svg class="novel-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 01-3.46 0"></path>
        </svg>
        <span class="nav-badge" id="navNotifCount" data-count="<?php echo is_user_logged_in() ? (int) ($unread ?? 0) : 0; ?>">
            <?php 
            if (is_user_logged_in() && isset($unread) && $unread > 0) {
                echo esc_html(novel_fa_num($unread > 99 ? '99+' : $unread));
            }
            ?>
        </span>
        <span>اعلان‌ها</span>
    </a>

    <!-- پروفایل -->
    <a href="<?php echo is_user_logged_in() ? esc_url(home_url('/dashboard/')) : esc_url(home_url('/login/')); ?>" 
       class="novel-nav-item" data-page="profile">
        <?php if (is_user_logged_in()) : ?>
            <img src="<?php echo esc_url(novel_get_avatar_url(get_current_user_id())); ?>" 
                 alt="پروفایل" class="novel-nav-avatar" width="26" height="26" loading="lazy"
                 data-fallback="<?php echo esc_url(NOVEL_ASSETS . 'avatars/avatar-1.png'); ?>">
        <?php else : ?>
            <svg class="novel-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
        <?php endif; ?>
        <span>پروفایل</span>
    </a>
</nav>

<!-- Toast Container -->
<div class="novel-toast-container" aria-live="polite" aria-atomic="false"></div>

<?php wp_footer(); ?>
</body>
</html>