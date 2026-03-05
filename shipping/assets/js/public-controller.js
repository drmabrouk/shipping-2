/**
 * Public Access Controller
 * Handles Tracking and Verification for non-logged users
 */

window.PublicController = {
    trackShipment() {
        const numInput = document.getElementById('public-track-number');
        if (!numInput) return;
        const num = numInput.value.trim();
        if (!num) return;

        const resultDiv = document.getElementById('public-tracking-result');
        const errorDiv = document.getElementById('pub-res-error');

        resultDiv.style.display = 'none';
        errorDiv.style.display = 'none';

        fetch(ajaxurl + '?action=shipping_public_tracking_ajax&number=' + encodeURIComponent(num) + '&nonce=' + (window.shippingVars ? shippingVars.publicNonce : ''))
        .then(r => r.json()).then(res => {
            if (res.success) {
                const s = res.data;
                document.getElementById('pub-res-number').innerText = s.shipment_number;
                document.getElementById('pub-res-status').innerText = s.status;
                document.getElementById('pub-res-origin').innerText = s.origin;
                document.getElementById('pub-res-destination').innerText = s.destination;

                let html = '';
                if (s.events && s.events.length > 0) {
                    s.events.forEach((ev, idx) => {
                        html += `
                            <div style="position: relative; padding-bottom: 30px;">
                                <div style="position: absolute; right: -27px; top: 5px; width: 14px; height: 14px; border-radius: 50%; background: ${idx === 0 ? 'var(--shipping-primary-color)' : '#cbd5e0'}; border: 3px solid #fff; box-shadow: 0 0 0 2px ${idx === 0 ? 'rgba(246, 48, 73, 0.2)' : '#f1f5f9'}; z-index: 2;"></div>
                                ${idx < s.events.length - 1 ? '<div style="position: absolute; right: -21px; top: 20px; bottom: 0; width: 2px; background: #e2e8f0; z-index: 1;"></div>' : ''}
                                <div style="font-weight: 800; color: #111F35; margin-bottom: 5px;">${ev.status}</div>
                                <div style="font-size: 12px; color: #94a3b8; margin-bottom: 8px;">${ev.created_at} ${ev.location ? ' - ' + ev.location : ''}</div>
                                <div style="font-size: 13px; color: #4a5568; line-height: 1.5; background: #f8fafc; padding: 10px 15px; border-radius: 8px; border: 1px solid #edf2f7;">${ev.description || ''}</div>
                            </div>
                        `;
                    });
                } else {
                    html = '<p style="text-align:center; color:#94a3b8;">تم إنشاء الشحنة، بانتظار تحديثات المسار.</p>';
                }
                document.getElementById('pub-res-timeline').innerHTML = html;
                resultDiv.style.display = 'block';
            } else {
                errorDiv.style.display = 'block';
            }
        });
    },

    verifyDocument(e) {
        if (e) e.preventDefault();
        const val = document.getElementById('shipping-verify-value').value;
        const type = document.getElementById('shipping-verify-type').value;
        const results = document.getElementById('shipping-verify-results');
        const loading = document.getElementById('shipping-verify-loading');

        results.innerHTML = '';
        loading.style.display = 'flex';

        const fd = new FormData();
        fd.append('action', 'shipping_verify_document');
        fd.append('nonce', window.shippingVars ? shippingVars.publicNonce : '');
        fd.append('search_value', val);
        fd.append('search_type', type);

        fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            loading.style.display = 'none';
            if (res.success) {
                this.renderVerificationResults(res.data);
            } else {
                results.innerHTML = `<div style="background: #fff5f5; color: #c53030; padding: 20px; border-radius: 10px; border: 1px solid #feb2b2; text-align: center; font-weight: 600;">${res.data}</div>`;
            }
        });
    },

    renderVerificationResults(data) {
        const results = document.getElementById('shipping-verify-results');
        const today = new Date();
        let finalHtml = '';

        for (let k in data) {
            const doc = data[k];
            let statusClass = 'shipping-verify-status-valid';
            let statusLabel = 'صالح / ساري';

            if (doc.expiry) {
                const expiry = new Date(doc.expiry);
                if (expiry < today) {
                    statusClass = 'shipping-verify-status-invalid';
                    statusLabel = 'منتهي الصلاحية';
                }
            }

            let html = `
                <div class="shipping-verify-card">
                    <div class="shipping-verify-card-header">
                        <h3 style="margin: 0; font-weight: 800; color: var(--shipping-primary-color); font-size: 1.1em;">${doc.label}</h3>
                        <span class="shipping-badge ${statusClass === 'shipping-verify-status-valid' ? 'shipping-badge-high' : 'shipping-badge-urgent'}" style="font-size: 11px;">${statusLabel}</span>
                    </div>
                    <div class="shipping-verify-grid">
            `;

            if (k === 'customership') {
                html += `
                    <div class="shipping-verify-item"><label>الاسم</label><span>${doc.name}</span></div>
                    <div class="shipping-verify-item"><label>رقم القيد</label><span>${doc.number}</span></div>
                    <div class="shipping-verify-item"><label>تاريخ الانتهاء</label><span class="${statusClass}">${doc.expiry || 'غير محدد'}</span></div>
                `;
            }

            html += `</div></div>`;
            finalHtml += html;
        }
        results.innerHTML = finalHtml;
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const verifyForm = document.getElementById('shipping-verify-form');
    if (verifyForm) {
        verifyForm.addEventListener('submit', (e) => PublicController.verifyDocument(e));
    }
});
