<?php
/**
 * Batch Processing
 *
 * @package Astra Sites
 * @since 1.0.14
 */

if ( ! class_exists( 'Astra_Sites_Batch_Processing' ) ) :

	/**
	 * Astra_Sites_Batch_Processing
	 *
	 * @since 1.0.14
	 */
	class Astra_Sites_Batch_Processing {

		/**
		 * Instance
		 *
		 * @since 1.0.14
		 * @var object Class object.
		 * @access private
		 */
		private static $instance;

		/**
		 * Process All
		 *
		 * @since 1.0.14
		 * @var object Class object.
		 * @access public
		 */
		public static $process_all;

		/**
		 * Sites Importer
		 *
		 * @since 2.0.0
		 * @var object Class object.
		 * @access public
		 */
		public static $process_site_importer;

		/**
		 * Process Single Page
		 *
		 * @since 2.0.0
		 * @var object Class object.
		 * @access public
		 */
		public static $process_single;

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

			// Core Helpers - Image.
			// @todo 	This file is required for Elementor.
			// Once we implement our logic for updating elementor data then we'll delete this file.
			require_once ABSPATH . 'wp-admin/includes/image.php';

			// Core Helpers - Image Downloader.
			require_once ASTRA_SITES_DIR . 'inc/importers/batch-processing/helpers/class-astra-sites-image-importer.php';

			// Core Helpers - Batch Processing.
			require_once ASTRA_SITES_DIR . 'inc/importers/batch-processing/helpers/class-wp-async-request.php';
			require_once ASTRA_SITES_DIR . 'inc/importers/batch-processing/helpers/class-wp-background-process.php';
			require_once ASTRA_SITES_DIR . 'inc/importers/batch-processing/helpers/class-wp-background-process-astra.php';
			require_once ASTRA_SITES_DIR . 'inc/importers/batch-processing/helpers/class-wp-background-process-astra-single.php';
			require_once ASTRA_SITES_DIR . 'inc/importers/batch-processing/helpers/class-wp-background-process-astra-site-importer.php';

			// Prepare Widgets.
			require_once ASTRA_SITES_DIR . 'inc/importers/batch-processing/class-astra-sites-batch-processing-widgets.php';

			// Prepare Page Builders.
			require_once ASTRA_SITES_DIR . 'inc/importers/batch-processing/class-astra-sites-batch-processing-beaver-builder.php';
			require_once ASTRA_SITES_DIR . 'inc/importers/batch-processing/class-astra-sites-batch-processing-elementor.php';
			require_once ASTRA_SITES_DIR . 'inc/importers/batch-processing/class-astra-sites-batch-processing-gutenberg.php';
			require_once ASTRA_SITES_DIR . 'inc/importers/batch-processing/class-astra-sites-batch-processing-brizy.php';

			// Prepare Misc.
			require_once ASTRA_SITES_DIR . 'inc/importers/batch-processing/class-astra-sites-batch-processing-misc.php';

			// Process Importer.
			require_once ASTRA_SITES_DIR . 'inc/importers/batch-processing/class-astra-sites-batch-processing-importer.php';

			self::$process_all           = new WP_Background_Process_Astra();
			self::$process_single        = new WP_Background_Process_Astra_Single();
			self::$process_site_importer = new WP_Background_Process_Astra_Site_Importer();

			// Start image importing after site import complete.
			add_filter( 'astra_sites_image_importer_skip_image', array( $this, 'skip_image' ), 10, 2 );
			add_action( 'astra_sites_import_complete', array( $this, 'start_process' ) );
			add_action( 'astra_sites_process_single', array( $this, 'start_process_single' ) );
			add_filter( 'http_request_timeout', array( $this, 'set_http_timeout' ), 10, 2 );
			add_action( 'admin_head', array( $this, 'start_importer' ) );
			add_action( 'wp_ajax_astra-sites-update-library', array( $this, 'update_library' ) );
			add_action( 'wp_ajax_astra-sites-update-library-complete', array( $this, 'update_library_complete' ) );
			add_action( 'wp_ajax_astra-sites-import-categories', array( $this, 'import_categories' ) );
			add_action( 'wp_ajax_astra-sites-import-page-builders', array( $this, 'import_page_builders' ) );
			add_action( 'wp_ajax_astra-sites-import-blocks', array( $this, 'import_blocks' ) );
			add_action( 'wp_ajax_astra-sites-get-sites-request-count', array( $this, 'sites_requests_count' ) );
			add_action( 'wp_ajax_astra-sites-import-sites', array( $this, 'import_sites' ) );
		}

		/**
		 * Import Categories
		 *
		 * @since 2.0.0
		 * @return void
		 */
		function import_categories() {
			Astra_Sites_Batch_Processing_Importer::get_instance()->import_categories();
			wp_send_json_success();
		}

		/**
		 * Import Page Builders
		 *
		 * @since 2.0.0
		 * @return void
		 */
		function import_page_builders() {
			Astra_Sites_Batch_Processing_Importer::get_instance()->import_page_builders();
			wp_send_json_success();
		}

		/**
		 * Import Blocks
		 *
		 * @since 2.0.0
		 * @return void
		 */
		function import_blocks() {
			Astra_Sites_Batch_Processing_Importer::get_instance()->import_blocks();
			wp_send_json_success();
		}

		/**
		 * Import Sites
		 *
		 * @since 2.0.0
		 * @return void
		 */
		function import_sites() {
			$page_no = isset( $_POST['page_no'] ) ? absint( $_POST['page_no'] ) : '';
			if ( $page_no ) {
				$sites_and_pages = Astra_Sites_Batch_Processing_Importer::get_instance()->import_sites( $page_no );

				$page_builder_keys    = wp_list_pluck( $sites_and_pages, 'astra-site-page-builder' );
				$default_page_builder = Astra_Sites_Page::get_instance()->get_setting( 'page_builder' );

				$current_page_builder_sites = array();
				foreach ( $page_builder_keys as $site_id => $page_builder ) {
					if ( $default_page_builder === $page_builder ) {
						$current_page_builder_sites[ $site_id ] = $sites_and_pages[ $site_id ];
					}
				}

				wp_send_json_success( $current_page_builder_sites );
			}

			wp_send_json_error();
		}

		/**
		 * Sites Requests Count
		 *
		 * @since 2.0.0
		 * @return void
		 */
		function sites_requests_count() {

			// Get count.
			$total_requests = $this->get_total_requests();
			if ( $total_requests ) {
				wp_send_json_success( $total_requests );
			}

			wp_send_json_error();
		}

		/**
		 * Update Library Complete
		 *
		 * @since 2.0.0
		 * @return void
		 */
		function update_library_complete() {
			update_option( 'astra-sites-batch-is-complete', 'no' );
			wp_send_json_success();
		}

		/**
		 * Update Library
		 *
		 * @since 2.0.0
		 * @return void
		 */
		function update_library() {
			$status = Astra_Sites_Page::get_instance()::test_cron();
			if ( is_wp_error( $status ) ) {
				$import_with = 'ajax';
			} else {
				$import_with = 'batch';
				// Process import.
				$this->process_batch();
			}

			wp_send_json_success( $import_with );
		}

		/**
		 * Start Importer
		 *
		 * @since 2.0.0
		 * @return void
		 */
		function start_importer() {

			$is_fresh_user = get_option( 'astra-sites-fresh-user', 'no' );

			// Process initially for the fresh user.
			if ( isset( $_GET['reset'] ) ) {

				// Process import.
				$this->process_batch();

			} elseif ( 'no' === $is_fresh_user ) {

				// Process import.
				$this->process_batch();

				update_option( 'astra-sites-fresh-user', 'yes' );

				// If not fresh user then trigger batch import on the transient and option
				// Only on the Astra Sites page.
			} elseif ( isset( get_current_screen()->id ) && 'appearance_page_astra-sites' === get_current_screen()->id ) {

				// Process import.
				$this->process_import();
			}
		}

		/**
		 * Process Batch
		 *
		 * @since 2.0.0
		 * @return mixed
		 */
		function process_batch() {

			$status = Astra_Sites_Page::get_instance()::test_cron();
			if ( is_wp_error( $status ) ) {
				error_log( 'Error! Batch Not Start due to disabled cron events!' );
				update_option( 'astra-sites-batch-status-string', 'Error! Batch Not Start due to disabled cron events!' );
				return;
			}

			error_log( 'Batch Started!' );
			update_option( 'astra-sites-batch-status-string', 'Batch Started!' );

			// Added the categories.
			error_log( 'Added Categories in queue.' );
			update_option( 'astra-sites-batch-status-string', 'Added Categories in queue.' );
			self::$process_site_importer->push_to_queue(
				array(
					'instance' => Astra_Sites_Batch_Processing_Importer::get_instance(),
					'method'   => 'import_categories',
				)
			);

			// Added the page_builders.
			error_log( 'Added page builders in queue.' );
			update_option( 'astra-sites-batch-status-string', 'Added page_builders in queue.' );
			self::$process_site_importer->push_to_queue(
				array(
					'instance' => Astra_Sites_Batch_Processing_Importer::get_instance(),
					'method'   => 'import_page_builders',
				)
			);

			// Get count.
			$total_requests = $this->get_total_blocks_requests();
			if ( $total_requests ) {
				error_log( 'BLOCK: Total Blocks Requests ' . $total_requests );
				update_option( 'astra-sites-batch-status-string', 'Total Blocks Requests ' . $total_requests );
				for ( $page = 1; $page <= $total_requests; $page++ ) {

					error_log( 'BLOCK: Added page ' . $page . ' in queue.' );
					update_option( 'astra-sites-batch-status-string', 'Added page ' . $page . ' in queue.' );
					self::$process_site_importer->push_to_queue(
						array(
							'page'     => $page,
							'instance' => Astra_Sites_Batch_Processing_Importer::get_instance(),
							'method'   => 'import_blocks',
						)
					);
				}
			}

			// Get count.
			$total_requests = $this->get_total_requests();
			if ( $total_requests ) {
				error_log( 'Total Requests ' . $total_requests );
				update_option( 'astra-sites-batch-status-string', 'Total Requests ' . $total_requests );
				for ( $page = 1; $page <= $total_requests; $page++ ) {

					error_log( 'Added page ' . $page . ' in queue.' );
					update_option( 'astra-sites-batch-status-string', 'Added page ' . $page . ' in queue.' );
					self::$process_site_importer->push_to_queue(
						array(
							'page'     => $page,
							'instance' => Astra_Sites_Batch_Processing_Importer::get_instance(),
							'method'   => 'import_sites',
						)
					);
				}
			}

			error_log( 'Dispatch the Queue!' );
			update_option( 'astra-sites-batch-status-string', 'Dispatch the Queue!' );

			// Dispatch Queue.
			self::$process_site_importer->save()->dispatch();
		}

		/**
		 * Process Import
		 *
		 * @since 2.0.0
		 *
		 * @return mixed Null if process is already started.
		 */
		function process_import() {

			// Batch is already started? Then return.
			$status  = get_option( 'astra-sites-batch-status' );
			$expired = get_transient( 'astra-sites-import-check' );
			if ( 'in-process' === $status ) {
				return;
			}

			// Check batch expiry.
			$expired = get_transient( 'astra-sites-import-check' );
			if ( false !== $expired ) {
				return;
			}

			// For 1 hour.
			set_transient( 'astra-sites-import-check', 'true', WEEK_IN_SECONDS );

			update_option( 'astra-sites-batch-status', 'in-process' );

			// Process batch.
			$this->process_batch();
		}

		/**
		 * Get Total Requests
		 *
		 * @return integer
		 */
		function get_total_requests() {

			error_log( 'Getting Total Pages' );
			update_option( 'astra-sites-batch-status-string', 'Getting Total Pages' );

			$api_args = array(
				'timeout' => 60,
			);

			$response = wp_remote_get( trailingslashit( Astra_Sites::get_instance()->get_api_domain() ) . '/wp-json/astra-sites/v1/get-total-pages/?per_page=15', $api_args );
			if ( ! is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) === 200 ) {
				$total_requests = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( isset( $total_requests['pages'] ) ) {
					error_log( 'Updated requests ' . $total_requests['pages'] );
					update_option( 'astra-sites-batch-status-string', 'Updated requests ' . $total_requests['pages'] );
					update_option( 'astra-sites-requests', $total_requests['pages'] );

					return $total_requests['pages'];
				}
			}

			error_log( 'Request Failed! Still Calling..' );
			update_option( 'astra-sites-batch-status-string', 'Request Failed! Still Calling..' );

			$this->get_total_requests();
		}

		/**
		 * Get Blocks Total Requests
		 *
		 * @return integer
		 */
		function get_total_blocks_requests() {

			error_log( 'BLOCK: Getting Total Blocks' );
			update_option( 'astra-sites-batch-status-string', 'Getting Total Blocks' );

			$api_args = array(
				'timeout' => 60,
			);

			$response = wp_remote_get( trailingslashit( Astra_Sites::get_instance()->get_api_domain() ) . '/wp-json/astra-blocks/v1/get-total-blocks', $api_args );
			if ( ! is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) === 200 ) {
				$total_requests = json_decode( wp_remote_retrieve_body( $response ), true );
				error_log( 'BLOCK: Updated requests ' . $total_requests );
				update_option( 'astra-blocks-batch-status-string', 'Updated requests ' . $total_requests );

				update_option( 'astra-blocks-requests', $total_requests );

				return $total_requests;
			}

			error_log( 'BLOCK: Request Failed! Still Calling..' );
			update_option( 'astra-blocks-batch-status-string', 'Request Failed! Still Calling..' );

			$this->get_total_blocks_requests();
		}

		/**
		 * Start Single Page Import
		 *
		 * @param  int $page_id Page ID .
		 * @since 2.0.0
		 * @return void
		 */
		public function start_process_single( $page_id ) {

			error_log( '=================== ' . Astra_Sites_White_Label::get_instance()->page_title( ASTRA_SITES_NAME ) . ' - Single Page - Importing Images for Blog name \'' . get_the_title( $page_id ) . '\' (' . $page_id . ') ===================' );

			$default_page_builder = Astra_Sites_Page::get_instance()->get_setting( 'page_builder' );

			if ( 'gutenberg' === $default_page_builder ) {
				// Add "gutenberg" in import [queue].
				self::$process_single->push_to_queue(
					array(
						'page_id'  => $page_id,
						'instance' => Astra_Sites_Batch_Processing_Gutenberg::get_instance(),
					)
				);
			}

			// Add "brizy" in import [queue].
			if ( 'brizy' === $default_page_builder && is_plugin_active( 'brizy/brizy.php' ) ) {
				// Add "gutenberg" in import [queue].
				self::$process_single->push_to_queue(
					array(
						'page_id'  => $page_id,
						'instance' => Astra_Sites_Batch_Processing_Brizy::get_instance(),
					)
				);
			}

			// Add "bb-plugin" in import [queue].
			if (
				'beaver-builder' === $default_page_builder &&
				( is_plugin_active( 'beaver-builder-lite-version/fl-builder.php' ) || is_plugin_active( 'bb-plugin/fl-builder.php' ) )
			) {
				// Add "gutenberg" in import [queue].
				self::$process_single->push_to_queue(
					array(
						'page_id'  => $page_id,
						'instance' => Astra_Sites_Batch_Processing_Beaver_Builder::get_instance(),
					)
				);
			}

			// Add "elementor" in import [queue].
			if ( 'elementor' === $default_page_builder ) {
				// @todo Remove required `allow_url_fopen` support.
				if ( ini_get( 'allow_url_fopen' ) ) {
					if ( is_plugin_active( 'elementor/elementor.php' ) ) {

						// !important, Clear the cache after images import.
						\Elementor\Plugin::$instance->posts_css_manager->clear_cache();

						$import = new \Elementor\TemplateLibrary\Astra_Sites_Batch_Processing_Elementor();
						self::$process_single->push_to_queue(
							array(
								'page_id'  => $page_id,
								'instance' => $import,
							)
						);
					}
				} else {
					error_log( 'Couldn\'t not import image due to allow_url_fopen() is disabled!' );
				}
			}

			// Dispatch Queue.
			self::$process_single->save()->dispatch();
		}

		/**
		 * Set the timeout for the HTTP request for the images which serve from domain `websitedemos.net`.
		 *
		 * @since 1.3.10
		 *
		 * @param int    $default Time in seconds until a request times out. Default 5.
		 * @param string $url           The request URL.
		 */
		function set_http_timeout( $default, $url ) {

			if ( strpos( $url, 'websitedemos.net' ) === false ) {
				return $default;
			}

			if ( Astra_Sites_Image_Importer::get_instance()->is_image_url( $url ) ) {
				$default = 30;
			}

			return $default;
		}

		/**
		 * Skip Image from Batch Processing.
		 *
		 * @since 1.0.14
		 *
		 * @param  boolean $can_process Batch process image status.
		 * @param  array   $attachment  Batch process image input.
		 * @return boolean
		 */
		function skip_image( $can_process, $attachment ) {

			if ( isset( $attachment['url'] ) && ! empty( $attachment['url'] ) ) {
				if (
					strpos( $attachment['url'], 'brainstormforce.com' ) !== false ||
					strpos( $attachment['url'], 'wpastra.com' ) !== false ||
					strpos( $attachment['url'], 'sharkz.in' ) !== false ||
					strpos( $attachment['url'], 'websitedemos.net' ) !== false
				) {
					return false;
				}
			}

			return true;
		}

		/**
		 * Start Image Import
		 *
		 * @since 1.0.14
		 *
		 * @return void
		 */
		public function start_process() {

			Astra_Sites_Importer_Log::add( 'Batch Process Started!' );
			Astra_Sites_Importer_Log::add( Astra_Sites_White_Label::get_instance()->page_title( ASTRA_SITES_NAME ) . ' - Importing Images for Blog name \'' . get_bloginfo( 'name' ) . '\' (' . get_current_blog_id() . ')' );

			// Add "widget" in import [queue].
			if ( class_exists( 'Astra_Sites_Batch_Processing_Widgets' ) ) {
				self::$process_all->push_to_queue( Astra_Sites_Batch_Processing_Widgets::get_instance() );
			}

			// Add "gutenberg" in import [queue].
			self::$process_all->push_to_queue( Astra_Sites_Batch_Processing_Gutenberg::get_instance() );

			// Add "brizy" in import [queue].
			if ( is_plugin_active( 'brizy/brizy.php' ) ) {
				self::$process_all->push_to_queue( Astra_Sites_Batch_Processing_Brizy::get_instance() );
			}

			// Add "bb-plugin" in import [queue].
			// Add "beaver-builder-lite-version" in import [queue].
			if ( is_plugin_active( 'beaver-builder-lite-version/fl-builder.php' ) || is_plugin_active( 'bb-plugin/fl-builder.php' ) ) {
				self::$process_all->push_to_queue( Astra_Sites_Batch_Processing_Beaver_Builder::get_instance() );
			}

			// Add "elementor" in import [queue].
			// @todo Remove required `allow_url_fopen` support.
			if ( ini_get( 'allow_url_fopen' ) ) {
				if ( is_plugin_active( 'elementor/elementor.php' ) ) {
					$import = new \Elementor\TemplateLibrary\Astra_Sites_Batch_Processing_Elementor();
					self::$process_all->push_to_queue( $import );
				}
			} else {
				Astra_Sites_Importer_Log::add( 'Couldn\'t not import image due to allow_url_fopen() is disabled!' );
			}

			// Add "astra-addon" in import [queue].
			if ( is_plugin_active( 'astra-addon/astra-addon.php' ) ) {
				if ( class_exists( 'Astra_Sites_Compatibility_Astra_Pro' ) ) {
					self::$process_all->push_to_queue( Astra_Sites_Compatibility_Astra_Pro::get_instance() );
				}
			}

			// Add "misc" in import [queue].
			self::$process_all->push_to_queue( Astra_Sites_Batch_Processing_Misc::get_instance() );

			// Dispatch Queue.
			self::$process_all->save()->dispatch();
		}

		/**
		 * Get all post id's
		 *
		 * @since 1.0.14
		 *
		 * @param  array $post_types Post types.
		 * @return array
		 */
		public static function get_pages( $post_types = array() ) {

			if ( $post_types ) {
				$args = array(
					'post_type'      => $post_types,

					// Query performance optimization.
					'fields'         => 'ids',
					'no_found_rows'  => true,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
				);

				$query = new WP_Query( $args );

				// Have posts?
				if ( $query->have_posts() ) :

					return $query->posts;

				endif;
			}

			return null;
		}

		/**
		 * Get Supporting Post Types..
		 *
		 * @since 1.3.7
		 * @param  integer $feature Feature.
		 * @return array
		 */
		public static function get_post_types_supporting( $feature ) {
			global $_wp_post_type_features;

			$post_types = array_keys(
				wp_filter_object_list( $_wp_post_type_features, array( $feature => true ) )
			);

			return $post_types;
		}

	}

	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	Astra_Sites_Batch_Processing::get_instance();

endif;
