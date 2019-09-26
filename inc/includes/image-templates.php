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
 * @since x.x.x
 */

defined( 'ABSPATH' ) or exit;

?>

<script type="text/template" id="tmpl-ast-image-skeleton">
	<div class="ast-image__skeleton-wrap">
		<div class="ast-image__skeleton-inner-wrap">
			<div class="ast-image__skeleton">
			</div>
			<div class="ast-image__preview-skeleton">
			</div>
		</div>
	</div>
	<div class="ast-image__loader-wrap">
		<div class="ast-image__loader-1"></div>
		<div class="ast-image__loader-2"></div>
		<div class="ast-image__loader-3"></div>
	</div>
</script>

<script type="text/template" id="tmpl-ast-image-list">

	<# var count = 0; #>
		<# for ( key in data ) { count++; #>
			<# var imported_class = _.includes( astraImages.saved_images, data[key]['id'].toString() ) ? 'imported' : ''; #>
			<div class="ast-image__list-wrap loading" data-id="{{data[key]['id']}}" data-url="{{data[key]['pageURL']}}">
				<div class="ast-image__list-inner-wrap {{imported_class}}">
					<div class="ast-image__list-img-wrap">
						<img src="{{data[key]['webformatURL']}}" alt="{{data[key]['tags']}}" />
						<div class="ast-image__list-img-overlay" data-img-info="{{JSON.stringify( data[key] )}}">
							<span>{{data[key]['tags']}}</span>
						</div>
					</div>
				</div>
			</div>
		<# } #>
		<# if ( 0 === count ) { #>
			<# if ( AstraImageCommon.apiStatus ) { #>
			<div class="astra-sites-no-sites">
				<h2><?php _e( 'Sorry No Result Found.', 'astra-sites' ); ?></h2>
			</div>
			<# } else { #>
			<# var ht = 'calc( ' + $scope.find( '.ast-image__skeleton-inner-wrap' ).innerHeight() + 'px - 50px );' #>
			<# var license_val = ( undefined != astraImages.integration[ 'pixabay_api_key'] ) ? astraImages.integration[ 'pixabay_api_key'] : '' #>
				<div class="astra-sites-no-license" style="height: {{ht}};">
				</div>
				<div class="ast-image__license-wrap">
					<div class="ast-image__license-heading-wrap">
						<h2 class="ast-image__license-heading"><?php _e( 'Stunning free images & royalty free stock', 'astra-images' ); ?></h2>
					</div>
					<div>
						<p class="ast-image__license-description"><?php _e( 'Over 1 million+ high quality stock images and videos shared by Pixabay community. Over 1 million+ high quality stock images and videos', 'astra-sites' ); ?></p>
					</div>
					<div class="ast-image__license-input-wrap">
						<div class="ast-image__license-input-inner-wrap">
							<input type="text" data-type="pixabay" value="{{license_val}}" placeholder="<?php _e( 'Enter the Key', 'astra-sites' ); ?>" class="ast-image__license" />
							<# if ( 200 == astraImages.api_status['code'] ) { #>
							<div class="dashicons-yes-alt dashicons ast-image-valid-license"></div>
							<# } #>
							<div class="ast-image__license-msg"><i class="dashicons-warning dashicons"></i><span></span></div>
						</div>
						<button type="button" class="button media-button button-primary button-large ast-image__validate-btn"><?php _e( 'Validate Key', 'astra-sites' ); ?></button>
					</div>
					<# if ( 200 == astraImages.api_status['code'] ) { #>
					<div class="ast-image__browse-images"><?php _e( '&larr; Browse Images', 'astra-sites' ); ?></div>
					<# } #>
					<div class="ast-image__license-get-wrap">
						<h4><?php _e( 'Don\'t have an API Key?', 'astra-sites' ); ?> <a href="#" target="_blank"><?php _e( 'Create here', 'astra-sites' ); ?></a></h4>
					</div>
				</div>
			<# } #>
		<# } #>
</script>

