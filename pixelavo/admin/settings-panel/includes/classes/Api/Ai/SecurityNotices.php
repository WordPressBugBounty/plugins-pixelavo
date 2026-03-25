<?php
namespace Pixelavo\Api\Ai;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Security Notices for AI Features
 *
 * Handles admin notices related to encryption and security warnings
 *
 * @package Pixelavo\Api\Ai
 * @since 1.0.0
 */
class SecurityNotices {

    /**
     * Initialize security notices
     */
    public static function init() {
        add_action('pixelavo_admin_notices', [__CLASS__, 'display_security_notices']);
        add_action('wp_ajax_pixelavo_dismiss_security_notice', [__CLASS__, 'dismiss_notice']);
    }

    /**
     * Display security-related admin notices
     */
    public static function display_security_notices() {
        // Only show on plugin pages
        if (!self::is_plugin_page()) {
            return;
        }

        // Check for OpenSSL availability
        self::check_openssl_notice();

        // Check for WordPress security keys
        self::check_wp_keys_notice();
    }

    /**
     * Check if current page is a plugin page
     *
     * @return bool
     */
    private static function is_plugin_page() {
        $screen = get_current_screen();
        return $screen && (
            strpos($screen->id, 'pixelavo') !== false ||
            strpos($screen->base, 'pixelavo') !== false
        );
    }

    /**
     * Check and display OpenSSL notice
     */
    private static function check_openssl_notice() {
        // Check if notice has been dismissed
        if (get_option('pixelavo_openssl_notice_dismissed', false)) {
            return;
        }

        // Load EncryptionHelper
        if (!class_exists('\Pixelavo\Api\Ai\EncryptionHelper')) {
            require_once __DIR__ . '/EncryptionHelper.php';
        }

        if (!EncryptionHelper::is_encryption_available()) {
            ?>
            <div class="notice notice-error is-dismissible pixelavo-security-notice" data-notice="openssl">
                <h3><?php esc_html_e('Pixelavo Security Warning', 'pixelavo'); ?></h3>
                <p>
                    <strong><?php esc_html_e('OpenSSL PHP extension is not available.', 'pixelavo'); ?></strong>
                    <?php esc_html_e('AI API keys will be stored in plain text, which is a security risk.', 'pixelavo'); ?>
                </p>
                <p>
                    <?php esc_html_e('Please contact your hosting provider to enable the OpenSSL extension, or:', 'pixelavo'); ?>
                </p>
                <ul style="margin-left: 20px;">
                    <li><?php esc_html_e('Update your PHP installation to include OpenSSL', 'pixelavo'); ?></li>
                    <li><?php esc_html_e('Enable the extension in your php.ini file', 'pixelavo'); ?></li>
                    <li><?php esc_html_e('Restart your web server after making changes', 'pixelavo'); ?></li>
                </ul>
            </div>
            <?php
        }
    }

    /**
     * Check and display WordPress security keys notice
     */
    private static function check_wp_keys_notice() {
        // Check if notice has been dismissed
        if (get_option('pixelavo_wp_keys_notice_dismissed', false)) {
            return;
        }

        $weak_keys = [];

        // Check for default or weak WordPress keys
        $keys_to_check = ['AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY'];

        foreach ($keys_to_check as $key) {
            if (!defined($key) ||
                empty(constant($key)) ||
                constant($key) === 'put your unique phrase here' ||
                strlen(constant($key)) < 32) {
                $weak_keys[] = $key;
            }
        }

        if (!empty($weak_keys)) {
            ?>
            <div class="notice notice-warning is-dismissible pixelavo-security-notice" data-notice="wp_keys">
                <h3><?php esc_html_e('Pixelavo Security Recommendation', 'pixelavo'); ?></h3>
                <p>
                    <strong><?php esc_html_e('WordPress security keys are not properly configured.', 'pixelavo'); ?></strong>
                    <?php esc_html_e('This affects the security of API key encryption.', 'pixelavo'); ?>
                </p>
                <p><?php esc_html_e('Missing or weak keys:', 'pixelavo'); ?> <code><?php echo esc_html(implode(', ', $weak_keys)); ?></code></p>
                <p>
                    <?php esc_html_e('Please generate new security keys:', 'pixelavo'); ?>
                    <a href="https://api.wordpress.org/secret-key/1.1/salt/" target="_blank" rel="noopener">
                        <?php esc_html_e('WordPress Secret Key Generator', 'pixelavo'); ?>
                    </a>
                </p>
                <p>
                    <small>
                        <?php esc_html_e('Add these keys to your wp-config.php file. API key encryption will still work but with reduced security.', 'pixelavo'); ?>
                    </small>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Handle notice dismissal via AJAX
     */
    public static function dismiss_notice() {
        // Verify nonce
        if (!isset($_POST['nonce'])) {
            wp_die('Security check failed');
        }
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'pixelavo_dismiss_notice')) {
            wp_die('Security check failed');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $notice_type = isset($_POST['notice_type']) ? sanitize_text_field(wp_unslash($_POST['notice_type'])) : '';

        switch ($notice_type) {
            case 'openssl':
                update_option('pixelavo_openssl_notice_dismissed', true);
                break;
            case 'wp_keys':
                update_option('pixelavo_wp_keys_notice_dismissed', true);
                break;
        }

        wp_die('Notice dismissed');
    }

    /**
     * Add JavaScript for notice dismissal
     */
    public static function enqueue_notice_scripts() {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.pixelavo-security-notice').on('click', '.notice-dismiss', function(e) {
                e.preventDefault();

                var notice = $(this).closest('.pixelavo-security-notice');
                var noticeType = notice.data('notice');

                $.post(ajaxurl, {
                    action: 'pixelavo_dismiss_security_notice',
                    notice_type: noticeType,
                    nonce: '<?php echo esc_js(wp_create_nonce('pixelavo_dismiss_notice')); ?>'
                });
            });
        });
        </script>
        <?php
    }
}