<?php
/**
 * BBL_Billing_Failure — Registra o motivo do declínio em cobranças recorrentes
 *
 * Quando o Stripe reporta `charge.failed`, o core (gateway-request-handlers.php)
 * cria/reaproveita uma ordem `pending` e dispara `pmpro_subscription_payment_failed`,
 * mas NÃO grava o motivo (decline code) — a causa fica só no Stripe (caso Romario,
 * ped. 64 `error`, notes vazios). Aqui lemos a fatura no Stripe e persistimos o
 * motivo em ordermeta + nota na ordem, para atendimento/reconciliação
 * (decisão Leo/Clarisse — set/2026).
 *
 * NUNCA bloqueia o fluxo do webhook nem o envio do e-mail: qualquer falha vira só log.
 *
 * @package Bebelume_PMPro_Profile
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BBL_Billing_Failure {

    private static $instance = null;

    /** Motivos "frescos" por e-mail, consumidos pelo template de e-mail (T02). */
    private static $decline_by_email = [];

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'pmpro_subscription_payment_failed', [ $this, 'on_subscription_payment_failed' ], 5, 1 );
    }

    // =========================================================================
    // HANDLER
    // =========================================================================

    public function on_subscription_payment_failed( $order ): void {
        if ( empty( $order ) || empty( $order->id ) || 'stripe' !== $order->gateway ) {
            return;
        }

        // Retries do Stripe reutilizam a MESMA ordem `pending`; buscamos uma vez só.
        if ( get_pmpro_membership_order_meta( $order->id, 'bbl_stripe_decline_code', true ) ) {
            return;
        }

        $reason = $this->fetch_decline_reason( $order );
        if ( empty( $reason['code'] ) && empty( $reason['message'] ) ) {
            return;
        }

        update_pmpro_membership_order_meta( $order->id, 'bbl_stripe_decline_code', $reason['code'] );
        update_pmpro_membership_order_meta( $order->id, 'bbl_stripe_decline_message', $reason['message'] );
        update_pmpro_membership_order_meta( $order->id, 'bbl_stripe_decline_at', current_time( 'mysql' ) );

        try {
            $label = ! empty( $reason['message'] ) ? $reason['message'] : $reason['code'];
            $order->add_order_note(
                'Declínio da cobrança (Stripe): ' . $label
                . ( ! empty( $reason['code'] ) && $reason['code'] !== $label ? ' [' . $reason['code'] . ']' : '' )
            );
            $order->saveOrder();
        } catch ( \Throwable $e ) {
            error_log( '[bbl-billing-failure] Falha ao anotar ordem ' . $order->id . ': ' . $e->getMessage() );
        }

        $this->stash_decline_for_email( $order, $reason );
    }

    // =========================================================================
    // LEITURA NO STRIPE (read-only)
    // =========================================================================

    /**
     * Último erro de pagamento da fatura da ordem. Fluxo validado em produção
     * (pedido 64): fatura `in_…` → payments[0].payment.payment_intent →
     * PaymentIntent.last_payment_error → code/decline_code/message.
     *
     * @return array{code:string,message:string}
     */
    private function fetch_decline_reason( $order ): array {
        $this->load_stripe_lib();
        if ( ! class_exists( '\Stripe\Stripe' ) ) {
            return [];
        }

        try {
            $this->set_stripe_key( $order );
        } catch ( \Throwable $e ) {
            return [];
        }

        $pi_id = $this->find_payment_intent( $order );
        if ( empty( $pi_id ) ) {
            return [];
        }

        try {
            $pi   = \Stripe\PaymentIntent::retrieve( $pi_id );
            $err = ! empty( $pi->last_payment_error ) ? $pi->last_payment_error : null;
            if ( empty( $err ) ) {
                return [];
            }
            return [
                'code'    => ! empty( $err->decline_code ) ? $err->decline_code : ( ! empty( $err->code ) ? $err->code : '' ),
                'message' => ! empty( $err->message ) ? $err->message : '',
            ];
        } catch ( \Throwable $e ) {
            error_log( '[bbl-billing-failure] Erro ao ler PaymentIntent ' . $pi_id . ': ' . $e->getMessage() );
        }

        return [];
    }

    private function find_payment_intent( $order ): string {
        $invoice_id = ! empty( $order->payment_transaction_id ) && 0 === strpos( (string) $order->payment_transaction_id, 'in_' )
            ? (string) $order->payment_transaction_id
            : '';

        if ( ! empty( $invoice_id ) ) {
            try {
                $invoice = \Stripe\Invoice::retrieve( [ 'id' => $invoice_id, 'expand' => [ 'payments' ] ] );
                $pi_id   = $this->pi_from_invoice( $invoice );
                if ( ! empty( $pi_id ) ) {
                    return $pi_id;
                }
            } catch ( \Throwable $e ) {
                error_log( '[bbl-billing-failure] Erro ao ler fatura ' . $invoice_id . ': ' . $e->getMessage() );
            }
        }

        // Fallback: última fatura da assinatura (caso a ordem não tenha guardado a fatura).
        $sub_id = ! empty( $order->subscription_transaction_id ) ? (string) $order->subscription_transaction_id : '';
        if ( 0 === strpos( $sub_id, 'sub_' ) ) {
            try {
                $subscription = \Stripe\Subscription::retrieve( [ 'id' => $sub_id, 'expand' => [ 'latest_invoice' ] ] );
                $invoice      = ! empty( $subscription->latest_invoice ) ? $subscription->latest_invoice : null;
                if ( ! empty( $invoice->id ) ) {
                    $invoice = \Stripe\Invoice::retrieve( [ 'id' => $invoice->id, 'expand' => [ 'payments' ] ] );
                    return $this->pi_from_invoice( $invoice );
                }
            } catch ( \Throwable $e ) {
                error_log( '[bbl-billing-failure] Erro ao ler assinatura ' . $sub_id . ': ' . $e->getMessage() );
            }
        }

        return '';
    }

    private function pi_from_invoice( $invoice ): string {
        if ( empty( $invoice->payments->data ) ) {
            return '';
        }
        foreach ( $invoice->payments->data as $payment ) {
            if ( ! empty( $payment->payment->payment_intent ) ) {
                return (string) $payment->payment->payment_intent;
            }
        }
        return '';
    }

    private function load_stripe_lib(): void {
        if ( class_exists( '\Stripe\Stripe' ) || ! defined( 'PMPRO_DIR' ) ) {
            return;
        }
        $init = PMPRO_DIR . '/includes/lib/Stripe/init.php';
        if ( file_exists( $init ) ) {
            require_once $init;
        }
    }

    private function set_stripe_key( $order ): void {
        if ( PMProGateway_stripe::using_api_keys() ) {
            $key = get_option( 'pmpro_stripe_secretkey' );
        } elseif ( 'sandbox' === $order->gateway_environment ) {
            $key = get_option( 'pmpro_sandbox_stripe_connect_secretkey' );
        } else {
            $key = get_option( 'pmpro_live_stripe_connect_secretkey' );
        }
        if ( ! empty( $key ) ) {
            \Stripe\Stripe::setApiKey( $key );
        }
    }

    // =========================================================================
    // STASH PARA E-MAIL (consumido pelo BBL_PMPro_Emails — T02)
    // =========================================================================

    private function stash_decline_for_email( $order, array $reason ): void {
        $user = ! empty( $order->user_id ) ? get_userdata( $order->user_id ) : null;
        if ( empty( $user->user_email ) ) {
            return;
        }
        $email                              = strtolower( $user->user_email );
        self::$decline_by_email[ $email ]   = [
            'code'    => $reason['code'],
            'message' => $reason['message'],
            'level'   => ! empty( $order->membership_id ) ? (int) $order->membership_id : 0,
        ];
    }

    public function consume_decline_for_email( $email ) {
        if ( empty( $email ) ) {
            return [];
        }
        $email = strtolower( trim( (string) $email ) );
        if ( ! isset( self::$decline_by_email[ $email ] ) ) {
            return [];
        }
        $data = self::$decline_by_email[ $email ];
        unset( self::$decline_by_email[ $email ] );
        return $data;
    }

    // =========================================================================
    // TEXTO AMIGÁVEL EM PT-BR (T02) — retorna texto cru; quem renderiza faz esc_html
    // =========================================================================

    public static function friendly_decline( string $code = '', string $raw_message = '' ): string {
        $map = [
            'incorrect_number'        => 'o número do cartão está incorreto',
            'incorrect_cvc'           => 'o código de segurança (CVV) está incorreto',
            'expired_card'            => 'o cartão está vencido',
            'card_declined'           => 'o cartão foi recusado pelo banco emissor',
            'insufficient_funds'      => 'não há saldo/limite disponível no cartão',
            'processing_error'        => 'houve um erro no processamento do pagamento — tente novamente',
            'lost_card'               => 'o cartão foi bloqueado (perda/roubo)',
            'stolen_card'             => 'o cartão foi bloqueado (perda/roubo)',
            'fraud'                   => 'o pagamento foi recusado por suspeita de fraude',
            'transaction_not_allowed' => 'este tipo de cartão/transação não é aceito',
            'currency_not_supported'  => 'a moeda do cartão não é suportada',
            'try_again_later'         => 'o pagamento não pôde ser processado agora — tente novamente mais tarde',
        ];

        if ( ! empty( $code ) && isset( $map[ $code ] ) ) {
            return $map[ $code ];
        }

        if ( ! empty( $raw_message ) ) {
            return $raw_message;
        }

        return 'o pagamento foi recusado pela operadora do cartão';
    }
}

add_action( 'plugins_loaded', function () {
    if ( ! function_exists( 'pmpro_getOption' ) || ! class_exists( 'PMProGateway_stripe' ) ) {
        return;
    }
    BBL_Billing_Failure::get_instance();
} );
