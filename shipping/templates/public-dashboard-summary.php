<?php
if (!defined('ABSPATH')) exit;
global $wpdb;
$is_officer = current_user_can('manage_options');
?>

<?php
$shipping_info = Shipping_Settings::get_shipping_info();
$currency = $shipping_info['currency'] ?? 'SAR';

if ($is_officer):
?>

<div id="dashboard-overview" class="shipping-overview-full">
    <div class="shipping-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 25px; margin-bottom: 30px;">
        <div class="shipping-stat-card" style="background: white; padding: 25px; border-radius: 15px; border: 1px solid var(--shipping-border-color); box-shadow: var(--shipping-shadow); text-align: center; cursor: pointer;" onclick="window.location.href='<?php echo add_query_arg(['shipping_tab' => 'customer-mgmt']); ?>'">
            <div style="font-size: 0.85em; color: #64748b; margin-bottom: 10px; font-weight: 700;">إجمالي العملاء</div>
            <div style="font-size: 2.5em; font-weight: 900; color: var(--shipping-primary-color);"><?php echo esc_html($stats['total_customers'] ?? 0); ?></div>
        </div>
        <div class="shipping-stat-card" style="background: white; padding: 25px; border-radius: 15px; border: 1px solid var(--shipping-border-color); box-shadow: var(--shipping-shadow); text-align: center; cursor: pointer;" onclick="window.location.href='<?php echo add_query_arg(['shipping_tab' => 'order-mgmt']); ?>'">
            <div style="font-size: 0.85em; color: #64748b; margin-bottom: 10px; font-weight: 700;">شحنات نشطة</div>
            <div style="font-size: 2.5em; font-weight: 900; color: var(--shipping-secondary-color);"><?php echo esc_html($stats['active_shipments'] ?? 0); ?></div>
        </div>
        <div class="shipping-stat-card" style="background: white; padding: 25px; border-radius: 15px; border: 1px solid var(--shipping-border-color); box-shadow: var(--shipping-shadow); text-align: center; cursor: pointer;" onclick="window.location.href='<?php echo add_query_arg(['shipping_tab' => 'order-mgmt']); ?>'">
            <div style="font-size: 0.85em; color: #64748b; margin-bottom: 10px; font-weight: 700;">طلبات جديدة</div>
            <div style="font-size: 2.5em; font-weight: 900; color: #2ecc71;"><?php echo esc_html($stats['new_orders'] ?? 0); ?></div>
        </div>
        <div class="shipping-stat-card" style="background: white; padding: 25px; border-radius: 15px; border: 1px solid var(--shipping-border-color); box-shadow: var(--shipping-shadow); text-align: center; cursor: pointer;" onclick="window.location.href='<?php echo add_query_arg(['shipping_tab' => 'billing-payments']); ?>'">
            <div style="font-size: 0.85em; color: #64748b; margin-bottom: 10px; font-weight: 700;">إجمالي الإيرادات</div>
            <div style="font-size: 1.8em; font-weight: 900; color: #27ae60; margin-top: 10px;"><?php echo number_format($stats['total_revenue'] ?? 0, 0); ?> <span style="font-size: 0.4em;"><?php echo esc_html($currency); ?></span></div>
        </div>
    </div>

    <div class="shipping-grid" style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px;">
        <div class="shipping-card">
            <h4>توزيع حالات الشحن</h4>
            <div style="height: 300px;"><canvas id="shipmentStatusChart"></canvas></div>
        </div>
        <div class="shipping-card">
            <h4>توجه الإيرادات (آخر 7 أيام)</h4>
            <div style="height: 300px;"><canvas id="revenueTrendChart"></canvas></div>
        </div>
    </div>
</div>
<?php else:
    $user = wp_get_current_user();
    $cust_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}shipping_customers WHERE wp_user_id = %d", $user->ID));

    $active_ship_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}shipping_shipments WHERE customer_id = %d AND status != 'delivered' AND is_archived = 0", $cust_id));
    $pending_order_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}shipping_orders WHERE customer_id = %d AND status = 'new'", $cust_id));
    $unpaid_invoice_sum = $wpdb->get_var($wpdb->prepare("SELECT SUM(total_amount) FROM {$wpdb->prefix}shipping_invoices WHERE customer_id = %d AND status = 'unpaid'", $cust_id));
