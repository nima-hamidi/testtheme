<?php
/**
 * سیستم احراز هویت سفارشی - نسخه ۴ اصلاح‌شده
 * رفع: Edge nonce + کیبورد موبایل + تغییر ایمیل دو مرحله‌ای + محدودیت ۶ ماهه + تغییر رمز
 */
if (!defined('ABSPATH')) exit;

// ═══════════════════════════════════════
// ثابت‌ها
// ═══════════════════════════════════════
if (!defined('NOVEL_MAX_LOGIN_ATTEMPTS'))        define('NOVEL_MAX_LOGIN_ATTEMPTS', 5);
if (!defined('NOVEL_LOGIN_LOCKOUT_MINUTES'))      define('NOVEL_LOGIN_LOCKOUT_MINUTES', 15);
if (!defined('NOVEL_EMAIL_VERIFY_EXPIRY_HOURS'))  define('NOVEL_EMAIL_VERIFY_EXPIRY_HOURS', 24);
if (!defined('NOVEL_RESEND_COOLDOWN_SECONDS'))    define('NOVEL_RESEND_COOLDOWN_SECONDS', 180);
if (!defined('NOVEL_MAX_RESEND_PER_DAY'))         define('NOVEL_MAX_RESEND_PER_DAY', 3);
if (!defined('NOVEL_AVATAR_COUNT'))               define('NOVEL_AVATAR_COUNT', 114);
if (!defined('NOVEL_AVATAR_DIR'))                 define('NOVEL_AVATAR_DIR', get_template_directory() . '/avatars/');
if (!defined('NOVEL_AVATAR_URL'))                 define('NOVEL_AVATAR_URL', get_template_directory_uri() . '/avatars/');
if (!defined('NOVEL_DISPLAY_NAME_MIN'))           define('NOVEL_DISPLAY_NAME_MIN', 5);
if (!defined('NOVEL_DISPLAY_NAME_MAX'))           define('NOVEL_DISPLAY_NAME_MAX', 20);
if (!defined('NOVEL_EMAIL_CHANGE_DAYS'))          define('NOVEL_EMAIL_CHANGE_DAYS', 180);
if (!defined('NOVEL_EMAIL_CHANGE_CODE_EXPIRY'))   define('NOVEL_EMAIL_CHANGE_CODE_EXPIRY', 600);
if (!defined('NOVEL_EMAIL_CHANGE_MAX_PER_DAY'))   define('NOVEL_EMAIL_CHANGE_MAX_PER_DAY', 3);

// ═══════════════════════════════════════
// هوک‌ها
// ═══════════════════════════════════════
add_action('init', 'novel_auth_init');
add_action('template_redirect', 'novel_auth_template_redirect');
add_action('login_init', 'novel_redirect_wp_login_page');
add_action('wp_enqueue_scripts', 'novel_auth_enqueue_assets');

// AJAX — کاربران لاگین‌نشده
add_action('wp_ajax_nopriv_novel_login', 'novel_ajax_login');
add_action('wp_ajax_nopriv_novel_register', 'novel_ajax_register');
add_action('wp_ajax_nopriv_novel_check_display_name', 'novel_ajax_check_display_name');
add_action('wp_ajax_nopriv_novel_resend_verification', 'novel_ajax_resend_verification');
add_action('wp_ajax_nopriv_novel_reset_password_request', 'novel_ajax_reset_password_request');
add_action('wp_ajax_nopriv_novel_reset_password_do', 'novel_ajax_reset_password_do');

// AJAX — کاربران لاگین‌شده
add_action('wp_ajax_novel_check_display_name', 'novel_ajax_check_display_name');
add_action('wp_ajax_novel_resend_verification', 'novel_ajax_resend_verification');
add_action('wp_ajax_novel_save_avatar', 'novel_ajax_save_avatar');
add_action('wp_ajax_novel_update_profile', 'novel_ajax_update_profile');
add_action('wp_ajax_novel_change_password', 'novel_ajax_change_password');
add_action('wp_ajax_novel_send_email_change_code', 'novel_ajax_send_email_change_code');
add_action('wp_ajax_novel_verify_email_change_code', 'novel_ajax_verify_email_change_code');

// Cron — حذف حساب‌های تأیید نشده
add_action('novel_cleanup_unverified', 'novel_cleanup_unverified_accounts');
if (!wp_next_scheduled('novel_cleanup_unverified')) {
    wp_schedule_event(time(), 'hourly', 'novel_cleanup_unverified');
}

// ایمیل پاسخ دیدگاه
add_action('comment_post', 'novel_notify_comment_reply', 20, 3);

// ═══════════════════════════════════════
// ریدایرکت wp-login.php — روی login_init
// ═══════════════════════════════════════
function novel_redirect_wp_login_page() {
    // اجازه AJAX و POST
    if (defined('DOING_AJAX') && DOING_AJAX) return;
    if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) return;
    
    $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
    
    // اجازه لاگ‌اوت
    if ($action === 'logout') return;
    
    // اجازه POST برای لاگین واقعی (پلاگین‌ها)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($action)) return;
    
    $login_page = novel_get_page_url('login');
    $register_page = novel_get_page_url('register');
    $forgot_page = novel_get_page_url('forgot-password');
    
    if (!$login_page) return;
    
    if ($action === 'register' && $register_page) {
        wp_safe_redirect($register_page);
        exit;
    } elseif (($action === 'lostpassword' || $action === 'rp') && $forgot_page) {
        wp_safe_redirect($forgot_page);
        exit;
    } elseif ($action === '' || $action === 'login') {
        $redirect_to = isset($_GET['redirect_to']) ? esc_url_raw(wp_unslash($_GET['redirect_to'])) : '';
        $url = $redirect_to ? add_query_arg('redirect_to', urlencode($redirect_to), $login_page) : $login_page;
        wp_safe_redirect($url);
        exit;
    }
}

function novel_auth_template_redirect() {
    // خالی — ریدایرکت به login_init منتقل شد
}

