<?php
/**
 * Dashboard - My Coins
 *
 * @package suspended-flavor
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;
if (!is_user_logged_in()) return;

$user_id = get_current_user_id();
$balance = class_exists('Novel_Coins') ? Novel_Coins::get_balance($user_id) : 0;
$expiring = class_exists('Novel_Coins') ? Novel_Coins::get_expiring_soon($user_id, 3) : 0;
?>

<div class="dashboard-coins" id="dashboardCoins">

    <!-- Balance Card -->
    <div class="coins-balance-card">
        <div class="coins-balance-card__main">
            <span class="coins-balance-icon">🪙</span>
            <div class="coins-balance-info">
                <span class="coins-balance-label">موجودی فعلی</span>
                <span class="coins-balance-value" id="coinBalanceValue"><?php echo number_format_i18n($balance); ?></span>
                <span class="coins-balance-unit">سکه</span>
            </div>
        </div>
        <?php if ($expiring > 0): ?>
            <div class="coins-expiry-warning">
                ⚠️ <?php echo number_format_i18n($expiring); ?> سکه تا ۳ روز دیگر منقضی می‌شود!
            </div>
        <?php endif; ?>
        <div class="coins-balance-actions">
            <a href="<?php echo esc_url(home_url('/plans/')); ?>" class="btn-primary btn-sm">🛒 خرید سکه</a>
            <a href="<?php echo esc_url(home_url('/plans/')); ?>" class="btn-outline btn-sm">💎 خرید اشتراک</a>
        </div>
    </div>

    <!-- Transactions -->
    <div class="coins-transactions">
        <h3 class="coins-section-title">📋 تاریخچه تراکنش‌ها</h3>
        
        <div class="coins-table-wrap" id="coinsTableWrap">
            <div class="coins-loading" id="coinsLoading">در حال بارگذاری...</div>
            <table class="coins-table" id="coinsTable" style="display:none">
                <thead>
                    <tr>
                        <th>تاریخ</th>
                        <th>نوع</th>
                        <th>تعداد</th>
                        <th>توضیحات</th>
                        <th>مانده</th>
                    </tr>
                </thead>
                <tbody id="coinsTableBody"></tbody>
            </table>
            <div class="coins-empty" id="coinsEmpty" style="display:none">
                <p>تراکنشی وجود ندارد</p>
            </div>
        </div>

        <div class="coins-load-more" id="coinsLoadMore" style="display:none">
            <button class="btn-outline btn-sm" id="coinsLoadMoreBtn">بارگذاری بیشتر</button>
        </div>
    </div>

</div>

<script>
(function($) {
    'use strict';
    if (typeof novelCoins === 'undefined') return;

    let coinsPage = 1;

    function loadTransactions(page, append) {
        if (!append) $('#coinsLoading').show();

        $.post(novelCoins.ajaxUrl, {
            action: 'novel_get_transactions',
            nonce: novelCoins.nonce,
            page: page
        }, function(res) {
            if (!res.success) return;
            const d = res.data;
            coinsPage = d.page;

            if (d.transactions.length === 0 && !append) {
                $('#coinsLoading').hide();
                $('#coinsEmpty').show();
                return;
            }

            let html = '';
            d.transactions.forEach(t => {
                const amtClass = t.amount > 0 ? 'coin-positive' : 'coin-negative';
                const amtSign = t.amount > 0 ? '+' : '';
                html += '<tr>';
                html += '<td><span class="coin-date">' + t.date + '</span><br><small>' + t.time + '</small></td>';
                html += '<td><span class="coin-type-badge" style="color:' + t.type_color + '">' + t.type_icon + ' ' + t.type_label + '</span></td>';
                html += '<td class="' + amtClass + '">' + amtSign + t.amount + '</td>';
                html += '<td class="coin-desc">' + (t.description || '—') + '</td>';
                html += '<td>' + t.balance + '</td>';
                html += '</tr>';
            });

            if (append) {
                $('#coinsTableBody').append(html);
            } else {
                $('#coinsTableBody').html(html);
            }

            $('#coinsLoading').hide();
            $('#coinsTable').show();
            $('#coinsLoadMore').toggle(d.has_more);
        });
    }

    $(document).ready(function() {
        if (!$('#dashboardCoins').length) return;
        loadTransactions(1);

        $('#coinsLoadMoreBtn').on('click', function() {
            loadTransactions(coinsPage + 1, true);
        });
    });
})(jQuery);
</script>