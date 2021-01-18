<?php
/**
 * Sync Library
 *
 * @package Gutenberg Templates
 * @since 1.0.0
 */

if ( ! class_exists( 'Gutenberg_Templates_Sync_Library' ) ) :

	/**
	 * Sync Library
	 *
	 * @since 1.0.0
	 */
	class Gutenberg_Templates_Sync_Library {

		/**
		 * Instance
		 *
		 * @since 1.0.0
		 * @access private
		 * @var object Class object.
		 */
		private static $instance;

		/**
		 * Initiator
		 *
		 * @since 1.0.0
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
		 * @since 1.0.0
		 */
		public function __construct() {
			add_action( 'wp_ajax_gutenberg-templates-get-sites-request-count', array( $this, 'ajax_sites_requests_count' ) );
			add_action( 'wp_ajax_gutenberg-templates-import-sites', array( $this, 'ajax_import_sites' ) );

			add_action( 'wp_ajax_gutenberg-templates-get-blocks-request-count', array( $this, 'ajax_blocks_requests_count' ) );
			add_action( 'wp_ajax_gutenberg-templates-import-blocks', array( $this, 'ajax_import_blocks' ) );
			add_action( 'wp_ajax_gutenberg-templates-check-sync-library-status', array( $this, 'check_sync_status' ) );
			add_action( 'wp_ajax_gutenberg-templates-update-sync-library-status', array( $this, 'update_library_complete' ) );
			add_action( 'admin_head', array( $this, 'start_importer' ) );
		}

		/**
		 * Start Importer
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function start_importer() {

			$is_fresh_site = get_site_option( 'gutenberg-templates-fresh-site', '' );

			// Process initially for the fresh user.
			if ( empty( $is_fresh_site ) ) {

				$dir        = GUTENBERG_TEMPLATES_DIR . 'dist/json';
				$list_files = list_files( $dir );
				if ( ! empty( $list_files ) ) {
					$list_files = array_map( 'basename', $list_files );
					foreach ( $list_files as $key => $file_name ) {
						$data = Gutenberg_Templates::get_instance()->get_filesystem()->get_contents( $dir . '/' . $file_name );
						if ( ! empty( $data ) ) {
							$option_name = str_replace( '.json', '', $file_name );
							update_site_option( $option_name, json_decode( $data, true ) );
						}
					}
				}

				update_site_option( 'gutenberg-templates-fresh-site', 'yes', 'no' );
			}
		}

		/**
		 * Update Library Complete
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function update_library_complete() {
			$this->update_latest_checksums();

			update_site_option( 'gutenberg-templates-batch-is-complete', 'no', 'no' );
			update_site_option( 'gutenberg-templates-manual-sync-complete', 'yes', 'no' );

			if ( gutenberg_templates_doing_wp_cli() ) {
				WP_CLI::line( 'Updated checksums' );
			} else {
				wp_send_json_success(
					array(
						'message' => 'Updated checksums',
						'status'  => true,
						'data'    => '',
					)
				);
			}
		}

		/**
		 * Update Library
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function check_sync_status() {

			if ( 'no' === $this->get_last_export_checksums() ) {

				if ( gutenberg_templates_doing_wp_cli() ) {
					WP_CLI::error( 'Template library refreshed!' );
				} else {
					wp_send_json_success(
						array(
							'message' => 'Updated',
							'status'  => true,
							'data'    => 'updated',
						)
					);
				}
			}

			if ( ! gutenberg_templates_doing_wp_cli() ) {
				wp_send_json_success(
					array(
						'message' => 'Complete',
						'status'  => true,
						'data'    => '',
					)
				);
			}
		}

		/**
		 * Get Last Exported Checksum Status
		 *
		 * @since 1.0.0
		 * @return string Checksums Status.
		 */
		public function get_last_export_checksums() {

			$old_last_export_checksums = get_site_option( 'gutenberg-templates-last-export-checksums', '' );

			$new_last_export_checksums = $this->set_last_export_checksums();

			$checksums_status = 'no';

			if ( empty( $old_last_export_checksums ) ) {
				$checksums_status = 'yes';
			}

			if ( $new_last_export_checksums !== $old_last_export_checksums ) {
				$checksums_status = 'yes';
			}

			return apply_filters( 'gutenberg_templates_checksums_status', $checksums_status );
		}

		/**
		 * Set Last Exported Checksum
		 *
		 * @since 1.0.0
		 * @return string Checksums Status.
		 */
		public function set_last_export_checksums() {

			if ( ! empty( $this->last_export_checksums ) ) {
				return $this->last_export_checksums;
			}

			$api_args = array(
				'timeout' => 60,
			);

			$query_args = array();

			$api_url = add_query_arg( $query_args, trailingslashit( GUTENBERG_TEMPLATES_LIBRARY_URL ) . 'wp-json/astra-sites/v1/get-last-export-checksums/' );

			$response = wp_remote_get( $api_url, $api_args );

			if ( ! is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) === 200 ) {
				$result = json_decode( wp_remote_retrieve_body( $response ), true );

				// Set last export checksums.
				if ( ! empty( $result['last_export_checksums'] ) ) {
					update_site_option( 'gutenberg-templates-last-export-checksums-latest', $result['last_export_checksums'], 'no' );

					$this->last_export_checksums = $result['last_export_checksums'];
				}
			}

			return $this->last_export_checksums;
		}

		/**
		 * Check Cron Status
		 *
		 * Gets the current cron status by performing a test spawn. Cached for one hour when all is well.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $cache Whether to use the cached result from previous calls.
		 * @return true|WP_Error Boolean true if the cron spawner is working as expected, or a WP_Error object if not.
		 */
		public function test_cron( $cache = true ) {
			global $wp_version;

			if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
				return new WP_Error( 'gutenberg_templates_cron_error', esc_html__( 'ERROR! Cron schedules are disabled by setting constant DISABLE_WP_CRON to true.<br/>To start the import process please enable the cron by setting the constant to false. E.g. define( \'DISABLE_WP_CRON\', false );', 'gutenberg-templates' ) );
			}

			if ( defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON ) {
				return new WP_Error( 'gutenberg_templates_cron_error', esc_html__( 'ERROR! Cron schedules are disabled by setting constant ALTERNATE_WP_CRON to true.<br/>To start the import process please enable the cron by setting the constant to false. E.g. define( \'ALTERNATE_WP_CRON\', false );', 'gutenberg-templates' ) );
			}

			$cached_status = get_transient( 'gutenberg-templates-cron-test-ok' );

			if ( $cache && $cached_status ) {
				return true;
			}

			$sslverify     = version_compare( $wp_version, 4.0, '<' );
			$doing_wp_cron = sprintf( '%.22F', microtime( true ) );

			$cron_request = apply_filters(
				'cron_request',
				array(
					'url'  => site_url( 'wp-cron.php?doing_wp_cron=' . $doing_wp_cron ),
					'key'  => $doing_wp_cron,
					'args' => array(
						'timeout'   => 3,
						'blocking'  => true,
						'sslverify' => apply_filters( 'https_local_ssl_verify', $sslverify ),
					),
				)
			);

			$cron_request['args']['blocking'] = true;

			$result = wp_remote_post( $cron_request['url'], $cron_request['args'] );

			if ( is_wp_error( $result ) ) {
				return $result;
			} elseif ( wp_remote_retrieve_response_code( $result ) >= 300 ) {
				return new WP_Error(
					'unexpected_http_response_code',
					sprintf(
						/* translators: 1: The HTTP response code. */
						__( 'Unexpected HTTP response code: %s', 'gutenberg-templates' ),
						intval( wp_remote_retrieve_response_code( $result ) )
					)
				);
			} else {
				set_transient( 'gutenberg-templates-cron-test-ok', 1, 3600 );
				return true;
			}

		}

		/**
		 * Update Latest Checksums
		 *
		 * Store latest checksum after batch complete.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function update_latest_checksums() {
			$latest_checksums = get_site_option( 'gutenberg-templates-last-export-checksums-latest', '' );
			update_site_option( 'gutenberg-templates-last-export-checksums', $latest_checksums, 'no' );
		}

		/**
		 * Import Sites
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function ajax_import_sites() {
			$page_no = isset( $_POST['page_no'] ) ? absint( $_POST['page_no'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( $page_no ) {
				$sites_and_pages = $this->import_sites( $page_no );
				wp_send_json_success(
					array(
						'message' => 'Success imported sites for page ' . $page_no,
						'status'  => true,
						'data'    => $sites_and_pages,
					)
				);
			}

			wp_send_json_error(
				array(
					'message' => 'Failed imported sites for page ' . $page_no,
					'status'  => false,
					'data'    => '',
				)
			);
		}

		/**
		 * Import Blocks
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function ajax_import_blocks() {
			$page_no = isset( $_POST['page_no'] ) ? absint( $_POST['page_no'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( $page_no ) {
				$sites_and_pages = $this->import_blocks( $page_no );
				wp_send_json_success(
					array(
						'message' => 'Success imported sites for page ' . $page_no,
						'status'  => true,
						'data'    => $sites_and_pages,
					)
				);
			}

			wp_send_json_error(
				array(
					'message' => 'Failed imported blocks for page ' . $page_no,
					'status'  => false,
					'data'    => '',
				)
			);
		}

		/**
		 * Blocks Requests Count
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function ajax_sites_requests_count() {
			// Get count.
			$total_requests = $this->get_total_sites_count();
			if ( $total_requests ) {
				wp_send_json_success(
					array(
						'message' => 'Success',
						'status'  => true,
						'data'    => $total_requests,
					)
				);
			}

			wp_send_json_success(
				array(
					'message' => 'Failed',
					'status'  => false,
					'data'    => $total_requests,
				)
			);
		}

		/**
		 * Blocks Requests Count
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function ajax_blocks_requests_count() {
			// Get count.
			$total_requests = $this->get_total_blocks_requests();
			if ( $total_requests ) {
				wp_send_json_success(
					array(
						'message' => 'Success',
						'status'  => true,
						'data'    => $total_requests,
					)
				);
			}

			wp_send_json_success(
				array(
					'message' => 'Failed',
					'status'  => false,
					'data'    => $total_requests,
				)
			);
		}

		/**
		 * Get Sites Total Requests
		 *
		 * @return integer
		 */
		public function get_total_sites_count() {

			gutenberg_templates_log( 'SITE: Getting Total Sites' );

			$api_args = array(
				'timeout' => 60,
			);

			$query_args = apply_filters(
				'gutenberg_templates_get_total_pages_args',
				array(
					'page_builder' => 'gutenberg',
				)
			);

			$api_url = add_query_arg( $query_args, trailingslashit( GUTENBERG_TEMPLATES_LIBRARY_URL ) . 'wp-json/astra-sites/v1/get-total-pages/' );

			gutenberg_templates_log( 'SITE: ' . $api_url );

			$response = wp_remote_get( $api_url, $api_args );

			if ( ! is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) === 200 ) {
				$total_requests = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( isset( $total_requests['pages'] ) ) {
					gutenberg_templates_log( 'SITE: Request count ' . $total_requests['pages'] );

					update_site_option( 'gutenberg-templates-site-requests', $total_requests['pages'], 'no' );

					$this->generate_file( 'gutenberg-templates-site-requests', $total_requests['pages'] );

					return $total_requests['pages'];
				}
			}

			gutenberg_templates_log( 'SITE: Request Failed! Still Calling..' );
		}

		/**
		 * Get Blocks Total Requests
		 *
		 * @return integer
		 */
		public function get_total_blocks_requests() {

			gutenberg_templates_log( 'BLOCK: Getting Total Blocks' );

			$api_args = array(
				'timeout' => 60,
			);

			$query_args = apply_filters(
				'gutenberg_templates_get_blocks_count_args',
				array(
					'page_builder' => 'gutenberg',
				)
			);

			$api_url = add_query_arg( $query_args, trailingslashit( GUTENBERG_TEMPLATES_LIBRARY_URL ) . 'wp-json/astra-blocks/v1/get-blocks-count/' );

			gutenberg_templates_log( 'BLOCK: ' . $api_url );

			$response = wp_remote_get( $api_url, $api_args );

			if ( ! is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) === 200 ) {
				$total_requests = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( isset( $total_requests['pages'] ) ) {
					gutenberg_templates_log( 'BLOCK: Requests count ' . $total_requests['pages'] );

					update_site_option( 'gutenberg-templates-block-requests', $total_requests['pages'], 'no' );

					$this->generate_file( 'gutenberg-templates-block-requests', $total_requests['pages'] );

					return $total_requests['pages'];
				}
			}

		}

		/**
		 * Import Sites
		 *
		 * @since 1.0.0
		 * @param  integer $page Page number.
		 * @return void
		 */
		public function import_sites( $page = 1 ) {

			gutenberg_templates_log( 'SITE: Importing request ' . $page . ' ..' );
			$api_args   = array(
				'timeout' => 30,
			);
			$all_blocks = array();

			$query_args = apply_filters(
				'gutenberg_templates_get_sites_and_pages_args',
				array(
					'per_page'     => 100,
					'page'         => $page,
					'page-builder' => 'gutenberg',
				)
			);

			$api_url = add_query_arg( $query_args, trailingslashit( GUTENBERG_TEMPLATES_LIBRARY_URL ) . 'wp-json/astra-sites/v1/sites-and-pages/' );

			gutenberg_templates_log( 'SITE: ' . $api_url );

			$response = wp_remote_get( $api_url, $api_args );

			if ( ! is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) === 200 ) {
				$all_blocks = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( isset( $all_blocks['code'] ) ) {
					$message = isset( $all_blocks['message'] ) ? $all_blocks['message'] : '';
					if ( ! empty( $message ) ) {
						gutenberg_templates_log( 'SITE: HTTP Request Error: ' . $message );
					} else {
						gutenberg_templates_log( 'SITE: HTTP Request Error!' );
					}
				} else {

					$option_name = 'gutenberg-templates-sites-' . $page;
					gutenberg_templates_log( 'SITE: Storing in option ' . $option_name );

					update_site_option( $option_name, $all_blocks, 'no' );

					if ( gutenberg_templates_doing_wp_cli() ) {
						gutenberg_templates_log( 'SITE: Generating ' . $option_name . '.json file' );
						$this->generate_file( $option_name, $all_blocks );
					}
				}
			} else {
				gutenberg_templates_log( 'SITE: API Error: ' . $response->get_error_message() );
			}

			gutenberg_templates_log( 'SITE: Completed request ' . $page );
		}

		/**
		 * Import Blocks
		 *
		 * @since 1.0.0
		 * @param  integer $page Page number.
		 * @return void
		 */
		public function import_blocks( $page = 1 ) {

			gutenberg_templates_log( 'BLOCK: Importing request ' . $page . ' ..' );
			$api_args   = array(
				'timeout' => 30,
			);
			$all_blocks = array();

			$query_args = apply_filters(
				'gutenberg_templates_blocks_args',
				array(
					'per_page'     => 100,
					'page'         => $page,
					'page_builder' => 'gutenberg',
				)
			);

			$api_url = add_query_arg( $query_args, trailingslashit( GUTENBERG_TEMPLATES_LIBRARY_URL ) . 'wp-json/astra-blocks/v1/blocks/' );

			gutenberg_templates_log( 'BLOCK: ' . $api_url );

			$response = wp_remote_get( $api_url, $api_args );

			if ( ! is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) === 200 ) {
				$all_blocks = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( isset( $all_blocks['code'] ) ) {
					$message = isset( $all_blocks['message'] ) ? $all_blocks['message'] : '';
					if ( ! empty( $message ) ) {
						gutenberg_templates_log( 'BLOCK: HTTP Request Error: ' . $message );
					} else {
						gutenberg_templates_log( 'BLOCK: HTTP Request Error!' );
					}
				} else {
					$option_name = 'gutenberg-templates-blocks-' . $page;
					gutenberg_templates_log( 'BLOCK: Storing in option ' . $option_name );

					update_site_option( $option_name, $all_blocks, 'no' );

					if ( gutenberg_templates_doing_wp_cli() ) {
						gutenberg_templates_log( 'BLOCK: Genearting ' . $option_name . '.json file' );
						$this->generate_file( $option_name, $all_blocks );
					}
				}
			} else {
				gutenberg_templates_log( 'BLOCK: API Error: ' . $response->get_error_message() );
			}

			gutenberg_templates_log( 'BLOCK: Completed request ' . $page );
		}

		/**
		 * Generate JSON file.
		 *
		 * @since 1.0.0
		 *
		 * @param  string $filename File name.
		 * @param  array  $data     JSON file data.
		 * @return void.
		 */
		public function generate_file( $filename = '', $data = array() ) {
			if ( gutenberg_templates_doing_wp_cli() ) {
				$this->get_filesystem()->put_contents( GUTENBERG_TEMPLATES_DIR . 'dist/json/' . $filename . '.json', wp_json_encode( $data ) );
			}
		}

		/**
		 * Get an instance of WP_Filesystem_Direct.
		 *
		 * @since 1.0.0
		 * @return object A WP_Filesystem_Direct instance.
		 */
		public function get_filesystem() {
			global $wp_filesystem;

			require_once ABSPATH . '/wp-admin/includes/file.php';

			WP_Filesystem();

			return $wp_filesystem;
		}

	}

	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	Gutenberg_Templates_Sync_Library::get_instance();

endif;
