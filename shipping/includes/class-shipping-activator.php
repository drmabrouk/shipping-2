<?php

class Shipping_Activator {

    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $installed_ver = get_option('shipping_db_version');

        // Migration: Rename old tables if they exist
        if (empty($installed_ver) || version_compare($installed_ver, SHIPPING_VERSION, '<')) {
            self::migrate_tables();
            self::migrate_settings();
        }

        $sql = "";

        // Customers Table
        $table_name = $wpdb->prefix . 'shipping_customers';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            username varchar(100) NOT NULL,
            customer_code tinytext,
            first_name tinytext NOT NULL,
            last_name tinytext NOT NULL,
            gender enum('male', 'female') DEFAULT 'male',
            year_of_birth int,
            residence_street text,
            residence_city tinytext,
            id_number tinytext,
            account_start_date date,
            account_expiration_date date,
            account_status tinytext,
            email tinytext,
            phone tinytext,
            alt_phone tinytext,
            notes text,
            photo_url text,
            wp_user_id bigint(20),
            officer_id bigint(20),
            registration_date date,
            classification varchar(50) DEFAULT 'regular',
            sort_order int DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY username (username),
            KEY wp_user_id (wp_user_id),
            KEY officer_id (officer_id)
        ) $charset_collate;\n";


        // Messages Table
        $table_name = $wpdb->prefix . 'shipping_messages';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            sender_id bigint(20) NOT NULL,
            receiver_id bigint(20) NOT NULL,
            customer_id mediumint(9),
            message text NOT NULL,
            file_url text,
            is_read tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY sender_id (sender_id),
            KEY receiver_id (receiver_id),
            KEY customer_id (customer_id)
        ) $charset_collate;\n";

        // Logs Table
        $table_name = $wpdb->prefix . 'shipping_logs';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20),
            action tinytext NOT NULL,
            details text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;\n";


        // Notification Templates Table
        $table_name = $wpdb->prefix . 'shipping_notification_templates';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            template_type varchar(50) NOT NULL,
            subject varchar(255) NOT NULL,
            body text NOT NULL,
            days_before int DEFAULT 0,
            is_enabled tinyint(1) DEFAULT 1,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY template_type (template_type)
        ) $charset_collate;\n";

        // Notification Logs Table
        $table_name = $wpdb->prefix . 'shipping_notification_logs';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            customer_id mediumint(9),
            notification_type varchar(50),
            recipient_email varchar(100),
            subject varchar(255),
            sent_at datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20),
            PRIMARY KEY  (id),
            KEY customer_id (customer_id),
            KEY sent_at (sent_at)
        ) $charset_collate;\n";

        // Tickets Table
        $table_name = $wpdb->prefix . 'shipping_tickets';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            customer_id mediumint(9) NOT NULL,
            subject varchar(255) NOT NULL,
            category varchar(50),
            priority enum('low', 'medium', 'high') DEFAULT 'medium',
            status enum('open', 'in-progress', 'closed') DEFAULT 'open',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY customer_id (customer_id),
            KEY status (status)
        ) $charset_collate;\n";

        // Ticket Thread Table
        $table_name = $wpdb->prefix . 'shipping_ticket_thread';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            ticket_id mediumint(9) NOT NULL,
            sender_id bigint(20) NOT NULL,
            message text NOT NULL,
            file_url text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY ticket_id (ticket_id),
            KEY sender_id (sender_id)
        ) $charset_collate;\n";

        // Pages Table
        $table_name = $wpdb->prefix . 'shipping_pages';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            shortcode varchar(50) NOT NULL,
            instructions text,
            settings text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug),
            UNIQUE KEY shortcode (shortcode)
        ) $charset_collate;\n";

        // Articles Table
        $table_name = $wpdb->prefix . 'shipping_articles';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            content longtext NOT NULL,
            image_url text,
            author_id bigint(20),
            status enum('publish', 'draft') DEFAULT 'publish',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;\n";

        // Alerts Table
        $table_name = $wpdb->prefix . 'shipping_alerts';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            message text NOT NULL,
            severity enum('info', 'warning', 'critical') DEFAULT 'info',
            must_acknowledge tinyint(1) DEFAULT 0,
            status enum('active', 'inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;\n";

        // Alert Views Table
        $table_name = $wpdb->prefix . 'shipping_alert_views';
        $sql .= "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            alert_id mediumint(9) NOT NULL,
            user_id bigint(20) NOT NULL,
            acknowledged tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY alert_id (alert_id),
            KEY user_id (user_id)
        ) $charset_collate;\n";

        // Shipments Table
        $table_name = $wpdb->prefix . 'shipping_shipments';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            shipment_number varchar(100) NOT NULL,
            customer_id mediumint(9),
            origin varchar(255),
            destination varchar(255),
            weight decimal(10,2),
            dimensions varchar(100),
            classification varchar(50),
            status varchar(50) DEFAULT 'pending',
            pickup_date datetime,
            dispatch_date datetime,
            delivery_date datetime,
            carrier_id mediumint(9),
            route_id mediumint(9),
            estimated_cost decimal(10,2) DEFAULT 0,
            cost_breakdown_json text,
            current_lat decimal(10,8),
            current_lng decimal(11,8),
            is_archived tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY shipment_number (shipment_number),
            KEY customer_id (customer_id),
            KEY status (status),
            KEY is_archived (is_archived)
        ) $charset_collate;\n";

        // Shipment Logs Table (Audit Trail)
        $table_name = $wpdb->prefix . 'shipping_shipment_logs';
        $sql .= "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            shipment_id mediumint(9) NOT NULL,
            user_id bigint(20),
            action varchar(100) NOT NULL,
            old_value text,
            new_value text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY shipment_id (shipment_id)
        ) $charset_collate;\n";

        // Shipment Tracking Events Table
        $table_name = $wpdb->prefix . 'shipping_shipment_tracking_events';
        $sql .= "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            shipment_id mediumint(9) NOT NULL,
            status varchar(50) NOT NULL,
            location varchar(255),
            description text,
            current_lat decimal(10,8),
            current_lng decimal(11,8),
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY shipment_id (shipment_id),
            KEY status (status)
        ) $charset_collate;\n";

        // Orders Table
        $table_name = $wpdb->prefix . 'shipping_orders';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            order_number varchar(100) NOT NULL,
            customer_id mediumint(9),
            total_amount decimal(10,2),
            status varchar(50) DEFAULT 'new',
            pickup_address text,
            delivery_address text,
            order_details text,
            shipment_id mediumint(9),
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY order_number (order_number),
            KEY customer_id (customer_id),
            KEY status (status)
        ) $charset_collate;\n";

        // Order Logs Table (Audit Trail)
        $table_name = $wpdb->prefix . 'shipping_order_logs';
        $sql .= "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_id mediumint(9) NOT NULL,
            user_id bigint(20),
            action varchar(100) NOT NULL,
            old_value text,
            new_value text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY order_id (order_id)
        ) $charset_collate;\n";


        // Logistics Table (Routes)
        $table_name = $wpdb->prefix . 'shipping_logistics';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            route_name varchar(255),
            description text,
            start_location varchar(255),
            end_location varchar(255),
            total_distance decimal(10,2),
            estimated_duration varchar(50),
            PRIMARY KEY  (id)
        ) $charset_collate;\n";

        // Route Stop Points Table
        $table_name = $wpdb->prefix . 'shipping_route_stops';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            route_id mediumint(9) NOT NULL,
            stop_name varchar(255) NOT NULL,
            location varchar(255),
            lat decimal(10,8),
            lng decimal(11,8),
            stop_order int DEFAULT 0,
            PRIMARY KEY  (id),
            KEY route_id (route_id)
        ) $charset_collate;\n";

        // Warehouses Table
        $table_name = $wpdb->prefix . 'shipping_warehouses';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            location varchar(255),
            total_capacity decimal(10,2),
            available_capacity decimal(10,2),
            manager_name varchar(255),
            contact_number varchar(50),
            PRIMARY KEY  (id)
        ) $charset_collate;\n";

        // Inventory Table
        $table_name = $wpdb->prefix . 'shipping_inventory';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            warehouse_id mediumint(9) NOT NULL,
            item_name varchar(255) NOT NULL,
            sku varchar(100),
            quantity int DEFAULT 0,
            unit varchar(50),
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY warehouse_id (warehouse_id)
        ) $charset_collate;\n";

        // Fleet Table
        $table_name = $wpdb->prefix . 'shipping_fleet';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            vehicle_number varchar(100) NOT NULL,
            vehicle_type varchar(50),
            capacity decimal(10,2),
            status varchar(50) DEFAULT 'available',
            driver_name varchar(255),
            driver_phone varchar(50),
            last_maintenance_date date,
            next_maintenance_date date,
            PRIMARY KEY  (id),
            UNIQUE KEY vehicle_number (vehicle_number)
        ) $charset_collate;\n";

        // Maintenance Table
        $table_name = $wpdb->prefix . 'shipping_maintenance';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            vehicle_id mediumint(9) NOT NULL,
            maintenance_type varchar(100),
            description text,
            cost decimal(10,2),
            maintenance_date date,
            completed tinyint(1) DEFAULT 0,
            PRIMARY KEY  (id),
            KEY vehicle_id (vehicle_id)
        ) $charset_collate;\n";

        // Contracts Table
        $table_name = $wpdb->prefix . 'shipping_contracts';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            customer_id mediumint(9) NOT NULL,
            contract_number varchar(100) NOT NULL,
            title varchar(255),
            start_date date,
            end_date date,
            status varchar(50) DEFAULT 'active',
            file_url text,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY contract_number (contract_number),
            KEY customer_id (customer_id)
        ) $charset_collate;\n";

        // Customs Documents Table
        $table_name = $wpdb->prefix . 'shipping_customs_docs';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            shipment_id mediumint(9) NOT NULL,
            doc_type varchar(100) NOT NULL,
            file_url text NOT NULL,
            status varchar(50) DEFAULT 'pending',
            uploaded_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY shipment_id (shipment_id)
        ) $charset_collate;\n";

        // Customs Table
        $table_name = $wpdb->prefix . 'shipping_customs';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            shipment_id mediumint(9),
            documentation_status varchar(50),
            duties_amount decimal(10,2),
            clearance_status varchar(50),
            PRIMARY KEY  (id)
        ) $charset_collate;\n";

        // Invoices Table
        $table_name = $wpdb->prefix . 'shipping_invoices';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            invoice_number varchar(100) NOT NULL,
            order_id mediumint(9),
            customer_id mediumint(9),
            subtotal decimal(10,2),
            tax_amount decimal(10,2),
            discount_amount decimal(10,2),
            total_amount decimal(10,2),
            items_json text,
            currency varchar(10) DEFAULT 'SAR',
            due_date date,
            status varchar(50) DEFAULT 'unpaid',
            invoice_type varchar(50) DEFAULT 'one-time',
            is_recurring tinyint(1) DEFAULT 0,
            billing_interval varchar(20),
            next_billing_date date,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY invoice_number (invoice_number),
            KEY customer_id (customer_id),
            KEY status (status)
        ) $charset_collate;\n";

        // Payments Table
        $table_name = $wpdb->prefix . 'shipping_payments';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            invoice_id mediumint(9),
            transaction_id varchar(100),
            amount_paid decimal(10,2),
            payment_date datetime DEFAULT CURRENT_TIMESTAMP,
            payment_method varchar(50),
            payment_status varchar(50),
            currency varchar(10) DEFAULT 'SAR',
            gateway_response text,
            notes text,
            PRIMARY KEY  (id),
            KEY invoice_id (invoice_id),
            KEY transaction_id (transaction_id)
        ) $charset_collate;\n";

        // Billing Logs Table (Audit Trail)
        $table_name = $wpdb->prefix . 'shipping_billing_logs';
        $sql .= "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            invoice_id mediumint(9),
            user_id bigint(20),
            action varchar(100) NOT NULL,
            amount decimal(10,2),
            details text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY invoice_id (invoice_id)
        ) $charset_collate;\n";

        // Pricing Table (Legacy/Basic)
        $table_name = $wpdb->prefix . 'shipping_pricing';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            service_name varchar(255),
            base_cost decimal(10,2),
            additional_fees decimal(10,2),
            special_offer_details text,
            PRIMARY KEY  (id)
        ) $charset_collate;\n";

        // Advanced Pricing Rules
        $table_name = $wpdb->prefix . 'shipping_pricing_rules';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            rule_name varchar(255) NOT NULL,
            customer_type varchar(50) DEFAULT 'all',
            shipment_category varchar(50) DEFAULT 'all',
            min_weight decimal(10,2) DEFAULT 0,
            max_weight decimal(10,2) DEFAULT 999999.99,
            base_price decimal(10,2) DEFAULT 0,
            price_per_kg decimal(10,2) DEFAULT 0,
            price_per_km decimal(10,2) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            PRIMARY KEY  (id)
        ) $charset_collate;\n";

        // Additional Fees
        $table_name = $wpdb->prefix . 'shipping_additional_fees';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            fee_name varchar(255) NOT NULL,
            fee_type enum('fixed', 'percentage') DEFAULT 'fixed',
            fee_value decimal(10,2) NOT NULL,
            apply_to varchar(50) DEFAULT 'all',
            is_automated tinyint(1) DEFAULT 1,
            PRIMARY KEY  (id)
        ) $charset_collate;\n";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('shipping_db_version', SHIPPING_VERSION);

        self::setup_roles();
        self::seed_notification_templates();
        self::seed_sample_data();
    }

    private static function seed_sample_data() {
        global $wpdb;
        // Only seed if no customers exist
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}shipping_customers");
        if ($count > 0) return;

        // 1. Customers (5)
        $customers = [
            ['username' => 'ahmed_user', 'first_name' => 'أحمد', 'last_name' => 'علي', 'email' => 'ahmed@example.com', 'phone' => '0501234567', 'account_status' => 'active', 'classification' => 'regular'],
            ['username' => 'sara_corp', 'first_name' => 'سارة', 'last_name' => 'محمود', 'email' => 'sara@example.com', 'phone' => '0559876543', 'account_status' => 'active', 'classification' => 'vip'],
            ['username' => 'khaled_logistics', 'first_name' => 'خالد', 'last_name' => 'حسن', 'email' => 'khaled@example.com', 'phone' => '0561122334', 'account_status' => 'active', 'classification' => 'regular'],
            ['username' => 'mona_shop', 'first_name' => 'منى', 'last_name' => 'يوسف', 'email' => 'mona@example.com', 'phone' => '0544433221', 'account_status' => 'active', 'classification' => 'regular'],
            ['username' => 'omar_trade', 'first_name' => 'عمر', 'last_name' => 'إبراهيم', 'email' => 'omar@example.com', 'phone' => '0533322114', 'account_status' => 'restricted', 'classification' => 'regular']
        ];
        foreach ($customers as $c) {
            $wp_user_id = wp_insert_user([
                'user_login' => $c['username'],
                'user_email' => $c['email'],
                'user_pass' => 'password123',
                'display_name' => $c['first_name'] . ' ' . $c['last_name'],
                'role' => 'subscriber'
            ]);
            if (!is_wp_error($wp_user_id)) {
                $wpdb->insert("{$wpdb->prefix}shipping_customers", array_merge($c, ['wp_user_id' => $wp_user_id, 'registration_date' => date('Y-m-d')]));
            }
        }

        // 2. Warehouses (2)
        $wpdb->insert("{$wpdb->prefix}shipping_warehouses", ['name' => 'مستودع الرياض الرئيسي', 'location' => 'الرياض - حي السلي', 'total_capacity' => 5000, 'available_capacity' => 4200, 'manager_name' => 'فهد القحطاني', 'contact_number' => '0500001122']);
        $w1_id = $wpdb->insert_id;
        $wpdb->insert("{$wpdb->prefix}shipping_warehouses", ['name' => 'مستودع جدة البحري', 'location' => 'جدة - ميناء جدة الإسلامي', 'total_capacity' => 8000, 'available_capacity' => 7500, 'manager_name' => 'سالم الحربي', 'contact_number' => '0500003344']);
        $w2_id = $wpdb->insert_id;

        // 3. Inventory (some items)
        $wpdb->insert("{$wpdb->prefix}shipping_inventory", ['warehouse_id' => $w1_id, 'item_name' => 'كراتين تغليف كبيرة', 'sku' => 'PKG-L', 'quantity' => 1200, 'unit' => 'قطعة']);
        $wpdb->insert("{$wpdb->prefix}shipping_inventory", ['warehouse_id' => $w1_id, 'item_name' => 'أشرطة لاصقة هشة', 'sku' => 'TP-FRG', 'quantity' => 500, 'unit' => 'لفة']);
        $wpdb->insert("{$wpdb->prefix}shipping_inventory", ['warehouse_id' => $w2_id, 'item_name' => 'منصات خشبية (Pallets)', 'sku' => 'PLT-WD', 'quantity' => 300, 'unit' => 'قطعة']);

        // 4. Fleet (3)
        $wpdb->insert("{$wpdb->prefix}shipping_fleet", ['vehicle_number' => 'أ ب ج 1234', 'vehicle_type' => 'شاحنة كبيرة', 'capacity' => 15000, 'status' => 'available', 'driver_name' => 'محمد سعد', 'driver_phone' => '0511111111']);
        $v1_id = $wpdb->insert_id;
        $wpdb->insert("{$wpdb->prefix}shipping_fleet", ['vehicle_number' => 'د هـ و 5678', 'vehicle_type' => 'دينا نقل', 'capacity' => 5000, 'status' => 'in-transit', 'driver_name' => 'ياسر خالد', 'driver_phone' => '0522222222']);
        $v2_id = $wpdb->insert_id;
        $wpdb->insert("{$wpdb->prefix}shipping_fleet", ['vehicle_number' => 'ر ز س 9012', 'vehicle_type' => 'فانيت توصيل', 'capacity' => 1000, 'status' => 'maintenance', 'driver_name' => 'عبدالله صالح', 'driver_phone' => '0533333333']);

        // 5. Routes & Stops (2)
        $wpdb->insert("{$wpdb->prefix}shipping_logistics", ['route_name' => 'خط الرياض - الدمام السريع', 'start_location' => 'الرياض', 'end_location' => 'الدمام', 'total_distance' => 400, 'estimated_duration' => '4 ساعات']);
        $r1_id = $wpdb->insert_id;
        $wpdb->insert("{$wpdb->prefix}shipping_route_stops", ['route_id' => $r1_id, 'stop_name' => 'محطة تمير', 'location' => 'طريق الرياض الدمام', 'stop_order' => 1]);

        $wpdb->insert("{$wpdb->prefix}shipping_logistics", ['route_name' => 'خط الغربية (جدة - مكة)', 'start_location' => 'جدة', 'end_location' => 'مكة المكرمة', 'total_distance' => 80, 'estimated_duration' => 'ساعة واحدة']);

        // 6. Pricing Rules (3)
        $wpdb->insert("{$wpdb->prefix}shipping_pricing_rules", ['rule_name' => 'شحن قياسي محلي', 'base_price' => 30.00, 'price_per_kg' => 2.50, 'price_per_km' => 0.10, 'customer_type' => 'all', 'shipment_category' => 'standard']);
        $wpdb->insert("{$wpdb->prefix}shipping_pricing_rules", ['rule_name' => 'شحن VIP سريع', 'base_price' => 70.00, 'price_per_kg' => 5.00, 'price_per_km' => 0.20, 'customer_type' => 'vip', 'shipment_category' => 'express']);
        $wpdb->insert("{$wpdb->prefix}shipping_pricing_rules", ['rule_name' => 'شحن مواد قابلة للكسر', 'base_price' => 50.00, 'price_per_kg' => 3.00, 'price_per_km' => 0.15, 'customer_type' => 'all', 'shipment_category' => 'fragile']);

        // 7. Additional Fees (2)
        $wpdb->insert("{$wpdb->prefix}shipping_additional_fees", ['fee_name' => 'ضريبة القيمة المضافة', 'fee_type' => 'percentage', 'fee_value' => 15.00, 'is_automated' => 1]);
        $wpdb->insert("{$wpdb->prefix}shipping_additional_fees", ['fee_name' => 'رسوم وقود متغيرة', 'fee_type' => 'fixed', 'fee_value' => 5.00, 'is_automated' => 1]);

        // 9. Orders (4)
        $wpdb->insert("{$wpdb->prefix}shipping_orders", ['order_number' => 'ORD-TEST001', 'customer_id' => 1, 'total_amount' => 150.00, 'status' => 'new', 'pickup_address' => 'الرياض، حي الملقا', 'delivery_address' => 'الدمام، حي الزهور']);
        $o1_id = $wpdb->insert_id;
        $wpdb->insert("{$wpdb->prefix}shipping_orders", ['order_number' => 'ORD-TEST002', 'customer_id' => 2, 'total_amount' => 340.50, 'status' => 'in-progress', 'pickup_address' => 'جدة، الحمراء', 'delivery_address' => 'مكة، العزيزية']);
        $o2_id = $wpdb->insert_id;
        $wpdb->insert("{$wpdb->prefix}shipping_orders", ['order_number' => 'ORD-TEST003', 'customer_id' => 3, 'total_amount' => 85.00, 'status' => 'completed', 'pickup_address' => 'الرياض، العليا', 'delivery_address' => 'الرياض، الروضة']);
        $wpdb->insert("{$wpdb->prefix}shipping_orders", ['order_number' => 'ORD-TEST004', 'customer_id' => 4, 'total_amount' => 210.00, 'status' => 'cancelled', 'pickup_address' => 'الخبر، العقربية', 'delivery_address' => 'الجبيل، الصناعية']);

        // 10. Shipments (4)
        $wpdb->insert("{$wpdb->prefix}shipping_shipments", ['shipment_number' => 'SHP-LIVE100', 'customer_id' => 1, 'origin' => 'الرياض', 'destination' => 'الدمام', 'weight' => 10.5, 'status' => 'in-transit', 'carrier_id' => $v2_id, 'route_id' => $r1_id, 'current_lat' => 24.7136, 'current_lng' => 46.6753]);
        $s1_id = $wpdb->insert_id;
        $wpdb->update("{$wpdb->prefix}shipping_orders", ['shipment_id' => $s1_id], ['id' => $o1_id]);

        $wpdb->insert("{$wpdb->prefix}shipping_shipments", ['shipment_number' => 'SHP-LIVE200', 'customer_id' => 2, 'origin' => 'جدة', 'destination' => 'مكة', 'weight' => 5.0, 'status' => 'out-for-delivery', 'carrier_id' => $v1_id, 'current_lat' => 21.4858, 'current_lng' => 39.1925]);
        $s2_id = $wpdb->insert_id;
        $wpdb->update("{$wpdb->prefix}shipping_orders", ['shipment_id' => $s2_id], ['id' => $o2_id]);

        $wpdb->insert("{$wpdb->prefix}shipping_shipments", ['shipment_number' => 'SHP-ARCHIVED1', 'customer_id' => 3, 'origin' => 'الرياض', 'destination' => 'الرياض', 'weight' => 2.0, 'status' => 'delivered', 'is_archived' => 1]);
        $wpdb->insert("{$wpdb->prefix}shipping_shipments", ['shipment_number' => 'SHP-PENDING1', 'customer_id' => 4, 'origin' => 'الخبر', 'destination' => 'الجبيل', 'weight' => 15.0, 'status' => 'pending']);

        // 11. Maintenance Logs (2)
        $wpdb->insert("{$wpdb->prefix}shipping_maintenance", ['vehicle_id' => $v1_id, 'maintenance_type' => 'تغيير زيت وفلاتر', 'description' => 'صيانة دورية كل 10 آلاف كم', 'cost' => 450.00, 'maintenance_date' => date('Y-m-d', strtotime('-1 month')), 'completed' => 1]);
        $wpdb->insert("{$wpdb->prefix}shipping_maintenance", ['vehicle_id' => $v2_id, 'maintenance_type' => 'إصلاح فرامل', 'description' => 'تغيير فحمات أمامية', 'cost' => 800.00, 'maintenance_date' => date('Y-m-d'), 'completed' => 0]);

        // 12. Invoices (3)
        $wpdb->insert("{$wpdb->prefix}shipping_invoices", ['invoice_number' => 'INV-2024-001', 'customer_id' => 1, 'subtotal' => 100.00, 'tax_amount' => 15.00, 'total_amount' => 115.00, 'status' => 'paid', 'due_date' => date('Y-m-d')]);
        $inv1_id = $wpdb->insert_id;
        $wpdb->insert("{$wpdb->prefix}shipping_invoices", ['invoice_number' => 'INV-2024-002', 'customer_id' => 2, 'subtotal' => 300.00, 'tax_amount' => 45.00, 'total_amount' => 345.00, 'status' => 'unpaid', 'due_date' => date('Y-m-d', strtotime('+3 days'))]);
        $wpdb->insert("{$wpdb->prefix}shipping_invoices", ['invoice_number' => 'INV-2024-003', 'customer_id' => 3, 'subtotal' => 50.00, 'tax_amount' => 7.50, 'total_amount' => 57.50, 'status' => 'unpaid', 'due_date' => date('Y-m-d', strtotime('-2 days'))]);

        // 13. Payments (1)
        $wpdb->insert("{$wpdb->prefix}shipping_payments", ['invoice_id' => $inv1_id, 'transaction_id' => 'TRX-998877', 'amount_paid' => 115.00, 'payment_method' => 'online', 'payment_status' => 'completed', 'currency' => 'SAR']);

        // 14. Order Logs (2)
        $wpdb->insert("{$wpdb->prefix}shipping_order_logs", ['order_id' => $o1_id, 'user_id' => 1, 'action' => 'تحديث الحالة', 'old_value' => 'new', 'new_value' => 'in-progress']);
        $wpdb->insert("{$wpdb->prefix}shipping_order_logs", ['order_id' => $o1_id, 'user_id' => 1, 'action' => 'ربط شحنة', 'new_value' => 'SHP-LIVE100']);

        // 15. Tracking Events (2)
        $wpdb->insert("{$wpdb->prefix}shipping_shipment_tracking_events", ['shipment_id' => $s1_id, 'status' => 'picked-up', 'location' => 'مستودع الرياض', 'description' => 'تم استلام الشحنة من المرسل']);
        $wpdb->insert("{$wpdb->prefix}shipping_shipment_tracking_events", ['shipment_id' => $s1_id, 'status' => 'in-transit', 'location' => 'طريق الرياض الدمام', 'description' => 'الشحنة في الطريق للوجهة']);

        // 16. Contracts (2)
        $wpdb->insert("{$wpdb->prefix}shipping_contracts", ['customer_id' => 1, 'contract_number' => 'CON-Y2024-01', 'title' => 'اتفاقية شحن سنوية - مخفضة', 'start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d', strtotime('+1 year')), 'status' => 'active']);
        $wpdb->insert("{$wpdb->prefix}shipping_contracts", ['customer_id' => 2, 'contract_number' => 'CON-Y2024-02', 'title' => 'عقد توريد خدمات VIP', 'start_date' => date('Y-01-01'), 'end_date' => date('Y-12-31'), 'status' => 'active']);

        // 17. Customs Entries & Docs (2)
        $wpdb->insert("{$wpdb->prefix}shipping_customs", ['shipment_id' => $s1_id, 'documentation_status' => 'complete', 'duties_amount' => 1250.00, 'clearance_status' => 'released']);
        $wpdb->insert("{$wpdb->prefix}shipping_customs_docs", ['shipment_id' => $s1_id, 'doc_type' => 'Commercial Invoice', 'file_url' => 'https://example.com/invoice1.pdf', 'status' => 'approved']);
        $wpdb->insert("{$wpdb->prefix}shipping_customs_docs", ['shipment_id' => $s1_id, 'doc_type' => 'Packing List', 'file_url' => 'https://example.com/pack1.pdf', 'status' => 'approved']);
    }

    private static function seed_notification_templates() {
        global $wpdb;
        $table = $wpdb->prefix . 'shipping_notification_templates';
        $templates = [
            'customership_renewal' => [
                'subject' => 'تذكير: تجديد حساب Shipping',
                'body' => "عزيزي العميل {customer_name}،\n\nنود تذكيركم بقرب موعد تجديد حسابكم السنوي لعام {year}.\nيرجى السداد لضمان استمرار الخدمات.\n\nشكراً لكم.",
                'days_before' => 30
            ],
            'welcome_activation' => [
                'subject' => 'مرحباً بك في المنصة الرقمية للشحن',
                'body' => "أهلاً بك يا {customer_name}،\n\nتم تفعيل حسابك بنجاح في منصة الشحن.\nيمكنك الآن تتبع شحناتك والاستفادة من كافة الخدمات الإلكترونية.\n\nكود الحساب الخاص بك: {id_number}",
                'days_before' => 0
            ],
            'admin_alert' => [
                'subject' => 'تنبيه إداري من Shipping',
                'body' => "عزيزي العميل {customer_name}،\n\n{alert_message}\n\nشكراً لكم.",
                'days_before' => 0
            ],
            'shipment_status_update' => [
                'subject' => 'تحديث حالة الشحنة: {shipment_number}',
                'body' => "عزيزي العميل،\n\nتم تحديث حالة شحنتكم رقم {shipment_number} إلى: {status}.\n\nشكراً لاستخدامكم خدماتنا.",
                'days_before' => 0
            ],
            'shipment_delay_alert' => [
                'subject' => 'تنبيه: تأخر في وصول الشحنة {shipment_number}',
                'body' => "عزيزي العميل،\n\nنعتذر عن إبلاغكم بوجود تأخير بسيط في وصول شحنتكم رقم {shipment_number}.\nالحالة الحالية: {status}.\n\nشكراً لتفهمكم.",
                'days_before' => 0
            ]
        ];

        foreach ($templates as $type => $data) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE template_type = %s", $type));
            if (!$exists) {
                $wpdb->insert($table, [
                    'template_type' => $type,
                    'subject' => $data['subject'],
                    'body' => $data['body'],
                    'days_before' => $data['days_before'],
                    'is_enabled' => 1
                ]);
            }
        }
    }

    private static function migrate_settings() {
        // Core info migration
        $old_info = get_option('sm_company_info') ?: get_option('workedia_info');
        if ($old_info && !get_option('shipping_info')) {
            $mapped_info = [];
            foreach ((array)$old_info as $key => $value) {
                $new_key = str_replace(['company_', 'workedia_', 'sm_'], 'shipping_', $key);
                $mapped_info[$new_key] = $value;
            }
            // Ensure essential keys are present
            if (isset($old_info['company_name'])) $mapped_info['shipping_name'] = $old_info['company_name'];
            if (isset($old_info['workedia_name'])) $mapped_info['shipping_name'] = $old_info['workedia_name'];

            if (isset($old_info['company_officer_name'])) $mapped_info['shipping_officer_name'] = $old_info['company_officer_name'];
            if (isset($old_info['workedia_officer_name'])) $mapped_info['shipping_officer_name'] = $old_info['workedia_officer_name'];

            if (isset($old_info['company_logo'])) $mapped_info['shipping_logo'] = $old_info['company_logo'];
            if (isset($old_info['workedia_logo'])) $mapped_info['shipping_logo'] = $old_info['workedia_logo'];

            update_option('shipping_info', $mapped_info);
        }

        // Settings migration
        $settings_to_migrate = [
            'sm_appearance'            => 'shipping_appearance',
            'sm_labels'                => 'shipping_labels',
            'sm_notification_settings' => 'shipping_notification_settings',
            'sm_last_backup_download'  => 'shipping_last_backup_download',
            'sm_last_backup_import'    => 'shipping_last_backup_import',
            'sm_plugin_version'        => 'shipping_plugin_version'
        ];

        foreach ($settings_to_migrate as $old => $new) {
            $val = get_option($old);
            if ($val !== false && get_option($new) === false) {
                update_option($new, $val);
            }
        }
    }

    private static function migrate_tables() {
        global $wpdb;
        // Rebranding Migration (Legacy)
        $mappings = array(
            // Table Renaming
            'shipping_customers'          => 'shipping_customers',
            // Legacy Migration (sm_ -> shipping_)
            'sm_customers'                => 'shipping_customers',
            'sm_messages'               => 'shipping_messages',
            'sm_logs'                   => 'shipping_logs',
            'sm_payments'               => 'shipping_payments',
            'sm_notification_templates' => 'shipping_notification_templates',
            'sm_notification_logs'      => 'shipping_notification_logs',
            'sm_documents'              => 'shipping_documents',
            'sm_document_logs'          => 'shipping_document_logs',
            'sm_pub_templates'          => 'shipping_pub_templates',
            'sm_pub_documents'          => 'shipping_pub_documents',
            'sm_tickets'                => 'shipping_tickets',
            'sm_ticket_thread'          => 'shipping_ticket_thread',
            'sm_pages'                  => 'shipping_pages',
            'sm_articles'               => 'shipping_articles',
            'sm_alerts'                 => 'shipping_alerts',
            'sm_alert_views'            => 'shipping_alert_views'
        );

        foreach ($mappings as $old => $new) {
            $old_table = $wpdb->prefix . $old;
            $new_table = $wpdb->prefix . $new;
            if ($wpdb->get_var("SHOW TABLES LIKE '$old_table'") && !$wpdb->get_var("SHOW TABLES LIKE '$new_table'")) {
                $wpdb->query("RENAME TABLE $old_table TO $new_table");
            }
        }

        $customers_table = $wpdb->prefix . 'shipping_customers';
        if ($wpdb->get_var("SHOW TABLES LIKE '$customers_table'")) {
            // Rename national_id to username if it exists
            $col_national = $wpdb->get_results("SHOW COLUMNS FROM $customers_table LIKE 'national_id'");
            if (!empty($col_national)) {
                $wpdb->query("ALTER TABLE $customers_table CHANGE national_id username varchar(100) NOT NULL");
            }

            // Split name into first_name and last_name if name exists
            $col_name = $wpdb->get_results("SHOW COLUMNS FROM $customers_table LIKE 'name'");
            if (!empty($col_name)) {
                // Ensure first_name and last_name columns exist
                $col_first = $wpdb->get_results("SHOW COLUMNS FROM $customers_table LIKE 'first_name'");
                if (empty($col_first)) {
                    $wpdb->query("ALTER TABLE $customers_table ADD first_name tinytext NOT NULL AFTER username");
                    $wpdb->query("ALTER TABLE $customers_table ADD last_name tinytext NOT NULL AFTER first_name");

                    // Migrate data
                    $existing_customers = $wpdb->get_results("SELECT id, name FROM $customers_table");
                    foreach ($existing_customers as $m) {
                        $parts = explode(' ', $m->name);
                        $first = $parts[0];
                        $last = isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : '.';
                        $wpdb->update($customers_table, ['first_name' => $first, 'last_name' => $last], ['id' => $m->id]);
                    }
                }
                // Drop old name column
                $wpdb->query("ALTER TABLE $customers_table DROP COLUMN name");
            }

            // Drop geographic columns if they exist
            $cols_to_drop = ['governorate', 'province'];
            foreach ($cols_to_drop as $col) {
                $exists = $wpdb->get_results("SHOW COLUMNS FROM $customers_table LIKE '$col'");
                if (!empty($exists)) {
                    $wpdb->query("ALTER TABLE $customers_table DROP COLUMN $col");
                }
            }
        }
    }

    private static function setup_roles() {
        // Remove custom roles if they exist
        remove_role('shipping_system_admin');
        remove_role('shipping_admin');
        remove_role('shipping_customer');
        remove_role('shipping_officer');
        remove_role('shipping_company_admin');
        remove_role('shipping_company_customer');
        remove_role('sm_system_admin');
        remove_role('sm_company_admin');
        remove_role('sm_company_customer');
        remove_role('sm_officer');
        remove_role('sm_customer');
        remove_role('sm_parent');
        remove_role('sm_student');

        // Remove custom capabilities from administrator role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $custom_caps = [
                'shipping_manage_system',
                'shipping_manage_users',
                'shipping_manage_customers',
                'shipping_manage_finance',
                'shipping_manage_licenses',
                'shipping_print_reports',
                'shipping_full_access',
                'shipping_manage_archive'
            ];
            foreach ($custom_caps as $cap) {
                $admin_role->remove_cap($cap);
            }
        }

        self::migrate_user_meta();
        self::migrate_user_roles();
        self::sync_missing_customer_accounts();
        self::create_pages();
    }

    private static function migrate_user_meta() {
        global $wpdb;
        $meta_mappings = [
            'sm_phone' => 'shipping_phone',
            'sm_account_status' => 'shipping_account_status',
            'sm_temp_pass' => 'shipping_temp_pass',
            'sm_recovery_otp' => 'shipping_recovery_otp',
            'sm_recovery_otp_time' => 'shipping_recovery_otp_time',
            'sm_recovery_otp_used' => 'shipping_recovery_otp_used'
        ];

        foreach ($meta_mappings as $old => $new) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}usermeta SET meta_key = %s WHERE meta_key = %s",
                $new, $old
            ));
        }

        // Split name for existing users in usermeta
        $users = get_users(['fields' => ['ID', 'display_name']]);
        foreach ($users as $u) {
            if (!get_user_meta($u->ID, 'first_name', true)) {
                $parts = explode(' ', $u->display_name);
                update_user_meta($u->ID, 'first_name', $parts[0]);
                update_user_meta($u->ID, 'last_name', isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : '.');
            }
        }
    }

    private static function create_pages() {
        global $wpdb;
        $pages = array(
            'shipping-login' => array(
                'title' => 'تسجيل الدخول للنظام',
                'content' => '[shipping_login]'
            ),
            'shipping-admin' => array(
                'title' => 'لوحة الإدارة الشحن المحلي والدولي',
                'content' => '[shipping_admin]'
            ),
            'home' => array(
                'title' => 'الرئيسية',
                'content' => '[shipping_home]',
                'shortcode' => 'shipping_home'
            ),
            'about-us' => array(
                'title' => 'عن Shipping',
                'content' => '[shipping_about]',
                'shortcode' => 'shipping_about'
            ),
            'contact-us' => array(
                'title' => 'اتصل بنا',
                'content' => '[shipping_contact]',
                'shortcode' => 'shipping_contact'
            ),
            'articles' => array(
                'title' => 'أخبار ومقالات',
                'content' => '[shipping_blog]',
                'shortcode' => 'shipping_blog'
            ),
            'track-shipment' => array(
                'title' => 'تتبع الشحنات',
                'content' => '[shipping_public_tracking]',
                'shortcode' => 'shipping_public_tracking'
            ),
            'shipping-register' => array(
                'title' => 'إنشاء حساب جديد',
                'content' => '[shipping_register]',
                'shortcode' => 'shipping_register'
            )
        );

        foreach ($pages as $slug => $data) {
            $existing = get_page_by_path($slug);
            if (!$existing) {
                wp_insert_post(array(
                    'post_title'    => $data['title'],
                    'post_content'  => $data['content'],
                    'post_status'   => 'publish',
                    'post_type'     => 'page',
                    'post_name'     => $slug
                ));
            }

            // Sync with shipping_pages table
            if (isset($data['shortcode'])) {
                $table = $wpdb->prefix . 'shipping_pages';
                $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE slug = %s", $slug));
                if (!$exists) {
                    $wpdb->insert($table, array(
                        'title' => $data['title'],
                        'slug' => $slug,
                        'shortcode' => $data['shortcode'],
                        'instructions' => 'تحرير بيانات هذه الصفحة من إعدادات النظام.',
                        'settings' => json_encode(['layout' => 'standard'])
                    ));
                }
            }
        }
    }

    private static function sync_missing_customer_accounts() {
        global $wpdb;
        $customers = $wpdb->get_results("SELECT *, CONCAT(first_name, ' ', last_name) as name FROM {$wpdb->prefix}shipping_customers WHERE wp_user_id IS NULL OR wp_user_id = 0");
        foreach ($customers as $m) {
            $digits = '';
            for ($i = 0; $i < 10; $i++) {
                $digits .= mt_rand(0, 9);
            }
            $temp_pass = 'SHP' . $digits;
            $user_id = wp_insert_user([
                'user_login' => $m->username,
                'user_email' => $m->email ?: $m->username . '@shipping.com',
                'display_name' => $m->name,
                'user_pass' => $temp_pass,
                'role' => 'subscriber'
            ]);
            if (!is_wp_error($user_id)) {
                update_user_meta($user_id, 'shipping_temp_pass', $temp_pass);
                $wpdb->update("{$wpdb->prefix}shipping_customers", ['wp_user_id' => $user_id], ['id' => $m->id]);
            }
        }
    }

    private static function migrate_user_roles() {
        $role_migration = array(
            'sm_system_admin'           => 'administrator',
            'sm_company_admin'        => 'administrator',
            'sm_company_customer'       => 'subscriber',
            'sm_officer'                => 'administrator',
            'sm_customer'                 => 'subscriber',
            'sm_parent'                 => 'subscriber',
            'sm_student'                => 'subscriber',
            'shipping_system_admin'     => 'administrator',
            'shipping_admin'            => 'administrator',
            'shipping_customer'           => 'subscriber',
            'shipping_company_admin'  => 'administrator',
            'shipping_company_customer' => 'subscriber'
        );

        foreach ($role_migration as $old => $new) {
            $users = get_users(array('role' => $old));
            if (!empty($users)) {
                foreach ($users as $user) {
                    $user->add_role($new);
                    $user->remove_role($old);
                }
            }
        }
    }
}
