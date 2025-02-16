<?php
/**
 * Main UpMail class
 *
 * @package UpMail
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load base class.
require_once dirname( __FILE__ ) . '/core/class-upmail-base.php';

/**
 * Main UpMail Class.
 *
 * This class is a wrapper for UpMail_Base for backwards compatibility.
 *
 * @since 1.0.0
 */
class UpMail extends UpMail_Base {
    // This class is a wrapper for UpMail_Base.
    // It exists only for backwards compatibility.
} 