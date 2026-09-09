<?php
/**
 * Plugin Name: Bebelume ArtEduca
 * Plugin URI: https://bebelume.com.br
 * Description: Plugin com blocos Gutenberg personalizados para conteúdo educacional, CRUD pedagógico e seleção por post.
 * Version: 1.38.2
 * Author: Bebelume
 * Author URI: https://bebelume.com.br
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bebelume-arteduca
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

// Evita acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Define constantes do plugin
define('BEBELUME_ARTEDUCA_VERSION', '1.38.2');
define('BEBELUME_ARTEDUCA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BEBELUME_ARTEDUCA_PLUGIN_URL', plugin_dir_url(__FILE__));

// Módulos pedagógicos
require_once BEBELUME_ARTEDUCA_PLUGIN_DIR . 'includes/crud-pedagogico.php';
require_once BEBELUME_ARTEDUCA_PLUGIN_DIR . 'includes/meta-pedagogico.php';

// Menu ArtEduca > Páginas (configuração de páginas iniciais)
require_once BEBELUME_ARTEDUCA_PLUGIN_DIR . 'includes/paginas-arteduca.php';

// Custom Body Classes
require_once BEBELUME_ARTEDUCA_PLUGIN_DIR . 'includes/custom-body-classes.php';

/**
 * Registra os blocos do plugin
 */
