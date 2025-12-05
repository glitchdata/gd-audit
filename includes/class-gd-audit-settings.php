<?php
/**
 * Provides storage and helpers for GD Audit settings.
 */

if (!defined('ABSPATH')) {
    exit;
}

class GDAuditSettings {
    const OPTION_KEY = 'gd_audit_settings';

    /** @var array */
    private $defaults = [
        'enabled_events' => ['post_status', 'profile_update', 'user_register', 'user_delete', 'user_login'],
        'retention_days' => 0,
        'mask_ip'        => 0,
    ];

    /** @var array|null */
    private $cache;

    /**
     * Returns merged settings with defaults applied.
     */
    public function get_settings() {
        if (null === $this->cache) {
            $stored = get_option(self::OPTION_KEY, []);
            $settings = wp_parse_args(is_array($stored) ? $stored : [], $this->defaults);
            $settings['enabled_events'] = $this->sanitize_events($settings['enabled_events']);
            $settings['retention_days'] = absint($settings['retention_days']);
            $settings['mask_ip']        = (int) (bool) $settings['mask_ip'];

            $this->cache = $settings;
        }

        return $this->cache;
    }

    /**
     * Returns a map of available events => labels.
     */
    public function get_available_events() {
        return [
            'post_status'    => __('Post status changes', 'gd-audit'),
            'profile_update' => __('Profile updates', 'gd-audit'),
            'user_register'  => __('New user registrations', 'gd-audit'),
            'user_delete'    => __('User deletions', 'gd-audit'),
            'user_login'     => __('Successful logins', 'gd-audit'),
        ];
    }

    /**
     * Checks whether an event type should be recorded.
     */
    public function is_event_enabled($event_type) {
        $settings = $this->get_settings();
        return in_array($event_type, $settings['enabled_events'], true);
    }

    /**
     * Whether IP masking is enabled.
     */
    public function should_mask_ip() {
        $settings = $this->get_settings();
        return !empty($settings['mask_ip']);
    }

    /**
     * Returns retention window in days.
     */
    public function get_retention_days() {
        $settings = $this->get_settings();
        return absint($settings['retention_days']);
    }

    /**
     * Sanitizes settings before saving via WP Settings API.
     */
    public function sanitize($input) {
        $input = is_array($input) ? $input : [];

        $sanitized = [
            'enabled_events' => $this->sanitize_events($input['enabled_events'] ?? []),
            'retention_days' => absint($input['retention_days'] ?? 0),
            'mask_ip'        => empty($input['mask_ip']) ? 0 : 1,
        ];

        $this->cache = $sanitized;

        return $sanitized;
    }

    /**
     * Helper to restrict the enabled events list.
     */
    private function sanitize_events($events) {
        $events = is_array($events) ? array_map('sanitize_key', $events) : [];
        $allowed = array_keys($this->get_available_events());

        return array_values(array_intersect($events, $allowed));
    }
}
