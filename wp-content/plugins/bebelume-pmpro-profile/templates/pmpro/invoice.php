<?php
/**
 * Template: Fatura — Bebelume
 * @package Bebelume_PMPro_Profile
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $pmpro_invoice, $pmpro_msg, $pmpro_msgt, $current_user;

wp_enqueue_style( 'bbl-pmpro', BBL_PMPro_URL . 'assets/css/pmpro.css', [], BBL_PMPro_VERSION );
wp_enqueue_style( 'bbl-fonts', 'https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap', [], null );
?>

<div class="bbl-pmpro">

    <?php if ( $pmpro_msg ) : ?>
    <div class="bbl-msg bbl-msg--<?php echo $pmpro_msgt === 'pmpro_success' ? 'success' : 'error'; ?>">
        <?php echo wp_kses_post( $pmpro_msg ); ?>
    </div>
    <?php endif; ?>

    <?php if ( $pmpro_invoice ) :
        $pmpro_invoice->getUser();
        $pmpro_invoice->getMembershipLevel();
    ?>

    <div class="bbl-card">
        <h2 class="bbl-card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            Fatura #<?php echo esc_html( $pmpro_invoice->code ); ?>
        </h2>

        <div class="bbl-info-grid">
            <div class="bbl-info-item"><label>Data</label><span><?php echo date_i18n( 'd/m/Y', strtotime( $pmpro_invoice->datetime ) ); ?></span></div>
            <div class="bbl-info-item"><label>Plano</label><span><?php echo esc_html( $pmpro_invoice->membership_level->name ?? '—' ); ?></span></div>
            <div class="bbl-info-item"><label>Status</label><span><span class="bbl-badge bbl-badge--<?php echo $pmpro_invoice->status === 'success' ? 'active' : 'pending'; ?>"><?php echo $pmpro_invoice->status === 'success' ? 'Pago' : ucfirst( $pmpro_invoice->status ); ?></span></span></div>
            <div class="bbl-info-item"><label>Total</label><span style="color:var(--bbl-pink);font-weight:800;">R$ <?php echo number_format( $pmpro_invoice->total, 2, ',', '.' ); ?></span></div>
        </div>
    </div>

    <div class="bbl-btn-row">
        <a href="<?php echo esc_url( pmpro_url( 'account' ) ); ?>" class="bbl-btn bbl-btn--secondary">← Minha conta</a>
    </div>

    <?php else : ?>
    <div class="bbl-msg bbl-msg--error">Fatura não encontrada.</div>
    <?php endif; ?>

</div>
