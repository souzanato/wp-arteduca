<?php
/**
 * BBL_PMPro_Integration — Integração com Paid Memberships Pro
 *
 * - Registra templates customizados
 * - Redireciona não-admins do wp-admin para "Minha Conta"
 * - Esconde admin bar para não-admins
 *
 * @package Bebelume_PMPro_Profile
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class BBL_PMPro_Integration {

    private static $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        // Desativa página de billing — redireciona para conta
        add_action( 'template_redirect', [ $this, 'disable_billing_page' ], 1 );
        if ( ! function_exists( 'pmpro_url' ) ) return;
        $this->hooks();
    }

    private function hooks(): void {
        // Sobrescreve shortcodes do PMPro com nossos templates
        // PMPro re-registra shortcodes no hook 'wp' com prioridade 2 (pmpro_wp())
        // Entramos com prioridade 99 — sempre depois, contexto de post disponível
        add_action( 'wp', [ $this, 'override_shortcodes' ], 99 );

        // Templates customizados (fallback)
        add_filter( 'pmpro_pages_custom_template_path', [ $this, 'register_templates' ], 1, 5 );

        // Redireciona não-admins do wp-admin
        add_action( 'admin_init',   [ $this, 'redirect_non_admins' ] );
        add_action( 'admin_menu',   [ $this, 'hide_admin_bar_for_non_admins' ] );
        add_filter( 'show_admin_bar', [ $this, 'maybe_hide_admin_bar' ] );

        // Página de conteúdo restrito customizada
        add_filter( 'pmpro_no_access_message_body',   [ $this, 'render_restricted_content' ] );
        // Remove link "Já se associou? Entre aqui" que o PMPro renderiza fora do filtro
        add_filter( 'pmpro_no_access_message_action', '__return_empty_string' );
        add_action( 'wp_footer', [ $this, 'remove_pmpro_login_banner' ] );
    }

    // ── Shortcodes ────────────────────────────────────────────────────────────

    public function disable_billing_page(): void {
        if ( ! function_exists( 'pmpro_getOption' ) ) return;
        $billing_id = (int) pmpro_getOption( 'billing_page_id' );
        if ( $billing_id && is_page( $billing_id ) ) {
            $account_url = pmpro_url( 'account' );
            if ( ! $account_url ) $account_url = home_url( '/conta-de-associacao/' );
            wp_redirect( $account_url, 302 );
            exit;
        }
    }

    public function override_shortcodes(): void {
        $pages = [ 'account', 'billing', 'cancel', 'confirmation', 'invoice', 'levels' ];
        foreach ( $pages as $page ) {
            $template = BBL_PMPro_DIR . 'templates/pmpro/' . $page . '.php';
            if ( file_exists( $template ) ) {
                remove_shortcode( 'pmpro_' . $page );
                add_shortcode( 'pmpro_' . $page, function() use ( $template ) {
                    ob_start();
                    include $template;
                    return ob_get_clean();
                } );
            }
        }
    }

    // ── Templates ─────────────────────────────────────────────────────────────

    public function register_templates( array $templates, string $page, $post, string $where, string $template_path ): array {
        if ( $where !== 'local' ) return $templates;
        $custom = BBL_PMPro_DIR . 'templates/pmpro/' . $page . '.php';
        if ( file_exists( $custom ) ) {
            return [ $custom ];
        }
        return $templates;
    }

    // ── Redireciona não-admins ─────────────────────────────────────────────────

    public function redirect_non_admins(): void {
        if ( ! is_user_logged_in() ) return;
        if ( current_user_can( 'manage_options' ) ) return;
        if ( wp_doing_ajax() ) return;

        $account_url = function_exists( 'pmpro_url' ) ? pmpro_url( 'account' ) : '';
        if ( ! $account_url ) return; // sem página configurada, não redireciona

        wp_redirect( $account_url );
        exit;
    }

    public function maybe_hide_admin_bar( bool $show ): bool {
        if ( ! is_user_logged_in() ) return $show;
        if ( current_user_can( 'manage_options' ) ) return $show;
        return false;
    }

    public function hide_admin_bar_for_non_admins(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            remove_action( 'personal_options', 'wp_personal_options' );
        }
    }

    // ── Página de conteúdo restrito ───────────────────────────────────────────

    public function render_restricted_content( string $text ): string {

        // Documentação oficial: pmpro_hasMembershipLevel() retorna true/false
        // Se o usuário tem qualquer nível ativo, tem acesso — não mostrar o card
        if ( is_user_logged_in() && function_exists( 'pmpro_hasMembershipLevel' ) ) {
            if ( pmpro_hasMembershipLevel() ) return $text;
        }
        $levels_url = function_exists( 'pmpro_url' ) ? pmpro_url( 'levels' ) : home_url( '/planos/' );

        if ( is_user_logged_in() ) {
            $secondary_url  = wp_logout_url( get_permalink() );
            $secondary_text = 'Usar outra conta? Trocar de conta';
        } else {
            $secondary_url  = wp_login_url( get_permalink() );
            $secondary_text = 'Já sou assinante? Fazer login';
        }

        ob_start();
        ?>
        <style>
        /* ── Esconde card externo e título "Associação Obrigatória" do PMPro ── */
        .pmpro_card.pmpro_content_message {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            max-width: 100% !important;
        }
        .pmpro_card.pmpro_content_message > .pmpro_card_title {
            display: none !important;
        }
        /* ── Fundo off-white ── */
        body, #page, .site, .site-content, #content, main, #main {
            background-color: #f5f4f1 !important;
        }
        .bbl-restricted-wrap {
            display: flex;
            justify-content: center;
            padding: 60px 16px 80px;
        }
        .bbl-restricted-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 2px 16px rgba(0,0,0,.07);
            max-width: 520px;
            width: 100%;
            padding: 40px 36px;
            text-align: center;
            font-family: var(--bbl-font, 'Nunito', sans-serif);
            margin: 40px auto;
        }
        .blm-no-access-logo {
            margin-bottom: 24px;
        }
        .blm-no-access-logo img {
            height: 56px;
            width: auto;
            max-width: 180px;
            object-fit: contain;
            display: inline-block;
        }
        .bbl-restricted-icon {
            margin-top: 0;
            margin-bottom: 12px;
            display: block;
        }
        .bbl-restricted-title {
            font-size: 20px;
            font-weight: 700;
            color: #1a1a1a;
            margin: 0 0 14px;
            line-height: 1.3;
            font-family: var(--bbl-font, 'Nunito', sans-serif);
        }
        .bbl-restricted-body {
            font-size: 15px;
            color: #555555;
            line-height: 1.6;
            margin: 0 0 24px;
            max-width: 34ch !important;
            margin-left: auto !important;
            margin-right: auto !important;
            text-align: center;
            text-wrap: pretty;
        }
        .bbl-restricted-bullets {
            list-style: none;
            margin: 0 0 28px;
            padding: 0;
            text-align: left;
        }
        .bbl-restricted-bullets li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 14px;
            color: #666666;
            margin-bottom: 10px;
            font-family: var(--bbl-font, 'Nunito', sans-serif);
        }
        .bbl-restricted-bullets li svg {
            flex-shrink: 0;
            margin-top: 2px;
        }
        .bbl-restricted-btn {
            display: block;
            width: 100%;
            background: #eb2a61;
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
            text-decoration: none;
            font-family: var(--bbl-font, 'Nunito', sans-serif);
            font-size: 15px;
            font-weight: 600;
            border-radius: 50px;
            height: 48px;
            line-height: 48px;
            text-align: center;
            margin-top: 24px;
            transition: background .15s;
            box-sizing: border-box;
            opacity: 1 !important;
            visibility: visible !important;
        }
        .bbl-restricted-card a.bbl-restricted-btn,
        .bbl-restricted-card a.bbl-restricted-btn:link,
        .bbl-restricted-card a.bbl-restricted-btn:visited {
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
            opacity: 1 !important;
        }
        .bbl-restricted-btn *,
        .bbl-restricted-btn span,
        .bbl-restricted-btn::after,
        .bbl-restricted-btn::before {
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
            opacity: 1 !important;
            visibility: visible !important;
            font-size: 15px !important;
            font-weight: 600 !important;
        }
        .bbl-restricted-btn:hover {
            background: #c41e50;
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
        }
        .bbl-restricted-login {
            display: block;
            margin-top: 12px;
            font-size: 13px;
            color: #888888;
            text-decoration: none;
            font-family: var(--bbl-font, 'Nunito', sans-serif);
        }
        .bbl-restricted-login:hover {
            color: #eb2a61;
        }
        @media (max-width: 580px) {
            .bbl-restricted-card { padding: 28px 20px; }
            .bbl-restricted-title { font-size: 18px; }
        }
        </style>

        <div class="bbl-restricted-wrap">
            <div class="bbl-restricted-card">

                <!-- Logo centralizada -->
                <div style="text-align:center;margin-bottom:24px;">
                    <img src="https://bebelume.com.br/wp-content/uploads/2026/05/WhatsApp-Image-2026-05-20-at-14.24.50.jpeg" alt="Bebelume" loading="lazy"
                         style="height:56px;width:auto;max-width:180px;object-fit:contain;display:inline-block;">
                </div>

                <!-- Título -->
                <h2 class="bbl-restricted-title">
                    Este conteúdo é exclusivo para assinantes
                </h2>

                <!-- Corpo — parágrafo único centralizado -->
                <p style="font-size:15px;color:#555555;line-height:1.6;text-align:center;margin:0 auto 24px;max-width:34ch;">
                    Você estava prestes a acessar algo especial. Faça parte do Bebelume e explore todo o nosso conteúdo sobre maternidade, infância e desenvolvimento infantil.
                </p>

                <!-- Bullets com checks alinhados -->
                <ul style="list-style:none;padding:0;margin:0 auto 24px;display:inline-block;text-align:left;">
                    <li style="display:flex;align-items:center;gap:8px;font-size:14px;color:#444;padding:5px 0;">
                        <span style="color:#eb2a61;font-weight:700;flex-shrink:0;line-height:1;">✓</span>
                        <span>Acesso ilimitado a todos os conteúdos exclusivos</span>
                    </li>
                    <li style="display:flex;align-items:center;gap:8px;font-size:14px;color:#444;padding:5px 0;">
                        <span style="color:#eb2a61;font-weight:700;flex-shrink:0;line-height:1;">✓</span>
                        <span>Novos episódios e artigos toda semana</span>
                    </li>
                    <li style="display:flex;align-items:center;gap:8px;font-size:14px;color:#444;padding:5px 0;">
                        <span style="color:#eb2a61;font-weight:700;flex-shrink:0;line-height:1;">✓</span>
                        <span>Primeiro mês completamente gratuito</span>
                    </li>
                </ul>

                <!-- Botão 100% inline — sem dependência de CSS externo -->
                <a href="<?php echo esc_url( $levels_url ); ?>" style="display:block;width:100%;box-sizing:border-box;padding:14px 0;background:#eb2a61;color:#ffffff;-webkit-text-fill-color:#ffffff;font-family:'Nunito',sans-serif;font-size:16px;font-weight:600;text-align:center;text-decoration:none;border-radius:50px;margin-bottom:12px;">Quero me associar &rsaquo;</a>

                <!-- CTA secundário -->
                <a href="<?php echo esc_url( $secondary_url ); ?>" class="bbl-restricted-login">
                    <?php echo esc_html( $secondary_text ); ?>
                </a>

            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Remove via JS o banner "Já se associou? Entre aqui" que o PMPro
     * renderiza fora do filtro pmpro_no_access_message_body.
     * Cobre: .pmpro_login_link, .pmpro-member-login, .pmpro_message_action
     * e qualquer elemento que contenha esse texto exato.
     */
    public function remove_pmpro_login_banner(): void {
        if ( ! function_exists( 'pmpro_is_content_restricted' ) ) return;
        ?>
        <script>
        (function () {
            var selectors = [
                '.pmpro_login_link',
                '.pmpro-member-login',
                '.pmpro_message_action',
                '.pmpro_no_access_message_action',
            ];
            selectors.forEach(function (sel) {
                document.querySelectorAll(sel).forEach(function (el) {
                    el.style.display = 'none';
                });
            });
            // Fallback: remove qualquer parágrafo/div com esse texto
            document.querySelectorAll('.pmpro p, .pmpro div').forEach(function (el) {
                if (el.textContent.includes('Já se associou') || el.textContent.includes('Entre aqui')) {
                    el.style.display = 'none';
                }
            });
        })();
        </script>
        <?php
    }
}

add_action( 'plugins_loaded', function () {
    BBL_PMPro_Integration::get_instance();
} );
