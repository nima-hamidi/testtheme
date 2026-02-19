<?php
/**
 * فایل: inc/admin/class-novel-admin-settings.php
 * توضیح: صفحه تنظیمات ادمین وردپرس - تنظیمات عمومی + ماژول‌ها
 * نسخه: 2.0.0
 * وابستگی: class-novel-settings.php
 */

if (!defined('ABSPATH')) exit;

class Novel_Admin_Settings {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);
    }

    /**
     * افزودن منو به سایدبار ادمین
     */
    public function add_menu() {
        // منوی اصلی
        add_menu_page(
            'تنظیمات ناول',
            'تنظیمات ناول',
            'manage_options',
            'novel-settings',
            [$this, 'render_settings_page'],
            'dashicons-book-alt',
            3
        );

        // زیرمنوها
        add_submenu_page('novel-settings', 'تنظیمات عمومی', 'تنظیمات عمومی', 'manage_options', 'novel-settings');
        add_submenu_page('novel-settings', 'ماژول‌ها', 'ماژول‌ها', 'manage_options', 'novel-modules', [$this, 'render_modules_page']);
        add_submenu_page('novel-settings', 'ظاهر و طراحی', 'ظاهر و طراحی', 'manage_options', 'novel-appearance', [$this, 'render_appearance_page']);
        /*فاز 13*/
        add_submenu_page('novel-settings', 'بنر اطلاعیه', 'بنر اطلاعیه',
        'manage_options', 'novel-announcement', [$this, 'render_announcement_page']);
        }

    /**
     * Assets ادمین
     */
    public function admin_assets($hook) {
        if (strpos($hook, 'novel-') === false && strpos($hook, 'novel_') === false) {
            return;
        }

        wp_enqueue_style('novel-admin', NOVEL_ASSETS . 'css/admin.css', [], NOVEL_VERSION);
        wp_enqueue_media(); // برای آپلود لوگو
    }

    /**
     * ثبت تنظیمات
     */
    public function register_settings() {
        // ── گروه عمومی ──
        $general_fields = [
            'novel_site_description', 'novel_rules_page', 'novel_comment_rules_page',
            'novel_comment_encourage', 'novel_comment_warning',
            'novel_min_comment_chars', 'novel_max_comment_chars',
            'novel_min_review_words', 'novel_min_theory_words',
            'novel_comment_edit_time', 'novel_login_attempts', 'novel_login_lockout',
            'novel_bad_words', 'novel_allow_user_author',
            'novel_author_commission', 'novel_min_payout', 'novel_coin_expiry_days',
            'novel_notif_interval',
            'novel_maintenance_mode', 'novel_maintenance_message', 'novel_maintenance_eta',
            'novel_quiz_daily_auto', 'novel_quiz_daily_time',
            'novel_quiz_prize_1st', 'novel_quiz_prize_2nd',
            'novel_quiz_prize_3rd', 'novel_quiz_prize_participate',
            'novel_banner_enabled', 'novel_banner_duration',
            'novel_banner_max_active', 'novel_banner_position',
            'novel_social_telegram', 'novel_social_instagram', 'novel_social_twitter',
            'novel_site_logo',
        ];

        foreach ($general_fields as $field) {
            register_setting('novel_general_settings', $field);
        }
    }

    /**
     * رندر صفحه تنظیمات عمومی
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) return;

        // ذخیره‌سازی
        if (isset($_POST['novel_settings_nonce']) && wp_verify_nonce($_POST['novel_settings_nonce'], 'novel_save_settings')) {
            $this->save_settings();
            echo '<div class="notice notice-success"><p>✅ تنظیمات با موفقیت ذخیره شد.</p></div>';
            Novel_Settings::flush_cache();
        }

        $this->render_page_header('تنظیمات عمومی');
        ?>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('novel_save_settings', 'novel_settings_nonce'); ?>

            <!-- ═══ بخش عمومی ═══ -->
            <div class="novel-admin-section">
                <h2>🌐 عمومی</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="novel_site_logo">لوگو سایت</label></th>
                        <td>
                            <?php $logo = get_option('novel_site_logo', ''); ?>
                            <div class="novel-logo-upload">
                                <input type="hidden" id="novel_site_logo" name="novel_site_logo" value="<?php echo esc_attr($logo); ?>">
                                <img id="novel-logo-preview" src="<?php echo $logo ? esc_url($logo) : ''; ?>" 
                                     style="max-height: 60px; <?php echo !$logo ? 'display:none;' : ''; ?>">
                                <button type="button" class="button" id="novel-upload-logo">انتخاب لوگو</button>
                                <button type="button" class="button" id="novel-remove-logo" 
                                        style="<?php echo !$logo ? 'display:none;' : ''; ?>">حذف</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="novel_site_description">توضیح کوتاه سایت</label></th>
                        <td>
                            <textarea id="novel_site_description" name="novel_site_description" rows="2" class="large-text"
                            ><?php echo esc_textarea(get_option('novel_site_description', '')); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="novel_rules_page">لینک صفحه قوانین سایت</label></th>
                        <td>
                            <?php
                            wp_dropdown_pages([
                                'name'              => 'novel_rules_page',
                                'selected'          => get_option('novel_rules_page', ''),
                                'show_option_none'  => 'انتخاب صفحه...',
                                'option_none_value' => '',
                            ]);
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="novel_comment_rules_page">لینک صفحه قوانین دیدگاه</label></th>
                        <td>
                            <?php
                            wp_dropdown_pages([
                                'name'              => 'novel_comment_rules_page',
                                'selected'          => get_option('novel_comment_rules_page', ''),
                                'show_option_none'  => 'انتخاب صفحه...',
                                'option_none_value' => '',
                            ]);
                            ?>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- ═══ بخش دیدگاه ═══ -->
            <div class="novel-admin-section">
                <h2>💬 دیدگاه‌ها</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="novel_comment_encourage">متن تشویقی دیدگاه</label></th>
                        <td>
                            <textarea id="novel_comment_encourage" name="novel_comment_encourage" rows="2" class="large-text"
                            ><?php echo esc_textarea(get_option('novel_comment_encourage', '')); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="novel_comment_warning">متن هشدار دیدگاه</label></th>
                        <td>
                            <textarea id="novel_comment_warning" name="novel_comment_warning" rows="2" class="large-text"
                            ><?php echo esc_textarea(get_option('novel_comment_warning', '')); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="novel_min_comment_chars">حداقل کاراکتر دیدگاه</label></th>
                        <td><input type="number" id="novel_min_comment_chars" name="novel_min_comment_chars" 
                                   value="<?php echo absint(get_option('novel_min_comment_chars', 10)); ?>" min="1" max="500"></td>
                    </tr>
                    <tr>
                        <th><label for="novel_max_comment_chars">حداکثر کاراکتر دیدگاه</label></th>
                        <td><input type="number" id="novel_max_comment_chars" name="novel_max_comment_chars" 
                                   value="<?php echo absint(get_option('novel_max_comment_chars', 1000)); ?>" min="100" max="10000"></td>
                    </tr>
                    <tr>
                        <th><label for="novel_min_review_words">حداقل کلمه نقد</label></th>
                        <td><input type="number" id="novel_min_review_words" name="novel_min_review_words" 
                                   value="<?php echo absint(get_option('novel_min_review_words', 200)); ?>" min="50"></td>
                    </tr>
                    <tr>
                        <th><label for="novel_min_theory_words">حداقل کلمه تئوری</label></th>
                        <td><input type="number" id="novel_min_theory_words" name="novel_min_theory_words" 
                                   value="<?php echo absint(get_option('novel_min_theory_words', 250)); ?>" min="50"></td>
                    </tr>
                    <tr>
                        <th><label for="novel_comment_edit_time">زمان ویرایش دیدگاه (دقیقه)</label></th>
                        <td><input type="number" id="novel_comment_edit_time" name="novel_comment_edit_time" 
                                   value="<?php echo absint(get_option('novel_comment_edit_time', 15)); ?>" min="0" max="1440"></td>
                    </tr>
                    <tr>
                        <th><label for="novel_bad_words">کلمات رکیک</label></th>
                        <td>
                            <textarea id="novel_bad_words" name="novel_bad_words" rows="4" class="large-text" 
                                      placeholder="هر خط یک کلمه"
                            ><?php echo esc_textarea(get_option('novel_bad_words', '')); ?></textarea>
                            <p class="description">هر خط یک کلمه. دیدگاه حاوی این کلمات رد می‌شود.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- ═══ بخش امنیت ═══ -->
            <div class="novel-admin-section">
                <h2>🔒 امنیت</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="novel_login_attempts">تعداد تلاش ورود</label></th>
                        <td><input type="number" id="novel_login_attempts" name="novel_login_attempts" 
                                   value="<?php echo absint(get_option('novel_login_attempts', 5)); ?>" min="1" max="20"></td>
                    </tr>
                    <tr>
                        <th><label for="novel_login_lockout">مدت قفل ورود (دقیقه)</label></th>
                        <td><input type="number" id="novel_login_lockout" name="novel_login_lockout" 
                                   value="<?php echo absint(get_option('novel_login_lockout', 15)); ?>" min="1" max="1440"></td>
                    </tr>
                </table>
            </div>

            <!-- ═══ بخش نویسندگان ═══ -->
            <div class="novel-admin-section">
                <h2>✍️ نویسندگان و درآمد</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="novel_allow_user_author">نویسندگی سمت کاربر</label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="novel_allow_user_author" name="novel_allow_user_author" 
                                       value="1" <?php checked(get_option('novel_allow_user_author', true)); ?>>
                                کاربران عادی بتوانند رمان بنویسند
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="novel_author_commission">درصد سهم نویسنده</label></th>
                        <td>
                            <input type="number" id="novel_author_commission" name="novel_author_commission" 
                                   value="<?php echo absint(get_option('novel_author_commission', 70)); ?>" min="0" max="100">
                            <span>%</span>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="novel_min_payout">حداقل موجودی برای واریز (ریال)</label></th>
                        <td><input type="number" id="novel_min_payout" name="novel_min_payout" 
                                   value="<?php echo absint(get_option('novel_min_payout', 500000)); ?>" min="0"></td>
                    </tr>
                </table>
            </div>

            <!-- ═══ بخش سکه ═══ -->
            <div class="novel-admin-section">
                <h2>🪙 سکه و اشتراک</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="novel_coin_expiry_days">مدت انقضای سکه (روز)</label></th>
                        <td>
                            <input type="number" id="novel_coin_expiry_days" name="novel_coin_expiry_days" 
                                   value="<?php echo absint(get_option('novel_coin_expiry_days', 0)); ?>" min="0">
                            <p class="description">۰ = بدون انقضا</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- ═══ بخش مسابقه ═══ -->
            <div class="novel-admin-section">
                <h2>🏆 مسابقه کتابخوانی</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="novel_quiz_daily_auto">مسابقه روزانه خودکار</label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="novel_quiz_daily_auto" name="novel_quiz_daily_auto" 
                                       value="1" <?php checked(get_option('novel_quiz_daily_auto', false)); ?>>
                                فعال‌سازی
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="novel_quiz_daily_time">ساعت شروع مسابقه روزانه</label></th>
                        <td><input type="time" id="novel_quiz_daily_time" name="novel_quiz_daily_time" 
                                   value="<?php echo esc_attr(get_option('novel_quiz_daily_time', '00:00')); ?>"></td>
                    </tr>
                    <tr>
                        <th>جایزه مسابقه (سکه)</th>
                        <td>
                            <label>رتبه ۱: <input type="number" name="novel_quiz_prize_1st" 
                                                   value="<?php echo absint(get_option('novel_quiz_prize_1st', 50)); ?>" min="0" style="width:80px"></label>
                            <label>رتبه ۲: <input type="number" name="novel_quiz_prize_2nd" 
                                                   value="<?php echo absint(get_option('novel_quiz_prize_2nd', 30)); ?>" min="0" style="width:80px"></label>
                            <label>رتبه ۳: <input type="number" name="novel_quiz_prize_3rd" 
                                                   value="<?php echo absint(get_option('novel_quiz_prize_3rd', 20)); ?>" min="0" style="width:80px"></label>
                            <label>شرکت: <input type="number" name="novel_quiz_prize_participate" 
                                                value="<?php echo absint(get_option('novel_quiz_prize_participate', 5)); ?>" min="0" style="width:80px"></label>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- ═══ بخش بنر نویسنده ═══ -->
            <div class="novel-admin-section">
                <h2>🎨 بنرهای نویسنده</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="novel_banner_enabled">فعال‌سازی بنر</label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="novel_banner_enabled" name="novel_banner_enabled" 
                                       value="1" <?php checked(get_option('novel_banner_enabled', true)); ?>>
                                فعال
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="novel_banner_duration">مدت اعتبار بنر (روز)</label></th>
                        <td><input type="number" id="novel_banner_duration" name="novel_banner_duration" 
                                   value="<?php echo absint(get_option('novel_banner_duration', 30)); ?>" min="1"></td>
                    </tr>
                    <tr>
                        <th><label for="novel_banner_max_active">حداکثر بنر فعال هر نویسنده</label></th>
                        <td><input type="number" id="novel_banner_max_active" name="novel_banner_max_active" 
                                   value="<?php echo absint(get_option('novel_banner_max_active', 3)); ?>" min="1" max="10"></td>
                    </tr>
                    <tr>
                        <th><label for="novel_banner_position">محل نمایش بنر</label></th>
                        <td>
                            <select id="novel_banner_position" name="novel_banner_position">
                                <option value="before_chapter" <?php selected(get_option('novel_banner_position'), 'before_chapter'); ?>>بالای قسمت</option>
                                <option value="after_chapter" <?php selected(get_option('novel_banner_position'), 'after_chapter'); ?>>بعد از قسمت</option>
                                <option value="both" <?php selected(get_option('novel_banner_position', 'both'), 'both'); ?>>هر دو</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- ═══ بخش اعلان‌ها ═══ -->
            <div class="novel-admin-section">
                <h2>🔔 اعلان‌ها</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="novel_notif_interval">فاصله بررسی اعلان (ثانیه)</label></th>
                        <td><input type="number" id="novel_notif_interval" name="novel_notif_interval" 
                                   value="<?php echo absint(get_option('novel_notif_interval', 60)); ?>" min="15" max="300"></td>
                    </tr>
                </table>
            </div>

            <!-- ═══ شبکه‌های اجتماعی ═══ -->
            <div class="novel-admin-section">
                <h2>📱 شبکه‌های اجتماعی</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="novel_social_telegram">تلگرام</label></th>
                        <td><input type="url" id="novel_social_telegram" name="novel_social_telegram" 
                                   value="<?php echo esc_url(get_option('novel_social_telegram', '')); ?>" class="regular-text" placeholder="https://t.me/..."></td>
                    </tr>
                    <tr>
                        <th><label for="novel_social_instagram">اینستاگرام</label></th>
                        <td><input type="url" id="novel_social_instagram" name="novel_social_instagram" 
                                   value="<?php echo esc_url(get_option('novel_social_instagram', '')); ?>" class="regular-text" placeholder="https://instagram.com/..."></td>
                    </tr>
                    <tr>
                        <th><label for="novel_social_twitter">توییتر (X)</label></th>
                        <td><input type="url" id="novel_social_twitter" name="novel_social_twitter" 
                                   value="<?php echo esc_url(get_option('novel_social_twitter', '')); ?>" class="regular-text" placeholder="https://x.com/..."></td>
                    </tr>
                </table>
            </div>

            <!-- ═══ حالت تعمیرات ═══ -->
            <div class="novel-admin-section">
                <h2>🔧 حالت تعمیرات</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="novel_maintenance_mode">فعال‌سازی تعمیرات</label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="novel_maintenance_mode" name="novel_maintenance_mode" 
                                       value="1" <?php checked(get_option('novel_maintenance_mode', false)); ?>>
                                ⚠️ سایت برای بازدیدکنندگان غیرقابل دسترسی می‌شود
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="novel_maintenance_message">پیام سفارشی</label></th>
                        <td>
                            <textarea id="novel_maintenance_message" name="novel_maintenance_message" rows="3" class="large-text"
                            ><?php echo esc_textarea(get_option('novel_maintenance_message', '')); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="novel_maintenance_eta">زمان تخمینی</label></th>
                        <td><input type="text" id="novel_maintenance_eta" name="novel_maintenance_eta" 
                                   value="<?php echo esc_attr(get_option('novel_maintenance_eta', '')); ?>" placeholder="تا ساعت ۲۲:۰۰"></td>
                    </tr>
                </table>
            </div>

            <p class="submit">
                <button type="submit" class="button button-primary button-hero">💾 ذخیره تنظیمات</button>
            </p>
        </form>

        <script>
        jQuery(function($) {
            // آپلود لوگو
            $('#novel-upload-logo').on('click', function(e) {
                e.preventDefault();
                var frame = wp.media({
                    title: 'انتخاب لوگو',
                    button: { text: 'انتخاب' },
                    multiple: false
                });
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#novel_site_logo').val(attachment.url);
                    $('#novel-logo-preview').attr('src', attachment.url).show();
                    $('#novel-remove-logo').show();
                });
                frame.open();
            });
            $('#novel-remove-logo').on('click', function(e) {
                e.preventDefault();
                $('#novel_site_logo').val('');
                $('#novel-logo-preview').hide();
                $(this).hide();
            });
        });
        </script>
        <?php
        $this->render_page_footer();
    }

    /**
     * رندر صفحه ماژول‌ها
     */
    public function render_modules_page() {
        if (!current_user_can('manage_options')) return;

        // ذخیره
        if (isset($_POST['novel_modules_nonce']) && wp_verify_nonce($_POST['novel_modules_nonce'], 'novel_save_modules')) {
            $all_modules = Novel_Settings::get_all_modules();
            foreach ($all_modules as $slug => $info) {
                $key = 'novel_module_' . $slug;
                $value = isset($_POST[$key]) ? '1' : '0';
                update_option($key, $value);
            }
            Novel_Settings::flush_cache();
            echo '<div class="notice notice-success"><p>✅ ماژول‌ها به‌روزرسانی شدند.</p></div>';
        }

        $all_modules = Novel_Settings::get_all_modules();
        $groups = Novel_Settings::get_module_groups();

        $this->render_page_header('مدیریت ماژول‌ها');
        ?>
        <p class="description">هر ماژول را می‌توانید فعال یا غیرفعال کنید. غیرفعال کردن باعث عدم لود کد مربوطه می‌شود.</p>

        <form method="post">
            <?php wp_nonce_field('novel_save_modules', 'novel_modules_nonce'); ?>

            <div class="novel-modules-grid">
                <?php foreach ($groups as $group_slug => $group_title) : ?>
                <div class="novel-module-group">
                    <h3><?php echo esc_html($group_title); ?></h3>
                    <div class="novel-module-list">
                        <?php
                        foreach ($all_modules as $slug => $info) :
                            if ($info['group'] !== $group_slug) continue;
                            $key = 'novel_module_' . $slug;
                            $active = get_option($key, '1') === '1';
                        ?>
                        <label class="novel-module-toggle <?php echo $active ? 'active' : ''; ?>">
                            <input type="checkbox" name="<?php echo esc_attr($key); ?>" value="1" 
                                   <?php checked($active); ?>>
                            <span class="toggle-switch"></span>
                            <span class="toggle-label"><?php echo esc_html($info['title']); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <p class="submit">
                <button type="submit" class="button button-primary button-hero">💾 ذخیره ماژول‌ها</button>
            </p>
        </form>
        <?php
        $this->render_page_footer();
    }

    /**
     * رندر صفحه ظاهر
     */
    public function render_appearance_page() {
        if (!current_user_can('manage_options')) return;
        $this->render_page_header('ظاهر و طراحی');
        echo '<p>این بخش در فازهای بعد تکمیل می‌شود.</p>';
        $this->render_page_footer();
    }


    /* فاز 13*/

