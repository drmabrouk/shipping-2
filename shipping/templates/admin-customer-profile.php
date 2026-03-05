<?php if (!defined('ABSPATH')) exit;

$customer_id = intval($_GET['customer_id'] ?? 0);
$customer = Shipping_DB::get_customer_by_id($customer_id);

if (!$customer) {
    echo '<div class="error"><p>العميل غير موجود.</p></div>';
    return;
}

$user = wp_get_current_user();
$is_sys_manager = in_array('administrator', (array)$user->roles);
$is_administrator = in_array('administrator', (array)$user->roles);
$is_subscriber = in_array('subscriber', (array)$user->roles);

// IDOR CHECK: Restricted users can only see their own profile
if ($is_subscriber && !current_user_can('manage_options')) {
    if ($customer->wp_user_id != $user->ID) {
        echo '<div class="error" style="padding:20px; background:#fff5f5; color:#c53030; border-radius:8px; border:1px solid #feb2b2;"><h4>عذراً، لا تملك صلاحية الوصول لهذا الملف.</h4><p>لا يمكنك استعراض بيانات العملاء الآخرين.</p></div>';
        return;
    }
}

$statuses = Shipping_Settings::get_account_statuses();
?>

<div class="shipping-customer-profile-view" dir="rtl">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: #fff; padding: 20px; border-radius: 12px; border: 1px solid var(--shipping-border-color); box-shadow: var(--shipping-shadow);">
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="position: relative;">
                <div id="customer-photo-container" style="width: 80px; height: 80px; background: #f0f4f8; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; border: 3px solid var(--shipping-primary-color); overflow: hidden;">
                    <?php if ($customer->photo_url): ?>
                        <img src="<?php echo esc_url($customer->photo_url); ?>" style="width:100%; height:100%; object-fit:cover;">
                    <?php else: ?>
                        <span class="dashicons dashicons-admin-users" style="font-size: 50px; width: 50px; height: 50px; color: #cbd5e0;"></span>
                    <?php endif; ?>
                </div>
                <button onclick="shippingTriggerPhotoUpload()" style="position: absolute; bottom: 0; right: 0; background: var(--shipping-primary-color); color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                    <span class="dashicons dashicons-camera" style="font-size: 14px; width: 14px; height: 14px;"></span>
                </button>
                <input type="file" id="customer-photo-input" style="display:none;" accept="image/*" onchange="shippingUploadCustomerPhoto(<?php echo $customer->id; ?>)">
            </div>
            <div>
                <h2 style="margin:0; color: var(--shipping-dark-color);"><?php echo esc_html($customer->first_name . ' ' . $customer->last_name); ?></h2>
            </div>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <?php if (!$is_subscriber): ?>
                <button onclick="shippingEditCustomer(JSON.parse(this.dataset.customer))" data-customer='<?php echo esc_attr(wp_json_encode($customer)); ?>' class="shipping-btn" style="background: #3182ce; width: auto;"><span class="dashicons dashicons-edit"></span> تعديل البيانات</button>
            <?php endif; ?>

            <?php if (!$is_subscriber || current_user_can('manage_options')): ?>
                <a href="<?php echo admin_url('admin-ajax.php?action=shipping_print&print_type=id_card&customer_id='.$customer->id); ?>" target="_blank" class="shipping-btn" style="background: #27ae60; width: auto; text-decoration:none; display:flex; align-items:center; gap:8px;"><span class="dashicons dashicons-id-alt"></span> طباعة الكارنيه</a>
            <?php endif; ?>
            <?php if ($is_sys_manager): ?>
                <button onclick="deleteCustomer(<?php echo $customer->id; ?>, '<?php echo esc_js($customer->first_name . ' ' . $customer->last_name); ?>')" class="shipping-btn" style="background: #e53e3e; width: auto;"><span class="dashicons dashicons-trash"></span> حذف العميل</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Profile Tabs -->
    <div class="shipping-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 2px solid #eee; padding-bottom: 10px;">
        <button class="shipping-tab-btn shipping-active" onclick="shippingOpenInternalTab('profile-info', this)">بيانات الحساب</button>
        <button class="shipping-tab-btn" onclick="shippingOpenInternalTab('customer-chat', this); setTimeout(() => selectConversation(<?php echo $customer->id; ?>, '<?php echo esc_js($customer->first_name . ' ' . $customer->last_name); ?>', <?php echo $customer->wp_user_id ?: 0; ?>), 100);">المراسلات والشكاوى</button>
    </div>

    <div id="profile-info" class="shipping-internal-tab">
        <div style="display: grid; grid-template-columns: 1fr; gap: 30px;">
            <div style="display: flex; flex-direction: column; gap: 30px;">
                <!-- Basic Info -->
                <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid var(--shipping-border-color); box-shadow: var(--shipping-shadow);">
                <h3 style="margin-top:0; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">البيانات الأساسية</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div><label class="shipping-label">الاسم الأول:</label> <div class="shipping-value"><?php echo esc_html($customer->first_name); ?></div></div>
                    <div><label class="shipping-label">اسم العائلة:</label> <div class="shipping-value"><?php echo esc_html($customer->last_name); ?></div></div>
                    <div><label class="shipping-label">اسم المستخدم:</label> <div class="shipping-value"><?php echo esc_html($customer->username); ?></div></div>
                    <div><label class="shipping-label">كود الحساب:</label> <div class="shipping-value"><?php echo esc_html($customer->id_number); ?></div></div>
                    <div><label class="shipping-label">رقم الهاتف:</label> <div class="shipping-value"><?php echo esc_html($customer->phone); ?></div></div>
                    <div><label class="shipping-label">البريد الإلكتروني:</label> <div class="shipping-value"><?php echo esc_html($customer->email); ?></div></div>
                </div>


                <h4 style="margin: 20px 0 10px 0; color: var(--shipping-primary-color);">بيانات السكن والاتصال</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div><label class="shipping-label">المدينة:</label> <div class="shipping-value"><?php echo esc_html($customer->residence_city); ?></div></div>
                    <div style="grid-column: span 2;"><label class="shipping-label">العنوان (الشارع):</label> <div class="shipping-value"><?php echo esc_html($customer->residence_street); ?></div></div>
                    <?php if ($customer->wp_user_id): ?>
                        <?php $temp_pass = get_user_meta($customer->wp_user_id, 'shipping_temp_pass', true); if ($temp_pass): ?>
                            <div style="grid-column: span 2; background: #fffaf0; padding: 15px; border-radius: 8px; border: 1px solid #feebc8; margin-top: 10px;">
                                <label class="shipping-label" style="color: #744210;">كلمة المرور المؤقتة للنظام:</label>
                                <div style="font-family: monospace; font-size: 1.2em; font-weight: 700; color: #975a16;"><?php echo esc_html($temp_pass); ?></div>
                                <small style="color: #975a16;">* يرجى تزويد العميل بهذه الكلمة ليتمكن من الدخول لأول مرة.</small>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <!-- Communication Tab -->
    <div id="customer-chat" class="shipping-internal-tab" style="display: none;">
        <div style="height: 600px; border: 1px solid #eee; border-radius: 12px; overflow: hidden; background: #fff;">
            <?php
            // Reuse messaging-center but in a compact way
            include SHIPPING_PLUGIN_DIR . 'templates/messaging-center.php';
            ?>
        </div>
    </div>

    <!-- Edit Customer Modal -->
    <div id="edit-customer-modal" class="shipping-modal-overlay">
        <div class="shipping-modal-content" style="max-width: 900px;">
            <div class="shipping-modal-header"><h3>تعديل بيانات العميل</h3><button class="shipping-modal-close" onclick="ShippingModal.close('edit-customer-modal')">&times;</button></div>
            <form id="edit-customer-form" style="padding: 20px;">
                <?php wp_nonce_field('shipping_add_customer', 'shipping_nonce'); ?>
                <input type="hidden" name="customer_id" id="edit_customer_id_hidden">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                    <div class="shipping-form-group"><label class="shipping-label">الاسم الأول:</label><input name="first_name" id="edit_first_name" type="text" class="shipping-input" required></div>
                    <div class="shipping-form-group"><label class="shipping-label">اسم العائلة:</label><input name="last_name" id="edit_last_name" type="text" class="shipping-input" required></div>
                    <div class="shipping-form-group"><label class="shipping-label">اسم المستخدم:</label><input name="username" id="edit_username" type="text" class="shipping-input" required></div>

                    <div class="shipping-form-group"><label class="shipping-label">المدينة:</label><input name="residence_city" id="edit_res_city" type="text" class="shipping-input"></div>

                    <div class="shipping-form-group" style="grid-column: span 2;"><label class="shipping-label">العنوان (الشارع):</label><input name="residence_street" id="edit_res_street" type="text" class="shipping-input"></div>

                    <div class="shipping-form-group"><label class="shipping-label">رقم الهاتف:</label><input name="phone" id="edit_phone" type="text" class="shipping-input"></div>
                    <div class="shipping-form-group"><label class="shipping-label">البريد الإلكتروني:</label><input name="email" id="edit_email" type="email" class="shipping-input"></div>
                    <div class="shipping-form-group" style="grid-column: span 2;"><label class="shipping-label">ملاحظات:</label><textarea name="notes" id="edit_notes" class="shipping-input" rows="2"></textarea></div>
                </div>
                <button type="submit" class="shipping-btn" style="width: 100%; margin-top: 20px;">تحديث البيانات الآن</button>
            </form>
        </div>
    </div>

