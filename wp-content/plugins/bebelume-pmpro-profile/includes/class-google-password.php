<?php
/**
 * BBL_Google_Password — Fluxo de definir senha para usuários que entraram pelo Google
 *
 * Usuários que criaram conta via Google nunca definiram uma senha.
 * Este módulo adiciona na tela de completar cadastro a opção de definir uma senha,
 * e no "esqueceu a senha" trata o caso de conta Google sem senha.
 *
 * @package Bebelume_PMPro_Profile
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class BBL_Google_Password {

    private static $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        // Avisa na tela de completar cadastro se conta veio do Google
        add_action( 'login_footer', [ $this, 'inject_google_set_password_hint' ], 25 );
    }

    public function is_google_account( int $user_id ): bool {
        return (bool) get_user_meta( $user_id, 'bbl_google_id', true );
    }

    public function inject_google_set_password_hint(): void {
        $action = $_GET['action'] ?? '';
        if ( $action !== 'bbl_complete_profile' ) return;
        if ( ! is_user_logged_in() ) return;

        $user_id = get_current_user_id();
        if ( ! $this->is_google_account( $user_id ) ) return;

        $reset_url = wp_lostpassword_url();
        ?>
        <script>
        (function () {
            // Adiciona dica de definir senha após o formulário de completar cadastro
            var form = document.getElementById('bbl_complete_form');
            if ( ! form ) return;

            var hint = document.createElement('p');
            hint.className = 'bbl-google-pw-hint';
            hint.innerHTML =
                'Sua conta foi criada via Google. ' +
                'Quer também poder entrar com e-mail e senha? ' +
                '<a href="<?php echo esc_js( $reset_url ); ?>">Definir uma senha</a>';

            form.parentNode.insertBefore( hint, form.nextSibling );
        })();
        </script>
        <?php
    }
}

add_action( 'plugins_loaded', function () {
    BBL_Google_Password::get_instance();
} );
