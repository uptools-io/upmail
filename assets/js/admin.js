/**
 * Admin JavaScript functionality for UpMail plugin.
 *
 * Handles email viewing and modal interactions in the admin interface.
 */
jQuery(document).ready(function($) {

    // Handle email content viewing.
    $('.upmail-view-email').on('click', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        const nonce = $(this).data('nonce');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'upmail_view_email',
                id: id,
                nonce: nonce
            },
            beforeSend: function() {
                // Show loading state.
                $('#upmail-email-modal .modal-content').html('<div class="spinner"></div>');
                $('#upmail-email-modal').show();
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    let content = '<div class="email-details">';
                    
                    content += '<h3>' + data.subject + '</h3>';
                    content += '<p><strong>To:</strong> ' + data.to + '</p>';
                    content += '<p><strong>Date:</strong> ' + data.date + '</p>';
                    content += '<p><strong>Status:</strong> <span class="status-' + data.status + '">' + data.status + '</span></p>';
                    content += '<div class="email-message">' + data.message + '</div>';
                    content += '</div>';
                    
                    $('#upmail-email-modal .modal-content').html(content);
                } else {
                    $('#upmail-email-modal .modal-content').html('<div class="error">' + response.data + '</div>');
                }
            },
            error: function() {
                $('#upmail-email-modal .modal-content').html('<div class="error">An error occurred while loading the email content.</div>');
            }
        });
    });

    // Handle modal close button click.
    $('.modal-close').on('click', function() {
        $('#upmail-email-modal').hide();
    });

    // Handle modal close when clicking outside.
    $(window).on('click', function(e) {
        if ($(e.target).is('#upmail-email-modal')) {
            $('#upmail-email-modal').hide();
        }
    });
    
}); 