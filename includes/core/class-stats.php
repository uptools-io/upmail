<?php
/**
 * Email Statistics Handler
 *
 * Handles email statistics tracking and reporting.
 *
 * @package UpMail
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * UpMail Statistics Class
 *
 * @since 1.0.0
 */
class UpMail_Stats {

    /**
     * Statistics table name.
     *
     * @since 1.0.0
     * @var string
     */
    const TABLE_NAME = 'upmail_stats';

    /**
     * Initialize statistics functionality.
     *
     * @since 1.0.0
     */
    public static function init() {
        // Create or update database table.
        self::create_table();
        
        // Hook into email logging to update stats.
        add_action( 'upmail_after_log_email', array( __CLASS__, 'update_stats' ), 10, 2 );
    }

    /**
     * Create or update the statistics table.
     *
     * @since 1.0.0
     */
    private static function create_table() {
        global $wpdb;
        
        $table_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(1) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                self::TABLE_NAME
            )
        );
        
        if ( $table_exists ) {
            return;
        }
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            year smallint(4) NOT NULL,
            month tinyint(2) NOT NULL,
            day tinyint(2) NOT NULL,
            total_sent int(11) NOT NULL DEFAULT 0,
            total_failed int(11) NOT NULL DEFAULT 0,
            api_errors int(11) NOT NULL DEFAULT 0,
            config_errors int(11) NOT NULL DEFAULT 0,
            other_errors int(11) NOT NULL DEFAULT 0,
            last_updated datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY date_index (year, month, day),
            KEY year_month (year, month)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Update statistics after email logging.
     *
     * @since 1.0.0
     *
     * @param string $status       Email status ('sent' or 'failed').
     * @param string $api_response API response message.
     */
    public static function update_stats( $status, $api_response ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $current_date = current_time( 'mysql' );
        
        $year  = date( 'Y', strtotime( $current_date ) );
        $month = date( 'n', strtotime( $current_date ) );
        $day   = date( 'j', strtotime( $current_date ) );

        // Determine error type.
        $error_type = 'other';
        if ( 'failed' === $status ) {
            if ( false !== strpos( $api_response, 'Configuration Error' ) ) {
                $error_type = 'config';
            } elseif ( false !== strpos( $api_response, 'API Error' ) ) {
                $error_type = 'api';
            }
        }

        // Try to update existing record.
        $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO `$table_name` 
                (year, month, day, total_sent, total_failed, api_errors, config_errors, other_errors, last_updated)
                VALUES (%d, %d, %d, %d, %d, %d, %d, %d, %s)
                ON DUPLICATE KEY UPDATE
                total_sent = total_sent + %d,
                total_failed = total_failed + %d,
                api_errors = api_errors + %d,
                config_errors = config_errors + %d,
                other_errors = other_errors + %d,
                last_updated = %s",
                $year, 
                $month, 
                $day,
                'sent' === $status ? 1 : 0,
                'failed' === $status ? 1 : 0,
                'api' === $error_type ? 1 : 0,
                'config' === $error_type ? 1 : 0,
                'other' === $error_type && 'failed' === $status ? 1 : 0,
                $current_date,
                'sent' === $status ? 1 : 0,
                'failed' === $status ? 1 : 0,
                'api' === $error_type ? 1 : 0,
                'config' === $error_type ? 1 : 0,
                'other' === $error_type && 'failed' === $status ? 1 : 0,
                $current_date
            )
        );
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
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $current_date = current_time( 'mysql' );
        
        $current_year  = date( 'Y', strtotime( $current_date ) );
        $current_month = date( 'n', strtotime( $current_date ) );

        // Get current month stats.
        $month_stats = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT 
                    SUM(total_sent) as month_sent,
                    SUM(total_failed) as month_failed
                FROM `$table_name`
                WHERE year = %d AND month = %d",
                $current_year,
                $current_month
            )
        );

        // Get all-time stats.
        $total_stats = $wpdb->get_row(
            "SELECT 
                SUM(total_sent) as total_sent,
                SUM(total_failed) as total_failed
            FROM `$table_name`"
        );

        return array(
            'current_month' => array(
                'sent'   => (int) $month_stats->month_sent,
                'failed' => (int) $month_stats->month_failed,
            ),
            'total' => array(
                'sent'   => (int) $total_stats->total_sent,
                'failed' => (int) $total_stats->total_failed,
            ),
        );
    }

    /**
     * Migrate existing logs to statistics.
     *
     * @since 1.0.0
     */
    public static function migrate_logs() {
        global $wpdb;
        
        $log_table = $wpdb->prefix . UpMail_Logger::TABLE_NAME;
        $stats_table = $wpdb->prefix . self::TABLE_NAME;

        // Get all dates from logs.
        $dates = $wpdb->get_results(
            "SELECT 
                YEAR(date_time) as year,
                MONTH(date_time) as month,
                DAY(date_time) as day,
                COUNT(CASE WHEN status = 'sent' THEN 1 END) as total_sent,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as total_failed,
                COUNT(CASE WHEN status = 'failed' AND api_response LIKE '%API Error%' THEN 1 END) as api_errors,
                COUNT(CASE WHEN status = 'failed' AND api_response LIKE '%Configuration Error%' THEN 1 END) as config_errors,
                COUNT(CASE WHEN status = 'failed' AND api_response NOT LIKE '%API Error%' AND api_response NOT LIKE '%Configuration Error%' THEN 1 END) as other_errors
            FROM `$log_table`
            GROUP BY YEAR(date_time), MONTH(date_time), DAY(date_time)"
        );

        foreach ( $dates as $date ) {
            $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO `$stats_table` 
                    (year, month, day, total_sent, total_failed, api_errors, config_errors, other_errors, last_updated)
                    VALUES (%d, %d, %d, %d, %d, %d, %d, %d, NOW())
                    ON DUPLICATE KEY UPDATE
                    total_sent = VALUES(total_sent),
                    total_failed = VALUES(total_failed),
                    api_errors = VALUES(api_errors),
                    config_errors = VALUES(config_errors),
                    other_errors = VALUES(other_errors),
                    last_updated = NOW()",
                    $date->year,
                    $date->month,
                    $date->day,
                    $date->total_sent,
                    $date->total_failed,
                    $date->api_errors,
                    $date->config_errors,
                    $date->other_errors
                )
            );
        }
    }
} 