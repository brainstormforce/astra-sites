<?php
/**
 * Batch Import
 *
 * @package Astra Sites
 * @since x.x.x
 */

if ( ! class_exists( 'Astra_Sites_Batch_Import' ) ) :

	/**
	 * Batch Import
	 *
	 * @since x.x.x
	 */
	class Astra_Sites_Batch_Import {

		/**
		 * Instance
		 *
		 * @since x.x.x
		 * @var object Class object.
		 * @access private
		 */
		private static $instance;

		/**
		 * Initiator
		 *
		 * @since x.x.x
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
		 * @since x.x.x
		 */
		public function __construct() {

			// WP Core Files.
			require_once ABSPATH . 'wp-admin/includes/image.php';

			// Image Downloader.
			require_once ASTRA_SITES_DIR . 'inc/importers/batch-processing/helpers/class-astra-sites-image-importer.php';

			// Batch Processing.
			require_once ASTRA_SITES_DIR . 'inc/importers/batch-processing/helpers/class-wp-async-request.php';
			require_once ASTRA_SITES_DIR . 'inc/importers/batch-processing/helpers/class-wp-background-process.php';

			// Site Import Batch.
			require_once ASTRA_SITES_DIR . 'inc/classes/batch-import/class-astra-sites-batch-site-import.php';
		}
	}

	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	Astra_Sites_Batch_Import::get_instance();

endif;
