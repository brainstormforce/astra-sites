<?php
/**
 * Astra Sites WP CLI
 *
 * 1. Run `wp astra-sites list`                     List of all astra sites.
 * 2. Run `wp astra-sites import <id>`    Import site.
 *
 * @package Astra Sites
 * @since 1.4.0
 */

if ( class_exists( 'WP_CLI_Command' ) && ! class_exists( 'Astra_Sites_WP_CLI' ) ) :

	/**
	 * Astra Sites
	 *
	 * @since 1.4.0
	 */
	class Astra_Sites_WP_CLI extends WP_CLI_Command {

		/**
		 * Site Data
		 *
		 * @var array
		 */
		protected $current_site_data;

		/**
		 * Generates the list of all Astra Sites.
		 *
		 * ## OPTIONS
		 *
		 * [--per-page=<number>]
		 * : No of sites to show in the list. Default its showing 10 sites.
		 *
		 * [--search=<text>]
		 * : Show the sites from particular search term.
		 *
		 * [--category=<text>]
		 * : Show the site from the specific category.
		 *
		 * [--page-builder=<text>]
		 * : List the sites from the particular page builder.
		 *
		 * [--type=<text>]
		 * : List the sites from the particular site type.
		 *
		 * ## EXAMPLES
		 *
		 *     # List all the sites.
		 *     $ wp astra-sites list
		 *     +-------+-------------------+-----------------------------------------+---------+----------------+--------------+
		 *     | id    | title             | url                                     | type    | categories     | page-builder |
		 *     +-------+-------------------+-----------------------------------------+---------+----------------+--------------+
		 *     | 34184 | Nutritionist      | //websitedemos.net/nutritionist-01      | free    | Business, Free | Elementor    |
		 *     | 34055 | Law Firm          | //websitedemos.net/law-firm-03          | premium | Business       | Elementor    |
		 *     +-------+-------------------+-----------------------------------------+---------+----------------+--------------+
		 *
		 * @since 1.4.0
		 * @param  array $args        Arguments.
		 * @param  array $assoc_args Associated Arguments.
		 */
		public function list( $args, $assoc_args ) {

			$per_page = isset( $assoc_args['per-page'] ) ? $assoc_args['per-page'] : 10;
			$search   = isset( $assoc_args['search'] ) ? $assoc_args['search'] : '';

			$rest_args = array(
				'_fields'  => 'id,title,slug,astra-site-category,astra-site-page-builder,astra-sites-tag,astra-site-type,astra-site-url',
				'per_page' => $per_page,
			);

			if ( ! empty( $search ) ) {
				$rest_args['search'] = $search;
			}

			$list = (array) Astra_Sites::get_instance()->get_sites( 'astra-sites', $rest_args, true, $assoc_args );

			// Modify the output.
			foreach ( $list as $key => $item ) {
				$list[ $key ]['categories']   = implode( ', ', $list[ $key ]['categories'] );
				$list[ $key ]['page-builder'] = implode( ', ', $list[ $key ]['page_builders'] );
			}

			$display_fields = array(
				'id',
				'title',
				'url',
				'type',
				'categories',
				'page-builder',
			);
			$formatter      = $this->get_formatter( $assoc_args, $display_fields );
			$formatter->display_items( $list );
		}

		/**
		 * Import the site by site ID.
		 *
		 * ## OPTIONS
		 *
		 * <id>
		 * : Site id of the import site.
		 *
		 * [--reset]
		 * : Reset the recently imported site data. Including post, pages, customizer settings, widgets etc.
		 *
		 * [--yes]
		 * : Forcefully import the site without asking any prompt message.
		 *
		 * ## EXAMPLES
		 *
		 *     # Import demo site.
		 *     $ wp astra-sites import 34184 --reset --yes
		 *     Activating Plugins..
		 *     Reseting Posts..
		 *     ..
		 *
		 * @since 1.4.0
		 * @param  array $args        Arguments.
		 * @param  array $assoc_args Associated Arguments.
		 */
		public function import( $args, $assoc_args ) {

			// Force import.
			$yes = isset( $assoc_args['yes'] ) ? true : false;
			if ( ! $yes ) {
				WP_CLI::confirm( 'Are you sure you want to import the site?' );
			}

			// Valid site ID?
			$id = isset( $args[0] ) ? absint( $args[0] ) : 0;
			if ( ! $id ) {
				WP_CLI::error( __( 'Invalid Site ID,', 'astra-sites' ) );
			}

			$reset     = isset( $assoc_args['reset'] ) ? true : false;
			$site_url  = get_site_url();
			$demo_data = $this->get_site_data( $id );

			// Invalid Site ID.
			if ( is_wp_error( $demo_data ) ) {
				/* Translators: %s is the error message. */
				WP_CLI::error( sprintf( __( 'Site Import failed due to error: %s', 'astra-sites' ), $demo_data->get_error_message() ) );
			}

			// License Status.
			$license_status = false;
			if ( is_callable( 'BSF_License_Manager::bsf_is_active_license' ) ) {
				$license_status = BSF_License_Manager::bsf_is_active_license( 'astra-pro-sites' );
			}

			if ( 'free' !== $demo_data['site-type'] && 'upgrade' === $demo_data['license-status'] && ! $license_status ) {
				WP_CLI::line( 'This is Agency site. Please activate the Astra Premium Sites license!' );
				WP_CLI::line( 'Goto: ' . admin_url( 'plugins.php?bsf-inline-license-form=astra-pro-sites' ) );
				WP_CLI::line( 'Activate it from ' . admin_url( 'plugins.php?bsf-inline-license-form=astra-pro-sites' ) );
				WP_CLI::error( "Or\nUse CLI command `wp brainstormforce license activate astra-pro-sites {YOUR_LICENSE_KEY}`" );
			}

			/**
			 * Install & Activate Required Plugins.
			 */
			if ( isset( $demo_data['required-plugins'] ) ) {
				$plugins = (array) $demo_data['required-plugins'];
				if ( ! empty( $plugins ) ) {
					$plugin_status = Astra_Sites::get_instance()->required_plugin( $plugins );

					// Install Plugins.
					if ( ! empty( $plugin_status['required_plugins']['notinstalled'] ) ) {
						WP_CLI::line( 'Installing Plugins..' );
						foreach ( $plugin_status['required_plugins']['notinstalled'] as $key => $plugin ) {
							if ( isset( $plugin['slug'] ) ) {
								WP_CLI::runcommand( 'plugin install ' . $plugin['slug'] . ' --activate' );
							}
						}
					}

					// Activate Plugins.
					if ( ! empty( $plugin_status['required_plugins']['inactive'] ) ) {
						WP_CLI::line( 'Activating Plugins..' );
						foreach ( $plugin_status['required_plugins']['inactive'] as $key => $plugin ) {
							if ( isset( $plugin['init'] ) ) {
								Astra_Sites::get_instance()->required_plugin_activate( $plugin['init'], $demo_data['astra-site-options-data'], $demo_data['astra-enabled-extensions'] );
							}
						}
					}
				}
			}

			/**
			 * Backup Customizer Settings
			 */
			Astra_Sites::get_instance()->backup_settings();

			/**
			 * Reset Site Data
			 */
			if ( $reset ) {
				WP_CLI::runcommand( 'astra-sites reset --yes' );
			}

			/**
			 * Import Flows & Steps for CartFlows.
			 */
			if ( isset( $demo_data['astra-site-cartflows-path'] ) && ! empty( $demo_data['astra-site-cartflows-path'] ) ) {
				Astra_Sites_Importer::get_instance()->import_cartflows( $demo_data['astra-site-cartflows-path'] );
			}

			/**
			 * Import WP Forms.
			 */
			if ( isset( $demo_data['astra-site-wpforms-path'] ) && ! empty( $demo_data['astra-site-wpforms-path'] ) ) {
				Astra_Sites_Importer::get_instance()->import_wpforms( $demo_data['astra-site-wpforms-path'] );
			}

			/**
			 * Import Customizer Settings.
			 */
			WP_CLI::runcommand( 'astra-sites import_customizer_settings ' . $id );

			/**
			 * Import Content from XML/WXR.
			 */
			if ( isset( $demo_data['astra-site-wxr-path'] ) && ! empty( $demo_data['astra-site-wxr-path'] ) ) {

				// Download XML file.
				WP_CLI::line( 'Downloading ' . $demo_data['astra-site-wxr-path'] );
				$xml_path = Astra_Sites_Helper::download_file( $demo_data['astra-site-wxr-path'] );

				if ( $xml_path['success'] && isset( $xml_path['data']['file'] ) ) {
					WP_CLI::line( 'Importing WXR..' );
					Astra_WXR_Importer::instance()->sse_import( $xml_path['data']['file'] );
				} else {
					WP_CLI::line( 'WXR file Download Failed. Error ' . $xml_path['data'] );
				}
			}

			/**
			 * Import Site Options.
			 */
			if ( isset( $demo_data['astra-site-options-data'] ) && ! empty( $demo_data['astra-site-options-data'] ) ) {
				WP_CLI::line( 'Importing Site Options..' );
				Astra_Sites_Importer::get_instance()->import_options( $demo_data['astra-site-options-data'] );
			}

			/**
			 * Import Widgets.
			 */
			if ( isset( $demo_data['astra-site-widgets-data'] ) && ! empty( $demo_data['astra-site-widgets-data'] ) ) {
				WP_CLI::line( 'Importing Widgets..' );
				Astra_Sites_Importer::get_instance()->import_widgets( $demo_data['astra-site-widgets-data'] );
			}

			/**
			 * Import End.
			 */
			Astra_Sites_Importer::get_instance()->import_end();

			WP_CLI::line( 'Site Imported Successfully!' );
			WP_CLI::line( 'Visit: ' . $site_url );
		}

		/**
		 * Reset
		 *
		 * Delete all pages, post, custom post type, customizer settings and site options.
		 *
		 * Use: `wp astra-sites reset`
		 *
		 * @since 1.4.0
		 * @param  array $args       Arguments.
		 * @param  array $assoc_args Associated Arguments.
		 * @return void.
		 */
		public function reset( $args = array(), $assoc_args = array() ) {

			$yes = isset( $assoc_args['yes'] ) ? true : false;
			if ( ! $yes ) {
				WP_CLI::confirm( 'Are you sure you want to delete imported site data?' );
			}

			// Get tracked data.
			$reset_data = Astra_Sites::get_instance()->get_reset_data();

			// Delete tracked posts.
			if ( isset( $reset_data['reset_posts'] ) && ! empty( $reset_data['reset_posts'] ) ) {
				WP_CLI::line( 'Reseting Posts..' );
				foreach ( $reset_data['reset_posts'] as $key => $post_id ) {
					Astra_Sites_Importer::get_instance()->delete_imported_posts( $post_id );
				}
			}
			// Delete tracked terms.
			if ( isset( $reset_data['reset_terms'] ) && ! empty( $reset_data['reset_terms'] ) ) {
				WP_CLI::line( 'Reseting Terms..' );
				foreach ( $reset_data['reset_terms'] as $key => $post_id ) {
					Astra_Sites_Importer::get_instance()->delete_imported_terms( $post_id );
				}
			}
			// Delete tracked WP forms.
			if ( isset( $reset_data['reset_wp_forms'] ) && ! empty( $reset_data['reset_wp_forms'] ) ) {
				WP_CLI::line( 'Resting WP Forms...' );
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
		 * Import Customizer Settings
		 *
		 * @since 1.4.0
		 *
		 * Example: `wp astra-sites import_customizer_settings <id>`
		 *
		 * @param  array $args        Arguments.
		 * @param  array $assoc_args Associated Arguments.
		 * @return void
		 */
		public function import_customizer_settings( $args, $assoc_args ) {

			// Valid site ID?
			$id = isset( $args[0] ) ? absint( $args[0] ) : 0;
			if ( ! $id ) {
				WP_CLI::error( __( 'Invalid Site ID,', 'astra-sites' ) );
			}

			$demo_data = $this->get_site_data( $id );

			WP_CLI::line( __( 'Importing customizer settings..', 'astra-sites' ) );
			Astra_Sites_Importer::get_instance()->import_customizer_settings( $demo_data['astra-site-customizer-data'] );
		}

		/**
		 * Get Formatter
		 *
		 * @since 1.4.0
		 * @param  array  $assoc_args Associate arguments.
		 * @param  string $fields    Fields.
		 * @param  string $prefix    Prefix.
		 * @return object            Class object.
		 */
		protected function get_formatter( &$assoc_args, $fields = '', $prefix = '' ) {
			return new \WP_CLI\Formatter( $assoc_args, $fields, $prefix );
		}

		/**
		 * Get Site Data by Site ID
		 *
		 * @since 1.4.0
		 *
		 * @param  int $id        Site ID.
		 * @return array
		 */
		private function get_site_data( $id ) {
			if ( empty( $this->current_site_data ) ) {
				$this->current_site_data = Astra_Sites_Importer::get_instance()->get_single_demo( $id );
			}

			return $this->current_site_data;
		}
	}

	/**
	 * Add Command
	 */
	WP_CLI::add_command( 'astra-sites', 'Astra_Sites_WP_CLI' );

endif;
