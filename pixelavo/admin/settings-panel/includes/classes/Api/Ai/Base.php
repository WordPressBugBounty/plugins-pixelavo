<?php
namespace Pixelavo\Api\Ai;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use WP_REST_Controller;

require_once __DIR__ . '/Consultant.php';
require_once __DIR__ . '/AdCopyGenerator.php';
/**
 * REST_API Handler
 */
class Base extends WP_REST_Controller {

    /**
     * Base namespace
     */
    protected $namespace;
    /**
     * Error handler
     */
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
        $this->namespace = 'pixelavoopt/v1/ai';
        $this->errors = new \WP_Error();
    }

    /**
     * Register the routes
     *
     * @return void
     */
    public function register_routes() {

        $ad_copy_generator = new AdCopyGenerator();
        $consultant = new Consultant();
        register_rest_route(
            $this->namespace,
            '/chat',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [$consultant, 'get'],
                    'permission_callback' => [$this, 'permissions_check'],
                ],
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => [$consultant, 'create'],
                    'permission_callback' => [$this, 'permissions_check'],
                ],
                [
                    'methods'             => \WP_REST_Server::DELETABLE,
                    'callback'            => [$consultant, 'delete'],
                    'permission_callback' => [$this, 'permissions_check'],
                ],
                [
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'callback'            => [$consultant, 'rename'],
                    'permission_callback' => [$this, 'permissions_check'],
                ]
            ]
        );
        register_rest_route(
            $this->namespace,
            '/generate_ad_copy',
            [
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => [$ad_copy_generator, 'generate_ad_copy'],
                    'permission_callback' => [$this, 'permissions_check'],
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

}