<?php
/**
 * Class Astra WXR Importer
 *
 * @since  1.0.0
 * @package Astra Addon
 */

defined( 'ABSPATH' ) or exit;

/**
 * Class Astra WXR Importer
 *
 * @since  1.0.0
 */
class Astra_WXR_Importer {

	/**
	 * Instance of Astra_WXR_Importer
	 *
	 * @since  1.0.0
	 * @var Astra_WXR_Importer
	 */
	private static $_instance = null;

	/**
	 * Instantiate Astra_WXR_Importer
	 *
	 * @since  1.0.0
	 * @return (Object) Astra_WXR_Importer.
	 */
	public static function instance() {
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 */
	private function __construct() {

		require_once ABSPATH . '/wp-admin/includes/class-wp-importer.php';
		require_once ASTRA_SITES_DIR . 'inc/importers/wxr-importer/class-logger.php';
		require_once ASTRA_SITES_DIR . 'inc/importers/wxr-importer/class-wp-importer-logger-serversentevents.php';
		require_once ASTRA_SITES_DIR . 'inc/importers/wxr-importer/class-wxr-importer.php';
		require_once ASTRA_SITES_DIR . 'inc/importers/wxr-importer/class-wxr-import-info.php';

		add_filter( 'upload_mimes', array( $this, 'custom_upload_mimes' ) );
		add_action( 'wp_ajax_astra-wxr-import', array( $this, 'sse_import' ) );
		add_filter( 'wxr_importer.pre_process.user', '__return_null' );
		add_filter( 'wxr_importer.pre_process.post', array( $this, 'gutenberg_content_fix' ), 10, 4 );

		if ( version_compare( get_bloginfo( 'version' ), '5.1.0', '>=' ) ) {
			add_filter( 'wp_check_filetype_and_ext', array( $this, 'real_mime_types_5_1_0' ), 10, 5 );
		} else {
			add_filter( 'wp_check_filetype_and_ext', array( $this, 'real_mime_types' ), 10, 4 );
		}

	}

	/**
	 * Track Imported Post
	 *
	 * @param  int $post_id Post ID.
	 * @return void
	 */
	function track_post( $post_id ) {
		Astra_Sites_Importer_Log::add( 'Inserted - Post ' . $post_id . ' - ' . get_post_type( $post_id ) . ' - ' . get_the_title( $post_id ) );
		update_post_meta( $post_id, '_astra_sites_imported_post', true );
	}

	/**
	 * Track Imported Term
	 *
	 * @param  int $term_id Term ID.
	 * @return void
	 */
	function track_term( $term_id ) {
		$term = get_term( $term_id );
		if ( $term ) {
			Astra_Sites_Importer_Log::add( 'Inserted - Term ' . $term_id . ' - ' . json_encode( $term ) );
		}
		update_term_meta( $term_id, '_astra_sites_imported_term', true );
	}

	/**
	 * Gutenberg Content Data Fix
	 *
	 * Gutenberg encode the page content. In import process the encoded characterless e.g. <, > are
	 * decoded into HTML tag and it break the Gutenberg render markup.
	 *
	 * Note: We have not check the post is created with Gutenberg or not. We have imported other sites
	 * and confirm that this works for every other page builders too.
	 *
	 * @since 1.2.12
	 *
	 * @param array $data Post data. (Return empty to skip.).
	 * @param array $meta Meta data.
	 * @param array $comments Comments on the post.
	 * @param array $terms Terms on the post.
	 */
	function gutenberg_content_fix( $data, $meta, $comments, $terms ) {
		if ( isset( $data['post_content'] ) ) {
			$data['post_content'] = wp_slash( $data['post_content'] );
		}
		return $data;
	}

	/**
	 * Different MIME type of different PHP version
	 *
	 * Filters the "real" file type of the given file.
	 *
	 * @since 1.2.9
	 *
	 * @param array  $defaults File data array containing 'ext', 'type', and
	 *                                          'proper_filename' keys.
	 * @param string $file                      Full path to the file.
	 * @param string $filename                  The name of the file (may differ from $file due to
	 *                                          $file being in a tmp directory).
	 * @param array  $mimes                     Key is the file extension with value as the mime type.
	 * @param string $real_mime                Real MIME type of the uploaded file.
	 */
	function real_mime_types_5_1_0( $defaults, $file, $filename, $mimes, $real_mime ) {
		return $this->real_mimes( $defaults, $filename );
	}

	/**
	 * Different MIME type of different PHP version
	 *
	 * Filters the "real" file type of the given file.
	 *
	 * @since 1.2.9
	 *
	 * @param array  $defaults File data array containing 'ext', 'type', and
	 *                                          'proper_filename' keys.
	 * @param string $file                      Full path to the file.
	 * @param string $filename                  The name of the file (may differ from $file due to
	 *                                          $file being in a tmp directory).
	 * @param array  $mimes                     Key is the file extension with value as the mime type.
	 */
	function real_mime_types( $defaults, $file, $filename, $mimes ) {
		return $this->real_mimes( $defaults, $filename );
	}

	/**
	 * Real Mime Type
	 *
	 * @since 1.2.15
	 *
	 * @param array  $defaults File data array containing 'ext', 'type', and
	 *                                          'proper_filename' keys.
	 * @param string $filename                  The name of the file (may differ from $file due to
	 *                                          $file being in a tmp directory).
	 */
	function real_mimes( $defaults, $filename ) {

		// Set EXT and real MIME type only for the file name `wxr.xml`.
		if ( 'wxr.xml' === $filename ) {
			$defaults['ext']  = 'xml';
			$defaults['type'] = 'text/xml';
		}

		// Set EXT and real MIME type only for the file name `wpforms.json`.
		if ( 'wpforms.json' === $filename ) {
			$defaults['ext']  = 'json';
			$defaults['type'] = 'text/plain';
		}

		return $defaults;
	}

	/**
	 * Constructor.
	 *
	 * @since  1.1.0
	 * @since  1.4.0 The `$xml_url` was added.
	 *
	 * @param  string $xml_url XML file URL.
	 */
	function sse_import( $xml_url = '' ) {

		if ( ! defined( 'WP_CLI' ) ) {

			// Verify Nonce.
			check_ajax_referer( 'astra-sites', '_ajax_nonce' );

			// Start the event stream.
			header( 'Content-Type: text/event-stream, charset=UTF-8' );

			// Turn off PHP output compression.
			$previous = error_reporting( error_reporting() ^ E_WARNING );
			ini_set( 'output_buffering', 'off' );
			ini_set( 'zlib.output_compression', false );
			error_reporting( $previous );

			if ( $GLOBALS['is_nginx'] ) {
				// Setting this header instructs Nginx to disable fastcgi_buffering
				// and disable gzip for this request.
				header( 'X-Accel-Buffering: no' );
				header( 'Content-Encoding: none' );
			}

			// 2KB padding for IE
			echo ':' . str_repeat( ' ', 2048 ) . "\n\n";
		}

		$xml_url = isset( $_REQUEST['xml_url'] ) ? urldecode( $_REQUEST['xml_url'] ) : urldecode( $xml_url );
		if ( empty( $xml_url ) ) {
			exit;
		}

		if ( ! defined( 'WP_CLI' ) ) {
			// Time to run the import!
			set_time_limit( 0 );

			// Ensure we're not buffered.
			wp_ob_end_flush_all();
			flush();
		}

		// Are we allowed to create users?
		add_filter( 'wxr_importer.pre_process.user', '__return_null' );

		// Keep track of our progress.
		add_action( 'wxr_importer.processed.post', array( $this, 'imported_post' ), 10, 2 );
		add_action( 'wxr_importer.process_failed.post', array( $this, 'imported_post' ), 10, 2 );
		add_action( 'wxr_importer.process_already_imported.post', array( $this, 'already_imported_post' ), 10, 2 );
		add_action( 'wxr_importer.process_skipped.post', array( $this, 'already_imported_post' ), 10, 2 );
		add_action( 'wxr_importer.processed.comment', array( $this, 'imported_comment' ) );
		add_action( 'wxr_importer.process_already_imported.comment', array( $this, 'imported_comment' ) );
		add_action( 'wxr_importer.processed.term', array( $this, 'imported_term' ) );
		add_action( 'wxr_importer.process_failed.term', array( $this, 'imported_term' ) );
		add_action( 'wxr_importer.process_already_imported.term', array( $this, 'imported_term' ) );
		add_action( 'wxr_importer.processed.user', array( $this, 'imported_user' ) );
		add_action( 'wxr_importer.process_failed.user', array( $this, 'imported_user' ) );

		// Keep track of our progress.
		add_action( 'wxr_importer.processed.post', array( $this, 'track_post' ) );
		add_action( 'wxr_importer.processed.term', array( $this, 'track_term' ) );

		// Flush once more.
		flush();

		$importer = $this->get_importer();
		$response = $importer->import( $xml_url );

		// Let the browser know we're done.
		$complete = array(
			'action' => 'complete',
			'error'  => false,
		);
		if ( is_wp_error( $response ) ) {
			$complete['error'] = $response->get_error_message();
		}

		$this->emit_sse_message( $complete );
		if ( ! defined( 'WP_CLI' ) ) {
			exit;
		}
	}

	/**
	 * Add .xml files as supported format in the uploader.
	 *
	 * @since 1.1.5 Added SVG file support.
	 *
	 * @since 1.0.0
	 *
	 * @param array $mimes Already supported mime types.
	 */
	public function custom_upload_mimes( $mimes ) {

		// Allow SVG files.
		$mimes['svg']  = 'image/svg+xml';
		$mimes['svgz'] = 'image/svg+xml';

		// Allow XML files.
		$mimes['xml'] = 'text/xml';

		// Allow JSON files.
		$mimes['json'] = 'application/json';

		return $mimes;
	}

	/**
	 * Start the xml import.
	 *
	 * @since  1.0.0
	 *
	 * @param  (String) $path Absolute path to the XML file.
	 */
	public function get_xml_data( $path ) {

		$args = array(
			'action'  => 'astra-wxr-import',
			'id'      => '1',
			'xml_url' => $path,
		);
		$url  = add_query_arg( urlencode_deep( $args ), admin_url( 'admin-ajax.php' ) );

		$data = $this->get_data( $path );

		return array(
			'count'   => array(
				'posts'    => $data->post_count,
				'media'    => $data->media_count,
				'users'    => count( $data->users ),
				'comments' => $data->comment_count,
				'terms'    => $data->term_count,
			),
			'url'     => $url,
			'strings' => array(
				'complete' => __( 'Import complete!', 'astra-sites' ),
			),
		);
	}

	/**
	 * Get XML data.
	 *
	 * @since 1.1.0
	 * @param  string $url Downloaded XML file absolute URL.
	 * @return array  XML file data.
	 */
	function get_data( $url ) {
		$importer = $this->get_importer();
		$data     = $importer->get_preliminary_information( $url );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		return $data;
	}

	/**
	 * Get Importer
	 *
	 * @since 1.1.0
	 * @return object   Importer object.
	 */
	public function get_importer() {
		$options = apply_filters(
			'astra_sites_xml_import_options',
			array(
				'fetch_attachments' => true,
				'default_author'    => get_current_user_id(),
			)
		);

		$importer = new WXR_Importer( $options );
		$logger   = new WP_Importer_Logger_ServerSentEvents();

		$importer->set_logger( $logger );
		return $importer;
	}

	/**
	 * Send message when a post has been imported.
	 *
	 * @since 1.1.0
	 * @param int   $id Post ID.
	 * @param array $data Post data saved to the DB.
	 */
	public function imported_post( $id, $data ) {
		$this->emit_sse_message(
			array(
				'action' => 'updateDelta',
				'type'   => ( 'attachment' === $data['post_type'] ) ? 'media' : 'posts',
				'delta'  => 1,
			)
		);
	}

	/**
	 * Send message when a post is marked as already imported.
	 *
	 * @since 1.1.0
	 * @param array $data Post data saved to the DB.
	 */
	public function already_imported_post( $data ) {
		$this->emit_sse_message(
			array(
				'action' => 'updateDelta',
				'type'   => ( 'attachment' === $data['post_type'] ) ? 'media' : 'posts',
				'delta'  => 1,
			)
		);
	}

	/**
	 * Send message when a comment has been imported.
	 *
	 * @since 1.1.0
	 */
	public function imported_comment() {
		$this->emit_sse_message(
			array(
				'action' => 'updateDelta',
				'type'   => 'comments',
				'delta'  => 1,
			)
		);
	}

	/**
	 * Send message when a term has been imported.
	 *
	 * @since 1.1.0
	 */
	public function imported_term() {
		$this->emit_sse_message(
			array(
				'action' => 'updateDelta',
				'type'   => 'terms',
				'delta'  => 1,
			)
		);
	}

	/**
	 * Send message when a user has been imported.
	 *
	 * @since 1.1.0
	 */
	public function imported_user() {
		$this->emit_sse_message(
			array(
				'action' => 'updateDelta',
				'type'   => 'users',
				'delta'  => 1,
			)
		);
	}

	/**
	 * Emit a Server-Sent Events message.
	 *
	 * @since 1.1.0
	 * @param mixed $data Data to be JSON-encoded and sent in the message.
	 */
	public function emit_sse_message( $data ) {

		if ( ! defined( 'WP_CLI' ) ) {
			echo "event: message\n";
			echo 'data: ' . wp_json_encode( $data ) . "\n\n";

			// Extra padding.
			echo ':' . str_repeat( ' ', 2048 ) . "\n\n";
		}

		flush();
	}

}

Astra_WXR_Importer::instance();
