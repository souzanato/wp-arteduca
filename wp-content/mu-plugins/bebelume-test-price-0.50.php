<?php
/**
 * Plugin Name: Bebelume TESTE — Preço R$0,50 checkout nível 3 (hylozero + souzanato84)
 * Description: TEMPORÁRIO. Força billing_amount=0.50 no checkout do nível 3 APENAS para as
 *              contas de teste (hylozero / hylozero@gmail.com, user 186 e
 *              user-6f60fbd8 / souzanato84@gmail.com, user 188). Nenhum outro usuário/
 *              visitante/nível é afetado. REMOVER após o teste (encerramento).
 * Author: Bebelume (test harness)
 * Version: 0.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'pmpro_checkout_level', function ( $level ) {
    if ( ! is_object( $level ) || empty( $level->id ) ) {
        return $level;
    }
    if ( (int) $level->id !== 3 ) {
        return $level;
    }

    // Guard de escopo por (login + e-mail) exatos — robusto a ID recriado. Contas de teste:
    //   hylozero        / hylozero@gmail.com      (user 186)
    //   user-6f60fbd8   / souzanato84@gmail.com   (user 188, teste Trilha 2)
    $user = wp_get_current_user();
    if ( ! $user instanceof WP_User || empty( $user->user_login ) || empty( $user->user_email ) ) {
        return $level;
    }
    $perm = [
        [ 'hylozero', 'hylozero@gmail.com' ],
        [ 'user-6f60fbd8', 'souzanato84@gmail.com' ],
    ];
    $allowed = false;
    foreach ( $perm as $p ) {
        if ( $user->user_login === $p[0] && strtolower( trim( $user->user_email ) ) === $p[1] ) {
            $allowed = true;
            break;
        }
    }
    if ( ! $allowed ) {
        return $level;
    }

    $level->billing_amount = 0.50;

    return $level;
}, 10, 1 );
