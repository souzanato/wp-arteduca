<?php
/**
 * Plugin Name: Bebelume Stripe Meta
 * Description: Passa fbc, fbp e site_source nos metadata da sessão Stripe para maximizar o Match Quality da Meta CAPI.
 * Version:     1.1.0
 * Author:      Bebelume
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'pmpro_stripe_checkout_session_parameters', function( $params, $order, $customer ) {
    // fbc — prioridade: cookie nativo Meta > cookie formatado plugin > user_meta (SSO)
    $fbc = '';
    if ( ! empty( $_COOKIE['_fbc'] ) ) {
        $fbc = sanitize_text_field( wp_unslash( $_COOKIE['_fbc'] ) );
    } elseif ( ! empty( $_COOKIE['bebelume_fbc'] ) ) {
        $fbc = sanitize_text_field( wp_unslash( $_COOKIE['bebelume_fbc'] ) );
    } elseif ( ! empty( $order->user_id ) ) {
        $fbc = get_user_meta( $order->user_id, 'bebelume_fbc', true ) ?: '';
    }

    // fbp — prioridade: cookie nativo Meta > cookie formatado plugin > user_meta (SSO)
    $fbp = '';
    if ( ! empty( $_COOKIE['_fbp'] ) ) {
        $fbp = sanitize_text_field( wp_unslash( $_COOKIE['_fbp'] ) );
    } elseif ( ! empty( $_COOKIE['bebelume_fbp'] ) ) {
        $fbp = sanitize_text_field( wp_unslash( $_COOKIE['bebelume_fbp'] ) );
    } elseif ( ! empty( $order->user_id ) ) {
        $fbp = get_user_meta( $order->user_id, 'bebelume_fbp', true ) ?: '';
    }

    $site_source = sanitize_text_field( parse_url( home_url(), PHP_URL_HOST ) );

    $params['metadata'] = array_merge( $params['metadata'] ?? [], array_filter( [
        'fbc'         => $fbc,
        'fbp'         => $fbp,
        'site_source' => $site_source,
    ] ) );

    return $params;
}, 10, 3 );
