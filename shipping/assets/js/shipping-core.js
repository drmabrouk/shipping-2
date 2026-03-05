/**
 * Shipping Core Logic
 * Handles global state, standardized UI triggers, and notifications.
 */

window.ShippingState = {
    selectedCustomer: null,
    selectedShipment: null,
    selectedOrder: null,
    currentTab: 'overview',

    init() {
        const savedState = localStorage.getItem('shipping_system_state');
        if (savedState) {
            const parsed = JSON.parse(savedState);
            this.selectedCustomer = parsed.selectedCustomer || null;
            this.selectedShipment = parsed.selectedShipment || null;
            this.selectedOrder = parsed.selectedOrder || null;
        }
        this.syncUI();
    },

    save() {
        localStorage.setItem('shipping_system_state', JSON.stringify({
            selectedCustomer: this.selectedCustomer,
            selectedShipment: this.selectedShipment,
            selectedOrder: this.selectedOrder
        }));
    },

    setCustomer(customer) {
        this.selectedCustomer = customer;
        this.save();
        document.dispatchEvent(new CustomEvent('shipping_customer_changed', { detail: customer }));
    },

    setShipment(shipment) {
        this.selectedShipment = shipment;
        this.save();
        document.dispatchEvent(new CustomEvent('shipping_shipment_changed', { detail: shipment }));
    },

    setOrder(order) {
        this.selectedOrder = order;
        this.save();
        document.dispatchEvent(new CustomEvent('shipping_order_changed', { detail: order }));
    },

    syncUI() {
        // Apply visual highlights for selected items
        if (this.selectedCustomer) {
            document.querySelectorAll(`[data-customer-id="${this.selectedCustomer}"]`).forEach(el => el.classList.add('shipping-state-selected'));
        }
        if (this.selectedShipment) {
            document.querySelectorAll(`[data-shipment-id="${this.selectedShipment}"]`).forEach(el => el.classList.add('shipping-state-selected'));
        }
    }
};

/**
 * Standardized Modal Controller
 */
window.ShippingModal = {
    open(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    },

    close(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    },

    // Close when clicking overlay
    initOverlayClose() {
        document.querySelectorAll('.shipping-modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    overlay.style.display = 'none';
                    document.body.style.overflow = '';
                }
            });
        });
    }
};

/**
 * Tab Management
 */
window.shippingOpenInternalTab = function(tabId, btn) {
    // Hide all internal tabs in the current container
    const container = btn.closest('.shipping-admin-header')?.nextElementSibling || document.querySelector('.shipping-internal-tab')?.parentElement;
    if (!container) return;

    const tabs = container.querySelectorAll('.shipping-internal-tab');
    tabs.forEach(tab => tab.style.display = 'none');

    // Show target tab
    const target = document.getElementById(tabId);
    if (target) target.style.display = 'block';

    // Update button active state
    const btns = btn.parentElement.querySelectorAll('.shipping-tab-btn');
    btns.forEach(b => b.classList.remove('shipping-active'));
    btn.classList.add('shipping-active');
};

/**
 * Notifications
 */
window.shippingShowNotification = function(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `shipping-notification shipping-notification-${type}`;
    notification.innerHTML = `
        <div style="background: ${type === 'success' ? '#38a169' : '#e53e3e'}; color: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); margin-bottom: 10px; animation: slideInRight 0.3s ease;">
            ${message}
        </div>
    `;

    let container = document.getElementById('shipping-notifications-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'shipping-notifications-container';
        container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 100000;';
        document.body.appendChild(container);
    }

    container.appendChild(notification);

    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(20px)';
        notification.style.transition = '0.3s';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
};

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
    ShippingState.init();
    ShippingModal.initOverlayClose();
});

// Add slideInRight animation to CSS if not present
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
`;
document.head.appendChild(style);
