<?php
/**
 * Template: Planos — Bebelume
 * @package Bebelume_PMPro_Profile
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$levels  = pmpro_getAllLevels( false, true );
$current = pmpro_getMembershipLevelForUser( get_current_user_id() );

wp_enqueue_style( 'bbl-pmpro', BBL_PMPro_URL . 'assets/css/pmpro.css', [], BBL_PMPro_VERSION );
wp_enqueue_style( 'bbl-fonts', 'https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap', [], null );
?>

<div class="bbl-pmpro">

    <?php if ( $current ) : ?>
    <div class="bbl-msg bbl-msg--info">
        Você já é assinante do plano <strong><?php echo esc_html( $current->name ); ?></strong>.
        <a href="<?php echo esc_url( pmpro_url( 'account' ) ); ?>">Ver minha conta</a>
    </div>
    <?php endif; ?>

    <div class="bbl-levels-grid">
        <?php foreach ( $levels as $level ) :
            $is_current  = $current && $current->id == $level->id;
            $checkout_url = pmpro_url( 'checkout', '?level=' . $level->id );
            $price_str    = pmpro_getLevelCost( $level, true, true );
        ?>
        <div class="bbl-level-card <?php echo $is_current ? 'bbl-level-card--featured' : ''; ?>">

            <?php if ( $is_current ) : ?>
            <div style="margin-bottom:12px;"><span class="bbl-badge bbl-badge--active">Plano atual</span></div>
            <?php endif; ?>

            <div class="bbl-level-name"><?php echo esc_html( $level->name ); ?></div>

            <div class="bbl-level-price">
                <?php
                if ( (float) $level->initial_payment > 0 ) {
                    echo 'R$ ' . number_format( $level->initial_payment, 2, ',', '.' );
                } elseif ( (float) $level->billing_amount > 0 ) {
                    echo 'R$ ' . number_format( $level->billing_amount, 2, ',', '.' );
                } else {
                    echo 'Grátis';
                }
                ?>
            </div>

            <?php if ( $level->billing_amount > 0 ) : ?>
            <div class="bbl-level-period">
                por <?php echo $level->billing_limit == 1 ? 'mês' : $level->cycle_number . ' ' . $level->cycle_period; ?>
            </div>
            <?php endif; ?>

            <?php if ( $level->description ) : ?>
            <div class="bbl-level-desc"><?php echo wp_kses_post( $level->description ); ?></div>
            <?php endif; ?>

            <?php if ( $is_current ) : ?>
            <a href="<?php echo esc_url( pmpro_url( 'account' ) ); ?>" class="bbl-btn bbl-btn--secondary" style="width:100%;justify-content:center;">Minha conta</a>
            <?php else : ?>
            <a href="<?php echo esc_url( $checkout_url ); ?>" class="bbl-btn bbl-btn--primary" style="width:100%;justify-content:center;">Assinar</a>
            <?php endif; ?>

        </div>
        <?php endforeach; ?>
    </div>

</div>
