<?php
/**
 * Template: Confirmation (Bebelume - Versão Profissional)
 * Version: 3.1
 * 
 * Template customizado para página de confirmação
 * Design profissional com visual seguro e confiável
 *
 * @version 3.1
 */
global $wpdb, $pmpro_invoice, $pmpro_msg, $pmpro_msgt;

// Se não há invoice, mostrar erro
if ( empty( $pmpro_invoice ) ) {
	$pmpro_msg = __( 'Houve um erro ao recuperar seu pedido. Por favor, entre em contato com o suporte.', 'paid-memberships-pro' );
	$pmpro_msgt = 'pmpro_error';
}
?>


<div class="pmpro white-bkg">
	<?php
	// Mostrar mensagem de erro se houver
	if ( $pmpro_msg ) {
		?>
		<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ) ); ?>">
			<?php echo wp_kses_post( $pmpro_msg ); ?>
		</div>
		<?php
	}

	// Se temos um pedido válido
	if ( ! empty( $pmpro_invoice ) ) {
		$pmpro_invoice->getUser();
		$pmpro_invoice->getMembershipLevel();
		
		if ( ! empty( $pmpro_invoice->user ) && ! empty( $pmpro_invoice->membership_level ) ) {
			$pmpro_invoice->user->membership_level = $pmpro_invoice->membership_level;
		}

		// Verificar se há trial
		$delay_days = 0;
		$plugin_delay = get_option('pmpro_level_' . $pmpro_invoice->membership_id . '_delay_days', 0);
		if ($plugin_delay > 0) {
			$delay_days = $plugin_delay;
		}
		
		// Calcular data da primeira cobrança
		$first_charge_date = '';
		$billing_period_text = '';
		if ($delay_days > 0) {
			$first_charge_date = date_i18n('d/m/Y', strtotime('+' . $delay_days . ' days'));
			
			// Texto do período de cobrança
			if ($pmpro_invoice->membership_level->cycle_period == 'Year') {
				$billing_period_text = 'por ano';
			} elseif ($pmpro_invoice->membership_level->cycle_period == 'Month') {
				$billing_period_text = 'por mês';
			}
		}
		?>

		<!-- Hero de Sucesso -->
		<div class="bbl-confirmation-hero">
			<div class="bbl-confirmation-hero-content">
				<div class="bbl-success-icon">
					<i class="bi bi-check-circle-fill"></i>
				</div>
				
				<?php if ( 'success' == $pmpro_invoice->status ) { ?>
					<h1>🎉 Bem-vindo à Bebelume!</h1>
					<p>Sua assinatura <strong><?php echo esc_html($pmpro_invoice->membership_level->name); ?></strong> está ativa</p>
				<?php } else { ?>
					<h1>✅ Pedido Recebido!</h1>
					<p>Sua assinatura será ativada assim que o pagamento for confirmado</p>
				<?php } ?>
			</div>
		</div>

		<?php
		// Card de informações do trial (se houver)
		if ($delay_days > 0 && !pmpro_isLevelFree($pmpro_invoice->membership_level)) {
			?>
			<div class="bbl-info-card">
				<div class="bbl-info-card-header">
					<div class="bbl-info-card-icon">
						<i class="bi bi-gift-fill"></i>
					</div>
					<h2 class="bbl-info-card-title">Período de Degustação</h2>
				</div>
				
				<div class="bbl-info-grid">
					<div class="bbl-info-item">
						<span class="bbl-info-label">Dias Grátis</span>
						<span class="bbl-info-value"><?php echo esc_html($delay_days); ?> dias</span>
					</div>
					
					<div class="bbl-info-item">
						<span class="bbl-info-label">Primeira Cobrança</span>
						<span class="bbl-info-value"><?php echo esc_html($first_charge_date); ?></span>
					</div>
					
					<div class="bbl-info-item">
						<span class="bbl-info-label">Valor</span>
						<span class="bbl-info-value">
							<?php echo pmpro_escape_price(pmpro_formatPrice($pmpro_invoice->membership_level->billing_amount)); ?>
							<?php if ($billing_period_text) echo ' ' . esc_html($billing_period_text); ?>
						</span>
					</div>
				</div>
			</div>
			<?php
		}
		?>

		<!-- Próximos Passos -->
		<div class="bbl-next-steps">
			<h2>
				<i class="bi bi-list-check"></i>
				Próximos Passos
			</h2>
			
			<div class="bbl-steps-grid">
				<div class="bbl-step-card">
					<div class="bbl-step-number">1</div>
					<h3>Verifique seu e-mail</h3>
					<p>Enviamos uma confirmação para <strong><?php echo esc_html($pmpro_invoice->user->user_email); ?></strong> com todos os detalhes da sua assinatura.</p>
				</div>
				
				<div class="bbl-step-card">
					<div class="bbl-step-number">2</div>
					<h3>Acesse sua conta</h3>
					<p>Gerencie sua assinatura, atualize dados de pagamento e muito mais através da sua área de membro.</p>
				</div>
				
				<div class="bbl-step-card">
					<div class="bbl-step-number">3</div>
					<h3>Comece a usar</h3>
					<p>Explore todo o conteúdo exclusivo disponível para membros <?php echo esc_html($pmpro_invoice->membership_level->name); ?>.</p>
				</div>
			</div>
		</div>

		<!-- Botão CTA -->
		<div style="text-align: center;">
			<a href="<?php echo esc_url(pmpro_url('account')); ?>" class="bbl-cta-button">
				<i class="bi bi-person-circle"></i>
				Ir para Minha Conta
			</a>
		</div>

		<!-- Divider -->
		<div class="bbl-divider"></div>

		<?php
	}
	?>
</div>

<?php
// Incluir template de invoice se não for gratuito
if ( ! empty( $pmpro_invoice ) && ! pmpro_isLevelFree( $pmpro_invoice->membership_level ) ) {
	$pmpro_msg = false;
	$pmpro_msgt = false;
	
	// Forçar carregamento do nosso template de invoice
	$custom_invoice_path = plugin_dir_path( __FILE__ ) . 'invoice.php';
	if ( file_exists( $custom_invoice_path ) ) {
		include( $custom_invoice_path );
	} else {
		echo pmpro_loadTemplate( 'invoice' );
	}
}
?>