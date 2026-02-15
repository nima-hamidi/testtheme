<?php
/**
 * Unified Rating System
 * 
 * Handles:
 * - Novel star ratings (1-5)
 * - Chapter like/dislike votes
 * - Rating distribution display
 * - Average calculation & caching
 * 
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Novel_Ratings {

    /** @var string */
    private $ratings_table;

    /** @var string */
    private $votes_table;

    /**
     * Initialize
     */
    public function __construct() {
        global $wpdb;
        $this->ratings_table = $wpdb->prefix . 'novel_ratings';
        $this->votes_table   = $wpdb->prefix . 'chapter_votes';

        // Create tables
        add_action('after_switch_theme', [$this, 'create_tables']);
        add_action('init', [$this, 'maybe_create_tables'], 99);

        // AJAX - Star ratings
        add_action('wp_ajax_novel_rate_novel', [$this, 'ajax_rate_novel']);

        // AJAX - Chapter votes
        add_action('wp_ajax_novel_vote_chapter', [$this, 'ajax_vote_chapter']);

        // AJAX - Get rating distribution
        add_action('wp_ajax_novel_get_rating_distribution', [$this, 'ajax_get_distribution']);
        add_action('wp_ajax_nopriv_novel_get_rating_distribution', [$this, 'ajax_get_distribution']);

        // Enqueue
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    // ═══════════════════════════════════════════
    // TABLE CREATION
    // ═══════════════════════════════════════════

    public function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Novel star ratings
        $sql1 = "CREATE TABLE IF NOT EXISTS {$this->ratings_table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            novel_id BIGINT UNSIGNED NOT NULL,
            rating TINYINT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY user_novel (user_id, novel_id),
            INDEX idx_novel (novel_id),
            INDEX idx_rating (novel_id, rating)
        ) {$charset}";
        dbDelta($sql1);

        // Chapter votes
        $sql2 = "CREATE TABLE IF NOT EXISTS {$this->votes_table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            chapter_id BIGINT UNSIGNED NOT NULL,
            vote_type ENUM('like','dislike') NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY user_chapter (user_id, chapter_id),
            INDEX idx_chapter (chapter_id)
        ) {$charset}";
        dbDelta($sql2);
    }

    public function maybe_create_tables() {
        global $wpdb;
        if ($wpdb->get_var("SHOW TABLES LIKE '{$this->ratings_table}'") !== $this->ratings_table) {
            $this->create_tables();
        }
    }

    // ═══════════════════════════════════════════
    // ENQUEUE
    // ═══════════════════════════════════════════

    public function enqueue_assets() {
        if (is_singular('novel') || is_singular('chapter') || is_post_type_archive('novel') ||
            is_tax('genre') || is_tax('novel_tag') || is_tax('novel_status')) {

            wp_enqueue_style(
                'novel-ratings',
                get_template_directory_uri() . '/assets/css/ratings.css',
                ['novel-main-style'],
                NOVEL_VERSION
            );

            wp_enqueue_script(
                'novel-ratings',
                get_template_directory_uri() . '/assets/js/ratings.js',
                ['jquery'],
                NOVEL_VERSION,
                true
            );

            wp_localize_script('novel-ratings', 'novelRatings', [
                'ajaxUrl'    => admin_url('admin-ajax.php'),
                'nonce'      => wp_create_nonce('novel_ratings_action'),
                'isLoggedIn' => is_user_logged_in(),
                'loginUrl'   => wp_login_url(get_permalink()),
                'strings'    => [
                    'loginRequired' => 'برای امتیازدهی وارد شوید',
                    'rateSuccess'   => 'امتیاز شما ثبت شد',
                    'rateUpdated'   => 'امتیاز شما به‌روز شد',
                    'voteSuccess'   => 'رأی شما ثبت شد',
                    'voteRemoved'   => 'رأی شما حذف شد',
                    'error'         => 'خطایی رخ داد',
                    'stars'         => ['', '★☆☆☆☆', '★★☆☆☆', '★★★☆☆', '★★★★☆', '★★★★★'],
                ],
            ]);
        }
    }

    // ═══════════════════════════════════════════
    // NOVEL STAR RATINGS
    // ═══════════════════════════════════════════

    /**
     * Get user's rating for a novel
     */
    public function get_user_rating($user_id, $novel_id) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT rating FROM {$this->ratings_table} WHERE user_id = %d AND novel_id = %d",
            $user_id, $novel_id
        ));
    }

    /**
     * Get rating distribution for a novel
     * Returns: [1 => count, 2 => count, ..., 5 => count]
     */
    public function get_distribution($novel_id) {
        global $wpdb;

        $cache_key = 'novel_rating_dist_' . $novel_id;
        $cached = wp_cache_get($cache_key, 'novel');
        if (false !== $cached) {
            return $cached;
        }

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT rating, COUNT(*) as cnt FROM {$this->ratings_table}
             WHERE novel_id = %d GROUP BY rating ORDER BY rating DESC",
            $novel_id
        ));

        $dist = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($results as $row) {
            if (isset($dist[(int)$row->rating])) {
                $dist[(int)$row->rating] = (int) $row->cnt;
            }
        }

        wp_cache_set($cache_key, $dist, 'novel', 3600);
        return $dist;
    }

    /**
     * Recalculate and cache average rating
     */
    public function recalculate_average($novel_id) {
        global $wpdb;

        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as cnt, COALESCE(AVG(rating), 0) as avg_r, COALESCE(SUM(rating), 0) as total_r
             FROM {$this->ratings_table} WHERE novel_id = %d",
            $novel_id
        ));

        $count   = (int) $stats->cnt;
        $average = round((float) $stats->avg_r, 1);
        $total   = (int) $stats->total_r;

        update_post_meta($novel_id, 'novel_rating_count', $count);
        update_post_meta($novel_id, 'novel_avg_rating', $average);
        update_post_meta($novel_id, 'novel_rating_total', $total);

        // Invalidate cache
        wp_cache_delete('novel_rating_dist_' . $novel_id, 'novel');

        return [
            'count'   => $count,
            'average' => $average,
            'total'   => $total,
        ];
    }

    /**
     * AJAX: Rate a novel
     */
    public function ajax_rate_novel() {
        check_ajax_referer('novel_ratings_action', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'ابتدا وارد شوید']);
        }

        $novel_id = absint($_POST['novel_id'] ?? 0);
        $rating   = absint($_POST['rating'] ?? 0);
        $user_id  = get_current_user_id();

        if (!$novel_id || $rating < 1 || $rating > 5) {
            wp_send_json_error(['message' => 'داده نامعتبر']);
        }

        // Check novel exists
        $novel = get_post($novel_id);
        if (!$novel || $novel->post_type !== 'novel') {
            wp_send_json_error(['message' => 'رمان یافت نشد']);
        }

        global $wpdb;

        // Check existing
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->ratings_table} WHERE user_id = %d AND novel_id = %d",
            $user_id, $novel_id
        ));

        $is_update = false;

        if ($existing) {
            $wpdb->update(
                $this->ratings_table,
                ['rating' => $rating, 'updated_at' => current_time('mysql')],
                ['id' => $existing]
            );
            $is_update = true;
        } else {
            $wpdb->insert($this->ratings_table, [
                'user_id'    => $user_id,
                'novel_id'   => $novel_id,
                'rating'     => $rating,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ]);
        }

        // Recalculate
        $stats = $this->recalculate_average($novel_id);
        $dist  = $this->get_distribution($novel_id);

        wp_send_json_success([
            'is_update'    => $is_update,
            'user_rating'  => $rating,
            'average'      => $stats['average'],
            'count'        => $stats['count'],
            'distribution' => $dist,
            'message'      => $is_update ? 'امتیاز به‌روز شد' : 'امتیاز ثبت شد',
        ]);
    }

    /**
     * AJAX: Get rating distribution
     */
    public function ajax_get_distribution() {
        $novel_id = absint($_POST['novel_id'] ?? 0);
        if (!$novel_id) {
            wp_send_json_error();
        }

        $dist    = $this->get_distribution($novel_id);
        $average = get_post_meta($novel_id, 'novel_avg_rating', true) ?: 0;
        $count   = get_post_meta($novel_id, 'novel_rating_count', true) ?: 0;

        $user_rating = 0;
        if (is_user_logged_in()) {
            $user_rating = $this->get_user_rating(get_current_user_id(), $novel_id);
        }

        wp_send_json_success([
            'distribution' => $dist,
            'average'      => (float) $average,
            'count'        => (int) $count,
            'user_rating'  => $user_rating,
        ]);
    }

    // ═══════════════════════════════════════════
    // CHAPTER VOTES (LIKE/DISLIKE)
    // ═══════════════════════════════════════════

    /**
     * Get chapter vote stats
     */
    public function get_chapter_votes($chapter_id) {
        global $wpdb;

        $cache_key = 'chapter_votes_' . $chapter_id;
        $cached = wp_cache_get($cache_key, 'novel');
        if (false !== $cached) {
            return $cached;
        }

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT vote_type, COUNT(*) as cnt FROM {$this->votes_table}
             WHERE chapter_id = %d GROUP BY vote_type",
            $chapter_id
        ));

        $votes = ['likes' => 0, 'dislikes' => 0];
        foreach ($results as $row) {
            if ($row->vote_type === 'like') $votes['likes'] = (int) $row->cnt;
            if ($row->vote_type === 'dislike') $votes['dislikes'] = (int) $row->cnt;
        }

        $total = $votes['likes'] + $votes['dislikes'];
        $votes['total']        = $total;
        $votes['satisfaction'] = $total > 0 ? round(($votes['likes'] / $total) * 100) : 0;
        $votes['sat_class']    = $votes['satisfaction'] >= 80 ? 'good' : ($votes['satisfaction'] >= 50 ? 'mid' : 'bad');

        wp_cache_set($cache_key, $votes, 'novel', 1800);
        return $votes;
    }

    /**
     * Get user's vote on a chapter
     */
    public function get_user_vote($user_id, $chapter_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT vote_type FROM {$this->votes_table} WHERE user_id = %d AND chapter_id = %d",
            $user_id, $chapter_id
        ));
    }

    /**
     * Update chapter vote meta cache
     */
    private function update_vote_cache($chapter_id) {
        $votes = $this->get_chapter_votes($chapter_id);
        update_post_meta($chapter_id, 'chapter_likes', $votes['likes']);
        update_post_meta($chapter_id, 'chapter_dislikes', $votes['dislikes']);
        wp_cache_delete('chapter_votes_' . $chapter_id, 'novel');
    }

    /**
     * AJAX: Vote on chapter
     */
    public function ajax_vote_chapter() {
        check_ajax_referer('novel_ratings_action', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'ابتدا وارد شوید']);
        }

        $chapter_id = absint($_POST['chapter_id'] ?? 0);
        $vote_type  = sanitize_text_field($_POST['vote_type'] ?? '');
        $user_id    = get_current_user_id();

        if (!$chapter_id || !in_array($vote_type, ['like', 'dislike'])) {
            wp_send_json_error(['message' => 'داده نامعتبر']);
        }

        global $wpdb;

        // Check existing vote
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, vote_type FROM {$this->votes_table} WHERE user_id = %d AND chapter_id = %d",
            $user_id, $chapter_id
        ));

        $new_vote = '';

        if ($existing) {
            if ($existing->vote_type === $vote_type) {
                // Toggle off - remove vote
                $wpdb->delete($this->votes_table, ['id' => $existing->id]);
                $new_vote = '';
            } else {
                // Switch vote
                $wpdb->update(
                    $this->votes_table,
                    ['vote_type' => $vote_type],
                    ['id' => $existing->id]
                );
                $new_vote = $vote_type;
            }
        } else {
            // New vote
            $wpdb->insert($this->votes_table, [
                'user_id'    => $user_id,
                'chapter_id' => $chapter_id,
                'vote_type'  => $vote_type,
                'created_at' => current_time('mysql'),
            ]);
            $new_vote = $vote_type;
        }

        // Update cache
        $this->update_vote_cache($chapter_id);
        $votes = $this->get_chapter_votes($chapter_id);

        wp_send_json_success([
            'likes'        => number_format_i18n($votes['likes']),
            'dislikes'     => number_format_i18n($votes['dislikes']),
            'user_vote'    => $new_vote,
            'satisfaction' => $votes['satisfaction'],
            'sat_class'    => $votes['sat_class'],
        ]);
    }

    // ═══════════════════════════════════════════
    // TEMPLATE HELPERS (Static)
    // ═══════════════════════════════════════════

    /**
     * Render star rating HTML (display only)
     *
     * @param float  $rating   Average rating
     * @param int    $count    Number of votes
     * @param string $size     'lg' | 'md' | 'sm'
     * @param int    $novel_id For interactive rating
     */
    public static function render_stars($rating, $count = 0, $size = 'md', $novel_id = 0) {
        $rating = max(0, min(5, (float) $rating));
        $size_class = 'stars-' . $size;
        $interactive = $novel_id > 0 && is_user_logged_in();
        $user_rating = 0;

        if ($interactive) {
            $instance = new self();
            // Don't reinit hooks - just get data
            global $wpdb;
            $user_rating = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT rating FROM {$wpdb->prefix}novel_ratings WHERE user_id = %d AND novel_id = %d",
                get_current_user_id(), $novel_id
            ));
        }

        ob_start();
        ?>
        <div class="novel-star-rating <?php echo esc_attr($size_class); ?> <?php echo $interactive ? 'interactive' : ''; ?>"
             data-novel-id="<?php echo esc_attr($novel_id); ?>"
             data-rating="<?php echo esc_attr($rating); ?>"
             data-user-rating="<?php echo esc_attr($user_rating); ?>"
             data-count="<?php echo esc_attr($count); ?>">

            <div class="stars-container" role="group" aria-label="امتیاز">
                <?php for ($i = 1; $i <= 5; $i++) : ?>
                    <button type="button"
                            class="star-btn <?php echo self::get_star_class($i, $user_rating ?: $rating); ?>"
                            data-value="<?php echo $i; ?>"
                            <?php echo !$interactive ? 'disabled' : ''; ?>
                            aria-label="<?php echo $i; ?> ستاره"
                            title="<?php echo $i; ?> ستاره">
                        <svg class="star-svg" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </button>
                <?php endfor; ?>
            </div>

            <span class="rating-number"><?php echo number_format($rating, 1); ?></span>
            <span class="rating-count">(<?php echo number_format_i18n($count); ?> رأی)</span>

            <?php if ($user_rating > 0) : ?>
                <span class="user-rating-badge">امتیاز شما: <?php echo $user_rating; ?></span>
            <?php endif; ?>

            <?php if ($novel_id) : ?>
                <button type="button" class="btn-show-distribution" data-novel-id="<?php echo $novel_id; ?>"
                        title="توزیع امتیازها">
                    <svg viewBox="0 0 20 20" width="16" height="16">
                        <path d="M2 16h16v2H2v-2zm0-5h4v4H2v-4zm5-2h4v6H7V9zm5-4h4v10h-4V5z" fill="currentColor"/>
                    </svg>
                </button>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get star CSS class
     */
    private static function get_star_class($position, $rating) {
        if ($position <= floor($rating)) {
            return 'star-full';
        } elseif ($position - 0.5 <= $rating) {
            return 'star-half';
        }
        return 'star-empty';
    }

    /**
     * Render rating distribution HTML
     */
    public static function render_distribution($novel_id) {
        $instance = new self();
        $dist     = $instance->get_distribution($novel_id);
        $total    = array_sum($dist);

        ob_start();
        ?>
        <div class="rating-distribution" data-novel-id="<?php echo esc_attr($novel_id); ?>">
            <?php for ($i = 5; $i >= 1; $i--) :
                $count   = $dist[$i];
                $percent = $total > 0 ? round(($count / $total) * 100) : 0;
            ?>
                <div class="dist-row">
                    <span class="dist-stars">
                        <?php echo str_repeat('★', $i) . str_repeat('☆', 5 - $i); ?>
                    </span>
                    <span class="dist-label"><?php echo $i; ?></span>
                    <div class="dist-bar-wrap">
                        <div class="dist-bar" style="width: <?php echo $percent; ?>%;"
                             data-count="<?php echo $count; ?>"></div>
                    </div>
                    <span class="dist-count"><?php echo number_format_i18n($count); ?> رأی</span>
                </div>
            <?php endfor; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render chapter vote buttons HTML
     *
     * @param int    $chapter_id
     * @param string $position   'top' | 'bottom'
     */
    public static function render_chapter_votes($chapter_id, $position = 'top') {
        $instance = new self();
        $votes    = $instance->get_chapter_votes($chapter_id);
        $user_vote = '';

        if (is_user_logged_in()) {
            $user_vote = $instance->get_user_vote(get_current_user_id(), $chapter_id);
        }

        ob_start();
        ?>
        <div class="chapter-votes chapter-votes-<?php echo esc_attr($position); ?>"
             data-chapter-id="<?php echo esc_attr($chapter_id); ?>">

            <button class="vote-btn vote-like <?php echo $user_vote === 'like' ? 'active' : ''; ?>"
                    data-type="like" data-chapter="<?php echo $chapter_id; ?>">
                <svg class="vote-icon" viewBox="0 0 24 24" width="20" height="20">
                    <path d="M1 21h4V9H1v12zm22-11c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L14.17 1 7.59 7.59C7.22 7.95 7 8.45 7 9v10c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-2z" fill="currentColor"/>
                </svg>
                <span class="vote-count"><?php echo number_format_i18n($votes['likes']); ?></span>
                <span class="vote-label">پسندیدم</span>
            </button>

            <button class="vote-btn vote-dislike <?php echo $user_vote === 'dislike' ? 'active' : ''; ?>"
                    data-type="dislike" data-chapter="<?php echo $chapter_id; ?>">
                <svg class="vote-icon" viewBox="0 0 24 24" width="20" height="20">
                    <path d="M15 3H6c-.83 0-1.54.5-1.84 1.22l-3.02 7.05c-.09.23-.14.47-.14.73v2c0 1.1.9 2 2 2h6.31l-.95 4.57-.03.32c0 .41.17.79.44 1.06L9.83 23l6.59-6.59c.36-.36.58-.86.58-1.41V5c0-1.1-.9-2-2-2zm4 0v12h4V3h-4z" fill="currentColor"/>
                </svg>
                <span class="vote-count"><?php echo number_format_i18n($votes['dislikes']); ?></span>
                <span class="vote-label">نپسندیدم</span>
            </button>

            <?php if ($votes['total'] > 0) : ?>
                <div class="vote-satisfaction-bar">
                    <div class="satisfaction-fill satisfaction-<?php echo $votes['sat_class']; ?>"
                         style="width: <?php echo $votes['satisfaction']; ?>%;">
                    </div>
                    <span class="satisfaction-text">
                        <?php echo $votes['satisfaction']; ?>٪ رضایت
                    </span>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render mini like count for chapter list
     */
    public static function render_mini_likes($chapter_id) {
        $likes = get_post_meta($chapter_id, 'chapter_likes', true) ?: 0;
        $dislikes = get_post_meta($chapter_id, 'chapter_dislikes', true) ?: 0;
        $total = $likes + $dislikes;
        $sat = $total > 0 ? round(($likes / $total) * 100) : 0;

        $color_class = '';
        if ($total > 0) {
            $color_class = $sat >= 80 ? 'like-good' : ($sat >= 50 ? 'like-mid' : 'like-bad');
        }

        return '<span class="mini-likes ' . esc_attr($color_class) . '" title="' . $sat . '٪ رضایت">'
               . '👍 ' . number_format_i18n($likes)
               . '</span>';
    }
}