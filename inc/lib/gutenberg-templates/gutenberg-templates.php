<?php
/**
 * Plugin Name: Gutenberg Starter Templates
 * Plugin URI: https://wpastra.com/
 * Description: Gutenberg single page templates, and blocks library to imported your website easily.
 * Version: 1.0.0
 * Author: Brainstorm Force
 * Author URI: https://www.brainstormforce.com
 * Text Domain: gutenberg-templates
 *
 * @package Gutenberg Templates
 */

if ( class_exists( 'Gutenberg_Templates' ) ) {
	return;
}

if ( apply_filters( 'ast_block_templates_disable', false ) ) {
	return;
}

// Set constants.
define( 'GUTENBERG_TEMPLATES_LIBRARY_URL', 'https://websitedemos.net/' );
define( 'GUTENBERG_TEMPLATES_VER', '1.0.0' );
define( 'GUTENBERG_TEMPLATES_FILE', __FILE__ );
define( 'GUTENBERG_TEMPLATES_BASE', plugin_basename( GUTENBERG_TEMPLATES_FILE ) );
define( 'GUTENBERG_TEMPLATES_DIR', plugin_dir_path( GUTENBERG_TEMPLATES_FILE ) );
define( 'GUTENBERG_TEMPLATES_URI', plugins_url( '/', GUTENBERG_TEMPLATES_FILE ) );

require_once GUTENBERG_TEMPLATES_DIR . 'classes/class-gutenberg-templates.php';
