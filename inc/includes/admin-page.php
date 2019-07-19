<?php
/**
 * Shortcode Markup
 *
 * TMPL - Single Demo Preview
 * TMPL - No more demos
 * TMPL - Filters
 * TMPL - List
 *
 * @package Astra Sites
 * @since 1.0.0
 */

defined( 'ABSPATH' ) or exit;

$import_text = ( 'site-pages' === $global_cpt_meta['cpt_slug'] ) ? __( 'Import Page', 'astra-sites' ) : __( 'Import Site', 'astra-sites' );

$api_args = array(
	'timeout' => 60,
);
?>

<div class="wrap" id="astra-sites-admin" data-slug="<?php echo $global_cpt_meta['cpt_slug']; ?>">


	<?php
	// DEBUGGING PURPOSE ONLY.
	if ( isset( $_GET['debug'] ) ) {
		$crons  = _get_cron_array();
		$events = array();

		if ( empty( $crons ) ) {
			return new WP_Error(
				'no_events',
				__( 'You currently have no scheduled cron events.', 'wp-crontrol' )
			);
		}

		foreach ( $crons as $time => $cron ) {
			$events[ array_keys( $cron )[0] ] = $time;
		}

		$expired = get_transient( 'astra-sites-import-check' );
		if ( $expired ) {
			global $wpdb;
			$transient         = 'astra-sites-import-check';
			$transient_timeout = $wpdb->get_col(
				"
		      SELECT option_value
		      FROM $wpdb->options
		      WHERE option_name
		      LIKE '%_transient_timeout_$transient%'
		    "
			);
			$older_date        = $transient_timeout[0];
			$status            = 'Transient: Not Expired! Recheck in ' . human_time_diff( time(), $older_date );
		} else {
			$status = 'Transient: Starting.. Process for each 5 minutes.';
		}
		$temp  = get_option( 'astra-sites-batch-status-string', '' );
		$temp .= isset( $events['wp_astra_site_importer_cron'] ) ? '<br/>Batch: Recheck batch in ' . human_time_diff( time(), $events['wp_astra_site_importer_cron'] ) : '<br/>Batch: Not Started! Until the Transient expire.';
		?>
		<div class="batch-log"><?php echo $temp; ?><br> <?php echo $status; ?></div>
	<?php } ?>



	<div id="astra-sites-filters">
		<?php
		$categories = get_option( 'astra-sites-categories', array() );
		if ( ! empty( $categories ) ) {
			?>
			<div class="wp-filter hide-if-no-js">
				<div class="section-left">
					<div class="search-form">
						<input autocomplete="off" placeholder="<?php _e( 'Search Sites...', 'astra-sites' ); ?>" type="search" aria-describedby="live-search-desc" id="wp-filter-search-input" class="wp-filter-search">
						<span class="dashicons-search dashicons search-icon"></span>
						<div class="filters-wrap filters-wrap-page-categories">
							<div class="filters-slug" data-id="astra-site-category">
								<ul class="filter-links astra-site-category" data-category="astra-site-category">
									<?php if ( $categories ) { ?>
										<li>
											<a href="#" data-group="" class="current">All</a>
										</li>
										<?php foreach ( $categories as $category_id => $category ) { ?>
											<li>
												<a href="#" data-group="category-<?php echo $category['slug']; ?>" class="<?php echo $category['name']; ?>"><?php echo $category['name']; ?></a>
											</li>
										<?php } ?>
									<?php } ?>
								</ul>
							</div>
						</div>
					</div>
				</div>
				<div class="section-right">
					<div class="filters-wrap favorite-filters-wrap">
						<div class="filters-slug">
							<ul class="filter-links">
								<li>
									<a href="#">
										<span><i class="dashicons-heart dashicons"></i></span><span class="favorite-filters-title"><span>My Favorites</span></span>
									</a>
								</li>
								<li>
									<a href="#" class="astra-sites-sync-library-button">
										<span><i class="dashicons dashicons-update"></i></span><span><span class="astra-sites-sync-library">Sync Library</span></span>
									</a>
								</li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		<?php } ?>
	</div>

	<div id="astra-pages-back-wrap"></div>

	<?php do_action( 'astra_sites_before_site_grid' ); ?>

	<div class="theme-browser rendered">
		<div id="astra-sites" class="themes wp-clearfix">
			<?php
			$favorite_site_ids = get_option( 'astra-sites-favorites', array() );
			$sites_and_pages   = Astra_Sites::get_instance()->get_all_sites();
			if ( ! empty( $sites_and_pages ) ) {
				foreach ( $sites_and_pages as $site_id => $site ) {
					$site_page_builder = isset( $site['astra-site-page-builder'] ) ? sanitize_key( $site['astra-site-page-builder'] ) : '';
					if ( ! empty( $site_page_builder ) ) {
						if ( $default_page_builder === $site_page_builder ) {
							$category_classes = isset( $site['astra-site-category'] ) ? 'category-' . implode( ' category-', $site['astra-site-category'] ) : '';
							$type_classes     = isset( $site['astra-sites-type'] ) ? 'site-type-' . $site['astra-sites-type'] : '';
							$builder_classes  = isset( $site['astra-site-page-builder'] ) ? 'page-builder-' . $site['astra-site-page-builder'] : '';
							?>
							<div class="theme astra-theme site-single publish <?php echo $category_classes; ?> <?php echo $type_classes; ?> <?php echo $builder_classes; ?>" data-site-id="<?php echo $site_id; ?>">
								<div class="inner">
									<span class="site-preview" data-href="" data-title="<?php echo $site['title']; ?>">
										<div class="theme-screenshot one loading" data-src="<?php echo $site['featured-image-url']; ?>" style="background-image: url('<?php echo $site['tiny-image-url']; ?>');"></div>
									</span>
									<div class="theme-id-container">
										<h3 class="theme-name" id="astra-theme-name"><?php echo $site['title']; ?></h3>
										<?php
										$class = '';
										if ( in_array( $site_id, $favorite_site_ids ) ) {
											$class = 'is-favorite';
										}
										?>
										<div class="favorite-action-wrap <?php echo $class; ?>" data-favorite="false">
											<span><i class="dashicons-heart dashicons"></i></span>
										</div>
									</div>
								</div>
								<?php if ( isset( $site['astra-sites-type'] ) && ! empty( $site['astra-sites-type'] ) && 'free' !== $site['astra-sites-type'] ) { ?>
									<div class="site-type-wrap">
										<span class="site-type premium"><?php echo str_replace( 'agency-mini', 'agency', $site['astra-sites-type'] ); ?></span>
									</div>
								<?php } ?>
							</div>
							<?php
						}
					}
				}
			}
			?>
		</div>
		<div class="astra-sites-no-sites" style="display:none;">
			<h2><?php _e( 'No Demos found, Try a different search.', 'astra-sites' ); ?></h2>
			<p class="description">
				<?php
				/* translators: %1$s External Link */
				printf( __( 'Don\'t see a site that you would like to import?<br><a target="_blank" href="%1$s">Please suggest us!</a>', 'astra-sites' ), esc_url( 'https://wpastra.com/sites-suggestions/?utm_source=demo-import-panel&utm_campaign=astra-sites&utm_medium=suggestions' ) );
				?>
			</p>
		</div>
		<div id="site-pages" class="themes wp-clearfix"></div>
	</div>

	<?php do_action( 'astra_sites_after_site_grid' ); ?>

