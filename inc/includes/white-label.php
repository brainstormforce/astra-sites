<?php
/**
 * White Label Form
 *
 * @package Astra Sites
 */

?>
<?php
// Bail from displaying settings screen if Astra Pro is older version.
if ( ! is_callable( 'Astra_Ext_White_Label_Markup::branding_key_to_constant' ) ) {
	return;
}
?>
<li>
	<div class="branding-form postbox">
		<button type="button" class="handlediv button-link" aria-expanded="true">
			<span class="screen-reader-text"><?php echo esc_html( $plugin_name ); ?></span>
			<span class="toggle-indicator" aria-hidden="true"></span>
		</button>

		<h3 class="hndle ui-sortable-handle">
			<span><?php echo esc_html( $plugin_name ); ?></span>
		</h3>

		<div class="inside">
			<div class="form-wrap">
				<div class="form-field">
					<label><?php _e( 'Plugin Name:', 'astra-sites' ); ?>
						<input type="text" name="ast_white_label[astra-sites][name]" class="placeholder placeholder-active" <?php disabled( defined( Astra_Ext_White_Label_Markup::branding_key_to_constant( 'astra-sites', 'name' ) ), true, true ); ?> value="<?php echo esc_attr( Astra_Ext_White_Label_Markup::get_whitelabel_string( 'astra-sites', 'name' ) ); ?>">
					</label>
				</div>
				<div class="form-field">
					<label><?php _e( 'Plugin Description:', 'astra-sites' ); ?>
						<textarea name="ast_white_label[astra-sites][description]" class="placeholder placeholder-active" <?php disabled( defined( Astra_Ext_White_Label_Markup::branding_key_to_constant( 'astra-sites', 'description' ) ), true, true ); ?> rows="2"><?php echo esc_attr( Astra_Ext_White_Label_Markup::get_whitelabel_string( 'astra-sites', 'description' ) ); ?></textarea>
					</label>
				</div>
				<div class="clear"></div>
			</div>
		</div>
	</div>
</li>
