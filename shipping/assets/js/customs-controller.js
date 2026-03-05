/**
 * Customs Management Controller
 */

window.CustomsController = {
    init() {
        this.setupEventListeners();
        this.loadShipmentsForSelect();
        this.initActiveTab();
    },

    initActiveTab() {
        const activeTab = document.querySelector('.shipping-tab-btn.shipping-active');
        if (!activeTab) return;

        const onclick = activeTab.getAttribute('onclick');
        if (onclick.includes('customs-docs')) this.loadDocs();
        else if (onclick.includes('customs-invoices')) this.loadInvoices();
        else if (onclick.includes('customs-status')) this.loadStatus();
    },

    setupEventListeners() {
        this.bindForm('form-add-customs-full', 'shipping_add_customs', () => location.reload(), shippingVars.customsNonce);
        this.bindForm('form-add-customs-doc', 'shipping_add_customs_doc', () => this.loadDocs(), shippingVars.customsNonce);
    },

    bindForm(formId, action, callback, nonce) {
        const form = document.getElementById(formId);
        if (!form) return;
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const fd = new FormData(form);
            if (!fd.has('action')) fd.append('action', action);
            if (nonce) fd.append('nonce', nonce);

            fetch(ajaxurl, { method: 'POST', body: fd })
            .then(r => r.json()).then(res => {
                if (res.success) {
                    const modalId = form.closest('.shipping-modal-overlay')?.id;
                    if (modalId) ShippingModal.close(modalId);
                    shippingShowNotification('تمت العملية بنجاح');
                    if (callback) callback(res);
                } else alert(res.data);
            });
        });
    },

    calculateTax() {
        const val = parseFloat(document.getElementById('goods-value').value) || 0;
        const rate = parseFloat(document.getElementById('hs-category').value);

        const duties = val * rate;
        const vat = (val + duties) * 0.15;
        const total = duties + vat;

        document.getElementById('res-duties').innerText = duties.toFixed(2) + ' ' + (window.shippingCurrency || '');
        document.getElementById('res-vat').innerText = vat.toFixed(2) + ' ' + (window.shippingCurrency || '');
        document.getElementById('res-total-tax').innerText = total.toFixed(2) + ' ' + (window.shippingCurrency || '');
        document.getElementById('tax-result-card').style.display = 'block';
    },

    loadDocs() {
        fetch(ajaxurl + '?action=shipping_get_customs_docs&nonce=' + shippingVars.customsNonce)
        .then(r => r.json()).then(res => {
            const tbody = document.getElementById('customs-docs-table');
            if (!tbody) return;
            if (!res.data.length) { tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">لا توجد مستندات مرفوعة</td></tr>'; return; }
            tbody.innerHTML = res.data.filter(d => d.doc_type !== 'Commercial Invoice').map(d => `
                <tr>
                    <td><strong>#${d.shipment_id}</strong></td>
                    <td>${d.doc_type}</td>
                    <td><span class="shipping-badge">${d.status}</span></td>
                    <td>${d.uploaded_at}</td>
                    <td><a href="${d.file_url}" target="_blank" class="shipping-btn-outline" style="padding:4px 8px; font-size:11px;">معاينة</a></td>
                </tr>
            `).join('');
        });
    },

    loadInvoices() {
        fetch(ajaxurl + '?action=shipping_get_customs_docs&nonce=' + shippingVars.customsNonce)
        .then(r => r.json()).then(res => {
            const tbody = document.getElementById('customs-invoices-table');
            if (!tbody) return;
            if (!res.data.length) { tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">لا توجد فواتير تجارية</td></tr>'; return; }
            tbody.innerHTML = res.data.filter(d => d.doc_type === 'Commercial Invoice').map(d => `
                <tr>
                    <td><strong>#${d.shipment_id}</strong></td>
                    <td>CIN-${d.id}</td>
                    <td>---</td>
                    <td><span class="shipping-badge">${d.status}</span></td>
                    <td><a href="${d.file_url}" target="_blank" class="shipping-btn-outline" style="padding:4px 8px; font-size:11px;">عرض الفاتورة</a></td>
                </tr>
            `).join('');
        });
    },

    loadShipmentsForSelect() {
        fetch(ajaxurl + '?action=shipping_get_all_shipments&nonce=' + shippingVars.nonce)
        .then(r => r.json()).then(res => {
            if (res.success) {
                const options = res.data.map(s => `<option value="${s.id}">${s.shipment_number}</option>`).join('');
                const cSelect = document.getElementById('select-customs-shipment');
                const dSelect = document.getElementById('select-doc-shipment');
                if (cSelect) cSelect.innerHTML = '<option value="">اختر الشحنة...</option>' + options;
                if (dSelect) dSelect.innerHTML = '<option value="">اختر الشحنة...</option>' + options;
            }
        });
    },

    loadStatus() {
        fetch(ajaxurl + '?action=shipping_get_customs_status&nonce=' + shippingVars.customsNonce)
        .then(r => r.json()).then(res => {
            const tbody = document.getElementById('customs-status-table');
            if (!tbody) return;
            if (!res.data.length) { tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">لا توجد بيانات تخليص</td></tr>'; return; }
            tbody.innerHTML = res.data.map(c => `
                <tr>
                    <td><strong>${c.shipment_number}</strong></td>
                    <td>${c.documentation_status}</td>
                    <td>${parseFloat(c.duties_amount).toFixed(2)} ${window.shippingCurrency || ''}</td>
                    <td><span class="shipping-badge">${c.clearance_status}</span></td>
                </tr>
            `).join('');
        });
    }
};

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('customs-docs') || document.getElementById('customs-status')) {
        CustomsController.init();
    }
});
