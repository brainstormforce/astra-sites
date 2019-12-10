<?php
/**
 * Plugin Name: Brainstorm Connect
 * Description: Brainstorm Connect
 * Plugin URI: #
 * Author: Brainstorm
 * Author URI: #
 * Version: 1.0.0
 * License: GNU General Public License v2.0
 * Text Domain: brainstorm-connect
 *
 * @package Brainstorm Connect
 */

// Set constants.
define( 'BSF_CONNECT_SERVER_URL', 'http://localhost/store.brainstormforce.com/connect' );
define( 'BSF_CONNECT_VER', '1.0.0' );
define( 'BSF_CONNECT_FILE', __FILE__ );
define( 'BSF_CONNECT_BASE', plugin_basename( BSF_CONNECT_FILE ) );
define( 'BSF_CONNECT_DIR', plugin_dir_path( BSF_CONNECT_FILE ) );
define( 'BSF_CONNECT_URI', plugins_url( '/', BSF_CONNECT_FILE ) );

require_once BSF_CONNECT_DIR . 'classes/class-bsf-connect.php';