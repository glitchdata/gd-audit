<?php
/**
 * Admin UI for surfacing audit events.
 */

if (!defined('ABSPATH')) {
    exit;
}

class GDAuditAdminPage {
    /** @var GDAuditSettings */
    private $settings;
    /** @var GDAuditAnalytics */
    private $analytics;
    /** @var GDAuditPluginInspector */
    private $plugin_inspector;
    /** @var GDAuditThemeInspector */
    private $theme_inspector;
    /** @var GDAuditDatabaseInspector */
    private $database_inspector;

    public function __construct(
        GDAuditSettings $settings,
        GDAuditAnalytics $analytics,
        GDAuditPluginInspector $plugin_inspector,
        GDAuditThemeInspector $theme_inspector,
        GDAuditDatabaseInspector $database_inspector
    ) {
        $this->settings         = $settings;
        $this->analytics        = $analytics;
        $this->plugin_inspector = $plugin_inspector;
        $this->theme_inspector  = $theme_inspector;
        $this->database_inspector = $database_inspector;

        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
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
            __('Dashboard', 'gd-audit'),
            __('Dashboard', 'gd-audit'),
            'manage_options',
            'gd-audit',
            [$this, 'render_dashboard_page']
        );

        add_submenu_page(
            'gd-audit',
            __('Settings', 'gd-audit'),
            __('Settings', 'gd-audit'),
            'manage_options',
            'gd-audit-settings',
            [$this, 'render_settings_page']
        );

        add_submenu_page(
            'gd-audit',
            __('Config', 'gd-audit'),
            __('Config', 'gd-audit'),
            'manage_options',
            'gd-audit-config',
            [$this, 'render_config_page']
        );

        add_submenu_page(
            'gd-audit',
            __('DB', 'gd-audit'),
            __('DB', 'gd-audit'),
            'manage_options',
            'gd-audit-database',
            [$this, 'render_database_page']
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
            __('Posts', 'gd-audit'),
            __('Posts', 'gd-audit'),
            'manage_options',
            'gd-audit-posts',
            [$this, 'render_posts_page']
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
            __('Images', 'gd-audit'),
            __('Images', 'gd-audit'),
            'manage_options',
            'gd-audit-images',
            [$this, 'render_images_page']
        );

