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
        'license_key' => '',
    ];

    /** @var array|null */
    private $cache;

    /**
     * Returns merged settings with defaults applied.
     */
    public function get_settings() {
        if (null === $this->cache) {
            $stored   = get_option(self::OPTION_KEY, []);
            $settings = wp_parse_args(is_array($stored) ? $stored : [], $this->defaults);
            $settings['license_key'] = sanitize_text_field($settings['license_key']);

            $this->cache = $settings;
        }

        return $this->cache;
    }

    /**
     * Returns the stored license key.
     */
    public function get_license_key() {
        $settings = $this->get_settings();
        return $settings['license_key'];
    }

    /**
     * Sanitizes settings before saving via WP Settings API.
     */
    public function sanitize($input) {
        $input = is_array($input) ? $input : [];

        $sanitized = [
            'license_key' => sanitize_text_field($input['license_key'] ?? ''),
        ];

        $this->cache = $sanitized;

        // If the license key is cleared, drop stored license status so Advanced stays hidden.
        if ($sanitized['license_key'] === '') {
            delete_option('gd_audit_license_status');
        }

        return $sanitized;
    }
}
