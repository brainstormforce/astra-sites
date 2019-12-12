<?php
/**
 * Astra_Elementor_Images class
 *
 * This class is used to manage Pixabay Images.
 *
 * @package Astra Sites
 * @since 2.0.0
 */

use Elementor\Utils;

// If plugin - 'Elementor' not exist then return.
if ( class_exists( 'Astra_Elementor_Images' ) ) {
	return;
}

/**
 * Astra_Elementor_Images
 */
class Astra_Elementor_Images {

	/**
	 * Instance of Astra_Sites
	 *
	 * @since  2.0.0
	 * @var (Object) Astra_Sites
	 */
	private static $_instance = null;

	/**
	 * Instance of Astra_Elementor_Images.
	 *
	 * @since  2.0.0
	 *
	 * @return object Class object.
	 */
	public static function get_instance() {
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Import Image.
	 *
	 * @since  2.0.0
	 * @param array $image Downloaded Image array.
	 */
	public function get_attachment_data( $image ) {

		if ( ! empty( $image ) ) {
			return array(
				'content' => array(
					array(
						'id'       => \Elementor\Utils::generate_random_string(),
						'elType'   => 'section',
						'settings' => array(),
						'isInner'  => false,
						'elements' => array(
							array(
								'id'       => \Elementor\Utils::generate_random_string(),
								'elType'   => 'column',
								'elements' => array(
									array(
										'id'         => \Elementor\Utils::generate_random_string(),
										'elType'     => 'widget',
										'settings'   => array(
											'image'      => array(
												'url' => wp_get_attachment_url( $image ),
												'id'  => $image,
											),
											'image_size' => 'full',
										),
										'widgetType' => 'image',
									),
								),
								'isInner'  => false,
							),
						),
					),
				),
			);
		}
		return array();
	}
}

/**
 * Kicking this off by calling 'get_instance()' method
 */
Astra_Elementor_Images::get_instance();