// ═══════════════════════════════════════
// URL صفحه بر اساس slug
// ═══════════════════════════════════════
function novel_get_page_url($slug) {
    $page = get_page_by_path($slug);
    return $page ? get_permalink($page) : '';
}

// ═══════════════════════════════════════
// اعتبارسنجی نام نمایشی
// ═══════════════════════════════════════
function novel_validate_display_name($name) {
    $name = trim($name);
    $len = mb_strlen($name, 'UTF-8');
    
    if ($len < NOVEL_DISPLAY_NAME_MIN) {
        return array(
            'valid' => false,
            'message' => sprintf(
                __('نام نمایشی حداقل %d کاراکتر باشد.', 'flavor'),
                NOVEL_DISPLAY_NAME_MIN
            ),
        );
    }
    
    if ($len > NOVEL_DISPLAY_NAME_MAX) {
        return array(
            'valid' => false,
            'message' => sprintf(
                __('نام نمایشی حداکثر %d کاراکتر باشد.', 'flavor'),
                NOVEL_DISPLAY_NAME_MAX
            ),
        );
    }
    
    // فقط حروف فارسی + فاصله + نیم‌فاصله + خط تیره
    if (!preg_match('/^[\x{0600}-\x{06FF}\x{200C}\s\-]+$/u', $name)) {
        return array(
            'valid' => false,
            'message' => __('نام نمایشی فقط می‌تواند شامل حروف فارسی، فاصله و خط تیره (-) باشد.', 'flavor'),
        );
    }
    
    if (mb_substr($name, 0, 1) === '-' || mb_substr($name, -1) === '-') {
        return array(
            'valid' => false,
            'message' => __('خط تیره نمی‌تواند در ابتدا یا انتهای نام باشد.', 'flavor'),
        );
    }
    
    if (strpos($name, '--') !== false) {
        return array(
            'valid' => false,
            'message' => __('دو خط تیره پشت سر هم مجاز نیست.', 'flavor'),
        );
    }
    
    return array('valid' => true, 'message' => '');
}

function novel_is_display_name_unique($name, $exclude_user_id = 0) {
    global $wpdb;
    $query = "SELECT COUNT(*) FROM {$wpdb->users} WHERE LOWER(display_name) = LOWER(%s)";
    $params = array($name);
    if ($exclude_user_id > 0) {
        $query .= " AND ID != %d";
        $params[] = $exclude_user_id;
    }
    return (int) $wpdb->get_var($wpdb->prepare($query, ...$params)) === 0;
}

// ═══════════════════════════════════════
// AJAX: بررسی نام نمایشی
// ═══════════════════════════════════════
function novel_ajax_check_display_name() {
    novel_verify_nonce_flexible();
    
    $name = sanitize_text_field(wp_unslash($_POST['display_name'] ?? ''));
    $exclude_id = is_user_logged_in() ? get_current_user_id() : 0;
    
    $validation = novel_validate_display_name($name);
    if (!$validation['valid']) {
        wp_send_json_error(array('message' => $validation['message'], 'type' => 'validation'));
    }
    
    if (!novel_is_display_name_unique($name, $exclude_id)) {
        wp_send_json_error(array(
            'message' => __('این نام نمایشی قبلاً استفاده شده.', 'flavor'),
            'type' => 'taken',
        ));
    }
    
    wp_send_json_success(array('message' => __('✓ نام نمایشی در دسترس است.', 'flavor')));
}

// ═══════════════════════════════════════
// Init
// ═══════════════════════════════════════
function novel_auth_init() {
    if (isset($_GET['novel_verify_email'], $_GET['token'])) {
        novel_handle_email_verification();
    }
    if (isset($_GET['novel_verify_email_change'], $_GET['token'])) {
        novel_handle_email_change_link();
    }
}

// ═══════════════════════════════════════
// تابع کمکی: بررسی nonce سازگار با همه مرورگرها
// ═══════════════════════════════════════
function novel_verify_nonce_flexible() {
    $nonce_valid = false;
    
    // بررسی از POST
    $nonce_fields = array('nonce', '_wpnonce', 'security');
    foreach ($nonce_fields as $field) {
        if (!empty($_POST[$field])) {
            $val = sanitize_text_field(wp_unslash($_POST[$field]));
            if (wp_verify_nonce($val, 'novel_auth_nonce')) {
                $nonce_valid = true;
                break;
            }
        }
    }
    
    // بررسی از Header (برای Edge و سایر مرورگرها)
    if (!$nonce_valid && !empty($_SERVER['HTTP_X_WP_NONCE'])) {
        $val = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_WP_NONCE']));
        if (wp_verify_nonce($val, 'novel_auth_nonce')) {
            $nonce_valid = true;
        }
    }
    
    if (!$nonce_valid) {
        wp_send_json_error(array(
            'message' => __('نشست شما منقضی شده. لطفاً صفحه را رفرش کرده و دوباره تلاش کنید.', 'flavor'),
            'code' => 'nonce_failed',
        ));
    }
}

