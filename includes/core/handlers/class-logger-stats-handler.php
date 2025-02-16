<?php
/**
 * Logger Stats Handler
 *
 * Handles email statistics and reporting.
 *
 * @package UpMail
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * UpMail Logger Stats Handler Class
 *
 * @since 1.0.0
 */
class UpMail_Logger_Stats_Handler {

    /**
     * Get hourly stats for heatmap.
     *
     * @since 1.0.0
     *
     * @param array $args {
     *     Optional. Arguments for filtering stats.
     *
     *     @type string $start_date Start date for date range.
     *     @type string $end_date   End date for date range.
     * }
     * @return array Stats data organized by day and hour.
     */
    public static function get_hourly_stats( $args = array() ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . UpMail_Logger::TABLE_NAME;
        $query_parts = UpMail_Logger_Query_Handler::build_where_clause( $args );
        $where_clause = implode( ' AND ', $query_parts['where'] );
        $where_clause .= " AND status = 'sent'";
        
        $query = "SELECT 
            DAYOFWEEK(created_at) as day_of_week,
            HOUR(created_at) as hour,
            COUNT(*) as count
            FROM `$table_name`
            WHERE $where_clause
            GROUP BY DAYOFWEEK(created_at), HOUR(created_at)
            ORDER BY day_of_week, hour";

        if ( ! empty( $query_parts['values'] ) ) {
            $results = $wpdb->get_results( $wpdb->prepare( $query, $query_parts['values'] ) );
        } else {
            $results = $wpdb->get_results( $query );
        }

        // Initialize empty array for all days and hours
        $stats = array();
        for ( $day = 1; $day <= 7; $day++ ) {
            for ( $hour = 0; $hour < 24; $hour++ ) {
                $stats[$day][$hour] = 0;
            }
        }

        // Fill in actual values
        foreach ( $results as $row ) {
            $stats[$row->day_of_week][$row->hour] = (int) $row->count;
        }

        return $stats;
    }

    /**
     * Get failed email statistics.
     *
     * @since 1.0.0
     *
     * @param string $start_date Start date in Y-m-d format.
     * @param string $end_date   End date in Y-m-d format.
     * @return array {
     *     Failed email statistics.
     *
     *     @type int    $count             Number of failed emails.
     *     @type int    $total             Total number of emails.
     *     @type string $percentage        Failure rate as percentage.
     *     @type string $percentage_change Change from previous period.
     *     @type string $most_common_error Most common error type.
     * }
     */
    public static function get_failed_stats( $start_date = null, $end_date = null ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . UpMail_Logger::TABLE_NAME;
        $where = array( "status = 'failed'" );
        $values = array();

        if ( $start_date ) {
            $where[] = 'created_at >= %s';
            $values[] = $start_date . ' 00:00:00';
        }
        if ( $end_date ) {
            $where[] = 'created_at <= %s';
            $values[] = $end_date . ' 23:59:59';
        }

        $where_clause = implode( ' AND ', $where );

        // Get total failed count
        $failed_count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM `$table_name` WHERE $where_clause",
            $values
        ) );

        // Get total count for percentage
        $total_count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM `$table_name` WHERE created_at >= %s AND created_at <= %s",
            array( $start_date . ' 00:00:00', $end_date . ' 23:59:59' )
        ) );

        // Get most common error type
        $error_query = "SELECT 
            CASE 
                WHEN api_response LIKE '%Configuration Error%' THEN 'config'
                WHEN api_response LIKE '%API Error%' THEN 'api'
                ELSE 'other'
            END as error_type,
            COUNT(*) as count
            FROM `$table_name`
            WHERE $where_clause
            GROUP BY error_type
            ORDER BY count DESC
            LIMIT 1";

        $most_common_error = $wpdb->get_row( $wpdb->prepare( $error_query, $values ) );

        // Calculate percentage change from previous period
        $period_length = strtotime( $end_date ) - strtotime( $start_date );
        $prev_start = date( 'Y-m-d', strtotime( $start_date ) - $period_length );
        $prev_end = date( 'Y-m-d', strtotime( $start_date ) - 1 );

        $prev_count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM `$table_name` WHERE status = 'failed' AND created_at >= %s AND created_at <= %s",
            array( $prev_start . ' 00:00:00', $prev_end . ' 23:59:59' )
        ) );

        $percentage_change = 0 < $prev_count ? ( ( $failed_count - $prev_count ) / $prev_count ) * 100 : 0;

        return array(
            'count'             => (int) $failed_count,
            'total'            => (int) $total_count,
            'percentage'       => number_format( ( $failed_count / ( $total_count ?: 1 ) ) * 100, 1 ),
            'percentage_change' => number_format( $percentage_change, 1 ),
            'most_common_error' => $most_common_error ? $most_common_error->error_type : 'other'
        );
    }
} 