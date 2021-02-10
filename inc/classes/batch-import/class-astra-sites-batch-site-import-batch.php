<?php
/**
 * Complete Site Import Batch Process
 *
 * @package Astra Sites
 * @since 2.5.0
 */

if ( ! class_exists( 'Astra_Sites_Batch_Site_Import_Batch' ) && class_exists( 'WP_Background_Process' ) ) :

	/**
	 * Site Import Batch
	 *
	 * @since 2.5.0
	 */
	class Astra_Sites_Batch_Site_Import_Batch extends WP_Background_Process {

		/**
		 * Batch Action
		 *
		 * @var string
		 */
		protected $action = 'astra_sites_site_import_batch';

		/**
		 * Task
		 *
		 * Override this method to perform any actions required on each
		 * queue item. Return the modified item for further processing
		 * in the next pass through. Or, return false to remove the
		 * item from the queue.
		 *
		 * @since 2.5.0
		 *
		 * @param array $data Queue item object.
		 * @return mixed
		 */
		protected function task( $data ) {

			$instance = $data['instance'];

			if ( 'import_queue_1' === $data['method'] ) {
				$instance->import_queue_1( $data['args'] );
			}

			if ( 'import_queue_2' === $data['method'] ) {
				$instance->import_queue_2( $data['args'] );
			}

			if ( 'import_queue_3' === $data['method'] ) {
				$instance->import_queue_3( $data['args'] );
			}

			return false;
		}

	}

endif;
