<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Clean array data.
 * @param [array] $var
 * @return mixed
 */
function pixelavo_data_clean( $var ) {
    if ( is_array( $var ) ) {
        return array_map( 'pixelavo_data_clean', $var );
    } else {
        return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
    }
}

/**
 * Get Options Value.
 * @param [type] $key
 * @param [type] $section
 * @param mixed $default
 * @return mixed
 */
function pixelavo_get_option( $key, $section, $default = false ){
    $options = get_option( $section );
    if ( isset( $options[$key] ) ) {
        $value = $options[$key];
    }else{
        $value = $default;
    }
    return apply_filters( 'pixelavo_get_option_' . $key, $value, $key, $default );
}

/**
 * Get Option value Section wise.
 * @param array $registered_settings
 * @return void
 */
function pixelavo_get_options( $registered_settings = [] ) {
    if( ! is_array( $registered_settings ) ){
        return;
    }
    $settings = [];
    $options = [];
    foreach ( $registered_settings as $section_key => $setting_section ) {
        foreach ( $setting_section as $setting ) {
            $default                   = isset( $setting['std'] ) ? $setting['std'] : ( isset( $setting['default'] ) ? $setting['default'] : '' );
            $options[ $setting['id'] ] = pixelavo_get_option( $setting['id'], $section_key, $default );
        }
        $settings[$section_key] = $options;
        $options = [];
    }
    return apply_filters( 'pixelavo_get_settings', $settings );

}

/**
 * Get all page list.
 * @return array an `array` of all pages with `id` and `title`
 */
function pixelavo_page_list() {
    $page_list = [];
    // Use full post objects to avoid N+1 queries (get_the_title causes extra query per post)
    foreach(get_posts(['post_type' => 'page', 'posts_per_page' => -1, 'post_status' => 'publish']) as $post) {
        $page_list[] = ['id' => $post->ID, 'text' => 'Page - ' . $post->post_title];
    }
    $page_list[] = ['id' => 'all_posts', 'text' => 'All Post Page'];
    foreach(get_posts(['post_type' => 'post', 'posts_per_page' => -1, 'post_status' => 'publish']) as $post) {
        $page_list[] = ['id' => $post->ID, 'text' => 'Post - ' . $post->post_title];
    }
    if(pixelavo_is_woocommerce_active()) {
        $page_list[] = ['id' => 'all_products', 'text' => 'All Product Page'];
        foreach(get_posts(['post_type' => 'product', 'posts_per_page' => -1, 'post_status' => 'publish']) as $post) {
            $page_list[] = ['id' => $post->ID, 'text' => 'Product - ' . $post->post_title];
        }
    }
    if(pixelavo_is_edd_active()) {
        $page_list[] = ['id' => 'all_downloads', 'text' => 'All Download Page'];
        foreach(get_posts(['post_type' => 'download', 'posts_per_page' => -1, 'post_status' => 'publish']) as $post) {
            $page_list[] = ['id' => $post->ID, 'text' => 'Download - ' . $post->post_title];
        }
    }
    if(pixelavo_is_woocommerce_active()) {
        $page_list[] = ['id' => 'product_cat', 'text' => 'All Product Category Page'];
        $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
        if(is_array($terms) && !is_wp_error($terms)) {
            foreach($terms as $term) {
                $page_list[] = ['id' => 'term'.$term->term_id, 'text' => 'Category - ' . $term->name];
            }
        }
    }
    if(pixelavo_is_edd_active()) {
        $page_list[] = ['id' => 'download_category', 'text' => 'All Download Category Page'];
        $terms = get_terms(['taxonomy' => 'download_category', 'hide_empty' => false]);
        if(is_array($terms) && !is_wp_error($terms)) {
            foreach($terms as $term) {
                $page_list[] = ['id' => 'term'.$term->term_id, 'text' => 'Category - ' . $term->name];
            }
        }
    }
    $page_list[] = ['id' => 'category', 'text' => 'All Post Category Page'];
    $terms = get_terms(['taxonomy' => 'category', 'hide_empty' => false]);
    if(is_array($terms) && !is_wp_error($terms)) {
        foreach($terms as $term) {
            $page_list[] = ['id' => 'term'.$term->term_id, 'text' => 'Category - ' . $term->name];
        }
    }
    if(pixelavo_is_woocommerce_active()) {
        $page_list[] = ['id' => 'product_tag', 'text' => 'All Product Tag Page'];
        $terms = get_terms(['taxonomy' => 'product_tag', 'hide_empty' => false]);
        if(is_array($terms) && !is_wp_error($terms)) {
            foreach($terms as $term) {
                $page_list[] = ['id' => 'term'.$term->term_id, 'text' => 'Tag - ' . $term->name];
            }
        }
    }
    if(pixelavo_is_edd_active()) {
        $page_list[] = ['id' => 'download_tag', 'text' => 'All Download Tag Page'];
        $terms = get_terms(['taxonomy' => 'download_tag', 'hide_empty' => false]);
        if(is_array($terms) && !is_wp_error($terms)) {
            foreach($terms as $term) {
                $page_list[] = ['id' => 'term'.$term->term_id, 'text' => 'Tag - ' . $term->name];
            }
        }
    }
    $page_list[] = ['id' => 'post_tag', 'text' => 'All Post Tag Page'];
    $terms = get_terms(['taxonomy' => 'post_tag', 'hide_empty' => false]);
    if(is_array($terms) && !is_wp_error($terms)) {
        foreach($terms as $term) {
            $page_list[] = ['id' => 'term'.$term->term_id, 'text' => 'Tag - ' . $term->name];
        }
    }
    return $page_list;
}

