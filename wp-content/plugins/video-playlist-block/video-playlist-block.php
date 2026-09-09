<?php
/**
 * Arquivo: video-playlist-block.phps
 * Plugin Name: Video Playlist Block
 * Plugin URI: https://exemplo.com
 * Description: Bloco Gutenberg para criar listas de reprodução de vídeos com slider e modal + Posts de Vídeo
 * Version: 2.1.1
 * Author: Renato de Souza (renatocdesouza@gmail.com)
 * License: GPL v2 or later
 * Text Domain: video-playlist-block
 */

// Evita acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Define constantes do plugin
define('VPB_VERSION', '2.1.1');
define('VPB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VPB_PLUGIN_URL', plugin_dir_url(__FILE__));
require_once VPB_PLUGIN_DIR . 'includes/class-pmpro-integration.php';

// Verifica versão mínima do WordPress
function vpb_check_version() {
    if (version_compare(get_bloginfo('version'), '5.0', '<')) {
        add_action('admin_notices', 'vpb_version_notice');
        deactivate_plugins(plugin_basename(__FILE__));
        return false;
    }
    return true;
}

function vpb_version_notice() {
    ?>
    <div class="notice notice-error">
        <p><strong>Video Playlist Block</strong> requer WordPress 5.0 ou superior. Por favor, atualize seu WordPress.</p>
    </div>
    <?php
}

// Não inicializa se a versão for incompatível
if (!vpb_check_version()) {
    return;
}

class Video_Playlist_Block {

    public function __construct() {
        // ✅ FRONTEND: Sempre carrega
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('wp_footer', array($this, 'render_access_denied_modal'), 999);
        
        // ✅ POSTS DE VÍDEO: Sempre carrega em posts
        add_action('add_meta_boxes', array($this, 'add_video_metaboxes'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_video_admin_assets'));
        add_action('current_screen', array($this, 'remove_editor_for_video_posts')); // ✅ Remove editor cedo
        add_action('admin_head', array($this, 'hide_editor_for_video_posts'));
        add_filter('wp_insert_post_data', array($this, 'force_video_post_content'), 10, 2);
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // ⚠️ BLOCO GUTENBERG: Só carrega se NÃO for post de blog
        if ($this->should_load_block()) {
            add_action('init', array($this, 'register_block'));
            add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));
        }
    }

    /**
     * 🎯 Verifica se deve carregar o BLOCO GUTENBERG de Playlist
     * 
     * REGRA: Bloco de playlist só aparece em PÁGINAS, não em POSTS de blog
     * (Metaboxes e sistema de posts de vídeo sempre carregam)
     * 
     * @return bool True se deve carregar o bloco, False se não deve
     */
    private function should_load_block() {
        // No frontend, sempre carrega (para exibir playlists em posts/páginas)
        if (!is_admin()) {
            return true;
        }
        
        // No admin, verifica qual tela estamos
        global $pagenow;
        
        // Se não está em tela de edição, carrega normalmente
        if (!in_array($pagenow, array('post.php', 'post-new.php'))) {
            return true;
        }
        
        // Pega o tipo de post que está sendo editado
        $post_type = '';
        
        if ($pagenow === 'post-new.php') {
            // Post novo - verifica o parâmetro post_type
            $post_type = isset($_GET['post_type']) ? $_GET['post_type'] : 'post';
        } elseif ($pagenow === 'post.php') {
            // Post existente - busca pelo ID
            $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
            if ($post_id) {
                $post_type = get_post_type($post_id);
            }
        }
        
        // 🎯 REGRA: Bloco de playlist só aparece em PÁGINAS
        // Se for POST de blog, NÃO carrega o bloco (mas metaboxes SIM)
        if ($post_type === 'post') {
            return false; // É um post de blog - NÃO carrega o BLOCO
        }
        
        // É página ou outro tipo - carrega o bloco normalmente
        return true;
    }
            
    /**
     * Adiciona metaboxes para posts de vídeo
     */
    public function add_video_metaboxes() {
        add_meta_box(
            'vpb_content_type',
            'Tipo de Conteúdo',
            array($this, 'render_content_type_metabox'),
            'post',
            'side',
            'high'
        );
        
        add_meta_box(
            'vpb_video_fields',
            'Campos do Vídeo',
            array($this, 'render_video_fields_metabox'),
            'post',
            'normal',
            'high'
        );
    }
    
    /**
     * Renderiza metabox de tipo de conteúdo
     */
    public function render_content_type_metabox($post) {
        wp_nonce_field('vpb_content_type_nonce', 'vpb_content_type_nonce_field');
        
        $content_type = get_post_meta($post->ID, '_vpb_content_type', true);
        if (empty($content_type)) {
            $content_type = 'padrao';
        }
        ?>
        <div class="vpb-content-type-wrap">
            <label class="vpb-radio-label">
                <input type="radio" name="vpb_content_type" value="padrao" <?php checked($content_type, 'padrao'); ?>>
                <span>Padrão</span>
            </label>
            <label class="vpb-radio-label">
                <input type="radio" name="vpb_content_type" value="video" <?php checked($content_type, 'video'); ?>>
                <span>Vídeo</span>
            </label>
        </div>
        <?php
    }
    
