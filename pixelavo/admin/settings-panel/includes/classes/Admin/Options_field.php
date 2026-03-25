<?php
namespace Pixelavo\Admin;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Options_Field {

    private $woocommerce_active = false;
    private $woocommerce_subscription_active = false;
    private $edd_active = false;
    private $edd_subscription_active = false;
    private $event_list_desc_wc = '';
    private $event_list_desc_edd = '';

    /**
     * [$_instance]
     * @var null
     */
    private static $_instance = null;

    /**
     * [instance] Initializes a singleton instance
     * @return [Admin]
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    

	function __construct(){
        $this->event_list_desc_wc = __('Select pixel events from the list below.', 'pixelavo');
        $this->event_list_desc_edd = __('Select pixel events from the list below.', 'pixelavo');
		$this->woocommerce_active = pixelavo_is_woocommerce_active();
		$this->woocommerce_subscription_active = pixelavo_is_woocommerce_subscription_active();
        if(!$this->woocommerce_active) {
            $this->event_list_desc_wc = __('The below events require the "WooCommerce" plugin to be active. Please Make sure that WooCommerce has been installed and Activated.', 'pixelavo');
        }
		$this->edd_active = pixelavo_is_edd_active();
		$this->edd_subscription_active = pixelavo_is_edd_subscription_active();
        if(!$this->edd_active) {
            $this->event_list_desc_edd = __('The below events require the "Easy Digital Downloads" plugin to be active. Please Make sure that Easy Digital Downloads has been installed and Activated.', 'pixelavo');
        }
	}

    public function get_products(){
        $products = [];
        if(pixelavo_is_woocommerce_active()){
            $posts = get_posts([
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => -1,
            ]);
            foreach($posts as $post){
                $products[] = [ 'id' => $post->post_title, 'text' => $post->post_title ];
            }
        }
        if(pixelavo_is_edd_active()){
            $posts = get_posts([
                'post_type' => 'download',
                'post_status' => 'publish',
                'posts_per_page' => -1,
            ]);
            foreach($posts as $post){
                $products[] = [ 'id' => $post->post_title, 'text' => $post->post_title ];
            }
        }
        return $products;
    }

    public function get_settings_tabs(){
        $list_svg = PIXELAVO_ASSETS . '/images/icons/list.svg';
        $woocommerce_svg = PIXELAVO_ASSETS . '/images/icons/woocommerce.svg';
        $edd_svg = PIXELAVO_ASSETS . '/images/icons/edd.svg';
        $puzzle_svg = PIXELAVO_ASSETS . '/images/icons/puzzle.svg';
        $slider_svg = PIXELAVO_ASSETS . '/images/icons/slider.svg';
        $other_svg = PIXELAVO_ASSETS . '/images/icons/other.svg';
        $ai_svg = PIXELAVO_ASSETS . '/images/icons/ai.svg';

        // Define variables
        $list_icon = '';
        $woocommerce_icon = '';
        $edd_icon = '';
        $puzzle_icon = '';
        $slider_icon = '';
        $other_icon = '';
        
        $list_icon_res = wp_remote_get($list_svg, [
            'sslverify' => false
        ]);
        if ( is_array( $list_icon_res ) && ! is_wp_error( $list_icon_res ) ) {
            $list_icon = $list_icon_res['body'];
        }
        
        $woocommerce_icon_res = wp_remote_get($woocommerce_svg, [
            'sslverify' => false,
        ]);
        if ( is_array( $woocommerce_icon_res ) && ! is_wp_error( $woocommerce_icon_res ) ) {
            $woocommerce_icon = $woocommerce_icon_res['body'];
        }
            
        $edd_icon_res = wp_remote_get($edd_svg, [
            'sslverify' => false,
        ]);
        if ( is_array( $edd_icon_res ) && ! is_wp_error( $edd_icon_res ) ) {
            $edd_icon = $edd_icon_res['body'];
        }
            
        $puzzle_icon_res = wp_remote_get($puzzle_svg, [
            'sslverify' => false,
        ]);
        if ( is_array( $puzzle_icon_res ) && ! is_wp_error( $puzzle_icon_res ) ) {
            $puzzle_icon = $puzzle_icon_res['body'];
        }
            
        $slider_icon_res = wp_remote_get($slider_svg, [
            'sslverify' => false,
        ]);
        if ( is_array( $slider_icon_res ) && ! is_wp_error( $slider_icon_res ) ) {
            $slider_icon = $slider_icon_res['body'];
        }
        $other_icon_res = wp_remote_get($other_svg, [
            'sslverify' => false,
        ]);
        if ( is_array( $other_icon_res ) && ! is_wp_error( $other_icon_res ) ) {
            $other_icon = $other_icon_res['body'];
        }
        $ai_icon_res = wp_remote_get($ai_svg, [
            'sslverify' => false,
        ]);
        if ( is_array( $ai_icon_res ) && ! is_wp_error( $ai_icon_res ) ) {
            $ai_icon = $ai_icon_res['body'];
        }

        $ai_group_tabs = [
            [
                'id'    => 'ad_diagnostic_tool',
                'title' =>  esc_html__( 'Ad Diagnostic Tool', 'pixelavo' ),
            ],
            [
                'id'    => 'ad_copy_generator',
                'title' =>  esc_html__( 'Ad Copy Generator', 'pixelavo' ),
            ],
            [
                'id'    => 'ai_consultant',
                'title' =>  esc_html__( 'Ad Consultant', 'pixelavo' ),
            ],
        ];
            
        $tabs = array(
            'pixels' => [
                'id'    => 'pixelavo_pixels_list',
                'title' =>  esc_html__( 'Pixel List', 'pixelavo' ),
                'icon'  => wp_json_encode($list_icon),
                'showInNav' => true,
                'content' => [
                    'extraClass' => 'pixels',
                    'savebtn' => false,
                    'enableall' => false,
                    'title' => __( 'Pixels', 'pixelavo' ),
                    'desc'  => __( 'Add pixels to your site', 'pixelavo' ),
                    'desc_class'  => '',
                    'footer' => false,
                ],
            ],
            'events' => [
                'id'    => 'pixelavo_events',
                'title' =>  esc_html__( 'WooCommerce Events', 'pixelavo' ),
                'icon'  => wp_json_encode($woocommerce_icon),
                'showInNav' => $this->woocommerce_active,
                'content' => [
                    'column' => 2,
                    'extraClass' => 'events',
                    'title' => __( 'WooCommerce Events', 'pixelavo' ),
                    'desc'  => $this->event_list_desc_wc,
                    'desc_class'  => !$this->woocommerce_active ? 'warning' : '',
                    'footer' => false,
                ],
            ],
            'edd_events' => [
                'id'    => 'pixelavo_edd_events',
                'title' =>  esc_html__( 'EDD Events', 'pixelavo' ),
                'icon'  => wp_json_encode($edd_icon),
                'showInNav' => $this->edd_active,
                'content' => [
                    'column' => 2,
                    'extraClass' => 'events',
                    'title' => __( 'Easy Digital Downloads Events', 'pixelavo' ),
                    'desc'  => $this->event_list_desc_edd,
                    'desc_class'  => !$this->edd_active ? 'warning' : '',
                    'footer' => false,
                ],
            ],
            'other_events' => [
                'id'    => 'pixelavo_other_events',
                'title' =>  esc_html__( 'Other Events', 'pixelavo' ),
                'icon'  => wp_json_encode($other_icon),
                'showInNav' => true,
                'content' => [
                    'column' => 2,
                    'extraClass' => 'events',
                    'title' => __( 'Other Events', 'pixelavo' ),
                    'desc'  => __( 'Enable events from the list below.', 'pixelavo' ),
                    'desc_class'  => '',
                    'footer' => false,
                ],
            ],
            'custom_events' => [
                'id'    => 'pixelavo_custom_events',
                'title' =>  esc_html__( 'Custom Events', 'pixelavo' ),
                'icon'  => wp_json_encode($puzzle_icon),
                'showInNav' => true,
                'content' => [
                    'savebtn' => false,
                    'enableall' => false,
                    'extraClass' => 'custom_events',
                    'title' => __( 'Custom Events', 'pixelavo' ),
                    'desc'  => __( 'Add custom events to your site', 'pixelavo' ),
                    'desc_class'  => '',
                    'footer' => false,
                ],
            ],
            // 'ad_diagnostic_tool' => [
            //     'id'    => 'pixelavo_ad_diagnostic_tool',
            //     'title' =>  esc_html__( 'AI Copy', 'pixelavo' ),
            //     'icon'  => wp_json_encode($ai_icon),
            //     'showInNav' => true,
            //     'content' => [
            //         'savebtn' => false,
            //         'enableall' => false,
            //         'extraClass' => 'ai ai_ad_diagnostic_tool',
            //         'desc_class'  => '',
            //         'footer' => false,
            //         'ai_group_tabs' => $ai_group_tabs,
            //     ],
            // ],
            'ad_copy_generator' => [
                'id'    => 'pixelavo_ad_copy_generator',
                'title' =>  esc_html__( 'Ad Copy', 'pixelavo' ),
                'icon'  => wp_json_encode($ai_icon),
                'showInNav' => true,
                'content' => [
                    'savebtn' => false,
                    'enableall' => false,
                    'header' => true,
                    'title' => __( 'AI Ad Copy Generator', 'pixelavo' ),
                    'desc'  => __( 'Generate high-converting ad copy for your products in seconds.', 'pixelavo' ),
                    'extraClass' => 'ai ai_ad_copy_generator',
                    'desc_class'  => '',
                    'footer' => false,
                ],
            ],
            'ai_consultant' => [
                'id'    => 'pixelavo_ai_consultant',
                'title' =>  esc_html__( 'AI Consultant', 'pixelavo' ),
                'icon'  => 'chatbot',
                'showInNav' => true,
                'content' => [
                    'savebtn' => false,
                    'enableall' => false,
                    'header' => true,
                    'title' => __( 'AI Ad Consultant', 'pixelavo' ),
                    'desc'  => __( 'Get expert Facebook advertising advice from our AI consultant.', 'pixelavo' ),
                    'extraClass' => 'ai ai_ai_consultant',
                    'desc_class'  => '',
                    'footer' => false,
                ],
            ],
            'settings' => [
                'id'    => 'pixelavo_settings',
                'title' =>  esc_html__( 'Settings', 'pixelavo' ),
                'icon'  => wp_json_encode($slider_icon),
                'showInNav' => true,
                'content' => [
                    'enableall' => false,
                    'extraClass' => 'settings',
                    'title' => __( 'Advance Settings', 'pixelavo' ),
                    'desc'  => __( 'Select pixel settings from the list below.', 'pixelavo' ),
                    'desc_class'  => '',
                    'footer' => false,
                ],
            ],
        );

        return apply_filters( 'pixelavo_admin_fields_sections', $tabs );
    }

    public function get_settings_subtabs(){

        $subtabs = array();

        return apply_filters( 'pixelavo_admin_fields_sub_sections', $subtabs );
    }

    /**
     * Check if an API key exists and is valid (decrypted)
     *
     * @param array $settings_data Settings data array
     * @param string $key_field API key field name
     * @return bool
     */
    private function api_key_exists($settings_data, $key_field) {
        if (empty($settings_data[$key_field])) {
            return false;
        }

        try {
            // Load EncryptionHelper if not already loaded
            if (!class_exists('\Pixelavo\Api\Ai\EncryptionHelper')) {
                $helper_path = dirname(__DIR__) . '/Api/Ai/EncryptionHelper.php';
                if (file_exists($helper_path)) {
                    require_once $helper_path;
                } else {
                    // Fallback: if EncryptionHelper doesn't exist, assume plain text keys
                    return !empty($settings_data[$key_field]);
                }
            }

            // Try to decrypt the key - if it decrypts successfully and is not empty, the key exists
            $decrypted = \Pixelavo\Api\Ai\EncryptionHelper::decrypt($settings_data[$key_field]);

            // If decryption fails but we have a value, it might be a plain text key (fallback)
            if ($decrypted === false && !empty($settings_data[$key_field])) {
                // Check if it's not marked as encrypted (backward compatibility)
                if (!\Pixelavo\Api\Ai\EncryptionHelper::is_encrypted($settings_data[$key_field])) {
                    return true; // Treat as valid plain text key
                }
                return false; // Encrypted but failed to decrypt
            }

            return !empty($decrypted);

        } catch (\Exception $e) {
            // If any error occurs, fall back to checking if the value exists
            return !empty($settings_data[$key_field]);
        }
    }