// ═══════════════════════════════════════
// AJAX: ورود — سازگار با Edge/Chrome/Firefox
// ═══════════════════════════════════════
function novel_ajax_login() {
    novel_verify_nonce_flexible();
    
    // Honeypot
    if (!empty($_POST['website_url'])) {
        wp_send_json_error(array('message' => __('خطا. لطفاً دوباره تلاش کنید.', 'flavor')));
    }
    
    // Rate limiting
    $ip = novel_get_client_ip();
    $rate_key = 'novel_login_attempts_' . md5($ip);
    $attempts = (int) get_transient($rate_key);
    
    if ($attempts >= NOVEL_MAX_LOGIN_ATTEMPTS) {
        wp_send_json_error(array(
            'message' => sprintf(
                __('تلاش‌های ورود بیش از حد. لطفاً %d دقیقه صبر کنید.', 'flavor'),
                NOVEL_LOGIN_LOCKOUT_MINUTES
            ),
        ));
    }
    
    $email    = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $remember = !empty($_POST['remember']);
    
    if (empty($email) || empty($password)) {
        wp_send_json_error(array('message' => __('ایمیل و رمز عبور الزامی است.', 'flavor')));
    }
    
    $user = get_user_by('email', $email);
    if (!$user) {
        set_transient($rate_key, $attempts + 1, NOVEL_LOGIN_LOCKOUT_MINUTES * MINUTE_IN_SECONDS);
        wp_send_json_error(array('message' => __('ایمیل یا رمز عبور اشتباه است.', 'flavor')));
    }
    
    // بررسی رمز عبور — پیام واضح
    if (!wp_check_password($password, $user->user_pass, $user->ID)) {
        set_transient($rate_key, $attempts + 1, NOVEL_LOGIN_LOCKOUT_MINUTES * MINUTE_IN_SECONDS);
        wp_send_json_error(array('message' => __('رمز عبور اشتباه است. لطفاً دوباره تلاش کنید.', 'flavor')));
    }
    
    // تأیید ایمیل
    $verified = (int) get_user_meta($user->ID, 'email_verified', true);
    if ($verified !== 1) {
        $verify_page = novel_get_page_url('verify-email');
        $verify_url = $verify_page ? add_query_arg('email', urlencode($user->user_email), $verify_page) : '';
        wp_send_json_error(array(
            'message'     => __('ایمیل شما تأیید نشده. لطفاً ابتدا ایمیل خود را تأیید کنید.', 'flavor'),
            'redirect'    => $verify_url,
            'need_verify' => true,
        ));
    }
    
    // ورود
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID, $remember);
    do_action('wp_login', $user->user_login, $user);
    
    delete_transient($rate_key);
    
    $redirect = !empty($_POST['redirect_to']) ? esc_url_raw(wp_unslash($_POST['redirect_to'])) : '';
    if (empty($redirect)) {
        $redirect = novel_get_page_url('user-dashboard');
        if (!$redirect) $redirect = home_url('/');
    }
    
    wp_send_json_success(array(
        'message'  => __('ورود موفق! در حال انتقال...', 'flavor'),
        'redirect' => $redirect,
    ));
}

// ═══════════════════════════════════════
// AJAX: ثبت‌نام — بدون نام کاربری در دیتابیس
// ═══════════════════════════════════════
function novel_ajax_register() {
    novel_verify_nonce_flexible();
    
    // Honeypot
    if (!empty($_POST['website_url'])) {
        wp_send_json_error(array('message' => __('خطا.', 'flavor')));
    }
    
    // Rate limiting
    $ip = novel_get_client_ip();
    $rate_key = 'novel_reg_attempts_' . md5($ip);
    $attempts = (int) get_transient($rate_key);
    
    if ($attempts >= NOVEL_MAX_LOGIN_ATTEMPTS) {
        wp_send_json_error(array(
            'message' => sprintf(
                __('تلاش بیش از حد. لطفاً %d دقیقه صبر کنید.', 'flavor'),
                NOVEL_LOGIN_LOCKOUT_MINUTES
            ),
        ));
    }
    
    $display_name = sanitize_text_field(wp_unslash($_POST['display_name'] ?? ''));
    $email        = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $password     = $_POST['password'] ?? '';
    $password2    = $_POST['password_confirm'] ?? '';
    $accept_rules = !empty($_POST['accept_rules']);
    
    $errors = array();
    
    // اعتبارسنجی نام
    $name_valid = novel_validate_display_name($display_name);
    if (!$name_valid['valid']) {
        $errors[] = $name_valid['message'];
    } elseif (!novel_is_display_name_unique($display_name)) {
        $errors[] = __('این نام نمایشی قبلاً انتخاب شده.', 'flavor');
    }
    
    // ایمیل
    if (!is_email($email)) {
        $errors[] = __('ایمیل معتبر نیست.', 'flavor');
    }
    if (email_exists($email)) {
        $errors[] = __('این ایمیل قبلاً ثبت شده است. اگر حساب شماست از صفحه ورود استفاده کنید.', 'flavor');
    }
    
    // رمز
    if (strlen($password) < 8) $errors[] = __('رمز عبور حداقل ۸ کاراکتر.', 'flavor');
    if (!preg_match('/[A-Z]/', $password)) $errors[] = __('رمز عبور باید حداقل یک حرف بزرگ انگلیسی داشته باشد.', 'flavor');
    if (!preg_match('/[0-9]/', $password)) $errors[] = __('رمز عبور باید حداقل یک عدد داشته باشد.', 'flavor');
    if ($password !== $password2) $errors[] = __('تکرار رمز عبور مطابقت ندارد.', 'flavor');
    if (!$accept_rules) $errors[] = __('پذیرش قوانین الزامی است.', 'flavor');
    
    if (!empty($errors)) {
        set_transient($rate_key, $attempts + 1, NOVEL_LOGIN_LOCKOUT_MINUTES * MINUTE_IN_SECONDS);
        wp_send_json_error(array('message' => implode('<br>', $errors)));
    }
    
    // نام کاربری: تصادفی — نمی‌خوام نام کاربری در دیتابیس باشه
    $username = 'u' . wp_rand(100000, 999999) . time();
    while (username_exists($username)) {
        $username = 'u' . wp_rand(1000000, 9999999) . time();
    }
    
    $user_id = wp_create_user($username, $password, $email);
    if (is_wp_error($user_id)) {
        set_transient($rate_key, $attempts + 1, NOVEL_LOGIN_LOCKOUT_MINUTES * MINUTE_IN_SECONDS);
        wp_send_json_error(array('message' => $user_id->get_error_message()));
    }
    
    // نام نمایشی = نام انتخابی کاربر
    wp_update_user(array(
        'ID'           => $user_id,
        'display_name' => $display_name,
        'nickname'     => $display_name,
        'first_name'   => $display_name,
    ));
    
    update_user_meta($user_id, 'email_verified', 0);
    update_user_meta($user_id, 'notify_comment_reply', 1);
    update_user_meta($user_id, 'custom_avatar', wp_rand(1, NOVEL_AVATAR_COUNT));
    update_user_meta($user_id, 'registration_time', time());
    
    novel_send_verification_email($user_id);
    delete_transient($rate_key);
    
    $verify_page = novel_get_page_url('verify-email');
    $redirect = $verify_page ? add_query_arg('email', urlencode($email), $verify_page) : home_url('/');
    
    wp_send_json_success(array(
        'message'  => __('ثبت‌نام موفق! لطفاً ظرف ۲۴ ساعت ایمیل خود را تأیید کنید.', 'flavor'),
        'redirect' => $redirect,
    ));
}

