<?php
/**
 * Installed plugins overview.
 */

if (!defined('ABSPATH')) {
    exit;
}

$total_plugins = count($plugins);
$manage_url    = admin_url('plugins.php');
?>
<div class="wrap gd-audit gd-audit-plugins">
    <?php include GD_AUDIT_PLUGIN_DIR . 'includes/views/partials/nav-tabs.php'; ?>

    <h1 class="gd-audit__section-title"><?php esc_html_e('Plugins', 'gd-audit'); ?></h1>

    <section class="gd-audit__cards">
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Installed', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html(number_format_i18n($total_plugins)); ?></p>
        </article>
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Active', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html(number_format_i18n($active_count)); ?></p>
        </article>
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Inactive', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html(number_format_i18n($inactive_count)); ?></p>
        </article>
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Updates available', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html(number_format_i18n($update_count)); ?></p>
        </article>
    </section>

    <hr class="gd-audit__divider" />

    <table class="wp-list-table widefat fixed striped gd-audit__table">
        <thead>
            <tr>
                <th><?php esc_html_e('Plugin', 'gd-audit'); ?></th>
                <th><?php esc_html_e('Version', 'gd-audit'); ?></th>
                <th><?php esc_html_e('Status', 'gd-audit'); ?></th>
                <th><?php esc_html_e('Author', 'gd-audit'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($plugins) : ?>
                <?php foreach ($plugins as $plugin) : ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($plugin['name']); ?></strong>
                            <?php if (!empty($plugin['description'])) : ?>
                                <p class="description"><?php echo esc_html($plugin['description']); ?></p>
                            <?php endif; ?>
                            <p class="row-actions">
                                <span>
                                    <a href="<?php echo esc_url($manage_url); ?>" class="gd-audit__link">
                                        <?php esc_html_e('Manage', 'gd-audit'); ?>
                                    </a>
                                </span>
                                <?php if (!empty($plugin['plugin_url'])) : ?>
                                    | <span>
                                        <a href="<?php echo esc_url($plugin['plugin_url']); ?>" target="_blank" rel="noopener noreferrer" class="gd-audit__link">
                                            <?php esc_html_e('Plugin site', 'gd-audit'); ?>
                                        </a>
                                    </span>
                                <?php endif; ?>
                            </p>
                        </td>
                        <td><?php echo esc_html($plugin['version']); ?></td>
                        <td>
                            <span class="gd-audit__badge <?php echo !empty($plugin['active']) ? 'is-active' : 'is-inactive'; ?>">
                                <?php echo !empty($plugin['active']) ? esc_html__('Active', 'gd-audit') : esc_html__('Inactive', 'gd-audit'); ?>
                            </span>
                            <?php if (!empty($plugin['has_update'])) : ?>
                                <span class="gd-audit__badge is-update"><?php esc_html_e('Update available', 'gd-audit'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($plugin['author']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="4"><?php esc_html_e('No plugins detected.', 'gd-audit'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
