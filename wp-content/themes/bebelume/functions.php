<?php
/**
 * Bebelume Theme - Functions
 * 
 * @package Bebelume_Theme
 * @since 1.0.0
 */

// Evita acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

// Define constantes do tema
define('BEBELUME_VERSION', '1.0.0');
define('BEBELUME_THEME_DIR', get_template_directory());
define('BEBELUME_THEME_URI', get_template_directory_uri());

/**
 * Configuração inicial do tema
 */
function bebelume_setup() {
    // Suporte a título dinâmico
    add_theme_support('title-tag');
    
    // Suporte a logo personalizado
    add_theme_support('custom-logo', array(
        'height'      => 100,
        'width'       => 300,
        'flex-height' => true,
        'flex-width'  => true,
    ));
    
    // Suporte a imagens destacadas
    add_theme_support('post-thumbnails');
    
    // Suporte a HTML5
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));
    
    // Registra menu de navegação
    register_nav_menus(array(
        'footer-menu' => __('Menu do Rodapé', 'bebelume-theme'),
    ));
}
add_action('after_setup_theme', 'bebelume_setup');

/**
 * Registra área de widgets/blocos no footer
 */
function bebelume_register_footer_widgets() {
    register_sidebar( array(
        'name'          => 'Footer - Área de Blocos',
        'id'            => 'footer-blocks',
        'description'   => 'Adicione blocos Gutenberg no footer',
        'before_widget' => '<div class="footer-block-widget mb-3">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title mb-2">',
        'after_title'   => '</h4>',
    ) );
}
add_action( 'widgets_init', 'bebelume_register_footer_widgets' );

add_action('wp_footer', function() {
    // Só injeta o script se NÃO for a página inicial
    if ( ! is_front_page() ) {
        ?>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const block = document.querySelector('.bebelume-sobre-nos-block');
                if (block) {
                    block.remove();
                }
            });
        </script>
        <?php
    }
});

add_action('wp_footer', function() {
    // Só injeta o script se NÃO for a página inicial
    if ( ! is_front_page() ) {
        ?>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const block = document.querySelector('.sobre-nos-wrapper');
                if (block) {
                    block.remove();
                }
            });
        </script>
        <?php
    }
});

/**
 * Desabilita o editor de blocos em widgets (volta para o clássico)
 * Fix para erro: "Cannot read properties of null (reading 'endpoints')"
 */
function bebelume_disable_block_widgets() {
    remove_theme_support( 'widgets-block-editor' );
}
add_action( 'after_setup_theme', 'bebelume_disable_block_widgets' );

/**
 * Inclui arquivo de enqueue
 */
require_once BEBELUME_THEME_DIR . '/inc/enqueue.php';

/**
 * Registra blocos Gutenberg
 */
require_once BEBELUME_THEME_DIR . '/inc/register-blocks.php';




/**
 * Script: Deletar Posts de Vídeo Órfãos
 * 
 * Remove posts de vídeo que não estão vinculados a nenhuma lista de reprodução
 * 
 * COMO USAR:
 * 1. Adicionar ao functions.php do tema OU criar um plugin
 * 2. Acessar: /wp-admin/tools.php?page=vpb_delete_orphans (se criar menu)
 * 3. OU executar via WP-CLI: wp eval-file delete-orphan-videos.php
 * 4. OU executar uma vez e remover o código
 * 
 * ATENÇÃO: Este script DELETA posts permanentemente!
 * Faça backup antes de executar!
 */

// Evita acesso direto
if (!defined('ABSPATH')) {
    exit('Acesso direto não permitido.');
}

class VPB_Delete_Orphan_Videos {
    
