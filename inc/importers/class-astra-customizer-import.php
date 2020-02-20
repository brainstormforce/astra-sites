<?php
/**
 * Customizer Data importer class.
 *
 * @since  1.0.0
 * @package Astra Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Customizer Data importer class.
 *
 * @since  1.0.0
 */
class Astra_Customizer_Import {

	/**
	 * Instance of Astra_Customizer_Import
	 *
	 * @since  1.0.0
	 * @var Astra_Customizer_Import
	 */
	private static $instance = null;

	/**
	 * Instantiate Astra_Customizer_Import
	 *
	 * @since  1.0.0
	 * @return (Object) Astra_Customizer_Import
	 */
	public static function instance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Import customizer options.
	 *
	 * @since  1.0.0
	 *
	 * @param  (Array) $options customizer options from the demo.
	 */
	public function import( $options ) {

		// Update Astra Theme customizer settings.
		if ( isset( $options['astra-settings'] ) ) {
			self::import_settings( $options['astra-settings'] );
		}

		// Add Custom CSS.
		if ( isset( $options['custom-css'] ) ) {
			wp_update_custom_css_post( $options['custom-css'] );
		}

	}

	/**
	 * Import Astra Setting's
	 *
	 * Download & Import images from Astra Customizer Settings.
	 *
	 * @since 1.0.10
	 *
	 * @param  array $options Astra Customizer setting array.
	 * @return void
	 */
	public static function import_settings( $options = array() ) {
		foreach ( $options as $key => $val ) {

			if ( Astra_Sites_Helper::is_image_url( $val ) ) {

				$data = Astra_Sites_Helper::sideload_image( $val );

				if ( ! is_wp_error( $data ) ) {
					$options[ $key ] = $data->url;
				}
			}
		}

		// Updated settings.
		update_option( 'astra-settings', $options );
	}
}
