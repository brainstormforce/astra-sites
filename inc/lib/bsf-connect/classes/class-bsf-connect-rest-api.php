<?php
/**
 * BSF_Connect Rest API
 *
 * @package BSF_Connect Rest API
 * @since 1.0.0
 */

if ( ! class_exists( 'BSF_Connect_Rest_API' ) ) :

	/**
	 * BSF_Connect_Rest_API
	 *
	 * @since 1.0.0
	 */
	class BSF_Connect_Rest_API {

		/**
		 * Instance
		 *
		 * @since 1.0.0
		 *
		 * @access private
		 * @var object Class object.
		 */
		private static $instance;

		/**
		 * Initiator
		 *
		 * @since 1.0.0
		 *
		 * @return object initialized object of class.
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
		 * @since 1.0.0
		 */
		public function __construct() {
			add_action( 'rest_api_init', array( $this, 'api_actions' ) );
		}

		/**
		 * Register Route's
		 *
		 * @since 1.0.0
		 * @return void
		 */
		function api_actions() {
			register_rest_route(
				'bsf-connect/v1', '/activate',
				array(
					array(
						'methods'  => 'GET',
						'callback' => array( $this, 'activate_callback' ),
					),
				)
			);
			register_rest_route(
				'bsf-connect/v1', '/deactivate',
				array(
					array(
						'methods'  => 'GET',
						'callback' => array( $this, 'deactivate_callback' ),
					),
				)
			);
		}

		/**
		 * License Activation
		 *
		 * @since 1.0.0
		 * @access public
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
		 */
		public function activate_callback( $request ) {
			$args = $request->get_params();

			$data = array(
				'id'          => 'astra-pro-sites',
				'status'      => 'registered',
				'message'     => 'License successfully validated!',

				'purchase_key' => 'e3b1d6f02eb3dab83e23eb07b998da32',
			);

			$this->bsf_update_product_info( $data['id'], $data );

			// do_action( 'bsf_activate_license_'.$data['id'].'_after_success', $result, $response, $_POST );

			wp_cache_set( $data['purchase_key'] . '_license_status', '1' );
		
			// # Activation
			// [plugins] => Array(
			// 	[astra-pro-sites] => Array(
			//         [id] => astra-pro-sites
			//         [status] => registered
			//         [message] => License successfully validated!
			//     )
			// )
			// 
			// # Deactivation
			// [plugins] => Array(
			// 	[astra-pro-sites] => Array(
			//         [id] => astra-pro-sites
			//         [status] => not-registered
			//         [message] => License successfully validated!
			//     )
			// )
			return get_option( 'brainstrom_products', array() );

			return rest_ensure_response( 'License Successfully Activated!' );
		}

		/**
		 * License Deactivation
		 *
		 * @since 1.0.0
		 * @access public
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
		 */
		public function deactivate_callback( $request ) {
			$args = $request->get_params();

			$data = array(
				'id'          => 'astra-pro-sites',
				'status'      => 'not-registered',
				'message'     => 'License successfully validated!',

				'purchase_key' => 'e3b1d6f02eb3dab83e23eb07b998da32',
			);
			
			$this->bsf_update_product_info( $data['id'], $data );

			// do_action( 'bsf_activate_license_'.$data['id'].'_after_success', $result, $response, $_POST );

			wp_cache_set( $data['purchase_key'] . '_license_status', '0' );
		
			// # Activation
			// [plugins] => Array(
			// 	[astra-pro-sites] => Array(
			//         [id] => astra-pro-sites
			//         [status] => registered
			//         [message] => License successfully validated!
			//     )
			// )
			// 
			// # Deactivation
			// [plugins] => Array(
			// 	[astra-pro-sites] => Array(
			//         [id] => astra-pro-sites
			//         [status] => not-registered
			//         [message] => License successfully validated!
			//     )
			// )

			return get_option( 'brainstrom_products', array() );

			return rest_ensure_response( 'License Successfully Deactivated!' );
		}

		public function bsf_update_product_info( $product_id, $args ) {
			$brainstrom_products = get_option( 'brainstrom_products', array() );

			foreach ( $brainstrom_products as $type => $products ) {

				foreach ( $products as $id => $product ) {

					if ( $id == $product_id ) {
						foreach ( $args as $key => $value ) {
							$brainstrom_products[ $type ][ $id ][ $key ] = $value;
						}
					}
				}
			}

			update_option( 'brainstrom_products', $brainstrom_products );
		}

	}

	/**
	 * Initialize class object with 'get_instance()' method
	 */
	BSF_Connect_Rest_API::get_instance();

endif;