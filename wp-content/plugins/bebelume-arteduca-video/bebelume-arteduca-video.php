<?php
/**
 * Plugin Name: Bebelume ArtEduca Video
 * Plugin URI: https://bebelume.com
 * Description: Player de vídeo HTML5 com Plyr.io e customização de cores - Bloco Gutenberg
 * Version: 1.2.0
 * Author: Bebelume
 * License: GPL v2 or later
 * Text Domain: bebelume-arteduca-video
 */

// Impede acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registra o bloco Gutenberg
 */
function bebelume_arteduca_video_register_block() {
    // Registra o Plyr CSS do CDN
    wp_register_style(
        'plyr',
        'https://cdn.plyr.io/3.8.4/plyr.css',
        array(),
        '3.8.4'
    );

    // Registra o Plyr JS do CDN
    wp_register_script(
        'plyr',
        'https://cdn.plyr.io/3.8.4/plyr.js',
        array(),
        '3.8.4',
        true
    );

    // Registra o script do bloco
    wp_register_script(
        'bebelume-arteduca-video-block',
        plugins_url('block.js', __FILE__),
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
        filemtime(plugin_dir_path(__FILE__) . 'block.js')
    );

    // Registra o estilo do editor
    wp_register_style(
        'bebelume-arteduca-video-editor',
        plugins_url('editor.css', __FILE__),
        array('wp-edit-blocks', 'plyr'),
        filemtime(plugin_dir_path(__FILE__) . 'editor.css')
    );

    // Registra o estilo do frontend
    wp_register_style(
        'bebelume-arteduca-video-style',
        plugins_url('style.css', __FILE__),
        array('plyr'),
        filemtime(plugin_dir_path(__FILE__) . 'style.css')
    );

    // Registra o script do frontend
    wp_register_script(
        'bebelume-arteduca-video-frontend',
        plugins_url('frontend.js', __FILE__),
        array('plyr'),
        filemtime(plugin_dir_path(__FILE__) . 'frontend.js'),
        true
    );

    // Passa a URL do plugin para o JavaScript
    wp_add_inline_script(
        'bebelume-arteduca-video-frontend',
        'var bebelumePluginUrl = "' . plugins_url('', __FILE__) . '";',
        'before'
    );
    
    // Passa a URL da REST API para o block.js
    wp_add_inline_script(
        'bebelume-arteduca-video-block',
        'var bebelumeVideoApiUrl = "' . rest_url('vpb/v1/video-posts') . '";',
        'before'
    );

    // Registra o bloco
    register_block_type('bebelume/arteduca-video', array(
        'editor_script' => 'bebelume-arteduca-video-block',
        'editor_style' => 'bebelume-arteduca-video-editor',
        'style' => 'bebelume-arteduca-video-style',
        'script' => 'bebelume-arteduca-video-frontend',
    ));
}
add_action('init', 'bebelume_arteduca_video_register_block');

/**
 * Adiciona estilos da modal no editor
 */
function bebelume_arteduca_video_editor_styles() {
    ?>
    <style>
        .bebelume-import-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .bebelume-import-modal {
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            max-width: 900px;
            width: 100%;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
        }
        
        .bebelume-import-modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
        }
        
        .bebelume-import-modal-header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        
        .bebelume-import-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            color: #666;
        }
        
        .bebelume-import-modal-close:hover {
            background: #f0f0f0;
            color: #000;
        }
        
        .bebelume-import-modal-body {
            padding: 24px;
            overflow-y: auto;
            flex: 1;
        }
        
        .bebelume-import-modal-loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .bebelume-import-modal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .bebelume-import-video-card {
            border: 2px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .bebelume-import-video-card:hover {
            border-color: #00b3ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 179, 255, 0.2);
        }
        
        .bebelume-import-video-thumbnail {
            width: 100%;
            aspect-ratio: 16/9;
            object-fit: cover;
            background: #f0f0f0;
        }
        
        .bebelume-import-video-info {
            padding: 12px;
        }
        
        .bebelume-import-video-title {
            font-weight: 600;
            font-size: 14px;
            margin: 0 0 8px;
            color: #1e1e1e;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .bebelume-import-video-desc {
            font-size: 12px;
            color: #666;
            margin: 0 0 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .bebelume-import-video-date {
            font-size: 11px;
            color: #999;
        }
        
        .bebelume-import-modal-empty {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .bebelume-import-modal-empty-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.3;
        }
    </style>
    <?php
}
add_action('admin_head', 'bebelume_arteduca_video_editor_styles');
