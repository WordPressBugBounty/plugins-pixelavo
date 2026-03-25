<?php

namespace Pixelavo;

/**
 * Exit if accessed directly
 * */
if (!defined('ABSPATH')) {
    exit;
}

class AddPixel{
    
    private static $_instance = null;

    /**
     * Instance
     * 
     * Initializes a singleton instance
     * 
     * @return self self class
     */
    static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct() {
        add_action('wp_head', [ $this, 'add_pixels']);
    }
    
    /**
     * Add pixel to the site.
     *
     * @return void
     */
    function add_pixels() {
        $pixels = pixelavo_get_pixel_list(apply_filters('pixelavo_pixels_limit', 1));

        $pixelInitCode = '';
        $pixelNoScriptCode = '';
        $advanced_matching_data = [];
        if(function_exists('pixelavo_get_advance_matching_data')) {
            $advanced_matching_data = pixelavo_get_advance_matching_data();
        }
        $advanced_matching = !empty($advanced_matching_data['raw']) ? $advanced_matching_data['raw'] : [];
        $advanced_matching_hashed = !empty($advanced_matching_data['hashed']) ? $advanced_matching_data['hashed'] : [];

        if(!empty($pixels)) {
            
            /** 
             * Print facebook pixel code to the browser.
             */
            if(pixelavo_pixel_status() && !pixelavo_check_exclude_roles()) { ?>
                <script>
                    !function(f,b,e,v,n,t,s)
                    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
                    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
                    n.queue=[];t=b.createElement(e);t.async=!0;
                    t.src=v;s=b.getElementsByTagName(e)[0];
                    s.parentNode.insertBefore(t,s)}(window, document,'script',
                    'https://connect.facebook.net/en_US/fbevents.js');
                    <?php
                        foreach ($pixels as $pixel) {
                            if($pixel['active'] == 'on' && pixelavo_check_pixel_page($pixel)) {
                                if(!empty($advanced_matching)) {
                                    echo "fbq('init', ".esc_attr($pixel['pixel_id']).", ".wp_json_encode($advanced_matching).");" . "\n";
                                } else {
                                    echo "fbq('init', ".esc_attr($pixel['pixel_id']).");" . "\n";
                                }
                            }
                        }
                    ?>
                    fbq('track', 'PageView', <?php echo wp_json_encode(pixelavo_get_event_common_data()); ?>);
                </script>
                <noscript>
                    <?php
                        foreach ($pixels as $pixel) {
                            if($pixel['active'] == 'on' && pixelavo_check_pixel_page($pixel)) {
                                if(!empty($advanced_matching)) {
                                    // Build query string with only available advanced matching fields
                                    $noscript_params = ['id' => $pixel['pixel_id'], 'ev' => 'PageView', 'noscript' => 1];
                                    $ud_fields = ['em', 'fn', 'ln', 'ph', 'ct', 'st', 'zp', 'country'];
                                    foreach ($ud_fields as $field) {
                                        if (isset($advanced_matching_hashed[$field]) && !empty($advanced_matching_hashed[$field])) {
                                            $noscript_params["ud[{$field}]"] = $advanced_matching_hashed[$field];
                                        }
                                    }
                                    $query_string = http_build_query($noscript_params);
                                    // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
                                    echo "<img height='1' width='1' style='display:none' src='https://www.facebook.com/tr?" . esc_url($query_string) . "'/>" . "\n";
                                } else {
                                    // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
                                    echo "<img height='1' width='1' style='display:none' src='https://www.facebook.com/tr?id=".esc_attr($pixel['pixel_id'])."&ev=PageView&noscript=1'/>" . "\n";
                                }
                            }
                        }
                    ?>
                </noscript>
                <?php
            }

        }
    }
}

/**
 * Initialize AddPixel Class
 */
AddPixel::instance();
