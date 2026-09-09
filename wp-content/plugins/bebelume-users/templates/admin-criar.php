<?php
/**
 * Tela: Criar usuários.
 *
 * Aqui a ação é destrutiva no sentido de que dispara e-mail. Por isso o passo
 * de confirmação e o processamento em blocos.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rows        = BBL_Users_DB::get_all();
$counts      = BBL_Users_DB::count_by_status();
$pendentes   = (int) $counts[ BBL_Users_DB::STATUS_RASCUNHO ];
$levels      = BBL_Users_Diagnostics::get_levels();
$smtp_ok     = BBL_Users_Mailer::is_configured();
$pass_opts   = BBL_Users_Password::get_options();
$processados = (int) $counts[ BBL_Users_DB::STATUS_CRIADO ] + (int) $counts[ BBL_Users_DB::STATUS_EXISTENTE ];
?>
<div class="wrap bbl-users">

	<h1>Criar usuários</h1>
	<?php BBL_Users_Admin::notice(); ?>

	<?php if ( ! $smtp_ok ) : ?>
		<div class="notice notice-error">
			<p>
				O envio de e-mail ainda não está configurado, e a criação depende dele.
				<a href="<?php echo esc_url( BBL_Users_Admin::url( BBL_Users_Admin::SLUG . '-config', array( 'tab' => 'email' ) ) ); ?>">Configurar agora</a>.
			</p>
		</div>
	<?php endif; ?>

	<div class="bbl-card">
		<h2>Resumo do lote</h2>

		<div class="bbl-stats">
			<div class="bbl-stat">
				<span class="bbl-stat__num"><?php echo (int) $pendentes; ?></span>
				<span class="bbl-stat__label">no rascunho</span>
			</div>
			<div class="bbl-stat">
				<span class="bbl-stat__num"><?php echo (int) $counts[ BBL_Users_DB::STATUS_CRIADO ]; ?></span>
				<span class="bbl-stat__label">criados</span>
			</div>
			<div class="bbl-stat">
				<span class="bbl-stat__num"><?php echo (int) $counts[ BBL_Users_DB::STATUS_EXISTENTE ]; ?></span>
				<span class="bbl-stat__label">já existiam</span>
			</div>
			<div class="bbl-stat bbl-stat--erro">
				<span class="bbl-stat__num"><?php echo (int) $counts[ BBL_Users_DB::STATUS_ERRO ]; ?></span>
				<span class="bbl-stat__label">com erro</span>
			</div>
		</div>

		<?php if ( $pendentes > 0 ) : ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="bbl-level">Nível PMPro do lote</label></th>
					<td>
						<?php if ( empty( $levels ) ) : ?>
							<p class="description">Nenhum nível disponível. Os usuários serão criados sem associação.</p>
							<input type="hidden" id="bbl-level" value="0">
						<?php else : ?>
							<select id="bbl-level">
								<option value="0">Não atribuir nível</option>
								<?php foreach ( $levels as $level ) : ?>
									<option value="<?php echo (int) $level->id; ?>"><?php echo esc_html( $level->name ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description">Vale para todas as linhas deste lote, e só neste site.</p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row">Senha gerada</th>
					<td>
						<p class="description">
							<?php
							$partes = array();
							if ( $pass_opts['lower'] ) {
								$partes[] = 'minúsculas';
							}
							if ( $pass_opts['upper'] ) {
								$partes[] = 'maiúsculas';
							}
							if ( $pass_opts['numbers'] ) {
								$partes[] = 'números';
							}
							if ( $pass_opts['symbols'] ) {
								$partes[] = 'símbolos';
							}
							printf(
								'%d caracteres com %s. ',
								(int) $pass_opts['length'],
								esc_html( implode( ', ', $partes ) )
							);
							?>
							<a href="<?php echo esc_url( BBL_Users_Admin::url( BBL_Users_Admin::SLUG . '-config', array( 'tab' => 'senha' ) ) ); ?>">Alterar</a>
						</p>
					</td>
				</tr>
			</table>

			<p class="bbl-actions">
				<button type="button" class="button button-primary button-hero" id="bbl-create" <?php disabled( ! $smtp_ok ); ?>>
					Criar <?php echo (int) $pendentes; ?> usuário(s) e enviar e-mail
				</button>
			</p>
		<?php else : ?>
			<p class="bbl-empty">
				Nada pendente no rascunho.
				<a href="<?php echo esc_url( BBL_Users_Admin::url() ); ?>">Adicionar usuários</a>.
			</p>
		<?php endif; ?>

		<div id="bbl-progress" class="bbl-progress" hidden>
			<div class="bbl-progress__bar"><div class="bbl-progress__fill" id="bbl-progress-fill"></div></div>
			<p class="bbl-progress__label" id="bbl-progress-label"></p>
		</div>
	</div>

	<?php if ( ! empty( $rows ) ) : ?>
		<div class="bbl-card">
			<h2>Linhas</h2>

			<table class="widefat striped bbl-table" id="bbl-create-table">
				<thead>
					<tr>
						<th style="width:24%">Nome</th>
						<th style="width:26%">E-mail</th>
						<th style="width:14%">Situação</th>
						<th style="width:14%">Login</th>
						<th>Observação</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<tr data-id="<?php echo (int) $row['id']; ?>" data-status="<?php echo esc_attr( $row['status'] ); ?>">
							<td><?php echo esc_html( $row['nome'] ); ?></td>
							<td><?php echo esc_html( $row['email'] ); ?></td>
							<td class="bbl-cell-status"><?php echo wp_kses_post( BBL_Users_Admin::status_badge( $row['status'] ) ); ?></td>
							<td class="bbl-cell-login"><code><?php echo esc_html( $row['username'] ); ?></code></td>
							<td class="bbl-cell-msg"><?php echo esc_html( $row['erro'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( $processados > 0 ) : ?>
				<form method="post" class="bbl-actions" onsubmit="return confirm('Remover da lista as linhas já processadas? Os usuários criados continuam existindo.');">
					<?php wp_nonce_field( 'bbl_users_limpar_processados' ); ?>
					<input type="hidden" name="bbl_users_action" value="limpar_processados">
					<button type="submit" class="button">Limpar linhas já processadas</button>
				</form>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div id="bbl-modal" class="bbl-modal" hidden>
		<div class="bbl-modal__box" role="dialog" aria-modal="true" aria-labelledby="bbl-modal-title">
			<h2 id="bbl-modal-title">Confirmar criação</h2>
			<p id="bbl-modal-text"></p>
			<ul class="bbl-modal__list">
				<li>Cada pessoa recebe um e-mail com o link de primeiro acesso para definir a própria senha.</li>
				<li>O e-mail sai imediatamente e não tem como cancelar depois.</li>
				<li>Quem já tiver conta mantém a senha atual e só recebe o nível.</li>
			</ul>
			<p class="bbl-modal__actions">
				<button type="button" class="button" id="bbl-modal-cancel">Cancelar</button>
				<button type="button" class="button button-primary" id="bbl-modal-confirm">Criar e enviar</button>
			</p>
		</div>
	</div>

</div>