    public function get_registered_settings(){
        $settings_data = get_option('pixelavo_settings', []);
        $settings = array(
            'pixelavo_pixels_list' => array(
                array(
                    'id' => "pixel_lists",
                    'type' => 'table',
                    'table_title' => esc_html__( 'Pixel List', 'pixelavo' ),
                    'modal_open_button_text' => esc_html__( 'Add New Pixel' , 'pixelavo' ),
                    'modal_fields' => array(
                        array(
                            'id'  => 'pixel_name',
                            'name' => __( 'Pixel Name', 'pixelavo' ),
                            'desc'  => __( 'This name will help you to recognize your pixel', 'pixelavo' ),
                            'type' => 'text',
                            'placeholder' => __( 'e.g. WooCommerce Store Pixel', 'pixelavo' ),
                            'required' => true
                        ),
                        array(
                            'id'  => 'pixel_id',
                            'name' => __( 'Pixel ID', 'pixelavo' ),
                            'desc'  => __( 'Enter your Facebook pixel ID here. <a href="https://pixelavo.com/docs/where-to-find-pixel-id/" target="_blank">Where to find it?</a>', 'pixelavo' ),
                            'type' => 'text',
                            'placeholder' => __( 'e.g. 717216986833482', 'pixelavo' ),
                            'required' => true
                        ),
                        array(
                            'id'  => 'pixel_page',
                            'name' => __( 'Select Page', 'pixelavo' ),
                            'desc'  => __( 'Select the page type', 'pixelavo' ),
                            'type' => 'radio',
                            'required' => true,
                            'radio_inputs' => array(
                                array(
                                    'label' => esc_html__('All Pages', 'pixelavo'),
                                    'value' => "allpages",
                                    'default' => 'on',
                                ),
                                array(
                                    'label' => esc_html__('Specific Pages', 'pixelavo'),
                                    'value' => "specificpage",
                                )
                            )
                        ),
                        array(
                            'id'  => 'specificpage_list',
                            'name' => __( 'Page Lists', 'pixelavo' ),
                            'desc'  => __( 'Select specifi pages form the list', 'pixelavo' ),
                            'toggle' => array(
                                'key' => 'pixel_page',
                                'operator' => '==',
                                'value' => 'specificpage'
                            ),
                            'type' => 'multiselect',
                            'default' => '0',
                            'required' => true,
                            'options' => pixelavo_page_list()
                        ),
                        array(
                            'id'  => 'conversion_api_status',
                            'name'  => __( 'Conversion Api Status', 'pixelavo' ),
                            'type'  => 'element',
                            'default' => 'off',
                            'label_on' => __( 'On', 'pixelavo' ),
                            'label_off' => __( 'Off', 'pixelavo' ),
                        ),
                        array(
                            'id'  => 'access_token',
                            'name' => __( 'Access Token', 'pixelavo' ),
                            'desc'  => __( 'Enter access token here. <a href="https://pixelavo.com/docs/where-to-find-conversion-api-access-token/" target="_blank">Where to find it?</a>', 'pixelavo' ),
                            'toggle' => array(
                                'key' => 'conversion_api_status',
                                'operator' => '==',
                                'value' => 'on'
                            ),
                            'type' => 'textarea',
                            'required' => true
                        ),
                        array(
                            'id'  => 'event_code',
                            'name' => __( 'Test Event Code', 'pixelavo' ),
                            'desc'  => __( 'Enter test code here (Remove the test code after testing events). <a href="https://pixelavo.com/docs/how-to-test-facebook-conversion-api-capi-events/" target="_blank">Where to find it?</a>', 'pixelavo' ),
                            'toggle' => array(
                                'key' => 'conversion_api_status',
                                'operator' => '==',
                                'value' => 'on'
                            ),
                            'type' => 'text',
                            'placeholder' => 'e.g. TEST41232',
                        ),
                    ),
                    'table_column' => array(
                        'pixel_name' => __( 'Pixel Name', 'pixelavo' ),
                        'pixel_id'   => __( 'Pixel ID', 'pixelavo' ),
                        'status'     => array(
                            'title' => __( 'Status', 'pixelavo' ),
                            'type'  => 'switcher'
                        ),
                        'action'     => array(
                            'title'  =>  __( 'Action', 'pixelavo' ),
                            'button_edit' => __( 'Edit', 'pixelavo' ),
                            'button_delete' => __( 'Delete', 'pixelavo' )
                        )
                    ),
                    'pixel_limit' => apply_filters('pixelavo_pixels_limit', 1)
                )
            ),
            'pixelavo_events' => array(
                array(
                    'id'  => 'view_content',
                    'name'  => __( 'Track View Content', 'pixelavo' ),
                    'desc'  => __( 'When a person visit a product details page.', 'pixelavo' ),
                    'type'  => 'element',
                    'default' => $this->woocommerce_active ? 'on' : 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'is_pro' => false,
                    "disabled" => !$this->woocommerce_active,
                    'section'  => 'pixelavo_wc_view_content',
                    'child' => [
                        [
                            'id'  => 'view_content_delay',
                            'name'  => __( 'View Content Event Delay', 'pixelavo' ),
                            'desc'  => __( 'To exclude bouncing visitors from triggering the ViewContent event on product page, introduce a delay, which will be in second. Default is 0 second', 'pixelavo' ),
                            'type' => 'number',
                            'default' => '0',
                            'min' => '0',
                            'step' => '1',
                            'is_pro' => apply_filters('pixelavo_pro_feature', true),
                            "disabled" => !$this->woocommerce_active
                        ],
                    ]
                ),
                array(
                    'id'  => 'view_category',
                    'name'  => __( 'Track View Category', 'pixelavo' ),
                    'desc'  => __( 'When a person visit a product category page.', 'pixelavo' ),
                    'type'  => 'element',
                    'default' => $this->woocommerce_active ? 'on' : 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'is_pro' => false,
                    "disabled" => !$this->woocommerce_active
                ),
                array(
                    'id'  => 'search',
                    'name'  => __( 'Track Search', 'pixelavo' ),
                    'desc'  => __( 'When a search is made using WordPress built-in search feature.', 'pixelavo' ),
                    'type'  => 'element',
                    'default' => $this->woocommerce_active ? 'on' : 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'is_pro' => false,
                    "disabled" => !$this->woocommerce_active
                ),
                array(
                    'id'  => 'customize_product',
                    'name'  => __( 'Track Customize Product', 'pixelavo' ),
                    'desc'  => __( 'When a person customizes a product.', 'pixelavo' ),
                    'type'  => 'element',
                    'default' => $this->woocommerce_active ? apply_filters('pixelavo_pro_event_default', 'off') : 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'is_pro' => apply_filters('pixelavo_pro_feature', true),
                    "disabled" => !$this->woocommerce_active || apply_filters('pixelavo_pro_feature', true)
                ),
                array(
                    'id'  => 'add_to_cart',
                    'name'  => __( 'Track Add To Cart', 'pixelavo' ),
                    'desc'  => __( 'When a product is added to the shopping cart.', 'pixelavo' ),
                    'type'  => 'element',
                    'default' => $this->woocommerce_active ? apply_filters('pixelavo_pro_event_default', 'off') : 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'is_pro' => apply_filters('pixelavo_pro_feature', true),
                    "disabled" => !$this->woocommerce_active || apply_filters('pixelavo_pro_feature', true)
                ),
                array(
                    'id'  => 'remove_from_cart',
                    'name'  => __( 'Track Remove From Cart', 'pixelavo' ),
                    'desc'  => __( 'When a product is removed from the shopping cart.', 'pixelavo' ),
                    'type'  => 'element',
                    'default' => $this->woocommerce_active ? 'on' : 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'is_pro' => false,
                    "disabled" => !$this->woocommerce_active
                ),
                array(
                    'id'  => 'initiate_checkout',
                    'name'  => __( 'Track Initiate Checkout', 'pixelavo' ),
                    'desc'  => __( 'When a person enters the checkout flow prior to completing the checkout flow.', 'pixelavo' ),
                    'type'  => 'element',
                    'default' => $this->woocommerce_active ? apply_filters('pixelavo_pro_event_default', 'off') : 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'is_pro' => apply_filters('pixelavo_pro_feature', true),
                    "disabled" => !$this->woocommerce_active || apply_filters('pixelavo_pro_feature', true)
                ),
                array(
                    'id'  => 'purchase',
                    'name'  => __( 'Track Purchase', 'pixelavo' ),
                    'desc'  => __( 'When a purchase is made or checkout flow is completed.', 'pixelavo' ),
                    'type'  => 'element',
                    'default' => $this->woocommerce_active ? apply_filters('pixelavo_pro_event_default', 'off') : 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'is_pro' => apply_filters('pixelavo_pro_feature', true),
                    "disabled" => !$this->woocommerce_active || apply_filters('pixelavo_pro_feature', true),
                    'section'  => 'pixelavo_wc_purchase',
                    'child' => [
                        [
                            'id'  => 'purchase_event_trigger',
                            'name'  => __( 'WooCommerce Purchase Event Trigger', 'pixelavo' ),
                            'desc'  => __( 'You can select the timing to initiate the <b>"Purchase"</b> event. By default, it occurs when the order status is <b>"On Hold"</b> or <b>"Processing"</b>.', 'pixelavo' ),
                            'type' => 'multiselect',
                            'required' => true,
                            'default' => ['on-hold', 'processing'],
                            'options' => [
                                [
                                    'id' => "on-hold",
                                    'text' => __('On Hold', 'pixelavo' ),
                                ],
                                [
                                    'id' => "processing",
                                    'text' => __('Processing', 'pixelavo' ),
                                ],
                                [
                                    'id' => "completed",
                                    'text' => __('Completed', 'pixelavo' ),
                                ]
                            ],
                            'is_pro' => apply_filters('pixelavo_pro_feature', true),
                            "disabled" => !$this->woocommerce_active
                        ],
                        [
                            'id'  => 'purchase_additional_info',
                            'name'  => __( 'Include additional information with the Purchase event', 'pixelavo' ),
                            'desc'  => __( 'The Purchase event includes additional parameters like coupon codes, shipping cost, and tax, allowing you to create more precise and effective custom audiences.', 'pixelavo' ),
                            'type'  => 'switcher',
                            'default' => 'off',
                            'label_on' => __( 'On', 'pixelavo' ),
                            'label_off' => __( 'Off', 'pixelavo' ),
                            'is_pro' => apply_filters('pixelavo_pro_feature', true),
                            "disabled" => !$this->woocommerce_active
                        ],
                    ]
                ),
                array(
                    'id'  => 'refund',
                    'name'  => __( 'Track Refund', 'pixelavo' ),
                    'desc'  => __( 'When a refund is approved.', 'pixelavo' ),
                    'type'  => 'element',
                    'default' => $this->woocommerce_active ? apply_filters('pixelavo_pro_event_default', 'off') : 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'is_pro' => apply_filters('pixelavo_pro_feature', true),
                    "disabled" => !$this->woocommerce_active || apply_filters('pixelavo_pro_feature', true)
                ),
                array(
                    'id'  => 'subscription',
                    'name'  => __( 'Track Subscription', 'pixelavo' ),
                    'desc' => $this->woocommerce_subscription_active 
                        ? __('Track subscription when a subscription is created, renewed, cancelled, or changes status.', 'pixelavo')
                        : __('Track subscription when a subscription is created, renewed, cancelled, or changes status. Note: This feature requires (WooCommerce Subscription Extension)', 'pixelavo'),
                    'type'  => 'element',
                    'default' => $this->woocommerce_active && $this->woocommerce_subscription_active ? apply_filters('pixelavo_pro_event_default', 'off') : 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'is_pro' => apply_filters('pixelavo_pro_feature', true),
                    "disabled" => !$this->woocommerce_subscription_active || apply_filters('pixelavo_pro_feature', true),
                    'section'  => 'pixelavo_subscription',
                    'child' => [
                        [
                            'id'  => 'subscription_created',
                            'name'  => __( 'Track Subscription Created', 'pixelavo' ),
                            'desc'  => __( 'Triggered when a new subscription is created.', 'pixelavo' ),
                            'type'  => 'switcher',
                            'default' => $this->woocommerce_subscription_active ? apply_filters('pixelavo_pro_event_default', 'on') : 'off',
                            'label_on' => __( 'On', 'pixelavo' ),
                            'label_off' => __( 'Off', 'pixelavo' ),
                            'is_pro' => apply_filters('pixelavo_pro_feature', true),
                            'disabled' => !$this->woocommerce_subscription_active,
                        ],
                        [
                            'id'  => 'subscription_renewed',
                            'name'  => __( 'Track Subscription Renewed', 'pixelavo' ),
                            'desc'  => __( 'Triggered when a subscription is renewed.', 'pixelavo' ),
                            'type'  => 'switcher',
                            'default' => $this->woocommerce_subscription_active ? apply_filters('pixelavo_pro_event_default', 'on') : 'off',
                            'label_on' => __( 'On', 'pixelavo' ),
                            'label_off' => __( 'Off', 'pixelavo' ),
                            'is_pro' => apply_filters('pixelavo_pro_feature', true),
                            'disabled' => !$this->woocommerce_subscription_active,
                        ],
                        [
                            'id'  => 'subscription_cancelled',
                            'name'  => __( 'Track Subscription Cancelled', 'pixelavo' ),
                            'desc'  => __( 'Triggered when a subscription is cancelled.', 'pixelavo' ),
                            'type'  => 'switcher',
                            'default' => $this->woocommerce_subscription_active ? apply_filters('pixelavo_pro_event_default', 'on') : 'off',
                            'label_on' => __( 'On', 'pixelavo' ),
                            'label_off' => __( 'Off', 'pixelavo' ),
                            'is_pro' => apply_filters('pixelavo_pro_feature', true),
                            'disabled' => !$this->woocommerce_subscription_active,
                        ],
                    ]
                )
            ),
            'pixelavo_edd_events' => array(
                array(
                    'id'  => 'edd_view_content',
                    'name'  => __( 'Track View Content', 'pixelavo' ),
                    'desc'  => __( 'When a person visit a product details page.', 'pixelavo' ),
                    'type'  => 'element',
                    'default' => $this->edd_active ? 'on' : 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'is_pro' => false,
                    "disabled" => !$this->edd_active,
                    'section'  => 'pixelavo_edd_view_content',
                    'child' => [
                        [
                            'id'  => 'view_content_delay',
                            'name'  => __( 'View Content Event Delay', 'pixelavo' ),
                            'desc'  => __( 'To exclude bouncing visitors from triggering the ViewContent event on product page, introduce a delay, which will be in second. Default is 0 second', 'pixelavo' ),
                            'type' => 'number',
                            'default' => '0',
                            'min' => '0',
                            'step' => '1',
                            'is_pro' => apply_filters('pixelavo_pro_feature', true),
                            "disabled" => !$this->edd_active
                        ],
                    ]
                ),
                array(
                    'id'  => 'edd_view_category',
                    'name'  => __( 'Track View Category', 'pixelavo' ),
                    'desc'  => __( 'When a person visit a product category page.', 'pixelavo' ),
                    'type'  => 'element',
                    'default' => $this->edd_active ? 'on' : 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'is_pro' => false,
                    "disabled" => !$this->edd_active
                ),
                
                array(
                    'id'  => 'edd_search',
                    'name'  => __( 'Track Search', 'pixelavo' ),
                    'desc'  => __( 'When a search is made using WordPress built-in search feature.', 'pixelavo' ),
                    'type'  => 'element',
                    'default' => $this->edd_active ? 'on' : 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'is_pro' => false,
                    "disabled" => !$this->edd_active
                ),
                array(
                    'id'  => 'edd_add_to_cart',
                    'name'  => __( 'Track Add To Cart', 'pixelavo' ),
                    'desc'  => __( 'When a product is added to the shopping cart.', 'pixelavo' ),
                    'type'  => 'element',
                    'default' => $this->edd_active ? apply_filters('pixelavo_pro_event_default', 'off') : 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'is_pro' => apply_filters('pixelavo_pro_feature', true),
                    "disabled" => !$this->edd_active
                ),
                array(
                    'id'  => 'edd_remove_from_cart',
                    'name'  => __( 'Track Remove From Cart', 'pixelavo' ),
                    'desc'  => __( 'When a product is removed from the shopping cart.', 'pixelavo' ),
                    'type'  => 'element',
                    'default' => $this->edd_active ? 'on' : 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'is_pro' => false,
                    "disabled" => !$this->edd_active
                ),
                array(
                    'id'  => 'edd_initiate_checkout',
                    'name'  => __( 'Track Initiate Checkout', 'pixelavo' ),
                    'desc'  => __( 'When a person enters the checkout flow prior to completing the checkout flow.', 'pixelavo' ),
                    'type'  => 'element',
                    'default' => $this->edd_active ? apply_filters('pixelavo_pro_event_default', 'off') : 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'is_pro' => apply_filters('pixelavo_pro_feature', true),
                    "disabled" => !$this->edd_active
                ),
                array(
                    'id'  => 'edd_purchase',
                    'name'  => __( 'Track Purchase', 'pixelavo' ),
                    'desc'  => __( 'When a purchase is made or checkout flow is completed.', 'pixelavo' ),
                    'type'  => 'element',
                    'default' => $this->edd_active ? apply_filters('pixelavo_pro_event_default', 'off') : 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'is_pro' => apply_filters('pixelavo_pro_feature', true),
                    "disabled" => !$this->edd_active || apply_filters('pixelavo_pro_feature', true),
                    'section'  => 'pixelavo_edd_purchase',
                    'child' => [
                        [
                            'id'  => 'purchase_additional_info',
                            'name'  => __( 'Include additional information with the Purchase event', 'pixelavo' ),
                            'desc'  => __( 'The Purchase event includes additional parameters like coupon codes and tax, allowing you to create more precise and effective custom audiences.', 'pixelavo' ),
                            'type'  => 'switcher',
                            'default' => 'off',
                            'label_on' => __( 'On', 'pixelavo' ),
                            'label_off' => __( 'Off', 'pixelavo' ),
                            'is_pro' => apply_filters('pixelavo_pro_feature', true),
                            "disabled" => !$this->edd_active
                        ],
                    ]
                ),
                array(
                    'id'  => 'edd_refund',
                    'name'  => __( 'Track Refund', 'pixelavo' ),
                    'desc'  => __( 'When a refund is made.', 'pixelavo' ),
                    'type'  => 'element',
                    'default' => $this->edd_active ? apply_filters('pixelavo_pro_event_default', 'off') : 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'is_pro' => apply_filters('pixelavo_pro_feature', true),
                    "disabled" => !$this->edd_active || apply_filters('pixelavo_pro_feature', true),
                ),
                array(
                    'id'  => 'edd_subscription',
                    'name'  => __( 'Track Subscription', 'pixelavo' ),
                    'desc'  => $this->edd_subscription_active
                        ? __( 'Track subscription when a subscription is created, renewed, or cancelled.', 'pixelavo' )
                        : __( 'Track subscription when a subscription is created, renewed, or cancelled. Note: This feature requires (WooCommerce Subscription Extension)', 'pixelavo' ),
                    'type'  => 'element',
                    'default' => $this->edd_active && $this->edd_subscription_active ? apply_filters('pixelavo_pro_event_default', 'off') : 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'is_pro' => apply_filters('pixelavo_pro_feature', true),
                    "disabled" => !$this->edd_subscription_active || apply_filters('pixelavo_pro_feature', true),
                    'section'  => 'pixelavo_edd_subscription',
                    'child' => [
                        [
                            'id'  => 'subscription_created',
                            'name'  => __( 'Track Subscription Created', 'pixelavo' ),
                            'desc'  => __( 'Triggered when a new subscription is created.', 'pixelavo' ),
                            'type'  => 'switcher',
                            'default' => $this->edd_subscription_active ? apply_filters('pixelavo_pro_event_default', 'on') : 'off',
                            'label_on' => __( 'On', 'pixelavo' ),
                            'label_off' => __( 'Off', 'pixelavo' ),
                            'is_pro' => apply_filters('pixelavo_pro_feature', true),
                            'disabled' => !$this->edd_subscription_active,
                        ],
                        [
                            'id'  => 'subscription_renewed',
                            'name'  => __( 'Track Subscription Renewed', 'pixelavo' ),
                            'desc'  => __( 'Triggered when a subscription is renewed.', 'pixelavo' ),
                            'type'  => 'switcher',
                            'default' => $this->edd_subscription_active ? apply_filters('pixelavo_pro_event_default', 'on') : 'off',
                            'label_on' => __( 'On', 'pixelavo' ),
                            'label_off' => __( 'Off', 'pixelavo' ),
                            'is_pro' => apply_filters('pixelavo_pro_feature', true),
                            'disabled' => !$this->edd_subscription_active,
                        ],
                        [
                            'id'  => 'subscription_cancelled',
                            'name'  => __( 'Track Subscription Cancelled', 'pixelavo' ),
                            'desc'  => __( 'Triggered when a subscription is cancelled.', 'pixelavo' ),
                            'type'  => 'switcher',
                            'default' => $this->edd_subscription_active ? apply_filters('pixelavo_pro_event_default', 'on') : 'off',
                            'label_on' => __( 'On', 'pixelavo' ),
                            'label_off' => __( 'Off', 'pixelavo' ),
                            'is_pro' => apply_filters('pixelavo_pro_feature', true),
                            'disabled' => !$this->edd_subscription_active,
                        ],
                    ]
                )
            ),
            'pixelavo_other_events' => array(
                array(
                    'id'  => 'internal_links',
                    'name'  => __( 'Track Internal Link Clicks', 'pixelavo' ),
                    'desc'  => __( 'When a link is clicked that leads to another page within the same domain.', 'pixelavo' ),
                    'type'  => 'element',
                    'default' => 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'is_pro' => apply_filters('pixelavo_pro_feature', true),
                    'disabled' => apply_filters('pixelavo_pro_feature', true),
                ),
                array(
                    'id'  => 'external_links',
                    'name'  => __( 'Track External Link Clicks', 'pixelavo' ),
                    'desc'  => __( 'When a link is clicked that leads to a page outside the current domain.', 'pixelavo' ),
                    'type'  => 'element',
                    'default' => 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'is_pro' => apply_filters('pixelavo_pro_feature', true),
                    'disabled' => apply_filters('pixelavo_pro_feature', true),
                ),
                array(
                    'id'  => 'form_submission',
                    'name'  => __( 'Track Forms', 'pixelavo' ),
                    'desc'  => __( 'Track form submissions from various form plugins.', 'pixelavo' ),
                    'type'  => 'element',
                    'default' => 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'section'  => 'pixelavo_form_submission',
                    'is_pro' => apply_filters('pixelavo_pro_feature', false),
                    'disabled' => false,
                    'child' => [
                        [
                            'id'  => 'fluent_form',
                            'name'  => __( 'Fluent Forms', 'pixelavo' ),
                            'desc'  => __( 'Select the Fluent Forms you want to track.', 'pixelavo' ),
                            'type'  => 'multiselect',
                            'required' => false,
                            'default' => [],
                            'placeholder' => 'Select a form.',
                            'options' => pixelavo_get_fluent_forms_list(), // Will be populated dynamically with form list
                            'is_pro' => apply_filters('pixelavo_pro_feature', false),
                            'disabled' => !is_plugin_active('fluentform/fluentform.php'),
                            'error_message' => __( 'This option require Fluent Forms plugin to be active. Please Make sure that Fluent Forms has been installed and Activated.', 'pixelavo'),
                        ],
                        [
                            'id'  => 'contact_form_7',
                            'name'  => __( 'Contact Form 7', 'pixelavo' ),
                            'desc'  => __( 'Select the Contact Form 7 forms you want to track.', 'pixelavo' ),
                            'type'  => 'multiselect',
                            'required' => false,
                            'default' => [],
                            'placeholder' => 'Select a form.',
                            'options' => pixelavo_get_cf7_forms_list(), // Will be populated dynamically with form list
                            'is_pro' => apply_filters('pixelavo_pro_feature', false),
                            'disabled' => !is_plugin_active('contact-form-7/wp-contact-form-7.php'),
                            'error_message' => __( 'This option require Contact Form 7 plugin to be active. Please Make sure that Contact Form 7 has been installed and Activated.', 'pixelavo' ),
                        ],
                        [
                            'id'  => 'wp_forms',
                            'name'  => __( 'WPForms', 'pixelavo' ),
                            'desc'  => __( 'Select the WPForms you want to track.', 'pixelavo' ),
                            'type'  => 'multiselect',
                            'required' => false,
                            'default' => [],
                            'placeholder' => 'Select a form.',
                            'options' => pixelavo_get_wpforms_list(), // Will be populated dynamically with form list
                            'is_pro' => apply_filters('pixelavo_pro_feature', false),
                            'disabled' => !is_plugin_active('wpforms-lite/wpforms.php'),
                            'error_message' => __( 'This option require WPForms plugin to be active. Please Make sure that WPForms has been installed and Activated.', 'pixelavo'),
                        ],
                        [
                            'id'  => 'ht_contactform',
                            'name'  => __( 'HT Contact Form', 'pixelavo' ),
                            'desc'  => __( 'Select the HT Contact Forms you want to track.', 'pixelavo' ),
                            'type'  => 'multiselect',
                            'required' => false,
                            'default' => [],
                            'placeholder' => 'Select a form.',
                            'options' => pixelavo_get_ht_contactform_list(),
                            'is_pro' => apply_filters('pixelavo_pro_feature', false),
                            'disabled' => !is_plugin_active('ht-contactform/contact-form-widget-elementor.php'),
                            'error_message' => __( 'This option require HT Contact Form plugin to be active. Please Make sure that HT Contact Form has been installed and Activated.', 'pixelavo'),
                        ]
                    ]
                ),
                array(
                    'id'  => 'youtube_video',
                    'name'  => __( 'Track YouTube Video', 'pixelavo' ),
                    'desc'  => __( 'When a YouTube embed video is played, this event will be triggered.', 'pixelavo' ),
                    'type'  => 'element',
                    'default' => 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'is_pro' => apply_filters('pixelavo_pro_feature', true),
                    'disabled' => apply_filters('pixelavo_pro_feature', true),
                ),
                array(
                    'id'  => 'vimeo_video',
                    'name'  => __( 'Track Vimeo Video', 'pixelavo' ),
                    'desc'  => __( 'When a Vimeo embed video is played, this event will be triggered.', 'pixelavo' ),
                    'type'  => 'element',
                    'default' => 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'is_pro' => apply_filters('pixelavo_pro_feature', true),
                    'disabled' => apply_filters('pixelavo_pro_feature', true),
                ),
                array(
                    'id'  => 'self_hosted_video',
                    'name'  => __( 'Track Self Hosted Video', 'pixelavo' ),
                    'desc'  => __( 'When a Self Hosted video is played, this event will be triggered.', 'pixelavo' ),
                    'type'  => 'element',
                    'default' => 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'is_pro' => apply_filters('pixelavo_pro_feature', true),
                    'disabled' => apply_filters('pixelavo_pro_feature', true),
                ),
                array(
                    'id'  => 'page_scroll',
                    'name'  => __( 'Track Page Scroll', 'pixelavo' ),
                    'desc'  => __( 'Fires when the website visitor scrolls the page.', 'pixelavo' ),
                    'type'  => 'element',
                    'default' => 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'section'  => 'pixelavo_page_scroll',
                    'is_pro' => apply_filters('pixelavo_pro_feature', false),
                    'disabled' => false,
                    'child' => [
                        [
                            'id'  => 'page_scroll_value',
                            'name'  => __( 'Trigger for scroll position.', 'pixelavo' ),
                            'desc'  => __( 'Fires the event when scroll position reaches the specified value in percentage. Default: 30%.', 'pixelavo' ),
                            'type'  => 'number',
                            'default' => '30',
                            'min' => '0',
                            'step' => '1',
                            'is_pro' => apply_filters('pixelavo_pro_feature', false),
                            'disabled' => false,
                        ]
                    ]
                ),
                array(
                    'id'  => 'time_on_page',
                    'name'  => __( 'Track Time on Page', 'pixelavo' ),
                    'desc'  => __( 'Fires when a visitor stays on a page for a specified amount of time.', 'pixelavo' ),
                    'type'  => 'element',
                    'default' => 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'section'  => 'pixelavo_time_on_page',
                    'is_pro' => apply_filters('pixelavo_pro_feature', false),
                    'disabled' => false,
                    'child' => [
                        [
                            'id'  => 'time_on_page_value',
                            'name'  => __( 'Trigger for time on page.', 'pixelavo' ),
                            'desc'  => __( 'Fires the event when time reaches the specified value in seconds. Default: 30 seconds.', 'pixelavo' ),
                            'type'  => 'number',
                            'default' => '10',
                            'min' => '0',
                            'step' => '1',
                            'is_pro' => apply_filters('pixelavo_pro_feature', false),
                            'disabled' => false,
                        ]
                    ]
                ),
                array(
                    'id'  => 'download_files',
                    'name'  => __( 'Track Downloads', 'pixelavo' ),
                    'desc'  => __( 'Fires when a website visitor opens a file with a specified extension.', 'pixelavo' ),
                    'type'  => 'element',
                    'default' => 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'section'  => 'pixelavo_download_files',
                    'is_pro' => apply_filters('pixelavo_pro_feature', false),
                    'disabled' => false,
                    'child' => [
                        [
                            'id'  => 'download_files_extensions',
                            'name'  => __( 'Download extensions.', 'pixelavo' ),
                            'desc'  => __( 'Extensions that will trigger the download event.', 'pixelavo' ),
                            'type'  => 'multiselect',
                            'required' => true,
                            'default' => ['pdf', 'zip'],
                            'options' => [
                                [
                                    'id' => 'doc',
                                    'text' => 'doc'
                                ],
                                [
                                    'id' => 'exe',
                                    'text' => 'exe'
                                ],
                                [
                                    'id' => 'js',
                                    'text' => 'js'
                                ],
                                [
                                    'id' => 'css',
                                    'text' => 'css'
                                ],
                                [
                                    'id' => 'pdf',
                                    'text' => 'pdf'
                                ],
                                [
                                    'id' => 'ppt',
                                    'text' => 'ppt'
                                ],
                                [
                                    'id' => 'tgz',
                                    'text' => 'tgz'
                                ],
                                [
                                    'id' => 'zip',
                                    'text' => 'zip'
                                ],
                                [
                                    'id' => 'xls',
                                    'text' => 'xls'
                                ],
                            ],
                            'is_pro' => apply_filters('pixelavo_pro_feature', false),
                        ]
                    ]
                ),
                array(
                    'id'  => 'signup',
                    'name'  => __( 'Track Complete Registration (Signup)', 'pixelavo' ),
                    'desc'  => __( 'Triggered when a visitor creates a new account on your website.', 'pixelavo' ),
                    'type'  => 'element',
                    'default' => 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'is_pro' => apply_filters('pixelavo_pro_feature', false),
                    'disabled' => false,
                ),
                array(
                    'id'  => 'login',
                    'name'  => __( 'Track Login', 'pixelavo' ),
                    'desc'  => __( 'Triggered when a visitor logs in to their account on your website.', 'pixelavo' ),
                    'type'  => 'element',
                    'default' => 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'is_pro' => apply_filters('pixelavo_pro_feature', false),
                    'disabled' => false,
                ),
                array(
                    'id'  => 'tel_click',
                    'name'  => __( 'Track TelClick', 'pixelavo' ),
                    'desc'  => __( 'Triggered when a visitor clicks on a telephone number on your website.', 'pixelavo' ),
                    'type'  => 'element',
                    'default' => 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'is_pro' => apply_filters('pixelavo_pro_feature', false),
                    'disabled' => false,
                ),
            ),
            'pixelavo_custom_events' => [
                array(
                    'id' => "custom_events_lists",
                    'type' => 'event-table',
                    'table_title' => esc_html__( 'Custom Event List', 'pixelavo' ),
                    'modal_open_button_text' => esc_html__( 'Add New Event' , 'pixelavo' ),
                    'modal_fields' => [
                        [
                            'id'  => 'event_name',
                            'name' => __( 'Event Name', 'pixelavo' ),
                            'desc'  => __( 'This name will help you to recognize your event.', 'pixelavo' ),
                            'type' => 'text',
                            'required' => true,
                            'placeholder' => __('ex: Pricing Page Visit', 'pixelavo')
                        ],
                        [
                            'id'  => 'event_trigger',
                            'name' => __( 'Trigger', 'pixelavo' ),
                            'type' => 'select',
                            'required' => true,
                            'default' => 0,
                            'options' => [
                                [
                                    'id' => 'page_visit',
                                    'text' => 'Page Visit'
                                ],
                                [
                                    'id' => 'click_on_element',
                                    'text' => apply_filters('pixelavo_pro_feature', true) ? 'Click on Element (Pro)' : 'Click on Element',
                                    'disabled' => apply_filters('pixelavo_pro_feature', true)
                                ],
                                [
                                    'id' => 'hover_on_element',
                                    'text' => apply_filters('pixelavo_pro_feature', true) ? 'Hover on Element (Pro)' : 'Hover on Element',
                                    'disabled' => apply_filters('pixelavo_pro_feature', true)
                                ],
                                [
                                    'id' => 'url_click',
                                    'text' => apply_filters('pixelavo_pro_feature', true) ? 'URL Click (Pro)' : 'URL Click',
                                    'disabled' => apply_filters('pixelavo_pro_feature', true)
                                ],
                            ]
                        ],
                        [
                            'id'  => 'event_trigger_page_list',
                            'name' => __( 'Target Pages', 'pixelavo' ),
                            'desc'  => __( 'Choose where on your site to trigger this event.', 'pixelavo' ),
                            'toggle' => [
                                'key' => 'event_trigger',
                                'operator' => '==',
                                'value' => 'page_visit'
                            ],
                            'type' => 'multiselect',
                            'default' => '0',
                            'required' => true,
                            'options' => pixelavo_page_list()
                        ],
                        [
                            'id'  => 'event_trigger_element',
                            'name' => __( 'Target CSS Selector', 'pixelavo' ),
                            'desc'  => __( 'Enter the CSS selector manually or use the picker to select visually.', 'pixelavo' ),
                            'toggle' => [
                                'key' => 'event_trigger',
                                'operator' => '==',
                                'value' => 'click_on_element'
                            ],
                            'type' => 'selector',
                            'placeholder' => '#element-id',
                            'required' => true,
                        ],
                        [
                            'id'  => 'event_trigger_hover',
                            'name' => __( 'Target CSS Selector', 'pixelavo' ),
                            'desc'  => __( 'Enter the CSS selector manually or use the picker to select visually.', 'pixelavo' ),
                            'toggle' => [
                                'key' => 'event_trigger',
                                'operator' => '==',
                                'value' => 'hover_on_element'
                            ],
                            'type' => 'selector',
                            'placeholder' => '#element-id',
                            'required' => true,
                        ],
                        [
                            'id'  => 'event_trigger_url',
                            'name' => __( 'Target URL', 'pixelavo' ),
                            'desc'  => __( 'Enter the target URL manually or use the picker to select a link from your site.', 'pixelavo' ),
                            'toggle' => [
                                'key' => 'event_trigger',
                                'operator' => '==',
                                'value' => 'url_click'
                            ],
                            'type' => 'selector',
                            'placeholder' => 'https://hasthemes.com',
                            'required' => true,
                        ],
                        [
                            'id'  => 'event_params',
                            'name' => __( 'Parameters', 'pixelavo' ),
                            'desc'  => __( 'Add custom parameters here.', 'pixelavo' ),
                            'type' => 'repeater',
                            'fields' => ['name', 'value']
                        ],
                    ],
                    'table_column' => [
                        'event_name' => __( 'Event', 'pixelavo' ),
                        'event_trigger'   => __( 'Trigger', 'pixelavo' ),
                        'action'     => __( 'Action', 'pixelavo' )
                    ],
                    'custom_event_limit' => apply_filters('pixelavo_custom_event_limit', 1)
                )
            ],
            'pixelavo_settings' => array(
                array(
                    'id'  => 'exclude_roles',
                    'name'  => __( 'Exclude Roles', 'pixelavo' ),
                    'desc'  => __( 'Users who are logged in and have roles selected here will not trigger your pixel events.', 'pixelavo' ),
                    'type' => 'multiselect',
                    'options' => pixelavo_editable_roles(),
                    'default' => ['administrator'],
                ),
                array(
                    'id'  => 'product_feed',
                    'name'  => __( 'Product Feed (WooCommerce)', 'pixelavo' ),
                    'desc'  => __( 'A Product Feed is required to use Facebook Dynamic Product Ads.', 'pixelavo' ),
                    'type'  => 'switcher',
                    'default' => 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'disabled' => !$this->woocommerce_active
                ),
                array(
                    'id'  => 'include_variations',
                    'name'  => __( 'Include Variations', 'pixelavo' ),
                    'desc'  => __( 'Having a lot of product variations can cause load issues with your feed, disable to exclude variations from the feed.', 'pixelavo' ),
                    'type' => 'switcher',
                    'default' => 'on',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'toggle' => array(
                        'key' => 'product_feed',
                        'operator' => '==',
                        'value' => 'on'
                    ),
                ),
                array(
                    'id'  => 'product_feed_url',
                    'name'  => __( 'Feed Url', 'pixelavo' ),
                    'desc'  => __( 'You\'ll need above URL when setting up your Facebook Product Catalog.', 'pixelavo' ),
                    'type' => 'text',
                    'default' => get_site_url() . '?feed=pixelavo',
                    'readonly' => true,
                    'toggle' => array(
                        'key' => 'product_feed',
                        'operator' => '==',
                        'value' => 'on'
                    ),
                ),
                array(
                    'id'  => 'product_feed_brand',
                    'name'  => __( 'Default Product Brand', 'pixelavo' ),
                    'desc'  => __( 'If a product does not have a brand name, we will assign a placeholder brand using the name you provide.', 'pixelavo' ),
                    'type' => 'text',
                    'default' => 'pixelavo',
                    'toggle' => array(
                        'key' => 'product_feed',
                        'operator' => '==',
                        'value' => 'on'
                    ),
                ),
                array(
                    'id'  => 'product_feed_gpc',
                    'name'  => __( 'Google Product Category', 'pixelavo' ),
                    'desc'  => __( 'Enter your numeric Google Product Category ID here. For instance, if your category is "Apparel & Accessories > Clothing > Dresses," enter 2271. You can find a current spreadsheet of all categories and IDs <a href="http://www.google.com/basepages/producttype/taxonomy-with-ids.en-US.xls">here</a>.', 'pixelavo' ),
                    'type' => 'text',
                    'default' => '',
                    'placeholder' => 'e.g. 2271',
                    'toggle' => array(
                        'key' => 'product_feed',
                        'operator' => '==',
                        'value' => 'on'
                    ),
                ),
                array(
                    'id'  => 'exclude_categories',
                    'name'  => __( 'Exclude Categories', 'pixelavo' ),
                    'desc'  => __( 'Selected product categories will not be included in your feed.', 'pixelavo' ),
                    'type' => 'multiselect',
                    'options' => pixelavo_get_product_categories(),
                    'toggle' => array(
                        'key' => 'product_feed',
                        'operator' => '==',
                        'value' => 'on'
                    ),
                ),
                array(
                    'id'  => 'exclude_tags',
                    'name'  => __( 'Exclude Tags', 'pixelavo' ),
                    'desc'  => __( 'Selected product tags will not be included in your feed.', 'pixelavo' ),
                    'type' => 'multiselect',
                    'options' => pixelavo_get_product_tags(),
                    'toggle' => array(
                        'key' => 'product_feed',
                        'operator' => '==',
                        'value' => 'on'
                    ),
                ),
                array(
                    'id'  => 'product_identifier',
                    'name'  => __( 'Product Identifier', 'pixelavo' ),
                    'desc'  => __( 'Set how to identify your product using the Facebook Pixel (content_id) and the feed (g:id). Please make sure to have SKUs set on your products if you choose WooCommerce SKU.', 'pixelavo' ),
                    'type' => 'select',
                    'options' => [
                        [
                            'id' => 'post_id',
                            'text' => 'WordPress Post ID (recommended)'
                        ],
                        [
                            'id' => 'sku',
                            'text' => 'WooCommerce SKU'
                        ]
                    ],
                    'default' => 'post_id',
                    'toggle' => array(
                        'key' => 'product_feed',
                        'operator' => '==',
                        'value' => 'on'
                    ),
                ),
                array(
                    'id'  => 'description_field',
                    'name'  => __( 'Description Field', 'pixelavo' ),
                    'desc'  => __( 'Set the field to use as your product description for the Facebook product catalog.', 'pixelavo' ),
                    'type' => 'select',
                    'options' => [
                        [
                            'id' => 'description',
                            'text' => 'Product Content'
                        ],
                        [
                            'id' => 'short',
                            'text' => 'Product Short Description'
                        ]
                    ],
                    'default' => 'description',
                    'toggle' => array(
                        'key' => 'product_feed',
                        'operator' => '==',
                        'value' => 'on'
                    ),
                ),
                array(
                    'id'  => 'edd_product_feed',
                    'name'  => __( 'Product Feed (Easy Digital Downloads)', 'pixelavo' ),
                    'desc'  => __( 'A Product Feed is required to use Facebook Dynamic Product Ads.', 'pixelavo' ),
                    'type'  => 'switcher',
                    'default' => 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    "disabled" => !$this->edd_active
                ),
                array(
                    'id'  => 'edd_product_feed_url',
                    'name'  => __( 'Feed Url', 'pixelavo' ),
                    'desc'  => __( 'You\'ll need above URL when setting up your Facebook Product Catalog.', 'pixelavo' ),
                    'type' => 'text',
                    'default' => get_site_url() . '?feed=pixelavo-edd',
                    'readonly' => true,
                    'toggle' => array(
                        'key' => 'edd_product_feed',
                        'operator' => '==',
                        'value' => 'on'
                    ),
                    "disabled" => !$this->edd_active
                ),
                array(
                    'id'  => 'edd_product_feed_brand',
                    'name'  => __( 'Default Product Brand', 'pixelavo' ),
                    'desc'  => __( 'If a product does not have a brand name, we will assign a placeholder brand using the name you provide.', 'pixelavo' ),
                    'type' => 'text',
                    'default' => 'pixelavo',
                    'required' => true,
                    'toggle' => array(
                        [
                            'key' => 'edd_product_feed',
                            'operator' => '==',
                            'value' => 'on'
                        ]
                    ),
                    "disabled" => !$this->edd_active
                ),
                array(
                    'id'  => 'edd_exclude_categories',
                    'name'  => __( 'Exclude Categories', 'pixelavo' ),
                    'desc'  => __( 'Selected product categories will not be included in your feed.', 'pixelavo' ),
                    'type' => 'multiselect',
                    'options' => pixelavo_get_download_categories(),
                    'toggle' => array(
                        'key' => 'edd_product_feed',
                        'operator' => '==',
                        'value' => 'on'
                    ),
                    "disabled" => !$this->edd_active
                ),
                array(
                    'id'  => 'edd_exclude_tags',
                    'name'  => __( 'Exclude Tags', 'pixelavo' ),
                    'desc'  => __( 'Selected product tags will not be included in your feed.', 'pixelavo' ),
                    'type' => 'multiselect',
                    'options' => pixelavo_get_download_tags(),
                    'toggle' => array(
                        'key' => 'edd_product_feed',
                        'operator' => '==',
                        'value' => 'on'
                    ),
                    "disabled" => !$this->edd_active
                ),
                array(
                    'id'  => 'edd_product_identifier',
                    'name'  => __( 'Product Identifier', 'pixelavo' ),
                    'desc'  => __( 'Set how to identify your product using the Facebook Pixel (content_id) and the feed (g:id). Please make sure to have SKUs set on your products if you choose WooCommerce SKU.', 'pixelavo' ),
                    'type' => 'select',
                    'options' => [
                        [
                            'id' => 'post_id',
                            'text' => 'WordPress Post ID (recommended)'
                        ],
                        [
                            'id' => 'sku',
                            'text' => 'WooCommerce SKU'
                        ]
                    ],
                    'default' => 'post_id',
                    'toggle' => array(
                        'key' => 'edd_product_feed',
                        'operator' => '==',
                        'value' => 'on'
                    ),
                    "disabled" => !$this->edd_active
                ),
                array(
                    'id'  => 'edd_description_field',
                    'name'  => __( 'Description Field', 'pixelavo' ),
                    'desc'  => __( 'Set the field to use as your product description for the Facebook product catalog.', 'pixelavo' ),
                    'type' => 'select',
                    'options' => [
                        [
                            'id' => 'description',
                            'text' => 'Product Content'
                        ],
                        [
                            'id' => 'short',
                            'text' => 'Product Excerpt'
                        ]
                    ],
                    'default' => 'description',
                    'toggle' => array(
                        'key' => 'edd_product_feed',
                        'operator' => '==',
                        'value' => 'on'
                    ),
                    "disabled" => !$this->edd_active
                ),
                array(
                    'id'  => 'additional_user_info',
                    'name'  => __( 'Additional user information', 'pixelavo' ),
                    'desc'  => __( 'Include HTTP referrer, user language, post categories, and tags as event parameters, allowing you to build more precise custom audiences.', 'pixelavo' ),
                    'type'  => 'switcher',
                    'default' => 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'is_pro' => apply_filters('pixelavo_pro_feature', true)
                ),
                array(
                    'id'  => 'advanced_matching',
                    'name'  => __( 'Advanced Matching', 'pixelavo' ),
                    'desc'  => __( 'Enable Advanced Matching for all events. According to Facebook, advanced matching has assisted businesses in increasing attributed conversions by 10% and campaign reach by 20% through retargeting.', 'pixelavo' ),
                    'type'  => 'switcher',
                    'default' => 'off',
                    'label_on' => __( 'On', 'pixelavo' ),
                    'label_off' => __( 'Off', 'pixelavo' ),
                    'is_pro' => apply_filters('pixelavo_pro_feature', true)
                ),
                array(
                    'id' => 'api_keys_info',
                    'type' => 'html',
                    'name' => __( 'AI API Keys Configuration', 'pixelavo' ),
                    'desc' => __( 'To use AI features, you need at least one API key from your preferred AI provider.', 'pixelavo' ),
                ),
                array(
                    'id' => 'gemini_api_key',
                    'type' => 'password',
                    'name' => __( 'Google Gemini API Key', 'pixelavo' ),
                    'desc' => __( 'Enter your Google Gemini API key to enable AI features. Get your free API key from <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener">Google AI Studio</a>. Your key is encrypted and stored securely.', 'pixelavo' ),
                ),
                // Temporarily hidden - Claude API key option will be added later
                // array(
                //     'id' => 'claude_api_key',
                //     'type' => 'password',
                //     'name' => __( 'Claude', 'pixelavo' ),
                //     'desc' => __( 'Enter your Claude API Key', 'pixelavo' ),
                // ),
                array(
                    'id' => 'openai_api_key',
                    'type' => 'password',
                    'name' => __( 'OpenAI API Key', 'pixelavo' ),
                    'desc' => __( 'Enter your OpenAI API key to use ChatGPT models for AI features. Get your API key from <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener">OpenAI Platform</a>. Note: OpenAI requires billing setup. Your key is encrypted and stored securely.', 'pixelavo' ),
                ),
            ),
            'pixelavo_ad_diagnostic_tool' => array(
                array(
                    'id' => 'run_ad_diagnostic',
                    'type' => 'button',
                    'button_text' => __( 'Run Ad Diagnostic', 'pixelavo' ),
                    'button_type' => 'button',
                    'button_class' => '',
                ),
            ),
            'pixelavo_ad_copy_generator' => array_filter([
                // Hidden field to check API key availability
                array(
                    'id' => 'ai_type',
                    'name' => '',
                    'type' => 'hidden',
                    'options' => array_values(array_filter([
                        // Include options only if API keys exist
                        $this->api_key_exists($settings_data, 'gemini_api_key') ? array(
                            'id' => 'gemini',
                            'title' => 'Gemini',
                        ) : null,
                        $this->api_key_exists($settings_data, 'openai_api_key') ? array(
                            'id' => 'openai',
                            'title' => 'OpenAI',
                        ) : null,
                    ])),
                ),
                array(
                    'id' => 'product_description',
                    'name' => __( 'Product/Service Description', 'pixelavo' ),
                    'desc' => __( 'Provide a brief description of your product or service to generate targeted ad copy', 'pixelavo' ),
                    'type' => 'text',
                    'placeholder' => __( 'E.g., Wireless earbuds with noise cancellation, 24-hour battery life, and crystal clear sound quality. Perfect for fitness enthusiasts and professionals.', 'pixelavo' ),
                    'rows' => 3,
                ),
                // Conditionally show product selection if WC or EDD is active
                (pixelavo_is_woocommerce_active() || pixelavo_is_edd_active()) ? array(
                    'id' => 'existing_product_id',
                    'name' => __( 'Or Select Existing Product', 'pixelavo' ),
                    'desc' => __( 'Alternatively, choose an existing product from your store (optional)', 'pixelavo' ),
                    'type' => 'select',
                    'options' => array_merge(
                        [['id' => '', 'text' => __( '-- Select a product (optional) --', 'pixelavo' )]],
                        $this->get_products()
                    ),
                ) : null,
                array(
                    'id' => 'campaign_goal',
                    'name' => __( 'Campaign Goal', 'pixelavo' ),
                    'type' => 'radio_button_group',
                    'column' => 2,
                    'options' => [
                        [
                            'id' => 'brand_awareness',
                            'title' => 'Brand Awareness',
                            'desc' => [
                                'Increase brand awareness and visibility'
                            ]
                        ],
                        [
                            'id' => 'drive_traffic',
                            'title' => 'Drive Traffic',
                            'desc' => ['Increase traffic to your website']
                        ],
                        [
                            'id' => 'drive_sales',
                            'title' => 'Drive Sales',
                            'desc' => ['Increase sales and revenue']
                        ],
                        [
                            'id' => 'reengage_customers',
                            'title' => 'Re-engage Customers',
                            'desc' => ['Re-engage customers and increase customer loyalty']
                        ],
                    ],
                    'default' => 'drive_sales',
                ),
                array(
                    'id' => 'tone_of_voice',
                    'name' => __( 'Tone of Voice', 'pixelavo' ),
                    'type' => 'select',
                    'options' => [
                        [
                            'id' => 'friendly',
                            'text' => 'Friendly'
                        ],
                        [
                            'id' => 'professional',
                            'text' => 'Professional'
                        ],
                        [
                            'id' => 'urgent',
                            'text' => 'Urgent'
                        ],
                        [
                            'id' => 'casual',
                            'text' => 'Casual'
                        ],
                        [
                            'id' => 'luxury',
                            'text' => 'Luxury'
                        ],
                    ],
                    'default' => 'friendly',
                ),
                array(
                    'id' => 'target_audience',
                    'name' => __( 'Target Audience', 'pixelavo' ),
                    'desc' => __( 'Describe your ideal customer', 'pixelavo' ),
                    'type' => 'text',
                    'placeholder' => __( 'e.g., busy professionals, fitness enthusiasts, new parents', 'pixelavo' ),
                ),
                array(
                    'id' => 'special_offer',
                    'name' => __( 'Special Offer', 'pixelavo' ),
                    'type' => 'text',
                    'placeholder' => __( 'e.g., 20% off, Free shipping, Buy 2 get 1 free', 'pixelavo' ),
                ),
                array(
                    'id' => 'key_features',
                    'name' => __( 'Key Features (Max 3)', 'pixelavo' ),
                    'max' => 3,
                    'type' => 'checkbox_button_group',
                    'options' => [
                        [
                            'id' => 'high_quality',
                            'title' => 'High Quality',
                        ],
                        [
                            'id' => 'fast_shipping',
                            'title' => 'Fast Shipping',
                        ],
                        [
                            'id' => 'money_back_guarantee',
                            'title' => 'Money Back Guarantee',
                        ],
                        [
                            'id' => 'limited_edition',
                            'title' => 'Limited Edition',
                        ],
                        [
                            'id' => 'echo_friendly',
                            'title' => 'Echo Friendly',
                        ],
                        [
                            'id' => 'handmade',
                            'title' => 'Handmade',
                        ],
                        [
                            'id' => 'award_winning',
                            'title' => 'Award Winning',
                        ],
                        [
                            'id' => 'best_seller',
                            'title' => 'Best Seller',
                        ],
                    ],
                    'default' => [],
                ),
                array(
                    'id' => 'brand_voice_reference',
                    'name' => __( 'Brand Voice Reference (Optional)', 'pixelavo' ),
                    'type' => 'text',
                    'placeholder' => __( 'E.g., "Revolutionary tech that transforms your daily routine. Join thousands who\'ve already upgraded their lifestyle. Limited time offer - don\'t miss out!"', 'pixelavo' ),
                ),
                array(
                    'id' => 'copywriting_framework',
                    'name' => __( 'Copywriting Framework (Optional)', 'pixelavo' ),
                    'type' => 'radio_button_group',
                    'column' => 2,
                    'options' => [
                        [
                            'id' => 'auto_select',
                            'title' => 'Auto-Select',
                            'desc' => [
                                'Let AI choose the best framework based on your campaign goal',
                                'General purpose, AI will optimize based on your settings'
                            ]
                        ],
                        [
                            'id' => 'aida',
                            'title' => 'AIDA',
                            'desc' => [
                                'Attention → Interest → Desire → Action',
                                'General purpose, versatile for all campaign goals'
                            ]
                        ],
                        [
                            'id' => 'pas',
                            'title' => 'PAS',
                            'desc' => [
                                'Problem → Action → Solution',
                                'Pain point marketing, problem-solving products'
                            ],
                            'help' => "Example: Tired of X? → It's getting worse because... → Our product fixes it!"
                        ],
                        [
                            'id' => 'fomo',
                            'title' => 'FOMO',
                            'desc' => [
                                'Fear of Missing Out',
                                'Limited offers, seasonal sales, urgency campaigns'
                            ],
                            'help' => "Example: Only 24 hours left → 10,000 already bought → Don't miss out!"
                        ],
                        [
                            'id' => 'social_proof',
                            'title' => 'Social Proof',
                            'desc' => [
                                'Leverage popularity and testimonials',
                                'Building trust, new product launches, skeptical audiences'
                            ],
                            'help' => "Example: 50k+ customers → 98% satisfaction → Join them today!"
                        ],
                        [
                            'id' => 'before_after',
                            'title' => 'Before & After',
                            'desc' => [
                                'Show transformation and results',
                                'Fitness, beauty, lifestyle, improvement products'
                            ],
                            'help' => "Example: From struggle → Through our solution → To success!"
                        ],
                        [
                            'id' => 'question_hook',
                            'title' => 'Question Hook',
                            'desc' => [
                                'Start with engaging questions',
                                'Curiosity-driven campaigns, problem-aware audiences'
                            ],
                            'help' => "Example: Ever wondered why...? → Here's what most people don't know → The answer is..."
                        ],
                        [
                            'id' => 'benefits',
                            'title' => 'Benefits',
                            'desc' => [
                                'Focus on key product benefits',
                                'Feature-rich products, value-conscious customers'
                            ],
                            'help' => "Example: Save 5 hours daily → More family time + higher productivity → Start now!"
                        ],
                    ],
                    'default' => 'auto_select',
                ),
                array(
                    'id' => 'ai_model',
                    'name' => __( 'AI Model', 'pixelavo' ),
                    'desc' => __( 'Select the AI model to use', 'pixelavo' ),
                    'type' => 'select',
                    'default' => $this->api_key_exists($settings_data, 'gemini_api_key') ? 'gemini-2.5-flash' : ($this->api_key_exists($settings_data, 'openai_api_key') ? 'gpt-4o-mini' : ''),
                    'options' => array_values(array_filter([
                        // Show Gemini models only if API key exists
                        $this->api_key_exists($settings_data, 'gemini_api_key') ? array(
                            'id' => 'gemini-2.5-pro',
                            'text' => 'Gemini 2.5 Pro',
                        ) : null,
                        $this->api_key_exists($settings_data, 'gemini_api_key') ? array(
                            'id' => 'gemini-2.5-flash',
                            'text' => 'Gemini 2.5 Flash',
                        ) : null,
                        $this->api_key_exists($settings_data, 'gemini_api_key') ? array(
                            'id' => 'gemini-2.5-flash-lite',
                            'text' => 'Gemini 2.5 Flash Lite',
                        ) : null,
                        $this->api_key_exists($settings_data, 'gemini_api_key') ? array(
                            'id' => 'gemini-2.0-flash-exp',
                            'text' => 'Gemini 2.0 Flash (Experimental)',
                        ) : null,
                        $this->api_key_exists($settings_data, 'gemini_api_key') ? array(
                            'id' => 'gemini-1.5-pro',
                            'text' => 'Gemini 1.5 Pro',
                        ) : null,
                        $this->api_key_exists($settings_data, 'gemini_api_key') ? array(
                            'id' => 'gemini-1.5-flash',
                            'text' => 'Gemini 1.5 Flash',
                        ) : null,
                        $this->api_key_exists($settings_data, 'gemini_api_key') ? array(
                            'id' => 'gemini-1.5-flash-8b',
                            'text' => 'Gemini 1.5 Flash-8B',
                        ) : null,
                        $this->api_key_exists($settings_data, 'gemini_api_key') ? array(
                            'id' => 'gemini-1.0-pro',
                            'text' => 'Gemini 1.0 Pro',
                        ) : null,
                        // Temporarily hidden - Claude option will be added later
                        // !empty($settings_data['claude_api_key']) ? array(
                        //     'id' => 'claude-3-5-sonnet',
                        //     'text' => 'Claude 3.5 Sonnet',
                        // ) : null,
                        // !empty($settings_data['claude_api_key']) ? array(
                        //     'id' => 'claude-3-5-haiku',
                        //     'text' => 'Claude 3.5 Haiku',
                        // ) : null,
                        // !empty($settings_data['claude_api_key']) ? array(
                        //     'id' => 'claude-3-opus',
                        //     'text' => 'Claude 3 Opus',
                        // ) : null,
                        // Show OpenAI models only if API key exists
                        $this->api_key_exists($settings_data, 'openai_api_key') ? array(
                            'id' => 'gpt-5',
                            'text' => 'ChatGPT 5',
                        ) : null,
                        $this->api_key_exists($settings_data, 'openai_api_key') ? array(
                            'id' => 'gpt-5-mini',
                            'text' => 'ChatGPT 5 Mini',
                        ) : null,
                        $this->api_key_exists($settings_data, 'openai_api_key') ? array(
                            'id' => 'gpt-5-nano',
                            'text' => 'ChatGPT 5 Nano',
                        ) : null,
                        $this->api_key_exists($settings_data, 'openai_api_key') ? array(
                            'id' => 'gpt-4o',
                            'text' => 'GPT-4o',
                        ) : null,
                        $this->api_key_exists($settings_data, 'openai_api_key') ? array(
                            'id' => 'gpt-4o-mini',
                            'text' => 'GPT-4o Mini',
                        ) : null,
                        $this->api_key_exists($settings_data, 'openai_api_key') ? array(
                            'id' => 'gpt-4-turbo',
                            'text' => 'GPT-4 Turbo',
                        ) : null,
                        $this->api_key_exists($settings_data, 'openai_api_key') ? array(
                            'id' => 'gpt-4',
                            'text' => 'GPT-4',
                        ) : null,
                        $this->api_key_exists($settings_data, 'openai_api_key') ? array(
                            'id' => 'gpt-3.5-turbo',
                            'text' => 'GPT-3.5 Turbo',
                        ) : null,
                    ])),
                ),
                array(
                    'id' => 'generate_ad_copy',
                    'type' => 'button',
                    'button_text' => __( 'Generate Ad Copy', 'pixelavo' ),
                    'button_type' => 'button',
                    'button_class' => '',
                    'action' => 'generate_ad_copy',
                ),
            ]),
            'pixelavo_ai_consultant' => array(
                array(
                    'id' => 'ai_type',
                    'name' => __( 'AI Type', 'pixelavo' ),
                    'type' => 'select',
                    'default' => $this->api_key_exists($settings_data, 'gemini_api_key') ? 'gemini' : ($this->api_key_exists($settings_data, 'openai_api_key') ? 'openai' : ''),
                    'options' => array_values(array_filter([
                        // Show Gemini only if API key exists
                        $this->api_key_exists($settings_data, 'gemini_api_key') ? array(
                            'id' => 'gemini',
                            'title' => 'Gemini',
                        ) : null,
                        // Temporarily hidden - Claude option will be added later
                        // !empty($settings_data['claude_api_key']) ? array(
                        //     'id' => 'claude',
                        //     'title' => 'Claude',
                        // ) : null,
                        // Show OpenAI only if API key exists
                        $this->api_key_exists($settings_data, 'openai_api_key') ? array(
                            'id' => 'openai',
                            'title' => 'ChatGPT',
                        ) : null,
                    ])),
                ),
                array(
                    'id' => 'model_type',
                    'name' => __( 'Model Type', 'pixelavo' ),
                    'type' => 'select',
                    'default' => $this->api_key_exists($settings_data, 'gemini_api_key') ? 'gemini-2.5-flash' : ($this->api_key_exists($settings_data, 'openai_api_key') ? 'gpt-4o-mini' : ''),
                    'options' => array_merge(
                        // Show Gemini models only if Gemini API key exists
                        $this->api_key_exists($settings_data, 'gemini_api_key') ? [
                            'gemini' => array(
                                array(
                                    'id' => 'gemini-2.5-pro',
                                    'title' => 'Gemini 2.5 Pro',
                                ),
                                array(
                                    'id' => 'gemini-2.5-flash',
                                    'title' => 'Gemini 2.5 Flash',
                                ),
                                array(
                                    'id' => 'gemini-2.5-flash-lite',
                                    'title' => 'Gemini 2.5 Flash Lite',
                                ),
                                array(
                                    'id' => 'gemini-2.0-flash-exp',
                                    'title' => 'Gemini 2.0 Flash (Experimental)',
                                ),
                                array(
                                    'id' => 'gemini-1.5-pro',
                                    'title' => 'Gemini 1.5 Pro',
                                ),
                                array(
                                    'id' => 'gemini-1.5-flash',
                                    'title' => 'Gemini 1.5 Flash',
                                ),
                                array(
                                    'id' => 'gemini-1.5-flash-8b',
                                    'title' => 'Gemini 1.5 Flash-8B',
                                ),
                                array(
                                    'id' => 'gemini-1.0-pro',
                                    'title' => 'Gemini 1.0 Pro',
                                ),
                            )
                        ] : [],
                        // Temporarily hidden - Claude models will be added later
                        // !empty($settings_data['claude_api_key']) ? [
                        //     'claude' => array(
                        //         array(
                        //             'id' => 'claude-3.5',
                        //             'title' => 'Claude 3.5',
                        //         ),
                        //     )
                        // ] : [],
                        // Show OpenAI models only if OpenAI API key exists
                        $this->api_key_exists($settings_data, 'openai_api_key') ? [
                            'openai' => array(
                                array(
                                    'id' => 'gpt-5',
                                    'title' => 'ChatGPT 5',
                                ),
                                array(
                                    'id' => 'gpt-5-mini',
                                    'title' => 'ChatGPT 5 Mini',
                                ),
                                array(
                                    'id' => 'gpt-5-nano',
                                    'title' => 'ChatGPT 5 Nano',
                                ),
                                array(
                                    'id' => 'gpt-4o',
                                    'title' => 'GPT-4o',
                                ),
                                array(
                                    'id' => 'gpt-4o-mini',
                                    'title' => 'GPT-4o Mini',
                                ),
                                array(
                                    'id' => 'gpt-4-turbo',
                                    'title' => 'GPT-4 Turbo',
                                ),
                                array(
                                    'id' => 'gpt-4',
                                    'title' => 'GPT-4',
                                ),
                                array(
                                    'id' => 'gpt-3.5-turbo',
                                    'title' => 'GPT-3.5 Turbo',
                                ),
                            )
                        ] : []
                    ),
                ),
            ),
        );

        return apply_filters( 'pixelavo_admin_fields', $settings );

    }

}