    /**
     * Executa a limpeza de vídeos órfãos
     * 
     * @param bool $dry_run Se true, apenas lista sem deletar
     * @return array Resultado da operação
     */
    public static function run($dry_run = false) {
        $result = array(
            'total_videos' => 0,
            'videos_in_playlists' => 0,
            'orphan_videos' => 0,
            'deleted' => 0,
            'errors' => array(),
            'orphan_list' => array()
        );
        
        // 1. Buscar todos os posts de vídeo
        $all_video_posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_vpb_content_type',
                    'value' => 'video',
                    'compare' => '='
                )
            ),
            'fields' => 'ids'
        ));
        
        $result['total_videos'] = count($all_video_posts);
        
        if (empty($all_video_posts)) {
            $result['message'] = 'Nenhum post de vídeo encontrado.';
            return $result;
        }
        
        // 2. Buscar todos os IDs de vídeos que estão em playlists
        $videos_in_playlists = self::get_all_videos_in_playlists();
        $result['videos_in_playlists'] = count($videos_in_playlists);
        
        // 3. Encontrar vídeos órfãos (não estão em nenhuma playlist)
        $orphan_videos = array_diff($all_video_posts, $videos_in_playlists);
        $result['orphan_videos'] = count($orphan_videos);
        
        if (empty($orphan_videos)) {
            $result['message'] = 'Nenhum vídeo órfão encontrado! Todos os vídeos estão em playlists.';
            return $result;
        }
        
        // 4. Listar ou deletar os vídeos órfãos
        foreach ($orphan_videos as $post_id) {
            $post = get_post($post_id);
            
            if (!$post) {
                continue;
            }
            
            $video_info = array(
                'id' => $post_id,
                'title' => get_the_title($post_id),
                'url' => get_post_meta($post_id, '_vpb_video_url', true),
                'status' => get_post_status($post_id),
                'date' => get_the_date('Y-m-d H:i:s', $post_id)
            );
            
            $result['orphan_list'][] = $video_info;
            
            // Deletar se não for dry run
            if (!$dry_run) {
                $deleted = wp_delete_post($post_id, true); // true = forçar deleção permanente
                
                if ($deleted) {
                    $result['deleted']++;
                } else {
                    $result['errors'][] = "Erro ao deletar post ID {$post_id}: {$post->post_title}";
                }
            }
        }
        
        if ($dry_run) {
            $result['message'] = sprintf(
                'DRY RUN: %d vídeos órfãos seriam deletados. Execute sem dry_run para deletar.',
                count($orphan_videos)
            );
        } else {
            $result['message'] = sprintf(
                'Processo concluído! %d de %d vídeos órfãos foram deletados.',
                $result['deleted'],
                $result['orphan_videos']
            );
        }
        
        return $result;
    }
    
    /**
     * Busca todos os IDs de vídeos que estão em alguma playlist
     * 
     * @return array Array de post IDs
     */
    private static function get_all_videos_in_playlists() {
        global $wpdb;
        
        $video_ids = array();
        
        // Buscar todos os posts/páginas que contêm blocos de playlist
        $posts_with_blocks = $wpdb->get_results("
            SELECT ID, post_content 
            FROM {$wpdb->posts} 
            WHERE post_status IN ('publish', 'draft', 'pending', 'private')
            AND post_content LIKE '%wp:video-playlist/playlist-block%'
        ");
        
        if (empty($posts_with_blocks)) {
            return array();
        }
        
        // Analisar cada post para extrair os IDs dos vídeos
        foreach ($posts_with_blocks as $post) {
            $blocks = parse_blocks($post->post_content);
            $video_ids = array_merge($video_ids, self::extract_video_ids_from_blocks($blocks));
        }
        
        // Remover duplicatas
        return array_unique($video_ids);
    }
    
    /**
     * Extrai IDs de vídeos de blocos Gutenberg recursivamente
     * 
     * @param array $blocks Array de blocos
     * @return array Array de post IDs
     */
    private static function extract_video_ids_from_blocks($blocks) {
        $video_ids = array();
        
        foreach ($blocks as $block) {
            // Verificar se é um bloco de playlist
            if ($block['blockName'] === 'video-playlist/playlist-block') {
                if (isset($block['attrs']['videoPostIds']) && is_array($block['attrs']['videoPostIds'])) {
                    $video_ids = array_merge($video_ids, $block['attrs']['videoPostIds']);
                }
            }
            
            // Processar blocos aninhados recursivamente
            if (!empty($block['innerBlocks'])) {
                $video_ids = array_merge($video_ids, self::extract_video_ids_from_blocks($block['innerBlocks']));
            }
        }
        
        return $video_ids;
    }
    
    /**
     * Gera relatório HTML dos vídeos órfãos
     * 
     * @param array $result Resultado da operação
     * @return string HTML do relatório
     */
    public static function generate_report_html($result) {
        ob_start();
        ?>
        <div class="wrap">
            <h1>🗑️ Deletar Vídeos Órfãos</h1>
            
            <div class="notice notice-warning">
                <p><strong>⚠️ ATENÇÃO:</strong> Esta operação é irreversível! Faça backup antes de prosseguir.</p>
            </div>
            
            <div class="card" style="max-width: 800px; margin: 20px 0; padding: 20px;">
                <h2>📊 Estatísticas</h2>
                <table class="widefat" style="margin-top: 15px;">
                    <tbody>
                        <tr>
                            <td><strong>Total de posts de vídeo:</strong></td>
                            <td><?php echo esc_html($result['total_videos']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Vídeos em playlists:</strong></td>
                            <td><?php echo esc_html($result['videos_in_playlists']); ?></td>
                        </tr>
                        <tr style="background: #fff3cd;">
                            <td><strong>Vídeos órfãos (não estão em playlists):</strong></td>
                            <td><strong><?php echo esc_html($result['orphan_videos']); ?></strong></td>
                        </tr>
                        <?php if (!empty($result['deleted'])): ?>
                        <tr style="background: #d1fae5;">
                            <td><strong>Vídeos deletados:</strong></td>
                            <td><strong><?php echo esc_html($result['deleted']); ?></strong></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (!empty($result['orphan_list'])): ?>
            <div class="card" style="max-width: 1200px; margin: 20px 0; padding: 20px;">
                <h2>📋 Lista de Vídeos Órfãos</h2>
                <table class="widefat striped" style="margin-top: 15px;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>URL do Vídeo</th>
                            <th>Status</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result['orphan_list'] as $video): ?>
                        <tr>
                            <td><?php echo esc_html($video['id']); ?></td>
                            <td><?php echo esc_html($video['title']); ?></td>
                            <td>
                                <?php if (!empty($video['url'])): ?>
                                    <a href="<?php echo esc_url($video['url']); ?>" target="_blank">
                                        <?php echo esc_html(substr($video['url'], 0, 50)) . '...'; ?>
                                    </a>
                                <?php else: ?>
                                    <em>Sem URL</em>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($video['status']); ?></td>
                            <td><?php echo esc_html($video['date']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($result['errors'])): ?>
            <div class="notice notice-error">
                <h3>❌ Erros:</h3>
                <ul>
                    <?php foreach ($result['errors'] as $error): ?>
                        <li><?php echo esc_html($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <div class="notice notice-info">
                <p><strong>✅ <?php echo esc_html($result['message']); ?></strong></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

// ========================================
// OPÇÃO 1: Criar página de admin
// ========================================

function vpb_orphan_videos_admin_menu() {
    add_management_page(
        'Deletar Vídeos Órfãos',
        'Deletar Vídeos Órfãos',
        'manage_options',
        'vpb-delete-orphans',
        'vpb_orphan_videos_admin_page'
    );
}
add_action('admin_menu', 'vpb_orphan_videos_admin_menu');

function vpb_orphan_videos_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Você não tem permissão para acessar esta página.');
    }
    
    $result = null;
    
    // Processar formulário
    if (isset($_POST['vpb_action'])) {
        check_admin_referer('vpb_delete_orphans_nonce');
        
        $dry_run = ($_POST['vpb_action'] === 'preview');
        $result = VPB_Delete_Orphan_Videos::run($dry_run);
    } else {
        // Apenas preview inicial
        $result = VPB_Delete_Orphan_Videos::run(true);
    }
    
    // Mostrar relatório
    echo VPB_Delete_Orphan_Videos::generate_report_html($result);
    
    // Formulário de ação
    ?>
    <div class="card" style="max-width: 800px; margin: 20px 0; padding: 20px;">
        <h2>🚀 Ações</h2>
        <form method="post" action="">
            <?php wp_nonce_field('vpb_delete_orphans_nonce'); ?>
            
            <p>
                <button type="submit" name="vpb_action" value="preview" class="button button-secondary">
                    🔍 Atualizar Preview
                </button>
                
                <?php if (!empty($result['orphan_videos'])): ?>
                <button type="submit" name="vpb_action" value="delete" class="button button-primary" 
                        onclick="return confirm('⚠️ ATENÇÃO!\n\nVocê está prestes a DELETAR PERMANENTEMENTE <?php echo esc_js($result['orphan_videos']); ?> post(s) de vídeo.\n\nEsta ação NÃO PODE ser desfeita!\n\nTem certeza que deseja continuar?');">
                    🗑️ DELETAR <?php echo esc_html($result['orphan_videos']); ?> Vídeos Órfãos
                </button>
                <?php endif; ?>
            </p>
        </form>
    </div>
    <?php
}

// ========================================
// OPÇÃO 2: Executar via WP-CLI
// ========================================

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('vpb delete-orphans', function($args, $assoc_args) {
        $dry_run = isset($assoc_args['dry-run']);
        
        WP_CLI::line('🔍 Analisando vídeos...');
        $result = VPB_Delete_Orphan_Videos::run($dry_run);
        
        WP_CLI::line("\n📊 ESTATÍSTICAS:");
        WP_CLI::line("Total de vídeos: {$result['total_videos']}");
        WP_CLI::line("Vídeos em playlists: {$result['videos_in_playlists']}");
        WP_CLI::line("Vídeos órfãos: {$result['orphan_videos']}");
        
        if (!empty($result['deleted'])) {
            WP_CLI::success("Deletados: {$result['deleted']}");
        }
        
        if (!empty($result['orphan_list'])) {
            WP_CLI::line("\n📋 VÍDEOS ÓRFÃOS:");
            foreach ($result['orphan_list'] as $video) {
                WP_CLI::line("  - ID {$video['id']}: {$video['title']}");
            }
        }
        
        WP_CLI::line("\n" . $result['message']);
    });
}

