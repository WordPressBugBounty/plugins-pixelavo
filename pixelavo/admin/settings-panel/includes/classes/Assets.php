<?php
namespace Pixelavo;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Scripts and Styles Class
 */
class Assets {

    function __construct() {
        if ( is_admin() ) {
            add_action( 'admin_enqueue_scripts', [ $this, 'register' ], 5 );
        }
    }

    /**
     * Register our app scripts and styles
     *
     * @return void
     */
    public function register() {
        $this->register_scripts( $this->get_scripts() );
        $this->register_styles( $this->get_styles() );
    }

    /**
     * Register scripts
     *
     * @param  array $scripts
     *
     * @return void
     */
    private function register_scripts( $scripts ) {
        foreach ( $scripts as $handle => $script ) {
            $deps      = isset( $script['deps'] ) ? $script['deps'] : false;
            $in_footer = isset( $script['in_footer'] ) ? $script['in_footer'] : false;
            $version   = isset( $script['version'] ) ? $script['version'] : '1.0.0';

            wp_register_script( $handle, $script['src'], $deps, $version, $in_footer );
        }
    }

    /**
     * Register styles
     *
     * @param  array $styles
     *
     * @return void
     */
    public function register_styles( $styles ) {
        foreach ( $styles as $handle => $style ) {
            $deps = isset( $style['deps'] ) ? $style['deps'] : false;

            wp_register_style( $handle, $style['src'], $deps, PIXELAVO_VERSION );
        }
    }

    /**
     * Get all registered scripts
     *
     * @return array
     */
    public function get_scripts() {
        $scripts = [
            'pixelavoopt-runtime' => [
                'src'       => PIXELAVO_ASSETS . '/js/runtime.min.js',
                'version'   => PIXELAVO_VERSION,
                'in_footer' => true
            ],
            'pixelavoopt-vendor' => [
                'src'       => PIXELAVO_ASSETS . '/js/vendors.min.js',
                'version'   => PIXELAVO_VERSION,
                'in_footer' => true
            ],
            'pixelavoopt-admin' => [
                'src'       => PIXELAVO_ASSETS . '/js/admin.min.js',
                'deps'      => [ 'jquery', 'pixelavoopt-vendor', 'pixelavoopt-runtime' ],
                'version'   => PIXELAVO_VERSION,
                'in_footer' => true
            ],
            'pixelavoopt-main' => [
                'src'       => PIXELAVO_ASSETS . '/js/main.js',
                'deps'      => [],
                'version'   => PIXELAVO_VERSION,
                'in_footer' => true
            ]
        ];

        return $scripts;
    }

    /**
     * Get registered styles
     *
     * @return array
     */
    public function get_styles() {

        $styles = [
            'pixelavoopt-style' => [
                'src' =>  PIXELAVO_ASSETS . '/css/style.css'
            ],
            'pixelavoopt-sweetalert2' => [
                'src' =>  PIXELAVO_ASSETS . '/css/sweetalert2.min.css'
            ],
            'pixelavoopt-admin' => [
                'src' =>  PIXELAVO_ASSETS . '/css/admin.css'
            ]
        ];

        return $styles;
    }

}