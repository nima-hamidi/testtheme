/**
 * Novel Search System
 * 
 * Live Search هدر + Advanced Search صفحه
 *
 * @package suspended-flavor
 * @since 3.0.0
 */

(function($) {
    'use strict';

    if (typeof novelSearch === 'undefined') return;

    /* ═══════════════════════════════════════
       Live Search (Header)
       ═══════════════════════════════════════ */

    const LiveSearch = {
        input: null,
        dropdown: null,
        overlay: null,
        timer: null,
        cache: {},
        activeIndex: -1,
        isOpen: false,
        isMobile: window.innerWidth <= 768,

        /**
         * Init
         */
        init() {
            this.input    = document.getElementById('headerSearchInput');
            this.dropdown = document.getElementById('headerSearchDropdown');
            this.overlay  = document.getElementById('headerSearchOverlay');

            if (!this.input) return;

            this.bindEvents();
            this.loadTrending();
        },

        /**
         * Bind events
         */
        bindEvents() {
            const self = this;

            // Input typing
            $(this.input).on('input', function() {
                clearTimeout(self.timer);
                const val = this.value.trim();

                if (val.length < 2) {
                    self.showTrending();
                    return;
                }

                self.showLoading();
                self.timer = setTimeout(() => self.doSearch(val), 300);
            });

            // Focus → open
            $(this.input).on('focus', function() {
                self.open();
                if (this.value.trim().length < 2) {
                    self.showTrending();
                }
            });

            // Mobile search button
            $(document).on('click', '.header-search-toggle', function(e) {
                e.preventDefault();
                self.openMobile();
            });

            // Mobile cancel
            $(document).on('click', '.search-mobile-cancel', function(e) {
                e.preventDefault();
                self.closeMobile();
            });

            // Close on overlay click
            $(this.overlay).on('click', () => self.close());

            // Keyboard navigation
            $(this.input).on('keydown', function(e) {
                self.handleKeyboard(e);
            });

            // Close on outside click
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.header-search-wrap').length) {
                    self.close();
                }
            });

            // Resize → update mobile flag
            $(window).on('resize', function() {
                self.isMobile = window.innerWidth <= 768;
            });

            // Track search on result click
            $(document).on('click', '.search-result-item', function() {
                const query = self.input.value.trim();
                if (query.length >= 2) {
                    self.trackSearch(query);
                }
            });
        },

        /**
         * Open dropdown
         */
        open() {
            if (this.isOpen) return;
            this.isOpen = true;
            $(this.dropdown).addClass('is-visible');
            if (this.isMobile) {
                $(this.overlay).addClass('is-visible');
            }
        },

        /**
         * Close dropdown
         */
        close() {
            this.isOpen = false;
            this.activeIndex = -1;
            $(this.dropdown).removeClass('is-visible');
            $(this.overlay).removeClass('is-visible');
        },

        /**
         * Open mobile fullscreen
         */
        openMobile() {
            const $wrap = $('.header-search-wrap');
            $wrap.addClass('mobile-fullscreen');
            this.input.focus();
            document.body.style.overflow = 'hidden';
            this.open();
        },

        /**
         * Close mobile fullscreen
         */
        closeMobile() {
            const $wrap = $('.header-search-wrap');
            $wrap.removeClass('mobile-fullscreen');
            this.input.value = '';
            this.input.blur();
            document.body.style.overflow = '';
            this.close();
        },

        /**
         * Show loading skeleton
         */
        showLoading() {
            const skeleton = `
                <div class="search-loading">
                    <div class="search-skeleton-item">
                        <div class="skeleton-thumb"></div>
                        <div class="skeleton-lines">
                            <div class="skeleton-line w-70"></div>
                            <div class="skeleton-line w-50"></div>
                        </div>
                    </div>
                    <div class="search-skeleton-item">
                        <div class="skeleton-thumb"></div>
                        <div class="skeleton-lines">
                            <div class="skeleton-line w-60"></div>
                            <div class="skeleton-line w-40"></div>
                        </div>
                    </div>
                    <div class="search-skeleton-item">
                        <div class="skeleton-thumb"></div>
                        <div class="skeleton-lines">
                            <div class="skeleton-line w-80"></div>
                            <div class="skeleton-line w-45"></div>
                        </div>
                    </div>
                </div>
            `;
            $(this.dropdown).html(skeleton);
            this.open();
        },

        /**
         * Do search AJAX
         */
        doSearch(query) {
            // Check cache
            const cacheKey = query.toLowerCase();
            if (this.cache[cacheKey]) {
                this.renderResults(this.cache[cacheKey]);
                return;
            }

            $.ajax({
                url: novelSearch.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'novel_live_search',
                    nonce: novelSearch.nonce,
                    query: query
                },
                success: (response) => {
                    if (response.success) {
                        // Cache result
                        this.cache[cacheKey] = response.data;
                        this.renderResults(response.data);
                    }
                },
                error: () => {
                    $(this.dropdown).html(
                        '<div class="search-error">خطا در جستجو. لطفاً دوباره تلاش کنید.</div>'
                    );
                }
            });
        },

        /**
         * Render search results
         */
        renderResults(data) {
            if (data.total === 0) {
                this.renderNoResults(data.query);
                return;
            }

            let html = '';

            // Novels
            if (data.novels && data.novels.length > 0) {
                html += `<div class="search-section">
                    <h4 class="search-section__title">${novelSearch.strings.novels}</h4>`;
                
                data.novels.forEach((novel, i) => {
                    html += `
                        <a href="${novel.url}" class="search-result-item search-novel-item" 
                           data-index="${i}" tabindex="-1">
                            <img src="${novel.thumb}" alt="${this.escHtml(novel.title)}" 
                                 class="search-result__thumb" loading="lazy" 
                                 width="40" height="56">
                            <div class="search-result__info">
                                <div class="search-result__title">${this.highlightMatch(novel.title, data.query)}</div>
                                <div class="search-result__meta">
                                    ${novel.author ? this.escHtml(novel.author) + ' | ' : ''}
                                    <span class="search-result__type">${novel.type}</span>
                                    <span class="search-result__chapters">📖 ${this.formatNumber(novel.chapters)}</span>
                                </div>
                            </div>
                            <div class="search-result__rating">
                                <span class="star">★</span> ${novel.rating}
                            </div>
                        </a>
                    `;
                });

                html += '</div>';
            }

            // Authors
            if (data.authors && data.authors.length > 0) {
                html += `<div class="search-section">
                    <h4 class="search-section__title">${novelSearch.strings.authors}</h4>`;

                data.authors.forEach(author => {
                    html += `
                        <a href="${author.url}" class="search-result-item search-author-item" tabindex="-1">
                            <img src="${author.avatar}" alt="${this.escHtml(author.name)}" 
                                 class="search-result__avatar" loading="lazy" width="32" height="32">
                            <div class="search-result__info">
                                <div class="search-result__title">${this.highlightMatch(author.name, data.query)}</div>
                            </div>
                            <span class="search-result__count">📖 ${author.novel_count} ${novelSearch.strings.novels_count}</span>
                        </a>
                    `;
                });

                html += '</div>';
            }

            // Tags
            if (data.tags && data.tags.length > 0) {
                html += `<div class="search-section">
                    <h4 class="search-section__title">${novelSearch.strings.tags}</h4>
                    <div class="search-tags-wrap">`;

                data.tags.forEach(tag => {
                    html += `<a href="${tag.url}" class="search-tag-chip" tabindex="-1">${this.escHtml(tag.name)}</a>`;
                });

                html += '</div></div>';
            }

            // Genres
            if (data.genres && data.genres.length > 0) {
                html += `<div class="search-section">
                    <h4 class="search-section__title">${novelSearch.strings.genres}</h4>
                    <div class="search-tags-wrap">`;

                data.genres.forEach(genre => {
                    html += `<a href="${genre.url}" class="search-genre-chip" tabindex="-1">${this.escHtml(genre.name)}</a>`;
                });

                html += '</div></div>';
            }

            // More results link
            const advancedUrl = this.getAdvancedSearchUrl(data.query);
            html += `
                <div class="search-more">
                    <a href="${advancedUrl}" class="search-more-link" tabindex="-1">
                        🔍 ${novelSearch.strings.moreResults} «${this.escHtml(data.query)}» →
                    </a>
                </div>
            `;

            $(this.dropdown).html(html);
            this.activeIndex = -1;
        },

        /**
         * Render no results
         */
        renderNoResults(query) {
            const advancedUrl = this.getAdvancedSearchUrl(query);
            const html = `
                <div class="search-no-results">
                    <div class="search-no-results__icon">😕</div>
                    <p>${novelSearch.strings.noResults}</p>
                    <a href="${advancedUrl}" class="search-no-results__link">
                        جستجوی پیشرفته →
                    </a>
                </div>
                ${this.trendingHtml || ''}
            `;
            $(this.dropdown).html(html);
        },

        /**
         * Load trending searches
         */
        loadTrending() {
            $.ajax({
                url: novelSearch.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'novel_trending_searches',
                    nonce: novelSearch.nonce
                },
                success: (response) => {
                    if (response.success && response.data.trending) {
                        this.trendingData = response.data.trending;
                        this.trendingHtml = this.buildTrendingHtml(response.data.trending);
                    }
                }
            });
        },

        /**
         * Show trending in dropdown
         */
        showTrending() {
            if (this.trendingHtml) {
                $(this.dropdown).html(this.trendingHtml);
                this.open();
            }
        },

        /**
         * Build trending HTML
         */
        buildTrendingHtml(trending) {
            if (!trending || trending.length === 0) return '';

            let html = `
                <div class="search-section search-trending">
                    <h4 class="search-section__title">${novelSearch.strings.trending}</h4>
                    <div class="search-trending-chips">
            `;

            trending.forEach(term => {
                html += `
                    <button class="search-trending-chip" data-term="${this.escAttr(term)}">
                        ${this.escHtml(term)}
                    </button>
                `;
            });

            html += '</div></div>';
            return html;
        },

        /**
         * Keyboard navigation
         */
        handleKeyboard(e) {
            const $items = $(this.dropdown).find('.search-result-item, .search-more-link, .search-trending-chip');
            const count = $items.length;

            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    this.activeIndex = Math.min(this.activeIndex + 1, count - 1);
                    this.highlightItem($items);
                    break;

                case 'ArrowUp':
                    e.preventDefault();
                    this.activeIndex = Math.max(this.activeIndex - 1, -1);
                    this.highlightItem($items);
                    if (this.activeIndex === -1) this.input.focus();
                    break;

                case 'Enter':
                    if (this.activeIndex >= 0) {
                        e.preventDefault();
                        const $active = $items.eq(this.activeIndex);
                        if ($active.is('[data-term]')) {
                            // Trending chip → search
                            this.input.value = $active.data('term');
                            $(this.input).trigger('input');
                        } else if ($active.attr('href')) {
                            window.location.href = $active.attr('href');
                        }
                    } else {
                        // Go to advanced search
                        const query = this.input.value.trim();
                        if (query.length >= 2) {
                            e.preventDefault();
                            this.trackSearch(query);
                            window.location.href = this.getAdvancedSearchUrl(query);
                        }
                    }
                    break;

                case 'Escape':
                    this.close();
                    this.input.blur();
                    break;
            }
        },

        /**
         * Highlight item
         */
        highlightItem($items) {
            $items.removeClass('is-highlighted');
            if (this.activeIndex >= 0) {
                const $active = $items.eq(this.activeIndex);
                $active.addClass('is-highlighted');
                // Scroll into view
                $active[0]?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
        },

        /**
         * Track search
         */
        trackSearch(query) {
            $.ajax({
                url: novelSearch.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'novel_track_search',
                    nonce: novelSearch.nonce,
                    query: query
                }
            });
        },

        /**
         * Get advanced search URL
         */
        getAdvancedSearchUrl(query) {
            return '/advanced-search/?q=' + encodeURIComponent(query);
        },

        /**
         * Highlight matching text
         */
        highlightMatch(text, query) {
            if (!query) return this.escHtml(text);
            const escaped = this.escHtml(text);
            const regex = new RegExp('(' + this.escRegex(query) + ')', 'gi');
            return escaped.replace(regex, '<mark>$1</mark>');
        },

        /**
         * Escape HTML
         */
        escHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        },

        /**
         * Escape attribute
         */
        escAttr(str) {
            return str.replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;');
        },

        /**
         * Escape regex
         */
        escRegex(str) {
            return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        },

        /**
         * Format number
         */
        formatNumber(num) {
            if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
            if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
            return num.toString();
        }
    };

    /* ═══════════════════════════════════════
       Trending chip click
       ═══════════════════════════════════════ */
    $(document).on('click', '.search-trending-chip', function() {
        const term = $(this).data('term');
        if (LiveSearch.input && term) {
            LiveSearch.input.value = term;
            $(LiveSearch.input).trigger('input');
        }
    });

    /* ═══════════════════════════════════════
       Advanced Search (Page)
       ═══════════════════════════════════════ */

    const AdvancedSearch = {
        container: null,
        resultsWrap: null,
        isLoading: false,
        currentFilters: {},

        /**
         * Init
         */
        init() {
            this.container = document.getElementById('advancedSearchPage');
            if (!this.container) return;

            this.resultsWrap = document.getElementById('advSearchResults');
            this.bindEvents();
            this.loadFromUrl();
        },

        /**
         * Bind events
         */
        bindEvents() {
            const self = this;

            // Search input
            $('#advSearchInput').on('input', function() {
                clearTimeout(self.timer);
                self.timer = setTimeout(() => self.doSearch(1), 400);
            });

            // Filter changes
            $(this.container).on('change', '.adv-filter-select, .adv-filter-radio, .adv-filter-checkbox', function() {
                self.doSearch(1);
            });

            // Sort change
            $('#advSortSelect').on('change', function() {
                self.doSearch(1);
            });

            // Clear filters
            $(this.container).on('click', '.adv-clear-filters', function(e) {
                e.preventDefault();
                self.clearFilters();
            });

            // Pagination
            $(document).on('click', '.adv-pagination .page-link', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                if (page) self.doSearch(page);
            });

            // Mobile filter toggle
            $(document).on('click', '.adv-filter-toggle', function(e) {
                e.preventDefault();
                self.toggleMobileFilters();
            });

            // Apply filters (mobile)
            $(document).on('click', '.adv-apply-filters', function(e) {
                e.preventDefault();
                self.closeMobileFilters();
                self.doSearch(1);
            });

            // Genre chips toggle
            $(this.container).on('click', '.genre-chip', function() {
                $(this).toggleClass('selected');
                self.doSearch(1);
            });

            // Tag multi-select search
            $('#advTagSearch').on('input', function() {
                const val = this.value.toLowerCase();
                $('.adv-tag-option').each(function() {
                    const text = $(this).text().toLowerCase();
                    $(this).toggle(text.includes(val));
                });
            });

            // Range slider (chapters)
            $('#advMinChapters, #advMaxChapters').on('change', function() {
                clearTimeout(self.rangeTimer);
                self.rangeTimer = setTimeout(() => self.doSearch(1), 500);
            });
        },

        /**
         * Gather filters
         */
        gatherFilters() {
            const filters = {};

            filters.search = ($('#advSearchInput').val() || '').trim();

            // Genres (selected chips)
            filters.genres = [];
            $('.genre-chip.selected').each(function() {
                filters.genres.push($(this).data('slug'));
            });

            // Tags
            filters.tags = [];
            $('.adv-tag-option.selected').each(function() {
                filters.tags.push($(this).data('slug'));
            });

            // Type
            filters.type = $('input[name="adv_type"]:checked').val() || '';

            // Status
            filters.status = $('input[name="adv_status"]:checked').val() || '';

            // Country
            filters.country = $('input[name="adv_country"]:checked').val() || '';

            // Rating
            filters.min_rating = $('input[name="adv_rating"]:checked').val() || '';

            // Chapters
            filters.min_chapters = $('#advMinChapters').val() || '';
            filters.max_chapters = $('#advMaxChapters').val() || '';

            // Access
            filters.access = $('input[name="adv_access"]:checked').val() || '';

            // Sort
            filters.sort = $('#advSortSelect').val() || 'popular';

            this.currentFilters = filters;
            return filters;
        },

        /**
         * Do search
         */
        doSearch(page) {
            if (this.isLoading) return;

            const filters = this.gatherFilters();
            filters.page = page || 1;

            this.isLoading = true;
            this.showLoadingSkeleton();
            this.updateUrl(filters);
            this.updateFilterBadge();

            $.ajax({
                url: novelSearch.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'novel_advanced_search',
                    nonce: novelSearch.nonce,
                    ...filters,
                    page: filters.page
                },
                success: (response) => {
                    if (response.success) {
                        this.renderResults(response.data, filters.page);
                    } else {
                        this.renderError();
                    }
                },
                error: () => {
                    this.renderError();
                },
                complete: () => {
                    this.isLoading = false;
                }
            });
        },

        /**
         * Render results
         */
        renderResults(data, page) {
            const $results = $(this.resultsWrap);

            // Count header
            let html = `<div class="adv-results-header">
                <span class="adv-results-count">${this.formatNumber(data.total)} ${novelSearch.strings.found}</span>
            </div>`;

            if (data.novels.length === 0) {
                html += `
                    <div class="adv-no-results">
                        <div class="adv-no-results__icon">😕</div>
                        <p>${novelSearch.strings.noFilter}</p>
                        <button class="btn-outline adv-clear-filters">${novelSearch.strings.clearFilters}</button>
                    </div>
                `;
                $results.html(html);
                return;
            }

            // Novel grid
            html += '<div class="adv-novels-grid">';
            data.novels.forEach(novel => {
                html += this.renderNovelCard(novel);
            });
            html += '</div>';

            // Pagination
            if (data.pages > 1) {
                html += this.renderPagination(page, data.pages);
            }

            $results.html(html);

            // Scroll to top of results
            if (page > 1) {
                $results[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        },

        /**
         * Render novel card
         */
        renderNovelCard(novel) {
            const genresHtml = novel.genres.map(g => 
                `<span class="card-genre-tag">${this.escHtml(g)}</span>`
            ).join('');

            const statusClass = novel.status ? 'status-' + novel.status.replace(/\s/g, '-').toLowerCase() : '';

            return `
                <a href="${novel.url}" class="novel-card-search">
                    <div class="novel-card-search__cover">
                        <img src="${novel.thumb}" alt="${this.escHtml(novel.title)}" 
                             loading="lazy" width="130" height="185">
                        ${novel.status ? `<span class="novel-card-search__status ${statusClass}">${this.escHtml(novel.status)}</span>` : ''}
                        <span class="novel-card-search__type">${novel.type}</span>
                    </div>
                    <div class="novel-card-search__info">
                        <h3 class="novel-card-search__title">${this.escHtml(novel.title)}</h3>
                        ${novel.author ? `<p class="novel-card-search__author">${this.escHtml(novel.author)}</p>` : ''}
                        <div class="novel-card-search__meta">
                            <span class="meta-rating"><span class="star">★</span> ${novel.rating}</span>
                            <span class="meta-chapters">📖 ${this.formatNumber(novel.chapters)}</span>
                            <span class="meta-views">👁 ${this.formatNumber(novel.views)}</span>
                        </div>
                        <div class="novel-card-search__genres">${genresHtml}</div>
                        ${novel.excerpt ? `<p class="novel-card-search__excerpt">${this.escHtml(novel.excerpt)}</p>` : ''}
                    </div>
                </a>
            `;
        },

        /**
         * Render pagination
         */
        renderPagination(current, total) {
            let html = '<div class="adv-pagination">';

            // Previous
            if (current > 1) {
                html += `<button class="page-link" data-page="${current - 1}">← قبلی</button>`;
            }

            // Page numbers
            const range = 2;
            const start = Math.max(1, current - range);
            const end = Math.min(total, current + range);

            if (start > 1) {
                html += `<button class="page-link" data-page="1">1</button>`;
                if (start > 2) html += '<span class="page-dots">...</span>';
            }

            for (let i = start; i <= end; i++) {
                html += `<button class="page-link ${i === current ? 'active' : ''}" data-page="${i}">${i}</button>`;
            }

            if (end < total) {
                if (end < total - 1) html += '<span class="page-dots">...</span>';
                html += `<button class="page-link" data-page="${total}">${total}</button>`;
            }

            // Next
            if (current < total) {
                html += `<button class="page-link" data-page="${current + 1}">بعدی →</button>`;
            }

            html += '</div>';
            return html;
        },

        /**
         * Show loading skeleton
         */
        showLoadingSkeleton() {
            let html = '<div class="adv-results-header"><div class="skeleton-line w-30"></div></div>';
            html += '<div class="adv-novels-grid">';
            for (let i = 0; i < 8; i++) {
                html += `
                    <div class="novel-card-skeleton">
                        <div class="skeleton-cover"></div>
                        <div class="skeleton-info">
                            <div class="skeleton-line w-80"></div>
                            <div class="skeleton-line w-50"></div>
                            <div class="skeleton-line w-60"></div>
                        </div>
                    </div>
                `;
            }
            html += '</div>';
            $(this.resultsWrap).html(html);
        },

        /**
         * Render error
         */
        renderError() {
            $(this.resultsWrap).html(`
                <div class="adv-error">
                    <p>خطا در بارگذاری نتایج. لطفاً دوباره تلاش کنید.</p>
                    <button class="btn-primary" onclick="location.reload()">تلاش مجدد</button>
                </div>
            `);
        },

        /**
         * Clear filters
         */
        clearFilters() {
            $('#advSearchInput').val('');
            $('.genre-chip').removeClass('selected');
            $('.adv-tag-option').removeClass('selected');
            $('input[name="adv_type"][value=""]').prop('checked', true);
            $('input[name="adv_status"][value=""]').prop('checked', true);
            $('input[name="adv_country"][value=""]').prop('checked', true);
            $('input[name="adv_rating"][value=""]').prop('checked', true);
            $('input[name="adv_access"][value=""]').prop('checked', true);
            $('#advMinChapters').val('');
            $('#advMaxChapters').val('');
            $('#advSortSelect').val('popular');
            $('#advTagSearch').val('');
            $('.adv-tag-option').show();

            this.doSearch(1);
        },

        /**
         * Update URL with filters
         */
        updateUrl(filters) {
            const params = new URLSearchParams();

            if (filters.search) params.set('q', filters.search);
            if (filters.genres.length) params.set('genre', filters.genres.join(','));
            if (filters.tags.length) params.set('tag', filters.tags.join(','));
            if (filters.type) params.set('type', filters.type);
            if (filters.status) params.set('status', filters.status);
            if (filters.country) params.set('country', filters.country);
            if (filters.min_rating) params.set('rating', filters.min_rating);
            if (filters.min_chapters) params.set('min_ch', filters.min_chapters);
            if (filters.max_chapters) params.set('max_ch', filters.max_chapters);
            if (filters.access) params.set('access', filters.access);
            if (filters.sort && filters.sort !== 'popular') params.set('sort', filters.sort);
            if (filters.page > 1) params.set('page', filters.page);

            const url = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            history.pushState(null, '', url);
        },

        /**
         * Load filters from URL
         */
        loadFromUrl() {
            const params = new URLSearchParams(window.location.search);

            if (params.has('q')) $('#advSearchInput').val(params.get('q'));
            
            if (params.has('genre')) {
                params.get('genre').split(',').forEach(slug => {
                    $(`.genre-chip[data-slug="${slug}"]`).addClass('selected');
                });
            }

            if (params.has('tag')) {
                params.get('tag').split(',').forEach(slug => {
                    $(`.adv-tag-option[data-slug="${slug}"]`).addClass('selected');
                });
            }

            if (params.has('type'))    $(`input[name="adv_type"][value="${params.get('type')}"]`).prop('checked', true);
            if (params.has('status'))  $(`input[name="adv_status"][value="${params.get('status')}"]`).prop('checked', true);
            if (params.has('country')) $(`input[name="adv_country"][value="${params.get('country')}"]`).prop('checked', true);
            if (params.has('rating'))  $(`input[name="adv_rating"][value="${params.get('rating')}"]`).prop('checked', true);
            if (params.has('access'))  $(`input[name="adv_access"][value="${params.get('access')}"]`).prop('checked', true);
            if (params.has('min_ch'))  $('#advMinChapters').val(params.get('min_ch'));
            if (params.has('max_ch'))  $('#advMaxChapters').val(params.get('max_ch'));
            if (params.has('sort'))    $('#advSortSelect').val(params.get('sort'));

            const page = parseInt(params.get('page'), 10) || 1;

            // Check if any filter exists
            if (params.toString()) {
                this.doSearch(page);
            } else {
                this.doSearch(1);
            }
        },

        /**
         * Toggle mobile filters
         */
        toggleMobileFilters() {
            const $sidebar = $('.adv-filters-sidebar');
            const $overlay = $('.adv-filters-overlay');
            
            $sidebar.toggleClass('is-open');
            $overlay.toggleClass('is-visible');
            document.body.style.overflow = $sidebar.hasClass('is-open') ? 'hidden' : '';
        },

        /**
         * Close mobile filters
         */
        closeMobileFilters() {
            $('.adv-filters-sidebar').removeClass('is-open');
            $('.adv-filters-overlay').removeClass('is-visible');
            document.body.style.overflow = '';
        },

        /**
         * Update filter badge count
         */
        updateFilterBadge() {
            let count = 0;
            const f = this.currentFilters;

            if (f.genres && f.genres.length) count++;
            if (f.tags && f.tags.length) count++;
            if (f.type) count++;
            if (f.status) count++;
            if (f.country) count++;
            if (f.min_rating) count++;
            if (f.min_chapters || f.max_chapters) count++;
            if (f.access) count++;

            const $badge = $('.adv-filter-badge');
            if (count > 0) {
                $badge.text(count).show();
            } else {
                $badge.hide();
            }
        },

        /**
         * Utilities
         */
        escHtml(str) {
            const div = document.createElement('div');
            div.textContent = str || '';
            return div.innerHTML;
        },

        formatNumber(num) {
            num = parseInt(num, 10) || 0;
            if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
            if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
            return num.toLocaleString('fa-IR');
        }
    };

    /* ═══════════════════════════════════════
       Init
       ═══════════════════════════════════════ */
    $(document).ready(function() {
        LiveSearch.init();
        AdvancedSearch.init();
    });

    // Expose
    window.NovelLiveSearch = LiveSearch;
    window.NovelAdvancedSearch = AdvancedSearch;

})(jQuery);