<?php
class WEP_Admin_Menu {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
    }

    public function add_menu() {
        add_menu_page(
            'Event Participants Settings',
            'Event Participants',
            'manage_options',
            'wep-settings',
            [$this, 'settings_page'],
            'dashicons-groups',
            56
        );
    }

    public function settings_page() {
        // Check if the user has the required capability
        if (!current_user_can('manage_options')) {
            return;
        }

        // Include the settings template
        include_once plugin_dir_path(__FILE__) . '../templates/admin-settings.php';
    }
}
?>