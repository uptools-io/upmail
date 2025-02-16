<?php
/**
 * Settings Class
 *
 * Handles plugin settings and admin page functionality.
 *
 * @package UpMail
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * UpMail Settings Class
 *
 * @since 1.0.0
 */
class UpMail_Settings {

    /**
     * Initialize settings functionality.
     *
     * @since 1.0.0
     */
    public static function init() {
        // Initialize all required classes.
        UpMail_Settings_Fields::init();
        UpMail_Settings_Tabs::init();
        UpMail_Settings_Assets::init();
        
        add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
        add_action( 'admin_init', array( __CLASS__, 'handle_admin_notices' ) );
    }

    /**
     * Handle admin notices.
     *
     * @since 1.0.0
     */
    public static function handle_admin_notices() {
        // Remove unwanted notices on our plugin page.
        if ( isset( $_GET['page'] ) && 'upmail-settings' === $_GET['page'] ) {
            remove_all_actions( 'admin_notices' );
            remove_all_actions( 'all_admin_notices' );
            
            // Add back only our notices.
            add_action( 'admin_notices', array( __CLASS__, 'show_our_notices' ) );
        }
    }

    /**
     * Show only our plugin's notices.
     *
     * @since 1.0.0
     */
    public static function show_our_notices() {
        if ( isset( $_GET['upmail-notice'] ) && 'settings-updated' === $_GET['upmail-notice'] ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e( 'Settings updated successfully.', 'upmail' ); ?></p>
            </div>
            <?php
        }
    }

    /**
     * Add settings page to WordPress admin menu.
     *
     * @since 1.0.0
     */
    public static function add_settings_page() {
        global $menu;
        
        // Check if upTools menu exists.
        $uptools_exists = false;
        $uptools_position = 100;
        
        foreach ( $menu as $item ) {
            if ( isset( $item[2] ) && 'uptools' === $item[2] ) {
                $uptools_exists = true;
                break;
            }
        }
        
        // Create upTools menu if it doesn't exist.
        if ( ! $uptools_exists ) {
            add_menu_page(
                __( 'upTools', 'upmail' ),
                __( 'upTools', 'upmail' ),
                'manage_options',
                'uptools',
                '',
                'dashicons-screenoptions',
                $uptools_position
            );
        }
        
        // Add upMail as submenu.
        add_submenu_page(
            'uptools',
            __( 'upMail', 'upmail' ),
            __( 'upMail', 'upmail' ),
            'manage_options',
            'upmail-settings',
            array( 'UpMail_Settings_Tabs', 'render_settings_page' )
        );
        
        // Remove duplicate submenu item if we created the parent menu.
        if ( ! $uptools_exists ) {
            remove_submenu_page( 'uptools', 'uptools' );
        }
    }

    /**
     * Register plugin settings.
     *
     * @since 1.0.0
     */
    public static function register_settings() {
        // API Key.
        register_setting(
            'upmail_settings',
            'upmail_api_key',
            array(
                'type'              => 'string',
                'sanitize_callback' => array( __CLASS__, 'sanitize_api_key' ),
                'default'           => ''
            )
        );

        // Disable All Emails.
        register_setting(
            'upmail_settings',
            'upmail_disable_all_emails',
            array(
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default'           => false
            )
        );

        // From Email.
        register_setting(
            'upmail_settings',
            'upmail_from_email',
            array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_email',
                'default'           => get_option( 'admin_email' )
            )
        );

        // From Name.
        register_setting(
            'upmail_settings',
            'upmail_from_name',
            array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => get_option( 'blogname' )
            )
        );

        // Force From Email.
        register_setting(
            'upmail_settings',
            'upmail_force_from_email',
            array(
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default'           => false
            )
        );

        // Force From Name.
        register_setting(
            'upmail_settings',
            'upmail_force_from_name',
            array(
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default'           => false
            )
        );

        // Settings sections.
        add_settings_section(
            'upmail_settings_section',
            '',
            array( 'UpMail_Settings_Fields', 'settings_section_callback' ),
            'upmail-settings'
        );

        // Settings fields.
        add_settings_field(
            'upmail_api_key',
            __( 'upMail API Key', 'upmail' ),
            array( 'UpMail_Settings_Fields', 'api_key_field_callback' ),
            'upmail-settings',
            'upmail_settings_section'
        );

        add_settings_field(
            'upmail_disable_all_emails',
            __( 'Email Simulation', 'upmail' ),
            array( 'UpMail_Settings_Fields', 'disable_all_emails_field_callback' ),
            'upmail-settings',
            'upmail_settings_section'
        );

        add_settings_field(
            'upmail_from_email',
            __( 'From Email', 'upmail' ),
            array( 'UpMail_Settings_Fields', 'from_email_field_callback' ),
            'upmail-settings',
            'upmail_settings_section'
        );

        add_settings_field(
            'upmail_from_name',
            __( 'From Name', 'upmail' ),
            array( 'UpMail_Settings_Fields', 'from_name_field_callback' ),
            'upmail-settings',
            'upmail_settings_section'
        );

        add_settings_field(
            'upmail_force_settings',
            __( 'Force Settings', 'upmail' ),
            array( 'UpMail_Settings_Fields', 'force_settings_field_callback' ),
            'upmail-settings',
            'upmail_settings_section'
        );
    }

    /**
     * Sanitize API key value.
     *
     * @since 1.0.0
     *
     * @param string $value The API key value to sanitize.
     * @return string Sanitized and encrypted API key.
     */
    public static function sanitize_api_key( $value ) {
        // If empty or all bullet characters (••••), keep the old value.
        if ( empty( $value ) || preg_match( '/^[•]+$/', $value ) ) {
            return get_option( 'upmail_api_key', '' );
        }

        // If same as already encrypted value, keep it.
        $current_key = UpMail::get_instance()->get_api_key();
        if ( $value === $current_key ) {
            return get_option( 'upmail_api_key', '' );
        }

        // If new value, encrypt it.
        return UpMail_Encryption::encrypt( $value );
    }
} 