    /**
     * Renderiza metabox de campos do vídeo
     */
    public function render_video_fields_metabox($post) {
        $content_type = get_post_meta($post->ID, '_vpb_content_type', true);
        $video_url = get_post_meta($post->ID, '_vpb_video_url', true);
        $video_description = get_post_meta($post->ID, '_vpb_video_description', true);
        
        $display = ($content_type === 'video') ? 'block' : 'none';
        ?>
        <div id="vpb-video-fields" style="display: <?php echo $display; ?>;">
            
            <!-- Campo de Título -->
            <div class="vpb-field vpb-title-field">
                <label for="vpb_post_title">
                    <strong>Título do Vídeo *</strong>
                    <span class="description">(Obrigatório)</span>
                </label>
                <input 
                    type="text" 
                    id="vpb_post_title" 
                    name="post_title" 
                    value="<?php echo esc_attr($post->post_title); ?>" 
                    class="widefat"
                    placeholder="Digite o título do vídeo..."
                >
            </div>
            
            <div class="vpb-field">
                <label for="vpb_video_url">
                    <strong>URL do Vídeo *</strong>
                    <span class="description">(Obrigatório para posts de vídeo)</span>
                </label>
                <input 
                    type="url" 
                    id="vpb_video_url" 
                    name="vpb_video_url" 
                    value="<?php echo esc_attr($video_url); ?>" 
                    class="widefat vpb-video-url-input"
                    placeholder="https://www.youtube.com/watch?v=... ou https://vimeo.com/..."
                >
                <p class="description">Cole a URL do YouTube, Vimeo ou outro serviço de vídeo</p>
            </div>
            
            <!-- Preview do vídeo -->
            <div id="vpb-video-preview" class="vpb-video-preview">
                <?php if (!empty($video_url)): ?>
                    <?php echo $this->get_video_embed($video_url); ?>
                <?php endif; ?>
            </div>
            
            <div class="vpb-field">
                <label for="vpb_video_description">
                    <strong>Descrição</strong>
                    <span class="description">(Opcional)</span>
                </label>
                <textarea 
                    id="vpb_video_description" 
                    name="vpb_video_description" 
                    rows="5" 
                    class="widefat"
                    placeholder="Adicione uma descrição para o vídeo..."
                ><?php echo esc_textarea($video_description); ?></textarea>
            </div>
            
        </div>
        <?php
    }
    
    /**
     * Renderiza metabox do PMPro
     */
    
