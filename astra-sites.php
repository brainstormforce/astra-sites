<?php
/**
 * Plugin Name: Astra Starter Sites
 * Plugin URI: http://www.wpastra.com/pro/
 * Description: Import free sites build with Astra theme.
 * Version: 1.3.12
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
	define( 'ASTRA_SITES_VER', '1.3.12' );
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



// /**
//  * Debug WordPress hook sequence.
//  *
//  * > How it works?
//  * 	Add query parameter `debug` in URL e.g. https://<mysite>/?debug
//  * 	It'll show the list of hooks in sequence.
//  * 
//  * @todo Change `prefix_` with your own prefix.
//  * 
//  * @since 1.0.0
//  */
// if( ! function_exists( 'prefix_hook_sequence' ) ) {
// 	function prefix_hook_sequence() {
// 		error_log( '--------------- Started ------------------'  );
// 	    foreach( $GLOBALS['wp_actions'] as $action => $count ) {
// 			error_log( $action .' - (' .$count. ')'  );
// 	    }
// 		error_log( '--------------- End ------------------'  );
// 	}
// 	// Add hook.
// 	add_action( 'shutdown', 'prefix_hook_sequence' );
// }
// 
// if( ! function_exists( 'prefix_debug_meta' ) ) {
// 	function prefix_debug_meta( $object_id, $meta_key, $_meta_value ) {

// 		$_meta_value = is_array( $_meta_value ) ? json_encode( $_meta_value ) : $_meta_value;
// 		error_log( '--------------- Started ------------------'  );
// 		error_log( $object_id );
// 		error_log( $meta_key );
// 		error_log( $_meta_value );
// 		error_log( '--------------- End ------------------'  );
// 	}
// 	// Add hook.
// 	add_action( 'add_post_meta', 'prefix_debug_meta', 10, 3 );
// }