<?php if (!defined('ABSPATH')) exit;
$sub = $_GET['sub'] ?? 'documentation';
?>
<div class="shipping-admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <div class="shipping-tabs-wrapper" style="display: flex; gap: 15px; overflow-x: auto; white-space: nowrap; padding-bottom: 5px; margin-bottom: 0; border-bottom: none;">
        <button class="shipping-tab-btn <?php echo $sub == 'documentation' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('customs-docs', this); CustomsController.loadDocs()">الوثائق والمستندات</button>
        <button class="shipping-tab-btn <?php echo $sub == 'invoices' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('customs-invoices', this); CustomsController.loadInvoices()">الفواتير التجارية</button>
        <button class="shipping-tab-btn <?php echo $sub == 'duties-taxes' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('customs-taxes', this)">الرسوم والضرائب</button>
        <button class="shipping-tab-btn <?php echo $sub == 'status' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('customs-status', this); CustomsController.loadStatus()">حالة التخليص</button>
    </div>
    <div style="display: flex; gap: 10px;">
        <button class="shipping-btn" onclick="ShippingModal.open('modal-add-customs')">+ بيان جمركي</button>
        <button class="shipping-btn" style="background: #4a5568;" onclick="ShippingModal.open('modal-add-customs-doc')">+ رفع مستند</button>
    </div>
</div>

<!-- Professional Search Engine for Customs -->
<div class="shipping-search-engine-block" id="customs-search-block">
    <form id="customs-advanced-search" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 15px; align-items: end;">
        <div class="shipping-form-group" style="margin-bottom:0;">
            <label style="font-size: 12px; font-weight: 700; color: #64748b;">بحث شامل (رقم الشحنة):</label>
            <input type="text" id="customs-search-query" class="shipping-input" placeholder="أدخل بيانات البحث..." oninput="CustomsController.filterCustoms()">
        </div>
        <div class="shipping-form-group" style="margin-bottom:0;">
            <label style="font-size: 12px; font-weight: 700; color: #64748b;">ترتيب حسب:</label>
            <select id="customs-sort-order" class="shipping-select" onchange="CustomsController.filterCustoms()">
                <option value="newest">الأحدث أولاً</option>
                <option value="oldest">الأقدم أولاً</option>
            </select>
        </div>
        <button type="button" onclick="CustomsController.resetFilters()" class="shipping-btn shipping-btn-outline" style="height: 45px; width: auto;">إعادة ضبط</button>
    </form>
</div>

<!-- Extra Logic to hide/show customs search block -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.shipping-tab-btn');
    tabs.forEach(t => {
        t.addEventListener('click', function() {
            const block = document.getElementById('customs-search-block');
            if (block) {
                const isTaxes = this.getAttribute('onclick').includes('customs-taxes');
                block.style.display = isTaxes ? 'none' : 'block';
            }
        });
    });
});
</script>

<div id="customs-invoices" class="shipping-internal-tab" style="display: <?php echo $sub == 'invoices' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>الفواتير التجارية المصاحبة للشحنات</h4>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead>
                    <tr>
                        <th>رقم الشحنة</th>
                        <th>رقم الفاتورة</th>
                        <th>المبلغ</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody id="customs-invoices-table">
                    <!-- Data via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="customs-docs" class="shipping-internal-tab" style="display: <?php echo $sub == 'documentation' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>وثائق التخليص الجمركي للشحنات</h4>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead>
                    <tr>
                        <th>رقم الشحنة</th>
                        <th>نوع المستند</th>
                        <th>الحالة</th>
                        <th>تاريخ الرفع</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody id="customs-docs-table">
                    <!-- Data via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="customs-taxes" class="shipping-internal-tab" style="display: <?php echo $sub == 'duties-taxes' ? 'block' : 'none'; ?>;">
    <div class="shipping-grid" style="grid-template-columns: 2fr 1fr;">
        <div class="shipping-card">
            <h4>تقدير الرسوم الجمركية والضرائب</h4>
            <form id="form-tax-calculator" style="margin-top: 20px;">
                <div class="shipping-form-group">
                    <label>القيمة المصرح بها للبضاعة (<?php echo esc_html($currency); ?>)</label>
                    <input type="number" id="goods-value" class="shipping-input" placeholder="0.00">
                </div>
                <div class="shipping-form-group">
                    <label>فئة البضاعة / رمز HS</label>
                    <select id="hs-category" class="shipping-select">
                        <option value="0.05">إلكترونيات (5%)</option>
                        <option value="0.10">ملابس ومنسوجات (10%)</option>
                        <option value="0.15">قطع غيار (15%)</option>
                        <option value="0">أدوية ومواد طبية (0%)</option>
                        <option value="0.05">أخرى (5%)</option>
                    </select>
                </div>
                <div class="shipping-form-group">
                    <label>بلد المنشأ</label>
                    <input type="text" class="shipping-input" placeholder="مثال: الصين">
                </div>
                <button type="button" class="shipping-btn" onclick="CustomsController.calculateTax()">احسب التقدير</button>
            </form>
        </div>
        <div class="shipping-card" id="tax-result-card" style="display: none; background: #fffaf0; border: 1px solid #feebc8;">
            <h4>ملخص التقدير</h4>
            <div style="display: grid; gap: 15px; margin-top: 20px;">
                <div style="display: flex; justify-content: space-between;"><span>الرسوم الجمركية:</span><strong id="res-duties">0.00 <?php echo esc_html($currency); ?></strong></div>
                <div style="display: flex; justify-content: space-between;"><span>ضريبة القيمة المضافة (15%):</span><strong id="res-vat">0.00 <?php echo esc_html($currency); ?></strong></div>
                <div style="display: flex; justify-content: space-between; border-top: 1px solid #eee; padding-top: 10px; font-size: 1.2em; color: #c05621;">
                    <span>إجمالي التقدير:</span><strong id="res-total-tax">0.00 <?php echo esc_html($currency); ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="customs-status" class="shipping-internal-tab" style="display: <?php echo $sub == 'status' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>متابعة طلبات التخليص الجاري</h4>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead>
                    <tr>
                        <th>رقم الشحنة</th>
                        <th>الحالة الورقية</th>
                        <th>الرسوم المقدرة</th>
                        <th>حالة التخليص</th>
                    </tr>
                </thead>
                <tbody id="customs-status-table">
                    <!-- Loaded via AJAX in loadCustomsStatus() -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Customs Record Modal -->
