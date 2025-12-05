<?php
/**
 * Aggregates WordPress post analytics for the GD Audit dashboard.
 */

if (!defined('ABSPATH')) {
    exit;
}

class GDAuditAnalytics {
    /**
     * Returns a structured array of key post status totals.
     */
    public function get_post_status_totals($post_type = 'post') {
        $counts   = wp_count_posts($post_type);
        $statuses = [
            'publish' => __('Published', 'gd-audit'),
            'draft'   => __('Drafts', 'gd-audit'),
            'pending' => __('Pending', 'gd-audit'),
            'future'  => __('Scheduled', 'gd-audit'),
            'private' => __('Private', 'gd-audit'),
        ];

        $totals = [];
        foreach ($statuses as $status => $label) {
            $totals[] = [
                'status' => $status,
                'label'  => $label,
                'count'  => isset($counts->$status) ? (int) $counts->$status : 0,
            ];
        }

        return $totals;
    }

    /**
     * Returns the number of published posts per day over the provided window.
     */
    public function get_daily_post_activity($days = 7, $post_type = 'post') {
        global $wpdb;

        $days       = max(1, (int) $days);
        $now        = time();
        $cutoff_gmt = gmdate('Y-m-d H:i:s', $now - ($days * DAY_IN_SECONDS));

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE(post_date_gmt) as day, COUNT(*) as total
                 FROM {$wpdb->posts}
                 WHERE post_type = %s AND post_status = 'publish' AND post_date_gmt >= %s
                 GROUP BY day ORDER BY day ASC",
                $post_type,
                $cutoff_gmt
            )
        );

        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = gmdate('Y-m-d', $now - ($i * DAY_IN_SECONDS));
            $series[$day] = 0;
        }

        foreach ($results as $row) {
            $day = $row->day;
            if (isset($series[$day])) {
                $series[$day] = (int) $row->total;
            }
        }

        $prepared = [];
        foreach ($series as $day => $total) {
            $prepared[] = [
                'day'   => $day,
                'total' => $total,
            ];
        }

        return $prepared;
    }

    /**
     * Returns the top authors based on published posts during the provided window.
     */
    public function get_top_authors($days = 30, $limit = 5, $post_type = 'post') {
        global $wpdb;

        $days       = max(1, (int) $days);
        $limit      = max(1, (int) $limit);
        $cutoff_gmt = gmdate('Y-m-d H:i:s', time() - ($days * DAY_IN_SECONDS));

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT post_author, COUNT(*) as total
                 FROM {$wpdb->posts}
                 WHERE post_type = %s AND post_status = 'publish' AND post_date_gmt >= %s
                 GROUP BY post_author
                 ORDER BY total DESC
                 LIMIT %d",
                $post_type,
                $cutoff_gmt,
                $limit
            )
        );

        $authors = [];
        foreach ($rows as $row) {
            $user = get_userdata($row->post_author);
            $authors[] = [
                'user_id' => (int) $row->post_author,
                'name'    => $user ? $user->display_name : __('Unknown', 'gd-audit'),
                'count'   => (int) $row->total,
            ];
        }

        return $authors;
    }

    /**
     * Returns a list of the most recent published posts.
     */
    public function get_recent_published_posts($limit = 5, $post_type = 'post') {
        $limit = max(1, (int) $limit);

        $posts = get_posts([
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'no_found_rows'  => true,
            'suppress_filters' => false,
        ]);

        $items = [];
        foreach ($posts as $post) {
            $edit_link = get_edit_post_link($post->ID, '');
            $items[] = [
                'id'     => $post->ID,
                'title'  => get_the_title($post),
                'author' => get_the_author_meta('display_name', $post->post_author),
                'url'    => $edit_link ? $edit_link : get_permalink($post),
                'date'   => get_date_from_gmt($post->post_date_gmt, get_option('date_format') . ' ' . get_option('time_format')),
            ];
        }

        return $items;
    }
}
