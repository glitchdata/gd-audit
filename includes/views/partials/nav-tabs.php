<?php
/**
 * Shared nav tabs for GD Audit admin pages.
 */

if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="gd-audit__tabs">
    <h1 class="wp-heading-inline"><?php esc_html_e('GD Audit', 'gd-audit'); ?></h1>
    <h2 class="nav-tab-wrapper">
        <?php foreach ($nav_tabs as $tab) : ?>
            <a href="<?php echo esc_url($tab['url']); ?>" class="nav-tab <?php echo !empty($tab['active']) ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($tab['label']); ?>
            </a>
        <?php endforeach; ?>
    </h2>
</div>
