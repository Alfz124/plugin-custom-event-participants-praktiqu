<?php
class WEP_Order_Meta {
    private $settings;

    public function __construct() {
        $this->settings = get_option('wep_settings', []);
        
        // Log initialization
        error_log('WEP_Order_Meta initialized');
        
        // CORRECT: Use $this to reference the current instance method
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_participant_data'));
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_participant_data_admin'), 10, 1);
    }

    public function save_participant_data($order_id) {
        // Test value to verify hook is working
        update_post_meta($order_id, 'participant_hook_test', 'Hook is working!');
        
        // Log that this function was called with the order ID
        error_log('WEP_Order_Meta::save_participant_data called for order #' . $order_id);
        
        // Log the POST data to see what's coming in
        error_log('POST data: ' . print_r($_POST, true));
        
        // Save the default customer info as participant 1
        $order = wc_get_order($order_id);
        
        if ($order) {
            // Log that we have a valid order
            error_log('Valid order object found for #' . $order_id);
            
            // Save default customer billing info as participant 1 data
            update_post_meta($order_id, 'peserta_1_nama_depan', $order->get_billing_first_name());
            update_post_meta($order_id, 'peserta_1_nama_belakang', $order->get_billing_last_name());
            update_post_meta($order_id, 'peserta_1_telepon', $order->get_billing_phone());
            update_post_meta($order_id, 'peserta_1_email', $order->get_billing_email());
            update_post_meta($order_id, 'peserta_1_kota', $order->get_billing_city());
            
            // No direct field for occupation in standard WooCommerce, so leave it blank
            update_post_meta($order_id, 'peserta_1_pekerjaan', '');
            
            // Log successful first participant data storage
            error_log('Participant 1 data stored for order #' . $order_id);
            
            // Verify data was actually saved
            $first_name = get_post_meta($order_id, 'peserta_1_nama_depan', true);
            error_log('Verification - peserta_1_nama_depan value: ' . $first_name);
        } else {
            // Log failure to get order
            error_log('Failed to get order object for #' . $order_id);
        }
        
        // Log the filtered POST keys to check for participant data
        $participant_fields = array_filter(array_keys($_POST), function($key) {
            return strpos($key, 'peserta_') === 0;
        });
        error_log('Participant fields found in POST: ' . print_r($participant_fields, true));
        
        // Save data for additional participants (2 and above)
        $saved_fields = 0;
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'peserta_') === 0) {
                $result = update_post_meta($order_id, $key, sanitize_text_field($value));
                error_log('Saving field ' . $key . ' for order #' . $order_id . ': ' . ($result ? 'Success' : 'Failed'));
                $saved_fields++;
            }
        }
        
        // Log how many participant fields were saved
        error_log('Saved ' . $saved_fields . ' participant fields for order #' . $order_id);
        
        // Double-check with direct database query
        global $wpdb;
        $meta_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE %s",
                $order_id, 'peserta_%'
            )
        );
        error_log('Database verification - Order #' . $order_id . ' has ' . $meta_count . ' participant meta entries');
    }

    public function display_participant_data_admin($order) {
        // Check if we have any participant data
        $has_participant_data = false;
        foreach ($order->get_meta_data() as $meta) {
            if (strpos($meta->key, 'peserta_') === 0) {
                $has_participant_data = true;
                break;
            }
        }
        
        if (!$has_participant_data) {
            return;
        }
        
        echo '<h3>Data Peserta</h3>';
        
        // Group participant data for better display
        $participants = [];
        foreach ($order->get_meta_data() as $meta) {
            if (strpos($meta->key, 'peserta_') === 0) {
                $parts = explode('_', $meta->key);
                if (count($parts) >= 3) {
                    $participant_num = $parts[1];
                    $field_name = $parts[2];
                    if (isset($parts[3])) {
                        $field_name .= '_' . $parts[3];
                    }
                    
                    if (!isset($participants[$participant_num])) {
                        $participants[$participant_num] = [];
                    }
                    
                    $participants[$participant_num][$field_name] = $meta->value;
                }
            }
        }
        
        // Display participants in a more readable format
        if (!empty($participants)) {
            echo '<table class="widefat fixed" style="margin-bottom: 20px;">';
            echo '<thead><tr>';
            echo '<th>Peserta #</th>';
            echo '<th>Nama</th>';
            echo '<th>Email</th>';
            echo '<th>Telepon</th>';
            echo '<th>Kota</th>';
            echo '<th>Pekerjaan</th>';
            echo '</tr></thead>';
            echo '<tbody>';
            
            ksort($participants); // Sort by participant number
            
            foreach ($participants as $num => $data) {
                echo '<tr>';
                echo '<td>' . esc_html($num) . '</td>';
                echo '<td>' . esc_html(($data['nama_depan'] ?? '') . ' ' . ($data['nama_belakang'] ?? '')) . '</td>';
                echo '<td>' . esc_html($data['email'] ?? '') . '</td>';
                echo '<td>' . esc_html($data['telepon'] ?? '') . '</td>';
                echo '<td>' . esc_html($data['kota'] ?? '') . '</td>';
                echo '<td>' . esc_html($data['pekerjaan'] ?? '') . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        }
    }
}
?>