// ═══════════════════════════════════════
// AJAX: بروزرسانی پروفایل
// ═══════════════════════════════════════
function novel_ajax_update_profile() {
    novel_verify_nonce_flexible();
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('ابتدا وارد شوید.', 'flavor')));
    }
    
    $user_id = get_current_user_id();
    $display_name = sanitize_text_field(wp_unslash($_POST['display_name'] ?? ''));
    
    $name_valid = novel_validate_display_name($display_name);
    if (!$name_valid['valid']) {
        wp_send_json_error(array('message' => $name_valid['message']));
    }
    
    if (!novel_is_display_name_unique($display_name, $user_id)) {
        wp_send_json_error(array('message' => __('این نام نمایشی قبلاً استفاده شده.', 'flavor')));
    }
    
    wp_update_user(array(
        'ID'           => $user_id,
        'display_name' => $display_name,
        'nickname'     => $display_name,
        'first_name'   => $display_name,
    ));
    
    wp_send_json_success(array(
        'message'      => __('پروفایل با موفقیت بروزرسانی شد.', 'flavor'),
        'display_name' => $display_name,
    ));
}

// ═══════════════════════════════════════
// AJAX: تغییر رمز عبور (انتقال از تب تنظیمات به پروفایل)
// ═══════════════════════════════════════
function novel_ajax_change_password() {
    novel_verify_nonce_flexible();
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('ابتدا وارد شوید.', 'flavor')));
    }
    
    $user_id      = get_current_user_id();
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass     = $_POST['new_password'] ?? '';
    $new_pass2    = $_POST['new_password_confirm'] ?? '';
    
    $user = get_userdata($user_id);
    
    if (!wp_check_password($current_pass, $user->user_pass, $user_id)) {
        wp_send_json_error(array('message' => __('رمز عبور فعلی اشتباه است.', 'flavor')));
    }
    
    if (strlen($new_pass) < 8) {
        wp_send_json_error(array('message' => __('رمز جدید حداقل ۸ کاراکتر.', 'flavor')));
    }
    if (!preg_match('/[A-Z]/', $new_pass)) {
        wp_send_json_error(array('message' => __('رمز جدید باید حداقل یک حرف بزرگ انگلیسی داشته باشد.', 'flavor')));
    }
    if (!preg_match('/[0-9]/', $new_pass)) {
        wp_send_json_error(array('message' => __('رمز جدید باید حداقل یک عدد داشته باشد.', 'flavor')));
    }
    if ($new_pass !== $new_pass2) {
        wp_send_json_error(array('message' => __('تکرار رمز جدید مطابقت ندارد.', 'flavor')));
    }
    if ($current_pass === $new_pass) {
        wp_send_json_error(array('message' => __('رمز جدید نباید با رمز فعلی یکسان باشد.', 'flavor')));
    }
    
    wp_set_password($new_pass, $user_id);
    
    // لاگین مجدد
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id, true);
    
    // ایمیل اطلاع
    novel_send_password_changed_email($user);
    
    wp_send_json_success(array(
        'message' => __('رمز عبور با موفقیت تغییر کرد.', 'flavor'),
    ));
}

// ═══════════════════════════════════════
// سیستم تغییر ایمیل — مرحله ۱: ارسال کد تأیید
// ═══════════════════════════════════════
function novel_ajax_send_email_change_code() {
    novel_verify_nonce_flexible();
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('ابتدا وارد شوید.', 'flavor')));
    }
    
    $user_id   = get_current_user_id();
    $new_email = sanitize_email(wp_unslash($_POST['new_email'] ?? ''));
    $user      = wp_get_current_user();
    
    if (!is_email($new_email)) {
        wp_send_json_error(array('message' => __('ایمیل معتبر وارد کنید.', 'flavor')));
    }
    if ($new_email === $user->user_email) {
        wp_send_json_error(array('message' => __('این ایمیل فعلی شماست.', 'flavor')));
    }
    if (email_exists($new_email)) {
        wp_send_json_error(array('message' => __('این ایمیل قبلاً توسط کاربر دیگری ثبت شده.', 'flavor')));
    }
    
    // بررسی محدودیت ۶ ماهه
    $last_change = (int) get_user_meta($user_id, 'last_email_change_time', true);
    if ($last_change > 0) {
        $next_allowed = $last_change + (NOVEL_EMAIL_CHANGE_DAYS * DAY_IN_SECONDS);
        if (time() < $next_allowed) {
            $remaining_days = ceil(($next_allowed - time()) / DAY_IN_SECONDS);
            wp_send_json_error(array(
                'message' => sprintf(
                    __('تغییر ایمیل هر ۶ ماه یکبار مجاز است. %d روز تا امکان تغییر بعدی باقی مانده.', 'flavor'),
                    $remaining_days
                ),
            ));
        }
    }
    
    // محدودیت روزانه
    $daily_key   = 'novel_email_change_daily_' . $user_id;
    $daily_count = (int) get_transient($daily_key);
    if ($daily_count >= NOVEL_EMAIL_CHANGE_MAX_PER_DAY) {
        wp_send_json_error(array('message' => __('حداکثر ۳ بار در روز می‌توانید کد تأیید درخواست کنید.', 'flavor')));
    }
    
    // تولید کد ۶ رقمی
    $code = str_pad(wp_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    
    update_user_meta($user_id, 'email_change_code', $code);
    update_user_meta($user_id, 'email_change_code_expiry', time() + NOVEL_EMAIL_CHANGE_CODE_EXPIRY);
    update_user_meta($user_id, 'email_change_new_email', $new_email);
    update_user_meta($user_id, 'email_change_code_verified', 0);
    // ذخیره session token برای بررسی خروج از صفحه
    update_user_meta($user_id, 'email_change_session', wp_get_session_token());
    
    set_transient($daily_key, $daily_count + 1, DAY_IN_SECONDS);
    
    // ارسال کد به ایمیل فعلی کاربر
    $site_name = get_bloginfo('name');
    $subject   = sprintf('%s | کد تأیید تغییر ایمیل', $site_name);
    
    $body = novel_get_email_template('email-change-code', array(
        'user_name' => $user->display_name,
        'code'      => $code,
        'new_email' => $new_email,
        'site_name' => $site_name,
    ));
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $site_name . ' <noreply@' . wp_parse_url(home_url(), PHP_URL_HOST) . '>',
    );
    
    $sent = wp_mail($user->user_email, $subject, $body, $headers);
    
    if ($sent) {
        $remaining = NOVEL_EMAIL_CHANGE_MAX_PER_DAY - ($daily_count + 1);
        wp_send_json_success(array(
            'message'         => sprintf(
                __('کد تأیید به ایمیل فعلی شما (%s) ارسال شد. کد ۱۰ دقیقه اعتبار دارد.', 'flavor'),
                $user->user_email
            ),
            'remaining_sends' => $remaining,
        ));
    } else {
        wp_send_json_error(array('message' => __('خطا در ارسال ایمیل.', 'flavor')));
    }
}

