<?php
/**
 * Astra Sites Compatibility for 'SiteOrigin Widgets Bundle'
 *
 * @see  https://wordpress.org/plugins/so-widgets-bundle/
 *
 * @package Astra Sites
 * @since 1.0.0
 */

if ( ! class_exists( 'Astra_Sites_Compatibility_SO_Widgets' ) ) :

	/**
	 * Astra_Sites_Compatibility_SO_Widgets
	 *
	 * @since 1.0.0
	 */
	class Astra_Sites_Compatibility_SO_Widgets {

		/**
		 * Instance
		 *
		 * @access private
		 * @var object Class object.
		 * @since 1.0.0
		 */
		private static $instance;

		/**
		 * Initiator
		 *
		 * @since 1.0.0
		 * @return object initialized object of class.
		 */
		public static function instance() {
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
			add_action( 'astra_sites_after_plugin_activation', array( $this, 'site_origin' ), 10, 2 );
		}

		/**
		 * Update Site Origin Active Widgets
		 *
		 * @since 1.0.0
		 *
		 * @param  string $plugin_init        Plugin init file.
		 * @param  array  $data               Data.
		 * @return void
		 */
		public function site_origin( $plugin_init = '', $data = array() ) {

			if ( 'so-widgets-bundle/so-widgets-bundle.php' === $plugin_init ) {

				$data = wp_json_decode( wp_json_encode( $data ), true );

				if ( isset( $data['astra_site_options'] ) ) {

					$widgets = ( isset( $data['astra_site_options']['siteorigin_widgets_active'] ) ) ? $data['astra_site_options']['siteorigin_widgets_active'] : '';

					if ( ! empty( $widgets ) ) {
						update_option( 'siteorigin_widgets_active', $widgets );
						wp_cache_delete( 'active_widgets', 'siteorigin_widgets' );
					}
				}
			}

		}

	}

	/**
	 * Kicking this off by calling 'instance()' method
	 */
	Astra_Sites_Compatibility_SO_Widgets::instance();

endif;
