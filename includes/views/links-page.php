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
$avg_links     = $scanned_posts > 0 ? round($total_links / max(1, $scanned_posts), 1) : 0;
$hero_types    = $active_type_labels ? implode(', ', $active_type_labels) : __('Posts', 'gd-audit');

$callout = null;
if ($external_pct >= 60) {
    $callout = [
        'label' => __('High outbound activity', 'gd-audit'),
        'text'  => __('More than 60% of sampled links are external. Consider auditing nofollow policies and related partner content.', 'gd-audit'),
    ];
} elseif ($avg_links < 3 && $total_links > 0) {
    $callout = [
        'label' => __('Low linking density', 'gd-audit'),
        'text'  => __('Sampled content averages fewer than three links per post. Add internal references to improve crawlability.', 'gd-audit'),
    ];
}
?>
<div class="wrap gd-audit gd-audit-links">
    <?php include GD_AUDIT_PLUGIN_DIR . 'includes/views/partials/nav-tabs.php'; ?>

    <div class="gd-audit-links__hero">
        <span class="dashicons dashicons-admin-links" aria-hidden="true"></span>
        <div>
            <p class="gd-audit-links__hero-eyebrow"><?php esc_html_e('Media health', 'gd-audit'); ?></p>
            <h1><?php esc_html_e('Links', 'gd-audit'); ?></h1>
            <p>
                <?php
                printf(
                    esc_html__('Scanning %1$s recent %2$s for cross-site linking behavior.', 'gd-audit'),
                    esc_html(number_format_i18n($active_sample)),
                    esc_html($hero_types)
                );
                ?>
            </p>
        </div>
    </div>

    <?php if ($callout) : ?>
        <div class="gd-audit-links__callout" role="status">
            <strong><?php echo esc_html($callout['label']); ?></strong>
            <p><?php echo esc_html($callout['text']); ?></p>
        </div>
    <?php endif; ?>

    <form method="get" class="gd-audit-links__filters" action="<?php echo esc_url(admin_url('admin.php')); ?>">
        <input type="hidden" name="page" value="gd-audit-links" />
        <fieldset>
            <legend><?php esc_html_e('Post types', 'gd-audit'); ?></legend>
            <div class="gd-audit-links__filter-grid">
                <?php foreach ($filterable_types as $slug => $type_obj) : ?>
                    <label>
                        <input type="checkbox" name="post_types[]" value="<?php echo esc_attr($slug); ?>" <?php checked(in_array($slug, $active_types, true)); ?> />
                        <span><?php echo esc_html($type_obj->labels->name); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <label>
            <span><?php esc_html_e('Sample size', 'gd-audit'); ?></span>
            <select name="sample_size">
                <?php foreach ([25, 50, 75, 100, 150, 200] as $size_option) : ?>
                    <option value="<?php echo esc_attr($size_option); ?>" <?php selected($active_sample, $size_option); ?>>
                        <?php echo esc_html(sprintf(_n('%d post', '%d posts', $size_option, 'gd-audit'), $size_option)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="gd-audit-links__filter-actions">
            <button type="submit" class="button button-primary"><?php esc_html_e('Apply filters', 'gd-audit'); ?></button>
            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=gd-audit-links')); ?>"><?php esc_html_e('Reset', 'gd-audit'); ?></a>
        </div>
    </form>

    <section class="gd-audit-links__stat-box" aria-live="polite">
        <header class="gd-audit-links__stat-head">
            <div>
                <span class="gd-audit-links__eyebrow"><?php esc_html_e('Snapshot', 'gd-audit'); ?></span>
                <h2><?php esc_html_e('Link statistics', 'gd-audit'); ?></h2>
            </div>
            <p><?php printf(esc_html__('Based on %s sampled entries.', 'gd-audit'), esc_html(number_format_i18n($scanned_posts))); ?></p>
        </header>

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

    <section class="gd-audit-links__trend-panel">
        <header>
            <h2><?php esc_html_e('External ratio trend', 'gd-audit'); ?></h2>
            <p><?php esc_html_e('Shows the last eight sampled entries from oldest to newest.', 'gd-audit'); ?></p>
        </header>
        <?php if ($trend) : ?>
            <ul class="gd-audit-links__trend-list">
                <?php foreach ($trend as $entry) : ?>
                    <?php
                    $trend_type_obj   = get_post_type_object($entry['post_type']);
                    $trend_type_label = $trend_type_obj ? $trend_type_obj->labels->singular_name : strtoupper($entry['post_type']);
                    ?>
                    <li>
                        <div>
                            <strong><?php echo esc_html($entry['title']); ?></strong>
                            <span class="gd-audit__meta"><?php echo esc_html($entry['date']); ?> • <?php echo esc_html($trend_type_label); ?></span>
                        </div>
                        <div class="gd-audit-links__trend-meter" aria-label="<?php esc_attr_e('External link percentage', 'gd-audit'); ?>">
                            <span class="gd-audit-links__trend-fill" style="width: <?php echo esc_attr($entry['external_pct']); ?>%"></span>
                        </div>
                        <span class="gd-audit-links__trend-value"><?php echo esc_html($entry['external_pct']); ?>%</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p><?php esc_html_e('Not enough recent content to plot a trend yet.', 'gd-audit'); ?></p>
        <?php endif; ?>
    </section>

    <div class="gd-audit__panels">
        <section class="gd-audit__panel">
            <h2><?php esc_html_e('Top linked posts', 'gd-audit'); ?></h2>
            <?php if ($top_posts) : ?>
                <ul class="gd-audit-links__post-cards">
                    <?php foreach ($top_posts as $post_item) : ?>
                        <?php
                        $post_obj   = get_post($post_item['id']);
                        $post_type  = $post_obj ? get_post_type_object($post_obj->post_type) : null;
                        $type_label = $post_type ? $post_type->labels->singular_name : __('Content', 'gd-audit');
                        $post_date  = $post_obj ? get_date_from_gmt($post_obj->post_date_gmt, get_option('date_format')) : '';
                        $media_url  = ($post_obj && has_post_thumbnail($post_obj)) ? get_the_post_thumbnail_url($post_obj, 'medium') : '';
                        $excerpt    = '';
                        if ($post_obj) {
                            $raw_excerpt = has_excerpt($post_obj) ? get_the_excerpt($post_obj) : wp_strip_all_tags($post_obj->post_content);
                            $excerpt     = wp_trim_words($raw_excerpt, 24, '…');
                        }
                        $placeholder_letter = strtoupper(wp_html_excerpt($post_item['title'], 1, '')) ?: '?';
                        ?>
                        <li>
                            <div class="gd-audit-links__post-card-header">
                                <span class="gd-audit-links__chip"><?php echo esc_html($type_label); ?></span>
                                <span class="gd-audit__meta"><?php echo esc_html($post_date); ?></span>
                            </div>
                            <div class="gd-audit-links__post-body">
                                <div class="gd-audit-links__post-media">
                                    <?php if ($media_url) : ?>
                                        <img src="<?php echo esc_url($media_url); ?>" alt="" />
                                    <?php else : ?>
                                        <span class="gd-audit-links__media-placeholder" aria-hidden="true"><?php echo esc_html($placeholder_letter); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <strong><?php echo esc_html($post_item['title']); ?></strong>
                                    <?php if ($excerpt) : ?>
                                        <p class="gd-audit-links__excerpt"><?php echo esc_html($excerpt); ?></p>
                                    <?php endif; ?>
                                    <p class="gd-audit__meta"><?php printf(esc_html__('%s total links', 'gd-audit'), esc_html(number_format_i18n($post_item['count']))); ?></p>
                                    <div class="gd-audit-links__actions">
                                        <?php if (!empty($post_item['edit_url'])) : ?>
                                            <a class="gd-audit-links__action" href="<?php echo esc_url($post_item['edit_url']); ?>"><?php esc_html_e('Edit', 'gd-audit'); ?></a>
                                        <?php endif; ?>
                                        <?php if (!empty($post_item['view_url'])) : ?>
                                            <a class="gd-audit-links__action" href="<?php echo esc_url($post_item['view_url']); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('View', 'gd-audit'); ?></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p><?php esc_html_e('No links detected in the sampled posts.', 'gd-audit'); ?></p>
            <?php endif; ?>
        </section>

        <section class="gd-audit__panel">
            <h2><?php esc_html_e('Top outbound domains', 'gd-audit'); ?></h2>
            <?php if ($top_domains) : ?>
                <ul class="gd-audit-links__domain-list">
                    <?php foreach ($top_domains as $domain) : ?>
                        <?php $initial = strtoupper(substr($domain['domain'], 0, 1)); ?>
                        <li>
                            <span class="gd-audit-links__domain-avatar" aria-hidden="true"><?php echo esc_html($initial); ?></span>
                            <div>
                                <strong><?php echo esc_html($domain['domain']); ?></strong>
                                <span class="gd-audit__meta"><?php esc_html_e('Outbound references', 'gd-audit'); ?></span>
                            </div>
                            <span class="gd-audit__pill"><?php echo esc_html(number_format_i18n($domain['count'])); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p><?php esc_html_e('No outbound domains detected in the sampled posts.', 'gd-audit'); ?></p>
            <?php endif; ?>
        </section>
    </div>
</div>
