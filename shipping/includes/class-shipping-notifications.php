<?php

class Shipping_Notifications {

    public static function get_template($type) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}shipping_notification_templates WHERE template_type = %s",
            $type
        ));
    }

    public static function save_template($data) {
        global $wpdb;
        return $wpdb->replace(
            "{$wpdb->prefix}shipping_notification_templates",
            array(
                'template_type' => sanitize_text_field($data['template_type']),
                'subject' => sanitize_text_field($data['subject']),
                'body' => sanitize_textarea_field($data['body']),
                'days_before' => intval($data['days_before']),
                'is_enabled' => isset($data['is_enabled']) ? 1 : 0
            )
        );
    }

    public static function send_template_notification($customer_id, $type, $extra_placeholders = []) {
        $template = self::get_template($type);
        if (!$template || !$template->is_enabled) return false;

        $customer = Shipping_DB::get_customer_by_id($customer_id);
        if (!$customer || empty($customer->email)) return false;

        $subject = $template->subject;
        $body = $template->body;

        $placeholders = array_merge([
            '{customer_name}' => $customer->name,
            '{username}' => $customer->username,
            '{id_number}' => $customer->id_number,
            '{year}' => date('Y'),
        ], $extra_placeholders);

        foreach ($placeholders as $search => $replace) {
            $subject = str_replace($search, $replace, $subject);
            $body = str_replace($search, $replace, $body);
        }

        $email_settings = get_option('shipping_email_design_settings', [
            'header_bg' => '#111F35',
            'header_text' => '#ffffff',
            'footer_text' => '#64748b',
            'accent_color' => '#F63049'
        ]);

        $shipping = Shipping_Settings::get_shipping_info();

        $html_message = self::wrap_in_template($subject, $body, $email_settings, $shipping);

        // Professional Sender info
        add_filter('wp_mail_from', function() { return 'no-reply@shipping.com'; });
        add_filter('wp_mail_from_name', function() use ($shipping) { return $shipping['shipping_name']; });

        $headers = array('Content-Type: text/html; charset=UTF-8');
        $sent = wp_mail($customer->email, $subject, $html_message, $headers);

        self::log_notification($customer_id, $type, $customer->email, $subject, $sent ? 'success' : 'failed');

        return $sent;
    }

    private static function wrap_in_template($subject, $body, $design, $shipping) {
        $logo_html = !empty($shipping['shipping_logo']) ? '<img src="'.esc_url($shipping['shipping_logo']).'" style="max-height:80px; margin-bottom:15px; display:inline-block;">' : '';

        ob_start();
        ?>
        <!DOCTYPE html>
        <html dir="rtl" lang="ar">
        <head>
            <meta charset="UTF-8">
            <style>
                body { margin: 0; padding: 0; background-color: #f6f9fc; }
                .email-container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 15px; overflow: hidden; border: 1px solid #e1e8ed; }
                .header { background-color: <?php echo $design['header_bg']; ?>; color: <?php echo $design['header_text']; ?>; padding: 40px 20px; text-align: center; }
                .content { padding: 40px; line-height: 1.7; color: #1a202c; font-size: 16px; text-align: right; }
                .footer { background-color: #f8fafc; padding: 25px; text-align: center; font-size: 12px; color: <?php echo $design['footer_text']; ?>; border-top: 1px solid #edf2f7; }
                .btn { display: inline-block; padding: 12px 30px; background-color: <?php echo $design['accent_color']; ?>; color: #ffffff !important; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="header">
                    <?php echo $logo_html; ?>
                    <h1 style="margin: 0; font-size: 22px; font-weight: 800;"><?php echo esc_html($shipping['shipping_name']); ?></h1>
                </div>
                <div class="content">
                    <h2 style="color: <?php echo $design['accent_color']; ?>; margin-top: 0;"><?php echo esc_html($subject); ?></h2>
                    <div style="white-space: pre-line;">
                        <?php echo esc_html($body); ?>
                    </div>
                </div>
                <div class="footer">
                    <p style="margin: 0 0 10px 0; font-weight: 700;"><?php echo esc_html($shipping['shipping_name']); ?></p>
                    <p style="margin: 5px 0;"><?php echo esc_html($shipping['address']); ?></p>
                    <p style="margin: 5px 0;">هاتف: <?php echo esc_html($shipping['phone']); ?> | بريد: <?php echo esc_html($shipping['email']); ?></p>
                    <p style="margin: 15px 0 0 0; opacity: 0.8;">هذه رسالة تلقائية، يرجى عدم الرد عليها مباشرة.</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    private static function log_notification($customer_id, $type, $email, $subject, $status) {
        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}shipping_notification_logs", [
            'customer_id' => $customer_id,
            'notification_type' => $type,
            'recipient_email' => $email,
            'subject' => $subject,
            'status' => $status,
            'sent_at' => current_time('mysql')
        ]);
    }

    public static function run_daily_checks() {
        self::check_customership_expirations();
        self::trigger_overdue_reminders();
        self::check_shipment_delays();
    }

    private static function check_shipment_delays() {
        global $wpdb;
        $now = current_time('mysql');

        // Find shipments that passed delivery date but are not delivered/cancelled/delayed
        $overdue_shipments = $wpdb->get_results($wpdb->prepare(
            "SELECT id, shipment_number, customer_id FROM {$wpdb->prefix}shipping_shipments
             WHERE delivery_date < %s
             AND status NOT IN ('delivered', 'cancelled', 'delayed', 'operational-issue')",
            $now
        ));

        foreach ($overdue_shipments as $s) {
            // Update status to delayed - this will trigger alerts via Shipping_DB::update_shipment
            Shipping_DB::update_shipment($s->id, ['status' => 'delayed']);
        }
    }

    public static function trigger_overdue_reminders() {
        global $wpdb;
        $overdue_invoices = $wpdb->get_results("SELECT i.*, c.email, CONCAT(c.first_name, ' ', c.last_name) as name FROM {$wpdb->prefix}shipping_invoices i JOIN {$wpdb->prefix}shipping_customers c ON i.customer_id = c.id WHERE i.status = 'unpaid' AND i.due_date < CURDATE()");

        $shipping = Shipping_Settings::get_shipping_info();
        $currency = $shipping['currency'] ?? 'SAR';
        foreach ($overdue_invoices as $inv) {
            if ($inv->email) {
                $subject = "تنبيه: فاتورة متأخرة السداد - " . $inv->invoice_number;
                $message = "عزيزي العميل " . $inv->name . ",\n\nنود تذكيركم بوجود فاتورة متأخرة السداد برقم " . $inv->invoice_number . " بمبلغ " . $inv->total_amount . " " . $currency . ".\nيرجى السداد في أقرب وقت لتجنب انقطاع الخدمة.\n\nشكراً لكم.";
                wp_mail($inv->email, $subject, $message);
            }
        }
    }

    private static function check_customership_expirations() {
        $template = self::get_template('customership_renewal');
        if (!$template || !$template->is_enabled) return;

        global $wpdb;
        $days = $template->days_before;
        $target_date = date('Y-m-d', strtotime("+$days days"));

        $customers = $wpdb->get_results($wpdb->prepare(
            "SELECT id, account_expiration_date as expiry FROM {$wpdb->prefix}shipping_customers WHERE account_expiration_date = %s",
            $target_date
        ));

        foreach ($customers as $m) {
            if (!self::already_notified($m->id, 'customership_renewal', 5)) {
                self::send_template_notification($m->id, 'customership_renewal', ['{expiry_date}' => $m->expiry]);
            }
        }
    }

    private static function already_notified($customer_id, $type, $days_limit) {
        global $wpdb;
        $last_sent = $wpdb->get_var($wpdb->prepare(
            "SELECT sent_at FROM {$wpdb->prefix}shipping_notification_logs WHERE customer_id = %d AND notification_type = %s ORDER BY sent_at DESC LIMIT 1",
            $customer_id, $type
        ));
        if (!$last_sent) return false;
        return (strtotime($last_sent) > strtotime("-$days_limit days"));
    }

    public static function get_logs($limit = 100, $offset = 0) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, CONCAT(m.first_name, ' ', m.last_name) as customer_name
             FROM {$wpdb->prefix}shipping_notification_logs l
             LEFT JOIN {$wpdb->prefix}shipping_customers m ON l.customer_id = m.id
             ORDER BY l.sent_at DESC LIMIT %d OFFSET %d",
            $limit, $offset
        ));
    }
}
