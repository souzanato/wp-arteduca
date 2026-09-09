<?php
/**
 * BBL_Prevent_Duplicate_Subscription — Garante no máximo 1 subscription cobrável por (usuário, plano)
 *
 * Contexto: caso Shirlene (set/2026) — dois checkouts do MESMO plano conseguiram criar duas
 * subscriptions Stripe ATIVAS. O PMPro só cancela a anterior de forma reativa dentro de
 * pmpro_changeMembershipLevel (early-return em functions.php:1169 quando o nível já está
 * ativo), então em condição de corrida ou falha parcial do 2º checkout a duplicata segue
 * ativa e ambas cobram.
 *
 * Regra de negócio (Leo/Clarisse): no máximo 1 subscription que VAI COBRAR por
 * (usuário, plano). O único caso legítimo de 2 subscriptions no mesmo plano é
 * cancelou -> reinscreveu, em que a anterior está agendada para não renovar.
 *
 * Estratégia em 2 camadas (100% em banco no caminho do guard):
 *  - Camada A (garantia): em `pmpro_added_subscription`, chama cancel_at_gateway() em toda
 *    sub cobrável "extra" do par, mantendo a de menor id (mais antiga = original).
 *  - Camada B (prevenção): em `pmpro_checkout_order_creation_checks`, aborta novo checkout
 *    quando o usuário já tem sub cobrável do nível sendo comprado.
 *
 * "Cobrável" = sub local stripe com status active/trialing e SEM o marcador
 * bbl_cancel_at_period_end (=1), gravado pelo módulo de cancelamento quando agenda o fim.
 *
 * @package Bebelume_PMPro_Profile
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BBL_Prevent_Duplicate_Subscription {

    private static $instance = null;

    /** Meta que marca sub agendada para encerrar no fim do período (não vai mais cobrar). */
    const META_END = 'bbl_cancel_at_period_end';

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'pmpro_added_subscription', [ $this, 'on_subscription_added' ], 10, 1 );
        add_filter( 'pmpro_checkout_order_creation_checks', [ $this, 'block_duplicate_checkout' ], 10, 2 );
    }

    // =========================================================================
    // CAMADA A — garantia: varre e cancela duplicata no momento em que nasce
    // =========================================================================

    public function on_subscription_added( $sub ): void {
        if ( empty( $sub ) || ! is_a( $sub, 'PMPro_Subscription' ) ) {
            return;
        }
        $this->sweep_for_level( (int) $sub->get_user_id(), (int) $sub->get_membership_level_id() );
    }

    /**
     * Cancela via cancel_at_gateway() toda sub cobrável "extra" do par (usuário, nível),
     * preservando a de menor id (mais antiga = original). Retorna quantas foram removidas.
     */
    public function sweep_for_level( int $user_id, int $level_id ): int {
        if ( ! class_exists( 'PMPro_Subscription' ) ) {
            return 0;
        }

        $subs = PMPro_Subscription::get_subscriptions_for_user( $user_id, $level_id, [ 'active', 'trialing' ] );
        if ( empty( $subs ) ) {
            return 0;
        }

        $chargeable = [];
        foreach ( $subs as $sub ) {
            if ( $this->is_chargeable( $sub ) ) {
                $chargeable[ (int) $sub->get_id() ] = $sub;
            }
        }
        if ( count( $chargeable ) <= 1 ) {
            return 0;
        }

        // Mantém a de menor id; as demais são duplicatas.
        ksort( $chargeable, SORT_NUMERIC );
        array_shift( $chargeable );

        $removed = 0;
        foreach ( $chargeable as $sub ) {
            try {
                update_pmpro_subscription_meta( $sub->get_id(), 'bbl_removed_as_duplicate', current_time( 'mysql' ) );
                $sub->cancel_at_gateway();
                error_log(
                    '[bbl-prevent-duplicate] Sub #' . $sub->get_id()
                    . ' (' . $sub->get_subscription_transaction_id() . ') cancelada como duplicata'
                    . ' do nível ' . $level_id . ' do usuário ' . $user_id
                );
                $removed++;
            } catch ( \Throwable $e ) {
                error_log( '[bbl-prevent-duplicate] Falha ao cancelar sub duplicata #' . $sub->get_id() . ': ' . $e->getMessage() );
            }
        }

        return $removed;
    }

    // =========================================================================
    // CAMADA B — prevenção: bloqueia novo checkout quando já há sub cobrável
    // =========================================================================

    public function block_duplicate_checkout( $continue, $pmpro_level ) {
        if ( ! $continue ) {
            return $continue;
        }

        $user_id = get_current_user_id();
        if ( empty( $user_id ) || empty( $pmpro_level ) || empty( $pmpro_level->id ) ) {
            return $continue;
        }

        if ( $this->has_chargeable_sub( (int) $user_id, (int) $pmpro_level->id ) ) {
            pmpro_setMessage(
                __( 'Você já possui uma assinatura ativa para este plano, então não é possível assinar o mesmo plano novamente (evitamos cobrança em duplicidade). Se você cancelou e quer voltar a assinar, ou se acha que isto é um engano, fale com o suporte.', 'bebelume-pmpro-profile' ),
                'pmpro_error'
            );
            return false;
        }

        return $continue;
    }

    // =========================================================================
    // DEFINIÇÃO DE "COBRÁVEL"
    // =========================================================================

    /**
     * Sub que ainda vai cobrar: stripe, status active/trialing e sem marcador de
     * agendada-para-encerrar. Subs agendadas para o fim do período (cancelou -> reinscreveu)
     * não entram: continuam `active` localmente mas não renovam.
     */
    private function is_chargeable( $sub ): bool {
        if ( empty( $sub ) || ! is_a( $sub, 'PMPro_Subscription' ) ) {
            return false;
        }
        if ( 'stripe' !== $sub->get_gateway() ) {
            return false;
        }
        if ( ! in_array( $sub->get_status(), [ 'active', 'trialing' ], true ) ) {
            return false;
        }
        if ( get_pmpro_subscription_meta( (int) $sub->get_id(), self::META_END, true ) ) {
            return false;
        }
        return true;
    }

    private function has_chargeable_sub( int $user_id, int $level_id ): bool {
        if ( ! class_exists( 'PMPro_Subscription' ) ) {
            return false;
        }
        $subs = PMPro_Subscription::get_subscriptions_for_user( $user_id, $level_id, [ 'active', 'trialing' ] );
        if ( empty( $subs ) ) {
            return false;
        }
        foreach ( $subs as $sub ) {
            if ( $this->is_chargeable( $sub ) ) {
                return true;
            }
        }
        return false;
    }
}

add_action( 'plugins_loaded', function () {
    if ( ! function_exists( 'pmpro_getOption' ) || ! class_exists( 'PMProGateway_stripe' ) ) {
        return;
    }
    BBL_Prevent_Duplicate_Subscription::get_instance();
} );