// ═══════════════════════════════════════
// سیستم تغییر ایمیل — مرحله ۲: تأیید کد
// ═══════════════════════════════════════
function novel_ajax_verify_email_change_code() {
    novel_verify_nonce_flexible();
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('ابتدا وارد شوید.', 'flavor')));
    }
    
    $user_id = get_current_user_id();
    $code    = sanitize_text_field(wp_unslash($_POST['code'] ?? ''));
    
    if (empty($code) || strlen($code) !== 6) {
        wp_send_json_error(array('message' => __('کد ۶ رقمی وارد کنید.', 'flavor')));
    }
    
    $stored_code    = get_user_meta($user_id, 'email_change_code', true);
    $stored_expiry  = (int) get_user_meta($user_id, 'email_change_code_expiry', true);
    $new_email      = get_user_meta($user_id, 'email_change_new_email', true);
    $stored_session = get_user_meta($user_id, 'email_change_session', true);
    
    if (!$stored_code || !$new_email) {
        wp_send_json_error(array('message' => __('ابتدا درخواست تغییر ایمیل بدهید.', 'flavor')));
    }
    
    // بررسی خروج از صفحه (session باید یکسان باشد)
    if ($stored_session && $stored_session !== wp_get_session_token()) {
        // پاک کردن کد — باید دوباره درخواست بدهد
        delete_user_meta($user_id, 'email_change_code');
        delete_user_meta($user_id, 'email_change_code_expiry');
        delete_user_meta($user_id, 'email_change_session');
        wp_send_json_error(array('message' => __('نشست تغییر کرده. لطفاً دوباره کد تأیید درخواست کنید.', 'flavor')));
    }
    
    if (time() > $stored_expiry) {
        delete_user_meta($user_id, 'email_change_code');
        delete_user_meta($user_id, 'email_change_code_expiry');
        wp_send_json_error(array('message' => __('کد منقضی شده. لطفاً کد جدید درخواست کنید.', 'flavor')));
    }
    
    if ($code !== $stored_code) {
        wp_send_json_error(array('message' => __('کد اشتباه است.', 'flavor')));
    }
    
    // بررسی مجدد ایمیل
    if (email_exists($new_email)) {
        wp_send_json_error(array('message' => __('این ایمیل اکنون توسط کاربر دیگری ثبت شده.', 'flavor')));
    }
    
    // ارسال لینک تأیید به ایمیل جدید
    $token  = wp_generate_password(48, false);
    $expiry = time() + (24 * HOUR_IN_SECONDS);
    
    update_user_meta($user_id, 'email_change_token', $token);
    update_user_meta($user_id, 'email_change_token_expiry', $expiry);
    update_user_meta($user_id, 'email_change_code_verified', 1);
    
    $verify_url = add_query_arg(array(
        'novel_verify_email_change' => '1',
        'token' => $token,
        'uid'   => $user_id,
    ), home_url('/'));
    
    $site_name = get_bloginfo('name');
    $user      = get_userdata($user_id);
    $subject   = sprintf('%s | تأیید ایمیل جدید', $site_name);
    
    $body = novel_get_email_template('email-change', array(
        'user_name'  => $user->display_name,
        'verify_url' => $verify_url,
        'new_email'  => $new_email,
        'site_name'  => $site_name,
    ));
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $site_name . ' <noreply@' . wp_parse_url(home_url(), PHP_URL_HOST) . '>',
    );
    
    $sent = wp_mail($new_email, $subject, $body, $headers);
    
    // پاک کردن کد
    delete_user_meta($user_id, 'email_change_code');
    delete_user_meta($user_id, 'email_change_code_expiry');
    delete_user_meta($user_id, 'email_change_session');
    
    if ($sent) {
        wp_send_json_success(array(
            'message' => sprintf(
                __('کد تأیید شد! لینک تأیید نهایی به ایمیل جدید (%s) ارسال شد. لطفاً ایمیل جدید را بررسی کنید.', 'flavor'),
                esc_html($new_email)
            ),
        ));
    } else {
        wp_send_json_error(array('message' => __('خطا در ارسال ایمیل به آدرس جدید.', 'flavor')));
    }
}

