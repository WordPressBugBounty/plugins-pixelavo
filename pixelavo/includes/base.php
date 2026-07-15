<?php

namespace Pixelavo;

/**
 *  TODO: remove data from other events in the future update (in wp_localize_script)
 */

/**
 * Exit if accessed directly
 * */
if (!defined('ABSPATH')) {
    exit;
}

/**
* Base
*/
final class Base {

    private static $_instance = null;

    /**
     * Instance
     * 
     * Initializes a singleton instance
     * 
     * @return self class
     */
    static function instance() {
        if (is_null( self::$_instance )) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Base class constructor
     * @return void
     */
    private function __construct() {
        if (!function_exists('is_plugin_active')) {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }
        add_action('plugins_loaded', [$this, 'init']);
        add_action('in_admin_header', [$this, 'remove_admin_notice'], 1000);
        add_action('wp_enqueue_scripts', [$this, 'scripts']);
        add_action('admin_init', [$this, 'show_diagnostic_notice']);
        add_action('admin_init', [$this, 'show_rating_notice']);
        add_action('admin_init', [$this, 'show_promo_notice']);
        add_action('admin_init', [$this, 'ensure_ai_tables_exist']);
        add_action('admin_init', [$this, 'init_security_notices']);
        // add_action('admin_head', [$this, 'halloween_notice']); // leave for another offer banner 
        /**
         * Register Plugin Active Hook
         */
        register_activation_hook( PIXELAVO_PL_ROOT, [ $this, 'plugin_activate_hook' ] );

        // Add user login & register hook
        add_action('wp_login', [$this,'user_login'], 10, 2);
        add_action('user_register', [$this,'user_register'], 10, 2);
    }
        
    /**
     * Remove all Notices on admin pages.
     * @return void
     */
    function remove_admin_notice(){
        $current_screen = get_current_screen();
        $hide_screen = ['toplevel_page_pixelavo', 'update'];
        if(  in_array( $current_screen->id, $hide_screen) ){
            remove_all_actions('admin_notices');
            remove_all_actions('all_admin_notices');
        }
    }

    /**
     * init
     *
     * Plugins loaded init hook
     * @return void
     */
    function init() {

        /**
         * Include required files.
         */
        $this->include_files();

        /**
         * Redirect to option page after plugin activate
         */
        $this->plugin_redirect_option_page();

        /**
         * Add plugin setting link to the plugin.
         */
        add_filter('plugin_action_links_' . PIXELAVO_PLUGIN_BASE, [$this, 'plugins_setting_links']);

    }

    /**
     * Include Require Files
     */
    function include_files() {
        require_once (PIXELAVO_PL_PATH . 'includes/helper-functions.php');
        require_once (PIXELAVO_PL_PATH . 'admin/admin-setting.php');
        require_once (PIXELAVO_PL_PATH . 'includes/add-pixel.php');
        require_once (PIXELAVO_PL_PATH . 'includes/modifier.php');
        if(pixelavo_is_woocommerce_active()) {
            require_once (PIXELAVO_PL_PATH . 'includes/pixel-events-data.php');
            if(pixelavo_get_option('product_feed', 'pixelavo_settings') == 'on') {
                require_once (PIXELAVO_PL_PATH . 'includes/pixel-feed.php');
            }
        }
        if(pixelavo_is_edd_active()) {
            require_once (PIXELAVO_PL_PATH . 'includes/pixel-edd-events-data.php');
            if(pixelavo_get_option('edd_product_feed', 'pixelavo_settings') == 'on') {
                require_once (PIXELAVO_PL_PATH . 'includes/pixel-edd-feed.php');
            }
        }
        if(!empty(pixelavo_get_custom_events())) {
            require_once (PIXELAVO_PL_PATH . 'includes/pixel-custom-events-data.php');
        }
        require_once PIXELAVO_PL_PATH . 'includes/pixel-ajax-events-data.php';

        if( is_admin() ){
            require_once (PIXELAVO_PL_PATH . 'admin/class-notice-manager.php');
            require_once (PIXELAVO_PL_PATH . 'admin/class-diagnostic-data.php');
            require_once (PIXELAVO_PL_PATH . 'admin/class-notices.php');
        }
        add_action('wp_footer', [$this, 'localizedEventsData'], 11);
    }

    function localizedEventsData() {
        global $pixelavoEventsLocalizedData;
        if(count($pixelavoEventsLocalizedData) > 0) {
            wp_localize_script('pixelavo', 'pixelavo_event', $pixelavoEventsLocalizedData);
        }
    }

    /**
     * Scripts
     * 
     * Enqueue style and scripts for frontend part
     * @return void
     */
    function scripts() {
        if(pixelavo_pixel_status()) {
            wp_enqueue_script('pixelavo', PIXELAVO_DIR_URL . 'assets/public/js/main.js', ['jquery'], PIXELAVO_VERSION, true);
            wp_localize_script('pixelavo', 'pixelavo', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('pixelavo_ajax_event_nonce'),
                'other_events' => wp_json_encode(array_merge(pixelavo_get_other_events(), ['data' => pixelavo_get_event_common_data()])),
                'custom_events' => wp_json_encode(pixelavo_get_custom_events()),
                'additional_user_info' => apply_filters('pixelavo_additional_user_info', [], [] ),
                'common_params' => pixelavo_get_event_common_data(),
                'other_event_options' => [
                    "download_files" => pixelavo_get_option('download_files', 'pixelavo_download_files'),
                    "page_scroll" => pixelavo_get_option('page_scroll', 'pixelavo_page_scroll'),
                    "time_on_page" => pixelavo_get_option('time_on_page', 'pixelavo_time_on_page'),
                    "form" => pixelavo_get_option('form_submission', 'pixelavo_form_submission'),
                ],
                'is_active' => [
                    "woo" => pixelavo_is_woocommerce_active(),
                    'edd' => pixelavo_is_edd_active(),
                    'fluent_form' => pixelavo_is_fluentform_active(),
                    'cf7' => pixelavo_is_cf7_active(),
                    'wpforms' => pixelavo_is_wpforms_active(),
                    'ht_contactform' => pixelavo_is_ht_contactform_active(),
                ],
            ]);
            wp_localize_script('pixelavo', 'pixelavo_event', []);
        }
    }

