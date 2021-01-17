<?php
/**
 * Plugin Name: Media Sorter
 * Description: Get organized with thousands of images. Organize media into folders.
 * Version:     2.4
 * Author:      mistersippi
 * Text Domain: mediasorter
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /languages/
 */

/*

This plugin uses Open Source components. You can find the source code 
of their open source projects along with license information below. 
We acknowledge and are grateful to these developers for their contributions to open source.

--------------------------------------------------------------------

Project: FileBird – WordPress Media Library Folders (version:2.0)
Author: Ninja Team
Url: https://wordpress.org/plugins/filebird/
Lisence: GPL (General Public License)

--------------------------------------------------------------------

Project: Folders – Organize Pages, Posts and Media Library Folders with Drag and Drop (version:2.1.1)
Author: Premio
Url: https://wordpress.org/plugins/folders/
Lisence: GPL (General Public License)

*/

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'MEDIASORTER__FILE__', __FILE__ );
define( 'MEDIASORTER_FOLDER', 'mediasorter_wpfolder' );
define( 'MEDIASORTER_VERSION', '2.4' );
define( 'MEDIASORTER_PATH', plugin_dir_path( MEDIASORTER__FILE__ ) );
define( 'MEDIASORTER_URL', plugins_url( '/', MEDIASORTER__FILE__ ) );
define( 'MEDIASORTER_ASSETS_URL', MEDIASORTER_URL . 'assets/' );
define( 'MEDIASORTER_TEXT_DOMAIN', 'mediasorter' );
define( 'MEDIASORTER_PLUGIN_BASE', plugin_basename( MEDIASORTER__FILE__ ) );
define( 'MEDIASORTER_PLUGIN_NAME', 'mediasorter' );



function mediasorter_plugins_loaded(){

	// main files
	include_once ( MEDIASORTER_PATH . 'inc/plugin.php' );
	include_once ( MEDIASORTER_PATH . 'inc/functions.php' );
	
	mediasorter_cores();
	
	load_plugin_textdomain(MEDIASORTER_TEXT_DOMAIN, false, plugin_basename(__DIR__) . '/languages/');
}


add_action('plugins_loaded', 'mediasorter_plugins_loaded');





