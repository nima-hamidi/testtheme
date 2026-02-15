<?php
/**
 * Novel Comments System
 *
 * Three-tab comment system: Comment, Review, Theory
 * Includes: voting, reactions, mentions, spoilers, pinning,
 * anti-spam, bad-word filter, sticker support, sorting, pagination.
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

class Novel_Comments {

    private static $instance = null;

    // Rate limits
    const RATE_SECONDS       = 30;   // 1 comment per X seconds
    const RATE_HOURLY        = 20;   // per hour
    const RATE_DAILY         = 50;   // per day
    const EDIT_WINDOW        = 15;   // minutes
    const MAX_PINS           = 3;    // per post
    const MAX_LINKS          = 2;    // per comment
    const MIN_FORM_TIME      = 3;    // seconds (anti-bot)
    const SIMILARITY_THRESH  = 90;   // percent for duplicate

    // Comment type constraints
    const TYPES = [
        'comment' => ['min_chars' => 10, 'max_chars' => 500, 'unit' => 'char'],
        'review'  => ['min_words' => 200, 'max_chars' => 5000, 'unit' => 'word'],
        'theory'  => ['min_words' => 250, 'max_chars' => 5000, 'unit' => 'word'],
    ];

    // Reactions
    const REACTIONS = ['love', 'shocked', 'sad', 'angry', 'fire'];
    const REACTION_EMOJI = [
        'love'    => '😍',
        'shocked' => '🤯',
        'sad'     => '😢',
        'angry'   => '😡',
        'fire'    => '🔥',
    ];

    // Comment levels
    const LEVELS = [
        0   => ['title' => 'ناشناس',  'icon' => '👤', 'color' => '#6b7280'],
        1   => ['title' => 'تازه‌وارد', 'icon' => '🌱', 'color' => '#9ca3af'],
        11  => ['title' => 'خواننده',  'icon' => '📖', 'color' => '#60a5fa'],
        51  => ['title' => 'منتقد',   'icon' => '🔍', 'color' => '#a78bfa'],
        201 => ['title' => 'استاد',   'icon' => '🏆', 'color' => '#f59e0b'],
    ];

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // ---------- AJAX – logged-in ----------
        $ajax_actions = [
            'novel_submit_comment',
            'novel_vote_comment',
            'novel_react_comment',
            'novel_report_comment',
            'novel_edit_comment',
            'novel_delete_comment',
            'novel_pin_comment',
            'novel_load_replies',
            'novel_sort_comments',
            'novel_mention_search',
            'novel_helpful_vote',
            'novel_toggle_spoiler_mode',
        ];
        foreach ($ajax_actions as $action) {
            add_action("wp_ajax_{$action}", [$this, str_replace('novel_', '', $action)]);
        }

        // Guests can sort and load replies
        add_action('wp_ajax_nopriv_novel_sort_comments', [$this, 'sort_comments']);
        add_action('wp_ajax_nopriv_novel_load_replies', [$this, 'load_replies']);

        // ---------- Filters ----------
        add_filter('pre_comment_approved', [$this, 'filter_comment_approved'], 10, 2);
        add_filter('preprocess_comment', [$this, 'preprocess_comment']);
        add_filter('comment_text', [$this, 'filter_comment_text'], 20, 2);

        // ---------- Actions ----------
        add_action('comment_post', [$this, 'after_comment_post'], 10, 3);
        add_action('delete_comment', [$this, 'after_comment_delete'], 10, 2);

        // Enqueue
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // Rewrite for comment thread page
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'handle_comment_thread']);

        // Shortcode
        add_shortcode('novel_all_comments', [$this, 'shortcode_all_comments']);

        // DB tables on activation
        add_action('after_switch_theme', [$this, 'create_tables']);
    }

    // =============================================
    // DATABASE TABLES
    // =============================================

    public function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Comment votes (like/dislike)
        dbDelta("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}comment_votes (
            id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            comment_id  BIGINT UNSIGNED NOT NULL,
            user_id     BIGINT UNSIGNED NOT NULL,
            vote        TINYINT NOT NULL DEFAULT 0,  -- 1=like, -1=dislike
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_vote (comment_id, user_id),
            KEY idx_comment (comment_id),
            KEY idx_user (user_id)
        ) {$charset};");

        // Comment reactions (emoji)
        dbDelta("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}comment_reactions (
            id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            comment_id  BIGINT UNSIGNED NOT NULL,
            user_id     BIGINT UNSIGNED NOT NULL,
            reaction    VARCHAR(20) NOT NULL,
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_reaction (comment_id, user_id),
            KEY idx_comment (comment_id)
        ) {$charset};");

        // Review helpfulness (separate from votes)
        dbDelta("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}review_helpfulness (
            id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            comment_id  BIGINT UNSIGNED NOT NULL,
            user_id     BIGINT UNSIGNED NOT NULL,
            helpful     TINYINT NOT NULL DEFAULT 0,  -- 1=yes, -1=no
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_helpful (comment_id, user_id),
            KEY idx_comment (comment_id)
        ) {$charset};");

        // Reports
        dbDelta("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}comment_reports (
            id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            comment_id  BIGINT UNSIGNED NOT NULL,
            user_id     BIGINT UNSIGNED NOT NULL,
            reason      VARCHAR(50) NOT NULL,
            details     TEXT,
            status      VARCHAR(20) DEFAULT 'pending',
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_report (comment_id, user_id),
            KEY idx_status (status)
        ) {$charset};");
    }

    // =============================================
    // ENQUEUE
    // =============================================

    public function enqueue_assets() {
        if (!is_singular(['novel', 'chapter', 'post'])) {
            // Also load on pages with shortcode
            global $post;
            if ($post && !has_shortcode($post->post_content, 'novel_all_comments')) {
                return;
            }
        }

        wp_enqueue_style(
            'novel-comments',
            get_template_directory_uri() . '/assets/css/comments.css',
            ['novel-main-style'],
            SUSPENDED_STARTER_VERSION
        );

        wp_enqueue_script(
            'novel-comments',
            get_template_directory_uri() . '/assets/js/comments.js',
            ['jquery'],
            SUSPENDED_STARTER_VERSION,
            true
        );

        wp_localize_script('novel-comments', 'novelComments', [
            'ajaxUrl'      => admin_url('admin-ajax.php'),
            'nonce'        => wp_create_nonce('novel_comments_nonce'),
            'postId'       => get_the_ID(),
            'postType'     => get_post_type(),
            'isLoggedIn'   => is_user_logged_in(),
            'userId'       => get_current_user_id(),
            'loginUrl'     => home_url('/login/'),
            'editWindow'   => self::EDIT_WINDOW,
            'maxLinks'     => self::MAX_LINKS,
            'types'        => self::TYPES,
            'reactions'    => self::REACTION_EMOJI,
            'i18n'         => [
                'sending'          => 'در حال ارسال...',
                'sent'             => 'دیدگاه ارسال شد ✓',
                'loginRequired'    => 'برای این کار باید وارد شوید.',
                'verifyRequired'   => 'ابتدا ایمیل خود را تأیید کنید.',
                'tooFast'          => 'لطفاً کمی صبر کنید...',
                'duplicate'        => 'این دیدگاه قبلاً ارسال شده.',
                'minChars'         => 'حداقل %d کاراکتر لازم است.',
                'maxChars'         => 'حداکثر %d کاراکتر مجاز است.',
                'minWords'         => 'حداقل %d کلمه لازم است.',
                'tooManyLinks'     => 'حداکثر %d لینک مجاز است.',
                'reportSent'       => 'گزارش ثبت شد و بررسی خواهد شد ✓',
                'alreadyReported'  => 'شما قبلاً این دیدگاه را گزارش کرده‌اید.',
                'edited'           => 'دیدگاه ویرایش شد ✓',
                'editExpired'      => 'زمان ویرایش تمام شده.',
                'deleted'          => 'دیدگاه حذف شد.',
                'pinned'           => 'دیدگاه پین شد 📌',
                'unpinned'         => 'پین برداشته شد.',
                'maxPins'          => 'حداکثر %d پین مجاز است.',
                'spoilerTitle'     => 'هشدار اسپویلر!',
                'spoilerShow'      => 'می‌خوام ببینم 👀',
                'spoilerHide'      => 'نه، بیخیال ✋',
                'safeModeOn'       => 'حالت امن فعال شد 🛡️',
                'safeModeOff'      => 'حالت امن غیرفعال شد',
                'loadMore'         => 'بارگذاری بیشتر',
                'loading'          => 'در حال بارگذاری...',
                'noComments'       => 'هنوز دیدگاهی نوشته نشده. اولین نفر باشید! 🖋️',
                'errorGeneric'     => 'خطایی رخ داد. لطفاً دوباره تلاش کنید.',
                'charCount'        => '%d / %d کاراکتر',
                'wordCount'        => '%d / %d کلمه',
                'confirmDelete'    => 'آیا از حذف این دیدگاه مطمئنید؟',
                'viewAllReplies'   => 'مشاهده همه %d پاسخ →',
                'replies'          => '%d پاسخ',
                'repliesExpand'    => '💬 %d پاسخ ▼',
                'helpful'          => 'این نقد مفید بود؟',
                'helpfulYes'       => '👍 بله (%d)',
                'helpfulNo'        => '👎 خیر (%d)',
            ],
        ]);
    }

    // =============================================
    // REWRITE RULES
    // =============================================

    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^comment-thread/(\d+)/?$',
            'index.php?comment_thread=$matches[1]',
            'top'
        );
    }

    public function add_query_vars($vars) {
        $vars[] = 'comment_thread';
        return $vars;
    }

    public function handle_comment_thread() {
        $thread_id = get_query_var('comment_thread');
        if (empty($thread_id)) return;

        $comment = get_comment((int) $thread_id);
        if (!$comment) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }

        include get_template_directory() . '/templates/comments/comment-thread.php';
        exit;
    }

    // =============================================
    // SUBMIT COMMENT (AJAX)
    // =============================================

    public function submit_comment() {
        $this->verify_nonce();
        $this->require_login_and_verified();

        $user_id  = get_current_user_id();
        $user     = get_userdata($user_id);
        $post_id  = intval($_POST['post_id'] ?? 0);
        $parent   = intval($_POST['parent_id'] ?? 0);
        $content  = isset($_POST['content']) ? trim($_POST['content']) : '';
        $type     = sanitize_text_field($_POST['comment_type'] ?? 'comment');
        $spoiler  = !empty($_POST['is_spoiler']);
        $rating   = intval($_POST['rating'] ?? 0);

        // Honeypot
        if (!empty($_POST['website_url_hp'])) {
            wp_send_json_success(['message' => 'دیدگاه ارسال شد ✓']);
            return;
        }

        // Min form time (anti-bot)
        $form_start = intval($_POST['form_timestamp'] ?? 0);
        if ($form_start > 0 && (time() - $form_start) < self::MIN_FORM_TIME) {
            wp_send_json_error(['message' => 'لطفاً کمی صبر کنید...', 'code' => 'too_fast']);
        }

        // Validate post exists
        $post = get_post($post_id);
        if (!$post || !in_array($post->post_type, ['novel', 'chapter', 'post'])) {
            wp_send_json_error(['message' => 'پست نامعتبر.']);
        }

        // Validate comment type
        if (!array_key_exists($type, self::TYPES)) {
            $type = 'comment';
        }

        // Review only on novels
        if ($type === 'review' && $post->post_type !== 'novel') {
            wp_send_json_error(['message' => 'نقد فقط برای رمان‌ها مجاز است.']);
        }

        // One review per user per novel
        if ($type === 'review') {
            $existing = $this->get_user_review($user_id, $post_id);
            if ($existing) {
                wp_send_json_error([
                    'message' => 'شما قبلاً برای این رمان نقد نوشته‌اید. می‌توانید نقد قبلی را ویرایش کنید.',
                    'existing_id' => $existing->comment_ID,
                ]);
            }
        }

        // Review requires rating
        if ($type === 'review' && ($rating < 1 || $rating > 5)) {
            wp_send_json_error(['message' => 'امتیاز ستاره‌ای (۱ تا ۵) برای نقد الزامی است.']);
        }

        // Rate limiting
        $rate_error = $this->check_comment_rate_limit($user_id);
        if ($rate_error) {
            wp_send_json_error(['message' => $rate_error, 'code' => 'rate_limited']);
        }

        // Validate content length
        $length_error = $this->validate_content_length($content, $type);
        if ($length_error) {
            wp_send_json_error(['message' => $length_error]);
        }

        // Check link count
        $link_count = preg_match_all('/(https?:\/\/[^\s]+)/i', $content);
        if ($link_count > self::MAX_LINKS) {
            wp_send_json_error(['message' => sprintf('حداکثر %d لینک مجاز است.', self::MAX_LINKS)]);
        }

        // Duplicate check
        $dup_error = $this->check_duplicate($content, $user_id, $post_id);
        if ($dup_error) {
            wp_send_json_error(['message' => $dup_error, 'code' => 'duplicate']);
        }

        // Bad word filter
        $filtered_content = $this->filter_bad_words($content);
        $filter_count = $this->count_filtered_words($content);
        if ($filter_count > 5) {
            wp_send_json_error(['message' => 'دیدگاه شما حاوی کلمات نامناسب زیادی است.']);
        }

        // Process mentions (extract user IDs)
        $mentioned_ids = $this->extract_mentions($filtered_content);

        // Insert comment
        $commentdata = [
            'comment_post_ID'      => $post_id,
            'comment_content'      => wp_kses_post($filtered_content),
            'comment_parent'       => $parent,
            'user_id'              => $user_id,
            'comment_author'       => $user->display_name,
            'comment_author_email' => $user->user_email,
            'comment_author_url'   => '',
            'comment_approved'     => 1,
        ];

        $comment_id = wp_insert_comment($commentdata);

        if (!$comment_id) {
            wp_send_json_error(['message' => 'خطا در ثبت دیدگاه.']);
        }

        // Save meta
        update_comment_meta($comment_id, 'novel_comment_type', $type);
        update_comment_meta($comment_id, 'novel_is_spoiler', $spoiler ? 1 : 0);
        update_comment_meta($comment_id, 'novel_filtered_words_count', $filter_count);

        if ($type === 'review' && $rating >= 1 && $rating <= 5) {
            update_comment_meta($comment_id, 'novel_review_rating', $rating);
        }

        if (!empty($mentioned_ids)) {
            update_comment_meta($comment_id, 'novel_mentioned_users', $mentioned_ids);
        }

        // Record rate limit
        $this->record_comment_time($user_id);

        // Render the new comment HTML
        $comment = get_comment($comment_id);
        ob_start();
        $this->render_single_comment($comment, 0);
        $html = ob_get_clean();

        wp_send_json_success([
            'message'    => 'دیدگاه ارسال شد ✓',
            'comment_id' => $comment_id,
            'html'       => $html,
        ]);
    }

    // =============================================
    // VOTE (LIKE / DISLIKE)
    // =============================================

    public function vote_comment() {
        $this->verify_nonce();
        $this->require_login_and_verified();

        global $wpdb;
        $table      = $wpdb->prefix . 'comment_votes';
        $user_id    = get_current_user_id();
        $comment_id = intval($_POST['comment_id'] ?? 0);
        $vote       = intval($_POST['vote'] ?? 0); // 1 or -1

        if (!in_array($vote, [1, -1]) || !get_comment($comment_id)) {
            wp_send_json_error(['message' => 'درخواست نامعتبر.']);
        }

        // Can't vote on own comment
        $comment = get_comment($comment_id);
        if ((int) $comment->user_id === $user_id) {
            wp_send_json_error(['message' => 'نمی‌توانید به دیدگاه خودتان رأی دهید.']);
        }

        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE comment_id = %d AND user_id = %d",
            $comment_id, $user_id
        ));

        if ($existing) {
            if ((int) $existing->vote === $vote) {
                // Toggle off
                $wpdb->delete($table, ['id' => $existing->id], ['%d']);
                $action = 'removed';
            } else {
                // Change vote
                $wpdb->update($table, ['vote' => $vote], ['id' => $existing->id], ['%d'], ['%d']);
                $action = 'changed';
            }
        } else {
            // New vote
            $wpdb->insert($table, [
                'comment_id' => $comment_id,
                'user_id'    => $user_id,
                'vote'       => $vote,
            ], ['%d', '%d', '%d']);
            $action = 'added';

            // Notification for like
            if ($vote === 1 && (int) $comment->user_id !== $user_id) {
                $this->send_vote_notification($comment, $user_id);
            }
        }

        $counts = $this->get_vote_counts($comment_id);

        wp_send_json_success([
            'action'   => $action,
            'likes'    => $counts['likes'],
            'dislikes' => $counts['dislikes'],
        ]);
    }

    // =============================================
    // REACTION (EMOJI)
    // =============================================

    public function react_comment() {
        $this->verify_nonce();
        $this->require_login_and_verified();

        global $wpdb;
        $table      = $wpdb->prefix . 'comment_reactions';
        $user_id    = get_current_user_id();
        $comment_id = intval($_POST['comment_id'] ?? 0);
        $reaction   = sanitize_text_field($_POST['reaction'] ?? '');

        if (!in_array($reaction, self::REACTIONS) || !get_comment($comment_id)) {
            wp_send_json_error(['message' => 'درخواست نامعتبر.']);
        }

        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE comment_id = %d AND user_id = %d",
            $comment_id, $user_id
        ));

        if ($existing) {
            if ($existing->reaction === $reaction) {
                // Toggle off
                $wpdb->delete($table, ['id' => $existing->id], ['%d']);
                $action = 'removed';
            } else {
                // Change reaction
                $wpdb->update($table, ['reaction' => $reaction], ['id' => $existing->id], ['%s'], ['%d']);
                $action = 'changed';
            }
        } else {
            $wpdb->insert($table, [
                'comment_id' => $comment_id,
                'user_id'    => $user_id,
                'reaction'   => $reaction,
            ], ['%d', '%d', '%s']);
            $action = 'added';
        }

        $counts = $this->get_reaction_counts($comment_id);

        wp_send_json_success([
            'action'    => $action,
            'reactions' => $counts,
        ]);
    }

    // =============================================
    // HELPFUL VOTE (REVIEW ONLY)
    // =============================================

    public function helpful_vote() {
        $this->verify_nonce();
        $this->require_login_and_verified();

        global $wpdb;
        $table      = $wpdb->prefix . 'review_helpfulness';
        $user_id    = get_current_user_id();
        $comment_id = intval($_POST['comment_id'] ?? 0);
        $helpful    = intval($_POST['helpful'] ?? 0); // 1 or -1

        if (!in_array($helpful, [1, -1])) {
            wp_send_json_error(['message' => 'درخواست نامعتبر.']);
        }

        $comment = get_comment($comment_id);
        if (!$comment) {
            wp_send_json_error(['message' => 'دیدگاه یافت نشد.']);
        }

        // Must be a review
        $ctype = get_comment_meta($comment_id, 'novel_comment_type', true);
        if ($ctype !== 'review') {
            wp_send_json_error(['message' => 'فقط برای نقدها قابل استفاده است.']);
        }

        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE comment_id = %d AND user_id = %d",
            $comment_id, $user_id
        ));

        if ($existing) {
            if ((int) $existing->helpful === $helpful) {
                $wpdb->delete($table, ['id' => $existing->id], ['%d']);
            } else {
                $wpdb->update($table, ['helpful' => $helpful], ['id' => $existing->id], ['%d'], ['%d']);
            }
        } else {
            $wpdb->insert($table, [
                'comment_id' => $comment_id,
                'user_id'    => $user_id,
                'helpful'    => $helpful,
            ], ['%d', '%d', '%d']);
        }

        $counts = $this->get_helpful_counts($comment_id);

        wp_send_json_success([
            'yes' => $counts['yes'],
            'no'  => $counts['no'],
        ]);
    }

    // =============================================
    // EDIT COMMENT
    // =============================================

    public function edit_comment() {
        $this->verify_nonce();
        $this->require_login_and_verified();

        $user_id    = get_current_user_id();
        $comment_id = intval($_POST['comment_id'] ?? 0);
        $content    = isset($_POST['content']) ? trim($_POST['content']) : '';

        $comment = get_comment($comment_id);
        if (!$comment || (int) $comment->user_id !== $user_id) {
            wp_send_json_error(['message' => 'شما مجاز به ویرایش این دیدگاه نیستید.']);
        }

        // Check edit window
        $edit_window = (int) get_option('novel_edit_time', self::EDIT_WINDOW);
        $comment_time = strtotime($comment->comment_date_gmt);
        $elapsed = (time() - $comment_time) / 60;

        if ($elapsed > $edit_window) {
            wp_send_json_error(['message' => 'زمان ویرایش تمام شده.', 'code' => 'edit_expired']);
        }

        // Validate length
        $type = get_comment_meta($comment_id, 'novel_comment_type', true) ?: 'comment';
        $length_error = $this->validate_content_length($content, $type);
        if ($length_error) {
            wp_send_json_error(['message' => $length_error]);
        }

        // Filter bad words
        $filtered = $this->filter_bad_words($content);

        wp_update_comment([
            'comment_ID'      => $comment_id,
            'comment_content' => wp_kses_post($filtered),
        ]);

        update_comment_meta($comment_id, 'novel_last_edited', time());

        // Update spoiler flag if changed
        if (isset($_POST['is_spoiler'])) {
            update_comment_meta($comment_id, 'novel_is_spoiler', !empty($_POST['is_spoiler']) ? 1 : 0);
        }

        wp_send_json_success([
            'message' => 'دیدگاه ویرایش شد ✓',
            'content' => apply_filters('comment_text', $filtered, $comment),
        ]);
    }

    // =============================================
    // DELETE COMMENT
    // =============================================

    public function delete_comment() {
        $this->verify_nonce();

        $user_id    = get_current_user_id();
        $comment_id = intval($_POST['comment_id'] ?? 0);

        $comment = get_comment($comment_id);
        if (!$comment) {
            wp_send_json_error(['message' => 'دیدگاه یافت نشد.']);
        }

        // Owner or admin
        if ((int) $comment->user_id !== $user_id && !current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'شما مجاز به حذف این دیدگاه نیستید.']);
        }

        wp_delete_comment($comment_id, true);

        wp_send_json_success(['message' => 'دیدگاه حذف شد.']);
    }

    // =============================================
    // PIN / UNPIN COMMENT
    // =============================================

    public function pin_comment() {
        $this->verify_nonce();

        $user_id    = get_current_user_id();
        $comment_id = intval($_POST['comment_id'] ?? 0);

        $comment = get_comment($comment_id);
        if (!$comment) {
            wp_send_json_error(['message' => 'دیدگاه یافت نشد.']);
        }

        $post = get_post($comment->comment_post_ID);

        // Only admin or post author
        if (!current_user_can('manage_options') && (int) $post->post_author !== $user_id) {
            wp_send_json_error(['message' => 'شما مجاز نیستید.']);
        }

        $is_pinned = (int) get_comment_meta($comment_id, 'novel_is_pinned', true);

        if ($is_pinned) {
            // Unpin
            delete_comment_meta($comment_id, 'novel_is_pinned');
            wp_send_json_success(['message' => 'پین برداشته شد.', 'pinned' => false]);
        } else {
            // Check max pins
            $current_pins = get_comments([
                'post_id'    => $comment->comment_post_ID,
                'meta_key'   => 'novel_is_pinned',
                'meta_value' => 1,
                'count'      => true,
            ]);

            if ($current_pins >= self::MAX_PINS) {
                wp_send_json_error(['message' => sprintf('حداکثر %d پین مجاز است.', self::MAX_PINS)]);
            }

            update_comment_meta($comment_id, 'novel_is_pinned', 1);
            wp_send_json_success(['message' => 'دیدگاه پین شد 📌', 'pinned' => true]);
        }
    }

    // =============================================
    // REPORT COMMENT
    // =============================================

    public function report_comment() {
        $this->verify_nonce();
        $this->require_login_and_verified();

        global $wpdb;
        $table      = $wpdb->prefix . 'comment_reports';
        $user_id    = get_current_user_id();
        $comment_id = intval($_POST['comment_id'] ?? 0);
        $reason     = sanitize_text_field($_POST['reason'] ?? '');
        $details    = sanitize_textarea_field(mb_substr($_POST['details'] ?? '', 0, 300));

        $valid_reasons = ['spam', 'offensive', 'spoiler_unmarked', 'inappropriate', 'other'];
        if (!in_array($reason, $valid_reasons) || !get_comment($comment_id)) {
            wp_send_json_error(['message' => 'درخواست نامعتبر.']);
        }

        // Check if already reported
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE comment_id = %d AND user_id = %d",
            $comment_id, $user_id
        ));

        if ($exists > 0) {
            wp_send_json_error(['message' => 'شما قبلاً این دیدگاه را گزارش کرده‌اید.']);
        }

        $wpdb->insert($table, [
            'comment_id' => $comment_id,
            'user_id'    => $user_id,
            'reason'     => $reason,
            'details'    => $details,
            'status'     => 'pending',
        ], ['%d', '%d', '%s', '%s', '%s']);

        wp_send_json_success(['message' => 'گزارش ثبت شد و بررسی خواهد شد ✓']);
    }

    // =============================================
    // LOAD REPLIES (AJAX)
    // =============================================

    public function load_replies() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'novel_comments_nonce')) {
            // Also check for guest nonce
            if (!check_ajax_referer('novel_comments_nonce', 'nonce', false)) {
                wp_send_json_error(['message' => 'توکن نامعتبر.']);
            }
        }

        $parent_id = intval($_POST['parent_id'] ?? 0);
        $page      = intval($_POST['page'] ?? 1);
        $per_page  = 20;

        $replies = get_comments([
            'parent'  => $parent_id,
            'status'  => 'approve',
            'orderby' => 'comment_date_gmt',
            'order'   => 'ASC',
            'number'  => $per_page,
            'offset'  => ($page - 1) * $per_page,
        ]);

        $total = get_comments([
            'parent' => $parent_id,
            'status' => 'approve',
            'count'  => true,
        ]);

        ob_start();
        foreach ($replies as $reply) {
            $depth = $this->get_comment_depth($reply);
            $this->render_single_comment($reply, min($depth, 3));
        }
        $html = ob_get_clean();

        wp_send_json_success([
            'html'     => $html,
            'total'    => $total,
            'has_more' => ($page * $per_page) < $total,
        ]);
    }

    // =============================================
    // SORT COMMENTS
    // =============================================

    public function sort_comments() {
        check_ajax_referer('novel_comments_nonce', 'nonce');

        $post_id  = intval($_POST['post_id'] ?? 0);
        $sort     = sanitize_text_field($_POST['sort'] ?? 'newest');
        $type     = sanitize_text_field($_POST['comment_type'] ?? 'comment');
        $page     = intval($_POST['page'] ?? 1);
        $per_page = 20;

        $comments = $this->query_comments($post_id, $type, $sort, $page, $per_page);
        $total    = $this->count_comments($post_id, $type);

        ob_start();
        if (empty($comments)) {
            echo '<div class="comments-empty"><p>' . esc_html__('هنوز دیدگاهی نوشته نشده. اولین نفر باشید! 🖋️', 'suspended-starter') . '</p></div>';
        } else {
            foreach ($comments as $comment) {
                $this->render_single_comment($comment, 0);
            }
        }
        $html = ob_get_clean();

        wp_send_json_success([
            'html'     => $html,
            'total'    => $total,
            'has_more' => ($page * $per_page) < $total,
            'page'     => $page,
        ]);
    }

    // =============================================
    // MENTION SEARCH
    // =============================================

    public function mention_search() {
        $this->verify_nonce();

        $query = sanitize_text_field($_POST['query'] ?? '');
        if (mb_strlen($query) < 2) {
            wp_send_json_success(['users' => []]);
        }

        $users = get_users([
            'search'         => '*' . $query . '*',
            'search_columns' => ['display_name', 'user_login'],
            'number'         => 5,
            'orderby'        => 'display_name',
        ]);

        $results = [];
        foreach ($users as $user) {
            $results[] = [
                'id'          => $user->ID,
                'name'        => $user->display_name,
                'username'    => $user->user_login,
                'avatar_url'  => Novel_Avatars::get_avatar_url_static($user->ID, 24),
            ];
        }

        wp_send_json_success(['users' => $results]);
    }

    // =============================================
    // SPOILER MODE TOGGLE
    // =============================================

    public function toggle_spoiler_mode() {
        // Just a simple acknowledgment — actual state stored in localStorage
        wp_send_json_success(['message' => 'ok']);
    }

    // =============================================
    // FILTERS & HOOKS
    // =============================================

    /**
     * Auto-approve for verified users
     */
    public function filter_comment_approved($approved, $commentdata) {
        if (!is_user_logged_in()) {
            return 'hold';
        }

        $user_id = get_current_user_id();

        if (!Novel_Auth::is_email_verified($user_id)) {
            return new WP_Error('email_not_verified', 'ابتدا ایمیل خود را تأیید کنید.');
        }

        return 1;
    }

    /**
     * Preprocess comment data
     */
    public function preprocess_comment($commentdata) {
        // No guest comments
        if (empty($commentdata['user_id'])) {
            wp_die('لطفاً ابتدا وارد شوید.', 'خطا', ['response' => 403]);
        }

        return $commentdata;
    }

    /**
     * Filter comment text for display
     */
    public function filter_comment_text($text, $comment = null) {
        if (!$comment) return $text;

        // Process mentions → links
        $text = $this->render_mentions($text);

        // Process spoiler tags
        $text = $this->render_spoiler_tags($text);

        // Process sticker shortcodes
        $text = $this->render_stickers($text);

        // Add nofollow to links
        $text = preg_replace(
            '/<a((?!.*?rel=)[^>]*)>/i',
            '<a$1 rel="nofollow ugc">',
            $text
        );

        return $text;
    }

    /**
     * After comment is posted
     */
    public function after_comment_post($comment_id, $approved, $commentdata) {
        if (!isset($commentdata['user_id']) || !$commentdata['user_id']) return;

        $user_id = (int) $commentdata['user_id'];

        // Update comment count cache
        $this->update_user_comment_count($user_id);

        // Notify parent comment author
        if (!empty($commentdata['comment_parent'])) {
            $parent = get_comment($commentdata['comment_parent']);
            if ($parent && (int) $parent->user_id !== $user_id) {
                $this->send_reply_notification($parent, $comment_id);
            }
        }

        // Notify mentioned users
        $mentioned = get_comment_meta($comment_id, 'novel_mentioned_users', true);
        if (is_array($mentioned)) {
            foreach ($mentioned as $mid) {
                if ((int) $mid !== $user_id) {
                    $this->send_mention_notification($mid, $comment_id);
                }
            }
        }
    }

    /**
     * After comment deleted
     */
    public function after_comment_delete($comment_id, $comment) {
        if (isset($comment->user_id) && $comment->user_id) {
            $this->update_user_comment_count((int) $comment->user_id);
        }

        // Clean up custom tables
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'comment_votes', ['comment_id' => $comment_id], ['%d']);
        $wpdb->delete($wpdb->prefix . 'comment_reactions', ['comment_id' => $comment_id], ['%d']);
        $wpdb->delete($wpdb->prefix . 'review_helpfulness', ['comment_id' => $comment_id], ['%d']);
        $wpdb->delete($wpdb->prefix . 'comment_reports', ['comment_id' => $comment_id], ['%d']);
    }

    // =============================================
    // QUERY HELPERS
    // =============================================

    /**
     * Query comments with sorting
     */
    public function query_comments($post_id, $type = 'comment', $sort = 'newest', $page = 1, $per_page = 20) {
        global $wpdb;

        $offset = ($page - 1) * $per_page;

        // Base query – top-level only
        $where = $wpdb->prepare(
            "c.comment_post_ID = %d AND c.comment_approved = '1' AND c.comment_parent = 0",
            $post_id
        );

        // Filter by type
        $where .= $wpdb->prepare(
            " AND EXISTS (SELECT 1 FROM {$wpdb->commentmeta} cm 
              WHERE cm.comment_id = c.comment_ID AND cm.meta_key = 'novel_comment_type' AND cm.meta_value = %s)",
            $type
        );

        switch ($sort) {
            case 'oldest':
                $order = "c.comment_date_gmt ASC";
                break;

            case 'popular':
                $order = "(SELECT COALESCE(SUM(v.vote), 0) FROM {$wpdb->prefix}comment_votes v 
                           WHERE v.comment_id = c.comment_ID) DESC, c.comment_date_gmt DESC";
                break;

            case 'most_replied':
                $order = "(SELECT COUNT(*) FROM {$wpdb->comments} r 
                           WHERE r.comment_parent = c.comment_ID AND r.comment_approved = '1') DESC, 
                           c.comment_date_gmt DESC";
                break;

            case 'helpful':
                // Only for reviews
                $order = "(SELECT COALESCE(SUM(CASE WHEN h.helpful = 1 THEN 1 ELSE 0 END), 0) 
                           FROM {$wpdb->prefix}review_helpfulness h 
                           WHERE h.comment_id = c.comment_ID) DESC, c.comment_date_gmt DESC";
                break;

            case 'newest':
            default:
                $order = "c.comment_date_gmt DESC";
                break;
        }

        $sql = "SELECT c.* FROM {$wpdb->comments} c WHERE {$where} ORDER BY {$order} LIMIT %d OFFSET %d";
        $results = $wpdb->get_results($wpdb->prepare($sql, $per_page, $offset));

        // Convert to WP_Comment objects
        $comments = [];
        foreach ($results as $row) {
            $comments[] = new WP_Comment($row);
        }

        return $comments;
    }

    /**
     * Count comments by type
     */
    public function count_comments($post_id, $type = 'comment') {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->comments} c
             WHERE c.comment_post_ID = %d 
             AND c.comment_approved = '1' 
             AND c.comment_parent = 0
             AND EXISTS (SELECT 1 FROM {$wpdb->commentmeta} cm 
                WHERE cm.comment_id = c.comment_ID 
                AND cm.meta_key = 'novel_comment_type' 
                AND cm.meta_value = %s)",
            $post_id, $type
        ));
    }

    /**
     * Get reply count for a comment
     */
    public function get_reply_count($comment_id) {
        return (int) get_comments([
            'parent' => $comment_id,
            'status' => 'approve',
            'count'  => true,
        ]);
    }

    /**
     * Get vote counts
     */
    public function get_vote_counts($comment_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'comment_votes';

        $likes = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE comment_id = %d AND vote = 1", $comment_id
        ));
        $dislikes = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE comment_id = %d AND vote = -1", $comment_id
        ));

        return ['likes' => $likes, 'dislikes' => $dislikes];
    }

    /**
     * Get user's vote on a comment
     */
    public function get_user_vote($comment_id, $user_id) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT vote FROM {$wpdb->prefix}comment_votes WHERE comment_id = %d AND user_id = %d",
            $comment_id, $user_id
        ));
    }

    /**
     * Get reaction counts
     */
    public function get_reaction_counts($comment_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'comment_reactions';

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT reaction, COUNT(*) as cnt FROM {$table} WHERE comment_id = %d GROUP BY reaction",
            $comment_id
        ));

        $counts = [];
        foreach ($rows as $row) {
            if (isset(self::REACTION_EMOJI[$row->reaction])) {
                $counts[$row->reaction] = [
                    'emoji' => self::REACTION_EMOJI[$row->reaction],
                    'count' => (int) $row->cnt,
                ];
            }
        }

        return $counts;
    }

    /**
     * Get user's reaction on a comment
     */
    public function get_user_reaction($comment_id, $user_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT reaction FROM {$wpdb->prefix}comment_reactions WHERE comment_id = %d AND user_id = %d",
            $comment_id, $user_id
        ));
    }

    /**
     * Get helpful counts
     */
    public function get_helpful_counts($comment_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'review_helpfulness';

        $yes = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE comment_id = %d AND helpful = 1", $comment_id
        ));
        $no = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE comment_id = %d AND helpful = -1", $comment_id
        ));

        return ['yes' => $yes, 'no' => $no];
    }

    /**
     * Get user's existing review for a post
     */
    private function get_user_review($user_id, $post_id) {
        $reviews = get_comments([
            'post_id'    => $post_id,
            'user_id'    => $user_id,
            'meta_key'   => 'novel_comment_type',
            'meta_value' => 'review',
            'number'     => 1,
            'status'     => 'any',
        ]);

        return !empty($reviews) ? $reviews[0] : null;
    }

    // =============================================
    // VALIDATION & ANTI-SPAM
    // =============================================

    private function validate_content_length($content, $type) {
        $constraints = self::TYPES[$type] ?? self::TYPES['comment'];
        $stripped = wp_strip_all_tags($content);

        if ($constraints['unit'] === 'char') {
            $length = mb_strlen($stripped);
            $min = (int) get_option("novel_{$type}_min_chars", $constraints['min_chars']);
            $max = (int) get_option("novel_{$type}_max_chars", $constraints['max_chars']);

            if ($length < $min) {
                return sprintf('دیدگاه باید حداقل %d کاراکتر باشد.', $min);
            }
            if ($length > $max) {
                return sprintf('دیدگاه نباید بیش از %d کاراکتر باشد.', $max);
            }
        } else {
            // Word count
            $words = $this->count_words($stripped);
            $min = (int) get_option("novel_{$type}_min_words", $constraints['min_words']);
            $max = (int) get_option("novel_{$type}_max_chars", $constraints['max_chars']);

            if ($words < $min) {
                return sprintf('حداقل %d کلمه لازم است. (شما %d کلمه نوشته‌اید)', $min, $words);
            }
            if (mb_strlen($stripped) > $max) {
                return sprintf('حداکثر %d کاراکتر مجاز است.', $max);
            }
        }

        return null;
    }

    private function count_words($text) {
        $text = trim($text);
        if (empty($text)) return 0;
        return count(preg_split('/\s+/u', $text));
    }

    private function check_comment_rate_limit($user_id) {
        $key_last = 'novel_comment_last_' . $user_id;
        $key_hour = 'novel_comment_hour_' . $user_id;
        $key_day  = 'novel_comment_day_' . $user_id;

        // Per-second limit
        $last = get_transient($key_last);
        if ($last && (time() - $last) < self::RATE_SECONDS) {
            $wait = self::RATE_SECONDS - (time() - $last);
            return sprintf('لطفاً %d ثانیه صبر کنید.', $wait);
        }

        // Hourly limit
        $hourly = (int) get_transient($key_hour);
        if ($hourly >= self::RATE_HOURLY) {
            return 'حداکثر تعداد دیدگاه در ساعت اخیر رسیده. کمی صبر کنید.';
        }

        // Daily limit
        $daily = (int) get_transient($key_day);
        if ($daily >= self::RATE_DAILY) {
            return 'حداکثر تعداد دیدگاه روزانه رسیده. فردا دوباره تلاش کنید.';
        }

        return null;
    }

    private function record_comment_time($user_id) {
        set_transient('novel_comment_last_' . $user_id, time(), self::RATE_SECONDS + 5);

        $key_hour = 'novel_comment_hour_' . $user_id;
        $hourly = (int) get_transient($key_hour);
        set_transient($key_hour, $hourly + 1, 3600);

        $key_day = 'novel_comment_day_' . $user_id;
        $daily = (int) get_transient($key_day);
        set_transient($key_day, $daily + 1, 86400);
    }

    private function check_duplicate($content, $user_id, $post_id) {
        $hash = md5($content . $user_id . $post_id);

        // Check last 10 comments from this user on this post
        $recent = get_comments([
            'user_id'  => $user_id,
            'post_id'  => $post_id,
            'number'   => 10,
            'status'   => 'any',
            'orderby'  => 'comment_date_gmt',
            'order'    => 'DESC',
        ]);

        foreach ($recent as $rc) {
            $rc_hash = md5($rc->comment_content . $user_id . $post_id);
            if ($rc_hash === $hash) {
                return 'این دیدگاه قبلاً ارسال شده.';
            }

            // Similarity check
            $similarity = 0;
            similar_text(
                mb_strtolower(wp_strip_all_tags($content)),
                mb_strtolower(wp_strip_all_tags($rc->comment_content)),
                $similarity
            );
            if ($similarity > self::SIMILARITY_THRESH) {
                return 'دیدگاهی بسیار مشابه قبلاً ارسال شده.';
            }
        }

        // Flood detection: 5 comments in 5 minutes = block
        $five_min_ago = gmdate('Y-m-d H:i:s', time() - 300);
        $recent_count = get_comments([
            'user_id'    => $user_id,
            'date_query' => [['after' => $five_min_ago]],
            'count'      => true,
            'status'     => 'any',
        ]);

        if ($recent_count >= 5) {
            // Set 1 hour block
            set_transient('novel_comment_blocked_' . $user_id, 1, 3600);
            return 'فعالیت مشکوک تشخیص داده شد. لطفاً ۱ ساعت دیگر تلاش کنید.';
        }

        // Check if blocked
        if (get_transient('novel_comment_blocked_' . $user_id)) {
            return 'حساب شما موقتاً محدود شده. لطفاً کمی صبر کنید.';
        }

        return null;
    }

    // =============================================
    // BAD WORD FILTER
    // =============================================

    public function filter_bad_words($text) {
        $words_str = get_option('novel_bad_words', '');
        if (empty($words_str)) return $text;

        $words = array_filter(array_map('trim', explode("\n", $words_str)));
        if (empty($words)) return $text;

        foreach ($words as $word) {
            $word = trim($word);
            if (empty($word)) continue;

            $stars = str_repeat('*', mb_strlen($word));
            // Case-insensitive, multibyte-safe
            $pattern = '/' . preg_quote($word, '/') . '/iu';
            $text = preg_replace($pattern, $stars, $text);
        }

        return $text;
    }

    private function count_filtered_words($text) {
        $words_str = get_option('novel_bad_words', '');
        if (empty($words_str)) return 0;

        $words = array_filter(array_map('trim', explode("\n", $words_str)));
        $count = 0;

        foreach ($words as $word) {
            $word = trim($word);
            if (empty($word)) continue;
            $count += preg_match_all('/' . preg_quote($word, '/') . '/iu', $text);
        }

        return $count;
    }

    // =============================================
    // MENTION PROCESSING
    // =============================================

    private function extract_mentions($text) {
        preg_match_all('/@([\w\x{0600}-\x{06FF}]+)/u', $text, $matches);
        if (empty($matches[1])) return [];

        $user_ids = [];
        foreach (array_unique($matches[1]) as $name) {
            $user = get_user_by('login', $name);
            if (!$user) {
                // Try by display name
                $users = get_users([
                    'search'         => $name,
                    'search_columns' => ['display_name'],
                    'number'         => 1,
                ]);
                $user = !empty($users) ? $users[0] : null;
            }
            if ($user) {
                $user_ids[] = $user->ID;
            }
        }

        return array_unique($user_ids);
    }

    private function render_mentions($text) {
        return preg_replace_callback(
            '/@([\w\x{0600}-\x{06FF}]+)/u',
            function ($m) {
                $name = $m[1];
                $user = get_user_by('login', $name);
                if (!$user) {
                    $users = get_users([
                        'search'         => $name,
                        'search_columns' => ['display_name'],
                        'number'         => 1,
                    ]);
                    $user = !empty($users) ? $users[0] : null;
                }
                if ($user) {
                    $url = get_author_posts_url($user->ID);
                    return '<a href="' . esc_url($url) . '" class="comment-mention">@' . esc_html($user->display_name) . '</a>';
                }
                return $m[0];
            },
            $text
        );
    }

    // =============================================
    // SPOILER PROCESSING
    // =============================================

    private function render_spoiler_tags($text) {
        return preg_replace(
            '/\[spoiler\](.*?)\[\/spoiler\]/is',
            '<div class="spoiler-wrap">
                <div class="spoiler-overlay">
                    <span class="spoiler-overlay__icon">⚠️</span>
                    <span class="spoiler-overlay__text">اسپویلر! کلیک کنید</span>
                </div>
                <div class="spoiler-content">$1</div>
            </div>',
            $text
        );
    }

    // =============================================
    // STICKER RENDERING
    // =============================================

    private function render_stickers($text) {
        // [sticker:category/name]
        $text = preg_replace_callback(
            '/\[sticker:([a-zA-Z0-9_\-]+)\/([a-zA-Z0-9_\-]+)\]/',
            function ($m) {
                $cat = sanitize_file_name($m[1]);
                $name = sanitize_file_name($m[2]);
                $path = get_template_directory() . "/assets/stickers/{$cat}/{$name}.svg";
                if (file_exists($path)) {
                    $url = get_template_directory_uri() . "/assets/stickers/{$cat}/{$name}.svg";
                    return '<img src="' . esc_url($url) . '" class="comment-sticker" alt="' . esc_attr($name) . '" loading="lazy">';
                }
                return '';
            },
            $text
        );

        // [gif:category/name]
        $text = preg_replace_callback(
            '/\[gif:([a-zA-Z0-9_\-]+)\/([a-zA-Z0-9_\-]+)\]/',
            function ($m) {
                $cat = sanitize_file_name($m[1]);
                $name = sanitize_file_name($m[2]);
                $path = get_template_directory() . "/assets/gifs/{$cat}/{$name}.gif";
                if (file_exists($path)) {
                    $url = get_template_directory_uri() . "/assets/gifs/{$cat}/{$name}.gif";
                    return '<img src="' . esc_url($url) . '" class="comment-gif" alt="' . esc_attr($name) . '" loading="lazy">';
                }
                return '';
            },
            $text
        );

        return $text;
    }

    // =============================================
    // COMMENT LEVEL SYSTEM
    // =============================================

    public static function get_user_level($user_id) {
        $count = (int) get_user_meta($user_id, 'novel_comment_total', true);
        $level = self::LEVELS[0]; // default

        foreach (self::LEVELS as $threshold => $data) {
            if ($count >= $threshold) {
                $level = $data;
            }
        }

        $level['count'] = $count;
        return $level;
    }

    private function update_user_comment_count($user_id) {
        $count = (int) get_comments([
            'user_id' => $user_id,
            'status'  => 'approve',
            'count'   => true,
        ]);

        update_user_meta($user_id, 'novel_comment_total', $count);

        // Determine level
        $level_key = 0;
        foreach (array_keys(self::LEVELS) as $threshold) {
            if ($count >= $threshold) {
                $level_key = $threshold;
            }
        }
        update_user_meta($user_id, 'novel_comment_level', $level_key);
    }

    // =============================================
    // USER BADGES
    // =============================================

    public static function get_user_badges($user_id, $post_id = 0) {
        $badges = [];
        $user = get_userdata($user_id);
        if (!$user) return $badges;

        // ① Admin
        if (user_can($user_id, 'manage_options')) {
            $badges[] = ['label' => 'مدیر', 'icon' => '👑', 'color' => '#ef4444', 'priority' => 1];
        }

        // ② Editor
        if (user_can($user_id, 'edit_others_posts') && !user_can($user_id, 'manage_options')) {
            $badges[] = ['label' => 'ویراستار', 'icon' => '📝', 'color' => '#f97316', 'priority' => 2];
        }

        // ③ Post author
        if ($post_id > 0) {
            $post = get_post($post_id);
            if ($post && (int) $post->post_author === $user_id) {
                $badges[] = ['label' => 'نویسنده', 'icon' => '✍️', 'color' => '#f59e0b', 'priority' => 3];
            }
        }

        // ④ VIP (RCP subscription check placeholder)
        if (function_exists('rcp_user_has_active_membership') && rcp_user_has_active_membership($user_id)) {
            $badges[] = ['label' => 'عضو ویژه', 'icon' => '💎', 'color' => '#10b981', 'priority' => 4];
        }

        // ⑤ Comment level
        $level = self::get_user_level($user_id);
        if ($level['count'] > 0) {
            $badges[] = [
                'label'    => $level['title'],
                'icon'     => $level['icon'],
                'color'    => $level['color'],
                'priority' => 10,
                'tooltip'  => sprintf('سطح: %s (%d دیدگاه)', $level['title'], $level['count']),
            ];
        }

        // Sort by priority
        usort($badges, function ($a, $b) {
            return ($a['priority'] ?? 99) - ($b['priority'] ?? 99);
        });

        return $badges;
    }

    // =============================================
    // RENDER HELPERS
    // =============================================

    /**
     * Render a single comment (used by templates and AJAX)
     */
    public function render_single_comment($comment, $depth = 0) {
        $args = [
            'depth'   => $depth,
            'comment' => $comment,
        ];

        // Use template part
        $template = get_template_directory() . '/templates/comments/comment-single.php';
        if (file_exists($template)) {
            extract($args, EXTR_SKIP);
            include $template;
        }
    }

    /**
     * Get comment depth
     */
    public function get_comment_depth($comment) {
        $depth = 0;
        $c = $comment;
        while ($c->comment_parent > 0) {
            $depth++;
            $c = get_comment($c->comment_parent);
            if (!$c) break;
        }
        return $depth;
    }

    // =============================================
    // NOTIFICATIONS
    // =============================================

    private function send_reply_notification($parent_comment, $reply_id) {
        // Will integrate with Novel_Notifications class later
        // For now, store in notifications table if it exists
        global $wpdb;
        $table = $wpdb->prefix . 'notifications';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) return;

        $reply = get_comment($reply_id);
        $post = get_post($parent_comment->comment_post_ID);

        $wpdb->insert($table, [
            'user_id'    => (int) $parent_comment->user_id,
            'type'       => 'comment_reply',
            'message'    => sprintf('%s به دیدگاه شما پاسخ داد', $reply ? $reply->comment_author : ''),
            'link'       => get_comment_link($reply_id),
            'is_read'    => 0,
            'created_at' => current_time('mysql'),
        ]);

        // Email notification (check user settings)
        $this->maybe_send_reply_email($parent_comment, $reply_id);
    }

    private function send_mention_notification($user_id, $comment_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'notifications';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) return;

        $comment = get_comment($comment_id);

        $wpdb->insert($table, [
            'user_id'    => $user_id,
            'type'       => 'mention',
            'message'    => sprintf('%s شما را در دیدگاهی منشن کرد', $comment ? $comment->comment_author : ''),
            'link'       => get_comment_link($comment_id),
            'is_read'    => 0,
            'created_at' => current_time('mysql'),
        ]);
    }

    private function send_vote_notification($comment, $voter_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'notifications';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) return;

        $voter = get_userdata($voter_id);

        $wpdb->insert($table, [
            'user_id'    => (int) $comment->user_id,
            'type'       => 'comment_like',
            'message'    => sprintf('%s دیدگاه شما را پسندید 👍', $voter ? $voter->display_name : ''),
            'link'       => get_comment_link($comment->comment_ID),
            'is_read'    => 0,
            'created_at' => current_time('mysql'),
        ]);
    }

    private function maybe_send_reply_email($parent_comment, $reply_id) {
        $user_id = (int) $parent_comment->user_id;
        if (!$user_id) return;

        // Check notification settings
        if (class_exists('Novel_Profile')) {
            $settings = Novel_Profile::get_user_notification_settings($user_id);
            if (empty($settings['comment_reply']['email'])) return;
        }

        $user = get_userdata($user_id);
        $reply = get_comment($reply_id);
        if (!$user || !$reply) return;

        $site_name = get_bloginfo('name');
        $subject = sprintf('پاسخ جدید به دیدگاه شما - %s', $site_name);

        $body = sprintf(
            '<div dir="rtl" style="font-family:Tahoma,sans-serif;padding:20px;">
            <h2>پاسخ جدید 💬</h2>
            <p>سلام %s،</p>
            <p><strong>%s</strong> به دیدگاه شما پاسخ داد:</p>
            <blockquote style="background:#f8f9fa;padding:12px 16px;border-radius:8px;border-right:3px solid #6366f1;margin:16px 0;">%s</blockquote>
            <p><a href="%s" style="background:#6366f1;color:#fff;padding:10px 20px;text-decoration:none;border-radius:8px;display:inline-block;">مشاهده پاسخ</a></p>
            </div>',
            esc_html($user->display_name),
            esc_html($reply->comment_author),
            wp_trim_words(wp_strip_all_tags($reply->comment_content), 30),
            esc_url(get_comment_link($reply_id))
        );

        wp_mail($user->user_email, $subject, $body, ['Content-Type: text/html; charset=UTF-8']);
    }

    // =============================================
    // SHORTCODE
    // =============================================

    public function shortcode_all_comments($atts) {
        ob_start();
        include get_template_directory() . '/templates/comments/all-comments.php';
        return ob_get_clean();
    }

    // =============================================
    // UTILITY
    // =============================================

    private function verify_nonce() {
        if (!check_ajax_referer('novel_comments_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'توکن امنیتی نامعتبر است.']);
        }
    }

    private function require_login_and_verified() {
        if (!is_user_logged_in()) {
            wp_send_json_error([
                'message'   => 'برای این کار باید وارد شوید.',
                'code'      => 'not_logged_in',
                'login_url' => home_url('/login/'),
            ]);
        }

        if (class_exists('Novel_Auth') && !Novel_Auth::is_email_verified(get_current_user_id())) {
            wp_send_json_error([
                'message'    => 'ابتدا ایمیل خود را تأیید کنید.',
                'code'       => 'email_not_verified',
                'verify_url' => home_url('/verify-email/'),
            ]);
        }
    }

    /**
     * Helper: Get time elapsed string
     */
    public static function time_ago($datetime) {
        return human_time_diff(strtotime($datetime), current_time('timestamp')) . ' پیش';
    }

    /**
     * Helper: Can user edit this comment?
     */
    public static function can_edit($comment) {
        if (!is_user_logged_in()) return false;
        $user_id = get_current_user_id();
        if ((int) $comment->user_id !== $user_id) return false;

        $window = (int) get_option('novel_edit_time', self::EDIT_WINDOW);
        $elapsed = (time() - strtotime($comment->comment_date_gmt)) / 60;

        return $elapsed <= $window;
    }

    /**
     * Helper: Get edit remaining time in seconds
     */
    public static function edit_remaining($comment) {
        $window = (int) get_option('novel_edit_time', self::EDIT_WINDOW) * 60;
        $elapsed = time() - strtotime($comment->comment_date_gmt);
        return max(0, $window - $elapsed);
    }
}