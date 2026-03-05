/**
 * Billing & Payments Management Controller
 */

window.BillingController = {
    init() {
        this.setupEventListeners();
        this.addInvoiceRow();
        this.loadRules();
        this.loadFees();
        this.initCharts();
    },

    setupEventListeners() {
        this.bindForm('shipping-invoice-form', 'shipping_save_invoice', () => location.reload(), shippingVars.billingNonce);
        this.bindForm('shipping-payment-form', 'shipping_process_payment', () => location.reload(), shippingVars.billingNonce);
        this.bindForm('form-rule-direct', 'shipping_add_pricing', () => this.loadRules(), shippingVars.pricingNonce);
        this.bindForm('form-fee-direct', 'shipping_add_additional_fee', () => this.loadFees(), shippingVars.pricingNonce);

        const calcForm = document.getElementById('shipping-calculator-form-direct');
        if (calcForm) {
            calcForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const fd = new FormData(calcForm);
                fd.append('action', 'shipping_estimate_cost');
                fd.append('nonce', shippingVars.nonce);
                fetch(ajaxurl, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
                    if (res.success) {
                        const data = res.data;
                        document.getElementById('calc-results-direct').style.display = 'block';
                        document.getElementById('estimated-total-direct').innerText = data.total_cost.toFixed(2);
                        let html = '<ul style="list-style:none; padding:0; margin:0; font-size:13px;">';
                        html += `<li style="display:flex; justify-content:space-between; margin-bottom:8px;"><span>أساسي:</span> <strong>${data.breakdown.base.toFixed(2)}</strong></li>`;
                        html += `<li style="display:flex; justify-content:space-between; margin-bottom:8px;"><span>وزن:</span> <strong>${data.breakdown.weight.toFixed(2)}</strong></li>`;
                        html += `<li style="display:flex; justify-content:space-between; margin-bottom:8px;"><span>مسافة:</span> <strong>${data.breakdown.distance.toFixed(2)}</strong></li>`;
                        html += '</ul>';
                        document.getElementById('cost-breakdown-direct').innerHTML = html;
                    }
                });
            });
        }
    },

    bindForm(formId, action, callback, nonce) {
        const form = document.getElementById(formId);
        if (!form) return;
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const fd = new FormData(form);
            if (!fd.has('action')) fd.append('action', action);
            if (nonce) fd.append('nonce', nonce);

            // Special handling for invoice items
            if (formId === 'shipping-invoice-form') {
                const items = [];
                document.querySelectorAll('.invoice-item-row').forEach(row => {
                    items.push({
                        desc: row.querySelector('.item-desc').value,
                        qty: row.querySelector('.item-qty').value,
                        price: row.querySelector('.item-price').value
                    });
                });
                fd.append('items_json', JSON.stringify(items));
                fd.append('subtotal', document.getElementById('invoice-subtotal').innerText);
                fd.append('total_amount', document.getElementById('invoice-total').innerText);
                fd.append('tax_amount', document.getElementById('invoice-tax').innerText);
            }

            fetch(ajaxurl, { method: 'POST', body: fd })
            .then(r => r.json()).then(res => {
                if (res.success) {
                    const modalId = form.closest('.shipping-modal-overlay')?.id || form.closest('.shipping-modal')?.id;
                    if (modalId) ShippingModal.close(modalId);
                    shippingShowNotification('تمت العملية بنجاح');
                    if (callback) callback(res);
                } else alert(res.data);
            });
        });
    },

    addInvoiceRow(desc = '', qty = 1, price = 0) {
        const container = document.getElementById('invoice-items-container');
        if (!container) return;
        const div = document.createElement('div');
        div.className = 'invoice-item-row';
        div.style.cssText = 'display:grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap:10px; margin-bottom:10px;';
        div.innerHTML = `
            <input type="text" placeholder="الوصف" class="shipping-input item-desc" value="${desc}">
            <input type="number" placeholder="الكمية" class="shipping-input item-qty" value="${qty}">
            <input type="number" placeholder="السعر" class="shipping-input item-price" value="${price}">
            <button type="button" class="shipping-btn" style="background:#e53e3e;" onclick="this.parentElement.remove(); BillingController.calculateInvoice();">حذف</button>
        `;
        container.appendChild(div);

        div.querySelectorAll('input').forEach(input => {
            input.oninput = () => { this.calculateInvoice(); this.updatePreview(); };
        });

        this.calculateInvoice();
    },

    calculateInvoice() {
        let subtotal = 0;
        document.querySelectorAll('.invoice-item-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            subtotal += qty * price;
        });
        const tax = subtotal * 0.15;
        const total = subtotal + tax;
        const subElem = document.getElementById('invoice-subtotal');
        if (subElem) subElem.innerText = subtotal.toFixed(2);
        const taxElem = document.getElementById('invoice-tax');
        if (taxElem) taxElem.innerText = tax.toFixed(2);
        const totalElem = document.getElementById('invoice-total');
        if (totalElem) totalElem.innerText = total.toFixed(2);
        this.updatePreview();
    },

    updatePreview() {
        const tbody = document.getElementById('preview-items-body');
        if (!tbody) return;
        let html = '';
        let rowsFound = false;
        document.querySelectorAll('.invoice-item-row').forEach(row => {
            const desc = row.querySelector('.item-desc').value;
            const qty = row.querySelector('.item-qty').value;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            if (desc) {
                rowsFound = true;
                html += `<tr><td style="padding:10px; border-bottom:1px solid #f7fafc;">${desc}</td><td style="text-align:center;">${qty}</td><td style="text-align:left;">${(qty * price).toFixed(2)}</td></tr>`;
            }
        });
        tbody.innerHTML = rowsFound ? html : '<tr><td colspan="3" style="text-align: center; padding: 40px; color: #a0aec0;">أضف بنوداً لعرض المعاينة</td></tr>';
        const totalElem = document.getElementById('invoice-total');
        if (totalElem) {
            document.getElementById('preview-total').innerText = totalElem.innerText + ' ' + (window.shippingCurrency || '');
        }
    },

    importShipment() {
        const num = document.getElementById('import-shipment-number').value;
        if(!num) return alert('يرجى إدخال رقم الشحنة');

        fetch(ajaxurl + '?action=shipping_get_shipment_tracking&number=' + num + '&nonce=' + shippingVars.shipmentNonce)
        .then(r => r.json()).then(res => {
            if(res.success) {
                const s = res.data;
                document.getElementById('invoice-customer-id').value = s.customer_id;
                document.getElementById('invoice-items-container').innerHTML = '';
                document.getElementById('invoice-shipment-ref').innerText = '(شحنة: ' + s.shipment_number + ')';

                const fd = new FormData();
                fd.append('action', 'shipping_estimate_cost');
                fd.append('nonce', shippingVars.nonce);
                fd.append('customer_id', s.customer_id);
                fd.append('classification', s.classification);
                fd.append('weight', s.weight);
                fd.append('distance', 100);
                fd.append('is_urgent', s.classification === 'express' ? 1 : 0);

                fetch(ajaxurl, { method:'POST', body: fd }).then(r=>r.json()).then(calcRes => {
                    if(calcRes.success) {
                        const b = calcRes.data.breakdown;
                        this.addInvoiceRow('تكلفة الشحن الأساسية (' + s.shipment_number + ')', 1, b.base);
                        this.addInvoiceRow('تكلفة الوزن (' + s.weight + ' كجم)', 1, b.weight);
                        this.addInvoiceRow('تكلفة المسافة والوجهة', 1, b.distance);
                        if(b.fees > 0) this.addInvoiceRow('رسوم إضافية وخدمات خاصة', 1, b.fees);
                        if(b.discount > 0) this.addInvoiceRow('خصومات وعروض ترويجية', 1, -b.discount);
                    }
                });
            } else alert('لم يتم العثور على الشحنة');
        });
    },

    openPaymentModal(inv) {
        document.getElementById('pay-inv-id').value = inv.id;
        document.getElementById('pay-amount').value = inv.total_amount;
        ShippingModal.open('payment-modal');
    },

    loadRules() {
        fetch(ajaxurl + '?action=shipping_get_pricing_rules&nonce=' + shippingVars.pricingNonce).then(r => r.json()).then(res => {
            const body = document.getElementById('rules-table-direct');
            if(!body) return;
            if (!res.data.length) { body.innerHTML = '<tr><td colspan="5" style="text-align:center;">لا توجد قواعد</td></tr>'; return; }
            body.innerHTML = res.data.map(r => `
                <tr>
                    <td><strong>${r.name}</strong></td>
                    <td>${parseFloat(r.base_cost).toFixed(2)} ${window.shippingCurrency || ''}</td>
                    <td>${parseFloat(r.cost_per_kg).toFixed(2)} / كجم</td>
                    <td>${parseFloat(r.min_cost).toFixed(2)} ${window.shippingCurrency || ''}</td>
                    <td><button class="shipping-btn" style="background:#e53e3e; padding:4px 8px; font-size:11px;" onclick="BillingController.deleteRule(${r.id})">حذف</button></td>
                </tr>
            `).join('');
        });
    },

    deleteRule(id) {
        if(!confirm('حذف القاعدة؟')) return;
        const fd = new FormData();
        fd.append('action', 'shipping_delete_pricing_rule');
        fd.append('nonce', shippingVars.pricingNonce);
        fd.append('id', id);
        fetch(ajaxurl, { method: 'POST', body: fd }).then(() => this.loadRules());
    },

    loadFees() {
        fetch(ajaxurl + '?action=shipping_get_additional_fees&nonce=' + shippingVars.pricingNonce).then(r => r.json()).then(res => {
            const body = document.getElementById('fees-table-direct');
            if(!body) return;
            if (!res.data.length) { body.innerHTML = '<tr><td colspan="5" style="text-align:center;">لا توجد رسوم</td></tr>'; return; }
            body.innerHTML = res.data.map(f => `
                <tr>
                    <td>${f.fee_name}</td>
                    <td>${parseFloat(f.fee_value).toFixed(2)}${f.fee_type === 'percentage' ? '%' : ' ' + (window.shippingCurrency || '')}</td>
                    <td>${f.fee_type === 'percentage' ? 'نسبة' : 'ثابت'}</td>
                    <td>${f.is_automatic == 1 ? 'نعم' : 'لا'}</td>
                    <td><button class="shipping-btn" style="background:#e53e3e; padding:4px 8px; font-size:11px;" onclick="BillingController.deleteFee(${f.id})">حذف</button></td>
                </tr>
            `).join('');
        });
    },

    deleteFee(id) {
        if(!confirm('حذف الرسم؟')) return;
        const fd = new FormData();
        fd.append('action', 'shipping_delete_additional_fee');
        fd.append('nonce', shippingVars.pricingNonce);
        fd.append('id', id);
        fetch(ajaxurl, { method: 'POST', body: fd }).then(() => this.loadFees());
    },

    initCharts() {
        const ctx = document.getElementById('revenueChart')?.getContext('2d');
        if(!ctx) return;
        fetch(ajaxurl + '?action=shipping_get_billing_report&nonce=' + shippingVars.billingNonce)
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                const stats = res.data;
                const labels = stats.monthly.map(s => s.month);
                const data = stats.monthly.map(s => s.total);

                document.getElementById('today-revenue').innerText = stats.summary.today.toFixed(2) + ' ' + (window.shippingCurrency || '');
                document.getElementById('month-revenue').innerText = stats.summary.month.toFixed(2) + ' ' + (window.shippingCurrency || '');

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels.length ? labels : ['No Data'],
                        datasets: [{
                            label: 'الإيرادات الشهرية',
                            data: data.length ? data : [0],
                            borderColor: '#F63049',
                            backgroundColor: 'rgba(246, 48, 73, 0.1)',
                            fill: true,
                            tension: 0.3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }
        });
    }
};

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('billing-invoice') || document.getElementById('revenueChart')) {
        BillingController.init();
    }
});
