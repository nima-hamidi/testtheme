/**
 * Stickers System JavaScript
 *
 * Lightweight helper for sticker management in admin.
 * The main picker logic is in comments.js
 *
 * @package suspended-starter
 * @since 3.0.0
 */

(function ($) {
    'use strict';

    const StickerAdmin = {
        init() {
            if (!$('.novel-sticker-admin').length) return;
            this.bindUpload();
            this.bindDelete();
            this.bindSort();
        },

        bindUpload() {
            $(document).on('click', '.sticker-upload-btn', function () {
                const category = $(this).data('category');
                const type = $(this).data('type'); // 'sticker' or 'gif'
                const $input = $('<input type="file" accept="' + (type === 'gif' ? '.gif' : '.svg') + '" multiple>');

                $input.on('change', function () {
                    const files = this.files;
                    if (!files.length) return;

                    const formData = new FormData();
                    formData.append('action', 'novel_upload_sticker');
                    formData.append('nonce', novelAdmin.nonce);
                    formData.append('category', category);
                    formData.append('type', type);

                    for (let i = 0; i < files.length; i++) {
                        formData.append('files[]', files[i]);
                    }

                    $.ajax({
                        url: novelAdmin.ajaxUrl,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success(res) {
                            if (res.success) {
                                location.reload();
                            }
                        },
                    });
                });

                $input.trigger('click');
            });
        },

        bindDelete() {
            $(document).on('click', '.sticker-delete-btn', function () {
                if (!confirm('حذف این آیتم؟')) return;

                const file = $(this).data('file');
                const type = $(this).data('type');
                const $item = $(this).closest('.sticker-admin-item');

                $.post(novelAdmin.ajaxUrl, {
                    action: 'novel_delete_sticker',
                    nonce: novelAdmin.nonce,
                    file: file,
                    type: type,
                }, function (res) {
                    if (res.success) {
                        $item.fadeOut(300, function () { $(this).remove(); });
                    }
                });
            });
        },

        bindSort() {
            if ($.fn.sortable) {
                $('.sticker-admin-grid').sortable({
                    items: '.sticker-admin-item',
                    cursor: 'move',
                    opacity: 0.7,
                    update() {
                        // Save order if needed
                    },
                });
            }
        },
    };

    $(document).ready(() => StickerAdmin.init());

})(jQuery);