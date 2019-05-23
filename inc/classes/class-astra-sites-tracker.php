<?php
/**
 * Astra Sites Tracker
 *
 * @since  x.x.x
 * @package Astra Sites
 */

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( 'Astra_Sites_Tracker' ) ) :

	/**
	 * Astra_Sites_Tracker
	 */
	class Astra_Sites_Tracker {

		/**
		 * API URL which is used to get the response from.
		 *
		 * @since  x.x.x
		 * @var (String) URL
		 */
		public static $api_url;

		/**
		 * Instance of Astra_Sites_Tracker
		 *
		 * @since  x.x.x
		 * @var (Object) Astra_Sites_Tracker
		 */
		private static $_instance = null;

		/**
		 * Instance of Astra_Sites_Tracker.
		 *
		 * @since  x.x.x
		 *
		 * @return object Class object.
		 */
		public static function get_instance() {
			if ( ! isset( self::$_instance ) ) {
				self::$_instance = new self;
			}

			return self::$_instance;
		}

		/**
		 * Constructor.
		 *
		 * @since  x.x.x
		 */
		private function __construct() {

			self::set_api_url();

			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ) );

			// AJAX.
			add_action( 'wp_ajax_astra-view-count', array( $this, 'view_count' ) );
			add_action( 'wp_ajax_astra-download-count', array( $this, 'download_count' ) );
		}

		/**
		 * Send View Count to the Server.
		 *
		 * @since  x.x.x
		 */
		public function view_count() {

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( 'You can\'t access this action.' );
			}

			// Control Logic goes here.

			wp_send_json_success(
				array(
					'view_count' => true,
				)
			);
		}

		/**
		 * Send Download Count to the Server.
		 *
		 * @since  x.x.x
		 */
		public function download_count() {

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( 'You can\'t access this action.' );
			}

			// Control Logic goes here.

			wp_send_json_success(
				array(
					'download_count' => true,
				)
			);
		}

		/**
		 * Setter for $api_url
		 *
		 * @since  x.x.x
		 */
		public static function set_api_url() {

			self::$api_url = apply_filters( 'astra_sites_tracking_api_url', 'http://localhost/projects/analytics-server/wp-json/analytics/v2/' );

		}

		/**
		 * Enqueue admin scripts.
		 *
		 * @since  x.x.x
		 *
		 * @param  string $hook Current hook name.
		 * @return void
		 */
		public function admin_enqueue( $hook = '' ) {

			$params = self::get_tracking_data();
			// echo '<xmp>';
			// print_r( $params );
			// echo '</xmp>';
			// wp_die();
			wp_localize_script( 'jquery', 'trackingData', $params );

		}

		/**
		 * Get all the tracking data.
		 *
		 * @return array
		 */
		private static function get_tracking_data() {
			$data = array();

			// General site info.
			$data['url']   = home_url();
			$data['email'] = apply_filters( 'astra_sites_tracker_admin_email', get_option( 'admin_email' ) );
			$data['theme'] = self::get_theme_info();

			// WordPress Info.
			$data['wp'] = self::get_wordpress_info();

			// Server Info.
			$data['server'] = self::get_server_info();

			// Plugin info.
			$all_plugins              = self::get_all_plugins();
			$data['active_plugins']   = $all_plugins['active_plugins'];
			$data['inactive_plugins'] = $all_plugins['inactive_plugins'];

			// Get all WooCommerce options info.
			$data['settings'] = array(
				'astra-sites-settings'  => get_option( 'astra_sites_settings' ),
				'astra-sites-favorites' => get_option( 'astra-sites-favorites' ),
			);

			return apply_filters( 'astra_sites_tracker_data', $data );
		}

		/**
		 * Get the current theme info, theme name and version.
		 *
		 * @return array
		 */
		public static function get_theme_info() {
			$theme_data        = wp_get_theme();
			$theme_child_theme = self::astra_bool_to_string( is_child_theme() );
			$theme_wc_support  = self::astra_bool_to_string( current_theme_supports( 'woocommerce' ) );

			return array(
				'name'        => $theme_data->Name, // @phpcs:ignore
				'version'     => $theme_data->Version, // @phpcs:ignore
				'child_theme' => $theme_child_theme,
				'wc_support'  => $theme_wc_support,
			);
		}

		/**
		 * Get WordPress related data.
		 *
		 * @return array
		 */
		private static function get_wordpress_info() {
			$wp_data = array();

			$memory = self::astra_let_to_num( WP_MEMORY_LIMIT );

			if ( function_exists( 'memory_get_usage' ) ) {
				$system_memory = self::astra_let_to_num( @ini_get( 'memory_limit' ) );
				$memory        = max( $memory, $system_memory );
			}

			$wp_data['memory_limit'] = size_format( $memory );
			$wp_data['debug_mode']   = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'Yes' : 'No';
			$wp_data['locale']       = get_locale();
			$wp_data['version']      = get_bloginfo( 'version' );
			$wp_data['multisite']    = is_multisite() ? 'Yes' : 'No';

			return $wp_data;
		}

		/**
		 * Get server related info.
		 *
		 * @return array
		 */
		private static function get_server_info() {
			$server_data = array();

			if ( ! empty( $_SERVER['SERVER_SOFTWARE'] ) ) {
				$server_data['software'] = $_SERVER['SERVER_SOFTWARE']; // @phpcs:ignore
			}

			if ( function_exists( 'phpversion' ) ) {
				$server_data['php_version'] = phpversion();
			}

			if ( function_exists( 'ini_get' ) ) {
				$server_data['php_post_max_size']  = size_format( ( ini_get( 'post_max_size' ) ) );
				$server_data['php_time_limt']      = ini_get( 'max_execution_time' );
				$server_data['php_max_input_vars'] = ini_get( 'max_input_vars' );
				$server_data['php_suhosin']        = extension_loaded( 'suhosin' ) ? 'Yes' : 'No';
			}

			$database_version             = self::ast_get_server_database_version();
			$server_data['mysql_version'] = $database_version['number'];

			$server_data['php_max_upload_size']  = size_format( wp_max_upload_size() );
			$server_data['php_default_timezone'] = date_default_timezone_get();
			$server_data['php_soap']             = class_exists( 'SoapClient' ) ? 'Yes' : 'No';
			$server_data['php_fsockopen']        = function_exists( 'fsockopen' ) ? 'Yes' : 'No';
			$server_data['php_curl']             = function_exists( 'curl_init' ) ? 'Yes' : 'No';

			return $server_data;
		}

		/**
		 * Get all plugins grouped into activated or not.
		 *
		 * @return array
		 */
		private static function get_all_plugins() {
			// Ensure get_plugins function is loaded.
			if ( ! function_exists( 'get_plugins' ) ) {
				include ABSPATH . '/wp-admin/includes/plugin.php';
			}

			$plugins             = get_plugins();
			$active_plugins_keys = get_option( 'active_plugins', array() );
			$active_plugins      = array();

			foreach ( $plugins as $k => $v ) {
				// Take care of formatting the data how we want it.
				$formatted         = array();
				$formatted['name'] = strip_tags( $v['Name'] );
				if ( isset( $v['Version'] ) ) {
					$formatted['version'] = strip_tags( $v['Version'] );
				}
				if ( isset( $v['Author'] ) ) {
					$formatted['author'] = strip_tags( $v['Author'] );
				}
				if ( isset( $v['Network'] ) ) {
					$formatted['network'] = strip_tags( $v['Network'] );
				}
				if ( isset( $v['PluginURI'] ) ) {
					$formatted['plugin_uri'] = strip_tags( $v['PluginURI'] );
				}
				if ( in_array( $k, $active_plugins_keys, true ) ) {
					// Remove active plugins from list so we can show active and inactive separately.
					unset( $plugins[ $k ] );
					$active_plugins[ $k ] = $formatted;
				} else {
					$plugins[ $k ] = $formatted;
				}
			}

			return array(
				'active_plugins'   => $active_plugins,
				'inactive_plugins' => $plugins,
			);
		}

		/**
		 * Retrieves the MySQL server version. Based on $wpdb.
		 *
		 * @since x.x.x
		 * @return array Vesion information.
		 */
		public static function ast_get_server_database_version() {
			global $wpdb;

			if ( empty( $wpdb->is_mysql ) ) {
				return array(
					'string' => '',
					'number' => '',
				);
			}

			if ( $wpdb->use_mysqli ) {
				$server_info = mysqli_get_server_info( $wpdb->dbh ); // @codingStandardsIgnoreLine.
			} else {
				$server_info = mysql_get_server_info( $wpdb->dbh ); // @codingStandardsIgnoreLine.
			}

			return array(
				'string' => $server_info,
				'number' => preg_replace( '/([^\d.]+).*/', '', $server_info ),
			);
		}
		/**
		 * Converts a bool to a 'yes' or 'no'.
		 *
		 * @since x.x.x
		 * @param bool $bool String to convert.
		 * @return string
		 */
		public static function astra_bool_to_string( $bool ) {
			if ( ! is_bool( $bool ) ) {
				$bool = is_bool( $bool ) ? $bool : ( 'yes' === $bool || 1 === $bool || 'true' === $bool || '1' === $bool );
			}
			return true === $bool ? 'yes' : 'no';
		}

		/**
		 * Notation to numbers.
		 *
		 * This function transforms the php.ini notation for numbers (like '2M') to an integer.
		 *
		 * @param  string $size Size value.
		 * @return int
		 */
		public static function astra_let_to_num( $size ) {
			$l   = substr( $size, -1 );
			$ret = (int) substr( $size, 0, -1 );
			switch ( strtoupper( $l ) ) {
				case 'P':
					$ret *= 1024;
					// No break.
				case 'T':
					$ret *= 1024;
					// No break.
				case 'G':
					$ret *= 1024;
					// No break.
				case 'M':
					$ret *= 1024;
					// No break.
				case 'K':
					$ret *= 1024;
					// No break.
			}
			return $ret;
		}
	}


	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	Astra_Sites_Tracker::get_instance();

endif;
