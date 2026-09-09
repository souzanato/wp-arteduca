<?php
/**
 * bbl-monitor.php — Visibilidade de cobranças + falhas recentes (T04)
 *
 * Read-only. NÃO envia e-mail e NÃO grava nada.
 *
 * Uso (CLI, com o WordPress carregado):
 *   wp eval-file wp-content/plugins/bebelume-pmpro-profile/tools/bbl-monitor.php
 *   wp eval-file .../bbl-monitor.php -- days=7
 *
 * Imprime:
 *   1) Próximas cobranças (assinaturas Stripe live active/trialing) cujo próximo
 *      vencimento cai em até N dias (default 10), em hora local America/Sao_Paulo.
 *   2) Divergência: assinatura local "ativa" sem contraparte ativa no Stripe.
 *   3) Últimas falhas locais (orders error/pending), com o motivo do T01 se gravado.
 *
 * @package Bebelume_PMPro_Profile
 */

if ( ! defined( 'ABSPATH' ) ) {
    if ( PHP_SAPI !== 'cli' ) {
        http_response_code( 403 );
        exit;
    }
}

if ( PHP_SAPI !== 'cli' && ! defined( 'WP_CLI' ) ) {
    fwrite( STDERR, "Somente via CLI (wp eval-file).\n" );
    exit( 1 );
}

$days = 10;
$cli_args = isset( $args ) && is_array( $args ) ? $args : array();
foreach ( $cli_args as $arg ) {
    if ( preg_match( '/^days=(\d+)$/', (string) $arg, $m ) ) {
        $days = (int) $m[1];
    }
}

$fmt_ts = function ( $ts ) {
    return function_exists( 'wp_date' ) ? wp_date( 'Y-m-d H:i', (int) $ts ) : date( 'Y-m-d H:i', (int) $ts );
};

// Orders guardam timestamp como DATETIME UTC; converte para o fuso do site (T07).
$fmt_db = function ( $v ) use ( $fmt_ts ) {
    if ( empty( $v ) ) {
        return '';
    }
    if ( is_numeric( $v ) && (int) $v > 0 ) {
        return $fmt_ts( (int) $v );
    }
    $ts = strtotime( (string) $v );
    return $ts ? ( function_exists( 'wp_date' ) ? wp_date( 'Y-m-d H:i', $ts ) : date( 'Y-m-d H:i', $ts ) ) : (string) $v;
};

// ---------------------------------------------------------------------------
// 1) Mapa local de assinaturas active/trialing (id Stripe -> dados do WP)
// ---------------------------------------------------------------------------
global $wpdb;
$local = [];
$rows  = $wpdb->get_results(
    "SELECT subscription_transaction_id, user_id, membership_level_id, status
       FROM {$wpdb->prefix}pmpro_subscriptions
      WHERE gateway = 'stripe' AND gateway_environment = 'live'
        AND status IN ('active','trialing')"
);
foreach ( $rows as $r ) {
    $u                       = get_userdata( (int) $r->user_id );
    $lv                      = function_exists( 'pmpro_getLevel' ) ? pmpro_getLevel( (int) $r->membership_level_id ) : null;
    $local[ $r->subscription_transaction_id ] = [
        'email'  => $u ? $u->user_email : "user#{$r->user_id}",
        'level'  => $lv ? $lv->name : "level#{$r->membership_level_id}",
        'user'   => (int) $r->user_id,
        'local'  => $r->status,
    ];
}

// ---------------------------------------------------------------------------
// 2) Estado das assinaturas no Stripe (live, leitura)
// ---------------------------------------------------------------------------
if ( ! class_exists( '\Stripe\Stripe' ) ) {
    require_once PMPRO_DIR . '/includes/lib/Stripe/init.php';
}
$key = PMProGateway_stripe::using_api_keys()
    ? get_option( 'pmpro_stripe_secretkey' )
    : get_option( 'pmpro_live_stripe_connect_secretkey' );
\Stripe\Stripe::setApiKey( $key );

$stripe_subs = [];
foreach ( array( 'active', 'trialing', 'past_due', 'canceled' ) as $st ) {
    try {
        $coll = \Stripe\Subscription::all( array( 'status' => $st, 'limit' => 100 ) );
        foreach ( $coll->data as $sub ) {
            $stripe_subs[ $sub->id ] = $sub;
        }
    } catch ( \Throwable $e ) {
        fwrite( STDERR, "Erro Stripe (status=$st): {$e->getMessage()}\n" );
    }
}

