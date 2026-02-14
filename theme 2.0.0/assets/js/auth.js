/**
 * سیستم احراز هویت - نسخه ۴ اصلاح‌شده
 * رفع: کیبورد موبایل + Edge nonce + نوار قدرت رمز + تغییر ایمیل دو مرحله‌ای
 */
(function ($) {
    'use strict';

    if (typeof novelAuth === 'undefined') return;

    var AUTH = novelAuth;
    var nameCheckTimer = null;

    // ═══════════════════════════════════
    // رفع کیبورد موبایل — حذف autofocus
    // ═══════════════════════════════════
    $(document).ready(function () {
        // حذف autofocus از تمام input‌ها در موبایل
        if ('ontouchstart' in window || navigator.maxTouchPoints > 0) {
            $('input[autofocus]').removeAttr('autofocus');
            // blur تمام فیلدها
            setTimeout(function () {
                document.activeElement && document.activeElement.blur();
            }, 100);
        }
    });

    // ═══════════════════════════════════
    // توابع کمکی
    // ═══════════════════════════════════
    function showMsg($container, message, type) {
        $container
            .html(message)
            .removeClass('is-error is-success is-warning')
            .addClass('is-' + type)
            .stop(true, true)
            .slideDown(300);
    }

    function hideMsg($container) {
        $container.stop(true, true).slideUp(200);
    }

    function findMessages($el) {
        var $msg = $el.closest('.novel-auth-card, .novel-dashboard-card, form').find('.novel-auth-messages, .novel-profile-messages').first();
        if (!$msg.length) {
            $msg = $el.siblings('.novel-auth-messages').first();
        }
        if (!$msg.length) {
            $msg = $el.closest('.novel-auth-card').find('.novel-auth-messages').first();
        }
        return $msg;
    }

    function setLoading($btn, loading) {
        if (loading) {
            $btn.prop('disabled', true);
            $btn.find('.novel-auth-btn-text').hide();
            $btn.find('.novel-auth-btn-loading').css('display', 'inline-flex');
        } else {
            $btn.prop('disabled', false);
            $btn.find('.novel-auth-btn-text').show();
            $btn.find('.novel-auth-btn-loading').hide();
        }
    }

    function setFieldStatus($el, className, text) {
        $el.removeClass('is-checking is-available is-taken is-match is-mismatch is-error is-info')
            .addClass(className)
            .html(text)
            .stop(true, true)
            .slideDown(200);
    }

    // ارسال AJAX سازگار با Edge
    function novelAjax(data, successCb, errorCb) {
        // اضافه کردن nonce به header برای سازگاری Edge
        $.ajax({
            url: AUTH.ajaxurl,
            type: 'POST',
            data: data,
            dataType: 'json',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', AUTH.nonce);
            },
            success: successCb,
            error: function (xhr, status, error) {
                if (typeof errorCb === 'function') {
                    errorCb(xhr, status, error);
                }
            }
        });
    }

    // ═══════════════════════════════════
    // اعتبارسنجی نام فارسی (سمت کلاینت)
    // ═══════════════════════════════════
    var persianRegex = /^[\u0600-\u06FF\u200C\s\-]+$/;

    function validateDisplayNameClient(name) {
        name = (name || '').trim();
        var len = name.length;

        if (len === 0) {
            return { valid: false, message: '', type: 'empty' };
        }

        if (len < 5) {
            return {
                valid: false,
                message: '📝 ' + AUTH.i18n.name_min,
                type: 'min'
            };
        }

        if (len > 20) {
            return {
                valid: false,
                message: '📝 حداکثر ۲۰ کاراکتر',
                type: 'max'
            };
        }

        if (!persianRegex.test(name)) {
            return {
                valid: false,
                message: '🔤 ' + AUTH.i18n.name_persian_only,
                type: 'charset'
            };
        }

        if (name.charAt(0) === '-' || name.charAt(name.length - 1) === '-') {
            return {
                valid: false,
                message: '⚠️ ' + AUTH.i18n.name_dash_invalid,
                type: 'dash'
            };
        }

        if (name.indexOf('--') !== -1) {
            return {
                valid: false,
                message: '⚠️ ' + AUTH.i18n.name_dash_invalid,
                type: 'dash'
            };
        }

        return { valid: true, message: '', type: 'ok' };
    }

    // جلوگیری از تایپ کاراکتر غیرمجاز
    function filterPersianInput(input) {
        var val = $(input).val() || '';
        var filtered = val.replace(/[^\u0600-\u06FF\u200C\s\-]/g, '');
        if (filtered !== val) {
            $(input).val(filtered);
        }
    }

    // ═══════════════════════════════════
    // Toggle رمز عبور
    // ═══════════════════════════════════
    $(document).on('click', '.novel-auth-toggle-pass', function (e) {
        e.preventDefault();
        var targetId = $(this).data('target');
        var $input = $('#' + targetId);
        var isPassword = $input.attr('type') === 'password';
        $input.attr('type', isPassword ? 'text' : 'password');
        $(this).find('.eye-open').toggle(!isPassword);
        $(this).find('.eye-closed').toggle(isPassword);
    });

    // ═══════════════════════════════════
    // نوار قدرت رمز — رفع مشکل بعد حذف کاراکتر
    // ═══════════════════════════════════
    function checkPasswordStrength(password) {
        if (!password || password.length === 0) return null;
        var score = 0;
        if (password.length >= 8) score++;
        if (password.length >= 12) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^A-Za-z0-9]/.test(password)) score++;
        if (score <= 2) return 'weak';
        if (score <= 4) return 'medium';
        return 'strong';
    }

    function updateStrengthBar($field) {
        var val = $field.val() || '';
        var $container = $field.closest('.novel-auth-field');
        var $fill = $container.find('.novel-auth-strength-fill');
        var $text = $container.find('.novel-auth-strength-text');

        // پاک کردن کلاس‌های قبلی — کامل
        $fill.removeClass('strength-weak strength-medium strength-strong').css('width', '0%');
        $text.removeClass('strength-weak strength-medium strength-strong').text('');

        if (val.length === 0) return;

        var strength = checkPasswordStrength(val);
        if (!strength) return;

        var labels = {
            weak: AUTH.i18n.weak,
            medium: AUTH.i18n.medium,
            strong: AUTH.i18n.strong
        };
        var widths = { weak: '33%', medium: '66%', strong: '100%' };

        $fill.addClass('strength-' + strength).css('width', widths[strength]);
        $text.addClass('strength-' + strength).text(labels[strength]);
    }

    // بایند روی تمام رویدادهای ممکن — رفع مشکل بعد حذف
    $(document).on('input keyup keydown paste change focus', '#reg-password, #reset-password, #new-password', function () {
        updateStrengthBar($(this));
    });

    // ═══════════════════════════════════
    // تطابق رمز
    // ═══════════════════════════════════
    $(document).on('input keyup', '#reg-password-confirm, #reset-password-confirm, #new-password-confirm', function () {
        var $form = $(this).closest('form');
        var pass = $form.find('input[name="password"], input[name="new_password"]').val() || '';
        var confirm = $(this).val() || '';
        var $status = $(this).closest('.novel-auth-field').find('.novel-auth-field-status');

        if (confirm.length === 0) {
            $status.html('').removeClass('is-match is-mismatch').slideUp(100);
            return;
        }

        if (pass === confirm) {
            setFieldStatus($status, 'is-match', AUTH.i18n.passwords_match);
        } else {
            setFieldStatus($status, 'is-mismatch', AUTH.i18n.passwords_diff);
        }
    });

    // ═══════════════════════════════════
    // بررسی نام نمایشی — فیلتر + AJAX
    // ═══════════════════════════════════
    $(document).on('input', '#reg-display-name, #dashboard-display-name', function () {
        var $input = $(this);
        var $status = $input.closest('.novel-auth-field').find('.novel-auth-field-status');

        // فیلتر کاراکتر غیرمجاز
        filterPersianInput(this);

        var val = $input.val().trim();
        clearTimeout(nameCheckTimer);

        // اعتبارسنجی سمت کلاینت
        var validation = validateDisplayNameClient(val);

        if (validation.type === 'empty') {
            $status.html('').slideUp(100);
            return;
        }

        if (!validation.valid) {
            setFieldStatus($status, 'is-error', validation.message);
            $input.removeClass('is-success').addClass('is-error');
            return;
        }

        // اگر معتبره → بررسی یکتایی AJAX
        setFieldStatus($status, 'is-checking', '🔍 ' + AUTH.i18n.checking);

        nameCheckTimer = setTimeout(function () {
            novelAjax({
                action: 'novel_check_display_name',
                nonce: AUTH.nonce,
                display_name: val
            }, function (res) {
                if (res.success) {
                    setFieldStatus($status, 'is-available', res.data.message);
                    $input.removeClass('is-error').addClass('is-success');
                } else {
                    setFieldStatus($status, 'is-taken', '✗ ' + res.data.message);
                    $input.removeClass('is-success').addClass('is-error');
                }
            }, function () {
                $status.html('').slideUp(100);
            });
        }, 600);
    });

    // ═══════════════════════════════════
    // فرم ورود
    // ═══════════════════════════════════
    $(document).on('submit', '#novel-login-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $('#novel-login-btn');
        var $messages = findMessages($form);

        hideMsg($messages);
        setLoading($btn, true);

        novelAjax($form.serialize(), function (res) {
            setLoading($btn, false);
            if (res.success) {
                showMsg($messages, '✅ ' + res.data.message, 'success');
                if (res.data.redirect) {
                    setTimeout(function () {
                        window.location.href = res.data.redirect;
                    }, 800);
                }
            } else {
                showMsg($messages, '❌ ' + res.data.message, 'error');
                if (res.data.need_verify && res.data.redirect) {
                    setTimeout(function () {
                        window.location.href = res.data.redirect;
                    }, 2500);
                }
            }
        }, function () {
            setLoading($btn, false);
            showMsg($messages, '❌ خطا در ارتباط با سرور. لطفاً صفحه را رفرش کنید.', 'error');
        });
    });

    // ═══════════════════════════════════
    // فرم ثبت‌نام
    // ═══════════════════════════════════
    $(document).on('submit', '#novel-register-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $('#novel-register-btn');
        var $messages = findMessages($form);

        var name = $form.find('#reg-display-name').val().trim();
        var email = $form.find('#reg-email').val().trim();
        var pass = $form.find('#reg-password').val();
        var pass2 = $form.find('#reg-password-confirm').val();
        var rules = $form.find('#reg-accept-rules').is(':checked');

        var errors = [];

        var nameV = validateDisplayNameClient(name);
        if (!nameV.valid && nameV.type !== 'empty') {
            errors.push(nameV.message);
        } else if (name.length === 0) {
            errors.push('نام نمایشی الزامی است.');
        }
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errors.push('ایمیل معتبر وارد کنید.');
        if (pass.length < 8) errors.push('رمز عبور حداقل ۸ کاراکتر.');
        if (!/[A-Z]/.test(pass)) errors.push('رمز عبور باید حداقل یک حرف بزرگ انگلیسی داشته باشد.');
        if (!/[0-9]/.test(pass)) errors.push('رمز عبور باید حداقل یک عدد داشته باشد.');
        if (pass !== pass2) errors.push('تکرار رمز عبور مطابقت ندارد.');
        if (!rules) errors.push('پذیرش قوانین الزامی است.');

        if (errors.length) {
            showMsg($messages, '⚠️ ' + errors.join('<br>⚠️ '), 'error');
            return;
        }

        hideMsg($messages);
        setLoading($btn, true);

        novelAjax($form.serialize(), function (res) {
            setLoading($btn, false);
            if (res.success) {
                showMsg($messages, '✅ ' + res.data.message, 'success');
                if (res.data.redirect) {
                    setTimeout(function () { window.location.href = res.data.redirect; }, 1500);
                }
            } else {
                showMsg($messages, '❌ ' + res.data.message, 'error');
            }
        }, function () {
            setLoading($btn, false);
            showMsg($messages, '❌ خطا در ارتباط با سرور.', 'error');
        });
    });

    // ═══════════════════════════════════
    // فرم فراموشی رمز
    // ═══════════════════════════════════
    $(document).on('submit', '#novel-forgot-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $('#novel-forgot-btn');
        var $messages = findMessages($form);

        hideMsg($messages);
        setLoading($btn, true);

        novelAjax($form.serialize(), function (res) {
            setLoading($btn, false);
            if (res.success) {
                showMsg($messages, '✅ ' + res.data.message, 'success');
                $btn.prop('disabled', true);
            } else {
                showMsg($messages, '❌ ' + res.data.message, 'error');
            }
        }, function () {
            setLoading($btn, false);
            showMsg($messages, '❌ خطای ارتباط', 'error');
        });
    });

    // ═══════════════════════════════════
    // فرم ریست رمز
    // ═══════════════════════════════════
    $(document).on('submit', '#novel-reset-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $('#novel-reset-btn');
        var $messages = findMessages($form);

        hideMsg($messages);
        setLoading($btn, true);

        novelAjax($form.serialize(), function (res) {
            setLoading($btn, false);
            if (res.success) {
                showMsg($messages, '✅ ' + res.data.message, 'success');
                if (res.data.redirect) {
                    setTimeout(function () { window.location.href = res.data.redirect; }, 1500);
                }
            } else {
                showMsg($messages, '❌ ' + res.data.message, 'error');
            }
        }, function () {
            setLoading($btn, false);
            showMsg($messages, '❌ خطای ارتباط', 'error');
        });
    });

    // ═══════════════════════════════════
    // ارسال مجدد تأیید ایمیل
    // ═══════════════════════════════════
    var resendCooldownInterval = null;

    $(document).on('click', '.novel-resend-verify-btn', function () {
        var $btn = $(this);
        var email = $btn.data('email');
        var $messages = $('#verify-messages');
        var $cooldown = $('#resend-cooldown');

        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true).text(AUTH.i18n.sending);

        novelAjax({
            action: 'novel_resend_verification',
            nonce: AUTH.nonce,
            email: email
        }, function (res) {
            if (res.success) {
                showMsg($messages, '✅ ' + res.data.message, 'success');
                var remaining = res.data.cooldown || 180;
                startCooldown($btn, $cooldown, remaining);
                if (typeof res.data.remaining_sends !== 'undefined') {
                    $('#resend-remaining').text(AUTH.i18n.remaining_sends + ' ' + res.data.remaining_sends);
                }
            } else {
                if (res.data.cooldown) {
                    startCooldown($btn, $cooldown, res.data.cooldown);
                } else {
                    $btn.prop('disabled', false).text(AUTH.i18n.resend_btn);
                }
                showMsg($messages, '❌ ' + res.data.message, 'error');
            }
        }, function () {
            showMsg($messages, '❌ خطای ارتباط', 'error');
            $btn.prop('disabled', false).text(AUTH.i18n.resend_btn);
        });
    });

    function startCooldown($btn, $cooldown, seconds) {
        clearInterval(resendCooldownInterval);
        $cooldown.show();
        $btn.prop('disabled', true);

        function updateTimer() {
            if (seconds <= 0) {
                clearInterval(resendCooldownInterval);
                $cooldown.hide();
                $btn.prop('disabled', false).text(AUTH.i18n.resend_btn);
                return;
            }
            var m = Math.floor(seconds / 60);
            var s = seconds % 60;
            var timeStr = m + ':' + (s < 10 ? '0' : '') + s;
            $cooldown.text(AUTH.i18n.resend_wait.replace('%s', timeStr));
            $btn.text(AUTH.i18n.resend_wait.replace('%s', timeStr));
            seconds--;
        }

        updateTimer();
        resendCooldownInterval = setInterval(updateTimer, 1000);
    }

    // ═══════════════════════════════════
    // سیستم آواتار (داشبورد)
    // ═══════════════════════════════════
    $(document).on('click', '.novel-avatar-grid-toggle button', function () {
        var $grid = $(this).closest('.novel-avatar-section, .novel-dashboard-profile-col').find('.novel-avatar-grid');
        var isOpen = $grid.hasClass('is-open');
        if (isOpen) {
            $grid.removeClass('is-open').slideUp(300);
            $(this).html('🖼 ' + AUTH.i18n.select_avatar);
        } else {
            $grid.addClass('is-open').slideDown(300);
            $(this).text(AUTH.i18n.close);
        }
    });

    $(document).on('click', '.novel-avatar-grid-item', function () {
        var $this = $(this);
        var $section = $this.closest('.novel-avatar-section, .novel-dashboard-profile-col');
        $section.find('.novel-avatar-grid-item').removeClass('is-selected');
        $this.addClass('is-selected');
        $section.find('.novel-avatar-save-wrap').addClass('is-visible').slideDown(200);
        $section.find('#selected-avatar-id').val($this.data('avatar-id'));
    });

    $(document).on('click', '#novel-save-avatar-btn', function () {
        var $btn = $(this);
        var avatarId = $('#selected-avatar-id').val();
        if (!avatarId) return;

        $btn.prop('disabled', true).text(AUTH.i18n.saving);

        novelAjax({
            action: 'novel_save_avatar',
            nonce: AUTH.nonce,
            avatar_id: avatarId
        }, function (res) {
            if (res.success) {
                // بروزرسانی آواتار
                $('#current-avatar-preview').attr('src', res.data.avatar_url);
                $('.novel-avatar, .user-header-avatar, .user-avatar img, .novel-user-avatar img').each(function () {
                    $(this).attr('src', res.data.avatar_url);
                });
                $btn.text(AUTH.i18n.saved).addClass('is-saved');
                setTimeout(function () {
                    $btn.prop('disabled', false).text(AUTH.i18n.save_avatar).removeClass('is-saved');
                }, 2500);
            } else {
                alert(res.data.message);
                $btn.prop('disabled', false).text(AUTH.i18n.save_avatar);
            }
        }, function () {
            alert('خطای ارتباط');
            $btn.prop('disabled', false).text(AUTH.i18n.save_avatar);
        });
    });

    // ═══════════════════════════════════
    // بروزرسانی پروفایل (داشبورد) — AJAX بدون رفرش
    // ═══════════════════════════════════
    $(document).on('submit', '#novel-profile-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var $messages = $form.find('.novel-profile-messages, .novel-auth-messages').first();
        var displayName = $form.find('#dashboard-display-name').val().trim();

        var nameV = validateDisplayNameClient(displayName);
        if (!nameV.valid) {
            showMsg($messages, '⚠️ ' + nameV.message, 'error');
            return;
        }

        hideMsg($messages);
        setLoading($btn, true);

        novelAjax({
            action: 'novel_update_profile',
            nonce: AUTH.nonce,
            display_name: displayName
        }, function (res) {
            setLoading($btn, false);
            if (res.success) {
                showMsg($messages, '✅ ' + res.data.message, 'success');
                // بروزرسانی نام در هدر بدون رفرش
                if (res.data.display_name) {
                    $('.user-display-name, [data-user-display-name]').text(res.data.display_name);
                }
            } else {
                showMsg($messages, '❌ ' + res.data.message, 'error');
            }
        }, function () {
            setLoading($btn, false);
            showMsg($messages, '❌ خطای ارتباط', 'error');
        });
    });

    // ═══════════════════════════════════
    // تغییر رمز عبور (داشبورد — پروفایل)
    // ═══════════════════════════════════
    $(document).on('submit', '#novel-change-password-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var $messages = $form.find('.novel-auth-messages').first();

        var currentPass = $form.find('#current-password').val();
        var newPass = $form.find('#new-password').val();
        var newPass2 = $form.find('#new-password-confirm').val();

        var errors = [];
        if (!currentPass) errors.push('رمز فعلی الزامی است.');
        if (newPass.length < 8) errors.push('رمز جدید حداقل ۸ کاراکتر.');
        if (!/[A-Z]/.test(newPass)) errors.push('رمز جدید باید حداقل یک حرف بزرگ انگلیسی داشته باشد.');
        if (!/[0-9]/.test(newPass)) errors.push('رمز جدید باید حداقل یک عدد داشته باشد.');
        if (newPass !== newPass2) errors.push('تکرار رمز جدید مطابقت ندارد.');
        if (currentPass === newPass) errors.push('رمز جدید نباید با رمز فعلی یکسان باشد.');

        if (errors.length) {
            showMsg($messages, '⚠️ ' + errors.join('<br>⚠️ '), 'error');
            return;
        }

        hideMsg($messages);
        setLoading($btn, true);

        novelAjax({
            action: 'novel_change_password',
            nonce: AUTH.nonce,
            current_password: currentPass,
            new_password: newPass,
            new_password_confirm: newPass2
        }, function (res) {
            setLoading($btn, false);
            if (res.success) {
                showMsg($messages, '✅ ' + res.data.message, 'success');
                $form[0].reset();
                // پاک کردن نوار قدرت
                $form.find('.novel-auth-strength-fill').css('width', '0%').removeClass('strength-weak strength-medium strength-strong');
                $form.find('.novel-auth-strength-text').text('').removeClass('strength-weak strength-medium strength-strong');
            } else {
                showMsg($messages, '❌ ' + res.data.message, 'error');
            }
        }, function () {
            setLoading($btn, false);
            showMsg($messages, '❌ خطای ارتباط', 'error');
        });
    });

    // ═══════════════════════════════════
    // تغییر ایمیل — مرحله ۱: ارسال کد
    // ═══════════════════════════════════
    $(document).on('click', '#novel-send-email-code-btn', function () {
        var $btn = $(this);
        var newEmail = $('#new-email-input').val().trim();
        var $msg = $('#email-change-messages');

        if (!newEmail) {
            showMsg($msg, '⚠️ ایمیل جدید را وارد کنید.', 'error');
            return;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(newEmail)) {
            showMsg($msg, '⚠️ ایمیل معتبر وارد کنید.', 'error');
            return;
        }

        setLoading($btn, true);

        novelAjax({
            action: 'novel_send_email_change_code',
            nonce: AUTH.nonce,
            new_email: newEmail
        }, function (res) {
            setLoading($btn, false);
            if (res.success) {
                showMsg($msg, '✅ ' + res.data.message, 'success');
                // نمایش باکس کد تأیید
                $('#email-change-code-section').slideDown(300);
                // غیرفعال کردن فیلد ایمیل
                $('#new-email-input').prop('readonly', true);
            } else {
                showMsg($msg, '❌ ' + res.data.message, 'error');
            }
        }, function () {
            setLoading($btn, false);
            showMsg($msg, '❌ خطای ارتباط', 'error');
        });
    });

    // ═══════════════════════════════════
    // تغییر ایمیل — مرحله ۲: تأیید کد
    // ═══════════════════════════════════
    $(document).on('click', '#novel-verify-email-code-btn', function () {
        var $btn = $(this);
        var code = $('#email-change-code-input').val().trim();
        var $msg = $('#email-change-messages');

        if (!code || code.length !== 6) {
            showMsg($msg, '⚠️ کد ۶ رقمی وارد کنید.', 'error');
            return;
        }

        setLoading($btn, true);

        novelAjax({
            action: 'novel_verify_email_change_code',
            nonce: AUTH.nonce,
            code: code
        }, function (res) {
            setLoading($btn, false);
            if (res.success) {
                showMsg($msg, '✅ ' + res.data.message, 'success');
                // مخفی کردن باکس کد
                $('#email-change-code-section').slideUp(300);
                $('#new-email-input').val('').prop('readonly', false);
            } else {
                showMsg($msg, '❌ ' + res.data.message, 'error');
            }
        }, function () {
            setLoading($btn, false);
            showMsg($msg, '❌ خطای ارتباط', 'error');
        });
    });

    // ═══════════════════════════════════
    // داشبورد موبایل — منوی کشویی
    // ═══════════════════════════════════
    $(document).on('click', '#dashboard-menu-toggle', function () {
        var $sidebar = $('.dashboard-sidebar, .user-dashboard-sidebar, .novel-dashboard-sidebar');
        var $overlay = $('#dashboard-overlay');
        var $toggle = $(this);

        if ($sidebar.hasClass('is-open')) {
            $sidebar.removeClass('is-open');
            $overlay.removeClass('is-active');
            $toggle.html('☰');
        } else {
            $sidebar.addClass('is-open');
            $overlay.addClass('is-active');
            $toggle.html('✕');
        }
    });

    $(document).on('click', '#dashboard-overlay', function () {
        $('.dashboard-sidebar, .user-dashboard-sidebar, .novel-dashboard-sidebar').removeClass('is-open');
        $(this).removeClass('is-active');
        $('#dashboard-menu-toggle').html('☰');
    });

    // بستن ساید‌بار با کلیک روی لینک منو (موبایل)
    $(document).on('click', '.dashboard-sidebar a, .user-dashboard-sidebar a, .novel-dashboard-sidebar a', function () {
        if (window.innerWidth <= 768) {
            $('.dashboard-sidebar, .user-dashboard-sidebar, .novel-dashboard-sidebar').removeClass('is-open');
            $('#dashboard-overlay').removeClass('is-active');
            $('#dashboard-menu-toggle').html('☰');
        }
    });

    // ═══════════════════════════════════
    // تیک اطلاع‌رسانی (داشبورد)
    // ═══════════════════════════════════
    $(document).on('change', '#notify-comment-reply', function () {
        var val = $(this).is(':checked') ? '1' : '0';
        novelAjax({
            action: 'novel_update_profile_meta',
            nonce: AUTH.nonce,
            meta_key: 'notify_comment_reply',
            meta_value: val
        }, function () {
            // ذخیره خاموش
        });
    });

})(jQuery);