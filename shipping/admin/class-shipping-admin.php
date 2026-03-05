<?php

class Shipping_Admin {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function add_menu_pages() {
        add_menu_page(
            'Shipping',
            'Shipping',
            'read', // Allow all roles to see top level
            'shipping-dashboard',
            array($this, 'display_dashboard'),
            'dashicons-welcome-learn-more',
            6
        );

        add_submenu_page(
            'shipping-dashboard',
            'لوحة التحكم',
            'لوحة التحكم',
            'read',
            'shipping-dashboard',
            array($this, 'display_dashboard')
        );



        add_submenu_page(
            'shipping-dashboard',
            'الإعدادات المتقدمة',
            'الإعدادات المتقدمة',
            'manage_options',
            'shipping-advanced',
            array($this, 'display_advanced_settings')
        );
    }

    public function display_advanced_settings() {
        $_GET['shipping_tab'] = 'advanced-settings';
        $this->display_settings();
    }

    public function enqueue_styles() {
        wp_enqueue_media();
        wp_enqueue_style('google-font-rubik', 'https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;700;800;900&display=swap', array(), null);
        wp_add_inline_script('jquery', 'var ajaxurl = "' . admin_url('admin-ajax.php') . '";', 'before');
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '4.4.1', true);
        wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4');
        wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true);
        wp_enqueue_style($this->plugin_name, SHIPPING_PLUGIN_URL . 'assets/css/shipping-admin.css', array(), $this->version, 'all');

        // Modular JS Controllers
        wp_enqueue_script('shipping-core', SHIPPING_PLUGIN_URL . 'assets/js/shipping-core.js', array('jquery'), $this->version, true);
        wp_enqueue_script('shipping-admin', SHIPPING_PLUGIN_URL . 'assets/js/admin-controller.js', array('shipping-core'), $this->version, true);

        $info = Shipping_Settings::get_shipping_info();
        wp_localize_script('shipping-core', 'shippingVars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'adminUrl' => admin_url('admin.php?page=shipping-admin'),
            'currency' => $info['currency'] ?? 'SAR',
            'nonce' => wp_create_nonce('shipping_admin_action'),
            'profileNonce' => wp_create_nonce('shipping_profile_action'),
        ));

        $appearance = Shipping_Settings::get_appearance();
        $custom_css = "
            :root {
                --shipping-primary-color: {$appearance['primary_color']};
                --shipping-secondary-color: {$appearance['secondary_color']};
                --shipping-accent-color: {$appearance['accent_color']};
                --shipping-dark-color: {$appearance['dark_color']};
                --shipping-radius: {$appearance['border_radius']};
            }
            .shipping-content-wrapper, .shipping-admin-dashboard, .shipping-container,
            .shipping-content-wrapper *:not(.dashicons), .shipping-admin-dashboard *:not(.dashicons), .shipping-container *:not(.dashicons) {
                font-family: 'Rubik', sans-serif !important;
            }
            .shipping-content-wrapper { font-size: {$appearance['font_size']}; }
        ";
        wp_add_inline_style($this->plugin_name, $custom_css);
    }

    public function display_dashboard() {
        $_GET['shipping_tab'] = 'summary';
        $this->display_settings();
    }


    public function display_settings() {
        if (isset($_POST['shipping_save_settings_unified'])) {
            check_admin_referer('shipping_admin_action', 'shipping_admin_nonce');

            // 1. Save Shipping Info
            $info = Shipping_Settings::get_shipping_info();
            $info['shipping_name'] = sanitize_text_field($_POST['shipping_name']);
            $info['shipping_officer_name'] = sanitize_text_field($_POST['shipping_officer_name']);
            $info['phone'] = sanitize_text_field($_POST['shipping_phone']);
            $info['email'] = sanitize_email($_POST['shipping_email']);
            $info['shipping_logo'] = esc_url_raw($_POST['shipping_logo']);
            $info['address'] = sanitize_text_field($_POST['shipping_address']);
            $info['map_link'] = esc_url_raw($_POST['shipping_map_link'] ?? '');
            $info['extra_details'] = sanitize_textarea_field($_POST['shipping_extra_details'] ?? '');

            Shipping_Settings::save_shipping_info($info);

            // 2. Save Section Labels
            $labels = Shipping_Settings::get_labels();
            foreach($labels as $key => $val) {
                if (isset($_POST[$key])) {
                    $labels[$key] = sanitize_text_field($_POST[$key]);
                }
            }
            Shipping_Settings::save_labels($labels);

            wp_redirect(add_query_arg(['shipping_tab' => 'advanced-settings', 'sub' => 'init', 'settings_saved' => 1], wp_get_referer()));
            exit;
        }

        if (isset($_GET['settings_saved'])) {
            echo '<div class="updated notice is-dismissible"><p>تم حفظ الإعدادات بنجاح.</p></div>';
        }

        if (isset($_POST['shipping_save_appearance'])) {
            check_admin_referer('shipping_admin_action', 'shipping_admin_nonce');
            Shipping_Settings::save_appearance(array(
                'primary_color' => sanitize_hex_color($_POST['primary_color']),
                'secondary_color' => sanitize_hex_color($_POST['secondary_color']),
                'accent_color' => sanitize_hex_color($_POST['accent_color']),
                'dark_color' => sanitize_hex_color($_POST['dark_color']),
                'font_size' => sanitize_text_field($_POST['font_size']),
                'border_radius' => sanitize_text_field($_POST['border_radius']),
                'table_style' => sanitize_text_field($_POST['table_style']),
                'button_style' => sanitize_text_field($_POST['button_style'])
            ));
            wp_redirect(add_query_arg(['shipping_tab' => 'advanced-settings', 'sub' => 'design', 'settings_saved' => 1], wp_get_referer()));
            exit;
        }



        $customer_filters = array();
        $stats = Shipping_DB::get_statistics();
        $customers = Shipping_DB::get_customers();
        include SHIPPING_PLUGIN_DIR . 'templates/public-admin-panel.php';
    }

    public function display_users_management() {
        $_GET['shipping_tab'] = 'users-management';
        $this->display_settings();
    }

}
