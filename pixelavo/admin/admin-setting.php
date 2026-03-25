<?php 

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

require_once( PIXELAVO_PL_PATH.'admin/settings-panel/settings-panel.php' );

class Pixelavo_Admin_Settings{

	/**
     * [$_instance]
     * @var null
     */
    private static $_instance = null;

    /**
     * [instance] Initializes a singleton instance
     * @return [Base]
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


	function __construct(){
		Pixelavo_Settings_Base::init();
	}

}

Pixelavo_Admin_Settings::instance();