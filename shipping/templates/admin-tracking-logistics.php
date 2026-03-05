<?php if (!defined('ABSPATH')) exit;
$sub = $_GET['sub'] ?? 'live-tracking';
?>
<div class="shipping-admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <div class="shipping-tabs-wrapper" style="display: flex; gap: 10px; border-bottom: none; overflow-x: auto; white-space: nowrap; padding-bottom: 10px; margin-bottom: 0; margin-top: 0;">
        <button class="shipping-tab-btn <?php echo $sub == 'live-tracking' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('logistic-live', this)">تتبع مباشر</button>
        <button class="shipping-tab-btn <?php echo $sub == 'routes' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('logistic-routes', this)">مسارات الشحن</button>
        <button class="shipping-tab-btn <?php echo $sub == 'stop-points' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('logistic-stops', this)">نقاط التوقف</button>
        <button class="shipping-tab-btn <?php echo $sub == 'warehouse' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('logistic-warehouse', this)">المستودعات</button>
        <button class="shipping-tab-btn <?php echo $sub == 'fleet' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('logistic-fleet', this)">الأسطول</button>
        <button class="shipping-tab-btn <?php echo $sub == 'analytics' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('logistic-analytics', this)">التحليلات والتقارير</button>
    </div>
</div>

<!-- Professional Search Engine for Logistics (contextual) -->
<div class="shipping-search-engine-block" id="logistics-search-block" style="display: none;">
    <form id="logistics-advanced-search" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 15px; align-items: end;">
        <div class="shipping-form-group" style="margin-bottom:0;">
            <label id="logistics-search-label" style="font-size: 12px; font-weight: 700; color: #64748b;">بحث شامل:</label>
            <input type="text" id="logistics-search-query" class="shipping-input" placeholder="أدخل بيانات البحث..." oninput="LogisticsController.filterLogistics()">
        </div>
        <div class="shipping-form-group" style="margin-bottom:0;">
            <label style="font-size: 12px; font-weight: 700; color: #64748b;">ترتيب حسب:</label>
            <select id="logistics-sort-order" class="shipping-select" onchange="LogisticsController.filterLogistics()">
                <option value="newest">الأحدث أولاً</option>
                <option value="oldest">الأقدم أولاً</option>
                <option value="name_asc">الاسم (أ-ي)</option>
            </select>
        </div>
        <button type="button" onclick="LogisticsController.resetFilters()" class="shipping-btn shipping-btn-outline" style="height: 45px; width: auto;">إعادة ضبط</button>
    </form>
</div>

<div id="logistic-live" class="shipping-internal-tab" style="display: <?php echo $sub == 'live-tracking' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h4 style="margin: 0;">خريطة التتبع المباشر للشحنات</h4>
            <button class="shipping-btn" onclick="LogisticsController.loadActiveShipments()">تحديث الخريطة</button>
        </div>
        <div id="tracking-map" style="height: 500px; border-radius: 12px; border: 1px solid #eee;"></div>

        <div style="margin-top: 30px;">
            <h5>قائمة الشحنات النشطة</h5>
            <div class="shipping-table-container">
                <table class="shipping-table">
                    <thead>
                        <tr>
                            <th>رقم الشحنة</th>
                            <th>الحالة</th>
                            <th>الموقع الحالي</th>
                            <th>آخر تحديث</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="active-shipments-list">
                        <!-- Loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Update Location Modal -->
<div id="modal-update-location" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 400px;">
        <div class="shipping-modal-header">
            <h3>تحديث موقع الشحنة</h3>
            <button class="shipping-modal-close" onclick="ShippingModal.close('modal-update-location')">&times;</button>
        </div>
        <form id="form-update-location" style="padding: 20px;">
            <input type="hidden" name="action" value="shipping_update_shipment_location">
            <input type="hidden" name="id" id="update-shipment-id">
            <?php wp_nonce_field('shipping_shipment_action', 'nonce'); ?>
            <div class="shipping-form-group">
                <label>اسم الموقع (نصي)</label>
                <input type="text" name="location" class="shipping-input" placeholder="مثال: مستودع الإسكندرية" required>
            </div>
            <div class="shipping-form-group">
                <label>خط العرض (Lat)</label>
                <input type="number" name="lat" class="shipping-input" step="0.00000001" required>
            </div>
            <div class="shipping-form-group">
                <label>خط الطول (Lng)</label>
                <input type="number" name="lng" class="shipping-input" step="0.00000001" required>
            </div>
            <div class="shipping-form-group">
                <label>تحديث الحالة</label>
                <select name="status" class="shipping-select">
                    <option value="in-transit">قيد النقل</option>
                    <option value="out-for-delivery">خارج للتوصيل</option>
                    <option value="arrived-at-hub">وصل للمركز</option>
                    <option value="delayed">متأخر</option>
                        <option value="operational-issue">مشكلة تشغيلية</option>
                </select>
            </div>
            <button type="submit" class="shipping-btn" style="width: 100%;">تحديث الموقع</button>
        </form>
    </div>
