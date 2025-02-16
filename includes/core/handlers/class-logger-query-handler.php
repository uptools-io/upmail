<?php
/**
 * Logger Query Handler
 *
 * Handles database queries for email logging.
 *
 * @package UpMail
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * UpMail Logger Query Handler Class
 *
 * @since 1.0.0
 */
class UpMail_Logger_Query_Handler {

    /**
     * Build where clause for log queries.
     *
     * @since 1.0.0
     *
     * @param array $args {
     *     Optional. Arguments for filtering logs.
     *
     *     @type string $status     Filter by status.
     *     @type string $start_date Start date for date range.
     *     @type string $end_date   End date for date range.
     *     @type string $search     Search term.
     * }
     * @return array {
     *     @type array  $where   Array of where clauses.
     *     @type array  $values  Array of query values.
     * }
     */
    public static function build_where_clause( $args = array() ) {
        $where = array( '1=1' );
        $values = array();

        if ( ! empty( $args['status'] ) ) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        if ( ! empty( $args['start_date'] ) ) {
            $where[] = 'created_at >= %s';
            $values[] = $args['start_date'] . ' 00:00:00';
        }

        if ( ! empty( $args['end_date'] ) ) {
            $where[] = 'created_at <= %s';
            $values[] = $args['end_date'] . ' 23:59:59';
        }

        if ( ! empty( $args['search'] ) ) {
            $where[] = '(subject LIKE %s OR to_email LIKE %s OR message LIKE %s)';
            $search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $search_term;
        }

        return array(
            'where'  => $where,
            'values' => $values,
        );
    }

    /**
     * Get total count of logs based on filters.
     *
     * @since 1.0.0
     *
     * @param array $args Query arguments.
     * @return int Total number of logs.
     */
    public static function get_total_count( $args = array() ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . UpMail_Logger::TABLE_NAME;
        $query_parts = self::build_where_clause( $args );
        $where_clause = implode( ' AND ', $query_parts['where'] );
        
        if ( ! empty( $query_parts['values'] ) ) {
            return (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM `$table_name` WHERE $where_clause",
                $query_parts['values']
            ) );
        }
        
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$table_name` WHERE $where_clause" );
    }
} 