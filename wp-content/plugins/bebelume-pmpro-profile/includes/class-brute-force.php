<?php
/**
 * BBL_Brute_Force — Proteção contra tentativas repetidas de login
 *
 * Bloqueia o IP temporariamente após 5 tentativas falhas em 15 minutos.
 * Usa transients do WordPress — sem dependência externa.
 *
 * @package Bebelume_PMPro_Profile
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class BBL_Brute_Force {

    const MAX_ATTEMPTS  = 5;
    const LOCKOUT_TIME  = 15 * MINUTE_IN_SECONDS;
    const ATTEMPT_WINDOW = 15 * MINUTE_IN_SECONDS;

    private static $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_filter( 'authenticate',      [ $this, 'check_lockout' ], 1, 3 );
        add_action( 'wp_login_failed',   [ $this, 'record_attempt' ] );
        add_action( 'wp_login',          [ $this, 'clear_attempts' ], 10, 2 );
    }

    private function get_ip(): string {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    private function key(): string {
        return 'bbl_bf_' . md5( $this->get_ip() );
    }

    public function check_lockout( $user, string $username, string $password ) {
        if ( empty( $username ) || empty( $password ) ) return $user;

        $data = get_transient( $this->key() );
        if ( ! $data ) return $user;

        if ( $data['attempts'] >= self::MAX_ATTEMPTS ) {
            $remaining = ceil( ( $data['lockout_until'] - time() ) / 60 );
            return new WP_Error(
                'bbl_locked_out',
                sprintf(
                    '<strong>Erro:</strong> Muitas tentativas incorretas. Tente novamente em %d minuto(s).',
                    max( 1, $remaining )
                )
            );
        }

        return $user;
    }

    public function record_attempt( string $username ): void {
        $key  = $this->key();
        $data = get_transient( $key ) ?: [ 'attempts' => 0, 'lockout_until' => 0 ];

        $data['attempts']++;
        $data['lockout_until'] = time() + self::LOCKOUT_TIME;

        set_transient( $key, $data, self::ATTEMPT_WINDOW );
    }

    public function clear_attempts( string $user_login, WP_User $user ): void {
        delete_transient( $this->key() );
    }
}

add_action( 'plugins_loaded', function () {
    BBL_Brute_Force::get_instance();
} );
