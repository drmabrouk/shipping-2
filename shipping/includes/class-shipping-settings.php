<?php

class Shipping_Settings {

    public static function get_appearance() {
        $default = array(
            'primary_color' => '#F63049',
            'secondary_color' => '#D02752',
            'accent_color' => '#8A244B',
            'dark_color' => '#111F35',
            'bg_color' => '#ffffff',
            'sidebar_bg_color' => '#f8fafc',
            'font_color' => '#111F35',
            'border_color' => '#e2e8f0',
            'btn_color' => '#111F35',
            'font_size' => '15px',
            'font_weight' => '400',
            'line_spacing' => '1.5',
            'border_radius' => '12px',
            'table_style' => 'modern',
            'button_style' => 'flat'
        );
        return wp_parse_args(get_option('shipping_appearance', array()), $default);
    }

    public static function get_labels() {
        $default = array(
            'tab_summary' => 'نظرة عامة',
            'tab_users_management' => 'إدارة مستخدمي النظام (كافة الأدوار)',
            'tab_global_settings' => 'إعدادات النظام',
            'tab_my_profile' => 'ملفي الشخصي',
            'tab_general_stats' => 'الإحصائيات العامة',
            'tab_shipment_mgmt' => 'إدارة الشحنات',
            'tab_customer_mgmt' => 'إدارة العملاء (العملاء فقط)',
            'tab_order_mgmt' => 'إدارة الطلبات',
            'tab_tracking_logistics' => 'التتبع واللوجستيات',
            'tab_customs_clearance' => 'التخليص الجمركي',
            'tab_billing_payments' => 'الفواتير والمدفوعات'
        );
        return wp_parse_args(get_option('shipping_labels', array()), $default);
    }

    public static function save_labels($labels) {
        update_option('shipping_labels', $labels);
    }

    public static function save_appearance($data) {
        update_option('shipping_appearance', $data);
    }

    public static function get_notifications() {
        $default = array(
            'email_subject' => 'إشعار من Shipping بخصوص العميل: {customer_name}',
            'email_template' => "تحية طيبة، نود إخطاركم بخصوص العميل: {customer_name}\nالتفاصيل: {details}",
            'whatsapp_template' => "تنبيه من Shipping بخصوص العميل {customer_name}. تفاصيل: {details}.",
            'internal_template' => "إشعار نظام بخصوص العميل {customer_name}."
        );
        return get_option('shipping_notification_settings', $default);
    }

    public static function save_notifications($data) {
        update_option('shipping_notification_settings', $data);
    }

    public static function get_shipping_info() {
        $default = array(
            'shipping_name' => 'Shipping',
            'shipping_officer_name' => 'Admin',
            'shipping_logo' => '',
            'address' => 'Cairo, Egypt',
            'email' => 'info@shipping.com',
            'phone' => '0123456789',
            'website_url' => '',
            'map_link' => '',
            'extra_details' => '',
            'currency' => 'SAR'
        );
        return get_option('shipping_info', $default);
    }

    public static function save_shipping_info($data) {
        update_option('shipping_info', $data);
    }

    public static function get_retention_settings() {
        $default = array(
            'message_retention_days' => 90
        );
        return get_option('shipping_retention_settings', $default);
    }

    public static function save_retention_settings($data) {
        update_option('shipping_retention_settings', $data);
    }

    public static function record_backup_download() {
        update_option('shipping_last_backup_download', current_time('mysql'));
    }

    public static function record_backup_import() {
        update_option('shipping_last_backup_import', current_time('mysql'));
    }

    public static function get_last_backup_info() {
        return array(
            'export' => get_option('shipping_last_backup_download', 'لم يتم التصدير مسبقاً'),
            'import' => get_option('shipping_last_backup_import', 'لم يتم الاستيراد مسبقاً')
        );
    }



    public static function get_account_statuses() {
        return array(
            'active' => 'نشط',
            'inactive' => 'غير نشط',
            'pending' => 'قيد الانتظار',
            'expired' => 'منتهي'
        );
    }


}
