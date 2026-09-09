<?php
/**
 * Template: Account (Bebelume Customizado - Com Font Awesome)
 * Version: 1.2
 * 
 * Template customizado da página de conta do PMPro
 * Estilo: Bebelume - clean, professional, sober
 */

if (!defined('ABSPATH')) {
    exit;
}

global $current_user, $pmpro_msg, $pmpro_msgt;

// Pegar informações do usuário
$current_user->membership_level = pmpro_getMembershipLevelForUser($current_user->ID);
$membership_levels_to_display = pmpro_getAllLevels(false, true);
$ssorder = new MemberOrder();
$ssorder->getLastMemberOrder($current_user->ID, apply_filters("pmpro_confirmation_order_status", array("success", "pending")));

// Pegar endereço de cobrança
$bfirstname = get_user_meta($current_user->ID, "pmpro_bfirstname", true);
$blastname = get_user_meta($current_user->ID, "pmpro_blastname", true);
$baddress1 = get_user_meta($current_user->ID, "pmpro_baddress1", true);
$baddress2 = get_user_meta($current_user->ID, "pmpro_baddress2", true);
$bcity = get_user_meta($current_user->ID, "pmpro_bcity", true);
$bstate = get_user_meta($current_user->ID, "pmpro_bstate", true);
$bzipcode = get_user_meta($current_user->ID, "pmpro_bzipcode", true);
$bcountry = get_user_meta($current_user->ID, "pmpro_bcountry", true);
$bphone = get_user_meta($current_user->ID, "pmpro_bphone", true);
$bemail = get_user_meta($current_user->ID, "pmpro_bemail", true);

// Verificar se tem informações de cobrança
$has_billing = !empty($bfirstname) || !empty($blastname) || !empty($baddress1);
?>

<!-- Google Fonts - Nunito -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

<!-- Font Awesome CDN -->

