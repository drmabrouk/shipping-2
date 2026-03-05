<?php if (!defined('ABSPATH')) exit; ?>
<div class="shipping-verify-container" dir="rtl">
    <div class="shipping-verify-header">
        <h2 style="font-weight: 800; color: var(--shipping-dark-color); margin-bottom: 10px;">محرك التحقق الرسمي</h2>
        <p style="color: #64748b; font-size: 14px;">قم بالتحقق من صحة وصلاحية المستندات والعميليات الرسمية الصادرة عن Shipping.</p>
    </div>

    <div class="shipping-verify-search-box">
        <form id="shipping-verify-form">
            <div style="display: grid; grid-template-columns: 1fr 2fr auto; gap: 15px; align-items: flex-end;">
                <div class="shipping-form-group" style="margin-bottom: 0;">
                    <label class="shipping-label">نوع البحث:</label>
                    <select id="shipping-verify-type" class="shipping-select" style="background: #fff;">
                        <option value="all">اسم المستخدم</option>
                        <option value="customership">رقم التعريف</option>
                        <option value="license">رقم رخصة المنشأة</option>
                        <option value="practice">رقم تصريح المزاولة</option>
                    </select>
                </div>
                <div class="shipping-form-group" style="margin-bottom: 0;">
                    <label class="shipping-label">قيمة البحث:</label>
                    <input type="text" id="shipping-verify-value" class="shipping-input" placeholder="أدخل الرقم المراد التحقق منه..." style="background: #fff;">
                </div>
                <button type="submit" class="shipping-btn" style="height: 45px; padding: 0 30px; font-weight: 700;">تحقق الآن</button>
            </div>
        </form>
    </div>

    <div id="shipping-verify-loading" style="display: none; text-align: center; padding: 40px;">
        <span class="dashicons dashicons-update spin" style="font-size: 30px; color: var(--shipping-primary-color); width: 30px; height: 30px;"></span>
        <p style="margin-top: 10px; color: #64748b;">جاري استعلام البيانات من قاعدة البيانات...</p>
    </div>

    <div id="shipping-verify-results" style="margin-top: 30px;"></div>
</div>

<style>
/* Verification styles handled in shipping-public.css */
#shipping-verify-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 150px;
}
</style>

