<?php
/**
 * Stats Controller Class
 *
 * Handles email statistics and reporting functionality.
 *
 * @package UpMail
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * UpMail Stats Controller Class
 *
 * @since 1.0.0
 */
class UpMail_Stats_Controller {

    /**
     * Initialize the controller.
     *
     * @since 1.0.0
     */
    public static function init() {
        add_action( 'admin_init', array( __CLASS__, 'maybe_migrate_stats' ) );
    }

    /**
     * Get overview statistics.
     *
     * @since 1.0.0
     *
     * @return array {
     *     Overview statistics data.
     *
     *     @type array $current_month Current month statistics.
     *     @type array $total         Total statistics.
     * }
     */
    public static function get_overview_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . UpMail_Logger::TABLE_NAME;
        $current_date = current_time( 'mysql' );
        
        $current_year = date( 'Y', strtotime( $current_date ) );
        $current_month = date( 'n', strtotime( $current_date ) );
        $month_start = date( 'Y-m-01 00:00:00', strtotime( $current_date ) );
        $month_end = date( 'Y-m-t 23:59:59', strtotime( $current_date ) );

        // Get current month stats.
        $month_stats = $wpdb->get_row( $wpdb->prepare(
            "SELECT 
                COUNT(CASE WHEN status = 'sent' THEN 1 END) as month_sent,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as month_failed
            FROM `$table_name`
            WHERE created_at >= %s AND created_at <= %s",
            $month_start,
            $month_end
        ) );

        // Get total stats.
        $total_stats = $wpdb->get_row(
            "SELECT 
                COUNT(CASE WHEN status = 'sent' THEN 1 END) as total_sent,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as total_failed
            FROM `$table_name`"
        );

        return array(
            'current_month' => array(
                'sent'   => (int) ( $month_stats->month_sent ?? 0 ),
                'failed' => (int) ( $month_stats->month_failed ?? 0 )
            ),
            'total' => array(
                'sent'   => (int) ( $total_stats->total_sent ?? 0 ),
                'failed' => (int) ( $total_stats->total_failed ?? 0 )
            )
        );
    }

    /**
     * Get most common error type for a date range.
     *
     * @since 1.0.0
     *
     * @param string $start_date Start date in Y-m-d H:i:s format.
     * @param string $end_date   End date in Y-m-d H:i:s format.
     * @return string Error type ('config', 'api', or 'other').
     */
    public static function get_error_stats( $start_date, $end_date ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . UpMail_Logger::TABLE_NAME;
        
        $error_stats = $wpdb->get_row( $wpdb->prepare(
            "SELECT 
                CASE 
                    WHEN api_response LIKE %s THEN 'config'
                    WHEN api_response LIKE %s THEN 'api'
                    ELSE 'other'
                END as error_type,
                COUNT(*) as count
            FROM `$table_name`
            WHERE status = 'failed'
            AND created_at >= %s 
            AND created_at <= %s
            GROUP BY error_type
            ORDER BY count DESC
            LIMIT 1",
            '%Configuration Error%',
            '%API Error%',
            $start_date,
            $end_date
        ) );

        return $error_stats ? $error_stats->error_type : 'api';
    }

    /**
     * Get daily email stats for the last 3 months.
     *
     * @since 1.0.0
     *
     * @return array Array of daily stats with date and counts.
     */
    public static function get_daily_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . UpMail_Logger::TABLE_NAME;
        $three_months_ago = date( 'Y-m-d', strtotime( '-3 months' ) );
        
        $query = $wpdb->prepare(
            "SELECT 
                DATE(created_at) as date,
                COUNT(CASE WHEN status = 'sent' THEN 1 END) as sent_count,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_count,
                COUNT(*) as total_count
            FROM {$table_name} 
            WHERE created_at >= %s 
            GROUP BY DATE(created_at) 
            ORDER BY date ASC",
            $three_months_ago
        );
        
        $results = $wpdb->get_results( $query, ARRAY_A );
        
        // Calculate running totals.
        $running_total = 0;
        $running_sent = 0;
        $running_failed = 0;
        
        foreach ( $results as &$day ) {
            $running_sent += (int) $day['sent_count'];
            $running_failed += (int) $day['failed_count'];
            $running_total += (int) $day['total_count'];
            
            $day['cumulative_sent'] = $running_sent;
            $day['cumulative_failed'] = $running_failed;
            $day['cumulative_total'] = $running_total;
        }
        
        return $results ?: array();
    }

    /**
     * Maybe migrate statistics from old format.
     *
     * @since 1.0.0
     */
    public static function maybe_migrate_stats() {
        $migration_needed = get_option( 'upmail_stats_migration_needed', true );
        
        if ( ! $migration_needed ) {
            return;
        }

        self::migrate_stats();
        update_option( 'upmail_stats_migration_needed', false );
    }

    /**
     * Migrate statistics from old format to new format.
     *
     * @since 1.0.0
     */
    private static function migrate_stats() {
        global $wpdb;
        
        $old_table = $wpdb->prefix . 'upmail_stats';
        $new_table = $wpdb->prefix . UpMail_Logger::TABLE_NAME;
        
        // Check if old table exists.
        $table_exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(1) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
            DB_NAME,
            $old_table
        ) );
        
        if ( ! $table_exists ) {
            return;
        }

        // Migrate data.
        $wpdb->query( $wpdb->prepare(
            "INSERT INTO $new_table (status, created_at)
            SELECT 
                CASE WHEN type = 'success' THEN 'sent' ELSE 'failed' END,
                created_at
            FROM $old_table
            WHERE created_at >= %s",
            date( 'Y-m-d', strtotime( '-3 months' ) )
        ) );

        // Drop old table.
        $wpdb->query( "DROP TABLE IF EXISTS $old_table" );
    }
} 