</div>

<?php
/**
 * TMPL - Pro Site Description
 */
?>
<script type="text/template" id="tmpl-astra-sites-pro-site-description">
	<p><?php _e( 'Liked this demo?', 'astra-sites' ); ?></p>
	<p>
		<?php
			/* translators: %s is pricing page link */
			printf( __( 'It is a premium website demo which is available only with the Agency Bundles <a href="%s" target="_blank">Buy Now!</a>', 'astra-sites' ), 'https://wpastra.com/pricing/' );
		?>
	</p>
	<p>
		<?php
			/* translators: %s is article link */
			printf( __( 'Already own an Agency Bundle? Read an article to know how you can <a href="%s" target="_blank">import a premium website demo</a>.', 'astra-sites' ), 'https://wpastra.com/docs/import-astra-agency-website-demos/' );
		?>
	</p>
</script>

<?php
/**
 * TMPL - Pro Site Description for Inactive license
 */
?>
<script type="text/template" id="tmpl-astra-sites-pro-inactive-site-description">
	<p><?php _e( 'You are just 2 minutes away from importing this demo!', 'astra-sites' ); ?></p>
	<p><?php _e( 'It is a premium website demo and you need to activate the license to access it.', 'astra-sites' ); ?></p>
	<p>
		<?php
			/* translators: %s is article link */
			printf( __( 'Learn how you can <a href="%s" target="_blank">activate the license</a> of the Astra Premium Sites plugin.', 'astra-sites' ), 'https://wpastra.com/docs/activate-license-for-astra-premium-sites-plugin/' );
		?>
	</p>
