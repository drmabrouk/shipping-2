/**
 * Tickets & Support Management Controller
 */

window.TicketsController = {
    currentActiveTicketId: null,
    autoRefreshInterval: null,
    categories: {},
    statuses: {},
    priorities: {},
    isOfficial: false,
    currentUserId: 0,

    init(config) {
        this.categories = config.categories;
        this.statuses = config.statuses;
        this.priorities = config.priorities;
        this.isOfficial = config.isOfficial;
        this.currentUserId = config.currentUserId;

        this.setupEventListeners();
        this.loadTickets();
        this.startAutoRefresh();
    },

    setupEventListeners() {
        const createForm = document.getElementById('create-ticket-form');
        if (createForm) {
            createForm.addEventListener('submit', (e) => this.handleCreateTicket(e));
        }
    },

    loadTickets(showLoader = true) {
        const grid = document.getElementById('shipping-tickets-grid');
        if (!grid) return;
        if (showLoader) grid.style.opacity = '0.5';

        const status = document.getElementById('filter-status').value;
        const category = document.getElementById('filter-category').value;
        const priority = document.getElementById('filter-priority').value;
        const search = document.getElementById('filter-search').value;

        fetch(ajaxurl + `?action=shipping_get_tickets&status=${status}&category=${category}&priority=${priority}&search=${search}&nonce=${shippingVars.ticketNonce}`)
        .then(r => r.json())
        .then(res => {
            grid.style.opacity = '1';
            grid.innerHTML = '';
            if (res.success && res.data.length > 0) {
                res.data.forEach(t => {
                    const cat = this.categories[t.category] || this.categories['other'];
                    const stat = this.statuses[t.status];
                    const priorityLabel = this.priorities[t.priority];

                    const card = document.createElement('div');
                    card.className = 'shipping-ticket-card';
                    card.style.cssText = 'background: #fff; border: 1px solid var(--shipping-border-color); border-radius: 12px; padding: 20px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 20px;';
                    card.onclick = () => this.viewTicket(t.id);
                    card.innerHTML = `
                        <div style="width: 50px; height: 50px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden; border: 1px solid #e2e8f0;">
                            ${t.customer_photo ? `<img src="${t.customer_photo}" style="width: 100%; height: 100%; object-fit: cover;">` : `<span class="dashicons dashicons-admin-users" style="color: #94a3b8;"></span>`}
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                                <span style="font-size: 10px; font-weight: 700; color: #94a3b8;">#${t.id}</span>
                                <h4 style="margin: 0; font-size: 15px; font-weight: 700; color: var(--shipping-dark-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${t.subject}</h4>
                                <span style="background: ${cat.color}; color: ${cat.text}; padding: 2px 10px; border-radius: 20px; font-size: 10px; font-weight: 700;">${cat.label}</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 15px; font-size: 12px; color: #64748b;">
                                <span style="font-weight: 600;">${t.customer_name}</span>
                                <span>•</span>
                                <span>${t.updated_at}</span>
                            </div>
                        </div>
                        <div style="text-align: left; flex-shrink: 0;">
                            <div class="shipping-badge ${stat.class}" style="margin-bottom: 5px;">${stat.label}</div>
                            <div style="font-size: 10px; color: ${t.priority === 'high' ? '#e53e3e' : '#94a3b8'}; font-weight: 700;">الأولوية: ${priorityLabel}</div>
                        </div>
                    `;
                    grid.appendChild(card);
                });
            } else {
                grid.innerHTML = '<div style="text-align: center; padding: 50px; background: #fff; border-radius: 12px; border: 1px dashed #cbd5e0; color: #94a3b8; grid-column: 1/-1;">لا توجد تذاكر حالياً تتطابق مع البحث.</div>';
            }
        });
    },

    viewTicket(id, silent = false) {
        this.currentActiveTicketId = id;
        const listContainer = document.getElementById('tickets-list-container');
        const detailsContainer = document.getElementById('ticket-details-container');

        if (!silent) {
            listContainer.style.display = 'none';
            detailsContainer.style.display = 'block';
            detailsContainer.innerHTML = '<div style="text-align: center; padding: 100px;"><div class="shipping-loader-mini"></div></div>';
        }

        fetch(ajaxurl + `?action=shipping_get_ticket_details&id=${id}&nonce=${shippingVars.ticketNonce}`)
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const t = res.data.ticket;
                const thread = res.data.thread;

                if (silent) {
                    const threadBody = document.getElementById('ticket-thread-body');
                    if (threadBody) {
                        const threadHtml = this.renderThreadHtml(thread);
                        if (threadHtml.trim() !== threadBody.innerHTML.trim()) {
                            threadBody.innerHTML = threadHtml;
                            threadBody.scrollTop = threadBody.scrollHeight;
                        }
                    }
                    return;
                }

                const cat = this.categories[t.category] || this.categories['other'];
                const stat = this.statuses[t.status];
                const threadHtml = this.renderThreadHtml(thread);

                detailsContainer.innerHTML = `
                    <div style="background: #fff; border-radius: 15px; border: 1px solid var(--shipping-border-color); overflow: hidden; box-shadow: var(--shipping-shadow);">
                        <div style="padding: 20px 30px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #fafafa;">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <button onclick="TicketsController.backToList()" class="shipping-btn shipping-btn-outline" style="width: auto; padding: 5px 10px;"><span class="dashicons dashicons-arrow-right-alt2"></span> العودة</button>
                                <div>
                                    <h3 style="margin: 0; font-weight: 800; color: var(--shipping-dark-color);">${t.subject}</h3>
                                    <div style="display: flex; align-items: center; gap: 10px; font-size: 12px; color: #64748b; margin-top: 5px;">
                                        <span>تذكرة رقم: #${t.id}</span>
                                        <span>•</span>
                                        <span style="background: ${cat.color}; color: ${cat.text}; padding: 1px 8px; border-radius: 10px; font-weight: 700;">${cat.label}</span>
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <span class="shipping-badge ${stat.class}">${stat.label}</span>
                                ${this.isOfficial && t.status !== 'closed' ? `<button onclick="TicketsController.closeTicket(${t.id})" class="shipping-btn" style="background: #e53e3e; width: auto; padding: 5px 15px; font-size: 12px;">إغلاق التذكرة</button>` : ''}
                            </div>
                        </div>

                        <div style="padding: 30px; background: #f8fafc; max-height: 500px; overflow-y: auto;" id="ticket-thread-body">
                            ${threadHtml}
                        </div>

                        ${t.status !== 'closed' ? `
                            <div style="padding: 25px 30px; border-top: 1px solid #f1f5f9;">
                                <form id="ticket-reply-form" style="display: flex; flex-direction: column; gap: 15px;">
                                    <input type="hidden" name="ticket_id" value="${t.id}">
                                    <textarea name="message" class="shipping-textarea" rows="3" required placeholder="اكتب ردك هنا..."></textarea>
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <input type="file" name="attachment" style="font-size: 12px;">
                                        <button type="submit" class="shipping-btn" style="width: auto; padding: 0 30px; height: 40px;">إرسال الرد</button>
                                    </div>
                                </form>
                            </div>
                        ` : `
                            <div style="padding: 20px; text-align: center; background: #fff5f5; color: #c53030; font-weight: 700; font-size: 14px;">هذه التذكرة مغلقة. لا يمكنك إضافة ردود جديدة.</div>
                        `}
                    </div>
                    <div style="margin-top: 20px; background: #fff; border-radius: 15px; border: 1px solid var(--shipping-border-color); padding: 20px; box-shadow: var(--shipping-shadow);">
                        <h4 style="margin: 0 0 15px 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">بيانات مقدم الطلب</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; font-size: 13px;">
                            <div><label style="color: #94a3b8; display: block;">الاسم:</label><strong>${t.customer_name}</strong></div>
                            <div><label style="color: #94a3b8; display: block;">رقم الهاتف:</label><strong>${t.customer_phone}</strong></div>
                            <div><label style="color: #94a3b8; display: block;">تاريخ الفتح:</label><strong>${t.created_at}</strong></div>
                        </div>
                    </div>
                `;

                const threadBody = document.getElementById('ticket-thread-body');
                threadBody.scrollTop = threadBody.scrollHeight;

                const replyForm = document.getElementById('ticket-reply-form');
                if (replyForm) {
                    replyForm.addEventListener('submit', (e) => this.handleReply(e, t.id));
                }
            }
        });
    },

    renderThreadHtml(thread) {
        return thread.map(m => {
            const isMe = m.sender_id == this.currentUserId;
            let fileHtml = '';
            if (m.file_url) {
                const fileName = m.file_url.split('/').pop();
                fileHtml = `<a href="${m.file_url}" target="_blank" style="display: inline-flex; align-items: center; gap: 5px; margin-top: 10px; padding: 8px 12px; background: rgba(0,0,0,0.05); border-radius: 8px; text-decoration: none; color: inherit; font-size: 12px;">
                    <span class="dashicons dashicons-paperclip"></span> ${fileName}
                </a>`;
            }

            return `
                <div style="display: flex; flex-direction: column; align-items: ${isMe ? 'flex-end' : 'flex-start'}; margin-bottom: 20px;">
                    <div style="background: ${isMe ? 'var(--shipping-primary-color)' : '#fff'}; color: ${isMe ? '#fff' : 'inherit'}; padding: 15px 20px; border-radius: 15px; border-bottom-${isMe ? 'left' : 'right'}-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: ${isMe ? 'none' : '1px solid #e2e8f0'}; max-width: 80%;">
                        <div style="font-weight: 800; font-size: 11px; margin-bottom: 5px; opacity: 0.8;">${m.sender_name} • ${m.created_at}</div>
                        <div style="font-size: 14px; line-height: 1.6; white-space: pre-wrap;">${m.message}</div>
                        ${fileHtml}
                    </div>
                </div>
            `;
        }).join('');
    },

    handleCreateTicket(e) {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerText = 'جاري الإرسال...';

        const fd = new FormData(form);
        fd.append('action', 'shipping_create_ticket');
        fd.append('nonce', shippingVars.ticketNonce || '');

        fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                ShippingModal.close('create-ticket-modal');
                this.loadTickets();
                this.viewTicket(res.data);
            } else {
                alert('خطأ: ' + res.data);
                btn.disabled = false;
                btn.innerText = 'إرسال التذكرة';
            }
        });
    },

    handleReply(e, ticketId) {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerText = 'جاري الإرسال...';

        const fd = new FormData(form);
        fd.append('action', 'shipping_add_ticket_reply');
        fd.append('nonce', shippingVars.ticketNonce || '');

        fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                this.viewTicket(ticketId);
            } else {
                alert('خطأ: ' + res.data);
                btn.disabled = false;
                btn.innerText = 'إرسال الرد';
            }
        });
    },

    closeTicket(id) {
        if (!confirm('هل أنت متأكد من إغلاق هذه التذكرة بشكل نهائي؟')) return;
        const fd = new FormData();
        fd.append('action', 'shipping_close_ticket');
        fd.append('id', id);
        fd.append('nonce', shippingVars.ticketNonce || '');

        fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                this.viewTicket(id);
            } else alert('خطأ: ' + res.data);
        });
    },

    backToList() {
        this.currentActiveTicketId = null;
        document.getElementById('ticket-details-container').style.display = 'none';
        document.getElementById('tickets-list-container').style.display = 'block';
        this.loadTickets();
    },

    startAutoRefresh() {
        if (this.autoRefreshInterval) clearInterval(this.autoRefreshInterval);
        this.autoRefreshInterval = setInterval(() => {
            if (this.currentActiveTicketId) {
                this.viewTicket(this.currentActiveTicketId, true);
            } else if (document.getElementById('tickets-list-container')?.style.display !== 'none') {
                this.loadTickets(false);
            }
        }, 5000);
    }
};
