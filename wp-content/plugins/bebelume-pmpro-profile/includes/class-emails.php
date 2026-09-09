<?php
/**
 * BBL_Emails — Customização dos e-mails transacionais do WordPress
 *
 * @package Bebelume_PMPro_Profile
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BBL_Emails {

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
        // E-mail de boas-vindas ao novo usuário
        add_filter( 'wp_new_user_notification_email', [ $this, 'new_user_email' ], 10, 3 );

        // E-mail de redefinição de senha
        add_filter( 'retrieve_password_message',      [ $this, 'reset_password_email' ], 10, 4 );
        add_filter( 'retrieve_password_title',        [ $this, 'reset_password_subject' ] );

        // E-mail de senha alterada
        add_filter( 'password_change_email',          [ $this, 'password_changed_email' ], 10, 3 );

        // E-mail de e-mail alterado
        add_filter( 'email_change_email',             [ $this, 'email_changed_email' ], 10, 3 );

        // Força e-mails em HTML
        add_filter( 'wp_mail_content_type', [ $this, 'set_html_content_type' ] );
    }

    // =========================================================================
    // E-MAIL DE BOAS-VINDAS
    // =========================================================================

    public function new_user_email( array $wp_new_user_notification_email, WP_User $user, string $blogname ): array {
        $confirm_url = class_exists( 'BBL_Email_Confirmation' )
            ? BBL_Email_Confirmation::get_instance()->get_confirmation_url_public( $user->ID )
            : wp_login_url();

        $email = $this->build_confirmation_email( $user, $confirm_url );

        return [
            'to'      => $wp_new_user_notification_email['to'],
            'subject' => $email['subject'],
            'message' => $email['message'],
            'headers' => $email['headers'],
        ];
    }

    /**
     * Constrói o e-mail de confirmação com template HTML.
     * Usado tanto no registro inicial quanto no reenvio.
     */
    public function build_confirmation_email( WP_User $user, string $confirm_url ): array {
        $subject = 'Bem-vindo(a) ao Bebelume! Confirme sua conta';
        $message = $this->build_email( [
            'title'      => 'Seja bem-vindo(a) ao Bebelume!',
            'body'       => '<a href="' . esc_url( $confirm_url ) . '" style="display:inline-block;padding:14px 32px;background:#E73665;color:#ffffff;text-decoration:none;border-radius:10px;font-weight:700;font-size:15px;">Confirmar minha conta</a>',
            'link_url'   => $confirm_url,
            'link_label' => $confirm_url,
        ] );

        return [
            'subject' => $subject,
            'message' => $message,
            'headers' => [ 'Content-Type: text/html; charset=UTF-8' ],
        ];
    }

    // =========================================================================
    // E-MAIL DE REDEFINIÇÃO DE SENHA
    // =========================================================================

    public function reset_password_subject( string $title ): string {
        return '[Bebelume] Redefinição de senha';
    }

    public function reset_password_email( string $message, string $key, string $user_login, WP_User $user ): string {
        $reset_url = network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' );

        return $this->build_email( [
            'title'      => 'Redefinição de senha',
            'body'       =>
                '<p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#5a6c7d;text-align:center;">' .
                    'Alguém solicitou a alteração de senha para a sua conta do Bebelume. ' .
                    'Se isso foi um erro, apenas ignore este e-mail e nada acontecerá.' .
                '</p>' .
                '<div style="text-align:center;margin:8px 0 0;"><a href="' . esc_url( $reset_url ) . '" style="display:inline-block;padding:14px 32px;background:#E73665;color:#ffffff;text-decoration:none;border-radius:10px;font-weight:700;font-size:15px;">Redefinir minha senha</a></div>' .
                '<p style="margin:20px 0 0;font-size:13px;color:#b0bcc8;text-align:center;">Ou visite o endereço abaixo:</p>',
            'link_url'   => $reset_url,
            'link_label' => $reset_url,
        ] );
    }

    // =========================================================================
    // E-MAIL DE SENHA ALTERADA
    // =========================================================================

    public function password_changed_email( array $pass_change_email, array $user, array $userdata ): array {
        $login_url = wp_login_url();

        $message = $this->build_email( [
            'title'      => 'Sua senha foi alterada',
            'body'       =>
                '<p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#5a6c7d;text-align:center;">' .
                    'Sua senha da conta Bebelume foi alterada com sucesso.<br>' .
                    'Se você não fez essa alteração, entre em contato conosco imediatamente.' .
                '</p>' .
                '<div style="text-align:center;">' .
                    '<a href="' . esc_url( $login_url ) . '" style="display:inline-block;padding:14px 32px;background:#E73665;color:#ffffff;text-decoration:none;border-radius:10px;font-weight:700;font-size:15px;">Acessar minha conta</a>' .
                '</div>',
            'link_url'   => $login_url,
            'link_label' => '',
        ] );

        return [
            'to'      => $pass_change_email['to'],
            'subject' => '[Bebelume] Sua senha foi alterada',
            'message' => $message,
            'headers' => [ 'Content-Type: text/html; charset=UTF-8' ],
        ];
    }

    // =========================================================================
    // E-MAIL DE E-MAIL ALTERADO
    // =========================================================================

    public function email_changed_email( array $email_change_email, array $user, array $userdata ): array {
        $login_url = wp_login_url();

        $message = $this->build_email( [
            'title'      => 'Seu e-mail foi alterado',
            'body'       =>
                '<p style="margin:0 0 8px;font-size:15px;line-height:1.6;color:#5a6c7d;text-align:center;">' .
                    'O endereço de e-mail da sua conta Bebelume foi alterado.' .
                '</p>' .
                '<p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#b0bcc8;text-align:center;">' .
                    'De: ' . esc_html( $user['user_email'] ) . '<br>' .
                    'Para: ' . esc_html( $userdata['user_email'] ) .
                '</p>' .
                '<p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#5a6c7d;text-align:center;">' .
                    'Se você não fez essa alteração, entre em contato conosco imediatamente.' .
                '</p>' .
                '<div style="text-align:center;">' .
                    '<a href="' . esc_url( $login_url ) . '" style="display:inline-block;padding:14px 32px;background:#E73665;color:#ffffff;text-decoration:none;border-radius:10px;font-weight:700;font-size:15px;">Acessar minha conta</a>' .
                '</div>',
            'link_url'   => $login_url,
            'link_label' => '',
        ] );

        return [
            'to'      => $email_change_email['to'],
            'subject' => '[Bebelume] Seu e-mail foi alterado',
            'message' => $message,
            'headers' => [ 'Content-Type: text/html; charset=UTF-8' ],
        ];
    }

    // =========================================================================
    // BUILDER DE E-MAIL HTML
    // =========================================================================

    /**
     * Constrói o shell HTML da marca (logo, card, título, corpo, link).
     * Público: reutilizado pelos e-mails transacionais do PMPro (BBL_PMPro_Emails).
     */
    public function build_email( array $args ): string {
        $title      = $args['title']      ?? '';
        $body       = $args['body']       ?? '';
        $link_url   = $args['link_url']   ?? '';
        $link_label = $args['link_label'] ?? '';
        $align      = $args['align']      ?? 'center';

        // Link alternativo discreto (só quando solicitado)
        $link_html = $link_label
            ? '<p style="margin:24px 0 0;font-size:12px;color:#b0bcc8;text-align:center;word-break:break-all;">'
                . '<a href="' . esc_url( $link_url ) . '" style="color:#b0bcc8;text-decoration:underline;">'
                . $link_label . '</a></p>'
            : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$title}</title>
