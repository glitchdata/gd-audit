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
    $api_url = GD_AUDIT_LICENSE_VALIDATE_ENDPOINT . urlencode($license_key);
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
define('GD_AUDIT_LICENSE_VALIDATE_ENDPOINT', 'https://license.glitchdata.com/api/license/validate/');
define('GD_AUDIT_LOG_RECEIVER_ENDPOINT', 'https://logs.glitchdata.com/api/logs');
define('GD_AUDIT_LOG_RECEIVER_TOKEN', 'your-secret');
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
 * Exports audit data as a PDF when license is valid.
 */
function gd_audit_export_audit_pdf() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Permission denied.', 'gd-audit'), 403);
    }

    check_admin_referer('gd_audit_export_pdf');

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

    require_once GD_AUDIT_PLUGIN_DIR . 'includes/lib/fpdf.php';

    $analytics = $container->analytics;
    $plugins   = $container->plugins->get_plugins();
    $themes    = $container->themes->get_themes();
    $tables    = $container->database->get_tables();
    $db_summary = $container->database->get_summary($tables);
    $site_conf = $analytics->get_site_configuration_overview();

    // Clear any buffered output to avoid header issues
    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
    }

    try {
        $pdf = new FPDF();
        $pdf->SetTitle('GD Audit Report');
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'GD Audit Report', 0, 1, 'C');
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(0, 8, 'Generated: ' . gmdate('Y-m-d H:i:s') . ' UTC', 0, 1, 'C');
        $pdf->Ln(4);

        // Site configuration summary
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Site Configuration', 0, 1);
        $pdf->SetFont('Helvetica', '', 10);
        foreach ($site_conf['summary'] as $card) {
            $line = $card['label'] . ': ' . $card['value'];
            if (!empty($card['meta'])) {
                $line .= ' (' . $card['meta'] . ')';
            }
            $pdf->Cell(0, 6, $line, 0, 1);
        }
        $pdf->Ln(4);

        // Post stats
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Posts', 0, 1);
        $pdf->SetFont('Helvetica', '', 10);
        foreach ($analytics->get_post_status_totals() as $row) {
            $pdf->Cell(0, 6, $row['label'] . ': ' . $row['count'], 0, 1);
        }
        $pdf->Ln(4);

        // Users
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Users', 0, 1);
        $pdf->SetFont('Helvetica', '', 10);
        $role_data = $analytics->get_user_role_distribution();
        $pdf->Cell(0, 6, 'Total users: ' . $role_data['total'], 0, 1);
        foreach ($role_data['roles'] as $role) {
            $pdf->Cell(0, 6, $role['label'] . ': ' . $role['count'], 0, 1);
        }
        $pdf->Ln(4);

        // Plugins
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Plugins', 0, 1);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(0, 6, 'Total: ' . count($plugins), 0, 1);
        $active = count(array_filter($plugins, fn($p) => !empty($p['active'])));
        $updates = count(array_filter($plugins, fn($p) => !empty($p['has_update'])));
        $pdf->Cell(0, 6, 'Active: ' . $active . ' | Updates: ' . $updates, 0, 1);
        $pdf->Ln(4);

        // Themes
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Themes', 0, 1);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(0, 6, 'Total: ' . count($themes), 0, 1);
        $active_theme = current(array_filter($themes, fn($t) => !empty($t['is_active'])));
        if ($active_theme) {
            $pdf->Cell(0, 6, 'Active: ' . $active_theme['name'] . ' ' . $active_theme['version'], 0, 1);
        }
        $theme_updates = count(array_filter($themes, fn($t) => !empty($t['has_update'])));
        $pdf->Cell(0, 6, 'Updates: ' . $theme_updates, 0, 1);
        $pdf->Ln(4);

        // Database summary
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Database', 0, 1);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(0, 6, 'Tables: ' . $db_summary['total_tables'], 0, 1);
        $pdf->Cell(0, 6, 'WP Tables: ' . $db_summary['wp_tables'], 0, 1);
        $pdf->Cell(0, 6, 'Rows: ' . $db_summary['total_rows'], 0, 1);
        $pdf->Ln(4);

        // Links overview
        $link_overview = $analytics->get_link_analytics(['sample_size' => 75]);
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Links', 0, 1);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(0, 6, 'Scanned posts: ' . $link_overview['overview']['scanned'], 0, 1);
        $pdf->Cell(0, 6, 'Total links: ' . $link_overview['overview']['total'], 0, 1);
        $pdf->Cell(0, 6, 'Internal: ' . $link_overview['overview']['internal'] . ' | External: ' . $link_overview['overview']['external'], 0, 1);
        $pdf->Ln(4);

        // Media overview
        $media = $analytics->get_image_overview();
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Media (Images)', 0, 1);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(0, 6, 'Total images: ' . $media['total'], 0, 1);
        $pdf->Cell(0, 6, 'Total size (bytes): ' . $media['total_size'], 0, 1);
        $pdf->Ln(4);

        // License status
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'License', 0, 1);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(0, 6, 'Valid: yes', 0, 1);

        $filename = 'gd-audit-report-' . gmdate('Ymd-His') . '.pdf';
        nocache_headers();
        $pdf->Output('D', $filename);
    } catch (Exception $e) {
        wp_die('PDF generation failed: ' . esc_html($e->getMessage()), 500);
    }
    exit;
}
add_action('admin_post_gd_audit_export_pdf', 'gd_audit_export_audit_pdf');

/**
 * Returns the configured log receiver token.
 */
function gd_audit_get_log_receiver_token() {
    $env_token = getenv('LOG_RECEIVER_TOKEN');
    if (!empty($env_token)) {
        return $env_token;
    }

    return defined('GD_AUDIT_LOG_RECEIVER_TOKEN') ? GD_AUDIT_LOG_RECEIVER_TOKEN : '';
}

