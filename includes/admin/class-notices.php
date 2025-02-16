<?php
/**
 * Notices Class
 *
 * Handles admin notices management and display.
 *
 * @package UpMail
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * UpMail Notices Class
 *
 * @since 1.0.0
 */
class UpMail_Notices {

    /**
     * Initialize notices functionality.
     *
     * @since 1.0.0
     */
    public static function init() {
        add_action( 'admin_head', array( __CLASS__, 'remove_unwanted_notices' ) );
    }

    /**
     * Remove unwanted notices on our page.
     *
     * @since 1.0.0
     */
    public static function remove_unwanted_notices() {
        $screen = get_current_screen();
        if ( 'settings_page_upmail-settings' === $screen->id ) {
            remove_all_actions( 'admin_notices' );
            remove_all_actions( 'all_admin_notices' );
            add_action( 'admin_notices', array( __CLASS__, 'show_upmail_notices' ) );
        }
    }

    /**
     * Show only upMail related notices.
     *
     * @since 1.0.0
     */
    public static function show_upmail_notices() {
        /**
         * Fires when displaying upMail admin notices.
         *
         * @since 1.0.0
         */
        do_action( 'upmail_admin_notices' );
    }
} 