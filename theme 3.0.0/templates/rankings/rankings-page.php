<?php
/**
 * Rankings Page Template
 * 
 * صفحه رتبه‌بندی رمان‌ها با تب‌های زمانی و نوعی
 *
 * @package suspended-flavor
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;
?>

<div class="rankings-page" id="rankingsPage">

    <!-- Title -->
    <div class="rankings-header">
        <h1 class="rankings-title">🏆 رتبه‌بندی رمان‌ها</h1>
    </div>

    <!-- Period Tabs -->
    <div class="rankings-period-tabs">
        <button class="period-tab active" data-period="total">کل</button>
        <button class="period-tab" data-period="month">ماهانه</button>
        <button class="period-tab" data-period="week">هفتگی</button>
        <button class="period-tab" data-period="today">روزانه</button>
    </div>

    <!-- Type Tabs -->
    <div class="rankings-type-tabs">
        <button class="type-tab active" data-type="popular">📈 محبوب‌ترین</button>
        <button class="type-tab" data-type="rating">⭐ بالاترین امتیاز</button>
        <button class="type-tab" data-type="followers">❤ بیشترین فالوور</button>
        <button class="type-tab" data-type="comments">💬 بیشترین دیدگاه</button>
        <button class="type-tab" data-type="updated">🔄 آخرین آپدیت</button>
        <button class="type-tab" data-type="newest">🆕 تازه‌ترین</button>
        <button class="type-tab" data-type="bookmarks">📚 بیشترین بوکمارک</button>
    </div>

    <!-- Top 3 Podium -->
    <div class="rankings-podium" id="rankingsPodium" style="display:none">
        <div class="podium-item podium-second" id="podium2"></div>
        <div class="podium-item podium-first" id="podium1"></div>
        <div class="podium-item podium-third" id="podium3"></div>
    </div>

    <!-- Rankings List -->
    <div class="rankings-list" id="rankingsList">
        <!-- Loading -->
        <div class="rankings-loading" id="rankingsLoading">
            <?php for ($i = 0; $i < 6; $i++): ?>
                <div class="ranking-skeleton">
                    <div class="skeleton-rank"></div>
                    <div class="skeleton-cover"></div>
                    <div class="skeleton-info">
                        <div class="skeleton-line w-70"></div>
                        <div class="skeleton-line w-50"></div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>

        <!-- Items -->
        <div class="rankings-items" id="rankingsItems" style="display:none"></div>
    </div>

    <!-- Load More -->
    <div class="rankings-load-more" id="rankingsLoadMore" style="display:none">
        <button class="btn-outline" id="rankingsLoadMoreBtn">بارگذاری بیشتر</button>
    </div>

</div>

<script>
(function($) {
    'use strict';

    if (typeof novelRankings === 'undefined') return;

    const Rankings = {
        currentType: 'popular',
        currentPeriod: 'total',
        currentPage: 1,
        isLoading: false,

        init() {
            if (!document.getElementById('rankingsPage')) return;
            this.bindEvents();
            this.load(1);
        },

        bindEvents() {
            const self = this;

            $('.period-tab').on('click', function() {
                $('.period-tab').removeClass('active');
                $(this).addClass('active');
                self.currentPeriod = $(this).data('period');
                self.load(1);
            });

            $('.type-tab').on('click', function() {
                $('.type-tab').removeClass('active');
                $(this).addClass('active');
                self.currentType = $(this).data('type');
                self.load(1);
            });

            $('#rankingsLoadMoreBtn').on('click', function() {
                self.load(self.currentPage + 1, true);
            });
        },

        load(page, append) {
            if (this.isLoading) return;
            this.isLoading = true;

            if (!append) {
                $('#rankingsItems').hide();
                $('#rankingsLoading').show();
                $('#rankingsPodium').hide();
            }

            $.ajax({
                url: novelRankings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'novel_get_rankings',
                    nonce: novelRankings.nonce,
                    type: this.currentType,
                    period: this.currentPeriod,
                    page: page
                },
                success: (res) => {
                    if (!res.success) return;
                    const data = res.data;
                    this.currentPage = data.page;

                    if (data.page === 1 && data.novels.length >= 3) {
                        this.renderPodium(data.novels.slice(0, 3));
                        this.renderList(data.novels.slice(3), append);
                    } else {
                        this.renderList(data.novels, append);
                    }

                    $('#rankingsLoading').hide();
                    $('#rankingsItems').show();
                    
                    if (data.has_more) {
                        $('#rankingsLoadMore').show();
                    } else {
                        $('#rankingsLoadMore').hide();
                    }
                },
                complete: () => { this.isLoading = false; }
            });
        },

        renderPodium(top3) {
            const medals = ['🥇', '🥈', '🥉'];
            const positions = [1, 0, 2]; // Display: 2nd, 1st, 3rd
            const podiumIds = ['podium1', 'podium2', 'podium3'];

            top3.forEach((novel, i) => {
                const html = `
                    <a href="${novel.url}" class="podium-link">
                        <div class="podium-medal">${medals[i]}</div>
                        <img src="${novel.thumb}" alt="${this.esc(novel.title)}" 
                             class="podium-cover" loading="lazy">
                        <h3 class="podium-title">${this.esc(novel.title)}</h3>
                        <div class="podium-meta">
                            <span class="podium-rating">★ ${novel.rating}</span>
                            <span class="podium-chapters">📖 ${this.fmtNum(novel.chapters)}</span>
                        </div>
                    </a>
                `;
                $(`#${podiumIds[i]}`).html(html);
            });

            $('#rankingsPodium').show();
        },

        renderList(novels, append) {
            let html = '';
            novels.forEach(novel => {
                const dirIcon = novel.rank_direction === 'up' 
                    ? `<span class="rank-change rank-up">↑${novel.rank_change}</span>`
                    : novel.rank_direction === 'down'
                    ? `<span class="rank-change rank-down">↓${novel.rank_change}</span>`
                    : novel.rank_direction === 'new'
                    ? `<span class="rank-change rank-new">🆕</span>`
                    : `<span class="rank-change rank-same">──</span>`;

                html += `
                    <div class="ranking-item">
                        <div class="ranking-item__rank">
                            <span class="rank-number">#${novel.rank}</span>
                            ${dirIcon}
                        </div>
                        <a href="${novel.url}" class="ranking-item__cover">
                            <img src="${novel.thumb}" alt="${this.esc(novel.title)}" 
                                 loading="lazy" width="48" height="68">
                        </a>
                        <div class="ranking-item__info">
                            <a href="${novel.url}" class="ranking-item__title">${this.esc(novel.title)}</a>
                            <div class="ranking-item__meta">
                                ${novel.author ? this.esc(novel.author) + ' | ' : ''}
                                <span class="meta-type">${novel.type}</span>
                            </div>
                        </div>
                        <div class="ranking-item__stats">
                            <span class="stat">★ ${novel.rating}</span>
                            <span class="stat">📖 ${this.fmtNum(novel.chapters)}</span>
                            <span class="stat">👁 ${this.fmtNum(novel.views)}</span>
                        </div>
                    </div>
                `;
            });

            if (append) {
                $('#rankingsItems').append(html);
            } else {
                $('#rankingsItems').html(html);
            }
        },

        esc(s) { const d = document.createElement('div'); d.textContent = s||''; return d.innerHTML; },
        fmtNum(n) {
            n = parseInt(n,10)||0;
            if(n>=1000000) return (n/1000000).toFixed(1)+'M';
            if(n>=1000) return (n/1000).toFixed(1)+'K';
            return n.toLocaleString('fa-IR');
        }
    };

    $(document).ready(() => Rankings.init());
})(jQuery);
</script>