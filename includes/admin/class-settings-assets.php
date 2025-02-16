<?php
/**
 * Settings Assets Class
 *
 * Handles admin assets (CSS and JavaScript) loading.
 *
 * @package UpMail
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * UpMail Settings Assets Class
 *
 * @since 1.0.0
 */
class UpMail_Settings_Assets {

    /**
     * Initialize the class.
     *
     * @since 1.0.0
     */
    public static function init() {
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'load_admin_styles' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'load_admin_scripts' ) );
    }

    /**
     * Load admin styles.
     *
     * @since 1.0.0
     *
     * @param string $hook Current admin page hook.
     */
    public static function load_admin_styles( $hook ) {
        // Check if we're on the upMail admin page.
        if ( ! isset( $_GET['page'] ) || 'upmail-settings' !== $_GET['page'] ) {
            return;
        }

        // Load main admin styles.
        wp_enqueue_style(
            'upmail-admin',
            plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/css/admin.css',
            array(),
            UPMAIL_VERSION
        );

        // Load API key styles if on settings tab.
        if ( ! isset( $_GET['tab'] ) || 'settings' === $_GET['tab'] ) {
            wp_enqueue_style(
                'upmail-api-key',
                plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/css/api-key.css',
                array(),
                UPMAIL_VERSION
            );
        }

        // Load email logs styles if on logs tab.
        if ( isset( $_GET['tab'] ) && 'logs' === $_GET['tab'] ) {
            wp_enqueue_style(
                'upmail-email-logs',
                plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/css/email-logs.css',
                array(),
                UPMAIL_VERSION
            );
        }

        // Load test mail styles if on test tab.
        if ( isset( $_GET['tab'] ) && 'test' === $_GET['tab'] ) {
            wp_enqueue_style(
                'upmail-test-mail',
                plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/css/test-mail.css',
                array(),
                UPMAIL_VERSION
            );
        }

        // Load jQuery UI Datepicker on overview and logs tabs.
        if ( ! isset( $_GET['tab'] ) || in_array( $_GET['tab'], array( 'overview', 'logs' ), true ) ) {
            wp_enqueue_style(
                'jquery-ui',
                'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css',
                array(),
                '1.12.1'
            );
        }
    }

    /**
     * Load admin scripts.
     *
     * @since 1.0.0
     *
     * @param string $hook Current admin page hook.
     */
    public static function load_admin_scripts( $hook ) {
        // Check if we're on the upMail admin page.
        if ( ! isset( $_GET['page'] ) || 'upmail-settings' !== $_GET['page'] ) {
            return;
        }

        // Load API key scripts if on settings tab.
        if ( ! isset( $_GET['tab'] ) || 'settings' === $_GET['tab'] ) {
            wp_enqueue_script(
                'upmail-api-key',
                plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/js/api-key.js',
                array( 'jquery' ),
                UPMAIL_VERSION,
                true
            );

            // Localize script.
            wp_localize_script(
                'upmail-api-key',
                'upmail_api_params',
                array(
                    'nonce' => array(
                        'validate' => wp_create_nonce( 'upmail_validate_api_key' ),
                        'reset'    => wp_create_nonce( 'upmail_reset_api_key' ),
                    ),
                    'i18n'  => array(
                        'validating'       => esc_html__( 'Validating...', 'upmail' ),
                        'validation_error' => esc_html__( 'Failed to validate API key. Please try again.', 'upmail' ),
                        'confirm_reset'    => esc_html__( 'Are you sure you want to reset the API key? This will remove the current API key.', 'upmail' ),
                        'reset_error'      => esc_html__( 'Failed to reset API key. Please try again.', 'upmail' ),
                        'api_key_required' => esc_html__( 'API Key Required', 'upmail' ),
                    ),
                )
            );
        }

        // Load jQuery UI Datepicker on overview and logs tabs.
        if ( ! isset( $_GET['tab'] ) || in_array( $_GET['tab'], array( 'overview', 'logs' ), true ) ) {
            wp_enqueue_script( 'jquery-ui-datepicker' );
        }

        // Load test mail scripts if on test tab.
        if ( isset( $_GET['tab'] ) && 'test' === $_GET['tab'] ) {
            wp_enqueue_script(
                'upmail-test-mail',
                plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/js/test-mail.js',
                array( 'jquery' ),
                UPMAIL_VERSION,
                true
            );

            // Localize script.
            wp_localize_script(
                'upmail-test-mail',
                'upmail_test_params',
                array(
                    'nonce' => wp_create_nonce( 'upmail_send_test' ),
                    'i18n'  => array(
                        'email_required' => esc_html__( 'Please enter a recipient email address.', 'upmail' ),
                        'sending'        => esc_html__( 'Sending...', 'upmail' ),
                        'server_error'   => esc_html__( 'Failed to send test email. Server error occurred.', 'upmail' ),
                    ),
                )
            );
        }

        // Load email logs scripts if on logs tab.
        if ( isset( $_GET['tab'] ) && 'logs' === $_GET['tab'] ) {
            wp_enqueue_script(
                'upmail-email-logs',
                plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/js/email-logs.js',
                array( 'jquery', 'jquery-ui-datepicker' ),
                UPMAIL_VERSION,
                true
            );

            // Localize script.
            wp_localize_script(
                'upmail-email-logs',
                'upmail_params',
                array(
                    'current_status' => isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '',
                    'nonce'         => array(
                        'resend'       => wp_create_nonce( 'upmail_resend_email' ),
                        'delete'       => wp_create_nonce( 'upmail_delete_log' ),
                        'delete_all'   => wp_create_nonce( 'upmail_delete_all_logs' ),
                        'view_details' => wp_create_nonce( 'upmail_email_details' ),
                    ),
                    'i18n'          => array(
                        'confirm_resend'    => esc_html__( 'Are you sure you want to resend this email?', 'upmail' ),
                        'confirm_delete'    => esc_html__( 'Are you sure you want to delete this log?', 'upmail' ),
                        'confirm_delete_all' => esc_html__( 'Are you sure you want to delete all logs? This action cannot be undone.', 'upmail' ),
                        'loading'           => esc_html__( 'Loading...', 'upmail' ),
                        'load_error'        => esc_html__( 'Failed to load email details. Please try again.', 'upmail' ),
                        'subject'           => esc_html__( 'Subject', 'upmail' ),
                        'to'                => esc_html__( 'To', 'upmail' ),
                        'status'            => esc_html__( 'Status', 'upmail' ),
                        'date'              => esc_html__( 'Date', 'upmail' ),
                        'message'           => esc_html__( 'Message', 'upmail' ),
                        'api_response'      => esc_html__( 'API Response', 'upmail' ),
                        'email_details'     => esc_html__( 'Email Details', 'upmail' ),
                    ),
                )
            );
        }

        // Load Chart.js only on overview tab.
        if ( ! isset( $_GET['tab'] ) || 'overview' === $_GET['tab'] ) {
            wp_enqueue_script(
                'chart-js',
                'https://cdn.jsdelivr.net/npm/chart.js',
                array( 'jquery' ),
                '4.4.1',
                true
            );
        }
    }
} 