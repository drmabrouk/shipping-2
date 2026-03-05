<?php if (!defined('ABSPATH')) exit;
global $wpdb;
$sub = $_GET['sub'] ?? 'invoice-gen';
?>
<div class="shipping-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #eee; overflow-x: auto; white-space: nowrap; padding-bottom: 10px;">
    <button class="shipping-tab-btn <?php echo $sub == 'invoice-gen' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('billing-invoice', this)">إصدار فواتير</button>
    <button class="shipping-tab-btn <?php echo $sub == 'records' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('billing-records', this)">سجلات الدفع</button>
    <button class="shipping-tab-btn <?php echo $sub == 'balances' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('billing-balances', this)">الأرصدة</button>
    <button class="shipping-tab-btn <?php echo $sub == 'reports' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('billing-reports', this)">التقارير المالية</button>
    <button class="shipping-tab-btn <?php echo $sub == 'calculator' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('pricing-calc', this)">حاسبة التكلفة</button>
    <button class="shipping-tab-btn <?php echo $sub == 'pricing-rules' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('pricing-rules', this)">قواعد التسعير</button>
    <button class="shipping-tab-btn <?php echo $sub == 'extra-charges' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('pricing-extra', this)">رسوم إضافية</button>
</div>

<!-- 2. Payment Records -->
<div id="billing-records" class="shipping-internal-tab" style="display: <?php echo $sub == 'records' ? 'block' : 'none'; ?>;">
    <?php
    $payments = $wpdb->get_results("SELECT p.*, i.invoice_number, CONCAT(c.first_name, ' ', c.last_name) as customer_name FROM {$wpdb->prefix}shipping_payments p JOIN {$wpdb->prefix}shipping_invoices i ON p.invoice_id = i.id JOIN {$wpdb->prefix}shipping_customers c ON i.customer_id = c.id ORDER BY p.payment_date DESC");
    ?>
    <div class="shipping-card">
        <h4>سجل المدفوعات والتحويلات</h4>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead><tr><th>المعرف</th><th>رقم الفاتورة</th><th>العميل</th><th>المبلغ</th><th>الوسيلة</th><th>التاريخ</th></tr></thead>
                <tbody>
                    <?php if(empty($payments)): ?>
                        <tr><td colspan="6" style="text-align:center; padding:20px;">لا توجد سجلات دفع حالياً.</td></tr>
                    <?php else: foreach($payments as $p): ?>
                        <tr>
                            <td>#<?php echo $p->transaction_id; ?></td>
                            <td><strong><?php echo $p->invoice_number; ?></strong></td>
                            <td><?php echo esc_html($p->customer_name); ?></td>
                            <td style="color:#2f855a; font-weight:700;">+ <?php echo number_format($p->amount_paid, 2); ?> <?php echo esc_html($currency); ?></td>
                            <td><?php echo $p->payment_method; ?></td>
                            <td><?php echo $p->payment_date; ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 1. Automated Invoice Generation -->
