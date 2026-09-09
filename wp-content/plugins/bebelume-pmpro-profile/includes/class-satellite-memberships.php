<?php
/**
 * BBL_Satellite_Memberships
 * Busca assinaturas nos satélites via API REST do PMPro.
 * Endpoint: GET /wp-json/pmpro/v1/get_membership_levels_for_user?user_id=X
 * Autenticação: Application Password
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class BBL_Satellite_Memberships {

    private static function satellites(): array {
        return [
            'canal' => [
                'label'    => 'Canal Bebelume',
                'url'      => 'https://canal.bebelume.com.br',
                'wp_user'  => defined( 'BBL_CANAL_WP_USER' )  ? BBL_CANAL_WP_USER  : '',
                'app_pass' => defined( 'BBL_CANAL_APP_PASS' )  ? BBL_CANAL_APP_PASS  : '',
            ],
            'arteduca' => [
                'label'    => 'ArtEduca',
                'url'      => 'https://arteduca.bebelume.com.br',
                'wp_user'  => defined( 'BBL_ARTEDUCA_WP_USER' ) ? BBL_ARTEDUCA_WP_USER : '',
                'app_pass' => defined( 'BBL_ARTEDUCA_APP_PASS' ) ? BBL_ARTEDUCA_APP_PASS : '',
            ],
        ];
    }

    /**
     * Busca TODAS as assinaturas (ativas e inativas) de todos os satélites.
     * API retorna apenas ativas — inativas buscamos via MySQL.
     */
    public static function get_memberships( int $user_id ): array {
        $all = [];

        foreach ( self::satellites() as $key => $sat ) {
            if ( empty( $sat['app_pass'] ) ) continue;

            // 1. Ativas via API PMPro
            $url = trailingslashit( $sat['url'] ) . 'wp-json/pmpro/v1/get_membership_levels_for_user';
            $url = add_query_arg( 'user_id', $user_id, $url );

            $response = wp_remote_get( $url, [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode( $sat['wp_user'] . ':' . $sat['app_pass'] ),
                ],
                'timeout'   => 8,
                'sslverify' => true,
            ] );

            $active_ids = [];

            if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
                $data = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( is_array( $data ) ) {
                    foreach ( $data as $m ) {
                        $m['status']           = $m['status'] ?? 'active';
                        $m['_satellite_key']   = $key;
                        $m['_satellite_label'] = $sat['label'];
                        $m['_satellite_url']   = $sat['url'];
                        $all[] = $m;
                        $active_ids[] = intval( $m['ID'] ?? $m['id'] ?? 0 );
                    }
                }
            }

            // 2. Inativas via MySQL (histórico)
            $db_name = self::satellite_dbs()[ $key ] ?? '';
            if ( ! $db_name ) continue;

            $conn = mysqli_connect( DB_HOST, DB_USER, DB_PASSWORD, $db_name );
            if ( ! $conn ) continue;

            $uid    = intval( $user_id );
            $result = mysqli_query( $conn, "
                SELECT mu.membership_id AS ID, mu.membership_id AS id,
                       ml.name, ml.billing_amount, ml.cycle_period,
                       mu.startdate, mu.enddate, mu.status
                FROM wp_pmpro_memberships_users mu
                INNER JOIN wp_pmpro_membership_levels ml ON ml.id = mu.membership_id
                WHERE mu.user_id = {$uid}
                  AND mu.status != 'active'
                ORDER BY mu.startdate DESC
            " );

            if ( $result ) {
                while ( $row = mysqli_fetch_assoc( $result ) ) {
                    if ( in_array( intval( $row['ID'] ), $active_ids, true ) ) continue;
                    $row['_satellite_key']   = $key;
                    $row['_satellite_label'] = $sat['label'];
                    $row['_satellite_url']   = $sat['url'];
                    $all[] = $row;
                }
                mysqli_free_result( $result );
            }

            mysqli_close( $conn );
        }

        return $all;
    }

    /**
     * Mapa de banco de dados por satélite.
     * API do PMPro não tem endpoint de orders — acesso direto via MySQL.
     */
    private static function satellite_dbs(): array {
        return [
            'canal'    => 'wp_canal',
            'arteduca' => 'wp_arteduca',
        ];
    }

    public static function get_orders( int $user_id, string $satellite_key, int $limit = 5 ): array {
        $dbs = self::satellite_dbs();
        if ( ! isset( $dbs[ $satellite_key ] ) ) return [];

        $db_name = $dbs[ $satellite_key ];
        $conn    = mysqli_connect( DB_HOST, DB_USER, DB_PASSWORD, $db_name );
        if ( ! $conn ) return [];

        $uid    = intval( $user_id );
        $limit  = intval( $limit );
        $result = mysqli_query( $conn, "
            SELECT o.id, o.code, o.timestamp, o.total, o.status,
                   ml.name AS level_name
            FROM wp_pmpro_membership_orders o
            LEFT JOIN wp_pmpro_membership_levels ml ON ml.id = o.membership_id
            WHERE o.user_id = {$uid} AND o.status != 'error'
            ORDER BY o.timestamp DESC
            LIMIT {$limit}
        " );

        $orders = [];
        if ( $result ) {
            while ( $row = mysqli_fetch_assoc( $result ) ) {
                $orders[] = $row;
            }
            mysqli_free_result( $result );
        }

        mysqli_close( $conn );
        return $orders;
    }
}
