<?php
/**
 * Quiz Lobby Template
 * @var object $quiz
 */
if (!defined('ABSPATH')) exit;

$quiz_instance = Novel_Quiz::get_instance();
$user_id = get_current_user_id();
$has_attempted = $user_id ? $quiz_instance->has_attempted($quiz->id, $user_id) : false;
$leaderboard = $quiz_instance->get_leaderboard_data($quiz->id, 10);
$user_rank = ($user_id && $has_attempted) ? $quiz_instance->get_user_rank($quiz->id, $user_id) : null;

$remaining = null;
if ($quiz->end_time) {
    $end_ts = strtotime($quiz->end_time);
    $diff = $end_ts - current_time('timestamp');
    if ($diff > 0) {
        $remaining = [
            'hours' => floor($diff / 3600),
            'minutes' => floor(($diff % 3600) / 60),
        ];
    }
}

$rank_icons = ['1' => '🥇', '2' => '🥈', '3' => '🥉'];
?>

<div class="novel-quiz-lobby" id="quizLobby" data-quiz-id="<?php echo $quiz->id; ?>">
    <div class="novel-quiz-lobby__header">
        <h2 class="novel-quiz-lobby__title">🏆 <?php echo esc_html($quiz->title); ?></h2>
        <?php if ($quiz->description): ?>
            <p class="novel-quiz-lobby__desc"><?php echo wp_kses_post($quiz->description); ?></p>
        <?php endif; ?>
    </div>

    <div class="novel-quiz-lobby__info">
        <?php if ($quiz->novel_id): ?>
            <span>📖 <?php echo esc_html(get_the_title($quiz->novel_id)); ?>
                <?php if ($quiz->chapter_range_start): ?>
                    (قسمت <?php echo $quiz->chapter_range_start; ?>-<?php echo $quiz->chapter_range_end; ?>)
                <?php endif; ?>
            </span>
        <?php endif; ?>
        <span>❓ <?php echo $quiz->total_questions; ?> سوال</span>
        <span>⏱ <?php echo $quiz->time_per_question; ?> ثانیه</span>
        <span>🎯 <?php echo esc_html(['easy'=>'آسان','medium'=>'متوسط','hard'=>'سخت','mixed'=>'ترکیبی'][$quiz->difficulty] ?? ''); ?></span>
        <?php if ($remaining): ?>
            <span>⏳ <?php echo $remaining['hours']; ?>h <?php echo $remaining['minutes']; ?>m مانده</span>
        <?php endif; ?>
    </div>

    <div class="novel-quiz-lobby__rewards">
        <span>🥇 <?php echo $quiz->reward_coins_1st; ?> سکه</span>
        <span>🥈 <?php echo $quiz->reward_coins_2nd; ?> سکه</span>
        <span>🥉 <?php echo $quiz->reward_coins_3rd; ?> سکه</span>
        <span>🎖 <?php echo $quiz->reward_coins_participation; ?> سکه</span>
    </div>

    <div class="novel-quiz-lobby__stats">
        👥 <?php echo number_format_i18n($quiz->participant_count); ?> نفر شرکت کرده‌اند
    </div>

    <?php if ($quiz->effective_status === 'active'): ?>
        <?php if (!$user_id): ?>
            <a href="<?php echo esc_url(home_url('/login/')); ?>" class="novel-btn novel-btn--primary novel-btn--lg">
                وارد شوید
            </a>
        <?php elseif ($has_attempted && !$quiz->is_repeatable): ?>
            <div class="novel-quiz-lobby__already">
                ✅ شما قبلاً شرکت کرده‌اید<?php if ($user_rank): ?> — رتبه: <?php echo $user_rank; ?><?php endif; ?>
            </div>
        <?php else: ?>
            <button class="novel-btn novel-btn--primary novel-btn--lg novel-quiz-start-btn"
                    data-quiz-id="<?php echo $quiz->id; ?>">
                🚀 شروع مسابقه!
            </button>
        <?php endif; ?>
    <?php elseif ($quiz->effective_status === 'upcoming'): ?>
        <div class="novel-quiz-lobby__upcoming">⏳ مسابقه هنوز شروع نشده</div>
    <?php else: ?>
        <div class="novel-quiz-lobby__closed">🔴 مسابقه پایان یافته</div>
    <?php endif; ?>

    <!-- لیدربورد -->
    <?php if (!empty($leaderboard)): ?>
        <div class="novel-quiz-lobby__leaderboard">
            <h3>📊 رتبه‌بندی</h3>
            <div class="novel-quiz-lb-list">
                <?php foreach ($leaderboard as $entry):
                    $is_me = ($user_id && $entry->user_id == $user_id);
                    $icon = $rank_icons[$entry->ranking] ?? '🎖';
                    $mins = floor($entry->time_spent / 60);
                    $secs = $entry->time_spent % 60;
                    ?>
                    <div class="novel-quiz-lb-item <?php echo $is_me ? 'is-me' : ''; ?>">
                        <span class="novel-quiz-lb-rank"><?php echo $icon; ?> <?php echo $entry->ranking; ?></span>
                        <span class="novel-quiz-lb-name"><?php echo $is_me ? '⭐ ' : ''; ?><?php echo esc_html($entry->display_name); ?></span>
                        <span class="novel-quiz-lb-score"><?php echo $entry->score; ?> امتیاز</span>
                        <span class="novel-quiz-lb-time">⏱ <?php echo $mins; ?>:<?php echo str_pad($secs, 2, '0', STR_PAD_LEFT); ?></span>
                        <span class="novel-quiz-lb-coins">🪙 <?php echo $entry->coins_earned; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Play area (hidden, shown by JS) -->
    <div class="novel-quiz-play" id="quizPlayArea" style="display: none;"></div>

    <!-- Result area (hidden) -->
    <div class="novel-quiz-result" id="quizResultArea" style="display: none;"></div>
</div>