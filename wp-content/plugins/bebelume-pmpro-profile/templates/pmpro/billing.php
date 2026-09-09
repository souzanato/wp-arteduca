<?php
/**
 * Template: Faturamento — Bebelume
 * @package Bebelume_PMPro_Profile
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $pmpro_msg, $pmpro_msgt, $current_user;

wp_enqueue_style( 'bbl-pmpro', BBL_PMPro_URL . 'assets/css/pmpro.css', [], BBL_PMPro_VERSION );
wp_enqueue_style( 'bbl-fonts', 'https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap', [], null );
?>

<div class="bbl-pmpro">

    <?php if ( $pmpro_msg ) : ?>
    <div class="bbl-msg bbl-msg--<?php echo $pmpro_msgt === 'pmpro_success' ? 'success' : 'error'; ?>">
        <?php echo wp_kses_post( $pmpro_msg ); ?>
    </div>
    <?php endif; ?>

    <div class="bbl-card">
        <h2 class="bbl-card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            Forma de Pagamento
        </h2>

        <?php do_action( 'pmpro_billing_before_form' ); ?>

        <form id="pmpro_form" method="post">
            <?php do_action( 'pmpro_billing_form' ); ?>
            <div class="bbl-btn-row">
                <button type="submit" class="bbl-btn bbl-btn--primary">Salvar</button>
                <a href="<?php echo esc_url( pmpro_url( 'account' ) ); ?>" class="bbl-btn bbl-btn--secondary">Cancelar</a>
            </div>
        </form>

        <?php do_action( 'pmpro_billing_after_form' ); ?>
    </div>

</div>
