<?php
/**
 * Cron diagnostics page.
 */

if (!defined('ABSPATH')) {
    exit;
}

$summary   = isset($cron_overview['summary']) ? $cron_overview['summary'] : [];
$schedules = isset($cron_overview['schedules']) ? $cron_overview['schedules'] : [];
$events    = isset($cron_overview['events']) ? $cron_overview['events'] : [];
$constants = isset($cron_overview['constants']) ? $cron_overview['constants'] : [];

$schedule_labels = [];
foreach ($schedules as $schedule) {
    $schedule_labels[$schedule['name']] = $schedule['label'];
}

$cron_status_label = !empty($summary['cron_enabled']) ? __('Enabled', 'gd-audit') : __('Disabled', 'gd-audit');
$cron_status_meta  = !empty($summary['cron_enabled'])
    ? __('WP-Cron is running on this site.', 'gd-audit')
    : __('DISABLE_WP_CRON is preventing scheduled tasks.', 'gd-audit');

$next_event_label = !empty($summary['next_event']) ? $summary['next_event'] : __('No upcoming events', 'gd-audit');
$next_event_meta  = !empty($summary['next_event_diff'])
    ? sprintf(__('In %s', 'gd-audit'), $summary['next_event_diff'])
    : __('Queue is empty or events already ran.', 'gd-audit');

