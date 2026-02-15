<?php
/**
 * Novel Auth System
 * 
 * Handles registration, login, email verification, password reset
 * Rate limiting, security, and AJAX handlers
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Novel_Auth {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Rate limit defaults
     */
    const REGISTER_LIMIT = 3;          // per hour per IP
    const REGISTER_WINDOW = 3600;      // 1 hour
    const LOGIN_LIMIT = 5;             // per 15 min per IP+user
    const LOGIN_WINDOW = 900;          // 15 minutes
    const VERIFY_RESEND_COOLDOWN = 120; // seconds
    const RESET_TOKEN_EXPIRY = 3600;   // 1 hour
    const VERIFY_TOKEN_EXPIRY = 86400; // 24 hours

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - Register all hooks
     */
    public function __construct() {
        // AJAX handlers - logged out
        add_action('wp_ajax_nopriv_novel_register', [$this, 'register_user']);
        add_action('wp_ajax_nopriv_novel_login', [$this, 'login_user']);
        add_action('wp_ajax_nopriv_novel_forgot_password', [$this, 'forgot_password']);
        add_action('wp_ajax_nopriv_novel_reset_password', [$this, 'reset_password']);
        add_action('wp_ajax_nopriv_novel_check_display_name', [$this, 'check_duplicate_display_name']);
        add_action('wp_ajax_nopriv_novel_check_email', [$this, 'check_duplicate_email']);

        // AJAX handlers - logged in
        add_action('wp_ajax_novel_resend_verify', [$this, 'resend_verify']);
        add_action('wp_ajax_novel_check_display_name', [$this, 'check_duplicate_display_name']);

        // Email verification via GET
        add_action('template_redirect', [$this, 'handle_verify_email']);

        // Redirect wp-login.php
        add_action('login_init', [$this, 'redirect_wp_login']);

        // Enqueue auth assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_auth_assets']);

        // Register shortcodes for auth pages
        add_shortcode('novel_login', [$this, 'shortcode_login']);
        add_shortcode('novel_register', [$this, 'shortcode_register']);
        add_shortcode('novel_forgot_password', [$this, 'shortcode_forgot_password']);
        add_shortcode('novel_reset_password', [$this, 'shortcode_reset_password']);
        add_shortcode('novel_verify_email', [$this, 'shortcode_verify_email']);

        // Custom rewrite rules
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'handle_auth_pages']);

        // Set custom HTML content type for emails
        add_filter('wp_mail_content_type', [$this, 'set_html_content_type']);
    }

    /**
     * Add rewrite rules for auth pages
     */
    public function add_rewrite_rules() {
        add_rewrite_rule('^login/?$', 'index.php?novel_auth_page=login', 'top');
        add_rewrite_rule('^register/?$', 'index.php?novel_auth_page=register', 'top');
        add_rewrite_rule('^forgot-password/?$', 'index.php?novel_auth_page=forgot-password', 'top');
        add_rewrite_rule('^reset-password/?$', 'index.php?novel_auth_page=reset-password', 'top');
        add_rewrite_rule('^verify-email/?$', 'index.php?novel_auth_page=verify-email', 'top');
    }

    /**
     * Add custom query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'novel_auth_page';
        $vars[] = 'novel_verify';
        $vars[] = 'novel_reset';
        return $vars;
    }

    /**
     * Handle auth page template loading
     */
    public function handle_auth_pages() {
        $auth_page = get_query_var('novel_auth_page');
        if (empty($auth_page)) {
            return;
        }

        // If logged in, redirect away from login/register
        if (is_user_logged_in() && in_array($auth_page, ['login', 'register'])) {
            wp_redirect(home_url('/dashboard/'));
            exit;
        }

        $template_map = [
            'login'           => 'templates/auth/login.php',
            'register'        => 'templates/auth/register.php',
            'forgot-password' => 'templates/auth/forgot-password.php',
            'reset-password'  => 'templates/auth/reset-password.php',
            'verify-email'    => 'templates/auth/verify-email.php',
        ];

        if (isset($template_map[$auth_page])) {
            $template = get_template_directory() . '/' . $template_map[$auth_page];
            if (file_exists($template)) {
                include $template;
                exit;
            }
        }
    }

    /**
     * Enqueue auth-specific assets
     */
    public function enqueue_auth_assets() {
        $auth_page = get_query_var('novel_auth_page');
        if (empty($auth_page)) {
            return;
        }

        wp_enqueue_style(
            'novel-auth',
            get_template_directory_uri() . '/assets/css/auth.css',
            ['novel-main-style'],
            SUSPENDED_STARTER_VERSION
        );

        wp_enqueue_script(
            'novel-auth',
            get_template_directory_uri() . '/assets/js/auth.js',
            ['jquery'],
            SUSPENDED_STARTER_VERSION,
            true
        );

        wp_localize_script('novel-auth', 'novelAuth', [
            'ajaxUrl'    => admin_url('admin-ajax.php'),
            'nonce'      => wp_create_nonce('novel_auth_nonce'),
            'homeUrl'    => home_url('/'),
            'loginUrl'   => home_url('/login/'),
            'registerUrl'=> home_url('/register/'),
            'verifyUrl'  => home_url('/verify-email/'),
            'dashboardUrl' => home_url('/dashboard/'),
            'i18n'       => [
                'registering'       => 'در حال ثبت‌نام...',
                'loggingIn'         => 'در حال ورود...',
                'sending'           => 'در حال ارسال...',
                'resetting'         => 'در حال بازنشانی...',
                'nameAvailable'     => 'نام در دسترس است',
                'nameTaken'         => 'این نام قبلاً استفاده شده',
                'emailTaken'        => 'این ایمیل قبلاً ثبت شده.',
                'emailAvailable'    => 'ایمیل در دسترس است',
                'passwordWeak'      => 'ضعیف',
                'passwordFair'      => 'متوسط',
                'passwordGood'      => 'خوب',
                'passwordStrong'    => 'قوی',
                'passwordsMatch'    => 'رمزها مطابقت دارند',
                'passwordsMismatch' => 'رمزهای عبور مطابقت ندارند',
                'fieldRequired'     => 'این فیلد الزامی است',
                'invalidEmail'      => 'ایمیل معتبر نیست',
                'checkingName'      => 'در حال بررسی...',
                'checkingEmail'     => 'در حال بررسی...',
                'rateLimited'       => 'تعداد تلاش‌های شما زیاد است. %s دقیقه صبر کنید.',
                'genericError'      => 'خطایی رخ داد. لطفاً دوباره تلاش کنید.',
                'resendCooldown'    => 'ارسال مجدد تا %s ثانیه دیگر',
                'linkResent'        => 'لینک جدید ارسال شد',
            ],
        ]);
    }

    // ========================================
    // REGISTRATION
    // ========================================

    /**
     * AJAX: Register a new user
     */
    public function register_user() {
        // Verify nonce
        if (!check_ajax_referer('novel_auth_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'توکن امنیتی نامعتبر است. لطفاً صفحه را رفرش کنید.']);
        }

        // Honeypot check
        if (!empty($_POST['website_url'])) {
            wp_send_json_success(['message' => 'ثبت‌نام موفق! لینک تأیید به ایمیل شما ارسال شد.']);
            return; // Silently reject bots
        }

        // Rate limiting
        $ip = $this->get_client_ip();
        $rate_key = 'novel_reg_' . md5($ip);
        $attempts = (int) get_transient($rate_key);
        
        if ($attempts >= self::REGISTER_LIMIT) {
            wp_send_json_error([
                'message' => 'تعداد ثبت‌نام‌های شما بیش از حد مجاز است. لطفاً ۱ ساعت دیگر تلاش کنید.',
                'code'    => 'rate_limited'
            ]);
        }

        // Validate
        $validation = $this->validate_register($_POST);
        if (is_wp_error($validation)) {
            wp_send_json_error([
                'message' => $validation->get_error_message(),
                'code'    => $validation->get_error_code(),
                'field'   => $validation->get_error_data(),
            ]);
        }

        $display_name = sanitize_text_field(trim($_POST['display_name']));
        $email = sanitize_email(trim($_POST['email']));
        $password = $_POST['password'];

        // Generate username from email
        $username = $this->generate_username($email);

        // Create user
        $user_id = wp_insert_user([
            'user_login'   => $username,
            'user_email'   => $email,
            'user_pass'    => $password,
            'display_name' => $display_name,
            'role'         => 'subscriber',
        ]);

        if (is_wp_error($user_id)) {
            wp_send_json_error(['message' => 'خطا در ایجاد حساب کاربری. لطفاً دوباره تلاش کنید.']);
        }

        // Set user meta
        $verify_token = wp_generate_password(32, false);
        
        update_user_meta($user_id, 'novel_email_verified', 0);
        update_user_meta($user_id, 'novel_email_verify_token', $verify_token);
        update_user_meta($user_id, 'novel_email_verify_expiry', time() + self::VERIFY_TOKEN_EXPIRY);
        update_user_meta($user_id, 'novel_registration_ip', $ip);
        update_user_meta($user_id, 'novel_custom_avatar', 'avatar-1');
        update_user_meta($user_id, 'novel_registered_at', current_time('mysql'));
        update_user_meta($user_id, 'novel_display_name_clean', mb_strtolower($display_name));

        // Send verification email
        $this->send_verify_email($user_id, $email, $display_name, $verify_token);

        // Send welcome email  
        $this->send_welcome_email($user_id, $email, $display_name);

        // Record rate limit
        set_transient($rate_key, $attempts + 1, self::REGISTER_WINDOW);

        // Auto login
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);

        wp_send_json_success([
            'message'  => 'ثبت‌نام موفق! لینک تأیید به ایمیل شما ارسال شد.',
            'redirect' => home_url('/verify-email/'),
        ]);
    }

    /**
     * Validate registration data
     */
    private function validate_register($data) {
        // Display name
        $display_name = isset($data['display_name']) ? trim($data['display_name']) : '';
        if (empty($display_name)) {
            return new WP_Error('empty_display_name', 'نام نمایشی الزامی است.', 'display_name');
        }
        if (mb_strlen($display_name) < 3) {
            return new WP_Error('short_display_name', 'نام نمایشی باید حداقل ۳ کاراکتر باشد.', 'display_name');
        }
        if (mb_strlen($display_name) > 20) {
            return new WP_Error('long_display_name', 'نام نمایشی نباید بیش از ۲۰ کاراکتر باشد.', 'display_name');
        }
        // Allow Persian, English, numbers, underscore, space
        if (!preg_match('/^[\p{Arabic}a-zA-Z0-9_ ]+$/u', $display_name)) {
            return new WP_Error('invalid_display_name', 'نام نمایشی فقط می‌تواند شامل حروف فارسی، انگلیسی، عدد، فاصله و آندرلاین باشد.', 'display_name');
        }
        // Check uniqueness (case-insensitive)
        if ($this->is_display_name_taken($display_name)) {
            return new WP_Error('duplicate_display_name', 'این نام نمایشی قبلاً استفاده شده.', 'display_name');
        }

        // Email
        $email = isset($data['email']) ? trim($data['email']) : '';
        if (empty($email) || !is_email($email)) {
            return new WP_Error('invalid_email', 'ایمیل معتبر وارد کنید.', 'email');
        }
        if (email_exists($email)) {
            return new WP_Error('duplicate_email', 'این ایمیل قبلاً ثبت شده.', 'email');
        }

        // Password
        $password = isset($data['password']) ? $data['password'] : '';
        if (strlen($password) < 8) {
            return new WP_Error('weak_password', 'رمز عبور باید حداقل ۸ کاراکتر باشد.', 'password');
        }
        if (!preg_match('/[0-9]/', $password)) {
            return new WP_Error('weak_password', 'رمز عبور باید حداقل شامل ۱ عدد باشد.', 'password');
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return new WP_Error('weak_password', 'رمز عبور باید حداقل شامل ۱ حرف بزرگ انگلیسی باشد.', 'password');
        }

        // Confirm password
        $confirm = isset($data['password_confirm']) ? $data['password_confirm'] : '';
        if ($password !== $confirm) {
            return new WP_Error('password_mismatch', 'رمزهای عبور مطابقت ندارند.', 'password_confirm');
        }

        // Terms acceptance
        if (empty($data['accept_terms'])) {
            return new WP_Error('terms_not_accepted', 'پذیرش قوانین سایت الزامی است.', 'accept_terms');
        }

        return true;
    }

    /**
     * Check if display name is taken (case-insensitive)
     */
    private function is_display_name_taken($name, $exclude_user_id = 0) {
        global $wpdb;
        
        $clean_name = mb_strtolower(trim($name));
        
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->users} u 
             INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id 
             WHERE um.meta_key = 'novel_display_name_clean' 
             AND um.meta_value = %s",
            $clean_name
        );

        if ($exclude_user_id > 0) {
            $query = $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->users} u 
                 INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id 
                 WHERE um.meta_key = 'novel_display_name_clean' 
                 AND um.meta_value = %s 
                 AND u.ID != %d",
                $clean_name,
                $exclude_user_id
            );
        }

        return (int) $wpdb->get_var($query) > 0;
    }

    /**
     * Generate username from email
     */
    private function generate_username($email) {
        $base = strstr($email, '@', true);
        $base = sanitize_user($base, true);
        
        if (empty($base)) {
            $base = 'user';
        }

        $username = $base;
        $counter = 1;
        while (username_exists($username)) {
            $username = $base . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * Generate display name suggestions
     */
    private function generate_name_suggestions($name) {
        $suggestions = [];
        $base = trim($name);
        
        // Add random numbers
        $suggestions[] = $base . rand(10, 99);
        $suggestions[] = $base . '_' . rand(100, 999);
        $suggestions[] = $base . rand(1000, 9999);
        
        // Filter out taken ones
        $available = [];
        foreach ($suggestions as $suggestion) {
            if (!$this->is_display_name_taken($suggestion) && mb_strlen($suggestion) <= 20) {
                $available[] = $suggestion;
            }
        }
        
        return array_slice($available, 0, 3);
    }

    // ========================================
    // LOGIN
    // ========================================

    /**
     * AJAX: Login user
     */
    public function login_user() {
        if (!check_ajax_referer('novel_auth_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'توکن امنیتی نامعتبر است.']);
        }

        $login = isset($_POST['login']) ? sanitize_text_field(trim($_POST['login'])) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $remember = !empty($_POST['remember']);

        if (empty($login) || empty($password)) {
            wp_send_json_error(['message' => 'ایمیل و رمز عبور الزامی است.']);
        }

        // Rate limiting
        $ip = $this->get_client_ip();
        $rate_key = 'novel_login_' . md5($ip . $login);
        $attempts = (int) get_transient($rate_key);

        if ($attempts >= self::LOGIN_LIMIT) {
            $ttl = $this->get_transient_ttl($rate_key);
            $minutes = max(1, ceil($ttl / 60));
            wp_send_json_error([
                'message'   => sprintf('تعداد تلاش‌های شما زیاد است. %d دقیقه صبر کنید.', $minutes),
                'code'      => 'rate_limited',
                'wait_time' => $ttl,
            ]);
        }

        // Determine if login is email or username
        $user_login = $login;
        if (strpos($login, '@') !== false) {
            $user = get_user_by('email', $login);
            if ($user) {
                $user_login = $user->user_login;
            }
        }

        $credentials = [
            'user_login'    => $user_login,
            'user_password' => $password,
            'remember'      => $remember,
        ];

        $user = wp_signon($credentials, is_ssl());

        if (is_wp_error($user)) {
            // Record failed attempt
            $this->record_failed_login($rate_key, $attempts);
            
            // Generic error message for security
            $remaining = self::LOGIN_LIMIT - ($attempts + 1);
            $msg = 'ایمیل/نام کاربری یا رمز عبور اشتباه است.';
            if ($remaining > 0 && $remaining <= 2) {
                $msg .= sprintf(' (%d تلاش باقی‌مانده)', $remaining);
            }
            
            wp_send_json_error(['message' => $msg]);
        }

        // Successful login - clear rate limit
        delete_transient($rate_key);

        // Check email verification
        $verified = self::is_email_verified($user->ID);
        $redirect = isset($_POST['redirect_to']) ? esc_url_raw($_POST['redirect_to']) : '';

        if (!$verified) {
            $redirect = home_url('/verify-email/');
        } elseif (empty($redirect)) {
            $redirect = home_url('/dashboard/');
        }

        wp_send_json_success([
            'message'        => 'ورود موفق!',
            'redirect'       => $redirect,
            'email_verified' => $verified,
        ]);
    }

    /**
     * Record a failed login attempt
     */
    private function record_failed_login($rate_key, $current_attempts) {
        set_transient($rate_key, $current_attempts + 1, self::LOGIN_WINDOW);
    }

    /**
     * Check login rate limit
     */
    private function check_rate_limit($key, $limit, $window) {
        $attempts = (int) get_transient($key);
        return $attempts >= $limit;
    }

    // ========================================
    // EMAIL VERIFICATION
    // ========================================

    /**
     * Handle email verification link (GET request)
     */
    public function handle_verify_email() {
        if (!isset($_GET['novel_verify']) || empty($_GET['novel_verify'])) {
            return;
        }

        $token = sanitize_text_field($_GET['novel_verify']);
        
        // Find user with this token
        $users = get_users([
            'meta_key'   => 'novel_email_verify_token',
            'meta_value' => $token,
            'number'     => 1,
        ]);

        if (empty($users)) {
            // Redirect to verify page with error
            wp_redirect(add_query_arg('verify_status', 'invalid', home_url('/verify-email/')));
            exit;
        }

        $user = $users[0];
        $expiry = (int) get_user_meta($user->ID, 'novel_email_verify_expiry', true);

        if (time() > $expiry) {
            wp_redirect(add_query_arg([
                'verify_status' => 'expired',
                'user_id'       => $user->ID,
            ], home_url('/verify-email/')));
            exit;
        }

        // Verify the email
        update_user_meta($user->ID, 'novel_email_verified', 1);
        delete_user_meta($user->ID, 'novel_email_verify_token');
        delete_user_meta($user->ID, 'novel_email_verify_expiry');

        // Auto-login if not logged in
        if (!is_user_logged_in()) {
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID, true);
        }

        wp_redirect(add_query_arg('verify_status', 'success', home_url('/verify-email/')));
        exit;
    }

    /**
     * AJAX: Resend verification email
     */
    public function resend_verify() {
        if (!check_ajax_referer('novel_auth_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'توکن امنیتی نامعتبر است.']);
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'لطفاً ابتدا وارد شوید.']);
        }

        $user_id = get_current_user_id();
        
        // Check if already verified
        if (self::is_email_verified($user_id)) {
            wp_send_json_error(['message' => 'ایمیل شما قبلاً تأیید شده است.']);
        }

        // Cooldown check
        $cooldown_key = 'novel_verify_resend_' . $user_id;
        $last_sent = get_transient($cooldown_key);
        if ($last_sent) {
            $remaining = self::VERIFY_RESEND_COOLDOWN - (time() - $last_sent);
            if ($remaining > 0) {
                wp_send_json_error([
                    'message'   => sprintf('لطفاً %d ثانیه صبر کنید.', $remaining),
                    'cooldown'  => $remaining,
                ]);
            }
        }

        $user = get_userdata($user_id);
        $verify_token = wp_generate_password(32, false);

        update_user_meta($user_id, 'novel_email_verify_token', $verify_token);
        update_user_meta($user_id, 'novel_email_verify_expiry', time() + self::VERIFY_TOKEN_EXPIRY);

        $this->send_verify_email($user_id, $user->user_email, $user->display_name, $verify_token);

        // Set cooldown
        set_transient($cooldown_key, time(), self::VERIFY_RESEND_COOLDOWN);

        wp_send_json_success([
            'message'  => 'لینک تأیید جدید به ایمیل شما ارسال شد.',
            'cooldown' => self::VERIFY_RESEND_COOLDOWN,
        ]);
    }

    /**
     * Send verification email
     */
    private function send_verify_email($user_id, $email, $display_name, $token) {
        $verify_url = add_query_arg('novel_verify', $token, home_url('/'));
        $site_name = get_bloginfo('name');

        $subject = sprintf('تأیید ایمیل - %s', $site_name);

        // Load email template
        $body = $this->render_email_template('verify', [
            'display_name' => $display_name,
            'verify_url'   => $verify_url,
            'site_name'    => $site_name,
            'site_url'     => home_url('/'),
            'expiry_hours' => 24,
        ]);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            sprintf('From: %s <%s>', $site_name, get_option('admin_email')),
        ];

        wp_mail($email, $subject, $body, $headers);
    }

    /**
     * Send welcome email
     */
    private function send_welcome_email($user_id, $email, $display_name) {
        $site_name = get_bloginfo('name');
        $subject = sprintf('به %s خوش آمدید! 🎉', $site_name);

        $body = $this->render_email_template('welcome', [
            'display_name' => $display_name,
            'site_name'    => $site_name,
            'site_url'     => home_url('/'),
            'login_url'    => home_url('/login/'),
            'dashboard_url'=> home_url('/dashboard/'),
        ]);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            sprintf('From: %s <%s>', $site_name, get_option('admin_email')),
        ];

        wp_mail($email, $subject, $body, $headers);
    }

    // ========================================
    // PASSWORD RESET
    // ========================================

    /**
     * AJAX: Forgot password - send reset link
     */
    public function forgot_password() {
        if (!check_ajax_referer('novel_auth_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'توکن امنیتی نامعتبر است.']);
        }

        $email = isset($_POST['email']) ? sanitize_email(trim($_POST['email'])) : '';

        // Rate limiting
        $ip = $this->get_client_ip();
        $rate_key = 'novel_reset_' . md5($ip);
        $attempts = (int) get_transient($rate_key);

        if ($attempts >= 3) {
            // Same success message for security
            wp_send_json_success([
                'message' => 'اگر حسابی با این ایمیل وجود دارد، لینک بازنشانی ارسال شد.',
            ]);
            return;
        }

        set_transient($rate_key, $attempts + 1, 3600);

        // Always show same message (security)
        $generic_message = 'اگر حسابی با این ایمیل وجود دارد، لینک بازنشانی ارسال شد.';

        if (empty($email) || !is_email($email)) {
            wp_send_json_success(['message' => $generic_message]);
            return;
        }

        $user = get_user_by('email', $email);
        if (!$user) {
            wp_send_json_success(['message' => $generic_message]);
            return;
        }

        // Generate reset token
        $reset_token = wp_generate_password(32, false);
        update_user_meta($user->ID, 'novel_reset_token', $reset_token);
        update_user_meta($user->ID, 'novel_reset_expiry', time() + self::RESET_TOKEN_EXPIRY);

        // Send email
        $reset_url = add_query_arg([
            'novel_reset' => $reset_token,
            'email'       => urlencode($email),
        ], home_url('/reset-password/'));

        $site_name = get_bloginfo('name');
        $subject = sprintf('بازنشانی رمز عبور - %s', $site_name);

        $body = $this->render_email_template('reset-password', [
            'display_name' => $user->display_name,
            'reset_url'    => $reset_url,
            'site_name'    => $site_name,
            'site_url'     => home_url('/'),
            'expiry_hours' => 1,
        ]);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            sprintf('From: %s <%s>', $site_name, get_option('admin_email')),
        ];

        wp_mail($email, $subject, $body, $headers);

        wp_send_json_success(['message' => $generic_message]);
    }

    /**
     * AJAX: Reset password with token
     */
    public function reset_password() {
        if (!check_ajax_referer('novel_auth_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'توکن امنیتی نامعتبر است.']);
        }

        $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';

        if (empty($token) || empty($email) || empty($password)) {
            wp_send_json_error(['message' => 'اطلاعات ناقص است.']);
        }

        // Validate password
        if (strlen($password) < 8) {
            wp_send_json_error(['message' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.', 'field' => 'password']);
        }
        if (!preg_match('/[0-9]/', $password)) {
            wp_send_json_error(['message' => 'رمز عبور باید حداقل شامل ۱ عدد باشد.', 'field' => 'password']);
        }
        if (!preg_match('/[A-Z]/', $password)) {
            wp_send_json_error(['message' => 'رمز عبور باید حداقل شامل ۱ حرف بزرگ باشد.', 'field' => 'password']);
        }
        if ($password !== $confirm) {
            wp_send_json_error(['message' => 'رمزهای عبور مطابقت ندارند.', 'field' => 'password_confirm']);
        }

        // Find user
        $user = get_user_by('email', $email);
        if (!$user) {
            wp_send_json_error(['message' => 'لینک بازنشانی نامعتبر است.']);
        }

        // Verify token
        $stored_token = get_user_meta($user->ID, 'novel_reset_token', true);
        $expiry = (int) get_user_meta($user->ID, 'novel_reset_expiry', true);

        if ($token !== $stored_token) {
            wp_send_json_error(['message' => 'لینک بازنشانی نامعتبر است.']);
        }
        if (time() > $expiry) {
            wp_send_json_error(['message' => 'لینک بازنشانی منقضی شده. لطفاً دوباره درخواست دهید.']);
        }

        // Update password
        wp_set_password($password, $user->ID);

        // Clean up
        delete_user_meta($user->ID, 'novel_reset_token');
        delete_user_meta($user->ID, 'novel_reset_expiry');

        // Send confirmation email
        $this->send_password_changed_email($user);

        // Auto-login
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);

        wp_send_json_success([
            'message'  => 'رمز عبور با موفقیت تغییر کرد!',
            'redirect' => home_url('/dashboard/'),
        ]);
    }

    /**
     * Send password changed confirmation email
     */
    private function send_password_changed_email($user) {
        $site_name = get_bloginfo('name');
        $subject = sprintf('رمز عبور شما تغییر کرد - %s', $site_name);

        $body = $this->render_email_template('password-changed', [
            'display_name' => $user->display_name,
            'site_name'    => $site_name,
            'site_url'     => home_url('/'),
            'change_time'  => date_i18n('Y/m/d H:i', current_time('timestamp')),
            'ip_address'   => $this->get_client_ip(),
        ]);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            sprintf('From: %s <%s>', $site_name, get_option('admin_email')),
        ];

        wp_mail($user->user_email, $subject, $body, $headers);
    }

    // ========================================
    // DISPLAY NAME CHECK
    // ========================================

    /**
     * AJAX: Check if display name is available
     */
    public function check_duplicate_display_name() {
        if (!check_ajax_referer('novel_auth_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'توکن نامعتبر.']);
        }

        $name = isset($_POST['display_name']) ? sanitize_text_field(trim($_POST['display_name'])) : '';

        if (mb_strlen($name) < 3) {
            wp_send_json_error(['message' => 'حداقل ۳ کاراکتر وارد کنید.']);
        }

        $exclude_id = is_user_logged_in() ? get_current_user_id() : 0;
        $taken = $this->is_display_name_taken($name, $exclude_id);

        if ($taken) {
            $suggestions = $this->generate_name_suggestions($name);
            wp_send_json_error([
                'message'     => 'این نام قبلاً استفاده شده.',
                'suggestions' => $suggestions,
            ]);
        }

        wp_send_json_success(['message' => 'نام در دسترس است.']);
    }

    /**
     * AJAX: Check if email is available
     */
    public function check_duplicate_email() {
        if (!check_ajax_referer('novel_auth_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'توکن نامعتبر.']);
        }

        $email = isset($_POST['email']) ? sanitize_email(trim($_POST['email'])) : '';

        if (empty($email) || !is_email($email)) {
            wp_send_json_error(['message' => 'ایمیل معتبر وارد کنید.']);
        }

        if (email_exists($email)) {
            wp_send_json_error([
                'message'   => 'این ایمیل قبلاً ثبت شده.',
                'login_url' => home_url('/login/'),
            ]);
        }

        wp_send_json_success(['message' => 'ایمیل در دسترس است.']);
    }

    // ========================================
    // UTILITY METHODS
    // ========================================

    /**
     * Check if user's email is verified
     */
    public static function is_email_verified($user_id) {
        // Admins are always verified
        if (user_can($user_id, 'manage_options')) {
            return true;
        }
        return (int) get_user_meta($user_id, 'novel_email_verified', true) === 1;
    }

    /**
     * Check if current action requires email verification
     * Use in other modules before allowing actions
     */
    public static function require_verified_email() {
        if (!is_user_logged_in()) {
            wp_send_json_error([
                'message' => 'لطفاً ابتدا وارد شوید.',
                'code'    => 'not_logged_in',
                'login_url' => home_url('/login/'),
            ]);
        }

        if (!self::is_email_verified(get_current_user_id())) {
            wp_send_json_error([
                'message'    => 'ابتدا ایمیل خود را تأیید کنید.',
                'code'       => 'email_not_verified',
                'verify_url' => home_url('/verify-email/'),
            ]);
        }
    }

    /**
     * Redirect wp-login.php to custom pages
     */
    public function redirect_wp_login() {
        // Don't redirect AJAX, admin, or specific actions
        if (
            defined('DOING_AJAX') ||
            isset($_GET['action']) && in_array($_GET['action'], ['logout', 'postpass', 'rp', 'resetpass', 'lostpassword', 'confirmaction']) ||
            isset($_GET['checkemail']) ||
            is_admin()
        ) {
            return;
        }

        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'login';

        switch ($action) {
            case 'register':
                wp_redirect(home_url('/register/'));
                exit;
            case 'lostpassword':
                wp_redirect(home_url('/forgot-password/'));
                exit;
            default:
                wp_redirect(home_url('/login/'));
                exit;
        }
    }

    /**
     * Render email template
     */
    private function render_email_template($template_name, $data = []) {
        $template_path = get_template_directory() . '/inc/email-templates/' . $template_name . '.php';
        
        if (!file_exists($template_path)) {
            // Fallback to simple HTML
            return $this->fallback_email($template_name, $data);
        }

        ob_start();
        extract($data, EXTR_SKIP);
        include $template_path;
        return ob_get_clean();
    }

    /**
     * Fallback email if template missing
     */
    private function fallback_email($type, $data) {
        $site_name = $data['site_name'] ?? get_bloginfo('name');
        
        switch ($type) {
            case 'verify':
                return sprintf(
                    '<div dir="rtl" style="font-family:Tahoma,sans-serif;padding:20px;">
                    <h2>سلام %s! 👋</h2>
                    <p>از عضویت شما در %s خوشحالیم.</p>
                    <p>برای تأیید ایمیل، روی لینک زیر کلیک کنید:</p>
                    <p><a href="%s" style="background:#6366f1;color:#fff;padding:12px 24px;text-decoration:none;border-radius:8px;display:inline-block;">تأیید ایمیل ✓</a></p>
                    <p>یا لینک زیر را کپی کنید:<br><code>%s</code></p>
                    <p>این لینک %d ساعت اعتبار دارد.</p>
                    </div>',
                    esc_html($data['display_name']),
                    esc_html($site_name),
                    esc_url($data['verify_url']),
                    esc_url($data['verify_url']),
                    $data['expiry_hours']
                );
            default:
                return '<p>' . esc_html($data['display_name'] ?? '') . '</p>';
        }
    }

    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle comma-separated IPs (proxies)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '127.0.0.1';
    }

    /**
     * Get remaining TTL for a transient
     */
    private function get_transient_ttl($key) {
        global $wpdb;
        
        $transient_timeout = $wpdb->get_var($wpdb->prepare(
            "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
            '_transient_timeout_' . $key
        ));

        if ($transient_timeout) {
            return max(0, (int) $transient_timeout - time());
        }

        return 0;
    }

    /**
     * Set HTML content type for emails
     */
    public function set_html_content_type($content_type) {
        return 'text/html';
    }

    // ========================================
    // SHORTCODES (fallback for page-based approach)
    // ========================================

    public function shortcode_login() {
        if (is_user_logged_in()) {
            return '<p>شما قبلاً وارد شده‌اید. <a href="' . esc_url(home_url('/dashboard/')) . '">داشبورد</a></p>';
        }
        ob_start();
        include get_template_directory() . '/templates/auth/login.php';
        return ob_get_clean();
    }

    public function shortcode_register() {
        if (is_user_logged_in()) {
            return '<p>شما قبلاً عضو هستید. <a href="' . esc_url(home_url('/dashboard/')) . '">داشبورد</a></p>';
        }
        ob_start();
        include get_template_directory() . '/templates/auth/register.php';
        return ob_get_clean();
    }

    public function shortcode_forgot_password() {
        ob_start();
        include get_template_directory() . '/templates/auth/forgot-password.php';
        return ob_get_clean();
    }

    public function shortcode_reset_password() {
        ob_start();
        include get_template_directory() . '/templates/auth/reset-password.php';
        return ob_get_clean();
    }

    public function shortcode_verify_email() {
        ob_start();
        include get_template_directory() . '/templates/auth/verify-email.php';
        return ob_get_clean();
    }
}