// ---------------------------------------------------------------------------
// Saída 1 — próximas cobranças
// ---------------------------------------------------------------------------
$now  = time();
$soon = [];
foreach ( $stripe_subs as $id => $sub ) {
    if ( ! in_array( $sub->status, array( 'active', 'trialing', 'past_due' ), true ) ) {
        continue;
    }
    $next = ! empty( $sub->trial_end ) ? (int) $sub->trial_end : (int) $sub->current_period_end;
    if ( $next <= $now ) {
        continue;
    }
    $meta  = isset( $local[ $id ] ) ? $local[ $id ] : array( 'email' => '(sem WP)', 'level' => '', 'user' => 0, 'local' => '' );
    $item  = $sub->items->data[0]->price ?? null;
    $amt   = $item ? ( $item->unit_amount / 100 ) : null;
    $cur   = $item ? strtoupper( $item->currency ) : '';
    $soon[] = array(
        'date'   => $next,
        'email'  => $meta['email'],
        'level'  => $meta['level'],
        'amount' => ( null !== $amt ) ? $amt . ( $cur ? " $cur" : '' ) : '',
        'status' => $sub->status . ( $sub->cancel_at_period_end ? ' (cancela no fim)' : '' ),
    );
}
usort( $soon, function ( $a, $b ) { return $a['date'] - $b['date']; } );

echo "== Próximas cobranças (próximos {$days} dias) — hora local ==" . PHP_EOL;
echo str_pad( 'Data', 17 ) . str_pad( 'Valor', 12 ) . str_pad( 'Status', 22 ) . 'E-mail | Plano' . PHP_EOL;
$shown = 0;
foreach ( $soon as $s ) {
    if ( $s['date'] - $now > $days * DAY_IN_SECONDS ) {
        continue;
    }
    echo str_pad( $fmt_ts( $s['date'] ), 17 )
        . str_pad( $s['amount'], 12 )
        . str_pad( $s['status'], 22 )
        . "{$s['email']} | {$s['level']}" . PHP_EOL;
    $shown++;
}
echo $shown ? "Total na janela: {$shown}\n" : "Nenhuma cobrança nos próximos {$days} dias.\n";
echo PHP_EOL;

// ---------------------------------------------------------------------------
// Saída 2 — local ativa sem contraparte ativa no Stripe
// ---------------------------------------------------------------------------
echo "== Local 'ativa/trialing' sem assinatura ativa correspondente no Stripe ==" . PHP_EOL;
$found = 0;
foreach ( $local as $id => $meta ) {
    $st = isset( $stripe_subs[ $id ] ) ? $stripe_subs[ $id ]->status : '(não existe no Stripe)';
    if ( ! in_array( $st, array( 'active', 'trialing', 'past_due' ), true ) ) {
        echo "- {$id}  local={$meta['local']}  stripe={$st}  {$meta['email']} | {$meta['level']}" . PHP_EOL;
        $found++;
    }
}
echo $found ? "({$found} divergências)\n" : "Nenhuma divergência.\n";
echo PHP_EOL;

// ---------------------------------------------------------------------------
// Saída 3 — falhas locais recentes (orders error/pending) com motivo do T01
// ---------------------------------------------------------------------------
echo "== Últimas falhas de cobrança no WP (orders error/pending) ==" . PHP_EOL;
$fails = $wpdb->get_results(
    "SELECT id, user_id, total, payment_transaction_id, timestamp
       FROM {$wpdb->prefix}pmpro_membership_orders
      WHERE status IN ('error','pending')
      ORDER BY id DESC
      LIMIT 15"
);
if ( empty( $fails ) ) {
    echo "Nenhuma ordem com status error/pending.\n";
} else {
    foreach ( $fails as $f ) {
        $code = $wpdb->get_var( $wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->prefix}pmpro_membership_ordermeta WHERE pmpro_membership_order_id = %d AND meta_key = 'bbl_stripe_decline_code'",
            (int) $f->id
        ) );
        $msg  = $wpdb->get_var( $wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->prefix}pmpro_membership_ordermeta WHERE pmpro_membership_order_id = %d AND meta_key = 'bbl_stripe_decline_message'",
            (int) $f->id
        ) );
        $when = $fmt_db( $f->timestamp );
        echo "- ped.{$f->id}  {$when}  user={$f->user_id}  total={$f->total}  txn={$f->payment_transaction_id}" . PHP_EOL;
        if ( $code || $msg ) {
            echo "    motivo: {$code}" . ( $msg ? " — {$msg}" : '' ) . PHP_EOL;
        }
    }
}

echo PHP_EOL . "FIM (read-only).\n";
