<?php
/**
 * CRUD - Direitos de Aprendizagem e Campos de Experiência
 * Página de configurações no admin do WordPress
 * COM UPLOAD DE IMAGENS
 */

if (!defined('ABSPATH')) exit;

// ──────────────────────────────────────────────
// Registra a página de configurações no menu admin
// ──────────────────────────────────────────────
function bebelume_pedagogico_admin_menu() {
    add_menu_page(
        __('Pedagógico - Bebelume', 'bebelume-arteduca'),
        __('Bebelume Pedagógico', 'bebelume-arteduca'),
        'manage_options',
        'bebelume-pedagogico',
        'bebelume_pedagogico_admin_page',
        'dashicons-welcome-learn-more',
        30
    );
}
add_action('admin_menu', 'bebelume_pedagogico_admin_menu');

// Enfileirar biblioteca de mídia do WordPress
function bebelume_pedagogico_enqueue_media() {
    if (isset($_GET['page']) && $_GET['page'] === 'bebelume-pedagogico') {
        wp_enqueue_media();
    }
}
add_action('admin_enqueue_scripts', 'bebelume_pedagogico_enqueue_media');

// ──────────────────────────────────────────────
// Helpers: leitura e escrita das listas
// ──────────────────────────────────────────────
function bebelume_get_direitos() {
    $defaults = ['Conviver', 'Brincar', 'Conhecer-se', 'Participar', 'Expressar', 'Explorar'];
    $saved = get_option('bebelume_direitos_aprendizagem', null);
    if ($saved === null) {
        update_option('bebelume_direitos_aprendizagem', $defaults);
        return $defaults;
    }
    return is_array($saved) ? $saved : $defaults;
}

function bebelume_get_campos() {
    $defaults = [
        'Corpo, Gestos e Movimentos',
        'O Eu, o Outro, o Nós',
        'Escuta, Fala, Pensamento e Imaginação',
        'Espaço, Tempos, Quantidades, Relações e Transformações',
        'Traços, Sons, Cores e Formas',
    ];
    $saved = get_option('bebelume_campos_experiencia', null);
    if ($saved === null) {
        update_option('bebelume_campos_experiencia', $defaults);
        return $defaults;
    }
    return is_array($saved) ? $saved : $defaults;
}

// ──────────────────────────────────────────────
// Funções para gerenciar imagens dos itens
// ──────────────────────────────────────────────
function bebelume_set_item_image($tipo, $item_nome, $attachment_id) {
    $option_name = 'bebelume_' . $tipo . '_images';
    $images = get_option($option_name, []);
    $images[$item_nome] = $attachment_id;
    update_option($option_name, $images);
}

function bebelume_get_item_image($tipo, $item_nome) {
    $option_name = 'bebelume_' . $tipo . '_images';
    $images = get_option($option_name, []);
    
    if (!isset($images[$item_nome])) {
        return null;
    }
    
    $attachment_id = $images[$item_nome];
    $url = wp_get_attachment_image_url($attachment_id, 'thumbnail');
    
    if (!$url) {
        return null;
    }
    
    return [
        'id' => $attachment_id,
        'url' => $url
    ];
}

function bebelume_delete_item_image($tipo, $item_nome) {
    $option_name = 'bebelume_' . $tipo . '_images';
    $images = get_option($option_name, []);
    
    if (isset($images[$item_nome])) {
        unset($images[$item_nome]);
        update_option($option_name, $images);
    }
}

function bebelume_get_all_images($tipo) {
    $option_name = 'bebelume_' . $tipo . '_images';
    $images_ids = get_option($option_name, []);
    $result = [];
    
    foreach ($images_ids as $item_nome => $attachment_id) {
        $url = wp_get_attachment_image_url($attachment_id, 'thumbnail');
        if ($url) {
            $result[$item_nome] = [
                'id' => $attachment_id,
                'url' => $url
            ];
        }
    }
    
    return $result;
}

