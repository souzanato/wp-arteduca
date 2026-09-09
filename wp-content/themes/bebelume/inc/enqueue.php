<?php
/**
 * Enqueue scripts and styles
 * 
 * @package Bebelume_Theme
 * @since 1.0.0
 */

// Evita acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enfileira estilos e scripts do tema
 */
function bebelume_enqueue_assets() {
    // Bootstrap CSS (CDN)
    wp_enqueue_style(
        'bootstrap',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
        array(),
        '5.3.2'
    );
    
    // CSS principal do tema
    wp_enqueue_style(
        'bebelume-main',
        BEBELUME_THEME_URI . '/assets/css/main.css',
        array('bootstrap'),
        BEBELUME_VERSION
    );
    
    // Bootstrap JS (CDN)
    wp_enqueue_script(
        'bootstrap-js',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js',
        array(),
        '5.3.2',
        true
    );
    
    // JavaScript principal do tema
    wp_enqueue_script(
        'bebelume-main',
        BEBELUME_THEME_URI . '/assets/js/main.js',
        array('jquery', 'bootstrap-js'),
        BEBELUME_VERSION,
        true
    );
}
add_action('wp_enqueue_scripts', 'bebelume_enqueue_assets');