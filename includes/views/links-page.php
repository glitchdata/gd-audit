<?php
/**
 * Links analytics page.
 */

if (!defined('ABSPATH')) {
    exit;
}

$scanned_posts = $overview['scanned'] ?? 0;
$total_links   = $overview['total'] ?? 0;
$internal      = $overview['internal'] ?? 0;
$external      = $overview['external'] ?? 0;
$external_pct  = $total_links > 0 ? round(($external / $total_links) * 100) : 0;
$internal_pct  = $total_links > 0 ? max(0, 100 - $external_pct) : 0;
$avg_links     = $scanned_posts > 0 ? round($total_links / $scanned_posts, 1) : 0;
?>
<div class="wrap gd-audit gd-audit-links">
    <?php include GD_AUDIT_PLUGIN_DIR . 'includes/views/partials/nav-tabs.php'; ?>

    <h1 class="gd-audit__section-title"><?php esc_html_e('Links', 'gd-audit'); ?></h1>

    <section class="gd-audit__cards">
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Posts scanned', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html(number_format_i18n($scanned_posts)); ?></p>
        </article>
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Total links', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html(number_format_i18n($total_links)); ?></p>
        </article>
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Avg links / post', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html(number_format_i18n($avg_links, 1)); ?></p>
        </article>
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Internal links', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html(number_format_i18n($internal)); ?></p>
        </article>
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('External links', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html(number_format_i18n($external)); ?></p>
            <span class="gd-audit__meta"><?php printf(esc_html__('%s%% of all links', 'gd-audit'), esc_html($external_pct)); ?></span>
        </article>
    </section>

    <section class="gd-audit-links__distribution">
        <article class="gd-audit-links__split-card">
            <div class="gd-audit-links__split-heading">
                <span><?php esc_html_e('Internal share', 'gd-audit'); ?></span>
                <strong><?php echo esc_html($internal_pct); ?>%</strong>
            </div>
            <div class="gd-audit-links__meter">
                <span class="gd-audit-links__meter-fill is-internal" style="width: <?php echo esc_attr($internal_pct); ?>%"></span>
            </div>
            <p class="gd-audit__meta"><?php esc_html_e('Links that keep visitors on-site', 'gd-audit'); ?></p>
        </article>
        <article class="gd-audit-links__split-card">
            <div class="gd-audit-links__split-heading">
                <span><?php esc_html_e('External share', 'gd-audit'); ?></span>
                <strong><?php echo esc_html($external_pct); ?>%</strong>
            </div>
            <div class="gd-audit-links__meter">
                <span class="gd-audit-links__meter-fill is-external" style="width: <?php echo esc_attr($external_pct); ?>%"></span>
            </div>
            <p class="gd-audit__meta"><?php esc_html_e('Outbound links pointing away from your site', 'gd-audit'); ?></p>
        </article>
    </section>

    <div class="gd-audit__panels">
        <section class="gd-audit__panel">
            <h2><?php esc_html_e('Top linked posts', 'gd-audit'); ?></h2>
            <?php if ($top_posts) : ?>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Post', 'gd-audit'); ?></th>
                            <th><?php esc_html_e('Links', 'gd-audit'); ?></th>
                            <th><?php esc_html_e('Actions', 'gd-audit'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_posts as $post_item) : ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($post_item['title']); ?></strong>
                                </td>
                                <td><span class="gd-audit__pill"><?php echo esc_html(number_format_i18n($post_item['count'])); ?></span></td>
                                <td class="gd-audit-links__actions">
                                    <?php if (!empty($post_item['edit_url'])) : ?>
                                        <a class="gd-audit-links__action" href="<?php echo esc_url($post_item['edit_url']); ?>"><?php esc_html_e('Edit', 'gd-audit'); ?></a>
                                    <?php endif; ?>
                                    <?php if (!empty($post_item['view_url'])) : ?>
                                        <a class="gd-audit-links__action" href="<?php echo esc_url($post_item['view_url']); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('View', 'gd-audit'); ?></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e('No links detected in the sampled posts.', 'gd-audit'); ?></p>
            <?php endif; ?>
        </section>

        <section class="gd-audit__panel">
            <h2><?php esc_html_e('Top outbound domains', 'gd-audit'); ?></h2>
            <?php if ($top_domains) : ?>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Domain', 'gd-audit'); ?></th>
                            <th><?php esc_html_e('Links', 'gd-audit'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_domains as $domain) : ?>
                            <tr>
                                <td><?php echo esc_html($domain['domain']); ?></td>
                                <td><span class="gd-audit__pill"><?php echo esc_html(number_format_i18n($domain['count'])); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e('No outbound domains detected in the sampled posts.', 'gd-audit'); ?></p>
            <?php endif; ?>
        </section>
    </div>
</div>
