<?php
/**
 * Settings Fields Class
 *
 * Handles settings field callbacks and rendering.
 *
 * @package UpMail
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * UpMail Settings Fields Class
 *
 * @since 1.0.0
 */
class UpMail_Settings_Fields {

    /**
     * Initialize the class.
     *
     * @since 1.0.0
     */
    public static function init() {
        // No initialization needed for now.
    }

    /**
     * Settings section callback.
     *
     * @since 1.0.0
     */
    public static function settings_section_callback() {
        ?>
        <div class="section-title">
            <strong><?php esc_html_e( 'API Settings', 'upmail' ); ?></strong>
        </div>
        <p><?php esc_html_e( 'Configure your email sending settings below.', 'upmail' ); ?></p>
        <?php
    }

    /**
     * API Key field callback.
     *
     * @since 1.0.0
     */
    public static function api_key_field_callback() {
        $api_key = UpMail::get_instance()->get_api_key();
        $has_api_key = ! empty( $api_key );
        require dirname( __FILE__ ) . '/views/api-key-field.php';
    }

    /**
     * Disable all emails field callback.
     *
     * @since 1.0.0
     */
    public static function disable_all_emails_field_callback() {
        $disable_all = get_option( 'upmail_disable_all_emails', false );
        ?>
        <label>
            <input type="checkbox" 
                   id="upmail_disable_all_emails"
                   name="upmail_disable_all_emails" 
                   value="1" 
                   <?php checked( $disable_all ); ?> 
            />
            <?php esc_html_e( 'Disable sending all emails. If you enable this, no email will be sent.', 'upmail' ); ?>
        </label>
        <p class="description warning-text" style="color: #d63638; <?php echo ! $disable_all ? 'display: none;' : ''; ?>">
            <?php esc_html_e( 'No Emails will be sent from your WordPress.', 'upmail' ); ?>
        </p>
        <script>
        jQuery(document).ready(function($) {
            $('#upmail_disable_all_emails').on('change', function() {
                $('.warning-text').toggle(this.checked);
            });
        });
        </script>
        <?php
    }

    /**
     * From email field callback.
     *
     * @since 1.0.0
     */
    public static function from_email_field_callback() {
        $from_email = get_option( 'upmail_from_email', get_option( 'admin_email' ) );
        ?>
        <input type="email" 
               id="upmail_from_email" 
               name="upmail_from_email" 
               value="<?php echo esc_attr( $from_email ); ?>" 
               class="regular-text"
        />
        <p class="description">
            <?php esc_html_e( 'This email address will be used as the sender for all outgoing emails.', 'upmail' ); ?>
        </p>
        <?php
    }

    /**
     * From name field callback.
     *
     * @since 1.0.0
     */
    public static function from_name_field_callback() {
        $from_name = get_option( 'upmail_from_name', get_option( 'blogname' ) );
        ?>
        <input type="text" 
               id="upmail_from_name" 
               name="upmail_from_name" 
               value="<?php echo esc_attr( $from_name ); ?>" 
               class="regular-text"
        />
        <p class="description">
            <?php esc_html_e( 'This name will be used as the sender name for all outgoing emails.', 'upmail' ); ?>
        </p>
        <?php
    }

    /**
     * Force settings field callback.
     *
     * @since 1.0.0
     */
    public static function force_settings_field_callback() {
        $force_email = get_option( 'upmail_force_from_email', false );
        $force_name = get_option( 'upmail_force_from_name', false );
        ?>
        <label>
            <input type="checkbox" 
                   name="upmail_force_from_email" 
                   value="1" 
                   <?php checked( $force_email ); ?> 
            />
            <?php esc_html_e( 'Force From Email (override any other plugins)', 'upmail' ); ?>
        </label>
        <br>
        <label>
            <input type="checkbox" 
                   name="upmail_force_from_name" 
                   value="1" 
                   <?php checked( $force_name ); ?> 
            />
            <?php esc_html_e( 'Force From Name (override any other plugins)', 'upmail' ); ?>
        </label>
        <p class="description">
            <?php esc_html_e( 'When enabled, these settings will override any other plugin that tries to modify the From Email or Name.', 'upmail' ); ?>
        </p>
        <?php
    }
} 