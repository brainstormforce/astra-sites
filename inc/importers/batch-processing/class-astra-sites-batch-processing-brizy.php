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
				self::$instance = new self;
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

			// Astra_Sites_Importer_Log::add( '---- Processing WordPress Posts / Pages - for "Brizy" ----' );

			// if ( ! is_callable( 'Brizy_Editor_Storage_Common::instance' ) ) {
			// 	return;
			// }

			// $post_types = Brizy_Editor_Storage_Common::instance()->get( 'post-types' );
			// if ( empty( $post_types ) && ! is_array( $post_types ) ) {
			// 	return;
			// }

			// $post_ids = Astra_Sites_Batch_Processing::get_pages( $post_types );
			// if ( empty( $post_ids ) && ! is_array( $post_ids ) ) {
			// 	return;
			// }

			// foreach ( $post_ids as $post_id ) {
			// 	$is_brizy_post = get_post_meta( $post_id, 'brizy_post_uid', true ); 
			// 	if ( $is_brizy_post ) {
			// 		$this->import_single_post( $post_id );
			// 	}
			// }
		}

		/**
		 * Update post meta.
		 *
		 * @param  integer $post_id Post ID.
		 * @return void
		 */
		public function import_single_post( $post_id = 0 ) {

			error_log('IN Batch ID: ' . $post_id);

			$supported_post_types   = Brizy_Editor::get()->supported_post_types();
			error_log( json_encode( $supported_post_types ) );

			$post = Brizy_Editor_Post::get( (int) $post_id );

			// // $post = get_post( $post_id );

			// $needs_compile = ! $post->isCompiledWithCurrentVersion() || $post->get_needs_compile();

			// error_log( 'hre' );
			// error_log( $needs_compile );

			// if( $needs_compile ) {
			// 	$post->compile_page();
			// 	$post->save();
			// }



			// $pid       = Brizy_Editor::get()->currentPostId();
			$brizyPost = get_post( $post_id );

			$context = Brizy_Content_ContextFactory::createContext( Brizy_Editor_Project::get(), null, $brizyPost, null );

			$compiled_page = $post->get_compiled_page();

			$mainProcessor = new Brizy_Content_MainProcessor( $context );

			$mainProcessor->process( $compiled_page->get_body() );




			// // Brizy_Editor_Post::get( $post )

			// $is_preview    = is_preview() || isset( $_GET['preview'] );
			// $needs_compile = ! $this->post->isCompiledWithCurrentVersion() || $this->post->get_needs_compile();

			// if ( $is_preview ) {
			// 	$user_id      = get_current_user_id();
			// 	$postParentId = $this->post->get_parent_id();
			// 	$autosaveId = Brizy_Editor_Post::getAutoSavePost( $postParentId, $user_id );

			// 	if ( $autosaveId ) {
			// 		$this->post    = Brizy_Editor_Post::get( $autosaveId );
			// 		$needs_compile = ! $this->post->isCompiledWithCurrentVersion() || $this->post->get_needs_compile();
			// 	}
			// }

			// if ( $is_preview || $needs_compile ) {
			// 	$this->post->compile_page();
			// }

			// try {
			// 	if ( ! $is_preview && $needs_compile ) {
			// 		$this->post->save();
			// 	}

			// } catch ( Exception $e ) {
			// 	Brizy_Logger::instance()->exception( $e );
			// }












			// error_log( '---- Processing WordPress Page - for "Brizy" ---- "' . $post_id . '"' );

			// $ids_mapping = get_option( 'astra_sites_wpforms_ids_mapping', array() );

			// $json_value = null;

			// $post = Brizy_Editor_Post::get( (int) $post_id );
			// $data = $post->storage()->get( Brizy_Editor_Post::BRIZY_POST, false );

			// // Decode current data.
			// $json_value = base64_decode( $data['editor_data'] );

			// // Empty mapping? Then return.
			// if ( ! empty( $ids_mapping ) ) {
			// 	// Update WPForm IDs.
			// 	error_log( '---- Processing WP Forms Mapping ----' );
			// 	error_log( print_r( $ids_mapping, true ) );

			// 	foreach ( $ids_mapping as $old_id => $new_id ) {
			// 		$json_value = str_replace( '[wpforms id=\"' . $old_id, '[wpforms id=\"' . $new_id, $json_value );
			// 	}
			// }

			// // Encode modified data.
			// $data['editor_data'] = base64_encode( $json_value );

			// $post->set_editor_data( $json_value );

			// $post->storage()->set( Brizy_Editor_Post::BRIZY_POST, $data );

			// $post->compile_page();
			// $post->save();
		}

	}

	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	Astra_Sites_Batch_Processing_Brizy::get_instance();

endif;
