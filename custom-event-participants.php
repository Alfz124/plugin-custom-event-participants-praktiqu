<?php
/**
 * Plugin Name: Custom Event Participants
 * Description: A plugin to manage participant forms for WooCommerce products with advanced settings.
 * Version: 1.0
 * Author: Ahmad Luthfi Fahrizi
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WEP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WEP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once WEP_PLUGIN_DIR . 'includes/class-wep-admin-menu.php';
require_once WEP_PLUGIN_DIR . 'includes/class-wep-checkout-fields.php';
require_once WEP_PLUGIN_DIR . 'includes/class-wep-order-meta.php';
require_once WEP_PLUGIN_DIR . 'includes/class-wep-settings.php';

// Initialize the plugin
function wep_init() {
    // Load admin menu
    new WEP_Admin_Menu();
    
    // Load checkout fields
    new WEP_Checkout_Fields();
    
    // Load order meta
    new WEP_Order_Meta();
    
    // Load settings
    new WEP_Settings();
}
add_action('plugins_loaded', 'wep_init');

// Enqueue admin styles and scripts
function wep_enqueue_admin_scripts($hook) {
    if ($hook != 'toplevel_page_wep-settings') {
        return;
    }
    wp_enqueue_style('wep-admin-style', WEP_PLUGIN_URL . 'assets/css/admin-style.css');
    wp_enqueue_script('wep-admin-script', WEP_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), null, true);
}
add_action('admin_enqueue_scripts', 'wep_enqueue_admin_scripts');

// Enqueue frontend styles
function wep_enqueue_frontend_scripts() {
    if (is_checkout()) {
        wp_enqueue_style('wep-frontend-style', WEP_PLUGIN_URL . 'assets/css/frontend-style.css');
    }
}
add_action('wp_enqueue_scripts', 'wep_enqueue_frontend_scripts');
?>