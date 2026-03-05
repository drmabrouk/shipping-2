<?php
if (!defined('ABSPATH')) exit;
global $wpdb;
$is_officer = current_user_can('manage_options');
?>

<?php if ($is_officer):
    $shipping_info = Shipping_Settings::get_shipping_info();
    $currency = $shipping_info['currency'] ?? 'SAR';
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
