/**
 * API Key functionality
 *
 * Handles API key validation and display in the admin interface.
 *
 * @package UpMail
 */

jQuery(document).ready(function($) {
    // Handle API key validation
    $('.validate-api-key').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var statusContainer = $('.api-key-status');
        var originalText = button.text();
        
        button.prop('disabled', true).text(upmail_api_params.i18n.validating);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'upmail_validate_api_key',
                nonce: upmail_api_params.nonce.validate
            },
            success: function(response) {
                if (response.success) {
                    statusContainer.removeClass('invalid').addClass('valid')
                        .html('<span class="dashicons dashicons-yes-alt"></span>' + response.data.message);
                } else {
                    statusContainer.removeClass('valid').addClass('invalid')
                        .html('<span class="dashicons dashicons-warning"></span>' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                statusContainer.removeClass('valid').addClass('invalid')
                    .html('<span class="dashicons dashicons-warning"></span>' + upmail_api_params.i18n.validation_error);
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    });

    // Handle API key change
    $('.change-api-key').on('click', function() {
        $('.api-key-display').hide();
        $('.api-key-input').show();
    });

    // Handle API key change cancel
    $('.cancel-api-key').on('click', function() {
        $('.api-key-input').hide();
        $('.api-key-display').show();
    });

    // Handle API key save
    $('.save-api-key').on('click', function() {
        var form = $('form');
        form.submit();
    });

    // Handle API data reset
    $('.reset-api-data').on('click', function() {
        if (confirm(upmail_api_params.i18n.confirm_reset)) {
            var button = $(this);
            button.prop('disabled', true);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'upmail_reset_api_key',
                    nonce: upmail_api_params.nonce.reset
                },
                success: function(response) {
                    if (response.success) {
                        // Clear the input field
                        $('#upmail_api_key').val('');
                        
                        // Update status to invalid
                        $('.api-key-status').removeClass('valid').addClass('invalid')
                            .html('<span class="dashicons dashicons-warning"></span>' + upmail_api_params.i18n.api_key_required);
                            
                        // Hide input section, show display section
                        $('.api-key-input').hide();
                        $('.api-key-display').show();
                        
                        // Reload the page to refresh all states
                        window.location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert(upmail_api_params.i18n.reset_error);
                },
                complete: function() {
                    button.prop('disabled', false);
                }
            });
        }
    });
}); 