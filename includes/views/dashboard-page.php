<?php
/**
 * Dashboard analytics page.
 */

if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="wrap gd-audit gd-audit-dashboard">
    <?php include GD_AUDIT_PLUGIN_DIR . 'includes/views/partials/nav-tabs.php'; ?>

    <h1 class="gd-audit__section-title"><?php esc_html_e('Dashboard', 'gd-audit'); ?></h1>
    <p class="gd-audit-dashboard__lead">
        <?php esc_html_e('Jump directly into each audit area with live snapshots from across your WordPress stack.', 'gd-audit'); ?>
    </p>

    <?php if (!empty($dashboard_tiles)) : ?>
        <div class="gd-audit-dashboard__tiles">
            <?php foreach ($dashboard_tiles as $tile) : ?>
                <a class="gd-audit-dashboard__tile" href="<?php echo esc_url($tile['url']); ?>">
                    <div class="gd-audit-dashboard__tile-head">
                        <span class="gd-audit-dashboard__tile-label"><?php echo esc_html($tile['label']); ?></span>
                        <?php if (!empty($tile['description'])) : ?>
                            <span class="gd-audit-dashboard__tile-desc"><?php echo esc_html($tile['description']); ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="gd-audit-dashboard__tile-value">
                        <?php echo esc_html($tile['primary_value']); ?>
                        <?php if (!empty($tile['primary_label'])) : ?>
                            <span><?php echo esc_html($tile['primary_label']); ?></span>
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($tile['items'])) : ?>
                        <ul class="gd-audit-dashboard__tile-details">
                            <?php foreach ($tile['items'] as $detail) : ?>
                                <li><?php echo esc_html($detail); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <span class="gd-audit-dashboard__tile-cta"><?php esc_html_e('Open tab', 'gd-audit'); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <p><?php esc_html_e('No dashboard data available.', 'gd-audit'); ?></p>
    <?php endif; ?>
</div>
