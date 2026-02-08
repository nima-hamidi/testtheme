<?php
/**
 * متاباکس‌های سفارشی
 * فیلدهای رمان و فصل
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* =============================================
 *  متاباکس رمان (Novel)
 * ============================================= */
function fn_add_novel_meta_boxes() {
    add_meta_box(
        'fn_novel_details',
        '📖 جزئیات رمان',
        'fn_render_novel_meta_box',
        'novel',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'fn_add_novel_meta_boxes' );

/**
 * رندر متاباکس رمان
 */
function fn_render_novel_meta_box( $post ) {
    wp_nonce_field( 'fn_novel_meta', 'fn_novel_meta_nonce' );

    $fields = array(
        'synopsis'        => get_post_meta( $post->ID, '_fn_synopsis', true ),
        'original_author' => get_post_meta( $post->ID, '_fn_original_author', true ),
        'translator'      => get_post_meta( $post->ID, '_fn_translator', true ),
        'original_lang'   => get_post_meta( $post->ID, '_fn_original_language', true ),
        'publish_year'    => get_post_meta( $post->ID, '_fn_publish_year', true ),
        'alt_names'       => get_post_meta( $post->ID, '_fn_alternative_names', true ),
        'is_featured'     => get_post_meta( $post->ID, '_fn_is_featured', true ),
    );

    $languages = array(
        'chinese'  => 'چینی',
        'korean'   => 'کره‌ای',
        'japanese' => 'ژاپنی',
        'english'  => 'انگلیسی',
        'persian'  => 'فارسی',
        'other'    => 'سایر',
    );
    ?>
    <style>
        .fn-meta-row { margin-bottom: 16px; }
        .fn-meta-row label { display: block; font-weight: 700; margin-bottom: 6px; font-size: 13px; }
        .fn-meta-row input[type="text"],
        .fn-meta-row input[type="number"],
        .fn-meta-row select,
        .fn-meta-row textarea {
            width: 100%; padding: 8px 12px;
            border: 1px solid #ddd; border-radius: 6px; font-size: 14px;
        }
        .fn-meta-row textarea { min-height: 150px; resize: vertical; }
        .fn-meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .fn-meta-checkbox { display: flex; align-items: center; gap: 8px; }
    </style>

    <div class="fn-meta-grid">
        <div class="fn-meta-row">
            <label for="fn_original_author">✍️ نویسنده اصلی</label>
            <input type="text" id="fn_original_author" name="fn_original_author"
                   value="<?php echo esc_attr( $fields['original_author'] ); ?>"
                   placeholder="نام نویسنده اصلی اثر">
        </div>

        <div class="fn-meta-row">
            <label for="fn_translator">🌐 مترجم</label>
            <input type="text" id="fn_translator" name="fn_translator"
                   value="<?php echo esc_attr( $fields['translator'] ); ?>"
                   placeholder="نام مترجم فارسی">
        </div>

        <div class="fn-meta-row">
            <label for="fn_original_language">🗣️ زبان اصلی</label>
            <select id="fn_original_language" name="fn_original_language">
                <option value="">انتخاب کنید</option>
                <?php foreach ( $languages as $value => $label ) : ?>
                    <option value="<?php echo esc_attr( $value ); ?>"
                            <?php selected( $fields['original_lang'], $value ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="fn-meta-row">
            <label for="fn_publish_year">📅 سال انتشار</label>
            <input type="number" id="fn_publish_year" name="fn_publish_year"
                   value="<?php echo esc_attr( $fields['publish_year'] ); ?>"
                   min="1900" max="2030" placeholder="مثلاً 2024">
        </div>
    </div>

    <div class="fn-meta-row">
        <label for="fn_alternative_names">📝 نام‌های جایگزین</label>
        <input type="text" id="fn_alternative_names" name="fn_alternative_names"
               value="<?php echo esc_attr( $fields['alt_names'] ); ?>"
               placeholder="نام‌های دیگر اثر (با ویرگول جدا کنید)">
    </div>

    <div class="fn-meta-row">
        <label for="fn_synopsis">📋 خلاصه داستان</label>
        <textarea id="fn_synopsis" name="fn_synopsis"
                  placeholder="خلاصه‌ای جذاب از داستان رمان بنویسید..."><?php echo esc_textarea( $fields['synopsis'] ); ?></textarea>
    </div>

    <div class="fn-meta-row">
        <label class="fn-meta-checkbox">
            <input type="checkbox" name="fn_is_featured" value="1"
                   <?php checked( $fields['is_featured'], '1' ); ?>>
            ⭐ نمایش در اسلایدر صفحه اصلی (رمان ویژه)
        </label>
    </div>
    <?php
}

/**
 * ذخیره متای رمان
 */
function fn_save_novel_meta( $post_id ) {
    if ( ! isset( $_POST['fn_novel_meta_nonce'] ) ||
         ! wp_verify_nonce( $_POST['fn_novel_meta_nonce'], 'fn_novel_meta' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    // فیلدهای متنی
    $text_fields = array(
        'fn_original_author'   => '_fn_original_author',
        'fn_translator'        => '_fn_translator',
        'fn_original_language' => '_fn_original_language',
        'fn_publish_year'      => '_fn_publish_year',
        'fn_alternative_names' => '_fn_alternative_names',
    );

    foreach ( $text_fields as $form_key => $meta_key ) {
        if ( isset( $_POST[ $form_key ] ) ) {
            update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[ $form_key ] ) );
        }
    }

    // خلاصه (HTML مجاز)
    if ( isset( $_POST['fn_synopsis'] ) ) {
        update_post_meta( $post_id, '_fn_synopsis', wp_kses_post( $_POST['fn_synopsis'] ) );
    }

    // چک‌باکس ویژه
    update_post_meta( $post_id, '_fn_is_featured', isset( $_POST['fn_is_featured'] ) ? '1' : '0' );
}
add_action( 'save_post_novel', 'fn_save_novel_meta' );


/* =============================================
 *  متاباکس فصل (Chapter)
 * ============================================= */
function fn_add_chapter_meta_boxes() {
    add_meta_box(
        'fn_chapter_details',
        '📑 جزئیات فصل',
        'fn_render_chapter_meta_box',
        'chapter',
        'side',
        'high'
    );
}
add_action( 'add_meta_boxes', 'fn_add_chapter_meta_boxes' );

/**
 * رندر متاباکس فصل
 */
function fn_render_chapter_meta_box( $post ) {
    wp_nonce_field( 'fn_chapter_meta', 'fn_chapter_meta_nonce' );

    $parent_novel   = get_post_meta( $post->ID, '_fn_parent_novel', true );
    $chapter_number = get_post_meta( $post->ID, '_fn_chapter_number', true );

    $novels = get_posts( array(
        'post_type'      => 'novel',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post_status'    => array( 'publish', 'draft' ),
    ) );
    ?>
    <style>
        .fn-side-row { margin-bottom: 14px; }
        .fn-side-row label { display: block; font-weight: 700; margin-bottom: 4px; font-size: 12px; }
        .fn-side-row select, .fn-side-row input { width: 100%; padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px; }
    </style>

    <div class="fn-side-row">
        <label for="fn_parent_novel">📖 رمان مربوطه</label>
        <select id="fn_parent_novel" name="fn_parent_novel" required>
            <option value="">— انتخاب رمان —</option>
            <?php foreach ( $novels as $novel ) : ?>
                <option value="<?php echo esc_attr( $novel->ID ); ?>"
                        <?php selected( $parent_novel, $novel->ID ); ?>>
                    <?php echo esc_html( $novel->post_title ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="fn-side-row">
        <label for="fn_chapter_number">🔢 شماره فصل</label>
        <input type="number" id="fn_chapter_number" name="fn_chapter_number"
               value="<?php echo esc_attr( $chapter_number ); ?>"
               min="0" step="0.5" placeholder="مثلاً 1 یا 1.5" required>
    </div>

    <?php if ( $parent_novel ) : ?>
        <p style="margin-top:10px;font-size:12px;color:#666;">
            📊 فصل‌های فعلی: <strong><?php echo fn_get_chapter_count( $parent_novel ); ?></strong>
        </p>
    <?php endif; ?>
    <?php
}

/**
 * ذخیره متای فصل
 */
function fn_save_chapter_meta( $post_id ) {
    if ( ! isset( $_POST['fn_chapter_meta_nonce'] ) ||
         ! wp_verify_nonce( $_POST['fn_chapter_meta_nonce'], 'fn_chapter_meta' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    // رمان والد
    if ( isset( $_POST['fn_parent_novel'] ) ) {
        $novel_id = absint( $_POST['fn_parent_novel'] );
        update_post_meta( $post_id, '_fn_parent_novel', $novel_id );
        if ( $novel_id ) {
            fn_update_chapter_count( $novel_id );
        }
    }

    // شماره فصل
    if ( isset( $_POST['fn_chapter_number'] ) ) {
        update_post_meta( $post_id, '_fn_chapter_number', floatval( $_POST['fn_chapter_number'] ) );
    }
}
add_action( 'save_post_chapter', 'fn_save_chapter_meta' );