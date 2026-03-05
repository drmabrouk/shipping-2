/**
 * Orders Management Controller
 */

window.OrdersController = {
    currentStatus: 'new',

    init() {
        this.loadOrders();
        this.setupEventListeners();
    },

    setupEventListeners() {
        const addForm = document.getElementById('form-add-order');
        if (addForm) {
            addForm.addEventListener('submit', (e) => this.handleAddOrder(e));
        }

        const editForm = document.getElementById('form-edit-order');
        if (editForm) {
            editForm.addEventListener('submit', (e) => this.handleEditOrder(e));
        }
    },

    loadOrders(status = this.currentStatus) {
        this.currentStatus = status;
        const searchInput = document.getElementById('order-search');
        const search = searchInput ? searchInput.value : '';
        const sortOrder = document.getElementById('order-sort-order')?.value || 'newest';
        const tbody = document.getElementById('table-body-' + status);
        if (!tbody) return;

        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:30px;"><span class="dashicons dashicons-update spin"></span> جاري التحميل...</td></tr>';

        fetch(ajaxurl + `?action=shipping_get_orders&status=${status}&search=${encodeURIComponent(search)}&nonce=${shippingVars.orderNonce}`)
        .then(r => r.json()).then(res => {
            if (!res.data.length) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:30px; color:#94a3b8;">لا توجد طلبات متوفرة</td></tr>';
                return;
            }

            let orders = res.data;
            // Sorting
            orders.sort((a, b) => {
                if (sortOrder === 'newest') return parseInt(b.id) - parseInt(a.id);
                if (sortOrder === 'oldest') return parseInt(a.id) - parseInt(b.id);
                if (sortOrder === 'amount_desc') return parseFloat(b.total_amount) - parseFloat(a.total_amount);
                if (sortOrder === 'amount_asc') return parseFloat(a.total_amount) - parseFloat(b.total_amount);
                return 0;
            });

            tbody.innerHTML = orders.map(o => this.renderOrderRow(o)).join('');
        });
    },

    resetFilters() {
        document.getElementById('order-advanced-search').reset();
        this.loadOrders();
    },

    renderOrderRow(o) {
        return `
            <tr>
                <td><input type="checkbox" class="order-checkbox" value="${o.id}" onchange="OrdersController.updateBulkBar()"></td>
                <td><strong>${o.order_number}</strong></td>
                <td>
                    <div style="font-weight:700;">${o.customer_name}</div>
                    <div style="font-size:11px; color:#718096;">${o.customer_phone}</div>
                </td>
                <td>${parseFloat(o.total_amount).toFixed(2)} ${window.shippingCurrency || ''}</td>
                <td style="font-size:12px; max-width:200px;">
                    <div class="truncate" title="${o.pickup_address}">من: ${o.pickup_address}</div>
                    <div class="truncate" title="${o.delivery_address}">إلى: ${o.delivery_address}</div>
                </td>
                <td>${o.created_at.split(' ')[0]}</td>
                <td>
                    <div style="display:flex; gap:5px;">
                        <button class="shipping-btn" style="padding:4px 8px; font-size:11px;" onclick="OrdersController.viewOrderLogs(${o.id}, '${o.order_number}')">سجل</button>
                        <button class="shipping-btn" style="padding:4px 8px; font-size:11px; background:#4a5568;" onclick='OrdersController.openEditModal(${JSON.stringify(o).replace(/"/g, '&quot;')})'>تعديل</button>
                        ${o.shipment_id ? `<button class="shipping-btn" style="padding:4px 8px; font-size:11px; background:#319795;" onclick="OrdersController.viewShipmentDossier(${o.shipment_id})">ملف</button>` : ''}
                        ${o.status === 'new' ? `<button class="shipping-btn" style="padding:4px 8px; font-size:11px; background:#3182ce;" onclick="OrdersController.prepareShipment(${o.id})">شحن</button>` : ''}
                        ${o.status !== 'completed' && o.status !== 'cancelled' ? `
                            <button class="shipping-btn" style="padding:4px 8px; font-size:11px; background:#38a169;" onclick="OrdersController.updateStatus(${o.id}, '${this.getNextStatus(o.status)}')">تحديث</button>
                        ` : ''}
                        <button class="shipping-btn" style="padding:4px 8px; font-size:11px; background:#e53e3e;" onclick="OrdersController.deleteOrder(${o.id})">حذف</button>
                    </div>
                </td>
            </tr>
        `;
    },

    getNextStatus(current) {
        if (current === 'new') return 'in-progress';
        if (current === 'in-progress') return 'completed';
        return current;
    },

    handleAddOrder(e) {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        const originalText = btn.innerText;
        btn.innerText = 'جاري الحفظ...';

        const fd = new FormData(form);
        fd.append('action', 'shipping_add_order');
        fd.append('nonce', shippingVars.orderNonce);

        fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json()).then(res => {
            btn.disabled = false;
            btn.innerText = originalText;
            if (res.success) {
                shippingShowNotification('تم إنشاء الطلب بنجاح');
                ShippingModal.close('modal-add-order');
                form.reset();
                this.loadOrders('new');
            } else {
                alert(res.data);
            }
        });
    },

    openAddModal() {
        const f = document.getElementById('form-add-order');
        if (f && ShippingState.selectedCustomer) {
            f.customer_id.value = ShippingState.selectedCustomer;
        }
        ShippingModal.open('modal-add-order');
    },

    openEditModal(o) {
        const f = document.getElementById('form-edit-order');
        if (!f) return;
        f.id.value = o.id;
        f.customer_id.value = o.customer_id;
        f.total_amount.value = o.total_amount;
        f.pickup_address.value = o.pickup_address;
        f.delivery_address.value = o.delivery_address;
        f.order_details.value = o.order_details;
        ShippingModal.open('modal-edit-order');
    },

    handleEditOrder(e) {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerText = 'جاري الحفظ...';

        const fd = new FormData(form);
        fd.append('action', 'shipping_update_order');
        fd.append('nonce', shippingVars.orderNonce);

        fetch(ajaxurl, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            btn.disabled = false;
            btn.innerText = 'حفظ التعديلات';
            if (res.success) {
                shippingShowNotification('تم تحديث الطلب بنجاح');
                ShippingModal.close('modal-edit-order');
                this.loadOrders(this.currentStatus);
            } else alert(res.data);
        });
    },

    updateStatus(id, status) {
        if (!confirm(`هل أنت متأكد من تغيير حالة الطلب إلى ${status}؟`)) return;
        const fd = new FormData();
        fd.append('action', 'shipping_update_order');
        fd.append('nonce', shippingVars.orderNonce);
        fd.append('id', id);
        fd.append('status', status);

        fetch(ajaxurl, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if (res.success) {
                shippingShowNotification('تم تحديث حالة الطلب');
                this.loadOrders(this.currentStatus);
            } else alert(res.data);
        });
    },

    deleteOrder(id) {
        if (!confirm('هل أنت متأكد من حذف هذا الطلب نهائياً؟')) return;
        const fd = new FormData();
        fd.append('action', 'shipping_delete_order');
        fd.append('nonce', shippingVars.orderNonce);
        fd.append('id', id);

        fetch(ajaxurl, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if (res.success) {
                shippingShowNotification('تم حذف الطلب');
                this.loadOrders(this.currentStatus);
            } else alert(res.data);
        });
    },

    viewOrderLogs(id, num) {
        const logNumSpan = document.getElementById('log-order-num');
        if (logNumSpan) logNumSpan.innerText = num;

        const container = document.getElementById('order-logs-timeline');
        if (container) {
            container.innerHTML = '<p style="text-align:center;">جاري تحميل السجل...</p>';
            ShippingModal.open('modal-order-logs');

            fetch(ajaxurl + '?action=shipping_get_order_logs&id=' + id + '&nonce=' + shippingVars.nonce)
            .then(r => r.json()).then(res => {
                if (!res.data.length) { container.innerHTML = '<p>لا توجد سجلات لهذا الطلب</p>'; return; }
                container.innerHTML = res.data.map(l => `
                    <div class="timeline-item" style="border-right: 2px solid #edf2f7; padding-right: 20px; position: relative; padding-bottom: 15px; margin-right: 10px;">
                        <div style="position: absolute; right: -7px; top: 5px; width: 12px; height: 12px; border-radius: 50%; background: var(--shipping-primary-color); border: 2px solid #fff;"></div>
                        <div style="font-weight: 700; font-size: 13px;">${l.action}</div>
                        <div style="font-size: 11px; color: #718096;">بواسطة: ${l.display_name} | ${l.created_at}</div>
                        ${l.new_value ? `<div style="font-size: 12px; margin-top: 5px; background: #f8fafc; padding: 5px; border-radius: 5px;">${l.new_value}</div>` : ''}
                    </div>
                `).join('');
            });
        }
    },

    prepareShipment(orderId) {
        window.location.href = window.shippingAdminUrl + '&shipping_tab=shipment-mgmt&sub=create-shipment&order_id=' + orderId;
    },

    viewShipmentDossier(shipmentId) {
        window.location.href = window.shippingAdminUrl + '&shipping_tab=shipment-mgmt&sub=monitoring&view_dossier=' + shipmentId;
    },

    // Bulk actions
    updateBulkBar() {
        const checked = document.querySelectorAll('.order-checkbox:checked');
        const bar = document.getElementById('order-bulk-bar');
        if (!bar) return;

        if (checked.length > 0) {
            bar.style.display = 'flex';
            const countSpan = document.getElementById('bulk-count');
            if (countSpan) countSpan.innerText = checked.length;
        } else {
            bar.style.display = 'none';
        }
    },

    toggleAll(master) {
        document.querySelectorAll('.order-checkbox').forEach(cb => cb.checked = master.checked);
        this.updateBulkBar();
    },

    clearBulkSelection() {
        document.querySelectorAll('.order-checkbox').forEach(cb => cb.checked = false);
        this.updateBulkBar();
    },

    applyBulkStatus() {
        const statusSelect = document.getElementById('bulk-status');
        if (!statusSelect) return;
        const status = statusSelect.value;
        if (!status) return alert('يرجى اختيار الحالة الجديدة');

        const ids = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
        if (!confirm(`هل أنت متأكد من تغيير حالة ${ids.length} طلبات إلى ${status}؟`)) return;

        const fd = new FormData();
        fd.append('action', 'shipping_bulk_update_orders');
        fd.append('nonce', shippingVars.orderNonce);
        fd.append('ids', ids.join(','));
        fd.append('status', status);

        fetch(ajaxurl, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if (res.success) {
                shippingShowNotification(`تم تحديث ${res.data} طلبات بنجاح`);
                this.clearBulkSelection();
                this.loadOrders(this.currentStatus);
            } else alert(res.data);
        });
    },

    debounceSearch() {
        if (this.searchTimeout) clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => this.loadOrders(), 500);
    }
};

