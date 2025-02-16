<?php
/**
 * Settings Tabs Class
 *
 * Handles settings page tabs and their content rendering.
 *
 * @package UpMail
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * UpMail Settings Tabs Class
 *
 * @since 1.0.0
 */
class UpMail_Settings_Tabs {

    /**
     * Initialize the class.
     *
     * @since 1.0.0
     */
    public static function init() {
        // No initialization needed for now.
    }

    /**
     * Render settings page.
     *
     * @since 1.0.0
     */
    public static function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'overview';
        ?>
        <div class="wrap upmail-admin">
            <div class="upmail-header">
                <div class="upmail-header-content">
                    <div class="upmail-header-top">
                        <div class="upmail-branding">
                            <div class="upmail-logo">
                                <span class="dashicons dashicons-email"></span>
                                <span class="upmail-title"><?php esc_html_e( 'upMail', 'upmail' ); ?></span>
                            </div>
                            <span class="upmail-version"><?php echo esc_html( sprintf( 'v%s', UPMAIL_VERSION ) ); ?></span>
                        </div>
                        <div class="upmail-header-actions">
                            <a href="?page=upmail-settings&tab=overview" class="upmail-button <?php echo 'overview' === $active_tab ? 'active' : ''; ?>">
                                <span class="dashicons dashicons-chart-bar"></span>
                                <?php esc_html_e( 'Overview', 'upmail' ); ?>
                            </a>
                            <a href="?page=upmail-settings&tab=settings" class="upmail-button <?php echo 'settings' === $active_tab ? 'active' : ''; ?>">
                                <span class="dashicons dashicons-admin-settings"></span>
                                <?php esc_html_e( 'Settings', 'upmail' ); ?>
                            </a>
                            <a href="?page=upmail-settings&tab=test" class="upmail-button <?php echo 'test' === $active_tab ? 'active' : ''; ?>">
                                <span class="dashicons dashicons-email"></span>
                                <?php esc_html_e( 'Test Mail', 'upmail' ); ?>
                            </a>
                            <a href="?page=upmail-settings&tab=logs" class="upmail-button <?php echo 'logs' === $active_tab ? 'active' : ''; ?>">
                                <span class="dashicons dashicons-list-view"></span>
                                <?php esc_html_e( 'Email Logs', 'upmail' ); ?>
                            </a>
                        </div>
                    </div>
                    <p class="upmail-description">
                        <?php esc_html_e( 'Professional SMTP solution for WordPress using upTools API.', 'upmail' ); ?>
                    </p>
                </div>
            </div>

            <div class="upmail-content">
            <?php
            if ( 'overview' === $active_tab ) {
                self::render_overview_tab();
            } elseif ( 'settings' === $active_tab ) {
                self::render_settings_tab();
            } elseif ( 'test' === $active_tab ) {
                self::render_test_tab();
            } elseif ( 'logs' === $active_tab ) {
                self::render_logs_tab();
            }
            ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render overview tab.
     *
     * @since 1.0.0
     */
    private static function render_overview_tab() {
        // Get date range from GET parameters or use default.
        $end_date = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : current_time( 'Y-m-d' );
        $start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : date( 'Y-m-d', strtotime( '-7 days', strtotime( $end_date ) ) );

        // Get total emails sent/logged.
        $total_result = UpMail_Logger::get_logs();
        $total_emails = $total_result['total'];

        // Get active connections (always 1 for now).
        $active_connections = 1;

        // Get active senders (always 1 for now).
        $active_senders = 1;

        // Get email retention setting.
        $retention = __( 'After 14 Days', 'upmail' );

        // Get daily stats for the chart.
        $daily_stats = array();
        $cumulative_total = 0;
        
        // Calculate number of days between start and end date.
        $date_diff = ceil( ( strtotime( $end_date ) - strtotime( $start_date ) ) / ( 60 * 60 * 24 ) );
        
        for ( $i = $date_diff; $i >= 0; $i-- ) {
            $date = date( 'Y-m-d', strtotime( "-$i days", strtotime( $end_date ) ) );
            $result = UpMail_Logger::get_logs( array(
                'start_date' => $date,
                'end_date'   => $date
            ) );
            $daily_count = $result['total'];
            $cumulative_total += $daily_count;
            
            $daily_stats[] = array(
                'date'        => $date,
                'count'       => $daily_count,
                'cumulative'  => $cumulative_total
            );
        }

        // Include the overview template.
        require dirname( __FILE__ ) . '/views/overview.php';
    }

    /**
     * Render settings tab.
     *
     * @since 1.0.0
     */
    private static function render_settings_tab() {
        ?>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'upmail_settings' );
            do_settings_sections( 'upmail-settings' );
            submit_button();
            ?>
        </form>
        <?php
    }

    /**
     * Render test tab.
     *
     * @since 1.0.0
     */
    private static function render_test_tab() {
        UpMail_Test_Mail::render();
    }

    /**
     * Render logs tab.
     *
     * @since 1.0.0
     */
    private static function render_logs_tab() {
        // Get filter parameters.
        $status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
        $start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : '';
        $end_date = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : '';
        $search = isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';
        $per_page = isset( $_GET['per_page'] ) ? absint( $_GET['per_page'] ) : 10;
        $current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;

        // Get logs.
        $args = array(
            'status'     => $status,
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'search'     => $search,
            'per_page'   => $per_page,
            'page'       => $current_page
        );

        $result = UpMail_Logger::get_logs( $args );
        $logs = $result['logs'];
        $total_logs = $result['total'];
        $total_pages = ceil( $total_logs / $per_page );

        // Get counts for tabs.
        $sent_count = UpMail_Logger::get_logs( array( 'status' => 'sent' ) )['total'];
        $failed_count = UpMail_Logger::get_logs( array( 'status' => 'failed' ) )['total'];

        // Include the logs view.
        require dirname( __FILE__ ) . '/views/email-logs.php';
    }
} 