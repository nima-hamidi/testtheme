<?php
/**
 * پنل تنظیمات خواندن
 * شامل: فونت، سایز، فاصله خط، عرض، تم رنگی
 *
 * @package Flavor_Novel
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="fn-reader-settings" id="fnReaderSettings">
    <div class="fn-reader-settings__header">
        <h3 class="fn-reader-settings__title">⚙️ تنظیمات خواندن</h3>
        <button class="fn-reader-settings__close" id="fnSettingsClose">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>

    <!-- حالت رنگی -->
    <div class="fn-reader-settings__group">
        <label class="fn-reader-settings__label">🎨 حالت نمایش</label>
        <div class="fn-reader-settings__row fn-theme-options">
            <button class="fn-theme-btn fn-theme-btn--light active" data-reader-theme="light" title="روشن">
                <span style="font-size:10px; display:block; text-align:center; margin-top: 2px;">ر</span>
            </button>
            <button class="fn-theme-btn fn-theme-btn--dark" data-reader-theme="dark" title="تاریک">
                <span style="font-size:10px; display:block; text-align:center; margin-top: 2px; color:#fff;">ت</span>
            </button>
            <button class="fn-theme-btn fn-theme-btn--sepia" data-reader-theme="sepia" title="سپیا">
                <span style="font-size:10px; display:block; text-align:center; margin-top: 2px;">س</span>
            </button>
        </div>
    </div>

    <!-- فونت -->
    <div class="fn-reader-settings__group">
        <label class="fn-reader-settings__label">🔤 فونت</label>
        <select class="fn-reader-settings__select" id="fnFontSelect">
            <option value="Vazirmatn" selected>وزیرمتن</option>
            <option value="IRANSansX">ایران‌سنس X</option>
            <option value="Sahel">ساحل</option>
            <option value="Shabnam">شبنم</option>
            <option value="Tahoma">تاهوما</option>
            <option value="serif">سریف (پیش‌فرض مرورگر)</option>
        </select>
    </div>

    <!-- اندازه فونت -->
    <div class="fn-reader-settings__group">
        <label class="fn-reader-settings__label">
            📏 اندازه فونت
            <span class="fn-settings-value" id="fnFontSizeValue">18px</span>
        </label>
        <div class="fn-reader-settings__row">
            <button class="fn-settings-btn" id="fnFontSizeDown" aria-label="کوچک‌تر">A-</button>
            <input type="range" class="fn-settings-range" id="fnFontSizeRange"
                   min="14" max="28" value="18" step="1">
            <button class="fn-settings-btn" id="fnFontSizeUp" aria-label="بزرگ‌تر">A+</button>
        </div>
    </div>

    <!-- فاصله خطوط -->
    <div class="fn-reader-settings__group">
        <label class="fn-reader-settings__label">
            ↕️ فاصله خطوط
            <span class="fn-settings-value" id="fnLineHeightValue">2</span>
        </label>
        <div class="fn-reader-settings__row">
            <button class="fn-settings-btn" id="fnLineHeightDown">−</button>
            <input type="range" class="fn-settings-range" id="fnLineHeightRange"
                   min="1.4" max="3" value="2" step="0.1">
            <button class="fn-settings-btn" id="fnLineHeightUp">+</button>
        </div>
    </div>

    <!-- عرض محتوا -->
    <div class="fn-reader-settings__group">
        <label class="fn-reader-settings__label">
            ↔️ عرض محتوا
            <span class="fn-settings-value" id="fnContentWidthValue">800px</span>
        </label>
        <div class="fn-reader-settings__row">
            <button class="fn-settings-btn" id="fnWidthDown">⇤</button>
            <input type="range" class="fn-settings-range" id="fnContentWidthRange"
                   min="500" max="1100" value="800" step="50">
            <button class="fn-settings-btn" id="fnWidthUp">⇥</button>
        </div>
    </div>

    <!-- تراز متن -->
    <div class="fn-reader-settings__group">
        <label class="fn-reader-settings__label">📐 تراز متن</label>
        <div class="fn-reader-settings__row">
            <button class="fn-settings-align-btn active" data-align="justify" title="تراز دوطرفه">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                    <line x1="3" y1="14" x2="21" y2="14"/>
                    <line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
                <span>دوطرفه</span>
            </button>
            <button class="fn-settings-align-btn" data-align="right" title="راست‌چین">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <line x1="9" y1="10" x2="21" y2="10"/>
                    <line x1="3" y1="14" x2="21" y2="14"/>
                    <line x1="9" y1="18" x2="21" y2="18"/>
                </svg>
                <span>راست</span>
            </button>
            <button class="fn-settings-align-btn" data-align="left" title="چپ‌چین">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <line x1="3" y1="10" x2="15" y2="10"/>
                    <line x1="3" y1="14" x2="21" y2="14"/>
                    <line x1="3" y1="18" x2="15" y2="18"/>
                </svg>
                <span>چپ</span>
            </button>
            <button class="fn-settings-align-btn" data-align="center" title="وسط‌چین">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <line x1="6" y1="10" x2="18" y2="10"/>
                    <line x1="3" y1="14" x2="21" y2="14"/>
                    <line x1="6" y1="18" x2="18" y2="18"/>
                </svg>
                <span>وسط</span>
            </button>
        </div>
    </div>

    <!-- بازنشانی -->
    <div class="fn-reader-settings__group">
        <button class="fn-btn fn-btn--ghost fn-btn--sm fn-btn--full" id="fnResetSettings">
            🔄 بازنشانی به پیش‌فرض
        </button>
    </div>
</div>