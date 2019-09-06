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
				__( 'You currently have no scheduled cron events.', 'astra-sites' )
			);
		}

		foreach ( $crons as $time => $cron ) {
			$events[ array_keys( $cron )[0] ] = $time;
		}

		$expired = get_transient( 'astra-sites-import-check' );
		if ( $expired ) {
			global $wpdb;
			$transient = 'astra-sites-import-check';

			$transient_timeout = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT option_value
				FROM $wpdb->options
				WHERE option_name
				LIKE %s",
					'%_transient_timeout_' . $transient . '%'
				)
			);

			$older_date = $transient_timeout[0];
			$status     = 'Transient: Not Expired! Recheck in ' . human_time_diff( time(), $older_date );
		} else {
			$status = 'Transient: Starting.. Process for each 5 minutes.';
		}
		$temp  = get_option( 'astra-sites-batch-status-string', '' );
		$temp .= isset( $events['wp_astra_site_importer_cron'] ) ? '<br/>Batch: Recheck batch in ' . human_time_diff( time(), $events['wp_astra_site_importer_cron'] ) : '<br/>Batch: Not Started! Until the Transient expire.';
		?>
		<div class="batch-log"><?php echo $temp; ?><br> <?php echo $status; ?></div>
	<?php } ?>

	<?php do_action( 'astra_sites_before_site_grid' ); ?>

	<div class="theme-browser rendered">
		<div id="astra-sites" class="themes wp-clearfix"></div>
		<div id="site-pages" class="themes wp-clearfix"></div>
		<div class="astra-sites-result-preview" style="display: none;"></div>
	</div>

	<?php do_action( 'astra_sites_after_site_grid' ); ?>

</div>




<script type="text/template" id="tmpl-astra-sites-no-sites">
	<div class="astra-sites-no-sites">
		<div class="inner">
			<h2><?php _e( 'Sorry No Result Found.', 'astra-sites' ); ?></h2>
			<div class="content">
				<div class="empty-item">
					<img class="empty-collection-part" src="<?php echo ASTRA_SITES_URI . 'inc/assets/images/empty-collection.svg'; ?>" alt="empty-collection">
				</div>
				<div class="description">
					<p>
					<?php
					/* translators: %1$s External Link */
					printf( __( 'Don\'t see a template you would like to import?<br><a target="_blank" href="%1$s">Please Suggest Us!</a>', 'astra-sites' ), esc_url( 'https://wpastra.com/sites-suggestions/?utm_source=demo-import-panel&utm_campaign=astra-sites&utm_medium=suggestions' ) );
					?>
					</p>
					<div class="back-to-layout-button"><span class="button astra-sites-back">Back to Templates</span></div>
				</div>
			</div>
		</div>
	</div>
</script>

<script type="text/template" id="tmpl-astra-sites-no-favorites">
	<div class="astra-sites-no-favorites">
		<div class="inner">
			<h2><?php _e( 'Favorite template list is empty.', 'astra-sites' ); ?></h2>
			<div class="content">
				<div class="empty-item">
					<img class="empty-collection-part" src="<?php echo ASTRA_SITES_URI . 'inc/assets/images/empty-collection.svg'; ?>" alt="empty-collection">
				</div>
				<div class="description">
					<p>
					<?php
					/* translators: %1$s External Link */
					_e( 'You\'ll notice a heart-shaped symbol on every template card. Simply tap this icon to add template as Favorite.', 'astra-sites' );
					?>
					</p>
					<img src="<?php echo ASTRA_SITES_URI . 'inc/assets/images/arrow-blue.svg'; ?>" class="arrow-img">
					<div class="back-to-layout-button"><span class="button astra-sites-back"><?php _e( 'Back to Templates', 'astra-sites' ); ?></span></div>
				</div>
			</div>
		</div>
	</div>
</script>
<?php
/**
 * TMPL - Show Page Builder Sites
 */
