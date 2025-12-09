<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap gd-audit gd-audit-advanced">
    <?php include GD_AUDIT_PLUGIN_DIR . 'includes/views/partials/nav-tabs.php'; ?>

    <h1 class="gd-audit__section-title"><?php esc_html_e('Advanced', 'gd-audit'); ?></h1>
    <p class="gd-audit__lead">
        <?php esc_html_e('Premium insights are enabled because your license is valid.', 'gd-audit'); ?>
    </p>

    <div class="gd-audit__panel">
        <h2><?php esc_html_e('Export audit data', 'gd-audit'); ?></h2>
        <p><?php esc_html_e('Download a JSON export of key audit information (posts, users, links, media, plugins, themes, database, and site config).', 'gd-audit'); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('gd_audit_export'); ?>
            <input type="hidden" name="action" value="gd_audit_export" />
            <button type="submit" name="gd_audit_export_json" class="button button-primary">
                <?php esc_html_e('Download JSON Export', 'gd-audit'); ?>
            </button>
        </form>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:10px;">
            <?php wp_nonce_field('gd_audit_export_pdf'); ?>
            <input type="hidden" name="action" value="gd_audit_export_pdf" />
            <button type="submit" class="button">
                <?php esc_html_e('Download PDF Report', 'gd-audit'); ?>
            </button>
        </form>
    </div>

    <div class="gd-audit__panel">
        <h2><?php esc_html_e('Coming soon', 'gd-audit'); ?></h2>
        <p><?php esc_html_e('Advanced diagnostics and tools will appear here.', 'gd-audit'); ?></p>
    </div>
</div>
