/**
 * AJAX handler for license validation.
 */
function gd_audit_validate_license_ajax() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'gd-audit')]);
    }

    $license_key = isset($_POST['license_key']) ? sanitize_text_field($_POST['license_key']) : '';
    if (empty($license_key)) {
        wp_send_json_error(['message' => __('License key is required.', 'gd-audit')]);
    }

    // TODO: Replace with real validation logic, e.g., remote API call
    $is_valid = preg_match('/^[A-Z0-9\-]{10,}$/', $license_key);
    if ($is_valid) {
        wp_send_json_success(['message' => __('License is valid!', 'gd-audit')]);
    } else {
        wp_send_json_error(['message' => __('Invalid license key.', 'gd-audit')]);
    }
}
add_action('wp_ajax_gd_audit_validate_license', 'gd_audit_validate_license_ajax');
<?php
/**
 * Plugin Name: GD Audit
 * Description: Lightweight audit tool for WordPress that displays key installation details into a dashboard.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: GD Team
 * License: GPLv2 or later
 * Text Domain: gd-audit
 */

if (!defined('ABSPATH')) {
    exit;
}

define('GD_AUDIT_VERSION', '1.0.0');
define('GD_AUDIT_PLUGIN_FILE', __FILE__);
define('GD_AUDIT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GD_AUDIT_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once GD_AUDIT_PLUGIN_DIR . 'includes/class-gd-audit-settings.php';
require_once GD_AUDIT_PLUGIN_DIR . 'includes/class-gd-audit-analytics.php';
require_once GD_AUDIT_PLUGIN_DIR . 'includes/class-gd-audit-plugin-inspector.php';
require_once GD_AUDIT_PLUGIN_DIR . 'includes/class-gd-audit-theme-inspector.php';
require_once GD_AUDIT_PLUGIN_DIR . 'includes/class-gd-audit-database-inspector.php';
require_once GD_AUDIT_PLUGIN_DIR . 'includes/class-gd-audit-admin-page.php';

/**
 * Bootstraps the plugin components once all plugins are loaded.
 */
function gd_audit_bootstrap() {
    static $container = null;

    if (null === $container) {
        $container                = new stdClass();
        $container->settings      = new GDAuditSettings();
        $container->analytics     = new GDAuditAnalytics();
        $container->plugins       = new GDAuditPluginInspector();
        $container->themes        = new GDAuditThemeInspector();
        $container->database      = new GDAuditDatabaseInspector();
        $container->admin_page    = new GDAuditAdminPage(
            $container->settings,
            $container->analytics,
            $container->plugins,
            $container->themes,
            $container->database
        );
    }

    return $container;
}
add_action('plugins_loaded', 'gd_audit_bootstrap');


/**
 * Removes the legacy audit log table since logging is deprecated.
 */
function gd_audit_remove_legacy_logs_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'gd_audit_logs';
    $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
}
add_action('plugins_loaded', 'gd_audit_remove_legacy_logs_table', 5);

/**
 * Adds a shortcut to the settings screen from the plugins list.
 */
function gd_audit_plugin_action_links($links) {
    $settings_url = admin_url('admin.php?page=gd-audit-settings');
    $links[]      = '<a href="' . esc_url($settings_url) . '">' . esc_html__('Settings', 'gd-audit') . '</a>';

    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'gd_audit_plugin_action_links');
