<?php
/**
 * Renderização server-side do bloco Campos Pedagógicos
 * Lê os meta _bebelume_direitos e _bebelume_campos do post atual
 */

if (!defined('ABSPATH')) exit;

$post_id  = get_the_ID();
$direitos = get_post_meta($post_id, '_bebelume_direitos', true);
$campos   = get_post_meta($post_id, '_bebelume_campos',   true);

// Garante arrays
$direitos = is_array($direitos) ? array_filter($direitos) : [];
$campos   = is_array($campos)   ? array_filter($campos)   : [];

// Se não há nada marcado, não renderiza nada no frontend
if (empty($direitos) && empty($campos)) {
    // No editor exibe aviso, no frontend não exibe nada
    if (defined('REST_REQUEST') && REST_REQUEST) {
        echo '<div class="bebelume-campos-pedagogicos bebelume-vazio-aviso">'
           . '<em>' . esc_html__('Nenhum item marcado ainda. Use o painel "Pedagógico" na barra lateral.', 'bebelume-arteduca') . '</em>'
           . '</div>';
    }
    return;
}
?>
<div class="bebelume-campos-pedagogicos">

    <?php if (!empty($direitos)): ?>
    <div class="bebelume-campo-row">
        <p class="bebelume-campo-titulo">
            <?php esc_html_e('Direitos de Aprendizagem', 'bebelume-arteduca'); ?>
        </p>
        <p class="bebelume-campo-valor">
            <?php echo esc_html(implode('; ', $direitos)); ?>
        </p>
    </div>
    <?php endif; ?>

    <?php if (!empty($campos)): ?>
    <div class="bebelume-campo-row">
        <p class="bebelume-campo-titulo">
            <?php esc_html_e('Campos de Experiência', 'bebelume-arteduca'); ?>
        </p>
        <p class="bebelume-campo-valor">
            <?php echo esc_html(implode('; ', $campos)); ?>
        </p>
    </div>
    <?php endif; ?>

</div>
