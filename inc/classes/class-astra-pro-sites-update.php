<?php
/**
 * Astra Pro Sites Update
 *
 * @package Astra Pro Sites
 */

if ( ! class_exists( 'Astra_Pro_Sites_Update' ) ) :

	/**
	 * Astra Pro Sites Update
	 *
	 * @since 1.0.0
	 */
	class Astra_Pro_Sites_Update {

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

			add_action( 'astra_update_before', __CLASS__ . '::init' );

		}

		/**
		 * Update
		 *
		 * @since 1.0.0
		 * @return void
		 */
		static public function init() {

			do_action( 'astra_pro_sites_update_before' );

			// Get auto saved version number.
			$saved_version = get_option( 'astra-pro-sites-auto-version', '0' );

			// If equals then return.
			if ( version_compare( $saved_version, ASTRA_PRO_SITES_VER, '=' ) ) {
				return;
			}

			// Update to older version than 1.0.0-rc.8 version.
			if ( version_compare( $saved_version, '1.0.0-rc.8', '<' ) ) {
				self::v_1_0_0_rc_9();
			}

			// Force check bundled extensions.
			update_site_option( 'bsf_force_check_extensions', true );

			// Auto update product latest version.
			update_option( 'astra-pro-sites-auto-version', ASTRA_PRO_SITES_VER );

			do_action( 'astra_pro_sites_update_after' );
		}

		/**
		 * Update white label branding of older version than 1.0.0-rc.8.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		static public function v_1_0_0_rc_9() {

			if ( class_exists( 'Astra_Admin_Helper' ) ) {

				// Get old values.
				$defaults = array(
					'Name'        => '',
					'Description' => '',
				);
				if ( is_network_admin() ) {
					$old_stored = get_site_option( 'astra_pro_sites_white_label', $defaults );
				} else {
					$old_stored = get_option( 'astra_pro_sites_white_label', $defaults );
				}

				// Set old values in new format.
				$old_stored_in_new_format = array(
					'astra-sites' => array(
						'name'        => $old_stored['Name'],
						'description' => $old_stored['Description'],
					),
				);

				$branding = Astra_Admin_Helper::get_admin_settings_option( '_astra_ext_white_label', true );
				$branding = wp_parse_args( $old_stored_in_new_format, $branding );
				Astra_Admin_Helper::update_admin_settings_option( '_astra_ext_white_label', $branding, true );

			}

		}
	}

	/**
	 * Kicking this off by calling 'set_instance()' method
	 */
	Astra_Pro_Sites_Update::set_instance();

endif;