/**
 * Stores the latest log submission status for diagnostics.
 */
function gd_audit_record_log_status($is_success, $message, array $context = []) {
    update_option('gd_audit_log_status', [
        'status'     => $is_success ? 'success' : 'error',
        'message'    => $message,
        'context'    => $context,
        'updated_at' => time(),
    ]);
}

/**
 * Builds the audit payload data; returns WP_Error on failure.
 */
function gd_audit_prepare_audit_payload($user_id = 0, $source = 'gd-audit', $require_license = true) {
    $container   = gd_audit_bootstrap();
    $settings    = $container->settings->get_settings();
    $saved_key   = isset($settings['license_key']) ? (string) $settings['license_key'] : '';
    $license_row = get_option('gd_audit_license_status', []);

    $license_valid = $saved_key !== ''
        && !empty($license_row['valid'])
        && !empty($license_row['license_key'])
        && $license_row['license_key'] === $saved_key;

    if ($require_license && !$license_valid) {
        return new WP_Error('gd_audit_license_invalid', __('License is not valid. Please validate before submitting.', 'gd-audit'));
    }

    $analytics = $container->analytics;
    $plugins   = $container->plugins->get_plugins();
    $themes    = $container->themes->get_themes();
    $tables    = $container->database->get_tables();

    $context = [
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

    return [
        'type'        => 'external_event',
        'user_id'     => $user_id,
        'source'      => $source,
        'occurred_at' => gmdate('c'),
        'context'     => $context,
    ];
}

/**
 * Sends the prepared payload to the log receiver; returns WP_Error on failure.
 */
function gd_audit_send_payload_to_receiver($payload, $token) {
    $response = wp_remote_post(GD_AUDIT_LOG_RECEIVER_ENDPOINT, [
        'headers' => [
            'Content-Type' => 'application/json',
            'X-Log-Token'  => $token,
        ],
        'body'    => wp_json_encode($payload),
        'timeout' => 20,
    ]);

    if (is_wp_error($response)) {
        return $response;
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code < 200 || $code >= 300) {
        $body = wp_remote_retrieve_body($response);
        return new WP_Error('gd_audit_log_error', sprintf(
            __('Log receiver returned an error (%1$s): %2$s', 'gd-audit'),
            (int) $code,
            $body
        ));
    }

    return true;
}

/**
 * Submits audit data to the external log receiver as JSON (manual trigger).
 */
function gd_audit_send_audit_log() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Permission denied.', 'gd-audit'), 403);
    }

    check_admin_referer('gd_audit_send_log');

    $token = gd_audit_get_log_receiver_token();
    if (empty($token)) {
        wp_die(__('Log receiver token is not configured.', 'gd-audit'), 400);
    }

    $scheduled = false;
    if (isset($_POST['schedule_daily'])) {
        if (!wp_next_scheduled('gd_audit_cron_send_audit_log')) {
            wp_schedule_event(time() + MINUTE_IN_SECONDS, 'daily', 'gd_audit_cron_send_audit_log');
        }
        $scheduled = true;
    }

    $payload = gd_audit_prepare_audit_payload(get_current_user_id(), 'gd-audit', true);
    if (is_wp_error($payload)) {
        wp_die(esc_html($payload->get_error_message()), 403);
    }

    $result = gd_audit_send_payload_to_receiver($payload, $token);
    $redirect = wp_get_referer() ?: admin_url('admin.php?page=gd-audit-advanced');

    if (is_wp_error($result)) {
        $error_message = rawurlencode($result->get_error_message());
        gd_audit_record_log_status(false, $result->get_error_message(), [
            'mode'      => isset($_POST['schedule_daily']) ? 'schedule+manual' : 'manual',
            'error'     => $result->get_error_message(),
        ]);
        wp_safe_redirect(add_query_arg([
            'gd_audit_log_error'     => $error_message,
            'gd_audit_log_scheduled' => $scheduled ? '1' : '0',
        ], $redirect));
        exit;
    }

    gd_audit_record_log_status(true, __('Audit log submitted.', 'gd-audit'), [
        'mode' => isset($_POST['schedule_daily']) ? 'schedule+manual' : 'manual',
    ]);

    wp_safe_redirect(add_query_arg([
        'gd_audit_log_submitted' => '1',
        'gd_audit_log_scheduled' => $scheduled ? '1' : '0',
    ], $redirect));
    exit;
}
add_action('admin_post_gd_audit_send_log', 'gd_audit_send_audit_log');

/**
 * Cron handler to submit audit data daily.
 */
function gd_audit_cron_send_audit_log() {
    $token = gd_audit_get_log_receiver_token();
    if (empty($token)) {
        gd_audit_record_log_status(false, __('Log receiver token is not configured for cron.', 'gd-audit'), [
            'mode' => 'cron',
        ]);
        return;
    }

    $payload = gd_audit_prepare_audit_payload(0, 'gd-audit-cron', true);
    if (is_wp_error($payload)) {
        gd_audit_record_log_status(false, $payload->get_error_message(), [
            'mode'  => 'cron',
            'error' => $payload->get_error_message(),
        ]);
        return;
    }

    $result = gd_audit_send_payload_to_receiver($payload, $token);
    if (is_wp_error($result)) {
        gd_audit_record_log_status(false, $result->get_error_message(), [
            'mode'  => 'cron',
            'error' => $result->get_error_message(),
        ]);
        return;
    }

    gd_audit_record_log_status(true, __('Audit log submitted via cron.', 'gd-audit'), [
        'mode' => 'cron',
    ]);
}
add_action('gd_audit_cron_send_audit_log', 'gd_audit_cron_send_audit_log');



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
