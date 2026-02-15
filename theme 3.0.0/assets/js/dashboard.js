/**
 * Dashboard JavaScript
 * Tab navigation, AJAX loading, profile edit, account delete
 *
 * @package suspended-starter
 * @since 3.0.0
 */

(function ($) {
    'use strict';

    // Check for novelAuth localization
    const ajaxUrl = (typeof novelAuth !== 'undefined') ? novelAuth.ajaxUrl : (typeof novelDash !== 'undefined' ? novelDash.ajaxUrl : '/wp-admin/admin-ajax.php');
    const nonce = (typeof novelAuth !== 'undefined') ? novelAuth.nonce : (typeof novelDash !== 'undefined' ? novelDash.nonce : '');

    const Dashboard = {

        currentTab: 'overview',
        isLoading: false,

        init() {
            this.detectInitialTab();
            this.bindEvents();
            this.initProfileEdit();
            this.initDeleteAccount();
        },

        // ========================================
        // TAB NAVIGATION
        // ========================================

        detectInitialTab() {
            const params = new URLSearchParams(window.location.search);
            const tab = params.get('tab');
            if (tab) {
                this.currentTab = tab;
            }
            this.activateTab(this.currentTab, false);
        },

        bindEvents() {
            const self = this;

            // Desktop nav clicks
            $(document).on('click', '.dashboard__nav-item[data-tab]', function (e) {
                e.preventDefault();
                const tab = $(this).data('tab');
                self.switchTab(tab);
            });

            // Mobile nav clicks
            $(document).on('click', '.dashboard__mobile-tab[data-tab]', function (e) {
                e.preventDefault();
                const tab = $(this).data('tab');
                self.switchTab(tab);
            });

            // Bio character counter
            $(document).on('input', '#profileBio', function () {
                const max = 200;
                const len = $(this).val().length;
                const $counter = $('#bioCharCounter');
                $counter.text(`${len}/${max}`);
                $counter.toggleClass('is-limit', len >= max);
                if (len > max) {
                    $(this).val($(this).val().substring(0, max));
                    $counter.text(`${max}/${max}`);
                }
            });

            // Profile color selection
            $(document).on('click', '.profile-color-option', function () {
                $('.profile-color-option').removeClass('is-active');
                $(this).addClass('is-active');
            });

            // Notification toggle all in row
            $(document).on('change', '.notification-settings .toggle-switch input', function () {
                // Just visual feedback, saved on form submit
            });
        },

        switchTab(tab) {
            if (tab === this.currentTab && !this.isLoading) return;

            this.currentTab = tab;
            this.activateTab(tab, true);

            // Update URL without reload
            const url = new URL(window.location);
            url.searchParams.set('tab', tab);
            history.pushState({ tab: tab }, '', url);

            // Load tab content
            this.loadTabContent(tab);
        },

        activateTab(tab, animate) {
            // Desktop sidebar
            $('.dashboard__nav-item').removeClass('is-active');
            $(`.dashboard__nav-item[data-tab="${tab}"]`).addClass('is-active');

            // Mobile tabs
            $('.dashboard__mobile-tab').removeClass('is-active');
            $(`.dashboard__mobile-tab[data-tab="${tab}"]`).addClass('is-active');

            // Scroll mobile tab into view
            const $mobileTab = $(`.dashboard__mobile-tab[data-tab="${tab}"]`);
            if ($mobileTab.length) {
                const $nav = $('.dashboard__mobile-nav');
                const tabLeft = $mobileTab.position().left;
                const navWidth = $nav.width();
                $nav.scrollLeft(tabLeft - navWidth / 2 + $mobileTab.width() / 2);
            }
        },

        loadTabContent(tab) {
            if (this.isLoading) return;
            this.isLoading = true;

            const $content = $('.dashboard__content');

            // Show skeleton
            $content.html(this.getSkeleton());

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'novel_load_dashboard_tab',
                    nonce: nonce,
                    tab: tab,
                },
                dataType: 'html',
            })
            .done((html) => {
                $content.html(html);
                // Re-init components
                if (tab === 'profile') {
                    this.initProfileEdit();
                }
                if (tab === 'settings') {
                    this.initDeleteAccount();
                }
            })
            .fail(() => {
                $content.html('<div class="dashboard__error"><p>خطا در بارگذاری. لطفاً دوباره تلاش کنید.</p></div>');
            })
            .always(() => {
                this.isLoading = false;
            });
        },

        getSkeleton() {
            return `
                <div class="dashboard__skeleton">
                    <div class="skeleton-line skeleton-line--short"></div>
                    <div class="skeleton-line skeleton-line--long"></div>
                    <div class="skeleton-line skeleton-line--medium"></div>
                    <div style="display:flex;gap:12px;margin-top:20px;">
                        <div class="skeleton-box" style="flex:1;"></div>
                        <div class="skeleton-box" style="flex:1;"></div>
                        <div class="skeleton-box" style="flex:1;"></div>
                    </div>
                    <div class="skeleton-line skeleton-line--long" style="margin-top:20px;"></div>
                    <div class="skeleton-line skeleton-line--medium"></div>
                </div>
            `;
        },

        // ========================================
        // PROFILE EDIT
        // ========================================

        initProfileEdit() {
            const self = this;

            // Display name live check (reuse from auth.js if loaded)
            $(document).off('input.profileName').on('input.profileName', '#profileDisplayName', function () {
                const name = $(this).val().trim();
                const $group = $(this).closest('.form-group');
                const $feedback = $group.find('.field-feedback');

                if (name.length < 3) {
                    $feedback.text(name.length > 0 ? 'حداقل ۳ کاراکتر' : '').css('color', 'var(--color-danger)');
                    return;
                }
                if (name.length > 20) {
                    $feedback.text('حداکثر ۲۰ کاراکتر').css('color', 'var(--color-danger)');
                    return;
                }
                if (!/^[\u0600-\u06FFa-zA-Z0-9_ ]+$/.test(name)) {
                    $feedback.text('کاراکتر غیرمجاز').css('color', 'var(--color-danger)');
                    return;
                }

                $feedback.text('در حال بررسی...').css('color', 'var(--color-warning)');

                clearTimeout(self._nameTimer);
                self._nameTimer = setTimeout(() => {
                    $.post(ajaxUrl, {
                        action: 'novel_check_display_name',
                        nonce: nonce,
                        display_name: name,
                    }, function (res) {
                        if (res.success) {
                            $feedback.text('✓ نام در دسترس است').css('color', 'var(--color-success)');
                        } else {
                            $feedback.text('✗ ' + res.data.message).css('color', 'var(--color-danger)');
                        }
                    });
                }, 500);
            });

            // Save profile
            $(document).off('submit.profile').on('submit.profile', '#profileEditForm', function (e) {
                e.preventDefault();
                self.saveProfile($(this));
            });

            // Change password
            $(document).off('submit.password').on('submit.password', '#changePasswordForm', function (e) {
                e.preventDefault();
                self.changePassword($(this));
            });

            // Change email - initiate
            $(document).off('click.emailChange').on('click.emailChange', '#changeEmailBtn', function () {
                self.initiateEmailChange();
            });

            // Email verification code input auto-focus
            $(document).on('input', '.verification-code-inputs input', function () {
                if (this.value.length === 1) {
                    $(this).next('input').focus();
                }
            });

            $(document).on('keydown', '.verification-code-inputs input', function (e) {
                if (e.key === 'Backspace' && this.value === '') {
                    $(this).prev('input').focus();
                }
            });
        },

        saveProfile($form) {
            const $btn = $form.find('.btn--save-profile');
            $btn.addClass('is-loading').prop('disabled', true);

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: $form.serialize() + '&action=novel_save_profile&nonce=' + nonce,
            })
            .done((res) => {
                if (res.success) {
                    this.showToast(res.data.message || 'تغییرات ذخیره شد ✓', 'success');
                    // Update sidebar name
                    if (res.data.display_name) {
                        $('.dashboard__user-name').text(res.data.display_name);
                    }
                    if (res.data.avatar_url) {
                        $('.dashboard__user-avatar').attr('src', res.data.avatar_url);
                    }
                } else {
                    this.showToast(res.data.message || 'خطایی رخ داد.', 'error');
                }
            })
            .fail(() => {
                this.showToast('خطا در ارتباط با سرور.', 'error');
            })
            .always(() => {
                $btn.removeClass('is-loading').prop('disabled', false);
            });
        },

        changePassword($form) {
            const $btn = $form.find('.btn--change-password');
            $btn.addClass('is-loading').prop('disabled', true);

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: $form.serialize() + '&action=novel_change_password&nonce=' + nonce,
            })
            .done((res) => {
                if (res.success) {
                    this.showToast('رمز عبور تغییر کرد ✓', 'success');
                    $form[0].reset();
                } else {
                    this.showToast(res.data.message || 'خطایی رخ داد.', 'error');
                }
            })
            .fail(() => {
                this.showToast('خطا در ارتباط.', 'error');
            })
            .always(() => {
                $btn.removeClass('is-loading').prop('disabled', false);
            });
        },

        initiateEmailChange() {
            const newEmail = $('#newEmail').val().trim();
            if (!newEmail || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(newEmail)) {
                this.showToast('ایمیل معتبر وارد کنید.', 'error');
                return;
            }

            const $btn = $('#changeEmailBtn');
            $btn.addClass('is-loading').prop('disabled', true);

            $.post(ajaxUrl, {
                action: 'novel_initiate_email_change',
                nonce: nonce,
                new_email: newEmail,
            }, (res) => {
                $btn.removeClass('is-loading').prop('disabled', false);
                if (res.success) {
                    // Show verification code step
                    $('.email-change-step').removeClass('is-active');
                    $('#emailVerifyStep').addClass('is-active');
                    this.showToast('کد تأیید ارسال شد.', 'success');
                } else {
                    this.showToast(res.data.message, 'error');
                }
            });
        },

        // ========================================
        // DELETE ACCOUNT
        // ========================================

        initDeleteAccount() {
            const self = this;

            // Open modal
            $(document).off('click.deleteOpen').on('click.deleteOpen', '#deleteAccountBtn', function () {
                $('#deleteAccountModal').addClass('is-open');
                $('body').css('overflow', 'hidden');
            });

            // Close modal
            $(document).off('click.deleteClose').on('click.deleteClose', '.modal-overlay .modal__cancel, .modal-overlay', function (e) {
                if (e.target === this || $(this).hasClass('modal__cancel')) {
                    $('#deleteAccountModal').removeClass('is-open');
                    $('body').css('overflow', '');
                }
            });

            // Prevent close on modal body click
            $(document).on('click', '.modal', function (e) {
                e.stopPropagation();
            });

            // Confirm delete
            $(document).off('click.deleteConfirm').on('click.deleteConfirm', '#confirmDeleteBtn', function () {
                self.deleteAccount();
            });
        },

        deleteAccount() {
            const password = $('#deleteAccountPassword').val();
            const $error = $('#deleteAccountError');
            const $btn = $('#confirmDeleteBtn');

            if (!password) {
                $error.text('لطفاً رمز عبور خود را وارد کنید.').show();
                return;
            }

            $error.hide();
            $btn.addClass('is-loading').prop('disabled', true);

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'novel_delete_account',
                    nonce: nonce,
                    password: password,
                },
            })
            .done((res) => {
                if (res.success) {
                    // Show farewell message briefly
                    this.showToast('حساب شما حذف شد. به امید دیدار...', 'success');
                    setTimeout(() => {
                        window.location.href = res.data.redirect || '/';
                    }, 2000);
                } else {
                    $error.text(res.data.message || 'خطایی رخ داد.').show();
                    $btn.removeClass('is-loading').prop('disabled', false);
                }
            })
            .fail(() => {
                $error.text('خطا در ارتباط با سرور.').show();
                $btn.removeClass('is-loading').prop('disabled', false);
            });
        },

        // ========================================
        // UTILITIES
        // ========================================

        showToast(message, type = 'success') {
            if (typeof window.NovelToast !== 'undefined') {
                window.NovelToast.show(message, type);
                return;
            }

            const bg = type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#f59e0b';
            const $toast = $(`
                <div style="
                    position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(20px);
                    background:${bg};color:#fff;padding:12px 24px;border-radius:12px;
                    font-size:14px;font-weight:600;z-index:99999;opacity:0;
                    transition:all .3s;box-shadow:0 4px 20px rgba(0,0,0,.15);direction:rtl;
                ">${message}</div>
            `);

            $('body').append($toast);
            requestAnimationFrame(() => {
                $toast.css({ opacity: 1, transform: 'translateX(-50%) translateY(0)' });
            });
            setTimeout(() => {
                $toast.css({ opacity: 0, transform: 'translateX(-50%) translateY(20px)' });
                setTimeout(() => $toast.remove(), 300);
            }, 3500);
        },
    };

    // Handle browser back/forward
    $(window).on('popstate', function (e) {
        const state = e.originalEvent.state;
        if (state && state.tab) {
            Dashboard.currentTab = state.tab;
            Dashboard.activateTab(state.tab, false);
            Dashboard.loadTabContent(state.tab);
        }
    });

    $(document).ready(() => {
        if ($('.dashboard').length) {
            Dashboard.init();
        }
    });

})(jQuery);