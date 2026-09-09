<?php
/**
 * BBL_Expired_Link — Tela customizada para links expirados/inválidos
 *
 * Cobre: token de confirmação inválido e link de reset de senha expirado.
 *
 * @package Bebelume_PMPro_Profile
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class BBL_Expired_Link {

    private static $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action( 'login_footer', [ $this, 'inject_expired_ui' ], 20 );
    }

    public function inject_expired_ui(): void {
        // Link de confirmação de e-mail inválido
        $confirm_error = $_GET['bbl_confirm_error'] ?? '';

        // Link de reset de senha expirado — WP usa ?action=lostpassword&error=expiredkey ou invalidkey
        $wp_error = $_GET['error'] ?? '';
        $is_expired_reset = in_array( $wp_error, [ 'expiredkey', 'invalidkey' ], true );

        if ( ! $confirm_error && ! $is_expired_reset ) {
            return;
        }

        $lost_url = wp_lostpassword_url();
        $login_url = wp_login_url();

        if ( $confirm_error ) {
            $title   = 'Link inválido ou expirado';
            $msg     = 'O link de confirmação que você usou é inválido ou já expirou.<br>Solicite um novo link abaixo.';
            $btn_url = $login_url;
            $btn_txt = 'Voltar ao login';
        } else {
            $title   = 'Link expirado';
            $msg     = 'O link de redefinição de senha expirou.<br>Solicite um novo link para continuar.';
            $btn_url = $lost_url;
            $btn_txt = 'Solicitar novo link';
        }
        ?>
        <script>
        (function () {
            var login = document.getElementById('login');
            if ( ! login ) return;

            login.innerHTML =
                '<div class="bbl-checkemail">' +
                    '<div class="bbl-checkemail-icon">' +
                        '<svg width="64" height="64" viewBox="0 0 64 64" fill="none">' +
                            '<circle cx="32" cy="32" r="32" fill="#FFF8E7"/>' +
                            '<path d="M32 20v16" stroke="#FFB300" stroke-width="3" stroke-linecap="round"/>' +
                            '<circle cx="32" cy="44" r="2.5" fill="#FFB300"/>' +
                        '</svg>' +
                    '</div>' +
                    '<h2 class="bbl-checkemail-title"><?php echo esc_js( $title ); ?></h2>' +
                    '<p class="bbl-checkemail-msg"><?php echo esc_js( $msg ); ?></p>' +
                    '<a href="<?php echo esc_js( $btn_url ); ?>" class="bbl-checkemail-btn"><?php echo esc_js( $btn_txt ); ?></a>' +
                    '<a href="<?php echo esc_js( $login_url ); ?>" class="bbl-checkemail-back">← Voltar ao login</a>' +
                '</div>';
        })();
        </script>
        <?php
    }
}

add_action( 'plugins_loaded', function () {
    BBL_Expired_Link::get_instance();
} );
