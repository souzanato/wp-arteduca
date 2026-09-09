<?php
/**
 * Menu ArtEduca > Páginas
 * Permite configurar qual página deve ser usada como "página inicial"
 * do ArtEduca em cada situação de acesso do usuário:
 *   1. Logado sem assinatura
 *   2. Logado com assinatura
 *   3. Não logado
 */

if (!defined('ABSPATH')) exit;

// ──────────────────────────────────────────────
// DEBUG - grava um log próprio em wp-content/bebelume-arteduca-debug.log
// Para desativar depois, defina BEBELUME_ARTEDUCA_DEBUG como false
// no wp-config.php (antes do "That's all, stop editing!").
// ──────────────────────────────────────────────
if (!defined('BEBELUME_ARTEDUCA_DEBUG')) {
    define('BEBELUME_ARTEDUCA_DEBUG', true);
}

function bebelume_arteduca_debug_log($mensagem) {
    if (!BEBELUME_ARTEDUCA_DEBUG) return;
    $linha = '[' . current_time('mysql') . '] ' . $mensagem . "\n";
    @error_log($linha, 3, WP_CONTENT_DIR . '/bebelume-arteduca-debug.log');
}

// ──────────────────────────────────────────────
// Registra o template do plugin como opção selecionável em
// "Atributos da Página" (Page Attributes) e garante que o
// arquivo correto do PLUGIN seja carregado quando selecionado.
//
// Sem isso, um "Template Name:" dentro de um arquivo de PLUGIN
// não aparece no seletor nem é servido — WordPress só escaneia
// automaticamente arquivos dentro do TEMA ativo.
// ──────────────────────────────────────────────
function bebelume_arteduca_registrar_template($templates) {
    $templates['templates/page-arteduca-inicio.php'] = 'ArtEduca Início';
    return $templates;
}
add_filter('theme_page_templates', 'bebelume_arteduca_registrar_template');

function bebelume_arteduca_carregar_template($template) {
    if (is_admin()) return $template;

    $template_slug = get_page_template_slug();
    if ($template_slug === 'templates/page-arteduca-inicio.php') {
        $arquivo = BEBELUME_ARTEDUCA_PLUGIN_DIR . 'templates/page-arteduca-inicio.php';
        bebelume_arteduca_debug_log('carregar_template: post ' . get_the_ID() . ' usa o template do ArtEduca. Arquivo existe? ' . (file_exists($arquivo) ? 'sim' : 'NÃO'));
        if (file_exists($arquivo)) {
            return $arquivo;
        }
    }
    return $template;
}
add_filter('template_include', 'bebelume_arteduca_carregar_template');

// ──────────────────────────────────────────────
// Registra o menu "ArtEduca" e o submenu "Páginas"
// Ao clicar em "ArtEduca" o usuário já cai em "Páginas",
// pois usamos o MESMO slug no submenu (renomeia o item
// automático que o WordPress cria para o menu principal).
// ──────────────────────────────────────────────
function bebelume_arteduca_paginas_menu() {
    add_menu_page(
        __('ArtEduca - Páginas', 'bebelume-arteduca'),
        __('ArtEduca', 'bebelume-arteduca'),
        'manage_options',
        'bebelume-arteduca-paginas',
        'bebelume_arteduca_paginas_page',
        'dashicons-admin-page',
        31
    );

    add_submenu_page(
        'bebelume-arteduca-paginas',
        __('Páginas', 'bebelume-arteduca'),
        __('Páginas', 'bebelume-arteduca'),
        'manage_options',
        'bebelume-arteduca-paginas',
        'bebelume_arteduca_paginas_page'
    );
}
add_action('admin_menu', 'bebelume_arteduca_paginas_menu');

// ──────────────────────────────────────────────
// Mapa: campo do formulário → nome da option salva
// ──────────────────────────────────────────────
function bebelume_arteduca_paginas_config_keys() {
    return [
        'logado_sem_assinatura' => 'bebelume_pagina_logado_sem_assinatura',
        'logado_com_assinatura' => 'bebelume_pagina_logado_com_assinatura',
        'nao_logado'            => 'bebelume_pagina_nao_logado',
    ];
}

/**
 * Helper público: retorna o ID da página configurada para uma situação.
 * Uso: bebelume_arteduca_get_pagina('logado_com_assinatura')
 */
function bebelume_arteduca_get_pagina($situacao) {
    $keys = bebelume_arteduca_paginas_config_keys();
    if (!isset($keys[$situacao])) return 0;
    return intval(get_option($keys[$situacao], 0));
}

/**
 * Descobre em qual das 3 situações o usuário atual se encaixa.
 */
function bebelume_arteduca_get_situacao_usuario() {
    if (!is_user_logged_in()) {
        bebelume_arteduca_debug_log('situacao_usuario: visitante não logado');
        return 'nao_logado';
    }

    $tem_acesso = function_exists('bebelume_arteduca_user_has_access') && bebelume_arteduca_user_has_access();
    bebelume_arteduca_debug_log('situacao_usuario: user_id=' . get_current_user_id() . ' | bebelume_arteduca_user_has_access()=' . ($tem_acesso ? 'true' : 'false'));

    if ($tem_acesso) {
        return 'logado_com_assinatura';
    }

    return 'logado_sem_assinatura';
}