        add_submenu_page(
            'gd-audit',
            __('Links', 'gd-audit'),
            __('Links', 'gd-audit'),
            'manage_options',
            'gd-audit-links',
            [$this, 'render_links_page']
        );
    }

    /**
     * Loads CSS for the admin UI.
     */
    public function enqueue_assets($hook) {
        $allowed_hooks = [
            'toplevel_page_gd-audit',
            'gd-audit_page_gd-audit',
            'gd-audit_page_gd-audit-plugins',
            'gd-audit_page_gd-audit-themes',
            'gd-audit_page_gd-audit-links',
            'gd-audit_page_gd-audit-images',
            'gd-audit_page_gd-audit-users',
            'gd-audit_page_gd-audit-posts',
            'gd-audit_page_gd-audit-database',
            'gd-audit_page_gd-audit-config',
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
     * Renders the dashboard analytics page.
     */
    public function render_dashboard_page() {
        $nav_tabs = $this->get_nav_tabs('dashboard');

        $settings_data       = $this->settings->get_settings();
        $enabled_events      = isset($settings_data['enabled_events']) ? (array) $settings_data['enabled_events'] : [];
        $enabled_event_count = count($enabled_events);
        $retention_days      = isset($settings_data['retention_days']) ? (int) $settings_data['retention_days'] : 0;
        $retention_label     = $retention_days > 0
            ? sprintf(_n('%d day', '%d days', $retention_days, 'gd-audit'), $retention_days)
            : __('No limit', 'gd-audit');

        $post_status_totals = $this->analytics->get_post_status_totals();
        $published_total    = 0;
        $draft_total        = 0;
        $pending_total      = 0;
        $all_posts_total    = 0;
        foreach ($post_status_totals as $status_row) {
            $count = (int) $status_row['count'];
            $all_posts_total += $count;
            switch ($status_row['status']) {
                case 'publish':
                    $published_total = $count;
                    break;
                case 'draft':
                    $draft_total = $count;
                    break;
                case 'pending':
                    $pending_total = $count;
                    break;
            }
        }

        $plugins           = $this->plugin_inspector->get_plugins();
        $plugin_total      = count($plugins);
        $plugin_active     = count(array_filter($plugins, fn($plugin) => !empty($plugin['active'])));
        $plugin_updates    = count(array_filter($plugins, fn($plugin) => !empty($plugin['has_update'])));

        $themes            = $this->theme_inspector->get_themes();
        $active_theme      = current(array_filter($themes, fn($theme) => !empty($theme['is_active'])));
        $theme_name        = $active_theme['name'] ?? __('Unknown theme', 'gd-audit');
        $theme_version     = $active_theme['version'] ?? '';
        $theme_updates     = count(array_filter($themes, fn($theme) => !empty($theme['has_update'])));

        $link_report       = $this->analytics->get_link_analytics([
            'sample_size' => 50,
        ]);
        $links_overview    = $link_report['overview'];

        $image_overview    = $this->analytics->get_image_overview();

        $user_roles        = $this->analytics->get_user_role_distribution();
        $total_users       = $user_roles['total'];
        $role_rows         = $user_roles['roles'];
        $admin_count       = 0;
        foreach ($role_rows as $role) {
            if ('administrator' === $role['role']) {
                $admin_count = $role['count'];
                break;
            }
        }

        $tables            = $this->database_inspector->get_tables();
        $database_summary  = $this->database_inspector->get_summary($tables);

        $config_overview   = $this->analytics->get_site_configuration_overview();
        $config_summary    = $config_overview['summary'];

        $dashboard_tiles   = [
            [
                'key'           => 'plugins',
                'label'         => __('Plugins', 'gd-audit'),
                'description'   => __('Track active code and pending updates.', 'gd-audit'),
                'primary_value' => number_format_i18n($plugin_active) . '/' . number_format_i18n($plugin_total),
                'primary_label' => __('Active plugins', 'gd-audit'),
                'items'         => [
                    sprintf(__('Updates pending: %s', 'gd-audit'), number_format_i18n($plugin_updates)),
                ],
                'url'           => admin_url('admin.php?page=gd-audit-plugins'),
            ],
            [
                'key'           => 'themes',
                'label'         => __('Themes', 'gd-audit'),
                'description'   => __('Review active, child, and parent themes.', 'gd-audit'),
                'primary_value' => $theme_name,
                'primary_label' => $theme_version ? sprintf(__('Version %s', 'gd-audit'), $theme_version) : __('Version N/A', 'gd-audit'),
                'items'         => [
                    sprintf(__('Theme updates: %s', 'gd-audit'), number_format_i18n($theme_updates)),
                    sprintf(__('Child theme: %s', 'gd-audit'), !empty($active_theme['is_child']) ? __('Yes', 'gd-audit') : __('No', 'gd-audit')),
                ],
                'url'           => admin_url('admin.php?page=gd-audit-themes'),
            ],
            [
                'key'           => 'links',
                'label'         => __('Links', 'gd-audit'),
                'description'   => __('Compare internal vs external link patterns.', 'gd-audit'),
                'primary_value' => number_format_i18n($links_overview['total'] ?? 0),
                'primary_label' => __('Links analyzed', 'gd-audit'),
                'items'         => [
                    sprintf(__('Internal: %s', 'gd-audit'), number_format_i18n($links_overview['internal'] ?? 0)),
                    sprintf(__('External: %s', 'gd-audit'), number_format_i18n($links_overview['external'] ?? 0)),
                ],
                'url'           => admin_url('admin.php?page=gd-audit-links'),
            ],
            [
                'key'           => 'images',
                'label'         => __('Images', 'gd-audit'),
                'description'   => __('Keep an eye on the media library.', 'gd-audit'),
                'primary_value' => number_format_i18n($image_overview['total']),
                'primary_label' => __('Images stored', 'gd-audit'),
                'items'         => [
                    sprintf(__('Library size: %s', 'gd-audit'), size_format($image_overview['total_size'], 2)),
                    sprintf(__('Average file: %s', 'gd-audit'), size_format($image_overview['avg_size'], 2)),
                ],
                'url'           => admin_url('admin.php?page=gd-audit-images'),
            ],
            [
                'key'           => 'users',
                'label'         => __('Users', 'gd-audit'),
                'description'   => __('Understand who can access the site.', 'gd-audit'),
                'primary_value' => number_format_i18n($total_users),
                'primary_label' => __('Registered users', 'gd-audit'),
                'items'         => [
                    sprintf(__('Admins: %s', 'gd-audit'), number_format_i18n($admin_count)),
                    sprintf(__('Roles tracked: %s', 'gd-audit'), number_format_i18n(count($role_rows))),
                ],
                'url'           => admin_url('admin.php?page=gd-audit-users'),
            ],
            [
                'key'           => 'posts',
                'label'         => __('Posts', 'gd-audit'),
                'description'   => __('Monitor publishing velocity and queues.', 'gd-audit'),
                'primary_value' => number_format_i18n($published_total),
                'primary_label' => __('Published', 'gd-audit'),
                'items'         => [
                    sprintf(__('Drafts: %s', 'gd-audit'), number_format_i18n($draft_total)),
                    sprintf(__('Pending: %s', 'gd-audit'), number_format_i18n($pending_total)),
                ],
                'url'           => admin_url('admin.php?page=gd-audit-posts'),
            ],
            [
                'key'           => 'database',
                'label'         => __('DB', 'gd-audit'),
                'description'   => __('Inspect table health and footprint.', 'gd-audit'),
                'primary_value' => number_format_i18n($database_summary['total_tables']),
                'primary_label' => __('Tables detected', 'gd-audit'),
                'items'         => [
                    sprintf(__('Total size: %s', 'gd-audit'), size_format($database_summary['data_size'] + $database_summary['index_size'], 2)),
                    sprintf(__('WP tables: %s', 'gd-audit'), number_format_i18n($database_summary['wp_tables'])),
                ],
                'url'           => admin_url('admin.php?page=gd-audit-database'),
            ],
            [
                'key'           => 'config',
                'label'         => __('Config', 'gd-audit'),
                'description'   => __('Snapshot of core runtime settings.', 'gd-audit'),
                'primary_value' => get_bloginfo('version'),
                'primary_label' => __('WordPress version', 'gd-audit'),
                'items'         => [
                    sprintf(__('PHP: %s', 'gd-audit'), PHP_VERSION),
                    sprintf(__('Theme: %s', 'gd-audit'), $theme_name),
                ],
                'url'           => admin_url('admin.php?page=gd-audit-config'),
            ],
            [
                'key'           => 'settings',
                'label'         => __('Settings', 'gd-audit'),
                'description'   => __('Tune what gets logged and retained.', 'gd-audit'),
                'primary_value' => number_format_i18n($enabled_event_count),
                'primary_label' => __('Events monitored', 'gd-audit'),
                'items'         => [
                    sprintf(__('Retention: %s', 'gd-audit'), $retention_label),
                    sprintf(__('IP masking: %s', 'gd-audit'), !empty($settings_data['mask_ip']) ? __('On', 'gd-audit') : __('Off', 'gd-audit')),
                ],
                'url'           => admin_url('admin.php?page=gd-audit-settings'),
            ],
        ];

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
     * Displays link analytics summaries.
     */
    public function render_links_page() {
        $requested_types = isset($_GET['post_types']) ? (array) $_GET['post_types'] : [];
        $requested_types = array_map('sanitize_key', $requested_types);
        $sample_size     = isset($_GET['sample_size']) ? (int) $_GET['sample_size'] : 75;

        $report      = $this->analytics->get_link_analytics([
            'post_types'  => $requested_types,
            'sample_size' => min(200, max(10, $sample_size)),
        ]);

        $overview         = $report['overview'];
        $top_posts        = $report['top_posts'];
        $top_domains      = $report['top_domains'];
        $trend            = $report['trend'];
        $trend_points     = $report['trend_points'];
        $active_types     = $report['post_types'];
        $active_type_labels = $report['post_type_labels'];
        $active_sample    = $report['sample_size'];
        $nav_tabs         = $this->get_nav_tabs('links');
        $filterable_types = $this->get_filterable_post_types();

        include GD_AUDIT_PLUGIN_DIR . 'includes/views/links-page.php';
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
     * Displays post activity analytics.
     */
    public function render_posts_page() {
        $status_totals  = $this->analytics->get_post_status_totals();
        $daily_activity = $this->analytics->get_daily_post_activity();
        $top_authors    = $this->analytics->get_top_authors();
        $recent_posts   = $this->analytics->get_recent_published_posts();
        $nav_tabs       = $this->get_nav_tabs('posts');

        include GD_AUDIT_PLUGIN_DIR . 'includes/views/posts-page.php';
    }

    /**
     * Displays media library analytics.
     */
    public function render_images_page() {
        $overview      = $this->analytics->get_image_overview();
        $recent_images = $this->analytics->get_recent_images();
        $nav_tabs      = $this->get_nav_tabs('images');

        include GD_AUDIT_PLUGIN_DIR . 'includes/views/images-page.php';
    }

    /**
     * Displays database tables and stats.
     */
    public function render_database_page() {
        $tables   = $this->database_inspector->get_tables();
        $summary  = $this->database_inspector->get_summary($tables);
        $nav_tabs = $this->get_nav_tabs('database');

        include GD_AUDIT_PLUGIN_DIR . 'includes/views/database-page.php';
    }

    /**
     * Displays key WordPress configuration details.
     */
    public function render_config_page() {
        $config_overview = $this->analytics->get_site_configuration_overview();
        $config_summary  = $config_overview['summary'];
        $config_sections = $config_overview['sections'];
        $nav_tabs        = $this->get_nav_tabs('config');

        include GD_AUDIT_PLUGIN_DIR . 'includes/views/config-page.php';
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
            'settings' => [
                'label' => __('Settings', 'gd-audit'),
                'url'   => admin_url('admin.php?page=gd-audit-settings'),
            ],
            'config' => [
                'label' => __('Config', 'gd-audit'),
                'url'   => admin_url('admin.php?page=gd-audit-config'),
            ],
            'database' => [
                'label' => __('DB', 'gd-audit'),
                'url'   => admin_url('admin.php?page=gd-audit-database'),
            ],
            'users' => [
                'label' => __('Users', 'gd-audit'),
                'url'   => admin_url('admin.php?page=gd-audit-users'),
            ],
            'posts' => [
                'label' => __('Posts', 'gd-audit'),
                'url'   => admin_url('admin.php?page=gd-audit-posts'),
            ],
            'plugins' => [
                'label' => __('Plugins', 'gd-audit'),
                'url'   => admin_url('admin.php?page=gd-audit-plugins'),
            ],
            'themes' => [
                'label' => __('Themes', 'gd-audit'),
                'url'   => admin_url('admin.php?page=gd-audit-themes'),
            ],
            'images' => [
                'label' => __('Images', 'gd-audit'),
                'url'   => admin_url('admin.php?page=gd-audit-images'),
            ],
            'links' => [
                'label' => __('Links', 'gd-audit'),
                'url'   => admin_url('admin.php?page=gd-audit-links'),
            ],
        ];

        foreach ($tabs as $key => &$tab) {
            $tab['active'] = ($key === $current);
            $tab['key']    = $key;
        }

        return $tabs;
    }

    /**
     * Returns post types the Links tab can filter by.
     */
    private function get_filterable_post_types() {
        return get_post_types([
            'public'  => true,
            'show_ui' => true,
        ], 'objects');
    }
}