/**
 * Get all editable roles.
 */
function pixelavo_editable_roles() {
    $roles = [];
    foreach (wp_roles()->get_names() as $key => $value) {
        $roles[] = [
            'id' => $key,
            'text' => $value
        ];
    }
    return $roles;
}

/**
 * Get all product categories.
 */
function pixelavo_get_product_categories() {
    $cats = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false
    ]);
    if(!is_array($cats)) {
        $cats = [];
    }
    $values = [];
    foreach ($cats as $cat) {
        $values[] = [
            'id' => $cat->term_id,
            'text' => $cat->name
        ];
    }
    return $values;
}
/**
 * Get all product tags.
 */
function pixelavo_get_product_tags() {
    $tags = get_terms([
        'taxonomy' => 'product_tag',
        'hide_empty' => false
    ]);
    if(!is_array($tags)) {
        $tags = [];
    }
    $values = [];
    foreach ($tags as $tag) {
        $values[] = [
            'id' => $tag->term_id,
            'text' => $tag->name
        ];
    }
    return $values;
}

/**
 * Get all product categories.
 */
function pixelavo_get_download_categories() {
    $cats = get_terms([
        'taxonomy' => 'download_category',
        'hide_empty' => false
    ]);
    if(!is_array($cats)) {
        $cats = [];
    }
    $values = [];
    foreach ($cats as $cat) {
        $values[] = [
            'id' => $cat->term_id,
            'text' => $cat->name
        ];
    }
    return $values;
}
/**
 * Get all product tags.
 */
function pixelavo_get_download_tags() {
    $tags = get_terms([
        'taxonomy' => 'download_tag',
        'hide_empty' => false
    ]);
    if(!is_array($tags)) {
        $tags = [];
    }
    $values = [];
    foreach ($tags as $tag) {
        $values[] = [
            'id' => $tag->term_id,
            'text' => $tag->name
        ];
    }
    return $values;
}

/**
 * Get all pixel list.
 * @param int|null $limit
 * @return array
 */
function pixelavo_get_pixel_list($limit) {
    $pixel_option = get_option('pixelavo_pixels_list', []);

    // Validate the option structure
    if (empty($pixel_option) || !is_array($pixel_option)) {
        return [];
    }

    // Check if pixel_lists key exists and is an array
    if (!isset($pixel_option['pixel_lists']) || !is_array($pixel_option['pixel_lists'])) {
        return [];
    }

    // Check if pixel_lists is empty
    if (empty($pixel_option['pixel_lists'])) {
        return [];
    }

    if($limit) {
        return pixelavo_data_clean(array_slice($pixel_option['pixel_lists'], 0, $limit));
    }
    return pixelavo_data_clean($pixel_option['pixel_lists']);
}

/**
 * Check if any pixel status is active or not..
 * @return boolean `true` if any pixel status is active else 'false`
 */
