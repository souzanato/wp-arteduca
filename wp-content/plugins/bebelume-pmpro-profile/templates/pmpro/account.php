<?php
/**
 * Template: Minha Conta — Bebelume
 * Mini-dashboard de membro
 *
 * @package Bebelume_PMPro_Profile
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $current_user;
$current_user = wp_get_current_user();
// Detecta contexto: hub (bebelume.com.br) busca nos satélites via API
//                   satélites usam PMPro local
$is_hub = defined( 'BBL_CANAL_APP_PASS' ) && class_exists( 'BBL_Satellite_Memberships' );

// Sempre busca em todos os satélites — hub e satélites exibem todas as assinaturas
$satellite_memberships = class_exists( 'BBL_Satellite_Memberships' )
    ? BBL_Satellite_Memberships::get_memberships( $current_user->ID )
    : [];

// Aviso de dados fiscais pendentes: com o bloqueio ativo o membro só acessa a
// conta — o banner aponta p/ a tela de conclusão de cadastro (home, onde a
// tela bloqueante renderiza).
$bbl_fiscal_action = '';
if ( ! $is_hub && class_exists( 'BBL_Fiscal_Block' ) && BBL_Fiscal_Block::is_block_active( $current_user->ID ) ) {
    $bbl_fiscal_action = sprintf(
        '<div class="bbl-fiscal-banner"><span class="bbl-fiscal-banner-text"><strong>Ação necessária:</strong> complete seus dados fiscais para continuar usufruindo do seu período gratuito.</span><a class="bbl-fiscal-banner-btn" href="%s">Completar cadastro</a></div>',
        esc_url( home_url( '/' ) )
    );
}

if ( $is_hub ) {
    $membership = null;
    $orders_raw = [];
} else {
    // Satélite — PMPro local para dados locais
    $membership = pmpro_getMembershipLevelForUser( $current_user->ID );
    $orders_raw = MemberOrder::get_orders( [ 'user_id' => $current_user->ID, 'limit' => 20 ] );

    // Adiciona assinatura local à lista se não estiver nos satélites
    if ( $membership ) {
        $local_key = defined('BBL_SATELLITE_CANAL') ? 'arteduca' : 'canal';
        // já está nos satélites — não duplica
    }
}

$billing_url  = pmpro_url( 'billing' );
$cancel_url   = pmpro_url( 'cancel' );

// Modo detalhe: ?plano=canal ou ?plano=arteduca
$plano_param  = isset( $_GET['plano'] ) ? sanitize_key( $_GET['plano'] ) : '';
$detail_mode  = ! empty( $plano_param );
$levels_url   = pmpro_url( 'levels' );

// Data de renovação e membro desde (só para PMPro local)
$renewal_date = '';
$since        = '';
if ( ! $is_hub && $membership ) {
    if ( class_exists( 'PMPro_Subscription' ) ) {
        $subscriptions = PMPro_Subscription::get_subscriptions_for_user( $current_user->ID, $membership->id );
        if ( ! empty( $subscriptions ) ) {
            $next = $subscriptions[0]->get_next_payment_date( 'd/m/Y' );
            if ( $next ) $renewal_date = $next;
        }
    }
    if ( ! $renewal_date && ! empty( $membership->enddate ) && $membership->enddate !== '0000-00-00 00:00:00' ) {
        $renewal_date = date_i18n( 'd/m/Y', strtotime( $membership->enddate ) );
    }
    if ( ! empty( $membership->startdate ) ) {
        $since = date_i18n( 'F \d\e Y', $membership->startdate );
    }
} elseif ( $is_hub ) {
    $since = date_i18n( 'F \d\e Y', strtotime( $current_user->user_registered ) );
}
$billing_url  = pmpro_url( 'billing' );
$cancel_url   = pmpro_url( 'cancel' );

// Modo detalhe: ?plano=canal ou ?plano=arteduca
$plano_param  = isset( $_GET['plano'] ) ? sanitize_key( $_GET['plano'] ) : '';
$detail_mode  = ! empty( $plano_param );
$levels_url   = pmpro_url( 'levels' );

// Nome: preferência por first_name, fallback display_name
$first_name  = ! empty( $current_user->first_name ) ? $current_user->first_name : $current_user->display_name;
$full_name   = trim( $current_user->first_name . ' ' . $current_user->last_name );
if ( ! $full_name ) $full_name = $current_user->display_name;

$cpf = get_user_meta( $current_user->ID, 'cpf_formatted', true );

// Iniciais para avatar
$initials = '';
if ( ! empty( $current_user->first_name ) ) $initials .= mb_strtoupper( mb_substr( $current_user->first_name, 0, 1 ) );
if ( ! empty( $current_user->last_name ) )  $initials .= mb_strtoupper( mb_substr( $current_user->last_name, 0, 1 ) );
if ( ! $initials ) $initials = mb_strtoupper( mb_substr( $current_user->display_name, 0, 2 ) );

// Data de renovação — tenta subscription, depois enddate
$renewal_date = '';
if ( $membership ) {
    // Tenta via PMPro_Subscription
    if ( class_exists( 'PMPro_Subscription' ) ) {
        $subscriptions = PMPro_Subscription::get_subscriptions_for_user( $current_user->ID, $membership->id );
        if ( ! empty( $subscriptions ) ) {
            $next = $subscriptions[0]->get_next_payment_date( 'd/m/Y' );
            if ( $next ) $renewal_date = $next;
        }
    }
    // Fallback: enddate
    if ( ! $renewal_date && ! empty( $membership->enddate ) && $membership->enddate !== '0000-00-00 00:00:00' ) {
        $renewal_date = date_i18n( 'd/m/Y', strtotime( $membership->enddate ) );
    }
}

// Membro desde
$since = '';
if ( $membership && ! empty( $membership->startdate ) ) {
    $since = date_i18n( 'F \d\e Y', $membership->startdate );
}

// Processa pedidos — campos são privados, usa métodos públicos
$orders = [];
foreach ( $orders_raw as $order ) {
    $ts          = $order->getTimestamp();
    $date        = $ts ? date_i18n( 'd/m/Y', $ts ) : '—';
    $level_name  = '—';
    $mid         = $order->membership_id ?? null;
    if ( $mid ) {
        $level = pmpro_getLevel( $mid );
        if ( $level ) {
            $level_name = $level->name;
        } else {
            global $wpdb;
            $name = $wpdb->get_var( $wpdb->prepare(
                "SELECT name FROM {$wpdb->prefix}pmpro_membership_levels WHERE id = %d", $mid
            ) );
            if ( $name ) {
                $level_name = $name;
            } elseif ( $membership ) {
                // Fallback: usa o plano ativo do usuário
                $level_name = $membership->name;
            }
        }
    } elseif ( $membership ) {
        $level_name = $membership->name;
    }
    $orders[] = [
        'date'   => $date,
        'plan'   => $level_name,
        'total'  => $order->total,
        'status' => $order->status,
        'code'   => $order->code,
    ];
}

wp_enqueue_style( 'bbl-pmpro', BBL_PMPro_URL . 'assets/css/pmpro.css', [], BBL_PMPro_VERSION );
wp_enqueue_style( 'bbl-fonts', 'https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap', [], null );
?><style>
.bbl-fiscal-banner{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;background:#fff7e6;border:1px solid #f3d9a4;border-left:4px solid #f0a500;border-radius:12px;padding:14px 18px;margin-bottom:20px;font-family:'Nunito',sans-serif;}
.bbl-fiscal-banner-text{font-size:14px;color:#6b4d00;line-height:1.5;flex:1;min-width:220px;}
.bbl-fiscal-banner-btn{display:inline-block;background:#eb2a61;color:#fff !important;text-decoration:none;font-size:13px;font-weight:700;border-radius:99px;padding:9px 18px;white-space:nowrap;transition:background .15s;}
.bbl-fiscal-banner-btn:hover{background:#c41e50;color:#fff !important;}
</style>
<?php

// ── Modo detalhe de assinatura ────────────────────────────────────────────────
if ( $detail_mode ) :
    $account_url = pmpro_url( 'account' ) ?: home_url( '/conta-de-associacao/' );

    $level_param = isset( $_GET['level'] ) ? intval( $_GET['level'] ) : 0;

    if ( $is_hub ) {
        // Encontra a assinatura do satélite solicitado (por key + level_id)
        $detail_m = null;
        foreach ( $satellite_memberships as $m ) {
            $key_match   = ( $m['_satellite_key'] ?? '' ) === $plano_param;
            $level_match = ! $level_param || intval( $m['ID'] ?? $m['id'] ?? 0 ) === $level_param;
            if ( $key_match && $level_match ) {
                $detail_m = $m;
                break;
            }
        }

        if ( ! $detail_m ) {
            wp_redirect( $account_url );
            exit;
        }

        $sat_label  = $detail_m['_satellite_label'];
        $sat_url    = $detail_m['_satellite_url'];
        $sat_key    = $detail_m['_satellite_key'];
        $detail_lvl = (int) ( $detail_m['ID'] ?? $detail_m['id'] ?? 0 );
        $level_name = $detail_m['name'] ?? '';
        $billing_amt = floatval( $detail_m['billing_amount'] ?? 0 );
        $cycle      = strtolower( $detail_m['cycle_period'] ?? '' );
        $periodo    = match( $cycle ) { 'month' => 'mês', 'year' => 'ano', default => '' };
        $preco      = $billing_amt > 0 ? 'R$ ' . number_format( $billing_amt, 2, ',', '.' ) . ( $periodo ? "/{$periodo}" : '' ) : 'Gratuito';
        $startdate  = ! empty( $detail_m['startdate'] ) ? date_i18n( 'd/m/Y à\s H:i', (int) $detail_m['startdate'] ) : '—';
        $enddate    = ( ! empty( $detail_m['enddate'] ) && $detail_m['enddate'] !== '0000-00-00 00:00:00' )
            ? date_i18n( 'd/m/Y', strtotime( $detail_m['enddate'] ) ) : '—';
        $cancel_sat = trailingslashit( $sat_url ) . 'conta-de-associacao/cancelamento-da-associacao/';
        $detail_orders = BBL_Satellite_Memberships::get_orders( $current_user->ID, $sat_key, 20 );

    } else {
        // Satélite local — só tem um plano
        if ( ! $membership ) {
            wp_redirect( $account_url );
            exit;
        }
        $sat_label  = get_bloginfo( 'name' );
        $sat_url    = home_url();
        $sat_key    = $plano_param;
        $detail_lvl = (int) $membership->id;
        $level_name = $membership->name;
        $billing_amt = floatval( $membership->billing_amount ?? 0 );
        $cycle      = strtolower( $membership->cycle_period ?? '' );
        $periodo    = match( $cycle ) { 'month' => 'mês', 'year' => 'ano', default => '' };
        $preco      = $billing_amt > 0 ? 'R$ ' . number_format( $billing_amt, 2, ',', '.' ) . ( $periodo ? "/{$periodo}" : '' ) : 'Gratuito';
        $startdate  = ! empty( $membership->startdate ) ? date_i18n( 'd/m/Y à\s H:i', (int) $membership->startdate ) : '—';
        $enddate    = ( ! empty( $membership->enddate ) && $membership->enddate !== '0000-00-00 00:00:00' )
            ? date_i18n( 'd/m/Y', strtotime( $membership->enddate ) ) : '—';
        $cancel_sat = $cancel_url;
        $detail_orders = [];
        foreach ( $orders_raw as $o ) {
            $ts    = $o->getTimestamp();
            $mid   = $o->membership_id ?? null;
            $lname = $mid ? ( pmpro_getLevel( $mid )->name ?? $membership->name ) : $membership->name;
            $detail_orders[] = [
                'timestamp'  => $ts ? date( 'Y-m-d H:i:s', $ts ) : null,
                'level_name' => $lname,
                'total'      => $o->total,
                'status'     => $o->status,
                'code'       => $o->code,
            ];
        }
    }

    // Estado de teste/cancelamento na tela de detalhe: só quando o plano detalhado é do
    // satélite atual — a sub local é a fonte da verdade. A subscription marcada para
    // encerrar no fim do período (bbl_cancel_at_period_end) exibe o aviso CANCELADA,
    // com precedência sobre o selo de teste e independente de já ter havido cobrança
    // real: cancelou = acesso garantido até a data-limite.
    $detail_expire   = '';    // d/m/Y H:i em que o acesso termina
    $detail_in_trial = false; // ainda em teste (nenhuma cobrança real realizada)
    $detail_cancel   = false;
    if ( class_exists( 'PMPro_Subscription' ) && ! empty( $detail_lvl )
         && wp_parse_url( (string) $sat_url, PHP_URL_HOST ) === wp_parse_url( home_url(), PHP_URL_HOST ) ) {
        global $wpdb;
        $subs = PMPro_Subscription::get_subscriptions_for_user( $current_user->ID, $detail_lvl, [ 'active', 'trialing' ] );
        foreach ( $subs as $sub ) {
            if ( 'stripe' !== $sub->get_gateway() ) continue;
            $next = $sub->get_next_payment_date( 'timestamp', true );
            if ( empty( $next ) || $next <= current_time( 'timestamp' ) ) continue;
            $cancel = function_exists( 'get_pmpro_subscription_meta' )
                && (bool) get_pmpro_subscription_meta( (int) $sub->get_id(), 'bbl_cancel_at_period_end', true );
            $paid = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}pmpro_membership_orders
                 WHERE user_id = %d AND membership_id = %d AND status = 'success' AND total > 0",
                $current_user->ID, $detail_lvl
            ) );
            if ( ! $cancel && $paid > 0 ) continue; // assinatura paga e ativa: fluxo normal
            $detail_expire   = $sub->get_next_payment_date( 'd/m/Y H:i', true );
            $detail_in_trial = ( $paid === 0 );
            $detail_cancel   = $cancel;
            break;
        }
    }
?>

<div class="bbl-account bbl-account--page">

    <?php echo $bbl_fiscal_action; ?>

    <!-- Voltar -->
    <a href="<?php echo esc_url( $account_url ); ?>" class="bbl-back-link">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Voltar para Minha conta
    </a>

    <!-- Cabeçalho da assinatura -->
    <div class="bbl-card">
        <div class="bbl-card-header">
            <div class="bbl-card-title">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#eb2a61" stroke-width="2.5" aria-hidden="true"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                <?php echo esc_html( $sat_label ); ?>
            </div>
            <span class="bbl-badge-active"><span class="bbl-pulse"></span>Ativo</span>
        </div>

        <p class="bbl-plan-name"><?php echo esc_html( $level_name ); ?></p>

        <div class="bbl-data-grid" style="margin:1rem 0;">
            <div class="bbl-data-item">
                <label>Valor</label>
                <span><?php echo esc_html( $preco ); ?></span>
            </div>
            <div class="bbl-data-item">
                <label>Membro desde</label>
                <span><?php echo esc_html( $startdate ); ?></span>
            </div>
            <?php if ( $enddate !== '—' || $detail_cancel ) : ?>
            <div class="bbl-data-item">
                <label><?php echo $detail_cancel ? 'Acesso até' : 'Próxima renovação'; ?></label>
                <span><?php echo $detail_cancel ? esc_html( $detail_expire ) : esc_html( $enddate ); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <?php if ( $detail_expire ) : ?>
        <div class="bbl-trial-note <?php echo $detail_cancel ? 'bbl-trial-note--cancel' : ''; ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <div>
                <?php if ( $detail_cancel ) : ?>
                <strong>ASSINATURA CANCELADA:</strong>
                o acesso permanece até o fim <?php echo $detail_in_trial ? 'do teste' : 'do período já pago'; ?> e nenhuma cobrança será feita depois.<br>
                Expira em <strong><?php echo esc_html( $detail_expire ); ?></strong>
                <?php elseif ( $detail_in_trial ) : ?>
                <strong>Período de Teste</strong> · expira em <strong><?php echo esc_html( $detail_expire ); ?></strong>.<br>
                Ao fim do teste, a assinatura (<?php echo esc_html( $preco ); ?>) passa a ser cobrada automaticamente.
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php
        $detail_is_active = ( $detail_m['status'] ?? 'active' ) === 'active'
            || ( ! $is_hub && $membership && ( $membership->status ?? 'active' ) === 'active' );
        ?>
        <?php if ( $detail_is_active ) : ?>
        <a href="<?php echo esc_url( $sat_url ); ?>" class="bbl-cta" target="_blank" style="margin-bottom:.5rem;">
            <span class="bbl-cta-icon" aria-hidden="true">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
            </span>
            Acessar <?php echo esc_html( $sat_label ); ?>
        </a>
        <?php if ( ! $detail_cancel ) : ?>
        <div class="bbl-secondary-actions">
            <button class="bbl-btn-cancel" type="button"
                onclick="document.getElementById('bbl-cancel-detail').classList.add('open')">
                Cancelar assinatura
            </button>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Faturas -->
    <?php if ( ! empty( $detail_orders ) ) : ?>
    <div class="bbl-card">
        <div class="bbl-card-header">
            <div class="bbl-card-title">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Histórico de faturas
            </div>
        </div>
        <table class="bbl-invoice-table">
            <thead>
                <tr><th>Data</th><th>Plano</th><th>Valor</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php foreach ( $detail_orders as $o ) :
                $s_label = ( $o['status'] ?? '' ) === 'success' ? 'Pago' : ucfirst( $o['status'] ?? '' );
                $s_class = ( $o['status'] ?? '' ) === 'success' ? 'paid' : 'pending';
            ?>
                <tr>
                    <td><?php echo ! empty( $o['timestamp'] ) ? date_i18n( 'd/m/Y', strtotime( $o['timestamp'] ) ) : '—'; ?></td>
                    <td><?php echo esc_html( $o['level_name'] ?? '—' ); ?></td>
                    <td>R$ <?php echo number_format( (float) ( $o['total'] ?? 0 ), 2, ',', '.' ); ?></td>
                    <td><span class="bbl-status bbl-status--<?php echo esc_attr( $s_class ); ?>"><?php echo esc_html( $s_label ); ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div>

<!-- Modal cancelamento -->
<div class="bbl-modal-backdrop" id="bbl-cancel-detail"
     onclick="if(event.target===this)this.classList.remove('open')">
    <div class="bbl-modal" role="dialog" aria-modal="true">
        <h3>Cancelar assinatura do <?php echo esc_html( $sat_label ); ?>?</h3>
        <p>Você perderá acesso ao conteúdo ao final do período contratado. Essa ação não pode ser desfeita.</p>
        <div class="bbl-modal-actions">
            <button class="bbl-modal-back" type="button"
                onclick="document.getElementById('bbl-cancel-detail').classList.remove('open')">
                Manter assinatura
            </button>
            <a href="<?php echo esc_url( $cancel_sat ); ?>" class="bbl-modal-confirm">Sim, cancelar</a>
        </div>
    </div>
</div>

<?php
return; // Não renderiza o restante do template
endif;
// ── Fim modo detalhe ─────────────────────────────────────────────────────────
?>

<div class="bbl-account bbl-account--page">

    <?php echo $bbl_fiscal_action; ?>

    <!-- Boas-vindas -->
    <div class="bbl-welcome">
        <div class="bbl-avatar"><?php echo esc_html( $initials ); ?></div>
        <div>
            <h2 class="bbl-welcome-name">Olá, <?php echo esc_html( $first_name ); ?>!</h2>
            <?php if ( $since ) : ?>
            <p class="bbl-welcome-sub">Membro do Bebelume desde <?php echo esc_html( $since ); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Assinaturas -->
    <?php
    // Monta lista unificada: satélites via API + local se satélite
    $all_memberships = $satellite_memberships;

    // Se for satélite e PMPro local tiver assinatura não coberta pela API
    if ( ! $is_hub && $membership ) {
        $local_already = false;
        foreach ( $all_memberships as $am ) {
            if ( intval( $am['ID'] ?? $am['id'] ?? 0 ) === intval( $membership->id ) ) {
                $local_already = true; break;
            }
        }
        if ( ! $local_already ) {
            $all_memberships[] = [
                'ID'             => $membership->id,
                'id'             => $membership->id,
                'name'           => $membership->name,
                'billing_amount' => $membership->billing_amount,
                'cycle_period'   => $membership->cycle_period,
                'startdate'      => $membership->startdate,
                'enddate'        => $membership->enddate,
                'status'         => 'active',
                '_satellite_key'   => 'local',
                '_satellite_label' => get_bloginfo('name'),
                '_satellite_url'   => home_url(),
            ];
        }
    }

    $has_active = false;
    foreach ( $all_memberships as $am ) {
        if ( ( $am['status'] ?? 'active' ) === 'active' ) { $has_active = true; break; }
    }

    // Período de teste / cancelamento: assinatura local stripe active/trialing com
    // próxima cobrança no futuro. Só é exibida no card do SATÉLITE ATUAL (o host desta
    // página), onde a sub local é a fonte da verdade — cards remotos não têm esse dado.
    // Estado CANCELADO (meta bbl_cancel_at_period_end) tem precedência sobre o selo de
    // teste e independe de já ter havido cobrança real: cancelou = acesso garantido até
    // a data-limite, com aviso próprio.
    $home_host = wp_parse_url( home_url(), PHP_URL_HOST );
    $trial_by_level  = [];
    $cancel_by_level = [];
    if ( class_exists( 'PMPro_Subscription' ) ) {
        global $wpdb;
        $uid = $current_user->ID;
        $local_subs = PMPro_Subscription::get_subscriptions_for_user( $uid, null, [ 'active', 'trialing' ] );
        foreach ( $local_subs as $sub ) {
            if ( 'stripe' !== $sub->get_gateway() ) continue;
            $lv = (int) $sub->get_membership_level_id();
            if ( $lv < 1 ) continue;
            $next = $sub->get_next_payment_date( 'timestamp', true );
            if ( empty( $next ) || $next <= current_time( 'timestamp' ) ) continue;
            $cancelled = function_exists( 'get_pmpro_subscription_meta' )
                && (bool) get_pmpro_subscription_meta( (int) $sub->get_id(), 'bbl_cancel_at_period_end', true );
            if ( $cancelled ) {
                $cancel_by_level[ $lv ] = $sub->get_next_payment_date( 'd/m/Y H:i', true );
                continue; // cancelada: nunca mostra o selo de "teste em andamento"
            }
            $paid = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}pmpro_membership_orders
                 WHERE user_id = %d AND membership_id = %d AND status = 'success' AND total > 0",
                $uid, $lv
            ) );
            if ( $paid > 0 ) continue;
            $trial_by_level[ $lv ] = $sub->get_next_payment_date( 'd/m/Y H:i', true );
        }
    }
    ?>

    <?php if ( empty( $all_memberships ) ) : ?>
    <div class="bbl-card bbl-card--empty">
        <p>Você não possui nenhuma assinatura.</p>
        <div style="display:flex;gap:12px;margin-top:1rem;flex-wrap:wrap;">
            <a href="https://canal.bebelume.com.br/canal-bebelume/planos/" class="bbl-cta">Ver planos Canal</a>
            <a href="https://arteduca.bebelume.com.br/planos/" class="bbl-cta" style="background:#3acbe7;">Ver planos ArtEduca</a>
        </div>
    </div>
    <?php else : ?>
        <?php foreach ( $all_memberships as $m ) :
            $sat_key    = $m['_satellite_key'] ?? 'local';
            $sat_label  = $m['_satellite_label'] ?? get_bloginfo('name');
            $sat_url    = $m['_satellite_url'] ?? home_url();
            $level_name = $m['name'] ?? '';
            $is_active  = ( $m['status'] ?? 'active' ) === 'active';
            $m_level_id = (int) ( $m['ID'] ?? $m['id'] ?? 0 );
            $detail_url = add_query_arg( [ 'plano' => $sat_key, 'level' => $m_level_id ], pmpro_url('account') );
            $startdate  = ! empty( $m['startdate'] ) ? date_i18n( 'd/m/Y à\s H:i', is_numeric($m['startdate']) ? (int)$m['startdate'] : strtotime($m['startdate']) ) : '';
            $sat_host   = wp_parse_url( ( $m['_satellite_url'] ?? '' ), PHP_URL_HOST );
            $trial_expire = ( $is_active && $m_level_id && $sat_host === $home_host && isset( $trial_by_level[ $m_level_id ] ) )
                ? $trial_by_level[ $m_level_id ]
                : '';
            $cancel_expire = ( $is_active && $m_level_id && $sat_host === $home_host && isset( $cancel_by_level[ $m_level_id ] ) )
                ? $cancel_by_level[ $m_level_id ]
                : '';
        ?>
        <a href="<?php echo esc_url( $detail_url ); ?>" class="bbl-card bbl-card--link <?php echo $is_active ? '' : 'bbl-card--inactive'; ?>">
            <div class="bbl-card-header">
                <div class="bbl-card-title">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="<?php echo $is_active ? '#eb2a61' : '#9aa3ae'; ?>" stroke-width="2.5" aria-hidden="true"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    <?php echo esc_html( $sat_label ); ?>
                </div>
                <?php if ( $is_active ) : ?>
                <span class="bbl-badge-active"><span class="bbl-pulse"></span>Ativo</span>
                <?php else : ?>
                <span class="bbl-badge-inactive">Inativo</span>
                <?php endif; ?>
            </div>

            <p class="bbl-plan-name"><?php echo esc_html( $level_name ); ?></p>
            <?php if ( $startdate ) : ?>
            <p class="bbl-plan-meta">Assinante desde <strong><?php echo esc_html( $startdate ); ?></strong></p>
            <?php endif; ?>

            <?php if ( $cancel_expire ) : ?>
            <p class="bbl-cancel-pill">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>Assinatura Cancelada - Expira em: <strong><?php echo esc_html( $cancel_expire ); ?></strong></span>
            </p>
            <?php elseif ( $trial_expire ) : ?>
            <p class="bbl-trial-msg">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span>Período de Teste · Expira em: <strong><?php echo esc_html( $trial_expire ); ?></strong></span>
            </p>
            <?php endif; ?>

            <span class="bbl-card-details-label">Mais detalhes</span>
            <div class="bbl-card-arrow" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </div>
        </a>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Dados da conta -->
    <div class="bbl-card">
        <div class="bbl-card-header">
            <div class="bbl-card-title">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Dados da conta
            </div>
            <button type="button" class="bbl-btn-outline" data-bs-toggle="modal" data-bs-target="#bbl-password-modal">Alterar senha</button>
        </div>
        <div class="bbl-data-grid">
            <div class="bbl-data-item">
                <label>Nome</label>
                <span><?php echo esc_html( $full_name ); ?></span>
            </div>
            <div class="bbl-data-item">
                <label>E-mail</label>
                <span><?php echo esc_html( $current_user->user_email ); ?></span>
            </div>
            <?php if ( $cpf ) : ?>
            <div class="bbl-data-item">
                <label>CPF</label>
                <span><?php echo esc_html( $cpf ); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Faturas movidas para a página de detalhe da assinatura -->

</div>

<!-- Modal de cancelamento -->
<?php if ( $cancel_url ) : ?>
<div class="bbl-modal-backdrop" id="bbl-cancel-modal" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="bbl-modal" role="dialog" aria-modal="true" aria-labelledby="bbl-modal-title">
        <h3 id="bbl-modal-title">Cancelar assinatura?</h3>
        <p>Você perderá acesso a todo o conteúdo do Canal Bebelume ao final do período contratado. Essa ação não pode ser desfeita.</p>
        <div class="bbl-modal-actions">
            <button class="bbl-modal-back" type="button" onclick="document.getElementById('bbl-cancel-modal').classList.remove('open')">
                Manter assinatura
            </button>
            <a href="<?php echo esc_url( $cancel_url ); ?>" class="bbl-modal-confirm">Sim, cancelar</a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal alterar senha — Bootstrap -->
<div class="modal fade" id="bbl-password-modal" tabindex="-1" aria-labelledby="bbl-pw-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px;border:1px solid #e4e8ed;box-shadow:0 20px 60px rgba(0,0,0,.15);">
            <div class="modal-header" style="border-bottom:1px solid #e4e8ed;padding:1.25rem 1.5rem;">
                <h5 class="modal-title" id="bbl-pw-modal-title" style="font-family:'Nunito',sans-serif;font-weight:700;font-size:15px;color:#1a1a2e;">Alterar senha</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" style="padding:1.5rem;display:flex;flex-direction:column;gap:1rem;">
                <div id="bbl-pw-msg" style="display:none;border-radius:8px;padding:10px 14px;font-size:13px;font-weight:600;font-family:'Nunito',sans-serif;"></div>
                <div>
                    <label for="bbl-new-password" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9aa3ae;font-family:'Nunito',sans-serif;display:block;margin-bottom:5px;">Nova senha</label>
                    <input type="password" id="bbl-new-password" class="form-control" placeholder="Digite a nova senha" autocomplete="new-password"
                        style="font-family:'Nunito',sans-serif;font-size:14px;background:#fafafa;border:1px solid #e4e8ed;border-radius:10px;padding:10px 14px;height:auto;" />
                </div>
                <div>
                    <label for="bbl-confirm-password" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9aa3ae;font-family:'Nunito',sans-serif;display:block;margin-bottom:5px;">Confirmar nova senha</label>
                    <input type="password" id="bbl-confirm-password" class="form-control" placeholder="Confirme a nova senha" autocomplete="new-password"
                        style="font-family:'Nunito',sans-serif;font-size:14px;background:#fafafa;border:1px solid #e4e8ed;border-radius:10px;padding:10px 14px;height:auto;" />
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #e4e8ed;padding:1rem 1.5rem;background:#fafafa;border-radius:0 0 14px 14px;">
                <button type="button" class="btn" data-bs-dismiss="modal"
                    style="font-family:'Nunito',sans-serif;font-size:13px;font-weight:600;border:1px solid #e4e8ed;color:#6b7480;border-radius:99px;padding:8px 16px;">Cancelar</button>
                <button type="button" id="bbl-pw-submit"
                    style="font-family:'Nunito',sans-serif;font-size:14px;font-weight:700;background:#eb2a61;color:#fff;border:none;border-radius:99px;padding:10px 24px;cursor:pointer;transition:background .15s;">Salvar senha</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var btn   = document.getElementById('bbl-pw-submit');
    var msgEl = document.getElementById('bbl-pw-msg');
    var newPw = document.getElementById('bbl-new-password');
    var confPw= document.getElementById('bbl-confirm-password');
    var modal = document.getElementById('bbl-password-modal');

    // Limpa campos e mensagem ao abrir
    modal.addEventListener('show.bs.modal', function() {
        newPw.value = '';
        confPw.value = '';
        msgEl.style.display = 'none';
        btn.disabled = false;
        btn.textContent = 'Salvar senha';
    });

    function showMsg(text, type) {
        msgEl.textContent = text;
        msgEl.style.display = 'block';
        if (type === 'success') {
            msgEl.style.background = '#eaf3de';
            msgEl.style.color = '#3b6d11';
            msgEl.style.borderLeft = '3px solid #3b6d11';
        } else {
            msgEl.style.background = '#fff0f3';
            msgEl.style.color = '#c0254f';
            msgEl.style.borderLeft = '3px solid #eb2a61';
        }
    }

    btn.addEventListener('click', function() {
        var pw1 = newPw.value.trim();
        var pw2 = confPw.value.trim();

        if (!pw1) { showMsg('Por favor, digite a nova senha.', 'error'); return; }
        if (pw1.length < 6) { showMsg('A senha deve ter pelo menos 6 caracteres.', 'error'); return; }
        if (pw1 !== pw2) { showMsg('As senhas não coincidem.', 'error'); return; }

        btn.disabled = true;
        btn.textContent = 'Salvando...';

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '<?php echo esc_url( admin_url("admin-ajax.php") ); ?>');
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            btn.disabled = false;
            btn.textContent = 'Salvar senha';
            try {
                var resp = JSON.parse(xhr.responseText);
                if (resp.success) {
                    showMsg('Senha alterada com sucesso!', 'success');
                    newPw.value = '';
                    confPw.value = '';
                    setTimeout(function() {
                        var bsModal = bootstrap.Modal.getOrCreateInstance(modal);
                        bsModal.hide();
                        // Garante limpeza do backdrop
                        document.querySelectorAll('.modal-backdrop').forEach(function(el){ el.remove(); });
                        document.body.classList.remove('modal-open');
                        document.body.style.removeProperty('overflow');
                        document.body.style.removeProperty('padding-right');
                    }, 2000);
                } else {
                    showMsg(resp.data || 'Erro ao alterar a senha.', 'error');
                }
            } catch(e) {
                showMsg('Erro inesperado. Tente novamente.', 'error');
            }
        };
        xhr.onerror = function() {
            btn.disabled = false;
            btn.textContent = 'Salvar senha';
            showMsg('Falha na conexão. Tente novamente.', 'error');
        };
        xhr.send('action=bbl_change_password&nonce=<?php echo esc_js( wp_create_nonce("bbl_change_password") ); ?>&new_password=' + encodeURIComponent(pw1));
    });
})();
</script>
