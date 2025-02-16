<?php
/**
 * API Class
 *
 * Handles communication with the Plunk API service.
 *
 * @package UpMail
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * UpMail API Class
 *
 * @since 1.0.0
 */
class UpMail_API {

    /**
     * The API key.
     *
     * @since 1.0.0
     * @var string
     */
    private $api_key = '';

    /**
     * The API base URL.
     *
     * @since 1.0.0
     * @var string
     */
    private $api_base_url = 'https://mail.api.uptools.io';

    /**
     * Constructor.
     *
     * @since 1.0.0
     *
     * @param string $api_key The API key (encrypted or plain).
     */
    public function __construct( $api_key ) {
        // Try to decrypt the key if it's encrypted.
        try {
            $decrypted_key = UpMail_Encryption::decrypt( $api_key );
            $this->api_key = $decrypted_key;
        } catch ( Exception $e ) {
            // If decryption fails, assume it's already a plain key.
            $this->api_key = $api_key;
        }
    }

    /**
     * Validate API key by trying to fetch contacts.
     *
     * @since 1.0.0
     *
     * @throws Exception If validation fails.
     * @return bool Whether the API key is valid.
     */
    public function validate_api_key() {
        if ( empty( $this->api_key ) ) {
            throw new Exception( __( 'API key is required', 'upmail' ) );
        }

        // Try to make a test request to the contacts endpoint.
        $response = wp_remote_get( $this->api_base_url . '/v1/contacts', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'timeout' => 15,
        ) );

