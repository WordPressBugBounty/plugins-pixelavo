<?php

namespace Pixelavo;

/* Exit if accessed directly */
if (!defined('ABSPATH')) {
    exit;
}

class Modifier{
    
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
        add_action('init', [$this, 'init']);
    }

    function init() {
        $plugin_data = get_file_data( PIXELAVO_PL_ROOT, [ 'Version' => 'Version' ], 'plugin' );
        $version = $plugin_data['Version'];
        if(version_compare($version,'1.2.2','>') && !get_option('pixelavo_other_events_setting_modify', false)){
            $other_events_data = get_option('pixelavo_other_events', [] );
            if(array_key_exists('page_scroll_value', $other_events_data)){
                update_option('pixelavo_page_scroll', [
                    'page_scroll' => [
                        'page_scroll_value' => $other_events_data['page_scroll_value']
                    ]
                ]);
            }
            if(array_key_exists('time_on_page_value', $other_events_data)){
                update_option('pixelavo_time_on_page', [
                    'time_on_page' => [
                        'time_on_page_value' => $other_events_data['time_on_page_value']
                    ]
                ]);
            }
            if(array_key_exists('download_files_extensions', $other_events_data)){
                update_option('pixelavo_download_files', [
                    'download_files' => [
                        'download_files_extensions' => $other_events_data['download_files_extensions']
                    ]
                ]);
            }
            update_option('pixelavo_other_events_setting_modify', true);
        }
        if(version_compare($version,'1.2.3','>') && !get_option('pixelavo_setting_modify_124', false)){
            $settings = get_option('pixelavo_settings', [] );
            
            /* Purchase Evetn Settings Modifier */
            $purchase_event_data = [];
            if(array_key_exists('purchase_event_trigger', $settings)){
                $purchase_event_data['purchase_event_trigger'] = $settings['purchase_event_trigger'];
            }
            if(array_key_exists('purchase_additional_info', $settings)){
                $purchase_event_data['purchase_additional_info'] = $settings['purchase_additional_info'];
            }
            update_option('pixelavo_wc_purchase', array_replace_recursive([
                'purchase' => $purchase_event_data,
            ], get_option('pixelavo_wc_purchase', [])));
            update_option('pixelavo_edd_purchase', array_replace_recursive([
                'purchase' => $purchase_event_data,
            ], get_option('pixelavo_edd_purchase', [])));

            /* View Content Evetn Settings Modifier */
            $view_content_event_data = [];
            if(array_key_exists('view_content_delay', $settings)){
                $view_content_event_data['view_content_delay'] = $settings['view_content_delay'];
            }
            update_option('pixelavo_wc_view_content', array_replace_recursive([
                'view_content' => $view_content_event_data,
            ], get_option('pixelavo_wc_view_content', [])));
            update_option('pixelavo_edd_view_content', array_replace_recursive([
                'view_content' => $view_content_event_data,
            ], get_option('pixelavo_edd_view_content', [])));

            update_option('pixelavo_setting_modify_124', true);
        }
    }

}

/**
 * Initialize Modifier Class
 */
Modifier::instance();
