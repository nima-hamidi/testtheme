<?php
/**
 * Quiz Leaderboard Template
 * @var array $leaderboard
 */
if (!defined('ABSPATH')) exit;
$user_id = get_current_user_id();
$rank_icons = ['1' => '🥇', '2' => '🥈', '3' => '🥉'];
?>
<div class="novel-quiz-leaderboard-page">
    <h2 class="novel-section-title"><span class="novel-section-icon">🏆</span> لیدربورد</h2>
    <?php if (empty($leaderboard)): ?>
        <p style="text-align: center; color: var(--color-text-secondary);">هنوز کسی شرکت نکرده.</p>
    <?php else: ?>
        <div class="novel-quiz-lb-list novel-quiz-lb-list--full">
            <?php foreach ($leaderboard as $entry):
                $is_me = ($user_id && $entry->user_id == $user_id);
                $icon = $rank_icons[$entry->ranking] ?? $entry->ranking;
                $mins = floor($entry->time_spent / 60);
                $secs = $entry->time_spent % 60;
                ?>
                <div class="novel-quiz-lb-item <?php echo $is_me ? 'is-me' : ''; ?>">
                    <span class="novel-quiz-lb-rank"><?php echo $icon; ?></span>
                    <span class="novel-quiz-lb-name"><?php echo $is_me ? '⭐ ' : ''; ?><?php echo esc_html($entry->display_name); ?></span>
                    <span class="novel-quiz-lb-score"><?php echo number_format_i18n($entry->score); ?> امتیاز</span>
                    <span class="novel-quiz-lb-time">⏱ <?php echo $mins; ?>:<?php echo str_pad($secs, 2, '0', STR_PAD_LEFT); ?></span>
                    <span class="novel-quiz-lb-coins">🪙 <?php echo $entry->coins_earned; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>