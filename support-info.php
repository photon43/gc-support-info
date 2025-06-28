<?php
    /*
    Plugin Name: GC Support Info
    Description: Enhanced dashboard widget with server information, AJAX refresh, and JSON export capabilities for support teams.
    Author: Dan Simeone
    Author URI: https://gestaltcreations.com/
    Version: 1.5.3
    Text Domain: gc-support-info
    */
?>
<?php
    // Security
    defined( 'ABSPATH' ) || exit;
?>
<?php
    // Check for Updates
    require 'plugin-update-checker/plugin-update-checker.php';
    use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

    $myUpdateChecker = PucFactory::buildUpdateChecker(
        'https://github.com/photon43/gc-support-info/',
        __FILE__, //Full path to the main plugin file or functions.php.
        'gc-support-info'
    );
    
    // Enable GitHub release assets for downloading
    $myUpdateChecker->getVcsApi()->enableReleaseAssets();
?>
<?php
// Retrieve Server Information
function display_server_ip_dashboard_widget() {
    echo '<div id="gc-support-info-widget" style="font-size: 13px;">';
    
    echo '<div id="gc-support-info-content">';
    gc_display_support_info_content();
    echo '</div>';
    
    // Add action buttons at the bottom
    echo '<div style="margin-top: 15px; text-align: left;">';
    echo '<button type="button" id="gc-refresh-info" style="background-color: #0073aa; color: white; border: none; padding: 8px 16px; margin-right: 10px; border-radius: 4px; cursor: pointer; font-size: 13px;">Refresh</button>';
    echo '<button type="button" id="gc-export-info" style="background-color: #0073aa; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 13px;">Export JSON</button>';
    echo '</div>';
    
    echo '<p style="margin: 8px 0 0 0; font-size: 11px; color: #666;">Last refreshed: ' . current_time('M j, Y g:i A') . '</p>';
    
    echo '</div>';
}


