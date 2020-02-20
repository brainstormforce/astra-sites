<?php
/**
 * Functions
 *
 * @since  2.0.0
 * @package Astra Sites
 */

if ( ! function_exists( 'astra_sites_error_log' ) ) :

	/**
	 * Error Log
	 *
	 * A wrapper function for the error_log() function.
	 *
	 * @since 2.0.0
	 *
	 * @param  mixed $message Error message.
	 * @return void
	 */
	function astra_sites_error_log( $message = '' ) {
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			if ( is_array( $message ) ) {
				$message = wp_json_encode( $message );
			}

			error_log( $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

endif;
