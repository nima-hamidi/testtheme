<?php
/**
 * Custom Taxonomies Registration
 * 
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register all custom taxonomies
 */
function novel_register_taxonomies() {
    
    // ═══ Taxonomy: genre (ژانر) ═══
    $genre_labels = [
        'name'              => 'ژانرها',
        'singular_name'     => 'ژانر',
        'search_items'      => 'جستجوی ژانر',
        'all_items'         => 'همه ژانرها',
        'parent_item'       => 'ژانر مادر',
        'parent_item_colon' => 'ژانر مادر:',
        'edit_item'         => 'ویرایش ژانر',
        'update_item'       => 'به‌روزرسانی ژانر',
        'add_new_item'      => 'افزودن ژانر جدید',
        'new_item_name'     => 'نام ژانر جدید',
        'menu_name'         => 'ژانرها',
        'not_found'         => 'ژانری یافت نشد.',
        'back_to_items'     => '← بازگشت به ژانرها',
    ];

    register_taxonomy('genre', ['novel'], [
        'labels'            => $genre_labels,
        'hierarchical'      => true,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => true,
        'show_in_rest'      => true,
        'rewrite'           => [
            'slug'         => 'genre',
            'with_front'   => false,
            'hierarchical' => true,
        ],
        'query_var'         => true,
    ]);

    // ═══ Taxonomy: novel_tag (تگ‌های محتوایی) ═══
    $tag_labels = [
        'name'                       => 'تگ‌ها',
        'singular_name'              => 'تگ',
        'search_items'               => 'جستجوی تگ',
        'popular_items'              => 'تگ‌های محبوب',
        'all_items'                  => 'همه تگ‌ها',
        'edit_item'                  => 'ویرایش تگ',
        'update_item'                => 'به‌روزرسانی تگ',
        'add_new_item'               => 'افزودن تگ جدید',
        'new_item_name'              => 'نام تگ جدید',
        'separate_items_with_commas' => 'تگ‌ها را با کاما جدا کنید',
        'add_or_remove_items'        => 'افزودن یا حذف تگ‌ها',
        'choose_from_most_used'      => 'انتخاب از پرکاربردترین‌ها',
        'not_found'                  => 'تگی یافت نشد.',
        'menu_name'                  => 'تگ‌ها',
        'back_to_items'              => '← بازگشت به تگ‌ها',
    ];

    register_taxonomy('novel_tag', ['novel'], [
        'labels'            => $tag_labels,
        'hierarchical'      => false,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => true,
        'show_in_rest'      => true,
        'rewrite'           => [
            'slug'       => 'tag',
            'with_front' => false,
        ],
        'query_var'         => true,
        // Users cannot create new tags - admin only
        'capabilities'      => [
            'assign_terms' => 'edit_posts',
            'edit_terms'   => 'manage_options',
            'manage_terms' => 'manage_options',
            'delete_terms' => 'manage_options',
        ],
    ]);

    // ═══ Taxonomy: novel_status (وضعیت انتشار) ═══
    $status_labels = [
        'name'              => 'وضعیت انتشار',
        'singular_name'     => 'وضعیت',
        'search_items'      => 'جستجوی وضعیت',
        'all_items'         => 'همه وضعیت‌ها',
        'edit_item'         => 'ویرایش وضعیت',
        'update_item'       => 'به‌روزرسانی وضعیت',
        'add_new_item'      => 'افزودن وضعیت جدید',
        'new_item_name'     => 'نام وضعیت جدید',
        'menu_name'         => 'وضعیت انتشار',
        'not_found'         => 'وضعیتی یافت نشد.',
        'back_to_items'     => '← بازگشت به وضعیت‌ها',
    ];

    register_taxonomy('novel_status', ['novel'], [
        'labels'            => $status_labels,
        'hierarchical'      => true,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => false,
        'show_in_rest'      => true,
        'rewrite'           => [
            'slug'       => 'status',
            'with_front' => false,
        ],
        'query_var'         => true,
        'capabilities'      => [
            'assign_terms' => 'edit_posts',
            'edit_terms'   => 'manage_options',
            'manage_terms' => 'manage_options',
            'delete_terms' => 'manage_options',
        ],
    ]);
}
add_action('init', 'novel_register_taxonomies', 5);