<div id="billing-invoice" class="shipping-internal-tab" style="display: <?php echo $sub == 'invoice-gen' ? 'block' : 'none'; ?>;">
    <div class="shipping-grid" style="grid-template-columns: 2fr 1fr;">
        <div class="shipping-card">
            <h4>إصدار فاتورة شحن</h4>
            <div style="background: #fdf2f2; padding: 15px; border-radius: 10px; border: 1px solid #fed7d7; margin-bottom: 20px; font-size: 13px;">
                <strong>نصيحة:</strong> يمكنك استيراد بيانات الشحنة لحساب التكلفة والبنود تلقائياً بناءً على قواعد التسعير المسجلة.
            </div>

            <div class="shipping-form-group" style="margin-bottom: 25px; display: flex; gap: 10px; align-items: flex-end;">
                <div style="flex: 1;">
                    <label>استيراد بيانات من رقم شحنة:</label>
                    <input type="text" id="import-shipment-number" class="shipping-input" placeholder="SHP-XXXXXX">
                </div>
                <button type="button" class="shipping-btn" style="width: auto; height: 45px;" onclick="BillingController.importShipment()">استيراد البيانات</button>
            </div>

            <form id="shipping-invoice-form">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:20px;">
                    <div class="shipping-form-group">
                        <label>العميل:</label>
                        <select name="customer_id" id="invoice-customer-id" class="shipping-select" required>
                            <option value="">اختر العميل...</option>
                            <?php
                            $customers = $wpdb->get_results("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM {$wpdb->prefix}shipping_customers");
                            foreach($customers as $c) echo "<option value='{$c->id}'>".esc_html($c->name)."</option>";
                            ?>
                        </select>
                    </div>
                    <div class="shipping-form-group"><label>تاريخ الاستحقاق:</label><input type="date" name="due_date" class="shipping-input" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required></div>
                </div>

                <div id="invoice-items-container">
                    <h5 style="margin-bottom:15px; display: flex; justify-content: space-between;">
                        بنود الفاتورة:
                        <span id="invoice-shipment-ref" style="font-weight: normal; color: #718096;"></span>
                    </h5>
                    <!-- Items injected here -->
                </div>
                <button type="button" class="shipping-btn shipping-btn-outline" onclick="BillingController.addInvoiceRow()" style="width:auto; margin-bottom:20px;">+ إضافة بند يدوي</button>

                <div style="background:#f8fafc; padding:20px; border-radius:12px; margin-top:20px; border: 1px solid #e2e8f0;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px;"><span>المجموع الفرعي:</span><strong id="invoice-subtotal">0.00</strong></div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px;"><span>الضريبة (15%):</span><strong id="invoice-tax">0.00</strong></div>
                    <div style="display:flex; justify-content:space-between; border-top:2px solid #fff; padding-top:10px; margin-top: 10px; font-size:1.4em; color: var(--shipping-primary-color);"><span>الإجمالي النهائي:</span><strong id="invoice-total">0.00</strong></div>
                </div>

                <div style="margin-top:20px; display: flex; gap: 20px; align-items: center;">
                    <label><input type="checkbox" name="is_recurring" value="1"> فاتورة متكررة</label>
                    <select name="billing_interval" class="shipping-select" style="width:auto;">
                        <option value="monthly">شهرياً</option>
                        <option value="yearly">سنوياً</option>
                    </select>
                </div>

                <button type="submit" class="shipping-btn" style="margin-top:25px; height:55px; font-weight:800; font-size: 1.1em;">إصدار وحفظ الفاتورة</button>
            </form>
        </div>

        <div class="shipping-card" style="background: #f0f4f8;">
            <h4>معاينة سريعة</h4>
            <div id="invoice-preview-area" style="background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); min-height: 400px; position: relative;">
                <div style="text-align: center; margin-bottom: 30px;">
                    <h2 style="margin: 0; color: #2d3748;">فاتورة ضريبية</h2>
                    <div style="font-size: 12px; color: #718096; margin-top: 5px;">INVOICE DRAFT</div>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 40px; font-size: 13px;">
                    <div>
                        <strong>مُصدر الفاتورة:</strong><br>
                        <?php echo esc_html($shipping['shipping_name']); ?><br>
                        <?php echo esc_html($shipping['address']); ?>
                    </div>
                    <div style="text-align: left;">
                        <strong>التاريخ:</strong> <?php echo date('Y-m-d'); ?><br>
                        <strong>رقم المسودة:</strong> #TEMP-<?php echo time(); ?>
                    </div>
                </div>
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="background: #edf2f7; text-align: right;">
                            <th style="padding: 10px;">الوصف</th>
                            <th style="padding: 10px; text-align: center;">الكمية</th>
                            <th style="padding: 10px; text-align: left;">السعر</th>
                        </tr>
                    </thead>
                    <tbody id="preview-items-body">
                        <tr><td colspan="3" style="text-align: center; padding: 40px; color: #a0aec0;">أضف بنوداً لعرض المعاينة</td></tr>
                    </tbody>
                </table>
                <div style="position: absolute; bottom: 30px; left: 30px; right: 30px; border-top: 2px solid #edf2f7; padding-top: 15px;">
                    <div style="display: flex; justify-content: space-between; font-weight: 800; font-size: 16px;">
                        <span>الإجمالي المستحق:</span>
                        <span id="preview-total">0.00 <?php echo esc_html($currency); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 3. Receivables Tracking -->
<div id="billing-balances" class="shipping-internal-tab" style="display: <?php echo $sub == 'balances' ? 'block' : 'none'; ?>;">
    <?php
    $receivables = Shipping_DB::get_receivables();
    ?>
    <div class="shipping-card">
        <h4>الأرصدة المستحقة (الحسابات المدينة)</h4>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead><tr><th>رقم الفاتورة</th><th>العميل</th><th>المبلغ</th><th>تاريخ الاستحقاق</th><th>الحالة</th><th>إجراءات</th></tr></thead>
                <tbody>
                    <?php if(empty($receivables)): ?>
                        <tr><td colspan="6" style="text-align:center; padding:20px;">لا توجد مديونيات حالياً.</td></tr>
                    <?php else: foreach($receivables as $inv): ?>
                        <tr>
                            <td><strong><?php echo $inv->invoice_number; ?></strong></td>
                            <td><?php echo esc_html($inv->customer_name); ?></td>
                            <td><?php echo number_format($inv->total_amount, 2); ?> <?php echo esc_html($currency); ?></td>
                            <td style="color:<?php echo (strtotime($inv->due_date) < time()) ? '#e53e3e' : 'inherit'; ?>"><?php echo $inv->due_date; ?></td>
                            <td><span class="shipping-badge shipping-badge-low"><?php echo $inv->status; ?></span></td>
                            <td><button class="shipping-btn shipping-btn-outline" style="padding:5px 10px;" onclick="BillingController.openPaymentModal(<?php echo htmlspecialchars(json_encode($inv)); ?>)">تسجيل دفع</button></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 5. Advanced Shipping Calculator -->
