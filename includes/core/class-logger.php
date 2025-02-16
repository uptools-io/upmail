<?php
/**
 * Main Logger Class
 *
 * Handles core logging functionality and database table management.
 *
 * @package UpMail
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load handler classes
require_once dirname( __FILE__ ) . '/handlers/class-logger-query-handler.php';
require_once dirname( __FILE__ ) . '/handlers/class-logger-stats-handler.php';
require_once dirname( __FILE__ ) . '/handlers/class-logger-email-handler.php';

/**
 * UpMail Logger Class
 *
 * @since 1.0.0
 */
class UpMail_Logger {

    /**
     * Table name constant.
     *
     * @since 1.0.0
     * @var string
     */
    const TABLE_NAME = 'upmail_email_logs';

    /**
     * Initialize logger.
     *
     * @since 1.0.0
     */
    public static function init() {
        // Create or update database table.
        self::create_table();
    }

    /**
     * Create or update the logs table.
     *
     * @since 1.0.0
     */
    private static function create_table() {
        global $wpdb;
        
        $table_exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(1) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
            DB_NAME,
            self::TABLE_NAME
        ) );
        
        if ( $table_exists ) {
            return;
        }
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            to_email varchar(255) NOT NULL,
            subject text NOT NULL,
            message longtext NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            api_response text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    /**
     * Drop the logs table.
     *
     * @since 1.0.0
     */
    public static function drop_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $wpdb->query( "DROP TABLE IF EXISTS `$table_name`" );
    }

    /**
     * Log email.
     *
     * @since 1.0.0
     *
     * @param string $subject     Email subject.
     * @param string $to          Recipient email address.
     * @param string $status      Email status (sent/failed).
     * @param string $message     Email message content.
     * @param string $api_response API response data.
     * @return bool Whether the log was saved successfully.
     */
    public static function log_email( $subject, $to, $status, $message, $api_response ) {
        return UpMail_Logger_Email_Handler::log_email( $subject, $to, $status, $message, $api_response );
    }

    /**
     * Get logs with filtering and pagination.
     *
     * @since 1.0.0
     *
     * @param array $args {
     *     Optional. Arguments for filtering logs.
     *
     *     @type string  $status    Filter by status.
     *     @type string  $start_date Start date for date range.
     *     @type string  $end_date   End date for date range.
     *     @type string  $search     Search term.
     *     @type integer $per_page   Items per page.
     *     @type integer $page       Current page number.
     * }
     * @return array {
     *     @type array $logs  Array of log objects.
     *     @type int   $total Total number of logs.
     * }
     */
    public static function get_logs( $args = array() ) {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_NAME;

        // Ensure table exists
        self::create_table();

        // Get total count
        $total = UpMail_Logger_Query_Handler::get_total_count( $args );

        // Pagination
        $per_page = isset( $args['per_page'] ) ? absint( $args['per_page'] ) : 10;
        $page = isset( $args['page'] ) ? absint( $args['page'] ) : 1;
        $offset = ( $page - 1 ) * $per_page;

        // Build query
        $query_parts = UpMail_Logger_Query_Handler::build_where_clause( $args );
        $where_clause = implode( ' AND ', $query_parts['where'] );
        
        if ( ! empty( $query_parts['values'] ) ) {
            $query = $wpdb->prepare(
                "SELECT * FROM `$table_name` WHERE $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d",
                array_merge( $query_parts['values'], array( $per_page, $offset ) )
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT * FROM `$table_name` WHERE $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d",
                array( $per_page, $offset )
            );
        }
        
        $logs = $wpdb->get_results( $query );

        return array(
            'logs'  => $logs,
            'total' => $total
        );
    }

    /**
     * Get single email log by ID.
     *
     * @since 1.0.0
     *
     * @param int $id Email log ID.
     * @return object|null Email log data or null if not found.
     */
    public static function get_email( $id ) {
        return UpMail_Logger_Email_Handler::get_email( $id );
    }

    /**
     * Delete log entry.
     *
     * @since 1.0.0
     *
     * @param int $id Email log ID.
     * @return bool Whether the log was deleted successfully.
     */
    public static function delete_log( $id ) {
        return UpMail_Logger_Email_Handler::delete_log( $id );
    }

    /**
     * Delete all logs.
     *
     * @since 1.0.0
     *
     * @return bool Whether the logs were deleted successfully.
     */
    public static function delete_all_logs() {
        return UpMail_Logger_Email_Handler::delete_all_logs();
    }

    /**
     * Get hourly stats for heatmap.
     *
     * @since 1.0.0
     *
     * @param array $args Query arguments.
     * @return array Stats data organized by day and hour.
     */
    public static function get_hourly_stats( $args = array() ) {
        return UpMail_Logger_Stats_Handler::get_hourly_stats( $args );
    }

    /**
     * Get failed email statistics.
     *
     * @since 1.0.0
     *
     * @param string $start_date Start date in Y-m-d format.
     * @param string $end_date   End date in Y-m-d format.
     * @return array Failed email statistics.
     */
    public static function get_failed_stats( $start_date = null, $end_date = null ) {
        return UpMail_Logger_Stats_Handler::get_failed_stats( $start_date, $end_date );
    }
} 