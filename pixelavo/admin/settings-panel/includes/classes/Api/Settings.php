<?php
namespace Pixelavo\Api;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use WP_REST_Controller;
use Pixelavo\SanitizeTrail\Sanitize_Trait;

if (!class_exists('\Pixelavo\Admin\Options_Field')) {
    require_once PIXELAVO_INCLUDES . '/classes/Admin/Options_field.php';
}
use Pixelavo\Api\Ai\EncryptionHelper;


/**
 * REST_API Handler
 */
class Settings extends WP_REST_Controller {

    use Sanitize_Trait;

    protected $namespace;
    protected $rest_base;
    protected $errors;

    /**
     * All registered settings.
     *
     * @var array
     */
    protected $settings;

    /**
     * [__construct Settings constructor]
     */
    public function __construct() {
        $this->namespace = 'pixelavoopt/v1';
        $this->rest_base = 'settings';
        $this->errors = new \WP_Error();
        $this->settings = \Pixelavo\Admin\Options_Field::instance()->get_registered_settings();

        add_filter('pixelavo_settings_sanitize', [$this, 'sanitize_settings'], 10, 3);
        add_filter('pixelavo_settings_sanitize_openai_api_key', [$this, 'encrypt_api_key'], 20, 1);
        add_filter('pixelavo_settings_sanitize_gemini_api_key', [$this, 'encrypt_api_key'], 20, 1);

    }

    /**
     * Register the routes
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_items'],
                    'permission_callback' => [$this, 'permissions_check'],
                    'args'                => $this->get_collection_params(),
                ],

                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'create_items'],
                    'permission_callback' => [$this, 'permissions_check'],
                    'args'                => $this->get_collection_params(),
                ]
            ]
        );

    }

    /**
     * Checks if a given request has access to read the items.
     *
     * @param  WP_REST_Request $request Full details about the request.
     *
     * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
     */
    public function permissions_check($request) {

        if (!current_user_can('manage_options')) {
            return new \WP_Error('rest_forbidden', 'PIXELAVO OPT: Permission Denied.', ['status' => 401]);
        }

        return true;
    }

    /**
     * Retrieves the query params for the items collection.
     *
     * @return array Collection parameters.
     */
    public function get_collection_params() {
        return [];
    }

