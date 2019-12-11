<?php
/**
 * BSF Connect
 *
 * @package BSF Connect
 */

if( ! class_exists( 'BSF_Connect' ) ) :

	/**
	 * BSF Connect
	 */
	class BSF_Connect {

		/**
		 * Member Variable
		 *
		 * @var instance
		 */
		private static $instance;

		/**
		 * Initiator
		 *
		 * @since x.x.x
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self;
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 * 
		 * @since x.x.x
		 */
		public function __construct() {
			require_once BSF_CONNECT_DIR . 'classes/helper-functions.php';
			require_once BSF_CONNECT_DIR . 'classes/class-bsf-connect-rest-api.php';
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		}

		function admin_notices() {
			$url = add_query_arg( array(
				'site_url'    => site_url(),
				'product'     => 'astra-pro-sites',
				'redirect_url' => admin_url( ),
			), BSF_CONNECT_SERVER_URL );
			// vl( $_SERVER );
			// wp_die();
			?>
			<div class="notice notice-info">
				<p>Congrats! Connect to store.brainstormforce.com purchases will be listed here.</p>
				<p><a href="<?php echo $url; ?>">Connect</a></p>
			</div>
			<?php
		}

		function get_api_args( $args ) {

			$query_args = isset( $_SERVER['QUERY_STRING'] ) ? $_SERVER['QUERY_STRING'] : '';

			$url = admin_url();

			if( ! empty( $query_args ) ) {
				wp_parse_str( $query_args, $query_parameters );
				$url = add_query_arg( $query_parameters, admin_url( 'themes.php' ) );
			}

			$defaults = array(
				'site_url'     => site_url(),
				'product'      => 'astra-pro-sites',
				'open-popup'   => 'yes',
			);

			// if( bsf_is_active_license( 'astra-pro-sites' ) ) {
			// 	$defaults['action'] = 'deactivation';
			// } else {
			// 	$defaults['action'] = 'activation';
			// }

			$defaults['redirect_url'] = $url;

			return wp_parse_args( $args, $defaults );
		}

		function get_api_url( $args = array() ) {

			$args = $this->get_api_args( $args );

			return add_query_arg( $args, BSF_CONNECT_SERVER_URL );
		}

		function get_api_status_text() {
			if( bsf_is_active_license( 'astra-pro-sites' ) ) {
				return __( 'Disconnect', 'astra-sites' );
			}

			return __( 'Connect', 'astra-sites' );
		}

	}

	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	BSF_Connect::get_instance();

endif;