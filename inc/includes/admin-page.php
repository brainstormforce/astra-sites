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

<div class="wrap" id="astra-sites-admin">

	<div id="astra-sites-filters">

		<?php if ( apply_filters( 'astra_sites_show_filters', true ) ) { ?>
			<div class="wp-filter hide-if-no-js">
				<div class="section-left">

					<!-- All Filters -->
					<div class="filter-count">
						<span class="count"></span>
					</div>
					<div class="filters-wrap" style="display: none;">
						<div id="astra-site-page-builder"></div>
					</div>				
					<div class="filters-wrap">
						<div id="astra-site-category"></div>
					</div>

				</div>

				<div class="section-right">

					<div class="search-form">
						<label class="screen-reader-text" for="wp-filter-search-input"><?php _e( 'Search Sites', 'astra-sites' ); ?> </label>
						<input placeholder="<?php _e( 'Search Sites...', 'astra-sites' ); ?>" type="search" aria-describedby="live-search-desc" id="wp-filter-search-input" class="wp-filter-search">
					</div>

				</div>
			</div>
		<?php } ?>

	</div>

	<?php do_action( 'astra_sites_before_site_grid' ); ?>

	<div class="theme-browser rendered">
		<div id="astra-sites" class="themes wp-clearfix"></div>
	</div>

	<div class="spinner-wrap">
		<span class="spinner"></span>
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
		<h3 class="theme-name"><?php esc_html_e( 'Required Plugins Missing', 'astra-sites' ); ?></h3>
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
 * TMPL - Single Demo Preview
 */
?>
<script type="text/template" id="tmpl-astra-site-select-page-builder">
	<div class="select-page-builder">
		<div class="note-wrap">
			<h3>
				<span class="up-arrow dashicons dashicons-editor-break"></span>
				<div class="note"><?php _e( 'Select Your Favorite Page Builder', 'astra-sites' ); ?></div>
			</h3>
		</div>
		<img src="<?php echo esc_url( ASTRA_SITES_URI . 'inc/assets/images/sites-screenshot.jpg' ); ?>" alt="<?php _e( 'Sites List..', 'astra-sites' ); ?>" title="<?php _e( 'Sites List..', 'astra-sites' ); ?>" />
	</div>
</script>

<?php
/**
 * TMPL - Single Demo Preview
 */
