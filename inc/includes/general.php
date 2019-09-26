<?php
/**
 * General Setting Form
 *
 * @package Astra_Sites
 */

$settings = get_option( '_astra_images_integration', array() );
?>

<div class="ast-img-menu-page-wrapper">
	<div id="ast-img-menu-page">
		<div class="ast-img-menu-page-header integration">
			<div class="ast-img-container ast-img-flex">
				<div class="ast-img-title">
					<a href="" target="_blank" rel="noopener">
						<img src="<?php echo esc_url( ASTRA_SITES_URI . 'inc/assets/images/logo.svg' ); ?>" class="ast-img-header-icon" alt="<?php _e( 'Astra Sites', 'astra-sites' ); ?>">
					</a>
				</div>
				<!-- <div class="ast-img-top-links">Take Elementor to The Next Level!<a href="http://wpastra.com/" target="_blank" rel="">View Demos</a></div> -->
			</div>
		</div>

		<div class="ast-img-container ast-img-integration-wrapper">
			<form method="post" class="wrap clear" action="">
				<div class="wrap ast-img-addon-wrap clear">
					<h1 class="screen-reader-text"><?php _e( 'Integrations', 'astra-sites' ); ?></h1>
					<div id="poststuff">
						<div id="post-body" class="columns-1">
							<div id="post-body-content">
								<div class="ast-img-integration-form-wrap">
									<div class="widgets postbox">
										<div class="inside">
											<div class="form-wrap">
												<div class="form-field">
													<label for="ast-img-integration-pixabay-api-key" class="ast-img-integration-heading"><?php _e( 'Pixabay API Key', 'astra-sites' ); ?></label>
													<p class="install-help ast-img-p">
														<strong><?php _e( 'Note:', 'astra-sites' ); ?></strong>
														<?php
														$a_tag_open  = '<a target="_blank" rel="noopener" href="' . esc_url( 'https://wpastra.com/' ) . '">';
														$a_tag_close = '</a>';

														printf(
															/* translators: %1$s: a tag open. */
															__( 'This setting is required if you wish to use Pixabay Images in your website. Need help to get Pixabay API key? Read %1$s this article %2$s.', 'astra-sites' ),
															$a_tag_open,
															$a_tag_close
														);
														?>
													</p>
													<input type="text" name="astra_images_integration[pixabay_api_key]" id="ast-img-integration-pixabay-api-key" class="placeholder placeholder-active" value="<?php echo ( isset( $settings['pixabay_api_key'] ) ) ? $settings['pixabay_api_key'] : ''; ?>">
												</div>
											</div>
										</div>
									</div>
								</div>
								<?php submit_button( __( 'Save Changes', 'astra-sites' ), 'ast-img-save-integration-options button-primary button button-hero' ); ?>
								<?php wp_nonce_field( 'ast-img-integration', 'ast-img-integration-nonce' ); ?>
							</div>
						</div>
						<!-- /post-body -->
						<br class="clear">
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
