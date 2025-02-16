<?php
/**
 * API Key field template
 *
 * Displays the API key input field and validation status in the settings page.
 *
 * @package UpMail
 * @subpackage Admin/Views
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$status = UpMail_Validator::get_validation_status();
$is_valid = $status['is_valid'];
?>

<div class="api-key-container">
    <?php if ( $has_api_key ) : ?>
        <div class="api-key-display">
            <div class="api-key-mask">
                <?php echo str_repeat( '•', 32 ); ?>
            </div>
            <div class="api-key-status <?php echo $is_valid ? 'valid' : 'invalid'; ?>">
                <span class="dashicons <?php echo $is_valid ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                <?php 
                if ( $is_valid ) {
                    esc_html_e( 'API Key Valid', 'upmail' );
                } else {
                    esc_html_e( 'API Key Invalid', 'upmail' );
                }
                ?>
            </div>
            <button type="button" class="button validate-api-key">
                <?php esc_html_e( 'Validate Now', 'upmail' ); ?>
            </button>
            <button type="button" class="button change-api-key">
                <?php esc_html_e( 'Change API Key', 'upmail' ); ?>
            </button>
        </div>
        <div class="api-key-input" style="display: none;">
            <input type="password" 
                   id="upmail_api_key" 
                   name="upmail_api_key" 
                   class="regular-text"
                   value="<?php echo esc_attr( $api_key ); ?>"
                   placeholder="<?php esc_attr_e( 'Enter your API key', 'upmail' ); ?>"
            />
            <button type="button" class="button button-primary save-api-key">
                <?php esc_html_e( 'Save API Key', 'upmail' ); ?>
            </button>
            <button type="button" class="button button-danger reset-api-data">
                <?php esc_html_e( 'Reset API Data', 'upmail' ); ?>
            </button>
            <button type="button" class="button cancel-api-key">
                <?php esc_html_e( 'Cancel', 'upmail' ); ?>
            </button>
        </div>
    <?php else : ?>
        <div class="api-key-input">
            <input type="password" 
                   id="upmail_api_key" 
                   name="upmail_api_key" 
                   class="regular-text"
                   value=""
                   placeholder="<?php esc_attr_e( 'Enter your API key', 'upmail' ); ?>"
            />
        </div>
    <?php endif; ?>
</div>

<p class="description">
    <?php esc_html_e( 'Enter your API key. The key will be stored securely in an encrypted format.', 'upmail' ); ?>
</p> 