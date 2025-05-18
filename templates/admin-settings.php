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
?>

<div class="wrap">
    <h1>Event Participants Settings</h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('wep_settings_nonce'); ?>
        
        <div class="nav-tab-wrapper">
            <a href="#general" class="nav-tab nav-tab-active">General Settings</a>
            <a href="#variations" class="nav-tab">Variation Mappings</a>
        </div>
        
        <div id="general" class="tab-content" style="display:block;">
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
        </div>
        
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
        
        <p class="submit">
            <input type="submit" name="wep_save_settings" class="button-primary" value="Save Settings">
        </p>
    </form>
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
});
</script>