<?php
/**
 * Configuração de SMTP do Gmail e envio das mensagens do plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BBL_Users_Mailer {

	const OPTION_SMTP  = 'bbl_users_smtp';
	const OPTION_EMAIL = 'bbl_users_email';

	private static $instance = null;

	/** Último erro capturado do wp_mail. */
	private $last_error = '';

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function hooks() {
		add_action( 'phpmailer_init', array( $this, 'configure_phpmailer' ) );
		add_action( 'wp_mail_failed', array( $this, 'capture_error' ) );
	}

	/* ---------------------------------------------------------------------
	 * Configuração
	 * ------------------------------------------------------------------ */

	public static function get_smtp_options() {
		$defaults = array(
			'enabled'      => 0,
			'host'         => 'smtp.gmail.com',
			'port'         => 587,
			'encryption'   => 'tls',
			'username'     => '',
			'password'     => '',
			'from_name'    => get_bloginfo( 'name' ),
			'apply_global' => 1,
		);

		$opts = get_option( self::OPTION_SMTP, array() );
		$opts = wp_parse_args( is_array( $opts ) ? $opts : array(), $defaults );

		return $opts;
	}

	/**
	 * Senha de app em texto puro.
	 *
	 * A constante do wp-config.php tem prioridade sobre o valor do banco.
	 */
	public static function get_password() {
		if ( defined( 'BBL_USERS_GMAIL_PASS' ) && BBL_USERS_GMAIL_PASS ) {
			return BBL_USERS_GMAIL_PASS;
		}

		$opts = self::get_smtp_options();

		return BBL_Users_Crypto::decrypt( $opts['password'] );
	}

	public static function save_smtp_options( $input ) {
		$current = self::get_smtp_options();

		$opts = array(
			'enabled'      => empty( $input['enabled'] ) ? 0 : 1,
			'host'         => sanitize_text_field( $input['host'] ?? 'smtp.gmail.com' ),
			'port'         => (int) ( $input['port'] ?? 587 ),
			'encryption'   => in_array( $input['encryption'] ?? 'tls', array( 'tls', 'ssl' ), true ) ? $input['encryption'] : 'tls',
			'username'     => sanitize_email( $input['username'] ?? '' ),
			'from_name'    => sanitize_text_field( $input['from_name'] ?? get_bloginfo( 'name' ) ),
			'apply_global' => empty( $input['apply_global'] ) ? 0 : 1,
			'password'     => $current['password'],
		);

		// Campo em branco mantém a senha já salva. Espaços do copiar/colar do Google são removidos.
		$raw = isset( $input['password'] ) ? trim( (string) $input['password'] ) : '';

		if ( '' !== $raw ) {
			$opts['password'] = BBL_Users_Crypto::encrypt( str_replace( ' ', '', $raw ) );
		}

		update_option( self::OPTION_SMTP, $opts, false );

		return $opts;
	}

	public static function is_configured() {
		$opts = self::get_smtp_options();

		return ! empty( $opts['enabled'] )
			&& ! empty( $opts['username'] )
			&& '' !== self::get_password();
	}

	/* ---------------------------------------------------------------------
	 * PHPMailer
	 * ------------------------------------------------------------------ */

	/**
	 * Injeta as credenciais SMTP no PHPMailer.
	 *
	 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer
	 */
	public function configure_phpmailer( $phpmailer ) {
		if ( ! self::is_configured() ) {
			return;
		}

		$opts = self::get_smtp_options();

		// Fora do modo global, só as mensagens do próprio plugin passam pelo Gmail.
		if ( empty( $opts['apply_global'] ) && ! did_action( 'bbl_users_sending' ) ) {
			return;
		}

		$phpmailer->isSMTP();
		$phpmailer->Host       = $opts['host'];
		$phpmailer->Port       = (int) $opts['port'];
		$phpmailer->SMTPAuth   = true;
		$phpmailer->SMTPSecure = $opts['encryption'];
		$phpmailer->Username   = $opts['username'];
		$phpmailer->Password   = self::get_password();

		// O Gmail reescreve o remetente para a conta autenticada de qualquer forma.
		$phpmailer->setFrom( $opts['username'], $opts['from_name'], false );
		$phpmailer->CharSet = 'UTF-8';
	}

	public function capture_error( $wp_error ) {
		if ( is_wp_error( $wp_error ) ) {
			$this->last_error = $wp_error->get_error_message();
		}
	}

	public function get_last_error() {
		return $this->last_error;
	}

	/* ---------------------------------------------------------------------
	 * Conteúdo das mensagens
	 * ------------------------------------------------------------------ */

	public static function get_email_options() {
		$defaults = array(
			'subject'          => 'Seu acesso ao {site}',
			'body'             => self::default_body(),
			'subject_existing' => 'Novo acesso liberado em {site}',
			'body_existing'    => self::default_body_existing(),
			'notify_existing'  => 1,
		);

		$opts = get_option( self::OPTION_EMAIL, array() );

		return wp_parse_args( is_array( $opts ) ? $opts : array(), $defaults );
	}

	public static function save_email_options( $input ) {
		$opts = array(
			'subject'          => sanitize_text_field( $input['subject'] ?? '' ),
			'body'             => wp_kses_post( $input['body'] ?? '' ),
			'subject_existing' => sanitize_text_field( $input['subject_existing'] ?? '' ),
			'body_existing'    => wp_kses_post( $input['body_existing'] ?? '' ),
			'notify_existing'  => empty( $input['notify_existing'] ) ? 0 : 1,
		);

		update_option( self::OPTION_EMAIL, $opts, false );

		return $opts;
	}

	public static function default_body() {
		return "<p>Olá, {nome}!</p>\n"
			. "<p>Sua conta no {site} está pronta. Para entrar, defina a sua senha:</p>\n"
			. "<p><a href=\"{url_senha}\">Definir minha senha</a></p>\n"
			. "<p>Se o botão não funcionar, copie e cole este link no navegador: <a href=\"{url_senha}\">{url_senha}</a></p>\n"
			. "<p>Depois é só entrar com o seu e-mail <strong>{email}</strong> em: <a href=\"{url_login}\">{url_login}</a></p>\n"
			. '<p>Um abraço,<br>Equipe {site}</p>';
	}

	public static function default_body_existing() {
		return "<p>Olá, {nome}!</p>\n"
			. "<p>Você já tem conta no {site} e acabamos de liberar um novo acesso para ela.</p>\n"
			. "<p>Entre normalmente com o seu e-mail e a sua senha de sempre: <a href=\"{url_login}\">{url_login}</a></p>\n"
			. "<p>Se não lembrar a senha, use a opção de recuperação na tela de login.</p>\n"
			. '<p>Um abraço,<br>Equipe {site}</p>';
	}

	/**
	 * Substitui os marcadores do template.
	 */
	public static function render( $text, $vars ) {
		$map = array(
			'{nome}'      => $vars['nome'] ?? '',
			'{email}'     => $vars['email'] ?? '',
			'{usuario}'   => $vars['usuario'] ?? '',
			'{senha}'     => $vars['senha'] ?? '',
			'{site}'      => get_bloginfo( 'name' ),
			'{url_site}'  => home_url( '/' ),
			'{url_login}' => $vars['url_login'] ?? wp_login_url(),
			'{url_senha}' => $vars['url_senha'] ?? '',
			'{nivel}'     => $vars['nivel'] ?? '',
		);

		return strtr( $text, $map );
	}

	/**
	 * Envelopa o corpo no HTML final da mensagem.
	 *
	 * Usado tanto no envio quanto na prévia, para que o que aparece na tela
	 * seja exatamente o que sai no e-mail.
	 */
	public static function wrap_html( $body ) {
		// Corpo já contém HTML (template de marca): não passa pelo wpautop,
		// que corrompe tabelas/divs inserindo <p> no meio do markup.
		$content = ( $body === strip_tags( $body ) ) ? wpautop( $body ) : $body;

		return '<div style="font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#1f2328;">'
			. $content
			. '</div>';
	}

	/**
	 * Dados fictícios para a prévia da mensagem.
	 */
	public static function sample_vars( $com_senha = true ) {
		return array(
			'nome'      => 'Maria Souza',
			'email'     => 'maria@exemplo.com',
			'usuario'   => 'user-a7f3c2d9',
			'senha'     => $com_senha ? BBL_Users_Password::generate() : '',
			'nivel'     => 'Exemplo de nível',
			'url_login' => BBL_Users_Creator::login_url(),
			'url_senha' => wp_login_url() . '?action=rp&key=exemplo',
		);
	}

	/**
	 * Envia um e-mail em HTML pelo remetente configurado.
	 *
	 * @return bool
	 */
	public function send( $to, $subject, $body ) {
		$this->last_error = '';

		do_action( 'bbl_users_sending' );

		$html_filter = function () {
			return 'text/html';
		};

		add_filter( 'wp_mail_content_type', $html_filter );

		$sent = wp_mail( $to, $subject, self::wrap_html( $body ) );

		remove_filter( 'wp_mail_content_type', $html_filter );

		return $sent;
	}

	/**
	 * Envia a mensagem de teste da tela de configuração.
	 *
	 * @return array{sent:bool,error:string}
	 */
	public function send_test( $to ) {
		$body = '<p>Este é um teste do Bebelume Users.</p><p>Se você recebeu esta mensagem, o SMTP do Gmail está configurado corretamente e os e-mails de criação de usuário vão sair por esta conta.</p>';

		$sent = $this->send( $to, 'Teste de envio — ' . get_bloginfo( 'name' ), $body );

		return array(
			'sent'  => (bool) $sent,
			'error' => $this->last_error,
		);
	}
}
