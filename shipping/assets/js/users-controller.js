/**
 * Users & Staff Management Controller
 */

window.UsersController = {
    init() {
        this.setupEventListeners();
    },

    setupEventListeners() {
        this.bindForm('add-user-form', 'shipping_add_staff_ajax', () => location.reload(), shippingVars.staffNonce);
        this.bindForm('edit-user-form', 'shipping_update_staff_ajax', () => location.reload(), shippingVars.staffNonce);
    },

    bindForm(formId, action, callback, nonce) {
        const form = document.getElementById(formId);
        if (!form) return;
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const fd = new FormData(form);
            if (!fd.has('action')) fd.append('action', action);
            if (nonce) fd.append('shipping_nonce', nonce);

            fetch(ajaxurl, { method: 'POST', body: fd })
            .then(r => r.json()).then(res => {
                if (res.success) {
                    const modalId = form.closest('.shipping-modal-overlay')?.id;
                    if (modalId) ShippingModal.close(modalId);
                    shippingShowNotification('تمت العملية بنجاح');
                    if (callback) callback(res);
                } else {
                    shippingShowNotification('خطأ: ' + res.data, 'error');
                }
            });
        });
    },

    toggleAll(master) {
        document.querySelectorAll('.user-cb').forEach(cb => cb.checked = master.checked);
    },

    toggleCustomerFields(role) {
        const div = document.getElementById('customer-specific-fields');
        if (div) div.style.display = (role === 'subscriber') ? 'block' : 'none';
    },

    deleteUser(id, name) {
        if (!confirm('هل أنت متأكد من حذف حساب: ' + name + '؟')) return;
        const formData = new FormData();
        formData.append('action', 'shipping_delete_staff_ajax');
        formData.append('user_id', id);
        formData.append('nonce', shippingVars.staffNonce || '');

        fetch(ajaxurl, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                shippingShowNotification('تم حذف المستخدم بنجاح');
                setTimeout(() => location.reload(), 500);
            } else {
                alert('خطأ: ' + res.data);
            }
        });
    },

    executeBulkDelete() {
        const ids = Array.from(document.querySelectorAll('.user-cb:checked')).map(cb => cb.value);
        if (ids.length === 0) {
            alert('يرجى تحديد مستخدمين أولاً');
            return;
        }
        if (!confirm('هل أنت متأكد من حذف ' + ids.length + ' مستخدم؟')) return;

        const formData = new FormData();
        formData.append('action', 'shipping_bulk_delete_users_ajax');
        formData.append('user_ids', ids.join(','));
        formData.append('nonce', shippingVars.staffNonce || '');

        fetch(ajaxurl, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                shippingShowNotification('تم حذف المستخدمين بنجاح');
                setTimeout(() => location.reload(), 500);
            }
        });
    },

    editUser(u) {
        document.getElementById('edit_user_db_id').value = u.id;
        document.getElementById('edit_user_first_name').value = u.first_name;
        document.getElementById('edit_user_last_name').value = u.last_name;
        document.getElementById('edit_user_code').value = u.customer_id_attr;
        document.getElementById('edit_user_phone').value = u.phone;
        document.getElementById('edit_user_email').value = u.email;
        document.getElementById('edit_user_status').value = u.status || 'active';
        document.getElementById('edit_user_role').value = u.role;
        ShippingModal.open('edit-user-modal');
    }
};

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('add-user-form') || document.querySelector('.user-cb')) {
        UsersController.init();
    }
});