<script type="text/template" id="tmpl-ast-image-filters">
	<div class="ast-image__filter-wrap">
		<ul class="ast-image__filter">
			<li class="ast-image__filter-category">
				<select>
					<# for ( key in astraImages.pixabay_category ) { #>
					<option value="{{key}}">{{astraImages.pixabay_category[key]}}</option>
					<# } #>
				</select>
			</li>
			<li class="ast-image__filter-orientation">
				<select>
					<# for ( key in astraImages.pixabay_orientation ) { #>
					<option value="{{key}}">{{astraImages.pixabay_orientation[key]}}</option>
					<# } #>
				</select>
			</li>
			<li class="ast-image__filter-order">
				<select>
					<# for ( key in astraImages.pixabay_order ) { #>
					<option value="{{key}}">{{astraImages.pixabay_order[key]}}</option>
					<# } #>
				</select>
			</li>
			<li class="ast-image__license-edit-key">
				<div><a href="javascript:void(0);" title="<?php _e( 'Edit API Key', 'astra-sites' ); ?>" class="dashicons-admin-network dashicons ast-image__edit-api"></a></div>
			</li>
		</ul>
	</div>
</script>

<script type="text/template" id="tmpl-ast-image-no-result">
	<div class="astra-sites-no-sites">
		<h2><?php _e( 'Sorry No Result Found.', 'astra-sites' ); ?></h2>
		<p class="description">
			<?php
			/* translators: %1$s External Link */
			printf( __( 'Don\'t see a template you would like to import?<br><a target="_blank" href="%1$s">Please Suggest Us!</a>', 'astra-sites' ), esc_url( 'https://wpastra.com/sites-suggestions/?utm_source=demo-import-panel&utm_campaign=astra-sites&utm_medium=suggestions' ) );
			?>
		</p>
	</div>
</script>

<script type="text/template" id="tmpl-ast-image-single">
	<# var is_imported = _.includes( astraImages.saved_images, data.id.toString() ); #>
	<# var disable_class = ( is_imported ) ? 'disabled': ''; #>
	<# var image_type = data.largeImageURL.substring( data.largeImageURL.lastIndexOf( "." ) + 1 ); #>
	<div class="single-site-wrap">
		<div class="single-site">
			<div class="single-site-preview-wrap">
				<div class="single-site-preview">
					<img class="theme-screenshot" src="{{data.largeImageURL}}">
				</div>
			</div>
		</div>
		<div class="single-site-info">
			<div class="ast-image__title-wrap">
				<h3 class="ast-image__title">{{data.tags}}</h3>
			</div>
			<div class="ast-image__info-wrap">
				<div class="ast-image__info-user">
					<a href="https://pixabay.com/users/{{data.user}}" class="" target="_blank" rel="noreferrer noopener"><?php _e( 'By', 'astra-sites' ); ?> {{data.user}}</a>
				</div>
				<div class="ast-image__info-image">
					<ul class="ast-image__info-list">
						<li><strong><?php _e( 'Type', 'astra-sites' ); ?></strong><br>{{image_type}}</li>
						<li><strong><?php _e( 'Size', 'astra-sites' ); ?></strong><br>{{(data.imageSize/1000000).toFixed(2)}}MB</li>
						<li><strong><?php _e( 'Dimensions', 'astra-sites' ); ?></strong><br>{{data.imageHeight}}px X {{data.imageWidth}}px</li>
					</ul>
				</div>
				<div class="ast-image__save-wrap">
					<button type="button" class="ast-image__save button media-button button-primary button-large media-button-select {{disable_class}}" data-import-status={{is_imported}}>
						<# if ( is_imported ) { #>
							<?php _e( 'Already Saved', 'astra-sites' ); ?>
						<# } else { #>
							<?php _e( 'Save & Insert', 'astra-sites' ); ?>
						<# } #>
					</button>
				</div>
			</div>
		</div>
	</div>
</script>

<script type="text/template" id="tmpl-ast-image-go-back">
	<span class="ast-image__go-back">
		<i class="icon-chevron-left"></i>
		<span class="ast-image__go-back-text"><?php _e( 'Back to Images', 'astra-sites' ); ?></span>
	</span>
</script>

<?php
