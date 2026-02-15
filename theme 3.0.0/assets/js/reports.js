/**
 * Novel Reports System
 * 
 * مودال گزارش‌دهی + تعامل AJAX
 *
 * @package suspended-flavor
 * @since 3.0.0
 */

(function($) {
    'use strict';

    if (typeof novelReports === 'undefined') return;

    const Reports = {
        modal: null,
        currentType: '',
        currentId: 0,
        selectedReason: '',
        isSubmitting: false,

        /**
         * Initialize
         */
        init() {
            this.modal = document.getElementById('reportModal');
            if (!this.modal) return;

            this.bindEvents();
        },

        /**
         * Bind all events
         */
        bindEvents() {
            // Open modal triggers
            $(document).on('click', '[data-report-type]', (e) => {
                e.preventDefault();
                const btn = e.currentTarget;
                const type = btn.dataset.reportType;
                const id = parseInt(btn.dataset.reportId, 10);
                
                if (type && id) {
                    this.openModal(type, id);
                }
            });

            // Close modal
            $(this.modal).on('click', '[data-close-modal]', (e) => {
                e.preventDefault();
                this.closeModal();
            });

            // Escape key
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen()) {
                    this.closeModal();
                }
            });

            // Reason selection
            $(this.modal).on('click', '.report-reason-card', (e) => {
                this.selectReason(e.currentTarget);
            });

            // Character counter
            $('#reportDescription').on('input', (e) => {
                const len = e.target.value.length;
                $('#reportCharCount').text(len);
            });

            // Submit
            $('#submitReport').on('click', (e) => {
                e.preventDefault();
                this.submitReport();
            });
        },

        /**
         * Check if modal is open
         */
        isOpen() {
            return this.modal && this.modal.style.display !== 'none';
        },

        /**
         * Open modal
         */
        openModal(type, id) {
            // Check login
            if (!document.body.classList.contains('logged-in')) {
                this.showToast(novelReports.strings.loginRequired, 'warning');
                return;
            }

            this.currentType = type;
            this.currentId = id;
            this.selectedReason = '';
            this.isSubmitting = false;

            // Reset form
            this.resetForm();

            // Set type title
            const titles = {
                chapter: 'قسمت',
                comment: 'دیدگاه',
                user: 'کاربر',
                novel: 'رمان'
            };
            $('#reportTypeTitle').text(titles[type] || type);
            $('#reportedType').val(type);
            $('#reportedId').val(id);

            // Build reasons
            this.buildReasons(type);

            // Check if already reported
            this.checkAlreadyReported(type, id);

            // Show modal
            this.modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';

            // Focus trap
            setTimeout(() => {
                const firstCard = this.modal.querySelector('.report-reason-card');
                if (firstCard) firstCard.focus();
            }, 300);
        },

        /**
         * Close modal
         */
        closeModal() {
            if (!this.isOpen()) return;

            this.modal.classList.add('closing');
            
            setTimeout(() => {
                this.modal.style.display = 'none';
                this.modal.classList.remove('closing');
                document.body.style.overflow = '';
                this.resetForm();
            }, 250);
        },

        /**
         * Reset form
         */
        resetForm() {
            this.selectedReason = '';
            $('#reportReasons').empty();
            $('#reportDescription').val('');
            $('#reportCharCount').text('0');
            $('#reportDescWrap').hide();
            $('#reportDescReq').hide();
            $('#submitReport').prop('disabled', true);
            $('#reportForm').show();
            $('#reportAlreadyMsg').hide();
            
            // Reset button state
            $('.report-modal__submit-text').show();
            $('.report-modal__submit-loading').hide();
        },

        /**
         * Build reason cards
         */
        buildReasons(type) {
            const reasons = novelReports.reasons[type] || [];
            const container = $('#reportReasons');
            container.empty();

            reasons.forEach((reason, index) => {
                const card = $(`
                    <label class="report-reason-card" tabindex="0" data-reason="${reason.value}">
                        <input type="radio" name="report_reason" value="${reason.value}">
                        <span class="report-reason__radio"></span>
                        <span class="report-reason__icon">${reason.icon}</span>
                        <span class="report-reason__label">${reason.label}</span>
                    </label>
                `);

                // Keyboard support
                card.on('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.selectReason(card[0]);
                    }
                });

                container.append(card);
            });
        },

        /**
         * Select reason
         */
        selectReason(el) {
            const $el = $(el);
            const reason = $el.data('reason');

            // Deselect others
            $('.report-reason-card').removeClass('selected');
            $el.addClass('selected');
            $el.find('input[type="radio"]').prop('checked', true);

            this.selectedReason = reason;

            // Show/hide description
            if (reason) {
                $('#reportDescWrap').slideDown(200);
                
                if (reason === 'other') {
                    $('#reportDescReq').show();
                } else {
                    $('#reportDescReq').hide();
                }
            }

            // Enable submit
            this.updateSubmitState();
        },

        /**
         * Update submit button state
         */
        updateSubmitState() {
            const reason = this.selectedReason;
            const desc = $('#reportDescription').val().trim();
            
            let enabled = !!reason;
            
            // "other" requires description
            if (reason === 'other' && !desc) {
                enabled = false;
            }
            
            $('#submitReport').prop('disabled', !enabled || this.isSubmitting);
        },

        /**
         * Check if already reported
         */
        checkAlreadyReported(type, id) {
            $.ajax({
                url: novelReports.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'novel_check_reported',
                    nonce: novelReports.nonce,
                    reported_type: type,
                    reported_id: id
                },
                success: (response) => {
                    if (response.success && response.data.reported) {
                        $('#reportForm').hide();
                        $('#reportAlreadyMsg').show();
                    }
                }
            });
        },

        /**
         * Submit report
         */
        submitReport() {
            if (this.isSubmitting) return;
            if (!this.selectedReason) {
                this.showToast(novelReports.strings.selectReason, 'warning');
                return;
            }

            const description = $('#reportDescription').val().trim();
            
            if (this.selectedReason === 'other' && !description) {
                this.showToast(novelReports.strings.descRequired, 'warning');
                $('#reportDescription').focus();
                return;
            }

            this.isSubmitting = true;
            
            // UI loading state
            $('#submitReport').prop('disabled', true);
            $('.report-modal__submit-text').hide();
            $('.report-modal__submit-loading').show();

            $.ajax({
                url: novelReports.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'novel_submit_report',
                    nonce: novelReports.nonce,
                    reported_type: this.currentType,
                    reported_id: this.currentId,
                    reason: this.selectedReason,
                    description: description
                },
                success: (response) => {
                    if (response.success) {
                        this.showToast(response.data.message, 'success');
                        
                        // Mark trigger button as reported
                        $(`[data-report-type="${this.currentType}"][data-report-id="${this.currentId}"]`)
                            .addClass('reported')
                            .attr('title', novelReports.strings.alreadyReported);
                        
                        this.closeModal();
                    } else {
                        const msg = response.data?.message || novelReports.strings.error;
                        
                        if (response.data?.already_reported) {
                            $('#reportForm').hide();
                            $('#reportAlreadyMsg').show();
                        } else {
                            this.showToast(msg, 'error');
                        }
                    }
                },
                error: () => {
                    this.showToast(novelReports.strings.error, 'error');
                },
                complete: () => {
                    this.isSubmitting = false;
                    $('.report-modal__submit-text').show();
                    $('.report-modal__submit-loading').hide();
                    $('#submitReport').prop('disabled', false);
                }
            });
        },

        /**
         * Show toast notification
         */
        showToast(message, type) {
            // Use global toast if available
            if (typeof window.NovelToast !== 'undefined') {
                window.NovelToast.show(message, type);
                return;
            }

            // Fallback: simple toast
            const toast = $(`
                <div class="novel-toast novel-toast--${type}" style="
                    position: fixed;
                    bottom: 2rem;
                    left: 50%;
                    transform: translateX(-50%) translateY(20px);
                    padding: 0.75rem 1.5rem;
                    border-radius: 12px;
                    color: #fff;
                    font-size: 0.9rem;
                    font-weight: 500;
                    z-index: 99999;
                    opacity: 0;
                    transition: all 0.3s ease;
                    font-family: 'Vazirmatn', sans-serif;
                    background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#f59e0b'};
                    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
                    max-width: 90%;
                    text-align: center;
                ">${message}</div>
            `).appendTo('body');

            requestAnimationFrame(() => {
                toast.css({ opacity: 1, transform: 'translateX(-50%) translateY(0)' });
            });

            setTimeout(() => {
                toast.css({ opacity: 0, transform: 'translateX(-50%) translateY(20px)' });
                setTimeout(() => toast.remove(), 300);
            }, 3500);
        }
    };

    // Description input → update submit state
    $(document).on('input', '#reportDescription', function() {
        Reports.updateSubmitState();
    });

    // Init on ready
    $(document).ready(() => Reports.init());

    // Expose globally
    window.NovelReports = Reports;

})(jQuery);