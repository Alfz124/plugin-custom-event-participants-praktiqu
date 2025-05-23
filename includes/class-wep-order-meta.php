<?php
class WEP_Order_Meta {
    private $settings;

    public function __construct() {
        $this->settings = get_option('wep_settings', []);
        

        // Log initialization using our custom logger if available
        if (function_exists('wep_log')) {
            wep_log('WEP_Order_Meta initialized');
        } else {
            error_log('WEP_Order_Meta initialized');
        }
        
        // CORRECT: Use $this to reference the current instance method
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_participant_data'));
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_participant_data_admin'), 10, 1);
    }

public function save_participant_data($order_id) {
    // Use our custom logger if available
    $log = function_exists('wep_log') ? 'wep_log' : 'error_log';

    $log("WEP_Order_Meta::save_participant_data triggered for order #{$order_id}");

    // Log the raw $_POST for debugging
    $log("Raw POST data: " . print_r($_POST, true));

    // Load the WC_Order object
    $order = wc_get_order($order_id);

    if (!$order) {
        $log("❌ Failed to get WC_Order object for order #{$order_id}");
        return;
    }

    $log("✅ Valid order object loaded for order #{$order_id}");

    // Save customer billing info as participant 1
    $order->update_meta_data('peserta_1_nama_depan', $order->get_billing_first_name());
    $order->update_meta_data('peserta_1_nama_belakang', $order->get_billing_last_name());
    $order->update_meta_data('peserta_1_telepon', $order->get_billing_phone());
    $order->update_meta_data('peserta_1_email', $order->get_billing_email());
    $order->update_meta_data('peserta_1_kota', $order->get_billing_city());
    $order->update_meta_data('peserta_1_pekerjaan', ''); // no default field

    $log("📝 Participant 1 data saved to order meta");

    // Save any additional participant fields from $_POST
    $saved_fields = 0;
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'peserta_') === 0) {
            $clean_value = sanitize_text_field($value);
            $order->update_meta_data($key, $clean_value);
            $log("🔐 Saved meta '{$key}' with value '{$clean_value}'");
            $saved_fields++;
        }
    }

    // Persist all meta changes
    $order->save();
    $log("💾 Order #{$order_id} saved with {$saved_fields} custom participant fields");

    // Final sanity check — fetch what's now stored
    global $wpdb;
    $meta_entries = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT meta_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE %s",
            $order_id, 'peserta_%'
        )
    );
    $log("🧪 DB Verification - Found meta entries: " . print_r($meta_entries, true));
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