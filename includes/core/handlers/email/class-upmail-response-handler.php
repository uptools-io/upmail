<?php
/**
 * Response Handler Class
 *
 * Handles API response processing for various email operations.
 *
 * @package UpMail
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * UpMail Response Handler Class
 *
 * @since 1.0.0
 */
class UpMail_Response_Handler {

    /**
     * Handle regular send response.
     *
     * @since 1.0.0
     *
     * @param array  $api_response API response data.
     * @param string $subject      Email subject.
     * @param string $to           Recipient email address.
     * @param string $message      Email message content.
     * @param array  $atts         Email attributes.
     * @param mixed  $pre          Previous filter value.
     * @return mixed True if successful, previous value if failed.
     */
    public static function handle_send_response( $api_response, $subject, $to, $message, $atts, $pre ) {
        if ( is_array( $api_response ) && isset( $api_response['success'] ) && true === $api_response['success'] ) {
            // Log the successful email.
            UpMail_Logger::log_email(
                $subject, 
                $to, 
                'sent', 
                $message,
                wp_json_encode( $api_response, JSON_PRETTY_PRINT )
            );
            
            return true;
        }
        
        // Log the failed attempt.
        $error_data = wp_json_encode( array(
            'code'     => 500,
            'error'    => 'Invalid Response',
            'message'  => 'Unexpected API response format',
            'request'  => $atts,
            'response' => $api_response
        ), JSON_PRETTY_PRINT );

        UpMail_Logger::log_email(
            $subject, 
            $to, 
            'failed', 
            $message,
            $error_data
        );
        
        return $pre;
    }

    /**
     * Handle regular send error.
     *
     * @since 1.0.0
     *
     * @param Exception $e       Exception object.
     * @param string    $subject Email subject.
     * @param string    $to      Recipient email address.
     * @param string    $message Email message content.
     * @param mixed     $pre     Previous filter value.
     * @return mixed Previous filter value.
     */
    public static function handle_send_error( $e, $subject, $to, $message, $pre ) {
        $error_data = self::format_error_data( $e );
        
        // Log the failed attempt.
        UpMail_Logger::log_email(
            $subject, 
            $to, 
            'failed', 
            $message,
            $error_data
        );
        
        return $pre;
    }

    /**
     * Handle test email response.
     *
     * @since 1.0.0
     *
     * @param array  $api_response API response data.
     * @param string $subject      Email subject.
     * @param string $to           Recipient email address.
     * @param string $message      Email message content.
     * @return array {
     *     Response data.
     *
     *     @type bool   $success Whether the operation was successful.
     *     @type string $message Response message.
     * }
     */
    public static function handle_test_response( $api_response, $subject, $to, $message ) {
        if ( is_array( $api_response ) && isset( $api_response['success'] ) && true === $api_response['success'] ) {
            // Log the successful test email.
            UpMail_Logger::log_email(
                $subject,
                $to,
                'sent',
                $message,
                wp_json_encode( $api_response, JSON_PRETTY_PRINT )
            );

            return array(
                'success' => true,
                'message' => sprintf(
                    /* translators: %s: recipient email address */
                    __( 'Test email sent successfully to %s! Please check your inbox.', 'upmail' ),
                    $to
                )
            );
        }

        // Log the failed test email.
        $error_data = wp_json_encode( array(
            'code'     => 500,
            'error'    => 'Invalid Response',
            'message'  => 'Unexpected API response format',
            'response' => $api_response
        ), JSON_PRETTY_PRINT );

        UpMail_Logger::log_email(
            $subject,
            $to,
            'failed',
            $message,
            $error_data
        );

        return array(
            'success' => false,
            'message' => __( 'Failed to send test email. Unexpected API response.', 'upmail' )
        );
    }

    /**
     * Handle test email error.
     *
     * @since 1.0.0
     *
     * @param Exception $e       Exception object.
     * @param string    $subject Email subject.
     * @param string    $to      Recipient email address.
     * @param string    $message Email message content.
     * @return array {
     *     Response data.
     *
     *     @type bool   $success Whether the operation was successful.
     *     @type string $message Error message.
     * }
     */
    public static function handle_test_error( $e, $subject, $to, $message ) {
        $error_data = self::format_error_data( $e );
        $error_message = self::get_error_message( $e );

        // Log the failed test email.
        UpMail_Logger::log_email(
            $subject,
            $to,
            'failed',
            $message,
            $error_data
        );

        return array(
            'success' => false,
            'message' => $error_message
        );
    }

    /**
     * Handle resend response.
     *
     * @since 1.0.0
     *
     * @param array  $api_response API response data.
     * @param string $subject      Email subject.
     * @param string $to           Recipient email address.
     * @param string $message      Email message content.
     * @return array {
     *     Response data.
     *
     *     @type bool   $success Whether the operation was successful.
     *     @type string $message Response message.
     * }
     */
    public static function handle_resend_response( $api_response, $subject, $to, $message ) {
        if ( is_array( $api_response ) && isset( $api_response['success'] ) && true === $api_response['success'] ) {
            // Log the successful resend.
            UpMail_Logger::log_email(
                $subject . ' (Resent)',
                $to,
                'sent',
                $message,
                wp_json_encode( $api_response, JSON_PRETTY_PRINT )
            );

            return array(
                'success' => true,
                'message' => __( 'Email resent successfully.', 'upmail' )
            );
        }

        // Log the failed resend.
        $error_data = wp_json_encode( array(
            'code'     => 500,
            'error'    => 'Invalid Response',
            'message'  => 'Unexpected API response format',
            'response' => $api_response
        ), JSON_PRETTY_PRINT );

        UpMail_Logger::log_email(
            $subject . ' (Resend Failed)',
            $to,
            'failed',
            $message,
            $error_data
        );

        return array(
            'success' => false,
            'message' => __( 'Failed to resend email. Unexpected API response.', 'upmail' )
        );
    }

    /**
     * Handle resend error.
     *
     * @since 1.0.0
     *
     * @param Exception $e       Exception object.
     * @param string    $subject Email subject.
     * @param string    $to      Recipient email address.
     * @param string    $message Email message content.
     * @return array {
     *     Response data.
     *
     *     @type bool   $success Whether the operation was successful.
     *     @type string $message Error message.
     * }
     */
    public static function handle_resend_error( $e, $subject, $to, $message ) {
        $error_data = self::format_error_data( $e );
        $error_message = self::get_error_message( $e );

        // Log the failed resend.
        UpMail_Logger::log_email(
            $subject . ' (Resend Failed)',
            $to,
            'failed',
            $message,
            $error_data
        );

        return array(
            'success' => false,
            'message' => $error_message
        );
    }

    /**
     * Format error data from exception.
     *
     * @since 1.0.0
     *
     * @param Exception $e Exception object.
     * @return string Formatted error data.
     */
    private static function format_error_data( $e ) {
        $error_data = $e->getMessage();
        
        // Try to parse JSON error message.
        $json_error = json_decode( $error_data, true );
        if ( JSON_ERROR_NONE === json_last_error() ) {
            $error_data = wp_json_encode( $json_error, JSON_PRETTY_PRINT );
        }
        
        return $error_data;
    }

    /**
     * Get error message from exception.
     *
     * @since 1.0.0
     *
     * @param Exception $e Exception object.
     * @return string Error message.
     */
    private static function get_error_message( $e ) {
        $error_message = $e->getMessage();

        // Try to parse JSON error message.
        $json_error = json_decode( $error_message, true );
        if ( JSON_ERROR_NONE === json_last_error() && isset( $json_error['message'] ) ) {
            $error_message = $json_error['message'];
        }

        return $error_message;
    }
} 