// ──────────────────────────────────────────────
// Processa as ações POST (adicionar / editar / remover / imagem)
// ──────────────────────────────────────────────
function bebelume_pedagogico_handle_actions() {
    if (!current_user_can('manage_options')) return;
    if (!isset($_POST['bebelume_pedagogico_nonce'])) return;
    if (!wp_verify_nonce($_POST['bebelume_pedagogico_nonce'], 'bebelume_pedagogico_action')) return;

    $action = sanitize_text_field($_POST['bebelume_action'] ?? '');
    $tipo   = sanitize_text_field($_POST['bebelume_tipo'] ?? '');
    $option = $tipo === 'campos' ? 'bebelume_campos_experiencia' : 'bebelume_direitos_aprendizagem';
    $lista  = $tipo === 'campos' ? bebelume_get_campos() : bebelume_get_direitos();

    if ($action === 'adicionar') {
        $novo = sanitize_text_field($_POST['bebelume_novo_item'] ?? '');
        if ($novo !== '' && !in_array($novo, $lista)) {
            $lista[] = $novo;
            update_option($option, array_values($lista));
        }
    }

    if ($action === 'remover') {
        $index = intval($_POST['bebelume_item_index'] ?? -1);
        if (isset($lista[$index])) {
            $item_nome = $lista[$index];
            array_splice($lista, $index, 1);
            update_option($option, array_values($lista));
            // Remover imagem associada
            bebelume_delete_item_image($tipo, $item_nome);
        }
    }

    if ($action === 'editar') {
        $index = intval($_POST['bebelume_item_index'] ?? -1);
        $valor = sanitize_text_field($_POST['bebelume_item_valor'] ?? '');
        if (isset($lista[$index]) && $valor !== '') {
            $old_nome = $lista[$index];
            $lista[$index] = $valor;
            update_option($option, array_values($lista));
            
            // Se mudou o nome, atualizar referência da imagem
            if ($old_nome !== $valor) {
                $image = bebelume_get_item_image($tipo, $old_nome);
                if ($image) {
                    bebelume_delete_item_image($tipo, $old_nome);
                    bebelume_set_item_image($tipo, $valor, $image['id']);
                }
            }
        }
    }
    
    if ($action === 'salvar_imagem') {
        $item_nome = sanitize_text_field($_POST['bebelume_item_nome'] ?? '');
        $attachment_id = intval($_POST['bebelume_attachment_id'] ?? 0);
        
        if ($item_nome && $attachment_id) {
            bebelume_set_item_image($tipo, $item_nome, $attachment_id);
        }
    }
    
    if ($action === 'remover_imagem') {
        $item_nome = sanitize_text_field($_POST['bebelume_item_nome'] ?? '');
        if ($item_nome) {
            bebelume_delete_item_image($tipo, $item_nome);
        }
    }

    wp_redirect(admin_url('admin.php?page=bebelume-pedagogico&saved=1&tipo=' . $tipo));
    exit;
}
add_action('admin_post_bebelume_pedagogico', 'bebelume_pedagogico_handle_actions');

// ──────────────────────────────────────────────
// Renderiza a página de admin
// ──────────────────────────────────────────────
function bebelume_pedagogico_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Você não tem permissão para acessar esta página.', 'bebelume-arteduca'));
    }

    $direitos = bebelume_get_direitos();
    $campos   = bebelume_get_campos();
    $saved    = isset($_GET['saved']) ? intval($_GET['saved']) : 0;

    bebelume_pedagogico_admin_styles();
    ?>
    <div class="wrap">
        <h1 style="margin-bottom:20px;">🎓 <?php esc_html_e('Bebelume Pedagógico', 'bebelume-arteduca'); ?></h1>
        <p style="color:#666;margin-bottom:30px;">
            <?php esc_html_e('Gerencie os Direitos de Aprendizagem e Campos de Experiência disponíveis para marcação nos posts.', 'bebelume-arteduca'); ?>
        </p>

        <?php if ($saved): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('Alterações salvas com sucesso!', 'bebelume-arteduca'); ?></p>
            </div>
        <?php endif; ?>

        <div class="bebelume-crud-container">
            <?php
            bebelume_render_crud_card('direitos', __('Direitos de Aprendizagem', 'bebelume-arteduca'), $direitos, '#4CAF50');
            bebelume_render_crud_card('campos', __('Campos de Experiência', 'bebelume-arteduca'), $campos, '#2196F3');
            ?>
        </div>
    </div>
    <?php
}

