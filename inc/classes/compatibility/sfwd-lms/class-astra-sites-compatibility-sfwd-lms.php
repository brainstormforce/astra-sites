<?php
/**
 * Astra Sites Compatibility for 'LearnDash LMS'
 *
 * @see  https://www.learndash.com/
 *
 * @package Astra Sites
 * @since x.x.x
 */

if ( ! class_exists( 'Astra_Sites_Compatibility_LearnDash' ) ) :

	/**
	 * Astra_Sites_Compatibility_LearnDash
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
				self::$instance = new self;
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * @since x.x.x
		 */
		public function __construct() {
			add_filter( 'astra_sites_gutenberg_batch_process_post_types', array( $this, 'set_post_types' ) );
		}

		/**
		 * Add post types
		 *
		 * @since x.x.x
		 * @return array Post types.
		 */

		/**
		 * Set post types
		 *
		 * @since x.x.x
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
	Astra_Sites_Compatibility_LearnDash::get_instance();

endif;
