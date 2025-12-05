<?php
/**
 * Surfaces metadata about installed plugins for the GD Audit dashboard.
 */

if (!defined('ABSPATH')) {
    exit;
}

class GDAuditPluginInspector {
    /**
     * Returns a normalized list of installed plugins.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_plugins() {
        $this->maybe_include_wp_plugins();

        $plugins = function_exists('get_plugins') ? get_plugins() : [];
        $list    = [];

        foreach ($plugins as $file => $plugin_data) {
            $list[] = [
                'file'        => $file,
                'name'        => $plugin_data['Name'] ?? $file,
                'version'     => $plugin_data['Version'] ?? '',
                'author'      => wp_strip_all_tags($plugin_data['Author'] ?? ''),
                'description' => wp_strip_all_tags($plugin_data['Description'] ?? ''),
                'active'      => $this->is_active($file),
                'has_update'  => $this->has_update($file),
                'plugin_url'  => $plugin_data['PluginURI'] ?? '',
            ];
        }

        usort($list, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return $list;
    }

    /**
     * Ensures the WordPress plugin functions are loaded.
     */
    private function maybe_include_wp_plugins() {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
    }

    /**
     * Determines whether the plugin file is currently active.
     */
    private function is_active($plugin_file) {
        $this->maybe_include_wp_plugins();
        return function_exists('is_plugin_active') && is_plugin_active($plugin_file);
    }

    /**
     * Checks for pending updates for the plugin file.
     */
    private function has_update($plugin_file) {
        $updates = get_site_transient('update_plugins');

        if (!is_object($updates) || empty($updates->response)) {
            return false;
        }

        return isset($updates->response[$plugin_file]);
    }
}