</head>
<body style="margin:0;padding:0;background:#f5f7fa;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f7fa;padding:40px 16px;">
  <tr>
    <td align="center">
      <table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">

        <!-- Logo -->
        <tr>
          <td align="center" style="padding-bottom:24px;">
            <img src="https://hub.bebelume.com.br/wp-content/uploads/2025/11/cropped-logo-bebelume-play.png"
                 alt="Bebelume" width="60" height="60"
                 style="border-radius:50%;display:block;">
          </td>
        </tr>

        <!-- Card -->
        <tr>
          <td style="background:#ffffff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);padding:40px 40px 32px;">

            <!-- Título -->
            <h1 style="margin:0 0 16px;font-size:24px;font-weight:800;color:#1a1040;text-align:center;">
              {$title}
            </h1>

            <!-- Corpo -->
            <div style="margin:0 0 28px;font-size:15px;line-height:1.6;color:#5a6c7d;text-align:{$align};">
              {$body}
            </div>

            <!-- Link alternativo -->
            {$link_html}

          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="padding:24px 0 0;text-align:center;">
            <p style="margin:0;font-size:12px;color:#b0bcc8;">
              © Bebelume · Arte e imaginação para a primeira infância
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>
HTML;
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    public function set_html_content_type(): string {
        return 'text/html';
    }
}

add_action( 'plugins_loaded', function () {
    BBL_Emails::get_instance();
} );
