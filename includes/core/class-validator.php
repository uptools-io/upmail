<?php
/**
 * API Key validator class
 *
 * Handles API key validation, rate limiting, and status management.
 *
 * @package UpMail
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * UpMail API Key Validator
 *
 * @since 1.0.0
 */
class UpMail_Validator {

    /**
     * Validate API key format.
     *
     * @since 1.0.0
     *
     * @param string $api_key The API key to validate.
     * @return bool True if format is valid.
     * @throws Exception If validation fails.
     */
    public static function validate_format( $api_key ) {
        if ( empty( $api_key ) ) {
            throw new Exception( __( 'API key is required.', 'upmail' ) );
        }

        if ( ! preg_match( '/^[a-zA-Z0-9._-]+$/', $api_key ) ) {
            throw new Exception( __( 'Invalid API key format. The key should only contain letters, numbers, dots, underscores and hyphens.', 'upmail' ) );
        }

        if ( 32 > strlen( $api_key ) ) {
            throw new Exception( __( 'API key is too short. It should be at least 32 characters long.', 'upmail' ) );
        }

        return true;
    }

    /**
     * Rate limit check for API validation.
     *
     * @since 1.0.0
     *
     * @param int $user_id WordPress user ID.
     * @return bool True if within rate limit.
     * @throws Exception If rate limit exceeded.
     */
    public static function check_rate_limit( $user_id ) {
        $rate_key = 'upmail_validation_attempts_' . $user_id;
        $attempts = get_transient( $rate_key );
        
        if ( 5 < $attempts ) {
            throw new Exception( __( 'Too many validation attempts. Please try again later.', 'upmail' ) );
        }
        
        set_transient( $rate_key, ( $attempts ? $attempts + 1 : 1 ), HOUR_IN_SECONDS );
        return true;
    }

    /**
     * Reset rate limit for a user.
     *
     * @since 1.0.0
     *
     * @param int $user_id WordPress user ID.
     */
    public static function reset_rate_limit( $user_id ) {
        delete_transient( 'upmail_validation_attempts_' . $user_id );
    }

    /**
     * Store API key validation status.
     *
     * @since 1.0.0
     *
     * @param bool $is_valid Whether the API key is valid.
     */
    public static function store_validation_status( $is_valid ) {
        update_option( 'upmail_api_key_status', array(
            'is_valid'    => $is_valid,
            'last_check'  => current_time( 'timestamp' ),
        ) );
    }

    /**
     * Get API key validation status.
     *
     * @since 1.0.0
     *
     * @return array {
     *     API key validation status.
     *
     *     @type bool $is_valid    Whether the API key is valid.
     *     @type int  $last_check  Timestamp of last validation check.
     * }
     */
    public static function get_validation_status() {
        $status = get_option( 'upmail_api_key_status' );
        
        if ( ! $status ) {
            return array(
                'is_valid'    => false,
                'last_check'  => 0,
            );
        }
        
        return $status;
    }

    /**
     * Check if validation is needed (hourly check).
     *
     * @since 1.0.0
     *
     * @return bool True if validation is needed.
     */
    public static function needs_validation() {
        $status    = self::get_validation_status();
        $last_check = $status['last_check'];
        $hour_ago  = current_time( 'timestamp' ) - HOUR_IN_SECONDS;
        
        return $last_check < $hour_ago;
    }

    /**
     * Validate API key with the remote service.
     *
     * @since 1.0.0
     *
     * @param string $api_key      The API key to validate.
     * @param bool   $should_store Whether to store the validation result.
     * @return array {
     *     Validation result.
     *
     *     @type bool   $success  Whether the validation request succeeded.
     *     @type bool   $is_valid Whether the API key is valid.
     *     @type string $message  Response message.
     * }
     */
    public static function validate_api_key( $api_key, $should_store = true ) {
        try {
            $api = new UpMail_API( $api_key );
            $is_valid = $api->validate_api_key();
            
            if ( $should_store ) {
                self::store_validation_status( $is_valid );
            }
            
            return array(
                'success'  => true,
                'is_valid' => $is_valid,
                'message'  => $is_valid 
                    ? __( 'API key is valid.', 'upmail' )
                    : __( 'API key is invalid.', 'upmail' ),
            );
        } catch ( Exception $e ) {
            if ( $should_store ) {
                self::store_validation_status( false );
            }
            
            return array(
                'success'  => false,
                'is_valid' => false,
                'message'  => $e->getMessage(),
            );
        }
    }
} 