</script>

<?php
/**
 * TMPL - Third Party Required Plugins
 */
?>
<script type="text/template" id="tmpl-astra-sites-third-party-required-plugins">
	<div class="astra-sites-third-party-required-plugins-wrap">
		<h3 class="theme-name"><?php esc_html_e( 'Required Plugin Missing', 'astra-sites' ); ?></h3>
		<p><?php esc_html_e( 'This starter site requires premium plugins. As these are third party premium plugins, you\'ll need to purchase, install and activate them first.', 'astra-sites' ); ?></p>
		<ul class="astra-sites-third-party-required-plugins">
			<# for ( key in data ) { #>
				<li class="plugin-card plugin-card-{{data[ key ].slug}}'" data-slug="{{data[ key ].slug }}" data-init="{{data[ key ].init}}" data-name="{{data[ key ].name}}"><a href="{{data[ key ].link}}" target="_blank">{{data[ key ].name}}</a></li>
			<# } #>
		</ul>
	</div>
</script>

<?php
/**
 * TMPL - Single Site Preview
 */
?>
<script type="text/template" id="tmpl-astra-sites-single-site-preview">
	<div class="single-site-wrap">
		<div class="single-site">
			<div class="single-site-preview-wrap">
				<div class="astra-pages-back-wrap">
					<a class="astra-pages-back" href="javascript:void(0);"><?php _e( 'Back to Layouts', 'astra-sites' ); ?></a>
				</div>
				<div class="single-site-preview">
					<img src="{{data['featured-image-url']}}" />
				</div>
			</div>
			<div class="single-site-pages-wrap">
				<div class="single-site-pages-header">
					<h2 class="astra-site-title">{{{data['title']}}}</h2>
					<span class="count" style="display: none"></span>
				</div>
				<div class="single-site-pages">
					<div id="single-pages">
						<# for ( page_id in data.pages ) { #>
							<div class="theme astra-theme site-single" data-page-id="{{page_id}}" >
								<div class="inner">
									<#
									var featured_image_class = '';
									var featured_image = data.pages[page_id]['featured-image-url'] || '';
									if( '' === featured_image ) {
										featured_image = '<?php echo esc_url( ASTRA_SITES_URI . 'inc/assets/images/placeholder.png' ); ?>';
										featured_image_class = ' no-featured-image ';
									}

									var featured_tiny_image = data.pages[page_id]['tiny-image-url'] || '';
									if( '' === featured_tiny_image ) {
										featured_tiny_image = '<?php echo esc_url( ASTRA_SITES_URI . 'inc/assets/images/placeholder.png' ); ?>';
										featured_image_class = ' no-featured-tiny-image ';
									}
									console.log( featured_image );
									#>
									<span class="site-preview" data-href="?TB_iframe=true&width=600&height=550" data-title="{{ data.pages[page_id]['title'] }}">
										<div class="theme-screenshot one {{ featured_image_class }}" data-src="{{ featured_image }}" style="background-image: url('{{ featured_tiny_image }}');"></div>
									</span>
									<div class="theme-id-container">
										<h3 class="theme-name" id="astra-theme-name">
											{{{ data.pages[page_id]['title'] }}}
										</h3>
										<#
										/*var fav_class = "";
										var fav_flag = false;
										for ( fav_item in data.args.favorites ) {
											if ( data.items[ page_id ].id.toString() == data.args.favorites[fav_item] ) {
												fav_class = "is-favorite";
												fav_flag = true;
												break;
											}
										}*/
										#>
									</div>
								</div>
							</div>
						<# } #>
					</div>
				</div>
			</div>
			<div class="single-site-footer">
				<# console.log( data ) #>
				<div class="site-action-buttons-wrap">
					<a href="{{data['astra-site-url']}}" class="button button-hero site-preview-button" target="_blank">Preview This Site <i class="dashicons dashicons-external"></i></a>
					<div>
						<# if( 'free' !== data['astra-sites-type'] && ! astraRenderGrid.license_status ) { #>
							<a class="button button-hero button-primary" href="{{astraSitesAdmin.getProURL}}" target="_blank">{{astraSitesAdmin.getProText}}<i class="dashicons dashicons-external"></i></a>
							<# if( ! astraSitesAdmin.isPro ) { #>
								<span class="dashicons dashicons-editor-help astra-sites-get-agency-bundle-button"></span>
							<# } #>
						<# } else { #>
							<div class="button button-hero button-primary site-import-layout-button disabled">Import Layout</div>
							<div style="margin-left: 5px;" class="button button-hero button-primary site-import-site-button">Import Complete Site</div>
						<# } #>
					</div>
				</div>
			</div>
		</div>

		<div class="astra-sites-result-preview" style="display: none;"></div>

		<div class="astra-sites-result-preview-next-step" style="display: none;">
			<div class="overlay"></div>
			<div class="inner">
				<h2><?php _e( 'We\'re importing your website.', 'astra-sites' ); ?></h2>
				<p><?php _e( 'The process can take anywhere between 2 to 10 minutes depending on the size of the website and speed of connection.', 'astra-sites' ); ?></p>
				<p><?php _e( 'Please do not close this browser window until the site is imported completely.', 'astra-sites' ); ?></p>
				<div class="current-importing-status-wrap">
					<div class="current-importing-status">
						<div class="current-importing-status-title"></div>
						<div class="current-importing-status-description"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</script>

