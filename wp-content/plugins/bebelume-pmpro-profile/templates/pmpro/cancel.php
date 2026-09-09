<?php
/**
 * Template: Cancelamento — Bebelume
 * @package Bebelume_PMPro_Profile
 */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once BBL_PMPro_DIR . 'includes/processing-overlay.php';

global $current_user, $pmpro_msg, $pmpro_msgt;
$current_user = wp_get_current_user();
$membership   = pmpro_getMembershipLevelForUser( $current_user->ID );
$account_url  = pmpro_url( 'account' );
$levels_url   = pmpro_url( 'levels' );

// Data de acesso até quando
$access_until = '';
if ( $membership && ! empty( $membership->enddate ) && $membership->enddate !== '0000-00-00 00:00:00' ) {
    $access_until = date_i18n( 'd/m/Y', strtotime( $membership->enddate ) );
}

wp_enqueue_style( 'bbl-pmpro', BBL_PMPro_URL . 'assets/css/pmpro.css', [], BBL_PMPro_VERSION );
wp_enqueue_style( 'bbl-fonts', 'https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap', [], null );
?>

<div class="bbl-cancel-page">

    <?php if ( $pmpro_msg ) : ?>
    <?php $msg_type = $pmpro_msgt === 'pmpro_success' ? 'success' : 'error'; ?>
    <div class="bbl-cancel-card">
        <div class="bbl-cancel-msg bbl-cancel-msg--<?php echo $msg_type; ?>">
            <?php echo wp_kses_post( $pmpro_msg ); ?>
        </div>
        <?php if ( $msg_type === 'success' ) : ?>
        <a href="<?php echo esc_url( home_url('/') ); ?>" class="bbl-cancel-btn-back" style="margin-top:1.5rem;display:block;text-align:center;">
            Voltar ao início
        </a>
        <?php else : ?>
        <a href="<?php echo esc_url( $account_url ); ?>" class="bbl-cancel-btn-back" style="margin-top:1.5rem;display:block;text-align:center;">
            Voltar à minha conta
        </a>
        <?php endif; ?>
    </div>

    <?php elseif ( $membership ) : ?>

    <div class="bbl-cancel-card">

        <div class="bbl-cancel-icon" aria-hidden="true">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#c0254f" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
        </div>

        <h2 class="bbl-cancel-title">Cancelar assinatura do <?php echo esc_html( $membership->name ); ?>?</h2>

        <p class="bbl-cancel-desc">
            Ao confirmar, sua assinatura será cancelada e você não será cobrado novamente.
            <?php if ( $access_until ) : ?>
            Seu acesso ao conteúdo continua até <strong><?php echo esc_html( $access_until ); ?></strong>.
            <?php else : ?>
            Seu acesso ao conteúdo continua até o fim do período contratado.
            <?php endif; ?>
        </p>

        <ul class="bbl-cancel-list">
            <li class="bbl-cancel-list__negative">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c0254f" stroke-width="2.5" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                <span class="bbl-cancel-list__text">
                    <strong>Acesso a todos os vídeos e conteúdos exclusivos será encerrado</strong>
                    <small>Isso acontece a partir do fim do período em andamento: até lá você continua assistindo normalmente; depois disso, os vídeos e materiais exclusivos saem do seu alcance.</small>
                </span>
            </li>
            <li class="bbl-cancel-list__negative">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c0254f" stroke-width="2.5" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                <span class="bbl-cancel-list__text">
                    <strong>Novos conteúdos e atualizações não estarão disponíveis</strong>
                    <small>Você deixa de receber os vídeos e atualizações que forem publicados depois do encerramento da assinatura.</small>
                </span>
            </li>
            <li class="bbl-cancel-list__neutral">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#3b6d11" stroke-width="2.5" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                <span class="bbl-cancel-list__text">
                    <strong>Seu histórico é mantido — você pode voltar quando quiser</strong>
                    <small>Aqui, histórico é o da sua conta: os dados da assinatura e as faturas já pagas continuam registrados (não são os vídeos que você assistiu). Para voltar, é só assinar um plano de novo — sem novo período de teste.</small>
                </span>
            </li>
        </ul>

        <?php
        // E-mail de suporte (retenção): já pré-preenchido com TODOS os dados do usuário
        // e da assinatura, para o atendimento resolver sem precisar pedir nada de volta.
        $uid = (int) $current_user->ID;
        $full_name = trim( (string) ( $current_user->first_name ?? '' ) . ' ' . (string) ( $current_user->last_name ?? '' ) );
        if ( ! $full_name ) $full_name = $current_user->display_name;
        $support_host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
        if ( strpos( $support_host, 'canal.' ) !== false ) {
            $support_site = 'Canal Bebelume';
        } elseif ( strpos( $support_host, 'arteduca.' ) !== false ) {
            $support_site = 'ArtEduca';
        } else {
            $support_site = get_bloginfo( 'name' );
        }
        $support_plan = $membership->name;

        $sub = null;
        if ( class_exists( 'PMPro_Subscription' ) ) {
            $subs = PMPro_Subscription::get_subscriptions_for_user( $uid, (int) $membership->id, [ 'active', 'trialing' ] );
            if ( ! empty( $subs ) ) $sub = $subs[0];
        }

        $amount  = $sub ? (float) $sub->get_billing_amount() : (float) ( $membership->billing_amount ?? 0 );
        $cycle   = $sub ? strtolower( (string) $sub->get_cycle_period() ) : strtolower( (string) ( $membership->cycle_period ?? '' ) );
        $periodo = match ( $cycle ) { 'month' => 'mês', 'year' => 'ano', default => '' };
        $preco   = $amount > 0 ? 'R$ ' . number_format( $amount, 2, ',', '.' ) . ( $periodo ? '/' . $periodo : '' ) : 'Gratuito';

        $since = $sub ? (string) $sub->get_startdate( 'd/m/Y H:i', true ) : '';
        if ( ! $since && ! empty( $membership->startdate ) ) {
            $since = date_i18n( 'd/m/Y H:i', is_numeric( $membership->startdate ) ? (int) $membership->startdate : strtotime( $membership->startdate ) );
        }

        $status   = 'Assinatura ativa';
        $next_pay = '—';
        $txn_id   = '';
        if ( $sub ) {
            global $wpdb;
            $next_pay = (string) $sub->get_next_payment_date( 'd/m/Y H:i', true );
            if ( ! $next_pay ) $next_pay = '—';
            $txn_id = (string) $sub->get_subscription_transaction_id();
            $next_ts = $sub->get_next_payment_date( 'timestamp', true );
            $paid = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}pmpro_membership_orders
                 WHERE user_id = %d AND membership_id = %d AND status = 'success' AND total > 0",
                $uid, (int) $membership->id
            ) );
            if ( $next_ts && $next_ts > current_time( 'timestamp' ) && $paid === 0 ) {
                $status = 'Período de Teste (ainda sem cobrança)';
            }
        }

        $lines = [
            'Olá, equipe ' . $support_site . '!',
            '',
            'Escrevo sobre a minha assinatura. Para agilizar, seguem meus dados:',
            '',
            '— Dados do usuário —',
            'Nome: ' . $full_name,
            'E-mail: ' . $current_user->user_email,
            'ID no site: ' . $uid,
            '',
            '— Assinatura —',
            'Produto: ' . $support_site,
            'Plano: ' . $support_plan,
            'Valor: ' . $preco,
            'Status: ' . $status,
            'Assinante desde: ' . ( $since ?: '—' ),
            'Próxima cobrança (fim do período atual): ' . $next_pay,
        ];
        if ( $txn_id ) $lines[] = 'ID da assinatura: ' . $txn_id;
        $lines[] = '';
        $lines[] = 'Aguardo o retorno de vocês. Obrigado!';

        $support_subject = 'Preciso de ajuda com minha assinatura — ' . $support_plan;
        $support_mailto  = 'mailto:atendimento@bebelume.com.br?subject=' . rawurlencode( $support_subject ) . '&body=' . rawurlencode( implode( "
", $lines ) );
        ?>
        <div class="bbl-cancel-retention">
            <p class="bbl-cancel-retention__title">Antes de cancelar, podemos ajudar?</p>
            <div class="bbl-cancel-retention__options">
                <a href="<?php echo esc_url( $support_mailto ); ?>" class="bbl-cancel-retention__option">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <span>
                        <strong>Falar com o suporte</strong>
                        <small>Dúvidas sobre cobrança, acesso ou sua assinatura</small>
                    </span>
                </a>
            </div>
        </div>

        <form method="post" id="bbl-cancel-form">
            <?php wp_nonce_field( 'pmpro_cancel-nonce', 'pmpro_cancel-nonce' ); ?>
            <input type="hidden" name="confirm" value="1" />
            <button type="submit" class="bbl-cancel-btn-confirm">
                Cancelar assinatura agora
            </button>
        </form>

        <?php bbl_processing_overlay( [
            'overlay_id'      => 'bbl-cancel-processing',
            'form_selector'   => '#bbl-cancel-form',
            'button_selector' => '#bbl-cancel-form .bbl-cancel-btn-confirm',
            'title'           => 'Estamos cancelando sua assinatura',
            'sub'             => 'Não feche nem atualize esta página.<br>Isso leva só um instante.',
            'secure'          => false,
            'auto_release'    => false,
            'retry_ms'        => 20000,
        ] ); ?>

        <a href="<?php echo esc_url( $account_url ); ?>" class="bbl-cancel-btn-back">
            ← Manter minha assinatura
        </a>

    </div>

    <?php else : ?>

    <div class="bbl-cancel-card" style="text-align:center;">
        <p style="color:var(--bbl-text-sec);margin-bottom:1.5rem;">Você não possui um plano ativo no momento.</p>
        <a href="<?php echo esc_url( $levels_url ); ?>" class="bbl-cancel-btn-back">Ver planos disponíveis</a>
    </div>

    <?php endif; ?>

</div>
