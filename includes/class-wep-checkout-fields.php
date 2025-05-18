<?php
class WEP_Checkout_Fields {
    private $product_participant_limits;

    public function __construct() {
        $this->product_participant_limits = get_option('wep_product_participant_limits', []);
        add_action('woocommerce_before_order_notes', [$this, 'display_participant_forms']);
    }

    public function display_participant_forms($checkout) {
        $jumlah_peserta_total = 0;

        foreach (WC()->cart->get_cart() as $cart_item) {
            $product_id = $cart_item['product_id'];
            $quantity = $cart_item['quantity'];

            if (isset($this->product_participant_limits[$product_id])) {
                $jumlah_peserta = intval($this->product_participant_limits[$product_id]);
                $jumlah_peserta_total += $jumlah_peserta * $quantity;
            }
        }

        for ($i = 1; $i <= $jumlah_peserta_total; $i++) {
            echo "<h3>Data Peserta {$i}</h3>";

            woocommerce_form_field("peserta_{$i}_nama_depan", [
                'type' => 'text',
                'class' => ['form-row-first'],
                'label' => 'Nama Depan',
                'required' => true,
            ], $checkout->get_value("peserta_{$i}_nama_depan"));

            woocommerce_form_field("peserta_{$i}_nama_belakang", [
                'type' => 'text',
                'class' => ['form-row-last'],
                'label' => 'Nama Belakang',
                'required' => true,
            ], $checkout->get_value("peserta_{$i}_nama_belakang"));

            woocommerce_form_field("peserta_{$i}_pekerjaan", [
                'type' => 'text',
                'class' => ['form-row-wide'],
                'label' => 'Pekerjaan',
            ], $checkout->get_value("peserta_{$i}_pekerjaan"));

            woocommerce_form_field("peserta_{$i}_kota", [
                'type' => 'text',
                'class' => ['form-row-wide'],
                'label' => 'Kota',
            ], $checkout->get_value("peserta_{$i}_kota"));

            woocommerce_form_field("peserta_{$i}_telepon", [
                'type' => 'tel',
                'class' => ['form-row-wide'],
                'label' => 'Nomor Telepon',
                'required' => true,
            ], $checkout->get_value("peserta_{$i}_telepon"));

            woocommerce_form_field("peserta_{$i}_email", [
                'type' => 'email',
                'class' => ['form-row-wide'],
                'label' => 'Email',
                'required' => true,
            ], $checkout->get_value("peserta_{$i}_email"));
        }
    }
}
?>