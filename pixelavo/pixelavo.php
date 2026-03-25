<?php

/**
 * Plugin Name: Pixelavo - Server Side Tracking & Pixel + AI Ads Tools
 * Description: Add pixel to your website to track the actions people take when they interact with it. Includes AI Ad Copy Generator and Marketing Consultant.
 * Plugin URI: https://pixelavo.com/
 * Author:      HasThemes
 * Author URI: https://hasthemes.com/
 * Version:     1.5.3
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * License:     GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: pixelavo
 * Domain Path: /languages
 */

/**
 * Exit if accessed directly
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin const
 */
define('PIXELAVO_VERSION', '1.5.3');
define('PIXELAVO_PL_ROOT', __FILE__);
define('PIXELAVO_PL_URL', plugins_url('/', PIXELAVO_PL_ROOT));
define('PIXELAVO_PL_PATH', plugin_dir_path(PIXELAVO_PL_ROOT));
define('PIXELAVO_DIR_URL', plugin_dir_url(PIXELAVO_PL_ROOT));
define('PIXELAVO_PLUGIN_BASE', plugin_basename(PIXELAVO_PL_ROOT));

global $pixelavoEventsLocalizedData;
$pixelavoEventsLocalizedData = [];

/**
 * Require Files
 */
require_once (PIXELAVO_PL_PATH . 'includes/base.php');