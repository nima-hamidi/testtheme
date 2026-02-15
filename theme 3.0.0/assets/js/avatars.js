/**
 * Avatar Picker JavaScript
 *
 * @package suspended-starter
 * @since 3.0.0
 */

(function ($) {
    'use strict';

    const AvatarPicker = {
        selectedId: null,
        currentId: null,

        init() {
            const $picker = $('#avatarPicker');
            if (!$picker.length) return;

            // Determine current avatar
            const $current = $picker.find('.avatar-picker__item.is-current');
            if ($current.length) {
                this.currentId = parseInt($current.data('avatar-id'), 10);
                this.selectedId = this.currentId;
            }

            this.bindEvents();
        },

        bindEvents() {
            const self = this;

            // Click on avatar item
            $(document).on('click', '.avatar-picker__item', function () {
                const $item = $(this);
                const avatarId = parseInt($item.data('avatar-id'), 10);
                const avatarUrl = $item.data('avatar-url');

                // Deselect all
                $('.avatar-picker__item').removeClass('is-active');

                // Select this
                $item.addClass('is-active');
                self.selectedId = avatarId;

                // Update preview
                $('#avatarPreviewImg').attr('src', avatarUrl);

                // Enable/disable save button
                const changed = self.selectedId !== self.currentId;
                $('#saveAvatarBtn').prop('disabled', !changed);
            });

            // Save avatar
            $(document).on('click', '#saveAvatarBtn', function () {
                self.saveAvatar();
            });

            // Keyboard navigation
            $(document).on('keydown', '.avatar-picker__item', function (e) {
                const $items = $('.avatar-picker__item');
                const index = $items.index(this);
                let newIndex = -1;

                const cols = self.getGridColumns();

                switch (e.key) {
                    case 'ArrowRight':
                        newIndex = index > 0 ? index - 1 : $items.length - 1; // RTL
                        break;
                    case 'ArrowLeft':
                        newIndex = index < $items.length - 1 ? index + 1 : 0; // RTL
                        break;
                    case 'ArrowDown':
                        newIndex = Math.min(index + cols, $items.length - 1);
                        break;
                    case 'ArrowUp':
                        newIndex = Math.max(index - cols, 0);
                        break;
                    case 'Enter':
                    case ' ':
                        e.preventDefault();
                        $(this).trigger('click');
                        return;
                    default:
                        return;
                }

                e.preventDefault();
                $items.eq(newIndex).focus().trigger('click');
            });
        },

        getGridColumns() {
            const width = window.innerWidth;
            if (width <= 480) return 4;
            if (width <= 768) return 5;
            return 6;
        },

        saveAvatar() {
            if (!this.selectedId || this.selectedId === this.currentId) return;

            const $btn = $('#saveAvatarBtn');
            $btn.addClass('is-loading').prop('disabled', true);

            $.ajax({
                url: novelAuth.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'novel_save_avatar',
                    nonce: novelAuth.nonce,
                    avatar_id: this.selectedId,
                },
                dataType: 'json',
            })
            .done((res) => {
                if (res.success) {
                    // Update current state
                    this.currentId = this.selectedId;

                    // Update "is-current" class
                    $('.avatar-picker__item').removeClass('is-current');
                    $('.avatar-picker__item .avatar-picker__current-label').remove();
                    $(`.avatar-picker__item[data-avatar-id="${this.currentId}"]`)
                        .addClass('is-current');

                    // Update all avatars on the page
                    $('.novel-avatar-dynamic').attr('src', res.data.avatar_url);

                    // Update header avatar if exists
                    $('.header-user-avatar').attr('src', res.data.avatar_url);

                    // Show toast
                    this.showToast(res.data.message, 'success');

                    $btn.prop('disabled', true);
                } else {
                    this.showToast(res.data.message || 'خطایی رخ داد.', 'error');
                }
            })
            .fail(() => {
                this.showToast('خطا در ارتباط با سرور.', 'error');
            })
            .always(() => {
                $btn.removeClass('is-loading');
            });
        },

        showToast(message, type = 'success') {
            // Use global toast if available
            if (typeof window.NovelToast !== 'undefined') {
                window.NovelToast.show(message, type);
                return;
            }

            // Fallback toast
            const $toast = $(`
                <div class="novel-toast novel-toast--${type}" style="
                    position: fixed;
                    bottom: 24px;
                    left: 50%;
                    transform: translateX(-50%) translateY(20px);
                    background: ${type === 'success' ? '#10b981' : '#ef4444'};
                    color: #fff;
                    padding: 12px 24px;
                    border-radius: 12px;
                    font-size: 14px;
                    font-weight: 600;
                    z-index: 99999;
                    opacity: 0;
                    transition: all 0.3s ease;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
                    direction: rtl;
                ">${message}</div>
            `);

            $('body').append($toast);

            requestAnimationFrame(() => {
                $toast.css({ opacity: 1, transform: 'translateX(-50%) translateY(0)' });
            });

            setTimeout(() => {
                $toast.css({ opacity: 0, transform: 'translateX(-50%) translateY(20px)' });
                setTimeout(() => $toast.remove(), 300);
            }, 3000);
        },
    };

    $(document).ready(() => AvatarPicker.init());

})(jQuery);