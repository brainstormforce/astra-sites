<?php
/**
 * Admin Notices
 *
 * @since 2.3.7
 * @package Astra Sites
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Astra_Sites_Admin' ) ) :

	/**
	 * Admin
	 */
	class Astra_Sites_Admin {

		/**
		 * Instance of Astra_Sites_Admin
		 *
		 * @since 2.3.7
		 * @var (Object) Astra_Sites_Admin
		 */
		private static $instance = null;

		/**
		 * Instance of Astra_Sites_Admin.
		 *
		 * @since 2.3.7
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
		 * @since 2.3.7
		 */
		private function __construct() {
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			add_action( 'astra_notice_before_markup', array( $this, 'notice_assets' ) );
		}

		/**
		 * Admin Notices
		 *
		 * @since 2.3.7
		 * @return void
		 */
		public function admin_notices() {

			$image_path = esc_url( ASTRA_SITES_URI . 'inc/assets/images/logo.svg' );

			$complete = get_option( 'astra_sites_import_complete', '' );

			Astra_Notices::add_notice(
				array(
					'id'      => 'astra-sites-5-start-notice',
					'type'    => 'info',
					'class'   => 'astra-sites-5-star',
					'show_if' => ( 'yes' === $complete && false === Astra_Sites_White_Label::get_instance()->is_white_labeled() ),
					/* translators: %1$s white label plugin name and %2$s deactivation link */
					'message' => sprintf(
						'<div class="notice-image" style="display: flex;">
							<img src="%1$s" class="custom-logo" alt="Starter Templates" itemprop="logo" style="max-width: 90px;"></div>
							<div class="notice-content">
								<div class="notice-heading">
									%2$s
								</div>
								%3$s<br />
								<div class="astra-review-notice-container">
									<a href="%4$s" class="astra-notice-close astra-review-notice button-primary" target="_blank">
									%5$s
									</a>
								<span class="dashicons dashicons-calendar"></span>
									<a href="#" data-repeat-notice-after="%6$s" class="astra-notice-close astra-review-notice">
									%7$s
									</a>
								<span class="dashicons dashicons-smiley"></span>
									<a href="#" class="astra-notice-close astra-review-notice">
									%8$s
									</a>
								</div>
							</div>',
						$image_path,
						__( 'Hello! Seems like you have used Starter Templates to build this website &mdash; Thanks a ton!', 'astra-sites' ),
						__( 'Could you please do us a BIG favor and give it a 5-star rating on WordPress? This would boost our motivation and help other users make a comfortable decision while choosing the Starter Templates.', 'astra-sites' ),
						'https://wordpress.org/support/plugin/astra-sites/reviews/?filter=5#new-post',
						__( 'Ok, you deserve it', 'astra-sites' ),
						MONTH_IN_SECONDS,
						__( 'Nope, maybe later', 'astra-sites' ),
						__( 'I already did', 'astra-sites' )
					),
				)
			);
		}

		/**
		 * Enqueue Astra Notices CSS.
		 *
		 * @since 2.3.7
		 *
		 * @return void
		 */
		public static function notice_assets() {
			$file = is_rtl() ? 'astra-notices-rtl.css' : 'astra-notices.css';
			wp_enqueue_style( 'astra-sites-notices', ASTRA_SITES_URI . 'assets/css/' . $file, array(), ASTRA_SITES_VER );
		}
	}

	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	Astra_Sites_Admin::get_instance();

endif;
