<?php
/**
 * bbl-reconcile.php — Reconciliação cancelamento × acesso (T05)
 *
 * Read-only. NÃO grava nada. Serve de "antes/depois": rodar, decidir cada item,
 * aplicar correção pontual (com backup+aprovação) fora desta ferramenta e reexecutar.
 *
 * Uso (CLI):
 *   wp eval-file wp-content/plugins/bebelume-pmpro-profile/tools/bbl-reconcile.php
 *
 * Seções:
 *   A. Órfãs — membership ativa sem assinatura Stripe active/trialing (ex.: plano dado à mão).
 *   B. Cancelou × acesso — assinatura cancelled mas membership active com fim futuro.
 *   C. Duplicatas — mais de uma linha active/changed por usuário+nível.
 *   D. Assinatura ativa sem membership ativa correspondente.
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

global $wpdb;
$mu = $wpdb->prefix . 'pmpro_memberships_users';
$sub = $wpdb->prefix . 'pmpro_subscriptions';
$now_ts = current_time( 'timestamp' );

$user_label = function ( $uid ) {
    static $cache = array();
    if ( ! isset( $cache[ $uid ] ) ) {
        $u = get_userdata( (int) $uid );
        $cache[ $uid ] = $u ? $u->user_email : "user#{$uid}";
    }
    return $cache[ $uid ];
};
$level_label = function ( $lid ) {
    static $cache = array();
    if ( ! isset( $cache[ $lid ] ) ) {
        $lv = function_exists( 'pmpro_getLevel' ) ? pmpro_getLevel( (int) $lid ) : null;
        $cache[ $lid ] = $lv ? $lv->name : "level#{$lid}";
    }
    return $cache[ $lid ];
};

echo "== A) Órfãs: membership active SEM assinatura active/trialing ==" . PHP_EOL;
$orphans = $wpdb->get_results(
    "SELECT m.user_id, m.membership_id, m.startdate, m.enddate
       FROM {$mu} m
      WHERE m.status = 'active'
        AND NOT EXISTS (
            SELECT 1 FROM {$sub} s
             WHERE s.user_id = m.user_id AND s.membership_level_id = m.membership_id
               AND s.status IN ('active','trialing') AND s.gateway = 'stripe'
        )
      ORDER BY m.user_id"
);
if ( empty( $orphans ) ) {
    echo "Nenhuma órfã.\n";
} else {
    foreach ( $orphans as $r ) {
        echo "- {$user_label( $r->user_id )} ({$r->user_id}) | {$level_label( $r->membership_id )}  desde {$r->startdate}  fim=" . ( ( '0000-00-00 00:00:00' === $r->enddate ) ? '(indeterminado)' : $r->enddate ) . PHP_EOL;
    }
}
echo PHP_EOL;

echo "== B) Cancelou mas membership ainda active (acesso até fim? decidir) ==" . PHP_EOL;
$can = $wpdb->get_results(
    "SELECT m.user_id, m.membership_id, m.startdate, m.enddate, s.subscription_transaction_id, s.status AS sub_status, s.enddate AS sub_enddate
       FROM {$mu} m
       JOIN {$sub} s ON s.user_id = m.user_id AND s.membership_level_id = m.membership_id AND s.status = 'cancelled'
      WHERE m.status = 'active' AND m.enddate > '0000-00-00 00:00:00' AND m.enddate > NOW()
      ORDER BY m.enddate"
);
if ( empty( $can ) ) {
    echo "Nenhuma.\n";
} else {
    foreach ( $can as $r ) {
        echo "- {$user_label( $r->user_id )} ({$r->user_id}) | {$level_label( $r->membership_id )}  acesso até {$r->enddate}  (sub {$r->subscription_transaction_id} cancelled)" . PHP_EOL;
    }
}
echo PHP_EOL;

echo "== C) Duplicatas: mais de uma linha active/changed por usuário+nível ==" . PHP_EOL;
$dups = $wpdb->get_results(
    "SELECT user_id, membership_id, COUNT(*) c
       FROM {$mu}
      WHERE status IN ('active','changed')
      GROUP BY user_id, membership_id HAVING c > 1
      ORDER BY user_id"
);
if ( empty( $dups ) ) {
    echo "Nenhuma duplicata.\n";
} else {
    foreach ( $dups as $r ) {
        $linhas = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, status, startdate, enddate FROM {$mu} WHERE user_id = %d AND membership_id = %d AND status IN ('active','changed') ORDER BY id",
            (int) $r->user_id, (int) $r->membership_id
        ) );
        echo "- {$user_label( $r->user_id )} ({$r->user_id}) | {$level_label( $r->membership_id )}  ({$r->c} linhas)";
        foreach ( $linhas as $l ) {
            echo "  [{$l->id} {$l->status} {$l->startdate}]";
        }
        echo PHP_EOL;
    }
}
echo PHP_EOL;

echo "== D) Assinatura active/trialing SEM membership active ==" . PHP_EOL;
$subonly = $wpdb->get_results(
    "SELECT s.id AS sub_id, s.user_id, s.membership_level_id, s.status, s.subscription_transaction_id
       FROM {$sub} s
      WHERE s.gateway = 'stripe' AND s.status IN ('active','trialing')
        AND NOT EXISTS (
            SELECT 1 FROM {$mu} m
             WHERE m.user_id = s.user_id AND m.membership_id = s.membership_level_id AND m.status = 'active'
        )
      ORDER BY s.user_id"
);
if ( empty( $subonly ) ) {
    echo "Nenhuma.\n";
} else {
    foreach ( $subonly as $r ) {
        echo "- sub#{$r->sub_id} {$r->subscription_transaction_id}  {$user_label( $r->user_id )} ({$r->user_id}) | {$level_label( $r->membership_id )}  status={$r->status}" . PHP_EOL;
    }
}

echo PHP_EOL . "FIM (read-only). Corrigir dados apenas com backup + aprovação.\n";
