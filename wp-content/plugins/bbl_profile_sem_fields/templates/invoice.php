<?php 
/**
 * Template: Invoice (Bebelume - Versão Profissional)
 * Version: 3.1
 *
 * Template customizado para fatura/pedido
 * Design profissional, traduzido e com visual seguro
 *
 * @version 3.1
 */
?>


<div class="white-bkg <?php echo esc_attr( pmpro_get_element_class( 'pmpro' ) ); ?>">
	<?php
	global $wpdb, $pmpro_invoice, $pmpro_msg, $pmpro_msgt, $current_user;

	if ( $pmpro_msg ) {
		?>
		<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ) ); ?>">
			<?php echo wp_kses_post( $pmpro_msg ); ?>
		</div>
		<?php
	}

	if ( $pmpro_invoice ) {
		// Buscar dados do pedido
		$pmpro_invoice->getUser();
		$pmpro_invoice->getMembershipLevel();
		
		// Definir status para exibição
		if ( in_array( $pmpro_invoice->status, array( '', 'success', 'cancelled' ) ) ) {
			$display_status = 'Pago';
			$status_class = 'success';
			$status_icon = 'check-circle-fill';
		} elseif ( $pmpro_invoice->status == 'refunded' ) {
			$display_status = 'Reembolsado';
			$status_class = 'refunded';
			$status_icon = 'arrow-counterclockwise';
		} else {
			$display_status = 'Pendente';
			$status_class = 'pending';
			$status_icon = 'clock-fill';
		}
		?>
		
		<!-- Header da Invoice -->
		<div class="bbl-invoice-header">
			<div class="bbl-invoice-title-section">
				<h2>
					<i class="bi bi-receipt"></i>
					Pedido #<?php echo esc_html($pmpro_invoice->code); ?>
				</h2>
				<span class="bbl-status-badge bbl-status-<?php echo esc_attr($status_class); ?>">
					<i class="bi bi-<?php echo esc_attr($status_icon); ?>"></i>
					<?php echo esc_html($display_status); ?>
				</span>
			</div>
			
			<div class="bbl-invoice-actions">
				<button class="bbl-btn bbl-btn-primary" onclick="window.print()">
					<i class="bi bi-printer"></i>
					Imprimir / Salvar PDF
				</button>
			</div>
		</div>

		<!-- Body da Invoice -->
		<div class="bbl-invoice-body">
			
			<!-- Metadados do Pedido -->
			<div class="bbl-invoice-meta">
				<div class="bbl-meta-item">
					<span class="bbl-meta-label">
						<i class="bi bi-calendar3"></i>
						Data do Pedido
					</span>
					<span class="bbl-meta-value">
						<?php echo date_i18n('d/m/Y', $pmpro_invoice->getTimestamp()); ?>
					</span>
				</div>
				
				<div class="bbl-meta-item">
					<span class="bbl-meta-label">
						<i class="bi bi-credit-card"></i>
						Método de Pagamento
					</span>
					<span class="bbl-meta-value">
						<?php
						if ($pmpro_invoice->accountnumber) {
							echo esc_html(ucwords($pmpro_invoice->cardtype)) . ' terminando em ' . esc_html(last4($pmpro_invoice->accountnumber));
						} elseif ($pmpro_invoice->payment_type === 'Check' && !empty(get_option('pmpro_check_gateway_label'))) {
							echo esc_html(get_option('pmpro_check_gateway_label'));
						} elseif (!empty($pmpro_invoice->payment_type)) {
							echo esc_html($pmpro_invoice->payment_type);
						} else {
							echo '—';
						}
						?>
					</span>
				</div>
				
				<div class="bbl-meta-item">
					<span class="bbl-meta-label">
						<i class="bi bi-building"></i>
						Pago Para
					</span>
					<span class="bbl-meta-value">
						<?php
						$business_address = get_option('pmpro_business_address');
						if (!empty($business_address['name'])) {
							echo wp_kses_post(pmpro_formatAddress(
								$business_address['name'],
								$business_address['street'],
								'',
								$business_address['city'],
								$business_address['state'],
								$business_address['zip'],
								$business_address['country'],
								''
							));
						} else {
							echo esc_html(get_option('blogname'));
						}
						?>
					</span>
				</div>
				
				<div class="bbl-meta-item">
					<span class="bbl-meta-label">
						<i class="bi bi-person-circle"></i>
						Faturado Para
					</span>
					<span class="bbl-meta-value">
						<?php
						if ($pmpro_invoice->has_billing_address()) {
							echo wp_kses_post(pmpro_formatAddress(
								$pmpro_invoice->billing->name,
								$pmpro_invoice->billing->street,
								'',
								$pmpro_invoice->billing->city,
								$pmpro_invoice->billing->state,
								$pmpro_invoice->billing->zip,
								$pmpro_invoice->billing->country,
								''
							));
						} else {
							echo esc_html($pmpro_invoice->user->display_name) . '<br>' . esc_html($pmpro_invoice->user->user_email);
						}
						?>
					</span>
				</div>
			</div>

			<!-- Detalhes do Pedido -->
			<h3 class="bbl-invoice-details-title">
				<i class="bi bi-list-ul"></i>
				Detalhes do Pedido
			</h3>

			<?php
			// Buscar partes do preço
			$pmpro_price_parts = pmpro_get_price_parts($pmpro_invoice, 'array');
			if (empty($pmpro_price_parts)) {
				$pmpro_price_parts = array(
					'total' => array(
						'label' => 'Total',
						'value' => $pmpro_invoice->get_formatted_total()
					)
				);
			}

			// Se reembolsado, adicionar
			if ($pmpro_invoice->status == 'refunded') {
				$pmpro_price_parts['refunded'] = array(
					'label' => 'Reembolsado',
					'value' => $pmpro_price_parts['total']['value']
				);
			}

			// Se o nível foi deletado
			if (empty($pmpro_invoice->membership_level)) {
				$pmpro_invoice->membership_level = new stdClass();
				$pmpro_invoice->membership_level->name = 'Nível ID ' . $pmpro_invoice->membership_id;
			}
			?>

			<table class="bbl-invoice-table">
				<thead>
					<tr>
						<th>Descrição</th>
						<th style="text-align: right;">Valor</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
							<strong><?php echo esc_html($pmpro_invoice->membership_level->name); ?></strong><br>
							<span style="color: #718096; font-size: 0.9rem;">
								Pedido #<?php echo esc_html($pmpro_invoice->code); ?>
							</span>
							<?php if (!empty($pmpro_invoice->billing->name)) { ?>
								<br>
								<span style="color: #718096; font-size: 0.9rem;">
									Conta: <?php echo esc_html($pmpro_invoice->user->display_name); ?> (<?php echo esc_html($pmpro_invoice->user->user_email); ?>)
								</span>
							<?php } ?>
							<?php
							$subscription_period_end = pmpro_get_subscription_period_end_date_for_order($pmpro_invoice, 'd/m/Y');
							$order_date = date_i18n('d/m/Y', $pmpro_invoice->getTimestamp());
							if (!empty($subscription_period_end) && $subscription_period_end !== $order_date) {
								?>
								<br>
								<span style="color: #667eea; font-size: 0.9rem;">
									<i class="bi bi-calendar-range"></i>
									<?php echo esc_html($order_date); ?> até <?php echo esc_html($subscription_period_end); ?>
								</span>
								<?php
							}
							?>
						</td>
						<td style="text-align: right;">
							<strong><?php echo pmpro_escape_price($pmpro_invoice->get_formatted_subtotal()); ?></strong>
						</td>
					</tr>
				</tbody>
				<tfoot>
					<?php foreach ($pmpro_price_parts as $key => $part) { ?>
						<tr>
							<td><?php echo esc_html($part['label']); ?></td>
							<td style="text-align: right;">
								<?php echo pmpro_escape_price($part['value']); ?>
							</td>
						</tr>
					<?php } ?>
				</tfoot>
			</table>

			<?php if ($pmpro_invoice->getDiscountCode()) { ?>
				<div class="bbl-discount-badge">
					<i class="bi bi-tag-fill"></i>
					Cupom Aplicado: <?php echo esc_html($pmpro_invoice->discount_code->code); ?>
				</div>
			<?php } ?>

		</div>

		<!-- Footer da Invoice -->
		<div class="bbl-invoice-footer">
			<a href="<?php echo esc_url(pmpro_url('invoice')); ?>">
				<i class="bi bi-arrow-left"></i>
				Ver Todos os Pedidos
			</a>
			<a href="<?php echo esc_url(pmpro_url('account')); ?>">
				Ver Minha Conta
				<i class="bi bi-arrow-right"></i>
			</a>
		</div>

		<?php
	} else {
		// Lista de pedidos
		$orders = MemberOrder::get_orders(
			array(
				'status' => array('pending', 'refunded', 'success'),
				'user_id' => $current_user->ID,
			)
		);
		?>
		
		<!-- Header da Lista -->
		<div class="bbl-order-list-header">
			<h2>
				<i class="bi bi-card-list"></i>
				Histórico de Pedidos
			</h2>
		</div>

		<?php if ($orders) { ?>
			<table class="bbl-orders-table">
				<thead>
					<tr>
						<th>Data</th>
						<th>Plano</th>
						<th>Total</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($orders as $order) {
						$order_id = $order->id;
						$order = new MemberOrder;
						$order->getMemberOrderByID($order_id);
						$order->getMembershipLevel();

						// Status
						if (in_array($order->status, array('', 'success', 'cancelled'))) {
							$display_status = 'Pago';
							$status_class = 'success';
							$status_icon = 'check-circle-fill';
						} elseif ($order->status == 'pending') {
							$display_status = 'Pendente';
							$status_class = 'pending';
							$status_icon = 'clock-fill';
						} elseif ($order->status == 'refunded') {
							$display_status = 'Reembolsado';
							$status_class = 'refunded';
							$status_icon = 'arrow-counterclockwise';
						}
						?>
						<tr>
							<td>
								<a href="<?php echo esc_url(pmpro_url('invoice', '?invoice=' . $order->code)); ?>">
									<?php echo date_i18n('d/m/Y', $order->getTimestamp()); ?>
								</a>
							</td>
							<td>
								<?php echo !empty($order->membership_level) ? esc_html($order->membership_level->name) : 'N/A'; ?>
							</td>
							<td><?php echo pmpro_escape_price($order->get_formatted_total()); ?></td>
							<td>
								<span class="bbl-status-badge bbl-status-<?php echo esc_attr($status_class); ?>">
									<i class="bi bi-<?php echo esc_attr($status_icon); ?>"></i>
									<?php echo esc_html($display_status); ?>
								</span>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		<?php } else { ?>
			<div style="text-align: center; padding: 3rem; color: #718096;">
				<i class="bi bi-inbox" style="font-size: 3rem; color: #cbd5e0; margin-bottom: 1rem;"></i>
				<p>Nenhum pedido encontrado.</p>
			</div>
		<?php } ?>

		<!-- Footer -->
		<div class="bbl-invoice-footer" style="border-top: none; margin-top: 2rem;">
			<div></div>
			<a href="<?php echo esc_url(pmpro_url('account')); ?>">
				Ver Minha Conta
				<i class="bi bi-arrow-right"></i>
			</a>
		</div>

		<?php
	}
	?>
</div>