</div>

<div id="logistic-routes" class="shipping-internal-tab" style="display: <?php echo $sub == 'routes' ? 'block' : 'none'; ?>;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h4 style="margin: 0;">تخطيط مسارات الشحن</h4>
        <button class="shipping-btn" onclick="LogisticsController.openRouteModal()">+ إضافة مسار جديد</button>
    </div>

    <div class="shipping-table-container">
        <table class="shipping-table">
            <thead>
                <tr>
                    <th>اسم المسار</th>
                    <th>من</th>
                    <th>إلى</th>
                    <th>المسافة</th>
                    <th>المدة المتوقعة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody id="route-list-body">
                <!-- Routes will be loaded here -->
            </tbody>
        </table>
    </div>
</div>

<div id="logistic-stops" class="shipping-internal-tab" style="display: <?php echo $sub == 'stop-points' ? 'block' : 'none'; ?>;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h4 style="margin: 0;">إدارة نقاط التوقف للمسار: <span id="selected-route-stops-name">الرجاء اختيار مسار</span></h4>
        <button class="shipping-btn" id="btn-add-stop" onclick="LogisticsController.openStopModal()" disabled>+ إضافة نقطة توقف</button>
    </div>

    <div class="shipping-table-container">
        <table class="shipping-table">
            <thead>
                <tr>
                    <th>الترتيب</th>
                    <th>اسم النقطة</th>
                    <th>الموقع</th>
                    <th>الإحداثيات</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody id="stops-list-body">
                <tr><td colspan="5" style="text-align:center;">اختر مساراً من تبويب المسارات لعرض نقاط التوقف.</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Route Modal -->
<div id="modal-route" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 500px;">
        <div class="shipping-modal-header">
            <h3 id="route-modal-title">إضافة مسار</h3>
            <button class="shipping-modal-close" onclick="ShippingModal.close('modal-route')">&times;</button>
        </div>
        <div class="shipping-modal-body">
            <form id="form-route" style="padding: 20px;">
                <input type="hidden" name="action" value="shipping_add_route">
                <input type="hidden" name="id" id="route-id">
                <?php wp_nonce_field('shipping_logistic_action', 'nonce'); ?>
                <div class="shipping-form-group">
                    <label>اسم المسار</label>
                    <input type="text" name="route_name" class="shipping-input" required>
                </div>
                <div class="shipping-form-group">
                    <label>نقطة البداية</label>
                    <input type="text" name="start_location" class="shipping-input" required>
                </div>
                <div class="shipping-form-group">
                    <label>نقطة النهاية</label>
                    <input type="text" name="end_location" class="shipping-input" required>
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div class="shipping-form-group">
                        <label>المسافة (كم)</label>
                        <input type="number" name="total_distance" class="shipping-input" step="0.1">
                    </div>
                    <div class="shipping-form-group">
                        <label>المدة المتوقعة</label>
                        <input type="text" name="estimated_duration" class="shipping-input" placeholder="مثال: 5 ساعات">
                    </div>
                </div>
                <div class="shipping-form-group">
                    <label>وصف المسار</label>
                    <textarea name="description" class="shipping-textarea" rows="3"></textarea>
                </div>
                <button type="submit" class="shipping-btn" style="width: 100%;">حفظ المسار</button>
            </form>
        </div>
    </div>
</div>