<div id="modal-add-customs" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 500px;">
        <div class="shipping-modal-header">
            <h3>إضافة بيان جمركي جديد</h3>
            <button class="shipping-modal-close" onclick="ShippingModal.close('modal-add-customs')">&times;</button>
        </div>
        <form id="form-add-customs-full">
            <input type="hidden" name="action" value="shipping_add_customs">
            <?php wp_nonce_field('shipping_customs_action', 'nonce'); ?>
            <div class="shipping-modal-body">
                <div class="shipping-form-group">
                    <label>رقم الشحنة</label>
                    <select name="shipment_id" id="select-customs-shipment" class="shipping-input" required>
                        <option value="">اختر الشحنة...</option>
                        <!-- Loaded via AJAX -->
                    </select>
                </div>
                <div class="shipping-form-group">
                    <label>حالة التوثيق</label>
                    <select name="documentation_status" class="shipping-select">
                        <option value="complete">مكتملة</option>
                        <option value="pending">قيد المراجعة</option>
                        <option value="missing-info">نقص بيانات</option>
                    </select>
                </div>
                <div class="shipping-form-group">
                    <label>الرسوم الجمركية المقدرة (<?php echo esc_html($currency); ?>)</label>
                    <input type="number" step="0.01" name="duties_amount" class="shipping-input" required>
                </div>
                <div class="shipping-form-group">
                    <label>حالة التخليص الميداني</label>
                    <select name="clearance_status" class="shipping-select">
                        <option value="waiting">في الانتظار</option>
                        <option value="under-inspection">تحت التفتيش</option>
                        <option value="released">تم الفسح</option>
                        <option value="held">محجوزة</option>
                    </select>
                </div>
            </div>
            <div class="shipping-modal-footer">
                <button type="submit" class="shipping-btn">حفظ البيان الجمركي</button>
            </div>
        </form>
    </div>
</div>

<!-- Upload Doc Modal -->
<div id="modal-add-customs-doc" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 500px;">
        <div class="shipping-modal-header">
            <h3>رفع مستند جمركي</h3>
            <button class="shipping-modal-close" onclick="ShippingModal.close('modal-add-customs-doc')">&times;</button>
        </div>
        <form id="form-add-customs-doc">
            <input type="hidden" name="action" value="shipping_add_customs_doc">
            <?php wp_nonce_field('shipping_customs_action', 'nonce'); ?>
            <div class="shipping-modal-body">
                <div class="shipping-form-group">
                    <label>رقم الشحنة</label>
                    <select name="shipment_id" id="select-doc-shipment" class="shipping-input" required>
                        <option value="">اختر الشحنة...</option>
                        <!-- Loaded via AJAX -->
                    </select>
                </div>
                <div class="shipping-form-group">
                    <label>نوع المستند</label>
                    <select name="doc_type" class="shipping-select">
                        <option value="Bill of Lading">بوليصة الشحن (BOL)</option>
                        <option value="Commercial Invoice">فاتورة تجارية</option>
                        <option value="Packing List">قائمة التعبئة</option>
                        <option value="Certificate of Origin">شهادة المنشأ</option>
                    </select>
                </div>
                <div class="shipping-form-group">
                    <label>رابط المستند / الملف</label>
                    <input type="text" name="file_url" class="shipping-input" placeholder="https://..." required>
                </div>
            </div>
            <div class="shipping-modal-footer">
                <button type="submit" class="shipping-btn">رفع المستند</button>
            </div>
        </form>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', () => {
    CustomsController.init();
});
</script>
