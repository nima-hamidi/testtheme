<?php
/**
 * Dashboard - Reading History
 * 
 * تاریخچه مطالعه کاربر با گروه‌بندی روزانه
 *
 * @package suspended-flavor
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

if (!is_user_logged_in()) return;
?>

<div class="dashboard-history" id="dashboardHistory">
    
    <!-- Header -->
    <div class="history-header">
        <h2 class="history-title">📜 تاریخچه مطالعه</h2>
        <button class="btn-clear-history" id="clearHistoryBtn" title="پاک کردن تاریخچه">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" 
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"></path>
            </svg>
            پاک کردن همه
        </button>
    </div>

    <!-- History Content -->
    <div class="history-content" id="historyContent">
        
        <!-- Loading -->
        <div class="history-loading" id="historyLoading">
            <?php for ($i = 0; $i < 6; $i++): ?>
                <div class="history-skeleton">
                    <div class="skeleton-thumb-sm"></div>
                    <div class="skeleton-info">
                        <div class="skeleton-line w-60"></div>
                        <div class="skeleton-line w-40"></div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>

        <!-- Items -->
        <div class="history-groups" id="historyGroups" style="display:none"></div>

        <!-- Empty -->
        <div class="history-empty" id="historyEmpty" style="display:none">
            <div class="history-empty__icon">📜</div>
            <h3>تاریخچه‌ای وجود ندارد</h3>
            <p>وقتی قسمتی بخوانید، اینجا ثبت می‌شود.</p>
        </div>
    </div>

    <!-- Load More -->
    <div class="history-load-more" id="historyLoadMore" style="display:none">
        <button class="btn-outline" id="historyLoadMoreBtn">
            بارگذاری بیشتر
        </button>
    </div>

</div>

<!-- History Group Template -->
<script type="text/html" id="tmpl-history-group">
<div class="history-group">
    <h3 class="history-group__date">{{data.label}}</h3>
    <div class="history-group__items">
        <# _.each(data.items, function(item) { #>
            <div class="history-item" data-chapter-id="{{item.chapter_id}}">
                <a href="{{item.chapter_url}}" class="history-item__cover">
                    <img src="{{item.thumb}}" alt="{{item.novel_title}}" 
                         loading="lazy" width="44" height="62">
                </a>
                <div class="history-item__info">
                    <a href="{{item.chapter_url}}" class="history-item__title">
                        {{item.novel_title}} - قسمت {{item.chapter_num}}
                    </a>
                    <span class="history-item__time">⏱ {{item.time}}</span>
                </div>
                <button class="history-item__delete" data-chapter-id="{{item.chapter_id}}" 
                        title="حذف">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" 
                         stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        <# }); #>
    </div>
</div>
</script>