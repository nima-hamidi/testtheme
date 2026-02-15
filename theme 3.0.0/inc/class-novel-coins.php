<?php
/**
 * Novel Coins System
 * 
 * سیستم سکه: موجودی + خرید قسمت + انقضا + درآمد نویسنده
 *
 * @package suspended-flavor
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

class Novel_Coins {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // AJAX
        add_action('wp_ajax_novel_purchase_chapter', [$this, 'purchase_chapter']);
        add_action('wp_ajax_novel_get_balance', [$this, 'ajax_get_balance']);
        add_action('wp_ajax_novel_get_transactions', [$this, 'ajax_get_transactions']);
        add_action('wp_ajax_novel_check_chapter_access', [$this, 'check_chapter_access']);

        // Author income
        add_action('wp_ajax_novel_request_payout', [$this, 'request_payout']);
        add_action('wp_ajax_novel_get_income_stats', [$this, 'ajax_income_stats']);

        // Cron: expire coins
        add_action('novel_cron_expire_coins', [$this, 'expire_coins']);
        if (!wp_next_scheduled('novel_cron_expire_coins')) {
            wp_schedule_event(time(), 'daily', 'novel_cron_expire_coins');
        }

        // Cron: expiry warning (3 days before)
        add_action('novel_cron_expiry_warning', [$this, 'send_expiry_warnings']);
        if (!wp_next_scheduled('novel_cron_expiry_warning')) {
            wp_schedule_event(time(), 'daily', 'novel_cron_expiry_warning');
        }

        // Enqueue
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Enqueue
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'novel-coins',
            get_template_directory_uri() . '/assets/css/coins.css',
            ['novel-main-style'],
            FLAVOR_VERSION
        );

        wp_enqueue_script(
            'novel-coins',
            get_template_directory_uri() . '/assets/js/coins.js',
            ['jquery'],
            FLAVOR_VERSION,
            true
        );

        wp_localize_script('novel-coins', 'novelCoins', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('novel_coins_nonce'),
            'strings' => [
                'purchaseSuccess' => 'قسمت با موفقیت خریداری شد! ✓',
                'insufficient'   => 'موجودی سکه کافی نیست',
                'loginRequired'  => 'برای خرید ابتدا وارد شوید',
                'confirmPurchase'=> 'آیا از خرید این قسمت مطمئنید؟',
                'balance'        => 'موجودی:',
                'price'          => 'قیمت:',
                'coins'          => 'سکه',
                'error'          => 'خطایی رخ داد',
                'payoutSent'     => 'درخواست واریز ارسال شد ✓',
            ],
        ]);
    }

    /* ═══════════════════════════════════════
       Balance Management
       ═══════════════════════════════════════ */

    /**
     * Get user balance (non-expired coins)
     */
    public static function get_balance($user_id = 0) {
        if (!$user_id) $user_id = get_current_user_id();
        if (!$user_id) return 0;

        $cache_key = 'novel_coin_balance_' . $user_id;
        $cached = get_transient($cache_key);
        if ($cached !== false) return (int) $cached;

        global $wpdb;
        $table = $wpdb->prefix . 'novel_user_coins';

        $balance = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM {$table}
             WHERE user_id = %d AND is_expired = 0
             AND (expires_at IS NULL OR expires_at > NOW())",
            $user_id
        ));

        $balance = max(0, $balance);
        set_transient($cache_key, $balance, 5 * MINUTE_IN_SECONDS);

        return $balance;
    }

    /**
     * Add coins to user
     */
    public static function add_coins($user_id, $amount, $type, $description = '', $related_id = 0) {
        if ($amount <= 0 || !$user_id) return false;

        $balance = self::get_balance($user_id);
        $new_balance = $balance + $amount;

        // Calculate expiry
        $expiry_days = (int) get_option('novel_coin_expiry_days', 0);
        $expires_at = null;
        if ($expiry_days > 0) {
            $expires_at = date('Y-m-d H:i:s', current_time('timestamp') + ($expiry_days * DAY_IN_SECONDS));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'novel_user_coins';

        $inserted = $wpdb->insert($table, [
            'user_id'       => $user_id,
            'amount'        => $amount,
            'balance_after' => $new_balance,
            'type'          => $type,
            'description'   => $description,
            'related_id'    => $related_id,
            'expires_at'    => $expires_at,
            'is_expired'    => 0,
            'created_at'    => current_time('mysql'),
        ], ['%d', '%d', '%d', '%s', '%s', '%d', '%s', '%d', '%s']);

        if ($inserted) {
            delete_transient('novel_coin_balance_' . $user_id);
            return true;
        }

        return false;
    }

    /**
     * Spend coins
     */
    public static function spend_coins($user_id, $amount, $type, $description = '', $related_id = 0) {
        if ($amount <= 0 || !$user_id) return new WP_Error('invalid', 'مقدار نامعتبر');

        $balance = self::get_balance($user_id);

        if ($balance < $amount) {
            return new WP_Error('insufficient_coins', 'موجودی سکه کافی نیست');
        }

        $new_balance = $balance - $amount;

        global $wpdb;
        $table = $wpdb->prefix . 'novel_user_coins';

        $inserted = $wpdb->insert($table, [
            'user_id'       => $user_id,
            'amount'        => -$amount,
            'balance_after' => $new_balance,
            'type'          => $type,
            'description'   => $description,
            'related_id'    => $related_id,
            'expires_at'    => null,
            'is_expired'    => 0,
            'created_at'    => current_time('mysql'),
        ], ['%d', '%d', '%d', '%s', '%s', '%d', '%s', '%d', '%s']);

        if ($inserted) {
            delete_transient('novel_coin_balance_' . $user_id);
            return true;
        }

        return new WP_Error('db_error', 'خطا در ثبت تراکنش');
    }

    /* ═══════════════════════════════════════
       Chapter Purchase
       ═══════════════════════════════════════ */

    /**
     * AJAX: Purchase chapter with coins
     */
    public function purchase_chapter() {
        check_ajax_referer('novel_coins_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'ابتدا وارد حساب شوید']);
        }

        $user_id    = get_current_user_id();
        $chapter_id = absint($_POST['chapter_id'] ?? 0);

        if (!$chapter_id || get_post_type($chapter_id) !== 'chapter') {
            wp_send_json_error(['message' => 'قسمت نامعتبر']);
        }

        // Check VIP
        $is_vip = get_post_meta($chapter_id, 'chapter_is_vip', true);
        if (!$is_vip) {
            wp_send_json_error(['message' => 'این قسمت رایگان است']);
        }

        // Check already purchased
        if (self::has_purchased($user_id, $chapter_id)) {
            wp_send_json_success(['message' => 'شما قبلاً این قسمت را خریداری کرده‌اید', 'already' => true]);
        }

        // Check subscription (subscribers get free access)
        if (class_exists('Novel_Subscriptions') && Novel_Subscriptions::has_active_subscription($user_id)) {
            wp_send_json_success(['message' => 'شما اشتراک فعال دارید', 'subscriber' => true]);
        }

        // Get price
        $price = (int) get_post_meta($chapter_id, 'chapter_coin_price', true);
        if (!$price) {
            $price = (int) get_option('novel_default_coin_price', 5);
        }

        // Spend coins
        $result = self::spend_coins(
            $user_id,
            $price,
            'chapter_purchase',
            sprintf('خرید قسمت %s', get_the_title($chapter_id)),
            $chapter_id
        );

        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message(),
                'balance' => self::get_balance($user_id),
                'price'   => $price,
            ]);
        }

        // Record purchase
        global $wpdb;
        $purchases_table = $wpdb->prefix . 'novel_chapter_purchases';
        $novel_id = (int) get_post_meta($chapter_id, 'chapter_novel_id', true);
        if (!$novel_id) $novel_id = wp_get_post_parent_id($chapter_id);

        $wpdb->insert($purchases_table, [
            'user_id'     => $user_id,
            'chapter_id'  => $chapter_id,
            'novel_id'    => $novel_id,
            'coins_spent' => $price,
            'created_at'  => current_time('mysql'),
        ], ['%d', '%d', '%d', '%d', '%s']);

        // Record author earnings
        $this->record_author_earning($chapter_id, $novel_id, $user_id, $price);

        wp_send_json_success([
            'message' => 'قسمت با موفقیت خریداری شد! ✓',
            'balance' => self::get_balance($user_id),
        ]);
    }

    /**
     * Check if user purchased chapter
     */
    public static function has_purchased($user_id, $chapter_id) {
        // Subscribers have access
        if (class_exists('Novel_Subscriptions') && Novel_Subscriptions::has_active_subscription($user_id)) {
            return true;
        }

        $cache_key = "novel_purchased_{$user_id}_{$chapter_id}";
        $cached = get_transient($cache_key);
        if ($cached !== false) return $cached === 'yes';

        global $wpdb;
        $table = $wpdb->prefix . 'novel_chapter_purchases';

        $exists = (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE user_id = %d AND chapter_id = %d",
            $user_id, $chapter_id
        ));

        set_transient($cache_key, $exists ? 'yes' : 'no', HOUR_IN_SECONDS);
        return $exists;
    }

    /**
     * Check chapter access (AJAX)
     */
    public function check_chapter_access() {
        check_ajax_referer('novel_coins_nonce', 'nonce');

        $chapter_id = absint($_POST['chapter_id'] ?? 0);
        if (!$chapter_id) {
            wp_send_json_error();
        }

        $is_vip = (bool) get_post_meta($chapter_id, 'chapter_is_vip', true);

        if (!$is_vip) {
            wp_send_json_success(['access' => true, 'is_vip' => false]);
        }

        if (!is_user_logged_in()) {
            $price = (int) get_post_meta($chapter_id, 'chapter_coin_price', true) ?: (int) get_option('novel_default_coin_price', 5);
            wp_send_json_success(['access' => false, 'is_vip' => true, 'price' => $price, 'logged_in' => false]);
        }

        $user_id = get_current_user_id();
        $has_access = self::has_purchased($user_id, $chapter_id);
        $price = (int) get_post_meta($chapter_id, 'chapter_coin_price', true) ?: (int) get_option('novel_default_coin_price', 5);

        // Author has access to own chapters
        $chapter_post = get_post($chapter_id);
        if ($chapter_post && (int) $chapter_post->post_author === $user_id) {
            $has_access = true;
        }

        wp_send_json_success([
            'access'    => $has_access,
            'is_vip'    => true,
            'price'     => $price,
            'balance'   => self::get_balance($user_id),
            'logged_in' => true,
        ]);
    }

    /* ═══════════════════════════════════════
       Coin Expiration
       ═══════════════════════════════════════ */

    /**
     * Expire coins (daily cron)
     */
    public function expire_coins() {
        global $wpdb;
        $table = $wpdb->prefix . 'novel_user_coins';

        // Find expired positive entries
        $expired_rows = $wpdb->get_results(
            "SELECT id, user_id, amount FROM {$table}
             WHERE expires_at IS NOT NULL AND expires_at <= NOW()
             AND is_expired = 0 AND amount > 0"
        );

        foreach ($expired_rows as $row) {
            // Mark as expired
            $wpdb->update($table, ['is_expired' => 1], ['id' => $row->id], ['%d'], ['%d']);

            // Insert expiration record
            $balance = self::get_balance($row->user_id);
            $new_balance = max(0, $balance - $row->amount);

            $wpdb->insert($table, [
                'user_id'       => $row->user_id,
                'amount'        => -$row->amount,
                'balance_after' => $new_balance,
                'type'          => 'expired',
                'description'   => 'انقضای سکه',
                'related_id'    => $row->id,
                'is_expired'    => 0,
                'created_at'    => current_time('mysql'),
            ], ['%d', '%d', '%d', '%s', '%s', '%d', '%d', '%s']);

            delete_transient('novel_coin_balance_' . $row->user_id);

            // Notify user
            if (class_exists('Novel_Notifications')) {
                Novel_Notifications::get_instance()->send_notification(
                    $row->user_id, 'coins',
                    'سکه‌های منقضی شده',
                    sprintf('%d سکه شما منقضی شد.', $row->amount),
                    home_url('/dashboard/?tab=coins')
                );
            }
        }
    }

    /**
     * Send expiry warnings (3 days before)
     */
    public function send_expiry_warnings() {
        global $wpdb;
        $table = $wpdb->prefix . 'novel_user_coins';

        $warning_date = date('Y-m-d H:i:s', current_time('timestamp') + (3 * DAY_IN_SECONDS));
        $today = current_time('mysql');

        $at_risk = $wpdb->get_results($wpdb->prepare(
            "SELECT user_id, SUM(amount) as total_amount
             FROM {$table}
             WHERE expires_at IS NOT NULL 
             AND expires_at > %s AND expires_at <= %s
             AND is_expired = 0 AND amount > 0
             GROUP BY user_id",
            $today, $warning_date
        ));

        foreach ($at_risk as $row) {
            // Check if already warned
            $warned_key = 'novel_coin_warned_' . $row->user_id . '_' . date('Ymd');
            if (get_transient($warned_key)) continue;

            if (class_exists('Novel_Notifications')) {
                Novel_Notifications::get_instance()->send_notification(
                    $row->user_id, 'coins',
                    '⚠️ هشدار انقضای سکه',
                    sprintf('%d سکه شما تا ۳ روز دیگر منقضی می‌شود! از آنها استفاده کنید.', $row->total_amount),
                    home_url('/dashboard/?tab=coins')
                );
            }

            // Send email
            if (class_exists('Novel_Notifications')) {
                $user = get_user_by('id', $row->user_id);
                if ($user) {
                    $email_body = $this->get_expiry_email($user->display_name, $row->total_amount);
                    wp_mail($user->user_email, '⚠️ هشدار انقضای سکه', $email_body, ['Content-Type: text/html; charset=UTF-8']);
                }
            }

            set_transient($warned_key, 1, DAY_IN_SECONDS);
        }
    }

    /**
     * Get coins expiring soon for a user
     */
    public static function get_expiring_soon($user_id, $days = 3) {
        global $wpdb;
        $table = $wpdb->prefix . 'novel_user_coins';

        $future = date('Y-m-d H:i:s', current_time('timestamp') + ($days * DAY_IN_SECONDS));

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM {$table}
             WHERE user_id = %d AND expires_at IS NOT NULL
             AND expires_at <= %s AND is_expired = 0 AND amount > 0
             AND (expires_at > NOW())",
            $user_id, $future
        ));
    }

    /* ═══════════════════════════════════════
       Author Earnings
       ═══════════════════════════════════════ */

    /**
     * Record author earning from chapter purchase
     */
    private function record_author_earning($chapter_id, $novel_id, $buyer_id, $coins_spent) {
        $author_id = (int) get_post_field('post_author', $novel_id);
        if (!$author_id || $author_id === $buyer_id) return;

        $commission_rate = (float) get_option('novel_author_commission', 0.70);
        $coins_earned = floor($coins_spent * $commission_rate);
        $coin_value = (int) get_option('novel_coin_value', 1000); // Rials per coin
        $real_value = $coins_earned * $coin_value;

        global $wpdb;
        $table = $wpdb->prefix . 'novel_author_earnings';

        $wpdb->insert($table, [
            'author_id'       => $author_id,
            'novel_id'        => $novel_id,
            'chapter_id'      => $chapter_id,
            'buyer_id'        => $buyer_id,
            'coins_earned'    => $coins_earned,
            'real_value'      => $real_value,
            'commission_rate' => $commission_rate,
            'status'          => 'pending',
            'created_at'      => current_time('mysql'),
        ], ['%d', '%d', '%d', '%d', '%d', '%d', '%f', '%s', '%s']);
    }

    /**
     * AJAX: Author income stats
     */
    public function ajax_income_stats() {
        check_ajax_referer('novel_coins_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'ابتدا وارد شوید']);
        }

        $user_id = get_current_user_id();

        global $wpdb;
        $earnings_table = $wpdb->prefix . 'novel_author_earnings';
        $payouts_table = $wpdb->prefix . 'novel_author_payouts';

        // Summary
        $total_earned = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(real_value), 0) FROM {$earnings_table} WHERE author_id = %d",
            $user_id
        ));

        $month_start = date('Y-m-01 00:00:00');
        $month_earned = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(real_value), 0) FROM {$earnings_table} 
             WHERE author_id = %d AND created_at >= %s",
            $user_id, $month_start
        ));

        $pending_earned = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(real_value), 0) FROM {$earnings_table} 
             WHERE author_id = %d AND status = 'pending'",
            $user_id
        ));

        $paid_out = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM {$payouts_table} 
             WHERE author_id = %d AND status = 'completed'",
            $user_id
        ));

        // Per novel
        $novel_earnings = $wpdb->get_results($wpdb->prepare(
            "SELECT e.novel_id, p.post_title as novel_title,
                    COUNT(DISTINCT e.chapter_id) as vip_chapters,
                    COUNT(e.id) as total_sales,
                    SUM(e.coins_earned) as total_coins,
                    SUM(e.real_value) as total_value
             FROM {$earnings_table} e
             INNER JOIN {$wpdb->posts} p ON e.novel_id = p.ID
             WHERE e.author_id = %d
             GROUP BY e.novel_id
             ORDER BY total_value DESC",
            $user_id
        ));

        // Recent chapter earnings
        $recent_chapters = $wpdb->get_results($wpdb->prepare(
            "SELECT e.*, p.post_title as chapter_title, n.post_title as novel_title
             FROM {$earnings_table} e
             INNER JOIN {$wpdb->posts} p ON e.chapter_id = p.ID
             INNER JOIN {$wpdb->posts} n ON e.novel_id = n.ID
             WHERE e.author_id = %d
             ORDER BY e.created_at DESC
             LIMIT 20",
            $user_id
        ));

        // Payout history
        $payouts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$payouts_table} WHERE author_id = %d ORDER BY created_at DESC LIMIT 10",
            $user_id
        ));

        // Monthly chart (last 6 months)
        $chart_data = [];
        for ($i = 5; $i >= 0; $i--) {
            $m_start = date('Y-m-01', strtotime("-{$i} months"));
            $m_end = date('Y-m-t 23:59:59', strtotime("-{$i} months"));
            $m_earned = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(real_value), 0) FROM {$earnings_table}
                 WHERE author_id = %d AND created_at >= %s AND created_at <= %s",
                $user_id, $m_start, $m_end
            ));
            $chart_data[] = [
                'month'   => date_i18n('M Y', strtotime($m_start)),
                'earning' => $m_earned,
            ];
        }

        $min_payout = (int) get_option('novel_min_payout', 500000);

        wp_send_json_success([
            'summary' => [
                'total'   => $total_earned,
                'month'   => $month_earned,
                'pending' => $pending_earned,
                'paid'    => $paid_out,
            ],
            'novels'          => $novel_earnings,
            'recent_chapters' => $recent_chapters,
            'payouts'         => $payouts,
            'chart'           => $chart_data,
            'min_payout'      => $min_payout,
            'can_request'     => $pending_earned >= $min_payout,
        ]);
    }

    /**
     * AJAX: Request payout
     */
    public function request_payout() {
        check_ajax_referer('novel_coins_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'ابتدا وارد شوید']);
        }

        $user_id    = get_current_user_id();
        $amount     = absint($_POST['amount'] ?? 0);
        $card_number = sanitize_text_field($_POST['card_number'] ?? '');
        $card_name  = sanitize_text_field($_POST['card_name'] ?? '');
        $bank_name  = sanitize_text_field($_POST['bank_name'] ?? '');

        // Validate
        if (!$amount || $amount < (int) get_option('novel_min_payout', 500000)) {
            wp_send_json_error(['message' => 'مبلغ کمتر از حداقل واریز است']);
        }

        // Validate card number (16 digits)
        $card_clean = preg_replace('/[^0-9]/', '', $card_number);
        if (strlen($card_clean) !== 16) {
            wp_send_json_error(['message' => 'شماره کارت نامعتبر است (۱۶ رقم)']);
        }

        if (empty($card_name)) {
            wp_send_json_error(['message' => 'نام صاحب کارت الزامی است']);
        }

        // Check available balance
        global $wpdb;
        $earnings_table = $wpdb->prefix . 'novel_author_earnings';

        $available = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(real_value), 0) FROM {$earnings_table}
             WHERE author_id = %d AND status = 'pending'",
            $user_id
        ));

        if ($amount > $available) {
            wp_send_json_error(['message' => 'مبلغ درخواستی بیش از موجودی قابل واریز است']);
        }

        // Check duplicate pending payout
        $payouts_table = $wpdb->prefix . 'novel_author_payouts';
        $pending_payout = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$payouts_table} WHERE author_id = %d AND status = 'pending'",
            $user_id
        ));

        if ($pending_payout) {
            wp_send_json_error(['message' => 'شما یک درخواست واریز در انتظار دارید']);
        }

        // Insert payout request
        $wpdb->insert($payouts_table, [
            'author_id'   => $user_id,
            'amount'      => $amount,
            'card_number' => $card_clean,
            'card_name'   => $card_name,
            'bank_name'   => $bank_name,
            'status'      => 'pending',
            'created_at'  => current_time('mysql'),
        ], ['%d', '%d', '%s', '%s', '%s', '%s', '%s']);

        // Notify admins
        if (class_exists('Novel_Notifications')) {
            $admins = get_users(['role' => 'administrator', 'fields' => 'ID']);
            $user = wp_get_current_user();
            foreach ($admins as $admin_id) {
                Novel_Notifications::get_instance()->send_notification(
                    (int) $admin_id, 'system',
                    'درخواست واریز جدید 💰',
                    sprintf('%s درخواست واریز %s ریال دارد.', $user->display_name, number_format($amount)),
                    admin_url('admin.php?page=novel-authors&tab=payouts')
                );
            }
        }

        wp_send_json_success(['message' => 'درخواست واریز ارسال شد ✓']);
    }

    /* ═══════════════════════════════════════
       AJAX Helpers
       ═══════════════════════════════════════ */

    /**
     * AJAX: Get balance
     */
    public function ajax_get_balance() {
        check_ajax_referer('novel_coins_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_success(['balance' => 0]);
        }

        $user_id = get_current_user_id();
        $expiring = self::get_expiring_soon($user_id, 3);

        wp_send_json_success([
            'balance'  => self::get_balance($user_id),
            'expiring' => $expiring,
        ]);
    }

    /**
     * AJAX: Get transactions
     */
    public function ajax_get_transactions() {
        check_ajax_referer('novel_coins_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error();
        }

        $user_id  = get_current_user_id();
        $page     = max(1, absint($_POST['page'] ?? 1));
        $per_page = 20;
        $offset   = ($page - 1) * $per_page;

        global $wpdb;
        $table = $wpdb->prefix . 'novel_user_coins';

        $total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE user_id = %d",
            $user_id
        ));

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $user_id, $per_page, $offset
        ));

        $transactions = [];
        foreach ($items as $item) {
            $type_labels = [
                'purchase'            => ['label' => 'خرید سکه',    'icon' => '🟢', 'color' => '#10b981'],
                'subscription_bonus'  => ['label' => 'اشتراک',      'icon' => '🟢', 'color' => '#10b981'],
                'admin_grant'         => ['label' => 'هدیه ادمین',   'icon' => '🟢', 'color' => '#10b981'],
                'chapter_purchase'    => ['label' => 'خرید قسمت',   'icon' => '🔴', 'color' => '#ef4444'],
                'spend'               => ['label' => 'مصرف',        'icon' => '🔴', 'color' => '#ef4444'],
                'expired'             => ['label' => 'منقضی',       'icon' => '🟡', 'color' => '#f59e0b'],
                'refund'              => ['label' => 'بازگشت',      'icon' => '🔵', 'color' => '#3b82f6'],
            ];

            $info = $type_labels[$item->type] ?? ['label' => $item->type, 'icon' => '⚪', 'color' => '#999'];

            $transactions[] = [
                'id'          => $item->id,
                'amount'      => (int) $item->amount,
                'balance'     => (int) $item->balance_after,
                'type'        => $item->type,
                'type_label'  => $info['label'],
                'type_icon'   => $info['icon'],
                'type_color'  => $info['color'],
                'description' => $item->description,
                'date'        => date_i18n('j F Y', strtotime($item->created_at)),
                'time'        => date_i18n('H:i', strtotime($item->created_at)),
                'expires_at'  => $item->expires_at,
            ];
        }

        wp_send_json_success([
            'transactions' => $transactions,
            'total'        => $total,
            'pages'        => ceil($total / $per_page),
            'page'         => $page,
            'has_more'     => $page < ceil($total / $per_page),
        ]);
    }

    /* ═══════════════════════════════════════
       Email Template
       ═══════════════════════════════════════ */

    private function get_expiry_email($name, $amount) {
        ob_start();
        $user_name = $name;
        $coin_amount = $amount;
        include get_template_directory() . '/inc/email-templates/coin-expiry.php';
        return ob_get_clean();
    }

    /* ═══════════════════════════════════════
       Create Tables
       ═══════════════════════════════════════ */

    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        // User Coins
        $coins_table = $wpdb->prefix . 'novel_user_coins';
        $sql1 = "CREATE TABLE IF NOT EXISTS {$coins_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            amount INT NOT NULL,
            balance_after INT NOT NULL DEFAULT 0,
            type VARCHAR(30) NOT NULL,
            description VARCHAR(255) DEFAULT '',
            related_id BIGINT UNSIGNED DEFAULT 0,
            expires_at DATETIME DEFAULT NULL,
            is_expired TINYINT(1) DEFAULT 0,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_user (user_id),
            KEY idx_type (type),
            KEY idx_expires (expires_at, is_expired),
            KEY idx_created (created_at)
        ) {$charset};";

        // Chapter Purchases
        $purchases_table = $wpdb->prefix . 'novel_chapter_purchases';
        $sql2 = "CREATE TABLE IF NOT EXISTS {$purchases_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            chapter_id BIGINT UNSIGNED NOT NULL,
            novel_id BIGINT UNSIGNED NOT NULL,
            coins_spent INT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_purchase (user_id, chapter_id),
            KEY idx_novel (novel_id),
            KEY idx_created (created_at)
        ) {$charset};";

        // Author Earnings
        $earnings_table = $wpdb->prefix . 'novel_author_earnings';
        $sql3 = "CREATE TABLE IF NOT EXISTS {$earnings_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            author_id BIGINT UNSIGNED NOT NULL,
            novel_id BIGINT UNSIGNED NOT NULL,
            chapter_id BIGINT UNSIGNED NOT NULL,
            buyer_id BIGINT UNSIGNED NOT NULL,
            coins_earned INT NOT NULL,
            real_value BIGINT NOT NULL DEFAULT 0,
            commission_rate DECIMAL(3,2) NOT NULL DEFAULT 0.70,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_author (author_id),
            KEY idx_novel (novel_id),
            KEY idx_status (status),
            KEY idx_created (created_at)
        ) {$charset};";

        // Author Payouts
        $payouts_table = $wpdb->prefix . 'novel_author_payouts';
        $sql4 = "CREATE TABLE IF NOT EXISTS {$payouts_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            author_id BIGINT UNSIGNED NOT NULL,
            amount BIGINT NOT NULL,
            card_number VARCHAR(20) DEFAULT '',
            card_name VARCHAR(100) DEFAULT '',
            bank_name VARCHAR(100) DEFAULT '',
            transaction_id VARCHAR(100) DEFAULT '',
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            admin_note TEXT,
            processed_by BIGINT UNSIGNED DEFAULT NULL,
            processed_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_author (author_id),
            KEY idx_status (status),
            KEY idx_created (created_at)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql1);
        dbDelta($sql2);
        dbDelta($sql3);
        dbDelta($sql4);
    }
}