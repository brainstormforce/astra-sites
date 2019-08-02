<?php
/**
 * Astra Pro Sites White Label
 *
 * @package Astra Pro Sites
 * @since 1.0.0
 */

if ( ! class_exists( 'Astra_Pro_Sites_White_Label' ) ) :

	/**
	 * Astra_Pro_Sites_White_Label
	 *
	 * @since 1.0.0
	 */
	class Astra_Pro_Sites_White_Label {

		/**
		 * Instance
		 *
		 * @since 1.0.0
		 * @var object Class Object.
		 * @access private
		 */
		private static $instance;

		/**
		 * Member Variable
		 *
		 * @since 1.0.0
		 * @var array branding
		 * @access private
		 */
		private static $branding;

		/**
		 * Initiator
		 *
		 * @since 1.0.0
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
		 * @since 1.0.0
		 */
		public function __construct() {

			add_filter( 'all_plugins', array( $this, 'plugins_page' ) );

		}

		/**
		 * Get value of single key from option array.
		 *
		 * @since 1.0.0
		 * @param  string $type Option type.
		 * @param  string $key  Option key.
		 * @param  string $default  Default value if key not found.
		 * @return mixed        Return stored option value.
		 */
		public static function get_option( $type = '', $key = '', $default = null ) {

			if ( ! is_callable( 'Astra_Ext_White_Label_Markup::get_white_label' ) ) {
				return $default;
			}

			$value = Astra_Ext_White_Label_Markup::get_white_label( $type, $key );
			if ( ! empty( $value ) ) {
				return $value;
			}

			return $default;

		}

		/**
		 * White labels the plugins page.
		 *
		 * @param array $plugins Plugins Array.
		 * @return array
		 */
		function plugins_page( $plugins ) {

			if ( ! is_callable( 'Astra_Ext_White_Label_Markup::get_white_label' ) ) {
				return $plugins;
			}

			if ( ! isset( $plugins[ ASTRA_PRO_SITES_BASE ] ) ) {
				return $plugins;
			}

			// Set White Labels.
			$name        = self::get_option( 'astra-sites', 'name' );
			$description = self::get_option( 'astra-sites', 'description' );
			$author      = self::get_option( 'astra-agency', 'author' );
			$author_uri  = self::get_option( 'astra-agency', 'author_url' );

			if ( ! empty( $name ) ) {
				$plugins[ ASTRA_PRO_SITES_BASE ]['Name'] = $name;

				// Remove Plugin URI if Agency White Label name is set.
				$plugins[ ASTRA_PRO_SITES_BASE ]['PluginURI'] = '';
			}

			if ( ! empty( $description ) ) {
				$plugins[ ASTRA_PRO_SITES_BASE ]['Description'] = $description;
			}

			if ( ! empty( $author ) ) {
				$plugins[ ASTRA_PRO_SITES_BASE ]['Author'] = $author;
			}

			if ( ! empty( $author_uri ) ) {
				$plugins[ ASTRA_PRO_SITES_BASE ]['AuthorURI'] = $author_uri;
			}

			return $plugins;
		}

	}

	/**
	 * Kicking this off by calling 'set_instance()' method
	 */
	Astra_Pro_Sites_White_Label::set_instance();

endif;
