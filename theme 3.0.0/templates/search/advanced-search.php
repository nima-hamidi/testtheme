<?php
/**
 * Advanced Search Page Template
 * 
 * صفحه جستجوی پیشرفته با فیلترهای کامل
 * شورتکد: [novel_search]
 *
 * @package suspended-flavor
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

$filter_options = get_query_var('novel_filter_options', []);
$genres    = $filter_options['genres'] ?? [];
$tags      = $filter_options['tags'] ?? [];
$statuses  = $filter_options['statuses'] ?? [];
$countries = $filter_options['countries'] ?? [];
$types     = $filter_options['types'] ?? [];
$sorts     = $filter_options['sorts'] ?? [];

// Initial query from URL
$initial_query = sanitize_text_field($_GET['q'] ?? '');
?>

<div class="advanced-search-page" id="advancedSearchPage">

    <!-- ═══ Search Header ═══ -->
    <div class="adv-search-header">
        <h1 class="adv-search-page-title">🔍 جستجوی پیشرفته رمان</h1>
        <div class="adv-search-input-wrap">
            <svg class="search-icon-lg" viewBox="0 0 24 24" fill="none" stroke="currentColor" 
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" id="advSearchInput" 
                   placeholder="نام رمان، نویسنده، تگ..." 
                   value="<?php echo esc_attr($initial_query); ?>"
                   autocomplete="off">
        </div>
        
        <!-- Mobile filter toggle + sort -->
        <div class="adv-search-toolbar">
            <button class="adv-filter-toggle" type="button">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" 
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="4" y1="21" x2="4" y2="14"></line>
                    <line x1="4" y1="10" x2="4" y2="3"></line>
                    <line x1="12" y1="21" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12" y2="3"></line>
                    <line x1="20" y1="21" x2="20" y2="16"></line>
                    <line x1="20" y1="12" x2="20" y2="3"></line>
                </svg>
                فیلتر
                <span class="adv-filter-badge" style="display:none">0</span>
            </button>
            
            <div class="adv-sort-wrap">
                <label for="advSortSelect">مرتب‌سازی:</label>
                <select id="advSortSelect">
                    <?php foreach ($sorts as $sort): ?>
                        <option value="<?php echo esc_attr($sort['slug']); ?>">
                            <?php echo esc_html($sort['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- ═══ Layout: Sidebar + Results ═══ -->
    <div class="adv-search-layout">

        <!-- Mobile filter overlay -->
        <div class="adv-filters-overlay" id="advFiltersOverlay"></div>

        <!-- ═══ Filters Sidebar ═══ -->
        <aside class="adv-filters-sidebar" id="advFiltersSidebar">

            <!-- Mobile close handle (drag indicator shown via CSS ::before) -->

            <!-- ── ژانر ── -->
            <?php if (!empty($genres)): ?>
            <div class="adv-filter-group">
                <h4 class="adv-filter-group__title">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" 
                         stroke-width="2"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"></path>
                    </svg>
                    ژانر
                </h4>
                <div class="adv-genres-grid">
                    <?php foreach ($genres as $genre): ?>
                        <button type="button" class="genre-chip" 
                                data-slug="<?php echo esc_attr($genre['slug']); ?>">
                            <?php echo esc_html($genre['name']); ?>
                            <span class="genre-chip__count">(<?php echo (int) $genre['count']; ?>)</span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── نوع رمان ── -->
            <?php if (!empty($types)): ?>
            <div class="adv-filter-group">
                <h4 class="adv-filter-group__title">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" 
                         stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                    نوع رمان
                </h4>
                <div class="adv-radio-group">
                    <label class="adv-radio-option">
                        <input type="radio" name="adv_type" value="" checked>
                        <span>همه</span>
                    </label>
                    <?php foreach ($types as $type): ?>
                        <label class="adv-radio-option">
                            <input type="radio" name="adv_type" value="<?php echo esc_attr($type['slug']); ?>">
                            <span><?php echo esc_html($type['name']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── وضعیت ── -->
            <?php if (!empty($statuses)): ?>
            <div class="adv-filter-group">
                <h4 class="adv-filter-group__title">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" 
                         stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                    وضعیت
                </h4>
                <div class="adv-radio-group">
                    <label class="adv-radio-option">
                        <input type="radio" name="adv_status" value="" checked>
                        <span>همه</span>
                    </label>
                    <?php foreach ($statuses as $status): ?>
                        <label class="adv-radio-option">
                            <input type="radio" name="adv_status" value="<?php echo esc_attr($status['slug']); ?>">
                            <span><?php echo esc_html($status['name']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── کشور ── -->
            <?php if (!empty($countries)): ?>
            <div class="adv-filter-group">
                <h4 class="adv-filter-group__title">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" 
                         stroke-width="2"><circle cx="12" cy="12" r="10"></circle>
                        <line x1="2" y1="12" x2="22" y2="12"></line>
                        <path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"></path>
                    </svg>
                    کشور مبدأ
                </h4>
                <div class="adv-radio-group">
                    <label class="adv-radio-option">
                        <input type="radio" name="adv_country" value="" checked>
                        <span>همه</span>
                    </label>
                    <?php foreach ($countries as $country): ?>
                        <label class="adv-radio-option">
                            <input type="radio" name="adv_country" value="<?php echo esc_attr($country['slug']); ?>">
                            <span><?php echo esc_html($country['name']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── امتیاز ── -->
            <div class="adv-filter-group">
                <h4 class="adv-filter-group__title">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" 
                         stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                    حداقل امتیاز
                </h4>
                <div class="adv-radio-group">
                    <label class="adv-radio-option">
                        <input type="radio" name="adv_rating" value="" checked>
                        <span>همه</span>
                    </label>
                    <label class="adv-radio-option">
                        <input type="radio" name="adv_rating" value="4">
                        <span>★★★★ بالای ۴</span>
                    </label>
                    <label class="adv-radio-option">
                        <input type="radio" name="adv_rating" value="3">
                        <span>★★★ بالای ۳</span>
                    </label>
                    <label class="adv-radio-option">
                        <input type="radio" name="adv_rating" value="2">
                        <span>★★ بالای ۲</span>
                    </label>
                </div>
            </div>

            <!-- ── تعداد قسمت ── -->
            <div class="adv-filter-group">
                <h4 class="adv-filter-group__title">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" 
                         stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                    تعداد قسمت
                </h4>
                <div class="adv-range-inputs">
                    <input type="number" id="advMinChapters" placeholder="از" min="0" step="1">
                    <span class="adv-range-separator">تا</span>
                    <input type="number" id="advMaxChapters" placeholder="تا" min="0" step="1">
                </div>
            </div>

            <!-- ── دسترسی ── -->
            <div class="adv-filter-group">
                <h4 class="adv-filter-group__title">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" 
                         stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0110 0v4"></path>
                    </svg>
                    دسترسی
                </h4>
                <div class="adv-radio-group">
                    <label class="adv-radio-option">
                        <input type="radio" name="adv_access" value="" checked>
                        <span>همه</span>
                    </label>
                    <label class="adv-radio-option">
                        <input type="radio" name="adv_access" value="free">
                        <span>🆓 فقط رایگان</span>
                    </label>
                    <label class="adv-radio-option">
                        <input type="radio" name="adv_access" value="vip">
                        <span>👑 دارای VIP</span>
                    </label>
                </div>
            </div>

            <!-- ── تگ‌ها ── -->
            <?php if (!empty($tags)): ?>
            <div class="adv-filter-group">
                <h4 class="adv-filter-group__title">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" 
                         stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"></path>
                        <line x1="7" y1="7" x2="7.01" y2="7"></line>
                    </svg>
                    تگ‌ها
                </h4>
                <input type="text" id="advTagSearch" class="adv-tag-search" 
                       placeholder="جستجوی تگ..." autocomplete="off">
                <div class="adv-tags-container">
                    <?php foreach ($tags as $tag): ?>
                        <div class="adv-tag-option" data-slug="<?php echo esc_attr($tag['slug']); ?>"
                             role="button" tabindex="0">
                            <span class="adv-tag-option__name"><?php echo esc_html($tag['name']); ?></span>
                            <span class="adv-tag-option__count"><?php echo (int) $tag['count']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── دکمه‌های عملیات ── -->
            <div class="adv-filter-actions">
                <button type="button" class="adv-clear-filters">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" 
                         stroke-width="2"><polyline points="1 4 1 10 7 10"></polyline>
                        <path d="M3.51 15a9 9 0 102.13-9.36L1 10"></path>
                    </svg>
                    پاک کردن فیلترها
                </button>
                
                <!-- Mobile only: apply button -->
                <button type="button" class="adv-apply-filters">
                    🔍 اعمال فیلتر
                </button>
            </div>

        </aside>

        <!-- ═══ Results Area ═══ -->
        <main class="adv-results-area">
            <div id="advSearchResults">
                <!-- Filled by JS -->
                <div class="adv-initial-loading">
                    <div class="adv-initial-loading__spinner">
                        <span class="spinner-dots-lg">
                            <span></span><span></span><span></span>
                        </span>
                    </div>
                    <p>در حال بارگذاری رمان‌ها...</p>
                </div>
            </div>
        </main>

    </div>

</div>