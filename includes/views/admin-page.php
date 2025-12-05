<?php
/**
 * Admin template for GD Audit logs.
 */

if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="wrap gd-audit">
    <h1><?php esc_html_e('GD Audit Logs', 'gd-audit'); ?></h1>

    <form method="get" class="gd-audit__filters">
        <input type="hidden" name="page" value="gd-audit" />
        <input type="hidden" name="paged" value="1" />

        <label>
            <?php esc_html_e('Event Type', 'gd-audit'); ?>
            <select name="event_type">
                <option value=""><?php esc_html_e('All', 'gd-audit'); ?></option>
                <?php foreach ($eventTypes as $type) : ?>
                    <option value="<?php echo esc_attr($type); ?>" <?php selected($filters['event_type'], $type); ?>>
                        <?php echo esc_html($type); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            <?php esc_html_e('Search', 'gd-audit'); ?>
            <input type="search" name="s" value="<?php echo esc_attr($filters['search']); ?>" placeholder="<?php esc_attr_e('Message or context', 'gd-audit'); ?>" />
        </label>

        <label>
            <?php esc_html_e('Date From', 'gd-audit'); ?>
            <input type="date" name="date_from" value="<?php echo esc_attr($filters['date_from']); ?>" />
        </label>

        <label>
            <?php esc_html_e('Date To', 'gd-audit'); ?>
            <input type="date" name="date_to" value="<?php echo esc_attr($filters['date_to']); ?>" />
        </label>

        <button class="button button-primary"><?php esc_html_e('Apply Filters', 'gd-audit'); ?></button>
    </form>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Timestamp', 'gd-audit'); ?></th>
                <th><?php esc_html_e('Event', 'gd-audit'); ?></th>
                <th><?php esc_html_e('Object', 'gd-audit'); ?></th>
                <th><?php esc_html_e('User', 'gd-audit'); ?></th>
                <th><?php esc_html_e('Message', 'gd-audit'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($logs) : ?>
                <?php foreach ($logs as $log) : ?>
                    <tr>
                        <td><?php echo esc_html(get_date_from_gmt($log->created_at, get_option('date_format') . ' ' . get_option('time_format'))); ?></td>
                        <td><?php echo esc_html($log->event_type); ?></td>
                        <td><?php echo esc_html($log->object_type . ' #' . $log->object_id); ?></td>
                        <td>
                            <?php
                            if ($log->user_id) {
                                $user = get_userdata($log->user_id);
                                echo esc_html($user ? $user->user_login : $log->user_id);
                            } else {
                                echo '&mdash;';
                            }
                            ?>
                        </td>
                        <td>
                            <strong><?php echo esc_html($log->message); ?></strong>
                            <?php if ($log->context) : ?>
                                <details>
                                    <summary><?php esc_html_e('Context', 'gd-audit'); ?></summary>
                                    <pre><?php echo esc_html(wp_json_encode(json_decode($log->context), JSON_PRETTY_PRINT)); ?></pre>
                                </details>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="5"><?php esc_html_e('No logs found for the current filters.', 'gd-audit'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="tablenav">
        <div class="tablenav-pages">
            <?php
            echo paginate_links([
                'base'      => add_query_arg('paged', '%#%'),
                'format'    => '',
                'prev_text' => __('&laquo; Previous', 'gd-audit'),
                'next_text' => __('Next &raquo;', 'gd-audit'),
                'total'     => $total_pages,
                'current'   => $paged,
            ]);
            ?>
        </div>
    </div>
</div>
