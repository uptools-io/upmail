<?php
/**
 * Main UpMail Base Class
 *
 * Handles core functionality and plugin initialization.
 *
 * @package UpMail
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * UpMail Base Class
 *
 * @since 1.0.0
 */
class UpMail_Base {

    /**
     * The single instance of the class.
     *
     * @since 1.0.0
     * @var UpMail_Base|null
     */
    private static $instance = null;

    /**
     * The API key for Plunk.
     *
     * @since 1.0.0
     * @var string
     */
    protected $api_key = '';

    /**
     * The API handler instance.
     *
     * @since 1.0.0
     * @var UpMail_API|null
     */
    protected $api = null;

    /**
     * Main UpMail Instance.
     *
     * Ensures only one instance of UpMail is loaded or can be loaded.
     *
     * @since 1.0.0
     *
     * @return UpMail_Base Main instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Initialize the plugin.
     *
     * @since 1.0.0
     */
    private function init() {
        // Load plugin text domain.
        add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

        // Initialize API with decrypted key.
        $encrypted_key = get_option( 'upmail_api_key', '' );
        $this->api_key = UpMail_Encryption::decrypt( $encrypted_key );
        $this->api     = new UpMail_API( $this->api_key );

        // Initialize handlers.
        UpMail_API_Handler::init( $this );
        UpMail_Email_Handler::init( $this );
        UpMail_Ajax_Handler::init( $this );
        UpMail_Header_Handler::init( $this );

        // Initialize admin components.
        if ( is_admin() ) {
            UpMail_Settings::init();
            UpMail_Notices::init();
        }
    }

    /**
     * Load plugin text domain.
     *
     * @since 1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'upmail',
            false,
            dirname( plugin_basename( __FILE__ ) ) . '/languages'
        );
    }

    /**
     * Get API key.
     *
     * @since 1.0.0
     *
     * @return string Decrypted API key
     */
    public function get_api_key() {
        $encrypted_key = get_option( 'upmail_api_key', '' );
        return UpMail_Encryption::decrypt( $encrypted_key );
    }

    /**
     * Get API instance.
     *
     * @since 1.0.0
     *
     * @return UpMail_API|null API instance
     */
    public function get_api() {
        return $this->api;
    }

    /**
     * Plugin deactivation handler.
     *
     * @since 1.0.0
     */
    public static function deactivate() {
        wp_clear_scheduled_hook( 'upmail_validate_api_key_cron' );
    }
} 