?>
<script type="text/template" id="tmpl-astra-site-preview">
	<div class="astra-sites-preview theme-install-overlay wp-full-overlay expanded">
		<div class="wp-full-overlay-sidebar">
			<div class="wp-full-overlay-header"
					data-demo-id="{{{data.id}}}"
					data-demo-type="{{{data.astra_demo_type}}}"
					data-demo-url="{{{data.astra_demo_url}}}"
					data-demo-api="{{{data.demo_api}}}"
					data-demo-name="{{{data.demo_name}}}"
					data-demo-slug="{{{data.slug}}}"
					data-screenshot="{{{data.screenshot}}}"
					data-content="{{{data.content}}}"
					data-required-plugins="{{data.required_plugins}}">
				<input type="hidden" class="astra-site-options" value="{{data.astra_site_options}}" >
				<input type="hidden" class="astra-enabled-extensions" value="{{data.astra_enabled_extensions}}" >
				<button class="close-full-overlay"><span class="screen-reader-text"><?php esc_html_e( 'Close', 'astra-sites' ); ?></span></button>
				<button class="previous-theme"><span class="screen-reader-text"><?php esc_html_e( 'Previous', 'astra-sites' ); ?></span></button>
				<button class="next-theme"><span class="screen-reader-text"><?php esc_html_e( 'Next', 'astra-sites' ); ?></span></button>
				<!-- <a class="button hide-if-no-customize astra-site-import" href="#" data-import="disabled"><?php esc_html_e( 'Import Site', 'astra-sites' ); ?></a> -->
				<a class="button hide-if-no-customize button-primary astra-demo-import" href="#" data-import="disabled"><?php esc_html_e( 'Import Site', 'astra-sites' ); ?></a>

			</div>
			<div class="wp-full-overlay-sidebar-content">
				<div class="install-theme-info">

					<span class="site-type {{{data.astra_demo_type}}}">{{{data.astra_demo_type}}}</span>
					<h3 class="theme-name">{{{data.demo_name}}}</h3>

					<# if ( data.screenshot.length ) { #>
						<div class="theme-screenshot-wrap">
							<img class="theme-screenshot" src="{{{data.screenshot}}}" alt="">
						</div>
					<# } #>

					<div class="theme-details">
						{{{data.content}}}
					</div>
					<a href="#" class="theme-details-read-more"><?php _e( 'Read more', 'astra-sites' ); ?> &hellip;</a>

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
							</ul>
						</div>

						<ul>
							<li class="astra-sites-reset-data">
								<label>
									<input type="checkbox" name="reset" class="checkbox">
									<strong>Delete Previously Imported Site</strong>
									<div class="astra-sites-tooltip-message" id="astra-sites-tooltip-reset-data" style="display: none;"><p><?php _e( 'WARNING: Selecting this option will delete data from your current website. Choose this option only if this is intended.', 'astra-sites' ); ?></p></div>
								</label>
							</li>
						</ul>

						<!-- <p><a href="#" class="astra-sites-advanced-options-button"><?php _e( 'Advanced Options', 'astra-sites' ); ?></a></p> -->

					</div>

					<!-- <div class="astra-sites-advanced-options">
						<h4><?php _e( 'Required Plugins', 'astra-sites' ); ?> </h4>
						<div class="required-plugins"></div>
					</div> -->
				</div>
			</div>

			<div class="wp-full-overlay-footer">
				<div class="footer-import-button-wrap">
					<a class="button button-hero hide-if-no-customize button-primary astra-demo-import" href="#" data-import="disabled">
						<?php esc_html_e( 'Import Site', 'astra-sites' ); ?>
						<span class="percent"></span>
					</a>
					<div class="astra-site-import-process-wrap" style="display: none;">
						<progress class="astra-site-import-process" max="100" value="0"></progress>
					</div>
					<!-- <a class="button button-hero hide-if-no-customize astra-site-import" href="#">
						<?php esc_html_e( 'Import Site', 'astra-sites' ); ?>
					</a> -->
				</div>
				<button type="button" class="collapse-sidebar button" aria-expanded="true"
						aria-label="Collapse Sidebar">
					<span class="collapse-sidebar-arrow"></span>
					<span class="collapse-sidebar-label"><?php esc_html_e( 'Collapse', 'astra-sites' ); ?></span>
				</button>

				<div class="devices-wrapper">
					<div class="devices">
						<button type="button" class="preview-desktop active" aria-pressed="true" data-device="desktop">
							<span class="screen-reader-text"><?php _e( 'Enter desktop preview mode', 'astra-sites' ); ?></span>
						</button>
						<button type="button" class="preview-tablet" aria-pressed="false" data-device="tablet">
							<span class="screen-reader-text"><?php _e( 'Enter tablet preview mode', 'astra-sites' ); ?></span>
						</button>
						<button type="button" class="preview-mobile" aria-pressed="false" data-device="mobile">
							<span class="screen-reader-text"><?php _e( 'Enter mobile preview mode', 'astra-sites' ); ?></span>
						</button>
					</div>
				</div>

			</div>
		</div>
		<div class="wp-full-overlay-main">
			<iframe src="{{{data.astra_demo_url}}}" title="<?php esc_attr_e( 'Preview', 'astra-sites' ); ?>"></iframe>
			<div class="astra-sites-result-preview" style="display: none;">
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
	</div>
</script>

<?php
/**
 * TMPL - No more demos
 */
?>
<script type="text/template" id="tmpl-astra-site-api-request-failed">
	<div class="no-themes">
		<?php

		/* translators: %1$s & %2$s are a Demo API URL */
		printf( __( '<p> It seems the demo data server, <i><a href="%1$s">%2$s</a></i> is unreachable from your site.</p>', 'astra-sites' ), esc_url( Astra_Sites::$api_url ), esc_url( Astra_Sites::$api_url ) );

		_e( '<p class="left-margin"> 1. Sometimes, simple page reload fixes any temporary issues. No kidding!</p>', 'astra-sites' );

		_e( '<p class="left-margin"> 2. If that does not work, you will need to talk to your server administrator and check if demo server is being blocked by the firewall!</p>', 'astra-sites' );

		/* translators: %1$s is a support link */
		printf( __( '<p>If that does not help, please open up a <a href="%1$s" target="_blank">Support Ticket</a> and we will be glad take a closer look for you.</p>', 'astra-sites' ), esc_url( 'https://wpastra.com/support/?utm_source=demo-import-panel&utm_campaign=astra-sites&utm_medium=api-request-failed' ) );
		?>
	</div>
</script>

<?php
/**
 * TMPL - Site Down
 */
