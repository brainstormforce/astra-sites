<?php
/**
 * Astra Sites
 *
 * @since  1.0.0
 * @package Astra Sites
 */

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( 'Astra_Sites' ) ) :

	/**
	 * Astra_Sites
	 */
	class Astra_Sites {

		/**
		 * API URL which is used to get the response from.
		 *
		 * @since  1.0.0
		 * @var (String) URL
		 */
		public static $api_url;

		/**
		 * Instance of Astra_Sites
		 *
		 * @since  1.0.0
		 * @var (Object) Astra_Sites
		 */
		private static $_instance = null;

		/**
		 * Instance of Astra_Sites.
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
		private function __construct() {

			self::set_api_url();

			$this->includes();

			add_action( 'admin_notices', array( $this, 'add_notice' ), 1 );
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ) );

			// AJAX.
			add_action( 'wp_ajax_astra-required-plugins', array( $this, 'required_plugin' ) );
			add_action( 'wp_ajax_astra-required-plugin-activate', array( $this, 'required_plugin_activate' ) );
		}

		/**
		 * Add Admin Notice.
		 */
		function add_notice() {

			Astra_Sites_Notices::add_notice(
				array(
					'type'             => 'error',
					'show_if'          => ( ! defined( 'ASTRA_THEME_SETTINGS' ) ) ? true : false,
					/* translators: 1: theme.php file*/
					'message'          => sprintf( __( 'Astra Theme needs to be active for you to use currently installed "%1$s" plugin. <a href="%2$s">Install & Activate Now</a>', 'astra-sites' ), ASTRA_SITES_NAME, esc_url( admin_url( 'themes.php?theme=astra' ) ) ),
					'dismissible'      => true,
					'dismissible-time' => MINUTE_IN_SECONDS,
				)
			);

		}

		/**
		 * Loads textdomain for the plugin.
		 *
		 * @since 1.0.1
		 */
		function load_textdomain() {
			load_plugin_textdomain( 'astra-sites' );
		}

		/**
		 * Admin Notices
		 *
		 * @since 1.0.5
		 * @return void
		 */
		function admin_notices() {

			if ( ! defined( 'ASTRA_THEME_SETTINGS' ) ) {
				return;
			}

			add_action( 'plugin_action_links_' . ASTRA_SITES_BASE, array( $this, 'action_links' ) );
		}

		/**
		 * Show action links on the plugin screen.
		 *
		 * @param   mixed $links Plugin Action links.
		 * @return  array
		 */
		function action_links( $links ) {
			$action_links = array(
				'settings' => '<a href="' . admin_url( 'themes.php?page=astra-sites' ) . '" aria-label="' . esc_attr__( 'See Library', 'astra-sites' ) . '">' . esc_html__( 'See Library', 'astra-sites' ) . '</a>',
			);

			return array_merge( $action_links, $links );
		}

		/**
		 * Setter for $api_url
		 *
		 * @since  1.0.0
		 */
		public static function set_api_url() {

			self::$api_url = apply_filters( 'astra_sites_api_url', 'https://sites.wpastra.com/wp-json/wp/v2/' );

		}

		/**
		 * Enqueue admin scripts.
		 *
		 * @since  1.0.5    Added 'getUpgradeText' and 'getUpgradeURL' localize variables.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $hook Current hook name.
		 * @return void
		 */
		public function admin_enqueue( $hook = '' ) {

			if ( 'appearance_page_astra-sites' !== $hook ) {
				return;
			}

			// API.
			wp_register_script( 'astra-sites-api', ASTRA_SITES_URI . 'inc/assets/js/astra-sites-api.js', array( 'jquery' ), ASTRA_SITES_VER, true );

			// Admin Page.
			wp_enqueue_style( 'astra-sites-admin', ASTRA_SITES_URI . 'inc/assets/css/admin.css', ASTRA_SITES_VER, true );
			wp_enqueue_script( 'astra-sites-admin-page', ASTRA_SITES_URI . 'inc/assets/js/admin-page.js', array( 'jquery', 'wp-util', 'updates' ), ASTRA_SITES_VER, true );
			wp_enqueue_script( 'astra-sites-render-grid', ASTRA_SITES_URI . 'inc/assets/js/render-grid.js', array( 'wp-util', 'astra-sites-api', 'imagesloaded', 'jquery' ), ASTRA_SITES_VER, true );

			$data = array(
				'ApiURL'  => self::$api_url,
				'filters' => array(
					'page_builder' => array(
						'title'   => __( 'Page Builder', 'astra-sites' ),
						'slug'    => 'astra-site-page-builder',
						'trigger' => 'astra-api-category-loaded',
					),
					'categories'   => array(
						'title'   => __( 'Categories', 'astra-sites' ),
						'slug'    => 'astra-site-category',
						'trigger' => 'astra-api-category-loaded',
					),
				),
			);
			wp_localize_script( 'astra-sites-api', 'astraSitesApi', $data );

			// Use this for premium demos.
			$request_params = apply_filters(
				'astra_sites_api_params', array(
					'purchase_key' => '',
					'site_url'     => '',
					'par-page'     => 6,
				)
			);

			$data = apply_filters(
				'astra_sites_localize_vars',
				array(
					'sites'    => $request_params,
					'settings' => array(),
				)
			);

			wp_localize_script( 'astra-sites-render-grid', 'astraRenderGrid', $data );

			$data = apply_filters(
				'astra_sites_localize_vars',
				array(
					'debug'           => ( ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || isset( $_GET['debug'] ) ) ? true : false,
					'ajaxurl'         => esc_url( admin_url( 'admin-ajax.php' ) ),
					'siteURL'         => site_url(),
					'getProText'      => __( 'Purchase', 'astra-sites' ),
					'getProURL'       => esc_url( 'https://wpastra.com/agency/?utm_source=demo-import-panel&utm_campaign=astra-sites&utm_medium=wp-dashboard' ),
					'getUpgradeText'  => __( 'Upgrade', 'astra-sites' ),
					'getUpgradeURL'   => esc_url( 'https://wpastra.com/agency/?utm_source=demo-import-panel&utm_campaign=astra-sites&utm_medium=wp-dashboard' ),
					'_ajax_nonce'     => wp_create_nonce( 'astra-sites' ),
					'requiredPlugins' => array(),
					'strings'         => array(
						'importFailedBtnSmall' => __( 'Error!', 'astra-sites' ),
						'importFailedBtnLarge' => __( 'Error! Read Possibilities.', 'astra-sites' ),
						'viewSite'             => __( 'Done! View Site', 'astra-sites' ),
						'btnActivating'        => __( 'Activating', 'astra-sites' ) . '&hellip;',
						'btnActive'            => __( 'Active', 'astra-sites' ),
						'importFailBtn'        => __( 'Import failed.', 'astra-sites' ),
						'importFailBtnLarge'   => __( 'Import failed. See error log.', 'astra-sites' ),
						'importDemo'           => __( 'Import This Site', 'astra-sites' ),
						'importingDemo'        => __( 'Importing..', 'astra-sites' ),
						'DescExpand'           => __( 'Read more', 'astra-sites' ) . '&hellip;',
						'DescCollapse'         => __( 'Hide', 'astra-sites' ),
						'responseError'        => __( 'There was a problem receiving a response from server.', 'astra-sites' ),
						'searchNoFound'        => __( 'No Demos found, Try a different search.', 'astra-sites' ),
						'importWarning'        => __( "Executing Demo Import will make your site similar as ours. Please bear in mind -\n\n1. It is recommended to run import on a fresh WordPress installation.\n\n2. Importing site does not delete any pages or posts. However, it can overwrite your existing content.\n\n3. Copyrighted media will not be imported. Instead it will be replaced with placeholders.", 'astra-sites' ),
					),
					'log'             => array(
						'installingPlugin'        => __( 'Installing plugin ', 'astra-sites' ),
						'installed'               => __( 'Successfully plugin installed!', 'astra-sites' ),
						'installError'            => __( 'Error! While Installing plugin ', 'astra-sites' ),
						'activating'              => __( 'Activating plugin ', 'astra-sites' ),
						'activated'               => __( 'Successfully plugin activated ', 'astra-sites' ),
						'bulkActivation'          => __( 'Bulk plugin activation...', 'astra-sites' ),
						'activate'                => __( 'Successfully plugin activate - ', 'astra-sites' ),
						'activationError'         => __( 'Error! While activating plugin  - ', 'astra-sites' ),
						'bulkInstall'             => __( 'Bulk plugin installation...', 'astra-sites' ),
						'api'                     => __( 'Site API ', 'astra-sites' ),
						'importing'               => __( 'Importing..', 'astra-sites' ),
						'processingRequest'       => __( 'Processing requests...', 'astra-sites' ),
						'importCustomizer'        => __( '1) Importing "Customizer Settings"...', 'astra-sites' ),
						'importCustomizerSuccess' => __( 'Successfully imported customizer settings!', 'astra-sites' ),
						'importXML'               => __( '2) Importing "XML"...', 'astra-sites' ),
						'importXMLSuccess'        => __( 'Successfully imported XML!', 'astra-sites' ),
						'importOptions'           => __( '3) Importing "Options"...', 'astra-sites' ),
						'importOptionsSuccess'    => __( 'Successfully imported Options!', 'astra-sites' ),
						'importWidgets'           => __( '4) Importing "Widgets"...', 'astra-sites' ),
						'importWidgetsSuccess'    => __( 'Successfully imported Widgets!', 'astra-sites' ),
						/* translators: %1$s Upgrade server configuration article link. */
						'serverConfiguration'     => esc_url( 'https://wpastra.com/docs/?p=1314&utm_source=demo-import-panel&utm_campaign=import-error&utm_medium=wp-dashboard' ),
						'success'                 => __( 'Site imported successfully! visit : ', 'astra-sites' ),
						'gettingData'             => __( 'Getting Import Data..', 'astra-sites' ),
						'importingCustomizer'     => __( 'Importing Customizer Settings..', 'astra-sites' ),
						'importingXML'            => __( 'Importing XML..', 'astra-sites' ),
						'importingOptions'        => __( 'Importing Site Options..', 'astra-sites' ),
						'importingWidgets'        => __( 'Importing Widgets..', 'astra-sites' ),
						'importComplete'          => __( 'Import Complete..', 'astra-sites' ),
						'preview'                 => __( 'Previewing ', 'astra-sites' ),
						'importLogText'           => __( 'See Error Log &rarr;', 'astra-sites' ),
					),
				)
			);

			wp_localize_script( 'astra-sites-admin-page', 'astraSitesAdmin', $data );

		}

		/**
		 * Load all the required files in the importer.
		 *
		 * @since  1.0.0
		 */
		private function includes() {

			require_once ASTRA_SITES_DIR . 'inc/classes/class-astra-sites-notices.php';
			require_once ASTRA_SITES_DIR . 'inc/classes/class-astra-sites-page.php';
			require_once ASTRA_SITES_DIR . 'inc/classes/compatibility/class-astra-sites-compatibility.php';
			require_once ASTRA_SITES_DIR . 'inc/classes/class-astra-sites-white-label.php';
			require_once ASTRA_SITES_DIR . 'inc/classes/class-astra-sites-importer.php';

		}

		/**
		 * Required Plugin Activate
		 *
		 * @since 1.0.0
		 */
		public function required_plugin_activate() {

			if ( ! current_user_can( 'install_plugins' ) || ! isset( $_POST['init'] ) || ! $_POST['init'] ) {
				wp_send_json_error(
					array(
						'success' => false,
						'message' => __( 'No plugin specified', 'astra-sites' ),
					)
				);
			}

			$data               = array();
			$plugin_init        = ( isset( $_POST['init'] ) ) ? esc_attr( $_POST['init'] ) : '';
			$astra_site_options = ( isset( $_POST['options'] ) ) ? json_decode( stripslashes( $_POST['options'] ) ) : '';
			$enabled_extensions = ( isset( $_POST['enabledExtensions'] ) ) ? json_decode( stripslashes( $_POST['enabledExtensions'] ) ) : '';

			$data['astra_site_options'] = $astra_site_options;
			$data['enabled_extensions'] = $enabled_extensions;

			$activate = activate_plugin( $plugin_init, '', false, true );

			if ( is_wp_error( $activate ) ) {
				wp_send_json_error(
					array(
						'success' => false,
						'message' => $activate->get_error_message(),
					)
				);
			}

			do_action( 'astra_sites_after_plugin_activation', $plugin_init, $data );

			wp_send_json_success(
				array(
					'success' => true,
					'message' => __( 'Plugin Successfully Activated', 'astra-sites' ),
				)
			);

		}

		/**
		 * Required Plugin
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function required_plugin() {

			// Verify Nonce.
			check_ajax_referer( 'astra-sites', '_ajax_nonce' );

			$response = array(
				'active'       => array(),
				'inactive'     => array(),
				'notinstalled' => array(),
			);

			if ( ! current_user_can( 'customize' ) ) {
				wp_send_json_error( $response );
			}

			$required_plugins = ( isset( $_POST['required_plugins'] ) ) ? $_POST['required_plugins'] : array();

			if ( count( $required_plugins ) > 0 ) {
				foreach ( $required_plugins as $key => $plugin ) {

					/**
					 * Has Pro Version Support?
					 * And
					 * Is Pro Version Installed?
					 */
					$plugin_pro = self::pro_plugin_exist( $plugin['init'] );
					if ( $plugin_pro ) {

						// Pro - Active.
						if ( is_plugin_active( $plugin_pro['init'] ) ) {
							$response['active'][] = $plugin_pro;

							// Pro - Inactive.
						} else {
							$response['inactive'][] = $plugin_pro;
						}
					} else {

						// Lite - Installed but Inactive.
						if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin['init'] ) && is_plugin_inactive( $plugin['init'] ) ) {

							$response['inactive'][] = $plugin;

							// Lite - Not Installed.
						} elseif ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin['init'] ) ) {

							$response['notinstalled'][] = $plugin;

							// Lite - Active.
						} else {
							$response['active'][] = $plugin;
						}
					}
				}
			}

			// Send response.
			wp_send_json_success( $response );
		}

		/**
		 * Has Pro Version Support?
		 * And
		 * Is Pro Version Installed?
		 *
		 * Check Pro plugin version exist of requested plugin lite version.
		 *
		 * Eg. If plugin 'BB Lite Version' required to import demo. Then we check the 'BB Agency Version' is exist?
		 * If yes then we only 'Activate' Agency Version. [We couldn't install agency version.]
		 * Else we 'Activate' or 'Install' Lite Version.
		 *
		 * @since 1.0.1
		 *
		 * @param  string $lite_version Lite version init file.
		 * @return mixed               Return false if not installed or not supported by us
		 *                                    else return 'Pro' version details.
		 */
		public static function pro_plugin_exist( $lite_version = '' ) {

			// Lite init => Pro init.
			$plugins = apply_filters(
				'astra_sites_pro_plugin_exist', array(
					'beaver-builder-lite-version/fl-builder.php' => array(
						'slug' => 'bb-plugin',
						'init' => 'bb-plugin/fl-builder.php',
						'name' => 'Beaver Builder Plugin (Agency Version)',
					),
					'ultimate-addons-for-beaver-builder-lite/bb-ultimate-addon.php' => array(
						'slug' => 'bb-ultimate-addon',
						'init' => 'bb-ultimate-addon/bb-ultimate-addon.php',
						'name' => 'Ultimate Addon for Beaver Builder',
					),
				), $lite_version
			);

			if ( isset( $plugins[ $lite_version ] ) ) {

				// Pro plugin directory exist?
				if ( file_exists( WP_PLUGIN_DIR . '/' . $plugins[ $lite_version ]['init'] ) ) {
					return $plugins[ $lite_version ];
				}
			}

			return false;
		}
	}

	/**
	 * Kicking this off by calling 'set_instance()' method
	 */
	Astra_Sites::set_instance();

endif;