function pixelavo_pixel_status() {
    $pixel_list = pixelavo_get_pixel_list(apply_filters('pixelavo_pixels_limit', 1));
    foreach ($pixel_list as $pixel) {
        if($pixel['active'] == 'on' && pixelavo_check_pixel_page($pixel)) {
            return true;
        }
    }
    return false;
}

/**
 * Check pixel page list.
 * @param  array $pixel
 * @return bool Return `true` If allpages is selected or if specificpage is selected and current page `ID` exist in the specificpage array.
 */
function pixelavo_check_pixel_page ($pixel) {
    // Validate pixel_page key exists
    if (!isset($pixel['pixel_page'])) {
        return false;
    }

    $page_id = get_queried_object_id();

    // Don't fire on pages without valid ID when specific page mode is enabled
    if ($page_id === 0 && $pixel['pixel_page'] === 'specificpage') {
        return false;
    }

    if( function_exists('is_shop') && is_shop()) {
        $page_id = wc_get_page_id('shop');
    }

    if (
        $pixel['pixel_page'] == 'allpages' ||
        (
            $pixel['pixel_page'] == 'specificpage' &&
            !empty($pixel['specificpage_list']) &&
            (
                in_array($page_id, $pixel['specificpage_list']) ||
                in_array('term'.$page_id, $pixel['specificpage_list']) ||
                (in_array('all_posts', $pixel['specificpage_list']) && get_post_type($page_id) == 'post' ) ||
                (in_array('all_products', $pixel['specificpage_list']) && get_post_type($page_id) == 'product' ) ||
                (in_array('all_downloads', $pixel['specificpage_list']) && get_post_type($page_id) == 'download' ) ||
                (is_archive() && !empty(get_queried_object()->taxonomy) && in_array(get_queried_object()->taxonomy, $pixel['specificpage_list']) )
            )
        )
    ) {
        return true;
    }
    return false;
}

/**
 * Check if current user role is included in `Exclude Roles`
 * @return bool If current user role exist in `Exclude Roles` return `true` else `false`
 */
function pixelavo_check_exclude_roles() {
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;
    
    $settings = get_option('pixelavo_settings', []);
    $exclude_roles = array_key_exists('exclude_roles', $settings) ? $settings['exclude_roles'] : [];
    
    if(count(array_intersect($user_roles, $exclude_roles))) {
        return true;
    }
    return false;
}

/**
 * Check if a event exists and it's enable.
 * @param string $event Event name as a `string` in lowercase and word separate with `_`
 * @return bool Return `true` if exists and enable otherwise `false`
 */
function pixelavo_pixel_event_exists ($event) {
    $pixel_event = pixelavo_get_option($event, 'pixelavo_events');
    if($pixel_event && $pixel_event == 'on') {
        return true;
    }
    return false;
}

/**
 * Check if a Easy Digital Downloads event exists and it's enable.
 * @param string $event Event name as a `string` in lowercase and word separate with `_`
 * @return bool Return `true` if exists and enable otherwise `false`
 */
function pixelavo_edd_event_exists ($event) {
    $pixel_event = pixelavo_get_option($event, 'pixelavo_edd_events');
    if($pixel_event && $pixel_event == 'on') {
        return true;
    }
    return false;
}

/**
 * Check if a custom event exists
 * @param string $event Event name as a `string` in lowercase
 * @return bool Return `true` if exists and enable otherwise `false`
 */
function pixelavo_custom_event_exists ($target) {
    $events = pixelavo_get_option('custom_events_lists', 'pixelavo_custom_events');
    foreach ($events as $event) {
        if(is_array($event) && in_array($target, $event)) {
            return true;
        }
    }
    return false;
}

/**
 * Get all Custom Events
 * @return array
 */
function pixelavo_get_custom_events () {
    $custom_events = get_option('pixelavo_custom_events', []);
    if(empty($custom_events)) {
        return $custom_events;
    }
    return pixelavo_data_clean($custom_events['custom_events_lists']);
}

/**
 * Prepare Custom Event Data
 * @param array $params
 * @return array
 */
function pixelavo_prepare_custom_event_data ($params) {
    $data = [];
    if(!empty($params)) {
        foreach ($params as $item) {
            if(!empty($item)) {
                $data[$item['name']] = $item['value'];
            }
        }
    }
    return $data;
}

/**
 * Check if Other/Extra Event Exists
 * @param string $event Event name as a `string` in lowercase
 * @return bool Return `true` if exists and enable otherwise `false`
 */