    /**
    * Plugins setting links

    * @param  array plugin default action links
    * @return array plugin action link
    */
    function plugins_setting_links($links) {
        $link = sprintf(
            '<a href="%1$s">%2$s</a>',
            admin_url('admin.php?page=pixelavo#/pixels'),
            __( 'Settings', 'pixelavo' )
        );
        array_unshift($links, $link);
        return $links; 
    }

    /**
     * Plugin Active Hook
     * Run when plugin is activated
     */
    public function plugin_activate_hook() {
        // Create database tables for AI features
        $this->create_ai_tables();

        $events = get_option('pixelavo_events', [
            'search' => 'on',
            'view_content' => 'on',
            'view_category' => 'on',
        ]);
        $edd_events = get_option('pixelavo_edd_events', [
            'edd_search' => 'on',
            'edd_view_content' => 'on',
            'edd_view_category' => 'on',
        ]);
        $other_events = get_option('pixelavo_edd_events', [
            'youtube_video' => 'off',
            'vimeo_video' => 'off',
            'self_hosted_video' => 'off',
            'page_scroll' => 'on',
            'time_on_page' => 'on',
            'download_files' => 'on',
            'form_submission' => 'on',
            'internal_links' => 'off',
            'external_links' => 'off',
        ]);
        $download_files = get_option('pixelavo_download_files', [
            'download_files' => [
                "download_files_extensions" => ["pdf", "zip"]
            ]
        ]);
        $time_on_page = get_option('pixelavo_time_on_page', [
            'time_on_page' => [
                "time_on_page_value" => "10"
            ]
        ]);
        $page_scroll = get_option('pixelavo_page_scroll', [
            'page_scroll' => [
                "page_scroll_value" => "30"
            ]
        ]);
        $form_submission = get_option('pixelavo_form_submission', [
            'form_submission' => [
                "fluent_form" => [],
                "contact_form_7" => [],
                "wp_forms" => [],
                "ht_contactform" => [],
            ]
        ]);
        update_option('pixelavo_events', $events);
        update_option('pixelavo_edd_events', $edd_events);
        update_option('pixelavo_other_events', $other_events);
        update_option('pixelavo_download_files', $download_files);
        update_option('pixelavo_time_on_page', $time_on_page);
        update_option('pixelavo_page_scroll', $page_scroll);
        update_option('pixelavo_form_submission', $form_submission);

        /* ViewContent Event Settings */
        $pixelavo_wc_view_content = get_option('pixelavo_wc_view_content', [
            'view_content' => [
                'view_content_delay' => 0,
            ],
        ]);
        update_option('pixelavo_wc_view_content', $pixelavo_wc_view_content);
        /* Purchase Event Settings */
        $pixelavo_wc_purchase = get_option('pixelavo_wc_purchase', [
            'purchase' => [
                'purchase_event_trigger' => ['on-hold', 'processing'],
                'purchase_additional_info' => 'off',
            ],
        ]);
        update_option('pixelavo_wc_purchase', $pixelavo_wc_purchase);

        add_option('pixelavo_do_activation_redirect', true);
        flush_rewrite_rules();

        if ( ! get_option( 'pixelavo_installed' ) ) {
            update_option( 'pixelavo_installed', time() );
        }
    }

