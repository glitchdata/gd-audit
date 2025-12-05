<?php
/**
 * Settings page template.
 */

if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="wrap gd-audit gd-audit-settings">
    <h1><?php esc_html_e('GD Audit Settings', 'gd-audit'); ?></h1>

    <form method="post" action="options.php" class="gd-audit__settings-form">
        <?php settings_fields('gd_audit_settings_group'); ?>

        <h2><?php esc_html_e('Events to capture', 'gd-audit'); ?></h2>
        <p><?php esc_html_e('Choose which actions should be saved to the audit trail.', 'gd-audit'); ?></p>
        <fieldset>
            <?php foreach ($events as $key => $label) : ?>
                <label class="gd-audit__checkbox">
                    <input type="checkbox" name="<?php echo esc_attr($option_key); ?>[enabled_events][]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $settings['enabled_events'], true)); ?> />
                    <span><?php echo esc_html($label); ?></span>
                </label>
            <?php endforeach; ?>
        </fieldset>

        <h2><?php esc_html_e('Retention & privacy', 'gd-audit'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="gd-audit-retention"><?php esc_html_e('Retention window (days)', 'gd-audit'); ?></label>
                </th>
                <td>
                    <input type="number" id="gd-audit-retention" name="<?php echo esc_attr($option_key); ?>[retention_days]" value="<?php echo esc_attr($settings['retention_days']); ?>" min="0" class="small-text" />
                    <p class="description">
                        <?php esc_html_e('Set to 0 to keep logs indefinitely. Older rows are purged automatically after each new entry.', 'gd-audit'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Mask IP addresses', 'gd-audit'); ?></th>
                <td>
                    <label class="gd-audit__checkbox">
                        <input type="checkbox" name="<?php echo esc_attr($option_key); ?>[mask_ip]" value="1" <?php checked(!empty($settings['mask_ip'])); ?> />
                        <span><?php esc_html_e('Do not store originating IP data in the audit log.', 'gd-audit'); ?></span>
                    </label>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
</div>
