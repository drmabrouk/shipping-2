<?php if (!defined('ABSPATH')) exit;
global $wpdb;
$sub = $_GET['sub'] ?? 'new-orders';
?>
<div class="shipping-admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <div class="shipping-tabs-wrapper" style="display: flex; gap: 15px; overflow-x: auto; white-space: nowrap; padding-bottom: 5px; margin-bottom: 0; border-bottom: none;">
        <button class="shipping-tab-btn <?php echo $sub == 'new-orders' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('order-new', this); OrdersController.loadOrders('new')">طلبات جديدة</button>
        <button class="shipping-tab-btn <?php echo $sub == 'in-progress' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('order-progress', this); OrdersController.loadOrders('in-progress')">قيد التنفيذ</button>
        <button class="shipping-tab-btn <?php echo $sub == 'completed' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('order-completed', this); OrdersController.loadOrders('completed')">مكتملة</button>
        <button class="shipping-tab-btn <?php echo $sub == 'cancelled' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('order-cancelled', this); OrdersController.loadOrders('cancelled')">ملغاة</button>
    </div>
    <div style="display: flex; gap: 10px;">
        <button class="shipping-btn" onclick="OrdersController.openAddModal()">+ طلب جديد</button>
    </div>
</div>

<!-- Professional Search Engine for Orders -->
<div class="shipping-search-engine-block">
    <form id="order-advanced-search" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 15px; align-items: end;">
        <div class="shipping-form-group" style="margin-bottom:0;">
            <label style="font-size: 12px; font-weight: 700; color: #64748b;">بحث شامل (رقم الطلب، العميل، العنوان):</label>
            <input type="text" id="order-search" class="shipping-input" placeholder="أدخل بيانات البحث..." oninput="OrdersController.debounceSearch()">
        </div>
        <div class="shipping-form-group" style="margin-bottom:0;">
            <label style="font-size: 12px; font-weight: 700; color: #64748b;">ترتيب حسب:</label>
            <select id="order-sort-order" class="shipping-select" onchange="OrdersController.loadOrders()">
                <option value="newest">الأحدث أولاً</option>
                <option value="oldest">الأقدم أولاً</option>
                <option value="amount_desc">المبلغ (الأعلى)</option>
                <option value="amount_asc">المبلغ (الأقل)</option>
            </select>
        </div>
        <button type="button" onclick="OrdersController.resetFilters()" class="shipping-btn shipping-btn-outline" style="height: 45px; width: auto;">إعادة ضبط</button>
    </form>
</div>

<div class="shipping-bulk-actions" id="order-bulk-bar" style="display: none; background: #f8fafc; padding: 15px 20px; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 20px; align-items: center; gap: 15px; animation: slideIn 0.3s ease;">
    <span style="font-weight: 700; color: #4a5568;">الإجراءات الجماعية (<span id="bulk-count">0</span>):</span>
    <select id="bulk-status" class="shipping-select" style="width: 180px;">
        <option value="">تغيير الحالة إلى...</option>
        <option value="new">جديد</option>
        <option value="in-progress">قيد التنفيذ</option>
        <option value="completed">مكتمل</option>
        <option value="cancelled">ملغى</option>
    </select>
    <button class="shipping-btn" onclick="OrdersController.applyBulkStatus()" style="width: auto;">تطبيق</button>
    <button class="shipping-btn shipping-btn-outline" onclick="OrdersController.clearBulkSelection()" style="width: auto;">إلغاء التحديد</button>
</div>

<!-- Tabs Content -->
<?php
$statuses = ['new' => 'order-new', 'in-progress' => 'order-progress', 'completed' => 'order-completed', 'cancelled' => 'order-cancelled'];
foreach($statuses as $status => $id): ?>
<div id="<?php echo $id; ?>" class="shipping-internal-tab" style="display: <?php echo $sub == $status ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead>
                    <tr>
                        <th style="width: 40px;"><input type="checkbox" onclick="OrdersController.toggleAll(this)"></th>
                        <th>رقم الطلب</th>
                        <th>العميل</th>
                        <th>المبلغ</th>
                        <th>المسار/العناوين</th>
                        <th>التاريخ</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody id="table-body-<?php echo $status; ?>">
                    <!-- Data loaded via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Modals -->