    /**
     * Create AI database tables
     * @return void
     */
    private function create_ai_tables() {
        // Load DatabaseInstaller class if it exists
        $installer_class = PIXELAVO_PL_PATH . 'admin/settings-panel/includes/classes/Api/Ai/DatabaseInstaller.php';
        if (file_exists($installer_class)) {
            require_once $installer_class;
            \Pixelavo\Api\Ai\DatabaseInstaller::create_tables();
        }
    }

    /**
     * Ensure AI tables exist on admin init
     * This handles cases where activation hook might have been missed
     * @return void
     */
    public function ensure_ai_tables_exist() {
        // Only check once per admin session to avoid performance impact
        if (get_transient('pixelavo_ai_tables_checked')) {
            return;
        }

        $installer_class = PIXELAVO_PL_PATH . 'admin/settings-panel/includes/classes/Api/Ai/DatabaseInstaller.php';
        if (file_exists($installer_class)) {
            require_once $installer_class;

            // Check if table exists, create if not
            if (!\Pixelavo\Api\Ai\DatabaseInstaller::table_exists()) {
                \Pixelavo\Api\Ai\DatabaseInstaller::create_tables();
            }
        }

        // Set transient to avoid repeated checks (expires in 12 hours)
        set_transient('pixelavo_ai_tables_checked', true, 12 * HOUR_IN_SECONDS);
    }

    /**
     * Initialize security notices for AI features
     * @return void
     */
    public function init_security_notices() {
        $notices_class = PIXELAVO_PL_PATH . 'admin/settings-panel/includes/classes/Api/Ai/SecurityNotices.php';
        if (file_exists($notices_class)) {
            require_once $notices_class;
            \Pixelavo\Api\Ai\SecurityNotices::init();

            // Enqueue JavaScript for notice dismissal
            add_action('admin_footer', [\Pixelavo\Api\Ai\SecurityNotices::class, 'enqueue_notice_scripts']);
        }
    }

    public function halloween_notice() {
        $plugins = get_plugins();
        if(!empty($plugins['pixelavo-pro/pixelavo-pro.php'])) {
            return;
        }
        $url = 'https://pixelavo.com/pricing/?utm_source=halloween&utm_medium=dashboard#pixelavo-pricing';
        $image = PIXELAVO_PL_URL . '/admin/settings-panel/assets/images/banner/halloween-2025.png';
        \Pixelavo_Notice::set_notice(
            [
                'id'          => 'pixelavo-halloween-notice',
                'type'        => 'notice',
                'dismissible' => true,
                'message_type' => 'banner',
                'banner' => [
                    'url' => esc_url_raw($url),
                    'image' => '<img src="'.esc_url_raw($image).'" alt="Pixelavo" style="max-width:100%"/>'
                ],
                'close_by'    => 'transient'
            ]
        );
    }

