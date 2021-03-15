### How to use? ###
 
@see https://github.com/brainstormforce/astra-sites/blob/3c42ceeeb466a2f4e7656ba0d5b43a8a9909e6fd/inc/classes/class-astra-sites.php#L143

if ( ( isset( $_REQUEST['page'] ) && 'plugin_settings_page_name' === $_REQUEST['page'] ) ) {
    add_action( 'admin_footer', array( $this, 'add_quick_links' ) );
}

public function add_quick_links() {
    bsf_quick_links(
        'default_logo' => array(
            'title' => '', //title on logo hover.
            'url'   => '',
            ),
        'links'        => array(
            array('label' => '','icon' => '','url' => ''),
            array('label' => '','icon' => '','url' => ''),
            array('label' => '','icon' => '','url' => ''),
            ...
        )
    )
}