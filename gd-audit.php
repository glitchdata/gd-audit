<?php
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

    // Validate license via remote API
    $api_url = 'https://license.glitchdata.com/license/validate/' . urlencode($license_key);
    $response = wp_remote_get($api_url, [
        'timeout' => 15
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => __('Could not connect to license server.', 'gd-audit')]);
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    if (is_array($data)) {
        if (!empty($data['valid'])) {
            update_option('gd_audit_license_status', [
                'valid'      => true,
                'license_key'=> $license_key,
                'checked_at' => time(),
            ]);
            wp_send_json_success(['message' => __('License is valid!', 'gd-audit')]);
        }

        update_option('gd_audit_license_status', [
            'valid'      => false,
            'license_key'=> $license_key,
            'checked_at' => time(),
        ]);
        wp_send_json_error(['message' => __('Invalid license key.', 'gd-audit')]);
    } else {
        // Show raw response for debugging and mark invalid
        update_option('gd_audit_license_status', [
            'valid'      => false,
            'license_key'=> $license_key,
            'checked_at' => time(),
        ]);
        wp_send_json_error(['message' => __('Unexpected response: ', 'gd-audit') . $body]);
    }
}
add_action('wp_ajax_gd_audit_validate_license', 'gd_audit_validate_license_ajax');
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
 * Exports audit data as JSON when license is valid.
 */
function gd_audit_export_audit_data() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Permission denied.', 'gd-audit'), 403);
    }

    check_admin_referer('gd_audit_export');

    $container   = gd_audit_bootstrap();
    $settings    = $container->settings->get_settings();
    $saved_key   = isset($settings['license_key']) ? (string) $settings['license_key'] : '';
    $license_row = get_option('gd_audit_license_status', []);

    $license_valid = $saved_key !== ''
        && !empty($license_row['valid'])
        && !empty($license_row['license_key'])
        && $license_row['license_key'] === $saved_key;

    if (!$license_valid) {
        wp_die(__('License is not valid. Please validate before exporting.', 'gd-audit'), 403);
    }

    $analytics = $container->analytics;
    $plugins   = $container->plugins->get_plugins();
    $themes    = $container->themes->get_themes();
    $tables    = $container->database->get_tables();

    $data = [
        'generated_at' => gmdate('c'),
        'site'         => $analytics->get_site_configuration_overview(),
        'posts'        => [
            'status_totals'  => $analytics->get_post_status_totals(),
            'daily_activity'  => $analytics->get_daily_post_activity(14),
            'top_authors'     => $analytics->get_top_authors(30, 5),
            'recent'          => $analytics->get_recent_published_posts(10),
        ],
        'users'        => [
            'roles'           => $analytics->get_user_role_distribution(),
            'recent'          => $analytics->get_recent_users(10),
            'registrations'   => $analytics->get_user_registration_trend(14),
        ],
        'links'        => $analytics->get_link_analytics(['sample_size' => 75]),
        'media'        => [
            'overview'        => $analytics->get_image_overview(),
            'recent'          => $analytics->get_recent_images(10),
        ],
        'plugins'      => $plugins,
        'themes'       => $themes,
        'database'     => [
            'tables'  => $tables,
            'summary' => $container->database->get_summary($tables),
        ],
        'license'      => [
            'valid'      => !empty($license_row['valid']),
            'checked_at' => isset($license_row['checked_at']) ? (int) $license_row['checked_at'] : null,
        ],
    ];

    $filename = 'gd-audit-export-' . gmdate('Ymd-His') . '.json';
    nocache_headers();
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    echo wp_json_encode($data, JSON_PRETTY_PRINT);
    exit;
}
add_action('admin_post_gd_audit_export', 'gd_audit_export_audit_data');


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
