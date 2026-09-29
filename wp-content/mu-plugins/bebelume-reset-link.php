<?php
/**
 * Plugin Name: Bebelume — Link de definir senha
 * Description: Devolve o link do próprio WordPress para a pessoa definir a senha (o mesmo que o
 *              bebelume-users montava), para o Gestão Bebelume usar no e-mail de boas-vindas.
 *
 * O link só pode ser gerado DENTRO do WordPress: quem cria a chave é o get_password_reset_key(),
 * que grava o hash em wp_users.user_activation_key. Este endpoint existe só para isso — não cria,
 * não altera e não apaga usuário nenhum.
 */
add_action('rest_api_init', function () {
    register_rest_route('gestao-bebelume/v1', '/reset-link', [
        'methods'  => 'POST',
        'callback' => 'bebelume_reset_link',
        // A checagem de permissão fica DENTRO do callback: o callback roda no dispatch, com o
        // usuário da Application Password já resolvido (o permission_callback, aqui, é avaliado
        // antes disso e negava a requisição autenticada com 401).
        'permission_callback' => '__return_true',
        'args' => [
            'email' => [ 'required' => true, 'type' => 'string' ],
        ],
    ]);
});

function bebelume_reset_link(WP_REST_Request $request) {
    if (! current_user_can('create_users')) {
        return new WP_Error('bebelume_forbidden', 'Sem permissão para gerar o link.', [ 'status' => 401 ]);
    }

    $user = get_user_by('email', sanitize_email($request['email']));

    if (! $user) {
        return new WP_Error('bebelume_user_not_found', 'Não há usuário com este e-mail.', [ 'status' => 404 ]);
    }

    $key = get_password_reset_key($user);

    if (is_wp_error($key)) {
        return $key;
    }

    return [
        'url'   => network_site_url(
            'wp-login.php?action=rp&key=' . $key . '&login=' . rawurlencode($user->user_login),
            'login'
        ),
        'login' => $user->user_login,
        // A validade do link (padrão do WordPress: 24 horas) — quem manda o e-mail pode dizer isso.
        'expira_em' => (int) apply_filters('password_reset_expiration', DAY_IN_SECONDS),
    ];
}
