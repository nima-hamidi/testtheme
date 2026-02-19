/**
 * Novel Polls JavaScript
 * رأی‌گیری AJAX، شمارش معکوس، انیمیشن نوار درصد
 *
 * @package suspended developer
 * @since 3.0.0
 */

(function($) {
    'use strict';

    const NovelPollsApp = {

        init() {
            this.bindVoteSubmit();
            this.initCountdowns();
            this.animateVisibleBars();
            this.initIntersectionObserver();
        },

        // ═══════════════════════════════════════
        // Vote Submission
        // ═══════════════════════════════════════

        bindVoteSubmit() {
            $(document).on('submit', '.novel-poll__form', (e) => {
                e.preventDefault();
                this.handleVote($(e.currentTarget));
            });
        },

        handleVote($form) {
            const pollId = $form.data('poll-id') || $form.closest('.novel-poll').data('poll-id');

            if (!NovelPolls.is_logged) {
                this.showToast(NovelPolls.strings.login_required, 'warning');
                return;
            }

            // جمع‌آوری گزینه‌های انتخاب‌شده
            const $inputs = $form.find('.novel-poll__input:checked');
            if ($inputs.length === 0) {
                this.showToast(NovelPolls.strings.select_option, 'warning');
                return;
            }

            const optionIds = $inputs.map(function() {
                return $(this).val();
            }).get();

            const $btn = $form.find('.novel-poll__submit');
            $btn.prop('disabled', true).addClass('is-loading');
            $btn.find('.novel-poll__submit-text').text('در حال ثبت');

            $.ajax({
                url: NovelPolls.ajaxurl,
                method: 'POST',
                data: {
                    action: 'novel_vote_poll',
                    nonce: NovelPolls.nonce,
                    poll_id: pollId,
                    option_ids: optionIds,
                },
                success: (response) => {
                    if (response.success) {
                        this.showToast(response.data.message, 'success');
                        this.showResults($form, response.data.results);
                    } else {
                        this.showToast(response.data.message || NovelPolls.strings.error, 'error');
                        $btn.prop('disabled', false).removeClass('is-loading');
                        $btn.find('.novel-poll__submit-text').text('ثبت رأی');
                    }
                },
                error: () => {
                    this.showToast(NovelPolls.strings.error, 'error');
                    $btn.prop('disabled', false).removeClass('is-loading');
                    $btn.find('.novel-poll__submit-text').text('ثبت رأی');
                }
            });
        },

        // ═══════════════════════════════════════
        // Show Results After Vote
        // ═══════════════════════════════════════

        showResults($form, results) {
            const $poll = $form.closest('.novel-poll');
            const $body = $poll.find('.novel-poll__body');

            // Fade out form
            $form.addClass('is-voted');

            setTimeout(() => {
                // Build results HTML
                let html = '<div class="novel-poll__results is-entering">';

                // Find max votes
                let maxVotes = 0;
                results.options.forEach(opt => {
                    if (opt.count > maxVotes) maxVotes = opt.count;
                });

                results.options.forEach(opt => {
                    const isUser = opt.is_user ? 'is-user-choice' : '';
                    const isWinner = (opt.count === maxVotes && maxVotes > 0) ? 'is-winner' : '';
                    const check = opt.is_user ? '<span class="novel-poll__check">✓</span>' : '';

                    html += `
                        <div class="novel-poll__result-item ${isUser} ${isWinner}">
                            <div class="novel-poll__result-header">
                                <span class="novel-poll__result-text">
                                    ${check}
                                    ${this.escapeHtml(opt.text)}
                                </span>
                                <span class="novel-poll__result-pct">${opt.percentage}٪</span>
                            </div>
                            <div class="novel-poll__result-bar-wrap">
                                <div class="novel-poll__result-bar" 
                                     data-percentage="${opt.percentage}"
                                     style="width: 0%;">
                                </div>
                            </div>
                            <div class="novel-poll__result-count">
                                ${this.formatNumber(opt.count)} رأی
                            </div>
                        </div>
                    `;
                });

                html += '</div>';

                $body.html(html);

                // Animate bars
                setTimeout(() => {
                    this.animateBars($body);
                }, 100);

                // Update footer
                const $footer = $poll.find('.novel-poll__footer');
                $footer.find('.novel-poll__total-votes').html(
                    `${this.formatNumber(results.total_voters)} نفر رأی داده‌اند`
                );

                // Add voted badge
                if (!$footer.find('.novel-poll__voted-badge').length) {
                    $footer.append('<span class="novel-poll__voted-badge">✓ رأی شما ثبت شد</span>');
                }

            }, 350);
        },

        // ═══════════════════════════════════════
        // Bar Animation
        // ═══════════════════════════════════════

        animateBars($container) {
            $container.find('.novel-poll__result-bar').each(function(i) {
                const $bar = $(this);
                const pct = $bar.data('percentage');

                setTimeout(() => {
                    $bar.addClass('is-animating').css('width', pct + '%');
                }, i * 120);
            });
        },

        animateVisibleBars() {
            // Animate bars that are already in view on page load
            $('.novel-poll__results').each((_, el) => {
                this.animateBars($(el));
            });
        },

        // ═══════════════════════════════════════
        // Intersection Observer for lazy animation
        // ═══════════════════════════════════════

        initIntersectionObserver() {
            if (!('IntersectionObserver' in window)) {
                return;
            }

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const $results = $(entry.target).find('.novel-poll__results');
                        if ($results.length && !$results.data('animated')) {
                            this.animateBars($results);
                            $results.data('animated', true);
                        }
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.3 });

            document.querySelectorAll('.novel-poll').forEach(poll => {
                observer.observe(poll);
            });
        },

        // ═══════════════════════════════════════
        // Countdown Timer
        // ═══════════════════════════════════════

        initCountdowns() {
            const $countdowns = $('.novel-poll__countdown[data-end]');
            if ($countdowns.length === 0) return;

            this.updateCountdowns($countdowns);
            setInterval(() => this.updateCountdowns($countdowns), 60000); // هر دقیقه
        },

        updateCountdowns($countdowns) {
            const now = Math.floor(Date.now() / 1000);

            $countdowns.each(function() {
                const $el = $(this);
                const endTimestamp = parseInt($el.data('end'), 10);
                const diff = endTimestamp - now;

                if (diff <= 0) {
                    $el.find('.countdown-text').text('پایان یافته');
                    $el.closest('.novel-poll').find('.novel-poll__submit').prop('disabled', true);
                    return;
                }

                const days = Math.floor(diff / 86400);
                const hours = Math.floor((diff % 86400) / 3600);
                const minutes = Math.floor((diff % 3600) / 60);

                let text = '⏳ ';
                const parts = [];
                if (days > 0) parts.push(days + ' ' + NovelPolls.strings.day);
                if (hours > 0) parts.push(hours + ' ' + NovelPolls.strings.hour);
                if (parts.length === 0 && minutes > 0) parts.push(minutes + ' ' + NovelPolls.strings.minute);

                text = parts.join(' ') + ' ' + NovelPolls.strings.remaining;

                $el.find('.countdown-text').text(text);
            });
        },

        // ═══════════════════════════════════════
        // Utilities
        // ═══════════════════════════════════════

        showToast(message, type) {
            // استفاده از toast سیستم اصلی (main.js)
            if (typeof NovelApp !== 'undefined' && NovelApp.showToast) {
                NovelApp.showToast(message, type);
                return;
            }

            // Fallback ساده
            const toast = document.createElement('div');
            toast.className = `novel-toast novel-toast--${type}`;
            toast.textContent = message;
            toast.style.cssText = `
                position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%);
                padding: 12px 24px; border-radius: 10px; color: #fff; font-size: 14px;
                z-index: 99999; font-family: inherit; animation: pollToastIn 0.3s ease;
                box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            `;

            const colors = { success: '#10b981', error: '#ef4444', warning: '#f59e0b', info: '#3b82f6' };
            toast.style.background = colors[type] || colors.info;

            document.body.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        formatNumber(num) {
            return new Intl.NumberFormat('fa-IR').format(num);
        }
    };

    // Initialize
    $(document).ready(() => NovelPollsApp.init());

})(jQuery);