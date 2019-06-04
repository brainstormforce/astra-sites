<?php
/**
 * Astra Sites Compatibility for 3rd party plugins.
 *
 * @package Astra Sites
 * @since 1.0.11
 */

if ( ! class_exists( 'Astra_Sites_Compatibility' ) ) :

	/**
	 * Astra Sites Compatibility
	 *
	 * @since 1.0.11
	 */
	class Astra_Sites_Compatibility {

		/**
		 * Instance
		 *
		 * @access private
		 * @var object Class object.
		 * @since 1.0.11
		 */
		private static $instance;

		/**
		 * Initiator
		 *
		 * @since 1.0.11
		 * @return object initialized object of class.
		 */
		public static function instance() {
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

			// Plugin - Astra Pro.
			require_once ASTRA_SITES_DIR . 'inc/classes/compatibility/astra-pro/class-astra-sites-compatibility-astra-pro.php';

			// Plugin - Site Origin Widgets.
			require_once ASTRA_SITES_DIR . 'inc/classes/compatibility/so-widgets-bundle/class-astra-sites-compatibility-so-widgets.php';

			// Plugin - WooCommerce.
			require_once ASTRA_SITES_DIR . 'inc/classes/compatibility/woocommerce/class-astra-sites-compatibility-woocommerce.php';

			// Plugin - LearnDash LMS.
			require_once ASTRA_SITES_DIR . 'inc/classes/compatibility/sfwd-lms/class-astra-sites-compatibility-sfwd-lms.php';
		}

	}

	/**
	 * Kicking this off by calling 'instance()' method
	 */
	Astra_Sites_Compatibility::instance();

endif;


