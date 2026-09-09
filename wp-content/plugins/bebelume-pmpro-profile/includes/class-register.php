<?php
/**
 * BBL_Register — Registro simplificado: apenas e-mail, senha e confirmação.
 *
 * Nome, sobrenome e CPF são coletados na tela de completar cadastro (BBL_Complete_Profile),
 * que é acionada automaticamente após o primeiro login.
 *
 * @package Bebelume_PMPro_Profile
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BBL_Register {

    const RECAPTCHA_SITE_KEY   = '6LeVpeYsAAAAAML9QhQVocykVUI-uW8vXnJ7XEpU';
    const RECAPTCHA_SECRET_KEY = '6LeVpeYsAAAAMwyvFzPe9BM63tOTmFKvRpbYHCF';
    const RECAPTCHA_THRESHOLD  = 0.5;
    const RECAPTCHA_ACTION     = 'bbl_register';

    private static $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->hooks();
    }

    private function hooks(): void {
        add_action( 'login_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'register_form',         [ $this, 'render_fields' ] );
        add_filter( 'registration_errors',   [ $this, 'validate_fields' ], 10, 3 );
        add_action( 'user_register',         [ $this, 'save_fields' ], 10, 1 );
        add_action( 'login_init',             [ $this, 'inject_username_early' ] );
    }

    // ── Assets ───────────────────────────────────────────────────────────────

    public function enqueue_assets(): void {
        // reCAPTCHA v3 desativado — usando honeypot como proteção
    }

    private function inline_js(): string {
        $site_key = self::RECAPTCHA_SITE_KEY;
        $action   = self::RECAPTCHA_ACTION;
        return <<<JS
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('registerform');
            if (!form) return;

            // Intercepta o submit UMA única vez, gera token fresco e envia
            form.addEventListener('submit', function (e) {
                // Se já tem token válido esperando (ex: tentativa anterior), limpa
                var tokenEl = document.getElementById('bbl_recaptcha_token');

                // Só bloqueia se o token estiver vazio
                if (tokenEl && tokenEl.value !== '') return;

                e.preventDefault();
                e.stopImmediatePropagation(); // evita outros listeners

                grecaptcha.ready(function () {
                    grecaptcha.execute('{$site_key}', {action: '{$action}'}).then(function (token) {
                        tokenEl.value = token;
                        // Submete diretamente sem disparar o event listener novamente
                        HTMLFormElement.prototype.submit.call(form);
                    });
                });
            }, true); // capture phase — roda antes de outros listeners
        });
JS;
    }

    // ── Formulário ────────────────────────────────────────────────────────────

    public function render_fields(): void {
        ?>
        <!-- Senha -->
        <p>
            <label for="bbl_password">Senha <span aria-hidden="true">*</span></label>
            <input type="password"
                   name="bbl_password"
                   id="bbl_password"
                   autocomplete="new-password"
                   required />
            <span class="bbl-password-hint">Mínimo 8 caracteres.</span>
        </p>

        <!-- Confirmação de senha -->
        <p>
            <label for="bbl_password_confirm">Confirmar senha <span aria-hidden="true">*</span></label>
            <input type="password"
                   name="bbl_password_confirm"
                   id="bbl_password_confirm"
                   autocomplete="new-password"
                   required />
        </p>

        <!-- Honeypot anti-bot: campo invisível que robôs preenchem -->
        <p style="display:none !important" aria-hidden="true">
            <label for="bbl_website">Website</label>
            <input type="text" name="bbl_website" id="bbl_website" tabindex="-1" autocomplete="off" value="" />
        </p>
        <?php
    }

    // ── Validação ─────────────────────────────────────────────────────────────

    public function validate_fields( WP_Error $errors, string $sanitized_user_login, string $user_email ): WP_Error {

        $password         = $_POST['bbl_password']         ?? '';
        $password_confirm = $_POST['bbl_password_confirm'] ?? '';

        if ( empty( $password ) ) {
            $errors->add( 'bbl_password_error', '<strong>Erro:</strong> Por favor, informe uma senha.' );
        } elseif ( strlen( $password ) < 8 ) {
            $errors->add( 'bbl_password_error', '<strong>Erro:</strong> A senha deve ter pelo menos 8 caracteres.' );
        } elseif ( $password !== $password_confirm ) {
            $errors->add( 'bbl_password_confirm_error', '<strong>Erro:</strong> As senhas não coincidem.' );
        }

        // Honeypot: se o campo oculto foi preenchido, é bot
        if ( ! empty( $_POST['bbl_website'] ) ) {
            $errors->add( 'bbl_honeypot', '<strong>Erro:</strong> Verificação falhou.' );
        }

        return $errors;
    }

    // ── Salvamento ────────────────────────────────────────────────────────────

    public function save_fields( int $user_id ): void {
        $password = $_POST['bbl_password'] ?? '';
        if ( $password ) {
            wp_set_password( $password, $user_id );
        }
    }

    // ── Username automático ───────────────────────────────────────────────────

    /**
     * Injeta username no $_POST antes de qualquer validação do WordPress.
     * Roda em login_init quando a action é register e o método é POST.
     */
    public function inject_username_early(): void {
        if ( ( $_GET['action'] ?? '' ) !== 'register' ) return;
        if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) return;
        if ( ! empty( $_POST['user_login'] ) ) return;

        $email = isset( $_POST['user_email'] ) ? sanitize_email( $_POST['user_email'] ) : '';
        $base  = $email ? strtolower( strstr( $email, '@', true ) ) : 'user';
        $base  = preg_replace( '/[^a-z0-9]/', '', $base ) ?: 'user';

        $candidate = $base;
        $i = 2;
        while ( username_exists( $candidate ) ) {
            $candidate = $base . $i++;
        }

        $_POST['user_login'] = $_REQUEST['user_login'] = $candidate;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function is_register_page(): bool {
        return isset( $GLOBALS['pagenow'] ) && $GLOBALS['pagenow'] === 'wp-login.php'
            && isset( $_GET['action'] ) && $_GET['action'] === 'register';
    }

    private function verify_recaptcha( string $token ): true|WP_Error {
        $response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', [
            'body'    => [
                'secret'   => self::RECAPTCHA_SECRET_KEY,
                'response' => $token,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ],
            'timeout' => 10,
        ] );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'recaptcha_request_failed', 'Falha na requisição: ' . $response->get_error_message() );
        }

        $raw  = wp_remote_retrieve_body( $response );
        $body = json_decode( $raw, true );

        if ( empty( $body['success'] ) ) {
            $codes = implode( ', ', $body['error-codes'] ?? [] );
            return new WP_Error( 'recaptcha_failed', 'Falhou. Códigos: [' . $codes . '] Token: ' . substr($token, 0, 20) . '...' );
        }

        if ( isset( $body['score'] ) && $body['score'] < self::RECAPTCHA_THRESHOLD ) {
            return new WP_Error( 'recaptcha_score_low', 'Score baixo: ' . $body['score'] );
        }

        if ( isset( $body['action'] ) && $body['action'] !== self::RECAPTCHA_ACTION ) {
            return new WP_Error( 'recaptcha_action_mismatch', 'Action errada: ' . $body['action'] . ' esperava: ' . self::RECAPTCHA_ACTION );
        }

        return true;
    }
}

add_action( 'plugins_loaded', function () {
    BBL_Register::get_instance();
} );
