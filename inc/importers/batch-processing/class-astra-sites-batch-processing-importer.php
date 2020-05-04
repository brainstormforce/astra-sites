<?php
/**
 * Batch Processing
 *
 * @package Astra Sites
 * @since 2.0.0
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
				self::$instance = new self();
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
		 * Import Categories
		 *
		 * @since 2.0.0
		 * @return void
		 */
		public function import_categories() {
			astra_sites_error_log( 'Requesting Tags' );
			update_site_option( 'astra-sites-batch-status-string', 'Requesting Tags' );

			$api_args     = array(
				'timeout' => 30,
			);
			$tags_request = wp_remote_get( trailingslashit( Astra_Sites::get_instance()->get_api_domain() ) . 'wp-json/wp/v2/astra-sites-tag/?_fields=id,name,slug', $api_args );
			if ( ! is_wp_error( $tags_request ) && 200 === (int) wp_remote_retrieve_response_code( $tags_request ) ) {
				$tags = json_decode( wp_remote_retrieve_body( $tags_request ), true );

				if ( isset( $tags['code'] ) ) {
					$message = isset( $tags['message'] ) ? $tags['message'] : '';
					if ( ! empty( $message ) ) {
						astra_sites_error_log( 'HTTP Request Error: ' . $message );
					} else {
						astra_sites_error_log( 'HTTP Request Error!' );
					}
				} else {
					update_site_option( 'astra-sites-tags', $tags );

					if ( defined( 'WP_CLI' ) ) {
						$this->generate_file( 'astra-sites-tags', $tags );
					}
				}
			}

			astra_sites_error_log( 'Tags Imported Successfully!' );
			update_site_option( 'astra-sites-batch-status-string', 'Tags Imported Successfully!' );
		}

		/**
		 * Generate JSON file.
		 *
		 * @since 2.0.0
		 *
		 * @param  string $filename File name.
		 * @param  array  $data     JSON file data.
		 * @return void.
		 */
		public function generate_file( $filename = '', $data = array() ) {
			if ( defined( 'WP_CLI' ) ) {
				Astra_Sites::get_instance()->get_filesystem()->put_contents( ASTRA_SITES_DIR . 'inc/json/' . $filename . '.json', wp_json_encode( $data ) );
			}
		}

		/**
		 * Import Categories
		 *
		 * @since 2.0.0
		 * @return void
		 */
		public function import_site_categories() {
			astra_sites_error_log( 'Requesting Site Categories' );
			update_site_option( 'astra-sites-batch-status-string', 'Requesting Site Categories' );

			$api_args           = array(
				'timeout' => 30,
			);
			$categories_request = wp_remote_get( trailingslashit( Astra_Sites::get_instance()->get_api_domain() ) . 'wp-json/wp/v2/astra-site-category/?_fields=id,name,slug&per_page=100', $api_args );
			if ( ! is_wp_error( $categories_request ) && 200 === (int) wp_remote_retrieve_response_code( $categories_request ) ) {
				$categories = json_decode( wp_remote_retrieve_body( $categories_request ), true );

				if ( isset( $categories['code'] ) ) {
					$message = isset( $categories['message'] ) ? $categories['message'] : '';
					if ( ! empty( $message ) ) {
						astra_sites_error_log( 'HTTP Request Error: ' . $message );
					} else {
						astra_sites_error_log( 'HTTP Request Error!' );
					}
				} else {
					update_site_option( 'astra-sites-categories', $categories );

					if ( defined( 'WP_CLI' ) ) {
						$this->generate_file( 'astra-sites-categories', $categories );
					}
				}
			}

			astra_sites_error_log( 'Site Categories Imported Successfully!' );
			update_site_option( 'astra-sites-batch-status-string', 'Site Categories Imported Successfully!' );
		}

		/**
		 * Import Block Categories
		 *
		 * @since 2.0.0
		 * @return void
		 */
		public function import_block_categories() {
			astra_sites_error_log( 'Requesting Block Categories' );
			update_site_option( 'astra-sites-batch-status-string', 'Requesting Block Categories' );

			$api_args     = array(
				'timeout' => 30,
			);
			$tags_request = wp_remote_get( trailingslashit( Astra_Sites::get_instance()->get_api_domain() ) . 'wp-json/wp/v2/blocks-category/?_fields=id,name,slug&per_page=100&hide_empty=1', $api_args );
			if ( ! is_wp_error( $tags_request ) && 200 === (int) wp_remote_retrieve_response_code( $tags_request ) ) {
				$tags = json_decode( wp_remote_retrieve_body( $tags_request ), true );

				if ( isset( $tags['code'] ) ) {
					$message = isset( $tags['message'] ) ? $tags['message'] : '';
					if ( ! empty( $message ) ) {
						astra_sites_error_log( 'HTTP Request Error: ' . $message );
					} else {
						astra_sites_error_log( 'HTTP Request Error!' );
					}
				} else {
					$categories = array();
					foreach ( $tags as $key => $value ) {
						$categories[ $value['id'] ] = $value;
					}

					update_site_option( 'astra-blocks-categories', $categories );

					if ( defined( 'WP_CLI' ) ) {
						$this->generate_file( 'astra-blocks-categories', $categories );
					}
				}
			}

			astra_sites_error_log( 'Block Categories Imported Successfully!' );
			update_site_option( 'astra-sites-batch-status-string', 'Categories Imported Successfully!' );
		}


		/**
		 * Import Page Builders
		 *
		 * @since 2.0.0
		 * @return void
		 */
		public function set_license_page_builder() {

			astra_sites_error_log( 'Requesting License Page Builders' );

			$url = add_query_arg(
				array(
					'_fields'                  => 'id,name,slug',
					'site_url'                 => get_site_url(),
					'purchase_key'             => Astra_Sites::get_instance()->get_license_key(),
					'only_allow_page_builders' => 'true',
				),
				trailingslashit( Astra_Sites::get_instance()->get_api_domain() ) . 'wp-json/wp/v2/astra-site-page-builder/'
			);

			$api_args = array(
				'timeout' => 30,
			);

			$page_builder_request = wp_remote_get( $url, $api_args );
			if ( ! is_wp_error( $page_builder_request ) && 200 === (int) wp_remote_retrieve_response_code( $page_builder_request ) ) {
				$page_builders = json_decode( wp_remote_retrieve_body( $page_builder_request ), true );
				if ( isset( $page_builders['code'] ) ) {
					$message = isset( $page_builders['message'] ) ? $page_builders['message'] : '';
					if ( ! empty( $message ) ) {
						astra_sites_error_log( 'HTTP Request Error: ' . $message );
					} else {
						astra_sites_error_log( 'HTTP Request Error!' );
					}
				} else {
					// @codingStandardsIgnoreStart
					// Set mini agency page builder.
					$page_builder_slugs = wp_list_pluck( $page_builders, 'slug' );
					if ( in_array( 'elementor', $page_builder_slugs ) && ! in_array( 'beaver-builder', $page_builder_slugs ) ) {
						update_option( 'astra-sites-license-page-builder', 'elementor' );
						astra_sites_error_log( 'Set "Elementor" as License Page Builder' );
					} elseif ( in_array( 'beaver-builder', $page_builder_slugs ) && ! in_array( 'elementor', $page_builder_slugs ) ) {
						update_option( 'astra-sites-license-page-builder', 'beaver-builder' );
						astra_sites_error_log( 'Set "Beaver Builder" as License Page Builder' );
					} else {
						update_option( 'astra-sites-license-page-builder', '' );
						astra_sites_error_log( 'Not Set Any License Page Builder' );
					}
					// @codingStandardsIgnoreEnd
				}
			}
		}

		/**
		 * Import Page Builders
		 *
		 * @since 2.0.0
		 * @return void
		 */
		public function import_page_builders() {
			astra_sites_error_log( 'Requesting Page Builders' );
			update_site_option( 'astra-sites-batch-status-string', 'Requesting Page Builders' );

			$purchase_key = Astra_Sites::get_instance()->get_license_key();
			$site_url     = get_site_url();

			$api_args = array(
				'timeout' => 30,
			);

			$page_builder_request = wp_remote_get( trailingslashit( Astra_Sites::get_instance()->get_api_domain() ) . 'wp-json/wp/v2/astra-site-page-builder/?_fields=id,name,slug&site_url=' . $site_url . '&purchase_key=' . $purchase_key, $api_args );
			if ( ! is_wp_error( $page_builder_request ) && 200 === (int) wp_remote_retrieve_response_code( $page_builder_request ) ) {
				$page_builders = json_decode( wp_remote_retrieve_body( $page_builder_request ), true );

				if ( isset( $page_builders['code'] ) ) {
					$message = isset( $page_builders['message'] ) ? $page_builders['message'] : '';
					if ( ! empty( $message ) ) {
						astra_sites_error_log( 'HTTP Request Error: ' . $message );
					} else {
						astra_sites_error_log( 'HTTP Request Error!' );
					}
				} else {
					update_site_option( 'astra-sites-page-builders', $page_builders );

					if ( defined( 'WP_CLI' ) ) {
						$this->generate_file( 'astra-sites-page-builders', $page_builders );
					}
				}
			}

			astra_sites_error_log( 'Page Builders Imported Successfully!' );
			update_site_option( 'astra-sites-batch-status-string', 'Page Builders Imported Successfully!' );
		}

		/**
		 * Import Blocks
		 *
		 * @since 2.0.0
		 * @param  integer $page Page number.
		 * @return void
		 */
		public function import_blocks( $page = 1 ) {

			astra_sites_error_log( 'BLOCK: -------- ACTUAL IMPORT --------' );
			$api_args   = array(
				'timeout' => 30,
			);
			$all_blocks = array();
			astra_sites_error_log( 'BLOCK: Requesting ' . $page );
			update_site_option( 'astra-blocks-batch-status-string', 'Requesting for blocks page - ' . $page );
			$response = wp_remote_get( trailingslashit( Astra_Sites::get_instance()->get_api_domain() ) . '/wp-json/astra-blocks/v1/blocks?per_page=100&page=' . $page, $api_args );
			if ( ! is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) === 200 ) {
				$astra_blocks = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( isset( $astra_blocks['code'] ) ) {
					$message = isset( $astra_blocks['message'] ) ? $astra_blocks['message'] : '';
					if ( ! empty( $message ) ) {
						astra_sites_error_log( 'HTTP Request Error: ' . $message );
					} else {
						astra_sites_error_log( 'HTTP Request Error!' );
					}
				} else {
					astra_sites_error_log( 'BLOCK: Storing data for page ' . $page . ' in option astra-blocks-' . $page );
					update_site_option( 'astra-blocks-batch-status-string', 'Storing data for page ' . $page . ' in option astra-blocks-' . $page );

					update_site_option( 'astra-blocks-' . $page, $astra_blocks );

					if ( defined( 'WP_CLI' ) ) {
						$this->generate_file( 'astra-blocks-' . $page, $astra_blocks );
					}
				}
			} else {
				astra_sites_error_log( 'BLOCK: API Error: ' . $response->get_error_message() );
			}

			astra_sites_error_log( 'BLOCK: Complete storing data for blocks ' . $page );
			update_site_option( 'astra-blocks-batch-status-string', 'Complete storing data for page ' . $page );
		}

		/**
		 * Import
		 *
		 * @since 1.0.14
		 * @since 2.0.0 Added page no.
		 *
		 * @param  integer $page Page number.
		 * @return array
		 */
		public function import_sites( $page = 1 ) {
			$api_args        = array(
				'timeout' => 30,
			);
			$sites_and_pages = array();
			astra_sites_error_log( 'Requesting ' . $page );
			update_site_option( 'astra-sites-batch-status-string', 'Requesting ' . $page );

			$query_args = apply_filters(
				'astra_sites_import_sites_query_args',
				array(
					'per_page' => 15,
					'page'     => $page,
				)
			);

			$api_url = add_query_arg( $query_args, trailingslashit( Astra_Sites::get_instance()->get_api_domain() ) . 'wp-json/astra-sites/v1/sites-and-pages/' );

			$response = wp_remote_get( $api_url, $api_args );
			if ( ! is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) === 200 ) {
				$sites_and_pages = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( isset( $sites_and_pages['code'] ) ) {
					$message = isset( $sites_and_pages['message'] ) ? $sites_and_pages['message'] : '';
					if ( ! empty( $message ) ) {
						astra_sites_error_log( 'HTTP Request Error: ' . $message );
					} else {
						astra_sites_error_log( 'HTTP Request Error!' );
					}
				} else {
					astra_sites_error_log( 'Storing data for page ' . $page . ' in option astra-sites-and-pages-page-' . $page );
					update_site_option( 'astra-sites-batch-status-string', 'Storing data for page ' . $page . ' in option astra-sites-and-pages-page-' . $page );

					update_site_option( 'astra-sites-and-pages-page-' . $page, $sites_and_pages );

					if ( defined( 'WP_CLI' ) ) {
						$this->generate_file( 'astra-sites-and-pages-page-' . $page, $sites_and_pages );
					}
				}
			} else {
				astra_sites_error_log( 'API Error: ' . $response->get_error_message() );
			}

			astra_sites_error_log( 'Complete storing data for page ' . $page );
			update_site_option( 'astra-sites-batch-status-string', 'Complete storing data for page ' . $page );

			return $sites_and_pages;
		}
	}

	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	Astra_Sites_Batch_Processing_Importer::get_instance();

endif;
