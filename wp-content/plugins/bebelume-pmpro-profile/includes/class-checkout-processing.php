<?php
/**
 * BBL_Checkout_Processing — Bloqueia submissão duplicada no checkout
 *
 * Desabilita o botão "Começar minha assinatura" assim que o formulário é
 * enviado (após a validação nativa do navegador) e cobre a tela inteira com
 * um backdrop de "processando" (logo ArtEduca + spinner + selo de segurança).
 *
 * Por que: o gateway Stripe onsite (`pmpro-stripe.js`) cria o PaymentMethod de
 * forma ASSÍNCRONA antes de reenviar o formulário. Nessa janela (~1–3s) o botão
 * não fica desabilitado — um segundo clique dispara outra criação de pagamento e
 * pode gerar ASSINATURA DUPLICADA. Este módulo elimina a janela de duplo clique.
 *
 * O backdrop é liberado automaticamente quando o PMPro reabilita o botão
 * (caso de erro de cartão/autenticação). Se tudo ocorrer bem, a página navega
 * e o estado é descartado naturalmente.
 *
 * O markup/CSS/JS do overlay vivem em `bbl_processing_overlay()` (processing-overlay.php),
 * compartilhado com o cancelamento para manter a mesma tela de bloqueio.
 *
 * @package Bebelume_PMPro_Profile
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/processing-overlay.php';

class BBL_Checkout_Processing {

    private static $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_footer', [ $this, 'render_overlay' ], 20 );
    }

    /**
     * Injeta o overlay (CSS + HTML + JS) apenas na página de checkout.
     */
    public function render_overlay(): void {
        if ( ! function_exists( 'pmpro_is_checkout' ) || ! pmpro_is_checkout() ) {
            return;
        }

        bbl_processing_overlay( [
            'overlay_id'      => 'bbl-checkout-processing',
            'form_selector'   => '.pmpro_form, #pmpro_form',
            'button_selector' => '#pmpro_btn-submit',
            'title'           => 'Seu pedido está sendo processado',
            'sub'             => 'Não feche nem atualize esta página.<br>Isso pode levar alguns instantes.',
            'secure'          => true,
            'auto_release'    => true,
            'retry_ms'        => 25000,
        ] );
    }
}

add_action( 'plugins_loaded', function () {
    BBL_Checkout_Processing::get_instance();
} );
