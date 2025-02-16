<?php
/**
 * Header Handler Class
 *
 * Handles email header modifications like from email and name.
 *
 * @package UpMail
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * UpMail Header Handler Class
 *
 * @since 1.0.0
 */
class UpMail_Header_Handler {

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
        
        // Force from email and name.
        add_filter( 'wp_mail_from', array( __CLASS__, 'force_from_email' ) );
        add_filter( 'wp_mail_from_name', array( __CLASS__, 'force_from_name' ) );
    }

    /**
     * Force from email address if set in options.
     *
     * @since 1.0.0
     *
     * @param string $email Default WordPress from email.
     * @return string Modified from email if set, otherwise default email.
     */
    public static function force_from_email( $email ) {
        $forced_email = get_option( 'upmail_from_email' );
        return $forced_email ? $forced_email : $email;
    }

    /**
     * Force from name if set in options.
     *
     * @since 1.0.0
     *
     * @param string $name Default WordPress from name.
     * @return string Modified from name if set, otherwise default name.
     */
    public static function force_from_name( $name ) {
        $forced_name = get_option( 'upmail_from_name' );
        return $forced_name ? $forced_name : $name;
    }
} 