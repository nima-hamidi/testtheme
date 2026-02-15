<?php
/**
 * Social Share Buttons Component
 * 
 * فایل: templates/components/share-buttons.php
 * استفاده: get_template_part('templates/components/share-buttons')
 *
 * @package suspended-flavor
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;

$share_url   = urlencode(get_permalink());
$share_title = urlencode(get_the_title());
$share_text  = urlencode(get_the_title() . ' | ' . get_bloginfo('name'));
?>

<div class="share-buttons">
    <span class="share-buttons__label">اشتراک‌گذاری:</span>

    <!-- Telegram -->
    <a href="https://t.me/share/url?url=<?php echo $share_url; ?>&text=<?php echo $share_text; ?>" 
       target="_blank" rel="noopener noreferrer" class="btn-share btn-share--telegram" title="تلگرام">
        <svg viewBox="0 0 24 24" fill="currentColor">
            <path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.46-.52-.19L7.74 13.3 3.64 12c-.88-.25-.89-.86.2-1.3l15.97-6.16c.73-.33 1.43.18 1.15 1.3l-2.72 12.81c-.19.91-.74 1.13-1.5.71L12.6 16.3l-1.99 1.93c-.23.23-.42.42-.83.42z"/>
        </svg>
    </a>

    <!-- WhatsApp -->
    <a href="https://wa.me/?text=<?php echo $share_text . '%20' . $share_url; ?>" 
       target="_blank" rel="noopener noreferrer" class="btn-share btn-share--whatsapp" title="واتس‌اپ">
        <svg viewBox="0 0 24 24" fill="currentColor">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
        </svg>
    </a>

    <!-- Twitter/X -->
    <a href="https://twitter.com/intent/tweet?url=<?php echo $share_url; ?>&text=<?php echo $share_title; ?>" 
       target="_blank" rel="noopener noreferrer" class="btn-share btn-share--twitter" title="توییتر">
        <svg viewBox="0 0 24 24" fill="currentColor">
            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
        </svg>
    </a>

    <!-- Copy Link -->
    <button class="btn-share btn-share--copy" title="کپی لینک" 
            onclick="novelCopyLink(this, '<?php echo esc_js(get_permalink()); ?>')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" 
             stroke-linecap="round" stroke-linejoin="round" class="copy-icon">
            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
            <path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"></path>
        </svg>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" 
             stroke-linecap="round" stroke-linejoin="round" class="check-icon" style="display:none">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
    </button>
</div>

<!-- Mobile Web Share API -->
<button class="btn-share-mobile" id="mobileShareBtn" style="display:none"
        data-url="<?php echo esc_attr(get_permalink()); ?>"
        data-title="<?php echo esc_attr(get_the_title()); ?>">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="18" cy="5" r="3"></circle>
        <circle cx="6" cy="12" r="3"></circle>
        <circle cx="18" cy="19" r="3"></circle>
        <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
        <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
    </svg>
    اشتراک‌گذاری
</button>

<script>
function novelCopyLink(btn, url) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(function() {
            btn.classList.add('copied');
            btn.querySelector('.copy-icon').style.display = 'none';
            btn.querySelector('.check-icon').style.display = 'block';
            if (typeof NovelToast !== 'undefined') NovelToast.show('لینک کپی شد ✓', 'success');
            setTimeout(function() {
                btn.classList.remove('copied');
                btn.querySelector('.copy-icon').style.display = 'block';
                btn.querySelector('.check-icon').style.display = 'none';
            }, 2000);
        });
    }
}

// Mobile Web Share API
(function() {
    if (navigator.share && window.innerWidth <= 768) {
        var mBtn = document.getElementById('mobileShareBtn');
        if (mBtn) {
            document.querySelector('.share-buttons')?.style.setProperty('display', 'none');
            mBtn.style.display = 'inline-flex';
            mBtn.addEventListener('click', function() {
                navigator.share({
                    title: this.dataset.title,
                    url: this.dataset.url
                });
            });
        }
    }
})();
</script>