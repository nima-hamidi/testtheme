<?php
/**
 * Template Name: درباره ما
 * About Us Page
 * 
 * @package suspended developer
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

get_header();

// آمار سایت (cached)
$stats_cache = get_transient('novel_site_stats');
if (!$stats_cache) {
    global $wpdb;
    $stats_cache = [
        'novels'   => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='novel' AND post_status='publish'"),
        'chapters' => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='chapter' AND post_status='publish'"),
        'users'    => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}"),
        'comments' => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved='1'"),
    ];
    set_transient('novel_site_stats', $stats_cache, 6 * HOUR_IN_SECONDS);
}
?>

<main class="novel-main">
    <div class="novel-container">

        <!-- هدر -->
        <div class="novel-about-hero">
            <h1 class="novel-about-hero__title">درباره ما 📖</h1>
            <p class="novel-about-hero__subtitle">
                ما عاشق داستان‌هاییم و اینجا ساختیم تا شما هم لذت ببرید!
            </p>
        </div>

        <!-- معرفی -->
        <section class="novel-about-section">
            <div class="novel-about-content">
                <?php the_content(); ?>
            </div>
        </section>

        <!-- آمار -->
        <section class="novel-about-stats" id="aboutStats">
            <h2 class="novel-about-stats__title">📊 آمار سایت</h2>
            <div class="novel-about-stats__grid">
                <div class="novel-about-stat" data-target="<?php echo $stats_cache['novels']; ?>">
                    <span class="novel-about-stat__icon">📚</span>
                    <span class="novel-about-stat__number" data-count="0">0</span>
                    <span class="novel-about-stat__label">رمان</span>
                </div>
                <div class="novel-about-stat" data-target="<?php echo $stats_cache['chapters']; ?>">
                    <span class="novel-about-stat__icon">📖</span>
                    <span class="novel-about-stat__number" data-count="0">0</span>
                    <span class="novel-about-stat__label">قسمت</span>
                </div>
                <div class="novel-about-stat" data-target="<?php echo $stats_cache['users']; ?>">
                    <span class="novel-about-stat__icon">👥</span>
                    <span class="novel-about-stat__number" data-count="0">0</span>
                    <span class="novel-about-stat__label">کاربر</span>
                </div>
                <div class="novel-about-stat" data-target="<?php echo $stats_cache['comments']; ?>">
                    <span class="novel-about-stat__icon">💬</span>
                    <span class="novel-about-stat__number" data-count="0">0</span>
                    <span class="novel-about-stat__label">دیدگاه</span>
                </div>
            </div>
        </section>

    </div>
</main>

<script>
// Counter Up Animation
(function() {
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.querySelectorAll('.novel-about-stat').forEach(stat => {
                    const target = parseInt(stat.dataset.target) || 0;
                    const el = stat.querySelector('.novel-about-stat__number');
                    animateCount(el, 0, target, 2000);
                });
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.3 });

    const statsEl = document.getElementById('aboutStats');
    if (statsEl) observer.observe(statsEl);

    function animateCount(el, start, end, duration) {
        const range = end - start;
        const startTime = performance.now();
        function update(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            const current = Math.floor(start + range * eased);
            el.textContent = new Intl.NumberFormat('fa-IR').format(current);
            if (progress < 1) requestAnimationFrame(update);
        }
        requestAnimationFrame(update);
    }
})();
</script>

<?php get_footer(); ?>