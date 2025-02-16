<?php
/**
 * Encryption handler class
 *
 * Handles API key encryption and decryption using OpenSSL.
 *
 * @package UpMail
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * UpMail Encryption Class
 *
 * @since 1.0.0
 */
class UpMail_Encryption {

    /**
     * Encrypt API key using OpenSSL.
     *
     * @since 1.0.0
     *
     * @param string $api_key The API key to encrypt.
     * @return string Encrypted API key or empty string if encryption fails.
     */
    public static function encrypt( $api_key ) {
        if ( empty( $api_key ) ) {
            return '';
        }

        $salt = wp_salt( 'auth' );
        return openssl_encrypt(
            $api_key,
            'AES-256-CBC',
            $salt,
            0,
            substr( $salt, 0, 16 )
        );
    }

    /**
     * Decrypt API key using OpenSSL.
     *
     * @since 1.0.0
     *
     * @param string $encrypted_key The encrypted API key to decrypt.
     * @return string Decrypted API key or empty string if decryption fails.
     */
    public static function decrypt( $encrypted_key ) {
        if ( empty( $encrypted_key ) ) {
            return '';
        }

        $salt = wp_salt( 'auth' );
        $decrypted = openssl_decrypt(
            $encrypted_key,
            'AES-256-CBC',
            $salt,
            0,
            substr( $salt, 0, 16 )
        );

        return false !== $decrypted ? $decrypted : '';
    }
} 