<div id="pricing-calc" class="shipping-internal-tab" style="display: <?php echo $sub == 'calculator' ? 'block' : 'none'; ?>;">
    <div class="shipping-grid" style="grid-template-columns: 1fr 1fr;">
        <div class="shipping-card">
            <h4 style="margin-top:0; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">حاسبة التكلفة التقديرية</h4>
            <form id="shipping-calculator-form-direct">
                <div class="shipping-form-group">
                    <label>الوزن (كجم)</label>
                    <input type="number" step="0.1" name="weight" class="shipping-input" placeholder="0.0" required>
                </div>
                <div class="shipping-grid" style="grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                    <div class="shipping-form-group"><label>الطول (سم)</label><input type="number" name="length" class="shipping-input" placeholder="0"></div>
                    <div class="shipping-form-group"><label>العرض (سم)</label><input type="number" name="width" class="shipping-input" placeholder="0"></div>
                    <div class="shipping-form-group"><label>الارتفاع (سم)</label><input type="number" name="height" class="shipping-input" placeholder="0"></div>
                </div>
                <div class="shipping-form-group">
                    <label>المسافة (كم)</label>
                    <input type="number" name="distance" class="shipping-input" placeholder="0" required>
                </div>
                <div class="shipping-form-group">
                    <label>خيار السرعة</label>
                    <select name="is_urgent" class="shipping-input">
                        <option value="0">شحن عادي</option>
                        <option value="1">شحن مستعجل (+)</option>
                    </select>
                </div>
                <button type="submit" class="shipping-btn" style="width: 100%; height: 50px; font-size: 1.1em;">حساب التكلفة التقديرية</button>
            </form>
        </div>

        <div id="calc-results-direct" class="shipping-card" style="display: none; background: #f0fdf4; border: 2px solid #bbf7d0;">
            <h4 style="margin-top:0; color: #166534;">تحليل التكلفة المتوقعة</h4>
            <div id="cost-breakdown-direct" style="margin-bottom: 20px;"></div>
            <div style="text-align: center; padding: 20px; background: #fff; border-radius: 10px; border: 1px dashed #38a169;">
                <span style="font-size: 0.9em; color: #666; display: block; margin-bottom: 5px;">إجمالي التكلفة التقديرية</span>
                <span id="estimated-total-direct" style="font-size: 2.5em; font-weight: 900; color: #2f855a;">0.00</span>
                <span style="font-size: 1.1em; font-weight: 700; color: #2f855a; margin-right: 5px;"><?php echo esc_html($currency); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- 6. Pricing Rules -->
<div id="pricing-rules" class="shipping-internal-tab" style="display: <?php echo $sub == 'pricing-rules' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h4>قواعد تسعير الشحن النشطة</h4>
            <button class="shipping-btn" style="width:auto;" onclick="ShippingModal.open('modal-add-rule-direct')">+ إضافة قاعدة جديدة</button>
        </div>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead>
                    <tr>
                        <th>اسم الخدمة</th>
                        <th>السعر الأساسي</th>
                        <th>سعر الكجم</th>
                        <th>الحد الأدنى</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody id="rules-table-direct">
                    <!-- Data via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Rule Modal -->
<div id="modal-add-rule-direct" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 500px;">
        <div class="shipping-modal-header">
            <h3>إضافة قاعدة تسعير</h3>
            <button class="shipping-modal-close" onclick="ShippingModal.close('modal-add-rule-direct')">&times;</button>
        </div>
        <form id="form-rule-direct">
            <input type="hidden" name="action" value="shipping_add_pricing">
            <?php wp_nonce_field('shipping_pricing_action', 'nonce'); ?>
            <div class="shipping-modal-body">
                <div class="shipping-form-group"><label>اسم الخدمة:</label><input type="text" name="name" class="shipping-input" required></div>
                <div class="shipping-form-group"><label>التكلفة الأساسية (<?php echo esc_html($currency); ?>):</label><input type="number" step="0.01" name="base_cost" class="shipping-input" required></div>
                <div class="shipping-form-group"><label>تكلفة الكجم (<?php echo esc_html($currency); ?>):</label><input type="number" step="0.01" name="cost_per_kg" class="shipping-input" required></div>
                <div class="shipping-form-group"><label>تكلفة الكم (<?php echo esc_html($currency); ?>):</label><input type="number" step="0.01" name="cost_per_km" class="shipping-input" required></div>
                <div class="shipping-form-group"><label>الحد الأدنى (<?php echo esc_html($currency); ?>):</label><input type="number" step="0.01" name="min_cost" class="shipping-input" value="0"></div>
            </div>
            <div class="shipping-modal-footer"><button type="submit" class="shipping-btn">حفظ القاعدة</button></div>
        </form>
    </div>
