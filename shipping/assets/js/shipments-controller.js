/**
 * Shipments Management Controller
 */

window.ShipmentsController = {
    arabCities: {
        "Saudi Arabia": ["الرياض", "جدة", "الدمام", "مكة المكرمة", "المدينة المنورة", "الخبر"],
        "UAE": ["دبي", "أبو ظبي", "الشارقة", "عجمان", "العين"],
        "Egypt": ["القاهرة", "الإسكندرية", "الجيزة", "بورسعيد", "المنصورة"],
        "Oman": ["مسقط", "صلالة", "صحار", "نزوى"],
        "Qatar": ["الدوحة", "الريان", "الوكرة", "الخور"],
        "Jordan": ["عمان", "إربد", "الزرقاء", "العقبة"]
    },

    init() {
        this.setupEventListeners();
        this.checkUrlParams();
    },

    quickTrack(number, id, btn) {
        ShippingState.setShipment(id);
        document.getElementById('res-number-unified').innerText = 'جاري التحميل...';
        document.getElementById('res-timeline-unified').innerHTML = '';
        ShippingModal.open('modal-shipment-tracking-unified');

        fetch(ajaxurl + '?action=shipping_get_shipment_tracking&number=' + encodeURIComponent(number) + '&nonce=' + shippingVars.shipmentNonce)
        .then(r => r.json()).then(res => {
            if (res.success) {
                const s = res.data;
                document.getElementById('res-number-unified').innerText = s.shipment_number;
                document.getElementById('res-status-unified').innerText = s.status;
                document.getElementById('res-route-unified').innerText = s.origin + ' ← ' + s.destination;

                let timelineHtml = '';
                if (s.events && s.events.length > 0) {
                    s.events.forEach((ev, idx) => {
                        timelineHtml += `
                            <div class="tracking-event ${idx === 0 ? 'active' : ''}">
                                <div style="font-weight:700; color:var(--shipping-dark-color);">${ev.status}</div>
                                <div style="font-size:12px; color:#64748b;">${ev.created_at} - ${ev.location || ''}</div>
                                <div style="font-size:13px; margin-top:5px;">${ev.description || ''}</div>
                            </div>
                        `;
                    });
                } else {
                    timelineHtml = '<p>لا توجد أحداث تتبع مسجلة.</p>';
                }
                document.getElementById('res-timeline-unified').innerHTML = timelineHtml;
            } else alert('لم يتم العثور على الشحنة');
        });
    },

    filterShipments() {
        const query = document.getElementById('shipment-search-query').value.toLowerCase();
        const status = document.getElementById('shipment-filter-status').value;
        const sort = document.getElementById('shipment-sort-order').value;
        const rows = Array.from(document.querySelectorAll('.shipment-row'));

        rows.forEach(row => {
            const text = (row.dataset.number + row.dataset.customer).toLowerCase();
            const rowStatus = row.dataset.status;
            const matchesQuery = !query || text.includes(query);
            const matchesStatus = !status || rowStatus === status;
            row.style.display = (matchesQuery && matchesStatus) ? '' : 'none';
        });

        // Sorting
        const tbody = document.getElementById('shipments-list-body');
        const sortedRows = rows.sort((a, b) => {
            if (sort === 'newest') return new Date(b.dataset.created) - new Date(a.dataset.created);
            if (sort === 'oldest') return new Date(a.dataset.created) - new Date(b.dataset.created);
            if (sort === 'weight_desc') return parseFloat(b.dataset.weight) - parseFloat(a.dataset.weight);
            if (sort === 'weight_asc') return parseFloat(a.dataset.weight) - parseFloat(b.dataset.weight);
            return 0;
        });
        sortedRows.forEach(row => tbody.appendChild(row));
    },

    resetFilters() {
        document.getElementById('shipment-search-form').reset();
        this.filterShipments();
    },

    printInvoice(id) {
        const modal = document.getElementById('modal-shipment-invoice-print');
        const container = document.getElementById('invoice-print-content');
        container.innerHTML = '<div style="text-align:center; padding:50px;"><span class="dashicons dashicons-update spin" style="font-size:40px; width:40px; height:40px;"></span><br>جاري تحضير الفاتورة...</div>';
        ShippingModal.open('modal-shipment-invoice-print');

        fetch(ajaxurl + '?action=shipping_get_shipment_full_details&id=' + id + '&nonce=' + shippingVars.shipmentNonce)
        .then(r => r.json()).then(res => {
            if (!res.success) { alert(res.data); return; }
            const d = res.data;
            const invoice = d.invoice;

            if (!invoice) {
                container.innerHTML = `
                    <div style="text-align:center; padding:40px; background:#fff; border-radius:12px; border:1px dashed #cbd5e0;">
                        <span class="dashicons dashicons-warning" style="font-size:48px; width:48px; height:48px; color:#e53e3e;"></span>
                        <h3 style="margin:15px 0;">لا توجد فاتورة مصدرة لهذه الشحنة</h3>
                        <p style="color:#64748b; margin-bottom:20px;">يرجى إصدار فاتورة من قسم الحسابات أولاً ليتم عرضها هنا.</p>
                        <button class="shipping-btn" style="width:auto;" onclick="window.location.href='${window.shippingAdminUrl}&shipping_tab=billing-payments&sub=invoice-gen'">انتقل لإصدار فاتورة</button>
                    </div>
                `;
                return;
            }

            const items = JSON.parse(invoice.items_json || '[]');
            let itemsHtml = items.map(item => `
                <tr style="border-bottom:1px solid #edf2f7;">
                    <td style="padding:12px;">${item.description}</td>
                    <td style="padding:12px; text-align:center;">${item.quantity}</td>
                    <td style="padding:12px; text-align:left;">${parseFloat(item.price).toFixed(2)}</td>
                </tr>
            `).join('');

            container.innerHTML = `
                <div id="printable-invoice-direct" style="background:#fff; padding:40px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.05); font-family:'Rubik', sans-serif;" dir="rtl">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:40px;">
                        <div>
                            <h2 style="margin:0; color:var(--shipping-dark-color);">فاتورة ضريبية</h2>
                            <div style="font-size:14px; color:#718096; margin-top:5px;">رقم الفاتورة: <strong>${invoice.invoice_number}</strong></div>
                        </div>
                        <div style="text-align:left;">
                            <strong>التاريخ:</strong> ${invoice.created_at}<br>
                            <strong>الحالة:</strong> <span style="color:${invoice.status === 'paid' ? '#38a169' : '#e53e3e'}; font-weight:800;">${invoice.status.toUpperCase()}</span>
                        </div>
                    </div>
                    <div style="margin-bottom:30px; padding-bottom:20px; border-bottom:2px solid #f7fafc;">
                        <strong>بيانات العميل:</strong><br>
                        ${d.shipment.customer_name}<br>
                        ${d.shipment.customer_phone || ''}
                    </div>
                    <table style="width:100%; border-collapse:collapse; margin-bottom:30px;">
                        <thead><tr style="background:#f7fafc; text-align:right;"><th style="padding:12px;">الوصف</th><th style="padding:12px; text-align:center;">الكمية</th><th style="padding:12px; text-align:left;">السعر (SAR)</th></tr></thead>
                        <tbody>${itemsHtml}</tbody>
                    </table>
                    <div style="width:250px; margin-right:auto; text-align:left; border-top:2px solid var(--shipping-primary-color); padding-top:15px;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:5px;"><span>المجموع الفرعي:</span> <strong>${parseFloat(invoice.subtotal).toFixed(2)}</strong></div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:5px;"><span>الضريبة (15%):</span> <strong>${parseFloat(invoice.tax_amount).toFixed(2)}</strong></div>
                        <div style="display:flex; justify-content:space-between; font-size:1.3em; font-weight:900; color:var(--shipping-primary-color); margin-top:10px;"><span>الإجمالي:</span> <strong>${parseFloat(invoice.total_amount).toFixed(2)}</strong></div>
                    </div>
                    <div style="margin-top:50px; text-align:center; font-size:11px; color:#a0aec0; border-top:1px solid #eee; padding-top:20px;">
                        هذه فاتورة صادرة آلياً من نظام الشحن والمتابعة الموحد
                    </div>
                </div>
            `;
        });
    },

    executePrint() {
        const content = document.getElementById('printable-invoice-direct');
        if (!content) return;
        const win = window.open('', '_blank');
        win.document.write('<html><head><title>Print Invoice</title>');
        win.document.write('<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;700&display=swap">');
        win.document.write('<style>body{font-family:"Rubik", sans-serif; padding:20px;} @media print{.no-print{display:none;}}</style>');
        win.document.write('</head><body>');
        win.document.write(content.innerHTML);
        win.document.write('</body></html>');
        win.document.close();
        setTimeout(() => { win.print(); win.close(); }, 500);
    },

    processBulkDirect() {
        const rowsRaw = document.getElementById('bulk-rows-input').value;
        if (!rowsRaw) return alert('يرجى إدخال البيانات');
        try { JSON.parse(rowsRaw); } catch(e) { return alert('تنسيق JSON غير صحيح'); }

        const fd = new FormData();
        fd.append('action', 'shipping_bulk_shipments');
        fd.append('nonce', shippingVars.shipmentNonce);
        fd.append('rows', rowsRaw);

        fetch(ajaxurl, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if (res.success) {
                shippingShowNotification('تمت معالجة ' + res.data + ' شحنة بنجاح');
                location.reload();
            } else alert(res.data);
        });
    },

    openEditModal(s) {
        // Simple mapping for now, assuming creation form can be reused or specialized
        const f = document.getElementById('shipping-create-shipment-form');
        if (!f) return;
        f.customer_id.value = s.customer_id;
        f.weight.value = s.weight;
        f.classification.value = s.classification;
        // ... (Would need to handle country/city split if needed)
        ShippingModal.open('modal-create-shipment');
        // Update title to Edit
        document.querySelector('#modal-create-shipment h3').innerText = 'تعديل بيانات الشحنة: ' + s.shipment_number;
    },

    setupEventListeners() {
        const form = document.getElementById('shipping-create-shipment-form');
        if (form) {
            form.addEventListener('submit', (e) => this.handleCreateShipment(e));

            // Real-time cost calculation triggers
            form.querySelectorAll('input, select').forEach(input => {
                if (['weight', 'distance', 'classification', 'is_urgent', 'is_insured'].includes(input.name)) {
                    input.addEventListener('change', () => this.debounceCalculateCost());
                }
            });

            // Country/City dropdown sync
            form.querySelectorAll('.origin-country-select, .destination-country-select').forEach(select => {
                select.addEventListener('change', (e) => {
                    const type = e.target.name.includes('origin') ? 'origin-city-select' : 'destination-city-select';
                    this.updateCities(e.target, type);
                });
            });
        }
    },

    checkUrlParams() {
        const urlParams = new URLSearchParams(window.location.search);
        const orderId = urlParams.get('order_id');
        if (orderId) {
            this.openCreationModal();
            this.loadOrderDataForShipment(orderId);
        }

        if (urlParams.has('trigger_add')) {
            this.openCreationModal();
        }

        const dossierId = urlParams.get('view_dossier');
        if (dossierId) {
            this.viewFullDossier(dossierId);
        }
    },

    openCreationModal() {
        const f = document.getElementById('shipping-create-shipment-form');
        if (f && ShippingState.selectedCustomer) {
            f.customer_id.value = ShippingState.selectedCustomer;
        }
        ShippingModal.open('modal-create-shipment');
        // Initial city load
        document.querySelectorAll('.origin-country-select, .destination-country-select').forEach(s => {
            this.updateCities(s, s.name.includes('origin') ? 'origin-city-select' : 'destination-city-select');
        });
    },

    updateCities(countrySelect, citySelectClass) {
        const country = countrySelect.value;
        const citySelects = document.querySelectorAll('.' + citySelectClass);
        const cities = this.arabCities[country] || [];

        citySelects.forEach(select => {
            select.innerHTML = '<option value="">اختر المدينة...</option>' +
                cities.map(c => `<option value="${c}">${c}</option>`).join('');
        });
    },

    loadOrderDataForShipment(orderId) {
        fetch(ajaxurl + '?action=shipping_get_orders&id=' + orderId + '&nonce=' + shippingVars.orderNonce)
        .then(r => r.json()).then(res => {
            if (res.success && res.data.length) {
                const o = res.data[0];
                const f = document.getElementById('shipping-create-shipment-form');
                if (f) {
                    document.getElementById('shipment-order-id-input').value = orderId;
                    f.customer_id.value = o.customer_id;
                }
            }
        });
    },

    debounceCalculateCost() {
        if (this.costTimeout) clearTimeout(this.costTimeout);
        this.costTimeout = setTimeout(() => this.calculateCost(), 500);
    },

    calculateCost() {
        const form = document.getElementById('shipping-create-shipment-form');
        const weight = form.weight.value;
        const distance = form.distance.value;
        if (!weight || !distance) return;

        const loader = document.getElementById('cost-loader');
        const details = document.getElementById('cost-details');
        if (loader) loader.style.display = 'block';
        if (details) details.style.display = 'none';

        const fd = new FormData(form);
        fd.append('action', 'shipping_estimate_cost');
        fd.append('nonce', shippingVars.nonce);

        fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json()).then(res => {
            if (loader) loader.style.display = 'none';
            if (details) details.style.display = 'block';

            if (res.success) {
                const data = res.data;
                document.getElementById('display-cost').innerText = data.total_cost.toFixed(2);
                document.getElementById('shipment-estimated-cost-input').value = data.total_cost;

                let html = '<div style="display:grid; gap:8px;">';
                html += `<div style="display:flex; justify-content:space-between;"><span>التكلفة الأساسية:</span> <strong>${data.breakdown.base.toFixed(2)}</strong></div>`;
                html += `<div style="display:flex; justify-content:space-between;"><span>وزن (${weight} كجم):</span> <strong>${data.breakdown.weight.toFixed(2)}</strong></div>`;
                html += `<div style="display:flex; justify-content:space-between;"><span>مسافة (${distance} كم):</span> <strong>${data.breakdown.distance.toFixed(2)}</strong></div>`;
                if (data.breakdown.fees > 0) html += `<div style="display:flex; justify-content:space-between; color:#c53030;"><span>إضافات:</span> <strong>+ ${data.breakdown.fees.toFixed(2)}</strong></div>`;
                if (data.breakdown.discount > 0) html += `<div style="display:flex; justify-content:space-between; color:#2f855a;"><span>خصومات:</span> <strong>- ${data.breakdown.discount.toFixed(2)}</strong></div>`;
                html += '</div>';
                document.getElementById('cost-breakdown-list').innerHTML = html;
            }
        });
    },

    handleCreateShipment(e) {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('button[type="submit"]');
        if (!btn) return;

        btn.disabled = true;
        btn.innerHTML = '<span class="dashicons dashicons-update spin"></span> جاري المعالجة...';

        const fd = new FormData(form);

        // Manual mapping if selects are used
        const oc = form.querySelector('[name="origin_country"]')?.value || '';
        const oci = form.querySelector('[name="origin_city"]')?.value || '';
        const dc = form.querySelector('[name="destination_country"]')?.value || '';
        const dci = form.querySelector('[name="destination_city"]')?.value || '';

        if (oc && oci) fd.set('origin', oc + ', ' + oci);
        if (dc && dci) fd.set('destination', dc + ', ' + dci);

        fd.append('action', 'shipping_create_shipment');
        fd.append('nonce', shippingVars.shipmentNonce);

        fetch(ajaxurl, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if (res.success) {
                const d = res.data;
                ShippingModal.close('modal-create-shipment');
                form.reset();

                // Show Success & Print Selection Modal
                this.showCreationSuccess(d);

                // If on shipments list page, refresh
                if (document.getElementById('shipment-management-unified')) {
                    setTimeout(() => location.reload(), 1500);
                }
            } else {
                btn.disabled = false;
                btn.innerText = 'تأكيد وإنشاء الشحنة';
                alert(res.data);
            }
        });
    },

    showCreationSuccess(data) {
        const modalId = 'modal-shipment-success-actions';
        let modal = document.getElementById(modalId);
        if (!modal) {
            const div = document.createElement('div');
            div.id = modalId;
            div.className = 'shipping-modal-overlay';
            div.innerHTML = `
                <div class="shipping-modal-content" style="max-width: 500px; text-align: center;">
                    <div class="shipping-modal-header"><h3>تم إنشاء الشحنة بنجاح</h3></div>
                    <div class="shipping-modal-body" style="padding: 30px;">
                        <div style="font-size: 50px; color: #38a169; margin-bottom: 20px;">✓</div>
                        <h4 id="success-ship-num" style="margin:0 0 10px 0; color: var(--shipping-primary-color);"></h4>
                        <p style="color: #64748b; margin-bottom: 25px;">تم توليد الفاتورة والباركود آلياً. يرجى اختيار الإجراء التالي:</p>
                        <div style="display: grid; gap: 10px;">
                            <button id="btn-print-sticker" class="shipping-btn" style="background: #2d3748;">طباعة ملصق الشحنة (Sticker)</button>
                            <button id="btn-print-invoice" class="shipping-btn" style="background: #4a5568;">طباعة الفاتورة الضريبية</button>
                            <button onclick="location.reload()" class="shipping-btn shipping-btn-outline">العودة للجدول الرئيسي</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(div);
            modal = div;
        }

        document.getElementById('success-ship-num').innerText = data.shipment_number;
        document.getElementById('btn-print-sticker').onclick = () => this.printSticker(data.shipment_id);
        document.getElementById('btn-print-invoice').onclick = () => this.printInvoice(data.shipment_id);

        ShippingModal.open(modalId);
    },

    printSticker(id) {
        const modalId = 'modal-shipment-sticker-print';
        let modal = document.getElementById(modalId);
        if (!modal) {
            const div = document.createElement('div');
            div.id = modalId;
            div.className = 'shipping-modal-overlay';
            div.innerHTML = `
                <div class="shipping-modal-content" style="max-width: 450px;">
                    <div class="shipping-modal-header"><h3>ملصق الشحنة</h3><button class="shipping-modal-close" onclick="ShippingModal.close('${modalId}')">&times;</button></div>
                    <div id="sticker-print-content" style="padding: 20px; background: #fff;"></div>
                    <div class="shipping-modal-footer" style="text-align: left; padding: 15px 25px;">
                        <button class="shipping-btn" onclick="ShipmentsController.executeStickerPrint()">طباعة الآن</button>
                    </div>
                </div>
            `;
            document.body.appendChild(div);
            modal = div;
        }

        const container = document.getElementById('sticker-print-content');
        container.innerHTML = '<div style="text-align:center; padding:30px;">جاري التجهيز...</div>';
        ShippingModal.open(modalId);

        fetch(ajaxurl + '?action=shipping_get_shipment_full_details&id=' + id + '&nonce=' + shippingVars.shipmentNonce)
        .then(r => r.json()).then(res => {
            if (!res.success) return alert(res.data);
            const s = res.data.shipment;

            container.innerHTML = `
                <div id="printable-sticker-direct" style="width: 100mm; background: #fff; color: #000; border: 2px solid #000; padding: 5mm; font-family: 'Rubik', sans-serif; direction: rtl;">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #000; padding-bottom: 3mm; margin-bottom: 3mm;">
                        <div style="font-weight: 900; font-size: 1.2em;">${window.shippingName || 'SHIPPING SYSTEM'}</div>
                        <div style="font-weight: 800; font-size: 0.9em;">${s.shipment_number.startsWith('INT') ? 'INTERNATIONAL' : 'LOCAL'}</div>
                    </div>

                    <div style="text-align: center; margin-bottom: 4mm; padding: 4mm; background: #eee;">
                        <div style="font-size: 1.8em; font-weight: 900; letter-spacing: 1px;">${s.shipment_number}</div>
                        <div style="font-size: 10px; margin-top: 2mm;">(Scan for tracking)</div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 4mm; margin-bottom: 4mm;">
                        <div style="border: 1px solid #000; padding: 2mm;">
                            <div style="font-size: 10px; font-weight: 700; color: #555;">SHIP FROM:</div>
                            <div style="font-size: 13px; font-weight: 800; margin-top: 1mm;">${s.origin}</div>
                        </div>
                        <div style="border: 1px solid #000; padding: 2mm;">
                            <div style="font-size: 10px; font-weight: 700; color: #555;">SHIP TO:</div>
                            <div style="font-size: 13px; font-weight: 800; margin-top: 1mm;">${s.destination}</div>
                        </div>
                    </div>

                    <div style="border: 1px solid #000; padding: 3mm; margin-bottom: 4mm;">
                        <div style="font-size: 11px;"><strong>CUSTOMER:</strong> ${s.customer_name}</div>
                        <div style="display: flex; justify-content: space-between; margin-top: 2mm;">
                            <div style="font-size: 11px;"><strong>WEIGHT:</strong> ${s.weight} KG</div>
                            <div style="font-size: 11px;"><strong>DATE:</strong> ${s.created_at.split(' ')[0]}</div>
                        </div>
                    </div>

                    <div style="text-align: center; border-top: 1px dashed #000; padding-top: 3mm; font-size: 10px; font-weight: 700;">
                        OFFICIAL SHIPPING DOCUMENT - DO NOT TAMPER
                    </div>
                </div>
            `;
        });
    },

    executeStickerPrint() {
        const content = document.getElementById('printable-sticker-direct');
        if (!content) return;
        const win = window.open('', '_blank');
        win.document.write('<html><head><title>Print Sticker</title>');
        win.document.write('<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;700;900&display=swap">');
        win.document.write('<style>body{margin:0; padding:0;} @page { size: 100mm 150mm; margin: 0; }</style>');
        win.document.write('</head><body>');
        win.document.write(content.innerHTML);
        win.document.write('</body></html>');
        win.document.close();
        setTimeout(() => { win.print(); win.close(); }, 500);
    },

    trackShipment() {
        const num = document.getElementById('track-number').value;
        if (!num) return alert('يرجى إدخال رقم الشحنة');

        fetch(ajaxurl + '?action=shipping_get_shipment_tracking&number=' + encodeURIComponent(num) + '&nonce=' + shippingVars.shipmentNonce)
        .then(r => r.json()).then(res => {
            if (res.success) {
                const s = res.data;
                document.getElementById('res-number').innerText = s.shipment_number;
                document.getElementById('res-status').innerText = s.status;
                document.getElementById('res-route').innerText = s.origin + ' ← ' + s.destination;

                let timelineHtml = '';
                if (s.events && s.events.length > 0) {
                    s.events.forEach((ev, idx) => {
                        timelineHtml += `
                            <div class="tracking-event ${idx === 0 ? 'active' : ''}">
                                <div style="font-weight:700; color:var(--shipping-dark-color);">${ev.status}</div>
                                <div style="font-size:12px; color:#64748b;">${ev.created_at} - ${ev.location || ''}</div>
                                <div style="font-size:13px; margin-top:5px;">${ev.description || ''}</div>
                            </div>
                        `;
                    });
                } else {
                    timelineHtml = '<p>لا توجد أحداث تتبع مسجلة.</p>';
                }
                document.getElementById('res-timeline').innerHTML = timelineHtml;
                document.getElementById('tracking-result').style.display = 'block';
            } else alert('لم يتم العثور على الشحنة');
        });
    },

    viewFullDossier(id) {
        const modal = document.getElementById('modal-full-dossier');
        const container = document.getElementById('dossier-content');
        container.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding:50px;"><span class="dashicons dashicons-update spin" style="font-size:40px; width:40px; height:40px;"></span><br>جاري تجميع ملف البيانات...</div>';
        ShippingModal.open('modal-full-dossier');

        fetch(ajaxurl + '?action=shipping_get_shipment_full_details&id=' + id + '&nonce=' + shippingVars.shipmentNonce)
        .then(r => r.json()).then(res => {
            if (!res.success) { alert(res.data); return; }
            const d = res.data;
            document.getElementById('dossier-num').innerText = d.shipment.shipment_number;

            let html = `
                <div class="shipping-card" style="margin:0;">
                    <h5 style="color:var(--shipping-primary-color); border-bottom:1px solid #eee; padding-bottom:10px;">تفاصيل الشحنة واللوجستيات</h5>
                    <div style="font-size:13px; display:grid; gap:8px; margin-top:10px;">
                        <div><strong>العميل:</strong> ${d.shipment.customer_name}</div>
                        <div><strong>المسار:</strong> ${d.shipment.route_name || 'غير محدد'}</div>
                        <div><strong>المركبة:</strong> ${d.shipment.vehicle_number || 'غير محدد'}</div>
                        <div><strong>الوزن:</strong> ${d.shipment.weight} كجم</div>
                        <div><strong>الحالة الحالية:</strong> <span class="shipping-badge">${d.shipment.status}</span></div>
                        <div style="margin-top:10px; padding-top:10px; border-top:1px dashed #eee;">
                            <strong>من:</strong> ${d.shipment.origin}<br>
                            <strong>إلى:</strong> ${d.shipment.destination}
                        </div>
                    </div>
                </div>
                <div class="shipping-card" style="margin:0;">
                    <h5 style="color:#3182ce; border-bottom:1px solid #eee; padding-bottom:10px;">الطلب المرتبط والفواتير</h5>
                    <div style="font-size:13px; display:grid; gap:8px; margin-top:10px;">
                        ${d.order ? `<div><strong>رقم الطلب:</strong> ${d.order.order_number}</div>` : '<div style="color:#e53e3e;">لا يوجد طلب مرتبط</div>'}
                        ${d.invoice ? `<div style="margin-top:10px; padding:10px; background:#f0fff4; border-radius:8px;"><strong>الفاتورة:</strong> ${d.invoice.invoice_number}<br><strong>المبلغ:</strong> ${parseFloat(d.invoice.total_amount).toFixed(2)} ${window.shippingCurrency || ''}</div>` : '<div>لا توجد فاتورة</div>'}
                    </div>
                </div>
                <div class="shipping-card" style="margin:0; grid-column: 1 / -1;">
                    <h5 style="color:#805ad5; border-bottom:1px solid #eee; padding-bottom:10px;">سجل التتبع التاريخي</h5>
                    <div style="max-height:200px; overflow-y:auto; font-size:12px; margin-top:10px;">
                        ${d.events.map(ev => `
                            <div style="display:flex; gap:10px; margin-bottom:5px; padding-bottom:5px; border-bottom:1px solid #f8f9fa;">
                                <span style="color:#718096; white-space:nowrap;">${ev.created_at}</span>
                                <strong>${ev.status}:</strong> <span>${ev.location || ''} - ${ev.description || ''}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
            container.innerHTML = html;
        });
    },

    viewLogs(id, num) {
        document.getElementById('log-shipment-num').innerText = num;
        const container = document.getElementById('shipment-logs-timeline');
        container.innerHTML = '<p style="text-align:center;">جاري تحميل السجل...</p>';
        ShippingModal.open('modal-shipment-logs');

        fetch(ajaxurl + '?action=shipping_get_shipment_logs&id=' + id + '&nonce=' + shippingVars.shipmentNonce)
        .then(r => r.json()).then(res => {
            if (!res.data.length) { container.innerHTML = '<p>لا توجد سجلات لهذه الشحنة</p>'; return; }
            container.innerHTML = res.data.map(l => `
                <div class="timeline-item" style="border-right: 2px solid #edf2f7; padding-right: 20px; position: relative; padding-bottom: 15px; margin-right: 10px; text-align: right;">
                    <div style="position: absolute; right: -7px; top: 5px; width: 12px; height: 12px; border-radius: 50%; background: var(--shipping-primary-color); border: 2px solid #fff;"></div>
                    <div style="font-weight: 700; font-size: 13px;">${l.action}</div>
                    <div style="font-size: 11px; color: #718096;">بواسطة: ${l.display_name} | ${l.created_at}</div>
                    <div style="font-size: 12px; margin-top: 5px; background: #f8fafc; padding: 5px; border-radius: 5px;">
                        <span style="color:#718096">من:</span> ${l.old_value || '---'} <br>
                        <span style="color:#718096">إلى:</span> ${l.new_value}
                    </div>
                </div>
            `).join('');
        });
    },

    processBulk() {
        const rowsRaw = document.getElementById('bulk-rows').value;
        if (!rowsRaw) return alert('يرجى إدخال البيانات');
        try { JSON.parse(rowsRaw); } catch(e) { return alert('تنسيق JSON غير صحيح'); }

        const fd = new FormData();
        fd.append('action', 'shipping_bulk_shipments');
        fd.append('nonce', shippingVars.shipmentNonce);
        fd.append('rows', rowsRaw);

        fetch(ajaxurl, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if (res.success) {
                shippingShowNotification('تمت المعالجة بنجاح');
                location.reload();
            } else alert(res.data);
        });
    }
};