/**
 * Seed default taxonomy terms on theme activation
 */
function novel_seed_default_terms() {
    // Check if already seeded
    if (get_option('novel_terms_seeded')) {
        return;
    }
    
    // ═══ Default Genres ═══
    $genres = [
        'action'    => ['name' => 'اکشن',     'description' => 'رمان‌های پر از صحنه‌های نبرد و هیجان'],
        'adventure' => ['name' => 'ماجراجویی', 'description' => 'سفرها و کاوش‌های هیجان‌انگیز'],
        'comedy'    => ['name' => 'کمدی',      'description' => 'رمان‌های طنز و شاد'],
        'drama'     => ['name' => 'درام',       'description' => 'داستان‌های احساسی و عمیق'],
        'fantasy'   => ['name' => 'فانتزی',    'description' => 'دنیاهای خیالی، جادو و موجودات افسانه‌ای'],
        'horror'    => ['name' => 'وحشت',       'description' => 'ترس، تعلیق و عناصر ترسناک'],
        'mystery'   => ['name' => 'رمز و راز', 'description' => 'معما، جنایت و کشف حقیقت'],
        'romance'   => ['name' => 'عاشقانه',   'description' => 'داستان‌های عشقی و رمانتیک'],
        'sci-fi'    => ['name' => 'علمی-تخیلی', 'description' => 'فناوری پیشرفته، فضا و آینده'],
        'slice-of-life' => ['name' => 'برش زندگی', 'description' => 'زندگی روزمره و واقع‌گرایانه'],
        'thriller'  => ['name' => 'هیجانی',    'description' => 'تعلیق و هیجان شدید'],
        'tragedy'   => ['name' => 'تراژدی',     'description' => 'پایان‌های تلخ و غم‌انگیز'],
        'martial-arts' => ['name' => 'هنرهای رزمی', 'description' => 'رزمندگان، تمرین و نبرد'],
        'supernatural' => ['name' => 'فراطبیعی', 'description' => 'قدرت‌های ماوراطبیعی و ارواح'],
        'psychological' => ['name' => 'روان‌شناختی', 'description' => 'بازی‌های ذهنی و روانی'],
        'mecha'     => ['name' => 'مکا',       'description' => 'ربات‌های غول‌پیکر و نبرد مکانیکی'],
        'sports'    => ['name' => 'ورزشی',     'description' => 'رقابت‌ها و ورزش‌ها'],
        'historical' => ['name' => 'تاریخی',   'description' => 'داستان‌های مبتنی بر تاریخ'],
        'xuanhuan'  => ['name' => 'شوان‌هوان',  'description' => 'فانتزی چینی با عناصر cultivation'],
        'xianxia'   => ['name' => 'شیان‌شیا',   'description' => 'تلاش برای جاودانگی و cultivation'],
        'wuxia'     => ['name' => 'ووشیا',     'description' => 'هنرهای رزمی چینی سنتی'],
        'josei'     => ['name' => 'جوسی',      'description' => 'داستان برای زنان بزرگسال'],
        'seinen'    => ['name' => 'سینن',      'description' => 'داستان برای مردان بزرگسال'],
        'shoujo'    => ['name' => 'شوجو',      'description' => 'داستان برای دختران نوجوان'],
        'shounen'   => ['name' => 'شونن',      'description' => 'داستان برای پسران نوجوان'],
        'harem'     => ['name' => 'حرمسرا',    'description' => 'یک قهرمان با چند علاقه‌مند'],
        'ecchi'     => ['name' => 'اچی',       'description' => 'محتوای خفیف بزرگسال'],
        'mature'    => ['name' => 'بزرگسال',   'description' => 'محتوای مناسب بزرگسالان'],
        'school-life' => ['name' => 'زندگی مدرسه‌ای', 'description' => 'ماجراهای مدرسه و دانشگاه'],
        'game'      => ['name' => 'بازی',       'description' => 'دنیای بازی و گیمینگ'],
    ];

    foreach ($genres as $slug => $data) {
        if (!term_exists($slug, 'genre')) {
            wp_insert_term($data['name'], 'genre', [
                'slug'        => $slug,
                'description' => $data['description'],
            ]);
        }
    }

    // ═══ Default Tags ═══
    $tags = [
        'op-mc'            => 'OP MC',
        'harem'            => 'Harem',
        'reincarnation'    => 'Reincarnation',
        'system'           => 'System',
        'isekai'           => 'Isekai',
        'cultivation'      => 'Cultivation',
        'dungeon'          => 'Dungeon',
        'tower'            => 'Tower',
        'academy'          => 'Academy',
        'revenge'          => 'Revenge',
        'weak-to-strong'   => 'Weak to Strong',
        'time-travel'      => 'Time Travel',
        'virtual-reality'  => 'Virtual Reality',
        'kingdom-building' => 'Kingdom Building',
        'monster-tamer'    => 'Monster Tamer',
        'anti-hero'        => 'Anti-Hero',
        'slice-of-life'    => 'Slice of Life',
        'martial-arts'     => 'Martial Arts',
        'romance'          => 'Romance',
        'regression'       => 'Regression',
        'overpowered'      => 'Overpowered',
        'leveling'         => 'Leveling',
        'magic'            => 'Magic',
        'demons'           => 'Demons',
        'dragons'          => 'Dragons',
        'necromancer'      => 'Necromancer',
        'summoner'         => 'Summoner',
        'assassin'         => 'Assassin',
        'healer'           => 'Healer',
        'sword'            => 'Sword',
        // Persian tags
        'asheghaneh'       => 'عاشقانه',
        'bazgasht'         => 'بازگشت به گذشته',
        'tanasokh'         => 'تناسخ',
        'systemi'          => 'سیستمی',
        'ghahreman-ghavi'  => 'قهرمان قوی',
        'haramsara'        => 'حرمسرا',
        'academy-fa'       => 'آکادمی',
        'entegham'         => 'انتقام',
        'zaif-be-ghavi'    => 'ضعیف به قوی',
        'majarajuyi'       => 'ماجراجویی',
    ];

    foreach ($tags as $slug => $name) {
        if (!term_exists($slug, 'novel_tag')) {
            wp_insert_term($name, 'novel_tag', [
                'slug' => $slug,
            ]);
        }
    }

    // ═══ Default Novel Statuses ═══
    $statuses = [
        'ongoing'   => ['name' => 'در حال انتشار', 'description' => 'رمان به‌طور فعال در حال به‌روزرسانی است'],
        'completed' => ['name' => 'تکمیل شده',     'description' => 'ترجمه/نوشتن رمان تمام شده است'],
        'hiatus'    => ['name' => 'متوقف شده',     'description' => 'انتشار موقتاً متوقف شده'],
        'dropped'   => ['name' => 'رها شده',        'description' => 'ترجمه/نوشتن رمان رها شده است'],
    ];

    foreach ($statuses as $slug => $data) {
        if (!term_exists($slug, 'novel_status')) {
            wp_insert_term($data['name'], 'novel_status', [
                'slug'        => $slug,
                'description' => $data['description'],
            ]);
        }
    }

    update_option('novel_terms_seeded', true);
}
add_action('after_switch_theme', 'novel_seed_default_terms');

