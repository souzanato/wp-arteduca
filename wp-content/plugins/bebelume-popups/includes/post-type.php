<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function bpp_register_post_type() {
    register_post_type( 'bpp_popup', [
        'labels' => [
            'name'          => 'Popups',
            'singular_name' => 'Popup',
        ],
        'public'       => false,
        'show_ui'      => false,
        'show_in_menu' => false,
        'supports'     => [ 'title' ],
    ]);
}
add_action( 'init', 'bpp_register_post_type' );
