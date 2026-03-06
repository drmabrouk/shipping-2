/**
 * General Admin Controller
 * Handles system settings, logs, pages, and alerts
 */

window.AdminController = {
    init() {
        this.setupEventListeners();
        this.initSidebarState();
        this.pollAlerts();
        setInterval(() => this.pollAlerts(), 30000);
    },

    initSidebarState() {
        const isCollapsed = localStorage.getItem('shipping_sidebar_collapsed') === 'true';
        const sidebar = document.querySelector('.shipping-sidebar');
        if (sidebar) {
            if (isCollapsed) {
                sidebar.classList.add('collapsed');
            } else {
                sidebar.classList.remove('collapsed');
            }
        }
    },

    toggleSidebar() {
        const sidebar = document.querySelector('.shipping-sidebar');
        if (!sidebar) return;

        sidebar.classList.toggle('collapsed');
        const isCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('shipping_sidebar_collapsed', isCollapsed);

        // Trigger a resize event to help any layout components (like charts) adjust
        window.dispatchEvent(new Event('resize'));
    },

    openSubTab(tab, btn) {
        const container = btn.closest('.shipping-notifications-settings') || btn.closest('.shipping-main-panel');
        if (!container) return;

        container.querySelectorAll('.shipping-sub-tab').forEach(t => t.style.display = 'none');
        const target = document.getElementById(tab);
        if (target) target.style.display = 'block';

        btn.parentElement.querySelectorAll('.shipping-tab-btn').forEach(b => b.classList.remove('shipping-active'));
        btn.classList.add('shipping-active');
    },

    setupEventListeners() {
        const toggleBtn = document.getElementById('shipping-sidebar-toggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => this.toggleSidebar());
        }

        this.bindForm('shipping-edit-page-form', 'shipping_save_page_settings', () => location.reload(), shippingVars.nonce);
        this.bindForm('shipping-add-article-form', 'shipping_add_article', () => location.reload(), shippingVars.nonce);
        this.bindForm('shipping-notif-template-form', 'shipping_save_template_ajax', () => shippingShowNotification('تم حفظ القالب بنجاح'), shippingVars.nonce);
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
                    if (callback) callback(res);
                } else alert(res.data);
            });
        });
    },

    // --- Logs ---
    viewLogDetails(log) {
        const detailsBody = document.getElementById('log-details-body');
        if (!detailsBody) return;
        let detailsText = log.details;

        if (log.details.startsWith('ROLLBACK_DATA:')) {
            try {
                const data = JSON.parse(log.details.replace('ROLLBACK_DATA:', ''));
                detailsText = `<pre style="background:#f4f4f4; padding:10px; border-radius:5px; font-size:11px; overflow-x:auto;">${JSON.stringify(data, null, 2)}</pre>`;
            } catch(e) {
                detailsText = log.details;
            }
        }

        detailsBody.innerHTML = `
            <div style="display:grid; gap:15px;">
                <div><strong>المشغل:</strong> ${log.display_name || 'نظام'}</div>
                <div><strong>الوقت:</strong> ${log.created_at}</div>
                <div><strong>الإجراء:</strong> <span class="shipping-badge shipping-badge-low">${log.action}</span></div>
                <div><strong>بيانات العملية:</strong><br>${detailsText}</div>
            </div>
        `;
        ShippingModal.open('log-details-modal');
    },

    rollbackLog(logId) {
        if (!confirm('هل أنت متأكد من رغبتك في استعادة هذه البيانات؟ سيتم محاولة عكس العملية.')) return;
        const fd = new FormData();
        fd.append('action', 'shipping_rollback_log_ajax');
        fd.append('log_id', logId);
        fd.append('nonce', shippingVars.nonce);

        fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json()).then(res => {
            if (res.success) {
                shippingShowNotification('تمت الاستعادة بنجاح');
                setTimeout(() => location.reload(), 500);
            } else alert('خطأ: ' + res.data);
        });
    },

    deleteLog(logId) {
        if (!confirm('هل أنت متأكد من حذف هذا السجل؟')) return;
        const fd = new FormData();
        fd.append('action', 'shipping_delete_log');
        fd.append('log_id', logId);
        fd.append('nonce', shippingVars.nonce);
        fetch(ajaxurl, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if (res.success) location.reload();
        });
    },

    deleteAllLogs() {
        if (!confirm('هل أنت متأكد من مسح كافة السجلات؟')) return;
        const fd = new FormData();
        fd.append('action', 'shipping_clear_all_logs');
        fd.append('nonce', shippingVars.nonce);
        fetch(ajaxurl, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if (res.success) location.reload();
        });
    },

    // --- System ---
    resetSystem() {
        const password = prompt('تحذير نهائي: سيتم مسح كافة بيانات النظام بالكامل. يرجى إدخال كلمة مرور مدير النظام للتأكيد:');
        if (!password) return;
        if (!confirm('هل أنت متأكد تماماً؟ لا يمكن التراجع عن هذا الإجراء.')) return;

        const fd = new FormData();
        fd.append('action', 'shipping_reset_system_ajax');
        fd.append('admin_password', password);
        fd.append('nonce', shippingVars.nonce);

        fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json()).then(res => {
            if (res.success) {
                alert('تمت إعادة تهيئة النظام بنجاح.');
                location.reload();
            } else alert('خطأ: ' + res.data);
        });
    },

    openMediaUploader(inputId) {
        const frame = wp.media({
            title: 'اختر شعار Shipping',
            button: { text: 'استخدام هذا الشعار' },
            multiple: false
        });
        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            document.getElementById(inputId).value = attachment.url;
        });
        frame.open();
    },

    toggleUserDropdown() {
        const menu = document.getElementById('shipping-user-dropdown-menu');
        if (!menu) return;
        if (menu.style.display === 'none' || menu.style.display === '') {
            menu.style.display = 'block';
            document.getElementById('shipping-profile-view').style.display = 'block';
            document.getElementById('shipping-profile-edit').style.display = 'none';
            const notif = document.getElementById('shipping-notifications-menu');
            if (notif) notif.style.display = 'none';
        } else {
            menu.style.display = 'none';
        }
    },

    toggleNotifications() {
        const menu = document.getElementById('shipping-notifications-menu');
        if (!menu) return;
        if (menu.style.display === 'none' || menu.style.display === '') {
            menu.style.display = 'block';
            const userMenu = document.getElementById('shipping-user-dropdown-menu');
            if (userMenu) userMenu.style.display = 'none';
            this.pollAlerts(); // Refresh when opening
        } else {
            menu.style.display = 'none';
        }
    },

    pollAlerts() {
        fetch(ajaxurl + '?action=shipping_get_alerts&nonce=' + (shippingVars.nonce || ''))
        .then(r => r.json()).then(res => {
            if (res.success) {
                this.renderAlerts(res.data);
            }
        });
    },

    renderAlerts(alerts) {
        const container = document.getElementById('shipping-notifications-menu');
        const badge = document.querySelector('.shipping-icon-dot');
        if (!container) return;

        if (alerts.length > 0) {
            if (badge) badge.style.display = 'block';
            let html = '<h4 style="margin: 0 0 10px 0; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 8px;">التنبيهات والإشعارات</h4>';
            html += alerts.map(a => `
                <div style="font-size: 12px; padding: 10px; border-bottom: 1px solid #f9fafb; color: #4a5568; display: flex; gap: 10px; align-items: flex-start; background: ${a.severity === 'critical' ? '#fff5f5' : 'transparent'};">
                    <span class="dashicons dashicons-${a.severity === 'critical' ? 'warning' : 'megaphone'}" style="font-size: 16px; color: ${a.severity === 'critical' ? '#e53e3e' : 'var(--shipping-primary-color)'};"></span>
                    <div style="flex:1;">
                        <div style="font-weight:700;">${a.title}</div>
                        <div style="margin-top:2px;">${a.message}</div>
                        <button onclick="AdminController.acknowledgeAlert(${a.id})" style="border:none; background:none; padding:0; color:var(--shipping-primary-color); font-size:10px; cursor:pointer; font-weight:700; margin-top:5px;">تحديد كمقروء</button>
                    </div>
                </div>
            `).join('');
            container.innerHTML = html;
        } else {
            if (badge) badge.style.display = 'none';
            container.innerHTML = '<h4 style="margin: 0 0 10px 0; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 8px;">التنبيهات والإشعارات</h4><div style="font-size: 12px; color: #94a3b8; text-align: center; padding: 10px;">لا توجد تنبيهات جديدة حالياً</div>';
        }
    },

    acknowledgeAlert(id) {
        const fd = new FormData();
        fd.append('action', 'shipping_acknowledge_alert');
        fd.append('id', id);
        fd.append('nonce', shippingVars.nonce);
        fetch(ajaxurl, { method: 'POST', body: fd }).then(() => this.pollAlerts());
    },

    saveProfile() {
        const fd = new FormData();
        fd.append('action', 'shipping_update_profile_ajax');
        fd.append('nonce', shippingVars.profileNonce);
        fd.append('first_name', document.getElementById('shipping_edit_first_name').value);
        fd.append('last_name', document.getElementById('shipping_edit_last_name').value);
        fd.append('user_email', document.getElementById('shipping_edit_user_email').value);
        fd.append('user_pass', document.getElementById('shipping_edit_user_pass').value);

        fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json()).then(res => {
            if (res.success) {
                shippingShowNotification('تم تحديث الملف الشخصي بنجاح');
                setTimeout(() => location.reload(), 500);
            } else shippingShowNotification('خطأ: ' + res.data, 'error');
        });
    },

    // --- Pages & Articles ---
    editPageSettings(page) {
        document.getElementById('edit-page-id').value = page.id;
        document.getElementById('page-edit-name').innerText = page.title;
        document.getElementById('edit-page-title').value = page.title;
        document.getElementById('edit-page-instructions').value = page.instructions;
        ShippingModal.open('shipping-edit-page-modal');
    },

    deleteArticle(id) {
        if(!confirm('هل أنت متأكد من حذف هذا المقال؟')) return;
        const fd = new FormData();
        fd.append('action', 'shipping_delete_article');
        fd.append('id', id);
        fd.append('nonce', shippingVars.nonce);
        fetch(ajaxurl, { method: 'POST', body: fd }).then(r=>r.json()).then(res=>{
            if(res.success) location.reload();
        });
    },


    loadNotifTemplate(type) {
        const fd = new FormData();
        fd.append('action', 'shipping_get_template_ajax');
        fd.append('type', type);
        fd.append('nonce', shippingVars.nonce);
        fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const t = res.data;
                document.getElementById('tmpl_type').value = t.template_type;
                document.getElementById('tmpl_subject').value = t.subject;
                document.getElementById('tmpl_body').value = t.body;
                document.getElementById('tmpl_days').value = t.days_before;
                document.getElementById('tmpl_enabled').checked = t.is_enabled == 1;
                document.getElementById('notif-template-editor').style.display = 'block';
            }
        });
    }
};

