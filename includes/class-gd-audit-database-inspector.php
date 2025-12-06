<?php
/**
 * Retrieves MySQL table metadata for the GD Audit dashboard.
 */

if (!defined('ABSPATH')) {
    exit;
}

class GDAuditDatabaseInspector {
    /**
     * Returns metadata for each table in the current database.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_tables() {
        global $wpdb;

        $results = $wpdb->get_results('SHOW TABLE STATUS', ARRAY_A);

        if (!$results) {
            return [];
        }

        $tables = [];
        foreach ($results as $row) {
            $column_info = $this->get_table_columns($row['Name']);
            $tables[] = [
                'name'          => $row['Name'],
                'engine'        => $row['Engine'],
                'rows'          => isset($row['Rows']) ? (int) $row['Rows'] : 0,
                'data_length'   => isset($row['Data_length']) ? (int) $row['Data_length'] : 0,
                'index_length'  => isset($row['Index_length']) ? (int) $row['Index_length'] : 0,
                'data_free'     => isset($row['Data_free']) ? (int) $row['Data_free'] : 0,
                'auto_increment'=> isset($row['Auto_increment']) ? (int) $row['Auto_increment'] : 0,
                'collation'     => $row['Collation'],
                'comment'       => $row['Comment'],
                'is_wp_table'   => strpos($row['Name'], $wpdb->prefix) === 0,
                'size_bytes'    => ((int) $row['Data_length']) + ((int) $row['Index_length']),
                'row_format'    => isset($row['Row_format']) ? $row['Row_format'] : '',
                'avg_row_length'=> isset($row['Avg_row_length']) ? (int) $row['Avg_row_length'] : 0,
                'create_time'   => isset($row['Create_time']) ? $row['Create_time'] : null,
                'update_time'   => isset($row['Update_time']) ? $row['Update_time'] : null,
                'check_time'    => isset($row['Check_time']) ? $row['Check_time'] : null,
                'columns'       => $column_info['columns'],
                'column_total'  => $column_info['total'],
            ];
        }

        usort($tables, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return $tables;
    }

    /**
     * Aggregates summary metrics for the provided tables list.
     */
    public function get_summary(array $tables) {
        $summary = [
            'total_tables'   => count($tables),
            'total_rows'     => 0,
            'data_size'      => 0,
            'index_size'     => 0,
            'wp_tables'      => 0,
        ];

        foreach ($tables as $table) {
            $summary['total_rows'] += $table['rows'];
            $summary['data_size']  += $table['data_length'];
            $summary['index_size'] += $table['index_length'];
            if (!empty($table['is_wp_table'])) {
                $summary['wp_tables']++;
            }
        }

        return $summary;
    }

    /**
     * Retrieves column definitions for a table with a sane display limit.
     */
    private function get_table_columns($table_name, $limit = 12) {
        global $wpdb;

        if (empty($table_name)) {
            return [
                'columns' => [],
                'total'   => 0,
            ];
        }

        $safe_name = $this->escape_table_name($table_name);
        $query     = sprintf('SHOW FULL COLUMNS FROM `%s`', $safe_name);
        $rows      = $wpdb->get_results($query, ARRAY_A);

        if (!$rows) {
            return [
                'columns' => [],
                'total'   => 0,
            ];
        }

        $columns = [];
        foreach ($rows as $column) {
            $columns[] = [
                'name'    => $column['Field'],
                'type'    => strtoupper($column['Type']),
                'key'     => $column['Key'],
                'null'    => ('YES' === strtoupper($column['Null'])),
                'default' => $column['Default'],
                'extra'   => $column['Extra'],
            ];
        }

        $total = count($columns);
        if ($limit > 0 && $total > $limit) {
            $columns = array_slice($columns, 0, $limit);
        }

        return [
            'columns' => $columns,
            'total'   => $total,
        ];
    }

    /**
     * Escapes table identifiers for SHOW queries.
     */
    private function escape_table_name($table_name) {
        return str_replace('`', '``', $table_name);
    }
}
