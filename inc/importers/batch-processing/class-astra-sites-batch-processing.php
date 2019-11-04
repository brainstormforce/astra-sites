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

			// Prepare Widgets.
			require_once ASTRA_SITES_DIR . 'inc/importers/batch-processing/class-astra-sites-batch-processing-widgets.php';

			// Prepare Page Builders.
			require_once ASTRA_SITES_DIR . 'inc/importers/batch-processing/class-astra-sites-batch-processing-beaver-builder.php';
			require_once ASTRA_SITES_DIR . 'inc/importers/batch-processing/class-astra-sites-batch-processing-elementor.php';
			require_once ASTRA_SITES_DIR . 'inc/importers/batch-processing/class-astra-sites-batch-processing-gutenberg.php';
			require_once ASTRA_SITES_DIR . 'inc/importers/batch-processing/class-astra-sites-batch-processing-brizy.php';

			// Prepare Misc.
			require_once ASTRA_SITES_DIR . 'inc/importers/batch-processing/class-astra-sites-batch-processing-misc.php';

			self::$process_all = new WP_Background_Process_Astra();

			// Start image importing after site import complete.
			add_filter( 'astra_sites_image_importer_skip_image', array( $this, 'skip_image' ), 10, 2 );
			add_action( 'astra_sites_import_complete', array( $this, 'start_process' ) );
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
