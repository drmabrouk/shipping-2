<?php if (!defined('ABSPATH')) exit;
global $wpdb;
$shipping = Shipping_Settings::get_shipping_info();
$currency = $shipping['currency'] ?? 'SAR';
?>

<!-- Creation Modal -->
<div id="modal-create-shipment" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 900px;">
        <div class="shipping-modal-header">
            <h3>إنشاء شحنة جديدة</h3>
            <button class="shipping-modal-close" onclick="ShippingModal.close('modal-create-shipment')">&times;</button>
        </div>
        <div class="shipping-modal-body">
            <div class="shipping-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px;">
                <form id="shipping-create-shipment-form" style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div class="shipping-form-group" style="grid-column: span 2;">
                        <label>العميل:</label>
                        <select name="customer_id" class="shipping-select" required>
                            <option value="">اختر العميل...</option>
                            <?php
                            $customers = $wpdb->get_results("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM {$wpdb->prefix}shipping_customers ORDER BY first_name ASC");
                            foreach($customers as $c) echo "<option value='{$c->id}'>".esc_html($c->name)."</option>";
                            ?>
                        </select>
                    </div>
                    <div class="shipping-form-group">
                        <label>دولة الانطلاق:</label>
                        <select name="origin_country" class="shipping-select origin-country-select" required>
                            <option value="">اختر الدولة...</option>
                            <option value="Saudi Arabia" selected>السعودية</option>
                            <option value="UAE">الإمارات</option>
                            <option value="Egypt">مصر</option>
                            <option value="Oman">عمان</option>
                            <option value="Qatar">قطر</option>
                            <option value="Jordan">الأردن</option>
                        </select>
                    </div>
                    <div class="shipping-form-group">
                        <label>مدينة الانطلاق:</label>
                        <select name="origin_city" class="shipping-select origin-city-select" required>
                            <option value="">اختر المدينة...</option>
                        </select>
                    </div>
                    <div class="shipping-form-group">
                        <label>دولة الوصول:</label>
                        <select name="destination_country" class="shipping-select destination-country-select" required>
                            <option value="">اختر الدولة...</option>
                            <option value="Saudi Arabia" selected>السعودية</option>
                            <option value="UAE">الإمارات</option>
                            <option value="Egypt">مصر</option>
                            <option value="Oman">عمان</option>
                            <option value="Qatar">قطر</option>
                            <option value="Jordan">الأردن</option>
                        </select>
                    </div>
                    <div class="shipping-form-group">
                        <label>مدينة الوصول:</label>
                        <select name="destination_city" class="shipping-select destination-city-select" required>
                            <option value="">اختر المدينة...</option>
                        </select>
                    </div>

                    <div class="shipping-form-group">
                        <label>الوزن (كجم):</label>
                        <input type="number" name="weight" id="shipment-weight" step="0.01" class="shipping-input" required>
                    </div>
                    <div class="shipping-form-group">
                        <label>المسافة التقريبية (كم):</label>
                        <input type="number" name="distance" id="shipment-distance" class="shipping-input" placeholder="0">
                    </div>

                    <div class="shipping-form-group"><label>الأبعاد (L x W x H):</label><input type="text" name="dimensions" class="shipping-input" placeholder="30x30x30"></div>

                    <div class="shipping-form-group">
                        <label>التصنيف:</label>
                        <select name="classification" id="shipment-classification" class="shipping-select">
                            <option value="standard">قياسي (Standard)</option>
                            <option value="express">سريع (Express)</option>
                            <option value="priority">أولوية (Priority)</option>
                            <option value="fragile">قابل للكسر (Fragile)</option>
                        </select>
                    </div>

                    <div class="shipping-form-group" style="grid-column: span 2; border-top: 1px solid #eee; padding-top: 10px; margin-top: 5px;">
                        <label style="font-weight: 700;">خيارات إضافية:</label>
                        <div style="display: flex; gap: 20px; margin-top: 10px;">
                            <label><input type="checkbox" name="is_urgent" value="1"> شحن مستعجل</label>
                            <label><input type="checkbox" name="is_insured" value="1"> تأمين الشحنة</label>
                        </div>
                    </div>

                    <div class="shipping-form-group"><label>تاريخ الاستلام:</label><input type="datetime-local" name="pickup_date" class="shipping-input"></div>
                    <div class="shipping-form-group"><label>تاريخ التسليم المتوقع:</label><input type="datetime-local" name="delivery_date" class="shipping-input"></div>

                    <div class="shipping-form-group">
                        <label>المركبة (Fleet):</label>
                        <select name="carrier_id" class="shipping-select">
                            <option value="0">غير محدد</option>
                            <?php
                            $fleet = $wpdb->get_results("SELECT id, vehicle_number FROM {$wpdb->prefix}shipping_fleet WHERE status = 'available'");
                            foreach($fleet as $v) echo "<option value='{$v->id}'>".esc_html($v->vehicle_number)."</option>";
                            ?>
                        </select>
                    </div>
                    <div class="shipping-form-group">
                        <label>المسار (Route):</label>
                        <select name="route_id" class="shipping-select">
                            <option value="0">اختر المسار...</option>
                            <?php
                            $routes = $wpdb->get_results("SELECT id, route_name FROM {$wpdb->prefix}shipping_logistics");
                            foreach($routes as $r) echo "<option value='{$r->id}'>".esc_html($r->route_name)."</option>";
                            ?>
                        </select>
                    </div>

                    <input type="hidden" name="order_id" id="shipment-order-id-input" value="">
                    <input type="hidden" name="estimated_cost" id="shipment-estimated-cost-input" value="0">
                    <button type="submit" class="shipping-btn" style="grid-column: span 2; height: 50px; font-weight: 800; margin-top: 10px;">تأكيد وإنشاء الشحنة</button>
                </form>

                <div class="shipping-card" id="realtime-cost-card" style="background: #f8fafc; border: 2px solid #e2e8f0; margin: 0;">
                    <h4 style="margin-top:0; color: #4a5568;">ملخص التكلفة</h4>
                    <div id="cost-loader" style="display: none; text-align: center; padding: 20px;">
                        <span class="dashicons dashicons-update spin" style="font-size: 30px; width: 30px; height: 30px;"></span>
                    </div>
                    <div id="cost-details">
                        <div style="text-align: center; padding: 20px; background: #fff; border-radius: 12px; border: 1px dashed #cbd5e0; margin-bottom: 20px;">
                            <div style="font-size: 0.8em; color: #718096;">التكلفة المتوقعة</div>
                            <div style="font-size: 2em; font-weight: 900; color: var(--shipping-primary-color);" id="display-cost">0.00</div>
                            <div style="font-weight: 700; color: #4a5568; font-size: 12px;"><?php echo esc_html($currency); ?></div>
                        </div>
                        <div id="cost-breakdown-list" style="font-size: 12px; color: #4a5568;">
                            <p style="text-align: center; opacity: 0.7;">أدخل بيانات الشحنة للحساب.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Full Dossier Modal -->
<div id="modal-full-dossier" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 900px;">
        <div class="shipping-modal-header" style="background: var(--shipping-dark-color); color: #fff;">
            <h3>ملف البيانات الموحد للشحنة: <span id="dossier-num"></span></h3>
            <button class="shipping-modal-close" onclick="ShippingModal.close('modal-full-dossier')" style="color:#fff;">&times;</button>
        </div>
        <div class="shipping-modal-body" id="dossier-content" style="padding: 25px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <!-- Content injected via JS -->
        </div>
    </div>
</div>
