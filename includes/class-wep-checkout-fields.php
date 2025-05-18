<?php
class WEP_Checkout_Fields {
    private $settings;

    public function __construct() {
        $this->settings = get_option('wep_settings', []);
        
        // Log initialization
        error_log('WEP_Checkout_Fields initialized with settings: ' . print_r($this->settings, true));
        
        // CORRECT: Use $this to reference the current instance method
        add_action('woocommerce_before_order_notes', array($this, 'display_participant_forms'));
    }

    public function display_participant_forms($checkout) {
        // Check if plugin is enabled
        if (isset($this->settings['enable_plugin']) && $this->settings['enable_plugin'] == 0) {
            error_log('Plugin is disabled. Not displaying participant forms.');
            return;
        }
        
        $jumlah_peserta_total = $this->calculate_total_participants();
        
        // Log the calculated number of participants
        error_log('Total participants calculated: ' . $jumlah_peserta_total);
        
        // If only one or zero participants needed, exit (as the first participant is the default checkout customer)
        if ($jumlah_peserta_total <= 1) {
            error_log('Only one participant needed. Not displaying additional forms.');
            return;
        }
        
        // Display an explanatory message about the first participant
        echo '<div class="wep-participant-info">';
        echo '<p><strong>Informasi:</strong> Data peserta pertama akan menggunakan data pemesan di atas.</p>';
        echo '</div>';
        
        // Log that we're displaying participant forms
        error_log('Displaying forms for ' . ($jumlah_peserta_total - 1) . ' additional participants');
        
        // Display participant forms starting from participant #2
        // The first participant's info comes from the standard billing fields
        for ($i = 2; $i <= $jumlah_peserta_total; $i++) {
            echo "<h3>Data Peserta {$i}</h3>";
            
            // First name (required)
            woocommerce_form_field("peserta_{$i}_nama_depan", [
                'type' => 'text',
                'class' => ['form-row-first'],
                'label' => 'Nama Depan',
                'required' => true,
            ], $checkout->get_value("peserta_{$i}_nama_depan"));
            
            // Last name (required)
            woocommerce_form_field("peserta_{$i}_nama_belakang", [
                'type' => 'text',
                'class' => ['form-row-last'],
                'label' => 'Nama Belakang',
                'required' => true,
            ], $checkout->get_value("peserta_{$i}_nama_belakang"));
            
            // Occupation (optional)
            woocommerce_form_field("peserta_{$i}_pekerjaan", [
                'type' => 'text',
                'class' => ['form-row-wide'],
                'label' => 'Pekerjaan',
            ], $checkout->get_value("peserta_{$i}_pekerjaan"));
            
            // City (optional)
            woocommerce_form_field("peserta_{$i}_kota", [
                'type' => 'text',
                'class' => ['form-row-wide'],
                'label' => 'Kota',
            ], $checkout->get_value("peserta_{$i}_kota"));
            
            // Phone number (required)
            woocommerce_form_field("peserta_{$i}_telepon", [
                'type' => 'tel',
                'class' => ['form-row-wide'],
                'label' => 'Nomor Telepon',
                'required' => true,
            ], $checkout->get_value("peserta_{$i}_telepon"));
            
            // Email (required)
            woocommerce_form_field("peserta_{$i}_email", [
                'type' => 'email',
                'class' => ['form-row-wide'],
                'label' => 'Email',
                'required' => true,
            ], $checkout->get_value("peserta_{$i}_email"));
        }
    }
    
    private function calculate_total_participants() {
        $total_participants = 0;
        $detection_method = isset($this->settings['detection_method']) ? $this->settings['detection_method'] : 'auto';
        $pattern = isset($this->settings['variation_pattern']) ? $this->settings['variation_pattern'] : '/\b(\d+)\s*Orang\b/i';
        
        // Log the detection method and pattern
        error_log('Detection method: ' . $detection_method);
        error_log('Variation pattern: ' . $pattern);
        
        // Log the cart contents
        error_log('Cart contents: ' . print_r(WC()->cart->get_cart(), true));
        
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $product_id = $cart_item['product_id'];
            $variation_id = isset($cart_item['variation_id']) ? $cart_item['variation_id'] : 0;
            $quantity = $cart_item['quantity'];
            
            // For manual mode, check if this product/variation has a manual setting
            if ($detection_method === 'manual' && !empty($this->settings['product_variations'])) {
                if ($variation_id && isset($this->settings['product_variations'][$variation_id])) {
                    // If variation is configured
                    $total_participants += intval($this->settings['product_variations'][$variation_id]) * $quantity;
                    continue;
                } elseif (isset($this->settings['product_variations'][$product_id])) {
                    // If product is configured
                    $total_participants += intval($this->settings['product_variations'][$product_id]) * $quantity;
                    continue;
                }
            }
            
            // Automatic detection for variations
            if ($product->is_type('variation')) {
                $variation_name = $product->get_name();
                if (preg_match($pattern, $variation_name, $match)) {
                    $participant_count = (int) $match[1];
                    $total_participants += $participant_count * $quantity;
                } else {
                    // If no match but it's still a variation, assume 1 participant
                    $total_participants += 1 * $quantity;
                }
            }
        }
        
        return $total_participants;
    }
}
?>