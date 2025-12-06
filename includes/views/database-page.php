<?php
/**
 * Database overview page.
 */

if (!defined('ABSPATH')) {
    exit;
}

$total_size  = $summary['data_size'] + $summary['index_size'];
$date_format = get_option('date_format') . ' ' . get_option('time_format');
?>
<div class="wrap gd-audit gd-audit-database">
    <?php include GD_AUDIT_PLUGIN_DIR . 'includes/views/partials/nav-tabs.php'; ?>

    <h1 class="gd-audit__section-title"><?php esc_html_e('Database', 'gd-audit'); ?></h1>

    <section class="gd-audit__cards">
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Tables', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html(number_format_i18n($summary['total_tables'])); ?></p>
            <p class="gd-audit__meta"><?php printf(esc_html__('%s WordPress tables', 'gd-audit'), esc_html(number_format_i18n($summary['wp_tables']))); ?></p>
        </article>
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Total rows', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html(number_format_i18n($summary['total_rows'])); ?></p>
        </article>
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Data size', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html(size_format($summary['data_size'], 2)); ?></p>
        </article>
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Index size', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html(size_format($summary['index_size'], 2)); ?></p>
            <p class="gd-audit__meta"><?php printf(esc_html__('Total storage %s', 'gd-audit'), esc_html(size_format($total_size, 2))); ?></p>
        </article>
    </section>

    <hr class="gd-audit__divider" />

    <table class="wp-list-table widefat fixed striped gd-audit__table">
        <thead>
            <tr>
                <th><?php esc_html_e('Table', 'gd-audit'); ?></th>
                <th><?php esc_html_e('Engine', 'gd-audit'); ?></th>
                <th><?php esc_html_e('Rows', 'gd-audit'); ?></th>
                <th><?php esc_html_e('Data', 'gd-audit'); ?></th>
                <th><?php esc_html_e('Index', 'gd-audit'); ?></th>
                <th><?php esc_html_e('Total', 'gd-audit'); ?></th>
                <th><?php esc_html_e('Collation', 'gd-audit'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($tables) : ?>
                <?php foreach ($tables as $table) : ?>
                    <?php
                    $total_bytes       = $table['data_length'] + $table['index_length'];
                    $created_timestamp = !empty($table['create_time']) ? strtotime($table['create_time']) : false;
                    $updated_timestamp = !empty($table['update_time']) ? strtotime($table['update_time']) : false;
                    $checked_timestamp = !empty($table['check_time']) ? strtotime($table['check_time']) : false;
                    $created_display   = $created_timestamp ? wp_date($date_format, $created_timestamp) : __('Unknown', 'gd-audit');
                    $updated_display   = $updated_timestamp ? wp_date($date_format, $updated_timestamp) : __('Unknown', 'gd-audit');
                    $checked_display   = $checked_timestamp ? wp_date($date_format, $checked_timestamp) : __('Never', 'gd-audit');
                    $row_format        = !empty($table['row_format']) ? $table['row_format'] : __('Unknown', 'gd-audit');
                    $avg_row_length    = $table['avg_row_length'] > 0 ? size_format($table['avg_row_length'], 2) : __('N/A', 'gd-audit');
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($table['name']); ?></strong>
                            <p class="gd-audit__meta">
                                <?php if (!empty($table['is_wp_table'])) : ?>
                                    <span class="gd-audit__badge is-active"><?php esc_html_e('WP', 'gd-audit'); ?></span>
                                <?php else : ?>
                                    <span class="gd-audit__badge is-inactive"><?php esc_html_e('Custom', 'gd-audit'); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($table['comment'])) : ?>
                                    <?php echo esc_html($table['comment']); ?>
                                <?php endif; ?>
                            </p>
                            <details class="gd-audit__table-details">
                                <summary class="gd-audit__table-details-summary">
                                    <span class="gd-audit__details-icon" aria-hidden="true"></span>
                                    <span class="gd-audit__details-text"><?php esc_html_e('View table details', 'gd-audit'); ?></span>
                                </summary>
                                <div class="gd-audit__table-details-body">
                                    <div class="gd-audit__table-details-grid">
                                        <div>
                                            <p class="gd-audit__detail-label"><?php esc_html_e('Row format', 'gd-audit'); ?></p>
                                            <p class="gd-audit__detail-value"><?php echo esc_html($row_format); ?></p>
                                        </div>
                                        <div>
                                            <p class="gd-audit__detail-label"><?php esc_html_e('Average row length', 'gd-audit'); ?></p>
                                            <p class="gd-audit__detail-value"><?php echo esc_html($avg_row_length); ?></p>
                                        </div>
                                        <div>
                                            <p class="gd-audit__detail-label"><?php esc_html_e('Free space', 'gd-audit'); ?></p>
                                            <p class="gd-audit__detail-value"><?php echo esc_html(size_format($table['data_free'], 2)); ?></p>
                                        </div>
                                        <div>
                                            <p class="gd-audit__detail-label"><?php esc_html_e('Created at', 'gd-audit'); ?></p>
                                            <p class="gd-audit__detail-value"><?php echo esc_html($created_display); ?></p>
                                        </div>
                                        <div>
                                            <p class="gd-audit__detail-label"><?php esc_html_e('Updated at', 'gd-audit'); ?></p>
                                            <p class="gd-audit__detail-value"><?php echo esc_html($updated_display); ?></p>
                                        </div>
                                        <div>
                                            <p class="gd-audit__detail-label"><?php esc_html_e('Last checked', 'gd-audit'); ?></p>
                                            <p class="gd-audit__detail-value"><?php echo esc_html($checked_display); ?></p>
                                        </div>
                                    </div>
                                    <?php if (!empty($table['columns'])) : ?>
                                        <div class="gd-audit__table-details-columns">
                                            <p class="gd-audit__detail-label"><?php esc_html_e('Fields', 'gd-audit'); ?></p>
                                            <ul class="gd-audit__columns-list">
                                                <?php foreach ($table['columns'] as $column) : ?>
                                                    <li>
                                                        <span class="gd-audit__column-name"><?php echo esc_html($column['name']); ?></span>
                                                        <span class="gd-audit__column-type"><?php echo esc_html($column['type']); ?></span>
                                                        <?php if (!empty($column['key'])) : ?>
                                                            <span class="gd-audit__column-key"><?php echo esc_html($column['key']); ?></span>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                            <?php
                                            $total_columns = isset($table['column_total']) ? (int) $table['column_total'] : count($table['columns']);
                                            if ($total_columns > count($table['columns'])) :
                                                ?>
                                                <p class="gd-audit__detail-meta">
                                                    <?php
                                                    printf(
                                                        esc_html__('Showing %1$d of %2$d fields', 'gd-audit'),
                                                        count($table['columns']),
                                                        $total_columns
                                                    );
                                                    ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($table['comment'])) : ?>
                                        <div class="gd-audit__table-details-note">
                                            <p class="gd-audit__detail-label"><?php esc_html_e('Table comment', 'gd-audit'); ?></p>
                                            <p class="gd-audit__detail-value"><?php echo esc_html($table['comment']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </details>
                        </td>
                        <td><?php echo esc_html($table['engine']); ?></td>
                        <td><?php echo esc_html(number_format_i18n($table['rows'])); ?></td>
                        <td>
                            <?php echo esc_html(size_format($table['data_length'], 2)); ?>
                            <?php if ($table['data_free'] > 0) : ?>
                                <p class="gd-audit__meta"><?php printf(esc_html__('Free: %s', 'gd-audit'), esc_html(size_format($table['data_free'], 2))); ?></p>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo esc_html(size_format($table['index_length'], 2)); ?>
                            <?php if ($table['index_length'] <= 0) : ?>
                                <p class="gd-audit__meta"><?php esc_html_e('No indexes', 'gd-audit'); ?></p>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html(size_format($total_bytes, 2)); ?></td>
                        <td><?php echo esc_html($table['collation']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="7"><?php esc_html_e('No tables found or insufficient permissions.', 'gd-audit'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</div>