?>
<script type="text/template" id="tmpl-astra-site-down">
	<div class="postbox astra-site-down">
		<h2><?php _e( 'Under Maintenance..', 'astra-sites' ); ?></h2>
		<p><?php _e( 'If you are seeing this message, most likely our servers are under routine maintenance and we will be back shortly. ', 'astra-sites' ); ?></p>
		<p><?php _e( 'In rare case, it is possible your website is having trouble connecting with ours. If you need help, please feel free to get in touch with us from our website.', 'astra-sites' ); ?></p>
	</div>
</script>

<?php
/**
 * TMPL - Filters
 */
?>
<script type="text/template" id="tmpl-astra-site-filters">

	<# if ( data ) { #>

		<ul class="{{ data.args.wrapper_class }} {{ data.args.class }}">

			<# if ( data.args.show_all ) { #>
				<li>
					<a href="#" data-group="all"> All </a>
				</li>
			<# } #>

			<# for ( key in data.items ) { #>
				<# if ( data.items[ key ].count ) { #>
					<li>
						<a href="#" data-group='{{ data.items[ key ].id }}' class="{{ data.items[ key ].name }}">
							{{ data.items[ key ].name }}
						</a>
					</li>
				<# } #>
			<# } #>

		</ul>
	<# } #>
</script>

<?php
/**
 * TMPL - List
 */
?>
<script type="text/template" id="tmpl-astra-sites-list">

	<# if ( data.items.length ) { #>
		<# for ( key in data.items ) { #>

			<div class="theme astra-theme site-single {{ data.items[ key ].status }}" tabindex="0" aria-describedby="astra-theme-action astra-theme-name"
				data-demo-id="{{{ data.items[ key ].id }}}"
				data-demo-type="{{{ data.items[ key ]['astra-site-type'] }}}"
				data-demo-url="{{{ data.items[ key ]['astra-site-url'] }}}"
				data-demo-api="{{{ data.items[ key ]['_links']['self'][0]['href'] }}}"
				data-demo-name="{{{  data.items[ key ].title.rendered }}}"
				data-demo-slug="{{{  data.items[ key ].slug }}}"
				data-screenshot="{{{ data.items[ key ]['featured-image-url'] }}}"
				data-content="{{{ data.items[ key ].content.rendered }}}"
				data-required-plugins="{{ JSON.stringify( data.items[ key ]['required-plugins'] ) }}"
				data-groups=["{{ data.items[ key ].tags }}"]>
				<input type="hidden" class="astra-site-options" value="{{ JSON.stringify(data.items[ key ]['astra-site-options-data'] ) }}" />
				<input type="hidden" class="astra-enabled-extensions" value="{{ JSON.stringify(data.items[ key ]['astra-enabled-extensions'] ) }}" />

				<div class="inner">
					<span class="site-preview" data-href="{{ data.items[ key ]['astra-site-url'] }}?TB_iframe=true&width=600&height=550" data-title="{{ data.items[ key ].title.rendered }}">
						<div class="theme-screenshot" style="background-image: url('{{ data.items[ key ]['featured-image-url'] }}');"></div>
					</span>
					<# if ( data.items[ key ]['astra-site-type'] ) { #>
						<# var type = ( data.items[ key ]['astra-site-type'] !== 'premium' ) ? ( data.items[ key ]['astra-site-type'] ) : 'agency'; #>
						<span class="site-type {{data.items[ key ]['astra-site-type']}}">{{ type }}</span>
					<# } #>
					<# if ( data.items[ key ].status ) { #>
						<span class="status {{data.items[ key ].status}}">{{data.items[ key ].status}}</span>
					<# } #>
					<div class="theme-id-container">
						<h3 class="theme-name" id="astra-theme-name"> {{{ data.items[ key ].title.rendered }}} </h3>
						<div class="theme-actions">
							<button class="button-primary button preview install-theme-preview"><?php esc_html_e( 'Preview', 'astra-sites' ); ?></button>
						</div>
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
/**
 * TMPL - List
 */
?>
<script type="text/template" id="tmpl-astra-sites-suggestions">
	<div class="theme astra-theme site-single astra-sites-suggestions">
		<div class="inner">
			<p>
			<?php
			/* translators: %1$s External Link */
			printf( __( 'Don\'t see a site that you would like to import?<br><a target="_blank" href="%1$s">Please suggest us!</a>', 'astra-sites' ), esc_url( 'https://wpastra.com/sites-suggestions/?utm_source=demo-import-panel&utm_campaign=astra-sites&utm_medium=suggestions' ) );
			?>
			</p>
		</div>
	</div>
</script>
<?php
wp_print_admin_notice_templates();
