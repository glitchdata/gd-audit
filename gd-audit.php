<?php
/**
 * Plugin Name: GD Audit
 * Description: Lightweight audit trail for WordPress that records key user actions and surfaces them in a searchable dashboard.
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
require_once GD_AUDIT_PLUGIN_DIR . 'includes/class-gd-audit-logger.php';
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
        $container->logger        = new GDAuditLogger($container->settings);
        $container->admin_page    = new GDAuditAdminPage(
            $container->logger,
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

register_activation_hook(__FILE__, ['GDAuditLogger', 'create_table']);

/**
 * Adds a shortcut to the settings screen from the plugins list.
 */
function gd_audit_plugin_action_links($links) {
    $settings_url = admin_url('admin.php?page=gd-audit-settings');
    $links[]      = '<a href="' . esc_url($settings_url) . '">' . esc_html__('Settings', 'gd-audit') . '</a>';

    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'gd_audit_plugin_action_links');