function pixelavo_other_event_exists ($event) {
    $pixel_event = pixelavo_get_option($event, 'pixelavo_other_events');
    if($pixel_event && $pixel_event === 'on') {
        return true;
    }
    return false;
}
/**
 * Get all Other/Extra Events
 * @return array
 */
function pixelavo_get_other_events () {
    $other_events = get_option('pixelavo_other_events', []);
    if(empty($other_events)) {
        return $other_events;
    }
    return pixelavo_data_clean($other_events);
}
/**
 * Get Event Common data
 * @return array
 */
function pixelavo_get_event_common_data() {
    global $wp, $post;
    $user = wp_get_current_user();
    $post_type = get_post_type();
    $data = [
        'event_url' => home_url(add_query_arg([], $wp->request)),
    ];
    if($post_type) {
        $data['post_type'] = $post_type;
    }
    if(is_singular( 'post' )) {
        $data['page_title'] = $post->post_title;
        $data['post_id']    = $post->ID;
    } elseif( is_singular( 'page' ) || is_home()) {
        $data['post_type']  = 'page';
        if(!is_home()) {
            $data['post_id'] = $post->ID;  // Only add post_id if not home page to avoid null
        }
        $data['page_title'] = is_home() == true ? get_bloginfo( 'name' ) : $post->post_title;
    } elseif (function_exists('is_shop') && is_shop()) {
        $page_id = (int) wc_get_page_id( 'shop' );
        $data['post_type']  = 'page';
        $data['post_id']    = $page_id;
        $data['page_title'] = get_the_title( $page_id );
    } elseif ( is_category() ) {
        $cat  = get_query_var( 'cat' );
        $term = get_category( $cat );
        $data['post_type']  = 'category';
        $data['post_id']    = $cat;
        $data['page_title'] = $term->name;
    } elseif ( is_tag() ) {
        $slug = get_query_var( 'tag' );
        $term = get_term_by( 'slug', $slug, 'post_tag' );
        $data['post_type'] = 'tag';
        if($term) {
            $data['post_id']    = $term->term_id;
            $data['page_title'] = $term->name;
        }
    } elseif (is_tax()) {
        $term = get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ) );
        $data['post_type'] = get_query_var( 'taxonomy' );
        if ( $term ) {
            $data['post_id']    = $term->term_id;
            $data['page_title'] = $term->name;
        }
    } elseif(is_archive()){
        $data['page_title'] = get_the_archive_title();
        $data['post_type']  = 'archive';
    } elseif ($post_type == 'product' || $post_type == 'download' ) {
        $data['page_title'] = $post->post_title;
        $data['post_id']    = $post->ID;
    } elseif(is_search()) {
        $data['page_title'] = 'Search';
    } elseif(is_404()) {
        $data['page_title'] = 'Page Not Found';
    } else {
        if(!empty($post)) {
            $data['page_title'] = $post->post_title;
            $data['post_id']    = $post->ID;
        }
    }
    $data['plugin'] = 'Pixelavo';
    $data['user_role'] = implode( ',', $user->roles );
    return array_filter($data);
}

/**
 * Check if woocommerce is installed
 */
function pixelavo_is_woocommerce_install () {
    $installed_plugins = get_plugins();
    return isset($installed_plugins['woocommerce/woocommerce.php']);
}

/**
 * Check if woocommerce is active
 */
function pixelavo_is_woocommerce_active () {
    return is_plugin_active( 'woocommerce/woocommerce.php');
}

/**
 * Check if woocommerce subscription is active
 */
function pixelavo_is_woocommerce_subscription_active () {
    return is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php');
}

/**
 * Check if Easy Digital Downloads is active
 */
function pixelavo_is_edd_active () {
    return is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php');
}

/**
 * Check if EDD subscription is active
 */
function pixelavo_is_edd_subscription_active () {
    return is_plugin_active( 'edd-recurring/edd-recurring.php');
}

/** Check if Fluent form is active */
function pixelavo_is_fluentform_active () {
    return is_plugin_active( 'fluentform/fluentform.php');
}

/** Check if Contact form 7 is active */
function pixelavo_is_cf7_active () {
    return is_plugin_active( 'contact-form-7/wp-contact-form-7.php');
}