        if ( is_wp_error( $response ) ) {
            throw new Exception( $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        
        // API key is valid only if we get a 200 response.
        return 200 === $code;
    }

    /**
     * Clean email address by removing name part.
     *
     * @since 1.0.0
     *
     * @param string $email Email address possibly with name part.
     * @return string Clean email address.
     */
    private function clean_email( $email ) {
        // If email is in format "Name <email@domain.com>".
        if ( preg_match( '/<(.+?)>/', $email, $matches ) ) {
            return trim( $matches[1] );
        }
        // If it's just an email address.
        return trim( $email );
    }

    /**
     * Send email using API.
     *
     * @since 1.0.0
     *
     * @param array $atts {
     *     Email attributes.
     *
     *     @type string|array $to      Recipients email address(es).
     *     @type string       $subject Email subject.
     *     @type string       $message Email message.
     *     @type array        $headers Email headers.
     * }
     * @throws Exception If sending fails.
     * @return array API response data.
     */
    public function send_email( $atts ) {
        if ( empty( $this->api_key ) ) {
            throw new Exception( wp_json_encode( array(
                'code'    => 400,
                'error'   => 'Configuration Error',
                'message' => 'API key is required'
            ) ) );
        }

        // Extract email parameters.
        $to = isset( $atts['to'] ) ? $atts['to'] : '';
        $subject = isset( $atts['subject'] ) ? $atts['subject'] : '';
        $message = isset( $atts['message'] ) ? $atts['message'] : '';
        $headers = isset( $atts['headers'] ) ? $this->parse_headers( $atts['headers'] ) : array();
        
        // Validate required fields.
        if ( empty( $to ) ) {
            throw new Exception( wp_json_encode( array(
                'code'    => 400,
                'error'   => 'Validation Error',
                'message' => 'Recipient email is required'
            ) ) );
        }
        if ( empty( $subject ) ) {
            throw new Exception( wp_json_encode( array(
                'code'    => 400,
                'error'   => 'Validation Error',
                'message' => 'Subject is required'
            ) ) );
        }
        if ( empty( $message ) ) {
            throw new Exception( wp_json_encode( array(
                'code'    => 400,
                'error'   => 'Validation Error',
                'message' => 'Message content is required'
            ) ) );
        }

        // Format recipient(s).
        $recipients = is_array( $to ) ? $to : array( $to );
        
        // Clean up recipient email addresses.
        $cleaned_recipients = array();
        foreach ( $recipients as $recipient ) {
            $cleaned_recipients[] = $this->clean_email( $recipient );
        }

        // Check if the email should be HTML.
        $is_html = false;
        if ( isset( $headers['content-type'] ) ) {
            $is_html = ( false !== strpos( strtolower( $headers['content-type'] ), 'text/html' ) );
        }

        // Extract sender name if available.
        $sender_name = '';
        if ( is_string( $to ) && preg_match( '/^([^<]+)</', $to, $matches ) ) {
            $sender_name = trim( $matches[1] );
        }

        // Prepare request data according to Plunk API format.
        $data = array(
            'to'      => $cleaned_recipients[0], // Primary recipient.
            'subject' => $subject,
            'body'    => $message,
            'html'    => $is_html
        );

        // Add sender name if available.
        if ( ! empty( $sender_name ) ) {
            $data['name'] = $sender_name;
        }

        // Add CC recipients if present.
        if ( ! empty( $headers['cc'] ) ) {
            $cc_recipients = is_array( $headers['cc'] ) ? $headers['cc'] : array( $headers['cc'] );
            $cleaned_cc = array();
            foreach ( $cc_recipients as $cc ) {
                $cleaned_cc[] = $this->clean_email( $cc );
            }
            $data['cc'] = $cleaned_cc;
        }

        // Add BCC recipients if present.
        if ( ! empty( $headers['bcc'] ) ) {
            $bcc_recipients = is_array( $headers['bcc'] ) ? $headers['bcc'] : array( $headers['bcc'] );
            $cleaned_bcc = array();
            foreach ( $bcc_recipients as $bcc ) {
                $cleaned_bcc[] = $this->clean_email( $bcc );
            }
            $data['bcc'] = $cleaned_bcc;
        }

        // Handle From name and email.
        if ( ! empty( $headers['from'] ) ) {
            if ( preg_match( '/(.*?)\s*<(.+?)>/', $headers['from'], $matches ) ) {
                $data['name'] = trim( $matches[1] );
                $data['from'] = trim( $matches[2] );
            } else {
                $data['from'] = trim( $headers['from'] );
            }
        }

        // Add reply-to if set.
        if ( ! empty( $headers['reply-to'] ) ) {
            $data['reply'] = $this->clean_email( $headers['reply-to'] );
        }

        // Log request data for debugging.
        error_log( 'Plunk API Request Data: ' . print_r( $data, true ) );

        // Make API request.
        $response = wp_remote_post( $this->api_base_url . '/v1/send', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'body'    => wp_json_encode( $data ),
            'timeout' => 30,
            'sslverify' => true,
        ) );

        // Check for WP_Error.
        if ( is_wp_error( $response ) ) {
            throw new Exception( wp_json_encode( array(
                'code'    => 500,
                'error'   => 'Connection Error',
                'message' => $response->get_error_message(),
                'request' => $data
            ) ) );
        }

        $raw_body = wp_remote_retrieve_body( $response );
        $body = json_decode( $raw_body, true );
        $code = wp_remote_retrieve_response_code( $response );

        // Log the complete response for debugging.
        error_log( 'Plunk API Response Code: ' . $code );
        error_log( 'Plunk API Response Body: ' . $raw_body );

        // Check for successful response.
        if ( 200 === $code && is_array( $body ) && isset( $body['success'] ) && true === $body['success'] ) {
            return $body;
        }

        // Handle error response.
        $error_message = isset( $body['message'] ) ? $body['message'] : 'Unknown error';
        $error_code = isset( $body['code'] ) ? $body['code'] : $code;
        $error_type = isset( $body['error'] ) ? $body['error'] : 'API Error';

        throw new Exception( wp_json_encode( array(
            'code'     => $error_code,
            'error'    => $error_type,
            'message'  => $error_message,
            'time'     => time(),
            'request'  => $data,
            'response' => $body
        ) ) );
    }

    /**
     * Parse email headers.
     *
     * @since 1.0.0
     *
     * @param string|array $headers Email headers.
     * @return array Parsed headers.
     */
    private function parse_headers( $headers ) {
        $parsed = array();

        if ( is_string( $headers ) ) {
            $headers = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
        }

        foreach ( (array) $headers as $header ) {
            if ( false === strpos( $header, ':' ) ) {
                continue;
            }

            list( $name, $value ) = explode( ':', trim( $header ), 2 );
            $name = strtolower( trim( $name ) );
            $value = trim( $value );

            switch ( $name ) {
                case 'from':
                case 'reply-to':
                case 'cc':
                case 'bcc':
                case 'content-type':
                    $parsed[$name] = $value;
                    break;
            }
        }

        return $parsed;
    }
} 