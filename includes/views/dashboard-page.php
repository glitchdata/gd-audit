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

    <section class="gd-audit__cards">
        <?php foreach ($status_totals as $item) : ?>
            <article class="gd-audit__card">
                <p class="gd-audit__card-label"><?php echo esc_html($item['label']); ?></p>
                <p class="gd-audit__card-value"><?php echo esc_html(number_format_i18n($item['count'])); ?></p>
            </article>
        <?php endforeach; ?>
    </section>

    <div class="gd-audit__panels">
        <section class="gd-audit__panel">
            <h2><?php esc_html_e('Publish trend (last 7 days)', 'gd-audit'); ?></h2>
            <ul class="gd-audit__sparkline">
                <?php foreach ($daily_activity as $point) : ?>
                    <li>
                        <span><?php echo esc_html(get_date_from_gmt($point['day'] . ' 00:00:00', get_option('date_format'))); ?></span>
                        <strong><?php echo esc_html($point['total']); ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="gd-audit__panel">
            <h2><?php esc_html_e('Top authors (last 30 days)', 'gd-audit'); ?></h2>
            <?php if ($top_authors) : ?>
                <ol class="gd-audit__list">
                    <?php foreach ($top_authors as $author) : ?>
                        <li>
                            <span><?php echo esc_html($author['name']); ?></span>
                            <strong><?php echo esc_html(number_format_i18n($author['count'])); ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else : ?>
                <p><?php esc_html_e('No author activity recorded for this window.', 'gd-audit'); ?></p>
            <?php endif; ?>
        </section>
    </div>

    <div class="gd-audit__panels">
        <section class="gd-audit__panel">
            <h2><?php esc_html_e('Recent published posts', 'gd-audit'); ?></h2>
            <?php if ($recent_posts) : ?>
                <ul class="gd-audit__recent-posts">
                    <?php foreach ($recent_posts as $post_item) : ?>
                        <li>
                            <div>
                                <a href="<?php echo esc_url($post_item['url']); ?>">
                                    <?php echo esc_html($post_item['title']); ?>
                                </a>
                                <span class="gd-audit__meta">
                                    <?php
                                    printf(
                                        /* translators: 1: author name, 2: publish date */
                                        esc_html__('by %1$s on %2$s', 'gd-audit'),
                                        esc_html($post_item['author']),
                                        esc_html($post_item['date'])
                                    );
                                    ?>
                                </span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p><?php esc_html_e('No recent posts to display.', 'gd-audit'); ?></p>
            <?php endif; ?>
        </section>
    </div>
</div>
