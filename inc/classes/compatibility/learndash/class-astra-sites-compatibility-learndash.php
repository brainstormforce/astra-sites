<?php
/**
 * Compatibility for 'LearnDash'
 *
 * @see  https://wordpress.org/plugins/astra-pro/
 *
 * @package Astra Sites
 * @since x.x.x
 */

// If LearnDash is not defined then return false.
if ( ! defined( 'LEARNDASH_COURSE_GRID_VERSION' ) ) {
	return;
}

if ( ! class_exists( 'Astra_Sites_Compatibility_LearnDash' ) ) :

	/**
	 * Astra Sites Compatibility LearnDash
	 *
	 * @since x.x.x
	 */
	class Astra_Sites_Compatibility_LearnDash {

		/**
		 * Instance
		 *
		 * @access private
		 * @var object Class object.
		 * @since x.x.x
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
			add_filter( 'astra_sites_pre_process_post_disable_content', '__return_false' );
		}
	}

	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	Astra_Sites_Compatibility_LearnDash::get_instance();

endif;
