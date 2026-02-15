<?php
/**
 * Unified Follow System
 *
 * Handles: follow user + follow novel + notifications
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Novel_Follow {

    private $user_follows_table;
    private $novel_follows_table;

    public function __construct() {
        global $wpdb;
        $this->user_follows_table  = $wpdb->prefix . 'user_follows';
        $this->novel_follows_table = $wpdb->prefix . 'novel_follows';

        add_action('after_switch_theme', [$this, 'create_tables']);
        add_action('init', [$this, 'maybe_create_tables'], 99);

        // AJAX
        add_action('wp_ajax_novel_follow_user', [$this, 'ajax_follow_user']);
        add_action('wp_ajax_novel_follow_novel', [$this, 'ajax_follow_novel']);
        add_action('wp_ajax_novel_get_followers', [$this, 'ajax_get_followers']);
        add_action('wp_ajax_nopriv_novel_get_followers', [$this, 'ajax_get_followers']);

        // Hook: new chapter published → notify followers
        add_action('publish_chapter', [$this, 'notify_new_chapter'], 20, 2);

        // Hook: new novel published → notify author followers
        add_action('publish_novel', [$this, 'notify_new_novel'], 20, 2);

        // Enqueue
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
    }

    // ═══════════════════════════════════════════
    // TABLES
    // ═══════════════════════════════════════════

    public function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta("CREATE TABLE IF NOT EXISTS {$this->user_follows_table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            follower_id BIGINT UNSIGNED NOT NULL,
            followed_id BIGINT UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY pair (follower_id, followed_id),
            INDEX idx_followed (followed_id)
        ) {$charset}");

        dbDelta("CREATE TABLE IF NOT EXISTS {$this->novel_follows_table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            novel_id BIGINT UNSIGNED NOT NULL,
            notify_new_chapter TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY user_novel (user_id, novel_id),
            INDEX idx_novel (novel_id)
        ) {$charset}");
    }

    public function maybe_create_tables() {
        global $wpdb;
        if ($wpdb->get_var("SHOW TABLES LIKE '{$this->user_follows_table}'") !== $this->user_follows_table) {
            $this->create_tables();
        }
    }

    public function enqueue() {
        wp_enqueue_script('novel-follow', get_template_directory_uri() . '/assets/js/follow.js', ['jquery'], NOVEL_VERSION, true);
        wp_localize_script('novel-follow', 'novelFollow', [
            'ajaxUrl'    => admin_url('admin-ajax.php'),
            'nonce'      => wp_create_nonce('novel_follow_action'),
            'isLoggedIn' => is_user_logged_in(),
            'loginUrl'   => wp_login_url(get_permalink()),
        ]);
    }

    // ═══════════════════════════════════════════
    // USER FOLLOW
    // ═══════════════════════════════════════════

    public static function is_following_user($follower_id, $followed_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'user_follows';
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE follower_id=%d AND followed_id=%d",
            $follower_id, $followed_id
        ));
    }

    public static function get_followers_count($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'user_follows';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) return 0;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE followed_id=%d", $user_id
        ));
    }

    public static function get_following_count($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'user_follows';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) return 0;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE follower_id=%d", $user_id
        ));
    }

    public function ajax_follow_user() {
        check_ajax_referer('novel_follow_action', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'ابتدا وارد شوید']);
        }

        $followed_id = absint($_POST['user_id'] ?? 0);
        $follower_id = get_current_user_id();

        if (!$followed_id || $follower_id === $followed_id) {
            wp_send_json_error(['message' => 'داده نامعتبر']);
        }

        global $wpdb;

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->user_follows_table} WHERE follower_id=%d AND followed_id=%d",
            $follower_id, $followed_id
        ));

        if ($exists) {
            // Unfollow
            $wpdb->delete($this->user_follows_table, ['id' => $exists]);
            $following = false;
        } else {
            // Follow
            $wpdb->insert($this->user_follows_table, [
                'follower_id' => $follower_id,
                'followed_id' => $followed_id,
                'created_at'  => current_time('mysql'),
            ]);
            $following = true;

            // Notify
            if (class_exists('Novel_Notifications')) {
                $follower = wp_get_current_user();
                Novel_Notifications::send(
                    $followed_id,
                    'new_follower',
                    '❤ ' . $follower->display_name . ' شما را دنبال کرد',
                    '',
                    get_author_posts_url($follower_id)
                );
            }
        }

        $count = self::get_followers_count($followed_id);

        // Invalidate cache
        wp_cache_delete('novel_author_stats_' . $followed_id, 'novel');

        wp_send_json_success([
            'following' => $following,
            'count'     => number_format_i18n($count),
            'message'   => $following ? 'دنبال شد' : 'لغو شد',
        ]);
    }

    // ═══════════════════════════════════════════
    // NOVEL FOLLOW
    // ═══════════════════════════════════════════

    public static function is_following_novel($user_id, $novel_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'novel_follows';
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE user_id=%d AND novel_id=%d",
            $user_id, $novel_id
        ));
    }

    public static function get_novel_followers_count($novel_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'novel_follows';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) return 0;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE novel_id=%d", $novel_id
        ));
    }

    public function ajax_follow_novel() {
        check_ajax_referer('novel_follow_action', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'ابتدا وارد شوید']);
        }

        $novel_id = absint($_POST['novel_id'] ?? 0);
        $user_id  = get_current_user_id();

        if (!$novel_id) {
            wp_send_json_error(['message' => 'داده نامعتبر']);
        }

        global $wpdb;

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->novel_follows_table} WHERE user_id=%d AND novel_id=%d",
            $user_id, $novel_id
        ));

        if ($exists) {
            $wpdb->delete($this->novel_follows_table, ['id' => $exists]);
            $following = false;
        } else {
            $wpdb->insert($this->novel_follows_table, [
                'user_id'            => $user_id,
                'novel_id'           => $novel_id,
                'notify_new_chapter' => 1,
                'created_at'         => current_time('mysql'),
            ]);
            $following = true;

            // Auto-add to library if not there
            $lib_table = $wpdb->prefix . 'user_library';
            if ($wpdb->get_var("SHOW TABLES LIKE '{$lib_table}'") === $lib_table) {
                $in_lib = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$lib_table} WHERE user_id=%d AND novel_id=%d",
                    $user_id, $novel_id
                ));
                if (!$in_lib) {
                    $wpdb->insert($lib_table, [
                        'user_id'    => $user_id,
                        'novel_id'   => $novel_id,
                        'status'     => 'plan',
                        'created_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql'),
                    ]);
                }
            }
        }

        $count = self::get_novel_followers_count($novel_id);
        update_post_meta($novel_id, 'novel_follow_count', $count);

        wp_send_json_success([
            'following' => $following,
            'count'     => number_format_i18n($count),
        ]);
    }

    // ═══════════════════════════════════════════
    // FOLLOWERS LIST
    // ═══════════════════════════════════════════

    public function ajax_get_followers() {
        $type    = sanitize_text_field($_POST['type'] ?? 'user');
        $id      = absint($_POST['id'] ?? 0);
        $page    = absint($_POST['page'] ?? 1);
        $per     = 20;
        $offset  = ($page - 1) * $per;

        if (!$id) wp_send_json_error();

        global $wpdb;

        if ($type === 'novel') {
            $user_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT user_id FROM {$this->novel_follows_table} WHERE novel_id=%d ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $id, $per, $offset
            ));
            $total = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->novel_follows_table} WHERE novel_id=%d", $id
            ));
        } else {
            $list_type = sanitize_text_field($_POST['list'] ?? 'followers');
            if ($list_type === 'following') {
                $user_ids = $wpdb->get_col($wpdb->prepare(
                    "SELECT followed_id FROM {$this->user_follows_table} WHERE follower_id=%d ORDER BY created_at DESC LIMIT %d OFFSET %d",
                    $id, $per, $offset
                ));
                $total = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->user_follows_table} WHERE follower_id=%d", $id
                ));
            } else {
                $user_ids = $wpdb->get_col($wpdb->prepare(
                    "SELECT follower_id FROM {$this->user_follows_table} WHERE followed_id=%d ORDER BY created_at DESC LIMIT %d OFFSET %d",
                    $id, $per, $offset
                ));
                $total = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->user_follows_table} WHERE followed_id=%d", $id
                ));
            }
        }

        ob_start();
        if (!empty($user_ids)) {
            foreach ($user_ids as $uid) {
                $user = get_user_by('id', $uid);
                if (!$user) continue;
                $is_following = is_user_logged_in() ? self::is_following_user(get_current_user_id(), $uid) : false;
                ?>
                <div class="follower-item" data-user-id="<?php echo $uid; ?>">
                    <div class="follower-avatar">
                        <?php echo get_avatar($uid, 48); ?>
                        <?php echo Novel_Authors::render_online_dot($uid); ?>
                    </div>
                    <div class="follower-info">
                        <a href="<?php echo get_author_posts_url($uid); ?>" class="follower-name">
                            <?php echo esc_html($user->display_name); ?>
                        </a>
                    </div>
                    <?php if (is_user_logged_in() && (int)$uid !== get_current_user_id()) : ?>
                    <button class="btn-follow-user <?php echo $is_following ? 'following' : ''; ?>"
                            data-user-id="<?php echo $uid; ?>">
                        <?php echo $is_following ? 'دنبال شده ✓' : '❤ دنبال'; ?>
                    </button>
                    <?php endif; ?>
                </div>
                <?php
            }
        } else {
            echo '<p class="followers-empty">لیست خالی است.</p>';
        }
        $html = ob_get_clean();

        wp_send_json_success([
            'html'      => $html,
            'total'     => $total,
            'max_pages' => ceil($total / $per),
        ]);
    }

    // ═══════════════════════════════════════════
    // NOTIFICATIONS ON NEW CONTENT
    // ═══════════════════════════════════════════

    public function notify_new_chapter($post_id, $post) {
        if ($post->post_type !== 'chapter') return;

        $novel_id    = get_post_meta($post_id, 'chapter_novel_id', true);
        $chapter_num = get_post_meta($post_id, 'chapter_number', true);
        if (!$novel_id) return;

        $novel_title = get_the_title($novel_id);
        $ch_url      = novel_get_chapter_permalink($post_id);

        global $wpdb;
        $followers = $wpdb->get_col($wpdb->prepare(
            "SELECT user_id FROM {$this->novel_follows_table} WHERE novel_id=%d AND notify_new_chapter=1",
            $novel_id
        ));

        if (empty($followers) || !class_exists('Novel_Notifications')) return;

        $cover = get_the_post_thumbnail_url($novel_id, 'thumbnail') ?: '';

        foreach ($followers as $uid) {
            if ((int)$uid === (int)$post->post_author) continue;
            Novel_Notifications::send(
                $uid,
                'new_chapter',
                '📖 قسمت جدید: ' . $novel_title . ' - قسمت ' . $chapter_num,
                '',
                $ch_url,
                $cover
            );
        }
    }

    public function notify_new_novel($post_id, $post) {
        if ($post->post_type !== 'novel') return;

        $author_id = $post->post_author;
        global $wpdb;
        $followers = $wpdb->get_col($wpdb->prepare(
            "SELECT follower_id FROM {$this->user_follows_table} WHERE followed_id=%d",
            $author_id
        ));

        if (empty($followers) || !class_exists('Novel_Notifications')) return;

        $author_name = get_the_author_meta('display_name', $author_id);
        $novel_url   = get_permalink($post_id);

        foreach ($followers as $uid) {
            Novel_Notifications::send(
                $uid,
                'new_novel',
                '📚 رمان جدید ' . $author_name . ': ' . $post->post_title,
                '',
                $novel_url
            );
        }
    }
}