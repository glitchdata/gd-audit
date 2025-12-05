<?php
/**
 * Database overview page.
 */

if (!defined('ABSPATH')) {
    exit;
}

$total_size = $summary['data_size'] + $summary['index_size'];
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
                    <?php $total_bytes = $table['data_length'] + $table['index_length']; ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($table['name']); ?></strong>
                            <p class="gd-audit__meta">
                                <?php if (!empty($table['is_wp_table'])) : ?>
                                    <span class="gd-audit__badge is-active"><?php esc_html_e('WP', 'gd-audit'); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($table['comment'])) : ?>
                                    <?php echo esc_html($table['comment']); ?>
                                <?php endif; ?>
                            </p>
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
