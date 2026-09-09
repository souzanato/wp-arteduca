<?php
/**
 * Plugin Name: Bebelume Profile
 * Plugin URI: https://github.com/yourname/bebelume-profile
 * Description: Plugin de perfil leve e colorido para WordPress, com design limpo e amigável.
 * Version: 1.3.0
 * Author: Seu Nome
 * Author URI: https://seusite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bebelume-profile
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

// Se acessado diretamente, abortar
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes do plugin
define('BBL_PROFILE_VERSION', '1.3.0');
define('BBL_PROFILE_DIR', plugin_dir_path(__FILE__));
define('BBL_PROFILE_URL', plugin_dir_url(__FILE__));
define('BBL_PROFILE_BASENAME', plugin_basename(__FILE__));

// Carregar integração PMPro
if (file_exists(BBL_PROFILE_DIR . 'includes/class-pmpro-integration.php')) {
    require_once BBL_PROFILE_DIR . 'includes/class-pmpro-integration.php';
}

// Carregar campos customizados PMPro (Nome, Sobrenome, Username automático)
// Carregar blocos Gutenberg
if (file_exists(BBL_PROFILE_DIR . 'includes/class-blocks.php')) {
    require_once BBL_PROFILE_DIR . 'includes/class-blocks.php';
}

// Carregar PMPro Redirect (metabox de redirecionamento por plano)
if (file_exists(BBL_PROFILE_DIR . 'includes/class-pmpro-redirect.php')) {
    require_once BBL_PROFILE_DIR . 'includes/class-pmpro-redirect.php';
}

/**
 * Classe principal do plugin
 */
class Bebelume_Profile {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Controlar visibilidade da admin bar (para todos os usuários)
        add_filter('show_admin_bar', array($this, 'control_admin_bar'));
        
        // Só aplicar modificações se o usuário NÃO for administrador
        if (!current_user_can('administrator')) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
            add_action('admin_init', array($this, 'intercept_profile_page'));
            add_action('admin_init', array($this, 'redirect_from_dashboard'));
            
