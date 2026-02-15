/**
 * Novel Coins System - Frontend
 * 
 * خرید قسمت VIP + نمایش موجودی
 *
 * @package suspended-flavor
 * @since 3.0.0
 */

(function($) {
    'use strict';

    if (typeof novelCoins === 'undefined') return;

    const CoinsUI = {

        init() {
            this.bindEvents();
            this.updateHeaderBalance();
        },

        bindEvents() {
            // Purchase VIP chapter
            $(document).on('click', '.btn-purchase-chapter', function(e) {
                e.preventDefault();
                const chapterId = $(this).data('chapter-id');
                const price = $(this).data('price');
                CoinsUI.purchaseChapter(chapterId, price, $(this));
            });

            // Show purchase modal
            $(document).on('click', '.btn-unlock-chapter', function(e) {
                e.preventDefault();
                const chapterId = $(this).data('chapter-id');
                CoinsUI.showPurchaseModal(chapterId);
            });
        },

        /**
         * Update header coin balance
         */
        updateHeaderBalance() {
            const $badge = $('.header-coin-balance');
            if (!$badge.length) return;

            $.post(novelCoins.ajaxUrl, {
                action: 'novel_get_balance',
                nonce: novelCoins.nonce
            }, function(res) {
                if (res.success) {
                    $badge.find('.coin-count').text(res.data.balance);
                    if (res.data.expiring > 0) {
                        $badge.find('.coin-expiring').text('⚠️ ' + res.data.expiring).show();
                    }
                }
            });
        },

        /**
         * Show purchase modal for VIP chapter
         */
        showPurchaseModal(chapterId) {
            $.post(novelCoins.ajaxUrl, {
                action: 'novel_check_chapter_access',
                nonce: novelCoins.nonce,
                chapter_id: chapterId
            }, function(res) {
                if (!res.success) return;
                const d = res.data;

                if (d.access) {
                    // Already has access → reload to show content
                    location.reload();
                    return;
                }

                if (!d.logged_in) {
                    NovelToast.show(novelCoins.strings.loginRequired, 'warning');
                    return;
                }

                const canAfford = d.balance >= d.price;

                const html = `
                    <div class="coin-purchase-modal" id="coinPurchaseModal">
                        <div class="coin-modal-overlay" data-close></div>
                        <div class="coin-modal-content glassmorphism">
                            <button class="coin-modal-close" data-close>&times;</button>
                            <div class="coin-modal-icon">🔒</div>
                            <h3>قسمت VIP</h3>
                            <p>این قسمت نیاز به خرید دارد</p>
                            <div class="coin-modal-details">
                                <div class="coin-detail-row">
                                    <span>${novelCoins.strings.price}</span>
                                    <span class="coin-price">🪙 ${d.price} ${novelCoins.strings.coins}</span>
                                </div>
                                <div class="coin-detail-row">
                                    <span>${novelCoins.strings.balance}</span>
                                    <span class="coin-balance ${canAfford ? '' : 'insufficient'}">🪙 ${d.balance} ${novelCoins.strings.coins}</span>
                                </div>
                            </div>
                            ${canAfford 
                                ? `<button class="btn-purchase-chapter btn-primary" data-chapter-id="${chapterId}" data-price="${d.price}">
                                       🪙 خرید با ${d.price} سکه
                                   </button>`
                                : `<div class="coin-insufficient-msg">
                                       <p>${novelCoins.strings.insufficient}</p>
                                       <a href="${location.origin}/plans/" class="btn-outline">🛒 خرید سکه</a>
                                   </div>`
                            }
                        </div>
                    </div>
                `;

                $('body').append(html);
                document.body.style.overflow = 'hidden';

                // Close events
                $(document).on('click', '#coinPurchaseModal [data-close]', function() {
                    CoinsUI.closePurchaseModal();
                });
                $(document).on('keydown', function(e) {
                    if (e.key === 'Escape') CoinsUI.closePurchaseModal();
                });
            });
        },

        closePurchaseModal() {
            const $modal = $('#coinPurchaseModal');
            $modal.addClass('closing');
            setTimeout(() => {
                $modal.remove();
                document.body.style.overflow = '';
            }, 250);
        },

        /**
         * Purchase chapter
         */
        purchaseChapter(chapterId, price, $btn) {
            if (!confirm(novelCoins.strings.confirmPurchase + '\n' + novelCoins.strings.price + ' ' + price + ' ' + novelCoins.strings.coins)) {
                return;
            }

            $btn.prop('disabled', true).text('در حال خرید...');

            $.post(novelCoins.ajaxUrl, {
                action: 'novel_purchase_chapter',
                nonce: novelCoins.nonce,
                chapter_id: chapterId
            }, function(res) {
                if (res.success) {
                    NovelToast.show(res.data.message, 'success');

                    // Close modal
                    CoinsUI.closePurchaseModal();

                    // Update balance
                    $('.header-coin-balance .coin-count').text(res.data.balance);

                    // Reload to show content
                    setTimeout(() => location.reload(), 1000);
                } else {
                    NovelToast.show(res.data?.message || novelCoins.strings.error, 'error');
                    $btn.prop('disabled', false).text('🪙 خرید با ' + price + ' سکه');
                }
            });
        }
    };

    // Toast fallback
    if (typeof window.NovelToast === 'undefined') {
        window.NovelToast = {
            show(msg, type) {
                const colors = { success: '#10b981', error: '#ef4444', warning: '#f59e0b', info: '#3b82f6' };
                const $t = $('<div>' + msg + '</div>').css({
                    position: 'fixed', bottom: '2rem', left: '50%',
                    transform: 'translateX(-50%) translateY(20px)',
                    padding: '0.75rem 1.5rem', borderRadius: '12px',
                    color: '#fff', fontSize: '0.9rem', fontWeight: '500',
                    zIndex: 99999, opacity: 0, background: colors[type] || colors.info,
                    boxShadow: '0 4px 20px rgba(0,0,0,0.2)',
                    fontFamily: "'Vazirmatn', sans-serif",
                    transition: 'all 0.3s ease'
                }).appendTo('body');
                requestAnimationFrame(() => $t.css({ opacity: 1, transform: 'translateX(-50%) translateY(0)' }));
                setTimeout(() => { $t.css({ opacity: 0 }); setTimeout(() => $t.remove(), 300); }, 3500);
            }
        };
    }

    $(document).ready(() => CoinsUI.init());
    window.NovelCoins = CoinsUI;

})(jQuery);