<?php
/**
 * Email AJAX Handler Class
 *
 * Handles all email-related AJAX operations.
 *
 * @package UpMail
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * UpMail Email AJAX Handler Class
 *
 * @since 1.0.0
 */
class UpMail_Email_Ajax_Handler {

    /**
     * Send test email via AJAX.
     *
     * @since 1.0.0
     */
    public static function send_test_email() {
        // Verify nonce and capability.
        check_ajax_referer( 'upmail_send_test', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You do not have permission to perform this action.', 'upmail' ) );
        }

        // Check if API key is configured.
        if ( empty( UpMail::get_instance()->get_api_key() ) ) {
            wp_send_json_error( __( 'Please configure your API key first.', 'upmail' ) );
        }

        // Get and validate input.
        $to = isset( $_POST['to'] ) ? sanitize_email( $_POST['to'] ) : '';
        $subject = isset( $_POST['subject'] ) ? sanitize_text_field( $_POST['subject'] ) : '';
        $message = isset( $_POST['message'] ) ? sanitize_textarea_field( $_POST['message'] ) : '';
        $is_html = isset( $_POST['html'] ) && $_POST['html'] === 'true';

        // Input validation with specific messages.
        $errors = array();
        
        if ( empty( $to ) ) {
            $errors[] = __( 'Recipient email is required.', 'upmail' );
        } elseif ( ! is_email( $to ) ) {
            $errors[] = __( 'Invalid recipient email address.', 'upmail' );
        }

        if ( empty( $subject ) ) {
            $errors[] = __( 'Subject is required.', 'upmail' );
        }

        if ( empty( $message ) ) {
            $errors[] = __( 'Message is required.', 'upmail' );
        }

        if ( ! empty( $errors ) ) {
            wp_send_json_error( implode( '<br>', $errors ) );
        }

        // Send test email.
        $result = UpMail_Email_Handler::send_test_email( $to, $subject, $message, $is_html );
        
        if ( $result['success'] ) {
            wp_send_json_success( $result['message'] );
        } else {
            wp_send_json_error( $result['message'] );
        }
    }

    /**
     * View email content via AJAX.
     *
     * @since 1.0.0
     */
    public static function view_email() {
        check_ajax_referer( 'upmail_view_email', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You do not have permission to perform this action.', 'upmail' ) );
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        if ( ! $id ) {
            wp_send_json_error( __( 'Invalid log ID.', 'upmail' ) );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'upmail_mail_logs';
        $log = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ) );

        if ( ! $log ) {
            wp_send_json_error( __( 'Log not found.', 'upmail' ) );
        }

        // Format API response if it exists.
        $api_response = '';
        if ( ! empty( $log->api_response ) ) {
            $response_data = json_decode( $log->api_response, true );
            if ( json_last_error() === JSON_ERROR_NONE && is_array( $response_data ) ) {
                $api_response = wp_json_encode( $response_data, JSON_PRETTY_PRINT );
            } else {
                $api_response = $log->api_response;
            }
        }

        wp_send_json_success( array(
            'subject'      => $log->subject,
            'to'          => $log->to_email,
            'message'     => $log->message,
            'status'      => $log->status,
            'date'        => $log->date_time,
            'api_response' => $api_response
        ) );
    }

    /**
     * Resend email via AJAX.
     *
     * @since 1.0.0
     */
    public static function resend_email() {
        check_ajax_referer( 'upmail_resend_email', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You do not have permission to perform this action.', 'upmail' ) );
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        if ( ! $id ) {
            wp_send_json_error( __( 'Invalid log ID.', 'upmail' ) );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'upmail_mail_logs';
        $log = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ) );

        if ( ! $log ) {
            wp_send_json_error( __( 'Log not found.', 'upmail' ) );
        }

        // Resend email.
        $result = UpMail_Email_Handler::resend_email( $log );
        
        if ( $result['success'] ) {
            wp_send_json_success( $result['message'] );
        } else {
            wp_send_json_error( $result['message'] );
        }
    }
} 