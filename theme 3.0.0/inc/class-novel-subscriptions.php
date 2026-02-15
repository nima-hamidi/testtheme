<?php
/**
 * Novel Subscriptions - RCP Integration
 * 
 * یکپارچگی با Restrict Content Pro + مدیریت اشتراک
 *
 * @package suspended-flavor
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

class Novel_Subscriptions {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // RCP hooks (if available)
        add_action('rcp_membership_post_activate', [$this, 'on_membership_activate'], 10, 2);
        add_action('rcp_membership_post_renew', [$this, 'on_membership_renew'], 10, 2);
        add_action('rcp_transition_membership_status', [$this, 'on_status_change'], 10, 3);

        // AJAX
        add_action('wp_ajax_novel_get_subscription_info', [$this, 'ajax_get_info']);

        // Shortcode
        add_shortcode('novel_plans', [$this, 'render_plans_page']);
    }

    /**
     * Check if RCP is active
     */
    public static function is_rcp_active() {
        return function_exists('rcp_get_membership') || class_exists('RCP_Membership');
    }

    /**
     * Check if user has active subscription
     */
    public static function has_active_subscription($user_id = 0) {
        if (!$user_id) $user_id = get_current_user_id();
        if (!$user_id) return false;

        $cache_key = 'novel_sub_active_' . $user_id;
        $cached = get_transient($cache_key);
        if ($cached !== false) return $cached === 'yes';

        $active = false;

        if (self::is_rcp_active()) {
            if (function_exists('rcp_user_has_active_membership')) {
                $active = rcp_user_has_active_membership($user_id);
            }
        }

        // Fallback: check user meta
        if (!$active) {
            $manual_sub = get_user_meta($user_id, 'novel_manual_subscription', true);
            if ($manual_sub) {
                $expiry = get_user_meta($user_id, 'novel_subscription_expiry', true);
                if (!$expiry || strtotime($expiry) > current_time('timestamp')) {
                    $active = true;
                }
            }
        }

        set_transient($cache_key, $active ? 'yes' : 'no', HOUR_IN_SECONDS);
        return $active;
    }

    /**
     * Get subscription info
     */
    public static function get_subscription_info($user_id = 0) {
        if (!$user_id) $user_id = get_current_user_id();

        $info = [
            'active'      => false,
            'plan_name'   => '',
            'plan_level'  => 0,
            'start_date'  => '',
            'expiry_date' => '',
            'days_left'   => 0,
            'status'      => 'none',
            'coins_granted' => 0,
        ];

        if (self::is_rcp_active() && function_exists('rcp_get_customer_by_user_id')) {
            $customer = rcp_get_customer_by_user_id($user_id);
            if ($customer) {
                $membership = rcp_get_customer_single_membership($customer->get_id());
                if ($membership) {
                    $info['active']      = $membership->is_active();
                    $info['plan_name']   = $membership->get_membership_level_name();
                    $info['plan_level']  = $membership->get_object_id();
                    $info['start_date']  = $membership->get_activated_date();
                    $info['expiry_date'] = $membership->get_expiration_date();
                    $info['status']      = $membership->get_status();

                    if ($info['expiry_date']) {
                        $diff = strtotime($info['expiry_date']) - current_time('timestamp');
                        $info['days_left'] = max(0, floor($diff / DAY_IN_SECONDS));
                    }
                }
            }
        }

        // Fallback manual check
        if (!$info['active']) {
            $manual = get_user_meta($user_id, 'novel_manual_subscription', true);
            if ($manual) {
                $expiry = get_user_meta($user_id, 'novel_subscription_expiry', true);
                $plan = get_user_meta($user_id, 'novel_subscription_plan', true);

                if (!$expiry || strtotime($expiry) > current_time('timestamp')) {
                    $info['active']      = true;
                    $info['plan_name']   = $plan ?: 'اشتراک دستی';
                    $info['start_date']  = get_user_meta($user_id, 'novel_subscription_start', true);
                    $info['expiry_date'] = $expiry;
                    $info['status']      = 'active';

                    if ($expiry) {
                        $diff = strtotime($expiry) - current_time('timestamp');
                        $info['days_left'] = max(0, floor($diff / DAY_IN_SECONDS));
                    }
                }
            }
        }

        return $info;
    }

    /**
     * RCP: On membership activate
     */
    public function on_membership_activate($membership_id, $membership) {
        if (!is_object($membership)) return;

        $customer = $membership->get_customer();
        if (!$customer) return;

        $user_id = $customer->get_user_id();
        $plan_id = $membership->get_object_id();

        // Grant coins based on plan
        $this->grant_plan_coins($user_id, $plan_id, 'subscription_bonus');

        // Clear cache
        delete_transient('novel_sub_active_' . $user_id);

        // Notification
        if (class_exists('Novel_Notifications')) {
            $plan_name = $membership->get_membership_level_name();
            Novel_Notifications::get_instance()->send_notification(
                $user_id, 'subscription',
                'اشتراک فعال شد! 🎉',
                sprintf('پلن %s با موفقیت فعال شد.', $plan_name),
                home_url('/dashboard/?tab=subscription')
            );
        }
    }

    /**
     * RCP: On membership renew
     */
    public function on_membership_renew($membership_id, $membership) {
        if (!is_object($membership)) return;

        $customer = $membership->get_customer();
        if (!$customer) return;

        $user_id = $customer->get_user_id();
        $plan_id = $membership->get_object_id();

        $this->grant_plan_coins($user_id, $plan_id, 'subscription_bonus');
        delete_transient('novel_sub_active_' . $user_id);
    }

    /**
     * RCP: On status change
     */
    public function on_status_change($old_status, $new_status, $membership_id) {
        if (!function_exists('rcp_get_membership')) return;

        $membership = rcp_get_membership($membership_id);
        if (!$membership) return;

        $customer = $membership->get_customer();
        if (!$customer) return;

        $user_id = $customer->get_user_id();
        delete_transient('novel_sub_active_' . $user_id);

        // Notify on expiration
        if ($new_status === 'expired') {
            if (class_exists('Novel_Notifications')) {
                Novel_Notifications::get_instance()->send_notification(
                    $user_id, 'subscription',
                    'اشتراک منقضی شد ⚠️',
                    'اشتراک شما منقضی شده است. برای ادامه دسترسی به محتوای VIP، اشتراک خود را تمدید کنید.',
                    home_url('/dashboard/?tab=subscription')
                );
            }
        }
    }

    /**
     * Grant coins for subscription plan
     */
    private function grant_plan_coins($user_id, $plan_id, $type) {
        if (!class_exists('Novel_Coins')) return;

        $plan_coins = get_option('novel_plan_coins', []);
        $coins = isset($plan_coins[$plan_id]) ? (int) $plan_coins[$plan_id] : 0;

        if ($coins > 0) {
            Novel_Coins::add_coins(
                $user_id,
                $coins,
                $type,
                sprintf('سکه اشتراک پلن %s', $plan_id),
                $plan_id
            );
        }
    }

    /**
     * AJAX: Get subscription info
     */
    public function ajax_get_info() {
        check_ajax_referer('novel_subscription_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'ابتدا وارد شوید']);
        }

        $info = self::get_subscription_info(get_current_user_id());
        wp_send_json_success($info);
    }

    /**
     * Render plans page shortcode
     */
    public function render_plans_page($atts) {
        ob_start();
        get_template_part('templates/subscription/plans');
        return ob_get_clean();
    }
}