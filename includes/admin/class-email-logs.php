<?php
/**
 * Email Logs Class
 *
 * Handles email logs functionality and AJAX operations.
 *
 * @package UpMail
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * UpMail Email Logs Class
 *
 * @since 1.0.0
 */
class UpMail_Email_Logs {

    /**
     * Initialize email logs functionality.
     *
     * @since 1.0.0
     */
    public static function init() {
        // Ensure the table exists.
        UpMail_Logger::init();
        
        add_action( 'wp_ajax_upmail_get_email_details', array( __CLASS__, 'handle_get_email_details' ) );
        add_action( 'wp_ajax_upmail_resend_email', array( __CLASS__, 'handle_resend_email' ) );
    }

    /**
     * Handle email details AJAX request.
     *
     * @since 1.0.0
     */
    public static function handle_get_email_details() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'upmail_email_details', 'nonce', false ) ) {
            wp_send_json_error( array(
                'message' => __( 'Invalid security token.', 'upmail' ),
            ) );
        }

        // Check user capability.
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array(
                'message' => __( 'You do not have permission to perform this action.', 'upmail' ),
            ) );
        }

        // Get and validate input.
        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        if ( empty( $id ) ) {
            wp_send_json_error( array(
                'message' => __( 'Invalid email ID.', 'upmail' ),
            ) );
        }

        // Get email details.
        $email = UpMail_Logger::get_email( $id );
        if ( ! $email ) {
            wp_send_json_error( array(
                'message' => __( 'Email not found.', 'upmail' ),
            ) );
        }

        wp_send_json_success( array(
            'email' => $email,
        ) );
    }

    /**
     * Handle resend email AJAX request.
     *
     * @since 1.0.0
     */
    public static function handle_resend_email() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'upmail_resend_email', 'nonce', false ) ) {
            wp_send_json_error( __( 'Invalid security token.', 'upmail' ) );
        }

        // Check user capability.
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You do not have permission to perform this action.', 'upmail' ) );
        }

        // Get and validate input.
        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        if ( empty( $id ) ) {
            wp_send_json_error( __( 'Invalid email ID.', 'upmail' ) );
        }

        // Get email details.
        $email = UpMail_Logger::get_email( $id );
        if ( ! $email ) {
            wp_send_json_error( __( 'Email not found.', 'upmail' ) );
        }

        // Attempt to resend.
        try {
            $result = UpMail_Resend_Handler::resend_email( $email );
            if ( $result['success'] ) {
                wp_send_json_success( __( 'Email resent successfully.', 'upmail' ) );
            } else {
                wp_send_json_error( $result['message'] );
            }
        } catch ( Exception $e ) {
            wp_send_json_error( $e->getMessage() );
        }
    }

    /**
     * Render email logs page.
     *
     * @since 1.0.0
     */
    public static function render() {
        require dirname( __FILE__ ) . '/views/email-logs.php';
    }
} 