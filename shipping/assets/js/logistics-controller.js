/**
 * Logistics Management Controller
 * Handles Tracking, Routes, Warehouses, Fleet and Analytics
 */

window.LogisticsController = {
    trackingMap: null,
    trackingMarkers: [],
    charts: {},

    init() {
        this.setupEventListeners();
        this.initActiveTab();
    },

    openSubTab(tab, btn) {
        const container = btn.closest('.shipping-internal-tab') || btn.closest('.shipping-main-panel');
        if (!container) return;

        container.querySelectorAll('.vehicle-sub-tab, .shipping-sub-tab').forEach(t => t.style.display = 'none');
        const target = document.getElementById(tab.includes('vehicle') ? tab : (tab.includes('-tab') ? tab : tab));
        // Note: The logic for target ID selection might need to be more robust if names differ
        const targetEl = document.getElementById(tab) || document.getElementById('vehicle-' + tab + '-tab');
        if (targetEl) targetEl.style.display = 'block';

        btn.parentElement.querySelectorAll('.shipping-tab-btn').forEach(b => b.classList.remove('shipping-active'));
        btn.classList.add('shipping-active');
    },

    initActiveTab() {
        const activeTab = document.querySelector('.shipping-tab-btn.shipping-active');
        if (!activeTab) return;

        const onclick = activeTab.getAttribute('onclick');
        const searchBlock = document.getElementById('logistics-search-block');
        const searchLabel = document.getElementById('logistics-search-label');

        if (onclick.includes('logistic-live')) {
            this.initTrackingMap();
            if (searchBlock) searchBlock.style.display = 'none';
        } else if (onclick.includes('logistic-routes')) {
            this.loadRoutes();
            if (searchBlock) {
                searchBlock.style.display = 'block';
                searchLabel.innerText = 'بحث في المسارات:';
            }
        } else if (onclick.includes('logistic-warehouse')) {
            this.loadWarehouses();
            if (searchBlock) {
                searchBlock.style.display = 'block';
                searchLabel.innerText = 'بحث في المستودعات:';
            }
        } else if (onclick.includes('logistic-fleet')) {
            this.loadFleet();
            if (searchBlock) {
                searchBlock.style.display = 'block';
                searchLabel.innerText = 'بحث في الأسطول:';
            }
        } else if (onclick.includes('logistic-analytics')) {
            this.loadAnalytics();
            if (searchBlock) searchBlock.style.display = 'none';
        }
    },

    setupEventListeners() {
        // Warehouse forms
        this.bindForm('form-warehouse', 'shipping_add_warehouse', () => this.loadWarehouses(), shippingVars.logisticNonce);
        this.bindForm('form-inventory', 'shipping_add_inventory_item', () => this.loadInventory(document.getElementById('inventory-warehouse-id').value), shippingVars.logisticNonce);

        // Route forms
        this.bindForm('form-route', 'shipping_add_route', () => this.loadRoutes(), shippingVars.logisticNonce);
        this.bindForm('form-stop', 'shipping_add_route_stop', () => this.loadStops(document.getElementById('stop-route-id').value), shippingVars.logisticNonce);

        // Fleet forms
        this.bindForm('form-vehicle', 'shipping_add_vehicle', () => this.loadFleet(), shippingVars.logisticNonce);
        this.bindForm('form-maintenance', 'shipping_add_maintenance_log', () => this.loadMaintenance(document.getElementById('maintenance-vehicle-id').value), shippingVars.logisticNonce);

        // Tracking update form
        this.bindForm('form-update-location', 'shipping_update_shipment_location', () => this.loadActiveShipments(), shippingVars.shipmentNonce);
    },

    bindForm(formId, action, callback, nonce) {
        const form = document.getElementById(formId);
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const fd = new FormData(form);
                if (!fd.has('action')) fd.append('action', action);
                if (nonce) fd.append('nonce', nonce);

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
        }
    },

    filterLogistics() {
        const query = document.getElementById('logistics-search-query').value.toLowerCase();
        const activeTab = document.querySelector('.shipping-tab-btn.shipping-active').getAttribute('onclick');

        if (activeTab.includes('logistic-routes')) {
            this.filterItems('.shipping-table tbody tr', query);
        } else if (activeTab.includes('logistic-warehouse')) {
            this.filterItems('.warehouse-card', query);
        } else if (activeTab.includes('logistic-fleet')) {
            this.filterItems('.vehicle-card', query);
        }
    },

    filterItems(selector, query) {
        document.querySelectorAll(selector).forEach(el => {
            const text = el.innerText.toLowerCase();
            el.style.display = !query || text.includes(query) ? '' : 'none';
        });
    },

    resetFilters() {
        document.getElementById('logistics-advanced-search').reset();
        this.filterLogistics();
    },

    // --- Tracking ---
    initTrackingMap() {
        if (!document.getElementById('tracking-map')) return;
        if (!this.trackingMap) {
            this.trackingMap = L.map('tracking-map').setView([23.8859, 45.0792], 6);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(this.trackingMap);
        }
        this.loadActiveShipments();
    },

    loadActiveShipments() {
        fetch(ajaxurl + '?action=shipping_get_shipment_tracking&id=all&nonce=' + shippingVars.shipmentNonce)
        .then(r => r.json()).then(res => {
            if (res.success) {
                const tableBody = document.getElementById('active-shipments-list');
                const shipments = res.data;

                if (tableBody) {
                    tableBody.innerHTML = shipments.map(s => `
                        <tr>
                            <td><strong>${s.shipment_number}</strong></td>
                            <td><span class="shipping-badge">${s.status}</span></td>
                            <td>${s.location || '-'}</td>
                            <td style="font-size:11px;">${s.updated_at}</td>
                            <td>
                                <button class="shipping-btn-outline" onclick="LogisticsController.openUpdateLocationModal(${s.id}, ${s.current_lat}, ${s.current_lng}, '${s.location || ''}')" style="padding:4px 8px; font-size:10px;">تحديث الموقع</button>
                            </td>
                        </tr>
                    `).join('') || '<tr><td colspan="5" style="text-align:center;">لا توجد شحنات نشطة حالياً.</td></tr>';
                }

                if (this.trackingMap) {
                    this.trackingMarkers.forEach(m => this.trackingMap.removeLayer(m));
                    this.trackingMarkers = [];

                    shipments.forEach(s => {
                        if (s.current_lat && s.current_lng) {
                            const marker = L.marker([s.current_lat, s.current_lng]).addTo(this.trackingMap);
                            marker.bindPopup(`
                                <div style="direction:rtl; text-align:right; font-family:'Rubik', sans-serif;">
                                    <strong>${s.shipment_number}</strong><br>
                                    <span>الحالة: ${s.status}</span><br>
                                    <button onclick="window.location.href='${window.shippingAdminUrl}&shipping_tab=shipment-mgmt&sub=monitoring&view_dossier=${s.id}'" style="background:var(--shipping-primary-color); color:#fff; border:none; border-radius:4px; padding:4px 8px; cursor:pointer; font-size:10px; width:100%; margin-top:5px;">الملف الكامل</button>
                                </div>
                            `);
                            this.trackingMarkers.push(marker);
                        }
                    });

                    if (this.trackingMarkers.length > 0) {
                        const group = new L.featureGroup(this.trackingMarkers);
                        this.trackingMap.fitBounds(group.getBounds().pad(0.1));
                    }
                }
            }
        });
    },

    openUpdateLocationModal(id, lat, lng, loc) {
        const form = document.getElementById('form-update-location');
        if (!form) return;
        document.getElementById('update-shipment-id').value = id;
        form.lat.value = lat || '';
        form.lng.value = lng || '';
        form.location.value = loc || '';
        ShippingModal.open('modal-update-location');
    },

    // --- Warehouses ---
    loadWarehouses() {
        fetch(ajaxurl + '?action=shipping_get_warehouses&nonce=' + shippingVars.logisticNonce)
        .then(r => r.json()).then(res => {
            if (res.success) {
                const container = document.getElementById('warehouse-list-container');
                if (!container) return;
                container.innerHTML = res.data.map(w => `
                    <div class="shipping-card warehouse-card" onclick="LogisticsController.selectWarehouse(${w.id}, '${w.name}')" style="cursor:pointer; border:1px solid #eee; transition:0.2s;">
                        <div style="display:flex; justify-content:space-between;">
                            <h5 style="margin:0;">${w.name}</h5>
                            <span class="shipping-badge">${w.location}</span>
                        </div>
                        <div style="margin-top:15px; font-size:13px; color:#666;">
                            <div><strong>السعة:</strong> ${w.available_capacity} / ${w.total_capacity} m³</div>
                        </div>
                        <div style="margin-top:15px; display:flex; gap:10px;">
                            <button class="shipping-btn-outline" onclick="event.stopPropagation(); LogisticsController.openWarehouseModal(${JSON.stringify(w).replace(/"/g, '&quot;')})" style="padding:5px 10px; font-size:11px;">تعديل</button>
                            <button class="shipping-btn" style="background:#e53e3e; padding:5px 10px; font-size:11px;" onclick="event.stopPropagation(); LogisticsController.deleteWarehouse(${w.id})">حذف</button>
                        </div>
                    </div>
                `).join('') || '<p style="text-align:center; grid-column:1/-1;">لا توجد مستودعات مسجلة.</p>';
            }
        });
    },

    selectWarehouse(id, name) {
        document.getElementById('inventory-warehouse-id').value = id;
        document.getElementById('selected-warehouse-name').innerText = name;
        document.getElementById('inventory-section').style.display = 'block';
        this.loadInventory(id);
        document.querySelectorAll('.warehouse-card').forEach(c => c.style.borderColor = '#eee');
        event.currentTarget.style.borderColor = 'var(--shipping-primary-color)';
    },

    loadInventory(warehouseId) {
        fetch(ajaxurl + '?action=shipping_get_inventory&warehouse_id=' + warehouseId + '&nonce=' + shippingVars.logisticNonce)
        .then(r => r.json()).then(res => {
            if (res.success) {
                const body = document.getElementById('inventory-list-body');
                if (!body) return;
                body.innerHTML = res.data.map(i => `
                    <tr>
                        <td>${i.item_name}</td>
                        <td>${i.sku || '-'}</td>
                        <td><strong>${i.quantity}</strong></td>
                        <td>${i.unit}</td>
                        <td>${i.last_updated}</td>
                        <td>
                            <button class="shipping-btn-outline" onclick='LogisticsController.openInventoryModal(${JSON.stringify(i).replace(/"/g, '&quot;')})' style="padding:4px 8px; font-size:10px;">تعديل</button>
                            <button class="shipping-btn" style="background:#e53e3e; padding:4px 8px; font-size:10px;" onclick="LogisticsController.deleteInventoryItem(${i.id})">حذف</button>
                        </td>
                    </tr>
                `).join('') || '<tr><td colspan="6" style="text-align:center;">لا توجد أصناف في هذا المستودع.</td></tr>';
            }
        });
    },

    openWarehouseModal(w = null) {
        const form = document.getElementById('form-warehouse');
        if (!form) return;
        form.reset();
        document.getElementById('warehouse-id').value = w ? w.id : '';
        document.getElementById('warehouse-modal-title').innerText = w ? 'تعديل مستودع' : 'إضافة مستودع جديد';
        if (w) {
            form.name.value = w.name;
            form.location.value = w.location;
            form.total_capacity.value = w.total_capacity;
            form.manager_name.value = w.manager_name;
            form.contact_number.value = w.contact_number;
        }
        ShippingModal.open('modal-warehouse');
    },

    openInventoryModal(i = null) {
        const form = document.getElementById('form-inventory');
        if (!form) return;
        form.reset();
        document.getElementById('inventory-id').value = i ? i.id : '';
        document.getElementById('inventory-modal-title').innerText = i ? 'تعديل صنف' : 'إضافة صنف جديد';
        if (i) {
            form.item_name.value = i.item_name;
            form.sku.value = i.sku;
            form.quantity.value = i.quantity;
            form.unit.value = i.unit;
        }
        ShippingModal.open('modal-inventory');
    },

    // --- Routes ---
    loadRoutes() {
        fetch(ajaxurl + '?action=shipping_get_routes&nonce=' + shippingVars.logisticNonce)
        .then(r => r.json()).then(res => {
            if (res.success) {
                const body = document.getElementById('route-list-body');
                if (!body) return;
                body.innerHTML = res.data.map(r => `
                    <tr>
                        <td><strong>${r.route_name}</strong></td>
                        <td>${r.start_location}</td>
                        <td>${r.end_location}</td>
                        <td>${r.total_distance} كم</td>
                        <td>${r.estimated_duration}</td>
                        <td>
                            <button class="shipping-btn-outline" onclick="LogisticsController.selectRoute(${r.id}, '${r.route_name}')" style="padding:4px 8px; font-size:10px;">النقاط</button>
                            <button class="shipping-btn-outline" onclick='LogisticsController.openRouteModal(${JSON.stringify(r).replace(/"/g, '&quot;')})' style="padding:4px 8px; font-size:10px;">تعديل</button>
                            <button class="shipping-btn" style="background:#e53e3e; padding:4px 8px; font-size:10px;" onclick="LogisticsController.deleteRoute(${r.id})">حذف</button>
                        </td>
                    </tr>
                `).join('') || '<tr><td colspan="6" style="text-align:center;">لا توجد مسارات مسجلة.</td></tr>';
            }
        });
    },

    selectRoute(id, name) {
        document.getElementById('stop-route-id').value = id;
        document.getElementById('selected-route-stops-name').innerText = name;
        document.getElementById('btn-add-stop').disabled = false;
        shippingOpenInternalTab('logistic-stops', document.querySelector('button[onclick*="logistic-stops"]'));
        this.loadStops(id);
    },

    loadStops(routeId) {
        fetch(ajaxurl + '?action=shipping_get_route_stops&route_id=' + routeId + '&nonce=' + shippingVars.logisticNonce)
        .then(r => r.json()).then(res => {
            if (res.success) {
                const body = document.getElementById('stops-list-body');
                if (!body) return;
                body.innerHTML = res.data.map(s => `
                    <tr>
                        <td>${s.stop_order}</td>
                        <td>${s.stop_name}</td>
                        <td>${s.location || '-'}</td>
                        <td style="font-size:11px;">${s.lat}, ${s.lng}</td>
                        <td>
                            <button class="shipping-btn-outline" onclick='LogisticsController.openStopModal(${JSON.stringify(s).replace(/"/g, '&quot;')})' style="padding:4px 8px; font-size:10px;">تعديل</button>
                            <button class="shipping-btn" style="background:#e53e3e; padding:4px 8px; font-size:10px;" onclick="LogisticsController.deleteStop(${s.id})">حذف</button>
                        </td>
                    </tr>
                `).join('') || '<tr><td colspan="5" style="text-align:center;">لا توجد نقاط توقف مسجلة لهذا المسار.</td></tr>';
            }
        });
    },

    openRouteModal(r = null) {
        const form = document.getElementById('form-route');
        if (!form) return;
        form.reset();
        document.getElementById('route-id').value = r ? r.id : '';
        document.getElementById('route-modal-title').innerText = r ? 'تعديل مسار' : 'إضافة مسار جديد';
        if (r) {
            form.route_name.value = r.route_name;
            form.start_location.value = r.start_location;
            form.end_location.value = r.end_location;
            form.total_distance.value = r.total_distance;
            form.estimated_duration.value = r.estimated_duration;
            form.description.value = r.description;
        }
        ShippingModal.open('modal-route');
    },

    openStopModal(s = null) {
        const form = document.getElementById('form-stop');
        if (!form) return;
        form.reset();
        document.getElementById('stop-id').value = s ? s.id : '';
        document.getElementById('stop-modal-title').innerText = s ? 'تعديل نقطة' : 'إضافة نقطة توقف';
        if (s) {
            form.stop_name.value = s.stop_name;
            form.location.value = s.location;
            form.lat.value = s.lat;
            form.lng.value = s.lng;
            form.stop_order.value = s.stop_order;
        }
        ShippingModal.open('modal-stop');
    },

    // --- Fleet ---
    loadFleet() {
        fetch(ajaxurl + '?action=shipping_get_fleet&nonce=' + shippingVars.logisticNonce)
        .then(r => r.json()).then(res => {
            if (res.success) {
                const container = document.getElementById('fleet-list-container');
                if (!container) return;
                container.innerHTML = res.data.map(v => `
                    <div class="shipping-card vehicle-card" onclick="LogisticsController.selectVehicle(${v.id}, '${v.vehicle_number}')" style="cursor:pointer; border:1px solid #eee;">
                        <div style="display:flex; justify-content:space-between;">
                            <h5 style="margin:0;">${v.vehicle_number}</h5>
                            <span class="shipping-badge ${v.status === 'available' ? 'shipping-badge-high' : 'shipping-badge-urgent'}">${v.status}</span>
                        </div>
                        <div style="margin-top:15px; font-size:13px; color:#666;">
                            <div><strong>النوع:</strong> ${v.vehicle_type}</div>
                            <div><strong>السائق:</strong> ${v.driver_name || 'N/A'}</div>
                        </div>
                        <div style="margin-top:15px; display:flex; gap:10px;">
                            <button class="shipping-btn-outline" onclick="event.stopPropagation(); LogisticsController.openVehicleModal(${JSON.stringify(v).replace(/"/g, '&quot;')})" style="padding:5px 10px; font-size:11px;">تعديل</button>
                            <button class="shipping-btn" style="background:#e53e3e; padding:5px 10px; font-size:11px;" onclick="event.stopPropagation(); LogisticsController.deleteVehicle(${v.id})">حذف</button>
                        </div>
                    </div>
                `).join('') || '<p style="text-align:center; grid-column:1/-1;">لا توجد مركبات مسجلة.</p>';
            }
        });
    },

    selectVehicle(id, number) {
        document.getElementById('maintenance-vehicle-id').value = id;
        document.getElementById('selected-vehicle-number').innerText = number;
        document.getElementById('vehicle-details-section').style.display = 'block';
        this.loadMaintenance(id);
        this.loadVehicleShipments(id);
        document.querySelectorAll('.vehicle-card').forEach(c => c.style.borderColor = '#eee');
        event.currentTarget.style.borderColor = 'var(--shipping-primary-color)';
    },

    loadMaintenance(vehicleId) {
        fetch(ajaxurl + '?action=shipping_get_maintenance_logs&vehicle_id=' + vehicleId + '&nonce=' + shippingVars.logisticNonce)
        .then(r => r.json()).then(res => {
            if (res.success) {
                const body = document.getElementById('maintenance-list-body');
                if (!body) return;
                body.innerHTML = res.data.map(m => `
                    <tr>
                        <td>${m.maintenance_type}</td>
                        <td>${m.maintenance_date}</td>
                        <td>${parseFloat(m.cost).toFixed(2)} ${window.shippingCurrency || ''}</td>
                        <td><span class="shipping-badge ${m.completed == 1 ? 'shipping-badge-high' : 'shipping-badge-medium'}">${m.completed == 1 ? 'مكتملة' : 'قيد الانتظار'}</span></td>
                        <td>
                            <button class="shipping-btn-outline" onclick='LogisticsController.openMaintenanceModal(${JSON.stringify(m).replace(/"/g, '&quot;')})' style="padding:4px 8px; font-size:10px;">تعديل</button>
                        </td>
                    </tr>
                `).join('') || '<tr><td colspan="5" style="text-align:center;">لا توجد سجلات صيانة.</td></tr>';
            }
        });
    },

    loadVehicleShipments(vehicleId) {
        fetch(ajaxurl + '?action=shipping_get_vehicle_shipments&vehicle_id=' + vehicleId + '&nonce=' + shippingVars.logisticNonce)
        .then(r => r.json()).then(res => {
            const body = document.getElementById('vehicle-shipments-body');
            if (!body) return;
            if (res.success && res.data.length) {
                body.innerHTML = res.data.map(s => `
                    <tr>
                        <td><strong>${s.shipment_number}</strong></td>
                        <td>${s.customer_name}</td>
                        <td>${s.origin} → ${s.destination}</td>
                        <td><span class="shipping-badge">${s.status}</span></td>
                        <td><button class="shipping-btn-outline" style="padding:4px 8px; font-size:10px;" onclick="window.location.href='${window.shippingAdminUrl}&shipping_tab=shipment-mgmt&sub=monitoring&view_dossier=${s.id}'">الملف</button></td>
                    </tr>
                `).join('');
            } else {
                body.innerHTML = '<tr><td colspan="5" style="text-align:center;">لا توجد شحنات نشطة.</td></tr>';
            }
        });
    },

    openVehicleModal(v = null) {
        const form = document.getElementById('form-vehicle');
        if (!form) return;
        form.reset();
        document.getElementById('vehicle-id').value = v ? v.id : '';
        document.getElementById('vehicle-modal-title').innerText = v ? 'تعديل مركبة' : 'إضافة مركبة جديدة';
        if (v) {
            form.vehicle_number.value = v.vehicle_number;
            form.vehicle_type.value = v.vehicle_type;
            form.capacity.value = v.capacity;
            form.driver_name.value = v.driver_name;
            form.driver_phone.value = v.driver_phone;
            form.next_maintenance_date.value = v.next_maintenance_date;
        }
        ShippingModal.open('modal-vehicle');
    },

    openMaintenanceModal(m = null) {
        const form = document.getElementById('form-maintenance');
        if (!form) return;
        form.reset();
        document.getElementById('maintenance-id').value = m ? m.id : '';
        document.getElementById('maintenance-modal-title').innerText = m ? 'تعديل سجل صيانة' : 'إضافة سجل صيانة';
        if (m) {
            form.maintenance_type.value = m.maintenance_type;
            form.description.value = m.description;
            form.cost.value = m.cost;
            form.maintenance_date.value = m.maintenance_date;
            form.completed.checked = m.completed == 1;
        }
        ShippingModal.open('modal-maintenance');
    },

    // --- Analytics ---
    loadAnalytics() {
        fetch(ajaxurl + '?action=shipping_get_logistics_analytics&nonce=' + shippingVars.logisticNonce)
        .then(r => r.json()).then(res => {
            if (res.success) {
                const data = res.data;
                const costElem = document.getElementById('total-maintenance-cost');
                if (costElem) costElem.innerText = (data.total_maintenance_cost || 0).toFixed(2) + ' ' + (window.shippingCurrency || '');

                this.renderChart('shipment-status', 'pie', {
                    labels: data.shipment_count_by_status.map(i => i.status),
                    datasets: [{
                        data: data.shipment_count_by_status.map(i => i.count),
                        backgroundColor: ['#4299E1', '#48BB78', '#ECC94B', '#F56565']
                    }]
                });

                this.renderChart('fleet-status', 'bar', {
                    labels: data.fleet_status.map(i => i.status),
                    datasets: [{
                        label: 'عدد المركبات',
                        data: data.fleet_status.map(i => i.count),
                        backgroundColor: '#805AD5'
                    }]
                });

                this.renderChart('warehouse-utilization', 'bar', {
                    labels: data.warehouse_utilization.map(i => i.name),
                    datasets: [{
                        label: '% نسبة الإشغال',
                        data: data.warehouse_utilization.map(i => i.utilization),
                        backgroundColor: '#319795'
                    }]
                });
            }
        });
    },

    renderChart(id, type, data) {
        const canvas = document.getElementById('chart-' + id);
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        if (this.charts[id]) this.charts[id].destroy();
        this.charts[id] = new Chart(ctx, {
            type: type,
            data: data,
            options: { responsive: true, maintainAspectRatio: false }
        });
    },

    searchHistory() {
        const number = document.getElementById('history-shipment-number').value;
        if (!number) return;

        fetch(ajaxurl + '?action=shipping_get_shipment_tracking&number=' + number + '&nonce=' + shippingVars.shipmentNonce)
        .then(r => r.json()).then(res => {
            const body = document.getElementById('history-list-body');
            if (!body) return;
            if (res.success && res.data.events) {
                body.innerHTML = res.data.events.map(e => `
                    <tr>
                        <td>${e.created_at}</td>
                        <td><span class="shipping-badge">${e.status}</span></td>
                        <td>${e.location || '-'}</td>
                        <td>${e.description}</td>
                    </tr>
                `).join('');
            } else {
                body.innerHTML = '<tr><td colspan="4" style="text-align:center;">لم يتم العثور على شحنة بهذا الرقم.</td></tr>';
            }
        });
    }
};