    /**
     * User Login
     * @param string $user_login
     * @param \WP_User $user
     * @return void
     */
    public function user_login($user_login, $user) {
        if(pixelavo_other_event_exists('login')) {
            // Don't double-fire Login when this is part of a fresh registration
            // (CompleteRegistration already fired in the same request).
            if($this->auth_event_queued('CompleteRegistration')) {
                return;
            }
            $this->fire_server_auth_event('Login', 'trackCustom', $user);
        }
    }

    /**
     * User Register
     * @param int $user_id
     * @param \WP_User $userdata
     * @return void
     */
    public function user_register($user_id, $userdata) {
        if(pixelavo_other_event_exists('signup')) {
            $this->fire_server_auth_event('CompleteRegistration', 'track', get_userdata($user_id));
        }
    }

    /**
     * Fire an identity event (Login / CompleteRegistration) server-side.
     *
     * The Conversions API call is made HERE, from the authenticated wp_login /
     * user_register hook — this is the server-side source of truth and cannot be
     * forged by a client. A short-lived cookie then carries ONLY the event name
     * + the shared event_id to the browser, which fires the fbq pixel for
     * deduplication. The cookie is genuinely non-authoritative: forging it can
     * at most make the visitor's own browser fire an fbq pixel — it never
     * triggers a CAPI call and carries no credentials.
     *
     * @param string        $event_name
     * @param string        $track 'track' or 'trackCustom'
     * @param \WP_User|false $user  The authenticated user (for matching data).
     * @return void
     */
    private function fire_server_auth_event($event_name, $track, $user = null) {
        $event_id = $event_name . '.' . time() . '.' . wp_generate_password(12, false, false);

        // Make the just-authenticated user available for advanced matching —
        // wp_login / user_register run before WordPress sets the current user.
        if($user instanceof \WP_User) {
            wp_set_current_user($user->ID);
        }

        // Fire the Conversions API now. Identity events are not page-scoped, so
        // skip page-targeting and use the site home as the source URL (there is
        // no queried page in this hook). The request is non-blocking, so login
        // and registration are not slowed.
        //
        // Do NOT call pixelavo_get_event_common_data() here — it runs query
        // conditionals (is_singular/is_home/...) which trigger a "called
        // incorrectly" notice this early (before the main query). A minimal,
        // page-independent payload is correct for an identity event anyway.
        $common = ['event_url' => home_url('/')];
        pixelavo_run_conversions_api($event_name, $common, $event_id, null, [
            'event_source_url' => home_url('/'),
            'skip_page_check'  => true,
        ]);

        // Hand only a browser descriptor to the client for the fbq pixel.
        $this->queue_browser_auth_event($event_name, $track, $event_id);
    }

    /**
     * Store a browser-only descriptor (event name + shared event_id) in the
     * short-lived cookie so the pixel fires fbq once for deduplication. No PII,
     * no credentials, not a trust boundary.
     *
     * @param string $event_name
     * @param string $track
     * @param string $event_id
     * @return void
     */
    private function queue_browser_auth_event($event_name, $track, $event_id) {
        $queue = [];
        if(!empty($_COOKIE['pixelavo_srv_evt'])) {
            $existing = json_decode(wp_unslash($_COOKIE['pixelavo_srv_evt']), true);
            if(is_array($existing)) {
                $queue = $existing;
            }
        }

        $queue[] = [
            'name'  => $event_name,
            'track' => ($track === 'track') ? 'track' : 'trackCustom',
            'id'    => $event_id,
        ];

        $this->set_server_auth_cookie(wp_json_encode($queue));
    }

    /**
     * Whether an event of the given name is already in the pending cookie queue.
     * @param string $name
     * @return bool
     */
    private function auth_event_queued($name) {
        if(empty($_COOKIE['pixelavo_srv_evt'])) {
            return false;
        }
        $queue = json_decode(wp_unslash($_COOKIE['pixelavo_srv_evt']), true);
        if(!is_array($queue)) {
            return false;
        }
        foreach($queue as $event) {
            if(!empty($event['name']) && $event['name'] === $name) {
                return true;
            }
        }
        return false;
    }

