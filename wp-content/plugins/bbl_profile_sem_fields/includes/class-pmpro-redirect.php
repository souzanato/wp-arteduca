<?php
/**
 * BBL PMPro Redirect
 *
 * NOTA: Redirecionamento desativado para evitar conflito com
 * o plugin bebelume-pmpro-profile-v2, que gerencia redirecionamentos PMPro.
 *
 * @package Bebelume_Profile
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class BBL_PMPro_Redirect {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        // Redirecionamentos desativados — gerenciados pelo bebelume-pmpro-profile-v2
    }
}

BBL_PMPro_Redirect::get_instance();