<?php
/**
 * TMPL - First Screen
 */
?>
<script type="text/template" id="tmpl-astra-sites-result-preview">

	<div class="overlay"></div>
	<div class="inner">

		<# if( 'astra-sites' === data ) { #>
			<h2><?php _e( 'We are importing site!', 'astra-sites' ); ?></h2>
		<# } else { #>
			<h2><?php _e( 'We are importing page!', 'astra-sites' ); ?></h2>
		<# } #>

		<div class="astra-sites-import-content">
			<div class="install-theme-info">
				<div class="astra-sites-advanced-options-wrap">
					<div class="astra-sites-advanced-options">
						<ul class="astra-site-contents">
							<li class="astra-sites-import-plugins">
								<input type="checkbox" name="plugins" checked="checked" class="disabled checkbox" readonly>
								<strong><?php _e( 'Install Required Plugins', 'astra-sites' ); ?></strong>
								<span class="astra-sites-tooltip-icon" data-tip-id="astra-sites-tooltip-plugins-settings"><span class="dashicons dashicons-editor-help"></span></span>
								<div class="astra-sites-tooltip-message" id="astra-sites-tooltip-plugins-settings" style="display: none;">
									<ul class="required-plugins-list"><span class="spinner is-active"></span></ul>
								</div>
							</li>
							<# if( 'astra-sites' === data ) { #>
								<li class="astra-sites-import-customizer">
									<label>
										<input type="checkbox" name="customizer" checked="checked" class="checkbox">
										<strong>Import Customizer Settings</strong>
										<span class="astra-sites-tooltip-icon" data-tip-id="astra-sites-tooltip-customizer-settings"><span class="dashicons dashicons-editor-help"></span></span>
										<div class="astra-sites-tooltip-message" id="astra-sites-tooltip-customizer-settings" style="display: none;">
											<p><?php _e( 'Customizer is what gives a design to the website; and selecting this option replaces your current design with a new one.', 'astra-sites' ); ?></p>
											<p><?php _e( 'Backup of current customizer settings will be stored in "wp-content/astra-sites" directory, just in case if you want to restore it later.', 'astra-sites' ); ?></p>
										</div>
									</label>
								</li>
								<li class="astra-sites-import-xml">
									<label>
										<input type="checkbox" name="xml" checked="checked" class="checkbox">
										<strong>Import Content</strong>
									</label>
									<span class="astra-sites-tooltip-icon" data-tip-id="astra-sites-tooltip-site-content"><span class="dashicons dashicons-editor-help"></span></span>
									<div class="astra-sites-tooltip-message" id="astra-sites-tooltip-site-content" style="display: none;"><p><?php _e( 'Selecting this option will import dummy pages, posts, images and menus. If you do not want to import dummy content, please uncheck this option.', 'astra-sites' ); ?></p></div>
								</li>
								<li class="astra-sites-import-widgets">
									<label>
										<input type="checkbox" name="widgets" checked="checked" class="checkbox">
										<strong>Import Widgets</strong>
									</label>
								</li>
							<# } #>
						</ul>
					</div>
					<# if( 'astra-sites' === data ) { #>
						<ul>
							<li class="astra-sites-reset-data">
								<label>
									<input type="checkbox" name="reset" class="checkbox">
									<strong>Delete Previously Imported Site</strong>
									<div class="astra-sites-tooltip-message" id="astra-sites-tooltip-reset-data" style="display: none;"><p><?php _e( 'WARNING: Selecting this option will delete data from your current website. Choose this option only if this is intended.', 'astra-sites' ); ?></p></div>
								</label>
							</li>
						</ul>
					<# } #>
				</div>
			</div>
			<div class="ast-importing-wrap">
				<p><?php _e( 'The process can take anywhere between 2 to 10 minutes depending on the size of the website and speed of connection.', 'astra-sites' ); ?></p>
				<p><?php _e( 'Please do not close this browser window until the site is imported completely.', 'astra-sites' ); ?></p>
				<div class="current-importing-status-wrap">
					<div class="current-importing-status">
						<div class="current-importing-status-title"></div>
						<div class="current-importing-status-description"></div>
					</div>
				</div>
			</div>
		</div>
		<div class="ast-actioms-wrap">
			<div class="button button-hero site-import-cancel"><?php _e( 'Cancel', 'astra-sites' ); ?></div>
			<a href="#" class="button button-hero button-primary astra-demo-import disabled site-install-site-button"><?php _e( 'Import', 'astra-sites' ); ?></a>
		</div>
	</div>
