<?php
/**
 * Astra Sites
 *
 * @since  1.0.0
 * @package Astra Sites
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		public $api_url;

		/**
		 * API URL which is used to get the response from Pixabay.
		 *
		 * @since  2.0.0
		 * @var (String) URL
		 */
		public $pixabay_url;

		/**
		 * API Key which is used to get the response from Pixabay.
		 *
		 * @since  2.0.0
		 * @var (String) URL
		 */
		public $pixabay_api_key;

		/**
		 * Instance of Astra_Sites
		 *
		 * @since  1.0.0
		 * @var (Object) Astra_Sites
		 */
		private static $instance = null;

		/**
		 * Localization variable
		 *
		 * @since  2.0.0
		 * @var (Array) $local_vars
		 */
		public static $local_vars = array();

		/**
		 * Localization variable
		 *
		 * @since  2.0.0
		 * @var (Array) $wp_upload_url
		 */
		public $wp_upload_url = '';

		/**
		 * Instance of Astra_Sites.
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
		private function __construct() {

			$this->set_api_url();

			$this->includes();

			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ), 99 );
			add_action( 'wp_enqueue_scripts', array( $this, 'image_search_scripts' ) );
			add_action( 'elementor/editor/footer', array( $this, 'insert_templates' ) );
			add_action( 'admin_footer', array( $this, 'insert_image_templates' ) );
			add_action( 'wp_footer', array( $this, 'insert_image_templates_bb_and_brizy' ) );
			add_action( 'elementor/editor/footer', array( $this, 'register_widget_scripts' ), 99 );
			add_action( 'elementor/editor/wp_head', array( $this, 'add_predefined_variables' ) );
			add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'popup_styles' ) );
			add_action( 'elementor/preview/enqueue_styles', array( $this, 'popup_styles' ) );

			// AJAX.
			add_action( 'wp_ajax_astra-required-plugins', array( $this, 'required_plugin' ) );
			add_action( 'wp_ajax_astra-required-plugin-activate', array( $this, 'required_plugin_activate' ) );
			add_action( 'wp_ajax_astra-sites-backup-settings', array( $this, 'backup_settings' ) );
			add_action( 'wp_ajax_astra-sites-set-reset-data', array( $this, 'get_reset_data' ) );
			add_action( 'wp_ajax_astra-sites-activate-theme', array( $this, 'activate_theme' ) );
			add_action( 'wp_ajax_astra-sites-create-page', array( $this, 'create_page' ) );
			add_action( 'wp_ajax_astra-sites-create-template', array( $this, 'create_template' ) );
			add_action( 'wp_ajax_astra-sites-create-image', array( $this, 'create_image' ) );
			add_action( 'wp_ajax_astra-sites-getting-started-notice', array( $this, 'getting_started_notice' ) );
			add_action( 'wp_ajax_astra-sites-favorite', array( $this, 'add_to_favorite' ) );
			add_action( 'wp_ajax_astra-sites-api-request', array( $this, 'api_request' ) );
			add_action( 'wp_ajax_astra-page-elementor-batch-process', array( $this, 'elementor_batch_process' ) );

			add_action( 'delete_attachment', array( $this, 'delete_astra_images' ) );
		}

		/**
		 * Before Astra Image delete, remove from options.
		 *
		 * @since  2.0.0
		 * @param int $id ID to deleting image.
		 * @return void
		 */
		public function delete_astra_images( $id ) {

			if ( ! $id ) {
				return;
			}

			// @codingStandardsIgnoreStart
			$saved_images     = get_option( 'astra-sites-saved-images', array() );
			$astra_image_flag = get_post_meta( $id, 'astra-images', true );
			$astra_image_flag = (int) $astra_image_flag;
			if (
				'' !== $astra_image_flag &&
				is_array( $saved_images ) &&
				! empty( $saved_images ) &&
				in_array( $astra_image_flag, $saved_images )
			) {
				$saved_images = array_diff( $saved_images, [ $astra_image_flag ] );
				update_option( 'astra-sites-saved-images', $saved_images );
			}
			// @codingStandardsIgnoreEnd
		}

		/**
		 * Enqueue Image Search scripts into Beaver Builder Editor.
		 *
		 * @since  2.0.0
		 * @return void
		 */
		public function image_search_scripts() {

			if ( class_exists( 'FLBuilderModel' ) ) {
				if ( FLBuilderModel::is_builder_active() ) {
					// Image Search assets.
					$this->image_search_assets();
				}
			}

			if ( class_exists( 'Brizy_Editor_Post' ) ) {
				if ( isset( $_GET['brizy-edit'] ) || isset( $_GET['brizy-edit-iframe'] ) ) {
					// Image Search assets.
					$this->image_search_assets();
				}
			}
		}

		/**
		 * Elementor Batch Process via AJAX
		 *
		 * @since 2.0.0
		 */
		public function elementor_batch_process() {

			// Verify Nonce.
			check_ajax_referer( 'astra-sites', '_ajax_nonce' );

			if ( ! current_user_can( 'customize' ) ) {
				wp_send_json_error( __( 'You are not allowed to perform this action', 'astra-sites' ) );
			}

			if ( ! isset( $_POST['url'] ) ) {
				wp_send_json_error( __( 'Invalid API URL', 'astra-sites' ) );
			}

			$response = wp_remote_get( $_POST['url'] );

			if ( is_wp_error( $response ) ) {
				wp_send_json_error( wp_remote_retrieve_body( $response ) );
			}

			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );
			if ( ! isset( $data['post-meta']['_elementor_data'] ) ) {
				wp_send_json_error( __( 'Invalid Post Meta', 'astra-sites' ) );
			}

			$meta    = json_decode( $data['post-meta']['_elementor_data'], true );
			$post_id = $_POST['id'];

			if ( empty( $post_id ) || empty( $meta ) ) {
				wp_send_json_error( __( 'Invalid Post ID or Elementor Meta', 'astra-sites' ) );
			}

			if ( isset( $data['astra-page-options-data'] ) && isset( $data['astra-page-options-data']['elementor_load_fa4_shim'] ) ) {
				update_option( 'elementor_load_fa4_shim', $data['astra-page-options-data']['elementor_load_fa4_shim'] );
			}

			$import      = new \Elementor\TemplateLibrary\Astra_Elementor_Pages();
			$import_data = $import->import( $post_id, $meta );

			wp_send_json_success( $import_data );
		}

		/**
		 * API Request
		 *
		 * @since 2.0.0
		 */
		public function api_request() {
			$url = isset( $_POST['url'] ) ? $_POST['url'] : '';

			if ( empty( $url ) ) {
				wp_send_json_error( __( 'Provided API URL is empty! Please try again!', 'astra-sites' ) );
			}

			$api_args = array(
				'timeout' => 30,
			);
			$request  = wp_remote_get( trailingslashit( self::get_instance()->get_api_domain() ) . '/wp-json/wp/v2/' . $url, $api_args );
			if ( ! is_wp_error( $request ) && 200 === (int) wp_remote_retrieve_response_code( $request ) ) {

				$demo_data = json_decode( wp_remote_retrieve_body( $request ), true );
				update_option( 'astra_sites_import_data', $demo_data );

				wp_send_json_success( $demo_data );
			} elseif ( is_wp_error( $request ) ) {
				wp_send_json_error( 'API Request is failed due to ' . $request->get_error_message() );
			} elseif ( 200 !== (int) wp_remote_retrieve_response_code( $request ) ) {
				wp_send_json_error( wp_remote_retrieve_body( $request ) );
			}
		}

		/**
		 * Add Predefined Variables
		 *
		 * @since 2.0.0
		 */
		public function add_predefined_variables() {

			global $current_screen;
			?>
			<script type="text/javascript">
			addLoadEvent = function(func){if(typeof jQuery!="undefined")jQuery(document).ready(func);else if(typeof wpOnload!='function'){wpOnload=func;}else{var oldonload=wpOnload;wpOnload=function(){oldonload();func();}}};
			var ajaxurl = '<?php echo esc_url( admin_url( 'admin-ajax.php', 'relative' ) ); ?>',
				pagenow = '<?php echo $current_screen->id; ?>',
				typenow = '<?php echo $current_screen->post_type; ?>';
			</script>
			<?php
		}

		/**
		 * Insert Template
		 *
		 * @return void
		 */
		public function insert_image_templates() {
			ob_start();
			require_once ASTRA_SITES_DIR . 'inc/includes/image-templates.php';
			ob_end_flush();
		}

		/**
		 * Insert Template
		 *
		 * @return void
		 */
		public function insert_image_templates_bb_and_brizy() {

			if ( class_exists( 'FLBuilderModel' ) ) {
				if ( FLBuilderModel::is_builder_active() ) {
					ob_start();
					require_once ASTRA_SITES_DIR . 'inc/includes/image-templates.php';
					ob_end_flush();
				}
			}

			if ( class_exists( 'Brizy_Editor_Post' ) ) {
				if ( isset( $_GET['brizy-edit'] ) || isset( $_GET['brizy-edit-iframe'] ) ) {
					ob_start();
					require_once ASTRA_SITES_DIR . 'inc/includes/image-templates.php';
					ob_end_flush();
				}
			}
		}

		/**
		 * Insert Template
		 *
		 * @return void
		 */
		public function insert_templates() {
			ob_start();
			require_once ASTRA_SITES_DIR . 'inc/includes/templates.php';
			require_once ASTRA_SITES_DIR . 'inc/includes/image-templates.php';
			ob_end_flush();
		}

		/**
		 * Add/Remove Favorite.
		 *
		 * @since  2.0.0
		 */
		public function add_to_favorite() {

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( 'You can\'t access this action.' );
			}

			$new_favorites = array();
			$site_id       = isset( $_POST['site_id'] ) ? sanitize_key( $_POST['site_id'] ) : '';

			if ( empty( $site_id ) ) {
				wp_send_json_error();
			}

			$favorite_settings = get_option( 'astra-sites-favorites', array() );

			if ( false !== $favorite_settings && is_array( $favorite_settings ) ) {
				$new_favorites = $favorite_settings;
			}

			if ( 'false' === $_POST['is_favorite'] ) {
				if ( in_array( $site_id, $new_favorites, true ) ) {
					$key = array_search( $site_id, $new_favorites, true );
					unset( $new_favorites[ $key ] );
				}
			} else {
				if ( ! in_array( $site_id, $new_favorites, true ) ) {
					array_push( $new_favorites, $site_id );
				}
			}

			update_option( 'astra-sites-favorites', $new_favorites );

			wp_send_json_success(
				array(
					'all_favorites' => $new_favorites,
				)
			);
		}

		/**
		 * Import Template.
		 *
		 * @since  2.0.0
		 */
		public function create_template() {

			// Verify Nonce.
			check_ajax_referer( 'astra-sites', '_ajax_nonce' );

			if ( ! current_user_can( 'customize' ) ) {
				wp_send_json_error( __( 'You are not allowed to perform this action', 'astra-sites' ) );
			}

			$content = isset( $_POST['data']['content']['rendered'] ) ? $_POST['data']['content']['rendered'] : '';

			$data = isset( $_POST['data'] ) ? $_POST['data'] : array();

			if ( empty( $data ) ) {
				wp_send_json_error( 'Empty page data.' );
			}

			$page_id = isset( $_POST['data']['id'] ) ? $_POST['data']['id'] : '';

			$title = '';
			if ( isset( $_POST['data']['title']['rendered'] ) ) {
				if ( '' !== $_POST['title'] ) {
					$title = $_POST['title'] . ' - ' . $_POST['data']['title']['rendered'];
				} else {
					$title = $_POST['data']['title']['rendered'];
				}
			}

			$excerpt = isset( $_POST['data']['excerpt']['rendered'] ) ? $_POST['data']['excerpt']['rendered'] : '';

			$post_args = array(
				'post_type'    => 'elementor_library',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_content' => $content,
				'post_excerpt' => $excerpt,
			);

			$new_page_id = wp_insert_post( $post_args );
			$post_meta   = isset( $_POST['data']['post-meta'] ) ? $_POST['data']['post-meta'] : array();

			if ( ! empty( $post_meta ) ) {
				$this->import_template_meta( $new_page_id, $post_meta );
			}

			if ( 'pages' === $_POST['type'] ) {
				update_post_meta( $new_page_id, '_elementor_template_type', 'page' );
				wp_set_object_terms( $new_page_id, 'page', 'elementor_library_type' );
			} else {
				update_post_meta( $new_page_id, '_elementor_template_type', 'section' );
				wp_set_object_terms( $new_page_id, 'section', 'elementor_library_type' );
			}

			do_action( 'astra_sites_process_single', $new_page_id );

			wp_send_json_success(
				array(
					'remove-page-id' => $page_id,
					'id'             => $new_page_id,
					'link'           => get_permalink( $new_page_id ),
				)
			);
		}

		/**
		 * Import Page.
		 *
		 * @since  2.0.0
		 */
		public function create_page() {

			// Verify Nonce.
			check_ajax_referer( 'astra-sites', '_ajax_nonce' );

			if ( ! current_user_can( 'customize' ) ) {
				wp_send_json_error( __( 'You are not allowed to perform this action', 'astra-sites' ) );
			}

			$default_page_builder = Astra_Sites_Page::get_instance()->get_setting( 'page_builder' );

			if ( 'gutenberg' === $default_page_builder ) {
				$content = isset( $_POST['data']['original_content'] ) ? $_POST['data']['original_content'] : '';
			} else {
				$content = isset( $_POST['data']['content']['rendered'] ) ? $_POST['data']['content']['rendered'] : '';
			}

			if ( 'elementor' === $default_page_builder ) {
				if ( isset( $_POST['data']['astra-page-options-data'] ) && isset( $_POST['data']['astra-page-options-data']['elementor_load_fa4_shim'] ) ) {
					update_option( 'elementor_load_fa4_shim', $_POST['data']['astra-page-options-data']['elementor_load_fa4_shim'] );
				}
			}

			$data = isset( $_POST['data'] ) ? $_POST['data'] : array();

			if ( empty( $data ) ) {
				wp_send_json_error( 'Empty page data.' );
			}

			$page_id = isset( $_POST['data']['id'] ) ? $_POST['data']['id'] : '';
			$title   = isset( $_POST['data']['title']['rendered'] ) ? $_POST['data']['title']['rendered'] : '';
			$excerpt = isset( $_POST['data']['excerpt']['rendered'] ) ? $_POST['data']['excerpt']['rendered'] : '';

			$post_args = array(
				'post_type'    => 'page',
				'post_status'  => 'draft',
				'post_title'   => $title,
				'post_content' => $content,
				'post_excerpt' => $excerpt,
			);

			$new_page_id = wp_insert_post( $post_args );
			$post_meta   = isset( $_POST['data']['post-meta'] ) ? $_POST['data']['post-meta'] : array();

			if ( ! empty( $post_meta ) ) {
				$this->import_post_meta( $new_page_id, $post_meta );
			}

			if ( isset( $_POST['data']['astra-page-options-data'] ) && ! empty( $_POST['data']['astra-page-options-data'] ) ) {

				foreach ( $_POST['data']['astra-page-options-data'] as $option => $value ) {
					update_option( $option, $value );
				}
			}

			do_action( 'astra_sites_process_single', $new_page_id );

			wp_send_json_success(
				array(
					'remove-page-id' => $page_id,
					'id'             => $new_page_id,
					'link'           => get_permalink( $new_page_id ),
				)
			);
		}

		/**
		 * Import Image.
		 *
		 * @since  2.0.0
		 */
		public function create_image() {

			// Verify Nonce.
			check_ajax_referer( 'astra-sites', '_ajax_nonce' );

			if ( ! current_user_can( 'customize' ) ) {
				wp_send_json_error( __( 'You are not allowed to perform this action', 'astra-sites' ) );
			}

			$url      = $_POST['url'];
			$name     = $_POST['name'];
			$photo_id = $_POST['id'];

			$saved_images = get_option( 'astra-sites-saved-images', array() );

			$this->wp_upload_url = $this->get_wp_upload_url();

			$image  = '';
			$result = array();

			if ( '' !== $url ) {

				$name  = preg_replace( '/\.[^.]+$/', '', $name ) . '-' . $photo_id . '.jpg';
				$image = $this->create_image_from_url( $url, $name );

				if ( $image ) {

					$result['attachmentData'] = wp_prepare_attachment_for_js( $image );
					if ( did_action( 'elementor/loaded' ) ) {
						$result['data'] = Astra_Elementor_Images::get_instance()->get_attachment_data( $image );
					}
				}
			}

			if ( empty( $saved_images ) || false === $saved_images ) {
				$saved_images = array();
			}

			$saved_images[] = $photo_id;
			update_option( 'astra-sites-saved-images', $saved_images );

			$result['updated-saved-images'] = get_option( 'astra-sites-saved-images', array() );

			wp_send_json_success( $result );
		}

		/**
		 * Set the upload directory
		 */
		public function get_wp_upload_url() {
			$wp_upload_dir = wp_upload_dir();
			return isset( $wp_upload_dir['url'] ) ? $wp_upload_dir['url'] : false;
		}

		/**
		 * Create the image and return the new media upload id.
		 *
		 * @param String $url URL to pixabay image.
		 * @param String $name Name to pixabay image.
		 * @see http://codex.wordpress.org/Function_Reference/wp_insert_attachment#Example
		 */
		public function create_image_from_url( $url, $name ) {

			if ( empty( $url ) || empty( $this->wp_upload_url ) ) {
				return false;
			}

			$filename = basename( $url );

			$upload_file = wp_upload_bits( $name, null, file_get_contents( $url ) );

			if ( ! $upload_file['error'] ) {

				$wp_filetype   = wp_check_filetype( $name, null );
				$attachment    = array(
					'post_mime_type' => $wp_filetype['type'],
					'post_parent'    => 0,
					'post_title'     => preg_replace( '/\.[^.]+$/', '', $name ),
					'post_content'   => __( 'Astra Site Image - ', 'astra-sites' ) . $name,
					'post_status'    => 'inherit',
				);
				$attachment_id = wp_insert_attachment( $attachment, $upload_file['file'], 0 );

				if ( ! is_wp_error( $attachment_id ) ) {

					require_once ABSPATH . 'wp-admin/includes/image.php';
					require_once ABSPATH . 'wp-admin/includes/media.php';

					$attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] );
					wp_update_attachment_metadata( $attachment_id, $attachment_data );

					update_post_meta( $attachment_id, 'astra-images', $_POST['id'] );
					update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( ! empty( $_POST['name'] ) ? $_POST['name'] : '' ) );

					return $attachment_id;
				}
			}

			return false;

		}

		/**
		 * Import Post Meta
		 *
		 * @since 2.0.0
		 *
		 * @param  integer $post_id  Post ID.
		 * @param  array   $metadata  Post meta.
		 * @return void
		 */
		public function import_post_meta( $post_id, $metadata ) {

			$metadata = (array) $metadata;

			$default_page_builder = Astra_Sites_Page::get_instance()->get_setting( 'page_builder' );

			if ( 'gutenberg' === $default_page_builder ) {
				return;
			}

			foreach ( $metadata as $meta_key => $meta_value ) {

				if ( $meta_value ) {

					if ( '_elementor_data' === $meta_key ) {

						$raw_data = json_decode( stripslashes( $meta_value ), true );

						if ( is_array( $raw_data ) ) {
							$raw_data = wp_slash( wp_json_encode( $raw_data ) );
						} else {
							$raw_data = wp_slash( $raw_data );
						}
					} else {

						if ( is_serialized( $meta_value, true ) ) {
							$raw_data = maybe_unserialize( stripslashes( $meta_value ) );
						} elseif ( is_array( $meta_value ) ) {
							$raw_data = json_decode( stripslashes( $meta_value ), true );
						} else {
							$raw_data = $meta_value;
						}
					}

					if ( '_elementor_page_settings' === $meta_key ) {

						if ( is_array( $raw_data ) ) {

							if ( isset( $raw_data['astra_sites_page_setting_enable'] ) ) {
								$raw_data['astra_sites_page_setting_enable'] = 'yes';
							}

							if ( isset( $raw_data['astra_sites_body_font_family'] ) ) {
								$raw_data['astra_sites_body_font_family'] = str_replace( "'", '', $raw_data['astra_sites_body_font_family'] );
							}

							for ( $i = 1; $i < 7; $i++ ) {

								if ( isset( $raw_data[ 'astra_sites_heading_' . $i . '_font_family' ] ) ) {

									$raw_data[ 'astra_sites_heading_' . $i . '_font_family' ] = str_replace( "'", '', $raw_data[ 'astra_sites_heading_' . $i . '_font_family' ] );
								}
							}
						}
					}

					update_post_meta( $post_id, $meta_key, $raw_data );
				}
			}
		}

		/**
		 * Import Post Meta
		 *
		 * @since 2.0.0
		 *
		 * @param  integer $post_id  Post ID.
		 * @param  array   $metadata  Post meta.
		 * @return void
		 */
		public function import_template_meta( $post_id, $metadata ) {

			$metadata = (array) $metadata;

			foreach ( $metadata as $meta_key => $meta_value ) {

				if ( $meta_value ) {

					if ( '_elementor_data' === $meta_key ) {

						$raw_data = json_decode( stripslashes( $meta_value ), true );

						if ( is_array( $raw_data ) ) {
							$raw_data = wp_slash( wp_json_encode( $raw_data ) );
						} else {
							$raw_data = wp_slash( $raw_data );
						}
					} else {

						if ( is_serialized( $meta_value, true ) ) {
							$raw_data = maybe_unserialize( stripslashes( $meta_value ) );
						} elseif ( is_array( $meta_value ) ) {
							$raw_data = json_decode( stripslashes( $meta_value ), true );
						} else {
							$raw_data = $meta_value;
						}
					}

					if ( '_elementor_page_settings' === $meta_key ) {

						if ( is_array( $raw_data ) ) {

							if ( isset( $raw_data['astra_sites_page_setting_enable'] ) ) {
								$raw_data['astra_sites_page_setting_enable'] = 'yes';
							}

							if ( isset( $raw_data['astra_sites_body_font_family'] ) ) {
								$raw_data['astra_sites_body_font_family'] = str_replace( "'", '', $raw_data['astra_sites_body_font_family'] );
							}

							for ( $i = 1; $i < 7; $i++ ) {

								if ( isset( $raw_data[ 'astra_sites_heading_' . $i . '_font_family' ] ) ) {

									$raw_data[ 'astra_sites_heading_' . $i . '_font_family' ] = str_replace( "'", '', $raw_data[ 'astra_sites_heading_' . $i . '_font_family' ] );
								}
							}
						}
					}

					update_post_meta( $post_id, $meta_key, $raw_data );
				}
			}
		}

		/**
		 * Close getting started notice for current user
		 *
		 * @since 1.3.5
		 * @return void
		 */
		public function getting_started_notice() {
			// Verify Nonce.
			check_ajax_referer( 'astra-sites', '_ajax_nonce' );

			if ( ! current_user_can( 'customize' ) ) {
				wp_send_json_error( __( 'You are not allowed to perform this action', 'astra-sites' ) );
			}

			update_user_meta( get_current_user_id(), '_astra_sites_gettings_started', true );
			wp_send_json_success();
		}

		/**
		 * Activate theme
		 *
		 * @since 1.3.2
		 * @return void
		 */
		public function activate_theme() {

			// Verify Nonce.
			check_ajax_referer( 'astra-sites', '_ajax_nonce' );

			if ( ! current_user_can( 'customize' ) ) {
				wp_send_json_error( __( 'You are not allowed to perform this action', 'astra-sites' ) );
			}

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
		public function get_reset_data() {

			if ( ! defined( 'WP_CLI' ) ) {
				check_ajax_referer( 'astra-sites', '_ajax_nonce' );

				if ( ! current_user_can( 'manage_options' ) ) {
					return;
				}
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
		public function backup_settings() {

			if ( ! defined( 'WP_CLI' ) ) {
				check_ajax_referer( 'astra-sites', '_ajax_nonce' );

				if ( ! current_user_can( 'manage_options' ) ) {
					wp_send_json_error( __( 'User not have permission!', 'astra-sites' ) );
				}
			}

			$file_name    = 'astra-sites-backup-' . gmdate( 'd-M-Y-h-i-s' ) . '.json';
			$old_settings = get_option( 'astra-settings', array() );
			$upload_dir   = Astra_Sites_Importer_Log::get_instance()->log_dir();
			$upload_path  = trailingslashit( $upload_dir['path'] );
			$log_file     = $upload_path . $file_name;
			$file_system  = Astra_Sites_Importer_Log::get_instance()->get_filesystem();

			// If file system fails? Then take a backup in site option.
			if ( false === $file_system->put_contents( $log_file, wp_json_encode( $old_settings ), FS_CHMOD_FILE ) ) {
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
		public function add_notice() {

			$theme_status = 'astra-sites-theme-' . $this->get_theme_status();

			Astra_Notices::add_notice(
				array(
					'id'               => 'astra-theme-activation-nag',
					'type'             => 'error',
					'class'            => 'astra-sites-notice',
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
		public function get_theme_status() {

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
		public function load_textdomain() {
			load_plugin_textdomain( 'astra-sites' );
		}

		/**
		 * Admin Notices
		 *
		 * @since 1.0.5
		 * @return void
		 */
		public function admin_notices() {

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
		public function action_links( $links ) {
			$action_links = array(
				'settings' => '<a href="' . admin_url( 'themes.php?page=astra-sites' ) . '" aria-label="' . esc_attr__( 'See Library', 'astra-sites' ) . '">' . esc_html__( 'See Library', 'astra-sites' ) . '</a>',
			);

			return array_merge( $action_links, $links );
		}

		/**
		 * Get the API URL.
		 *
		 * @since  1.0.0
		 */
		public static function get_api_domain() {
			return apply_filters( 'astra_sites_api_domain', 'https://websitedemos.net/' );
		}

		/**
		 * Setter for $api_url
		 *
		 * @since  1.0.0
		 */
		public function set_api_url() {
			$this->api_url = apply_filters( 'astra_sites_api_url', trailingslashit( self::get_api_domain() ) . '/wp-json/wp/v2/' );

			$this->pixabay_url     = 'https://pixabay.com/api/';
			$this->pixabay_api_key = '2727911-c4d7c1031949c7e0411d7e81e';
		}

		/**
		 * Enqueue Image Search scripts.
		 *
		 * @since  2.0.0
		 * @return void
		 */
		public function image_search_assets() {

			wp_enqueue_script( 'masonry' );
			wp_enqueue_script( 'imagesloaded' );

			wp_enqueue_script(
				'astra-sites-images-common',
				ASTRA_SITES_URI . 'inc/assets/js/dist/common.js',
				array( 'jquery', 'wp-util' ), // Dependencies, defined above.
				ASTRA_SITES_VER,
				true
			);

			$data = apply_filters(
				'astra_sites_images_common',
				array(
					'ajaxurl'             => esc_url( admin_url( 'admin-ajax.php' ) ),
					'asyncurl'            => esc_url( admin_url( 'async-upload.php' ) ),
					'pixabay_url'         => $this->pixabay_url,
					'pixabay_api_key'     => $this->pixabay_api_key,
					'is_bb_active'        => ( class_exists( 'FLBuilderModel' ) ),
					'is_brizy_active'     => ( class_exists( 'Brizy_Editor_Post' ) ),
					'is_elementor_active' => ( did_action( 'elementor/loaded' ) ),
					'is_bb_editor'        => ( class_exists( 'FLBuilderModel' ) ) ? ( FLBuilderModel::is_builder_active() ) : false,
					'is_brizy_editor'     => ( class_exists( 'Brizy_Editor_Post' ) ) ? ( isset( $_GET['brizy-edit'] ) || isset( $_GET['brizy-edit-iframe'] ) ) : false,
					'saved_images'        => get_option( 'astra-sites-saved-images', array() ),
					'pixabay_category'    => array(
						'all'            => __( 'All', 'astra-sites' ),
						'animals'        => __( 'Animals', 'astra-sites' ),
						'buildings'      => __( 'Architecture/Buildings', 'astra-sites' ),
						'backgrounds'    => __( 'Backgrounds/Textures', 'astra-sites' ),
						'fashion'        => __( 'Beauty/Fashion', 'astra-sites' ),
						'business'       => __( 'Business/Finance', 'astra-sites' ),
						'computer'       => __( 'Computer/Communication', 'astra-sites' ),
						'education'      => __( 'Education', 'astra-sites' ),
						'feelings'       => __( 'Emotions', 'astra-sites' ),
						'food'           => __( 'Food/Drink', 'astra-sites' ),
						'health'         => __( 'Health/Medical', 'astra-sites' ),
						'industry'       => __( 'Industry/Craft', 'astra-sites' ),
						'music'          => __( 'Music', 'astra-sites' ),
						'nature'         => __( 'Nature/Landscapes', 'astra-sites' ),
						'people'         => __( 'People', 'astra-sites' ),
						'places'         => __( 'Places/Monuments', 'astra-sites' ),
						'religion'       => __( 'Religion', 'astra-sites' ),
						'science'        => __( 'Science/Technology', 'astra-sites' ),
						'sports'         => __( 'Sports', 'astra-sites' ),
						'transportation' => __( 'Transportation/Traffic', 'astra-sites' ),
						'travel'         => __( 'Travel/Vacation', 'astra-sites' ),
					),
					'pixabay_order'       => array(
						'popular'  => __( 'Popular', 'astra-sites' ),
						'latest'   => __( 'Latest', 'astra-sites' ),
						'upcoming' => __( 'Upcoming', 'astra-sites' ),
						'ec'       => __( 'Editor\'s Choice', 'astra-sites' ),
					),
					'pixabay_orientation' => array(
						'any'        => __( 'Any Orientation', 'astra-sites' ),
						'vertical'   => __( 'Vertical', 'astra-sites' ),
						'horizontal' => __( 'Horizontal', 'astra-sites' ),
					),
					'integration'         => get_option( '_astra_images_integration', array() ),
					'title'               => __( 'Free Images by Astra', 'astra-sites' ),
					'search_placeholder'  => __( 'Pixabay Search - Ex: flowers', 'astra-sites' ),
					'downloading'         => __( 'Downloading...', 'astra-sites' ),
					'validating'          => __( 'Validating...', 'astra-sites' ),
					'empty_api_key'       => __( 'Please enter an API key.', 'astra-sites' ),
					'error_api_key'       => __( 'An error occured with code ', 'astra-sites' ),
					'_ajax_nonce'         => wp_create_nonce( 'astra-sites' ),
				)
			);
			wp_localize_script( 'astra-sites-images-common', 'astraImages', $data );

			wp_enqueue_script(
				'astra-sites-images-script',
				ASTRA_SITES_URI . 'inc/assets/js/dist/index.js',
				array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-components', 'wp-editor', 'wp-api-fetch', 'astra-sites-images-common' ), // Dependencies, defined above.
				ASTRA_SITES_VER,
				true
			);

			wp_enqueue_style( 'astra-sites-images', ASTRA_SITES_URI . 'inc/assets/css/images.css', ASTRA_SITES_VER, true );
			wp_style_add_data( 'astra-sites-images', 'rtl', 'replace' );
		}

		/**
		 * Getter for $api_url
		 *
		 * @since  1.0.0
		 */
		public function get_api_url() {
			return $this->api_url;
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

			// Image Search assets.
			$this->image_search_assets();

			wp_enqueue_script( 'astra-sites-install-theme', ASTRA_SITES_URI . 'inc/assets/js/install-theme.js', array( 'jquery', 'updates' ), ASTRA_SITES_VER, true );
			wp_enqueue_style( 'astra-sites-install-theme', ASTRA_SITES_URI . 'inc/assets/css/install-theme.css', null, ASTRA_SITES_VER, 'all' );
			wp_style_add_data( 'astra-sites-install-theme', 'rtl', 'replace' );

			$data = apply_filters(
				'astra_sites_install_theme_localize_vars',
				array(
					'installed'   => __( 'Installed! Activating..', 'astra-sites' ),
					'activating'  => __( 'Activating...', 'astra-sites' ),
					'activated'   => __( 'Activated!', 'astra-sites' ),
					'installing'  => __( 'Installing...', 'astra-sites' ),
					'ajaxurl'     => esc_url( admin_url( 'admin-ajax.php' ) ),
					'_ajax_nonce' => wp_create_nonce( 'astra-sites' ),
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

			// History.
			wp_register_script( 'astra-sites-history', ASTRA_SITES_URI . 'inc/assets/js/history.js', array( 'jquery' ), ASTRA_SITES_VER, true );

			// API.
			wp_register_script( 'astra-sites-api', ASTRA_SITES_URI . 'inc/assets/js/astra-sites-api.js', array( 'jquery', 'astra-sites-fetch' ), ASTRA_SITES_VER, true );

			// Admin Page.
			wp_enqueue_style( 'astra-sites-admin', ASTRA_SITES_URI . 'inc/assets/css/admin.css', ASTRA_SITES_VER, true );
			wp_style_add_data( 'astra-sites-admin', 'rtl', 'replace' );
			wp_enqueue_script( 'astra-sites-admin-page', ASTRA_SITES_URI . 'inc/assets/js/admin-page.js', array( 'jquery', 'wp-util', 'updates', 'jquery-ui-autocomplete', 'astra-sites-api', 'astra-sites-history', 'wp-url' ), ASTRA_SITES_VER, true );

			$data = $this->get_local_vars();

			wp_localize_script( 'astra-sites-admin-page', 'astraSitesVars', $data );
		}

		/**
		 * Returns Localization Variables.
		 *
		 * @since 2.0.0
		 */
		public function get_local_vars() {

			$stored_data = array(
				'astra-site-category'        => array(),
				'astra-site-page-builder'    => array(),
				'astra-sites'                => array(),
				'site-pages-category'        => array(),
				'site-pages-page-builder'    => array(),
				'site-pages-parent-category' => array(),
				'site-pages'                 => array(),
				'favorites'                  => get_option( 'astra-sites-favorites' ),
			);

			$favorite_data = get_option( 'astra-sites-favorites' );

			// Use this for premium demos.
			$request_params = apply_filters(
				'astra_sites_api_params',
				array(
					'purchase_key' => '',
					'site_url'     => '',
					'per-page'     => 15,
				)
			);

			$license_status = false;
			if ( is_callable( 'BSF_License_Manager::bsf_is_active_license' ) ) {
				$license_status = BSF_License_Manager::bsf_is_active_license( 'astra-pro-sites' );
			}

			$default_page_builder = Astra_Sites_Page::get_instance()->get_setting( 'page_builder' );

			$data = apply_filters(
				'astra_sites_localize_vars',
				array(
					// @codingStandardsIgnoreStart
					'debug'             => ( ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || isset( $_GET['debug'] ) ) ? true : false,
					// @codingStandardsIgnoreEnd
					'isPro'                      => defined( 'ASTRA_PRO_SITES_NAME' ) ? true : false,
					'isWhiteLabeled'             => Astra_Sites_White_Label::get_instance()->is_white_labeled(),
					'ajaxurl'                    => esc_url( admin_url( 'admin-ajax.php' ) ),
					'siteURL'                    => site_url(),
					'docUrl'                     => 'https://wpastra.com/',
					'getProText'                 => __( 'Get Agency Bundle', 'astra-sites' ),
					'getProURL'                  => esc_url( 'https://wpastra.com/agency/?utm_source=demo-import-panel&utm_campaign=astra-sites&utm_medium=wp-dashboard' ),
					'getUpgradeText'             => __( 'Upgrade', 'astra-sites' ),
					'getUpgradeURL'              => esc_url( 'https://wpastra.com/agency/?utm_source=demo-import-panel&utm_campaign=astra-sites&utm_medium=wp-dashboard' ),
					'_ajax_nonce'                => wp_create_nonce( 'astra-sites' ),
					'requiredPlugins'            => array(),
					'XMLReaderDisabled'          => ! class_exists( 'XMLReader' ) ? true : false,
					'strings'                    => array(
						/* translators: %s are HTML tags. */
						'warningXMLReader'         => sprintf( __( '%1$sRequired XMLReader PHP extension is missing on your server!%2$sAstra Sites import requires XMLReader extension to be installed. Please contact your web hosting provider and ask them to install and activate the XMLReader PHP extension.', 'astra-sites' ), '<div class="notice astra-sites-notice astra-sites-xml-notice notice-error"><p><b>', '</b></p><p>', '</p></div>' ),
						'warningBeforeCloseWindow' => __( 'Warning! Astra Site Import process is not complete. Don\'t close the window until import process complete. Do you still want to leave the window?', 'astra-sites' ),
						'importFailedBtnSmall'     => __( 'Error!', 'astra-sites' ),
						'importFailedBtnLarge'     => __( 'Error! Read Possibilities.', 'astra-sites' ),
						'viewSite'                 => __( 'Done! View Site', 'astra-sites' ),
						'importFailBtn'            => __( 'Import failed.', 'astra-sites' ),
						'importFailBtnLarge'       => __( 'Import failed.', 'astra-sites' ),
						'importDemo'               => __( 'Import This Site', 'astra-sites' ),
						'importingDemo'            => __( 'Importing..', 'astra-sites' ),
						'syncCompleteMessage'      => self::get_instance()->get_sync_complete_message(),
					),
					'log'                        => array(
						'bulkInstall'          => __( 'Installing Required Plugins..', 'astra-sites' ),
						'serverConfiguration'  => esc_url( 'https://wpastra.com/docs/?p=1314&utm_source=demo-import-panel&utm_campaign=import-error&utm_medium=wp-dashboard' ),
						'importWidgetsSuccess' => __( 'Imported Widgets!', 'astra-sites' ),
					),
					'default_page_builder'       => $default_page_builder,
					'default_page_builder_sites' => Astra_Sites_Page::get_instance()->get_sites_by_page_builder( $default_page_builder ),
					'sites'                      => $request_params,
					'settings'                   => array(),
					'page-builders'              => array(),
					'categories'                 => array(),
					'parent_categories'          => array(),
					'api_sites_and_pages'        => (array) $this->get_all_sites(),
					'api_sites_and_pages_tags'   => get_option( 'astra-sites-tags', array() ),
					'license_status'             => $license_status,
					'license_page_builder'       => get_option( 'astra-sites-license-page-builder', '' ),

					'ApiURL'                     => $this->api_url,
					'stored_data'                => $stored_data,
					'favorite_data'              => $favorite_data,
					'category_slug'              => 'astra-site-category',
					'page_builder'               => 'astra-site-page-builder',
					'cpt_slug'                   => 'astra-sites',
					'parent_category'            => '',
				)
			);

			return $data;
		}

		/**
		 * Register module required js on elementor's action.
		 *
		 * @since 2.0.0
		 */
		public function register_widget_scripts() {

			$page_builders = self::get_instance()->get_page_builders();
			$has_elementor = false;

			foreach ( $page_builders as $page_builder ) {

				if ( 'elementor' === $page_builder['slug'] ) {
					$has_elementor = true;
				}
			}

			if ( ! $has_elementor ) {
				return;
			}

			wp_enqueue_script( 'astra-sites-helper', ASTRA_SITES_URI . 'inc/assets/js/helper.js', array( 'jquery' ), ASTRA_SITES_VER, true );

			wp_enqueue_script( 'masonry' );
			wp_enqueue_script( 'imagesloaded' );

			// Image Search assets.
			$this->image_search_assets();

			wp_enqueue_script( 'astra-sites-elementor-admin-page', ASTRA_SITES_URI . 'inc/assets/js/elementor-admin-page.js', array( 'jquery', 'astra-sites-helper', 'wp-util', 'updates', 'masonry', 'imagesloaded' ), ASTRA_SITES_VER, true );

			wp_enqueue_style( 'astra-sites-admin', ASTRA_SITES_URI . 'inc/assets/css/admin.css', ASTRA_SITES_VER, true );
			wp_style_add_data( 'astra-sites-admin', 'rtl', 'replace' );

			// Use this for premium demos.
			$request_params = apply_filters(
				'astra_sites_api_params',
				array(
					'purchase_key' => '',
					'site_url'     => '',
					'per-page'     => 15,
				)
			);

			$license_status = false;
			if ( is_callable( 'BSF_License_Manager::bsf_is_active_license' ) ) {
				$license_status = BSF_License_Manager::bsf_is_active_license( 'astra-pro-sites' );
			}

			/* translators: %s are link. */
			$license_msg = sprintf( __( 'This is a premium website demo available only with the Agency Bundles you can purchase it from <a href="%s" target="_blank">here</a>.', 'astra-sites' ), 'https://wpastra.com/pricing/' );

			if ( defined( 'ASTRA_PRO_SITES_NAME' ) ) {
				/* translators: %s are link. */
				$license_msg = sprintf( __( 'This is a premium template available with Astra \'Agency\' packages. <a href="%s" target="_blank">Validate Your License</a> Key to import this template.', 'astra-sites' ), esc_url( admin_url( 'plugins.php?bsf-inline-license-form=astra-pro-sites' ) ) );
			}

			$data = apply_filters(
				'astra_sites_render_localize_vars',
				array(
					'sites'                      => $request_params,
					'settings'                   => array(),
					'page-builders'              => array(),
					'categories'                 => array(),
					'parent_categories'          => array(),
					'default_page_builder'       => 'elementor',
					'api_sites_and_pages'        => $this->get_all_sites(),
					'astra_blocks'               => $this->get_all_blocks(),
					'license_status'             => $license_status,
					'ajaxurl'                    => esc_url( admin_url( 'admin-ajax.php' ) ),
					'api_sites_and_pages_tags'   => get_option( 'astra-sites-tags', array() ),
					'default_page_builder_sites' => Astra_Sites_Page::get_instance()->get_sites_by_page_builder( 'elementor' ),
					'ApiURL'                     => $this->api_url,
					'_ajax_nonce'                => wp_create_nonce( 'astra-sites' ),
					'isPro'                      => defined( 'ASTRA_PRO_SITES_NAME' ) ? true : false,
					'license_msg'                => $license_msg,
					'isWhiteLabeled'             => Astra_Sites_White_Label::get_instance()->is_white_labeled(),
					'getProText'                 => __( 'Get Agency Bundle', 'astra-sites' ),
					'getProURL'                  => esc_url( 'https://wpastra.com/agency/?utm_source=demo-import-panel&utm_campaign=astra-sites&utm_medium=wp-dashboard' ),
					'astra_block_categories'     => get_option( 'astra-blocks-categories', array() ),
					'siteURL'                    => site_url(),
					'_ajax_nonce'                => wp_create_nonce( 'astra-sites' ),
				)
			);

			wp_localize_script( 'astra-sites-elementor-admin-page', 'astraElementorSites', $data );
		}

		/**
		 * Register module required js on elementor's action.
		 *
		 * @since 2.0.0
		 */
		public function popup_styles() {

			wp_enqueue_style( 'astra-sites-elementor-admin-page', ASTRA_SITES_URI . 'inc/assets/css/elementor-admin.css', ASTRA_SITES_VER, true );
			wp_style_add_data( 'astra-sites-elementor-admin-page', 'rtl', 'replace' );

		}

		/**
		 * Get all sites
		 *
		 * @since 2.0.0
		 * @return array All sites.
		 */
		public function get_all_sites() {
			$sites_and_pages = array();
			$total_requests  = (int) get_option( 'astra-sites-requests', 0 );
			for ( $page = 1; $page <= $total_requests; $page++ ) {
				$current_page_data = get_option( 'astra-sites-and-pages-page-' . $page, array() );
				if ( ! empty( $current_page_data ) ) {
					foreach ( $current_page_data as $page_id => $page_data ) {
						$sites_and_pages[ $page_id ] = $page_data;
					}
				}
			}

			return $sites_and_pages;
		}

		/**
		 * Get all blocks
		 *
		 * @since 2.0.0
		 * @return array All Elementor Blocks.
		 */
		public function get_all_blocks() {
			$blocks = array();
			for ( $page = 1; $page <= 2; $page++ ) {
				$current_page_data = get_option( 'astra-blocks-' . $page, array() );
				if ( ! empty( $current_page_data ) ) {
					foreach ( $current_page_data as $page_id => $page_data ) {
						$blocks[ $page_id ] = $page_data;
					}
				}
			}

			return $blocks;
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
			require_once ASTRA_SITES_DIR . 'inc/classes/class-astra-elementor-pages.php';
			require_once ASTRA_SITES_DIR . 'inc/classes/class-astra-elementor-images.php';
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

			if ( ! defined( 'WP_CLI' ) ) {
				check_ajax_referer( 'astra-sites', '_ajax_nonce' );

				if ( ! current_user_can( 'install_plugins' ) || ! isset( $_POST['init'] ) || ! $_POST['init'] ) {
					wp_send_json_error(
						array(
							'success' => false,
							'message' => __( 'User have not plugin install permissions.', 'astra-sites' ),
						)
					);
				}
			}

			$plugin_init = ( isset( $_POST['init'] ) ) ? esc_attr( $_POST['init'] ) : $init;

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

			$options = ( isset( $_POST['options'] ) ) ? json_decode( stripslashes( $_POST['options'] ) ) : $options;
			$enabled_extensions = ( isset( $_POST['enabledExtensions'] ) ) ? json_decode( stripslashes( $_POST['enabledExtensions'] ) ) : $enabled_extensions;

			$this->after_plugin_activate( $plugin_init, $options, $enabled_extensions );

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
		 * @param  array  $options            Site Options.
		 * @param  array  $enabled_extensions Enabled Extensions.
		 * @return mixed
		 */
		public function required_plugin( $required_plugins = array(), $options = array(), $enabled_extensions = array() ) {

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

			$options = ( isset( $_POST['options'] ) ) ? json_decode( stripslashes( $_POST['options'] ) ) : $options;
			$enabled_extensions = ( isset( $_POST['enabledExtensions'] ) ) ? json_decode( stripslashes( $_POST['enabledExtensions'] ) ) : $enabled_extensions;

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

							$this->after_plugin_activate( $plugin['init'], $options, $enabled_extensions );

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

							$this->after_plugin_activate( $plugin['init'], $options, $enabled_extensions );
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
		 * After Plugin Activate
		 *
		 * @since 2.0.0
		 * 
		 * @param  string $plugin_init        Plugin Init File.
		 * @param  array  $options            Site Options.
		 * @param  array  $enabled_extensions Enabled Extensions.
		 * @return void
		 */
		function after_plugin_activate( $plugin_init = '', $options = array(), $enabled_extensions = array() ) {
			$data = array(
				'astra_site_options' => $options,
				'enabled_extensions' => $enabled_extensions,
			);

			do_action( 'astra_sites_after_plugin_activation', $plugin_init, $data );
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
		 * Get Default Page Builders
		 *
		 * @since 2.0.0
		 * @return array
		 */
		public function get_default_page_builders() {
			return array(
				array(
					'id'   => 33,
					'slug' => 'elementor',
					'name' => 'Elementor',
				),
				array(
					'id'   => 34,
					'slug' => 'beaver-builder',
					'name' => 'Beaver Builder',
				),
				array(
					'id'   => 41,
					'slug' => 'brizy',
					'name' => 'Brizy',
				),
				array(
					'id'   => 42,
					'slug' => 'gutenberg',
					'name' => 'Gutenberg',
				),
			);
		}

		/**
		 * Get Page Builders
		 *
		 * @since 2.0.0
		 * @return array
		 */
		public function get_page_builders() {

			$stored_page_builders = get_option( 'astra-sites-page-builders', array() );

			if ( ! empty( $stored_page_builders ) ) {
				return $stored_page_builders;
			}

			return $this->get_default_page_builders();
		}

		/**
		 * Get Page Builder Filed
		 *
		 * @since 2.0.0
		 * @param  string $page_builder Page Bulider.
		 * @param  string $field        Field name.
		 * @return mixed
		 */
		public function get_page_builder_field( $page_builder = '', $field = '' ) {
			if ( empty( $page_builder ) ) {
				return '';
			}

			$page_builders = self::get_instance()->get_page_builders();
			if ( empty( $page_builders ) ) {
				return '';
			}

			foreach ( $page_builders as $key => $current_page_builder ) {
				if ( $page_builder === $current_page_builder['slug'] ) {
					if ( isset( $current_page_builder[ $field ] ) ) {
						return $current_page_builder[ $field ];
					}
				}
			}

			return '';
		}

		/**
		 * Get License Key
		 *
		 * @since 2.0.0
		 * @return array
		 */
		public function get_license_key() {
			if ( class_exists( 'BSF_License_Manager' ) ) {
				if ( BSF_License_Manager::bsf_is_active_license( 'astra-pro-sites' ) ) {
					return BSF_License_Manager::instance()->bsf_get_product_info( 'astra-pro-sites', 'purchase_key' );
				}
			}

			return '';
		}

		/**
		 * Get Sync Complete Message
		 *
		 * @since 2.0.0
		 * @param  boolean $echo Echo the message.
		 * @return mixed
		 */
		public function get_sync_complete_message( $echo = false ) {

			$message = __( 'Template library refreshed!', 'astra-sites' );

			if ( $echo ) {
				echo esc_html( $message );
			} else {
				return esc_html( $message );
			}
		}
	}

	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	Astra_Sites::get_instance();

endif;