<div class="bebelume-account-wrapper">
    
    <?php if ($pmpro_msg): ?>
        <div class="pmpro_message <?php echo esc_attr($pmpro_msgt); ?>">
            <?php echo wp_kses_post($pmpro_msg); ?>
        </div>
    <?php endif; ?>

    <div class="bebelume-account-grid">
        
        <!-- ========== COLUNA ESQUERDA: Informações da Conta ========== -->
        <div class="bebelume-account-main">
            
            <!-- Cabeçalho de Boas-vindas -->
            <div class="bebelume-welcome-header">
                <div class="welcome-avatar">
                    <?php echo get_avatar($current_user->ID, 80); ?>
                </div>
                <div class="welcome-info">
                    <h1><i class="fa-solid fa-hand-wave"></i> Olá, <?php echo esc_html($current_user->display_name); ?>!</h1>
                    <p class="welcome-subtitle">Gerencie sua assinatura e informações da conta</p>
                </div>
            </div>

            <!-- Assinatura Atual -->
            <div class="bebelume-card">
                <div class="card-header">
                    <h2><i class="fa-solid fa-crown"></i> Sua Assinatura</h2>
                </div>
                <div class="card-content">
                    <?php
                    // Pega todos os níveis do usuário
                    $user_levels = pmpro_getMembershipLevelsForUser($current_user->ID);
                    if (!empty($user_levels)):
                    ?>
                        <div class="membership-active">
                            <!-- Grid de até 2 colunas centralizado -->
                            <div class="membership-levels-grid">
                                <?php foreach ($user_levels as $ulevel):
                                    $img_url = Bebelume_PMPro_Level_Fields::get_imagem($ulevel->id, 'large');
                                ?>
                                <?php $pagina_inicio = Bebelume_PMPro_Level_Fields::get_pagina_inicio($ulevel->id); ?>
                                <div class="membership-level-card">
                                    <?php if ($pagina_inicio): ?>
                                        <a href="<?php echo esc_url($pagina_inicio); ?>" class="level-link" target="_blank" rel="noopener">
                                    <?php endif; ?>
                                    <?php if ($img_url): ?>
                                        <div class="level-image">
                                            <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($ulevel->name); ?>" />
                                        </div>
                                    <?php else: ?>
                                        <div class="level-image level-image-placeholder">
                                            <i class="fa-solid fa-crown"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="level-info">
                                        <h3 class="level-name"><?php echo esc_html($ulevel->name); ?></h3>
                                        <?php if (!empty($ulevel->enddate)): ?>
                                            <span class="level-status"><i class="fa-regular fa-calendar"></i> Expira em <?php echo date_i18n(get_option('date_format'), $ulevel->enddate); ?></span>
                                        <?php else: ?>
                                            <span class="level-status level-status-active"><i class="fa-solid fa-circle-check"></i> Assinatura Ativa</span>
                                        <?php endif; ?>
                                        <span class="level-since"><i class="fa-regular fa-calendar-plus"></i> Membro desde <?php echo date_i18n(get_option('date_format'), $ulevel->startdate); ?></span>
                                    </div>
                                    <?php if ($pagina_inicio): ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="membership-actions">
                                <?php if (!defined("PMPRO_DEFAULT_LEVEL")): ?>
                                    <a href="<?php echo esc_url(pmpro_url("cancel")); ?>" class="btn btn-outline">
                                        <i class="fa-solid fa-xmark"></i> Cancelar Assinatura
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($ssorder->id)): ?>
                                    <a href="<?php echo esc_url(pmpro_url("invoice", "?invoice=" . $ssorder->code)); ?>" class="btn btn-outline">
                                        <i class="fa-solid fa-file-invoice"></i> Ver Última Fatura
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="membership-inactive">
                            <div class="empty-state">
                                <i class="fa-solid fa-box-open empty-icon"></i>
                                <h3>Nenhuma assinatura ativa</h3>
                                <p>Escolha um plano e comece a aproveitar todos os benefícios!</p>
                                <a href="<?php echo esc_url(pmpro_url("levels")); ?>" class="btn btn-primary">
                                    <i class="fa-solid fa-rocket"></i> Ver Planos Disponíveis
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Informações Pessoais -->
            <div class="bebelume-card">
                <div class="card-header">
                    <h2><i class="fa-solid fa-user"></i> Informações Pessoais</h2>
                </div>
                <div class="card-content">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label"><i class="fa-solid fa-signature"></i> Nome:</span>
                            <span class="info-value"><?php echo esc_html($current_user->first_name . ' ' . $current_user->last_name); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label"><i class="fa-solid fa-envelope"></i> Email:</span>
                            <span class="info-value"><?php echo esc_html($current_user->user_email); ?></span>
                        </div>
                    </div>
                    <div class="info-actions">
                        <a href="<?php echo admin_url('profile.php'); ?>" class="btn btn-outline">
                            <i class="fa-solid fa-pen-to-square"></i> Editar Perfil
                        </a>
                    </div>
                </div>
            </div>

            <!-- Informações de Cobrança -->
            <?php if ($has_billing || !empty($ssorder->id)): ?>
            <div class="bebelume-card">
                <div class="card-header">
                    <h2><i class="fa-solid fa-credit-card"></i> Informações de Cobrança</h2>
                </div>
                <div class="card-content">
                    <?php if ($has_billing): ?>
                        <div class="billing-info">
                            <p><strong><i class="fa-solid fa-user"></i> <?php echo esc_html($bfirstname . ' ' . $blastname); ?></strong></p>
                            <?php if ($baddress1): ?>
                                <p><i class="fa-solid fa-location-dot"></i> <?php echo esc_html($baddress1); ?></p>
                            <?php endif; ?>
                            <?php if ($baddress2): ?>
                                <p><i class="fa-solid fa-location-dot"></i> <?php echo esc_html($baddress2); ?></p>
                            <?php endif; ?>
                            <?php if ($bcity || $bstate || $bzipcode): ?>
                                <p><i class="fa-solid fa-map-pin"></i>
                                    <?php echo esc_html($bcity); ?>
                                    <?php if ($bstate): echo ', ' . esc_html($bstate); endif; ?>
                                    <?php if ($bzipcode): echo ' ' . esc_html($bzipcode); endif; ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($bcountry): ?>
                                <p><i class="fa-solid fa-globe"></i> <?php echo esc_html($bcountry); ?></p>
                            <?php endif; ?>
                            <?php if ($bphone): ?>
                                <p><i class="fa-solid fa-phone"></i> <?php echo esc_html($bphone); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($ssorder->accountnumber)): ?>
                        <div class="payment-method">
                            <p><strong><i class="fa-solid fa-wallet"></i> Método de Pagamento:</strong></p>
                            <p><i class="fa-brands fa-cc-<?php echo strtolower($ssorder->cardtype); ?>"></i> <?php echo esc_html($ssorder->cardtype); ?> terminando em <?php echo esc_html(last4($ssorder->accountnumber)); ?></p>
                            <?php if (!empty($ssorder->expirationmonth)): ?>
                                <p><i class="fa-regular fa-calendar-xmark"></i> <strong>Expira:</strong> <?php echo esc_html($ssorder->expirationmonth . '/' . $ssorder->expirationyear); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="billing-actions">
                        <a href="<?php echo esc_url(pmpro_url("billing")); ?>" class="btn btn-outline">
                            <i class="fa-solid fa-pen-to-square"></i> Atualizar Informações de Cobrança
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- ========== COLUNA DIREITA: Links Rápidos e Outros Planos ========== -->
        <div class="bebelume-account-sidebar">
            
            <!-- Links Rápidos -->
            <div class="bebelume-card">
                <div class="card-header">
                    <h3><i class="fa-solid fa-bolt"></i> Links Rápidos</h3>
                </div>
                <div class="card-content">
                    <ul class="quick-links">
                        <li>
                            <a href="<?php echo admin_url('profile.php'); ?>">
                                <i class="fa-solid fa-user-pen link-icon"></i>
                                <span class="link-text">Editar Perfil</span>
                            </a>
                        </li>
                        <?php if ($current_user->membership_level && !empty($ssorder->id)): ?>
                        <li>
                            <a href="<?php echo esc_url(pmpro_url("invoice", "?invoice=" . $ssorder->code)); ?>">
                                <i class="fa-solid fa-file-invoice-dollar link-icon"></i>
                                <span class="link-text">Última Fatura</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <li>
                            <a href="<?php echo wp_logout_url(home_url()); ?>">
                                <i class="fa-solid fa-right-from-bracket link-icon"></i>
                                <span class="link-text">Sair</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Ajuda -->
            <div class="bebelume-card bebelume-help">
                <div class="card-content">
                    <i class="fa-solid fa-headset help-icon"></i>
                    <h3>Precisa de Ajuda?</h3>
                    <p>Entre em contato com nosso suporte se tiver qualquer dúvida.</p>
                    <a href="mailto:atendimento@bebelume.com" class="btn btn-sm btn-primary">
                        <i class="fa-solid fa-envelope"></i> Falar com Suporte
                    </a>
                </div>
            </div>

        </div>

    </div>