<!-- Stop Modal -->
<div id="modal-stop" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 500px;">
        <div class="shipping-modal-header">
            <h3 id="stop-modal-title">إضافة نقطة توقف</h3>
            <button class="shipping-modal-close" onclick="ShippingModal.close('modal-stop')">&times;</button>
        </div>
        <div class="shipping-modal-body">
            <form id="form-stop" style="padding: 20px;">
                <input type="hidden" name="action" value="shipping_add_route_stop">
                <input type="hidden" name="id" id="stop-id">
                <input type="hidden" name="route_id" id="stop-route-id">
                <?php wp_nonce_field('shipping_logistic_action', 'nonce'); ?>
                <div class="shipping-form-group">
                    <label>اسم النقطة</label>
                    <input type="text" name="stop_name" class="shipping-input" required>
                </div>
                <div class="shipping-form-group">
                    <label>الموقع الوصفي</label>
                    <input type="text" name="location" class="shipping-input">
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div class="shipping-form-group">
                        <label>خط العرض (Lat)</label>
                        <input type="number" name="lat" class="shipping-input" step="0.00000001">
                    </div>
                    <div class="shipping-form-group">
                        <label>خط الطول (Lng)</label>
                        <input type="number" name="lng" class="shipping-input" step="0.00000001">
                    </div>
                </div>
                <div class="shipping-form-group">
                    <label>الترتيب في المسار</label>
                    <input type="number" name="stop_order" class="shipping-input" value="1">
                </div>
                <button type="submit" class="shipping-btn" style="width: 100%;">حفظ النقطة</button>
            </form>
        </div>
    </div>
</div>

<div id="logistic-warehouse" class="shipping-internal-tab" style="display: <?php echo $sub == 'warehouse' ? 'block' : 'none'; ?>;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h4 style="margin: 0;">إدارة المستودعات والتخزين</h4>
        <button class="shipping-btn" onclick="LogisticsController.openWarehouseModal()">+ إضافة مستودع جديد</button>
    </div>

    <div id="warehouse-list-container" class="shipping-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
        <!-- Warehouses will be loaded here via AJAX -->
    </div>

    <div id="inventory-section" style="display: none; margin-top: 40px; border-top: 2px solid #eee; padding-top: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h4 style="margin: 0;">مخزون مستودع: <span id="selected-warehouse-name"></span></h4>
            <button class="shipping-btn" onclick="LogisticsController.openInventoryModal()">+ إضافة صنف للمخزون</button>
        </div>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead>
                    <tr>
                        <th>اسم الصنف</th>
                        <th>SKU</th>
                        <th>الكمية</th>
                        <th>الوحدة</th>
                        <th>آخر تحديث</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody id="inventory-list-body">
                    <!-- Inventory items will be loaded here -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Warehouse Modal -->
<div id="modal-warehouse" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 500px;">
        <div class="shipping-modal-header">
            <h3 id="warehouse-modal-title">إضافة مستودع</h3>
            <button class="shipping-modal-close" onclick="ShippingModal.close('modal-warehouse')">&times;</button>
        </div>
        <div class="shipping-modal-body">
            <form id="form-warehouse" style="padding: 20px;">
                <input type="hidden" name="action" value="shipping_add_warehouse">
                <input type="hidden" name="id" id="warehouse-id">
                <?php wp_nonce_field('shipping_logistic_action', 'nonce'); ?>
                <div class="shipping-form-group">
                    <label>اسم المستودع</label>
                    <input type="text" name="name" class="shipping-input" required>
                </div>
                <div class="shipping-form-group">
                    <label>الموقع</label>
                    <input type="text" name="location" class="shipping-input" required>
                </div>
                <div class="shipping-form-group">
                    <label>السعة الإجمالية (متر مكعب)</label>
                    <input type="number" name="total_capacity" class="shipping-input" step="0.1" required>
                </div>
                <div class="shipping-form-group">
                    <label>اسم المدير</label>
                    <input type="text" name="manager_name" class="shipping-input">
                </div>
                <div class="shipping-form-group">
                    <label>رقم التواصل</label>
                    <input type="text" name="contact_number" class="shipping-input">
                </div>
                <button type="submit" class="shipping-btn" style="width: 100%;">حفظ البيانات</button>
            </form>
        </div>
    </div>
</div>

