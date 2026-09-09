<?php
/**
 * Custom Body Classes por Página/Post
 * 
 * Permite adicionar classes CSS customizadas ao body de páginas/posts específicas
 */

// Evita acesso direto
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Adiciona metabox no editor
 */
function bebelume_arteduca_add_body_classes_metabox() {
    add_meta_box(
        'bebelume_body_classes',
        'Classes CSS Adicionais (Body)',
        'bebelume_arteduca_body_classes_callback',
        ['post', 'page'],
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'bebelume_arteduca_add_body_classes_metabox');

/**
 * Renderiza o metabox
 */
function bebelume_arteduca_body_classes_callback($post) {
    // Nonce para segurança
    wp_nonce_field('bebelume_body_classes_save', 'bebelume_body_classes_nonce');
    
    // Valor atual
    $classes = get_post_meta($post->ID, '_bebelume_body_classes', true);
    
    ?>
    <input 
        type="text" 
        id="bebelume_body_classes" 
        name="bebelume_body_classes" 
        value="<?php echo esc_attr($classes); ?>" 
        placeholder="ex: filtro-arteduca max-width-1200"
        style="width: 100%;"
    >
    <?php
}

/**
 * Salva o valor do metabox
 */
function bebelume_arteduca_save_body_classes($post_id) {
    // Verifica nonce
    if (!isset($_POST['bebelume_body_classes_nonce']) || 
        !wp_verify_nonce($_POST['bebelume_body_classes_nonce'], 'bebelume_body_classes_save')) {
        return;
    }
    
    // Verifica autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Verifica permissões
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Sanitiza e salva
    if (isset($_POST['bebelume_body_classes'])) {
        $classes = sanitize_text_field($_POST['bebelume_body_classes']);
        update_post_meta($post_id, '_bebelume_body_classes', $classes);
    } else {
        delete_post_meta($post_id, '_bebelume_body_classes');
    }
}
add_action('save_post', 'bebelume_arteduca_save_body_classes');

/**
 * Adiciona classes ao body no frontend
 */
function bebelume_arteduca_add_custom_body_classes($classes) {
    // Só funciona em páginas/posts singulares
    if (is_singular()) {
        $custom_classes = get_post_meta(get_the_ID(), '_bebelume_body_classes', true);
        
        if (!empty($custom_classes)) {
            // Transforma string em array de classes
            $custom_classes_array = array_filter(array_map('trim', explode(' ', $custom_classes)));
            
            // Adiciona ao array de classes do body
            $classes = array_merge($classes, $custom_classes_array);
        }
    }
    
    return $classes;
}
add_filter('body_class', 'bebelume_arteduca_add_custom_body_classes');
