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

    /**
     * Returns totals and role distribution for site users.
     */
    public function get_user_role_distribution() {
        $counts     = count_users();
        $roles      = $counts['avail_roles'] ?? [];
        $formatted  = [];

        $wp_roles = wp_roles();

        foreach ($roles as $role => $count) {
            $label = isset($wp_roles->roles[$role]['name']) ? $wp_roles->roles[$role]['name'] : ucwords(str_replace('_', ' ', $role));
            $formatted[] = [
                'role'  => $role,
                'label' => translate_user_role($label),
                'count' => (int) $count,
            ];
        }

        usort($formatted, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return [
            'total' => isset($counts['total_users']) ? (int) $counts['total_users'] : 0,
            'roles' => $formatted,
        ];
    }

    /**
     * Returns recently registered users with meta details.
     */
    public function get_recent_users($limit = 5) {
        $limit = max(1, (int) $limit);

        $users = get_users([
            'number'  => $limit,
            'orderby' => 'registered',
            'order'   => 'DESC',
            'fields'  => ['ID', 'user_login', 'display_name', 'user_registered', 'user_email'],
        ]);

        $items = [];
        foreach ($users as $user) {
            $items[] = [
                'id'        => $user->ID,
                'name'      => $user->display_name ?: $user->user_login,
                'login'     => $user->user_login,
                'email'     => $user->user_email,
                'registered'=> get_date_from_gmt($user->user_registered, get_option('date_format') . ' ' . get_option('time_format')),
                'edit_url'  => get_edit_user_link($user->ID),
            ];
        }

        return $items;
    }

    /**
     * Returns counts of new registrations over a time window.
     */
    public function get_user_registration_trend($days = 7) {
        global $wpdb;

        $days = max(1, (int) $days);
        $now  = time();
        $cutoff = gmdate('Y-m-d H:i:s', $now - ($days * DAY_IN_SECONDS));

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE(user_registered) as day, COUNT(*) as total
                 FROM {$wpdb->users}
                 WHERE user_registered >= %s
                 GROUP BY day
                 ORDER BY day ASC",
                $cutoff
            )
        );

        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = gmdate('Y-m-d', $now - ($i * DAY_IN_SECONDS));
            $series[$day] = 0;
        }

        foreach ($rows as $row) {
            if (isset($series[$row->day])) {
                $series[$row->day] = (int) $row->total;
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
     * Aggregates link metrics from recently published content.
     */
    public function get_link_analytics($args = []) {
        $defaults = [
            'post_types'  => ['post', 'page'],
            'sample_size' => 75,
        ];

        $args['post_types'] = isset($args['post_types']) ? (array) $args['post_types'] : [];
        $args   = wp_parse_args($args, $defaults);

        $post_types = $this->sanitize_post_types($args['post_types']);
        if (!$post_types) {
            $post_types = ['post'];
        }

        $sample = $this->scan_linked_posts($post_types, (int) $args['sample_size']);

        $overview = [
            'scanned'  => $sample['scanned'],
            'total'    => $sample['total_links'],
            'internal' => $sample['internal_links'],
            'external' => $sample['external_links'],
        ];

        $top_posts = [];
        if ($sample['post_link_counts']) {
            arsort($sample['post_link_counts']);
            $post_ids = array_slice(array_keys($sample['post_link_counts']), 0, 5);
            foreach ($post_ids as $post_id) {
                $post = get_post($post_id);
                if (!$post) {
                    continue;
                }

                $top_posts[] = [
                    'id'       => $post_id,
                    'title'    => get_the_title($post),
                    'count'    => $sample['post_link_counts'][$post_id],
                    'edit_url' => get_edit_post_link($post_id, ''),
                    'view_url' => get_permalink($post_id),
                ];
            }
        }

        $top_domains = [];
        if ($sample['domains']) {
            arsort($sample['domains']);
            foreach (array_slice($sample['domains'], 0, 5, true) as $domain => $count) {
                $top_domains[] = [
                    'domain' => $domain,
                    'count'  => $count,
                ];
            }
        }

        $trend = [];
        if ($sample['post_breakdown']) {
            $recent = array_slice($sample['post_breakdown'], 0, 8);
            $recent = array_reverse($recent); // oldest first for trend line
            foreach ($recent as $entry) {
                $percent_external = $entry['total'] > 0 ? round(($entry['external'] / $entry['total']) * 100) : 0;
                $trend[] = [
                    'id'       => $entry['post_id'],
                    'title'    => $entry['title'],
                    'date'     => $entry['date'],
                    'external_pct' => $percent_external,
                    'total'    => $entry['total'],
                    'post_type'=> $entry['post_type'],
                ];
            }
        }

        $type_labels = [];
        $type_objects = get_post_types([], 'objects');
        foreach ($post_types as $type) {
            if (isset($type_objects[$type])) {
                $type_labels[] = $type_objects[$type]->labels->name;
            } else {
                $type_labels[] = ucfirst($type);
            }
        }

        return [
            'overview'      => $overview,
            'top_posts'     => $top_posts,
            'top_domains'   => $top_domains,
            'trend'         => $trend,
            'post_types'    => $post_types,
            'post_type_labels' => $type_labels,
            'sample_size'   => (int) $args['sample_size'],
        ];
    }

    /**
     * Provides aggregate stats for image attachments.
     */
    public function get_image_overview() {
        global $wpdb;

        $status_rows = $wpdb->get_results(
            "SELECT post_status, COUNT(*) as total
             FROM {$wpdb->posts}
             WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%'
             GROUP BY post_status",
            OBJECT_K
        );

        $mime_rows = $wpdb->get_results(
            "SELECT post_mime_type, COUNT(*) as total
             FROM {$wpdb->posts}
             WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%'
             GROUP BY post_mime_type
             ORDER BY total DESC",
            ARRAY_A
        );

        $summary = [
            'total'        => 0,
            'status'       => [],
            'mime_counts'  => [],
            'total_size'   => 0,
            'avg_size'     => 0,
        ];

        if ($status_rows) {
            foreach ($status_rows as $status => $row) {
                $summary['status'][$status] = (int) $row->total;
                $summary['total']          += (int) $row->total;
            }
        }

        if ($mime_rows) {
            foreach ($mime_rows as $row) {
                $summary['mime_counts'][] = [
                    'mime'  => $row['post_mime_type'],
                    'label' => strtoupper(str_replace('image/', '', $row['post_mime_type'])),
                    'count' => (int) $row['total'],
                ];
            }
        }

        $summary['total_size'] = $this->calculate_image_library_size();
        if ($summary['total'] > 0) {
            $summary['avg_size'] = (int) ($summary['total_size'] / $summary['total']);
        }

        return $summary;
    }

    /**
     * Returns recently uploaded images with sizes.
     */
    public function get_recent_images($limit = 6) {
        $limit = max(1, (int) $limit);

        $attachments = get_posts([
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => $limit,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post_status'    => 'inherit',
            'no_found_rows'  => true,
        ]);

        $items = [];
        foreach ($attachments as $attachment) {
            $size_bytes = $this->get_attachment_size($attachment->ID);
            $items[] = [
                'id'        => $attachment->ID,
                'title'     => get_the_title($attachment),
                'author'    => get_the_author_meta('display_name', $attachment->post_author),
                'date'      => get_date_from_gmt($attachment->post_date_gmt, get_option('date_format') . ' ' . get_option('time_format')),
                'edit_url'  => get_edit_post_link($attachment->ID, ''),
                'mime'      => $attachment->post_mime_type,
                'size'      => $size_bytes,
                'thumbnail' => wp_get_attachment_image_url($attachment->ID, 'thumbnail'),
            ];
        }

        return $items;
    }

    /**
     * Computes the aggregate size of all image attachments.
     */
    private function calculate_image_library_size() {
        global $wpdb;

        $rows = $wpdb->get_col(
            "SELECT pm.meta_value
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE pm.meta_key = '_wp_attachment_metadata'
             AND p.post_type = 'attachment'
             AND p.post_mime_type LIKE 'image/%'"
        );

        $total = 0;
        if ($rows) {
            foreach ($rows as $meta_value) {
                $meta = maybe_unserialize($meta_value);
                if (is_array($meta) && isset($meta['filesize'])) {
                    $total += (int) $meta['filesize'];
                }
            }
        }

        return $total;
    }

    /**
     * Builds a snapshot of links contained in recent content.
     */
    private function scan_linked_posts(array $post_types, $sample_size) {
        $sample_size = max(1, (int) $sample_size);

        $posts = get_posts([
            'post_type'      => $post_types,
            'post_status'    => 'publish',
            'posts_per_page' => $sample_size,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'no_found_rows'  => true,
        ]);

        $stats = [
            'scanned'          => count($posts),
            'total_links'      => 0,
            'internal_links'   => 0,
            'external_links'   => 0,
            'post_link_counts' => [],
            'domains'          => [],
            'post_breakdown'   => [],
        ];

        if (!$posts) {
            return $stats;
        }

        foreach ($posts as $post) {
            $links = $this->extract_links_from_content($post->post_content);
            if (!$links) {
                continue;
            }

            $post_internal = 0;
            $post_external = 0;

            foreach ($links as $href) {
                $stats['total_links']++;
                if ($this->is_internal_url($href)) {
                    $stats['internal_links']++;
                    $post_internal++;
                } else {
                    $stats['external_links']++;
                    $post_external++;
                    $domain = $this->normalize_domain($href);
                    if ($domain) {
                        if (!isset($stats['domains'][$domain])) {
                            $stats['domains'][$domain] = 0;
                        }
                        $stats['domains'][$domain]++;
                    }
                }
            }

            $total_for_post = $post_internal + $post_external;
            if ($total_for_post <= 0) {
                continue;
            }

            $stats['post_link_counts'][$post->ID] = $total_for_post;
            $stats['post_breakdown'][] = [
                'post_id'   => $post->ID,
                'title'     => get_the_title($post),
                'date'      => get_date_from_gmt($post->post_date_gmt, get_option('date_format')),
                'post_type' => $post->post_type,
                'total'     => $total_for_post,
                'internal'  => $post_internal,
                'external'  => $post_external,
            ];
        }

        return $stats;
    }

    /**
     * Validates requested post types against registered types.
     */
    private function sanitize_post_types(array $post_types) {
        $post_types = array_filter(array_map('sanitize_key', $post_types));
        if (!$post_types) {
            return [];
        }

        $registered = get_post_types([], 'names');
        return array_values(array_intersect($post_types, $registered));
    }

    /**
     * Extracts anchor href values from post content.
     */
    private function extract_links_from_content($content) {
        if (empty($content)) {
            return [];
        }

        $links = [];
        if (preg_match_all("/<a\s[^>]*href=(\"|')(.*?)(\"|')/is", $content, $matches)) {
            foreach ($matches[2] as $href) {
                $href = trim($href);
                if ('' === $href) {
                    continue;
                }

                if (stripos($href, 'javascript:') === 0 || stripos($href, 'mailto:') === 0) {
                    continue;
                }

                $links[] = $href;
            }
        }

        return $links;
    }

    /**
     * Checks whether a URL belongs to the current site.
     */
    private function is_internal_url($url) {
        if (empty($url)) {
            return true;
        }

        $parsed = wp_parse_url($url);
        if (false === $parsed) {
            return false;
        }

        if (empty($parsed['host'])) {
            return true;
        }

        $site_host = wp_parse_url(home_url(), PHP_URL_HOST);
        return $parsed['host'] === $site_host;
    }

    /**
     * Normalizes a domain for display.
     */
    private function normalize_domain($url) {
        $parsed = wp_parse_url($url);
        if (false === $parsed || empty($parsed['host'])) {
            return '';
        }

        $host = strtolower($parsed['host']);
        if (0 === strpos($host, 'www.')) {
            $host = substr($host, 4);
        }

        return $host;
    }

    /**
     * Attempts to determine an attachment's file size.
     */
    private function get_attachment_size($attachment_id) {
        $meta = wp_get_attachment_metadata($attachment_id);
        if (is_array($meta) && isset($meta['filesize'])) {
            return (int) $meta['filesize'];
        }

        $file = get_attached_file($attachment_id);
        if ($file && file_exists($file)) {
            $size = filesize($file);
            return $size ? (int) $size : 0;
        }

        return 0;
    }
}
