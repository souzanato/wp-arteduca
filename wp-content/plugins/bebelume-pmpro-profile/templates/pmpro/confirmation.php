<?php
/**
 * Template: Confirmação pós-compra — Bebelume
 * @package Bebelume_PMPro_Profile
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $current_user, $pmpro_invoice;

wp_enqueue_style( 'bbl-pmpro',  BBL_PMPro_URL . 'assets/css/pmpro.css', [], BBL_PMPro_VERSION );
wp_enqueue_style( 'bbl-fonts',  'https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap', [], null );

/* ── Dados do nível ──────────────────────────────────────── */
$level = null;
if ( ! empty( $pmpro_invoice ) && ! empty( $pmpro_invoice->membership_level ) ) {
    $level = $pmpro_invoice->membership_level;
} else {
    $level = pmpro_getMembershipLevelForUser( $current_user->ID );
}

$level_name = $level ? esc_html( $level->name ) : '';

/* ── Nome do usuário ─────────────────────────────────────── */
$first_name = trim( $current_user->first_name );
if ( ! $first_name ) $first_name = $current_user->display_name;
$first_name = esc_html( $first_name );

/* ── Valores da fatura ───────────────────────────────────── */
$total_hoje   = 0.00;
$billing_amt  = 0.00;
$codigo       = '';
$next_date    = '';

if ( ! empty( $pmpro_invoice ) ) {
    $total_hoje  = floatval( $pmpro_invoice->total );
    $codigo      = esc_html( $pmpro_invoice->code );
}

if ( $level ) {
    $billing_amt = floatval( $level->billing_amount );
}

/* ── Próxima cobrança ────────────────────────────────────── */
// Tenta calcular a partir do nível atual do usuário
$member_level = pmpro_getMembershipLevelForUser( $current_user->ID );
if ( $member_level && ! empty( $member_level->enddate ) && $member_level->enddate != '0000-00-00 00:00:00' ) {
    $next_date = date_i18n( 'd/m/Y', strtotime( $member_level->enddate ) );
} elseif ( $level && ! empty( $level->cycle_number ) && $level->cycle_number > 0 ) {
    $period_map = [ 'Day' => 'day', 'Week' => 'week', 'Month' => 'month', 'Year' => 'year' ];
    $period     = $period_map[ $level->cycle_period ] ?? 'month';
    $next_date  = date_i18n( 'd/m/Y', strtotime( '+' . $level->cycle_number . ' ' . $period ) );
}

/* ── Detecta trial (cobrança inicial = 0 mas billing > 0) ── */
$is_trial = ( $total_hoje == 0.00 && $billing_amt > 0.00 );

