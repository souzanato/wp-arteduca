<?php
/**
 * BBL_Cancel_Notice — Aviso de assinatura cancelada na home do satélite
 *
 * Injeta um banner no topo do conteúdo da página de entrada do satélite
 * ("bebelume-arteduca/inicio") quando o usuário logado tem assinatura local
 * marcada para encerrar no fim do período (meta bbl_cancel_at_period_end) com
 * acesso ainda em vigor. Informa a data-limite de acesso e lembra que, depois
 * dela, é preciso nova assinatura para voltar a acessar os conteúdos.
 *
 * A detecção espelha a do template de conta: sub stripe active/trialing com
 * próxima cobrança no futuro e o marcador gravado por class-cancel-at-period-end.
 *
 * @package Bebelume_PMPro_Profile
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BBL_Cancel_Notice {

    private static $instance = null;

    /** Cache do id da página-alvo (evita repetir get_page_by_path). */
    private static $target_id = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Após wpautop/do_blocks (prioridade 10/9): o markup devolvido aqui não é
        // reprocessado, então <style> e <div> do banner saem intactos.
        add_filter( 'the_content', [ $this, 'maybe_banner' ], 20 );
    }

    private static function target_page_id(): int {
        if ( null === self::$target_id ) {
            self::$target_id = 0;
            $page = get_page_by_path( 'bebelume-arteduca/inicio' );
            if ( $page instanceof WP_Post ) {
                self::$target_id = (int) $page->ID;
            }
        }
        return self::$target_id;
    }

    public function maybe_banner( $content ) {
        if ( ! is_singular() || ! is_user_logged_in() ) {
            return $content;
        }
        if ( (int) get_the_ID() !== self::target_page_id() ) {
            return $content;
        }
        if ( ! function_exists( 'get_pmpro_subscription_meta' ) || ! class_exists( 'PMPro_Subscription' ) ) {
            return $content;
        }

        $expire = $this->earliest_cancelled_expiry();
        if ( empty( $expire ) ) {
            return $content;
        }

        $site_label = esc_html( get_bloginfo( 'name' ) );
        $expire_esc = esc_html( $expire );
        $plans_url  = esc_url( home_url( '/planos/' ) );

        $html = <<<HTML
<style>
.bbl-cancel-banner{display:flex;align-items:flex-start;gap:12px;box-sizing:border-box;width:100%;margin:0 0 22px;padding:14px 16px;border-radius:14px;background:#fff7ed;border:1px solid #fcd34d;border-left:5px solid #f59e0b;color:#7c2d12;font-family:Nunito,sans-serif;font-size:14px;line-height:1.5;}
.bbl-cancel-banner svg{flex:none;margin-top:2px;color:#d97706;}
.bbl-cancel-banner strong{font-weight:800;}
.bbl-cancel-banner a{color:#b45309;font-weight:800;text-decoration:underline;}
.bbl-cancel-banner__title{margin:0 0 2px;font-size:14.5px;font-weight:800;color:#92400e;}
.bbl-cancel-banner__text{margin:0;}
@media(max-width:560px){.bbl-cancel-banner{font-size:13px;padding:12px;}}
</style>
<div class="bbl-cancel-banner" role="status" aria-live="polite">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <div>
        <p class="bbl-cancel-banner__title">Assinatura cancelada</p>
        <p class="bbl-cancel-banner__text">Seu acesso aos conteúdos {$site_label} continua liberado até <strong>{$expire_esc}</strong>. Depois dessa data, para voltar a acessar os conteúdos, basta <a href="{$plans_url}">realizar uma nova assinatura</a>.</p>
    </div>
</div>
HTML;

        return $html . $content;
    }

    /**
     * Data-limite (d/m/Y H:i) da sub cancelada com acesso futuro que encerra
     * primeiro. Vazio = nenhuma assinatura cancelada com acesso em vigor.
     */
    private function earliest_cancelled_expiry(): string {
        $expire = '';
        $min    = 0;
        $subs   = PMPro_Subscription::get_subscriptions_for_user( get_current_user_id(), null, [ 'active', 'trialing' ] );
        foreach ( $subs as $sub ) {
            if ( 'stripe' !== $sub->get_gateway() ) {
                continue;
            }
            $next = $sub->get_next_payment_date( 'timestamp', true );
            if ( empty( $next ) || $next <= current_time( 'timestamp' ) ) {
                continue;
            }
            $cancelled = (bool) get_pmpro_subscription_meta( (int) $sub->get_id(), 'bbl_cancel_at_period_end', true );
            if ( ! $cancelled ) {
                continue;
            }
            if ( ! $min || $next < $min ) {
                $min    = $next;
                $expire = $sub->get_next_payment_date( 'd/m/Y H:i', true );
            }
        }
        return $expire;
    }
}

add_action( 'plugins_loaded', function () {
    BBL_Cancel_Notice::get_instance();
} );