// ═══════════════════════════════════════
// تأیید لینک تغییر ایمیل
// ═══════════════════════════════════════
function novel_handle_email_change_link() {
    $token   = sanitize_text_field($_GET['token'] ?? '');
    $user_id = absint($_GET['uid'] ?? 0);
    
    $dashboard = novel_get_page_url('user-dashboard') ?: home_url('/');
    
    if (!$token || !$user_id) {
        wp_safe_redirect(add_query_arg('email_change', 'invalid', $dashboard));
        exit;
    }
    
    $stored_token   = get_user_meta($user_id, 'email_change_token', true);
    $stored_expiry  = (int) get_user_meta($user_id, 'email_change_token_expiry', true);
    $new_email      = get_user_meta($user_id, 'email_change_new_email', true);
    $code_verified  = (int) get_user_meta($user_id, 'email_change_code_verified', true);
    
    if ($token !== $stored_token || time() > $stored_expiry || !$new_email || $code_verified !== 1) {
        wp_safe_redirect(add_query_arg('email_change', 'invalid', $dashboard));
        exit;
    }
    
    if (email_exists($new_email)) {
        wp_safe_redirect(add_query_arg('email_change', 'taken', $dashboard));
        exit;
    }
    
    wp_update_user(array('ID' => $user_id, 'user_email' => $new_email));
    
    // ثبت زمان تغییر برای محدودیت ۶ ماهه
    update_user_meta($user_id, 'last_email_change_time', time());
    
    // پاک کردن
    delete_user_meta($user_id, 'email_change_token');
    delete_user_meta($user_id, 'email_change_token_expiry');
    delete_user_meta($user_id, 'email_change_new_email');
    delete_user_meta($user_id, 'email_change_code_verified');
    
    wp_safe_redirect(add_query_arg('email_change', 'success', $dashboard));
    exit;
}

// ═══════════════════════════════════════
// ایمیل تأیید ثبت‌نام
// ═══════════════════════════════════════
function novel_send_verification_email($user_id) {
    $user = get_userdata($user_id);
    if (!$user) return false;
    
    $token  = wp_generate_password(48, false);
    $expiry = time() + (NOVEL_EMAIL_VERIFY_EXPIRY_HOURS * HOUR_IN_SECONDS);
    
    update_user_meta($user_id, 'email_verify_token', $token);
    update_user_meta($user_id, 'email_verify_expiry', $expiry);
    
    $verify_url = add_query_arg(array(
        'novel_verify_email' => '1',
        'token' => $token,
        'uid'   => $user_id,
    ), home_url('/'));
    
    $site_name = get_bloginfo('name');
    $subject   = sprintf('فقط یک قدم تا شروع داستانت باقی مونده | %s', $site_name);
    
    $body = novel_get_email_template('verify', array(
        'user_name'  => $user->display_name,
        'verify_url' => $verify_url,
        'site_name'  => $site_name,
        'site_url'   => home_url('/'),
    ));
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $site_name . ' <noreply@' . wp_parse_url(home_url(), PHP_URL_HOST) . '>',
    );
    
    return wp_mail($user->user_email, $subject, $body, $headers);
}

function novel_handle_email_verification() {
    $token   = sanitize_text_field($_GET['token'] ?? '');
    $user_id = absint($_GET['uid'] ?? 0);
    
    if (!$token || !$user_id) {
        novel_verification_redirect('invalid');
        return;
    }
    
    $user = get_userdata($user_id);
    if (!$user) {
        novel_verification_redirect('expired');
        return;
    }
    
    $stored_token  = get_user_meta($user_id, 'email_verify_token', true);
    $stored_expiry = (int) get_user_meta($user_id, 'email_verify_expiry', true);
    
    if ($token !== $stored_token) {
        novel_verification_redirect('invalid');
        return;
    }
    
    if (time() > $stored_expiry) {
        novel_verification_redirect('expired');
        return;
    }
    
    update_user_meta($user_id, 'email_verified', 1);
    delete_user_meta($user_id, 'email_verify_token');
    delete_user_meta($user_id, 'email_verify_expiry');
    delete_user_meta($user_id, 'email_resend_count');
    
    novel_send_welcome_email($user_id);
    
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id, true);
    
    novel_verification_redirect('success');
}

function novel_verification_redirect($status) {
    $verify_page = novel_get_page_url('verify-email');
    if (!$verify_page) $verify_page = home_url('/');
    wp_safe_redirect(add_query_arg('status', $status, $verify_page));
    exit;
}

// ═══════════════════════════════════════
// ارسال مجدد
// ═══════════════════════════════════════
function novel_ajax_resend_verification() {
    novel_verify_nonce_flexible();
    
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    if (empty($email)) wp_send_json_error(array('message' => __('ایمیل الزامی.', 'flavor')));
    
    $user = get_user_by('email', $email);
    if (!$user) wp_send_json_error(array('message' => __('کاربری یافت نشد.', 'flavor')));
    
    if ((int) get_user_meta($user->ID, 'email_verified', true) === 1) {
        wp_send_json_error(array('message' => __('ایمیل قبلاً تأیید شده.', 'flavor')));
    }
    
    $resend_count = (int) get_user_meta($user->ID, 'email_resend_count', true);
    if ($resend_count >= NOVEL_MAX_RESEND_PER_DAY) {
        wp_send_json_error(array('message' => __('حداکثر ارسال مجدد امروز تمام شده.', 'flavor')));
    }
    
    $last_sent = (int) get_user_meta($user->ID, 'email_resend_last', true);
    $diff = time() - $last_sent;
    if ($diff < NOVEL_RESEND_COOLDOWN_SECONDS) {
        wp_send_json_error(array(
            'message'  => sprintf(__('لطفاً %d ثانیه صبر کنید.', 'flavor'), NOVEL_RESEND_COOLDOWN_SECONDS - $diff),
            'cooldown' => NOVEL_RESEND_COOLDOWN_SECONDS - $diff,
        ));
    }
    
    $sent = novel_send_verification_email($user->ID);
    if ($sent) {
        update_user_meta($user->ID, 'email_resend_count', $resend_count + 1);
        update_user_meta($user->ID, 'email_resend_last', time());
        wp_send_json_success(array(
            'message'         => __('ایمیل تأیید ارسال شد.', 'flavor'),
            'remaining_sends' => NOVEL_MAX_RESEND_PER_DAY - ($resend_count + 1),
            'cooldown'        => NOVEL_RESEND_COOLDOWN_SECONDS,
        ));
    } else {
        wp_send_json_error(array('message' => __('خطا در ارسال.', 'flavor')));
    }
}

