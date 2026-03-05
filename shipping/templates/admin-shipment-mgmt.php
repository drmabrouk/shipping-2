<?php if (!defined('ABSPATH')) exit;
global $wpdb;
$sub = $_GET['sub'] ?? 'create-shipment';
?>
<div id="shipment-management-unified">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h3 style="margin:0;">إدارة الشحنات</h3>
        <div style="display: flex; gap: 10px;">
            <button onclick="ShippingModal.open('modal-bulk-shipments')" class="shipping-btn" style="width:auto; background: var(--shipping-secondary-color);">+ إدخال بالجملة</button>
            <button onclick="ShipmentsController.openCreationModal()" class="shipping-btn" style="width:auto;">+ إضافة شحنة جديدة</button>
        </div>
    </div>

    <!-- Professional Search & Filter Engine -->
    <div style="background: white; padding: 25px; border: 1px solid var(--shipping-border-color); border-radius: 12px; margin-bottom: 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <form id="shipment-search-form" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 15px; align-items: end;">
            <div class="shipping-form-group" style="margin-bottom:0;">
                <label style="font-size: 12px; font-weight: 700; color: #64748b;">بحث متقدم (رقم الشحنة، العميل، الموقع):</label>
                <input type="text" id="shipment-search-query" class="shipping-input" placeholder="أدخل بيانات البحث..." oninput="ShipmentsController.filterShipments()">
            </div>
            <div class="shipping-form-group" style="margin-bottom:0;">
                <label style="font-size: 12px; font-weight: 700; color: #64748b;">تصفية حسب الحالة:</label>
                <select id="shipment-filter-status" class="shipping-select" onchange="ShipmentsController.filterShipments()">
                    <option value="">كافة الحالات</option>
                    <option value="pending">قيد الانتظار</option>
                    <option value="in-transit">قيد النقل</option>
                    <option value="out-for-delivery">خارج للتوصيل</option>
                    <option value="delivered">تم التسليم</option>
                    <option value="delayed">متأخر</option>
                    <option value="cancelled">ملغاة</option>
                </select>
            </div>
            <div class="shipping-form-group" style="margin-bottom:0;">
                <label style="font-size: 12px; font-weight: 700; color: #64748b;">ترتيب حسب:</label>
                <select id="shipment-sort-order" class="shipping-select" onchange="ShipmentsController.filterShipments()">
                    <option value="newest">الأحدث أولاً</option>
                    <option value="oldest">الأقدم أولاً</option>
                    <option value="weight_desc">الوزن (الأعلى)</option>
                    <option value="weight_asc">الوزن (الأقل)</option>
                </select>
            </div>
            <button type="button" onclick="ShipmentsController.resetFilters()" class="shipping-btn shipping-btn-outline" style="height: 45px; width: auto;">إعادة ضبط</button>
        </form>
    </div>

    <div class="shipping-card" style="padding: 0; overflow: hidden;">
        <div class="shipping-table-container" style="margin: 0;">
            <table class="shipping-table" id="shipments-main-table">
                <thead>
                    <tr>
                        <th>رقم الشحنة</th>
                        <th>العميل</th>
                        <th>المسار والوجهة</th>
                        <th>البيانات الفنية</th>
                        <th>تاريخ الإنشاء</th>
                        <th>الحالة</th>
                        <th style="text-align: left;">إجراءات العمليات</th>
                    </tr>
                </thead>
                <tbody id="shipments-list-body">
                    <?php
                    $all_shipments = $wpdb->get_results("SELECT s.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name FROM {$wpdb->prefix}shipping_shipments s LEFT JOIN {$wpdb->prefix}shipping_customers c ON s.customer_id = c.id ORDER BY s.created_at DESC");
                    if(empty($all_shipments)): ?>
                        <tr><td colspan="7" style="text-align:center; padding:50px; color:#94a3b8;">لا توجد شحنات مسجلة حالياً في النظام.</td></tr>
                    <?php else: foreach($all_shipments as $s): ?>
                        <tr class="shipment-row" data-status="<?php echo $s->status; ?>" data-number="<?php echo $s->shipment_number; ?>" data-customer="<?php echo esc_attr($s->customer_name); ?>" data-created="<?php echo $s->created_at; ?>" data-weight="<?php echo $s->weight; ?>">
                            <td>
                                <div style="font-weight: 800; color: var(--shipping-primary-color); font-size: 1.1em;"><?php echo $s->shipment_number; ?></div>
                                <div style="font-size: 10px; color: #a0aec0; margin-top: 2px;">ID: #<?php echo $s->id; ?></div>
                            </td>
                            <td>
                                <div style="font-weight: 700;"><?php echo esc_html($s->customer_name); ?></div>
                                <div style="font-size: 11px; color: #718096;"><?php echo esc_html($s->origin); ?></div>
                            </td>
                            <td>
                                <div style="font-size: 12px;">➔ <strong><?php echo $s->destination; ?></strong></div>
                                <div style="font-size: 11px; color: #718096; margin-top: 3px;"><span class="dashicons dashicons-location-alt" style="font-size: 12px; width:12px; height:12px;"></span> <?php echo $s->location ?: 'قيد التحديث...'; ?></div>
                            </td>
                            <td>
                                <div style="font-size: 12px;">الوزن: <strong><?php echo $s->weight; ?> كجم</strong></div>
                                <div style="font-size: 11px; color: #718096;">التصنيف: <?php echo $s->classification; ?></div>
                            </td>
                            <td style="font-size: 12px; color: #4a5568;">
                                <?php echo date('Y-m-d', strtotime($s->created_at)); ?><br>
                                <small><?php echo date('H:i', strtotime($s->created_at)); ?></small>
                            </td>
                            <td>
                                <span class="pastel-badge status-<?php echo $s->status; ?>" style="padding: 6px 12px; font-size: 10px;"><?php echo strtoupper($s->status); ?></span>
                            </td>
                            <td>
                                <div style="display:flex; gap:6px; justify-content: flex-end;">
                                    <button class="shipping-btn-outline" style="padding:6px 10px; font-size:11px;" onclick="ShipmentsController.quickTrack('<?php echo $s->shipment_number; ?>', <?php echo $s->id; ?>, this)">تتبع</button>
                                    <button class="shipping-btn-outline" style="padding:6px 10px; font-size:11px; color: #319795; border-color: #319795;" onclick="ShipmentsController.viewFullDossier(<?php echo $s->id; ?>)">التفاصيل</button>
                                    <button class="shipping-btn-outline" style="padding:6px 10px; font-size:11px; color: #2d3748; border-color: #2d3748;" onclick="ShipmentsController.printSticker(<?php echo $s->id; ?>)">Sticker</button>
                                    <button class="shipping-btn-outline" style="padding:6px 10px; font-size:11px; color: #805ad5; border-color: #805ad5;" onclick="ShipmentsController.printInvoice(<?php echo $s->id; ?>)">الفاتورة</button>
                                    <button class="shipping-btn" style="padding:6px 10px; font-size:11px; background: #4a5568;" onclick="ShipmentsController.openEditModal(<?php echo htmlspecialchars(json_encode($s)); ?>)">تعديل</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

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

