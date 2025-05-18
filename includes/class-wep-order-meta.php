<?php
class WEP_Order_Meta {
    private $settings;

    public function __construct() {
        $this->settings = get_option('wep_settings', []);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'save_participant_data']);
        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'display_participant_data_admin'], 10, 1);
    }

    public function save_participant_data($order_id) {
        // Save the default customer info as participant 1
        $order = wc_get_order($order_id);
        
        if ($order) {
            // Save default customer billing info as participant 1 data
            update_post_meta($order_id, 'peserta_1_nama_depan', $order->get_billing_first_name());
            update_post_meta($order_id, 'peserta_1_nama_belakang', $order->get_billing_last_name());
            update_post_meta($order_id, 'peserta_1_telepon', $order->get_billing_phone());
            update_post_meta($order_id, 'peserta_1_email', $order->get_billing_email());
            update_post_meta($order_id, 'peserta_1_kota', $order->get_billing_city());
            
            // No direct field for occupation in standard WooCommerce, so leave it blank
            update_post_meta($order_id, 'peserta_1_pekerjaan', '');
        }
        
        // Save data for additional participants (2 and above)
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'peserta_') === 0) {
                update_post_meta($order_id, $key, sanitize_text_field($value));
            }
        }
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