    /**
     * Gera embed do vídeo baseado na URL
     */
    private function get_video_embed($url) {
        if (empty($url)) {
            return '';
        }
        
        $embed = '';
        
        // YouTube
        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches)) {
            $video_id = $matches[1];
            $embed = sprintf(
                '<iframe width="100%%" height="400" src="https://www.youtube.com/embed/%s" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>',
                esc_attr($video_id)
            );
        }
        // Vimeo
        elseif (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
            $video_id = $matches[1];
            $embed = sprintf(
                '<iframe src="https://player.vimeo.com/video/%s" width="100%%" height="400" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>',
                esc_attr($video_id)
            );
        }
        // Outros vídeos diretos
        elseif (preg_match('/\.(mp4|webm|ogg)$/i', $url)) {
            $embed = sprintf(
                '<video controls width="100%%" height="400"><source src="%s" type="video/mp4">Seu navegador não suporta vídeo HTML5.</video>',
                esc_url($url)
            );
        }
        
        if (!empty($embed)) {
            return '<div class="vpb-embed-container">' . $embed . '</div>';
        }
        
        return '<div class="vpb-no-preview"><p>⚠️ Preview não disponível para esta URL. O vídeo será exibido no frontend.</p></div>';
    }
    
    /**
     * Salva metadados do post
     */
    public function save_video_meta($post_id) {
        // Verifica nonce
        if (!isset($_POST['vpb_content_type_nonce_field']) || 
            !wp_verify_nonce($_POST['vpb_content_type_nonce_field'], 'vpb_content_type_nonce')) {
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
        
        // Salva tipo de conteúdo
        if (isset($_POST['vpb_content_type'])) {
            $content_type = sanitize_text_field($_POST['vpb_content_type']);
            update_post_meta($post_id, '_vpb_content_type', $content_type);
        }
        
        // Salva URL do vídeo
        if (isset($_POST['vpb_video_url'])) {
            update_post_meta($post_id, '_vpb_video_url', esc_url_raw($_POST['vpb_video_url']));
        }
        
        // Salva descrição
        if (isset($_POST['vpb_video_description'])) {
            update_post_meta($post_id, '_vpb_video_description', wp_kses_post($_POST['vpb_video_description']));
        }

        // Se for vídeo e o post estiver vazio, adiciona conteúdo automático
        if (isset($_POST['vpb_content_type']) && $_POST['vpb_content_type'] === 'video') {
            $post_content = get_post_field('post_content', $post_id);
            
            if (empty(trim($post_content))) {
                // Atualiza o post com conteúdo placeholder
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_content' => '<!-- wp:paragraph --><p>Post de vídeo</p><!-- /wp:paragraph -->'
                ));
            }
        }
    }
    
    /**
     * Enfileira assets do admin
     */
    public function enqueue_video_admin_assets($hook) {
        // Só na tela de edição de posts
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        
        global $post;
        if (!$post) {
            return;
        }
        
        // CSS inline
        wp_add_inline_style('wp-admin', $this->get_video_admin_css());
        
        // JavaScript inline
        wp_add_inline_script('jquery', $this->get_video_admin_js());
    }
    
    /**
     * CSS do admin
     */
    private function get_video_admin_css() {
        return "
        /* Tipo de Conteúdo */
        .vpb-content-type-wrap {
            padding: 10px 0;
        }
        
        .vpb-radio-label {
            display: block;
            padding: 10px;
            margin: 5px 0;
            cursor: pointer;
            border: 2px solid #ddd;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .vpb-radio-label:hover {
            border-color: #2271b1;
            background: #f0f6fc;
        }
        
        .vpb-radio-label input[type='radio'] {
            margin-right: 8px;
        }
        
        .vpb-radio-label span {
            font-weight: 600;
        }

        #vpb-video-fields {
            padding: 0 10em;
        }
        
        /* Campos do Vídeo */
        #vpb-video-fields .vpb-field {
            margin-bottom: 25px;
        }
        
        /* Campo de Título - Destaque */
        #vpb-video-fields .vpb-title-field {
            background: #f0f6fc;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #2271b1;
            margin-bottom: 30px;
        }
        
        #vpb-video-fields .vpb-title-field input[type='text'] {
            font-size: 1.7em;
            font-weight: 600;
            line-height: 1.4;
            padding: 10px;
        }
        
        #vpb-video-fields label {
            display: block;
            margin-bottom: 8px;
        }
        
        #vpb-video-fields label .description {
            font-weight: normal;
            color: #646970;
            font-size: 12px;
        }
        
        #vpb-video-fields input[type='url'],
        #vpb-video-fields textarea {
            font-size: 14px;
        }
        
        #vpb-video-fields .description {
            margin-top: 5px;
            color: #646970;
            font-style: italic;
        }
        
        /* Preview do Vídeo */
        .vpb-video-preview {
            margin: 20px 0;
            padding: 15px;
            background: #f0f0f1;
            border-radius: 4px;
            display: none;
        }
        
        .vpb-video-preview.has-video {
            display: block;
        }
        
        .vpb-embed-container {
            position: relative;
            width: 100%;
            max-width: 100%;
        }
        
        .vpb-embed-container iframe,
        .vpb-embed-container video {
            border-radius: 4px;
        }
        
        .vpb-no-preview {
            text-align: center;
            padding: 30px;
            color: #d63638;
        }
        
        /* Info Box */
        .vpb-info-box {
            padding: 15px;
            background: #e5f5ff;
            border-left: 4px solid #0073aa;
            border-radius: 4px;
            margin-top: 20px;
        }
        
        /* Esconder editor quando for tipo vídeo - SELETORES ESPECÍFICOS */
        body.vpb-video-type #postdivrich,
        body.vpb-video-type .block-editor__typewriter,
        body.vpb-video-type .block-editor-writing-flow,
        body.vpb-video-type .editor-styles-wrapper,
        body.vpb-video-type .edit-post-visual-editor,
        body.vpb-video-type .block-editor-block-list__layout,
        body.vpb-video-type .block-editor-default-block-appender,
        body.vpb-video-type .is-root-container {
            display: none !important;
        }
        
        /* NÃO esconder o .editor-visual-editor para manter o título visível */
        /* Esconder apenas o conteúdo do editor (block-list) dentro dele */
        body.vpb-video-type .editor-visual-editor .block-editor-block-list__layout {
            display: none !important;
        }
        
        /* Manter visível a estrutura da interface */
        body.vpb-video-type .interface-interface-skeleton__content {
            display: block !important;
        }
        
        /* Esconder apenas o conteúdo visual do editor dentro do skeleton */
        body.vpb-video-type .interface-interface-skeleton__content .editor-visual-editor {
            display: none !important;
        }
        
        /* Garante que o metabox de vídeo E SEU CONTEÚDO apareçam quando for tipo vídeo */
        body.vpb-video-type #vpb_video_fields,
        body.vpb-video-type #vpb-video-fields {
            display: block !important;
        }
        
        /* IMPORTANTE: Força exibição de todos os elementos internos */
        body.vpb-video-type #vpb-video-fields > *,
        body.vpb-video-type #vpb-video-fields .vpb-field,
        body.vpb-video-type #vpb-video-fields .vpb-info-box {
            display: block !important;
        }
        
        /* Esconde metabox de vídeo quando for tipo padrão */
        body.vpb-padrao-type #vpb_video_fields,
        body.vpb-padrao-type #vpb-video-fields {
            display: none !important;
        }
        ";
    }
    
    /**
     * JavaScript do admin
     */
    private function get_video_admin_js() {
        return "
        jQuery(document).ready(function($) {
            
            // Função para alternar entre tipos de conteúdo
            function toggleContentType() {
                var contentType = $('input[name=\"vpb_content_type\"]:checked').val();
                var isVideo = (contentType === 'video');
                
                // Remove classes anteriores
                $('body').removeClass('vpb-video-type vpb-padrao-type');
                
                if (isVideo) {
                    // Adiciona classe para tipo vídeo
                    $('body').addClass('vpb-video-type');
                    
                    // 🔥 ESCONDE O EDITOR GUTENBERG COMPLETAMENTE
                    $('#postdivrich').hide();
                    $('.block-editor-writing-flow').hide();
                    $('.editor-styles-wrapper').hide();
                    $('.edit-post-visual-editor').hide();
                    $('.block-editor-block-list__layout').hide();
                    $('.block-editor-default-block-appender').hide();
                    $('.is-root-container').hide();
                    $('.interface-interface-skeleton__content').hide();
                    $('.edit-post-layout__content').hide();
                    
                    // Mostra campos de vídeo
                    $('#vpb-video-fields').attr('style', 'display: block !important');
                    $('#vpb_video_fields').attr('style', 'display: block !important');
                    
                    // Sincroniza título do WordPress com o campo no metabox
                    var wpTitle = $('#title').val() || $('.editor-post-title__input').text();
                    if (wpTitle && !$('#vpb_post_title').val()) {
                        $('#vpb_post_title').val(wpTitle);
                    }
                    
                } else {
                    // Adiciona classe para tipo padrão
                    $('body').addClass('vpb-padrao-type');
                    
                    // Mostra elementos do editor
                    $('#postdivrich').show();
                    $('.block-editor-writing-flow').show();
                    $('.editor-styles-wrapper').show();
                    $('.edit-post-visual-editor').show();
                    $('.block-editor-block-list__layout').show();
                    $('.block-editor-default-block-appender').show();
                    $('.is-root-container').show();
                    $('.interface-interface-skeleton__content').show();
                    $('.edit-post-layout__content').show();
                    
                    // Esconde campos de vídeo
                    $('#vpb-video-fields').hide();
                    $('#vpb_video_fields').hide();
                }
            }
            
            // ⏱️ Executa com delay para dar tempo do Gutenberg carregar
            setTimeout(toggleContentType, 200);
            setTimeout(toggleContentType, 500); // Segunda tentativa
            
            // Executa ao mudar o tipo
            $('input[name=\"vpb_content_type\"]').on('change', function() {
                toggleContentType();
                // Executa novamente após delay para garantir
                setTimeout(toggleContentType, 100);
            });
            
            // Sincroniza título do metabox com o título nativo do WordPress
            $('#vpb_post_title').on('input', function() {
                var title = $(this).val();
                
                // Atualiza título no editor clássico
                $('#title').val(title);
                
                // Atualiza título no Gutenberg
                var gutenbergTitle = $('.editor-post-title__input');
                if (gutenbergTitle.length) {
                    gutenbergTitle.text(title);
                }
            });

            // Habilita botão publicar quando preencher URL do vídeo
            $('#vpb_video_url').on('input', function() {
                var videoUrl = $(this).val().trim();
                var postTitle = $('#vpb_post_title').val().trim();
                
                // Se tiver título E URL, habilita o botão
                if (videoUrl && postTitle) {
                    $('#publish').removeClass('disabled').attr('aria-disabled', 'false');
                    
                    // Gutenberg
                    $('.editor-post-publish-button').prop('disabled', false).removeClass('is-busy');
                    
                    // Força atualização do estado do editor
                    if (window.wp && window.wp.data) {
                        window.wp.data.dispatch('core/editor').editPost({ content: '<!-- wp:paragraph --><p>Post de vídeo</p><!-- /wp:paragraph -->' });
                    }
                }
            });

            // Carrega preview automaticamente se já tiver URL ao carregar a página
            $(document).ready(function() {
                var existingUrl = $('#vpb_video_url').val().trim();
                if (existingUrl) {
                    var previewContainer = $('#vpb-video-preview');
                    updateVideoPreview(existingUrl, previewContainer);
                }
            });
            
            // Também monitora o título
            $('#vpb_post_title').on('input', function() {
                $('#vpb_video_url').trigger('input'); // Revalida
            });
            
            // Preview do vídeo ao mudar URL
            var previewTimeout;
            $('#vpb_video_url').on('input', function() {
                clearTimeout(previewTimeout);
                var url = $(this).val().trim();
                var previewContainer = $('#vpb-video-preview');
                
                if (!url) {
                    previewContainer.removeClass('has-video').html('');
                    return;
                }
                
                // Delay para não fazer muitas requisições
                previewTimeout = setTimeout(function() {
                    updateVideoPreview(url, previewContainer);
                }, 800);
            });
            
            // Função para atualizar preview
            function updateVideoPreview(url, container) {
                var embed = '';
                
                // YouTube
                var youtubeMatch = url.match(/(?:youtube\\.com\\/(?:[^\\/]+\\/.+\\/|(?:v|e(?:mbed)?)\\/|.*[?&]v=)|youtu\\.be\\/)([^\"&?\\/\\s]{11})/);
                if (youtubeMatch) {
                    var videoId = youtubeMatch[1];
                    embed = '<div class=\"vpb-embed-container\"><iframe width=\"100%\" height=\"400\" src=\"https://www.youtube.com/embed/' + videoId + '\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe></div>';
                }
                
                // Vimeo (tenta extrair ID ou usa diretamente como MP4)
                if (!embed && url.includes('vimeo.com/progressive_redirect')) {
                    embed = '<div class=\"vpb-embed-container\"><video controls width=\"100%\" height=\"400\"><source src=\"' + url + '\" type=\"video/mp4\">Seu navegador não suporta vídeo HTML5.</video></div>';
                } else if (!embed) {
                    var vimeoMatch = url.match(/vimeo\\.com\\/(?:video\\/)?(\\d+)/);
                    if (vimeoMatch) {
                        var videoId = vimeoMatch[1];
                        embed = '<div class=\"vpb-embed-container\"><iframe src=\"https://player.vimeo.com/video/' + videoId + '\" width=\"100%\" height=\"400\" frameborder=\"0\" allow=\"autoplay; fullscreen; picture-in-picture\" allowfullscreen></iframe></div>';
                    }
                }
                
                // Vídeo direto (MP4, WebM, etc)
                if (!embed && url.match(/\\.(mp4|webm|ogg)/i)) {
                    embed = '<div class=\"vpb-embed-container\"><video controls width=\"100%\" height=\"400\"><source src=\"' + url + '\" type=\"video/mp4\">Seu navegador não suporta vídeo HTML5.</video></div>';
                }
                
                if (embed) {
                    container.addClass('has-video').html(embed);
                } else {
                    container.addClass('has-video').html('<div class=\"vpb-no-preview\"><p>⚠️ Preview não disponível para esta URL. O vídeo será exibido no frontend.</p></div>');
                }
            }
            
            // Validação ao publicar/atualizar
            $('#publish, #save-post').on('click', function(e) {
                var contentType = $('input[name=\"vpb_content_type\"]:checked').val();
                
                if (contentType === 'video') {
                    var postTitle = $('#vpb_post_title').val().trim();
                    var videoUrl = $('#vpb_video_url').val().trim();
                    
                    // Valida título
                    if (!postTitle) {
                        e.preventDefault();
                        alert('⚠️ O título do vídeo é obrigatório!');
                        $('#vpb_post_title').focus().css('border-color', '#d63638');
                        return false;
                    }
                    
                    // Valida URL
                    if (!videoUrl) {
                        e.preventDefault();
                        alert('⚠️ A URL do vídeo é obrigatória para posts do tipo Vídeo!');
                        $('#vpb_video_url').focus().css('border-color', '#d63638');
                        return false;
                    }
                }
            });
            
        });
        ";
    }
    
    /**
     * Remove suporte ao editor para posts de vídeo (chamado cedo, no current_screen)
     */
    public function remove_editor_for_video_posts($current_screen) {
        // Só atua na tela de edição de posts
        if (!$current_screen || $current_screen->post_type !== 'post') {
            return;
        }
        
        if ($current_screen->base !== 'post') {
            return;
        }
        
        global $post;
        if (!$post) {
            // Tenta pegar o ID do post da URL
            $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
            if (!$post_id) {
                return;
            }
            $post = get_post($post_id);
            if (!$post) {
                return;
            }
        }
        
        $content_type = get_post_meta($post->ID, '_vpb_content_type', true);
        
        if ($content_type === 'video') {
            // ✅ Remove suporte ao editor ANTES do Gutenberg carregar
            remove_post_type_support('post', 'editor');
        }
    }
    
    /**
     * Esconde o editor para posts de vídeo ao carregar
     */
    public function hide_editor_for_video_posts() {
        global $post, $pagenow;
        
        // Só executa na tela de edição de posts
        if ($pagenow !== 'post.php' && $pagenow !== 'post-new.php') {
            return;
        }
        
        if (!$post || get_post_type($post) !== 'post') {
            return;
        }
        
        $content_type = get_post_meta($post->ID, '_vpb_content_type', true);
        
        if ($content_type === 'video') {
            // ✅ CSS para layout limpo e organizado sem o editor
            echo '<style>
                /* Esconde editor clássico (fallback) */
                #postdivrich { display: none !important; }
                
                /* Esconde o conteúdo principal do editor Gutenberg */
                .edit-post-layout .edit-post-layout__content,
                #post-body-content {
                    display: none !important;
                }
                
                /* Ajusta layout geral do post */
                #poststuff {
                    padding-top: 20px;
                }
                
                /* Layout de 2 colunas: sidebar à direita */
                #post-body.columns-2 {
                    margin-right: 300px !important;
                }
                
                #post-body.columns-2 #postbox-container-1 {
                    float: right !important;
                    margin-right: -300px !important;
                    width: 280px !important;
                }
                
                #post-body.columns-2 #postbox-container-2 {
                    float: none !important;
                    width: 100% !important;
                    margin-right: 0 !important;
                }
                
                /* Metaboxes de vídeo com largura controlada */
                #vpb_video_fields .inside {
                    padding: 12px !important;
                }
                
                #vpb_video_fields .vpb-field {
                    margin-bottom: 20px;
                }
                
                #vpb_video_fields input[type="text"],
                #vpb_video_fields input[type="url"],
                #vpb_video_fields textarea {
                    width: 100%;
                    max-width: 100%;
                }
                
                /* Preview do vídeo com tamanho controlado */
                #vpb-video-preview {
                    max-width: 640px;
                    margin: 15px 0;
                }
                
                #vpb-video-preview iframe,
                #vpb-video-preview video {
                    max-width: 100%;
                    height: auto;
                }
                
                /* Garante visibilidade dos metaboxes */
                #vpb-video-fields,
                #vpb_video_fields,
                #vpb_content_type {
                    display: block !important;
                }
                
                /* Remove espaços desnecessários */
                .edit-post-layout__metaboxes {
                    margin-top: 0 !important;
                }
                
                /* Esconde mensagens sobre o editor de blocos */
                .block-editor-warning {
                    display: none !important;
                }
                
                /* Ajusta layout */
                .edit-post-layout__metaboxes {
                    margin-top: 20px;
                }
                
                /* Esconde o conteúdo principal do editor */
                .edit-post-layout .edit-post-layout__content {
                    display: none !important;
                }
            </style>';
            
            // JavaScript para garantir compatibilidade
            echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    document.body.classList.add("vpb-video-type");
                });
            </script>';
        } else {
            // Tipo padrão - esconde campos de vídeo
            echo '<style>
                #vpb-video-fields,
                #vpb_video_fields {
                    display: none !important;
                }
            </style>';
            
            echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    document.body.classList.add("vpb-padrao-type");
                });
            </script>';
        }
    }

    /**
     * Registra rotas da REST API
     */
    public function register_rest_routes() {
        register_rest_route('vpb/v1', '/video-posts', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_video_posts'),
            'permission_callback' => '__return_true'  // Permite leitura pública
        ));
    }

    /**
     * Retorna lista de posts de vídeo para o editor
     */
    public function get_video_posts($request) {
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_vpb_content_type',
                    'value' => 'video',
                    'compare' => '='
                )
            ),
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $posts = get_posts($args);
        $video_posts = array();
        
        foreach ($posts as $post) {
            $video_url = get_post_meta($post->ID, '_vpb_video_url', true);
            $video_description = get_post_meta($post->ID, '_vpb_video_description', true);
            $thumbnail_id = get_post_thumbnail_id($post->ID);
            $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium') : '';
            
            $video_posts[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'videoUrl' => $video_url,
                'thumbnail' => $thumbnail_url,
                'description' => $video_description ? $video_description : '',
                'date' => get_the_date('', $post->ID)
            );
        }
        
        return rest_ensure_response($video_posts);
    }

    /**
     * Força conteúdo mínimo para posts de vídeo
     */
    public function force_video_post_content($data, $postarr) {
        // Verifica se é um post de vídeo
        if (isset($_POST['vpb_content_type']) && $_POST['vpb_content_type'] === 'video') {
            // Se o conteúdo estiver vazio, força um conteúdo mínimo
            if (empty(trim(strip_tags($data['post_content'])))) {
                $data['post_content'] = '<!-- wp:paragraph --><p>Este é um post de vídeo.</p><!-- /wp:paragraph -->';
            }
        }
        
        return $data;
    }
    
    /**
     * Registra o bloco (código original mantido)
     */
    public function register_block() {
        if (!function_exists('register_block_type')) {
            return;
        }
        
        wp_register_script(
            'video-playlist-block-frontend',
            VPB_PLUGIN_URL . 'assets/js/frontend.js',
            array('plyr-js'),
            VPB_VERSION,
            true
        );
        
        wp_register_style(
            'video-playlist-block-style',
            VPB_PLUGIN_URL . 'assets/css/style.css',
            array(),
            VPB_VERSION
        );
        
        register_block_type('video-playlist/playlist-block', array(
            'editor_script' => 'video-playlist-block-editor',
            'editor_style' => 'video-playlist-block-editor-style',
            'style' => 'video-playlist-block-style',
            'script' => 'video-playlist-block-frontend',
            'render_callback' => array($this, 'render_block'),
            'attributes' => array(
                'playlistName' => array(
                    'type' => 'string',
                    'default' => 'Nova Lista de Reprodução'
                ),
                'playlistDescription' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'videos' => array(
                    'type' => 'array',
                    'default' => array()
                ),
                'videoPostIds' => array(
                    'type' => 'array',
                    'default' => array()
                ),
                'className' => array(
                    'type' => 'string',
                    'default' => ''
                )
            )
        ));
    }
    
    public function enqueue_editor_assets() {
        wp_enqueue_script(
            'video-playlist-block-editor',
            VPB_PLUGIN_URL . 'assets/js/editor.js',
            array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'),
            VPB_VERSION,
            true
        );
        
        wp_enqueue_style(
            'video-playlist-block-editor-style',
            VPB_PLUGIN_URL . 'assets/css/editor.css',
            array('wp-edit-blocks'),
            VPB_VERSION
        );
    }
    
    public function enqueue_frontend_assets() {
        // Bootstrap CSS
        wp_enqueue_style(
            'bootstrap-css',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
            array(),
            '5.3.2'
        );
        
        // CSS do Frontend (depende do Bootstrap)
        wp_enqueue_style(
            'video-playlist-frontend-css',
            VPB_PLUGIN_URL . 'assets/css/frontend.css',
            array('bootstrap-css'),
            VPB_VERSION
        );
            
        // Plyr CSS
        wp_enqueue_style(
            'plyr-css',
            'https://cdn.jsdelivr.net/npm/plyr@3.8.4/dist/plyr.css',
            array(),
            '3.8.4'
        );

        // Bootstrap JS (precisa carregar antes do nosso JS)
        wp_enqueue_script(
            'bootstrap-bundle-js',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js',
            array(),
            '5.3.2',
            true
        );

        // Plyr JS
        wp_enqueue_script(
            'plyr-js',
            'https://cdn.jsdelivr.net/npm/plyr@3.8.4/dist/plyr.min.js',
            array(),
            '3.8.4',
            true
        );
    }
    
    public function render_block($attributes) {
        $attributes = wp_parse_args($attributes, array(
            'playlistName' => 'Nova Lista de Reprodução',
            'playlistDescription' => '',
            'videos' => array(),
            'videoPostIds' => array(),
            'className' => ''
        ));
        
        $playlist_name = esc_html($attributes['playlistName']);
        $playlist_description = esc_html($attributes['playlistDescription']);
        $videos = $attributes['videos'];
        $video_post_ids = $attributes['videoPostIds'];
        $additional_classes = esc_attr($attributes['className']);
        
        // Se tiver posts selecionados, busca os dados
        if (!empty($video_post_ids) && is_array($video_post_ids)) {
            $videos = array();
            
            foreach ($video_post_ids as $post_id) {
                $post = get_post($post_id);
                
                if (!$post || get_post_meta($post_id, '_vpb_content_type', true) !== 'video') {
                    continue;
                }
                
                $video_url = get_post_meta($post_id, '_vpb_video_url', true);
                $video_description = get_post_meta($post_id, '_vpb_video_description', true);
                $thumbnail_id = get_post_thumbnail_id($post_id);
                $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium') : '';
                
                // DEBUG - REMOVER DEPOIS
                error_log('POST ID: ' . $post_id);
                error_log('VIDEO URL: ' . $video_url);
                error_log('THUMBNAIL: ' . $thumbnail_url);
                // FIM DEBUG
                
                // Verifica acesso PMPro
                $access_info = VPB_PMPro_Integration::check_video_access($post_id);
                
                $videos[] = array(
                    'title' => $post->post_title,
                    'thumbnail' => $thumbnail_url,
                    'videoUrl' => $video_url,
                    'description' => $video_description ? $video_description : '',
                    'postId' => $post_id,
                    'isProtected' => $access_info['is_protected'],
                    'canAccess' => $access_info['can_access'],
                    'accessMessage' => $access_info['message'],
                    'requiredLevels' => $access_info['required_levels']
                );
            }
        }
        
        if (empty($videos)) {
            return '<div class="video-playlist-empty">Nenhum vídeo adicionado ainda.</div>';
        }
        
        $unique_id = 'playlist-' . uniqid();
        
        ob_start();
        ?>
        <div class="video-playlist-container" data-playlist-id="<?php echo esc_attr($unique_id); ?>">
            <h3 class="video-playlist-title <?php echo $additional_classes; ?>"><?php echo $playlist_name; ?></h3>
            
            <?php if (!empty($playlist_description)) : ?>
                <p class="video-playlist-description"><?php echo $playlist_description; ?></p>
            <?php endif; ?>
            
            <div class="video-playlist-slider">
                <button class="video-slider-nav video-slider-prev" aria-label="Anterior">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                
                <div class="video-slider-wrapper">
                    <div class="video-slider-track">
                        <?php foreach ($videos as $index => $video) : 
                            $video = wp_parse_args($video, array(
                                'title' => 'Vídeo ' . ($index + 1),
                                'thumbnail' => '',
                                'videoUrl' => '',
                                'description' => '',
                                'postId' => 0,
                                'isProtected' => false,
                                'canAccess' => true,
                                'accessMessage' => '',
                                'requiredLevels' => array()
                            ));
                            
                            // Verifica proteção do vídeo
                            $is_protected = !empty($video['isProtected']) ? $video['isProtected'] : false;
                            $can_access = isset($video['canAccess']) ? $video['canAccess'] : true;
                            $access_message = !empty($video['accessMessage']) ? $video['accessMessage'] : '';
                            $post_id = !empty($video['postId']) ? $video['postId'] : 0;
                            
                            $thumb_class = 'video-thumb';
                            if ($is_protected && !$can_access) {
                                $thumb_class .= ' video-locked';
                            }
                            
                            $caption = '<div style="text-align:center;">';
                            $caption .= '<h2 style="margin:0 0 15px 0;font-size:24px;color:#fff;font-weight:bold;">' . esc_html($video['title']) . '</h2>';
                            if (!empty($video['description'])) {
                                $caption .= '<p style="color:#ccc;font-size:16px;line-height:1.6;margin:0;">' . esc_html($video['description']) . '</p>';
                            }
                            $caption .= '</div>';
                        ?>

                        <div class="video-slide <?php echo $additional_classes; ?>" 
                            data-video-index="<?php echo esc_attr($index); ?>" 
                            data-protected="<?php echo $is_protected ? 'true' : 'false'; ?>" 
                            data-can-access="<?php echo $can_access ? 'true' : 'false'; ?>" 
                            data-access-message="<?php echo esc_attr($access_message); ?>" 
                            data-post-id="<?php echo esc_attr($post_id); ?>"
                            data-plans-url="<?php echo esc_attr( VPB_PMPro_Integration::get_plans_url_for_levels( $video['requiredLevels'] ) ); ?>"><?php // URL da página de planos do nível exigido ?>
                                <a href="<?php echo esc_url($video['videoUrl']); ?>" 
                                data-fancybox="<?php echo !$is_protected || $can_access ? esc_attr($unique_id) : ''; ?>" 
                                data-caption="<?php echo esc_attr($caption); ?>" 
                                class="<?php echo $thumb_class; ?>">
                                           <?php if (!empty($video['thumbnail'])) : ?>
                                        <img src="<?php echo esc_url($video['thumbnail']); ?>" 
                                            alt="<?php echo esc_attr($video['title']); ?>">
                                    <?php endif; ?>
                                    <div class="video-play-overlay">
                                        <svg width="48" height="48" viewBox="0 0 48 48" fill="white">
                                            <circle cx="24" cy="24" r="24" fill="rgba(0,0,0,0.7)"/>
                                            <path d="M18 14l16 10-16 10V14z" fill="white"/>
                                        </svg>
                                    </div>
                                    <?php if ($is_protected && !$can_access) : ?>
                                        <div class="video-lock-overlay">
                                            <svg class="lock-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                            </a>
                            <div class="video-title"><?php echo esc_html($video['title']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <button class="video-slider-nav video-slider-next" aria-label="Próximo">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
        </div>
        
<?php
        
        return ob_get_clean();
    }

    /**
     * Renderiza modal de acesso negado (apenas 1 vez no footer)
     */
    public function render_access_denied_modal() {
        static $already_rendered = false;
        if ($already_rendered) {
            return;
        }
        $already_rendered = true;
        ?>
        <!-- Modal Bootstrap - Acesso Negado PMPro -->
        <div class="modal fade" id="vpb-access-denied-modal" tabindex="-1" aria-labelledby="vpbModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content vpb-modal-custom">
                    <!-- Header com botão de fechar -->
                    <div class="modal-header border-0 pb-0">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    
                    <!-- Body -->
                    <div class="modal-body text-center px-4 pb-4">
                        <!-- Ícone do cadeado -->
                        <div class="vpb-modal-icon mx-auto mb-3">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                        </div>
                        
                        <!-- Título -->
                        <h5 class="modal-title mb-3" id="vpbModalLabel">
                            🔒 Conteúdo Exclusivo
                        </h5>
                        
                        <!-- Mensagem -->
                        <p class="text-muted mb-4" id="vpb-access-message"></p>
                        
                        <!-- Botões de ação -->
                        <div class="d-grid gap-2">
                            <?php if (!is_user_logged_in()) : 
                                $redirect_urls = VPB_PMPro_Integration::get_redirect_urls();
                            ?>
                                <a href="<?php echo esc_url($redirect_urls['membership']); ?>" class="btn btn-primary btn-lg">
                                    Assinar Agora
                                </a>
                                <a href="<?php echo esc_url($redirect_urls['login']); ?>" class="btn btn-outline-secondary">
                                    Fazer Login
                                </a>
                            <?php else: 
                                $redirect_urls = VPB_PMPro_Integration::get_redirect_urls();
                            ?>
                                <a href="<?php echo esc_url($redirect_urls['membership']); ?>" class="btn btn-primary btn-lg">
                                    Ver Planos Disponíveis
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

// Inicializa o plugin
new Video_Playlist_Block();
