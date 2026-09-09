<?php
/**
 * BBL_Google_Auth — Login com Google via OAuth 2.0
 *
 * Fluxo:
 *  1. Usuário clica em "Continuar com Google"
 *  2. Redireciona para Google com state (nonce) e redirect_uri
 *  3. Google devolve para /wp-login.php?action=google_callback&code=...&state=...
 *  4. Troca o code por um access_token
 *  5. Busca dados do usuário (email, nome, foto)
 *  6. Se usuário existe → loga. Se não → cria e loga.
 *  7. Redireciona para home_url()
 *
 * @package Bebelume_PMPro_Profile
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BBL_Google_Auth {

    const CLIENT_ID     = '33347749194-v64j3lmtcld8kkre264po6k70avue1c4.apps.googleusercontent.com';
    // CALLBACK_URL agora é dinâmico — usa o domínio do site onde o plugin está instalado.
    // Adicione todos os domínios Bebelume como "Authorized redirect URIs" no Google Console.
    private static function callback_url(): string {
        return site_url( '/wp-login.php?action=google_callback' );
    }

    /**
     * Client secret do OAuth Google — NÃO vai para o repositório.
     * Definido via constante BBL_GOOGLE_CLIENT_SECRET em wp-config.php.
     * Se ausente, o login com Google falha de forma segura (ver exchange_code).
     */
    private static function client_secret(): string {
        return defined( 'BBL_GOOGLE_CLIENT_SECRET' ) ? (string) BBL_GOOGLE_CLIENT_SECRET : '';
    }

    const AUTH_URL      = 'https://accounts.google.com/o/oauth2/v2/auth';
    const TOKEN_URL     = 'https://oauth2.googleapis.com/token';
    const USERINFO_URL  = 'https://www.googleapis.com/oauth2/v3/userinfo';

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
        // Intercepta action=google_login (clique no botão) e action=google_callback (retorno do Google)
        add_action( 'login_init', [ $this, 'handle_request' ] );

        // Atualiza a URL do botão Google após o HTML ser gerado
        add_action( 'login_footer', [ $this, 'inject_google_url' ] );
    }

    // =========================================================================
    // ROTEAMENTO
    // =========================================================================

    public function handle_request(): void {
        $action = $_GET['action'] ?? '';

        if ( $action === 'google_login' ) {
            $this->redirect_to_google();
        }

        if ( $action === 'google_callback' ) {
            $this->handle_callback();
        }
    }

    // =========================================================================
    // PASSO 1 — Redireciona para o Google
    // =========================================================================

    private function redirect_to_google(): void {
        // Gera e salva state (nonce CSRF)
        $state = wp_generate_password( 32, false );
        set_transient( 'bbl_google_state_' . $state, 1, 10 * MINUTE_IN_SECONDS );

        $params = http_build_query( [
            'client_id'             => self::CLIENT_ID,
            'redirect_uri'          => self::callback_url(),
            'response_type'         => 'code',
            'scope'                 => 'openid email profile',
            'state'                 => $state,
            'access_type'           => 'online',
            'prompt'                => 'select_account',
        ] );

        wp_redirect( self::AUTH_URL . '?' . $params );
        exit;
    }

    // =========================================================================
    // PASSO 2 — Processa o callback do Google
    // =========================================================================

    private function handle_callback(): void {
        // Valida state (proteção CSRF)
        $state = sanitize_text_field( $_GET['state'] ?? '' );
        if ( empty( $state ) || ! get_transient( 'bbl_google_state_' . $state ) ) {
            $this->abort( 'Verificação de segurança falhou. Tente novamente.' );
        }
        delete_transient( 'bbl_google_state_' . $state );

        // Verifica code
        $code = sanitize_text_field( $_GET['code'] ?? '' );
        if ( empty( $code ) ) {
            $this->abort( 'Código de autorização ausente.' );
        }

        // Troca code por token
        $token = $this->exchange_code( $code );
        if ( is_wp_error( $token ) ) {
            $this->abort( $token->get_error_message() );
        }

        // Busca dados do usuário
        $google_user = $this->get_user_info( $token );
        if ( is_wp_error( $google_user ) ) {
            $this->abort( $google_user->get_error_message() );
        }

        // Loga ou cria o usuário
        $this->login_or_create( $google_user );
    }

    // =========================================================================
    // TROCA DE CODE POR TOKEN
    // =========================================================================

    private function exchange_code( string $code ): string|WP_Error {
        if ( '' === self::client_secret() ) {
            return new WP_Error( 'google_not_configured', 'Login com Google temporariamente indisponível. Tente novamente mais tarde.' );
        }
        $response = wp_remote_post( self::TOKEN_URL, [
            'body' => [
                'code'          => $code,
                'client_id'     => self::CLIENT_ID,
                'client_secret' => self::client_secret(),
                'redirect_uri'  => self::callback_url(),
                'grant_type'    => 'authorization_code',
            ],
            'timeout' => 15,
        ] );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'token_request_failed', 'Não foi possível conectar ao Google. Tente novamente.' );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body['access_token'] ) ) {
            $error = $body['error_description'] ?? $body['error'] ?? 'Erro desconhecido';
            return new WP_Error( 'token_error', 'Erro ao obter token do Google: ' . $error );
        }

        return $body['access_token'];
    }

    // =========================================================================
    // BUSCA DADOS DO USUÁRIO
    // =========================================================================

    private function get_user_info( string $access_token ): array|WP_Error {
        $response = wp_remote_get( self::USERINFO_URL, [
            'headers' => [ 'Authorization' => 'Bearer ' . $access_token ],
            'timeout' => 15,
        ] );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'userinfo_request_failed', 'Não foi possível obter dados do Google.' );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body['email'] ) ) {
            return new WP_Error( 'userinfo_missing_email', 'O Google não retornou um e-mail. Verifique as permissões.' );
        }

        if ( empty( $body['email_verified'] ) || ! $body['email_verified'] ) {
            return new WP_Error( 'email_not_verified', 'O e-mail da conta Google não está verificado.' );
        }

        return [
            'email'      => strtolower( sanitize_email( $body['email'] ) ),
            'first_name' => sanitize_text_field( $body['given_name']  ?? '' ),
            'last_name'  => sanitize_text_field( $body['family_name'] ?? '' ),
            'google_id'  => sanitize_text_field( $body['sub']         ?? '' ),
            'avatar'     => esc_url_raw( $body['picture']             ?? '' ),
        ];
    }

    // =========================================================================
    // LOGA OU CRIA O USUÁRIO
    // =========================================================================

    private function login_or_create( array $google_user ): void {
        $email = $google_user['email'];

        // Verifica se já existe usuário com esse email
        $user = get_user_by( 'email', $email );

        if ( ! $user ) {
            // Cria novo usuário
            $user_id = $this->create_user( $google_user );
            if ( is_wp_error( $user_id ) ) {
                $this->abort( $user_id->get_error_message() );
            }
            $user = get_user_by( 'id', $user_id );
        } else {
            // Atualiza google_id se ainda não tiver
            if ( ! get_user_meta( $user->ID, 'bbl_google_id', true ) ) {
                update_user_meta( $user->ID, 'bbl_google_id', $google_user['google_id'] );
            }

            // Garante que conta via Google sempre está confirmada
            update_user_meta( $user->ID, 'bbl_email_confirmed', 1 );

            // Atualiza nome/sobrenome do Google se estiverem vazios no WP
            if ( ! get_user_meta( $user->ID, 'first_name', true ) && ! empty( $google_user['first_name'] ) ) {
                update_user_meta( $user->ID, 'first_name', $google_user['first_name'] );
            }
            if ( ! get_user_meta( $user->ID, 'last_name', true ) && ! empty( $google_user['last_name'] ) ) {
                update_user_meta( $user->ID, 'last_name', $google_user['last_name'] );
            }
        }

        // Loga o usuário
        wp_clear_auth_cookie();
        wp_set_current_user( $user->ID );
        wp_set_auth_cookie( $user->ID, true );

        do_action( 'wp_login', $user->user_login, $user );

        // Redireciona — verifica se perfil está completo
        if ( $user->has_cap( 'manage_options' ) ) {
            $redirect = admin_url();
        } elseif ( class_exists( 'BBL_Complete_Profile' ) && ! BBL_Complete_Profile::get_instance()->profile_is_complete( $user->ID ) ) {
            $redirect = BBL_Complete_Profile::get_instance()->complete_profile_url();
        } else {
            $redirect = home_url();
        }
        wp_redirect( $redirect );
        exit;
    }

    // =========================================================================
    // CRIAÇÃO DE USUÁRIO
    // =========================================================================

    private function create_user( array $google_user ): int|WP_Error {
        $email      = $google_user['email'];
        $first_name = $google_user['first_name'];
        $last_name  = $google_user['last_name'];

        // Gera username baseado no email
        $username = $this->generate_username( $email );

        $user_id = wp_insert_user( [
            'user_login'   => $username,
            'user_email'   => $email,
            'user_pass'    => wp_generate_password( 32 ),
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'display_name' => trim( $first_name . ' ' . $last_name ) ?: $username,
            'role'         => 'subscriber',
        ] );

        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }

        // Salva metadados
        update_user_meta( $user_id, 'bbl_google_id',      $google_user['google_id'] );
        update_user_meta( $user_id, 'bbl_google_avatar',  $google_user['avatar'] );
        update_user_meta( $user_id, 'bbl_auth_provider',  'google' );

        // E-mail verificado pelo Google — marca conta como confirmada
        update_user_meta( $user_id, 'bbl_email_confirmed', 1 );

        return $user_id;
    }

    // =========================================================================
    // GERA USERNAME ÚNICO BASEADO NO EMAIL
    // =========================================================================

    private function generate_username( string $email ): string {
        // Pega a parte antes do @
        $base = strtolower( strstr( $email, '@', true ) );
        $base = preg_replace( '/[^a-z0-9]/', '', $base );
        $base = $base ?: 'user';

        // Se não existe, usa direto
        if ( ! username_exists( $base ) ) {
            return $base;
        }

        // Acrescenta números até achar um livre
        $i = 2;
        while ( username_exists( $base . $i ) ) {
            $i++;
        }

        return $base . $i;
    }

    // =========================================================================
    // INJETA URL CORRETA NO BOTÃO GOOGLE
    // =========================================================================

    public function inject_google_url(): void {
        $google_login_url = wp_login_url() . '?action=google_login';
        ?>
        <script>
        (function () {
            var btn = document.getElementById('bbl-google-login');
            if ( btn ) {
                btn.href = '<?php echo esc_js( $google_login_url ); ?>';
            }
        })();
        </script>
        <?php
    }

    // =========================================================================
    // ABORT — exibe erro e interrompe
    // =========================================================================

    private function abort( string $message ): void {
        $login_url = add_query_arg(
            'bbl_error',
            urlencode( $message ),
            wp_login_url()
        );
        wp_redirect( $login_url );
        exit;
    }
}

add_action( 'plugins_loaded', function () {
    BBL_Google_Auth::get_instance();
} );
