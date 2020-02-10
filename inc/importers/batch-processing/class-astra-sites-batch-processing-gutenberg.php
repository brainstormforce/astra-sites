<?php
/**
 * Batch Processing
 *
 * @package Astra Sites
 * @since 1.2.14
 */

if ( ! class_exists( 'Astra_Sites_Batch_Processing_Gutenberg' ) ) :

	/**
	 * Astra Sites Batch Processing Brizy
	 *
	 * @since 1.2.14
	 */
	class Astra_Sites_Batch_Processing_Gutenberg {

		/**
		 * Instance
		 *
		 * @since 1.2.14
		 * @access private
		 * @var object Class object.
		 */
		private static $instance;

		/**
		 * Initiator
		 *
		 * @since 1.2.14
		 * @return object initialized object of class.
		 */
		public static function get_instance() {

			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * @since 1.2.14
		 */
		public function __construct() {}

		/**
		 * Allowed tags for the batch update process.
		 *
		 * @param  array        $allowedposttags   Array of default allowable HTML tags.
		 * @param  string|array $context    The context for which to retrieve tags. Allowed values are 'post',
		 *                                  'strip', 'data', 'entities', or the name of a field filter such as
		 *                                  'pre_user_description'.
		 * @return array Array of allowed HTML tags and their allowed attributes.
		 */
		public function allowed_tags_and_attributes( $allowedposttags, $context ) {

			// Keep only for 'post' contenxt.
			if ( 'post' === $context ) {

				// <svg> tag and attributes.
				$allowedposttags['svg'] = array(
					'xmlns'   => true,
					'viewbox' => true,
				);

				// <path> tag and attributes.
				$allowedposttags['path'] = array(
					'd' => true,
				);
			}

			return $allowedposttags;
		}

		/**
		 * Import
		 *
		 * @since 1.2.14
		 * @return void
		 */
		public function import() {

			// Allow the SVG tags in batch update process.
			add_filter( 'wp_kses_allowed_html', array( $this, 'allowed_tags_and_attributes' ), 10, 2 );

			Astra_Sites_Importer_Log::add( '---- Processing WordPress Posts / Pages - for "Gutenberg" ----' );

			$post_types = apply_filters( 'astra_sites_gutenberg_batch_process_post_types', array( 'page' ) );

			$post_ids = Astra_Sites_Batch_Processing::get_pages( $post_types );
			if ( empty( $post_ids ) && ! is_array( $post_ids ) ) {
				return;
			}

			foreach ( $post_ids as $post_id ) {
				$this->import_single_post( $post_id );
			}
		}

		/**
		 * Update post meta.
		 *
		 * @param  integer $post_id Post ID.
		 * @return void
		 */
		public function import_single_post( $post_id = 0 ) {

			$is_elementor_page      = get_post_meta( $post_id, '_elementor_version', true );
			$is_beaver_builder_page = get_post_meta( $post_id, '_fl_builder_enabled', true );
			$is_brizy_page          = get_post_meta( $post_id, 'brizy_post_uid', true );

			// If page contain Elementor, Brizy or Beaver Builder meta then skip this page.
			if ( $is_elementor_page || $is_beaver_builder_page || $is_brizy_page ) {
				return;
			}

			Astra_Sites_Importer_Log::add( '---- Processing WordPress Page - for Gutenberg ---- "' . $post_id . '"' );

			$ids_mapping = get_option( 'astra_sites_wpforms_ids_mapping', array() );

			// Post content.
			$content = get_post_field( 'post_content', $post_id );

			// Empty mapping? Then return.
			if ( ! empty( $ids_mapping ) ) {
				// Replace ID's.
				foreach ( $ids_mapping as $old_id => $new_id ) {
					$content = str_replace( '[wpforms id="' . $old_id, '[wpforms id="' . $new_id, $content );
				}
			}

			// This replaces the category ID in UAG Post blocks.
			$site_options = get_option( 'astra_sites_import_data', array() );

			if ( isset( $site_options['astra-site-taxonomy-mapping'] ) ) {

				$tax_mapping = $site_options['astra-site-taxonomy-mapping'];

				if ( isset( $tax_mapping['post'] ) ) {

					$catogory_mapping = ( isset( $tax_mapping['post']['category'] ) ) ? $tax_mapping['post']['category'] : array();

					if ( is_array( $catogory_mapping ) && ! empty( $catogory_mapping ) ) {

						foreach ( $catogory_mapping as $key => $value ) {

							$this_site_term = get_term_by( 'slug', $value['slug'], 'category' );
							$content        = str_replace( '"categories":"' . $value['id'], '"categories":"' . $this_site_term->term_id, $content );
						}
					}
				}
			}

			// # Tweak
			// Gutenberg break block markup from render. Because the '&' is updated in database with '&amp;' and it
			// expects as 'u0026amp;'. So, Converted '&amp;' with 'u0026amp;'.
			//
			// @todo This affect for normal page content too. Detect only Gutenberg pages and process only on it.
			// $content = str_replace( '&amp;', "\u0026amp;", $content );
			$content = $this->get_content( $content );

			// Update content.
			wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => $content,
				)
			);
		}

		/**
		 * Download and Replace hotlink images
		 *
		 * @since 2.0.0
		 *
		 * @param  string $content Mixed post content.
		 * @return array           Hotlink image array.
		 */
		public function get_content( $content = '' ) {

			$all_links   = wp_extract_urls( $content );
			$image_links = array();
			$image_map   = array();

			// Not have any link.
			if ( empty( $all_links ) ) {
				return $content;
			}

			foreach ( $all_links as $key => $link ) {

				if ( preg_match( '/^((https?:\/\/)|(www\.))([a-z0-9-].?)+(:[0-9]+)?\/[\w\-]+\.(jpg|png|gif|jpeg)\/?$/i', $link ) ) {
					$image_links[] = $link;
				}
			}
			// Not have any image link.
			if ( empty( $image_links ) ) {
				return $content;
			}

			foreach ( $image_links as $key => $image_url ) {
				// Download remote image.
				$image            = array(
					'url' => $image_url,
					'id'  => wp_rand( 000, 999 ),
				);
				$downloaded_image = Astra_Sites_Image_Importer::get_instance()->import( $image );
				// Old and New image mapping links.
				$image_map[ $image_url ] = $downloaded_image['url'];
			}

			// Replace old image links with new image links.
			foreach ( $image_map as $old_url => $new_url ) {
				$content = str_replace( $old_url, $new_url, $content );
			}

			return $content;
		}

	}

	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	Astra_Sites_Batch_Processing_Gutenberg::get_instance();

endif;
