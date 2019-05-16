<?php
/**
 * Astra Sites Page
 *
 * @since 1.0.6
 * @package Astra Sites
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Astra_Sites_Page' ) ) {

	/**
	 * Astra Admin Settings
	 */
	class Astra_Sites_Page {

		/**
		 * View all actions
		 *
		 * @since 1.0.6
		 * @var array $view_actions
		 */
		public $view_actions = array();

		/**
		 * Member Variable
		 *
		 * @var instance
		 */
		private static $instance;

		/**
		 * Initiator
		 *
		 * @since 1.3.0
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
		 * @since 1.3.0
		 */
		public function __construct() {

			if ( ! is_admin() ) {
				return;
			}

			add_action( 'after_setup_theme', array( $this, 'init_admin_settings' ), 99 );
			add_action( 'admin_init', array( $this, 'save_page_builder' ) );
			add_action( 'admin_notices', array( $this, 'getting_started' ) );
		}

		/**
		 * Admin notice
		 *
		 * @since 1.3.5
		 *
		 * @return void
		 */
		function getting_started() {
			if ( 'plugins' !== get_current_screen()->base ) {
				return;
			}

			$processed    = get_user_meta( get_current_user_id(), '_astra_sites_gettings_started', true );
			$product_name = Astra_Sites_White_Label::get_instance()->page_title( 'Astra' );

			if ( $processed ) {
				return;
			}

			?>
			<div class="notice notice-info is-dismissible astra-sites-getting-started-notice">
				<?php /* translators: %1$s is the admin page URL, %2$s is product name. */ ?>
				<p><?php printf( __( 'Thank you for choosing %1$s! Check the library of <a class="astra-sites-getting-started-btn" href="%2$s">ready starter sites here »</a>', 'astra-sites' ), $product_name, admin_url( 'themes.php?page=astra-sites' ) ); ?></p>
			</div>
			<?php
		}

		/**
		 * Save Page Builder
		 *
		 * @return void
		 */
		function save_page_builder() {

			// Only admins can save settings.
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// Make sure we have a valid nonce.
			if ( isset( $_REQUEST['astra-sites-page-builder'] ) && wp_verify_nonce( $_REQUEST['astra-sites-page-builder'], 'astra-sites-welcome-screen' ) ) {

				// Stored Settings.
				$stored_data = $this->get_settings();
				$slug        = ( isset( $_REQUEST['redirect_page'] ) ) ? $_REQUEST['redirect_page'] : 'astra-sites';

				// New settings.
				$new_data = array(
					'page_builder' => ( isset( $_REQUEST['page_builder'] ) ) ? sanitize_key( $_REQUEST['page_builder'] ) : '',
				);

				// Merge settings.
				$data = wp_parse_args( $new_data, $stored_data );

				// Update settings.
				update_option( 'astra_sites_settings', $data );

				wp_redirect( admin_url( '/themes.php?page=' . $slug ) );
			}
		}

		/**
		 * Get single setting value
		 *
		 * @param  string $key      Setting key.
		 * @param  mixed  $defaults Setting value.
		 * @return mixed           Stored setting value.
		 */
		function get_setting( $key = '', $defaults = '' ) {

			$settings = $this->get_settings();

			if ( empty( $settings ) ) {
				return $defaults;
			}

			if ( array_key_exists( $key, $settings ) ) {
				return $settings[ $key ];
			}

			return $defaults;
		}

		/**
		 * Get Settings
		 *
		 * @return array Stored settings.
		 */
		function get_settings() {

			$defaults = array(
				'page_builder' => '',
			);

			$stored_data = get_option( 'astra_sites_settings', $defaults );

			return wp_parse_args( $stored_data, $defaults );
		}

		/**
		 * Admin settings init
		 */
		public function init_admin_settings() {
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 100 );
			add_action( 'admin_notices', array( $this, 'notices' ) );
			add_action( 'astra_sites_menu_general_action', array( $this, 'general_page' ) );
			add_action( 'astra_pages_menu_general_action', array( $this, 'general_page_for_astra_pages' ) );
		}

		/**
		 * Admin notice
		 *
		 * @since 1.2.8
		 */
		public function notices() {

			if ( 'appearance_page_astra-sites' !== get_current_screen()->id ) {
				return;
			}

			if ( ! class_exists( 'XMLReader' ) ) {
				?>
				<div class="notice astra-sites-xml-notice notice-error">
					<p><b><?php _e( 'Required XMLReader PHP extension is missing on your server!', 'astra-sites' ); ?></b></p>
					<?php /* translators: %s is the white label name. */ ?>
					<p><?php printf( __( '%s import requires XMLReader extension to be installed. Please contact your web hosting provider and ask them to install and activate the XMLReader PHP extension.', 'astra-sites' ), ASTRA_SITES_NAME ); ?></p>
				</div>
				<?php
			}
		}

		/**
		 * Init Nav Menu
		 *
		 * @param mixed $action Action name.
		 * @since 1.0.6
		 */
		public function init_nav_menu( $action = '' ) {

			if ( '' !== $action ) {
				$this->render_tab_menu( $action );
			}
		}

		/**
		 * Render tab menu
		 *
		 * @param mixed $action Action name.
		 * @since 1.0.6
		 */
		public function render_tab_menu( $action = '' ) {
			?>
			<div id="astra-sites-menu-page">
				<?php $this->render( $action ); ?>
			</div>
			<?php
		}

		/**
		 * View actions
		 *
		 * @since 1.0.11
		 */
		public function get_view_actions() {

			if ( empty( $this->view_actions ) ) {

				$this->view_actions = apply_filters(
					'astra_sites_menu_item',
					array()
				);
			}

			return $this->view_actions;
		}

		/**
		 * Prints HTML content for tabs
		 *
		 * @param mixed $action Action name.
		 * @since 1.0.6
		 */
		public function render( $action ) {

			// Settings update message.
			if ( isset( $_REQUEST['message'] ) && ( 'saved' === $_REQUEST['message'] || 'saved_ext' === $_REQUEST['message'] ) ) {
				?>
					<span id="message" class="notice notice-success is-dismissive"><p> <?php esc_html_e( 'Settings saved successfully.', 'astra-sites' ); ?> </p></span>
				<?php
			}

			$default_page_builder = $this->get_setting( 'page_builder' );
			$current_slug         = isset( $_GET['page'] ) ? esc_attr( $_GET['page'] ) : 'astra-sites';

			if ( empty( $default_page_builder ) || isset( $_GET['change-page-builder'] ) ) {

				$plugins       = get_option( 'active_plugins', array() );
				$page_builders = array();
				if ( $plugins ) {
					foreach ( $plugins as $key => $plugin_init ) {
						if ( false !== strpos( $plugin_init, 'elementor' ) ) {
							$page_builders[] = 'elementor';
						}
						if ( false !== strpos( $plugin_init, 'beaver-builder' ) ) {
							$page_builders[] = 'beaver-builder';
						}
						if ( false !== strpos( $plugin_init, 'brizy' ) ) {
							$page_builders[] = 'brizy';
						}
					}
				}
				$page_builders   = array_unique( $page_builders );
				$page_builders[] = 'gutenberg';
				$page_builders   = implode( ',', $page_builders );
				?>
				<div class="astra-sites-welcome" data-plugins="<?php echo esc_attr( $page_builders ); ?>">
					<div class="inner">
						<form id="astra-sites-welcome-form" enctype="multipart/form-data" method="post">
							<h1><?php _e( 'Select Page Builder', 'astra-sites' ); ?></h1>
							<p><?php _e( 'Astra offers starter sites that can be imported in one click. These templates are available in few different page builders. Please choose your preferred page builder from the list below.', 'astra-sites' ); ?></p>
							<div class="fields">
								<ul class="page-builders">
									<li>
										<label>
											<input type="radio" name="page_builder" value="gutenberg">
											<img src="<?php echo esc_url( ASTRA_SITES_URI . 'inc/assets/images/gutenberg.jpg' ); ?>" />
											<div class="title"><?php _e( 'Gutenberg', 'astra-sites' ); ?></div>
										</label>
									</li>
									<li>
										<label>
											<input type="radio" name="page_builder" value="elementor">
											<img src="<?php echo esc_url( ASTRA_SITES_URI . 'inc/assets/images/elementor.jpg' ); ?>" />
											<div class="title"><?php _e( 'Elementor', 'astra-sites' ); ?></div>
										</label>
									</li>
									<li>
										<label>
											<input type="radio" name="page_builder" value="beaver-builder">
											<img src="<?php echo esc_url( ASTRA_SITES_URI . 'inc/assets/images/beaver-builder.png' ); ?>" />
											<div class="title"><?php _e( 'Beaver Builder', 'astra-sites' ); ?></div>
										</li>
									<li>
										<label>
											<input type="radio" name="page_builder" value="brizy">
											<img src="<?php echo esc_url( ASTRA_SITES_URI . 'inc/assets/images/brizy.jpg' ); ?>" />
											<div class="title"><?php _e( 'Brizy', 'astra-sites' ); ?></div>
										</label>
									</li>
								</ul>
								<div class="astra-sites-page-builder-notice" style="display: none;">
									<p class="description"><?php _e( 'Please select your favorite page builder to continue..', 'astra-sites' ); ?></p>
								</div>
								<?php submit_button( __( 'Next', 'astra-sites' ), 'primary button-hero disabled' ); ?>
							</div>
							<input type="hidden" name="redirect_page" value="<?php echo $current_slug; ?>">
							<input type="hidden" name="message" value="saved" />
							<?php wp_nonce_field( 'astra-sites-welcome-screen', 'astra-sites-page-builder' ); ?>
						</form>
					</div>
				</div>
			<?php } else { ?>
				<?php
				$page_title = apply_filters( 'astra_sites_page_title', __( 'Astra Starter Sites', 'astra-sites' ) );
				?>
				<div class="nav-tab-wrapper">
					<div class="logo">
						<div class="astra-sites-logo-wrap"><a href="" target="_blank" rel="noopener">
							<img src="<?php echo esc_url( ASTRA_SITES_URI . 'inc/assets/images/logo.svg' ); ?>" alt="<?php echo $page_title; ?>"></a>
						</div>
						<h1 class='astra-sites-title'> <?php echo esc_html( $page_title ); ?> </h1>
					</div>

					<div class="menu" style="display:none;">
						<ul class="astra-sites-nav-items">
							<li><a href="#"><?php _e( 'Site &amp; Pages', 'astra-sites'); ?></a></li>
						</ul>
					</div>
					<div class="form">
						<form id="astra-sites-welcome-form-inline" enctype="multipart/form-data" method="post">
							<div class="fields">
								<select name="page_builder" required="required">
									<option value="gutenberg" <?php selected( $default_page_builder, 'gutenberg' ); ?>><?php _e( 'Block Editor (Gutenberg)', 'astra-sites' ); ?></option>
									<option value="elementor" <?php selected( $default_page_builder, 'elementor' ); ?>><?php _e( 'Elementor', 'astra-sites' ); ?></option>
									<option value="beaver-builder" <?php selected( $default_page_builder, 'beaver-builder' ); ?>><?php _e( 'Beaver Builder', 'astra-sites' ); ?></option>
									<option value="brizy" <?php selected( $default_page_builder, 'brizy' ); ?>><?php _e( 'Brizy', 'astra-sites' ); ?></option>
								</select>
							</div>
							<input type="hidden" name="message" value="saved" />
							<input type="hidden" name="redirect_page" value="<?php echo $current_slug; ?>">
							<?php wp_nonce_field( 'astra-sites-welcome-screen', 'astra-sites-page-builder' ); ?>
						</form>
					</div>
					<?php
					$view_actions = $this->get_view_actions();

					foreach ( $view_actions as $slug => $data ) {

						if ( ! $data['show'] ) {
							continue;
						}

						$url = $this->get_page_url( $slug );

						if ( 'general' === $slug ) {
							update_option( 'astra_parent_page_url', $url );
						}

						$active = ( $slug === $action ) ? 'nav-tab-active' : '';
						?>
							<a class='nav-tab <?php echo esc_attr( $active ); ?>' href='<?php echo esc_url( $url ); ?>'> <?php echo esc_html( $data['label'] ); ?> </a>
					<?php } ?>
				</div><!-- .nav-tab-wrapper -->
				<?php
			}
		}

		/**
		 * Get and return page URL
		 *
		 * @param string $menu_slug Menu name.
		 * @since 1.0.6
		 * @return  string page url
		 */
		public function get_page_url( $menu_slug ) {

			$current_slug = isset( $_GET['page'] ) ? esc_attr( $_GET['page'] ) : 'astra-sites';
			$parent_page  = 'themes.php';

			if ( strpos( $parent_page, '?' ) !== false ) {
				$query_var = '&page=' . $current_slug;
			} else {
				$query_var = '?page=' . $current_slug;
			}

			$parent_page_url = admin_url( $parent_page . $query_var );

			$url = $parent_page_url . '&action=' . $menu_slug;

			return esc_url( $url );
		}

		/**
		 * Add main menu
		 *
		 * @since 1.0.6
		 */
		public function add_admin_menu() {
			$page_title = apply_filters( 'astra_sites_menu_page_title', __( 'Astra Starter Sites', 'astra-sites' ) );

			$page = add_theme_page( $page_title, $page_title, 'manage_options', 'astra-sites', array( $this, 'menu_callback' ) );
		}

		/**
		 * Menu callback
		 *
		 * @since 1.0.6
		 */
		public function menu_callback() {

			$current_slug = isset( $_GET['action'] ) ? esc_attr( $_GET['action'] ) : 'general';

			$active_tab   = str_replace( '_', '-', $current_slug );
			$current_slug = str_replace( '-', '_', $current_slug );
			?>
			<div class="astra-sites-menu-page-wrapper">
				<?php $this->init_nav_menu( $active_tab ); ?>
				<?php do_action( 'astra_sites_menu_' . esc_attr( $current_slug ) . '_action' ); ?>
			</div>
			<?php
		}

		/**
		 * Include general page
		 *
		 * @since 1.0.6
		 */
		public function general_page() {
			$default_page_builder = $this->get_setting( 'page_builder' );
			if ( empty( $default_page_builder ) || isset( $_GET['change-page-builder'] ) ) {
				return;
			}

			$global_cpt_meta = array(
				'category_slug' => 'astra-site-category',
				'cpt_slug'      => 'astra-sites',
				'page_builder'  => 'astra-site-page-builder',
			);

			require_once ASTRA_SITES_DIR . 'inc/includes/admin-page.php';
		}
	}

	Astra_Sites_Page::get_instance();

}// End if.
