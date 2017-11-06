<?php
/**
 * Astra Sites Importer
 *
 * @since  1.0.0
 * @package Astra Sites
 */

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( 'Astra_Sites_Importer' ) ) :

	/**
	 * Astra Sites Importer
	 */
	class Astra_Sites_Importer {

		/**
		 * Instance
		 *
		 * @since  1.0.0
		 * @var (Object) Class object
		 */
		public static $_instance     = null;

		/**
		 * Set Instance
		 *
		 * @since  1.0.0
		 *
		 * @return object Class object.
		 */
		public static function set_instance() {
			if ( ! isset( self::$_instance ) ) {
				self::$_instance = new self;
			}

			return self::$_instance;
		}

		/**
		 * Constructor.
		 *
		 * @since  1.0.0
		 */
		public function __construct() {

			require_once ASTRA_SITES_DIR . 'inc/importers/class-astra-sites-helper.php';
			require_once ASTRA_SITES_DIR . 'inc/importers/class-widgets-importer.php';
			require_once ASTRA_SITES_DIR . 'inc/importers/class-astra-customizer-import.php';
			require_once ASTRA_SITES_DIR . 'inc/importers/wxr-importer/class-astra-wxr-importer.php';
			require_once ASTRA_SITES_DIR . 'inc/importers/class-astra-site-options-import.php';

			// Import AJAX.
			add_action( 'wp_ajax_astra-sites-import-start'               , array( $this, 'import_start' ) );
			add_action( 'wp_ajax_astra-sites-import-customizer-settings' , array( $this, 'import_customizer_settings' ) );
			add_action( 'wp_ajax_astra-sites-import-xml'                 , array( $this, 'import_xml' ) );
			add_action( 'wp_ajax_astra-sites-import-options'             , array( $this, 'import_options' ) );
			add_action( 'wp_ajax_astra-sites-import-widgets'             , array( $this, 'import_widgets' ) );
			add_action( 'wp_ajax_astra-sites-import-end'                 , array( $this, 'import_end' ) );

			// Hooks in AJAX.
			add_action( 'astra_sites_import_complete'                    , array( $this, 'clear_cache' ) );
			add_action( 'astra_sites_image_import_complete'              , array( $this, 'clear_cache' ) );

		}

		/**
		 * Start Site Import
		 *
		 * @since 1.0.14
		 * @return void
		 */
		function import_start() {

			if ( ! current_user_can( 'customize' ) ) {
				wp_send_json_error( __( 'You have not "customize" access to import the astra site.', 'astra-sites' ) );
			}

			$demo_api_uri = isset( $_POST['api_url'] ) ? esc_url( $_POST['api_url'] ) : '';

			if ( ! empty( $demo_api_uri ) ) {

				$demo_data = self::get_astra_single_demo( $demo_api_uri );

				do_action( 'astra_sites_import_start', $demo_data );

				wp_send_json_success( $demo_data );

			} else {
				wp_send_json_error( __( 'Site API URL is empty!', 'astra-sites' ) );
			}

		}

		/**
		 * Import Customizer Settings.
		 *
		 * @since 1.0.14
		 * @return void
		 */
		function import_customizer_settings() {

			$customizer_data = ( isset( $_POST['customizer_data'] ) ) ? (array) json_decode( stripcslashes( $_POST['customizer_data'] ), 1 ) : '';

			if ( isset( $customizer_data ) ) {
				$customizer_import = Astra_Customizer_Import::instance();
				$customizer_import->import( $customizer_data );

				wp_send_json_success( $customizer_data );
			} else {
				wp_send_json_error( __( 'Customizer data is empty!', 'astra-sites' ) );
			}

		}

		/**
		 * Import XML.
		 *
		 * @since 1.0.14
		 * @return void
		 */
		function import_xml() {

			$wxr_url = ( isset( $_POST['wxr_url'] ) ) ? urldecode( $_POST['wxr_url'] ) : '';

			if ( isset( $wxr_url ) ) {
				$wxr_importer = Astra_WXR_Importer::instance();
				$xml_path     = $wxr_importer->download_xml( $wxr_url );
				$wxr_importer->import_xml( $xml_path['file'] );
				wp_send_json_success( $wxr_url );
			} else {
				wp_send_json_error( __( 'Invalid site XML file!', 'astra-sites' ) );
			}

		}

		/**
		 * Import Options.
		 *
		 * @since 1.0.14
		 * @return void
		 */
		function import_options() {

			$options_data = ( isset( $_POST['options_data'] ) ) ? (array) json_decode( stripcslashes( $_POST['options_data'] ), 1 ) : '';

			if ( isset( $options_data ) ) {
				$options_importer = Astra_Site_Options_Import::instance();
				$options_importer->import_options( $options_data );
				wp_send_json_success( $options_data );
			} else {
				wp_send_json_error( __( 'Site options are empty!', 'astra-sites' ) );
			}

		}

		/**
		 * Import Widgets.
		 *
		 * @since 1.0.14
		 * @return void
		 */
		function import_widgets() {

			$widgets_data = ( isset( $_POST['widgets_data'] ) ) ? (object) json_decode( stripcslashes( $_POST['widgets_data'] ) ) : '';

			if ( isset( $widgets_data ) ) {
				$widgets_importer = Astra_Widget_Importer::instance();
				$status = $widgets_importer->import_widgets_data( $widgets_data );
				wp_send_json_success( $widgets_data );
			} else {
				wp_send_json_error( __( 'Site options are empty!', 'astra-sites' ) );
			}

		}

		/**
		 * Import End.
		 *
		 * @since 1.0.14
		 * @return void
		 */
		function import_end() {

			$demo_data = ( isset( $_POST['demo_data'] ) ) ? (array) json_decode( stripcslashes( $_POST['demo_data'] ), 1 ) : '';

			if ( ! empty( $demo_data ) ) {
				do_action( 'astra_sites_import_complete', $demo_data );
				wp_send_json_success( $demo_data );
			} else {
				wp_send_json_error( __( 'Site Import data not found!', 'astra-sites' ) );
			}

		}

		/**
		 * Get single demo.
		 *
		 * @since  1.0.0
		 *
		 * @param  (String) $demo_api_uri API URL of a demo.
		 *
		 * @return (Array) $astra_demo_data demo data for the demo.
		 */
		public static function get_astra_single_demo( $demo_api_uri ) {

			// default values.
			$remote_args = array();
			$defaults    = array(
				'id'                         => '',
				'astra-site-widgets-data'    => '',
				'astra-site-customizer-data' => '',
				'astra-site-options-data'    => '',
				'astra-site-wxr-path'        => '',
				'astra-enabled-extensions'   => '',
				'astra-custom-404'           => '',
				'required-plugins'           => '',
			);

			$api_args = apply_filters(
				'astra_sites_api_args', array(
					'timeout' => 15,
				)
			);

			// Use this for premium demos.
			$request_params = apply_filters(
				'astra_sites_api_params', array(
					'purchase_key' => '',
					'site_url'     => '',
				)
			);

			$demo_api_uri = add_query_arg( $request_params, $demo_api_uri );

			// API Call.
			$response = wp_remote_get( $demo_api_uri, $api_args );

			if ( ! is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) === 200 ) {

				$result                                     = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( ! isset( $result['code'] ) ) {
					$remote_args['id']                         = $result['id'];
					$remote_args['astra-site-widgets-data']    = json_decode( $result['astra-site-widgets-data'] );
					$remote_args['astra-site-customizer-data'] = $result['astra-site-customizer-data'];
					$remote_args['astra-site-options-data']    = $result['astra-site-options-data'];
					$remote_args['astra-site-wxr-path']        = $result['astra-site-wxr-path'];
					$remote_args['astra-enabled-extensions']   = $result['astra-enabled-extensions'];
					$remote_args['astra-custom-404']           = $result['astra-custom-404'];
					$remote_args['required-plugins']           = $result['required-plugins'];
				}
			}

			// Merge remote demo and defaults.
			return wp_parse_args( $remote_args, $defaults );
		}

		/**
		 * Clear Cache.
		 *
		 * @since  1.0.9
		 */
		public function clear_cache() {

			// Clear 'Elementor' file cache.
			if ( class_exists( '\Elementor\Plugin' ) ) {
				Elementor\Plugin::$instance->posts_css_manager->clear_cache();
			}

			// Clear 'Builder Builder' cache.
			if ( is_callable( 'FLBuilderModel::delete_asset_cache_for_all_posts' ) ) {
				FLBuilderModel::delete_asset_cache_for_all_posts();
			}
		}

	}

	/**
	 * Kicking this off by calling 'set_instance()' method
	 */
	Astra_Sites_Importer::set_instance();

endif;
