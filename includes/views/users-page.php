<?php
/**
 * User analytics overview.
 */

if (!defined('ABSPATH')) {
    exit;
}

$manage_url = admin_url('users.php');
$role_count = count($roles);
?>
<div class="wrap gd-audit gd-audit-users">
    <?php include GD_AUDIT_PLUGIN_DIR . 'includes/views/partials/nav-tabs.php'; ?>

    <h1 class="gd-audit__section-title"><?php esc_html_e('Users', 'gd-audit'); ?></h1>

    <section class="gd-audit__cards">
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Total users', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html(number_format_i18n($total_users)); ?></p>
        </article>
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Administrators', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html(number_format_i18n($admin_count)); ?></p>
        </article>
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Distinct roles', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html(number_format_i18n($role_count)); ?></p>
        </article>
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Registrations (7d)', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html(number_format_i18n($recent_registrations)); ?></p>
        </article>
    </section>

    <div class="gd-audit__panels">
        <section class="gd-audit__panel">
            <h2><?php esc_html_e('Registration trend (last 7 days)', 'gd-audit'); ?></h2>
            <ul class="gd-audit__sparkline">
                <?php foreach ($registration_trend as $point) : ?>
                    <li>
                        <span><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($point['day']))); ?></span>
                        <strong><?php echo esc_html($point['total']); ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="gd-audit__panel">
            <h2><?php esc_html_e('Role distribution', 'gd-audit'); ?></h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Role', 'gd-audit'); ?></th>
                        <th><?php esc_html_e('Users', 'gd-audit'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $role) : ?>
                        <tr>
                            <td><?php echo esc_html($role['label']); ?></td>
                            <td><?php echo esc_html(number_format_i18n($role['count'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>

    <div class="gd-audit__panels">
        <section class="gd-audit__panel">
            <h2><?php esc_html_e('Recent registrations', 'gd-audit'); ?></h2>
            <?php if ($recent_users) : ?>
                <ul class="gd-audit__recent-users">
                    <?php foreach ($recent_users as $user) : ?>
                        <li>
                            <div>
                                <strong><?php echo esc_html($user['name']); ?></strong>
                                <span class="gd-audit__meta">
                                    <?php
                                    printf(
                                        /* translators: 1: username, 2: registration date */
                                        esc_html__('@%1$s • %2$s', 'gd-audit'),
                                        esc_html($user['login']),
                                        esc_html($user['registered'])
                                    );
                                    ?>
                                </span>
                                <?php if (!empty($user['email'])) : ?>
                                    <span class="gd-audit__meta"><?php echo esc_html($user['email']); ?></span>
                                <?php endif; ?>
                            </div>
                            <a href="<?php echo esc_url($user['edit_url']); ?>" class="gd-audit__link"><?php esc_html_e('Edit', 'gd-audit'); ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p><?php esc_html_e('No recent user registrations.', 'gd-audit'); ?></p>
            <?php endif; ?>
            <p><a href="<?php echo esc_url($manage_url); ?>" class="button button-secondary"><?php esc_html_e('Manage users', 'gd-audit'); ?></a></p>
        </section>
    </div>
</div>