/**
 * Also seed on init if not done yet (for first install)
 */
function novel_maybe_seed_terms() {
    if (!get_option('novel_terms_seeded')) {
        novel_seed_default_terms();
    }
}
add_action('init', 'novel_maybe_seed_terms', 99);

/**
 * Add custom fields to genre taxonomy (icon, color)
 */
function novel_genre_add_form_fields() {
    ?>
    <div class="form-field">
        <label for="genre_icon"><?php _e('آیکون', 'suspended-starter'); ?></label>
        <input type="text" name="genre_icon" id="genre_icon" value="" placeholder="مثال: ⚔️ یا dashicons-shield" />
        <p class="description">آیکون اموجی یا کلاس dashicon برای این ژانر</p>
    </div>
    <div class="form-field">
        <label for="genre_color"><?php _e('رنگ', 'suspended-starter'); ?></label>
        <input type="color" name="genre_color" id="genre_color" value="#6366f1" />
        <p class="description">رنگ بج ژانر در فرانت‌اند</p>
    </div>
    <?php
}
add_action('genre_add_form_fields', 'novel_genre_add_form_fields');

/**
 * Edit form fields for genre
 */
function novel_genre_edit_form_fields($term) {
    $icon  = get_term_meta($term->term_id, 'genre_icon', true);
    $color = get_term_meta($term->term_id, 'genre_color', true) ?: '#6366f1';
    ?>
    <tr class="form-field">
        <th scope="row"><label for="genre_icon"><?php _e('آیکون', 'suspended-starter'); ?></label></th>
        <td>
            <input type="text" name="genre_icon" id="genre_icon" value="<?php echo esc_attr($icon); ?>" placeholder="مثال: ⚔️" />
            <p class="description">آیکون اموجی یا کلاس dashicon</p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="genre_color"><?php _e('رنگ', 'suspended-starter'); ?></label></th>
        <td>
            <input type="color" name="genre_color" id="genre_color" value="<?php echo esc_attr($color); ?>" />
            <p class="description">رنگ بج ژانر</p>
        </td>
    </tr>
    <?php
}
add_action('genre_edit_form_fields', 'novel_genre_edit_form_fields');

