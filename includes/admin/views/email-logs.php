<?php
/**
 * Email Logs template
 *
 * Displays the email logs page in the admin interface.
 *
 * @package UpMail
 * @subpackage Admin/Views
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get filter parameters.
$status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : '';
$end_date = isset( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) : '';
$search = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
$per_page = isset( $_GET['per_page'] ) ? absint( $_GET['per_page'] ) : 10;
$current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;

// Get logs.
$args = array(
    'status'     => $status,
    'start_date' => $start_date,
    'end_date'   => $end_date,
    'search'     => $search,
    'per_page'   => $per_page,
    'page'       => $current_page,
);

$result = UpMail_Logger::get_logs( $args );
$logs = $result['logs'];
$total_logs = $result['total'];
$total_pages = ceil( $total_logs / $per_page );

// Get counts for tabs.
$sent_count = UpMail_Logger::get_logs( array( 'status' => 'sent' ) )['total'];
$failed_count = UpMail_Logger::get_logs( array( 'status' => 'failed' ) )['total'];
?>

<div class="email-logs-wrap">
    <div class="email-logs-header">
        <div class="filters">
            <div class="status-tabs">
                <a href="?page=upmail-settings&tab=logs" class="<?php echo empty( $status ) ? 'active' : ''; ?>">
                    <?php esc_html_e( 'Email Logs', 'upmail' ); ?>
                    <span class="count"><?php echo esc_html( $total_logs ); ?></span>
                </a>
                <a href="?page=upmail-settings&tab=logs&status=sent" class="<?php echo 'sent' === $status ? 'active' : ''; ?>">
                    <?php esc_html_e( 'Successful', 'upmail' ); ?>
                    <span class="count"><?php echo esc_html( $sent_count ); ?></span>
                </a>
                <a href="?page=upmail-settings&tab=logs&status=failed" class="<?php echo 'failed' === $status ? 'active' : ''; ?>">
                    <?php esc_html_e( 'Failed', 'upmail' ); ?>
                    <span class="count"><?php echo esc_html( $failed_count ); ?></span>
                </a>
            </div>

            <div class="date-range">
                <input type="text" 
                       id="start_date" 
                       name="start_date" 
                       value="<?php echo esc_attr( $start_date ); ?>" 
                       placeholder="<?php esc_attr_e( 'Start date', 'upmail' ); ?>"
                       class="datepicker" 
                />
                <span><?php esc_html_e( 'To', 'upmail' ); ?></span>
                <input type="text" 
                       id="end_date" 
                       name="end_date" 
                       value="<?php echo esc_attr( $end_date ); ?>" 
                       placeholder="<?php esc_attr_e( 'End date', 'upmail' ); ?>"
                       class="datepicker" 
                />
                <button type="button" class="button filter-button secondary">
                    <?php esc_html_e( 'Filter', 'upmail' ); ?>
                </button>
            </div>

            <div class="search">
                <input type="text" 
                       id="search" 
                       name="search" 
                       value="<?php echo esc_attr( $search ); ?>" 
                       placeholder="<?php esc_attr_e( 'Type & press enter...', 'upmail' ); ?>" 
                />
                <button type="button" class="button search-button">
                    <span class="dashicons dashicons-search"></span>
                </button>
            </div>
        </div>
    </div>

    <div class="email-logs-table">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th class="check-column">
                        <input type="checkbox" id="select-all-logs" />
                    </th>
                    <th><?php esc_html_e( 'Subject', 'upmail' ); ?></th>
                    <th><?php esc_html_e( 'To', 'upmail' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'upmail' ); ?></th>
                    <th><?php esc_html_e( 'Date-Time', 'upmail' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'upmail' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $logs ) ) : ?>
                    <?php foreach ( $logs as $log ) : ?>
                        <tr>
                            <td class="check-column">
                                <input type="checkbox" name="log_ids[]" value="<?php echo esc_attr( $log->id ); ?>" />
                            </td>
                            <td><?php echo esc_html( $log->subject ); ?></td>
                            <td><?php echo esc_html( $log->to_email ); ?></td>
                            <td>
                                <span class="status-badge <?php echo esc_attr( $log->status ); ?>">
                                    <?php echo esc_html( $log->status ); ?>
                                </span>
                            </td>
                            <td><?php 
                                $date = isset( $log->date_time ) ? $log->date_time : ( isset( $log->created_at ) ? $log->created_at : null );
                                echo esc_html(
                                    $date ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $date ) ) : __( 'N/A', 'upmail' )
                                ); 
                            ?></td>
                            <td class="actions">
                                <button type="button" 
                                        class="button resend-email" 
                                        data-id="<?php echo esc_attr( $log->id ); ?>">
                                    <?php esc_html_e( 'Resend', 'upmail' ); ?>
                                </button>
                                <button type="button" 
                                        class="button view-email" 
                                        data-id="<?php echo esc_attr( $log->id ); ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                                <button type="button" 
                                        class="button delete-email" 
                                        data-id="<?php echo esc_attr( $log->id ); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6" class="no-logs">
                            <?php esc_html_e( 'No email logs found.', 'upmail' ); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ( $total_pages > 1 ) : ?>
        <div class="tablenav">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php
                    printf(
                        /* translators: %s: number of items */
                        esc_html__( 'Total %s', 'upmail' ),
                        sprintf(
                            /* translators: 1: number of items, 2: plural or singular */
                            esc_html( _n( '%s item', '%s items', $total_logs, 'upmail' ) ),
                            number_format_i18n( $total_logs )
                        )
                    );
                    ?>
                </span>
                <select class="per-page">
                    <option value="10" <?php selected( $per_page, 10 ); ?>>10/page</option>
                    <option value="20" <?php selected( $per_page, 20 ); ?>>20/page</option>
                    <option value="50" <?php selected( $per_page, 50 ); ?>>50/page</option>
                </select>
                <span class="pagination-links">
                    <?php
                    echo wp_kses(
                        paginate_links( array(
                            'base'      => add_query_arg( 'paged', '%#%' ),
                            'format'    => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total'     => $total_pages,
                            'current'   => $current_page,
                        ) ),
                        array(
                            'a'    => array(
                                'href'  => array(),
                                'class' => array(),
                            ),
                            'span' => array(
                                'class'        => array(),
                                'aria-current' => array(),
                            ),
                        )
                    );
                    ?>
                </span>
            </div>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $logs ) ) : ?>
        <div class="bulk-actions">
            <button type="button" class="button delete-all-logs">
                <span class="dashicons dashicons-trash"></span>
                <?php esc_html_e( 'Delete All Logs', 'upmail' ); ?>
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Email Details Modal -->
<div id="upmail-email-modal" class="upmail-modal">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        <div class="email-content">
            <!-- Content will be loaded dynamically -->
        </div>
    </div>
</div>

<div id="email-details-modal" class="upmail-modal" style="display: none;">
    <div class="upmail-modal-content">
        <span class="upmail-modal-close">&times;</span>
        <h3><?php esc_html_e( 'Email Details', 'upmail' ); ?></h3>
        <div class="email-details-content"></div>
    </div>
</div> 