/**
 * Announcement Banner Settings Section
 * بخش تنظیمات بنر اطلاعیه سایت
 * 
 * ⚠️ تمام آپشن‌ها از prefix novel_announcement_ استفاده می‌کنند
 *    (نه novel_banner_ که مربوط به بنر تبلیغاتی نویسنده است)
 * 
 * اضافه شود به class-novel-admin-settings.php → render_settings_page()
 * به عنوان تب/بخش جدید «بنر اطلاعیه»
 * 
 * @package suspended developer
 * @since 3.0.0
 */

// === این متد به کلاس Novel_Admin_Settings اضافه شود ===

/**
 * رندر تنظیمات بنر اطلاعیه سایت
 */
public function render_announcement_settings_section() {
    // ذخیره تنظیمات
    if (isset($_POST['novel_save_announcement']) && wp_verify_nonce($_POST['_wpnonce_announcement'] ?? '', 'novel_announcement_settings')) {
        update_option('novel_announcement_enabled', !empty($_POST['novel_announcement_enabled']));
        update_option('novel_announcement_text', wp_kses($_POST['novel_announcement_text'] ?? '', [
            'a'      => ['href' => [], 'target' => [], 'rel' => []],
            'b'      => [],
            'em'     => [],
            'strong' => [],
        ]));
        update_option('novel_announcement_type', sanitize_text_field($_POST['novel_announcement_type'] ?? 'info'));
        update_option('novel_announcement_link_url', esc_url_raw($_POST['novel_announcement_link_url'] ?? ''));
        update_option('novel_announcement_link_text', sanitize_text_field($_POST['novel_announcement_link_text'] ?? ''));
        update_option('novel_announcement_start_date', sanitize_text_field($_POST['novel_announcement_start_date'] ?? ''));
        update_option('novel_announcement_end_date', sanitize_text_field($_POST['novel_announcement_end_date'] ?? ''));
        update_option('novel_announcement_dismissible', !empty($_POST['novel_announcement_dismissible']));
        update_option('novel_announcement_audience', sanitize_text_field($_POST['novel_announcement_audience'] ?? 'all'));

        echo '<div class="notice notice-success is-dismissible"><p>تنظیمات بنر اطلاعیه ذخیره شد. ✅</p></div>';
    }

    $enabled     = get_option('novel_announcement_enabled', false);
    $text        = get_option('novel_announcement_text', '');
    $type        = get_option('novel_announcement_type', 'info');
    $link_url    = get_option('novel_announcement_link_url', '');
    $link_text   = get_option('novel_announcement_link_text', '');
    $start_date  = get_option('novel_announcement_start_date', '');
    $end_date    = get_option('novel_announcement_end_date', '');
    $dismissible = get_option('novel_announcement_dismissible', true);
    $audience    = get_option('novel_announcement_audience', 'all');
    ?>

    <form method="post">
        <?php wp_nonce_field('novel_announcement_settings', '_wpnonce_announcement'); ?>
        <input type="hidden" name="novel_save_announcement" value="1">

        <div class="novel-admin-section">
            <h2>📢 بنر اطلاعیه سایت</h2>
            <p class="description">
                بنر اطلاعیه بالای سایت نمایش داده می‌شود. 
                <br><em>⚠️ این با «بنر تبلیغاتی نویسنده» متفاوت است. بنر نویسنده در تنظیمات جداگانه مدیریت می‌شود.</em>
            </p>

            <table class="form-table">
                <tr>
                    <th><label for="novel_announcement_enabled">فعال‌سازی</label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="novel_announcement_enabled" 
                                   name="novel_announcement_enabled"
                                   value="1" <?php checked($enabled); ?>>
                            نمایش بنر اطلاعیه در بالای سایت
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label for="novel_announcement_text">متن بنر</label></th>
                    <td>
                        <textarea id="novel_announcement_text" name="novel_announcement_text" 
                                  rows="3" class="large-text" 
                                  placeholder="متن اطلاعیه... (HTML ساده مجاز: a, b, em, strong)"
                        ><?php echo esc_textarea($text); ?></textarea>
                        <p class="description">تگ‌های مجاز: <code>&lt;a&gt;</code>, <code>&lt;b&gt;</code>, <code>&lt;em&gt;</code>, <code>&lt;strong&gt;</code></p>
                    </td>
                </tr>
                <tr>
                    <th>نوع / رنگ</th>
                    <td>
                        <fieldset>
                            <label style="margin-left: 16px;">
                                <input type="radio" name="novel_announcement_type" value="info" 
                                       <?php checked($type, 'info'); ?>>
                                🔵 اطلاعیه (آبی)
                            </label>
                            <label style="margin-left: 16px;">
                                <input type="radio" name="novel_announcement_type" value="warning" 
                                       <?php checked($type, 'warning'); ?>>
                                🟡 هشدار (زرد)
                            </label>
                            <label style="margin-left: 16px;">
                                <input type="radio" name="novel_announcement_type" value="danger" 
                                       <?php checked($type, 'danger'); ?>>
                                🔴 خطر (قرمز)
                            </label>
                            <label style="margin-left: 16px;">
                                <input type="radio" name="novel_announcement_type" value="success" 
                                       <?php checked($type, 'success'); ?>>
                                🟢 موفقیت (سبز)
                            </label>
                            <label style="margin-left: 16px;">
                                <input type="radio" name="novel_announcement_type" value="promo" 
                                       <?php checked($type, 'promo'); ?>>
                                🟣 ویژه (بنفش gradient)
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th><label for="novel_announcement_link_url">لینک (اختیاری)</label></th>
                    <td>
                        <input type="url" id="novel_announcement_link_url" 
                               name="novel_announcement_link_url"
                               value="<?php echo esc_url($link_url); ?>" 
                               class="regular-text"
                               placeholder="https://example.com">
                        <br>
                        <input type="text" id="novel_announcement_link_text" 
                               name="novel_announcement_link_text"
                               value="<?php echo esc_attr($link_text); ?>" 
                               class="regular-text"
                               placeholder="متن دکمه لینک (مثلاً: بیشتر بخوانید)" 
                               style="margin-top: 6px;">
                        <p class="description">اگر لینک و متن وارد شود، دکمه‌ای در بنر نمایش داده می‌شود.</p>
                    </td>
                </tr>
                <tr>
                    <th><label>زمان‌بندی (اختیاری)</label></th>
                    <td>
                        <label style="display: inline-block; margin-bottom: 8px;">
                            شروع:
                            <input type="datetime-local" name="novel_announcement_start_date"
                                   value="<?php echo esc_attr($start_date); ?>">
                        </label>
                        <br>
                        <label>
                            پایان:
                            <input type="datetime-local" name="novel_announcement_end_date"
                                   value="<?php echo esc_attr($end_date); ?>">
                        </label>
                        <p class="description">اگر خالی باشد، بنر بدون محدودیت زمانی نمایش داده می‌شود. بعد از تاریخ پایان خودکار مخفی می‌شود.</p>
                    </td>
                </tr>
                <tr>
                    <th>قابل بسته‌شدن</th>
                    <td>
                        <label>
                            <input type="checkbox" name="novel_announcement_dismissible"
                                   value="1" <?php checked($dismissible); ?>>
                            کاربر بتواند بنر را ببندد (دکمه ×)
                        </label>
                        <p class="description">بعد از بسته شدن، ۲۴ ساعت بعد دوباره نمایش داده می‌شود (یا تا تغییر متن بنر).</p>
                    </td>
                </tr>
                <tr>
                    <th>نمایش برای</th>
                    <td>
                        <fieldset>
                            <label style="margin-left: 16px;">
                                <input type="radio" name="novel_announcement_audience" value="all" 
                                       <?php checked($audience, 'all'); ?>>
                                همه بازدیدکنندگان
                            </label>
                            <label style="margin-left: 16px;">
                                <input type="radio" name="novel_announcement_audience" value="logged_in" 
                                       <?php checked($audience, 'logged_in'); ?>>
                                فقط کاربران لاگین‌شده
                            </label>
                            <label style="margin-left: 16px;">
                                <input type="radio" name="novel_announcement_audience" value="logged_out" 
                                       <?php checked($audience, 'logged_out'); ?>>
                                فقط مهمانان (غیرلاگین)
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </table>

            <!-- پیش‌نمایش -->
            <?php if ($enabled && !empty(trim($text))): ?>
                <div style="margin-top: 20px; padding: 16px; background: #f0f4ff; border-radius: 10px;">
                    <h4 style="margin: 0 0 10px;">👁 پیش‌نمایش بنر فعلی:</h4>
                    <div class="novel-announcement-preview" style="
                        padding: 10px 16px;
                        border-radius: 8px;
                        font-size: 14px;
                        <?php
                        $preview_styles = [
                            'info'    => 'background:#dbeafe;color:#1e40af;border:1px solid #93c5fd;',
                            'warning' => 'background:#fef3c7;color:#92400e;border:1px solid #fcd34d;',
                            'danger'  => 'background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;',
                            'success' => 'background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;',
                            'promo'   => 'background:linear-gradient(90deg,#7c3aed,#6366f1);color:#fff;',
                        ];
                        echo $preview_styles[$type] ?? $preview_styles['info'];
                        ?>
                    ">
                        <?php echo wp_kses($text, ['a' => ['href' => []], 'b' => [], 'em' => [], 'strong' => []]); ?>
                        <?php if ($link_url && $link_text): ?>
                            <span style="margin-right: 10px; padding: 3px 10px; border-radius: 5px; background: rgba(0,0,0,0.15); font-weight: 700;">
                                <?php echo esc_html($link_text); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php submit_button('💾 ذخیره تنظیمات بنر اطلاعیه'); ?>
        </div>
    </form>

    <?php
}


