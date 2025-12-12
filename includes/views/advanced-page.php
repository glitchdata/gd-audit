<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap gd-audit gd-audit-advanced">
    <?php include GD_AUDIT_PLUGIN_DIR . 'includes/views/partials/nav-tabs.php'; ?>

    <h1 class="gd-audit__section-title"><?php esc_html_e('Advanced', 'gd-audit'); ?></h1>
    <?php
    $log_submitted = isset($_GET['gd_audit_log_submitted']);
    $log_scheduled = isset($_GET['gd_audit_log_scheduled']) && $_GET['gd_audit_log_scheduled'] === '1';
    $log_error     = isset($_GET['gd_audit_log_error']) ? sanitize_text_field(wp_unslash($_GET['gd_audit_log_error'])) : '';

    if ($log_error) : ?>
        <div class="notice notice-error is-dismissible"><p>
            <?php echo esc_html(sprintf(__('Audit log submission failed: %s', 'gd-audit'), $log_error)); ?>
        </p></div>
    <?php elseif ($log_submitted) : ?>
        <div class="notice notice-success is-dismissible"><p>
            <?php echo esc_html($log_scheduled
                ? __('Audit log submitted and daily schedule created.', 'gd-audit')
                : __('Audit log submitted successfully.', 'gd-audit'));
            ?>
        </p></div>
    <?php elseif ($log_scheduled) : ?>
        <div class="notice notice-info is-dismissible"><p>
            <?php esc_html_e('Daily audit log schedule created.', 'gd-audit'); ?>
        </p></div>
    <?php endif; ?>
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
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:10px;">
            <?php wp_nonce_field('gd_audit_send_log'); ?>
            <input type="hidden" name="action" value="gd_audit_send_log" />
            <button type="submit" class="button button-secondary">
                <?php esc_html_e('Submit Audit to Logs', 'gd-audit'); ?>
            </button>
        </form>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:10px;">
            <?php wp_nonce_field('gd_audit_send_log'); ?>
            <input type="hidden" name="action" value="gd_audit_send_log" />
            <input type="hidden" name="schedule_daily" value="1" />
            <button type="submit" class="button">
                <?php esc_html_e('Schedule Daily Log Submit', 'gd-audit'); ?>
            </button>
        </form>
    </div>

    <div class="gd-audit__panel">
        <h2><?php esc_html_e('Coming soon', 'gd-audit'); ?></h2>
        <p><?php esc_html_e('Advanced diagnostics and tools will appear here.', 'gd-audit'); ?></p>
    </div>
</div>
