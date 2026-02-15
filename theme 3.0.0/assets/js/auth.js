/**
 * Auth Pages JavaScript
 * Handles registration, login, forgot password, reset password, verify email
 *
 * @package suspended-starter
 * @since 3.0.0
 */

(function ($) {
    'use strict';

    // ========================================
    // UTILITY FUNCTIONS
    // ========================================

    const Auth = {
        debounceTimers: {},
        
        /**
         * Show message in auth-messages container
         */
        showMessage(container, message, type = 'error') {
            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
            };
            
            const $container = $(container);
            $container.html(`
                <div class="auth-message auth-message--${type}">
                    <span class="auth-message__icon">${icons[type] || ''}</span>
                    <span>${message}</span>
                </div>
            `).show();
            
            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(() => $container.find('.auth-message').fadeOut(300), 5000);
            }
        },
        
        /**
         * Clear messages
         */
        clearMessages(container) {
            $(container).empty().hide();
        },
        
        /**
         * Set field validation state
         */
        setFieldState($group, state, message = '') {
            $group.removeClass('is-valid is-invalid is-checking').addClass(`is-${state}`);
            const $feedback = $group.find('.field-feedback');
            if ($feedback.length) {
                $feedback.text(message);
            }
        },
        
        /**
         * Clear field state
         */
        clearFieldState($group) {
            $group.removeClass('is-valid is-invalid is-checking');
            $group.find('.field-feedback').text('');
            $group.find('.field-suggestions').empty();
        },
        
        /**
         * Set button loading state
         */
        setLoading($btn, loading) {
            if (loading) {
                $btn.addClass('is-loading').prop('disabled', true);
            } else {
                $btn.removeClass('is-loading').prop('disabled', false);
            }
        },
        
        /**
         * Debounce function
         */
        debounce(key, fn, delay = 500) {
            if (this.debounceTimers[key]) {
                clearTimeout(this.debounceTimers[key]);
            }
            this.debounceTimers[key] = setTimeout(fn, delay);
        },
        
        /**
         * Validate email format
         */
        isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },
        
        /**
         * AJAX request wrapper
         */
        ajax(data) {
            return $.ajax({
                url: novelAuth.ajaxUrl,
                type: 'POST',
                data: data,
                dataType: 'json',
            });
        },
    };

    // ========================================
    // TOGGLE PASSWORD VISIBILITY
    // ========================================

    $(document).on('click', '.toggle-password', function (e) {
        e.preventDefault();
        const target = $(this).data('target');
        const $input = $(`#${target}`);
        const $eyeOpen = $(this).find('.eye-open');
        const $eyeClosed = $(this).find('.eye-closed');
        
        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $eyeOpen.hide();
            $eyeClosed.show();
        } else {
            $input.attr('type', 'password');
            $eyeOpen.show();
            $eyeClosed.hide();
        }
    });

    // ========================================
    // PASSWORD STRENGTH METER
    // ========================================

    function checkPasswordStrength(password) {
        const checks = {
            length: password.length >= 8,
            number: /[0-9]/.test(password),
            uppercase: /[A-Z]/.test(password),
            special: /[^A-Za-z0-9]/.test(password),
        };
        
        // Update requirement indicators
        Object.keys(checks).forEach(key => {
            const $req = $(`.requirement[data-req="${key}"]`);
            if ($req.length) {
                $req.toggleClass('met', checks[key]);
                $req.find('.req-icon').text(checks[key] ? '✓' : '✗');
            }
        });
        
        // Calculate strength level
        let score = 0;
        if (checks.length) score++;
        if (checks.number) score++;
        if (checks.uppercase) score++;
        if (checks.special) score++;
        
        let level, text;
        if (password.length === 0) {
            level = '';
            text = '';
        } else if (score <= 1) {
            level = 'weak';
            text = novelAuth.i18n.passwordWeak;
        } else if (score === 2) {
            level = 'fair';
            text = novelAuth.i18n.passwordFair;
        } else if (score === 3) {
            level = 'good';
            text = novelAuth.i18n.passwordGood;
        } else {
            level = 'strong';
            text = novelAuth.i18n.passwordStrong;
        }
        
        // Update UI
        const $fill = $('#strengthFill');
        const $text = $('#strengthText');
        
        $fill.attr('data-level', level);
        $text.attr('data-level', level).text(text);
        
        return {
            checks,
            level,
            isValid: checks.length && checks.number && checks.uppercase,
        };
    }

    // Listen for password input
    $(document).on('input', '#password', function () {
        const password = $(this).val();
        const result = checkPasswordStrength(password);
        
        // Check confirm password match if filled
        const $confirm = $('#password_confirm');
        if ($confirm.length && $confirm.val().length > 0) {
            checkPasswordMatch(password, $confirm.val());
        }
    });

    // ========================================
    // PASSWORD CONFIRM MATCH
    // ========================================

    function checkPasswordMatch(password, confirm) {
        const $group = $('[data-field="password_confirm"]');
        
        if (confirm.length === 0) {
            Auth.clearFieldState($group);
            return false;
        }
        
        if (password === confirm) {
            Auth.setFieldState($group, 'valid', novelAuth.i18n.passwordsMatch);
            return true;
        } else {
            Auth.setFieldState($group, 'invalid', novelAuth.i18n.passwordsMismatch);
            return false;
        }
    }

    $(document).on('input', '#password_confirm', function () {
        const password = $('#password').val();
        checkPasswordMatch(password, $(this).val());
        updateRegisterButton();
    });

    // ========================================
    // REGISTRATION FORM
    // ========================================

    // Display name live check
    $(document).on('input', '#registerForm #display_name', function () {
        const name = $(this).val().trim();
        const $group = $(this).closest('.form-group');
        
        // Clear suggestions
        $('#displayNameSuggestions').empty();
        
        if (name.length < 3) {
            Auth.clearFieldState($group);
            if (name.length > 0) {
                Auth.setFieldState($group, 'invalid', 'حداقل ۳ کاراکتر وارد کنید.');
            }
            updateRegisterButton();
            return;
        }
        
        if (name.length > 20) {
            Auth.setFieldState($group, 'invalid', 'حداکثر ۲۰ کاراکتر مجاز است.');
            updateRegisterButton();
            return;
        }
        
        // Check allowed characters
        if (!/^[\u0600-\u06FFa-zA-Z0-9_ ]+$/.test(name)) {
            Auth.setFieldState($group, 'invalid', 'فقط حروف فارسی، انگلیسی، عدد، فاصله و آندرلاین مجاز است.');
            updateRegisterButton();
            return;
        }
        
        Auth.setFieldState($group, 'checking', novelAuth.i18n.checkingName);
        
        Auth.debounce('displayName', () => {
            Auth.ajax({
                action: 'novel_check_display_name',
                nonce: novelAuth.nonce,
                display_name: name,
            }).done(function (res) {
                if (res.success) {
                    Auth.setFieldState($group, 'valid', novelAuth.i18n.nameAvailable);
                } else {
                    Auth.setFieldState($group, 'invalid', res.data.message);
                    // Show suggestions
                    if (res.data.suggestions && res.data.suggestions.length > 0) {
                        const $suggestions = $('#displayNameSuggestions');
                        res.data.suggestions.forEach(name => {
                            $suggestions.append(
                                `<button type="button" class="suggestion-btn" data-name="${name}">${name}</button>`
                            );
                        });
                    }
                }
                updateRegisterButton();
            }).fail(function () {
                Auth.clearFieldState($group);
                updateRegisterButton();
            });
        }, 500);
    });

    // Click on suggestion
    $(document).on('click', '.suggestion-btn', function () {
        const name = $(this).data('name');
        $('#display_name').val(name).trigger('input');
    });

    // Email live check
    $(document).on('blur', '#registerForm #email', function () {
        const email = $(this).val().trim();
        const $group = $(this).closest('.form-group');
        
        if (!email) {
            Auth.clearFieldState($group);
            updateRegisterButton();
            return;
        }
        
        if (!Auth.isValidEmail(email)) {
            Auth.setFieldState($group, 'invalid', novelAuth.i18n.invalidEmail);
            updateRegisterButton();
            return;
        }
        
        Auth.setFieldState($group, 'checking', novelAuth.i18n.checkingEmail);
        
        Auth.ajax({
            action: 'novel_check_email',
            nonce: novelAuth.nonce,
            email: email,
        }).done(function (res) {
            if (res.success) {
                Auth.setFieldState($group, 'valid', novelAuth.i18n.emailAvailable);
            } else {
                const msg = res.data.login_url
                    ? `${res.data.message} <a href="${res.data.login_url}">وارد شوید</a>`
                    : res.data.message;
                Auth.setFieldState($group, 'invalid', '');
                $group.find('.field-feedback').html(msg);
            }
            updateRegisterButton();
        }).fail(function () {
            Auth.clearFieldState($group);
            updateRegisterButton();
        });
    });

    // Terms checkbox
    $(document).on('change', '#accept_terms', function () {
        updateRegisterButton();
    });

    // Update register button state
    function updateRegisterButton() {
        const $form = $('#registerForm');
        if (!$form.length) return;
        
        const $btn = $('#registerBtn');
        const $nameGroup = $form.find('[data-field="display_name"]');
        const $emailGroup = $form.find('[data-field="email"]');
        
        const nameValid = $nameGroup.hasClass('is-valid');
        const emailValid = $emailGroup.hasClass('is-valid');
        const password = $form.find('#password').val() || '';
        const passwordResult = checkPasswordStrength(password);
        const confirmMatch = $form.find('#password_confirm').val() === password && password.length > 0;
        const termsAccepted = $form.find('#accept_terms').is(':checked');
        
        const allValid = nameValid && emailValid && passwordResult.isValid && confirmMatch && termsAccepted;
        $btn.prop('disabled', !allValid);
    }

    // Submit registration
    $(document).on('submit', '#registerForm', function (e) {
        e.preventDefault();
        
        const $form = $(this);
        const $btn = $('#registerBtn');
        const $messages = $('#authMessages');
        
        Auth.clearMessages($messages);
        Auth.setLoading($btn, true);
        
        const formData = $form.serialize();
        
        Auth.ajax(formData).done(function (res) {
            if (res.success) {
                Auth.showMessage($messages, res.data.message, 'success');
                // Redirect after short delay
                if (res.data.redirect) {
                    setTimeout(() => {
                        window.location.href = res.data.redirect;
                    }, 1000);
                }
            } else {
                Auth.showMessage($messages, res.data.message, 'error');
                
                // Highlight specific field
                if (res.data.field) {
                    const $group = $form.find(`[data-field="${res.data.field}"]`);
                    Auth.setFieldState($group, 'invalid', res.data.message);
                }
                
                Auth.setLoading($btn, false);
            }
        }).fail(function () {
            Auth.showMessage($messages, novelAuth.i18n.genericError, 'error');
            Auth.setLoading($btn, false);
        });
    });

    // ========================================
    // LOGIN FORM
    // ========================================

    $(document).on('submit', '#loginForm', function (e) {
        e.preventDefault();
        
        const $form = $(this);
        const $btn = $('#loginBtn');
        const $messages = $('#authMessages');
        
        const login = $form.find('#login').val().trim();
        const password = $form.find('#password').val();
        
        // Basic validation
        if (!login || !password) {
            Auth.showMessage($messages, 'لطفاً تمام فیلدها را پر کنید.', 'error');
            return;
        }
        
        Auth.clearMessages($messages);
        Auth.setLoading($btn, true);
        
        Auth.ajax($form.serialize()).done(function (res) {
            if (res.success) {
                Auth.showMessage($messages, res.data.message, 'success');
                if (res.data.redirect) {
                    setTimeout(() => {
                        window.location.href = res.data.redirect;
                    }, 800);
                }
            } else {
                Auth.showMessage($messages, res.data.message, 'error');
                
                // Rate limited - show countdown
                if (res.data.code === 'rate_limited' && res.data.wait_time) {
                    startCountdown($btn, res.data.wait_time);
                }
                
                Auth.setLoading($btn, false);
            }
        }).fail(function () {
            Auth.showMessage($messages, novelAuth.i18n.genericError, 'error');
            Auth.setLoading($btn, false);
        });
    });

    // Rate limit countdown for login
    function startCountdown($btn, seconds) {
        $btn.prop('disabled', true);
        let remaining = Math.ceil(seconds);
        
        const $text = $btn.find('.btn-text');
        const originalText = $text.text();
        
        const timer = setInterval(() => {
            remaining--;
            if (remaining <= 0) {
                clearInterval(timer);
                $text.text(originalText);
                $btn.prop('disabled', false);
            } else {
                const mins = Math.floor(remaining / 60);
                const secs = remaining % 60;
                $text.text(`${mins}:${secs.toString().padStart(2, '0')} صبر کنید`);
            }
        }, 1000);
    }

    // ========================================
    // FORGOT PASSWORD FORM
    // ========================================

    $(document).on('submit', '#forgotForm', function (e) {
        e.preventDefault();
        
        const $form = $(this);
        const $btn = $('#forgotBtn');
        const $messages = $('#authMessages');
        const email = $form.find('#email').val().trim();
        
        if (!email) {
            Auth.showMessage($messages, 'لطفاً ایمیل خود را وارد کنید.', 'error');
            return;
        }
        
        if (!Auth.isValidEmail(email)) {
            Auth.showMessage($messages, novelAuth.i18n.invalidEmail, 'error');
            return;
        }
        
        Auth.clearMessages($messages);
        Auth.setLoading($btn, true);
        
        Auth.ajax($form.serialize()).done(function (res) {
            // Always show success (security)
            $form.hide();
            $('#forgotSuccess').fadeIn(300);
        }).fail(function () {
            Auth.showMessage($messages, novelAuth.i18n.genericError, 'error');
            Auth.setLoading($btn, false);
        });
    });

    // ========================================
    // RESET PASSWORD FORM
    // ========================================

    $(document).on('submit', '#resetForm', function (e) {
        e.preventDefault();
        
        const $form = $(this);
        const $btn = $('#resetBtn');
        const $messages = $('#authMessages');
        
        const password = $form.find('#password').val();
        const confirm = $form.find('#password_confirm').val();
        
        // Validate
        const strength = checkPasswordStrength(password);
        if (!strength.isValid) {
            Auth.showMessage($messages, 'رمز عبور شرایط لازم را ندارد.', 'error');
            return;
        }
        
        if (password !== confirm) {
            Auth.showMessage($messages, novelAuth.i18n.passwordsMismatch, 'error');
            return;
        }
        
        Auth.clearMessages($messages);
        Auth.setLoading($btn, true);
        
        Auth.ajax($form.serialize()).done(function (res) {
            if (res.success) {
                Auth.showMessage($messages, res.data.message, 'success');
                if (res.data.redirect) {
                    setTimeout(() => {
                        window.location.href = res.data.redirect;
                    }, 1500);
                }
            } else {
                Auth.showMessage($messages, res.data.message, 'error');
                Auth.setLoading($btn, false);
            }
        }).fail(function () {
            Auth.showMessage($messages, novelAuth.i18n.genericError, 'error');
            Auth.setLoading($btn, false);
        });
    });

    // ========================================
    // RESEND VERIFICATION EMAIL
    // ========================================

    $(document).on('click', '#resendVerifyBtn', function () {
        const $btn = $(this);
        const $messages = $('#authMessages');
        const $cooldown = $('#resendCooldown');
        const $timer = $('#cooldownTimer');
        
        Auth.clearMessages($messages);
        Auth.setLoading($btn, true);
        
        Auth.ajax({
            action: 'novel_resend_verify',
            nonce: novelAuth.nonce,
        }).done(function (res) {
            if (res.success) {
                Auth.showMessage($messages, res.data.message, 'success');
                
                // Start cooldown
                const cooldown = res.data.cooldown || 120;
                $btn.hide();
                $cooldown.show();
                
                let remaining = cooldown;
                $timer.text(remaining);
                
                const countdown = setInterval(() => {
                    remaining--;
                    $timer.text(remaining);
                    
                    if (remaining <= 0) {
                        clearInterval(countdown);
                        $cooldown.hide();
                        $btn.show();
                        Auth.setLoading($btn, false);
                    }
                }, 1000);
            } else {
                Auth.showMessage($messages, res.data.message, 'error');
                
                // If cooldown error
                if (res.data.cooldown) {
                    $btn.hide();
                    $cooldown.show();
                    
                    let remaining = res.data.cooldown;
                    $timer.text(remaining);
                    
                    const countdown = setInterval(() => {
                        remaining--;
                        $timer.text(remaining);
                        if (remaining <= 0) {
                            clearInterval(countdown);
                            $cooldown.hide();
                            $btn.show();
                        }
                    }, 1000);
                }
                
                Auth.setLoading($btn, false);
            }
        }).fail(function () {
            Auth.showMessage($messages, novelAuth.i18n.genericError, 'error');
            Auth.setLoading($btn, false);
        });
    });

    // ========================================
    // FORM INPUT ANIMATIONS
    // ========================================

    // Focus animation for input wrapper
    $(document).on('focus', '.form-input', function () {
        $(this).closest('.input-wrapper').addClass('is-focused');
    });

    $(document).on('blur', '.form-input', function () {
        $(this).closest('.input-wrapper').removeClass('is-focused');
    });

    // ========================================
    // INIT
    // ========================================

    $(document).ready(function () {
        // Auto-focus first input
        $('.auth-form').first().find('.form-input:first').focus();
        
        // Init password strength if password field has value (browser autofill)
        const $password = $('#password');
        if ($password.length && $password.val()) {
            checkPasswordStrength($password.val());
        }
    });

})(jQuery);