// ──────────────────────────────────────────────
// Redireciona a "página inicial" do ArtEduca conforme a situação
// do usuário (logado com/sem assinatura, ou não logado).
// Atua na página que usa o template "ArtEduca Início".
// ──────────────────────────────────────────────
function bebelume_arteduca_redirecionar_pagina_inicial() {
    if (is_admin()) return;

    $template_atual = get_page_template_slug();
    $usa_template   = is_page_template('templates/page-arteduca-inicio.php');
    $eh_front_page  = is_front_page();
    $eh_pagina_arteduca = $eh_front_page || $usa_template;

    bebelume_arteduca_debug_log(
        'redirect_check: URL=' . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '?') .
        ' | post_id=' . get_the_ID() .
        ' | template_slug_salvo="' . $template_atual . '"' .
        ' | is_front_page()=' . ($eh_front_page ? 'true' : 'false') .
        ' | is_page_template(arteduca-inicio)=' . ($usa_template ? 'true' : 'false')
    );

    if (!$eh_pagina_arteduca) return;

    $situacao  = bebelume_arteduca_get_situacao_usuario();
    $pagina_id = bebelume_arteduca_get_pagina($situacao);

    bebelume_arteduca_debug_log('redirect_check: situacao="' . $situacao . '" | pagina_id_configurada=' . $pagina_id);

    // Nada configurado para essa situação: mantém o template padrão.
    if (!$pagina_id) {
        bebelume_arteduca_debug_log('redirect_check: NADA configurado para "' . $situacao . '" — não redireciona.');
        return;
    }

    // Evita loop: se a própria página configurada é a página atual, não redireciona.
    if (is_page($pagina_id)) {
        bebelume_arteduca_debug_log('redirect_check: já está na página de destino (id=' . $pagina_id . ') — não redireciona.');
        return;
    }

    $destino = get_permalink($pagina_id);
    if (!$destino) {
        bebelume_arteduca_debug_log('redirect_check: get_permalink(' . $pagina_id . ') falhou — página pode não existir mais.');
        return;
    }

    bebelume_arteduca_debug_log('redirect_check: REDIRECIONANDO para ' . $destino);
    wp_safe_redirect($destino);
    exit;
}
add_action('template_redirect', 'bebelume_arteduca_redirecionar_pagina_inicial');

// ──────────────────────────────────────────────
// Processa o salvamento (POST)
// ──────────────────────────────────────────────
function bebelume_arteduca_paginas_handle_save() {
    if (!current_user_can('manage_options')) return;
    if (!isset($_POST['bebelume_paginas_nonce'])) return;
    if (!wp_verify_nonce($_POST['bebelume_paginas_nonce'], 'bebelume_paginas_action')) return;

    foreach (bebelume_arteduca_paginas_config_keys() as $campo => $option_name) {
        $page_id = intval($_POST[$campo] ?? 0);
        update_option($option_name, $page_id);
    }

    wp_redirect(admin_url('admin.php?page=bebelume-arteduca-paginas&saved=1'));
    exit;
}
add_action('admin_post_bebelume_arteduca_paginas', 'bebelume_arteduca_paginas_handle_save');

// ──────────────────────────────────────────────
// Renderiza a página de admin
// ──────────────────────────────────────────────
function bebelume_arteduca_paginas_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Você não tem permissão para acessar esta página.', 'bebelume-arteduca'));
    }

    $saved = isset($_GET['saved']) ? intval($_GET['saved']) : 0;
    $keys  = bebelume_arteduca_paginas_config_keys();

    $atual = [];
    foreach ($keys as $campo => $option_name) {
        $atual[$campo] = intval(get_option($option_name, 0));
    }

    $situacoes = [
        'logado_sem_assinatura' => __('1. Página inicial — logado, sem assinatura', 'bebelume-arteduca'),
        'logado_com_assinatura' => __('2. Página inicial — logado, com assinatura', 'bebelume-arteduca'),
        'nao_logado'            => __('3. Página inicial — não logado', 'bebelume-arteduca'),
    ];
    ?>
    <div class="wrap">
        <h1 style="margin-bottom:20px;">📄 <?php esc_html_e('ArtEduca - Páginas Iniciais', 'bebelume-arteduca'); ?></h1>
        <p style="color:#666;margin-bottom:30px;">
            <?php esc_html_e('Selecione qual página do site deve ser exibida como "página inicial" do ArtEduca em cada uma das situações abaixo.', 'bebelume-arteduca'); ?>
        </p>

        <?php if ($saved): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('Alterações salvas com sucesso!', 'bebelume-arteduca'); ?></p>
            </div>
        <?php endif; ?>

        <div class="bebelume-crud-card" style="max-width:650px;--cor:#2A4582;">
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="bebelume_arteduca_paginas">
                <input type="hidden" name="bebelume_paginas_nonce" value="<?php echo esc_attr(wp_create_nonce('bebelume_paginas_action')); ?>">

                <table class="form-table" role="presentation">
                    <tbody>
                        <?php foreach ($situacoes as $campo => $label): ?>
                        <tr>
                            <th scope="row">
                                <label for="<?php echo esc_attr($campo); ?>"><?php echo esc_html($label); ?></label>
                            </th>
                            <td>
                                <?php
                                wp_dropdown_pages([
                                    'name'              => $campo,
                                    'id'                => $campo,
                                    'selected'          => $atual[$campo],
                                    'show_option_none'  => __('— Selecione uma página —', 'bebelume-arteduca'),
                                    'option_none_value' => 0,
                                ]);
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php submit_button(__('Salvar Alterações', 'bebelume-arteduca')); ?>
            </form>
        </div>
    </div>
    <?php

    // Reaproveita os estilos .bebelume-crud-card já usados no menu Pedagógico,
    // caso a página seja acessada isoladamente (garante consistência visual).
    if (function_exists('bebelume_pedagogico_admin_styles')) {
        bebelume_pedagogico_admin_styles();
    }
}
