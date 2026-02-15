<?php
/**
 * Report Modal Template
 * 
 * مودال مشترک گزارش‌دهی برای همه انواع
 *
 * @package suspended-flavor
 * @since 3.0.0
 */

if (!defined('ABSPATH')) exit;
?>

<div class="novel-report-modal" id="reportModal" style="display:none" role="dialog" 
     aria-modal="true" aria-labelledby="reportModalTitle">
    
    <!-- Overlay -->
    <div class="report-modal__overlay" data-close-modal></div>
    
    <!-- Content -->
    <div class="report-modal__content glassmorphism">
        
        <!-- Header -->
        <div class="report-modal__header">
            <h3 class="report-modal__title" id="reportModalTitle">
                🚩 گزارش <span id="reportTypeTitle"></span>
            </h3>
            <button class="report-modal__close" data-close-modal aria-label="بستن">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" 
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        
        <!-- Hidden fields -->
        <input type="hidden" id="reportedType" value="">
        <input type="hidden" id="reportedId" value="">
        
        <!-- Already reported message -->
        <div class="report-modal__already" id="reportAlreadyMsg" style="display:none">
            <div class="report-modal__already-icon">✅</div>
            <p>شما قبلاً این مورد را گزارش کرده‌اید</p>
            <span class="report-modal__already-sub">گزارش شما در حال بررسی است</span>
        </div>
        
        <!-- Report form -->
        <div class="report-modal__form" id="reportForm">
            
            <!-- Reasons -->
            <div class="report-modal__reasons" id="reportReasons">
                <!-- JS fills dynamically based on type -->
            </div>
            
            <!-- Description -->
            <div class="report-modal__desc-wrap" id="reportDescWrap" style="display:none">
                <label for="reportDescription" class="report-modal__desc-label">
                    توضیحات <span id="reportDescReq" style="display:none">(الزامی)</span>
                </label>
                <textarea id="reportDescription" class="report-modal__textarea" 
                          placeholder="توضیحات بیشتر درباره مشکل..." 
                          maxlength="500" rows="3"></textarea>
                <div class="report-modal__char-count">
                    <span id="reportCharCount">0</span>/500
                </div>
            </div>
            
            <!-- Submit -->
            <button class="report-modal__submit" id="submitReport" disabled>
                <span class="report-modal__submit-text">ارسال گزارش</span>
                <span class="report-modal__submit-loading" style="display:none">
                    <span class="spinner-dots">
                        <span></span><span></span><span></span>
                    </span>
                    در حال ارسال...
                </span>
            </button>
            
        </div>
        
    </div>
</div>