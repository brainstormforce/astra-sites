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
		public static function get_instance() {
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
			add_action( 'wp_ajax_astra-sites-backup-settings', array( $this, 'backup_settings' ) );
			add_action( 'wp_ajax_astra-sites-set-reset-data', array( $this, 'get_reset_data' ) );
			add_action( 'wp_ajax_astra-sites-activate-theme', array( $this, 'activate_theme' ) );
			add_action( 'wp_ajax_astra-sites-getting-started-notice', array( $this, 'getting_started_notice' ) );
		}

		/**
		 * Close getting started notice for current user
		 *
		 * @since 1.3.5
		 * @return void
		 */
		function getting_started_notice() {
			update_user_meta( get_current_user_id(), '_astra_sites_gettings_started', true );
			wp_send_json_success();
		}

		/**
		 * Activate theme
		 *
		 * @since 1.3.2
		 * @return void
		 */
		function activate_theme() {

			switch_theme( 'astra' );

			wp_send_json_success(
				array(
					'success' => true,
					'message' => __( 'Theme Activated', 'astra-sites' ),
				)
			);
		}

		/**
		 * Set reset data
		 */
		function get_reset_data() {
			if ( ! defined( 'WP_CLI' ) && ! current_user_can( 'manage_options' ) ) {
				return;
			}

			global $wpdb;

			$post_ids = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_astra_sites_imported_post'" );
			$form_ids = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_astra_sites_imported_wp_forms'" );
			$term_ids = $wpdb->get_col( "SELECT term_id FROM {$wpdb->termmeta} WHERE meta_key='_astra_sites_imported_term'" );

			$data = array(
				'reset_posts'    => $post_ids,
				'reset_wp_forms' => $form_ids,
				'reset_terms'    => $term_ids,
			);

			if ( defined( 'WP_CLI' ) ) {
				return $data;
			} else {
				wp_send_json_success( $data );
			}

		}

		/**
		 * Backup our existing settings.
		 */
		function backup_settings() {
			if ( ! defined( 'WP_CLI' ) && ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$file_name    = 'astra-sites-backup-' . date( 'd-M-Y-h-i-s' ) . '.json';
			$old_settings = get_option( 'astra-settings', array() );
			$upload_dir   = Astra_Sites_Importer_Log::get_instance()->log_dir();
			$upload_path  = trailingslashit( $upload_dir['path'] );
			$log_file     = $upload_path . $file_name;
			$file_system  = Astra_Sites_Importer_Log::get_instance()->get_filesystem();

			// If file system fails? Then take a backup in site option.
			if ( false === $file_system->put_contents( $log_file, json_encode( $old_settings ), FS_CHMOD_FILE ) ) {
				update_option( 'astra_sites_' . $file_name, $old_settings );
			}

			if ( defined( 'WP_CLI' ) ) {
				WP_CLI::line( 'File generated at ' . $log_file );
			} else {
				wp_send_json_success();
			}
		}

		/**
		 * Add Admin Notice.
		 */
		function add_notice() {

			$theme_status = 'astra-sites-theme-' . $this->get_theme_status();

			Astra_Notices::add_notice(
				array(
					'id'               => 'astra-theme-activation-nag',
					'type'             => 'error',
					'show_if'          => ( ! defined( 'ASTRA_THEME_SETTINGS' ) ) ? true : false,
					/* translators: 1: theme.php file*/
					'message'          => sprintf( __( '<p>Astra Theme needs to be active for you to use currently installed "%1$s" plugin. <a href="#" class="%3$s" data-theme-slug="astra">Install & Activate Now</a></p>', 'astra-sites' ), ASTRA_SITES_NAME, esc_url( admin_url( 'themes.php?theme=astra' ) ), $theme_status ),
					'dismissible'      => true,
					'dismissible-time' => WEEK_IN_SECONDS,
				)
			);
		}

		/**
		 * Get theme install, active or inactive status.
		 *
		 * @since 1.3.2
		 *
		 * @return string Theme status
		 */
		function get_theme_status() {

			$theme = wp_get_theme();

			// Theme installed and activate.
			if ( 'Astra' === $theme->name || 'Astra' === $theme->parent_theme ) {
				return 'installed-and-active';
			}

			// Theme installed but not activate.
			foreach ( (array) wp_get_themes() as $theme_dir => $theme ) {
				if ( 'Astra' === $theme->name || 'Astra' === $theme->parent_theme ) {
					return 'installed-but-inactive';
				}
			}

			return 'not-installed';
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
			self::$api_url = apply_filters( 'astra_sites_api_url', 'https://websitedemos.net/wp-json/wp/v2/' );

		}

		/**
		 * Enqueue admin scripts.
		 *
		 * @since  1.3.2    Added 'install-theme.js' to install and activate theme.
		 * @since  1.0.5    Added 'getUpgradeText' and 'getUpgradeURL' localize variables.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $hook Current hook name.
		 * @return void
		 */
		public function admin_enqueue( $hook = '' ) {

			wp_enqueue_script( 'astra-sites-install-theme', ASTRA_SITES_URI . 'inc/assets/js/install-theme.js', array( 'jquery', 'updates' ), ASTRA_SITES_VER, true );
			wp_enqueue_style( 'astra-sites-install-theme', ASTRA_SITES_URI . 'inc/assets/css/install-theme.css', null, ASTRA_SITES_VER, 'all' );

			$data = apply_filters(
				'astra_sites_install_theme_localize_vars',
				array(
					'installed'  => __( 'Installed! Activating..', 'astra-sites' ),
					'activating' => __( 'Activating..', 'astra-sites' ),
					'activated'  => __( 'Activated! Reloading..', 'astra-sites' ),
					'installing' => __( 'Installing..', 'astra-sites' ),
					'ajaxurl'    => esc_url( admin_url( 'admin-ajax.php' ) ),
				)
			);
			wp_localize_script( 'astra-sites-install-theme', 'AstraSitesInstallThemeVars', $data );

			if ( 'appearance_page_astra-sites' !== $hook ) {
				return;
			}

			global $is_IE, $is_edge;

			if ( $is_IE || $is_edge ) {
				wp_enqueue_script( 'astra-sites-eventsource', ASTRA_SITES_URI . 'inc/assets/js/eventsource.min.js', array( 'jquery', 'wp-util', 'updates' ), ASTRA_SITES_VER, true );
			}

			// Fetch.
			wp_register_script( 'astra-sites-fetch', ASTRA_SITES_URI . 'inc/assets/js/fetch.umd.js', array( 'jquery' ), ASTRA_SITES_VER, true );

			// API.
			wp_register_script( 'astra-sites-api', ASTRA_SITES_URI . 'inc/assets/js/astra-sites-api.js', array( 'jquery', 'astra-sites-fetch' ), ASTRA_SITES_VER, true );

			// Admin Page.
			wp_enqueue_style( 'astra-sites-admin', ASTRA_SITES_URI . 'inc/assets/css/admin.css', ASTRA_SITES_VER, true );
			wp_enqueue_script( 'astra-sites-admin-page', ASTRA_SITES_URI . 'inc/assets/js/admin-page.js', array( 'jquery', 'wp-util', 'updates' ), ASTRA_SITES_VER, true );
			wp_enqueue_script( 'astra-sites-render-grid', ASTRA_SITES_URI . 'inc/assets/js/render-grid.js', array( 'wp-util', 'astra-sites-api', 'imagesloaded', 'jquery' ), ASTRA_SITES_VER, true );

			$data = apply_filters(
				'astra_sites_localize_vars',
				array(
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
				)
			);
			wp_localize_script( 'astra-sites-api', 'astraSitesApi', $data );

			// Use this for premium demos.
			$request_params = apply_filters(
				'astra_sites_api_params',
				array(
					'purchase_key' => '',
					'site_url'     => '',
					'par-page'     => 30,
				)
			);

			$data = apply_filters(
				'astra_sites_render_localize_vars',
				array(
					'sites'                => $request_params,
					'page-builders'        => array(),
					'categories'           => array(),
					'settings'             => array(),
					'default_page_builder' => Astra_Sites_Page::get_instance()->get_setting( 'page_builder' ),
				)
			);

			wp_localize_script( 'astra-sites-render-grid', 'astraRenderGrid', $data );

			$data = apply_filters(
				'astra_sites_localize_vars',
				array(
					'debug'             => ( ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || isset( $_GET['debug'] ) ) ? true : false,
					'isPro'             => defined( 'ASTRA_PRO_SITES_NAME' ) ? true : false,
					'isWhiteLabeled'    => Astra_Sites_White_Label::get_instance()->is_white_labeled(),
					'ajaxurl'           => esc_url( admin_url( 'admin-ajax.php' ) ),
					'siteURL'           => site_url(),
					'docUrl'            => 'https://wpastra.com/',
					'getProText'        => __( 'Get Agency Bundle', 'astra-sites' ),
					'getProURL'         => esc_url( 'https://wpastra.com/agency/?utm_source=demo-import-panel&utm_campaign=astra-sites&utm_medium=wp-dashboard' ),
					'getUpgradeText'    => __( 'Upgrade', 'astra-sites' ),
					'getUpgradeURL'     => esc_url( 'https://wpastra.com/agency/?utm_source=demo-import-panel&utm_campaign=astra-sites&utm_medium=wp-dashboard' ),
					'_ajax_nonce'       => wp_create_nonce( 'astra-sites' ),
					'requiredPlugins'   => array(),
					'XMLReaderDisabled' => ! class_exists( 'XMLReader' ) ? true : false,
					'strings'           => array(
						/* translators: %s are HTML tags. */
						'warningXMLReader'         => sprintf( __( '%1$sRequired XMLReader PHP extension is missing on your server!%2$sAstra Sites import requires XMLReader extension to be installed. Please contact your web hosting provider and ask them to install and activate the XMLReader PHP extension.', 'astra-sites' ), '<div class="notice astra-sites-xml-notice notice-error"><p><b>', '</b></p><p>', '</p></div>' ),
						'warningBeforeCloseWindow' => __( 'Warning! Astra Site Import process is not complete. Don\'t close the window until import process complete. Do you still want to leave the window?', 'astra-sites' ),
						'importFailedBtnSmall'     => __( 'Error!', 'astra-sites' ),
						'importFailedBtnLarge'     => __( 'Error! Read Possibilities.', 'astra-sites' ),
						'importFailedURL'          => esc_url( 'https://wpastra.com/docs/?p=1314&utm_source=demo-import-panel&utm_campaign=astra-sites&utm_medium=import-failed' ),
						'viewSite'                 => __( 'Done! View Site', 'astra-sites' ),
						'btnActivating'            => __( 'Activating', 'astra-sites' ) . '&hellip;',
						'btnActive'                => __( 'Active', 'astra-sites' ),
						'importFailBtn'            => __( 'Import failed.', 'astra-sites' ),
						'importFailBtnLarge'       => __( 'Import failed.', 'astra-sites' ),
						'importDemo'               => __( 'Import This Site', 'astra-sites' ),
						'importingDemo'            => __( 'Importing..', 'astra-sites' ),
						'DescExpand'               => __( 'Read more', 'astra-sites' ) . '&hellip;',
						'DescCollapse'             => __( 'Hide', 'astra-sites' ),
						'responseError'            => __( 'There was a problem receiving a response from server.', 'astra-sites' ),
						'searchNoFound'            => __( 'No Demos found, Try a different search.', 'astra-sites' ),
					),
					'log'               => array(
						'installingPlugin'        => __( 'Installing plugin ', 'astra-sites' ),
						'installed'               => __( 'Plugin installed!', 'astra-sites' ),
						'activating'              => __( 'Activating plugin ', 'astra-sites' ),
						'activated'               => __( 'Plugin activated ', 'astra-sites' ),
						'bulkActivation'          => __( 'Bulk plugin activation...', 'astra-sites' ),
						'activate'                => __( 'Plugin activate - ', 'astra-sites' ),
						'activationError'         => __( 'Error! While activating plugin  - ', 'astra-sites' ),
						'bulkInstall'             => __( 'Bulk plugin installation...', 'astra-sites' ),
						'api'                     => __( 'Site API ', 'astra-sites' ),
						'importing'               => __( 'Importing..', 'astra-sites' ),
						'processingRequest'       => __( 'Processing requests...', 'astra-sites' ),
						'importCustomizer'        => __( 'Importing "Customizer Settings"...', 'astra-sites' ),
						'importCustomizerSuccess' => __( 'Imported customizer settings!', 'astra-sites' ),
						'importWPForms'           => __( 'Importing "Contact Forms"...', 'astra-sites' ),
						'importWPFormsSuccess'    => __( 'Imported Contact Forms!', 'astra-sites' ),
						'importXMLPrepare'        => __( 'Preparing "XML" Data...', 'astra-sites' ),
						'importXMLPrepareSuccess' => __( 'Set XML data!', 'astra-sites' ),
						'importXML'               => __( 'Importing "XML"...', 'astra-sites' ),
						'importXMLSuccess'        => __( 'Imported XML!', 'astra-sites' ),
						'importOptions'           => __( 'Importing "Options"...', 'astra-sites' ),
						'importOptionsSuccess'    => __( 'Imported Options!', 'astra-sites' ),
						'importWidgets'           => __( 'Importing "Widgets"...', 'astra-sites' ),
						'importWidgetsSuccess'    => __( 'Imported Widgets!', 'astra-sites' ),
						'serverConfiguration'     => esc_url( 'https://wpastra.com/docs/?p=1314&utm_source=demo-import-panel&utm_campaign=import-error&utm_medium=wp-dashboard' ),
						'success'                 => __( 'View site: ', 'astra-sites' ),
						'gettingData'             => __( 'Getting Site Information..', 'astra-sites' ),
						'importingCustomizer'     => __( 'Importing Customizer Settings..', 'astra-sites' ),
						'importingWPForms'        => __( 'Importing Contact Forms..', 'astra-sites' ),
						'importXMLPreparing'      => __( 'Setting up import data..', 'astra-sites' ),
						'importingXML'            => __( 'Importing Content..', 'astra-sites' ),
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

			require_once ASTRA_SITES_DIR . 'inc/lib/astra-notices/class-astra-notices.php';
			require_once ASTRA_SITES_DIR . 'inc/classes/class-astra-sites-white-label.php';
			require_once ASTRA_SITES_DIR . 'inc/classes/class-astra-sites-page.php';
			require_once ASTRA_SITES_DIR . 'inc/classes/compatibility/class-astra-sites-compatibility.php';
			require_once ASTRA_SITES_DIR . 'inc/classes/class-astra-sites-importer.php';
			require_once ASTRA_SITES_DIR . 'inc/classes/class-astra-sites-wp-cli.php';
		}

		/**
		 * Required Plugin Activate
		 *
		 * @since 2.0.0 Added parameters $init, $options & $enabled_extensions to add the WP CLI support.
		 * @since 1.0.0
		 * @param  string $init               Plugin init file.
		 * @param  array  $options            Site options.
		 * @param  array  $enabled_extensions Enabled extensions.
		 * @return void
		 */
		public function required_plugin_activate( $init = '', $options = array(), $enabled_extensions = array() ) {

			if ( ! defined( 'WP_CLI' ) && ! current_user_can( 'install_plugins' ) ) {
				wp_send_json_error(
					array(
						'success' => false,
						'message' => __( 'User have not plugin install permissions.', 'astra-sites' ),
					)
				);
			}
			if ( ! defined( 'WP_CLI' ) && ( ! isset( $_POST['init'] ) || ! $_POST['init'] ) ) {
				wp_send_json_error(
					array(
						'success' => false,
						'message' => __( 'No plugin specified!', 'astra-sites' ),
					)
				);
			}

			$plugin_init        = ( isset( $_POST['init'] ) ) ? esc_attr( $_POST['init'] ) : $init;
			$astra_site_options = ( isset( $_POST['options'] ) ) ? json_decode( stripslashes( $_POST['options'] ) ) : $options;
			$enabled_extensions = ( isset( $_POST['enabledExtensions'] ) ) ? json_decode( stripslashes( $_POST['enabledExtensions'] ) ) : $enabled_extensions;

			$data = array(
				'astra_site_options' => $astra_site_options,
				'enabled_extensions' => $enabled_extensions,
			);

			$activate = activate_plugin( $plugin_init, '', false, true );

			if ( is_wp_error( $activate ) ) {
				if ( defined( 'WP_CLI' ) ) {
					WP_CLI::line( 'Plugin Activation Error: ' . $activate->get_error_message() );
				} else {
					wp_send_json_error(
						array(
							'success' => false,
							'message' => $activate->get_error_message(),
						)
					);
				}
			}

			do_action( 'astra_sites_after_plugin_activation', $plugin_init, $data );

			if ( defined( 'WP_CLI' ) ) {
				WP_CLI::line( 'Plugin Activated!' );
			} else {
				wp_send_json_success(
					array(
						'success' => true,
						'message' => __( 'Plugin Activated', 'astra-sites' ),
					)
				);
			}
		}

		/**
		 * Required Plugins
		 *
		 * @since 2.0.0
		 *
		 * @param  array $required_plugins Required Plugins.
		 * @return mixed
		 */
		public function required_plugin( $required_plugins = array() ) {

			// Verify Nonce.
			if ( ! defined( 'WP_CLI' ) ) {
				check_ajax_referer( 'astra-sites', '_ajax_nonce' );
			}

			$response = array(
				'active'       => array(),
				'inactive'     => array(),
				'notinstalled' => array(),
			);

			if ( ! defined( 'WP_CLI' ) && ! current_user_can( 'customize' ) ) {
				wp_send_json_error( $response );
			}

			$required_plugins             = ( isset( $_POST['required_plugins'] ) ) ? $_POST['required_plugins'] : $required_plugins;
			$third_party_required_plugins = array();
			$third_party_plugins          = array(
				'learndash-course-grid' => array(
					'init' => 'learndash-course-grid/learndash_course_grid.php',
					'name' => 'LearnDash Course Grid',
					'link' => 'https://www.brainstormforce.com/go/learndash-course-grid/',
				),
				'sfwd-lms'              => array(
					'init' => 'sfwd-lms/sfwd_lms.php',
					'name' => 'LearnDash LMS',
					'link' => 'https://brainstormforce.com/go/learndash/',
				),
				'learndash-woocommerce' => array(
					'init' => 'learndash-woocommerce/learndash_woocommerce.php',
					'name' => 'LearnDash WooCommerce Integration',
					'link' => 'https://www.brainstormforce.com/go/learndash-woocommerce/',
				),
			);

			if ( count( $required_plugins ) > 0 ) {
				foreach ( $required_plugins as $key => $plugin ) {

					/**
					 * Has Pro Version Support?
					 * And
					 * Is Pro Version Installed?
					 */
					$plugin_pro = $this->pro_plugin_exist( $plugin['init'] );
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

							// Added premium plugins which need to install first.
							if ( array_key_exists( $plugin['slug'], $third_party_plugins ) ) {
								$third_party_required_plugins[] = $third_party_plugins[ $plugin['slug'] ];
							}

							// Lite - Active.
						} else {
							$response['active'][] = $plugin;
						}
					}
				}
			}

			$data = array(
				'required_plugins'             => $response,
				'third_party_required_plugins' => $third_party_required_plugins,
			);

			if ( defined( 'WP_CLI' ) ) {
				return $data;
			} else {
				// Send response.
				wp_send_json_success( $data );
			}

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
		public function pro_plugin_exist( $lite_version = '' ) {

			// Lite init => Pro init.
			$plugins = apply_filters(
				'astra_sites_pro_plugin_exist',
				array(
					'beaver-builder-lite-version/fl-builder.php' => array(
						'slug' => 'bb-plugin',
						'init' => 'bb-plugin/fl-builder.php',
						'name' => 'Beaver Builder Plugin',
					),
					'ultimate-addons-for-beaver-builder-lite/bb-ultimate-addon.php' => array(
						'slug' => 'bb-ultimate-addon',
						'init' => 'bb-ultimate-addon/bb-ultimate-addon.php',
						'name' => 'Ultimate Addon for Beaver Builder',
					),
					'wpforms-lite/wpforms.php' => array(
						'slug' => 'wpforms',
						'init' => 'wpforms/wpforms.php',
						'name' => 'WPForms',
					),
				),
				$lite_version
			);

			if ( isset( $plugins[ $lite_version ] ) ) {

				// Pro plugin directory exist?
				if ( file_exists( WP_PLUGIN_DIR . '/' . $plugins[ $lite_version ]['init'] ) ) {
					return $plugins[ $lite_version ];
				}
			}

			return false;
		}

		/**
		 * Get astra demos.
		 *
		 * @since 1.0.0
		 *
		 * @param  array  $term_slug Term Slug.
		 * @param  array  $args      For selecting the demos (Search terms, pagination etc).
		 * @param  string $force     Force import.
		 * @return $array            Term response.
		 */
		public function get_terms( $term_slug = '', $args = array(), $force = false ) {

			$defaults = array(
				'_fields' => 'id,name,slug,count',
			);
			$args     = wp_parse_args( (array) $args, $defaults );

			$terms_data = get_transient( 'astra-sites-term-' . $term_slug );

			if ( empty( $terms_data ) || $force ) {
				$url = add_query_arg( $args, $this->get_api_url() . $term_slug );

				$api_args = array(
					'timeout' => 60,
				);

				$success  = false;
				$response = wp_remote_get( $url, $api_args );
				if ( ! is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) === 200 ) {
					$request_term_data = json_decode( wp_remote_retrieve_body( $response ), true );

					if ( ! isset( $request_term_data['code'] ) ) {
						$success        = true;
						$new_terms_data = array();
						foreach ( $request_term_data as $key => $request_term ) {
							$new_terms_data[ $request_term['id'] ] = $request_term['name'];
						}
						set_transient( 'astra-sites-term-' . $term_slug, $new_terms_data, WEEK_IN_SECONDS );
					}
				}
			} else {
				$success = true;
			}

			return array(
				'success' => $success,
				'data'    => $terms_data,
			);
		}

		/**
		 * Get Term IDs
		 *
		 * @since 1.4.0
		 *
		 * @param  string $term_slug   Term slug.
		 * @param  string $search_term Search term.
		 * @param  array  $args        Term query arguments.
		 * @return array               Term response.
		 */
		private function get_term_ids( $term_slug = '', $search_term = '', $args = array() ) {
			$term_args = array();

			if ( ! empty( $search_term ) ) {
				$term_args = array(
					'search' => $search_term,
				);
			}

			$term_response = (array) Astra_Sites::get_instance()->get_terms( $term_slug, $term_args, true );

			WP_CLI::line( 'Requesting "' . ucwords( str_replace( '-', ' ', $term_slug ) ) . '"' );

			if ( ! empty( $search_term ) ) {
				if ( ! empty( $term_response ) && is_array( $term_response['data'] ) ) {
					$args[ $term_slug ] = implode( ',', array_keys( $term_response['data'] ) );
				}
			}

			return array(
				'args'  => $args,
				'terms' => $term_response,
			);
		}

		/**
		 * Get Sites
		 *
		 * @since 1.4.0
		 *
		 * @param  string  $post_slug  Post slug.
		 * @param  array   $args       Post query arguments.
		 * @param  boolean $force      Force import.
		 * @param  array   $assoc_args Associate arguments.
		 * @return array
		 */
		public function get_sites( $post_slug = '', $args = array(), $force = false, $assoc_args = array() ) {

			// Add page builders.
			$page_builder  = isset( $assoc_args['page-builder'] ) ? $assoc_args['page-builder'] : '';
			$response      = $this->get_term_ids( 'astra-site-page-builder', $page_builder, $args );
			$args          = $response['args'];
			$page_builders = $response['terms'];

			// Add type.
			$type     = isset( $assoc_args['type'] ) ? $assoc_args['type'] : '';
			$response = $this->get_term_ids( 'astra-sites-type', $type, $args );
			$args     = $response['args'];
			$types    = $response['terms'];

			// Add categories.
			$category   = isset( $assoc_args['category'] ) ? $assoc_args['category'] : '';
			$response   = $this->get_term_ids( 'astra-site-category', $category, $args );
			$args       = $response['args'];
			$categories = $response['terms'];

			// Add tags.
			$tag      = isset( $assoc_args['tag'] ) ? $assoc_args['tag'] : '';
			$response = $this->get_term_ids( 'astra-sites-tag', $tag, $args );
			$args     = $response['args'];
			$tags     = $response['terms'];

			// Site list.
			$sites = (array) Astra_Sites::get_instance()->get_posts( 'astra-sites', $args, $force );

			$list = array();
			if ( $sites['success'] ) {
				foreach ( $sites['data'] as $key => $site ) {
					$single_site = array(
						'id'            => $site['id'],
						'slug'          => $site['slug'],
						'title'         => $site['title']['rendered'],
						'url'           => $site['astra-site-url'],
						'type'          => $site['astra-site-type'],
						'categories'    => array(),
						'tags'          => array(),
						'page_builders' => array(),
					);

					if ( isset( $site['astra-site-category'] ) && ! empty( $categories['data'] ) ) {
						foreach ( $site['astra-site-category'] as $category_key => $category_id ) {
							if ( isset( $categories['data'][ $category_id ] ) ) {
								$single_site['categories'][ $category_id ] = $categories['data'][ $category_id ];
							}
						}
					}

					if ( isset( $site['astra-sites-tag'] ) && ! empty( $tags['data'] ) ) {
						foreach ( $site['astra-sites-tag'] as $tag_key => $tag_id ) {
							if ( isset( $tags['data'][ $tag_id ] ) ) {
								$single_site['tags'][ $tag_id ] = $tags['data'][ $tag_id ];
							}
						}
					}

					if ( isset( $site['astra-site-page-builder'] ) && ! empty( $page_builders['data'] ) ) {
						foreach ( $site['astra-site-page-builder'] as $page_builder_key => $page_builder_id ) {
							if ( isset( $page_builders['data'][ $page_builder_id ] ) ) {
								$single_site['page_builders'][ $page_builder_id ] = $page_builders['data'][ $page_builder_id ];
							}
						}
					}

					$list[] = $single_site;
				}
			}

			return $list;
		}

		/**
		 * Get Posts
		 *
		 * @since 1.4.0
		 *
		 * @param  string  $post_slug  Post slug.
		 * @param  array   $args       Post query arguments.
		 * @param  boolean $force      Force import.
		 * @return array
		 */
		public function get_posts( $post_slug = '', $args = array(), $force = false ) {

			$args = wp_parse_args( (array) $args, array() );

			$all_posts = get_transient( 'astra-sites-post-' . $post_slug );

			if ( empty( $all_posts ) || $force ) {
				$url = add_query_arg( $args, $this->get_api_url() . $post_slug );

				$api_args = array(
					'timeout' => 60,
				);

				$success  = false;
				$response = wp_remote_get( $url, $api_args );
				if ( ! is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) === 200 ) {
					$all_posts = json_decode( wp_remote_retrieve_body( $response ), true );

					if ( ! isset( $all_posts['code'] ) ) {
						$success = true;
						set_transient( 'astra-sites-post-' . $post_slug, $all_posts, WEEK_IN_SECONDS );
					}
				}
			} else {
				$success = true;
			}

			return array(
				'success' => $success,
				'data'    => $all_posts,
			);
		}

		/**
		 * Getter for $api_url
		 *
		 * @since  1.0.0
		 */
		public function get_api_url() {
			return apply_filters( 'astra_sites_api_url', 'https://websitedemos.net/wp-json/wp/v2/' );
		}
	}

	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	Astra_Sites::get_instance();

endif;
