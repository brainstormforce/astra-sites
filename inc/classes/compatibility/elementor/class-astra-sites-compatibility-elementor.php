<?php
/**
 * Astra Sites Elementor
 *
 * @package Astra Sites
 * @since 1.0.11
 */

if ( ! class_exists( 'Astra_Sites_Compatibility_Elementor' ) ) :

	/**
	 * Astra_Sites_Compatibility_Elementor
	 *
	 * @since 1.0.11
	 */
	class Astra_Sites_Compatibility_Elementor {

		/**
		 * All Processes.
		 *
		 * @since 1.0.11
		 *
		 * @access protected
		 * @var $process_all
		 */
		protected $process_all;

		/**
		 * Instance
		 *
		 * @since 1.0.11
		 *
		 * @access private
		 * @var object Class object.
		 */
		private static $instance;

		/**
		 * Initiator
		 *
		 * @since 1.0.11
		 * @return object initialized object of class.
		 */
		public static function set_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self;
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * @since 1.0.11
		 */
		public function __construct() {

			if ( ini_get( 'allow_url_fopen' ) ) {

				require_once ABSPATH . 'wp-admin/includes/image.php';

				// Remote Source extends Elementor Remote API.
				require_once ASTRA_SITES_DIR . 'inc/classes/compatibility/elementor/class-astra-sites-source-remote.php';

				// Background Processing.
				require_once ASTRA_SITES_DIR . 'inc/classes/compatibility/elementor/background-processing/class-astra-elementor-image-importer-process.php';

				$this->process_all = new Astra_Elementor_Image_Importer_Process();

				// Start image importing after site import complete.
				add_action( 'astra_sites_import_complete', array( $this, 'start_image_import' ) );

			}

		}

		/**
		 * Start Image Import
		 *
		 * @since 1.0.11
		 *
		 * @param  array $data Site API Data.
		 * @return void
		 */
		public function start_image_import( $data ) {

			// Have Required Plugins?
			if ( array_key_exists( 'required-plugins', $data ) ) {

				// Have Elementor Plugin?
				// Yes, Then proceed!
				if ( in_array( 'elementor', array_column( $data['required-plugins'], 'slug' ) ) ) {

					$page_ids = $this->get_pages();

					if ( is_array( $page_ids ) ) {
						foreach ( $page_ids as $page_id ) {
							error_log( $page_id );
							$this->process_all->push_to_queue( $page_id );
						}
						$this->process_all->save()->dispatch();
					}
				}
			}
		}

		/**
		 * Get Page IDs
		 *
		 * @since 1.0.11
		 *
		 * @return array
		 */
		protected function get_pages() {

			$args = array(
				'post_type'     => 'page',

				// Query performance optimization.
				'fields'        => 'ids',
				'no_found_rows' => true,
				'post_status'   => 'publish',
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
	 * Kicking this off by calling 'set_instance()' method
	 */
	Astra_Sites_Compatibility_Elementor::set_instance();

endif;