<div id="modal-add-order" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 650px;">
        <div class="shipping-modal-header">
            <h3>إنشاء طلب شحن جديد</h3>
            <button class="shipping-modal-close" onclick="ShippingModal.close('modal-add-order')">&times;</button>
        </div>
        <form id="form-add-order">
            <input type="hidden" name="action" value="shipping_add_order">
            <?php wp_nonce_field('shipping_order_action', 'nonce'); ?>
            <div class="shipping-modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="shipping-form-group">
                        <label>العميل</label>
                        <select name="customer_id" class="shipping-input" required>
                            <option value="">اختر العميل...</option>
                            <?php
                            $customers = $wpdb->get_results("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM {$wpdb->prefix}shipping_customers ORDER BY first_name ASC");
                            foreach($customers as $c) echo "<option value='{$c->id}'>".esc_html($c->name)."</option>";
                            ?>
                        </select>
                    </div>
                    <div class="shipping-form-group">
                        <label>المبلغ الإجمالي (<?php echo esc_html($currency); ?>)</label>
                        <input type="number" step="0.01" name="total_amount" class="shipping-input" placeholder="0.00" required>
                    </div>
                </div>
                <div class="shipping-form-group">
                    <label>عنوان الاستلام</label>
                    <textarea name="pickup_address" class="shipping-textarea" rows="2" placeholder="أدخل تفاصيل موقع الاستلام..." required></textarea>
                </div>
                <div class="shipping-form-group">
                    <label>عنوان التسليم</label>
                    <textarea name="delivery_address" class="shipping-textarea" rows="2" placeholder="أدخل تفاصيل موقع التسليم..." required></textarea>
                </div>
                <div class="shipping-form-group">
                    <label>تفاصيل الشحنة / ملاحظات</label>
                    <textarea name="order_details" class="shipping-textarea" rows="3" placeholder="محتويات الشحنة، متطلبات خاصة..."></textarea>
                </div>
            </div>
            <div class="shipping-modal-footer">
                <button type="submit" class="shipping-btn">تأكيد وإنشاء الطلب</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Order Modal -->
<div id="modal-edit-order" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 650px;">
        <div class="shipping-modal-header">
            <h3>تعديل طلب الشحن</h3>
            <button class="shipping-modal-close" onclick="ShippingModal.close('modal-edit-order')">&times;</button>
        </div>
        <form id="form-edit-order">
            <input type="hidden" name="id">
            <div class="shipping-modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="shipping-form-group">
                        <label>العميل</label>
                        <select name="customer_id" class="shipping-input" required>
                            <?php
                            $customers = $wpdb->get_results("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM {$wpdb->prefix}shipping_customers ORDER BY first_name ASC");
                            foreach($customers as $c) echo "<option value='{$c->id}'>".esc_html($c->name)."</option>";
                            ?>
                        </select>
                    </div>
                    <div class="shipping-form-group">
                        <label>المبلغ الإجمالي (<?php echo esc_html($currency); ?>)</label>
                        <input type="number" step="0.01" name="total_amount" class="shipping-input" required>
                    </div>
                </div>
                <div class="shipping-form-group">
                    <label>عنوان الاستلام</label>
                    <textarea name="pickup_address" class="shipping-textarea" rows="2" required></textarea>
                </div>
                <div class="shipping-form-group">
                    <label>عنوان التسليم</label>
                    <textarea name="delivery_address" class="shipping-textarea" rows="2" required></textarea>
                </div>
                <div class="shipping-form-group">
                    <label>تفاصيل الشحنة / ملاحظات</label>
                    <textarea name="order_details" class="shipping-textarea" rows="3"></textarea>
                </div>
            </div>
            <div class="shipping-modal-footer">
                <button type="submit" class="shipping-btn">حفظ التعديلات</button>
            </div>
        </form>
    </div>
</div>

<!-- Order Logs Modal -->
<div id="modal-order-logs" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 600px;">
        <div class="shipping-modal-header">
            <h3>سجل تتبع الطلب: <span id="log-order-num"></span></h3>
            <button class="shipping-modal-close" onclick="ShippingModal.close('modal-order-logs')">&times;</button>
        </div>
        <div class="shipping-modal-body">
            <div id="order-logs-timeline" class="shipping-timeline">
                <!-- Logs loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', () => {
    OrdersController.currentStatus = '<?php echo ($sub == 'new-orders' ? 'new' : $sub); ?>';
    OrdersController.loadOrders();
});
</script>

<style>
.spin { animation: spin 2s linear infinite; }
@keyframes spin { 100% { transform: rotate(360deg); } }
.truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.shipping-btn-icon {
    background: none; border: none; cursor: pointer; font-size: 16px; padding: 6px; border-radius: 6px; transition: 0.2s;
    background: #f1f5f9; color: #475569; display: flex; align-items: center; justify-content: center;
}
.shipping-btn-icon:hover { transform: translateY(-2px); filter: brightness(0.9); }
@keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
</style>
