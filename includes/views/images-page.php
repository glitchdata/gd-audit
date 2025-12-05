<?php
/**
 * Images analytics page.
 */

if (!defined('ABSPATH')) {
    exit;
}

$total_images = $overview['total'];
$status_counts = $overview['status'];
$mime_counts = array_slice($overview['mime_counts'], 0, 5);
$library_url = admin_url('upload.php');
?>
<div class="wrap gd-audit gd-audit-images">
    <?php include GD_AUDIT_PLUGIN_DIR . 'includes/views/partials/nav-tabs.php'; ?>

    <h1 class="gd-audit__section-title"><?php esc_html_e('Images', 'gd-audit'); ?></h1>

    <section class="gd-audit__cards">
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Total images', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html(number_format_i18n($total_images)); ?></p>
        </article>
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Library size', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html(size_format($overview['total_size'], 2)); ?></p>
        </article>
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Average file size', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html(size_format($overview['avg_size'], 2)); ?></p>
        </article>
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Manage library', 'gd-audit'); ?></p>
            <p><a href="<?php echo esc_url($library_url); ?>" class="button button-secondary"><?php esc_html_e('Open Media Library', 'gd-audit'); ?></a></p>
        </article>
    </section>

    <div class="gd-audit__panels">
        <section class="gd-audit__panel">
            <h2><?php esc_html_e('Status breakdown', 'gd-audit'); ?></h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Status', 'gd-audit'); ?></th>
                        <th><?php esc_html_e('Images', 'gd-audit'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($status_counts as $status => $count) : ?>
                        <tr>
                            <td><?php echo esc_html(ucfirst($status)); ?></td>
                            <td><?php echo esc_html(number_format_i18n($count)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="gd-audit__panel">
            <h2><?php esc_html_e('Top file types', 'gd-audit'); ?></h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('MIME', 'gd-audit'); ?></th>
                        <th><?php esc_html_e('Images', 'gd-audit'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mime_counts as $mime) : ?>
                        <tr>
                            <td><?php echo esc_html($mime['mime']); ?></td>
                            <td><?php echo esc_html(number_format_i18n($mime['count'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>

    <div class="gd-audit__panels">
        <section class="gd-audit__panel">
            <h2><?php esc_html_e('Recent uploads', 'gd-audit'); ?></h2>
            <?php if ($recent_images) : ?>
                <ul class="gd-audit__media-grid">
                    <?php foreach ($recent_images as $image) : ?>
                        <li>
                            <?php if (!empty($image['thumbnail'])) : ?>
                                <img src="<?php echo esc_url($image['thumbnail']); ?>" alt="<?php echo esc_attr($image['title']); ?>" />
                            <?php endif; ?>
                            <div>
                                <strong><?php echo esc_html($image['title']); ?></strong>
                                <span class="gd-audit__meta">
                                    <?php
                                    printf(
                                        /* translators: 1: author name, 2: date */
                                        esc_html__('%1$s • %2$s', 'gd-audit'),
                                        esc_html($image['author']),
                                        esc_html($image['date'])
                                    );
                                    ?>
                                </span>
                                <span class="gd-audit__meta"><?php echo esc_html($image['mime']); ?> • <?php echo esc_html(size_format($image['size'], 2)); ?></span>
                            </div>
                            <a href="<?php echo esc_url($image['edit_url']); ?>" class="gd-audit__link"><?php esc_html_e('Edit', 'gd-audit'); ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p><?php esc_html_e('No image uploads detected.', 'gd-audit'); ?></p>
            <?php endif; ?>
        </section>
    </div>
</div>
