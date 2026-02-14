<?php
/**
 * فوتر قالب فلیور نوول
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>

</main><!-- پایان محتوای اصلی -->

<!-- ===== فوتر سایت ===== -->
<footer class="fn-footer" id="fnFooter">
    <div class="fn-container">
        <div class="fn-footer__grid">

            <!-- ستون اول: درباره سایت -->
            <div class="fn-footer__col">
                <div class="fn-footer__brand">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="fn-header__logo" style="margin-bottom: 14px;">
                        <span class="fn-header__logo-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                            </svg>
                        </span>
                        <span><?php bloginfo( 'name' ); ?></span>
                    </a>
                </div>
                <p class="fn-footer__desc">
                    <?php
                    $description = get_bloginfo( 'description' );
                    if ( $description ) {
                        echo esc_html( $description );
                    } else {
                        echo 'پلتفرم خواندن رمان و داستان آنلاین فارسی. هزاران فصل از بهترین رمان‌های جهان با ترجمه فارسی.';
                    }
                    ?>
                </p>
                <!-- شبکه‌های اجتماعی -->
                <div class="fn-footer__social">
                    <?php
                    $social_links = array(
                        'telegram'  => get_theme_mod( 'fn_telegram', '' ),
                        'instagram' => get_theme_mod( 'fn_instagram', '' ),
                        'twitter'   => get_theme_mod( 'fn_twitter', '' ),
                        'discord'   => get_theme_mod( 'fn_discord', '' ),
                    );

                    foreach ( $social_links as $platform => $url ) :
                        if ( ! empty( $url ) ) :
                    ?>
                        <a href="<?php echo esc_url( $url ); ?>" class="fn-footer__social-link" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $platform ); ?>">
                            <?php echo fn_get_social_icon( $platform ); ?>
                        </a>
                    <?php
                        endif;
                    endforeach;
                    ?>
                </div>
            </div>

            <!-- ستون دوم: دسترسی سریع -->
            <div class="fn-footer__col">
                <h4 class="fn-footer__title">دسترسی سریع</h4>
                <div class="fn-footer__links">
                    <a href="<?php echo esc_url( get_post_type_archive_link( 'novel' ) ); ?>" class="fn-footer__link">
                        📚 کتابخانه رمان‌ها
                    </a>
                    <a href="<?php echo esc_url( home_url( '/genre/fantasy/' ) ); ?>" class="fn-footer__link">
                        🧙 رمان‌های فانتزی
                    </a>
                    <a href="<?php echo esc_url( home_url( '/genre/action/' ) ); ?>" class="fn-footer__link">
                        ⚔️ رمان‌های اکشن
                    </a>
                    <a href="<?php echo esc_url( home_url( '/genre/romance/' ) ); ?>" class="fn-footer__link">
                        ❤️ رمان‌های عاشقانه
                    </a>
                    <a href="<?php echo esc_url( home_url( '/status/completed/' ) ); ?>" class="fn-footer__link">
                        ✅ رمان‌های تمام‌شده
                    </a>
                </div>
            </div>

            <!-- ستون سوم: لینک‌های مفید -->
            <div class="fn-footer__col">
                <h4 class="fn-footer__title">لینک‌های مفید</h4>
                <div class="fn-footer__links">
                    <?php
                    if ( has_nav_menu( 'footer' ) ) {
                        wp_nav_menu( array(
                            'theme_location' => 'footer',
                            'container'      => false,
                            'items_wrap'     => '%3$s',
                            'walker'         => new FN_Footer_Nav_Walker(),
                            'fallback_cb'    => false,
                        ) );
                    } else {
                    ?>
                        <a href="<?php echo esc_url( home_url( '/about/' ) ); ?>" class="fn-footer__link">درباره ما</a>
                        <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="fn-footer__link">تماس با ما</a>
                        <a href="<?php echo esc_url( home_url( '/faq/' ) ); ?>" class="fn-footer__link">سوالات متداول</a>
                        <a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>" class="fn-footer__link">حریم خصوصی</a>
                        <a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>" class="fn-footer__link">قوانین و مقررات</a>
                    <?php } ?>
                </div>
            </div>

            <!-- ستون چهارم: آمار -->
            <div class="fn-footer__col">
                <h4 class="fn-footer__title">آمار سایت</h4>
                <div class="fn-footer__stats">
                    <?php
                    // تعداد رمان‌ها
                    $novel_count = wp_count_posts( 'novel' );
                    $total_novels = isset( $novel_count->publish ) ? $novel_count->publish : 0;

                    // تعداد فصل‌ها
                    $chapter_count = wp_count_posts( 'chapter' );
                    $total_chapters = isset( $chapter_count->publish ) ? $chapter_count->publish : 0;

                    // تعداد کاربران
                    $total_users = count_users();
                    ?>
                    <div class="fn-footer__stat-item">
                        <span class="fn-footer__stat-icon">📖</span>
                        <div>
                            <strong><?php echo fn_format_number( $total_novels ); ?></strong>
                            <small>رمان</small>
                        </div>
                    </div>
                    <div class="fn-footer__stat-item">
                        <span class="fn-footer__stat-icon">📄</span>
                        <div>
                            <strong><?php echo fn_format_number( $total_chapters ); ?></strong>
                            <small>فصل</small>
                        </div>
                    </div>
                    <div class="fn-footer__stat-item">
                        <span class="fn-footer__stat-icon">👥</span>
                        <div>
                            <strong><?php echo fn_format_number( $total_users['total_users'] ); ?></strong>
                            <small>کاربر</small>
                        </div>
                    </div>
                    <div class="fn-footer__stat-item">
                        <span class="fn-footer__stat-icon">🏷️</span>
                        <div>
                            <strong><?php echo wp_count_terms( 'genre' ); ?></strong>
                            <small>ژانر</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- پایین فوتر -->
        <div class="fn-footer__bottom">
            <p>
                © <?php echo date( 'Y' ); ?>
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
                — تمامی حقوق محفوظ است.
                ساخته شده با ❤️ برای علاقه‌مندان به رمان
            </p>
        </div>
    </div>
</footer>

<!-- دکمه بازگشت به بالا -->
<button class="fn-back-to-top" id="fnBackToTop" aria-label="بازگشت به بالا">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="18 15 12 9 6 15"/>
    </svg>
</button>

<!-- Toast Container -->
<div class="fn-toast-container" id="fnToastContainer"></div>

<?php wp_footer(); ?>
</body>
</html>