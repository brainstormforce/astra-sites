<?php
/**
 * Batch Processing
 *
 * @package Astra Sites
 * @since 1.0.14
 */

if ( ! class_exists( 'Astra_Sites_Batch_Processing_Importer' ) ) :

	/**
	 * Astra_Sites_Batch_Processing_Importer
	 *
	 * @since 1.0.14
	 */
	class Astra_Sites_Batch_Processing_Importer {

		/**
		 * Instance
		 *
		 * @since 1.0.14
		 * @access private
		 * @var object Class object.
		 */
		private static $instance;

		/**
		 * Initiator
		 *
		 * @since 1.0.14
		 * @return object initialized object of class.
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self;
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * @since 1.0.14
		 */
		public function __construct() {
		}

		/**
		 * Import
		 *
		 * @since 1.0.14
		 * @return void
		 */
		public function import_categories() {
			error_log( 'Requesting Tags' );
			update_option( 'astra-sites-batch-status-string', 'Requesting Tags' );

			$api_args = array(
				'timeout' => 30,
			);
			$tags_request = wp_remote_get( trailingslashit( Astra_Sites::get_instance()->get_api_domain() ) . '/wp-json/wp/v2/astra-sites-tag/?_fields=id,name,slug', $api_args );
			if ( ! is_wp_error( $tags_request ) && 200 === (int) wp_remote_retrieve_response_code( $tags_request ) ) {
				$tags = json_decode( wp_remote_retrieve_body( $tags_request ), true );
				update_option( 'astra-sites-tags', $tags );
			}

			error_log( 'Tags Imported Successfully!' );
			update_option( 'astra-sites-batch-status-string', 'Tags Imported Successfully!' );
		}

		/**
		 * Import
		 *
		 * @since 1.0.14
		 * @return void
		 */
		public function import_sites( $page = 1 ) {
			$api_args = array(
				'timeout' => 30,
			);
			error_log( 'Requesting ' . $page );
			update_option( 'astra-sites-batch-status-string', 'Requesting ' . $page );
			$response = wp_remote_get( trailingslashit( Astra_Sites::get_instance()->get_api_domain() ) . '/wp-json/astra-sites/v1/sites-and-pages?per_page=30&page=' . $page, $api_args );
			if ( ! is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) === 200 ) {
				$sites_and_pages = json_decode( wp_remote_retrieve_body( $response ), true );

				error_log( 'Storing data for page ' . $page . ' in option astra-sites-and-pages-page-' . $page );
				update_option( 'astra-sites-batch-status-string', 'Storing data for page ' . $page . ' in option astra-sites-and-pages-page-' . $page );

				update_option( 'astra-sites-and-pages-page-' . $page, $sites_and_pages );
			} else {
				error_log( 'API Error: ' . $response->get_error_message() );
			}

			error_log( 'Complete storing data for page ' . $page );
			update_option( 'astra-sites-batch-status-string', 'Complete storing data for page ' . $page );
		}
	}

	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	Astra_Sites_Batch_Processing_Importer::get_instance();

endif;
