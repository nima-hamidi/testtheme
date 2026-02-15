<?php
/**
 * Dashboard - My Subscription
 *
 * @package suspended-flavor
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;
if (!is_user_logged_in()) return;

$user_id = get_current_user_id();
$sub_info = class_exists('Novel_Subscriptions') ? Novel_Subscriptions::get_subscription_info($user_id) : ['active' => false];
?>

<div class="dashboard-subscription">
    
    <?php if ($sub_info['active']): ?>
        <div class="sub-active-card">
            <div class="sub-active-card__header">
                <span class="sub-active-card__icon">💎</span>
                <h3>پلن فعال: <?php echo esc_html($sub_info['plan_name']); ?></h3>
            </div>
            <div class="sub-active-card__details">
                <div class="sub-detail">
                    <span class="sub-detail__label">📅 شروع:</span>
                    <span class="sub-detail__value"><?php echo $sub_info['start_date'] ? date_i18n('j F Y', strtotime($sub_info['start_date'])) : '—'; ?></span>
                </div>
                <div class="sub-detail">
                    <span class="sub-detail__label">📅 پایان:</span>
                    <span class="sub-detail__value"><?php echo $sub_info['expiry_date'] ? date_i18n('j F Y', strtotime($sub_info['expiry_date'])) : 'نامحدود'; ?></span>
                </div>
                <div class="sub-detail">
                    <span class="sub-detail__label">⏳ باقی‌مانده:</span>
                    <span class="sub-detail__value <?php echo $sub_info['days_left'] < 7 ? 'text-warning' : ''; ?>">
                        <?php echo $sub_info['days_left'] > 0 ? $sub_info['days_left'] . ' روز' : 'نامحدود'; ?>
                    </span>
                </div>
            </div>
            <div class="sub-active-card__actions">
                <a href="<?php echo esc_url(home_url('/plans/')); ?>" class="btn-outline">تمدید اشتراک</a>
                <a href="<?php echo esc_url(home_url('/plans/')); ?>" class="btn-outline">تغییر پلن</a>
            </div>
        </div>
    <?php else: ?>
        <div class="sub-empty-card">
            <div class="sub-empty-card__icon">💎</div>
            <h3>شما اشتراک فعالی ندارید</h3>
            <p>با خرید اشتراک، به تمام قسمت‌های VIP دسترسی داشته باشید و سکه رایگان بگیرید!</p>
            <a href="<?php echo esc_url(home_url('/plans/')); ?>" class="btn-primary">
                💎 خرید اشتراک ویژه
            </a>
        </div>
    <?php endif; ?>

</div>