</div>

<?php 
// Hook para adicionar conteúdo customizado no final
do_action('pmpro_account_page_after');
?>

<style>
/* ========== BEBELUME ACCOUNT PAGE STYLES - COM FONT AWESOME ========== */

:root {
    --bbl-pink: #FF6B9D;
}

.bebelume-account-wrapper {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.bebelume-account-grid {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 2rem;
}

@media (max-width: 968px) {
    .bebelume-account-grid {
        grid-template-columns: 1fr;
    }
}

/* ========== CARDS ========== */

.bebelume-card {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    margin-bottom: 1.5rem;
    overflow: hidden;
    transition: box-shadow 0.2s;
}

.bebelume-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.card-header {
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    color: #1f2937;
    padding: 1.25rem 1.5rem;
}

.card-header h2,
.card-header h3 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 700;
    font-family: 'Nunito', sans-serif !important;
    color: var(--bbl-pink) !important;
}

.card-header i {
    margin-right: 0.5rem;
    color: var(--bbl-pink);
}

.card-content {
    padding: 1.5rem;
}

/* ========== WELCOME HEADER ========== */

.bebelume-welcome-header {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 2rem;
    padding: 2rem;
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    color: #1f2937;
}

.welcome-avatar img {
    border-radius: 50%;
    border: 3px solid #e5e7eb;
}

