<?php
/**
 * Dashboard - Author Income
 *
 * @package suspended-flavor
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;
if (!is_user_logged_in()) return;
?>

<div class="author-income" id="authorIncome">

    <!-- Summary -->
    <div class="income-summary" id="incomeSummary">
        <div class="income-card">
            <span class="income-card__label">💰 کل درآمد</span>
            <span class="income-card__value" id="incomeTotal">—</span>
        </div>
        <div class="income-card">
            <span class="income-card__label">📅 این ماه</span>
            <span class="income-card__value" id="incomeMonth">—</span>
        </div>
        <div class="income-card">
            <span class="income-card__label">⏳ در انتظار</span>
            <span class="income-card__value" id="incomePending">—</span>
        </div>
        <div class="income-card">
            <span class="income-card__label">✅ واریز شده</span>
            <span class="income-card__value" id="incomePaid">—</span>
        </div>
    </div>

    <!-- Chart -->
    <div class="income-chart-section">
        <h3>📊 نمودار درآمد ماهانه</h3>
        <div class="income-chart-container">
            <canvas id="incomeChart" height="220"></canvas>
        </div>
    </div>

    <!-- Per Novel -->
    <div class="income-novels-section">
        <h3>📚 درآمد هر رمان</h3>
        <div class="income-table-wrap">
            <table class="income-table" id="incomeNovelTable">
                <thead>
                    <tr>
                        <th>رمان</th>
                        <th>قسمت‌های VIP</th>
                        <th>فروش</th>
                        <th>سکه</th>
                        <th>درآمد (ریال)</th>
                    </tr>
                </thead>
                <tbody id="incomeNovelBody">
                    <tr><td colspan="5">در حال بارگذاری...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payout Request -->
    <div class="income-payout-section" id="payoutSection">
        <h3>💳 درخواست واریز</h3>
        <div class="payout-info" id="payoutInfo"></div>
        
        <div class="payout-form" id="payoutForm" style="display:none">
            <div class="form-group">
                <label>مبلغ (ریال):</label>
                <input type="number" id="payoutAmount" min="0">
            </div>
            <div class="form-group">
                <label>شماره کارت:</label>
                <input type="text" id="payoutCard" maxlength="19" placeholder="xxxx-xxxx-xxxx-xxxx" dir="ltr">
            </div>
            <div class="form-group">
                <label>نام صاحب کارت:</label>
                <input type="text" id="payoutName">
            </div>
            <div class="form-group">
                <label>نام بانک:</label>
                <input type="text" id="payoutBank">
            </div>
            <button class="btn-primary" id="payoutSubmitBtn">💳 ارسال درخواست واریز</button>
        </div>
    </div>

    <!-- Payout History -->
    <div class="income-payouts-history">
        <h3>📜 تاریخچه واریزی‌ها</h3>
        <div id="payoutsHistoryList"></div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>

<script>
(function($) {
    'use strict';
    if (typeof novelCoins === 'undefined') return;

    let incomeChart = null;

    function loadIncome() {
        $.post(novelCoins.ajaxUrl, {
            action: 'novel_get_income_stats',
            nonce: novelCoins.nonce
        }, function(res) {
            if (!res.success) return;
            const d = res.data;

            // Summary
            $('#incomeTotal').text(formatRial(d.summary.total));
            $('#incomeMonth').text(formatRial(d.summary.month));
            $('#incomePending').text(formatRial(d.summary.pending));
            $('#incomePaid').text(formatRial(d.summary.paid));

            // Novel table
            let rows = '';
            if (d.novels && d.novels.length) {
                d.novels.forEach(n => {
                    rows += '<tr>';
                    rows += '<td>' + esc(n.novel_title) + '</td>';
                    rows += '<td>' + n.vip_chapters + '</td>';
                    rows += '<td>' + n.total_sales + '</td>';
                    rows += '<td>🪙 ' + n.total_coins + '</td>';
                    rows += '<td>' + formatRial(n.total_value) + '</td>';
                    rows += '</tr>';
                });
            } else {
                rows = '<tr><td colspan="5" style="text-align:center;color:#999;">فروشی ثبت نشده</td></tr>';
            }
            $('#incomeNovelBody').html(rows);

            // Chart
            renderIncomeChart(d.chart);

            // Payout info
            if (d.can_request) {
                $('#payoutInfo').html('<p>موجودی قابل واریز: <strong>' + formatRial(d.summary.pending) + '</strong></p>');
                $('#payoutForm').show();
                $('#payoutAmount').attr('max', d.summary.pending).val(d.summary.pending);
            } else {
                $('#payoutInfo').html('<p>حداقل موجودی برای درخواست واریز: <strong>' + formatRial(d.min_payout) + '</strong></p><p>موجودی فعلی شما: <strong>' + formatRial(d.summary.pending) + '</strong></p>');
                $('#payoutForm').hide();
            }

            // Payout history
            let payHtml = '';
            if (d.payouts && d.payouts.length) {
                payHtml = '<table class="income-table"><thead><tr><th>تاریخ</th><th>مبلغ</th><th>وضعیت</th></tr></thead><tbody>';
                d.payouts.forEach(p => {
                    const statusMap = {
                        pending: '⏳ در انتظار',
                        processing: '🔄 در حال پردازش',
                        completed: '✅ واریز شده',
                        failed: '❌ ناموفق'
                    };
                    payHtml += '<tr>';
                    payHtml += '<td>' + (p.created_at ? p.created_at.substring(0,10) : '—') + '</td>';
                    payHtml += '<td>' + formatRial(p.amount) + '</td>';
                    payHtml += '<td>' + (statusMap[p.status] || p.status) + '</td>';
                    payHtml += '</tr>';
                });
                payHtml += '</tbody></table>';
            } else {
                payHtml = '<p style="color:#999;">واریزی ثبت نشده</p>';
            }
            $('#payoutsHistoryList').html(payHtml);
        });
    }

    function renderIncomeChart(data) {
        const ctx = document.getElementById('incomeChart');
        if (!ctx || !data) return;
        if (incomeChart) incomeChart.destroy();

        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';

        incomeChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(d => d.month),
                datasets: [{
                    label: 'درآمد (ریال)',
                    data: data.map(d => d.earning),
                    backgroundColor: 'rgba(16, 185, 129, 0.6)',
                    borderColor: '#10b981',
                    borderWidth: 1,
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: isDark ? '#94a3b8' : '#64748b' }, grid: { display: false } },
                    y: { beginAtZero: true, ticks: { color: isDark ? '#94a3b8' : '#64748b', callback: v => formatRial(v) } }
                }
            }
        });
    }

    function formatRial(n) {
        n = parseInt(n, 10) || 0;
        if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
        if (n >= 1000) return (n / 1000).toFixed(0) + 'K';
        return n.toLocaleString('fa-IR');
    }

    function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

    // Card number formatting
    $('#payoutCard').on('input', function() {
        let v = this.value.replace(/[^0-9]/g, '').substring(0, 16);
        this.value = v.replace(/(.{4})/g, '$1-').replace(/-$/, '');
    });

    // Submit payout
    $('#payoutSubmitBtn').on('click', function() {
        const amount = $('#payoutAmount').val();
        const card = $('#payoutCard').val();
        const name = $('#payoutName').val();
        const bank = $('#payoutBank').val();

        if (!amount || !card || !name) {
            NovelToast.show('لطفاً همه فیلدها را پر کنید', 'warning');
            return;
        }

        $(this).prop('disabled', true).text('در حال ارسال...');

        $.post(novelCoins.ajaxUrl, {
            action: 'novel_request_payout',
            nonce: novelCoins.nonce,
            amount: amount,
            card_number: card,
            card_name: name,
            bank_name: bank
        }, function(res) {
            if (res.success) {
                NovelToast.show(res.data.message, 'success');
                loadIncome();
            } else {
                NovelToast.show(res.data?.message || 'خطا', 'error');
            }
            $('#payoutSubmitBtn').prop('disabled', false).text('💳 ارسال درخواست واریز');
        });
    });

    $(document).ready(function() {
        if (!$('#authorIncome').length) return;
        loadIncome();
    });
})(jQuery);
</script>