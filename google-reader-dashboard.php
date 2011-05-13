<?php
/*
Plugin Name: Google Reader Dashboard
Plugin URI: http://www.jumping-duck.com/wordpress/
Description: Adds a Google Reader dashboard widget to the WordPress admin screen.
Version: 1.1.1
Author: Eric Mann
Author URI: http://www.eamann.com
License: GPL2
*/

/*  Copyright 2010  Eric Mann  (email : eric@eamann.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; version 2.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Define global variables and constants
if ( ! defined( 'GRD_VER' ) ) define( 'GRD_VER', '1.1.1' );
if ( ! defined( 'GRD_URL' ) ) define( 'GRD_URL', get_bloginfo('url') . '/wp-content/plugins/google-reader-dashboard' );
if ( ! defined( 'GRD_INC_URL' ) ) define( 'GRD_INC_URL', GRD_URL . '/includes');
if ( ! defined( 'GRD_BASE_URL' ) ) define( 'GRD_BASE_URL', dirname(__FILE__) );	
if ( ! defined( 'GRD_BASE_INC_URL' ) ) define( 'GRD_BASE_INC_URL', GRD_BASE_URL . '/includes');

// Add framework dependency loader and register the Google Reader Library
include_once( GRD_BASE_INC_URL . '/class.wp-frameworks.php');
wp_register_framework( 'greader', GRD_BASE_INC_URL . '/greader.class.php');

if ( ! get_option( 'GRD_Installed_Version' ) ) {
	update_option( 'GRD_Installed_Version', '1.1.1' );
}

/*
 * Sets admin warnings regarding required PHP version.
 */
function _grd_php_warning() {
	$data = get_plugin_data(__FILE__);
	
	echo '<div class="error"><p><strong>' . __('Warning:') . '</strong> '
		. sprintf(__('The active plugin %s is not compatible with your installed PHP version.') .'</p><p>',
			'&laquo;' . $data['Name'] . ' ' . $data['Version'] . '&raquo;')
		. sprintf(__('%s is required for this plugin.'), 'PHP 5 ');
	echo '</p></div>';
}

// START PROCEDURE

// Check required PHP version.
if ( version_compare(PHP_VERSION, '5.0', '<')) {
	add_action('admin_notices', '_grd_php_warning');
} else {
	include_once ( GRD_BASE_INC_URL . '/core.class.php' );
	$greader = new GRDplugin();
	$greader->load();
}
?>