.welcome-info h1 {
    margin: 0;
    font-size: 1.875rem;
    font-weight: 800;
    font-family: 'Nunito', sans-serif !important;
    color: var(--bbl-pink) !important;
}

.welcome-info h1 i {
    color: #f59e0b;
    margin-right: 0.5rem;
}

.welcome-subtitle {
    margin: 0.5rem 0 0;
    color: #6b7280;
    font-size: 0.95rem;
}

/* ========== MEMBERSHIP ACTIVE ========== */

.membership-active {
    text-align: center;
}

/* Grid de níveis: uma coluna por plano */
.membership-levels-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.membership-level-card {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    display: flex;
    flex-direction: row;
    align-items: stretch;
    text-align: left;
}

.level-image {
    flex: 0 0 200px;
    max-width: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background: #f9fafb;
}

.level-image img {
    width: 100%;
    height: auto;
    display: block;
    border-radius: 8px;
}

.level-image-placeholder {
    width: 100%;
    min-height: 120px;
    background: #111827;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 3rem;
}

.level-info {
    padding: 1.25rem 1.5rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 0.5rem;
    flex: 1;
}

.level-name {
    margin: 0;
    font-family: 'Nunito', sans-serif !important;
    font-size: 1.125rem;
    font-weight: 700;
    color: #111827;
}

.level-status {
    font-size: 0.9rem;
    color: #6b7280;
}

.level-status-active {
    color: #059669;
}

.level-since {
    font-size: 0.85rem;
    color: #9ca3af;
}

a.level-link {
    display: flex;
    flex-direction: row;
    align-items: stretch;
    text-decoration: none;
    color: inherit;
    width: 100%;
}

a.level-link:hover .level-image img,
a.level-link:hover .level-image-placeholder {
    opacity: 0.9;
}

a.level-link:hover .level-name {
    color: var(--bbl-pink);
}

@media (max-width: 600px) {
    .membership-level-card {
        flex-direction: column;
    }
    .level-image {
        flex: none;
        max-width: 100%;
        width: 100%;
        padding: 1rem 1rem 0;
    }
    .level-image img {
        height: auto;
        width: 100%;
    }
    a.level-link {
        flex-direction: column;
    }
}

.membership-details {
    margin: 1.5rem 0;
    text-align: left;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    padding: 0.875rem 0;
    border-bottom: 1px solid #f3f4f6;
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-label {
    color: #6b7280;
    font-weight: 500;
}

.detail-label i {
    margin-right: 0.5rem;
    width: 16px;
    text-align: center;
}

.detail-value {
    color: #111827;
    font-weight: 600;
}

.membership-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
    flex-wrap: wrap;
    justify-content: center;
}

/* ========== MEMBERSHIP INACTIVE ========== */

.membership-inactive .empty-state {
    text-align: center;
    padding: 2rem 1rem;
}

.empty-icon {
    font-size: 4rem;
    display: block;
    margin-bottom: 1rem;
    color: #9ca3af;
}

.empty-state h3 {
    margin: 0 0 0.5rem;
    font-family: 'Nunito', sans-serif !important;
    color: var(--bbl-pink) !important;
    font-weight: 700;
}

.empty-state p {
    color: #6b7280;
    margin-bottom: 1.5rem;
}

/* ========== INFO GRID ========== */

.info-grid {
    display: grid;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 8px;
    border: 1px solid #f3f4f6;
}

.info-label {
    color: #6b7280;
    font-weight: 500;
}

.info-label i {
    margin-right: 0.5rem;
    width: 16px;
    text-align: center;
}

.info-value {
    color: #111827;
    font-weight: 600;
}

.info-actions {
    text-align: center;
}

/* ========== BILLING INFO ========== */

.billing-info,
.payment-method {
    padding: 1rem;
    background: #f9fafb;
    border-radius: 8px;
    border: 1px solid #f3f4f6;
    margin-bottom: 1rem;
}

.billing-info p,
.payment-method p {
    margin: 0.5rem 0;
    color: #374151;
}

.billing-info i,
.payment-method i {
    margin-right: 0.5rem;
    width: 16px;
    text-align: center;
    color: #6b7280;
}

