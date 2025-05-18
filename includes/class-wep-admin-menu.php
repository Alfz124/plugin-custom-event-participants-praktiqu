<?php
class WEP_Admin_Menu {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }

    public function add_admin_menu() {
        add_menu_page(
            'Event Participants Settings',
            'Event Participants',
            'manage_options',
            'wep-settings',
            [$this, 'display_settings_page'],
            'dashicons-groups',
            30
        );
    }

    public function display_settings_page() {
        require_once WEP_PLUGIN_DIR . 'templates/admin-settings.php';
    }
}
?>