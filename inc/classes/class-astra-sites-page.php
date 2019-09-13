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
			add_action( 'wp_ajax_astra-sites-change-page-builder', array( $this, 'save_page_builder_on_ajax' ) );
			add_action( 'admin_init', array( $this, 'save_page_builder_on_submit' ) );
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
		function save_page_builder_on_submit() {
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
		 * Save Page Builder
		 *
		 * @return void
		 */
		function save_page_builder_on_ajax() {

			// Only admins can save settings.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error();
			}

			// Stored Settings.
			$stored_data = $this->get_settings();

			// New settings.
			$new_data = array(
				'page_builder' => ( isset( $_REQUEST['page_builder'] ) ) ? sanitize_key( $_REQUEST['page_builder'] ) : '',
			);

			// Merge settings.
			$data = wp_parse_args( $new_data, $stored_data );

			// Update settings.
			update_option( 'astra_sites_settings', $data );

			$sites = $this->get_sites_by_page_builder( $new_data['page_builder'] );

			wp_send_json_success( $sites );
		}

		/**
		 * Get Page Builder Sites
		 *
		 * @since x.x.x
		 *
		 * @param  string $default_page_builder default page builder slug.
		 * @return array page builder sites.
		 */
		function get_sites_by_page_builder( $default_page_builder = '' ) {
			$sites_and_pages = Astra_Sites::get_instance()->get_all_sites();

			$page_builder_keys = wp_list_pluck( $sites_and_pages, 'astra-site-page-builder' );

			$current_page_builder_sites = array();
			foreach ( $page_builder_keys as $site_id => $page_builder ) {
				if ( $default_page_builder === $page_builder ) {
					$current_page_builder_sites[ $site_id ] = $sites_and_pages[ $site_id ];
				}
			}

			return $current_page_builder_sites;
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
		public function get_settings() {

			$defaults = array(
				'page_builder' => '',
			);

			$stored_data = get_option( 'astra_sites_settings', $defaults );

			return wp_parse_args( $stored_data, $defaults );
		}

		/**
		 * Update Settings
		 *
		 * @return array Stored settings.
		 */
		public function update_settings( $args = array() ) {

			$stored_data = get_option( 'astra_sites_settings', array() );

			$new_data = wp_parse_args( $args, $stored_data );

			update_option( 'astra_sites_settings', $new_data );
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

			$current_slug = isset( $_GET['page'] ) ? esc_attr( $_GET['page'] ) : 'astra-sites';

			$default_page_builder = $this->get_setting( 'page_builder' );

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
					<div class="inner-wrap">
						<div class="inner">
							<div class="header">
								<span class="logo">
									<img src="<?php echo esc_url( ASTRA_SITES_URI . 'inc/assets/images/logo.svg' ); ?>">
									<h3 class="title"><?php _e( 'Getting Started', 'astra-sites' ); ?></h3>
								</span>
								<a href="<?php echo esc_url( admin_url() ); ?>" class="close"><span class="dashicons dashicons-no-alt"></span></a>
							</div>
							<form id="astra-sites-welcome-form" enctype="multipart/form-data" method="post">
								<h1><?php _e( 'Select Page Builder', 'astra-sites' ); ?></h1>
								<p><?php _e( 'Astra offers starter templates that can be imported in one click. These sites are available in the following page builders. Please choose your preferred page builder from the list below.', 'astra-sites' ); ?></p>
								<div class="fields">
									<ul class="page-builders">
										<?php
										$default_page_builder = $this->get_setting( 'page_builder' );
										$page_builders        = Astra_Sites::get_instance()->get_page_builders();
										foreach ( $page_builders as $key => $page_builder ) {
											?>
											<li data-page-builder="<?php echo $page_builder['slug']; ?>">
												<label>
													<input type="radio" name="page_builder" value="<?php echo $page_builder['name']; ?>">
													<img src="<?php echo $this->get_page_builder_image( $page_builder['slug'] ); ?>" />
													<div class="title"><?php echo $page_builder['name']; ?></div>
												</label>
											</li>
											<?php
										}
										?>
									</ul>
								</div>
								<input type="hidden" name="redirect_page" value="<?php echo $current_slug; ?>">
								<input type="hidden" name="message" value="saved" />
								<?php wp_nonce_field( 'astra-sites-welcome-screen', 'astra-sites-page-builder' ); ?>
							</form>
						</div>
					</div>
				</div>
			<?php } else { ?>

				<div class="nav-tab-wrapper">
					<?php

					$while_label = false;

					if ( is_callable( 'Astra_Ext_White_Label_Markup::get_whitelabel_string' ) ) {
						$while_label_title = Astra_Ext_White_Label_Markup::get_whitelabel_string( 'astra-sites', 'name' );
						if ( $while_label_title ) {
							$while_label = true;
						}
					}

					if ( ! $while_label ) {
						?>
					<div class="logo">
						<div class="astra-sites-logo-wrap">
							<img src="<?php echo esc_url( ASTRA_SITES_URI . 'inc/assets/images/logo.svg' ); ?>">
						</div>
					</div>
						<?php
					}
					?>
					<div class="back-to-layout" title="Back to Layout"><i class="icon-chevron-left"></i></div>
					<div id="astra-sites-filters">
						<div class="wp-filter hide-if-no-js">
							<div class="section-left">
								<div class="search-form">
									<input autocomplete="off" placeholder="<?php _e( 'Search...', 'astra-sites' ); ?>" type="search" aria-describedby="live-search-desc" id="wp-filter-search-input" class="wp-filter-search">
									<span class="icon-search search-icon"></span>
									<div class="astra-sites-autocomplete-result"></div>
								</div>
							</div>
						</div>
					</div>	

					<!-- <div class="menu" style="display:none;">
						<ul class="astra-sites-nav-items">
							<li><a href="#"><?php // _e( 'Site &amp; Pages', 'astra-sites' ); ?></a></li>
						</ul>
					</div> -->
					<div class="form">
						<div class="filters-wrap favorite-filters-wrap header-actions">
							<div class="filters-slug">
								<ul class="filter-links">
									<li>
										<a title="<?php _e( 'My Favorite', 'astra-sites' ); ?>" href="#" class="astra-sites-show-favorite-button">
											<i class="icon-heart"></i>
										</a>
									</li>
									<li>
										<a title="<?php _e( 'Sync Library', 'astra-sites' ); ?>" href="#" class="astra-sites-sync-library-button">
											<i class="icon-refresh"></i>
										</a>

										<?php
										$status = get_option( 'astra-sites-batch-is-complete', 'no' );
										if ( 'yes' === $status ) {
											?>
											<div class="astra-sites-sync-library-message success notice notice-alt notice-success is-dismissible">
												<p><?php _e( 'Template library refreshed!', 'astra-sites' ); ?> <button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php _e( 'Dismiss', 'astra-sites' ); ?></span></button></p>
											</div>
										<?php } ?>
									</li>
								</ul>
							</div>
						</div>
						<span class="page-builder-icon">
							<div class="selected-page-builder">
								<?php
								$page_builder = $this->get_default_page_builder();
								if ( $page_builder ) {
									?>
									<img src="<?php echo esc_url( $this->get_page_builder_image( $page_builder['slug'] ) ); ?>" />
									<span class="page-builder-title"><?php echo esc_html( $page_builder['name'] ); ?></span>
									<span class="dashicons dashicons-arrow-down"></span>
								<?php } ?>
							</div>
							<ul class="page-builders">
								<?php
								$default_page_builder = $this->get_setting( 'page_builder' );
								$page_builders        = Astra_Sites::get_instance()->get_page_builders();
								foreach ( $page_builders as $key => $page_builder ) {
									$class = '';
									if ( $default_page_builder === $page_builder['slug'] ) {
										$class = 'active';
									}
									?>
									<li data-page-builder="<?php echo $page_builder['slug']; ?>" class="<?php echo $class; ?>">
										<img src="<?php echo $this->get_page_builder_image( $page_builder['slug'] ); ?>" />
										<div class="title"><?php echo $page_builder['name']; ?></div>
									</li>
									<?php
								}
								?>
							</ul>
							<form id="astra-sites-welcome-form-inline" enctype="multipart/form-data" method="post" style="display: none;">
								<div class="fields">
									<input type="hidden" name="page_builder" class="page-builder-input" required="required" />
								</div>
								<input type="hidden" name="message" value="saved" />
								<input type="hidden" name="redirect_page" value="<?php echo $current_slug; ?>">
								<?php wp_nonce_field( 'astra-sites-welcome-screen', 'astra-sites-page-builder' ); ?>
							</form>
						</span>
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
		 * Get Default Page Builder
		 *
		 * @since x.x.x
		 *
		 * @return mixed page builders or empty string.
		 */
		function get_default_page_builder() {
			$default_page_builder = $this->get_setting( 'page_builder' );

			$page_builders = Astra_Sites::get_instance()->get_page_builders();

			foreach ( $page_builders as $key => $page_builder ) {
				if ( $page_builder['slug'] === $default_page_builder ) {
					return $page_builder;
				}
			}

			return '';
		}

		/**
		 * Get Page Builders
		 *
		 * @since x.x.x
		 *
		 * @param  string $slug Page Builder Slug.
		 * @return array page builders.
		 */
		function get_page_builder_image( $slug ) {

			$image = '';

			switch ( $slug ) {

				case 'elementor':
					$image = ASTRA_SITES_URI . 'inc/assets/images/elementor.jpg';
					break;

				case 'beaver-builder':
					$image = ASTRA_SITES_URI . 'inc/assets/images/beaver-builder.png';
					break;

				case 'gutenberg':
					$image = ASTRA_SITES_URI . 'inc/assets/images/gutenberg.jpg';
					break;

				case 'brizy':
					$image = ASTRA_SITES_URI . 'inc/assets/images/brizy.jpg';
					break;
			}

			return $image;
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
			$page_title = apply_filters( 'astra_sites_menu_page_title', __( 'Starter Templates', 'astra-sites' ) );

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

		/**
		 * Converts a period of time in seconds into a human-readable format representing the interval.
		 *
		 * Example:
		 *
		 *     echo self::interval( 90 );
		 *     // 1 minute 30 seconds
		 *
		 * @param  int $since A period of time in seconds.
		 * @return string An interval represented as a string.
		 */
		public function interval( $since ) {
			// Array of time period chunks.
			$chunks = array(
				/* translators: 1: The number of years in an interval of time. */
				array( 60 * 60 * 24 * 365, _n_noop( '%s year', '%s years', 'astra-sites' ) ),
				/* translators: 1: The number of months in an interval of time. */
				array( 60 * 60 * 24 * 30, _n_noop( '%s month', '%s months', 'astra-sites' ) ),
				/* translators: 1: The number of weeks in an interval of time. */
				array( 60 * 60 * 24 * 7, _n_noop( '%s week', '%s weeks', 'astra-sites' ) ),
				/* translators: 1: The number of days in an interval of time. */
				array( 60 * 60 * 24, _n_noop( '%s day', '%s days', 'astra-sites' ) ),
				/* translators: 1: The number of hours in an interval of time. */
				array( 60 * 60, _n_noop( '%s hour', '%s hours', 'astra-sites' ) ),
				/* translators: 1: The number of minutes in an interval of time. */
				array( 60, _n_noop( '%s minute', '%s minutes', 'astra-sites' ) ),
				/* translators: 1: The number of seconds in an interval of time. */
				array( 1, _n_noop( '%s second', '%s seconds', 'astra-sites' ) ),
			);

			if ( $since <= 0 ) {
				return __( 'now', 'astra-sites' );
			}

			/**
			 * We only want to output two chunks of time here, eg:
			 * x years, xx months
			 * x days, xx hours
			 * so there's only two bits of calculation below:
			 */
			$j = count( $chunks );

			// Step one: the first chunk.
			for ( $i = 0; $i < $j; $i++ ) {
				$seconds = $chunks[ $i ][0];
				$name    = $chunks[ $i ][1];

				// Finding the biggest chunk (if the chunk fits, break).
				$count = floor( $since / $seconds );
				if ( $count ) {
					break;
				}
			}

			// Set output var.
			$output = sprintf( translate_nooped_plural( $name, $count, 'astra-sites' ), $count );

			// Step two: the second chunk.
			if ( $i + 1 < $j ) {
				$seconds2 = $chunks[ $i + 1 ][0];
				$name2    = $chunks[ $i + 1 ][1];
				$count2   = floor( ( $since - ( $seconds * $count ) ) / $seconds2 );
				if ( $count2 ) {
					// Add to output var.
					$output .= ' ' . sprintf( translate_nooped_plural( $name2, $count2, 'astra-sites' ), $count2 );
				}
			}

			return $output;
		}

		/**
		 * Check Cron Status
		 *
		 * Gets the current cron status by performing a test spawn. Cached for one hour when all is well.
		 *
		 * @since 1.7.0
		 *
		 * @param bool $cache Whether to use the cached result from previous calls.
		 * @return true|WP_Error Boolean true if the cron spawner is working as expected, or a WP_Error object if not.
		 */
		public static function test_cron( $cache = true ) {
			global $wp_version;

			if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
				return new WP_Error( 'wp_portfolio_cron_error', __( 'ERROR! Cron schedules are disabled by setting constant DISABLE_WP_CRON to true.<br/>To start the import process please enable the cron by setting false. E.g. define( \'DISABLE_WP_CRON\', false );', 'astra-sites' ) );
			}

			if ( defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON ) {
				return new WP_Error( 'wp_portfolio_cron_error', __( 'ERROR! Cron schedules are disabled by setting constant ALTERNATE_WP_CRON to true.<br/>To start the import process please enable the cron by setting false. E.g. define( \'ALTERNATE_WP_CRON\', false );', 'astra-sites' ) );
			}

			$cached_status = get_transient( 'astra-portfolio-cron-test-ok' );

			if ( $cache && $cached_status ) {
				return true;
			}

			$sslverify     = version_compare( $wp_version, 4.0, '<' );
			$doing_wp_cron = sprintf( '%.22F', microtime( true ) );

			$cron_request = apply_filters(
				'cron_request',
				array(
					'url'  => site_url( 'wp-cron.php?doing_wp_cron=' . $doing_wp_cron ),
					'key'  => $doing_wp_cron,
					'args' => array(
						'timeout'   => 3,
						'blocking'  => true,
						'sslverify' => apply_filters( 'https_local_ssl_verify', $sslverify ),
					),
				)
			);

			$cron_request['args']['blocking'] = true;

			$result = wp_remote_post( $cron_request['url'], $cron_request['args'] );

			if ( is_wp_error( $result ) ) {
				return $result;
			} elseif ( wp_remote_retrieve_response_code( $result ) >= 300 ) {
				return new WP_Error(
					'unexpected_http_response_code',
					sprintf(
						/* translators: 1: The HTTP response code. */
						__( 'Unexpected HTTP response code: %s', 'astra-sites' ),
						intval( wp_remote_retrieve_response_code( $result ) )
					)
				);
			} else {
				set_transient( 'astra-portfolio-cron-test-ok', 1, 3600 );
				return true;
			}

		}
	}

	Astra_Sites_Page::get_instance();

}// End if.