    /**
     * Set the short-lived carrier cookie for pending auth events.
     * httponly is intentionally false: the browser JS must read it. It holds no
     * secret — only event name + server-generated id.
     * @param string $value
     * @return void
     */
    private function set_server_auth_cookie($value) {
        // Reflect immediately so same-request reads (register -> login) see it.
        $_COOKIE['pixelavo_srv_evt'] = $value;
        if(headers_sent()) {
            return;
        }
        setcookie('pixelavo_srv_evt', $value, [
            'expires'  => time() + 300,
            'path'     => defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/',
            'domain'   => defined('COOKIE_DOMAIN') && COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
            'secure'   => is_ssl(),
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * After Active the plugin then redirect to option page
     * @return void
     */
    public function plugin_redirect_option_page() {
        if ( get_option( 'pixelavo_do_activation_redirect', false ) ) {
            delete_option('pixelavo_do_activation_redirect');
            if( !isset( $_GET['activate-multi'] ) ){ // phpcs:ignore WordPress.Security.NonceVerification
                wp_safe_redirect( admin_url("admin.php?page=pixelavo#/pixels") );
                exit;
            }
        }
    }

    
    function show_diagnostic_notice() {
        $notice_instance = \Pixelavo_Diagnostic_Data::get_instance();
        if ( ! $notice_instance->should_show_notice() ) {
            return;
        }
        ob_start();
        $notice_instance->show_notices();
        $message = ob_get_clean();
        if (! empty( $message ) ) {
            \Pixelavo_Notice::set_notice(
                [
                    'id'          => 'diagnostic-data',
                    'type'        => 'success',
                    'dismissible' => false,
                    'message_type' => 'html',
                    'message'     => $message,
                    'display_after'  => ( 7 * DAY_IN_SECONDS ),
                    'expire_time' => ( 0 * DAY_IN_SECONDS ),
                    'close_by'    => 'transient'
                ]
            );
        }
    }
    
    function show_rating_notice() {
        $message = '<div class="pixelavo-review-notice-wrap">
            <div class="pixelavo-rating-notice-logo">
                <img src="' . esc_url(PIXELAVO_PL_URL . 'assets/images/logo.png') . '" alt="'.esc_attr__('Pixelavo', 'pixelavo'). '" style="max-width:110px"/>
            </div>
            <div class="pixelavo-review-notice-content">
                <h3>'.esc_html__('Thank you for choosing Pixelavo to manage you plugins!','pixelavo').'</h3>
                <p>'.esc_html__('Would you mind doing us a huge favor by providing your feedback on WordPress? Your support helps us spread the word and greatly boosts our motivation.','pixelavo').'</p>
                <div class="pixelavo-review-notice-action">
                    <a href="https://wordpress.org/support/plugin/pixelavo/reviews/" class="pixelavo-review-notice button-primary" target="_blank">'.esc_html__('Ok, you deserve it!','pixelavo').'</a>
                    <a href="#" class="pixelavo-notice-close pixelavo-review-notice"><span class="dashicons dashicons-calendar"></span>'.esc_html__('Maybe Later','pixelavo').'</a>
                    <a href="#" data-already-did="yes" class="pixelavo-notice-close pixelavo-review-notice"><span class="dashicons dashicons-smiley"></span>'.esc_html__('I already did','pixelavo').'</a>
                </div>
            </div>
        </div>';
        if (! empty( $message ) ) {
            \Pixelavo_Notice::set_notice(
                [
                    'id'          => 'ratting',
                    'type'        => 'info',
                    'dismissible' => true,
                    'message_type' => 'html',
                    'message'     => $message,
                    'display_after'  => ( 14 * DAY_IN_SECONDS ),
                    'close_by'    => 'transient'
                ]
            );
        }
    }

    

    function show_promo_notice() {

        // remove on next update
        if(!get_option('force_update_pixelavo_notice_info')) {
            delete_transient('pixelavo_notice_info');
            update_option('force_update_pixelavo_notice_info', true);
        }

        $noticeManager = \Pixelavo_Notice_Manager::instance();
        $notices = $noticeManager->get_notices_info();
        if(!empty($notices)) {
            foreach ($notices as $notice) {
                if(empty($notice['disable'])) {
                    \Pixelavo_Notice::set_notice($notice);
                }
            }
        }
    }

}

/**
 * Initialize Base Class
 */
Base::instance();