function bebelume_arteduca_register_blocks() {
    // Registra o bloco Título ArtEduca
    register_block_type(BEBELUME_ARTEDUCA_PLUGIN_DIR . 'blocks/titulo-arteduca');
    
    // Registra o bloco Accordion Tipo 1
    register_block_type(BEBELUME_ARTEDUCA_PLUGIN_DIR . 'blocks/accordion-tipo1');
    
    // Registra o bloco Accordion Tipo 2
    register_block_type(BEBELUME_ARTEDUCA_PLUGIN_DIR . 'blocks/accordion-tipo2');
    
    // Registra o bloco Icon Box
    register_block_type(BEBELUME_ARTEDUCA_PLUGIN_DIR . 'blocks/icon-box');
    
    // Registra o bloco Documento
    register_block_type(BEBELUME_ARTEDUCA_PLUGIN_DIR . 'blocks/documento');
    
    // Registra o bloco Link Externo
    register_block_type(BEBELUME_ARTEDUCA_PLUGIN_DIR . 'blocks/link-externo');
    
    // Registra o bloco Campos Pedagógicos
    register_block_type(BEBELUME_ARTEDUCA_PLUGIN_DIR . 'blocks/campos-pedagogicos');
    
    // Registra o bloco Filtro ArtEduca
    register_block_type(BEBELUME_ARTEDUCA_PLUGIN_DIR . 'blocks/filtro-arteduca');
    
    // Registra o bloco Buscador Bebelume ArtEduca
    register_block_type(BEBELUME_ARTEDUCA_PLUGIN_DIR . 'blocks/buscador-arteduca');

    // Registra o bloco ArtEduca Footer
    register_block_type(BEBELUME_ARTEDUCA_PLUGIN_DIR . 'blocks/arteduca-footer');

    // Registra o bloco Marketing Bebelume - Capa (com cache-busting via filemtime)
    $capa_dir = BEBELUME_ARTEDUCA_PLUGIN_DIR . 'blocks/marketing-capa';
    $capa_url = BEBELUME_ARTEDUCA_PLUGIN_URL . 'blocks/marketing-capa';

    wp_register_script(
        'bebelume-marketing-capa-editor',
        $capa_url . '/block.js',
        array('wp-blocks', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-data', 'wp-element'),
        filemtime($capa_dir . '/block.js'),
        true
    );

    wp_register_style(
        'bebelume-marketing-capa-style',
        $capa_url . '/style.css',
        array(),
        filemtime($capa_dir . '/style.css')
    );

    register_block_type($capa_dir, array(
        'editor_script' => 'bebelume-marketing-capa-editor',
        'style'          => 'bebelume-marketing-capa-style',
    ));

    // Registra o bloco Marketing Bebelume - Conteúdo (com cache-busting via filemtime)
    $conteudo_dir = BEBELUME_ARTEDUCA_PLUGIN_DIR . 'blocks/marketing-conteudo';
    $conteudo_url = BEBELUME_ARTEDUCA_PLUGIN_URL . 'blocks/marketing-conteudo';

    wp_register_script(
        'bebelume-marketing-conteudo-editor',
        $conteudo_url . '/block.js',
        array('wp-blocks', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-data', 'wp-element'),
        filemtime($conteudo_dir . '/block.js'),
        true
    );

    wp_register_style(
        'bebelume-marketing-conteudo-style',
        $conteudo_url . '/style.css',
        array(),
        filemtime($conteudo_dir . '/style.css')
    );

    register_block_type($conteudo_dir, array(
        'editor_script' => 'bebelume-marketing-conteudo-editor',
        'style'          => 'bebelume-marketing-conteudo-style',
    ));

    // Registra o bloco Marketing Bebelume - Título (com cache-busting via filemtime)
    $titulo_dir = BEBELUME_ARTEDUCA_PLUGIN_DIR . 'blocks/marketing-titulo';
    $titulo_url = BEBELUME_ARTEDUCA_PLUGIN_URL . 'blocks/marketing-titulo';

    wp_register_script(
        'bebelume-marketing-titulo-editor',
        $titulo_url . '/block.js',
        array('wp-blocks', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-data', 'wp-element'),
        filemtime($titulo_dir . '/block.js'),
        true
    );

    wp_register_style(
        'bebelume-marketing-titulo-style',
        $titulo_url . '/style.css',
        array(),
        filemtime($titulo_dir . '/style.css')
    );

    register_block_type($titulo_dir, array(
        'editor_script' => 'bebelume-marketing-titulo-editor',
        'style'          => 'bebelume-marketing-titulo-style',
    ));

    // Registra o bloco Marketing Bebelume - Texto (com cache-busting via filemtime)
    $texto_dir = BEBELUME_ARTEDUCA_PLUGIN_DIR . 'blocks/marketing-texto';
    $texto_url = BEBELUME_ARTEDUCA_PLUGIN_URL . 'blocks/marketing-texto';

    wp_register_script(
        'bebelume-marketing-texto-editor',
        $texto_url . '/block.js',
        array('wp-blocks', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-data', 'wp-element'),
        filemtime($texto_dir . '/block.js'),
        true
    );

    wp_register_style(
        'bebelume-marketing-texto-style',
        $texto_url . '/style.css',
        array(),
        filemtime($texto_dir . '/style.css')
    );

    register_block_type($texto_dir, array(
        'editor_script' => 'bebelume-marketing-texto-editor',
        'style'          => 'bebelume-marketing-texto-style',
    ));
}
add_action('init', 'bebelume_arteduca_register_blocks');

/**
 * Enfileira estilos do frontend
 */
function bebelume_arteduca_enqueue_frontend_assets() {
    wp_enqueue_style(
        'bebelume-arteduca-frontend',
        BEBELUME_ARTEDUCA_PLUGIN_URL . 'assets/css/frontend.css',
        array(),
        BEBELUME_ARTEDUCA_VERSION
    );
}
add_action('wp_enqueue_scripts', 'bebelume_arteduca_enqueue_frontend_assets');

/**
 * Adiciona categoria de blocos personalizada
 */
function bebelume_arteduca_block_category($categories) {
    return array_merge(
        array(
            array(
                'slug'  => 'bebelume-arteduca',
                'title' => __('Bebelume ArtEduca', 'bebelume-arteduca'),
                'icon'  => 'welcome-learn-more',
            ),
        ),
        $categories
    );
}
add_filter('block_categories_all', 'bebelume_arteduca_block_category', 10, 1);

/**
 * Enfileira Lucide Icons no editor
 */
function bebelume_arteduca_enqueue_editor_assets() {
    // Lucide Icons LOCAL (evita timeout em requisições externas)
    wp_enqueue_script(
        'lucide-icons-editor',
        BEBELUME_ARTEDUCA_PLUGIN_URL . 'assets/js/lucide.min.js',
        array(),
        BEBELUME_ARTEDUCA_VERSION,
        true
    );
    
    // Inicialização do Lucide no editor
    wp_add_inline_script(
        'lucide-icons-editor',
        '
        (function() {
            function initLucide() {
                if (typeof lucide !== "undefined") {
                    lucide.createIcons();
                }
            }
            
            // Inicializa ao carregar
            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", initLucide);
            } else {
                initLucide();
            }
            
            // Reinicializa quando blocos são adicionados/alterados
            if (wp && wp.data) {
                wp.data.subscribe(function() {
                    setTimeout(initLucide, 100);
                });
            }
        })();
        '
    );
}
add_action('enqueue_block_editor_assets', 'bebelume_arteduca_enqueue_editor_assets');

/**
 * Enfileira JavaScript do frontend
 */
function bebelume_arteduca_enqueue_frontend_js() {
    // jQuery (accordion)
    wp_enqueue_script(
        'bebelume-arteduca-frontend-js',
        BEBELUME_ARTEDUCA_PLUGIN_URL . 'assets/js/frontend.js',
        array('jquery'),
        BEBELUME_ARTEDUCA_VERSION,
        true
    );
    
    // Filtro ArtEduca frontend
    wp_enqueue_script(
        'bebelume-filtro-arteduca-frontend',
        BEBELUME_ARTEDUCA_PLUGIN_URL . 'blocks/filtro-arteduca/frontend.js',
        array(),
        BEBELUME_ARTEDUCA_VERSION,
        true
    );
    
    // Passar URL do plugin e status do usuário para JavaScript
    wp_localize_script(
        'bebelume-filtro-arteduca-frontend',
        'bebelumeArteducaData',
        array(
            'pluginUrl' => BEBELUME_ARTEDUCA_PLUGIN_URL,
            // Passa 1 ou 0 explicitamente — wp_localize_script serializa PHP como string,
            // então o JS recebe "1" ou "0" que são inequívocos (evita o bug de "false" ser truthy)
            'hasAccess' => bebelume_arteduca_user_has_access() ? 1 : 0,
            'plansUrl'  => home_url( '/planos/' ),
        )
    );
    
    // Lucide Icons LOCAL
    wp_enqueue_script(
        'lucide-icons',
        BEBELUME_ARTEDUCA_PLUGIN_URL . 'assets/js/lucide.min.js',
        array(),
        BEBELUME_ARTEDUCA_VERSION,
        true
    );
    
    // Inicialização do Lucide
    wp_add_inline_script(
        'lucide-icons',
        'document.addEventListener("DOMContentLoaded", function() { if (typeof lucide !== "undefined") { lucide.createIcons(); } });'
    );
}
add_action('wp_enqueue_scripts', 'bebelume_arteduca_enqueue_frontend_js');

/**
 * Verifica se o usuário atual tem acesso ao ArtEduca.
 * Retorna true se: logado + QUALQUER nível PMPro ativo (não importa o ID).
 *
 * ATENÇÃO: pmpro_hasMembershipLevel() retorna uma string de erro (truthy!)
 * em caso de falha — por isso usamos === true explicitamente.
 *
 * Chamado sem argumentos, pmpro_hasMembershipLevel() verifica se o usuário
 * tem QUALQUER nível de assinatura ativo, em vez de uma lista fixa de IDs.
 * Assim, novos níveis criados no PMPro já funcionam automaticamente,
 * sem precisar mexer no código.
 */
function bebelume_arteduca_user_has_access() {
    if ( ! is_user_logged_in() ) return false;
    if ( ! function_exists( 'pmpro_hasMembershipLevel' ) ) {
        if ( function_exists( 'bebelume_arteduca_debug_log' ) ) {
            bebelume_arteduca_debug_log('user_has_access: função pmpro_hasMembershipLevel() NÃO existe — PMPro está ativo?');
        }
        return false;
    }
    $resultado = pmpro_hasMembershipLevel();
    if ( function_exists( 'bebelume_arteduca_debug_log' ) ) {
        bebelume_arteduca_debug_log('user_has_access: pmpro_hasMembershipLevel() retornou => ' . var_export($resultado, true));
    }
    // Compara com === true para evitar falso-positivo com string de erro
    return $resultado === true;
}



/**
 * Registra o bloco ArtEduca Footer (adicionado via register_block_type acima)
 * e injeta o JS que move o .arteduca-footer-block para o footer
 */
function bebelume_arteduca_footer_block_move_js() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var block  = document.querySelector('.arteduca-footer-block');
        var footer = document.querySelector('.site-footer');
        if (block && footer) {
            block.parentNode.removeChild(block);
            block.style.display = 'block';
            footer.insertBefore(block, footer.firstChild);
        } else if (block) {
            // Fallback: sem footer, mostra no lugar
            block.classList.add('no-footer');
        }
    });
    </script>
    <?php
}
add_action('wp_footer', 'bebelume_arteduca_footer_block_move_js');