/**
 * Save genre custom fields
 */
function novel_save_genre_fields($term_id) {
    if (isset($_POST['genre_icon'])) {
        update_term_meta($term_id, 'genre_icon', sanitize_text_field($_POST['genre_icon']));
    }
    if (isset($_POST['genre_color'])) {
        update_term_meta($term_id, 'genre_color', sanitize_hex_color($_POST['genre_color']));
    }
}
add_action('created_genre', 'novel_save_genre_fields');
add_action('edited_genre', 'novel_save_genre_fields');

/**
 * Get genre badge HTML
 */
function novel_get_genre_badge($term, $linked = true) {
    if (is_int($term)) {
        $term = get_term($term, 'genre');
    }
    
    if (!$term || is_wp_error($term)) {
        return '';
    }
    
    $icon  = get_term_meta($term->term_id, 'genre_icon', true);
    $color = get_term_meta($term->term_id, 'genre_color', true) ?: '#6366f1';
    
    $badge_html = '<span class="genre-badge" style="--genre-color: ' . esc_attr($color) . ';">';
    if ($icon) {
        $badge_html .= '<span class="genre-icon">' . esc_html($icon) . '</span> ';
    }
    $badge_html .= esc_html($term->name);
    $badge_html .= '</span>';
    
    if ($linked) {
        $link = get_term_link($term);
        if (!is_wp_error($link)) {
            $badge_html = '<a href="' . esc_url($link) . '" class="genre-badge-link">' . $badge_html . '</a>';
        }
    }
    
    return $badge_html;
}

/**
 * Get novel status badge HTML
 */
function novel_get_status_badge($novel_id) {
    $statuses = wp_get_post_terms($novel_id, 'novel_status');
    
    if (empty($statuses) || is_wp_error($statuses)) {
        return '<span class="status-badge status-unknown">نامشخص</span>';
    }
    
    $status = $statuses[0];
    $slug   = $status->slug;
    
    $icons = [
        'ongoing'   => '🟢',
        'completed' => '✅',
        'hiatus'    => '⏸️',
        'dropped'   => '❌',
    ];
    
    $icon = isset($icons[$slug]) ? $icons[$slug] : '⚪';
    
    return '<span class="status-badge status-' . esc_attr($slug) . '">' 
           . $icon . ' ' . esc_html($status->name) 
           . '</span>';
}

/**
 * Display genre column with color in admin
 */
function novel_genre_admin_columns($columns) {
    $new = [];
    foreach ($columns as $key => $val) {
        $new[$key] = $val;
        if ($key === 'name') {
            $new['genre_color'] = 'رنگ';
            $new['genre_icon']  = 'آیکون';
        }
    }
    return $new;
}
add_filter('manage_edit-genre_columns', 'novel_genre_admin_columns');

/**
 * Populate genre admin columns
 */
function novel_genre_admin_column_content($content, $column_name, $term_id) {
    switch ($column_name) {
        case 'genre_color':
            $color = get_term_meta($term_id, 'genre_color', true) ?: '#6366f1';
            return '<span style="display:inline-block;width:24px;height:24px;border-radius:4px;background:' 
                   . esc_attr($color) . ';"></span>';
            
        case 'genre_icon':
            $icon = get_term_meta($term_id, 'genre_icon', true);
            return $icon ? '<span style="font-size:20px;">' . esc_html($icon) . '</span>' : '—';
    }
    return $content;
}
add_filter('manage_genre_custom_column', 'novel_genre_admin_column_content', 10, 3);