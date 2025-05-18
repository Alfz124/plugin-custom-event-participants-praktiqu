<?php
class WEP_Settings {
    private $options;

    public function __construct() {
        $this->options = get_option('wep_settings');
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_settings() {
        register_setting('wep_options_group', 'wep_settings', [$this, 'validate_settings']);
    }

    public function validate_settings($input) {
        // Validate and sanitize input
        $validated = [];
        if (isset($input['product_variations']) && is_array($input['product_variations'])) {
            foreach ($input['product_variations'] as $key => $value) {
                $validated[$key] = sanitize_text_field($value);
            }
        }
        return $validated;
    }

    public function get_options() {
        return $this->options;
    }

    public function display_settings_page() {
        ?>
        <div class="wrap">
            <h1>Woo Event Participants Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wep_options_group');
                do_settings_sections('wep_options_group');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Product Variations</th>
                        <td>
                            <input type="text" name="wep_settings[product_variations]" value="<?php echo esc_attr($this->options['product_variations'] ?? ''); ?>" />
                            <p class="description">Enter product IDs and the number of participant forms (e.g., 123:2, 456:3).</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
?>