</div>

<script>
window.shippingTriggerPhotoUpload = () => document.getElementById('customer-photo-input').click();

window.shippingUploadCustomerPhoto = function(customerId) {
    const file = document.getElementById('customer-photo-input').files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('action', 'shipping_update_customer_photo');
    formData.append('customer_id', customerId);
    formData.append('customer_photo', file);
    formData.append('shipping_photo_nonce', '<?php echo wp_create_nonce("shipping_photo_action"); ?>');

    fetch(ajaxurl, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            document.getElementById('customer-photo-container').innerHTML = `<img src="${res.data.photo_url}" style="width:100%; height:100%; object-fit:cover;">`;
            shippingShowNotification('تم تحديث الصورة الشخصية');
        } else {
            alert('فشل الرفع: ' + res.data);
        }
    });
};

window.deleteCustomer = function(id, name) {
    if (!confirm('هل أنت متأكد من حذف العميل؟')) return;
    const formData = new FormData();
    formData.append('action', 'shipping_delete_customer_ajax');
    formData.append('customer_id', id);
    formData.append('nonce', '<?php echo wp_create_nonce("shipping_delete_customer"); ?>');

    fetch(ajaxurl, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            window.location.href = '<?php echo add_query_arg('shipping_tab', 'users-management'); ?>';
        } else alert('خطأ: ' + res.data);
    });
};

window.shippingEditCustomer = function(s) {
    const f = document.getElementById('edit-customer-form');
    f.customer_id.value = s.id;
    f.first_name.value = s.first_name;
    f.last_name.value = s.last_name;
    f.username.value = s.username;
    f.residence_city.value = s.residence_city || '';
    f.residence_street.value = s.residence_street || '';
    f.phone.value = s.phone;
    f.email.value = s.email;
    f.notes.value = s.notes || '';
    ShippingModal.open('edit-customer-modal');
};

document.getElementById('edit-customer-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fd.append('action', 'shipping_update_customer_ajax');
    fetch(ajaxurl, { method: 'POST', body: fd })
    .then(r => r.json()).then(res => {
        if(res.success) {
            shippingShowNotification('تم تحديث البيانات بنجاح');
            setTimeout(() => location.reload(), 500);
        } else alert(res.data);
    });
});
</script>
