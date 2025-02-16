<?php
/**
 * Logger Email Handler
 *
 * Handles email logging operations.
 *
 * @package UpMail
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * UpMail Logger Email Handler Class
 *
 * @since 1.0.0
 */
class UpMail_Logger_Email_Handler {

    /**
     * Log email details.
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
        global $wpdb;

        $table_name = $wpdb->prefix . UpMail_Logger::TABLE_NAME;
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'subject'      => $subject,
                'to_email'     => $to,
                'message'      => $message,
                'status'       => $status,
                'api_response' => $api_response,
                'created_at'   => current_time( 'mysql' )
            ),
            array(
                '%s', // subject
                '%s', // to_email
                '%s', // message
                '%s', // status
                '%s', // api_response
                '%s'  // created_at
            )
        );

        if ( $result ) {
            /**
             * Fires after an email is logged.
             *
             * @since 1.0.0
             *
             * @param string $status       Email status.
             * @param string $api_response API response data.
             */
            do_action( 'upmail_after_log_email', $status, $api_response );
        }

        return (bool) $result;
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
        global $wpdb;
        
        $table_name = $wpdb->prefix . UpMail_Logger::TABLE_NAME;
        
        $email = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM `$table_name` WHERE id = %d",
            $id
        ) );

        if ( $email ) {
            // Decode API response JSON for better display
            $email->api_response = json_decode( $email->api_response );
            return $email;
        }

        return null;
    }

    /**
     * Delete email log entry.
     *
     * @since 1.0.0
     *
     * @param int $id Email log ID.
     * @return bool Whether the log was deleted successfully.
     */
    public static function delete_log( $id ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . UpMail_Logger::TABLE_NAME;
        return (bool) $wpdb->delete( $table_name, array( 'id' => $id ), array( '%d' ) );
    }

    /**
     * Delete all email logs.
     *
     * @since 1.0.0
     *
     * @return bool Whether the logs were deleted successfully.
     */
    public static function delete_all_logs() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . UpMail_Logger::TABLE_NAME;
        return (bool) $wpdb->query( "TRUNCATE TABLE `$table_name`" );
    }
} 