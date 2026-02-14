<?php
/**
 * سایدبار صفحه تک رمان
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$novel_id = get_the_ID();
?>

<!-- ناوبری سریع -->
<div class="fn-sidebar-widget fn-sidebar-nav">
    <h3 class="fn-sidebar-widget__title">ناوبری سریع</h3>
    <nav class="fn-sidebar-nav__links">
        <a href="#synopsis" class="fn-sidebar-nav__link active">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
            خلاصه داستان
        </a>
        <a href="#info" class="fn-sidebar-nav__link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/></svg>
            اطلاعات
        </a>
        <a href="#chapters" class="fn-sidebar-nav__link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/></svg>
            فصل‌ها
        </a>
        <a href="#comments-section" class="fn-sidebar-nav__link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            نظرات
        </a>
    </nav>
</div>

<!-- اطلاع‌رسانی آخرین فصل -->
<?php
$latest = fn_get_latest_chapter( $novel_id );
if ( $latest ) :
    $latest_num = get_post_meta( $latest->ID, '_fn_chapter_number', true );
?>
<div class="fn-sidebar-widget fn-sidebar-latest">
    <h3 class="fn-sidebar-widget__title">📢 آخرین فصل</h3>
    <a href="<?php echo esc_url( get_permalink( $latest->ID ) ); ?>" class="fn-sidebar-latest__link">
        <div class="fn-sidebar-latest__number">فصل <?php echo esc_html( $latest_num ); ?></div>
        <div class="fn-sidebar-latest__title"><?php echo esc_html( $latest->post_title ); ?></div>
        <div class="fn-sidebar-latest__date"><?php echo fn_time_ago( get_post_time( 'U', false, $latest->ID ) ); ?></div>
    </a>
</div>
<?php endif; ?>

<!-- اشتراک‌گذاری -->
<div class="fn-sidebar-widget fn-sidebar-share">
    <h3 class="fn-sidebar-widget__title">📤 اشتراک‌گذاری</h3>
    <div class="fn-share-buttons">
        <?php
        $share_url   = urlencode( get_permalink() );
        $share_title = urlencode( get_the_title() );
        ?>
        <a href="https://t.me/share/url?url=<?php echo $share_url; ?>&text=<?php echo $share_title; ?>"
           target="_blank" rel="noopener" class="fn-share-btn fn-share-btn--telegram" title="تلگرام">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
        </a>
        <a href="https://twitter.com/intent/tweet?url=<?php echo $share_url; ?>&text=<?php echo $share_title; ?>"
           target="_blank" rel="noopener" class="fn-share-btn fn-share-btn--twitter" title="توییتر">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
        </a>
        <a href="https://wa.me/?text=<?php echo $share_title . '%20' . $share_url; ?>"
           target="_blank" rel="noopener" class="fn-share-btn fn-share-btn--whatsapp" title="واتساپ">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492l4.634-1.215A11.95 11.95 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.75c-2.115 0-4.107-.636-5.764-1.834l-.413-.276-4.285 1.124 1.143-4.177-.303-.432A9.72 9.72 0 0 1 .75 12C.75 6.21 5.46 1.5 12 1.5S23.25 6.21 23.25 12 18.54 21.75 12 21.75z"/></svg>
        </a>
        <button class="fn-share-btn fn-share-btn--copy" title="کپی لینک" onclick="navigator.clipboard.writeText('<?php echo esc_url( get_permalink() ); ?>'); FN.toast('لینک کپی شد!', 'success');">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
        </button>
    </div>
</div>

<!-- ویجت‌های سایدبار وردپرس -->
<?php if ( is_active_sidebar( 'novel-sidebar' ) ) : ?>
    <?php dynamic_sidebar( 'novel-sidebar' ); ?>
<?php endif; ?>