<!-- Inventory Modal -->
<div id="modal-inventory" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 500px;">
        <div class="shipping-modal-header">
            <h3 id="inventory-modal-title">إضافة صنف</h3>
            <button class="shipping-modal-close" onclick="ShippingModal.close('modal-inventory')">&times;</button>
        </div>
        <div class="shipping-modal-body">
            <form id="form-inventory" style="padding: 20px;">
                <input type="hidden" name="action" value="shipping_add_inventory_item">
                <input type="hidden" name="id" id="inventory-id">
                <input type="hidden" name="warehouse_id" id="inventory-warehouse-id">
                <?php wp_nonce_field('shipping_logistic_action', 'nonce'); ?>
                <div class="shipping-form-group">
                    <label>اسم الصنف</label>
                    <input type="text" name="item_name" class="shipping-input" required>
                </div>
                <div class="shipping-form-group">
                    <label>SKU (رمز الصنف)</label>
                    <input type="text" name="sku" class="shipping-input">
                </div>
                <div class="shipping-form-group">
                    <label>الكمية</label>
                    <input type="number" name="quantity" class="shipping-input" required>
                </div>
                <div class="shipping-form-group">
                    <label>الوحدة</label>
                    <input type="text" name="unit" class="shipping-input" placeholder="قطعة، كجم، إلخ">
                </div>
                <button type="submit" class="shipping-btn" style="width: 100%;">حفظ الصنف</button>
            </form>
        </div>
    </div>
</div>

<div id="logistic-analytics" class="shipping-internal-tab" style="display: <?php echo $sub == 'analytics' ? 'block' : 'none'; ?>;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h4 style="margin: 0;">تحليلات الأداء والخدمات اللوجستية</h4>
        <button class="shipping-btn" onclick="LogisticsController.loadAnalytics()">تحديث البيانات</button>
    </div>

    <div class="shipping-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
        <div class="shipping-card">
            <h5>حالة الشحنات النشطة</h5>
            <canvas id="chart-shipment-status" height="200"></canvas>
        </div>
        <div class="shipping-card">
            <h5>حالة الأسطول والمركبات</h5>
            <canvas id="chart-fleet-status" height="200"></canvas>
        </div>
        <div class="shipping-card">
            <h5>نسبة إشغال المستودعات</h5>
            <canvas id="chart-warehouse-utilization" height="200"></canvas>
        </div>
        <div class="shipping-card">
            <h5>تكاليف صيانة الأسطول</h5>
            <div style="text-align: center; padding: 40px 0;">
                <div style="font-size: 14px; color: #666;">إجمالي التكاليف المسجلة</div>
                <div style="font-size: 32px; font-weight: 800; color: var(--shipping-primary-color);" id="total-maintenance-cost">0.00 <?php echo esc_html($currency); ?></div>
            </div>
        </div>
    </div>

    <div class="shipping-card" style="margin-top: 30px;">
        <h5>سجل التتبع التاريخي (Historical Tracking)</h5>
        <div class="shipping-form-group" style="display: flex; gap: 10px; align-items: center;">
            <input type="text" id="history-shipment-number" class="shipping-input" placeholder="أدخل رقم الشحنة (مثلاً: SHP-XXXX)">
            <button class="shipping-btn" onclick="LogisticsController.searchHistory()">بحث</button>
        </div>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead>
                    <tr>
                        <th>الوقت</th>
                        <th>الحالة</th>
                        <th>الموقع</th>
                        <th>الوصف</th>
                    </tr>
                </thead>
                <tbody id="history-list-body">
                    <tr><td colspan="4" style="text-align:center;">أدخل رقم الشحنة لعرض السجل التاريخي.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="logistic-fleet" class="shipping-internal-tab" style="display: <?php echo $sub == 'fleet' ? 'block' : 'none'; ?>;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h4 style="margin: 0;">إدارة الأسطول والمركبات</h4>
        <button class="shipping-btn" onclick="LogisticsController.openVehicleModal()">+ إضافة مركبة جديدة</button>
    </div>

    <div id="fleet-list-container" class="shipping-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
        <!-- Vehicles will be loaded here via AJAX -->
    </div>

    <div id="vehicle-details-section" style="display: none; margin-top: 40px; border-top: 2px solid #eee; padding-top: 30px;">
        <div class="shipping-tabs-wrapper" style="display: flex; gap: 10px; border-bottom: 1px solid #eee; margin-bottom: 20px;">
            <button class="shipping-tab-btn shipping-active" onclick="LogisticsController.openSubTab('maintenance', this)">سجل الصيانة</button>
            <button class="shipping-tab-btn" onclick="LogisticsController.openSubTab('shipments', this)">الشحنات المرتبطة</button>
        </div>

        <div id="vehicle-maintenance-tab" class="vehicle-sub-tab">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h4 style="margin: 0;">سجل الصيانة للمركبة: <span id="selected-vehicle-number"></span></h4>
                <button class="shipping-btn" onclick="LogisticsController.openMaintenanceModal()">+ إضافة سجل صيانة</button>
            </div>
            <div class="shipping-table-container">
                <table class="shipping-table">
                <thead>
                    <tr>
                        <th>نوع الصيانة</th>
                        <th>التاريخ</th>
                        <th>التكلفة</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                    <tbody id="maintenance-list-body">
                        <!-- Maintenance logs will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>

        <div id="vehicle-shipments-tab" class="vehicle-sub-tab" style="display:none;">
            <h4>الشحنات النشطة لهذه المركبة</h4>
            <div class="shipping-table-container">
                <table class="shipping-table">
                    <thead>
                        <tr>
                            <th>رقم الشحنة</th>
                            <th>العميل</th>
                            <th>المسار</th>
                            <th>الحالة</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="vehicle-shipments-body">
                        <!-- Loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Vehicle Modal -->
