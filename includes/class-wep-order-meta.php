<?php
class WEP_Order_Meta {
    public function __construct() {
        add_action('woocommerce_checkout_update_order_meta', [$this, 'save_participant_data']);
        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'display_participant_data_admin'], 10, 1);
    }

    public function save_participant_data($order_id) {
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'peserta_') === 0) {
                update_post_meta($order_id, $key, sanitize_text_field($value));
            }
        }
    }

    public function display_participant_data_admin($order) {
        echo '<h3>Data Peserta</h3>';
        foreach ($order->get_meta_data() as $meta) {
            if (strpos($meta->key, 'peserta_') === 0) {
                echo '<p><strong>' . esc_html($meta->key) . ':</strong> ' . esc_html($meta->value) . '</p>';
            }
        }
    }
}
?>