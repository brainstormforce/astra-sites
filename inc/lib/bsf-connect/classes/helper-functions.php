<?php
function bsf_is_active_license( $product_id ) {
	$brainstrom_products = get_option( 'brainstrom_products', array() );
	$brainstorm_plugins  = isset( $brainstrom_products['plugins'] ) ? $brainstrom_products['plugins'] : array();
	$brainstorm_themes   = isset( $brainstrom_products['themes'] ) ? $brainstrom_products['themes'] : array();

	$all_products = $brainstorm_plugins + $brainstorm_themes;

	if ( ! isset( $all_products[ $product_id ] ) ) {
		return false;
	}

	// Not have purchase key?
	if ( ! isset( $all_products[ $product_id ]['purchase_key'] ) ) {
		return false;
	}

	if ( ! isset( $all_products[ $product_id ]['status'] ) ) {
		return false;
	}

	if ( 'registered' !== $all_products[ $product_id ]['status'] ) {
		return false;
	}

	return true;
}

function bsf_get_product_info( $product_id, $key ) {

	$brainstrom_products = get_option( 'brainstrom_products', array() );
	$brainstorm_plugins  = isset( $brainstrom_products['plugins'] ) ? $brainstrom_products['plugins'] : array();
	$brainstorm_themes   = isset( $brainstrom_products['themes'] ) ? $brainstrom_products['themes'] : array();

	$all_products = $brainstorm_plugins + $brainstorm_themes;

	if ( isset( $all_products[ $product_id ][ $key ] ) && $all_products[ $product_id ][ $key ] !== '' ) {
		return $all_products[ $product_id ][ $key ];
	}
}

function bsf_connect_link() {
	$api_url = BSF_Connect::get_instance()->get_api_url();
	$api_text = BSF_Connect::get_instance()->get_api_status_text();
	?>
	<a href="<?php echo $api_url; ?>"><?php echo $api_text; ?></a>
	<?php
}

function bsf_install_demo_site_url() {

	$args = array(
		'action' => 'site-import',
	);

	return BSF_Connect::get_instance()->get_api_url( $args );
}

function bsf_install_demo_site_args( $args = array() )
{
	return BSF_Connect::get_instance()->get_api_args( $args );
}