?>
<script type="text/template" id="tmpl-astra-sites-page-builder-sites">
	<# for ( site_id in data ) { #>
	<#
		var current_site_id     = site_id;
		var type                = data[site_id]['type'] || 'site';
		var page_site_id        = data[site_id]['site_id'] || '';
		var favorite_status     = false;
		var favorite_class      = '';
		var favorite_title      = '<?php _e( 'Make as Favorite', 'astra-sites' ); ?>';
		var featured_image_url = data[site_id]['featured-image-url'];
		var thumbnail_image_url = data[site_id]['thumbnail-image-url'] || featured_image_url;
		var tiny_image_url      = data[site_id]['tiny-image-url'] || thumbnail_image_url;

		var site_type = data[site_id]['astra-sites-type'] || '';
		var page_id = '';
		if ( 'site' === type ) {
			if( Object.values( astraSitesVars.favorite_data ).indexOf( String(site_id) ) >= 0 ) {
				favorite_class = 'is-favorite';
				favorite_status = true;
				favorite_title = '<?php _e( 'Make as Unfavorite', 'astra-sites' ); ?>';
			}
		} else {
			thumbnail_image_url = featured_image_url;
			current_site_id = page_site_id;
			page_id = site_id;
		}

		var title = data[site_id]['title'] || '';
		var pages_count = data[site_id]['pages-count'] || 0;
		var pages_count_class = '';
		if( 'site' === type ) {
			if( pages_count ) {
				pages_count_class = 'has-pages';
			} else {
				pages_count_class = 'no-pages';
			}
		}
		var site_title = data[site_id]['site-title'] || '';

	#>
	<div class="theme astra-theme site-single {{favorite_class}} {{pages_count_class}} astra-sites-previewing-{{type}}" data-site-id="{{current_site_id}}" data-page-id="{{page_id}}">
		<div class="inner">
			<span class="site-preview" data-title="{{{title}}}">
				<div class="theme-screenshot one loading" data-src="{{thumbnail_image_url}}" data-featured-src="{{featured_image_url}}"></div>
			</span>
			<div class="theme-id-container">
				<div class="theme-name">
					<span class="title">
						<# if ( 'site' === type ) { #>
							<div class='site-title'>{{{title}}}</div>
							<# if ( pages_count ) { #>
								<div class='pages-count'>{{{pages_count}}} Templates</div>
							<# } #>
						<# } else { #>
							<div class='site-title'>{{{site_title}}}</div>
							<div class='page-title'>{{{title}}}</div>
						<# } #>
					</span>
				</div>
				<# if ( '' === type || 'site' === type ) { #>
					<div class="favorite-action-wrap" data-favorite="{{favorite_class}}" title="{{favorite_title}}">
						<i class="icon-heart"></i>
					</div>
				<# } #>
			</div>
			<# if ( site_type && 'free' !== site_type ) { #>
				<div class="agency-ribbons" title="Agency">Agency</div>
			<# } #>
		</div>
	</div>
	<# } #>

</script>

<?php
/**
 * TMPL - Pro Site Description
 */
?>
<script type="text/template" id="tmpl-astra-sites-pro-site-description">
	<p>
		<?php
			/* translators: %s is pricing page link */
			printf( __( 'This is a premium website demo available only with the Agency Bundles you can purchase it from <a href="%s" target="_blank">here</a>.', 'astra-sites' ), 'https://wpastra.com/pricing/' );
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
 * TMPL - Install & Activate Theme
 */
?>
<script type="text/template" id="tmpl-astra-sites-install-activate-theme">
	<div id="astra-theme-activation-nag">
		<p>
		<?php
		esc_html_e( 'Your starter site has been imported successfully in {{data}}! Now go ahead, customize the text, images, and design to make it yours!', 'astra-sites' );
		?>
		</p>
		<p><?php esc_html_e( 'You can now start making changes according to your requirements.', 'astra-sites' ); ?></p>
		<?php
		$theme_status = Astra_Sites::get_instance()->get_theme_status();
		if ( 'installed-and-active' !== $theme_status ) {
			$link_class = 'astra-sites-theme-' . $theme_status;
			/* translators: %1$s is the plugin name, %2$s is the CSS class name.  */
			printf( __( '<p>Astra Theme needs to be active for you to use currently installed "%1$s" plugin.</p><p><a href="#" class="%2$s" data-theme-slug="astra">Install & Activate Now</a></p>', 'astra-sites' ), ASTRA_SITES_NAME, $link_class );
		}
		?>
	</div>
</script>

<?php
/**
 * TMPL - Activate License
 */
