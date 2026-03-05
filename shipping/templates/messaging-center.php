<?php if (!defined('ABSPATH')) exit; ?>
<?php
$my_id = get_current_user_id();
$user = wp_get_current_user();
$roles = (array)$user->roles;
$is_admin = in_array('administrator', $roles);
$is_officer = in_array('administrator', $roles);
$is_customer = in_array('subscriber', $roles);
$is_official = $is_admin || $is_officer;

// Get customer data if applicable
$customer_id = 0;
global $wpdb;
$customer = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}shipping_customers WHERE wp_user_id = %d", $my_id));
if ($customer) {
    $customer_id = $customer->id;
}

$categories = array(
    'inquiry' => array('label' => 'استفسار عام', 'color' => '#EBF8FF', 'text' => '#3182CE'),
    'finance' => array('label' => 'مشكلة مالية', 'color' => '#FEF3C7', 'text' => '#B45309'),
    'technical' => array('label' => 'دعم فني', 'color' => '#F0FDF4', 'text' => '#15803D'),
    'customership' => array('label' => 'تجديد حساب', 'color' => '#F5F3FF', 'text' => '#6D28D9'),
    'other' => array('label' => 'أخرى', 'color' => '#F1F5F9', 'text' => '#475569')
);

$statuses = array(
    'open' => array('label' => 'مفتوح', 'class' => 'shipping-badge-high'),
    'in-progress' => array('label' => 'قيد التنفيذ', 'class' => 'shipping-badge-mid'),
    'closed' => array('label' => 'مغلق', 'class' => 'shipping-badge-low')
);

$priorities = array(
    'low' => 'منخفض',
    'medium' => 'متوسط',
    'high' => 'عاجل'
);
?>

<div class="shipping-tickets-wrapper" dir="rtl" style="min-height: 700px; font-family: 'Rubik', sans-serif;">

    <!-- Top Filter Bar -->
    <div class="shipping-tickets-top-bar" style="background: #fff; border-radius: 15px; border: 1px solid var(--shipping-border-color); padding: 20px 25px; box-shadow: var(--shipping-shadow); margin-bottom: 25px;">
        <div style="display: flex; flex-wrap: wrap; gap: 15px; align-items: center;">
            <h2 style="margin: 0; font-weight: 800; color: var(--shipping-dark-color); font-size: 1.2em; flex: 1; min-width: 200px;">نظام التذاكر والدعم</h2>

            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <select id="filter-status" class="shipping-select" onchange="TicketsController.loadTickets()" style="width: 120px; height: 40px; padding: 0 10px;">
                    <option value="">كل الحالات</option>
                    <?php foreach($statuses as $k => $v) echo "<option value='$k'>{$v['label']}</option>"; ?>
                </select>

                <select id="filter-category" class="shipping-select" onchange="TicketsController.loadTickets()" style="width: 130px; height: 40px; padding: 0 10px;">
                    <option value="">كل الأقسام</option>
                    <?php foreach($categories as $k => $v) echo "<option value='$k'>{$v['label']}</option>"; ?>
                </select>

                <select id="filter-priority" class="shipping-select" onchange="TicketsController.loadTickets()" style="width: 110px; height: 40px; padding: 0 10px;">
                    <option value="">كل الأولويات</option>
                    <?php foreach($priorities as $k => $v) echo "<option value='$k'>$v</option>"; ?>
                </select>


                <div style="position: relative;">
                    <input type="text" id="filter-search" class="shipping-input" placeholder="بحث..." oninput="TicketsController.loadTickets()" style="width: 180px; height: 40px; padding-left: 30px;">
                    <span class="dashicons dashicons-search" style="position: absolute; left: 8px; top: 10px; color: #94a3b8; font-size: 18px;"></span>
                </div>

                <?php if ($is_customer): ?>
                    <button onclick="ShippingModal.open('create-ticket-modal')" class="shipping-btn" style="height: 40px; padding: 0 15px; font-weight: 700;">+ تذكرة</button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="shipping-tickets-main">
        <div id="tickets-list-container">
            <div id="shipping-tickets-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                <!-- Loaded via JS -->
            </div>
        </div>

        <div id="ticket-details-container" style="display: none; animation: shippingFadeIn 0.3s ease-out;">
            <!-- Loaded via JS -->
        </div>
    </div>
</div>

<!-- Modal: Create Ticket -->
<div id="create-ticket-modal" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 600px;">
        <div class="shipping-modal-header">
            <h3>فتح تذكرة دعم جديدة</h3>
            <button class="shipping-modal-close" onclick="ShippingModal.close('create-ticket-modal')">&times;</button>
        </div>
        <form id="create-ticket-form" style="padding: 20px;">
            <div class="shipping-form-group">
                <label class="shipping-label">موضوع التذكرة:</label>
                <input type="text" name="subject" class="shipping-input" required placeholder="مثال: مشكلة في تحديث البيانات">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="shipping-form-group">
                    <label class="shipping-label">القسم:</label>
                    <select name="category" class="shipping-select" required>
                        <?php foreach($categories as $k => $v) echo "<option value='$k'>{$v['label']}</option>"; ?>
                    </select>
                </div>
                <div class="shipping-form-group">
                    <label class="shipping-label">الأولوية:</label>
                    <select name="priority" class="shipping-select">
                        <option value="low">منخفضة</option>
                        <option value="medium" selected>متوسطة</option>
                        <option value="high">عالية / عاجل</option>
                    </select>
                </div>
            </div>
            <div class="shipping-form-group">
                <label class="shipping-label">تفاصيل المشكلة / الطلب:</label>
                <textarea name="message" class="shipping-textarea" rows="5" required placeholder="يرجى شرح طلبك بالتفصيل..."></textarea>
            </div>
            <div class="shipping-form-group">
                <label class="shipping-label">مرفقات (اختياري):</label>
                <input type="file" name="attachment" class="shipping-input">
                <p style="font-size: 11px; color: #64748b; margin-top: 5px;">يسمح بملفات الصور و PDF (بحد أقصى 5 ميجابايت)</p>
            </div>
            <button type="submit" class="shipping-btn" style="width: 100%; height: 45px; font-weight: 700; margin-top: 10px;">إرسال التذكرة</button>
        </form>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', () => {
    TicketsController.init({
        categories: <?php echo json_encode($categories); ?>,
        statuses: <?php echo json_encode($statuses); ?>,
        priorities: <?php echo json_encode($priorities); ?>,
        isOfficial: <?php echo $is_official ? 'true' : 'false'; ?>,
        currentUserId: <?php echo $my_id; ?>
    });
});
</script>

<style>
.shipping-ticket-card:hover {
    border-color: var(--shipping-primary-color) !important;
    box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    transform: translateY(-2px);
}
.shipping-loader-mini { border: 3px solid #f3f3f3; border-top: 3px solid var(--shipping-primary-color); border-radius: 50%; width: 24px; height: 24px; animation: shipping-spin 1s linear infinite; display: inline-block; }
@keyframes shipping-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>