.billing-actions {
    text-align: center;
}

/* ========== QUICK LINKS ========== */

.quick-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.quick-links li {
    border-bottom: 1px solid #f3f4f6;
}

.quick-links li:last-child {
    border-bottom: none;
}

.quick-links a {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    text-decoration: none;
    color: #374151;
    transition: background 0.2s;
    border-radius: 6px;
}

.quick-links a:hover {
    background: #f9fafb;
}

.link-icon {
    font-size: 1.25rem;
    width: 24px;
    text-align: center;
    color: #6b7280;
}

.link-text {
    font-weight: 500;
}

/* ========== OTHER LEVELS ========== */

.other-levels {
    list-style: none;
    padding: 0;
    margin: 0;
}

.other-levels li {
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
}

.other-levels li:last-child {
    border-bottom: none;
}

.level-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.level-name {
    font-weight: 600;
    color: #111827;
}

.level-name i {
    margin-right: 0.5rem;
    color: #6b7280;
}

.level-price {
    color: #111827;
    font-weight: 700;
}

.level-price i {
    margin-right: 0.25rem;
}

.level-free {
    color: #059669;
}

/* ========== HELP CARD ========== */

.bebelume-help .card-content {
    text-align: center;
    padding: 2rem 1.5rem;
}

.help-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #6b7280;
}

.bebelume-help h3 {
    margin: 0 0 0.5rem;
    font-family: 'Nunito', sans-serif !important;
    color: var(--bbl-pink) !important;
    font-weight: 700;
}

.bebelume-help p {
    color: #6b7280;
    margin-bottom: 1.5rem;
}

/* ========== REFERRAL CARD ========== */

.bebelume-referral {
    background: linear-gradient(135deg, rgba(0, 188, 212, 0.08) 0%, rgba(0, 151, 167, 0.08) 100%);
    border: 2px solid rgba(0, 188, 212, 0.3);
}

.bebelume-referral .card-content {
    text-align: center;
    padding: 2rem 1.5rem;
}

.bebelume-referral h3 {
    margin: 0 0 0.5rem;
    font-family: 'Nunito', sans-serif !important;
    color: var(--bbl-text-primary) !important;
    font-weight: 700;
    font-size: 1.3rem;
}

.bebelume-referral p {
    color: var(--bbl-text-secondary);
    margin-bottom: 1.5rem;
}

/* ========== BUTTONS ========== */

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
    text-align: center;
    font-size: 0.9375rem;
}

.btn i {
    font-size: 1rem;
}

.btn-primary {
    background: #111827;
    color: white;
}

.btn-primary:hover {
    background: #1f2937;
    box-shadow: 0 4px 12px rgba(17, 24, 39, 0.25);
}

.btn-outline {
    background: transparent;
    border: 1.5px solid #d1d5db;
    color: #374151;
}

.btn-outline:hover {
    background: #f9fafb;
    border-color: #9ca3af;
}

.btn-cyan {
    background: linear-gradient(135deg, var(--bbl-cyan) 0%, #0097A7 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(0, 188, 212, 0.25);
}

.btn-cyan:hover {
    background: linear-gradient(135deg, #0097A7 0%, var(--bbl-cyan) 100%);
    color: white;
    box-shadow: 0 6px 20px rgba(0, 188, 212, 0.35);
    transform: translateY(-2px);
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

/* ========== MESSAGES ========== */

.pmpro_message {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    border-left: 4px solid;
    background-color: #fff !important;
}

.pmpro_message.pmpro_success {
    background: #fff;
    color: #065f46;
    border-color: #10b981;
}

.pmpro_message.pmpro_error {
    background: #fff !important;
    color: #991b1b;
    border-color: #ef4444;
}

.pmpro_message.pmpro_alert {
    background: #fff !important;
    color: #92400e;
    border-color: #f59e0b;
}

/* ========== RESPONSIVE ========== */

@media (max-width: 768px) {
    .bebelume-welcome-header {
        flex-direction: column;
        text-align: center;
    }
    
    .welcome-info h1 {
        font-size: 1.5rem;
    }
    
    .membership-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
}
</style>