// ──────────────────────────────────────────────
// Estilos da interface
// ──────────────────────────────────────────────
function bebelume_pedagogico_admin_styles() {
    ?>
    <style>
        .bebelume-crud-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 30px;
            margin-top: 20px;
        }
        .bebelume-crud-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .bebelume-crud-card h2 {
            margin: 0 0 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--cor, #999);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .bebelume-badge {
            background: var(--cor, #999);
            color: #fff;
            border-radius: 12px;
            padding: 2px 9px;
            font-size: 13px;
            font-weight: normal;
        }
        .bebelume-crud-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .bebelume-crud-list li {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
            min-height: 44px;
        }
        .bebelume-crud-list li:hover {
            background: #f9f9f9;
        }
        .bebelume-item-label {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .bebelume-item-image {
            width: 32px;
            height: 32px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .bebelume-inline-edit {
            flex: 1;
            display: none;
            gap: 6px;
        }
        .bebelume-inline-edit input[type="text"] {
            flex: 1;
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .bebelume-btn {
            border: none;
            background: #f0f0f0;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        .bebelume-btn:hover {
            background: #e0e0e0;
        }
        .bebelume-btn-save {
            background: #4CAF50;
            color: #fff;
        }
        .bebelume-btn-save:hover {
            background: #45a049;
        }
        .bebelume-btn-cancel {
            background: #f44336;
            color: #fff;
        }
        .bebelume-btn-cancel:hover {
            background: #da190b;
        }
        .bebelume-btn-remove {
            background: #ffebee;
        }
        .bebelume-btn-remove:hover {
            background: #ffcdd2;
        }
        .bebelume-btn-image {
            background: #E3F2FD;
            font-size: 18px;
        }
        .bebelume-btn-image:hover {
            background: #BBDEFB;
        }
        .bebelume-btn-image.has-image {
            background: #C8E6C9;
        }
        .bebelume-add-form {
            display: flex;
            gap: 8px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #f0f0f0;
        }
        .bebelume-add-form input[type="text"] {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .bebelume-btn-add {
            background: var(--cor, #4CAF50);
            color: #fff;
            font-weight: 600;
        }
        .bebelume-btn-add:hover {
            opacity: 0.9;
        }
    </style>

    <script>
    function bebelume_toggleEdit(btn, index, tipo) {
        var li = btn.closest('li');
        li.querySelector('.bebelume-item-label').style.display =
            li.querySelector('.bebelume-item-label').style.display === 'none' ? 'flex' : 'none';
        var editArea = li.querySelector('.bebelume-inline-edit');
        editArea.style.display = editArea.style.display === 'flex' ? 'none' : 'flex';
    }
    
    function bebelume_openMediaUploader(itemNome, tipo) {
        var frame = wp.media({
            title: 'Selecionar Imagem para ' + itemNome,
            button: { text: 'Usar esta imagem' },
            multiple: false
        });
        
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            bebelume_saveImage(itemNome, tipo, attachment.id, attachment.url);
        });
        
        frame.open();
    }
    
    function bebelume_saveImage(itemNome, tipo, attachmentId, url) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?php echo admin_url('admin-post.php'); ?>';
        
        var fields = {
            'action': 'bebelume_pedagogico',
            'bebelume_pedagogico_nonce': '<?php echo wp_create_nonce('bebelume_pedagogico_action'); ?>',
            'bebelume_action': 'salvar_imagem',
            'bebelume_tipo': tipo,
            'bebelume_item_nome': itemNome,
            'bebelume_attachment_id': attachmentId
        };
        
        for (var key in fields) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = fields[key];
            form.appendChild(input);
        }
        
        document.body.appendChild(form);
        form.submit();
    }
    
    function bebelume_removeImage(itemNome, tipo) {
        if (!confirm('Remover imagem de ' + itemNome + '?')) return;
        
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?php echo admin_url('admin-post.php'); ?>';
        
        var fields = {
            'action': 'bebelume_pedagogico',
            'bebelume_pedagogico_nonce': '<?php echo wp_create_nonce('bebelume_pedagogico_action'); ?>',
            'bebelume_action': 'remover_imagem',
            'bebelume_tipo': tipo,
            'bebelume_item_nome': itemNome
        };
        
        for (var key in fields) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = fields[key];
            form.appendChild(input);
        }
        
        document.body.appendChild(form);
        form.submit();
    }
    </script>
    <?php
}

