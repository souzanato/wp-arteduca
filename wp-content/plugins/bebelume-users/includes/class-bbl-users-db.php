<?php
/**
 * Tabela de rascunho.
 *
 * Fica no banco do site onde o plugin está instalado (local ao satélite),
 * mesmo quando wp_users é uma VIEW compartilhada com o hub.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BBL_Users_DB {

	const DB_VERSION = '1.0.0';

	/** Rascunho salvo, ainda não virou usuário. */
	const STATUS_RASCUNHO = 'rascunho';
	/** Usuário criado com sucesso nesta rodada. */
	const STATUS_CRIADO = 'criado';
	/** O e-mail já tinha conta; o nível foi atribuído à conta existente. */
	const STATUS_EXISTENTE = 'existente';
	/** Falhou. O motivo fica na coluna erro. */
	const STATUS_ERRO = 'erro';

	/**
	 * Nome da tabela com prefixo do site.
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'bebelume_users_draft';
	}

	/**
	 * Cria a tabela na ativação.
	 */
	public static function install() {
		global $wpdb;

		$table   = self::table();
		$collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			nome VARCHAR(190) NOT NULL DEFAULT '',
			email VARCHAR(190) NOT NULL DEFAULT '',
			status VARCHAR(20) NOT NULL DEFAULT 'rascunho',
			membership_level INT NOT NULL DEFAULT 0,
			user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			username VARCHAR(60) NOT NULL DEFAULT '',
			email_enviado TINYINT(1) NOT NULL DEFAULT 0,
			erro TEXT NULL,
			criado_em DATETIME NOT NULL,
			processado_em DATETIME NULL,
			PRIMARY KEY (id),
			KEY email (email),
			KEY status (status)
		) {$collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'bbl_users_db_version', self::DB_VERSION, false );
	}

	/**
	 * Roda o install se a versão da tabela estiver defasada.
	 */
	public static function maybe_upgrade() {
		if ( get_option( 'bbl_users_db_version' ) !== self::DB_VERSION ) {
			self::install();
		}
	}

	/**
	 * Insere uma linha de rascunho.
	 *
	 * @return int|false ID da linha ou false.
	 */
	public static function add( $nome, $email ) {
		global $wpdb;

		$ok = $wpdb->insert( // phpcs:ignore WordPress.DB
			self::table(),
			array(
				'nome'      => $nome,
				'email'     => $email,
				'status'    => self::STATUS_RASCUNHO,
				'criado_em' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s' )
		);

		return $ok ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Busca uma linha pelo ID.
	 */
	public static function get( $id ) {
		global $wpdb;

		return $wpdb->get_row( // phpcs:ignore WordPress.DB
			$wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ),
			ARRAY_A
		);
	}

	/**
	 * Lista linhas, opcionalmente filtrando por status.
	 *
	 * @param string|array $status Status ou lista de status. Vazio traz tudo.
	 */
	public static function get_all( $status = '' ) {
		global $wpdb;

		$table = self::table();

		if ( empty( $status ) ) {
			return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id ASC", ARRAY_A ); // phpcs:ignore WordPress.DB
		}

		$status      = (array) $status;
		$placeholders = implode( ',', array_fill( 0, count( $status ), '%s' ) );

		return $wpdb->get_results( // phpcs:ignore WordPress.DB
			$wpdb->prepare( "SELECT * FROM {$table} WHERE status IN ({$placeholders}) ORDER BY id ASC", $status ),
			ARRAY_A
		);
	}

	/**
	 * IDs das linhas ainda em rascunho.
	 */
	public static function get_pending_ids() {
		global $wpdb;

		$ids = $wpdb->get_col( // phpcs:ignore WordPress.DB
			$wpdb->prepare(
				'SELECT id FROM ' . self::table() . ' WHERE status = %s ORDER BY id ASC',
				self::STATUS_RASCUNHO
			)
		);

		return array_map( 'intval', $ids );
	}

	/**
	 * Conta linhas por status.
	 */
	public static function count_by_status() {
		global $wpdb;

		$rows = $wpdb->get_results( 'SELECT status, COUNT(*) AS total FROM ' . self::table() . ' GROUP BY status', ARRAY_A ); // phpcs:ignore WordPress.DB

		$out = array(
			self::STATUS_RASCUNHO  => 0,
			self::STATUS_CRIADO    => 0,
			self::STATUS_EXISTENTE => 0,
			self::STATUS_ERRO      => 0,
		);

		foreach ( (array) $rows as $row ) {
			$out[ $row['status'] ] = (int) $row['total'];
		}

		return $out;
	}

	/**
	 * Atualiza campos de uma linha.
	 */
	public static function update( $id, $data ) {
		global $wpdb;

		$allowed = array( 'nome', 'email', 'status', 'membership_level', 'user_id', 'username', 'email_enviado', 'erro', 'processado_em' );
		$data    = array_intersect_key( $data, array_flip( $allowed ) );

		if ( empty( $data ) ) {
			return false;
		}

		return $wpdb->update( self::table(), $data, array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Remove uma linha.
	 */
	public static function delete( $id ) {
		global $wpdb;
		return $wpdb->delete( self::table(), array( 'id' => (int) $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Remove todas as linhas de um status.
	 */
	public static function delete_by_status( $status ) {
		global $wpdb;
		return $wpdb->delete( self::table(), array( 'status' => $status ), array( '%s' ) ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Verifica se o e-mail já está no rascunho (evita duplicata na própria lista).
	 */
	public static function email_in_draft( $email, $ignore_id = 0 ) {
		global $wpdb;

		$id = $wpdb->get_var( // phpcs:ignore WordPress.DB
			$wpdb->prepare(
				'SELECT id FROM ' . self::table() . ' WHERE email = %s AND id != %d LIMIT 1',
				$email,
				(int) $ignore_id
			)
		);

		return (int) $id > 0;
	}
}
