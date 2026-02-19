<?php
/**
 * Dashboard - My Achievements
 * 
 * @package suspended developer
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

$user_id = get_current_user_id();
$ach_instance = Novel_Achievements::get_instance();

$all_achievements = $ach_instance->get_all_with_user_status($user_id);
$categories = $ach_instance->get_categories();
$total_earned = $ach_instance->get_user_achievement_count($user_id);
$total_points = $ach_instance->get_user_total_points($user_id);
$total_all = count($all_achievements);

// دسته‌بندی
$grouped = [];
foreach ($all_achievements as $a) {
    $grouped[$a->category][] = $a;
}

$active_cat = sanitize_text_field($_GET['ach_cat'] ?? 'all');
?>

<div class="novel-dashboard-achievements">

    <!-- هدر -->
    <div class="novel-ach-header">
        <div class="novel-ach-header__stats">
            <div class="novel-ach-stat">
                <span class="novel-ach-stat__number"><?php echo $total_earned; ?></span>
                <span class="novel-ach-stat__label">دستاورد از <?php echo $total_all; ?></span>
            </div>
            <div class="novel-ach-stat">
                <span class="novel-ach-stat__number"><?php echo number_format_i18n($total_points); ?></span>
                <span class="novel-ach-stat__label">امتیاز کل</span>
            </div>
            <div class="novel-ach-stat">
                <span class="novel-ach-stat__number"><?php echo $total_all > 0 ? round(($total_earned / $total_all) * 100) : 0; ?>٪</span>
                <span class="novel-ach-stat__label">تکمیل</span>
            </div>
        </div>

        <!-- Progress bar کلی -->
        <div class="novel-ach-progress-total">
            <div class="novel-ach-progress-total__bar"
                 style="width: <?php echo $total_all > 0 ? round(($total_earned / $total_all) * 100) : 0; ?>%;"></div>
        </div>
    </div>

    <!-- تب دسته‌بندی -->
    <div class="novel-ach-tabs">
        <a href="?tab=achievements&ach_cat=all"
           class="novel-ach-tab <?php echo $active_cat === 'all' ? 'is-active' : ''; ?>">
            🏆 همه
        </a>
        <?php foreach ($categories as $key => $cat): ?>
            <a href="?tab=achievements&ach_cat=<?php echo $key; ?>"
               class="novel-ach-tab <?php echo $active_cat === $key ? 'is-active' : ''; ?>">
                <?php echo $cat['label']; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- لیست دستاوردها -->
    <div class="novel-ach-grid">
        <?php
        $display_achievements = $active_cat === 'all' ? $all_achievements : ($grouped[$active_cat] ?? []);

        foreach ($display_achievements as $a):
            $is_earned = (bool)$a->is_earned;
            $progress = null;
            if (!$is_earned) {
                $progress = $ach_instance->get_progress($user_id, $a);
            }
            ?>
            <div class="novel-ach-card <?php echo $is_earned ? 'is-earned' : 'is-locked'; ?>">
                <div class="novel-ach-card__icon">
                    <?php if ($is_earned): ?>
                        <?php echo $a->icon; ?>
                    <?php else: ?>
                        <span class="novel-ach-card__lock">🔒</span>
                    <?php endif; ?>
                </div>
                <div class="novel-ach-card__info">
                    <h4 class="novel-ach-card__title"><?php echo esc_html($a->title); ?></h4>
                    <p class="novel-ach-card__desc"><?php echo esc_html($a->description); ?></p>

                    <?php if ($is_earned): ?>
                        <span class="novel-ach-card__date">
                            ✅ <?php echo esc_html(mysql2date('j F Y', $a->earned_at)); ?>
                        </span>
                    <?php elseif ($progress): ?>
                        <div class="novel-ach-card__progress">
                            <div class="novel-ach-card__progress-bar">
                                <div class="novel-ach-card__progress-fill"
                                     style="width: <?php echo $progress['percentage']; ?>%;"></div>
                            </div>
                            <span class="novel-ach-card__progress-text">
                                <?php echo number_format_i18n($progress['current']); ?> / <?php echo number_format_i18n($progress['target']); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="novel-ach-card__points">
                    +<?php echo $a->points; ?> 🏅
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- چالش‌های فعال -->
    <?php if (class_exists('Novel_Challenges')):
        $ch_instance = Novel_Challenges::get_instance();
        $active_challenges = $ch_instance->get_challenges('active', 5);
        if (!empty($active_challenges)):
            ?>
            <div class="novel-ach-challenges">
                <h3 class="novel-ach-challenges__title">📚 چالش‌های فعال</h3>
                <div class="novel-ach-challenges__list">
                    <?php foreach ($active_challenges as $challenge):
                        $joined = $ch_instance->has_joined($challenge->id, $user_id);
                        $user_progress = $joined ? $ch_instance->get_user_progress($challenge->id, $user_id) : null;
                        $pct = $user_progress ? min(100, round(($user_progress->progress / $challenge->target_value) * 100)) : 0;

                        $remaining_days = max(0, floor((strtotime($challenge->end_date) - current_time('timestamp')) / 86400));
                        ?>
                        <div class="novel-challenge-card <?php echo $joined ? 'is-joined' : ''; ?>">
                            <div class="novel-challenge-card__icon"><?php echo $challenge->icon; ?></div>
                            <div class="novel-challenge-card__body">
                                <h4 class="novel-challenge-card__title"><?php echo esc_html($challenge->title); ?></h4>
                                <?php if ($challenge->description): ?>
                                    <p class="novel-challenge-card__desc"><?php echo esc_html(wp_trim_words($challenge->description, 15)); ?></p>
                                <?php endif; ?>

                                <?php if ($joined && $user_progress): ?>
                                    <div class="novel-challenge-card__progress">
                                        <div class="novel-challenge-card__progress-bar">
                                            <div class="novel-challenge-card__progress-fill"
                                                 style="width: <?php echo $pct; ?>%;"></div>
                                        </div>
                                        <span class="novel-challenge-card__progress-text">
                                            <?php echo number_format_i18n($user_progress->progress); ?> از <?php echo number_format_i18n($challenge->target_value); ?>
                                            (<?php echo $pct; ?>٪)
                                        </span>
                                    </div>
                                    <?php if ($user_progress->completed_at): ?>
                                        <span class="novel-challenge-card__completed">🎉 تکمیل شده!</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="novel-btn novel-btn--primary novel-btn--sm novel-join-challenge"
                                            data-challenge-id="<?php echo $challenge->id; ?>">
                                        🚀 شرکت می‌کنم
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div class="novel-challenge-card__meta">
                                <span>⏳ <?php echo $remaining_days; ?> روز مانده</span>
                                <span>👥 <?php echo number_format_i18n($challenge->participant_count); ?> نفر</span>
                                <?php if ($challenge->reward_coins > 0): ?>
                                    <span>🪙 <?php echo number_format_i18n($challenge->reward_coins); ?> سکه</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; endif; ?>
</div>