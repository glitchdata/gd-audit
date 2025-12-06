<?php
/**
 * Settings page template.
 */

if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="wrap gd-audit gd-audit-settings">
    <?php include GD_AUDIT_PLUGIN_DIR . 'includes/views/partials/nav-tabs.php'; ?>

    <h1 class="gd-audit__section-title"><?php esc_html_e('Settings', 'gd-audit'); ?></h1>

    <form method="post" action="options.php" class="gd-audit__settings-form">
        <?php settings_fields('gd_audit_settings_group'); ?>

        <h2><?php esc_html_e('License', 'gd-audit'); ?></h2>
        <p><?php esc_html_e('Add your license key to unlock premium updates and support.', 'gd-audit'); ?></p>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="gd-audit-license-key"><?php esc_html_e('License key', 'gd-audit'); ?></label>
                </th>
                <td>
                    <input type="text" id="gd-audit-license-key" name="<?php echo esc_attr($option_key); ?>[license_key]" value="<?php echo esc_attr($settings['license_key']); ?>" class="regular-text" autocomplete="off" />
                    <p class="description">
                        <?php esc_html_e('Paste the key from your purchase receipt. Leave blank to deactivate the license on this site.', 'gd-audit'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
</div>