/** Check if WP forms is active */
function pixelavo_is_wpforms_active () {
    return is_plugin_active( 'wpforms-lite/wpforms.php');
}

/**
 * Get client IP address with improved validation and proxy handling.
 * @return string|false Returns IP address or false if invalid
 */
function pixelavo_get_client_ip() {
    // List of IP headers to check in order of preference
    $ip_headers = [
        'HTTP_CF_CONNECTING_IP', // Cloudflare
        'HTTP_X_REAL_IP',       // Nginx proxy
        'HTTP_CLIENT_IP',       // Client IP
        'HTTP_X_FORWARDED_FOR', // Forward for
        'HTTP_X_FORWARDED',     // Forwarded
        'HTTP_FORWARDED_FOR',   // Forwarded for
        'HTTP_FORWARDED',       // Forwarded
        'REMOTE_ADDR'           // Default
    ];

    foreach ($ip_headers as $header) {
        if (!isset($_SERVER[$header])) {
            continue;
        }

        // Handle comma-separated IPs (common in proxy chains)
        $ips = array_map('trim', explode(',', sanitize_text_field(wp_unslash($_SERVER[$header]))));
        
        // Get the first IP in the chain
        $ip = reset($ips);
        
        // Clean and validate the IP
        $ip = sanitize_text_field(wp_unslash($ip));
        
        // Validate as either IPv4 or IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $ip;
        }
    }

    return false;
}

/**
 * Pixel browser event.
 * Inject pixel browser event script into website to run using `wp_footer` hook.
 * @param  string $event_name
 * @param  array $data
 * @param  string $eventID
 * @param  bool $customEvent
 * @return void
 */
function pixelavo_run_browser_event($event_name, $data, $event_id) {
    echo "<script>fbq('track', '".esc_js($event_name)."', ".wp_json_encode($data).", {eventID: '".esc_js($event_id)."'});</script>";
}

/**
 * Pixel conversions api.
 * Check if conversion api is exist and active. 
 * Prepare all necessary data and send request to facebook pixel for server event.
 * @param  string $eventName
 * @param  array $data
 * @param  string $eventID
 * @return void
 */
