/**
 * Ratings & Votes JavaScript
 * 
 * Handles:
 * - Star rating (hover, click, AJAX)
 * - Rating distribution popup
 * - Chapter like/dislike voting
 * - Animations (burst, pulse, countUp)
 * 
 * @package suspended-starter
 * @since 3.0.0
 */

(function() {
    'use strict';

    if (typeof novelRatings === 'undefined') return;

    var ajax = novelRatings.ajaxUrl;
    var nonce = novelRatings.nonce;
    var strings = novelRatings.strings;

    // ═══════════════════════════════════════════
    // STAR RATING
    // ═══════════════════════════════════════════

    document.querySelectorAll('.novel-star-rating.interactive').forEach(function(widget) {
        var stars = widget.querySelectorAll('.star-btn');
        var novelId = widget.dataset.novelId;
        var currentRating = parseFloat(widget.dataset.rating) || 0;
        var userRating = parseInt(widget.dataset.userRating) || 0;

        // Hover: progressive highlight
        stars.forEach(function(star, index) {
            star.addEventListener('mouseenter', function() {
                highlightStars(stars, index + 1);
            });

            star.addEventListener('mouseleave', function() {
                highlightStars(stars, userRating || currentRating);
            });

            // Click: submit rating
            star.addEventListener('click', function(e) {
                e.preventDefault();

                if (!novelRatings.isLoggedIn) {
                    if (typeof novelToast === 'function') {
                        novelToast(strings.loginRequired, 'warning');
                    }
                    window.location.href = novelRatings.loginUrl;
                    return;
                }

                var value = parseInt(this.dataset.value);
                submitStarRating(widget, stars, novelId, value);
            });
        });

        // Reset on container leave
        widget.querySelector('.stars-container').addEventListener('mouseleave', function() {
            highlightStars(stars, userRating || currentRating);
        });
    });

    /**
     * Highlight stars progressively
     */
    function highlightStars(stars, rating) {
        stars.forEach(function(star, i) {
            var svg = star.querySelector('.star-svg');
            if (i + 1 <= Math.floor(rating)) {
                svg.style.fill = '#F1C40F';
                star.classList.add('star-hover');
                star.classList.remove('star-hover-empty');
            } else if (i + 0.5 < rating) {
                svg.style.fill = '#F1C40F';
                svg.style.opacity = '0.6';
                star.classList.add('star-hover');
            } else {
                svg.style.fill = '#D1D5DB';
                svg.style.opacity = '1';
                star.classList.remove('star-hover');
                star.classList.add('star-hover-empty');
            }
        });
    }

    /**
     * Submit star rating via AJAX
     */
    function submitStarRating(widget, stars, novelId, value) {
        // Disable during request
        stars.forEach(function(s) { s.disabled = true; });

        fetch(ajax, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=novel_rate_novel&novel_id=' + novelId +
                  '&rating=' + value + '&nonce=' + nonce
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                var d = data.data;

                // Update widget data
                widget.dataset.rating = d.average;
                widget.dataset.userRating = d.user_rating;
                widget.dataset.count = d.count;

                // Update display
                var numEl = widget.querySelector('.rating-number');
                var countEl = widget.querySelector('.rating-count');
                if (numEl) numEl.textContent = parseFloat(d.average).toFixed(1);
                if (countEl) countEl.textContent = '(' + d.count + ' رأی)';

                // Update or add user badge
                var badge = widget.querySelector('.user-rating-badge');
                if (badge) {
                    badge.textContent = 'امتیاز شما: ' + d.user_rating;
                } else {
                    var span = document.createElement('span');
                    span.className = 'user-rating-badge';
                    span.textContent = 'امتیاز شما: ' + d.user_rating;
                    widget.appendChild(span);
                }

                // Highlight with user's rating
                highlightStars(stars, d.user_rating);

                // Burst animation on selected star
                stars[value - 1].classList.add('star-burst');
                setTimeout(function() {
                    stars[value - 1].classList.remove('star-burst');
                }, 400);

                // Toast
                if (typeof novelToast === 'function') {
                    var starText = strings.stars[d.user_rating] || '';
                    novelToast(
                        d.is_update ? strings.rateUpdated : strings.rateSuccess +
                        ': ' + starText,
                        'success'
                    );
                }

                // Update distribution if visible
                var distPopup = document.querySelector('.rating-dist-popup[data-novel-id="' + novelId + '"]');
                if (distPopup && distPopup.classList.contains('show')) {
                    updateDistribution(distPopup, d.distribution, d.count);
                }
            } else {
                if (typeof novelToast === 'function') {
                    novelToast(data.data.message || strings.error, 'error');
                }
            }
        })
        .catch(function() {
            if (typeof novelToast === 'function') {
                novelToast(strings.error, 'error');
            }
        })
        .finally(function() {
            stars.forEach(function(s) { s.disabled = false; });
        });
    }

    // ═══════════════════════════════════════════
    // RATING DISTRIBUTION POPUP
    // ═══════════════════════════════════════════

    document.querySelectorAll('.btn-show-distribution').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            var novelId = this.dataset.novelId;
            var existing = document.querySelector('.rating-dist-popup[data-novel-id="' + novelId + '"]');

            if (existing) {
                existing.classList.toggle('show');
                return;
            }

            // Create popup
            var popup = document.createElement('div');
            popup.className = 'rating-distribution rating-dist-popup';
            popup.dataset.novelId = novelId;
            popup.innerHTML = '<div style="text-align:center;padding:12px;color:#9ca3af;">بارگذاری...</div>';

            // Position relative to widget
            var widget = this.closest('.novel-star-rating');
            widget.style.position = 'relative';
            widget.appendChild(popup);

            // Fetch data
            fetch(ajax, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=novel_get_rating_distribution&novel_id=' + novelId + '&nonce=' + nonce
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    renderDistribution(popup, data.data.distribution, data.data.count);
                    popup.classList.add('show');
                }
            });
        });
    });

    /**
     * Render distribution bars in popup
     */
    function renderDistribution(popup, dist, total) {
        var html = '';
        for (var i = 5; i >= 1; i--) {
            var count = dist[i] || 0;
            var percent = total > 0 ? Math.round((count / total) * 100) : 0;
            var starStr = '★'.repeat(i) + '☆'.repeat(5 - i);

            html += '<div class="dist-row">' +
                '<span class="dist-stars">' + starStr + '</span>' +
                '<span class="dist-label">' + i + '</span>' +
                '<div class="dist-bar-wrap"><div class="dist-bar" style="width:0%;" data-target="' + percent + '"></div></div>' +
                '<span class="dist-count">' + count + ' رأی</span>' +
                '</div>';
        }
        popup.innerHTML = html;

        // Animate bars
        setTimeout(function() {
            popup.querySelectorAll('.dist-bar').forEach(function(bar) {
                bar.style.width = bar.dataset.target + '%';
            });
        }, 50);
    }

    /**
     * Update existing distribution
     */
    function updateDistribution(popup, dist, total) {
        var rows = popup.querySelectorAll('.dist-row');
        var rating = 5;
        rows.forEach(function(row) {
            var count = dist[rating] || 0;
            var percent = total > 0 ? Math.round((count / total) * 100) : 0;
            row.querySelector('.dist-bar').style.width = percent + '%';
            row.querySelector('.dist-count').textContent = count + ' رأی';
            rating--;
        });
    }

    // Close popup on outside click
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.rating-dist-popup') && !e.target.closest('.btn-show-distribution')) {
            document.querySelectorAll('.rating-dist-popup.show').forEach(function(p) {
                p.classList.remove('show');
            });
        }
    });

    // ═══════════════════════════════════════════
    // CHAPTER VOTES (LIKE/DISLIKE)
    // ═══════════════════════════════════════════

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.vote-btn');
        if (!btn) return;

        e.preventDefault();

        if (!novelRatings.isLoggedIn) {
            if (typeof novelToast === 'function') {
                novelToast(strings.loginRequired, 'warning');
            }
            return;
        }

        var voteType = btn.dataset.type;
        var chapterId = btn.dataset.chapter;

        if (!voteType || !chapterId) return;

        // Prevent double-click
        if (btn.disabled) return;
        btn.disabled = true;

        fetch(ajax, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=novel_vote_chapter&chapter_id=' + chapterId +
                  '&vote_type=' + voteType + '&nonce=' + nonce
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                var d = data.data;

                // Update ALL vote buttons for this chapter (top + bottom)
                document.querySelectorAll('.chapter-votes[data-chapter-id="' + chapterId + '"]').forEach(function(container) {
                    updateVoteUI(container, d);
                });

                // Also update buttons matched by data-chapter attribute
                document.querySelectorAll('.vote-btn[data-chapter="' + chapterId + '"]').forEach(function(b) {
                    b.classList.remove('active');
                    var countEl = b.querySelector('.vote-count');
                    if (b.dataset.type === 'like') {
                        countEl.textContent = d.likes;
                    } else {
                        countEl.textContent = d.dislikes;
                    }

                    if (d.user_vote && b.dataset.type === d.user_vote) {
                        b.classList.add('active');
                    }
                });

                // Pulse animation
                btn.classList.add('pulse');
                setTimeout(function() { btn.classList.remove('pulse'); }, 300);

                // Count animation
                var countEl = btn.querySelector('.vote-count');
                if (countEl) {
                    countEl.classList.add('updating');
                    setTimeout(function() { countEl.classList.remove('updating'); }, 300);
                }

                if (typeof novelToast === 'function') {
                    novelToast(
                        d.user_vote ? strings.voteSuccess : strings.voteRemoved,
                        'success'
                    );
                }
            } else {
                if (typeof novelToast === 'function') {
                    novelToast(data.data.message || strings.error, 'error');
                }
            }
        })
        .catch(function() {
            if (typeof novelToast === 'function') {
                novelToast(strings.error, 'error');
            }
        })
        .finally(function() {
            btn.disabled = false;
        });
    });

    /**
     * Update vote UI in a container
     */
    function updateVoteUI(container, data) {
        // Update like button
        var likeBtn = container.querySelector('.vote-like');
        var dislikeBtn = container.querySelector('.vote-dislike');

        if (likeBtn) {
            likeBtn.classList.toggle('active', data.user_vote === 'like');
            var likeCount = likeBtn.querySelector('.vote-count');
            if (likeCount) likeCount.textContent = data.likes;
        }

        if (dislikeBtn) {
            dislikeBtn.classList.toggle('active', data.user_vote === 'dislike');
            var dislikeCount = dislikeBtn.querySelector('.vote-count');
            if (dislikeCount) dislikeCount.textContent = data.dislikes;
        }

        // Update satisfaction bar
        var satBar = container.querySelector('.vote-satisfaction-bar');
        if (satBar) {
            var fill = satBar.querySelector('.satisfaction-fill');
            var text = satBar.querySelector('.satisfaction-text');
            if (fill) {
                fill.style.width = data.satisfaction + '%';
                fill.className = 'satisfaction-fill satisfaction-' + data.sat_class;
            }
            if (text) {
                text.textContent = data.satisfaction + '٪ رضایت';
            }
        } else if (parseInt(data.likes) + parseInt(data.dislikes) > 0) {
            // Create satisfaction bar if first votes
            var barHtml = '<div class="vote-satisfaction-bar">' +
                '<div class="satisfaction-fill satisfaction-' + data.sat_class + '" style="width:' + data.satisfaction + '%;"></div>' +
                '<span class="satisfaction-text">' + data.satisfaction + '٪ رضایت</span>' +
                '</div>';
            container.insertAdjacentHTML('beforeend', barHtml);
        }
    }

})();