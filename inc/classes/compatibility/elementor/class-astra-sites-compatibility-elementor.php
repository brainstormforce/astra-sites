<?php
/**
 * Astra Sites Compatibility for 'Elementor'
 *
 * @package Astra Sites
 * @since 2.0.0
 */

namespace AstraSites\Elementor;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Astra_Sites_Compatibility_Elementor' ) ) :

	/**
	 * Elementor Compatibility
	 *
	 * @since 2.0.0
	 */
	class Astra_Sites_Compatibility_Elementor {

		/**
		 * Instance
		 *
		 * @access private
		 * @var object Class object.
		 * @since 2.0.0
		 */
		private static $instance;

		/**
		 * Initiator
		 *
		 * @since 2.0.0
		 * @return object initialized object of class.
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * @since 2.0.0
		 */
		public function __construct() {

			/**
			 * Add Slashes
			 *
			 * @todo    Elementor already have below code which works on defining the constant `WP_LOAD_IMPORTERS`.
			 *          After defining the constant `WP_LOAD_IMPORTERS` in WP CLI it was not works.
			 *          Try to remove below duplicate code in future.
			 */
			if ( defined( 'WP_CLI' ) ) {
				add_filter( 'wp_import_post_meta', array( $this, 'on_wp_import_post_meta' ) );
				add_filter( 'wxr_importer.pre_process.post_meta', array( $this, 'on_wxr_importer_pre_process_post_meta' ) );
			}
		}

		/**
		 * Process post meta before WP importer.
		 *
		 * Normalize Elementor post meta on import, We need the `wp_slash` in order
		 * to avoid the unslashing during the `add_post_meta`.
		 *
		 * Fired by `wp_import_post_meta` filter.
		 *
		 * @since 1.4.3
		 * @access public
		 *
		 * @param array $post_meta Post meta.
		 *
		 * @return array Updated post meta.
		 */
		public function on_wp_import_post_meta( $post_meta ) {
			foreach ( $post_meta as &$meta ) {
				if ( '_elementor_data' === $meta['key'] ) {
					$meta['value'] = wp_slash( $meta['value'] );
					break;
				}
			}

			return $post_meta;
		}

		/**
		 * Process post meta before WXR importer.
		 *
		 * Normalize Elementor post meta on import with the new WP_importer, We need
		 * the `wp_slash` in order to avoid the unslashing during the `add_post_meta`.
		 *
		 * Fired by `wxr_importer.pre_process.post_meta` filter.
		 *
		 * @since 1.4.3
		 * @access public
		 *
		 * @param array $post_meta Post meta.
		 *
		 * @return array Updated post meta.
		 */
		public function on_wxr_importer_pre_process_post_meta( $post_meta ) {
			if ( '_elementor_data' === $post_meta['key'] ) {
				$post_meta['value'] = wp_slash( $post_meta['value'] );
			}

			return $post_meta;
		}
	}

	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	Astra_Sites_Compatibility_Elementor::get_instance();

endif;
