<?php
/**
 * Tela: Configurações (abas E-mail, Senha e Diagnóstico).
 *
 * @var string $tab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$smtp       = BBL_Users_Mailer::get_smtp_options();
$email_opts = BBL_Users_Mailer::get_email_options();
$pass       = BBL_Users_Password::get_options();
$has_pass   = '' !== BBL_Users_Mailer::get_password();
$const_pass = defined( 'BBL_USERS_GMAIL_PASS' ) && BBL_USERS_GMAIL_PASS;

$tabs = array(
	'email'       => 'E-mail',
	'senha'       => 'Senha',
	'diagnostico' => 'Diagnóstico',
);
?>
<div class="wrap bbl-users">

	<h1>Configurações</h1>
	<?php BBL_Users_Admin::notice(); ?>

	<nav class="nav-tab-wrapper">
		<?php foreach ( $tabs as $key => $label ) : ?>
			<a href="<?php echo esc_url( BBL_Users_Admin::url( BBL_Users_Admin::SLUG . '-config', array( 'tab' => $key ) ) ); ?>"
				class="nav-tab <?php echo $tab === $key ? 'nav-tab-active' : ''; ?>">
				<?php echo esc_html( $label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<?php if ( 'email' === $tab ) : ?>

		<form method="post">
			<?php wp_nonce_field( 'bbl_users_save_smtp' ); ?>
			<input type="hidden" name="bbl_users_action" value="save_smtp">

			<div class="bbl-card">
				<h2>Conta do Gmail</h2>

				<div class="bbl-callout">
					<strong>O Gmail não aceita a senha normal da conta.</strong>
					Você precisa de uma <em>Senha de app</em> de 16 caracteres, gerada em
					<a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener">myaccount.google.com/apppasswords</a>.
					A opção só aparece se a conta tiver verificação em duas etapas ativada.
					Limite de envio: cerca de 500 mensagens por dia em conta gratuita e 2.000 no Workspace.
				</div>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">Usar este SMTP</th>
						<td>
							<label>
								<input type="checkbox" name="smtp[enabled]" value="1" <?php checked( $smtp['enabled'], 1 ); ?>>
								Enviar as mensagens por esta conta
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="smtp-username">E-mail da conta</label></th>
						<td>
							<input type="email" id="smtp-username" name="smtp[username]" class="regular-text" value="<?php echo esc_attr( $smtp['username'] ); ?>" placeholder="conta@gmail.com">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="smtp-password">Senha de app</label></th>
						<td>
							<input type="password" id="smtp-password" name="smtp[password]" class="regular-text" autocomplete="new-password"
								placeholder="<?php echo $has_pass ? 'Senha salva. Preencha só para trocar.' : 'xxxx xxxx xxxx xxxx'; ?>" <?php disabled( $const_pass ); ?>>
							<p class="description">
								<?php if ( $const_pass ) : ?>
									A senha está definida em <code>BBL_USERS_GMAIL_PASS</code> no wp-config.php e tem prioridade sobre este campo.
								<?php else : ?>
									Fica cifrada no banco. Os espaços do copiar e colar do Google são removidos automaticamente.
									Para não guardar no banco, defina <code>BBL_USERS_GMAIL_PASS</code> no wp-config.php.
								<?php endif; ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="smtp-from-name">Nome do remetente</label></th>
						<td>
							<input type="text" id="smtp-from-name" name="smtp[from_name]" class="regular-text" value="<?php echo esc_attr( $smtp['from_name'] ); ?>">
							<p class="description">O endereço do remetente é sempre a conta acima: o Gmail reescreve qualquer outro.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">Servidor</th>
						<td>
							<input type="text" name="smtp[host]" class="regular-text" value="<?php echo esc_attr( $smtp['host'] ); ?>">
							<label class="bbl-inline">Porta
								<input type="number" name="smtp[port]" class="small-text" value="<?php echo (int) $smtp['port']; ?>">
							</label>
							<label class="bbl-inline">Criptografia
								<select name="smtp[encryption]">
									<option value="tls" <?php selected( $smtp['encryption'], 'tls' ); ?>>TLS (587)</option>
									<option value="ssl" <?php selected( $smtp['encryption'], 'ssl' ); ?>>SSL (465)</option>
								</select>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">Alcance</th>
						<td>
							<label>
								<input type="checkbox" name="smtp[apply_global]" value="1" <?php checked( $smtp['apply_global'], 1 ); ?>>
								Usar esta conta para todos os e-mails do site
							</label>
							<p class="description">
								Desmarque para que só as mensagens deste plugin passem pelo Gmail.
								Se você já usa WP Mail SMTP ou similar, deixe desmarcado para evitar conflito.
							</p>
						</td>
					</tr>
				</table>
			</div>

			<?php
			$editor_args = array(
				'media_buttons' => false,
				'teeny'         => true,
				'quicktags'     => true,
				'editor_height' => 260,
				'tinymce'       => array(
					'toolbar1' => 'bold,italic,underline,bullist,numlist,link,unlink,undo,redo',
				),
			);
			?>

			<div class="bbl-card">
				<h2>Mensagem de boas-vindas</h2>
				<p class="description">
					Enviada para quem teve a conta criada agora. Nenhuma senha é enviada por
					e-mail: a pessoa define a própria senha clicando no link de primeiro acesso.
				</p>

				<div class="bbl-tokens">
					<span class="bbl-tokens__label">Marcadores — clique para copiar:</span>
					<?php
					$tokens = array(
						'{nome}'       => 'Nome da pessoa',
						'{email}'      => 'E-mail dela',
						'{usuario}'    => 'Login gerado',
						'{url_senha}'  => 'Link para definir a senha',
						'{site}'       => 'Nome do site',
						'{url_site}'   => 'Endereço do site',
						'{url_login}'  => 'Página de login',
						'{nivel}'      => 'Nível PMPro',
					);
					foreach ( $tokens as $token => $desc ) :
						?>
						<button type="button" class="bbl-token" data-token="<?php echo esc_attr( $token ); ?>" title="<?php echo esc_attr( $desc ); ?>">
							<?php echo esc_html( $token ); ?>
						</button>
					<?php endforeach; ?>
				</div>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="email-subject">Assunto</label></th>
						<td><input type="text" id="email-subject" name="email[subject]" class="large-text" value="<?php echo esc_attr( $email_opts['subject'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="email_body">Conteúdo</label></th>
						<td>
							<?php
							wp_editor(
								$email_opts['body'],
								'email_body',
								array_merge( $editor_args, array( 'textarea_name' => 'email[body]' ) )
							);
							?>
							<p class="bbl-actions">
								<button type="button" class="button bbl-preview-mail" data-subject="#email-subject" data-editor="email_body">Ver prévia</button>
								<button type="button" class="button-link bbl-restore" data-editor="email_body" data-default="novo">Restaurar texto padrão</button>
							</p>
						</td>
					</tr>
				</table>
			</div>

			<div class="bbl-card">
				<h2>Mensagem para quem já tem conta</h2>
				<p class="description">
					Acontece bastante aqui: canal e arteduca compartilham a mesma base de usuários do hub.
					A senha da pessoa é preservada, então esta mensagem não deve conter <code>{senha}</code>.
				</p>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">Avisar mesmo assim</th>
						<td>
							<label>
								<input type="checkbox" name="email[notify_existing]" value="1" <?php checked( $email_opts['notify_existing'], 1 ); ?>>
								Enviar mensagem para quem já tinha conta
							</label>
							<p class="description">Desmarcado, essas pessoas recebem o nível em silêncio, sem nenhum e-mail.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="email-subject-existing">Assunto</label></th>
						<td><input type="text" id="email-subject-existing" name="email[subject_existing]" class="large-text" value="<?php echo esc_attr( $email_opts['subject_existing'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="email_body_existing">Conteúdo</label></th>
						<td>
							<?php
							wp_editor(
								$email_opts['body_existing'],
								'email_body_existing',
								array_merge( $editor_args, array( 'textarea_name' => 'email[body_existing]' ) )
							);
							?>
							<p class="bbl-actions">
								<button type="button" class="button bbl-preview-mail" data-subject="#email-subject-existing" data-editor="email_body_existing" data-sem-senha="1">Ver prévia</button>
								<button type="button" class="button-link bbl-restore" data-editor="email_body_existing" data-default="existente">Restaurar texto padrão</button>
							</p>
						</td>
					</tr>
				</table>
			</div>

			<div id="bbl-mail-preview" class="bbl-modal" hidden>
				<div class="bbl-modal__box bbl-modal__box--wide" role="dialog" aria-modal="true" aria-labelledby="bbl-mail-preview-title">
					<h2 id="bbl-mail-preview-title">Prévia da mensagem</h2>
					<p class="description">Com dados fictícios. É assim que a pessoa vai receber.</p>
					<div id="bbl-mail-preview-aviso" class="bbl-callout bbl-callout--alerta" hidden></div>
					<div class="bbl-mailview">
						<div class="bbl-mailview__head">
							<span class="bbl-mailview__label">Assunto</span>
							<strong id="bbl-mail-preview-subject"></strong>
						</div>
						<div class="bbl-mailview__body" id="bbl-mail-preview-body"></div>
					</div>
					<p class="bbl-modal__actions">
						<button type="button" class="button button-primary" id="bbl-mail-preview-close">Fechar</button>
					</p>
				</div>
			</div>

			<p class="bbl-actions">
				<button type="submit" class="button button-primary">Salvar configurações</button>
			</p>
		</form>

		<div class="bbl-card">
			<h2>Testar envio</h2>
			<p class="description">Salve as configurações antes de testar.</p>
			<p class="bbl-actions">
				<input type="email" id="bbl-test-to" class="regular-text" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>">
				<button type="button" class="button" id="bbl-test-email">Enviar mensagem de teste</button>
				<span class="bbl-feedback" id="bbl-test-feedback"></span>
			</p>
		</div>

	<?php elseif ( 'senha' === $tab ) : ?>

		<form method="post">
			<?php wp_nonce_field( 'bbl_users_save_password' ); ?>
			<input type="hidden" name="bbl_users_action" value="save_password">

			<div class="bbl-card">
				<h2>Senha gerada automaticamente</h2>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="pass-length">Comprimento</label></th>
						<td>
							<input type="number" id="pass-length" name="password[length]" class="small-text" min="6" max="64" value="<?php echo (int) $pass['length']; ?>">
							<span class="description">Entre 6 e 64 caracteres.</span>
						</td>
					</tr>
					<tr>
						<th scope="row">Caracteres</th>
						<td>
							<label><input type="checkbox" id="pass-lower" name="password[lower]" value="1" <?php checked( $pass['lower'], 1 ); ?>> Minúsculas <code>a-z</code></label><br>
							<label><input type="checkbox" id="pass-upper" name="password[upper]" value="1" <?php checked( $pass['upper'], 1 ); ?>> Maiúsculas <code>A-Z</code></label><br>
							<label><input type="checkbox" id="pass-numbers" name="password[numbers]" value="1" <?php checked( $pass['numbers'], 1 ); ?>> Números <code>0-9</code></label><br>
							<label><input type="checkbox" id="pass-symbols" name="password[symbols]" value="1" <?php checked( $pass['symbols'], 1 ); ?>> Símbolos</label>
							<p class="description">A senha sempre traz ao menos um caractere de cada tipo marcado.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="pass-symbols-set">Símbolos permitidos</label></th>
						<td>
							<input type="text" id="pass-symbols-set" name="password[symbols_set]" class="regular-text code" value="<?php echo esc_attr( $pass['symbols_set'] ); ?>">
							<p class="description">Evite aspas e barras invertidas, que costumam quebrar ao copiar e colar.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">Ambiguidade</th>
						<td>
							<label>
								<input type="checkbox" id="pass-no-ambiguous" name="password[no_ambiguous]" value="1" <?php checked( $pass['no_ambiguous'], 1 ); ?>>
								Não usar caracteres parecidos entre si
							</label>
							<p class="description">Tira <code>0 O 1 l I |</code> e aspas, que geram erro de digitação quando a pessoa copia da mensagem.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">Amostra</th>
						<td>
							<button type="button" class="button" id="bbl-pass-preview">Gerar exemplos</button>
							<div id="bbl-pass-samples" class="bbl-samples"></div>
						</td>
					</tr>
				</table>
			</div>

			<p class="bbl-actions">
				<button type="submit" class="button button-primary">Salvar configurações</button>
			</p>
		</form>

	<?php else : ?>

		<div class="bbl-card">
			<h2>Ambiente</h2>

			<table class="widefat striped bbl-table">
				<tbody>
					<?php foreach ( BBL_Users_Diagnostics::run() as $check ) : ?>
						<tr>
							<td style="width:26%"><strong><?php echo esc_html( $check['label'] ); ?></strong></td>
							<td style="width:12%"><span class="bbl-badge bbl-badge--<?php echo esc_attr( $check['status'] ); ?>">
								<?php
								$labels = array(
									'ok'    => 'OK',
									'aviso' => 'Atenção',
									'erro'  => 'Erro',
								);
								echo esc_html( $labels[ $check['status'] ] ?? $check['status'] );
								?>
							</span></td>
							<td><?php echo esc_html( $check['detail'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<div class="bbl-card">
			<h2>Teste de criação</h2>
			<p class="description">
				Cria um usuário descartável e o remove em seguida. É a forma de confirmar que o INSERT
				atravessa a VIEW da <code>wp_users</code> antes de rodar um lote grande.
				Se aparecer erro de permissão, é o GRANT cruzado do MySQL que ainda falta.
			</p>
			<p class="bbl-actions">
				<button type="button" class="button" id="bbl-test-insert">Executar teste</button>
				<span class="bbl-feedback" id="bbl-insert-feedback"></span>
			</p>
		</div>

	<?php endif; ?>

</div>
