<?php
/**
 * 404 Error Page (Enhanced)
 * 
 * @package suspended developer
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

get_header();

// رمان‌های محبوب
$popular_novels = get_posts([
    'post_type'      => 'novel',
    'posts_per_page' => 3,
    'post_status'    => 'publish',
    'meta_key'       => '_novel_views',
    'orderby'        => 'meta_value_num',
    'order'          => 'DESC',
]);
?>

<main class="novel-main">
    <div class="novel-container">
        <div class="novel-error-page">

            <!-- انیمیشن SVG -->
            <div class="novel-error-page__illustration">
                <div class="novel-error-dragon">
                    <span class="novel-error-dragon__emoji">🐉</span>
                    <span class="novel-error-dragon__map">🗺️</span>
                </div>
            </div>

            <h1 class="novel-error-page__code">۴۰۴</h1>

            <h2 class="novel-error-page__title">
                اوه! اینجا سرزمین ناشناخته‌هاست! 🐉
            </h2>

            <p class="novel-error-page__desc">
                صفحه‌ای که دنبالش بودید در هیچ دنیای داستانی وجود نداره!
                <br>شاید آدرس رو اشتباه وارد کردی یا صفحه حذف شده.
            </p>

            <div class="novel-error-page__actions">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="novel-btn novel-btn--primary novel-btn--lg">
                    🏠 صفحه اصلی
                </a>
                <a href="<?php echo esc_url(home_url('/search/')); ?>" class="novel-btn novel-btn--outline novel-btn--lg">
                    🔍 جستجو
                </a>
                <a href="<?php echo esc_url(home_url('/novels/')); ?>" class="novel-btn novel-btn--outline novel-btn--lg">
                    📚 رمان‌ها
                </a>
            </div>

            <?php if (!empty($popular_novels)): ?>
                <div class="novel-error-page__suggestions">
                    <h3>📚 شاید اینا بهت بخوره:</h3>
                    <div class="novel-error-page__novels-grid">
                        <?php foreach ($popular_novels as $novel): ?>
                            <a href="<?php echo get_permalink($novel->ID); ?>" class="novel-error-novel-card">
                                <?php if (has_post_thumbnail($novel->ID)): ?>
                                    <?php echo get_the_post_thumbnail($novel->ID, 'thumbnail', ['class' => 'novel-error-novel-card__img']); ?>
                                <?php endif; ?>
                                <span class="novel-error-novel-card__title"><?php echo esc_html($novel->post_title); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</main>

<?php get_footer(); ?>