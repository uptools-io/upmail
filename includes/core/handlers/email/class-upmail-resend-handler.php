<?php
/**
 * Resend Handler Class
 *
 * Handles email resending functionality.
 *
 * @package UpMail
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * UpMail Resend Handler Class
 *
 * Handles resending of failed or previously sent emails.
 *
 * @since 1.0.0
 */
class UpMail_Resend_Handler {

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
     * Resend an email from log entry.
     *
     * @since 1.0.0
     *
     * @param object $log Email log entry object.
     * @return array {
     *     Response data.
     *
     *     @type bool   $success Whether the operation was successful.
     *     @type string $message Response message.
     * }
     */
    public static function resend_email( $log ) {
        try {
            // Send email using API.
            $api_response = self::$plugin->get_api()->send_email( array(
                'to'      => $log->to_email,
                'subject' => $log->subject,
                'message' => $log->message
            ) );

            return UpMail_Response_Handler::handle_resend_response(
                $api_response,
                $log->subject,
                $log->to_email,
                $log->message
            );
        } catch ( Exception $e ) {
            return UpMail_Response_Handler::handle_resend_error(
                $e,
                $log->subject,
                $log->to_email,
                $log->message
            );
        }
    }
}