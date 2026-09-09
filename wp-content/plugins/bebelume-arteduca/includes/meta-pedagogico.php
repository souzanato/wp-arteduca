<?php
/**
 * Meta: Direitos de Aprendizagem e Campos de Experiência por post
 * Sidebar do Gutenberg via REST API + plugin sidebar JS
 */

if (!defined('ABSPATH')) exit;

// ──────────────────────────────────────────────
// Registra os meta fields para REST API (Gutenberg precisa disso)
// ──────────────────────────────────────────────
function bebelume_register_post_meta() {
    $args = array(
        'show_in_rest' => array(
            'schema' => array(
                'type'  => 'array',
                'items' => array('type' => 'string'),
            ),
        ),
        'single'       => true,
        'type'         => 'array',
        'auth_callback'=> function() { return current_user_can('edit_posts'); },
    );

    register_post_meta('post', '_bebelume_direitos', $args);
    register_post_meta('post', '_bebelume_campos',   $args);
    register_post_meta('page', '_bebelume_direitos', $args);
    register_post_meta('page', '_bebelume_campos',   $args);
}
add_action('init', 'bebelume_register_post_meta');

// ──────────────────────────────────────────────
// Enfileira o script da sidebar do Gutenberg
// ──────────────────────────────────────────────
function bebelume_enqueue_sidebar_plugin() {
    // Só carrega no editor de blocos
    $screen = get_current_screen();
    if (!$screen || !$screen->is_block_editor()) return;

    wp_enqueue_script(
        'bebelume-pedagogico-sidebar',
        BEBELUME_ARTEDUCA_PLUGIN_URL . 'assets/js/sidebar-pedagogico.js',
        array('wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-core-data', 'wp-i18n'),
        BEBELUME_ARTEDUCA_VERSION,
        true
    );

    // Passa as listas de opções para o JS
    wp_localize_script('bebelume-pedagogico-sidebar', 'bebelumePedagogico', array(
        'direitos' => bebelume_get_direitos(),
        'campos'   => bebelume_get_campos(),
    ));
}
add_action('enqueue_block_editor_assets', 'bebelume_enqueue_sidebar_plugin');
