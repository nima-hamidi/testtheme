<?php
/**
 * Quiz List Template
 * @var array $result
 */
if (!defined('ABSPATH')) exit;
$quiz_instance = Novel_Quiz::get_instance();
$user_id = get_current_user_id();
?>
<div class="novel-quiz-list-page">
    <div class="novel-page-header" style="text-align: center;">
        <h1 class="novel-page-title"><span class="novel-page-icon">🏆</span> مسابقات</h1>
        <p class="novel-page-subtitle">دانشت رو نشون بده و جایزه بگیر!</p>
    </div>

    <?php if (empty($result['quizzes'])): ?>
        <div class="novel-empty-state">
            <div class="novel-empty-icon">🏆</div>
            <h3>مسابقه‌ای یافت نشد</h3>
        </div>
    <?php else: ?>
        <div class="novel-quiz-list-grid">
            <?php foreach ($result['quizzes'] as $q):
                $has_done = $user_id ? $quiz_instance->has_attempted($q->id, $user_id) : false;
                ?>
                <div class="novel-quiz-list-card">
                    <div class="novel-quiz-list-card__header">
                        <h3>🏆 <?php echo esc_html($q->title); ?></h3>
                        <span class="novel-quiz-list-card__status novel-quiz-list-card__status--<?php echo $q->effective_status; ?>">
                            <?php echo ['active'=>'🟢 فعال','closed'=>'🔴 پایان','upcoming'=>'⏳ آینده'][$q->effective_status] ?? ''; ?>
                        </span>
                    </div>
                    <div class="novel-quiz-list-card__meta">
                        <?php if ($q->novel_id): ?><span>📖 <?php echo esc_html(get_the_title($q->novel_id)); ?></span><?php endif; ?>
                        <span>❓ <?php echo $q->total_questions; ?> سوال</span>
                        <span>👥 <?php echo number_format_i18n($q->participant_count); ?></span>
                        <span>🪙 <?php echo $q->reward_coins_1st; ?>+<?php echo $q->reward_coins_2nd; ?>+<?php echo $q->reward_coins_3rd; ?></span>
                    </div>
                    <div class="novel-quiz-list-card__actions">
                        <?php if ($q->effective_status === 'active' && !$has_done): ?>
                            <a href="<?php echo esc_url(add_query_arg('quiz_id', $q->id, home_url('/quiz/'))); ?>" class="novel-btn novel-btn--primary novel-btn--sm">🚀 شرکت</a>
                        <?php elseif ($has_done): ?>
                            <span style="color: var(--color-success); font-weight: 600;">✅ شرکت کرده‌اید</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>