</div>

<!-- 7. Additional Fees -->
<div id="pricing-extra" class="shipping-internal-tab" style="display: <?php echo $sub == 'extra-charges' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h4>الرسوم والخدمات الإضافية</h4>
            <button class="shipping-btn" style="width:auto; background:#38a169;" onclick="ShippingModal.open('modal-add-fee-direct')">+ إضافة رسم جديد</button>
        </div>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead>
                    <tr>
                        <th>اسم الرسم</th>
                        <th>القيمة</th>
                        <th>النوع</th>
                        <th>التطبيق التلقائي</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody id="fees-table-direct">
                    <!-- Data via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Fee Modal -->
<div id="modal-add-fee-direct" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 450px;">
        <div class="shipping-modal-header">
            <h3>إضافة رسم إضافي</h3>
            <button class="shipping-modal-close" onclick="ShippingModal.close('modal-add-fee-direct')">&times;</button>
        </div>
        <form id="form-fee-direct">
            <input type="hidden" name="action" value="shipping_add_additional_fee">
            <?php wp_nonce_field('shipping_pricing_action', 'nonce'); ?>
            <div class="shipping-modal-body">
                <div class="shipping-form-group"><label>اسم الرسم:</label><input type="text" name="fee_name" class="shipping-input" required></div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div class="shipping-form-group"><label>القيمة:</label><input type="number" step="0.01" name="fee_value" class="shipping-input" required></div>
                    <div class="shipping-form-group">
                        <label>النوع:</label>
                        <select name="fee_type" class="shipping-select">
                            <option value="fixed">مبلغ ثابت</option>
                            <option value="percentage">نسبة %</option>
                        </select>
                    </div>
                </div>
                <div class="shipping-form-group"><label><input type="checkbox" name="is_automatic" value="1"> تطبيق تلقائي</label></div>
            </div>
            <div class="shipping-modal-footer"><button type="submit" class="shipping-btn">حفظ الرسم</button></div>
        </form>
    </div>
</div>

<!-- 4. Financial Reporting -->
<div id="billing-reports" class="shipping-internal-tab" style="display: <?php echo $sub == 'reports' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>التقارير المالية وتحليل الإيرادات</h4>
        <div style="height:300px; margin-top:20px;">
            <canvas id="revenueChart"></canvas>
        </div>
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-top:30px;">
            <div style="background:#f0fff4; padding:20px; border-radius:12px; text-align:center; border:1px solid #c6f6d5;">
                <h5 style="margin-top:0;">إيرادات اليوم</h5>
                <div style="font-size:2em; font-weight:800; color:#2f855a;" id="today-revenue">0.00 <?php echo esc_html($currency); ?></div>
            </div>
            <div style="background:#ebf8ff; padding:20px; border-radius:12px; text-align:center; border:1px solid #bee3f8;">
                <h5 style="margin-top:0;">إيرادات الشهر الحالي</h5>
                <div style="font-size:2em; font-weight:800; color:#2b6cb0;" id="month-revenue">0.00 <?php echo esc_html($currency); ?></div>
            </div>
        </div>
    </div>
</div>

<div id="payment-modal" class="shipping-modal-overlay">
    <div class="shipping-modal-content">
        <div class="shipping-modal-header"><h3>تسجيل عملية دفع</h3><button class="shipping-modal-close" onclick="ShippingModal.close('payment-modal')">&times;</button></div>
        <form id="shipping-payment-form" style="padding:20px;">
            <input type="hidden" name="invoice_id" id="pay-inv-id">
            <div class="shipping-form-group"><label>المبلغ المدفوع:</label><input type="number" step="0.01" name="amount_paid" id="pay-amount" class="shipping-input" required></div>
            <div class="shipping-form-group">
                <label>وسيلة الدفع:</label>
                <select name="payment_method" class="shipping-select">
                    <option value="cash">نقدي</option>
                    <option value="bank">تحويل بنكي</option>
                    <option value="online">دفع إلكتروني (بوابة دفع)</option>
                </select>
            </div>
            <button type="submit" class="shipping-btn" style="width:100%;">تأكيد عملية الدفع</button>
        </form>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', () => {
    BillingController.init();
});
</script>
