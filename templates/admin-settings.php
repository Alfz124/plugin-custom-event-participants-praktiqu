<?php
// filepath: /woo-event-participants-advanced/woo-event-participants-advanced/templates/admin-settings.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get settings
$settings = get_option('wep_settings', []);

// Handle form submission
if (isset($_POST['wep_save_settings']) && check_admin_referer('wep_settings_nonce')) {
    update_option('wep_settings', $_POST['wep_settings']);
    echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
    $settings = get_option('wep_settings', []);
}

// Load log entries
$log_entries = get_option('wep_debug_logs', []);

// Get all products with variations
$args = array(
    'post_type'      => 'product',
    'posts_per_page' => -1,
    'tax_query'      => array(
        array(
            'taxonomy' => 'product_type',
            'field'    => 'slug',
            'terms'    => 'variable',
        ),
    ),
);
$variable_products = get_posts($args);

// Get all variations
$variations = [];
foreach ($variable_products as $product) {
    $product_obj = wc_get_product($product->ID);
    $product_variations = $product_obj->get_available_variations();
    
    foreach ($product_variations as $variation) {
        $variation_obj = wc_get_product($variation['variation_id']);
        $variation_name = $variation_obj->get_name();
        $variations[$variation['variation_id']] = array(
            'product_id' => $product->ID,
            'product_name' => $product->post_title,
            'variation_id' => $variation['variation_id'],
            'variation_name' => $variation_name,
        );
    }
}

// DEBUG: Check recent orders for participant data
$recent_orders_with_participants = [];
$args = array(
    'post_type'      => 'shop_order',
    'post_status'    => array('wc-processing', 'wc-completed'),
    'posts_per_page' => 5,
    'orderby'        => 'date',
    'order'          => 'DESC',
);
$recent_orders = get_posts($args);

foreach ($recent_orders as $order_post) {
    $order_id = $order_post->ID;
    $order = wc_get_order($order_id);
    
    // Check if order has participant data
    $participant_meta = [];
    foreach ($order->get_meta_data() as $meta) {
        if (strpos($meta->key, 'peserta_') === 0) {
            $participant_meta[] = $meta;
        }
    }
    
    // Direct database query to double-check
    global $wpdb;
    $db_participant_meta = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE %s", 
        $order_id, 'peserta_%')
    );
    
    if (!empty($participant_meta) || !empty($db_participant_meta)) {
        $recent_orders_with_participants[$order_id] = [
            'order_number' => $order->get_order_number(),
            'date' => $order->get_date_created()->date('Y-m-d H:i:s'),
            'wc_api_meta' => $participant_meta,
            'direct_db_meta' => $db_participant_meta
        ];
    }
}
?>