/* ── Logo ────────────────────────────────────────────────── */
$logo_img = '';
if ( has_custom_logo() ) {
    $logo_id  = get_theme_mod( 'custom_logo' );
    $logo_src = wp_get_attachment_image_url( $logo_id, 'medium' );
    if ( $logo_src ) {
        $logo_img = '<img src="' . esc_url( $logo_src ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '" style="height:64px;width:auto;object-fit:contain;">';
    }
}
if ( ! $logo_img ) {
    $logo_img = '<img src="' . esc_url( BBL_PMPro_URL . 'assets/images/bebelume-logo-fundo-transparente.png' ) . '" alt="Bebelume" style="height:64px;width:auto;object-fit:contain;">';
}

/* ── URL do conteúdo ─────────────────────────────────────── */
$content_url = home_url();
if ( defined( 'BBL_SATELLITE_CANAL' ) && $level ) {
    // Nível 1/2 = Canal, 3/6 = ArtEduca — adapta se necessário
    $content_url = home_url();
}
?>

<style>
.bbl-confirm-wrap {
    min-height: 60vh;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 48px 16px 64px;
    background: transparent;
}
.bbl-confirm-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 2px 16px rgba(0,0,0,.07);
    max-width: 560px;
    width: 100%;
    padding: 40px 36px;
    font-family: var(--bbl-font, 'Nunito', sans-serif);
}
.bbl-confirm-logo {
    text-align: center;
    margin-bottom: 20px;
}
.bbl-confirm-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
}
.bbl-confirm-icon-circle {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: rgba(235,42,97,.10);
    display: flex;
    align-items: center;
    justify-content: center;
}
.bbl-confirm-title {
    text-align: center;
    font-size: 22px;
    font-weight: 800;
    color: var(--bbl-text, #1a1a2e);
    margin: 0 0 8px;
    line-height: 1.3;
}
.bbl-confirm-subtitle {
    text-align: center;
    font-size: 15px;
    color: var(--bbl-text-sec, #6b7480);
    margin: 0 0 24px;
    line-height: 1.5;
}
.bbl-confirm-divider {
    border: none;
    border-top: 1px solid var(--bbl-border, #e4e8ed);
    margin: 0 0 20px;
}
.bbl-confirm-summary {
    background: #f7f7f7;
    border-radius: 10px;
    padding: 18px 20px;
    margin-bottom: 24px;
}
.bbl-confirm-row {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 8px;
    padding: 5px 0;
}
.bbl-confirm-row + .bbl-confirm-row {
    border-top: 1px solid #ebebeb;
}
.bbl-confirm-row label {
    font-size: 12px;
    font-weight: 700;
    color: var(--bbl-text-sec, #6b7480);
    text-transform: uppercase;
    letter-spacing: .4px;
    flex-shrink: 0;
}
.bbl-confirm-row span {
    font-size: 13px;
    font-weight: 600;
    color: var(--bbl-text, #1a1a2e);
    text-align: right;
}
.bbl-confirm-row span.bbl-confirm-code {
    font-family: 'Courier New', monospace;
    font-size: 12px;
    color: var(--bbl-text-sec, #6b7480);
    font-weight: 400;
}
.bbl-confirm-row span.bbl-confirm-offer {
    color: #2e9e4f;
    font-weight: 700;
}
.bbl-confirm-btn-primary {
    display: block;
    width: 100%;
    text-align: center;
    background: var(--bbl-pink, #eb2a61);
    color: #fff !important;
    font-family: var(--bbl-font, 'Nunito', sans-serif);
    font-size: 15px;
    font-weight: 700;
    text-decoration: none;
    border-radius: 50px;
    padding: 0;
    height: 48px;
    line-height: 48px;
    margin-bottom: 10px;
    transition: background .15s;
}
.bbl-confirm-btn-primary:hover { background: #c41e50; color: #fff !important; }
.bbl-confirm-btn-secondary {
    display: block;
    width: 100%;
    text-align: center;
    background: #fff;
    color: var(--bbl-text-sec, #6b7480) !important;
    font-family: var(--bbl-font, 'Nunito', sans-serif);
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    border-radius: 50px;
    border: 1px solid var(--bbl-border, #e4e8ed);
    height: 44px;
    line-height: 42px;
    margin-bottom: 24px;
    transition: border-color .15s, color .15s;
}
.bbl-confirm-btn-secondary:hover { border-color: #bbb; color: var(--bbl-text, #1a1a2e) !important; }
.bbl-confirm-micros {
    text-align: center;
}
.bbl-confirm-micro {
    font-size: 12px;
    color: var(--bbl-text-sec, #6b7480);
    margin: 0 0 4px;
    line-height: 1.5;
}
@media (max-width: 600px) {
    .bbl-confirm-card { padding: 28px 20px; }
    .bbl-confirm-title { font-size: 19px; }
}
</style>

<div class="bbl-confirm-wrap">
    <div class="bbl-confirm-card">

        <!-- Logo -->
        <div class="bbl-confirm-logo">
            <?php echo $logo_img; ?>
        </div>

        <!-- Ícone de sucesso -->
        <div class="bbl-confirm-icon">
            <div class="bbl-confirm-icon-circle">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none"
                     stroke="#eb2a61" stroke-width="2.5"
                     stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
        </div>

        <!-- Título -->
        <h2 class="bbl-confirm-title">
            Bem-vindo ao Bebelume<?php if ( $first_name ) echo ', ' . $first_name; ?>! 🎉
        </h2>

        <!-- Subtítulo -->
        <p class="bbl-confirm-subtitle">
            Sua assinatura<?php if ( $level_name ) echo ' <strong>' . $level_name . '</strong>'; ?> foi ativada com sucesso.
        </p>

        <hr class="bbl-confirm-divider">

        <!-- Resumo -->
        <div class="bbl-confirm-summary">

            <?php if ( $level_name ) : ?>
            <div class="bbl-confirm-row">
                <label>Plano</label>
                <span><?php echo $level_name; ?></span>
            </div>
            <?php endif; ?>

            <div class="bbl-confirm-row">
                <label>Cobrado hoje</label>
                <span>R$ <?php echo number_format( $total_hoje, 2, ',', '.' ); ?></span>
            </div>

            <?php if ( $is_trial ) : ?>
            <div class="bbl-confirm-row">
                <label>Oferta aplicada</label>
                <span class="bbl-confirm-offer">1º mês gratuito ✓</span>
            </div>
            <?php endif; ?>

            <?php if ( $next_date && $billing_amt > 0 ) : ?>
            <div class="bbl-confirm-row">
                <label>Próxima cobrança</label>
                <span>R$ <?php echo number_format( $billing_amt, 2, ',', '.' ); ?> em <?php echo esc_html( $next_date ); ?></span>
            </div>
            <?php endif; ?>

            <?php if ( $codigo ) : ?>
            <div class="bbl-confirm-row">
                <label>Código do pedido</label>
                <span class="bbl-confirm-code"><?php echo $codigo; ?></span>
            </div>
            <?php endif; ?>

        </div>

        <!-- CTAs -->
        <a href="<?php echo esc_url( $content_url ); ?>" class="bbl-confirm-btn-primary">
            Começar a assistir &rsaquo;
        </a>
        <a href="<?php echo esc_url( pmpro_url( 'account' ) ?: home_url( '/conta-de-associacao/' ) ); ?>" class="bbl-confirm-btn-secondary">
            Ir para minha conta
        </a>

        <!-- Microtextos -->
        <div class="bbl-confirm-micros">
            <p class="bbl-confirm-micro">✉ Você receberá um e-mail de confirmação em breve.</p>
            <p class="bbl-confirm-micro">▶ Seu acesso já está ativo — comece a explorar agora.</p>
        </div>

    </div>
</div>
