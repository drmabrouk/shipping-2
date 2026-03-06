<?php if (!defined('ABSPATH')) exit;
global $wpdb;
$sub = $_GET['sub'] ?? 'create-shipment';
?>
<div id="shipment-management-unified">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h3 style="margin:0;">إدارة الشحنات</h3>
        <div style="display: flex; gap: 10px;">
            <a href="<?php echo admin_url('admin-ajax.php?action=shipping_export_csv&type=shipments&nonce=' . wp_create_nonce('shipping_export_nonce')); ?>" class="shipping-btn" style="width:auto; background: #2f855a; text-decoration:none;">تصدير CSV</a>
            <button onclick="ShippingModal.open('modal-bulk-shipments')" class="shipping-btn" style="width:auto; background: var(--shipping-secondary-color);">+ إدخال بالجملة</button>
            <button onclick="ShipmentsController.openCreationModal()" class="shipping-btn" style="width:auto;">+ إضافة شحنة جديدة</button>
        </div>
    </div>

    <!-- Professional Search & Filter Engine -->
    <div class="shipping-search-engine-block">
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
                                    <button class="shipping-btn-outline" style="padding:6px 10px; font-size:11px; color: #2d3748; border-color: #2d3748;" onclick="ShipmentsController.printSticker(<?php echo $s->id; ?>)">ملصق الشحنة</button>
                                    <button class="shipping-btn-outline" style="padding:6px 10px; font-size:11px; color: #805ad5; border-color: #805ad5;" onclick="ShipmentsController.printInvoice(<?php echo $s->id; ?>)">الفاتورة</button>
                                    <button class="shipping-btn" style="padding:6px 10px; font-size:11px; background: #4a5568;" onclick="ShipmentsController.openEditModal(<?php echo htmlspecialchars(json_encode($s)); ?>)">تعديل</button>
                                    <button class="shipping-btn" style="padding:6px 10px; font-size:11px; background: #e53e3e;" onclick="ShipmentsController.deleteShipment(<?php echo $s->id; ?>, '<?php echo $s->shipment_number; ?>')">حذف</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
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
.tracking-timeline::before {
    content: ""; position: absolute; right: 8px; top: 0; bottom: 0; width: 2px; background: #e2e8f0;
}
.tracking-event { position: relative; padding-bottom: 20px; }
.tracking-event::after {
    content: ""; position: absolute; right: -25px; top: 5px; width: 12px; height: 12px; border-radius: 50%; background: #cbd5e0; border: 2px solid #fff;
}
.tracking-event.active::after { background: var(--shipping-primary-color); box-shadow: 0 0 0 4px rgba(246, 48, 73, 0.2); }
</style>
