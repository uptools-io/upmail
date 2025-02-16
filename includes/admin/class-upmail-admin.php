<?php
/**
 * Admin Class
 *
 * Handles WordPress admin functionality and asset loading.
 *
 * @package UpMail
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * UpMail Admin Class
 *
 * @since 1.0.0
 */
class UpMail_Admin {

    /**
     * Enqueue admin scripts and styles.
     *
     * @since 1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'upmail-admin',
            plugin_dir_url( __FILE__ ) . 'css/admin.css',
            array(),
            UPMAIL_VERSION
        );

        wp_enqueue_style(
            'upmail-settings',
            plugin_dir_url( __FILE__ ) . 'css/settings.css',
            array(),
            UPMAIL_VERSION
        );

        wp_enqueue_script(
            'upmail-admin',
            plugin_dir_url( __FILE__ ) . 'js/admin.js',
            array( 'jquery' ),
            UPMAIL_VERSION,
            true
        );
    }
} 