/**
 * صفحه بنر اطلاعیه
 */
public function render_announcement_page() {
    if (!current_user_can('manage_options')) return;
    $this->render_page_header('بنر اطلاعیه');
    $this->render_announcement_settings_section();
    $this->render_page_footer();
}


    /**
     * ذخیره تنظیمات
     */
    private function save_settings() {
        $text_fields = [
            'novel_site_description', 'novel_comment_encourage', 'novel_comment_warning',
            'novel_bad_words', 'novel_maintenance_message', 'novel_maintenance_eta',
            'novel_quiz_daily_time', 'novel_site_logo',
        ];
        foreach ($text_fields as $field) {
            if (isset($_POST[$field])) {
                update_option($field, sanitize_textarea_field($_POST[$field]));
            }
        }

        $number_fields = [
            'novel_min_comment_chars', 'novel_max_comment_chars',
            'novel_min_review_words', 'novel_min_theory_words',
            'novel_comment_edit_time', 'novel_login_attempts', 'novel_login_lockout',
            'novel_author_commission', 'novel_min_payout', 'novel_coin_expiry_days',
            'novel_notif_interval',
            'novel_quiz_prize_1st', 'novel_quiz_prize_2nd',
            'novel_quiz_prize_3rd', 'novel_quiz_prize_participate',
            'novel_banner_duration', 'novel_banner_max_active',
        ];
        foreach ($number_fields as $field) {
            if (isset($_POST[$field])) {
                update_option($field, absint($_POST[$field]));
            }
        }

        $url_fields = ['novel_social_telegram', 'novel_social_instagram', 'novel_social_twitter'];
        foreach ($url_fields as $field) {
            if (isset($_POST[$field])) {
                update_option($field, esc_url_raw($_POST[$field]));
            }
        }

        $checkbox_fields = [
            'novel_allow_user_author', 'novel_maintenance_mode',
            'novel_quiz_daily_auto', 'novel_banner_enabled',
        ];
        foreach ($checkbox_fields as $field) {
            update_option($field, isset($_POST[$field]) ? '1' : '0');
        }

        $page_fields = ['novel_rules_page', 'novel_comment_rules_page'];
        foreach ($page_fields as $field) {
            if (isset($_POST[$field])) {
                update_option($field, absint($_POST[$field]));
            }
        }

        $select_fields = ['novel_banner_position'];
        foreach ($select_fields as $field) {
            if (isset($_POST[$field])) {
                update_option($field, sanitize_key($_POST[$field]));
            }
        }
    }

    /**
     * هدر صفحه ادمین
     */
    private function render_page_header($title) {
        ?>
        <div class="wrap novel-admin-wrap">
            <div class="novel-admin-header">
                <h1>📖 <?php echo esc_html($title); ?></h1>
                <p>نسخه <?php echo esc_html(NOVEL_VERSION); ?> | نسخه DB: <?php echo esc_html(get_option('novel_db_version', 'N/A')); ?></p>
            </div>
        <?php
    }

    /**
     * فوتر صفحه ادمین
     */
    private function render_page_footer() {
        echo '</div><!-- .novel-admin-wrap -->';
    }
}

new Novel_Admin_Settings();