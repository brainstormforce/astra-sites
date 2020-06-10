<?php
/**
 * Single Page Background Process
 *
 * @package Astra Sites
 * @since 2.0.0
 */

if ( class_exists( 'WP_Background_Process' ) ) :

	/**
	 * Image Background Process
	 *
	 * @since 2.0.0
	 */
	class WP_Background_Process_Astra_Site_Importer extends WP_Background_Process {

		/**
		 * Image Process
		 *
		 * @var string
		 */
		protected $action = 'astra_site_importer';

		/**
		 * Task
		 *
		 * Override this method to perform any actions required on each
		 * queue item. Return the modified item for further processing
		 * in the next pass through. Or, return false to remove the
		 * item from the queue.
		 *
		 * @since 2.0.0
		 *
		 * @param object $object Queue item object.
		 * @return mixed
		 */
		protected function task( $object ) {

			$process = $object['instance'];
			$method  = $object['method'];

			if ( 'import_page_builders' === $method ) {
				astra_sites_error_log( '-------- Importing Page Builders --------' );
				update_site_option( 'astra-sites-batch-status-string', 'Importing Page Builders', 'no' );
				$process->import_page_builders();
			} elseif ( 'import_categories' === $method ) {
				astra_sites_error_log( '-------- Importing Tags --------' );
				update_site_option( 'astra-sites-batch-status-string', 'Importing Tags', 'no' );
				$process->import_categories();
			} elseif ( 'import_sites' === $method ) {
				astra_sites_error_log( '-------- Importing Sites --------' );
				$page = $object['page'];
				astra_sites_error_log( 'Inside Batch ' . $page );
				update_site_option( 'astra-sites-batch-status-string', 'Inside Batch ' . $page, 'no' );
				$process->import_sites( $page );
			} elseif ( 'import_blocks' === $method ) {
				astra_sites_error_log( '-------- Importing Blocks --------' );
				$page = $object['page'];
				astra_sites_error_log( 'Inside Batch ' . $page );
				update_site_option( 'astra-sites-batch-status-string', 'Inside Batch ' . $page, 'no' );
				$process->import_blocks( $page );
			} elseif ( 'import_block_categories' === $method ) {
				astra_sites_error_log( '-------- Importing Blocks Categories --------' );
				update_site_option( 'astra-sites-batch-status-string', 'Importing Blocks Categories', 'no' );
				$process->import_block_categories();
			} elseif ( 'import_site_categories' === $method ) {
				astra_sites_error_log( '-------- Importing Site Categories --------' );
				update_site_option( 'astra-sites-batch-status-string', 'Importing Site Categories', 'no' );
				$process->import_site_categories();
			}

			return false;
		}

		/**
		 * Complete
		 *
		 * Override if applicable, but ensure that the below actions are
		 * performed, or, call parent::complete().
		 *
		 * @since 2.0.0
		 */
		protected function complete() {
			parent::complete();

			astra_sites_error_log( esc_html__( 'All processes are complete', 'astra-sites' ) );
			update_site_option( 'astra-sites-batch-status-string', 'All processes are complete', 'no' );
			delete_site_option( 'astra-sites-batch-status' );
			update_site_option( 'astra-sites-batch-is-complete', 'yes', 'no' );

			do_action( 'astra_sites_site_import_batch_complete' );
		}

	}

endif;
