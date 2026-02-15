<?php
/**
 * Dashboard - Author Stats
 * 
 * آمار نویسنده: بازدید + نمودار + رمان‌ها
 *
 * @package suspended-flavor
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;
if (!is_user_logged_in()) return;

$user_id = get_current_user_id();
$has_novels = count(get_posts([
    'post_type' => 'novel', 'author' => $user_id, 
    'post_status' => 'publish', 'fields' => 'ids', 'posts_per_page' => 1
])) > 0;

if (!$has_novels): ?>
    <div class="author-stats-empty">
        <div class="empty-icon">📊</div>
        <h3>آماری برای نمایش نیست</h3>
        <p>وقتی رمانی منتشر کنید، آمار شما اینجا نمایش داده می‌شود.</p>
    </div>
<?php return; endif; ?>

<div class="author-stats" id="authorStats">

    <!-- Summary Cards -->
    <div class="stats-summary" id="statsSummary">
        <div class="stat-card">
            <span class="stat-card__icon">👁</span>
            <span class="stat-card__value" id="statViews">—</span>
            <span class="stat-card__label">بازدید</span>
        </div>
        <div class="stat-card">
            <span class="stat-card__icon">❤</span>
            <span class="stat-card__value" id="statFollowers">—</span>
            <span class="stat-card__label">فالوور</span>
        </div>
        <div class="stat-card">
            <span class="stat-card__icon">💬</span>
            <span class="stat-card__value" id="statComments">—</span>
            <span class="stat-card__label">دیدگاه</span>
        </div>
        <div class="stat-card">
            <span class="stat-card__icon">⭐</span>
            <span class="stat-card__value" id="statRating">—</span>
            <span class="stat-card__label">امتیاز</span>
        </div>
    </div>

    <!-- Chart Period Tabs -->
    <div class="stats-chart-section">
        <div class="chart-header">
            <h3>📈 نمودار بازدید</h3>
            <div class="chart-period-tabs">
                <button class="chart-period active" data-period="month">ماهانه</button>
                <button class="chart-period" data-period="week">هفتگی</button>
            </div>
        </div>
        <div class="chart-container">
            <canvas id="viewsChart" height="250"></canvas>
        </div>
    </div>

    <!-- Novel Stats Table -->
    <div class="stats-novels-section">
        <h3>📚 آمار هر رمان</h3>
        <div class="stats-table-wrap">
            <table class="stats-table" id="novelStatsTable">
                <thead>
                    <tr>
                        <th>رمان</th>
                        <th>بازدید</th>
                        <th>فالوور</th>
                        <th>دیدگاه</th>
                        <th>امتیاز</th>
                        <th>قسمت‌ها</th>
                    </tr>
                </thead>
                <tbody id="novelStatsBody">
                    <tr><td colspan="6" class="loading-text">در حال بارگذاری...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Chapters -->
    <div class="stats-top-chapters">
        <h3>🔥 محبوب‌ترین قسمت‌ها</h3>
        <div id="topChaptersList"></div>
    </div>

</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>

<script>
(function($) {
    'use strict';

    if (typeof novelRankings === 'undefined') return;

    let chartInstance = null;

    function loadStats(period) {
        $.ajax({
            url: novelRankings.ajaxUrl,
            type: 'POST',
            data: {
                action: 'novel_author_stats',
                nonce: novelRankings.nonce,
                period: period
            },
            success(res) {
                if (!res.success || !res.data.has_novels) return;
                const d = res.data;

                // Summary
                $('#statViews').text(fmtNum(d.summary.views));
                $('#statFollowers').text(fmtNum(d.summary.followers));
                $('#statComments').text(fmtNum(d.summary.comments));
                $('#statRating').text(d.summary.rating || '—');

                // Chart
                renderChart(d.chart);

                // Novel table
                let rows = '';
                d.novels.forEach(n => {
                    rows += `<tr>
                        <td><a href="${n.url}">${esc(n.title)}</a></td>
                        <td>${fmtNum(n.views)}</td>
                        <td>${fmtNum(n.followers)}</td>
                        <td>${fmtNum(n.comments)}</td>
                        <td>★ ${n.rating}</td>
                        <td>${n.chapters}</td>
                    </tr>`;
                });
                $('#novelStatsBody').html(rows || '<tr><td colspan="6">داده‌ای نیست</td></tr>');

                // Top chapters
                let chapHtml = '';
                d.top_chapters.forEach((ch, i) => {
                    chapHtml += `
                        <div class="top-chapter-item">
                            <span class="top-chapter-rank">#${i + 1}</span>
                            <a href="${ch.url}" class="top-chapter-title">${esc(ch.title)}</a>
                            <span class="top-chapter-novel">${esc(ch.novel)}</span>
                            <span class="top-chapter-views">👁 ${fmtNum(ch.views)}</span>
                        </div>
                    `;
                });
                $('#topChaptersList').html(chapHtml || '<p class="text-muted">داده‌ای نیست</p>');
            }
        });
    }

    function renderChart(data) {
        const ctx = document.getElementById('viewsChart');
        if (!ctx) return;

        if (chartInstance) chartInstance.destroy();

        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const textColor = isDark ? '#94a3b8' : '#64748b';
        const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';

        chartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(d => d.date),
                datasets: [{
                    label: 'بازدید',
                    data: data.map(d => d.views),
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 2,
                    pointHoverRadius: 5,
                    borderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        rtl: true,
                        textDirection: 'rtl',
                        backgroundColor: isDark ? '#1e1e32' : '#fff',
                        titleColor: isDark ? '#e2e8f0' : '#333',
                        bodyColor: isDark ? '#94a3b8' : '#666',
                        borderColor: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)',
                        borderWidth: 1,
                    }
                },
                scales: {
                    x: {
                        ticks: { color: textColor, maxTicksLimit: 10 },
                        grid: { color: gridColor }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: textColor },
                        grid: { color: gridColor }
                    }
                }
            }
        });
    }

    function fmtNum(n) {
        n = parseInt(n,10)||0;
        if(n>=1000000) return (n/1000000).toFixed(1)+'M';
        if(n>=1000) return (n/1000).toFixed(1)+'K';
        return n.toLocaleString('fa-IR');
    }

    function esc(s) { const d = document.createElement('div'); d.textContent=s||''; return d.innerHTML; }

    // Init
    $(document).ready(function() {
        if (!$('#authorStats').length) return;
        loadStats('month');

        $('.chart-period').on('click', function() {
            $('.chart-period').removeClass('active');
            $(this).addClass('active');
            loadStats($(this).data('period'));
        });
    });
})(jQuery);
</script>