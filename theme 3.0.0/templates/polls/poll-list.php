<?php
/**
 * Poll List Template
 * لیست تمام نظرسنجی‌ها
 * 
 * @var array $result (polls, total, pages, current)
 */

if (!defined('ABSPATH')) exit;

$polls_instance = Novel_Polls::get_instance();
?>

<div class="novel-polls-page">
    <div class="novel-container">
        
        <div class="novel-page-header">
            <h1 class="novel-page-title">
                <span class="novel-page-icon">📊</span>
                نظرسنجی‌ها
            </h1>
            <p class="novel-page-subtitle">نظرتو بگو و نتیجه رو ببین!</p>
        </div>

        <!-- تب‌ها -->
        <div class="novel-polls-tabs">
            <?php
            $current_status = sanitize_text_field($_GET['poll_status'] ?? 'all');
            $tabs = [
                'all'    => '📋 همه',
                'active' => '🟢 فعال',
                'closed' => '🔴 پایان‌یافته',
            ];
            foreach ($tabs as $key => $label):
                $active = $current_status === $key ? 'is-active' : '';
                $url = add_query_arg('poll_status', $key);
                ?>
                <a href="<?php echo esc_url($url); ?>" class="novel-polls-tab <?php echo $active; ?>">
                    <?php echo $label; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- لیست نظرسنجی‌ها -->
        <?php if (empty($result['polls'])): ?>
            <div class="novel-empty-state">
                <div class="novel-empty-icon">📊</div>
                <h3>نظرسنجی‌ای یافت نشد</h3>
                <p>هنوز نظرسنجی‌ای ایجاد نشده است.</p>
            </div>
        <?php else: ?>
            <div class="novel-polls-list">
                <?php foreach ($result['polls'] as $poll):
                    $has_voted = is_user_logged_in() ? $polls_instance->has_user_voted($poll->id) : false;
                    $remaining = $polls_instance->get_remaining_time($poll);
                    $is_closed = $poll->effective_status === 'closed' || ($remaining && $remaining['expired']);
                    ?>
                    <div class="novel-poll-card <?php echo $is_closed ? 'is-closed' : ''; ?>">
                        <div class="novel-poll-card__header">
                            <h3 class="novel-poll-card__title">
                                <?php echo esc_html($poll->title); ?>
                            </h3>
                            <span class="novel-poll-card__status novel-poll-card__status--<?php echo esc_attr($poll->effective_status); ?>">
                                <?php
                                $status_texts = [
                                    'active'   => '🟢 فعال',
                                    'closed'   => '🔴 بسته',
                                    'draft'    => '📝 پیش‌نویس',
                                    'upcoming' => '⏳ آینده',
                                ];
                                echo $status_texts[$poll->effective_status] ?? $poll->effective_status;
                                ?>
                            </span>
                        </div>

                        <?php if ($poll->description): ?>
                            <p class="novel-poll-card__desc"><?php echo wp_kses_post(wp_trim_words($poll->description, 20)); ?></p>
                        <?php endif; ?>

                        <div class="novel-poll-card__meta">
                            <span class="novel-poll-card__meta-item">
                                🗳 <?php echo number_format_i18n($poll->total_votes); ?> رأی
                            </span>
                            <span class="novel-poll-card__meta-item">
                                📝 <?php echo count($poll->options); ?> گزینه
                            </span>
                            <span class="novel-poll-card__meta-item">
                                <?php echo $poll->poll_type === 'single' ? '◉ تک‌انتخابی' : '☑ چندانتخابی'; ?>
                            </span>
                            <?php if ($remaining && !$remaining['expired']): ?>
                                <span class="novel-poll-card__meta-item novel-poll-card__meta-countdown">
                                    ⏳ <?php echo $remaining['days']; ?> روز مانده
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="novel-poll-card__actions">
                            <?php if (!$is_closed && !$has_voted): ?>
                                <a href="<?php echo esc_url(add_query_arg('poll_id', $poll->id, home_url('/poll/'))); ?>"
                                   class="novel-btn novel-btn--primary novel-btn--sm">
                                    🗳 شرکت در رأی‌گیری
                                </a>
                            <?php elseif ($has_voted): ?>
                                <a href="<?php echo esc_url(add_query_arg('poll_id', $poll->id, home_url('/poll/'))); ?>"
                                   class="novel-btn novel-btn--outline novel-btn--sm">
                                    📊 مشاهده نتایج
                                </a>
                                <span class="novel-poll-card__voted">✓ رأی داده‌اید</span>
                            <?php else: ?>
                                <a href="<?php echo esc_url(add_query_arg('poll_id', $poll->id, home_url('/poll/'))); ?>"
                                   class="novel-btn novel-btn--outline novel-btn--sm">
                                    📊 مشاهده نتایج
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- صفحه‌بندی -->
            <?php if ($result['pages'] > 1): ?>
                <div class="novel-pagination">
                    <?php
                    echo paginate_links([
                        'base'    => add_query_arg('poll_page', '%#%'),
                        'format'  => '',
                        'current' => $result['current'],
                        'total'   => $result['pages'],
                        'prev_text' => '← قبلی',
                        'next_text' => 'بعدی →',
                    ]);
                    ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>