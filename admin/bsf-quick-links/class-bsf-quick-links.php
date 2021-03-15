<?php
/**
 * BSF_Quick_Links Setup.
 *
 * @see https://github.com/brainstormforce/astra-sites/blob/3c42ceeeb466a2f4e7656ba0d5b43a8a9909e6fd/inc/classes/class-astra-sites.php#L143
 *
 * => How to use?
 *
 *  if ( ( isset( $_REQUEST['page'] ) && 'plugin_settings_page_name' === $_REQUEST['page'] ) ) {
 *      add_action( 'admin_footer', array( $this, 'add_quick_links' ) );
 *  }
 *
 *  public function add_quick_links() {
 *      bsf_quick_links(
 *          'default_logo' => array(
 *               'title' => '', //title on logo hover.
 *               'url'   => '',
 *           ),
 *           'links'        => array(
 *           array('label' => '','icon' => '','url' => ''),
 *           array('label' => '','icon' => '','url' => ''),
 *           array('label' => '','icon' => '','url' => ''),
 *           ...
 *           )
 *      )
 *  }
 *
 * @since x.x.x
 * @package Astra Sites
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BSF_Quick_Links' ) ) {

	/**
	 * BSF_Quick_Links.
	 */
	class BSF_Quick_Links {
		/**
		 * BSF_Quick_Links version.
		 *
		 * @access private
		 * @var array BSF_Quick_Links.
		 * @since x.x.x
		 */
		private static $version = '1.0.0';

		/**
		 * BSF_Quick_Links
		 *
		 * @access private
		 * @var array BSF_Quick_Links.
		 * @since x.x.x
		 */

		private static $instance;

		/**
		 * Initiator
		 *
		 * @since x.x.x
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
		 * @since x.x.x
		 */
		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		/**
		 * Enqueue Scripts.
		 *
		 * @since x.x.x
		 * @return void
		 */
		public function enqueue_scripts() {
			wp_register_script( 'bsf-quick-links', self::_get_uri() . 'quicklinks.js', array( 'jquery' ), self::$version, true );
			wp_register_style( 'bsf-quick-links-css', self::_get_uri() . 'quicklink.css', array(), self::$version, 'screen' );
		}

		/**
		 * Get URI
		 *
		 * @return mixed URL.
		 */
		public static function _get_uri() { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
			$path      = wp_normalize_path( dirname( __FILE__ ) );
			$theme_dir = wp_normalize_path( get_template_directory() );

			if ( strpos( $path, $theme_dir ) !== false ) {
				return trailingslashit( get_template_directory_uri() . str_replace( $theme_dir, '', $path ) );
			} else {
				return plugin_dir_url( __FILE__ );
			}
		}
		/**
		 * Generate Quick Links Markup.
		 *
		 * @param array $data links array.
		 */
		public function generate_quick_links_markup( $data ) {

			wp_enqueue_script( 'bsf-quick-links' );
			wp_enqueue_style( 'bsf-quick-links-css' );

			?>
			<div class="bsf-quick-link-wrap">
				<label class="bsf-quick-link-title"><?php echo esc_html( $data['default_logo']['title'] ); ?></label>
				<div class="bsf-quick-link-items-wrap">
					<?php echo self::get_links_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<a href="#" class="bsf-quick-link">					
					<img src="<?php echo esc_html( $data['default_logo']['url'] ); ?>">
				</a>
			</div>
			<?php
		}
		/**
		 * Generate links markup.
		 *
		 * @param array $data links array.
		 */
		private static function get_links_html( $data ) {
			$menu_items = $data['links'];
			$items_html = '';

			foreach ( $menu_items as $item_key => $item ) {
				$items_html .= sprintf(
					'<a href="%1$s" target="_blank" rel="noopener noreferrer" class="bsf-quick-link-item bsf-quick-link-item-%4$d">
						<div class="bsf-quick-link-label">%2$s</div>
						<div class="dashicons %3$s menu-item-logo" %5$s></div>
					</a>',
					esc_url( $item['url'] ),
					esc_html( $item['label'] ),
					sanitize_html_class( $item['icon'] ),
					(int) $item_key,
					! empty( $item['bgcolor'] ) ? ' style="background-color: ' . esc_attr( $item['bgcolor'] ) . '"' : ''
				);
			}
			return $items_html;
		}
	}

	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	BSF_Quick_Links::get_instance();

}
if ( ! function_exists( 'bsf_quick_links' ) ) {
	/**
	 * Add BSF Quick Links.
	 *
	 * @param array $args links array.
	 */
	function bsf_quick_links( $args ) {
		$bsf_quick_links = new BSF_Quick_Links();
		$bsf_quick_links->generate_quick_links_markup( $args );
	}
}
