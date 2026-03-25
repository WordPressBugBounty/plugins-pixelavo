<?php
namespace Pixelavo\Api\Ai;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Encryption Helper for API Keys
 *
 * Provides secure encryption and decryption for sensitive API keys
 * using WordPress salts and OpenSSL encryption
 *
 * @package Pixelavo\Api\Ai
 * @since 1.0.0
 */
class EncryptionHelper {

    /**
     * Encryption method
     */
    private const CIPHER_METHOD = 'AES-256-CBC';

    /**
     * Prefix to identify encrypted values
     */
    private const ENCRYPTED_PREFIX = 'encrypted:';

    /**
     * Get encryption key derived from WordPress salts
     *
     * @return string|false Returns encryption key or false if cannot generate secure key
     */
    private static function get_encryption_key() {
        // Use WordPress AUTH_KEY and SECURE_AUTH_KEY as base for encryption
        $key = '';

        if (defined('AUTH_KEY') && !empty(AUTH_KEY) && AUTH_KEY !== 'put your unique phrase here') {
            $key .= AUTH_KEY;
        }

        if (defined('SECURE_AUTH_KEY') && !empty(SECURE_AUTH_KEY) && SECURE_AUTH_KEY !== 'put your unique phrase here') {
            $key .= SECURE_AUTH_KEY;
        }

        // Check if we have proper WordPress salts
        if (empty($key)) {

            // Try other available salts
            if (defined('LOGGED_IN_KEY') && !empty(LOGGED_IN_KEY) && LOGGED_IN_KEY !== 'put your unique phrase here') {
                $key = LOGGED_IN_KEY;
            } elseif (defined('NONCE_KEY') && !empty(NONCE_KEY) && NONCE_KEY !== 'put your unique phrase here') {
                $key = NONCE_KEY;
            } else {
                // Last resort fallback - log warning
                $key = get_site_url() . 'pixelavo-encryption-fallback-' . wp_salt('auth');
            }
        }

        // Create a consistent 32-byte key for AES-256
        return substr(hash('sha256', $key), 0, 32);
    }

    /**
     * Get initialization vector derived from WordPress salts
     *
     * @return string
     */
    private static function get_iv() {
        // Use different WordPress salts for IV generation
        $iv_base = '';

        if (defined('LOGGED_IN_KEY') && !empty(LOGGED_IN_KEY) && LOGGED_IN_KEY !== 'put your unique phrase here') {
            $iv_base .= LOGGED_IN_KEY;
        }

        if (defined('NONCE_KEY') && !empty(NONCE_KEY) && NONCE_KEY !== 'put your unique phrase here') {
            $iv_base .= NONCE_KEY;
        }

        if (empty($iv_base)) {
            // Try AUTH_SALT and SECURE_AUTH_SALT as fallback
            if (defined('AUTH_SALT') && !empty(AUTH_SALT) && AUTH_SALT !== 'put your unique phrase here') {
                $iv_base = AUTH_SALT;
            } elseif (defined('SECURE_AUTH_SALT') && !empty(SECURE_AUTH_SALT) && SECURE_AUTH_SALT !== 'put your unique phrase here') {
                $iv_base = SECURE_AUTH_SALT;
            } else {
                // Last resort fallback
                $iv_base = get_home_url() . 'pixelavo-iv-fallback-' . wp_salt('nonce');
            }
        }

        // Create a consistent 16-byte IV for AES-256-CBC
        return substr(hash('md5', $iv_base), 0, 16);
    }

    /**
     * Check if OpenSSL is available
     *
     * @return bool
     */
    public static function is_encryption_available() {
        return function_exists('openssl_encrypt') && function_exists('openssl_decrypt');
    }