// Helper function to get database size
function get_database_size() {
    global $wpdb;
    
    $result = $wpdb->get_row("
        SELECT 
            ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
        FROM information_schema.tables 
        WHERE table_schema = '" . DB_NAME . "'
    ");
    
    if ($result && $result->size_mb) {
        return $result->size_mb . ' MB';
    }
    return 'Unknown';
}


// Helper function to get server load
function get_server_load() {
    if (function_exists('sys_getloadavg')) {
        $load = sys_getloadavg();
        if ($load !== false) {
            return sprintf('%.2f, %.2f, %.2f', $load[0], $load[1], $load[2]);
        }
    }
    
    // Alternative method for some systems
    if (is_readable('/proc/loadavg')) {
        $load = file_get_contents('/proc/loadavg');
        if ($load) {
            $load_parts = explode(' ', $load);
            return sprintf('%.2f, %.2f, %.2f', $load_parts[0], $load_parts[1], $load_parts[2]);
        }
    }
    
    return false;
}
// Hook into the 'wp_dashboard_setup' action to add the widget
function add_server_ip_dashboard_widget() {
    if (current_user_can('manage_options')) {
        wp_add_dashboard_widget(
            'server_ip_dashboard_widget', // Widget slug
            'GC Support Info', // Title of the widget
            'display_server_ip_dashboard_widget' // Callback function
        );
    }
}
add_action('wp_dashboard_setup', 'add_server_ip_dashboard_widget');

// AJAX handlers
add_action('wp_ajax_gc_refresh_support_info', 'gc_ajax_refresh_support_info');
add_action('wp_ajax_gc_export_support_info', 'gc_ajax_export_support_info');

// AJAX refresh handler
function gc_ajax_refresh_support_info() {
    // Check nonce and permissions
    if (!wp_verify_nonce($_POST['nonce'], 'gc_support_info_nonce') || !current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
        return;
    }
    
    ob_start();
    gc_display_support_info_content();
    $content = ob_get_clean();
    
    wp_send_json_success($content);
}

// AJAX export handler
function gc_ajax_export_support_info() {
    // Check nonce and permissions
    if (!wp_verify_nonce($_POST['nonce'], 'gc_support_info_nonce') || !current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
        return;
    }
    
    $data = gc_get_support_info_data();
    
    wp_send_json_success($data);
}

// Function to get support info as structured data
function gc_get_support_info_data() {
    global $wpdb;
    
    // Basic server info
    $server_hostname = gethostname();
    $server_ip = $_SERVER['SERVER_ADDR'];
    $baseurl = $_SERVER['SERVER_NAME'];
    $php_version = phpversion();
    
    // Database information
    $db_version = $wpdb->db_version();
    $db_size = get_database_size();
    $table_count = count($wpdb->get_results("SHOW TABLES"));
    
    // WordPress information
    $wp_version = get_bloginfo('version');
    $current_theme = wp_get_theme();
    $parent_theme = $current_theme->parent();
    $plugin_count = count(get_plugins());
    $is_multisite = is_multisite();
    
    // Server load
    $server_load = get_server_load();
    
    return array(
        'generated_at' => current_time('c'),
        'site_url' => get_site_url(),
        'server' => array(
            'hostname' => $server_hostname,
            'ip_address' => $server_ip,
            'base_url' => $baseurl,
            'php_version' => $php_version,
            'server_load' => $server_load
        ),
        'database' => array(
            'version' => $db_version,
            'size' => $db_size,
            'table_count' => $table_count
        ),
        'wordpress' => array(
            'version' => $wp_version,
            'active_theme' => array(
                'name' => $current_theme->get('Name'),
                'version' => $current_theme->get('Version')
            ),
            'parent_theme' => $parent_theme ? array(
                'name' => $parent_theme->get('Name'),
                'version' => $parent_theme->get('Version')
            ) : null,
            'plugin_count' => $plugin_count,
            'is_multisite' => $is_multisite
        )
    );
}

// Separate function for the actual content display (for AJAX calls)
function gc_display_support_info_content() {
    global $wpdb;
    
    // Basic server info
    $server_hostname = gethostname();
    $server_ip = $_SERVER['SERVER_ADDR'];
    $baseurl = $_SERVER['SERVER_NAME'];
    $php_version = phpversion();
    
    // Database information
    $db_version = $wpdb->db_version();
    $db_size = get_database_size();
    $table_count = count($wpdb->get_results("SHOW TABLES"));
    
    // WordPress information
    $wp_version = get_bloginfo('version');
    $current_theme = wp_get_theme();
    $parent_theme = $current_theme->parent();
    $plugin_count = count(get_plugins());
    $is_multisite = is_multisite() ? 'Yes' : 'No';
    
    // Server load
    $server_load = get_server_load();
    
    // Server Section
    echo '<h4 style="margin-bottom: 8px; color: #333; font-size: 16px; font-weight: bold;">Server Information</h4>';
    echo '<p style="margin: 0 0 12px 0;">';
    echo '<strong>Host Name:</strong> ' . esc_html($server_hostname) . '<br />';
    echo '<strong>IP Address:</strong> ' . esc_html($server_ip) . '<br />';
    echo '<strong>Base URL:</strong> ' . esc_html($baseurl) . '<br />';
    echo '<strong>PHP Version:</strong> ' . esc_html($php_version);
    if ($server_load) {
        echo '<br /><strong>Server Load:</strong> ' . esc_html($server_load);
    }
    echo '</p>';
    
    // Database Section
    echo '<h4 style="margin-bottom: 8px; color: #333; font-size: 16px; font-weight: bold;">Database Information</h4>';
    echo '<p style="margin: 0 0 12px 0;">';
    echo '<strong>Database Version:</strong> ' . esc_html($db_version) . '<br />';
    echo '<strong>Database Size:</strong> ' . esc_html($db_size) . '<br />';
    echo '<strong>Table Count:</strong> ' . esc_html($table_count);
    echo '</p>';
    
    // WordPress Section
    echo '<h4 style="margin-bottom: 8px; color: #333; font-size: 16px; font-weight: bold;">WordPress Information</h4>';
    echo '<p style="margin: 0;">';
    echo '<strong>WordPress Version:</strong> ' . esc_html($wp_version) . '<br />';
    echo '<strong>Active Theme:</strong> ' . esc_html($current_theme->get('Name')) . ' (v' . esc_html($current_theme->get('Version')) . ')';
    if ($parent_theme) {
        echo '<br /><strong>Parent Theme:</strong> ' . esc_html($parent_theme->get('Name')) . ' (v' . esc_html($parent_theme->get('Version')) . ')';
    }
    echo '<br />';
    echo '<strong>Total Plugins:</strong> ' . esc_html($plugin_count) . '<br />';
    echo '<strong>Multisite:</strong> ' . esc_html($is_multisite);
    echo '</p>';
    
}

// Enqueue JavaScript for AJAX functionality
function gc_support_info_enqueue_scripts($hook) {
    if ($hook != 'index.php') {
        return;
    }
    
    wp_enqueue_script('gc-support-info-js', plugin_dir_url(__FILE__) . 'gc-support-info.js', array('jquery'), '1.5.3', true);
    wp_localize_script('gc-support-info-js', 'gc_support_info_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gc_support_info_nonce')
    ));
}
add_action('admin_enqueue_scripts', 'gc_support_info_enqueue_scripts');
?>