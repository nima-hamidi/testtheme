<?php
/**
 * Template part for displaying chapter list organized by volumes (جلدها)
 * Used in single-novel.php for light novels
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$novel_id = get_the_ID();
$volumes = fn_get_volumes( $novel_id );

if ( empty( $volumes ) ) {
    return;
}
?>

<div class="fn-chapter-list fn-chapter-list--volumes">
    <div class="fn-chapter-list__header">
        <h3><?php esc_html_e( 'Volumes', 'flavor-novel' ); ?></h3>
        <span class="fn-volume-count"><?php echo count( $volumes ); ?> <?php esc_html_e( 'Volume(s)', 'flavor-novel' ); ?></span>
    </div>

    <div class="fn-volumes-list">
        <?php foreach ( $volumes as $volume ) : ?>
            <?php
            $volume_number   = $volume['number'];
            $volume_title    = ! empty( $volume['title'] ) ? $volume['title'] : sprintf( esc_html__( 'Volume %d', 'flavor-novel' ), $volume_number );
            $chapters_count  = count( $volume['chapters'] );
            $volume_id       = 'volume-' . $volume_number;
            ?>
            
            <div class="fn-volume" data-volume="<?php echo esc_attr( $volume_number ); ?>">
                <div class="fn-volume__header" role="button" tabindex="0" data-toggle="<?php echo esc_attr( $volume_id ); ?>">
                    <div class="fn-volume__info">
                        <h4 class="fn-volume__title">
                            <span class="fn-volume__icon">📕</span>
                            <?php echo esc_html( $volume_title ); ?>
                        </h4>
                        <span class="fn-volume__count">
                            <?php echo esc_html( $chapters_count ); ?> 
                            <?php echo $chapters_count === 1 ? esc_html__( 'Chapter', 'flavor-novel' ) : esc_html__( 'Chapters', 'flavor-novel' ); ?>
                        </span>
                    </div>
                    <span class="fn-volume__toggle">
                        <svg class="fn-icon" viewBox="0 0 24 24" width="20" height="20">
                            <path d="M10 6L5 11l5 5 1.41-1.41L7.83 12l3.58-3.59L10 6zm4 0l-1.41 1.41L16.17 12l-3.58 3.59L14 17.41l5-5-5-5z" fill="currentColor"/>
                        </svg>
                    </span>
                </div>

                <div class="fn-volume__content" id="<?php echo esc_attr( $volume_id ); ?>">
                    <ul class="fn-chapter-list__items">
                        <?php foreach ( $volume['chapters'] as $chapter ) : ?>
                            <?php
                            $chapter_id = $chapter->ID;
                            $chapter_number = floatval( get_post_meta( $chapter_id, '_fn_chapter_number', true ) );
                            $is_vip = get_post_meta( $chapter_id, '_fn_is_vip', true );
                            $view_count = intval( get_post_meta( $chapter_id, '_fn_total_views', true ) );
                            $chapter_url = get_permalink( $chapter_id );
                            ?>
                            
                            <li class="fn-chapter-item <?php echo $is_vip ? 'fn-chapter-item--vip' : ''; ?>">
                                <div class="fn-chapter-item__left">
                                    <span class="fn-chapter-item__number">
                                        <?php echo fn_persian_number( $chapter_number ); ?>
                                    </span>
                                    <div class="fn-chapter-item__info">
                                        <a href="<?php echo esc_url( $chapter_url ); ?>" class="fn-chapter-item__title">
                                            <?php echo esc_html( $chapter->post_title ); ?>
                                        </a>
                                        <?php if ( $is_vip ) : ?>
                                            <span class="fn-chapter-badge fn-chapter-badge--vip" title="<?php esc_attr_e( 'VIP Chapter', 'flavor-novel' ); ?>">
                                                <svg class="fn-icon" viewBox="0 0 24 24" width="14" height="14">
                                                    <path d="M12 2c5.52 0 10 4.48 10 10s-4.48 10-10 10S2 17.52 2 12 6.48 2 12 2zm-2 15h4v2h-4v-2zm2-14C6.48 3 3 6.48 3 10.5S6.48 18 12 18s9-3.48 9-7.5S17.52 3 12 3zm0 13a3 3 0 100-6 3 3 0 000 6z" fill="currentColor"/>
                                                </svg>
                                                VIP
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="fn-chapter-item__right">
                                    <span class="fn-chapter-item__views" title="<?php esc_attr_e( 'Total Views', 'flavor-novel' ); ?>">
                                        <svg class="fn-icon" viewBox="0 0 24 24" width="16" height="16">
                                            <path d="M12 4C7 4 2.73 7.11 1 11.5C2.73 15.89 7 19 12 19s9.27-3.11 11-7.5C21.27 7.11 17 4 12 4zm0 12.5c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z" fill="currentColor"/>
                                        </svg>
                                        <?php echo fn_format_number( $view_count ); ?>
                                    </span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.fn-chapter-list--volumes {
    padding: 0;
}

.fn-volume {
    border-bottom: 1px solid var(--fn-border);
}

.fn-volume:last-child {
    border-bottom: none;
}

.fn-volume__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px;
    cursor: pointer;
    transition: var(--fn-transition);
    user-select: none;
    background: var(--fn-bg-secondary);
}

.fn-volume__header:hover {
    background: var(--fn-accent-light);
}

.fn-volume__info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

.fn-volume__title {
    font-size: 1.02rem;
    font-weight: 700;
    color: var(--fn-text-primary);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.fn-volume__icon {
    font-size: 1.3rem;
}

.fn-volume__count {
    font-size: 0.8rem;
    color: var(--fn-text-muted);
    background: var(--fn-bg-primary);
    padding: 3px 10px;
    border-radius: 12px;
    white-space: nowrap;
}

.fn-volume__toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    color: var(--fn-text-secondary);
    transition: var(--fn-transition);
    flex-shrink: 0;
}

.fn-volume__header:hover .fn-volume__toggle {
    background: rgba(0,0,0,0.05);
    color: var(--fn-accent);
}

.fn-volume__content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.fn-volume__content.open {
    max-height: 2000px;
}

.fn-chapter-list__items {
    list-style: none;
    padding: 0;
    margin: 0;
    background: var(--fn-bg-primary);
}

.fn-chapter-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 18px;
    border-bottom: 1px solid var(--fn-border);
    transition: var(--fn-transition);
}

.fn-chapter-item:hover {
    background: var(--fn-bg-secondary);
}

.fn-chapter-item__left {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
    min-width: 0;
}

.fn-chapter-item__number {
    min-width: 45px;
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--fn-accent);
    background: var(--fn-accent-light);
    padding: 4px 8px;
    border-radius: 4px;
    text-align: center;
}

.fn-chapter-item__info {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.fn-chapter-item__title {
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--fn-text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    transition: var(--fn-transition);
}

.fn-chapter-item:hover .fn-chapter-item__title {
    color: var(--fn-accent);
}

.fn-chapter-badge {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 2px 6px;
    background: var(--fn-danger);
    color: #fff;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 700;
    white-space: nowrap;
    flex-shrink: 0;
}

.fn-chapter-badge--vip {
    background: linear-gradient(135deg, #FFD700, #FFA500);
    box-shadow: 0 2px 4px rgba(255,165,0,0.3);
}

.fn-chapter-item__right {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}

.fn-chapter-item__views {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.78rem;
    color: var(--fn-text-muted);
}

.fn-volume-count {
    font-size: 0.82rem;
    color: var(--fn-text-muted);
    background: var(--fn-bg-primary);
    padding: 4px 12px;
    border-radius: 12px;
}

/* Responsive */
@media (max-width: 768px) {
    .fn-volume__header {
        padding: 16px 14px;
    }

    .fn-volume__title {
        font-size: 0.98rem;
    }

    .fn-chapter-item {
        padding: 10px 14px;
        gap: 8px;
    }

    .fn-chapter-item__title {
        font-size: 0.88rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const volumeHeaders = document.querySelectorAll('.fn-volume__header');
    
    volumeHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const contentId = this.getAttribute('data-toggle');
            const content = document.getElementById(contentId);
            
            if (content) {
                content.classList.toggle('open');
                this.classList.toggle('active');
            }
        });

        header.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });
});
</script>