<!-- Unified Tracking Modal -->
<div id="modal-shipment-tracking-unified" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 700px;">
        <div class="shipping-modal-header">
            <h3>تتبع الشحنة المباشر</h3>
            <button class="shipping-modal-close" onclick="ShippingModal.close('modal-shipment-tracking-unified')">&times;</button>
        </div>
        <div id="tracking-result-unified" style="padding: 25px; background: #f8fafc;">
            <div style="display:flex; justify-content:space-between; margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:15px;">
                <div>
                    <h3 id="res-number-unified" style="margin:0; color:var(--shipping-primary-color);"></h3>
                    <div id="res-route-unified" style="font-size:13px; color:#64748b; margin-top:5px;"></div>
                </div>
                <span id="res-status-unified" class="shipping-badge" style="font-size:14px; padding:8px 15px;"></span>
            </div>
            <div id="res-timeline-unified" class="tracking-timeline" style="position:relative; padding-right:40px; margin-top:20px;">
                <!-- Timeline events will be injected here -->
            </div>
        </div>
    </div>
</div>

<!-- Bulk Entry Modal -->
<div id="modal-bulk-shipments" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 800px;">
        <div class="shipping-modal-header">
            <h3>إدخال الشحنات بالجملة (JSON Import)</h3>
            <button class="shipping-modal-close" onclick="ShippingModal.close('modal-bulk-shipments')">&times;</button>
        </div>
        <div class="shipping-modal-body" style="padding: 25px;">
            <p style="color:#64748b; font-size:13px; margin-bottom: 15px;">يرجى لصق بيانات الشحنات بتنسيق JSON المعياري للإدراج الجماعي في النظام.</p>
            <textarea id="bulk-rows-input" class="shipping-textarea" rows="12" placeholder='[{"shipment_number":"SHP-001", "customer_id":1, "origin":"الرياض", "destination":"دبي", "weight":10.5, "dimensions":"30x30x30", "classification":"express"}]'></textarea>
            <button class="shipping-btn" style="margin-top:20px; height: 50px; font-weight: 800;" onclick="ShipmentsController.processBulkDirect()">بدء المعالجة والإدراج الفوري</button>
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

<!-- Direct Invoice Modal -->
<div id="modal-shipment-invoice-print" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 850px;">
        <div class="shipping-modal-header">
            <h3>معاينة وطباعة فاتورة الشحنة</h3>
            <button class="shipping-modal-close" onclick="ShippingModal.close('modal-shipment-invoice-print')">&times;</button>
        </div>
        <div class="shipping-modal-body" id="invoice-print-content" style="padding: 30px; background: #f0f4f8;">
            <!-- Invoice Template Injected Here -->
        </div>
        <div class="shipping-modal-footer" style="background: #fff; border-top: 1px solid #eee; padding: 15px 25px; text-align: left;">
            <button class="shipping-btn" style="width: auto; padding: 0 30px;" onclick="ShipmentsController.executePrint()">طباعة الفاتورة الآن</button>
        </div>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', () => {
    ShipmentsController.init();
});
</script>

<style>
.pastel-badge {
    padding: 4px 12px; border-radius: 50px; font-size: 11px; font-weight: 700; display: inline-block;
}
.badge-intl { background: #e9d8fd; color: #553c9a; }
.badge-dom { background: #bee3f8; color: #2b6cb0; }

.status-pending { background: #fed7d7; color: #9b2c2c; }
.status-in-transit { background: #feebc8; color: #9c4221; }
.status-delivered { background: #c6f6d5; color: #22543d; }
.status-cancelled { background: #edf2f7; color: #4a5568; }

.spin { animation: spin 2s linear infinite; }
@keyframes spin { 100% { transform: rotate(360deg); } }

.tracking-timeline::before {
    content: ""; position: absolute; right: 8px; top: 0; bottom: 0; width: 2px; background: #e2e8f0;
}
.tracking-event { position: relative; padding-bottom: 20px; }
.tracking-event::after {
    content: ""; position: absolute; right: -25px; top: 5px; width: 12px; height: 12px; border-radius: 50%; background: #cbd5e0; border: 2px solid #fff;
}
.tracking-event.active::after { background: var(--shipping-primary-color); box-shadow: 0 0 0 4px rgba(246, 48, 73, 0.2); }
</style>
