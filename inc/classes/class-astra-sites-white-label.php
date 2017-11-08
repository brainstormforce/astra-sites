<?php
/**
 * Astra Sites White Label
 *
 * @package Astra Sites
 * @since 1.0.12
 */

if ( ! class_exists( 'Astra_Sites_White_Label' ) ) :

	/**
	 * Astra_Sites_White_Label
	 *
	 * @since 1.0.12
	 */
	class Astra_Sites_White_Label {

		/**
		 * Instance
		 *
		 * @since 1.0.12
		 *
		 * @var object Class Object.
		 * @access private
		 */
		private static $instance;

		/**
		 * Member Variable
		 *
		 * @since 1.0.12
		 *
		 * @var array branding
		 * @access private
		 */
		private static $branding;

		/**
		 * Initiator
		 *
		 * @since 1.0.12
		 *
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
		 * @since 1.0.12
		 */
		public function __construct() {

			add_filter( 'all_plugins', array( $this, 'plugins_page' ) );
			add_filter( 'astra_addon_branding_options', __CLASS__ . '::settings' );
			add_action( 'astra_pro_white_label_add_form', __CLASS__ . '::add_white_lavel_form' );
			add_filter( 'astra_sites_menu_page_title', array( $this, 'page_title' ) );

			// Display the link with the plugin meta.
			if ( is_admin() ) {
				add_filter( 'plugin_row_meta', array( $this, 'plugin_links' ), 10, 4 );
			}
		}

		/**
		 * White labels the plugins page.
		 *
		 * @since 1.0.12
		 *
		 * @param array $plugins Plugins Array.
		 * @return array
		 */
		function plugins_page( $plugins ) {

			if ( ! is_callable( 'Astra_Ext_White_Label_Markup::get_white_label' ) ) {
				return $plugins;
			}

			if ( ! isset( $plugins[ ASTRA_SITES_BASE ] ) ) {
				return $plugins;
			}

			// Set White Labels.
			$name        = Astra_Ext_White_Label_Markup::get_white_label( 'astra-sites', 'name' );
			$description = Astra_Ext_White_Label_Markup::get_white_label( 'astra-sites', 'description' );
			$author      = Astra_Ext_White_Label_Markup::get_white_label( 'astra-agency', 'author' );
			$author_uri  = Astra_Ext_White_Label_Markup::get_white_label( 'astra-agency', 'author_url' );

			if ( ! empty( $name ) ) {
				$plugins[ ASTRA_SITES_BASE ]['Name'] = $name;

				// Remove Plugin URI if Agency White Label name is set.
				$plugins[ ASTRA_SITES_BASE ]['PluginURI'] = '';
			}

			if ( ! empty( $description ) ) {
				$plugins[ ASTRA_SITES_BASE ]['Description'] = $description;
			}

			if ( ! empty( $author ) ) {
				$plugins[ ASTRA_SITES_BASE ]['Author'] = $author;
			}

			if ( ! empty( $author_uri ) ) {
				$plugins[ ASTRA_SITES_BASE ]['AuthorURI'] = $author_uri;
			}

			return $plugins;
		}

		/**
		 * Remove a "view details" link from the plugin list table
		 *
		 * @since 1.0.12
		 *
		 * @param array  $plugin_meta  List of links.
		 * @param string $plugin_file Relative path to the main plugin file from the plugins directory.
		 * @param array  $plugin_data  Data from the plugin headers.
		 * @return array
		 */
		public function plugin_links( $plugin_meta, $plugin_file, $plugin_data ) {

			if ( ! is_callable( 'Astra_Ext_White_Label_Markup::get_white_label' ) ) {
				return $plugin_meta;
			}

			// Set White Labels.
			if ( ASTRA_SITES_BASE == $plugin_file ) {

				$name        = Astra_Ext_White_Label_Markup::get_white_label( 'astra-sites', 'name' );
				$description = Astra_Ext_White_Label_Markup::get_white_label( 'astra-sites', 'description' );

				// Remove Plugin URI if Agency White Label name is set.
				if ( ! empty( $name ) ) {
					unset( $plugin_meta[2] );
				}
			}

			return $plugin_meta;
		}

		/**
		 * Add White Label setting's
		 *
		 * @since 1.0.12
		 *
		 * @param  array $settings White label setting.
		 * @return array
		 */
		public static function settings( $settings = array() ) {

			$settings['astra-sites'] = array(
				'name'        => '',
				'description' => '',
			);

			return $settings;
		}

		/**
		 * Add White Label form
		 *
		 * @since 1.0.12
		 *
		 * @param  array $settings White label setting.
		 * @return void
		 */
		public static function add_white_lavel_form( $settings = array() ) {

			/* translators: %1$s product name */
			$plugin_name = sprintf( __( '%1$s Branding', 'astra-sites' ), ASTRA_SITES_NAME );

			require_once ASTRA_SITES_DIR . 'inc/includes/white-label.php';
		}

		/**
		 * Page Title
		 *
		 * @since 1.0.12
		 *
		 * @param  string $title Page Title.
		 * @return string        Filtered Page Title.
		 */
		function page_title( $title ) {
			$get_white_labels = 'Astra_Ext_White_Label_Markup::get_white_labels';
			if ( is_callable( $get_white_labels ) ) {
				$astra_sites_name = Astra_Ext_White_Label_Markup::get_white_label( 'astra-sites', 'name' );
				if ( ! empty( $astra_sites_name ) ) {
					$title = Astra_Ext_White_Label_Markup::get_white_label( 'astra-sites', 'name' );
				}
			}

			return $title;
		}

	}

	/**
	 * Kicking this off by calling 'set_instance()' method
	 */
	Astra_Sites_White_Label::set_instance();

endif;
