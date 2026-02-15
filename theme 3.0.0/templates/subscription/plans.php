<?php
/**
 * Subscription Plans Page
 * 
 * صفحه پلن‌های اشتراک
 * شورتکد: [novel_plans]
 *
 * @package suspended-flavor
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

$plans = get_option('novel_subscription_plans', [
    [
        'name'     => 'برنزی',
        'icon'     => '🥉',
        'duration' => 'ماهانه',
        'price'    => '۴۹,۰۰۰',
        'coins'    => 20,
        'features' => ['قسمت‌های VIP', '۲۰ سکه هدیه', 'بج ویژه'],
        'featured' => false,
        'rcp_id'   => 1,
        'color'    => '#cd7f32',
    ],
    [
        'name'     => 'طلایی',
        'icon'     => '🥇',
        'duration' => '۳ ماهه',
        'price'    => '۱۱۹,۰۰۰',
        'coins'    => 50,
        'features' => ['قسمت‌های VIP', '۵۰ سکه هدیه', 'بدون تبلیغات', 'بج طلایی 👑', 'دسترسی زودتر'],
        'featured' => true,
        'rcp_id'   => 2,
        'color'    => '#f59e0b',
    ],
    [
        'name'     => 'نقره‌ای',
        'icon'     => '🥈',
        'duration' => 'سالانه',
        'price'    => '۳۹۹,۰۰۰',
        'coins'    => 200,
        'features' => ['قسمت‌های VIP', '۲۰۰ سکه هدیه', 'بدون تبلیغات', 'بج نقره‌ای', 'اولویت پشتیبانی'],
        'featured' => false,
        'rcp_id'   => 3,
        'color'    => '#94a3b8',
    ],
]);

$is_logged_in = is_user_logged_in();
$has_sub = $is_logged_in && class_exists('Novel_Subscriptions') && Novel_Subscriptions::has_active_subscription();
$rcp_url = function_exists('rcp_get_registration_page_url') ? rcp_get_registration_page_url() : home_url('/register/');
?>

<div class="plans-page">
    
    <div class="plans-header">
        <h1 class="plans-title">💎 پلن‌های اشتراک ویژه</h1>
        <p class="plans-subtitle">با خرید اشتراک، به تمام قسمت‌های VIP دسترسی داشته باشید و سکه رایگان بگیرید!</p>
    </div>

    <?php if ($has_sub): ?>
        <div class="plans-active-notice">
            <span class="notice-icon">✅</span>
            <span>شما اشتراک فعال دارید!</span>
            <a href="<?php echo esc_url(home_url('/dashboard/?tab=subscription')); ?>">مشاهده جزئیات</a>
        </div>
    <?php endif; ?>

    <div class="plans-grid">
        <?php foreach ($plans as $plan): ?>
            <div class="plan-card <?php echo $plan['featured'] ? 'plan-card--featured' : ''; ?>">
                
                <?php if ($plan['featured']): ?>
                    <div class="plan-card__badge">⭐ پیشنهاد ویژه</div>
                <?php endif; ?>

                <div class="plan-card__header" style="--plan-color: <?php echo $plan['color']; ?>">
                    <span class="plan-card__icon"><?php echo $plan['icon']; ?></span>
                    <h3 class="plan-card__name"><?php echo esc_html($plan['name']); ?></h3>
                </div>

                <div class="plan-card__duration"><?php echo esc_html($plan['duration']); ?></div>

                <div class="plan-card__price">
                    <span class="price-amount"><?php echo esc_html($plan['price']); ?></span>
                    <span class="price-currency">تومان</span>
                </div>

                <div class="plan-card__divider"></div>

                <ul class="plan-card__features">
                    <?php foreach ($plan['features'] as $feature): ?>
                        <li>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <?php echo esc_html($feature); ?>
                        </li>
                    <?php endforeach; ?>
                    <?php if ($plan['coins']): ?>
                        <li class="feature-highlight">
                            🪙 <?php echo $plan['coins']; ?> سکه هدیه
                        </li>
                    <?php endif; ?>
                </ul>

                <div class="plan-card__action">
                    <?php if ($has_sub): ?>
                        <span class="btn-plan btn-plan--disabled">اشتراک فعال ✓</span>
                    <?php elseif ($is_logged_in): ?>
                        <a href="<?php echo esc_url(add_query_arg('level', $plan['rcp_id'], $rcp_url)); ?>" 
                           class="btn-plan <?php echo $plan['featured'] ? 'btn-plan--primary' : 'btn-plan--outline'; ?>">
                            خرید اشتراک
                        </a>
                    <?php else: ?>
                        <a href="<?php echo esc_url(home_url('/register/')); ?>" 
                           class="btn-plan btn-plan--outline">
                            ثبت‌نام و خرید
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Coin Packages -->
    <div class="coin-packages-section">
        <h2 class="coin-packages-title">🪙 خرید سکه</h2>
        <p class="coin-packages-subtitle">بدون اشتراک هم می‌توانید قسمت‌های VIP را تکی بخرید!</p>
        
        <?php
        $coin_packages = get_option('novel_coin_packages', [
            ['coins' => 10,  'price' => '۹,۹۰۰',  'discount' => 0],
            ['coins' => 50,  'price' => '۴۴,۹۰۰',  'discount' => 10],
            ['coins' => 100, 'price' => '۷۹,۹۰۰',  'discount' => 20],
        ]);
        ?>

        <div class="coin-packages-grid">
            <?php foreach ($coin_packages as $pkg): ?>
                <div class="coin-package">
                    <div class="coin-package__icon">🪙</div>
                    <div class="coin-package__amount"><?php echo $pkg['coins']; ?> سکه</div>
                    <div class="coin-package__price"><?php echo esc_html($pkg['price']); ?> تومان</div>
                    <?php if ($pkg['discount']): ?>
                        <div class="coin-package__discount"><?php echo $pkg['discount']; ?>٪ تخفیف</div>
                    <?php endif; ?>
                    <button class="btn-coin-buy" data-coins="<?php echo $pkg['coins']; ?>">
                        خرید
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>