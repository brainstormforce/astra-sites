<?php
/**
 * Astra Sites Compatibility for 'WooCommerce'
 *
 * @see  https://wordpress.org/plugins/woocommerce/
 *
 * @package Astra Sites
 * @since 1.1.4
 */

if ( ! class_exists( 'Astra_Sites_Compatibility_WooCommerce' ) ) :

	/**
	 * WooCommerce Compatibility
	 *
	 * @since 1.1.4
	 */
	class Astra_Sites_Compatibility_WooCommerce {

		/**
		 * Instance
		 *
		 * @access private
		 * @var object Class object.
		 * @since 1.1.4
		 */
		private static $instance;

		/**
		 * Initiator
		 *
		 * @since 1.1.4
		 * @return object initialized object of class.
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self;
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * @since 1.1.4
		 */
		public function __construct() {
			add_action( 'astra_sites_import_start', array( $this, 'add_attributes' ), 10, 2 );
		}

		/**
		 * Add product attributes.
		 *
		 * @since 1.1.4
		 *
		 * @param  string $demo_data        Import data.
		 * @param  array  $demo_api_uri     Demo site URL.
		 * @return void
		 */
		function add_attributes( $demo_data = array(), $demo_api_uri = '' ) {
			$attributes = ( isset( $demo_data['astra-site-options-data']['woocommerce_product_attributes'] ) ) ? $demo_data['astra-site-options-data']['woocommerce_product_attributes'] : array();

			if ( ! empty( $attributes ) && function_exists( 'wc_create_attribute' ) ) {
				foreach ( $attributes as $key => $attribute ) {
					$args = array(
						'name'         => $attribute['attribute_label'],
						'slug'         => $attribute['attribute_name'],
						'type'         => $attribute['attribute_type'],
						'order_by'     => $attribute['attribute_orderby'],
						'has_archives' => $attribute['attribute_public'],
					);

					$id = wc_create_attribute( $args );
				}
			}
		}
	}

	/**
	 * Kicking this off by calling 'instance()' method
	 */
	Astra_Sites_Compatibility_WooCommerce::instance();

endif;
