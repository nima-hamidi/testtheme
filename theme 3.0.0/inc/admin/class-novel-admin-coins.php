<?php
/**
 * Novel Admin Coins Management
 * 
 * مدیریت سکه: اعطا/کسر + تراکنش‌ها + تنظیمات
 *
 * @package suspended-flavor
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

class Novel_Admin_Coins {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('wp_ajax_novel_admin_grant_coins', [$this, 'grant_coins']);
        add_action('wp_ajax_novel_admin_search_users', [$this, 'search_users']);
    }

    public function add_menu() {
        add_submenu_page(
            'novel-settings',
            'مدیریت سکه',
            '🪙 سکه‌ها',
            'manage_options',
            'novel-coins',
            [$this, 'render_page']
        );
    }

    public function render_page() {
        // Handle settings save
        if (isset($_POST['novel_coins_settings_nonce']) && wp_verify_nonce($_POST['novel_coins_settings_nonce'], 'novel_coins_settings')) {
            update_option('novel_default_coin_price', absint($_POST['default_price'] ?? 5));
            update_option('novel_coin_expiry_days', absint($_POST['expiry_days'] ?? 0));
            update_option('novel_coin_value', absint($_POST['coin_value'] ?? 1000));
            update_option('novel_author_commission', floatval($_POST['author_commission'] ?? 0.70));
            update_option('novel_min_payout', absint($_POST['min_payout'] ?? 500000));

            // Plan coins
            $plan_coins = [];
            if (isset($_POST['plan_id']) && is_array($_POST['plan_id'])) {
                foreach ($_POST['plan_id'] as $i => $plan_id) {
                    $plan_id = absint($plan_id);
                    $coins = absint($_POST['plan_coins'][$i] ?? 0);
                    if ($plan_id && $coins) {
                        $plan_coins[$plan_id] = $coins;
                    }
                }
            }
            update_option('novel_plan_coins', $plan_coins);

            echo '<div class="notice notice-success"><p>تنظیمات ذخیره شد ✓</p></div>';
        }

        $default_price = get_option('novel_default_coin_price', 5);
        $expiry_days = get_option('novel_coin_expiry_days', 0);
        $coin_value = get_option('novel_coin_value', 1000);
        $author_commission = get_option('novel_author_commission', 0.70);
        $min_payout = get_option('novel_min_payout', 500000);
        $plan_coins = get_option('novel_plan_coins', []);
        ?>
        <div class="wrap">
            <h1>🪙 مدیریت سکه</h1>

            <!-- Stats -->
            <?php $this->render_stats(); ?>

            <!-- Grant/Deduct Form -->
            <div class="card" style="max-width:500px;margin-bottom:20px;">
                <h2>اعطا/کسر سکه به کاربر</h2>
                <div id="coinGrantForm">
                    <p>
                        <label>کاربر:</label><br>
                        <input type="text" id="coinUserSearch" placeholder="جستجوی نام یا ایمیل..." style="width:100%">
                        <input type="hidden" id="coinUserId" value="">
                        <div id="coinUserResults" style="display:none"></div>
                    </p>
                    <p>
                        <label>تعداد سکه:</label><br>
                        <input type="number" id="coinAmount" min="1" value="10" style="width:120px">
                    </p>
                    <p>
                        <label>عملیات:</label><br>
                        <select id="coinOperation">
                            <option value="add">➕ اعطا</option>
                            <option value="deduct">➖ کسر</option>
                        </select>
                    </p>
                    <p>
                        <label>توضیحات:</label><br>
                        <input type="text" id="coinDescription" placeholder="هدیه ادمین" style="width:100%">
                    </p>
                    <button class="button button-primary" id="coinGrantBtn">اعمال</button>
                    <span id="coinGrantResult" style="margin-right:10px;"></span>
                </div>
            </div>

            <!-- Settings -->
            <div class="card" style="max-width:600px;">
                <h2>⚙️ تنظیمات سکه</h2>
                <form method="post">
                    <?php wp_nonce_field('novel_coins_settings', 'novel_coins_settings_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th>قیمت پیش‌فرض هر قسمت VIP (سکه)</th>
                            <td><input type="number" name="default_price" value="<?php echo $default_price; ?>" min="1"></td>
                        </tr>
                        <tr>
                            <th>مدت انقضای سکه (روز)</th>
                            <td>
                                <input type="number" name="expiry_days" value="<?php echo $expiry_days; ?>" min="0">
                                <p class="description">۰ = بدون انقضا</p>
                            </td>
                        </tr>
                        <tr>
                            <th>ارزش هر سکه (ریال)</th>
                            <td><input type="number" name="coin_value" value="<?php echo $coin_value; ?>" min="100"></td>
                        </tr>
                        <tr>
                            <th>سهم نویسنده (درصد)</th>
                            <td>
                                <input type="number" name="author_commission" value="<?php echo $author_commission * 100; ?>" min="0" max="100" step="1">%
                                <p class="description">مثال: ۷۰ = نویسنده ۷۰٪ دریافت می‌کند</p>
                            </td>
                        </tr>
                        <tr>
                            <th>حداقل مبلغ واریز (ریال)</th>
                            <td><input type="number" name="min_payout" value="<?php echo $min_payout; ?>" min="0"></td>
                        </tr>
                        <tr>
                            <th>سکه هر پلن اشتراکی</th>
                            <td>
                                <div id="planCoinsRepeater">
                                    <?php foreach ($plan_coins as $pid => $coins): ?>
                                        <div class="plan-coin-row" style="margin-bottom:5px;">
                                            <input type="number" name="plan_id[]" value="<?php echo $pid; ?>" placeholder="شناسه پلن RCP" style="width:120px">
                                            <input type="number" name="plan_coins[]" value="<?php echo $coins; ?>" placeholder="تعداد سکه" style="width:100px">
                                            <button type="button" class="button" onclick="this.parentElement.remove()">✕</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="button" onclick="addPlanRow()">+ افزودن پلن</button>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button('ذخیره تنظیمات'); ?>
                </form>
            </div>
        </div>

        <script>
        function addPlanRow() {
            document.getElementById('planCoinsRepeater').insertAdjacentHTML('beforeend',
                '<div class="plan-coin-row" style="margin-bottom:5px;">' +
                '<input type="number" name="plan_id[]" placeholder="شناسه پلن RCP" style="width:120px"> ' +
                '<input type="number" name="plan_coins[]" placeholder="تعداد سکه" style="width:100px"> ' +
                '<button type="button" class="button" onclick="this.parentElement.remove()">✕</button>' +
                '</div>'
            );
        }

        jQuery(function($) {
            let searchTimer;
            $('#coinUserSearch').on('input', function() {
                clearTimeout(searchTimer);
                const val = this.value.trim();
                if (val.length < 2) { $('#coinUserResults').hide(); return; }
                searchTimer = setTimeout(() => {
                    $.post(ajaxurl, {
                        action: 'novel_admin_search_users',
                        nonce: '<?php echo wp_create_nonce("novel_admin_coins_nonce"); ?>',
                        query: val
                    }, function(res) {
                        if (res.success && res.data.length) {
                            let html = '';
                            res.data.forEach(u => {
                                html += '<div class="user-result" data-id="'+u.id+'" style="padding:5px;cursor:pointer;border-bottom:1px solid #eee;">';
                                html += '<strong>'+u.name+'</strong> ('+u.email+') - موجودی: '+u.balance+' سکه</div>';
                            });
                            $('#coinUserResults').html(html).show();
                        } else {
                            $('#coinUserResults').html('<div style="padding:5px;color:#999;">کاربری یافت نشد</div>').show();
                        }
                    });
                }, 300);
            });

            $(document).on('click', '.user-result', function() {
                $('#coinUserId').val($(this).data('id'));
                $('#coinUserSearch').val($(this).find('strong').text());
                $('#coinUserResults').hide();
            });

            $('#coinGrantBtn').on('click', function() {
                const userId = $('#coinUserId').val();
                const amount = $('#coinAmount').val();
                const op = $('#coinOperation').val();
                const desc = $('#coinDescription').val();
                if (!userId) { alert('کاربر انتخاب نشده'); return; }

                $.post(ajaxurl, {
                    action: 'novel_admin_grant_coins',
                    nonce: '<?php echo wp_create_nonce("novel_admin_coins_nonce"); ?>',
                    user_id: userId,
                    amount: amount,
                    operation: op,
                    description: desc
                }, function(res) {
                    $('#coinGrantResult').text(res.success ? '✅ ' + res.data.message : '❌ خطا').fadeIn();
                    setTimeout(() => $('#coinGrantResult').fadeOut(), 3000);
                });
            });
        });
        </script>
        <?php
    }

    private function render_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'novel_user_coins';

        $total_active = (int) $wpdb->get_var(
            "SELECT COALESCE(SUM(amount), 0) FROM {$table} WHERE is_expired = 0 AND (expires_at IS NULL OR expires_at > NOW())"
        );
        $total_spent = (int) $wpdb->get_var(
            "SELECT COALESCE(ABS(SUM(amount)), 0) FROM {$table} WHERE amount < 0 AND type != 'expired'"
        );
        $total_expired = (int) $wpdb->get_var(
            "SELECT COALESCE(ABS(SUM(amount)), 0) FROM {$table} WHERE type = 'expired'"
        );
        ?>
        <div style="display:flex;gap:12px;margin:16px 0;">
            <div class="card" style="flex:1;text-align:center;padding:16px;">
                <div style="font-size:1.5em;font-weight:700;color:#10b981;"><?php echo number_format_i18n($total_active); ?></div>
                <div>سکه‌های فعال</div>
            </div>
            <div class="card" style="flex:1;text-align:center;padding:16px;">
                <div style="font-size:1.5em;font-weight:700;color:#ef4444;"><?php echo number_format_i18n($total_spent); ?></div>
                <div>مصرف‌شده</div>
            </div>
            <div class="card" style="flex:1;text-align:center;padding:16px;">
                <div style="font-size:1.5em;font-weight:700;color:#f59e0b;"><?php echo number_format_i18n($total_expired); ?></div>
                <div>منقضی‌شده</div>
            </div>
        </div>
        <?php
    }

    public function grant_coins() {
        check_ajax_referer('novel_admin_coins_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();

        $user_id = absint($_POST['user_id'] ?? 0);
        $amount  = absint($_POST['amount'] ?? 0);
        $op      = sanitize_text_field($_POST['operation'] ?? 'add');
        $desc    = sanitize_text_field($_POST['description'] ?? '');

        if (!$user_id || !$amount) {
            wp_send_json_error(['message' => 'داده نامعتبر']);
        }

        if ($op === 'add') {
            $result = Novel_Coins::add_coins($user_id, $amount, 'admin_grant', $desc ?: 'اعطا توسط ادمین', 0);
            $msg = sprintf('%d سکه به کاربر اعطا شد', $amount);
        } else {
            $result = Novel_Coins::spend_coins($user_id, $amount, 'admin_deduct', $desc ?: 'کسر توسط ادمین', 0);
            $msg = sprintf('%d سکه از کاربر کسر شد', $amount);
        }

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success(['message' => $msg, 'balance' => Novel_Coins::get_balance($user_id)]);
    }

    public function search_users() {
        check_ajax_referer('novel_admin_coins_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();

        $query = sanitize_text_field($_POST['query'] ?? '');
        if (strlen($query) < 2) wp_send_json_success([]);

        $users = get_users([
            'search'         => '*' . $query . '*',
            'search_columns' => ['user_login', 'user_email', 'display_name'],
            'number'         => 10,
        ]);

        $results = [];
        foreach ($users as $user) {
            $results[] = [
                'id'      => $user->ID,
                'name'    => $user->display_name,
                'email'   => $user->user_email,
                'balance' => Novel_Coins::get_balance($user->ID),
            ];
        }

        wp_send_json_success($results);
    }
}س