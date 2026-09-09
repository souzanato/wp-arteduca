<?php
/**
 * Integração com Paid Memberships Pro
 * 
 * NOTA: Templates PMPro e shortcodes foram desativados para evitar
 * conflito com o plugin bebelume-pmpro-profile-v2, que é responsável
 * por essas funcionalidades.
 * 
 * Mantido apenas: geração de username aleatório no checkout.
 *
 * @package Bebelume_Profile
 */

if (!defined('ABSPATH')) {
    exit;
}

class Bebelume_PMPro_Integration {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        if (!function_exists('pmpro_hasMembershipLevel')) {
            return;
        }
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Gerar username aleatório no checkout
        add_action('init', array($this, 'generate_random_username'), 1);
    }
    
    /**
     * Gera username aleatório: user-{hash8digitos}
     */
    public function generate_random_username() {
        if (is_user_logged_in()) return;
        if (empty($_REQUEST['submit-checkout']) && empty($_POST['submit-checkout'])) return;
        if (!empty($_REQUEST['username']) || !empty($_POST['username']) || !empty($GLOBALS['username'])) return;

        $hash = substr(md5(uniqid(rand(), true)), 0, 8);
        $username = 'user-' . $hash;

        while (username_exists($username)) {
            $hash = substr(md5(uniqid(rand(), true)), 0, 8);
            $username = 'user-' . $hash;
        }

        $_POST['username']    = $username;
        $_REQUEST['username'] = $username;
        $GLOBALS['username']  = $username;
    }
}

function bebelume_pmpro_integration_init() {
    return Bebelume_PMPro_Integration::get_instance();
}
add_action('plugins_loaded', 'bebelume_pmpro_integration_init', 5);
