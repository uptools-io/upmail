<?php
/**
 * API Handler Class
 *
 * Handles API key validation and scheduled checks.
 *
 * @package UpMail
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * UpMail API Handler Class
 *
 * @since 1.0.0
 */
class UpMail_API_Handler {

    /**
     * The main plugin instance.
     *
     * @since 1.0.0
     * @var UpMail_Base
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
        
        // Schedule API key validation.
        if ( ! wp_next_scheduled( 'upmail_validate_api_key_cron' ) ) {
            wp_schedule_event( time(), 'hourly', 'upmail_validate_api_key_cron' );
        }
        add_action( 'upmail_validate_api_key_cron', array( __CLASS__, 'validate_api_key_cron' ) );
    }

    /**
     * Validate API key via AJAX.
     *
     * Handles the AJAX request to validate an API key, including rate limiting
     * and secure storage of valid keys.
     *
     * @since 1.0.0
     */
    public static function validate_api_key() {
        // Verify nonce and capability.
        check_ajax_referer( 'upmail_validate_api_key', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You do not have permission to perform this action.', 'upmail' ) );
        }

        try {
            // Rate limiting.
            UpMail_Validator::check_rate_limit( get_current_user_id() );

            // Get and validate API key.
            $api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( $_POST['api_key'] ) : '';
            UpMail_Validator::validate_format( $api_key );

            // Validate with API.
            $api = new UpMail_API( $api_key );
            $is_valid = $api->validate_api_key();
            
            if ( $is_valid ) {
                // Encrypt and save the valid API key.
                $encrypted_key = UpMail_Encryption::encrypt( $api_key );
                update_option( 'upmail_api_key', $encrypted_key );
                
                // Reset validation attempts on success.
                UpMail_Validator::reset_rate_limit( get_current_user_id() );
                
                wp_send_json_success( __( 'API key is valid and has been saved securely.', 'upmail' ) );
            } else {
                wp_send_json_error( __( 'Invalid API key. Please check your key and try again.', 'upmail' ) );
            }
        } catch ( Exception $e ) {
            error_log( 'upMail API Validation Error: ' . $e->getMessage() );
            wp_send_json_error( $e->getMessage() );
        }
    }

    /**
     * Cron job to validate API key.
     *
     * Scheduled task that periodically validates the stored API key
     * and updates the validation status.
     *
     * @since 1.0.0
     */
    public static function validate_api_key_cron() {
        try {
            $api_key = self::$plugin->get_api_key();
            if ( empty( $api_key ) ) {
                update_option( 'upmail_api_last_check', array(
                    'valid' => false,
                    'time'  => time(),
                    'error' => 'API key is empty'
                ) );
                return;
            }

            $api = new UpMail_API( $api_key );
            $is_valid = $api->validate_api_key();
            
            update_option( 'upmail_api_last_check', array(
                'valid' => $is_valid,
                'time'  => time(),
                'error' => $is_valid ? '' : 'API key validation failed'
            ) );
        } catch ( Exception $e ) {
            update_option( 'upmail_api_last_check', array(
                'valid' => false,
                'time'  => time(),
                'error' => $e->getMessage()
            ) );
        }
    }

    /**
     * Reset API key and related data.
     *
     * @since 1.0.0
     */
    public static function reset_api_key() {
        // Verify nonce and capability.
        check_ajax_referer( 'upmail_reset_api_key', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array(
                'message' => __( 'You do not have permission to perform this action.', 'upmail' )
            ) );
        }

        // Delete API key
        delete_option( 'upmail_api_key' );
        
        // Delete validation status
        delete_option( 'upmail_api_key_status' );
        
        // Delete API last check
        delete_option( 'upmail_api_last_check' );

        wp_send_json_success( array(
            'message' => __( 'API key and related data have been reset successfully.', 'upmail' )
        ) );
    }
} 