<?php
// filepath: /woo-event-participants-advanced/woo-event-participants-advanced/templates/admin-settings.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$products = get_posts(['post_type' => 'product', 'numberposts' => -1]);
$settings = get_option('wep_participant_settings', []);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_admin_referer('wep_save_settings');

    $settings = [];
    foreach ($products as $product) {
        $settings[$product->ID] = [
            'number_of_participants' => isset($_POST["number_of_participants_{$product->ID}"]) ? intval($_POST["number_of_participants_{$product->ID}"]) : 0,
        ];
    }
    update_option('wep_participant_settings', $settings);
    echo '<div class="updated"><p>Settings saved.</p></div>';
}
?>

<div class="wrap">
    <h1>Woo Event Participants Settings</h1>
    <form method="post" action="">
        <?php wp_nonce_field('wep_save_settings'); ?>
        <table class="form-table">
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <th scope="row"><?php echo esc_html($product->post_title); ?></th>
                        <td>
                            <input type="number" name="number_of_participants_<?php echo esc_attr($product->ID); ?>" value="<?php echo esc_attr($settings[$product->ID]['number_of_participants'] ?? 0); ?>" min="0" />
                            <p class="description">Define the number of participant forms for this product.</p>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php submit_button('Save Settings'); ?>
    </form>
</div>