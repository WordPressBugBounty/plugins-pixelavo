<?php

namespace Pixelavo;

/* Exit if accessed directly */
if (!defined('ABSPATH')) {
    exit;
}

class PixelEddEventsData{
    
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
        if(!pixelavo_check_exclude_roles()) {
            /** Search */
            if(pixelavo_edd_event_exists('edd_search')) {
                add_action('wp_footer', [$this, 'search_data']);
            }

            /** View Category */
            if(pixelavo_edd_event_exists('edd_view_category')) {
                add_action('wp_footer', [$this, 'view_category_data']);
            }

            /** View Content */
            if(pixelavo_edd_event_exists('edd_view_content')) {
                add_action('wp_footer', [$this, 'view_content_data']);
            }

            /** 
             * Remove From Cart
             */
            if(pixelavo_edd_event_exists('edd_remove_from_cart')) {
                add_action('wp_ajax_pixelavo_edd_ajax_remove_from_cart', [$this, 'remove_from_cart_data']);
                add_action('wp_ajax_nopriv_pixelavo_edd_ajax_remove_from_cart', [$this, 'remove_from_cart_data']);
            }
        }
    }

    /**
     * Search data.
     * Run when user search for any product and go to `search` page.
     * @return void
     */
    function search_data() {
        if(pixelavo_pixel_status() && function_exists('is_search') && is_search()) {
            global $pixelavoEventsLocalizedData;

            $search_query = get_search_query();
            $args = [
                's' => $search_query,
                'status' => 'publish',
                'visibility' => 'visible',
                'fields'    => 'ids',
                'post_type' => 'download',
                'posts_per_page'   => -1,
            ];
            $download_ids = get_posts($args);
            
            if(empty($download_ids)) {
                return;
            }
            
            $ids = [];
            $category = [];
            $values = 0;
            $event_id = 'Search.'.time();
            
            foreach ($download_ids as $id) {
                $ids[] = $id;
                $terms = get_the_terms($id, 'download_category');
                if (is_array($terms) && !is_wp_error($terms)) {
                    foreach ($terms as $cat) {
                        if(!in_array($cat->name, $category)) {
                            $category[] = $cat->name;
                        }
                    }
                }
                $download = edd_get_download($id);
                if ($download) {
                    $values = $values + floatval($download->get_price());
                }
            }

            $data = [
                'content_category' => implode(', ', $category),
                'content_ids' => $ids,
                'currency' => edd_get_option('currency', 'USD'),
                'search_string' => $search_query,
                'content_type' => 'product',
                'value' => round($values, 2) // Use round() instead of number_format() to return numeric value
            ];

            $data = array_merge($data, apply_filters('pixelavo_additional_user_info', [], $data['content_ids'] ), pixelavo_get_event_common_data());

            if(!empty($data['content_ids'])) {
                $pixelavoEventsLocalizedData[] = ['track' => 'track', 'event' => 'Search', 'data' => $data, 'eventID' => $event_id];
                pixelavo_run_conversions_api('Search', $data, $event_id);
            }
        }
    }
    
    /**
     * View Category data.
     * Run when user to go `product category` page.
     * @return void
     */
    function view_category_data() {
        if(pixelavo_pixel_status() && function_exists('is_tax') && is_tax('download_category')) {
            global $pixelavoEventsLocalizedData;

            $id = get_queried_object_id();
            if(term_exists($id)) {
                $term = get_term($id);
                $args = [
                    'fields'    => 'ids',
                    'post_type' => 'download',
                    'posts_per_page'   => -1,
                    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
                    'tax_query' => [
                        [
                            'taxonomy' => $term->taxonomy,
                            'terms' => $id
                        ],
                    ],
                ];
                $download_ids = get_posts($args);

                $download = edd_get_download($id);

                $ids = [];
                $titles = [];
                $contents = [];
                $values = 0;
                $event_id = 'ViewCategory.'.trim(strtolower(str_replace(' ', '', $term->name))).'.'.time();

                foreach ($download_ids as $download_id) {
                    $download = edd_get_download($download_id);
                    $ids[] = $download_id;
                    $titles[] = $download->post_title;
                    $contents[] = ['id' => $download_id, 'quantity' => 1];
                    $values = $values + floatval($download->get_price());
                }

                $data = [
                    'content_ids' => $ids,
                    'content_category' => $term->name,
                    'content_name' => implode(', ', $titles),
                    'content_type' => 'product',
                    'contents' => $contents,
                    'currency' => edd_get_option('currency', 'USD'),
                    'value' => round($values, 2) // Use round() instead of number_format() to return numeric value
                ];

                $data = array_merge($data, apply_filters('pixelavo_additional_user_info', [], $data['content_ids'] ), pixelavo_get_event_common_data());

                if(!empty($data['content_ids'])) {
                    $pixelavoEventsLocalizedData[] = ['track' => 'trackCustom', 'event' => 'ViewCategory', 'data' => $data, 'eventID' => $event_id];
                    pixelavo_run_conversions_api('ViewCategory', $data, $event_id);
                }
            }
        }
    }
    
    /**
     * View Content data.
     * Run when user to go `single product` page.
     * @return void
     */
    function view_content_data() {
        if(pixelavo_pixel_status() && function_exists('is_singular') && is_singular('download')) {
            global $pixelavoEventsLocalizedData;

            $id = get_queried_object_id();
            $download = edd_get_download($id);
            
            $category = [];
            $event_id = 'ViewContent.'.$id.'.'.time();
            $terms = get_the_terms($id, 'download_category');
            if (is_array($terms) && !is_wp_error($terms)) {
                foreach ($terms as $cat) {
                    if(!in_array($cat->name, $category)) {
                        $category[] = $cat->name;
                    }
                }
            }
            if ( $download ) {
                $content_type = $download->has_variable_prices() ? 'product_group' : 'product';
                $data = [
                    'content_ids' => [$id],
                    'content_category' => implode(', ', $category),
                    'content_name' => $download->post_title,
                    'content_type' => $content_type,
                    'contents' => [['id' => $id, 'quantity' => 1]], // Add contents for better match quality
                    'currency' => edd_get_option('currency', 'USD'),
                    'value' => round($download->get_price(), 2) // Use round() instead of number_format(),
                ];

                $data = array_merge($data, apply_filters('pixelavo_additional_user_info', [], $data['content_ids'] ), pixelavo_get_event_common_data());

                if(!empty($data['content_ids'])) {
                    $view_content_event_settings = pixelavo_get_option('edd_view_content', 'pixelavo_edd_view_content');
                    if(isset($view_content_event_settings['view_content_delay']) && (int) $view_content_event_settings['view_content_delay'] > 0) {
                        $pixelavoEventsLocalizedData[] = ['track' => 'track', 'event' => 'ViewContent', 'data' => $data, 'eventID' => $event_id, 'isEventDelay' => true, 'eventDelay' => $view_content_event_settings['view_content_delay']];
                    } else {
                        $pixelavoEventsLocalizedData[] = ['track' => 'track', 'event' => 'ViewContent', 'data' => $data, 'eventID' => $event_id];
                        pixelavo_run_conversions_api('ViewContent', $data, $event_id);
                    }
                }
            }
        }
    }

    /**
     * Remove From Cart Event
     * @return void
     */
    function remove_from_cart_data() {
        check_ajax_referer('pixelavo_ajax_event_nonce', 'ajax_nonce');

        if(pixelavo_pixel_status()) {
            $id = isset($_POST['download_id']) ? sanitize_text_field(wp_unslash($_POST['download_id'])) : '';
            $quantity = isset($_POST['download_quantity']) ? sanitize_text_field(wp_unslash($_POST['download_quantity'])) : '';
            $common_params = isset($_POST['common_params']) ? array_map('sanitize_text_field', (array)wp_unslash($_POST['common_params'])) : [];
            $download = edd_get_download($id);
            $event_id = 'removeFromCart.'.$id.'.'.time();
            if ( $download ) {
                $content_type = $download->has_variable_prices() ? 'product_group' : 'product';
                $data = [
                    'content_ids' => [$id],
                    'content_name' => $download->post_title,
                    'content_type' => $content_type,
                    'contents' => [
                        [
                            'id'=> $id,
                            'quantity'=> $quantity
                        ]
                    ],
                    'currency' => edd_get_option('currency', 'USD'),
                    'value' => round($download->get_price(), 2) // Use round() instead of number_format()
                ];
                
                $data = array_merge($data, apply_filters('pixelavo_additional_user_info', [], $data['content_ids'] ), $common_params);
                
                if(!empty($data['content_ids'])) {
                    pixelavo_run_conversions_api('RemoveFromCart', $data, $event_id);
                    wp_send_json_success( ["data" => $data, "event_id" => $event_id] );
                }
            }
            wp_die();
        }
    }
}

/**
 * Initialize PixelEddEventsData Class
 */
PixelEddEventsData::instance();
