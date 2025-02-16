<?php
/**
 * Log AJAX Handler Class
 *
 * Handles all log-related AJAX operations.
 *
 * @package UpMail
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * UpMail Log AJAX Handler Class
 *
 * @since 1.0.0
 */
class UpMail_Log_Ajax_Handler {

    /**
     * Delete log via AJAX.
     *
     * @since 1.0.0
     */
    public static function delete_log() {
        check_ajax_referer( 'upmail_delete_log', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You do not have permission to perform this action.', 'upmail' ) );
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        if ( ! $id ) {
            wp_send_json_error( __( 'Invalid log ID.', 'upmail' ) );
        }

        $result = UpMail_Logger::delete_logs( array( $id ) );
        if ( $result ) {
            wp_send_json_success( __( 'Log deleted successfully.', 'upmail' ) );
        } else {
            wp_send_json_error( __( 'Failed to delete log.', 'upmail' ) );
        }
    }

    /**
     * Delete all logs via AJAX.
     *
     * @since 1.0.0
     */
    public static function delete_all_logs() {
        check_ajax_referer( 'upmail_delete_all_logs', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You do not have permission to perform this action.', 'upmail' ) );
        }

        $result = UpMail_Logger::delete_logs();
        if ( $result ) {
            wp_send_json_success( __( 'All logs deleted successfully.', 'upmail' ) );
        } else {
            wp_send_json_error( __( 'Failed to delete logs.', 'upmail' ) );
        }
    }
} 