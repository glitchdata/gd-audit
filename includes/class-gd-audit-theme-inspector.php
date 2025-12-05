<?php
/**
 * Collects information about installed WordPress themes.
 */

if (!defined('ABSPATH')) {
    exit;
}

class GDAuditThemeInspector {
    /**
     * Returns a normalized list of themes keyed by stylesheet slug.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_themes() {
        $themes        = wp_get_themes();
        $active_theme  = wp_get_theme();
        $updates       = get_site_transient('update_themes');
        $update_slugs  = is_object($updates) && !empty($updates->response) ? array_keys($updates->response) : [];
        $list          = [];

        foreach ($themes as $stylesheet => $theme) {
            $parent   = $theme->parent();
            $list[] = [
                'slug'        => $stylesheet,
                'name'        => $theme->get('Name'),
                'version'     => $theme->get('Version'),
                'author'      => wp_strip_all_tags($theme->get('Author')), 
                'description' => wp_strip_all_tags($theme->get('Description')),
                'is_active'   => $active_theme->get_stylesheet() === $stylesheet,
                'is_child'    => (bool) $parent,
                'parent_name' => $parent ? $parent->get('Name') : '',
                'screenshot'  => $theme->get_screenshot(),
                'has_update'  => in_array($stylesheet, $update_slugs, true),
                'theme_url'   => $theme->get('ThemeURI'),
            ];
        }

        usort($list, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return $list;
    }
}