    /**
     * Retrieves a collection of items.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_items($request) {
        $items = [];

        $section = (string) $request['section'];
        if (!empty($section)) {
            $items = get_option($section, true);

            // Mask API keys for display (show masked version)
            if ($section === 'pixelavo_settings' && is_array($items)) {
                // Load EncryptionHelper if not already loaded
                if (!class_exists('\Pixelavo\Api\Ai\EncryptionHelper')) {
                    require_once dirname(__DIR__) . '/Api/Ai/EncryptionHelper.php';
                }

                $api_keys = ['openai_api_key', 'gemini_api_key'];
                foreach ($api_keys as $key_field) {
                    if (isset($items[$key_field]) && !empty($items[$key_field])) {
                        $stored_value = $items[$key_field];

                        // Check if value is encrypted
                        if (EncryptionHelper::is_encrypted($stored_value)) {
                            // Decrypt to get actual key for masking
                            $decrypted = EncryptionHelper::decrypt($stored_value);

                            if ($decrypted && strlen($decrypted) > 11) {
                                // Show first 7 and last 4 characters
                                $items[$key_field] = substr($decrypted, 0, 7) . '...' . substr($decrypted, -4);
                            } elseif ($decrypted) {
                                // Short key - show masked placeholder
                                $items[$key_field] = '••••••••••••••••';
                            } else {
                                // Decryption failed - show placeholder
                                $items[$key_field] = '••••••••••••••••';
                            }
                        } else {
                            // Plain text key (backward compatibility) - mask it
                            if (strlen($stored_value) > 11) {
                                $items[$key_field] = substr($stored_value, 0, 7) . '...' . substr($stored_value, -4);
                            } else {
                                $items[$key_field] = '••••••••••••••••';
                            }
                        }
                    }
                }
            }
        }

        $response = rest_ensure_response($items);
        return $response;
    }

    /**
     * Create item response
     */
    public function create_items($request) {

        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($request['settings']['verifynonce'])), 'pixelavoopt_verifynonce')) {
            wp_send_json_error(array('message' => esc_html__('Invalid nonce', 'pixelavo')));
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => esc_html__('Permission Denied', 'pixelavo')));
            return;
        }

        $section = (!empty($request['section']) ? sanitize_text_field($request['section']) : '');
        $sub_section        = ( !empty( $request['subsection'] ) ? sanitize_text_field( $request['subsection'] ) : '' );
        $settings_received = (!empty($request['settings']) ? pixelavo_data_clean($request['settings']) : '');
        $settings_reset = (!empty($request['reset']) ? rest_sanitize_boolean($request['reset']) : '');

        // Data reset
        if ($settings_reset == true) {
            $reseted = ( !empty ( $sub_section ) ) ? delete_option ( $sub_section ) : delete_option ( $section );
            return rest_ensure_response( $reseted );
        }

        if (empty($section) || empty($settings_received)) {
            return;
        }

        $get_settings = $this->settings[$section];
        $data_to_save = [];

        if (is_array($get_settings) && !empty($get_settings)) {
            foreach ($get_settings as $setting) {

                // Skip if no setting type.
                if (!$setting['type']) {
                    continue;
                }

                // Skip if setting type is html.
                if ($setting['type'] === 'html') {
                    continue;
                }

                // Skip if setting field is pro.
                if (isset($setting['is_pro']) && $setting['is_pro']) {
                    continue;
                }

                // Skip if the ID doesn't exist in the data received.
                if (!array_key_exists($setting['id'], $settings_received)) {
                    continue;
                }

                // Sanitize the input.
                $setting_type = $setting['type'];

                $output = apply_filters('pixelavo_settings_sanitize', $settings_received[$setting['id']], $this->errors, $setting);

                $output = apply_filters('pixelavo_settings_sanitize_' . $setting['id'], $output, $this->errors, $setting);

                if ($setting_type == 'checkbox' && $output == false) {
                    continue;
                }

                // Add the option to the list of ones that we need to save.
                if (!empty($output) && !is_wp_error($output)) {
                    $data_to_save[$setting['id']] = $settings_received[$setting['id']];
                }

            }
        }

        if (!empty($this->errors->get_error_codes())) {
            return new \WP_REST_Response($this->errors, 422);
        }

        if( ! empty( $sub_section ) ){
            update_option( $sub_section, $data_to_save );
        } else {
            update_option( $section, $data_to_save );
        }

        return rest_ensure_response($data_to_save);

    }

    /**
     * Sanitize callback for Settings Data
     *
     * @return mixed
     */
    public function sanitize_settings($setting_value, $errors, $setting) {

        if (!empty($setting['sanitize_callback']) && is_callable($setting['sanitize_callback'])) {
            $setting_value = call_user_func($setting['sanitize_callback'], $setting_value);
        } else {
            $setting_value = $this->default_sanitizer($setting_value, $errors, $setting);
        }

        return $setting_value;

    }

    /**
     * If no Sanitize callback function from option field.
     *
     * @return mixed
     */
    public function default_sanitizer($setting_value, $errors, $setting) {

        switch ($setting['type']) {
            case 'text':
            case 'radio':
            case 'select':
                $finalvalue = $this->sanitize_text_field($setting_value, $errors, $setting);
                break;

            case 'textarea':
                $finalvalue = $this->sanitize_textarea_field($setting_value, $errors, $setting);
                break;

            case 'checkbox':
            case 'switcher':
            case 'element':
                $finalvalue = $this->sanitize_checkbox_field($setting_value, $errors, $setting);
                break;

            case 'table':
            case 'event-table':
            case 'multiselect':
            case 'multicheckbox':
                $finalvalue = $this->sanitize_multiple_field($setting_value, $errors, $setting);
                break;

            case 'file':
                $finalvalue = $this->sanitize_file_field($setting_value, $errors, $setting);
                break;

            default:
                $finalvalue = sanitize_text_field($setting_value);
                break;
        }

        return $finalvalue;

    }

    /**
     * Encrypt API key before saving
     *
     * @param string $value The API key value
     * @return string|false Encrypted value or false on failure
     */
    public function encrypt_api_key($value) {
        // If empty, return as is
        if (empty($value)) {
            return $value;
        }

        // Load EncryptionHelper if not already loaded
        if (!class_exists('\Pixelavo\Api\Ai\EncryptionHelper')) {
            $helper_path = dirname(__DIR__) . '/Api/Ai/EncryptionHelper.php';
            if (file_exists($helper_path)) {
                require_once $helper_path;
            } else {
                // Try alternative path if constant is defined
                if (defined('PIXELAVO_PL_PATH')) {
                    $helper_path = PIXELAVO_PL_PATH . 'admin/settings-panel/includes/classes/Api/Ai/EncryptionHelper.php';
                    if (file_exists($helper_path)) {
                        require_once $helper_path;
                    } else {
                        return $value;
                    }
                } else {
                    return $value;
                }
            }
        }

        // Check if the value is already masked (contains ...)
        // This means user didn't change it, so we need to preserve the original encrypted value
        if (strpos($value, '...') !== false) {
            // Get the current stored value
            $settings = get_option('pixelavo_settings', []);
            $current_filter = current_filter();
            $key_field = str_replace('pixelavo_settings_sanitize_', '', $current_filter);

            if (isset($settings[$key_field])) {
                // Return the existing encrypted value
                return $settings[$key_field];
            }
        }

        // Encrypt the new value
        $encrypted = EncryptionHelper::encrypt($value);

        // Fallback: if encryption fails, store in plain text with warning
        if ($encrypted === false) {
            return $value;
        }

        return $encrypted;
    }

}