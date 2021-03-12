<?php
/**
 * Backward Compatibility
 *
 * @package Astra Sites
 */

if ( ! class_exists( 'Astra_Sites_Update' ) ) :

	/**
	 * Update
	 *
	 * @since 2.6.3
	 */
	class Astra_Sites_Update {

		/**
		 * Instance
		 *
		 * @access private
		 * @var object Class object.
		 * @since 2.6.3
		 */
		private static $instance;

		/**
		 * Initiator
		 *
		 * @since 2.6.3
		 * @return object initialized object of class.
		 */
		public static function set_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * @since 2.6.3
		 */
		public function __construct() {

			add_action( 'admin_init', array( $this, 'init' ) );

		}

		/**
		 * Update
		 *
		 * @since 2.6.3
		 * @return void
		 */
		public function init() {

			do_action( 'astra_sites_update_before' );

			// Get auto saved version number.
			$saved_version = get_option( 'astra-sites-auto-version', '0' );

			// If equals then return.
			if ( version_compare( $saved_version, ASTRA_SITES_VER, '=' ) ) {
				return;
			}

			// Update to older version than 2.6.3 version.
			if ( version_compare( $saved_version, '2.6.3', '<' ) ) {
				$this->v_2_6_3();
			}

			// Force check bundled extensions.
			update_site_option( 'bsf_force_check_extensions', true );

			// Auto update product latest version.
			update_option( 'astra-sites-auto-version', ASTRA_SITES_VER );

			do_action( 'astra_sites_update_after' );

		}

		/**
		 * Update white label branding of older version than 1.0.0-rc.8.
		 *
		 * @since 2.6.3
		 * @return void
		 */
		public function v_2_6_3() {

			// Updated the block templates libary.
			if ( is_callable( 'Ast_Block_Templates_Sync_Library', 'set_default_assets' ) ) {
				Ast_Block_Templates_Sync_Library::get_instance()->set_default_assets();
			}

		}

	}

	/**
	 * Kicking this off by calling 'set_instance()' method
	 */
	Astra_Sites_Update::set_instance();

endif;
