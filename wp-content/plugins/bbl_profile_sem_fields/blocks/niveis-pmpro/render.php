<?php
/**
 * Render callback: Bebelume Níveis PMPro
 * Disponível tanto no frontend quanto no preview do editor (ServerSideRender).
 */

if ( ! function_exists( 'pmpro_getAllLevels' ) ) {
    echo '<p style="padding:1em;color:red;">PMPro não está ativo.</p>';
    return;
}

$group_id = isset( $attributes['groupId'] ) ? intval( $attributes['groupId'] ) : 0;

if ( ! $group_id ) {
    echo '<div style="padding:2em;text-align:center;color:#999;border:2px dashed #ccc;">Selecione um grupo de níveis no painel lateral.</div>';
    return;
}

// Busca o grupo diretamente no banco
global $wpdb;
$group = $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}pmpro_groups WHERE id = %d",
    $group_id
) );

if ( ! $group ) {
    echo '<div style="padding:2em;text-align:center;color:red;">Grupo não encontrado.</div>';
    return;
}

// Busca níveis do grupo via pmpro_membership_levels_groups
if ( ! isset( $wpdb ) ) global $wpdb;

$levels    = array();
$level_ids = $wpdb->get_col( $wpdb->prepare(
    "SELECT `level` FROM {$wpdb->prefix}pmpro_membership_levels_groups WHERE `group` = %d ORDER BY id ASC",
    $group_id
) );

if ( ! empty( $level_ids ) ) {
    foreach ( $level_ids as $lid ) {
        $level = pmpro_getLevel( intval( $lid ) );
        if ( $level ) $levels[] = $level;
    }
}
if ( empty( $levels ) ) {
    echo '<div style="padding:2em;text-align:center;color:#999;">Nenhum nível encontrado neste grupo.</div>';
    return;
}

// Usuário atual (só no frontend, não no editor preview)
$current_user = wp_get_current_user();
$is_editor_preview = defined( 'REST_REQUEST' ) && REST_REQUEST;

