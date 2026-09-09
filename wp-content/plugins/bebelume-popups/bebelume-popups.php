<?php
/**
 * Plugin Name: Bebelume Popups
 * Description: Gerenciador de popups informativos com estética customizável por página/post.
 * Version: 1.0.0
 * Author: Bebelume
 * Text Domain: bebelume-popups
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'BPP_VERSION', '1.0.0' );
define( 'BPP_PATH', plugin_dir_path( __FILE__ ) );
define( 'BPP_URL', plugin_dir_url( __FILE__ ) );

require_once BPP_PATH . 'includes/post-type.php';
require_once BPP_PATH . 'includes/admin-menu.php';
require_once BPP_PATH . 'includes/meta-box.php';
require_once BPP_PATH . 'includes/frontend.php';

register_activation_hook( __FILE__, 'bpp_activate' );
function bpp_activate() {
    bpp_register_post_type();
    flush_rewrite_rules();
}
