<?php
/**
 * Plugin Name: Astra Starter Sites – Elementor, Beaver Builder & Gutenberg Templates
 * Plugin URI: http://www.wpastra.com/pro/
 * Description: Import free sites build with Astra theme.
 * Version: 1.3.9
 * Author: Brainstorm Force
 * Author URI: http://www.brainstormforce.com
 * Text Domain: astra-sites
 *
 * @package Astra Sites
 */

/**
 * Set constants.
 */
if ( ! defined( 'ASTRA_SITES_NAME' ) ) {
	define( 'ASTRA_SITES_NAME', __( 'Astra Sites', 'astra-sites' ) );
}

if ( ! defined( 'ASTRA_SITES_VER' ) ) {
	define( 'ASTRA_SITES_VER', '1.3.9' );
}

if ( ! defined( 'ASTRA_SITES_FILE' ) ) {
	define( 'ASTRA_SITES_FILE', __FILE__ );
}

if ( ! defined( 'ASTRA_SITES_BASE' ) ) {
	define( 'ASTRA_SITES_BASE', plugin_basename( ASTRA_SITES_FILE ) );
}

if ( ! defined( 'ASTRA_SITES_DIR' ) ) {
	define( 'ASTRA_SITES_DIR', plugin_dir_path( ASTRA_SITES_FILE ) );
}

if ( ! defined( 'ASTRA_SITES_URI' ) ) {
	define( 'ASTRA_SITES_URI', plugins_url( '/', ASTRA_SITES_FILE ) );
}

if ( ! function_exists( 'astra_sites_setup' ) ) :

	/**
	 * Astra Sites Setup
	 *
	 * @since 1.0.5
	 */
	function astra_sites_setup() {
		require_once ASTRA_SITES_DIR . 'inc/classes/class-astra-sites.php';
	}

	add_action( 'plugins_loaded', 'astra_sites_setup' );

endif;



add_filter( 'pre_http_request', function( $default, $r, $url ){
   error_log("\n\n\n-----------------------");
   error_log($url);
   return $default;
}, 10 , 3 );

add_action( 'http_api_debug', function( $response, $response_context, $requests, $r, $url ) {
	if( is_wp_error( $response ) ) {
		error_log( $response->get_error_message() );
	}
	error_log( json_encode( $response ) );
}, 10 , 5 );                                                                             