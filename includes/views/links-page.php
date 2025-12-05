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
$sample_label  = $active_type_labels ? implode(', ', $active_type_labels) : __('posts', 'gd-audit');
?>
<div class="wrap gd-audit gd-audit-links">
    <?php include GD_AUDIT_PLUGIN_DIR . 'includes/views/partials/nav-tabs.php'; ?>

    <h1 class="gd-audit__section-title"><?php esc_html_e('Links', 'gd-audit'); ?></h1>
    <p class="gd-audit-links__lead">
        <?php
        printf(
            esc_html__('Sampling the most recent %1$s %2$s to compare internal and external linking activity.', 'gd-audit'),
            esc_html(number_format_i18n($active_sample)),
            esc_html($sample_label)
        );
        ?>
    </p>

    <form method="get" class="gd-audit-links__toolbar" action="<?php echo esc_url(admin_url('admin.php')); ?>">
        <input type="hidden" name="page" value="gd-audit-links" />

        <div class="gd-audit-links__toolbar-group">
            <span><?php esc_html_e('Post types', 'gd-audit'); ?></span>
            <div class="gd-audit-links__checkboxes">
                <?php foreach ($filterable_types as $slug => $type_obj) : ?>
                    <label class="gd-audit-links__check">
                        <input type="checkbox" name="post_types[]" value="<?php echo esc_attr($slug); ?>" <?php checked(in_array($slug, $active_types, true)); ?> />
                        <?php echo esc_html($type_obj->labels->name); ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <label class="gd-audit-links__select">
            <span><?php esc_html_e('Sample size', 'gd-audit'); ?></span>
            <select name="sample_size">
                <?php foreach ([25, 50, 75, 100, 150, 200] as $size_option) : ?>
                    <option value="<?php echo esc_attr($size_option); ?>" <?php selected($active_sample, $size_option); ?>>
                        <?php echo esc_html(sprintf(_n('%d post', '%d posts', $size_option, 'gd-audit'), $size_option)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <div class="gd-audit-links__buttons">
            <button type="submit" class="button button-primary"><?php esc_html_e('Apply filters', 'gd-audit'); ?></button>
            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=gd-audit-links')); ?>"><?php esc_html_e('Reset', 'gd-audit'); ?></a>
        </div>
    </form>

    <div class="gd-audit-links__summary-row">
        <section class="gd-audit-card" aria-live="polite">
            <header class="gd-audit-card__header">
                <h2><?php esc_html_e('Snapshot', 'gd-audit'); ?></h2>
                <span class="gd-audit__meta"><?php printf(esc_html__('%s items scanned', 'gd-audit'), esc_html(number_format_i18n($scanned_posts))); ?></span>
            </header>
            <div class="gd-audit__cards">
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
            </div>
            <canvas
                id="gd-audit-links-bars"
                height="180"
                data-bars='<?php echo esc_attr(wp_json_encode([
                    ['label' => __('Items scanned', 'gd-audit'), 'value' => (int) $active_sample],
                    ['label' => __('Posts scanned', 'gd-audit'), 'value' => (int) $scanned_posts],
                    ['label' => __('Total links', 'gd-audit'), 'value' => (int) $total_links],
                    ['label' => __('Avg links / post', 'gd-audit'), 'value' => (float) $avg_links],
                ])); ?>'
            ></canvas>
        </section>

        <section class="gd-audit-card">
            <header class="gd-audit-card__header">
                <h2><?php esc_html_e('Distribution', 'gd-audit'); ?></h2>
            </header>
            <div class="gd-audit-links__progress">
                <div class="gd-audit-progress">
                    <div class="gd-audit-progress__label"><?php esc_html_e('Internal', 'gd-audit'); ?> (<?php echo esc_html($internal_pct); ?>%)</div>
                    <div class="gd-audit-progress__track"><span style="width: <?php echo esc_attr($internal_pct); ?>%"></span></div>
                    <small class="gd-audit__meta"><?php echo esc_html(number_format_i18n($internal)); ?> <?php esc_html_e('links', 'gd-audit'); ?></small>
                </div>
                <div class="gd-audit-progress">
                    <div class="gd-audit-progress__label"><?php esc_html_e('External', 'gd-audit'); ?> (<?php echo esc_html($external_pct); ?>%)</div>
                    <div class="gd-audit-progress__track"><span class="is-danger" style="width: <?php echo esc_attr($external_pct); ?>%"></span></div>
                    <small class="gd-audit__meta"><?php echo esc_html(number_format_i18n($external)); ?> <?php esc_html_e('links', 'gd-audit'); ?></small>
                </div>
            </div>
            <canvas id="gd-audit-links-chart" height="120" data-points="<?php echo esc_attr(wp_json_encode($trend_points)); ?>"></canvas>
            <canvas
                id="gd-audit-links-pie"
                height="180"
                width="180"
                data-pie='<?php echo esc_attr(wp_json_encode([
                    ['label' => __('Internal', 'gd-audit'), 'value' => (int) $internal],
                    ['label' => __('External', 'gd-audit'), 'value' => (int) $external],
                ])); ?>'
            ></canvas>
        </section>
    </div>

    <div class="gd-audit-links__grid">
        <section class="gd-audit-card">
            <header class="gd-audit-card__header">
                <h2><?php esc_html_e('Top linked posts', 'gd-audit'); ?></h2>
            </header>
            <?php if ($top_posts) : ?>
                <table class="widefat fixed striped gd-audit-links__table">
                    <thead>
                        <tr>
                            <th scope="col"><?php esc_html_e('Title', 'gd-audit'); ?></th>
                            <th scope="col" class="column-links"><?php esc_html_e('Links', 'gd-audit'); ?></th>
                            <th scope="col"><?php esc_html_e('Actions', 'gd-audit'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_posts as $post_item) : ?>
                            <?php
                            $post_obj   = get_post($post_item['id']);
                            $post_type  = $post_obj ? get_post_type_object($post_obj->post_type) : null;
                            $type_label = $post_type ? $post_type->labels->singular_name : __('Content', 'gd-audit');
                            $post_date  = $post_obj ? get_date_from_gmt($post_obj->post_date_gmt, get_option('date_format')) : '';
                            $excerpt    = '';
                            if ($post_obj) {
                                $raw_excerpt = has_excerpt($post_obj) ? get_the_excerpt($post_obj) : wp_strip_all_tags($post_obj->post_content);
                                $excerpt     = wp_trim_words($raw_excerpt, 18, '…');
                            }
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($post_item['title']); ?></strong>
                                    <span class="gd-audit__meta"><?php echo esc_html($type_label); ?> • <?php echo esc_html($post_date); ?></span>
                                    <?php if ($excerpt) : ?>
                                        <p class="gd-audit-links__excerpt"><?php echo esc_html($excerpt); ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="column-links">
                                    <span class="gd-audit__pill"><?php echo esc_html(number_format_i18n($post_item['count'])); ?></span>
                                </td>
                                <td class="gd-audit-links__table-actions">
                                    <?php if (!empty($post_item['edit_url'])) : ?>
                                        <a class="button button-small" href="<?php echo esc_url($post_item['edit_url']); ?>"><?php esc_html_e('Edit', 'gd-audit'); ?></a>
                                    <?php endif; ?>
                                    <?php if (!empty($post_item['view_url'])) : ?>
                                        <a class="button button-small" href="<?php echo esc_url($post_item['view_url']); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('View', 'gd-audit'); ?></a>
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

        <section class="gd-audit-card">
            <header class="gd-audit-card__header">
                <h2><?php esc_html_e('Top outbound domains', 'gd-audit'); ?></h2>
            </header>
            <?php if ($top_domains) : ?>
                <table class="widefat fixed striped gd-audit-links__table">
                    <thead>
                        <tr>
                            <th scope="col"><?php esc_html_e('Domain', 'gd-audit'); ?></th>
                            <th scope="col" class="column-links"><?php esc_html_e('Links', 'gd-audit'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_domains as $domain) : ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($domain['domain']); ?></strong>
                                    <span class="gd-audit__meta"><?php esc_html_e('Outbound references', 'gd-audit'); ?></span>
                                </td>
                                <td class="column-links">
                                    <span class="gd-audit__pill"><?php echo esc_html(number_format_i18n($domain['count'])); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e('No outbound domains detected in the sampled posts.', 'gd-audit'); ?></p>
            <?php endif; ?>
        </section>

        <section class="gd-audit-card">
            <header class="gd-audit-card__header">
                <h2><?php esc_html_e('Recent activity', 'gd-audit'); ?></h2>
            </header>
            <?php if ($trend) : ?>
                <table class="widefat fixed striped gd-audit-links__table">
                    <thead>
                        <tr>
                            <th scope="col"><?php esc_html_e('Title', 'gd-audit'); ?></th>
                            <th scope="col" class="column-links"><?php esc_html_e('External %', 'gd-audit'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trend as $entry) : ?>
                            <?php
                            $trend_type_obj   = get_post_type_object($entry['post_type']);
                            $trend_type_label = $trend_type_obj ? $trend_type_obj->labels->singular_name : strtoupper($entry['post_type']);
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($entry['title']); ?></strong>
                                    <span class="gd-audit__meta"><?php echo esc_html($trend_type_label); ?> • <?php echo esc_html($entry['date']); ?></span>
                                </td>
                                <td class="column-links">
                                    <span class="gd-audit__pill"><?php echo esc_html($entry['external_pct']); ?>%</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e('Not enough recent content to show activity yet.', 'gd-audit'); ?></p>
            <?php endif; ?>
        </section>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    renderLinksLineChart();
    renderLinksBarChart();
});

