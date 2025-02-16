/**
 * Email Logs JavaScript functionality.
 *
 * Handles email logs viewing and interactions in the admin interface.
 * @since 1.0.0
 */
jQuery(document).ready(function($) {
    // Initialize datepicker
    $('.datepicker').datepicker({
        dateFormat: 'yy-mm-dd',
        maxDate: '0'
    });

    // Handle filter button click
    $('.filter-button').on('click', function() {
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();
        const status = upmail_params.current_status;
        const search = $('#search').val();

        const url = new URL(window.location.href);
        url.searchParams.set('tab', 'logs');
        if (startDate) url.searchParams.set('start_date', startDate);
        if (endDate) url.searchParams.set('end_date', endDate);
        if (status) url.searchParams.set('status', status);
        if (search) url.searchParams.set('search', search);

        window.location.href = url.toString();
    });

    // Handle search button click
    $('.search-button').on('click', function() {
        const search = $('#search').val();
        if (search) {
            const url = new URL(window.location.href);
            url.searchParams.set('search', search);
            window.location.href = url.toString();
        }
    });

    // Enter kezelése a keresésnél
    $('#search').on('keypress', function(e) {
        if (e.which === 13) {
            $('.search-button').click();
        }
    });

    // Per page változás kezelése
    $('.per-page').on('change', function() {
        var url = new URL(window.location.href);
        url.searchParams.set('per_page', $(this).val());
        window.location.href = url.toString();
    });

    // Email újraküldés
    $('.resend-email').on('click', function() {
        var id = $(this).data('id');
        if (confirm(upmail_params.i18n.confirm_resend)) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'upmail_resend_email',
                    id: id,
                    nonce: upmail_params.nonce.resend
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data);
                    }
                }
            });
        }
    });

    // Email törlés
    $('.delete-email').on('click', function() {
        var id = $(this).data('id');
        if (confirm(upmail_params.i18n.confirm_delete)) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'upmail_delete_log',
                    id: id,
                    nonce: upmail_params.nonce.delete
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                }
            });
        }
    });

    // Összes log törlése
    $('.delete-all-logs').on('click', function() {
        if (confirm(upmail_params.i18n.confirm_delete_all)) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'upmail_delete_all_logs',
                    nonce: upmail_params.nonce.delete_all
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                }
            });
        }
    });

    // Handle view email details
    $('.view-email').on('click', function(e) {
        e.preventDefault();
        
        const id = $(this).data('id');
        const modal = $('#upmail-email-modal');
        const content = modal.find('.email-content');
        
        content.html(upmail_params.i18n.loading);
        modal.show();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'upmail_get_email_details',
                id: id,
                nonce: upmail_params.nonce.view_details
            },
            success: function(response) {
                if (response.success) {
                    const email = response.data.email;
                    const html = `
                        <div class="email-details">
                            <h3>${upmail_params.i18n.email_details || 'Email Details'}</h3>
                            <div class="email-details-row">
                                <strong>${upmail_params.i18n.subject}</strong>
                                <div>${email.subject}</div>
                            </div>
                            <div class="email-details-row">
                                <strong>${upmail_params.i18n.to}</strong>
                                <div>${email.to_email}</div>
                            </div>
                            <div class="email-details-row">
                                <strong>${upmail_params.i18n.status}</strong>
                                <div>${email.status}</div>
                            </div>
                            <div class="email-details-row">
                                <strong>${upmail_params.i18n.date}</strong>
                                <div>${email.date_time}</div>
                            </div>
                            <div class="email-message">
                                <strong>${upmail_params.i18n.message}</strong>
                                <div>${email.message}</div>
                            </div>
                            <div class="api-response">
                                <strong>${upmail_params.i18n.api_response}</strong>
                                <pre>${JSON.stringify(email.api_response, null, 2)}</pre>
                            </div>
                        </div>`;
                    
                    content.html(html);
                } else {
                    content.html(`<div class="error">${response.data.message}</div>`);
                }
            },
            error: function() {
                content.html(`<div class="error">${upmail_params.i18n.load_error}</div>`);
            }
        });
    });
    
    // Close modal
    $('.modal-close').on('click', function() {
        $(this).closest('.upmail-modal').hide();
    });
    
    // Close modal on outside click
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('upmail-modal')) {
            $('.upmail-modal').hide();
        }
    });
}); 