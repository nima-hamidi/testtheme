<?php
/**
 * Top Authors Section (Homepage)
 *
 * @package suspended-starter
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

$top_authors = Novel_Authors::get_top_authors(8);
if (empty($top_authors)) return;
?>

<section class="top-authors-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">✍️ نویسندگان برتر</h2>
            <a href="<?php echo home_url('/authors/'); ?>" class="section-link">همه نویسندگان →</a>
        </div>

        <div class="top-authors-grid">
            <?php foreach ($top_authors as $user) :
                $uid   = $user->ID;
                $stats = Novel_Authors::get_author_stats($uid);
                $is_following = is_user_logged_in() ? Novel_Follow::is_following_user(get_current_user_id(), $uid) : false;
            ?>
            <div class="top-author-card">
                <a href="<?php echo get_author_posts_url($uid); ?>" class="top-author-card__avatar">
                    <?php echo get_avatar($uid, 64, '', '', ['class' => 'top-author-img']); ?>
                    <?php echo Novel_Authors::render_online_dot($uid); ?>
                </a>
                <h4 class="top-author-card__name">
                    <a href="<?php echo get_author_posts_url($uid); ?>"><?php echo esc_html($user->display_name); ?></a>
                </h4>
                <div class="top-author-card__stats">
                    ★ <?php echo number_format($stats['avg_rating'], 1); ?>
                    | 📖 <?php echo $stats['novels']; ?> رمان
                </div>
                <div class="top-author-card__followers">
                    ❤ <?php echo number_format_i18n($stats['followers']); ?> فالوور
                </div>
                <?php if (is_user_logged_in() && get_current_user_id() !== $uid) : ?>
                    <button class="btn-follow-user btn-follow-sm <?php echo $is_following ? 'following' : ''; ?>"
                            data-user-id="<?php echo $uid; ?>">
                        <?php echo $is_following ? '✓' : '+ دنبال'; ?>
                    </button>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>