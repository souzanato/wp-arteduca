<?php
/**
 * BBL_Complete_Profile — Tela de completar cadastro pós-autenticação
 *
 * Dispara quando qualquer usuário logado não tem CPF cadastrado.
 * Exige: Nome, Sobrenome, CPF.
 * Pré-preenche Nome e Sobrenome quando disponíveis (ex: vindo do Google).
 *
 * @package Bebelume_PMPro_Profile
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BBL_Complete_Profile {

    const ACTION = 'bbl_complete_profile';

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
        // Verifica CPF após qualquer login e redireciona se necessário
        add_filter( 'login_redirect',  [ $this, 'maybe_redirect' ], 20, 3 );

        // Intercepta a action bbl_complete_profile no wp-login.php
        add_action( 'login_init',      [ $this, 'handle_action' ] );
        add_action( 'wp_ajax_bbl_logout_all_devices', [ $this, 'handle_logout_all_devices' ] );

        // Injeta o redesign na tela de completar cadastro
        add_action( 'login_footer',    [ $this, 'inject_complete_profile_js' ] );
    }

    // =========================================================================
    // REDIRECT PÓS-LOGIN
    // =========================================================================

    /**
     * Após qualquer login, verifica se CPF existe.
     * Se não existir, redireciona para a tela de completar cadastro.
     */
    public function maybe_redirect( string $redirect_to, string $requested, $user ): string {
        if ( is_wp_error( $user ) || ! ( $user instanceof WP_User ) ) {
            return $redirect_to;
        }

        if ( $this->profile_is_complete( $user->ID ) ) {
            return $redirect_to;
        }

        return $this->complete_profile_url();
    }

    /**
     * Checa se o perfil está completo (CPF preenchido).
     */
    public function profile_is_complete( int $user_id ): bool {
        $cpf = get_user_meta( $user_id, 'cpf', true );
        return ! empty( $cpf );
    }

    /**
     * URL da tela de completar cadastro.
     */
    public function complete_profile_url(): string {
        return wp_login_url() . '?action=' . self::ACTION;
    }

    // =========================================================================
    // HANDLER DA ACTION
    // =========================================================================

    public function handle_action(): void {
        $action = $_GET['action'] ?? '';
        if ( $action !== self::ACTION ) {
            return;
        }

        // Precisa estar logado
        if ( ! is_user_logged_in() ) {
            wp_redirect( wp_login_url() );
            exit;
        }

        $user_id = get_current_user_id();

        // Se já completou, vai para home
        if ( $this->profile_is_complete( $user_id ) ) {
            wp_redirect( home_url() );
            exit;
        }

        // Processa o formulário submetido
        if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
            $this->process_form( $user_id );
        }

        // Renderiza a tela
        $this->render_page( $user_id );
        exit;
    }

    // =========================================================================
    // PROCESSAMENTO DO FORMULÁRIO
    // =========================================================================

    private function process_form( int $user_id ): void {
        // Verifica nonce
        if ( ! isset( $_POST['bbl_complete_nonce'] ) ||
             ! wp_verify_nonce( $_POST['bbl_complete_nonce'], 'bbl_complete_profile_' . $user_id ) ) {
            $this->render_page( $user_id, [ 'Erro de segurança. Tente novamente.' ] );
            exit;
        }

        $first_name = sanitize_text_field( trim( $_POST['bbl_first_name'] ?? '' ) );
        $last_name  = sanitize_text_field( trim( $_POST['bbl_last_name']  ?? '' ) );
        $cpf_raw    = preg_replace( '/\D/', '', $_POST['bbl_cpf'] ?? '' );

        $errors = [];

        if ( empty( $first_name ) ) {
            $errors[] = 'Por favor, informe seu nome.';
        }

        if ( empty( $last_name ) ) {
            $errors[] = 'Por favor, informe seu sobrenome.';
        }

        if ( empty( $cpf_raw ) ) {
            $errors[] = 'Por favor, informe seu CPF.';
        } elseif ( ! $this->validate_cpf( $cpf_raw ) ) {
            $errors[] = 'CPF inválido.';
        } elseif ( $this->cpf_already_registered( $cpf_raw, $user_id ) ) {
            $errors[] = 'Este CPF já está cadastrado em outra conta.';
        }

        if ( ! empty( $errors ) ) {
            $this->render_page( $user_id, $errors );
            exit;
        }

        // Salva os dados
        update_user_meta( $user_id, 'first_name', $first_name );
        update_user_meta( $user_id, 'last_name',  $last_name );

        $cpf_formatted = preg_replace( '/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf_raw );
        update_user_meta( $user_id, 'cpf',           $cpf_raw );
        update_user_meta( $user_id, 'cpf_formatted', $cpf_formatted );

        wp_update_user( [
            'ID'           => $user_id,
            'display_name' => trim( $first_name . ' ' . $last_name ),
        ] );

        wp_redirect( home_url() );
        exit;
    }

    // =========================================================================
    // RENDERIZAÇÃO DA TELA
    // =========================================================================

    private function render_page( int $user_id, array $errors = [] ): void {
        $user       = get_user_by( 'id', $user_id );
        $first_name = get_user_meta( $user_id, 'first_name', true ) ?: '';
        $last_name  = get_user_meta( $user_id, 'last_name',  true ) ?: '';
        $logout_url = wp_logout_url( wp_login_url() );
        $nonce      = wp_create_nonce( 'bbl_complete_profile_' . $user_id );
        $action_url = $this->complete_profile_url();

        // Usa o header padrão do wp-login.php para carregar os assets corretamente
        login_header( 'Complete seu cadastro' );
        ?>

        <form name="bbl_complete_form" id="bbl_complete_form"
              action="<?php echo esc_url( $action_url ); ?>"
              method="post" novalidate>

            <?php // Nossos elementos (serão reordenados pelo JS do painel esquerdo) ?>
            <div class="bbl-form-header">
                <h2 class="bbl-form-title">Complete seu cadastro</h2>
                <p class="bbl-form-sub">Precisamos de mais algumas informações para continuar</p>
            </div>

            <?php if ( ! empty( $errors ) ) : ?>
                <div id="login_error">
                    <?php foreach ( $errors as $error ) : ?>
                        <span><?php echo esc_html( $error ); ?></span><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <p>
                <label for="bbl_first_name">Nome <span aria-hidden="true">*</span></label>
                <input type="text"
                       name="bbl_first_name"
                       id="bbl_first_name"
                       value="<?php echo esc_attr( $first_name ); ?>"
                       autocomplete="given-name"
                       required />
            </p>

            <p>
                <label for="bbl_last_name">Sobrenome <span aria-hidden="true">*</span></label>
                <input type="text"
                       name="bbl_last_name"
                       id="bbl_last_name"
                       value="<?php echo esc_attr( $last_name ); ?>"
                       autocomplete="family-name"
                       required />
            </p>

            <p>
                <label for="bbl_cpf">CPF <span aria-hidden="true">*</span></label>
                <input type="text"
                       name="bbl_cpf"
                       id="bbl_cpf"
                       placeholder="000.000.000-00"
                       maxlength="14"
                       autocomplete="off"
                       inputmode="numeric"
                       required />
            </p>

            <input type="hidden" name="bbl_complete_nonce" value="<?php echo esc_attr( $nonce ); ?>">

            <p class="submit">
                <input type="submit"
                       name="bbl_complete_submit"
                       id="bbl_complete_submit"
                       class="button button-primary button-large"
                       value="Salvar e continuar" />
            </p>

            <p class="bbl-logout-link">
                <a href="<?php echo esc_url( $logout_url ); ?>">Sair da conta</a>
                &nbsp;·&nbsp;
                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=bbl_logout_all_devices' ), 'bbl_logout_all' ) ); ?>"
                   onclick="return confirm('Isso vai encerrar sua sessão em todos os dispositivos. Continuar?')">
                   Sair de todos os dispositivos
                </a>
            </p>

        </form>

        <?php
        login_footer();
    }

    // =========================================================================
    // JS — adapta o redesign para a tela de completar cadastro
    // =========================================================================

    public function inject_complete_profile_js(): void {
        $action = $_GET['action'] ?? '';
        if ( $action !== self::ACTION ) {
            return;
        }
        ?>
        <script>
        (function () {
            var form = document.getElementById('bbl_complete_form');
            if (!form) return;

            /* Adiciona validação de CPF client-side */
            var cpfInput = document.getElementById('bbl_cpf');
            if (cpfInput) {
                cpfInput.addEventListener('input', function () {
                    var v = this.value.replace(/\D/g, '').substring(0, 11);
                    v = v.replace(/(\d{3})(\d)/, '$1.$2');
                    v = v.replace(/(\d{3})(\d)/, '$1.$2');
                    v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                    this.value = v;
                });
            }
        })();
        </script>
        <?php
    }

    // =========================================================================
    // LOGOUT DE TODOS OS DISPOSITIVOS
    // =========================================================================

    public function handle_logout_all_devices(): void {
        if ( ! is_user_logged_in() ) wp_die( 'Não autorizado.' );
        if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'bbl_logout_all' ) ) wp_die( 'Nonce inválido.' );

        $user_id = get_current_user_id();

        // Destrói todas as sessões do usuário
        WP_Session_Tokens::get_instance( $user_id )->destroy_all();

        // Gera novos cookies para a sessão atual
        wp_clear_auth_cookie();

        wp_redirect( wp_login_url() );
        exit;
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function validate_cpf( string $cpf ): bool {
        if ( strlen( $cpf ) !== 11 || preg_match( '/(\d)\1{10}/', $cpf ) ) {
            return false;
        }
        for ( $t = 9; $t < 11; $t++ ) {
            $sum = 0;
            for ( $i = 0; $i < $t; $i++ ) {
                $sum += (int) $cpf[ $i ] * ( $t + 1 - $i );
            }
            $digit = ( ( 10 * $sum ) % 11 ) % 10;
            if ( (int) $cpf[ $t ] !== $digit ) {
                return false;
            }
        }
        return true;
    }

    private function cpf_already_registered( string $cpf_raw, int $exclude_user_id ): bool {
        $users = get_users( [
            'meta_key'   => 'cpf',
            'meta_value' => $cpf_raw,
            'exclude'    => [ $exclude_user_id ],
            'number'     => 1,
            'fields'     => 'ids',
        ] );
        return ! empty( $users );
    }
}

add_action( 'plugins_loaded', function () {
    BBL_Complete_Profile::get_instance();
} );