function renderLinksLineChart() {
    var canvas = document.getElementById('gd-audit-links-chart');
    if (!canvas) {
        return;
    }

    var ctx = canvas.getContext('2d');
    var points = JSON.parse(canvas.getAttribute('data-points') || '[]');
    if (!points.length) {
        ctx.font = '12px sans-serif';
        ctx.fillStyle = '#6c757d';
        ctx.fillText('<?php echo esc_js(__('Not enough data for chart', 'gd-audit')); ?>', 10, 40);
        return;
    }

    var width = canvas.width;
    var height = canvas.height;
    var max = Math.max.apply(null, points);
    var min = Math.min.apply(null, points);
    if (max === min) {
        max += 1;
        min -= 1;
    }

    var padding = 20;
    var stepX = (width - (padding * 2)) / Math.max(points.length - 1, 1);
    var range = max - min;

    ctx.clearRect(0, 0, width, height);
    ctx.lineWidth = 2;
    ctx.strokeStyle = '#0d6efd';
    ctx.beginPath();

    points.forEach(function (value, index) {
        var x = padding + (index * stepX);
        var normalized = (value - min) / range;
        var y = height - padding - (normalized * (height - (padding * 2)));
        if (index === 0) {
            ctx.moveTo(x, y);
        } else {
            ctx.lineTo(x, y);
        }
    });

    ctx.stroke();

    ctx.fillStyle = '#0d6efd';
    points.forEach(function (value, index) {
        var x = padding + (index * stepX);
        var normalized = (value - min) / range;
        var y = height - padding - (normalized * (height - (padding * 2)));
        ctx.beginPath();
        ctx.arc(x, y, 3, 0, Math.PI * 2);
        ctx.fill();
    });
}

