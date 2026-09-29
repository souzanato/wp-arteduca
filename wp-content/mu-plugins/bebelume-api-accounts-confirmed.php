<?php
/**
 * Plugin Name: Bebelume — Conta criada pela API já nasce confirmada
 * Description: Contas criadas pelo Gestão Bebelume (REST, com credencial que pode criar usuários) já
 *              chegam com o e-mail dado como certo — a mesma regra que o bebelume-users aplicava no
 *              class-bbl-users-creator: "não faz sentido pedir confirmação de um segundo link para
 *              quem vai definir a senha pelo link de primeiro acesso".
 *
 * Sem isto a pessoa define a senha pelo link do e-mail de boas-vindas e, no primeiro login, cai na
 * tela "Confirme seu e-mail": o bebelume-pmpro-profile marca `bbl_email_confirmed = 0` em TODO
 * user_register, e o Gestão Bebelume cria a conta pela API — que também dispara user_register.
 *
 * O cadastro público do site continua exigindo a confirmação normalmente: a regra vale só para
 * requisição REST autenticada por quem tem `create_users`.
 */
add_action('user_register', 'bebelume_api_account_confirmed', 20);

function bebelume_api_account_confirmed($user_id): void {
    if (! bebelume_is_trusted_api_creation()) {
        return;
    }

    update_user_meta($user_id, 'bbl_email_confirmed', 1);
}

/**
 * A conta foi criada por uma requisição REST autenticada com permissão de criar usuários (a
 * Application Password do Gestão Bebelume)?
 *
 * As duas condições juntas de propósito: só REST deixa de fora a criação manual pelo wp-admin (onde
 * alguém pode ter digitado o e-mail errado e a confirmação ainda faz sentido), e o `create_users`
 * deixa de fora o cadastro público, que é o fluxo que a confirmação existe para proteger.
 */
function bebelume_is_trusted_api_creation(): bool {
    return defined('REST_REQUEST') && REST_REQUEST && current_user_can('create_users');
}
