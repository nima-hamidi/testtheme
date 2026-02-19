<?php
/**
 * Poll Widget Template (Compact)
 * نمایش فشرده نظرسنجی برای سایدبار و صفحه اصلی
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

$total_option_votes = 0;
$max_votes = 0;
foreach ($poll->options as $opt) {
    $total_option_votes += (int)$opt->vote_count;
    $max_votes = max($max_votes, (int)$opt->vote_count);
}
?>

<div class="novel-poll novel-poll--compact" id="novel-poll-<?php echo $poll_id; ?>"
     data-poll-id="<?php echo $poll_id; ?>"
     data-poll-type="<?php echo esc_attr($poll->poll_type); ?>"
     <?php if ($remaining && !$remaining['expired']): ?>
     data-end-timestamp="<?php echo esc_attr($remaining['timestamp']); ?>"
     <?php endif; ?>>

    <div class="novel-poll__header">
        <span class="novel-poll__icon">📊</span>
        <h4 class="novel-poll__title"><?php echo esc_html($poll->title); ?></h4>
    </div>

    <div class="novel-poll__body">
        <?php if ($show_results): ?>
            <!-- نتایج فشرده -->
            <div class="novel-poll__results novel-poll__results--compact">
                <?php foreach ($poll->options as $opt):
                    $count = (int)$opt->vote_count;
                    $pct = $total_option_votes > 0 ? round(($count / $total_option_votes) * 100, 1) : 0;
                    $is_user_choice = in_array($opt->id, $user_votes);
                    ?>
                    <div class="novel-poll__result-item <?php echo $is_user_choice ? 'is-user-choice' : ''; ?>">
                        <div class="novel-poll__result-header">
                            <span class="novel-poll__result-text">
                                <?php if ($is_user_choice): ?>✓ <?php endif; ?>
                                <?php echo esc_html(wp_trim_words($opt->option_text, 8)); ?>
                            </span>
                            <span class="novel-poll__result-pct"><?php echo $pct; ?>٪</span>
                        </div>
                        <div class="novel-poll__result-bar-wrap">
                            <div class="novel-poll__result-bar" data-percentage="<?php echo $pct; ?>" style="width: 0%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- فرم فشرده -->
            <form class="novel-poll__form novel-poll__form--compact" data-poll-id="<?php echo $poll_id; ?>">
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
                    <button type="submit" class="novel-poll__submit novel-poll__submit--compact">
                        🗳 ثبت رأی
                    </button>
                <?php else: ?>
                    <a href="<?php echo esc_url(home_url('/login/')); ?>" class="novel-poll__login-link">
                        برای رأی وارد شوید
                    </a>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>

    <div class="novel-poll__footer novel-poll__footer--compact">
        <span class="novel-poll__total-votes">
            <?php echo number_format_i18n($poll->total_votes); ?> رأی
        </span>
        <?php if ($remaining && !$remaining['expired']): ?>
            <span class="novel-poll__countdown" data-end="<?php echo esc_attr($remaining['timestamp']); ?>">
                ⏳ <?php echo $remaining['days']; ?>d <?php echo $remaining['hours']; ?>h
            </span>
        <?php endif; ?>
    </div>
</div>