$contador = 0;
ob_start();
?>
<div class="bbl-niveis-pmpro">
    <div class="container">
        <div class="row">
            <?php foreach ( $levels as $level ) :
                $has_level   = false;
                $user_level  = null;
                $cancelado   = false;
                $ja_assinou  = false;
                if ( $current_user->ID && ! $is_editor_preview ) {
                    $user_level = pmpro_getSpecificMembershipLevelForUser( $current_user->ID, $level->id );
                    $has_level  = ! empty( $user_level );

                    // "Já utilizou o benefício de dias grátis": teve qualquer
                    // subscription ou pedido success (mesma chave anti-trial do
                    // plugin bebelume-subscription-delay — decisão de ago/2026).
                    $ja_assinou =
                        (int) $wpdb->get_var( $wpdb->prepare(
                            "SELECT COUNT(*) FROM {$wpdb->prefix}pmpro_subscriptions WHERE user_id = %d",
                            $current_user->ID
                        ) ) > 0
                        ||
                        (int) $wpdb->get_var( $wpdb->prepare(
                            "SELECT COUNT(*) FROM {$wpdb->prefix}pmpro_membership_orders WHERE user_id = %d AND status = 'success'",
                            $current_user->ID
                        ) ) > 0;

                    // Plano cancelado no fim do período não conta mais como
                    // "plano atual" nesta página (a pessoa pode reassinar).
                    if ( class_exists( 'PMPro_Subscription' )
                         && function_exists( 'get_pmpro_subscription_meta' ) ) {
                        $subs_nivel = PMPro_Subscription::get_subscriptions_for_user(
                            (int) $current_user->ID, $level->id, array( 'active', 'trialing' )
                        );
                        foreach ( $subs_nivel as $sub_nivel ) {
                            if ( get_pmpro_subscription_meta( (int) $sub_nivel->get_id(), 'bbl_cancel_at_period_end', true ) ) {
                                $cancelado = true;
                                break;
                            }
                        }
                    }
                }

                $show_current = $has_level && ! $cancelado;

                // Cor alternada
                $classe_cor = ( $contador % 2 === 0 ) ? 'pmpro-pink-bkg' : 'pmpro-yellow-bkg';
                $contador++;

                // Tipo e período
                $tipo_plano   = '';
                $periodo_texto = '';
                $eh_mensal    = ( strtolower( $level->cycle_period ) === 'month' );
                $eh_anual     = ( strtolower( $level->cycle_period ) === 'year' );

                if ( $eh_mensal )      { $tipo_plano = 'MENSAL'; $periodo_texto = 'MÊS'; }
                elseif ( $eh_anual )   { $tipo_plano = 'ANUAL';  $periodo_texto = 'ANO'; }

                // Preço
                $preco = number_format( $level->billing_amount, 2, ',', '.' );

                // Dias grátis
                $dias_gratis = 0;
                $delay = intval( get_option( 'pmpro_level_' . $level->id . '_delay_days', 0 ) );
                if ( $delay > 0 ) {
                    $dias_gratis = $delay;
                } elseif ( floatval( $level->initial_payment ) == 0 && $eh_mensal ) {
                    $dias_gratis = 30;
                }
                $mensagem_trial  = '';
                $oferta_ja_usado = false;
                if ( ! $show_current && $dias_gratis > 0 ) {
                    if ( $ja_assinou ) {
                        $mensagem_trial = sprintf(
                            'Você já utilizou seu benefício de %d dias gratuitos. Ao assinar novamente, o valor do plano será cobrado no ato da contratação.',
                            $dias_gratis
                        );
                        $oferta_ja_usado = true;
                    } else {
                        $mensagem_trial = 'Experimente ' . $dias_gratis . ' dias gratuitamente';
                    }
                }
            ?>
            <div class="col-md-6 col-sm-12 <?php echo esc_attr( $classe_cor ); ?>">

                    <?php if ( ! empty( $tipo_plano ) ) : ?>
                        <p class="nivel-tipo"><?php echo esc_html( $tipo_plano ); ?></p>
                    <?php endif; ?>

                <div id="nivel-<?php echo esc_attr( $level->id ); ?>"
                     class="nivel-item<?php echo $show_current ? ' nivel-atual' : ''; ?> p-3">

                    <div class="nivel-preco-novo">
                        <p class="preco-valor">R$ <?php echo esc_html( $preco ); ?></p>
                        <?php if ( ! empty( $periodo_texto ) ) : ?>
                            <p class="preco-periodo">/POR <?php echo esc_html( $periodo_texto ); ?></p>
                        <?php endif; ?>
                    </div>

                    <?php if ( ! empty( $mensagem_trial ) ) : ?>
                        <div class="nivel-trial<?php echo $oferta_ja_usado ? ' nivel-trial--usado' : ''; ?>">
                            <p><?php echo esc_html( $mensagem_trial ); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php
                    $expiration_text = pmpro_getLevelExpiration( $level );
                    if ( ! empty( $expiration_text ) ) : ?>
                        <p class="nivel-expiracao"><?php echo wp_kses_post( $expiration_text ); ?></p>
                    <?php endif; ?>

                    <div class="nivel-acao">
                        <?php if ( ! $show_current ) : ?>
                            <a class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-select', 'pmpro_btn-select' ) ); ?>"
                               href="<?php echo esc_url( pmpro_url( 'checkout', '?pmpro_level=' . $level->id, 'https' ) ); ?>">
                                Selecionar
                            </a>
                        <?php else : ?>
                            <?php if ( pmpro_isLevelExpiringSoon( $user_level ) && $level->allow_signups ) : ?>
                                <a class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-renew pmpro_btn-select', 'pmpro_btn-select' ) ); ?>"
                                   href="<?php echo esc_url( pmpro_url( 'checkout', '?pmpro_level=' . $level->id, 'https' ) ); ?>">
                                    <?php esc_html_e( 'Renovar', 'paid-memberships-pro' ); ?>
                                </a>
                            <?php else : ?>
                                <a class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-outline', 'pmpro_btn' ) ); ?>"
                                   href="<?php echo esc_url( pmpro_url( 'account' ) ); ?>">
                                    <?php esc_html_e( 'Seu Nível', 'paid-memberships-pro' ); ?>
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php
echo ob_get_clean();