?>
<div id="customer-overview" class="shipping-overview-full">
    <div class="shipping-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin-bottom: 30px;">
        <div class="shipping-stat-card" style="background: white; padding: 25px; border-radius: 15px; border: 1px solid var(--shipping-border-color); box-shadow: var(--shipping-shadow); text-align: center;">
            <div style="font-size: 0.85em; color: #64748b; margin-bottom: 10px; font-weight: 700;">شحناتي النشطة</div>
            <div style="font-size: 2.5em; font-weight: 900; color: var(--shipping-primary-color);"><?php echo (int)$active_ship_count; ?></div>
            <a href="<?php echo add_query_arg('shipping_tab', 'shipment-mgmt'); ?>" style="font-size: 11px; color: var(--shipping-primary-color); text-decoration: none; font-weight: 700;">عرض الكل ←</a>
        </div>
        <div class="shipping-stat-card" style="background: white; padding: 25px; border-radius: 15px; border: 1px solid var(--shipping-border-color); box-shadow: var(--shipping-shadow); text-align: center;">
            <div style="font-size: 0.85em; color: #64748b; margin-bottom: 10px; font-weight: 700;">طلبات بانتظار التأكيد</div>
            <div style="font-size: 2.5em; font-weight: 900; color: var(--shipping-secondary-color);"><?php echo (int)$pending_order_count; ?></div>
            <a href="<?php echo add_query_arg('shipping_tab', 'order-mgmt'); ?>" style="font-size: 11px; color: var(--shipping-secondary-color); text-decoration: none; font-weight: 700;">عرض الكل ←</a>
        </div>
        <div class="shipping-stat-card" style="background: white; padding: 25px; border-radius: 15px; border: 1px solid var(--shipping-border-color); box-shadow: var(--shipping-shadow); text-align: center;">
            <div style="font-size: 0.85em; color: #64748b; margin-bottom: 10px; font-weight: 700;">مستحقات مالية غير مسددة</div>
            <div style="font-size: 1.8em; font-weight: 900; color: #e53e3e; margin-top: 10px;"><?php echo number_format($unpaid_invoice_sum ?: 0, 2); ?> <span style="font-size: 0.4em;"><?php echo esc_html($currency); ?></span></div>
            <a href="<?php echo add_query_arg('shipping_tab', 'billing-payments'); ?>" style="font-size: 11px; color: #e53e3e; text-decoration: none; font-weight: 700;">سداد الآن ←</a>
        </div>
    </div>

    <div class="shipping-card" style="padding: 30px;">
        <h4 style="margin-top: 0; margin-bottom: 20px;">آخر الشحنات</h4>
        <div class="shipping-table-container" style="margin: 0; border: none; box-shadow: none;">
            <table class="shipping-table">
                <thead><tr><th>رقم الشحنة</th><th>الوجهة</th><th>الحالة</th><th>التاريخ</th></tr></thead>
                <tbody>
                    <?php
                    $last_ships = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}shipping_shipments WHERE customer_id = %d ORDER BY created_at DESC LIMIT 5", $cust_id));
                    if (empty($last_ships)): ?>
                        <tr><td colspan="4" style="text-align: center; padding: 30px; color: #94a3b8;">لا توجد شحنات مسجلة حالياً.</td></tr>
                    <?php else: foreach($last_ships as $s): ?>
                        <tr>
                            <td><strong><?php echo $s->shipment_number; ?></strong></td>
                            <td><?php echo esc_html($s->destination); ?></td>
                            <td><span class="pastel-badge status-<?php echo $s->status; ?>"><?php echo $s->status; ?></span></td>
                            <td style="font-size: 12px;"><?php echo date('Y-m-d', strtotime($s->created_at)); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function shippingDownloadChart(chartId, fileName) {
    const canvas = document.getElementById(chartId);
    if (!canvas) return;
    const link = document.createElement('a');
    link.download = fileName + '.png';
    link.href = canvas.toDataURL('image/png');
    link.click();
}

(function() {
    <?php if (!$is_officer): ?>
    return;
    <?php endif; ?>
    window.shippingCharts = window.shippingCharts || {};

    const initSummaryCharts = function() {
        if (typeof Chart === 'undefined') {
            setTimeout(initSummaryCharts, 200);
            return;
        }

        const chartOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } };

        const createOrUpdateChart = (id, config) => {
            if (window.shippingCharts[id]) {
                window.shippingCharts[id].destroy();
            }
            const el = document.getElementById(id);
            if (el) {
                window.shippingCharts[id] = new Chart(el.getContext('2d'), config);
            }
        };

        // 1. Shipment Status Distribution
        createOrUpdateChart('shipmentStatusChart', {
            type: 'doughnut',
            data: {
                labels: ['نشطة', 'مسلمة', 'متأخرة', 'معلقة'],
                datasets: [{
                    data: [<?php echo (int)($stats['active_shipments'] ?? 0); ?>, <?php echo (int)($stats['delivered_shipments'] ?? 0); ?>, <?php echo (int)($stats['delayed_shipments'] ?? 0); ?>, <?php echo (int)($stats['new_orders'] ?? 0); ?>],
                    backgroundColor: ['#4299E1', '#48BB78', '#F56565', '#ECC94B']
                }]
            },
            options: chartOptions
        });

        // 2. Revenue Trend (Last 7 Days)
        fetch(ajaxurl + '?action=shipping_get_billing_report')
        .then(r => r.json()).then(res => {
            if (res.success) {
                createOrUpdateChart('revenueTrendChart', {
                    type: 'line',
                    data: {
                        labels: res.data.daily.map(d => d.date),
                        datasets: [{
                            label: 'الإيرادات اليومية',
                            data: res.data.daily.map(d => d.total),
                            borderColor: '#F63049',
                            backgroundColor: 'rgba(246, 48, 73, 0.1)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: chartOptions
                });
            }
        });
    };

    if (document.readyState === 'complete') initSummaryCharts();
    else window.addEventListener('load', initSummaryCharts);
})();
</script>
