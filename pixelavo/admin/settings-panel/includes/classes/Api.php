<?php
namespace Pixelavo;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use WP_REST_Controller;

/**
 * REST_API Handler
 */
class Api extends WP_REST_Controller {

    /**
     * [__construct description]
     */
    public function __construct() {
        $this->includes();
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Include the controller classes
     *
     * @return void
     */
    private function includes() {
        if (!class_exists(__NAMESPACE__ . '\Api\Settings')) {
            require_once __DIR__ . '/Api/Settings.php';
        }
        if (!class_exists(__NAMESPACE__ . '\Api\Ai\Base')) {
            require_once __DIR__ . '/Api/Ai/Base.php';
        }
    }

    /**
     * Register the API routes
     *
     * @return void
     */
    public function register_routes() {
        (new Api\Settings())->register_routes();
        (new Api\Ai\Base())->register_routes();
    }

}