// ========================================
// OPÇÃO 3: Executar uma única vez
// ========================================

/**
 * Descomente as linhas abaixo para executar UMA VEZ automaticamente
 * ATENÇÃO: Remova o código depois de executar!
 */

/*
add_action('admin_init', function() {
    if (get_transient('vpb_orphans_deleted')) {
        return; // Já foi executado
    }
    
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Executar
    $result = VPB_Delete_Orphan_Videos::run(false); // false = deletar de verdade
    
    // Marcar como executado
    set_transient('vpb_orphans_deleted', true, WEEK_IN_SECONDS);
    
    // Mostrar aviso
    add_action('admin_notices', function() use ($result) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>✅ Vídeos órfãos deletados:</strong> <?php echo esc_html($result['message']); ?></p>
        </div>
        <?php
    });
});
*/


// ============================================================================
// LUCIDE ICONS - ADICIONE NO FINAL DO SEU FUNCTIONS.PHP
// ============================================================================

/**
 * Carrega Lucide Icons globalmente
 * Disponível para tema, plugins e blocos Gutenberg
 * Todos os 1000+ ícones: https://lucide.dev/icons/
 * 
 * USO: <i data-lucide="heart"></i>
 */
function bebelume_enqueue_lucide_icons() {
    // CSS do Lucide (opcional)
    wp_enqueue_style(
        'lucide-icons',
        'https://unpkg.com/lucide-static@latest/font/lucide.css',
        array(),
        null
    );
    
    // JavaScript do Lucide
    wp_enqueue_script(
        'lucide-icons',
        'https://unpkg.com/lucide@latest/dist/umd/lucide.js',
        array(),
        null,
        true
    );
    
    // Inicializa os ícones
    wp_add_inline_script(
        'lucide-icons',
        'document.addEventListener("DOMContentLoaded", function() {
            if (typeof lucide !== "undefined") {
                lucide.createIcons();
            }
        });'
    );
}

// Frontend
add_action('wp_enqueue_scripts', 'bebelume_enqueue_lucide_icons');

// Editor Gutenberg
add_action('enqueue_block_editor_assets', 'bebelume_enqueue_lucide_icons');

// Admin
add_action('admin_enqueue_scripts', 'bebelume_enqueue_lucide_icons');