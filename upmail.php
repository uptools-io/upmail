<?php
/**
 * Plugin Name: upMail
 * Plugin URI: https://uptools.io/upmail
 * Description: Professional SMTP solution for WordPress using upTools API.
 * Version: 1.0.1
 * Author: upTools Devs
 * Author URI: https://uptools.io
 * Text Domain: upmail
 * Domain Path: /languages
 *
 * @package UpMail
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants.
define( 'UPMAIL_VERSION', '1.0.1' );
define( 'UPMAIL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'UPMAIL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include required files.
require_once UPMAIL_PLUGIN_DIR . 'includes/core/class-encryption.php';
require_once UPMAIL_PLUGIN_DIR . 'includes/core/class-validator.php';
require_once UPMAIL_PLUGIN_DIR . 'includes/core/class-logger.php';
require_once UPMAIL_PLUGIN_DIR . 'includes/api/class-api.php';
require_once UPMAIL_PLUGIN_DIR . 'includes/admin/class-settings.php';
require_once UPMAIL_PLUGIN_DIR . 'includes/admin/class-settings-fields.php';
require_once UPMAIL_PLUGIN_DIR . 'includes/admin/class-settings-tabs.php';
require_once UPMAIL_PLUGIN_DIR . 'includes/admin/class-settings-assets.php';
require_once UPMAIL_PLUGIN_DIR . 'includes/admin/class-notices.php';
require_once UPMAIL_PLUGIN_DIR . 'includes/admin/class-test-mail.php';
require_once UPMAIL_PLUGIN_DIR . 'includes/admin/class-email-logs.php';
require_once UPMAIL_PLUGIN_DIR . 'includes/admin/class-stats-controller.php';

// Include handlers.
require_once UPMAIL_PLUGIN_DIR . 'includes/core/handlers/class-upmail-ajax-handler.php';
require_once UPMAIL_PLUGIN_DIR . 'includes/core/handlers/class-upmail-api-handler.php';
require_once UPMAIL_PLUGIN_DIR . 'includes/core/handlers/class-upmail-header-handler.php';

// Include email handlers.
require_once UPMAIL_PLUGIN_DIR . 'includes/core/handlers/email/class-upmail-response-handler.php';
require_once UPMAIL_PLUGIN_DIR . 'includes/core/handlers/email/class-upmail-test-email-handler.php';
require_once UPMAIL_PLUGIN_DIR . 'includes/core/handlers/email/class-upmail-resend-handler.php';
require_once UPMAIL_PLUGIN_DIR . 'includes/core/handlers/email/class-upmail-email-handler.php';

require_once UPMAIL_PLUGIN_DIR . 'includes/class-upmail.php';

/**
 * Plugin activation hook.
 *
 * Initializes the plugin by creating necessary database tables
 * and scheduling recurring tasks.
 *
 * @since 1.0.0
 */
function upmail_activate() {
    // Initialize logger to create table.
    UpMail_Logger::init();
    
    // Set version.
    update_option( 'upmail_version', UPMAIL_VERSION );

    // Schedule API key validation.
    if ( ! wp_next_scheduled( 'upmail_hourly_api_validation' ) ) {
        wp_schedule_event( time(), 'hourly', 'upmail_hourly_api_validation' );
    }
}
register_activation_hook( __FILE__, 'upmail_activate' );

/**
 * Plugin deactivation hook.
 *
 * Cleans up by removing database tables and scheduled tasks.
 *
 * @since 1.0.0
 */
function upmail_deactivate() {
    // Drop logs table.
    UpMail_Logger::drop_table();
    
    // Delete version.
    delete_option( 'upmail_version' );

    // Clear scheduled hooks.
    wp_clear_scheduled_hook( 'upmail_hourly_api_validation' );
}
register_deactivation_hook( __FILE__, 'upmail_deactivate' );

/**
 * Hourly API key validation.
 *
 * Validates the API key if it hasn't been validated recently.
 *
 * @since 1.0.0
 */
function upmail_hourly_api_validation() {
    $api_key = UpMail::get_instance()->get_api_key();
    if ( ! empty( $api_key ) && UpMail_Validator::needs_validation() ) {
        UpMail_Validator::validate_api_key( $api_key );
    }
}
add_action( 'upmail_hourly_api_validation', 'upmail_hourly_api_validation' );

/**
 * Initialize the plugin.
 *
 * Sets up the logger, test mail functionality, and email logs.
 *
 * @since 1.0.0
 * @return UpMail Plugin instance.
 */
function upmail_init() {
    // Initialize logger.
    UpMail_Logger::init();
    
    // Initialize test mail.
    UpMail_Test_Mail::init();
    
    // Initialize email logs.
    UpMail_Email_Logs::init();
    
    // Initialize settings assets
    UpMail_Settings_Assets::init();
    
    return UpMail::get_instance();
}
add_action( 'init', 'upmail_init' );

/**
 * Ensure this plugin loads first.
 *
 * @since 1.0.0
 * @param array $plugins Array of plugin paths.
 * @return array Modified array of plugin paths.
 */
function upmail_ensure_load_first( $plugins ) {
    $plugin_path = 'upmail/upmail.php';
    $index = array_search( $plugin_path, $plugins );
    
    if ( false !== $index && 0 !== $index ) {
        unset( $plugins[$index] );
        array_unshift( $plugins, $plugin_path );
    }
    
    return $plugins;
}
add_filter( 'pre_update_option_active_plugins', 'upmail_ensure_load_first' );

if ( ! function_exists( 'wp_mail' ) ) :
    /**
     * Override WordPress mail function.
     *
     * @since 1.0.0
     * @param string|array $to          Array or comma-separated list of email addresses to send message.
     * @param string      $subject     Email subject.
     * @param string      $message     Message contents.
     * @param string|array $headers    Optional. Additional headers.
     * @param string|array $attachments Optional. Files to attach.
     * @return bool Whether the email was sent successfully.
     */
    function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
        $atts = array(
            'to'          => $to,
            'subject'     => $subject,
            'message'     => $message,
            'headers'     => $headers,
            'attachments' => $attachments,
        );
        return UpMail_Email_Handler::send_mail( false, $atts );
    }
endif; 