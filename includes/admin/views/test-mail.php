<?php
/**
 * Test Mail template
 *
 * Displays the test email form in the admin interface.
 *
 * @package UpMail
 * @subpackage Admin/Views
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Localize script with translations and nonce.
 *
 * @since 1.0.0
 */
wp_localize_script(
    'upmail-test-mail',
    'upmail_test_params',
    array(
        'nonce' => wp_create_nonce( 'upmail_send_test' ),
        'i18n'  => array(
            'email_required' => __( 'Please enter a recipient email address.', 'upmail' ),
            'sending'        => __( 'Sending...', 'upmail' ),
            'server_error'   => __( 'Failed to send test email. Server error occurred.', 'upmail' ),
        ),
    )
);

// Enqueue required assets.
wp_enqueue_style( 'upmail-test-mail' );
wp_enqueue_script( 'upmail-test-mail' );
?>

<div class="test-mail-form">
    <div class="section-title">
        <strong><?php esc_html_e( 'Send Test Email', 'upmail' ); ?></strong>
    </div>
    <p><?php esc_html_e( 'Use this form to test your email configuration.', 'upmail' ); ?></p>

    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="test_to_email"><?php esc_html_e( 'To Email', 'upmail' ); ?></label>
            </th>
            <td>
                <input type="email" 
                       id="test_to_email" 
                       class="regular-text"
                       value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>"
                />
                <p class="description">
                    <?php esc_html_e( 'Email address where the test email will be sent.', 'upmail' ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="test_subject"><?php esc_html_e( 'Subject', 'upmail' ); ?></label>
            </th>
            <td>
                <input type="text" 
                       id="test_subject" 
                       class="regular-text"
                       value="<?php esc_html_e( 'Test email from upMail', 'upmail' ); ?>"
                />
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="test_message"><?php esc_html_e( 'Message', 'upmail' ); ?></label>
            </th>
            <td>
                <textarea id="test_message" 
                          class="large-text" 
                          rows="5"><?php echo esc_textarea( __( 'This is a test email sent using upMail plugin.', 'upmail' ) ); ?></textarea>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label><?php esc_html_e( 'Content Type', 'upmail' ); ?></label>
            </th>
            <td>
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" 
                               name="test_content_type" 
                               value="text" 
                               checked
                        />
                        <span class="radio-text"><?php esc_html_e( 'Plain Text', 'upmail' ); ?></span>
                    </label>
                    <label class="radio-label">
                        <input type="radio" 
                               name="test_content_type" 
                               value="html"
                        />
                        <span class="radio-text"><?php esc_html_e( 'HTML', 'upmail' ); ?></span>
                    </label>
                </div>
            </td>
        </tr>
    </table>

    <p class="submit">
        <button type="button" id="send_test_email" class="button button-primary">
            <?php esc_html_e( 'Send Test Email', 'upmail' ); ?>
        </button>
        <span id="test_mail_result"></span>
    </p>

    <div id="test_mail_debug" class="test-mail-debug" style="display: none;">
        <h3><?php esc_html_e( 'Debug Information', 'upmail' ); ?></h3>
        <pre></pre>
    </div>
</div> 