// ═══════════════════════════════════════
// خوش‌آمدگویی
// ═══════════════════════════════════════
function novel_send_welcome_email($user_id) {
    $user = get_userdata($user_id);
    if (!$user) return false;
    
    $site_name = get_bloginfo('name');
    $subject   = sprintf('خوش اومدی؛ داستانت از اینجا شروع میشه 🌙 | %s', $site_name);
    
    $body = novel_get_email_template('welcome', array(
        'user_name'     => $user->display_name,
        'site_name'     => $site_name,
        'site_url'      => home_url('/'),
        'dashboard_url' => novel_get_page_url('user-dashboard') ?: home_url('/'),
        'archive_url'   => get_post_type_archive_link('novel') ?: home_url('/'),
    ));
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $site_name . ' <noreply@' . wp_parse_url(home_url(), PHP_URL_HOST) . '>',
    );
    
    return wp_mail($user->user_email, $subject, $body, $headers);
}

// ═══════════════════════════════════════
// بازیابی رمز
// ═══════════════════════════════════════
function novel_ajax_reset_password_request() {
    novel_verify_nonce_flexible();
    
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    if (!is_email($email)) wp_send_json_error(array('message' => __('ایمیل معتبر وارد کنید.', 'flavor')));
    
    $ip = novel_get_client_ip();
    $rate_key = 'novel_reset_' . md5($ip);
    $attempts = (int) get_transient($rate_key);
    if ($attempts >= 3) wp_send_json_error(array('message' => __('درخواست بیش از حد. بعداً تلاش کنید.', 'flavor')));
    
    set_transient($rate_key, $attempts + 1, 30 * MINUTE_IN_SECONDS);
    
    $user = get_user_by('email', $email);
    if ($user) {
        $key = get_password_reset_key($user);
        if (!is_wp_error($key)) novel_send_reset_password_email($user, $key);
    }
    
    wp_send_json_success(array('message' => __('اگر حسابی با این ایمیل وجود دارد، لینک بازیابی ارسال شد.', 'flavor')));
}

function novel_send_reset_password_email($user, $key) {
    $site_name  = get_bloginfo('name');
    $reset_page = novel_get_page_url('reset-password') ?: home_url('/');
    $reset_url  = add_query_arg(array(
        'novel_reset_password' => '1',
        'key'   => $key,
        'login' => rawurlencode($user->user_login),
    ), $reset_page);
    
    $subject = sprintf('بازیابی رمز عبور | %s', $site_name);
    $body    = novel_get_email_template('reset-password', array(
        'user_name' => $user->display_name,
        'reset_url' => $reset_url,
        'site_name' => $site_name,
        'site_url'  => home_url('/'),
    ));
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $site_name . ' <noreply@' . wp_parse_url(home_url(), PHP_URL_HOST) . '>',
    );
    
    wp_mail($user->user_email, $subject, $body, $headers);
}

function novel_ajax_reset_password_do() {
    novel_verify_nonce_flexible();
    
    $key       = sanitize_text_field($_POST['key'] ?? '');
    $login     = sanitize_text_field($_POST['login'] ?? '');
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password_confirm'] ?? '';
    
    if (empty($key) || empty($login) || empty($password)) {
        wp_send_json_error(array('message' => __('اطلاعات ناقص.', 'flavor')));
    }
    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        wp_send_json_error(array('message' => __('رمز: حداقل ۸ کاراکتر + ۱ حرف بزرگ + ۱ عدد', 'flavor')));
    }
    if ($password !== $password2) {
        wp_send_json_error(array('message' => __('تکرار رمز مطابقت ندارد.', 'flavor')));
    }
    
    $user = check_password_reset_key($key, $login);
    if (is_wp_error($user)) {
        wp_send_json_error(array('message' => __('لینک نامعتبر یا منقضی.', 'flavor')));
    }
    
    reset_password($user, $password);
    novel_send_password_changed_email($user);
    
    wp_send_json_success(array(
        'message'  => __('رمز تغییر کرد. در حال انتقال...', 'flavor'),
        'redirect' => novel_get_page_url('login'),
    ));
}

function novel_send_password_changed_email($user) {
    $site_name = get_bloginfo('name');
    $subject   = sprintf('رمز عبور حساب شما تغییر کرد | %s', $site_name);
    
    $body = novel_get_email_template('password-changed', array(
        'user_name'  => $user->display_name,
        'site_name'  => $site_name,
        'site_url'   => home_url('/'),
        'forgot_url' => novel_get_page_url('forgot-password') ?: home_url('/'),
        'site_email' => get_option('admin_email'),
    ));
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $site_name . ' <noreply@' . wp_parse_url(home_url(), PHP_URL_HOST) . '>',
    );
    
    wp_mail($user->user_email, $subject, $body, $headers);
}

// ═══════════════════════════════════════
// آواتار
// ═══════════════════════════════════════
function novel_get_avatar_url($user_id, $size = 64) {
    $custom = get_user_meta($user_id, 'custom_avatar', true);
    if ($custom && is_numeric($custom) && (int)$custom >= 1 && (int)$custom <= NOVEL_AVATAR_COUNT) {
        return NOVEL_AVATAR_URL . 'avatar-' . (int)$custom . '.png';
    }
    return get_avatar_url($user_id, array('size' => $size, 'default' => 'mystery'));
}

function novel_get_avatar_img($user_id, $size = 64, $extra_class = '') {
    $url  = novel_get_avatar_url($user_id, $size);
    $user = get_userdata($user_id);
    $alt  = $user ? esc_attr($user->display_name) : '';
    return sprintf(
        '<img src="%s" alt="%s" width="%d" height="%d" class="novel-avatar %s" loading="lazy" />',
        esc_url($url), $alt, (int)$size, (int)$size, esc_attr($extra_class)
    );
}

