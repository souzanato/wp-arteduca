<?php
/**
 * Bebelume_NFSe_DB
 * Gerencia a tabela wp_bebelume_nfse que armazena o log de todas as notas.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Bebelume_NFSe_DB {

    // Colunas:
    // id            INT PK AUTO
    // order_id      INT          — ID do pedido PMPro
    // user_id       INT          — ID do usuário WP
    // searchkey     VARCHAR(100) — chave retornada pela TransmiteNota
    // status        VARCHAR(30)  — pendente | aprovada | reprovada | cancelada | erro
    // numero_nfse   VARCHAR(30)  — número da nota (após aprovação)
    // link_pdf      TEXT
    // link_xml      TEXT
    // valor         DECIMAL(10,2)
    // payload       LONGTEXT     — JSON enviado (para reemissão/debug)
    // resposta      LONGTEXT     — JSON retornado
    // created_at    DATETIME
    // updated_at    DATETIME

    public static function create_table(): void {
        global $wpdb;

        $table   = $wpdb->prefix . BBL_NFSE_TABLE;
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id            BIGINT(20)    UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id      BIGINT(20)    UNSIGNED NOT NULL DEFAULT 0,
            user_id       BIGINT(20)    UNSIGNED NOT NULL DEFAULT 0,
            searchkey     VARCHAR(120)  NOT NULL DEFAULT '',
            status        VARCHAR(30)   NOT NULL DEFAULT 'pendente',
            numero_nfse   VARCHAR(30)   NOT NULL DEFAULT '',
            link_pdf      TEXT          NOT NULL,
            link_xml      TEXT          NOT NULL,
            valor         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            payload       LONGTEXT      NOT NULL,
            resposta      LONGTEXT      NOT NULL,
            created_at    DATETIME      NOT NULL,
            updated_at    DATETIME      NOT NULL,
            PRIMARY KEY (id),
            KEY order_id  (order_id),
            KEY user_id   (user_id),
            KEY status    (status),
            KEY searchkey (searchkey)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Insere um registro de nota no banco.
     */
    public static function insert( array $data ): int|false {
        global $wpdb;

        $now = current_time( 'mysql' );

        // created_at/updated_at entram em $data ANTES de calcular os formatos.
        // Do contrário o $wpdb alinha os placeholders por posição e os dois
        // campos de data caem em %d (gravava "2026" no lugar do datetime), o que
        // deixava created_at=0000 e o cron de finalização nunca achava a nota.
        $data = array_merge( $data, [
            'created_at' => $now,
            'updated_at' => $now,
        ] );

        $wpdb->insert(
            $wpdb->prefix . BBL_NFSE_TABLE,
            $data,
            self::get_formats( $data )
        );

        return $wpdb->insert_id ?: false;
    }

    /**
     * Atualiza um registro pelo ID.
     */
    public static function update( int $id, array $data ): void {
        global $wpdb;

        $data['updated_at'] = current_time( 'mysql' );

        $wpdb->update(
            $wpdb->prefix . BBL_NFSE_TABLE,
            $data,
            [ 'id' => $id ],
            self::get_formats( $data ),
            [ '%d' ]
        );
    }

    /**
     * Busca nota pelo ID interno.
     */
    public static function get( int $id ): ?object {
        global $wpdb;
        $table = $wpdb->prefix . BBL_NFSE_TABLE;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
    }

    /**
     * Busca nota pelo order_id do PMPro.
     */
    public static function get_by_order( int $order_id ): ?object {
        global $wpdb;
        $table = $wpdb->prefix . BBL_NFSE_TABLE;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE order_id = %d ORDER BY id DESC LIMIT 1", $order_id ) );
    }

    /**
     * Lista todas as notas com filtros opcionais (para a tela admin).
     */
    public static function list( array $args = [] ): array {
        global $wpdb;
        $table = $wpdb->prefix . BBL_NFSE_TABLE;

        $where  = '1=1';
        $values = [];

        if ( ! empty( $args['status'] ) ) {
            $where   .= ' AND status = %s';
            $values[] = $args['status'];
        }
        if ( ! empty( $args['user_id'] ) ) {
            $where   .= ' AND user_id = %d';
            $values[] = (int) $args['user_id'];
        }

        $limit  = isset( $args['limit'] ) ? (int) $args['limit'] : 50;
        $offset = isset( $args['offset'] ) ? (int) $args['offset'] : 0;

        $sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT %d OFFSET %d";
        $values[] = $limit;
        $values[] = $offset;

        return $wpdb->get_results( $wpdb->prepare( $sql, $values ) ) ?: [];
    }

    /**
     * Conta total de notas com filtros.
     */
    public static function count( array $args = [] ): int {
        global $wpdb;
        $table = $wpdb->prefix . BBL_NFSE_TABLE;

        $where  = '1=1';
        $values = [];

        if ( ! empty( $args['status'] ) ) {
            $where   .= ' AND status = %s';
            $values[] = $args['status'];
        }

        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        return (int) ( $values ? $wpdb->get_var( $wpdb->prepare( $sql, $values ) ) : $wpdb->get_var( $sql ) );
    }

    /**
     * Retorna todas as notas com status "pendente" (para o cron checar).
     */
    public static function get_pending(): array {
        global $wpdb;
        $table = $wpdb->prefix . BBL_NFSE_TABLE;

        // Só consulta notas dos últimos N dias. Sem isso, uma nota travada em
        // "pendente" era consultada de hora em hora para sempre (1 request de
        // até 30s cada). O botão "Atualizar" no admin continua funcionando para
        // notas antigas, independente deste filtro.
        $dias = (int) apply_filters( 'bbl_nfse_dias_consulta_pendente', 7 );

        $sql = $wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE status IN ('pendente','processando')
               AND searchkey != ''
               AND created_at >= DATE_SUB( NOW(), INTERVAL %d DAY )
             ORDER BY id ASC
             LIMIT 100",
            $dias
        );

        return $wpdb->get_results( $sql ) ?: [];
    }

    // -------------------------------------------------------------------------

    private static function get_formats( array $data ): array {
        $map = [
            'order_id'    => '%d',
            'user_id'     => '%d',
            'searchkey'   => '%s',
            'status'      => '%s',
            'numero_nfse' => '%s',
            'link_pdf'    => '%s',
            'link_xml'    => '%s',
            'valor'       => '%f',
            'payload'     => '%s',
            'resposta'    => '%s',
            'created_at'  => '%s',
            'updated_at'  => '%s',
        ];

        $formats = [];
        foreach ( array_keys( $data ) as $col ) {
            $formats[] = $map[ $col ] ?? '%s';
        }
        return $formats;
    }
}
