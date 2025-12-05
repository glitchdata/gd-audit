<?php
/**
 * Installed themes overview.
 */

if (!defined('ABSPATH')) {
    exit;
}

$total_themes   = count($themes);
$manage_url     = admin_url('themes.php');
$active_name    = $active_theme['name'] ?? __('Unknown', 'gd-audit');
$active_version = $active_theme['version'] ?? '';
$active_parent  = $active_theme['parent_name'] ?? '';
$screenshot     = $active_theme['screenshot'] ?? '';
?>
<div class="wrap gd-audit gd-audit-themes">
    <?php include GD_AUDIT_PLUGIN_DIR . 'includes/views/partials/nav-tabs.php'; ?>

    <h1 class="gd-audit__section-title"><?php esc_html_e('Themes', 'gd-audit'); ?></h1>

    <section class="gd-audit__cards">
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Installed', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html(number_format_i18n($total_themes)); ?></p>
        </article>
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Active theme', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html($active_name); ?></p>
            <?php if ($active_version) : ?>
                <p class="gd-audit__meta"><?php printf(esc_html__('Version %s', 'gd-audit'), esc_html($active_version)); ?></p>
            <?php endif; ?>
            <?php if ($active_parent) : ?>
                <p class="gd-audit__meta"><?php printf(esc_html__('Child of %s', 'gd-audit'), esc_html($active_parent)); ?></p>
            <?php endif; ?>
        </article>
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Child themes', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html(number_format_i18n($child_count)); ?></p>
        </article>
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Updates available', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html(number_format_i18n($update_count)); ?></p>
        </article>
    </section>

    <?php if ($screenshot) : ?>
        <div class="gd-audit__hero">
            <img src="<?php echo esc_url($screenshot); ?>" alt="<?php echo esc_attr($active_name); ?>" />
            <div>
                <h2><?php esc_html_e('Current theme preview', 'gd-audit'); ?></h2>
                <p class="description"><?php esc_html_e('This is the screenshot bundled with your active theme.', 'gd-audit'); ?></p>
                <a href="<?php echo esc_url($manage_url); ?>" class="button button-secondary"><?php esc_html_e('Manage themes', 'gd-audit'); ?></a>
            </div>
        </div>
    <?php endif; ?>

    <table class="wp-list-table widefat fixed striped gd-audit__table">
        <thead>
            <tr>
                <th><?php esc_html_e('Theme', 'gd-audit'); ?></th>
                <th><?php esc_html_e('Version', 'gd-audit'); ?></th>
                <th><?php esc_html_e('Status', 'gd-audit'); ?></th>
                <th><?php esc_html_e('Parent', 'gd-audit'); ?></th>
                <th><?php esc_html_e('Author', 'gd-audit'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($themes) : ?>
                <?php foreach ($themes as $theme) : ?>
                    <tr>
                        <td>
                            <div class="gd-audit__theme">
                                <?php if (!empty($theme['screenshot'])) : ?>
                                    <img src="<?php echo esc_url($theme['screenshot']); ?>" alt="<?php echo esc_attr($theme['name']); ?>" />
                                <?php endif; ?>
                                <div>
                                    <strong><?php echo esc_html($theme['name']); ?></strong>
                                    <?php if (!empty($theme['description'])) : ?>
                                        <p class="description"><?php echo esc_html($theme['description']); ?></p>
                                    <?php endif; ?>
                                    <p class="row-actions">
                                        <span>
                                            <a href="<?php echo esc_url($manage_url); ?>" class="gd-audit__link"><?php esc_html_e('Manage', 'gd-audit'); ?></a>
                                        </span>
                                        <?php if (!empty($theme['theme_url'])) : ?>
                                            | <span>
                                                <a href="<?php echo esc_url($theme['theme_url']); ?>" target="_blank" rel="noopener noreferrer" class="gd-audit__link"><?php esc_html_e('Theme site', 'gd-audit'); ?></a>
                                            </span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td><?php echo esc_html($theme['version']); ?></td>
                        <td>
                            <span class="gd-audit__badge <?php echo !empty($theme['is_active']) ? 'is-active' : 'is-inactive'; ?>">
                                <?php echo !empty($theme['is_active']) ? esc_html__('Active', 'gd-audit') : esc_html__('Inactive', 'gd-audit'); ?>
                            </span>
                            <?php if (!empty($theme['has_update'])) : ?>
                                <span class="gd-audit__badge is-update"><?php esc_html_e('Update available', 'gd-audit'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($theme['parent_name'])) : ?>
                                <?php echo esc_html($theme['parent_name']); ?>
                            <?php else : ?>
                                &mdash;
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($theme['author']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="5"><?php esc_html_e('No themes detected.', 'gd-audit'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
