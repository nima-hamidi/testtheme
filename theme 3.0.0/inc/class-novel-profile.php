<?php
/**
 * Novel Profile Manager
 *
 * Handles profile editing, password change, email change, account deletion.
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

class Novel_Profile {

    private static $instance = null;

    const NAME_CHANGE_COOLDOWN = 30; // days

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Profile AJAX
        add_action('wp_ajax_novel_save_profile', [$this, 'save_profile']);
        add_action('wp_ajax_novel_change_password', [$this, 'change_password']);
        add_action('wp_ajax_novel_initiate_email_change', [$this, 'initiate_email_change']);
        add_action('wp_ajax_novel_confirm_email_change', [$this, 'confirm_email_change']);
        add_action('wp_ajax_novel_save_notification_settings', [$this, 'save_notification_settings']);
        add_action('wp_ajax_novel_save_privacy_settings', [$this, 'save_privacy_settings']);

        // Account deletion
        add_action('wp_ajax_novel_delete_account', [$this, 'delete_account']);

        // Dashboard tab loader
        add_action('wp_ajax_novel_load_dashboard_tab', [$this, 'load_dashboard_tab']);

        // Enqueue dashboard assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // REST API for deletion
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * Enqueue dashboard CSS/JS
     */
    public function enqueue_assets() {
        if (!$this->is_dashboard_page()) return;

        wp_enqueue_style(
            'novel-dashboard',
            get_template_directory_uri() . '/assets/css/dashboard.css',
            ['novel-main-style'],
            SUSPENDED_STARTER_VERSION
        );

        wp_enqueue_script(
            'novel-dashboard',
            get_template_directory_uri() . '/assets/js/dashboard.js',
            ['jquery'],
            SUSPENDED_STARTER_VERSION,
            true
        );

        wp_localize_script('novel-dashboard', 'novelDash', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('novel_auth_nonce'),
            'homeUrl' => home_url('/'),
        ]);
    }

    // ========================================
    // SAVE PROFILE
    // ========================================

    public function save_profile() {
        $this->verify_request();

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        // Display Name
        $display_name = isset($_POST['display_name']) ? sanitize_text_field(trim($_POST['display_name'])) : '';

        if (!empty($display_name) && $display_name !== $user->display_name) {
            // Check cooldown
            $last_change = (int) get_user_meta($user_id, 'novel_last_name_change', true);
            $days_since = $last_change ? floor((time() - $last_change) / 86400) : 999;

            if ($days_since < self::NAME_CHANGE_COOLDOWN) {
                $remaining = self::NAME_CHANGE_COOLDOWN - $days_since;
                wp_send_json_error([
                    'message' => sprintf('شما %d روز دیگر می‌توانید نام نمایشی را تغییر دهید.', $remaining),
                ]);
            }

            // Validate
            if (mb_strlen($display_name) < 3 || mb_strlen($display_name) > 20) {
                wp_send_json_error(['message' => 'نام نمایشی باید بین ۳ تا ۲۰ کاراکتر باشد.']);
            }
            if (!preg_match('/^[\p{Arabic}a-zA-Z0-9_ ]+$/u', $display_name)) {
                wp_send_json_error(['message' => 'نام نمایشی شامل کاراکتر غیرمجاز است.']);
            }

            // Check uniqueness
            $clean = mb_strtolower($display_name);
            global $wpdb;
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->usermeta} 
                 WHERE meta_key = 'novel_display_name_clean' AND meta_value = %s AND user_id != %d",
                $clean, $user_id
            ));

            if ($exists > 0) {
                wp_send_json_error(['message' => 'این نام نمایشی قبلاً استفاده شده.']);
            }

            wp_update_user(['ID' => $user_id, 'display_name' => $display_name]);
            update_user_meta($user_id, 'novel_display_name_clean', $clean);
            update_user_meta($user_id, 'novel_last_name_change', time());
        }

        // Bio
        if (isset($_POST['bio'])) {
            $bio = sanitize_textarea_field(mb_substr(trim($_POST['bio']), 0, 200));
            update_user_meta($user_id, 'novel_bio', $bio);
        }

        // Telegram
        if (isset($_POST['telegram'])) {
            $telegram = sanitize_text_field(trim($_POST['telegram']));
            if (!empty($telegram) && !preg_match('/^@?[a-zA-Z0-9_]{5,32}$/', $telegram)) {
                wp_send_json_error(['message' => 'نام کاربری تلگرام نامعتبر است.']);
            }
            if (!empty($telegram) && $telegram[0] !== '@') {
                $telegram = '@' . $telegram;
            }
            update_user_meta($user_id, 'novel_telegram', $telegram);
        }

        // Instagram
        if (isset($_POST['instagram'])) {
            $instagram = sanitize_text_field(trim($_POST['instagram']));
            if (!empty($instagram) && !preg_match('/^@?[a-zA-Z0-9_.]{1,30}$/', $instagram)) {
                wp_send_json_error(['message' => 'نام کاربری اینستاگرام نامعتبر است.']);
            }
            if (!empty($instagram) && $instagram[0] !== '@') {
                $instagram = '@' . $instagram;
            }
            update_user_meta($user_id, 'novel_instagram', $instagram);
        }

        // Profile Color
        if (isset($_POST['profile_color'])) {
            $allowed_colors = ['purple', 'blue', 'green', 'red', 'orange', 'pink', 'teal', 'gray'];
            $color = sanitize_text_field($_POST['profile_color']);
            if (in_array($color, $allowed_colors)) {
                update_user_meta($user_id, 'novel_profile_color', $color);
            }
        }

        // Refresh user data
        $user = get_userdata($user_id);

        wp_send_json_success([
            'message'      => 'تغییرات با موفقیت ذخیره شد ✓',
            'display_name' => $user->display_name,
            'avatar_url'   => Novel_Avatars::get_avatar_url_static($user_id),
        ]);
    }

    // ========================================
    // CHANGE PASSWORD
    // ========================================

    public function change_password() {
        $this->verify_request();

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        $current = isset($_POST['current_password']) ? $_POST['current_password'] : '';
        $new = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirm = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

        if (empty($current) || empty($new) || empty($confirm)) {
            wp_send_json_error(['message' => 'تمام فیلدها الزامی است.']);
        }

        if (!wp_check_password($current, $user->user_pass, $user_id)) {
            wp_send_json_error(['message' => 'رمز عبور فعلی اشتباه است.']);
        }

        if (strlen($new) < 8) {
            wp_send_json_error(['message' => 'رمز عبور جدید باید حداقل ۸ کاراکتر باشد.']);
        }
        if (!preg_match('/[0-9]/', $new)) {
            wp_send_json_error(['message' => 'رمز عبور باید حداقل شامل ۱ عدد باشد.']);
        }
        if (!preg_match('/[A-Z]/', $new)) {
            wp_send_json_error(['message' => 'رمز عبور باید حداقل شامل ۱ حرف بزرگ باشد.']);
        }
        if ($new !== $confirm) {
            wp_send_json_error(['message' => 'رمزهای عبور مطابقت ندارند.']);
        }
        if ($current === $new) {
            wp_send_json_error(['message' => 'رمز جدید نباید با رمز فعلی یکسان باشد.']);
        }

        wp_set_password($new, $user_id);

        // Re-login
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);

        // Send notification email
        $this->send_password_changed_notification($user);

        wp_send_json_success(['message' => 'رمز عبور با موفقیت تغییر کرد ✓']);
    }

    // ========================================
    // EMAIL CHANGE
    // ========================================

    public function initiate_email_change() {
        $this->verify_request();

        $user_id = get_current_user_id();
        $new_email = isset($_POST['new_email']) ? sanitize_email(trim($_POST['new_email'])) : '';

        if (empty($new_email) || !is_email($new_email)) {
            wp_send_json_error(['message' => 'ایمیل معتبر وارد کنید.']);
        }

        $user = get_userdata($user_id);
        if ($new_email === $user->user_email) {
            wp_send_json_error(['message' => 'ایمیل جدید با ایمیل فعلی یکسان است.']);
        }

        if (email_exists($new_email)) {
            wp_send_json_error(['message' => 'این ایمیل قبلاً در سایت ثبت شده.']);
        }

        // Rate limiting
        $rate_key = 'novel_email_change_' . $user_id;
        if (get_transient($rate_key)) {
            wp_send_json_error(['message' => 'لطفاً کمی صبر کنید قبل از درخواست مجدد.']);
        }
        set_transient($rate_key, 1, 120);

        // Generate 6-digit code
        $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        update_user_meta($user_id, 'novel_email_change_code', $code);
        update_user_meta($user_id, 'novel_email_change_new', $new_email);
        update_user_meta($user_id, 'novel_email_change_expiry', time() + 900); // 15 min

        // Send code to new email
        $site_name = get_bloginfo('name');
        $subject = sprintf('کد تأیید تغییر ایمیل - %s', $site_name);

        $body = $this->render_email('email-change', [
            'display_name' => $user->display_name,
            'code'         => $code,
            'site_name'    => $site_name,
            'site_url'     => home_url('/'),
        ]);

        wp_mail($new_email, $subject, $body, ['Content-Type: text/html; charset=UTF-8']);

        // Notify current email
        $notify_subject = sprintf('درخواست تغییر ایمیل - %s', $site_name);
        $notify_body = sprintf(
            '<div dir="rtl" style="font-family:Tahoma,sans-serif;padding:20px;">
            <p>سلام %s،</p>
            <p>درخواست تغییر ایمیل حساب شما به <strong>%s</strong> ثبت شد.</p>
            <p>اگر شما این درخواست را نداده‌اید، لطفاً فوراً رمز عبور خود را تغییر دهید.</p>
            </div>',
            esc_html($user->display_name),
            esc_html($new_email)
        );
        wp_mail($user->user_email, $notify_subject, $notify_body, ['Content-Type: text/html; charset=UTF-8']);

        wp_send_json_success(['message' => 'کد تأیید به ایمیل جدید ارسال شد.']);
    }

    public function confirm_email_change() {
        $this->verify_request();

        $user_id = get_current_user_id();
        $code = isset($_POST['code']) ? sanitize_text_field(trim($_POST['code'])) : '';

        if (empty($code) || strlen($code) !== 6) {
            wp_send_json_error(['message' => 'کد تأیید ۶ رقمی وارد کنید.']);
        }

        $stored_code = get_user_meta($user_id, 'novel_email_change_code', true);
        $new_email = get_user_meta($user_id, 'novel_email_change_new', true);
        $expiry = (int) get_user_meta($user_id, 'novel_email_change_expiry', true);

        if (time() > $expiry) {
            wp_send_json_error(['message' => 'کد تأیید منقضی شده. لطفاً دوباره تلاش کنید.']);
        }

        if ($code !== $stored_code) {
            wp_send_json_error(['message' => 'کد تأیید اشتباه است.']);
        }

        // Update email
        wp_update_user(['ID' => $user_id, 'user_email' => $new_email]);
        update_user_meta($user_id, 'novel_email_verified', 1);

        // Clean up
        delete_user_meta($user_id, 'novel_email_change_code');
        delete_user_meta($user_id, 'novel_email_change_new');
        delete_user_meta($user_id, 'novel_email_change_expiry');

        wp_send_json_success([
            'message' => 'ایمیل با موفقیت تغییر کرد ✓',
            'email'   => $new_email,
        ]);
    }

    // ========================================
    // NOTIFICATION SETTINGS
    // ========================================

    public function save_notification_settings() {
        $this->verify_request();

        $user_id = get_current_user_id();

        $types = [
            'comment_reply', 'comment_like', 'new_follower',
            'new_chapter', 'new_novel_author', 'mention',
            'report_result', 'system_message', 'coin_expiry',
        ];

        $settings = [];
        foreach ($types as $type) {
            $settings[$type] = [
                'site'  => !empty($_POST['notif_site_' . $type]),
                'email' => !empty($_POST['notif_email_' . $type]),
            ];
        }

        update_user_meta($user_id, 'novel_notification_settings', $settings);

        wp_send_json_success(['message' => 'تنظیمات اعلان ذخیره شد ✓']);
    }

    // ========================================
    // PRIVACY SETTINGS
    // ========================================

    public function save_privacy_settings() {
        $this->verify_request();

        $user_id = get_current_user_id();

        $privacy = [
            'show_reading_list'  => !empty($_POST['show_reading_list']),
            'show_achievements'  => !empty($_POST['show_achievements']),
            'show_comment_stats' => !empty($_POST['show_comment_stats']),
        ];

        update_user_meta($user_id, 'novel_privacy_settings', $privacy);

        wp_send_json_success(['message' => 'تنظیمات حریم خصوصی ذخیره شد ✓']);
    }

    // ========================================
    // DELETE ACCOUNT
    // ========================================

    public function delete_account() {
        $this->verify_request();

        // Check if admin allows deletion
        $allow_delete = get_option('novel_allow_account_deletion', true);
        if (!$allow_delete) {
            wp_send_json_error(['message' => 'حذف حساب توسط مدیر غیرفعال شده. با پشتیبانی تماس بگیرید.']);
        }

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        // Don't allow admin deletion through this endpoint
        if (user_can($user_id, 'manage_options')) {
            wp_send_json_error(['message' => 'حساب مدیر قابل حذف از این طریق نیست.']);
        }

        // Verify password
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        if (empty($password) || !wp_check_password($password, $user->user_pass, $user_id)) {
            wp_send_json_error(['message' => 'رمز عبور اشتباه است.']);
        }

        // Check deactivation period
        $wait_days = (int) get_option('novel_deletion_wait_days', 0);
        if ($wait_days > 0) {
            // Deactivate instead of delete
            update_user_meta($user_id, 'novel_deactivated', 1);
            update_user_meta($user_id, 'novel_deactivation_date', time());
            update_user_meta($user_id, 'novel_deletion_scheduled', time() + ($wait_days * 86400));

            wp_logout();

            wp_send_json_success([
                'message'  => sprintf('حساب شما غیرفعال شد. حذف نهایی تا %d روز دیگر. برای بازگشت تا آن زمان وارد شوید.', $wait_days),
                'redirect' => home_url('/'),
            ]);
            return;
        }

        // Perform immediate deletion
        $this->perform_account_deletion($user_id, $user);

        wp_send_json_success([
            'message'  => 'حساب شما حذف شد. به امید دیدار...',
            'redirect' => home_url('/'),
        ]);
    }

    /**
     * Perform the actual account deletion
     */
    public function perform_account_deletion($user_id, $user = null) {
        global $wpdb;

        if (!$user) {
            $user = get_userdata($user_id);
        }
        if (!$user) return false;

        $admin_id = $this->get_admin_id();
        $novel_behavior = get_option('novel_deletion_novel_behavior', 'reassign'); // reassign|delete|draft

        // ① Anonymize comments (don't delete - preserve discussions)
        $wpdb->update(
            $wpdb->comments,
            [
                'comment_author'       => 'کاربر حذف‌شده',
                'comment_author_email' => '',
                'comment_author_url'   => '',
                'comment_author_IP'    => '',
                'user_id'              => 0,
            ],
            ['user_id' => $user_id],
            ['%s', '%s', '%s', '%s', '%d'],
            ['%d']
        );

        // ② Handle novels
        $novels = get_posts([
            'post_type'   => 'novel',
            'author'      => $user_id,
            'numberposts' => -1,
            'post_status' => 'any',
        ]);

        foreach ($novels as $novel) {
            switch ($novel_behavior) {
                case 'delete':
                    // Delete chapters first
                    $chapters = get_posts([
                        'post_type'   => 'chapter',
                        'meta_key'    => '_novel_id',
                        'meta_value'  => $novel->ID,
                        'numberposts' => -1,
                        'post_status' => 'any',
                    ]);
                    foreach ($chapters as $ch) {
                        wp_delete_post($ch->ID, true);
                    }
                    wp_delete_post($novel->ID, true);
                    break;

                case 'draft':
                    wp_update_post(['ID' => $novel->ID, 'post_status' => 'draft', 'post_author' => $admin_id]);
                    break;

                case 'reassign':
                default:
                    wp_update_post(['ID' => $novel->ID, 'post_author' => $admin_id]);
                    break;
            }
        }

        // ③ Clean custom tables
        $custom_tables = [
            $wpdb->prefix . 'user_library',
            $wpdb->prefix . 'reading_history',
            $wpdb->prefix . 'user_coins',
            $wpdb->prefix . 'user_follows',
            $wpdb->prefix . 'novel_follows',
            $wpdb->prefix . 'notifications',
            $wpdb->prefix . 'user_achievements',
            $wpdb->prefix . 'quiz_attempts',
            $wpdb->prefix . 'author_banners',
        ];

        foreach ($custom_tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
                $wpdb->delete($table, ['user_id' => $user_id], ['%d']);
            }
        }

        // Clean follows (both directions)
        $follow_table = $wpdb->prefix . 'user_follows';
        if ($wpdb->get_var("SHOW TABLES LIKE '$follow_table'") === $follow_table) {
            $wpdb->delete($follow_table, ['follower_id' => $user_id], ['%d']);
            $wpdb->delete($follow_table, ['following_id' => $user_id], ['%d']);
        }

        // ④ Author earnings
        $earnings_table = $wpdb->prefix . 'author_earnings';
        if ($wpdb->get_var("SHOW TABLES LIKE '$earnings_table'") === $earnings_table) {
            $wpdb->update(
                $earnings_table,
                ['status' => 'cancelled'],
                ['user_id' => $user_id],
                ['%s'],
                ['%d']
            );
        }

        $payouts_table = $wpdb->prefix . 'author_payouts';
        if ($wpdb->get_var("SHOW TABLES LIKE '$payouts_table'") === $payouts_table) {
            $wpdb->update(
                $payouts_table,
                ['status' => 'cancelled'],
                ['user_id' => $user_id, 'status' => 'pending'],
                ['%s'],
                ['%d', '%s']
            );
        }

        // ⑤ Log deletion (without personal info)
        $log_table = $wpdb->prefix . 'novel_logs';
        if ($wpdb->get_var("SHOW TABLES LIKE '$log_table'") === $log_table) {
            $wpdb->insert($log_table, [
                'action'     => 'account_deleted',
                'details'    => wp_json_encode(['user_id' => $user_id, 'date' => current_time('mysql')]),
                'created_at' => current_time('mysql'),
            ]);
        }

        // ⑥ Send farewell email (before deletion)
        $site_name = get_bloginfo('name');
        wp_mail(
            $user->user_email,
            sprintf('حساب شما حذف شد - %s', $site_name),
            sprintf(
                '<div dir="rtl" style="font-family:Tahoma,sans-serif;padding:20px;">
                <h2>حساب شما حذف شد</h2>
                <p>سلام %s، حساب کاربری شما در %s حذف شد.</p>
                <p>اگر شما این کار را انجام نداده‌اید، فوراً با پشتیبانی تماس بگیرید.</p>
                <p>امیدواریم دوباره شما را ببینیم. 💜</p>
                </div>',
                esc_html($user->display_name),
                esc_html($site_name)
            ),
            ['Content-Type: text/html; charset=UTF-8']
        );

        // ⑦ Logout before deletion
        wp_logout();

        // ⑧ Delete user (reassign posts to admin)
        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user($user_id, $admin_id);

        return true;
    }

    // ========================================
    // DASHBOARD TAB LOADER
    // ========================================

    public function load_dashboard_tab() {
        $this->verify_request();

        $tab = isset($_POST['tab']) ? sanitize_text_field($_POST['tab']) : 'overview';

        $tab_map = [
            'overview'      => 'templates/dashboard/overview.php',
            'profile'       => 'templates/dashboard/profile-edit.php',
            'settings'      => 'templates/dashboard/settings.php',
            'library'       => 'templates/dashboard/my-bookmarks.php',
            'history'       => 'templates/dashboard/my-history.php',
            'following'     => 'templates/dashboard/my-following.php',
            'comments'      => 'templates/dashboard/my-comments.php',
            'notifications' => 'templates/dashboard/my-notifications.php',
            'followers'     => 'templates/dashboard/my-followers.php',
            'achievements'  => 'templates/dashboard/my-achievements.php',
            'coins'         => 'templates/dashboard/my-coins.php',
            'subscription'  => 'templates/dashboard/my-subscription.php',
            'contests'      => 'templates/dashboard/my-contests.php',
            'my-novels'     => 'templates/dashboard/my-novels.php',
            'my-chapters'   => 'templates/dashboard/my-chapters.php',
            'author-stats'  => 'templates/dashboard/author-stats.php',
            'author-income' => 'templates/dashboard/author-income.php',
            'author-banners'=> 'templates/dashboard/author-banners.php',
        ];

        $template_file = isset($tab_map[$tab]) ? $tab_map[$tab] : $tab_map['overview'];
        $template_path = get_template_directory() . '/' . $template_file;

        if (!file_exists($template_path)) {
            echo '<div class="dashboard__error"><p>این بخش هنوز در دسترس نیست.</p></div>';
            wp_die();
        }

        ob_start();
        include $template_path;
        echo ob_get_clean();
        wp_die();
    }

    // ========================================
    // REST API
    // ========================================

    public function register_rest_routes() {
        register_rest_route('novel/v1', '/users/me', [
            'methods'             => 'DELETE',
            'callback'            => [$this, 'rest_delete_account'],
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ]);
    }

    public function rest_delete_account(\WP_REST_Request $request) {
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        if (user_can($user_id, 'manage_options')) {
            return new \WP_REST_Response(['message' => 'Admin cannot be deleted.'], 403);
        }

        $password = $request->get_param('password');
        $confirm = $request->get_param('confirm');

        if (!$confirm) {
            return new \WP_REST_Response(['message' => 'Confirmation required.'], 400);
        }

        if (!wp_check_password($password, $user->user_pass, $user_id)) {
            return new \WP_REST_Response(['message' => 'Invalid password.'], 401);
        }

        $this->perform_account_deletion($user_id, $user);

        return new \WP_REST_Response(['message' => 'Account deleted.', 'redirect' => home_url('/')], 200);
    }

    // ========================================
    // UTILITIES
    // ========================================

    private function verify_request() {
        if (!check_ajax_referer('novel_auth_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'توکن امنیتی نامعتبر است.']);
        }
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'لطفاً ابتدا وارد شوید.']);
        }
    }

    private function get_admin_id() {
        $admins = get_users(['role' => 'administrator', 'number' => 1, 'orderby' => 'ID', 'order' => 'ASC']);
        return !empty($admins) ? $admins[0]->ID : 1;
    }

    private function send_password_changed_notification($user) {
        if (!class_exists('Novel_Auth')) return;
        $auth = Novel_Auth::get_instance();
        // Use reflection or just inline the email
        $site_name = get_bloginfo('name');
        wp_mail(
            $user->user_email,
            sprintf('رمز عبور شما تغییر کرد - %s', $site_name),
            sprintf(
                '<div dir="rtl" style="font-family:Tahoma,sans-serif;padding:20px;">
                <h2>رمز عبور تغییر کرد ✅</h2>
                <p>سلام %s، رمز عبور حساب شما تغییر کرد.</p>
                <p>زمان: %s</p>
                <p>اگر شما این تغییر را انجام نداده‌اید، فوراً با پشتیبانی تماس بگیرید.</p>
                </div>',
                esc_html($user->display_name),
                date_i18n('Y/m/d H:i')
            ),
            ['Content-Type: text/html; charset=UTF-8']
        );
    }

    private function render_email($template, $data) {
        $path = get_template_directory() . '/inc/email-templates/' . $template . '.php';
        if (!file_exists($path)) {
            return '<p>' . ($data['display_name'] ?? '') . '</p>';
        }
        ob_start();
        extract($data, EXTR_SKIP);
        include $path;
        return ob_get_clean();
    }

    private function is_dashboard_page() {
        $uri = trim($_SERVER['REQUEST_URI'] ?? '', '/');
        return strpos($uri, 'dashboard') !== false;
    }

    /**
     * Get default notification settings
     */
    public static function get_default_notification_settings() {
        return [
            'comment_reply'    => ['site' => true, 'email' => true],
            'comment_like'     => ['site' => true, 'email' => false],
            'new_follower'     => ['site' => true, 'email' => false],
            'new_chapter'      => ['site' => true, 'email' => true],
            'new_novel_author' => ['site' => true, 'email' => false],
            'mention'          => ['site' => true, 'email' => true],
            'report_result'    => ['site' => true, 'email' => false],
            'system_message'   => ['site' => true, 'email' => true],
            'coin_expiry'      => ['site' => true, 'email' => true],
        ];
    }

    /**
     * Get user notification settings
     */
    public static function get_user_notification_settings($user_id) {
        $settings = get_user_meta($user_id, 'novel_notification_settings', true);
        if (!is_array($settings)) {
            return self::get_default_notification_settings();
        }
        return wp_parse_args($settings, self::get_default_notification_settings());
    }

    /**
     * Get user privacy settings
     */
    public static function get_user_privacy_settings($user_id) {
        $defaults = [
            'show_reading_list'  => false,
            'show_achievements'  => true,
            'show_comment_stats' => true,
        ];
        $settings = get_user_meta($user_id, 'novel_privacy_settings', true);
        if (!is_array($settings)) {
            return $defaults;
        }
        return wp_parse_args($settings, $defaults);
    }
}