</script>

<?php
/**
 * TMPL - List
 */
?>
<script type="text/template" id="tmpl-astra-sites-list">

	<# console.log ( data ) #>
	<# if ( data.items.length ) { #>
		<# for ( key in data.items ) { #>

			<div class="theme astra-theme site-single {{ data.items[ key ].status }}" tabindex="0" aria-describedby="astra-theme-action astra-theme-name"
				data-demo-id="{{{ data.items[ key ].id }}}"
				data-type="{{{ data.type }}}"
				data-demo-type="{{{ data.items[ key ]['astra-site-type'] }}}"
				data-demo-url="{{{ data.items[ key ]['astra-site-url'] }}}"
				data-demo-api="{{{ data.items[ key ]['_links']['self'][0]['href'] }}}"
				data-demo-name="{{{  data.items[ key ].title.rendered }}}"
				data-demo-slug="{{{  data.items[ key ].slug }}}"
				data-demo-parent="{{{  data.items[ key ]['astra-site-parent-id'] }}}"
				data-screenshot="{{{ data.items[ key ]['featured-image-url'] }}}"
				data-content="{{{ data.items[ key ].content.rendered }}}"
				data-required-plugins="{{ JSON.stringify( data.items[ key ]['required-plugins'] ) }}"
				data-groups=["{{ data.items[ key ].tags }}"]>
				<input type="hidden" class="astra-site-options" value="{{ JSON.stringify(data.items[ key ]['astra-site-options-data'] ) }}" />
				<input type="hidden" class="astra-enabled-extensions" value="{{ JSON.stringify(data.items[ key ]['astra-enabled-extensions'] ) }}" />

				<div class="inner">
					<span class="site-preview" data-href="{{ data.items[ key ]['astra-site-url'] }}?TB_iframe=true&width=600&height=550" data-title="{{ data.items[ key ].title.rendered }}">
						<div class="theme-screenshot one" data-src="{{data.items[ key ]['featured-image-url']}}" style="background-image: url('<?php echo trailingslashit( Astra_Sites::get_instance()->get_api_domain() ); ?>/wp-content/uploads/tiny/image-{{ data.items[ key ].id }}-resized-tiny.jpg');"></div>
					</span>
					<# if ( data.items[ key ]['astra-site-type'] ) { #>
						<# var type = ( data.items[ key ]['astra-site-type'] !== 'premium' ) ? ( data.items[ key ]['astra-site-type'] ) : 'agency'; #>
						<span class="site-type {{data.items[ key ]['astra-site-type']}}">{{ type }}</span>
					<# } #>
					<# if ( data.items[ key ].status ) { #>
						<span class="status {{data.items[ key ].status}}">{{data.items[ key ].status}}</span>
					<# } #>
					<div class="theme-id-container">
						<h3 class="theme-name" id="astra-theme-name">
							{{{ data.items[ key ].title.rendered }}}
						</h3>
						<#
						var fav_class = "";
						var fav_flag = false;
						for ( fav_item in data.args.favorites ) {
							if ( data.items[ key ].id.toString() == data.args.favorites[fav_item] ) {
								fav_class = "is-favorite";
								fav_flag = true;
								break;
							}
						}
						#>
						<# if ( data.type != 'site-pages' ) { #>
						<div class="favorite-action-wrap {{fav_class}}" data-favorite={{fav_flag}}>
							<span><i class="dashicons-heart dashicons"></i></span>
						</div>
						<# } #>
						<!-- <div class="theme-actions">
							<div class="theme-action-wrap">
								<# if ( data.type != 'site-pages' ) { #>
								<button class="button install-page-preview"><?php esc_html_e( 'Import Pages', 'astra-sites' ); ?></button>
								<# } #>
								<button class="button-primary button preview install-theme-preview"><?php esc_html_e( 'Import Site', 'astra-sites' ); ?></button>
							</div>
						</div> -->
					</div>
				</div>
			</div>
		<# } #>
	<# } else { #>
		<p class="no-themes" style="display:block;">
			<?php _e( 'No Demos found, Try a different search.', 'astra-sites' ); ?>
			<span class="description">
				<?php
				/* translators: %1$s External Link */
				printf( __( 'Don\'t see a site that you would like to import?<br><a target="_blank" href="%1$s">Please suggest us!</a>', 'astra-sites' ), esc_url( 'https://wpastra.com/sites-suggestions/?utm_source=demo-import-panel&utm_campaign=astra-sites&utm_medium=suggestions' ) );
				?>
			</span>
		</p>
	<# } #>
</script>

<?php
wp_print_admin_notice_templates();
