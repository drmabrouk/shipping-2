<?php

if ( ! class_exists( 'Shipping_DB' ) ) {
class Shipping_DB {

    public static function get_staff($args = array()) {
        $default_args = array(
            'role__in' => array('administrator', 'subscriber'),
            'number' => 20,
            'offset' => 0,
            'orderby' => 'display_name',
            'order' => 'ASC'
        );

        $args = wp_parse_args($args, $default_args);
        return get_users($args);
    }

    public static function save_user($data) {
        global $wpdb;
        $user_id = !empty($data['id']) ? intval($data['id']) : 0;
        $role = sanitize_text_field($data['role'] ?? 'subscriber');
        $username = sanitize_user($data['user_login'] ?? '');
        $email = sanitize_email($data['user_email'] ?? '');
        $first_name = sanitize_text_field($data['first_name'] ?? '');
        $last_name = sanitize_text_field($data['last_name'] ?? '');
        $display_name = trim($first_name . ' ' . $last_name);
        $pass = !empty($data['user_pass']) ? $data['user_pass'] : '';

        $user_args = [
            'user_email' => $email,
            'display_name' => $display_name,
            'first_name' => $first_name,
            'last_name' => $last_name,
        ];

        if ($user_id) {
            $user_args['ID'] = $user_id;
            if ($pass) $user_args['user_pass'] = $pass;
            $user_id = wp_update_user($user_args);
        } else {
            $user_args['user_login'] = $username;
            if (!$pass) $pass = wp_generate_password(12, false);
            $user_args['user_pass'] = $pass;
            $user_id = wp_insert_user($user_args);
            if (!is_wp_error($user_id)) {
                update_user_meta($user_id, 'shipping_temp_pass', $pass);
            }
        }

        if (is_wp_error($user_id)) return $user_id;

        $u = new WP_User($user_id);
        $u->set_role($role);

        update_user_meta($user_id, 'shipping_phone', sanitize_text_field($data['phone'] ?? ''));
        update_user_meta($user_id, 'shippingCustomerIdAttr', sanitize_text_field($data['officer_id'] ?? ''));
        update_user_meta($user_id, 'shipping_account_status', sanitize_text_field($data['account_status'] ?? 'active'));

        // Unified link to shipping_customers if role is subscriber
        if ($role === 'subscriber') {
            $customer_exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}shipping_customers WHERE wp_user_id = %d", $user_id));
            $customer_data = [
                'username' => $username ?: $u->user_login,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'phone' => sanitize_text_field($data['phone'] ?? ''),
                'wp_user_id' => $user_id,
                'account_status' => sanitize_text_field($data['account_status'] ?? 'active'),
                'id_number' => sanitize_text_field($data['id_number'] ?? $data['officer_id'] ?? '')
            ];

            if ($customer_exists) {
                $wpdb->update("{$wpdb->prefix}shipping_customers", $customer_data, ['wp_user_id' => $user_id]);
            } else {
                $customer_data['registration_date'] = current_time('Y-m-d');
                $customer_data['sort_order'] = self::get_next_sort_order();
                $wpdb->insert("{$wpdb->prefix}shipping_customers", $customer_data);
            }
        }

        return $user_id;
    }

    public static function get_customers($args = array()) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'shipping_customers';
        $query = "SELECT *, CONCAT(first_name, ' ', last_name) as name FROM $table_name WHERE 1=1";
        $params = array();

        $limit = isset($args['limit']) ? intval($args['limit']) : 20;
        $offset = isset($args['offset']) ? intval($args['offset']) : 0;

        // Ensure we don't have negative limits unless specifically -1
        if ($limit < -1) $limit = 20;

        if (isset($args['account_status']) && !empty($args['account_status'])) {
            $query .= " AND account_status = %s";
            $params[] = $args['account_status'];
        }

        if (isset($args['search']) && !empty($args['search'])) {
            $query .= " AND (first_name LIKE %s OR last_name LIKE %s OR username LIKE %s OR id_number LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
        }

        $query .= " ORDER BY sort_order ASC, first_name ASC, last_name ASC";

        if ($limit != -1) {
            $query .= " LIMIT %d OFFSET %d";
            $params[] = $limit;
            $params[] = $offset;
        }

        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($query, $params));
        }
        return $wpdb->get_results($query);
    }

    public static function get_customer_by_id($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT *, CONCAT(first_name, ' ', last_name) as name FROM {$wpdb->prefix}shipping_customers WHERE id = %d", $id));
    }

    public static function get_customer_comprehensive($id) {
        global $wpdb;
        $customer = self::get_customer_by_id($id);
        if (!$customer) return null;

        $shipments = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}shipping_shipments WHERE customer_id = %d ORDER BY created_at DESC",
            $id
        ));

        $contracts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}shipping_contracts WHERE customer_id = %d ORDER BY id DESC",
            $id
        ));

        return [
            'customer' => $customer,
            'shipments' => $shipments,
            'contracts' => $contracts
        ];
    }

    public static function get_customer_by_username($username) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT *, CONCAT(first_name, ' ', last_name) as name FROM {$wpdb->prefix}shipping_customers WHERE username = %s", $username));
    }

    public static function get_customer_by_id_number($id_number) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT *, CONCAT(first_name, ' ', last_name) as name FROM {$wpdb->prefix}shipping_customers WHERE id_number = %s", $id_number));
    }

    public static function get_customer_by_wp_username($username) {
        $user = get_user_by('login', $username);
        if (!$user) return null;
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT *, CONCAT(first_name, ' ', last_name) as name FROM {$wpdb->prefix}shipping_customers WHERE wp_user_id = %d", $user->ID));
    }

    public static function add_customer($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'shipping_customers';

        $username = sanitize_text_field($data['username'] ?? '');
        if (empty($username)) {
            return new WP_Error('invalid_username', 'اسم المستخدم مطلوب.');
        }

        // Check if username already exists
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE username = %s", $username));
        if ($exists) {
            return new WP_Error('duplicate_username', 'اسم المستخدم مسجل مسبقاً.');
        }

        $first_name = sanitize_text_field($data['first_name'] ?? '');
        $last_name = sanitize_text_field($data['last_name'] ?? '');
        $full_name = trim($first_name . ' ' . $last_name);
        $email = sanitize_email($data['email'] ?? '');

        // Auto-create WordPress User for the Customer
        $wp_user_id = null;
        $digits = '';
        for ($i = 0; $i < 10; $i++) {
            $digits .= mt_rand(0, 9);
        }
        $temp_pass = 'SHP' . $digits;

        if (!function_exists('wp_insert_user')) {
            require_once(ABSPATH . 'wp-includes/user.php');
        }

        $wp_user_id = wp_insert_user(array(
            'user_login' => $username,
            'user_email' => $email ?: $username . '@shipping.com',
            'display_name' => $full_name,
            'user_pass' => $temp_pass,
            'role' => 'subscriber'
        ));

        if (!is_wp_error($wp_user_id)) {
            $wp_user_id = $wp_user_id;
            update_user_meta($wp_user_id, 'shipping_temp_pass', $temp_pass);
            update_user_meta($wp_user_id, 'first_name', $first_name);
            update_user_meta($wp_user_id, 'last_name', $last_name);
        } else {
            return $wp_user_id; // Return WP_Error
        }

        $insert_data = array(
            'username' => $username,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'gender' => sanitize_text_field($data['gender'] ?? 'male'),
            'year_of_birth' => intval($data['year_of_birth'] ?? 0),
            'residence_street' => sanitize_textarea_field($data['residence_street'] ?? ''),
            'residence_city' => sanitize_text_field($data['residence_city'] ?? ''),
            'id_number' => sanitize_text_field($data['id_number'] ?? ''),
            'account_start_date' => sanitize_text_field($data['account_start_date'] ?? null),
            'account_expiration_date' => sanitize_text_field($data['account_expiration_date'] ?? null),
            'account_status' => sanitize_text_field($data['account_status'] ?? ''),
            'email' => $email ?: $username . '@shipping.com',
            'phone' => sanitize_text_field($data['phone'] ?? ''),
            'alt_phone' => sanitize_text_field($data['alt_phone'] ?? ''),
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
            'wp_user_id' => $wp_user_id,
            'registration_date' => current_time('Y-m-d'),
            'sort_order' => self::get_next_sort_order()
        );

        $wpdb->insert($table_name, $insert_data);
        $id = $wpdb->insert_id;

        if ($id) {
            Shipping_Logger::log('إضافة عميل جديد', "تمت إضافة العميل: $full_name بنجاح (اسم المستخدم: $username)");
        }

        return $id;
    }

    public static function add_customer_record($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'shipping_customers';

        $insert_data = array(
            'username' => sanitize_text_field($data['username']),
            'first_name' => sanitize_text_field($data['first_name']),
            'last_name' => sanitize_text_field($data['last_name']),
            'gender' => sanitize_text_field($data['gender'] ?? 'male'),
            'year_of_birth' => intval($data['year_of_birth'] ?? 0),
            'email' => sanitize_email($data['email']),
            'phone' => sanitize_text_field($data['phone'] ?? ''),
            'wp_user_id' => intval($data['wp_user_id']),
            'account_status' => sanitize_text_field($data['account_status'] ?? 'active'),
            'registration_date' => current_time('Y-m-d'),
            'sort_order' => self::get_next_sort_order()
        );

        $wpdb->insert($table_name, $insert_data);
        return $wpdb->insert_id;
    }

    public static function update_customer($id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'shipping_customers';

        $update_data = array();
        $fields = [
            'username', 'first_name', 'last_name', 'gender', 'year_of_birth',
            'residence_street', 'residence_city', 'id_number',
            'account_start_date', 'account_expiration_date',
            'account_status', 'email', 'phone', 'alt_phone', 'notes'
        ];

        foreach ($fields as $f) {
            if (isset($data[$f])) {
                if (in_array($f, ['notes', 'residence_street'])) {
                    $update_data[$f] = sanitize_textarea_field($data[$f]);
                } elseif ($f === 'email') {
                    $update_data[$f] = sanitize_email($data[$f]);
                } else {
                    $update_data[$f] = sanitize_text_field($data[$f]);
                }
            }
        }

        if (isset($data['wp_user_id'])) $update_data['wp_user_id'] = intval($data['wp_user_id']);
        if (isset($data['registration_date'])) $update_data['registration_date'] = sanitize_text_field($data['registration_date']);
        if (isset($data['sort_order'])) $update_data['sort_order'] = intval($data['sort_order']);

        $res = $wpdb->update($table_name, $update_data, array('id' => $id));

        // Sync to WP User
        $customer = self::get_customer_by_id($id);
        if ($customer && $customer->wp_user_id) {
            $user_data = ['ID' => $customer->wp_user_id];
            if (isset($data['first_name']) || isset($data['last_name'])) {
                $f = $data['first_name'] ?? $customer->first_name;
                $l = $data['last_name'] ?? $customer->last_name;
                $user_data['display_name'] = trim($f . ' ' . $l);
                update_user_meta($customer->wp_user_id, 'first_name', $f);
                update_user_meta($customer->wp_user_id, 'last_name', $l);
            }
            if (isset($data['email'])) $user_data['user_email'] = $data['email'];
            if (count($user_data) > 1) {
                wp_update_user($user_data);
            }
        }

        return $res;
    }

    public static function update_customer_photo($id, $photo_url) {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'shipping_customers', array('photo_url' => $photo_url), array('id' => $id));
    }

    public static function delete_customer($id) {
        global $wpdb;

        $customer = self::get_customer_by_id($id);
        if ($customer) {
            Shipping_Logger::log('حذف عميل (مع إمكانية الاستعادة)', 'ROLLBACK_DATA:' . json_encode(['table' => 'customers', 'data' => (array)$customer]));
            if ($customer->wp_user_id) {
                if (!function_exists('wp_delete_user')) {
                    require_once(ABSPATH . 'wp-admin/includes/user.php');
                }
                wp_delete_user($customer->wp_user_id);
            }
        }

        return $wpdb->delete($wpdb->prefix . 'shipping_customers', array('id' => $id));
    }

    public static function customer_exists($username) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}shipping_customers WHERE username = %s",
            $username
        ));
    }

    public static function get_next_sort_order() {
        global $wpdb;
        $max = $wpdb->get_var("SELECT MAX(sort_order) FROM {$wpdb->prefix}shipping_customers");
        return ($max ? intval($max) : 0) + 1;
    }

    public static function send_message($sender_id, $receiver_id, $message, $customer_id = null, $file_url = null) {
        global $wpdb;
        return $wpdb->insert($wpdb->prefix . 'shipping_messages', array(
            'sender_id' => $sender_id,
            'receiver_id' => $receiver_id,
            'customer_id' => $customer_id,
            'message' => $message,
            'file_url' => $file_url,
            'created_at' => current_time('mysql')
        ));
    }

    public static function add_order($data) {
        global $wpdb;
        $order_number = 'ORD-' . strtoupper(wp_generate_password(8, false));
        $res = $wpdb->insert($wpdb->prefix . 'shipping_orders', array(
            'order_number' => $order_number,
            'customer_id' => intval($data['customer_id']),
            'total_amount' => floatval($data['total_amount']),
            'status' => 'new',
            'pickup_address' => sanitize_textarea_field($data['pickup_address'] ?? ''),
            'delivery_address' => sanitize_textarea_field($data['delivery_address'] ?? ''),
            'order_details' => sanitize_textarea_field($data['order_details'] ?? ''),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ));

        if ($res) {
            $order_id = $wpdb->insert_id;
            self::log_order_event($order_id, 'Order Created', '', 'Status: new');
            return $order_id;
        }
        return false;
    }

    public static function update_order($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'shipping_orders';
        $old_order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id), ARRAY_A);
        if (!$old_order) return false;

        $update_data = array();
        $fields = ['status', 'total_amount', 'pickup_address', 'delivery_address', 'order_details', 'shipment_id'];
        foreach ($fields as $f) {
            if (isset($data[$f])) $update_data[$f] = $data[$f];
        }
        $update_data['updated_at'] = current_time('mysql');

        $res = $wpdb->update($table, $update_data, array('id' => $id));
        if ($res !== false) {
            foreach ($update_data as $key => $val) {
                if (isset($old_order[$key]) && $old_order[$key] != $val) {
                    self::log_order_event($id, "Updated $key", $old_order[$key], $val);
                }
            }
            return true;
        }
        return false;
    }

    public static function get_orders($args = array()) {
        global $wpdb;
        $where = "1=1";
        $params = array();

        if (!empty($args['id'])) {
            $where .= " AND o.id = %d";
            $params[] = intval($args['id']);
        }
        if (!empty($args['status'])) {
            $where .= " AND o.status = %s";
            $params[] = $args['status'];
        }
        if (!empty($args['customer_id'])) {
            $where .= " AND o.customer_id = %d";
            $params[] = intval($args['customer_id']);
        }
        if (!empty($args['search'])) {
            $s = '%' . $wpdb->esc_like($args['search']) . '%';
            $where .= " AND (o.order_number LIKE %s OR c.first_name LIKE %s OR c.last_name LIKE %s)";
            $params[] = $s; $params[] = $s; $params[] = $s;
        }

        $query = "SELECT o.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name, c.email as customer_email, c.phone as customer_phone
                  FROM {$wpdb->prefix}shipping_orders o
                  LEFT JOIN {$wpdb->prefix}shipping_customers c ON o.customer_id = c.id
                  WHERE $where ORDER BY o.created_at DESC";

        if (!empty($params)) return $wpdb->get_results($wpdb->prepare($query, $params));
        return $wpdb->get_results($query);
    }

    public static function get_order_logs($order_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, u.display_name FROM {$wpdb->prefix}shipping_order_logs l
             LEFT JOIN {$wpdb->prefix}users u ON l.user_id = u.ID
             WHERE l.order_id = %d ORDER BY l.created_at DESC",
            $order_id
        ));
    }

    public static function log_order_event($order_id, $action, $old_val = '', $new_val = '') {
        global $wpdb;
        return $wpdb->insert($wpdb->prefix . 'shipping_order_logs', array(
            'order_id' => intval($order_id),
            'user_id' => get_current_user_id(),
            'action' => sanitize_text_field($action),
            'old_value' => is_array($old_val) ? json_encode($old_val) : $old_val,
            'new_value' => is_array($new_val) ? json_encode($new_val) : $new_val,
            'created_at' => current_time('mysql')
        ));
    }

    public static function delete_order($id) {
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'shipping_order_logs', array('order_id' => $id));
        return $wpdb->delete($wpdb->prefix . 'shipping_orders', array('id' => $id));
    }


    public static function get_ticket_messages($customer_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name as sender_name
             FROM {$wpdb->prefix}shipping_messages m
             LEFT JOIN {$wpdb->prefix}users u ON m.sender_id = u.ID
             WHERE m.customer_id = %d
             ORDER BY m.created_at ASC",
            $customer_id
        ));
    }

    public static function get_conversation_messages($user1, $user2) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name as sender_name
             FROM {$wpdb->prefix}shipping_messages m
             JOIN {$wpdb->prefix}users u ON m.sender_id = u.ID
             WHERE (sender_id = %d AND receiver_id = %d)
                OR (sender_id = %d AND receiver_id = %d)
             ORDER BY created_at ASC",
            $user1, $user2, $user2, $user1
        ));
    }

    public static function get_sent_messages($user_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name as receiver_name
             FROM {$wpdb->prefix}shipping_messages m
             JOIN {$wpdb->prefix}users u ON m.receiver_id = u.ID
             WHERE m.sender_id = %d
             ORDER BY m.created_at DESC",
            $user_id
        ));
    }

    public static function delete_expired_messages() {
        global $wpdb;
        return $wpdb->query("DELETE FROM {$wpdb->prefix}shipping_messages WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)");
    }

    public static function get_conversations($user_id) {
        global $wpdb;
        $other_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT CASE WHEN sender_id = %d THEN receiver_id ELSE sender_id END
             FROM {$wpdb->prefix}shipping_messages
             WHERE sender_id = %d OR receiver_id = %d",
            $user_id, $user_id, $user_id
        ));

        $conversations = [];
        foreach ($other_ids as $oid) {
            $last_msg = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}shipping_messages
                 WHERE (sender_id = %d AND receiver_id = %d) OR (sender_id = %d AND receiver_id = %d)
                 ORDER BY created_at DESC LIMIT 1",
                $user_id, $oid, $oid, $user_id
            ));
            $conversations[] = [
                'user' => get_userdata($oid),
                'last_message' => $last_msg
            ];
        }
        return $conversations;
    }

    public static function get_officials() {
        return get_users(array('role__in' => array('administrator')));
    }

    public static function get_all_conversations() {
        global $wpdb;
        $ticket_customers = $wpdb->get_col("SELECT DISTINCT customer_id FROM {$wpdb->prefix}shipping_messages WHERE customer_id IS NOT NULL");
        $results = [];
        foreach ($ticket_customers as $mid) {
            $customer = self::get_customer_by_id($mid);
            if (!$customer) continue;
            $last_msg = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}shipping_messages WHERE customer_id = %d ORDER BY created_at DESC LIMIT 1",
                $mid
            ));
            $unread = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}shipping_messages WHERE customer_id = %d AND is_read = 0",
                $mid
            ));
            $results[] = [
                'customer' => $customer,
                'last_message' => $last_msg,
                'unread_count' => $unread
            ];
        }
        return $results;
    }

    public static function get_statistics($filters = array()) {
        global $wpdb;
        $stats = array();

        $stats['total_customers'] = intval($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}shipping_customers"));
        $stats['active_shipments'] = intval($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}shipping_shipments WHERE status != 'delivered' AND is_archived = 0"));
        $stats['delivered_shipments'] = intval($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}shipping_shipments WHERE status = 'delivered'"));
        $stats['delayed_shipments'] = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}shipping_shipments WHERE status != 'delivered' AND delivery_date < %s", current_time('mysql'))));
        $stats['total_revenue'] = floatval($wpdb->get_var("SELECT SUM(total_amount) FROM {$wpdb->prefix}shipping_invoices WHERE status = 'paid'"));
        $stats['new_orders'] = intval($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}shipping_orders WHERE status = 'new'"));

        return $stats;
    }

    public static function get_customer_stats($customer_id) {
        return array();
    }

    public static function delete_all_data() {
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}shipping_customers");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}shipping_messages");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}shipping_logs");
        Shipping_Logger::log('مسح شامل للبيانات', 'تم تنفيذ أمر مسح كافة بيانات النظام');
    }

    public static function get_backup_data() {
        global $wpdb;
        $data = array();
        $tables = array(
            'customers', 'messages', 'shipments', 'orders', 'customers',
            'logistics', 'customs', 'invoices', 'payments', 'pricing',
            'shipment_logs', 'shipment_tracking_events'
        );
        foreach ($tables as $t) {
            // Check if table exists before querying
            $table_name = $wpdb->prefix . 'shipping_' . $t;
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'")) {
                $data[$t] = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
            }
        }
        return json_encode($data);
    }

    public static function restore_backup($json) {
        global $wpdb;
        $data = json_decode($json, true);
        if (!$data) return false;

        foreach ($data as $table => $rows) {
            $table_name = $wpdb->prefix . 'shipping_' . $table;
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'")) {
                $wpdb->query("TRUNCATE TABLE $table_name");
                foreach ($rows as $row) {
                    $wpdb->insert($table_name, $row);
                }
            }
        }
        return true;
    }

    public static function add_shipment($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'shipping_shipments';
        $res = $wpdb->insert($table, array(
            'shipment_number' => $data['shipment_number'],
            'customer_id' => intval($data['customer_id']),
            'origin' => sanitize_text_field($data['origin']),
            'destination' => sanitize_text_field($data['destination']),
            'weight' => floatval($data['weight']),
            'dimensions' => sanitize_text_field($data['dimensions']),
            'classification' => sanitize_text_field($data['classification']),
            'status' => sanitize_text_field($data['status'] ?? 'pending'),
            'pickup_date' => $data['pickup_date'] ?: null,
            'dispatch_date' => $data['dispatch_date'] ?: null,
            'delivery_date' => $data['delivery_date'] ?: null,
            'carrier_id' => intval($data['carrier_id'] ?? 0),
            'route_id' => intval($data['route_id'] ?? 0),
            'estimated_cost' => floatval($data['estimated_cost'] ?? 0),
            'cost_breakdown_json' => $data['cost_breakdown_json'] ?? null
        ));
        if ($res) {
            $id = $wpdb->insert_id;
            self::log_shipment_event($id, $data['status'] ?? 'pending', 'Shipment created');

            Shipping_Logger::log('إضافة شحنة جديدة', "رقم الشحنة: {$data['shipment_number']}");

            // Notification Alert for Admins
            self::save_alert([
                'title' => 'شحنة جديدة في النظام',
                'message' => "تم إنشاء شحنة جديدة رقم {$data['shipment_number']} بنجاح.",
                'severity' => 'info',
                'status' => 'active'
            ]);

            return $id;
        }
        return false;
    }

    public static function update_shipment($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'shipping_shipments';
        $old_shipment = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id), ARRAY_A);

        $res = $wpdb->update($table, $data, array('id' => $id));
        if ($res !== false) {
            foreach ($data as $key => $val) {
                if (isset($old_shipment[$key]) && $old_shipment[$key] != $val) {
                    self::add_shipment_audit_log($id, "Updated $key", $old_shipment[$key], $val);
                }
            }
            if (isset($data['status'])) {
                self::log_shipment_event($id, $data['status'], 'Status updated');

                // Sync status to linked orders
                $order_status_map = [
                    'pending' => 'new',
                    'in-transit' => 'in-progress',
                    'out-for-delivery' => 'in-progress',
                    'delivered' => 'completed',
                    'cancelled' => 'cancelled',
                    'delayed' => 'in-progress'
                ];

                if (isset($order_status_map[$data['status']])) {
                    $wpdb->update($wpdb->prefix . 'shipping_orders',
                        ['status' => $order_status_map[$data['status']]],
                        ['shipment_id' => $id]
                    );
                }

                // Fetch full shipment for sync and alerts
                $shipment = self::get_shipment_with_tracking($id);

                // Sync status to Fleet (Vehicle)
                if ($shipment && $shipment->carrier_id) {
                    $fleet_status = 'available';
                    if (in_array($data['status'], ['in-transit', 'out-for-delivery'])) {
                        $fleet_status = 'in-transit';
                    } elseif ($data['status'] === 'delayed') {
                        $fleet_status = 'delayed';
                    }
                    $wpdb->update($wpdb->prefix . 'shipping_fleet', ['status' => $fleet_status], ['id' => $shipment->carrier_id]);
                }

                // Trigger Automated Alert
                if ($shipment && $shipment->customer_id) {
                    $customer = $wpdb->get_row($wpdb->prepare("SELECT email, CONCAT(first_name, ' ', last_name) as name FROM {$wpdb->prefix}shipping_customers WHERE id = %d", $shipment->customer_id));

                    if ($data['status'] === 'delayed') {
                        Shipping_Notifications::send_template_notification($shipment->customer_id, 'shipment_delay_alert', ['{shipment_number}' => $shipment->shipment_number, '{status}' => 'متأخرة']);

                        // Also add to System Alerts
                        self::save_alert([
                            'title' => 'تأخير في شحنة',
                            'message' => "الشحنة رقم {$shipment->shipment_number} تواجه تأخيراً حالياً.",
                            'severity' => 'warning',
                            'status' => 'active'
                        ]);
                    } elseif ($data['status'] === 'operational-issue') {
                        // Operational Issue Alert
                        self::save_alert([
                            'title' => 'مشكلة تشغيلية عاجلة',
                            'message' => "تم تسجيل مشكلة تشغيلية في الشحنة رقم {$shipment->shipment_number}. يرجى المتابعة فوراً.",
                            'severity' => 'critical',
                            'status' => 'active'
                        ]);
                    } elseif ($data['status'] === 'route-deviation') {
                        // Route Deviation Alert
                        self::save_alert([
                            'title' => 'انحراف عن المسار',
                            'message' => "تنبيه: الشحنة رقم {$shipment->shipment_number} خرجت عن المسار المحدد لها.",
                            'severity' => 'warning',
                            'status' => 'active'
                        ]);
                    } else {
                        Shipping_Notifications::send_template_notification($shipment->customer_id, 'shipment_status_update', ['{shipment_number}' => $shipment->shipment_number, '{status}' => $data['status']]);
                    }
                }
            }
            return true;
        }
        return false;
    }

    public static function log_shipment_event($shipment_id, $status, $description = '', $location = '') {
        global $wpdb;
        return $wpdb->insert($wpdb->prefix . 'shipping_shipment_tracking_events', array(
            'shipment_id' => intval($shipment_id),
            'status' => $status,
            'location' => $location,
            'description' => $description,
            'created_at' => current_time('mysql')
        ));
    }

    public static function add_shipment_audit_log($shipment_id, $action, $old_val = '', $new_val = '') {
        global $wpdb;
        return $wpdb->insert($wpdb->prefix . 'shipping_shipment_logs', array(
            'shipment_id' => intval($shipment_id),
            'user_id' => get_current_user_id(),
            'action' => $action,
            'old_value' => is_array($old_val) ? json_encode($old_val) : $old_val,
            'new_value' => is_array($new_val) ? json_encode($new_val) : $new_val,
            'created_at' => current_time('mysql')
        ));
    }

    public static function get_shipment_logs($shipment_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, u.display_name FROM {$wpdb->prefix}shipping_shipment_logs l
             LEFT JOIN {$wpdb->prefix}users u ON l.user_id = u.ID
             WHERE l.shipment_id = %d ORDER BY l.created_at DESC",
            $shipment_id
        ));
    }

    public static function get_shipment_with_tracking($id_or_number) {
        global $wpdb;
        $table = $wpdb->prefix . 'shipping_shipments';
        if (is_numeric($id_or_number)) {
            $shipment = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id_or_number));
        } else {
            $shipment = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE shipment_number = %s", $id_or_number));
        }

        if ($shipment) {
            $id = $shipment->id;
            $shipment->events = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}shipping_shipment_tracking_events WHERE shipment_id = %d ORDER BY created_at DESC",
                $id
            ));
        }
        return $shipment;
    }

    public static function bulk_add_shipments($rows) {
        $count = 0;
        foreach ($rows as $row) {
            if (self::add_shipment($row)) $count++;
        }
        return $count;
    }

    public static function archive_shipment($id) {
        return self::update_shipment($id, array('is_archived' => 1));
    }

    public static function create_invoice($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'shipping_invoices';
        $invoice_number = 'INV-' . strtoupper(wp_generate_password(8, false));
        $res = $wpdb->insert($table, array(
            'invoice_number' => $invoice_number,
            'customer_id' => intval($data['customer_id']),
            'order_id' => intval($data['order_id'] ?? 0),
            'subtotal' => floatval($data['subtotal']),
            'tax_amount' => floatval($data['tax_amount'] ?? 0),
            'discount_amount' => floatval($data['discount_amount'] ?? 0),
            'total_amount' => floatval($data['total_amount']),
            'items_json' => $data['items_json'],
            'due_date' => $data['due_date'],
            'status' => 'unpaid',
            'is_recurring' => intval($data['is_recurring'] ?? 0),
            'billing_interval' => sanitize_text_field($data['billing_interval'] ?? '')
        ));
        if ($res) {
            $id = $wpdb->insert_id;
            self::log_billing_event($id, 'Invoice Created', floatval($data['total_amount']));
            Shipping_Logger::log('إصدار فاتورة', "رقم الفاتورة: $invoice_number (المبلغ: {$data['total_amount']})");
            return $id;
        }
        return false;
    }

    public static function record_payment($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'shipping_payments';
        $res = $wpdb->insert($table, array(
            'invoice_id' => intval($data['invoice_id']),
            'transaction_id' => sanitize_text_field($data['transaction_id']),
            'amount_paid' => floatval($data['amount_paid']),
            'payment_method' => sanitize_text_field($data['payment_method']),
            'payment_status' => 'completed',
            'payment_date' => current_time('mysql')
        ));
        if ($res) {
            $invoice_id = intval($data['invoice_id']);
            $wpdb->update($wpdb->prefix . 'shipping_invoices', array('status' => 'paid'), array('id' => $invoice_id));
            self::log_billing_event($invoice_id, 'Payment Received', floatval($data['amount_paid']));

            $inv_num = $wpdb->get_var($wpdb->prepare("SELECT invoice_number FROM {$wpdb->prefix}shipping_invoices WHERE id = %d", $invoice_id));
            Shipping_Logger::log('تسجيل دفعة مادية', "الفاتورة: $inv_num (المبلغ: {$data['amount_paid']})");

            // Notification Alert for Admins
            self::save_alert([
                'title' => 'تأكيد سداد فاتورة',
                'message' => "تم استلام مبلغ {$data['amount_paid']} للفاتورة $inv_num بنجاح.",
                'severity' => 'info',
                'status' => 'active'
            ]);

            return true;
        }
        return false;
    }

    public static function get_receivables() {
        global $wpdb;
        return $wpdb->get_results("SELECT i.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name FROM {$wpdb->prefix}shipping_invoices i JOIN {$wpdb->prefix}shipping_customers c ON i.customer_id = c.id WHERE i.status != 'paid' ORDER BY i.due_date ASC");
    }

    public static function get_revenue_stats() {
        global $wpdb;
        $stats = array();
        $stats['daily'] = $wpdb->get_results("SELECT DATE(payment_date) as date, SUM(amount_paid) as total FROM {$wpdb->prefix}shipping_payments GROUP BY DATE(payment_date) ORDER BY date ASC LIMIT 30");
        $stats['monthly'] = $wpdb->get_results("SELECT DATE_FORMAT(payment_date, '%Y-%m') as month, SUM(amount_paid) as total FROM {$wpdb->prefix}shipping_payments GROUP BY month ORDER BY month ASC LIMIT 12");

        $today = date('Y-m-d');
        $month = date('Y-m');

        $stats['summary'] = [
            'today' => floatval($wpdb->get_var($wpdb->prepare("SELECT SUM(amount_paid) FROM {$wpdb->prefix}shipping_payments WHERE DATE(payment_date) = %s", $today))),
            'month' => floatval($wpdb->get_var($wpdb->prepare("SELECT SUM(amount_paid) FROM {$wpdb->prefix}shipping_payments WHERE DATE_FORMAT(payment_date, '%%Y-%%m') = %s", $month))),
            'total_revenue' => floatval($wpdb->get_var("SELECT SUM(total_amount) FROM {$wpdb->prefix}shipping_invoices WHERE status = 'paid'")),
            'total_discounts' => floatval($wpdb->get_var("SELECT SUM(discount_amount) FROM {$wpdb->prefix}shipping_invoices"))
        ];

        return $stats;
    }

    public static function log_billing_event($invoice_id, $action, $amount = 0, $details = '') {
        global $wpdb;
        return $wpdb->insert($wpdb->prefix . 'shipping_billing_logs', array(
            'invoice_id' => intval($invoice_id),
            'user_id' => get_current_user_id(),
            'action' => $action,
            'amount' => $amount,
            'details' => $details,
            'created_at' => current_time('mysql')
        ));
    }




    // Ticketing System Methods
    public static function create_ticket($data) {
        global $wpdb;
        $res = $wpdb->insert("{$wpdb->prefix}shipping_tickets", array(
            'customer_id' => intval($data['customer_id']),
            'subject' => sanitize_text_field($data['subject']),
            'category' => sanitize_text_field($data['category']),
            'priority' => sanitize_text_field($data['priority'] ?? 'medium'),
            'status' => 'open',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ));
        if ($res) {
            $ticket_id = $wpdb->insert_id;
            // Add initial message to thread
            self::add_ticket_reply(array(
                'ticket_id' => $ticket_id,
                'sender_id' => get_current_user_id(),
                'message' => $data['message'],
                'file_url' => $data['file_url'] ?? null
            ));
            return $ticket_id;
        }
        return false;
    }

    public static function add_ticket_reply($data) {
        global $wpdb;
        $res = $wpdb->insert("{$wpdb->prefix}shipping_ticket_thread", array(
            'ticket_id' => intval($data['ticket_id']),
            'sender_id' => intval($data['sender_id']),
            'message' => sanitize_textarea_field($data['message']),
            'file_url' => $data['file_url'] ?? null,
            'created_at' => current_time('mysql')
        ));
        if ($res) {
            $wpdb->update("{$wpdb->prefix}shipping_tickets", array('updated_at' => current_time('mysql')), array('id' => intval($data['ticket_id'])));
            return $wpdb->insert_id;
        }
        return false;
    }

    public static function get_tickets($args = array()) {
        global $wpdb;
        $user = wp_get_current_user();
        $is_customer = in_array('subscriber', $user->roles);

        $where = "1=1";
        $params = array();

        if ($is_customer) {
            // Find customer_id from wp_user_id
            $customer_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}shipping_customers WHERE wp_user_id = %d", $user->ID));
            $where .= " AND t.customer_id = %d";
            $params[] = intval($customer_id);
        }

        if (!empty($args['status'])) {
            $where .= " AND t.status = %s";
            $params[] = sanitize_text_field($args['status']);
        }

        if (!empty($args['category'])) {
            $where .= " AND t.category = %s";
            $params[] = sanitize_text_field($args['category']);
        }

        if (!empty($args['priority'])) {
            $where .= " AND t.priority = %s";
            $params[] = sanitize_text_field($args['priority']);
        }

        if (!empty($args['search'])) {
            $s = '%' . $wpdb->esc_like($args['search']) . '%';
            $where .= " AND (t.subject LIKE %s OR m.first_name LIKE %s OR m.last_name LIKE %s)";
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }

        $query = "SELECT t.*, CONCAT(m.first_name, ' ', m.last_name) as customer_name, m.photo_url as customer_photo
                  FROM {$wpdb->prefix}shipping_tickets t
                  JOIN {$wpdb->prefix}shipping_customers m ON t.customer_id = m.id
                  WHERE $where
                  ORDER BY t.updated_at DESC";

        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($query, $params));
        }
        return $wpdb->get_results($query);
    }

    public static function get_ticket($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT t.*, CONCAT(m.first_name, ' ', m.last_name) as customer_name, m.phone as customer_phone
             FROM {$wpdb->prefix}shipping_tickets t
             JOIN {$wpdb->prefix}shipping_customers m ON t.customer_id = m.id
             WHERE t.id = %d",
            $id
        ));
    }

    public static function get_ticket_thread($ticket_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT tr.*, u.display_name as sender_name
             FROM {$wpdb->prefix}shipping_ticket_thread tr
             LEFT JOIN {$wpdb->base_prefix}users u ON tr.sender_id = u.ID
             WHERE tr.ticket_id = %d
             ORDER BY tr.created_at ASC",
            $ticket_id
        ));
    }

    public static function update_ticket_status($id, $status) {
        global $wpdb;
        return $wpdb->update("{$wpdb->prefix}shipping_tickets", array('status' => $status), array('id' => $id));
    }

    // Page Customization Methods
    public static function get_pages() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shipping_pages ORDER BY id ASC");
    }

    public static function get_page_by_shortcode($shortcode) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}shipping_pages WHERE shortcode = %s", $shortcode));
    }

    public static function update_page($id, $data) {
        global $wpdb;
        return $wpdb->update("{$wpdb->prefix}shipping_pages", [
            'title' => sanitize_text_field($data['title']),
            'instructions' => sanitize_textarea_field($data['instructions']),
            'settings' => $data['settings']
        ], ['id' => intval($id)]);
    }

    // Article Management Methods
    public static function add_article($data) {
        global $wpdb;
        return $wpdb->insert("{$wpdb->prefix}shipping_articles", [
            'title' => sanitize_text_field($data['title']),
            'content' => wp_kses_post($data['content']),
            'image_url' => esc_url_raw($data['image_url'] ?? ''),
            'author_id' => get_current_user_id(),
            'status' => $data['status'] ?? 'publish',
            'created_at' => current_time('mysql')
        ]);
    }

    public static function get_articles($limit = 10) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}shipping_articles WHERE status = 'publish' ORDER BY created_at DESC LIMIT %d", $limit));
    }

    public static function delete_article($id) {
        global $wpdb;
        return $wpdb->delete("{$wpdb->prefix}shipping_articles", ['id' => intval($id)]);
    }

    // Global Alert System Methods (Core only)
    public static function save_alert($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'shipping_alerts';
        $insert_data = [
            'title' => sanitize_text_field($data['title']),
            'message' => wp_kses_post($data['message']),
            'severity' => sanitize_text_field($data['severity']),
            'must_acknowledge' => !empty($data['must_acknowledge']) ? 1 : 0,
            'status' => sanitize_text_field($data['status'] ?? 'active')
        ];

        if (!empty($data['id'])) {
            return $wpdb->update($table, $insert_data, ['id' => intval($data['id'])]);
        }
        return $wpdb->insert($table, $insert_data);
    }

    public static function get_active_alerts_for_user($user_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("
            SELECT a.*
            FROM {$wpdb->prefix}shipping_alerts a
            LEFT JOIN {$wpdb->prefix}shipping_alert_views v ON a.id = v.alert_id AND v.user_id = %d
            WHERE a.status = 'active'
            AND v.id IS NULL
        ", $user_id));
    }

    public static function acknowledge_alert($alert_id, $user_id) {
        global $wpdb;
        return $wpdb->insert("{$wpdb->prefix}shipping_alert_views", [
            'alert_id' => intval($alert_id),
            'user_id' => intval($user_id),
            'acknowledged' => 1,
            'created_at' => current_time('mysql')
        ]);
    }

    public static function add_route($data) {
        global $wpdb;
        $res = $wpdb->insert($wpdb->prefix . 'shipping_logistics', array(
            'route_name' => sanitize_text_field($data['route_name']),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'start_location' => sanitize_text_field($data['start_location'] ?? ''),
            'end_location' => sanitize_text_field($data['end_location'] ?? ''),
            'total_distance' => floatval($data['total_distance'] ?? 0),
            'estimated_duration' => sanitize_text_field($data['estimated_duration'] ?? '')
        ));
        if ($res) {
            Shipping_Logger::log('إضافة مسار جديد', "المسار: {$data['route_name']}");
        }
        return $res;
    }

    public static function get_routes() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shipping_logistics ORDER BY id DESC");
    }

    public static function update_route($id, $data) {
        global $wpdb;
        $res = $wpdb->update($wpdb->prefix . 'shipping_logistics', $data, array('id' => $id));
        if ($res !== false) {
            Shipping_Logger::log('تحديث مسار', "معرف المسار: $id");
        }
        return $res;
    }

    public static function delete_route($id) {
        global $wpdb;
        $name = $wpdb->get_var($wpdb->prepare("SELECT route_name FROM {$wpdb->prefix}shipping_logistics WHERE id = %d", $id));
        $wpdb->delete($wpdb->prefix . 'shipping_route_stops', array('route_id' => $id));
        $res = $wpdb->delete($wpdb->prefix . 'shipping_logistics', array('id' => $id));
        if ($res) {
            Shipping_Logger::log('حذف مسار', "المسار: $name (ID: $id)");
        }
        return $res;
    }

    public static function get_route_stops($route_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}shipping_route_stops WHERE route_id = %d ORDER BY stop_order ASC", $route_id));
    }

    public static function add_route_stop($data) {
        global $wpdb;
        return $wpdb->insert($wpdb->prefix . 'shipping_route_stops', array(
            'route_id' => intval($data['route_id']),
            'stop_name' => sanitize_text_field($data['stop_name']),
            'location' => sanitize_text_field($data['location']),
            'lat' => floatval($data['lat']),
            'lng' => floatval($data['lng']),
            'stop_order' => intval($data['stop_order'])
        ));
    }

    public static function update_route_stop($id, $data) {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'shipping_route_stops', $data, array('id' => $id));
    }

    public static function delete_route_stop($id) {
        global $wpdb;
        return $wpdb->delete($wpdb->prefix . 'shipping_route_stops', array('id' => $id));
    }

    // Warehouse Management
    public static function get_warehouses() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shipping_warehouses ORDER BY id DESC");
    }

    public static function add_warehouse($data) {
        global $wpdb;
        $res = $wpdb->insert($wpdb->prefix . 'shipping_warehouses', array(
            'name' => sanitize_text_field($data['name']),
            'location' => sanitize_text_field($data['location']),
            'total_capacity' => floatval($data['total_capacity']),
            'available_capacity' => floatval($data['total_capacity']),
            'manager_name' => sanitize_text_field($data['manager_name']),
            'contact_number' => sanitize_text_field($data['contact_number'])
        ));
        if ($res) {
            Shipping_Logger::log('إضافة مستودع جديد', "المستودع: {$data['name']}");
        }
        return $res;
    }

    public static function update_warehouse($id, $data) {
        global $wpdb;
        $res = $wpdb->update($wpdb->prefix . 'shipping_warehouses', $data, array('id' => $id));
        if ($res !== false) {
            Shipping_Logger::log('تحديث مستودع', "ID: $id");
        }
        return $res;
    }

    public static function delete_warehouse($id) {
        global $wpdb;
        $name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}shipping_warehouses WHERE id = %d", $id));
        $wpdb->delete($wpdb->prefix . 'shipping_inventory', array('warehouse_id' => $id));
        $res = $wpdb->delete($wpdb->prefix . 'shipping_warehouses', array('id' => $id));
        if ($res) {
            Shipping_Logger::log('حذف مستودع', "المستودع: $name (ID: $id)");
        }
        return $res;
    }

    // Inventory Management
    public static function get_inventory($warehouse_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}shipping_inventory WHERE warehouse_id = %d", $warehouse_id));
    }

    public static function add_inventory_item($data) {
        global $wpdb;
        $qty = intval($data['quantity']);
        $warehouse_id = intval($data['warehouse_id']);

        $res = $wpdb->insert($wpdb->prefix . 'shipping_inventory', array(
            'warehouse_id' => $warehouse_id,
            'item_name' => sanitize_text_field($data['item_name'] ?? ''),
            'sku' => sanitize_text_field($data['sku'] ?? ''),
            'quantity' => $qty,
            'unit' => sanitize_text_field($data['unit'] ?? '')
        ));

        if ($res) {
            // Deduct from warehouse capacity
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}shipping_warehouses SET available_capacity = available_capacity - %f WHERE id = %d",
                $qty, $warehouse_id
            ));
        }
        return $res;
    }

    public static function update_inventory_item($id, $data) {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'shipping_inventory', $data, array('id' => $id));
    }

    public static function delete_inventory_item($id) {
        global $wpdb;
        $item = $wpdb->get_row($wpdb->prepare("SELECT warehouse_id, quantity FROM {$wpdb->prefix}shipping_inventory WHERE id = %d", $id));
        if ($item) {
            // Restore warehouse capacity
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}shipping_warehouses SET available_capacity = available_capacity + %f WHERE id = %d",
                $item->quantity, $item->warehouse_id
            ));
        }
        return $wpdb->delete($wpdb->prefix . 'shipping_inventory', array('id' => $id));
    }

    // Fleet Management
    public static function get_fleet() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shipping_fleet ORDER BY id DESC");
    }

    public static function add_vehicle($data) {
        global $wpdb;
        $res = $wpdb->insert($wpdb->prefix . 'shipping_fleet', array(
            'vehicle_number' => sanitize_text_field($data['vehicle_number']),
            'vehicle_type' => sanitize_text_field($data['vehicle_type']),
            'capacity' => floatval($data['capacity']),
            'status' => sanitize_text_field($data['status'] ?? 'available'),
            'driver_name' => sanitize_text_field($data['driver_name']),
            'driver_phone' => sanitize_text_field($data['driver_phone']),
            'next_maintenance_date' => $data['next_maintenance_date'] ?: null
        ));
        if ($res) {
            Shipping_Logger::log('إضافة مركبة للأسطول', "المركبة: {$data['vehicle_number']}");
        }
        return $res;
    }

    public static function update_vehicle($id, $data) {
        global $wpdb;
        $res = $wpdb->update($wpdb->prefix . 'shipping_fleet', $data, array('id' => $id));
        if ($res !== false) {
            Shipping_Logger::log('تحديث مركبة', "معرف المركبة: $id");
        }
        return $res;
    }

    public static function delete_vehicle($id) {
        global $wpdb;
        $num = $wpdb->get_var($wpdb->prepare("SELECT vehicle_number FROM {$wpdb->prefix}shipping_fleet WHERE id = %d", $id));
        $wpdb->delete($wpdb->prefix . 'shipping_maintenance', array('vehicle_id' => $id));
        $res = $wpdb->delete($wpdb->prefix . 'shipping_fleet', array('id' => $id));
        if ($res) {
            Shipping_Logger::log('حذف مركبة من الأسطول', "المركبة: $num (ID: $id)");
        }
        return $res;
    }

    // Maintenance Management
    public static function get_maintenance_logs($vehicle_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}shipping_maintenance WHERE vehicle_id = %d ORDER BY maintenance_date DESC", $vehicle_id));
    }

    public static function add_maintenance_log($data) {
        global $wpdb;
        return $wpdb->insert($wpdb->prefix . 'shipping_maintenance', array(
            'vehicle_id' => intval($data['vehicle_id']),
            'maintenance_type' => sanitize_text_field($data['maintenance_type']),
            'description' => sanitize_textarea_field($data['description']),
            'cost' => floatval($data['cost']),
            'maintenance_date' => $data['maintenance_date'],
            'completed' => intval($data['completed'] ?? 0)
        ));
    }

    public static function update_maintenance_log($id, $data) {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'shipping_maintenance', $data, array('id' => $id));
    }

    public static function delete_maintenance_log($id) {
        global $wpdb;
        return $wpdb->delete($wpdb->prefix . 'shipping_maintenance', array('id' => $id));
    }

    public static function get_logistics_analytics() {
        global $wpdb;
        $analytics = array();
        $analytics['shipment_count_by_status'] = $wpdb->get_results("SELECT status, COUNT(*) as count FROM {$wpdb->prefix}shipping_shipments GROUP BY status");
        $analytics['fleet_status'] = $wpdb->get_results("SELECT status, COUNT(*) as count FROM {$wpdb->prefix}shipping_fleet GROUP BY status");
        $analytics['total_maintenance_cost'] = $wpdb->get_var("SELECT SUM(cost) FROM {$wpdb->prefix}shipping_maintenance WHERE completed = 1");
        $analytics['warehouse_utilization'] = $wpdb->get_results("SELECT name, CASE WHEN total_capacity > 0 THEN (total_capacity - available_capacity) / total_capacity * 100 ELSE 0 END as utilization FROM {$wpdb->prefix}shipping_warehouses");
        return $analytics;
    }

    public static function get_tracking_history($shipment_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}shipping_shipment_tracking_events WHERE shipment_id = %d ORDER BY created_at DESC", $shipment_id));
    }

    public static function add_customs_entry($data) {
        global $wpdb;
        return $wpdb->insert($wpdb->prefix . 'shipping_customs', array(
            'shipment_id' => intval($data['shipment_id']),
            'documentation_status' => sanitize_text_field($data['documentation_status']),
            'duties_amount' => floatval($data['duties_amount']),
            'clearance_status' => sanitize_text_field($data['clearance_status'])
        ));
    }

    public static function get_customs_entries() {
        global $wpdb;
        return $wpdb->get_results("SELECT c.*, s.shipment_number FROM {$wpdb->prefix}shipping_customs c JOIN {$wpdb->prefix}shipping_shipments s ON c.shipment_id = s.id ORDER BY c.id DESC");
    }

    public static function get_customs_docs($shipment_id = 0) {
        global $wpdb;
        $where = $shipment_id ? $wpdb->prepare("WHERE shipment_id = %d", $shipment_id) : "";
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shipping_customs_docs $where ORDER BY id DESC");
    }

    public static function add_customs_doc($data) {
        global $wpdb;
        return $wpdb->insert($wpdb->prefix . 'shipping_customs_docs', array(
            'shipment_id' => intval($data['shipment_id']),
            'doc_type' => sanitize_text_field($data['doc_type']),
            'file_url' => esc_url_raw($data['file_url']),
            'status' => 'pending'
        ));
    }

    public static function get_contracts($customer_id = 0) {
        global $wpdb;
        $where = $customer_id ? $wpdb->prepare("WHERE c.customer_id = %d", $customer_id) : "";
        return $wpdb->get_results("SELECT c.*, CONCAT(cu.first_name, ' ', cu.last_name) as customer_name FROM {$wpdb->prefix}shipping_contracts c JOIN {$wpdb->prefix}shipping_customers cu ON c.customer_id = cu.id $where ORDER BY c.id DESC");
    }

    public static function add_contract($data) {
        global $wpdb;
        return $wpdb->insert($wpdb->prefix . 'shipping_contracts', array(
            'customer_id' => intval($data['customer_id']),
            'contract_number' => 'CON-' . strtoupper(wp_generate_password(8, false)),
            'title' => sanitize_text_field($data['title']),
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'status' => 'active',
            'file_url' => esc_url_raw($data['file_url'] ?? ''),
            'notes' => sanitize_textarea_field($data['notes'] ?? '')
        ));
    }

    public static function add_pricing_rule($data) {
        global $wpdb;
        return $wpdb->insert($wpdb->prefix . 'shipping_pricing_rules', array(
            'rule_name' => sanitize_text_field($data['rule_name'] ?? $data['name']),
            'customer_type' => sanitize_text_field($data['customer_type'] ?? 'all'),
            'shipment_category' => sanitize_text_field($data['shipment_category'] ?? 'all'),
            'min_weight' => floatval($data['min_weight'] ?? 0),
            'max_weight' => floatval($data['max_weight'] ?? 999999.99),
            'base_price' => floatval($data['base_price'] ?? 0),
            'price_per_kg' => floatval($data['price_per_kg'] ?? 0),
            'price_per_km' => floatval($data['price_per_km'] ?? 0),
            'is_active' => 1
        ));
    }

    public static function get_pricing_rules() {
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shipping_pricing_rules ORDER BY id DESC");
        foreach ($results as &$r) {
            $r->name = $r->rule_name; // JS compatibility
        }
        return $results;
    }

    public static function delete_pricing_rule($id) {
        global $wpdb;
        return $wpdb->delete($wpdb->prefix . 'shipping_pricing_rules', array('id' => $id));
    }

    // Additional Fees
    public static function add_additional_fee($data) {
        global $wpdb;
        return $wpdb->insert($wpdb->prefix . 'shipping_additional_fees', array(
            'fee_name' => sanitize_text_field($data['fee_name']),
            'fee_type' => sanitize_text_field($data['fee_type']),
            'fee_value' => floatval($data['fee_value']),
            'apply_to' => sanitize_text_field($data['apply_to'] ?? 'all'),
            'is_automated' => 1
        ));
    }

    public static function get_additional_fees() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shipping_additional_fees ORDER BY id DESC");
    }

    public static function delete_additional_fee($id) {
        global $wpdb;
        return $wpdb->delete($wpdb->prefix . 'shipping_additional_fees', array('id' => $id));
    }

    // Advanced Cost Estimation Logic
    public static function estimate_shipment_cost($data) {
        global $wpdb;
        $weight = floatval($data['weight']);
        $distance = floatval($data['distance'] ?? 0);
        $customer_id = intval($data['customer_id'] ?? 0);
        $category = sanitize_text_field($data['classification'] ?? 'standard');
        $is_urgent = !empty($data['is_urgent']);
        $is_insured = !empty($data['is_insured']);

        $customer = self::get_customer_by_id($customer_id);
        $customer_type = $customer ? ($customer->classification ?: 'regular') : 'all';

        // 1. Find Best Rule
        $rule = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}shipping_pricing_rules
             WHERE is_active = 1
             AND (customer_type = %s OR customer_type = 'all')
             AND (shipment_category = %s OR shipment_category = 'all')
             AND %f BETWEEN min_weight AND max_weight
             ORDER BY CASE WHEN customer_type != 'all' THEN 0 ELSE 1 END,
                      CASE WHEN shipment_category != 'all' THEN 0 ELSE 1 END
             LIMIT 1",
            $customer_type, $category, $weight
        ));

        if (!$rule) {
            $rule = (object)['base_price' => 50, 'price_per_kg' => 5, 'price_per_km' => 2];
        }

        $calc_weight_cost = $weight * $rule->price_per_kg;
        $calc_distance_cost = $distance * $rule->price_per_km;

        // 2. Add Automated Fees
        $fees_list = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shipping_additional_fees WHERE is_automated = 1");
        $total_fees = 0;

        foreach ($fees_list as $f) {
            $amt = ($f->fee_type === 'percentage') ? ($rule->base_price * ($f->fee_value / 100)) : $f->fee_value;
            $total_fees += $amt;
        }

        if ($is_urgent) $total_fees += ($rule->base_price * 0.5); // 50% extra for urgency
        if ($is_insured) $total_fees += ($rule->base_price * 0.1); // 10% extra for insurance

        // 3. Final Calculation
        $total_before_discount = $rule->base_price + $calc_weight_cost + $calc_distance_cost + $total_fees;
        $discount = 0;

        $final_total = max(0, $total_before_discount - $discount);

        // Structure expected by JS
        return [
            'total_cost' => $final_total,
            'breakdown' => [
                'base' => floatval($rule->base_price),
                'weight' => $calc_weight_cost,
                'distance' => $calc_distance_cost,
                'fees' => $total_fees,
                'discount' => $discount
            ],
            'rule_applied' => $rule->rule_name ?? 'Default'
        ];
    }
}
}
