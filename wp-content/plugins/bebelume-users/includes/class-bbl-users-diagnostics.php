<?php
/**
 * Verificações de ambiente.
 *
 * Existe por causa de um detalhe do Bebelume: em canal e arteduca a
 * wp_users é uma VIEW apontando para o banco do hub. Se os GRANTs
 * cruzados não estiverem no lugar, o INSERT falha e o lote inteiro
 * morre no meio. Melhor descobrir antes de rodar.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BBL_Users_Diagnostics {

	/**
	 * Retorna a lista de checagens.
	 *
	 * @return array<int,array{label:string,status:string,detail:string}>
	 */
	public static function run() {
		global $wpdb;

		$checks = array();

		// Tabela de rascunho.
		$table  = BBL_Users_DB::table();
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table; // phpcs:ignore WordPress.DB

		$checks[] = array(
			'label'  => 'Tabela de rascunho',
			'status' => $exists ? 'ok' : 'erro',
			'detail' => $exists ? $table : 'A tabela ' . $table . ' não existe. Desative e reative o plugin.',
		);

		// wp_users é tabela ou view?
		$type = $wpdb->get_var( // phpcs:ignore WordPress.DB
			$wpdb->prepare(
				'SELECT TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s',
				$wpdb->users
			)
		);

		if ( 'VIEW' === $type ) {
			$checks[] = array(
				'label'  => 'Tabela de usuários',
				'status' => 'aviso',
				'detail' => $wpdb->users . ' é uma VIEW. Os usuários criados aqui vão para o banco do hub e aparecem nos três sites. O nível PMPro, esse sim, fica só neste site.',
			);
		} else {
			$checks[] = array(
				'label'  => 'Tabela de usuários',
				'status' => 'ok',
				'detail' => $wpdb->users . ' é uma tabela local deste banco.',
			);
		}

		// PMPro.
		if ( function_exists( 'pmpro_changeMembershipLevel' ) ) {
			$levels = self::get_levels();
			$checks[] = array(
				'label'  => 'Paid Memberships Pro',
				'status' => empty( $levels ) ? 'aviso' : 'ok',
				'detail' => empty( $levels ) ? 'Ativo, mas nenhum nível cadastrado.' : 'Ativo, com ' . count( $levels ) . ' nível(is) disponível(is).',
			);
		} else {
			$checks[] = array(
				'label'  => 'Paid Memberships Pro',
				'status' => 'aviso',
				'detail' => 'Não está ativo. Os usuários são criados normalmente, mas sem nível.',
			);
		}

		// SMTP.
		$smtp = BBL_Users_Mailer::get_smtp_options();

		if ( BBL_Users_Mailer::is_configured() ) {
			$checks[] = array(
				'label'  => 'Envio de e-mail',
				'status' => 'ok',
				'detail' => 'Configurado em ' . $smtp['username'] . ' via ' . $smtp['host'] . ':' . $smtp['port'] . '.',
			);
		} else {
			$checks[] = array(
				'label'  => 'Envio de e-mail',
				'status' => 'erro',
				'detail' => 'Sem SMTP configurado. Preencha a aba E-mail antes de criar usuários.',
			);
		}

		// OpenSSL, para a senha de app ficar cifrada no banco.
		$checks[] = array(
			'label'  => 'Criptografia das credenciais',
			'status' => function_exists( 'openssl_encrypt' ) ? 'ok' : 'aviso',
			'detail' => function_exists( 'openssl_encrypt' )
				? 'OpenSSL disponível. A senha de app fica cifrada no banco.'
				: 'OpenSSL indisponível. A senha de app fica apenas codificada. Prefira definir BBL_USERS_GMAIL_PASS no wp-config.php.',
		);

		return $checks;
	}

	/**
	 * Cria e remove um usuário descartável para provar que o INSERT funciona.
	 *
	 * @return array{ok:bool,message:string}
	 */
	public static function test_insert() {
		$email = 'bbl-users-teste-' . wp_generate_password( 8, false, false ) . '@bebelume.invalid';
		$login = 'bbl-teste-' . substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 8 );

		$user_id = wp_insert_user(
			array(
				'user_login' => $login,
				'user_email' => $email,
				'user_pass'  => wp_generate_password( 20, true, true ),
				'role'       => 'subscriber',
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return array(
				'ok'      => false,
				'message' => 'O INSERT falhou: ' . $user_id->get_error_message() . ' Se a mensagem citar permissão ou comando negado, é o GRANT cruzado que ainda não foi aplicado no MySQL.',
			);
		}

		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $user_id );

		$still_there = get_user_by( 'id', $user_id );

		if ( $still_there ) {
			return array(
				'ok'      => false,
				'message' => 'O usuário de teste foi criado (ID ' . (int) $user_id . '), mas não pôde ser removido. Apague manualmente antes de rodar o lote.',
			);
		}

		return array(
			'ok'      => true,
			'message' => 'Criação e remoção funcionaram. O ambiente está liberado para o lote.',
		);
	}

	/**
	 * Níveis PMPro disponíveis.
	 */
	public static function get_levels() {
		if ( ! function_exists( 'pmpro_getAllLevels' ) ) {
			return array();
		}

		$levels = pmpro_getAllLevels( true, true );

		return is_array( $levels ) ? $levels : array();
	}
}