function novel_ajax_save_avatar() {
    novel_verify_nonce_flexible();
    
    if (!is_user_logged_in()) wp_send_json_error(array('message' => __('وارد شوید.', 'flavor')));
    
    $avatar_id = absint($_POST['avatar_id'] ?? 0);
    if ($avatar_id < 1 || $avatar_id > NOVEL_AVATAR_COUNT) {
        wp_send_json_error(array('message' => __('آواتار نامعتبر.', 'flavor')));
    }
    
    update_user_meta(get_current_user_id(), 'custom_avatar', $avatar_id);
    
    wp_send_json_success(array(
        'message'    => __('آواتار ذخیره شد.', 'flavor'),
        'avatar_url' => NOVEL_AVATAR_URL . 'avatar-' . $avatar_id . '.png',
    ));
}

// ═══════════════════════════════════════
// توابع کمکی
// ═══════════════════════════════════════
function novel_is_email_verified($user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    if (!$user_id) return false;
    return (int)get_user_meta($user_id, 'email_verified', true) === 1;
}

function novel_can_interact($user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    if (!$user_id) return false;
    return novel_is_email_verified($user_id);
}

function novel_cleanup_unverified_accounts() {
    $users = get_users(array(
        'meta_query' => array(
            array('key' => 'email_verified', 'value' => '0'),
            array('key' => 'registration_time', 'value' => time() - (24 * HOUR_IN_SECONDS), 'compare' => '<', 'type' => 'NUMERIC'),
        ),
        'fields' => 'ID',
    ));
    if (!empty($users)) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        foreach ($users as $uid) wp_delete_user($uid);
    }
}

// ═══════════════════════════════════════
// ایمیل پاسخ دیدگاه
// ═══════════════════════════════════════
function novel_notify_comment_reply($comment_id, $comment_approved, $commentdata) {
    if ($comment_approved !== 1 && $comment_approved !== '1') return;
    
    $comment = get_comment($comment_id);
    if (!$comment || !$comment->comment_parent) return;
    
    $parent = get_comment($comment->comment_parent);
    if (!$parent || !$parent->user_id) return;
    
    $notify = get_user_meta($parent->user_id, 'notify_comment_reply', true);
    if ($notify === '0') return;
    
    $parent_user = get_userdata($parent->user_id);
    if (!$parent_user || !$parent_user->user_email) return;
    
    if ($comment->user_id && (int)$comment->user_id === (int)$parent->user_id) return;
    
    $post      = get_post($comment->comment_post_ID);
    $site_name = get_bloginfo('name');
    $subject   = sprintf('پاسخی به دیدگاه شما 💬 | %s', $site_name);
    
    $body = novel_get_email_template('comment-reply', array(
        'parent_author_name' => $parent_user->display_name,
        'replier_name'       => $comment->comment_author ?: __('کاربر', 'flavor'),
        'reply_excerpt'      => wp_trim_words(wp_strip_all_tags($comment->comment_content), 40, '...'),
        'content_title'      => $post ? $post->post_title : '',
        'comment_url'        => get_comment_link($comment_id),
        'site_name'          => $site_name,
        'unsubscribe_url'    => novel_get_page_url('user-dashboard') ?: home_url('/'),
    ));
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $site_name . ' <noreply@' . wp_parse_url(home_url(), PHP_URL_HOST) . '>',
    );
    
    wp_mail($parent_user->user_email, $subject, $body, $headers);
}

function novel_get_client_ip() {
    foreach (array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR') as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = explode(',', sanitize_text_field(wp_unslash($_SERVER[$key])));
            return trim($ip[0]);
        }
    }
    return '127.0.0.1';
}

// ═══════════════════════════════════════
// Enqueue — بدون autofocus
// ═══════════════════════════════════════
function novel_auth_enqueue_assets() {
    wp_enqueue_style('novel-auth-css', get_template_directory_uri() . '/assets/css/auth.css', array(), wp_get_theme()->get('Version'));
    wp_enqueue_script('novel-auth-js', get_template_directory_uri() . '/assets/js/auth.js', array('jquery'), wp_get_theme()->get('Version'), true);
    
    wp_localize_script('novel-auth-js', 'novelAuth', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('novel_auth_nonce'),
        'i18n'    => array(
            'checking'          => __('در حال بررسی...', 'flavor'),
            'available'         => __('✓ نام نمایشی در دسترس است', 'flavor'),
            'taken'             => __('✗ این نام قبلاً استفاده شده', 'flavor'),
            'weak'              => __('ضعیف', 'flavor'),
            'medium'            => __('متوسط', 'flavor'),
            'strong'            => __('قوی', 'flavor'),
            'passwords_match'   => __('✓ رمز عبور مطابقت دارد', 'flavor'),
            'passwords_diff'    => __('✗ رمز عبور مطابقت ندارد', 'flavor'),
            'name_min'          => sprintf(__('حداقل %d کاراکتر فارسی', 'flavor'), NOVEL_DISPLAY_NAME_MIN),
            'name_persian_only' => __('فقط حروف فارسی و خط تیره (-) بین کلمات مجاز است', 'flavor'),
            'name_dash_invalid' => __('خط تیره فقط بین کلمات (نه اول/آخر/دوتایی)', 'flavor'),
            'saving'            => __('در حال ذخیره...', 'flavor'),
            'saved'             => __('✓ ذخیره شد', 'flavor'),
            'save_avatar'       => __('ذخیره آواتار', 'flavor'),
            'select_avatar'     => __('انتخاب آواتار 🖼', 'flavor'),
            'close'             => __('بستن', 'flavor'),
            'resend_btn'        => __('ارسال مجدد لینک تأیید', 'flavor'),
            'sending'           => __('در حال ارسال...', 'flavor'),
            'remaining_sends'   => __('ارسال‌های باقیمانده:', 'flavor'),
            'resend_wait'       => __('ارسال مجدد تا %s دیگر', 'flavor'),
        ),
    ));
}

function novel_get_email_template($type, $vars = array()) {
    $template_file = get_template_directory() . '/inc/email-templates/' . $type . '.php';
    if (!file_exists($template_file)) return '<p>Template not found: ' . esc_html($type) . '</p>';
    extract($vars, EXTR_SKIP);
    ob_start();
    include $template_file;
    return ob_get_clean();
}