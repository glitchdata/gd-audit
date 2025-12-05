<?php
/**
 * Admin UI for surfacing audit events.
 */

if (!defined('ABSPATH')) {
    exit;
}

class GDAuditAdminPage {
    /** @var GDAuditLogger */
    private $logger;
    /** @var GDAuditSettings */
    private $settings;

    public function __construct(GDAuditLogger $logger, GDAuditSettings $settings) {
        $this->logger   = $logger;
        $this->settings = $settings;

        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_filter('set_screen_option_gd_audit_per_page', [$this, 'set_screen_option'], 10, 3);
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Registers the top-level GD Audit menu and subpages.
     */
    public function register_menu() {
        $hook = add_menu_page(
            __('GD Audit', 'gd-audit'),
            __('GD Audit', 'gd-audit'),
            'manage_options',
            'gd-audit',
            [$this, 'render_page'],
            'dashicons-visibility',
            65
        );

        add_action("load-$hook", [$this, 'add_screen_options']);
        add_action('load-gd-audit_page_gd-audit', [$this, 'add_screen_options']);

        add_submenu_page(
            'gd-audit',
            __('Settings', 'gd-audit'),
            __('Settings', 'gd-audit'),
            'manage_options',
            'gd-audit-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Adds pagination options to the screen.
     */
    public function add_screen_options() {
        add_screen_option(
            'per_page',
            [
                'label'   => __('Logs per page', 'gd-audit'),
                'default' => 25,
                'option'  => 'gd_audit_per_page',
            ]
        );
    }

    /**
     * Stores custom per-page value.
     */
    public function set_screen_option($status, $option, $value) {
        if ('gd_audit_per_page' === $option) {
            return (int) $value;
        }

        return $status;
    }

    /**
     * Loads CSS for the admin UI.
     */
    public function enqueue_assets($hook) {
        $allowed_hooks = ['toplevel_page_gd-audit', 'gd-audit_page_gd-audit', 'gd-audit_page_gd-audit-settings'];

        if (!in_array($hook, $allowed_hooks, true)) {
            return;
        }

        wp_enqueue_style(
            'gd-audit-admin',
            GD_AUDIT_PLUGIN_URL . 'assets/css/admin.css',
            [],
            GD_AUDIT_VERSION
        );
    }

    /**
     * Registers the settings and sanitization callbacks.
     */
    public function register_settings() {
        register_setting(
            'gd_audit_settings_group',
            GDAuditSettings::OPTION_KEY,
            [$this->settings, 'sanitize']
        );
    }

    /**
     * Renders the audit page with filters and table.
     */
    public function render_page() {
        $per_page = $this->get_per_page();
        $paged    = max(1, (int) ($_GET['paged'] ?? 1));

        $filters = [
            'event_type' => sanitize_text_field($_GET['event_type'] ?? ''),
            'search'     => sanitize_text_field($_GET['s'] ?? ''),
            'date_from'  => sanitize_text_field($_GET['date_from'] ?? ''),
            'date_to'    => sanitize_text_field($_GET['date_to'] ?? ''),
            'limit'      => $per_page,
            'offset'     => ($paged - 1) * $per_page,
        ];

        $logs       = $this->logger->get_logs($filters);
        $total      = $this->logger->count_logs($filters);
        $eventTypes = $this->logger->get_event_types();
        $total_pages= max(1, ceil($total / $per_page));

        include GD_AUDIT_PLUGIN_DIR . 'includes/views/admin-page.php';
    }

    /**
     * Outputs the settings form.
     */
    public function render_settings_page() {
        $settings       = $this->settings->get_settings();
        $events         = $this->settings->get_available_events();
        $option_key     = GDAuditSettings::OPTION_KEY;

        include GD_AUDIT_PLUGIN_DIR . 'includes/views/settings-page.php';
    }

    /**
     * Returns the per page preference using WP screen API.
     */
    private function get_per_page() {
        $screen   = get_current_screen();
        $per_page = $screen ? (int) $screen->get_option('per_page', 'option') : 25;
        $value    = (int) get_user_meta(get_current_user_id(), 'gd_audit_per_page', true);

        if ($value) {
            $per_page = $value;
        }

        return max(5, $per_page);
    }
}