?>
<script type="text/template" id="tmpl-astra-sites-activate-license">
	<p>
		<?php
			/* translators: %s is pricing page link */
			printf( __( 'This is a premium template available with Astra \'Agency\' packages. <a href="%s">Validate Your License</a> Key to import this template.', 'astra-sites' ), admin_url( 'plugins.php?bsf-inline-license-form=astra-pro-sites' ) );
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
				<div class="single-site-pages-header">
					<h2 class="astra-site-title">{{{data['title']}}}</h2>
					<span class="count" style="display: none"></span>
				</div>
				<div class="single-site-preview">
					<img class="theme-screenshot" data-src="" src="{{data['featured-image-url']}}" />
				</div>
			</div>
			<div class="single-site-pages-wrap">
				<div class="astra-pages-title-wrap">
					<span class="astra-pages-title"><?php _e( 'Page Templates', 'astra-sites' ); ?></span>
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

									var thumbnail_image = data.pages[page_id]['thumbnail-image-url'] || '';
									if( '' === thumbnail_image ) {
										thumbnail_image = featured_image;
									}
									#>
									<span class="site-preview" data-title="{{ data.pages[page_id]['title'] }}">
										<div class="theme-screenshot one loading {{ featured_image_class }}" data-src="{{ thumbnail_image }}" data-featured-src="{{ featured_image }}" data-tiny-src="{{ featured_tiny_image }}" ></div>
									</span>
									<div class="theme-id-container">
										<h3 class="theme-name">
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
				<div class="site-action-buttons-wrap">
					<a href="{{data['astra-site-url']}}" class="button button-hero site-preview-button" target="_blank">Preview "{{{data['title']}}}" Site <i class="dashicons dashicons-external"></i></a>
					<div class="site-action-buttons-right">
						<# if( 'free' !== data['astra-sites-type'] && ! astraSitesVars.license_status ) { #>
							<a class="button button-hero button-primary" href="{{astraSitesVars.getProURL}}" target="_blank">{{astraSitesVars.getProText}}<i class="dashicons dashicons-external"></i></a>
							<# if( ! astraSitesVars.isPro ) { #>
								<span class="dashicons dashicons-editor-help astra-sites-get-agency-bundle-button"></span>
							<# } else { #>
								<span class="dashicons dashicons-editor-help astra-sites-activate-license-button"></span>
							<# } #>
						<# } else { #>
							<div class="button button-hero button-primary site-import-layout-button disabled"><?php _e( 'Import Template', 'astra-sites' ); ?></div>
							<div style="margin-left: 5px;" class="button button-hero button-primary site-import-site-button">Import Site</div>
						<# } #>
					</div>
				</div>
			</div>
		</div>
	</div>
</script>

<script type="text/template" id="tmpl-astra-sites-site-import-success">
</script>
<script type="text/template" id="tmpl-astra-sites-page-import-success">
	<p><?php esc_html_e( 'Your page imported successfully! Now go ahead, customize the text, images, and design to make it yours!', 'astra-sites' ); ?></p>
	<p><?php esc_html_e( 'You can now start making changes according to your requirements.', 'astra-sites' ); ?></p>
</script>
<?php
/**
 * TMPL - First Screen
 */
?>
<script type="text/template" id="tmpl-astra-sites-result-preview">

	<div class="overlay"></div>
	<div class="inner">

		<div class="heading">
			<# if( 'astra-sites' === data ) { #>
				<h2><?php _e( 'We are importing site for you!', 'astra-sites' ); ?></h2>
			<# } else { #>
				<h2><?php _e( 'We are importing template for you!', 'astra-sites' ); ?></h2>
			<# } #>
			<span class="dashicons close dashicons-no-alt"></span>
		</div>

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
			</div>
			<div class="ast-importing-wrap">
				<#
				if( 'astra-sites' === data ) {
					var string = 'site';
				} else {
					var string = 'template';
				}
				console.log( data ); #>
				<p>
				<?php
				/* translators: %s is the dynamic string. */
				printf( __( 'Import process can take anywhere between 2 to 10 minutes depending on the size of the %s and speed of the connection.', 'astra-sites' ), '{{string}}' );
				?>
				</p>
				<p>
				<?php
				/* translators: %s is the dynamic string. */
				printf( __( 'Please do not close this browser window until the %s is imported completely.', 'astra-sites' ), '{{string}}' );
				?>
				</p>

				<div class="current-importing-status-wrap">
					<div class="current-importing-status">
						<div class="current-importing-status-title"></div>
						<div class="current-importing-status-description"></div>
					</div>
				</div>
			</div>
		</div>
		<div class="ast-actioms-wrap">
			<a href="#" class="button button-hero button-primary astra-demo-import disabled site-install-site-button"><?php _e( 'Import', 'astra-sites' ); ?></a>
			<a href="#" class="button button-hero button-primary astra-sites-skip-and-import" style="display: none;"><?php _e( 'Skip & Import', 'astra-sites' ); ?></a>
			<div class="button button-hero site-import-cancel"><?php _e( 'Cancel', 'astra-sites' ); ?></div>
		</div>
	</div>
</script>

<?php
wp_print_admin_notice_templates();
