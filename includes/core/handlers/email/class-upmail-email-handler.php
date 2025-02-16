<?php
/**
 * Email Handler Class
 *
 * Handles core email sending functionality and WordPress mail integration.
 *
 * @package UpMail
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * UpMail Email Handler Class
 *
 * @since 1.0.0
 */
class UpMail_Email_Handler {

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
        
        // Initialize sub-handlers.
        UpMail_Test_Email_Handler::init( $plugin );
        UpMail_Resend_Handler::init( $plugin );
        
        // Hook into WordPress mail.
        add_filter( 'wp_mail', array( __CLASS__, 'handle_wp_mail' ), 10, 1 );
        remove_all_filters( 'pre_wp_mail' );
        add_filter( 'pre_wp_mail', array( __CLASS__, 'send_mail' ), 10, 2 );
    }

    /**
     * Handle wp_mail filter to ensure proper header formatting.
     *
     * @since 1.0.0
     *
     * @param array $args Email arguments.
     * @return array Modified email arguments.
     */
    public static function handle_wp_mail( $args ) {
        // Ensure headers are properly formatted.
        if ( ! empty( $args['headers'] ) ) {
            if ( is_string( $args['headers'] ) ) {
                $args['headers'] = explode( "\n", str_replace( "\r\n", "\n", $args['headers'] ) );
            }
            
            // Process Content-Type header.
            $content_type = null;
            $headers = array();
            
            foreach ( (array) $args['headers'] as $header ) {
                if ( false === strpos( $header, ':' ) ) {
                    continue;
                }
                
                list( $name, $value ) = explode( ':', trim( $header ), 2 );
                $name = trim( $name );
                $value = trim( $value );
                
                if ( 'content-type' === strtolower( $name ) ) {
                    $content_type = $value;
                } else {
                    $headers[] = $header;
                }
            }
            
            // Ensure Content-Type is first in the headers array.
            if ( null !== $content_type ) {
                array_unshift( $headers, "Content-Type: $content_type" );
            }
            
            $args['headers'] = $headers;
        }
        
        return $args;
    }

    /**
     * Send mail using Plunk API.
     *
     * @since 1.0.0
     *
     * @param mixed $pre  Whether to short-circuit wp_mail().
     * @param array $atts {
     *     Email attributes.
     *
     *     @type string|array $to      Recipients email address(es).
     *     @type string       $subject Email subject.
     *     @type string       $message Email message.
     *     @type array        $headers Email headers.
     * }
     * @return bool Whether the email was sent successfully.
     */
    public static function send_mail( $pre, $atts ) {
        // Check if email sending is disabled.
        if ( get_option( 'upmail_disable_all_emails', false ) ) {
            // Log skipped email due to disabled sending.
            UpMail_Logger::log_email(
                isset( $atts['subject'] ) ? $atts['subject'] : '',
                isset( $atts['to'] ) ? ( is_array( $atts['to'] ) ? implode( ', ', $atts['to'] ) : $atts['to'] ) : '',
                'skipped',
                isset( $atts['message'] ) ? $atts['message'] : '',
                wp_json_encode( array(
                    'code'    => 200,
                    'status'  => 'Skipped',
                    'message' => 'Email sending is disabled in settings'
                ), JSON_PRETTY_PRINT )
            );
            return true; // Return true to prevent WordPress from trying to send the email.
        }

        $api_key = self::$plugin->get_api_key();
        if ( empty( $api_key ) ) {
            // Log skipped email due to missing API key.
            UpMail_Logger::log_email(
                isset( $atts['subject'] ) ? $atts['subject'] : '',
                isset( $atts['to'] ) ? ( is_array( $atts['to'] ) ? implode( ', ', $atts['to'] ) : $atts['to'] ) : '',
                'failed',
                isset( $atts['message'] ) ? $atts['message'] : '',
                wp_json_encode( array(
                    'code'    => 400,
                    'error'   => 'Configuration Error',
                    'message' => 'API key is not configured'
                ), JSON_PRETTY_PRINT )
            );
            return false;
        }

        // Check if we need to force From Email/Name.
        $force_email = get_option( 'upmail_force_from_email', false );
        $force_name = get_option( 'upmail_force_from_name', false );

        if ( $force_email || $force_name ) {
            if ( ! isset( $atts['headers'] ) || ! is_array( $atts['headers'] ) ) {
                $atts['headers'] = array();
            }

            // Force From Email/Name if enabled.
            if ( $force_email ) {
                $from_email = get_option( 'upmail_from_email' );
                if ( ! empty( $from_email ) ) {
                    // Remove any existing From header.
                    foreach ( $atts['headers'] as $key => $header ) {
                        if ( 0 === stripos( $header, 'From:' ) ) {
                            unset( $atts['headers'][$key] );
                        }
                    }
                    // Add forced From header.
                    if ( $force_name ) {
                        $from_name = get_option( 'upmail_from_name' );
                        $atts['headers'][] = 'From: ' . $from_name . ' <' . $from_email . '>';
                    } else {
                        $atts['headers'][] = 'From: ' . $from_email;
                    }
                }
            } elseif ( $force_name ) {
                $from_name = get_option( 'upmail_from_name' );
                // Only force name if there's an existing From header.
                foreach ( $atts['headers'] as $key => $header ) {
                    if ( 0 === stripos( $header, 'From:' ) ) {
                        if ( preg_match( '/<(.+?)>/', $header, $matches ) ) {
                            $email = $matches[1];
                            $atts['headers'][$key] = 'From: ' . $from_name . ' <' . $email . '>';
                        }
                    }
                }
            }
        }

        // Prepare log data.
        $subject = isset( $atts['subject'] ) ? $atts['subject'] : '';
        $to = isset( $atts['to'] ) ? $atts['to'] : '';
        $message = isset( $atts['message'] ) ? $atts['message'] : '';
        if ( is_array( $to ) ) {
            $to = implode( ', ', $to );
        }

        try {
            // Attempt to send email.
            $api_response = self::$plugin->get_api()->send_email( $atts );
            
            // Log successful email.
            if ( isset( $api_response['success'] ) && true === $api_response['success'] ) {
                UpMail_Logger::log_email(
                    $subject,
                    $to,
                    'sent',
                    $message,
                    wp_json_encode( $api_response, JSON_PRETTY_PRINT )
                );
                return true;
            }
            
            // Log failed email with API response.
            UpMail_Logger::log_email(
                $subject,
                $to,
                'failed',
                $message,
                wp_json_encode( array(
                    'code'     => isset( $api_response['code'] ) ? $api_response['code'] : 500,
                    'error'    => isset( $api_response['error'] ) ? $api_response['error'] : 'API Error',
                    'message'  => isset( $api_response['message'] ) ? $api_response['message'] : 'Unknown error',
                    'response' => $api_response
                ), JSON_PRETTY_PRINT )
            );
            return false;
            
        } catch ( Exception $e ) {
            // Log failed email with exception details.
            UpMail_Logger::log_email(
                $subject,
                $to,
                'failed',
                $message,
                wp_json_encode( array(
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ), JSON_PRETTY_PRINT )
            );
            return false;
        }
    }
} 