$schedule_total  = isset($summary['schedule_total']) ? (int) $summary['schedule_total'] : 0;
$event_total     = isset($summary['event_total']) ? (int) $summary['event_total'] : 0;
$lock_timeout    = isset($summary['lock_timeout']) ? (int) $summary['lock_timeout'] : 60;
?>
<div class="wrap gd-audit gd-audit-cron">
    <?php include GD_AUDIT_PLUGIN_DIR . 'includes/views/partials/nav-tabs.php'; ?>

    <h1 class="gd-audit__section-title"><?php esc_html_e('Cron', 'gd-audit'); ?></h1>
    <p class="gd-audit-links__lead"><?php esc_html_e('Monitor WordPress Cron status, custom schedules, and queued events to keep automations healthy.', 'gd-audit'); ?></p>

    <section class="gd-audit__cards">
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Cron status', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html($cron_status_label); ?></p>
            <p class="gd-audit__meta"><?php echo esc_html($cron_status_meta); ?></p>
        </article>
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Next event', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html($next_event_label); ?></p>
            <p class="gd-audit__meta"><?php echo esc_html($next_event_meta); ?></p>
        </article>
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Queued events', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html(number_format_i18n($event_total)); ?></p>
            <p class="gd-audit__meta"><?php esc_html_e('Total hooks waiting to run.', 'gd-audit'); ?></p>
        </article>
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Registered schedules', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html(number_format_i18n($schedule_total)); ?></p>
            <p class="gd-audit__meta"><?php esc_html_e('Intervals provided by core or plugins.', 'gd-audit'); ?></p>
        </article>
    </section>

    <div class="gd-audit__panels">
        <section class="gd-audit__panel">
            <h2><?php esc_html_e('Cron settings', 'gd-audit'); ?></h2>
            <table class="widefat fixed striped">
                <tbody>
                    <tr>
                        <th><?php esc_html_e('WP-Cron enabled', 'gd-audit'); ?></th>
                        <td>
                            <?php if (!empty($summary['cron_enabled'])) : ?>
                                <span class="gd-audit__badge is-active"><?php esc_html_e('Enabled', 'gd-audit'); ?></span>
                            <?php else : ?>
                                <span class="gd-audit__badge is-inactive"><?php esc_html_e('Disabled', 'gd-audit'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Alternate Cron', 'gd-audit'); ?></th>
                        <td>
                            <?php if (!empty($summary['alternate_cron'])) : ?>
                                <span class="gd-audit__badge is-active"><?php esc_html_e('Enabled', 'gd-audit'); ?></span>
                            <?php else : ?>
                                <span class="gd-audit__badge is-inactive"><?php esc_html_e('Disabled', 'gd-audit'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Lock timeout', 'gd-audit'); ?></th>
                        <td><?php echo esc_html($lock_timeout); ?>s</td>
                    </tr>
                </tbody>
            </table>
        </section>
        <section class="gd-audit__panel">
            <h2><?php esc_html_e('Cron constants', 'gd-audit'); ?></h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Constant', 'gd-audit'); ?></th>
                        <th><?php esc_html_e('Value', 'gd-audit'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($constants) : ?>
                        <?php foreach ($constants as $constant) : ?>
                            <tr>
                                <td><?php echo esc_html($constant['label']); ?></td>
                                <td><?php echo esc_html($constant['value']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="2"><?php esc_html_e('No cron constants detected.', 'gd-audit'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>

    <section class="gd-audit__panels">
        <section class="gd-audit__panel">
            <h2><?php esc_html_e('Registered schedules', 'gd-audit'); ?></h2>
            <table class="wp-list-table widefat fixed striped gd-audit__table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Name', 'gd-audit'); ?></th>
                        <th><?php esc_html_e('Label', 'gd-audit'); ?></th>
                        <th><?php esc_html_e('Interval', 'gd-audit'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($schedules) : ?>
                        <?php foreach ($schedules as $schedule) : ?>
                            <?php
                            $interval = $schedule['interval'];
                            $interval_label = $interval > 0
                                ? human_time_diff(time(), time() + $interval)
                                : __('N/A', 'gd-audit');
                            ?>
                            <tr>
                                <td><code><?php echo esc_html($schedule['name']); ?></code></td>
                                <td><?php echo esc_html($schedule['label']); ?></td>
                                <td>
                                    <?php echo esc_html($interval_label); ?>
                                    <?php if ($interval > 0) : ?>
                                        <p class="gd-audit__meta"><?php printf(esc_html__('%s seconds', 'gd-audit'), esc_html(number_format_i18n($interval))); ?></p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="3"><?php esc_html_e('No schedules registered.', 'gd-audit'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </section>

    <section class="gd-audit__panels">
        <section class="gd-audit__panel">
            <h2><?php esc_html_e('Upcoming events', 'gd-audit'); ?></h2>
            <table class="wp-list-table widefat fixed striped gd-audit__table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Hook', 'gd-audit'); ?></th>
                        <th><?php esc_html_e('Schedule', 'gd-audit'); ?></th>
                        <th><?php esc_html_e('Next run', 'gd-audit'); ?></th>
                        <th><?php esc_html_e('Status', 'gd-audit'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($events) : ?>
                        <?php foreach ($events as $event) : ?>
                            <?php
                            $schedule_key   = isset($event['schedule']) ? $event['schedule'] : '';
                            $schedule_label = $schedule_key && isset($schedule_labels[$schedule_key])
                                ? $schedule_labels[$schedule_key]
                                : ($schedule_key ? $schedule_key : __('One-off', 'gd-audit'));
                            $status_label = !empty($event['is_past'])
                                ? sprintf(__('Ran %s ago', 'gd-audit'), $event['time_diff'])
                                : sprintf(__('In %s', 'gd-audit'), $event['time_diff']);
                            ?>
                            <tr>
                                <td>
                                    <code><?php echo esc_html($event['hook']); ?></code>
                                    <?php if (!empty($event['args'])) : ?>
                                        <p class="gd-audit__meta">
                                            <?php
                                            printf(
                                                esc_html(_n('%d argument', '%d arguments', count($event['args']), 'gd-audit')),
                                                count($event['args'])
                                            );
                                            ?>
                                        </p>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($schedule_label); ?></td>
                                <td><?php echo esc_html($event['display']); ?></td>
                                <td><?php echo esc_html($status_label); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4"><?php esc_html_e('No upcoming cron events detected.', 'gd-audit'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php if ($event_total > count($events)) : ?>
                <p class="gd-audit__meta"><?php printf(esc_html__('Showing %1$d of %2$d queued events.', 'gd-audit'), count($events), $event_total); ?></p>
            <?php endif; ?>
        </section>
    </section>
</div>
