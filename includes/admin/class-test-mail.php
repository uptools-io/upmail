<?php
/**
 * Test Mail Class
 *
 * Handles test email functionality in the admin interface.
 *
 * @package UpMail
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * UpMail Test Mail Class
 *
 * @since 1.0.0
 */
class UpMail_Test_Mail {

    /**
     * Initialize test mail functionality.
     *
     * @since 1.0.0
     */
    public static function init() {
        add_action( 'wp_ajax_upmail_send_test', array( __CLASS__, 'handle_test_email' ) );
    }

    /**
     * Handle test email AJAX request.
     *
     * @since 1.0.0
     */
    public static function handle_test_email() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'upmail_send_test', 'nonce', false ) ) {
            wp_send_json_error( array(
                'message' => __( 'Invalid security token.', 'upmail' ),
                'debug'   => null
            ) );
        }

        // Check user capability.
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array(
                'message' => __( 'You do not have permission to perform this action.', 'upmail' ),
                'debug'   => null
            ) );
        }

        // Get and validate input.
        $to = isset( $_POST['to'] ) ? sanitize_email( $_POST['to'] ) : '';
        if ( empty( $to ) ) {
            wp_send_json_error( array(
                'message' => __( 'Please enter a valid recipient email address.', 'upmail' ),
                'debug'   => null
            ) );
        }

        $subject = isset( $_POST['subject'] ) ? sanitize_text_field( $_POST['subject'] ) : '';
        $message = isset( $_POST['message'] ) ? wp_kses_post( $_POST['message'] ) : '';
        $is_html = isset( $_POST['html'] ) ? filter_var( $_POST['html'], FILTER_VALIDATE_BOOLEAN ) : false;

        // Send test email using the dedicated test email handler.
        $result = UpMail_Test_Email_Handler::send_test_email( $to, $subject, $message, $is_html );

        // Always return the complete result including debug info.
        if ( $result['success'] ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result );
        }
    }

    /**
     * Render test mail page.
     *
     * @since 1.0.0
     */
    public static function render() {
        require dirname( __FILE__ ) . '/views/test-mail.php';
    }
} 