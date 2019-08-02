<?php
/**
 * Premium License
 *
 * @since 1.0.0
 * @package Astra Pro Sites
 */

?>

<div id="astra-pro-sites-license-form" style="display:none;">
	<div class="inner" style="display: none;">

		<?php
			$bsf_product_id = bsf_extract_product_id( ASTRA_PRO_SITES_DIR );
			$args           = array(
				'product_id'                       => $bsf_product_id,
				'button_text_activate'             => esc_html__( 'Activate License', 'astra-sites' ),
				'button_text_deactivate'           => esc_html__( 'Deactivate License', 'astra-sites' ),
				'license_form_title'               => '',
				'license_deactivate_status'        => esc_html__( 'Your license is not active!', 'astra-sites' ),
				'license_activate_status'          => esc_html__( 'Your license is activated!', 'astra-sites' ),
				'submit_button_class'              => 'astra-product-license button-default',
				'form_class'                       => 'form-wrap bsf-license-register-' . esc_attr( $bsf_product_id ),
				'bsf_license_form_heading_class'   => 'astra-license-heading',
				'bsf_license_active_class'         => 'success-message',
				'bsf_license_not_activate_message' => 'license-error',
				'size'                             => 'regular',
				'bsf_license_allow_email'          => false,
			);

			echo bsf_license_activation_form( $args );
			?>

	</div>
</div>
