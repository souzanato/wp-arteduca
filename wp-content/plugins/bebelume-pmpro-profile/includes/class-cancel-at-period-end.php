<?php
/**
 * BBL_Cancel_At_Period_End — Cancelamento com acesso até o fim do prazo
 *
 * Intercepta o cancelamento self-service do PMPro e usa cancel_at_period_end
 * no Stripe em vez do cancel() imediato, mantendo o acesso do assinante até
 * o fim do ciclo pago (decisão Leo/Clarisse — ago/2026).
 *
 * O core (preheaders/cancel.php) chama $subscription->cancel(), que cancela
 * imediatamente no Stripe. Aqui bloqueamos esse fluxo e agendamos o
 * cancelamento para o fim do período: o Stripe mantém a assinatura ativa
 * (cancel_at_period_end = true), e o webhook customer.subscription.deleted
 * cancela localmente quando o prazo termina.
 *
 * @package Bebelume_PMPro_Profile
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BBL_Cancel_At_Period_End {

    private static $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Impede o cancelamento imediato do core (preheaders/cancel.php).
        add_filter( 'pmpro_cancel_should_process', [ $this, 'should_process' ], 10, 2 );
        // Depois do preheader do core rodar (wp, prio 2), executa o nosso fluxo.
        add_action( 'wp', [ $this, 'maybe_process' ], 5 );
    }

    /**
     * Nunca deixa o core cancelar imediatamente no Stripe.
     * Nós cancelamos com cancel_at_period_end em maybe_process().
     */
    public function should_process( $process, $user ) {
        return false;
    }

    /**
     * Só age na página de cancelamento, com confirm + nonce válido.
     */
    public function maybe_process(): void {
        // IMPORTANTE: o preheader do core (preheaders/cancel.php, rodado em wp/2)
        // faz $_REQUEST['confirm'] = false quando pmpro_cancel_should_process
        // devolve false (nosso filtro). $_REQUEST então some do nosso alcance —
        // lemos dos superglobals crus ($_POST primeiro, depois $_GET), que o core
        // não altera. Sem isto maybe_process retorna em silêncio e o clique no
        // botão "não faz nada" (a página apenas recarrega).
        $confirm = isset( $_POST['confirm'] ) ? $_POST['confirm'] : ( $_GET['confirm'] ?? '' );
        $nonce   = isset( $_POST['pmpro_cancel-nonce'] ) ? $_POST['pmpro_cancel-nonce'] : ( $_GET['pmpro_cancel-nonce'] ?? '' );
        if ( ! is_user_logged_in() || empty( $confirm ) ) {
            return;
        }
        if ( ! wp_verify_nonce( $nonce, 'pmpro_cancel-nonce' ) ) {
            return;
        }
        if ( ! $this->is_cancel_page() ) {
            return;
        }

        $user_id     = get_current_user_id();
        $user_levels = pmpro_getMembershipLevelsForUser( $user_id );
        if ( empty( $user_levels ) ) {
            return;
        }

        $old_level_ids = $this->get_levels_to_cancel( $user_levels );
        if ( empty( $old_level_ids ) ) {
            return;
        }

        $worked        = true;
        $has_recurring = false;
        $expire_date   = false;

        foreach ( $old_level_ids as $old_level_id ) {
            $level_id = (int) $old_level_id;
            $next_payment_date = $this->get_next_payment_date( $user_id, $level_id );

            if ( ! empty( $next_payment_date ) ) {
                if ( ! $this->cancel_at_period_end_for_level( $user_id, $level_id ) ) {
                    $worked = false;
                    continue;
                }

                $has_recurring = true;
                $expire_date   = $next_payment_date;
                pmpro_set_expiration_date( $user_id, $level_id, $next_payment_date );

                $current_user = wp_get_current_user();
                $myemail      = new PMProEmail();
                $myemail->sendCancelOnNextPaymentDateEmail( $current_user, $level_id );
                $myemail = new PMProEmail();
                $myemail->sendCancelOnNextPaymentDateAdminEmail( $current_user, $level_id );
            } elseif ( pmpro_cancelMembershipLevel( $level_id, $user_id, 'cancelled' ) ) {
                $current_user = wp_get_current_user();
                $myemail      = new PMProEmail();
                $myemail->sendCancelEmail( $current_user, $level_id );
                $myemail = new PMProEmail();
                $myemail->sendCancelAdminEmail( $current_user, $level_id );
            } else {
                $worked = false;
            }
        }

        if ( $worked ) {
            global $pmpro_msg, $pmpro_msgt;
            if ( $has_recurring && ! empty( $expire_date ) ) {
                $pmpro_msg = sprintf(
                    'Sua assinatura foi cancelada e você não será cobrado novamente. Seu acesso ao conteúdo continua até <strong>%s</strong>.',
                    date_i18n( get_option( 'date_format' ), $expire_date )
                );
            } else {
                $pmpro_msg = 'Sua assinatura foi cancelada.';
            }
            $pmpro_msgt = 'pmpro_success';

            do_action( 'pmpro_cancel_processed', wp_get_current_user() );
        } else {
            // $force = true: erro nunca é silenciado por mensagens anteriores (ex: 'Assinatura salva.' do PMPro).
            pmpro_setMessage( 'Não foi possível cancelar sua assinatura agora. Tente novamente ou fale com a gente.', 'pmpro_error', true );
        }
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function is_cancel_page(): bool {
        global $pmpro_page_name;
        if ( ! empty( $pmpro_page_name ) && 'cancel' === $pmpro_page_name ) {
            return true;
        }
        if ( function_exists( 'pmpro_getOption' ) ) {
            $cancel_page_id = (int) pmpro_getOption( 'cancel_page_id' );
            if ( $cancel_page_id && is_page( $cancel_page_id ) ) {
                return true;
            }
        }
        return false;
    }

    private function get_levels_to_cancel( array $user_levels ): array {
        $requested = isset( $_POST['levelstocancel'] ) ? $_POST['levelstocancel'] : ( $_GET['levelstocancel'] ?? '' );
        if ( 'all' === $requested ) {
            return wp_list_pluck( $user_levels, 'ID' );
        }
        if ( ! empty( $requested ) ) {
            $requested = str_replace( [ ' ', '%20' ], '+', sanitize_text_field( $requested ) );
            $requested = preg_replace( '/[^0-9\+]/', '', $requested );
            $ids       = array_map( 'intval', explode( '+', $requested ) );
            return array_values( array_unique( $ids ) );
        }
        return wp_list_pluck( $user_levels, 'ID' );
    }

    /**
     * Furthest next payment date after today for the level, without pending orders.
     * Replica a lógica do core cancel.php, incluindo subs em trial.
     */
    private function get_next_payment_date( int $user_id, int $level_id ) {
        $subscriptions = PMPro_Subscription::get_subscriptions_for_user( $user_id, $level_id, [ 'active', 'trialing' ] );
        $next_payment_date = false;
        foreach ( $subscriptions as $sub ) {
            $sub_next_payment_date = $sub->get_next_payment_date();
            if (
                ! empty( $sub_next_payment_date )
                && $sub_next_payment_date > current_time( 'timestamp' )
                && ( empty( $next_payment_date ) || $sub_next_payment_date > $next_payment_date )
                && empty( $sub->get_orders( [ 'status' => 'pending', 'limit' => 1 ] ) )
            ) {
                $next_payment_date = $sub_next_payment_date;
            }
        }
        return $next_payment_date;
    }

    /**
     * Agenda o cancelamento no Stripe para o fim do período (cancel_at_period_end).
     *
     * @return bool true se todos os subs do nível foram agendados com sucesso.
     */
    private function cancel_at_period_end_for_level( int $user_id, int $level_id ): bool {
        $subscriptions = PMPro_Subscription::get_subscriptions_for_user( $user_id, $level_id, [ 'active', 'trialing' ] );
        // A SDK do Stripe NÃO existe nesta página até $sub->get_gateway_object()
        // construir o gateway (o __construct chama loadStripeLibrary() + setApiKey()).
        // Checar class_exists() aqui faria o método falhar sem nunca carregar a SDK.
        if ( empty( $subscriptions ) ) {
            return false;
        }

        $ok = true;
        foreach ( $subscriptions as $sub ) {
            $txn = $sub->get_subscription_transaction_id();
            if ( empty( $txn ) || 'stripe' !== $sub->get_gateway() ) {
                continue;
            }
            // Constrói o gateway: carrega a SDK e configura a chave da API (connect/live).
            $gateway = $sub->get_gateway_object();
            if ( ! ( $gateway instanceof PMProGateway_Stripe ) ) {
                $ok = false;
                continue;
            }
            try {
                \Stripe\Subscription::update( $txn, [ 'cancel_at_period_end' => true ] );
                // Marca a sub como "agendada para encerrar": ela segue `active` localmente,
                // mas o guard de duplicata deve ignorá-la (não vai mais cobrar).
                if ( function_exists( 'update_pmpro_subscription_meta' ) && class_exists( 'BBL_Prevent_Duplicate_Subscription' ) ) {
                    update_pmpro_subscription_meta( $sub->get_id(), BBL_Prevent_Duplicate_Subscription::META_END, 1 );
                }
            } catch ( \Throwable $e ) {
                error_log( '[bbl-cancel-period-end] Falha ao agendar cancel_at_period_end da sub ' . $txn . ': ' . $e->getMessage() );
                $ok = false;
            }
        }

        return $ok;
    }
}

add_action( 'plugins_loaded', function () {
    if ( ! class_exists( 'PMPro_Subscription' ) || ! class_exists( 'PMProGateway_Stripe' ) ) {
        return;
    }
    BBL_Cancel_At_Period_End::get_instance();
} );