<div class="wrap">
    <h1>Event Participants Settings</h1>
    
    <div class="nav-tab-wrapper">
        <a href="#general" class="nav-tab nav-tab-active">General Settings</a>
        <a href="#variations" class="nav-tab">Variation Mappings</a>
        <a href="#debug" class="nav-tab">Debug Info</a>
        <a href="#logs" class="nav-tab">Debug Logs</a>
        <a href="#database" class="nav-tab">Database Check</a>
    </div>
    
    <!-- General settings tab -->
    <div id="general" class="tab-content" style="display:block;">
        <form method="post" action="">
            <?php wp_nonce_field('wep_settings_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Enable Plugin</th>
                    <td>
                        <label>
                            <input type="checkbox" name="wep_settings[enable_plugin]" value="1" <?php checked(isset($settings['enable_plugin']) ? $settings['enable_plugin'] : 1); ?>>
                            Enable participant forms
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Detection Method</th>
                    <td>
                        <label>
                            <input type="radio" name="wep_settings[detection_method]" value="auto" <?php checked(isset($settings['detection_method']) ? $settings['detection_method'] : 'auto', 'auto'); ?>>
                            Automatic (detect from variation name)
                        </label><br>
                        <label>
                            <input type="radio" name="wep_settings[detection_method]" value="manual" <?php checked(isset($settings['detection_method']) ? $settings['detection_method'] : 'auto', 'manual'); ?>>
                            Manual (set participant count for each variation)
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Variation Name Pattern</th>
                    <td>
                        <input type="text" name="wep_settings[variation_pattern]" value="<?php echo esc_attr(isset($settings['variation_pattern']) ? $settings['variation_pattern'] : '/\b(\d+)\s*Orang\b/i'); ?>" class="regular-text">
                        <p class="description">The regular expression pattern to extract participant count from variation names.<br>Default: <code>/\b(\d+)\s*Orang\b/i</code> which matches patterns like "X Orang" or "X orang".</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="wep_save_settings" class="button-primary" value="Save Settings">
            </p>
        </form>
    </div>
    
    <!-- Variations tab -->
    <div id="variations" class="tab-content" style="display:none;">
        <p>Here you can manually set the number of participant forms for each product variation. This is only used when the "Manual" detection method is selected.</p>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Variation</th>
                    <th>Participants</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($variations)): ?>
                    <tr>
                        <td colspan="3">No variable products or variations found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($variations as $variation_id => $variation): ?>
                        <tr>
                            <td><?php echo esc_html($variation['product_name']); ?></td>
                            <td><?php echo esc_html($variation['variation_name']); ?></td>
                            <td>
                                <input type="number" 
                                       name="wep_settings[product_variations][<?php echo esc_attr($variation_id); ?>]" 
                                       value="<?php echo esc_attr(isset($settings['product_variations'][$variation_id]) ? $settings['product_variations'][$variation_id] : 1); ?>" 
                                       min="0" 
                                       step="1">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Debug tab -->
    <div id="debug" class="tab-content" style="display:none;">
        <h2>Plugin Debug Information</h2>
        
        <h3>Plugin Settings</h3>
        <pre><?php print_r($settings); ?></pre>
        
        <h3>Recent Orders with Participant Data</h3>
        <?php if (empty($recent_orders_with_participants)): ?>
            <p><strong>No orders with participant data found in the last 5 orders.</strong></p>
        <?php else: ?>
            <?php foreach ($recent_orders_with_participants as $order_id => $data): ?>
                <h4>Order #<?php echo esc_html($data['order_number']); ?> (<?php echo esc_html($data['date']); ?>)</h4>
                
                <h5>WC API Meta Data</h5>
                <?php if (empty($data['wc_api_meta'])): ?>
                    <p>No participant meta data found via WC API for this order.</p>
                <?php else: ?>
                    <table class="widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Meta Key</th>
                                <th>Meta Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['wc_api_meta'] as $meta): ?>
                                <tr>
                                    <td><?php echo esc_html($meta->key); ?></td>
                                    <td><?php echo esc_html($meta->value); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                
                <h5>Direct Database Query</h5>
                <?php if (empty($data['direct_db_meta'])): ?>
                    <p>No participant meta data found via direct DB query for this order.</p>
                <?php else: ?>
                    <table class="widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Meta ID</th>
                                <th>Meta Key</th>
                                <th>Meta Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['direct_db_meta'] as $meta): ?>
                                <tr>
                                    <td><?php echo esc_html($meta->meta_id); ?></td>
                                    <td><?php echo esc_html($meta->meta_key); ?></td>
                                    <td><?php echo esc_html($meta->meta_value); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                <hr>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <h3>Active Checkout Hook Tests</h3>
        <p>This section will test if the checkout hooks are properly registered:</p>
        <?php 
        $hooks = [
            'woocommerce_checkout_update_order_meta' => has_action('woocommerce_checkout_update_order_meta', ['WEP_Order_Meta', 'save_participant_data']),
            'woocommerce_before_order_notes' => has_action('woocommerce_before_order_notes', ['WEP_Checkout_Fields', 'display_participant_forms'])
        ];
        ?>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th>Hook Name</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($hooks as $hook => $status): ?>
                    <tr>
                        <td><?php echo esc_html($hook); ?></td>
                        <td><?php echo $status ? '<span style="color:green">Active</span>' : '<span style="color:red">Not found</span>'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Logs tab -->
    <div id="logs" class="tab-content" style="display:none;">
        <h2>Debug Logs</h2>
        
        <div class="log-actions" style="margin-bottom: 15px;">
            <form method="post" action="">
                <?php wp_nonce_field('wep_clear_logs_nonce'); ?>
                <input type="submit" name="wep_clear_logs" class="button" value="Clear Logs">
            </form>
        </div>
        
        <div class="log-container" style="max-height: 500px; overflow-y: auto; background-color: #f0f0f0; padding: 10px; font-family: monospace;">
            <?php if (empty($log_entries)): ?>
                <p>No log entries found.</p>
            <?php else: ?>
                <table class="widefat" style="border-collapse: collapse; width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 20%;">Time</th>
                            <th style="width: 10%;">Type</th>
                            <th style="width: 70%;">Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($log_entries) as $entry): ?>
                            <tr class="<?php echo esc_attr($entry['type']); ?>-log" style="border-bottom: 1px solid #ddd;">
                                <td style="padding: 5px;"><?php echo esc_html($entry['time']); ?></td>
                                <td style="padding: 5px; 
                                          color: <?php echo ($entry['type'] == 'error' ? 'red' : 
                                                 ($entry['type'] == 'test' ? 'blue' : 'black')); ?>;">
                                    <?php echo esc_html(strtoupper($entry['type'])); ?>
                                </td>
                                <td style="padding: 5px; word-wrap: break-word;">
                                    <pre style="margin: 0; white-space: pre-wrap;"><?php echo esc_html($entry['message']); ?></pre>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Database Check tab -->
    <div id="database" class="tab-content" style="display:none;">
        <h2>Database Check</h2>
        
        <div class="database-actions" style="margin-bottom: 15px;">
            <form method="post" action="">
                <?php wp_nonce_field('wep_test_nonce'); ?>
                <input type="submit" name="wep_run_test" class="button" value="Run Test">
            </form>
        </div>
        
        <h3>Latest Orders with Participant Data</h3>
        <?php
        global $wpdb;
        $orders_with_participant_data = $wpdb->get_results(
            "SELECT DISTINCT pm.post_id, p.post_date
             FROM {$wpdb->postmeta} pm
             JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE pm.meta_key LIKE 'peserta_%'
             AND p.post_type = 'shop_order'
             ORDER BY p.post_date DESC
             LIMIT 10"
        );
        
        if (empty($orders_with_participant_data)):
        ?>
            <p>No orders with participant data found.</p>
        <?php else: ?>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Participant Fields</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders_with_participant_data as $order): 
                        $participant_fields = $wpdb->get_results(
                            $wpdb->prepare(
                                "SELECT meta_key, meta_value FROM {$wpdb->postmeta} 
                                 WHERE post_id = %d AND meta_key LIKE 'peserta_%'",
                                $order->post_id
                            )
                        );
                        $field_count = count($participant_fields);
                    ?>
                        <tr>
                            <td><?php echo esc_html($order->post_id); ?></td>
                            <td><?php echo esc_html($order->post_date); ?></td>
                            <td><?php echo esc_html($field_count); ?> fields</td>
                            <td>
                                <button class="button toggle-details" data-order-id="<?php echo esc_attr($order->post_id); ?>">
                                    Show Details
                                </button>
                            </td>
                        </tr>
                        <tr class="order-details-<?php echo esc_attr($order->post_id); ?>" style="display:none;">
                            <td colspan="4">
                                <table class="widefat" style="margin-left: 20px; width: 95%;">
                                    <thead>
                                        <tr>
                                            <th>Meta Key</th>
                                            <th>Meta Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($participant_fields as $field): ?>
                                            <tr>
                                                <td><?php echo esc_html($field->meta_key); ?></td>
                                                <td><?php echo esc_html($field->meta_value); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <h3>Database Table Information</h3>
        <?php
        $tables = [
            $wpdb->prefix . 'posts' => 'Posts Table',
            $wpdb->prefix . 'postmeta' => 'Post Meta Table'
        ];
        
        foreach ($tables as $table => $label):
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
        ?>
            <div style="margin-bottom: 15px;">
                <strong><?php echo esc_html($label); ?>:</strong> 
                <?php echo $exists ? 
                    '<span style="color:green;">Exists</span> (' . esc_html($count) . ' rows)' : 
                    '<span style="color:red;">Missing</span>'; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab navigation
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // Hide all tab contents
        $('.tab-content').hide();
        
        // Remove active class from all tabs
        $('.nav-tab').removeClass('nav-tab-active');
        
        // Add active class to clicked tab and show its content
        $(this).addClass('nav-tab-active');
        $($(this).attr('href')).show();
    });
    
    // Toggle order details
    $('.toggle-details').on('click', function() {
        var orderId = $(this).data('order-id');
        $('.order-details-' + orderId).toggle();
        
        if ($('.order-details-' + orderId).is(':visible')) {
            $(this).text('Hide Details');
        } else {
            $(this).text('Show Details');
        }
    });
});
</script>