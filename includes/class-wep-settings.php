<?php
class WEP_Settings {
    private $options;

    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
        $this->options = get_option('wep_settings', []);
    }

    public function register_settings() {
        register_setting('wep_options_group', 'wep_settings', [$this, 'sanitize_settings']);
    }

    public function sanitize_settings($input) {
        $sanitized = [];
        
        // General settings
        $sanitized['enable_plugin'] = isset($input['enable_plugin']) ? 1 : 0;
        $sanitized['detection_method'] = isset($input['detection_method']) ? sanitize_text_field($input['detection_method']) : 'auto';
        
        // Pattern settings
        if (isset($input['variation_pattern'])) {
            $sanitized['variation_pattern'] = sanitize_text_field($input['variation_pattern']);
        } else {
            $sanitized['variation_pattern'] = '/\b(\d+)\s*Orang\b/i'; // Default pattern
        }
        
        // Manual product variation mappings
        if (isset($input['product_variations']) && is_array($input['product_variations'])) {
            $sanitized['product_variations'] = [];
            foreach ($input['product_variations'] as $id => $count) {
                $sanitized['product_variations'][$id] = intval($count);
            }
        }
        
        return $sanitized;
    }
    
    public function get_setting($key, $default = false) {
        return isset($this->options[$key]) ? $this->options[$key] : $default;
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
                        <th scope="row">Enable Plugin</th>
                        <td>
                            <input type="checkbox" name="wep_settings[enable_plugin]" value="1" <?php checked($this->get_setting('enable_plugin', 0), 1); ?> />
                            <p class="description">Enable or disable the plugin functionality.</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Detection Method</th>
                        <td>
                            <input type="text" name="wep_settings[detection_method]" value="<?php echo esc_attr($this->get_setting('detection_method', 'auto')); ?>" />
                            <p class="description">Specify the detection method (e.g., auto, manual).</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Variation Pattern</th>
                        <td>
                            <input type="text" name="wep_settings[variation_pattern]" value="<?php echo esc_attr($this->get_setting('variation_pattern', '/\b(\d+)\s*Orang\b/i')); ?>" />
                            <p class="description">Enter the regex pattern for detecting variations.</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Product Variations</th>
                        <td>
                            <textarea name="wep_settings[product_variations]" rows="5" cols="50"><?php echo esc_textarea(json_encode($this->get_setting('product_variations', []))); ?></textarea>
                            <p class="description">Enter product IDs and the number of participant forms in JSON format (e.g., {"123":2,"456":3}).</p>
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