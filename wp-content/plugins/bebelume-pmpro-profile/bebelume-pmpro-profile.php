<?php
/**
 * Plugin Name: Bebelume PMPro Profile
 * Description: Customizações de autenticação, perfil e PMPro para o ecossistema Bebelume.
 * Version:     1.0.0
 * Author:      Bebelume
 * License:     GPL v2 or later
 * Text Domain: bebelume-pmpro-profile
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'BBL_PMPro_VERSION',  '2.6.1' );
define( 'BBL_PMPro_DIR',      plugin_dir_path( __FILE__ ) );
define( 'BBL_PMPro_URL',      plugin_dir_url( __FILE__ ) );
define( 'BBL_PMPro_BASENAME', plugin_basename( __FILE__ ) );

// Módulos — carregados conforme existirem
$modules = [
    'includes/class-auth.php',
    'includes/class-register.php',
    'includes/class-google-auth.php',
    'includes/class-complete-profile.php',
    'includes/class-email-confirmation.php',
    'includes/class-pmpro-integration.php',
    'includes/class-checkout-fields.php',
    'includes/class-checkout.php',
    'includes/class-checkout-processing.php',
    'includes/class-brute-force.php',
    'includes/class-expired-link.php',
    'includes/class-google-password.php',
    'includes/class-emails.php',
    'includes/class-satellite-memberships.php',
    'includes/class-n8n-webhook.php',
    'includes/class-fiscal-block.php',
    'includes/class-cancel-at-period-end.php',
    'includes/class-prevent-duplicate-subscription.php',
    'includes/class-billing-failure.php',
    'includes/class-cancel-notice.php',
    'includes/class-pmpro-emails.php',
];

// Exibe erros do Google na tela de login
add_filter( 'login_errors', function ( $errors ) {
    if ( ! empty( $_GET['bbl_error'] ) ) {
        $msg = sanitize_text_field( urldecode( $_GET['bbl_error'] ) );
        $errors .= '<br>' . esc_html( $msg );
    }
    return $errors;
} );

foreach ( $modules as $module ) {
    $path = BBL_PMPro_DIR . $module;
    if ( file_exists( $path ) ) {
        require_once $path;
    }
}

// Enfileira CSS PMPro no frontend
add_action( 'wp_enqueue_scripts', function () {
    if ( function_exists( 'pmpro_url' ) ) {
        wp_enqueue_style( 'bbl-fonts', 'https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap', [], null );
    }
} );

// Lembrar-me: estende a sessão para 30 dias quando marcado
add_filter( 'auth_cookie_expiration', function ( int $expiration, int $user_id, bool $remember ): int {
    return $remember ? 30 * DAY_IN_SECONDS : $expiration;
}, 10, 3 );

// ── Handler AJAX: alterar senha ──────────────────────────────────────────────
add_action( 'wp_ajax_bbl_change_password', 'bbl_ajax_change_password' );
function bbl_ajax_change_password(): void {
    check_ajax_referer( 'bbl_change_password', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Você precisa estar logado.' );
    }

    $new_password = isset( $_POST['new_password'] ) ? $_POST['new_password'] : '';

    if ( empty( $new_password ) ) {
        wp_send_json_error( 'A nova senha não pode estar vazia.' );
    }

    if ( strlen( $new_password ) < 6 ) {
        wp_send_json_error( 'A senha deve ter pelo menos 6 caracteres.' );
    }

    $user_id = get_current_user_id();
    wp_set_password( $new_password, $user_id );

    // Mantém o usuário logado após trocar a senha
    $user = get_user_by( 'id', $user_id );
    wp_set_auth_cookie( $user_id, true );

    wp_send_json_success( 'Senha alterada com sucesso!' );
}
