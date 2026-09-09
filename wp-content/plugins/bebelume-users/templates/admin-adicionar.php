<?php
/**
 * Tela: Adicionar usuários.
 *
 * Salva rascunho. Não cria usuário, não gera senha, não envia e-mail.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$drafts = BBL_Users_DB::get_all( BBL_Users_DB::STATUS_RASCUNHO );
$counts = BBL_Users_DB::count_by_status();
?>
<div class="wrap bbl-users">

	<h1>Adicionar usuários</h1>
	<?php BBL_Users_Admin::notice(); ?>

	<p class="bbl-lead">
		Cole a lista abaixo para salvar no rascunho. Nada é criado aqui: os usuários só passam a existir
		quando você confirmar na tela <a href="<?php echo esc_url( BBL_Users_Admin::url( BBL_Users_Admin::SLUG . '-criar' ) ); ?>">Criar usuários</a>.
	</p>

	<div class="bbl-card">
		<h2>Lista</h2>
		<p class="description">
			Uma pessoa por linha, no formato <code>Nome, email@dominio.com</code>.
			Também funciona com ponto e vírgula, tabulação (colando direto de uma planilha) ou <code>Nome &lt;email@dominio.com&gt;</code>.
		</p>

		<textarea id="bbl-bulk" rows="10" class="large-text code" placeholder="Maria Souza, maria@exemplo.com
João Pereira, joao@exemplo.com"></textarea>

		<p class="bbl-actions">
			<button type="button" class="button" id="bbl-preview">Conferir lista</button>
			<button type="button" class="button button-primary" id="bbl-add" disabled>Salvar no rascunho</button>
			<span class="bbl-feedback" id="bbl-add-feedback"></span>
		</p>

		<div id="bbl-preview-box" class="bbl-preview" hidden>
			<h3>Conferência</h3>
			<div id="bbl-preview-summary" class="bbl-summary"></div>
			<table class="widefat striped bbl-table">
				<thead>
					<tr>
						<th style="width:34%">Nome</th>
						<th style="width:38%">E-mail</th>
						<th>Situação</th>
					</tr>
				</thead>
				<tbody id="bbl-preview-rows"></tbody>
			</table>
		</div>
	</div>

	<div class="bbl-card">
		<h2>
			Rascunho atual
			<span class="bbl-count"><?php echo (int) $counts[ BBL_Users_DB::STATUS_RASCUNHO ]; ?></span>
		</h2>

		<?php if ( empty( $drafts ) ) : ?>
			<p class="bbl-empty">Nenhuma pessoa no rascunho ainda. Cole a lista acima para começar.</p>
		<?php else : ?>
			<table class="widefat striped bbl-table" id="bbl-draft-table">
				<thead>
					<tr>
						<th style="width:32%">Nome</th>
						<th style="width:36%">E-mail</th>
						<th style="width:18%">Situação</th>
						<th style="width:14%">Ações</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $drafts as $row ) : ?>
						<?php $ja_existe = (bool) get_user_by( 'email', $row['email'] ); ?>
						<tr data-id="<?php echo (int) $row['id']; ?>">
							<td><input type="text" class="regular-text bbl-field-nome" value="<?php echo esc_attr( $row['nome'] ); ?>"></td>
							<td><input type="email" class="regular-text bbl-field-email" value="<?php echo esc_attr( $row['email'] ); ?>"></td>
							<td class="bbl-row-status">
								<?php if ( $ja_existe ) : ?>
									<span class="bbl-badge bbl-badge--existente">Já tem conta</span>
								<?php else : ?>
									<span class="bbl-badge bbl-badge--rascunho">Novo</span>
								<?php endif; ?>
							</td>
							<td>
								<button type="button" class="button button-small bbl-save-row">Salvar</button>
								<button type="button" class="button button-small button-link-delete bbl-delete-row">Remover</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<p class="bbl-actions">
				<a class="button button-primary" href="<?php echo esc_url( BBL_Users_Admin::url( BBL_Users_Admin::SLUG . '-criar' ) ); ?>">
					Ir para Criar usuários
				</a>
			</p>
		<?php endif; ?>
	</div>

</div>
