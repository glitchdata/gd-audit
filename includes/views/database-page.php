<?php
/**
 * Database overview page.
 */

if (!defined('ABSPATH')) {
    exit;
}

$total_size  = $summary['data_size'] + $summary['index_size'];
$date_format = get_option('date_format') . ' ' . get_option('time_format');
?>
<div class="wrap gd-audit gd-audit-database">
    <?php include GD_AUDIT_PLUGIN_DIR . 'includes/views/partials/nav-tabs.php'; ?>

    <h1 class="gd-audit__section-title"><?php esc_html_e('Database', 'gd-audit'); ?></h1>

    <section class="gd-audit__cards">
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Tables', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html(number_format_i18n($summary['total_tables'])); ?></p>
            <p class="gd-audit__meta"><?php printf(esc_html__('%s WordPress tables', 'gd-audit'), esc_html(number_format_i18n($summary['wp_tables']))); ?></p>
        </article>
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Total rows', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html(number_format_i18n($summary['total_rows'])); ?></p>
        </article>
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Data size', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html(size_format($summary['data_size'], 2)); ?></p>
        </article>
        <article class="gd-audit__card">
            <p class="gd-audit__card-label"><?php esc_html_e('Index size', 'gd-audit'); ?></p>
            <p class="gd-audit__card-value"><?php echo esc_html(size_format($summary['index_size'], 2)); ?></p>
            <p class="gd-audit__meta"><?php printf(esc_html__('Total storage %s', 'gd-audit'), esc_html(size_format($total_size, 2))); ?></p>
        </article>
    </section>

    <hr class="gd-audit__divider" />

    <table class="wp-list-table widefat fixed striped gd-audit__table">
        <thead>
            <tr>
                <th><?php esc_html_e('Table', 'gd-audit'); ?></th>
                <th><?php esc_html_e('Engine', 'gd-audit'); ?></th>
                <th><?php esc_html_e('Rows', 'gd-audit'); ?></th>
                <th><?php esc_html_e('Data', 'gd-audit'); ?></th>
                <th><?php esc_html_e('Index', 'gd-audit'); ?></th>
                <th><?php esc_html_e('Total', 'gd-audit'); ?></th>
                <th><?php esc_html_e('Collation', 'gd-audit'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($tables) : ?>
                <?php foreach ($tables as $table) : ?>
                    <?php
                    $total_bytes       = $table['data_length'] + $table['index_length'];
                    $created_timestamp = !empty($table['create_time']) ? strtotime($table['create_time']) : false;
                    $updated_timestamp = !empty($table['update_time']) ? strtotime($table['update_time']) : false;
                    $checked_timestamp = !empty($table['check_time']) ? strtotime($table['check_time']) : false;
                    $created_display   = $created_timestamp ? wp_date($date_format, $created_timestamp) : __('Unknown', 'gd-audit');
                    $updated_display   = $updated_timestamp ? wp_date($date_format, $updated_timestamp) : __('Unknown', 'gd-audit');
                    $checked_display   = $checked_timestamp ? wp_date($date_format, $checked_timestamp) : __('Never', 'gd-audit');
                    $row_format        = !empty($table['row_format']) ? $table['row_format'] : __('Unknown', 'gd-audit');
                    $avg_row_length    = $table['avg_row_length'] > 0 ? size_format($table['avg_row_length'], 2) : __('N/A', 'gd-audit');
                    $detail_payload    = [
                        'name'          => $table['name'],
                        'subtitle'      => !empty($table['is_wp_table']) ? __('WordPress table', 'gd-audit') : __('Custom table', 'gd-audit'),
                        'engine'        => $table['engine'] ? $table['engine'] : __('Unknown', 'gd-audit'),
                        'collation'     => $table['collation'] ? $table['collation'] : __('Unknown', 'gd-audit'),
                        'rows'          => number_format_i18n($table['rows']),
                        'dataSize'      => size_format($table['data_length'], 2),
                        'indexSize'     => size_format($table['index_length'], 2),
                        'totalSize'     => size_format($total_bytes, 2),
                        'freeSpace'     => size_format($table['data_free'], 2),
                        'autoIncrement' => number_format_i18n($table['auto_increment']),
                        'rowFormat'     => $row_format,
                        'avgRowLength'  => $avg_row_length,
                        'created'       => $created_display,
                        'updated'       => $updated_display,
                        'checked'       => $checked_display,
                        'comment'       => isset($table['comment']) ? $table['comment'] : '',
                    ];
                    $detail_json = wp_json_encode($detail_payload);
                    if (false === $detail_json) {
                        $detail_json = '{}';
                    }
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($table['name']); ?></strong>
                            <p class="gd-audit__meta">
                                <?php if (!empty($table['is_wp_table'])) : ?>
                                    <span class="gd-audit__badge is-active"><?php esc_html_e('WP', 'gd-audit'); ?></span>
                                <?php else : ?>
                                    <span class="gd-audit__badge is-inactive"><?php esc_html_e('Custom', 'gd-audit'); ?></span>
                                <?php endif; ?>
                            </p>
                            <p class="gd-audit__table-actions">
                                <button type="button"
                                    class="gd-audit__table-details-trigger button button-link"
                                    data-table-details="<?php echo esc_attr($detail_json); ?>"
                                    aria-haspopup="dialog">
                                    <?php esc_html_e('View details', 'gd-audit'); ?>
                                </button>
                            </p>
                        </td>
                        <td><?php echo esc_html($table['engine']); ?></td>
                        <td><?php echo esc_html(number_format_i18n($table['rows'])); ?></td>
                        <td>
                            <?php echo esc_html(size_format($table['data_length'], 2)); ?>
                            <?php if ($table['data_free'] > 0) : ?>
                                <p class="gd-audit__meta"><?php printf(esc_html__('Free: %s', 'gd-audit'), esc_html(size_format($table['data_free'], 2))); ?></p>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo esc_html(size_format($table['index_length'], 2)); ?>
                            <?php if ($table['index_length'] <= 0) : ?>
                                <p class="gd-audit__meta"><?php esc_html_e('No indexes', 'gd-audit'); ?></p>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html(size_format($total_bytes, 2)); ?></td>
                        <td><?php echo esc_html($table['collation']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="7"><?php esc_html_e('No tables found or insufficient permissions.', 'gd-audit'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div id="gd-audit-table-modal" class="gd-audit-modal" aria-hidden="true" hidden>
        <div class="gd-audit-modal__backdrop" data-action="close"></div>
        <div class="gd-audit-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="gd-audit-table-modal-title">
            <button type="button" class="gd-audit-modal__close" data-action="close" aria-label="<?php esc_attr_e('Close details window', 'gd-audit'); ?>">&times;</button>
            <h2 id="gd-audit-table-modal-title" class="gd-audit-modal__title"><?php esc_html_e('Table details', 'gd-audit'); ?></h2>
            <p class="gd-audit-modal__subtitle" data-field="subtitle"></p>
            <div class="gd-audit-modal__grid">
                <div>
                    <p class="gd-audit__detail-label"><?php esc_html_e('Engine', 'gd-audit'); ?></p>
                    <p class="gd-audit__detail-value" data-field="engine">—</p>
                </div>
                <div>
                    <p class="gd-audit__detail-label"><?php esc_html_e('Collation', 'gd-audit'); ?></p>
                    <p class="gd-audit__detail-value" data-field="collation">—</p>
                </div>
                <div>
                    <p class="gd-audit__detail-label"><?php esc_html_e('Rows', 'gd-audit'); ?></p>
                    <p class="gd-audit__detail-value" data-field="rows">—</p>
                </div>
                <div>
                    <p class="gd-audit__detail-label"><?php esc_html_e('Data size', 'gd-audit'); ?></p>
                    <p class="gd-audit__detail-value" data-field="dataSize">—</p>
                </div>
                <div>
                    <p class="gd-audit__detail-label"><?php esc_html_e('Index size', 'gd-audit'); ?></p>
                    <p class="gd-audit__detail-value" data-field="indexSize">—</p>
                </div>
                <div>
                    <p class="gd-audit__detail-label"><?php esc_html_e('Total size', 'gd-audit'); ?></p>
                    <p class="gd-audit__detail-value" data-field="totalSize">—</p>
                </div>
                <div>
                    <p class="gd-audit__detail-label"><?php esc_html_e('Free space', 'gd-audit'); ?></p>
                    <p class="gd-audit__detail-value" data-field="freeSpace">—</p>
                </div>
                <div>
                    <p class="gd-audit__detail-label"><?php esc_html_e('Auto increment', 'gd-audit'); ?></p>
                    <p class="gd-audit__detail-value" data-field="autoIncrement">—</p>
                </div>
                <div>
                    <p class="gd-audit__detail-label"><?php esc_html_e('Row format', 'gd-audit'); ?></p>
                    <p class="gd-audit__detail-value" data-field="rowFormat">—</p>
                </div>
                <div>
                    <p class="gd-audit__detail-label"><?php esc_html_e('Average row length', 'gd-audit'); ?></p>
                    <p class="gd-audit__detail-value" data-field="avgRowLength">—</p>
                </div>
                <div>
                    <p class="gd-audit__detail-label"><?php esc_html_e('Created at', 'gd-audit'); ?></p>
                    <p class="gd-audit__detail-value" data-field="created">—</p>
                </div>
                <div>
                    <p class="gd-audit__detail-label"><?php esc_html_e('Updated at', 'gd-audit'); ?></p>
                    <p class="gd-audit__detail-value" data-field="updated">—</p>
                </div>
                <div>
                    <p class="gd-audit__detail-label"><?php esc_html_e('Last checked', 'gd-audit'); ?></p>
                    <p class="gd-audit__detail-value" data-field="checked">—</p>
                </div>
            </div>
            <div class="gd-audit-modal__note" data-field="comment-wrapper" hidden>
                <p class="gd-audit__detail-label"><?php esc_html_e('Table comment', 'gd-audit'); ?></p>
                <p class="gd-audit__detail-value" data-field="comment"></p>
            </div>
        </div>
    </div>

    <script>
    (function() {
        const modal = document.getElementById('gd-audit-table-modal');
        if (!modal) {
            return;
        }

        const titleEl = modal.querySelector('#gd-audit-table-modal-title');
        const subtitleEl = modal.querySelector('[data-field="subtitle"]');
        const commentWrapper = modal.querySelector('[data-field="comment-wrapper"]');
        const commentField = modal.querySelector('[data-field="comment"]');
        const fieldNames = ['engine', 'collation', 'rows', 'dataSize', 'indexSize', 'totalSize', 'freeSpace', 'autoIncrement', 'rowFormat', 'avgRowLength', 'created', 'updated', 'checked'];
        const fields = {};

        fieldNames.forEach(function(key) {
            fields[key] = modal.querySelector('[data-field="' + key + '"]');
        });

        const closeElements = modal.querySelectorAll('[data-action="close"]');
        let lastActiveElement = null;

        function closeModal() {
            modal.hidden = true;
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('gd-audit-modal-open');
            if (lastActiveElement && typeof lastActiveElement.focus === 'function') {
                lastActiveElement.focus({ preventScroll: true });
            }
        }

        function openModal(data) {
            lastActiveElement = document.activeElement;
            modal.hidden = false;
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('gd-audit-modal-open');

            if (titleEl) {
                titleEl.textContent = data.name || '<?php echo esc_js(__('Table details', 'gd-audit')); ?>';
            }

            if (subtitleEl) {
                subtitleEl.textContent = data.subtitle || '';
                subtitleEl.style.display = data.subtitle ? '' : 'none';
            }

            fieldNames.forEach(function(key) {
                if (!fields[key]) {
                    return;
                }
                const value = data[key] && data[key] !== '' ? data[key] : '—';
                fields[key].textContent = value;
            });

            if (commentWrapper && commentField) {
                if (data.comment) {
                    commentWrapper.hidden = false;
                    commentField.textContent = data.comment;
                } else {
                    commentWrapper.hidden = true;
                    commentField.textContent = '';
                }
            }

            const focusTarget = modal.querySelector('[data-action="close"]');
            if (focusTarget) {
                focusTarget.focus({ preventScroll: true });
            }
        }

        document.addEventListener('click', function(event) {
            const trigger = event.target.closest('.gd-audit__table-details-trigger');
            if (!trigger) {
                return;
            }

            const payload = trigger.getAttribute('data-table-details');
            if (!payload) {
                return;
            }

            event.preventDefault();

            try {
                const data = JSON.parse(payload);
                openModal(data);
            } catch (error) {
                if (window.console && console.error) {
                    console.error('GD Audit: invalid table payload', error);
                }
            }
        });

        closeElements.forEach(function(element) {
            element.addEventListener('click', function(event) {
                event.preventDefault();
                closeModal();
            });
        });

        modal.addEventListener('click', function(event) {
            if (event.target.classList.contains('gd-audit-modal__backdrop')) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && !modal.hidden) {
                closeModal();
            }
        });
    })();
    </script>
</div>
