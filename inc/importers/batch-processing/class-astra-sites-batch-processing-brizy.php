<?php
/**
 * Batch Processing
 *
 * @package Astra Sites
 * @since 1.2.14
 */

if ( ! class_exists( 'Astra_Sites_Batch_Processing_Brizy' ) ) :

	/**
	 * Astra Sites Batch Processing Brizy
	 *
	 * @since 1.2.14
	 */
	class Astra_Sites_Batch_Processing_Brizy {

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
		 * Import
		 *
		 * @since 1.2.14
		 * @return void
		 */
		public function import() {

			if ( defined( 'WP_CLI' ) ) {
				WP_CLI::line( 'Processing "Brizy" Batch Import' );
			}

			Astra_Sites_Importer_Log::add( '---- Processing WordPress Posts / Pages - for "Brizy" ----' );

			if ( ! is_callable( 'Brizy_Editor_Storage_Common::instance' ) ) {
				return;
			}

			$post_types = Brizy_Editor_Storage_Common::instance()->get( 'post-types' );

			if ( defined( 'WP_CLI' ) ) {
				WP_CLI::line( 'For post types: ' . implode( ', ', $post_types ) );
			}

			if ( empty( $post_types ) && ! is_array( $post_types ) ) {
				return;
			}

			$post_ids = Astra_Sites_Batch_Processing::get_pages( $post_types );
			if ( empty( $post_ids ) && ! is_array( $post_ids ) ) {
				return;
			}

			foreach ( $post_ids as $post_id ) {
				$is_brizy_post = get_post_meta( $post_id, 'brizy_post_uid', true );
				if ( $is_brizy_post ) {
					$this->import_single_post( $post_id );
				}
			}
		}

		/**
		 * Update post meta.
		 *
		 * @param  integer $post_id Post ID.
		 * @return void
		 */
		public function import_single_post( $post_id = 0 ) {

			if ( defined( 'WP_CLI' ) ) {
				WP_CLI::line( 'Brizy - Processing page: ' . $post_id );
			}

			astra_sites_error_log( '---- Processing WordPress Page - for "Brizy" ---- "' . $post_id . '"' );

			$ids_mapping = get_option( 'astra_sites_wpforms_ids_mapping', array() );

			$json_value = null;

			$post = Brizy_Editor_Post::get( (int) $post_id );
			$data = $post->storage()->get( Brizy_Editor_Post::BRIZY_POST, false );

			// @codingStandardsIgnoreStart
			// Decode current data.
			$json_value = base64_decode( $data['editor_data'] );
			// @codingStandardsIgnoreEnd

			// Empty mapping? Then return.
			if ( ! empty( $ids_mapping ) ) {

				// Update WPForm IDs.
				astra_sites_error_log( '---- Processing WP Forms Mapping ----' );
				astra_sites_error_log( $ids_mapping );

				foreach ( $ids_mapping as $old_id => $new_id ) {
					$json_value = str_replace( '[wpforms id=\"' . $old_id, '[wpforms id=\"' . $new_id, $json_value );
				}
			}

			// @codingStandardsIgnoreStart
			// Encode modified data.
			$data['editor_data'] = base64_encode( $json_value );
			// @codingStandardsIgnoreEnd

			$post->set_editor_data( $json_value );
			$post->storage()->set( Brizy_Editor_Post::BRIZY_POST, $data );
			$post->compile_page();
			$post->save();
		}

	}

	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	Astra_Sites_Batch_Processing_Brizy::get_instance();

endif;
