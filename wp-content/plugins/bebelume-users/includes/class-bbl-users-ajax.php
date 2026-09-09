<?php
/**
 * Endpoints AJAX.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BBL_Users_Ajax {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function hooks() {
		$actions = array(
			'bbl_users_preview'      => 'preview',
			'bbl_users_add'          => 'add',
			'bbl_users_update_row'   => 'update_row',
			'bbl_users_delete_row'   => 'delete_row',
			'bbl_users_create_chunk' => 'create_chunk',
			'bbl_users_test_email'   => 'test_email',
			'bbl_users_test_insert'  => 'test_insert',
			'bbl_users_preview_pass' => 'preview_pass',
			'bbl_users_preview_mail' => 'preview_mail',
		);

		foreach ( $actions as $action => $method ) {
			add_action( 'wp_ajax_' . $action, array( $this, $method ) );
		}
	}

	/**
	 * Nonce + capability em todo endpoint.
	 */
	private function guard( $cap = BBL_Users_Admin::CAPABILITY ) {
		check_ajax_referer( 'bbl_users', 'nonce' );

		if ( ! current_user_can( $cap ) ) {
			wp_send_json_error( array( 'message' => 'Você não tem permissão para esta ação.' ), 403 );
		}
	}

	/**
	 * Quebra o texto colado em linhas de nome + e-mail.
	 *
	 * Aceita vírgula, ponto e vírgula ou tabulação como separador, e também
	 * o formato "Nome <email@dominio.com>".
	 */
	private function parse( $text ) {
		$rows  = array();
		$lines = preg_split( '/\r\n|\r|\n/', (string) $text );

		foreach ( $lines as $line ) {
			$line = trim( $line );

			if ( '' === $line ) {
				continue;
			}

			$nome  = '';
			$email = '';

			if ( preg_match( '/^(.*?)<([^>]+)>$/', $line, $m ) ) {
				$nome  = trim( $m[1] );
				$email = trim( $m[2] );
			} else {
				$parts = preg_split( '/[;,\t]+/', $line );

				if ( count( $parts ) >= 2 ) {
					$email = trim( array_pop( $parts ) );
					$nome  = trim( implode( ' ', array_map( 'trim', $parts ) ) );
				} else {
					$email = trim( $parts[0] );
				}
			}

			$email = sanitize_email( $email );
			$nome  = sanitize_text_field( preg_replace( '/\s+/', ' ', $nome ) );

			$erro = '';

			if ( ! is_email( $email ) ) {
				$erro = 'E-mail inválido';
			} elseif ( BBL_Users_DB::email_in_draft( $email ) ) {
				$erro = 'Já está na lista de rascunho';
			}

			$rows[] = array(
				'nome'     => $nome,
				'email'    => $email,
				'linha'    => $line,
				'erro'     => $erro,
				'existente' => $erro ? false : (bool) get_user_by( 'email', $email ),
			);
		}

		return $rows;
	}

	/**
	 * Confere o que foi colado antes de salvar.
	 */
	public function preview() {
		$this->guard();

		$text = isset( $_POST['text'] ) ? wp_unslash( $_POST['text'] ) : ''; // phpcs:ignore
		$rows = $this->parse( $text );

		$validos = 0;

		foreach ( $rows as $row ) {
			if ( '' === $row['erro'] ) {
				$validos++;
			}
		}

		wp_send_json_success(
			array(
				'rows'    => $rows,
				'total'   => count( $rows ),
				'validos' => $validos,
			)
		);
	}

	/**
	 * Salva as linhas válidas como rascunho. Não cria usuário nenhum.
	 */
	public function add() {
		$this->guard();

		$text = isset( $_POST['text'] ) ? wp_unslash( $_POST['text'] ) : ''; // phpcs:ignore
		$rows = $this->parse( $text );

		$salvos    = 0;
		$ignorados = 0;

		foreach ( $rows as $row ) {
			if ( '' !== $row['erro'] ) {
				$ignorados++;
				continue;
			}

			// Reconfere aqui porque as linhas do mesmo lote podem repetir entre si.
			if ( BBL_Users_DB::email_in_draft( $row['email'] ) ) {
				$ignorados++;
				continue;
			}

			if ( BBL_Users_DB::add( $row['nome'], $row['email'] ) ) {
				$salvos++;
			} else {
				$ignorados++;
			}
		}

		wp_send_json_success(
			array(
				'salvos'    => $salvos,
				'ignorados' => $ignorados,
				'message'   => sprintf(
					'%d linha(s) salva(s) no rascunho%s.',
					$salvos,
					$ignorados ? sprintf( ', %d ignorada(s)', $ignorados ) : ''
				),
			)
		);
	}

	/**
	 * Edição inline de nome e e-mail no rascunho.
	 */
	public function update_row() {
		$this->guard();

		$id    = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$nome  = isset( $_POST['nome'] ) ? sanitize_text_field( wp_unslash( $_POST['nome'] ) ) : '';
		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

		$row = BBL_Users_DB::get( $id );

		if ( ! $row ) {
			wp_send_json_error( array( 'message' => 'Linha não encontrada.' ) );
		}

		if ( BBL_Users_DB::STATUS_RASCUNHO !== $row['status'] ) {
			wp_send_json_error( array( 'message' => 'Esta linha já foi processada e não pode mais ser editada.' ) );
		}

		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => 'E-mail inválido.' ) );
		}

		if ( BBL_Users_DB::email_in_draft( $email, $id ) ) {
			wp_send_json_error( array( 'message' => 'Este e-mail já está em outra linha do rascunho.' ) );
		}

		BBL_Users_DB::update(
			$id,
			array(
				'nome'  => $nome,
				'email' => $email,
			)
		);

		wp_send_json_success( array( 'message' => 'Linha atualizada.' ) );
	}

	public function delete_row() {
		$this->guard();

		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

		BBL_Users_DB::delete( $id );

		wp_send_json_success( array( 'message' => 'Linha removida do rascunho.' ) );
	}

	/**
	 * Processa um bloco de linhas.
	 *
	 * O JS chama isso em sequência até acabar a fila. Assim nenhum request
	 * fica longo o bastante para estourar o timeout do PHP, e se algo cair
	 * no meio, as linhas já feitas continuam marcadas como criadas.
	 */
	public function create_chunk() {
		$this->guard();

		$ids = isset( $_POST['ids'] ) ? (array) wp_unslash( $_POST['ids'] ) : array(); // phpcs:ignore
		$ids = array_slice( array_map( 'intval', $ids ), 0, 25 );

		$level_id = isset( $_POST['level_id'] ) ? (int) $_POST['level_id'] : 0;

		if ( empty( $ids ) ) {
			wp_send_json_error( array( 'message' => 'Nenhuma linha recebida.' ) );
		}

		if ( ! BBL_Users_Mailer::is_configured() ) {
			wp_send_json_error( array( 'message' => 'O SMTP não está configurado. Configure o envio de e-mail antes de criar usuários.' ) );
		}

		$results = array();

		foreach ( $ids as $id ) {
			$results[] = BBL_Users_Creator::process( $id, $level_id );
		}

		wp_send_json_success( array( 'results' => $results ) );
	}

	/**
	 * Envio de teste.
	 */
	public function test_email() {
		$this->guard( 'manage_options' );

		$to = isset( $_POST['to'] ) ? sanitize_email( wp_unslash( $_POST['to'] ) ) : '';

		if ( ! is_email( $to ) ) {
			wp_send_json_error( array( 'message' => 'Informe um e-mail válido para o teste.' ) );
		}

		if ( ! BBL_Users_Mailer::is_configured() ) {
			wp_send_json_error( array( 'message' => 'Salve as credenciais e marque "Usar este SMTP" antes de testar.' ) );
		}

		$result = BBL_Users_Mailer::instance()->send_test( $to );

		if ( $result['sent'] ) {
			wp_send_json_success( array( 'message' => 'Mensagem enviada para ' . $to . '. Confira a caixa de entrada.' ) );
		}

		wp_send_json_error(
			array(
				'message' => 'O envio falhou. ' . ( $result['error'] ? $result['error'] : 'O servidor não informou o motivo.' ),
			)
		);
	}

	/**
	 * Teste de criação e remoção de usuário descartável.
	 */
	public function test_insert() {
		$this->guard( 'manage_options' );

		$result = BBL_Users_Diagnostics::test_insert();

		if ( $result['ok'] ) {
			wp_send_json_success( array( 'message' => $result['message'] ) );
		}

		wp_send_json_error( array( 'message' => $result['message'] ) );
	}

	/**
	 * Prévia da mensagem com dados fictícios, sem salvar nem enviar.
	 */
	public function preview_mail() {
		$this->guard( 'manage_options' );

		$subject  = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
		$body     = isset( $_POST['body'] ) ? wp_kses_post( wp_unslash( $_POST['body'] ) ) : ''; // phpcs:ignore
		$com_senha = empty( $_POST['sem_senha'] );

		$vars = BBL_Users_Mailer::sample_vars( $com_senha );

		$rendered_subject = BBL_Users_Mailer::render( $subject, $vars );
		$rendered_body    = BBL_Users_Mailer::render( $body, $vars );

		// Marcador de senha numa mensagem que não leva senha vira string vazia:
		// melhor avisar do que deixar o e-mail sair com um buraco no meio.
		$aviso = '';

		if ( ! $com_senha && false !== strpos( $body . $subject, '{senha}' ) ) {
			$aviso = 'Esta mensagem usa {senha}, mas quem já tem conta mantém a senha antiga. O marcador vai sair vazio. Remova-o do texto.';
		}

		wp_send_json_success(
			array(
				'subject' => $rendered_subject,
				'html'    => BBL_Users_Mailer::wrap_html( $rendered_body ),
				'aviso'   => $aviso,
			)
		);
	}

	/**
	 * Amostra de senhas com a configuração atual da tela, sem salvar.
	 */
	public function preview_pass() {
		$this->guard( 'manage_options' );

		$opts = isset( $_POST['opts'] ) ? (array) wp_unslash( $_POST['opts'] ) : array(); // phpcs:ignore

		$clean = array(
			'length'       => max( 6, min( 64, (int) ( $opts['length'] ?? 12 ) ) ),
			'lower'        => empty( $opts['lower'] ) ? 0 : 1,
			'upper'        => empty( $opts['upper'] ) ? 0 : 1,
			'numbers'      => empty( $opts['numbers'] ) ? 0 : 1,
			'symbols'      => empty( $opts['symbols'] ) ? 0 : 1,
			'symbols_set'  => sanitize_text_field( $opts['symbols_set'] ?? BBL_Users_Password::SYMBOLS ),
			'no_ambiguous' => empty( $opts['no_ambiguous'] ) ? 0 : 1,
		);

		$samples = array();

		for ( $i = 0; $i < 3; $i++ ) {
			$samples[] = BBL_Users_Password::generate( $clean );
		}

		wp_send_json_success( array( 'samples' => $samples ) );
	}
}
