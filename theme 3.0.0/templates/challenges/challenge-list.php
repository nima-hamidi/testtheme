<?php
/**
 * Challenge List Template
 * شورتکد: [novel_challenges]
 * 
 * @var array $challenges
 */

if (!defined('ABSPATH')) exit;

$ch_instance = Novel_Challenges::get_instance();
$user_id = get_current_user_id();
?>

<div class="novel-challenges-page">
    <div class="novel-container">
        <div class="novel-page-header" style="text-align: center;">
            <h1 class="novel-page-title"><span class="novel-page-icon">📚</span> چالش‌های مطالعه</h1>
            <p class="novel-page-subtitle">چالش بپذیر، بخون و جایزه بگیر!</p>
        </div>

        <?php if (empty($challenges)): ?>
            <div class="novel-empty-state">
                <div class="novel-empty-icon">📚</div>
                <h3>چالش فعالی وجود ندارد</h3>
                <p>به‌زودی چالش‌های جدید اضافه می‌شوند.</p>
            </div>
        <?php else: ?>
            <div class="novel-challenges-grid">
                <?php foreach ($challenges as $challenge):
                    $joined = $user_id ? $ch_instance->has_joined($challenge->id, $user_id) : false;
                    $user_progress = $joined ? $ch_instance->get_user_progress($challenge->id, $user_id) : null;
                    $pct = $user_progress ? min(100, round(($user_progress->progress / $challenge->target_value) * 100)) : 0;
                    $remaining_days = max(0, floor((strtotime($challenge->end_date) - current_time('timestamp')) / 86400));
                    $is_completed = $user_progress && $user_progress->completed_at;

                    $type_labels = [
                        'chapter_count'  => '📖 قسمت بخوان',
                        'novel_count'    => '📚 رمان بخوان',
                        'genre_specific' => '🎭 ژانر خاص',
                        'diverse'        => '🌈 تنوع ژانر',
                    ];
                    ?>
                    <div class="novel-challenge-card-full <?php echo $is_completed ? 'is-completed' : ''; ?>">
                        <div class="novel-challenge-card-full__header">
                            <span class="novel-challenge-card-full__icon"><?php echo $challenge->icon; ?></span>
                            <div>
                                <h3 class="novel-challenge-card-full__title"><?php echo esc_html($challenge->title); ?></h3>
                                <span class="novel-challenge-card-full__type"><?php echo $type_labels[$challenge->challenge_type] ?? '📋'; ?></span>
                            </div>
                            <span class="novel-challenge-card-full__status novel-challenge-card-full__status--<?php echo $challenge->effective_status; ?>">
                                <?php
                                $status_texts = ['active' => '🟢 فعال', 'upcoming' => '⏳ آینده', 'ended' => '🔴 پایان‌یافته'];
                                echo $status_texts[$challenge->effective_status] ?? '';
                                ?>
                            </span>
                        </div>

                        <?php if ($challenge->description): ?>
                            <p class="novel-challenge-card-full__desc"><?php echo wp_kses_post($challenge->description); ?></p>
                        <?php endif; ?>

                        <div class="novel-challenge-card-full__target">
                            🎯 هدف: <?php echo number_format_i18n($challenge->target_value); ?>
                            <?php
                            $target_labels = ['chapter_count' => 'قسمت', 'novel_count' => 'رمان', 'genre_specific' => 'قسمت', 'diverse' => 'ژانر'];
                            echo $target_labels[$challenge->challenge_type] ?? '';
                            ?>
                        </div>

                        <?php if ($joined && $user_progress): ?>
                            <div class="novel-challenge-card-full__progress">
                                <div class="novel-challenge-card-full__progress-bar">
                                    <div class="novel-challenge-card-full__progress-fill" style="width: <?php echo $pct; ?>%;"></div>
                                </div>
                                <div class="novel-challenge-card-full__progress-info">
                                    <span><?php echo number_format_i18n($user_progress->progress); ?> / <?php echo number_format_i18n($challenge->target_value); ?></span>
                                    <span><?php echo $pct; ?>٪</span>
                                </div>
                            </div>
                            <?php if ($is_completed): ?>
                                <div class="novel-challenge-card-full__completed">🎉 تبریک! این چالش را تکمیل کردید!</div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <div class="novel-challenge-card-full__footer">
                            <div class="novel-challenge-card-full__meta">
                                <span>⏳ <?php echo $remaining_days; ?> روز مانده</span>
                                <span>👥 <?php echo number_format_i18n($challenge->participant_count); ?> شرکت‌کننده</span>
                                <?php if ($challenge->reward_coins > 0): ?>
                                    <span>🪙 جایزه: <?php echo number_format_i18n($challenge->reward_coins); ?> سکه</span>
                                <?php endif; ?>
                            </div>

                            <?php if ($challenge->effective_status === 'active' && !$joined && $user_id): ?>
                                <button class="novel-btn novel-btn--primary novel-join-challenge"
                                        data-challenge-id="<?php echo $challenge->id; ?>">
                                    🚀 شرکت می‌کنم!
                                </button>
                            <?php elseif (!$user_id): ?>
                                <a href="<?php echo esc_url(home_url('/login/')); ?>" class="novel-btn novel-btn--outline">
                                    وارد شوید
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>