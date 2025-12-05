<?php
/**
 * Configuration overview page.
 */

if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="wrap gd-audit gd-audit-config">
    <?php include GD_AUDIT_PLUGIN_DIR . 'includes/views/partials/nav-tabs.php'; ?>

    <h1 class="gd-audit__section-title"><?php esc_html_e('Configuration', 'gd-audit'); ?></h1>
    <p class="gd-audit-config__lead">
        <?php esc_html_e('A quick reference for the core WordPress, server, and feature settings powering this site.', 'gd-audit'); ?>
    </p>

    <?php if (!empty($config_summary)) : ?>
        <section class="gd-audit__cards">
            <?php foreach ($config_summary as $card) : ?>
                <article class="gd-audit__card">
                    <p class="gd-audit__card-label"><?php echo esc_html($card['label']); ?></p>
                    <p class="gd-audit__card-value"><?php echo esc_html($card['value']); ?></p>
                    <?php if (!empty($card['meta'])) : ?>
                        <span class="gd-audit__meta"><?php echo esc_html($card['meta']); ?></span>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php if (!empty($config_sections)) : ?>
        <div class="gd-audit__panels">
            <?php foreach ($config_sections as $section) : ?>
                <section class="gd-audit__panel">
                    <h2><?php echo esc_html($section['title']); ?></h2>
                    <table class="widefat fixed striped gd-audit-config__table">
                        <tbody>
                            <?php foreach ($section['items'] as $item) : ?>
                                <tr>
                                    <th scope="row"><?php echo esc_html($item['label']); ?></th>
                                    <td>
                                        <?php
                                        $value = $item['value'];
                                        if (!empty($item['is_boolean'])) {
                                            $enabled    = (bool) $value;
                                            $true_label = $item['true_label'] ?? __('Enabled', 'gd-audit');
                                            $false_label = $item['false_label'] ?? __('Disabled', 'gd-audit');
                                            $display    = $enabled ? $true_label : $false_label;
                                            $badge_class = $enabled ? 'gd-audit__badge is-active' : 'gd-audit__badge is-inactive';
                                            echo '<span class="' . esc_attr($badge_class) . '">' . esc_html($display) . '</span>';
                                        } else {
                                            if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                                                echo '<a class="gd-audit__link" href="' . esc_url($value) . '" target="_blank" rel="noopener noreferrer">' . esc_html($value) . '</a>';
                                            } elseif ($value === '' || $value === null) {
                                                esc_html_e('Not set', 'gd-audit');
                                            } else {
                                                if (is_int($value) || is_float($value)) {
                                                    echo esc_html($value);
                                                } else {
                                                    echo esc_html($value);
                                                }
                                            }
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <p><?php esc_html_e('No configuration data available.', 'gd-audit'); ?></p>
    <?php endif; ?>
</div>
