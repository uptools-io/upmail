<?php
/**
 * Main AJAX Handler Class
 *
 * Initializes and coordinates all AJAX operations.
 *
 * @package UpMail
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load specialized handlers.
require_once dirname( __FILE__ ) . '/ajax/class-upmail-api-ajax-handler.php';
require_once dirname( __FILE__ ) . '/ajax/class-upmail-email-ajax-handler.php';
require_once dirname( __FILE__ ) . '/ajax/class-upmail-log-ajax-handler.php';

/**
 * UpMail AJAX Handler Class
 *
 * @since 1.0.0
 */
class UpMail_Ajax_Handler {

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
        
        // Add AJAX handlers.
        add_action( 'wp_ajax_upmail_validate_api_key', array( 'UpMail_API_Ajax_Handler', 'handle_api_key_validation' ) );
        add_action( 'wp_ajax_upmail_reset_api_key', array( 'UpMail_API_Handler', 'reset_api_key' ) );
        add_action( 'wp_ajax_upmail_send_test', array( 'UpMail_Email_Ajax_Handler', 'send_test_email' ) );
        add_action( 'wp_ajax_upmail_delete_log', array( 'UpMail_Log_Ajax_Handler', 'delete_log' ) );
        add_action( 'wp_ajax_upmail_delete_all_logs', array( 'UpMail_Log_Ajax_Handler', 'delete_all_logs' ) );
        add_action( 'wp_ajax_upmail_resend_email', array( 'UpMail_Email_Ajax_Handler', 'resend_email' ) );
        add_action( 'wp_ajax_upmail_view_email', array( 'UpMail_Email_Ajax_Handler', 'view_email' ) );
    }
} 