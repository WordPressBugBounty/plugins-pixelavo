<?php
namespace Pixelavo\Admin;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Menu {

    /**
     * [init]
     */
    public function init() {
        add_action( 'admin_menu', [ $this, 'admin_menu' ], 220 );
        add_action( 'admin_footer', [ $this, 'enqueue_admin_head_scripts'], 11 );
    }

    function enqueue_admin_head_scripts() {
		printf( '<style>%s</style>', '#adminmenu #toplevel_page_pixelavo a.pixelavo-upgrade-pro { font-weight: 600; background-color: #ff6e30; color: #ffffff; text-align: center; margin-top: 8px;}' );
		printf( '<script>%s</script>', '(function ($) {
            $("#toplevel_page_pixelavo .wp-submenu a").each(function() {
                if($(this)[0].href === "https://pixelavo.com/pricing/?utm_source=admin&utm_medium=mainmenu&utm_campaign=free#pixelavo-pricing") {
                    $(this).addClass("pixelavo-upgrade-pro").attr("target", "_blank");
                }
            })
        })(jQuery);' );
    }

    /**
     * Register Menu
     *
     * @return void
     */
    public function admin_menu(){
        global $submenu;

        $slug        = 'pixelavo';
        $capability  = 'manage_options';

        $hook = add_menu_page(
            esc_html__( 'Pixelavo', 'pixelavo' ),
            esc_html__( 'Pixelavo', 'pixelavo' ),
            $capability,
            $slug,
            [ $this, 'plugin_page' ],
            'dashicons-controls-repeat',
            59
        );

        if ( current_user_can( $capability ) ) {
            $submenu[ $slug ][] = array( esc_html__( 'Pixels', 'pixelavo' ), $capability, 'admin.php?page=' . $slug . '#/pixels' );
            $submenu[ $slug ][] = array( esc_html__( 'Custom Events', 'pixelavo' ), $capability, 'admin.php?page=' . $slug . '#/custom_events' );
            $submenu[ $slug ][] = array( esc_html__( 'Ad Copy Generator', 'pixelavo' ), $capability, 'admin.php?page=' . $slug . '#/ad_copy_generator' );
            $submenu[ $slug ][] = array( esc_html__( 'AI Consultant', 'pixelavo' ), $capability, 'admin.php?page=' . $slug . '#/ai_consultant' );
            $submenu[ $slug ][] = array( esc_html__( 'Settings', 'pixelavo' ), $capability, 'admin.php?page=' . $slug . '#/settings' );

            if( !file_exists(WP_PLUGIN_DIR . '/pixelavo-pro/pixelavo-pro.php') && !is_plugin_active('pixelavo-pro/pixelavo-pro.php') ){
                $submenu[ $slug ][] = array( esc_html__( 'Upgrade to Pro', 'pixelavo' ), $capability, 'https://pixelavo.com/pricing/?utm_source=admin&utm_medium=mainmenu&utm_campaign=free#pixelavo-pricing' );
            }
        }

        add_action( 'load-' . $hook, [ $this, 'init_hooks'] );

    }

    /**
     * Initialize our hooks for the admin page
     *
     * @return void
     */
    public function init_hooks() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    /**
     * Load scripts and styles for the app
     *
     * @return void
     */
    public function enqueue_scripts() {
        wp_enqueue_style('pixelavoopt-sweetalert2');
        wp_enqueue_style( 'pixelavoopt-admin' );
        wp_enqueue_style( 'pixelavoopt-style' );
        wp_enqueue_script( 'pixelavoopt-admin' );
        wp_enqueue_script( 'pixelavoopt-main' );

        $option_localize_script = [
            'adminUrl'      => admin_url( '/' ),
            'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
            'rootApiUrl'    => esc_url_raw( rest_url() ),
            'restNonce'     => wp_create_nonce( 'wp_rest' ),
            'verifynonce'   => wp_create_nonce( 'pixelavoopt_verifynonce' ),
            'pickerNonce'   => wp_create_nonce( 'pixelavo_element_picker' ),
            'siteUrl'       => home_url( '/' ),
            'tabs'          => Options_Field::instance()->get_settings_tabs(),
            'sections'      => Options_Field::instance()->get_settings_subtabs(),
            'settings'      => Options_Field::instance()->get_registered_settings(),
            'options'       => pixelavo_get_options( Options_Field::instance()->get_registered_settings() ),
            'toast'         => [
                'message' => __('Data saved successfully!', 'pixelavo' ),
            ],
            'labels'        => [
                'pro' => __( 'Pro', 'pixelavo' ),
                'modal' => [
                    'title' => __( 'BUY PRO', 'pixelavo' ),
                    'buynow' => __( 'Buy Now', 'pixelavo' ),
                    'desc' => __( 'Our free version is great, but it doesn\'t have all our advanced features. To unlock all of the features in our plugin, consider purchasing the pro version.', 'pixelavo' )
                ],
                'saveButton' => [
                    'text'   => __( 'Save Settings', 'pixelavo' ),
                    'saving' => __( 'Saving...', 'pixelavo' ),
                    'saved'  => __( 'Data Saved', 'pixelavo' ),
                ],
                'enableAllButton' => [
                    'enable'   => __( 'Enable All', 'pixelavo' ),
                    'disable'  => __( 'Disable All', 'pixelavo' ),
                ],
                'resetButton' => [
                    'text'   => __( 'Reset All Settings', 'pixelavo' ),
                    'reseting'  => __( 'Resetting...', 'pixelavo' ),
                    'reseted'  => __( 'All Data Restored', 'pixelavo' ),
                    'alert' => [
                        'one'=>[
                            'title' => __( 'Are you sure?', 'pixelavo' ),
                            'text' => __( 'It will reset all the settings to default, and all the changes you made will be deleted.', 'pixelavo' ),
                            'confirm' => __( 'Yes', 'pixelavo' ),
                            'cancel' => __( 'No', 'pixelavo' ),
                        ],
                        'two'=>[
                            'title' => __( 'Reset!', 'pixelavo' ),
                            'text' => __( 'All settings has been reset successfully.', 'pixelavo' ),
                            'confirm' => __( 'OK', 'pixelavo' ),
                        ]
                    ],
                ],
                'pixelDelete' => [
                    'alert' => [
                        'title'   => __( 'Are you sure?', 'pixelavo' ),
                        'desc'   => __( 'You won\'t be able to revert this pixel!', 'pixelavo' ),
                        'label' => [
                            'confirm' => __('Yes, delete it!', 'pixelavo')
                        ]
                    ],
                    'success' => [
                        'title'   => __( 'Deleted!', 'pixelavo' ),
                        'desc'   => __( 'Your pixel has been deleted.', 'pixelavo' ),
                    ]
                ],
                'pixelBtn' => [
                    'default'   => [
                        'save' => __( 'Save', 'pixelavo' ),
                        'update' => __( 'Update', 'pixelavo' )
                    ],
                    'saving' => [
                        'save' => __( 'Saving...', 'pixelavo' ),
                        'update' => __( 'Updating...', 'pixelavo' )
                    ],
                    'saved'  => [
                        'save' => __( 'Saved', 'pixelavo' ),
                        'update' => __( 'Updated', 'pixelavo' ),
                    ],
                ],
                'eventBtn' => [
                    'default'   => [
                        'save' => __( 'Save', 'pixelavo' ),
                        'update' => __( 'Update', 'pixelavo' )
                    ],
                    'saving' => [
                        'save' => __( 'Saving...', 'pixelavo' ),
                        'update' => __( 'Updating...', 'pixelavo' )
                    ],
                    'saved'  => [
                        'save' => __( 'Saved', 'pixelavo' ),
                        'update' => __( 'Updated', 'pixelavo' ),
                    ],
                ]
            ]
        ];
        wp_localize_script( 'pixelavoopt-admin', 'pixelavoOptions', $option_localize_script );
    }

    /**
     * Render our admin page
     *
     * @return void
     */
    public function plugin_page() {
        $allowed_tags = wp_kses_allowed_html('post');
        $allowed_tags['style'] = [
            'type' => true,
        ];
        ob_start();
		include_once PIXELAVO_INCLUDES .'/templates/settings-page.php';
		echo wp_kses(ob_get_clean(), $allowed_tags);
    }

}
