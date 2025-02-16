<?php
/**
 * API AJAX Handler Class
 *
 * Handles all API-related AJAX operations.
 *
 * @package UpMail
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * UpMail API AJAX Handler Class
 *
 * @since 1.0.0
 */
class UpMail_API_Ajax_Handler {

    /**
     * Handle API key validation via AJAX.
     *
     * @since 1.0.0
     */
    public static function handle_api_key_validation() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'upmail_validate_api_key', 'nonce', false ) ) {
            wp_send_json_error( array(
                'message' => __( 'Invalid security token.', 'upmail' )
            ) );
        }

        // Check user capability.
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array(
                'message' => __( 'You do not have permission to perform this action.', 'upmail' )
            ) );
        }

        // Get the current API key.
        $api_key = UpMail::get_instance()->get_api_key();
        if ( empty( $api_key ) ) {
            wp_send_json_error( array(
                'message' => __( 'No API key configured.', 'upmail' )
            ) );
        }

        try {
            // Create API instance and validate key.
            $api = new UpMail_API( $api_key );
            $is_valid = $api->validate_api_key();

            // Store validation result.
            UpMail_Validator::store_validation_status( $is_valid );

            if ( $is_valid ) {
                wp_send_json_success( array(
                    'is_valid' => true,
                    'message'  => __( 'API key is valid.', 'upmail' )
                ) );
            } else {
                wp_send_json_error( array(
                    'is_valid' => false,
                    'message'  => __( 'API key is invalid. Please check your key and try again.', 'upmail' )
                ) );
            }
        } catch ( Exception $e ) {
            wp_send_json_error( array(
                'is_valid' => false,
                'message'  => $e->getMessage()
            ) );
        }
    }
} 