    /**
     * Encrypt a value
     *
     * @param string $value Plain text value to encrypt
     * @return string|false Encrypted value with prefix, or false on failure
     */
    public static function encrypt($value) {
        if (empty($value)) {
            return $value;
        }

        // If encryption is not available, log warning and return plain value
        if (!self::is_encryption_available()) {
            return $value;
        }

        // Don't double-encrypt
        if (self::is_encrypted($value)) {
            return $value;
        }

        try {
            $encryption_key = self::get_encryption_key();
            $iv = self::get_iv();

            if (empty($encryption_key)) {
                return false;
            }

            $encrypted = openssl_encrypt(
                $value,
                self::CIPHER_METHOD,
                $encryption_key,
                0,
                $iv
            );

            if ($encrypted === false) {
                $openssl_error = openssl_error_string();
                return false;
            }

            // Add prefix to identify encrypted values
            return self::ENCRYPTED_PREFIX . base64_encode($encrypted);

        } catch (\Exception $e) {
            return false;
        } catch (\Error $e) {
            return false;
        }
    }

    /**
     * Decrypt a value
     *
     * @param string $value Encrypted value to decrypt
     * @return string|false Decrypted plain text, or false on failure
     */
    public static function decrypt($value) {
        if (empty($value)) {
            return $value;
        }

        // If it's not encrypted, return as-is (backward compatibility)
        if (!self::is_encrypted($value)) {
            return $value;
        }

        // If encryption is not available but value is marked as encrypted, we have a problem
        if (!self::is_encryption_available()) {
            return false;
        }

        try {
            // Remove prefix and decode
            $encrypted = substr($value, strlen(self::ENCRYPTED_PREFIX));
            $encrypted = base64_decode($encrypted, true); // Strict mode

            if ($encrypted === false) {
                return false;
            }

            $encryption_key = self::get_encryption_key();
            $iv = self::get_iv();

            if (empty($encryption_key)) {
                return false;
            }

            $decrypted = openssl_decrypt(
                $encrypted,
                self::CIPHER_METHOD,
                $encryption_key,
                0,
                $iv
            );

            if ($decrypted === false) {
                $openssl_error = openssl_error_string();
                return false;
            }

            return $decrypted;

        } catch (\Exception $e) {
            return false;
        } catch (\Error $e) {
            return false;
        }
    }

    /**
     * Check if a value is encrypted
     *
     * @param string $value Value to check
     * @return bool
     */
    public static function is_encrypted($value) {
        return is_string($value) && strpos($value, self::ENCRYPTED_PREFIX) === 0;
    }

    /**
     * Get decrypted API key
     *
     * Helper method to get a decrypted API key from settings
     *
     * @param string $ai_provider AI provider name (openai|gemini)
     * @return string|false Decrypted API key or false if not found
     */
    public static function get_decrypted_api_key($ai_provider) {
        $settings = get_option('pixelavo_settings', []);
        $key_field = "{$ai_provider}_api_key";

        if (!isset($settings[$key_field]) || empty($settings[$key_field])) {
            return false;
        }

        return self::decrypt($settings[$key_field]);
    }

    /**
     * Save encrypted API key
     *
     * Helper method to save an encrypted API key to settings
     *
     * @param string $ai_provider AI provider name (openai|gemini)
     * @param string $api_key Plain text API key to encrypt and save
     * @return bool Success status
     */
    public static function save_encrypted_api_key($ai_provider, $api_key) {
        $settings = get_option('pixelavo_settings', []);
        $key_field = "{$ai_provider}_api_key";

        if (empty($api_key)) {
            // If empty, remove the key
            unset($settings[$key_field]);
        } else {
            // Encrypt and save
            $encrypted = self::encrypt($api_key);

            if ($encrypted === false) {
                return false;
            }

            $settings[$key_field] = $encrypted;
        }

        return update_option('pixelavo_settings', $settings);
    }

    /**
     * Verify encryption setup
     *
     * Check if the encryption system is properly configured
     *
     * @return array Status information
     */
    public static function verify_setup() {
        $status = [
            'openssl_available' => self::is_encryption_available(),
            'auth_key_defined' => defined('AUTH_KEY') && !empty(AUTH_KEY),
            'secure_auth_key_defined' => defined('SECURE_AUTH_KEY') && !empty(SECURE_AUTH_KEY),
            'logged_in_key_defined' => defined('LOGGED_IN_KEY') && !empty(LOGGED_IN_KEY),
            'nonce_key_defined' => defined('NONCE_KEY') && !empty(NONCE_KEY),
        ];

        $status['ready'] = $status['openssl_available'] &&
                          ($status['auth_key_defined'] || $status['secure_auth_key_defined']);

        return $status;
    }
}