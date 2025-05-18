<?php
class WEP_Checkout_Fields {
    private $settings;

    public function __construct() {
        $this->settings = get_option('wep_settings', []);
        add_action('woocommerce_before_order_notes', [$this, 'display_participant_forms']);
    }

    public function display_participant_forms($checkout) {
        // Check if plugin is enabled
        if (isset($this->settings['enable_plugin']) && $this->settings['enable_plugin'] == 0) {
            return;
        }
        
        $jumlah_peserta_total = $this->calculate_total_participants();
        
        // If no participants needed, exit
        if ($jumlah_peserta_total < 1) {
            return;
        }
        
        // Display participant forms
        for ($i = 1; $i <= $jumlah_peserta_total; $i++) {
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