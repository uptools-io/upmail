<?php
/**
 * Test Email Handler Class
 *
 * Handles test email sending functionality.
 *
 * @package UpMail
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * UpMail Test Email Handler Class
 *
 * Handles sending and debugging test emails.
 *
 * @since 1.0.0
 */
class UpMail_Test_Email_Handler {

    /**
     * The main plugin instance.
     *
     * @since 1.0.0
     * @var UpMail_Base|null
     */
    private static $plugin = null;

    /**
     * Initialize the handler.
     *
     * @since 1.0.0
     *
     * @param UpMail_Base $plugin The main plugin instance.
     */
    public static function init( $plugin ) {
        self::$plugin = $plugin;
    }

    /**
     * Send test email using the main email handler.
     *
     * @since 1.0.0
     *
     * @param string  $to      Recipient email address.
     * @param string  $subject Email subject.
     * @param string  $message Email message content.
     * @param boolean $is_html Whether to send as HTML email.
     * @return array {
     *     Test email result data.
     *
     *     @type boolean $success Whether the email was sent successfully.
     *     @type string  $message Response message.
     *     @type array   $debug   Debug information.
     * }
     */
    public static function send_test_email( $to, $subject, $message, $is_html = false ) {
        $debug_info = array(
            'steps'    => array(),
            'headers'  => array(),
            'response' => null,
            'error'    => null
        );
        
        // Prepare email data.
        $headers = array();
        if ( $is_html ) {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $debug_info['steps'][] = 'HTML content type set';
        } else {
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
            $debug_info['steps'][] = 'Plain text content type set';
        }

        // Add From headers if configured.
        $from_email = get_option( 'upmail_from_email' );
        $from_name = get_option( 'upmail_from_name' );
        if ( ! empty( $from_email ) ) {
            $headers[] = empty( $from_name ) 
                ? 'From: ' . $from_email
                : 'From: ' . $from_name . ' <' . $from_email . '>';
        }

        $debug_info['headers'] = $headers;
        $debug_info['steps'][] = 'Headers prepared';

        try {
            // Check if API key is configured.
            if ( empty( self::$plugin->get_api_key() ) ) {
                return array(
                    'success' => false,
                    'message' => __( 'API key is not configured. Please configure it in the settings.', 'upmail' ),
                    'debug'   => $debug_info
                );
            }

            // Prepare email attributes.
            $atts = array(
                'to'      => $to,
                'subject' => $subject,
                'message' => $message,
                'headers' => $headers
            );

            $debug_info['steps'][] = 'Email attributes prepared';
            $debug_info['steps'][] = 'Is HTML: ' . ( $is_html ? 'Yes' : 'No' );

            // Send email using the main handler.
            $result = UpMail_Email_Handler::send_mail( false, $atts );
            $debug_info['steps'][] = 'Email handler called with result: ' . ( $result === true ? 'true' : 'false' );

            if ( $result === true ) {
                return array(
                    'success' => true,
                    'message' => sprintf(
                        /* translators: %s: recipient email address */
                        __( '🚀 Woohoo! Test email successfully launched to %s! Time to raid that inbox! 📨', 'upmail' ),
                        $to
                    ),
                    'debug'   => $debug_info
                );
            }

            // Get the last error from the error log.
            $error_log = error_get_last();
            if ( $error_log ) {
                $debug_info['error'] = $error_log;
            }

            // If sending failed.
            return array(
                'success' => false,
                'message' => __( '🤔 Oops! The email got lost in cyberspace. Let\'s check the debug info below and figure out what happened! 🔍', 'upmail' ),
                'debug'   => $debug_info
            );

        } catch ( Exception $e ) {
            $debug_info['steps'][] = 'Exception caught: ' . $e->getMessage();
            $debug_info['error'] = array(
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            );

            return array(
                'success' => false,
                'message' => '❌ ' . $e->getMessage() . ' 🛠️',
                'debug'   => $debug_info
            );
        }
    }
} 