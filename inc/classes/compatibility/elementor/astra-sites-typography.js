/* global elementor */
jQuery( document ).ready( function( $ ) {
	function handleFonts( font ) {
		elementor.helpers.enqueueFont( font );
	}

	const keys = [
		'astra_sites_page_setting_enable',
		'astra_sites_heading_1',
		'astra_sites_heading_2',
		'astra_sites_heading_3',
		'astra_sites_heading_4',
		'astra_sites_heading_5',
		'astra_sites_heading_6',
		'astra_sites_default_heading',
		'astra_sites_body',
		'astra_sites_main_color',
		'astra_sites_text_color',
		'astra_sites_link_color',
		'astra_sites_link_hover_color',
	];

	for ( let index = 0; index < keys.length; index++ ) {
		const element = keys[ index ] + '_font_family';

		elementor.settings.page.addChangeCallback( element, handleFonts );
	}
} );
