<?php
/**
 * Astra Sites Compatibility for 'LearnDash LMS'
 *
 * @see  https://www.learndash.com/
 *
 * @package Astra Sites
 * @since 1.3.13
 */

if ( ! class_exists( 'Astra_Sites_Compatibility_SFWD_LMS' ) ) :

	/**
	 * Astra_Sites_Compatibility_SFWD_LMS
	 *
	 * @since 1.3.13
	 */
	class Astra_Sites_Compatibility_SFWD_LMS {

		/**
		 * Instance
		 *
		 * @access private
		 * @var object Class object.
		 * @since 1.3.13
		 */
		private static $instance;

		/**
		 * Initiator
		 *
		 * @since 1.3.13
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
		 * @since 1.3.13
		 */
		public function __construct() {
			add_filter( 'astra_sites_gutenberg_batch_process_post_types', array( $this, 'set_post_types' ) );
		}

		/**
		 * Add post types
		 *
		 * @since 1.3.13
		 * @return array Post types.
		 */

		/**
		 * Set post types
		 *
		 * @since 1.3.13
		 *
		 * @param array $post_types Post types.
		 */
		function set_post_types( $post_types = array() ) {
			return array_merge( $post_types, array( 'sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz', 'sfwd-certificates', 'sfwd-assignment' ) );
		}
	}

	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	Astra_Sites_Compatibility_SFWD_LMS::get_instance();

endif;
