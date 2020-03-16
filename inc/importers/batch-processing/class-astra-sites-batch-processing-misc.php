<?php
/**
 * Misc batch import tasks.
 *
 * @package Astra Sites
 * @since 1.1.6
 */

if ( ! class_exists( 'Astra_Sites_Batch_Processing_Misc' ) ) :

	/**
	 * Astra_Sites_Batch_Processing_Misc
	 *
	 * @since 1.1.6
	 */
	class Astra_Sites_Batch_Processing_Misc {

		/**
		 * Instance
		 *
		 * @since 1.1.6
		 * @access private
		 * @var object Class object.
		 */
		private static $instance;

		/**
		 * Initiator
		 *
		 * @since 1.1.6
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
		 * @since 1.1.6
		 */
		public function __construct() {}

		/**
		 * Import
		 *
		 * @since 1.1.6
		 * @return void
		 */
		public function import() {

			if ( defined( 'WP_CLI' ) ) {
				WP_CLI::line( 'Processing "MISC" Batch Import' );
			}

			Astra_Sites_Importer_Log::add( '---- Processing MISC ----' );
			self::fix_nav_menus();
		}

		/**
		 * Import Module Images.
		 *
		 * @return object
		 */
		public static function fix_nav_menus() {

			if ( defined( 'WP_CLI' ) ) {
				WP_CLI::line( 'Setting Nav Menus' );
			}

			// Not found site data, then return.
			$demo_data = get_option( 'astra_sites_import_data', array() );
			if ( ! isset( $demo_data['astra-post-data-mapping'] ) ) {
				return;
			}

			// Not found/empty XML URL, then return.
			$xml_url = ( isset( $demo_data['astra-site-wxr-path'] ) ) ? esc_url( $demo_data['astra-site-wxr-path'] ) : '';
			if ( empty( $xml_url ) ) {
				return;
			}

			// Not empty site URL, then return.
			$site_url = strpos( $xml_url, '/wp-content' );
			if ( false === $site_url ) {
				return;
			}

			// Get remote site URL.
			$site_url = substr( $xml_url, 0, $site_url );

			$post_ids = self::get_menu_post_ids();
			if ( is_array( $post_ids ) ) {
				foreach ( $post_ids as $post_id ) {
					if ( defined( 'WP_CLI' ) ) {
						WP_CLI::line( 'Post ID: ' . $post_id );
					}
					Astra_Sites_Importer_Log::add( 'Post ID: ' . $post_id );
					$menu_url = get_post_meta( $post_id, '_menu_item_url', true );

					if ( $menu_url ) {
						$menu_url = str_replace( $site_url, site_url(), $menu_url );
						update_post_meta( $post_id, '_menu_item_url', $menu_url );
					}
				}
			}
		}

		/**
		 * Get all post id's
		 *
		 * @since 1.1.6
		 *
		 * @return array
		 */
		public static function get_menu_post_ids() {

			$args = array(
				'post_type'     => 'nav_menu_item',

				// Query performance optimization.
				'fields'        => 'ids',
				'no_found_rows' => true,
				'post_status'   => 'any',
			);

			$query = new WP_Query( $args );

			// Have posts?
			if ( $query->have_posts() ) :

				return $query->posts;

			endif;
			return null;
		}

	}

	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	Astra_Sites_Batch_Processing_Misc::get_instance();

endif;
