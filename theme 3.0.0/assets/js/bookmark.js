/**
 * Novel Bookmark & Library System
 * 
 * کتابخانه + تاریخچه + ادامه مطالعه + scroll position
 *
 * @package suspended-flavor
 * @since 3.0.0
 */

(function($) {
    'use strict';

    if (typeof novelBookmark === 'undefined') return;

    /* ═══════════════════════════════════════
       Library Dropdown (Global)
       ═══════════════════════════════════════ */

    const LibraryDropdown = {
        init() {
            this.bindEvents();
        },

        bindEvents() {
            // Toggle library dropdown on novel cards / single-novel
            $(document).on('click', '.btn-library-toggle', function(e) {
                e.preventDefault();
                e.stopPropagation();

                if (!novelBookmark.loggedIn) {
                    NovelToast.show(novelBookmark.strings.loginRequired, 'warning');
                    return;
                }

                const $btn = $(this);
                const $dropdown = $btn.next('.library-dropdown');
                
                // Close all others
                $('.library-dropdown.is-open').not($dropdown).removeClass('is-open');
                $dropdown.toggleClass('is-open');
            });

            // Select status from dropdown
            $(document).on('click', '.library-status-option', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const $opt = $(this);
                const novelId = $opt.data('novel-id');
                const status = $opt.data('status');

                LibraryDropdown.setStatus(novelId, status, $opt);
            });

            // Remove from library
            $(document).on('click', '.library-remove-option', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const novelId = $(this).data('novel-id');
                if (confirm(novelBookmark.strings.confirmRemove)) {
                    LibraryDropdown.remove(novelId, $(this));
                }
            });

            // Close dropdown on outside click
            $(document).on('click', function() {
                $('.library-dropdown.is-open').removeClass('is-open');
            });
        },

        setStatus(novelId, status, $trigger) {
            $.ajax({
                url: novelBookmark.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'novel_add_to_library',
                    nonce: novelBookmark.nonce,
                    novel_id: novelId,
                    status: status
                },
                success(res) {
                    if (res.success) {
                        NovelToast.show(res.data.message, 'success');

                        // Update button appearance
                        const $wrap = $trigger.closest('.library-wrap');
                        const $btn = $wrap.find('.btn-library-toggle');
                        $btn.addClass('in-library')
                            .attr('data-current-status', status);
                        $btn.find('.library-btn-text').text(res.data.icon + ' ' + res.data.label);

                        // Update active state in dropdown
                        $wrap.find('.library-status-option').removeClass('active');
                        $trigger.addClass('active');

                        // Close dropdown
                        $wrap.find('.library-dropdown').removeClass('is-open');
                    } else {
                        NovelToast.show(res.data?.message || novelBookmark.strings.error, 'error');
                    }
                },
                error() {
                    NovelToast.show(novelBookmark.strings.error, 'error');
                }
            });
        },

        remove(novelId, $trigger) {
            $.ajax({
                url: novelBookmark.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'novel_remove_from_library',
                    nonce: novelBookmark.nonce,
                    novel_id: novelId
                },
                success(res) {
                    if (res.success) {
                        NovelToast.show(novelBookmark.strings.removed, 'info');

                        const $wrap = $trigger.closest('.library-wrap');
                        const $btn = $wrap.find('.btn-library-toggle');
                        $btn.removeClass('in-library').removeAttr('data-current-status');
                        $btn.find('.library-btn-text').text(novelBookmark.strings.addToLibrary);
                        $wrap.find('.library-status-option').removeClass('active');
                        $wrap.find('.library-dropdown').removeClass('is-open');
                    }
                }
            });
        }
    };

    /* ═══════════════════════════════════════
       Dashboard Library
       ═══════════════════════════════════════ */

    const DashboardLibrary = {
        currentStatus: '',
        currentSort: 'updated',
        currentPage: 1,
        totalPages: 1,
        searchTerm: '',
        isLoading: false,

        init() {
            if (!document.getElementById('dashboardLibrary')) return;
            this.bindEvents();
            this.loadLibrary(1);
        },

        bindEvents() {
            const self = this;

            // Tab clicks
            $('#libraryTabs').on('click', '.library-tab', function() {
                $('.library-tab').removeClass('active');
                $(this).addClass('active');
                self.currentStatus = $(this).data('status');
                self.loadLibrary(1);
            });

            // Sort change
            $('#librarySortSelect').on('change', function() {
                self.currentSort = this.value;
                self.loadLibrary(1);
            });

            // Search
            let searchTimer;
            $('#librarySearchInput').on('input', function() {
                clearTimeout(searchTimer);
                const val = this.value.trim();
                searchTimer = setTimeout(() => {
                    self.searchTerm = val;
                    self.filterLocal(val);
                }, 200);
            });

            // Load more
            $('#libraryLoadMoreBtn').on('click', function() {
                if (self.currentPage < self.totalPages) {
                    self.loadLibrary(self.currentPage + 1, true);
                }
            });

            // Status change in item dropdown
            $(document).on('click', '.dropdown-status-btn', function(e) {
                e.stopPropagation();
                const novelId = $(this).data('novel-id');
                const status = $(this).data('status');
                self.changeItemStatus(novelId, status, $(this));
            });

            // Remove from item dropdown
            $(document).on('click', '.dropdown-remove-btn', function(e) {
                e.stopPropagation();
                const novelId = $(this).data('novel-id');
                if (confirm(novelBookmark.strings.confirmRemove)) {
                    self.removeItem(novelId);
                }
            });

            // Item dropdown toggle
            $(document).on('click', '.library-item__status-btn', function(e) {
                e.stopPropagation();
                const $menu = $(this).next('.library-item__dropdown-menu');
                $('.library-item__dropdown-menu').not($menu).hide();
                $menu.toggle();
            });

            // Close item dropdowns
            $(document).on('click', function() {
                $('.library-item__dropdown-menu').hide();
            });
        },

        loadLibrary(page, append) {
            if (this.isLoading) return;
            this.isLoading = true;

            if (!append) {
                $('#libraryList').hide();
                $('#libraryEmpty').hide();
                $('#libraryLoading').show();
            }

            $.ajax({
                url: novelBookmark.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'novel_get_library',
                    nonce: novelBookmark.nonce,
                    status: this.currentStatus,
                    sort: this.currentSort,
                    page: page
                },
                success: (res) => {
                    if (!res.success) return;

                    const data = res.data;
                    this.currentPage = data.page;
                    this.totalPages = data.pages;

                    // Update counts
                    this.updateCounts(data.counts);

                    // Render items
                    if (data.novels.length === 0 && !append) {
                        $('#libraryLoading').hide();
                        $('#libraryList').hide();
                        $('#libraryEmpty').show();
                        $('#libraryLoadMore').hide();
                        return;
                    }

                    const html = data.novels.map(novel => this.renderItem(novel)).join('');

                    if (append) {
                        $('#libraryList').append(html);
                    } else {
                        $('#libraryList').html(html);
                    }

                    $('#libraryLoading').hide();
                    $('#libraryList').show();
                    $('#libraryEmpty').hide();

                    // Load more button
                    if (data.has_more) {
                        $('#libraryLoadMore').show();
                    } else {
                        $('#libraryLoadMore').hide();
                    }
                },
                complete: () => {
                    this.isLoading = false;
                }
            });
        },

        renderItem(novel) {
            // Use underscore template if available, else manual
            const tmpl = document.getElementById('tmpl-library-item');
            if (tmpl && typeof _ !== 'undefined') {
                const compiled = _.template(tmpl.innerHTML);
                return compiled({ data: novel });
            }

            // Manual fallback
            const statusDropdown = Object.entries(novelBookmark.statuses).map(([key, info]) => {
                const active = key === novel.status ? 'active' : '';
                const check = key === novel.status ? '<span class="check">✓</span>' : '';
                return `<button class="dropdown-status-btn ${active}" data-novel-id="${novel.id}" data-status="${key}">
                    ${info.icon} ${info.label} ${check}
                </button>`;
            }).join('');

            return `
                <div class="library-item" data-novel-id="${novel.id}" data-status="${novel.status}">
                    <a href="${novel.url}" class="library-item__cover">
                        <img src="${novel.thumb}" alt="${this.esc(novel.title)}" loading="lazy" width="60" height="84">
                        <span class="library-item__type">${novel.type}</span>
                    </a>
                    <div class="library-item__info">
                        <div class="library-item__top">
                            <a href="${novel.url}" class="library-item__title">${this.esc(novel.title)}</a>
                            <div class="library-item__dropdown">
                                <button class="library-item__status-btn" style="color:${novel.status_color}">
                                    ${novel.status_icon} ${novel.status_label} ▾
                                </button>
                                <div class="library-item__dropdown-menu" style="display:none">
                                    ${statusDropdown}
                                    <div class="dropdown-divider"></div>
                                    <button class="dropdown-remove-btn" data-novel-id="${novel.id}">
                                        🗑 حذف از کتابخانه
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="library-item__meta">
                            ${novel.author ? this.esc(novel.author) + ' | ' : ''}
                            ${novel.type} | ★ ${novel.rating}
                        </div>
                        ${novel.last_chapter > 0 ? `<div class="library-item__chapter">آخرین خوانده: قسمت ${novel.last_chapter}</div>` : ''}
                        <div class="library-item__progress">
                            <div class="progress-bar">
                                <div class="progress-bar__fill" style="width:${novel.progress}%"></div>
                            </div>
                            <span class="progress-bar__text">
                                ${novel.last_chapter}/${novel.chapter_count} (${novel.progress}٪)
                            </span>
                        </div>
                        <div class="library-item__footer">
                            <span class="library-item__date">📅 ${novel.updated_human}</span>
                            ${novel.continue_url ? `<a href="${novel.continue_url}" class="btn-continue">▶ ادامه مطالعه</a>` : ''}
                        </div>
                    </div>
                </div>
            `;
        },

        updateCounts(counts) {
            $('#countAll').text(counts.all || 0);
            Object.entries(counts).forEach(([key, val]) => {
                if (key !== 'all') {
                    $(`#count_${key}`).text(val || 0);
                }
            });
        },

        changeItemStatus(novelId, status, $btn) {
            $.ajax({
                url: novelBookmark.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'novel_change_library_status',
                    nonce: novelBookmark.nonce,
                    novel_id: novelId,
                    status: status
                },
                success: (res) => {
                    if (res.success) {
                        NovelToast.show(res.data.message, 'success');
                        // Reload to update counts
                        this.loadLibrary(1);
                    }
                }
            });
        },

        removeItem(novelId) {
            $.ajax({
                url: novelBookmark.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'novel_remove_from_library',
                    nonce: novelBookmark.nonce,
                    novel_id: novelId
                },
                success: (res) => {
                    if (res.success) {
                        NovelToast.show(novelBookmark.strings.removed, 'info');
                        $(`.library-item[data-novel-id="${novelId}"]`).slideUp(300, function() {
                            $(this).remove();
                        });
                        // Reload counts
                        setTimeout(() => this.loadLibrary(this.currentPage), 400);
                    }
                }
            });
        },

        filterLocal(term) {
            if (!term) {
                $('.library-item').show();
                return;
            }
            const lower = term.toLowerCase();
            $('.library-item').each(function() {
                const title = $(this).find('.library-item__title').text().toLowerCase();
                $(this).toggle(title.includes(lower));
            });
        },

        esc(str) {
            const div = document.createElement('div');
            div.textContent = str || '';
            return div.innerHTML;
        }
    };

    /* ═══════════════════════════════════════
       Dashboard History
       ═══════════════════════════════════════ */

    const DashboardHistory = {
        currentPage: 1,
        totalPages: 1,
        isLoading: false,

        init() {
            if (!document.getElementById('dashboardHistory')) return;
            this.bindEvents();
            this.loadHistory(1);
        },

        bindEvents() {
            const self = this;

            // Load more
            $('#historyLoadMoreBtn').on('click', function() {
                if (self.currentPage < self.totalPages) {
                    self.loadHistory(self.currentPage + 1, true);
                }
            });

            // Delete single item
            $(document).on('click', '.history-item__delete', function(e) {
                e.preventDefault();
                const chapterId = $(this).data('chapter-id');
                self.deleteItem(chapterId, $(this));
            });

            // Clear all
            $('#clearHistoryBtn').on('click', function() {
                if (confirm(novelBookmark.strings.confirmClear)) {
                    self.clearAll();
                }
            });
        },

        loadHistory(page, append) {
            if (this.isLoading) return;
            this.isLoading = true;

            if (!append) {
                $('#historyGroups').hide();
                $('#historyEmpty').hide();
                $('#historyLoading').show();
            }

            $.ajax({
                url: novelBookmark.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'novel_get_history',
                    nonce: novelBookmark.nonce,
                    page: page
                },
                success: (res) => {
                    if (!res.success) return;

                    const data = res.data;
                    this.currentPage = data.page;
                    this.totalPages = data.pages;

                    const groups = data.groups;
                    const keys = Object.keys(groups);

                    if (keys.length === 0 && !append) {
                        $('#historyLoading').hide();
                        $('#historyGroups').hide();
                        $('#historyEmpty').show();
                        $('#historyLoadMore').hide();
                        return;
                    }

                    let html = '';
                    keys.forEach(label => {
                        html += this.renderGroup(label, groups[label]);
                    });

                    if (append) {
                        $('#historyGroups').append(html);
                    } else {
                        $('#historyGroups').html(html);
                    }

                    $('#historyLoading').hide();
                    $('#historyGroups').show();
                    $('#historyEmpty').hide();

                    if (data.has_more) {
                        $('#historyLoadMore').show();
                    } else {
                        $('#historyLoadMore').hide();
                    }
                },
                complete: () => {
                    this.isLoading = false;
                }
            });
        },

        renderGroup(label, items) {
            const itemsHtml = items.map(item => `
                <div class="history-item" data-chapter-id="${item.chapter_id}">
                    <a href="${item.chapter_url}" class="history-item__cover">
                        <img src="${item.thumb}" alt="${this.esc(item.novel_title)}" 
                             loading="lazy" width="44" height="62">
                    </a>
                    <div class="history-item__info">
                        <a href="${item.chapter_url}" class="history-item__title">
                            ${this.esc(item.novel_title)} - قسمت ${item.chapter_num}
                        </a>
                        <span class="history-item__time">⏱ ${item.time}</span>
                    </div>
                    <button class="history-item__delete" data-chapter-id="${item.chapter_id}" title="حذف">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" 
                             stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
            `).join('');

            return `
                <div class="history-group">
                    <h3 class="history-group__date">${this.esc(label)}</h3>
                    <div class="history-group__items">${itemsHtml}</div>
                </div>
            `;
        },

        deleteItem(chapterId, $btn) {
            $.ajax({
                url: novelBookmark.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'novel_delete_history_item',
                    nonce: novelBookmark.nonce,
                    chapter_id: chapterId
                },
                success: (res) => {
                    if (res.success) {
                        const $item = $btn.closest('.history-item');
                        const $group = $item.closest('.history-group');
                        
                        $item.slideUp(250, function() {
                            $(this).remove();
                            // If group is empty, remove group
                            if ($group.find('.history-item').length === 0) {
                                $group.slideUp(200, function() { $(this).remove(); });
                            }
                        });

                        NovelToast.show(novelBookmark.strings.historyDeleted, 'info');
                    }
                }
            });
        },

        clearAll() {
            $.ajax({
                url: novelBookmark.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'novel_clear_history',
                    nonce: novelBookmark.nonce
                },
                success: (res) => {
                    if (res.success) {
                        $('#historyGroups').html('');
                        $('#historyGroups').hide();
                        $('#historyEmpty').show();
                        $('#historyLoadMore').hide();
                        NovelToast.show(novelBookmark.strings.historyCleared, 'success');
                    }
                }
            });
        },

        esc(str) {
            const div = document.createElement('div');
            div.textContent = str || '';
            return div.innerHTML;
        }
    };

    /* ═══════════════════════════════════════
       Scroll Position Save/Restore
       ═══════════════════════════════════════ */

    const ScrollTracker = {
        chapterId: 0,
        saveTimer: null,
        restored: false,

        init() {
            const $body = $('body');
            if (!$body.hasClass('single-chapter') || !novelBookmark.loggedIn) return;

            // Get chapter ID from body class or data attribute
            this.chapterId = parseInt($body.data('chapter-id') || $('[data-chapter-id]').first().data('chapter-id'), 10);
            if (!this.chapterId) return;

            this.restorePosition();
            this.bindSaveEvents();
        },

        restorePosition() {
            $.ajax({
                url: novelBookmark.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'novel_get_scroll_position',
                    nonce: novelBookmark.nonce,
                    chapter_id: this.chapterId
                },
                success: (res) => {
                    if (res.success && res.data.position > 5) {
                        const position = res.data.position;
                        const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
                        const targetY = (position / 100) * maxScroll;

                        setTimeout(() => {
                            window.scrollTo({ top: targetY, behavior: 'smooth' });
                            NovelToast.show(novelBookmark.strings.continueFrom, 'info');
                            this.restored = true;
                        }, 500);
                    }
                }
            });
        },

        bindSaveEvents() {
            const self = this;

            // Save on scroll (debounced)
            $(window).on('scroll', function() {
                clearTimeout(self.saveTimer);
                self.saveTimer = setTimeout(() => self.savePosition(), 2000);
            });

            // Save before leaving
            $(window).on('beforeunload', function() {
                self.savePositionSync();
            });

            // Save on visibility change
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    self.savePositionSync();
                }
            });
        },

        savePosition() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
            const position = maxScroll > 0 ? (scrollTop / maxScroll) * 100 : 0;

            $.ajax({
                url: novelBookmark.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'novel_save_scroll_position',
                    nonce: novelBookmark.nonce,
                    chapter_id: this.chapterId,
                    position: Math.round(position * 100) / 100
                }
            });
        },

        savePositionSync() {
            if (!this.chapterId) return;

            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
            const position = maxScroll > 0 ? (scrollTop / maxScroll) * 100 : 0;

            // Use sendBeacon for reliable save
            if (navigator.sendBeacon) {
                const formData = new FormData();
                formData.append('action', 'novel_save_scroll_position');
                formData.append('nonce', novelBookmark.nonce);
                formData.append('chapter_id', this.chapterId);
                formData.append('position', Math.round(position * 100) / 100);
                navigator.sendBeacon(novelBookmark.ajaxUrl, formData);
            }
        }
    };

    /* ═══════════════════════════════════════
       Continue Reading Section
       ═══════════════════════════════════════ */

    const ContinueReading = {
        init() {
            const $container = $('#continueReadingSection');
            if (!$container.length || !novelBookmark.loggedIn) return;
            this.load($container);
        },

        load($container) {
            $.ajax({
                url: novelBookmark.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'novel_get_continue_reading',
                    nonce: novelBookmark.nonce,
                    limit: 5
                },
                success: (res) => {
                    if (res.success && res.data.novels.length > 0) {
                        this.render($container, res.data.novels);
                        $container.show();
                    } else {
                        $container.hide();
                    }
                }
            });
        },

        render($container, novels) {
            let html = '<div class="continue-reading-slider">';

            novels.forEach(novel => {
                html += `
                    <div class="continue-card">
                        <a href="${novel.continue_url}" class="continue-card__cover">
                            <img src="${novel.thumb}" alt="${this.esc(novel.title)}" 
                                 loading="lazy" width="80" height="112">
                            <div class="continue-card__progress-ring">
                                <svg viewBox="0 0 36 36">
                                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                          fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="3"/>
                                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                          fill="none" stroke="#8b5cf6" stroke-width="3"
                                          stroke-dasharray="${novel.progress}, 100"/>
                                </svg>
                                <span>${Math.round(novel.progress)}%</span>
                            </div>
                        </a>
                        <div class="continue-card__info">
                            <h4 class="continue-card__title">${this.esc(novel.title)}</h4>
                            <span class="continue-card__chapter">قسمت ${novel.last_chapter}/${novel.total}</span>
                            <a href="${novel.continue_url}" class="continue-card__btn">▶ ادامه</a>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            $container.find('.section-content').html(html);
        },

        esc(str) {
            const div = document.createElement('div');
            div.textContent = str || '';
            return div.innerHTML;
        }
    };

    /* ═══════════════════════════════════════
       Toast Helper (if not globally available)
       ═══════════════════════════════════════ */

    if (typeof window.NovelToast === 'undefined') {
        window.NovelToast = {
            show(message, type) {
                const colors = { success: '#10b981', error: '#ef4444', warning: '#f59e0b', info: '#3b82f6' };
                const bg = colors[type] || colors.info;
                
                const $toast = $(`<div class="novel-toast-msg">${message}</div>`).css({
                    position: 'fixed', bottom: '2rem', left: '50%',
                    transform: 'translateX(-50%) translateY(20px)',
                    padding: '0.75rem 1.5rem', borderRadius: '12px',
                    color: '#fff', fontSize: '0.9rem', fontWeight: '500',
                    zIndex: 99999, opacity: 0, background: bg,
                    boxShadow: '0 4px 20px rgba(0,0,0,0.2)',
                    fontFamily: "'Vazirmatn', sans-serif",
                    maxWidth: '90%', textAlign: 'center',
                    transition: 'all 0.3s ease'
                }).appendTo('body');

                requestAnimationFrame(() => {
                    $toast.css({ opacity: 1, transform: 'translateX(-50%) translateY(0)' });
                });
                setTimeout(() => {
                    $toast.css({ opacity: 0, transform: 'translateX(-50%) translateY(20px)' });
                    setTimeout(() => $toast.remove(), 300);
                }, 3500);
            }
        };
    }

    /* ═══════════════════════════════════════
       Init
       ═══════════════════════════════════════ */

    $(document).ready(function() {
        LibraryDropdown.init();
        DashboardLibrary.init();
        DashboardHistory.init();
        ScrollTracker.init();
        ContinueReading.init();
    });

    // Expose
    window.NovelLibrary = LibraryDropdown;

})(jQuery);