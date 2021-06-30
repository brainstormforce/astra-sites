<?php
/**
 * Astra Sites Importer
 *
 * @since  1.0.0
 * @package Astra Sites
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Astra_Sites_Importer' ) ) {

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
		public static $instance = null;

		/**
		 * Set Instance
		 *
		 * @since  1.0.0
		 *
		 * @return object Class object.
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor.
		 *
		 * @since  1.0.0
		 */
		public function __construct() {

			require_once ASTRA_SITES_DIR . 'inc/classes/class-astra-sites-importer-log.php';
			require_once ASTRA_SITES_DIR . 'inc/importers/class-astra-sites-helper.php';
			require_once ASTRA_SITES_DIR . 'inc/importers/class-astra-widget-importer.php';
			require_once ASTRA_SITES_DIR . 'inc/importers/class-astra-customizer-import.php';
			require_once ASTRA_SITES_DIR . 'inc/importers/class-astra-site-options-import.php';

			// Import AJAX.
			add_action( 'wp_ajax_astra-sites-import-wpforms', array( $this, 'import_wpforms' ) );
			add_action( 'wp_ajax_astra-sites-import-cartflows', array( $this, 'import_cartflows' ) );
			add_action( 'wp_ajax_astra-sites-import-customizer-settings', array( $this, 'import_customizer_settings' ) );
			add_action( 'wp_ajax_astra-sites-import-prepare-xml', array( $this, 'prepare_xml_data' ) );
			add_action( 'wp_ajax_astra-sites-import-options', array( $this, 'import_options' ) );
			add_action( 'wp_ajax_astra-sites-import-widgets', array( $this, 'import_widgets' ) );
			add_action( 'wp_ajax_astra-sites-import-end', array( $this, 'import_end' ) );

			// Hooks in AJAX.
			add_action( 'astra_sites_import_complete', array( $this, 'after_batch_complete' ) );
			add_action( 'init', array( $this, 'load_importer' ) );

			require_once ASTRA_SITES_DIR . 'inc/importers/batch-processing/class-astra-sites-batch-processing.php';

			add_action( 'astra_sites_image_import_complete', array( $this, 'after_batch_complete' ) );

			// Reset Customizer Data.
			add_action( 'wp_ajax_astra-sites-reset-customizer-data', array( $this, 'reset_customizer_data' ) );
			add_action( 'wp_ajax_astra-sites-reset-site-options', array( $this, 'reset_site_options' ) );
			add_action( 'wp_ajax_astra-sites-reset-widgets-data', array( $this, 'reset_widgets_data' ) );

			// Reset Post & Terms.
			add_action( 'wp_ajax_astra-sites-delete-posts', array( $this, 'delete_imported_posts' ) );
			add_action( 'wp_ajax_astra-sites-delete-wp-forms', array( $this, 'delete_imported_wp_forms' ) );
			add_action( 'wp_ajax_astra-sites-delete-terms', array( $this, 'delete_imported_terms' ) );

			if ( version_compare( get_bloginfo( 'version' ), '5.1.0', '>=' ) ) {
				add_filter( 'http_request_timeout', array( $this, 'set_timeout_for_images' ), 10, 2 );
			}
		}

		/**
		 * Set the timeout for the HTTP request by request URL.
		 *
		 * E.g. If URL is images (jpg|png|gif|jpeg) are from the domain `https://websitedemos.net` then we have set the timeout by 30 seconds. Default 5 seconds.
		 *
		 * @since 1.3.8
		 *
		 * @param int    $timeout_value Time in seconds until a request times out. Default 5.
		 * @param string $url           The request URL.
		 */
		public function set_timeout_for_images( $timeout_value, $url ) {

			// URL not contain `https://websitedemos.net` then return $timeout_value.
			if ( strpos( $url, 'https://websitedemos.net' ) === false ) {
				return $timeout_value;
			}

			// Check is image URL of type jpg|png|gif|jpeg.
			if ( astra_sites_is_valid_image( $url ) ) {
				$timeout_value = 300;
			}

			return $timeout_value;
		}

		/**
		 * Load WordPress WXR importer.
		 */
		public function load_importer() {
			require_once ASTRA_SITES_DIR . 'inc/importers/wxr-importer/class-astra-wxr-importer.php';
		}

		/**
		 * Change flow status
		 *
		 * @since 2.0.0
		 *
		 * @param  array $args Flow query args.
		 * @return array Flow query args.
		 */
		public function change_flow_status( $args ) {
			$args['post_status'] = 'publish';
			return $args;
		}

		/**
		 * Track Flow
		 *
		 * @since 2.0.0
		 *
		 * @param  integer $flow_id Flow ID.
		 * @return void
		 */
		public function track_flows( $flow_id ) {
			astra_sites_error_log( 'Flow ID ' . $flow_id );
			Astra_WXR_Importer::instance()->track_post( $flow_id );
		}

		/**
		 * Import WP Forms
		 *
		 * @since 1.2.14
		 * @since 1.4.0 The `$wpforms_url` was added.
		 *
		 * @param  string $wpforms_url WP Forms JSON file URL.
		 * @return void
		 */
		public function import_wpforms( $wpforms_url = '' ) {

			if ( ! defined( 'WP_CLI' ) && wp_doing_ajax() ) {
				// Verify Nonce.
				check_ajax_referer( 'astra-sites', '_ajax_nonce' );

				if ( ! current_user_can( 'customize' ) ) {
					wp_send_json_error( __( 'You are not allowed to perform this action', 'astra-sites' ) );
				}
			}

			$wpforms_url = ( isset( $_REQUEST['wpforms_url'] ) ) ? urldecode( $_REQUEST['wpforms_url'] ) : $wpforms_url;
			$ids_mapping = array();

			if ( ! empty( $wpforms_url ) && function_exists( 'wpforms_encode' ) ) {

				// Download JSON file.
				$file_path = Astra_Sites_Helper::download_file( $wpforms_url );

				if ( $file_path['success'] ) {
					if ( isset( $file_path['data']['file'] ) ) {

						$ext = strtolower( pathinfo( $file_path['data']['file'], PATHINFO_EXTENSION ) );

						if ( 'json' === $ext ) {
							$forms = json_decode( Astra_Sites::get_instance()->get_filesystem()->get_contents( $file_path['data']['file'] ), true );

							if ( ! empty( $forms ) ) {

								foreach ( $forms as $form ) {
									$title = ! empty( $form['settings']['form_title'] ) ? $form['settings']['form_title'] : '';
									$desc  = ! empty( $form['settings']['form_desc'] ) ? $form['settings']['form_desc'] : '';

									$new_id = post_exists( $title );

									if ( ! $new_id ) {
										$new_id = wp_insert_post(
											array(
												'post_title'   => $title,
												'post_status'  => 'publish',
												'post_type'    => 'wpforms',
												'post_excerpt' => $desc,
											)
										);

										if ( defined( 'WP_CLI' ) ) {
											WP_CLI::line( 'Imported Form ' . $title );
										}

										// Set meta for tracking the post.
										update_post_meta( $new_id, '_astra_sites_imported_wp_forms', true );
										Astra_Sites_Importer_Log::add( 'Inserted WP Form ' . $new_id );
									}

									if ( $new_id ) {

										// ID mapping.
										$ids_mapping[ $form['id'] ] = $new_id;

										$form['id'] = $new_id;
										wp_update_post(
											array(
												'ID' => $new_id,
												'post_content' => wpforms_encode( $form ),
											)
										);
									}
								}
							}
						}
					}
				}
			}

			update_option( 'astra_sites_wpforms_ids_mapping', $ids_mapping, 'no' );

			if ( defined( 'WP_CLI' ) ) {
				WP_CLI::line( 'WP Forms Imported.' );
			} elseif ( wp_doing_ajax() ) {
				wp_send_json_success( $ids_mapping );
			}
		}

		/**
		 * Import CartFlows
		 *
		 * @since 2.0.0
		 *
		 * @param  string $url Cartflows JSON file URL.
		 * @return void
		 */
		public function import_cartflows( $url = '' ) {

			// Make the flow publish.
			add_action( 'cartflows_flow_importer_args', array( $this, 'change_flow_status' ) );
			add_action( 'cartflows_flow_imported', array( $this, 'track_flows' ) );
			add_action( 'cartflows_step_imported', array( $this, 'track_flows' ) );

			$url = ( isset( $_REQUEST['cartflows_url'] ) ) ? urldecode( $_REQUEST['cartflows_url'] ) : urldecode( $url ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! empty( $url ) && is_callable( 'CartFlows_Importer::get_instance' ) ) {

				// Download JSON file.
				$file_path = Astra_Sites_Helper::download_file( $url );

				if ( $file_path['success'] ) {
					if ( isset( $file_path['data']['file'] ) ) {

						$ext = strtolower( pathinfo( $file_path['data']['file'], PATHINFO_EXTENSION ) );

						if ( 'json' === $ext ) {
							$flows = json_decode( Astra_Sites::get_instance()->get_filesystem()->get_contents( $file_path['data']['file'] ), true );

							if ( ! empty( $flows ) ) {
								CartFlows_Importer::get_instance()->import_from_json_data( $flows );
							}
						}
					}
				}
			}

			if ( defined( 'WP_CLI' ) ) {
				WP_CLI::line( 'Imported from ' . $url );
			} elseif ( wp_doing_ajax() ) {
				wp_send_json_success( $url );
			}
		}

		/**
		 * Import Customizer Settings.
		 *
		 * @since 1.0.14
		 * @since 1.4.0  The `$customizer_data` was added.
		 *
		 * @param  array $customizer_data Customizer Data.
		 * @return void
		 */
		public function import_customizer_settings( $customizer_data = array() ) {

			if ( ! defined( 'WP_CLI' ) && wp_doing_ajax() ) {
				// Verify Nonce.
				check_ajax_referer( 'astra-sites', '_ajax_nonce' );

				if ( ! current_user_can( 'customize' ) ) {
					wp_send_json_error( __( 'You are not allowed to perform this action', 'astra-sites' ) );
				}
			}

			$customizer_data = ( isset( $_POST['customizer_data'] ) ) ? (array) json_decode( stripcslashes( $_POST['customizer_data'] ), 1 ) : $customizer_data;

			if ( ! empty( $customizer_data ) ) {

				Astra_Sites_Importer_Log::add( 'Imported Customizer Settings ' . wp_json_encode( $customizer_data ) );

				// Set meta for tracking the post.
				astra_sites_error_log( 'Customizer Data ' . wp_json_encode( $customizer_data ) );

				update_option( '_astra_sites_old_customizer_data', $customizer_data, 'no' );

				Astra_Customizer_Import::instance()->import( $customizer_data );

				if ( defined( 'WP_CLI' ) ) {
					WP_CLI::line( 'Imported Customizer Settings!' );
				} elseif ( wp_doing_ajax() ) {
					wp_send_json_success( $customizer_data );
				}
			} else {
				if ( defined( 'WP_CLI' ) ) {
					WP_CLI::line( 'Customizer data is empty!' );
				} elseif ( wp_doing_ajax() ) {
					wp_send_json_error( __( 'Customizer data is empty!', 'astra-sites' ) );
				}
			}

		}

		/**
		 * Prepare XML Data.
		 *
		 * @since 1.1.0
		 * @return void
		 */
		public function prepare_xml_data() {

			// Verify Nonce.
			check_ajax_referer( 'astra-sites', '_ajax_nonce' );

			if ( ! current_user_can( 'customize' ) ) {
				wp_send_json_error( __( 'You are not allowed to perform this action', 'astra-sites' ) );
			}

			if ( ! class_exists( 'XMLReader' ) ) {
				wp_send_json_error( __( 'If XMLReader is not available, it imports all other settings and only skips XML import. This creates an incomplete website. We should bail early and not import anything if this is not present.', 'astra-sites' ) );
			}

			$wxr_url = ( isset( $_REQUEST['wxr_url'] ) ) ? urldecode( $_REQUEST['wxr_url'] ) : '';

			if ( isset( $wxr_url ) ) {

				Astra_Sites_Importer_Log::add( 'Importing from XML ' . $wxr_url );

				$overrides = array(
					'wp_handle_sideload' => 'upload',
				);

				// Download XML file.
				$xml_path = Astra_Sites_Helper::download_file( $wxr_url, $overrides );

				if ( $xml_path['success'] ) {

					$post = array(
						'post_title'     => basename( $wxr_url ),
						'guid'           => $xml_path['data']['url'],
						'post_mime_type' => $xml_path['data']['type'],
					);

					astra_sites_error_log( wp_json_encode( $post ) );
					astra_sites_error_log( wp_json_encode( $xml_path ) );

					// as per wp-admin/includes/upload.php.
					$post_id = wp_insert_attachment( $post, $xml_path['data']['file'] );

					astra_sites_error_log( wp_json_encode( $post_id ) );

					if ( is_wp_error( $post_id ) ) {
						wp_send_json_error( __( 'There was an error downloading the XML file.', 'astra-sites' ) );
					} else {

						update_option( 'astra_sites_imported_wxr_id', $post_id, 'no' );
						$attachment_metadata = wp_generate_attachment_metadata( $post_id, $xml_path['data']['file'] );
						wp_update_attachment_metadata( $post_id, $attachment_metadata );
						$data        = Astra_WXR_Importer::instance()->get_xml_data( $xml_path['data']['file'], $post_id );
						$data['xml'] = $xml_path['data'];
						wp_send_json_success( $data );
					}
				} else {
					wp_send_json_error( $xml_path['data'] );
				}
			} else {
				wp_send_json_error( __( 'Invalid site XML file!', 'astra-sites' ) );
			}

		}

		/**
		 * Import Options.
		 *
		 * @since 1.0.14
		 * @since 1.4.0 The `$options_data` was added.
		 *
		 * @param  array $options_data Site Options.
		 * @return void
		 */
		public function import_options( $options_data = array() ) {

			if ( ! defined( 'WP_CLI' ) && wp_doing_ajax() ) {
				// Verify Nonce.
				check_ajax_referer( 'astra-sites', '_ajax_nonce' );

				if ( ! current_user_can( 'customize' ) ) {
					wp_send_json_error( __( 'You are not allowed to perform this action', 'astra-sites' ) );
				}
			}

			$options_data = ( isset( $_POST['options_data'] ) ) ? (array) json_decode( stripcslashes( $_POST['options_data'] ), 1 ) : $options_data;

			if ( ! empty( $options_data ) ) {
				// Set meta for tracking the post.
				if ( is_array( $options_data ) ) {
					Astra_Sites_Importer_Log::add( 'Imported - Site Options ' . wp_json_encode( $options_data ) );
					update_option( '_astra_sites_old_site_options', $options_data, 'no' );
				}

				$options_importer = Astra_Site_Options_Import::instance();
				$options_importer->import_options( $options_data );
				if ( defined( 'WP_CLI' ) ) {
					WP_CLI::line( 'Imported Site Options!' );
				} elseif ( wp_doing_ajax() ) {
					wp_send_json_success( $options_data );
				}
			} else {
				if ( defined( 'WP_CLI' ) ) {
					WP_CLI::line( 'Site options are empty!' );
				} elseif ( wp_doing_ajax() ) {
					wp_send_json_error( __( 'Site options are empty!', 'astra-sites' ) );
				}
			}

		}

		/**
		 * Import Widgets.
		 *
		 * @since 1.0.14
		 * @since 1.4.0 The `$widgets_data` was added.
		 *
		 * @param  string $widgets_data Widgets Data.
		 * @return void
		 */
		public function import_widgets( $widgets_data = '' ) {

			if ( ! defined( 'WP_CLI' ) && wp_doing_ajax() ) {
				// Verify Nonce.
				check_ajax_referer( 'astra-sites', '_ajax_nonce' );

				if ( ! current_user_can( 'customize' ) ) {
					wp_send_json_error( __( 'You are not allowed to perform this action', 'astra-sites' ) );
				}
			}

			$widgets_data = ( isset( $_POST['widgets_data'] ) ) ? (object) json_decode( stripslashes( $_POST['widgets_data'] ) ) : (object) $widgets_data;

			if ( ! empty( $widgets_data ) ) {

				Astra_Widget_Importer::instance()->import_widgets_data( $widgets_data );

				$sidebars_widgets = get_option( 'sidebars_widgets', array() );
				update_option( '_astra_sites_old_widgets_data', $sidebars_widgets, 'no' );
				Astra_Sites_Importer_Log::add( 'Imported - Widgets ' . wp_json_encode( $sidebars_widgets ) );

				if ( defined( 'WP_CLI' ) ) {
					WP_CLI::line( 'Widget Imported!' );
				} elseif ( wp_doing_ajax() ) {
					wp_send_json_success( $widgets_data );
				}
			} else {
				if ( defined( 'WP_CLI' ) ) {
					WP_CLI::line( 'Widget data is empty!' );
				} elseif ( wp_doing_ajax() ) {
					wp_send_json_error( __( 'Widget data is empty!', 'astra-sites' ) );
				}
			}

		}

		/**
		 * Import End.
		 *
		 * @since 1.0.14
		 * @return void
		 */
		public function import_end() {

			if ( ! defined( 'WP_CLI' ) && wp_doing_ajax() ) {
				// Verify Nonce.
				check_ajax_referer( 'astra-sites', '_ajax_nonce' );

				if ( ! current_user_can( 'customize' ) ) {
					wp_send_json_error( __( 'You are not allowed to perform this action', 'astra-sites' ) );
				}
			}

			$demo_data = get_option( 'astra_sites_import_data', array() );

			do_action( 'astra_sites_import_complete', $demo_data );

			update_option( 'astra_sites_import_complete', 'yes', 'no' );

			if ( wp_doing_ajax() ) {
				wp_send_json_success();
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
		public static function get_single_demo( $demo_api_uri ) {

			if ( is_int( $demo_api_uri ) ) {
				$demo_api_uri = Astra_Sites::get_instance()->get_api_url() . 'astra-sites/' . $demo_api_uri;
			}

			// default values.
			$remote_args = array();
			$defaults    = array(
				'id'                          => '',
				'astra-site-widgets-data'     => '',
				'astra-site-customizer-data'  => '',
				'astra-site-options-data'     => '',
				'astra-post-data-mapping'     => '',
				'astra-site-wxr-path'         => '',
				'astra-site-wpforms-path'     => '',
				'astra-enabled-extensions'    => '',
				'astra-custom-404'            => '',
				'required-plugins'            => '',
				'astra-site-taxonomy-mapping' => '',
				'license-status'              => '',
				'site-type'                   => '',
				'astra-site-url'              => '',
			);

			$api_args = apply_filters(
				'astra_sites_api_args',
				array(
					'timeout' => 15,
				)
			);

			// Use this for premium demos.
			$request_params = apply_filters(
				'astra_sites_api_params',
				array(
					'purchase_key' => '',
					'site_url'     => '',
				)
			);

			$demo_api_uri = add_query_arg( $request_params, $demo_api_uri );

			// API Call.
			$response = wp_remote_get( $demo_api_uri, $api_args );

			if ( is_wp_error( $response ) || ( isset( $response->status ) && 0 === $response->status ) ) {
				if ( isset( $response->status ) ) {
					$data = json_decode( $response, true );
				} else {
					return new WP_Error( 'api_invalid_response_code', $response->get_error_message() );
				}
			}

			if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
				return new WP_Error( 'api_invalid_response_code', wp_remote_retrieve_body( $response ) );
			} else {
				$data = json_decode( wp_remote_retrieve_body( $response ), true );
			}

			$data = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! isset( $data['code'] ) ) {
				$remote_args['id']                          = $data['id'];
				$remote_args['astra-site-widgets-data']     = json_decode( $data['astra-site-widgets-data'] );
				$remote_args['astra-site-customizer-data']  = $data['astra-site-customizer-data'];
				$remote_args['astra-site-options-data']     = $data['astra-site-options-data'];
				$remote_args['astra-post-data-mapping']     = $data['astra-post-data-mapping'];
				$remote_args['astra-site-wxr-path']         = $data['astra-site-wxr-path'];
				$remote_args['astra-site-wpforms-path']     = $data['astra-site-wpforms-path'];
				$remote_args['astra-enabled-extensions']    = $data['astra-enabled-extensions'];
				$remote_args['astra-custom-404']            = $data['astra-custom-404'];
				$remote_args['required-plugins']            = $data['required-plugins'];
				$remote_args['astra-site-taxonomy-mapping'] = $data['astra-site-taxonomy-mapping'];
				$remote_args['license-status']              = $data['license-status'];
				$remote_args['site-type']                   = $data['astra-site-type'];
				$remote_args['astra-site-url']              = $data['astra-site-url'];
			}

			// Merge remote demo and defaults.
			return wp_parse_args( $remote_args, $defaults );
		}

		/**
		 * Clear Cache.
		 *
		 * @since  1.0.9
		 */
		public function after_batch_complete() {

			// Clear 'Builder Builder' cache.
			if ( is_callable( 'FLBuilderModel::delete_asset_cache_for_all_posts' ) ) {
				FLBuilderModel::delete_asset_cache_for_all_posts();
			}

			// Clear 'Astra Addon' cache.
			if ( is_callable( 'Astra_Minify::refresh_assets' ) ) {
				Astra_Minify::refresh_assets();
			}

			$this->update_latest_checksums();

			// Flush permalinks.
			flush_rewrite_rules();

			Astra_Sites_Importer_Log::add( 'Complete ' );
		}

		/**
		 * Update Latest Checksums
		 *
		 * Store latest checksum after batch complete.
		 *
		 * @since 2.0.0
		 * @return void
		 */
		public function update_latest_checksums() {
			$latest_checksums = get_site_option( 'astra-sites-last-export-checksums-latest', '' );
			update_site_option( 'astra-sites-last-export-checksums', $latest_checksums, 'no' );
		}

		/**
		 * Reset customizer data
		 *
		 * @since 1.3.0
		 * @return void
		 */
		public function reset_customizer_data() {

			if ( ! defined( 'WP_CLI' ) && wp_doing_ajax() ) {
				// Verify Nonce.
				check_ajax_referer( 'astra-sites', '_ajax_nonce' );

				if ( ! current_user_can( 'customize' ) ) {
					wp_send_json_error( __( 'You are not allowed to perform this action', 'astra-sites' ) );
				}
			}

			Astra_Sites_Importer_Log::add( 'Deleted customizer Settings ' . wp_json_encode( get_option( 'astra-settings', array() ) ) );

			delete_option( 'astra-settings' );

			if ( defined( 'WP_CLI' ) ) {
				WP_CLI::line( 'Deleted Customizer Settings!' );
			} elseif ( wp_doing_ajax() ) {
				wp_send_json_success();
			}
		}

		/**
		 * Reset site options
		 *
		 * @since 1.3.0
		 * @return void
		 */
		public function reset_site_options() {

			if ( ! defined( 'WP_CLI' ) && wp_doing_ajax() ) {
				// Verify Nonce.
				check_ajax_referer( 'astra-sites', '_ajax_nonce' );

				if ( ! current_user_can( 'customize' ) ) {
					wp_send_json_error( __( 'You are not allowed to perform this action', 'astra-sites' ) );
				}
			}

			$options = get_option( '_astra_sites_old_site_options', array() );

			Astra_Sites_Importer_Log::add( 'Deleted - Site Options ' . wp_json_encode( $options ) );

			if ( $options ) {
				foreach ( $options as $option_key => $option_value ) {
					delete_option( $option_key );
				}
			}

			if ( defined( 'WP_CLI' ) ) {
				WP_CLI::line( 'Deleted Site Options!' );
			} elseif ( wp_doing_ajax() ) {
				wp_send_json_success();
			}
		}

		/**
		 * Reset widgets data
		 *
		 * @since 1.3.0
		 * @return void
		 */
		public function reset_widgets_data() {

			if ( ! defined( 'WP_CLI' ) && wp_doing_ajax() ) {
				// Verify Nonce.
				check_ajax_referer( 'astra-sites', '_ajax_nonce' );

				if ( ! current_user_can( 'customize' ) ) {
					wp_send_json_error( __( 'You are not allowed to perform this action', 'astra-sites' ) );
				}
			}

			// Get all old widget ids.
			$old_widgets_data = (array) get_option( '_astra_sites_old_widgets_data', array() );
			$old_widget_ids = array();
			foreach ( $old_widgets_data as $old_sidebar_key => $old_widgets ) {
				if ( ! empty( $old_widgets ) && is_array( $old_widgets ) ) {
					$old_widget_ids = array_merge( $old_widget_ids, $old_widgets );
				}
			}

			// Process if not empty.
			$sidebars_widgets = get_option( 'sidebars_widgets', array() );
			if ( ! empty( $old_widget_ids ) && ! empty( $sidebars_widgets ) ) {

				Astra_Sites_Importer_Log::add( 'DELETED - WIDGETS ' . wp_json_encode( $old_widget_ids ) );

				foreach ( $sidebars_widgets as $sidebar_id => $widgets ) {
					$widgets = (array) $widgets;

					if ( ! empty( $widgets ) && is_array( $widgets ) ) {
						foreach ( $widgets as $widget_id ) {

							if ( in_array( $widget_id, $old_widget_ids, true ) ) {
								Astra_Sites_Importer_Log::add( 'DELETED - WIDGET ' . $widget_id );

								// Move old widget to inacitve list.
								$sidebars_widgets['wp_inactive_widgets'][] = $widget_id;

								// Remove old widget from sidebar.
								$sidebars_widgets[ $sidebar_id ] = array_diff( $sidebars_widgets[ $sidebar_id ], array( $widget_id ) );
							}
						}
					}
				}

				update_option( 'sidebars_widgets', $sidebars_widgets );
			}

			if ( defined( 'WP_CLI' ) ) {
				WP_CLI::line( 'Deleted Widgets!' );
			} elseif ( wp_doing_ajax() ) {
				wp_send_json_success();
			}
		}

		/**
		 * Delete imported posts
		 *
		 * @since 1.3.0
		 * @since 1.4.0 The `$post_id` was added.
		 *
		 * @param  integer $post_id Post ID.
		 * @return void
		 */
		public function delete_imported_posts( $post_id = 0 ) {

			if ( wp_doing_ajax() ) {
				// Verify Nonce.
				check_ajax_referer( 'astra-sites', '_ajax_nonce' );

				if ( ! current_user_can( 'customize' ) ) {
					wp_send_json_error( __( 'You are not allowed to perform this action', 'astra-sites' ) );
				}
			}

			$post_id = isset( $_REQUEST['post_id'] ) ? absint( $_REQUEST['post_id'] ) : $post_id;

			$message = 'Deleted - Post ID ' . $post_id . ' - ' . get_post_type( $post_id ) . ' - ' . get_the_title( $post_id );

			$message = '';
			if ( $post_id ) {

				$post_type = get_post_type( $post_id );
				$message   = 'Deleted - Post ID ' . $post_id . ' - ' . $post_type . ' - ' . get_the_title( $post_id );

				do_action( 'astra_sites_before_delete_imported_posts', $post_id, $post_type );

				Astra_Sites_Importer_Log::add( $message );
				wp_delete_post( $post_id, true );
			}

			if ( defined( 'WP_CLI' ) ) {
				WP_CLI::line( $message );
			} elseif ( wp_doing_ajax() ) {
				wp_send_json_success( $message );
			}
		}

		/**
		 * Delete imported WP forms
		 *
		 * @since 1.3.0
		 * @since 1.4.0 The `$post_id` was added.
		 *
		 * @param  integer $post_id Post ID.
		 * @return void
		 */
		public function delete_imported_wp_forms( $post_id = 0 ) {

			if ( ! defined( 'WP_CLI' ) && wp_doing_ajax() ) {
				// Verify Nonce.
				check_ajax_referer( 'astra-sites', '_ajax_nonce' );

				if ( ! current_user_can( 'customize' ) ) {
					wp_send_json_error( __( 'You are not allowed to perform this action', 'astra-sites' ) );
				}
			}

			$post_id = isset( $_REQUEST['post_id'] ) ? absint( $_REQUEST['post_id'] ) : $post_id;

			$message = '';
			if ( $post_id ) {

				do_action( 'astra_sites_before_delete_imported_wp_forms', $post_id );

				$message = 'Deleted - Form ID ' . $post_id . ' - ' . get_post_type( $post_id ) . ' - ' . get_the_title( $post_id );
				Astra_Sites_Importer_Log::add( $message );
				wp_delete_post( $post_id, true );
			}

			if ( defined( 'WP_CLI' ) ) {
				WP_CLI::line( $message );
			} elseif ( wp_doing_ajax() ) {
				wp_send_json_success( $message );
			}
		}

		/**
		 * Delete imported terms
		 *
		 * @since 1.3.0
		 * @since 1.4.0 The `$post_id` was added.
		 *
		 * @param  integer $term_id Term ID.
		 * @return void
		 */
		public function delete_imported_terms( $term_id = 0 ) {
			if ( ! defined( 'WP_CLI' ) && wp_doing_ajax() ) {
				// Verify Nonce.
				check_ajax_referer( 'astra-sites', '_ajax_nonce' );

				if ( ! current_user_can( 'customize' ) ) {
					wp_send_json_error( __( 'You are not allowed to perform this action', 'astra-sites' ) );
				}
			}

			$term_id = isset( $_REQUEST['term_id'] ) ? absint( $_REQUEST['term_id'] ) : $term_id;

			$message = '';
			if ( $term_id ) {
				$term = get_term( $term_id );
				if ( ! is_wp_error( $term ) ) {

					do_action( 'astra_sites_before_delete_imported_terms', $term_id, $term );

					$message = 'Deleted - Term ' . $term_id . ' - ' . $term->name . ' ' . $term->taxonomy;
					Astra_Sites_Importer_Log::add( $message );
					wp_delete_term( $term_id, $term->taxonomy );
				}
			}

			if ( defined( 'WP_CLI' ) ) {
				WP_CLI::line( $message );
			} elseif ( wp_doing_ajax() ) {
				wp_send_json_success( $message );
			}
		}

	}

	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	Astra_Sites_Importer::get_instance();
}
