<?php
/**
 * Single Page Background Process
 *
 * @package Astra Sites
 * @since 2.5.0
 */

if ( ! class_exists( 'Astra_Sites_Batch_Site_Import' ) ) :

	/**
	 * Background Process
	 *
	 * @since 2.5.0
	 */
	class Astra_Sites_Batch_Site_Import {

		/**
		 * Instance
		 *
		 * @since 2.5.0
		 *
		 * @access private
		 * @var object Class object.
		 */
		private static $instance;

		/**
		 * Current Site Data
		 *
		 * @since 2.5.0
		 *
		 * @access private
		 * @var object Class object.
		 */
		public $current_site_data;

		/**
		 * Process All
		 *
		 * @since 2.5.0
		 * @var object Class object.
		 * @access public
		 */
		public static $site_import_batch;

		/**
		 * Initiator
		 *
		 * @since 2.5.0
		 *
		 * @return object initialized object of class.
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * @since 2.5.0
		 */
		public function __construct() {

			require_once ASTRA_SITES_DIR . 'inc/classes/batch-import/class-astra-sites-batch-site-import-batch.php';

			/** WordPress Plugin Administration API */
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			require_once ABSPATH . 'wp-admin/includes/update.php';

			self::$site_import_batch = new Astra_Sites_Batch_Site_Import_Batch();

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu_item' ), 99 );
			add_action( 'wp_ajax_astra_sites_check_import_status', array( $this, 'check_import_status' ) );
			add_filter( 'wp_astra_sites_site_import_batch_cron_interval', array( $this, 'set_cron_interval' ) );

			$status = $this->get_status();

			if (
				! empty( $status ) &&
				( isset( $status['step'] ) && ( 'complete' !== $status['step'] && 'fail' !== $status['step'] ) )
			) {
				add_filter( 'astra_sites_process_sync_batch', '__return_false' );
				add_filter( 'astra_sites_process_auto_sync_library', '__return_false' );
			}
		}

		/**
		 * Set Cron Interval
		 *
		 * @param int $interval Cron interval time in minutes.
		 */
		public function set_cron_interval( $interval = 5 ) {
			return 1;
		}

		/**
		 * Check Import Status
		 */
		public function check_import_status() {

			// Verify Nonce.
			check_ajax_referer( 'astra-sites-import-status', '_ajax_nonce' );

			$schedule = wp_get_scheduled_event( 'wp_astra_sites_site_import_batch_cron' );
			$time     = '';
			if ( $schedule ) {
				$time = human_time_diff( $schedule->timestamp );
			}

			wp_send_json_success(
				array(
					'response' => $this->get_status(),
					'time'     => $time,
				)
			);
		}

		/**
		 * Get status
		 */
		public function get_status() {
			return get_option( 'astra_sites_site_import_batch_status', array() );
		}

		/**
		 * Update Import Status
		 *
		 * @param array $args   Update status arguments.
		 */
		public function update_status( $args = array() ) {

			$status = $this->get_status();

			$args = wp_parse_args( $args, $status );

			update_option( 'astra_sites_site_import_batch_status', $args );
		}

		/**
		 * Admin bar menu item
		 *
		 * @param instance $admin_bar   Admin bar.
		 */
		public function admin_bar_menu_item( $admin_bar = '' ) {
			if ( ! is_admin() ) {
				return;
			}

			$status = $this->get_status();
			if ( empty( $status ) || ( isset( $status['step'] ) && 'complete' === $status['step'] ) ) {
				return;
			}

			$admin_bar->add_menu(
				array(
					'title'  => '<a id="astra-sites-import-status-admin-bar" href="' . admin_url( 'themes.php?page=starter-templates&action=site-import' ) . '">' . esc_html__( 'Import Status', 'astra-sites' ) . '</a>',
					'id'     => 'astra-sites-menu-item',
					'parent' => false,
				)
			);
		}

		/**
		 * Enqueue Scripts
		 */
		public function enqueue_scripts() {

			wp_enqueue_style( 'astra-sites-import-status', ASTRA_SITES_URI . 'inc/assets/css/import-status.css', null, ASTRA_SITES_VER, 'all' );

			$status = $this->get_status();
			if ( ! empty( $status ) && ( isset( $status['step'] ) && 'complete' !== $status['step'] ) ) {
				wp_enqueue_script( 'astra-sites-import-status', ASTRA_SITES_URI . 'inc/assets/js/import-status.js', array( 'jquery' ), ASTRA_SITES_VER, true );
				wp_localize_script(
					'astra-sites-import-status',
					'AstraSitesImportStatusVars',
					array(
						'ajaxurl'     => esc_url( admin_url( 'admin-ajax.php' ) ),
						'_ajax_nonce' => wp_create_nonce( 'astra-sites-import-status' ),
					)
				);
			}
		}

		/**
		 * Import
		 *
		 * @param int     $site_id  Site ID.
		 * @param boolean $reset    Reset and import.
		 */
		public function import( $site_id = 0, $reset = false ) {

			/**
			 * Validate site ID.
			 */
			if ( ! $site_id ) {
				$this->update_status(
					array(
						'step'     => 'fail',
						'message'  => esc_html__( 'Invalid site ID.', 'astra-sites' ),
						'end_time' => time(),
					)
				);
				return null;
			}

			/**
			 * Reset
			 */
			if ( false === $reset ) {
				$status = $this->get_status();

				if ( ! empty( $status ) ) {
					if ( isset( $status['step'] ) && 'complete' === $status['step'] ) {
						return array(
							'status'  => 'complete',
							'message' => esc_html__( 'Site import process is complete.', 'astra-sites' ),
						);
					} else {
						return array(
							'status'  => 'processing',
							'message' => esc_html__( 'Site import is in process.', 'astra-sites' ),
						);
					}
				}
			}

			// Set Site Data.
			$demo_data = $this->get_site_data( $site_id );

			// Invalid Site ID.
			if ( is_wp_error( $demo_data ) ) {
				$this->update_status(
					array(
						'step'     => 'fail',
						'status'   => 'invalid_site_id',
						'end_time' => time(),
					)
				);
				return;
			} elseif ( isset( $demo_data['site-type'] ) && 'free' !== $demo_data['site-type'] ) {
				$this->update_status(
					array(
						'step'     => 'fail',
						'status'   => 'premium_sites',
						'end_time' => time(),
					)
				);
				return;
			}

			// Preparing Import.
			$this->update_status(
				array(
					'step'       => 'preparing',
					'message'    => esc_html__( 'Preparing Site Import', 'astra-sites' ),
					'start_time' => time(),
				)
			);

			// Process Site Import.
			$this->process_site_import_batch( $site_id );

			return array(
				'status'  => 'started',
				'message' => esc_html__( 'Site import started.', 'astra-sites' ),
			);
		}

		/**
		 * Process Batch
		 *
		 * @since 2.5.0
		 *
		 * @param int $site_id  Site ID.
		 * @return mixed
		 */
		public function process_site_import_batch( $site_id = 0 ) {

			self::$site_import_batch->push_to_queue(
				array(
					'instance' => self::get_instance(),
					'method'   => 'import_queue_1',
					'args'     => $site_id,
				)
			);
			self::$site_import_batch->push_to_queue(
				array(
					'instance' => self::get_instance(),
					'method'   => 'import_queue_2',
					'args'     => $site_id,
				)
			);
			self::$site_import_batch->push_to_queue(
				array(
					'instance' => self::get_instance(),
					'method'   => 'import_queue_3',
					'args'     => $site_id,
				)
			);

			self::$site_import_batch->save()->dispatch();
		}

		/**
		 * Import Batch One
		 *
		 * @param int $site_id  Site ID.
		 */
		public function import_queue_1( $site_id = 0 ) {

			// Install Required Plugins.
			$this->update_status(
				array(
					'step'    => 'install_plugins',
					'message' => esc_html__( 'Installing Required Plugins', 'astra-sites' ),
				)
			);
			$this->install_required_plugins( $site_id );

		}

		/**
		 * Import Batch Two
		 *
		 * @param int $site_id  Site ID.
		 */
		public function import_queue_2( $site_id = 0 ) {

			$this->update_status(
				array(
					'step'    => 'import_contact_forms',
					'message' => esc_html__( 'Importing Contact Forms', 'astra-sites' ),
				)
			);

			// Import Flows & Steps.
			$this->import_flows_and_steps( $site_id );

			// Import WP Forms.
			$this->import_wp_forms( $site_id );

			$this->update_status(
				array(
					'step'    => 'import_customizer_settings',
					'message' => esc_html__( 'Setting up the Theme', 'astra-sites' ),
				)
			);

			// Import Customizer Settings.
			$this->import_customizer_settings( $site_id );

			$this->update_status(
				array(
					'step'    => 'import_content',
					'message' => esc_html__( 'Importing Media, Posts, and Pages', 'astra-sites' ),
				)
			);

			// Import XML.
			$this->import_xml( $site_id );

			// Import Site Options.
			$this->update_status(
				array(
					'step'    => 'import_options',
					'message' => esc_html__( 'Importing Site Options', 'astra-sites' ),
				)
			);
			$this->import_site_options( $site_id );

			// Import Widgets.
			$this->update_status(
				array(
					'step'    => 'import_widgets',
					'message' => esc_html__( 'Importing Sidebar and Widgets', 'astra-sites' ),
				)
			);
			$this->import_widgets( $site_id );

		}

		/**
		 * Import Batch Three
		 *
		 * @param int $site_id  Site ID.
		 */
		public function import_queue_3( $site_id = 0 ) {

			// Import End.
			$this->import_end( $site_id );

			$this->update_status(
				array(
					'step'     => 'complete',
					'message'  => esc_html__( 'Import Complete', 'astra-sites' ),
					'end_time' => time(),
				)
			);
		}

		/**
		 * Validate ID
		 *
		 * @param int $site_id  Site ID.
		 */
		public function validate_id( $site_id ) {
			$site_id = isset( $site_id ) ? absint( $site_id ) : 0;
			if ( ! $site_id ) {
				$this->update_status(
					array(
						'step'     => 'fail',
						'message'  => esc_html__( 'Invalid site ID.', 'astra-sites' ),
						'end_time' => time(),
					)
				);
				return null;
			}
		}

		/**
		 * Set site data
		 *
		 * @param int $site_id  Site ID.
		 */
		public function set_site_data( $site_id ) {

		}

		/**
		 * Install Required Plugins
		 */
		public function install_required_plugins() {

			/** WordPress Plugin Administration API */
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			require_once ABSPATH . 'wp-admin/includes/update.php';

			$demo_data = get_option( 'astra_sites_import_data', array() );

			if ( isset( $demo_data['required-plugins'] ) ) {

				$plugins = (array) $demo_data['required-plugins'];

				if ( ! empty( $plugins ) ) {
					$plugin_status = Astra_Sites::get_instance()->required_plugin( $plugins, $demo_data['astra-site-options-data'], $demo_data['astra-enabled-extensions'] );

					// Install Plugins.
					if ( ! empty( $plugin_status['required_plugins']['notinstalled'] ) ) {

						foreach ( $plugin_status['required_plugins']['notinstalled'] as $key => $plugin ) {
							if ( isset( $plugin['slug'] ) ) {

								// Install plugin.
								$this->install_plugin( $plugin );

								// Activate plugin.
								Astra_Sites::get_instance()->required_plugin_activate( $plugin['init'], $demo_data['astra-site-options-data'], $demo_data['astra-enabled-extensions'] );
							}
						}
					}

					// Activate Plugins.
					if ( ! empty( $plugin_status['required_plugins']['inactive'] ) ) {
						foreach ( $plugin_status['required_plugins']['inactive'] as $key => $plugin ) {
							if ( isset( $plugin['init'] ) ) {
								Astra_Sites::get_instance()->required_plugin_activate( $plugin['init'], $demo_data['astra-site-options-data'], $demo_data['astra-enabled-extensions'] );
							}
						}
					}
				}
			}
		}

		/**
		 * Install Plugin
		 *
		 * @param array $plugin Required Plugin.
		 */
		public function install_plugin( $plugin = array() ) {

			if ( ! isset( $plugin['slug'] ) || empty( $plugin['slug'] ) ) {
				return esc_html__( 'Invalid plugin slug', 'astra-sites' );
			}

			include_once ABSPATH . 'wp-admin/includes/plugin.php';
			include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

			$api = plugins_api(
				'plugin_information',
				array(
					'slug'   => sanitize_key( wp_unslash( $plugin['slug'] ) ),
					'fields' => array(
						'sections' => false,
					),
				)
			);

			if ( is_wp_error( $api ) ) {
				$status['errorMessage'] = $api->get_error_message();
				return $status;
			}

			$skin     = new WP_Ajax_Upgrader_Skin();
			$upgrader = new Plugin_Upgrader( $skin );
			$result   = $upgrader->install( $api->download_link );

			if ( is_wp_error( $result ) ) {
				return $result->get_error_message();
			} elseif ( is_wp_error( $skin->result ) ) {
				return $skin->result->get_error_message();
			} elseif ( $skin->get_errors()->has_errors() ) {
				return $skin->get_error_messages();
			} elseif ( is_null( $result ) ) {
				global $wp_filesystem;

				// Pass through the error from WP_Filesystem if one was raised.
				if ( $wp_filesystem instanceof WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->has_errors() ) {
					return esc_html( $wp_filesystem->errors->get_error_message() );
				}

				return esc_html__( 'Unable to connect to the filesystem. Please confirm your credentials.', 'astra-sites' );
			}

			/* translators: %s plugin name. */
			return sprintf( __( 'Successfully installed "%s" plugin!', 'astra-sites' ), $api->name );
		}

		/**
		 * Backup Customizer Settings
		 */
		public function backup_customizer_settings() {
			Astra_Sites::get_instance()->backup_settings();
		}

		/**
		 * Import Flows and Steps
		 */
		public function import_flows_and_steps() {
			$demo_data = get_option( 'astra_sites_import_data', array() );
			if ( ! empty( $demo_data ) && isset( $demo_data['astra-site-cartflows-path'] ) && ! empty( $demo_data['astra-site-cartflows-path'] ) ) {
				Astra_Sites_Importer::get_instance()->import_cartflows( $demo_data['astra-site-cartflows-path'] );
			}
		}

		/**
		 * Import WP Forms
		 */
		public function import_wp_forms() {

			/** WordPress Post Administration API */
			require_once ABSPATH . 'wp-admin/includes/post.php';

			$demo_data = get_option( 'astra_sites_import_data', array() );
			/**
			 * Import WP Forms.
			 */
			if ( isset( $demo_data['astra-site-wpforms-path'] ) && ! empty( $demo_data['astra-site-wpforms-path'] ) ) {
				Astra_Sites_Importer::get_instance()->import_wpforms( $demo_data['astra-site-wpforms-path'] );
			}
		}

		/**
		 * Import Customizer Settings.
		 */
		public function import_customizer_settings() {
			$demo_data = get_option( 'astra_sites_import_data', array() );
			Astra_Sites_Importer::get_instance()->import_customizer_settings( $demo_data['astra-site-customizer-data'] );
		}

		/**
		 * Import Content from XML/WXR.
		 */
		public function import_xml() {
			$demo_data = get_option( 'astra_sites_import_data', array() );

			if ( isset( $demo_data['astra-site-wxr-path'] ) && ! empty( $demo_data['astra-site-wxr-path'] ) ) {
				$xml_path = Astra_Sites_Helper::download_file( $demo_data['astra-site-wxr-path'] );

				Astra_WXR_Importer::instance()->sse_import( $xml_path['data']['file'] );
			}
		}

		/**
		 * Import Site Options.
		 */
		public function import_site_options() {
			$demo_data = get_option( 'astra_sites_import_data', array() );

			if ( isset( $demo_data['astra-site-options-data'] ) && ! empty( $demo_data['astra-site-options-data'] ) ) {
				Astra_Sites_Importer::get_instance()->import_options( $demo_data['astra-site-options-data'] );
			}
		}

		/**
		 * Import Widgets.
		 */
		public function import_widgets() {
			$demo_data = get_option( 'astra_sites_import_data', array() );

			if ( isset( $demo_data['astra-site-widgets-data'] ) && ! empty( $demo_data['astra-site-widgets-data'] ) ) {
				Astra_Sites_Importer::get_instance()->import_widgets( $demo_data['astra-site-widgets-data'] );
			}
		}

		/**
		 * Import End.
		 */
		public function import_end() {
			Astra_Sites_Importer::get_instance()->import_end();
		}

		/**
		 * Direct Import Site without Batch
		 *
		 * @param int $site_id  Site ID.
		 */
		public function direct_import( $site_id = 0 ) {

			$this->validate_id( $site_id );

			$this->set_site_data( $site_id );

			$this->install_required_plugins();

			$this->backup_customizer_settings();

			$this->reset_previously_import_site();

			$this->import_flows_and_steps();

			$this->import_wp_forms();

			$this->import_customizer_settings();

			$this->import_xml();

			$this->import_site_options();

			$this->import_widgets();

			$this->import_end();

		}

		/**
		 * Reset Previously Imported Site
		 */
		public function reset_previously_import_site() {

			// Get tracked data.
			$reset_data = Astra_Sites::get_instance()->get_reset_data();

			// Delete tracked posts.
			if ( isset( $reset_data['reset_posts'] ) && ! empty( $reset_data['reset_posts'] ) ) {
				foreach ( $reset_data['reset_posts'] as $key => $post_id ) {
					Astra_Sites_Importer::get_instance()->delete_imported_posts( $post_id );
				}
			}
			// Delete tracked terms.
			if ( isset( $reset_data['reset_terms'] ) && ! empty( $reset_data['reset_terms'] ) ) {
				foreach ( $reset_data['reset_terms'] as $key => $post_id ) {
					Astra_Sites_Importer::get_instance()->delete_imported_terms( $post_id );
				}
			}
			// Delete tracked WP forms.
			if ( isset( $reset_data['reset_wp_forms'] ) && ! empty( $reset_data['reset_wp_forms'] ) ) {
				foreach ( $reset_data['reset_wp_forms'] as $key => $post_id ) {
					Astra_Sites_Importer::get_instance()->delete_imported_wp_forms( $post_id );
				}
			}

			// Delete Customizer Data.
			Astra_Sites_Importer::get_instance()->reset_customizer_data();

			// Delete Site Options.
			Astra_Sites_Importer::get_instance()->reset_site_options();

			// Delete Widgets Data.
			Astra_Sites_Importer::get_instance()->reset_widgets_data();
		}

		/**
		 * Get Site Data by Site ID
		 *
		 * @since 2.5.0
		 *
		 * @param  int $id        Site ID.
		 * @return array
		 */
		public function get_site_data( $id ) {
			if ( empty( $this->current_site_data ) ) {
				// @todo Use Astra_Sites::get_instance()->api_request() instead of below function.
				$this->current_site_data = Astra_Sites_Importer::get_instance()->get_single_demo( $id );
				update_option( 'astra_sites_import_data', $this->current_site_data );
			}

			return $this->current_site_data;
		}
	}

	Astra_Sites_Batch_Site_Import::get_instance();

endif;
