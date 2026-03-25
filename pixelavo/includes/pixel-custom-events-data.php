<?php

namespace Pixelavo;

/* Exit if accessed directly */
if (!defined('ABSPATH')) {
    exit;
}

class PixelCustomEventsData{
    
    private static $_instance = null;

    /**
     * Instance.
     * Initializes a singleton instance.
     * @return self class
     */
    static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    function __construct() {
        /** Custom Event on Page Visit */
        if(!pixelavo_check_exclude_roles()) {
            if(pixelavo_custom_event_exists('page_visit')) {
                add_action('wp_footer', [$this, 'custom_page_visit_event']);
            }
        }
    }

    /**
     * Custom Event on Page Visit
     * @return void
     */
    function custom_page_visit_event() {
        if(pixelavo_pixel_status()) {
            global $pixelavoEventsLocalizedData;
            
            $post_id = get_queried_object_id();
            $custom_events = pixelavo_get_custom_events();
            $events = array_filter($custom_events, function($event) {
                return $event['event_trigger'] === 'page_visit';
            });
            foreach ($events as $event) {
                if(!$post_id) {
                    if(is_plugin_active( 'woocommerce/woocommerce.php') && is_shop()) {
                        $post_id = wc_get_page_id( 'shop' );
                    }
                    // if(is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php') && is_post_type_archive('download')) {
                    //     $post_id = true;
                    // }
                }
                if(
                    in_array($post_id, $event['event_trigger_page_list']) ||
                    in_array('term'.$post_id, $event['event_trigger_page_list'])  ||
                    (in_array('all_posts', $event['event_trigger_page_list']) && get_post_type($post_id) == 'post' ) ||
                    (in_array('all_products', $event['event_trigger_page_list']) && get_post_type($post_id) == 'product' ) ||
                    (in_array('all_downloads', $event['event_trigger_page_list']) && get_post_type($post_id) == 'download' ) ||
                    (is_archive() && !empty(get_queried_object()->taxonomy) && in_array(get_queried_object()->taxonomy, $event['event_trigger_page_list']) )
                ) {
                    $name = $event['event_name'];
                    $data = pixelavo_prepare_custom_event_data($event['event_params']);
                    $data = array_merge($data, apply_filters('pixelavo_additional_user_info', [], [] ), pixelavo_get_event_common_data());
                    $event_id = str_replace(' ', '', $name).time();
    
                    $pixelavoEventsLocalizedData[] = ['track' => 'trackCustom', 'event' => $name, 'data' => $data, 'eventID' => $event_id];
                    pixelavo_run_conversions_api($name, $data, $event_id);
                }
            }
        }
    }
}

/**
 * Initialize PixelCustomEventsData Class
 */
PixelCustomEventsData::instance();
