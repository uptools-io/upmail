/**
 * Test Mail functionality
 *
 * @package UpMail
 */

jQuery(document).ready(function($) {
    $('#send_test_email').on('click', function() {
        var button = $(this);
        var result = $('#test_mail_result');
        var debug = $('#test_mail_debug');
        
        // Get form values
        var data = {
            action: 'upmail_send_test',
            to: $('#test_to_email').val(),
            subject: $('#test_subject').val(),
            message: $('#test_message').val(),
            html: $('input[name="test_content_type"]:checked').val() === 'html',
            nonce: upmail_test_params.nonce
        };

        // Validate email
        if (!data.to) {
            result.removeClass('success').addClass('error')
                  .html('<span class="emoji">⚠️</span> ' + upmail_test_params.i18n.email_required);
            return;
        }

        // Send test email
        button.prop('disabled', true);
        result.removeClass('success error').html(upmail_test_params.i18n.sending);
        debug.hide();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    result.removeClass('error').addClass('success')
                          .html(response.data.message);
                } else {
                    result.removeClass('success').addClass('error')
                          .html(response.data.message);
                }
                
                // Display debug information if available
                if (response.data && response.data.debug) {
                    debug.find('pre').text(JSON.stringify(response.data.debug, null, 2));
                    debug.show();
                }
            },
            error: function(xhr, status, error) {
                result.removeClass('success').addClass('error')
                      .html('<span class="emoji">❌</span> ' + upmail_test_params.i18n.server_error);
                // Show error details in debug
                debug.find('pre').text(JSON.stringify({
                    status: status,
                    error: error,
                    response: xhr.responseText
                }, null, 2));
                debug.show();
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });
}); 