<?php
/**
 * Novel Smart Features
 * 
 * قابلیت‌های خلاقانه و هوشمند:
 * - حالت خواندن شبانه هوشمند (Auto Night Mode)
 * - لیست خواندن روزانه پیشنهادی (Daily Reading List)
 * - فیلتر احساسی / حال‌وهوای من (Mood Discovery)
 * - ردیاب بازگشت (Catch-up Tracker)
 * 
 * @package suspended developer
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

class Novel_Smart_Features {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // AJAX endpoints
        add_action('wp_ajax_novel_get_daily_recommendations', [$this, 'ajax_daily_recommendations']);
        add_action('wp_ajax_novel_get_mood_novels', [$this, 'ajax_mood_novels']);
        add_action('wp_ajax_novel_get_catchup_info', [$this, 'ajax_catchup_info']);
        add_action('wp_ajax_novel_save_theme_preference', [$this, 'ajax_save_theme_pref']);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // Footer inline script for auto night mode
        add_action('wp_footer', [$this, 'render_night_mode_script'], 5);
    }

    /**
     * Assets
     */
    public function enqueue_assets() {
        wp_enqueue_script(
            'novel-smart-features',
            get_template_directory_uri() . '/assets/js/smart-features.js',
            ['jquery'],
            JEsuspended_DEVELOPER_VERSION,
            true
        );

        wp_localize_script('novel-smart-features', 'NovelSmart', [
            'ajaxurl'   => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('novel_smart_nonce'),
            'is_logged' => is_user_logged_in(),
            'theme_pref' => is_user_logged_in()
                ? get_user_meta(get_current_user_id(), 'novel_theme_mode', true) ?: 'auto'
                : 'auto',
            'mood_map' => $this->get_mood_map(),
            'strings' => [
                'loading'      => 'در حال بارگذاری...',
                'error'        => 'خطا در دریافت اطلاعات',
                'no_results'   => 'نتیجه‌ای یافت نشد',
                'continue'     => 'ادامه از قسمت',
                'new_chapters' => 'قسمت جدید',
                'saved'        => 'ذخیره شد ✅',
            ],
        ]);

        wp_enqueue_style(
            'novel-smart-features',
            get_template_directory_uri() . '/assets/css/smart-features.css',
            [],
            JEsuspended_DEVELOPER_VERSION
        );
    }

    // ═══════════════════════════════════════
    // ۱. حالت شبانه هوشمند
    // ═══════════════════════════════════════

    /**
     * اسکریپت inline برای تشخیص خودکار حالت شب
     * این اسکریپت باید سریع اجرا شود (قبل از رندر) تا flash نداشته باشیم
     */
    public function render_night_mode_script() {
        $user_pref = 'auto';
        if (is_user_logged_in()) {
            $user_pref = get_user_meta(get_current_user_id(), 'novel_theme_mode', true) ?: 'auto';
        }
        ?>
        <script id="novel-night-mode">
        (function() {
            var pref = '<?php echo esc_js($user_pref); ?>';
            var stored = localStorage.getItem('novel_theme_mode');
            var mode = stored || pref;

            function applyTheme(theme) {
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('novel_current_theme', theme);
            }

            function getAutoTheme() {
                var hour = new Date().getHours();
                // بین ۲۰ تا ۷ → dark
                if (hour >= 20 || hour < 7) return 'dark';
                return 'light';
            }

            switch (mode) {
                case 'dark':
                    applyTheme('dark');
                    break;
                case 'light':
                    applyTheme('light');
                    break;
                case 'system':
                    var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    applyTheme(prefersDark ? 'dark' : 'light');
                    // Listen for system changes
                    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                        applyTheme(e.matches ? 'dark' : 'light');
                    });
                    break;
                case 'auto':
                default:
                    applyTheme(getAutoTheme());
                    // بررسی هر ۵ دقیقه
                    setInterval(function() {
                        if ((localStorage.getItem('novel_theme_mode') || 'auto') === 'auto') {
                            applyTheme(getAutoTheme());
                        }
                    }, 300000);
                    break;
            }

            // فیلتر نور آبی شبانه (اختیاری)
            var nightFilter = localStorage.getItem('novel_night_filter');
            if (nightFilter !== '0') {
                var h = new Date().getHours();
                if (h >= 22 || h < 6) {
                    document.documentElement.style.filter = 'sepia(12%) brightness(96%)';
                    document.documentElement.dataset.nightFilter = 'on';
                }
            }
        })();
        </script>
        <?php
    }

    /**
     * AJAX: ذخیره ترجیح تم کاربر
     */
    public function ajax_save_theme_pref() {
        check_ajax_referer('novel_smart_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error();
        }

        $mode = sanitize_text_field($_POST['mode'] ?? 'auto');
        if (!in_array($mode, ['auto', 'light', 'dark', 'system'])) {
            $mode = 'auto';
        }

        update_user_meta(get_current_user_id(), 'novel_theme_mode', $mode);

        wp_send_json_success(['mode' => $mode]);
    }

    // ═══════════════════════════════════════
    // ۲. لیست خواندن پیشنهادی روزانه
    // ═══════════════════════════════════════

    /**
     * AJAX: دریافت پیشنهادات روزانه
     */
    public function ajax_daily_recommendations() {
        check_ajax_referer('novel_smart_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'لاگین نیستید.']);
        }

        $user_id = get_current_user_id();
        $cache_key = 'novel_daily_rec_' . $user_id;
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            wp_send_json_success(['novels' => $cached]);
        }

        $novels = $this->generate_recommendations($user_id);

        set_transient($cache_key, $novels, 24 * HOUR_IN_SECONDS);

        wp_send_json_success(['novels' => $novels]);
    }

    /**
     * تولید پیشنهادات
     */
    private function generate_recommendations($user_id) {
        global $wpdb;

        $recommendations = [];
        $exclude_ids = [];

        // ① ژانرهای محبوب کاربر
        $favorite_genres = $this->get_user_favorite_genres($user_id);

        // ② رمان‌هایی که کاربر خوانده (برای exclude)
        $read_novel_ids = $this->get_user_read_novels($user_id);
        $exclude_ids = $read_novel_ids;

        // ③ بر اساس ژانرهای محبوب
        if (!empty($favorite_genres)) {
            $genre_novels = $this->get_novels_by_genres(
                array_slice($favorite_genres, 0, 3),
                $exclude_ids,
                3
            );
            foreach ($genre_novels as $novel) {
                $genre_names = wp_get_post_terms($novel->ID, 'genre', ['fields' => 'names']);
                $recommendations[] = [
                    'id'      => $novel->ID,
                    'title'   => $novel->post_title,
                    'url'     => get_permalink($novel->ID),
                    'image'   => get_the_post_thumbnail_url($novel->ID, 'medium') ?: '',
                    'rating'  => (float)get_post_meta($novel->ID, '_novel_rating', true),
                    'reason'  => sprintf('چون %s دوست دارید', implode(' و ', array_slice($genre_names, 0, 2))),
                ];
                $exclude_ids[] = $novel->ID;
            }
        }

        // ④ Collaborative: کاربرانی که رمان‌های مشابه خوانده‌اند
        if (!empty($read_novel_ids)) {
            $collab = $this->get_collaborative_recommendations($user_id, $read_novel_ids, $exclude_ids, 2);
            foreach ($collab as $novel) {
                $recommendations[] = [
                    'id'      => $novel->ID,
                    'title'   => $novel->post_title,
                    'url'     => get_permalink($novel->ID),
                    'image'   => get_the_post_thumbnail_url($novel->ID, 'medium') ?: '',
                    'rating'  => (float)get_post_meta($novel->ID, '_novel_rating', true),
                    'reason'  => 'خوانندگان مشابه شما این را هم خوانده‌اند',
                ];
                $exclude_ids[] = $novel->ID;
            }
        }

        // ⑤ رمان‌های جدید محبوب (پرکننده)
        if (count($recommendations) < 5) {
            $needed = 5 - count($recommendations);
            $popular = get_posts([
                'post_type'      => 'novel',
                'posts_per_page' => $needed,
                'post_status'    => 'publish',
                'post__not_in'   => $exclude_ids,
                'meta_key'       => '_novel_views',
                'orderby'        => 'meta_value_num',
                'order'          => 'DESC',
                'date_query'     => [['after' => '-60 days']],
            ]);

            foreach ($popular as $novel) {
                $recommendations[] = [
                    'id'      => $novel->ID,
                    'title'   => $novel->post_title,
                    'url'     => get_permalink($novel->ID),
                    'image'   => get_the_post_thumbnail_url($novel->ID, 'medium') ?: '',
                    'rating'  => (float)get_post_meta($novel->ID, '_novel_rating', true),
                    'reason'  => 'رمان محبوب جدید',
                ];
            }
        }

        // ترکیب و محدود
        shuffle($recommendations);
        return array_slice($recommendations, 0, 5);
    }

    /**
     * ژانرهای محبوب کاربر
     */
    private function get_user_favorite_genres($user_id) {
        global $wpdb;

        if (!$wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}reading_history'")) {
            return [];
        }

        $genres = $wpdb->get_col($wpdb->prepare(
            "SELECT tt.term_id
             FROM {$wpdb->prefix}reading_history rh
             INNER JOIN {$wpdb->postmeta} pm ON rh.chapter_id = pm.post_id AND pm.meta_key = '_novel_id'
             INNER JOIN {$wpdb->term_relationships} tr ON pm.meta_value = tr.object_id
             INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'genre'
             WHERE rh.user_id = %d
             GROUP BY tt.term_id
             ORDER BY COUNT(*) DESC
             LIMIT 5",
            $user_id
        ));

        return array_map('absint', $genres);
    }

    /**
     * رمان‌هایی که کاربر خوانده
     */
    private function get_user_read_novels($user_id) {
        global $wpdb;

        if (!$wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}reading_history'")) {
            return [];
        }

        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT pm.meta_value
             FROM {$wpdb->prefix}reading_history rh
             INNER JOIN {$wpdb->postmeta} pm ON rh.chapter_id = pm.post_id AND pm.meta_key = '_novel_id'
             WHERE rh.user_id = %d",
            $user_id
        ));

        return array_map('absint', $ids);
    }

    /**
     * رمان‌ها بر اساس ژانر
     */
    private function get_novels_by_genres($genre_ids, $exclude_ids, $limit = 3) {
        $args = [
            'post_type'      => 'novel',
            'posts_per_page' => $limit,
            'post_status'    => 'publish',
            'post__not_in'   => $exclude_ids ?: [0],
            'orderby'        => 'rand',
            'tax_query'      => [[
                'taxonomy' => 'genre',
                'field'    => 'term_id',
                'terms'    => $genre_ids,
            ]],
        ];

        return get_posts($args);
    }

    /**
     * Collaborative filtering ساده
     */
    private function get_collaborative_recommendations($user_id, $read_novels, $exclude_ids, $limit = 2) {
        global $wpdb;

        if (empty($read_novels) || !$wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}reading_history'")) {
            return [];
        }

        $novel_ids_str = implode(',', array_map('absint', array_slice($read_novels, 0, 10)));
        $exclude_str = !empty($exclude_ids)
            ? 'AND pm2.meta_value NOT IN (' . implode(',', array_map('absint', $exclude_ids)) . ')'
            : '';

        $results = $wpdb->get_col(
            "SELECT DISTINCT pm2.meta_value
             FROM {$wpdb->prefix}reading_history rh1
             INNER JOIN {$wpdb->postmeta} pm1 ON rh1.chapter_id = pm1.post_id AND pm1.meta_key = '_novel_id'
             INNER JOIN {$wpdb->prefix}reading_history rh2 ON rh1.user_id = rh2.user_id AND rh2.user_id != {$user_id}
             INNER JOIN {$wpdb->postmeta} pm2 ON rh2.chapter_id = pm2.post_id AND pm2.meta_key = '_novel_id'
             WHERE pm1.meta_value IN ({$novel_ids_str})
             AND pm2.meta_value NOT IN ({$novel_ids_str})
             {$exclude_str}
             GROUP BY pm2.meta_value
             ORDER BY COUNT(*) DESC
             LIMIT {$limit}"
        );

        if (empty($results)) return [];

        return get_posts([
            'post_type'      => 'novel',
            'post__in'       => array_map('absint', $results),
            'posts_per_page' => $limit,
            'post_status'    => 'publish',
        ]);
    }

    // ═══════════════════════════════════════
    // ۳. فیلتر احساسی (Mood Discovery)
    // ═══════════════════════════════════════

    /**
     * نقشه حال‌وهوا به ژانرها
     */
    public function get_mood_map() {
        $default_map = [
            'laugh' => [
                'label'  => '😂 می‌خوام بخندم',
                'icon'   => '😂',
                'genres' => ['comedy', 'slice-of-life'],
                'tags'   => [],
                'color'  => '#f59e0b',
            ],
            'sad' => [
                'label'  => '😢 غمگینم',
                'icon'   => '😢',
                'genres' => ['drama', 'romance'],
                'tags'   => ['tragedy'],
                'color'  => '#3b82f6',
            ],
            'adventure' => [
                'label'  => '💪 حس ماجراجویی',
                'icon'   => '💪',
                'genres' => ['adventure', 'action'],
                'tags'   => ['isekai'],
                'color'  => '#10b981',
            ],
            'romance' => [
                'label'  => '❤ عاشقانه می‌خوام',
                'icon'   => '❤',
                'genres' => ['romance'],
                'tags'   => [],
                'color'  => '#ec4899',
            ],
            'think' => [
                'label'  => '🧠 چالش ذهنی',
                'icon'   => '🧠',
                'genres' => ['mystery', 'psychology'],
                'tags'   => ['mind-game'],
                'color'  => '#8b5cf6',
            ],
            'thrill' => [
                'label'  => '😱 هیجان و ترس',
                'icon'   => '😱',
                'genres' => ['horror', 'thriller'],
                'tags'   => [],
                'color'  => '#ef4444',
            ],
            'calm' => [
                'label'  => '🧘 آرامش',
                'icon'   => '🧘',
                'genres' => ['slice-of-life'],
                'tags'   => ['healing'],
                'color'  => '#06b6d4',
            ],
            'action' => [
                'label'  => '🔥 اکشن محض',
                'icon'   => '🔥',
                'genres' => ['action'],
                'tags'   => ['op-mc', 'battles'],
                'color'  => '#f97316',
            ],
        ];

        return apply_filters('novel_mood_map', $default_map);
    }

    /**
     * AJAX: رمان‌ها بر اساس حال‌وهوا
     */
    public function ajax_mood_novels() {
        check_ajax_referer('novel_smart_nonce', 'nonce');

        $mood = sanitize_text_field($_POST['mood'] ?? '');
        $mood_map = $this->get_mood_map();

        if (!isset($mood_map[$mood])) {
            wp_send_json_error(['message' => 'حال‌وهوای نامعتبر']);
        }

        $config = $mood_map[$mood];
        $genre_slugs = $config['genres'] ?? [];
        $tag_slugs = $config['tags'] ?? [];

        $tax_query = ['relation' => 'OR'];

        if (!empty($genre_slugs)) {
            $tax_query[] = [
                'taxonomy' => 'genre',
                'field'    => 'slug',
                'terms'    => $genre_slugs,
            ];
        }

        if (!empty($tag_slugs)) {
            $tax_query[] = [
                'taxonomy' => 'novel_tag',
                'field'    => 'slug',
                'terms'    => $tag_slugs,
            ];
        }

        $novels = get_posts([
            'post_type'      => 'novel',
            'posts_per_page' => 6,
            'post_status'    => 'publish',
            'orderby'        => 'rand',
            'tax_query'      => $tax_query,
        ]);

        $data = [];
        foreach ($novels as $novel) {
            $data[] = [
                'id'     => $novel->ID,
                'title'  => $novel->post_title,
                'url'    => get_permalink($novel->ID),
                'image'  => get_the_post_thumbnail_url($novel->ID, 'medium') ?: '',
                'rating' => (float)get_post_meta($novel->ID, '_novel_rating', true),
            ];
        }

        wp_send_json_success([
            'novels' => $data,
            'mood'   => $config,
        ]);
    }

    // ═══════════════════════════════════════
    // ۴. ردیاب بازگشت (Catch-up Tracker)
    // ═══════════════════════════════════════

    /**
     * AJAX: اطلاعات catch-up برای یک رمان
     */
    public function ajax_catchup_info() {
        check_ajax_referer('novel_smart_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error();
        }

        $novel_id = absint($_POST['novel_id'] ?? 0);
        if (!$novel_id) wp_send_json_error();

        $user_id = get_current_user_id();
        $info = $this->get_catchup_data($novel_id, $user_id);

        wp_send_json_success($info);
    }

    /**
     * داده‌های catch-up
     */
    public function get_catchup_data($novel_id, $user_id) {
        global $wpdb;

        if (!$wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}reading_history'")) {
            return ['show' => false];
        }

        // آخرین قسمت خوانده شده
        $last_read = $wpdb->get_row($wpdb->prepare(
            "SELECT rh.chapter_id, rh.read_at, pm2.meta_value as chapter_number
             FROM {$wpdb->prefix}reading_history rh
             INNER JOIN {$wpdb->postmeta} pm ON rh.chapter_id = pm.post_id AND pm.meta_key = '_novel_id'
             LEFT JOIN {$wpdb->postmeta} pm2 ON rh.chapter_id = pm2.post_id AND pm2.meta_key = '_chapter_number'
             WHERE pm.meta_value = %d AND rh.user_id = %d
             ORDER BY rh.read_at DESC LIMIT 1",
            $novel_id, $user_id
        ));

        if (!$last_read) {
            return ['show' => false];
        }

        // اگر کمتر از ۷ روز پیش خوانده → نشون نده
        $days_ago = (int)floor((time() - strtotime($last_read->read_at)) / 86400);
        if ($days_ago < 7) {
            return ['show' => false];
        }

        $last_chapter_num = (int)$last_read->chapter_number;

        // تعداد قسمت‌های جدید بعد از آن
        $new_chapters = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_novel_id'
             INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_chapter_number'
             WHERE pm.meta_value = %d AND p.post_type = 'chapter' AND p.post_status = 'publish'
             AND CAST(pm2.meta_value AS UNSIGNED) > %d",
            $novel_id, $last_chapter_num
        ));

        if ($new_chapters < 1) {
            return ['show' => false];
        }

        // قسمت بعدی
        $next_chapter = $wpdb->get_row($wpdb->prepare(
            "SELECT p.ID, pm2.meta_value as chapter_number
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_novel_id'
             INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_chapter_number'
             WHERE pm.meta_value = %d AND p.post_type = 'chapter' AND p.post_status = 'publish'
             AND CAST(pm2.meta_value AS UNSIGNED) > %d
             ORDER BY CAST(pm2.meta_value AS UNSIGNED) ASC LIMIT 1",
            $novel_id, $last_chapter_num
        ));

        return [
            'show'             => true,
            'last_chapter'     => $last_chapter_num,
            'new_count'        => (int)$new_chapters,
            'days_ago'         => $days_ago,
            'next_chapter_id'  => $next_chapter ? $next_chapter->ID : 0,
            'next_chapter_url' => $next_chapter ? get_permalink($next_chapter->ID) : '',
            'next_chapter_num' => $next_chapter ? (int)$next_chapter->chapter_number : 0,
        ];
    }

    // ═══════════════════════════════════════
    // Render Components
    // ═══════════════════════════════════════

    /**
     * رندر بخش حال‌وهوا (صفحه اصلی)
     */
    public function render_mood_section() {
        $moods = $this->get_mood_map();
        ?>
        <section class="novel-section novel-mood-section">
            <div class="novel-container">
                <div class="novel-section-header">
                    <h2 class="novel-section-title">
                        <span class="novel-section-icon">🎭</span>
                        الان چه حالی داری؟
                    </h2>
                    <p class="novel-section-subtitle">بر اساس حال‌وهوات رمان مناسبت رو پیدا کن!</p>
                </div>

                <div class="novel-mood-grid" id="novelMoodGrid">
                    <?php foreach ($moods as $key => $mood): ?>
                        <button class="novel-mood-btn"
                                data-mood="<?php echo esc_attr($key); ?>"
                                style="--mood-color: <?php echo esc_attr($mood['color']); ?>;">
                            <span class="novel-mood-btn__icon"><?php echo $mood['icon']; ?></span>
                            <span class="novel-mood-btn__label"><?php echo esc_html(str_replace($mood['icon'] . ' ', '', $mood['label'])); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- نتایج -->
                <div class="novel-mood-results" id="novelMoodResults" style="display: none;">
                    <div class="novel-mood-results__header">
                        <h3 class="novel-mood-results__title" id="moodResultsTitle"></h3>
                        <button class="novel-mood-results__back" id="moodResultsBack">← انتخاب دیگر</button>
                    </div>
                    <div class="novel-mood-results__grid" id="moodResultsGrid">
                        <!-- AJAX load -->
                    </div>
                </div>
            </div>
        </section>
        <?php
    }

    /**
     * رندر بخش پیشنهادات روزانه (صفحه اصلی)
     */
    public function render_daily_section() {
        if (!is_user_logged_in()) return;
        ?>
        <section class="novel-section novel-daily-section" id="novelDailySection">
            <div class="novel-container">
                <div class="novel-section-header">
                    <h2 class="novel-section-title">
                        <span class="novel-section-icon">📋</span>
                        پیشنهاد امروز برای شما
                    </h2>
                    <p class="novel-section-subtitle">بر اساس ذائقه و تاریخچه مطالعه شما</p>
                </div>
                <div class="novel-daily-grid" id="novelDailyGrid">
                    <div class="novel-daily-loading">
                        <div class="novel-spinner"></div>
                        <span>در حال بارگذاری پیشنهادات...</span>
                    </div>
                </div>
            </div>
        </section>
        <?php
    }

    /**
     * رندر بنر catch-up (صفحه رمان)
     */
    public function render_catchup_banner($novel_id) {
        if (!is_user_logged_in()) return;

        $data = $this->get_catchup_data($novel_id, get_current_user_id());
        if (!$data['show']) return;
        ?>
        <div class="novel-catchup-banner" id="novelCatchupBanner">
            <div class="novel-catchup-banner__icon">📚</div>
            <div class="novel-catchup-banner__content">
                <p class="novel-catchup-banner__text">
                    آخرین بار <strong>قسمت <?php echo number_format_i18n($data['last_chapter']); ?></strong> را خواندید.
                    از آن زمان <strong><?php echo number_format_i18n($data['new_count']); ?> قسمت جدید</strong> منتشر شده!
                </p>
            </div>
            <div class="novel-catchup-banner__actions">
                <?php if ($data['next_chapter_url']): ?>
                    <a href="<?php echo esc_url($data['next_chapter_url']); ?>" class="novel-btn novel-btn--primary novel-btn--sm">
                        ▶ ادامه از <?php echo number_format_i18n($data['next_chapter_num']); ?>
                    </a>
                <?php endif; ?>
                <button class="novel-btn novel-btn--outline novel-btn--sm novel-catchup-dismiss"
                        onclick="document.getElementById('novelCatchupBanner').style.display='none'">
                    ✕ بستن
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * رندر تنظیمات تم در داشبورد
     */
    public function render_theme_settings() {
        $current = is_user_logged_in()
            ? (get_user_meta(get_current_user_id(), 'novel_theme_mode', true) ?: 'auto')
            : 'auto';

        $night_filter = true; // default on
        ?>
        <div class="novel-theme-settings">
            <h4 class="novel-theme-settings__title">🌗 حالت نمایش</h4>
            <div class="novel-theme-options" id="novelThemeOptions">
                <?php
                $options = [
                    'auto'   => ['🌗 خودکار (ساعتی)', 'بین ۲۰ تا ۷ تاریک'],
                    'light'  => ['☀️ همیشه روشن', ''],
                    'dark'   => ['🌙 همیشه تاریک', ''],
                    'system' => ['💻 سیستم‌عامل', 'مطابق تنظیمات دستگاه'],
                ];
                foreach ($options as $key => $info):
                    ?>
                    <label class="novel-theme-option <?php echo $current === $key ? 'is-active' : ''; ?>">
                        <input type="radio" name="novel_theme_mode" value="<?php echo $key; ?>"
                               <?php checked($current, $key); ?>
                               class="novel-theme-radio">
                        <span class="novel-theme-option__label"><?php echo $info[0]; ?></span>
                        <?php if ($info[1]): ?>
                            <span class="novel-theme-option__desc"><?php echo $info[1]; ?></span>
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="novel-night-filter-toggle" style="margin-top: 16px;">
                <label>
                    <input type="checkbox" id="novelNightFilter" checked>
                    🌙 فیلتر نور آبی شبانه (بین ۲۲ تا ۶ صبح)
                </label>
                <small style="color: var(--color-text-secondary);">کاهش خستگی چشم هنگام مطالعه شبانه</small>
            </div>
        </div>
        <?php
    }
}