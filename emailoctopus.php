<?php
// don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/*
Plugin Name: Gravity Forms EmailOctopus Add-On
Plugin URI: https://gravityforms.com
Description: Integrates Gravity Forms with EmailOctopus
Version: 1.3.0
Author: Gravity Forms
Author URI: https://gravityforms.com
License: GPL-3.0+
Text Domain: gravityformsemailoctopus
Domain Path: /languages

------------------------------------------------------------------------
Copyright 2020-2024 Rocketgenius Inc.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see http://www.gnu.org/licenses.

*/

// Defines the current version of the Gravity Forms EmailOctopus Add-On.
define( 'GF_EMAILOCTOPUS_VERSION', '1.3.0' );

// Defines the minimum version of Gravity Forms required to run Gravity Forms EmailOctopus Add-On.
define( 'GF_EMAILOCTOPUS_MIN_GF_VERSION', '2.4' );

// After GF is loaded, load the add-on.
add_action( 'gform_loaded', array( 'GF_EmailOctopus_Bootstrap', 'load_addon' ), 5 );

/**
 * Loads the Gravity Forms EmailOctopus Add-On} Add-On.
 *
 * Includes the main class and registers it with GFAddOn.
 *
 * @since 1.0
 */
class GF_EmailOctopus_Bootstrap {

	/**
	 * Loads the required files.
	 *
	 * @since  1.0
	 */
	public static function load_addon() {

		// Requires the class file.
		require_once plugin_dir_path( __FILE__ ) . '/class-gf-emailoctopus.php';

		// Registers the class name with GFAddOn.
		GFAddOn::register( 'GF_EmailOctopus' );
	}
}

/**
 * Returns an instance of the GF_EmailOctopus class
 *
 * @since  1.0
 * @return GF_EmailOctopus An instance of the GF_EmailOctopus class
 */
function gf_emailoctopus() {
	return GF_EmailOctopus::get_instance();
}
