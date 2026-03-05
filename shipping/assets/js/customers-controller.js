/**
 * Customers Management Controller
 */

window.CustomersController = {
    activeCustomerId: null,

    init() {
        this.setupEventListeners();
    },

    setupEventListeners() {
        const addCustomerForm = document.getElementById('shipping-add-customer-form');
        if (addCustomerForm) {
            addCustomerForm.addEventListener('submit', (e) => this.handleAddCustomer(e));
        }

        const addContractForm = document.getElementById('form-add-contract');
        if (addContractForm) {
            addContractForm.addEventListener('submit', (e) => this.handleAddContract(e));
        }

        const updateProfileForm = document.getElementById('form-update-profile');
        if (updateProfileForm) {
            updateProfileForm.addEventListener('submit', (e) => this.handleUpdateProfile(e));
        }

        const updateAccountForm = document.getElementById('form-update-account-security');
        if (updateAccountForm) {
            updateAccountForm.addEventListener('submit', (e) => this.handleUpdateAccount(e));
        }

        const deleteBtn = document.getElementById('btn-delete-customer');
        if (deleteBtn) {
            deleteBtn.onclick = () => this.deleteAccount();
        }
    },

    filterCustomers() {
        const query = document.getElementById('customer-search-query').value.toLowerCase();
        const filterClass = document.getElementById('customer-filter-class').value;
        const sortOrder = document.getElementById('customer-sort-order').value;
        const rows = Array.from(document.querySelectorAll('.customer-entry-row'));

        rows.forEach(row => {
            const searchData = row.getAttribute('data-search');
            const rowClass = row.getAttribute('data-class');

            const matchesQuery = !query || searchData.includes(query);
            const matchesClass = !filterClass || rowClass === filterClass;

            row.style.display = (matchesQuery && matchesClass) ? '' : 'none';
        });

        // Sorting
        const tbody = document.getElementById('unified-customer-list');
        const sortedRows = rows.sort((a, b) => {
            if (sortOrder === 'newest') return parseInt(b.dataset.id) - parseInt(a.dataset.id);
            if (sortOrder === 'oldest') return parseInt(a.dataset.id) - parseInt(b.dataset.id);
            if (sortOrder === 'name_asc') return a.dataset.name.localeCompare(b.dataset.name);
            if (sortOrder === 'name_desc') return b.dataset.name.localeCompare(a.dataset.name);
            return 0;
        });
        sortedRows.forEach(row => tbody.appendChild(row));
    },

    resetFilters() {
        document.getElementById('customer-advanced-search').reset();
        this.filterCustomers();
    },

    openEditSimple(c) {
        this.activeCustomerId = c.id;
        const form = document.getElementById('shipping-add-customer-form');
        if (!form) return;

        form.first_name.value = c.first_name;
        form.last_name.value = c.last_name;
        form.username.value = c.username;
        form.email.value = c.email;
        form.phone.value = c.phone;
        form.residence_city.value = c.residence_city || '';
        form.classification.value = c.classification || 'regular';

        const modal = document.getElementById('add-customer-modal');
        modal.querySelector('h3').innerText = 'تعديل بيانات العميل: ' + c.name;
        modal.querySelector('button[type="submit"]').innerText = 'تحديث البيانات';

        ShippingModal.open('add-customer-modal');
    },

    viewCustomerDossier(id) {
        this.activeCustomerId = id;
        ShippingState.setCustomer(id);

        const modal = 'modal-customer-dossier';
        document.getElementById('dossier-loading').style.display = 'block';
        document.getElementById('dossier-content').style.display = 'none';
        ShippingModal.open(modal);

        fetch(ajaxurl + '?action=shipping_get_customer_comprehensive&id=' + id + '&nonce=' + shippingVars.nonce)
        .then(r => r.json()).then(res => {
            if (res.success) {
                const d = res.data;
                this.populateDossier(d);
                document.getElementById('dossier-loading').style.display = 'none';
                document.getElementById('dossier-content').style.display = 'flex';
            } else alert(res.data);
        });
    },

    populateDossier(d) {
        const c = d.customer;
        document.getElementById('dossier-customer-name').innerText = 'ملف: ' + c.name;

        // Avatar
        const avatarCont = document.getElementById('dossier-avatar-container');
        avatarCont.innerHTML = `
            <div style="width:80px; height:80px; border-radius:50%; margin:0 auto 10px; border:3px solid var(--shipping-primary-color); overflow:hidden; background:#fff;">
                <img src="${c.photo_url || ''}" style="width:100%; height:100%; object-fit:cover; display:${c.photo_url ? 'block' : 'none'}">
                <span class="dashicons dashicons-admin-users" style="font-size:40px; width:40px; height:40px; margin-top:20px; color:#cbd5e0; display:${c.photo_url ? 'none' : 'inline-block'}"></span>
            </div>
            <div style="font-weight:800;">${c.name}</div>
            <div style="font-size:11px; color:#64748b;">@${c.username}</div>
        `;

        // Profile Form
        const f = document.getElementById('form-update-profile');
        f.first_name.value = c.first_name;
        f.last_name.value = c.last_name;
        f.phone.value = c.phone;
        f.alt_phone.value = c.alt_phone || '';
        f.classification.value = c.classification || 'regular';
        f.account_status.value = c.account_status;
        f.residence_street.value = c.residence_street || '';
        f.notes.value = c.notes || '';

        // Account Form
        const af = document.getElementById('form-update-account-security');
        af.account_email.value = c.email || '';
        document.getElementById('dossier-new-pass').value = '';

        // Shipments
        const sBody = document.getElementById('dossier-shipments-body');
        if (d.shipments.length) {
            sBody.innerHTML = d.shipments.map(s => `
                <tr>
                    <td><strong>${s.shipment_number}</strong></td>
                    <td>${s.destination}</td>
                    <td>${s.created_at.split(' ')[0]}</td>
                    <td><span class="shipping-badge">${s.status}</span></td>
                    <td><button class="shipping-btn-outline" style="padding:2px 8px; font-size:10px;" onclick="ShipmentsController.viewFullDossier(${s.id})">ملف</button></td>
                </tr>
            `).join('');
        } else {
            sBody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:20px; color:#94a3b8;">لا توجد شحنات مسجلة لهذا العميل.</td></tr>';
        }

        // Contracts
        const cBody = document.getElementById('dossier-contracts-body');
        if (d.contracts.length) {
            cBody.innerHTML = d.contracts.map(con => `
                <tr>
                    <td><strong>${con.contract_number}</strong></td>
                    <td>${con.title}</td>
                    <td>${con.end_date}</td>
                    <td><span class="shipping-badge">${con.status}</span></td>
                </tr>
            `).join('');
        } else {
            cBody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:20px; color:#94a3b8;">لا توجد عقود مسجلة.</td></tr>';
        }

        // Addresses
        const aList = document.getElementById('dossier-address-list');
        aList.innerHTML = `
            <div style="padding:15px; background:#fff; border:1px solid #edf2f7; border-radius:8px; margin-bottom:10px;">
                <div style="font-weight:700; color:var(--shipping-primary-color); margin-bottom:5px;">العنوان الرئيسي (المسجل)</div>
                <div style="font-size:13px;">${c.residence_street || 'غير محدد'}</div>
                <div style="font-size:13px;">${c.residence_city || ''}</div>
            </div>
        `;
    },

    switchDossierTab(tabId, btn) {
        document.querySelectorAll('.dossier-tab').forEach(t => t.style.display = 'none');
        document.getElementById('dossier-tab-' + tabId).style.display = 'block';

        document.querySelectorAll('.dossier-nav-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        // Contextual UI updates
        if (tabId === 'contracts') {
            const select = document.querySelector('#form-add-contract select[name="customer_id"]');
            if (select) select.value = this.activeCustomerId;
        }
    },

    generateRandomPass() {
        const pass = 'SHP' + Math.random().toString(36).slice(-8).toUpperCase();
        document.getElementById('dossier-new-pass').value = pass;
    },

    handleUpdateProfile(e) {
        e.preventDefault();
        const fd = new FormData(e.target);
        fd.append('action', 'shipping_update_customer_ajax');
        fd.append('customer_id', this.activeCustomerId);
        fd.append('shipping_nonce', shippingVars.customerNonce);

        fetch(ajaxurl, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if (res.success) {
                shippingShowNotification('تم تحديث الملف الشخصي بنجاح');
            } else alert(res.data);
        });
    },

    handleUpdateAccount(e) {
        e.preventDefault();
        const fd = new FormData(e.target);
        fd.append('action', 'shipping_update_customer_account_ajax');
        fd.append('customer_id', this.activeCustomerId);
        fd.append('shipping_nonce', shippingVars.nonce);

        fetch(ajaxurl, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if (res.success) {
                shippingShowNotification('تم تحديث بيانات الحساب والأمان');
            } else alert(res.data);
        });
    },

    deleteAccount() {
        if (!confirm('تحذير: سيتم حذف هذا الحساب وكافة بياناته نهائياً. هل أنت متأكد؟')) return;
        const fd = new FormData();
        fd.append('action', 'shipping_delete_customer_ajax');
        fd.append('customer_id', this.activeCustomerId);
        fd.append('nonce', shippingVars.deleteCustomerNonce);

        fetch(ajaxurl, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if (res.success) {
                shippingShowNotification('تم حذف الحساب بنجاح');
                location.reload();
            } else alert(res.data);
        });
    },

    handleAddCustomer(e) {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;

        const isUpdate = this.activeCustomerId !== null && form.querySelector('button[type="submit"]').innerText.includes('تحديث');
        btn.innerText = isUpdate ? 'جاري التحديث...' : 'جاري الحفظ...';

        const fd = new FormData(form);
        if (isUpdate) {
            fd.append('action', 'shipping_update_customer_ajax');
            fd.append('customer_id', this.activeCustomerId);
        } else {
            fd.append('action', 'shipping_add_customer_ajax');
        }
        fd.append('shipping_nonce', shippingVars.customerNonce);

        fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json()).then(res => {
            btn.disabled = false;
            btn.innerText = isUpdate ? 'تحديث البيانات' : 'حفظ بيانات العميل';
            if (res.success) {
                shippingShowNotification(isUpdate ? 'تم تحديث العميل بنجاح' : 'تمت إضافة العميل بنجاح');
                ShippingModal.close('add-customer-modal');
                location.reload();
            } else {
                alert(res.data);
            }
        });
    },

    loadContracts() {
        const tbody = document.getElementById('contracts-table-body');
        if (!tbody) return;

        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;"><span class="dashicons dashicons-update spin"></span> جاري التحميل...</td></tr>';

        fetch(ajaxurl + '?action=shipping_get_contracts&nonce=' + shippingVars.nonce)
        .then(r => r.json()).then(res => {
            if (!res.data.length) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">لا توجد عقود مسجلة</td></tr>';
                return;
            }
            tbody.innerHTML = res.data.map(c => `
                <tr>
                    <td><strong>${c.contract_number}</strong></td>
                    <td>${c.customer_name}</td>
                    <td>${c.title}</td>
                    <td style="color: ${new Date(c.end_date) < new Date() ? '#e53e3e' : 'inherit'}">${c.end_date}</td>
                    <td><span class="shipping-badge">${c.status}</span></td>
                    <td><a href="${c.file_url}" target="_blank" class="shipping-btn-outline" style="padding:4px 8px; font-size:10px;">عرض العقد</a></td>
                </tr>
            `).join('');
        });
    },

    handleAddContract(e) {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerText = 'جاري الحفظ...';

        const fd = new FormData(form);
        fd.append('action', 'shipping_add_contract');
        fd.append('nonce', shippingVars.contractNonce);

        fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json()).then(res => {
            btn.disabled = false;
            btn.innerText = 'حفظ العقد';
            if (res.success) {
                shippingShowNotification('تم حفظ العقد بنجاح');
                ShippingModal.close('modal-add-contract');
                form.reset();
                this.loadContracts();
            } else {
                alert(res.data);
            }
        });
    }
};

