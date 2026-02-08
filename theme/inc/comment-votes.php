<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function fn_ajax_vote_comment() {
    check_ajax_referer( 'flavor_novel_nonce', 'nonce' );

    $comment_id = absint( $_POST['comment_id'] ?? 0 );
    $type       = sanitize_text_field( $_POST['type'] ?? '' );

    if ( ! $comment_id || ! in_array( $type, array( 'like', 'dislike' ) ) ) {
        wp_send_json_error();
    }

    // ایجاد کلید یکتا بر اساس IP یا user_id
    $voter_key = is_user_logged_in() ? 'user_' . get_current_user_id() : 'ip_' . md5( $_SERVER['REMOTE_ADDR'] );
    $voted = get_comment_meta( $comment_id, '_fn_voted_' . $voter_key, true );

    if ( $voted === $type ) {
        // حذف رأی
        delete_comment_meta( $comment_id, '_fn_voted_' . $voter_key );
        $meta_key = $type === 'like' ? '_fn_likes' : '_fn_dislikes';
        $current = max( 0, absint( get_comment_meta( $comment_id, $meta_key, true ) ) - 1 );
        update_comment_meta( $comment_id, $meta_key, $current );
        wp_send_json_success( array( 'action' => 'removed', 'likes' => absint( get_comment_meta( $comment_id, '_fn_likes', true ) ), 'dislikes' => absint( get_comment_meta( $comment_id, '_fn_dislikes', true ) ) ) );
    }

    // حذف رأی قبلی اگر وجود دارد
    if ( $voted ) {
        $old_key = $voted === 'like' ? '_fn_likes' : '_fn_dislikes';
        $old_val = max( 0, absint( get_comment_meta( $comment_id, $old_key, true ) ) - 1 );
        update_comment_meta( $comment_id, $old_key, $old_val );
    }

    // ثبت رأی جدید
    update_comment_meta( $comment_id, '_fn_voted_' . $voter_key, $type );
    $meta_key = $type === 'like' ? '_fn_likes' : '_fn_dislikes';
    $new_val = absint( get_comment_meta( $comment_id, $meta_key, true ) ) + 1;
    update_comment_meta( $comment_id, $meta_key, $new_val );

    wp_send_json_success( array(
        'action'   => 'voted',
        'type'     => $type,
        'likes'    => absint( get_comment_meta( $comment_id, '_fn_likes', true ) ),
        'dislikes' => absint( get_comment_meta( $comment_id, '_fn_dislikes', true ) ),
    ) );
}
add_action( 'wp_ajax_fn_vote_comment', 'fn_ajax_vote_comment' );
add_action( 'wp_ajax_nopriv_fn_vote_comment', 'fn_ajax_vote_comment' );
