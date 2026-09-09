<?php
/**
 * Criação do usuário a partir de uma linha do rascunho.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BBL_Users_Creator {

	/**
	 * Gera um login no padrão user-a7f3c2d9, o mesmo do bebelume-pmpro-profile.
	 */
	public static function generate_username() {
		$tries = 0;

		do {
			$username = 'user-' . substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 8 );
			$tries++;
		} while ( username_exists( $username ) && $tries < 20 );

		return $username;
	}

	/**
	 * Quebra o nome completo em primeiro e último nome.
	 */
	private static function split_name( $nome ) {
		$nome  = trim( preg_replace( '/\s+/', ' ', $nome ) );
		$parts = explode( ' ', $nome );
		$first = array_shift( $parts );
		$last  = implode( ' ', $parts );

		return array( $first, $last );
	}

	/**
	 * URL de login. Usa a rota do bebelume-pmpro-profile quando ela existe.
	 */
	public static function login_url() {
		$page_id = function_exists( 'pmpro_getOption' ) ? (int) pmpro_getOption( 'login_page_id' ) : 0;

		if ( $page_id && get_post_status( $page_id ) === 'publish' ) {
			return get_permalink( $page_id );
		}

		return wp_login_url();
	}

	/**
	 * Link de primeiro acesso: o usuário define a própria senha na tela
	 * de reset do WordPress, sem que nenhuma senha trafegue por e-mail.
	 */
	public static function first_access_url( $user_id ) {
		$user = get_userdata( (int) $user_id );

		if ( ! $user ) {
			return '';
		}

		$key = get_password_reset_key( $user );

		if ( is_wp_error( $key ) ) {
			return '';
		}

		return network_site_url(
			'wp-login.php?action=rp&key=' . $key . '&login=' . rawurlencode( $user->user_login ),
			'login'
		);
	}

	/**
	 * Nome legível de um nível PMPro.
	 */
	public static function level_name( $level_id ) {
		if ( ! $level_id || ! function_exists( 'pmpro_getLevel' ) ) {
			return '';
		}

		$level = pmpro_getLevel( (int) $level_id );

		return $level && isset( $level->name ) ? $level->name : '';
	}

	/**
	 * Processa uma linha do rascunho.
	 *
	 * Cria o usuário quando o e-mail é novo. Quando o e-mail já existe
	 * (comum aqui, porque canal e arteduca compartilham a wp_users do hub),
	 * apenas atribui o nível à conta existente.
	 *
	 * @param int $id       ID da linha no rascunho.
	 * @param int $level_id Nível PMPro escolhido para o lote. 0 = nenhum.
	 * @return array Resultado para exibição na tela.
	 */
	public static function process( $id, $level_id = 0 ) {
		$row = BBL_Users_DB::get( $id );

		if ( ! $row ) {
			return self::fail( $id, 'Linha não encontrada no rascunho.' );
		}

		if ( BBL_Users_DB::STATUS_RASCUNHO !== $row['status'] ) {
			return array(
				'id'      => (int) $id,
				'status'  => $row['status'],
				'message' => 'Já processado. Nada foi refeito.',
				'skipped' => true,
			);
		}

		$nome  = trim( $row['nome'] );
		$email = sanitize_email( $row['email'] );

		if ( ! is_email( $email ) ) {
			return self::fail( $id, 'E-mail inválido.' );
		}

		$level_id   = (int) $level_id;
		$level_name = self::level_name( $level_id );
		$existing   = get_user_by( 'email', $email );

		if ( $existing ) {
			return self::handle_existing( $id, $existing, $nome, $level_id, $level_name );
		}

		$username = self::generate_username();
		$password = BBL_Users_Password::generate();

		list( $first, $last ) = self::split_name( $nome );

		// wp_insert_user não dispara e-mail do WordPress. O envio é só o nosso.
		$user_id = wp_insert_user(
			array(
				'user_login'   => $username,
				'user_email'   => $email,
				'user_pass'    => $password,
				'display_name' => $nome ? $nome : $email,
				'first_name'   => $first,
				'last_name'    => $last,
				'nickname'     => $nome ? $nome : $username,
				'role'         => 'subscriber',
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return self::fail( $id, 'Não foi possível criar o usuário: ' . $user_id->get_error_message() );
		}

		// Conta criada pelo admin já chega com e-mail dado como certo:
		// não faz sentido pedir confirmação de um segundo link para quem
		// vai definir a senha pelo link de primeiro acesso. Sem isso a
		// pessoa cai na tela "Confirme seu e-mail" no primeiro login.
		update_user_meta( $user_id, 'bbl_email_confirmed', 1 );

		$level_error = '';

		if ( $level_id ) {
			$level_error = self::assign_level( $user_id, $level_id );
		}

		$mailer = BBL_Users_Mailer::instance();
		$email_opts = BBL_Users_Mailer::get_email_options();

		$vars = array(
			'nome'      => $nome,
			'email'     => $email,
			'usuario'   => $username,
			'senha'     => '',
			'nivel'     => $level_name,
			'url_login' => self::login_url(),
			'url_senha' => self::first_access_url( $user_id ),
		);

		$sent = $mailer->send(
			$email,
			BBL_Users_Mailer::render( $email_opts['subject'], $vars ),
			BBL_Users_Mailer::render( $email_opts['body'], $vars )
		);

		$erro = '';

		if ( ! $sent ) {
			$erro = 'Usuário criado, mas o e-mail não saiu: ' . ( $mailer->get_last_error() ? $mailer->get_last_error() : 'motivo não informado pelo servidor.' );
		}

		if ( $level_error ) {
			$erro = trim( $erro . ' ' . $level_error );
		}

		BBL_Users_DB::update(
			$id,
			array(
				'status'           => BBL_Users_DB::STATUS_CRIADO,
				'user_id'          => $user_id,
				'username'         => $username,
				'membership_level' => $level_id,
				'email_enviado'    => $sent ? 1 : 0,
				'erro'             => $erro,
				'processado_em'    => current_time( 'mysql' ),
			)
		);

		return array(
			'id'       => (int) $id,
			'status'   => BBL_Users_DB::STATUS_CRIADO,
			'username' => $username,
			'user_id'  => (int) $user_id,
			'sent'     => (bool) $sent,
			'message'  => $sent ? 'Usuário criado e e-mail enviado.' : $erro,
		);
	}

	/**
	 * E-mail já cadastrado: mantém a conta e só libera o nível.
	 */
	private static function handle_existing( $id, $user, $nome, $level_id, $level_name ) {
		$level_error = '';

		if ( $level_id ) {
			$level_error = self::assign_level( $user->ID, $level_id );
		}

		$sent       = false;
		$email_opts = BBL_Users_Mailer::get_email_options();

		if ( ! empty( $email_opts['notify_existing'] ) ) {
			$vars = array(
				'nome'      => $nome ? $nome : $user->display_name,
				'email'     => $user->user_email,
				'usuario'   => $user->user_login,
				'senha'     => '',
				'nivel'     => $level_name,
				'url_login' => self::login_url(),
			);

			$sent = BBL_Users_Mailer::instance()->send(
				$user->user_email,
				BBL_Users_Mailer::render( $email_opts['subject_existing'], $vars ),
				BBL_Users_Mailer::render( $email_opts['body_existing'], $vars )
			);
		}

		$message = 'Este e-mail já tinha conta. A senha foi mantida' . ( $level_id ? ' e o nível foi atribuído.' : '.' );

		if ( $level_error ) {
			$message .= ' ' . $level_error;
		}

		BBL_Users_DB::update(
			$id,
			array(
				'status'           => BBL_Users_DB::STATUS_EXISTENTE,
				'user_id'          => $user->ID,
				'username'         => $user->user_login,
				'membership_level' => $level_id,
				'email_enviado'    => $sent ? 1 : 0,
				'erro'             => $level_error,
				'processado_em'    => current_time( 'mysql' ),
			)
		);

		return array(
			'id'       => (int) $id,
			'status'   => BBL_Users_DB::STATUS_EXISTENTE,
			'username' => $user->user_login,
			'user_id'  => (int) $user->ID,
			'sent'     => (bool) $sent,
			'message'  => $message,
		);
	}

	/**
	 * Atribui o nível PMPro. Retorna string de erro ou vazio.
	 */
	private static function assign_level( $user_id, $level_id ) {
		if ( ! function_exists( 'pmpro_changeMembershipLevel' ) ) {
			return 'O PMPro não está ativo, então o nível não foi atribuído.';
		}

		$ok = pmpro_changeMembershipLevel( (int) $level_id, (int) $user_id );

		return $ok ? '' : 'O nível PMPro não pôde ser atribuído.';
	}

	/**
	 * Marca a linha como erro.
	 */
	private static function fail( $id, $message ) {
		BBL_Users_DB::update(
			$id,
			array(
				'status'        => BBL_Users_DB::STATUS_ERRO,
				'erro'          => $message,
				'processado_em' => current_time( 'mysql' ),
			)
		);

		return array(
			'id'      => (int) $id,
			'status'  => BBL_Users_DB::STATUS_ERRO,
			'message' => $message,
		);
	}
}