function renderLinksBarChart() {
    var canvas = document.getElementById('gd-audit-links-bars');
    if (!canvas) {
        return;
    }

    var ctx = canvas.getContext('2d');
    var bars = JSON.parse(canvas.getAttribute('data-bars') || '[]');
    if (!bars.length) {
        ctx.font = '12px sans-serif';
        ctx.fillStyle = '#6c757d';
        ctx.fillText('<?php echo esc_js(__('No snapshot data available', 'gd-audit')); ?>', 10, 40);
        return;
    }

    var width = canvas.width;
    var height = canvas.height;
    var padding = 24;
    var barWidth = ((width - padding * 2) / bars.length) * 0.6;
    var maxValue = Math.max.apply(null, bars.map(function (bar) { return bar.value; }));
    if (maxValue <= 0) {
        ctx.font = '12px sans-serif';
        ctx.fillStyle = '#6c757d';
        ctx.fillText('<?php echo esc_js(__('Values too small to chart', 'gd-audit')); ?>', 10, 40);
        return;
    }

    ctx.clearRect(0, 0, width, height);
    ctx.fillStyle = '#0d6efd';
    ctx.textAlign = 'center';
    ctx.font = '12px sans-serif';

    bars.forEach(function (bar, index) {
        var x = padding + (index * ((width - padding * 2) / bars.length)) + (((width - padding * 2) / bars.length) - barWidth) / 2;
        var barHeight = (bar.value / maxValue) * (height - padding * 2);
        var y = height - padding - barHeight;

        ctx.fillStyle = '#0d6efd';

        ctx.fillRect(x, y, barWidth, barHeight);

        ctx.fillStyle = '#000';
        var displayValue = Number.isInteger(bar.value) ? bar.value : bar.value.toFixed(1);
        ctx.fillText(displayValue, x + (barWidth / 2), y - 6);
        ctx.fillText(bar.label, x + (barWidth / 2), height - padding + 14);
    });
}
</script>
</script>
