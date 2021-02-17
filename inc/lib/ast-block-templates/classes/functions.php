<?php
/**
 * Useful functions
 *
 * @since 1.0.0
 * @package Ast Block Templates
 */

if ( ! function_exists( 'ast_block_templates_log' ) ) :

	/**
	 * Log
	 *
	 * @param string $message   Log message.
	 */
	function ast_block_templates_log( $message = '' ) {
		if ( ast_block_templates_doing_wp_cli() ) {
			WP_CLI::line( $message );
		} else {
			error_log( $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
endif;

if ( ! function_exists( 'ast_block_templates_doing_wp_cli' ) ) :
	/**
	 * Doing WP CLI
	 */
	function ast_block_templates_doing_wp_cli() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return true;
		}
		return false;
	}
endif;

if ( ! function_exists( 'ast_block_templates_get_filesystem' ) ) :
	/**
	 * Get an instance of WP_Filesystem_Direct.
	 *
	 * @since 1.0.0
	 * @return object A WP_Filesystem_Direct instance.
	 */
	function ast_block_templates_get_filesystem() {
		global $wp_filesystem;

		require_once ABSPATH . '/wp-admin/includes/file.php';

		WP_Filesystem();

		return $wp_filesystem;
	}
endif;

if ( ! function_exists( 'ast_block_templates_is_valid_image' ) ) :
	/**
	 * Check for the valid image
	 *
	 * @param string $link  The Image link.
	 *
	 * @since 1.0.0
	 * @return boolean
	 */
	function ast_block_templates_is_valid_image( $link = '' ) {
		return preg_match( '/^((https?:\/\/)|(www\.))([a-z0-9-].?)+(:[0-9]+)?\/[\w\-]+\.(jpg|png|gif|jpeg)\/?$/i', $link );
	}
endif;
