<?php
/**
 * Core logging utilities for capturing and retrieving audit events.
 */

if (!defined('ABSPATH')) {
    exit;
}

class GDAuditLogger {
    /** @var string */
    private $table_name;
    /** @var GDAuditSettings */
    private $settings;

    public function __construct(GDAuditSettings $settings) {
        global $wpdb;

        $this->table_name = $wpdb->prefix . 'gd_audit_logs';
        $this->settings   = $settings;

        add_action('transition_post_status', [$this, 'capture_post_status'], 10, 3);
        add_action('profile_update', [$this, 'capture_profile_update'], 10, 2);
        add_action('user_register', [$this, 'capture_user_register']);
        add_action('delete_user', [$this, 'capture_user_delete']);
        add_action('wp_login', [$this, 'capture_login'], 10, 2);
    }

    /**
     * Creates the audit table on plugin activation.
     */
    public static function create_table() {
        global $wpdb;

        $table_name      = $wpdb->prefix . 'gd_audit_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            object_type varchar(50) NOT NULL,
            object_id bigint(20) unsigned DEFAULT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            message text NOT NULL,
            context longtext NULL,
            ip_address varchar(100) DEFAULT '',
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY event_type (event_type),
            KEY object_type (object_type),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Drops the audit table, typically run during uninstall.
     */
    public static function drop_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'gd_audit_logs';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }

    /**
     * Returns a list of distinct event types for filtering.
     */
    public function get_event_types() {
        global $wpdb;

        $query = "SELECT DISTINCT event_type FROM {$this->table_name} ORDER BY event_type";
        return $wpdb->get_col($query);
    }

    /**
     * Retrieves logs honoring the provided filters.
     */
    public function get_logs($args = []) {
        global $wpdb;

        $defaults = [
            'limit'     => 25,
            'offset'    => 0,
            'event_type'=> '',
            'search'    => '',
            'date_from' => '',
            'date_to'   => '',
        ];
        $args = wp_parse_args($args, $defaults);

        $where  = 'WHERE 1=1';
        $params = [];

        if ($args['event_type']) {
            $where   .= ' AND event_type = %s';
            $params[] = $args['event_type'];
        }

        if ($args['search']) {
            $where   .= ' AND (message LIKE %s OR context LIKE %s)';
            $like     = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        if ($args['date_from']) {
            $where   .= ' AND created_at >= %s';
            $params[] = $args['date_from'] . ' 00:00:00';
        }

        if ($args['date_to']) {
            $where   .= ' AND created_at <= %s';
            $params[] = $args['date_to'] . ' 23:59:59';
        }

        $params[] = (int) $args['limit'];
        $params[] = (int) $args['offset'];

        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $params
        );

        return $wpdb->get_results($sql);
    }

    /**
     * Counts logs using the same filtering logic as get_logs.
     */
    public function count_logs($args = []) {
        global $wpdb;

        $defaults = [
            'event_type'=> '',
            'search'    => '',
            'date_from' => '',
            'date_to'   => '',
        ];
        $args = wp_parse_args($args, $defaults);

        $where  = 'WHERE 1=1';
        $params = [];

        if ($args['event_type']) {
            $where   .= ' AND event_type = %s';
            $params[] = $args['event_type'];
        }

        if ($args['search']) {
            $where   .= ' AND (message LIKE %s OR context LIKE %s)';
            $like     = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        if ($args['date_from']) {
            $where   .= ' AND created_at >= %s';
            $params[] = $args['date_from'] . ' 00:00:00';
        }

        if ($args['date_to']) {
            $where   .= ' AND created_at <= %s';
            $params[] = $args['date_to'] . ' 23:59:59';
        }

        $sql = "SELECT COUNT(*) FROM {$this->table_name} {$where}";
        if ($params) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return (int) $wpdb->get_var($sql);
    }

    /**
     * Captures transitions when posts change state.
     */
    public function capture_post_status($new_status, $old_status, $post) {
        if ($new_status === $old_status || 'revision' === $post->post_type || wp_is_post_revision($post)) {
            return;
        }

        $message = sprintf(
            'Post "%s" changed from %s to %s.',
            wp_strip_all_tags($post->post_title),
            $old_status,
            $new_status
        );

        $this->log_event(
            'post_status',
            $post->post_type,
            $post->ID,
            $message,
            [
                'post_author' => $post->post_author,
                'post_type'   => $post->post_type,
            ]
        );
    }

    /**
     * Captures profile updates.
     */
    public function capture_profile_update($user_id, $old_user_data) {
        $user    = get_userdata($user_id);
        $message = sprintf('Profile updated for %s.', $user ? $user->user_login : $user_id);

        $this->log_event(
            'profile_update',
            'user',
            $user_id,
            $message,
            [
                'old_display_name' => $old_user_data->display_name ?? '',
                'new_display_name' => $user ? $user->display_name : '',
            ]
        );
    }

    /**
     * Captures new user registrations.
     */
    public function capture_user_register($user_id) {
        $user    = get_userdata($user_id);
        $message = sprintf('New user registered: %s.', $user ? $user->user_login : $user_id);

        $this->log_event(
            'user_register',
            'user',
            $user_id,
            $message,
            [
                'role' => $user ? implode(',', $user->roles) : '',
            ]
        );
    }

    /**
     * Captures user deletions.
     */
    public function capture_user_delete($user_id) {
        $message = sprintf('User deleted: %s.', $user_id);

        $this->log_event(
            'user_delete',
            'user',
            $user_id,
            $message
        );
    }

    /**
     * Captures successful logins.
     */
    public function capture_login($user_login, $user) {
        $this->log_event(
            'user_login',
            'user',
            $user->ID,
            sprintf('User %s authenticated successfully.', $user_login)
        );
    }

    /**
     * Writes a row to the audit log table.
     */
    private function log_event($event_type, $object_type, $object_id, $message, $context = []) {
        if (!$this->settings->is_event_enabled($event_type)) {
            return;
        }

        global $wpdb;

        $ip_address = $this->settings->should_mask_ip() ? '' : $this->get_ip_address();

        $wpdb->insert(
            $this->table_name,
            [
                'event_type' => sanitize_key($event_type),
                'object_type'=> sanitize_key($object_type),
                'object_id'  => $object_id ? (int) $object_id : null,
                'user_id'    => get_current_user_id() ?: null,
                'message'    => wp_strip_all_tags($message),
                'context'    => $context ? wp_json_encode($context) : null,
                'ip_address' => $ip_address,
                'created_at' => current_time('mysql', true),
            ],
            [
                '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s',
            ]
        );

        $this->maybe_prune_logs();
    }

    /**
     * Returns the visitor IP address in a best-effort fashion.
     */
    private function get_ip_address() {
        if (empty($_SERVER['REMOTE_ADDR'])) {
            return '';
        }

        return sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
    }

    /**
     * Deletes logs older than the configured retention window.
     */
    private function maybe_prune_logs() {
        $retention_days = $this->settings->get_retention_days();

        if ($retention_days <= 0) {
            return;
        }

        global $wpdb;

        $cutoff = gmdate('Y-m-d H:i:s', time() - (DAY_IN_SECONDS * $retention_days));
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE created_at < %s",
                $cutoff
            )
        );
    }
}
