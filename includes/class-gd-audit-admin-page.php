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
    /** @var GDAuditAnalytics */
    private $analytics;
    /** @var GDAuditPluginInspector */
    private $plugin_inspector;
    /** @var GDAuditThemeInspector */
    private $theme_inspector;

    public function __construct(
        GDAuditLogger $logger,
        GDAuditSettings $settings,
        GDAuditAnalytics $analytics,
        GDAuditPluginInspector $plugin_inspector,
        GDAuditThemeInspector $theme_inspector
    ) {
        $this->logger           = $logger;
        $this->settings         = $settings;
        $this->analytics        = $analytics;
        $this->plugin_inspector = $plugin_inspector;
        $this->theme_inspector  = $theme_inspector;

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
            [$this, 'render_dashboard_page'],
            'dashicons-visibility',
            65
        );

        add_submenu_page(
            'gd-audit',
            __('Logs', 'gd-audit'),
            __('Logs', 'gd-audit'),
            'manage_options',
            'gd-audit-logs',
            [$this, 'render_logs_page']
        );

        add_submenu_page(
            'gd-audit',
            __('Plugins', 'gd-audit'),
            __('Plugins', 'gd-audit'),
            'manage_options',
            'gd-audit-plugins',
            [$this, 'render_plugins_page']
        );

        add_submenu_page(
            'gd-audit',
            __('Themes', 'gd-audit'),
            __('Themes', 'gd-audit'),
            'manage_options',
            'gd-audit-themes',
            [$this, 'render_themes_page']
        );

        add_submenu_page(
            'gd-audit',
            __('Users', 'gd-audit'),
            __('Users', 'gd-audit'),
            'manage_options',
            'gd-audit-users',
            [$this, 'render_users_page']
        );

        add_submenu_page(
            'gd-audit',
            __('Settings', 'gd-audit'),
            __('Settings', 'gd-audit'),
            'manage_options',
            'gd-audit-settings',
            [$this, 'render_settings_page']
        );

        add_action('load-gd-audit_page_gd-audit-logs', [$this, 'add_screen_options']);
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
        $allowed_hooks = [
            'toplevel_page_gd-audit',
            'gd-audit_page_gd-audit',
            'gd-audit_page_gd-audit-logs',
            'gd-audit_page_gd-audit-plugins',
            'gd-audit_page_gd-audit-themes',
            'gd-audit_page_gd-audit-users',
            'gd-audit_page_gd-audit-settings',
        ];

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
     * Renders the audit logs page with filters and table.
     */
    public function render_logs_page() {
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

        $logs        = $this->logger->get_logs($filters);
        $total       = $this->logger->count_logs($filters);
        $eventTypes  = $this->logger->get_event_types();
        $total_pages = max(1, ceil($total / $per_page));
        $nav_tabs = $this->get_nav_tabs('logs');

        include GD_AUDIT_PLUGIN_DIR . 'includes/views/admin-page.php';
    }

    /**
     * Renders the dashboard analytics page.
     */
    public function render_dashboard_page() {
        $status_totals = $this->analytics->get_post_status_totals();
        $daily_activity= $this->analytics->get_daily_post_activity();
        $recent_posts  = $this->analytics->get_recent_published_posts();
        $top_authors   = $this->analytics->get_top_authors();

        $nav_tabs = $this->get_nav_tabs('dashboard');

        include GD_AUDIT_PLUGIN_DIR . 'includes/views/dashboard-page.php';
    }

    /**
     * Displays the installed plugins overview.
     */
    public function render_plugins_page() {
        $plugins        = $this->plugin_inspector->get_plugins();
        $active_count   = count(array_filter($plugins, fn($plugin) => !empty($plugin['active'])));
        $inactive_count = max(0, count($plugins) - $active_count);
        $update_count   = count(array_filter($plugins, fn($plugin) => !empty($plugin['has_update'])));
        $nav_tabs       = $this->get_nav_tabs('plugins');

        include GD_AUDIT_PLUGIN_DIR . 'includes/views/plugins-page.php';
    }

    /**
     * Displays the installed themes overview.
     */
    public function render_themes_page() {
        $themes         = $this->theme_inspector->get_themes();
        $active_theme   = current(array_filter($themes, fn($theme) => !empty($theme['is_active'])));
        $child_count    = count(array_filter($themes, fn($theme) => !empty($theme['is_child'])));
        $update_count   = count(array_filter($themes, fn($theme) => !empty($theme['has_update'])));
        $nav_tabs       = $this->get_nav_tabs('themes');

        include GD_AUDIT_PLUGIN_DIR . 'includes/views/themes-page.php';
    }

    /**
     * Displays user analytics.
     */
    public function render_users_page() {
        $roles_data     = $this->analytics->get_user_role_distribution();
        $registration_trend = $this->analytics->get_user_registration_trend();
        $recent_users   = $this->analytics->get_recent_users();
        $total_users    = $roles_data['total'];
        $roles          = $roles_data['roles'];

        $admin_count = 0;
        foreach ($roles as $role) {
            if ('administrator' === $role['role']) {
                $admin_count = $role['count'];
                break;
            }
        }

        $recent_registrations = array_sum(wp_list_pluck($registration_trend, 'total'));
        $nav_tabs = $this->get_nav_tabs('users');

        include GD_AUDIT_PLUGIN_DIR . 'includes/views/users-page.php';
    }

    /**
     * Outputs the settings form.
     */
    public function render_settings_page() {
        $settings    = $this->settings->get_settings();
        $events      = $this->settings->get_available_events();
        $option_key  = GDAuditSettings::OPTION_KEY;
        $nav_tabs = $this->get_nav_tabs('settings');

        include GD_AUDIT_PLUGIN_DIR . 'includes/views/settings-page.php';
    }

    /**
     * Builds nav tab configuration for all plugin pages.
     */
    private function get_nav_tabs($current) {
        $tabs = [
            'dashboard' => [
                'label' => __('Dashboard', 'gd-audit'),
                'url'   => admin_url('admin.php?page=gd-audit'),
            ],
            'logs' => [
                'label' => __('Logs', 'gd-audit'),
                'url'   => admin_url('admin.php?page=gd-audit-logs'),
            ],
            'plugins' => [
                'label' => __('Plugins', 'gd-audit'),
                'url'   => admin_url('admin.php?page=gd-audit-plugins'),
            ],
            'themes' => [
                'label' => __('Themes', 'gd-audit'),
                'url'   => admin_url('admin.php?page=gd-audit-themes'),
            ],
            'users' => [
                'label' => __('Users', 'gd-audit'),
                'url'   => admin_url('admin.php?page=gd-audit-users'),
            ],
            'settings' => [
                'label' => __('Settings', 'gd-audit'),
                'url'   => admin_url('admin.php?page=gd-audit-settings'),
            ],
        ];

        foreach ($tabs as $key => &$tab) {
            $tab['active'] = ($key === $current);
            $tab['key']    = $key;
        }

        return $tabs;
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
