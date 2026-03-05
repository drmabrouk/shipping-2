<?php
/**
 * Plugin Name: Shipping
 * Description: نظام شامل لإدارة الشحن المحلي والدولي المحلي والدولي.
 * Version: 97.3.0
 * Author: Shipping
 * Language: ar
 * Text Domain: shipping
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SHIPPING_VERSION', '97.3.0');
define('SHIPPING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SHIPPING_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_shipping() {
    require_once SHIPPING_PLUGIN_DIR . 'includes/class-shipping-activator.php';
    Shipping_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_shipping() {
    require_once SHIPPING_PLUGIN_DIR . 'includes/class-shipping-deactivator.php';
    Shipping_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_shipping');
register_deactivation_hook(__FILE__, 'deactivate_shipping');

/**
 * Core class used to maintain the plugin.
 */
require_once SHIPPING_PLUGIN_DIR . 'includes/class-shipping.php';

function run_shipping() {
    $plugin = new Shipping();
    $plugin->run();
}

// Global settings save handler
add_action('admin_init', function() {
    if (isset($_POST['shipping_save_settings_unified'])) {
        check_admin_referer('shipping_admin_action', 'shipping_admin_nonce');

        $info = Shipping_Settings::get_shipping_info();
        $info['shipping_name'] = sanitize_text_field($_POST['shipping_name']);
        $info['shipping_officer_name'] = sanitize_text_field($_POST['shipping_officer_name']);
        $info['phone'] = sanitize_text_field($_POST['shipping_phone']);
        $info['email'] = sanitize_email($_POST['shipping_email']);
        $info['address'] = sanitize_text_field($_POST['shipping_address']);
        $info['map_link'] = esc_url_raw($_POST['shipping_map_link']);
        $info['extra_details'] = sanitize_textarea_field($_POST['shipping_extra_details']);
        $info['shipping_logo'] = esc_url_raw($_POST['shipping_logo']);
        $info['currency'] = sanitize_text_field($_POST['shipping_currency']);
        $info['map_link'] = esc_url_raw($_POST['shipping_map_link'] ?? '');
        $info['extra_details'] = sanitize_textarea_field($_POST['shipping_extra_details'] ?? '');

        Shipping_Settings::save_shipping_info($info);

        // Save labels too
        $labels = Shipping_Settings::get_labels();
        foreach($labels as $key => $val) {
            if (isset($_POST[$key])) {
                $labels[$key] = sanitize_text_field($_POST[$key]);
            }
        }
        Shipping_Settings::save_labels($labels);

        wp_redirect(add_query_arg(['shipping_tab' => 'advanced-settings', 'sub' => 'init', 'settings_saved' => 1], admin_url('admin.php?page=shipping-admin')));
        exit;
    }
});

run_shipping();
