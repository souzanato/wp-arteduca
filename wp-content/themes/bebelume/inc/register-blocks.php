<?php
/**
 * Registra os blocos Gutenberg do Bebelume
 * 
 * @package Bebelume_Theme
 */

// Evita acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registra os blocos
 */
function bebelume_register_blocks() {
    // Lista de todos os blocos
    $blocks = array(
       'sobre-nos',
       'telhado-casinha-bebelume',
       'telhado-castelinho-bebelume',
       'telhado-passarinhos-bebelume',
       'andar-castelinho-bebelume',
       'andar-casinha-bebelume',
       'terreo-casinha-bebelume',
       'terreo-castelinho-bebelume',
    );

    foreach ($blocks as $block) {
        $block_path = get_template_directory() . '/build/blocks/' . $block;
        
        // Verifica se o diretório do bloco existe
        if (file_exists($block_path)) {
            register_block_type($block_path);
        }
    }
}
add_action('init', 'bebelume_register_blocks');