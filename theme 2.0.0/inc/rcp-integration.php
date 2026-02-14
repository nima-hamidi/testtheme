<?php
/**
 * سازگاری با Restrict Content Pro
 * سیستم فصل‌های VIP / پولی
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * بررسی فعال بودن RCP
 */
function fn_is_rcp_active() {
    return function_exists( 'rcp_user_has_active_membership' );
}

/**
 * بررسی دسترسی کاربر به فصل
 */
function fn_user_can_read_chapter( $chapter_id = null ) {
    if ( ! $chapter_id ) $chapter_id = get_the_ID();

    // اگر RCP فعال نیست، همه دسترسی دارند
    if ( ! fn_is_rcp_active() ) return true;

    // اگر فصل رایگان است
    $is_vip = get_post_meta( $chapter_id, '_fn_is_vip', true );
    if ( $is_vip !== '1' ) return true;

    // اگر ادمین است
    if ( current_user_can( 'manage_options' ) ) return true;

    // اگر لاگین نیست
    if ( ! is_user_logged_in() ) return false;

    // بررسی عضویت فعال RCP
    return rcp_user_has_active_membership( get_current_user_id() );
}

/**
 * دریافت سطح عضویت مورد نیاز
 */
function fn_get_required_membership( $chapter_id = null ) {
    if ( ! $chapter_id ) $chapter_id = get_the_ID();
    $level = get_post_meta( $chapter_id, '_fn_vip_level', true );
    return $level ?: 'any'; // هر عضویتی
}

/**
 * اضافه کردن فیلد VIP به متاباکس فصل
 */
function fn_add_vip_meta_to_chapter( $post ) {
    if ( ! fn_is_rcp_active() ) return;

    $is_vip   = get_post_meta( $post->ID, '_fn_is_vip', true );
    $vip_level = get_post_meta( $post->ID, '_fn_vip_level', true );

    // دریافت سطوح عضویت RCP
    $levels = array();
    if ( function_exists( 'rcp_get_membership_levels' ) ) {
        $levels = rcp_get_membership_levels( array( 'status' => 'active' ) );
    }
    ?>
    <div class="fn-side-row" style="margin-top: 16px; padding-top: 14px; border-top: 1px dashed #ddd;">
        <label style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
            <input type="checkbox" name="fn_is_vip" value="1" <?php checked( $is_vip, '1' ); ?>>
            <strong>🔒 فصل VIP (نیاز به عضویت)</strong>
        </label>

        <?php if ( ! empty( $levels ) ) : ?>
            <label style="display:block;font-size:12px;font-weight:700;margin-bottom:4px;">سطح عضویت مورد نیاز:</label>
            <select name="fn_vip_level" style="width:100%;padding:6px 10px;border:1px solid #ddd;border-radius:4px;">
                <option value="any" <?php selected( $vip_level, 'any' ); ?>>هر عضویت فعالی</option>
                <?php foreach ( $levels as $level ) : ?>
                    <option value="<?php echo esc_attr( $level->id ); ?>"
                            <?php selected( $vip_level, $level->id ); ?>>
                        <?php echo esc_html( $level->name ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
    </div>
    <?php
}
add_action( 'fn_chapter_meta_box_after', 'fn_add_vip_meta_to_chapter' );

/**
 * ذخیره فیلدهای VIP
 */
function fn_save_vip_meta( $post_id ) {
    if ( get_post_type( $post_id ) !== 'chapter' ) return;

    update_post_meta( $post_id, '_fn_is_vip', isset( $_POST['fn_is_vip'] ) ? '1' : '0' );

    if ( isset( $_POST['fn_vip_level'] ) ) {
        update_post_meta( $post_id, '_fn_vip_level', sanitize_text_field( $_POST['fn_vip_level'] ) );
    }
}
add_action( 'save_post_chapter', 'fn_save_vip_meta', 25 );

/**
 * فیلتر محتوای فصل VIP
 */
function fn_filter_vip_chapter_content( $content ) {
    if ( ! is_singular( 'chapter' ) ) return $content;
    if ( fn_user_can_read_chapter() ) return $content;

    // محتوای جایگزین برای کاربران بدون دسترسی
    $novel_id = get_post_meta( get_the_ID(), '_fn_parent_novel', true );

    ob_start();
    ?>
    <div class="fn-vip-lock">
        <div class="fn-vip-lock__icon">🔒</div>
        <h3 class="fn-vip-lock__title">فصل ویژه (VIP)</h3>
        <p class="fn-vip-lock__desc">
            برای خواندن این فصل نیاز به عضویت ویژه دارید.
        </p>

        <!-- نمایش پیش‌نمایش (۲۰۰ کلمه اول) -->
        <div class="fn-vip-lock__preview">
            <?php echo wp_trim_words( $content, 200, '...' ); ?>
            <div class="fn-vip-lock__fade"></div>
        </div>

        <div class="fn-vip-lock__actions">
            <?php if ( ! is_user_logged_in() ) : ?>
                <a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="fn-btn fn-btn--primary fn-btn--lg">
                    ورود به حساب کاربری
                </a>
                <a href="<?php echo esc_url( wp_registration_url() ); ?>" class="fn-btn fn-btn--ghost">
                    ثبت‌نام
                </a>
            <?php else : ?>
                <?php if ( function_exists( 'rcp_get_registration_page_url' ) ) : ?>
                    <a href="<?php echo esc_url( rcp_get_registration_page_url() ); ?>" class="fn-btn fn-btn--primary fn-btn--lg">
                        💎 خرید اشتراک VIP
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ( $novel_id ) : ?>
                <a href="<?php echo esc_url( get_permalink( $novel_id ) ); ?>" class="fn-btn fn-btn--ghost">
                    بازگشت به صفحه رمان
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_filter( 'the_content', 'fn_filter_vip_chapter_content', 999 );

/**
 * بج VIP در لیست فصل‌ها
 */
function fn_chapter_vip_badge( $chapter_id ) {
    $is_vip = get_post_meta( $chapter_id, '_fn_is_vip', true );
    if ( $is_vip === '1' ) {
        echo '<span class="fn-vip-badge">VIP</span>';
    }
}
