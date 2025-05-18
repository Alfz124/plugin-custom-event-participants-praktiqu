<?php
class WEP_Settings {
    private $settings;
    private $log_entries = [];

    public function __construct() {
        $this->settings = get_option('wep_settings', []);
        
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        
        // Register our custom logger for plugin use
        add_action('init', [$this, 'init_custom_logger']);
    }
    
    public function init_custom_logger() {
        // Define a global function to log messages
        if (!function_exists('wep_log')) {
            function wep_log($message, $type = 'info') {
                global $wep_settings_instance;
                if (isset($wep_settings_instance)) {
                    $wep_settings_instance->add_log_entry($message, $type);
                }
                // Also log to WordPress debug log as backup
                error_log('WEP: ' . $message);
            }
        }
        
        // Store instance globally for logging access
        global $wep_settings_instance;
        $wep_settings_instance = $this;
        
        // Load existing logs
        $this->load_logs();
    }
    
    public function add_log_entry($message, $type = 'info') {
        // Add timestamp to log entry
        $entry = [
            'time' => current_time('mysql'),
            'message' => $message,
            'type' => $type
        ];
        
        // Add to current logs
        $this->log_entries[] = $entry;
        
        // Save logs to option (limited to last 100 entries)
        $this->save_logs();
    }
    
    private function load_logs() {
        $logs = get_option('wep_debug_logs', []);
        $this->log_entries = is_array($logs) ? $logs : [];
    }
    
    private function save_logs() {
        // Keep only the last 100 log entries to avoid database bloat
        if (count($this->log_entries) > 100) {
            $this->log_entries = array_slice($this->log_entries, -100);
        }
        
        update_option('wep_debug_logs', $this->log_entries);
    }
    
    public function clear_logs() {
        $this->log_entries = [];
        update_option('wep_debug_logs', []);
    }

    public function add_settings_page() {
        add_submenu_page(
            'woocommerce',
            'Event Participants Settings',
            'Event Participants',
            'manage_options',
            'wep-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        register_setting('wep_settings_group', 'wep_settings');
    }

    public function render_settings_page() {
        // Check if we should clear logs
        if (isset($_POST['wep_clear_logs']) && check_admin_referer('wep_clear_logs_nonce')) {
            $this->clear_logs();
            echo '<div class="notice notice-success is-dismissible"><p>Debug logs cleared successfully!</p></div>';
        }
        
        // Check if we should run a test save
        if (isset($_POST['wep_run_test']) && check_admin_referer('wep_test_nonce')) {
            $this->run_participant_data_test();
            echo '<div class="notice notice-info is-dismissible"><p>Test completed. Check logs for results.</p></div>';
        }
        
        include(WEP_PLUGIN_DIR . 'templates/admin-settings.php');
    }
    
    public function run_participant_data_test() {
        wep_log('Running participant data storage test', 'test');
        
        // Create a fake order ID using a timestamp
        $test_id = 'test_' . time();
        
        // Log test parameters
        wep_log('Test ID: ' . $test_id, 'test');
        
        // Create sample participant data
        $test_data = [
            'peserta_1_nama_depan' => 'Test',
            'peserta_1_nama_belakang' => 'User',
            'peserta_1_telepon' => '123456789',
            'peserta_1_email' => 'test@example.com',
            'peserta_1_kota' => 'Test City',
            'peserta_1_pekerjaan' => 'Tester',
            'peserta_2_nama_depan' => 'Test2',
            'peserta_2_nama_belakang' => 'User2',
            'peserta_2_telepon' => '987654321',
            'peserta_2_email' => 'test2@example.com',
            'peserta_2_kota' => 'Test City 2',
            'peserta_2_pekerjaan' => 'Tester 2'
        ];
        
        // Log the test data
        wep_log('Test data: ' . print_r($test_data, true), 'test');
        
        // Try WordPress transients to store test data
        $transient_key = 'wep_test_data_' . $test_id;
        $result = set_transient($transient_key, $test_data, HOUR_IN_SECONDS);
        wep_log('Transient save result: ' . ($result ? 'Success' : 'Failed'), 'test');
        
        // Try retrieving data
        $retrieved_data = get_transient($transient_key);
        wep_log('Retrieved data: ' . print_r($retrieved_data, true), 'test');
        
        // Check for database issues
        global $wpdb;
        wep_log('Database prefix: ' . $wpdb->prefix, 'test');
        
        // Test direct database access
        $results = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}options'");
        wep_log('Can access options table: ' . (!empty($results) ? 'Yes' : 'No'), 'test');
        
        // Test permission to insert
        $test_insert = $wpdb->insert(
            $wpdb->options,
            [
                'option_name' => 'wep_test_option_' . time(),
                'option_value' => 'test_value',
                'autoload' => 'no'
            ],
            ['%s', '%s', '%s']
        );
        wep_log('Can insert into options table: ' . ($test_insert ? 'Yes' : 'No'), 'test');
        if (!$test_insert) {
            wep_log('Database error: ' . $wpdb->last_error, 'error');
        }
        
        wep_log('Test completed', 'test');
    }
}
?>