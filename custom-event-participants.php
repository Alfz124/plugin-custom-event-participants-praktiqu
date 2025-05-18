<?php
/**
 * Plugin Name: Woo Event Participants
 * Description: Tambahkan form data peserta dinamis di checkout berdasarkan variasi produk.
 * Version: 1.0
 * Author: Ahmad Luthfi Fahrizi
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Tambahkan form di checkout
add_action('woocommerce_before_order_notes', 'wep_tambah_form_data_peserta');

function wep_tambah_form_data_peserta($checkout) {
    $jumlah_peserta_total = 0;

    foreach (WC()->cart->get_cart() as $cart_item) {
        $product = $cart_item['data'];

        if ($product->is_type('variation')) {
            $variation_name = $product->get_name();
            if (preg_match('/\b(\d+)\s*Orang\b/i', $variation_name, $match)) {
                $jumlah_peserta = (int) $match[1];
                $jumlah_peserta_total += $jumlah_peserta * $cart_item['quantity'];
            } else {
                $jumlah_peserta_total += 1 * $cart_item['quantity'];
            }
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

// Simpan data ke order meta
add_action('woocommerce_checkout_update_order_meta', 'wep_simpan_data_peserta');
function wep_simpan_data_peserta($order_id) {
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'peserta_') === 0) {
            update_post_meta($order_id, $key, sanitize_text_field($value));
        }
    }
}

// Tampilkan data peserta di admin
add_action('woocommerce_admin_order_data_after_billing_address', 'wep_tampilkan_data_peserta_admin', 10, 1);
function wep_tampilkan_data_peserta_admin($order) {
    echo '<h3>Data Peserta</h3>';
    foreach ($order->get_meta_data() as $meta) {
        if (strpos($meta->key, 'peserta_') === 0) {
            echo '<p><strong>' . esc_html($meta->key) . ':</strong> ' . esc_html($meta->value) . '</p>';
        }
    }
}
