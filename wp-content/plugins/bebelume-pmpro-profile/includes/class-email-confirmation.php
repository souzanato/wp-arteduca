<?php
/**
 * BBL_Email_Confirmation — Confirmação de e-mail obrigatória no registro
 *
 * Fluxo:
 *  1. Registro → meta bbl_email_confirmed = 0 + token salvo
 *  2. E-mail customizado com link de confirmação
 *  3. Login bloqueado se não confirmado → tela "verifique seu e-mail" com reenvio
 *  4. Clique no link → confirma → redireciona para completar cadastro
 *
 * @package Bebelume_PMPro_Profile
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BBL_Email_Confirmation {

    const META_CONFIRMED = 'bbl_email_confirmed';
    const META_TOKEN     = 'bbl_confirm_token';
    const ACTION_CONFIRM = 'bbl_confirm_email';
    const ACTION_RESEND  = 'bbl_resend_confirmation';

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
        // Marca conta como não confirmada após registro
        add_action( 'user_register',                      [ $this, 'mark_unconfirmed' ], 5 );

        // E-mail customizado é gerenciado pelo class-emails.php que chama get_confirmation_url_public()

        // Bloqueia login de contas não confirmadas
        add_filter( 'authenticate',                       [ $this, 'block_unconfirmed_login' ], 30, 3 );
        add_action( 'login_init',                         [ $this, 'intercept_unconfirmed_redirect' ], 1 );

        // Handlers das actions
        add_action( 'login_init',                         [ $this, 'handle_actions' ] );

        // Tela "verifique seu e-mail" com reenvio
        add_action( 'login_footer',                       [ $this, 'inject_unconfirmed_ui' ] );
    }

    // =========================================================================
    // MARCA CONTA COMO NÃO CONFIRMADA
    // =========================================================================

    public function mark_unconfirmed( int $user_id ): void {
        update_user_meta( $user_id, self::META_CONFIRMED, 0 );
        $this->generate_token( $user_id );

        // Salva user_id em cookie para exibir botão de reenvio na tela checkemail
        setcookie( 'bbl_last_registered', $user_id, time() + 3600, '/', '', is_ssl(), true );
    }

    private function generate_token( int $user_id ): string {
        $token = bin2hex( random_bytes( 32 ) );
        update_user_meta( $user_id, self::META_TOKEN, $token );
        return $token;
    }

    public function get_confirmation_url_public( int $user_id ): string {
        return $this->get_confirmation_url( $user_id );
    }

    private function get_confirmation_url( int $user_id ): string {
        $token = get_user_meta( $user_id, self::META_TOKEN, true );
        if ( ! $token ) {
            $token = $this->generate_token( $user_id );
        }
        return add_query_arg( [
            'action'  => self::ACTION_CONFIRM,
            'user_id' => $user_id,
            'token'   => $token,
        ], wp_login_url() );
    }

    // =========================================================================
    // E-MAIL CUSTOMIZADO
    // =========================================================================

    public function customize_email( array $email, WP_User $user, string $blogname ): array {
        $confirm_url = $this->get_confirmation_url( $user->ID );

        $email['subject'] = 'Bem-vindo(a) ao Bebelume! Confirme sua conta';
        $email['message'] = "Olá!\n\n";
        $email['message'] .= "Seja bem-vindo(a) ao Bebelume!\n\n";
        $email['message'] .= "Clique no link abaixo para confirmar sua conta:\n\n";
        $email['message'] .= $confirm_url . "\n\n";
        $email['message'] .= "Se você não criou uma conta, pode ignorar este e-mail.\n\n";
        $email['message'] .= "— Equipe Bebelume";
        $email['headers'] = [ 'Content-Type: text/plain; charset=UTF-8' ];

        return $email;
    }

    // =========================================================================
    // BLOQUEIA LOGIN
    // =========================================================================

    public function block_unconfirmed_login( $user, string $username, string $password ) {
        if ( is_wp_error( $user ) || ! ( $user instanceof WP_User ) ) {
            return $user;
        }

        $confirmed = get_user_meta( $user->ID, self::META_CONFIRMED, true );

        // Meta vazia = conta criada antes do plugin = considerada confirmada
        // Só bloqueia se a meta existir explicitamente com valor 0
        if ( $confirmed === '' || $confirmed === false ) {
            return $user;
        }

        if ( (int) $confirmed === 0 ) {
            return new WP_Error(
                'bbl_email_not_confirmed',
                'bbl_unconfirmed::' . $user->ID
            );
        }

        return $user;
    }

    // =========================================================================
    // INTERCEPTA LOGIN DE CONTA NÃO CONFIRMADA
    // =========================================================================

    public function intercept_unconfirmed_redirect(): void {
        if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) return;
        $action = $_GET['action'] ?? '';
        if ( $action !== '' && $action !== 'login' ) return;

        $log      = sanitize_user( wp_unslash( $_POST['log'] ?? '' ) );
        $password = $_POST['pwd'] ?? '';

        if ( empty( $log ) || empty( $password ) ) return;

        $user = get_user_by( 'email', $log );
        if ( ! $user ) $user = get_user_by( 'login', $log );
        if ( ! $user ) return;

        if ( ! wp_check_password( $password, $user->user_pass, $user->ID ) ) return;

        $confirmed = get_user_meta( $user->ID, self::META_CONFIRMED, true );
        if ( $confirmed === '' || $confirmed === false || (int) $confirmed === 1 ) return;

        wp_redirect( add_query_arg( 'bbl_unconfirmed', $user->ID, wp_login_url() ) );
        exit;
    }

    // =========================================================================
    // HANDLERS
    // =========================================================================

    public function handle_actions(): void {
        $action = $_GET['action'] ?? '';

        // Confirma e-mail
        if ( $action === self::ACTION_CONFIRM ) {
            $this->handle_confirm();
        }

        // Reenvia e-mail
        if ( $action === self::ACTION_RESEND ) {
            $this->handle_resend();
        }
    }

    private function handle_confirm(): void {
        $user_id = (int) ( $_GET['user_id'] ?? 0 );
        $token   = sanitize_text_field( $_GET['token'] ?? '' );

        if ( ! $user_id || ! $token ) {
            wp_redirect( add_query_arg( 'bbl_confirm_error', '1', wp_login_url() ) );
            exit;
        }

        $saved_token = get_user_meta( $user_id, self::META_TOKEN, true );

        if ( ! hash_equals( $saved_token, $token ) ) {
            wp_redirect( add_query_arg( 'bbl_confirm_error', '1', wp_login_url() ) );
            exit;
        }

        // Confirma a conta
        update_user_meta( $user_id, self::META_CONFIRMED, 1 );
        delete_user_meta( $user_id, self::META_TOKEN );

        // Loga automaticamente
        wp_clear_auth_cookie();
        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id, false );

        // Redireciona para completar cadastro ou home
        if ( class_exists( 'BBL_Complete_Profile' ) && ! BBL_Complete_Profile::get_instance()->profile_is_complete( $user_id ) ) {
            wp_redirect( BBL_Complete_Profile::get_instance()->complete_profile_url() );
        } else {
            wp_redirect( home_url() );
        }
        exit;
    }

    private function handle_resend(): void {
        $user_id = (int) ( $_GET['user_id'] ?? 0 );
        $nonce   = sanitize_text_field( $_GET['_wpnonce'] ?? '' );

        if ( ! $user_id || ! wp_verify_nonce( $nonce, 'bbl_resend_' . $user_id ) ) {
            wp_redirect( wp_login_url() );
            exit;
        }

        $user = get_user_by( 'id', $user_id );
        if ( ! $user ) {
            wp_redirect( wp_login_url() );
            exit;
        }

        // Gera novo token e envia
        $this->generate_token( $user_id );
        $this->send_confirmation_email( $user );

        wp_redirect( add_query_arg( [
            'checkemail' => 'registered',
            'resent'     => '1',
        ], wp_login_url() ) );
        exit;
    }

    private function send_confirmation_email( WP_User $user ): void {
        // Usa o BBL_Emails para manter o template HTML
        if ( class_exists( 'BBL_Emails' ) ) {
            $email = BBL_Emails::get_instance()->build_confirmation_email( $user, $this->get_confirmation_url( $user->ID ) );
            wp_mail( $user->user_email, $email['subject'], $email['message'], $email['headers'] );
        } else {
            $confirm_url = $this->get_confirmation_url( $user->ID );
            wp_mail(
                $user->user_email,
                'Bem-vindo(a) ao Bebelume! Confirme sua conta',
                "Confirme sua conta: " . $confirm_url,
                [ 'Content-Type: text/html; charset=UTF-8' ]
            );
        }
    }

    // =========================================================================
    // UI — tela de não confirmado com reenvio
    // =========================================================================

    public function inject_unconfirmed_ui(): void {
        // Detecta erro de login por conta não confirmada
        $login_error = $_GET['login'] ?? '';

        // O erro é passado como query var quando o WP redireciona após falha
        // Vamos checar via cookie de sessão temporário
        $unconfirmed_user_id = (int) ( $_COOKIE['bbl_unconfirmed'] ?? 0 );

        // Detecta pelo error code na URL (após nossa injeção no authenticate)
        $error_raw = $_GET['bbl_unconfirmed'] ?? '';
        if ( $error_raw ) {
            $unconfirmed_user_id = (int) $error_raw;
        }

        if ( ! $unconfirmed_user_id ) {
            return;
        }

        $resend_url = add_query_arg( [
            'action'   => self::ACTION_RESEND,
            'user_id'  => $unconfirmed_user_id,
            '_wpnonce' => wp_create_nonce( 'bbl_resend_' . $unconfirmed_user_id ),
        ], wp_login_url() );

        ?>
        <script>
        (function () {
            var login = document.getElementById('login');
            if ( ! login ) return;
            login.innerHTML = '<div class="bbl-checkemail">' +
                '<div class="bbl-checkemail-icon">' +
                    '<svg width="64" height="64" viewBox="0 0 64 64" fill="none">' +
                        '<circle cx="32" cy="32" r="32" fill="#FFF0F3"/>' +
                        '<path d="M16 24a4 4 0 0 1 4-4h24a4 4 0 0 1 4 4v16a4 4 0 0 1-4 4H20a4 4 0 0 1-4-4V24z" fill="#E73665" opacity=".15"/>' +
                        '<path d="M16 24l16 11 16-11" stroke="#E73665" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>' +
                        '<rect x="16" y="24" width="32" height="20" rx="4" stroke="#E73665" stroke-width="2.5"/>' +
                    '</svg>' +
                '</div>' +
                '<h2 class="bbl-checkemail-title">Confirme seu e-mail</h2>' +
                '<p class="bbl-checkemail-msg">Sua conta ainda não foi confirmada.<br>Verifique sua caixa de entrada e clique no link que enviamos.</p>' +
                '<p class="bbl-checkemail-hint">Não recebeu? Verifique a pasta de spam.</p>' +
                '<a href="<?php echo esc_js( $resend_url ); ?>" class="bbl-checkemail-resend">Reenviar e-mail de confirmação</a>' +
            '</div>';
        })();
        </script>
        <?php
    }

    // =========================================================================
    // HELPERS PÚBLICOS
    // =========================================================================

    public function is_confirmed( int $user_id ): bool {
        $val = get_user_meta( $user_id, self::META_CONFIRMED, true );
        return $val === '' || (int) $val === 1;
    }
}

add_action( 'plugins_loaded', function () {
    BBL_Email_Confirmation::get_instance();
} );
