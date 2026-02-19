<?php
/**
 * Poll Single Template
 * نمایش یک نظرسنجی کامل (فرم رأی + نتایج)
 * 
 * @var object $poll
 * @var bool $has_voted
 * @var array $user_votes
 * @var array|null $remaining
 * @var bool $show_results
 */

if (!defined('ABSPATH')) exit;

$poll_id = $poll->id;
$is_closed = $poll->effective_status === 'closed' || ($remaining && $remaining['expired']);
$is_upcoming = $poll->effective_status === 'upcoming';

// محاسبه درصدها
$total_option_votes = 0;
$max_votes = 0;
foreach ($poll->options as $opt) {
    $total_option_votes += (int)$opt->vote_count;
    $max_votes = max($max_votes, (int)$opt->vote_count);
}
?>

<div class="novel-poll" id="novel-poll-<?php echo $poll_id; ?>" 
     data-poll-id="<?php echo $poll_id; ?>"
     data-poll-type="<?php echo esc_attr($poll->poll_type); ?>"
     <?php if ($remaining && !$remaining['expired']): ?>
     data-end-timestamp="<?php echo esc_attr($remaining['timestamp']); ?>"
     <?php endif; ?>>
    
    <!-- هدر نظرسنجی -->
    <div class="novel-poll__header">
        <div class="novel-poll__icon">📊</div>
        <h3 class="novel-poll__title"><?php echo esc_html($poll->title); ?></h3>
        <?php if ($poll->description): ?>
            <p class="novel-poll__desc"><?php echo wp_kses_post($poll->description); ?></p>
        <?php endif; ?>
    </div>

    <!-- محتوای نظرسنجی -->
    <div class="novel-poll__body">
        
        <?php if ($is_upcoming): ?>
            <!-- نظرسنجی هنوز شروع نشده -->
            <div class="novel-poll__upcoming">
                <div class="novel-poll__upcoming-icon">⏳</div>
                <p>این نظرسنجی هنوز شروع نشده است.</p>
                <?php if ($poll->start_date): ?>
                    <p class="novel-poll__start-date">شروع: <?php echo esc_html(mysql2date('j F Y - H:i', $poll->start_date)); ?></p>
                <?php endif; ?>
            </div>

        <?php elseif ($show_results): ?>
            <!-- نتایج -->
            <div class="novel-poll__results">
                <?php foreach ($poll->options as $opt):
                    $count = (int)$opt->vote_count;
                    $pct = $total_option_votes > 0 ? round(($count / $total_option_votes) * 100, 1) : 0;
                    $is_user_choice = in_array($opt->id, $user_votes);
                    $is_winner = $count === $max_votes && $max_votes > 0;
                    ?>
                    <div class="novel-poll__result-item <?php echo $is_user_choice ? 'is-user-choice' : ''; ?> <?php echo $is_winner ? 'is-winner' : ''; ?>">
                        <div class="novel-poll__result-header">
                            <span class="novel-poll__result-text">
                                <?php if ($is_user_choice): ?><span class="novel-poll__check">✓</span><?php endif; ?>
                                <?php echo esc_html($opt->option_text); ?>
                            </span>
                            <span class="novel-poll__result-pct"><?php echo $pct; ?>٪</span>
                        </div>
                        <div class="novel-poll__result-bar-wrap">
                            <div class="novel-poll__result-bar" 
                                 data-percentage="<?php echo $pct; ?>"
                                 style="width: 0%;">
                            </div>
                        </div>
                        <div class="novel-poll__result-count">
                            <?php echo number_format_i18n($count); ?> رأی
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <!-- فرم رأی‌دهی -->
            <form class="novel-poll__form" data-poll-id="<?php echo $poll_id; ?>">
                <div class="novel-poll__options">
                    <?php foreach ($poll->options as $opt): ?>
                        <label class="novel-poll__option">
                            <input type="<?php echo $poll->poll_type === 'single' ? 'radio' : 'checkbox'; ?>"
                                   name="poll_option_<?php echo $poll_id; ?>"
                                   value="<?php echo $opt->id; ?>"
                                   class="novel-poll__input">
                            <span class="novel-poll__option-indicator"></span>
                            <span class="novel-poll__option-text"><?php echo esc_html($opt->option_text); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <?php if (is_user_logged_in()): ?>
                    <button type="submit" class="novel-poll__submit">
                        <span class="novel-poll__submit-icon">🗳</span>
                        <span class="novel-poll__submit-text">ثبت رأی</span>
                    </button>
                <?php else: ?>
                    <div class="novel-poll__login-notice">
                        <a href="<?php echo esc_url(home_url('/login/')); ?>">وارد شوید</a> تا رأی دهید.
                    </div>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>

    <!-- فوتر نظرسنجی -->
    <div class="novel-poll__footer">
        <span class="novel-poll__total-votes">
            <?php echo number_format_i18n($poll->total_votes); ?> نفر رأی داده‌اند
        </span>

        <?php if ($remaining && !$remaining['expired']): ?>
            <span class="novel-poll__countdown" data-end="<?php echo esc_attr($remaining['timestamp']); ?>">
                ⏳ <span class="countdown-text">
                    <?php
                    $parts = [];
                    if ($remaining['days'] > 0) $parts[] = $remaining['days'] . ' روز';
                    if ($remaining['hours'] > 0) $parts[] = $remaining['hours'] . ' ساعت';
                    if (empty($parts) && $remaining['minutes'] > 0) $parts[] = $remaining['minutes'] . ' دقیقه';
                    echo implode(' ', $parts) . ' مانده';
                    ?>
                </span>
            </span>
        <?php elseif ($is_closed): ?>
            <span class="novel-poll__status-closed">🔴 بسته شده</span>
        <?php endif; ?>

        <?php if ($has_voted && !$is_closed): ?>
            <span class="novel-poll__voted-badge">✓ رأی شما ثبت شد</span>
        <?php endif; ?>
    </div>

    <!-- بخش دیدگاه (اختیاری) -->
    <?php if (!isset($compact) || !$compact): ?>
        <div class="novel-poll__comments">
            <?php
            // نمایش دیدگاه‌ها با سیستم موجود
            // اگر class-novel-comments موجود باشد
            if (class_exists('Novel_Comments')) {
                echo '<div class="novel-poll__comments-section">';
                echo '<h4 class="novel-poll__comments-title">💬 دیدگاه‌ها درباره این نظرسنجی</h4>';
                // می‌توان از comment_post_ID = poll_X یا custom implementation استفاده کرد
                echo '</div>';
            }
            ?>
        </div>
    <?php endif; ?>
</div>