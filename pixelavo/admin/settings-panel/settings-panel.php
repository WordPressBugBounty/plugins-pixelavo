<?php
// don't call the file directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Plugin Main Class
 */
final class Pixelavo_Settings_Base{

    /**
     * Holds various class instances
     *
     * @var array
     */
    private $container = [];

    /**
     * Constructor for the Pixelavo_Settings_Base class
     *
     * Sets up all the appropriate hooks and actions
     */
    public function __construct(){
        $this->define_constants();
        $this->init_plugin();
    }

    /**
     * Initializes the Pixelavo_Settings_Base() class
     *
     * Checks for an existing Pixelavo_Settings_Base() instance
     * and if it doesn't find one, creates it.
     */
    public static function init() {
        static $instance = false;

        if ( ! $instance ) {
            $instance = new Pixelavo_Settings_Base();
        }

        return $instance;
    }

    /**
     * Magic getter to bypass referencing plugin.
     *
     * @param $prop
     *
     * @return mixed
     */
    public function __get( $prop ) {
        if ( array_key_exists( $prop, $this->container ) ) {
            return $this->container[ $prop ];
        }

        return $this->{$prop};
    }

    /**
     * Magic isset to bypass referencing plugin.
     *
     * @param $prop
     *
     * @return mixed
     */
    public function __isset( $prop ) {
        return isset( $this->{$prop} ) || isset( $this->container[ $prop ] );
    }

    /**
     * Define the constants
     *
     * @return void
     */
    public function define_constants() {
        $this->define( 'PIXELAVO_FILE', __FILE__ );
        $this->define( 'PIXELAVO_PATH', dirname( PIXELAVO_FILE ) );
        $this->define( 'PIXELAVO_INCLUDES', PIXELAVO_PATH . '/includes' );
        $this->define( 'PIXELAVO_URL', plugins_url( '', PIXELAVO_FILE ) );
        $this->define( 'PIXELAVO_ASSETS', PIXELAVO_URL . '/assets' );
    }

    /**
     * Define constant if not already set
     *
     * @param  string $name
     * @param  string|bool $value
     * @return mixed
     */
    private function define( $name, $value ) {
        if ( ! defined( $name ) ) {
            define( $name, $value );
        }
    }

    /**
     * Load the plugin after all plugins are loaded
     *
     * @return void
     */
    public function init_plugin() {
        $this->includes();
        $this->init_hooks();
    }

    public function includes() {
        require_once PIXELAVO_INCLUDES . '/classes/Assets.php';
        require_once PIXELAVO_INCLUDES . '/classes/Sanitize_Trait.php';

        if ( $this->is_request( 'admin' ) ) {
            require_once PIXELAVO_INCLUDES . '/classes/Admin.php';
        }

        require_once PIXELAVO_INCLUDES . '/classes/Api.php';
    }

    /**
     * Initialize the hooks
     *
     * @return void
     */
    public function init_hooks() {
        add_action( 'init', [ $this, 'init_classes' ] );
    }

     /**
     * Instantiate the required classes
     *
     * @return void
     */
    public function init_classes() {

        if ( $this->is_request( 'admin' ) ) {
            $this->container['admin'] = new Pixelavo\Admin();
        }

        $this->container['api'] = new Pixelavo\Api();
        $this->container['assets'] = new Pixelavo\Assets();
    }

    /**
     * What type of request is this?
     *
     * @param  string $type admin, ajax, cron or frontend.
     *
     * @return bool
     */
    private function is_request( $type ) {
        switch ( $type ) {
            case 'admin' :
                return is_admin();

            case 'ajax' :
                return defined( 'DOING_AJAX' );

            case 'rest' :
                return defined( 'REST_REQUEST' );

            case 'cron' :
                return defined( 'DOING_CRON' );

            case 'frontend' :
                return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
        }
    }

}