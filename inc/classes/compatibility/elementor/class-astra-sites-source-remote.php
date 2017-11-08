<?php
/**
 * Elementor Images
 *
 * @package Astra Sites
 * @since 1.0.11
 */

namespace Elementor;

// If plugin - 'Elementor' not exist then return.
if ( ! class_exists( '\Elementor\Plugin' ) ) {
	return;
}

namespace Elementor\TemplateLibrary;

use Elementor\Core\Settings\Manager as SettingsManager;
use Elementor\TemplateLibrary\Classes\Import_Images;
use Elementor\TemplateLibrary;
use Elementor\TemplateLibrary\Classes;
use Elementor\Api;
use Elementor\PageSettings\Page;

// For working protected methods defined in.
// file '/elementor/includes/template-library/sources/base.php'.
use Elementor\Plugin;
use Elementor\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Astra Source Remote
 */
class Astra_Sites_Source_Remote extends Source_Base {

	/**
	 * Get ID
	 *
	 * @since 1.0.4
	 *
	 * @return string
	 */
	public function get_id() {
		return 'remote';
	}

	/**
	 * Get Title.
	 *
	 * @since 1.0.4
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Remote', 'astra-sites' );
	}

	/**
	 * Get Data
	 *
	 * @since 1.0.4
	 *
	 * @return void
	 */
	public function register_data() {}

	/**
	 * Get Items
	 *
	 * @since 1.0.4
	 *
	 * @param  array $args Arguments.
	 * @return array
	 */
	public function get_items( $args = array() ) {
		$templates_data = Api::get_templates_data();

		$templates = array();

		if ( ! empty( $templates_data ) ) {
			foreach ( $templates_data as $template_data ) {
				$templates[] = $this->get_item( $template_data );
			}
		}

		if ( ! empty( $args ) ) {
			$templates = wp_list_filter( $templates, $args );
		}

		return $templates;
	}

	/**
	 * Get Item
	 *
	 * @param array $template_data Template Data.
	 *
	 * @return array
	 */
	public function get_item( $template_data ) {
		return array(
			'template_id'     => $template_data['id'],
			'source'          => $this->get_id(),
			'title'           => $template_data['title'],
			'thumbnail'       => $template_data['thumbnail'],
			'date'            => date( get_option( 'date_format' ), $template_data['tmpl_created'] ),
			'author'          => $template_data['author'],
			'categories'      => array(),
			'keywords'        => array(),
			'isPro'           => ( '1' === $template_data['is_pro'] ),
			'hasPageSettings' => ( '1' === $template_data['has_page_settings'] ),
			'url'             => $template_data['url'],
		);
	}

	/**
	 * Template Data
	 *
	 * @param  boolean $template_data Template Data.
	 * @return boolean                Return false.
	 */
	public function save_item( $template_data ) {
		return false;
	}

	/**
	 * Update Item
	 *
	 * @param  boolean $new_data New Data.
	 * @return boolean                Return false.
	 */
	public function update_item( $new_data ) {
		return false;
	}

	/**
	 * Delete Template
	 *
	 * @param  boolean $template_id Template ID.
	 * @return boolean                Return false.
	 */
	public function delete_template( $template_id ) {
		return false;
	}

	/**
	 * Delete Template
	 *
	 * @param  boolean $template_id Template ID.
	 * @return boolean                Return false.
	 */
	public function export_template( $template_id ) {
		return false;
	}

	/**
	 * Get Data
	 *
	 * @param  array  $args    Arguments.
	 * @param  string $context Context.
	 * @return array          Data.
	 */
	public function get_data( array $args, $context = 'display' ) {
		$data = Api::get_template_content( $args['template_id'] );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		// TODO: since 1.5.0 to content container named `content` instead of `data`.
		if ( ! empty( $data['data'] ) ) {
			$data['content'] = $data['data'];
			unset( $data['data'] );
		}

		$data['content'] = $this->replace_elements_ids( $data['content'] );
		$data['content'] = $this->process_export_import_content( $data['content'], 'on_import' );

		if ( ! empty( $args['page_settings'] ) && ! empty( $data['page_settings'] ) ) {
			$page = new Page(
				array(
					'settings' => $data['page_settings'],
				)
			);

			$page_settings_data    = $this->process_element_export_import_content( $page, 'on_import' );
			$data['page_settings'] = $page_settings_data['settings'];
		}

		return $data;
	}

	/**
	 * Replace Elements Ids
	 *
	 * @param  string $content Context.
	 * @return array    Element.
	 */
	public function replace_elements_ids( $content ) {
		return Plugin::$instance->db->iterate_data(
			$content, function( $element ) {
				$element['id'] = Utils::generate_random_string();

				return $element;
			}
		);
	}

	/**
	 * Process Import Content.
	 *
	 * @param array  $content a set of elements.
	 * @param string $method  (on_export|on_import).
	 *
	 * @return mixed
	 */
	public function process_export_import_content( $content, $method ) {
		return Plugin::$instance->db->iterate_data(
			$content, function( $element_data ) use ( $method ) {
				$element = Plugin::$instance->elements_manager->create_element_instance( $element_data );

				// If the widget/element isn't exist, like a plugin that creates a widget but deactivated.
				if ( ! $element ) {
					return null;
				}

				return $this->process_element_export_import_content( $element, $method );
			}
		);
	}

	/**
	 * Process Element/Export Import Content.
	 *
	 * @param \Elementor\Controls_Stack $element Element.
	 * @param string                    $method Method.
	 *
	 * @return array
	 */
	public function process_element_export_import_content( $element, $method ) {
		$element_data = $element->get_data();

		if ( method_exists( $element, $method ) ) {
			// TODO: Use the internal element data without parameters.
			$element_data = $element->{$method}( $element_data );
		}

		foreach ( $element->get_controls() as $control ) {
			$control_class = Plugin::$instance->controls_manager->get_control( $control['type'] );

			// If the control isn't exist, like a plugin that creates the control but deactivated.
			if ( ! $control_class ) {
				return $element_data;
			}

			if ( method_exists( $control_class, $method ) ) {
				$element_data['settings'][ $control['name'] ] = $control_class->{$method}( $element->get_settings( $control['name'] ) );
			}
		}

		return $element_data;
	}

	/**
	 * Update post meta.
	 *
	 * @param  integer $post_id Post ID.
	 * @return void
	 */
	public function hotlink_images( $post_id = 0 ) {

		if ( ! empty( $post_id ) ) {

			$hotlink_imported = get_post_meta( $post_id, '_astra_sites_hotlink_imported', true );

			if ( empty( $hotlink_imported ) ) {

				$data = get_post_meta( $post_id, '_elementor_data', true );

				if ( ! empty( $data ) ) {

					$data = json_decode( $data, true );

					$data = $this->replace_elements_ids( $data );
					$data = $this->process_export_import_content( $data, 'on_import' );

					// Update processed meta.
					update_metadata( 'post', $post_id, '_elementor_data', $data );
					update_metadata( 'post', $post_id, '_astra_sites_hotlink_imported', true );

					// !important, Clear the cache after images import.
					Plugin::$instance->posts_css_manager->clear_cache();

				}
			}
		}

	}
}