<div id="modal-vehicle" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 500px;">
        <div class="shipping-modal-header">
            <h3 id="vehicle-modal-title">إضافة مركبة</h3>
            <button class="shipping-modal-close" onclick="ShippingModal.close('modal-vehicle')">&times;</button>
        </div>
        <div class="shipping-modal-body">
            <form id="form-vehicle" style="padding: 20px;">
                <input type="hidden" name="action" value="shipping_add_vehicle">
                <input type="hidden" name="id" id="vehicle-id">
                <?php wp_nonce_field('shipping_logistic_action', 'nonce'); ?>
                <div class="shipping-form-group">
                    <label>رقم المركبة</label>
                    <input type="text" name="vehicle_number" class="shipping-input" required>
                </div>
                <div class="shipping-form-group">
                    <label>نوع المركبة</label>
                    <input type="text" name="vehicle_type" class="shipping-input" placeholder="مثال: شاحنة نقل ثقيل" required>
                </div>
                <div class="shipping-form-group">
                    <label>الحمولة القصوى (طن)</label>
                    <input type="number" name="capacity" class="shipping-input" step="0.1" required>
                </div>
                <div class="shipping-form-group">
                    <label>اسم السائق</label>
                    <input type="text" name="driver_name" class="shipping-input">
                </div>
                <div class="shipping-form-group">
                    <label>رقم هاتف السائق</label>
                    <input type="text" name="driver_phone" class="shipping-input">
                </div>
                <div class="shipping-form-group">
                    <label>تاريخ الصيانة القادمة</label>
                    <input type="date" name="next_maintenance_date" class="shipping-input">
                </div>
                <button type="submit" class="shipping-btn" style="width: 100%;">حفظ البيانات</button>
            </form>
        </div>
    </div>
</div>

<!-- Maintenance Modal -->
<div id="modal-maintenance" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 500px;">
        <div class="shipping-modal-header">
            <h3 id="maintenance-modal-title">إضافة سجل صيانة</h3>
            <button class="shipping-modal-close" onclick="ShippingModal.close('modal-maintenance')">&times;</button>
        </div>
        <div class="shipping-modal-body">
            <form id="form-maintenance" style="padding: 20px;">
                <input type="hidden" name="action" value="shipping_add_maintenance_log">
                <input type="hidden" name="id" id="maintenance-id">
                <input type="hidden" name="vehicle_id" id="maintenance-vehicle-id">
                <?php wp_nonce_field('shipping_logistic_action', 'nonce'); ?>
                <div class="shipping-form-group">
                    <label>نوع الصيانة</label>
                    <input type="text" name="maintenance_type" class="shipping-input" placeholder="مثال: تغيير زيت، فحص دوري" required>
                </div>
                <div class="shipping-form-group">
                    <label>الوصف</label>
                    <textarea name="description" class="shipping-textarea" rows="3"></textarea>
                </div>
                <div class="shipping-form-group">
                    <label>التكلفة</label>
                    <input type="number" name="cost" class="shipping-input" step="0.01" required>
                </div>
                <div class="shipping-form-group">
                    <label>التاريخ</label>
                    <input type="date" name="maintenance_date" class="shipping-input" required>
                </div>
                <div class="shipping-form-group">
                    <label><input type="checkbox" name="completed" value="1"> تمت العملية</label>
                </div>
                <button type="submit" class="shipping-btn" style="width: 100%;">حفظ السجل</button>
            </form>
        </div>
    </div>
</div>