function pixelavo_run_conversions_api($eventName, $data, $eventID = '', $order = null) {
    global $wp;

    $current_url = home_url(add_query_arg(array(), $wp->request));
    $pixels = pixelavo_get_pixel_list(apply_filters('pixelavo_pixels_limit', 1));

    // Validate pixels is an array
    if (!is_array($pixels) || empty($pixels)) {
        return; // No pixels configured or invalid data
    }

    $client_ip = pixelavo_get_client_ip();
    $client_user_agent = !empty($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';

    $advanced_matching_data = [];
    if(function_exists('pixelavo_get_advance_matching_data')) {
        $advanced_matching_data = pixelavo_get_advance_matching_data($order);
    }
    $advanced_matching_hashed = !empty($advanced_matching_data['hashed']) ? $advanced_matching_data['hashed'] : [];
    $user_data = array_merge($advanced_matching_hashed, [
        "client_ip_address" => $client_ip,
        "client_user_agent" => $client_user_agent
    ]);

    // Clean up user meta for specific events
    if($eventName === 'CompleteRegistration') {
        delete_user_meta(get_current_user_id(), 'pixelavo_user_signup');
        delete_user_meta(get_current_user_id(), 'pixelavo_user_login');
    } else if($eventName === 'Login') {
        delete_user_meta(get_current_user_id(), 'pixelavo_user_login');
    }

    foreach ($pixels as $pixel) {
        // Validate pixel has required fields
        if (!isset($pixel['pixel_id'])) {
            continue;
        }

        // Check conversion API is enabled
        if (!array_key_exists('conversion_api_status', $pixel)) {
            continue;
        }

        if ($pixel['conversion_api_status'] != 'on') {
            // This is expected, don't log (pixel has CAPI disabled)
            continue;
        }

        // Validate access token
        if (!array_key_exists('access_token', $pixel)) {
            continue;
        }

        $token = trim($pixel['access_token']);
        if (empty($token)) {
            continue;
        }

        // Facebook access tokens are typically 100+ characters
        if (strlen($token) < 50) {
            continue;
        }

        // Check page validation
        if (!pixelavo_check_pixel_page($pixel)) {
            // Only log if we're debugging (can be noisy)
            // Uncomment for debugging: error_log("Pixelavo CAPI: Skipping {$eventName} for pixel {$pixel['pixel_id']} - page validation failed");
            continue;
        }

        // All validations passed, send event
        // Filter out empty values from custom_data to improve match quality
        $custom_data_filtered = array_filter($data, function($value) {
                if ($value === null ||
                    $value === false ||
                    $value === '' ||
                    $value === 'null' ||
                    $value === 'undefined' ||
                    (is_array($value) && empty($value))
                ) {
                    return false;
                }
                return true;
            });

            $url = "https://graph.facebook.com/v21.0/{$pixel['pixel_id']}/events?access_token={$token}";
            $request_body = [
                "data" => [
                    [
                        "event_name" => $eventName,
                        "event_time" => time(),
                        "action_source" => "website",
                        "event_source_url" => $current_url,
                        "event_id" => $eventID,
                        "user_data" => $user_data,
                        "custom_data" => $custom_data_filtered,
                    ]
                ]
            ];

            // Only include test_event_code if it's actually set (for testing mode)
            $is_test_mode = isset($pixel['event_code']) && !empty($pixel['event_code']);
            if ($is_test_mode) {
                $request_body["test_event_code"] = $pixel['event_code'];
            }

            // Use blocking requests in test/debug mode for error validation
            // Non-blocking in production for better performance
            $is_debug = defined('WP_DEBUG') && WP_DEBUG;
            $use_blocking = $is_test_mode || $is_debug;

            $response = wp_remote_post($url, [
                'method' => 'POST',
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => wp_json_encode($request_body),
                'timeout' => 5,
                'blocking' => $use_blocking, // Block only in test/debug mode
            ]);
        // Note: Non-blocking requests cannot be validated. Check Facebook Events Manager for delivery confirmation.
    }
}



// Function to get Fluent Forms list
function pixelavo_get_fluent_forms_list() {
    if (!is_plugin_active('fluentform/fluentform.php')) {
        return [];
    }

    // Limit to 500 forms to prevent performance issues on large sites
    $forms = wpFluent()->table('fluentform_forms')
        ->select('id', 'title')
        ->orderBy('id', 'desc')
        ->limit(500)
        ->get();

    $options = [
        [
            'id' => 'all_forms',
            'text' => 'All Forms'
        ]
    ];

    if (!empty($forms) && is_array($forms)) {
        foreach ($forms as $form) {
            $options[] = [
                'id' => $form->id,
                'text' => $form->title
            ];
        }
    }

    return $options;
}

// Function to get Contact Form 7 list
function pixelavo_get_cf7_forms_list() {
    if (!is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
        return [];
    }
    
    $forms = get_posts([
        'post_type' => 'wpcf7_contact_form',
        'posts_per_page' => -1
    ]);
    
    $options = [
        [
            'id' => 'all_forms',
            'text' => 'All Forms'
        ]
    ];
    foreach ($forms as $form) {
        $options[] = [
            'id' => $form->ID,
            'text' => $form->post_title
        ];
    }
    
    return $options;
}

// Function to get WPForms list
function pixelavo_get_wpforms_list() {
    if (!is_plugin_active('wpforms-lite/wpforms.php')) {
        return [];
    }

    $forms = get_posts([
        'post_type' => 'wpforms',
        'posts_per_page' => -1
    ]);

    $options = [
        [
            'id' => 'all_forms',
            'text' => 'All Forms'
        ]
    ];
    foreach ($forms as $form) {
        $options[] = [
            'id' => $form->ID,
            'text' => $form->post_title
        ];
    }

    return $options;
}

// Check if HT Contact Form is active
function pixelavo_is_ht_contactform_active() {
    return is_plugin_active('ht-contactform/contact-form-widget-elementor.php');
}

// Function to get HT Contact Form list
function pixelavo_get_ht_contactform_list() {
    if (!is_plugin_active('ht-contactform/contact-form-widget-elementor.php')) {
        return [];
    }

    $forms = get_posts([
        'post_type' => 'ht_form',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ]);

    $options = [
        ['id' => 'all_forms', 'text' => 'All Forms']
    ];
    foreach ($forms as $form) {
        $options[] = [
            'id' => $form->ID,
            'text' => $form->post_title
        ];
    }
    return $options;
}