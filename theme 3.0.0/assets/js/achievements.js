/**
 * Novel Achievements JavaScript
 * Popup دستاورد جدید + confetti + شرکت در چالش
 * 
 * @package suspended developer
 * @since 3.0.0
 */

(function($) {
    'use strict';

    const AchievementsApp = {

        init() {
            this.checkPendingAchievements();
            this.bindChallengeJoin();
        },

        // ═══════════════════════════════════════
        // بررسی مدال‌های جدید (popup)
        // ═══════════════════════════════════════

        checkPendingAchievements() {
            if (typeof NovelAchievements === 'undefined') return;

            // بررسی فقط ۱ بار در هر session
            if (sessionStorage.getItem('novel_ach_checked')) return;
            sessionStorage.setItem('novel_ach_checked', '1');

            $.ajax({
                url: NovelAchievements.ajaxurl,
                method: 'POST',
                data: {
                    action: 'novel_get_pending_achievements',
                    nonce: NovelAchievements.nonce,
                },
                success: (response) => {
                    if (response.success && response.data.achievements.length > 0) {
                        this.showPopupQueue(response.data.achievements);
                    }
                }
            });
        },

        showPopupQueue(achievements) {
            let index = 0;

            const showNext = () => {
                if (index >= achievements.length) return;
                this.showPopup(achievements[index], () => {
                    index++;
                    setTimeout(showNext, 300);
                });
            };

            showNext();
        },

        showPopup(achievement, onClose) {
            const overlay = document.createElement('div');
            overlay.className = 'novel-ach-popup-overlay';

            overlay.innerHTML = `
                <div class="novel-ach-popup">
                    <div class="novel-ach-popup__confetti" id="achConfetti"></div>
                    <div class="novel-ach-popup__label">🎉 تبریک!</div>
                    <div class="novel-ach-popup__icon">${this.escapeHtml(achievement.icon)}</div>
                    <h3 class="novel-ach-popup__title">دستاورد جدید کسب کردید:</h3>
                    <h2 class="novel-ach-popup__title" style="color: var(--ach-gold);">
                        «${this.escapeHtml(achievement.title)}»
                    </h2>
                    <p class="novel-ach-popup__desc">${this.escapeHtml(achievement.description)}</p>
                    <p style="font-size: 14px; color: var(--ach-gold); font-weight: 700;">+${achievement.points} امتیاز 🏅</p>
                    <button class="novel-ach-popup__btn" id="achPopupClose">عالیه! ✓</button>
                </div>
            `;

            document.body.appendChild(overlay);
            document.body.style.overflow = 'hidden';

            // Confetti
            this.createConfetti(overlay.querySelector('#achConfetti'));

            // Dismiss
            const dismiss = () => {
                overlay.style.opacity = '0';
                overlay.style.transition = 'opacity 0.3s ease';
                document.body.style.overflow = '';

                // Mark as notified
                $.post(NovelAchievements.ajaxurl, {
                    action: 'novel_dismiss_achievement',
                    nonce: NovelAchievements.nonce,
                    achievement_id: achievement.id,
                });

                setTimeout(() => {
                    overlay.remove();
                    if (onClose) onClose();
                }, 300);
            };

            overlay.querySelector('#achPopupClose').addEventListener('click', dismiss);
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) dismiss();
            });

            // Auto-close 8 ثانیه
            setTimeout(dismiss, 8000);
        },

        createConfetti(container) {
            if (!container) return;

            const colors = ['#f59e0b', '#6366f1', '#ef4444', '#10b981', '#ec4899', '#3b82f6'];
            const confettiCount = 30;

            for (let i = 0; i < confettiCount; i++) {
                const piece = document.createElement('div');
                const color = colors[Math.floor(Math.random() * colors.length)];
                const left = Math.random() * 100;
                const delay = Math.random() * 2;
                const duration = 2 + Math.random() * 2;
                const size = 6 + Math.random() * 8;
                const rotation = Math.random() * 360;

                piece.style.cssText = `
                    position: absolute;
                    top: -20px;
                    left: ${left}%;
                    width: ${size}px;
                    height: ${size}px;
                    background: ${color};
                    border-radius: ${Math.random() > 0.5 ? '50%' : '2px'};
                    transform: rotate(${rotation}deg);
                    animation: achConfettiFall ${duration}s ${delay}s ease-in forwards;
                    opacity: 0.8;
                `;

                container.appendChild(piece);
            }

            // CSS animation (inject once)
            if (!document.getElementById('achConfettiStyle')) {
                const style = document.createElement('style');
                style.id = 'achConfettiStyle';
                style.textContent = `
                    @keyframes achConfettiFall {
                        0% { transform: translateY(0) rotate(0deg); opacity: 1; }
                        100% { transform: translateY(500px) rotate(720deg); opacity: 0; }
                    }
                `;
                document.head.appendChild(style);
            }
        },

        // ═══════════════════════════════════════
        // شرکت در چالش
        // ═══════════════════════════════════════

        bindChallengeJoin() {
            $(document).on('click', '.novel-join-challenge', (e) => {
                e.preventDefault();
                const $btn = $(e.currentTarget);
                const challengeId = $btn.data('challenge-id');

                if (!challengeId) return;

                $btn.prop('disabled', true).text('در حال ثبت...');

                $.ajax({
                    url: NovelAchievements.ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'novel_join_challenge',
                        nonce: NovelAchievements.nonce,
                        challenge_id: challengeId,
                    },
                    success: (response) => {
                        if (response.success) {
                            this.showToast(response.data.message, 'success');
                            $btn.replaceWith('<span style="color: var(--ach-success); font-weight: 700;">✅ شرکت کردید!</span>');
                        } else {
                            this.showToast(response.data.message || 'خطا', 'error');
                            $btn.prop('disabled', false).text('🚀 شرکت می‌کنم');
                        }
                    },
                    error: () => {
                        this.showToast('خطا در اتصال', 'error');
                        $btn.prop('disabled', false).text('🚀 شرکت می‌کنم');
                    }
                });
            });
        },

        // ═══════════════════════════════════════
        // Utils
        // ═══════════════════════════════════════

        showToast(message, type) {
            if (typeof NovelApp !== 'undefined' && NovelApp.showToast) {
                NovelApp.showToast(message, type);
                return;
            }
            // Fallback
            const colors = { success: '#10b981', error: '#ef4444', warning: '#f59e0b' };
            const toast = document.createElement('div');
            toast.textContent = message;
            toast.style.cssText = `
                position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%);
                padding: 12px 24px; border-radius: 10px; color: #fff; font-size: 14px;
                z-index: 99999; background: ${colors[type] || colors.success};
                box-shadow: 0 4px 20px rgba(0,0,0,0.15); font-family: inherit;
            `;
            document.body.appendChild(toast);
            setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity 0.3s'; setTimeout(() => toast.remove(), 300); }, 3000);
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    $(document).ready(() => AchievementsApp.init());

})(jQuery);