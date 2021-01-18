<?php
/**
 * Init
 *
 * @since 1.0.0
 * @package Gutenberg Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Astra_Sites_Gutenberg_Templates' ) ) :

	/**
	 * Admin
	 */
	class Astra_Sites_Gutenberg_Templates {

		/**
		 * Instance
		 *
		 * @since 1.0.0
		 * @var (Object) Astra_Sites_Gutenberg_Templates
		 */
		private static $instance = null;

		/**
		 * Get Instance
		 *
		 * @since 1.0.0
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
		 * @since 1.0.0
		 */
		private function __construct() {
			$this->version_check();
			add_action( 'init', array( $this, 'load' ), 999 );
		}

		/**
		 * Version Check
		 *
		 * @return void
		 */
		public function version_check() {

			$file = realpath( dirname( __FILE__ ) . '/gutenberg-templates/package.json' );

			// Is file exist?
			if ( is_file( $file ) ) {
				// @codingStandardsIgnoreStart
				$file_data = json_decode( file_get_contents( $file ), true );
				// @codingStandardsIgnoreEnd
				global $gutenberg_templates_version, $gutenberg_templates_init;
				$path    = realpath( dirname( __FILE__ ) . '/gutenberg-templates/gutenberg-templates.php' );
				$version = isset( $file_data['version'] ) ? $file_data['version'] : 0;

				// Compare versions.
				if ( version_compare( $version, $gutenberg_templates_version, '>' ) ) {
					$gutenberg_templates_version = $version;
					$gutenberg_templates_init    = $path;
				}
			}
		}

		/**
		 * Load latest plugin
		 *
		 * @return void
		 */
		public function load() {
			global $gutenberg_templates_version, $gutenberg_templates_init;
			if ( is_file( realpath( $gutenberg_templates_init ) ) ) {
				include_once realpath( $gutenberg_templates_init );
			}
		}

	}

	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	Astra_Sites_Gutenberg_Templates::get_instance();

endif;
