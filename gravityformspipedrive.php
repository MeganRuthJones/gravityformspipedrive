<?php
/*
Plugin Name: Gravity Forms Pipedrive Add-On
Description: Integrates Gravity Forms with Pipedrive CRM, allowing you to send form entries to Pipedrive as Persons, Organizations, and Deals.
Version: 1.0.1
Author: Megan Jones
Text Domain: gravityformspipedrive
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GF_PIPEDRIVE_VERSION', '1.0.1' );

// Load the add-on once Gravity Forms is ready.
add_action( 'gform_loaded', array( 'GF_Pipedrive_Bootstrap', 'load' ), 5 );

class GF_Pipedrive_Bootstrap {

	public static function load() {

		if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
			return;
		}

		require_once plugin_dir_path( __FILE__ ) . 'class-gf-pipedrive-addon.php';

		GFAddOn::register( 'GFPipedriveAddOn' );
	}
}

function gf_pipedrive() {
	return GFPipedriveAddOn::get_instance();
}
