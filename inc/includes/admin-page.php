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

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$subscription_status = Astra_Sites::get_instance()->should_display_subscription_form();
$subscription_class  = true === $subscription_status ? 'subscription-enabled' : '';

$site_import_options = apply_filters(
	'astra_sites_site_import_options',
	array(
		'activate-theme' => true,
		'reset'          => false,
		'customizer'     => true,
		'widgets'        => true,
		'plugins'        => true,
		'xml'            => true,
	)
);

?>

<div class="wrap" id="astra-sites-admin" data-slug="<?php echo esc_html( $global_cpt_meta['cpt_slug'] ); ?>">

	<?php
	if ( ! empty( $_GET['debug'] ) && 'yes' === $_GET['debug'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="astra-sites-log">
			<?php Astra_Sites_Importer_Log::get_instance()->display_data(); ?>
		</div>

	<?php } else { ?>

		<?php do_action( 'astra_sites_before_site_grid' ); ?>

		<div class="theme-browser rendered">
			<div id="astra-sites" class="themes wp-clearfix"></div>
			<div id="site-pages" class="themes wp-clearfix"></div>
			<div class="astra-sites-result-preview" style="display: none;"></div>

			<div class="astra-sites-popup" style="display: none;">
				<div class="overlay"></div>
				<div class="inner">
					<div class="heading">
						<h3><?php esc_html_e( 'Heading', 'astra-sites' ); ?></h3>
						<span class="dashicons close dashicons-no-alt"></span>
					</div>
					<div class="astra-sites-import-content"></div>
					<div class="ast-actioms-wrap"></div>
				</div>
			</div>
		</div>

		<?php do_action( 'astra_sites_after_site_grid' ); ?>

	<?php } ?>
</div>

<script type="text/template" id="tmpl-astra-sites-compatibility-messages">

	<div class="skip-and-import">
		<div class="heading">
			<h3><?php esc_html_e( 'We\'re Almost There!', 'astra-sites' ); ?></h3>
			<span class="dashicons close dashicons-no-alt"></span>
		</div>
		<div class="astra-sites-import-content">

			<p><?php esc_html_e( 'You\'re close to importing the template. To complete the process, please clear the following conditions.', 'astra-sites' ); ?></p>

			<ul class="astra-site-contents">

				<# for ( code in data ) { #>
					<# if( Object.keys( data[ code ] ).length ) { #>

						<# for ( id in data[ code ] ) { #>
							<li>
								{{{ data[ code ][id].title }}}

								<# if ( data[ code ][id].tooltip ) { #>
									<span class="astra-sites-tooltip-icon" data-tip-id="astra-sites-skip-and-import-notice-{{id}}">
										<span class="dashicons dashicons-editor-help"></span>
									</span>
									<div class="astra-sites-tooltip-message" id="astra-sites-skip-and-import-notice-{{id}}" style="display: none;">
										{{{data[ code ][id].tooltip}}}
									</div>
								<# } #>
							</li>
						<# } #>

					<# } #>
				<# } #>

			</ul>

		</div>
		<div class="ast-actioms-wrap">
			<# if( Object.keys( data['errors'] ).length ) { #>
				<a href="#" class="button button-hero button-primary astra-demo-import disabled site-install-site-button"><?php esc_html_e( 'Skip & Import', 'astra-sites' ); ?></a>
				<div class="button button-hero site-import-cancel"><?php esc_html_e( 'Cancel', 'astra-sites' ); ?></div>
			<# } else {
				var plugin_update = data['warnings']['update-available'] || 0;
				if( plugin_update ) { #>
					<a href="<?php echo esc_url( network_admin_url( 'update-core.php' ) ); ?>" class="button button-hero button-primary" target="_blank"><?php esc_html_e( 'Update', 'astra-sites' ); ?></a>
					<a href="#" class="button button-hero button-primary astra-sites-skip-and-import-step"><?php esc_html_e( 'Skip & Import', 'astra-sites' ); ?></a>
				<# } else { #>
					<a href="#" class="button button-hero button-primary astra-sites-skip-and-import-step"><?php esc_html_e( 'Skip & Import', 'astra-sites' ); ?></a>
					<div class="button button-hero site-import-cancel"><?php esc_html_e( 'Cancel', 'astra-sites' ); ?></div>
				<# } #>
			<# } #>
		</div>
	</div>

</script>

<script type="text/template" id="tmpl-astra-sites-no-sites">
	<div class="astra-sites-no-sites">
		<div class="inner">
			<h3><?php esc_html_e( 'Sorry No Results Found.', 'astra-sites' ); ?></h3>
			<div class="content">
				<div class="empty-item">
					<img class="empty-collection-part" src="<?php echo esc_url( ASTRA_SITES_URI . 'inc/assets/images/empty-collection.svg' ); ?>" alt="empty-collection">
				</div>
				<div class="description">
					<p>
					<?php
					/* translators: %1$s External Link */
					printf( __( 'Don\'t see a template you would like to import?<br><a target="_blank" href="%1$s">Make a Template Suggestion!</a>', 'astra-sites' ), esc_url( astra_sites_get_suggestion_link() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
					</p>
					<div class="back-to-layout-button"><span class="button astra-sites-back"><?php esc_html_e( 'Back to Templates', 'astra-sites' ); ?></span></div>
				</div>
			</div>
		</div>
	</div>
</script>

<script type="text/template" id="tmpl-astra-sites-no-favorites">
	<div class="astra-sites-no-favorites">
		<div class="inner">
			<h3><?php esc_html_e( 'Favorite Template List Is Empty.', 'astra-sites' ); ?></h3>
			<div class="content">
				<div class="empty-item">
					<img class="empty-collection-part" src="<?php echo esc_url( ASTRA_SITES_URI . 'inc/assets/images/empty-collection.svg' ); ?>" alt="empty-collection">
				</div>
				<div class="description">
					<p>
					<?php
					/* translators: %1$s External Link */
					esc_html_e( 'You\'ll notice a heart-shaped symbol on every template card. Simply tap this icon to mark the template as Favorite.', 'astra-sites' );
					?>
					</p>
					<img src="<?php echo esc_url( ASTRA_SITES_URI . 'inc/assets/images/arrow-blue.svg' ); ?>" class="arrow-img">
					<div class="back-to-layout-button"><span class="button astra-sites-back"><?php esc_html_e( 'Back to Templates', 'astra-sites' ); ?></span></div>
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
		var wrapper_class       = data[site_id]['class'] || '';
		var page_site_id        = data[site_id]['site_id'] || '';
		var favorite_status     = false;
		var favorite_class      = '';
		var favorite_title      = '<?php esc_html_e( 'Make as Favorite', 'astra-sites' ); ?>';
		var featured_image_url = data[site_id]['featured-image-url'];
		var thumbnail_image_url = data[site_id]['thumbnail-image-url'] || featured_image_url;

		var site_type = data[site_id]['astra-sites-type'] || '';
		var page_id = '';
		if ( 'site' === type ) {
			if( Object.values( astraSitesVars.favorite_data ).indexOf( String(site_id) ) >= 0 ) {
				favorite_class = 'is-favorite';
				favorite_status = true;
				favorite_title = '<?php esc_html_e( 'Make as Unfavorite', 'astra-sites' ); ?>';
			}
		} else {
			thumbnail_image_url = featured_image_url;
			current_site_id = page_site_id;
			page_id = site_id;
		}

		var title = data[site_id]['title'] || '';
		var pages_count = parseInt( data[site_id]['pages-count'] ) || 0;
		var pages_count_class = '';
		var pages_count_string = ( pages_count !== 1 ) ? pages_count + ' Templates' : pages_count + ' Template';
		if( 'site' === type ) {
			if( pages_count ) {
				pages_count_class = 'has-pages';
			} else {
				pages_count_class = 'no-pages';
			}
		}
		var site_title = data[site_id]['site-title'] || '';

	#>
	<div class="theme astra-theme site-single {{favorite_class}} {{pages_count_class}} astra-sites-previewing-{{type}} {{wrapper_class}}" data-site-id="{{current_site_id}}" data-page-id="{{page_id}}">
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
								<div class='pages-count'>{{{pages_count_string}}}</div>
							<# } #>
						<# } else { #>
							<div class='site-title'>{{{site_title}}}</div>
							<div class='page-title'>{{{title}}}</div>
						<# } #>
					</span>
				</div>
				<# if ( '' === type || 'site' === type ) { #>
					<div class="favorite-action-wrap" data-favorite="{{favorite_class}}" title="{{favorite_title}}">
						<i class="ast-icon-heart"></i>
					</div>
				<# } #>
			</div>
			<# if ( site_type && 'free' !== site_type ) { #>
				<?php /* translators: %s are white label strings. */ ?>
				<div class="agency-ribbons" title="<?php printf( esc_attr__( 'This premium template is accessible with %1$s "Agency" Package.', 'astra-sites' ), Astra_Sites_White_Label::get_instance()->get_white_label_name() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"><?php esc_html_e( 'Agency', 'astra-sites' ); ?></div>
			<# } #>
		</div>
	</div>
	<# } #>

</script>

<?php
/**
 * TMPL - Show Page Builder Sites
 */
?>
<script type="text/template" id="tmpl-astra-sites-page-builder-sites-search">
	<# var pages_list = []; #>
	<# var sites_list = []; #>
	<# var pages_list_arr = []; #>
	<# var sites_list_arr = []; #>
	<# for ( site_id in data ) {
		var type = data[site_id]['type'] || 'site';
		if ( 'site' === type ) {
			sites_list_arr.push( data[site_id] );
			sites_list[site_id] = data[site_id];
		} else {
			pages_list_arr.push( data[site_id] );
			pages_list[site_id] = data[site_id]
		}
	} #>
	<# if ( sites_list_arr.length > 0 ) { #>
		<h3 class="ast-sites__search-title"><?php esc_html_e( 'Site Templates', 'astra-sites' ); ?></h3>
		<div class="ast-sites__search-wrap">
		<# for ( site_id in sites_list ) { #>
		<#
			var current_site_id     = site_id;
			var type                = sites_list[site_id]['type'] || 'site';
			var page_site_id        = sites_list[site_id]['site_id'] || '';
			var favorite_status     = false;
			var favorite_class      = '';
			var favorite_title      = '<?php esc_html_e( 'Make as Favorite', 'astra-sites' ); ?>';
			var featured_image_url = sites_list[site_id]['featured-image-url'];
			var thumbnail_image_url = sites_list[site_id]['thumbnail-image-url'] || featured_image_url;

			var site_type = sites_list[site_id]['astra-sites-type'] || '';
			var page_id = '';
			if( Object.values( astraSitesVars.favorite_data ).indexOf( String(site_id) ) >= 0 ) {
				favorite_class = 'is-favorite';
				favorite_status = true;
				favorite_title = '<?php esc_html_e( 'Make as Unfavorite', 'astra-sites' ); ?>';
			}

			var title = sites_list[site_id]['title'] || '';
			var pages_count = parseInt( sites_list[site_id]['pages-count'] ) || 0;
			var pages_count_string = ( pages_count !== 1 ) ? pages_count + ' Templates' : pages_count + ' Template';
			var pages_count_class = '';
			if( pages_count ) {
				pages_count_class = 'has-pages';
			} else {
				pages_count_class = 'no-pages';
			}
			var site_title = sites_list[site_id]['site-title'] || '';

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
										<div class='pages-count'>{{{pages_count_string}}}</div>
									<# } #>
								<# } else { #>
									<div class='site-title'>{{{site_title}}}</div>
									<div class='page-title'>{{{title}}}</div>
								<# } #>
							</span>
						</div>
						<# if ( '' === type || 'site' === type ) { #>
							<div class="favorite-action-wrap" data-favorite="{{favorite_class}}" title="{{favorite_title}}">
								<i class="ast-icon-heart"></i>
							</div>
						<# } #>
					</div>
					<# if ( site_type && 'free' !== site_type ) { #>
						<?php /* translators: %s are white label strings. */ ?>
						<div class="agency-ribbons" title="<?php printf( esc_attr__( 'This premium template is accessible with %1$s "Agency" Package.', 'astra-sites' ), Astra_Sites_White_Label::get_instance()->get_white_label_name() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"><?php esc_html_e( 'Agency', 'astra-sites' ); ?></div>
					<# } #>
				</div>
			</div>
		<# } #>
		</div>
	<# } #>
	<# if ( pages_list_arr.length > 0 ) { #>

		<h3 class="ast-sites__search-title"><?php esc_html_e( 'Page Templates', 'astra-sites' ); ?></h3>
		<div class="ast-sites__search-wrap">
		<# for ( site_id in pages_list ) { #>
		<#
			var current_site_id     = site_id;
			var type                = pages_list[site_id]['type'] || 'site';
			var page_site_id        = pages_list[site_id]['site_id'] || '';
			var favorite_status     = false;
			var favorite_class      = '';
			var favorite_title      = '<?php esc_html_e( 'Make as Favorite', 'astra-sites' ); ?>';
			var featured_image_url = pages_list[site_id]['featured-image-url'];
			var thumbnail_image_url = pages_list[site_id]['thumbnail-image-url'] || featured_image_url;

			var site_type = pages_list[site_id]['astra-sites-type'] || '';
			var page_id = '';
			thumbnail_image_url = featured_image_url;
			current_site_id = page_site_id;
			page_id = site_id;

			var title = pages_list[site_id]['title'] || '';
			var pages_count = pages_list[site_id]['pages-count'] || 0;
			var pages_count_class = '';
			if( 'site' === type ) {
				if( pages_count ) {
					pages_count_class = 'has-pages';
				} else {
					pages_count_class = 'no-pages';
				}
			}
			var site_title = pages_list[site_id]['site-title'] || '';

		#>
			<div class="theme astra-theme site-single {{favorite_class}} {{pages_count_class}} astra-sites-previewing-{{type}}" data-site-id="{{current_site_id}}" data-page-id="{{page_id}}">
				<div class="inner">
					<span class="site-preview" data-title="{{{title}}}">
						<div class="theme-screenshot one loading" data-src="{{thumbnail_image_url}}" data-featured-src="{{featured_image_url}}"></div>
					</span>
					<div class="theme-id-container">
						<div class="theme-name">
							<span class="title">
								<div class='site-title'>{{{site_title}}}</div>
								<div class='page-title'>{{{title}}}</div>
							</span>
						</div>
						<# if ( '' === type || 'site' === type ) { #>
							<div class="favorite-action-wrap" data-favorite="{{favorite_class}}" title="{{favorite_title}}">
								<i class="ast-icon-heart"></i>
							</div>
						<# } #>
					</div>
					<# if ( site_type && 'free' !== site_type ) { #>
						<?php /* translators: %s are white label strings. */ ?>
						<div class="agency-ribbons" title="<?php printf( esc_attr__( 'This premium template is accessible with %1$s "Agency" Package.', 'astra-sites' ), Astra_Sites_White_Label::get_instance()->get_white_label_name() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"><?php esc_html_e( 'Agency', 'astra-sites' ); ?></div>
					<# } #>
				</div>
			</div>
		<# } #>
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
			printf( __( 'This is a premium template available with \'Agency\' package. you can purchase it from <a href="%s" target="_blank">here</a>.', 'astra-sites' ), 'https://wpastra.com/pricing/' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</p>
	<p>
		<?php
			/* translators: %s is article link */
			printf( __( 'If you already own an Agency pack. Read an article to know how you can <a href="%s" target="_blank">import a premium website demo</a>.', 'astra-sites' ), esc_url( 'https://wpastra.com/docs/starter-templates-complete-site/' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</p>
</script>

<?php
/**
 * TMPL - Activate License
 */
?>
<script type="text/template" id="tmpl-astra-sites-skip-templates">
	<p><?php esc_html_e( 'The page templates which contain the dynamic widgets/modules are not available for single template import. With the "Import Site" option, you can get those pages.', 'astra-sites' ); ?></p>
	<p><?php esc_html_e( 'You can have the complete site preview from bottom left button.', 'astra-sites' ); ?></p>
</script>

<?php
/**
 * TMPL - Activate License
 */
?>
<script type="text/template" id="tmpl-astra-sites-activate-license">
	<?php
		do_action( 'astra_sites_activate_license_popup' );
	?>
</script>

<?php
/**
 * TMPL - Invalid Mini Agency License
 */
?>
<script type="text/template" id="tmpl-astra-sites-invalid-mini-agency-license">
	<p>
		<?php
			$page_builder = get_option( 'astra-sites-license-page-builder', '' );
		if ( 'elementor' === $page_builder ) {
			$current_page_builder = 'Elementor';
			$upgrade_page_builder = 'Beaver Builder';
		} else {
			$current_page_builder = 'Beaver Builder';
			$upgrade_page_builder = 'Elementor';
		}

		/* translators: %s is pricing page link */
		printf( __( '<p>Seems like you have purchased the %4$s \'Mini Agency\' package with a choice of \'%1$s\' page builder addon.</p><p>While this template is built with \'%2$s\' page builder addon.</p><p>Read article <a href="%3$s" target="_blank">here</a> to see how you can proceed.</p>', 'astra-sites' ), esc_html( $current_page_builder ), esc_html( $upgrade_page_builder ), esc_url( Astra_Sites_White_Label::get_instance()->get_white_label_link( 'https://wpastra.com/docs/not-valid-license/' ) ), Astra_Sites_White_Label::get_instance()->get_white_label_name() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</p>
</script>

<?php
/**
 * TMPL - Pro Site Description for Inactive license
 */
?>
<script type="text/template" id="tmpl-astra-sites-pro-inactive-site-description">
	<p><?php esc_html_e( 'You are just 2 minutes away from importing this demo!', 'astra-sites' ); ?></p>
	<p><?php esc_html_e( 'This is a premium website demo template. Please activate your license to access it.', 'astra-sites' ); ?></p>
	<p>
		<?php
			/* translators: %s is article link */
			printf( __( 'Learn how you can <a href="%1$s" target="_blank">activate the license</a> of the %2$s plugin.', 'astra-sites' ), esc_url( 'https://wpastra.com/docs/activate-license-for-astra-premium-sites-plugin/' ), Astra_Sites_White_Label::get_instance()->get_white_label_name() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</p>
</script>

<?php
/**
 * TMPL - Third Party Required Plugins
 */
?>
<script type="text/template" id="tmpl-astra-sites-third-party-required-plugins">
	<div class="skip-and-import">
		<div class="heading">
			<h3><?php esc_html_e( 'Required Plugins Missing', 'astra-sites' ); ?></h3>
			<span class="dashicons close dashicons-no-alt"></span>
		</div>
		<div class="astra-sites-import-content">
			<p><?php esc_html_e( 'This starter template requires premium plugins. As these are third party premium plugins, you\'ll need to purchase, install and activate them first.', 'astra-sites' ); ?></p>
			<ul class="astra-sites-third-party-required-plugins">
				<# for ( key in data ) { #>
					<li class="plugin-card plugin-card-{{data[ key ].slug}}'" data-slug="{{data[ key ].slug }}" data-init="{{data[ key ].init}}" data-name="{{data[ key ].name}}"><a href="{{data[ key ].link}}" target="_blank">{{data[ key ].name}}</a></li>
				<# } #>
			</ul>
		</div>
		<div class="ast-actioms-wrap">
			<a href="#" class="button button-hero button-primary astra-sites-skip-and-import-step"><?php esc_html_e( 'Skip & Import', 'astra-sites' ); ?></a>
			<div class="button button-hero site-import-cancel"><?php esc_html_e( 'Cancel', 'astra-sites' ); ?></div>
		</div>
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
					<h3 class="astra-site-title">{{{data['title']}}}</h3>
					<span class="count" style="display: none"></span>
				</div>
				<div class="single-site-preview">
					<img class="theme-screenshot" data-src="" src="{{data['featured-image-url']}}" />
				</div>
			</div>
			<div class="single-site-pages-wrap">
				<div class="astra-pages-title-wrap">
					<span class="astra-pages-title"><?php esc_html_e( 'Page Templates', 'astra-sites' ); ?></span>
				</div>
				<div class="single-site-pages">
					<div id="single-pages">
						<# for ( page_id in data.pages ) {
							var dynamic_page = data.pages[page_id]['dynamic-page'] || 'no'; #>
							<div class="theme astra-theme site-single" data-page-id="{{page_id}}" data-dynamic-page="{{dynamic_page}}" >
								<div class="inner">
									<#
									var featured_image_class = '';
									var featured_image = data.pages[page_id]['featured-image-url'] || '';
									if( '' === featured_image ) {
										featured_image = '<?php echo esc_url( ASTRA_SITES_URI . 'inc/assets/images/placeholder.png' ); ?>';
										featured_image_class = ' no-featured-image ';
									}

									var thumbnail_image = data.pages[page_id]['thumbnail-image-url'] || '';
									if( '' === thumbnail_image ) {
										thumbnail_image = featured_image;
									}
									#>
									<span class="site-preview" data-title="{{ data.pages[page_id]['title'] }}">
										<div class="theme-screenshot one loading {{ featured_image_class }}" data-src="{{ thumbnail_image }}" data-featured-src="{{ featured_image }}"></div>
									</span>
									<div class="theme-id-container">
										<h3 class="theme-name">
											{{{ data.pages[page_id]['title'] }}}
										</h3>
									</div>
								</div>
							</div>
						<# } #>
					</div>
				</div>
			</div>
			<div class="single-site-footer">
				<div class="site-action-buttons-wrap">
					<?php $white_label_class = ( Astra_Sites_White_Label::get_instance()->get_white_label_name() !== ASTRA_SITES_NAME ) ? 'ast-white-label-flag' : ''; ?>
					<a href="{{data['astra-site-url']}}/" class="button button-hero site-preview-button <?php echo esc_html( $white_label_class ); ?>" target="_blank">Preview "{{{data['title']}}}" Site <i class="dashicons dashicons-external"></i></a>
					<div class="site-action-buttons-right">
						<# if( 'free' !== data['astra-sites-type'] && '' !== astraSitesVars.license_page_builder && data['astra-site-page-builder'] !== astraSitesVars.license_page_builder && ( 'brizy' !== data['astra-site-page-builder'] && 'gutenberg' !== data['astra-site-page-builder']  ) ) { #>
							<a class="button button-hero button-primary disabled astra-sites-invalid-mini-agency-license-button"><?php esc_html_e( 'Not Valid License', 'astra-sites' ); ?></a>
							<span class="dashicons dashicons-editor-help astra-sites-invalid-mini-agency-license-button"></span>
						<# } else if( 'free' !== data['astra-sites-type'] && ! astraSitesVars.license_status ) { #>
							<# if( ! astraSitesVars.isPro ) { #>
								<a class="button button-hero button-primary " href="{{astraSitesVars.getProURL}}" target="_blank">{{astraSitesVars.getProText}}<i class="dashicons dashicons-external"></i></a>
								<span class="dashicons dashicons-editor-help astra-sites-get-agency-bundle-button"></span>
							<# } else { #>
								<span class="button button-hero button-primary astra-sites-activate-license-button">{{astraSitesVars.getProText}}</span>
							<# } #>
						<# } else { #>
							<div class="button button-hero button-primary site-import-site-button"><?php esc_html_e( 'Import Complete Site', 'astra-sites' ); ?></div>
							<div style="margin-left: 5px;" class="button button-hero button-primary site-import-layout-button disabled"><?php esc_html_e( 'Import Template', 'astra-sites' ); ?></div>
						<# } #>
					</div>
				</div>
			</div>
		</div>
	</div>
</script>

<script type="text/template" id="tmpl-astra-sites-site-import-success">
	<div class="heading">
		<h3><?php esc_html_e( 'Imported Successfully!', 'astra-sites' ); ?></h3>
		<span class="dashicons close dashicons-no-alt"></span>
	</div>
	<div class="astra-sites-import-content">
		<p><b><?php esc_html_e( 'Hurray! The Website Imported Successfully! 🎉', 'astra-sites' ); ?></b></p>
		<p>
			<?php esc_html_e( 'Go ahead, customize the text, images and design to make it yours!', 'astra-sites' ); ?>&nbsp;
			<# if ( '46177' == AstraSitesAdmin.templateData.id ) { #>
			<?php
				$kit_doc_url = 'https://wpastra.com/docs/mountain-template-elementor-theme-style/';
				/* translators: %1$s External Link */
				printf( __( '%1$sRead more%2$s about customizing this Elementor Style Kit site.', 'astra-sites' ), '<a href="' . esc_url( $kit_doc_url ) . '" target="_blank">', '</a>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
			<# } #>
		</p>
		<p><?php esc_html_e( 'Have fun!', 'astra-sites' ); ?></p>
		<p><?php esc_html_e( 'PS: We try our best to use images that are free from legal perspectives. However, we do not take any responsibility. We strongly advise website owners to replace the images and any copyrighted media before publishing them online.', 'astra-sites' ); ?></p>
	</div>
	<div class="ast-actioms-wrap">
		<a class="button button-primary button-hero" href="<?php echo esc_url( site_url() ); ?>" target="_blank"><?php esc_html_e( 'View Site', 'astra-sites' ); ?> <i class="dashicons dashicons-external"></i></a>
	</div>
</script>

<script type="text/template" id="tmpl-astra-sites-page-import-success">
	<div class="heading">
		<h3><?php esc_html_e( 'Imported Successfully!', 'astra-sites' ); ?></h3>
		<span class="dashicons close dashicons-no-alt"></span>
	</div>
	<div class="astra-sites-import-content">
		<p><b><?php esc_html_e( 'Hurray! The Template Imported Successfully! 🎉', 'astra-sites' ); ?></b></p>
		<p><?php esc_html_e( 'Go ahead, customize the text, images and design to make it yours!', 'astra-sites' ); ?></p>
		<p><?php esc_html_e( 'Have fun!', 'astra-sites' ); ?></p>
		<p><?php esc_html_e( 'PS: We try our best to use images that are free from legal perspectives. However, we do not take any responsibility. We strongly advise website owners to replace the images and any copyrighted media before publishing them online.', 'astra-sites' ); ?></p>
	</div>
	<div class="ast-actioms-wrap">
		<a class="button button-primary button-hero" href="{{data['link']}}" target="_blank"><?php esc_html_e( 'View Template', 'astra-sites' ); ?> <i class="dashicons dashicons-external"></i></a>
	</div>
</script>

<?php
/**
 * TMPL - Import Process Interrupted
 */
?>
<script type="text/template" id="tmpl-astra-sites-request-failed">
	<p><?php esc_html_e( 'Your website is facing a temporary issue connecting to the template server.', 'astra-sites' ); ?></p>
	<p>
		<?php
		/* translators: %s doc link. */
		printf( __( 'Read an article <a href="%s" target="_blank">here</a> to resolve the issue.', 'astra-sites' ), 'https://wpastra.com/docs/import-process-interrupted/' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</p>
</script>

<?php
/**
 * TMPL - Dynamic Page
 */
?>
<script type="text/template" id="tmpl-astra-sites-dynamic-page">
	<div class="skip-and-import">
		<div class="heading">
			<h3><?php esc_html_e( 'Heads Up!', 'astra-sites' ); ?></h3>
			<span class="dashicons close dashicons-no-alt"></span>
		</div>
		<div class="astra-sites-import-content">
			<p><?php esc_html_e( 'The page template you are about to import contains a dynamic widget/module. Please note this dynamic data will not be available with the imported page.', 'astra-sites' ); ?></p>
			<p><?php esc_html_e( 'You will need to add it manually on the page.', 'astra-sites' ); ?></p>
			<p><?php esc_html_e( 'This dynamic content will be available when you import the entire site.', 'astra-sites' ); ?></p>
		</div>
		<div class="ast-actioms-wrap">
			<a href="#" class="button button-hero button-primary astra-sites-skip-and-import-step"><?php esc_html_e( 'Skip & Import', 'astra-sites' ); ?></a>
			<div class="button button-hero site-import-cancel"><?php esc_html_e( 'Cancel', 'astra-sites' ); ?></div>
		</div>
	</div>
</script>

<script type="text/template" id="tmpl-astra-sites-subscription-form-one">
<div class="subscription-fields">
	<div class="subscription-field-wrap">
		<select class="subscription-input subscription-input-wp-user-type" name="wp_user_type">
			<option value=""></option>
			<option value="1"><?php esc_html_e( 'Beginner', 'astra-sites' ); ?></option>
			<option value="2"><?php esc_html_e( 'Intermediate', 'astra-sites' ); ?></option>
			<option value="3"><?php esc_html_e( 'Expert', 'astra-sites' ); ?></option>
		</select>
		<small class="subscription-desc"><?php esc_html_e( 'Field is required', 'astra-sites' ); ?></small>
		<label class="subscription-label"><?php esc_html_e( 'I\'m a WordPress:', 'astra-sites' ); ?></label>
	</div>
	<div class="subscription-field-wrap">
		<select class="subscription-input subscription-input-build-website-for" name="build_website_for">
			<option value=""></option>
			<option value="1"><?php esc_html_e( 'Myself/My company', 'astra-sites' ); ?></option>
			<option value="2"><?php esc_html_e( 'My client', 'astra-sites' ); ?></option>
		</select>
		<small class="subscription-desc"><?php esc_html_e( 'Field is required', 'astra-sites' ); ?></small>
		<label class="subscription-label"><?php esc_html_e( 'I\'m building website for:', 'astra-sites' ); ?></label>
	</div>
</div>
</script>

<script type="text/template" id="tmpl-astra-sites-subscription-form-two">
	<div class="subscription-fields">
		<div class="subscription-field-wrap">
			<input class="subscription-input subscription-input-name" type="text" name="first_name" />
			<small class="subscription-desc"><?php esc_html_e( 'First name is required', 'astra-sites' ); ?></small>
			<label class="subscription-label"><?php esc_html_e( 'Your First Name', 'astra-sites' ); ?></label>
		</div>
		<div class="subscription-field-wrap">
			<input class="subscription-input subscription-input-email" type="email" name="email" />
			<small class="subscription-desc"><?php esc_html_e( 'Email address is required', 'astra-sites' ); ?></small>
			<label class="subscription-label"><?php esc_html_e( 'Your Work Email', 'astra-sites' ); ?></label>
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
	<div class="inner <?php echo esc_attr( $subscription_class ); ?>">

		<div class="subscription-popup">
			<div class="heading">
				<h3><?php esc_html_e( 'One Last Step..', 'astra-sites' ); ?></h3>
				<span class="dashicons close dashicons-no-alt"></span>
			</div>
			<div class="astra-sites-import-content">
				<p><?php esc_html_e( 'To get access to exclusive starter templates, themes and updates, enter your details below:', 'astra-sites' ); ?></p>
				<div id="astra-sites-subscription-form-two" class="subscription-form astra-sites-subscription-form-two"></div>
				<div class="subscription-actions">
					<button class="button button-hero button-primary button-subscription-submit">
						<span class="button-title"><?php esc_html_e( 'Submit and Start Importing', 'astra-sites' ); ?></span>
						<span class="dashicons dashicons-update"></span>
					</button>
				</div>
				<div class="subscription-footer">
					<?php /* translators: %1$s and %3$s are opening anchor tags, and %2$s and %4$s is closing anchor tags. */ ?>
					<p><?php printf( __( 'By submitting, you agree to our %1$sTerms%2$s and %3$sPrivacy Policy%4$s.', 'astra-sites' ), '<a href="https://store.brainstormforce.com/terms-and-conditions/" target="_blank">', '<a>', '<a href="https://store.brainstormforce.com/privacy-policy/" target="_blank">', '<a>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
					<a href="#" class="button-subscription-skip"><?php esc_html_e( 'Skip', 'astra-sites' ); ?></a>
				</div>
			</div>
		</div>

		<div class="default">
			<div class="heading">
				<# if( 'astra-sites' === data ) { #>
					<h3><?php esc_html_e( 'Your Selected Website is Being Imported.', 'astra-sites' ); ?></h3>
				<# } else { #>
					<h3><?php esc_html_e( 'Your Selected Template is Being Imported.', 'astra-sites' ); ?></h3>
				<# } #>
				<span class="dashicons close dashicons-no-alt"></span>
			</div>

			<div class="astra-sites-import-content">
				<div class="install-theme-info">
					<div class="astra-sites-advanced-options-wrap">

						<?php if ( true === $subscription_status ) : ?>
							<p><?php esc_html_e( 'To serve more beautiful starter templates, we would like to know more about you:', 'astra-sites' ); ?></p>
						<?php endif; ?>

						<div id="astra-sites-subscription-form-one" class="subscription-form astra-sites-subscription-form-one"></div>

						<h2 class="astra-sites-advanced-options-heading">Advanced Options <span class="dashicons dashicons-arrow-down-alt2"></span></h2>

						<div class="astra-sites-advanced-options">
							<ul class="astra-site-contents">

								<# if( 'astra-sites' === data ) { #>
									<?php
									$first_import_complete = get_option( 'astra_sites_import_complete', '' );
									if ( ! empty( $first_import_complete ) ) :
										?>
									<li class="astra-sites-reset-data">
										<label>
											<input type="checkbox" name="reset" class="checkbox" <?php checked( 'reset', true ); ?>>
											<strong><?php esc_html_e( 'Delete Previously Imported Site', 'astra-sites' ); ?></strong>
											<div class="astra-sites-tooltip-message" id="astra-sites-tooltip-reset-data" style="display: none;"><p><?php esc_html_e( 'WARNING: Selecting this option will delete all data from the previous import. Choose this option only if this is intended.', 'astra-sites' ); ?></p><p><?php esc_html_e( 'You can find the backup to the current customizer settings at ', 'astra-sites' ); ?><code><?php esc_html_e( '/wp-content/uploads/astra-sites/', 'astra-sites' ); ?></code></p></div>
										</label>
									</li>
									<?php endif; ?>

									<?php $theme_status = Astra_Sites::get_instance()->get_theme_status(); ?>
									<?php $theme_dependancy_class = ''; ?>
									<?php if ( 'installed-and-active' !== $theme_status ) { ?>
										<?php $theme_dependancy_class = 'astra-theme-module'; ?>
										<li class="astra-sites-theme-activation">
											<label>
												<input type="checkbox" name="activate-theme" class="checkbox" <?php checked( $site_import_options['activate-theme'], true ); ?> data-status="<?php echo esc_attr( $theme_status ); ?>">
												<strong><?php esc_html_e( 'Install & Activate Astra Theme', 'astra-sites' ); ?></strong>
												<div class="astra-sites-tooltip-message" id="astra-sites-tooltip-theme-activation" style="display: none;"><p><?php esc_html_e( 'To import the site in the original format, you would need the Astra theme activated. You can import it with any other theme, but the site might lose some of the design settings and look a bit different.', 'astra-sites' ); ?></p></div>
											</label>
										</li>
									<?php } ?>

									<li class="astra-sites-import-customizer <?php echo esc_attr( $theme_dependancy_class ); ?>">
										<label>
											<input type="checkbox" name="customizer" class="checkbox" <?php checked( $site_import_options['customizer'], true ); ?> />
											<strong><?php esc_html_e( 'Import Customizer Settings', 'astra-sites' ); ?></strong>
											<span class="astra-sites-tooltip-icon" data-tip-id="astra-sites-tooltip-customizer-settings"><span class="dashicons dashicons-editor-help"></span></span>
											<div class="astra-sites-tooltip-message" id="astra-sites-tooltip-customizer-settings" style="display: none;">
											<?php /* translators: %s are white label strings. */ ?>
											<p><?php printf( esc_html__( '%1$s customizer serves global settings that give uniform design to the website. Choosing this option will override your current customizer settings.', 'astra-sites' ), Astra_Sites_White_Label::get_instance()->get_white_label_name() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
											</div>
										</label>
									</li>

								<# } #>

								<# if( 'astra-sites' === data ) { #>
									<li class="astra-sites-import-widgets">
										<label>
											<input type="checkbox" name="widgets" class="checkbox" <?php checked( $site_import_options['widgets'], true ); ?> />
											<strong><?php esc_html_e( 'Import Widgets', 'astra-sites' ); ?></strong>
										</label>
									</li>
								<# } #>

								<li class="astra-sites-import-plugins">
									<input type="checkbox" name="plugins" class="disabled checkbox" readonly <?php checked( $site_import_options['plugins'], true ); ?> />
									<strong><?php esc_html_e( 'Install Required Plugins', 'astra-sites' ); ?></strong>
									<span class="astra-sites-tooltip-icon" data-tip-id="astra-sites-tooltip-plugins-settings"><span class="dashicons dashicons-editor-help"></span></span>
									<div class="astra-sites-tooltip-message" id="astra-sites-tooltip-plugins-settings" style="display: none;">
										<p><?php esc_html_e( 'Plugins needed to import this template are missing. Required plugins will be installed and activated automatically.', 'astra-sites' ); ?></p>
										<ul class="required-plugins-list"><span class="spinner is-active"></span></ul>
									</div>
								</li>

								<# if( 'astra-sites' === data ) { #>
									<li class="astra-sites-import-xml">
										<label>
											<input type="checkbox" name="xml" class="checkbox" <?php checked( $site_import_options['xml'], true ); ?> />
											<strong><?php esc_html_e( 'Import Content', 'astra-sites' ); ?></strong>
										</label>
										<span class="astra-sites-tooltip-icon" data-tip-id="astra-sites-tooltip-site-content"><span class="dashicons dashicons-editor-help"></span></span>
										<div class="astra-sites-tooltip-message" id="astra-sites-tooltip-site-content" style="display: none;"><p><?php esc_html_e( 'Selecting this option will import dummy pages, posts, images, and menus. If you do not want to import dummy content, please uncheck this option.', 'astra-sites' ); ?></p></div>
									</li>
								<# } #>
							</ul>
						</div>
					</div>
				</div>
				<div class="ast-importing-wrap">
					<#
					if( 'astra-sites' === data ) {
						var string = 'sites';
					} else {
						var string = 'template';
					}
					#>
					<p>
					<?php
					/* translators: %s is the dynamic string. */
					printf( esc_html__( 'The import process can take a few minutes depending on the size of the %s and speed of the connection.', 'astra-sites' ), '{{string}}' );
					?>
					</p>
					<p>
					<?php
					/* translators: %s is the dynamic string. */
					printf( esc_html__( 'Please do NOT close this browser window until the %s is imported completely.', 'astra-sites' ), '{{string}}' );
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
				<a href="#" class="button button-hero button-primary astra-demo-import disabled site-install-site-button">
				<?php
				if ( true === $subscription_status ) {
					esc_html_e( 'Next', 'astra-sites' );
				} else {
					esc_html_e( 'Import', 'astra-sites' );
				}
				?>
				</a>
				<a href="#" class="button button-hero button-primary astra-sites-skip-and-import" style="display: none;"><?php esc_html_e( 'Skip & Import', 'astra-sites' ); ?></a>
				<div class="button button-hero site-import-cancel"><?php esc_html_e( 'Cancel', 'astra-sites' ); ?></div>
			</div>
		</div>
	</div>
</script>

<?php
wp_print_admin_notice_templates();