// ──────────────────────────────────────────────
// Helper: renderiza um card CRUD com suporte a imagens
// ──────────────────────────────────────────────
function bebelume_render_crud_card($tipo, $titulo, $lista, $cor) {
    $action_url = admin_url('admin-post.php');
    $count = count($lista);
    $images = bebelume_get_all_images($tipo);
    ?>
    <div class="bebelume-crud-card" style="--cor:<?php echo esc_attr($cor); ?>">
        <h2>
            <?php echo esc_html($titulo); ?>
            <span class="bebelume-badge"><?php echo $count; ?></span>
        </h2>

        <ul class="bebelume-crud-list">
            <?php foreach ($lista as $i => $item): 
                $image = $images[$item] ?? null;
            ?>
            <li>
                <span class="bebelume-item-label">
                    <span style="color:#999;font-size:12px;margin-right:6px;"><?php echo ($i + 1); ?>.</span>
                    <?php if ($image): ?>
                        <img src="<?php echo esc_url($image['url']); ?>" class="bebelume-item-image" alt="<?php echo esc_attr($item); ?>">
                    <?php endif; ?>
                    <?php echo esc_html($item); ?>
                </span>

                <!-- Edição inline -->
                <span class="bebelume-inline-edit">
                    <form method="post" action="<?php echo esc_url($action_url); ?>" style="display:flex;gap:6px;width:100%;">
                        <input type="hidden" name="action" value="bebelume_pedagogico">
                        <input type="hidden" name="bebelume_pedagogico_nonce" value="<?php echo wp_create_nonce('bebelume_pedagogico_action'); ?>">
                        <input type="hidden" name="bebelume_action" value="editar">
                        <input type="hidden" name="bebelume_tipo" value="<?php echo esc_attr($tipo); ?>">
                        <input type="hidden" name="bebelume_item_index" value="<?php echo $i; ?>">
                        <input type="text" name="bebelume_item_valor" value="<?php echo esc_attr($item); ?>" required>
                        <button type="submit" class="bebelume-btn bebelume-btn-save">✔</button>
                        <button type="button" class="bebelume-btn bebelume-btn-cancel"
                            onclick="bebelume_toggleEdit(this, <?php echo $i; ?>, '<?php echo esc_js($tipo); ?>')">✕</button>
                    </form>
                </span>

                <!-- Botão de imagem -->
                <button type="button" 
                    class="bebelume-btn bebelume-btn-image <?php echo $image ? 'has-image' : ''; ?>"
                    onclick="bebelume_openMediaUploader('<?php echo esc_js($item); ?>', '<?php echo esc_js($tipo); ?>')"
                    title="<?php echo $image ? 'Alterar imagem' : 'Adicionar imagem'; ?>">
                    🖼️
                </button>
                
                <?php if ($image): ?>
                <button type="button" 
                    class="bebelume-btn bebelume-btn-remove"
                    onclick="bebelume_removeImage('<?php echo esc_js($item); ?>', '<?php echo esc_js($tipo); ?>')"
                    title="Remover imagem">
                    🗑️ img
                </button>
                <?php endif; ?>

                <!-- Ações -->
                <button type="button" class="bebelume-btn bebelume-btn-edit"
                    onclick="bebelume_toggleEdit(this, <?php echo $i; ?>, '<?php echo esc_js($tipo); ?>')">✏️</button>

                <form method="post" action="<?php echo esc_url($action_url); ?>" style="margin:0;"
                    onsubmit="return confirm('<?php esc_attr_e('Remover este item?', 'bebelume-arteduca'); ?>')">
                    <input type="hidden" name="action" value="bebelume_pedagogico">
                    <input type="hidden" name="bebelume_pedagogico_nonce" value="<?php echo wp_create_nonce('bebelume_pedagogico_action'); ?>">
                    <input type="hidden" name="bebelume_action" value="remover">
                    <input type="hidden" name="bebelume_tipo" value="<?php echo esc_attr($tipo); ?>">
                    <input type="hidden" name="bebelume_item_index" value="<?php echo $i; ?>">
                    <button type="submit" class="bebelume-btn bebelume-btn-remove">🗑</button>
                </form>
            </li>
            <?php endforeach; ?>
        </ul>

        <!-- Formulário adicionar -->
        <form method="post" action="<?php echo esc_url($action_url); ?>" class="bebelume-add-form">
            <input type="hidden" name="action" value="bebelume_pedagogico">
            <input type="hidden" name="bebelume_pedagogico_nonce" value="<?php echo wp_create_nonce('bebelume_pedagogico_action'); ?>">
            <input type="hidden" name="bebelume_action" value="adicionar">
            <input type="hidden" name="bebelume_tipo" value="<?php echo esc_attr($tipo); ?>">
            <input type="text" name="bebelume_novo_item"
                placeholder="<?php esc_attr_e('Novo item...', 'bebelume-arteduca'); ?>" required>
            <button type="submit" class="bebelume-btn bebelume-btn-add">+ Adicionar</button>
        </form>
    </div>
    <?php
}
