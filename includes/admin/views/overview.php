<?php
/**
 * Overview template
 *
 * Displays the email statistics overview page in the admin interface.
 *
 * @package UpMail
 * @subpackage Admin/Views
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get stats data.
$stats = UpMail_Stats_Controller::get_overview_stats();

// Get most common error type for current month.
$current_month_start = date( 'Y-m-01 00:00:00' );
$current_month_end = date( 'Y-m-t 23:59:59' );
$most_common_error = UpMail_Stats_Controller::get_error_stats( $current_month_start, $current_month_end );

// Prepare chart data.
$daily_stats = UpMail_Stats_Controller::get_daily_stats();
$chart_data = array(
    'dailyData'      => array_map( function( $day ) {
        return (int) $day['sent_count'];
    }, $daily_stats ),
    'cumulativeData' => array_map( function( $day ) {
        return (int) $day['cumulative_sent'];
    }, $daily_stats ),
    'labels'         => array_map( function( $day ) {
        return date( 'M d', strtotime( $day['date'] ) );
    }, $daily_stats ),
);

// Enqueue required scripts and styles.
wp_enqueue_style( 'upmail-overview', UPMAIL_PLUGIN_URL . 'assets/css/overview.css', array(), UPMAIL_VERSION );
wp_enqueue_script( 'apexcharts', 'https://cdn.jsdelivr.net/npm/apexcharts', array(), '3.41.0', true );
wp_enqueue_script( 'upmail-overview', UPMAIL_PLUGIN_URL . 'assets/js/overview.js', array( 'jquery', 'jquery-ui-datepicker', 'apexcharts' ), UPMAIL_VERSION, true );
wp_localize_script( 'upmail-overview', 'upmail_chart_data', $chart_data );
?>

<div class="upmail-overview">
    <!-- Stats Cards -->
    <div class="upmail-stats-grid">
        <div class="upmail-stat-card">
            <div class="stat-header">
                <h3><?php esc_html_e( 'Current Month Sent', 'upmail' ); ?></h3>
            </div>
            <div class="stat-value"><?php echo esc_html( number_format( $stats['current_month']['sent'] ) ); ?></div>
            <div class="stat-description"><?php esc_html_e( 'emails sent this month', 'upmail' ); ?></div>
        </div>
        
        <div class="upmail-stat-card">
            <div class="stat-header">
                <h3><?php esc_html_e( 'Current Month Failed', 'upmail' ); ?></h3>
            </div>
            <div class="stat-value"><?php echo esc_html( number_format( $stats['current_month']['failed'] ) ); ?></div>
            <div class="stat-description">
                <?php 
                $error_types = array(
                    'api'    => __( 'API errors', 'upmail' ),
                    'config' => __( 'Configuration issues', 'upmail' ),
                    'other'  => __( 'Other errors', 'upmail' ),
                );
                echo esc_html( sprintf(
                    /* translators: %s: error type */
                    __( 'Most common: %s', 'upmail' ),
                    isset( $most_common_error ) && isset( $error_types[ $most_common_error ] ) ? $error_types[ $most_common_error ] : $error_types['api']
                ) ); 
                ?>
            </div>
        </div>

        <div class="upmail-stat-card">
            <div class="stat-header">
                <h3><?php esc_html_e( 'Total Sent', 'upmail' ); ?></h3>
            </div>
            <div class="stat-value"><?php echo esc_html( number_format( $stats['total']['sent'] ) ); ?></div>
            <div class="stat-description"><?php esc_html_e( 'all time sent emails', 'upmail' ); ?></div>
        </div>

        <div class="upmail-stat-card">
            <div class="stat-header">
                <h3><?php esc_html_e( 'Total Failed', 'upmail' ); ?></h3>
            </div>
            <div class="stat-value"><?php echo esc_html( number_format( $stats['total']['failed'] ) ); ?></div>
            <div class="stat-description"><?php esc_html_e( 'all time failed emails', 'upmail' ); ?></div>
        </div>
    </div>

    <!-- Main Chart -->
    <div class="upmail-chart-card">
        <div class="chart-header">
            <div>
                <h3><?php esc_html_e( 'Email Analytics', 'upmail' ); ?></h3>
                <p class="chart-description"><?php esc_html_e( 'Showing total emails for the last 3 months', 'upmail' ); ?></p>
            </div>
            <div class="upmail-date-range">
                <input type="text" 
                       id="upmail-start-date" 
                       class="date-input" 
                       placeholder="<?php esc_attr_e( 'Start date', 'upmail' ); ?>" 
                       value="<?php echo esc_attr( $start_date ); ?>" 
                />
                <span class="date-separator"><?php esc_html_e( 'to', 'upmail' ); ?></span>
                <input type="text" 
                       id="upmail-end-date" 
                       class="date-input" 
                       placeholder="<?php esc_attr_e( 'End date', 'upmail' ); ?>" 
                       value="<?php echo esc_attr( $end_date ); ?>" 
                />
                <button type="button" class="apply-button" id="upmail-apply-date">
                    <?php esc_html_e( 'Apply', 'upmail' ); ?>
                </button>
            </div>
        </div>
        <div id="upmail-stats-chart"></div>
    </div>
</div> 