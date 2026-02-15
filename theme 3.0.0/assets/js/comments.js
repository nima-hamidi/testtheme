/**
 * Comments System JavaScript
 *
 * Handles: tabs, sorting, submit, vote, reaction, reply, edit, delete,
 * pin, report, spoiler, mention autocomplete, sticker picker,
 * load more, countdown, scroll-to-ref, safe mode
 *
 * @package suspended-starter
 * @since 3.0.0
 */

(function ($) {
    'use strict';

    if (typeof novelComments === 'undefined') return;

    const NC = novelComments;
    const $section = $('.novel-comments');
    if (!$section.length) return;

    // =============================================
    // STATE
    // =============================================

    const State = {
        currentTab: $section.find('.comment-tab.is-active').data('tab') || 'comment',
        currentSort: 'newest',
        currentPage: 1,
        isLoading: false,
        safeMode: localStorage.getItem('novel_spoiler_mode') === 'true',
        mentionQuery: '',
        mentionActive: false,
        mentionIndex: -1,
        editTimers: {},
        stickerInserted: false,
    };

    // =============================================
    // INIT
    // =============================================

    function init() {
        initSafeMode();
        initEditCountdowns();
        bindTabEvents();
        bindSortEvents();
        bindFormEvents();
        bindVoteEvents();
        bindReactionEvents();
        bindReplyEvents();
        bindEditEvents();
        bindDeleteEvents();
        bindPinEvents();
        bindReportEvents();
        bindSpoilerEvents();
        bindMentionEvents();
        bindStickerEvents();
        bindHelpfulEvents();
        bindLoadMore();
        bindRefScroll();
        bindRatingStars();
    }

    // =============================================
    // TABS
    // =============================================

    function bindTabEvents() {
        $(document).on('click', '.comment-tab', function () {
            const tab = $(this).data('tab');
            if (tab === State.currentTab) return;

            State.currentTab = tab;
            State.currentPage = 1;

            // Update UI
            $('.comment-tab').removeClass('is-active').attr('aria-selected', 'false');
            $(this).addClass('is-active').attr('aria-selected', 'true');

            // Update form type
            $('#commentTypeInput').val(tab);
            updateFormForType(tab);

            // Show/hide helpful sort for reviews
            if (tab === 'review') {
                if (!$('.comment-sort__btn[data-sort="helpful"]').length) {
                    $('.comment-sort__options').append(
                        '<button type="button" class="comment-sort__btn" data-sort="helpful">مفیدترین</button>'
                    );
                }
            } else {
                $('.comment-sort__btn[data-sort="helpful"]').remove();
            }

            // Update URL
            const url = new URL(window.location);
            url.searchParams.set('ctab', tab);
            url.searchParams.delete('sort');
            history.replaceState(null, '', url);

            // Reset sort
            State.currentSort = 'newest';
            $('.comment-sort__btn').removeClass('is-active');
            $('.comment-sort__btn[data-sort="newest"]').addClass('is-active');

            loadComments();
        });
    }

    function updateFormForType(type) {
        const $textarea = $('#commentTextarea');
        const $rating = $('#commentFormRating');
        const types = NC.types;
        const config = types[type] || types['comment'];

        switch (type) {
            case 'review':
                $textarea.attr('placeholder', 'نقد خود را با جزئیات بنویسید. حداقل ۲۰۰ کلمه...');
                $textarea.attr('rows', 8);
                $rating.show();
                break;
            case 'theory':
                $textarea.attr('placeholder', 'تئوری و پیش‌بینی خود را بنویسید. حداقل ۲۵۰ کلمه...');
                $textarea.attr('rows', 8);
                $rating.hide();
                break;
            default:
                $textarea.attr('placeholder', 'دیدگاه خود را بنویسید...');
                $textarea.attr('rows', 4);
                $rating.hide();
        }

        updateCounter();
    }

    // =============================================
    // SORTING
    // =============================================

    function bindSortEvents() {
        $(document).on('click', '.comment-sort__btn', function () {
            const sort = $(this).data('sort');
            if (sort === State.currentSort) return;

            State.currentSort = sort;
            State.currentPage = 1;

            $('.comment-sort__btn').removeClass('is-active');
            $(this).addClass('is-active');

            const url = new URL(window.location);
            url.searchParams.set('sort', sort);
            history.replaceState(null, '', url);

            loadComments();
        });
    }

    // =============================================
    // LOAD COMMENTS (AJAX)
    // =============================================

    function loadComments() {
        if (State.isLoading) return;
        State.isLoading = true;

        const $list = $('#commentsList');
        $list.html(getSkeletonHTML());

        $.post(NC.ajaxUrl, {
            action: 'novel_sort_comments',
            nonce: NC.nonce,
            post_id: NC.postId,
            comment_type: State.currentTab,
            sort: State.currentSort,
            page: State.currentPage,
        }, function (res) {
            State.isLoading = false;
            if (res.success) {
                $list.html(res.data.html);
                $list.data('page', State.currentPage);

                // Update load more
                if (res.data.has_more) {
                    $('#commentsLoadMore').show();
                } else {
                    $('#commentsLoadMore').hide();
                }

                initEditCountdowns();
                applySafeMode();
            } else {
                $list.html('<div class="comments-empty"><p>' + NC.i18n.errorGeneric + '</p></div>');
            }
        });
    }

    function getSkeletonHTML() {
        let html = '';
        for (let i = 0; i < 3; i++) {
            html += `<div class="dashboard__skeleton" style="padding:18px 0;border-bottom:1px solid rgba(0,0,0,0.05);">
                <div style="display:flex;gap:10px;margin-bottom:12px;">
                    <div class="skeleton-circle" style="width:40px;height:40px;"></div>
                    <div style="flex:1;"><div class="skeleton-line skeleton-line--short"></div><div class="skeleton-line" style="width:20%;height:10px;"></div></div>
                </div>
                <div class="skeleton-line skeleton-line--long"></div>
                <div class="skeleton-line skeleton-line--medium"></div>
            </div>`;
        }
        return html;
    }

    // =============================================
    // LOAD MORE
    // =============================================

    function bindLoadMore() {
        $(document).on('click', '#loadMoreBtn', function () {
            if (State.isLoading) return;
            State.isLoading = true;

            const $btn = $(this);
            $btn.addClass('is-loading').find('.btn-text').hide();
            $btn.find('.btn-loading').show();

            State.currentPage++;

            $.post(NC.ajaxUrl, {
                action: 'novel_sort_comments',
                nonce: NC.nonce,
                post_id: NC.postId,
                comment_type: State.currentTab,
                sort: State.currentSort,
                page: State.currentPage,
            }, function (res) {
                State.isLoading = false;
                $btn.removeClass('is-loading').find('.btn-text').show();
                $btn.find('.btn-loading').hide();

                if (res.success) {
                    $('#commentsList').append(res.data.html);
                    if (!res.data.has_more) {
                        $('#commentsLoadMore').hide();
                    }
                    initEditCountdowns();
                    applySafeMode();
                }
            });
        });
    }

    // =============================================
    // SUBMIT COMMENT
    // =============================================

    function bindFormEvents() {
        // Counter
        $(document).on('input', '#commentTextarea', function () {
            updateCounter();
            checkMention(this);
        });

        // Spoiler checkbox → form style
        $(document).on('change', '#commentSpoiler', function () {
            $('#commentForm').toggleClass('has-spoiler', this.checked);
        });

        // Submit
        $(document).on('submit', '#commentForm', function (e) {
            e.preventDefault();
            submitComment($(this));
        });
    }

    function updateCounter() {
        const type = State.currentTab;
        const $textarea = $('#commentTextarea');
        const text = $textarea.val();
        const $counter = $('#commentCounter');
        const $text = $('#counterText');
        const config = NC.types[type] || NC.types['comment'];

        let current, max, label, min;

        if (config.unit === 'word') {
            current = countWords(text);
            min = config.min_words;
            max = config.max_chars;
            const charLen = text.length;

            if (current < min) {
                $text.text(current + ' / ' + min + ' کلمه');
                $counter.removeClass('is-valid is-warning').addClass('is-error');
            } else if (charLen > max * 0.8) {
                $text.text(charLen + ' / ' + max + ' کاراکتر');
                $counter.removeClass('is-valid is-error').addClass('is-warning');
            } else {
                $text.text(current + ' کلمه ✓');
                $counter.removeClass('is-error is-warning').addClass('is-valid');
            }
        } else {
            current = text.length;
            min = config.min_chars;
            max = config.max_chars;

            $text.text(current + ' / ' + max + ' کاراکتر');

            if (current < min) {
                $counter.removeClass('is-valid is-warning').addClass('is-error');
            } else if (current > max * 0.8) {
                $counter.removeClass('is-valid is-error').addClass('is-warning');
            } else {
                $counter.removeClass('is-error is-warning').addClass('is-valid');
            }

            // Block typing beyond max
            if (current > max) {
                $textarea.val(text.substring(0, max));
                $text.text(max + ' / ' + max + ' کاراکتر');
                $counter.addClass('is-error');
            }
        }
    }

    function countWords(text) {
        text = text.trim();
        if (!text) return 0;
        return text.split(/\s+/).filter(function (w) { return w.length > 0; }).length;
    }

    function submitComment($form) {
        if (State.isLoading) return;

        if (!NC.isLoggedIn) {
            showToast(NC.i18n.loginRequired, 'error');
            return;
        }

        // Client validation
        const content = $('#commentTextarea').val().trim();
        const type = State.currentTab;
        const config = NC.types[type] || NC.types['comment'];

        if (config.unit === 'word') {
            if (countWords(content) < config.min_words) {
                showFormMessage(sprintf(NC.i18n.minWords, config.min_words), 'error');
                return;
            }
        } else {
            if (content.length < config.min_chars) {
                showFormMessage(sprintf(NC.i18n.minChars, config.min_chars), 'error');
                return;
            }
        }

        // Link check
        const linkCount = (content.match(/https?:\/\/[^\s]+/gi) || []).length;
        if (linkCount > NC.maxLinks) {
            showFormMessage(sprintf(NC.i18n.tooManyLinks, NC.maxLinks), 'error');
            return;
        }

        // Rating check for reviews
        if (type === 'review' && parseInt($('#ratingInput').val()) < 1) {
            showFormMessage('لطفاً امتیاز ستاره‌ای انتخاب کنید.', 'error');
            return;
        }

        State.isLoading = true;
        const $btn = $('#commentSubmitBtn');
        $btn.addClass('is-loading').prop('disabled', true);
        clearFormMessage();

        $.post(NC.ajaxUrl, $form.serialize(), function (res) {
            State.isLoading = false;
            $btn.removeClass('is-loading').prop('disabled', false);

            if (res.success) {
                const parentId = parseInt($('#commentParentId').val());

                if (parentId > 0) {
                    // Append reply
                    const $replies = $('#replies-' + parentId);
                    $replies.show().append(res.data.html);
                    // Hide inline reply form
                    $('#replyForm-' + parentId).slideUp(200);
                } else {
                    // Prepend to list (if newest sort) or append
                    if (State.currentSort === 'newest') {
                        $('#commentsList').prepend(res.data.html);
                    } else {
                        $('#commentsList').append(res.data.html);
                    }
                    // Remove empty state
                    $('#commentsList .comments-empty').remove();
                }

                // Reset form
                $('#commentTextarea').val('');
                $('#commentParentId').val('0');
                $('#commentSpoiler').prop('checked', false).trigger('change');
                $('#ratingInput').val('0');
                $('.star-rating-input__star').removeClass('is-selected is-hovered');
                State.stickerInserted = false;
                updateCounter();

                // Update tab count
                const $tabCount = $(`.comment-tab[data-tab="${type}"] .comment-tab__count`);
                if ($tabCount.length) {
                    const c = parseInt($tabCount.text().replace(/[^\d]/g, '')) || 0;
                    $tabCount.text(c + 1);
                }

                showToast(NC.i18n.sent, 'success');
                initEditCountdowns();

                // Scroll to new comment
                const $newComment = $('#comment-' + res.data.comment_id);
                if ($newComment.length) {
                    $('html, body').animate({
                        scrollTop: $newComment.offset().top - 100
                    }, 500);
                    $newComment.addClass('is-highlighted');
                    setTimeout(() => $newComment.removeClass('is-highlighted'), 3000);
                }
            } else {
                showFormMessage(res.data.message || NC.i18n.errorGeneric, 'error');
            }
        }).fail(function () {
            State.isLoading = false;
            $btn.removeClass('is-loading').prop('disabled', false);
            showFormMessage(NC.i18n.errorGeneric, 'error');
        });
    }

    // =============================================
    // VOTING (LIKE / DISLIKE)
    // =============================================

    function bindVoteEvents() {
        $(document).on('click', '.vote-btn', function () {
            if (!NC.isLoggedIn) {
                showToast(NC.i18n.loginRequired, 'error');
                return;
            }

            const $btn = $(this);
            const commentId = $btn.data('comment-id');
            const vote = parseInt($btn.data('vote'));

            $.post(NC.ajaxUrl, {
                action: 'novel_vote_comment',
                nonce: NC.nonce,
                comment_id: commentId,
                vote: vote,
            }, function (res) {
                if (res.success) {
                    const $parent = $btn.closest('.comment-votes-group');

                    // Update active states
                    $parent.find('.vote-btn').removeClass('is-active');
                    if (res.data.action !== 'removed') {
                        $btn.addClass('is-active');
                    }

                    // Update counts
                    $parent.find('#likeCount-' + commentId).text(res.data.likes || '');
                    $parent.find('#dislikeCount-' + commentId).text(res.data.dislikes || '');

                    // Ripple animation
                    $btn.css('animation', 'none');
                    setTimeout(() => $btn.css('animation', ''), 10);
                } else {
                    showToast(res.data.message, 'error');
                }
            });
        });
    }

    // =============================================
    // REACTIONS
    // =============================================

    function bindReactionEvents() {
        // Toggle picker
        $(document).on('click', '.reaction-toggle', function (e) {
            e.stopPropagation();
            const commentId = $(this).data('comment-id');
            const $picker = $('#reactionPicker-' + commentId);

            // Close all other pickers
            $('.reaction-picker').not($picker).hide();

            $picker.toggle();
        });

        // Close picker on outside click
        $(document).on('click', function () {
            $('.reaction-picker').hide();
        });

        // Select reaction
        $(document).on('click', '.reaction-picker__item', function (e) {
            e.stopPropagation();
            if (!NC.isLoggedIn) {
                showToast(NC.i18n.loginRequired, 'error');
                return;
            }

            const $btn = $(this);
            const commentId = $btn.data('comment-id');
            const reaction = $btn.data('reaction');

            $.post(NC.ajaxUrl, {
                action: 'novel_react_comment',
                nonce: NC.nonce,
                comment_id: commentId,
                reaction: reaction,
            }, function (res) {
                if (res.success) {
                    // Rebuild reactions display
                    const $display = $btn.closest('.comment-item').find('.comment-item__reactions-display');
                    let html = '';

                    Object.keys(res.data.reactions).forEach(function (key) {
                        const r = res.data.reactions[key];
                        const isActive = (res.data.action !== 'removed' && key === reaction) ? 'is-active' : '';
                        html += `<span class="reaction-chip ${isActive}" data-reaction="${key}">${r.emoji} <span class="reaction-chip__count">${r.count}</span></span>`;
                    });

                    if ($display.length) {
                        $display.html(html);
                    } else if (html) {
                        $btn.closest('.comment-item').find('.comment-item__content').after(
                            '<div class="comment-item__reactions-display">' + html + '</div>'
                        );
                    }

                    // Close picker
                    $('#reactionPicker-' + commentId).hide();

                    // Update picker active state
                    $btn.closest('.reaction-picker').find('.reaction-picker__item').removeClass('is-active');
                    if (res.data.action !== 'removed') {
                        $btn.addClass('is-active');
                    }
                }
            });
        });
    }

    // =============================================
    // REPLY
    // =============================================

    function bindReplyEvents() {
        // Show reply form inline
        $(document).on('click', '.reply-btn', function () {
            if (!NC.isLoggedIn) {
                showToast(NC.i18n.loginRequired, 'error');
                return;
            }

            const commentId = $(this).data('comment-id');
            const authorName = $(this).data('author');
            const $wrap = $('#replyForm-' + commentId);

            // Close all other reply forms
            $('.comment-reply-form-wrap').not($wrap).slideUp(200).empty();

            if ($wrap.is(':visible')) {
                $wrap.slideUp(200).empty();
                return;
            }

            // Clone main form concept into inline
            const replyFormHTML = `
                <div class="comment-form comment-form--reply" style="margin-top:12px;">
                    <textarea class="comment-form__textarea reply-textarea" rows="3" placeholder="پاسخ به ${authorName}...">@${authorName} </textarea>
                    <div class="comment-form__footer" style="margin-top:8px;">
                        <button type="button" class="btn btn--primary btn--sm reply-submit-btn" data-parent-id="${commentId}">
                            <span class="btn-text">ارسال پاسخ</span>
                            <span class="btn-loading" style="display:none;">ارسال...</span>
                        </button>
                        <button type="button" class="btn btn--outline btn--sm reply-cancel-btn" data-comment-id="${commentId}">انصراف</button>
                    </div>
                </div>
            `;

            $wrap.html(replyFormHTML).slideDown(300);
            $wrap.find('.reply-textarea').focus();
        });

        // Submit reply
        $(document).on('click', '.reply-submit-btn', function () {
            const $btn = $(this);
            const parentId = $btn.data('parent-id');
            const $textarea = $btn.closest('.comment-form--reply').find('.reply-textarea');
            const content = $textarea.val().trim();

            if (!content) return;

            $btn.addClass('is-loading').prop('disabled', true);

            $.post(NC.ajaxUrl, {
                action: 'novel_submit_comment',
                nonce: NC.nonce,
                post_id: NC.postId,
                parent_id: parentId,
                content: content,
                comment_type: State.currentTab,
                form_timestamp: Math.floor(Date.now() / 1000) - 10,
            }, function (res) {
                $btn.removeClass('is-loading').prop('disabled', false);

                if (res.success) {
                    // Show replies container and append
                    const $replies = $('#replies-' + parentId);
                    $replies.show().append(res.data.html);

                    // Hide reply form
                    $('#replyForm-' + parentId).slideUp(200).empty();

                    // Update reply count
                    const $toggle = $(`.comment-replies-btn[data-parent-id="${parentId}"]`);
                    if ($toggle.length) {
                        const text = $toggle.find('.comment-replies-btn__text').text();
                        const match = text.match(/\d+/);
                        const count = match ? parseInt(match[0]) + 1 : 1;
                        $toggle.find('.comment-replies-btn__text').text('💬 ' + count + ' پاسخ ▼');
                    }

                    showToast(NC.i18n.sent, 'success');
                } else {
                    showToast(res.data.message || NC.i18n.errorGeneric, 'error');
                }
            });
        });

        // Cancel reply
        $(document).on('click', '.reply-cancel-btn', function () {
            const commentId = $(this).data('comment-id');
            $('#replyForm-' + commentId).slideUp(200).empty();
        });

        // Load replies toggle
        $(document).on('click', '.comment-replies-btn', function () {
            const $btn = $(this);
            const parentId = $btn.data('parent-id');
            const $replies = $('#replies-' + parentId);
            const $allLink = $('#repliesAll-' + parentId);

            if ($btn.data('loaded') === 1) {
                $replies.slideToggle(250);
                $allLink.slideToggle(250);
                return;
            }

            $btn.text('در حال بارگذاری...');

            $.post(NC.ajaxUrl, {
                action: 'novel_load_replies',
                nonce: NC.nonce,
                parent_id: parentId,
                page: 1,
            }, function (res) {
                if (res.success) {
                    $replies.html(res.data.html).slideDown(250);
                    $btn.data('loaded', 1);
                    $btn.find('.comment-replies-btn__text').text('💬 ' + res.data.total + ' پاسخ ▲');

                    if (res.data.total > 3) {
                        $allLink.slideDown(250);
                    }

                    applySafeMode();
                }
            });
        });
    }

    // =============================================
    // EDIT
    // =============================================

    function bindEditEvents() {
        $(document).on('click', '.edit-btn', function () {
            const commentId = $(this).data('comment-id');
            const $item = $('#comment-' + commentId);
            const $content = $item.find('.comment-item__text').first();
            const originalText = $content.text().trim();

            // Replace with textarea
            const $textarea = $('<textarea class="comment-form__textarea edit-textarea" rows="3"></textarea>').val(originalText);
            const $actions = $(`
                <div class="edit-actions" style="margin-top:8px;display:flex;gap:8px;">
                    <button type="button" class="btn btn--primary btn--sm edit-save-btn" data-comment-id="${commentId}">
                        <span class="btn-text">ذخیره</span>
                        <span class="btn-loading" style="display:none;">ذخیره...</span>
                    </button>
                    <button type="button" class="btn btn--outline btn--sm edit-cancel-btn" data-comment-id="${commentId}" data-original="${encodeURIComponent(originalText)}">انصراف</button>
                </div>
            `);

            $content.replaceWith($textarea);
            $item.find('.comment-item__content').after($actions);
            $textarea.focus();
        });

        // Save edit
        $(document).on('click', '.edit-save-btn', function () {
            const $btn = $(this);
            const commentId = $btn.data('comment-id');
            const $item = $('#comment-' + commentId);
            const content = $item.find('.edit-textarea').val().trim();

            if (!content) return;

            $btn.addClass('is-loading').prop('disabled', true);

            $.post(NC.ajaxUrl, {
                action: 'novel_edit_comment',
                nonce: NC.nonce,
                comment_id: commentId,
                content: content,
            }, function (res) {
                $btn.removeClass('is-loading').prop('disabled', false);

                if (res.success) {
                    const $textarea = $item.find('.edit-textarea');
                    $textarea.replaceWith('<div class="comment-item__text">' + res.data.content + '</div>');
                    $item.find('.edit-actions').remove();

                    // Add edited label
                    const $meta = $item.find('.comment-item__meta').first();
                    if (!$meta.find('.comment-item__edited').length) {
                        $meta.append('<span class="comment-item__edited">(ویرایش‌شده)</span>');
                    }

                    showToast(NC.i18n.edited, 'success');
                } else {
                    showToast(res.data.message, 'error');
                }
            });
        });

        // Cancel edit
        $(document).on('click', '.edit-cancel-btn', function () {
            const commentId = $(this).data('comment-id');
            const original = decodeURIComponent($(this).data('original'));
            const $item = $('#comment-' + commentId);

            $item.find('.edit-textarea').replaceWith('<div class="comment-item__text">' + original + '</div>');
            $item.find('.edit-actions').remove();
        });
    }

    function initEditCountdowns() {
        $('.edit-countdown').each(function () {
            const $el = $(this);
            let seconds = parseInt($el.data('seconds'));
            if (isNaN(seconds) || seconds <= 0) {
                $el.text('');
                return;
            }

            // Clear existing timer
            const commentId = $el.closest('.comment-item').data('comment-id');
            if (State.editTimers[commentId]) clearInterval(State.editTimers[commentId]);

            function tick() {
                if (seconds <= 0) {
                    clearInterval(State.editTimers[commentId]);
                    $el.text('');
                    $el.closest('.edit-btn').replaceWith(
                        '<span class="comment-action comment-action--disabled" title="زمان ویرایش تمام شده">✏️</span>'
                    );
                    return;
                }
                const m = Math.floor(seconds / 60);
                const s = seconds % 60;
                $el.text('⏱ ' + m + ':' + (s < 10 ? '0' : '') + s);
                seconds--;
            }

            tick();
            State.editTimers[commentId] = setInterval(tick, 1000);
        });
    }

    // =============================================
    // DELETE
    // =============================================

    function bindDeleteEvents() {
        $(document).on('click', '.delete-btn', function () {
            const commentId = $(this).data('comment-id');

            if (!confirm(NC.i18n.confirmDelete)) return;

            $.post(NC.ajaxUrl, {
                action: 'novel_delete_comment',
                nonce: NC.nonce,
                comment_id: commentId,
            }, function (res) {
                if (res.success) {
                    $('#comment-' + commentId).slideUp(300, function () {
                        $(this).remove();
                    });
                    showToast(NC.i18n.deleted, 'success');
                } else {
                    showToast(res.data.message, 'error');
                }
            });
        });
    }

    // =============================================
    // PIN
    // =============================================

    function bindPinEvents() {
        $(document).on('click', '.pin-btn', function () {
            const $btn = $(this);
            const commentId = $btn.data('comment-id');

            $.post(NC.ajaxUrl, {
                action: 'novel_pin_comment',
                nonce: NC.nonce,
                comment_id: commentId,
            }, function (res) {
                if (res.success) {
                    $btn.toggleClass('is-active', res.data.pinned);
                    const $item = $('#comment-' + commentId);
                    $item.toggleClass('is-pinned', res.data.pinned);

                    if (res.data.pinned) {
                        if (!$item.find('.comment-pinned-badge').length) {
                            $item.prepend('<div class="comment-pinned-badge">📌 پین شده</div>');
                        }
                    } else {
                        $item.find('.comment-pinned-badge').remove();
                    }

                    showToast(res.data.message, 'success');
                } else {
                    showToast(res.data.message, 'error');
                }
            });
        });
    }

    // =============================================
    // REPORT
    // =============================================

    function bindReportEvents() {
        $(document).on('click', '.report-btn', function () {
            const commentId = $(this).data('comment-id');
            $('#reportCommentId').val(commentId);
            $('#reportModal').addClass('is-open');
            $('body').css('overflow', 'hidden');
            // Reset form
            $('input[name="report_reason"]').prop('checked', false);
            $('#reportDetails').val('');
            $('#reportCharCount').text('0');
            $('#reportError').hide();
        });

        // Report details char count
        $(document).on('input', '#reportDetails', function () {
            $('#reportCharCount').text($(this).val().length);
        });

        // Submit report
        $(document).on('click', '#submitReportBtn', function () {
            const commentId = $('#reportCommentId').val();
            const reason = $('input[name="report_reason"]:checked').val();
            const details = $('#reportDetails').val();
            const $btn = $(this);

            if (!reason) {
                $('#reportError').text('لطفاً دلیل گزارش را انتخاب کنید.').show();
                return;
            }

            $btn.addClass('is-loading').prop('disabled', true);
            $('#reportError').hide();

            $.post(NC.ajaxUrl, {
                action: 'novel_report_comment',
                nonce: NC.nonce,
                comment_id: commentId,
                reason: reason,
                details: details,
            }, function (res) {
                $btn.removeClass('is-loading').prop('disabled', false);

                if (res.success) {
                    $('#reportModal').removeClass('is-open');
                    $('body').css('overflow', '');
                    showToast(NC.i18n.reportSent, 'success');
                } else {
                    $('#reportError').text(res.data.message).show();
                }
            });
        });

        // Close modal
        $(document).on('click', '.report-modal .modal__cancel, .report-modal', function (e) {
            if (e.target === this || $(this).hasClass('modal__cancel')) {
                $('#reportModal').removeClass('is-open');
                $('body').css('overflow', '');
            }
        });

        $(document).on('click', '.report-modal .modal', function (e) {
            e.stopPropagation();
        });
    }

    // =============================================
    // SPOILER
    // =============================================

    function bindSpoilerEvents() {
        // Click on blurred content
        $(document).on('click', '.comment-spoiler-overlay', function () {
            const commentId = $(this).data('comment-id');
            // Show spoiler confirmation modal
            $('#spoilerModal').addClass('is-open').data('comment-id', commentId);
        });

        // Confirm show
        $(document).on('click', '.spoiler-modal__show', function () {
            const commentId = $('#spoilerModal').data('comment-id');
            const $content = $('#commentContent-' + commentId);
            $content.removeClass('is-blurred');
            $content.find('.comment-spoiler-overlay').remove();
            $('#spoilerModal').removeClass('is-open');
        });

        // Cancel
        $(document).on('click', '.spoiler-modal__hide', function () {
            $('#spoilerModal').removeClass('is-open');
        });

        // Close modal
        $(document).on('click', '.spoiler-modal', function (e) {
            if (e.target === this) $(this).removeClass('is-open');
        });

        // Inline spoiler tags
        $(document).on('click', '.spoiler-wrap .spoiler-overlay', function () {
            $(this).closest('.spoiler-wrap').addClass('is-revealed');
        });

        // Safe mode toggle
        $(document).on('click', '#spoilerModeToggle', function () {
            State.safeMode = !State.safeMode;
            localStorage.setItem('novel_spoiler_mode', State.safeMode);
            $(this).attr('aria-pressed', State.safeMode);
            applySafeMode();
            showToast(State.safeMode ? NC.i18n.safeModeOn : NC.i18n.safeModeOff, 'success');
        });
    }

    function initSafeMode() {
        if (State.safeMode) {
            $('#spoilerModeToggle').attr('aria-pressed', 'true');
        }
        applySafeMode();
    }

    function applySafeMode() {
        if (State.safeMode) {
            $('.comment-item.is-spoiler').hide();
        } else {
            $('.comment-item.is-spoiler').show();
        }
    }

    // =============================================
    // MENTION AUTOCOMPLETE
    // =============================================

    function bindMentionEvents() {
        $(document).on('keydown', '#commentTextarea, .reply-textarea', function (e) {
            if (!State.mentionActive) return;
            const $dropdown = $('#mentionDropdown');
            const $items = $dropdown.find('.mention-dropdown__item');

            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    State.mentionIndex = Math.min(State.mentionIndex + 1, $items.length - 1);
                    $items.removeClass('is-active').eq(State.mentionIndex).addClass('is-active');
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    State.mentionIndex = Math.max(State.mentionIndex - 1, 0);
                    $items.removeClass('is-active').eq(State.mentionIndex).addClass('is-active');
                    break;
                case 'Enter':
                    if (State.mentionIndex >= 0) {
                        e.preventDefault();
                        $items.eq(State.mentionIndex).trigger('click');
                    }
                    break;
                case 'Escape':
                    closeMentionDropdown();
                    break;
            }
        });

        $(document).on('click', '.mention-dropdown__item', function () {
            const username = $(this).data('username');
            const $textarea = $(document.activeElement).is('textarea') ? $(document.activeElement) : $('#commentTextarea');
            const text = $textarea.val();

            // Replace @query with @username
            const mentionRegex = /@[\w\u0600-\u06FF]*$/u;
            const newText = text.replace(mentionRegex, '@' + username + ' ');
            $textarea.val(newText).focus();

            closeMentionDropdown();
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('.mention-dropdown').length) {
                closeMentionDropdown();
            }
        });
    }

    function checkMention(textarea) {
        const text = textarea.value;
        const cursorPos = textarea.selectionStart;
        const textBeforeCursor = text.substring(0, cursorPos);

        const mentionMatch = textBeforeCursor.match(/@([\w\u0600-\u06FF]{2,})$/u);

        if (mentionMatch) {
            const query = mentionMatch[1];
            if (query === State.mentionQuery) return;
            State.mentionQuery = query;

            clearTimeout(State.mentionTimer);
            State.mentionTimer = setTimeout(function () {
                searchMentions(query, textarea);
            }, 300);
        } else {
            closeMentionDropdown();
        }
    }

    function searchMentions(query, textarea) {
        $.post(NC.ajaxUrl, {
            action: 'novel_mention_search',
            nonce: NC.nonce,
            query: query,
        }, function (res) {
            if (res.success && res.data.users.length > 0) {
                showMentionDropdown(res.data.users, textarea);
            } else {
                closeMentionDropdown();
            }
        });
    }

    function showMentionDropdown(users, textarea) {
        const $dropdown = $('#mentionDropdown');
        let html = '';

        users.forEach(function (u) {
            html += `<div class="mention-dropdown__item" data-username="${u.username}" data-user-id="${u.id}">
                <img src="${u.avatar_url}" class="mention-dropdown__avatar" width="24" height="24" alt="">
                <span class="mention-dropdown__name">${u.name}</span>
                <span class="mention-dropdown__username">@${u.username}</span>
            </div>`;
        });

        $dropdown.html(html).show();
        State.mentionActive = true;
        State.mentionIndex = -1;

        // Position
        const $ta = $(textarea);
        const offset = $ta.offset();
        $dropdown.css({
            top: offset.top + $ta.outerHeight() + 4,
            right: offset.left,
        });
    }

    function closeMentionDropdown() {
        $('#mentionDropdown').hide().empty();
        State.mentionActive = false;
        State.mentionQuery = '';
        State.mentionIndex = -1;
    }

    // =============================================
    // STICKER PICKER
    // =============================================

    function bindStickerEvents() {
        // Toggle sticker picker
        $(document).on('click', '#stickerToggle', function (e) {
            e.stopPropagation();
            $('#stickerPickerWrap').slideToggle(200);
        });

        // Sticker picker tabs
        $(document).on('click', '.sticker-picker__tab', function () {
            const tab = $(this).data('picker-tab');
            $('.sticker-picker__tab').removeClass('is-active');
            $(this).addClass('is-active');
            $('[data-picker-panel]').hide();
            $(`[data-picker-panel="${tab}"]`).show();
        });

        // Search stickers
        $(document).on('input', '#stickerSearch', function () {
            const query = $(this).val().toLowerCase();
            $('.sticker-picker__item').each(function () {
                const name = $(this).data('name').toLowerCase();
                $(this).toggle(name.includes(query));
            });
        });

        // Select sticker/gif
        $(document).on('click', '.sticker-picker__item', function () {
            if (State.stickerInserted) {
                showToast('حداکثر ۱ استیکر/GIF در هر دیدگاه', 'error');
                return;
            }

            const code = $(this).data('code');
            const $textarea = $('#commentTextarea');
            $textarea.val($textarea.val() + '\n' + code);
            State.stickerInserted = true;

            // Close picker
            $('#stickerPickerWrap').slideUp(200);

            updateCounter();
        });

        // Close on outside click
        $(document).on('click', function (e) {
            if (!$(e.target).closest('#stickerPickerWrap, #stickerToggle').length) {
                $('#stickerPickerWrap').slideUp(200);
            }
        });
    }

    // =============================================
    // HELPFUL VOTES (REVIEW)
    // =============================================

    function bindHelpfulEvents() {
        $(document).on('click', '.comment-helpful__btn', function () {
            if (!NC.isLoggedIn) {
                showToast(NC.i18n.loginRequired, 'error');
                return;
            }

            const $btn = $(this);
            const commentId = $btn.data('comment-id');
            const helpful = parseInt($btn.data('helpful'));

            $.post(NC.ajaxUrl, {
                action: 'novel_helpful_vote',
                nonce: NC.nonce,
                comment_id: commentId,
                helpful: helpful,
            }, function (res) {
                if (res.success) {
                    const $parent = $btn.closest('.comment-item__helpful');
                    $parent.find('.comment-helpful__btn').removeClass('is-active');

                    // Toggle
                    if (helpful === 1) {
                        $btn.addClass('is-active');
                    } else {
                        $parent.find('.comment-helpful__btn--no').addClass('is-active');
                    }

                    $parent.find('.helpful-count-yes').text(res.data.yes);
                    $parent.find('.helpful-count-no').text(res.data.no);
                } else {
                    showToast(res.data.message, 'error');
                }
            });
        });
    }

    // =============================================
    // RATING STARS
    // =============================================

    function bindRatingStars() {
        $(document).on('mouseenter', '.star-rating-input__star', function () {
            const val = parseInt($(this).data('value'));
            $(this).closest('.star-rating-input').find('.star-rating-input__star').each(function () {
                $(this).toggleClass('is-hovered', parseInt($(this).data('value')) <= val);
            });
        });

        $(document).on('mouseleave', '.star-rating-input', function () {
            $(this).find('.star-rating-input__star').removeClass('is-hovered');
        });

        $(document).on('click', '.star-rating-input__star', function () {
            const val = parseInt($(this).data('value'));
            $('#ratingInput').val(val);

            $(this).closest('.star-rating-input').find('.star-rating-input__star').each(function () {
                $(this).toggleClass('is-selected', parseInt($(this).data('value')) <= val);
            });

            const labels = ['', 'ضعیف', 'متوسط', 'خوب', 'عالی', 'شاهکار!'];
            $('#ratingText').text(labels[val] || '');
        });
    }

    // =============================================
    // REF SCROLL (PARENT REFERENCE)
    // =============================================

    function bindRefScroll() {
        $(document).on('click', '.comment-item__ref', function () {
            const targetId = $(this).data('scroll-to');
            const $target = $('#' + targetId);

            if ($target.length) {
                $('html, body').animate({
                    scrollTop: $target.offset().top - 100
                }, 400);
                $target.addClass('is-highlighted');
                setTimeout(function () {
                    $target.removeClass('is-highlighted');
                }, 2500);
            }
        });
    }

    // =============================================
    // UTILITIES
    // =============================================

    function showToast(message, type) {
        if (typeof window.NovelToast !== 'undefined') {
            window.NovelToast.show(message, type);
            return;
        }

        const bg = type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#f59e0b';
        const $toast = $(`<div style="position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(20px);background:${bg};color:#fff;padding:12px 24px;border-radius:12px;font-size:14px;font-weight:600;z-index:99999;opacity:0;transition:all .3s;box-shadow:0 4px 20px rgba(0,0,0,.15);direction:rtl;max-width:90%;text-align:center;">${message}</div>`);

        $('body').append($toast);
        requestAnimationFrame(function () {
            $toast.css({ opacity: 1, transform: 'translateX(-50%) translateY(0)' });
        });
        setTimeout(function () {
            $toast.css({ opacity: 0, transform: 'translateX(-50%) translateY(20px)' });
            setTimeout(function () { $toast.remove(); }, 300);
        }, 3500);
    }

    function showFormMessage(msg, type) {
        const cls = type === 'error' ? 'auth-message--error' : 'auth-message--success';
        const icon = type === 'error' ? '❌' : '✅';
        $('#commentFormMessages').html(
            `<div class="auth-message ${cls}"><span class="auth-message__icon">${icon}</span><span>${msg}</span></div>`
        ).show();
    }

    function clearFormMessage() {
        $('#commentFormMessages').empty().hide();
    }

    function sprintf(str) {
        const args = Array.prototype.slice.call(arguments, 1);
        let i = 0;
        return str.replace(/%[sd]/g, function () {
            return args[i++] !== undefined ? args[i - 1] : '';
        });
    }

    // =============================================
    // INIT ON READY
    // =============================================

    $(document).ready(init);

})(jQuery);