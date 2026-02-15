<?php
/**
 * Dashboard - My Library (Bookmarks)
 * 
 * کتابخانه شخصی کاربر با تب‌های وضعیت
 *
 * @package suspended-flavor
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

if (!is_user_logged_in()) return;

$user_id = get_current_user_id();
$bookmarks = Novel_Bookmarks::get_instance();
$statuses = $bookmarks->get_status_labels();
?>

<div class="dashboard-library" id="dashboardLibrary">
    
    <!-- Header -->
    <div class="library-header">
        <h2 class="library-title">📚 کتابخانه من</h2>
        <div class="library-search">
            <input type="text" id="librarySearchInput" 
                   placeholder="جستجو در کتابخانه..." 
                   autocomplete="off">
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="library-tabs" id="libraryTabs">
        <button class="library-tab active" data-status="">
            همه <span class="tab-count" id="countAll">0</span>
        </button>
        <?php foreach ($statuses as $key => $info): ?>
            <button class="library-tab" data-status="<?php echo esc_attr($key); ?>">
                <?php echo $info['icon']; ?> 
                <?php echo esc_html($info['label']); ?>
                <span class="tab-count" id="count_<?php echo esc_attr($key); ?>">0</span>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- Sort Options -->
    <div class="library-sort">
        <label>مرتب‌سازی:</label>
        <select id="librarySortSelect">
            <option value="updated">آخرین مطالعه</option>
            <option value="name">نام رمان</option>
            <option value="rating">امتیاز</option>
            <option value="progress">پیشرفت</option>
            <option value="added">تاریخ افزودن</option>
        </select>
    </div>

    <!-- Results -->
    <div class="library-items" id="libraryItems">
        <!-- Loading skeleton -->
        <div class="library-loading" id="libraryLoading">
            <?php for ($i = 0; $i < 4; $i++): ?>
                <div class="library-item-skeleton">
                    <div class="skeleton-cover"></div>
                    <div class="skeleton-info">
                        <div class="skeleton-line w-70"></div>
                        <div class="skeleton-line w-50"></div>
                        <div class="skeleton-line w-80"></div>
                        <div class="skeleton-line w-40"></div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>

        <!-- Items container -->
        <div class="library-list" id="libraryList" style="display:none"></div>

        <!-- Empty state -->
        <div class="library-empty" id="libraryEmpty" style="display:none">
            <div class="library-empty__icon">📚</div>
            <h3>کتابخانه شما خالی است!</h3>
            <p>بیایید اولین رمان را اضافه کنید.</p>
            <a href="<?php echo esc_url(home_url('/advanced-search/')); ?>" class="btn-primary">
                🔍 جستجوی رمان
            </a>
        </div>
    </div>

    <!-- Load More -->
    <div class="library-load-more" id="libraryLoadMore" style="display:none">
        <button class="btn-outline" id="libraryLoadMoreBtn">
            بارگذاری بیشتر
        </button>
    </div>

</div>

<!-- Library Item Template (JS) -->
<script type="text/html" id="tmpl-library-item">
<div class="library-item" data-novel-id="{{data.id}}" data-status="{{data.status}}">
    <a href="{{data.url}}" class="library-item__cover">
        <img src="{{data.thumb}}" alt="{{data.title}}" loading="lazy" width="60" height="84">
        <span class="library-item__type">{{data.type}}</span>
    </a>
    
    <div class="library-item__info">
        <div class="library-item__top">
            <a href="{{data.url}}" class="library-item__title">{{data.title}}</a>
            <div class="library-item__dropdown">
                <button class="library-item__status-btn" style="color:{{data.status_color}}">
                    {{data.status_icon}} {{data.status_label}} ▾
                </button>
                <div class="library-item__dropdown-menu" style="display:none">
                    <# _.each(novelBookmark.statuses, function(info, key) { #>
                        <button class="dropdown-status-btn {{key === data.status ? 'active' : ''}}" 
                                data-novel-id="{{data.id}}" data-status="{{key}}">
                            {{info.icon}} {{info.label}}
                            <# if (key === data.status) { #><span class="check">✓</span><# } #>
                        </button>
                    <# }); #>
                    <div class="dropdown-divider"></div>
                    <button class="dropdown-remove-btn" data-novel-id="{{data.id}}">
                        🗑 حذف از کتابخانه
                    </button>
                </div>
            </div>
        </div>
        
        <div class="library-item__meta">
            <# if (data.author) { #>
                <span>{{data.author}}</span> | 
            <# } #>
            <span>{{data.type}}</span> | 
            <span>★ {{data.rating}}</span>
        </div>
        
        <# if (data.last_chapter > 0) { #>
            <div class="library-item__chapter">
                آخرین خوانده: قسمت {{data.last_chapter}}
            </div>
        <# } #>
        
        <div class="library-item__progress">
            <div class="progress-bar">
                <div class="progress-bar__fill" style="width:{{data.progress}}%"></div>
            </div>
            <span class="progress-bar__text">
                {{data.last_chapter}}/{{data.chapter_count}} ({{data.progress}}٪)
            </span>
        </div>
        
        <div class="library-item__footer">
            <span class="library-item__date">📅 {{data.updated_human}}</span>
            <div class="library-item__actions">
                <# if (data.continue_url) { #>
                    <a href="{{data.continue_url}}" class="btn-continue">
                        ▶ ادامه مطالعه
                    </a>
                <# } #>
            </div>
        </div>
    </div>
</div>
</script>