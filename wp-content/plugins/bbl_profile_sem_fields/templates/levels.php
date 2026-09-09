<?php

/**
 * Template: Levels
 * Version: 3.1
 *
 * See documentation for how to override the PMPro templates.
 * @link https://www.paidmembershipspro.com/documentation/templates/
 *
 * @version 3.1
 *
 * @author Paid Memberships Pro
 */
global $wpdb, $pmpro_msg, $pmpro_msgt, $current_user;

$pmpro_levels = pmpro_sort_levels_by_order(pmpro_getAllLevels(false, true));
$pmpro_levels = apply_filters('pmpro_levels_array', $pmpro_levels);

$level_groups = pmpro_get_level_groups_in_order();

?>
<div class="bbl-pmpro-levels <?php echo esc_attr(pmpro_get_element_class('pmpro')); ?>">
	<?php
	if ($pmpro_msg) {
		?>
		<div class="<?php echo esc_attr(pmpro_get_element_class('pmpro_message ' . $pmpro_msgt, $pmpro_msgt)); ?>">
			<?php echo wp_kses_post($pmpro_msg); ?></div>
		<?php
	}
	?>



	<section class="wp-block-bebelume-telhado-passarinhos-bebelume bebelume-nivel" style="position:relative">
		<div class="clouds-container">
			<div class="cloud cloud-1"></div>
			<div class="cloud cloud-2"></div>
			<div class="cloud cloud-3"></div>
		</div>
		<div class="bebelume-andar telhado-passarinhos-bebelume">
			<div class="andar-content d-flex p-5">
				<h1 class="my-auto" style="font-weight: 800">
					BEBELUME É A PRIMEIRA PLATAFORMA DE STREAMING DE ARTE PARA BEBÊS, EM UM AMBIENTE 100% SEGURO
				</h1>
			</div>
		</div>
	</section>

	<section class="wp-block-bebelume-andar-casinha-bebelume bebelume-nivel w-100 branco">
		<div class="clouds-container">
			<div class="cloud cloud-1"></div>
			<div class="cloud cloud-2"></div>
			<div class="cloud cloud-3"></div>
		</div>
		<div class="bebelume-andar andar-casinha-bebelume  has-image">
			<div class="andar-content p-0">
				<video class="bebelume-video" playsinline controls preload="metadata"
					data-poster="https://bebelume.com.br/wp-content/uploads/2025/11/302551532_373126645025277_6500816978032902797_n.png"
					style="width: 100%; aspect-ratio: 16/9; object-fit: contain; background: #000;">
					<source
						src="https://player.vimeo.com/progressive_redirect/playback/1167487738/rendition/1080p/file.mp4%20%281080p%29.mp4?loc=external&signature=ad1a5c2835e8a1eac89a98cf674ee7c755b728c5697cef30a8d9551712718711"
						type="video/mp4">
					Seu navegador não suporta vídeos HTML5.
				</video>
			</div>
		</div>
	</section>
	
	<section class="wp-block-bebelume-andar-casinha-bebelume bebelume-nivel w-100 branco">
		<div class="bebelume-andar andar-casinha-bebelume has-image">
			<div class="andar-content">
				<div class="container">
					<div class="row">
						<div class="col-md-6 col-sm-12 d-flex">
							<h1 class="pt-3 pt-md-0 bebelume-pink-title bebelume-800-bold-text my-auto text-center">
								NUTRA SEU BEBÊ COM ARTE, EM UM LUGAR SEGURO.
							</h1>
						</div>
						<div class="col-md-6 col-sm-12">
							<figure class="wp-block-image size-large"><a href="#"
								rel="Bebelume brincando na cama"><img fetchpriority="high" decoding="async" width="1024"
									src="https://bebelume.com.br/wp-content/uploads/2026/02/bebelume-cama.png"
									alt="Bebê sentado em uma cama brincando com blocos coloridos enquanto assiste TV com o logotipo Bebê Lume na tela."
									sizes="(max-width: 1024px) 100vw, 1024px"></a></figure>

						</div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section class="wp-block-bebelume-andar-casinha-bebelume bebelume-nivel w-100 branco">
		<div class="bebelume-andar andar-casinha-bebelume has-image">
			<div class="andar-content">
				<div class="container">
					<div class="row">

						<?php
						// Pega todos os níveis
						$contador = 0; // Contador para alternar cores
						foreach ($pmpro_levels as $level) {
							$user_level = pmpro_getSpecificMembershipLevelForUser($current_user->ID, $level->id);
							$has_level = !empty($user_level);

							// Classes para estilização
							$element_classes = array('nivel-item');
							if ($has_level) {
								$element_classes[] = 'nivel-atual';
							}

							// Alterna classe de cor de fundo
							$classe_cor = ($contador % 2 == 0) ? 'pmpro-pink-bkg' : 'pmpro-yellow-bkg';
							$contador++;

							// Determina se é MENSAL ou ANUAL
							$tipo_plano = '';
							$periodo_texto = '';
							$eh_mensal = (strtolower($level->cycle_period) == 'month');
							$eh_anual = (strtolower($level->cycle_period) == 'year');

							if ($eh_mensal) {
								$tipo_plano = 'MENSAL';
								$periodo_texto = 'MÊS';
							} elseif ($eh_anual) {
								$tipo_plano = 'ANUAL';
								$periodo_texto = 'ANO';
							}

							// Formata o preço
							$preco_formatado = number_format($level->billing_amount, 2, ',', '.');

							// ✅ CALCULA DIAS GRÁTIS
							$dias_gratis = 0;
							$pagamento_inicial_zero = (floatval($level->initial_payment) == 0);

							// Pega o delay diretamente do banco de dados (plugin bebelume-subscription-delay)
							$delay_configurado = get_option('pmpro_level_' . $level->id . '_delay_days', 0);
							$delay_configurado = intval($delay_configurado);

							// Se tem delay configurado no plugin
							if ($delay_configurado > 0) {
								$dias_gratis = $delay_configurado;
							}
							// Se não tem delay mas initial_payment = 0 e é mensal, automaticamente tem 30 dias grátis
							elseif ($pagamento_inicial_zero && $eh_mensal) {
								$dias_gratis = 30;
							}

							// Texto da mensagem de trial
							$mensagem_trial = '';
							if ($dias_gratis > 0) {
								$mensagem_trial = 'Experimente ' . $dias_gratis . ' dias gratuitamente';
							}
							?>

							<div class="col-md-6 col-sm-12 <?php echo esc_attr($classe_cor); ?>">
								<div id="nivel-<?php echo esc_attr($level->id); ?>"
									class="<?php echo esc_attr(implode(' ', $element_classes)); ?> p-3">

									<!-- Tipo do Plano (MENSAL/ANUAL) -->
									<?php if (!empty($tipo_plano)) { ?>
										<p class="nivel-tipo"><?php echo esc_html($tipo_plano); ?></p>
									<?php } ?>

									<!-- Preço Reformatado -->
									<div class="nivel-preco-novo">
										<p class="preco-valor">R$ <?php echo esc_html($preco_formatado); ?></p>
										<?php if (!empty($periodo_texto)) { ?>
											<p class="preco-periodo">/POR <?php echo esc_html($periodo_texto); ?></p>
										<?php } ?>
									</div>

									<!-- ✅ NOVO: Mensagem de Trial -->
									<?php if (!empty($mensagem_trial)) { ?>
										<div class="nivel-trial">
											<p><?php echo esc_html($mensagem_trial); ?></p>
										</div>
									<?php } ?>

									<?php
									// Mostra expiração se houver
									$expiration_text = pmpro_getLevelExpiration($level);
									if (!empty($expiration_text)) {
										?>
										<p class="nivel-expiracao"><?php echo wp_kses_post($expiration_text); ?></p>
										<?php
									}
									?>

									<!-- Botão de Ação (SIMPLIFICADO) -->
									<div class="nivel-acao">
										<?php if (!$has_level) { ?>
											<a aria-label="Selecionar"
												class="<?php echo esc_attr(pmpro_get_element_class('pmpro_btn pmpro_btn-select', 'pmpro_btn-select')); ?>"
												href="<?php echo esc_url(pmpro_url("checkout", "?pmpro_level=" . $level->id, "https")) ?>">
												Selecionar
											</a>
										<?php } else { ?>
											<?php
											// Se está expirando em breve, oferece renovação
											if (pmpro_isLevelExpiringSoon($user_level) && $level->allow_signups) {
												?>
												<a aria-label="<?php echo esc_attr(sprintf(__('Renovar seu nível %s', 'paid-memberships-pro'), $level->name)); ?>"
													class="<?php echo esc_attr(pmpro_get_element_class('pmpro_btn pmpro_btn-renew pmpro_btn-select', 'pmpro_btn-select')); ?>"
													href="<?php echo esc_url(pmpro_url("checkout", "?pmpro_level=" . $level->id, "https")) ?>">
													<?php esc_html_e('Renovar', 'paid-memberships-pro'); ?>
												</a>
												<?php
											} else {
												?>
												<a aria-label="<?php echo esc_attr(sprintf(__('Ver sua conta do nível %s', 'paid-memberships-pro'), $level->name)); ?>"
													class="<?php echo esc_attr(pmpro_get_element_class('pmpro_btn pmpro_btn-outline', 'pmpro_btn')); ?>"
													href="<?php echo esc_url(pmpro_url("account")) ?>">
													<?php esc_html_e('Seu Nível', 'paid-memberships-pro'); ?>
												</a>
												<?php
											}
											?>
										<?php } ?>
									</div>
								</div>
							</div>

						<?php } ?>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section class="wp-block-bebelume-andar-casinha-bebelume bebelume-nivel w-100 branco">
		<div class="bebelume-andar andar-casinha-bebelume has-image">
			<div class="andar-content">
				<div class="container">
					<div class="row p-3">
						<div class="col-md-6 col-sm-12 ">
							<figure class="wp-block-image size-large "><a href="#"
								rel="Bebelume brincando na cama" class="d-flex">
								<img fetchpriority="high" decoding="async" 
									style="width: 70%;"
									src="https://bebelume.com.br/wp-content/uploads/2026/02/Bebelume-TV.png"
									alt="TV com o logotipo Bebê Lume na tela."
									sizes="(max-width: 1024px) 100vw, 1024px"
									class="mx-auto">
								</a></figure>
						</div>
						<div class="col-md-6 col-sm-12 d-flex">
							<h1 class="bebelume-cyan-title bebelume-800-bold-text my-auto text-center">
								SE AVENTURE COM O MUNDO MÁGICO DO COTIDIANO, O SENSÍVEL E O INVISÍVEL, O DRAMA E A CALMA PARA SEU BEBÊ
						</h1>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>


	<section class="wp-block-bebelume-andar-casinha-bebelume bebelume-nivel w-100 branco">
		<div class="bebelume-andar andar-casinha-bebelume has-image">
			<div class="andar-content container">

			</div>
		</div>
	</section>


	<section class="wp-block-bebelume-terreo-casinha-bebelume bebelume-nivel w-100 branco">
		<div class="garden-container">
			<div class="colinas">
				<div class="colina colina1"></div>
				<div class="colina colina2"></div>
				<div class="colina colina3"></div>
			</div>
			<div class="campo"></div>
			<div class="borboleta borboleta1">
				<div class="asa asa-esquerda"></div>
				<div class="asa asa-direita"></div>
				<div class="corpo-borboleta"></div>
			</div>
			<div class="borboleta borboleta2">
				<div class="asa asa-esquerda"></div>
				<div class="asa asa-direita"></div>
				<div class="corpo-borboleta"></div>
			</div>
			<div class="borboleta borboleta3">
				<div class="asa asa-esquerda"></div>
				<div class="asa asa-direita"></div>
				<div class="corpo-borboleta"></div>
			</div>
			<div class="flores-container">
				<div class="arbustos">
					<div class="arbusto" style="left:0%"></div>
					<div class="arbusto" style="left:10%;bottom:-6em"></div>
					<div class="arbusto" style="left:80%"></div>
					<div class="arbusto" style="left:90%;bottom:-6em"></div>
				</div>
				<div class="plantinhas">
					<div class="plantinha-bebelume" style="left:5%"></div>
					<div class="plantinha-bebelume" style="left:20%"></div>
					<div class="plantinha-bebelume" style="left:35%"></div>
					<div class="plantinha-bebelume" style="left:50%"></div>
					<div class="plantinha-bebelume" style="left:65%"></div>
					<div class="plantinha-bebelume" style="left:80%"></div>
				</div>
			</div>
		</div>

		<?php if (!is_user_logged_in()): ?>
			<div class="bebelume-andar andar-registro-bebelume">
				<div class="andar-content"
					style="background: linear-gradient(135deg, #00BCD4 0%, #0097A7 100%); padding: 60px 20px;">
					<div class="container">
						<div class="row">
							<div class="col-md-12 col-sm-12 text-center">
								<div class="bbl-register-cta">
									<div class="bbl-register-icon"
										style="width: 80px; height: 80px; margin: 0 auto 20px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
										<i class="bi bi-person-plus-fill" style="font-size: 40px; color: white;"></i>
									</div>
									<h3 style="color: white; font-size: 2rem; margin: 20px 0; font-weight: 800;">Ainda não
										tem uma conta?</h3>
									<p style="color: rgba(255,255,255,0.95); font-size: 1.2rem; margin-bottom: 30px;">Crie
										sua conta gratuita agora e explore tudo que temos a oferecer!</p>
									<a href="<?php echo home_url('/auth/registro/'); ?>" class="bbl-register-cta-btn"
										style="background: white; color: #00BCD4; padding: 15px 40px; border-radius: 50px; text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 10px; transition: all 0.3s ease; font-size: 1.1rem; box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
										<i class="bi bi-rocket-takeoff-fill"></i> Criar Conta Gratuita
									</a>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<div class="bebelume-andar terreo-casinha-bebelume  has-image">
			<div class="andar-content">
				<div class="row bebelume-level-teasers">
					<div class="col-md-12 col-sm-12">
						<p class="bebelume-cyan-rib-text">Veja o resumo de algumas séries</p>
					</div>

					<div class="col-md-6 col-sm-12 p-3">
						<div class="bebelume-video-container ratio-16-9">
							<video class="bebelume-video" playsinline controls preload="metadata"
								data-poster="https://bebelume.com.br/wp-content/uploads/2025/12/videoframe_18640.png">
								<source
									src="https://player.vimeo.com/progressive_redirect/playback/1137304087/rendition/720p/file.mp4?loc=external&signature=b4e2a1bab698b742008502063fde9c62a64ca07105a027d471a8ee569d4c8587"
									type="video/mp4">
								Seu navegador não suporta vídeos HTML5.
							</video>
						</div>
					</div>

					<!-- VÍDEO 1 -->
					<div class="col-md-6 col-sm-12 teaser-text p-3 d-flex">
						<p class="my-auto">
							<strong>INSPIRA FUNDO</strong>
							Utiliza a linguagem do teatro de objetos com muita poesia
							e uma trilha sonora original. Aborda temas como redondo e
							quadrado, sim e não e o corpo. Tudo com muita delicadeza
							e inteligência.
						</p>
					</div>

					<!-- VÍDEO 2 (ordem invertida) -->
					<div class="col-md-6 col-sm-12 p-3">
						<div class="bebelume-video-container ratio-16-9">
							<video class="bebelume-video" playsinline controls preload="metadata"
								data-poster="https://bebelume.com.br/wp-content/uploads/2025/12/videoframe_24647.png">
								<source
									src="https://player.vimeo.com/progressive_redirect/playback/1137255281/rendition/720p/file.mp4?loc=external&signature=dfc65b553522ae93b65d72836f81db4ffcbf36c3b477210775c56c5bb3a47dbf"
									type="video/mp4">
								Seu navegador não suporta vídeos HTML5.
							</video>
						</div>
					</div>

					<div class="col-md-6 col-sm-12 teaser-text p-3 d-flex">
						<p class="my-auto">
							<strong>MAKURU</strong>
							É inspirada nas canções de ninar da cultura popular brasileira. O bebê makuru é um menino
							sapeca que custa para dormir, mas que com as lindas canções se entrega no sono acolhedor.
						</p>
					</div>

				</div>			</div>
		</div>
	</section>



















</div> <!-- end pmpro -->