            // Hook para frontend (páginas do PMPro)
            add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
            add_action('admin_footer', array($this, 'add_admin_bar_logo'));
        }
        
        // AJAX handlers (para todos os usuários)
        add_action('wp_ajax_bbl_save_profile', array($this, 'ajax_save_profile'));
        add_action('wp_ajax_bbl_render_profile', array($this, 'ajax_render_profile'));
        
        // Shortcode de registro (mantido para compatibilidade)
        add_shortcode('bebelume_register', array($this, 'register_shortcode'));
        
        // Sistema de rotas customizadas para registro
        add_action('init', array($this, 'add_register_rewrite_rules'));
        add_action('template_redirect', array($this, 'handle_register_page'));
        add_filter('query_vars', array($this, 'add_register_query_vars'));

        // Sistema de rotas customizadas para login
        add_action('init', array($this, 'add_login_rewrite_rules'));
        add_action('template_redirect', array($this, 'handle_login_page'));
        add_filter('query_vars', array($this, 'add_login_query_vars'));

        // Sistema de rotas customizadas para lost password
        add_action('init', array($this, 'add_lostpassword_rewrite_rules'));
        add_action('template_redirect', array($this, 'handle_lostpassword_page'));
        add_filter('query_vars', array($this, 'add_lostpassword_query_vars'));

        // Interceptar página de reset password
        add_action('login_init', array($this, 'intercept_reset_password'));
        
        // Hooks de ativação/desativação
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Controlar visibilidade da admin bar
     */
    public function control_admin_bar($show) {
        // Se for administrador, sempre mostrar
        if (current_user_can('administrator')) {
            return true;
        }
        
        // Se NÃO for administrador
        global $pagenow;
        
        // Mostrar APENAS na página de perfil
        if ($pagenow === 'profile.php') {
            return true;
        }
        
        // Esconder em todas as outras páginas
        return false;
    }
        
    /**
     * Interceptar a página de perfil padrão
     */
    public function intercept_profile_page() {
        global $pagenow;
        
        // Só interceptar se NÃO for administrador
        if (current_user_can('administrator')) {
            return;
        }
        
        if ($pagenow === 'profile.php') {
            add_action('admin_head', array($this, 'hide_default_profile'));
            add_action('admin_footer', array($this, 'render_custom_profile'));
        }
    }

    /**
     * Redirecionar usuários não-admin do Dashboard para o Perfil
     */
    public function redirect_from_dashboard() {
        global $pagenow;
        
        // Só redirecionar se NÃO for administrador
        if (current_user_can('administrator')) {
            return;
        }
        
        // Se Bebelume Roles está ativo e controla este usuário, não interferir
        if ( class_exists( 'Bebelume_Roles' ) ) {
            $user = wp_get_current_user();
            // Checa meta do perfil
            if ( get_user_meta( $user->ID, '_bebelume_profile_id', true ) ) {
                return;
            }
            // Checa se a role do usuário é de um perfil Bebelume Roles
            foreach ( (array) $user->roles as $role ) {
                if ( strpos( $role, 'bebelume_profile_' ) === 0 ) {
                    return;
                }
            }
        }
        
        // Lista de páginas bloqueadas para usuários não-admin
        $blocked_pages = array(
            'index.php',           // Dashboard
            'update-core.php',     // Atualizações
            'plugins.php',         // Plugins
            'themes.php',          // Temas
            'tools.php',           // Ferramentas
            'options-general.php', // Configurações
        );
        
        // Se estiver em uma página bloqueada, redirecionar
        if (in_array($pagenow, $blocked_pages)) {
            wp_safe_redirect(admin_url('profile.php'));
            exit;
        }
    }
    
    /**
     * Esconder o formulário padrão do WordPress
     */
    public function hide_default_profile() {
        echo '<style>
            #your-profile { display: none !important; }
            #profile-page > h1 { display: none !important; }
            #profile-page > hr { display: none !important; }
        </style>';
    }
    
    /**
     * Renderizar nosso perfil customizado
     */
    public function render_custom_profile() {
        echo '<script>
            jQuery(document).ready(function($) {
                $("#wpbody-content").prepend($("<div id=\"bebelume-profile-container\"></div>"));
                $("#bebelume-profile-container").load("' . admin_url('admin-ajax.php?action=bbl_render_profile') . '");
            });
        </script>';
    }
    
    public function enqueue_admin_assets($hook) {
        // NÃO carregar CSS se for administrador
        if (current_user_can('administrator')) {
            return;
        }
        
        // Carregar CSS único compilado em TODAS as páginas do admin
        wp_enqueue_style(
            'bbl-profile-main',
            BBL_PROFILE_URL . 'assets/css/main.css',
            array(),
            BBL_PROFILE_VERSION
        );
        
        // Carregar JS apenas na página de perfil
        if ($hook === 'profile.php') {
            wp_enqueue_script(
                'bbl-profile-main',
                BBL_PROFILE_URL . 'assets/js/main.js',
                array('jquery'),
                BBL_PROFILE_VERSION,
                true
            );
            
            wp_localize_script('bbl-profile-main', 'bblProfileData', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bbl_profile_nonce'),
                'userId' => get_current_user_id(),
                'userName' => wp_get_current_user()->display_name,
                'userEmail' => wp_get_current_user()->user_email,
            ));
        }
    }
    
    /**
     * AJAX: Renderizar template do perfil
     */
    public function ajax_render_profile() {
        // Verificar se NÃO é administrador
        if (current_user_can('administrator')) {
            wp_send_json_error(array('message' => 'Administradores usam o perfil padrão'));
        }
        
        include BBL_PROFILE_DIR . 'templates/profile-page.php';
        wp_die();
    }
    
    public function ajax_save_profile() {
        check_ajax_referer('bbl_profile_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(array('message' => 'Usuário não autenticado'));
        }
        
        $data = array();
        
        if (isset($_POST['first_name'])) {
            $data['first_name'] = sanitize_text_field($_POST['first_name']);
            update_user_meta($user_id, 'first_name', $data['first_name']);
        }
        
        if (isset($_POST['last_name'])) {
            $data['last_name'] = sanitize_text_field($_POST['last_name']);
            update_user_meta($user_id, 'last_name', $data['last_name']);
        }
        
        // Atualizar senha se fornecida
        if (isset($_POST['new_password']) && !empty($_POST['new_password'])) {
            $new_password = $_POST['new_password'];
            
            if (strlen($new_password) < 8) {
                wp_send_json_error(array('message' => 'A senha deve ter no mínimo 8 caracteres'));
            }
            
            wp_set_password($new_password, $user_id);
            $data['password_changed'] = true;
        }
        
        wp_send_json_success(array(
            'message' => 'Perfil salvo com sucesso! 🎉',
            'data' => $data
        ));
    }
    
    public function enqueue_frontend_assets() {
        // NÃO carregar no painel wp-admin (editor, etc.)
        if (is_admin()) {
            return;
        }
        
        // Carregar CSS principal
        wp_enqueue_style(
            'bbl-profile-main',
            BBL_PROFILE_URL . 'assets/css/main.css',
            array(),
            BBL_PROFILE_VERSION
        );

        // Font Awesome — ícones no checkout, perfil e conta
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
            array(),
            '6.5.1'
        );

        // ===== PLYR.IO =====
        
        // 1. CSS do Plyr (CDN)
        wp_enqueue_style(
            'plyr-css',
            'https://cdn.plyr.io/3.7.8/plyr.css',
            array(),
            '3.7.8'
        );
        
        // 2. CSS customizado do Plyr (Bebelume)
        wp_enqueue_style(
            'bbl-plyr-custom',
            BBL_PROFILE_URL . 'assets/css/plyr-custom.css',
            array('plyr-css'),
            BBL_PROFILE_VERSION
        );
        
        // 3. JavaScript do Plyr (CDN)
        wp_enqueue_script(
            'plyr-js',
            'https://cdn.plyr.io/3.7.8/plyr.js',
            array(),
            '3.7.8',
            true
        );
        
        // 4. JavaScript de inicialização do Plyr (Bebelume)
        wp_enqueue_script(
            'bbl-plyr-init',
            BBL_PROFILE_URL . 'assets/js/plyr-init.js',
            array('jquery', 'plyr-js'),
            BBL_PROFILE_VERSION,
            true
        );
        
        // 5. ⭐ IMPORTANTE: Passar configurações PHP para JavaScript
        wp_localize_script('bbl-plyr-init', 'bebelumePlyrConfig', array(
            'autoplay' => false,
            'muted' => false,
            'debug' => WP_DEBUG,
        ));
    }

    /**
     * Adicionar logo na admin bar (placeholder)
     */
    public function add_admin_bar_logo() {
        // Implementar se necessário
    }
    
    /**
     * Shortcode para exibir o formulário de registro
     */
    public function register_shortcode($atts) {
        ob_start();
        include BBL_PROFILE_DIR . 'templates/register.php';
        return ob_get_clean();
    }
    
    /**
     * Adicionar rewrite rules para a página de registro
     */
    public function add_register_rewrite_rules() {
        // Adiciona a regra: /auth/registro/ ou /registro/
        add_rewrite_rule(
            '^auth/registro/?$',
            'index.php?bbl_register_page=1',
            'top'
        );
        
        // Regra alternativa sem /auth/
        add_rewrite_rule(
            '^registro/?$',
            'index.php?bbl_register_page=1',
            'top'
        );
    }
    
    /**
     * Adicionar query vars customizadas
     */
    public function add_register_query_vars($vars) {
        $vars[] = 'bbl_register_page';
        return $vars;
    }
    
    /**
     * Interceptar e renderizar a página de registro
     */
    public function handle_register_page() {
        // PROTEÇÃO: Nunca interceptar requisições do painel admin
        if (is_admin()) {
            return;
        }
        
        $is_register = get_query_var('bbl_register_page');
        
        if ($is_register) {
            // ✅ ENFILEIRAR CSS ANTES DE TUDO!
            wp_enqueue_style(
                'bbl-profile-main',
                BBL_PROFILE_URL . 'assets/css/main.css',
                array(),
                BBL_PROFILE_VERSION
            );
            
            // Remover header e footer do WordPress
            remove_action('wp_head', '_wp_render_title_tag', 1);
            
            // Começar output
            ob_start();
            ?>
            <!DOCTYPE html>
            <html <?php language_attributes(); ?>>
            <head>
                <meta charset="<?php bloginfo('charset'); ?>">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Registro - <?php bloginfo('name'); ?></title>
                <?php wp_head(); ?>
            </head>
            <body <?php body_class('bebelume-register-body'); ?>>
            <?php
            
            // Renderizar o template de registro
            include BBL_PROFILE_DIR . 'templates/register.php';
            
            // Footer básico
            wp_footer();
            ?>
            </body>
            </html>
            <?php
            
            echo ob_get_clean();
            exit;
        }
    }
    
    /**
     * Helper function para pegar URL de registro
     */
    public static function get_register_url() {
        return home_url('/auth/registro/');
    }
    
    public function activate() {
        add_option('bbl_profile_activated', current_time('timestamp'));
        
        // Adicionar as rewrite rules antes de fazer flush
        $this->add_register_rewrite_rules();
        $this->add_login_rewrite_rules(); 
        $this->add_lostpassword_rewrite_rules(); 
        
        // Recriar as regras de URL
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Remover as rewrite rules ao desativar
        flush_rewrite_rules();
    }

    /**
     * Adicionar rewrite rules para a página de login
     */
    public function add_login_rewrite_rules() {
        // Adiciona a regra: /auth/login/
        add_rewrite_rule(
            '^auth/login/?$',
            'index.php?bbl_login_page=1',
            'top'
        );
        
        // Regra alternativa sem /auth/
        add_rewrite_rule(
            '^login/?$',
            'index.php?bbl_login_page=1',
            'top'
        );
    }

    /**
     * Adicionar query vars customizadas para login
     */
    public function add_login_query_vars($vars) {
        $vars[] = 'bbl_login_page';
        return $vars;
    }

    /**
     * Interceptar e renderizar a página de login
     */
    public function handle_login_page() {
        // PROTEÇÃO: Nunca interceptar requisições do painel admin
        if (is_admin()) {
            return;
        }
        
        $is_login = get_query_var('bbl_login_page');
        
        if ($is_login) {
            // Se já estiver logado, redirecionar para home
            if (is_user_logged_in()) {
                wp_redirect(home_url());
                exit;
            }
            
            // ✅ ENFILEIRAR CSS ANTES DE TUDO!
            wp_enqueue_style(
                'bbl-profile-main',
                BBL_PROFILE_URL . 'assets/css/main.css',
                array(),
                BBL_PROFILE_VERSION
            );
            
            // Remover header e footer do WordPress
            remove_action('wp_head', '_wp_render_title_tag', 1);
            
            // Começar output
            ob_start();
            ?>
            <!DOCTYPE html>
            <html <?php language_attributes(); ?>>
            <head>
                <meta charset="<?php bloginfo('charset'); ?>">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Login - <?php bloginfo('name'); ?></title>
                <?php wp_head(); ?>
            </head>
            <body <?php body_class('bebelume-login-body'); ?>>
            <?php
            
            // Renderizar o template de login
            include BBL_PROFILE_DIR . 'templates/login.php';
            
            // Footer básico
            wp_footer();
            ?>
            </body>
            </html>
            <?php
            
            echo ob_get_clean();
            exit;
        }
    }

    /**
     * Helper function para pegar URL de login
     */
    public static function get_login_url() {
        return home_url('/auth/login/');
    }

    /**
     * Adicionar rewrite rules para a página de esqueceu a senha
     */
    public function add_lostpassword_rewrite_rules() {
        add_rewrite_rule(
            '^auth/esqueceu-senha/?$',
            'index.php?bbl_lostpassword_page=1',
            'top'
        );
        
        add_rewrite_rule(
            '^esqueceu-senha/?$',
            'index.php?bbl_lostpassword_page=1',
            'top'
        );
    }

    public function add_lostpassword_query_vars($vars) {
        $vars[] = 'bbl_lostpassword_page';
        return $vars;
    }

    public function handle_lostpassword_page() {
        // PROTEÇÃO: Nunca interceptar requisições do painel admin
        if (is_admin()) {
            return;
        }
        
        $is_lostpassword = get_query_var('bbl_lostpassword_page');
        
        if ($is_lostpassword) {
            if (is_user_logged_in()) {
                wp_redirect(home_url());
                exit;
            }
            
            // ✅ ENFILEIRAR CSS ANTES DE TUDO!
            wp_enqueue_style(
                'bbl-profile-main',
                BBL_PROFILE_URL . 'assets/css/main.css',
                array(),
                BBL_PROFILE_VERSION
            );
            
            remove_action('wp_head', '_wp_render_title_tag', 1);
            ob_start();
            ?>
            <!DOCTYPE html>
            <html <?php language_attributes(); ?>>
            <head>
                <meta charset="<?php bloginfo('charset'); ?>">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Esqueceu a Senha - <?php bloginfo('name'); ?></title>
                <?php wp_head(); ?>
            </head>
            <body <?php body_class('bebelume-lostpassword-body'); ?>>
            <?php
            
            include BBL_PROFILE_DIR . 'templates/lost-password.php';
            
            wp_footer();
            ?>
            </body>
            </html>
            <?php
            
            echo ob_get_clean();
            exit;
        }
    }

    public static function get_lostpassword_url() {
        return home_url('/auth/esqueceu-senha/');
    }

    /**
     * Interceptar a página de reset password do WordPress
     */
    public function intercept_reset_password() {
        // PROTEÇÃO: Nunca interceptar requisições do painel admin
        if (is_admin()) {
            return;
        }
        
        // Detectar se é a página de reset password
        if (isset($_GET['action']) && $_GET['action'] === 'rp' && isset($_GET['key']) && isset($_GET['login'])) {
            
            // ✅ ENFILEIRAR CSS ANTES DE TUDO!
            wp_enqueue_style(
                'bbl-profile-main',
                BBL_PROFILE_URL . 'assets/css/main.css',
                array(),
                BBL_PROFILE_VERSION
            );
            
            remove_action('wp_head', '_wp_render_title_tag', 1);
            ob_start();
            ?>
            <!DOCTYPE html>
            <html <?php language_attributes(); ?>>
            <head>
                <meta charset="<?php bloginfo('charset'); ?>">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Redefinir Senha - <?php bloginfo('name'); ?></title>
                <?php wp_head(); ?>
            </head>
            <body <?php body_class('bebelume-resetpass-body'); ?>>
            <?php
            
            include BBL_PROFILE_DIR . 'templates/reset-password.php';
            
            wp_footer();
            ?>
            </body>
            </html>
            <?php
            
            echo ob_get_clean();
            exit;
        }
    }

    public static function get_resetpassword_url($key, $login) {
        return add_query_arg(
            array(
                'action' => 'rp',
                'key' => $key,
                'login' => $login
            ),
            site_url('wp-login.php')
        );
    }

} // FIM DA CLASSE Bebelume_Profile

/**
 * Inicializar o plugin
 */
function bebelume_profile_init() {
    return Bebelume_Profile::get_instance();
}

add_action('plugins_loaded', 'bebelume_profile_init');